<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Settings (CMS/app configuration) key-value model.
 */
final class Setting
{
    /** @return array<string,string> key => value map */
    public static function all(): array
    {
        $rows = Database::all('SELECT setting_key, setting_value FROM settings');
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['setting_key']] = (string) $row['setting_value'];
        }

        return $map;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = Database::scalar('SELECT setting_value FROM settings WHERE setting_key = ?', [$key]);

        return $value === false || $value === null ? $default : (string) $value;
    }

    public static function set(string $key, string $value, ?int $userId = null): void
    {
        Database::exec(
            'INSERT INTO settings (setting_key, setting_value, updated_at, updated_by)
             VALUES (?, ?, NOW(), ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value),
                                     updated_at = NOW(), updated_by = VALUES(updated_by)',
            [$key, $value, $userId]
        );
    }
}
