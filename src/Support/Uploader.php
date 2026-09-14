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
