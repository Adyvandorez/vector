<div class="card admin-page admin-form-card">
    <div class="card-header admin-page-head"><div><h1><?= $row ? 'Edit' : 'Tambah'; ?> Tim</h1><div class="small">Gunakan role sesuai tanggung jawab pengguna.</div></div><a class="btn" href="<?= base_url('team'); ?>">Kembali</a></div>
    <div class="card-body">
        <?php if ($this->session->flashdata('team_err')): ?><div class="badge u-badge-danger u-mb-12 flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('team_err')); ?></div><?php endif; ?>
        <form method="post"><?= csrf_field(); ?>
            <div class="row"><div><div class="small">Nama *</div><input class="input" name="name" required value="<?= html_escape($row->name ?? ''); ?>"></div><div><div class="small">Username *</div><input class="input" name="username" required value="<?= html_escape($row->username ?? ''); ?>"></div></div>
            <div class="row u-mt-12"><div><div class="small">Email *</div><input class="input" type="email" name="email" required value="<?= html_escape($row->email ?? ''); ?>"></div><div><div class="small">Role</div><select class="input" name="role"><?php foreach (['OWNER','ADMIN','STAFF'] as $role): ?><option value="<?= $role; ?>" <?= ($row->role ?? 'STAFF') === $role ? 'selected' : ''; ?>><?= $role; ?></option><?php endforeach; ?></select></div></div>
            <div class="u-mt-12"><div class="small"><?= $row ? 'Password Baru (opsional)' : 'Password *'; ?></div><input class="input" type="password" name="password" minlength="8" <?= $row ? '' : 'required'; ?> autocomplete="new-password"></div>
            <label class="admin-check u-mt-12"><input type="checkbox" name="is_active" value="1" <?= !$row || (int)$row->is_active === 1 ? 'checked' : ''; ?>> Akun aktif</label>
            <hr class="sep"><button class="btn btn-red" type="submit">Simpan Akun</button>
        </form>
    </div>
</div>
