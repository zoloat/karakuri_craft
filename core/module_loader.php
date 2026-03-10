<?php
declare(strict_types=1);

/**
 * Load enabled modules from modules/ based on storage/modules.json.
 *
 * @return array{
 *   scanned: array<string, array<string,mixed>>,
 *   enabled: array<int, string>,
 *   loaded: array<int, string>
 * }
 */
function kr_load_modules(string $root, string $storage): array
{
    $modulesDir = $root . DIRECTORY_SEPARATOR . 'modules';
    $modulesStateFile = $storage . DIRECTORY_SEPARATOR . 'modules.json';

    $state = ['enabled' => []];
    if (file_exists($modulesStateFile)) {
        $decoded = kr_read_json_file($modulesStateFile, ['enabled' => []]);
        if (is_array($decoded) && isset($decoded['enabled']) && is_array($decoded['enabled'])) {
            $state = $decoded;
        }
    }

    $enabledSet = [];
    foreach ($state['enabled'] as $slug) {
        if (is_string($slug) && $slug !== '') {
            $enabledSet[$slug] = true;
        }
    }

    $scanned = [];
    $loaded = [];
    $dirs = glob($modulesDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];

    foreach ($dirs as $modulePath) {
        $slug = basename($modulePath);
        $metaFile = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
        $entryFile = $modulePath . DIRECTORY_SEPARATOR . 'module.php';
        if (!file_exists($metaFile) || !file_exists($entryFile)) {
            continue;
        }

        $meta = kr_read_json_file($metaFile, []);
        if (!is_array($meta)) {
            continue;
        }

        $slug = (string) ($meta['slug'] ?? $slug);
        $scanned[$slug] = $meta;

        if (!isset($enabledSet[$slug])) {
            continue;
        }

        require $entryFile;
        $loaded[] = $slug;
    }

    return [
        'scanned' => $scanned,
        'enabled' => array_keys($enabledSet),
        'loaded' => $loaded,
    ];
}
