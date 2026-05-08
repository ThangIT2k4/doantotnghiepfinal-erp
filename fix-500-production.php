<?php
/**
 * Script kiểm tra và sửa lỗi HTTP 500 trên production
 * Chạy script này trên server production để kiểm tra và sửa các vấn đề phổ biến
 */

echo "========================================\n";
echo "  KIỂM TRA VÀ SỬA LỖI HTTP 500\n";
echo "========================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Kiểm tra PHP version
echo "[1/10] Kiểm tra PHP version...\n";
$phpVersion = phpversion();
if (version_compare($phpVersion, '8.2.0', '>=')) {
    $success[] = "PHP version: $phpVersion (OK)";
    echo "  ✓ PHP version: $phpVersion\n";
} else {
    $errors[] = "PHP version $phpVersion không đủ. Cần PHP >= 8.2.0";
    echo "  ✗ PHP version: $phpVersion (Cần >= 8.2.0)\n";
}

// 2. Kiểm tra composer.json có package dompdf
echo "\n[2/10] Kiểm tra composer.json...\n";
if (file_exists('composer.json')) {
    $composer = json_decode(file_get_contents('composer.json'), true);
    if (isset($composer['require']['barryvdh/laravel-dompdf'])) {
        $success[] = "Package barryvdh/laravel-dompdf có trong composer.json";
        echo "  ✓ Package barryvdh/laravel-dompdf có trong composer.json\n";
    } else {
        $errors[] = "Package barryvdh/laravel-dompdf chưa có trong composer.json";
        echo "  ✗ Package barryvdh/laravel-dompdf chưa có trong composer.json\n";
    }
} else {
    $errors[] = "File composer.json không tồn tại";
    echo "  ✗ File composer.json không tồn tại\n";
}

// 3. Kiểm tra vendor/barryvdh/laravel-dompdf
echo "\n[3/10] Kiểm tra package đã được cài đặt...\n";
if (file_exists('vendor/barryvdh/laravel-dompdf')) {
    $success[] = "Package barryvdh/laravel-dompdf đã được cài đặt";
    echo "  ✓ Package barryvdh/laravel-dompdf đã được cài đặt\n";
} else {
    $errors[] = "Package barryvdh/laravel-dompdf chưa được cài đặt. Cần chạy: composer install --no-dev";
    echo "  ✗ Package barryvdh/laravel-dompdf chưa được cài đặt\n";
    echo "    → Cần chạy: composer install --no-dev --optimize-autoloader\n";
}

// 4. Kiểm tra autoload files
echo "\n[4/10] Kiểm tra autoload files...\n";
if (file_exists('vendor/autoload.php')) {
    $success[] = "File vendor/autoload.php tồn tại";
    echo "  ✓ File vendor/autoload.php tồn tại\n";
} else {
    $errors[] = "File vendor/autoload.php không tồn tại. Cần chạy: composer dump-autoload";
    echo "  ✗ File vendor/autoload.php không tồn tại\n";
    echo "    → Cần chạy: composer dump-autoload --optimize\n";
}

// 5. Kiểm tra storage permissions
echo "\n[5/10] Kiểm tra quyền thư mục storage...\n";
$storageDirs = [
    'storage',
    'storage/app',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache'
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            $errors[] = "Không thể tạo thư mục: $dir";
            echo "  ✗ Không thể tạo thư mục: $dir\n";
        } else {
            echo "  ✓ Đã tạo thư mục: $dir\n";
        }
    } else {
        if (!is_writable($dir)) {
            $warnings[] = "Thư mục $dir không có quyền ghi";
            echo "  ⚠ Thư mục $dir không có quyền ghi (cần chmod 755 hoặc 775)\n";
        } else {
            echo "  ✓ Thư mục $dir có quyền ghi\n";
        }
    }
}

// 6. Kiểm tra .env file
echo "\n[6/10] Kiểm tra file .env...\n";
if (file_exists('.env')) {
    $success[] = "File .env tồn tại";
    echo "  ✓ File .env tồn tại\n";
    
    // Kiểm tra APP_DEBUG
    $envContent = file_get_contents('.env');
    if (strpos($envContent, 'APP_DEBUG=true') !== false) {
        $warnings[] = "APP_DEBUG=true trong production. Nên đặt APP_DEBUG=false";
        echo "  ⚠ APP_DEBUG=true (nên đặt false trong production)\n";
    } else {
        echo "  ✓ APP_DEBUG không phải true\n";
    }
} else {
    $errors[] = "File .env không tồn tại";
    echo "  ✗ File .env không tồn tại\n";
}

// 7. Kiểm tra cache files
echo "\n[7/10] Kiểm tra cache files...\n";
$cacheFiles = [
    'bootstrap/cache/config.php',
    'bootstrap/cache/routes.php',
    'bootstrap/cache/services.php',
    'bootstrap/cache/packages.php'
];

