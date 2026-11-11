# Lentera Digital Library

Sistem informasi perpustakaan berbasis web untuk tugas UAS semester 1. Aplikasi ini
mengelola **buku fisik**: katalog, pendaftaran anggota, peminjaman, pengembalian,
dan pencatatan denda keterlambatan.

Peminjaman **gratis**. Denda hanya dikenakan bila buku dikembalikan melewati jatuh tempo.

---

## 1. Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | PHP Native 8.3 |
| Frontend | HTML5, CSS3, JavaScript (Bootstrap 5 + CSS kustom) |
| Database | MySQL / MariaDB (PDO prepared statements) |
| Web Server | Apache (Laragon) |
| Autentikasi | PHP Session + proteksi CSRF |
| Ikon | Bootstrap Icons |
| Gaya visual | Soft Clay Design System |

Tidak menggunakan framework PHP, tidak ada REST API terpisah, tidak ada e-book/PDF reader.

---

## 2. Persyaratan

- **Laragon** (sudah termasuk Apache, PHP 8.3, MySQL/MariaDB).
- PHP dengan ekstensi `pdo_mysql` dan `fileinfo` (aktif secara default di Laragon).
- Browser modern (Chrome, Edge, Firefox).
- Koneksi internet **opsional** — hanya untuk memuat font dan Bootstrap dari CDN.
  Tanpa internet, tampilan tetap berfungsi dengan fallback font dan CSS kustom.

---

## 3. Struktur Project

```text
digital-library/         ← DocumentRoot Apache (http://digital-library.test)
├── index.php            Entry point utama (launcher ke public/index.php)
├── .htaccess            Routing URL bersih + proteksi berkas internal
├── app/
│   ├── auth/            Session, CSRF, guard role
│   ├── controllers/     Handler CRUD & alur peminjaman
│   ├── helpers/         Escaping, URL, format tanggal/rupiah, render view
│   ├── services/        Query database (buku, kategori, user, peminjaman, upload)
│   ├── views/           Layout, partial, halaman publik & dashboard
│   ├── bootstrap.php    Inisialisasi aplikasi & koneksi database
│   └── .htaccess        Require all denied
├── config/
│   ├── app.php          Nama aplikasi, zona waktu, aturan bisnis
│   ├── database.php     Konfigurasi koneksi PDO
│   └── .htaccess        Require all denied
├── database/
│   ├── digital_library.sql
│   ├── migrations/      Penyesuaian skema (aman dijalankan ulang)
│   └── .htaccess        Require all denied
├── public/              Skrip yang diakses browser (diakses via URL bersih)
│   ├── index.php        Beranda
│   ├── katalog.php      Katalog + pencarian + filter
│   ├── detail.php       Detail & pengajuan peminjaman
│   ├── login.php, logout.php
│   ├── admin/           Halaman admin
│   ├── petugas/         Halaman petugas
│   ├── user/            Halaman anggota
│   ├── assets/          CSS, JS, gambar
│   └── uploads/covers/  Cover buku yang diunggah (.htaccess menolak eksekusi skrip)
├── reports/             Laporan internal (.htaccess: Require all denied)
├── storage-logs/        Log Apache (.htaccess: Require all denied)
├── scripts/
│   ├── setup.php        Seeder lokal (admin, petugas, anggota demo, buku contoh)
│   ├── test_flow.php    Uji otomatis alur peminjaman
│   ├── test_edge.php    Uji kasus tepi
│   └── .htaccess        Require all denied
├── PRD.md
├── design.md
├── README.md
└── TEST_REPORT.md
```

**Cara kerja entry point.** `DocumentRoot` menunjuk ke **root proyek**. Berkas
`index.php` di root adalah launcher tipis yang memuat `public/index.php`. Berkas
`.htaccess` root memetakan URL bersih ke berkas fisik di `public/`, sehingga URL
publik **tidak memerlukan prefix `/public`**:

| URL publik | Berkas fisik |
|---|---|
| `/` | `public/index.php` |
| `/katalog.php`, `/detail.php`, `/login.php`, `/logout.php` | `public/*.php` |
| `/admin/...`, `/petugas/...`, `/user/...` | `public/[role]/...` |
| `/assets/...`, `/uploads/...` | `public/assets/...`, `public/uploads/...` |

Folder internal (`app/`, `config/`, `database/`, `scripts/`, `reports/`,
`storage-logs/`) berisi `.htaccess` `Require all denied` **dan** dikunci kembali di
level VirtualHost Apache. Berkas `.sql`, `.log`, `.bak`, `.md`, konfigurasi, serta
berkas tersembunyi juga ditolak.

---

## 4. Cara Menjalankan (Laragon)

> **Lokasi proyek:** `C:\laragon\www\digital-library`
> **Domain lokal:** **http://digital-library.test**
> **DocumentRoot:** `C:/laragon/www/digital-library` (root proyek)

### Langkah 1 — Import database

Buka Laragon → **Database** (HeidiSQL/phpMyAdmin), atau jalankan dari terminal:

