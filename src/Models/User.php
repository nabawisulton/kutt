<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * User model - system accounts (admin/staff), not members.
 */
final class User
{
    public static function findByLogin(string $login): ?array
    {
        return Database::first(
            'SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$login, $login]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function all(): array
    {
        return Database::all(
            'SELECT id, user_id, username, email, full_name, role, is_active, created_at
             FROM users ORDER BY id ASC'
        );
    }

    public static function updatePasswordHash(int $id, string $hash): void
    {
        Database::exec('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }
}
