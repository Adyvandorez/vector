# Revisi 2026-06-19 - Fix Nama File PDF Nota

Fokus revisi:
1. Tombol Download PDF nota tidak lagi memakai dialog print browser sebagai jalur utama.
2. PDF sekarang dibuat otomatis dari tampilan nota menggunakan canvas browser lalu diunduh sebagai file PDF.
3. Nama file PDF dipaksa memakai format nama klien dan judul order, contoh: `Nota-Nama-Klien-Judul-Order.pdf`.
4. `document.title` tetap diset sesuai nama nota sebagai fallback untuk print browser.
5. Download JPG tetap memakai mekanisme fallback browser yang sudah ada.

Catatan:
- Revisi ini hanya menyentuh file tampilan dan JavaScript nota.
- Tidak ada perubahan database/SQL.
- Tidak ada perubahan alur data order, invoice, pembayaran, atau Drive Storage.
