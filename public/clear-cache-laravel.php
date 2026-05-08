<?php
/**
 * ============================================================================
 * CLEAR LARAVEL CACHE - Direct PHP Script
 * ============================================================================
 * 
 * Script này clear tất cả cache của Laravel mà không cần SSH
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Clear Laravel Cache</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #17a2b8; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
    .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px 10px 0; }
</style></head><body><div class='container'>";

echo "<h1>🧹 Clear Laravel Cache</h1>";

$rootDir = dirname(__DIR__);
$cacheDir = $rootDir . '/bootstrap/cache';
$storageCacheDir = $rootDir . '/storage/framework/cache';
$storageViewsDir = $rootDir . '/storage/framework/views';

$cleared = [];
$errors = [];

// Clear bootstrap/cache files
$cacheFiles = [
    'config.php',
    'routes.php',
    'services.php',
];

foreach ($cacheFiles as $file) {
    $filePath = $cacheDir . '/' . $file;
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            $cleared[] = "bootstrap/cache/$file";
        } else {
            $errors[] = "Không thể xóa: bootstrap/cache/$file";
        }
    }
}

// Clear storage/framework/cache
if (is_dir($storageCacheDir)) {
    $files = glob($storageCacheDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            if (unlink($file)) {
                $cleared[] = "storage/framework/cache/" . basename($file);
            }
        } elseif (is_dir($file)) {
            // Recursive delete for cache data directory
            $thisDir = $file;
            $dirFiles = glob($thisDir . '/*');
            foreach ($dirFiles as $dirFile) {
                if (is_file($dirFile)) {
                    unlink($dirFile);
                }
            }
            if (rmdir($thisDir)) {
                $cleared[] = "storage/framework/cache/" . basename($file) . " (directory)";
            }
        }
    }
}

// Clear storage/framework/views (compiled views)
if (is_dir($storageViewsDir)) {
    $viewFiles = glob($storageViewsDir . '/*.php');
    foreach ($viewFiles as $viewFile) {
        if (is_file($viewFile)) {
            if (unlink($viewFile)) {
                $cleared[] = "storage/framework/views/" . basename($viewFile);
            }
        }
    }
}

// Results
if (!empty($cleared)) {
    echo "<div class='success'>✅ Đã clear " . count($cleared) . " cache files:</div>";
    foreach ($cleared as $item) {
        echo "<div class='info'>  • $item</div>";
    }
} else {
    echo "<div class='info'>ℹ️ Không tìm thấy cache files để clear (có thể đã được clear trước đó)</div>";
}

if (!empty($errors)) {
    echo "<div class='error'>❌ Có lỗi khi clear cache:</div>";
    foreach ($errors as $error) {
        echo "<div class='error'>  • $error</div>";
    }
}

echo "<hr>";
echo "<p><a href='/' class='btn'>Thử truy cập trang chủ</a></p>";
echo "<p><a href='fix-redirect-simple.php' class='btn'>Quay lại Fix Redirect Loop</a></p>";

echo "</div></body></html>";

