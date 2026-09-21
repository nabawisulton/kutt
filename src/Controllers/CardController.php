<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Roles;
use App\Models\Card;
use App\Models\Member;
use App\Models\MemberWallet;
use RuntimeException;

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
        $this->renderVerify($token);
    }

    /**
     * CEK SALDO dari hasil scan QR — wajib PIN 6 digit (QR hanya identifikasi).
     * Saldo TIDAK pernah tampil hanya dengan memegang URL/token.
     */
    public function verifySaldo(string $token): void
    {
        $member = $this->requireScannedMember($token);
        Csrf::validate();

        try {
            MemberWallet::verifyPin((int) $member['id'], trim((string) ($_POST['wallet_pin'] ?? '')));
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            $this->renderVerify($token);
            return;
        }

        $memberId = (int) $member['id'];
        Audit::log('VIEW', 'Cek saldo via scan QR oleh ' . $member['member_no'], 'SALDO', (string) $member['member_no']);
        $this->renderVerify($token, [
            'showBalance'   => true,
            'walletBalance' => MemberWallet::balance($memberId),
            'walletLedger'  => MemberWallet::ledger($memberId, 10),
        ]);
    }

    /**
     * RIWAYAT SALDO dari hasil scan QR — wajib PIN 6 digit.
     */
    public function verifyHistory(string $token): void
    {
        $member = $this->requireScannedMember($token);
        Csrf::validate();

        try {
            MemberWallet::verifyPin((int) $member['id'], trim((string) ($_POST['wallet_pin'] ?? '')));
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            $this->renderVerify($token);
            return;
        }

        $memberId = (int) $member['id'];
        Audit::log('VIEW', 'Riwayat saldo via scan QR oleh ' . $member['member_no'], 'SALDO', (string) $member['member_no']);
        $this->renderVerify($token, [
            'showHistory'   => true,
            'walletLedger'  => MemberWallet::ledger($memberId, 20),
        ]);
    }

    /**
     * AJUKAN TOP UP dari hasil scan QR — wajib PIN 6 digit.
     * TIDAK langsung menambah saldo: masuk sebagai pengajuan PENDING
     * yang diproses admin/kasir setelah uang diterima.
     */
    public function verifyTopup(string $token): void
    {
        $member = $this->requireScannedMember($token);
        Csrf::validate();

        $amount = round((float) ($_POST['amount'] ?? 0), 2);
        $note = mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 255);

        try {
            MemberWallet::verifyPin((int) $member['id'], trim((string) ($_POST['wallet_pin'] ?? '')));
            if ($amount <= 0 || $amount > 100000000) {
                throw new RuntimeException('Nominal pengajuan top up tidak valid.');
            }
            $requestId = MemberWallet::createTopupRequest((int) $member['id'], $amount, $note, null);
            $request = MemberWallet::findTopup($requestId);
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            $this->renderVerify($token);
            return;
        }

        \App\Models\Notification::push(
            'Pengajuan top up saldo baru',
            $member['full_name'] . ' (' . $member['member_no'] . ') mengajukan top up ' . rupiah($amount) . ' via scan QR.',
            'INFO',
            '/sales/pos',
            ['role' => Roles::ADMIN]
        );
        Audit::log('CREATE', 'Pengajuan top up via QR ' . ($request['request_no'] ?? '-') . ' oleh ' . $member['member_no'] . ' ' . rupiah($amount), 'SALDO', (string) ($request['request_no'] ?? ''));
        flash_set('success', 'Pengajuan top up terkirim (' . ($request['request_no'] ?? '-') . '). Saldo akan bertambah setelah admin memprosesnya.');
        $this->renderVerify($token);
    }

    /** Validasi token scan; render halaman invalid bila tidak dikenal. @never */
    private function requireScannedMember(string $token): array
    {
        $member = Card::memberByToken($token);
        if ($member === null) {
            http_response_code(404);
            $this->viewPlain('public/card_invalid', [
                'seoTitle' => 'Kartu Tidak Valid - KUTT SUKA MAKMUR',
            ]);
            exit;
        }

        return $member;
    }

    /** Render halaman verifikasi dengan opsional hasil aksi PIN (saldo/riwayat). */
    private function renderVerify(string $token, array $walletState = []): void
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
        $memberId = (int) $member['id'];
        MemberWallet::ensure($memberId); // wallet saldo 0 otomatis bila belum ada

        $this->viewPlain('public/member_profile', [
            'seoTitle'  => $member['full_name'] . ' - Kartu Anggota KUTT SUKA MAKMUR',
            'seoDesc'   => 'Verifikasi identitas anggota KUTT SUKA MAKMUR.',
            'seoImage'  => $member['photo_path'] !== null ? base_url((string) $member['photo_path']) : null,
            'member'    => $member,
            'finance'   => $canSeeFinance ? Member::finance($memberId) : null,
            'transactions' => $canSeeFinance ? Member::recentTransactions($memberId, 8) : [],
            'canSeeFinance' => $canSeeFinance,
            'cardBg'    => Card::backgroundSettings(),
            'cardToken' => $token,
            'hasPin'    => MemberWallet::hasPin($memberId),
        ] + $walletState);
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
