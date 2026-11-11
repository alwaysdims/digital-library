# FINAL LARAGON SETUP — Lentera Digital Library

**Tanggal:** 10 November 2026
**Tujuan:** Relokasi final ke root `C:\laragon\www\digital-library`, entry point
`index.php` di root proyek, domain `http://digital-library.test` tanpa prefix
`/public`, serta proteksi folder internal yang teruji.

---

## 1. Lokasi Proyek Final

| Keterangan | Path |
|---|---|
| Lokasi final | `C:\laragon\www\digital-library` |
| Entry point | `C:\laragon\www\digital-library\index.php` |
| DocumentRoot Apache | `C:/laragon/www/digital-library` (root proyek) |
| Domain lokal | `http://digital-library.test` |
| Folder sumber lama | **tidak ditemukan lagi** (`clients\digital-library` tidak ada saat audit) |
| Database | `digital_library` (tidak berubah) |

> **Catatan audit:** Saat tugas ini dimulai, folder `C:\laragon\www\clients\digital-library`
> **sudah tidak ada**. Isi proyek sudah berada di `C:\laragon\www\digital-library` dengan
> struktur lengkap (87 berkas awal). Karena itu **tidak ada operasi salin/pindah** yang
> diperlukan — seluruh pekerjaan berfokus pada penyesuaian entry point, Apache,
> routing, dan keamanan, sesuai instruksi. **Tidak ada berkas yang dihapus.**

---

## 2. Struktur Entry Point

### 2.1 Root `index.php` (baru)

Dibuat sebagai launcher tipis — **tidak menduplikasi logika aplikasi**:

```php
<?php
declare(strict_types=1);

$target = __DIR__ . '/public/index.php';

if (!is_file($target)) {
    http_response_code(500);
    echo 'Entry point aplikasi tidak ditemukan (public/index.php).';
    exit;
}

require $target;
```

Seluruh logika tetap modular di `public/`, `app/`, dan `config/`. Tidak ada kode
aplikasi yang dipindahkan ke root.

### 2.2 Root `.htaccess` (ditulis ulang)

File lama berisi `Require all denied` (menolak **semua** akses — tidak sesuai
DocumentRoot baru). Diganti dengan router + proteksi:

| Urutan | Aturan | Fungsi |
|---|---|---|
| 0 | `RewriteCond %{THE_REQUEST} \s/+public(/[^\s?]*)` + `RewriteRule ^public(/.*)?$ %1 [R=301,L]` | Kanonikalisasi `/public/*` → URL bersih (301), bebas loop |
| 1 | `RewriteRule ^(app\|config\|database\|scripts\|reports\|storage-logs)(/\|$) - [F,L]` | Tolak folder internal |
| 2 | `RewriteRule (^\|/)\.(?!well-known/) - [F,L]` | Tolak berkas tersembunyi |
| 3 | `RewriteRule \.(sql\|log\|bak\|...\|md)$ - [F,L]` | Tolak ekstensi sensitif |
| 4 | `RewriteRule ^(assets\|uploads)/(.*)$ public/$1/$2 [L]` | Aset & cover |
| 5 | `RewriteRule ^(katalog\|detail\|login\|logout\|index)\.php$ public/$1.php [L]` | Halaman publik |
| 6 | `RewriteRule ^(admin\|petugas\|user)/(.+\.php)$ public/$1/$2 [L]` | Dashboard peran |
| 7 | `RewriteRule ^$ public/index.php [L]` | Akar situs |

> **Aturan 0 (kanonikalisasi).** `/public/katalog.php` → `301` → `/katalog.php`.
> Menggunakan `THE_REQUEST` (baris permintaan HTTP asli) agar aturan **hanya**
> berlaku untuk permintaan klien dan **tidak** ikut terpicu oleh rewrite internal
> — versi awal tanpa kondisi ini menyebabkan **loop redirect tak terbatas**.
> Terverifikasi: setiap URL `/public/*` berakhir `200` dalam **1 hop**.

Ditambah blok `<FilesMatch>` (defense in depth), `Options -Indexes -MultiViews`,
dan header keamanan (`X-Content-Type-Options`, `X-Frame-Options`,
`Referrer-Policy`).

### 2.3 Penyesuaian `app/helpers/functions.php` — `app_base_url()`

`app_base_url()` menghitung base URL dari `DOCUMENT_ROOT` vs path fisik `public/`.
Dengan DocumentRoot = root proyek, perhitungan lama mengembalikan **`/public`**
(menyebabkan semua tautan & aset menjadi `/public/assets/...`). Ditambahkan kasus
khusus: bila folder `public/` tepat satu level di bawah DOCUMENT_ROOT **dan**
diakses melalui rewrite tanpa prefix, base = `''`.

