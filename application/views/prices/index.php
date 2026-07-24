<div class="card prices-page">
    <div class="card-header">
        <div class="topbar u-w-full">
            <input class="input search mobile-search-compact js-enter-search"
                data-search-url="<?= base_url('prices'); ?>"
                placeholder="Cari jenis desain / bagian dari awalan huruf..."
                value="<?= htmlspecialchars($q ?? ''); ?>">

            <div class="u-spacer-260"></div>
            <a class="btn btn-red" href="<?= base_url('prices/create'); ?>">+ Tambah Harga</a>
        </div>
    </div>

    <div class="card-body">
        <div class="small">
            Isi harga untuk kombinasi Jenis Desain × Bagian (Close Up/Half/Full/Lainnya).
        </div>

        <hr class="sep">
        <table class="table price-table">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Jenis</th>
                    <th>Bagian</th>
                    <th>Harga</th>
                    <th class="u-actions-col-220">Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($rows) === 0): ?>
                    <tr><td colspan="5" class="small">Data harga tidak ditemukan. Coba kata kunci awalan lain.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="u-thumb-cell">
                            <?php if (!empty($r->preview_image) || !empty($r->preview_drive_id)): ?>
                                <a class="vi-image-trigger" href="<?= html_escape(vi_design_preview_url($r)); ?>" data-image-lightbox="<?= html_escape(vi_design_preview_url($r)); ?>" data-image-title="<?= html_escape($r->design_name); ?>">
                                    <img
                                    src="<?= html_escape(vi_design_preview_url($r)); ?>"
                                    class="u-thumb-58-bordered" loading="lazy">
                                </a>
                            <?php else: ?>
                                <div class="badge">No Img</div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <div class="vi-title-stack">
                                <b><?= htmlspecialchars($r->design_name); ?></b>
                                <?php if (!empty($r->preview_image) || !empty($r->preview_drive_id)): ?>
                                    <span class="small vi-storage-note"><?= html_escape(vi_design_storage_status($r)); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><span class="badge"><?= htmlspecialchars($r->body_name); ?></span></td>
                        <td><b><?= rupiah($r->base_price); ?></b></td>

                        <td>
                            <div class="actions">
                                <button type="button" class="btn btn-gold vi-image-trigger-btn" data-image-lightbox="<?= html_escape(vi_design_preview_url($r)); ?>" data-image-title="<?= html_escape($r->design_name); ?>">Lihat</button>
                                <a class="btn btn-gold" href="<?= base_url('prices/edit/' . $r->id); ?>">Edit</a>
                                <form method="post" action="<?= base_url('prices/delete/' . $r->id); ?>" class="u-inline-form" data-confirm="Hapus data harga?">
                                    <?= csrf_field(); ?>
                                    <button type="submit" class="btn btn-red">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- DESKTOP -->


        <!-- ================= MOBILE PRICE MATRIX ================= -->
        <div class="price-mobile-list mobile-only">
            <?php if (count($rows) === 0): ?>
                <div class="price-mobile-empty small">Data harga tidak ditemukan.</div>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <div class="pm-card">

                    <!-- LEFT -->
                    <div class="pm-left">
                        <div class="pm-thumb">
                            <?php if (!empty($r->preview_image) || !empty($r->preview_drive_id)): ?>
                                <img src="<?= html_escape(vi_design_preview_url($r)); ?>" loading="lazy">
                            <?php else: ?>
                                <span>No Img</span>
                            <?php endif; ?>
                        </div>

                        <div class="pm-info">
                            <div class="pm-title">
                                <?= htmlspecialchars($r->design_name); ?>
                            </div>

                            <?php if (!empty($r->preview_image) || !empty($r->preview_drive_id)): ?>
                                <div class="small vi-storage-note"><?= html_escape(vi_design_storage_status($r)); ?></div>
                            <?php endif; ?>

                            <span class="pm-badge">
                                <?= htmlspecialchars($r->body_name); ?>
                            </span>

                            <div class="pm-price">
                                <?= rupiah($r->base_price); ?>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT ACTIONS -->
                    <div class="pm-actions">
                        <a href="<?= base_url('prices/edit/' . $r->id); ?>"
                            class="pm-action pm-edit"
                            aria-label="Edit">
                            <!-- square-pen -->
                            <svg viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                            </svg>
                        </a>

                        <form method="post" action="<?= base_url('prices/delete/' . $r->id); ?>" class="u-icon-form" data-confirm="Hapus data harga?">
                            <?= csrf_field(); ?>
                            <button type="submit" class="pm-action pm-delete" aria-label="Hapus harga">
                            <!-- trash-2 -->
                            <svg viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6h18" />
                                <path d="M8 6V4h8v2" />
                                <path d="M19 6l-1 14H6L5 6" />
                                <path d="M10 11v6" />
                                <path d="M14 11v6" />
                            </svg>
                        </button>
                        </form>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>


    </div>
</div>