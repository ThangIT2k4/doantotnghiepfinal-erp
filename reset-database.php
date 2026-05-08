<?php
/**
 * Script reset toàn bộ dữ liệu và đặt auto increment = 1
 * 
 * CẢNH BÁO: Script này sẽ XÓA TẤT CẢ DỮ LIỆU trong database!
 * 
 * Cách sử dụng:
 * php reset-database.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "========================================" . PHP_EOL;
echo "  RESET DATABASE - XÓA TẤT CẢ DỮ LIỆU" . PHP_EOL;
echo "========================================" . PHP_EOL;
echo PHP_EOL;

// Xác nhận
echo "⚠️  CẢNH BÁO: Script này sẽ XÓA TẤT CẢ DỮ LIỆU trong database!" . PHP_EOL;
echo "Database: " . config('database.connections.mysql.database') . PHP_EOL;
echo PHP_EOL;

$confirm = readline("Bạn có chắc chắn muốn tiếp tục? (yes/no): ");

if (strtolower($confirm) !== 'yes') {
    echo "Đã hủy." . PHP_EOL;
    exit(0);
}

echo PHP_EOL;
echo "Bắt đầu reset database..." . PHP_EOL;
echo PHP_EOL;

$success = false;
$deletedCount = 0;
$errors = [];

try {
    // Tắt foreign key checks tạm thời
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    
    // Lấy danh sách tất cả các bảng
    $tables = DB::select('SHOW TABLES');
    $databaseName = config('database.connections.mysql.database');
    $tableKey = "Tables_in_{$databaseName}";
    
    $tableNames = [];
    foreach ($tables as $table) {
        $tableName = $table->$tableKey;
        // Bỏ qua các bảng migrations (giữ lại để biết migrations đã chạy)
        if ($tableName !== 'migrations') {
            $tableNames[] = $tableName;
        }
    }
    
    echo "Tìm thấy " . count($tableNames) . " bảng (trừ migrations)" . PHP_EOL;
    echo PHP_EOL;
    
    // Xóa dữ liệu và reset auto increment
    foreach ($tableNames as $tableName) {
        try {
            // Đếm số records trước khi xóa
            $count = DB::table($tableName)->count();
            
            // Xóa tất cả dữ liệu
            DB::table($tableName)->truncate();
            
            // Reset auto increment về 1
            DB::statement("ALTER TABLE `{$tableName}` AUTO_INCREMENT = 1");
            
            echo "  ✓ {$tableName}: Đã xóa {$count} records, reset AUTO_INCREMENT = 1" . PHP_EOL;
            $deletedCount++;
        } catch (\Exception $e) {
            $errors[] = ['table' => $tableName, 'error' => $e->getMessage()];
            echo "  ✗ {$tableName}: Lỗi - " . $e->getMessage() . PHP_EOL;
        }
    }
    
    // Bật lại foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    
    $success = true;
    
} catch (\Exception $e) {
    // Đảm bảo bật lại foreign key checks ngay cả khi có lỗi
    try {
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    } catch (\Exception $e2) {
        // Ignore
    }
    
    $errors[] = ['table' => 'GENERAL', 'error' => $e->getMessage()];
}

// Hiển thị kết quả
echo PHP_EOL;
if ($success) {
    echo "========================================" . PHP_EOL;
    echo "  RESET HOÀN TẤT!" . PHP_EOL;
    echo "========================================" . PHP_EOL;
    echo "Đã xóa dữ liệu và reset AUTO_INCREMENT cho {$deletedCount} bảng." . PHP_EOL;
    
    if (!empty($errors)) {
        echo PHP_EOL;
        echo "Có " . count($errors) . " lỗi xảy ra:" . PHP_EOL;
        foreach ($errors as $error) {
            echo "  - {$error['table']}: {$error['error']}" . PHP_EOL;
        }
    }
    
    echo PHP_EOL;
    echo "Bước tiếp theo:" . PHP_EOL;
    echo "  1. Chạy migrations: php artisan migrate" . PHP_EOL;
    echo "  2. Chạy seeders: php artisan db:seed" . PHP_EOL;
    echo PHP_EOL;
    
    exit(0);
} else {
    echo "========================================" . PHP_EOL;
    echo "  LỖI!" . PHP_EOL;
    echo "========================================" . PHP_EOL;
    echo "Có lỗi xảy ra trong quá trình reset:" . PHP_EOL;
    foreach ($errors as $error) {
        echo "  - {$error['table']}: {$error['error']}" . PHP_EOL;
    }
    echo PHP_EOL;
    exit(1);
}

