<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Global error handling for the front controller.
 *
 * Production (default): user sees a themed 404/500 page; technical details
 * (SQL, paths, stack traces) go ONLY to storage/logs/app-YYYY-MM-DD.log.
 * Development (APP_ENV=local): uncaught exceptions render the same themed
 * page with a debug panel.
 */
final class ErrorHandler
{
    /** Register shutdown/exception handlers (call once from bootstrap). */
    public static function register(): void
    {
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        set_exception_handler(static function (Throwable $e): void {
            self::render(500, $e);
        });

        // Convert warnings/notices/deprecations into log entries so they
        // never pollute pages. Deprecations dari pustaka pihak ketiga
        // (vendor/) tidak dicatat — di luar kendali kita dan hanya noise.
        set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
            if (!(error_reporting() & $no)) {
                return false; // respect @-suppression
            }
            if ($no === E_DEPRECATED && str_contains($file, '/vendor/')) {
                return true; // vendor library noise — skip logging
            }
            self::logLine(sprintf('PHP %s: %s in %s:%d', self::severityName($no), $str, $file, $line));

            return true; // do not let PHP print it
        });

        register_shutdown_function(static function (): void {
            $err = error_get_last();
            if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::logLine(sprintf(
                    'PHP Fatal: %s in %s:%d',
                    $err['message'],
                    $err['file'],
                    $err['line']
                ));
            }
        });
    }

    /** Render the themed 404 page (public layout, safe content only). */
    public static function notFound(): void
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code(404);
        self::renderPage('404', 'Halaman Tidak Ditemukan', 'Alamat yang Anda tuju tidak tersedia atau telah dipindahkan.', null);
    }

    /**
     * Render the themed 500 page. In APP_ENV=local a debug panel with the
     * exception details is included; otherwise only a reference code.
     */
    public static function render(int $code, ?Throwable $e = null): void
    {
        // Discard any partial output from the failed request before rendering.
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        if ($e !== null) {
            self::log($e);
        }
        http_response_code($code);
        $ref = strtoupper(bin2hex(random_bytes(3)));
        $debug = null;
        if ($e !== null && config('app.env') === 'local') {
            $debug = sprintf('%s: %s @ %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine());
        }
        self::renderPage(
            '500',
            'Terjadi Kesalahan',
            'Sistem sedang mengalami gangguan. Tim kami telah mencatat kejadian ini — silakan coba lagi.',
            $ref,
            $debug
        );
    }

    private static function log(Throwable $e): void
    {
        self::logLine(sprintf(
            'Uncaught %s: %s in %s:%d | url=%s',
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $_SERVER['REQUEST_URI'] ?? '-'
        ));
    }

    private static function logLine(string $line): void
    {
        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(
            $dir . '/app-' . date('Y-m-d') . '.log',
            sprintf('[%s] %s%s', date('Y-m-d H:i:s'), $line, PHP_EOL),
            FILE_APPEND
        );
    }

    private static function severityName(int $type): string
    {
        return match ($type) {
            E_WARNING => 'Warning',
            E_NOTICE => 'Notice',
            E_DEPRECATED => 'Deprecated',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_USER_DEPRECATED => 'User Deprecated',
            default => 'Error',
        };
    }

    /**
     * Standalone themed error page (no dependencies on DB/Session, safe even
     * when the failure IS the database connection).
     */
    private static function renderPage(string $code, string $title, string $message, ?string $ref = null, ?string $debug = null): void
    {
        if (headers_sent()) {
            return;
        }
        $brand = 'KUTT SUKA MAKMUR';
        try {
            $map = \App\Models\Setting::all();
            $brand = ($map['brandName'] ?? '') !== '' ? $map['brandName'] : $brand;
        } catch (Throwable) {
            // settings unavailable — fall back to the default brand name
        }
        $subBrand = 'Grati - Pasuruan';
        $color = '#0b7a3e';
        $logo = '';
        try {
            $map = $map ?? [];
            $subBrand = (string) ($map['subBrand'] ?? $subBrand);
            $color = (string) ($map['colorPrimary'] ?? $color);
            $logo = (string) ($map['logoImage'] ?? '');
        } catch (Throwable) {
        }
        ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= (int) $code ?> · <?= htmlspecialchars($brand) ?></title>
<link rel="icon" href="/favicon.ico" sizes="any">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center px-4" style="background-image:radial-gradient(circle at 15% 20%, rgba(11,122,62,.08), transparent 45%), radial-gradient(circle at 85% 80%, rgba(255,193,7,.07), transparent 45%);">
<div class="max-w-md w-full text-center py-16">
  <div class="w-16 h-16 rounded-2xl mx-auto flex items-center justify-center shadow-lg shadow-emerald-900/20 mb-6"
       style="background:linear-gradient(135deg, <?= htmlspecialchars($color) ?>, #052e1a);">
    <?php if ($logo !== ''): ?>
      <img src="/uploads/<?= htmlspecialchars($logo) ?>" alt="" class="w-10 h-10 rounded-xl object-contain">
    <?php else: ?>
      <i class="fa-solid fa-cow text-white text-2xl"></i>
    <?php endif; ?>
  </div>
  <p class="text-6xl font-bold tracking-tight text-slate-800"><?= (int) $code ?></p>
  <h1 class="mt-2 text-xl font-semibold text-slate-700"><?= htmlspecialchars($title) ?></h1>
  <p class="mt-3 text-sm text-slate-500 leading-relaxed"><?= htmlspecialchars($message) ?></p>
  <?php if ($ref !== null): ?>
    <p class="mt-2 text-xs text-slate-400">Kode referensi: <span class="font-mono"><?= htmlspecialchars($ref) ?></span></p>
  <?php endif; ?>
  <?php if ($debug !== null): ?>
    <pre class="mt-4 text-left text-xs bg-slate-900 text-emerald-300 rounded-xl p-4 overflow-auto"><?= htmlspecialchars($debug) ?></pre>
  <?php endif; ?>
  <div class="mt-8 flex items-center justify-center gap-3">
    <a href="/" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg shadow-emerald-900/20" style="background:linear-gradient(135deg, <?= htmlspecialchars($color) ?>, #052e1a);">
      <i class="fa-solid fa-house"></i> Halaman Utama
    </a>
    <a href="/dashboard" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>
  </div>
  <p class="mt-10 text-xs text-slate-400"><?= htmlspecialchars($brand) ?> · <?= htmlspecialchars($subBrand) ?></p>
</div>
</body>
</html>
        <?php
    }
}
