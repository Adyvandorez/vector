<div class="card admin-page">
    <div class="card-header admin-page-head">
        <div><h1><?= html_escape($row->name); ?></h1><div class="small">Detail pelanggan dan seluruh riwayat pesanannya.</div></div>
        <div class="admin-actions"><a class="btn" href="<?= base_url('clients/edit/' . $row->id); ?>">Edit</a><a class="btn" href="<?= base_url('orders/create?client_id=' . $row->id); ?>">Buat Order</a><a class="btn" href="<?= base_url('clients'); ?>">Kembali</a></div>
    </div>
    <div class="card-body">
        <?php if ($this->session->flashdata('clients_ok')): ?><div class="badge u-mb-12 flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('clients_ok')); ?></div><?php endif; ?>
        <div class="admin-stat-grid">
            <div class="card"><div class="small">Total Order</div><div class="admin-stat-value"><?= (int)$row->total_orders; ?></div></div>
            <div class="card"><div class="small">Nilai Order</div><div class="admin-stat-value"><?= rupiah((int)$row->total_value); ?></div></div>
            <div class="card"><div class="small">Sudah Dibayar</div><div class="admin-stat-value"><?= rupiah((int)$row->total_paid); ?></div></div>
            <div class="card"><div class="small">Sisa Tagihan</div><div class="admin-stat-value"><?= rupiah(max(0, (int)$row->total_value - (int)$row->total_paid)); ?></div></div>
        </div>
        <div class="admin-detail-grid u-mt-16">
            <div><div class="small">WhatsApp</div><b><?= html_escape($row->phone ?: '-'); ?></b></div>
            <div><div class="small">Email</div><b><?= html_escape($row->email ?: '-'); ?></b></div>
            <div><div class="small">Jenis Akun</div><b><?= !empty($row->password_hash) ? 'Pelanggan terdaftar' : 'Pelanggan manual'; ?></b></div>
            <div><div class="small">Status</div><span class="badge <?= (int)$row->is_active ? '' : 'u-badge-danger'; ?>"><?= (int)$row->is_active ? 'AKTIF' : 'NONAKTIF'; ?></span></div>
            <div class="admin-detail-wide"><div class="small">Alamat</div><div><?= nl2br(html_escape($row->address ?: '-')); ?></div></div>
            <div class="admin-detail-wide"><div class="small">Catatan</div><div><?= nl2br(html_escape($row->notes ?: '-')); ?></div></div>
        </div>
        <hr class="sep">
        <h2>Riwayat Order</h2>
        <div class="table-wrap"><table class="table admin-table"><thead><tr><th>Kode</th><th>Judul</th><th>Total</th><th>Dibayar</th><th>Status</th><th>Tanggal</th></tr></thead><tbody>
        <?php if (empty($orders)): ?><tr><td colspan="6" class="small">Pelanggan belum memiliki order.</td></tr><?php else: foreach ($orders as $o): ?>
            <tr><td><a href="<?= base_url('orders/view/' . $o->id); ?>"><b><?= html_escape($o->order_code); ?></b></a></td><td><?= html_escape($o->title); ?></td><td><?= rupiah($o->total); ?></td><td><?= rupiah($o->paid); ?></td><td><span class="badge"><?= html_escape($o->status === 'LUNAS' ? 'SELESAI' : $o->status); ?></span></td><td><?= html_escape($o->created_at); ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody></table></div>
    </div>
</div>
