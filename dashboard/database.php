<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$storage = $root . DIRECTORY_SEPARATOR . 'storage';
$configFile = $storage . DIRECTORY_SEPARATOR . 'config.json';
$publicBaseUrl = kr_public_base_url();

$config = kr_read_json_file($configFile, []);
$db = is_array($config['database'] ?? null) ? $config['database'] : [];
$driver = strtolower((string) ($db['driver'] ?? 'json'));
if (!in_array($driver, ['json', 'sqlite', 'mysql'], true)) {
    $driver = 'json';
}

$sqlitePath = (string) ($db['sqlite_path'] ?? 'storage/app.sqlite');
$mysql = is_array($db['mysql'] ?? null) ? $db['mysql'] : [];
$mysqlHost = (string) ($mysql['host'] ?? '127.0.0.1');
$mysqlPort = (int) ($mysql['port'] ?? 3306);
$mysqlDatabase = (string) ($mysql['database'] ?? '');
$mysqlUsername = (string) ($mysql['username'] ?? '');
$mysqlPassword = (string) ($mysql['password'] ?? '');
$mysqlCharset = (string) ($mysql['charset'] ?? 'utf8mb4');

$errors = [];
$messages = [];
$queryResult = null;
$queryColumns = [];
$queryRows = [];

$action = (string) ($_POST['action'] ?? '');
$hasDatabaseService = kr_has_service('database.status') && kr_has_service('database.pdo');

$buildDbConfig = static function () use (
    &$driver,
    &$sqlitePath,
    &$mysqlHost,
    &$mysqlPort,
    &$mysqlDatabase,
    &$mysqlUsername,
    &$mysqlPassword,
    &$mysqlCharset
): array {
    return [
        'driver' => $driver,
        'sqlite_path' => ($sqlitePath === '') ? 'storage/app.sqlite' : $sqlitePath,
        'mysql' => [
            'host' => ($mysqlHost === '') ? '127.0.0.1' : $mysqlHost,
            'port' => ($mysqlPort > 0) ? $mysqlPort : 3306,
            'database' => $mysqlDatabase,
            'username' => $mysqlUsername,
            'password' => $mysqlPassword,
            'charset' => $mysqlCharset,
        ],
    ];
};

