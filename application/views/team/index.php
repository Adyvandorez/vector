<div class="card admin-page">
    <div class="card-header admin-page-head">
        <div><h1>Tim Admin</h1><div class="small">Kelola akun pemilik, admin, dan staf yang dapat mengakses dashboard.</div></div>
        <a class="btn btn-gold" href="<?= base_url('team/create'); ?>">+ Tambah Tim</a>
    </div>
    <div class="card-body">
        <?php if ($this->session->flashdata('team_ok')): ?><div class="badge u-mb-12 flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('team_ok')); ?></div><?php endif; ?>
        <?php if ($this->session->flashdata('team_err')): ?><div class="badge u-badge-danger u-mb-12 flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('team_err')); ?></div><?php endif; ?>
        <div class="table-wrap"><table class="table admin-table"><thead><tr><th>Nama</th><th>Login</th><th>Role</th><th>Terakhir Login</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><b><?= html_escape($r->name); ?></b><?php if ((int)$r->id === current_user_id()): ?><div class="small">Akun saat ini</div><?php endif; ?></td>
                <td><?= html_escape($r->username); ?><div class="small"><?= html_escape($r->email); ?></div></td>
                <td><span class="badge"><?= html_escape($r->role ?? 'ADMIN'); ?></span></td>
                <td><?= html_escape($r->last_login ?: '-'); ?></td>
                <td><span class="badge <?= (int)$r->is_active ? '' : 'u-badge-danger'; ?>"><?= (int)$r->is_active ? 'AKTIF' : 'NONAKTIF'; ?></span></td>
                <td><div class="admin-actions"><a class="btn" href="<?= base_url('team/edit/' . $r->id); ?>">Edit</a><?php if ((int)$r->id !== current_user_id()): ?><form method="post" action="<?= base_url('team/toggle/' . $r->id); ?>" data-confirm="Ubah status akun tim ini?"><?= csrf_field(); ?><button class="btn <?= (int)$r->is_active ? 'btn-red' : 'btn-gold'; ?>" type="submit"><?= (int)$r->is_active ? 'Nonaktifkan' : 'Aktifkan'; ?></button></form><?php endif; ?></div></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>
</div>
