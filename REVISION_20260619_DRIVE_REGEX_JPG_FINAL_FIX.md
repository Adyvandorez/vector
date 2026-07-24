# Revision 2026-06-19 - Drive Regex + JPG Export Final Fix

Fokus revisi ini hanya bugfix tanpa mengubah alur web.

## Diperbaiki

1. Drive Storage migration error
   - Memperbaiki regex `safe_name()` dan `safe_filename()` di `application/libraries/Google_drive_storage.php`.
   - Error sebelumnya: `preg_replace(): Unknown modifier '\\'`.
   - Regex diganti memakai delimiter `#` agar aman untuk karakter slash/backslash di Windows/XAMPP.

2. Download JPG nota
   - Tombol JPG tidak lagi membuka endpoint server secara otomatis ketika browser export gagal.
   - Ditambahkan export JPG berbasis Canvas manual dari data nota, sehingga tidak bergantung ke PHP GD dan tidak membuka halaman `Situs tidak tersedia`.
   - Nama file tetap mengikuti nama klien dan judul order.

3. Download PDF nota
   - PDF tetap memakai download otomatis dengan nama file nota.
   - Export PDF memakai canvas nota yang sama agar stabil.

## File utama yang berubah

- `application/libraries/Google_drive_storage.php`
- `application/views/invoices/print.php`
- `assets/js/invoice-print.js`

## Validasi

- PHP lint: OK
- JavaScript syntax check: OK
