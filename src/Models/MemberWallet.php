<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

/**
 * Saldo Anggota (wallet) + PIN transaksi 6 digit.
 *
 * Keamanan:
 *  - PIN DISIMPAN SEBAGAI HASH (bcrypt) — tidak pernah plaintext.
 *  - Percobaan PIN salah dibatasi (5x) lalu wallet terkunci 15 menit.
 *  - Semua mutasi saldo lewat apply(): row-locked (FOR UPDATE) di dalam
 *    transaksi pemanggil, saldo tidak pernah boleh negatif, dan setiap
 *    perubahan tercatat di wallet_transactions (ledger audit penuh).
 */
final class MemberWallet
{
    public const TYPES = ['TOPUP', 'PEMBAYARAN', 'REFUND', 'PENYESUAIAN'];
    private const MAX_PIN_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    /** Pastikan wallet ada untuk anggota (auto-create saldo 0). */
    public static function ensure(int $memberId): array
    {
        $wallet = Database::first('SELECT * FROM member_wallets WHERE member_id = ?', [$memberId]);
        if ($wallet !== null) {
            return $wallet;
        }

        Database::exec('INSERT INTO member_wallets (member_id, balance) SELECT ?, 0 WHERE NOT EXISTS (SELECT 1 FROM member_wallets WHERE member_id = ?)', [$memberId, $memberId]);

        return Database::first('SELECT * FROM member_wallets WHERE member_id = ?', [$memberId]) ?? [];
    }

    public static function balance(int $memberId): float
    {
        $wallet = Database::first('SELECT balance FROM member_wallets WHERE member_id = ?', [$memberId]);

        return $wallet === null ? 0.0 : (float) $wallet['balance'];
    }

    public static function hasPin(int $memberId): bool
    {
        $wallet = Database::first('SELECT pin_hash FROM member_wallets WHERE member_id = ?', [$memberId]);

        return $wallet !== null && $wallet['pin_hash'] !== null && $wallet['pin_hash'] !== '';
    }

    /** Validasi format PIN 6 digit; lempar RuntimeException bila tidak valid. */
    public static function assertPinFormat(string $pin): void
    {
        if (!preg_match('/^\d{6}$/', $pin)) {
            throw new RuntimeException('PIN harus tepat 6 digit angka.');
        }
    }

    /** Buat / ganti PIN (hash bcrypt). $currentPin wajib bila PIN sudah ada. */
    public static function setPin(int $memberId, string $newPin, ?string $currentPin = null): void
    {
        self::assertPinFormat($newPin);
        if ($newPin === '123456' || preg_match('/^(\d)\1{5}$/', $newPin)) {
            throw new RuntimeException('PIN terlalu mudah ditebak. Gunakan kombinasi angka yang lain.');
        }

        $wallet = self::ensure($memberId);
        $hasPin = $wallet['pin_hash'] !== null && $wallet['pin_hash'] !== '';
        if ($hasPin) {
            if ($currentPin === null || $currentPin === '') {
                throw new RuntimeException('Masukkan PIN saat ini untuk mengubah PIN.');
            }
            self::verifyPin($memberId, $currentPin);
        }

        Database::exec(
            'UPDATE member_wallets SET pin_hash = ?, pin_attempts = 0, pin_locked_until = NULL, updated_at = NOW() WHERE member_id = ?',
            [password_hash($newPin, PASSWORD_DEFAULT), $memberId]
        );
    }

    /**
     * Verifikasi PIN dengan pembatasan percobaan.
     * Melempar RuntimeException dengan pesan yang aman ditampilkan ke user.
     */
    public static function verifyPin(int $memberId, string $pin): bool
    {
        $wallet = self::ensure($memberId);
        if ($wallet['pin_hash'] === null || $wallet['pin_hash'] === '') {
            throw new RuntimeException('PIN belum dibuat. Silakan buat PIN transaksi terlebih dahulu.');
        }

        // Cek lock di sisi DB (timezone-safe): pin_locked_until > NOW() dihitung
        // oleh MySQL, sehingga perbedaan timezone PHP vs DB tidak mempengaruhi.
        $lockInfo = Database::first(
            'SELECT (pin_locked_until IS NOT NULL AND pin_locked_until > NOW()) AS locked,
                    GREATEST(1, COALESCE(TIMESTAMPDIFF(MINUTE, NOW(), pin_locked_until), 0)) AS minutes
             FROM member_wallets WHERE id = ?',
            [(int) $wallet['id']]
        );
        if ($lockInfo !== null && (int) $lockInfo['locked'] === 1) {
            throw new RuntimeException('PIN terkunci sementara karena terlalu banyak percobaan. Coba lagi dalam ' . (int) $lockInfo['minutes'] . ' menit.');
        }

        self::assertPinFormat($pin);

        if (!password_verify($pin, (string) $wallet['pin_hash'])) {
            $attempts = (int) $wallet['pin_attempts'] + 1;
            if ($attempts >= self::MAX_PIN_ATTEMPTS) {
                Database::exec(
                    'UPDATE member_wallets SET pin_attempts = ?, pin_locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?',
                    [$attempts, self::LOCK_MINUTES, (int) $wallet['id']]
                );
                throw new RuntimeException('PIN salah ' . self::MAX_PIN_ATTEMPTS . ' kali. PIN terkunci selama ' . self::LOCK_MINUTES . ' menit.');
            }

            Database::exec(
                'UPDATE member_wallets SET pin_attempts = ? WHERE id = ?',
                [$attempts, (int) $wallet['id']]
            );
            throw new RuntimeException('PIN salah. Silakan coba kembali (sisa percobaan: ' . (self::MAX_PIN_ATTEMPTS - $attempts) . ').');
        }

        if ((int) $wallet['pin_attempts'] > 0 || $wallet['pin_locked_until'] !== null) {
            Database::exec(
                'UPDATE member_wallets SET pin_attempts = 0, pin_locked_until = NULL WHERE id = ?',
                [(int) $wallet['id']]
            );
        }

        return true;
    }

