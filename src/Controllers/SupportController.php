<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Roles;
use App\Models\Member;
use App\Models\MemberPortal;
use App\Models\Notification;

/**
 * Sisi admin untuk komunikasi anggota (pesan masuk + balasan).
 */
final class SupportController extends Controller
{
    public function inbox(): void
    {
        Roles::requirePermission('support.view');
        $user = (array) Auth::user();

        $this->view('support/inbox', [
            'pageTitle'    => 'Pesan Anggota',
            'pageSubtitle' => 'Komunikasi anggota dengan pengurus',
            'inbox'        => MemberPortal::adminInbox(),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'support',
        ]);
    }

    public function thread(string $memberId): void
    {
        Roles::requirePermission('support.view');
        $user = (array) Auth::user();

        $member = Member::find((int) $memberId);
        if ($member === null) {
            flash_set('error', 'Anggota tidak ditemukan.');
            redirect('/support');
        }

        MemberPortal::markReadByAdmin((int) $memberId);

        $this->view('support/thread', [
            'pageTitle'    => 'Pesan: ' . $member['full_name'],
            'pageSubtitle' => $member['member_no'],
            'member'       => $member,
            'thread'       => MemberPortal::thread((int) $memberId),
            'finance'      => Member::finance((int) $memberId),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'support',
        ]);
    }

    public function reply(string $memberId): void
    {
        Roles::requirePermission('support.reply');
        Csrf::validate();

        $user = (array) Auth::user();
        $member = Member::find((int) $memberId);
        if ($member === null) {
            flash_set('error', 'Anggota tidak ditemukan.');
            redirect('/support');
        }

        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '') {
            flash_set('error', 'Balasan tidak boleh kosong.');
            redirect('/support/' . $memberId);
        }

        MemberPortal::sendMessage((int) $memberId, 'ADMIN', (int) $user['id'], (string) $user['full_name'], $body);
        Audit::log('CREATE', 'Balas pesan anggota ' . $member['member_no'], 'SUPPORT', (string) $memberId);

        Notification::push(
            'Admin membalas pesan Anda',
            'Balasan dari ' . $user['full_name'] . ' sudah terkirim.',
            'SUCCESS',
            '/portal/chat',
            ['user' => (int) ($member['user_id'] ?? 0)]
        );

        flash_set('success', 'Balasan terkirim.');
        redirect('/support/' . $memberId);
    }
}
