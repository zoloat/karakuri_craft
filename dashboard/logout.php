<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !kr_csrf_validate((string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(400);
    echo 'Bad request.';
    exit;
}

if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];
    session_destroy();
}

header('Location: ' . kr_url('/dashboard/login'), true, 302);
exit;
