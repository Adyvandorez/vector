# Hotfix Android HTTP 401

Perbaikan kompatibilitas Bearer Token untuk Apache/XAMPP/CGI:

- `.htaccess` meneruskan header `Authorization` ke PHP.
- API menerima fallback `X-Authorization`.
- CORS mengizinkan `X-Authorization`.
- API juga membaca `REDIRECT_HTTP_AUTHORIZATION`.

Tidak ada endpoint, package Android, tabel, atau aturan integrasi lama yang dihapus.
