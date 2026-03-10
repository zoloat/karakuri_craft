<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$adminFile = $storage . DIRECTORY_SEPARATOR . 'admin.json';
$baseUrl = kr_base_url();
$publicBaseUrl = kr_public_base_url();

$admin = kr_read_json_file($adminFile, []);
$errors = [];
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kr_csrf_validate((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = 'Invalid CSRF token.';
    }

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $storedHash = (string) ($admin['password'] ?? '');

    if (!$errors && ($currentPassword === '' || $newPassword === '' || $confirmPassword === '')) {
        $errors[] = 'All password fields are required.';
    }
    if (!$errors && !password_verify($currentPassword, $storedHash)) {
        $errors[] = 'Current password is incorrect.';
    }
    if (!$errors && strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }
    if (!$errors && $newPassword !== $confirmPassword) {
        $errors[] = 'New password confirmation does not match.';
    }

    if (!$errors) {
        $admin['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $admin['updated_at'] = date(DATE_ATOM);
        if (!kr_write_json_file($adminFile, $admin)) {
            $errors[] = 'Failed to update admin password.';
        } else {
            session_regenerate_id(true);
            $messages[] = 'Password has been updated.';
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
  <title>Karakuri Account</title>
  <link rel="stylesheet" href="<?= htmlspecialchars($publicBaseUrl . '/assets/setup.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <main class="card">
  <h1>Admin Account</h1>
  <p><a href="<?= htmlspecialchars($baseUrl . '/dashboard', ENT_QUOTES, 'UTF-8') ?>">Back to dashboard</a></p>

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
      <label for="current_password">Current password</label><br>
      <input id="current_password" name="current_password" type="password" required>
    </div>
    <div>
      <label for="new_password">New password</label><br>
      <input id="new_password" name="new_password" type="password" required minlength="8">
    </div>
    <div>
      <label for="confirm_password">Confirm new password</label><br>
      <input id="confirm_password" name="confirm_password" type="password" required minlength="8">
    </div>
    <button type="submit">Update password</button>
  </form>
  </main>
</body>
</html>