```powershell
& "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root < database\digital_library.sql
```

Opsional, jalankan migration penyesuaian skema:

```powershell
& "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root digital_library < database\migrations\001_schema_adjustments.sql
```

### Langkah 2 — Konfigurasi koneksi

Buka `config/database.php`. Default sudah cocok untuk Laragon:

```php
host     : 127.0.0.1
database : digital_library
username : root
password : (kosong)
charset  : utf8mb4
```

Bila MySQL Laragon Anda memakai password, ubah `$dbPass` atau set environment
variable `DB_PASS`. File ini tidak pernah diakses langsung oleh browser.

### Langkah 3 — Buat akun pertama dan data demo

Jalankan dari terminal (opsional bila database sudah berisi data):

```powershell
& "C:\laragon\bin\php\php-8.3.7-Win32-vs16-x64\php.exe" scripts\setup.php
```

Script ini membuat:

| Role | Username | Password default |
|---|---|---|
| Admin | `admin` | `Admin#Lentera2024` |
| Petugas | `petugas` | `Petugas#2024` |
| Anggota | `anggota` | `Anggota#2024` |

Plus 5 kategori dan 12 buku contoh dengan sinopsis.

> Script ini hanya dapat dijalankan dari command line (bukan browser).
> **Ganti password demo sebelum presentasi.**
> Untuk password admin berbeda, set `ADMIN_PASSWORD` sebelum menjalankan seeder.

### Langkah 4 — Arahkan Apache ke root proyek

Proyek berada di `C:\laragon\www\digital-library` dan dikonfigurasi sebagai
virtual host di `C:\laragon\etc\apache2\sites-enabled\digital-library.test.conf`
dengan `DocumentRoot` di root proyek:

```
DocumentRoot "C:/laragon/www/digital-library"
```

Entri hosts berikut sudah ditambahkan (memerlukan hak administrator):

```
127.0.0.1    digital-library.test
```

Apache sudah di-restart, sehingga vhost dimuat. Buka: **http://digital-library.test**

> Bila Apache perlu dijalankan ulang, **Stop lalu Start** dari menu Laragon.

> Folder `config/`, `app/`, `database/`, `scripts/`, `reports/`, dan
> `storage-logs/` tidak dapat diakses lewat browser (`.htaccess` + aturan
> VirtualHost). Aset publik (`/assets/...`) dan cover (`/uploads/...`) tetap
> dilayani dari `public/` **tanpa** prefix `/public`.

Untuk uji cepat tanpa Apache/Laragon, gunakan PHP built-in server dengan router
`.htaccess` tidak berlaku — jalankan langsung dari `public/`:

```powershell
cd public
php -S 127.0.0.1:8000
```
Lalu buka **http://127.0.0.1:8000**. (Mode ini tidak menguji rewrite root;
pengujian routing sebenarnya dilakukan melalui Apache.)

---

## 5. Tiga Role & Hak Akses

| Fitur | Pengunjung | Admin | Petugas | Anggota |
|---|:---:|:---:|:---:|:---:|
| Lihat katalog & sinopsis | Ya | Ya | Ya | Ya |
| Login | – | Ya | Ya | Ya |
| Kelola buku & kategori | – | Ya | Ya | – |
| Kelola akun petugas | – | Ya | – | – |
| Daftarkan anggota | – | Ya | Ya | – |
| Ajukan peminjaman | – | – | – | Ya |
| Setujui/tolak pengajuan | – | Ya | Ya | – |
| Catat pengembalian & denda | – | Ya | Ya | – |
| Lihat pinjaman sendiri | – | – | – | Ya |

Semua pembatasan ditegakkan di **backend** (PHP), bukan sekadar menyembunyikan menu.

---

## 6. Fitur

**Publik** — beranda dengan pencarian, katalog dengan filter kategori & pagination,
detail buku dengan sinopsis dan status stok.

**Autentikasi** — satu halaman login untuk semua role, sesi aman, logout,
proteksi CSRF, dan pengalihan ke dashboard sesuai role.

**Admin** — ringkasan statistik, CRUD buku & kategori, kelola akun petugas,
lihat anggota, lihat seluruh transaksi, lihat denda.

**Petugas** — CRUD buku, kelola data anggota (termasuk membuat akun), memproses
pengajuan, mencatat pengembalian, mengelola denda.

**Anggota** — katalog, pengajuan peminjaman, status pengajuan, daftar pinjaman aktif
dengan jatuh tempo, estimasi denda, dan riwayat peminjaman.

---

## 7. Aturan Peminjaman & Denda

| Ketentuan | Nilai |
|---|---|
| Biaya peminjaman | **Rp0** (gratis) |
| Masa pinjam | **7 hari** kalender sejak buku diserahkan |
| Batas pinjaman aktif | **3 buku** per anggota |
| Pengajuan | **1 buku per pengajuan** |
| Denda keterlambatan | **Rp1.000 per hari per buku** |
| Status denda | Belum Lunas / Lunas (dicatat manual petugas) |

