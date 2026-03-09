<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$modulesDir = $root . DIRECTORY_SEPARATOR . 'modules';
$modulesFile = $storage . DIRECTORY_SEPARATOR . 'modules.json';
$baseUrl = kr_base_url();

$state = ['enabled' => []];
if (file_exists($modulesFile)) {
    $decoded = json_decode((string) file_get_contents($modulesFile), true);
    if (is_array($decoded) && isset($decoded['enabled']) && is_array($decoded['enabled'])) {
        $state = $decoded;
    }
}

$modules = [];
$moduleSlugSet = [];
$dirs = glob($modulesDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
foreach ($dirs as $modulePath) {
    $slug = basename($modulePath);
    $metaFile = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
    if (!file_exists($metaFile)) {
        continue;
    }
    $meta = json_decode((string) file_get_contents($metaFile), true);
    if (!is_array($meta)) {
        continue;
    }
    $slug = (string) ($meta['slug'] ?? $slug);
    $moduleSlugSet[$slug] = true;
    $modules[] = [
        'slug' => $slug,
        'name' => (string) ($meta['name'] ?? $slug),
        'description' => (string) ($meta['description'] ?? ''),
        'enabled' => false,
    ];
}

$enabledSet = [];
foreach ($state['enabled'] as $slug) {
    if (is_string($slug) && $slug !== '' && isset($moduleSlugSet[$slug])) {
        $enabledSet[$slug] = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = (string) ($_POST['slug'] ?? '');
    $action = (string) ($_POST['action'] ?? '');
    if (kr_csrf_validate((string) ($_POST['csrf_token'] ?? '')) && isset($moduleSlugSet[$slug])) {
        if ($action === 'enable') {
            $enabledSet[$slug] = true;
        } elseif ($action === 'disable') {
            unset($enabledSet[$slug]);
        }
        $newState = ['enabled' => array_values(array_keys($enabledSet))];
        file_put_contents($modulesFile, json_encode($newState, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    header('Location: ' . $baseUrl . '/dashboard/modules', true, 302);
    exit;
}

foreach ($modules as &$module) {
    $module['enabled'] = isset($enabledSet[$module['slug']]);
}
unset($module);

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Karakuri Module Manager</title>
</head>
<body>
  <h1>Module Manager</h1>
  <p><a href="./dashboard">Back to dashboard</a></p>

  <?php if (!$modules): ?>
    <p>No modules found.</p>
  <?php endif; ?>

  <?php foreach ($modules as $module): ?>
    <section>
      <h2><?= htmlspecialchars($module['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($module['slug'], ENT_QUOTES, 'UTF-8') ?>)</h2>
      <p><?= htmlspecialchars($module['description'], ENT_QUOTES, 'UTF-8') ?></p>
      <p>Status: <?= $module['enabled'] ? 'enabled' : 'disabled' ?></p>
      <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(kr_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="slug" value="<?= htmlspecialchars($module['slug'], ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($module['enabled']): ?>
          <button type="submit" name="action" value="disable">Disable</button>
        <?php else: ?>
          <button type="submit" name="action" value="enable">Enable</button>
        <?php endif; ?>
      </form>
    </section>
    <hr>
  <?php endforeach; ?>
</body>
</html>
