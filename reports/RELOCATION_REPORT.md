# LAPORAN RELOKASI — Lentera Digital Library

**Tanggal:** 10 November 2026
**Sasaran:** Memindahkan proyek ke direktori Laragon baru dan menyiapkan akses
melalui `http://lentera.test` tanpa membangun ulang aplikasi.

---

## 1. Lokasi Proyek

| Keterangan | Path |
|---|---|
| Lokasi lama | `E:\clients\digital-library` |
| Lokasi baru | `C:\laragon\www\clients\digital-library` |
| DocumentRoot Apache | `C:/laragon/www/clients/digital-library/public` |
| Database | `digital_library` (tidak berubah) |

> **Catatan:** Direktori lama `E:\clients\digital-library` **tidak ditemukan lagi**
> pada drive E saat audit dilakukan. Seluruh isi proyek sudah berada di lokasi baru.
> Tidak ada penghapusan yang dilakukan oleh proses relokasi ini; folder lama tidak
> dibuat ulang dan tidak dihapus.

---

## 2. Audit Direktori Baru

Seluruh berkas proyek tersedia lengkap dan utuh:

- Struktur inti: `app/`, `config/`, `database/`, `public/`, `scripts/` — **ada, lokasi benar**.
- Dokumentasi: `PRD.md`, `design.md`, `README.md`, `TEST_REPORT.md` — **ada**.
- Aset aplikasi: `public/assets/css/clay.css`, `public/assets/js/app.js`,
  `public/assets/images/cover-placeholder.svg`, `favicon.svg` — **ada**.
- Database SQL: `database/digital_library.sql` + `database/migrations/` — **ada**.
- File PHP: **67 berkas** — semuanya **lolos** `php -l` (0 error).
- Tidak ditemukan tanda kerusakan berkas akibat pemindahan.

> Gambar mockup referensi desain tidak berada di dalam folder proyek (merupakan
> berkas referensi eksternal; hal ini sudah tercatat di `TEST_REPORT.md`).
> Aset milik aplikasi sendiri (favicon & placeholder cover) lengkap.

---

## 3. File yang Diubah

| File | Perubahan |
|---|---|
| `C:\laragon\etc\apache2\sites-enabled\lentera.test.conf` | `DocumentRoot`, `<Directory>`, `ErrorLog`, dan `CustomLog` diarahkan dari `E:/clients/digital-library/...` ke `C:/laragon/www/clients/digital-library/...` |
| `README.md` | Bagian instalasi (Langkah 3 & 4) diperbarui: path PHP Laragon eksplisit, lokasi proyek baru, dan status vhost |
| `TEST_REPORT.md` | Catatan #3 tentang vhost diperbarui (path public & hosts) |

**Tidak ada perubahan** pada kode aplikasi (PHP), desain Soft Clay, aturan
peminjaman/denda, maupun skema database. Kode sudah sepenuhnya portabel
(menggunakan `__DIR__` dan `DOCUMENT_ROOT`), sehingga **tidak ada** referensi
path absolut yang tertinggal di dalam proyek.

**Pencarian menyeluruh** untuk `E:\`, `E:/`, `clients/digital-library`, dan URL host lama
pada seluruh berkas proyek (`.php`, `.md`, `.sql`, `.css`, `.js`, `.htaccess`, `.json`):
**0 hasil** di dalam kode aplikasi.

---

## 4. Konfigurasi Apache

- **Instalasi Apache terdeteksi:** `C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe`
- **Port aktif:** 80 (HTTP) dan 443 (HTTPS) — proses `httpd.exe` sedang berjalan.
- **PHP handler:** `mod_php` → PHP `8.3.7` (`C:/laragon/bin/php/php-8.3.7-Win32-vs16-x64`).
- **Include aktif:** `IncludeOptional "C:/laragon/etc/apache2/sites-enabled/*.conf"`.
- **Validasi konfigurasi Apache (`httpd -t`):** `Syntax OK`.
- **Virtual host terdaftar (`httpd -t -D DUMP_VHOSTS`):**
  `port 80 namevhost lentera.test (lentera.test.conf:6)`, alias `www.lentera.test`.

VHost `lentera.test.conf` (kondisi saat ini):

```apache
<VirtualHost *:80>
    ServerName lentera.test
    ServerAlias www.lentera.test
    DocumentRoot "C:/laragon/www/clients/digital-library/public"

    <Directory "C:/laragon/www/clients/digital-library/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>

    ErrorLog  "C:/laragon/www/clients/digital-library/storage-logs/error.log"
    CustomLog "C:/laragon/www/clients/digital-library/storage-logs/access.log" combined
