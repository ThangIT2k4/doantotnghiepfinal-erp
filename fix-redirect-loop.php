<?php
/**
 * ============================================================================
 * FIX REDIRECT LOOP (ERR_TOO_MANY_REDIRECTS)
 * ============================================================================
 * 
 * Script này sẽ kiểm tra và sửa các vấn đề gây ra redirect loop:
 * 1. Kiểm tra APP_URL trong .env
 * 2. Kiểm tra SESSION_SECURE_COOKIE configuration
 * 3. Kiểm tra .htaccess có force HTTPS redirect không
 * 4. Tạm thời disable EnsureSecureSession middleware nếu cần
 * 
 * CÁCH DÙNG:
 * 1. Upload file này lên thư mục public/
 * 2. Truy cập: https://ZoroRMS.click/fix-redirect-loop.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
    echo "<title>Fix Redirect Loop</title>";
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
    </style></head><body><div class='container'>";
}

function printMessage($message, $type = 'info') {
    global $isCLI;
    $icons = [
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    $icon = $icons[$type] ?? 'ℹ️';
    
    if ($isCLI) {
        echo "[$type] $icon $message\n";
    } else {
        echo "<div class='$type'>$icon $message</div>";
    }
}

function printHeader($message) {
    global $isCLI;
    if ($isCLI) {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "$message\n";
        echo str_repeat("=", 50) . "\n";
    } else {
        echo "<h2>$message</h2>";
    }
}

$rootDir = dirname(__DIR__);
$envFile = $rootDir . '/.env';
$publicHtaccess = $rootDir . '/public/.htaccess';
$rootHtaccess = $rootDir . '/.htaccess';
$bootstrapApp = $rootDir . '/bootstrap/app.php';

printHeader("🔧 Fix Redirect Loop - ERR_TOO_MANY_REDIRECTS");

// ============================================================================
// STEP 1: Detect Current URL and Protocol
// ============================================================================
printHeader("📡 Bước 1: Phát hiện URL và Protocol hiện tại");

if (!$isCLI) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $currentUrl = $protocol . '://' . $host;
    
    printMessage("Protocol: <code>$protocol</code>", 'info');
    printMessage("Host: <code>$host</code>", 'info');
    printMessage("Current URL: <code>$currentUrl</code>", 'info');
    
    // Check if behind proxy
    $isBehindProxy = !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) || 
                     !empty($_SERVER['HTTP_X_FORWARDED_FOR']);
    if ($isBehindProxy) {
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'unknown';
        printMessage("Detected behind proxy. X-Forwarded-Proto: <code>$forwardedProto</code>", 'warning');
    }
} else {
    printMessage("Chạy qua CLI - Không thể tự động phát hiện URL", 'warning');
    $currentUrl = 'https://ZoroRMS.click';
}

// ============================================================================
// STEP 2: Check and Fix .env file
// ============================================================================
printHeader("📄 Bước 2: Kiểm tra và sửa file .env");

if (!file_exists($envFile)) {
    printMessage("File .env không tồn tại!", 'error');
    exit;
}

$envContent = file_get_contents($envFile);
$updates = [];
$needsUpdate = false;

// Check APP_URL
if (preg_match('/^APP_URL=(.*)$/m', $envContent, $matches)) {
    $currentAppUrl = trim($matches[1]);
    printMessage("Current APP_URL: <code>$currentAppUrl</code>", 'info');
    
    // If APP_URL doesn't match current URL, update it
    if ($currentAppUrl !== $currentUrl && !$isCLI) {
        $envContent = preg_replace('/^APP_URL=.*$/m', "APP_URL=$currentUrl", $envContent);
        $updates[] = "APP_URL updated to: $currentUrl";
        $needsUpdate = true;
        printMessage("APP_URL sẽ được cập nhật thành: <code>$currentUrl</code>", 'warning');
    }
} else {
    // APP_URL not found, add it
    $envContent .= "\nAPP_URL=$currentUrl\n";
    $updates[] = "APP_URL added: $currentUrl";
    $needsUpdate = true;
    printMessage("APP_URL không tồn tại, sẽ được thêm: <code>$currentUrl</code>", 'warning');
}

// Check SESSION_SECURE_COOKIE
if (preg_match('/^SESSION_SECURE_COOKIE=(.*)$/m', $envContent, $matches)) {
    $currentSecureCookie = trim($matches[1]);
    printMessage("Current SESSION_SECURE_COOKIE: <code>$currentSecureCookie</code>", 'info');
    
    // If using HTTPS, should be true or null (auto-detect)
    if (!$isCLI && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        if ($currentSecureCookie === 'false') {
            // Change to null for auto-detect
            $envContent = preg_replace('/^SESSION_SECURE_COOKIE=.*$/m', "SESSION_SECURE_COOKIE=null", $envContent);
            $updates[] = "SESSION_SECURE_COOKIE changed to null (auto-detect)";
            $needsUpdate = true;
            printMessage("SESSION_SECURE_COOKIE sẽ được đổi thành null (auto-detect)", 'warning');
        }
    } else {
        // If using HTTP, should be false
        if ($currentSecureCookie !== 'false' && $currentSecureCookie !== '') {
            $envContent = preg_replace('/^SESSION_SECURE_COOKIE=.*$/m', "SESSION_SECURE_COOKIE=false", $envContent);
            $updates[] = "SESSION_SECURE_COOKIE changed to false";
            $needsUpdate = true;
            printMessage("SESSION_SECURE_COOKIE sẽ được đổi thành false", 'warning');
        }
    }
} else {
    // SESSION_SECURE_COOKIE not found
    $secureValue = (!$isCLI && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'null' : 'false';
    $envContent .= "\nSESSION_SECURE_COOKIE=$secureValue\n";
    $updates[] = "SESSION_SECURE_COOKIE added: $secureValue";
    $needsUpdate = true;
    printMessage("SESSION_SECURE_COOKIE không tồn tại, sẽ được thêm: <code>$secureValue</code>", 'warning');
}

// Save .env if updates needed
if ($needsUpdate) {
    if (file_put_contents($envFile, $envContent)) {
        printMessage("✅ File .env đã được cập nhật thành công!", 'success');
        foreach ($updates as $update) {
            printMessage("  - $update", 'info');
        }
    } else {
        printMessage("❌ Không thể ghi file .env. Vui lòng kiểm tra quyền truy cập!", 'error');
    }
} else {
    printMessage("✅ File .env đã đúng, không cần cập nhật", 'success');
}

// ============================================================================
// STEP 3: Check .htaccess files for HTTPS redirect
// ============================================================================
printHeader("📋 Bước 3: Kiểm tra file .htaccess");

// Check public/.htaccess
if (file_exists($publicHtaccess)) {
    $htaccessContent = file_get_contents($publicHtaccess);
    
    // Check for HTTPS force redirect
    if (preg_match('/RewriteCond.*HTTPS.*on/i', $htaccessContent) || 
        preg_match('/RewriteRule.*https/i', $htaccessContent)) {
        printMessage("⚠️ Phát hiện HTTPS force redirect trong public/.htaccess", 'warning');
        printMessage("Nếu bạn đang dùng HTTP hoặc có vấn đề với proxy, điều này có thể gây redirect loop", 'warning');
    } else {
        printMessage("✅ public/.htaccess không có HTTPS force redirect", 'success');
    }
} else {
    printMessage("⚠️ File public/.htaccess không tồn tại", 'warning');
}

// Check root .htaccess
if (file_exists($rootHtaccess)) {
    $rootHtaccessContent = file_get_contents($rootHtaccess);
    printMessage("✅ File root .htaccess tồn tại", 'info');
    
    // Check if it redirects to public
    if (strpos($rootHtaccessContent, 'public') !== false) {
        printMessage("✅ Root .htaccess redirects to public/", 'success');
    }
} else {
    printMessage("⚠️ File root .htaccess không tồn tại", 'warning');
}

// ============================================================================
// STEP 4: Check EnsureSecureSession middleware
// ============================================================================
printHeader("🔐 Bước 4: Kiểm tra EnsureSecureSession Middleware");

if (file_exists($bootstrapApp)) {
    $bootstrapContent = file_get_contents($bootstrapApp);
    
    if (strpos($bootstrapContent, 'EnsureSecureSession') !== false) {
        printMessage("⚠️ EnsureSecureSession middleware đang được sử dụng", 'warning');
        printMessage("Middleware này có thể gây redirect loop nếu HTTPS detection không đúng", 'warning');
        printMessage("Nếu vẫn còn lỗi, có thể tạm thời comment middleware này trong bootstrap/app.php", 'info');
    } else {
        printMessage("✅ EnsureSecureSession middleware không được sử dụng", 'success');
    }
} else {
    printMessage("⚠️ File bootstrap/app.php không tồn tại", 'warning');
}

// ============================================================================
// STEP 5: Recommendations
// ============================================================================
printHeader("💡 Bước 5: Khuyến nghị");

printMessage("1. Xóa cookies và cache trình duyệt (Ctrl+Shift+Delete)", 'info');
printMessage("2. Kiểm tra lại APP_URL trong .env phải đúng với domain của bạn", 'info');
printMessage("3. Nếu dùng HTTPS, đảm bảo SSL certificate hợp lệ", 'info');
printMessage("4. Nếu đứng sau proxy/load balancer, cấu hình TrustProxies middleware", 'info');
printMessage("5. Clear cache Laravel: <code>php artisan config:clear</code> và <code>php artisan cache:clear</code>", 'info');

if (!$isCLI) {
    echo "<hr>";
    echo "<h2>🔧 Thực hiện thêm</h2>";
    echo "<p>Nếu vẫn còn lỗi, thử các bước sau:</p>";
    echo "<ol>";
    echo "<li>Clear Laravel cache bằng cách chạy: <code>php artisan config:clear && php artisan cache:clear</code></li>";
    echo "<li>Kiểm tra file <code>bootstrap/app.php</code> và tạm thời comment dòng có <code>EnsureSecureSession</code></li>";
    echo "<li>Kiểm tra xem có middleware nào khác force HTTPS redirect không</li>";
    echo "<li>Kiểm tra cấu hình proxy/load balancer nếu có</li>";
    echo "</ol>";
    
    echo "<p><a href='/' class='btn'>Thử truy cập trang chủ</a></p>";
    echo "</div></body></html>";
} else {
    echo "\n";
    printMessage("Hoàn thành! Vui lòng kiểm tra lại website.", 'success');
}

