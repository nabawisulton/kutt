<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Roles;
use App\Models\Notification;

/**
 * Notification center + activity log endpoints.
 */
final class MiscController extends Controller
{
    public function notifications(): void
    {
        Auth::requireLogin();
        $user = (array) Auth::user();

        header('Content-Type: application/json');
        echo json_encode([
            'unread' => Notification::unreadCount((int) $user['id'], (string) $user['role']),
            'items'  => Notification::latestFor((int) $user['id'], (string) $user['role'], 8),
        ]);
        exit;
    }

    public function notificationsRead(): void
    {
        Auth::requireLogin();
        Csrf::validate();

        $user = (array) Auth::user();
        Notification::markAllRead((int) $user['id'], (string) $user['role']);
        http_response_code(204);
        exit;
    }

    public function notificationsClear(): void
    {
        Auth::requireLogin();
        Csrf::validate();

        $user = (array) Auth::user();
        Notification::clearFor((int) $user['id'], (string) $user['role']);
        flash_set('success', 'Notifikasi dibersihkan.');
        redirect('/dashboard');
    }

    public function activity(): void
    {
        Roles::requirePermission('activity_log.view');

        $user = (array) Auth::user();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;

        $total = (int) \App\Core\Database::scalar('SELECT COUNT(*) FROM audit_logs');
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $logs = \App\Core\Database::all(
            "SELECT * FROM audit_logs ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}"
        );

        $this->view('activity/index', [
            'pageTitle'    => 'Log Aktivitas',
            'pageSubtitle' => 'Jejak audit seluruh aktivitas penting sistem',
            'logs'         => $logs,
            'page'         => $page,
            'pages'        => $pages,
            'total'        => $total,
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'activity',
        ]);
    }
}