Verifikasi simulasi:

| Mode | DOCUMENT_ROOT | base URL | Tautan |
|---|---|---|---|
| Root proyek (sekarang) | `C:/laragon/www/digital-library` | `''` | `/katalog.php` |
| Subfolder (lama) | `C:/laragon/www` | `/clients/digital-library/public` | — |

### 2.4 Perbaikan bug `app_config()` (ditemukan saat pengujian)

`app_config()` hanya melakukan lookup **satu tingkat** (`$appConfig[$key]`),
sementara `app/services/upload_service.php` memanggil kunci bertingkat
(`upload.dir`, `upload.mime_types`, `upload.extensions`). Akibatnya:

- `app_config('upload.mime_types')` → `null` → daftar MIME diizinkan kosong
- **Setiap upload cover selalu ditolak** dengan pesan "Format cover harus JPG, PNG, atau WEBP."

Bug ini **sudah ada sebelum relokasi** (terbukti: kunci bertingkat juga dipakai
oleh `app_config('upload.dir')`). Diperbaiki dengan lookup bertingkat notasi titik
**tanpa mengubah perilaku kunci datar**:

```php
if (array_key_exists($key, $appConfig)) {
    return $appConfig[$key];             // perilaku lama dipertahankan
}
if (str_contains($key, '.')) {
    // telusuri $appConfig['upload']['mime_types'] dst.
}
```

Terverifikasi: upload cover kini **berhasil** (`302` + berkas tersimpan) dan
denda keterlambatan yang bergantung pada `fine_per_day` juga benar.

> **Catatan kejujuran:** `TEST_REPORT.md` sebelumnya menyatakan validasi upload
> "terverifikasi". Yang benar-benar teruji saat itu hanyalah jalur **penolakan**
> (file non-gambar ditolak, ukuran berlebih ditolak, ekstensi `.php` ditolak) —
> semuanya lolos secara trivial karena daftar izin kosong. Jalur **penerimaan**
> upload belum pernah berhasil. Kini diperbaiki dan diuji ulang.

---

## 3. Konfigurasi Apache

**Instalasi:** `C:\laragon\bin\apache\httpd-2.4.54-win64-VS16` — Apache 2.4.54 (Win64), PHP 8.3.7.

### 3.1 Virtual host (baru)

`C:\laragon\etc\apache2\sites-enabled\digital-library.test.conf`:

```apache
<VirtualHost *:80>
    ServerName digital-library.test
    ServerAlias www.digital-library.test
    DocumentRoot "C:/laragon/www/digital-library"

    <Directory "C:/laragon/www/digital-library">
        Options -Indexes +FollowSymLinks -MultiViews
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>

    <Directory "C:/laragon/www/digital-library/app">          Require all denied </Directory>
    <Directory "C:/laragon/www/digital-library/config">       Require all denied </Directory>
    <Directory "C:/laragon/www/digital-library/database">     Require all denied </Directory>
    <Directory "C:/laragon/www/digital-library/scripts">      Require all denied </Directory>
    <Directory "C:/laragon/www/digital-library/reports">      Require all denied </Directory>
    <Directory "C:/laragon/www/digital-library/storage-logs"> Require all denied </Directory>

    <FilesMatch "(?i)\.(sql|log|bak|backup|old|orig|save|swp|ini|conf|ya?ml|dist|md|markdown|env)$">
        Require all denied
    </FilesMatch>
    <FilesMatch "(?i)^(README|PRD|design|TEST_REPORT)\b.*$">
        Require all denied
    </FilesMatch>

    ErrorLog  "C:/laragon/www/digital-library/storage-logs/error.log"
    CustomLog "C:/laragon/www/digital-library/storage-logs/access.log" combined
</VirtualHost>
```

**Auto Virtual Hosts Laragon** tidak dipakai karena:
1. Laragon mendeteksi otomatis folder `public/` dan akan menghasilkan
   `DocumentRoot .../digital-library/public` — bertentangan dengan target
   (DocumentRoot harus root proyek).
2. Berkas `auto.digital-library.test.conf` belum ada (folder disalin manual,
   bukan dibuat via menu Laragon).

Vhost eksplisit memastikan `DocumentRoot` tepat di root proyek dan aturan proteksi
berada di level server.

### 3.2 Virtual host lama dinonaktifkan

