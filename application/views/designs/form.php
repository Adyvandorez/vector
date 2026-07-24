<div class="card">
    <div class="card-header">
        <h1><?= $row ? 'Edit' : 'Tambah'; ?> Jenis Desain</h1>
        <a class="btn mobile-back-btn" href="<?= base_url('designs'); ?>">Kembali</a>
    </div>

    <div class="card-body">

        <?php if ($this->session->flashdata('designs_err')): ?>
            <div class="badge u-badge-danger u-mb-12 flash-toast js-flash-toast">
                <?= html_escape($this->session->flashdata('designs_err')); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <?= csrf_field(); ?>
            <div class="row">
                <div>
                    <div class="small">Nama</div>
                    <input class="input" name="name" value="<?= $row ? htmlspecialchars($row->name) : ''; ?>" required>
                </div>
                <div>
                    <div class="small">Aktif</div>
                    <label class="badge u-clickable">
                        <input type="checkbox" name="is_active" value="1" <?= (!$row || $row->is_active) ? 'checked' : ''; ?>>
                        tampilkan
                    </label>
                </div>
            </div>

            <div class="u-mt-12">
                <div class="small">Deskripsi Layanan</div>
                <textarea class="input admin-textarea" name="description" placeholder="Jelaskan hasil desain, ketentuan, atau informasi yang nantinya dapat ditampilkan ke pelanggan."><?= html_escape($row->description ?? ''); ?></textarea>
            </div>

            <hr class="sep">

            <div class="row">
                <div>
                    <div class="small">Foto Preview (JPG/PNG/WEBP)</div>
                    <input class="input" type="file" name="preview" accept="image/jpeg,image/png,image/webp">
                    <div class="small u-mt-8">
                        Gambar akan dikompres ringan untuk lokal, dibuat thumbnail kecil, lalu dibackup ke Google Drive.
                        Kosongkan jika tidak ingin mengganti preview.
                    </div>
                </div>

                <div>
                    <div class="small">Preview Saat Ini</div>
                    <?php if ($row && (!empty($row->preview_image) || !empty($row->preview_drive_id) || !empty($row->preview_thumb))): ?>
                        <img src="<?= html_escape(vi_design_preview_url($row)); ?>"
                            class="u-thumb-120" loading="lazy">
                        <div class="small u-mt-8">Storage: <?= html_escape(vi_storage_badge($row->preview_storage ?? 'local')); ?></div>
                    <?php else: ?>
                        <div class="small">Belum ada gambar.</div>
                    <?php endif; ?>
                </div>
            </div>

            <hr class="sep">

            <div class="row">
                <div>
                    <div class="small">File Master / Source CDR</div>
                    <input class="input" type="file" name="source_file" accept=".cdr">
                    <div class="small u-mt-8">
                        File CDR tidak disimpan di lokal. File master langsung dibackup ke Google Drive agar hosting tetap ringan.
                    </div>
                </div>

                <div>
                    <div class="small">File Master Saat Ini</div>
                    <?php if ($row && !empty($row->source_drive_id)): ?>
                        <div class="card u-card-pad-14">
                            <div class="u-bold"><?= html_escape($row->source_original_name ?: $row->source_file_name); ?></div>
                            <div class="small u-mt-6">
                                Drive • CDR<?= !empty($row->source_size) ? ' • ' . vi_human_size($row->source_size) : ''; ?>
                            </div>
                            <div class="u-mt-10 vi-file-actions">
                                <a class="btn" href="<?= html_escape(vi_drive_public_url($row->source_drive_id)); ?>" target="_blank">Buka di Drive</a>
                                <button
                                    class="btn btn-red"
                                    type="submit"
                                    formaction="<?= base_url('designs/delete-source/' . $row->id); ?>"
                                    formmethod="post"
                                    data-confirm="Hapus file master dari Google Drive? File ini akan benar-benar dihapus.">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="small">Belum ada file master CDR.</div>
                    <?php endif; ?>
                </div>
            </div>

            <hr class="sep">
            <button class="btn btn-red" type="submit">Simpan</button>
        </form>
    </div>
</div>
