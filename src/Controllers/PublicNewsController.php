<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Validator;
use App\Models\News;

/**
 * Public news portal (no login required) with Open Graph metadata.
 */
final class PublicNewsController extends Controller
{
    private const OG_SITE = 'KUTT SUKA MAKMUR';

    /**
     * Gambar share utama sebuah berita: gambar unggahan/URL -> thumbnail
     * frame video lokal (ffmpeg) -> fallback portal. Selalu URL absolut.
     */
    private static function ogImageFor(array $post): ?string
    {
        if (!empty($post['image_path'])) {
            return news_og_image($post['image_path']);
        }

        $poster = \App\Support\Uploader::videoPoster($post['video_url'] ?? null);
        if ($poster !== null) {
            return news_og_image($poster);
        }

        $fallback = (string) \App\Models\Setting::get('ogImagePath', '');
        if ($fallback !== '') {
            return base_url('/' . $fallback);
        }

        return null;
    }

    /** URL og:video bila video tersimpan lokal (mp4/webm). */
    private static function ogVideoFor(array $post): ?string
    {
        $video = (string) ($post['video_url'] ?? '');
        if ($video === '' || !str_contains($video, 'uploads/')) {
            return null;
        }

        return base_url('/' . ltrim($video, '/'));
    }

    public function index(): void
    {
        $categoryId = ($_GET['kategori'] ?? '') !== '' ? (int) $_GET['kategori'] : null;
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 9;

        $total = News::publicCount($categoryId, $q);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $posts = News::publicList($categoryId, $q, $perPage, ($page - 1) * $perPage);
        $featured = $q === '' && $categoryId === null && $page === 1
            ? array_slice(News::publicList(null, '', 5), 0, 5)
            : [];

        $this->viewPlain('public/news', [
            'seoTitle'     => 'Berita - ' . self::OG_SITE,
            'seoDesc'      => 'Berita dan kegiatan terbaru KUTT SUKA MAKMUR Grati, Pasuruan.',
            'seoImage'     => isset($posts[0]) ? self::ogImageFor($posts[0]) : null,
            'posts'        => $posts,
            'featured'     => $featured,
            'popular'      => News::mostViewed(5),
            'categories'   => News::categories(),
            'activeCategory' => $categoryId,
            'q'            => $q,
            'page'         => $page,
            'pages'        => $pages,
            'total'        => $total,
        ]);
    }

    /** Feed JSON berita terbaru untuk slider halaman berita (maks 10). */
    public function feed(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $posts = News::publicList(null, '', 10);
        $out = [];
        foreach ($posts as $p) {
            $out[] = [
                'slug'     => (string) $p['slug'],
                'title'    => (string) $p['title'],
                'excerpt'  => mb_substr(strip_tags((string) ($p['excerpt'] ?: $p['body'])), 0, 160),
                'image'    => news_image_src($p['image_path'] ?? null),
                'category' => (string) ($p['category_name'] ?? 'Umum'),
                'date'     => tanggal((string) ($p['published_at'] ?? $p['created_at'])),
                'views'    => (int) $p['views'],
            ];
        }
        echo json_encode(['posts' => $out]);
        exit;
    }

    public function detail(string $slug): void
    {
        $post = News::findBySlug($slug);
        if ($post === null || $post['status'] !== 'PUBLISHED') {
            http_response_code(404);
            $this->viewPlain('public/news', [
                'seoTitle'   => 'Berita tidak ditemukan - ' . self::OG_SITE,
                'seoDesc'    => 'Berita tidak ditemukan.',
                'seoImage'   => null,
                'posts'      => [],
                'categories' => News::categories(),
                'activeCategory' => null,
                'q'          => '',
            ]);
            return;
        }

        // Count a view (one per browser per day).
        $hash = md5(($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        News::recordView((int) $post['id'], $hash);

        $url = base_url('/berita/' . rawurlencode((string) $post['slug']));

        $this->viewPlain('public/news_detail', [
            'seoTitle'  => (string) $post['title'] . ' - ' . self::OG_SITE,
            'seoDesc'   => (string) ($post['excerpt'] ?: mb_substr(strip_tags((string) $post['body']), 0, 200)),
            'seoImage'  => self::ogImageFor($post),
            'seoVideo'  => self::ogVideoFor($post),
            'seoUrl'    => $url,
            'post'      => $post,
            'tags'      => News::tagsFor((int) $post['id']),
            'comments'  => News::approvedComments((int) $post['id']),
            'related'   => News::publicList($post['category_id'] !== null ? (int) $post['category_id'] : null, '', 4),
        ]);
    }

    public function like(string $id): void
    {
        Csrf::validate();
        $result = News::toggleLike((int) $id, $this->userHash());
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function share(string $id): void
    {
        Csrf::validate();
        $platform = mb_substr((string) ($_POST['platform'] ?? 'link'), 0, 30);
        News::recordShare((int) $id, $platform);
        http_response_code(204);
        exit;
    }

    public function comment(string $id): void
    {
        Csrf::validate();

        [$ok, $data, $errors] = Validator::check($_POST, [
            'name' => 'required|max:120',
            'body' => 'required|max:1000',
        ]);

        if (!$ok) {
            flash_set('error', 'Data belum lengkap: nama dan komentar wajib diisi.');
            redirect('/berita/' . ($_POST['slug'] ?? ''));
        }

        // Naive anti-spam: same IP cannot post more than 5 comments/minute.
        $recent = (int) \App\Core\Database::scalar(
            "SELECT COUNT(*) FROM news_comments WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)",
            [mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45)]
        );
        if ($recent >= 5) {
            flash_set('error', 'Terlalu banyak komentar. Silakan tunggu sebentar.');
            redirect('/berita/' . ($_POST['slug'] ?? ''));
        }

        News::addComment(
            (int) $id,
            (string) $data['name'],
            (string) $data['body'],
            mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45)
        );

        \App\Models\Notification::push(
            'Komentar baru menunggu moderasi',
            'Komentar pada berita #' . $id . ' menunggu persetujuan.',
            'INFO',
            '/news/comments/' . $id,
            ['role' => \App\Core\Roles::ADMIN]
        );

        flash_set('success', 'Komentar terkirim dan menunggu moderasi. Terima kasih!');
        redirect('/berita/' . ($_POST['slug'] ?? ''));
    }

    private function userHash(): string
    {
        return md5(($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }
}
