<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Secure file upload service (Tahap berita & foto anggota).
 *
 * Proteksi:
 * - whitelist extension + MIME (finfo)
 * - batas ukuran
 * - nama file acak unik (tanpa input user)
 * - gambar di-re-encode ulang via GD (buang metadata/script yang disisipkan)
 * - direktori upload di luar root aplikasi namun tetap web-accessible
 */
final class Uploader
{
    private const IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public const MAX_IMAGE_BYTES = 3 * 1024 * 1024;  // 3 MB
    public const MAX_VIDEO_BYTES = 25 * 1024 * 1024; // 25 MB

    private const VIDEO_TYPES = [
        'video/mp4'       => 'mp4',
        'video/webm'      => 'webm',
        'video/quicktime' => 'mov',
    ];

    /**
     * Upload an image, re-encode it, and return the relative path
     * (e.g. "uploads/news/ab12cd.jpg") or null when no file supplied.
     *
     * @throws RuntimeException on validation failure
     */
    public static function image(?array $file, string $subdir, int $maxBytes = self::MAX_IMAGE_BYTES): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload gagal (kode error ' . $file['error'] . ').');
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('Ukuran file melebihi batas ' . round($maxBytes / 1048576) . ' MB.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        if (!isset(self::IMAGE_TYPES[$mime])) {
            throw new RuntimeException('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau WEBP.');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Upload tidak valid.');
        }

