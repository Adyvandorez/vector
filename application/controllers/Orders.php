<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Orders extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Order_model', 'orders');
        $this->load->model('Price_model', 'prices');
        $this->load->model('Client_model', 'clients');
        $this->load->library('google_drive_storage');
    }

    /** total = subtotal_items + addons + revision_fee - discount */
    private function calc_total($subtotal_items, $addons, $rev_fee, $discount)
    {
        return max(0, (int)$subtotal_items + (int)$addons + (int)$rev_fee - (int)$discount);
    }

    /** Status di kolom orders.status sekarang dipakai untuk progres kerja, bukan pembayaran. */
    private function normalize_work_status($status)
    {
        $allowed = ['MASUK', 'PROSES', 'REVISI', 'SELESAI'];
        $status = strtoupper((string)$status);
        if ($status === 'LUNAS') return 'SELESAI';
        return in_array($status, $allowed, true) ? $status : 'MASUK';
    }

    /** Nomor order dibuat urut harian agar tidak bentrok seperti random biasa. */
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

    /** Hitung jumlah baris item yang dikirim form. */
    private function raw_item_rows_count()
    {
        $design_ids = $this->input->post('item_design_type_id');
        return is_array($design_ids) ? count($design_ids) : 0;
    }

    /** Ambil item valid dari POST. Item valid harus punya jenis desain dan bagian. */
    private function build_items_from_post()
    {
        $design_ids = $this->input->post('item_design_type_id');
        $body_ids   = $this->input->post('item_body_part_id');
        $qtys       = $this->input->post('item_qty');
        $prices     = $this->input->post('item_price');
        $notes      = $this->input->post('item_note');

        $items = [];
        if (is_array($design_ids)) {
            for ($i = 0; $i < count($design_ids); $i++) {
                $d = isset($design_ids[$i]) ? (int)$design_ids[$i] : 0;
                $b = isset($body_ids[$i]) ? (int)$body_ids[$i] : 0;
                $q = isset($qtys[$i]) ? max(1, (int)$qtys[$i]) : 1;
                $p = isset($prices[$i]) ? max(0, rupiah_number($prices[$i])) : 0;
                $n = isset($notes[$i]) ? trim($notes[$i]) : null;

                if ($d && $b) {
                    $items[] = [
                        'design_type_id' => $d,
                        'body_part_id'   => $b,
                        'qty'            => $q,
                        'price'          => $p,
                        'note'           => $n
                    ];
                }
            }
        }
        return $items;
    }

    /** subtotal items = sum(qty * price) */
    private function items_subtotal($items)
    {
        $sum = 0;
        foreach ($items as $it) {
            $sum += ((int)$it['qty'] * (int)$it['price']);
        }
        return $sum;
    }

    /** Recalculate total setelah perubahan revisi/order dan sinkron paid dari riwayat pembayaran. */
    private function recalc_order_total($order_id)
    {
        $order = $this->orders->find($order_id);
        if (!$order) return;

        $total = $this->calc_total(
            (int)$order->base_price,
            (int)$order->addons,
            (int)$order->revision_fee,
            (int)$order->discount
        );

        $this->orders->update($order_id, [
            'total'  => $total,
            'status' => $this->normalize_work_status($order->status)
        ]);
        $this->orders->sync_paid_from_payments($order_id);
    }

    private function resolve_client_from_post()
    {
        $selectedId = (int)$this->input->post('client_id');
        if ($selectedId > 0) {
            $client = $this->clients->find($selectedId);
            if ($client) return $selectedId;
        }

        $name = trim((string)$this->input->post('client_name', true));
        $phone = normalize_phone($this->input->post('client_phone', true));
        $email = strtolower(trim((string)$this->input->post('client_email', true)));
        if ($name === '') return null;
        return $this->clients->find_or_create($name, $phone, $email);
    }

    public function index()
    {
        $q = trim((string)$this->input->get('q', true));
        $status = strtoupper(trim((string)$this->input->get('status', true)));
        $source = strtoupper(trim((string)$this->input->get('source', true)));
        $data = [
            'title' => 'Orders',
            'rows'  => $this->orders->all($q, $status, $source),
            'q'     => $q,
            'status_filter' => $status,
            'source_filter' => $source,
            'page_css' => ['orders-mobile.css', 'admin.css'],
            'page_js' => ['list-search.js']
        ];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('orders/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        $designs = $this->prices->design_types();
        $parts   = $this->prices->body_parts();

        if ($this->input->post()) {
            $client_id = $this->resolve_client_from_post();
            if (!$client_id) {
                $this->session->set_flashdata('orders_err', 'Pilih pelanggan yang sudah ada atau isi nama pelanggan baru.');
                redirect('orders/create');
            }
            if (trim((string)$this->input->post('title', true)) === '') {
                $this->session->set_flashdata('orders_err', 'Judul pekerjaan wajib diisi.');
                redirect('orders/create');
            }

            $rawCount = $this->raw_item_rows_count();
            $items    = $this->build_items_from_post();

            if ($rawCount === 0) {
                $this->session->set_flashdata('orders_err', 'Minimal 1 baris item desain harus ditambahkan.');
                redirect('orders/create');
            }

            if (count($items) === 0) {
                $this->session->set_flashdata('orders_err', 'Item desain belum lengkap. Pilih Jenis & Bagian minimal 1 baris.');
                redirect('orders/create');
            }

            $subtotal = $this->items_subtotal($items);
            $addons   = rupiah_number($this->input->post('addons'));
            $discount = rupiah_number($this->input->post('discount'));
            $paid     = rupiah_number($this->input->post('paid'));
            $status   = $this->normalize_work_status($this->input->post('status', true));

            $total  = $this->calc_total($subtotal, $addons, 0, $discount);
            $initialPaid = min($paid, $total);

            $this->db->trans_start();
            $this->orders->create([
                'order_code'     => $this->generate_order_code(),
                'client_id'      => $client_id,
                'design_type_id' => $items[0]['design_type_id'],
                'body_part_id'   => $items[0]['body_part_id'],
                'title'          => trim((string)$this->input->post('title', true)),
                'customer_notes' => trim((string)$this->input->post('customer_notes', true)) ?: null,
                'admin_notes'    => trim((string)$this->input->post('admin_notes', true)) ?: null,
                'base_price'     => $subtotal,
                'addons'         => $addons,
                'revision_count' => 0,
                'revision_fee'   => 0,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'total'          => $total,
                'paid'           => 0,
                'status'         => $status,
                'created_via'    => 'WEB_ADMIN',
                'deadline'       => $this->input->post('deadline', true) ?: null,
                'created_at'     => date('Y-m-d H:i:s')
            ]);
            $order_id = $this->db->insert_id();
            $this->orders->record_status_change($order_id, null, $status, 'ADMIN', current_user_id(), 'Order dibuat melalui web admin.');
            $this->orders->replace_items($order_id, $items);
            if ($initialPaid > 0) {
                $this->orders->add_payment($order_id, $initialPaid, 'Pembayaran awal / DP', date('Y-m-d'), 'DP');
            }
            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                $this->session->set_flashdata('orders_err', 'Gagal menyimpan order. Silakan coba lagi.');
                redirect('orders/create');
            }

            redirect('orders/view/' . $order_id);
        }

        $data = [
            'title'          => 'Tambah Order',
            'row'            => null,
            'designs'        => $designs,
            'parts'          => $parts,
            'clients'        => $this->clients->active_options(),
            'selected_client_id' => (int)$this->input->get('client_id'),
            'existing_items' => [],
            'page_css'       => ['orders-form.css', 'admin.css'],
            'page_js'        => ['custom-select.js', 'vi-datepicker.js', 'orders-form.js', 'rupiah-format.js']
        ];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('orders/form', $data);
        $this->load->view('layout/footer');
    }

    public function edit($id)
    {
        $row = $this->orders->find($id);
        if (!$row) show_404();

        $designs        = $this->prices->design_types();
        $parts          = $this->prices->body_parts();
        $existing_items = $this->orders->items($id);
        $client         = $this->clients->find($row->client_id);

        if ($this->input->post()) {
            $rawCount = $this->raw_item_rows_count();
            $items    = $this->build_items_from_post();

            if ($rawCount === 0) {
                $this->session->set_flashdata('orders_err', 'Minimal 1 item desain harus diisi.');
                redirect('orders/edit/' . $id);
            }

            if (count($items) === 0) {
                $this->session->set_flashdata('orders_err', 'Item desain belum lengkap. Pilih Jenis & Bagian minimal 1 baris.');
                redirect('orders/edit/' . $id);
            }

            $client_id = $this->resolve_client_from_post();
            if (!$client_id) {
                $this->session->set_flashdata('orders_err', 'Pilih pelanggan yang sudah ada atau isi nama pelanggan baru.');
                redirect('orders/edit/' . $id);
            }
            if (trim((string)$this->input->post('title', true)) === '') {
                $this->session->set_flashdata('orders_err', 'Judul pekerjaan wajib diisi.');
                redirect('orders/edit/' . $id);
            }

            $subtotal = $this->items_subtotal($items);
            $addons   = rupiah_number($this->input->post('addons'));
            $discount = rupiah_number($this->input->post('discount'));
            $status   = $this->normalize_work_status($this->input->post('status', true));

            $total  = $this->calc_total($subtotal, $addons, (int)$row->revision_fee, $discount);

            $this->db->trans_start();
            $this->orders->update($id, [
                'client_id'      => $client_id,
                'design_type_id' => $items[0]['design_type_id'],
                'body_part_id'   => $items[0]['body_part_id'],
                'title'          => trim((string)$this->input->post('title', true)),
                'customer_notes' => trim((string)$this->input->post('customer_notes', true)) ?: null,
                'admin_notes'    => trim((string)$this->input->post('admin_notes', true)) ?: null,
                'base_price'     => $subtotal,
                'addons'         => $addons,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'total'          => $total,
                'status'         => $status,
                'deadline'       => $this->input->post('deadline', true) ?: null
            ]);
            $this->orders->record_status_change($id, $row->status, $status, 'ADMIN', current_user_id(), trim((string)$this->input->post('status_note', true)) ?: null);
            $this->orders->replace_items($id, $items);
            $this->orders->sync_paid_from_payments($id);
            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                $this->session->set_flashdata('orders_err', 'Gagal memperbarui order. Silakan coba lagi.');
                redirect('orders/edit/' . $id);
            }

            redirect('orders/view/' . $id);
        }

        $data = [
            'title'          => 'Edit Order',
            'row'            => $row,
            'designs'        => $designs,
            'parts'          => $parts,
            'existing_items' => $existing_items,
            'client'         => $client,
            'clients'        => $this->clients->active_options(),
            'selected_client_id' => (int)$row->client_id,
            'page_css'       => ['orders-form.css', 'admin.css'],
            'page_js'        => ['custom-select.js', 'vi-datepicker.js', 'orders-form.js', 'rupiah-format.js']
        ];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('orders/form', $data);
        $this->load->view('layout/footer');
    }

    public function view($id)
    {
        $order = $this->orders->detail($id);
        if (!$order) show_404();

        $data = [
            'title'     => 'Detail Order',
            'order'     => $order,
            'files'     => $this->orders->files($id),
            'revisions' => $this->orders->revisions($id),
            'items'     => $this->orders->items($id),
            'payments'  => $this->orders->payments($id),
            'payment_summary' => $this->orders->payment_summary($id),
            'status_histories' => $this->orders->status_histories($id),
            'page_css'  => ['orders-view.css', 'admin.css'],
            'page_js'   => ['vi-datepicker.js', 'orders-view.js', 'rupiah-format.js']
        ];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('orders/view', $data);
        $this->load->view('layout/footer');
    }

    /** Aksi hapus hanya POST. File fisik ikut dibersihkan lewat model. */
    public function delete($id)
    {
        require_post();
        $this->orders->delete_with_files($id);
        redirect('orders');
    }

    public function add_payment($id)
    {
        require_post();
        $order = $this->orders->find($id);
        if (!$order) show_404();

        $amount = rupiah_number($this->input->post('amount'));
        $note = $this->input->post('note', true);
        $paymentDate = $this->input->post('payment_date', true) ?: date('Y-m-d');
        $remaining = max(0, (int)$order->total - (int)$order->paid);

        if ($amount <= 0) {
            $this->session->set_flashdata('orders_err', 'Nominal pembayaran harus lebih dari Rp 0.');
            redirect('orders/view/' . $id);
        }

        if ($amount > $remaining) {
            $this->session->set_flashdata('orders_err', 'Nominal pembayaran melebihi sisa tagihan. Sisa saat ini: ' . rupiah($remaining));
            redirect('orders/view/' . $id);
        }

        $ok = $this->orders->add_payment($id, $amount, $note ?: 'Pembayaran tambahan', $paymentDate, 'MANUAL');
        if (!$ok) {
            $this->session->set_flashdata('orders_err', 'Gagal menyimpan pembayaran.');
        } else {
            $this->session->set_flashdata('orders_ok', 'Pembayaran ' . rupiah($amount) . ' berhasil dicatat.');
        }

        redirect('orders/view/' . $id);
    }

    public function delete_payment($payment_id)
    {
        require_post();
        $payment = $this->orders->payment_find($payment_id);
        if (!$payment) show_404();

        $orderId = (int)$payment->order_id;
        $this->orders->delete_payment($payment_id);
        $this->session->set_flashdata('orders_ok', 'Riwayat pembayaran berhasil dihapus dan total dibayar dihitung ulang.');
        redirect('orders/view/' . $orderId);
    }

    public function add_revision($id)
    {
        require_post();
        $order = $this->orders->find($id);
        if (!$order) show_404();

        $note = $this->input->post('note', true);
        $fee  = rupiah_number($this->input->post('fee'));

        if (trim($note) !== '') {
            $this->db->trans_start();
            $this->orders->add_revision($id, $note, $fee, 'ADMIN');
            if ($this->normalize_work_status($order->status) !== 'REVISI') {
                $this->orders->update($id, ['status' => 'REVISI']);
                $this->orders->record_status_change($id, $order->status, 'REVISI', 'ADMIN', current_user_id(), 'Revisi ditambahkan oleh admin.');
            }
            $this->recalc_order_total($id);
            $this->db->trans_complete();
            $this->session->set_flashdata('orders_ok', 'Revisi berhasil ditambahkan.');
        }

        redirect('orders/view/' . $id);
    }

    public function upload_preview($id)
    {
        require_post();
        $order = $this->orders->find($id);
        if (!$order) show_404();

        if (empty($_FILES['previews']['name'][0])) {
            $this->session->set_flashdata('orders_err', 'Tidak ada file yang dipilih.');
            redirect('orders/view/' . $id);
        }

        $files = $_FILES['previews'];
        $count = count($files['name']);
        $success = 0;

        for ($i = 0; $i < $count; $i++) {
            $_FILES['preview_tmp']['name']     = $files['name'][$i];
            $_FILES['preview_tmp']['type']     = $files['type'][$i];
            $_FILES['preview_tmp']['tmp_name'] = $files['tmp_name'][$i];
            $_FILES['preview_tmp']['error']    = $files['error'][$i];
            $_FILES['preview_tmp']['size']     = $files['size'][$i];

            $res = vi_upload_image('preview_tmp', 'assets/uploads/previews', [
                'max_size_kb' => 8192,
                'max_width' => 900,
                'max_height' => 900,
                'quality' => 50,
                'thumb_dir' => 'assets/uploads/cache/previews',
                'thumb_width' => 360,
                'thumb_height' => 360,
                'thumb_quality' => 42,
            ]);

            if (empty($res['ok'])) {
                $this->session->set_flashdata('orders_err', 'Gagal upload: ' . ($res['error'] ?? 'file tidak valid.'));
                redirect('orders/view/' . $id);
            }

            $drive = null;
            $localPath = FCPATH . 'assets/uploads/previews/' . $res['file_name'];
            $detail = $this->orders->detail($id);
            $deadlineYear = !empty($detail->deadline) ? date('Y', strtotime($detail->deadline)) : date('Y');
            $deadlineMonth = !empty($detail->deadline) ? date('m F', strtotime($detail->deadline)) : date('m F');
            $folderOrder = trim(($detail->order_code ?? ('ORD-' . $id)) . ' - ' . ($detail->client_name ?? 'Client'));
            $driveName = $this->google_drive_storage->safe_filename($folderOrder . '-' . $res['file_name']);

            $drive = $this->google_drive_storage->upload_to_path(
                $localPath,
                $driveName,
                ['Orders', $deadlineYear, $deadlineMonth, $folderOrder, 'Preview'],
                $this->google_drive_storage->detect_mime($localPath)
            );

            $this->orders->add_file($id, 'PREVIEW', $res['file_name'], $res['original_name'], $drive ?: [], [
                'thumb_path' => $res['thumb_path'] ?? null,
                'file_size' => is_file($localPath) ? filesize($localPath) : null,
                'mime_type' => $this->google_drive_storage->detect_mime($localPath),
            ]);
            $success++;
        }

        $driveNote = $this->google_drive_storage->is_enabled() ? ' Preview yang berhasil akan tersimpan ke Google Drive jika koneksi/API aktif.' : ' Google Drive belum aktif, file disimpan lokal.';
        $this->session->set_flashdata('orders_ok', $success . ' preview berhasil diupload dan dikompres.' . $driveNote);
        redirect('orders/view/' . $id);
    }


    public function upload_source($id)
    {
        require_post();
        $order = $this->orders->find($id);
        if (!$order) show_404();

        if (empty($_FILES['sources']['name'][0])) {
            $this->session->set_flashdata('orders_err', 'Tidak ada file master yang dipilih.');
            redirect('orders/view/' . $id);
        }

        if (!$this->google_drive_storage->is_enabled()) {
            $this->session->set_flashdata('orders_err', 'File master CDR hanya disimpan di Google Drive. Hubungkan Google Drive terlebih dahulu sebelum upload file master.');
            redirect('orders/view/' . $id);
        }

        $files = $_FILES['sources'];
        $count = count($files['name']);
        $success = 0;

        for ($i = 0; $i < $count; $i++) {
            $_FILES['source_tmp']['name']     = $files['name'][$i];
            $_FILES['source_tmp']['type']     = $files['type'][$i];
            $_FILES['source_tmp']['tmp_name'] = $files['tmp_name'][$i];
            $_FILES['source_tmp']['error']    = $files['error'][$i];
            $_FILES['source_tmp']['size']     = $files['size'][$i];

            $res = vi_source_temp_file('source_tmp', ['cdr'], 204800);
            if (empty($res['ok'])) {
                $this->session->set_flashdata('orders_err', 'Gagal upload file master: ' . ($res['error'] ?? 'file tidak valid.'));
                redirect('orders/view/' . $id);
            }

            $detail = $this->orders->detail($id);
            $deadlineYear = !empty($detail->deadline) ? date('Y', strtotime($detail->deadline)) : date('Y');
            $deadlineMonth = !empty($detail->deadline) ? date('m F', strtotime($detail->deadline)) : date('m F');
            $folderOrder = trim(($detail->order_code ?? ('ORD-' . $id)) . ' - ' . ($detail->client_name ?? 'Client'));
            $driveName = $this->google_drive_storage->safe_filename($folderOrder . '-' . $res['original_name']);

            $drive = $this->google_drive_storage->upload_to_path(
                $res['tmp_path'],
                $driveName,
                ['Orders', $deadlineYear, $deadlineMonth, $folderOrder, 'File Master'],
                $res['mime_type'] ?: 'application/octet-stream'
            );

            if (!$drive) {
                $this->session->set_flashdata('orders_err', 'Gagal upload file master ke Google Drive: ' . $this->google_drive_storage->last_error());
                redirect('orders/view/' . $id);
            }

            $this->orders->add_file($id, 'SOURCE', null, $res['original_name'], $drive, [
                'file_size' => $res['size'],
                'mime_type' => $res['mime_type'] ?: 'application/octet-stream',
            ]);
            $success++;
        }

        $this->session->set_flashdata('orders_ok', $success . ' file master berhasil dibackup ke Google Drive. File CDR tidak disimpan di lokal.');
        redirect('orders/view/' . $id);
    }

    public function delete_sources($order_id)
    {
        require_post();
        $order = $this->orders->find($order_id);
        if (!$order) show_404();

        $ids = $this->input->post('source_ids');
        if (!is_array($ids) || count($ids) === 0) {
            $this->session->set_flashdata('orders_err', 'Pilih minimal 1 file master untuk dihapus.');
            redirect('orders/view/' . $order_id);
        }

        $files = $this->db
            ->where('order_id', (int)$order_id)
            ->where('file_type', 'SOURCE')
            ->where_in('id', array_map('intval', $ids))
            ->get('order_files')
            ->result();

        if (!$files) {
            $this->session->set_flashdata('orders_err', 'File master tidak ditemukan.');
            redirect('orders/view/' . $order_id);
        }

        $this->orders->delete_physical_files($files);
        $this->db
            ->where('order_id', (int)$order_id)
            ->where('file_type', 'SOURCE')
            ->where_in('id', array_map('intval', $ids))
            ->delete('order_files');

        $this->session->set_flashdata('orders_ok', count($files) . ' file master berhasil dihapus dari daftar dan Google Drive.');
        redirect('orders/view/' . $order_id);
    }

    public function delete_previews($order_id)
    {
        require_post();
        $order = $this->orders->find($order_id);
        if (!$order) show_404();

        $ids = $this->input->post('file_ids');
        if (!is_array($ids) || count($ids) === 0) {
            $this->session->set_flashdata('orders_err', 'Pilih minimal 1 preview untuk dihapus.');
            redirect('orders/view/' . $order_id);
        }

        $files = $this->db
            ->where('order_id', (int)$order_id)
            ->where('file_type', 'PREVIEW')
            ->where_in('id', array_map('intval', $ids))
            ->get('order_files')
            ->result();

        if (!$files) {
            $this->session->set_flashdata('orders_err', 'File preview tidak ditemukan.');
            redirect('orders/view/' . $order_id);
        }

        $this->orders->delete_physical_files($files);
        $this->db
            ->where('order_id', (int)$order_id)
            ->where('file_type', 'PREVIEW')
            ->where_in('id', array_map('intval', $ids))
            ->delete('order_files');

        $this->session->set_flashdata('orders_ok', count($files) . ' preview berhasil dihapus.');
        redirect('orders/view/' . $order_id);
    }

    public function revision_edit($rev_id)
    {
        $rev = $this->orders->revision_find($rev_id);
        if (!$rev) show_404();

        $order = $this->orders->find($rev->order_id);
        if (!$order) show_404();

        if ($this->input->post()) {
            $note = $this->input->post('note', true);
            $fee  = rupiah_number($this->input->post('fee'));

            $this->db->trans_start();
            $this->orders->revision_update($rev_id, $note, $fee);
            $this->orders->update($rev->order_id, [
                'revision_fee'   => $this->orders->revision_sum_fee($rev->order_id),
                'revision_count' => $this->orders->revision_count($rev->order_id)
            ]);
            $this->recalc_order_total($rev->order_id);
            $this->db->trans_complete();

            redirect('orders/view/' . $rev->order_id);
        }

        $data = [
            'title'    => 'Edit Revisi',
            'rev'      => $rev,
            'order_id' => $rev->order_id,
            'page_js'  => ['rupiah-format.js']
        ];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('orders/revision_form', $data);
        $this->load->view('layout/footer');
    }

    public function revision_delete($rev_id)
    {
        require_post();
        $rev = $this->orders->revision_find($rev_id);
        if (!$rev) show_404();

        $order = $this->orders->find($rev->order_id);
        if (!$order) show_404();

        $this->db->trans_start();
        $this->orders->revision_delete($rev_id);
        $this->orders->update($rev->order_id, [
            'revision_fee'   => $this->orders->revision_sum_fee($rev->order_id),
            'revision_count' => $this->orders->revision_count($rev->order_id)
        ]);
        $this->recalc_order_total($rev->order_id);
        $this->db->trans_complete();

        redirect('orders/view/' . $rev->order_id);
    }
}
