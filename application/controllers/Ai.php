<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Controller AI Assistant
 * -------------------------------------------------------------
 * Konsep AI pada project ini sengaja dibuat GRATIS / TANPA API.
 * Sistem bekerja memakai rule-based intent detection, bukan OpenAI.
 *
 * File ini berperan sebagai penghubung antara:
 * 1. Chat frontend di assets/js/ai.js
 * 2. IntentEngine.php untuk membaca maksud kalimat user
 * 3. Database CodeIgniter 3 untuk mengambil / mengubah data
 */
class Ai extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Database dan session wajib untuk AI, karena AI membaca data dan menyimpan state konfirmasi.
        $this->load->database();
        $this->load->library('session');
        $this->load->library('IntentEngine', null, 'intent_engine');
        $this->load->model('Order_model', 'orders');

        // Proteksi halaman AI agar tidak bisa dipakai tanpa login.
        // Endpoint chat AJAX tetap mendapat JSON agar tidak merusak tampilan frontend.
        if (!$this->session->userdata('user_id')) {
            $method = $this->uri->segment(2);

            // Semua endpoint AI selain halaman index dipakai oleh fetch/AJAX, jadi balas JSON.
            if ($this->input->is_ajax_request() || ($method && $method !== 'index')) {
                $this->json_reply('⚠️ Sesi login sudah habis. Silakan login ulang.');
                exit;
            }

            redirect('login');
        }
    }

    /**
     * Halaman /ai.
     * Overlay AI sebenarnya ada di layout/footer.php, view ini hanya mencegah error missing view.
     */
    public function index()
    {
        $data = ['title' => 'AI Assistant'];

        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('ai/assistant', $data);
        $this->load->view('layout/footer');
    }

    /**
     * Entry point utama chat.
     * Dipanggil oleh assets/js/ai.js ke route ai/chat.
     */
    public function chat()
    {
        require_post();
        try {
            $raw = trim((string)$this->input->post('message', true));

            // Jika kosong, tampilkan bantuan.
            if ($raw === '') {
                $this->json_reply($this->fallback());
                return;
            }

            // Normalisasi kalimat user lewat IntentEngine.
            $text = $this->intent_engine->normalize($raw);

            // Trigger khusus untuk membuka wizard input data di frontend.
            if ($text === 'input data') {
                $this->log_chat($raw, 'TRIGGER_INPUT_MODE', 1);
                $this->json_reply('[INPUT_MODE]');
                return;
            }

            // Jika sebelumnya AI meminta user memilih data, proses dulu pilihan itu.
            if ($this->handle_disambiguation($text)) {
                $this->log_chat($raw, 'HANDLE_DISAMBIGUATION', 1);
                return;
            }

            // Jika sebelumnya AI meminta konfirmasi ya/batal, proses dulu konfirmasi itu.
            if ($this->handle_confirmation($text)) {
                $this->log_chat($raw, 'HANDLE_CONFIRMATION', 1);
                return;
            }

            // Deteksi intent gratis/offline berbasis rule.
            $context = $this->intent_engine->context($text);
            $intent  = $this->intent_engine->detect($text);

            if ($intent === 'UNKNOWN') {
                $this->log_chat($raw, 'UNKNOWN', 0);
                $this->json_reply($this->fallback());
                return;
            }

            // Jalankan aksi sesuai intent.
            $reply = $this->execute_intent($intent, $text, $context);
            $this->log_chat($raw, $intent, 1);
            $this->json_reply($reply);
        } catch (Throwable $e) {
            log_message('error', 'AI ERROR: ' . $e->getMessage() . ' | FILE: ' . $e->getFile() . ' | LINE: ' . $e->getLine());
            $this->json_reply('⚠️ Terjadi kesalahan sistem pada AI Assistant. Cek log server.');
        }
    }

    /* =========================================================
     * BAGIAN 1 — HELPER UMUM
     * ========================================================= */

    /** Kirim balasan JSON standar ke frontend. */
    private function json_reply($reply, array $extra = [])
    {
        $payload = array_merge(['reply' => $reply], $extra);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    /** Cek kata/frasa memakai IntentEngine agar konsisten. */
    private function has($text, array $words)
    {
        return $this->intent_engine->has($text, $words);
    }

    /** Format rupiah singkat agar output rapi. */
    private function rupiah($value)
    {
        return 'Rp ' . number_format((int)$value, 0, ',', '.');
    }

    /** Nama bulan Indonesia untuk output laporan. */
    private function month_label()
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $bulan[(int)date('n')] . ' ' . date('Y');
    }


    /** Rentang tanggal bulan berjalan. */
    private function month_range($offset = 0)
    {
        $start = date('Y-m-01', strtotime($offset . ' month'));
        $end = date('Y-m-t', strtotime($offset . ' month'));
        return [$start, $end];
    }

    /** SUM pembayaran dari tabel order_payments jika tersedia, fallback ke orders.paid. */
    private function paid_sum_between($start, $end)
    {
        if ($this->db->table_exists('order_payments')) {
            $row = $this->db->query("SELECT COALESCE(SUM(amount),0) total FROM order_payments WHERE payment_date BETWEEN ? AND ?", [$start, $end])->row();
            return (int)($row->total ?? 0);
        }

        $row = $this->db->query("SELECT COALESCE(SUM(paid),0) total FROM orders WHERE DATE(created_at) BETWEEN ? AND ?", [$start, $end])->row();
        return (int)($row->total ?? 0);
    }

    private function percent_change($current, $previous)
    {
        $current = (float)$current;
        $previous = (float)$previous;
        if ($previous <= 0) return $current > 0 ? 100.0 : 0.0;
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function trend_label($percent)
    {
        if ($percent > 0) return 'naik +' . $percent . '%';
        if ($percent < 0) return 'turun ' . $percent . '%';
        return 'stabil 0%';
    }

    /** Ambil user yang sedang menjalankan AI untuk audit log. */
    private function current_user_label()
    {
        return $this->session->userdata('username')
            ?: $this->session->userdata('name')
            ?: ('user_id:' . $this->session->userdata('user_id'));
    }

    /** Simpan riwayat chat AI jika tabel ai_logs tersedia. */
    private function log_chat($prompt, $action, $confirmed = 0)
    {
        if (!$this->db->table_exists('ai_logs')) return;

        $this->db->insert('ai_logs', [
            'prompt'     => (string)$prompt,
            'action'     => (string)$action,
            'confirmed'  => (int)$confirmed,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /** Simpan audit untuk aksi yang mengubah data. */
    private function audit($actionType, $targetType, $targetId, $description)
    {
        if (!$this->db->table_exists('ai_audit_logs')) return;

        $this->db->insert('ai_audit_logs', [
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id'   => (int)$targetId,
            'description' => $description,
            'executed_by' => $this->current_user_label(),
            'created_at'  => date('Y-m-d H:i:s')
        ]);
    }

    /** Ambil angka uang dari teks, mendukung hasil normalisasi 2jt / 500rb. */
    private function extract_money($text)
    {
        if (preg_match('/(\d{4,})/', (string)$text, $m)) {
            return (int)$m[1];
        }
        if (preg_match('/(\d+)/', (string)$text, $m)) {
            return (int)$m[1];
        }
        return 0;
    }

    /** Bersihkan nama target dari perintah user. */
    private function clean_target_name($text, array $keywords)
    {
        $name = ' ' . strtolower($text) . ' ';
        foreach ($keywords as $word) {
            $name = preg_replace('/\b' . preg_quote($word, '/') . '\b/', ' ', $name);
        }
        $name = preg_replace('/\b\d+\b/', ' ', $name);
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /** Ambil satu baris setting harga pertama milik desain. */
    private function first_price_matrix($designId)
    {
        return $this->db->query("\n            SELECT pm.id, pm.base_price, pm.body_part_id, bp.name AS body_part\n            FROM price_matrix pm\n            JOIN body_parts bp ON bp.id = pm.body_part_id\n            WHERE pm.design_type_id = ?\n            ORDER BY pm.id ASC\n            LIMIT 1\n        ", [(int)$designId])->row();
    }

    /** Nomor order AI dibuat urut harian agar tidak bentrok dengan random number. */
    private function generate_order_code()
    {
        $prefix = 'ORD-' . date('Ymd') . '-';
        $last = $this->db
            ->like('order_code', $prefix, 'after')
            ->order_by('order_code', 'DESC')
            ->limit(1)
            ->get('orders')
            ->row();

        $next = 1;
        if ($last && preg_match('/-(\d{4})$/', $last->order_code, $m)) {
            $next = (int)$m[1] + 1;
        }

        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    /* =========================================================
     * BAGIAN 2 — ROUTER INTENT KE FUNGSI
     * ========================================================= */

    /** Jalankan fungsi sesuai intent yang ditemukan. */
    private function execute_intent($intent, $text, $ctx)
    {
        // Aksi berbahaya tidak langsung dieksekusi, wajib masuk sistem konfirmasi.
        if (in_array($intent, ['DELETE_ORDER', 'DELETE_CLIENT', 'MARK_ORDER_PAID'], true)) {
            return $this->ask_confirmation($intent, $text);
        }

        switch ($intent) {
            // Small talk
            case 'CHAT_HELLO': return $this->chat_hello();
            case 'CHAT_ACK': return $this->chat_ack();
            case 'CHAT_CANCEL': return $this->chat_cancel();
            case 'CHAT_ACTIVITY': return $this->chat_activity();
            case 'CHAT_BUSY': return $this->chat_busy();
            case 'CHAT_THANKS': return $this->chat_thanks();
            case 'CHAT_IDENTITY': return $this->chat_identity();
            case 'CHAT_USER_IDENTITY': return $this->chat_user_identity();
            case 'AI_CAPABILITY': return $this->ai_capability();

            // Dashboard
            case 'DASH_SUMMARY_MONTH': return $this->summary_month();
            case 'DASH_TOTAL_INCOME': return $this->total_income_month();
            case 'DASH_ORDER_TODAY': return $this->order_today();
            case 'DASH_UNPAID_LIST': return $this->list_unpaid_orders();
            case 'DASH_TOP_ORDER': return $this->top_order_month();
            case 'DASH_TOTAL_ORDER_ALL': return $this->total_order_all();
            case 'DASH_ORDER_PAID': return $this->order_paid_list();
            case 'DASH_ORDER_PROCESS': return $this->order_process();

            // Insight
            case 'INSIGHT_TOP_DESIGN': return $this->insight_top_design();
            case 'INSIGHT_TOP_BODY_PART': return $this->insight_top_body_part();
            case 'INSIGHT_TOP_CLIENT': return $this->insight_top_client();
            case 'INSIGHT_LATE_PAYMENT': return $this->insight_late_payment();
            case 'ANALYSIS_COMPARE_OMZET': return $this->analysis_compare_omzet();
            case 'ANALYSIS_WHY_DOWN': return $this->analysis_why_down();
            case 'ANALYSIS_PRIORITY_FOLLOWUP': return $this->analysis_priority_followup();
            case 'ANALYSIS_STUCK_ORDERS': return $this->analysis_stuck_orders();
            case 'ANALYSIS_PREDICT_INCOME': return $this->analysis_predict_income();
            case 'ANALYSIS_PRICE_RECOMMENDATION': return $this->analysis_price_recommendation();
            case 'ANALYSIS_DAILY_OWNER': return $this->analysis_daily_owner();
            case 'ANALYSIS_MONTHLY_REPORT': return $this->analysis_monthly_report();
            case 'ANALYSIS_UNDERPERFORMING_DESIGN': return $this->analysis_underperforming_design();

            // Draft pesan
            case 'DRAFT_FOLLOW_UP_PAYMENT': return $this->draft_follow_up();
            case 'DRAFT_DP_REMINDER': return $this->draft_dp();
            case 'DRAFT_ORDER_DONE': return $this->draft_done();
            case 'DRAFT_SOFT_BILLING': return $this->draft_billing();

            // Update data dengan konfirmasi khusus
            case 'UPDATE_PRICE': return $this->handle_update_price($text);
            case 'DISABLE_DESIGN': return $this->handle_disable_design($text);

            // Simulasi
            case 'SIM_PRICE_UP': return $this->simulate_price_up();
            case 'SIM_TARGET_CHECK': return $this->check_target($text);
        }

        return $this->fallback();
    }

    /* =========================================================
     * BAGIAN 3 — KONFIRMASI DAN PEMILIHAN DATA
     * ========================================================= */

    /**
     * Persiapan konfirmasi untuk aksi berbahaya.
     * Data disimpan di session ai_pending agar user bisa menjawab ya/batal.
     */
    private function ask_confirmation($intent, $text)
    {
        if ($intent === 'MARK_ORDER_PAID') {
            return $this->prepare_mark_paid($text);
        }

        if ($intent === 'DELETE_CLIENT') {
            return $this->prepare_delete_client($text);
        }

        return $this->prepare_delete_order($text);
    }

    /** Siapkan konfirmasi tandai order lunas. */
    private function prepare_mark_paid($text)
    {
        $name = $this->clean_target_name($text, ['tandai', 'mark', 'set', 'jadikan', 'order', 'invoice', 'lunas']);

        if (strlen($name) < 2) {
            return "⚠️ Sebutkan nama klien atau kode order.\nContoh: tandai lunas rifky";
        }

        $rows = $this->db->query("\n            SELECT o.id, o.order_code, o.total, o.paid, o.client_id, c.name\n            FROM orders o\n            JOIN clients c ON c.id = o.client_id\n            WHERE (LOWER(c.name) LIKE ? OR LOWER(o.order_code) LIKE ?)\n              AND o.paid < o.total\n            ORDER BY o.created_at DESC\n            LIMIT 10\n        ", ['%' . $name . '%', '%' . $name . '%'])->result();

        if (!$rows) {
            return "❌ Tidak ditemukan order belum lunas untuk \"{$name}\".";
        }

        if (count($rows) > 1) {
            $this->session->set_userdata('ai_pending', [
                'type' => 'DISAMBIGUATION',
                'intent' => 'MARK_ORDER_PAID',
                'candidates' => $rows
            ]);

            return $this->format_candidates($rows, "Aku menemukan beberapa order belum lunas untuk \"{$name}\":", 'Ketik angka order yang ingin ditandai lunas.');
        }

        return $this->set_mark_paid_confirmation($rows[0]);
    }

    /** Simpan session konfirmasi tandai lunas. */
    private function set_mark_paid_confirmation($order)
    {
        $this->session->set_userdata('ai_pending', [
            'type'       => 'CONFIRMATION',
            'intent'     => 'MARK_ORDER_PAID',
            'order_id'   => (int)$order->id,
            'order_code' => $order->order_code,
            'total'      => (int)$order->total
        ]);

        return "⚠️ Konfirmasi tandai order {$order->order_code} sebagai LUNAS?\n\nKetik: ya / batal";
    }

    /** Siapkan konfirmasi hapus satu order. */
    private function prepare_delete_order($text)
    {
        $name = $this->clean_target_name($text, ['hapus', 'delete', 'order', 'invoice', 'data']);

        if (strlen($name) < 2) {
            return "⚠️ Sebutkan nama klien atau kode order yang ingin dihapus.\nContoh: hapus order rifki";
        }

        $rows = $this->db->query("\n            SELECT o.id, o.order_code, o.total, o.client_id, c.name\n            FROM orders o\n            JOIN clients c ON c.id = o.client_id\n            WHERE LOWER(c.name) LIKE ? OR LOWER(o.order_code) LIKE ?\n            ORDER BY o.created_at DESC\n            LIMIT 10\n        ", ['%' . $name . '%', '%' . $name . '%'])->result();

        if (!$rows) {
            return "❌ Tidak ditemukan order untuk \"{$name}\".";
        }

        if (count($rows) > 1) {
            $this->session->set_userdata('ai_pending', [
                'type' => 'DISAMBIGUATION',
                'intent' => 'DELETE_ORDER',
                'candidates' => $rows
            ]);

            return $this->format_candidates($rows, "Aku menemukan beberapa order untuk \"{$name}\":", 'Ketik angka order yang ingin dihapus.');
        }

        return $this->set_delete_order_confirmation($rows[0]);
    }

    /** Simpan session konfirmasi hapus order. */
    private function set_delete_order_confirmation($order)
    {
        $this->session->set_userdata('ai_pending', [
            'type'       => 'CONFIRMATION',
            'intent'     => 'DELETE_ORDER',
            'order_id'   => (int)$order->id,
            'order_code' => $order->order_code,
            'client_id'  => (int)$order->client_id,
            'client_name'=> $order->name
        ]);

        return "⚠️ Konfirmasi hapus order {$order->order_code} milik {$order->name}?\n\nKetik: ya / batal";
    }

    /** Siapkan konfirmasi hapus client beserta semua ordernya. */
    private function prepare_delete_client($text)
    {
        $name = $this->clean_target_name($text, ['hapus', 'delete', 'client', 'klien', 'data']);

        if (strlen($name) < 2) {
            return "⚠️ Sebutkan nama client yang ingin dihapus.\nContoh: hapus client rifki";
        }

        $rows = $this->db->query("\n            SELECT id AS client_id, name\n            FROM clients\n            WHERE LOWER(name) LIKE ?\n            ORDER BY name ASC\n            LIMIT 10\n        ", ['%' . $name . '%'])->result();

        if (!$rows) {
            return "❌ Client \"{$name}\" tidak ditemukan.";
        }

        if (count($rows) > 1) {
            $this->session->set_userdata('ai_pending', [
                'type' => 'DISAMBIGUATION',
                'intent' => 'DELETE_CLIENT',
                'candidates' => $rows
            ]);

            return $this->format_candidates($rows, "Aku menemukan beberapa client untuk \"{$name}\":", 'Ketik angka client yang ingin dihapus.');
        }

        return $this->set_delete_client_confirmation($rows[0]);
    }

    /** Simpan session konfirmasi hapus client. */
    private function set_delete_client_confirmation($client)
    {
        $this->session->set_userdata('ai_pending', [
            'type'        => 'CONFIRMATION',
            'intent'      => 'DELETE_CLIENT',
            'client_id'   => (int)$client->client_id,
            'client_name' => $client->name
        ]);

        return "⚠️ Konfirmasi hapus client {$client->name} beserta semua ordernya?\n\nKetik: ya / batal";
    }

    /** Format daftar pilihan ketika hasil pencarian lebih dari satu. */
    private function format_candidates($rows, $title, $footer)
    {
        $out = $title . "\n\n";
        foreach ($rows as $i => $r) {
            $code = isset($r->order_code) ? " (Order {$r->order_code})" : '';
            $out .= ($i + 1) . ". {$r->name}{$code}\n";
        }
        return trim($out) . "\n\n" . $footer;
    }

    /** Proses pilihan angka/nama saat AI menampilkan beberapa kandidat. */
    private function handle_disambiguation($text)
    {
        $pending = $this->session->userdata('ai_pending');
        if (!$pending || ($pending['type'] ?? '') !== 'DISAMBIGUATION') {
            return false;
        }

        $candidates = $pending['candidates'];
        $intent = $pending['intent'];
        $selected = null;

        // User bisa memilih dengan angka.
        if (is_numeric($text)) {
            $idx = (int)$text - 1;
            if (isset($candidates[$idx])) {
                $selected = $candidates[$idx];
            }
        }

        // User juga bisa memilih dengan nama.
        if (!$selected) {
            foreach ($candidates as $candidate) {
                if (isset($candidate->name) && strpos(strtolower($candidate->name), strtolower($text)) !== false) {
                    $selected = $candidate;
                    break;
                }
            }
        }

        if (!$selected) {
            $this->json_reply('❌ Pilihan tidak valid. Ketik angka sesuai daftar atau nama yang muncul.');
            return true;
        }

        // Penting: setiap intent punya format confirmation berbeda.
        if ($intent === 'MARK_ORDER_PAID') {
            $this->json_reply($this->set_mark_paid_confirmation($selected));
            return true;
        }

        if ($intent === 'DELETE_CLIENT') {
            $this->json_reply($this->set_delete_client_confirmation($selected));
            return true;
        }

        $this->json_reply($this->set_delete_order_confirmation($selected));
        return true;
    }

    /** Proses jawaban ya/batal dari user. */
    private function handle_confirmation($text)
    {
        $pending = $this->session->userdata('ai_pending');
        if (!$pending) return false;

        // Konfirmasi harga baru: user diminta memasukkan nominal.
        if (($pending['type'] ?? '') === 'PRICE_SELECT') {
            return $this->handle_price_select($text, $pending);
        }

        // Konfirmasi final update harga.
        if (($pending['type'] ?? '') === 'PRICE_CONFIRM') {
            return $this->handle_price_confirm($text, $pending);
        }

        // Konfirmasi nonaktifkan desain.
        if (($pending['type'] ?? '') === 'DESIGN_DISABLE_CONFIRM') {
            return $this->handle_design_disable_confirm($text, $pending);
        }

        // Semua konfirmasi umum mendukung batal.
        if ($this->has($text, ['batal', 'cancel', 'stop', 'jangan', 'tidak', 'nggak', 'ga', 'gak'])) {
            $this->session->unset_userdata('ai_pending');
            $this->json_reply('❌ Dibatalkan. Tidak ada data yang berubah.');
            return true;
        }

        if (!$this->has($text, ['ya', 'iya', 'y', 'yes', 'ok', 'oke', 'sip', 'siap', 'gas', 'lanjut', 'boleh', 'setuju', 'mantap'])) {
            $this->json_reply('⚠️ Jawab ya / batal.');
            return true;
        }

        switch ($pending['intent'] ?? '') {
            case 'DELETE_ORDER':
                $this->execute_delete_order($pending);
                return true;

            case 'DELETE_CLIENT':
                $this->execute_delete_client($pending);
                return true;

            case 'MARK_ORDER_PAID':
                $this->execute_mark_paid($pending);
                return true;
        }

        $this->session->unset_userdata('ai_pending');
        $this->json_reply('❌ Aksi tidak dikenali.');
        return true;
    }

    /** Eksekusi hapus satu order. */
    private function execute_delete_order($pending)
    {
        $orderId = (int)$pending['order_id'];
        $code = $pending['order_code'];

        $ok = $this->orders->delete_with_files($orderId);
        $this->session->unset_userdata('ai_pending');

        if (!$ok) {
            $this->json_reply('❌ Gagal menghapus order.');
            return;
        }

        $this->audit('DELETE_ORDER', 'orders', $orderId, "Order {$code} dihapus lewat AI Assistant");
        $this->json_reply("🗑️ Order {$code} berhasil dihapus.");
    }

    /** Eksekusi hapus client beserta semua ordernya. */
    private function execute_delete_client($pending)
    {
        $clientId = (int)$pending['client_id'];
        $clientName = $pending['client_name'];

        $orders = $this->db->get_where('orders', ['client_id' => $clientId])->result();
        $ok = true;
        foreach ($orders as $order) {
            if (!$this->orders->delete_with_files($order->id)) {
                $ok = false;
                break;
            }
        }

        if ($ok) {
            $ok = $this->db->where('id', $clientId)->delete('clients');
        }

        $this->session->unset_userdata('ai_pending');

        if (!$ok) {
            $this->json_reply('❌ Gagal menghapus client.');
            return;
        }

        $this->audit('DELETE_CLIENT', 'clients', $clientId, "Client {$clientName} dan semua ordernya dihapus lewat AI Assistant");
        $this->json_reply("🗑️ Client {$clientName} beserta semua ordernya berhasil dihapus.");
    }

    /** Eksekusi tandai order lunas. */
    private function execute_mark_paid($pending)
    {
        $orderId = (int)$pending['order_id'];
        $code = $pending['order_code'];
        $total = (int)$pending['total'];

        $order = $this->orders->find($orderId);
        $remaining = $order ? max(0, (int)$order->total - (int)$order->paid) : 0;

        if ($remaining > 0 && $this->db->table_exists('order_payments')) {
            $this->orders->add_payment($orderId, $remaining, 'Pelunasan melalui AI Assistant', date('Y-m-d'), 'AI');
        } else {
            $this->db->where('id', $orderId)->update('orders', ['paid' => $total, 'status' => 'SELESAI']);
        }

        $this->session->unset_userdata('ai_pending');
        $this->audit('MARK_ORDER_PAID', 'orders', $orderId, "Order {$code} ditandai lunas lewat AI Assistant");
        $this->json_reply("✅ Order {$code} berhasil ditandai LUNAS. Riwayat pembayaran pelunasan juga dicatat.");
    }

    /* =========================================================
     * BAGIAN 4 — DASHBOARD DAN LAPORAN
     * ========================================================= */

    private function summary_month()
    {
        [$start, $end] = $this->month_range(0);
        $r = $this->db->query("
            SELECT COUNT(*) total_order, COALESCE(SUM(total),0) total
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
        ", [$start, $end])->row();

        $target = 8000000;
        $total = (int)$r->total;
        $paid = $this->paid_sum_between($start, $end);
        $unpaid = max(0, $total - $paid);
        $progress = $target > 0 ? min(100, round(($paid / $target) * 100)) : 0;

        return "📅 Ringkasan {$this->month_label()}\n\n"
            . "📦 Total Order: " . (int)$r->total_order . "\n"
            . "💰 Nilai Invoice: " . $this->rupiah($total) . "\n"
            . "✅ Pembayaran Masuk: " . $this->rupiah($paid) . "\n"
            . "⏳ Belum Dibayar: " . $this->rupiah($unpaid) . "\n"
            . "📈 Progress Target: {$progress}%";
    }

    private function total_income_month()
    {
        [$start, $end] = $this->month_range(0);
        $total = $this->paid_sum_between($start, $end);
        return "💰 Total Pendapatan {$this->month_label()}\n\nTotal pemasukan bulan ini sebesar:\n" . $this->rupiah($total);
    }

    private function order_today()
    {
        $r = $this->db->query("SELECT COUNT(*) total FROM orders WHERE DATE(created_at)=CURDATE()")->row();
        return ((int)$r->total > 0)
            ? "📅 Transaksi Hari Ini\n\nJumlah order yang tercatat hari ini: {$r->total} transaksi"
            : "📅 Transaksi Hari Ini\n\nTidak terdapat transaksi yang tercatat hari ini.";
    }

    private function list_unpaid_orders()
    {
        $rows = $this->db->query("\n            SELECT o.order_code, o.total, o.paid, (o.total-o.paid) AS sisa, c.name\n            FROM orders o\n            JOIN clients c ON c.id=o.client_id\n            WHERE o.paid < o.total\n            ORDER BY o.created_at DESC\n        ")->result();

        if (!$rows) {
            return "📌 Daftar Order Belum Lunas\n\nSemua order telah lunas.";
        }

        $out = "📌 Daftar Order Belum Lunas\n\n";
        foreach ($rows as $i => $r) {
            $out .= ($i + 1) . ". {$r->order_code}\n"
                . "   Klien  : {$r->name}\n"
                . "   Total  : " . $this->rupiah($r->total) . "\n"
                . "   Dibayar: " . $this->rupiah($r->paid) . "\n"
                . "   Sisa   : " . $this->rupiah($r->sisa) . "\n\n";
        }

        return trim($out);
    }

    private function top_order_month()
    {
        $row = $this->db->query("\n            SELECT o.order_code, o.total, o.paid, c.name\n            FROM orders o\n            JOIN clients c ON c.id=o.client_id\n            WHERE MONTH(o.created_at)=MONTH(NOW()) AND YEAR(o.created_at)=YEAR(NOW())\n            ORDER BY o.total DESC\n            LIMIT 1\n        ")->row();

        if (!$row) {
            return "🏆 Order dengan Nilai Tertinggi – {$this->month_label()}\n\nBelum terdapat transaksi pada periode ini.";
        }

        $status = ((int)$row->paid >= (int)$row->total) ? 'Lunas' : 'Belum Lunas';
        return "🏆 Order dengan Nilai Tertinggi – {$this->month_label()}\n\n"
            . "Kode Order : {$row->order_code}\n"
            . "Klien      : {$row->name}\n"
            . "Total Nilai: " . $this->rupiah($row->total) . "\n"
            . "Status     : {$status}";
    }

    private function total_order_all()
    {
        $total = $this->db->count_all('orders');
        return $total > 0
            ? "📦 Total Seluruh Order\n\nJumlah total order yang tercatat:\n" . number_format($total, 0, ',', '.') . " transaksi"
            : "📦 Total Seluruh Order\n\nBelum ada order yang tercatat di sistem.";
    }

    private function order_paid_list()
    {
        $rows = $this->db->query("\n            SELECT o.order_code, o.total, c.name\n            FROM orders o\n            JOIN clients c ON c.id=o.client_id\n            WHERE o.paid >= o.total AND o.total > 0\n            ORDER BY o.created_at DESC\n        ")->result();

        if (!$rows) return "✅ Daftar Order Lunas\n\nBelum terdapat order yang berstatus lunas.";

        $reply = "✅ Daftar Order Lunas\n\n";
        foreach ($rows as $i => $r) {
            $reply .= ($i + 1) . ". {$r->order_code}\n"
                . "   Klien  : {$r->name}\n"
                . "   Total  : " . $this->rupiah($r->total) . "\n"
                . "   Status : Lunas\n\n";
        }
        return trim($reply);
    }

    private function order_process()
    {
        $r = $this->db->query("SELECT COUNT(*) total FROM orders WHERE status='PROSES'")->row();
        return "📦 Order Proses\n\nAda " . (int)$r->total . " order yang masih dalam proses.";
    }

    /* =========================================================
     * BAGIAN 5 — INSIGHT DATA
     * ========================================================= */

    private function insight_top_design()
    {
        $rows = $this->db->query("\n            SELECT dt.name, COUNT(*) total\n            FROM order_items oi\n            JOIN orders o ON o.id=oi.order_id\n            JOIN design_types dt ON dt.id=oi.design_type_id\n            WHERE MONTH(o.created_at)=MONTH(NOW()) AND YEAR(o.created_at)=YEAR(NOW())\n            GROUP BY oi.design_type_id\n            ORDER BY total DESC\n        ")->result();

        if (!$rows) return "🎨 Desain Terlaris – {$this->month_label()}\n\nBelum terdapat data pemesanan pada periode ini.";

        $top = (int)$rows[0]->total;
        $topRows = array_values(array_filter($rows, function ($r) use ($top) { return (int)$r->total === $top; }));

        $out = "🎨 Desain Terlaris – {$this->month_label()}\n\n";
        foreach ($topRows as $i => $r) {
            $out .= ($i + 1) . ". {$r->name} – {$r->total} order\n";
        }
        return trim($out);
    }

    private function insight_top_body_part()
    {
        $rows = $this->db->query("\n            SELECT bp.name, COUNT(*) total\n            FROM order_items oi\n            JOIN body_parts bp ON bp.id=oi.body_part_id\n            GROUP BY oi.body_part_id\n            ORDER BY total DESC\n        ")->result();

        if (!$rows) return "Belum ada data body part untuk dianalisis.";

        $top = (int)$rows[0]->total;
        $topRows = array_values(array_filter($rows, function ($r) use ($top) { return (int)$r->total === $top; }));

        $out = "🧩 Body part paling sering dipesan:\n\n";
        foreach ($topRows as $i => $r) {
            $out .= ($i + 1) . ". {$r->name} – {$r->total} kali\n";
        }
        return trim($out);
    }

    private function insight_top_client()
    {
        $rows = $this->db->query("\n            SELECT c.name, COUNT(*) total\n            FROM orders o\n            JOIN clients c ON c.id=o.client_id\n            WHERE MONTH(o.created_at)=MONTH(NOW()) AND YEAR(o.created_at)=YEAR(NOW())\n            GROUP BY o.client_id\n            ORDER BY total DESC\n        ")->result();

        if (!$rows) return "👤 Klien Paling Aktif – {$this->month_label()}\n\nBelum terdapat aktivitas pemesanan pada periode ini.";

        $top = (int)$rows[0]->total;
        $topRows = array_values(array_filter($rows, function ($r) use ($top) { return (int)$r->total === $top; }));

        $out = "👤 Klien Paling Aktif – {$this->month_label()}\n\n";
        foreach ($topRows as $i => $r) {
            $out .= ($i + 1) . ". {$r->name} – {$r->total} transaksi\n";
        }
        return trim($out);
    }

    private function insight_late_payment()
    {
        $rows = $this->db->query("\n            SELECT o.order_code, c.name, (o.total-o.paid) AS sisa, DATEDIFF(NOW(), o.created_at) AS days_late\n            FROM orders o\n            JOIN clients c ON c.id=o.client_id\n            WHERE o.paid < o.total AND DATEDIFF(NOW(), o.created_at) > 0\n            ORDER BY days_late DESC\n            LIMIT 20\n        ")->result();

        if (!$rows) return "⏳ Pembayaran Tertunda\n\nTidak terdapat pembayaran yang melewati batas waktu.";

        $reply = "⏳ Pembayaran Tertunda\n\n";
        foreach ($rows as $i => $r) {
            $reply .= ($i + 1) . ". {$r->order_code}\n"
                . "   Klien      : {$r->name}\n"
                . "   Sisa Bayar : " . $this->rupiah($r->sisa) . "\n"
                . "   Tertunda   : {$r->days_late} hari\n\n";
        }
        return trim($reply);
    }

    /* =========================================================
     * BAGIAN 6 — DRAFT PESAN UNTUK KLIEN
     * ========================================================= */

    private function draft_follow_up()
    {
        return '[FOLLOWUP_JSON]' . json_encode([
            'v1' => "Halo Kak, semoga sehat selalu.\n\nKami ingin mengingatkan bahwa masih terdapat sisa pembayaran untuk order sebelumnya. Mohon kesediaannya untuk melakukan pelunasan sesuai kesepakatan.\n\nJika pembayaran sudah dilakukan, mohon konfirmasi agar dapat kami proses lebih lanjut.\n\nTerima kasih atas kerja samanya 🙏",
            'v2' => "Halo Kak,\n\nKami ingin menginformasikan bahwa masih ada sisa pembayaran untuk order yang sedang berjalan. Mohon kesediaannya untuk menyelesaikan pembayaran agar proses dapat dilanjutkan tanpa kendala.\n\nTerima kasih atas perhatiannya."
        ]);
    }

    private function draft_dp()
    {
        return '[DP_JSON]' . json_encode([
            'v1' => "Halo Kak,\n\nSebelum proses pengerjaan dilanjutkan, kami ingin mengingatkan terkait pembayaran Down Payment (DP) sesuai kesepakatan awal.\n\nMohon konfirmasinya apabila pembayaran sudah dilakukan agar pengerjaan dapat segera diproses.\n\nTerima kasih 🙏",
            'v2' => "Halo Kak,\n\nKami mengingatkan kembali terkait pembayaran DP untuk order yang sedang diajukan. Pengerjaan akan diproses setelah DP diterima.\n\nTerima kasih atas kerja samanya."
        ]);
    }

    private function draft_done()
    {
        return '[DONE_JSON]' . json_encode([
            'v1' => "Halo Kak,\n\nKami informasikan bahwa order yang dikerjakan telah selesai. Silakan dicek terlebih dahulu. Apabila ada revisi atau penyesuaian, mohon disampaikan agar dapat segera kami tindak lanjuti.\n\nTerima kasih 🙏",
            'v2' => "Halo Kak, order sudah selesai. Silakan dicek terlebih dahulu. Kalau ada revisi, bisa langsung dikabari ya. Terima kasih 🙏"
        ]);
    }

    private function draft_billing()
    {
        return '[BILLING_JSON]' . json_encode([
            'v1' => "Halo Kak, semoga sehat selalu.\n\nKami ingin menyampaikan pengingat pembayaran untuk order yang masih memiliki sisa tagihan. Mohon kesediaannya untuk melakukan pelunasan sesuai kesepakatan.\n\nTerima kasih atas kerja samanya 🙏",
            'v2' => "Halo Kak,\n\nBerdasarkan catatan kami, masih terdapat sisa tagihan pada order sebelumnya. Mohon dapat dilakukan pelunasan agar administrasi order dapat kami selesaikan.\n\nTerima kasih."
        ]);
    }

    /* =========================================================
     * BAGIAN 7 — UPDATE HARGA DAN NONAKTIFKAN DESAIN
     * ========================================================= */

    private function handle_update_price($text)
    {
        $newPrice = $this->extract_money($text);
        $name = $this->clean_target_name($text, ['ubah', 'update', 'ganti', 'harga', 'jadi', 'menjadi']);

        // Hilangkan angka harga dari nama desain.
        $name = trim(preg_replace('/\b\d+\b/', ' ', $name));
        $name = trim(preg_replace('/\s+/', ' ', $name));

        if (strlen($name) < 2) {
            return "⚠️ Sebutkan nama desain.\nContoh: ubah harga karikatur 100000";
        }

        $design = $this->db->query("SELECT id, name FROM design_types WHERE LOWER(name) LIKE ? LIMIT 1", ['%' . $name . '%'])->row();
        if (!$design) return "❌ Desain \"{$name}\" tidak ditemukan.";

        $price = $this->first_price_matrix($design->id);
        if (!$price) return "❌ Harga untuk desain {$design->name} belum tersedia di price matrix.";

        if ($newPrice > 0) {
            $this->session->set_userdata('ai_pending', [
                'type' => 'PRICE_CONFIRM',
                'design_id' => (int)$design->id,
                'design_name' => $design->name,
                'price_matrix_id' => (int)$price->id,
                'old_price' => (int)$price->base_price,
                'new_price' => $newPrice
            ]);

            return "⚠️ Konfirmasi ubah harga {$design->name}\n"
                . "Bagian: {$price->body_part}\n"
                . "Dari " . $this->rupiah($price->base_price) . "\n"
                . "Menjadi " . $this->rupiah($newPrice) . "\n\n"
                . "Ketik: ya / batal";
        }

        $this->session->set_userdata('ai_pending', [
            'type' => 'PRICE_SELECT',
            'design_id' => (int)$design->id,
            'design_name' => $design->name,
            'price_matrix_id' => (int)$price->id,
            'old_price' => (int)$price->base_price
        ]);

        return "Jenis  : {$design->name}\n"
            . "Bagian : {$price->body_part}\n"
            . "Harga sekarang : " . $this->rupiah($price->base_price) . "\n\n"
            . "Kamu mau ubah harga {$design->name} jadi berapa?";
    }

    private function handle_price_select($text, $pending)
    {
        if ($this->has($text, ['batal', 'cancel', 'stop', 'jangan', 'tidak', 'nggak', 'ga', 'gak'])) {
            $this->session->unset_userdata('ai_pending');
            $this->json_reply('❌ Dibatalkan. Harga tidak berubah.');
            return true;
        }

        $newPrice = $this->extract_money($text);
        if ($newPrice <= 0) {
            $this->json_reply('⚠️ Masukkan angka harga baru. Contoh: 150000');
            return true;
        }

        $this->session->set_userdata('ai_pending', [
            'type' => 'PRICE_CONFIRM',
            'design_id' => $pending['design_id'],
            'design_name' => $pending['design_name'],
            'price_matrix_id' => $pending['price_matrix_id'],
            'old_price' => $pending['old_price'],
            'new_price' => $newPrice
        ]);

        $this->json_reply("⚠️ Konfirmasi ubah harga {$pending['design_name']}\n"
            . "Dari " . $this->rupiah($pending['old_price']) . "\n"
            . "Menjadi " . $this->rupiah($newPrice) . "\n\n"
            . "Ketik: ya / batal");
        return true;
    }

    private function handle_price_confirm($text, $pending)
    {
        if ($this->has($text, ['batal', 'cancel', 'stop', 'jangan', 'tidak', 'nggak', 'ga', 'gak'])) {
            $this->session->unset_userdata('ai_pending');
            $this->json_reply('❌ Dibatalkan. Harga tidak berubah.');
            return true;
        }

        if (!$this->has($text, ['ya', 'iya', 'y', 'yes', 'ok', 'oke', 'sip', 'siap', 'gas', 'lanjut', 'boleh', 'setuju', 'mantap'])) {
            $this->json_reply('⚠️ Jawab ya / batal.');
            return true;
        }

        $this->db->where('id', (int)$pending['price_matrix_id'])->update('price_matrix', [
            'base_price' => (int)$pending['new_price']
        ]);

        $this->session->unset_userdata('ai_pending');
        $this->audit('UPDATE_PRICE', 'price_matrix', $pending['price_matrix_id'], "Harga {$pending['design_name']} diubah dari {$pending['old_price']} menjadi {$pending['new_price']} lewat AI Assistant");
        $this->json_reply("✅ Harga {$pending['design_name']} berhasil diperbarui menjadi " . $this->rupiah($pending['new_price']) . ".");
        return true;
    }

    private function handle_disable_design($text)
    {
        $name = $this->clean_target_name($text, ['nonaktifkan', 'disable', 'desain', 'design']);
        if (strlen($name) < 2) return "⚠️ Sebutkan nama desain.\nContoh: nonaktifkan desain karikatur";

        $design = $this->db->query("SELECT id, name, is_active FROM design_types WHERE LOWER(name) LIKE ? LIMIT 1", ['%' . $name . '%'])->row();
        if (!$design) return "❌ Desain \"{$name}\" tidak ditemukan.";
        if ((int)$design->is_active === 0) return "⚠️ Desain {$design->name} sudah dalam status NONAKTIF.";

        $this->session->set_userdata('ai_pending', [
            'type' => 'DESIGN_DISABLE_CONFIRM',
            'design_id' => (int)$design->id,
            'design_name' => $design->name
        ]);

        return "Jenis desain : {$design->name}\nStatus saat ini : Aktif\n\n⚠️ Yakin ingin menonaktifkan desain ini?\nKetik: ya / batal";
    }

    private function handle_design_disable_confirm($text, $pending)
    {
        if ($this->has($text, ['batal', 'cancel', 'stop', 'jangan', 'tidak', 'nggak', 'ga', 'gak'])) {
            $this->session->unset_userdata('ai_pending');
            $this->json_reply('❌ Dibatalkan. Desain tetap aktif.');
            return true;
        }

        if (!$this->has($text, ['ya', 'iya', 'y', 'yes', 'ok', 'oke', 'sip', 'siap', 'gas', 'lanjut', 'boleh', 'setuju', 'mantap'])) {
            $this->json_reply('⚠️ Jawab ya / batal.');
            return true;
        }

        $this->db->where('id', (int)$pending['design_id'])->update('design_types', ['is_active' => 0]);
        $this->session->unset_userdata('ai_pending');
        $this->audit('DISABLE_DESIGN', 'design_types', $pending['design_id'], "Desain {$pending['design_name']} dinonaktifkan lewat AI Assistant");
        $this->json_reply("✅ Desain {$pending['design_name']} berhasil dinonaktifkan.");
        return true;
    }


    /* =========================================================
     * BAGIAN 7B — ANALISIS BISNIS EXECUTIVE
     * ========================================================= */

    private function analysis_compare_omzet()
    {
        [$cs, $ce] = $this->month_range(0);
        [$ps, $pe] = $this->month_range(-1);
        $current = $this->paid_sum_between($cs, $ce);
        $previous = $this->paid_sum_between($ps, $pe);
        $pct = $this->percent_change($current, $previous);

        $ordersNow = (int)$this->db->query("SELECT COUNT(*) total FROM orders WHERE DATE(created_at) BETWEEN ? AND ?", [$cs, $ce])->row()->total;
        $ordersPrev = (int)$this->db->query("SELECT COUNT(*) total FROM orders WHERE DATE(created_at) BETWEEN ? AND ?", [$ps, $pe])->row()->total;

        return "📊 Perbandingan Omzet\n\n"
            . "Bulan ini : " . $this->rupiah($current) . " ({$ordersNow} order)\n"
            . "Bulan lalu: " . $this->rupiah($previous) . " ({$ordersPrev} order)\n"
            . "Tren      : " . $this->trend_label($pct) . "\n\n"
            . ($pct >= 0 ? "Insight: performa pemasukan sedang membaik." : "Insight: pemasukan menurun, cek order belum lunas dan jumlah order masuk.");
    }

    private function analysis_why_down()
    {
        [$cs, $ce] = $this->month_range(0);
        [$ps, $pe] = $this->month_range(-1);
        $current = $this->paid_sum_between($cs, $ce);
        $previous = $this->paid_sum_between($ps, $pe);
        $pct = $this->percent_change($current, $previous);
        $ordersNow = (int)$this->db->query("SELECT COUNT(*) total FROM orders WHERE DATE(created_at) BETWEEN ? AND ?", [$cs, $ce])->row()->total;
        $ordersPrev = (int)$this->db->query("SELECT COUNT(*) total FROM orders WHERE DATE(created_at) BETWEEN ? AND ?", [$ps, $pe])->row()->total;
        $unpaid = $this->db->query("SELECT COUNT(*) c, COALESCE(SUM(total-paid),0) sisa FROM orders WHERE paid < total")->row();

        $out = "🧠 Analisis Penyebab Omzet\n\n";
        $out .= "Tren pemasukan: " . $this->trend_label($pct) . "\n";
        $out .= "Order bulan ini: {$ordersNow}, bulan lalu: {$ordersPrev}\n";
        $out .= "Piutang aktif  : " . $this->rupiah($unpaid->sisa) . " ({$unpaid->c} order)\n\n";
        $out .= "Kemungkinan penyebab:\n";
        if ($ordersNow < $ordersPrev) $out .= "1. Jumlah order masuk turun dibanding bulan lalu.\n";
        if ((int)$unpaid->sisa > 0) $out .= "2. Masih ada pembayaran belum lunas yang menahan cashflow.\n";
        if ($current < $previous) $out .= "3. Nilai pembayaran masuk bulan ini lebih kecil dari bulan sebelumnya.\n";
        if ($current >= $previous) $out .= "1. Omzet tidak sedang turun; pemasukan bulan ini masih aman dibanding bulan lalu.\n";
        $out .= "\nSaran: follow-up invoice dengan sisa terbesar dan dorong desain yang paling laris.";
        return $out;
    }

    private function analysis_priority_followup()
    {
        $rows = $this->db->query("\n            SELECT o.id, o.order_code, o.total, o.paid, (o.total-o.paid) sisa, o.deadline, c.name\n            FROM orders o JOIN clients c ON c.id=o.client_id\n            WHERE o.paid < o.total\n            ORDER BY sisa DESC, o.deadline ASC, o.created_at ASC\n            LIMIT 5\n        ")->result();
        if (!$rows) return "✅ Tidak ada prioritas follow-up. Semua order sudah lunas.";

        $out = "📌 Prioritas Follow-up Hari Ini\n\n";
        foreach ($rows as $i => $r) {
            $out .= ($i+1) . ". {$r->name} ({$r->order_code})\n"
                . "   Sisa: " . $this->rupiah($r->sisa) . " dari " . $this->rupiah($r->total) . "\n"
                . "   Deadline: " . ($r->deadline ?: '-') . "\n";
        }
        $out .= "\nSaran: mulai dari nominal sisa terbesar agar cashflow cepat membaik.";
        return trim($out);
    }

    private function analysis_stuck_orders()
    {
        $rows = $this->db->query("\n            SELECT o.order_code, o.status, o.deadline, DATEDIFF(CURDATE(), DATE(o.created_at)) umur, c.name\n            FROM orders o JOIN clients c ON c.id=o.client_id\n            WHERE o.status IN ('MASUK','PROSES','REVISI')\n              AND (DATEDIFF(CURDATE(), DATE(o.created_at)) >= 3 OR (o.deadline IS NOT NULL AND o.deadline < CURDATE()))\n            ORDER BY umur DESC, o.deadline ASC\n            LIMIT 10\n        ")->result();
        if (!$rows) return "✅ Tidak ada order macet yang terdeteksi.";
        $out = "🚧 Order Berpotensi Macet\n\n";
        foreach ($rows as $i => $r) {
            $out .= ($i+1) . ". {$r->order_code} - {$r->name}\n"
                . "   Status: {$r->status} | Umur: {$r->umur} hari | Deadline: " . ($r->deadline ?: '-') . "\n";
        }
        $out .= "\nSaran: update status, hubungi klien untuk revisi, atau prioritaskan order yang deadline-nya lewat.";
        return trim($out);
    }

    private function analysis_predict_income()
    {
        [$start, $end] = $this->month_range(0);
        $paid = $this->paid_sum_between($start, $end);
        $day = max(1, (int)date('j'));
        $days = (int)date('t');
        $linear = (int)round(($paid / $day) * $days);
        $unpaidRow = $this->db->query("SELECT COALESCE(SUM(total-paid),0) sisa FROM orders WHERE paid < total AND DATE(created_at) BETWEEN ? AND ?", [$start, $end])->row();
        $optimistic = $linear + (int)($unpaidRow->sisa ?? 0);
        return "🔮 Prediksi Income Bulan Ini\n\n"
            . "Realisasi saat ini : " . $this->rupiah($paid) . "\n"
            . "Prediksi konservatif: " . $this->rupiah($linear) . "\n"
            . "Prediksi optimistis : " . $this->rupiah($optimistic) . "\n\n"
            . "Catatan: prediksi optimistis menambahkan potensi pelunasan invoice bulan ini.";
    }

    private function analysis_price_recommendation()
    {
        $rows = $this->db->query("\n            SELECT dt.name, COUNT(*) total_order, AVG(oi.price) avg_price\n            FROM order_items oi JOIN design_types dt ON dt.id=oi.design_type_id\n            GROUP BY oi.design_type_id\n            HAVING total_order >= 2\n            ORDER BY total_order DESC, avg_price ASC\n            LIMIT 5\n        ")->result();
        if (!$rows) return "📈 Belum cukup data untuk rekomendasi kenaikan harga.";
        $out = "📈 Rekomendasi Kenaikan Harga\n\n";
        foreach ($rows as $i => $r) {
            $out .= ($i+1) . ". {$r->name}\n"
                . "   Terjual: {$r->total_order} item | Rata-rata harga: " . $this->rupiah($r->avg_price) . "\n"
                . "   Saran: pertimbangkan kenaikan bertahap 10–15% jika permintaan stabil.\n";
        }
        return trim($out);
    }

    private function analysis_daily_owner()
    {
        $today = date('Y-m-d');
        $orders = (int)$this->db->query("SELECT COUNT(*) total FROM orders WHERE DATE(created_at)=CURDATE()")->row()->total;
        $paid = $this->paid_sum_between($today, $today);
        $unpaid = $this->db->query("SELECT COUNT(*) c, COALESCE(SUM(total-paid),0) sisa FROM orders WHERE paid < total")->row();
        return "📋 Ringkasan Harian Owner\n\n"
            . "Order baru hari ini : {$orders}\n"
            . "Pembayaran masuk    : " . $this->rupiah($paid) . "\n"
            . "Piutang aktif       : " . $this->rupiah($unpaid->sisa) . " ({$unpaid->c} order)\n\n"
            . "Prioritas: follow-up invoice belum lunas dengan nilai terbesar.";
    }

    private function analysis_monthly_report()
    {
        [$start, $end] = $this->month_range(0);
        $paid = $this->paid_sum_between($start, $end);
        $orders = $this->db->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) total FROM orders WHERE DATE(created_at) BETWEEN ? AND ?", [$start, $end])->row();
        $topDesign = $this->db->query("\n            SELECT dt.name, COUNT(*) total\n            FROM order_items oi JOIN orders o ON o.id=oi.order_id JOIN design_types dt ON dt.id=oi.design_type_id\n            WHERE DATE(o.created_at) BETWEEN ? AND ?\n            GROUP BY oi.design_type_id\n            ORDER BY total DESC LIMIT 1\n        ", [$start, $end])->row();
        $topClient = $this->db->query("\n            SELECT c.name, COUNT(*) total\n            FROM orders o JOIN clients c ON c.id=o.client_id\n            WHERE DATE(o.created_at) BETWEEN ? AND ?\n            GROUP BY o.client_id\n            ORDER BY total DESC LIMIT 1\n        ", [$start, $end])->row();
        return "📄 Laporan Bulanan {$this->month_label()}\n\n"
            . "Total order       : {$orders->c}\n"
            . "Nilai invoice     : " . $this->rupiah($orders->total) . "\n"
            . "Pembayaran masuk  : " . $this->rupiah($paid) . "\n"
            . "Desain terlaris   : " . ($topDesign ? $topDesign->name . " ({$topDesign->total} item)" : '-') . "\n"
            . "Klien paling aktif: " . ($topClient ? $topClient->name . " ({$topClient->total} order)" : '-') . "\n\n"
            . "Kesimpulan: gunakan laporan ini untuk mengecek performa dan menentukan follow-up pembayaran.";
    }

    private function analysis_underperforming_design()
    {
        $rows = $this->db->query("\n            SELECT dt.name, COUNT(oi.id) total\n            FROM design_types dt\n            LEFT JOIN order_items oi ON oi.design_type_id=dt.id\n            WHERE dt.is_active=1\n            GROUP BY dt.id\n            HAVING total <= 1\n            ORDER BY total ASC, dt.created_at DESC\n            LIMIT 10\n        ")->result();
        if (!$rows) return "✅ Tidak ada desain aktif yang terlihat kurang laku berdasarkan data order.";
        $out = "📉 Desain Kurang Laku\n\n";
        foreach ($rows as $i => $r) {
            $out .= ($i+1) . ". {$r->name} – {$r->total} order\n";
        }
        $out .= "\nSaran: promosikan ulang, ubah contoh preview, atau nonaktifkan jika sudah tidak diproduksi.";
        return trim($out);
    }

    /* =========================================================
     * BAGIAN 8 — SIMULASI DAN TARGET
     * ========================================================= */

    private function simulate_price_up()
    {
        return "📈 Simulasi Harga Naik\n\nJika harga dinaikkan, sistem belum mengubah data apa pun.\nGunakan perintah: ubah harga [nama desain] [harga baru] jika ingin update sungguhan.";
    }

    private function check_target($text)
    {
        $target = $this->extract_money($text);
        if ($target <= 0) $target = 8000000;

        [$start, $end] = $this->month_range(0);
        $total = $this->paid_sum_between($start, $end);

        return ($total >= $target)
            ? "🎯 Target bulan ini sudah aman.\n\nTarget: " . $this->rupiah($target) . "\nTercapai: " . $this->rupiah($total)
            : "🎯 Target bulan ini belum aman.\n\nTarget: " . $this->rupiah($target) . "\nTercapai: " . $this->rupiah($total) . "\nKurang: " . $this->rupiah($target - $total);
    }

    /* =========================================================
     * BAGIAN 9 — ENDPOINT WIZARD INPUT DATA
     * ========================================================= */

    /** Simpan desain baru dari wizard input data. */
    public function save_design_wizard()
    {
        require_post();
        $name = trim((string)$this->input->post('name', true));
        $status = (int)$this->input->post('status');

        if ($name === '') {
            $this->json_status(false, 'Nama desain wajib diisi');
            return;
        }

        $exists = $this->db
            ->where('LOWER(name)', strtolower($name))
            ->limit(1)
            ->get('design_types')
            ->row();

        $previewData = [];
        if (!empty($_FILES['preview']['name'])) {
            $res = vi_upload_image('preview', 'assets/uploads/design_previews', [
                'max_size_kb' => 8192,
                'max_width' => 900,
                'max_height' => 900,
                'quality' => 50,
                'thumb_dir' => 'assets/uploads/cache/designs',
                'thumb_width' => 360,
                'thumb_height' => 360,
                'thumb_quality' => 42,
            ]);

            if (empty($res['ok'])) {
                $this->json_status(false, $res['error'] ?? 'Gagal upload gambar');
                return;
            }

            if ($exists && !empty($exists->preview_image)) {
                $old = FCPATH . 'assets/uploads/design_previews/' . $exists->preview_image;
                if (is_file($old)) @unlink($old);
            }
            if ($exists && !empty($exists->preview_thumb)) {
                $thumb = FCPATH . trim((string)$exists->preview_thumb, '/');
                if (is_file($thumb)) @unlink($thumb);
            }

            $previewData['preview_image'] = $res['file_name'];
            if ($this->db->field_exists('preview_thumb', 'design_types')) {
                $previewData['preview_thumb'] = $res['thumb_path'] ?? null;
            }
            if ($this->db->field_exists('preview_storage', 'design_types')) {
                $previewData['preview_storage'] = 'local';
            }
        }

        if ($exists) {
            // Jangan gagal hanya karena nama tersimpan di database tetapi sedang nonaktif/tersembunyi.
            // Data lama dipakai ulang agar flow input tidak nyangkut dan tidak membuat duplikat.
            $update = array_merge([
                'name' => $name,
                'is_active' => $status ? 1 : 0,
            ], $previewData);

            $ok = $this->db->where('id', (int)$exists->id)->update('design_types', $update);
            if (!$ok) {
                $this->json_status(false, 'Gagal mengaktifkan/memperbarui desain lama');
                return;
            }

            $this->audit('REUSE_DESIGN', 'design_types', (int)$exists->id, "Desain {$name} dipakai ulang lewat AI Wizard");
            $this->json_status(true, ((int)$exists->is_active === 1 ? 'Desain sudah ada, dipakai ulang' : 'Desain lama diaktifkan ulang'), [
                'design_id' => (int)$exists->id,
                'reused' => true
            ]);
            return;
        }

        $data = array_merge([
            'name'          => $name,
            'preview_image' => null,
            'is_active'     => $status ? 1 : 0,
            'created_at'    => date('Y-m-d H:i:s')
        ], $previewData);

        $ok = $this->db->insert('design_types', $data);
        if (!$ok) {
            $this->json_status(false, 'Gagal menyimpan desain');
            return;
        }

        $designId = $this->db->insert_id();
        $this->audit('CREATE_DESIGN', 'design_types', $designId, "Desain {$name} dibuat lewat AI Wizard");

        $this->json_status(true, 'Desain berhasil disimpan', ['design_id' => $designId]);
    }

    /** Ambil body part untuk form harga wizard. */
    public function get_body_parts()
    {
        $parts = $this->db->order_by('id', 'ASC')->get('body_parts')->result();
        $this->output->set_content_type('application/json')->set_output(json_encode($parts));
    }

    /** Simpan / update price matrix dari wizard. */
    public function save_price_matrix_wizard()
    {
        require_post();
        $designId = (int)$this->input->post('design_id');
        $prices = $this->input->post('prices');

        if (!$designId || !is_array($prices)) {
            $this->json_status(false, 'Data harga tidak lengkap');
            return;
        }

        $this->db->trans_start();
        foreach ($prices as $bodyPartId => $price) {
            $bodyPartId = (int)$bodyPartId;
            $price = max(0, rupiah_number($price));
            if ($bodyPartId <= 0 || $price <= 0) continue;

            $exists = $this->db->get_where('price_matrix', [
                'design_type_id' => $designId,
                'body_part_id' => $bodyPartId
            ])->row();

            if ($exists) {
                $this->db->where('id', $exists->id)->update('price_matrix', ['base_price' => $price]);
            } else {
                $this->db->insert('price_matrix', [
                    'design_type_id' => $designId,
                    'body_part_id' => $bodyPartId,
                    'base_price' => $price,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            $this->json_status(false, 'Gagal menyimpan harga');
            return;
        }

        $this->audit('SAVE_PRICE_MATRIX', 'design_types', $designId, "Harga desain ID {$designId} disimpan lewat AI Wizard");
        $this->json_status(true, 'Harga berhasil disimpan');
    }

    /** Simpan order final dari wizard input data. */
    public function save_order_wizard()
    {
        require_post();
        $clientName = trim((string)$this->input->post('client_name', true));
        $phone = trim((string)$this->input->post('phone', true));
        $title = trim((string)$this->input->post('title', true));
        $deadline = $this->input->post('deadline', true) ?: null;
        $items = $this->input->post('items');

        $dp = max(0, rupiah_number($this->input->post('dp')));
        $addonsRaw = (string)$this->input->post('addons', true);
        $addons = max(0, rupiah_number($addonsRaw));
        $discount = max(0, rupiah_number($this->input->post('discount')));
        $revisionCount = max(0, (int)$this->input->post('revision'));
        $notes = trim((string)$this->input->post('notes', true));

        if ($clientName === '' || !is_array($items) || count($items) === 0) {
            $this->json_status(false, 'Data order tidak lengkap');
            return;
        }

        if ($title === '') $title = 'Order dari AI Wizard';

        // Validasi item dan hitung subtotal memakai price_matrix di database, bukan dari frontend.
        $validItems = [];
        $subtotal = 0;
        foreach ($items as $item) {
            $designId = isset($item['design_id']) ? (int)$item['design_id'] : 0;
            $bodyPartId = isset($item['body_part_id']) ? (int)$item['body_part_id'] : 0;
            $qty = isset($item['qty']) ? max(1, (int)$item['qty']) : 1;

            if (!$designId || !$bodyPartId) continue;

            $pm = $this->db->get_where('price_matrix', [
                'design_type_id' => $designId,
                'body_part_id' => $bodyPartId
            ])->row();

            if (!$pm) {
                $this->json_status(false, 'Harga tidak ditemukan di price matrix');
                return;
            }

            $lineTotal = (int)$pm->base_price * $qty;
            $subtotal += $lineTotal;
            $validItems[] = [
                'design_type_id' => $designId,
                'body_part_id' => $bodyPartId,
                'qty' => $qty,
                'price' => (int)$pm->base_price,
                'note' => $notes ?: null
            ];
        }

        if (count($validItems) === 0) {
            $this->json_status(false, 'Item order tidak valid');
            return;
        }

        if ($discount > $subtotal + $addons) $discount = $subtotal + $addons;

        $revisionFee = 0;
        $total = max(0, $subtotal + $addons + $revisionFee - $discount);
        if ($dp > $total) $dp = $total;
        $status = 'PROSES';

        $this->db->trans_start();

        // Cari client lama atau buat client baru.
        $client = $this->db->where('name', $clientName)->get('clients')->row();
        if ($client) {
            $clientId = (int)$client->id;
            if ($phone !== '' && $phone !== $client->phone) {
                $this->db->where('id', $clientId)->update('clients', ['phone' => $phone]);
            }
        } else {
            $this->db->insert('clients', [
                'name' => $clientName,
                'phone' => $phone,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $clientId = $this->db->insert_id();
        }

        $orderCode = $this->generate_order_code();
        $first = $validItems[0];

        // Kolom order disamakan dengan schema vector_invoice.sql.
        $this->db->insert('orders', [
            'order_code' => $orderCode,
            'client_id' => $clientId,
            'title' => $title,
            'design_type_id' => $first['design_type_id'],
            'body_part_id' => $first['body_part_id'],
            'base_price' => $subtotal,
            'addons' => $addons,
            'revision_count' => $revisionCount,
            'revision_fee' => $revisionFee,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'paid' => 0,
            'status' => $status,
            'deadline' => $deadline,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $orderId = $this->db->insert_id();
        foreach ($validItems as $it) {
            $this->db->insert('order_items', [
                'order_id' => $orderId,
                'design_type_id' => $it['design_type_id'],
                'body_part_id' => $it['body_part_id'],
                'qty' => $it['qty'],
                'price' => $it['price'],
                'note' => $it['note'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        if ($dp > 0 && $this->db->table_exists('order_payments')) {
            $this->orders->add_payment($orderId, $dp, 'Pembayaran awal / DP dari AI Wizard', date('Y-m-d'), 'AI_WIZARD');
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            $this->json_status(false, 'Gagal menyimpan order');
            return;
        }

        $this->audit('CREATE_ORDER', 'orders', $orderId, "Order {$orderCode} dibuat lewat AI Wizard");
        $this->json_status(true, 'Order berhasil disimpan', [
            'order_id' => $orderId,
            'order_code' => $orderCode,
            'total' => $total
        ]);
    }

    /** Response JSON standar untuk endpoint wizard. */
    private function json_status($status, $msg = '', array $extra = [])
    {
        $payload = array_merge(['status' => (bool)$status, 'msg' => $msg], $extra);
        $this->output->set_content_type('application/json')->set_output(json_encode($payload));
    }

    /* =========================================================
     * BAGIAN 10 — SMALL TALK, HELP, DAN FALLBACK
     * ========================================================= */

    private function chat_hello()
    {
        return "Halo! 😎\n\nMau cek order, income, order belum lunas, atau input data baru?";
    }

    private function chat_ack()
    {
        $pending = $this->session->userdata('ai_pending');
        if ($pending) {
            return "Oke, aku siap lanjut. Jawab sesuai pilihan yang muncul ya.";
        }

        return "Oke siap 🙌\n\nKamu bisa langsung bilang, misalnya:\n- Ringkasan bulan ini\n- Order belum lunas\n- Cek order macet\n- Input data";
    }

    private function chat_cancel()
    {
        return "Aman, tidak ada aksi yang dibatalkan karena belum ada proses konfirmasi aktif.";
    }

    private function chat_activity()
    {
        return "Lagi standby bantuin kamu ngatur invoice 😄\n\nMau mulai dari cek order atau input data?";
    }

    private function chat_busy()
    {
        return "Nggak sibuk kok 😄\n\nSilakan ketik kebutuhanmu.";
    }

    private function chat_thanks()
    {
        return "Sama-sama 🙌\n\nKalau butuh cek laporan, follow-up klien, atau input order, tinggal bilang.";
    }

    private function chat_identity()
    {
        return "Aku AI Assistant gratis berbasis rule-based intent detection di Vector Invoice.\n\nAku tidak memakai API berbayar, jadi semua proses dilakukan langsung di sistemmu.";
    }

    private function chat_user_identity()
    {
        return "Kamu adalah admin Vector Invoice yang sedang login di sistem ini.";
    }

    private function ai_capability()
    {
        return "🤖 Aku bisa bantu beberapa hal ini:\n\n"
            . "📊 Dashboard & laporan:\n"
            . "- Ringkasan bulan ini\n"
            . "- Total pendapatan bulan ini\n"
            . "- Order hari ini\n"
            . "- Order belum lunas\n"
            . "- Order lunas / order proses\n\n"
            . "📈 Insight:\n"
            . "- Desain terlaris\n"
            . "- Body part paling sering dipesan\n"
            . "- Klien paling aktif\n"
            . "- Pembayaran tertunda\n\n"
            . "🧾 Aksi data:\n"
            . "- Input data\n"
            . "- Tandai order lunas\n"
            . "- Ubah harga desain\n"
            . "- Nonaktifkan desain\n"
            . "- Hapus order / hapus client dengan konfirmasi\n\n"
            . "💬 Draft pesan:\n"
            . "- Buat pesan follow up\n"
            . "- Buat pesan DP\n"
            . "- Buat pesan order selesai\n"
            . "- Buat pesan penagihan";
    }

    private function fallback()
    {
        return "Aku belum memahami perintah tersebut.\n\n"
            . "Coba pakai contoh berikut:\n"
            . "- Ringkasan bulan ini\n"
            . "- Order belum lunas\n"
            . "- Total income bulan ini\n"
            . "- Desain terlaris\n"
            . "- Bandingkan omzet bulan ini\n"
            . "- Siapa yang harus follow up hari ini\n"
            . "- Cek order macet\n"
            . "- Prediksi income bulan ini\n"
            . "- Tandai lunas nama_client\n"
            . "- Input data";
    }
}
