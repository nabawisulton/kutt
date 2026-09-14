<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Roles;
use App\Models\Notification;
use App\Models\Portal;
use App\Models\Setting;
use App\Support\Uploader;
use RuntimeException;

/**
 * CMS Landing Page: kelola konten portal publik (hero, profil, unit usaha,
 * galeri, video, produk, kontak). Konten disimpan di tabel `settings`.
 */
final class CmsController extends Controller
{
    public function index(): void
    {
        Roles::requirePermission('cms');
        $user = (array) Auth::user();

        $this->view('cms/index', [
            'pageTitle'    => 'CMS Landing Page',
            'pageSubtitle' => 'Kelola konten portal publik KUTT Suka Makmur',
            'cms'          => Portal::content(),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'cms',
        ]);
    }

    /** Simpan bagian teks portal (hero, profil, kontak). */
    public function saveText(): void
    {
        Roles::requirePermission('cms');
        Csrf::validate();
        $userId = (int) (Auth::user()['id'] ?? 0);

        $textKeys = [
            'heroBadge', 'heroTitle', 'heroSubtitle',
            'statAnggota', 'statSusu', 'statPengalaman',
            'profileDesc', 'visiText', 'misiText', 'nilaiText',
            'footerHourWeekday', 'footerHourSaturday', 'mapLat', 'mapLng',
        ];

        foreach ($textKeys as $key) {
            if (isset($_POST[$key])) {
                Setting::set($key, trim((string) $_POST[$key]), $userId);
            }
        }

        // Hero images: daftar URL, satu per baris (maks 7, sesuai legacy).
        if (isset($_POST['heroImages'])) {
            $urls = array_values(array_filter(array_map(
                static fn (string $line): string => filter_var(trim($line), FILTER_VALIDATE_URL) ? trim($line) : '',
                explode("\n", (string) $_POST['heroImages'])
            )));
            Setting::set('portal_hero_images', (string) json_encode(array_slice($urls, 0, 7)), $userId);
        }

        Audit::log('UPDATE', 'Konten portal (teks) diperbarui', 'CMS');
        Notification::push('Konten portal diperbarui', 'Bagian teks landing page diperbarui oleh admin.', 'INFO', '/', ['role' => Roles::ADMIN]);
        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/cms');
    }

    /** Simpan unit usaha (JSON dari editor kartu). */
    public function saveUnits(): void
    {
        Roles::requirePermission('cms');
        Csrf::validate();
        $userId = (int) (Auth::user()['id'] ?? 0);

        $units = $this->decodeJsonList('units_payload');
        if ($units === null) {
            flash_set('error', 'Terjadi kesalahan. Silakan coba kembali.');
            redirect('/cms');
        }

        Setting::set('portal_units', (string) json_encode(array_slice($units, 0, 12), JSON_UNESCAPED_SLASHES), $userId);
        Audit::log('UPDATE', 'Unit usaha portal diperbarui (' . count($units) . ' item)', 'CMS');
        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/cms#layanan');
    }

    /** Simpan galeri (upload file atau URL). */
    public function saveGallery(): void
    {
        Roles::requirePermission('cms');
        Csrf::validate();
        $userId = (int) (Auth::user()['id'] ?? 0);

        $items = $this->decodeJsonList('gallery_payload');
        if ($items === null) {
            flash_set('error', 'Terjadi kesalahan. Silakan coba kembali.');
            redirect('/cms');
        }

        // Upload file opsional per item (field gallery_file_0..N).
        try {
            foreach ($items as $i => $item) {
                $file = $_FILES['gallery_file_' . $i] ?? null;
                if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $path = Uploader::image($file, 'cms');
                    if ($path !== null) {
                        $items[$i]['gambar'] = base_url('/uploads/' . ltrim($path, '/'));
                    }
                }
            }
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect('/cms');
        }

        Setting::set('portal_gallery', (string) json_encode(array_slice($items, 0, 24), JSON_UNESCAPED_SLASHES), $userId);
        Audit::log('UPDATE', 'Galeri portal diperbarui (' . count($items) . ' item)', 'CMS');
        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/cms#galeri');
    }

    /** Simpan video YouTube (videoId + judul + deskripsi). */
    public function saveVideos(): void
    {
        Roles::requirePermission('cms');
        Csrf::validate();
        $userId = (int) (Auth::user()['id'] ?? 0);

        $items = $this->decodeJsonList('videos_payload');
        if ($items === null) {
            flash_set('error', 'Terjadi kesalahan. Silakan coba kembali.');
            redirect('/cms');
        }

        $items = array_values(array_filter($items, static fn (array $v): bool => $v['videoId'] ?? '' !== ''));
        Setting::set('portal_videos', (string) json_encode(array_slice($items, 0, 24), JSON_UNESCAPED_SLASHES), $userId);
        Audit::log('UPDATE', 'Video portal diperbarui (' . count($items) . ' item)', 'CMS');
        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/cms#video');
    }

    /** Simpan katalog produk. */
    public function saveProducts(): void
    {
        Roles::requirePermission('cms');
        Csrf::validate();
        $userId = (int) (Auth::user()['id'] ?? 0);

        $items = $this->decodeJsonList('products_payload');
        if ($items === null) {
            flash_set('error', 'Terjadi kesalahan. Silakan coba kembali.');
            redirect('/cms');
        }

        Setting::set('portal_products', (string) json_encode(array_slice($items, 0, 12), JSON_UNESCAPED_SLASHES), $userId);
        Audit::log('UPDATE', 'Katalog produk portal diperbarui (' . count($items) . ' item)', 'CMS');
        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/cms#produk');
    }

    /**
     * Decode a JSON payload posted from the editors; returns null on corrupt
     * input. Each item is normalized to an array with only string keys.
     *
     * @return list<array<string,string>>|null
     */
    private function decodeJsonList(string $field): ?array
    {
        $raw = (string) ($_POST[$field] ?? '');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $out = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $row = [];
            foreach ($item as $k => $v) {
                $row[(string) $k] = is_scalar($v) ? trim((string) $v) : '';
            }
            $out[] = $row;
        }

        return $out;
    }
}
