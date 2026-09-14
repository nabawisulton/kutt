<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Minimal but safe view renderer. Views live in src/views and receive
 * `$data` extracted into local variables. Output is buffered so layouts
 * can embed section content.
 */
final class View
{
    /**
     * Render a view file and return its output as a string.
     *
     * @param array<string,mixed> $data
     */
    public static function render(string $view, array $data = []): string
    {
        $file = BASE_PATH . '/src/views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View tidak ditemukan: {$view}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;

        return (string) ob_get_clean();
    }

    /**
     * Render a view inside the dashboard layout shell.
     *
     * @param array<string,mixed> $data
     */
    public static function layout(string $view, array $data = [], string $layout = 'layouts.app'): string
    {
        $content = self::render($view, $data);

        return self::render($layout, $data + ['content' => $content]);
    }
}
