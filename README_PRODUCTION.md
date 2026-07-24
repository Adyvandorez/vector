# Vector Invoice

Vector Invoice adalah sistem pengelolaan pesanan jasa desain berbasis CodeIgniter 3 dan MySQL. Project ini mempertahankan fitur operasional lama dan menambahkan fondasi pelanggan terdaftar agar pada tahap selanjutnya dapat dihubungkan ke REST API dan aplikasi Android.

## Fitur web saat ini

- Login aman menggunakan email atau username.
- Dashboard pemasukan, tagihan, order, pelanggan, dan sumber order.
- CRUD pelanggan tanpa menghapus riwayat transaksi.
- Pelanggan manual untuk order WhatsApp dan pelanggan terdaftar untuk aplikasi.
- CRUD jenis desain, deskripsi layanan, harga, dan pesanan multi-item.
- Pembayaran bertahap, revisi, file preview/source, Google Drive, dan invoice.
- Catatan pelanggan dan catatan internal admin.
- Riwayat perubahan status serta notifikasi pelanggan di database.
- Pengelolaan tim dengan role OWNER, ADMIN, dan STAFF.
- Profil admin dan penggantian password.
- Tampilan desktop serta mobile.

## Kebutuhan

- PHP 7.4 atau lebih baru. PHP 8.x direkomendasikan.
- MySQL/MariaDB.
- Apache dengan `mod_rewrite`.
- Ekstensi PHP: mysqli, mbstring, fileinfo, gd, curl, openssl, json.

## Instalasi XAMPP

1. Ekstrak folder project ke `C:/xampp/htdocs/vector-invoice`.
2. Aktifkan Apache dan MySQL.
3. Buka phpMyAdmin.
4. Import `database/vector_invoice_production.sql`.
5. Periksa `application/config/database.php`.
6. Buka `http://localhost/vector-invoice/`.

Konfigurasi bawaan XAMPP:

```php
'hostname' => 'localhost',
'port'     => '3308',
'username' => 'root',
'password' => 'root',
'database' => 'vector_invoice',
```

## Login awal

- Username: `muhammadadimulyono@gmail.com`
- Password: `muhammadadimulyono16.`

Ganti password melalui **Profil Saya** setelah login pertama.

## Konfigurasi server produksi

Project mendukung environment variable berikut:

- `CI_ENV=production`
- `APP_KEY=<kunci-acak-panjang>`
- `DB_HOST`
- `DB_PORT`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DB_DATABASE`
- `VI_APP_NAME`
- `VI_BRAND_NAME`
- `VI_BRAND_TAGLINE`
- `VI_OWNER_NAME`
- `VI_OWNER_EMAIL`
- `VI_TIMEZONE`

Jangan mengunggah database, OAuth token, atau konfigurasi rahasia ke repository publik.

## Tahap integrasi berikutnya

Database sudah menyiapkan `api_tokens`, akun pelanggan, notifikasi, sumber order, dan histori status. REST API dan aplikasi Android dikerjakan setelah web admin ini berhasil diuji pada localhost.
