<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$adminFile = $storage . DIRECTORY_SEPARATOR . 'admin.json';
$configFile = $storage . DIRECTORY_SEPARATOR . 'config.json';
$modulesFile = $storage . DIRECTORY_SEPARATOR . 'modules.json';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $admin = [
            'user' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date(DATE_ATOM),
        ];
        file_put_contents($adminFile, json_encode($admin, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $config = [
            'site_name' => ($siteName === '') ? 'Karakuri' : $siteName,
            'allow_manual_modules' => $allowManualModules,
            'installed_at' => date(DATE_ATOM),
        ];
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $modulesState = [
            'enabled' => ['welcome'],
        ];
        file_put_contents($modulesFile, json_encode($modulesState, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        header('Location: ../public/index.php/dashboard', true, 302);
        exit;
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
</head>
<body>
  <h1>Karakuri Setup</h1>
  <p>Create the first admin account and initial configuration.</p>

  <?php if ($errors): ?>
    <ul>
      <?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" action="">
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
</body>
</html>
