<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$adminFile = $storage . DIRECTORY_SEPARATOR . 'admin.json';
$attemptsFile = $storage . DIRECTORY_SEPARATOR . 'login_attempts.json';
$baseUrl = kr_base_url();

$errors = [];
$admin = [];
if (!empty($_SESSION['kr_admin_auth'])) {
    header('Location: ' . $baseUrl . '/dashboard', true, 302);
    exit;
}
if (file_exists($adminFile)) {
    $admin = kr_read_json_file($adminFile, []);
}

$ip = kr_client_ip();
$attempts = kr_read_json_file($attemptsFile, []);
$key = 'ip:' . $ip;
$now = time();
$window = 300;
$maxAttempts = 5;
$lockSeconds = 600;
$state = $attempts[$key] ?? [
    'count' => 0,
    'first_at' => $now,
    'lock_until' => 0,
];

if (($state['lock_until'] ?? 0) > $now) {
    $wait = (int) $state['lock_until'] - $now;
    $errors[] = 'Too many failed attempts. Try again in ' . $wait . ' seconds.';
}

if (($state['first_at'] ?? 0) + $window < $now) {
    $state = ['count' => 0, 'first_at' => $now, 'lock_until' => 0];
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
        $state['count'] = (int) ($state['count'] ?? 0) + 1;
        if ($state['count'] >= $maxAttempts) {
            $state['lock_until'] = $now + $lockSeconds;
        }
        $attempts[$key] = $state;
        kr_write_json_file($attemptsFile, $attempts);
    } elseif (!$errors) {
        session_regenerate_id(true);
        $_SESSION['kr_admin_auth'] = true;
        $_SESSION['kr_admin_user'] = $storedUser;
        unset($attempts[$key]);
        kr_write_json_file($attemptsFile, $attempts);
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
