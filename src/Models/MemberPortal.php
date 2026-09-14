<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Audit;
use App\Core\Database;
use App\Core\Roles;

/**
 * Portal Anggota: penghubung akun login (role ANGGOTA) dengan data anggota,
 * pengajuan pinjaman mandiri, dan pesan dua arah dengan admin.
 */
final class MemberPortal
{
    /** Ambil baris anggota milik akun login ini (null bila belum tertaut). */
    public static function memberForUser(int $userId): ?array
    {
        return Database::first(
            'SELECT * FROM members WHERE user_id = ? LIMIT 1',
            [$userId]
        );
    }

    /** Tautkan akun user ANGGOTA ke data anggota (sekali saja). */
    public static function linkUser(int $memberId, int $userId): void
    {
        Database::exec('UPDATE members SET user_id = ? WHERE id = ?', [$userId, $memberId]);
    }

    /** Cari anggota berdasarkan email akun (fallback penautan otomatis). */
    public static function memberByEmail(string $email): ?array
    {
        if ($email === '') {
            return null;
        }

        return Database::first(
            'SELECT * FROM members WHERE email = ? AND user_id IS NULL ORDER BY id ASC LIMIT 1',
            [$email]
        );
    }

    /**
     * Pengajuan pinjaman oleh anggota (selalu PENDING + notifikasi admin).
     * @return array{0: bool, 1: string} [sukses, pesan/loan_no]
     */
    public static function submitLoanRequest(int $memberId, float $pokok, int $tenor, string $sistemBunga, string $keperluan): array
    {
        if ($pokok <= 0 || $pokok > 200_000_000 || $tenor < 1 || $tenor > 60) {
            return [false, 'Plafon (maks Rp200.000.000) atau tenor (1-60 bulan) tidak valid.'];
        }
        if (!in_array($sistemBunga, ['FLAT', 'MENURUN'], true)) {
            return [false, 'Sistem bunga tidak valid.'];
        }
        if (trim($keperluan) === '') {
            return [false, 'Keperluan pinjaman wajib diisi.'];
        }

        // Maksimal 1 pengajuan PENDING per anggota.
        $pending = (int) Database::scalar(
            "SELECT COUNT(*) FROM loans WHERE member_id = ? AND status = 'PENDING'",
            [$memberId]
        );
        if ($pending > 0) {
            return [false, 'Anda masih memiliki pengajuan yang menunggu persetujuan.'];
        }

        $loanNo = self::nextLoanNo();
        Database::insert('loans', [
            'loan_no'            => $loanNo,
            'member_id'          => $memberId,
            'pokok_pinjaman'     => $pokok,
            'bunga_pertahun'     => 12.00,
            'tenor_bulan'        => $tenor,
            'sistem_bunga'       => $sistemBunga,
            'status'             => 'PENDING',
            'tanggal_pengajuan'  => date('Y-m-d'),
            'keperluan'          => mb_substr(trim($keperluan), 0, 255),
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        $member = Member::find($memberId);
        Notification::push(
            'Pengajuan pinjaman baru dari anggota',
            ($member['full_name'] ?? 'Anggota') . ' mengajukan ' . $loanNo . ' sebesar ' . rupiah($pokok) . '.',
            'WARNING',
            '/pinjaman',
            ['role' => Roles::ADMIN]
        );
        Audit::log('CREATE', 'Pengajuan pinjaman mandiri ' . $loanNo, 'PORTAL', $loanNo);

        return [true, $loanNo];
    }

    /** Daftar pengajuan milik satu anggota (terbaru dulu). */
    public static function myLoans(int $memberId, int $limit = 10): array
    {
        return Database::all(
            'SELECT id, loan_no, pokok_pinjaman, tenor_bulan, sistem_bunga, status,
                    tanggal_pengajuan, keperluan
             FROM loans WHERE member_id = ?
             ORDER BY id DESC LIMIT ' . max(1, $limit),
            [$memberId]
        );
    }

    /** Jadwal angsuran milik satu anggota (gabungan semua pinjaman). */
    public static function mySchedule(int $memberId, int $limit = 12): array
    {
        return Database::all(
            'SELECT ls.*, l.loan_no, (ls.pokok_due + ls.bunga_due) AS total_bayar
             FROM loan_schedules ls
             JOIN loans l ON l.id = ls.loan_id
             WHERE l.member_id = ?
             ORDER BY ls.loan_id DESC, ls.angsuran_ke ASC
             LIMIT ' . max(1, $limit),
            [$memberId]
        );
    }

    // --- Komunikasi anggota <-> admin ---------------------------------------

    public static function sendMessage(int $memberId, string $senderRole, ?int $senderUser, string $senderName, string $body): void
    {
        Database::insert('support_messages', [
            'member_id'   => $memberId,
            'sender_role' => $senderRole === 'ADMIN' ? 'ADMIN' : 'ANGGOTA',
            'sender_user' => $senderUser,
            'sender_name' => mb_substr($senderName, 0, 120),
            'body'        => mb_substr(trim($body), 0, 2000),
            'is_read_by_admin'  => $senderRole === 'ADMIN' ? 1 : 0,
            'is_read_by_member' => $senderRole === 'ADMIN' ? 0 : 1,
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    /** Seluruh percakapan satu anggota (thread). */
    public static function thread(int $memberId, int $limit = 100): array
    {
        return Database::all(
            'SELECT * FROM support_messages WHERE member_id = ?
             ORDER BY id ASC LIMIT ' . max(1, $limit),
            [$memberId]
        );
    }

    /** Inbox admin: daftar anggota yang punya pesan + jumlah belum dibaca. */
    public static function adminInbox(int $limit = 50): array
    {
        $limit = max(1, $limit);

        return Database::all(<<<SQL
            SELECT m.id AS member_id, m.member_no, m.full_name, m.photo_path,
                    MAX(s.created_at) AS last_at,
                    SUM(CASE WHEN s.sender_role = 'ANGGOTA' AND s.is_read_by_admin = 0 THEN 1 ELSE 0 END) AS unread
             FROM support_messages s
             JOIN members m ON m.id = s.member_id
             GROUP BY m.id, m.member_no, m.full_name, m.photo_path
             ORDER BY last_at DESC
             LIMIT {$limit}
            SQL);
    }

    public static function markReadByAdmin(int $memberId): void
    {
        Database::exec(
            "UPDATE support_messages SET is_read_by_admin = 1 WHERE member_id = ? AND sender_role = 'ANGGOTA'",
            [$memberId]
        );
    }

    public static function markReadByMember(int $memberId): void
    {
        Database::exec(
            "UPDATE support_messages SET is_read_by_member = 1 WHERE member_id = ? AND sender_role = 'ADMIN'",
            [$memberId]
        );
    }

    /** Total pesan anggota belum dibaca admin (badge navbar). */
    public static function adminUnreadCount(): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM support_messages WHERE sender_role = 'ANGGOTA' AND is_read_by_admin = 0"
        );
    }

    /** Pesan admin belum dibaca anggota (badge portal). */
    public static function memberUnreadCount(int $memberId): int
    {
        return (int) Database::scalar(
            "SELECT COUNT(*) FROM support_messages WHERE member_id = ? AND sender_role = 'ADMIN' AND is_read_by_member = 0",
            [$memberId]
        );
    }

    /** Nomor pinjaman unik PJN-YYYYMMDD-NNNN (sama dengan FinController). */
    private static function nextLoanNo(): string
    {
        $datePart = date('Ymd');
        $like = 'PJN-' . $datePart . '-%';
        $rows = Database::all(
            'SELECT loan_no FROM loans WHERE loan_no LIKE ?',
            [$like]
        );
        $max = 0;
        foreach ($rows as $row) {
            $parts = explode('-', (string) $row['loan_no']);
            if (count($parts) >= 3 && ctype_digit($parts[2])) {
                $max = max($max, (int) $parts[2]);
            }
        }

        return 'PJN-' . $datePart . '-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
