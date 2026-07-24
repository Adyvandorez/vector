<?php
$summary = isset($payment_summary) ? $payment_summary : [
    'total' => (int)$order->total,
    'paid' => (int)$order->paid,
    'remaining' => max(0, (int)$order->total - (int)$order->paid),
    'payment_status' => ((int)$order->paid >= (int)$order->total && (int)$order->total > 0) ? 'LUNAS' : ((int)$order->paid > 0 ? 'BELUM LUNAS' : 'BELUM BAYAR')
];
$sisa = (int)$summary['remaining'];
$is_lunas = ($sisa <= 0 && (int)$order->total > 0);
$payment_status = $summary['payment_status'];
$work_status = $order->status === 'LUNAS' ? 'SELESAI' : $order->status;
?>

<div class="card order-detail-page">

    <?php if ($this->session->flashdata('orders_err')): ?>
        <div class="badge u-badge-danger u-mb-12 flash-toast js-flash-toast">
            <?= html_escape($this->session->flashdata('orders_err')); ?>
        </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('orders_ok')): ?>
        <div class="badge u-mb-12 flash-toast js-flash-toast">
            <?= html_escape($this->session->flashdata('orders_ok')); ?>
        </div>
    <?php endif; ?>

    <div class="card-header">
        <div>
            <h1>Detail Order</h1>
            <div class="small">
                <?= htmlspecialchars($order->order_code); ?> • <?= htmlspecialchars($order->client_name); ?>
            </div>
        </div>
        <div class="actions">
            <a class="btn btn-gold" href="<?= base_url('invoices/print/' . $order->id); ?>">Preview Nota</a>
            <a class="btn" href="<?= base_url('orders/edit/' . $order->id); ?>">Edit</a>
            <a class="btn mobile-back-btn" href="<?= base_url('orders'); ?>">Kembali</a>
        </div>
    </div>

    <div class="card-body">

        <div class="row3 order-detail-top-cards">
            <div class="card order-info-card">
                <div class="small">Judul</div>
                <div class="u-bold u-mt-6"><?= htmlspecialchars($order->title); ?></div>
                <div class="small u-mt-4">
                    Deadline: <?= $order->deadline ? htmlspecialchars($order->deadline) : '-'; ?>
                </div>
            </div>

            <div class="card order-info-card">
                <div class="small">Ringkasan Pembayaran</div>
                <div class="order-main-value"><?= rupiah($order->total); ?></div>

                <div class="small u-mt-6">
                    Sudah Dibayar: <b><?= rupiah($order->paid); ?></b>
                </div>

                <div class="small u-mt-6">
                    Sisa Tagihan:
                    <b class="<?= $sisa > 0 ? 'u-danger' : ''; ?>">
                        <?= rupiah($sisa); ?>
                    </b>
                </div>

                <?php if ($payment_status === 'LUNAS'): ?>
                    <div class="badge order-status-badge">LUNAS</div>
                <?php elseif ($payment_status === 'BELUM BAYAR'): ?>
                    <div class="badge u-badge-danger order-status-badge">BELUM BAYAR</div>
                <?php else: ?>
                    <div class="badge u-badge-danger order-status-badge">BELUM LUNAS</div>
                <?php endif; ?>
            </div>

            <div class="card order-info-card">
                <div class="small">Status Pengerjaan</div>
                <div class="order-status-wrap">
                    <span class="badge"><?= htmlspecialchars($work_status); ?></span>
                </div>
                <div class="small u-mt-6">
                    Revisi: <?= (int)$order->revision_count; ?>x • Fee: <?= rupiah($order->revision_fee); ?>
                </div>
            </div>
        </div>

        <div class="admin-detail-grid u-mt-16">
            <div><div class="small">Sumber Order</div><span class="badge"><?= ($order->created_via ?? 'WEB_ADMIN') === 'ANDROID' ? 'ANDROID' : 'WEB ADMIN'; ?></span></div>
            <div><div class="small">WhatsApp</div><b><?= html_escape($order->client_phone ?: '-'); ?></b></div>
            <div><div class="small">Email</div><b><?= html_escape($order->client_email ?: '-'); ?></b></div>
            <div><div class="small">Dibuat</div><b><?= html_escape($order->created_at); ?></b></div>
            <div class="admin-detail-wide admin-note-box"><div class="small">Catatan Pelanggan</div><div><?= nl2br(html_escape($order->customer_notes ?: '-')); ?></div></div>
            <div class="admin-detail-wide admin-note-box"><div class="small">Catatan Internal Admin</div><div><?= nl2br(html_escape($order->admin_notes ?: '-')); ?></div></div>
        </div>

        <hr class="sep">

        <b>Item Desain</b>
        <div class="small u-mt-4">
            Subtotal item: <b><?= rupiah($order->base_price); ?></b>
        </div>

        <table class="table order-item-table">
            <thead>
                <tr>
                    <th>Jenis</th>
                    <th>Bagian</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $it): ?>
                        <?php
                            $item_preview_thumb = vi_design_preview_url($it);
                            $item_preview_full = vi_design_preview_full_url($it) ?: $item_preview_thumb;
                        ?>
                        <tr>
                            <td data-label="Jenis">
                                <div class="order-item-design">
                                    <?php if ($item_preview_thumb): ?>
                                        <a class="vi-image-trigger order-item-design__preview"
                                           href="<?= html_escape($item_preview_full); ?>"
                                           data-image-lightbox="<?= html_escape($item_preview_full); ?>"
                                           data-image-title="<?= html_escape($it->design_name); ?>"
                                           aria-label="Buka preview <?= html_escape($it->design_name); ?>">
                                            <img src="<?= html_escape($item_preview_thumb); ?>"
                                                 alt="Preview <?= html_escape($it->design_name); ?>"
                                                 loading="lazy">
                                        </a>
                                        <button type="button" class="btn btn-gold vi-image-trigger-btn" data-image-lightbox="<?= html_escape($item_preview_full); ?>" data-image-title="<?= html_escape($it->design_name); ?>">Lihat</button>
                                    <?php else: ?>
                                        <div class="order-item-design__empty" aria-hidden="true">No Img</div>
                                    <?php endif; ?>
                                    <div class="order-item-design__text">
                                        <b><?= htmlspecialchars($it->design_name); ?></b>
                                        <?php if (!empty($it->note)): ?>
                                            <div class="small"><?= htmlspecialchars($it->note); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Bagian"><span class="badge"><?= htmlspecialchars($it->body_name); ?></span></td>
                            <td data-label="Qty"><?= (int)$it->qty; ?></td>
                            <td data-label="Harga"><?= rupiah($it->price); ?></td>
                            <td data-label="Subtotal"><b><?= rupiah($it->price * $it->qty); ?></b></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="small">Belum ada item. Silakan edit order dan tambah item.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="row3 order-summary-grid">
            <div class="card order-mini-card">
                <div class="small">Add-ons</div>
                <div class="u-bold u-mt-6"><?= rupiah($order->addons); ?></div>
            </div>
            <div class="card order-mini-card">
                <div class="small">Diskon</div>
                <div class="u-bold u-mt-6"><?= rupiah($order->discount); ?></div>
            </div>
            <div class="card order-mini-card">
                <div class="small">Biaya Revisi</div>
                <div class="u-bold u-mt-6"><?= rupiah($order->revision_fee); ?></div>
            </div>
        </div>

        <hr class="sep">

        <div class="row">
            <div class="card order-info-card">
                <div class="order-section-head">
                    <div>
                        <b>Tambah Pembayaran</b>
                        <div class="small">Catat cicilan/DP/pelunasan tanpa menghitung manual.</div>
                    </div>
                    <span class="badge <?= $sisa > 0 ? 'u-badge-danger' : ''; ?>"><?= htmlspecialchars($payment_status); ?></span>
                </div>

                <?php if ($sisa > 0): ?>
                    <form method="post" action="<?= base_url('orders/add_payment/' . $order->id); ?>" class="revision-form">
                        <?= csrf_field(); ?>
                        <div class="small">Nominal Pembayaran</div>
                        <input class="input js-money" type="text" inputmode="numeric" name="amount" data-empty placeholder="contoh: 500.000" required>

                        <div class="small u-mt-10">Tanggal Pembayaran</div>
                        <input class="input vi-date-input" type="date" name="payment_date" value="<?= date('Y-m-d'); ?>" required>

                        <div class="small u-mt-10">Catatan</div>
                        <input class="input" name="note" placeholder="misal: cicilan kedua / transfer / cash / pelunasan">

                        <div class="u-mt-10">
                            <button class="btn btn-red" type="submit">Simpan Pembayaran</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="small">Order ini sudah lunas. Pembayaran tambahan tidak diperlukan.</div>
                <?php endif; ?>
            </div>

            <div class="card order-info-card">
                <b>Riwayat Pembayaran</b>
                <div class="small u-mt-4">
                    Total sudah dibayar: <b><?= rupiah($order->paid); ?></b> • Sisa: <b class="<?= $sisa > 0 ? 'u-danger' : ''; ?>"><?= rupiah($sisa); ?></b>
                </div>

                <div class="revision-list u-mt-10">
                    <?php foreach ($payments as $pay): ?>
                        <div class="card revision-card">
                            <div class="small revision-head">
                                <div>
                                    <?= htmlspecialchars($pay->payment_date); ?> • <b><?= rupiah($pay->amount); ?></b>
                                </div>
                                <form method="post" action="<?= base_url('orders/delete_payment/' . $pay->id); ?>" class="u-inline-form" data-confirm="Hapus pembayaran ini? Total dibayar akan dihitung ulang.">
                                    <?= csrf_field(); ?>
                                    <button type="submit" class="btn btn-red">Hapus</button>
                                </form>
                            </div>
                            <div class="revision-note">
                                <?= htmlspecialchars($pay->note ?: '-'); ?>
                                <div class="small">Sumber: <?= htmlspecialchars($pay->source ?? 'MANUAL'); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($payments) === 0): ?>
                        <div class="small">Belum ada riwayat pembayaran.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <hr class="sep">

        <div class="row">
            <!-- Upload Preview + Manage Preview -->
            <div class="card order-info-card">
                <div class="order-section-head">
                    <div>
                        <b>Upload Preview (JPG/PNG/WEBP)</b>
                        <div class="small">Maksimal 8MB, otomatis dikompres saat upload.</div>
                    </div>

                    <div class="actions">
                        <button class="btn btn-gold" type="button" id="btnEditPreview">Edit</button>

                        <form id="formDeletePreview"
                            method="post"
                            action="<?= base_url('orders/delete_previews/' . $order->id); ?>"
                            class="u-form-inline-zero">
                            <?= csrf_field(); ?>
                            <button class="btn btn-red" type="submit" id="btnDeletePreview" disabled>
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data"
                    action="<?= base_url('orders/upload_preview/' . $order->id); ?>"
                    class="order-upload-form">
                    <?= csrf_field(); ?>
                    <input class="input"
                        type="file"
                        name="previews[]"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        required>

                    <div class="u-mt-10">
                        <button class="btn btn-red" type="submit">Upload</button>
                    </div>
                </form>

                <hr class="sep">

                <div class="order-section-head">
                    <b>Preview Files</b>
                    <div class="small preview-hint" id="previewHint">
                        Centang preview yang mau dihapus
                    </div>
                </div>

                <div class="order-preview-grid" id="previewGrid">
                    <?php
                    $has_preview = false;
                    foreach ($files as $f) {
                        if ($f->file_type === 'PREVIEW') {
                            $has_preview = true;
                            break;
                        }
                    }
                    ?>

                    <?php if ($has_preview): ?>
                        <?php foreach ($files as $f): ?>
                            <?php if ($f->file_type === 'PREVIEW'): ?>
                                <div class="card preview-card">
                                    <label class="preview-check">
                                        <input type="checkbox"
                                            class="preview-checkbox"
                                            form="formDeletePreview"
                                            name="file_ids[]"
                                            value="<?= (int)$f->id; ?>">
                                    </label>

                                    <a class="card preview-link"
                                        target="_blank"
                                        href="<?= html_escape(vi_order_file_url($f)); ?>">
                                        <img class="preview-image" src="<?= html_escape(vi_order_file_thumb_url($f)); ?>" alt="Preview" loading="lazy">
                                    </a>

                                    <div class="small preview-name">
                                        <?= htmlspecialchars($f->original_name); ?>
                                        <div class="small">Storage: <?= html_escape(vi_storage_badge($f->storage ?? 'local')); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="small">Belum ada preview.</div>
                    <?php endif; ?>
                </div>
            </div>


            <!-- Revisi -->
            <div class="card order-info-card">
                <b>Tambah Revisi</b>
                <form method="post" action="<?= base_url('orders/add_revision/' . $order->id); ?>" class="revision-form">
                    <?= csrf_field(); ?>
                    <div class="small">Catatan revisi</div>
                    <textarea class="input" name="note" rows="4"
                        placeholder="misal: ganti warna background, tambah detail rambut..." required></textarea>

                    <div class="small u-mt-10">Biaya revisi (jika ada)</div>
                    <input class="input js-money" type="text" inputmode="numeric" name="fee" value="0">

                    <div class="u-mt-10">
                        <button class="btn btn-red" type="submit">Simpan Revisi</button>
                    </div>
                </form>

                <hr class="sep">

                <b>Riwayat Revisi</b>
                <div class="revision-list">
                    <?php foreach ($revisions as $rv): ?>
                        <div class="card revision-card">
                            <div class="small revision-head">
                                <div>
                                    <?= htmlspecialchars($rv->created_at); ?> • <?= html_escape($rv->source ?? 'ADMIN'); ?> • Fee: <?= rupiah($rv->fee); ?>
                                </div>
                                <div class="actions">
                                    <a class="btn btn-gold" href="<?= base_url('orders/revision_edit/' . $rv->id); ?>">Edit</a>
                                    <form method="post" action="<?= base_url('orders/revision_delete/' . $rv->id); ?>" class="u-inline-form" data-confirm="Hapus revisi ini?">
                                        <?= csrf_field(); ?>
                                        <button type="submit" class="btn btn-red">Hapus</button>
                                    </form>
                                </div>
                            </div>
                            <div class="revision-note"><?= nl2br(htmlspecialchars($rv->note)); ?></div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($revisions) === 0): ?>
                        <div class="small">Belum ada revisi.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <hr class="sep">
        <div class="card order-info-card">
            <b>Riwayat Status</b>
            <div class="small u-mt-4">Perjalanan status pesanan dicatat otomatis untuk kebutuhan web dan aplikasi pelanggan.</div>
            <div class="admin-timeline u-mt-12">
                <?php if (empty($status_histories)): ?>
                    <div class="small">Belum ada riwayat status.</div>
                <?php else: foreach ($status_histories as $history): ?>
                    <div class="admin-timeline-item">
                        <div class="small"><?= html_escape($history->created_at); ?></div>
                        <div>
                            <b><?= html_escape(($history->old_status ?: 'AWAL') . ' → ' . $history->new_status); ?></b>
                            <div class="small"><?= html_escape($history->changed_by_type); ?><?= !empty($history->note) ? ' • ' . html_escape($history->note) : ''; ?></div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

    </div>
</div>
