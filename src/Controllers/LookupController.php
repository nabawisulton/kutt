<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Roles;
use App\Models\Card;

/**
 * JSON lookup untuk halaman Scan Anggota (kamera / input manual).
 * Hanya mengekspos field identitas dasar - bukan data keuangan.
 */
final class LookupController extends Controller
{
    public function member(): void
    {
        Roles::requirePermission('member.view');

        $code = trim((string) ($_GET['code'] ?? ''));
        if ($code === '') {
            header('Content-Type: application/json');
            echo json_encode(['found' => false]);
            exit;
        }

        // 1) Coba sebagai token QR (32 hex) 2) fallback sebagai nomor anggota.
        $member = strlen($code) === 32 && ctype_xdigit($code)
            ? Card::memberByToken($code)
            : null;

        if ($member === null) {
            $row = \App\Core\Database::first(
                'SELECT id, member_no, full_name, status FROM members WHERE member_no = ? LIMIT 1',
                [$code]
            );
            $member = $row;
        }

        header('Content-Type: application/json');
        if ($member === null) {
            echo json_encode(['found' => false]);
            exit;
        }

        echo json_encode([
            'found'     => true,
            'id'        => (int) $member['id'],
            'member_no' => (string) $member['member_no'],
            'full_name' => (string) $member['full_name'],
            'status'    => (string) $member['status'],
        ]);
        exit;
    }
}
