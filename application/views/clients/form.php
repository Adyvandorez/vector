<div class="card admin-page admin-form-card">
    <div class="card-header admin-page-head">
        <div><h1><?= $row ? 'Edit' : 'Tambah'; ?> Pelanggan</h1><div class="small">Email dan password hanya wajib untuk pelanggan yang akan memakai aplikasi Android.</div></div>
        <a class="btn" href="<?= base_url('clients'); ?>">Kembali</a>
    </div>
    <div class="card-body">
        <?php if ($this->session->flashdata('clients_err')): ?>
            <div class="badge u-badge-danger u-mb-12 flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('clients_err')); ?></div>
        <?php endif; ?>
        <form method="post">
            <?= csrf_field(); ?>
            <div class="row">
                <div><div class="small">Nama Lengkap *</div><input class="input" name="name" required value="<?= html_escape($row->name ?? ''); ?>"></div>
                <div><div class="small">Nomor WhatsApp</div><input class="input" name="phone" inputmode="tel" value="<?= html_escape($row->phone ?? ''); ?>" placeholder="08xxxxxxxxxx"></div>
            </div>
            <div class="row u-mt-12">
                <div><div class="small">Email Login</div><input class="input" type="email" name="email" value="<?= html_escape($row->email ?? ''); ?>" placeholder="pelanggan@email.com"></div>
                <div><div class="small"><?= $row ? 'Password Baru (opsional)' : 'Password Android (opsional)'; ?></div><input class="input" type="password" name="password" minlength="8" autocomplete="new-password"><div class="small u-mt-6">Kosongkan bila pelanggan hanya memesan lewat WhatsApp/admin.</div></div>
            </div>
            <div class="u-mt-12"><div class="small">Alamat</div><textarea class="input admin-textarea" name="address"><?= html_escape($row->address ?? ''); ?></textarea></div>
            <div class="u-mt-12"><div class="small">Catatan Internal</div><textarea class="input admin-textarea" name="notes"><?= html_escape($row->notes ?? ''); ?></textarea></div>
            <label class="admin-check u-mt-12"><input type="checkbox" name="is_active" value="1" <?= !$row || (int)$row->is_active === 1 ? 'checked' : ''; ?>> Pelanggan aktif</label>
            <hr class="sep">
            <button class="btn btn-red" type="submit"><?= $row ? 'Simpan Perubahan' : 'Tambah Pelanggan'; ?></button>
        </form>
    </div>
</div>
