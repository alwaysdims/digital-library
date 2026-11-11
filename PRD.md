# PRD — Digital Library

**Versi:** 1.0 (MVP UAS)  
**Status:** Rancangan awal untuk implementasi  
**Jenis aplikasi:** Sistem informasi perpustakaan berbasis web  
**Target:** Proyek UAS mahasiswa semester 1  
**Pendekatan:** Sederhana, mudah dipresentasikan, dan dapat dijalankan secara lokal maupun di-hosting

## 1. Ringkasan produk

Digital Library adalah website untuk melihat katalog dan sinopsis buku, mengelola data buku dan anggota, serta mencatat peminjaman dan pengembalian **buku fisik**. Peminjaman **gratis**. Jika buku terlambat dikembalikan, sistem menghitung denda yang dicatat oleh petugas.

Pengunjung dapat melihat katalog tanpa login. Hanya anggota yang telah **didaftarkan oleh petugas** dan memiliki akun yang dapat mengajukan peminjaman. Aplikasi memiliki tepat **tiga role**: Admin, Petugas, dan User/Anggota.

## 2. Tujuan dan batasan

### Tujuan
- Menyediakan katalog buku dan sinopsis yang mudah dicari.
- Memudahkan petugas mendaftarkan anggota dan mencatat transaksi buku.
- Memberikan informasi status pinjaman dan denda secara jelas.
- Menunjukkan penerapan CRUD, login, role, relasi database, dan logika perhitungan tanggal untuk tugas UAS.

### Di luar lingkup versi 1.0
- E-book/PDF reader dan unggah file buku digital.
- Registrasi mandiri, verifikasi email, dan reset password melalui email.
- Pembayaran online, QR/barcode, notifikasi WhatsApp/email.
- Grafik rumit, ekspor Excel, reservasi antrean, multi-cabang.
- API terpisah, aplikasi mobile native, sistem denda otomatis berkala/cron.

## 3. Teknologi

| Kebutuhan | Pilihan |
|---|---|
| Frontend | HTML, CSS, JavaScript dasar |
| Komponen UI | Bootstrap 5 + CSS kustom tema Soft Clay |
| Backend | PHP Native (tanpa Laravel) |
| Database | MySQL/MariaDB dengan PDO prepared statements |
| Lokal | XAMPP (Apache + database) |
| Publikasi opsional | Hosting PHP + MySQL gratis yang mendukung fitur aplikasi; ngrok hanya untuk demo sementara |

Tidak memerlukan Node.js untuk menjalankan website produksi.

## 4. Peran dan hak akses

| Fitur | Pengunjung (tanpa role/login) | Admin | Petugas | User |
|---|:---:|:---:|:---:|:---:|
| Lihat katalog, cari, baca sinopsis | Ya | Ya | Ya | Ya |
| Login | - | Ya | Ya | Ya |
| Kelola buku dan kategori | Tidak | Ya | Ya | Tidak |
| Kelola akun petugas | Tidak | Ya | Tidak | Tidak |
| Buat akun anggota | Tidak | Ya | Ya | Tidak |
| Ajukan pinjaman buku | Tidak | Tidak | Tidak | Ya |
| Setujui/tolak pengajuan | Tidak | Ya | Ya | Tidak |
| Catat pengembalian dan pembayaran denda | Tidak | Ya | Ya | Tidak |
| Lihat status pinjaman sendiri | Tidak | Tidak | Tidak | Ya |
| Lihat daftar transaksi keseluruhan | Tidak | Ya | Ya | Tidak |

**Catatan:** Pengunjung bukan role keempat; ini sekadar halaman publik sebelum login. User tidak dapat membuat akun sendiri.

## 5. Fitur MVP

### 5.1 Publik
- Beranda singkat dengan pencarian buku dan akses katalog.
- Katalog: cover, judul, penulis, kategori, dan indikator ketersediaan.
- Detail buku: cover, judul, penulis, kategori, tahun terbit (jika ada), sinopsis, dan stok tersedia.
- Tombol **Ajukan Peminjaman** mengarahkan pengunjung yang belum login ke login dan informasi pendaftaran melalui petugas.

### 5.2 Autentikasi
- Login menggunakan username/email dan password.
- Logout.
- Hak akses dipastikan di server untuk setiap halaman/aksi.
- Akun anggota dibuat petugas; admin dapat membuat akun petugas.

