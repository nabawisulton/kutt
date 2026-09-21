<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Roles;
use App\Models\Dashboard;
use App\Models\Notification;
use App\Support\ExcelExport;

/**
 * Dashboard controller - Executive Dashboard + export.
 */
final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $user = (array) Auth::user();

        // ANGGOTA tidak melihat executive dashboard: arahkan ke portalnya.
        if (($user['role'] ?? '') === \App\Core\Roles::ANGGOTA) {
            redirect('/portal');
        }

        // Pembersihan lazy: order marketplace NEW menggantung > 24 jam dibatalkan
        // otomatis (stok dikembalikan) agar tidak menahan stok tanpa batas.
        \App\Models\Sale::expireStaleNewOrders();

        $userId = (int) $user['id'];

        $this->view('dashboard/index', [
            'pageTitle'    => 'Executive Dashboard',
            'pageSubtitle' => 'Ringkasan performa finansial dan operasional KUTT Suka Makmur Grati',
            'metrics'      => Dashboard::metrics(),
            'trend'        => Dashboard::financialTrend(),
            'portfolio'    => Dashboard::savingsPortfolio(),
            'recent'       => Dashboard::recentTransactions(8),
            'notifications' => Notification::latestFor($userId, (string) $user['role'], 6),
            'unreadCount'  => Notification::unreadCount($userId, (string) $user['role']),
            'role'         => (string) $user['role'],
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'dashboard',
        ]);
    }

    public function exportExcel(): void
    {
        Auth::requireLogin();
        Roles::requirePermission('report.export');

        $metrics = Dashboard::metrics();
        $rows = [];
        foreach ($metrics as $key => $value) {
            $rows[] = [ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', (string) $key)), (string) $value];
        }

        Audit::log('EXPORT', 'Export Excel ringkasan dashboard', 'DASHBOARD');
        ExcelExport::download('ringkasan-dashboard-kutt', 'Ringkasan Dashboard',
            ['Indikator', 'Nilai'], $rows);
    }
}
