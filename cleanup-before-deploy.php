<?php
/**
 * ============================================================================
 * CLEANUP BEFORE DEPLOY - Laravel to cPanel
 * ============================================================================
 * 
 * Script này dọn dẹp tất cả cache files trước khi deploy lên cPanel
 * để tránh lỗi 405 Method Not Allowed và các lỗi liên quan đến cache.
 * 
 * CÁCH SỬ DỤNG:
 * 1. Chạy script này TRƯỚC KHI deploy/upload lên cPanel
 * 2. php cleanup-before-deploy.php
 * 3. Hoặc double-click file này (nếu PHP đã cấu hình)
 * 
 * @author Laravel Deployment Helper
 * @version 1.0
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ANSI color codes for terminal output
class Color {
    const RESET = "\033[0m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const BOLD = "\033[1m";
}

// Check if running in CLI or browser
$isCLI = php_sapi_name() === 'cli';

function printLine($message, $color = Color::WHITE, $bold = false) {
    global $isCLI;
    
    if ($isCLI) {
        echo ($bold ? Color::BOLD : '') . $color . $message . Color::RESET . PHP_EOL;
    } else {
        // HTML output
        $style = "margin: 5px 0; padding: 10px; border-radius: 4px;";
        if ($color === Color::GREEN) $style .= "background: #d4edda; color: #155724;";
        elseif ($color === Color::RED) $style .= "background: #f8d7da; color: #721c24;";
        elseif ($color === Color::YELLOW) $style .= "background: #fff3cd; color: #856404;";
        elseif ($color === Color::BLUE) $style .= "background: #d1ecf1; color: #0c5460;";
        elseif ($color === Color::CYAN) $style .= "background: #e7f3ff; color: #004085;";
        
        echo "<div style='$style'>" . htmlspecialchars($message) . "</div>";
    }
}

function printHeader($message) {
    global $isCLI;
    if ($isCLI) {
        echo PHP_EOL;
        echo Color::BOLD . Color::CYAN . str_repeat("=", 70) . Color::RESET . PHP_EOL;
        echo Color::BOLD . Color::CYAN . $message . Color::RESET . PHP_EOL;
        echo Color::BOLD . Color::CYAN . str_repeat("=", 70) . Color::RESET . PHP_EOL;
    } else {
        echo "<h2 style='color: #3490dc; border-bottom: 3px solid #3490dc; padding-bottom: 10px; margin-top: 30px;'>$message</h2>";
    }
}

function printSuccess($message) {
    printLine("✅ $message", Color::GREEN);
}

function printError($message) {
    printLine("❌ $message", Color::RED);
}

function printWarning($message) {
    printLine("⚠️  $message", Color::YELLOW);
}

function printInfo($message) {
    printLine("ℹ️  $message", Color::BLUE);
}

// Start HTML if browser
if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
    echo "<title>Cleanup Before Deploy - Laravel</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:30px auto;padding:20px;background:#f5f5f5;}";
    echo ".container{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
    echo "h1{color:#e3342f;border-bottom:3px solid #e3342f;padding-bottom:10px;}";
    echo "code{background:#f4f4f4;padding:2px 6px;border-radius:3px;font-family:'Courier New',monospace;}";
    echo "</style></head><body><div class='container'>";
    echo "<h1>🧹 Laravel Cleanup Before Deploy</h1>";
}

printHeader("🚀 Laravel Cleanup Script - Chuẩn bị Deploy lên cPanel");

$rootDir = __DIR__;
$stats = [
    'deleted_files' => 0,
    'deleted_dirs' => 0,
    'errors' => 0,
    'warnings' => 0,
];

// ============================================================================
// STEP 1: Clean Bootstrap Cache
// ============================================================================
printHeader("📦 Bước 1: Dọn dẹp Bootstrap Cache");

$bootstrapCache = $rootDir . '/bootstrap/cache';
$cacheFiles = [
    'routes-v7.php',
    'config.php',
    'packages.php',
    'services.php',
];

foreach ($cacheFiles as $file) {
    $filePath = $bootstrapCache . '/' . $file;
    if (file_exists($filePath)) {
        if (is_writable($filePath)) {
            if (@unlink($filePath)) {
                printSuccess("Đã xóa: bootstrap/cache/$file");
                $stats['deleted_files']++;
            } else {
                printError("Không thể xóa: bootstrap/cache/$file");
                $stats['errors']++;
            }
        } else {
            printError("Không có quyền xóa: bootstrap/cache/$file");
            $stats['errors']++;
        }
    } else {
        printInfo("File không tồn tại (OK): bootstrap/cache/$file");
    }
}

// Delete all other .php files in bootstrap/cache except .gitignore
$allBootstrapCache = glob($bootstrapCache . '/*.php');
if ($allBootstrapCache) {
    foreach ($allBootstrapCache as $file) {
        if (basename($file) !== '.gitignore') {
            if (@unlink($file)) {
                printSuccess("Đã xóa: " . str_replace($rootDir, '', $file));
                $stats['deleted_files']++;
            }
        }
    }
}

// ============================================================================
// STEP 2: Clean Compiled Views
// ============================================================================
printHeader("🎨 Bước 2: Dọn dẹp Compiled Views");

$viewsDir = $rootDir . '/storage/framework/views';
if (is_dir($viewsDir)) {
    $viewFiles = glob($viewsDir . '/*.php');
    if ($viewFiles && count($viewFiles) > 0) {
        $deletedViews = 0;
        foreach ($viewFiles as $file) {
            if (is_file($file) && basename($file) !== '.gitignore') {
                if (@unlink($file)) {
                    $deletedViews++;
                    $stats['deleted_files']++;
                }
            }
        }
        if ($deletedViews > 0) {
            printSuccess("Đã xóa $deletedViews file(s) từ storage/framework/views/");
        } else {
            printInfo("Không có compiled views nào cần xóa");
        }
    } else {
        printInfo("Thư mục views đã sạch");
    }
} else {
    printWarning("Thư mục views không tồn tại: storage/framework/views/");
    $stats['warnings']++;
}

// ============================================================================
// STEP 3: Clean Application Cache
// ============================================================================
printHeader("💾 Bước 3: Dọn dẹp Application Cache");

$cacheDataDir = $rootDir . '/storage/framework/cache/data';
if (is_dir($cacheDataDir)) {
    $deletedCache = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDataDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getFilename() !== '.gitignore') {
            if (@unlink($file->getRealPath())) {
                $deletedCache++;
                $stats['deleted_files']++;
            }
        }
    }
    
    if ($deletedCache > 0) {
        printSuccess("Đã xóa $deletedCache file(s) cache data");
    } else {
        printInfo("Không có cache data nào cần xóa");
    }
} else {
    printInfo("Thư mục cache data không tồn tại (OK)");
}

// ============================================================================
// STEP 4: Clean Session Files
// ============================================================================
printHeader("🔐 Bước 4: Dọn dẹp Session Files");

$sessionDir = $rootDir . '/storage/framework/sessions';
if (is_dir($sessionDir)) {
    $sessionFiles = glob($sessionDir . '/*');
    $deletedSessions = 0;
    
    if ($sessionFiles) {
        foreach ($sessionFiles as $file) {
            if (is_file($file) && basename($file) !== '.gitignore') {
                if (@unlink($file)) {
                    $deletedSessions++;
                    $stats['deleted_files']++;
                }
            }
        }
    }
    
    if ($deletedSessions > 0) {
        printSuccess("Đã xóa $deletedSessions session file(s)");
    } else {
        printInfo("Không có session nào cần xóa");
    }
} else {
    printInfo("Thư mục sessions không tồn tại (OK)");
}

// ============================================================================
// STEP 5: Verify Critical Directories
// ============================================================================
printHeader("🔍 Bước 5: Kiểm tra cấu trúc thư mục");

$requiredDirs = [
    'bootstrap/cache' => true,
    'storage/app' => true,
    'storage/framework/cache' => true,
    'storage/framework/sessions' => true,
    'storage/framework/views' => true,
    'storage/logs' => true,
];

foreach ($requiredDirs as $dir => $required) {
    $fullPath = $rootDir . '/' . $dir;
    if (is_dir($fullPath)) {
        if (is_writable($fullPath)) {
            printSuccess("Thư mục tồn tại và writable: $dir");
        } else {
            printWarning("Thư mục không writable: $dir");
            $stats['warnings']++;
        }
    } else {
        if ($required) {
            printError("Thư mục bắt buộc không tồn tại: $dir");
            $stats['errors']++;
        }
    }
}

// ============================================================================
// STEP 6: Check .env Configuration
// ============================================================================
printHeader("⚙️  Bước 6: Kiểm tra cấu hình .env");

$envFile = $rootDir . '/.env';
if (file_exists($envFile)) {
    printSuccess("File .env tồn tại");
    
    $envContent = file_get_contents($envFile);
    
    // Check important settings
    if (strpos($envContent, 'APP_KEY=') !== false && 
        preg_match('/APP_KEY=base64:[A-Za-z0-9+\/=]+/', $envContent)) {
        printSuccess("APP_KEY đã được generate");
    } else {
        printWarning("APP_KEY chưa được generate - chạy: php artisan key:generate");
        $stats['warnings']++;
    }
    
    if (strpos($envContent, 'APP_ENV=production') !== false) {
        printInfo("APP_ENV=production (Sẵn sàng cho production)");
    } else {
        printWarning("APP_ENV không phải production (OK cho staging/test)");
    }
    
    if (strpos($envContent, 'APP_DEBUG=false') !== false) {
        printInfo("APP_DEBUG=false (Đúng cho production)");
    } else {
        printWarning("APP_DEBUG=true (Nên đổi thành false trên production)");
        $stats['warnings']++;
    }
} else {
    printError("File .env không tồn tại!");
    printInfo("Copy .env.example thành .env và cấu hình");
    $stats['errors']++;
}

// ============================================================================
// SUMMARY
// ============================================================================
printHeader("📊 Tổng kết");

if ($stats['errors'] === 0) {
    printSuccess("✅ Cleanup hoàn tất thành công!");
    printInfo("📁 Đã xóa: {$stats['deleted_files']} file(s)");
    if ($stats['warnings'] > 0) {
        printWarning("⚠️  Cảnh báo: {$stats['warnings']} vấn đề cần chú ý");
    }
} else {
    printError("❌ Có {$stats['errors']} lỗi xảy ra");
    printInfo("📁 Đã xóa: {$stats['deleted_files']} file(s)");
    printWarning("⚠️  Cảnh báo: {$stats['warnings']} vấn đề");
}

// ============================================================================
// DEPLOYMENT CHECKLIST
// ============================================================================
printHeader("📋 Checklist Deploy lên cPanel");

$checklist = [
    "✅ Đã chạy cleanup script này",
    "✅ File .env đã cấu hình đúng",
    "✅ APP_ENV=production, APP_DEBUG=false",
    "✅ Không có file cache trong bootstrap/cache/",
    "✅ Database credentials đúng trên server",
    "✅ APP_URL khớp với domain/subdomain",
    "⏭️  Zip toàn bộ project (hoặc upload qua FTP)",
    "⏭️  Upload lên cPanel",
    "⏭️  Extract trên server",
    "⏭️  Cấu hình Document Root → public/",
    "⏭️  Set permissions: storage/ và bootstrap/cache/ → 755",
    "⏭️  Test website",
];

foreach ($checklist as $item) {
    printInfo($item);
}

// ============================================================================
// IMPORTANT NOTES
// ============================================================================
printHeader("⚠️  Lưu ý quan trọng");

printWarning("❌ KHÔNG chạy các lệnh sau trên cPanel:");
printLine("   - php artisan route:cache", Color::RED);
printLine("   - php artisan config:cache", Color::RED);
printLine("   - php artisan view:cache", Color::RED);

printInfo("✅ Chỉ chạy trên local khi develop:");
printLine("   - php artisan route:clear", Color::GREEN);
printLine("   - php artisan config:clear", Color::GREEN);
printLine("   - php artisan cache:clear", Color::GREEN);

// ============================================================================
// NEXT STEPS
// ============================================================================
printHeader("🎯 Bước tiếp theo");

printInfo("1️⃣  Kiểm tra lại các cảnh báo (nếu có)");
printInfo("2️⃣  Zip/Upload project lên cPanel");
printInfo("3️⃣  Nếu gặp lỗi 405, upload file: fix-405-route-error.php");
printInfo("4️⃣  Test website kỹ lưỡng");

// End HTML if browser
if (!$isCLI) {
    echo "<div style='margin-top: 30px; padding: 20px; background: #e7f3ff; border-radius: 4px;'>";
    echo "<h3 style='color: #004085; margin-top: 0;'>🎉 Cleanup hoàn tất!</h3>";
    echo "<p>Project đã sẵn sàng để deploy lên cPanel.</p>";
    echo "<p><strong>Lưu ý:</strong> Đóng cửa sổ này và tiến hành deploy theo checklist ở trên.</p>";
    echo "</div>";
    echo "<div style='text-align: center; color: #6c757d; font-size: 0.9em; margin-top: 30px;'>";
    echo "Laravel Cleanup Script v1.0 | " . date('Y-m-d H:i:s');
    echo "</div>";
    echo "</div></body></html>";
}

// Exit with appropriate code
exit($stats['errors'] > 0 ? 1 : 0);
?>

