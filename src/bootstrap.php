<?php

declare(strict_types=1);

/**
 * KUTT SUKA MAKMUR - Application Bootstrap
 * Koperasi Usaha Tani Ternak Suka Makmur Grati
 *
 * Single entry bootstrap: registers the PSR-4 style autoloader, loads
 * environment configuration and boots a hardened PHP session.
 * Called only from public/index.php (the front controller).
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_VERSION', '1.0.0');

// --- Autoloader (App\ -> src/) ---------------------------------------------
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/src/Support/Env.php';
require BASE_PATH . '/src/Support/helpers.php';

App\Support\Env::load(BASE_PATH . '/.env');

date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));
mb_internal_encoding('UTF-8');

// --- Global error handling (themed 404/500, log-only details) ---------------
App\Core\ErrorHandler::register();

// --- Secure session ---------------------------------------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('KUTTSESSID');
    // HTTPS hanya saat request benar-benar HTTPS (langsung atau via proxy
    // X-Forwarded-Proto). Tidak boleh tergantung APP_ENV: banyak shared
    // hosting cPanel melayani HTTP di origin (SSL di proxy).
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => (int) config('app.session_lifetime', 7200),
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        // HTTPS: SameSite=None agar cookie tetap terkirim saat aplikasi
        // di-embed di iframe (preview host / integrasi lain). Browser hanya
        // menerima None bila Secure aktif — keduanya berpasangan di sini.
        // HTTP: Lax (default aman untuk browsing top-level biasa).
        'samesite' => $isHttps ? 'None' : 'Lax',
        'httponly' => true,
    ]);
    session_start();
}

// --- Storage directories ----------------------------------------------------
foreach ([BASE_PATH . '/storage/logs', BASE_PATH . '/storage/uploads'] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}
