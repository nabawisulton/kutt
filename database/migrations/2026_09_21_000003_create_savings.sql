-- =====================================================================
-- Tabungan Uang (tabungan kas koperasi) KUTT
-- - cash_savings_accounts      : pos saldo tabungan (per akun/fond)
-- - cash_savings_transactions  : buku besar keluar-masuk tabungan
-- Catatan penting: NAMA TABEL cash_savings_* disengaja.
--   `savings_transactions` SUDAH DIPAKAI modul SIMPANAN legacy
--   (POKOK/WAJIB/SUKARELA — dipakai FinController & Member::finance),
--   sehingga fitur tabungan kas tidak boleh menimpanya.
-- Dapat dipakai sebagai sumber pembayaran di kasir (metode TABUNGAN).
-- Tanpa mengubah tabel/skema existing lain.
-- =====================================================================

CREATE TABLE IF NOT EXISTS cash_savings_accounts (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_no    VARCHAR(40)  NOT NULL,
  name          VARCHAR(120) NOT NULL,
  balance       DECIMAL(14, 2) NOT NULL DEFAULT 0,
  note          VARCHAR(255) NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_by    INT UNSIGNED NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cs_accounts_no (account_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cash_savings_transactions (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  account_id     INT UNSIGNED NOT NULL,
  transaction_no VARCHAR(50)  NOT NULL,
  type           ENUM('SETOR', 'TARIK', 'PEMBAYARAN', 'REFUND', 'PENYESUAIAN') NOT NULL,
  amount         DECIMAL(14, 2) NOT NULL,
  balance_before DECIMAL(14, 2) NOT NULL,
  balance_after  DECIMAL(14, 2) NOT NULL,
  method         ENUM('KAS', 'TRANSFER', 'LAINNYA') NOT NULL DEFAULT 'KAS',
  description    VARCHAR(255) NULL,
  reference_type VARCHAR(40)  NULL,
  reference_id   INT UNSIGNED NULL,
  created_by     INT UNSIGNED NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cs_tx_account FOREIGN KEY (account_id)
      REFERENCES cash_savings_accounts (id) ON DELETE CASCADE,
  INDEX idx_cs_tx_account_date (account_id, created_at),
  INDEX idx_cs_tx_type (type),
  UNIQUE KEY uq_cs_tx_no (transaction_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- sales_orders boleh dibayar dari akun tabungan
ALTER TABLE sales_orders
  ADD COLUMN IF NOT EXISTS savings_account_id INT UNSIGNED NULL AFTER member_id;

-- Akun tabungan awal
INSERT INTO cash_savings_accounts (account_no, name, balance, note, is_active)
SELECT 'TBG-0001', 'Tabungan Umum KUTT', 0, 'Akun tabungan kas operasional (dibuat otomatis)', 1
WHERE NOT EXISTS (SELECT 1 FROM cash_savings_accounts WHERE account_no = 'TBG-0001');
