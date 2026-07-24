<div class="card admin-page admin-form-card">
    <div class="card-header admin-page-head"><div><h1>Profil Saya</h1><div class="small">Perbarui identitas login dan password akun yang sedang digunakan.</div></div></div>
    <div class="card-body">
        <?php if ($this->session->flashdata('profile_ok')): ?><div class="badge u-mb-12 flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('profile_ok')); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('profile_err')): ?><div class="badge u-badge-danger u-mb-12 flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('profile_err')); ?></div><?php endif; ?>
        <form method="post"><?= csrf_field(); ?>
            <div class="row"><div><div class="small">Nama</div><input class="input" name="name" required value="<?= html_escape($row->name); ?>"></div><div><div class="small">Role</div><input class="input" disabled value="<?= html_escape($row->role ?? 'ADMIN'); ?>"></div></div>
            <div class="row u-mt-12"><div><div class="small">Username</div><input class="input" name="username" required value="<?= html_escape($row->username); ?>"></div><div><div class="small">Email</div><input class="input" type="email" name="email" required value="<?= html_escape($row->email); ?>"></div></div>
            <hr class="sep"><h2>Ganti Password</h2><div class="small">Kosongkan kedua kolom berikut bila password tidak ingin diubah.</div>
            <div class="row u-mt-12"><div><div class="small">Password Saat Ini</div><input class="input" type="password" name="current_password" autocomplete="current-password"></div><div><div class="small">Password Baru</div><input class="input" type="password" name="new_password" minlength="8" autocomplete="new-password"></div></div>
            <hr class="sep"><button class="btn btn-red" type="submit">Simpan Profil</button>
        </form>
    </div>
</div>
