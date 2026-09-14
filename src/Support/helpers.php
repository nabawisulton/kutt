<?php

declare(strict_types=1);

use App\Support\Env;

/**
 * Get configuration value using dot notation, backed by environment variables.
 * e.g. config('db.host') reads env DB_HOST with fallback from config defaults.
 */
function config(string $key, mixed $default = null): mixed
{
    static $defaults = [
        'app.name'              => 'KUTT SUKA MAKMUR',
        'app.sub_brand'         => 'Grati - Pasuruan',
        'app.env'               => 'production',
        'app.timezone'          => 'Asia/Jakarta',
        'app.session_lifetime'  => 7200,
        'app.logo_icon'         => 'fa-solid fa-cow',
        'db.host'               => '127.0.0.1',
        'db.port'               => '3306',
        'db.name'               => 'kutt_suka_makmur',
        'db.user'               => 'kutt',
        'db.password'           => '',
        'db.charset'            => 'utf8mb4',
    ];

    $envKey = strtoupper(str_replace('.', '_', $key));

    $value = Env::get($envKey);
    if ($value !== null) {
        return $value;
    }

    return $defaults[$key] ?? $default;
}

/**
 * Normalisasi sumber gambar berita untuk atribut src.
 * Path upload lokal ("uploads/...") di-prefix "/"; URL eksternal diteruskan apa adanya.
 */
function news_image_src(mixed $path): string
{
    $p = trim((string) $path);
    if ($p === '') {
        return '';
    }
    if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://') || str_starts_with($p, '/')) {
        return $p;
    }
    return '/' . $p;
}

/**
 * URL absolut gambar berita untuk metadata Open Graph/Twitter Card.
 * URL eksternal diteruskan apa adanya; path lokal dijadikan absolut via base_url().
 */
function news_og_image(mixed $path): ?string
{
    $src = news_image_src($path);
    if ($src === '') {
        return null;
    }
    return (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) ? $src : base_url($src);
}

/** HTML-escape output (sanitasi output). */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Base URL of the app (respects reverse proxy X-Forwarded-* headers). */
function base_url(string $path = ''): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim($scheme . '://' . $host, '/');

    return $base . '/' . ltrim($path, '/');
}

/** Redirect and stop execution. */
function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

/** Format number as Indonesian Rupiah, e.g. Rp 1.250.000 */
function rupiah(mixed $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

/** Format date to Indonesian short format, e.g. 12 Sep 2026 */
function tanggal(?string $date, bool $withTime = false): string
{
    if ($date === null || $date === '') {
        return '-';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '-';
    }
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
    $out = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    if ($withTime) {
        $out .= ' ' . date('H:i', $ts);
    }

    return $out;
}

/** Two-letter initials for avatar, e.g. "Ahmad Hidayat" -> "AH" */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        if ($part !== '' && $letters === '') {
            $letters .= mb_substr($part, 0, 1);
        } elseif ($part !== '' && mb_strlen($letters) < 2) {
            $letters .= mb_substr($part, 0, 1);
        }
    }

    return mb_strtoupper($letters ?: 'U');
}

/** Map a system role to its display label (same mapping as legacy app). */
function role_label(string $role): string
{
    return match ($role) {
        'SUPER_ADMIN' => 'Super Admin',
        'ADMIN'       => 'Admin',
        'BENDAHARA'   => 'Bendahara Utama',
        'KETUA'       => 'Ketua Koperasi',
        'STAFF'       => 'Staff / Kasir',
        'ANGGOTA'     => 'Portal Anggota',
        default       => $role,
    };
}

/** Store a one-shot flash message in the session. */
function flash_set(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

/** Fetch and clear the current flash message. */
function flash_take(): ?array
{
    if (isset($_SESSION['_flash'])) {
        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);

        return $flash;
    }

    return null;
}

/** Old input helper for form re-population after validation errors. */
function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function flash_old_input(array $input): void
{
    unset($input['password'], $input['csrf_token']);
    $_SESSION['_old'] = $input;
}

function flash_clear_old(): void
{
    unset($_SESSION['_old']);
}
