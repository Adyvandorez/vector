# Revision 20260619 - Preview Nota Mobile Header & Flow

Fokus revisi hanya tampilan halaman Preview Nota, tanpa mengubah alur backend, database, download PDF/JPG, Drive Storage, atau struktur order.

## Perubahan

1. Header Preview Nota
   - Tombol Download PDF dan Download JPG di header halaman dihapus untuk desktop dan mobile.
   - Header desktop hanya menyisakan tombol Kembali.
   - Header mobile menyembunyikan icon nota dan tombol Kembali.
   - Header mobile hanya menampilkan judul Preview Nota dan baris invoice/order.

2. Mobile Layout
   - Catatan format download disembunyikan di mobile.
   - Area nota di mobile tidak lagi memakai scroll internal.
   - Nota mengikuti tinggi isi secara full agar halaman utama yang discroll.

3. Desktop Layout
   - Layout dua card tetap dipertahankan.
   - Scroll internal card tetap aktif di desktop/tablet agar nota panjang tidak merusak layout.

## File yang diubah

- application/views/invoices/print.php
- assets/css/pages/invoice-print.css
