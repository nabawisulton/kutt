-- =====================================================================
-- KUTT SUKA MAKMUR - Foundation Schema 1/4: Users & Members (Tahap 1)
-- Idempotent: safe to re-run, never drops or destroys existing data.
-- =====================================================================

-- ---------------------------------------------------------------
-- A. USERS, SESSIONS & SECURITY
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       VARCHAR(20)  NOT NULL COMMENT 'Kode tampil, mis. USR-001',
  username      VARCHAR(40)  NOT NULL,
  email         VARCHAR(120) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('SUPER_ADMIN','ADMIN','BENDAHARA','KETUA','STAFF','ANGGOTA') NOT NULL DEFAULT 'STAFF',
  full_name     VARCHAR(120) NOT NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_user_id (user_id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role),
  KEY idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  token      CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_prt_token (token),
  CONSTRAINT fk_prt_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NULL,
  action     VARCHAR(40)  NOT NULL,
  details    VARCHAR(1000) NULL,
  ip         VARCHAR(45)  NULL,
  timestamp  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_user (user_id),
  KEY idx_audit_action (action),
  KEY idx_audit_time (timestamp),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- C. MEMBERS (DATA ANGGOTA)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS members (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_no     VARCHAR(20)  NOT NULL COMMENT 'Nomor anggota unik, mis. AGT-2026-0001',
  nia           VARCHAR(30)  NULL COMMENT 'Nomor Induk Anggota (legacy)',
  nik           CHAR(16)     NULL,
  full_name     VARCHAR(120) NOT NULL,
  birth_place   VARCHAR(120) NULL,
  birth_date    DATE NULL,
  gender        ENUM('L','P') NULL,
  address       VARCHAR(255) NULL,
  phone         VARCHAR(20)  NULL,
  email         VARCHAR(120) NULL,
  occupation    VARCHAR(80)  NULL,
  group_name    VARCHAR(120) NULL COMMENT 'Kelompok tani',
  status        ENUM('AKTIF','CALON','NONAKTIF','KELUAR') NOT NULL DEFAULT 'CALON',
  photo_path    VARCHAR(255) NULL,
  joined_at     DATE NULL,
  created_by    INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_members_member_no (member_no),
  KEY idx_members_nik (nik),
  KEY idx_members_name (full_name),
  KEY idx_members_status (status),
  CONSTRAINT fk_members_creator FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
