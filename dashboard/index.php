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

$publicBaseUrl = kr_public_base_url();

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="<?= htmlspecialchars(kr_lang(), ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(kr_t('dashboard.title'), ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($publicBaseUrl . '/assets/setup.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <main class="card">
  <?php
    $pageTitle = kr_t('dashboard.title');
    $pageSubtitle = kr_t('dashboard.setup_complete');
    require __DIR__ . '/_header.php';
  ?>
  <form method="post" action="<?= htmlspecialchars(kr_url('/dashboard/logout'), ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(kr_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit"><?= htmlspecialchars(kr_t('common.logout'), ENT_QUOTES, 'UTF-8') ?></button>
  </form>
  <p><a href="<?= htmlspecialchars(kr_url('/dashboard/account'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(kr_t('dashboard.admin_account'), ENT_QUOTES, 'UTF-8') ?></a></p>
  <p><a href="<?= htmlspecialchars(kr_url('/dashboard/database'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(kr_t('dashboard.database_settings'), ENT_QUOTES, 'UTF-8') ?></a></p>
  <p><a href="<?= htmlspecialchars(kr_url('/dashboard/modules'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(kr_t('dashboard.module_manager'), ENT_QUOTES, 'UTF-8') ?></a></p>
  <ul>
    <li><?= htmlspecialchars(kr_t('dashboard.site'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($config['site_name'] ?? 'Karakuri'), ENT_QUOTES, 'UTF-8') ?></li>
    <li><?= htmlspecialchars(kr_t('dashboard.admin'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) ($admin['user'] ?? kr_t('dashboard.not_set')), ENT_QUOTES, 'UTF-8') ?></li>
    <li><?= htmlspecialchars(kr_t('dashboard.manual_modules'), ENT_QUOTES, 'UTF-8') ?>: <?= !empty($config['allow_manual_modules']) ? htmlspecialchars(kr_t('common.enabled'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(kr_t('common.disabled'), ENT_QUOTES, 'UTF-8') ?></li>
    <li><?= htmlspecialchars(kr_t('dashboard.enabled_modules'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars(implode(', ', $modulesState['enabled']), ENT_QUOTES, 'UTF-8') ?: htmlspecialchars(kr_t('dashboard.none'), ENT_QUOTES, 'UTF-8') ?></li>
  </ul>
  </main>
</body>
</html>
