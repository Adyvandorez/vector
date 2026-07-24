<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Google Drive Storage - Vector Invoice
| -------------------------------------------------------------------------
| Mode baru: OAuth 2.0 memakai akun Google pemilik Drive.
| Cara ini lebih cocok untuk Google Drive pribadi karena file memakai kuota akun user,
| bukan kuota Service Account.
*/

$config['google_drive_enabled'] = true;
$config['google_drive_auth_mode'] = 'oauth';
$config['google_drive_root_folder_id'] = '1AozTUK0O1E6IRL6iUrP8veHtDjkUGK-l';
$config['google_drive_oauth_client_path'] = APPPATH . 'config/google/oauth-client.json';
$config['google_drive_make_public'] = true;
$config['google_drive_delete_local_after_upload'] = false;
$config['google_drive_max_migrate_per_run'] = 1;
$config['google_drive_max_cleanup_per_run'] = 10;
$config['google_drive_max_sync_per_run'] = 10;
$config['google_drive_oauth_scope'] = 'https://www.googleapis.com/auth/drive';
$config['google_drive_ssl_verify'] = false; // Lokal/XAMPP sering belum punya CA bundle. Untuk hosting production boleh ubah true.
