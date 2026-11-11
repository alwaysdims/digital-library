# TEST_REPORT — Lentera Digital Library

**Tanggal pengujian:** 10 November 2026
**Lingkungan:** Laragon — Windows, PHP 8.3.7, MySQL 8.0.30, Apache 2.4.54
**Metode:** Uji otomatis level service (`scripts/test_flow.php`, `scripts/test_edge.php`),
uji HTTP end-to-end dengan sesi login nyata, dan pemeriksaan statis (`php -l`).

---

## 1. Ringkasan Hasil

> **Pembaruan 10 Nov 2026 (relokasi final).** Pengujian dijalankan ulang setelah
> relokasi ke `C:\laragon\www\digital-library` dengan DocumentRoot di **root proyek**
> dan URL bersih tanpa `/public`. Pengujian HTTP dijalankan **langsung terhadap
> `http://digital-library.test` (port 80)**. Rincian relokasi ada di
> [`reports/FINAL_LARAGON_SETUP.md`](reports/FINAL_LARAGON_SETUP.md).

| Kategori | Jumlah | Lulus | Gagal |
|---|---:|---:|---:|
| Alur peminjaman & pengembalian (otomatis) | 27 | 27 | 0 |
| Kasus tepi & ketahanan (otomatis) | 22 | 22 | 0 |
| Pemeriksaan sintaks PHP | 68 file | 68 | 0 |
| Halaman publik (HTTP) | 5 | 5 | 0 |
| Aset CSS/JS/SVG (HTTP) | 4 | 4 | 0 |
| Guard halaman privat tanpa login (HTTP) | 16 | 16 | 0 |
| Login 3 role + penolakan (HTTP) | 4 | 4 | 0 |
| Dashboard terautentikasi (HTTP) | 3 | 3 | 0 |
| Kontrol akses berbasis role / RBAC (HTTP) | 16 | 16 | 0 |
| Logout & proteksi sesi (HTTP) | 2 | 2 | 0 |
| Proteksi folder/berkas internal (HTTP, harus 403/404) | 23 | 23 | 0 |
| Tanpa kebocoran `/public`/host/port di HTML | 3 | 3 | 0 |
| Proteksi folder uploads | 1 | 1 | 0 |
| Alur bisnis & upload via domain aktif | 6 | 6 | 0 |
| **Total sesi pengujian relokasi** | **202** | **202** | **0** |

---

## 2. Skenario Autentikasi

| # | Skenario | Metode | Hasil |
|---|---|---|---|
| 2.1 | Login admin (`admin`) | HTTP | Lulus — redirect ke `/admin/dashboard.php` |
| 2.2 | Login petugas (`petugas`) | HTTP | Lulus — redirect ke `/petugas/dashboard.php` |
| 2.3 | Login anggota (`anggota`) | HTTP | Lulus — redirect ke `/user/dashboard.php` |
| 2.4 | Password salah | HTTP | Lulus — pesan "Username atau password salah, atau akun tidak aktif." |
| 2.5 | Username tidak ada | Service | Lulus — `user_authenticate()` mengembalikan `null` |
| 2.6 | Logout | HTTP | Lulus — sesi dihapus, kembali ke beranda |
| 2.7 | Akses halaman privat tanpa login | HTTP | Lulus — redirect 302 ke `/login.php` |
| 2.8 | Anggota membuka halaman admin | HTTP | Lulus — **403 Akses Ditolak** |
| 2.9 | Petugas membuka kelola petugas (admin-only) | HTTP | Lulus — **403 Akses Ditolak** |
| 2.10 | POST tanpa token CSRF | HTTP | Lulus — **419 Sesi form tidak valid** |
| 2.11 | Akun nonaktif tidak dapat login | Kode | Lulus — `is_active = 0` ditolak |

---

## 3. Skenario Manajemen Buku