</VirtualHost>
```

Pengamanan folder sensitif: `DocumentRoot` menunjuk ke `public/` sehingga
`config/`, `database/`, `app/`, dan `scripts/` **tidak dapat diakses** melalui browser.

**Virtual host proyek lain tidak diubah dan tidak dihapus.**

### Entri hosts

Belum tersedia entri untuk `lentera.test` pada `C:\Windows\System32\drivers\etc\hosts`
(domain `.test` lain seperti `perpustakaan.test` sudah ada dan berfungsi).
Penulisan file hosts memerlukan **hak administrator**.

Tambahkan baris berikut (lihat Bagian 7 — Langkah Manual):

```
127.0.0.1    lentera.test
```

---

## 5. Kondisi Database

| Item | Nilai |
|---|---|
| DBMS | MySQL 8.0.30 (Laragon) |
| Host / Port | `127.0.0.1` : `3306` |
| Database | `digital_library` — **tersedia** |
| Charset | `utf8mb4` |
| User | `root` (password dikosongkan; **tidak ditampilkan** dalam laporan ini) |
| Driver | PDO MySQL (koneksi berhasil diuji) |

Isi database (tidak diubah, tidak di-import ulang, tidak ada operasi destruktif):

| Tabel | Jumlah baris |
|---|---:|
| users | 3 (admin, petugas, anggota) |
| categories | 5 |
| books | 12 |
| borrowings | 0 (data aktif; data uji dibersihkan oleh test runner) |

Koneksi PDO diverifikasi langsung: **berhasil**, `@@character_set_database = utf8mb4`.

---

## 6. Hasil Pengujian Ulang

Pengujian dijalankan ulang setelah relokasi pada PHP 8.3.7 / MySQL 8.0.30.
Untuk lapisan HTTP, karena domain `lentera.test` belum aktif (menunggu entri
hosts & restart Apache), pengujian dijalankan melalui PHP built-in server dengan
root dokumen `public/` — perilaku kode identik karena URL dihitung dari
`DOCUMENT_ROOT`.

| Kategori | Jumlah | Lulus | Gagal |
|---|---:|---:|---:|
| Pemeriksaan sintaks PHP (`php -l`, 67 berkas) | 67 | 67 | 0 |
| Alur peminjaman `scripts/test_flow.php` | 27 | 27 | 0 |
| Kasus tepi `scripts/test_edge.php` | 22 | 22 | 0 |
| Halaman publik (HTTP) | 5 | 5 | 0 |
| Halaman privat tanpa login (HTTP, 302 → login.php) | 16 | 16 | 0 |
| Login 3 role + redirect dashboard (HTTP) | 3 | 3 | 0 |
| Kontrol akses berbasis role / RBAC (HTTP) | 8 | 8 | 0 |
| Logout & proteksi sesi (HTTP) | 2 | 2 | 0 |
| Aset (CSS/JS/gambar, HTTP 200) | 4 | 4 | 0 |

**Ringkasan hasil sesi uji ini:** 154 pemeriksaan, **0 gagal**.

Rincian yang diverifikasi:
- Login `admin` → `/admin/dashboard.php`; `petugas` → `/petugas/dashboard.php`;
  `anggota` → `/user/dashboard.php`. Password salah ditolak (tetap di login).
- Anggota membuka halaman admin/petugas → **403**. Petugas membuka halaman
  admin-only → **403**. Admin dapat mengakses semua.
- Semua halaman privat tanpa login → **302 ke `/login.php`** (bukan folder peran —
  bug lama tidak muncul kembali).
- Logout → sesi dihapus → **302 ke `/index.php`**; akses ulang halaman privat → 302 login.
- Detail buku → daftar; ID tidak valid → redirect aman ke katalog.
- Pencarian katalog (`q=matematika`) mengembalikan hasil yang benar.
- Aset (`clay.css`, `app.js`, `cover-placeholder.svg`, `favicon.svg`) → **HTTP 200**.
- HTML yang dirender tidak memuat referensi `E:` atau path host lama.

> Catatan kejujuran: angka **142** pada audit sebelumnya adalah gabungan uji lama.
> Sesi ini menjalankan kembali seluruh rangkaian yang tersedia dan menambah
> pengujian HTTP pasca-relokasi. Semua yang dijalankan **lulus**; tidak ada klaim
> atas uji yang tidak benar-benar dijalankan.

---

## 7. URL Lokal Aplikasi

Setelah langkah manual (Bagian 8) selesai:

| Halaman | URL |
|---|---|
| Beranda | `http://lentera.test/` |
| Katalog | `http://lentera.test/katalog.php` |
| Detail buku | `http://lentera.test/detail.php?id=1` |
| Login | `http://lentera.test/login.php` |
| Dashboard admin | `http://lentera.test/admin/dashboard.php` |
| Dashboard petugas | `http://lentera.test/petugas/dashboard.php` |
| Dashboard anggota | `http://lentera.test/user/dashboard.php` |
| Logout | `http://lentera.test/logout.php` |

