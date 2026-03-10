<?php
declare(strict_types=1);

/*
 * Quick guide for module authors:
 * - kr_server()->asArray()  : php version / https / client ip / server software
 * - kr_member()->adminUser(): current admin user or null
 * - kr_member()->isAdminAuthenticated(): admin login status
 */

kr_route('GET', '/', static function (): void {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Karakuri</title></head><body>';
    echo '<h1>Karakuri</h1>';
    echo '<p>Modular runtime is active.</p>';
    echo '<ul>';
    echo '<li><a href="./setup">Setup</a></li>';
    echo '<li><a href="./dashboard">Dashboard</a></li>';
    echo '<li><a href="./health">Health</a></li>';
    echo '</ul>';
    echo '</body></html>';
});

kr_route('GET', '/health', static function (): void {
    kr_response()->json([
        'status' => 'ok',
        'time' => date(DATE_ATOM),
        'site' => kr_config('site_name', 'Karakuri'),
        'server' => kr_server()->asArray(),
        'admin_user' => kr_member()->adminUser(),
    ]);
});

kr_register('system.ping', static fn (): array => [
    'status' => 'ok',
    'time' => date(DATE_ATOM),
]);
