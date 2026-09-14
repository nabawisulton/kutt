<?php

declare(strict_types=1);

/**
 * KUTT SUKA MAKMUR - Migration & Seed Runner
 *
 * Usage:
 *   php database/migrate.php            # apply all pending migrations + seeds
 *
 * Safe by design:
 * - Applied files are tracked in `migrations` (never re-executed).
 * - Base schema files only use CREATE TABLE IF NOT EXISTS / INSERT IGNORE.
 * - Hashtables: full legacy module parity (A-O) beyond the Tahap 1 core.
 * - Forward-compatible column adds use ensureColumn() (ADD COLUMN IF NOT EXISTS
 *   where available, information_schema probe otherwise). Never drops data.
 *
 * After structural changes, the database.sql snapshot for phpMyAdmin/cPanel
 * can be regenerated with:
 *   php database/migrate.php --export-sql
 */

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Core\Database;

// --- 1. Ensure the database itself exists -----------------------------------
$host = (string) config('db.host');
$port = (string) config('db.port');
$name = (string) config('db.name');
$user = (string) config('db.user');
$pass = (string) config('db.password');
$charset = (string) config('db.charset');

$serverPdo = new PDO(
    sprintf('mysql:host=%s;port=%s;charset=%s', $host, $port, $charset),
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$serverPdo->exec(sprintf(
    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    $name
));
$serverPdo->exec(sprintf('USE `%s`', $name));
echo "[ok] Database '{$name}' tersedia.\n";

// --- 2. Migration bookkeeping table ------------------------------------------
$serverPdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        migration VARCHAR(190) NOT NULL,
        batch INT UNSIGNED NOT NULL,
        executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_migrations_name (migration)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

// Connect the app layer to the (now existing) database.
$pdo = Database::pdo();

$executedNames = array_column(Database::all('SELECT migration FROM migrations'), 'migration');

// --- 3. Idempotent column guard (forward-compatible migrations) --------------
/**
 * Add a column only when it does not exist yet. Never modifies or drops data.
 */
function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
{
    $exists = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $pdo->quote($table) .
        " AND COLUMN_NAME = " . $pdo->quote($column)
    )->fetchColumn();

    if ((int) $exists === 0) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        echo "  [col] {$table}.{$column} ditambahkan\n";
    }
}

