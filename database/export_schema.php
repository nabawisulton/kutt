<?php

declare(strict_types=1);

/**
 * KUTT SUKA MAKMUR - Export database.sql (cPanel / phpMyAdmin import file).
 *
 * Membuat `database/database.sql` secara REPRODUCIBLE:
 *   1. Membuat database scratch sementara (+ user khusus export).
 *   2. Menjalankan seluruh migrasi + seed di database scratch tersebut
 *      (persis alur `php database/migrate.php`).
 *   3. Dump struktur semua tabel (tanpa data) via mysqldump.
 *   4. Menggabungkan file seed (database/seeds/*.sql, urut nama).
 *   5. Menulis hasilnya ke database/database.sql lalu membersihkan scratch.
 *
 * Usage:
 *   php database/export_schema.php
 *
 * Output aman untuk di-import lewat phpMyAdmin pada instalasi cPanel baru.
 * JANGAN sunting file database.sql secara manual — sunting migrasi/seed,
 * lalu jalankan ulang script ini.
 */

require __DIR__ . '/../src/Support/Env.php';

use App\Support\Env;

Env::load(dirname(__DIR__));

$root = dirname(__DIR__);
$outFile = __DIR__ . '/database.sql';

// --- 0. Prasyarat -----------------------------------------------------------
if (!shell_exec('command -v mysqldump 2>/dev/null')) {
    fwrite(STDERR, "[fail] mysqldump tidak ditemukan di PATH.\n");
    exit(1);
}

$scratchDb   = 'kutt_export_scratch';
$scratchUser = 'kutt_export';
// Hanya [a-z0-9_] agar aman dipakai langsung di SQL & shell (tidak perlu quoting).
$scratchPass = 'kutt_export_' . bin2hex(random_bytes(4));
if (!preg_match('/^[A-Za-z0-9_]+$/', $scratchPass) || !preg_match('/^[A-Za-z0-9_]+$/', $scratchDb) || !preg_match('/^[A-Za-z0-9_]+$/', $scratchUser)) {
    fwrite(STDERR, "[fail] Nama/password scratch mengandung karakter tak aman.\n");
    exit(1);
}

// Kredensial admin untuk membuat scratch DB/user: pakai kredensial env saat ini
// (sandbox: root socket; cPanel dev: user DB biasanya punya hak CREATE).
$adminUser = getenv('DB_USER') ?: (getenv('DB_USERNAME') ?: 'root');
$adminPass = getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: '');

function run(string $cmd): void
{
    exec($cmd . ' 2>&1', $out, $code);
    if ($code !== 0) {
        fwrite(STDERR, "[fail] {$cmd}\n" . implode("\n", $out) . "\n");
        exit(1);
    }
}

// --- 1. Scratch database + user ---------------------------------------------
// Identifikasi scratch bersifat konstanta & tervalidasi [A-Za-z0-9_] -> aman tanpa quoting.
$adminPassFlag = $adminPass === '' ? '' : ' -p' . escapeshellarg($adminPass);

/** Jalankan SQL multi-statement lewat stdin (hindari quoting shell). */
function mysqlStdin(string $user, string $passFlag, string $sql): void
{
    $sqlFile = tempnam(sys_get_temp_dir(), 'kutt_sql_');
    file_put_contents($sqlFile, $sql);
    run("mysql -u{$user}{$passFlag} < " . escapeshellarg($sqlFile));
    unlink($sqlFile);
}

mysqlStdin($adminUser, $adminPassFlag, "DROP DATABASE IF EXISTS `{$scratchDb}`;
CREATE DATABASE `{$scratchDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
DROP USER IF EXISTS `{$scratchUser}`@`localhost`;
CREATE USER `{$scratchUser}`@`localhost` IDENTIFIED BY '{$scratchPass}';
GRANT ALL PRIVILEGES ON `{$scratchDb}`.* TO `{$scratchUser}`@`localhost`;
FLUSH PRIVILEGES;");
echo "[1/4] Scratch database {$scratchDb} siap\n";

// --- 2. Migrasi + seed ke scratch -------------------------------------------
$env = sprintf(
    'DB_NAME=%s DB_USER=%s DB_PASSWORD=%s DB_PASS=%s',
    escapeshellarg($scratchDb),
    escapeshellarg($scratchUser),
    escapeshellarg($scratchPass),
    escapeshellarg($scratchPass)
);
run("env {$env} php " . escapeshellarg(__DIR__ . '/migrate.php'));
echo "[2/4] Migrasi + seed diterapkan di scratch\n";

// --- 3. Dump struktur --------------------------------------------------------
$defaults = tempnam(sys_get_temp_dir(), 'mycnf');
file_put_contents($defaults, "[client]\nuser={$scratchUser}\npassword=\"{$scratchPass}\"\n");
$dump = [];
exec(sprintf(
    'mysqldump --defaults-extra-file=%s --no-data --skip-comments --routines=false %s 2>&1',
    escapeshellarg($defaults),
    escapeshellarg($scratchDb)
), $dump, $code);
unlink($defaults);
if ($code !== 0) {
    fwrite(STDERR, "[fail] mysqldump:\n" . implode("\n", $dump) . "\n");
    exit(1);
}

$schema = implode("\n", $dump);
// Bersihkan noise dump: auto_increment counter & blok kondisional versi.
$schema = preg_replace('/ AUTO_INCREMENT=\d+/', '', $schema) ?? $schema;
$schema = preg_replace("/\/\*!40101 SET NAMES.*?\*\/;\n?/", '', $schema) ?? $schema;
echo "[3/4] Struktur (" . substr_count($schema, 'CREATE TABLE') . " tabel) di-dump\n";

// --- 4. Gabungkan seed + tulis file ------------------------------------------
$seedFiles = glob(__DIR__ . '/seeds/*.sql') ?: [];
sort($seedFiles);
$seedSql = '';
foreach ($seedFiles as $file) {
    $seedSql .= "\n-- ==== SEED: " . basename($file) . " ====\n"
        . rtrim((string) file_get_contents($file)) . "\n";
}

$header = <<<SQL
-- =====================================================================
-- KUTT SUKA MAKMUR - database.sql (FILE GENERATE OTOMATIS)
-- =====================================================================
-- Sumber kebenaran: database/migrations/*.sql + database/seeds/*.sql.
-- JANGAN sunting file ini manual. Regenerate dengan:
--     php database/export_schema.php
--
-- Cara pakai di cPanel: import file ini lewat phpMyAdmin pada database
-- yang sudah dibuat (struktur + data seed awal terpasang sekaligus).
-- PENTING: import HANYA ke database KOSONG baru - file ini memuat
-- `DROP TABLE IF EXISTS` dan akan MENGGANTI tabel yang sudah ada.
-- =====================================================================


SQL;

file_put_contents($outFile, $header . $schema . "\n-- =====================================================================\n-- SEED DATA (idempoten: INSERT IGNORE / guard WHERE NOT EXISTS)\n-- =====================================================================\n" . $seedSql);
echo "[4/4] Ditulis: database/database.sql (" . number_format((float) filesize($outFile)) . " bytes)\n";

// --- 5. Bersihkan scratch -----------------------------------------------------
mysqlStdin($adminUser, $adminPassFlag, "DROP DATABASE IF EXISTS `{$scratchDb}`;
DROP USER IF EXISTS `{$scratchUser}`@`localhost`;");
echo "[ok] Scratch dibersihkan. database.sql siap dipakai.\n";
