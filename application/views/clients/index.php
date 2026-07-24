<div class="card admin-page">
    <div class="card-header admin-page-head">
        <div>
            <h1>Pelanggan</h1>
            <div class="small">Kelola pelanggan WhatsApp, pelanggan web, dan akun yang nantinya digunakan di Android.</div>
        </div>
        <a class="btn btn-gold" href="<?= base_url('clients/create'); ?>">+ Tambah Pelanggan</a>
    </div>
    <div class="card-body">
        <?php if ($this->session->flashdata('clients_ok')): ?>
            <div class="badge u-mb-12 flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('clients_ok')); ?></div>
        <?php endif; ?>

        <form method="get" class="admin-filter-grid">
            <input class="input" name="q" value="<?= html_escape($q); ?>" placeholder="Cari nama, nomor WhatsApp, atau email">
            <select class="input" name="active">
                <option value="">Semua status</option>
                <option value="1" <?= (string)$active === '1' ? 'selected' : ''; ?>>Aktif</option>
                <option value="0" <?= (string)$active === '0' ? 'selected' : ''; ?>>Nonaktif</option>
            </select>
            <button class="btn" type="submit">Terapkan</button>
            <a class="btn" href="<?= base_url('clients'); ?>">Reset</a>
        </form>

        <div class="table-wrap">
            <table class="table admin-table">
                <thead><tr><th>Pelanggan</th><th>Kontak</th><th>Akun</th><th>Order</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="small">Belum ada pelanggan yang sesuai.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td data-label="Pelanggan"><b><?= html_escape($r->name); ?></b><div class="small">ID #<?= (int)$r->id; ?></div></td>
                        <td data-label="Kontak"><?= html_escape($r->phone ?: '-'); ?><div class="small"><?= html_escape($r->email ?: 'Belum memiliki email'); ?></div></td>
                        <td data-label="Akun"><span class="badge <?= !empty($r->password_hash) ? '' : 'u-badge-danger'; ?>"><?= !empty($r->password_hash) ? 'SIAP LOGIN' : 'MANUAL'; ?></span></td>
                        <td data-label="Order"><b><?= (int)$r->total_orders; ?></b><div class="small"><?= rupiah((int)$r->total_value); ?></div></td>
                        <td data-label="Status"><span class="badge <?= (int)$r->is_active ? '' : 'u-badge-danger'; ?>"><?= (int)$r->is_active ? 'AKTIF' : 'NONAKTIF'; ?></span></td>
                        <td data-label="Aksi">
                            <div class="admin-actions">
                                <a class="btn" href="<?= base_url('clients/view/' . $r->id); ?>">Detail</a>
                                <a class="btn" href="<?= base_url('clients/edit/' . $r->id); ?>">Edit</a>
                                <form method="post" action="<?= base_url('clients/toggle/' . $r->id); ?>" data-confirm="Hapus pelanggan ini? Data order tetap tersimpan sebagai histori?">
                                    <?= csrf_field(); ?>
                                    <button class="btn btn-red" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
