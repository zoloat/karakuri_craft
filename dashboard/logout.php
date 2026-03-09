<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];
    session_destroy();
}

$baseUrl = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/public/index.php')), '/');
header('Location: ' . $baseUrl . '/dashboard/login', true, 302);
exit;
