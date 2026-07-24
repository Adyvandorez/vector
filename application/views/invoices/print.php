<?php
$clean_filename = function ($value) {
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
};

$nota_filename = 'Nota-' . $clean_filename($order->client_name ?? 'Klien') . '-' . $clean_filename($order->title ?? $order->order_code ?? 'Order');

$invoice_export_items = [];
foreach ($items as $it) {
    $invoice_export_items[] = [
        'description' => trim((string)($it->design_name ?? '') . ' - ' . (string)($it->body_name ?? '')),
        'note' => (string)($it->note ?? ''),
        'qty_price' => 'Qty: ' . (int)($it->qty ?? 0) . ' × ' . rupiah(($it->price ?? 0)),
        'amount' => rupiah(($it->price ?? 0) * ($it->qty ?? 0)),
    ];
}

$invoice_export = [
    'filename' => $nota_filename,
    'logo_url' => base_url('assets/img/logo-ady.png'),
    'brand' => 'Ady_vandorez',
    'subtitle' => 'Nota Pembayaran Vektor Portrait Art',
    'contacts' => ['IG. Ady_vandorez', 'WA. 08999986783', 'Probolinggo, Kota Probolinggo, Jawa Timur, Indonesia'],
    'status' => (string)($order->status ?? ''),
    'invoice_no' => (string)($invoice->invoice_no ?? ''),
    'date_label' => 'Tanggal: ' . date('d/m/Y', strtotime($invoice->created_at ?? 'now')),
    'order_label' => 'Order: ' . (string)($order->order_code ?? ''),
    'client_name' => (string)($order->client_name ?? ''),
    'client_phone' => (string)($order->client_phone ?? ''),
    'title' => (string)($order->title ?? ''),
    'design_body' => trim((string)($order->design_name ?? '') . ' • ' . (string)($order->body_name ?? '')),
    'items' => $invoice_export_items,
    'addons' => rupiah($order->addons ?? 0),
    'revision_fee' => rupiah($order->revision_fee ?? 0),
    'discount' => '-' . rupiah($order->discount ?? 0),
    'total' => rupiah($order->total ?? 0),
    'paid' => rupiah($order->paid ?? 0),
    'remaining' => rupiah(max(0, ($order->total ?? 0) - ($order->paid ?? 0))),
    'note' => "File Yang Sudah Di Kirim Tidak Bisa Revisi\nKecuali Kesalahan Desainer.",
    'footer_left' => 'Ady_vandorez • Vektor Portrait Artist',
    'footer_right' => 'Terima kasih 🙏',
];

