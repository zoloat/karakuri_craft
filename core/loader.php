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

require __DIR__ . DIRECTORY_SEPARATOR . 'security.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'router.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'storage.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'contracts' . DIRECTORY_SEPARATOR . 'RequestContext.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'contracts' . DIRECTORY_SEPARATOR . 'ResponseContext.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'contracts' . DIRECTORY_SEPARATOR . 'ServerContext.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'contracts' . DIRECTORY_SEPARATOR . 'MemberContext.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'contracts' . DIRECTORY_SEPARATOR . 'StorageContext.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'helpers.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'module_loader.php';

kr_start_session();
kr_send_security_headers();

$GLOBALS['kr_request'] = new KrRequestContext();
$GLOBALS['kr_response'] = new KrResponseContext();
$GLOBALS['kr_server'] = new KrServerContext();
$GLOBALS['kr_member'] = new KrMemberContext();
$GLOBALS['kr_storage'] = new KrStorageContext($storage);

$moduleState = kr_load_modules($root, $storage);
$GLOBALS['kr_module_state'] = $moduleState;

$adminFile = $storage . DIRECTORY_SEPARATOR . 'admin.json';
$setupLockFile = $storage . DIRECTORY_SEPARATOR . 'setup.lock';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

if ($baseDir !== '' && str_starts_with($path, $baseDir)) {
    $path = substr($path, strlen($baseDir));
}

$path = '/' . ltrim((string) $path, '/');

// Setup is first-run only. Once locked, always send users to dashboard login.
if ($path === '/setup') {
    if (file_exists($setupLockFile) || file_exists($adminFile)) {
        header('Location: ./dashboard/login', true, 302);
        exit;
    }
    require $root . DIRECTORY_SEPARATOR . 'setup' . DIRECTORY_SEPARATOR . 'index.php';
    exit;
}

// Dashboard routes are protected behind admin session checks.
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

if ($path === '/dashboard/modules') {
    if (!file_exists($adminFile)) {
        header('Location: ./setup', true, 302);
        exit;
    }
    if (empty($_SESSION['kr_admin_auth'])) {
        header('Location: ./dashboard/login', true, 302);
        exit;
    }
    require $root . DIRECTORY_SEPARATOR . 'dashboard' . DIRECTORY_SEPARATOR . 'modules.php';
    exit;
}

if ($path === '/dashboard/account') {
    if (!file_exists($adminFile)) {
        header('Location: ./setup', true, 302);
        exit;
    }
    if (empty($_SESSION['kr_admin_auth'])) {
        header('Location: ./dashboard/login', true, 302);
        exit;
    }
    require $root . DIRECTORY_SEPARATOR . 'dashboard' . DIRECTORY_SEPARATOR . 'account.php';
    exit;
}

// Login remains accessible until authenticated; protected routes redirect here.
if ($path === '/dashboard/login') {
    if (!file_exists($adminFile)) {
        header('Location: ./setup', true, 302);
        exit;
    }
    require $root . DIRECTORY_SEPARATOR . 'dashboard' . DIRECTORY_SEPARATOR . 'login.php';
    exit;
}

// Logout is handled as dedicated endpoint to centralize session teardown.
if ($path === '/dashboard/logout') {
    require $root . DIRECTORY_SEPARATOR . 'dashboard' . DIRECTORY_SEPARATOR . 'logout.php';
    exit;
}

// Module routes are evaluated after built-in control routes.
if (kr_dispatch(kr_request()->method(), $path)) {
    exit;
}

http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
echo '<!doctype html><html><head><meta charset="utf-8"><title>Karakuri</title></head><body>';
echo '<h1>404 Not Found</h1>';
echo '<p>No route matched: <code>' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '</code></p>';
echo '<p><a href="./">Back to home</a></p>';
echo '</body></html>';
