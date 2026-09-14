<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF protection helpers. Every state-changing POST must carry a token
 * that matches the one stored in the session (hash_equals comparison).
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['_csrf_token'];
    }

    /** Hidden input field for forms. */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(self::token()) . '">';
    }

    /**
     * Validate the submitted token. Accepts token from POST body or
     * X-CSRF-Token header.
     *
     * On failure: AJAX/fetch callers get a JSON 419; normal form posts are
     * redirected back with a friendly flash message instead of a raw text
     * page (the session token is regenerated, so a simple retry works).
     */
    public static function validate(): void
    {
        $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $known = $_SESSION['_csrf_token'] ?? '';

        if (is_string($sent) && $sent !== '' && $known !== '' && hash_equals($known, $sent)) {
            return;
        }

        // Rotate so the next render of the form carries a fresh token.
        unset($_SESSION['_csrf_token']);

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($accept, 'application/json')
            || isset($_SERVER['HTTP_X_CSRF_TOKEN']);

        if ($isAjax) {
            http_response_code(419);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'Sesi telah berakhir. Muat ulang halaman lalu coba lagi.']);
            exit;
        }

        flash_set('error', 'Sesi Anda telah berakhir. Silakan coba sekali lagi.');
        $back = '/';
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref !== '') {
            $path = parse_url($ref, PHP_URL_PATH);
            if (is_string($path) && str_starts_with($path, '/')) {
                $back = $path;
            }
        }
        redirect($back);
    }
}
