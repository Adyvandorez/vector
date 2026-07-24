<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Email Configuration - Reset Password
| -------------------------------------------------------------------------
| Untuk Gmail SMTP, smtp_pass WAJIB memakai App Password Gmail 16 karakter,
| bukan password login Gmail biasa. Jika App Password ditulis dengan spasi,
| sistem akan otomatis menghapus spasinya sebelum dikirim ke SMTP.
|
| Cara pakai Gmail:
| 1. Aktifkan 2-Step Verification di akun Google.
| 2. Buka App Passwords / Sandi Aplikasi.
| 3. Buat sandi aplikasi baru untuk Mail.
| 4. Isi smtp_user dengan Gmail dan smtp_pass dengan App Password 16 karakter.
| -------------------------------------------------------------------------
*/

$config['smtp_user'] = getenv('SMTP_USER') ?: 'muhammadadimulyono@gmail.com';
$config['smtp_pass'] = getenv('SMTP_PASS') ?: ''; // ISI APP PASSWORD GMAIL 16 KARAKTER, bukan password Gmail biasa.

// Pakai SMTP Gmail secara eksplisit agar XAMPP/Windows tidak jatuh ke PHP mail().
$config['protocol'] = getenv('EMAIL_PROTOCOL') ?: 'smtp';
$config['smtp_host'] = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$config['smtp_port'] = getenv('SMTP_PORT') ?: 587;
$config['smtp_crypto'] = getenv('SMTP_CRYPTO') ?: 'tls';
$config['smtp_timeout'] = 30;
$config['smtp_keepalive'] = false;

$config['mailtype'] = 'html';
$config['charset']  = 'utf-8';
$config['newline']  = "\r\n";
$config['crlf']     = "\r\n";
$config['wordwrap'] = true;

// Sender ikut akun SMTP Gmail supaya tidak ditolak.
$config['auth_email_from'] = getenv('AUTH_EMAIL_FROM') ?: ($config['smtp_user'] ?: 'no-reply@localhost.test');
$config['auth_email_from_name'] = getenv('AUTH_EMAIL_FROM_NAME') ?: 'Ady_vandorez';
