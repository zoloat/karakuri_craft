<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$adminFile = $storage . DIRECTORY_SEPARATOR . 'admin.json';
$attemptsFile = $storage . DIRECTORY_SEPARATOR . 'login_attempts.json';
$publicBaseUrl = kr_public_base_url();

$errors = [];
$admin = [];
if (!empty($_SESSION['kr_admin_auth'])) {
    header('Location: ' . kr_url('/dashboard'), true, 302);
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
    $errors[] = kr_tf('login.too_many_attempts', ['seconds' => $wait]);
}

if (($state['first_at'] ?? 0) + $window < $now) {
    $state = ['count' => 0, 'first_at' => $now, 'lock_until' => 0];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kr_csrf_validate((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = kr_t('login.invalid_csrf');
    }

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $storedUser = (string) ($admin['user'] ?? '');
    $storedHash = (string) ($admin['password'] ?? '');

    // Validate in deterministic order to avoid leaking account state details.
    if (!$errors && ($username === '' || $password === '')) {
        $errors[] = kr_t('login.required');
    } elseif (!$errors && ($username !== $storedUser || $storedHash === '' || !password_verify($password, $storedHash))) {
        $errors[] = kr_t('login.invalid_credentials');
        // Track failed attempts per client IP to slow brute-force attacks.
        $state['count'] = (int) ($state['count'] ?? 0) + 1;
        if ($state['count'] >= $maxAttempts) {
            $state['lock_until'] = $now + $lockSeconds;
        }
        $attempts[$key] = $state;
        kr_write_json_file($attemptsFile, $attempts);
    } elseif (!$errors) {
        // Prevent session fixation after successful authentication.
        session_regenerate_id(true);
        $_SESSION['kr_admin_auth'] = true;
        $_SESSION['kr_admin_user'] = $storedUser;
        unset($attempts[$key]);
        kr_write_json_file($attemptsFile, $attempts);
        header('Location: ' . kr_url('/dashboard'), true, 302);
        exit;
    }
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="<?= htmlspecialchars(kr_lang(), ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(kr_t('login.title'), ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($publicBaseUrl . '/assets/setup.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <main class="card">
  <?php
    $pageTitle = kr_t('login.title');
    require __DIR__ . '/_header.php';
  ?>

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
      <label for="username"><?= htmlspecialchars(kr_t('login.username'), ENT_QUOTES, 'UTF-8') ?></label><br>
      <input id="username" name="username" type="text" required>
    </div>
    <div>
      <label for="password"><?= htmlspecialchars(kr_t('login.password'), ENT_QUOTES, 'UTF-8') ?></label><br>
      <input id="password" name="password" type="password" required>
    </div>
    <button type="submit"><?= htmlspecialchars(kr_t('login.submit'), ENT_QUOTES, 'UTF-8') ?></button>
  </form>
  </main>
</body>
</html>
