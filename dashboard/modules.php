<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$modulesDir = $root . DIRECTORY_SEPARATOR . 'modules';
$modulesFile = $storage . DIRECTORY_SEPARATOR . 'modules.json';
$publicBaseUrl = kr_public_base_url();

$state = ['enabled' => []];
if (file_exists($modulesFile)) {
    $decoded = kr_read_json_file($modulesFile, ['enabled' => []]);
    if (isset($decoded['enabled']) && is_array($decoded['enabled'])) {
        $state = $decoded;
    }
}

$modules = [];
$moduleSlugSet = [];
// Build module catalog from filesystem metadata.
$dirs = glob($modulesDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
foreach ($dirs as $modulePath) {
    $slug = basename($modulePath);
    $metaFile = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
    if (!file_exists($metaFile)) {
        continue;
    }
    $meta = kr_read_json_file($metaFile, []);
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
// Keep enabled state only for modules that are actually present on disk.
foreach ($state['enabled'] as $slug) {
    if (is_string($slug) && $slug !== '' && isset($moduleSlugSet[$slug])) {
        $enabledSet[$slug] = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = (string) ($_POST['slug'] ?? '');
    $action = (string) ($_POST['action'] ?? '');
    // Accept state changes only with valid CSRF and known module slug.
    if (kr_csrf_validate((string) ($_POST['csrf_token'] ?? '')) && isset($moduleSlugSet[$slug])) {
        if ($action === 'enable') {
            $enabledSet[$slug] = true;
        } elseif ($action === 'disable') {
            unset($enabledSet[$slug]);
        }
        $newState = ['enabled' => array_values(array_keys($enabledSet))];
        kr_write_json_file($modulesFile, $newState);
    }
    // Post/Redirect/Get to prevent duplicate actions on refresh.
    header('Location: ' . kr_url('/dashboard/modules'), true, 302);
    exit;
}

foreach ($modules as &$module) {
    $module['enabled'] = isset($enabledSet[$module['slug']]);
}
unset($module);

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="<?= htmlspecialchars(kr_lang(), ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(kr_t('modules.title'), ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($publicBaseUrl . '/assets/setup.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <main class="card">
  <?php
    $pageTitle = kr_t('modules.title');
    require __DIR__ . '/_header.php';
  ?>
  <p><a href="<?= htmlspecialchars(kr_url('/dashboard'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(kr_t('common.back_to_dashboard'), ENT_QUOTES, 'UTF-8') ?></a></p>

  <?php if (!$modules): ?>
    <p><?= htmlspecialchars(kr_t('modules.no_modules'), ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php foreach ($modules as $module): ?>
    <section>
      <h2><?= htmlspecialchars($module['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($module['slug'], ENT_QUOTES, 'UTF-8') ?>)</h2>
      <p><?= htmlspecialchars($module['description'], ENT_QUOTES, 'UTF-8') ?></p>
      <p><?= htmlspecialchars(kr_t('modules.status'), ENT_QUOTES, 'UTF-8') ?>: <?= $module['enabled'] ? htmlspecialchars(kr_t('common.enabled'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(kr_t('common.disabled'), ENT_QUOTES, 'UTF-8') ?></p>
      <form method="post" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(kr_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="slug" value="<?= htmlspecialchars($module['slug'], ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($module['enabled']): ?>
          <button type="submit" name="action" value="disable"><?= htmlspecialchars(kr_t('modules.disable'), ENT_QUOTES, 'UTF-8') ?></button>
        <?php else: ?>
          <button type="submit" name="action" value="enable"><?= htmlspecialchars(kr_t('modules.enable'), ENT_QUOTES, 'UTF-8') ?></button>
        <?php endif; ?>
      </form>
    </section>
    <hr>
  <?php endforeach; ?>
  </main>
</body>
</html>
