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
    <title><?= html_escape(($title ?? 'Login') . ' | ' . ($this->config->item('vi_app_name') ?: 'Vector Invoice')); ?></title>
    <link rel="icon" href="<?= base_url('assets/img/logo-ady.png'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/pages/login.css?v=20260619rev4'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/themes/theme-light.css?v=20260619rev4'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/pages/auth-polish.css?v=20260619rev4'); ?>">
</head>

<body class="login-auth-page">
    <div class="wrap">

        <div class="nav">
            <a href="#"><?= htmlspecialchars($brand_name ?? 'Ady_vandorez'); ?></a>
        </div>

        <div class="social" aria-hidden="true">
            <div class="soc">i</div>
            <div class="soc">x</div>
            <div class="soc">f</div>
        </div>

        <div class="content">
            <div class="left">
                <div class="brand">
                    <img src="<?= base_url('assets/img/logo-ady.png'); ?>" alt="Logo">
                    <div>
                        <div class="t1"><?= htmlspecialchars($brand_name ?? 'Ady_vandorez'); ?></div>
                        <div class="t2"><?= htmlspecialchars($brand_sub ?? 'Vector Order Manager'); ?></div>
                    </div>
                </div>

                <div class="login-card">
                    <div class="top-profile">
                        <div class="avatar-frame mobile-avatar">
                            <img src="<?= base_url('assets/img/maskot.jpg'); ?>" alt="Mascot">
                        </div>
                        <div class="name"><?= htmlspecialchars($brand_name ?? 'Ady_vandorez'); ?></div>
                        <div class="role"><?= htmlspecialchars($brand_sub ?? 'Vector Order Manager'); ?></div>
                    </div>

                    <h1>SELAMAT DATANG!</h1>
                    <div class="sub">
                        Masuk untuk mengelola order, harga, desain, dan invoice <b><?= htmlspecialchars($brand_name ?? 'Admin'); ?></b>.
                    </div>

                    <?php if ($this->session->flashdata('auth_err')): ?>
                        <div class="err flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('auth_err')); ?></div>
                    <?php endif; ?>

                    <?php if ($this->session->flashdata('auth_success')): ?>
                        <div class="ok flash-toast js-flash-toast"><?= html_escape($this->session->flashdata('auth_success')); ?></div>
                    <?php endif; ?>

                    <form class="form" method="post" action="<?= base_url('login'); ?>" autocomplete="on">
                        <?= csrf_field(); ?>

                        <div class="label">Email / Username</div>
                        <div class="field">
                            <input name="username" type="text" inputmode="email" placeholder="muhammadadimulyono@gmail.com" required autofocus autocomplete="username">
                        </div>

                        <div class="label">Password</div>
                        <div class="field">
                            <input id="pw" type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                            <span class="eye" id="togglePassword" title="Tampilkan password">👁️</span>
                        </div>

                        <div class="row2">
                            <label class="remember-label">
                                <input type="checkbox" name="remember" value="1">
                                Ingat saya
                            </label>

                            <a href="<?= base_url('forgot-password'); ?>">Lupa password?</a>
                        </div>

                        <button class="btn" type="submit">Masuk</button>
                    </form>
                </div>

                <div class="admin-account">
                    <div class="admin-logo">
                        <img src="<?= base_url('assets/img/logo-ady.png'); ?>" alt="Admin">
                    </div>
                    <div class="admin-text">Secure Admin Access</div>
                </div>
            </div>

            <div class="right">
                <div class="avatar-frame">
                    <img src="<?= base_url('assets/img/maskot.jpg'); ?>" alt="Mascot">
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/js/login.js?v=20260619rev4'); ?>"></script>
    <script src="<?= base_url('assets/js/flash-toast.js?v=20260619rev4'); ?>"></script>
</body>

</html>
