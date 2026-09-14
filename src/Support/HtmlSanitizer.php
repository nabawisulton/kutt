<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Whitelist-based HTML sanitizer for news body content.
 * Removes script/style/iframe/event handlers/javascript: URLs, keeps
 * basic formatting tags. Also builds safe video embeds from URLs.
 */
final class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><blockquote><img><a><figure><figcaption><table><thead><tbody><tr><th><td>';

    public static function clean(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $html = strip_tags($html, self::ALLOWED_TAGS);

        // Remove inline event handlers (onclick, onerror, ...).
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';

        // Remove javascript:/vbscript: URLs.
        $html = preg_replace('/(href|src)\s*=\s*(["\']?)\s*(javascript|vbscript):[^"\'>\s]*\2/i', '$1="#"', $html) ?? '';

        // Force all links to open safely in a new tab.
        $html = preg_replace('/<a\s+/i', '<a rel="noopener noreferrer nofollow" target="_blank" ', $html) ?? '';

        return $html;
    }

    /** Safe embed for YouTube (embed URL) or plain link for other hosts. */
    public static function embedVideo(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        // YouTube watch / youtu.be -> embed.
        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})#', $url, $m)) {
            $src = 'https://www.youtube-nocookie.com/embed/' . $m[1];

            return sprintf(
                '<iframe src="%s" title="Video berita" class="w-full aspect-video rounded-xl mb-4" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>',
                e($src)
            );
        }

        // Any other URL: render as a safe link.
        return '<p class="mb-4"><a href="' . e($url) . '" rel="noopener noreferrer nofollow" target="_blank" class="text-brand-600 font-semibold hover:underline"><i class="fa-solid fa-film mr-1"></i>Lihat Video</a></p>';
    }
}
