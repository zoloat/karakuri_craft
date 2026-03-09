<?php
declare(strict_types=1);

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
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(
        [
            'status' => 'ok',
            'time' => date(DATE_ATOM),
            'site' => kr_config('site_name', 'Karakuri'),
        ],
        JSON_UNESCAPED_SLASHES
    );
});

kr_register('system.ping', static fn (): array => [
    'status' => 'ok',
    'time' => date(DATE_ATOM),
]);
