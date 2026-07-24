# Final Stitch Handoff — Web 2.3.0

## Instalasi

1. Salin folder web ke `htdocs/vector-invoice`.
2. Buat database kosong bernama `vector_invoice`.
3. Import `database/vector_invoice_final.sql`.
4. Sesuaikan koneksi database di konfigurasi project bila port/user/password lokal berbeda.
5. Login sebagai OWNER, lalu buka menu **Konten Aplikasi**.

## Konten Android yang dapat diatur

- Identitas, sapaan, hero title, label section, kontak, dan URL dokumen.
- Banner hero dan tindakan tombol.
- Promosi, badge, gambar, jadwal, dan tindakan tombol.
- Portofolio, kategori, gambar, urutan, status aktif, dan featured.
- Minimal DP, revisi, CDR, lisensi, background kompleks, dan express.

Endpoint lama tidak diubah. Endpoint baru: `GET /api/home-content`.
