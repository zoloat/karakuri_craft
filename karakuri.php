<?php
declare(strict_types=1);

/*
 * Karakuri bootstrap installer (web-first, CLI helper supported).
 */

$isCli = PHP_SAPI === 'cli';
$root = __DIR__;
$lockFile = $root . DIRECTORY_SEPARATOR . 'install.lock';

$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
$baseUrl = ($baseUrl === '' || $baseUrl === '.') ? '' : $baseUrl;
$setupUrl = $baseUrl . '/public/index.php/setup';
$dashboardLoginUrl = $baseUrl . '/public/index.php/dashboard/login';
$cssUrl = $baseUrl . '/public/assets/setup.css';

// Hard stop: once installed, do not run bootstrap again.
if (file_exists($lockFile)) {
    if ($isCli) {
        fwrite(STDOUT, "Karakuri is already installed. Bootstrap is locked.\n");
        fwrite(STDOUT, "Open: /public/index.php/dashboard/login\n");
        exit(0);
    }

    header('Content-Type: text/html; charset=UTF-8');
    echo "<!doctype html><html><head><meta charset='utf-8'><title>Karakuri Installer</title>";
    echo "<link rel='stylesheet' href='" . htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') . "'>";
    echo "</head><body><main class='card'>";
    echo "<h1>Karakuri is already installed</h1>";
    echo "<p>Bootstrap is locked by <code>install.lock</code>.</p>";
    echo "<p><a href='" . htmlspecialchars($dashboardLoginUrl, ENT_QUOTES, 'UTF-8') . "'>Go to dashboard login</a></p>";
    echo "<p><a href='" . htmlspecialchars($setupUrl, ENT_QUOTES, 'UTF-8') . "'>Setup (locked)</a></p>";
    echo "</main></body></html>";
    exit;
}

$requiredDirs = [
    'core',
    'modules',
    'storage',
    'storage/logs',
    'dashboard',
    'config',
    'public',
    'setup',
];

$environment = [
    'php_version' => PHP_VERSION,
    'extensions' => [
        'gd' => extension_loaded('gd'),
        'imagick' => extension_loaded('imagick'),
        'pdo' => extension_loaded('pdo'),
        'sqlite' => extension_loaded('pdo_sqlite') || extension_loaded('sqlite3'),
        'mysql' => extension_loaded('pdo_mysql') || extension_loaded('mysqli'),
        'curl' => extension_loaded('curl'),
        'zip' => extension_loaded('zip'),
    ],
];

$errors = [];
// Create required runtime directories up front so later writes are predictable.
foreach ($requiredDirs as $dir) {
    $path = $root . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
        $errors[] = "Failed to create directory: {$dir}";
    }
}

$storagePath = $root . DIRECTORY_SEPARATOR . 'storage';
$environmentFile = $storagePath . DIRECTORY_SEPARATOR . 'environment.json';
$configFile = $storagePath . DIRECTORY_SEPARATOR . 'config.json';
$modulesFile = $storagePath . DIRECTORY_SEPARATOR . 'modules.json';
$htaccessFile = $storagePath . DIRECTORY_SEPARATOR . '.htaccess';

// Write initial runtime files only when directory bootstrap succeeded.
if (!$errors) {
    file_put_contents($environmentFile, json_encode($environment, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    if (!file_exists($configFile)) {
        $config = [
            'site_name' => 'Karakuri',
            'allow_manual_modules' => false,
            'database' => [
                'driver' => 'json',
                'sqlite_path' => 'storage/app.sqlite',
                'mysql' => [
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'database' => '',
                    'username' => '',
                    'password' => '',
                    'charset' => 'utf8mb4',
                ],
            ],
            'installed_at' => date(DATE_ATOM),
        ];
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    if (!file_exists($modulesFile)) {
        $modules = ['enabled' => []];
        file_put_contents($modulesFile, json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    if (!file_exists($htaccessFile)) {
        file_put_contents($htaccessFile, "Deny from all\n");
    }

    if (!file_exists($lockFile)) {
        file_put_contents($lockFile, "installed_at=" . date(DATE_ATOM) . PHP_EOL);
    }
}

// CLI mode is helper-only; web mode remains the primary installer flow.
if ($isCli) {
    if ($errors) {
        fwrite(STDERR, "Karakuri installer failed:\n- " . implode("\n- ", $errors) . "\n");
        exit(1);
    }
    fwrite(STDOUT, "Karakuri installer completed.\n");
    fwrite(STDOUT, "Open: /public/index.php/setup\n");
    exit(0);
}

header('Content-Type: text/html; charset=UTF-8');

// Show explicit bootstrap errors in web mode so shared-hosting users can self-diagnose.
if ($errors) {
    http_response_code(500);
    echo "<h1>Karakuri Installer Error</h1><ul>";
    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    echo "</ul>";
    exit;
}

echo "<!doctype html><html><head><meta charset='utf-8'><title>Karakuri Installer</title>";
echo "<link rel='stylesheet' href='" . htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') . "'>";
echo "</head><body><main class='card'>";
echo "<h1>Karakuri installed</h1>";
echo "<p>Environment and initial config were created in <code>storage/</code>.</p>";
echo "<p><a href='" . htmlspecialchars($setupUrl, ENT_QUOTES, 'UTF-8') . "'>Go to setup</a></p>";
echo "</main></body></html>";
