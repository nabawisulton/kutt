<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Session-based authentication + login throttling.
 * Passwords use password_hash()/password_verify() (bcrypt).
 */
final class Auth
{
    private const SESSION_KEY   = '_auth_user';
    private const THROTTLE_KEY  = '_login_throttle';

    /** Attempt a login. Returns true and regenerates the session on success. */
    public static function attempt(string $login, string $password): bool
    {
        $user = User::findByLogin($login);
        if ($user === null) {
            // Equalize timing between unknown user and wrong password.
            password_verify($password, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');
            return false;
        }

        if ((int) $user['is_active'] !== 1) {
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = [
            'id'        => (int) $user['id'],
            'user_id'   => (string) $user['user_id'],
            'username'  => (string) $user['username'],
            'full_name' => (string) $user['full_name'],
            'role'      => (string) $user['role'],
            'email'     => (string) $user['email'],
        ];
        $_SESSION['_auth_login_at'] = time();

        Audit::log('LOGIN', 'User berhasil login: ' . $user['username']);

        // Password bawaan seed harus diganti pada login pertama (keamanan).
        if (password_verify('admin123', (string) $user['password_hash'])) {
            self::setMustChangePassword(true);
        }

        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]['id']);
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION[self::SESSION_KEY]['id'] ?? null;
    }

    public static function logout(): void
    {
        $user = self::user();
        if ($user !== null) {
            Audit::log('LOGOUT', 'User logout: ' . $user['username']);
        }
        unset($_SESSION[self::SESSION_KEY], $_SESSION['_auth_login_at']);
        session_regenerate_id(true);
    }

    /** Enforce an authenticated session, otherwise redirect to /login. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash_set('error', 'Silakan login terlebih dahulu.');
            redirect('/login');
        }
    }

    /**
     * Password masih bawaan seed (admin123)? Semua akses selain logout
     * diarahkan ke /password/change sampai password diganti.
     */
    public static function mustChangePassword(): bool
    {
        return self::check()
            && ($_SESSION['_must_change_password'] ?? false) === true;
    }

    /** Tandai/lepaskan flag "wajib ganti password" untuk user aktif. */
    public static function setMustChangePassword(bool $required): void
    {
        if ($required) {
            $_SESSION['_must_change_password'] = true;
        } else {
            unset($_SESSION['_must_change_password']);
        }
    }

    /** Enforce an authenticated session with a specific view permission. */
    public static function requirePermission(string $view): void
    {
        self::requireLogin();
        if (!Roles::canView($view)) {
            flash_set('error', 'Akses ditolak: halaman ini tidak tersedia untuk role Anda.');
            redirect('/dashboard');
        }
    }

    /**
     * Simple login throttle: max 5 attempts per 60 seconds per identity+IP.
     * Returns true when the attempt is allowed.
     */
    public static function throttleAllow(string $identity): bool
    {
        $key = self::THROTTLE_KEY;
        $bucket = $_SESSION[$key] ?? [];
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $now = time();
        $identityKey = md5($identity . '|' . $ip);

        $bucket = array_values(array_filter(
            $bucket,
            static fn (array $item): bool => $item['t'] > $now - 60
        ));

        $count = 0;
        foreach ($bucket as $item) {
            if ($item['k'] === $identityKey) {
                $count++;
            }
        }

        if ($count >= 5) {
            return false;
        }

        $bucket[] = ['k' => $identityKey, 't' => $now];
        $_SESSION[$key] = $bucket;

        return true;
    }

    public static function throttleRemainingSeconds(): int
    {
        $bucket = $_SESSION[self::THROTTLE_KEY] ?? [];
        if ($bucket === []) {
            return 0;
        }
        $last = max(array_column($bucket, 't'));

        return max(0, 60 - (time() - (int) $last));
    }
}
