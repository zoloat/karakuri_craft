<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$configFile = $storage . DIRECTORY_SEPARATOR . 'config.json';
$adminFile = $storage . DIRECTORY_SEPARATOR . 'admin.json';
$modulesFile = $storage . DIRECTORY_SEPARATOR . 'modules.json';

$config = [];
if (file_exists($configFile)) {
    $config = kr_read_json_file($configFile, []);
}

$admin = [];
if (file_exists($adminFile)) {
    $admin = kr_read_json_file($adminFile, []);
}

$modulesState = ['enabled' => []];
if (file_exists($modulesFile)) {
    $decoded = kr_read_json_file($modulesFile, ['enabled' => []]);
    if (isset($decoded['enabled']) && is_array($decoded['enabled'])) {
        $modulesState = $decoded;
    }
}

$baseUrl = kr_base_url();

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Karakuri Dashboard</title>
</head>
<body>
  <h1>Karakuri Dashboard</h1>
  <p>Setup is complete.</p>
  <form method="post" action="<?= htmlspecialchars($baseUrl . '/dashboard/logout', ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(kr_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit">Logout</button>
  </form>
  <p><a href="<?= htmlspecialchars($baseUrl . '/dashboard/account', ENT_QUOTES, 'UTF-8') ?>">Admin Account</a></p>
  <p><a href="<?= htmlspecialchars($baseUrl . '/dashboard/modules', ENT_QUOTES, 'UTF-8') ?>">Module Manager</a></p>
  <ul>
    <li>Site: <?= htmlspecialchars((string) ($config['site_name'] ?? 'Karakuri'), ENT_QUOTES, 'UTF-8') ?></li>
    <li>Admin: <?= htmlspecialchars((string) ($admin['user'] ?? '(not set)'), ENT_QUOTES, 'UTF-8') ?></li>
    <li>Manual modules: <?= !empty($config['allow_manual_modules']) ? 'enabled' : 'disabled' ?></li>
    <li>Enabled modules: <?= htmlspecialchars(implode(', ', $modulesState['enabled']), ENT_QUOTES, 'UTF-8') ?: '(none)' ?></li>
  </ul>
</body>
</html>
