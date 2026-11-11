# FULL AUDIT AND BUG FIX REPORT

Tanggal audit: 11 November 2026
Aplikasi: Lentera Digital Library
URL: http://digital-library.test
Lingkungan: Laragon, Apache, PHP 8.3, MySQL

## Executive Summary

- File PHP ditemukan: 68.
- File CSS ditemukan: 1.
- File JavaScript ditemukan: 1.
- Halaman publik: 5.
- Halaman Admin, Petugas, Anggota: 16.
- Total halaman aplikasi terinventarisasi: 21.
- Bug ditemukan saat seluruh audit ulang: 7.
- Bug diperbaiki: 6.
- Fitur tidak diimplementasikan: 1 (nomor telepon, tidak ada kolom DB).
- Bug belum selesai: 0 pada alur yang dapat diuji.
- Verifikasi visual penuh: NOT TESTED. Screenshot browser berhasil dibuat, analisis visual provider gagal HTTP 403.

Status akhir: PASS untuk lint, test service, route utama, aset, keamanan folder, dan alur bisnis. NOT TESTED untuk visual manual seluruh breakpoint.

## Inventaris Halaman dan Endpoint

Publik:

- `/`
- `/index.php`
- `/katalog.php`
- `/detail.php?id=1`
- `/login.php`
- `/logout.php`
- `/favicon.ico`
- `/assets/css/clay.css`
- `/assets/js/app.js`
- `/uploads/covers/*`

Admin:

- `/admin/dashboard.php`
- `/admin/buku.php`
- `/admin/kategori.php`
- `/admin/petugas.php`
- `/admin/anggota.php`
- `/admin/peminjaman.php`
- `/admin/denda.php`

Petugas:

- `/petugas/dashboard.php`
- `/petugas/buku.php`
- `/petugas/kategori.php`
- `/petugas/anggota.php`
- `/petugas/peminjaman.php`
- `/petugas/pengembalian.php`
- `/petugas/denda.php`

Anggota:

- `/user/dashboard.php`
- `/user/pinjaman.php`
- `/user/riwayat.php`

Form action, internal link, redirect, asset URL, pagination, CSRF field, dan guard role diperiksa melalui source scan. Tidak ditemukan referensi domain lama, path drive `E:`, atau URL publik `/public` pada HTML aplikasi.

## Bug Details

### BUG-001 — Favicon 404

- Lokasi: `.htaccess` root.
- Halaman terdampak: seluruh halaman yang dimuat browser modern.
- Jenis: broken asset route.
- Bukti sebelum perbaikan: browser console mencatat `http://digital-library.test/favicon.ico` status `404`.
- Penyebab: browser meminta `/favicon.ico`, tetapi router hanya memetakan asset di `/assets/`.
- Perbaikan: tambah rewrite `^favicon\\.ico$` ke `public/assets/images/favicon.svg`.
- Uji ulang: `GET /favicon.ico` menghasilkan `200`.

### BUG-002 — Session timeout tidak ditegakkan

- Lokasi: `app/auth/session.php`, `config/app.php`.
- Halaman terdampak: seluruh halaman privat.
- Jenis: authentication/session control.
- Penyebab: `last_activity` dibuat saat login, tetapi tidak pernah diperiksa terhadap batas waktu.
- Perbaikan: tambah `session_timeout = 1800` detik dan invalidasi sesi setelah idle timeout terlewati.
- Uji ulang: PHP lint lulus; alur autentikasi dan test service tetap lulus.
- Catatan: pengujian menunggu 30 menit tidak dijalankan karena tidak efisien; cabang timeout diperiksa lewat source review dan konfigurasi terpusat.

## Test Results

### PASS

- PHP syntax: 68/68 file lulus `php -l`.
- Borrowing flow: 27/27 lulus melalui `php scripts/test_flow.php`.
- Edge and resilience: 22/22 lulus melalui `php scripts/test_edge.php`.
- Route utama: `/`, `/index.php`, `/katalog.php`, `/detail.php?id=1`, `/login.php` → `200`.
- Asset CSS/JS: `/assets/css/clay.css`, `/assets/js/app.js` → `200`.
- Favicon: `/favicon.ico` → `200` setelah perbaikan.
- Protected files: `/config/database.php`, `/database/digital_library.sql`, `/README.md` → `403`.
- Database connection: test service berjalan tanpa PDO exception.
- Prepared statements: static scan tidak menemukan concatenation input langsung ke query.
- CSRF: semua form write utama memiliki `csrf_field()` dan controller memanggil `csrf_verify()`.
- XSS: escaping helper aktif; edge test XSS lulus.
- Upload validation: file non-gambar, ukuran berlebih, dan ekstensi PHP ditolak.
- Borrowing: stok, status, batas 3 buku, duplikasi, approval ganda, return ganda, dan denda lulus.
- Timezone: `Asia/Jakarta` dikonfigurasi.
- Session cookie: HttpOnly, SameSite=Lax, Secure mengikuti HTTPS.

### NOT TESTED

- Screenshot visual seluruh halaman pada 320, 375, 768, 1024, 1366, dan 1440 px.
- Analisis visual screenshot browser gagal karena provider vision mengembalikan HTTP 403. Screenshot tersedia di:
  `C:\Users\ASUS\AppData\Local\hermes\cache\screenshots\browser_screenshot_61be0b58e45545278edca9536ce20259.png`
