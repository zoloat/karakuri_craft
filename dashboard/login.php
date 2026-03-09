<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$adminFile = $storage . DIRECTORY_SEPARATOR . 'admin.json';
$baseUrl = kr_base_url();

$errors = [];
$admin = [];
if (file_exists($adminFile)) {
    $decoded = json_decode((string) file_get_contents($adminFile), true);
    if (is_array($decoded)) {
        $admin = $decoded;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kr_csrf_validate((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid CSRF token.';
    }

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $storedUser = (string) ($admin['user'] ?? '');
    $storedHash = (string) ($admin['password'] ?? '');

    if (!$errors && ($username === '' || $password === '')) {
        $errors[] = 'Username and password are required.';
    } elseif (!$errors && ($username !== $storedUser || $storedHash === '' || !password_verify($password, $storedHash))) {
        $errors[] = 'Invalid credentials.';
    } elseif (!$errors) {
        session_regenerate_id(true);
        $_SESSION['kr_admin_auth'] = true;
        $_SESSION['kr_admin_user'] = $storedUser;
        header('Location: ' . $baseUrl . '/dashboard', true, 302);
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
  <title>Karakuri Dashboard Login</title>
</head>
<body>
  <h1>Dashboard Login</h1>

  <?php if ($errors): ?>
    <ul>
      <?php foreach ($errors as $error): ?>
        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(kr_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <div>
      <label for="username">Username</label><br>
      <input id="username" name="username" type="text" required>
    </div>
    <div>
      <label for="password">Password</label><br>
      <input id="password" name="password" type="password" required>
    </div>
    <button type="submit">Login</button>
  </form>
</body>
</html>
