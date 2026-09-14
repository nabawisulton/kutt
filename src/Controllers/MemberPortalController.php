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
}