- Full interactive browser login tiga role pada sesi audit ini. `TEST_REPORT.md` mencatat bukti HTTP sebelumnya untuk login, RBAC, logout, dan alur bisnis.
- Concurrent multi-connection load test.
- Real 30-minute idle timeout wait.

### SKIP

- Edge test stok sedang dipinjam: fixture saat test tidak memiliki buku uji yang sedang dipinjam. Test melaporkan skip, bukan pass.

### BUG-003 — Pengembalian dapat commit tanpa menambah stok

- Lokasi: `app/services/borrowing_service.php`.
- Halaman terdampak: halaman pengembalian Admin/Petugas.
- Jenis: integritas transaksi dan stok.
- Penyebab: update stok memakai guard `stock_available < stock_total`, tetapi hasil `rowCount()` diabaikan. Transaksi dapat menjadi `dikembalikan` tanpa stok bertambah saat data stok sudah tidak konsisten.
- Perbaikan: rollback transaksi bila update stok tidak mengubah baris.
- Uji ulang: `test_flow.php` lulus `27/27`; invariant DB menunjukkan `negative_stock=0`, `invalid_return=0`, `active_without_dates=0`.

### BUG-004 — Akun nonaktif atau role berubah tetap berwenang sampai timeout

- Lokasi: `app/auth/guard.php`.
- Dampak: sesi lama dapat memakai hak lama sampai 30 menit.
- Perbaikan: `require_login()` membaca ulang user dari DB pada setiap halaman privat, lalu menghapus sesi bila akun nonaktif atau role berubah.
- Uji ulang: lint dan regression suite lulus.

### BUG-005 — Penggantian cover dapat menghapus cover lama sebelum penyimpanan berhasil

- Lokasi: `app/controllers/book_controller.php`.
- Dampak: validasi gagal atau update stok gagal dapat meninggalkan referensi cover lama yang sudah terhapus.
- Perbaikan: cover baru dihapus saat validasi/update gagal; cover lama baru dihapus setelah `book_update()` berhasil.
- Uji ulang: lint dan regression suite lulus.

### BUG-006 — Logout memakai GET tanpa CSRF

- Lokasi: `public/logout.php`, `app/views/layouts/dashboard.php`.
- Dampak: halaman pihak ketiga dapat memaksa logout browser.
- Perbaikan: logout menjadi POST dengan token CSRF. GET menghasilkan `405`; POST tanpa token menghasilkan `403`.
- Uji ulang: probe HTTP menghasilkan `GET=405`, `POST tanpa CSRF=403`.

### NOT IMPLEMENTED-001 — Nomor telepon anggota tidak disimpan

- Lokasi: `app/controllers/member_controller.php`, skema `database/digital_library.sql`.
- Dampak: tidak ada persistence nomor telepon.
- Status: tidak diubah menjadi fake persistence. Skema tidak punya kolom `phone`, dan field telepon tidak tampil di form aktif.
- Tindakan lanjutan: tambah migration dan UI hanya jika fitur telepon memang masuk scope.

## Security Review

- Folder `app/`, `config/`, `database/`, `scripts/`, `reports/`, dan `storage-logs/` diblokir Apache.
- File dokumentasi, SQL, log, backup, konfigurasi, dan hidden file diblokir.
- Upload directory menolak eksekusi PHP.
- SQL query memakai PDO prepared statements.
- Output memakai `htmlspecialchars` melalui helper `e()`.
- Akses role diperiksa server-side melalui `require_admin()`, `require_staff()`, dan `require_user()`.
- POST write actions memakai CSRF.
- Tidak ditemukan `eval()`, `innerHTML`, `document.write`, atau query MySQL legacy.
- Tidak ada secret yang ditambahkan atau dicetak dalam laporan.

## Files Changed

- `C:\laragon\www\digital-library\.htaccess`
- `C:\laragon\www\digital-library\app\auth\session.php`
- `C:\laragon\www\digital-library\app\auth\guard.php`
- `C:\laragon\www\digital-library\app\controllers\book_controller.php`
- `C:\laragon\www\digital-library\app\controllers\member_controller.php`
- `C:\laragon\www\digital-library\app\services\borrowing_service.php`
- `C:\laragon\www\digital-library\app\views\layouts\dashboard.php`
- `C:\laragon\www\digital-library\config\app.php`
- `C:\laragon\www\digital-library\public\logout.php`
- `C:\laragon\www\digital-library\reports\FULL_AUDIT_AND_BUG_FIX_REPORT.md`

## Final Status

- PASS: lint PHP.
- PASS: database/service regression.
- PASS: route utama dan asset.
- PASS: security folder protection.
- PASS: CSRF, escaping, prepared statement static checks.
- PASS: borrowing, return, stock, fine logic.
- NOT TESTED: visual responsive full matrix.
- NOT TESTED: real idle timeout wait.
- BLOCKED: visual image analysis provider returned HTTP 403.

Tidak ada bug lain yang dapat dibuktikan dan aman diperbaiki dari audit ini. Visual manual tetap diperlukan sebelum klaim final untuk seluruh breakpoint.
