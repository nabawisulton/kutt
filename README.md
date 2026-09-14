# KUTT SUKA MAKMUR — Sistem Informasi Koperasi

Aplikasi web koperasi **Koperasi Usaha Tani Ternak Suka Makmur Grati, Pasuruan** —
portal publik CMS + dashboard admin multi-user, PHP + MySQL, siap deploy di cPanel.

```
public/                 DOCUMENT ROOT (satu-satunya entry HTTP + aset)
  index.php             Front controller + tabel route
  router.php            Router untuk PHP built-in dev server
  .htaccess             Rewrite cPanel/Apache -> index.php
  uploads/              Hasil upload (foto anggota, galeri, kartu)
src/
  bootstrap.php         Autoload, env, session aman
  Core/                 Router, Database(PDO), View, Auth, Csrf, Roles, Audit, Validator
  Controllers/          Portal, Auth, Dashboard, Member, Card, News, PublicNews,
                        Cms, Settings, User, Fin (simpanan/pinjaman/kas/akuntansi/SHU),
                        Misc, Report, Lookup
  Models/               Akses data (prepared statements saja)
  Support/              Env, helpers, Uploader, ExcelExport, PdfExport, HtmlSanitizer
  views/                Template PHP (layout dashboard, portal publik, semua modul)
database/
  migrations/           Skema SQL idempoten (CREATE TABLE IF NOT EXISTS, tanpa DROP)
  seeds/                Data awal (user, COA, kategori berita, konten portal)
  migrate.php           Runner migrasi + seed + harmonisasi skema
storage/                Log runtime (tidak di-deploy)
tests/run.php           Test runner mandiri (php tests/run.php)
vendor/                 phpqrcode + fpdf (dibundel, tanpa composer)
```

## Fitur

- **Portal publik** (`/`): landing page lengkap dengan desain lama — hero carousel,
  profil/visi, unit usaha, galeri + lightbox, video YouTube, katalog produk,
  simulasi SHU, kontak & peta. Semua konten dikelola via **CMS Landing Page**.
- **Portal berita**: admin CRUD + draft/publish, kategori, komentar + moderasi,
  views/likes/shares, halaman publik `/berita` + detail + metadata Open Graph.
- **Master anggota**: CRUD, foto, pencarian/filter/pagination, kartu anggota
  (QR code + halaman verifikasi token), scan QR, profil anggota dengan
  informasi keuangan sesuai permission.
- **Simpanan**: setoran/penarikan pokok/wajib/sukarela, saldo per jenis, bukti no. transaksi.
- **Pinjaman & angsuran**: pengajuan → approval → pencairan (jadwal otomatis FLAT/
  MENURUN) → pembayaran angsuran + denda → status LUNAS otomatis.
- **Kas**: voucher masuk/keluar per kategori akun, saldo berjalan.
- **Akuntansi**: jurnal double-entry otomatis dari setiap transaksi + jurnal manual
  dengan validasi seimbang, buku besar rekap per akun.
- **SHU**: kalkulasi laba dari jurnal, distribusi jasa modal/jasa anggota, simpan hasil.
- **User management**: akun multi-user, role SUPER_ADMIN/ADMIN/BENDAHARA/KETUA/STAFF,
  aktivasi, reset password, proteksi SUPER_ADMIN terakhir.
- **Sistem**: notifikasi + bell navbar, log aktivitas, dark mode, dialog konfirmasi UI,
  export Excel (SpreadsheetML) & PDF (FPDF) + cetak di semua modul.

## Menjalankan (development)

```bash
php database/migrate.php                       # migrasi + seed (idempoten)
php -S 0.0.0.0:8080 -t public public/router.php
```

Buka `http://localhost:8080` — portal publik. Dashboard di `/login`.

### Akun seed

| Username  | Password | Role        |
|-----------|----------|-------------|
| admin     | admin123 | SUPER_ADMIN |
| bendahara | admin123 | BENDAHARA   |
| ketua     | admin123 | KETUA       |
| staff     | admin123 | STAFF       |

> Wajib ganti password default setelah login pertama (menu Kelola User).

## Instalasi di cPanel

1. **Upload** seluruh isi proyek ke `public_html/` (atau subdomain). Struktur yang
   harus terlihat: `public_html/public/…`, `public_html/src/…`, dst.
2. **Document root** arahkan ke `public_html/public` bila memungkinkan
   (Subdomain/Addon domain memungkinkan ini). Jika tidak bisa, `.htaccess`
   di root sudah disediakan (lihat langkah 4).
3. **Database**: buat DB + user di MySQL® Databases cPanel, lalu beri ALL PRIVILEGES.
4. **Konfigurasi**: buat file `.env` di root folder (sejajar `install.php`)
   melalui File Manager cPanel, isinya:

   ```
   DB_HOST=localhost
   DB_NAME=cpaneluser_kutt
   DB_USER=cpaneluser_kutt
   DB_PASSWORD=password-anda
   ```

   (Nama `DB_PASS` juga diterima — keduanya otomatis disinkronkan.)

5. **Migrasi** — dua cara:
   - Terminal/SSH: `php database/migrate.php`, atau
   - phpMyAdmin: import `database/database.sql` (skema + seed gabungan).
     PENTING: import HANYA ke database KOSONG baru — file ini memuat
     `DROP TABLE IF EXISTS` dan akan MENGGANTI tabel yang sudah ada.
6. **Tanpa document root khusus**: pastikan `.htaccess` di root folder memuat
   rewrite ke `public/index.php` (file `public_html/.htaccess` contoh tersedia).
7. Buka domain — portal publik tampil, login admin di `/login`.
8. **Hapus `install.php` setelah instalasi sukses.**

### Installer web (opsional)

Buka `https://domain-anda/install.php` — memeriksa versi PHP & ekstensi,
menguji koneksi DB, menjalankan migrasi, dan membuat akun admin pertama.
File ini **wajib dihapus** setelah instalasi.

## Keamanan

PDO prepared statements, bcrypt (`password_hash`/`password_verify`), CSRF token
di semua POST, session hardening + throttle login, RBAC dua lapis (view + granular),
audit trail lengkap, escape output (`e()`), sanitasi HTML terbatas, validasi upload
(MIME + extension + re-encode), token QR acak (bukan NIK), dan error handling yang
tidak membocorkan detail database ke pengguna.