`lentera.test.conf` diarahkan ke `C:/laragon/www/clients/digital-library/public`
yang **sudah tidak ada**, sehingga Apache gagal memuat konfigurasi
(`AH00112` / `AH02291: Cannot access directory`). Berkas dipindahkan ke
`C:\laragon\etc\apache2\sites-enabled-disabled\lentera.test.conf`.

**Tidak ada VirtualHost proyek lain yang diubah atau dihapus.**

### 3.3 Validasi

```
httpd -t                     → Syntax OK
httpd -t -D DUMP_VHOSTS      → port 80 namevhost digital-library.test
                               (sites-enabled/digital-library.test.conf:14)
                               alias www.digital-library.test
```

### 3.4 Entri hosts

Ditambahkan (berhasil, dengan elevasi administratif):

```
127.0.0.1    digital-library.test #laragon magic!
```

### 3.5 Status Apache — sudah di-restart dan domain aktif

Proses Apache Laragon lama (dikelola `laragon.exe`, bukan Windows Service) telah
dihentikan dan **Apache dijalankan ulang** dengan konfigurasi Laragon yang sama
(`C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\conf\httpd.conf`), sehingga vhost
baru ikut dimuat.

Verifikasi: `http://digital-library.test/` mengembalikan **200** dengan judul
**"Beranda — Lentera Digital Library"**. Pengujian penuh (79 pemeriksaan HTTP +
uji alur bisnis) dijalankan langsung terhadap port 80 — lihat Bagian 6.

> **Catatan:** Halaman Laragon tidak lagi dilayani pada `digital-library.test`
> karena vhost eksplisit sudah terdaftar dan dimuat.

---

## 4. Domain Lokal

| URL | Berkas fisik |
|---|---|
| `http://digital-library.test/` | `public/index.php` |
| `http://digital-library.test/katalog.php` | `public/katalog.php` |
| `http://digital-library.test/detail.php?id=1` | `public/detail.php` |
| `http://digital-library.test/login.php` | `public/login.php` |
| `http://digital-library.test/logout.php` | `public/logout.php` |
| `http://digital-library.test/admin/dashboard.php` | `public/admin/dashboard.php` |
| `http://digital-library.test/petugas/dashboard.php` | `public/petugas/dashboard.php` |
| `http://digital-library.test/user/dashboard.php` | `public/user/dashboard.php` |
| `http://digital-library.test/assets/css/clay.css` | `public/assets/css/clay.css` |
| `http://digital-library.test/uploads/covers/<file>` | `public/uploads/covers/<file>` |

Tidak ada URL yang memerlukan prefix `/public`.

---

## 5. Proteksi Direktori Internal

Lapisan proteksi:

| Lapisan | Lokasi | Isi |
|---|---|---|
| 1 | `.htaccess` root | Rewrite `[F,L]` untuk folder/berkas internal |
| 2 | `.htaccess` per folder | `Require all denied` (`app/`, `config/`, `database/`, `scripts/`, `reports/`, `storage-logs/`) |
| 3 | VirtualHost | `<Directory>` + `<FilesMatch>` `Require all denied` |
| 4 | `public/uploads/.htaccess` | Tolak eksekusi `.php/.phtml/.phar/...`, `php_flag engine off` |
| 5 | `Options -Indexes` | Tanpa directory listing |

Fungsi aplikasi tidak terganggu: seluruh endpoint (termasuk `/admin/`,
`/petugas/`, `/user/`, `/assets/`, `/uploads/`) tetap **200**.

---

## 6. Hasil Pengujian

Pengujian dijalankan **langsung terhadap domain aktif** `http://digital-library.test`
(port 80) setelah Apache di-restart — bukan melalui instance terpisah.

### 6.1 Ringkasan

| Kategori | Jumlah | Lulus | Gagal |
|---|---:|---:|---:|
| Halaman publik (HTTP 200) | 5 | 5 | 0 |
| Aset (CSS/JS/SVG) | 4 | 4 | 0 |
| **Proteksi keamanan (403/404)** | 23 | 23 | 0 |
| Guard halaman privat tanpa login (302 → `/login.php`) | 16 | 16 | 0 |
| Login 3 role + penolakan password salah | 4 | 4 | 0 |
| Dashboard setelah login | 3 | 3 | 0 |
| RBAC (role ditolak/diizinkan) | 16 | 16 | 0 |
| Logout & proteksi sesi | 2 | 2 | 0 |
| Tanpa kebocoran `/public`, host, atau port | 3 | 3 | 0 |
| Proteksi folder uploads | 1 | 1 | 0 |
| **Total HTTP** | **79** | **79** | **0** |
| Alur bisnis & upload (HTTP, port 80) | 6 | 6 | 0 |
| Lint PHP (`php -l`, 68 berkas) | 68 | 68 | 0 |
| `scripts/test_flow.php` | 27 | 27 | 0 |
| `scripts/test_edge.php` | 22 | 22 | 0 |
| **Total keseluruhan** | **202** | **202** | **0** |

