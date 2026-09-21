<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

/**
 * Tabungan Uang (tabungan kas) KUTT.
 *
 * - Setiap akun tabungan punya satu baris cash_savings_accounts.
 * - Semua perubahan saldo WAJIB lewat apply(): row-locked (FOR UPDATE)
 *   di dalam transaksi pemanggil, saldo tidak boleh negatif,
 *   dan tiap mutasi tercatat di cash_savings_transactions.
 * - Pembayaran kasir pakai metode TABUNGAN: Sale::createOrder()
 *   memanggil apply() bertipe PEMBAYARAN; batal/refund → REFUND.
 */
final class Savings
{
    public const MAX_ATTEMPTS = 3;

    /** Daftar akun tabungan (aktif lebih dulu, urut nomor). */
    public static function accounts(): array
    {
        return Database::all(
            'SELECT ta.*, u.full_name AS created_by_name
             FROM cash_savings_accounts ta
             LEFT JOIN users u ON u.id = ta.created_by
             ORDER BY ta.is_active DESC, ta.account_no ASC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM cash_savings_accounts WHERE id = ?', [$id]);
    }

    /** @param bool $onlyActive hanya akun aktif (untuk dropdown form) */
    public static function options(bool $onlyActive = true): array
    {
        return Database::all(
            'SELECT id, account_no, name, balance FROM cash_savings_accounts'
            . ($onlyActive ? ' WHERE is_active = 1' : '') .
            ' ORDER BY account_no ASC'
        );
    }

    /** Lock akun utk transaksi; return baris terkini. */
    public static function lock(int $id): array
    {
        $row = Database::first('SELECT * FROM cash_savings_accounts WHERE id = ? FOR UPDATE', [$id]);
        if ($row === null) {
            throw new RuntimeException('Akun tabungan tidak ditemukan.');
        }

        return $row;
    }

    /**
     * Mutasi saldo tabungan — SATU-SATUNYA pintu perubahan saldo.
     * Wajib dipanggil di dalam transaksi DB (lock() → apply()).
     *
     * @param string $type SETOR|TARIK|PEMBAYARAN|REFUND|PENYESUAIAN
     * @param float  $amount selalu positif; arah ditentukan $type
     * @param string $method KAS|TRANSFER|LAINNYA
     */
    public static function apply(
        int $accountId,
        string $type,
        float $amount,
        ?int $orderId = null,
        string $description = '',
        ?int $userId = null,
        string $method = 'KAS'
    ): array {
        $inflow = ['SETOR', 'REFUND', 'PENYESUAIAN_IN'];
        if ($amount <= 0) {
            throw new RuntimeException('Nominal tabungan harus lebih besar dari nol.');
        }
        $delta = in_array($type, $inflow, true) ? $amount : -$amount;
        if (!in_array($type, ['SETOR', 'TARIK', 'PEMBAYARAN', 'REFUND', 'PENYESUAIAN_IN', 'PENYESUAIAN_OUT'], true)) {
            throw new RuntimeException('Jenis transaksi tabungan tidak dikenal.');
        }

        $account = self::lock($accountId);
        if ((int) $account['is_active'] !== 1) {
            throw new RuntimeException('Akun tabungan tidak aktif.');
        }
        $before = (float) $account['balance'];
        $after  = $before + $delta;
        if ($after < 0) {
            throw new RuntimeException('Saldo tabungan tidak mencukupi. Saldo: Rp ' . number_format($before, 0, ',', '.') . ', dibutuhkan: Rp ' . number_format($amount, 0, ',', '.'));
        }

        // Nomor unik dgn retry (race-safe utk unique key).
        $attempt = 0;
        while (true) {
            $attempt++;
            $no = self::nextNo();
            try {
                Database::insert('cash_savings_transactions', [
                    'account_id'     => $accountId,
                    'transaction_no' => $no,
                    'type'           => str_starts_with($type, 'PENYESUAIAN') ? 'PENYESUAIAN' : $type,
                    'amount'         => abs($delta),
                    'balance_before' => $before,
                    'balance_after'  => $after,
                    'method'         => $method,
                    'description'    => $description !== '' ? mb_substr($description, 0, 255) : null,
                    'reference_type' => $orderId !== null ? 'sales_order' : null,
                    'reference_id'   => $orderId,
                    'created_by'     => $userId,
                ]);
                Database::exec('UPDATE cash_savings_accounts SET balance = ? WHERE id = ?', [$after, $accountId]);

                return ['balance_before' => $before, 'balance_after' => $after, 'transaction_no' => $no];
            } catch (\PDOException $e) {
                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw $e;
                }
            }
        }
    }