// --- 4. Hashtables (full legacy module parity) -------------------------------
$hashtables = [
    'users' => 'user_id VARCHAR(20) NULL, username VARCHAR(40) NULL, email VARCHAR(120) NULL,
                password_hash VARCHAR(255) NULL, role VARCHAR(30) NULL, full_name VARCHAR(120) NULL,
                is_active TINYINT(1) NULL, created_at DATETIME NULL, updated_at DATETIME NULL',
    'members' => 'member_no VARCHAR(20) NULL, nia VARCHAR(30) NULL, nik CHAR(16) NULL,
                  nama_lengkap VARCHAR(120) NULL, alamat_lengkap VARCHAR(255) NULL,
                  no_hp VARCHAR(20) NULL, email VARCHAR(120) NULL, pekerjaan VARCHAR(80) NULL,
                  kelompok_tani VARCHAR(120) NULL, status_anggota VARCHAR(20) NULL,
                  foto_path VARCHAR(255) NULL, created_at DATETIME NULL',
    'savings_transactions' => 'trans_no VARCHAR(30) NULL, member_no VARCHAR(20) NULL,
                  jenis_simpanan VARCHAR(20) NULL, tipe_transaksi VARCHAR(10) NULL,
                  nominal DECIMAL(18,2) NULL, tanggal_trans DATE NULL,
                  operator_user VARCHAR(40) NULL, keterangan VARCHAR(255) NULL,
                  created_at DATETIME NULL',
    'loans' => 'loan_no VARCHAR(30) NULL, member_no VARCHAR(20) NULL,
                  pokok_pinjaman DECIMAL(18,2) NULL, bunga_pertahun DECIMAL(9,2) NULL,
                  tenor_bulan INT NULL, sistem_bunga VARCHAR(20) NULL,
                  status_appr VARCHAR(20) NULL, tanggal_pengajuan DATE NULL,
                  tanggal_cair DATE NULL, keperluan VARCHAR(255) NULL, agunan VARCHAR(255) NULL,
                  created_at DATETIME NULL',
    'loan_schedules' => 'loan_no VARCHAR(30) NULL, angsuran_ke INT NULL, jatuh_tempo DATE NULL,
                  pokok_due DECIMAL(18,2) NULL, bunga_due DECIMAL(18,2) NULL,
                  total_due DECIMAL(18,2) NULL, status VARCHAR(20) NULL',
    'loan_payments' => 'payment_no VARCHAR(30) NULL, loan_no VARCHAR(30) NULL,
                  angsuran_ke INT NULL, bayar_pokok DECIMAL(18,2) NULL,
                  bayar_bunga DECIMAL(18,2) NULL, denda DECIMAL(18,2) NULL,
                  total_bayar DECIMAL(18,2) NULL, tanggal_bayar DATE NULL,
                  operator_user VARCHAR(40) NULL, keterangan VARCHAR(255) NULL,
                  created_at DATETIME NULL',
    'cash_transactions' => 'voucher_no VARCHAR(30) NULL, jenis_kas VARCHAR(10) NULL,
                  account_code VARCHAR(10) NULL, nominal DECIMAL(18,2) NULL,
                  keterangan VARCHAR(255) NULL, tanggal_trans DATE NULL,
                  created_by VARCHAR(40) NULL, created_at DATETIME NULL',
    'journal_entries' => 'journal_no VARCHAR(30) NULL, ref_voucher VARCHAR(30) NULL,
                  tanggal DATE NULL, account_code VARCHAR(10) NULL,
                  debet DECIMAL(18,2) NULL, kredit DECIMAL(18,2) NULL,
                  keterangan VARCHAR(255) NULL, created_by VARCHAR(40) NULL,
                  created_at DATETIME NULL',
    'shu_distributions' => 'shu_no VARCHAR(30) NULL, tahun_buku INT NULL,
                  member_no VARCHAR(20) NULL, jasa_modal DECIMAL(18,2) NULL,
                  jasa_anggota DECIMAL(18,2) NULL, total_shu DECIMAL(18,2) NULL,
                  status_pencairan VARCHAR(20) NULL, created_at DATETIME NULL',
    'news_posts' => 'post_id VARCHAR(30) NULL, judul VARCHAR(200) NULL,
                  ringkasan VARCHAR(500) NULL, isi LONGTEXT NULL, gambar VARCHAR(255) NULL,
                  thumbnail VARCHAR(255) NULL, video_url VARCHAR(255) NULL,
                  kategori VARCHAR(60) NULL, tags VARCHAR(255) NULL, penulis VARCHAR(120) NULL,
                  status VARCHAR(20) NULL, views INT NULL, likes INT NULL, shares INT NULL,
                  comments_count INT NULL, published_at DATETIME NULL,
                  created_by VARCHAR(40) NULL, created_at DATETIME NULL, updated_at DATETIME NULL',
    'member_cards' => 'card_no VARCHAR(30) NULL, member_no VARCHAR(20) NULL,
                  verify_code VARCHAR(64) NULL, background_type VARCHAR(10) NULL,
                  background_value VARCHAR(255) NULL, background_opacity DECIMAL(4,2) NULL,
                  issued_at DATETIME NULL, is_active TINYINT(1) NULL',
    'notifications' => 'notif_no VARCHAR(30) NULL, recipient_role VARCHAR(30) NULL,
                  recipient_user VARCHAR(40) NULL, title VARCHAR(160) NULL,
                  message VARCHAR(500) NULL, type VARCHAR(20) NULL, link VARCHAR(255) NULL,
                  is_read TINYINT(1) NULL, created_at DATETIME NULL',
    'audit_logs' => 'log_no VARCHAR(30) NULL, user_id VARCHAR(40) NULL, role VARCHAR(30) NULL,
                  action VARCHAR(40) NULL, module VARCHAR(40) NULL, data_id VARCHAR(40) NULL,
                  details VARCHAR(500) NULL, ip VARCHAR(45) NULL, timestamp DATETIME NULL',
    'settings' => 'setting_key VARCHAR(60) NULL, setting_value TEXT NULL,
                  updated_at DATETIME NULL, updated_by VARCHAR(40) NULL',
];

