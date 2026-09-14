<?php

declare(strict_types=1);

/**
 * Router script for the PHP built-in development server.
 * Usage: php -S 0.0.0.0:8080 -t public public/router.php
 *
 * Existing static files are served directly; every other request is
 * handled by the front controller.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false; // serve the static file as-is
}

require __DIR__ . '/index.php';