    /** Penyesuaian dua arah: nominal bisa +/-, hasil tidak boleh negatif. */
    public static function adjust(int $accountId, float $signedDelta, string $description, ?int $userId): array
    {
        $account = self::lock($accountId);
        $before  = (float) $account['balance'];
        $after   = $before + $signedDelta;
        if ($after < 0) {
            throw new RuntimeException('Penyesuaian menghasilkan saldo negatif.');
        }

        Database::insert('cash_savings_transactions', [
            'account_id'     => $accountId,
            'transaction_no' => self::nextNo(),
            'type'           => 'PENYESUAIAN',
            'amount'         => abs($signedDelta),
            'balance_before' => $before,
            'balance_after'  => $after,
            'method'         => 'LAINNYA',
            'description'    => mb_substr($description, 0, 255),
            'created_by'     => $userId,
        ]);
        Database::exec('UPDATE cash_savings_accounts SET balance = ? WHERE id = ?', [$after, $accountId]);

        return ['balance_before' => $before, 'balance_after' => $after];
    }

    /** Total saldo seluruh akun tabungan aktif. */
    public static function totalBalance(): float
    {
        return (float) Database::scalar('SELECT COALESCE(SUM(balance), 0) FROM cash_savings_accounts WHERE is_active = 1');
    }

    /** Buku besar tabungan, terbaru dulu. */
    public static function ledger(?int $accountId = null, int $limit = 200, ?string $from = null, ?string $to = null): array
    {
        $sql = 'SELECT tt.*, ta.name AS account_name, ta.account_no, u.full_name AS created_by_name
                FROM cash_savings_transactions tt
                JOIN cash_savings_accounts ta ON ta.id = tt.account_id
                LEFT JOIN users u ON u.id = tt.created_by';
        $params = [];
        $where = [];
        if ($accountId !== null) {
            $where[] = 'tt.account_id = ?';
            $params[] = $accountId;
        }
        if ($from !== null && $from !== '') {
            $where[] = 'tt.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== null && $to !== '') {
            $where[] = 'tt.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY tt.id DESC LIMIT ' . max(1, min(500, $limit));

        return Database::all($sql, $params);
    }

    /** Ringkasan arus keluar-masuk per periode (basis created_at). */
    public static function periodSummary(string $from, string $to): array
    {
        $in  = (float) Database::scalar(
            "SELECT COALESCE(SUM(amount), 0) FROM cash_savings_transactions
             WHERE type IN ('SETOR', 'REFUND') AND created_at BETWEEN ? AND ?",
            [$from, $to]
        );
        $out = (float) Database::scalar(
            "SELECT COALESCE(SUM(amount), 0) FROM cash_savings_transactions
             WHERE type IN ('TARIK', 'PEMBAYARAN') AND created_at BETWEEN ? AND ?",
            [$from, $to]
        );

        return ['in' => $in, 'out' => $out];
    }

    /** Nomor transaksi unik: TBG-YYYYMMDD-#### (retry saat bentrok). */
    private static function nextNo(): string
    {
        $attempt = 0;
        while (true) {
            $attempt++;
            $no = 'TBG-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $exists = Database::scalar('SELECT COUNT(*) FROM cash_savings_transactions WHERE transaction_no = ?', [$no]);
            if ((int) $exists === 0) {
                return $no;
            }
            if ($attempt >= 10) {
                return $no . '-' . random_int(100, 999);
            }
        }
    }
}