| # | Skenario | Metode | Hasil |
|---|---|---|---|
| 3.1 | Tambah buku | HTTP | Lulus — buku tersimpan, redirect 302 |
| 3.2 | Edit buku | HTTP | Lulus — judul & stok diperbarui di database |
| 3.3 | Hapus buku tanpa riwayat | HTTP | Lulus — terhapus dari database |
| 3.4 | Hapus buku dengan riwayat | Service | Lulus — ditolak, riwayat terjaga |
| 3.5 | Stok total < jumlah dipinjam | Service | Lulus — update dikembalikan `false` |
| 3.6 | Pencarian judul/penulis | HTTP | Lulus — hasil terfilter |
| 3.7 | Filter kategori & pagination | HTTP | Lulus — parameter `kategori`, `hal`, `urut` bekerja |
| 3.8 | Validasi input form kosong | Kode | Lulus — error per-field ditampilkan |
| 3.9 | Pencarian katalog (`q=matematika`) | HTTP | Lulus — 1 hasil ditemukan |
| 3.10 | Pencarian tanpa hasil → empty state | HTTP | Lulus — pesan "Buku tidak ditemukan" |
| 3.11 | Pencarian anggota, peminjaman, denda | HTTP | Lulus — tidak ada `PDOException` |

---

## 4. Skenario Upload Cover

| # | Skenario | Hasil |
|---|---|---|
| 4.1 | File non-gambar (.jpg berisi teks) | Lulus — ditolak (MIME tidak cocok) |
| 4.2 | Ukuran melebihi 2 MB | Lulus — ditolak |
| 4.3 | Ekstensi `.php` | Lulus — ditolak |
| 4.4 | Nama file acak | Lulus — `bin2hex(random_bytes(16))` |
| 4.5 | Folder `uploads/` menolak eksekusi skrip | Lulus — aturan `.htaccess` terpasang |
| 4.6 | **Upload gambar valid diterima** (PNG asli) | Lulus — `302`, berkas tersimpan di `public/uploads/covers/` |
| 4.7 | **Cover disajikan kembali** via `/uploads/covers/<file>` | Lulus — `200`, `image/png`, 674 byte |
| 4.8 | **Cover tertanam di halaman detail** | Lulus — `<img src="/uploads/covers/...">` |

> **Koreksi penting.** Pada audit sebelumnya, skenario 4.1–4.3 "lulus" secara
> **trivial** karena daftar MIME/ekstensi yang diizinkan kosong (bug `app_config()`
> — lihat §9.6), sehingga **semua** upload ditolak, termasuk yang valid. Setelah
> perbaikan, jalur penerimaan (4.6–4.8) diuji dan **berhasil**. Data uji upload
> dihapus kembali; database kembali ke kondisi awal.

---

## 5. Skenario Anggota

| # | Skenario | Metode | Hasil |
|---|---|---|---|
| 5.1 | Petugas mendaftarkan anggota baru | HTTP | Lulus — akun dibuat, password awal dihasilkan |
| 5.2 | Anggota baru dapat login | Service | Lulus |
| 5.3 | Edit data anggota | HTTP | Lulus |
| 5.4 | Nonaktifkan / aktifkan anggota | HTTP | Lulus |
| 5.5 | Hapus anggota beriwayat | Service | Lulus — ditolak |
| 5.6 | Username duplikat | Service | Lulus — ditolak |

---

## 6. Skenario Peminjaman (`scripts/test_flow.php`)

| # | Skenario | Hasil |
|---|---|---|
| 6.1 | Pengajuan dibuat, status Menunggu | Lulus |
| 6.2 | Stok **tidak** berkurang saat pengajuan | Lulus |
| 6.3 | Pengajuan ganda untuk buku sama ditolak | Lulus |
| 6.4 | Persetujuan berhasil | Lulus |
| 6.5 | Stok berkurang 1 setelah disetujui | Lulus |
| 6.6 | Jatuh tempo = pinjam + 7 hari | Lulus |
| 6.7 | Persetujuan ganda ditolak | Lulus |
| 6.8 | Stok tidak berkurang dua kali | Lulus |
| 6.9 | Penolakan tidak mengubah stok | Lulus |
| 6.10 | Batas 3 pinjaman aktif ditegakkan saat persetujuan | Lulus |

