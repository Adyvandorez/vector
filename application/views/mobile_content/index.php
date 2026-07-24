<?php
$s = function ($key, $default = '') use ($settings) {
    return html_escape($settings[$key] ?? $default);
};
$action_options = [
    'ORDER' => 'Buat Pesanan',
    'CATALOG' => 'Buka Katalog',
    'PORTFOLIO' => 'Buka Portofolio',
    'URL' => 'Buka Tautan',
    'NONE' => 'Tanpa Aksi'
];
$action_label = function ($value) use ($action_options) {
    return $action_options[$value] ?? $value;
};
$active_banners = count(array_filter($banners, function ($row) { return (int)$row->is_active === 1; }));
$active_promotions = count(array_filter($promotions, function ($row) { return (int)$row->is_active === 1; }));
$active_portfolios = count(array_filter($portfolios, function ($row) { return (int)$row->is_active === 1; }));
?>

<div class="app-content-page">
    <header class="card app-content-hero">
        <div class="app-content-hero__body">
            <div class="app-content-hero__copy">
                <div class="app-content-kicker">PUSAT KONTEN ANDROID</div>
                <h1>Atur tampilan aplikasi dari satu halaman</h1>
                <p>Kelola teks beranda, biaya, banner, promosi, dan portofolio. Perubahan akan dibaca Android melalui API tanpa mengubah endpoint atau memasang APK baru.</p>
                <div class="app-content-hero__actions">
                    <a class="btn btn-red" href="#settings">Mulai Mengatur</a>
                    <a class="btn" href="<?= base_url('api/home-content'); ?>" target="_blank" rel="noopener">Periksa Data API</a>
                </div>
            </div>

            <div class="content-summary-grid" aria-label="Ringkasan konten">
                <div class="content-summary-card">
                    <span>Banner aktif</span>
                    <strong><?= $active_banners; ?></strong>
                    <small>dari <?= count($banners); ?> banner</small>
                </div>
                <div class="content-summary-card">
                    <span>Promosi aktif</span>
                    <strong><?= $active_promotions; ?></strong>
                    <small>dari <?= count($promotions); ?> promosi</small>
                </div>
                <div class="content-summary-card">
                    <span>Portofolio aktif</span>
                    <strong><?= $active_portfolios; ?></strong>
                    <small>dari <?= count($portfolios); ?> karya</small>
                </div>
            </div>
        </div>
    </header>

    <?php if ($this->session->flashdata('app_content_ok')): ?>
        <div class="content-alert content-alert--success flash-toast js-flash-toast">
            <strong>Berhasil.</strong> <?= html_escape($this->session->flashdata('app_content_ok')); ?>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('app_content_err')): ?>
        <div class="content-alert content-alert--danger flash-toast js-flash-toast">
            <strong>Perlu diperiksa.</strong> <?= html_escape($this->session->flashdata('app_content_err')); ?>
        </div>
    <?php endif; ?>

    <div class="content-flow card" aria-label="Urutan pengaturan konten">
        <div><span>1</span><strong>Identitas</strong><small>Atur teks dan biaya</small></div>
        <div><span>2</span><strong>Banner</strong><small>Hero utama beranda</small></div>
        <div><span>3</span><strong>Promosi</strong><small>Informasi promo berkala</small></div>
        <div><span>4</span><strong>Portofolio</strong><small>Contoh karya untuk klien</small></div>
    </div>

    <nav class="app-content-tabs" aria-label="Navigasi konten aplikasi">
        <a href="#settings" class="is-active"><span>01</span> Pengaturan</a>
        <a href="#banners"><span>02</span> Banner</a>
        <a href="#promotions"><span>03</span> Promosi</a>
        <a href="#portfolios"><span>04</span> Portofolio</a>
    </nav>

    <section id="settings" class="card app-content-section">
        <div class="content-section-head">
            <div class="content-section-number">01</div>
            <div>
                <h2>Pengaturan aplikasi</h2>
                <p>Bagian ini mengatur identitas, teks yang tampil, aturan pembayaran, dan biaya tambahan.</p>
            </div>
        </div>

        <form class="content-settings-form" method="post" action="<?= base_url('app-content/settings'); ?>">
            <?= csrf_field(); ?>

            <article class="content-panel">
                <div class="content-panel__head">
                    <div>
                        <h3>Identitas dan teks beranda</h3>
                        <p>Gunakan kalimat singkat agar nyaman dibaca pada layar ponsel.</p>
                    </div>
                    <span class="content-panel__badge">Tampilan</span>
                </div>
                <div class="app-content-grid two">
                    <label class="content-field"><span>Nama brand</span><input class="input" name="brand_name" value="<?= $s('brand_name', 'Ady_vandorez'); ?>"><small>Nama studio atau pemilik karya.</small></label>
                    <label class="content-field"><span>Nama aplikasi</span><input class="input" name="app_name" value="<?= $s('app_name', 'Vector Order'); ?>"><small>Nama yang tampil pada halaman autentikasi.</small></label>
                    <label class="content-field"><span>Nama pada header</span><input class="input" name="mobile_header_name" value="<?= $s('mobile_header_name', 'Ady_vandorez'); ?>"><small>Label singkat di bagian atas aplikasi.</small></label>
                    <label class="content-field"><span>Tagline</span><input class="input" name="tagline" value="<?= $s('tagline', 'Vector Portrait Artist'); ?>"><small>Deskripsi pendek tentang layanan.</small></label>
                    <label class="content-field"><span>Sapaan beranda</span><input class="input" name="home_greeting" value="<?= $s('home_greeting', 'Halo'); ?>"><small>Nama pelanggan ditambahkan otomatis oleh aplikasi.</small></label>
                    <label class="content-field"><span>Pertanyaan beranda</span><input class="input" name="home_question" value="<?= $s('home_question', 'Apa desain yang ingin kamu buat?'); ?>"><small>Kalimat pembuka sebelum daftar layanan.</small></label>
                    <label class="content-field span-two"><span>Judul hero</span><input class="input" name="hero_title" value="<?= $s('hero_title', 'Jadikan fotomu karya yang lebih berkarakter.'); ?>"><small>Judul utama di atas banner beranda.</small></label>
                    <label class="content-field"><span>Teks tombol hero</span><input class="input" name="hero_button_text" value="<?= $s('hero_button_text', 'Pesan Sekarang'); ?>"></label>
                    <label class="content-field"><span>Judul bagian promosi</span><input class="input" name="promotion_section_title" value="<?= $s('promotion_section_title', 'Promo Studio'); ?>"></label>
                    <label class="content-field"><span>Judul bagian portofolio</span><input class="input" name="portfolio_section_title" value="<?= $s('portfolio_section_title', 'Portofolio Terbaru'); ?>"></label>
                    <label class="content-field"><span>Estimasi normal</span><input class="input" name="normal_duration_text" value="<?= $s('normal_duration_text', '2–3 hari kerja'); ?>"><small>Contoh: 2–3 hari kerja.</small></label>
                </div>
            </article>

            <article class="content-panel">
                <div class="content-panel__head">
                    <div>
                        <h3>Aturan pembayaran dan biaya</h3>
                        <p>Nilai ini digunakan oleh simulator harga dan ringkasan pemesanan Android.</p>
                    </div>
                    <span class="content-panel__badge">Harga</span>
                </div>
                <div class="app-content-grid four">
                    <label class="content-field"><span>Minimal DP</span><div class="content-input-suffix"><input class="input" type="number" min="1" max="100" name="dp_percent" value="<?= $s('dp_percent', '50'); ?>"><b>%</b></div></label>
                    <label class="content-field"><span>Revisi gratis</span><div class="content-input-suffix"><input class="input" type="number" min="0" name="revision_free_limit" value="<?= $s('revision_free_limit', '3'); ?>"><b>x</b></div></label>
                    <label class="content-field"><span>Revisi tambahan</span><div class="content-input-prefix"><b>Rp</b><input class="input" inputmode="numeric" name="extra_revision_fee" value="<?= $s('extra_revision_fee', '15000'); ?>"></div></label>
                    <label class="content-field"><span>Biaya express</span><div class="content-input-prefix"><b>Rp</b><input class="input" inputmode="numeric" name="express_fee" value="<?= $s('express_fee', '50000'); ?>"></div></label>
                    <label class="content-field"><span>Background kompleks</span><div class="content-input-prefix"><b>Rp</b><input class="input" inputmode="numeric" name="complex_background_fee" value="<?= $s('complex_background_fee', '30000'); ?>"></div></label>
                    <label class="content-field"><span>CDR per desain</span><div class="content-input-prefix"><b>Rp</b><input class="input" inputmode="numeric" name="cdr_fee_per_design" value="<?= $s('cdr_fee_per_design', '25000'); ?>"></div></label>
                    <label class="content-field"><span>Lisensi per kepala</span><div class="content-input-prefix"><b>Rp</b><input class="input" inputmode="numeric" name="exclusive_license_fee_per_head" value="<?= $s('exclusive_license_fee_per_head', '50000'); ?>"></div></label>
                </div>
            </article>

            <article class="content-panel">
                <div class="content-panel__head">
                    <div>
                        <h3>Kontak bantuan dan dokumen</h3>
                        <p>Kontak ini muncul pada menu bantuan dan profil pelanggan.</p>
                    </div>
                    <span class="content-panel__badge">Bantuan</span>
                </div>
                <div class="app-content-grid two">
                    <label class="content-field"><span>WhatsApp bantuan</span><input class="input" name="support_whatsapp" value="<?= $s('support_whatsapp', '085236222785'); ?>"><small>Gunakan format nomor yang dapat dibuka WhatsApp.</small></label>
                    <label class="content-field"><span>Email bantuan</span><input class="input" type="email" name="support_email" value="<?= $s('support_email'); ?>"></label>
                    <label class="content-field span-two"><span>Linktree atau website</span><input class="input" type="url" name="linktree_url" value="<?= $s('linktree_url', 'https://linktr.ee/Ady_vandorez'); ?>"></label>
                    <label class="content-field"><span>URL syarat layanan</span><input class="input" type="url" name="terms_url" value="<?= $s('terms_url'); ?>" placeholder="https://..."></label>
                    <label class="content-field"><span>URL kebijakan privasi</span><input class="input" type="url" name="privacy_url" value="<?= $s('privacy_url'); ?>" placeholder="https://..."></label>
                </div>
            </article>

            <div class="content-save-bar">
                <div><strong>Simpan setelah selesai mengubah.</strong><small>Perubahan teks dan biaya langsung tersedia di API Android.</small></div>
                <button class="btn btn-red" type="submit">Simpan Pengaturan</button>
            </div>
        </form>
    </section>

    <section id="banners" class="card app-content-section">
        <div class="content-section-head content-section-head--with-stat">
            <div class="content-section-number">02</div>
            <div>
                <h2>Banner beranda</h2>
                <p>Banner aktif dengan urutan terkecil dipakai sebagai tampilan hero utama Android.</p>
            </div>
            <span class="section-stat"><?= $active_banners; ?> aktif</span>
        </div>
        <div class="content-section-body">
            <details class="content-create-box">
                <summary><span><b>+ Tambah banner</b><small>Buat hero atau informasi utama baru.</small></span><i></i></summary>
                <form class="content-editor-form" method="post" enctype="multipart/form-data" action="<?= base_url('app-content/banner/save/0'); ?>">
                    <?= csrf_field(); ?>
                    <div class="app-content-grid two">
                        <label class="content-field"><span>Judul banner</span><input class="input" name="title" required placeholder="Contoh: Ubah fotomu menjadi karya"></label>
                        <label class="content-field"><span>Subjudul</span><input class="input" name="subtitle" placeholder="Penjelasan singkat"></label>
                        <label class="content-field"><span>Teks tombol</span><input class="input" name="button_text" value="Pesan Sekarang"></label>
                        <label class="content-field"><span>Aksi tombol</span><select class="input" name="action_type" data-action-select><?php foreach ($action_options as $key => $label): ?><option value="<?= $key; ?>"><?= html_escape($label); ?></option><?php endforeach; ?></select></label>
                        <label class="content-field"><span>Tujuan aksi</span><input class="input" name="action_value" data-action-value placeholder="Kosongkan untuk aksi standar"><small data-action-help>Isi hanya jika memilih tautan atau tujuan khusus.</small></label>
                        <label class="content-field"><span>Urutan tampil</span><input class="input" type="number" name="sort_order" value="10"><small>Angka lebih kecil tampil lebih dulu.</small></label>
                        <label class="content-field"><span>Gambar banner</span><input class="input content-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>Disarankan rasio 16:9 atau 2:1.</small></label>
                        <label class="content-toggle"><input type="checkbox" name="is_active" value="1" checked><span><b>Aktif di aplikasi</b><small>Banner dapat langsung ditampilkan Android.</small></span></label>
                    </div>
                    <div class="content-form-actions"><button class="btn btn-red" type="submit">Tambah Banner</button></div>
                </form>
            </details>

            <div class="content-manage-list">
                <?php if (!$banners): ?><div class="empty-content"><strong>Belum ada banner.</strong><span>Android akan menggunakan tampilan gradient bawaan sampai banner dibuat.</span></div><?php endif; ?>
                <?php foreach ($banners as $row): ?>
                    <?php $banner_image = $row->image_path ? base_url(trim($row->image_path, '/')) : ''; ?>
                    <article class="content-manage-card">
                        <div class="content-manage-card__overview">
                            <?php if ($banner_image): ?>
                                <a class="content-manage-card__image vi-image-trigger" href="<?= html_escape($banner_image); ?>" data-image-lightbox="<?= html_escape($banner_image); ?>" data-image-title="<?= html_escape($row->title); ?>">
                                    <img src="<?= html_escape($banner_image); ?>" alt="Banner <?= html_escape($row->title); ?>" loading="lazy">
                                </a>
                            <?php else: ?><div class="content-manage-card__image content-image-empty">BANNER</div><?php endif; ?>
                            <div class="content-manage-card__info">
                                <div class="content-status-line"><span class="content-status <?= $row->is_active ? 'is-active' : 'is-inactive'; ?>"><?= $row->is_active ? 'Aktif' : 'Nonaktif'; ?></span><span>Urutan <?= (int)$row->sort_order; ?></span></div>
                                <h3><?= html_escape($row->title); ?></h3>
                                <p><?= html_escape($row->subtitle ?: 'Tanpa subjudul'); ?></p>
                                <div class="content-action-summary">Tombol: <b><?= html_escape($row->button_text ?: '-'); ?></b> → <?= html_escape($action_label($row->action_type)); ?></div>
                            </div>
                            <details class="content-edit-toggle">
                                <summary>Edit</summary>
                            </details>
                        </div>
                        <div class="content-edit-panel" data-details-panel>
                            <form class="content-editor-form" method="post" enctype="multipart/form-data" action="<?= base_url('app-content/banner/save/' . $row->id); ?>">
                                <?= csrf_field(); ?>
                                <div class="app-content-grid two">
                                    <label class="content-field"><span>Judul</span><input class="input" name="title" value="<?= html_escape($row->title); ?>" required></label>
                                    <label class="content-field"><span>Subjudul</span><input class="input" name="subtitle" value="<?= html_escape($row->subtitle); ?>"></label>
                                    <label class="content-field"><span>Teks tombol</span><input class="input" name="button_text" value="<?= html_escape($row->button_text); ?>"></label>
                                    <label class="content-field"><span>Aksi tombol</span><select class="input" name="action_type" data-action-select><?php foreach ($action_options as $key => $label): ?><option value="<?= $key; ?>" <?= $row->action_type === $key ? 'selected' : ''; ?>><?= html_escape($label); ?></option><?php endforeach; ?></select></label>
                                    <label class="content-field"><span>Tujuan aksi</span><input class="input" name="action_value" data-action-value value="<?= html_escape($row->action_value); ?>"><small data-action-help></small></label>
                                    <label class="content-field"><span>Urutan</span><input class="input" type="number" name="sort_order" value="<?= (int)$row->sort_order; ?>"></label>
                                    <label class="content-field"><span>Ganti gambar</span><input class="input content-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>Kosongkan jika tidak ingin mengganti.</small></label>
                                    <label class="content-toggle"><input type="checkbox" name="is_active" value="1" <?= $row->is_active ? 'checked' : ''; ?>><span><b>Aktif di aplikasi</b><small>Nonaktifkan tanpa menghapus data.</small></span></label>
                                </div>
                                <div class="content-form-actions"><button class="btn btn-red" type="submit">Simpan Perubahan</button><button class="btn content-delete-btn" type="submit" formaction="<?= base_url('app-content/delete/banner/' . $row->id); ?>" formmethod="post" onclick="return confirm('Hapus banner ini?')">Hapus Banner</button></div>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="promotions" class="card app-content-section">
        <div class="content-section-head content-section-head--with-stat">
            <div class="content-section-number">03</div>
            <div>
                <h2>Promosi</h2>
                <p>Atur promo yang tampil pada periode tertentu. Tanggal dapat dikosongkan untuk promo tanpa batas waktu.</p>
            </div>
            <span class="section-stat"><?= $active_promotions; ?> aktif</span>
        </div>
        <div class="content-section-body">
            <details class="content-create-box">
                <summary><span><b>+ Tambah promosi</b><small>Buat diskon, paket, atau pengumuman khusus.</small></span><i></i></summary>
                <form class="content-editor-form" method="post" enctype="multipart/form-data" action="<?= base_url('app-content/promotion/save/0'); ?>">
                    <?= csrf_field(); ?>
                    <div class="app-content-grid two">
                        <label class="content-field"><span>Judul promosi</span><input class="input" name="title" required></label>
                        <label class="content-field"><span>Badge</span><input class="input" name="badge_text" placeholder="Contoh: PROMO JULI"></label>
                        <label class="content-field span-two"><span>Deskripsi</span><textarea class="input admin-textarea" name="description" placeholder="Jelaskan promo dengan singkat dan jelas."></textarea></label>
                        <label class="content-field"><span>Teks tombol</span><input class="input" name="button_text" value="Lihat Promo"></label>
                        <label class="content-field"><span>Aksi tombol</span><select class="input" name="action_type" data-action-select><?php foreach ($action_options as $key => $label): ?><option value="<?= $key; ?>"><?= html_escape($label); ?></option><?php endforeach; ?></select></label>
                        <label class="content-field"><span>Tujuan aksi</span><input class="input" name="action_value" data-action-value><small data-action-help></small></label>
                        <label class="content-field"><span>Urutan tampil</span><input class="input" type="number" name="sort_order" value="10"></label>
                        <label class="content-field"><span>Mulai tampil</span><input class="input" type="datetime-local" name="starts_at"></label>
                        <label class="content-field"><span>Berakhir</span><input class="input" type="datetime-local" name="ends_at"></label>
                        <label class="content-field"><span>Gambar promosi</span><input class="input content-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                        <label class="content-toggle"><input type="checkbox" name="is_active" value="1" checked><span><b>Aktif di aplikasi</b><small>Jadwal tetap mengikuti tanggal di atas.</small></span></label>
                    </div>
                    <div class="content-form-actions"><button class="btn btn-red" type="submit">Tambah Promosi</button></div>
                </form>
            </details>

            <div class="content-manage-list">
                <?php if (!$promotions): ?><div class="empty-content"><strong>Belum ada promosi.</strong><span>Tambahkan promosi jika ingin menampilkan program khusus kepada pelanggan.</span></div><?php endif; ?>
                <?php foreach ($promotions as $row): ?>
                    <?php $promotion_image = $row->image_path ? base_url(trim($row->image_path, '/')) : ''; ?>
                    <article class="content-manage-card">
                        <div class="content-manage-card__overview">
                            <?php if ($promotion_image): ?>
                                <a class="content-manage-card__image vi-image-trigger" href="<?= html_escape($promotion_image); ?>" data-image-lightbox="<?= html_escape($promotion_image); ?>" data-image-title="<?= html_escape($row->title); ?>"><img src="<?= html_escape($promotion_image); ?>" alt="Promosi <?= html_escape($row->title); ?>" loading="lazy"></a>
                            <?php else: ?><div class="content-manage-card__image content-image-empty">PROMO</div><?php endif; ?>
                            <div class="content-manage-card__info">
                                <div class="content-status-line"><span class="content-status <?= $row->is_active ? 'is-active' : 'is-inactive'; ?>"><?= $row->is_active ? 'Aktif' : 'Nonaktif'; ?></span><?php if ($row->badge_text): ?><span class="content-badge-gold"><?= html_escape($row->badge_text); ?></span><?php endif; ?></div>
                                <h3><?= html_escape($row->title); ?></h3>
                                <p><?= html_escape($row->description ?: 'Tanpa deskripsi'); ?></p>
                                <div class="content-action-summary"><?= $row->starts_at ? html_escape(date('d M Y H:i', strtotime($row->starts_at))) : 'Mulai sekarang'; ?> — <?= $row->ends_at ? html_escape(date('d M Y H:i', strtotime($row->ends_at))) : 'Tanpa batas'; ?></div>
                            </div>
                            <details class="content-edit-toggle"><summary>Edit</summary></details>
                        </div>
                        <div class="content-edit-panel" data-details-panel>
                            <form class="content-editor-form" method="post" enctype="multipart/form-data" action="<?= base_url('app-content/promotion/save/' . $row->id); ?>">
                                <?= csrf_field(); ?>
                                <div class="app-content-grid two">
                                    <label class="content-field"><span>Judul</span><input class="input" name="title" value="<?= html_escape($row->title); ?>" required></label>
                                    <label class="content-field"><span>Badge</span><input class="input" name="badge_text" value="<?= html_escape($row->badge_text); ?>"></label>
                                    <label class="content-field span-two"><span>Deskripsi</span><textarea class="input admin-textarea" name="description"><?= html_escape($row->description); ?></textarea></label>
                                    <label class="content-field"><span>Teks tombol</span><input class="input" name="button_text" value="<?= html_escape($row->button_text); ?>"></label>
                                    <label class="content-field"><span>Aksi tombol</span><select class="input" name="action_type" data-action-select><?php foreach ($action_options as $key => $label): ?><option value="<?= $key; ?>" <?= $row->action_type === $key ? 'selected' : ''; ?>><?= html_escape($label); ?></option><?php endforeach; ?></select></label>
                                    <label class="content-field"><span>Tujuan aksi</span><input class="input" name="action_value" data-action-value value="<?= html_escape($row->action_value); ?>"><small data-action-help></small></label>
                                    <label class="content-field"><span>Urutan</span><input class="input" type="number" name="sort_order" value="<?= (int)$row->sort_order; ?>"></label>
                                    <label class="content-field"><span>Mulai</span><input class="input" type="datetime-local" name="starts_at" value="<?= $row->starts_at ? date('Y-m-d\TH:i', strtotime($row->starts_at)) : ''; ?>"></label>
                                    <label class="content-field"><span>Berakhir</span><input class="input" type="datetime-local" name="ends_at" value="<?= $row->ends_at ? date('Y-m-d\TH:i', strtotime($row->ends_at)) : ''; ?>"></label>
                                    <label class="content-field"><span>Ganti gambar</span><input class="input content-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                                    <label class="content-toggle"><input type="checkbox" name="is_active" value="1" <?= $row->is_active ? 'checked' : ''; ?>><span><b>Aktif di aplikasi</b><small>Nonaktifkan tanpa menghapus data.</small></span></label>
                                </div>
                                <div class="content-form-actions"><button class="btn btn-red" type="submit">Simpan Perubahan</button><button class="btn content-delete-btn" type="submit" formaction="<?= base_url('app-content/delete/promotion/' . $row->id); ?>" formmethod="post" onclick="return confirm('Hapus promosi ini?')">Hapus Promosi</button></div>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="portfolios" class="card app-content-section">
        <div class="content-section-head content-section-head--with-stat">
            <div class="content-section-number">04</div>
            <div>
                <h2>Portofolio aplikasi</h2>
                <p>Tambahkan contoh karya terbaik. Satu jenis desain dapat mempunyai banyak gambar portofolio.</p>
            </div>
            <span class="section-stat"><?= $active_portfolios; ?> aktif</span>
        </div>
        <div class="content-section-body">
            <details class="content-create-box">
                <summary><span><b>+ Tambah portofolio</b><small>Unggah karya dan hubungkan dengan jenis desain.</small></span><i></i></summary>
                <form class="content-editor-form" method="post" enctype="multipart/form-data" action="<?= base_url('app-content/portfolio/save/0'); ?>">
                    <?= csrf_field(); ?>
                    <div class="app-content-grid two">
                        <label class="content-field"><span>Judul karya</span><input class="input" name="title" required></label>
                        <label class="content-field"><span>Jenis desain terkait</span><select class="input" name="design_type_id"><option value="">Tanpa kategori</option><?php foreach ($designs as $d): ?><option value="<?= (int)$d->id; ?>"><?= html_escape($d->name); ?></option><?php endforeach; ?></select></label>
                        <label class="content-field span-two"><span>Deskripsi</span><textarea class="input admin-textarea" name="description" placeholder="Jelaskan gaya atau keunggulan karya."></textarea></label>
                        <label class="content-field"><span>Urutan tampil</span><input class="input" type="number" name="sort_order" value="10"></label>
                        <label class="content-field"><span>Gambar portofolio</span><input class="input content-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required><small>Gunakan gambar tajam tanpa distorsi.</small></label>
                        <label class="content-toggle"><input type="checkbox" name="is_featured" value="1" checked><span><b>Tampilkan di beranda</b><small>Karya masuk bagian portofolio unggulan.</small></span></label>
                        <label class="content-toggle"><input type="checkbox" name="is_active" value="1" checked><span><b>Aktif di galeri</b><small>Karya dapat dilihat seluruh pelanggan.</small></span></label>
                    </div>
                    <div class="content-form-actions"><button class="btn btn-red" type="submit">Tambah Portofolio</button></div>
                </form>
            </details>

            <div class="portfolio-manage-grid">
                <?php if (!$portfolios): ?><div class="empty-content span-two"><strong>Belum ada portofolio khusus.</strong><span>Android masih dapat menggunakan preview dari menu Jenis Desain.</span></div><?php endif; ?>
                <?php foreach ($portfolios as $row): ?>
                    <?php $portfolio_image = $row->image_path ? base_url(trim($row->image_path, '/')) : ''; ?>
                    <article class="portfolio-manage-card">
                        <?php if ($portfolio_image): ?>
                            <a class="portfolio-manage-card__image vi-image-trigger" href="<?= html_escape($portfolio_image); ?>" data-image-lightbox="<?= html_escape($portfolio_image); ?>" data-image-title="<?= html_escape($row->title); ?>"><img src="<?= html_escape($portfolio_image); ?>" alt="Portofolio <?= html_escape($row->title); ?>" loading="lazy"></a>
                        <?php else: ?><div class="portfolio-manage-card__image content-image-empty">PORTOFOLIO</div><?php endif; ?>
                        <div class="portfolio-manage-card__body">
                            <div class="content-status-line"><span class="content-status <?= $row->is_active ? 'is-active' : 'is-inactive'; ?>"><?= $row->is_active ? 'Aktif' : 'Nonaktif'; ?></span><?php if ($row->is_featured): ?><span class="content-badge-gold">Beranda</span><?php endif; ?></div>
                            <h3><?= html_escape($row->title); ?></h3>
                            <p><?= html_escape($row->design_name ?: 'Tanpa kategori'); ?></p>
                            <details class="portfolio-edit-toggle">
                                <summary>Edit Portofolio</summary>
                                <form class="content-editor-form" method="post" enctype="multipart/form-data" action="<?= base_url('app-content/portfolio/save/' . $row->id); ?>">
                                    <?= csrf_field(); ?>
                                    <label class="content-field"><span>Judul</span><input class="input" name="title" value="<?= html_escape($row->title); ?>" required></label>
                                    <label class="content-field"><span>Jenis desain</span><select class="input" name="design_type_id"><option value="">Tanpa kategori</option><?php foreach ($designs as $d): ?><option value="<?= (int)$d->id; ?>" <?= (int)$row->design_type_id === (int)$d->id ? 'selected' : ''; ?>><?= html_escape($d->name); ?></option><?php endforeach; ?></select></label>
                                    <label class="content-field"><span>Deskripsi</span><textarea class="input admin-textarea" name="description"><?= html_escape($row->description); ?></textarea></label>
                                    <label class="content-field"><span>Urutan</span><input class="input" type="number" name="sort_order" value="<?= (int)$row->sort_order; ?>"></label>
                                    <label class="content-field"><span>Ganti gambar</span><input class="input content-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                                    <label class="content-toggle"><input type="checkbox" name="is_featured" value="1" <?= $row->is_featured ? 'checked' : ''; ?>><span><b>Tampilkan di beranda</b></span></label>
                                    <label class="content-toggle"><input type="checkbox" name="is_active" value="1" <?= $row->is_active ? 'checked' : ''; ?>><span><b>Aktif di galeri</b></span></label>
                                    <div class="content-form-actions"><button class="btn btn-red" type="submit">Simpan</button><button class="btn content-delete-btn" type="submit" formaction="<?= base_url('app-content/delete/portfolio/' . $row->id); ?>" formmethod="post" onclick="return confirm('Hapus portofolio ini?')">Hapus</button></div>
                                </form>
                            </details>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>
