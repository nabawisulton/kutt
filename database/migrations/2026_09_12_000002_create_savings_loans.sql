-- =====================================================================
-- KUTT SUKA MAKMUR - Foundation Schema 2/4: Simpanan, Pinjaman, Angsuran
-- Idempotent: safe to re-run, never drops or destroys existing data.
-- =====================================================================

-- ---------------------------------------------------------------
-- E. SIMPANAN (pokok / wajib / sukarela)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS savings_transactions (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  trans_no      VARCHAR(30) NOT NULL COMMENT 'Nomor transaksi unik, mis. SVP-2026-0001',
  member_id     INT UNSIGNED NOT NULL,
  jenis         ENUM('POKOK','WAJIB','SUKARELA') NOT NULL,
  tipe_transaksi ENUM('SETOR','TARIK') NOT NULL,
  nominal       DECIMAL(15,2) NOT NULL,
  tanggal_trans DATE NOT NULL,
  operator_user_id INT UNSIGNED NULL,
  keterangan    VARCHAR(255) NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_st_trans_no (trans_no),
  KEY idx_st_member (member_id),
  KEY idx_st_date (tanggal_trans),
  KEY idx_st_jenis (jenis),
  CONSTRAINT fk_st_member FOREIGN KEY (member_id) REFERENCES members (id),
  CONSTRAINT fk_st_operator FOREIGN KEY (operator_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- F. PINJAMAN
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS loans (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  loan_no       VARCHAR(30) NOT NULL COMMENT 'Nomor pinjaman unik, mis. PJM-2026-0001',
  member_id     INT UNSIGNED NOT NULL,
  pokok_pinjaman DECIMAL(15,2) NOT NULL,
  bunga_pertahun DECIMAL(5,2)  NOT NULL DEFAULT 12.00,
  tenor_bulan   SMALLINT UNSIGNED NOT NULL,
  sistem_bunga  ENUM('FLAT','MENURUN') NOT NULL DEFAULT 'FLAT',
  status        ENUM('PENDING','APPROVED','REJECTED','DISBURSED','LUNAS') NOT NULL DEFAULT 'PENDING',
  tanggal_pengajuan DATE NOT NULL,
  tanggal_disburse DATE NULL,
  keperluan     VARCHAR(255) NULL,
  agunan        VARCHAR(255) NULL,
  approved_by   INT UNSIGNED NULL,
  approved_at   DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_loans_loan_no (loan_no),
  KEY idx_loans_member (member_id),
  KEY idx_loans_status (status),
  KEY idx_loans_date (tanggal_pengajuan),
  CONSTRAINT fk_loans_member FOREIGN KEY (member_id) REFERENCES members (id),
  CONSTRAINT fk_loans_approver FOREIGN KEY (approved_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- G. ANGSURAN (jadwal & pembayaran)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS loan_schedules (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  loan_id       BIGINT UNSIGNED NOT NULL,
  angsuran_ke   SMALLINT UNSIGNED NOT NULL,
  jatuh_tempo   DATE NOT NULL,
  pokok_due     DECIMAL(15,2) NOT NULL,
  bunga_due     DECIMAL(15,2) NOT NULL,
  status        ENUM('UNPAID','PAID') NOT NULL DEFAULT 'UNPAID',
  paid_at       DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ls_loan_no (loan_id, angsuran_ke),
  KEY idx_ls_due (jatuh_tempo),
  KEY idx_ls_status (status),
  CONSTRAINT fk_ls_loan FOREIGN KEY (loan_id) REFERENCES loans (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loan_payments (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  payment_no    VARCHAR(30) NOT NULL COMMENT 'Nomor bukti pembayaran unik, mis. ANG-2026-0001',
  loan_id       BIGINT UNSIGNED NOT NULL,
  schedule_id   BIGINT UNSIGNED NULL,
  angsuran_ke   SMALLINT UNSIGNED NOT NULL,
  bayar_pokok   DECIMAL(15,2) NOT NULL DEFAULT 0,
  bayar_bunga   DECIMAL(15,2) NOT NULL DEFAULT 0,
  denda         DECIMAL(15,2) NOT NULL DEFAULT 0,
  total_bayar   DECIMAL(15,2) NOT NULL,
  tanggal_bayar DATE NOT NULL,
  operator_user_id INT UNSIGNED NULL,
  keterangan    VARCHAR(255) NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lp_payment_no (payment_no),
  KEY idx_lp_loan (loan_id),
  KEY idx_lp_date (tanggal_bayar),
  CONSTRAINT fk_lp_loan FOREIGN KEY (loan_id) REFERENCES loans (id),
  CONSTRAINT fk_lp_schedule FOREIGN KEY (schedule_id) REFERENCES loan_schedules (id) ON DELETE SET NULL,
  CONSTRAINT fk_lp_operator FOREIGN KEY (operator_user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