    /**
     * Terapkan perubahan saldo (+ kredit / - debit) dan catat ledger.
     * WAJIB dipanggil di dalam transaksi DB pemanggil (row lock FOR UPDATE
     * aktif sampai commit/rollback). Saldo tidak pernah boleh negatif.
     *
     * @return array{balance_before: float, balance_after: float, transaction_no: string}
     * @throws RuntimeException saldo tidak cukup / data tidak valid
     */
    public static function apply(int $memberId, float $amount, string $type, string $description, ?string $reference = null, ?int $orderId = null, ?int $userId = null): array
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new RuntimeException('Jenis transaksi saldo tidak valid.');
        }
        if (abs($amount) < 0.005) {
            throw new RuntimeException('Nominal transaksi saldo tidak boleh nol.');
        }

        self::ensure($memberId);

        // FOR UPDATE: kunci baris wallet sampai commit transaksi pemanggil,
        // mencegah dua transaksi bersamaan memakai saldo yang sama.
        $wallet = Database::first('SELECT * FROM member_wallets WHERE member_id = ? FOR UPDATE', [$memberId]);
        if ($wallet === null) {
            throw new RuntimeException('Wallet anggota tidak ditemukan.');
        }

        $before = (float) $wallet['balance'];
        $after = round($before + $amount, 2);
        if ($after < 0) {
            throw new RuntimeException(sprintf(
                'Saldo tidak mencukupi. Saldo: %s, Total: %s.',
                rupiah($before),
                rupiah(abs($amount))
            ));
        }

        $transactionNo = self::nextTransactionNo();
        Database::insert('wallet_transactions', [
            'member_id'      => $memberId,
            'transaction_no' => $transactionNo,
            'type'           => $type,
            'amount'         => round($amount, 2),
            'balance_before' => $before,
            'balance_after'  => $after,
            'description'    => mb_substr($description, 0, 255),
            'reference'      => $reference,
            'order_id'       => $orderId,
            'user_id'        => ($userId !== null && $userId > 0) ? $userId : null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        Database::exec(
            'UPDATE member_wallets SET balance = ?, updated_at = NOW() WHERE id = ?',
            [$after, (int) $wallet['id']]
        );

        return ['balance_before' => $before, 'balance_after' => $after, 'transaction_no' => $transactionNo];
    }

    /** Riwayat mutasi saldo satu anggota (terbaru dulu). */
    public static function ledger(int $memberId, int $limit = 50): array
    {
        return Database::all(
            'SELECT wt.*, u.full_name AS operator_name FROM wallet_transactions wt
             LEFT JOIN users u ON u.id = wt.user_id
             WHERE wt.member_id = ?
             ORDER BY wt.id DESC LIMIT ' . max(1, $limit),
            [$memberId]
        );
    }

    /** Pengajuan top up milik anggota (terbaru dulu). */
    public static function topupRequests(int $memberId, int $limit = 20): array
    {
        return Database::all(
            'SELECT * FROM topup_requests WHERE member_id = ? ORDER BY id DESC LIMIT ' . max(1, $limit),
            [$memberId]
        );
    }

    /** Pengajuan top up PENDING untuk dashboard kasir/admin. */
    public static function pendingTopups(int $limit = 10): array
    {
        return Database::all(
            'SELECT tr.*, m.member_no, m.full_name FROM topup_requests tr
             JOIN members m ON m.id = tr.member_id
             WHERE tr.status = ? ORDER BY tr.id ASC LIMIT ' . max(1, $limit),
            ['PENDING']
        );
    }

    public static function findTopup(int $requestId): ?array
    {
        return Database::first(
            'SELECT tr.*, m.member_no, m.full_name FROM topup_requests tr
             JOIN members m ON m.id = tr.member_id WHERE tr.id = ?',
            [$requestId]
        );
    }

    public static function createTopupRequest(int $memberId, float $amount, string $note, ?int $userId): int
    {
        if ($amount <= 0) {
            throw new RuntimeException('Nominal pengajuan harus lebih besar dari nol.');
        }

        return (int) Database::insert('topup_requests', [
            'member_id'   => $memberId,
            'request_no'  => self::nextNo('TOP', 'topup_requests', 'request_no'),
            'amount'      => round($amount, 2),
            'note'        => mb_substr($note, 0, 255) ?: null,
            'status'      => 'PENDING',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public static function findTopupByNo(string $requestNo): ?array
    {
        return Database::first('SELECT * FROM topup_requests WHERE request_no = ?', [$requestNo]);
    }

    /** Nomor transaksi saldo unik: SAL-YYYYMMDD-#### */
    public static function nextTransactionNo(): string
    {
        return self::nextNo('SAL', 'wallet_transactions', 'transaction_no');
    }

    /** Pola nomor seragam dengan modul lain: PREFIX-YYYYMMDD-#### */
    public static function nextNo(string $prefix, string $table, string $column): string
    {
        $like = $prefix . '-' . date('Ymd') . '-%';
        $max = (int) (Database::scalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX({$column}, '-', -1) AS UNSIGNED)), 0) FROM `{$table}` WHERE {$column} LIKE ?",
            [$like]
        ) ?? 0);

        return $prefix . '-' . date('Ymd') . '-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