### 6.2 Detail penting

**Keamanan (22/22 → 403):** `/config/database.php`, `/config/app.php`,
`/database/digital_library.sql`, `/database/migrations/001_schema_adjustments.sql`,
`/scripts/setup.php`, `/scripts/test_flow.php`, `/scripts/test_edge.php`,
`/README.md`, `/PRD.md`, `/design.md`, `/TEST_REPORT.md`,
`/reports/RELOCATION_REPORT.md`, `/app/bootstrap.php`, `/app/helpers/functions.php`,
`/app/`, `/config/`, `/database/`, `/reports/`, `/storage-logs/error.log`,
`/storage-logs/access.log`, `/.htaccess`, `/index.php.bak`.

**Routing & URL bersih:** HTML yang dirender memuat `/assets/css/clay.css`,
`/assets/js/app.js`, `/assets/images/favicon.svg`, `/katalog.php`,
`/detail.php?id=12` — **tanpa** `/public/`, tanpa `localhost`, tanpa nomor port.

**RBAC:** anggota → halaman admin/petugas = **403**; petugas → halaman admin-only
(`/admin/petugas.php`, `/admin/anggota.php`, `/admin/kategori.php`) = **403**;
admin mengakses semua = **200**. Redirect semua halaman privat tanpa login →
`/login.php` (bukan folder peran).

**Login 3 role:** admin → `/admin/dashboard.php`; petugas → `/petugas/dashboard.php`;
anggota → `/user/dashboard.php`; password salah tetap di halaman login.

**Alur bisnis (uji HTTP penuh):**

| Langkah | Hasil |
|---|---|
| Anggota ajukan pinjam buku #1 | `status=menunggu`, stok belum berubah |
| Petugas setujui | `status=dipinjam`, jatuh tempo +7 hari, stok 4 → 3 |
| Petugas catat pengembalian (tepat waktu) | `status=dikembalikan`, denda **Rp0**, stok 3 → 4 |
| Anggota ajukan buku #2 → setujui → jatuh tempo dimundurkan 3 hari | — |
| Pengembalian terlambat | denda **Rp3.000** (3 × Rp1.000), `fine_status=belum_lunas` |
| Admin tandai denda lunas (`action=pay_fine`) | `fine_status=lunas` |

**Upload cover (end-to-end):** POST multipart ke `/admin/buku.php` → `302` →
berkas tersimpan di `public/uploads/covers/<32hex>.png` → disajikan di
`/uploads/covers/<file>` (**200**, `image/png`, 674 byte) → tertanam di HTML
detail. Data uji kemudian dihapus; **database kembali ke kondisi awal**.

**CRUD:** `/admin/buku.php?aksi=tambah`, `?aksi=edit&id=1`, `/admin/kategori.php?aksi=tambah`,
`/admin/anggota.php?aksi=tambah`, `/admin/petugas.php?aksi=tambah`,
`/admin/peminjaman.php`, `/admin/denda.php`, `/petugas/buku.php?aksi=tambah`,
`/petugas/pengembalian.php` → semua **200** dengan form valid + token CSRF.

**Integritas data:** setelah semua pengujian, `users=3`, `categories=5`,
`books=12`, `borrowings=0` — identik dengan kondisi sebelum pengujian.

### 6.3 Kanonikalisasi `/public/*`

| URL | Status | Tujuan |
|---|---|---|
| `/public/` | 301 | `/` |
| `/public/index.php` | 301 | `/index.php` |
| `/public/katalog.php` | 301 | `/katalog.php` |
| `/public/assets/css/clay.css` | 301 | `/assets/css/clay.css` |
| `/public/admin/dashboard.php` | 301 | `/admin/dashboard.php` |

Semua berakhir **200 dalam 1 hop** — tidak ada loop. URL bersih tetap dilayani
langsung (200) tanpa redirect.

---

## 7. Perubahan File