        $ext = self::IMAGE_TYPES[$mime];
        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        $dir = BASE_PATH . '/public/uploads/' . trim($subdir, '/');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Gagal menyiapkan direktori upload.');
        }

        $target = $dir . '/' . $name;

        // Re-encode: eliminates embedded payloads/metadata, normalizes format.
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => @imagecreatefrompng($file['tmp_name']),
            'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        };
        if ($img === false) {
            throw new RuntimeException('File gambar tidak dapat dibaca.');
        }

        $ok = match ($ext) {
            'jpg'  => imagejpeg($img, $target, 88),
            'png'  => imagepng($img, $target, 6),
            'webp' => imagewebp($img, $target, 88),
        };
        imagedestroy($img);

        if (!$ok) {
            throw new RuntimeException('Gagal memproses gambar.');
        }

        return 'uploads/' . trim($subdir, '/') . '/' . $name;
    }

    /**
     * Upload a video file (mp4/webm/mov) and return the relative path or null.
     *
     * @throws RuntimeException
     */
    public static function video(?array $file, string $subdir = 'news'): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload video gagal (kode error ' . $file['error'] . ').');
        }
        if (($file['size'] ?? 0) > self::MAX_VIDEO_BYTES) {
            throw new RuntimeException('Ukuran video melebihi batas 25 MB. Gunakan URL eksternal untuk video besar.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        if (!isset(self::VIDEO_TYPES[$mime]) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Tipe video tidak diizinkan. Gunakan MP4, WEBM, atau MOV.');
        }

        $ext = self::VIDEO_TYPES[$mime];
        $name = bin2hex(random_bytes(12)) . '.' . $ext;
        $dir = BASE_PATH . '/public/uploads/' . trim($subdir, '/');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Gagal menyiapkan direktori upload.');
        }

        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            throw new RuntimeException('Gagal menyimpan video.');
        }

        return 'uploads/' . trim($subdir, '/') . '/' . $name;
    }

    // --- Aset brand (logo, favicon, ikon PWA, gambar share OG) -------------

    /**
     * Upload logo koperasi: di-re-encode sebagai PNG dengan mempertahankan
     * transparansi (alpha) sehingga tetap pas di tile terang maupun gelap.
     * Gambar lebih besar dari $maxDim diturunkan skalanya (tanpa memotong).
     *
     * @throws RuntimeException
     */
    public static function brandImage(?array $file, string $subdir, int $maxDim = 512): ?string
    {
        $info = self::validatedImage($file);
        if ($info === null) {
            return null;
        }
        [$tmp, $mime] = $info;

        $img = self::scaleDown(self::decode($tmp, $mime), $maxDim);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        $dir = self::assetDir($subdir);
        $name = bin2hex(random_bytes(12)) . '.png';
        if (!imagepng($img, $dir . '/' . $name, 6)) {
            imagedestroy($img);
            throw new RuntimeException('Gagal memproses gambar.');
        }
        imagedestroy($img);

        return 'uploads/' . trim($subdir, '/') . '/' . $name;
    }

    /**
     * Upload SATU gambar sumber lalu generate paket ikon aplikasi lengkap
     * (ditulis langsung ke public/ agar dikenali crawler & browser):
     *  - favicon.ico           (16/32/48 px, kompatibel semua browser)
     *  - apple-touch-icon.png  (180 px, latar putih untuk perangkat Apple)
     *  - icon-192.png          (PWA)
     *  - icon-512.png          (PWA)
     *
     * @throws RuntimeException
     */
    public static function iconSet(?array $file): void
    {
        $info = self::validatedImage($file);
        if ($info === null) {
            return;
        }
        [$tmp, $mime] = $info;

        $src = self::square(self::scaleDown(self::decode($tmp, $mime), 512));
        if (min(imagesx($src), imagesy($src)) < 48) {
            imagedestroy($src);
            throw new RuntimeException('Gambar favicon terlalu kecil. Gunakan gambar minimal 48x48 piksel.');
        }

        self::writeFaviconIco($src);

        foreach ([192, 512] as $size) {
            $img = self::resample($src, $size, $size, false);
            imagealphablending($img, false);
            imagesavealpha($img, true);
            imagepng($img, BASE_PATH . '/public/icon-' . $size . '.png', 6);
            imagedestroy($img);
        }

        // Apple touch icon: flatten ke putih (iOS tidak merender transparansi).
        $apple = self::resample($src, 180, 180, false);
        $flat = imagecreatetruecolor(180, 180);
        imagefilledrectangle($flat, 0, 0, 180, 180, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $apple, 0, 0, 0, 0, 180, 180);
        imagedestroy($apple);
        imagepng($flat, BASE_PATH . '/public/apple-touch-icon.png', 6);
        imagedestroy($flat);

        imagedestroy($src);
    }

    /**
     * Upload gambar share (Open Graph) dan crop-cover tepat 1200x630
     * (rasio yang dirender WhatsApp/Facebook/Twitter).
     *
     * @throws RuntimeException
     */
    public static function ogCover(?array $file): ?string
    {
        $info = self::validatedImage($file);
        if ($info === null) {
            return null;
        }
        [$tmp, $mime] = $info;

        $img = self::resample(self::decode($tmp, $mime), 1200, 630, true);
        $dir = self::assetDir('branding');
        $name = bin2hex(random_bytes(12)) . '.jpg';
        if (!imagejpeg($img, $dir . '/' . $name, 88)) {
            imagedestroy($img);
            throw new RuntimeException('Gagal memproses gambar OG.');
        }
        imagedestroy($img);

        return 'uploads/branding/' . $name;
    }

    /**
     * Ambil 1 frame video (ffmpeg, bila tersedia) sebagai thumbnail berita.
     * Nama file poster deterministik dari nama video: aman dipanggil ulang.
     * Mengembalikan null bila video bukan file lokal atau ffmpeg tidak ada.
     */
    public static function videoPoster(?string $videoRelative): ?string
    {
        if ($videoRelative === null || $videoRelative === '' || str_starts_with($videoRelative, 'http')) {
            return null;
        }
        $full = BASE_PATH . '/public/' . ltrim($videoRelative, '/');
        if (!is_file($full)) {
            return null;
        }
        $bin = self::ffmpegBinary();
        if ($bin === null) {
            return null;
        }

        $dir = dirname($full);
        $name = pathinfo($full, PATHINFO_FILENAME) . '.poster.jpg';
        $target = $dir . '/' . $name;
        if (!is_file($target)) {
            $null = DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';
            @shell_exec(
                escapeshellarg($bin) . ' -y -ss 1 -i ' . escapeshellarg($full)
                . ' -frames:v 1 -vf scale=1200:-2 ' . escapeshellarg($target) . ' 2>' . $null
            );
        }

        return is_file($target) ? 'uploads/' . basename($dir) . '/' . $name : null;
    }

    /** @return array{0: string, 1: string}|null [tmp path, mime] atau null bila tidak ada file diupload. */
    private static function validatedImage(?array $file, int $maxBytes = self::MAX_IMAGE_BYTES): ?array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload gagal (kode error ' . $file['error'] . ').');
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('Ukuran file melebihi batas ' . round($maxBytes / 1048576) . ' MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        if (!isset(self::IMAGE_TYPES[$mime])) {
            throw new RuntimeException('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau WEBP.');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Upload tidak valid.');
        }

        return [$file['tmp_name'], $mime];
    }

    private static function decode(string $tmp, string $mime): \GdImage
    {
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png'  => @imagecreatefrompng($tmp),
            'image/webp' => @imagecreatefromwebp($tmp),
            default      => false,
        };
        if ($img === false) {
            throw new RuntimeException('File gambar tidak dapat dibaca.');
        }

        return $img;
    }

    private static function assetDir(string $subdir): string
    {
        $dir = BASE_PATH . '/public/uploads/' . trim($subdir, '/');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Gagal menyiapkan direktori upload.');
        }

        return $dir;
    }

    /** Turunkan skala bila sisi terbesar melebihi $maxDim (sumber tidak diubah). */
    private static function scaleDown(\GdImage $img, int $maxDim): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1.0, $maxDim / max($w, $h));
        if ($scale >= 1.0) {
            return $img;
        }

        return self::resample($img, (int) max(1, (int) round($w * $scale)), (int) max(1, (int) round($h * $scale)), false);
    }

    /**
     * Resample ke ukuran persis. $cover=true = crop tengah (untuk OG);
     * $cover=false = fit + canvas transparan (untuk logo/ikon).
     * Sumber TIDAK dihancurkan di sini.
     */
    private static function resample(\GdImage $src, int $w, int $h, bool $cover): \GdImage
    {
        $dst = imagecreatetruecolor($w, $h);
        if ($cover) {
            $sw = imagesx($src);
            $sh = imagesy($src);
            $scale = max($w / $sw, $h / $sh);
            $nw = (int) max(1, (int) round($sw * $scale));
            $nh = (int) max(1, (int) round($sh * $scale));
            $tmp = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($tmp, $src, 0, 0, 0, 0, $nw, $nh, $sw, $sh);
            imagecopy($dst, $tmp, (int) (($w - $nw) / 2), (int) (($h - $nh) / 2), 0, 0, $nw, $nh);
            imagedestroy($tmp);

            return $dst;
        }

        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefilledrectangle($dst, 0, 0, $w, $h, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));

        return $dst;
    }

    /** Jadikan persegi (fit + transparan) agar logo tidak gepeng saat jadi ikon. */
    private static function square(\GdImage $src): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w === $h) {
            return $src;
        }
        $side = max($w, $h);
        $dst = imagecreatetruecolor($side, $side);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefilledrectangle($dst, 0, 0, $side, $side, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopy($dst, $src, (int) (($side - $w) / 2), (int) (($side - $h) / 2), 0, 0, $w, $h);
        imagedestroy($src);

        return $dst;
    }

    /** Tulis public/favicon.ico: ICO berisi BMP 16/32/48 px (32-bit alpha). */
    private static function writeFaviconIco(\GdImage $sq): void
    {
        $entries = [];
        foreach ([16, 32, 48] as $size) {
            $img = imagesx($sq) === $size ? $sq : self::resample($sq, $size, $size, false);
            $entries[] = ['w' => $size, 'bmp' => self::bmpEntry($img)];
            if (imagesx($sq) !== $size) {
                imagedestroy($img);
            }
        }

        $data = pack('vvv', 0, 1, count($entries));
        $offset = 6 + 16 * count($entries);
        foreach ($entries as $entry) {
            // Direktori dulu (offset absolut dihitung dari tata letak ini),
            // lalu semua data BMP — format ICO melarang interleave.
            $data .= pack('CCCCvvVV', $entry['w'], $entry['w'], 0, 0, 1, 32, strlen($entry['bmp']), $offset);
            $offset += strlen($entry['bmp']);
        }
        foreach ($entries as $entry) {
            $data .= $entry['bmp'];
        }
        file_put_contents(BASE_PATH . '/public/favicon.ico', $data);
    }

    /** Satu entri BMP (BITMAPINFOHEADER + piksel BGRA bottom-up + AND mask). */
    private static function bmpEntry(\GdImage $img): string
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $andRow = (int) (ceil($w / 32) * 4);
        $andSize = $andRow * $h;

        $header = pack('VvvVVVVVVVV', 40, $w, $h * 2, 1, 32, 0, $w * $h * 4 + $andSize, 0, 0, 0, 0);
        $xor = str_repeat("\x00", $w * $h * 4);
        // AND mask: bit = 1 untuk piksel transparan (renderer lama yang tidak
        // membaca kanal alpha tetap menampilkan bentuk logo dengan benar).
        $and = str_repeat("\x00", $andSize);
        $i = 0;
        for ($y = $h - 1; $y >= 0; $y--) {
            $andRowIdx = $h - 1 - $y; // bottom-up: baris gambar y ada di baris AND ke h-1-y
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $a7 = ($rgb >> 24) & 0x7F; // GD: 0 = opak, 127 = transparan
                $a8 = (int) round(255 - ($a7 * 255 / 127));
                // GD truecolor: byte tertinggi = alpha, lalu R, G, B (terendah).
                // BMP BGRA bottom-up: byte 0 = B, 1 = G, 2 = R, 3 = A.
                $xor[$i++] = chr($rgb & 0xFF);         // B
                $xor[$i++] = chr(($rgb >> 8) & 0xFF);  // G
                $xor[$i++] = chr(($rgb >> 16) & 0xFF); // R
                $xor[$i++] = chr($a8);                 // A
                if ($a8 === 0) {
                    $byteIdx = $andRowIdx * $andRow + (int) ($x / 8);
                    $and[$byteIdx] = chr(ord($and[$byteIdx]) | (0x80 >> ($x % 8)));
                }
            }
        }

        return $header . $xor . $and;
    }

    private static function ffmpegBinary(): ?string
    {
        if (!function_exists('shell_exec')) {
            return null;
        }
        foreach (['ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'] as $bin) {
            $out = @shell_exec('command -v ' . escapeshellarg($bin) . ' 2>/dev/null');
            if (is_string($out) && trim($out) !== '') {
                return $bin;
            }
        }

        return null;
    }

    /** Delete an uploaded file inside public/uploads (path traversal safe). */
    public static function delete(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        $full = BASE_PATH . '/public/' . ltrim($relativePath, '/');
        $realBase = realpath(BASE_PATH . '/public');
        $realFile = realpath($full);
        // Compare with trailing separator: prevents a sibling directory such as
        // "public_extra" from passing a "public" prefix check.
        if ($realFile !== false && $realBase !== false && str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR) && is_file($realFile)) {
            @unlink($realFile);
        }
    }
}
