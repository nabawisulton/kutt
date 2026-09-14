<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Roles;
use App\Core\Validator;
use App\Models\News;
use App\Models\Notification;
use App\Support\ExcelExport;
use App\Support\Uploader;
use RuntimeException;

/**
 * Admin news management (portal berita).
 */
final class NewsController extends Controller
{
    public function index(): void
    {
        Roles::requirePermission('news.view');

        $user = (array) Auth::user();
        $filters = [
            'q'        => trim((string) ($_GET['q'] ?? '')),
            'status'   => (string) ($_GET['status'] ?? ''),
            'category' => (string) ($_GET['category'] ?? ''),
            'author'   => trim((string) ($_GET['author'] ?? '')),
            'date'     => (string) ($_GET['date'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $result = News::paginate($filters, $page, 10);

        $this->view('news/index', [
            'pageTitle'    => 'Portal Berita',
            'pageSubtitle' => 'Kelola berita koperasi: draft, publish, komentar & statistik',
            'result'       => $result,
            'filters'      => $filters,
            'categories'   => News::categories(),
            'currentUser'  => $user,
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'news',
        ]);
    }

    public function createForm(): void
    {
        Roles::requirePermission('news.create');

        $user = (array) Auth::user();
        $this->view('news/form', [
            'pageTitle'    => 'Tambah Berita',
            'pageSubtitle' => 'Buat berita baru untuk portal publik',
            'post'         => null,
            'postTags'     => [],
            'categories'   => News::categories(),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'news',
        ]);
    }

    public function editForm(string $id): void
    {
        Roles::requirePermission('news.edit');

        $user = (array) Auth::user();
        $post = News::find((int) $id);
        if ($post === null) {
            flash_set('error', 'Berita tidak ditemukan.');
            redirect('/news');
        }

        $this->view('news/form', [
            'pageTitle'    => 'Edit Berita',
            'pageSubtitle' => 'Perbarui konten berita',
            'post'         => $post,
            'postTags'     => array_column(News::tagsFor((int) $id), 'name'),
            'categories'   => News::categories(),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'news',
        ]);
    }

    public function save(): void
    {
        Roles::requirePermission('news.create');
        Csrf::validate();

        $user = (array) Auth::user();

        [$ok, $data, $errors] = Validator::check($_POST, [
            'title'       => 'required|max:200',
            'excerpt'     => 'max:500',
            'body'        => 'max:60000',
            'video_url'   => 'max:255',
            'image_url'   => 'max:500',
            'category_id' => 'max:20',
            'status'      => 'required|in:DRAFT,PUBLISHED',
        ]);

        if (!$ok) {
            flash_set('error', 'Data belum lengkap: ' . (implode(', ', $errors) ?: 'periksa isian.'));
            flash_old_input($_POST);
            redirect('/news/create');
        }

        // Optional image upload (re-encoded by Uploader).
        try {
            $imagePath = Uploader::image($_FILES['image'] ?? null, 'news');
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            flash_old_input($_POST);
            redirect('/news/create');
        }

        // Optional video upload.
        try {
            $videoPath = Uploader::video($_FILES['video'] ?? null, 'news');
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            flash_old_input($_POST);
            redirect('/news/create');
        }

        $publish = $data['status'] === 'PUBLISHED';
        $categoryId = ($data['category_id'] ?? '') !== '' ? (int) $data['category_id'] : null;

        $id = News::create([
            'slug'        => News::uniqueSlug((string) $data['title']),
            'title'       => $data['title'],
            'excerpt'     => $data['excerpt'] !== '' ? $data['excerpt'] : mb_substr(strip_tags((string) $data['body']), 0, 200),
            'body'        => $data['body'] !== '' ? $data['body'] : null,
            'image_path'  => $imagePath ?? ($data['image_url'] ?? null),
            'video_url'   => $videoPath ?? (($data['video_url'] ?? '') !== '' ? $data['video_url'] : null),
            'category_id' => $categoryId,
            'author_id'   => (int) $user['id'],
            'author_name' => (string) $user['full_name'],
            'status'      => $data['status'],
            'published_at' => $publish ? date('Y-m-d H:i:s') : null,
        ]);

        News::syncTags($id, array_map('trim', explode(',', (string) ($_POST['tags'] ?? ''))));

        Audit::log('CREATE', 'Berita dibuat: ' . $data['title'], 'NEWS', (string) $id);
        Notification::push(
            'Berita baru: ' . $data['title'],
            'Berita telah dibuat oleh ' . $user['full_name'],
            $publish ? 'SUCCESS' : 'INFO',
            '/news',
            ['role' => null]
        );

        flash_set('success', 'Data berhasil disimpan.');
        redirect('/news');
    }

    public function update(string $id): void
    {
        Roles::requirePermission('news.edit');
        Csrf::validate();

        $postId = (int) $id;
        $post = News::find($postId);
        if ($post === null) {
            flash_set('error', 'Berita tidak ditemukan.');
            redirect('/news');
        }

        [$ok, $data, $errors] = Validator::check($_POST, [
            'title'       => 'required|max:200',
            'excerpt'     => 'max:500',
            'body'        => 'max:60000',
            'video_url'   => 'max:255',
            'image_url'   => 'max:500',
            'category_id' => 'max:20',
            'status'      => 'required|in:DRAFT,PUBLISHED',
        ]);

        if (!$ok) {
            flash_set('error', 'Data belum lengkap.');
            flash_old_input($_POST);
            redirect('/news/edit/' . $postId);
        }

        try {
            $imagePath = Uploader::image($_FILES['image'] ?? null, 'news');
            $videoPath = Uploader::video($_FILES['video'] ?? null, 'news');
        } catch (RuntimeException $e) {
            flash_set('error', $e->getMessage());
            redirect('/news/edit/' . $postId);
        }

        $publish = $data['status'] === 'PUBLISHED';
        $categoryId = ($data['category_id'] ?? '') !== '' ? (int) $data['category_id'] : null;

        $payload = [
            'title'       => $data['title'],
            'excerpt'     => $data['excerpt'] !== '' ? $data['excerpt'] : mb_substr(strip_tags((string) $data['body']), 0, 200),
            'body'        => $data['body'] !== '' ? $data['body'] : null,
            'video_url'   => $videoPath ?? (($data['video_url'] ?? '') !== '' ? $data['video_url'] : $post['video_url']),
            'category_id' => $categoryId,
            'status'      => $data['status'],
        ];

        $newImage = $imagePath ?? (($data['image_url'] ?? '') !== '' ? $data['image_url'] : null);
        $oldImage = (string) ($post['image_path'] ?? '');
        if ($newImage !== null && $newImage !== $oldImage && str_starts_with($oldImage, 'uploads/')) {
            Uploader::delete($oldImage);
        }
        if ($newImage !== null) {
            $payload['image_path'] = $newImage;
        }
        if ($publish && $post['status'] !== 'PUBLISHED') {
            $payload['published_at'] = date('Y-m-d H:i:s');
        } elseif (!$publish && $post['status'] === 'PUBLISHED') {
            $payload['published_at'] = null;
        }

        News::update($postId, $payload);
        News::syncTags($postId, array_map('trim', explode(',', (string) ($_POST['tags'] ?? ''))));

        Audit::log('UPDATE', 'Berita diperbarui: ' . $data['title'], 'NEWS', (string) $postId);
        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/news');
    }

    public function publish(string $id): void
    {
        Roles::requirePermission('news.publish');
        Csrf::validate();

        News::update((int) $id, [
            'status'       => 'PUBLISHED',
            'published_at' => date('Y-m-d H:i:s'),
        ]);
        Audit::log('UPDATE', 'Berita dipublikasikan', 'NEWS', $id);
        Notification::push('Berita dipublikasikan', 'Berita baru telah tayang di portal.', 'SUCCESS', '/news', ['role' => null]);

        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/news');
    }

    public function unpublish(string $id): void
    {
        Roles::requirePermission('news.publish');
        Csrf::validate();

        News::update((int) $id, ['status' => 'DRAFT']);
        Audit::log('UPDATE', 'Berita dijadikan draft', 'NEWS', $id);
        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/news');
    }

    public function destroy(string $id): void
    {
        Roles::requirePermission('news.delete');
        Csrf::validate();

        $post = News::find((int) $id);
        if ($post !== null) {
            Uploader::delete((string) $post['image_path']);
            News::delete((int) $id);
            Audit::log('DELETE', 'Berita dihapus: ' . $post['title'], 'NEWS', $id);
            Notification::push('Berita dihapus', 'Berita "' . $post['title'] . '" telah dihapus.', 'WARNING', '/news', ['role' => null]);
        }

        flash_set('success', 'Data berhasil dihapus.');
        redirect('/news');
    }

    // --- Comment moderation -------------------------------------------------

    public function comments(string $id): void
    {
        Roles::requirePermission('news.edit');

        $user = (array) Auth::user();
        $post = News::find((int) $id);
        if ($post === null) {
            flash_set('error', 'Berita tidak ditemukan.');
            redirect('/news');
        }

        $this->view('news/comments', [
            'pageTitle'    => 'Moderasi Komentar',
            'pageSubtitle' => $post['title'],
            'post'         => $post,
            'comments'     => News::allComments((int) $id),
            'allowedViews' => Roles::allowedViews($user['role']),
            'activeView'   => 'news',
        ]);
    }

    public function commentStatus(string $id): void
    {
        Roles::requirePermission('news.edit');
        Csrf::validate();

        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, ['PENDING', 'APPROVED', 'HIDDEN'], true)) {
            flash_set('error', 'Status tidak valid.');
            redirect('/news');
        }

        $postId = News::setCommentStatus((int) $id, $status);
        Audit::log('UPDATE', "Komentar #{$id} -> {$status}", 'NEWS', $postId !== null ? (string) $postId : null);
        flash_set('success', 'Data berhasil diperbarui.');
        redirect('/news/comments/' . ($postId ?? 0));
    }

    public function commentDelete(string $id): void
    {
        Roles::requirePermission('news.delete');
        Csrf::validate();

        $postId = News::deleteComment((int) $id);
        Audit::log('DELETE', "Komentar #{$id} dihapus", 'NEWS', $postId !== null ? (string) $postId : null);
        flash_set('success', 'Data berhasil dihapus.');
        redirect('/news/comments/' . ($postId ?? 0));
    }

    // --- Exports ------------------------------------------------------------

    public function exportExcel(): void
    {
        Roles::requirePermission('report.export');

        $rows = News::paginate([
            'q'        => trim((string) ($_GET['q'] ?? '')),
            'status'   => (string) ($_GET['status'] ?? ''),
            'category' => (string) ($_GET['category'] ?? ''),
            'author'   => trim((string) ($_GET['author'] ?? '')),
            'date'     => (string) ($_GET['date'] ?? ''),
        ], 1, 1000)['rows'];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                $r['id'], $r['title'], $r['category_name'] ?? '-', $r['author_name'],
                $r['status'], $r['views'], $r['likes'], $r['shares'], $r['comments_count'],
                (string) $r['created_at'],
            ];
        }

        Audit::log('EXPORT', 'Export Excel daftar berita', 'NEWS');
        ExcelExport::download('berita-kutt', 'Daftar Berita',
            ['ID', 'Judul', 'Kategori', 'Penulis', 'Status', 'Views', 'Likes', 'Shares', 'Komentar', 'Dibuat'],
            $out);
    }
}
