<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Invoices extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Order_model', 'orders');
    }

    public function print_invoice($order_id)
    {
        $order = $this->orders->detail($order_id);
        if (!$order) show_404();

        $this->orders->ensure_invoice($order_id);
        $inv = $this->orders->invoice($order_id);

        $items = $this->orders->items($order_id);

        $data = [
            'title' => 'Preview Nota',
            'order' => $order,
            'invoice' => $inv,
            'items' => $items,
            'page_css' => ['pages/invoice-print.css'],
            'page_js' => ['invoice-print.js'],
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('invoices/print', $data);
        $this->load->view('layout/footer');
    }

    public function download_jpg($order_id)
    {
        $order = $this->orders->detail($order_id);
        if (!$order) show_404();

        $this->orders->ensure_invoice($order_id);
        $invoice = $this->orders->invoice($order_id);
        $items = $this->orders->items($order_id);

        $filename = $this->nota_filename($order) . '.jpg';

        if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
            log_message('error', 'Download JPG nota gagal: ekstensi PHP GD belum aktif. Order ID: ' . (int)$order_id);
            show_error('Ekstensi PHP GD belum aktif. Gunakan tombol Download JPG di halaman preview nota karena sudah tersedia fallback browser, atau aktifkan extension=gd di php.ini.', 500);
            return;
        }

        $binary = $this->build_invoice_jpg($order, $invoice, $items);
        if ($binary === '' || $binary === false) {
            show_error('Gagal membuat file JPG nota. Silakan ulangi atau gunakan fallback browser di halaman preview nota.', 500);
            return;
        }

        $encodedFilename = rawurlencode($filename);

        $this->output
            ->set_header('Content-Type: image/jpeg')
            ->set_header('Content-Transfer-Encoding: binary')
            ->set_header('Content-Length: ' . strlen($binary))
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . $encodedFilename)
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_header('Pragma: no-cache')
            ->set_output($binary);
    }

    private function nota_filename($order)
    {
        $client = $this->clean_filename_part($order->client_name ?? 'Klien');
        $title = $this->clean_filename_part($order->title ?? ($order->order_code ?? 'Order'));
        return 'Nota-' . $client . '-' . $title;
    }

    private function clean_filename_part($value)
    {
        $value = trim((string)$value);
        if ($value === '') return 'Tanpa-Nama';
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) $value = $converted;
        }
        $value = preg_replace('/[^A-Za-z0-9\s_-]+/', '', $value);
        $value = preg_replace('/\s+/', '-', $value);
        $value = preg_replace('/-+/', '-', $value);
        $value = trim($value, '-_ ');
        return $value !== '' ? $value : 'Tanpa-Nama';
    }

    private function build_invoice_jpg($order, $invoice, $items)
    {
        $w = 1400;
        $baseH = 980;
        $extraRows = max(0, count($items) - 3) * 58;
        $h = $baseH + $extraRows;

        $img = imagecreatetruecolor($w, $h);
        imageantialias($img, true);

        $c = [
            'bg' => imagecolorallocate($img, 11, 11, 11),
            'panel' => imagecolorallocate($img, 17, 17, 16),
            'panel2' => imagecolorallocate($img, 24, 21, 17),
            'line' => imagecolorallocate($img, 54, 43, 25),
            'lineSoft' => imagecolorallocate($img, 38, 38, 38),
            'gold' => imagecolorallocate($img, 222, 179, 72),
            'gold2' => imagecolorallocate($img, 242, 210, 117),
            'white' => imagecolorallocate($img, 248, 248, 248),
            'muted' => imagecolorallocate($img, 178, 178, 178),
            'black' => imagecolorallocate($img, 9, 9, 9),
        ];

        imagefilledrectangle($img, 0, 0, $w, $h, $c['bg']);
        for ($i = 0; $i < 420; $i++) {
            $alpha = 120 - (int)min(120, $i / 3);
            $col = imagecolorallocatealpha($img, 202, 162, 74, $alpha);
            imageellipse($img, 160, 60, $i * 3, $i * 2, $col);
        }

        $fontBold = $this->find_font(true);
        $fontRegular = $this->find_font(false);

        $this->rounded_rect($img, 86, 62, 1314, $h - 62, 28, $c['panel'], $c['line']);

        $y = 100;
        $logoPath = FCPATH . 'assets/img/logo-ady.png';
        if (is_file($logoPath)) {
            $this->paste_logo($img, $logoPath, 130, $y + 2, 145, 75);
        }

        $this->text($img, 'Ady_vandorez', 315, $y + 28, 28, $c['gold'], $fontBold);
        $this->text($img, 'Nota Pembayaran Vektor Portrait Art', 315, $y + 64, 15, $c['white'], $fontRegular);
        $this->text($img, 'IG. Ady_vandorez', 315, $y + 102, 14, $c['muted'], $fontRegular);
        $this->text($img, 'WA. 08999986783', 315, $y + 128, 14, $c['muted'], $fontRegular);
        $this->text($img, 'Probolinggo, Kota Probolinggo, Jawa Timur, Indonesia', 315, $y + 154, 14, $c['muted'], $fontRegular);

        $this->rounded_rect($img, 1130, $y + 4, 1245, $y + 40, 18, $c['panel2'], $c['gold']);
        $this->text($img, strtoupper((string)$order->status), 1158, $y + 28, 14, $c['gold2'], $fontBold);
        $this->text($img, (string)$invoice->invoice_no, 1038, $y + 88, 28, $c['white'], $fontBold);
        $this->text($img, 'Tanggal: ' . date('d/m/Y', strtotime($invoice->created_at)), 1090, $y + 126, 15, $c['muted'], $fontRegular);
        $this->text($img, 'Order: ' . (string)$order->order_code, 1082, $y + 152, 15, $c['muted'], $fontRegular);

        imageline($img, 126, 288, 1274, 288, $c['lineSoft']);

        // Info cards
        $this->rounded_rect($img, 126, 318, 675, 430, 18, $c['panel2'], $c['lineSoft']);
        $this->text($img, 'KLIEN', 150, 352, 14, $c['gold'], $fontBold);
        $this->text($img, (string)$order->client_name, 150, 388, 22, $c['white'], $fontBold);
        $this->text($img, (string)($order->client_phone ?? ''), 150, 416, 15, $c['muted'], $fontRegular);

        $this->rounded_rect($img, 725, 318, 1274, 430, 18, $c['panel2'], $c['lineSoft']);
        $this->text($img, 'JUDUL PEKERJAAN', 750, 352, 14, $c['gold'], $fontBold);
        $this->text($img, (string)$order->title, 750, 388, 22, $c['white'], $fontBold);
        $this->text($img, trim((string)$order->design_name . ' • ' . (string)$order->body_name), 750, 416, 15, $c['muted'], $fontRegular);

        // Table
        $tableY = 470;
        $rowH = 58;
        $rows = count($items) + 4;
        $tableH = 46 + ($rows * $rowH);
        $this->rounded_rect($img, 126, $tableY, 1274, $tableY + $tableH, 18, $c['panel'], $c['lineSoft']);
        imagefilledrectangle($img, 128, $tableY + 2, 1272, $tableY + 46, $c['panel2']);
        $this->text($img, 'DESKRIPSI', 150, $tableY + 32, 15, $c['gold'], $fontBold);
        $this->text($img, 'BIAYA', 1178, $tableY + 32, 15, $c['gold'], $fontBold);

        $cy = $tableY + 46;
        foreach ($items as $it) {
            imageline($img, 126, $cy, 1274, $cy, $c['lineSoft']);
            $desc = trim((string)$it->design_name . ' - ' . (string)$it->body_name);
            $this->text($img, $desc, 150, $cy + 24, 16, $c['white'], $fontBold);
            $this->text($img, 'Qty: ' . (int)$it->qty . ' × ' . rupiah($it->price), 150, $cy + 47, 13, $c['muted'], $fontRegular);
            $this->text_right($img, rupiah($it->price * $it->qty), 1248, $cy + 32, 16, $c['white'], $fontBold);
            $cy += $rowH;
        }
        $fixedRows = [
            ['Add-ons', rupiah($order->addons)],
            ['Biaya Revisi', rupiah($order->revision_fee)],
            ['Diskon', '-' . rupiah($order->discount)],
        ];
        foreach ($fixedRows as $r) {
            imageline($img, 126, $cy, 1274, $cy, $c['lineSoft']);
            $this->text($img, $r[0], 150, $cy + 35, 16, $c['white'], $fontRegular);
            $this->text_right($img, $r[1], 1248, $cy + 35, 16, $c['white'], $fontRegular);
            $cy += $rowH;
        }
        imageline($img, 126, $cy, 1274, $cy, $c['lineSoft']);
        imagefilledrectangle($img, 128, $cy + 1, 1272, $cy + $rowH - 1, $c['panel2']);
        $this->text($img, 'TOTAL', 644, $cy + 35, 16, $c['gold'], $fontBold);
        $this->text_right($img, rupiah($order->total), 1248, $cy + 35, 18, $c['gold2'], $fontBold);

        $sectionY = $tableY + $tableH + 28;
        $this->rounded_rect($img, 126, $sectionY, 705, $sectionY + 145, 18, $c['panel'], $c['lineSoft']);
        $this->text($img, 'KETERANGAN', 150, $sectionY + 38, 16, $c['gold'], $fontBold);
        $this->text($img, 'File Yang Sudah Di Kirim Tidak Bisa Revisi', 150, $sectionY + 78, 15, $c['muted'], $fontRegular);
        $this->text($img, 'Kecuali Kesalahan Desainer.', 150, $sectionY + 106, 15, $c['muted'], $fontRegular);

        $this->rounded_rect($img, 725, $sectionY, 1274, $sectionY + 145, 18, $c['panel2'], $c['line']);
        $this->summary_row($img, 'Total', rupiah($order->total), 755, $sectionY + 40, 1248, $c, $fontRegular, $fontBold);
        $this->summary_row($img, 'Paid', rupiah($order->paid), 755, $sectionY + 80, 1248, $c, $fontRegular, $fontBold);
        $this->text($img, 'Sisa', 755, $sectionY + 124, 22, $c['gold'], $fontBold);
        $this->text_right($img, rupiah(max(0, $order->total - $order->paid)), 1248, $sectionY + 124, 28, $c['gold2'], $fontBold);

        $this->text($img, 'Ady_vandorez • Vektor Portrait Artist', 126, $h - 95, 14, $c['muted'], $fontRegular);
        $this->text_right($img, 'Terima kasih', 1274, $h - 95, 14, $c['muted'], $fontRegular);

        ob_start();
        imagejpeg($img, null, 92);
        $binary = ob_get_clean();
        imagedestroy($img);
        return $binary;
    }

    private function summary_row($img, $label, $value, $x, $y, $rightX, $c, $fontRegular, $fontBold)
    {
        $this->text($img, $label, $x, $y, 17, $c['muted'], $fontRegular);
        $this->text_right($img, $value, $rightX, $y, 18, $c['white'], $fontBold);
    }

    private function find_font($bold = false)
    {
        $candidates = $bold ? [
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/seguisb.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf',
        ] : [
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/segoeui.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
        ];

        foreach ($candidates as $font) {
            if (is_file($font)) return $font;
        }
        return null;
    }

    private function text($img, $text, $x, $y, $size, $color, $font = null)
    {
        $text = (string)$text;
        if ($font && function_exists('imagettftext')) {
            imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
        } else {
            imagestring($img, min(5, max(1, (int)round($size / 5))), $x, $y - $size, $text, $color);
        }
    }

    private function text_right($img, $text, $rightX, $y, $size, $color, $font = null)
    {
        $text = (string)$text;
        if ($font && function_exists('imagettftext')) {
            $box = imagettfbbox($size, 0, $font, $text);
            $w = abs($box[4] - $box[0]);
            imagettftext($img, $size, 0, $rightX - $w, $y, $color, $font, $text);
        } else {
            $fontId = min(5, max(1, (int)round($size / 5)));
            $w = imagefontwidth($fontId) * strlen($text);
            imagestring($img, $fontId, $rightX - $w, $y - $size, $text, $color);
        }
    }

    private function rounded_rect($img, $x1, $y1, $x2, $y2, $r, $fill, $stroke = null)
    {
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $fill);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $fill);
        imagefilledellipse($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $fill);
        imagefilledellipse($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $fill);
        imagefilledellipse($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $fill);
        imagefilledellipse($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $fill);
        if ($stroke !== null) {
            imagerectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $stroke);
            imagerectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $stroke);
            imagearc($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, 180, 270, $stroke);
            imagearc($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, 270, 360, $stroke);
            imagearc($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, 90, 180, $stroke);
            imagearc($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, 0, 90, $stroke);
        }
    }

    private function paste_logo($img, $path, $x, $y, $maxW, $maxH)
    {
        $raw = @file_get_contents($path);
        if ($raw === false) return;
        $logo = @imagecreatefromstring($raw);
        if (!$logo) return;
        $sw = imagesx($logo);
        $sh = imagesy($logo);
        if ($sw <= 0 || $sh <= 0) return;
        $scale = min($maxW / $sw, $maxH / $sh);
        $dw = max(1, (int)round($sw * $scale));
        $dh = max(1, (int)round($sh * $scale));
        imagecopyresampled($img, $logo, $x, $y, 0, 0, $dw, $dh, $sw, $sh);
        imagedestroy($logo);
    }
}