### 5.3 Admin
- Melihat ringkasan angka sederhana: buku, anggota, pinjaman aktif, denda belum lunas.
- CRUD buku dan kategori.
- Membuat, melihat, mengubah, menonaktifkan akun petugas.
- Melihat semua transaksi peminjaman.

### 5.4 Petugas
- CRUD buku dan kategori.
- Membuat serta memperbarui data anggota.
- Melihat daftar pengajuan, menyetujui/menolak peminjaman.
- Mencatat pengembalian buku dan denda jika terlambat.
- Mencatat status denda **Belum Lunas** atau **Lunas**.

### 5.5 User/Anggota
- Melihat katalog dan sinopsis.
- Mengajukan peminjaman satu buku per pengajuan (mengurangi kompleksitas).
- Melihat pengajuan menunggu, pinjaman aktif, jatuh tempo, dan riwayat.
- Melihat denda terkait pinjaman sendiri bila ada.

## 6. Aturan bisnis

| Ketentuan | Nilai versi 1.0 |
|---|---|
| Jenis buku | Buku fisik |
| Biaya peminjaman | **Rp0** |
| Masa pinjam | **7 hari kalender** sejak petugas menyerahkan buku |
| Batas peminjaman | Maksimal **3 buku aktif** per anggota |
| Denda keterlambatan | **Rp1.000/hari/buku** |
| Pengajuan | **1 buku per pengajuan** |
| Ketersediaan | Peminjaman disetujui hanya bila stok tersedia > 0 |

- Tanggal jatuh tempo = tanggal pinjam + 7 hari kalender.
- Hari terlambat = `max(0, selisih_hari(tanggal_kembali_aktual, tanggal_jatuh_tempo))`. Perhitungan berdasarkan tanggal kalender lokal (`Asia/Jakarta`), bukan durasi jam.
- Denda = hari terlambat × Rp1.000 untuk satu buku.
- Saat buku belum dikembalikan, antarmuka boleh menampilkan **estimasi** denda sampai tanggal hari ini; jangan menganggapnya sebagai pembayaran atau denda final.
- Pada saat pengembalian, jumlah denda **difinalisasi**, dan tidak lagi bertambah setelahnya.
- Peminjaman dan pengembalian adalah dua aksi berbeda: stok **berkurang saat petugas menyerahkan buku**, bukan ketika pengajuan dibuat; stok **bertambah saat pengembalian dicatat**.
- Jika stok habis sebelum pengajuan disetujui, petugas harus menolak pengajuan tersebut.
- Pengajuan yang masih menunggu tidak dihitung sebagai pinjaman aktif; bila disetujui, batas maksimum 3 pinjaman aktif wajib diperiksa kembali.
- Denda tidak dipungut untuk peminjaman tepat waktu. Petugas hanya mencatat pembayaran secara manual; **tidak ada payment gateway**.
- Untuk MVP, besaran denda dan masa pinjam adalah konfigurasi tetap di kode/aplikasi (tidak perlu halaman pengaturan khusus).

## 7. Alur pengguna

### A. Pendaftaran anggota
1. Pengunjung melihat katalog dan ingin meminjam.
2. Pengunjung menghubungi petugas secara langsung.
3. Petugas memasukkan data anggota dan membuat akun.
4. Anggota menerima username dan cara mengatur password, lalu login.

### B. Peminjaman
1. Anggota login, membuka detail buku, dan menekan **Ajukan Peminjaman**.
2. Sistem mencatat pengajuan berstatus `Menunggu`.
3. Petugas memeriksa stok dan jumlah pinjaman aktif anggota.
4. Jika memenuhi aturan, petugas menyetujui saat menyerahkan buku; sistem mencatat tanggal pinjam, jatuh tempo, dan mengurangi stok secara atomik.
5. Jika ditolak, pengajuan berstatus `Ditolak` dan stok tidak berubah.

### C. Pengembalian
1. Anggota menyerahkan buku fisik ke petugas.
2. Petugas membuka transaksi `Dipinjam`, lalu mencatat tanggal kembali.
3. Sistem menghitung hari terlambat dan denda final.
4. Stok buku bertambah satu, status menjadi `Dikembalikan`.
5. Jika denda > Rp0, petugas mencatat `Belum Lunas` atau `Lunas` setelah pembayaran diterima.

## 8. Status data

**Status transaksi:** `Menunggu`, `Ditolak`, `Dipinjam`, `Dikembalikan`.  
**Terlambat** adalah kondisi yang dihitung dari tanggal jatuh tempo pada transaksi `Dipinjam`, bukan status transaksi tersendiri.  
**Status denda:** `Tidak Ada`, `Belum Lunas`, `Lunas`.