foreach ($hashtables as $table => $cols) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `{$table}_ht` (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        {$cols}, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
echo "[ok] " . count($hashtables) . " hashtable modul disiapkan (arsip paritas legacy).\n";

// --- 5. Run pending schema migrations (seeds run AFTER harmonization, see 5c) --
$files = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($files);

$batch = (int) (Database::scalar('SELECT COALESCE(MAX(batch), 0) FROM migrations') ?: 0) + 1;
$ran = 0;

foreach ($files as $file) {
    $fileName = basename($file);
    if (in_array($fileName, $executedNames, true)) {
        continue;
    }

    $sql = (string) file_get_contents($file);
    try {
        $pdo->exec($sql);
        Database::insert('migrations', ['migration' => $fileName, 'batch' => $batch]);
        echo "[ok] {$fileName}\n";
        $ran++;
    } catch (PDOException $e) {
        echo "[FAIL] {$fileName}: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// --- 5b. Schema harmonization (additive, never destructive) ----------------
// History: migration 4 created first-generation tables; migration 5 defined a
// richer news schema. Wherever migration 4 ran first (fresh installs run both),
// CREATE TABLE IF NOT EXISTS was a no-op, leaving drift between model SQL and
// columns. Runs AFTER the .sql files so tables always exist; ensureColumn() is
// a no-op when the column already exists. Nothing is dropped or rewritten.
echo "[..] harmonisasi skema (tambah kolom yang hilang, jika ada)\n";

// news_posts: richer schema used by News model + OG metadata.
ensureColumn($pdo, 'news_posts', 'excerpt',       "VARCHAR(500) NULL AFTER `title`");
ensureColumn($pdo, 'news_comments', 'ip',         "VARCHAR(45) NULL AFTER `body`");
ensureColumn($pdo, 'news_comments', 'status',     "ENUM('PENDING','APPROVED','HIDDEN') NOT NULL DEFAULT 'PENDING' AFTER `ip`");
ensureColumn($pdo, 'news_posts', 'category_id',   "INT UNSIGNED NULL AFTER `category`");
ensureColumn($pdo, 'news_posts', 'author_id',     "INT UNSIGNED NULL AFTER `category_id`");
ensureColumn($pdo, 'news_posts', 'author_name',   "VARCHAR(120) NULL AFTER `author_id`");
ensureColumn($pdo, 'news_posts', 'comments_count',"INT UNSIGNED NOT NULL DEFAULT 0 AFTER `shares`");
ensureColumn($pdo, 'news_posts', 'updated_at',    "DATETIME NULL");

// notifications: per-role broadcast targeting + deep links (navbar bell).
ensureColumn($pdo, 'notifications', 'role', 'VARCHAR(30) NULL AFTER `user_id`');
ensureColumn($pdo, 'notifications', 'link', 'VARCHAR(255) NULL AFTER `type`');

// audit_logs: full audit trail fields (module + data_id + role).
ensureColumn($pdo, 'audit_logs', 'role',    'VARCHAR(30) NULL AFTER `user_id`');
ensureColumn($pdo, 'audit_logs', 'module',  'VARCHAR(40) NOT NULL DEFAULT "SYSTEM" AFTER `action`');
ensureColumn($pdo, 'audit_logs', 'data_id', 'VARCHAR(40) NULL AFTER `module`');

// members: jabatan di koperasi (internal) + hubungan eksternal.
ensureColumn($pdo, 'members', 'jabatan_internal',  "VARCHAR(60) NULL AFTER `occupation`");
ensureColumn($pdo, 'members', 'relasi_eksternal',  "ENUM('KARYAWAN','KONSUMEN','PEMASOK','MITRA','PIHAK_LAIN','TIDAK_ADA') NOT NULL DEFAULT 'TIDAK_ADA' AFTER `jabatan_internal`");
ensureColumn($pdo, 'members', 'jabatan_eksternal', "VARCHAR(60) NULL AFTER `relasi_eksternal`");
ensureColumn($pdo, 'members', 'user_id', "INT UNSIGNED NULL AFTER `created_by`");

// Portal anggota: pesan dua arah anggota <-> admin.
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS support_messages (
        id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        member_id    INT UNSIGNED NOT NULL,
        sender_role  ENUM(\'ANGGOTA\',\'ADMIN\') NOT NULL,
        sender_user  INT UNSIGNED NULL,
        sender_name  VARCHAR(120) NOT NULL,
        body         VARCHAR(2000) NOT NULL,
        is_read_by_admin   TINYINT(1) NOT NULL DEFAULT 0,
        is_read_by_member  TINYINT(1) NOT NULL DEFAULT 0,
        created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_sm_member (member_id),
        KEY idx_sm_admin_unread (is_read_by_admin),
        CONSTRAINT fk_sm_member FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
        CONSTRAINT fk_sm_user FOREIGN KEY (sender_user) REFERENCES users (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

echo "  [tbl] support_messages siap\n";

// member_cards: Card::issue/rotateToken store 32 hex chars. ensureColumn only
// adds missing columns, so an existing narrow column needs an explicit widen.
// Widening CHAR(12) -> CHAR(32) never truncates (short values fit fine).
$verifyLen = (int) $pdo->query(
    "SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'member_cards' AND COLUMN_NAME = 'verify_code'"
)->fetchColumn();
if ($verifyLen > 0 && $verifyLen < 32) {
    $pdo->exec('ALTER TABLE member_cards MODIFY verify_code CHAR(32) NOT NULL');
    echo "  [col] member_cards.verify_code dilebarkan CHAR({$verifyLen}) -> CHAR(32)\n";
}

// Backfill: link pre-existing migration-4 news rows to their category by name.
$pdo->exec(
    'UPDATE news_posts np
     LEFT JOIN news_categories nc ON nc.name = np.category
     SET np.category_id = nc.id
     WHERE np.category_id IS NULL AND np.category IS NOT NULL'
);

// --- 5c. Seeds (run LAST: beberapa seed mengisi kolom hasil harmonisasi 5b) ----
$seedFiles = glob(__DIR__ . '/seeds/*.sql') ?: [];
sort($seedFiles);

$seedBatch = (int) (Database::scalar('SELECT COALESCE(MAX(batch), 0) FROM migrations') ?: 0) + 1;
$seedRan = 0;

foreach ($seedFiles as $file) {
    $fileName = basename($file);
    if (in_array($fileName, $executedNames, true)) {
        continue;
    }

    $sql = (string) file_get_contents($file);
    try {
        $pdo->exec($sql);
        Database::insert('migrations', ['migration' => $fileName, 'batch' => $seedBatch]);
        echo "[ok] seed {$fileName}\n";
        $seedRan++;
    } catch (PDOException $e) {
        echo "[FAIL] seed {$fileName}: " . $e->getMessage() . "\n";
        exit(1);
    }
}

if ($ran === 0 && $seedRan === 0) {
    echo "[ok] Semua migrasi & seed sudah diterapkan sebelumnya.\n";
} else {
    echo "[ok] " . ($ran + $seedRan) . " file diterapkan (batch {$batch}).\n";
}

// --- 6. Snapshot skema untuk phpMyAdmin/cPanel ------------------------------
// Dipindah ke database/export_schema.php (reproducible, NON-DESTRUKTIF: tanpa
// DROP TABLE, struktur + seed sekaligus). Jalankan: php database/export_schema.php
if (in_array('--export-sql', $argv ?? [], true)) {
    echo "[info] Flag --export-sql sudah tidak dipakai (format lama memakai DROP TABLE - berisiko).\n";
    echo "[info] Gunakan: php database/export_schema.php\n";
}

// --- 7. Integrity summary ------------------------------------------------------
$users = (int) Database::scalar('SELECT COUNT(*) FROM users');
$coa = (int) Database::scalar('SELECT COUNT(*) FROM chart_of_accounts');
$debit = (float) Database::scalar('SELECT COALESCE(SUM(debet),0) FROM journal_entries');
$kredit = (float) Database::scalar('SELECT COALESCE(SUM(kredit),0) FROM journal_entries');
echo "[info] users={$users}, coa={$coa}, jurnal debit={$debit}, kredit={$kredit}\n";
echo "[done] Migrasi selesai tanpa error.\n";
