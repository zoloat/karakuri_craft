<?php
declare(strict_types=1);

function kr_read_json_file(string $path, array $default = []): array
{
    if (!file_exists($path)) {
        return $default;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return $default;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $default;
}

function kr_write_json_file(string $path, array $data): bool
{
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        return false;
    }
    $json .= PHP_EOL;

    $tmpPath = $path . '.tmp';
    $written = file_put_contents($tmpPath, $json, LOCK_EX);
    if ($written === false) {
        return false;
    }

    return rename($tmpPath, $path);
}
