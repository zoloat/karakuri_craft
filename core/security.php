<?php
declare(strict_types=1);

function kr_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    return $forwarded === 'https';
}

function kr_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (kr_is_https()) {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}

function kr_send_security_headers(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
}

function kr_base_url(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/public/index.php'));
    if ($scriptName === '' || $scriptName === '/') {
        return '/public/index.php';
    }
    if (!str_ends_with($scriptName, '.php')) {
        $scriptName = rtrim($scriptName, '/') . '/index.php';
    }
    return $scriptName;
}

function kr_client_ip(): string
{
    $candidate = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
    if ($candidate !== '') {
        $parts = explode(',', $candidate);
        $ip = trim((string) $parts[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    $remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    return $remote !== '' ? $remote : 'unknown';
}

function kr_csrf_token(): string
{
    if (empty($_SESSION['kr_csrf']) || !is_string($_SESSION['kr_csrf'])) {
        $_SESSION['kr_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['kr_csrf'];
}

function kr_csrf_validate(?string $token): bool
{
    if (!isset($_SESSION['kr_csrf']) || !is_string($_SESSION['kr_csrf'])) {
        return false;
    }
    if ($token === null || $token === '') {
        return false;
    }
    return hash_equals($_SESSION['kr_csrf'], $token);
}
