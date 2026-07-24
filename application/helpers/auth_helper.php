<?php
defined('BASEPATH') or exit('No direct script access allowed');

function require_login()
{
    $ci = &get_instance();
    if (!$ci->session->userdata('user_id')) {
        redirect('login');
    }
}

function current_user_id()
{
    $ci = &get_instance();
    return (int)$ci->session->userdata('user_id');
}

function current_user_role()
{
    $ci = &get_instance();
    return strtoupper((string)($ci->session->userdata('user_role') ?: 'STAFF'));
}

function current_user_is_owner()
{
    return current_user_role() === 'OWNER';
}

function require_owner()
{
    if (!current_user_is_owner()) {
        show_error('Halaman ini hanya dapat diakses oleh pemilik akun.', 403, 'Akses ditolak');
    }
}

function csrf_field()
{
    $ci = &get_instance();

    if (!$ci->config->item('csrf_protection')) {
        return '';
    }

    $name = $ci->security->get_csrf_token_name();
    $hash = $ci->security->get_csrf_hash();

    return '<input type="hidden" name="' . html_escape($name) . '" value="' . html_escape($hash) . '">';
}

function require_post()
{
    $ci = &get_instance();
    if (strtoupper($ci->input->method(true)) !== 'POST') {
        show_error('Method not allowed', 405);
    }
}

function normalize_phone($phone)
{
    $phone = trim((string)$phone);
    if ($phone === '') return '';
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (strpos($phone, '+62') === 0) return '0' . substr($phone, 3);
    if (strpos($phone, '62') === 0) return '0' . substr($phone, 2);
    return $phone;
}
