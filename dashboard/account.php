<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$adminFile = $storage . DIRECTORY_SEPARATOR . 'admin.json';
$publicBaseUrl = kr_public_base_url();

$admin = kr_read_json_file($adminFile, []);
$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kr_csrf_validate((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = kr_t('account.invalid_csrf');
    }

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $storedHash = (string) ($admin['password'] ?? '');

    if (!$errors && ($currentPassword === '' || $newPassword === '' || $confirmPassword === '')) {
        $errors[] = kr_t('account.required');
    }
    if (!$errors && !password_verify($currentPassword, $storedHash)) {
        $errors[] = kr_t('account.current_incorrect');
    }
    if (!$errors && strlen($newPassword) < 8) {
        $errors[] = kr_t('account.new_min');
    }
    if (!$errors && $newPassword !== $confirmPassword) {
        $errors[] = kr_t('account.confirm_mismatch');
    }

    if (!$errors) {
        $admin['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $admin['updated_at'] = date(DATE_ATOM);
        if (!kr_write_json_file($adminFile, $admin)) {
            $errors[] = kr_t('account.save_failed');
        } else {
            session_regenerate_id(true);
            $messages[] = kr_t('account.updated');
        }
    }
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="<?= htmlspecialchars(kr_lang(), ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(kr_t('account.title'), ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($publicBaseUrl . '/assets/setup.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <main class="card">
  <?php
    $pageTitle = kr_t('account.title');
    require __DIR__ . '/_header.php';
  ?>
  <p><a href="<?= htmlspecialchars(kr_url('/dashboard'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(kr_t('common.back_to_dashboard'), ENT_QUOTES, 'UTF-8') ?></a></p>

  <?php if ($errors): ?>
    <ul class="notice">
      <?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if ($messages): ?>
    <ul>
      <?php foreach ($messages as $message): ?>
        <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(kr_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <div>
      <label for="current_password"><?= htmlspecialchars(kr_t('account.current_password'), ENT_QUOTES, 'UTF-8') ?></label><br>
      <input id="current_password" name="current_password" type="password" required>
    </div>
    <div>
      <label for="new_password"><?= htmlspecialchars(kr_t('account.new_password'), ENT_QUOTES, 'UTF-8') ?></label><br>
      <input id="new_password" name="new_password" type="password" required minlength="8">
    </div>
    <div>
      <label for="confirm_password"><?= htmlspecialchars(kr_t('account.confirm_password'), ENT_QUOTES, 'UTF-8') ?></label><br>
      <input id="confirm_password" name="confirm_password" type="password" required minlength="8">
    </div>
    <button type="submit"><?= htmlspecialchars(kr_t('account.submit'), ENT_QUOTES, 'UTF-8') ?></button>
  </form>
  </main>
</body>
</html>
