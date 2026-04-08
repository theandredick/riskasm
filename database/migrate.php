<?php

/**
 * Database migration runner.
 *
 * Usage:
 *   php database/migrate.php            — apply all pending migrations
 *   php database/migrate.php --fresh    — DROP all tables and re-run all migrations
 *   php database/migrate.php --status   — list applied migrations
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->safeLoad();

// ── DB connection ──────────────────────────────────────────────────────────────
$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s',
    $_ENV['DB_HOST']     ?? 'localhost',
    $_ENV['DB_PORT']     ?? '5432',
    $_ENV['DB_NAME']     ?? 'riskasm'
);

try {
    $pdo = new PDO(
        $dsn,
        $_ENV['DB_USER']     ?? '',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo "❌  Cannot connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

// ── Parse flags ────────────────────────────────────────────────────────────────
$fresh  = in_array('--fresh',  $argv ?? [], true);
$status = in_array('--status', $argv ?? [], true);

// ── Migrations tracking table ──────────────────────────────────────────────────
$pdo->exec("
    CREATE TABLE IF NOT EXISTS _migrations (
        id         SERIAL PRIMARY KEY,
        filename   TEXT UNIQUE NOT NULL,
        applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    )
");

// ── --fresh: drop everything and start clean ───────────────────────────────────
if ($fresh) {
    echo "⚠️   --fresh: dropping all user-created tables and types …\n";
    $pdo->exec("DROP SCHEMA public CASCADE; CREATE SCHEMA public;");
    $pdo->exec("GRANT ALL ON SCHEMA public TO public;");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS _migrations (
            id         SERIAL PRIMARY KEY,
            filename   TEXT UNIQUE NOT NULL,
            applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        )
    ");
    echo "    Schema reset.\n";
}

// ── --status: print which migrations have been applied ────────────────────────
if ($status) {
    $applied = $pdo->query("SELECT filename, applied_at FROM _migrations ORDER BY id")
                   ->fetchAll(PDO::FETCH_ASSOC);

    $files = glob(APP_ROOT . '/database/migrations/*.sql');
    sort($files);

    echo "\n📋  Migration status:\n";
    $appliedNames = array_column($applied, 'filename');
    foreach ($files as $file) {
        $name = basename($file);
        $tick = in_array($name, $appliedNames) ? '✅' : '⏳';
        echo "  $tick  $name\n";
    }
    echo "\n";
    exit(0);
}

// ── Apply pending migrations ───────────────────────────────────────────────────
$files = glob(APP_ROOT . '/database/migrations/*.sql');
sort($files);

$applied = $pdo->query("SELECT filename FROM _migrations")
               ->fetchAll(PDO::FETCH_COLUMN);

$pending = array_filter($files, fn($f) => !in_array(basename($f), $applied));

if (empty($pending)) {
    echo "✅  All migrations are already applied. Nothing to do.\n";
    exit(0);
}

echo "🚀  Running " . count($pending) . " pending migration(s)…\n\n";

foreach ($pending as $file) {
    $name = basename($file);
    $sql  = file_get_contents($file);

    echo "  ▶  $name … ";

    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $stmt = $pdo->prepare("INSERT INTO _migrations (filename) VALUES (?)");
        $stmt->execute([$name]);
        $pdo->commit();
        echo "done.\n";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "FAILED ❌\n";
        echo "     Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "\n✅  All migrations applied successfully.\n";
