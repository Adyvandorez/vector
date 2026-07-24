<div class="card">
    <div class="card-header">
        <h1><?= $row ? 'Edit' : 'Tambah'; ?> Order</h1>
        <a class="btn mobile-back-btn" href="<?= base_url('orders'); ?>">Kembali</a>
    </div>

    <div class="card-body">

        <?php if ($this->session->flashdata('orders_err')): ?>
            <div class="badge u-badge-danger u-mb-12 flash-toast js-flash-toast">
                <?= html_escape($this->session->flashdata('orders_err')); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field(); ?>

            <div class="card admin-note-box">
                <div class="small">Pilih Pelanggan yang Sudah Ada</div>
                <select class="input" name="client_id">
                    <option value="0">-- Buat pelanggan baru dari data di bawah --</option>
                    <?php foreach (($clients ?? []) as $c): ?>
                        <option value="<?= (int)$c->id; ?>" <?= (int)($selected_client_id ?? 0) === (int)$c->id ? 'selected' : ''; ?>>
                            <?= html_escape($c->name . ($c->phone ? ' • ' . $c->phone : '') . ($c->email ? ' • ' . $c->email : '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="small u-mt-6">Bila pelanggan belum tersedia, biarkan pilihan di atas kosong lalu isi data pelanggan baru.</div>
            </div>

            <div class="row u-mt-12">
                <div>
                    <div class="small">Nama Pelanggan Baru</div>
                    <input class="input" name="client_name" value="<?= $row && !empty($client) ? htmlspecialchars($client->name) : ''; ?>" placeholder="Diisi hanya untuk pelanggan baru">
                </div>
                <div>
                    <div class="small">Nomor WhatsApp</div>
                    <input class="input" name="client_phone" value="<?= $row && !empty($client) ? htmlspecialchars($client->phone) : ''; ?>" placeholder="08xxxxxxxxxx">
                </div>
            </div>
            <div class="u-mt-12">
                <div class="small">Email Pelanggan Baru (opsional)</div>
                <input class="input" type="email" name="client_email" value="<?= $row && !empty($client) ? htmlspecialchars($client->email ?? '') : ''; ?>" placeholder="Digunakan bila pelanggan akan memiliki akun aplikasi">
            </div>
            <hr class="sep">

            <div class="row">
                <div>
                    <div class="small">Judul Pekerjaan</div>
                    <input class="input" name="title" value="<?= $row ? htmlspecialchars($row->title) : ''; ?>" required>
                </div>
                <div>
                    <div class="small">Deadline</div>
                    <input class="input vi-date-input" type="date" name="deadline" value="<?= $row ? htmlspecialchars($row->deadline) : ''; ?>">
                </div>
            </div>

            <div class="row u-mt-12">
                <div>
                    <div class="small">Catatan Pelanggan</div>
                    <textarea class="input admin-textarea" name="customer_notes" placeholder="Brief, permintaan, warna, ukuran, atau detail yang dapat dilihat pelanggan"><?= html_escape($row->customer_notes ?? ''); ?></textarea>
                </div>
                <div>
                    <div class="small">Catatan Internal Admin</div>
                    <textarea class="input admin-textarea" name="admin_notes" placeholder="Catatan kerja internal yang tidak ditampilkan kepada pelanggan"><?= html_escape($row->admin_notes ?? ''); ?></textarea>
                </div>
            </div>

            <div class="card order-items-card">
                <div class="u-flex-between">
                    <b>Item Desain</b>
                    <button class="btn btn-gold" type="button" id="addItemBtn">+ Tambah Item</button>
                </div>
                <div class="small u-mt-6">Satu order bisa berisi banyak jenis desain.</div>

                <div id="itemsWrap" class="order-items-wrap"></div>
            </div>

            <hr class="sep">

            <div class="row3">
                <div>
                    <div class="small">Add-ons</div>
                    <input class="input js-money" type="text" inputmode="numeric" name="addons" value="<?= $row ? number_format((int)$row->addons, 0, ',', '.') : '0'; ?>">
                </div>
                <div>
                    <div class="small">Diskon</div>
                    <input class="input js-money" type="text" inputmode="numeric" name="discount" value="<?= $row ? number_format((int)$row->discount, 0, ',', '.') : '0'; ?>">
                </div>
                <div>
                    <?php if ($row): ?>
                        <div class="small">Total Sudah Dibayar</div>
                        <input class="input" disabled value="<?= number_format((int)$row->paid, 0, ',', '.'); ?>">
                        <div class="small u-mt-6">Pembayaran tambahan dicatat dari halaman detail order.</div>
                    <?php else: ?>
                        <div class="small">Pembayaran Awal / DP</div>
                        <input class="input js-money" type="text" inputmode="numeric" name="paid" value="0">
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div>
                    <div class="small">Status Pengerjaan</div>
                    <?php $st = $row ? ($row->status === 'LUNAS' ? 'SELESAI' : $row->status) : 'MASUK'; ?>
                    <select class="vi-custom-select" name="status">
                        <?php foreach (['MASUK', 'PROSES', 'REVISI', 'SELESAI'] as $s): ?>
                            <option value="<?= $s; ?>" <?= ($st === $s) ? 'selected' : ''; ?>><?= $s; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="small u-mt-6">Status lunas dihitung otomatis dari pembayaran, bukan dari status kerja.</div>
                    <?php if ($row): ?>
                        <div class="small u-mt-10">Catatan Perubahan Status (opsional)</div>
                        <input class="input" name="status_note" placeholder="Contoh: proses sketsa dimulai">
                    <?php endif; ?>
                </div>
                <div>
                    <div class="small">Total (otomatis setelah simpan)</div>
                    <input class="input" disabled value="<?= $row ? rupiah($row->total) : 'Rp 0'; ?>">
                </div>
            </div>

            <hr class="sep">
            <button class="btn btn-red"><?= $row ? 'Update' : 'Simpan'; ?> Order</button>
        </form>
    </div>
</div>

<!-- Data PHP untuk assets/js/orders-form.js -->
<script type="application/json" id="ordersFormData">
<?= json_encode([
    'base_url' => base_url(),
    'designs' => $designs,
    'parts' => $parts,
    'existing_items' => isset($existing_items) ? $existing_items : []
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
