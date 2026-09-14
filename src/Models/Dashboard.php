<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Dashboard aggregate queries (Tahap 1 scope).
 * All figures are derived from the normalized tables so they stay
 * consistent with the transaction modules built in later stages.
 */
final class Dashboard
{
    /** @return array<string,mixed> */
    public static function metrics(): array
    {
        $pdo = Database::pdo();

        $membersActive = (int) Database::scalar(
            "SELECT COUNT(*) FROM members WHERE status = 'AKTIF'"
        );

        $totalSimpanan = (float) Database::scalar(
            "SELECT COALESCE(SUM(CASE WHEN tipe_transaksi = 'SETOR' THEN nominal
                                      WHEN tipe_transaksi = 'TARIK' THEN -nominal
                                      ELSE 0 END), 0)
             FROM savings_transactions"
        );

        // Outstanding = sisa pokok yang belum dibayar dari pinjaman DISBURSED
        // (dihitung dari jadwal UNPAID, bukan plafon penuh).
        $loanOutstanding = (float) Database::scalar(
            "SELECT COALESCE(SUM(s.pokok_due), 0)
             FROM loan_schedules s
             JOIN loans l ON l.id = s.loan_id
             WHERE s.status = 'UNPAID' AND l.status = 'DISBURSED'"
        );

        // Pinjaman aktif = DISBURSED dan masih ada angsuran berjalan.
        $loanActive = (float) Database::scalar(
            "SELECT COALESCE(SUM(l.pokok_pinjaman), 0)
             FROM loans l
             WHERE l.status = 'DISBURSED'
               AND EXISTS (
                   SELECT 1 FROM loan_schedules s
                   WHERE s.loan_id = l.id AND s.status <> 'PAID'
               )"
        );

        $pendingLoans = (int) Database::scalar(
            "SELECT COUNT(*) FROM loans WHERE status = 'PENDING'"
        );

        $cashBalance = (float) Database::scalar(
            "SELECT COALESCE(SUM(j.debet - j.kredit), 0)
             FROM journal_entries j
             JOIN chart_of_accounts c ON c.account_code = j.account_code
             WHERE c.account_category = 'ASSET' AND c.account_code LIKE '110%'"
        );

        $revenue = (float) Database::scalar(
            "SELECT COALESCE(SUM(j.kredit - j.debet), 0)
             FROM journal_entries j
             JOIN chart_of_accounts c ON c.account_code = j.account_code
             WHERE c.account_category = 'REVENUE'"
        );

        $shuAvailable = (float) Database::scalar(
            "SELECT COALESCE(SUM(total_shu), 0) FROM shu_distributions WHERE status_pencairan = 'PENDING'"
        );

        return [
            'totalMembers'   => $membersActive,
            'totalSimpanan'  => $totalSimpanan,
            'outstandingPinjaman' => $loanOutstanding,
            'activePinjaman' => $loanActive,
            'pendingLoans'   => $pendingLoans,
            'saldoKas'       => $cashBalance,
            'totalRevenue'   => $revenue,
            'shuAvailable'   => $shuAvailable,
        ];
    }

    /** @return array{labels: string[], simpanan: float[], pinjaman: float[]} */
    public static function financialTrend(): array
    {
        $labels = [];
        $keys = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts = mktime(0, 0, 0, (int) date('n') - $i, 1, (int) date('Y'));
            $labels[] = date('M', $ts);
            $keys[] = date('Y-m', $ts);
        }

        $simpananRows = Database::all(
            "SELECT DATE_FORMAT(tanggal_trans, '%Y-%m') ym,
                    COALESCE(SUM(CASE WHEN tipe_transaksi = 'SETOR' THEN nominal
                                      WHEN tipe_transaksi = 'TARIK' THEN -nominal
                                      ELSE 0 END), 0) total
             FROM savings_transactions
             GROUP BY ym"
        );
        $pinjamanRows = Database::all(
            "SELECT DATE_FORMAT(tanggal_pengajuan, '%Y-%m') ym,
                    COALESCE(SUM(pokok_pinjaman), 0) total
             FROM loans
             WHERE status IN ('APPROVED','DISBURSED','LUNAS')
             GROUP BY ym"
        );

        $simpananMap = array_column($simpananRows, 'total', 'ym');
        $pinjamanMap = array_column($pinjamanRows, 'total', 'ym');

        $simpananSeries = [];
        $pinjamanSeries = [];
        $cumSimpanan = 0.0;
        foreach ($keys as $key) {
            $cumSimpanan += (float) ($simpananMap[$key] ?? 0);
            $simpananSeries[] = max(0.0, $cumSimpanan);
            $pinjamanSeries[] = (float) ($pinjamanMap[$key] ?? 0);
        }

        return ['labels' => $labels, 'simpanan' => $simpananSeries, 'pinjaman' => $pinjamanSeries];
    }

    /** @return array{labels: string[], values: float[]} */
    public static function savingsPortfolio(): array
    {
        $rows = Database::all(
            "SELECT jenis, COALESCE(SUM(CASE WHEN tipe_transaksi = 'SETOR' THEN nominal
                                            WHEN tipe_transaksi = 'TARIK' THEN -nominal
                                            ELSE 0 END), 0) total
             FROM savings_transactions
             GROUP BY jenis"
        );

        $jenisLabels = ['POKOK' => 'Pokok', 'WAJIB' => 'Wajib', 'SUKARELA' => 'Sukarela'];
        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $jenis = (string) $row['jenis'];
            $labels[] = $jenisLabels[$jenis] ?? ucfirst(strtolower($jenis));
            $values[] = max(0.0, (float) $row['total']);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Latest operational transactions across modules for the dashboard table.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function recentTransactions(int $limit = 8): array
    {
        $rows = Database::all(
            "SELECT j.tanggal, j.ref_voucher, j.account_code, j.debet, j.kredit, j.keterangan
             FROM journal_entries j
             ORDER BY j.tanggal DESC, j.id DESC
             LIMIT " . max(1, $limit)
        );

        $out = [];
        foreach ($rows as $row) {
            $nominal = (float) $row['debet'] > 0 ? (float) $row['debet'] : (float) $row['kredit'];
            $isCash = str_starts_with((string) $row['account_code'], '110');
            $out[] = [
                'id_ref'    => (string) ($row['ref_voucher'] ?: ('JR-' . $row['account_code'])),
                'anggota'   => (string) $row['keterangan'],
                'tipe'      => $isCash ? 'KAS' : 'JURNAL',
                'nominal'   => $nominal,
                'status'    => $isCash ? 'POSTED' : 'AUTO',
                'tanggal'   => (string) $row['tanggal'],
            ];
        }

        return $out;
    }

    /**
     * System notifications for the current user (or global broadcasts).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function notifications(int $userId, int $limit = 6): array
    {
        return Database::all(
            'SELECT id, title, message, type, is_read, created_at
             FROM notifications
             WHERE user_id IS NULL OR user_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT ' . max(1, $limit),
            [$userId]
        );
    }

    public static function unreadNotificationCount(int $userId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM notifications
             WHERE is_read = 0 AND (user_id IS NULL OR user_id = ?)',
            [$userId]
        );
    }
}
