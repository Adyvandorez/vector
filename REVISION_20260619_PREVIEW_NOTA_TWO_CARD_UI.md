# Revisi 2026-06-19 — Preview Nota Two Card UI

Fokus revisi hanya tampilan Preview Nota dan tidak mengubah alur order/invoice.

## Perubahan
1. Halaman Preview Nota sekarang memakai layout web utama, sehingga mode dark/light mengikuti mode global dari sidebar.
2. Pengaturan mode mandiri di halaman nota dihapus agar tidak bentrok dengan mode web.
3. Layout desktop dibuat menjadi 2 card sejajar:
   - Card Download PDF
   - Card Download JPG
4. Masing-masing card memiliki area scroll sendiri agar nota panjang tetap bisa dilihat tanpa merusak layout.
5. Layout mobile dibuat 1 kolom bertumpuk dengan area scroll per card.
6. Tombol Download PDF/JPG tersedia di toolbar atas dan di masing-masing card.
7. Menu Orders otomatis aktif saat membuka halaman Preview Nota dari controller Invoices.
8. Warna card logo Ady_vandorez pada sidebar mobile light disesuaikan dengan referensi.

## File yang diubah
- application/controllers/Invoices.php
- application/views/invoices/print.php
- application/views/layout/sidebar.php
- application/views/layout/header.php
- application/views/layout/footer.php
- assets/css/pages/invoice-print.css
- assets/css/mobile/mobile.css
- assets/js/invoice-print.js
