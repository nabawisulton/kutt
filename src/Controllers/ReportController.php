<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Roles;
use App\Core\View;
use App\Models\Dashboard;
use App\Models\Member;
use App\Support\PdfExport;

/**
 * Cetak / PDF / Excel untuk modul laporan (dashboard & anggota).
 * Print = HTML print-view; PDF = server-side FPDF; Excel ada di masing-masing modul.
 */
final class ReportController extends Controller
{
    public function dashboardPrint(): void
    {
        Auth::requireLogin();
        Roles::requirePermission('report.print');

        $user = (array) Auth::user();
        $this->printShell('Ringkasan Dashboard', 'Data per ' . date('d/m/Y'), (string) $user['full_name'], 'reports/dashboard_summary', [
            'metrics' => Dashboard::metrics(),
            'recent'  => Dashboard::recentTransactions(15),
        ]);
    }

    public function dashboardPdf(): void
    {
        Auth::requireLogin();
        Roles::requirePermission('report.export');

        $metrics = Dashboard::metrics();
        $rows = [];
        foreach ($metrics as $key => $value) {
            $rows[] = [ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', (string) $key)), (string) $value];
        }

        Audit::log('EXPORT', 'Export PDF ringkasan dashboard', 'DASHBOARD');
        PdfExport::download('ringkasan-dashboard-kutt', 'Ringkasan Dashboard',
            ['Indikator', 'Nilai'], $rows, 'per ' . date('d/m/Y'));
    }

    public function membersPrint(): void
    {
        Roles::requirePermission('report.print');

        $user = (array) Auth::user();
        $rows = Member::paginate($_GET, 1, 5000)['rows'];
        $this->printShell('Laporan Data Anggota', 'KUTT SUKA MAKMUR Grati - Pasuruan', (string) $user['full_name'], 'reports/members_table', [
            'rows'    => $rows,
            'filters' => $_GET,
        ]);
    }

    public function membersPdf(): void
    {
        Roles::requirePermission('report.export');

        $rows = Member::paginate($_GET, 1, 5000)['rows'];
        $out = [];
        foreach ($rows as $m) {
            $out[] = [
                $m['member_no'], $m['full_name'], (string) ($m['nik'] ?? '-'),
                (string) ($m['gender'] ?? '-'), (string) ($m['phone'] ?? '-'),
                (string) ($m['group_name'] ?? '-'), $m['status'],
                (string) ($m['joined_at'] ?? '-'),
            ];
        }

        Audit::log('EXPORT', 'Export PDF data anggota', 'MEMBERS');
        PdfExport::download('anggota-kutt', 'Laporan Data Anggota',
            ['No. Anggota', 'Nama', 'NIK', 'L/P', 'No. HP', 'Kelompok', 'Status', 'Bergabung'],
            $out, 'total ' . count($out) . ' anggota');
    }

    /** Render the shared print shell with a child fragment. */
    private function printShell(string $title, string $subtitle, string $userName, string $childView, array $childData): void
    {
        Audit::log('PRINT', 'Cetak laporan: ' . $title, 'REPORTS');
        $content = View::render($childView, $childData);
        echo View::render('reports/layout_print', [
            'title'    => $title,
            'subtitle' => $subtitle,
            'userName' => $userName,
            'content'  => $content,
        ]);
    }
}
