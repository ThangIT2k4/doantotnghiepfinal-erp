<?php
/**
 * ============================================================================
 * FIX REDIRECT LOOP - SIMPLE VERSION (No Laravel dependencies)
 * ============================================================================
 * 
 * Script này KHÔNG phụ thuộc Laravel, chạy trực tiếp để fix redirect loop
 * 
 * CÁCH DÙNG:
 * 1. File này đã ở trong public/
 * 2. Truy cập: https://ZoroRMS.click/fix-redirect-simple.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<title>Fix Redirect Loop - Simple</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
    h2 { color: #555; margin-top: 30px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #17a2b8; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
    pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px 10px 0; }
    .btn:hover { background: #5568d3; }
    .btn-danger { background: #dc3545; }
    .btn-danger:hover { background: #c82333; }
</style></head><body><div class='container'>";

echo "<h1>🔧 Fix Redirect Loop - Simple Version</h1>";

// Get root directory (parent of public)
$rootDir = dirname(__DIR__);
$envFile = $rootDir . '/.env';
$bootstrapApp = $rootDir . '/bootstrap/app.php';

// ============================================================================
// STEP 1: Detect Current Environment
// ============================================================================
echo "<h2>📡 Bước 1: Phát hiện môi trường</h2>";

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'unknown';
$currentUrl = $protocol . '://' . $host;

echo "<div class='info'>";
echo "✅ Protocol: <code>$protocol</code><br>";
echo "✅ Host: <code>$host</code><br>";
echo "✅ Current URL: <code>$currentUrl</code><br>";
echo "</div>";

// Check if behind proxy
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'];
    echo "<div class='warning'>";
    echo "⚠️ Detected behind proxy. X-Forwarded-Proto: <code>$forwardedProto</code>";
    echo "</div>";
}

// ============================================================================
// STEP 2: Check .env file
// ============================================================================
echo "<h2>📄 Bước 2: Kiểm tra và sửa file .env</h2>";

if (!file_exists($envFile)) {
    echo "<div class='error'>❌ File .env không tồn tại tại: <code>$envFile</code></div>";
} else {
    echo "<div class='success'>✅ File .env tồn tại</div>";
    
    $envContent = file_get_contents($envFile);
    $updates = [];
    $needsUpdate = false;
    
    // Check APP_URL
    if (preg_match('/^APP_URL=(.*)$/m', $envContent, $matches)) {
        $currentAppUrl = trim($matches[1], ' "\'');
        echo "<div class='info'>Current APP_URL: <code>$currentAppUrl</code></div>";
        
        if ($currentAppUrl !== $currentUrl) {
            $envContent = preg_replace('/^APP_URL=.*$/m', "APP_URL=$currentUrl", $envContent);
            $updates[] = "APP_URL: $currentAppUrl → $currentUrl";
            $needsUpdate = true;
            echo "<div class='warning'>⚠️ APP_URL không khớp, sẽ được cập nhật</div>";
        } else {
            echo "<div class='success'>✅ APP_URL đã đúng</div>";
        }
    } else {
        $envContent .= "\nAPP_URL=$currentUrl\n";
        $updates[] = "APP_URL added: $currentUrl";
        $needsUpdate = true;
        echo "<div class='warning'>⚠️ APP_URL không tồn tại, sẽ được thêm</div>";
    }
    
    // Check SESSION_SECURE_COOKIE
    if (preg_match('/^SESSION_SECURE_COOKIE=(.*)$/m', $envContent, $matches)) {
        $currentSecure = trim($matches[1], ' "\'');
        echo "<div class='info'>Current SESSION_SECURE_COOKIE: <code>$currentSecure</code></div>";
        
        // If using HTTPS, should be null or true
        if ($protocol === 'https') {
            if ($currentSecure === 'false') {
                $envContent = preg_replace('/^SESSION_SECURE_COOKIE=.*$/m', "SESSION_SECURE_COOKIE=null", $envContent);
                $updates[] = "SESSION_SECURE_COOKIE: false → null (auto-detect)";
                $needsUpdate = true;
                echo "<div class='warning'>⚠️ SESSION_SECURE_COOKIE sẽ được đổi thành null (auto-detect)</div>";
            } else {
                echo "<div class='success'>✅ SESSION_SECURE_COOKIE đã đúng cho HTTPS</div>";
            }
        } else {
            // HTTP - should be false
            if ($currentSecure !== 'false' && $currentSecure !== '') {
                $envContent = preg_replace('/^SESSION_SECURE_COOKIE=.*$/m', "SESSION_SECURE_COOKIE=false", $envContent);
                $updates[] = "SESSION_SECURE_COOKIE: $currentSecure → false";
                $needsUpdate = true;
                echo "<div class='warning'>⚠️ SESSION_SECURE_COOKIE sẽ được đổi thành false</div>";
            } else {
                echo "<div class='success'>✅ SESSION_SECURE_COOKIE đã đúng cho HTTP</div>";
            }
        }
    } else {
        $secureValue = ($protocol === 'https') ? 'null' : 'false';
        $envContent .= "\nSESSION_SECURE_COOKIE=$secureValue\n";
        $updates[] = "SESSION_SECURE_COOKIE added: $secureValue";
        $needsUpdate = true;
        echo "<div class='warning'>⚠️ SESSION_SECURE_COOKIE không tồn tại, sẽ được thêm: <code>$secureValue</code></div>";
    }
    
    // Save .env if updates needed
    if ($needsUpdate) {
        // Backup .env first
        $backupFile = $envFile . '.backup.' . date('Y-m-d_H-i-s');
        copy($envFile, $backupFile);
        echo "<div class='info'>💾 Backup created: <code>" . basename($backupFile) . "</code></div>";
        
        if (file_put_contents($envFile, $envContent)) {
            echo "<div class='success'>✅ File .env đã được cập nhật thành công!</div>";
            foreach ($updates as $update) {
                echo "<div class='info'>  • $update</div>";
            }
        } else {
            echo "<div class='error'>❌ Không thể ghi file .env. Vui lòng kiểm tra quyền truy cập!</div>";
        }
    } else {
        echo "<div class='success'>✅ File .env đã đúng, không cần cập nhật</div>";
    }
}

// ============================================================================
// STEP 3: Check and Fix bootstrap/app.php (Temporarily disable middleware)
// ============================================================================
echo "<h2>🔐 Bước 3: Kiểm tra EnsureSecureSession Middleware</h2>";

if (file_exists($bootstrapApp)) {
    $bootstrapContent = file_get_contents($bootstrapApp);
    
    // Check if EnsureSecureSession is active
    if (preg_match('/\$middleware->append\([^)]*EnsureSecureSession[^)]*\);/', $bootstrapContent)) {
        echo "<div class='warning'>⚠️ EnsureSecureSession middleware đang được sử dụng</div>";
        echo "<div class='info'>Nếu vẫn còn lỗi, có thể tạm thời comment middleware này</div>";
        
        // Offer to comment it out
        if (isset($_GET['disable_middleware']) && $_GET['disable_middleware'] === 'yes') {
            $backupFile = $bootstrapApp . '.backup.' . date('Y-m-d_H-i-s');
            copy($bootstrapApp, $backupFile);
            
            $newContent = preg_replace(
                '/(\$middleware->append\([^)]*EnsureSecureSession[^)]*\);)/',
                '// TEMPORARILY DISABLED: $1',
                $bootstrapContent
            );
            
            if (file_put_contents($bootstrapApp, $newContent)) {
                echo "<div class='success'>✅ EnsureSecureSession middleware đã được tạm thời disable</div>";
                echo "<div class='warning'>⚠️ Nhớ enable lại sau khi fix xong!</div>";
            } else {
                echo "<div class='error'>❌ Không thể ghi file bootstrap/app.php</div>";
            }
        } else {
            echo "<div class='info'>";
            echo "💡 Nếu vẫn còn lỗi, click vào đây để tạm thời disable middleware: ";
            echo "<a href='?disable_middleware=yes' class='btn btn-danger'>Tạm thời Disable Middleware</a>";
            echo "</div>";
        }
    } else {
        echo "<div class='success'>✅ EnsureSecureSession middleware không được sử dụng hoặc đã bị disable</div>";
    }
} else {
    echo "<div class='error'>❌ File bootstrap/app.php không tồn tại</div>";
}

// ============================================================================
// STEP 4: Clear Laravel Cache (if possible)
// ============================================================================
echo "<h2>🧹 Bước 4: Clear Cache</h2>";

$cacheCommands = [
    'config:clear' => 'config',
    'cache:clear' => 'cache',
    'route:clear' => 'route',
    'view:clear' => 'view',
];

$cacheCleared = false;
foreach ($cacheCommands as $command => $type) {
    $cachePath = $rootDir . '/bootstrap/cache/' . $type . '.php';
    if (file_exists($cachePath)) {
        if (unlink($cachePath)) {
            echo "<div class='success'>✅ Cleared $type cache</div>";
            $cacheCleared = true;
        }
    }
}

if (!$cacheCleared) {
    echo "<div class='info'>ℹ️ Không tìm thấy cache files hoặc đã được clear</div>";
    echo "<div class='info'>💡 Nếu có SSH access, chạy: <code>php artisan config:clear && php artisan cache:clear</code></div>";
}

// ============================================================================
// STEP 5: Instructions
// ============================================================================
echo "<h2>📋 Bước 5: Hướng dẫn tiếp theo</h2>";

echo "<div class='info'>";
echo "<strong>1. Xóa cookies trình duyệt:</strong><br>";
echo "   - Mở Developer Tools (F12)<br>";
echo "   - Application → Cookies → Xóa tất cả cookies cho <code>ZoroRMS.click</code><br>";
echo "   - Hoặc dùng chế độ Incognito/Private<br><br>";

echo "<strong>2. Thử truy cập lại:</strong><br>";
echo "   - <a href='/' class='btn'>Trang chủ</a><br><br>";

echo "<strong>3. Nếu vẫn còn lỗi:</strong><br>";
echo "   - Kiểm tra file <code>public/.htaccess</code> có force HTTPS redirect không<br>";
echo "   - Kiểm tra cấu hình server (Apache/Nginx)<br>";
echo "   - Kiểm tra SSL certificate<br>";
echo "</div>";

echo "<hr>";
echo "<p><strong>⚠️ Lưu ý quan trọng:</strong></p>";
echo "<ul>";
echo "<li>Nếu đã disable middleware, nhớ enable lại sau khi fix xong</li>";
echo "<li>Backup files đã được tạo tự động</li>";
echo "<li>Nếu có SSH access, chạy <code>php artisan config:clear</code> để đảm bảo</li>";
echo "</ul>";

echo "</div></body></html>";

