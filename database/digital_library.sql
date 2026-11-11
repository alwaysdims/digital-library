-- LENTERA DIGITAL LIBRARY | Database MVP UAS
-- PHP Native 8.3 + MySQL/MariaDB (Laragon, Apache)
-- IMPORT: Laragon phpMyAdmin / HeidiSQL / MySQL CLI
-- Peminjaman fisik gratis; satu pengajuan = satu buku
-- Akun admin dibuat melalui PHP password_hash(), bukan password polos SQL.

CREATE DATABASE IF NOT EXISTS digital_library
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE digital_library;

-- Admin / petugas / user (anggota). Tidak ada registrasi user mandiri.
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  email VARCHAR(150) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','petugas','user') NOT NULL DEFAULT 'user',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS books (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  author VARCHAR(150) NOT NULL,
  published_year SMALLINT UNSIGNED NULL,
  synopsis TEXT NULL,
  cover_path VARCHAR(255) NULL,
  stock_total INT UNSIGNED NOT NULL DEFAULT 0,
  stock_available INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_books_category FOREIGN KEY (category_id)
    REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_stock_valid CHECK (stock_available <= stock_total),
  INDEX idx_books_title (title),
  INDEX idx_books_category (category_id)
) ENGINE=InnoDB;

-- Status terlambat dihitung dari due_at; bukan status transaksi tersendiri.
-- Denda final dihitung saat buku dikembalikan; sebelum itu hanya estimasi.
CREATE TABLE IF NOT EXISTS borrowings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  book_id BIGINT UNSIGNED NOT NULL,
  status ENUM('menunggu','ditolak','dipinjam','dikembalikan') NOT NULL DEFAULT 'menunggu',
  requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  borrowed_at DATE NULL,
  due_at DATE NULL,
  returned_at DATE NULL,
  fine_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  fine_status ENUM('tidak_ada','belum_lunas','lunas') NOT NULL DEFAULT 'tidak_ada',
  fine_paid_at DATETIME NULL,
  processed_by BIGINT UNSIGNED NULL,
  CONSTRAINT fk_borrowings_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_borrowings_book FOREIGN KEY (book_id)
    REFERENCES books(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_borrowings_staff FOREIGN KEY (processed_by)
    REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_borrowings_user_status (user_id, status),
  INDEX idx_borrowings_status (status),
  INDEX idx_borrowings_due (due_at),
  INDEX idx_borrowings_book (book_id)
) ENGINE=InnoDB;

-- Kategori contoh; aman dieksekusi ulang.
INSERT INTO categories (name) VALUES
  ('Fiksi'), ('Non-Fiksi'), ('Pendidikan'), ('Teknologi'), ('Pengembangan Diri')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Data buku bersifat opsional; silakan input dari halaman admin/petugas.
-- Jangan mengisi password_hash dengan teks password biasa.
-- Gunakan PHP: password_hash($password, PASSWORD_DEFAULT).

-- Aturan bisnis diterapkan DI PHP, bukan dengan tabel tambahan:
-- 1. Maksimal 3 transaksi berstatus 'dipinjam' per user.
-- 2. Masa pinjam 7 hari kalender dari borrowed_at (zona Asia/Jakarta).
-- 3. Gratis meminjam; denda Rp1.000 per hari terlambat per buku.
-- 4. Pengajuan 'menunggu' tidak mengurangi stock_available.
-- 5. Saat disetujui: transaksi database, periksa stok > 0,
--    kurangi stock_available 1, status 'dipinjam', isi borrowed_at/due_at.
-- 6. Saat dikembalikan: transaksi database, tambah stock_available 1,
--    isi returned_at, status 'dikembalikan', hitung dan simpan fine_amount;
--    fine_status='belum_lunas' jika denda > 0.
-- 7. Petugas menandai 'lunas' sesudah menerima pembayaran.
-- 8. Tidak boleh menghapus buku/anggota dengan histori peminjaman;
--    nonaktifkan akun atau simpan buku sebagai arsip jika dibutuhkan.
