<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= html_escape(($title ?? 'Dashboard') . ' | ' . ($this->config->item('vi_app_name') ?: 'Vector Invoice')); ?></title>

    <!-- CSRF token untuk form dan AJAX (terutama AI Assistant). -->
    <meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name(); ?>">
    <meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash(); ?>">

    <!-- Theme mode bootstrap: default tetap dark, light hanya aktif jika user memilih. -->
    <script id="vi-theme-mode-bootstrap">
        (function () {
            try {
                var saved = localStorage.getItem('vi_theme_mode') || localStorage.getItem('vi_theme');
                var mode = saved === 'light' ? 'light' : 'dark';
                document.documentElement.classList.add(mode === 'light' ? 'vi-light-mode' : 'vi-dark-mode');
                document.documentElement.setAttribute('data-theme', mode);
            } catch (e) {
                document.documentElement.classList.add('vi-dark-mode');
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    <!-- =========================================================
         GLOBAL CSS TERSTRUKTUR
         - themes/     : dark mode, light mode, theme lama
         - mobile/     : seluruh CSS khusus mobile
         - base/       : utility/helper kecil
         - components/ : sidebar, AI, polish komponen
         - pages/      : CSS khusus halaman
    ========================================================== -->
    <link rel="stylesheet" href="<?= base_url('assets/css/themes/theme-dark.css?v=20260721ui1'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/mobile/mobile.css?v=20260721ui1'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/base/utilities.css?v=20260721ui1'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/components/sidebar.css?v=20260721ui1'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/components/ai.css?v=20260721ui1'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/components/ai-input-mode.css?v=20260620ui3'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/components/image-lightbox.css?v=20260721ui1'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/mobile/ai-mobile.css?v=20260620ui3'); ?>">

    <?php
        $css_manifest = [
            'dashboard.css' => 'pages/dashboard.css',
            'dashboard-mobile.css' => 'mobile/dashboard-mobile.css',
            'designs-mobile.css' => 'mobile/designs-mobile.css',
            'drive-storage.css' => 'pages/drive-storage.css',
            'drive-storage-mobile.css' => 'mobile/drive-storage-mobile.css',
            'orders-mobile.css' => 'mobile/orders-mobile.css',
            'orders-form.css' => 'pages/orders-form.css',
            'orders-view.css' => 'pages/orders-view.css',
            'prices-mobile.css' => 'mobile/prices-mobile.css',
            'admin.css' => 'pages/admin.css',
            'mobile-content.css' => 'pages/mobile-content.css',
        ];
    ?>

    <?php if (!empty($page_css) && is_array($page_css)): ?>
        <!-- =====================================================
             PAGE CSS
             Tambahkan nama file melalui $data['page_css'] di controller.
             Contoh: ['dashboard.css'] akan load assets/css/dashboard.css
        ====================================================== -->
        <?php foreach ($page_css as $css): ?>
            <?php $css_file = $css_manifest[$css] ?? $css; ?>
            <link rel="stylesheet" href="<?= base_url('assets/css/' . $css_file . '?v=20260721ui1'); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- LIGHT MODE OVERRIDE: hanya aktif saat html.vi-light-mode. Dark mode tidak disentuh. -->
    <link rel="stylesheet" href="<?= base_url('assets/css/themes/theme-light.css?v=20260620ui3'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/components/ui-polish.css?v=20260620ui3'); ?>">
</head>

<body data-base-url="<?= base_url(); ?>">

    <!-- MOBILE HAMBURGER -->
    <button
        id="sbToggle"
        class="sb-toggle mobile-only"
        aria-label="Toggle Menu"
        aria-expanded="false">

        <svg viewBox="0 0 24 24" fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round">
            <line x1="3" y1="6" x2="21" y2="6" />
            <line x1="3" y1="12" x2="21" y2="12" />
            <line x1="3" y1="18" x2="21" y2="18" />
        </svg>
    </button>
