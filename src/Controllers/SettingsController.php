<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Roles;
use App\Models\Card;
use App\Models\Setting;
use App\Support\Uploader;
use RuntimeException;

/**
 * Pengaturan aplikasi (CMS + kartu anggota).
 */
final class SettingsController extends Controller
{
    public function index(): void
    {
        Roles::requirePermission('settings.manage');

        $user = (array) Auth::user();
        $this->view('settings/index', [
            'pageTitle'    => 'Pengaturan Aplikasi',
            'pageSubtitle' => 'Identitas koperasi, tema, dan kartu anggota',
            'settings'     => Setting::all(),
            'cardBg'       => Card::backgroundSettings(),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'settings',
        ]);
    }

    public function saveGeneral(): void
    {
        Roles::requirePermission('settings.manage');
        Csrf::validate();

        $user = (array) Auth::user();
        $keys = ['brandName', 'subBrand', 'logoIcon', 'colorPrimary', 'colorDark', 'colorGold', 'footerAddress', 'footerPhone', 'footerCopyright'];

        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $value = trim((string) $_POST[$key]);
                // Color keys are injected into CSS variables in the layout -
                // enforce the hex format instead of trusting the client.
                if (str_starts_with($key, 'color') && preg_match('/^#[0-9a-fA-F]{6}$/', $value) !== 1) {
                    continue;
                }
                Setting::set($key, $value, (int) $user['id']);
            }
        }

        Audit::log('UPDATE', 'Pengaturan umum/CMS diperbarui', 'SETTINGS');
        \App\Models\Notification::push('Pengaturan diperbarui', 'Konfigurasi umum aplikasi diubah.', 'INFO', '/settings', ['role' => \App\Core\Roles::ADMIN]);
        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/settings');
    }

    public function saveCardBackground(): void
    {
        Roles::requirePermission('settings.manage');
        Csrf::validate();

        $user = (array) Auth::user();

        $type = ($_POST['card_bg_type'] ?? 'color') === 'image' ? 'image' : 'color';
        $color = (string) ($_POST['card_bg_color'] ?? '#0b7a3e');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#0b7a3e';
        }
        $opacity = (float) ($_POST['card_bg_opacity'] ?? 0.25);
        $opacity = max(0.0, min(1.0, $opacity));

        $payload = [
            'card_bg_type'    => $type,
            'card_bg_color'   => $color,
            'card_bg_opacity' => (string) $opacity,
        ];

        if ($type === 'image') {
            try {
                $imagePath = Uploader::image($_FILES['card_bg_image'] ?? null, 'cards');
                if ($imagePath !== null) {
                    Uploader::delete(Setting::get('card_bg_image', '') ?? '');
                    $payload['card_bg_image'] = $imagePath;
                } elseif (($_POST['keep_existing_image'] ?? '') === '1') {
                    $payload['card_bg_image'] = (string) (Setting::get('card_bg_image', '') ?? '');
                } else {
                    $payload['card_bg_image'] = (string) (Setting::get('card_bg_image', '') ?? '');
                }
            } catch (RuntimeException $e) {
                flash_set('error', $e->getMessage());
                redirect('/settings');
            }

            if (($payload['card_bg_image'] ?? '') === '') {
                flash_set('error', 'Gambar background wajib diunggah saat tipe gambar dipilih.');
                redirect('/settings');
            }
        }

        Card::saveBackgroundSettings($payload, (int) $user['id']);

        // --- Sisi belakang kartu (opsional) ---
        $backEnabled = ($_POST['card_back_enabled'] ?? '0') === '1' ? '1' : '0';
        $backPayload = ['card_back_enabled' => $backEnabled];

        if ($backEnabled === '1') {
            $backType = ($_POST['card_back_bg_type'] ?? 'color') === 'image' ? 'image' : 'color';
            $backColor = (string) ($_POST['card_back_bg_color'] ?? '#052e1a');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $backColor)) {
                $backColor = '#052e1a';
            }
            $backOpacity = max(0.0, min(1.0, (float) ($_POST['card_back_bg_opacity'] ?? 0.35)));
            $backPayload['card_back_bg_type'] = $backType;
            $backPayload['card_back_bg_color'] = $backColor;
            $backPayload['card_back_bg_opacity'] = (string) $backOpacity;

            if ($backType === 'image') {
                try {
                    $backImagePath = Uploader::image($_FILES['card_back_bg_image'] ?? null, 'cards');
                    if ($backImagePath !== null) {
                        Uploader::delete(Setting::get('card_back_bg_image', '') ?? '');
                        $backPayload['card_back_bg_image'] = $backImagePath;
                    } else {
                        $backPayload['card_back_bg_image'] = (string) (Setting::get('card_back_bg_image', '') ?? '');
                    }
                } catch (RuntimeException $e) {
                    flash_set('error', $e->getMessage());
                    redirect('/settings');
                }

                if (($backPayload['card_back_bg_image'] ?? '') === '') {
                    flash_set('error', 'Gambar background sisi belakang wajib diunggah saat tipe gambar dipilih.');
                    redirect('/settings');
                }
            }
        }

        Card::saveBackgroundSettings($backPayload, (int) $user['id']);
        Audit::log('UPDATE', 'Background kartu anggota diperbarui', 'SETTINGS');
        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/settings');
    }
}
