<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * News (portal berita) model with engagement statistics.
 */
final class News
{
    public static function categories(): array
    {
        return Database::all('SELECT * FROM news_categories ORDER BY name');
    }

    /** Admin list with search/filter + pagination. */
    public static function paginate(array $filters, int $page = 1, int $perPage = 10): array
    {
        $where = ['1=1'];
        $params = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(n.title LIKE ? OR n.excerpt LIKE ?)';
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'n.status = ?';
            $params[] = $filters['status'];
        }
        if (($filters['category'] ?? '') !== '') {
            $where[] = 'n.category_id = ?';
            $params[] = (int) $filters['category'];
        }
        if (($filters['author'] ?? '') !== '') {
            $where[] = 'n.author_name LIKE ?';
            $params[] = '%' . $filters['author'] . '%';
        }
        if (($filters['date'] ?? '') !== '') {
            $where[] = 'DATE(n.created_at) = ?';
            $params[] = $filters['date'];
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) Database::scalar(
            "SELECT COUNT(*) FROM news_posts n WHERE {$whereSql}",
            $params
        );
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $rows = Database::all(
            "SELECT n.*, c.name AS category_name
             FROM news_posts n
             LEFT JOIN news_categories c ON c.id = n.category_id
             WHERE {$whereSql}
             ORDER BY n.created_at DESC, n.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /** Public list: published only. */
    public static function publicList(?int $categoryId, string $q = '', int $limit = 12, int $offset = 0): array
    {
        [$whereSql, $params] = self::publicWhere($categoryId, $q);

        return Database::all(
            'SELECT n.*, c.name AS category_name
             FROM news_posts n
             LEFT JOIN news_categories c ON c.id = n.category_id
             WHERE ' . $whereSql . '
             ORDER BY n.published_at DESC, n.id DESC
             LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    /** Jumlah total berita published (untuk pagination portal publik). */
    public static function publicCount(?int $categoryId, string $q = ''): int
    {
        [$whereSql, $params] = self::publicWhere($categoryId, $q);

        return (int) Database::scalar(
            'SELECT COUNT(*) FROM news_posts n WHERE ' . $whereSql,
            $params
        );
    }

    /** Berita terpopuler (views tertinggi) untuk sidebar portal. */
    public static function mostViewed(int $limit = 5): array
    {
        return Database::all(
            'SELECT n.id, n.slug, n.title, n.views, n.image_path, n.published_at, c.name AS category_name
             FROM news_posts n
             LEFT JOIN news_categories c ON c.id = n.category_id
             WHERE n.status = \'PUBLISHED\'
             ORDER BY n.views DESC, n.id DESC
             LIMIT ' . max(1, $limit)
        );
    }

    /** @return array{0: string, 1: list<mixed>} WHERE clause + params untuk portal publik. */
    private static function publicWhere(?int $categoryId, string $q): array
    {
        $where = ["n.status = 'PUBLISHED'"];
        $params = [];
        if ($categoryId !== null) {
            $where[] = 'n.category_id = ?';
            $params[] = $categoryId;
        }
        if ($q !== '') {
            $where[] = '(n.title LIKE ? OR n.excerpt LIKE ?)';
            $params[] = "%{$q}%";
            $params[] = "%{$q}%";
        }

        return [implode(' AND ', $where), $params];
    }

    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT n.*, c.name AS category_name
             FROM news_posts n
             LEFT JOIN news_categories c ON c.id = n.category_id
             WHERE n.id = ?',
            [$id]
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::first(
            'SELECT n.*, c.name AS category_name
             FROM news_posts n
             LEFT JOIN news_categories c ON c.id = n.category_id
             WHERE n.slug = ?',
            [$slug]
        );
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        return Database::insert('news_posts', $data);
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, array $data): void
    {
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            $sets[] = "`{$col}` = ?";
            $params[] = $val;
        }
        $params[] = $id;
        Database::exec('UPDATE news_posts SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    public static function delete(int $id): void
    {
        Database::exec('DELETE FROM news_posts WHERE id = ?', [$id]);
    }

    public static function syncTags(int $postId, array $tagNames): void
    {
        Database::exec('DELETE FROM news_post_tags WHERE post_id = ?', [$postId]);
        foreach (array_slice(array_unique(array_filter($tagNames)), 0, 10) as $name) {
            $slug = self::slugify($name);
            $tag = Database::first('SELECT id FROM news_tags WHERE slug = ?', [$slug]);
            $tagId = $tag['id'] ?? Database::insert('news_tags', ['name' => $name, 'slug' => $slug]);
            Database::exec(
                'INSERT IGNORE INTO news_post_tags (post_id, tag_id) VALUES (?, ?)',
                [$postId, $tagId]
            );
        }
    }

    public static function tagsFor(int $postId): array
    {
        return Database::all(
            'SELECT t.name FROM news_tags t
             JOIN news_post_tags pt ON pt.tag_id = t.id
             WHERE pt.post_id = ?',
            [$postId]
        );
    }

    // --- Engagement -------------------------------------------------------

    public static function recordView(int $postId, string $userHash): void
    {
        // Dedupe: one view per browser hash per day (matching the comment on the
        // caller) and keeps news_views from growing unbounded on hot posts.
        $seen = Database::scalar(
            'SELECT COUNT(*) FROM news_views
             WHERE post_id = ? AND user_hash = ? AND viewed_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)',
            [$postId, $userHash]
        );
        if ((int) $seen > 0) {
            return;
        }

        Database::exec('INSERT INTO news_views (post_id, user_hash) VALUES (?, ?)', [$postId, $userHash]);
        Database::exec('UPDATE news_posts SET views = views + 1 WHERE id = ?', [$postId]);
    }

    /** @return array{liked: bool, likes: int} */
    public static function toggleLike(int $postId, string $userHash): array
    {
        $existing = Database::first(
            'SELECT id FROM news_likes WHERE post_id = ? AND user_hash = ?',
            [$postId, $userHash]
        );

        if ($existing !== null) {
            Database::exec('DELETE FROM news_likes WHERE id = ?', [$existing['id']]);
            Database::exec('UPDATE news_posts SET likes = GREATEST(likes - 1, 0) WHERE id = ?', [$postId]);
            $liked = false;
        } else {
            Database::exec('INSERT IGNORE INTO news_likes (post_id, user_hash) VALUES (?, ?)', [$postId, $userHash]);
            Database::exec('UPDATE news_posts SET likes = likes + 1 WHERE id = ?', [$postId]);
            $liked = true;
        }

        $likes = (int) Database::scalar('SELECT likes FROM news_posts WHERE id = ?', [$postId]);

        return ['liked' => $liked, 'likes' => $likes];
    }

    public static function recordShare(int $postId, string $platform): void
    {
        Database::exec('INSERT INTO news_shares (post_id, platform) VALUES (?, ?)', [$postId, $platform]);
        Database::exec('UPDATE news_posts SET shares = shares + 1 WHERE id = ?', [$postId]);
    }

    public static function addComment(int $postId, string $name, string $body, string $ip): int
    {
        $id = Database::insert('news_comments', [
            'post_id' => $postId,
            'name'    => $name,
            'body'    => $body,
            'ip'      => $ip,
            'status'  => 'PENDING',
        ]);
        self::refreshCommentCount($postId);

        return $id;
    }

    public static function approvedComments(int $postId): array
    {
        return Database::all(
            "SELECT * FROM news_comments WHERE post_id = ? AND status = 'APPROVED' ORDER BY created_at ASC",
            [$postId]
        );
    }

    public static function allComments(int $postId): array
    {
        return Database::all(
            'SELECT * FROM news_comments WHERE post_id = ? ORDER BY created_at DESC',
            [$postId]
        );
    }

    public static function setCommentStatus(int $commentId, string $status): ?int
    {
        Database::exec('UPDATE news_comments SET status = ? WHERE id = ?', [$status, $commentId]);
        $comment = Database::first('SELECT post_id FROM news_comments WHERE id = ?', [$commentId]);

        if ($comment === null) {
            return null;
        }
        self::refreshCommentCount((int) $comment['post_id']);

        return (int) $comment['post_id'];
    }

    public static function deleteComment(int $commentId): ?int
    {
        $comment = Database::first('SELECT post_id FROM news_comments WHERE id = ?', [$commentId]);
        if ($comment === null) {
            return null;
        }
        Database::exec('DELETE FROM news_comments WHERE id = ?', [$commentId]);
        self::refreshCommentCount((int) $comment['post_id']);

        return (int) $comment['post_id'];
    }

    public static function refreshCommentCount(int $postId): void
    {
        Database::exec(
            "UPDATE news_posts SET comments_count = (SELECT COUNT(*) FROM news_comments WHERE post_id = ? AND status = 'APPROVED') WHERE id = ?",
            [$postId, $postId]
        );
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text !== '' ? $text : 'berita-' . bin2hex(random_bytes(3));
    }

    /** Unique slug for create/update. */
    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = self::slugify($base);
        $attempt = 1;
        while (true) {
            $row = Database::first('SELECT id FROM news_posts WHERE slug = ?', [$slug]);
            if ($row === null || (int) $row['id'] === $ignoreId) {
                return $slug;
            }
            $slug = self::slugify($base) . '-' . (++$attempt);
        }
    }
}
