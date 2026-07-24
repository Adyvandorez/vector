<?php
/**
 * IntentEngine
 * -------------------------------------------------------------
 * Library kecil untuk AI Assistant GRATIS / OFFLINE.
 * File ini TIDAK memakai API berbayar. Semua proses dilakukan
 * dengan rule-based intent detection: kata kunci, sinonim, dan
 * normalisasi typo sederhana.
 */
defined('BASEPATH') or exit('No direct script access allowed');

class IntentEngine
{
    /**
     * Normalisasi teks user agar lebih mudah dicocokkan.
     * Contoh: "blm" -> "belum", "2jt" -> "2000000".
     */
    public function normalize($text)
    {
        $text = strtolower(trim((string)$text));
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        $map = [
            // sapaan / typo umum
            'hallo' => 'halo',
            'helo' => 'halo',
            'hay' => 'hai',
            'oi' => 'halo',
            'min' => 'halo',

            // bahasa santai / jawaban pendek
            'okee' => 'oke',
            'okey' => 'oke',
            'okk' => 'oke',
            'sip' => 'oke',
            'sipp' => 'oke',
            'siap' => 'oke',
            'gas' => 'lanjut',
            'gass' => 'lanjut',
            'gaskeun' => 'lanjut',
            'y' => 'ya',
            'yes' => 'ya',
            'yoi' => 'ya',
            'iyaaa' => 'iya',
            'iyah' => 'iya',
            'betul' => 'ya',
            'benar' => 'ya',
            'mantap' => 'oke',
            'nice' => 'oke',
            'lanjutkan' => 'lanjut',
            'lanjutin' => 'lanjut',
            'gajadi' => 'batal',
            'ga jadi' => 'batal',
            'gak jadi' => 'batal',
            'nggak jadi' => 'batal',
            'tidak jadi' => 'batal',
            'jangan' => 'batal',
            'stop' => 'batal',

            // pembayaran
            'blm' => 'belum',
            'belom' => 'belum',
            'blum' => 'belum',
            'lns' => 'lunas',
            'luna' => 'lunas',
            'byr' => 'bayar',
            'byaar' => 'bayar',
            'piutang' => 'belum bayar',

            // order / invoice
            'ordr' => 'order',
            'odr' => 'order',
            'oder' => 'order',
            'odrer' => 'order',
            'inv' => 'invoice',

            // waktu
            'hr ini' => 'hari ini',
            'tgl' => 'tanggal',

            // follow-up
            'followup' => 'follow up',
            'follow-up' => 'follow up',
            'folow up' => 'follow up',
            'foloup' => 'follow up',

            // income
            'omset' => 'omzet',
            'pendptn' => 'pendapatan',
        ];

        foreach ($map as $from => $to) {
            $text = preg_replace('/\b' . preg_quote($from, '/') . '\b/', $to, $text);
        }

        // Ubah angka singkat bahasa Indonesia ke integer.
        $text = preg_replace_callback('/(\d+)\s?jt\b/', function ($m) {
            return (string)((int)$m[1] * 1000000);
        }, $text);

        $text = preg_replace_callback('/(\d+)\s?rb\b/', function ($m) {
            return (string)((int)$m[1] * 1000);
        }, $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Parser konteks waktu sederhana.
     * Dipakai jika nantinya mau membedakan hari ini/bulan ini/tahun ini.
     */
    public function context($text)
    {
        return [
            'today' => $this->has($text, ['hari ini', 'today']),
            'month' => $this->has($text, ['bulan ini', 'bulan', 'month']),
            'year'  => $this->has($text, ['tahun ini', 'tahun', 'year']),
        ];
    }

    /**
     * Cek apakah teks mengandung minimal satu kata/frasa.
     */
    public function has($text, array $words)
    {
        foreach ($words as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $text)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Deteksi intent utama.
     * Urutan dibuat dari yang paling spesifik ke paling umum agar tidak tabrakan.
     */
    public function detect($t)
    {
        /* =========================
         * 1. SMALL TALK
         * ========================= */
        if ($this->has($t, ['halo', 'hai', 'hello', 'hey', 'hei'])) {
            return 'CHAT_HELLO';
        }

        if ($this->has($t, ['ok', 'oke', 'lanjut', 'ya', 'iya', 'sip', 'siap', 'mantap'])) {
            return 'CHAT_ACK';
        }

        if ($this->has($t, ['batal', 'cancel', 'stop'])) {
            return 'CHAT_CANCEL';
        }

        if ($this->has($t, ['lagi ngapain'])) {
            return 'CHAT_ACTIVITY';
        }

        if ($this->has($t, ['sibuk'])) {
            return 'CHAT_BUSY';
        }

        if ($this->has($t, ['makasih', 'terimakasih', 'terima kasih', 'thanks'])) {
            return 'CHAT_THANKS';
        }

        if ($this->has($t, ['kamu siapa', 'lo siapa', 'you siapa', 'siapa kamu'])) {
            return 'CHAT_IDENTITY';
        }

        if ($this->has($t, ['aku siapa'])) {
            return 'CHAT_USER_IDENTITY';
        }

        /* =========================
         * 2. HELP / CAPABILITY
         * ========================= */
        if (($this->has($t, ['apa']) && $this->has($t, ['bisa'])) || $this->has($t, ['fitur', 'bantuan', 'help', 'lakukan'])) {
            return 'AI_CAPABILITY';
        }

        /* =========================
         * 3. AKSI BERISIKO
         * Ditaruh sebelum query umum agar "tandai lunas" tidak terbaca sebagai daftar lunas.
         * ========================= */
        if ($this->has($t, ['tandai', 'mark', 'set', 'jadikan']) && $this->has($t, ['lunas'])) {
            return 'MARK_ORDER_PAID';
        }

        if ($this->has($t, ['hapus', 'delete']) && $this->has($t, ['client', 'klien'])) {
            return 'DELETE_CLIENT';
        }

        if ($this->has($t, ['hapus', 'delete'])) {
            return 'DELETE_ORDER';
        }

        if ($this->has($t, ['ubah', 'update', 'ganti']) && $this->has($t, ['harga'])) {
            return 'UPDATE_PRICE';
        }

        if ($this->has($t, ['nonaktifkan', 'disable']) && $this->has($t, ['desain', 'design'])) {
            return 'DISABLE_DESIGN';
        }

        /* =========================
         * 4. ANALISIS BISNIS LANJUTAN
         * ========================= */
        if (($this->has($t, ['bandingkan', 'compare', 'perbandingan']) && $this->has($t, ['omzet', 'income', 'pendapatan', 'pemasukan', 'revenue']))) {
            return 'ANALYSIS_COMPARE_OMZET';
        }

        if (($this->has($t, ['kenapa', 'mengapa']) && $this->has($t, ['turun'])) || $this->has($t, ['income turun', 'omzet turun', 'pendapatan turun'])) {
            return 'ANALYSIS_WHY_DOWN';
        }

        if (($this->has($t, ['prioritas']) && $this->has($t, ['follow up'])) || ($this->has($t, ['siapa']) && $this->has($t, ['follow up']))) {
            return 'ANALYSIS_PRIORITY_FOLLOWUP';
        }

        if (($this->has($t, ['macet']) && $this->has($t, ['order'])) || ($this->has($t, ['terlalu lama', 'lama diproses']) && $this->has($t, ['order']))) {
            return 'ANALYSIS_STUCK_ORDERS';
        }

        if (($this->has($t, ['prediksi', 'forecast', 'perkiraan']) && $this->has($t, ['income', 'omzet', 'pendapatan', 'pemasukan']))) {
            return 'ANALYSIS_PREDICT_INCOME';
        }

        if (($this->has($t, ['rekomendasi', 'saran']) && $this->has($t, ['harga'])) || ($this->has($t, ['harga']) && $this->has($t, ['murah']))) {
            return 'ANALYSIS_PRICE_RECOMMENDATION';
        }

        if ($this->has($t, ['ringkasan harian', 'laporan harian']) || ($this->has($t, ['ringkasan']) && $this->has($t, ['hari ini']))) {
            return 'ANALYSIS_DAILY_OWNER';
        }

        if ($this->has($t, ['laporan bulanan']) || ($this->has($t, ['buat laporan']) && $this->has($t, ['bulan']))) {
            return 'ANALYSIS_MONTHLY_REPORT';
        }

        if (($this->has($t, ['kurang laku', 'jarang']) && $this->has($t, ['desain', 'design']))) {
            return 'ANALYSIS_UNDERPERFORMING_DESIGN';
        }

        /* =========================
         * 4. DASHBOARD & LAPORAN
         * ========================= */
        if ($this->has($t, ['ringkasan', 'laporan', 'statistik', 'performa'])) {
            return 'DASH_SUMMARY_MONTH';
        }

        if ($this->has($t, ['income', 'pemasukan', 'omzet', 'revenue', 'pendapatan']) || ($this->has($t, ['total']) && $this->has($t, ['bulan']))) {
            return 'DASH_TOTAL_INCOME';
        }

        if ($this->has($t, ['hari ini', 'today']) && $this->has($t, ['order', 'invoice', 'transaksi'])) {
            return 'DASH_ORDER_TODAY';
        }

        if ($this->has($t, ['belum', 'nunggak', 'hutang']) && $this->has($t, ['lunas', 'bayar', 'pembayaran', 'order', 'invoice'])) {
            return 'DASH_UNPAID_LIST';
        }

        if ($this->has($t, ['mahal', 'terbesar', 'tertinggi'])) {
            return 'DASH_TOP_ORDER';
        }

        if ($this->has($t, ['total', 'jumlah']) && $this->has($t, ['order', 'invoice'])) {
            return 'DASH_TOTAL_ORDER_ALL';
        }

        if ($this->has($t, ['order', 'invoice']) && $this->has($t, ['lunas'])) {
            return 'DASH_ORDER_PAID';
        }

        if ($this->has($t, ['proses', 'aktif', 'dikerjakan', 'belum selesai'])) {
            return 'DASH_ORDER_PROCESS';
        }

        /* =========================
         * 5. INSIGHT DATA
         * ========================= */
        if ($this->has($t, ['body', 'bagian']) && $this->has($t, ['sering', 'terbanyak', 'paling'])) {
            return 'INSIGHT_TOP_BODY_PART';
        }

        if ($this->has($t, ['desain', 'design']) && $this->has($t, ['sering', 'terlaris', 'banyak', 'paling'])) {
            return 'INSIGHT_TOP_DESIGN';
        }

        if ($this->has($t, ['klien', 'client']) && $this->has($t, ['sering', 'terbanyak', 'paling', 'aktif'])) {
            return 'INSIGHT_TOP_CLIENT';
        }

        if ($this->has($t, ['telat', 'terlambat', 'tertunda', 'lama', 'overdue']) && $this->has($t, ['bayar', 'pembayaran', 'lunas'])) {
            return 'INSIGHT_LATE_PAYMENT';
        }

        /* =========================
         * 6. DRAFT PESAN
         * ========================= */
        if ($this->has($t, ['follow up'])) {
            return 'DRAFT_FOLLOW_UP_PAYMENT';
        }

        if ($this->has($t, ['dp', 'down payment'])) {
            return 'DRAFT_DP_REMINDER';
        }

        if ($this->has($t, ['selesai']) && $this->has($t, ['order'])) {
            return 'DRAFT_ORDER_DONE';
        }

        if ($this->has($t, ['penagihan', 'billing', 'tagihan'])) {
            return 'DRAFT_SOFT_BILLING';
        }

        /* =========================
         * 7. SIMULASI SEDERHANA
         * ========================= */
        if ($this->has($t, ['harga']) && $this->has($t, ['naik'])) {
            return 'SIM_PRICE_UP';
        }

        if ($this->has($t, ['target'])) {
            return 'SIM_TARGET_CHECK';
        }

        return 'UNKNOWN';
    }
}
