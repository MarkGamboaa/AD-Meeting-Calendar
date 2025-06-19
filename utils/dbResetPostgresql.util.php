<?php
declare(strict_types=1);

// BASE_PATH should be defined if not yet
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/../');
}

// 1) Composer autoload
require BASE_PATH . 'vendor/autoload.php';

// 2) (Optional) Composer bootstrap (if you have it)
if (file_exists(BASE_PATH . 'bootstrap.php')) {
    require BASE_PATH . 'bootstrap.php';
}

// 3) Load env
require_once BASE_PATH . 'utils/envSetter.util.php';

echo "✅ Connected to PostgreSQL.\n";

// ——— Connect to PostgreSQL ———
$dsn = "pgsql:host={$pgConfig['host']};port={$pgConfig['port']};dbname={$pgConfig['db']}";
$pdo = new PDO($dsn, $pgConfig['user'], $pgConfig['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// ——— Apply schemas before truncating ———
echo "📦 Applying schema files...\n";
$schemaFiles = [
    'database/user.model.sql',
    'database/meeting.model.sql',
    'database/meeting_users.model.sql',
    'database/tasks.model.sql'
];

foreach ($schemaFiles as $file) {
    echo "📄 Applying $file...\n";
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("❌ Could not read $file");
    }
    $pdo->exec($sql);
}

echo "🔁 Truncating tables…\n";
// Be sure to TRUNCATE in dependency-safe order (child → parent)
$tables = ['meeting_users', 'tasks', 'meetings', 'users'];
foreach ($tables as $table) {
    $pdo->exec("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE;");
}

echo "✅ Tables reset successfully.\n";
