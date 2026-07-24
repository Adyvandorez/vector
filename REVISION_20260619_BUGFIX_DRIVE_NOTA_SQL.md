# Revisi 2026-06-19 - Bugfix Drive Storage, Nota JPG, dan SQL Re-Migrate

Fokus revisi: memperbaiki bug tanpa mengubah alur utama web.

## 1. Print Nota - Download JPG
- Tombol Download JPG sekarang memakai fallback browser berbasis Canvas/SVG dari tampilan nota.
- Jika ekstensi PHP GD di server belum aktif, tombol tetap mencoba membuat JPG dari sisi browser.
- Endpoint server `invoices/download_jpg/{id}` tetap dipertahankan sebagai fallback.
- Header download server diperkuat dengan `filename` dan `filename*` agar nama file lebih konsisten.
- Nama file otomatis mengikuti nama klien dan judul order, contoh: `Nota-Firman-Vektor-Art.jpg`.

File diubah:
- `application/views/invoices/print.php`
- `assets/js/invoice-print.js`
- `application/controllers/Invoices.php`

## 2. Drive Storage
- Sinkronisasi file Drive yang sudah ada dibuat lebih fleksibel.
- Sistem sekarang mencoba beberapa kandidat nama file:
  - nama standar aplikasi,
  - nama file lokal/random,
  - nama asli upload,
  - versi safe filename.
- Migrasi Drive mencegah duplikat: jika file dengan nama sama sudah ada di folder tujuan, sistem memakai file tersebut dan tidak upload ulang.
- File Drive yang ditemukan/sudah ada juga dicoba dibuat public agar preview URL lebih aman terbaca.

File diubah:
- `application/controllers/DriveStorage.php`
- `application/libraries/Google_drive_storage.php`

## 3. SQL Clean Re-Migrate
- Data utama tetap dipertahankan: client, order, item, pembayaran, harga, desain, invoice tidak dihapus.
- Data OAuth/token Google Drive dikosongkan untuk keamanan.
- Metadata Drive lama dikosongkan agar migrasi ulang tidak dilewati setelah file Drive lama dihapus.
- `preview_thumb` jenis desain diisi sesuai file thumbnail lokal yang sudah tersedia di ZIP.

File SQL baru:
- `database/vector_invoice_clean_remigrate.sql`
- `database/vector_invoice.sql` juga diarahkan ke versi clean re-migrate agar tidak bingung saat import.

Catatan:
- Setelah import SQL clean, hubungkan ulang Google Drive dari halaman Drive Storage.
- Karena Drive ID lama dikosongkan, proses migrasi akan upload ulang file ke Drive dan menghindari duplikasi dari data lama.
