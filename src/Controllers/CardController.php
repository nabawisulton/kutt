<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Roles;
use App\Models\Card;
use App\Models\Member;

/**
 * Kartu anggota & QR verification.
 * QR hanya menyimpan token acak (bukan data keuangan).
 */
final class CardController extends Controller
{
    /** QR code PNG image for a member card token. */
    public function qr(string $token): void
    {
        // Token valid -> QR berisi URL verifikasi; token invalid tetap dibuat agar
        // tidak membocorkan validitas token via gambar.
        $url = base_url('/anggota/verify/' . rawurlencode($token));

        require_once BASE_PATH . '/vendor/phpqrcode/phpqrcode.php';
        \QRcode::png($url, false, QR_ECLEVEL_M, 6, 2);
        exit;
    }

    /** Public verification page opened after scanning the QR. */
    public function verify(string $token): void
    {
        $member = Card::memberByToken($token);

        if ($member === null) {
            http_response_code(404);
            $this->viewPlain('public/card_invalid', [
                'seoTitle' => 'Kartu Tidak Valid - KUTT SUKA MAKMUR',
            ]);
            return;
        }

        $user = Auth::user();
        // Keuangan: pengguna ber-permission finance.view ATAU anggota pemilik kartu sendiri.
        $isOwner = $user !== null && (int) ($member['user_id'] ?? 0) === (int) $user['id'];
        $canSeeFinance = $user !== null && (Roles::can($user['role'], 'finance.view') || $isOwner);

        $this->viewPlain('public/member_profile', [
            'seoTitle'  => $member['full_name'] . ' - Kartu Anggota KUTT SUKA MAKMUR',
            'seoDesc'   => 'Verifikasi identitas anggota KUTT SUKA MAKMUR.',
            'seoImage'  => $member['photo_path'] !== null ? base_url((string) $member['photo_path']) : null,
            'member'    => $member,
            'finance'   => $canSeeFinance ? Member::finance((int) $member['id']) : null,
            'transactions' => $canSeeFinance ? Member::recentTransactions((int) $member['id'], 8) : [],
            'canSeeFinance' => $canSeeFinance,
            'cardBg'    => Card::backgroundSettings(),
        ]);
    }

    /** QR scanner page (kamera via BarcodeDetector API + fallback manual). */
    public function scan(): void
    {
        Roles::requirePermission('member.view');

        $user = (array) Auth::user();
        $this->view('members/scan', [
            'pageTitle'    => 'Scan Anggota',
            'pageSubtitle' => 'Pindai QR kartu anggota menggunakan kamera',
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'members',
        ]);
    }
}
