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
    <title><?= htmlspecialchars($title ?? 'Reset Password'); ?></title>
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
                <div class="icon-badge">✅</div>
                <p class="eyebrow">Reset Password</p>
                <h1>Buat Password Baru</h1>
                <p class="desc">
                    Gunakan password minimal 8 karakter. Setelah disimpan, token reset otomatis dinonaktifkan.
                </p>

                <?php if ($this->session->flashdata('auth_err')): ?>
                    <div class="alert alert-error flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('auth_err')); ?></div>
                <?php endif; ?>

                <form method="post" action="<?= current_url(); ?>" autocomplete="off">
                    <?= csrf_field(); ?>

                    <label>Password Baru</label>
                    <div class="password-wrap">
                        <input id="newPassword" type="password" name="password" class="field" placeholder="Minimal 8 karakter" required autocomplete="new-password">
                        <button type="button" class="toggle-eye" data-target="newPassword">👁️</button>
                    </div>

                    <label>Konfirmasi Password</label>
                    <div class="password-wrap">
                        <input id="confirmPassword" type="password" name="password_confirm" class="field" placeholder="Ulangi password baru" required autocomplete="new-password">
                        <button type="button" class="toggle-eye" data-target="confirmPassword">👁️</button>
                    </div>

                    <button type="submit" class="btn">Simpan Password Baru</button>
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

    <script src="<?= base_url('assets/js/auth-password.js?v=20260619rev3'); ?>"></script>
    <script src="<?= base_url('assets/js/flash-toast.js?v=20260619rev3'); ?>"></script>
</body>

</html>
