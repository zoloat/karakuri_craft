<?php
declare(strict_types=1);

/**
 * Audience: Human and AI module authors.
 * Purpose: Expose server/runtime information without reading $_SERVER directly.
 *
 * Inputs:
 * - PHP runtime constants
 * - Current request environment
 *
 * Outputs:
 * - Stable server info map
 */
final class KrServerContext
{
    public function phpVersion(): string
    {
        return PHP_VERSION;
    }

    public function isHttps(): bool
    {
        return kr_is_https();
    }

    public function clientIp(): string
    {
        return kr_client_ip();
    }

    public function software(): string
    {
        return (string) ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown');
    }

    public function asArray(): array
    {
        return [
            'php_version' => $this->phpVersion(),
            'https' => $this->isHttps(),
            'client_ip' => $this->clientIp(),
            'software' => $this->software(),
        ];
    }
}
