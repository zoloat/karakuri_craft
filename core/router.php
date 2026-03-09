<?php
declare(strict_types=1);

function kr_normalize_path(string $path): string
{
    $path = '/' . ltrim($path, '/');
    if ($path !== '/') {
        $path = rtrim($path, '/');
    }
    return $path;
}

function kr_route(string $method, string $path, callable $handler): void
{
    $method = strtoupper($method);
    $path = kr_normalize_path($path);
    if (!isset($GLOBALS['kr_routes']) || !is_array($GLOBALS['kr_routes'])) {
        $GLOBALS['kr_routes'] = [];
    }
    if (!isset($GLOBALS['kr_routes'][$method])) {
        $GLOBALS['kr_routes'][$method] = [];
    }
    $GLOBALS['kr_routes'][$method][$path] = $handler;
}

function kr_dispatch(string $method, string $path): bool
{
    $method = strtoupper($method);
    $path = kr_normalize_path($path);
    $routes = $GLOBALS['kr_routes'][$method] ?? [];
    $handler = $routes[$path] ?? null;
    if (!is_callable($handler)) {
        return false;
    }
    $handler();
    return true;
}
