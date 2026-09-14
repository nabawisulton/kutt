<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;

/**
 * Base controller helpers shared by all controllers.
 */
abstract class Controller
{
    /** Render a view inside the dashboard layout. */
    protected function view(string $view, array $data = []): void
    {
        $data['currentUser']  = Auth::user();
        $data['csrfToken']    = Csrf::token();
        $data['flash']        = flash_take();

        echo View::layout($view, $data);
    }

    /** Render a standalone (no layout) view, e.g. login. */
    protected function viewPlain(string $view, array $data = []): void
    {
        $data['currentUser'] = Auth::user();
        $data['csrfToken']   = Csrf::token();
        $data['flash']       = flash_take();

        echo View::render($view, $data);
    }

    /** @never */
    protected function backWith(string $type, string $message): void
    {
        flash_set($type, $message);
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if (is_string($referer) && str_starts_with($referer, base_url())) {
            header('Location: ' . $referer);
        } else {
            redirect('/dashboard');
        }
        exit;
    }
}