$localPdo = static function (array $dbConfig) use ($root): ?PDO {
    if ($dbConfig['driver'] === 'json' || !extension_loaded('pdo')) {
        return null;
    }
    try {
        if ($dbConfig['driver'] === 'sqlite') {
            if (!extension_loaded('pdo_sqlite')) {
                return null;
            }
            $path = (string) ($dbConfig['sqlite_path'] ?? 'storage/app.sqlite');
            if (!preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) && !str_starts_with($path, '/')) {
                $path = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            }
            $pdo = new PDO('sqlite:' . $path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        }
        if (!extension_loaded('pdo_mysql')) {
            return null;
        }
        $mysqlCfg = is_array($dbConfig['mysql'] ?? null) ? $dbConfig['mysql'] : [];
        $dbName = (string) ($mysqlCfg['database'] ?? '');
        $user = (string) ($mysqlCfg['username'] ?? '');
        if ($dbName === '' || $user === '') {
            return null;
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            (string) ($mysqlCfg['host'] ?? '127.0.0.1'),
            (int) ($mysqlCfg['port'] ?? 3306),
            $dbName,
            (string) ($mysqlCfg['charset'] ?? 'utf8mb4')
        );
        $pdo = new PDO($dsn, $user, (string) ($mysqlCfg['password'] ?? ''));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (Throwable) {
        return null;
    }
};

$resolveStatus = static function () use (&$hasDatabaseService, &$buildDbConfig, &$localPdo): array {
    if ($hasDatabaseService) {
        /** @var array $status */
        $status = kr('database.status');
        return $status;
    }
    $cfg = $buildDbConfig();
    $pdo = $localPdo($cfg);
    return [
        'driver' => $cfg['driver'],
        'connected' => $pdo instanceof PDO,
        'pdo_enabled' => extension_loaded('pdo'),
        'pdo_sqlite_enabled' => extension_loaded('pdo_sqlite'),
        'pdo_mysql_enabled' => extension_loaded('pdo_mysql'),
    ];
};

$resolvePdo = static function () use (&$hasDatabaseService, &$buildDbConfig, &$localPdo): ?PDO {
    if ($hasDatabaseService) {
        /** @var ?PDO $pdo */
        $pdo = kr('database.pdo');
        return $pdo;
    }
    return $localPdo($buildDbConfig());
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kr_csrf_validate((string) ($_POST['csrf_token'] ?? ''))) {
        $errors[] = kr_t('database.invalid_csrf');
    }

    $driver = strtolower(trim((string) ($_POST['driver'] ?? $driver)));
    if (!in_array($driver, ['json', 'sqlite', 'mysql'], true)) {
        $driver = 'json';
    }
    $sqlitePath = trim((string) ($_POST['sqlite_path'] ?? $sqlitePath));
    $mysqlHost = trim((string) ($_POST['mysql_host'] ?? $mysqlHost));
    $mysqlPort = (int) ($_POST['mysql_port'] ?? $mysqlPort);
    $mysqlDatabase = trim((string) ($_POST['mysql_database'] ?? $mysqlDatabase));
    $mysqlUsername = trim((string) ($_POST['mysql_username'] ?? $mysqlUsername));
    $mysqlPassword = (string) ($_POST['mysql_password'] ?? $mysqlPassword);
    $mysqlCharset = trim((string) ($_POST['mysql_charset'] ?? $mysqlCharset));
    if ($mysqlCharset === '') {
        $mysqlCharset = 'utf8mb4';
    }

    if (!$errors) {
        $config['database'] = $buildDbConfig();
        $GLOBALS['kr_config'] = $config;

        if ($action === 'save' || $action === 'test' || $action === 'init_schema') {
            if (!kr_write_json_file($configFile, $config)) {
                $errors[] = kr_t('database.save_failed');
            } elseif ($action === 'save') {
                $messages[] = kr_t('database.saved');
            }
        }
    }

    if (!$errors && ($action === 'test' || $action === 'init_schema' || $action === 'run_select')) {
        $status = $resolveStatus();

        if ($status['driver'] === 'json') {
            if ($action === 'test') {
                $messages[] = kr_t('database.json_active');
            } elseif ($action === 'init_schema') {
                $messages[] = kr_t('database.json_skip_init');
            } elseif ($action === 'run_select') {
                $errors[] = kr_t('database.json_no_sql');
            }
        } else {
            $pdo = $resolvePdo();
            if (!$pdo) {
                $errors[] = kr_t('database.connect_failed');
            } elseif ($action === 'test') {
                $messages[] = kr_t('database.connect_ok');
            } elseif ($action === 'init_schema') {
                try {
                    if ($status['driver'] === 'sqlite') {
                        $pdo->exec(
                            'CREATE TABLE IF NOT EXISTS users (
                                id INTEGER PRIMARY KEY AUTOINCREMENT,
                                username TEXT NOT NULL,
                                email TEXT NULL,
                                created_at TEXT NOT NULL
                            )'
                        );
                    } else {
                        $pdo->exec(
                            'CREATE TABLE IF NOT EXISTS users (
                                id INTEGER PRIMARY KEY AUTO_INCREMENT,
                                username VARCHAR(100) NOT NULL,
                                email VARCHAR(255) NULL,
                                created_at DATETIME NOT NULL
                            )'
                        );
                    }
                    $messages[] = kr_t('database.schema_ok');
                } catch (Throwable $e) {
                    $errors[] = kr_tf('database.schema_failed', ['error' => $e->getMessage()]);
                }
            } elseif ($action === 'run_select') {
                $sql = trim((string) ($_POST['sql'] ?? ''));
                if ($sql === '') {
                    $errors[] = kr_t('database.sql_required');
                } elseif (!preg_match('/^\s*select\b/i', $sql)) {
                    $errors[] = kr_t('database.select_only');
                } else {
                    try {
                        $stmt = $pdo->query($sql);
                        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                        if (!is_array($rows)) {
                            $rows = [];
                        }
                        $queryRows = array_slice($rows, 0, 200);
                        $queryColumns = !empty($queryRows) ? array_keys($queryRows[0]) : [];
                        $queryResult = [
                            'count' => count($queryRows),
                            'limited' => count($rows) > 200,
                        ];
                    } catch (Throwable $e) {
                        $errors[] = kr_tf('database.query_failed', ['error' => $e->getMessage()]);
                    }
                }
            }
        }
    }
}

