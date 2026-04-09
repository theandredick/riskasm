<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_START', microtime(true));

// ── Autoloader ────────────────────────────────────────────────────────────────
require APP_ROOT . '/vendor/autoload.php';

// ── Environment ───────────────────────────────────────────────────────────────
$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->safeLoad();

// ── Health-check endpoint (pre-routing, no auth required) ─────────────────────
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = rtrim($requestPath, '/') ?: '/';

if ($requestPath === '/healthcheck') {
    header('Content-Type: application/json; charset=utf-8');

    $health = [
        'status'      => 'ok',
        'timestamp'   => date('c'),
        'php_version' => PHP_VERSION,
        'env_mode'    => $_ENV['APP_ENV'] ?? 'unknown',
        'db'          => healthCheckDb(),
    ];

    if ($health['db']['status'] !== 'ok') {
        $health['status'] = 'degraded';
        http_response_code(503);
    }

    echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── Bootstrap application ─────────────────────────────────────────────────────
require APP_ROOT . '/src/Config/config.php';

$router = new App\Core\Router();
require APP_ROOT . '/src/Config/routes.php';

$request  = new App\Core\Request();
$response = $router->dispatch($request);
$response->send();

// ── Health-check helper ───────────────────────────────────────────────────────
function healthCheckDb(): array
{
    try {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $_ENV['DB_HOST']     ?? 'localhost',
            $_ENV['DB_PORT']     ?? '5432',
            $_ENV['DB_NAME']     ?? 'riskasm'
        );
        $pdo = new PDO(
            $dsn,
            $_ENV['DB_USER']     ?? '',
            $_ENV['DB_PASSWORD'] ?? '',
            [PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $row = $pdo->query('SELECT version() AS v')->fetch(PDO::FETCH_ASSOC);
        return ['status' => 'ok', 'version' => $row['v'] ?? 'unknown'];
    } catch (Throwable $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}
