<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal method+path router with optional named parameters.
 * "{id}" matches [^/]+ and is passed to the controller action in order.
 */
final class Router
{
    /** @var array<string,array<string,array{0:class-string,1:string,2:string[]}>> */
    private array $routes = [];

    public function get(string $path, string $controller, string $action): void
    {
        $this->map('GET', $path, $controller, $action);
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->map('POST', $path, $controller, $action);
    }

    private function map(string $method, string $path, string $controller, string $action): void
    {
        $params = [];
        if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $path, $m)) {
            $params = $m[1];
        }
        $this->routes[$method][$path] = [$controller, $action, $params];
    }

    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        if ($scriptDir !== '/' && $scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }
        $path = '/' . trim((string) $uri, '/');
        if ($path === '//') {
            $path = '/';
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $route = $this->routes[$method][$path] ?? null;
        $args = [];

        if ($route === null) {
            // Try parameterized routes.
            foreach ($this->routes[$method] ?? [] as $pattern => [$controllerClass, $action, $params]) {
                $regex = preg_replace('/\{[a-zA-Z_][a-zA-Z0-9_]*\}/', '([^/]+)', $pattern);
                if (preg_match('#^' . $regex . '$#', $path, $m)) {
                    array_shift($m);
                    $args = $m;
                    $route = [$controllerClass, $action, $params];
                    break;
                }
            }
        }

        if ($route === null) {
            ErrorHandler::notFound();
            return;
        }

        [$controllerClass, $action] = $route;
        $controller = new $controllerClass();
        $controller->{$action}(...$args);
    }
}
