<div class="card orders-page">
    <div class="card-header admin-page-head">
        <div>
            <h1>Orders</h1>
            <div class="small">Kelola pesanan dari web admin dan aplikasi pelanggan dalam satu tempat.</div>
        </div>
        <a class="btn btn-red" href="<?= base_url('orders/create'); ?>">+ Tambah Order</a>
    </div>

    <div class="card-body">
        <form method="get" class="admin-filter-grid">
            <input class="input" name="q" placeholder="Cari kode, pelanggan, telepon, atau judul" value="<?= html_escape($q ?? ''); ?>">
            <select class="input" name="status">
                <option value="">Semua status</option>
                <?php foreach (['MASUK','PROSES','REVISI','SELESAI'] as $st): ?>
                    <option value="<?= $st; ?>" <?= ($status_filter ?? '') === $st ? 'selected' : ''; ?>><?= $st; ?></option>
                <?php endforeach; ?>
            </select>
            <select class="input" name="source">
                <option value="">Semua sumber</option>
                <option value="WEB_ADMIN" <?= ($source_filter ?? '') === 'WEB_ADMIN' ? 'selected' : ''; ?>>Web Admin</option>
                <option value="ANDROID" <?= ($source_filter ?? '') === 'ANDROID' ? 'selected' : ''; ?>>Android</option>
            </select>
            <div class="admin-actions"><button class="btn" type="submit">Terapkan</button><a class="btn" href="<?= base_url('orders'); ?>">Reset</a></div>
        </form>


        <!-- ================= DESKTOP TABLE (JANGAN DIUBAH) ================= -->
        <table class="table orders-desktop-table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Klien</th>
                    <th>Jenis</th>
                    <th>Bagian</th>
                    <th>Total</th>
                    <th>Sisa</th>
                    <th>Status</th>
                    <th class="u-actions-col-260">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?><tr><td colspan="8" class="small">Data order tidak ditemukan.</td></tr><?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <?php $sisa = max(0, (int)$r->total - (int)$r->paid); ?>
                    <tr>
                        <td>
                            <b><?= htmlspecialchars($r->order_code); ?></b>
                            <div class="small"><?= htmlspecialchars($r->title); ?></div>
                            <div class="u-mt-4"><span class="badge admin-source-badge"><?= ($r->created_via ?? 'WEB_ADMIN') === 'ANDROID' ? 'ANDROID' : 'WEB ADMIN'; ?></span></div>
                        </td>
                        <td><?= htmlspecialchars($r->client_name); ?></td>
                        <td><?= htmlspecialchars($r->design_name); ?></td>
                        <td><span class="badge"><?= htmlspecialchars($r->body_name); ?></span></td>
                        <td>
                            <b><?= rupiah($r->total); ?></b>
                            <div class="small">Paid: <?= rupiah($r->paid); ?></div>
                        </td>
                        <td>
                            <b class="<?= $sisa > 0 ? 'u-danger' : ''; ?>">
                                <?= rupiah($sisa); ?>
                            </b>
                            <?php if ($sisa > 0): ?>
                                <div class="small">Belum dibayar</div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge"><?= htmlspecialchars($r->status); ?></span></td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-gold" href="<?= base_url('orders/view/' . $r->id); ?>">Detail</a>
                                <a class="btn" href="<?= base_url('orders/edit/' . $r->id); ?>">Edit</a>
                                <form method="post" action="<?= base_url('orders/delete/' . $r->id); ?>" class="u-inline-form" data-confirm="Hapus order?">
                                    <?= csrf_field(); ?>
                                    <button type="submit" class="btn btn-red">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ================= MOBILE CARD LIST ================= -->
        <div class="order-mobile-list mobile-only">
            <?php if (count($rows) === 0): ?>
                <div class="order-mobile-empty small">Data order tidak ditemukan.</div>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <?php
                    $sisa = max(0, (int)$r->total - (int)$r->paid);
                    $status_class = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $r->status));
                ?>

                <div class="om-card">

                    <!-- STATUS -->
                    <span class="om-status <?= html_escape($status_class); ?>">
                        <?= htmlspecialchars(strtoupper($r->status === 'LUNAS' ? 'SELESAI' : $r->status)); ?>
                    </span>
                    <div class="small u-mt-4"><?= ($r->created_via ?? 'WEB_ADMIN') === 'ANDROID' ? 'Dari aplikasi Android' : 'Dari web admin'; ?></div>

                    <!-- CODE -->
                    <div class="om-code">
                        <?= htmlspecialchars($r->order_code); ?>
                    </div>

                    <!-- CLIENT -->
                    <div class="om-client">
                        <?= htmlspecialchars($r->client_name); ?>
                    </div>

                    <!-- GRID INFO -->
                    <div class="om-grid">
                        <div class="om-label">Jenis Desain</div>
                        <div class="om-value"><?= htmlspecialchars($r->design_name); ?></div>

                        <div class="om-label">Bagian</div>
                        <div class="om-value">
                            <span class="om-pill"><?= htmlspecialchars($r->body_name); ?></span>
                        </div>

                        <div class="om-label">Total Harga</div>
                        <div class="om-value price"><?= rupiah($r->total); ?></div>

                    </div>

                    <!-- ACTIONS -->
                    <div class="om-actions">

                        <!-- DETAIL -->
                        <a href="<?= base_url('orders/view/' . $r->id); ?>" class="om-detail">
                            <svg viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <path d="M14 2v6h6" />
                                <path d="M16 13H8" />
                                <path d="M16 17H8" />
                            </svg>
                            Detail
                        </a>

                        <!-- ICONS -->
                        <div class="om-icons">
                            <a href="<?= base_url('orders/edit/' . $r->id); ?>" class="om-action om-edit">
                                <svg viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                </svg>
                            </a>

                            <form method="post" action="<?= base_url('orders/delete/' . $r->id); ?>" class="u-icon-form" data-confirm="Hapus order?">
                                <?= csrf_field(); ?>
                                <button type="submit" class="om-action om-delete" aria-label="Hapus order">
                                <svg viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18" />
                                    <path d="M8 6V4h8v2" />
                                    <path d="M19 6l-1 14H6L5 6" />
                                    <path d="M10 11v6" />
                                    <path d="M14 11v6" />
                                </svg>
                            </button>
                            </form>
                        </div>

                    </div>

                    <?php if ($sisa > 0): ?>
                        <div class="om-sisa">
                            <span>Sisa Tagihan</span>
                            <b><?= rupiah($sisa); ?></b>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>