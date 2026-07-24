<?php
$seg1  = $this->uri->segment(1);
$class = $this->router->fetch_class(); // controller aktif

function is_active($seg1, $class, $target)
{
    if ($target === 'dashboard') {
        return ($class === 'dashboard') ? 'active' : '';
    }
    if ($target === 'orders' && $class === 'invoices') {
        return 'active';
    }
    return ($seg1 === $target) ? 'active' : '';
}
?>



<div class="sidebar sb">

    <!-- ===== Brand / Logo ===== -->
    <div class="sb__top">
        <div class="sb__brand">
            <div class="sb__logo">
                <img src="<?= base_url('assets/img/logo-ady.png'); ?>" alt="Logo">
            </div>
            <div class="sb__brandText">
                <div class="sb__brandName"><?= html_escape($this->config->item('vi_brand_name') ?: 'Vector Invoice'); ?></div>
                <div class="sb__brandSub"><?= html_escape($this->config->item('vi_brand_tagline') ?: 'Order Manager'); ?></div>
            </div>
        </div>
    </div>

    <!-- ===== Navigasi utama: master data dan transaksi lebih dulu ===== -->
    <nav class="sb__nav">
        <a class="sb__item <?= is_active($seg1, $class, 'dashboard'); ?>" href="<?= base_url('dashboard'); ?>">
            <span class="sb__icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 13h7V4H4v9Zm9 7h7V11h-7v9ZM4 20h7v-5H4v5Zm9-11h7V4h-7v5Z"
                        stroke="currentColor" stroke-width="1.8" />
                </svg>
            </span>
            <span class="sb__label">Dashboard</span>
        </a>

        <a class="sb__item <?= is_active($seg1, $class, 'designs'); ?>" href="<?= base_url('designs'); ?>">
            <span class="sb__icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 3a9 9 0 0 0 0 18h3a3 3 0 0 0 0-6h-1.2a1.8 1.8 0 1 1 0-3.6H15a3 3 0 0 0 0-6h-3Z"
                        stroke="currentColor" stroke-width="1.8" />
                    <path d="M7.5 10.5h.01M9 7.5h.01M16.5 8.5h.01M8.5 13.8h.01"
                        stroke="currentColor" stroke-width="2.6"
                        stroke-linecap="round" />
                </svg>
            </span>
            <span class="sb__label">Jenis Desain</span>
        </a>

        <a class="sb__item <?= is_active($seg1, $class, 'prices'); ?>" href="<?= base_url('prices'); ?>">
            <span class="sb__icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M20 13l-7.6 7.6a2 2 0 0 1-2.8 0L3 13V4h9l8 9Z"
                        stroke="currentColor" stroke-width="1.8" />
                    <path d="M7.5 7.5h.01"
                        stroke="currentColor" stroke-width="3.2"
                        stroke-linecap="round" />
                </svg>
            </span>
            <span class="sb__label">Harga Matrix</span>
        </a>


        <a class="sb__item <?= is_active($seg1, $class, 'orders'); ?>" href="<?= base_url('orders'); ?>">
            <span class="sb__icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M8 6h13M8 12h13M8 18h13"
                        stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" />
                    <path d="M4 6h.01M4 12h.01M4 18h.01"
                        stroke="currentColor" stroke-width="3.2"
                        stroke-linecap="round" />
                </svg>
            </span>
            <span class="sb__label">Order</span>
        </a>

        <?php if (current_user_is_owner()): ?>
        <a class="sb__item <?= is_active($seg1, $class, 'app-content'); ?>" href="<?= base_url('app-content'); ?>">
            <span class="sb__icon"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="16" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M7 15l3-3 2.5 2.5L16 10l3 3.5M8 8h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="sb__label">Konten Aplikasi</span>
        </a>
        <?php endif; ?>

        <a class="sb__item <?= is_active($seg1, $class, 'clients'); ?>" href="<?= base_url('clients'); ?>">
            <span class="sb__icon">
                <svg viewBox="0 0 24 24" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="sb__label">Pelanggan</span>
        </a>

        <a class="sb__item <?= is_active($seg1, $class, 'payments'); ?>" href="<?= base_url('payments'); ?>">
            <span class="sb__icon"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 10h18M16 15h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
            <span class="sb__label">Pembayaran</span>
        </a>

        <?php if (current_user_is_owner()): ?>
        <a class="sb__item <?= is_active($seg1, $class, 'payment-methods'); ?>" href="<?= base_url('payment-methods'); ?>">
            <span class="sb__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
            <span class="sb__label">Metode Bayar</span>
        </a>
        <?php endif; ?>

        <a class="sb__item <?= is_active($seg1, $class, 'profile'); ?>" href="<?= base_url('profile'); ?>">
            <span class="sb__icon"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
            <span class="sb__label">Profil Saya</span>
        </a>

        <?php if (current_user_is_owner()): ?>
        <a class="sb__item <?= is_active($seg1, $class, 'team'); ?>" href="<?= base_url('team'); ?>">
            <span class="sb__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M8.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M15.5 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span class="sb__label">Tim Admin</span>
        </a>
        <?php endif; ?>

        <a class="sb__item <?= is_active($seg1, $class, 'drive-storage'); ?>" href="<?= base_url('drive-storage'); ?>">
            <span class="sb__icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M7 18h10a4 4 0 0 0 .7-7.94A6 6 0 0 0 6.2 8.1 5 5 0 0 0 7 18Z"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M12 12v5m0-5-2 2m2-2 2 2"
                        stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <span class="sb__label">Drive Storage</span>
        </a>

        <!-- AI Assistant dibuat sejajar dengan tombol menu lain -->
        <button class="sb__item sb__aiMenu" id="openAiBtn" type="button">
            <span class="sb__aiIcon">⚡</span>
            <span class="sb__label sb__aiText">AI Assistant</span>
        </button>
    </nav>

    <!-- ===== Sidebar Controls: theme + logout ===== -->
    <div class="sb__controls">
        <button
            id="themeModeToggle"
            class="theme-mode-toggle"
            type="button"
            aria-label="Ubah mode tampilan"
            aria-pressed="false">
            <span class="theme-mode-knob" aria-hidden="true"></span>
            <span class="theme-mode-label">Dark Mode</span>
        </button>

        <form class="sb__logoutForm" method="post" action="<?= base_url('logout'); ?>" data-confirm="Logout sekarang?">
            <?= csrf_field(); ?>
            <button class="sb__logout" type="submit">
                <span class="sb__logoutIcon">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M10 7V6a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-7a2 2 0 0 1-2-2v-1"
                            stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M15 12H3m0 0 3-3M3 12l3 3"
                            stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="sb__logoutLabel">Log out</span>
            </button>
        </form>
    </div>

    <!-- ===== Bottom User ===== -->
    <div class="sb__bottom">
        <div class="sb__bottomSep"></div>

        <div class="sb__user">
            <div class="sb__avatar"><?= html_escape(strtoupper(substr((string)$this->session->userdata('user_name'), 0, 1)) ?: 'A'); ?></div>
            <div class="sb__userText">
                <div class="sb__userName"><?= html_escape($this->session->userdata('user_name') ?: 'Admin'); ?></div>
                <div class="sb__userSub"><?= html_escape($this->session->userdata('user_role') ?: 'ADMIN'); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="container app">