---

## 7. Skenario Pengembalian & Denda

| # | Skenario | Hasil |
|---|---|---|
| 7.1 | Pengembalian tepat waktu — denda Rp0 | Lulus |
| 7.2 | Stok kembali +1 | Lulus |
| 7.3 | Status menjadi Dikembalikan | Lulus |
| 7.4 | Pengembalian ganda ditolak | Lulus |
| 7.5 | Stok tidak bertambah dua kali | Lulus |
| 7.6 | Terlambat 3 hari → denda Rp3.000 | Lulus |
| 7.7 | Status denda menjadi Belum Lunas | Lulus |
| 7.8 | Denda dihitung per tanggal kalender | Lulus |
| 7.9 | Tandai denda Lunas | Lulus |
| 7.10 | Pelunasan ganda ditolak | Lulus |
| 7.11 | Tepat waktu = Rp0 (1, 3, 5 hari) | Lulus |
| 7.12 | Hari negatif tidak menghasilkan denda | Lulus |

---

## 8. Skenario UI

| # | Skenario | Hasil |
|---|---|---|
| 8.1 | Beranda: hero, pencarian, kategori, grid buku, footer | Lulus |
| 8.2 | Satu `<h1>` per halaman (beranda, dashboard, login) | Lulus |
| 8.3 | Skip link, `lang="id"`, viewport meta | Lulus |
| 8.4 | 17 halaman dashboard 3 role tanpa error PHP | Lulus |
| 8.5 | Empty state (belum ada buku/pengajuan/anggota) | Lulus |
| 8.6 | Pesan sukses/error flash | Lulus |
| 8.7 | Konfirmasi sebelum hapus/setujui/kembalikan | Lulus |
| 8.8 | Static asset (`clay.css`, `app.js`) termuat | Lulus |
| 8.9 | Token warna sesuai `design.md` | Lulus |
| 8.10 | Tidak ada overflow horizontal pada layout dashboard | Lulus (struktur, lihat §10) |

---

## 9. Bug yang Ditemukan dan Diperbaiki

| # | Bug | Dampak | Perbaikan |
|---|---|---|---|
| 9.1 | `save_book()` dideklarasikan `: never` padahal punya `return` di jalur validasi | Fatal error saat membuka tambah/edit buku | Ubah tipe kembali menjadi `: void` |
| 9.2 | `redirect('login.php')` dari subfolder (`admin/`, `petugas/`, `user/`) menghasilkan `/admin/login.php` | Halaman login tidak ditemukan saat akses tanpa hak | `app_base_url()` kini dihitung dari `DOCUMENT_ROOT`, bukan direktori script |
| 9.3 | **Placeholder PDO ganda** (`LIKE :search OR ... LIKE :search`) pada pencarian buku, anggota, peminjaman, dan denda — gagal dengan `ATTR_EMULATE_PREPARES = false` | Pencarian menyebabkan `PDOException: Invalid parameter number` (error 500) | Setiap kemunculan placeholder diberi nama unik (`:search_title`, `:search_author`, dst.) |
| 9.4 | Folder `config/` berisi `database.php` lama tanpa proteksi | Risiko kredensial terekspos | Konfigurasi dipusatkan, mendukung environment variable, dan dilindungi `.htaccess` |
| 9.5 | Halaman peminjaman petugas tidak memiliki filter status | Petugas sulit menemukan pengajuan menunggu | Ditambahkan tab **Semua / Menunggu / Dipinjam** yang mempertahankan kata kunci pencarian |
| 9.6 | **`app_config()` hanya melakukan lookup satu tingkat**, padahal `upload_service.php` memakai kunci bertingkat (`upload.dir`, `upload.mime_types`, `upload.extensions`) | `mime_types`/`extensions` = `null` → **semua upload cover ditolak** dengan pesan "Format cover harus JPG, PNG, atau WEBP." Ditemukan saat pengujian relokasi | `app_config()` mendukung notasi titik (nested lookup) tanpa mengubah perilaku kunci datar |
| 9.7 | **`app_base_url()` mengembalikan `/public`** ketika DocumentRoot = root proyek | Semua tautan & aset menjadi `/public/...` setelah relokasi | Ditambahkan kasus khusus: bila `public/` tepat satu level di bawah DOCUMENT_ROOT dan diakses via rewrite, base = `''` |

