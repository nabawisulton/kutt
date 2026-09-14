<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Role & permission layer. Mirrors the legacy KUTT roles:
 * SUPER_ADMIN, ADMIN, BENDAHARA, KETUA, STAFF, ANGGOTA.
 *
 * Two layers:
 *  - view permissions (dashboard sidebar / page guards), and
 *  - granular permissions (news.*, member.*, finance.*, report.*, settings.*, ...)
 */
final class Roles
{
    public const SUPER_ADMIN = 'SUPER_ADMIN';
    public const ADMIN       = 'ADMIN';
    public const BENDAHARA   = 'BENDAHARA';
    public const KETUA       = 'KETUA';
    public const STAFF       = 'STAFF';
    public const ANGGOTA     = 'ANGGOTA';

    public const ALL = [
        self::SUPER_ADMIN,
        self::ADMIN,
        self::BENDAHARA,
        self::KETUA,
        self::STAFF,
        self::ANGGOTA,
    ];

    /** Page-level permissions. */
    private const VIEW_PERMISSIONS = [
        'dashboard' => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::KETUA, self::STAFF],
        'members'   => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::KETUA, self::STAFF],
        'simpanan'  => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::STAFF],
        'pinjaman'  => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::KETUA, self::STAFF],
        'kas'       => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::STAFF],
        'akuntansi' => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA],
        'shu'       => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::KETUA],
        'cms'       => [self::SUPER_ADMIN, self::ADMIN],
        'settings'  => [self::SUPER_ADMIN, self::ADMIN],
        'users'     => [self::SUPER_ADMIN],
        'news'      => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::KETUA, self::STAFF],
        'activity'  => [self::SUPER_ADMIN, self::ADMIN],
        'portal'        => [self::ANGGOTA],
        'portal_chat'   => [self::ANGGOTA],
        'support'       => [self::SUPER_ADMIN, self::ADMIN],
    ];

    /** Granular permissions per role. */
    private const PERMISSIONS = [
        'news.view'      => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::KETUA, self::STAFF],
        'news.create'    => [self::SUPER_ADMIN, self::ADMIN, self::STAFF],
        'news.edit'      => [self::SUPER_ADMIN, self::ADMIN, self::STAFF],
        'news.delete'    => [self::SUPER_ADMIN, self::ADMIN],
        'news.publish'   => [self::SUPER_ADMIN, self::ADMIN],

        'member.view'    => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::KETUA, self::STAFF],
        'member.create'  => [self::SUPER_ADMIN, self::ADMIN, self::STAFF],
        'member.edit'    => [self::SUPER_ADMIN, self::ADMIN, self::STAFF],
        'member.delete'  => [self::SUPER_ADMIN, self::ADMIN],

        'finance.view'   => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::KETUA, self::STAFF],
        'finance.create' => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::STAFF],
        'finance.edit'   => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA],
        'finance.export' => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA],

        'report.view'    => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::KETUA, self::STAFF],
        'report.print'   => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA, self::KETUA, self::STAFF],
        'report.export'  => [self::SUPER_ADMIN, self::ADMIN, self::BENDAHARA],

        'settings.manage'    => [self::SUPER_ADMIN, self::ADMIN],
        'activity_log.view'  => [self::SUPER_ADMIN, self::ADMIN],
        'notification.view'  => self::ALL,

        // Portal anggota (role ANGGOTA): data miliknya sendiri + komunikasi.
        'portal.view'        => [self::ANGGOTA],
        'portal.loan_request'=> [self::ANGGOTA],
        'portal.chat'        => [self::ANGGOTA],
        // Sisi admin: memantau & membalas pesan anggota.
        'support.view'       => [self::SUPER_ADMIN, self::ADMIN],
        'support.reply'      => [self::SUPER_ADMIN, self::ADMIN, self::STAFF],
    ];

    public static function can(string $role, string $permission): bool
    {
        // Legacy view id -> also allow via granular map.
        if (isset(self::VIEW_PERMISSIONS[$permission])) {
            return in_array($role, self::VIEW_PERMISSIONS[$permission], true);
        }

        return in_array($role, self::PERMISSIONS[$permission] ?? [], true);
    }

    /** Views visible in the sidebar for a given role. */
    public static function allowedViews(string $role): array
    {
        $views = [];
        foreach (array_keys(self::VIEW_PERMISSIONS) as $view) {
            if (self::can($role, $view)) {
                $views[] = $view;
            }
        }

        return $views;
    }

    /** Server-side guard: is the current user allowed to open a view? */
    public static function canView(string $view): bool
    {
        $user = Auth::user();

        return $user !== null && self::can($user['role'], $view);
    }

    /** Guard a granular permission; redirects with flash when denied. */
    public static function requirePermission(string $permission): void
    {
        Auth::requireLogin();
        $user = (array) Auth::user();
        if (!self::can($user['role'], $permission)) {
            flash_set('error', 'Akses ditolak: Anda tidak memiliki izin untuk aksi ini.');
            redirect('/dashboard');
        }
    }
}
