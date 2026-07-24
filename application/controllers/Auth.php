<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model', 'users');
        $this->load->helper('cookie');

        // Auto-login dari remember me. Token asli hanya disimpan di cookie,
        // sedangkan database menyimpan hash SHA-256 agar lebih aman.
        if (!$this->session->userdata('logged_in')) {
            $token = get_cookie('remember_token');
            if ($token) {
                $user = $this->users->find_by_remember_token($token);
                if ($user) {
                    $this->set_login_session($user);
                }
            }
        }
    }

    private function auth_view_data($title)
    {
        return [
            'title'      => $title,
            'brand_name' => $this->config->item('vi_brand_name'),
            'brand_sub'  => $this->config->item('vi_brand_tagline'),
        ];
    }

    /** Set session login pada satu tempat agar konsisten. */
    private function set_login_session($user)
    {
        // Regenerate session ketika login untuk mencegah session fixation.
        $this->session->sess_regenerate(true);

        $this->session->set_userdata([
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'username'  => $user->username,
            'user_email' => $user->email,
            'user_role'  => property_exists($user, 'role') ? $user->role : 'ADMIN',
            'logged_in' => true
        ]);
        $this->users->update_last_login($user->id);
    }

    /** Proteksi brute force sederhana berbasis session. */
    private function too_many_login_attempts()
    {
        $attempts = (int)$this->session->userdata('login_attempts');
        $lockedUntil = (int)$this->session->userdata('login_locked_until');

        if ($lockedUntil > time()) {
            return true;
        }

        if ($attempts >= 5) {
            $this->session->set_userdata('login_locked_until', time() + 300);
            return true;
        }

        return false;
    }

    private function register_failed_login()
    {
        $attempts = (int)$this->session->userdata('login_attempts');
        $this->session->set_userdata('login_attempts', $attempts + 1);
    }

    private function clear_failed_login()
    {
        $this->session->unset_userdata(['login_attempts', 'login_locked_until']);
    }

    private function set_remember_cookie($token)
    {
        set_cookie([
            'name'     => 'remember_token',
            'value'    => $token,
            'expire'   => 60 * 60 * 24 * 30,
            'path'     => '/',
            'httponly' => true,
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
        ]);
    }

    /** Ringkas debug email agar tidak menumpuk transcript SMTP panjang di UI. */
    private function normalize_email_debug($raw)
    {
        $text = html_entity_decode(strip_tags((string)$raw), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if ($text === '') {
            return 'Email belum terkirim. Cek konfigurasi SMTP Gmail.';
        }

        if (stripos($text, '535-5.7.8') !== false || stripos($text, 'Username and Password not accepted') !== false || stripos($text, 'Failed to authenticate password') !== false) {
            return 'Gmail menolak login SMTP. Gunakan App Password Gmail 16 karakter, bukan password login Gmail biasa. Pastikan 2-Step Verification aktif, lalu buat App Password baru.';
        }

        if (stripos($text, 'could not connect') !== false || stripos($text, 'Connection refused') !== false || stripos($text, 'Unable to connect') !== false || stripos($text, 'fsockopen') !== false) {
            return 'Server tidak bisa konek ke SMTP Gmail. Coba port 587 TLS atau port 465 SSL, lalu pastikan firewall/antivirus tidak memblokir koneksi.';
        }

        if (stripos($text, 'mail()') !== false) {
            return 'PHP mail() lokal tidak aktif. Untuk XAMPP/Windows, gunakan SMTP Gmail dengan App Password.';
        }

        return mb_substr($text, 0, 400);
    }

    private function is_gmail_smtp_config($config)
    {
        $host = strtolower((string)($config['smtp_host'] ?? ''));
        $user = strtolower((string)($config['smtp_user'] ?? ''));
        return strpos($host, 'gmail.com') !== false || substr($user, -10) === '@gmail.com';
    }

    private function sanitize_gmail_app_password($password)
    {
        return preg_replace('/\s+/', '', (string)$password);
    }

    private function looks_like_gmail_app_password($password)
    {
        $clean = $this->sanitize_gmail_app_password($password);
        return (bool)preg_match('/^[A-Za-z0-9]{16}$/', $clean);
    }

    private function attempt_reset_email_send($config, $to, $from, $fromName, $subject, $htmlMessage, $plainMessage, &$rawDebug = '')
    {
        $this->load->library('email');
        $this->email->clear(true);
        $this->email->initialize($config);
        $this->email->from($from, $fromName ?: $this->brandName);
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($htmlMessage);
        if (method_exists($this->email, 'set_alt_message')) {
            $this->email->set_alt_message($plainMessage);
        }

        $sent = (bool)$this->email->send(false);
        if (!$sent) {
            $rawDebug = (string)$this->email->print_debugger(['headers']);
        }
        return $sent;
    }

    /**
     * Kirim email reset password.
     * Untuk Gmail, password wajib App Password 16 karakter, bukan password login akun.
     * Saat development, link reset tetap ditampilkan di halaman agar testing tidak macet.
     */
    private function send_reset_email($to, $name, $link, &$debug = '')
    {
        $subject = 'Reset Password - ' . $this->brandSub;
        $safeName = $name ? $name : 'Admin';
        $escapedName = html_escape($safeName);
        $escapedLink = html_escape($link);

        $plainMessage = "Halo {$safeName},\n\n" .
            "Kami menerima permintaan reset password untuk akun Vector Order Manager.\n" .
            "Silakan buka link berikut untuk membuat password baru:\n\n" .
            $link . "\n\n" .
            "Link ini berlaku 1 jam. Abaikan email ini jika kamu tidak meminta reset password.\n\n" .
            "Salam,\n" . $this->brandName;

        $htmlMessage = '<div style="font-family:Arial,sans-serif;line-height:1.6;color:#222">'
            . '<h2 style="margin:0 0 12px;color:#b77a13">Reset Password</h2>'
            . '<p>Halo <strong>' . $escapedName . '</strong>,</p>'
            . '<p>Kami menerima permintaan reset password untuk akun <strong>Vector Order Manager</strong>.</p>'
            . '<p><a href="' . $escapedLink . '" style="display:inline-block;padding:12px 18px;background:#d99a22;color:#111;text-decoration:none;border-radius:10px;font-weight:bold">Buka Link Reset Password</a></p>'
            . '<p>Link ini berlaku 1 jam. Abaikan email ini jika kamu tidak meminta reset password.</p>'
            . '<p>Salam,<br><strong>' . html_escape($this->brandName) . '</strong></p>'
            . '<p style="font-size:12px;color:#777">Jika tombol tidak bisa dibuka, salin link ini:<br>' . $escapedLink . '</p>'
            . '</div>';

        log_message('info', 'Reset password link for ' . $to . ': ' . $link);

        $emailConfig = [];
        try {
            $this->config->load('email', true);
            $loaded = $this->config->item('email');
            if (is_array($loaded)) {
                $emailConfig = $loaded;
            }
        } catch (Throwable $e) {
            log_message('error', 'Load email config failed: ' . $e->getMessage());
        }

        $protocol = strtolower(trim((string)($emailConfig['protocol'] ?? getenv('EMAIL_PROTOCOL') ?: 'mail')));
        $smtpHost = trim((string)($emailConfig['smtp_host'] ?? getenv('SMTP_HOST') ?: ''));
        $smtpUser = trim((string)($emailConfig['smtp_user'] ?? getenv('SMTP_USER') ?: ''));
        $smtpPass = (string)($emailConfig['smtp_pass'] ?? getenv('SMTP_PASS') ?: '');

        if ($protocol === 'smtp') {
            if ($smtpHost === '' || $smtpUser === '' || trim($smtpPass) === '') {
                $debug = 'SMTP belum lengkap. Isi smtp_user dan smtp_pass/App Password di application/config/email.php.';
                log_message('error', 'Reset email not sent: incomplete SMTP configuration.');
                return false;
            }

            if ($this->is_gmail_smtp_config($emailConfig)) {
                $smtpPass = $this->sanitize_gmail_app_password($smtpPass);
                $emailConfig['smtp_pass'] = $smtpPass;

                if (!$this->looks_like_gmail_app_password($smtpPass)) {
                    $debug = 'SMTP_PASS yang diisi bukan App Password Gmail 16 karakter. Password login Gmail biasa akan ditolak oleh Gmail.';
                    log_message('error', 'Reset email not sent: Gmail SMTP password is not an app password format.');
                    return false;
                }
            }
        }

        $from = trim((string)($emailConfig['auth_email_from'] ?? getenv('AUTH_EMAIL_FROM') ?: ''));
        $fromName = trim((string)($emailConfig['auth_email_from_name'] ?? getenv('AUTH_EMAIL_FROM_NAME') ?: $this->brandName));
        if ($from === '' || strpos($from, 'localhost') !== false) {
            $from = $smtpUser ?: 'no-reply@localhost.test';
        }

        $configsToTry = [$emailConfig];
        if ($protocol === 'smtp' && $this->is_gmail_smtp_config($emailConfig)) {
            $currentHost = strtolower((string)($emailConfig['smtp_host'] ?? ''));
            $currentPort = (int)($emailConfig['smtp_port'] ?? 0);
            $currentCrypto = strtolower((string)($emailConfig['smtp_crypto'] ?? ''));

            // Fallback otomatis Gmail: jika 587 TLS gagal karena koneksi/crypto, coba 465 SSL.
            if (!($currentPort === 465 || strpos($currentHost, 'ssl://') === 0 || $currentCrypto === 'ssl')) {
                $sslConfig = $emailConfig;
                $sslConfig['smtp_host'] = 'ssl://smtp.gmail.com';
                $sslConfig['smtp_port'] = 465;
                $sslConfig['smtp_crypto'] = '';
                $configsToTry[] = $sslConfig;
            }
        }

        foreach ($configsToTry as $idx => $tryConfig) {
            try {
                $rawDebug = '';
                if ($this->attempt_reset_email_send($tryConfig, $to, $from, $fromName, $subject, $htmlMessage, $plainMessage, $rawDebug)) {
                    return true;
                }

                log_message('error', 'CI email reset failed attempt ' . ($idx + 1) . ': ' . strip_tags($rawDebug));
                $debug = $this->normalize_email_debug($rawDebug);

                // Kalau credential salah, jangan retry port lain karena hasilnya tetap gagal.
                if (stripos($debug, 'App Password') !== false || stripos($debug, 'menolak login SMTP') !== false) {
                    break;
                }
            } catch (Throwable $e) {
                $debug = $this->normalize_email_debug($e->getMessage());
                log_message('error', 'CI email reset exception: ' . $e->getMessage());
            }
        }

        // Fallback mail() hanya jika memang tidak memakai SMTP.
        if ($protocol !== 'smtp') {
            $headers = "From: " . $this->brandName . " <no-reply@localhost.test>\r\n";
            if (@mail($to, $subject, $plainMessage, $headers)) {
                return true;
            }
            if ($debug === '') {
                $debug = 'mail() server lokal tidak aktif. Gunakan SMTP Gmail/App Password.';
            }
        }

        return false;
    }

    public function login()
    {
        if ($this->session->userdata('user_id')) {
            redirect('dashboard');
        }

        if ($this->input->method(true) === 'POST') {
            if ($this->too_many_login_attempts()) {
                $this->session->set_flashdata('auth_err', 'Terlalu banyak percobaan login. Coba lagi beberapa menit.');
                redirect('login');
            }

            $identity = trim((string)$this->input->post('username', true));
            // Password tidak perlu XSS filter agar karakter khusus tidak berubah.
            $password = (string)$this->input->post('password', false);
            $remember = $this->input->post('remember');

            if ($identity === '' || $password === '') {
                $this->register_failed_login();
                $this->session->set_flashdata('auth_err', 'Email/username dan password wajib diisi.');
                redirect('login');
            }

            $user = $this->users->find_by_login_identity($identity);
            if (!$user || !password_verify($password, $user->password_hash)) {
                $this->register_failed_login();
                $this->session->set_flashdata('auth_err', 'Email/username atau password salah.');
                redirect('login');
            }

            $this->clear_failed_login();
            $this->set_login_session($user);

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $this->users->save_remember_token($user->id, $token);
                $this->set_remember_cookie($token);
            } else {
                $this->users->clear_remember_token($user->id);
                delete_cookie('remember_token');
            }

            redirect('dashboard');
        }

        $this->load->view('auth/login', $this->auth_view_data('Login'));
    }

    public function forgot_password()
    {
        $data = $this->auth_view_data('Lupa Password');

        if ($this->input->method(true) === 'POST') {
            $identity = trim((string)$this->input->post('email', true));
            $user = $this->users->find_by_email_or_username($identity);

            if ($user && !empty($user->email)) {
                $token = bin2hex(random_bytes(32));
                $this->users->save_reset_token($user->id, $token, date('Y-m-d H:i:s', time() + 3600));

                $link = base_url('reset-password/' . rawurlencode($token));
                $emailDebug = '';
                $sent = $this->send_reset_email($user->email, $user->name, $link, $emailDebug);

                if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
                    $this->session->set_flashdata('debug_reset_link', $link);
                    if (!$sent) {
                        $this->session->set_flashdata('email_debug_error', $emailDebug ?: 'Email belum terkirim. Cek konfigurasi SMTP Gmail.');
                    }
                }

                if (!$sent) {
                    log_message('error', 'Reset password email could not be sent to ' . $user->email . ' | ' . $emailDebug);
                }
            }

            // Pesan dibuat umum supaya tidak membocorkan apakah email terdaftar atau tidak.
            $this->session->set_flashdata('auth_success', 'Jika akun terdaftar, link reset password sudah diproses. Cek Inbox/Spam Gmail. Saat testing lokal, link reset juga tampil di bawah.');
            redirect('forgot-password');
        }

        $this->load->view('auth/forgot_password', $data);
    }

    public function logout()
    {
        // Logout tetap diizinkan dari GET untuk kompatibilitas link lama,
        // tetapi view sidebar baru sudah memakai POST + CSRF.
        $userId = $this->session->userdata('user_id');
        if ($userId) {
            $this->users->clear_remember_token($userId);
        }

        delete_cookie('remember_token');
        $this->session->sess_destroy();
        redirect('login');
    }

    public function reset_password($token = null)
    {
        $token = trim((string)$token);
        $user = $token !== '' ? $this->users->find_valid_reset_token($token) : null;

        if (!$user) {
            $this->session->set_flashdata('auth_err', 'Link reset password tidak valid atau sudah kedaluwarsa. Silakan minta link baru.');
            redirect('forgot-password');
        }

        $data = $this->auth_view_data('Reset Password');
        $data['token'] = $token;

        if ($this->input->method(true) === 'POST') {
            $plain = (string)$this->input->post('password', false);
            $confirm = (string)$this->input->post('password_confirm', false);

            if (strlen($plain) < 8) {
                $this->session->set_flashdata('auth_err', 'Password minimal 8 karakter.');
                redirect('reset-password/' . rawurlencode($token));
            }

            if ($plain !== $confirm) {
                $this->session->set_flashdata('auth_err', 'Konfirmasi password belum sama.');
                redirect('reset-password/' . rawurlencode($token));
            }

            $password = password_hash($plain, PASSWORD_DEFAULT);
            $this->users->update_password($user->id, $password);
            $this->session->set_flashdata('auth_success', 'Password berhasil diubah. Silakan login dengan password baru.');
            redirect('login');
        }

        $this->load->view('auth/reset_password', $data);
    }
}