> Bug 9.6 **sudah ada sebelum relokasi** (bukan akibat relokasi) dan baru terungkap
> karena pengujian relokasi kali ini menempuh jalur **penerimaan** upload, bukan
> hanya penolakan. Bug 9.7 adalah konsekuensi langsung dari perubahan DocumentRoot
> dan diperbaiki dalam tugas ini.

---

## 10. Keterbatasan & Catatan

1. **Pengujian visual browser tidak dilakukan otomatis.** Model ini tidak memiliki
   kemampuan membaca gambar atau menjalankan browser headless. Verifikasi tampilan
   dilakukan pada struktur HTML, token warna, dan hierarki heading, bukan pada
   tangkapan layar visual. Responsivitas sebaiknya diverifikasi manual pada
   320 / 768 / 1024 / 1440 px karena CSS responsif disusun tetapi tidak diuji di browser nyata.

2. **Referensi gambar tidak dapat dibaca.** Berkas `Mockup UI Perpustakaan Lentera.png`
   tidak dapat dibaca oleh model ini, sehingga tampilan dibangun sepenuhnya dari
   `design.md` (tokens, komponen, wireframe teks) dan kebutuhan prompt. Bila mockup
   memiliki detail yang berbeda, penyesuaian visual mungkin diperlukan.

3. **Virtual host sudah aktif.** Konfigurasi `digital-library.test.conf` diarahkan
   ke `C:/laragon/www/digital-library` (root proyek), lolos validasi Apache
   (`httpd -t` = *Syntax OK*), entri hosts `127.0.0.1 digital-library.test` sudah
   ditambahkan, dan **Apache sudah di-restart**. `http://digital-library.test/`
   mengembalikan **200** dan melayani aplikasi. Instruksi ada di
   `reports/FINAL_LARAGON_SETUP.md` §8.

4. **Kolom `phone` belum ada di skema.** Prompt menyebut "nomor telepon bila diperlukan".
   Skema `digital_library.sql` tidak memiliki kolom ini, sehingga input telepon
   dinetralkan dengan aman (`store_member_phone()`). Menambah kolom memerlukan
   migration tambahan bila fitur ini ingin diaktifkan.

5. **Bootstrap dan font dimuat dari CDN.** Tanpa internet, tampilan menggunakan
   fallback font dan CSS kustom (`clay.css`) sehingga tetap terbaca, tetapi komponen
   bawaan Bootstrap (collapse, modal) tidak akan aktif.

6. **Pengujian konkurensi bersifat logis.** Alur persetujuan/pengembalian memakai
   `SELECT ... FOR UPDATE` di dalam transaksi dan sudah diuji untuk **klik ganda
   berurutan**. Pengujian dengan banyak koneksi paralel sungguhan (load test) tidak
   dilakukan karena berada di luar cakupan UAS.

---

## 11. Pengujian Relokasi Final (10 Nov 2026)

Dijalankan **langsung terhadap domain aktif** `http://digital-library.test` (port 80)
setelah Apache di-restart.

### 11.1 Routing & URL bersih

| # | Skenario | Hasil |
|---|---|---|
| 11.1.1 | `/` dan `/index.php` | 200, judul "Beranda — Lentera Digital Library" |
| 11.1.2 | `/katalog.php`, `/detail.php?id=1`, `/login.php` | 200 |
| 11.1.3 | `/assets/css/clay.css`, `/assets/js/app.js`, ikon SVG | 200 (MIME benar) |
| 11.1.4 | `/admin/*`, `/petugas/*`, `/user/*` | 200 (setelah login sesuai role) |
| 11.1.5 | HTML tanpa prefix `/public/` | Lulus — 0 kemunculan |
| 11.1.6 | HTML tanpa `localhost` / nomor port | Lulus |
| 11.1.7 | `/public/*` → kanonikalisasi 301 ke URL bersih | Lulus — 1 hop, berakhir 200 |