Akun demo: `admin` / `Admin#Lentera2024`, `petugas` / `Petugas#2024`,
`anggota` / `Anggota#2024` (**ganti sebelum presentasi**).

---

## 8. Langkah Manual yang Masih Diperlukan

Perubahan ini memerlukan **hak administrator** dan tidak dijalankan otomatis:

1. **Tambahkan entri hosts.** Buka Notepad **sebagai Administrator**, edit
   `C:\Windows\System32\drivers\etc\hosts`, tambahkan baris:
   ```
   127.0.0.1    lentera.test
   ```
   Lalu simpan.
2. **Restart Apache dari panel Laragon** (tombol **Stop** lalu **Start**).
   Apache yang berjalan saat audit masih memegang konfigurasi lama sehingga
   `lentera.test` mengembalikan **403**. Konfigurasi baru sudah benar dan lolos
   `httpd -t`; cukup dimuat ulang. (Restart tidak dapat dilakukan otomatis karena
   Apache dikelola Laragon, bukan Windows Service, dan bukan hak proses ini.)
3. Setelah restart, buka **http://lentera.test** dan lakukan verifikasi akhir.

Jika lebih memilih tanpa hak admin: aplikasi juga dapat dijalankan melalui
document root bawaan Laragon dengan mengakses
`http://localhost/clients/digital-library/public/`.

---

## 9. Kendala yang Belum Terselesaikan

1. **Domain `lentera.test` belum aktif** sampai entri hosts ditambahkan dan
   Apache di-restart (keduanya butuh hak administrator). Karena itu website
   **belum diklaim berjalan sepenuhnya** di `http://lentera.test`. Konfigurasi
   sudah valid (`httpd -t` = `Syntax OK`) dan vhost sudah terdaftar.
2. **Pengujian HTTPS/SSL** untuk `lentera.test` belum dilakukan (vhost SSL
   terpisah belum dibuat; aplikasi tidak memerlukannya untuk demo lokal).
3. **Folder upload cover kosong** (`public/uploads/covers/` belum berisi gambar),
   sesuai kondisi data saat ini (buku contoh tidak memiliki cover). Upload tetap
   divalidasi dan berfungsi (terverifikasi via `test_edge.php`).
4. Gambar mockup referensi desain tidak berada di dalam folder proyek, sehingga
   perbandingan visual terhadap mockup tidak dapat dilakukan dari repositori.

---

## 10. Kesimpulan

Relokasi konfigurasi **selesai untuk seluruh bagian yang dapat dilakukan tanpa
hak administrator**:

- Proyek tersedia lengkap di `C:\laragon\www\clients\digital-library`.
- VHost Apache diarahkan ke folder `public/` dan lolos validasi.
- Database tersambung; data lama aman dan tidak diubah.
- Seluruh kode portabel — tanpa referensi path lama.
- Pengujian ulang lulus tanpa kegagalan.

Yang tersisa hanya **dua langkah manual** (entri hosts + restart Apache via Laragon)
agar `http://lentera.test` aktif sepenuhnya.