## 9. Rancangan database minimal

| Tabel | Kolom penting |
|---|---|
| `users` | id, name, username, password_hash, role (`admin`/`petugas`/`user`), is_active, created_at |
| `categories` | id, name |
| `books` | id, category_id, title, author, published_year, synopsis, cover_path, stock_total, stock_available |
| `borrowings` | id, user_id, book_id, status, requested_at, borrowed_at, due_at, returned_at, fine_amount, fine_status, processed_by |

Satu pengajuan = satu buku. Data denda disimpan di transaksi agar tidak perlu membuat tabel terpisah. Pencatatan denda hanya untuk status `Dikembalikan`; estimasi sebelum pengembalian dihitung pada tampilan. `fine_amount` default 0, `fine_status` default `Tidak Ada`.

## 10. Halaman utama

**Publik:** Beranda, Katalog, Detail Buku, Login.  
**Admin:** Ringkasan, Buku & Kategori, Petugas, Transaksi.  
**Petugas:** Ringkasan, Buku, Anggota, Pengajuan/Peminjaman, Pengembalian & Denda.  
**User:** Katalog, Pinjaman Saya, Riwayat.

Role menggunakan layout dashboard yang sama, tetapi menu dan aksi yang tampil berbeda sesuai hak akses.

## 11. Persyaratan keamanan minimum

- Password wajib di-hash dengan `password_hash()` dan diverifikasi dengan `password_verify()`.
- Gunakan PDO prepared statements pada seluruh query yang menerima input.
- Gunakan session login yang aman, regenerate session ID setelah login, dan lakukan logout dengan benar.
- Validasi input di server; escape output teks untuk mencegah XSS.
- Aksi tulis melalui POST dan token CSRF; tindakan hapus memerlukan konfirmasi.
- Lindungi semua halaman dan handler berdasarkan role pada backend.
- Validasi upload cover: hanya tipe gambar yang diizinkan, batas ukuran, nama file aman, dan simpan agar tidak dieksekusi sebagai PHP.
- Proses persetujuan dan pengembalian memakai database transaction dan pemeriksaan stok agar klik ganda tidak menggandakan transaksi.
- Gunakan data fiktif untuk demo publik dan jangan mengekspos phpMyAdmin/XAMPP ke internet.

## 12. Kriteria penerimaan / skenario demo UAS

1. Pengunjung dapat membuka katalog, mencari buku, serta membaca sinopsis tanpa login.
2. Pengunjung tidak dapat mengajukan peminjaman sebelum menjadi anggota dan login.
3. Petugas dapat membuat akun anggota baru yang berhasil login.
4. Anggota dapat mengajukan buku yang tersedia, status menjadi `Menunggu`.
5. Petugas menyetujui satu pengajuan; stok berkurang satu dan tanggal jatuh tempo 7 hari tercatat.
6. Batas maksimum tiga pinjaman aktif ditegakkan.
7. Petugas mengembalikan buku tepat waktu; denda Rp0 dan stok bertambah.
8. Petugas mengembalikan buku 3 hari terlambat; denda tercatat Rp3.000 dan dapat ditandai `Lunas`.
9. Admin dapat mengelola akun petugas; user tidak bisa mengakses menu admin dengan mengetik URL langsung.
10. Aplikasi berjalan di XAMPP dan halaman utama dapat dibuka dari browser.

## 13. Urutan pengembangan

1. Setup XAMPP + database + koneksi PDO.
2. Login/session/role.
3. CRUD kategori dan buku + katalog publik + detail/sinopsis.
4. Daftar anggota oleh petugas.
5. Pengajuan, persetujuan, pengembalian, stok.
6. Denda, riwayat, ringkasan dashboard.
7. Uji alur ketiga role dan tampilan responsif.
8. Opsional: deploy hosting PHP/MySQL gratis setelah pengujian lokal berhasil.

## 14. Keputusan tetap dan asumsi

- Tidak menggunakan Laravel.
- Tidak menyediakan e-book di MVP.
- Sistem **gratis** untuk peminjaman; denda hanya karena terlambat.
- Pendaftaran anggota hanya melalui petugas.
- Tidak ada registrasi publik.
- Desain mengikuti `design.md` (tema Soft Clay Digital Library).
- Contoh nama aplikasi pada rancangan UI: **Lentera Digital Library**; nama bisa diganti tanpa mengubah logika aplikasi.
