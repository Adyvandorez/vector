<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Vector Invoice application settings
| -------------------------------------------------------------------------
| Keep business identity and non-secret defaults in one place. Secrets such
| as database passwords, SMTP passwords and OAuth credentials should remain
| in environment variables or their dedicated configuration files.
*/
$config['vi_app_name'] = getenv('VI_APP_NAME') ?: 'Vector Invoice';
$config['vi_brand_name'] = getenv('VI_BRAND_NAME') ?: 'Ady_vandorez';
$config['vi_brand_tagline'] = getenv('VI_BRAND_TAGLINE') ?: 'Vector Order Manager';
$config['vi_owner_name'] = getenv('VI_OWNER_NAME') ?: 'Muhammad Adi Mulyono';
$config['vi_owner_email'] = getenv('VI_OWNER_EMAIL') ?: 'muhammadadimulyono@gmail.com';
$config['vi_currency'] = 'IDR';
$config['vi_timezone'] = getenv('VI_TIMEZONE') ?: 'Asia/Jakarta';
$config['vi_order_statuses'] = ['MASUK', 'PROSES', 'REVISI', 'SELESAI'];
$config['vi_order_sources'] = ['WEB_ADMIN', 'ANDROID'];

if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set($config['vi_timezone']);
}

$config['vi_linktree_url'] = getenv('VI_LINKTREE_URL') ?: 'https://linktr.ee/Ady_vandorez';
$config['vi_support_whatsapp'] = getenv('VI_SUPPORT_WHATSAPP') ?: '085236222785';
$config['vi_cors_origin'] = getenv('VI_CORS_ORIGIN') ?: '*';
