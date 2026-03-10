<?php
declare(strict_types=1);

/**
 * Audience: Human and AI module authors.
 * Purpose: Read/write runtime JSON safely without re-implementing file handling.
 *
 * Inputs:
 * - Relative path under storage/
 * - Array payload
 *
 * Outputs:
 * - Array data or write success/failure
 */
final class KrStorageContext
{
    public function __construct(private readonly string $storageRoot)
    {
    }

    public function readJson(string $relativePath, array $default = []): array
    {
        $path = $this->path($relativePath);
        return kr_read_json_file($path, $default);
    }

    public function writeJson(string $relativePath, array $data): bool
    {
        $path = $this->path($relativePath);
        return kr_write_json_file($path, $data);
    }

    public function path(string $relativePath): string
    {
        $normalized = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        return $this->storageRoot . DIRECTORY_SEPARATOR . $normalized;
    }
}