$status = $resolveStatus();
header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="<?= htmlspecialchars(kr_lang(), ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(kr_t('database.title'), ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($publicBaseUrl . '/assets/setup.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <main class="card">
    <?php
      $pageTitle = kr_t('database.title');
      require __DIR__ . '/_header.php';
    ?>
    <p><a href="<?= htmlspecialchars(kr_url('/dashboard'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(kr_t('common.back_to_dashboard'), ENT_QUOTES, 'UTF-8') ?></a></p>
    <?php if (!$hasDatabaseService): ?>
      <p><?= htmlspecialchars(kr_t('database.fallback_note'), ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

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

    <div class="row">
      <strong><?= htmlspecialchars(kr_t('database.current_driver'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars((string) $status['driver'], ENT_QUOTES, 'UTF-8') ?>
      / <strong><?= htmlspecialchars(kr_t('database.connected'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= !empty($status['connected']) ? htmlspecialchars(kr_t('common.yes'), ENT_QUOTES, 'UTF-8') : htmlspecialchars(kr_t('common.no'), ENT_QUOTES, 'UTF-8') ?>
    </div>

    <form method="post" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(kr_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <div>
        <label for="driver"><?= htmlspecialchars(kr_t('database.driver'), ENT_QUOTES, 'UTF-8') ?></label><br>
        <select id="driver" name="driver">
          <option value="json" <?= $driver === 'json' ? 'selected' : '' ?>>json</option>
          <option value="sqlite" <?= $driver === 'sqlite' ? 'selected' : '' ?>>sqlite</option>
          <option value="mysql" <?= $driver === 'mysql' ? 'selected' : '' ?>>mysql</option>
        </select>
      </div>

      <h3><?= htmlspecialchars(kr_t('database.sqlite'), ENT_QUOTES, 'UTF-8') ?></h3>
      <div>
        <label for="sqlite_path"><?= htmlspecialchars(kr_t('database.sqlite_path'), ENT_QUOTES, 'UTF-8') ?></label><br>
        <input id="sqlite_path" name="sqlite_path" type="text" value="<?= htmlspecialchars($sqlitePath, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <h3><?= htmlspecialchars(kr_t('database.mysql'), ENT_QUOTES, 'UTF-8') ?></h3>
      <div>
        <label for="mysql_host"><?= htmlspecialchars(kr_t('database.host'), ENT_QUOTES, 'UTF-8') ?></label><br>
        <input id="mysql_host" name="mysql_host" type="text" value="<?= htmlspecialchars($mysqlHost, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label for="mysql_port"><?= htmlspecialchars(kr_t('database.port'), ENT_QUOTES, 'UTF-8') ?></label><br>
        <input id="mysql_port" name="mysql_port" type="text" value="<?= htmlspecialchars((string) $mysqlPort, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label for="mysql_database"><?= htmlspecialchars(kr_t('database.database'), ENT_QUOTES, 'UTF-8') ?></label><br>
        <input id="mysql_database" name="mysql_database" type="text" value="<?= htmlspecialchars($mysqlDatabase, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label for="mysql_username"><?= htmlspecialchars(kr_t('database.username'), ENT_QUOTES, 'UTF-8') ?></label><br>
        <input id="mysql_username" name="mysql_username" type="text" value="<?= htmlspecialchars($mysqlUsername, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label for="mysql_password"><?= htmlspecialchars(kr_t('database.password'), ENT_QUOTES, 'UTF-8') ?></label><br>
        <input id="mysql_password" name="mysql_password" type="password" value="<?= htmlspecialchars($mysqlPassword, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label for="mysql_charset"><?= htmlspecialchars(kr_t('database.charset'), ENT_QUOTES, 'UTF-8') ?></label><br>
        <input id="mysql_charset" name="mysql_charset" type="text" value="<?= htmlspecialchars($mysqlCharset, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <button type="submit" name="action" value="save"><?= htmlspecialchars(kr_t('database.save'), ENT_QUOTES, 'UTF-8') ?></button>
      <button type="submit" name="action" value="test"><?= htmlspecialchars(kr_t('database.save_test'), ENT_QUOTES, 'UTF-8') ?></button>
      <button type="submit" name="action" value="init_schema"><?= htmlspecialchars(kr_t('database.save_init'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>

    <hr>

    <h2><?= htmlspecialchars(kr_t('database.sql_runner'), ENT_QUOTES, 'UTF-8') ?></h2>
    <form method="post" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(kr_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="driver" value="<?= htmlspecialchars($driver, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="sqlite_path" value="<?= htmlspecialchars($sqlitePath, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="mysql_host" value="<?= htmlspecialchars($mysqlHost, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="mysql_port" value="<?= htmlspecialchars((string) $mysqlPort, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="mysql_database" value="<?= htmlspecialchars($mysqlDatabase, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="mysql_username" value="<?= htmlspecialchars($mysqlUsername, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="mysql_password" value="<?= htmlspecialchars($mysqlPassword, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="mysql_charset" value="<?= htmlspecialchars($mysqlCharset, ENT_QUOTES, 'UTF-8') ?>">
      <textarea name="sql" rows="8" style="width:100%;font-family:Consolas,monospace;">SELECT * FROM users LIMIT 20;</textarea>
      <button type="submit" name="action" value="run_select"><?= htmlspecialchars(kr_t('database.run_select'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>

    <?php if ($queryResult !== null): ?>
      <p><?= htmlspecialchars(kr_t('database.rows'), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) $queryResult['count'], ENT_QUOTES, 'UTF-8') ?><?= !empty($queryResult['limited']) ? ' ' . htmlspecialchars(kr_t('database.rows_limited'), ENT_QUOTES, 'UTF-8') : '' ?></p>
      <?php if ($queryColumns): ?>
        <table border="1" cellpadding="6" cellspacing="0">
          <thead>
            <tr>
              <?php foreach ($queryColumns as $col): ?>
                <th><?= htmlspecialchars((string) $col, ENT_QUOTES, 'UTF-8') ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($queryRows as $row): ?>
              <tr>
                <?php foreach ($queryColumns as $col): ?>
                  <td><?= htmlspecialchars((string) ($row[$col] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    <?php endif; ?>

    <hr>
    <h2><?= htmlspecialchars(kr_t('database.method_guide'), ENT_QUOTES, 'UTF-8') ?></h2>
    <ul>
      <li><code>kr("database.status")</code>: driver/connected/extensions status</li>
      <li><code>kr("database.pdo")</code>: returns <code>PDO|null</code></li>
      <li><code>/db/status</code>: quick JSON check endpoint</li>
    </ul>
  </main>
</body>
</html>