foreach ($cacheFiles as $file) {
    if (file_exists($file)) {
        if (!is_writable($file)) {
            $warnings[] = "File cache $file không có quyền ghi";
            echo "  ⚠ File $file không có quyền ghi\n";
        } else {
            echo "  ✓ File $file tồn tại và có quyền ghi\n";
        }
    } else {
        echo "  ℹ File $file chưa tồn tại (sẽ được tạo khi chạy cache commands)\n";
    }
}

// 8. Kiểm tra PHP extensions cần thiết
echo "\n[8/10] Kiểm tra PHP extensions...\n";
$requiredExtensions = ['mbstring', 'xml', 'dom', 'gd', 'zip', 'curl'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "  ✓ Extension $ext đã được load\n";
    } else {
        $errors[] = "Extension PHP $ext chưa được cài đặt";
        echo "  ✗ Extension $ext chưa được cài đặt\n";
    }
}

// 9. Kiểm tra log file
echo "\n[9/10] Kiểm tra log file...\n";
$logFile = 'storage/logs/laravel.log';
if (file_exists($logFile)) {
    $logSize = filesize($logFile);
    echo "  ✓ Log file tồn tại (kích thước: " . number_format($logSize / 1024, 2) . " KB)\n";
    
    if ($logSize > 10 * 1024 * 1024) { // > 10MB
        $warnings[] = "Log file quá lớn (>10MB). Nên xóa hoặc rotate";
        echo "  ⚠ Log file quá lớn, nên xóa hoặc rotate\n";
    }
    
    // Đọc 50 dòng cuối cùng
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    echo "\n  Các lỗi gần đây (50 dòng cuối):\n";
    foreach ($lastLines as $line) {
        if (stripos($line, 'ERROR') !== false || stripos($line, 'Exception') !== false) {
            echo "    " . trim($line) . "\n";
        }
    }
} else {
    echo "  ℹ Log file chưa tồn tại (sẽ được tạo khi có lỗi)\n";
}

// 10. Tóm tắt và đề xuất
echo "\n[10/10] Tóm tắt...\n";
echo "\n========================================\n";
echo "  KẾT QUẢ KIỂM TRA\n";
echo "========================================\n\n";

if (count($success) > 0) {
    echo "✓ THÀNH CÔNG (" . count($success) . "):\n";
    foreach ($success as $msg) {
        echo "  - $msg\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠ CẢNH BÁO (" . count($warnings) . "):\n";
    foreach ($warnings as $msg) {
        echo "  - $msg\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "✗ LỖI (" . count($errors) . "):\n";
    foreach ($errors as $msg) {
        echo "  - $msg\n";
    }
    echo "\n";
}

// Đề xuất các lệnh cần chạy
echo "========================================\n";
echo "  CÁC LỆNH CẦN CHẠY ĐỂ SỬA LỖI\n";
echo "========================================\n\n";

if (count($errors) > 0 || !file_exists('vendor/barryvdh/laravel-dompdf')) {
    echo "1. Cài đặt dependencies:\n";
    echo "   composer install --no-dev --optimize-autoloader --no-interaction\n\n";
}

echo "2. Clear tất cả cache:\n";
echo "   php artisan config:clear\n";
echo "   php artisan cache:clear\n";
echo "   php artisan route:clear\n";
echo "   php artisan view:clear\n\n";

echo "3. Regenerate autoload:\n";
echo "   composer dump-autoload --optimize\n\n";

echo "4. Cache lại cho production:\n";
echo "   php artisan config:cache\n";
echo "   php artisan route:cache\n";
echo "   php artisan view:cache\n\n";

echo "5. Kiểm tra package discovery:\n";
echo "   php artisan package:discover\n\n";

echo "6. Kiểm tra log file để xem lỗi chi tiết:\n";
echo "   tail -n 100 storage/logs/laravel.log\n";
echo "   hoặc\n";
echo "   cat storage/logs/laravel.log | tail -n 100\n\n";

if (count($errors) === 0 && count($warnings) === 0) {
    echo "========================================\n";
    echo "  ✓ TẤT CẢ KIỂM TRA ĐỀU PASS!\n";
    echo "========================================\n";
    echo "\nNếu vẫn gặp lỗi 500, hãy kiểm tra:\n";
    echo "1. Log file: storage/logs/laravel.log\n";
    echo "2. Web server error log (Apache/Nginx)\n";
    echo "3. PHP error log\n";
    echo "4. Đảm bảo APP_DEBUG=false trong .env\n";
} else {
    echo "========================================\n";
    echo "  ⚠ CẦN SỬA CÁC LỖI TRƯỚC\n";
    echo "========================================\n";
}

echo "\n";