Nilai-nilai ini dapat diubah di `config/app.php` (`loan_days`, `max_active_loans`,
`fine_per_day`).

**Alur:**
1. Anggota klik *Ajukan Peminjaman* → status **Menunggu** (stok belum berkurang).
2. Petugas menyetujui saat menyerahkan buku → stok **-1**, status **Dipinjam**,
   tanggal pinjam & jatuh tempo dicatat.
3. Petugas mencatat pengembalian → stok **+1**, status **Dikembalikan**,
   denda final dihitung dari tanggal kembali sebenarnya.
4. Jika ada denda, petugas menandai **Lunas** setelah pembayaran diterima.

Denda dihitung berdasarkan **tanggal kalender** (zona `Asia/Jakarta`), bukan durasi jam.
Contoh: terlambat 3 hari → denda Rp3.000.

---

## 8. Keamanan

- Password di-hash dengan `password_hash()` dan diverifikasi dengan `password_verify()`.
- Seluruh query memakai PDO **prepared statements**.
- Sesi diamankan: `session_regenerate_id()` setelah login, cookie `HttpOnly` + `SameSite=Lax`.
- Aksi yang mengubah data hanya melalui **POST** dengan **token CSRF**.
- Output di-escape (`htmlspecialchars`) untuk mencegah XSS.
- Upload cover divalidasi berdasarkan MIME asli, ekstensi, ukuran (maks 2 MB),
  dan disimpan dengan nama acak. Folder `uploads/` menolak eksekusi skrip.
- Persetujuan & pengembalian memakai **database transaction** dengan row locking
  (`SELECT ... FOR UPDATE`) sehingga klik ganda tidak menggandakan transaksi
  atau membuat stok negatif.
- Pesan error database tidak membocorkan kredensial.

**Proteksi root proyek (DocumentRoot = root).** Karena `DocumentRoot` berada di
root proyek, proteksi internal dilakukan berlapis:

1. `.htaccess` root menolak akses ke `app/`, `config/`, `database/`, `scripts/`,
   `reports/`, `storage-logs/`, berkas tersembunyi, dan ekstensi sensitif
   (`.sql`, `.log`, `.bak`, `.md`, `.ini`, `.conf`, `.yml`, dll).
2. Setiap folder internal juga memiliki `.htaccess` `Require all denied`.
3. `digital-library.test.conf` mengulang `Require all denied` pada level
   `<Directory>` dan `<FilesMatch>` (pertahanan berlapis bila `.htaccess` gagal).
4. `Options -Indexes` mencegah directory listing.
5. Folder `uploads/` menolak eksekusi skrip PHP.

Hasil pengujian: seluruh URL sensitif (`/config/database.php`,
`/database/digital_library.sql`, `/scripts/setup.php`, `/README.md`, `/app/`,
`/reports/`, `/.htaccess`, `/storage-logs/error.log`) mengembalikan **403**.

**Kanonikalisasi URL.** `/public/...` **tidak** dijadikan URL publik. Aturan
`RewriteCond %{THE_REQUEST}` di root `.htaccess` mengalihkan (301) `/public/*`
ke URL bersih, sehingga hanya ada satu ruang URL yang valid:

| Permintaan | Hasil |
|---|---|
| `/public/katalog.php` | 301 → `/katalog.php` |
| `/public/assets/css/clay.css` | 301 → `/assets/css/clay.css` |
| `/katalog.php` (kanonik) | 200 langsung, tanpa redirect |

---

## 9. Cara Demo UAS

1. **Pengunjung** — buka beranda, cari buku, buka detail dan baca sinopsis tanpa login.
2. **Login petugas** (`petugas` / `Petugas#2024`) — daftarkan anggota baru,
   lalu salin password awal yang muncul ke form.
3. **Login anggota** — ajukan peminjaman satu buku yang stoknya tersedia.
4. **Login petugas** — buka *Pengajuan*, setujui pengajuan (stok berkurang, jatuh tempo 7 hari).
5. **Pengembalian** — catat pengembalian tepat waktu (denda Rp0), lalu ulangi dengan
   buku yang jatuh temponya dimundurkan untuk menunjukkan denda Rp3.000.
6. **Denda** — tandai denda sebagai *Lunas*.
7. **Login admin** (`admin` / `Admin#Lentera2024`) — tunjukkan kelola petugas,
   kategori, dan seluruh transaksi.
8. **Uji hak akses** — login sebagai anggota lalu buka `/admin/buku.php` → ditolak (403).

---

## 10. Pengujian

Jalankan uji alur peminjaman otomatis:

```powershell
php scripts\test_flow.php
```

Hasil pengujian lengkap ada di [`TEST_REPORT.md`](TEST_REPORT.md).

---

## 11. Catatan

- Aplikasi menggunakan **tiga role**: Admin, Petugas, Anggota. "Pengunjung" adalah
  halaman publik sebelum login, bukan role tersendiri.
- Data buku contoh bersifat fiktif untuk keperluan demo.
- Jangan mengakses phpMyAdmin atau server dari internet tanpa perlindungan tambahan.
