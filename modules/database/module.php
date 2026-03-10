<?php
declare(strict_types=1);

/**
 * Database module guide:
 * - Config key: config.json -> "database" object
 * - Supported driver: "json" | "sqlite" | "mysql"
 * - API:
 *   - kr("database.status"): array
 *   - kr("database.pdo"): ?PDO
 */

/**
 * Build normalized database config with safe defaults.
 */
$krDatabaseConfig = static function (): array {
    $db = kr_config('database', []);
    if (!is_array($db)) {
        $db = [];
    }

    $driver = strtolower((string) ($db['driver'] ?? 'json'));
    if (!in_array($driver, ['json', 'sqlite', 'mysql'], true)) {
        $driver = 'json';
    }

    return [
        'driver' => $driver,
        'sqlite_path' => (string) ($db['sqlite_path'] ?? 'storage/app.sqlite'),
        'mysql' => [
            'host' => (string) ($db['mysql']['host'] ?? '127.0.0.1'),
            'port' => (int) ($db['mysql']['port'] ?? 3306),
            'database' => (string) ($db['mysql']['database'] ?? ''),
            'username' => (string) ($db['mysql']['username'] ?? ''),
            'password' => (string) ($db['mysql']['password'] ?? ''),
            'charset' => (string) ($db['mysql']['charset'] ?? 'utf8mb4'),
        ],
    ];
};

/**
 * Try to create PDO for sqlite/mysql mode. json mode returns null.
 */
$krDatabasePdo = static function () use ($krDatabaseConfig): ?PDO {
    $cfg = $krDatabaseConfig();

    if ($cfg['driver'] === 'json') {
        return null;
    }

    if (!extension_loaded('pdo')) {
        return null;
    }

    try {
        if ($cfg['driver'] === 'sqlite') {
            if (!extension_loaded('pdo_sqlite')) {
                return null;
            }
            $sqlitePath = $cfg['sqlite_path'];
            if (!preg_match('/^[A-Za-z]:[\\\\\\/]/', $sqlitePath) && !str_starts_with($sqlitePath, '/')) {
                $sqlitePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sqlitePath);
            }
            $pdo = new PDO('sqlite:' . $sqlitePath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        }

        if (!extension_loaded('pdo_mysql')) {
            return null;
        }
        $mysql = $cfg['mysql'];
        if ($mysql['database'] === '' || $mysql['username'] === '') {
            return null;
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $mysql['host'],
            $mysql['port'],
            $mysql['database'],
            $mysql['charset']
        );
        $pdo = new PDO($dsn, $mysql['username'], $mysql['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (Throwable) {
        return null;
    }
};

kr_register('database.status', static function () use ($krDatabaseConfig, $krDatabasePdo): array {
    $cfg = $krDatabaseConfig();
    $pdo = $krDatabasePdo();

    return [
        'driver' => $cfg['driver'],
        'connected' => $pdo instanceof PDO,
        'pdo_enabled' => extension_loaded('pdo'),
        'pdo_sqlite_enabled' => extension_loaded('pdo_sqlite'),
        'pdo_mysql_enabled' => extension_loaded('pdo_mysql'),
    ];
});

kr_register('database.pdo', static function () use ($krDatabasePdo): ?PDO {
    return $krDatabasePdo();
});

kr_route('GET', '/db/status', static function (): void {
    /** @var array $status */
    $status = kr('database.status');
    kr_response()->json([
        'status' => 'ok',
        'database' => $status,
        'time' => date(DATE_ATOM),
    ]);
});
