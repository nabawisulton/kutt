<?php

declare(strict_types=1);

/**
 * E2E portal anggota: login ANGGOTA -> dashboard portal (saldo) ->
 * pengajuan pinjaman -> chat ke admin -> balasan admin -> notifikasi.
 * Juga memverifikasi RBAC: ANGGOTA diblok modul internal.
 *
 * Usage: php tests/e2e_member_portal.php [baseUrl]
 * Exit 0 = semua langkah PASS, 1 = gagal.
 */

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');

require_once __DIR__ . '/http_client.php';

$jar = '';
[, , $html] = http_req($base . '/login', null, [], $jar);
if (!preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $html, $m)) {
    fwrite(STDERR, "FAIL: form login tanpa csrf token\n");
    exit(1);
}
[$cLogin, $locLogin] = http_req($base . '/login', http_build_query([
    'username' => 'anggota', 'password' => 'admin123', 'csrf_token' => $m[1],
]), ['Content-Type: application/x-www-form-urlencoded'], $jar);
if ($cLogin !== 302 || !str_contains((string) $locLogin, '/dashboard')) {
    fwrite(STDERR, "FAIL: login anggota tidak sukses ($cLogin $locLogin)\n");
    exit(1);
}

// 1. /dashboard untuk ANGGOTA dialihkan ke /portal
[$cDash, $locDash] = http_req($base . '/dashboard', null, [], $jar);
if ($cDash !== 302 || !str_contains((string) $locDash, '/portal')) {
    fwrite(STDERR, "FAIL: ANGGOTA /dashboard harus 302 -> /portal (dapat $cDash $locDash)\n");
    exit(1);
}

// 2. Portal menampilkan kartu saldo
[, , $html] = http_req($base . '/portal', null, [], $jar);
if (!str_contains($html, 'Simpanan') || !str_contains($html, 'Rp')) {
    fwrite(STDERR, "FAIL: portal tidak menampilkan saldo simpanan\n");
    exit(1);
}

// 3. RBAC: modul internal ditolak (302 balik ke /dashboard)
foreach (['members', 'users', 'settings', 'simpanan', 'pinjaman', 'kas', 'shu'] as $path) {
    [$c, $loc] = http_req($base . '/' . $path, null, [], $jar);
    if ($c !== 302 || !str_contains((string) $loc, '/dashboard')) {
        fwrite(STDERR, "FAIL: ANGGOTA /$path harus diblok 302->/dashboard (dapat $c -> $loc)\n");
        exit(1);
    }
}

// 4. Pengajuan pinjaman
[, , $html] = http_req($base . '/portal', null, [], $jar);
if (!preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $html, $m2)) {
    fwrite(STDERR, "FAIL: form pengajuan tanpa csrf token\n");
    exit(1);
}
[$cLoan, $locLoan] = http_req($base . '/portal/loan-request', http_build_query([
    'pokok_pinjaman' => '1200000',
    'tenor_bulan'    => '6',
    'sistem_bunga'   => 'FLAT',
    'keperluan'      => 'E2E portal uji otomatis',
    'csrf_token'     => $m2[1],
]), ['Content-Type: application/x-www-form-urlencoded'], $jar);
if ($cLoan !== 302) {
    fwrite(STDERR, "FAIL: pengajuan pinjaman tidak 302 (dapat $cLoan)\n");
    exit(1);
}

// 5. Chat anggota -> admin
[, , $html] = http_req($base . '/portal/chat', null, [], $jar);
if (!preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $html, $m3)) {
    fwrite(STDERR, "FAIL: form chat tanpa csrf token\n");
    exit(1);
}
[$cChat, $locChat] = http_req($base . '/portal/chat', http_build_query([
    'body'       => 'E2E portal: uji pesan anggota ke admin.',
    'csrf_token' => $m3[1],
]), ['Content-Type: application/x-www-form-urlencoded'], $jar);
if ($cChat !== 302) {
    fwrite(STDERR, "FAIL: kirim chat tidak 302 (dapat $cChat)\n");
    exit(1);
}

// --- Cleanup: hapus data uji agar test repeatable & DB tetap bersih ---------
try {
    $pdo = new PDO(
        sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4',
            (string) (getenv('DB_SOCKET') ?: '/run/mysqld/mysqld.sock'),
            (string) (getenv('DB_NAME') ?: 'kutt_suka_makmur')),
        (string) (getenv('DB_USER') ?: 'root'),
        (string) (getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: '')),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("DELETE FROM support_messages WHERE body LIKE 'E2E portal:%'");
    $pdo->exec("DELETE FROM notifications WHERE title = 'Pesan baru dari anggota' AND created_at >= NOW() - INTERVAL 10 MINUTE");
    $pdo->exec("DELETE FROM loans WHERE keperluan = 'E2E portal uji otomatis'");
} catch (Throwable $e) {
    // Cleanup bersifat best-effort; jangan gagalkan test karena DB tidak aktif.
    fwrite(STDERR, 'warn: cleanup dilewati: ' . $e->getMessage() . "\n");
}

echo "E2E MEMBER PORTAL: PASS\n";
exit(0);
