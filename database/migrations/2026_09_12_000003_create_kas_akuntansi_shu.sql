-- =====================================================================
-- KUTT SUKA MAKMUR - Foundation Schema 3/4: Kas, Akuntansi, SHU
-- Idempotent: safe to re-run, never drops or destroys existing data.
-- =====================================================================

-- ---------------------------------------------------------------
-- I. CHART OF ACCOUNTS & JURNAL
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chart_of_accounts (
  account_code     VARCHAR(10) NOT NULL,
  account_name     VARCHAR(120) NOT NULL,
  account_category ENUM('ASSET','LIABILITY','EQUITY','REVENUE','EXPENSE') NOT NULL,
  normal_balance   ENUM('DEBIT','CREDIT') NOT NULL,
  is_active        TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (account_code),
  KEY idx_coa_category (account_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS journal_entries (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  journal_id  VARCHAR(30) NOT NULL COMMENT 'Nomor jurnal unik, mis. JR-2026-000001',
  ref_voucher VARCHAR(30) NULL COMMENT 'Rujukan voucher/transaksi sumber',
  tanggal     DATE NOT NULL,
  account_code VARCHAR(10) NOT NULL,
  debet       DECIMAL(15,2) NOT NULL DEFAULT 0,
  kredit      DECIMAL(15,2) NOT NULL DEFAULT 0,
  keterangan  VARCHAR(255) NULL,
  created_by  INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_je_journal_id (journal_id),
  KEY idx_je_date (tanggal),
  KEY idx_je_account (account_code),
  KEY idx_je_ref (ref_voucher),
  CONSTRAINT fk_je_coa FOREIGN KEY (account_code) REFERENCES chart_of_accounts (account_code),
  CONSTRAINT fk_je_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- H. KAS (mutasi kas masuk/keluar via voucher)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cash_transactions (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  voucher_no    VARCHAR(30) NOT NULL COMMENT 'Nomor voucher unik, mis. KSM-2026-0001',
  jenis_kas     ENUM('MASUK','KELUAR') NOT NULL,
  account_code  VARCHAR(10) NOT NULL COMMENT 'Akun kas/bank (1101, 1102, ...)',
  nominal       DECIMAL(15,2) NOT NULL,
  keterangan    VARCHAR(255) NULL,
  tanggal_trans DATE NOT NULL,
  created_by    INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ct_voucher_no (voucher_no),
  KEY idx_ct_date (tanggal_trans),
  KEY idx_ct_jenis (jenis_kas),
  CONSTRAINT fk_ct_coa FOREIGN KEY (account_code) REFERENCES chart_of_accounts (account_code),
  CONSTRAINT fk_ct_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- J. SHU (Sisa Hasil Usaha)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shu_distributions (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  shu_no        VARCHAR(30) NOT NULL,
  tahun_buku    SMALLINT UNSIGNED NOT NULL,
  member_id     INT UNSIGNED NOT NULL,
  jasa_modal    DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'SHU atas simpanan',
  jasa_anggota  DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'SHU atas transaksi/jasa',
  total_shu     DECIMAL(15,2) NOT NULL,
  status_pencairan ENUM('PENDING','DIBAYAR','DIBATALKAN') NOT NULL DEFAULT 'PENDING',
  paid_at       DATETIME NULL,
  created_by    INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_shu_no (shu_no),
  UNIQUE KEY uq_shu_year_member (tahun_buku, member_id),
  KEY idx_shu_status (status_pencairan),
  CONSTRAINT fk_shu_member FOREIGN KEY (member_id) REFERENCES members (id),
  CONSTRAINT fk_shu_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
