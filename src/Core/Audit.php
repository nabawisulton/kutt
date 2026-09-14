<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Database;

/**
 * Activity/audit log writer.
 * Every sensitive action should call Audit::log(). Never throws.
 */
final class Audit
{
    public static function log(string $action, string $details, string $module = 'SYSTEM', ?string $dataId = null): void
    {
        try {
            $user = Auth::user();
            Database::insert('audit_logs', [
                'user_id'   => $user['id'] ?? null, // audit_logs.user_id is an INT FK to users.id
                'role'      => $user['role'] ?? null,
                'action'    => $action,
                'module'    => $module,
                'data_id'   => $dataId,
                'details'   => mb_substr($details, 0, 480),
                'ip'        => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Audit failure must not break the main flow - but it must not be
            // invisible either. Log to file so schema drift is diagnosable.
            error_log('[KUTT][Audit] insert failed: ' . $e->getMessage());
        }
    }

    /** @return array<int,array<string,mixed>> */
    public static function recent(int $limit = 50): array
    {
        return Database::all(
            'SELECT * FROM audit_logs ORDER BY id DESC LIMIT ' . max(1, $limit)
        );
    }
}
