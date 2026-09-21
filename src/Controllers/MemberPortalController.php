<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Roles;
use App\Core\Validator;
use App\Models\Member;
use App\Models\MemberPortal;
use App\Models\MemberWallet;
use App\Models\News;

/**
 * Portal Anggota (role ANGGOTA): saldo & SHU milik sendiri, pengajuan
 * pinjaman mandiri, dan komunikasi dengan admin. Data yang tampil SELALU
 * dibatasi ke member_id milik akun login.
 */
final class MemberPortalController extends Controller
{
    /** Anggota milik akun login; auto-link via email bila belum tertaut. */
    private function myMember(): ?array
    {
        $user = (array) Auth::user();
        $member = MemberPortal::memberForUser((int) $user['id']);
        if ($member !== null) {
            return $member;
        }

        // Fallback: tautkan otomatis dari email akun (sekali, aman).
        $byEmail = MemberPortal::memberByEmail((string) ($user['email'] ?? ''));
        if ($byEmail !== null) {
            MemberPortal::linkUser((int) $byEmail['id'], (int) $user['id']);

            return MemberPortal::memberForUser((int) $user['id']);
        }

        return null;
    }

    public function dashboard(): void
    {
        Roles::requirePermission('portal.view');
        $user = (array) Auth::user();
        $member = $this->myMember();

        if ($member === null) {
            $this->view('portal/not_linked', [
                'pageTitle'    => 'Portal Anggota',
                'pageSubtitle' => 'Akun belum tertaut ke data anggota',
                'allowedViews' => Roles::allowedViews($user['role']),
                'activeView'   => 'portal',
            ]);

            return;
        }

        $finance = Member::finance((int) $member['id']) ?? [];

        $this->view('portal/dashboard', [
            'pageTitle'    => 'Portal Anggota',
            'pageSubtitle' => $member['member_no'] . ' — ' . $member['full_name'],
            'member'       => $member,
            'finance'      => $finance,
            'myLoans'      => MemberPortal::myLoans((int) $member['id']),
            'schedule'     => MemberPortal::mySchedule((int) $member['id'], 6),
            'unread'       => MemberPortal::memberUnreadCount((int) $member['id']),
            'latestNews'   => News::publicList(null, '', 3),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'portal',
        ]);
    }

    public function submitLoan(): void
    {
        Roles::requirePermission('portal.loan_request');
        Csrf::validate();

        $member = $this->myMember();
        if ($member === null) {
            flash_set('error', 'Akun Anda belum tertaut ke data anggota.');
            redirect('/portal');
        }

        [$ok, $data, $errors] = Validator::check($_POST, [
            'pokok_pinjaman' => 'required|numeric',
            'tenor_bulan'    => 'required|numeric',
            'sistem_bunga'   => 'required|in:FLAT,MENURUN',
            'keperluan'      => 'required|max:255',
        ]);
        if (!$ok) {
            flash_set('error', 'Data belum lengkap: ' . (implode(', ', $errors) ?: 'periksa isian.'));
            redirect('/portal');
        }

        [$success, $msg] = MemberPortal::submitLoanRequest(
            (int) $member['id'],
            (float) $data['pokok_pinjaman'],
            (int) $data['tenor_bulan'],
            (string) $data['sistem_bunga'],
            (string) $data['keperluan']
        );

        flash_set($success ? 'success' : 'error', $success ? 'Pengajuan terkirim. No: ' . $msg . '. Admin akan memproses.' : $msg);
        redirect('/portal');
    }

    public function chat(): void
    {
        Roles::requirePermission('portal.chat');
        $user = (array) Auth::user();
        $member = $this->myMember();

        if ($member === null) {
            flash_set('error', 'Akun Anda belum tertaut ke data anggota.');
            redirect('/portal');
        }

        MemberPortal::markReadByMember((int) $member['id']);

        $this->view('portal/chat', [
            'pageTitle'    => 'Pesan ke Admin',
            'pageSubtitle' => 'Komunikasi dengan pengurus koperasi',
            'member'       => $member,
            'thread'       => MemberPortal::thread((int) $member['id']),
            'unread'       => MemberPortal::memberUnreadCount((int) $member['id']),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'portal_chat',
        ]);
    }

    public function sendChat(): void
    {
        Roles::requirePermission('portal.chat');
        Csrf::validate();

        $user = (array) Auth::user();
        $member = $this->myMember();
        if ($member === null) {
            flash_set('error', 'Akun Anda belum tertaut ke data anggota.');
            redirect('/portal');
        }

        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '') {
            flash_set('error', 'Pesan tidak boleh kosong.');
            redirect('/portal/chat');
        }

