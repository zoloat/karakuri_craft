<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$configFile = $storage . DIRECTORY_SEPARATOR . 'config.json';

if (!file_exists($configFile)) {
    http_response_code(503);
    echo 'Karakuri is not installed. Run karakuri.php first.';
    exit;
}

$config = json_decode((string) file_get_contents($configFile), true);
if (!is_array($config)) {
    http_response_code(500);
    echo 'Invalid configuration file.';
    exit;
}
$GLOBALS['kr_config'] = $config;
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . DIRECTORY_SEPARATOR . 'helpers.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'router.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'module_loader.php';

$moduleState = kr_load_modules($root, $storage);
$GLOBALS['kr_module_state'] = $moduleState;

$adminFile = $storage . DIRECTORY_SEPARATOR . 'admin.json';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

if ($baseDir !== '' && str_starts_with($path, $baseDir)) {
    $path = substr($path, strlen($baseDir));
}

$path = '/' . ltrim((string) $path, '/');

if ($path === '/setup') {
    require $root . DIRECTORY_SEPARATOR . 'setup' . DIRECTORY_SEPARATOR . 'index.php';
    exit;
}

if ($path === '/dashboard') {
    if (!file_exists($adminFile)) {
        header('Location: ./setup', true, 302);
        exit;
    }
    if (empty($_SESSION['kr_admin_auth'])) {
        header('Location: ./dashboard/login', true, 302);
        exit;
    }
    require $root . DIRECTORY_SEPARATOR . 'dashboard' . DIRECTORY_SEPARATOR . 'index.php';
    exit;
}

if ($path === '/dashboard/login') {
    if (!file_exists($adminFile)) {
        header('Location: ./setup', true, 302);
        exit;
    }
    require $root . DIRECTORY_SEPARATOR . 'dashboard' . DIRECTORY_SEPARATOR . 'login.php';
    exit;
}

if ($path === '/dashboard/logout') {
    require $root . DIRECTORY_SEPARATOR . 'dashboard' . DIRECTORY_SEPARATOR . 'logout.php';
    exit;
}

if (kr_dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path)) {
    exit;
}

http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
echo '<!doctype html><html><head><meta charset="utf-8"><title>Karakuri</title></head><body>';
echo '<h1>404 Not Found</h1>';
echo '<p>No route matched: <code>' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '</code></p>';
echo '<p><a href="./">Back to home</a></p>';
echo '</body></html>';
