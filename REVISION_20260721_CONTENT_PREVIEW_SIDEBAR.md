# Revisi Web Admin — 21 Juli 2026

Perubahan hanya pada antarmuka web admin dan pengambilan metadata preview. Endpoint REST API Android, autentikasi token, package Android, serta struktur transaksi tidak diubah.

## Perubahan

1. Halaman **Konten Aplikasi** disusun ulang agar lebih mudah dipahami:
   - ringkasan jumlah konten aktif;
   - alur pengaturan 1–4;
   - kelompok identitas, biaya, dan kontak;
   - pengelolaan banner, promosi, dan portofolio dalam card yang responsif;
   - form edit dapat dibuka/tutup;
   - bantuan isian aksi tombol;
   - tampilan desktop, tablet, dan mobile dirapikan.
2. Preview pada halaman **Jenis Desain** dapat diklik dan dibuka dalam lightbox.
3. Setiap item pada **Detail Order** menampilkan preview dari jenis desain terkait dan dapat dibuka dalam lightbox.
4. Urutan sidebar menjadi Dashboard → Jenis Desain → Harga Matrix → Order → menu lainnya.
5. Ditambahkan komponen lightbox bersama yang aman digunakan oleh halaman admin.

## Pemeriksaan

- Seluruh file PHP dalam folder `application` lolos `php -l`.
- File JavaScript baru lolos `node --check`.
- Tidak ada perubahan database, sehingga SQL v2.3.0/401-fixed tetap digunakan.
