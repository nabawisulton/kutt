-- =====================================================================
-- KUTT SUKA MAKMUR - Migration 8: Barcode produk untuk kasir POS
-- Barcode akan di-generate otomatis (BC-<id>) bila kosong saat disimpan.
-- Idempotent: aman dijalankan ulang.
-- =====================================================================

-- MariaDB: ADD COLUMN IF NOT EXISTS tidak tersedia utk semua versi; gunakan
-- pengecekan manual agar idempotent.
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'barcode');
SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE products ADD COLUMN barcode VARCHAR(40) NULL AFTER sku, ADD KEY idx_products_barcode (barcode)',
  'SELECT 1');
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Generate barcode otomatis utk produk existing yang belum punya (BC-<id>).
UPDATE products SET barcode = CONCAT('BC-', LPAD(id, 5, '0')) WHERE barcode IS NULL OR barcode = '';