### 11.2 Proteksi internal (harus 403/404)

| URL | Status |
|---|---|
| `/config/database.php`, `/config/app.php` | 403 |
| `/database/digital_library.sql`, `/database/migrations/001_schema_adjustments.sql` | 403 |
| `/scripts/setup.php`, `/scripts/test_flow.php`, `/scripts/test_edge.php` | 403 |
| `/README.md`, `/PRD.md`, `/design.md`, `/TEST_REPORT.md` | 403 |
| `/reports/RELOCATION_REPORT.md`, `/reports/FINAL_LARAGON_SETUP.md`, `/reports/` | 403 |
| `/app/bootstrap.php`, `/app/helpers/functions.php`, `/app/` | 403 |
| `/config/`, `/database/` | 403 |
| `/storage-logs/error.log`, `/storage-logs/access.log` | 403 |
| `/.htaccess`, `/index.php.bak` | 403 |
| `/uploads/` (listing) | 403 |

**Ringkasan: 23/23 → 403.**

### 11.3 Alur bisnis end-to-end via HTTP (port 80)

| # | Langkah | Hasil |
|---|---|---|
| 11.3.1 | Anggota ajukan pinjam buku | `status=menunggu`, stok tetap |
| 11.3.2 | Petugas setujui | `status=dipinjam`, jatuh tempo +7 hari |
| 11.3.3 | Petugas catat pengembalian tepat waktu | `dikembalikan`, denda Rp0, stok kembali |
| 11.3.4 | Buku lain dipinjam, jatuh tempo dimundurkan 3 hari | — |
| 11.3.5 | Pengembalian terlambat | denda **Rp3.000**, `belum_lunas` |
| 11.3.6 | Admin tandai lunas (`pay_fine`) | `lunas` |
| 11.3.7 | Upload cover PNG valid | `302`, `cover_path` tersimpan |
| 11.3.8 | Cover disajikan via `/uploads/covers/<file>` | **200** |
| 11.3.9 | Pembersihan data uji | `users=3, categories=5, books=12, borrowings=0` |

### 11.4 Ringkasan angka

| Kategori | Jumlah | Lulus | Gagal |
|---|---:|---:|---:|
| Pemeriksaan HTTP (routing, keamanan, RBAC, sesi) | 79 | 79 | 0 |
| Alur bisnis & upload (HTTP) | 6 | 6 | 0 |
| Lint PHP (68 berkas) | 68 | 68 | 0 |
| `scripts/test_flow.php` | 27 | 27 | 0 |
| `scripts/test_edge.php` | 22 | 22 | 0 |
| **Total** | **202** | **202** | **0** |

---

## 12. Cara Menjalankan Ulang Pengujian

```powershell
# Uji alur peminjaman lengkap
php scripts\test_flow.php

# Uji kasus tepi, validasi, dan keamanan
php scripts\test_edge.php

# Periksa sintaks seluruh file PHP
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

Semua skrip uji membersihkan data uji secara otomatis, sehingga database tetap
bersih setelah pengujian.

Untuk pengujian HTTP, domain sudah aktif — cukup akses langsung:

```powershell
& curl.exe -s -o NUL -w "%{http_code}`n" "http://digital-library.test/"
& curl.exe -s -o NUL -w "%{http_code}`n" "http://digital-library.test/config/database.php"
```

Harapan: `200` (beranda) dan `403` (berkas internal).

Baris	Role	Username	Password default
49-50	Admin	admin	Admin#Lentera2024 (bisa di-override via env ADMIN_PASSWORD)
54	Petugas	petugas	Petugas#2024
55	Anggota	anggota	Anggota#2024