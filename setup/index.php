<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$adminFile = $storage . DIRECTORY_SEPARATOR . 'admin.json';
$configFile = $storage . DIRECTORY_SEPARATOR . 'config.json';
$modulesFile = $storage . DIRECTORY_SEPARATOR . 'modules.json';
$setupLockFile = $storage . DIRECTORY_SEPARATOR . 'setup.lock';
$baseUrl = kr_base_url();
$publicBaseUrl = kr_public_base_url();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kr_csrf_validate((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid CSRF token.';
    }

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $siteName = trim((string) ($_POST['site_name'] ?? 'Karakuri'));
    $allowManualModules = isset($_POST['allow_manual_modules']);

    if ($username === '') {
        $errors[] = 'Admin username is required.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Admin password must be at least 8 characters.';
    }

    if (!$errors) {
        // First admin bootstrap writes all runtime state in one transaction-like block.
        $admin = [
            'user' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date(DATE_ATOM),
        ];
        $okAdmin = kr_write_json_file($adminFile, $admin);

        $config = [
            'site_name' => ($siteName === '') ? 'Karakuri' : $siteName,
            'allow_manual_modules' => $allowManualModules,
            'installed_at' => date(DATE_ATOM),
        ];
        $okConfig = kr_write_json_file($configFile, $config);

        $modulesState = [
            'enabled' => ['welcome'],
        ];
        $okModules = kr_write_json_file($modulesFile, $modulesState);

        $okLock = file_put_contents(
            $setupLockFile,
            "locked_at=" . date(DATE_ATOM) . PHP_EOL,
            LOCK_EX
        ) !== false;

        if (!($okAdmin && $okConfig && $okModules && $okLock)) {
            $errors[] = 'Failed to save setup files.';
        }

        if ($errors) {
            // Continue to show errors, do not redirect.
        } else {
            // Setup is one-shot; remove this handler file best-effort after lock is written.
            $setupFile = __FILE__;
            @unlink($setupFile);

            header('Location: ' . $baseUrl . '/dashboard/login', true, 302);
            exit;
        }
    }
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Karakuri Setup</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($publicBaseUrl . '/assets/setup.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <main class="card">
  <h1>Karakuri Setup</h1>
  <p>Create the first admin account and initial configuration.</p>

  <?php if ($errors): ?>
    <ul class="notice">
      <?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(kr_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <div>
      <label for="site_name">Site name</label><br>
      <input id="site_name" name="site_name" type="text" value="Karakuri">
    </div>
    <div>
      <label for="username">Admin username</label><br>
      <input id="username" name="username" type="text" required>
    </div>
    <div>
      <label for="password">Admin password</label><br>
      <input id="password" name="password" type="password" required minlength="8">
    </div>
    <div>
      <label>
        <input name="allow_manual_modules" type="checkbox">
        Allow manual modules
      </label>
    </div>
    <button type="submit">Complete setup</button>
  </form>
  </main>
</body>
</html>
