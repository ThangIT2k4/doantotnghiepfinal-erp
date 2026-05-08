<?php
/**
 * ============================================================================
 * ENABLE EnsureSecureSession Middleware
 * ============================================================================
 * 
 * Script này kiểm tra xem middleware đã được enable chưa
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Enable Middleware</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #17a2b8; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
    .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px 10px 0; }
</style></head><body><div class='container'>";

echo "<h1>✅ Khôi phục EnsureSecureSession Middleware</h1>";

$rootDir = dirname(__DIR__);
$bootstrapApp = $rootDir . '/bootstrap/app.php';

if (file_exists($bootstrapApp)) {
    $content = file_get_contents($bootstrapApp);
    
    // Check if middleware is enabled (not commented)
    if (preg_match('/\$middleware->append\([^)]*EnsureSecureSession[^)]*\);/', $content) && 
        !preg_match('/\/\/.*\$middleware->append.*EnsureSecureSession/', $content)) {
        echo "<div class='success'>✅ EnsureSecureSession middleware đã được enable!</div>";
        echo "<div class='info'>Middleware đang hoạt động bình thường.</div>";
    } else {
        echo "<div class='info'>⚠️ Middleware có thể đang bị comment. Vui lòng kiểm tra file bootstrap/app.php</div>";
    }
    
    // Show current status
    echo "<h2>📋 Trạng thái hiện tại:</h2>";
    echo "<div class='info'>";
    echo "File: <code>bootstrap/app.php</code><br>";
    if (strpos($content, 'EnsureSecureSession') !== false) {
        if (strpos($content, '// TEMPORARILY DISABLED') !== false || 
            preg_match('/\/\/.*\$middleware->append.*EnsureSecureSession/', $content)) {
            echo "Status: <strong style='color: orange;'>⚠️ DISABLED (commented)</strong><br>";
        } else {
            echo "Status: <strong style='color: green;'>✅ ENABLED</strong><br>";
        }
    } else {
        echo "Status: <strong style='color: red;'>❌ NOT FOUND</strong><br>";
    }
    echo "</div>";
} else {
    echo "<div class='error'>❌ File bootstrap/app.php không tồn tại</div>";
}

echo "<hr>";
echo "<h2>📝 Hướng dẫn:</h2>";
echo "<div class='info'>";
echo "<strong>1. Clear cache sau khi enable middleware:</strong><br>";
echo "   - Truy cập: <a href='clear-cache-laravel.php'>clear-cache-laravel.php</a><br>";
echo "   - Hoặc chạy: <code>php artisan config:clear && php artisan cache:clear</code><br><br>";

echo "<strong>2. Kiểm tra .env:</strong><br>";
echo "   - <code>APP_URL=https://ZoroRMS.click</code><br>";
echo "   - <code>SESSION_SECURE_COOKIE=null</code> hoặc <code>true</code> (cho HTTPS)<br><br>";

echo "<strong>3. Test website:</strong><br>";
echo "   - <a href='/' class='btn'>Truy cập trang chủ</a><br>";
echo "</div>";

echo "</div></body></html>";

