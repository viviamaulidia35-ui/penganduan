# ZENIN — Aplikasi Pengaduan Sarana Sekolah
PHP native + PDO + MySQL (XAMPP). Role: Siswa & Admin.

## Cara Menjalankan (XAMPP)
1. Buka **XAMPP Control Panel**, klik **Start** pada **Apache** dan **MySQL**.
2. Salin folder `zenin` ke `C:\xampp\htdocs\` sehingga menjadi `C:\xampp\htdocs\zenin`.
3. Buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/zenin.sql` → **Go**.
   (File ini sudah berisi `CREATE DATABASE ZENIN`, jadi tidak perlu membuat database manual.)
4. Buka `http://localhost/zenin/` di browser.

Jika MySQL Anda memakai password, ubah `DB_PASS` di `config/database.php`.

## Akun Contoh
| Role  | Username | Password |
|-------|----------|----------|
| Admin | admin    | admin123 |
| Siswa | budi / sinta / raka / dewi | siswa123 |

## Fitur
- Login siswa & admin (`password_verify`, session, pembatasan akses per role, logout)
- Siswa: dashboard ringkasan status, buat pengaduan (+ foto bukti), riwayat, detail & umpan balik
- Admin: dashboard ringkasan, cari & filter (tanggal, bulan, murid, kategori, status), detail, ubah status + umpan balik
- Riwayat perubahan status tersimpan di tabel `riwayat_status`
- Keamanan: PDO prepared statements, validasi input, token CSRF, validasi tipe/ukuran foto, output di-escape

## Struktur
```
zenin/
├─ index.php  login.php  logout.php
├─ config/database.php
├─ includes/ (bootstrap, layout, detail_parts)
├─ siswa/    (dashboard, buat, riwayat, detail)
├─ admin/    (dashboard, detail)
├─ assets/   (css, js)
├─ uploads/  (foto bukti)
└─ database/zenin.sql
```

## Database ZENIN
`users` → `pengaduan` (user_id, kategori_id) → `riwayat_status` (pengaduan_id, admin_id); `kategori` → `pengaduan`.
