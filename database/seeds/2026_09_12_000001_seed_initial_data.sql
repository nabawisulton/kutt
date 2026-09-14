-- =====================================================================
-- KUTT SUKA MAKMUR - Seeds (Tahap 1)
-- Idempotent: hanya mengisi data dasar jika belum ada.
-- Akun seed: admin / bendahara / ketua / staff - password: admin123
-- (GANTI password default setelah login pertama!)
-- =====================================================================
-- PENTING: seed yang SUDAH tercatat di tabel `migrations` tidak dijalankan
-- ulang pada instalasi lama. Untuk menambah data seed baru, buat FILE SEED
-- BARU (jangan sunting file lama), lalu `php database/migrate.php`.
-- =====================================================================

-- A. USERS ------------------------------------------------------------
-- Password semua akun seed: admin123 (GANTI setelah login pertama!)
-- (Akun demo ANGGOTA ada di seed terpisah: 2026_09_14_000001_seed_portal_demo.sql)
INSERT IGNORE INTO users (user_id, username, email, password_hash, role, full_name, is_active)
VALUES
  ('USR-001', 'admin',     'admin@kuttsukamakmur.coop',     '$2y$10$HbIWNvVX1zAhUEproGWvZeei5KFK3EQ0l8vbLCV.PuDcnqDWstVlC', 'SUPER_ADMIN', 'Administrator Utama', 1),
  ('USR-002', 'bendahara', 'bendahara@kuttsukamakmur.coop', '$2y$10$HbIWNvVX1zAhUEproGWvZeei5KFK3EQ0l8vbLCV.PuDcnqDWstVlC', 'BENDAHARA',   'Ahmad Hidayat',       1),
  ('USR-003', 'ketua',     'ketua@kuttsukamakmur.coop',     '$2y$10$HbIWNvVX1zAhUEproGWvZeei5KFK3EQ0l8vbLCV.PuDcnqDWstVlC', 'KETUA',       'H. Sutrisno',         1),
  ('USR-004', 'staff',     'staff@kuttsukamakmur.coop',     '$2y$10$HbIWNvVX1zAhUEproGWvZeei5KFK3EQ0l8vbLCV.PuDcnqDWstVlC', 'STAFF',       'Siti Rahma',          1);

-- I. CHART OF ACCOUNTS (sesuai COA legacy KUTT) ----------------------
INSERT IGNORE INTO chart_of_accounts (account_code, account_name, account_category, normal_balance)
VALUES
  ('1101', 'Kas Utama',                          'ASSET',    'DEBIT'),
  ('1102', 'Kas Bank Jatim',                     'ASSET',    'DEBIT'),
  ('1103', 'Piutang Pinjaman Anggota',           'ASSET',    'DEBIT'),
  ('2101', 'Simpanan Pokok Anggota',             'LIABILITY','CREDIT'),
  ('2102', 'Simpanan Wajib Anggota',             'LIABILITY','CREDIT'),
  ('2103', 'Simpanan Sukarela Anggota',          'LIABILITY','CREDIT'),
  ('3101', 'Modal Cadangan Koperasi',            'EQUITY',   'CREDIT'),
  ('3102', 'SHU Belum Dibagi',                   'EQUITY',   'CREDIT'),
  ('4101', 'Pendapatan Jasa Bunga Pinjaman',     'REVENUE',  'CREDIT'),
  ('4102', 'Pendapatan Operasional Unit Susu',   'REVENUE',  'CREDIT'),
  ('5101', 'Beban Operasional & Administrasi',   'EXPENSE',  'DEBIT'),
  ('5102', 'Beban Bunga & Simpanan',             'EXPENSE',  'DEBIT');

-- K. SETTINGS / CMS DEFAULTS -----------------------------------------
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('brandName',       'KUTT SUKA MAKMUR'),
  ('subBrand',        'Grati - Pasuruan'),
  ('logoIcon',        'fa-solid fa-cow'),
  ('colorPrimary',    '#0b7a3e'),
  ('colorDark',       '#052e1a'),
  ('colorGold',       '#ffc107'),
  ('footerAddress',   'Jl. Raya Grati No. 128, Kecamatan Grati, Kabupaten Pasuruan, Jawa Timur 67184.'),
  ('footerPhone',     '(0343) 481123 / WA: 0812-3456-7890'),
  ('footerCopyright', '© 2026 KUTT Suka Makmur Grati. All Rights Reserved.');

-- M. NOTIFIKASI SELAMAT DATANG (broadcast) ---------------------------
INSERT INTO notifications (user_id, title, message, type)
SELECT NULL, 'Selamat datang di KUTT SUKA MAKMUR',
       'Fondasi sistem koperasi digital telah aktif. Semua modul siap digunakan bertahap.',
       'SUCCESS'
WHERE NOT EXISTS (SELECT 1 FROM notifications WHERE title = 'Selamat datang di KUTT SUKA MAKMUR');
