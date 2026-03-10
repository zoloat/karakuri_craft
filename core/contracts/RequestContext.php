<?php
declare(strict_types=1);

/**
 * Audience: Human and AI module authors.
 * Purpose: Read incoming HTTP request values in one predictable place.
 *
 * Inputs:
 * - Superglobals ($_SERVER, $_GET, $_POST)
 *
 * Outputs:
 * - Normalized method/path/query/form values
 *
 * Failure behavior:
 * - Returns defaults (never throws for missing keys)
 */
final class KrRequestContext
{
    public function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public function uriPath(): string
    {
        return (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    public function form(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? $default;
    }
}
