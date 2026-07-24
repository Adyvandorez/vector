<div class="card dashboard-page">
    <div class="card-header">
        <div class="dash-title">
            <h1>Dashboard</h1>
            <div class="small">Ringkasan pekerjaan</div>
        </div>

        <div class="dash-actions">
            <span class="badge dash-date-badge"><?= html_escape($dashboard_date_label ?? date('d M Y')); ?></span>
            <a class="btn btn-red" href="<?= base_url('orders/create'); ?>">+ Tambah Order</a>
        </div>
    </div>

    <div class="card-body">
        <div class="dash-top">
            <div class="card dash-panel dash-main-panel">
                <div class="profile-head">
                    <div class="logo-box">
                        <img src="<?= base_url('assets/img/img_ady.png'); ?>" alt="Logo">
                    </div>

                    <div class="u-flex-1">
                        <div class="u-title-sm"><?= html_escape($this->config->item('vi_app_name') ?: 'Vector Invoice'); ?></div>
                        <div class="small">Kelola pelanggan, order, pembayaran, revisi, file, dan invoice dalam satu sistem.</div>
                    </div>
                </div>

                <hr class="sep">

                <?php
                    $fmtTrend = function ($value) {
                        $value = (float)$value;
                        return ($value >= 0 ? '+' : '') . number_format($value, 1, '.', '') . '%';
                    };

                    $trendClass = function ($value) {
                        $value = (float)$value;
                        if ($value > 0) return 'is-up';
                        if ($value < 0) return 'is-down';
                        return 'is-flat';
                    };

                    $trendIcon = function ($value) {
                        $value = (float)$value;
                        if ($value > 0) return '↗';
                        if ($value < 0) return '↘';
                        return '→';
                    };
                ?>

                <div class="stats-grid stats-grid-main" aria-label="Ringkasan dashboard">
                    <div class="card stat-card stat-card-paid">
                        <div class="stat-card-head">
                            <div>
                                <div class="stat-kicker">Paid</div>
                                <div class="stat-label">Seluruh Paid</div>
                            </div>
                            <div class="stat-trend <?= $trendClass($paid_trend_all ?? 0); ?>" title="Perbandingan paid 30 hari terakhir dengan 30 hari sebelumnya">
                                <span><?= $trendIcon($paid_trend_all ?? 0); ?></span> <?= $fmtTrend($paid_trend_all ?? 0); ?>
                            </div>
                        </div>
                        <div class="stat-value money"><?= rupiah($income_all); ?></div>
                        <div class="stat-foot small">Total pembayaran masuk dari semua order valid.</div>
                    </div>

                    <div class="card stat-card stat-card-paid">
                        <div class="stat-card-head">
                            <div>
                                <div class="stat-kicker">Paid</div>
                                <div class="stat-label">Paid Tahun Ini</div>
                            </div>
                            <div class="stat-trend <?= $trendClass($paid_trend_year ?? 0); ?>" title="Perbandingan paid tahun ini dengan tahun sebelumnya">
                                <span><?= $trendIcon($paid_trend_year ?? 0); ?></span> <?= $fmtTrend($paid_trend_year ?? 0); ?>
                            </div>
                        </div>
                        <div class="stat-value money"><?= rupiah($income_year); ?></div>
                        <div class="stat-foot small">Akumulasi paid tahun berjalan.</div>
                    </div>

                    <div class="card stat-card stat-card-paid">
                        <div class="stat-card-head">
                            <div>
                                <div class="stat-kicker">Paid</div>
                                <div class="stat-label">Paid Bulan Ini</div>
                            </div>
                            <div class="stat-trend <?= $trendClass($paid_trend_month ?? 0); ?>" title="Perbandingan paid bulan ini dengan bulan sebelumnya">
                                <span><?= $trendIcon($paid_trend_month ?? 0); ?></span> <?= $fmtTrend($paid_trend_month ?? 0); ?>
                            </div>
                        </div>
                        <div class="stat-value money"><?= rupiah($income); ?></div>
                        <div class="stat-foot small">Paid berdasarkan tanggal order bulan ini.</div>
                    </div>

                    <div class="card stat-card stat-card-order">
                        <div class="stat-card-head">
                            <div>
                                <div class="stat-kicker">Order</div>
                                <div class="stat-label">Total Order (Semua)</div>
                            </div>
                            <span class="stat-mini-icon">#</span>
                        </div>
                        <div class="stat-value big"><?= (int)$total_orders_all; ?></div>
                        <div class="stat-foot small">Seluruh order valid.</div>
                    </div>

                    <div class="card stat-card stat-card-order">
                        <div class="stat-card-head">
                            <div>
                                <div class="stat-kicker">Order</div>
                                <div class="stat-label">Total Order Tahun Ini</div>
                            </div>
                            <span class="stat-mini-icon">Y</span>
                        </div>
                        <div class="stat-value big"><?= (int)$total_orders_year; ?></div>
                        <div class="stat-foot small">Order valid pada tahun berjalan.</div>
                    </div>

                    <div class="card stat-card stat-card-order">
                        <div class="stat-card-head">
                            <div>
                                <div class="stat-kicker">Order</div>
                                <div class="stat-label">Total Order Bulan Ini</div>
                            </div>
                            <span class="stat-mini-icon">M</span>
                        </div>
                        <div class="stat-value big"><?= (int)$total_orders; ?></div>
                        <div class="stat-foot small">Order valid bulan ini.</div>
                    </div>

                    <div class="card stat-card stat-card-order">
                        <div class="stat-card-head"><div><div class="stat-kicker">Pelanggan</div><div class="stat-label">Pelanggan Aktif</div></div><span class="stat-mini-icon">C</span></div>
                        <div class="stat-value big"><?= (int)$client_count; ?></div>
                        <div class="stat-foot small"><?= (int)$registered_client_count; ?> akun sudah siap login aplikasi.</div>
                    </div>

                    <div class="card stat-card stat-card-order">
                        <div class="stat-card-head"><div><div class="stat-kicker">Integrasi</div><div class="stat-label">Order dari Android</div></div><span class="stat-mini-icon">A</span></div>
                        <div class="stat-value big"><?= (int)$android_order_count; ?></div>
                        <div class="stat-foot small">Akan bertambah otomatis setelah REST API dan Android aktif.</div>
                    </div>

                </div>

                <hr class="sep">

                <div class="goal-head">
                    <div>
                        <div class="u-bold">Goal Completion</div>
                        <div class="small">Paid vs Total bulan ini</div>
                    </div>
                    <div class="badge"><?= (int)$pct; ?>%</div>
                </div>

                <div class="goal-bar">
                    <div class="goal-bar-fill" data-dashboard-progress data-pct="<?= (int)$pct; ?>"></div>
                </div>

                <div class="small u-mt-10">
                    Total bulan ini: <b><?= rupiah($monthTotal); ?></b> • Paid: <b><?= rupiah($income); ?></b>
                </div>
            </div>

            <div class="dash-side">
                <div class="card dash-panel dash-trend-panel">
                <div class="u-flex-between">
                    <div>
                        <div class="u-bold">Statistik Order Harian</div>
                        <div class="small">Jumlah order masuk per hari</div>
                    </div>
                    <div class="badge">Live</div>
                </div>

                <?php
                $plotH  = 225;
                $minBar = 6;

                $scaleMax = (int)$maxCount;
                if ($scaleMax < 5) $scaleMax = 5;
                else if ($scaleMax < 10) $scaleMax = 10;
                else $scaleMax = (int)(ceil($scaleMax / 10) * 10);

                $maxBarH = $plotH - 20;
                ?>

                <div class="trend-box">
                    <div class="trend-plot">
                        <?php for ($i = 0; $i < count($counts); $i++):
                            $val = (int)$counts[$i];

                            if ($val > 0) {
                                $barPx = (int)round(($val / $scaleMax) * $maxBarH);
                                if ($barPx < 12) $barPx = 12;
                            } else {
                                $barPx = $minBar;
                            }
                            if ($barPx > $maxBarH) $barPx = $maxBarH;
                        ?>
                            <div class="trend-bar-wrap">
                                <div class="trend-bar" title="<?= $val; ?>" data-dashboard-bar data-height="<?= $barPx; ?>"></div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="trend-labels">
                        <?php for ($i = 0; $i < count($counts); $i++): ?>
                            <div class="trend-label-item">
                                <div class="small"><?= date('d', strtotime($days[$i])); ?></div>
                                <div class="small u-muted-70"><?= (int)$counts[$i]; ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="small u-mt-10 u-muted-80">
                        Skala: 0 – <?= (int)$scaleMax; ?> order/hari
                    </div>
                </div>

                <div class="small u-mt-10">
                    Tips: klik <b>Orders</b> untuk detail & cetak nota.
                </div>
                </div>

                <div class="card dash-panel dash-unpaid-panel" aria-label="Ringkasan tagihan belum lunas">
                    <div class="unpaid-grid">
                    <div class="card stat-card stat-card-unpaid">
                        <div class="stat-card-head">
                            <div>
                                <div class="stat-kicker">Belum Lunas</div>
                                <div class="stat-label">Belum Lunas (Semua)</div>
                            </div>
                            <span class="stat-mini-icon danger">!</span>
                        </div>
                        <div class="stat-value money danger"><?= rupiah($allUnpaid); ?></div>
                        <div class="stat-foot small">
                            <?= !empty($unpaid_orders_all) ? (int)$unpaid_orders_all . ' order masih punya sisa tagihan.' : 'Semua order sudah aman.'; ?>
                        </div>
                    </div>

                    <div class="card stat-card stat-card-unpaid">
                        <div class="stat-card-head">
                            <div>
                                <div class="stat-kicker">Belum Lunas</div>
                                <div class="stat-label">Belum Lunas Tahun Ini</div>
                            </div>
                            <span class="stat-mini-icon danger">Y</span>
                        </div>
                        <div class="stat-value money danger"><?= rupiah($yearUnpaid); ?></div>
                        <div class="stat-foot small">
                            <?= !empty($unpaid_orders_year) ? (int)$unpaid_orders_year . ' order tahun ini belum lunas.' : 'Tidak ada sisa tagihan tahun ini.'; ?>
                        </div>
                    </div>

                    <div class="card stat-card stat-card-unpaid">
                        <div class="stat-card-head">
                            <div>
                                <div class="stat-kicker">Belum Lunas</div>
                                <div class="stat-label">Belum Lunas Bulan Ini</div>
                            </div>
                            <span class="stat-mini-icon danger">M</span>
                        </div>
                        <div class="stat-value money danger"><?= rupiah($monthUnpaid); ?></div>
                        <div class="stat-foot small">
                            <?= !empty($unpaid_orders_month) ? (int)$unpaid_orders_month . ' order bulan ini belum lunas.' : 'Tidak ada sisa tagihan bulan ini.'; ?>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-bottom">
            <div class="card latest-orders">
                <div class="u-flex-between">
                    <div>
                        <div class="u-bold">History Order Terbaru</div>
                        <div class="small u-mt-4">4 order valid terbaru berdasarkan deadline/tanggal input.</div>
                    </div>
                    <a class="btn btn-gold" href="<?= base_url('orders'); ?>">Lihat semua</a>
                </div>

                <table class="table u-mt-12 u-w-full dashboard-latest-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Klien</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latest as $r): ?>
                            <tr>
                                <td>
                                    <b><?= htmlspecialchars($r->order_code); ?></b>
                                    <div class="small"><?= htmlspecialchars($r->title); ?></div>
                                </td>
                                <td><?= htmlspecialchars($r->client_name); ?></td>
                                <td><b><?= rupiah($r->total); ?></b></td>
                                <td><span class="badge"><?= htmlspecialchars($r->status); ?></span></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($latest) == 0): ?>
                            <tr>
                                <td colspan="4" class="small">Belum ada data order.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="dashboard-latest-mobile mobile-only">
                    <?php foreach ($latest as $r): ?>
                        <div class="dlm-card">
                            <div>
                                <div class="dlm-code"><?= htmlspecialchars($r->order_code); ?></div>
                                <div class="dlm-title small"><?= htmlspecialchars($r->title); ?></div>
                            </div>
                            <span class="badge dlm-status"><?= htmlspecialchars($r->status); ?></span>
                            <div class="dlm-meta">
                                <div class="dlm-client"><?= htmlspecialchars($r->client_name); ?></div>
                                <div class="dlm-total"><?= rupiah($r->total); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (count($latest) == 0): ?>
                        <div class="dlm-card">
                            <div class="small">Belum ada data order.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card quick-actions">
                <div class="u-bold">Quick Actions</div>
                <div class="small u-mt-6">Akses cepat ke menu yang sering dipakai.</div>

                <div class="qa-grid">
                    <a class="card qa-card" href="<?= base_url('orders/create'); ?>">
                        <div class="badge">+</div>
                        <div class="qa-title">Tambah Order</div>
                        <div class="small">Buat order baru + multi item</div>
                    </a>

                    <a class="card qa-card" href="<?= base_url('prices'); ?>">
                        <div class="badge">Rp</div>
                        <div class="qa-title">Harga Matrix</div>
                        <div class="small">Atur harga per jenis & bagian</div>
                    </a>

                    <a class="card qa-card" href="<?= base_url('designs'); ?>">
                        <div class="badge">#</div>
                        <div class="qa-title">Jenis Desain</div>
                        <div class="small">Tambah kategori desain</div>
                    </a>

                    <a class="card qa-card" href="<?= base_url('orders'); ?>">
                        <div class="badge">⟡</div>
                        <div class="qa-title">Semua Orders</div>
                        <div class="small">Cari & cetak nota</div>
                    </a>

                    <a class="card qa-card" href="<?= base_url('drive-storage'); ?>">
                        <div class="badge">☁</div>
                        <div class="qa-title">Drive Storage</div>
                        <div class="small">Backup & sinkron file</div>
                    </a>

                    <a class="card qa-card" href="<?= base_url('ai'); ?>">
                        <div class="badge">⚡</div>
                        <div class="qa-title">AI Assistant</div>
                        <div class="small">Bantu analisis order</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
