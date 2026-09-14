<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Portal (landing page) content.
 * Values are stored in the `settings` table as JSON/text and merged over
 * the legacy defaults that shipped with the original GAS application, so
 * a fresh install shows exactly the old landing page.
 */
final class Portal
{
    /** @return array<string,mixed> full portal content (defaults + overrides) */
    public static function content(): array
    {
        $s = Setting::all();

        $get = static fn (string $key, string $default): string => isset($s[$key]) && $s[$key] !== '' ? $s[$key] : $default;

        return [
            // Hero
            'heroBadge'    => $get('heroBadge', 'Koperasi Peternak Sapi Perah Terpercaya Since 1978'),
            'heroTitle'    => $get('heroTitle', 'Mengabdi Untuk Peternak, Mensejahterakan Anggota.'),
            'heroSubtitle' => $get('heroSubtitle', 'Koperasi Usaha Tani Ternak (KUTT) Suka Makmur Grati bertransformasi secara digital untuk mengelola rantai pasok susu murni berkualitas tinggi, pembiayaan syariah, serta pakan ternak nutrisi prima.'),
            'heroImages'   => self::jsonList('portal_hero_images', [
                'https://images.unsplash.com/photo-1527153857715-3908f2bae5e8?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1560493676-04071c5f467b?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&w=1200&q=80',
                'https://images.unsplash.com/photo-1571212515416-fca88c2d1b30?auto=format&fit=crop&w=1200&q=80',
            ]),

            // Stats
            'statAnggota'    => $get('statAnggota', '1,248+'),
            'statSusu'       => $get('statSusu', '45.000 L'),
            'statPengalaman' => $get('statPengalaman', '45+ Thn'),

            // Profil
            'profileDesc' => $get('profileDesc', 'Berdiri sejak tahun 1978 di Kecamatan Grati, Kabupaten Pasuruan, KUTT Suka Makmur menjadi pelopor modernisasi peternakan sapi perah di Jawa Timur.'),
            'visiText'    => $get('visiText', 'Menjadi koperasi ternak perah terdepan nasional yang mandiri, berdaya saing tinggi, dan menyejahterakan seluruh anggota peternak berbasis teknologi digital.'),
            'misiText'    => $get('misiText', 'Meningkatkan mutu produksi susu murni, menjamin harga jual yang adil, memberikan bantuan permodalan terjangkau, serta pendampingan kesehatan hewan berkala.'),
            'nilaiText'   => $get('nilaiText', '<strong>Amanah:</strong> Menjaga kepercayaan anggota.<br><strong>Transparan:</strong> Pembukuan akuntansi terbuka.<br><strong>Gotong Royong:</strong> Tumbuh bersama peternak lokal.'),

            // Unit usaha
            'units' => self::jsonList('portal_units', [
                ['icon' => 'fa-solid fa-hand-holding-dollar', 'color' => 'emerald', 'title' => 'Simpan Pinjam', 'desc' => 'Simpanan pokok, wajib, sukarela, dan pinjaman modal usaha tani ternak.'],
                ['icon' => 'fa-solid fa-jug-detergent', 'color' => 'blue', 'title' => 'Pengolahan Susu', 'desc' => 'Penampungan dan pengolahan susu segar dari peternak anggota.'],
                ['icon' => 'fa-solid fa-wheat-awn', 'color' => 'amber', 'title' => 'Pakan Ternak', 'desc' => 'Penyediaan pakan ternak berkualitas dengan harga anggota.'],
                ['icon' => 'fa-solid fa-truck-medical', 'color' => 'rose', 'title' => 'Kesehatan Hewan', 'desc' => 'Pendampingan dan layanan kesehatan hewan ternak secara berkala.'],
            ]),

            // Katalog produk
            'products' => self::jsonList('portal_products', [
                ['nama' => 'Susu Segar Pasteurisasi', 'harga' => 'Rp 12.000 / Liter', 'desc' => 'Susu murni segar hasil pasteurisasi higienis.', 'gambar' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&w=500&q=80'],
                ['nama' => 'Yogurt Susu Sapi', 'harga' => 'Rp 15.000 / 250ml', 'desc' => 'Yogurt fermentasi alami tanpa bahan pengawet.', 'gambar' => 'https://images.unsplash.com/photo-1571212515416-fca88c2d1b30?auto=format&fit=crop&w=500&q=80'],
                ['nama' => 'Pakan Konsentrat', 'harga' => 'Rp 6.500 / Kg', 'desc' => 'Pakan konsentrat nutrisi tinggi untuk sapi perah.', 'gambar' => 'https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=500&q=80'],
            ]),

            // Galeri & video
            'gallery' => self::jsonList('portal_gallery', [
                ['title' => 'Pusat Penampungan Susu Grati', 'desc' => 'Fasilitas penampungan susu segar dengan sistem cold chain modern untuk menjaga kualitas susu dari peternak hingga distribusi.', 'gambar' => 'https://images.unsplash.com/photo-1527153857715-3908f2bae5e8?auto=format&fit=crop&w=800&q=80'],
                ['title' => 'Peternakan Sapi Perah Anggota', 'desc' => 'Aktivitas harian peternak anggota KUTT dalam merawat sapi perah dengan standar kesehatan hewan yang terjaga.', 'gambar' => 'https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=800&q=80'],
                ['title' => 'Proses Pengolahan Susu', 'desc' => 'Tahapan pasteurisasi dan pengemasan produk olahan susu di unit produksi koperasi.', 'gambar' => 'https://images.unsplash.com/photo-1560493676-04071c5f467b?auto=format&fit=crop&w=800&q=80'],
                ['title' => 'Produk Susu Siap Distribusi', 'desc' => 'Produk susu pasteurisasi KUTT Suka Makmur yang siap didistribusikan ke mitra dan konsumen.', 'gambar' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&w=800&q=80'],
                ['title' => 'Produk Olahan Yogurt', 'desc' => 'Diversifikasi produk olahan susu berupa yogurt fermentasi alami tanpa bahan pengawet.', 'gambar' => 'https://images.unsplash.com/photo-1571212515416-fca88c2d1b30?auto=format&fit=crop&w=800&q=80'],
            ]),
            'videos' => self::jsonList('portal_videos', []),

            // Kontak / footer
            'footerHourWeekday'  => $get('footerHourWeekday', '07.00 - 16.00 WIB'),
            'footerHourSaturday' => $get('footerHourSaturday', '07.00 - 13.00 WIB'),
            'mapLat'             => $get('mapLat', '-7.6800'),
            'mapLng'             => $get('mapLng', '113.0050'),
        ];
    }

    /**
     * Decode a JSON list setting; falls back to the given default when the
     * stored value is missing or corrupt (never breaks the landing page).
     *
     * @param list<array<string,mixed>>|list<string> $default
     * @return list<array<string,mixed>>|list<string>
     */
    private static function jsonList(string $key, array $default): array
    {
        $raw = Setting::get($key, '');
        if ($raw === '') {
            return $default;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : $default;
    }
}
