<div class="card drive-page drive-storage-page">
    <div class="card-header drive-page-header">
        <div>
            <h1>Google Drive Storage</h1>
            <div class="small">Kelola preview, thumbnail, CDR, dan backup Google Drive.</div>
        </div>
        <div class="drive-header-actions">
            <a class="btn drive-help-btn" href="<?= base_url('drive-storage/guide'); ?>" title="Panduan Google Drive Storage" aria-label="Panduan Google Drive Storage">?</a>
            <a class="btn mobile-back-btn" href="<?= base_url('dashboard'); ?>">Kembali</a>
        </div>
    </div>

    <div class="card-body drive-page-body">
        <div class="drive-intro small">
            Kelola integrasi Google Drive untuk menyimpan preview jenis desain dan order secara lebih rapi, ringan, dan aman.
            Preview baru akan dikompres ringan, dibuat thumbnail lokal kecil untuk mempercepat halaman, lalu file preview dan file master CDR dibackup ke Google Drive.
        </div>

        <?php if (!empty($last_result)): ?>
            <div class="badge <?= !empty($last_result['ok']) ? '' : 'u-badge-danger'; ?> u-mb-12 flash-toast js-flash-toast drive-flash">
                <?= html_escape($last_result['message'] ?? 'Proses selesai.'); ?>
            </div>
        <?php endif; ?>

        <hr class="sep">

        <div class="drive-stat-grid">
            <div class="card drive-stat-card">
                <div class="drive-stat-label small">Konfigurasi OAuth</div>
                <div class="drive-stat-value"><?= $drive_configured ? 'Siap' : 'Belum Lengkap'; ?></div>
                <?php if (!$drive_configured): ?>
                    <div class="drive-stat-note small u-danger"><?= html_escape($drive_error ?: 'Periksa oauth-client.json dan Folder ID.'); ?></div>
                <?php endif; ?>
            </div>

            <div class="card drive-stat-card">
                <div class="drive-stat-label small">Status Google Drive</div>
                <div class="drive-stat-value"><?= $drive_authorized ? 'Terhubung' : 'Belum Terhubung'; ?></div>
                <?php if ($drive_configured && !$drive_authorized): ?>
                    <div class="drive-stat-note small u-danger"><?= html_escape($drive_error ?: 'Klik Hubungkan Google Drive.'); ?></div>
                <?php endif; ?>
            </div>

            <div class="card drive-stat-card">
                <div class="drive-stat-label small">Database Token</div>
                <div class="drive-stat-value"><?= $has_token_table ? 'Tersedia' : 'Belum Ada'; ?></div>
                <?php if (!$has_token_table): ?>
                    <div class="drive-stat-note small u-danger">Import SQL final atau jalankan SQL patch.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="drive-stat-grid drive-stat-grid-secondary">
            <div class="card drive-stat-card">
                <div class="drive-stat-label small">Preview/Thumbnail Jenis Desain Pending</div>
                <div class="drive-stat-value"><?= (int)$design_pending; ?></div>
                <div class="drive-stat-note small">Belum Drive: <?= (int)$design_drive_pending; ?>, Belum Thumbnail: <?= (int)$design_thumb_pending; ?></div>
            </div>

            <div class="card drive-stat-card">
                <div class="drive-stat-label small">Preview/Thumbnail Order Pending</div>
                <div class="drive-stat-value"><?= (int)$order_pending; ?></div>
                <div class="drive-stat-note small">Belum Drive: <?= (int)$order_drive_pending; ?>, Belum Thumbnail: <?= (int)$order_thumb_pending; ?></div>
            </div>

            <div class="card drive-stat-card">
                <div class="drive-stat-label small">File Preview Lokal Besar Siap Dibersihkan</div>
                <div class="drive-stat-value"><?= (int)($design_cleanable + $order_cleanable); ?></div>
                <div class="drive-stat-note small">Desain: <?= (int)$design_cleanable; ?>, Order: <?= (int)$order_cleanable; ?></div>
            </div>
        </div>

        <hr class="sep">

        <?php if (!$has_drive_columns): ?>
            <div class="badge u-badge-danger drive-inline-alert">
                Kolom Google Drive belum tersedia di database. Import SQL final atau jalankan file SQL patch terlebih dahulu.
            </div>
        <?php else: ?>
            <div class="card drive-action-card u-mb-12">
                <div class="drive-action-head">
                    <div class="u-min-w-0">
                        <div class="u-bold">Aksi Google Drive</div>
                        <div class="small u-mt-6">Gunakan tombol di bawah untuk menghubungkan Drive, menyinkronkan file yang sudah ada, membuat thumbnail lokal, migrasi preview lama, atau membersihkan file preview lokal besar yang sudah aman.</div>
                    </div>
                    <div class="badge">Mode OAuth 2.0</div>
                </div>

                <div class="drive-action-list">
                    <?php if (!$drive_authorized): ?>
                        <a class="btn btn-red" href="<?= base_url('drive-storage/connect'); ?>">Hubungkan Google Drive</a>
                    <?php else: ?>
                        <form method="post" action="<?= base_url('drive-storage/sync-existing'); ?>" data-confirm="Sinkronkan data database dengan file yang sudah ada di Google Drive? Sistem tidak akan upload ulang file, hanya mencocokkan dan mengisi Drive ID jika ditemukan.">
                            <?= csrf_field(); ?>
                            <button class="btn drive-sync-btn" type="submit">Sinkronkan File Drive yang Sudah Ada</button>
                        </form>

                        <form method="post" action="<?= base_url('drive-storage/migrate'); ?>" data-confirm="Mulai migrasi preview lama ke Google Drive? Gunakan ini jika file belum ada di Drive. Proses berjalan per batch agar server tidak berat.">
                            <?= csrf_field(); ?>
                            <button class="btn btn-red" type="submit">Migrasikan Preview Lama ke Drive</button>
                        </form>

                        <form method="post" action="<?= base_url('drive-storage/cleanup-local'); ?>" data-confirm="Bersihkan file lokal yang sudah punya file Drive valid? File yang belum punya Drive ID tidak akan dihapus.">
                            <?= csrf_field(); ?>
                            <button class="btn" type="submit">Bersihkan File Lokal Aman</button>
                        </form>

                        <form method="post" action="<?= base_url('drive-storage/disconnect'); ?>" data-confirm="Putuskan koneksi Google Drive? File yang sudah terupload tidak akan dihapus.">
                            <?= csrf_field(); ?>
                            <button class="btn" type="submit">Putuskan Koneksi</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="small drive-action-note">
                    Pembersihan lokal hanya menghapus file preview besar yang sudah memiliki Drive ID valid. Thumbnail lokal kecil tetap disimpan agar halaman Jenis Desain, Harga Matrix, dan Order tetap cepat.
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($last_result['errors'])): ?>
            <hr class="sep drive-error-sep">
            <details class="card drive-errors-card">
                <summary>
                    <span>Catatan Error / Skip</span>
                    <span class="drive-error-count"><?= count($last_result['errors']); ?> catatan</span>
                </summary>
                <ul class="small drive-error-list">
                    <?php foreach ($last_result['errors'] as $err): ?>
                        <li><?= html_escape($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>
    </div>
</div>
