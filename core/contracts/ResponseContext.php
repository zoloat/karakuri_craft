<?php
declare(strict_types=1);

/**
 * Audience: Human and AI module authors.
 * Purpose: Emit HTTP responses with minimal boilerplate.
 *
 * Inputs:
 * - Status code
 * - Body payload (html/text/json)
 *
 * Outputs:
 * - Final response and script termination
 */
final class KrResponseContext
{
    public function html(string $html, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;
    }

    public function text(string $text, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $text;
        exit;
    }

    public function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function redirect(string $url, int $status = 302): never
    {
        header('Location: ' . $url, true, $status);
        exit;
    }
}
