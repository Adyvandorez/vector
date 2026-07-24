
<div class="card designs-page">
    <div class="card-header">
        <div class="topbar u-w-full">
            <input class="input search mobile-search-compact js-enter-search"
                data-search-url="<?= base_url('designs'); ?>"
                placeholder="Cari jenis desain dari awalan huruf..."
                value="<?= htmlspecialchars($q ?? ''); ?>">

            <div class="u-spacer-260"></div>
            <a class="btn btn-red" href="<?= base_url('designs/create'); ?>">+ Tambah Jenis</a>
        </div>
    </div>

    <div class="card-body">

        <?php if ($this->session->flashdata('designs_ok')): ?>
            <div class="badge u-mb-12 flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('designs_ok')); ?></div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('designs_err')): ?>
            <div class="badge u-badge-danger u-mb-12 flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('designs_err')); ?></div>
        <?php endif; ?>

        <!-- DESKTOP TABLE (AMAN) -->
        <table class="table design-desktop-table">
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Nama</th>
                    <th>Status</th>
                    <th>File Master</th>
                    <th class="u-actions-col-220">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) === 0): ?>
                    <tr><td colspan="5" class="small">Data tidak ditemukan. Coba kata kunci awalan lain.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <?php
                        $preview_thumb = vi_design_preview_url($r);
                        $preview_full = vi_design_preview_full_url($r) ?: $preview_thumb;
                    ?>
                    <tr>
                        <td class="u-thumb-cell">
                            <?php if ($preview_thumb): ?>
                                <a class="vi-image-trigger design-preview-trigger"
                                   href="<?= html_escape($preview_full); ?>"
                                   data-image-lightbox="<?= html_escape($preview_full); ?>"
                                   data-image-title="<?= html_escape($r->name); ?>"
                                   aria-label="Buka preview <?= html_escape($r->name); ?>">
                                    <img src="<?= html_escape($preview_thumb); ?>"
                                        alt="Preview <?= html_escape($r->name); ?>"
                                        class="u-thumb-58" loading="lazy">
                                </a>
                            <?php else: ?>
                                <span class="badge">No Img</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="vi-title-stack">
                                <b><?= htmlspecialchars($r->name); ?></b>
                                <?php if (!empty($r->preview_image) || !empty($r->preview_drive_id)): ?>
                                    <span class="small vi-storage-note"><?= html_escape(vi_design_storage_status($r)); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= $r->is_active ? '<span class="badge status-badge is-active">Aktif</span>' : '<span class="badge status-badge is-inactive">Nonaktif</span>'; ?></td>
                        <td>
                            <?php if (!empty($r->source_drive_id)): ?>
                                <div class="vi-master-file">
                                    <a class="badge vi-master-badge" href="<?= html_escape(vi_drive_public_url($r->source_drive_id)); ?>" target="_blank">Drive • CDR</a>
                                    <div class="small vi-master-name"><?= html_escape($r->source_original_name ?: 'File master'); ?></div>
                                </div>
                            <?php else: ?>
                                <span class="small">Belum ada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-gold" href="<?= base_url('designs/edit/' . $r->id); ?>">Edit</a>
                                <form method="post" action="<?= base_url('designs/delete/' . $r->id); ?>" class="u-inline-form" data-confirm="Hapus desain ini dari daftar?">
                                    <?= csrf_field(); ?>
                                    <button type="submit" class="btn btn-red">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ================= DESIGN MOBILE LIST ================= -->
        <div class="design-mobile-list mobile-only">
            <?php if (count($rows) === 0): ?>
                <div class="design-mobile-empty small">Data jenis desain tidak ditemukan.</div>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <?php
                    $preview_thumb = vi_design_preview_url($r);
                    $preview_full = vi_design_preview_full_url($r) ?: $preview_thumb;
                ?>
                <div class="dm-card">

                    <!-- IMAGE -->
                    <div class="dm-thumb-wrap">
                        <?php if ($preview_thumb): ?>
                            <a class="vi-image-trigger dm-preview-trigger"
                               href="<?= html_escape($preview_full); ?>"
                               data-image-lightbox="<?= html_escape($preview_full); ?>"
                               data-image-title="<?= html_escape($r->name); ?>"
                               aria-label="Buka preview <?= html_escape($r->name); ?>">
                                <img src="<?= html_escape($preview_thumb); ?>" alt="Preview <?= html_escape($r->name); ?>" loading="lazy">
                            </a>
                        <?php else: ?>
                            <div class="dm-noimg">No Img</div>
                        <?php endif; ?>

                        <!-- STATUS -->
                        <span class="dm-status <?= $r->is_active ? 'active' : 'inactive'; ?>">
                            <?= $r->is_active ? 'Aktif' : 'Nonaktif'; ?>
                        </span>
                    </div>

                    <!-- TITLE -->
                    <div class="dm-title">
                        <?= htmlspecialchars($r->name); ?>
                    </div>

                    <?php if (!empty($r->preview_image) || !empty($r->preview_drive_id)): ?>
                        <div class="small vi-storage-note"><?= html_escape(vi_design_storage_status($r)); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($r->source_drive_id)): ?>
                        <div class="vi-master-file vi-master-file--mobile">
                            <a class="badge vi-master-badge" href="<?= html_escape(vi_drive_public_url($r->source_drive_id)); ?>" target="_blank">Drive • CDR</a>
                            <div class="small vi-master-name"><?= html_escape($r->source_original_name ?: 'File master'); ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- ACTIONS -->
                    <div class="dm-actions">

                        <!-- EDIT (PANJANG) -->
                        <a href="<?= base_url('designs/edit/' . $r->id); ?>"
                            class="dm-btn dm-edit">
                            <svg viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                            </svg>
                            <span>Edit</span>
                        </a>

                        <!-- DELETE (KOTAK) -->
                        <form method="post" action="<?= base_url('designs/delete/' . $r->id); ?>" class="u-icon-form" data-confirm="Hapus desain ini dari daftar?">
                            <?= csrf_field(); ?>
                            <button type="submit" class="dm-btn dm-delete" aria-label="Hapus">
                            <svg viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1"
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