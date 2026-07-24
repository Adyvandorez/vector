<div class="card price-form-page">
    <div class="card-header">
        <h1><?= $row ? 'Edit' : 'Tambah'; ?> Harga</h1>
        <a class="btn mobile-back-btn" href="<?= base_url('prices'); ?>">Kembali</a>
    </div>
    <div class="card-body">
        <?php if ($this->session->flashdata('err')): ?>
            <div class="badge u-badge-danger u-mb-12 flash-toast js-flash-toast">
                <?= html_escape($this->session->flashdata('err')); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field(); ?>
            <div class="row3">
                <div>
                    <div class="small">Jenis Desain</div>
                    <select id="design_type_id" class="vi-custom-select" name="design_type_id" required>
                        <option value="">-- pilih --</option>
                        <?php foreach ($designs as $d): ?>
                            <option value="<?= $d->id; ?>" <?= ($row && $row->design_type_id == $d->id) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($d->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <div class="small">Bagian</div>
                    <select id="body_part_id" class="vi-custom-select" name="body_part_id" required>
                        <option value="">-- pilih --</option>
                        <?php foreach ($parts as $p): ?>
                            <option value="<?= $p->id; ?>" <?= ($row && $row->body_part_id == $p->id) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($p->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <div class="small">Harga Dasar</div>
                    <input id="base_price" class="input js-money" type="text" inputmode="numeric" name="base_price" value="<?= $row ? number_format((int)$row->base_price, 0, ',', '.') : '0'; ?>" required>
                </div>
            </div>
            <hr class="sep">
            <button class="btn btn-red" type="submit">Simpan</button>
        </form>
        <div class="small u-mt-10">
            * Jika kombinasi sudah ada, sistem akan update (upsert) otomatis.
        </div>
    </div>
</div>
