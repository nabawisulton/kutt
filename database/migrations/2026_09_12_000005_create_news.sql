-- =====================================================================
-- KUTT SUKA MAKMUR - Migration 5: Portal Berita (News)
-- Idempotent: safe to re-run, never drops or destroys existing data.
-- =====================================================================

CREATE TABLE IF NOT EXISTS news_categories (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_nc_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_tags (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_nt_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_posts (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug          VARCHAR(190) NOT NULL,
  title         VARCHAR(200) NOT NULL,
  excerpt       VARCHAR(500) NULL,
  body          LONGTEXT NULL,
  image_path    VARCHAR(255) NULL COMMENT 'Gambar utama / thumbnail OG',
  video_url     VARCHAR(255) NULL,
  category_id   INT UNSIGNED NULL,
  author_id     INT UNSIGNED NULL,
  author_name   VARCHAR(120) NULL,
  status        ENUM('DRAFT','PUBLISHED') NOT NULL DEFAULT 'DRAFT',
  views         INT UNSIGNED NOT NULL DEFAULT 0,
  likes         INT UNSIGNED NOT NULL DEFAULT 0,
  shares        INT UNSIGNED NOT NULL DEFAULT 0,
  comments_count INT UNSIGNED NOT NULL DEFAULT 0,
  published_at  DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_np_slug (slug),
  KEY idx_np_status (status),
  KEY idx_np_category (category_id),
  KEY idx_np_author (author_id),
  KEY idx_np_published (published_at),
  FULLTEXT KEY ft_np_search (title, excerpt),
  CONSTRAINT fk_np_category FOREIGN KEY (category_id) REFERENCES news_categories (id) ON DELETE SET NULL,
  CONSTRAINT fk_np_author FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_post_tags (
  post_id BIGINT UNSIGNED NOT NULL,
  tag_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, tag_id),
  CONSTRAINT fk_npt_post FOREIGN KEY (post_id) REFERENCES news_posts (id) ON DELETE CASCADE,
  CONSTRAINT fk_npt_tag FOREIGN KEY (tag_id) REFERENCES news_tags (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_comments (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id    BIGINT UNSIGNED NOT NULL,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(120) NULL,
  body       VARCHAR(1000) NOT NULL,
  status     ENUM('PENDING','APPROVED','HIDDEN') NOT NULL DEFAULT 'PENDING',
  ip         VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_nc_post (post_id),
  KEY idx_nc_status (status),
  CONSTRAINT fk_nc_post FOREIGN KEY (post_id) REFERENCES news_posts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_likes (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id    BIGINT UNSIGNED NOT NULL,
  user_hash  CHAR(32) NOT NULL COMMENT 'md5(ip+ua), bukan data pribadi',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_nl_post_hash (post_id, user_hash),
  CONSTRAINT fk_nl_post FOREIGN KEY (post_id) REFERENCES news_posts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_views (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id    BIGINT UNSIGNED NOT NULL,
  user_hash  CHAR(32) NULL,
  viewed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_nv_post (post_id),
  CONSTRAINT fk_nv_post FOREIGN KEY (post_id) REFERENCES news_posts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_shares (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id    BIGINT UNSIGNED NOT NULL,
  platform   VARCHAR(30) NULL,
  shared_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ns_post (post_id),
  CONSTRAINT fk_ns_post FOREIGN KEY (post_id) REFERENCES news_posts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
