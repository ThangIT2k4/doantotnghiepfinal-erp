<?php
/**
 * Script kiểm tra cấu hình database từ .env
 * Chạy: php check-database-config.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DATABASE CONFIGURATION ===" . PHP_EOL;
echo "DB_HOST: " . env('DB_HOST', 'not set') . PHP_EOL;
echo "DB_PORT: " . env('DB_PORT', 'not set') . PHP_EOL;
echo "DB_DATABASE: " . env('DB_DATABASE', 'not set') . PHP_EOL;
echo "DB_USERNAME: " . env('DB_USERNAME', 'not set') . PHP_EOL;
echo "DB_PASSWORD: " . (env('DB_PASSWORD') ? '***' : 'not set') . PHP_EOL;
echo PHP_EOL;

echo "=== FROM CONFIG ===" . PHP_EOL;
echo "Database: " . config('database.connections.mysql.database') . PHP_EOL;
echo "Host: " . config('database.connections.mysql.host') . PHP_EOL;
echo "Username: " . config('database.connections.mysql.username') . PHP_EOL;
echo PHP_EOL;

echo "=== TEST CONNECTION ===" . PHP_EOL;
try {
    $pdo = DB::connection()->getPdo();
    echo "✓ Connection successful!" . PHP_EOL;
    echo "Connected to: " . DB::connection()->getDatabaseName() . PHP_EOL;
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . PHP_EOL;
}

