# Ady_vandorez Vector Order

Project ini berisi web admin CodeIgniter 3 dan REST API untuk aplikasi Android.

## Database lokal
- Host: localhost
- Port: 3308
- User: root
- Password: root
- Database: vector_invoice

Import `database/vector_invoice_final.sql` satu kali pada database bersih. Seluruh data lama tetap berada di file tersebut.

## URL penting
- Web admin: `http://localhost/vector-invoice/`
- Demo: `http://localhost/vector-invoice/demo`
- API health: `http://localhost/vector-invoice/api/health`
- API docs: `http://localhost/vector-invoice/api/docs`

## USB Android
Jalankan `adb reverse tcp:80 tcp:80`. Base URL Android debug: `http://127.0.0.1/vector-invoice/`.

## Konten aplikasi Android — Stitch Edition 2.3.0

- Menu admin: `http://localhost/vector-invoice/app-content`
- Endpoint publik: `GET http://localhost/vector-invoice/api/home-content`

Menu **Konten Aplikasi** tersedia untuk akun OWNER dan mengatur:

- Identitas dan teks aplikasi
- Banner hero beranda
- Promosi beserta jadwal tayang
- Portofolio pilihan
- Minimal DP
- Batas dan biaya revisi
- Biaya CDR, lisensi eksklusif, background kompleks, dan express
- Kontak bantuan, syarat layanan, dan kebijakan privasi

Folder upload:

- `assets/uploads/mobile/banners`
- `assets/uploads/mobile/promotions`
- `assets/uploads/mobile/portfolios`

Import `database/vector_invoice_final.sql` pada database baru agar tabel konten mobile dan data awal tersedia.