$render_invoice_paper = function ($paper_id = '', $extra_class = '') use ($order, $invoice, $items, $nota_filename) {
    ob_start();
?>
    <div
        <?php if ($paper_id !== ''): ?>id="<?= html_escape($paper_id); ?>"<?php endif; ?>
        class="paper invoice-paper <?= html_escape($extra_class); ?>"
        data-filename="<?= html_escape($nota_filename); ?>">

        <div class="head">
            <div class="invoice-head-row">
                <div class="invoice-brand-block">
                    <img class="logo" src="<?= base_url('assets/img/logo-ady.png'); ?>" alt="Ady Vandorez Logo">

                    <div class="brandname">Ady_vandorez</div>
                    <div class="subtitle">Nota Pembayaran Vektor Portrait Art</div>

                    <div class="meta invoice-contact">
                        <span>IG. Ady_vandorez</span>
                        <span>WA. 08999986783</span>
                        <span>Probolinggo, Kota Probolinggo, Jawa Timur, Indonesia</span>
                    </div>
                </div>

                <div class="invoice-number-block">
                    <div class="badge invoice-status-badge"><?= html_escape($order->status); ?></div>
                    <div class="invno"><?= html_escape($invoice->invoice_no); ?></div>
                    <div class="meta">
                        Tanggal: <?= date('d/m/Y', strtotime($invoice->created_at)); ?><br>
                        Order: <?= html_escape($order->order_code); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="grid2">
                <div class="box info-box">
                    <div class="label">Klien</div>
                    <div class="value"><?= html_escape($order->client_name); ?></div>
                    <div class="muted invoice-muted-top"><?= html_escape($order->client_phone ?? ''); ?></div>
                </div>

                <div class="box info-box">
                    <div class="label">Judul Pekerjaan</div>
                    <div class="value"><?= html_escape($order->title); ?></div>
                    <div class="muted invoice-muted-top">
                        <?= html_escape($order->design_name); ?> • <?= html_escape($order->body_name); ?>
                    </div>
                </div>
            </div>

            <div class="invoice-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Deskripsi</th>
                            <th class="t-right">Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td>
                                    <div class="line-main"><?= html_escape($it->design_name); ?> - <?= html_escape($it->body_name); ?></div>
                                    <?php if (!empty($it->note)): ?><div class="muted"><?= html_escape($it->note); ?></div><?php endif; ?>
                                    <div class="muted">Qty: <?= (int)$it->qty; ?> × <?= rupiah($it->price); ?></div>
                                </td>
                                <td class="amount-cell"><?= rupiah($it->price * $it->qty); ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <tr>
                            <td>Add-ons</td>
                            <td class="amount-cell"><?= rupiah($order->addons); ?></td>
                        </tr>
                        <tr>
                            <td>Biaya Revisi</td>
                            <td class="amount-cell"><?= rupiah($order->revision_fee); ?></td>
                        </tr>
                        <tr>
                            <td>Diskon</td>
                            <td class="amount-cell">-<?= rupiah($order->discount); ?></td>
                        </tr>
                        <tr class="row-total">
                            <td class="amount-cell"><span class="invoice-total-label">TOTAL</span></td>
                            <td class="amount-cell"><?= rupiah($order->total); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="totals">
                <div class="note">
                    <div class="strong invoice-note-title">Keterangan</div>
                    File Yang Sudah Di Kirim Tidak Bisa Revisi<br>
                    Kecuali Kesalahan Desainer.
                </div>

                <div class="sum">
                    <div class="sumrow">
                        <div class="muted">Total</div>
                        <div class="total"><?= rupiah($order->total); ?></div>
                    </div>
                    <div class="sumrow">
                        <div class="muted">Paid</div>
                        <div class="strong"><?= rupiah($order->paid); ?></div>
                    </div>
                    <div class="sumrow sumrow-remaining">
                        <div class="muted">Sisa</div>
                        <div class="strong"><?= rupiah(max(0, $order->total - $order->paid)); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="foot">
            <div>Ady_vandorez • Vektor Portrait Artist</div>
            <div class="muted">Terima kasih 🙏</div>
        </div>
    </div>
<?php
    return ob_get_clean();
};
?>

<div class="card invoice-preview-page">
    <div class="card-header invoice-page-header no-export">
        <div class="invoice-toolbar-main">
            <div class="invoice-toolbar-icon" aria-hidden="true">▤</div>
            <div class="invoice-toolbar-text">
                <h1>Preview Nota</h1>
                <div class="small invoice-toolbar-sub"><?= html_escape($invoice->invoice_no); ?> • <?= html_escape($order->order_code); ?></div>
            </div>
        </div>
        <div class="invoice-toolbar-actions invoice-toolbar-actions-back-only">
            <a class="invoice-action invoice-back-action" href="<?= base_url('orders/view/' . $order->id); ?>">Kembali</a>
        </div>
    </div>

    <div class="card-body invoice-page-body">
        <div class="invoice-format-note no-export">
            Download PDF dan JPG otomatis memakai nama klien pada file nota. Jika ingin cetak fisik, buka file PDF yang sudah terunduh lalu pilih Print.
        </div>

        <div class="invoice-preview-grid">
            <section class="invoice-preview-card invoice-preview-card-pdf">
                <div class="invoice-card-head no-export">
                    <button type="button" class="invoice-big-action js-invoice-download-pdf" data-filename="<?= html_escape($nota_filename); ?>">Download PDF</button>
                </div>
                <div class="invoice-card-divider no-export"></div>
                <div class="invoice-paper-scroll" aria-label="Scroll preview nota PDF">
                    <?= $render_invoice_paper('invoicePaper', 'invoice-paper-pdf'); ?>
                </div>
            </section>

            <section class="invoice-preview-card invoice-preview-card-jpg">
                <div class="invoice-card-head no-export">
                    <button type="button" class="invoice-big-action js-invoice-download-jpg" data-filename="<?= html_escape($nota_filename); ?>">Download JPG</button>
                </div>
                <div class="invoice-card-divider no-export"></div>
                <div class="invoice-paper-scroll" aria-label="Scroll preview nota JPG">
                    <?= $render_invoice_paper('invoicePaperJpg', 'invoice-paper-jpg'); ?>
                </div>
            </section>
        </div>
    </div>
</div>

<script id="invoiceExportData" type="application/json"><?= json_encode($invoice_export, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
</div>
