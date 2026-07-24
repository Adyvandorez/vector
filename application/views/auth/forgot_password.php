<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    <title><?= htmlspecialchars($title ?? 'Lupa Password'); ?></title>
    <link rel="icon" href="<?= base_url('assets/img/logo-ady.png'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/pages/forget.css?v=20260619rev3'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/themes/theme-light.css?v=20260619rev3'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/pages/auth-polish.css?v=20260619rev3'); ?>">
</head>

<body>
    <main class="auth-shell">
        <section class="auth-panel">
            <div class="brand-mini">
                <img src="<?= base_url('assets/img/logo-ady.png'); ?>" alt="Logo">
                <div>
                    <strong><?= htmlspecialchars($brand_name ?? 'Ady_vandorez'); ?></strong>
                    <span><?= htmlspecialchars($brand_sub ?? 'Vector Order Manager'); ?></span>
                </div>
            </div>

            <div class="auth-card">
                <div class="icon-badge">🔐</div>
                <p class="eyebrow">Keamanan Akun</p>
                <h1>Lupa Password?</h1>
                <p class="desc">
                    Masukkan email atau username akun admin. Sistem akan membuat link reset password yang berlaku selama 1 jam.
                </p>

                <?php if ($this->session->flashdata('auth_err')): ?>
                    <div class="alert alert-error flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('auth_err')); ?></div>
                <?php endif; ?>

                <?php if ($this->session->flashdata('auth_success')): ?>
                    <div class="alert alert-success flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('auth_success')); ?></div>
                <?php endif; ?>

                <?php if ($this->session->flashdata('email_debug_error')): ?>
                    <div class="alert alert-error">
                        Email belum terkirim ke Gmail.<br>
                        Detail: <?= html_escape($this->session->flashdata('email_debug_error')); ?>
                    </div>
                <?php endif; ?>

                <?php if ($this->session->flashdata('debug_reset_link')): ?>
                    <div class="alert alert-debug">
                        Mode development: <a href="<?= html_escape($this->session->flashdata('debug_reset_link')); ?>">buka link reset password</a>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('forgot-password'); ?>" autocomplete="on">
                    <?= csrf_field(); ?>

                    <label>Email / Username</label>
                    <input type="text" name="email" class="field" placeholder="ady_vandorez@gmail.com" required autocomplete="username">

                    <button type="submit" class="btn">Kirim Link Reset</button>
                </form>

                <div class="back-link">
                    <a href="<?= base_url('login'); ?>">← Kembali ke halaman login</a>
                </div>
            </div>
        </section>

        <aside class="auth-visual" aria-hidden="true">
            <div class="visual-card">
                <img src="<?= base_url('assets/img/maskot.jpg'); ?>" alt="Mascot">
            </div>
        </aside>
    </main>
    <script src="<?= base_url('assets/js/flash-toast.js?v=20260619rev3'); ?>"></script>
</body>

</html>
