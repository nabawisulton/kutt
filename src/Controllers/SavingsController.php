<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Roles;
use App\Models\Savings;
use App\Models\Sale;
use App\Support\ExcelExport;
use RuntimeException;

/**
 * Tabungan Uang — pos saldo koperasi yang dapat dipakai bertransaksi.
 *  - Dashboard: saldo, arus keluar-masuk, buku besar
 *  - Setor / tarik tunai + penyesuaian (dengan jejak audit)
 *  - Pembayaran kasir via metode TABUNGAN (Sale::createPosOrder)
 *  - Laporan keluar-masuk + export Excel
 */
final class SavingsController extends Controller
{
    public function index(): void
    {
        Roles::requirePermission('savings.view');
        $user = (array) Auth::user();

        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to'] ?? date('Y-m-d');
        $accountFilter = (int) ($_GET['account'] ?? 0);

        $summary = Savings::periodSummary($from . ' 00:00:00', $to . ' 23:59:59');
        $ledger  = Savings::ledger($accountFilter > 0 ? $accountFilter : null, 200);

        $this->view('savings/index', [
            'pageTitle'    => 'Tabungan Uang',
            'pageSubtitle' => 'Pos tabungan koperasi — setor, tarik, dan pembayaran kasir',
            'accounts'     => Savings::accounts(),
            'totalBalance' => Savings::totalBalance(),
            'summary'      => $summary,
            'ledger'       => $ledger,
            'from'         => (string) $from,
            'to'           => (string) $to,
            'accountFilter' => $accountFilter,
            'canManage'    => Roles::can((string) ($user['role'] ?? ''), 'savings.manage'),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'savings',
        ]);
    }

    /** Setor / tarik tunai oleh admin/pengurus. */
    public function move(): void
    {
        Roles::requirePermission('savings.manage');
        Csrf::validate();
        $user = (array) Auth::user();

        $accountId = (int) ($_POST['account_id'] ?? 0);
        $type      = (string) ($_POST['type'] ?? '');
        $amount    = (float) str_replace(['.', ','], ['', '.'], (string) ($_POST['amount'] ?? '0'));
        $method    = (string) ($_POST['method'] ?? 'KAS');
        $note      = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 255);

        if (!in_array($type, ['SETOR', 'TARIK'], true)) {
            flash_set('error', 'Jenis transaksi tidak valid.');
            redirect('/tabungan');
        }
        if ($amount <= 0) {
            flash_set('error', 'Nominal harus lebih besar dari nol.');
            redirect('/tabungan');
        }
        if (!in_array($method, ['KAS', 'TRANSFER', 'LAINNYA'], true)) {
            $method = 'KAS';
        }

        Database::beginTransaction();
        try {
            Savings::apply(
                $accountId,
                $type,
                $amount,
                null,
                $note !== '' ? $note : ucfirst(strtolower($type)) . ' tabungan manual',
                (int) $user['id'],
                $method
            );
            Database::commit();
        } catch (RuntimeException $e) {
            Database::rollBack();
            flash_set('error', $e->getMessage());
            redirect('/tabungan');
        }

        Audit::log('CREATE', ucfirst(strtolower($type)) . ' tabungan Rp ' . number_format($amount, 0, ',', '.') . ' oleh ' . $user['username'], 'TABUNGAN');
        flash_set('success', 'Transaksi tabungan berhasil dicatat.');
        redirect('/tabungan');
    }

    /** Penyesuaian saldo (koreksi) — delta bisa +/-, wajib keterangan. */
    public function adjust(): void
    {
        Roles::requirePermission('savings.manage');
        Csrf::validate();
        $user = (array) Auth::user();

        $accountId = (int) ($_POST['account_id'] ?? 0);
        $delta     = (float) str_replace(['.', ','], ['', '.'], (string) ($_POST['delta'] ?? '0'));
        $note      = mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 255);

        if ($delta === 0.0) {
            flash_set('error', 'Nilai penyesuaian tidak boleh nol.');
            redirect('/tabungan');
        }
        if ($note === '') {
            flash_set('error', 'Keterangan wajib diisi untuk penyesuaian saldo.');
            redirect('/tabungan');
        }

        Database::beginTransaction();
        try {
            Savings::adjust($accountId, $delta, $note, (int) $user['id']);
            Database::commit();
        } catch (RuntimeException $e) {
            Database::rollBack();
            flash_set('error', $e->getMessage());
            redirect('/tabungan');
        }

        Audit::log('UPDATE', 'Penyesuaian tabungan ' . ($delta > 0 ? '+' : '') . number_format($delta, 0, ',', '.') . ' oleh ' . $user['username'] . ': ' . $note, 'TABUNGAN');
        flash_set('success', 'Penyesuaian saldo tabungan tersimpan.');
        redirect('/tabungan');
    }

    /** Export laporan tabungan ke Excel. */
    public function export(): void
    {
        Roles::requirePermission('savings.view');

        $from = (string) ($_GET['from'] ?? date('Y-m-01'));
        $to   = (string) ($_GET['to'] ?? date('Y-m-d'));
        $rows = [];
        // Filter periode langsung di SQL (index created_at), bukan di PHP.
        foreach (Savings::ledger(null, 5000, $from, $to) as $tx) {
            $inflow = in_array((string) $tx['type'], ['SETOR', 'REFUND'], true);
            $rows[] = [
                $tx['created_at'],
                $tx['transaction_no'],
                $tx['account_no'] . ' — ' . $tx['account_name'],
                $tx['type'],
                (string) ($tx['method'] ?? ''),
                (string) ($tx['description'] ?? ''),
                $inflow ? (float) $tx['amount'] : 0.0,
                !$inflow ? (float) $tx['amount'] : 0.0,
                (float) $tx['balance_after'],
                (string) ($tx['created_by_name'] ?? ''),
            ];
        }

        ExcelExport::download(
            'Laporan-Tabungan-' . $from . '_sd_' . $to . '.xls',
            'Tabungan',
            ['Tanggal', 'No. Transaksi', 'Akun', 'Jenis', 'Metode', 'Keterangan', 'Masuk', 'Keluar', 'Saldo Akhir', 'Oleh'],
            $rows
        );
        exit;
    }
}
