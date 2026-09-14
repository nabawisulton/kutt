<?php

declare(strict_types=1);

/**
 * Fresh-database migration test.
 * Migrates a temporary database (kutt_fresh2) end-to-end using a dedicated
 * test DB user, verifies the resulting schema, then drops the database.
 *
 * Usage: php tests/fresh_db_check.php
 * Requires MariaDB/MySQL running locally and root socket access via `mysql` CLI
 * (used only to create/drop the throwaway test user + database).
 */

// Force test credentials BEFORE bootstrap reads env.
// NOTE: helpers config('db.password') reads env DB_PASSWORD (full word).
putenv('DB_NAME=kutt_fresh2');
putenv('DB_USER=kutt_test');
putenv('DB_PASSWORD=kutt_test');
putenv('DB_PASS=kutt_test');
$_ENV['DB_NAME'] = 'kutt_fresh2';
$_ENV['DB_USER'] = 'kutt_test';
$_ENV['DB_PASSWORD'] = 'kutt_test';
$_SERVER['DB_NAME'] = 'kutt_fresh2';
$_SERVER['DB_USER'] = 'kutt_test';
$_SERVER['DB_PASSWORD'] = 'kutt_test';

// Create/drop helper through the mysql CLI (root socket auth).
function sql(string $query): void
{
    exec('mysql -e ' . escapeshellarg($query) . ' 2>&1', $out, $code);
    if ($code !== 0) {
        fwrite(STDERR, "mysql failed: " . implode("\n", $out) . "\n");
        exit(1);
    }
}

sql("DROP DATABASE IF EXISTS kutt_fresh2");
sql("CREATE DATABASE kutt_fresh2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
sql("CREATE USER IF NOT EXISTS 'kutt_test'@'localhost' IDENTIFIED BY 'kutt_test'");
sql("CREATE USER IF NOT EXISTS 'kutt_test'@'127.0.0.1' IDENTIFIED BY 'kutt_test'");
sql("GRANT ALL ON kutt_fresh2.* TO 'kutt_test'@'localhost'");
sql("GRANT ALL ON kutt_fresh2.* TO 'kutt_test'@'127.0.0.1'");
sql("FLUSH PRIVILEGES");

require dirname(__DIR__) . '/src/bootstrap.php';

echo "[run] php database/migrate.php (DB=kutt_fresh2)\n";
// Child process inherits putenv vars (DB_NAME/DB_USER/DB_PASSWORD above),
// which Env::get() prefers over .env files after load.
passthru('php database/migrate.php 2>&1', $code);
if ($code !== 0) {
    fwrite(STDERR, "MIGRATION FAILED\n");
    exit(1);
}

// Verify content.
$counts = [
    'users'            => (int) App\Core\Database::scalar('SELECT COUNT(*) FROM users'),
    'settings'         => (int) App\Core\Database::scalar('SELECT COUNT(*) FROM settings'),
    'news_categories'  => (int) App\Core\Database::scalar('SELECT COUNT(*) FROM news_categories'),
    'chart_of_accounts'=> (int) App\Core\Database::scalar('SELECT COUNT(*) FROM chart_of_accounts'),
];
foreach ($counts as $tbl => $cnt) {
    echo "  {$tbl}={$cnt}";
}
echo "\n";

$pass = $counts['users'] >= 4 && $counts['chart_of_accounts'] >= 12 && $counts['settings'] >= 10;
sql("DROP DATABASE IF EXISTS kutt_fresh2");

echo $pass ? "FRESH-DB MIGRATION: PASS\n" : "FRESH-DB MIGRATION: FAIL (unexpected counts)\n";
exit($pass ? 0 : 1);
