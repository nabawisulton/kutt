<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Member (anggota) model - CRUD + aggregates.
 * Full CRUD, search, filter, pagination, photo, QR card data.
 */
final class Member
{
    public static function countByStatus(string $status): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM members WHERE status = ?',
            [$status]
        );
    }

    public static function countAll(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM members');
    }

    /** Search + filter + pagination for admin list. */
    public static function paginate(array $filters, int $page = 1, int $perPage = 10): array
    {
        $where = ['1=1'];
        $params = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(member_no LIKE ? OR full_name LIKE ? OR nik LIKE ? OR phone LIKE ?)';
            $params = array_fill(0, 4, '%' . $filters['q'] . '%');
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (($filters['group'] ?? '') !== '') {
            $where[] = 'group_name LIKE ?';
            $params[] = '%' . $filters['group'] . '%';
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) Database::scalar("SELECT COUNT(*) FROM members WHERE {$whereSql}", $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $rows = Database::all(
            "SELECT * FROM members WHERE {$whereSql}
             ORDER BY created_at DESC, id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM members WHERE id = ?', [$id]);
    }

    public static function findByMemberNo(string $memberNo): ?array
    {
        return Database::first('SELECT * FROM members WHERE member_no = ?', [$memberNo]);
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('members', $data);
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, array $data): void
    {
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            $sets[] = "`{$col}` = ?";
            $params[] = $val;
        }
        $params[] = $id;
        Database::exec('UPDATE members SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    public static function delete(int $id): void
    {
        Database::exec('DELETE FROM members WHERE id = ?', [$id]);
    }

    /** Next member number: AGT-2026-0001 format. */
    public static function nextMemberNo(): string
    {
        $year = date('Y');
        $last = Database::scalar(
            "SELECT member_no FROM members WHERE member_no LIKE ? ORDER BY id DESC LIMIT 1",
            ["AGT-{$year}-%"]
        );
        $seq = $last === null ? 0 : (int) substr((string) $last, strrpos((string) $last, '-') + 1);

        return sprintf('AGT-%s-%04d', $year, $seq + 1);
    }

    /**
     * Finance aggregates for the member card/profile scan page.
     * Returns null when the member has no financial footprint at all.
     *
     * @return array<string,float>|null
     */
    public static function finance(int $memberId): ?array
    {
        $savings = Database::first(
            "SELECT
                COALESCE(SUM(CASE WHEN jenis = 'POKOK' AND tipe_transaksi = 'SETOR' THEN nominal ELSE 0 END), 0) simpanan_pokok,
                COALESCE(SUM(CASE WHEN jenis = 'WAJIB' AND tipe_transaksi = 'SETOR' THEN nominal ELSE 0 END), 0) simpanan_wajib,
                COALESCE(SUM(CASE WHEN jenis = 'SUKARELA' THEN
                    (CASE WHEN tipe_transaksi = 'SETOR' THEN nominal ELSE -nominal END) ELSE 0 END), 0) simpanan_sukarela
             FROM savings_transactions WHERE member_id = ?",
            [$memberId]
        ) ?? [];

        $pokok = (float) ($savings['simpanan_pokok'] ?? 0);
        $wajib = (float) ($savings['simpanan_wajib'] ?? 0);
        $sukarela = (float) ($savings['simpanan_sukarela'] ?? 0);

        $loan = Database::first(
            "SELECT
                COALESCE(SUM(CASE WHEN status IN ('APPROVED','DISBURSED') THEN pokok_pinjaman ELSE 0 END), 0) total_pinjaman,
                COALESCE(SUM(CASE WHEN status = 'LUNAS' THEN pokok_pinjaman ELSE 0 END), 0) pinjaman_lunas,
                COALESCE(SUM(CASE WHEN status = 'PENDING' THEN pokok_pinjaman ELSE 0 END), 0) pinjaman_pending
             FROM loans WHERE member_id = ?",
            [$memberId]
        ) ?? [];

        $payment = Database::first(
            "SELECT
                COALESCE(SUM(lp.total_bayar), 0) total_angsuran,
                MAX(lp.tanggal_bayar) pembayaran_terakhir
             FROM loan_payments lp
             JOIN loans l ON l.id = lp.loan_id
             WHERE l.member_id = ?",
            [$memberId]
        ) ?? [];

        // Outstanding = disbursed principal - principal repaid.
        $disbursed = (float) ($loan['total_pinjaman'] ?? 0);
        $paidPrincipal = (float) Database::scalar(
            "SELECT COALESCE(SUM(lp.bayar_pokok), 0)
             FROM loan_payments lp JOIN loans l ON l.id = lp.loan_id
             WHERE l.member_id = ?",
            [$memberId]
        );
        $outstanding = max(0.0, $disbursed - $paidPrincipal);

        $loanStatus = 'TIDAK ADA PINJAMAN';
        if ((float) ($loan['pinjaman_pending'] ?? 0) > 0) {
            $loanStatus = 'PENGAJUAN PENDING';
        } elseif ($outstanding > 0) {
            $loanStatus = 'AKTIF';
        } elseif ($disbursed > 0) {
            $loanStatus = 'LUNAS';
        }

        $shu = (float) Database::scalar(
            'SELECT COALESCE(SUM(total_shu), 0) FROM shu_distributions WHERE member_id = ?',
            [$memberId]
        );

        return [
            'simpanan_pokok'    => $pokok,
            'simpanan_wajib'    => $wajib,
            'simpanan_sukarela' => $sukarela,
            'total_simpanan'    => $pokok + $wajib + $sukarela,
            'total_pinjaman'    => $disbursed,
            'total_angsuran'    => (float) ($payment['total_angsuran'] ?? 0),
            'sisa_pinjaman'     => $outstanding,
            'pembayaran_terakhir' => $payment['pembayaran_terakhir'] ?? null,
            'status_pinjaman'   => $loanStatus,
            'shu_total'         => $shu,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public static function recentTransactions(int $memberId, int $limit = 8): array
    {
        $rows = Database::all(
            "(SELECT 'SIMPANAN' AS modul, trans_no AS ref, CONCAT(jenis, ' / ', tipe_transaksi) AS jenis,
                    nominal, tanggal_trans AS tanggal
              FROM savings_transactions WHERE member_id = :mid)
             UNION ALL
             (SELECT 'ANGSURAN', payment_no, CONCAT('Angsuran ke-', angsuran_ke), total_bayar, tanggal_bayar
              FROM loan_payments lp JOIN loans l ON l.id = lp.loan_id WHERE l.member_id = :mid2)
             ORDER BY tanggal DESC
             LIMIT " . max(1, $limit),
            ['mid' => $memberId, 'mid2' => $memberId]
        );

        return $rows;
    }
}
