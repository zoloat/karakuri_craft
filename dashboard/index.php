<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$configFile = $storage . DIRECTORY_SEPARATOR . 'config.json';
$adminFile = $storage . DIRECTORY_SEPARATOR . 'admin.json';
$modulesFile = $storage . DIRECTORY_SEPARATOR . 'modules.json';

$config = [];
if (file_exists($configFile)) {
    $decoded = json_decode((string) file_get_contents($configFile), true);
    if (is_array($decoded)) {
        $config = $decoded;
    }
}

$admin = [];
if (file_exists($adminFile)) {
    $decoded = json_decode((string) file_get_contents($adminFile), true);
    if (is_array($decoded)) {
        $admin = $decoded;
    }
}

$modulesState = ['enabled' => []];
if (file_exists($modulesFile)) {
    $decoded = json_decode((string) file_get_contents($modulesFile), true);
    if (is_array($decoded) && isset($decoded['enabled']) && is_array($decoded['enabled'])) {
        $modulesState = $decoded;
    }
}

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
  <p><a href="./dashboard/logout">Logout</a></p>
  <p><a href="./dashboard/modules">Module Manager</a></p>
  <ul>
    <li>Site: <?= htmlspecialchars((string) ($config['site_name'] ?? 'Karakuri'), ENT_QUOTES, 'UTF-8') ?></li>
    <li>Admin: <?= htmlspecialchars((string) ($admin['user'] ?? '(not set)'), ENT_QUOTES, 'UTF-8') ?></li>
    <li>Manual modules: <?= !empty($config['allow_manual_modules']) ? 'enabled' : 'disabled' ?></li>
    <li>Enabled modules: <?= htmlspecialchars(implode(', ', $modulesState['enabled']), ENT_QUOTES, 'UTF-8') ?: '(none)' ?></li>
  </ul>
</body>
</html>
