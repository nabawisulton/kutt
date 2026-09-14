<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * In-app notification center.
 */
final class Notification
{
    /**
     * Push a notification.
     * $to: ['user' => int] | ['role' => string] | ['all' => true]
     */
    public static function push(string $title, string $message, string $type = 'INFO', ?string $link = null, array $to = ['all' => true]): void
    {
        $userId = $to['user'] ?? null;
        $role = $to['role'] ?? null;
        if (isset($to['all'])) {
            $userId = null;
            $role = null;
        }

        try {
            Database::insert('notifications', [
                'user_id'    => $userId,
                'role'       => $role,
                'title'      => mb_substr($title, 0, 160),
                'message'    => mb_substr($message, 0, 480),
                'type'       => $type,
                'link'       => $link,
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Notification failure must never break the main operation - but log
            // it so silent failures (schema drift, DB outage) are diagnosable.
            error_log('[KUTT][Notification] push failed: ' . $e->getMessage());
        }
    }

    /** @return array<int,array<string,mixed>> */
    public static function latestFor(int $userId, string $role, int $limit = 10): array
    {
        return Database::all(
            "SELECT * FROM notifications
             WHERE user_id = ? OR (user_id IS NULL AND (role IS NULL OR role = ?))
             ORDER BY id DESC
             LIMIT " . max(1, $limit),
            [$userId, $role]
        );
    }

    public static function unreadCount(int $userId, string $role): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM notifications
             WHERE is_read = 0 AND (user_id = ? OR (user_id IS NULL AND (role IS NULL OR role = ?)))",
            [$userId, $role]
        );
    }

    public static function markAllRead(int $userId, string $role): void
    {
        Database::exec(
            "UPDATE notifications SET is_read = 1
             WHERE is_read = 0 AND (user_id = ? OR (user_id IS NULL AND (role IS NULL OR role = ?)))",
            [$userId, $role]
        );
    }

    public static function clearFor(int $userId, string $role): void
    {
        Database::exec(
            "DELETE FROM notifications WHERE user_id = ? OR (user_id IS NULL AND (role IS NULL OR role = ?))",
            [$userId, $role]
        );
    }
}