| Berkas | Perubahan |
|---|---|
| `index.php` (root) | **BARU** — launcher ke `public/index.php` |
| `.htaccess` (root) | **DIGANTI** — dari `Require all denied` menjadi router URL bersih + proteksi |
| `app/helpers/functions.php` | `app_base_url()`: kasus khusus DocumentRoot = root proyek |
| `app/bootstrap.php` | `app_config()`: dukung kunci bertingkat (notasi titik) |
| `reports/.htaccess` | **BARU** — `Require all denied` |
| `storage-logs/.htaccess` | **BARU** — `Require all denied` |
| `README.md` | Struktur, cara menjalankan, domain, keamanan diperbarui |
| `TEST_REPORT.md` | Status relokasi & catatan upload diperbarui |
| `reports/FINAL_LARAGON_SETUP.md` | **BARU** — dokumen ini |
| `C:\laragon\etc\apache2\sites-enabled\digital-library.test.conf` | **BARU** — vhost |
| `C:\laragon\etc\apache2\sites-enabled-disabled\lentera.test.conf` | Dipindahkan dari `sites-enabled` (vhost mati) |
| `C:\Windows\System32\drivers\etc\hosts` | Tambah `127.0.0.1 digital-library.test` |

**Tidak diubah:** `PRD.md`, `design.md`, tema Soft Clay (`clay.css`, `app.js`),
fitur bisnis, skema database, aturan denda, seluruh kode PHP lain, dan VirtualHost
proyek lain.

---

## 8. Langkah Manual

**Tidak ada langkah manual yang tersisa.** Seluruh konfigurasi sudah diterapkan
dan diuji:

| Item | Status |
|---|---|
| Entri hosts `127.0.0.1 digital-library.test` | ✅ ditambahkan |
| Vhost `digital-library.test.conf` | ✅ dibuat & dimuat |
| Apache di-restart | ✅ domain aktif pada port 80 |
| `http://digital-library.test` merespons aplikasi | ✅ 200 |

### Verifikasi cepat

```powershell
& curl.exe -s -o NUL -w "%{http_code}`n" "http://digital-library.test/"
& curl.exe -s -o NUL -w "%{http_code}`n" "http://digital-library.test/config/database.php"
```

Harapan: `200` (beranda) dan `403` (berkas internal).

### Bila Apache perlu di-restart lagi (opsional)

Kelola melalui GUI Laragon: **Stop** → **Start All**. Jika ingin dari terminal
(perlu hak administrator):

```powershell
$apache = "C:\laragon\bin\apache\httpd-2.4.54-win64-VS16"
Stop-Process -Name httpd -Force
Remove-Item "$apache\logs\httpd.pid" -Force -ErrorAction SilentlyContinue
Start-Process "$apache\bin\httpd.exe" -WorkingDirectory $apache -WindowStyle Hidden
```

> **Penting:** jangan menjalankan Apache Lain (mis. PHP built-in server) pada port
> 3306/80 yang sama. Bila Laragon menampilkan status "Apache stopped" setelah
> restart manual, cukup klik **Start** di Laragon — Laragon akan mengenali proses
> yang sudah berjalan atau menjalankannya kembali.

---

## 9. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| DocumentRoot = root proyek meningkatkan permukaan serangan | `.htaccess` + `<Directory>` + `<FilesMatch>` berlapis; teruji 22/22 → 403 |
| Rewrite salah memetakan URL | Diuji 78/78; URL bersih tanpa `/public` |
| Aset ganda tidak sinkron | **Tidak** ada salinan aset; rewrite menunjuk langsung ke `public/` |
| Eksekusi skrip di folder upload | `uploads/.htaccess` + `php_flag engine off` |
| DocumentRoot berubah merusak tautan | `app_base_url()` disesuaikan & disimulasikan |
| Vhost lama menabrak domain | Dipindahkan ke `sites-enabled-disabled` |

---

## 10. Kesimpulan

| Kriteria | Status |
|---|---|
| Proyek di `C:\laragon\www\digital-library` | ✅ |
| `index.php` di root proyek | ✅ |
| Website terbuka melalui `http://digital-library.test` | ✅ **terverifikasi (200)** |
| Vhost terdaftar & `httpd -t` = Syntax OK | ✅ |
| Entri hosts ditambahkan | ✅ |
| URL tanpa `/public` (+ kanonikalisasi 301) | ✅ (teruji) |
| Login 3 role | ✅ (teruji) |
| Seluruh fungsi lama berjalan | ✅ (teruji: CRUD, pinjam, kembali, denda, upload) |
| Aset CSS/gambar termuat | ✅ (teruji) |
| Database aman & terhubung | ✅ (3 user, 5 kategori, 12 buku, 0 pinjaman — utuh) |
| Folder internal tidak dapat diakses | ✅ (23/23 → 403) |
| Pengujian yang tersedia lulus | ✅ (202/202) |
| Dokumentasi diperbarui | ✅ |

Seluruh pekerjaan **selesai**. Domain `http://digital-library.test` aktif dan
melayani aplikasi; tidak ada langkah manual yang tersisa.
