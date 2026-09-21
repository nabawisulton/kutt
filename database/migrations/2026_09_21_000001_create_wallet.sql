-- =====================================================================
-- KUTT SUKA MAKMUR - Migration 7: Saldo Anggota (Wallet) + PIN Transaksi
-- Idempotent: safe to re-run, never drops or destroys existing data.
-- QR kartu anggota existing TETAP dipakai sebagai identifikasi; PIN 6 digit
-- (hash) menjadi verifikasi untuk semua aksi finansial.
-- =====================================================================

-- ---------------------------------------------------------------
-- A. WALLET ANGGOTA (satu wallet per anggota + PIN transaksi)
--    pin_hash: bcrypt, TIDAK PERNAH plaintext. pin_attempts +
--    pin_locked_until = pembatasan percobaan PIN yang salah.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS member_wallets (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id        INT UNSIGNED NOT NULL,
  balance          DECIMAL(18,2) NOT NULL DEFAULT 0,
  pin_hash         VARCHAR(255) NULL COMMENT 'bcrypt dari PIN 6 digit',
  pin_attempts     INT NOT NULL DEFAULT 0,
  pin_locked_until DATETIME NULL,
  updated_at       DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_wallet_member (member_id),
  CONSTRAINT fk_wallet_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- B. RIWAYAT MUTASI SALDO (ledger lengkap, signed amount)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wallet_transactions (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id      INT UNSIGNED NOT NULL,
  transaction_no VARCHAR(30) NOT NULL COMMENT 'SAL-YYYYMMDD-####',
  type           ENUM('TOPUP','PEMBAYARAN','REFUND','PENYESUAIAN') NOT NULL,
  amount         DECIMAL(18,2) NOT NULL COMMENT '+ kredit, - debit',
  balance_before DECIMAL(18,2) NOT NULL,
  balance_after  DECIMAL(18,2) NOT NULL,
  description    VARCHAR(255) NULL,
  reference      VARCHAR(40) NULL COMMENT 'No order / voucher terkait',
  order_id       BIGINT UNSIGNED NULL,
  user_id        INT UNSIGNED NULL COMMENT 'Kasir/admin; NULL bila oleh anggota sendiri',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_wt_no (transaction_no),
  KEY idx_wt_member (member_id),
  KEY idx_wt_created (created_at),
  CONSTRAINT fk_wt_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
  CONSTRAINT fk_wt_order FOREIGN KEY (order_id) REFERENCES sales_orders (id) ON DELETE SET NULL,
  CONSTRAINT fk_wt_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- C. PENGAJUAN TOP UP dari anggota (via halaman hasil scan QR,
--    wajib PIN) — TIDAK langsung menambah saldo; diproses admin.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS topup_requests (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id    INT UNSIGNED NOT NULL,
  request_no   VARCHAR(30) NOT NULL COMMENT 'TOP-YYYYMMDD-####',
  amount       DECIMAL(18,2) NOT NULL,
  note         VARCHAR(255) NULL,
  status       ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  processed_by INT UNSIGNED NULL,
  processed_at DATETIME NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tr_no (request_no),
  KEY idx_tr_status (status),
  CONSTRAINT fk_tr_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
  CONSTRAINT fk_tr_processor FOREIGN KEY (processed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- D. sales_orders: pembayaran via saldo anggota
-- ---------------------------------------------------------------
ALTER TABLE sales_orders ADD COLUMN member_id INT UNSIGNED NULL AFTER cashier_user_id;
ALTER TABLE sales_orders
  MODIFY payment_method ENUM('CASH','TRANSFER','QRIS','COD','SALDO','TABUNGAN') NOT NULL DEFAULT 'CASH';

-- ---------------------------------------------------------------
-- E. COA kewajiban saldo belanja anggota (utk jurnal top up)
-- ---------------------------------------------------------------
INSERT INTO chart_of_accounts (account_code, account_name, account_category, normal_balance, is_active)
SELECT '2104', 'Saldo Belanja Anggota', 'LIABILITY', 'CREDIT', 1
WHERE NOT EXISTS (SELECT 1 FROM chart_of_accounts WHERE account_code = '2104');

-- ---------------------------------------------------------------
-- F. Wallet awal (saldo 0) untuk anggota yang sudah ada
-- ---------------------------------------------------------------
INSERT INTO member_wallets (member_id, balance)
SELECT m.id, 0 FROM members m
WHERE NOT EXISTS (SELECT 1 FROM member_wallets w WHERE w.member_id = m.id);