        MemberPortal::sendMessage((int) $member['id'], 'ANGGOTA', (int) $user['id'], (string) $user['full_name'], $body);

        \App\Models\Notification::push(
            'Pesan baru dari anggota',
            $user['full_name'] . ' (' . $member['member_no'] . ') mengirim pesan.',
            'INFO',
            '/support/' . $member['id'],
            ['role' => Roles::ADMIN]
        );

        flash_set('success', 'Pesan terkirim ke admin.');
        redirect('/portal/chat');
    }

    // ================================================== SALDO ANGGOTA (WALLET)

    /** Halaman wallet anggota: saldo, riwayat, PIN, dan pengajuan top up. */
    public function wallet(): void
    {
        Roles::requirePermission('portal.view');
        $user = (array) Auth::user();
        $member = $this->myMember();

        if ($member === null) {
            flash_set('error', 'Akun Anda belum tertaut ke data anggota.');
            redirect('/portal');
        }

        $memberId = (int) $member['id'];
        MemberWallet::ensure($memberId); // wallet saldo 0 otomatis bila belum ada

        $this->view('portal/wallet', [
            'pageTitle'    => 'Saldo & Transaksi Saya',
            'pageSubtitle' => $member['member_no'] . ' — ' . $member['full_name'],
            'member'       => $member,
            'balance'      => MemberWallet::balance($memberId),
            'hasPin'       => MemberWallet::hasPin($memberId),
            'ledger'       => MemberWallet::ledger($memberId, 30),
            'requests'     => MemberWallet::topupRequests($memberId, 10),
            'unread'       => MemberPortal::memberUnreadCount($memberId),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'portal_wallet',
        ]);
    }

    /** Buat / ubah PIN transaksi 6 digit (verifikasi PIN lama saat mengubah). */
    public function savePin(): void
    {
        Roles::requirePermission('portal.view');
        Csrf::validate();
        $member = $this->myMember();
        if ($member === null) {
            flash_set('error', 'Akun Anda belum tertaut ke data anggota.');
            redirect('/portal');
        }

        $current = trim((string) ($_POST['current_pin'] ?? ''));
        $new = trim((string) ($_POST['new_pin'] ?? ''));
        $confirm = trim((string) ($_POST['confirm_pin'] ?? ''));
        $hadPin = MemberWallet::hasPin((int) $member['id']);

        try {
            if ($new !== $confirm) {
                throw new \RuntimeException('Konfirmasi PIN tidak sama dengan PIN baru.');
            }
            MemberWallet::setPin((int) $member['id'], $new, $current !== '' ? $current : null);
        } catch (\RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect('/portal/wallet');
        }

        Audit::log('UPDATE', 'PIN transaksi ' . ($hadPin ? 'diperbarui' : 'dibuat') . ' untuk ' . $member['member_no'], 'SALDO', (string) $member['member_no']);
        flash_set('success', 'PIN transaksi berhasil disimpan.');
        redirect('/portal/wallet');
    }

    /** Ajukan top up dari portal (TIDAK langsung menambah saldo — diproses admin). */
    public function topupRequest(): void
    {
        Roles::requirePermission('portal.view');
        Csrf::validate();
        $user = (array) Auth::user();
        $member = $this->myMember();
        if ($member === null) {
            flash_set('error', 'Akun Anda belum tertaut ke data anggota.');
            redirect('/portal');
        }

        $amount = round((float) ($_POST['amount'] ?? 0), 2);
        $note = mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 255);

        try {
            if ($amount <= 0 || $amount > 100000000) {
                throw new \RuntimeException('Nominal pengajuan top up tidak valid.');
            }
            MemberWallet::verifyPin((int) $member['id'], trim((string) ($_POST['wallet_pin'] ?? '')));
            $id = MemberWallet::createTopupRequest((int) $member['id'], $amount, $note, (int) $user['id']);
            $request = MemberWallet::findTopup($id);
        } catch (\RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect('/portal/wallet');
        }

        \App\Models\Notification::push(
            'Pengajuan top up saldo baru',
            $member['full_name'] . ' (' . $member['member_no'] . ') mengajukan top up ' . rupiah($amount) . '.',
            'INFO',
            '/sales/pos',
            ['role' => Roles::ADMIN]
        );
        Audit::log('CREATE', 'Pengajuan top up ' . ($request['request_no'] ?? '-') . ' oleh ' . $member['member_no'] . ' sebesar ' . rupiah($amount), 'SALDO', (string) ($request['request_no'] ?? ''));
        flash_set('success', 'Pengajuan top up terkirim (' . ($request['request_no'] ?? '-') . '). Menunggu diproses admin — saldo belum bertambah.');
        redirect('/portal/wallet');
    }
}
