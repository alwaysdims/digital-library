-- =====================================================================
-- Migration 001 — Penyesuaian skema Lentera Digital Library
-- Aman dijalankan berulang (idempotent). Tidak menghapus data/tabel.
-- Jalankan hanya bila tabel dari digital_library.sql sudah ada.
-- =====================================================================

-- Kolom fine_paid_at mungkin belum ada pada instalasi lama.
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'borrowings'
    AND COLUMN_NAME = 'fine_paid_at'
);

SET @sql := IF(@col_exists = 0,
  'ALTER TABLE borrowings ADD COLUMN fine_paid_at DATETIME NULL AFTER fine_status',
  'SELECT ''fine_paid_at sudah ada'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Index tambahan untuk mempercepat filter denda belum lunas.
SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'borrowings'
    AND INDEX_NAME = 'idx_borrowings_fine_status'
);

SET @sql := IF(@idx_exists = 0,
  'ALTER TABLE borrowings ADD INDEX idx_borrowings_fine_status (fine_status)',
  'SELECT ''idx_borrowings_fine_status sudah ada'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
