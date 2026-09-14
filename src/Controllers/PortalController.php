<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Portal;
use App\Models\Setting;

/**
 * Public landing page ("Portal Koperasi").
 * Markup is the preserved legacy GAS landing page (FIX, DON'T REDESIGN):
 * content now comes from the MySQL settings table via Portal::content().
 */
final class PortalController extends Controller
{
    public function index(): void
    {
        $this->viewPlain('public/portal', [
            'cms'     => Portal::content(),
            'brand'   => Setting::all(),
            'seoData' => [
                'title'       => 'Koperasi Digital KUTT Suka Makmur Grati',
                'description' => 'Sistem Informasi Koperasi Usaha Tani Ternak Suka Makmur Grati, Pasuruan - simpan pinjam, susu murni, pakan ternak, dan portal anggota digital.',
            ],
        ]);
    }
}
