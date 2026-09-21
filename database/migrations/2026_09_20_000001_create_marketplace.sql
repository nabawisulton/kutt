-- =====================================================================
-- KUTT SUKA MAKMUR - Migration 6: Marketplace Produk & Penjualan
-- Idempotent: safe to re-run, never drops or destroys existing data.
-- Multi-channel: Katalog publik, Marketplace (WA checkout), Multi Kasir POS.
-- =====================================================================

-- ---------------------------------------------------------------
-- A. PRODUK (katalog multi-marketplace)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sku           VARCHAR(40)  NOT NULL COMMENT 'Kode produk unik',
  name          VARCHAR(160) NOT NULL,
  category      VARCHAR(60)  NULL,
  description   TEXT         NULL,
  price         DECIMAL(18,2) NOT NULL DEFAULT 0 COMMENT 'Harga jual (Rp)',
  cost_price    DECIMAL(18,2) NULL COMMENT 'Harga pokok (opsional, utk margin)',
  stock         INT          NOT NULL DEFAULT 0 COMMENT 'Stok fisik saat ini',
  min_stock     INT          NOT NULL DEFAULT 5 COMMENT 'Batas stok minimum (alert)',
  unit          VARCHAR(20)  NOT NULL DEFAULT 'pcs',
  image_path    VARCHAR(255) NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1 COMMENT 'Tampil di marketplace publik',
  created_by    INT UNSIGNED NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_sku (sku),
  KEY idx_products_category (category),
  KEY idx_products_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- B. KARTU STOK (audit semua perubahan stok)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock_movements (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id   INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED NULL,
  change_qty   INT NOT NULL COMMENT '+ masuk, - keluar (penjualan/koreksi)',
  stock_after  INT NOT NULL,
  reason       ENUM('PURCHASE','SALE','ADJUSTMENT','CANCEL') NOT NULL DEFAULT 'ADJUSTMENT',
  reference    VARCHAR(40) NULL COMMENT 'No order/adj terkait',
  note         VARCHAR(255) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sm_product (product_id),
  KEY idx_sm_created (created_at),
  CONSTRAINT fk_stkmv_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
  CONSTRAINT fk_stkmv_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- C. ORDER PENJUALAN (multi-channel: marketplace WA / kasir POS)
--    Sumber dihitung dari SUM(items.total_price), BUKAN kolom total.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sales_orders (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_no        VARCHAR(30) NOT NULL COMMENT 'JUAL-YYYYMMDD-####',
  channel         ENUM('MARKETPLACE','POS') NOT NULL DEFAULT 'POS',
  buyer_name      VARCHAR(120) NOT NULL,
  buyer_phone     VARCHAR(30)  NULL,
  buyer_note      VARCHAR(255) NULL,
  cashier_user_id INT UNSIGNED NULL COMMENT 'Kasir yang melayani (POS)',
  payment_method  ENUM('CASH','TRANSFER','QRIS','COD') NOT NULL DEFAULT 'CASH',
  status          ENUM('NEW','PAID','PROCESSING','COMPLETED','CANCELLED') NOT NULL DEFAULT 'NEW',
  stock_applied   TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Stok sudah dipotong?',
  paid_at         DATETIME NULL,
  completed_at    DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_so_order_no (order_no),
  KEY idx_so_status (status),
  KEY idx_so_channel (channel),
  KEY idx_so_created (created_at),
  KEY idx_so_cashier (cashier_user_id),
  CONSTRAINT fk_so_cashier FOREIGN KEY (cashier_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sales_order_items (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id    BIGINT UNSIGNED NOT NULL,
  product_id  INT UNSIGNED NULL COMMENT 'NULL bila produk dihapus',
  product_name VARCHAR(160) NOT NULL COMMENT 'Snapshot nama',
  unit_price  DECIMAL(18,2) NOT NULL COMMENT 'Snapshot harga saat jual',
  quantity    INT NOT NULL DEFAULT 1,
  total_price DECIMAL(18,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_soi_order (order_id),
  KEY idx_soi_product (product_id),
  CONSTRAINT fk_soi_order FOREIGN KEY (order_id) REFERENCES sales_orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_soi_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- D. PRODUK CONTOH (hanya sekali, DB kosong)
-- ---------------------------------------------------------------
INSERT INTO products (sku, name, category, description, price, cost_price, stock, min_stock, unit, is_active, created_at)
SELECT * FROM (
  SELECT 'PRD-001' AS sku, 'Susu Segar Pasteurisasi 1L' AS name, 'Susu' AS category,
         'Susu murni segar hasil pasteurisasi higienis dari peternak anggota KUTT.' AS description,
         14000.00 AS price, 10000.00 AS cost_price, 120 AS stock, 10 AS min_stock, 'botol' AS unit, 1 AS is_active, NOW() AS created_at
  UNION ALL SELECT 'PRD-002', 'Yogurt Susu Sapi 250ml', 'Susu', 'Yogurt fermentasi alami tanpa pengawet.', 15000.00, 11000.00, 60, 10, 'cup', 1, NOW()
  UNION ALL SELECT 'PRD-003', 'Pakan Konsentrat 1kg', 'Pakan', 'Pakan konsentrat nutrisi tinggi untuk sapi perah.', 6500.00, 5000.00, 200, 25, 'kg', 1, NOW()
  UNION ALL SELECT 'PRD-004', 'Susu Pasteurisasi Cokelat 250ml', 'Susu', 'Susu cokelat pasteurisasi favorit anak.', 8000.00, 5500.00, 80, 15, 'cup', 1, NOW()
  UNION ALL SELECT 'PRD-005', 'Vitamin Ternak 500ml', 'Obat Hewan', 'Vitamin & suplemen kesehatan hewan ternak.', 35000.00, 27000.00, 40, 5, 'botol', 1, NOW()
) AS demo
WHERE NOT EXISTS (SELECT 1 FROM products LIMIT 1);
