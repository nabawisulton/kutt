-- =====================================================================
-- KUTT SUKA MAKMUR - Foundation Schema 4/4: CMS, Kartu, Berita, Notifikasi
-- Idempotent: safe to re-run, never drops or destroys existing data.
-- =====================================================================

-- ---------------------------------------------------------------
-- K. SETTINGS / CMS
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(60) NOT NULL,
  setting_value TEXT NULL,
  updated_at    DATETIME NULL,
  updated_by    INT UNSIGNED NULL,
  PRIMARY KEY (setting_key),
  CONSTRAINT fk_settings_user FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- D. KARTU ANGGOTA
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS member_cards (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id   INT UNSIGNED NOT NULL,
  card_serial VARCHAR(30) NOT NULL COMMENT 'Nomor seri kartu unik',
  verify_code CHAR(12) NOT NULL COMMENT 'Kode verifikasi publik',
  issued_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  issued_by   INT UNSIGNED NULL,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mc_serial (card_serial),
  UNIQUE KEY uq_mc_verify (verify_code),
  UNIQUE KEY uq_mc_member_active (member_id, is_active),
  CONSTRAINT fk_mc_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
  CONSTRAINT fk_mc_user FOREIGN KEY (issued_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- L. BERITA / NEWS
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS news_posts (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(160) NOT NULL,
  title       VARCHAR(200) NOT NULL,
  body        MEDIUMTEXT NULL,
  image_path  VARCHAR(255) NULL,
  video_url   VARCHAR(255) NULL,
  category    VARCHAR(60) NULL,
  status      ENUM('DRAFT','PUBLISHED') NOT NULL DEFAULT 'DRAFT',
  views       INT UNSIGNED NOT NULL DEFAULT 0,
  likes       INT UNSIGNED NOT NULL DEFAULT 0,
  shares      INT UNSIGNED NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  created_by  INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_np_slug (slug),
  KEY idx_np_status (status),
  KEY idx_np_category (category),
  KEY idx_np_published (published_at),
  CONSTRAINT fk_np_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_comments (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id    BIGINT UNSIGNED NOT NULL,
  name       VARCHAR(120) NOT NULL,
  body       VARCHAR(1000) NOT NULL,
  status     ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_nc_post (post_id),
  KEY idx_nc_status (status),
  CONSTRAINT fk_nc_post FOREIGN KEY (post_id) REFERENCES news_posts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- M. NOTIFIKASI
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NULL COMMENT 'NULL = broadcast ke semua user',
  title      VARCHAR(160) NOT NULL,
  message    VARCHAR(500) NULL,
  type       ENUM('INFO','SUCCESS','WARNING','DANGER') NOT NULL DEFAULT 'INFO',
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notif_user (user_id),
  KEY idx_notif_read (is_read),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
