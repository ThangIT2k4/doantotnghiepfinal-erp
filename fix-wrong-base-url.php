<?php
/**
 * ============================================================================
 * FIX WRONG BASE URL - Laravel on cPanel Subdirectory
 * ============================================================================
 * 
 * Lỗi: Khi click chức năng, URL thiếu base path
 * - Homepage: http://103.18.6.36/~lpi0g927o3nw/public/ ✅
 * - Click link: http://103.18.6.36/register ❌ (thiếu base path)
 * 
 * Nguyên nhân: APP_URL trong .env không đúng
 * 
 * Giải pháp: Sửa APP_URL trong .env
 * 
 * CÁCH DÙNG:
 * Upload file này lên root và truy cập qua browser:
 * http://103.18.6.36/~lpi0g927o3nw/fix-wrong-base-url.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Detect if running via web or CLI
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
    echo "<title>Fix Wrong Base URL - Laravel cPanel</title>";
    echo "<style>
        body{font-family:Arial,sans-serif;max-width:900px;margin:30px auto;padding:20px;background:#f5f5f5;}
        .container{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
        h1{color:#e3342f;border-bottom:3px solid #e3342f;padding-bottom:10px;}
        h2{color:#3490dc;margin-top:30px;}
        .success{background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:12px;border-radius:4px;margin:10px 0;}
        .error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:12px;border-radius:4px;margin:10px 0;}
        .warning{background:#fff3cd;border:1px solid #ffeeba;color:#856404;padding:12px;border-radius:4px;margin:10px 0;}
        .info{background:#d1ecf1;border:1px solid #bee5eb;color:#0c5460;padding:12px;border-radius:4px;margin:10px 0;}
        code{background:#f4f4f4;padding:2px 6px;border-radius:3px;font-family:'Courier New',monospace;}
        pre{background:#f4f4f4;padding:15px;border-radius:4px;overflow-x:auto;}
        .btn{display:inline-block;padding:10px 20px;background:#3490dc;color:white;text-decoration:none;border-radius:4px;margin:10px 5px 10px 0;}
    </style></head><body><div class='container'>";
    echo "<h1>🔧 Fix Wrong Base URL Issue</h1>";
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
        echo "$icon $message\n";
    } else {
        echo "<div class='$type'>$icon $message</div>";
    }
}

function printHeader($message) {
    global $isCLI;
    if ($isCLI) {
        echo "\n========================================\n";
        echo "$message\n";
        echo "========================================\n";
    } else {
        echo "<h2>$message</h2>";
    }
}

$rootDir = __DIR__;
$envFile = $rootDir . '/.env';
$envExampleFile = $rootDir . '/.env.example';

// ============================================================================
// STEP 1: Detect Current URL
// ============================================================================
printHeader("🔍 Bước 1: Phát hiện URL hiện tại");

if (!$isCLI) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove filename from path
    $basePath = rtrim($scriptPath, '/');
    
    // Construct correct APP_URL
    $correctAppUrl = $protocol . '://' . $host . $basePath . '/public';
    
    printMessage("Protocol: <code>$protocol</code>", 'info');
    printMessage("Host: <code>$host</code>", 'info');
    printMessage("Base Path: <code>$basePath</code>", 'info');
    printMessage("Correct APP_URL: <code>$correctAppUrl</code>", 'success');
} else {
    printMessage("Chạy qua CLI - Không thể tự động phát hiện URL", 'warning');
    printMessage("Vui lòng chạy qua browser để tự động detect URL", 'info');
    $correctAppUrl = 'http://103.18.6.36/~lpi0g927o3nw/public';
}

// ============================================================================
// STEP 2: Check .env file
// ============================================================================
printHeader("📄 Bước 2: Kiểm tra file .env");

if (!file_exists($envFile)) {
    printMessage("File .env không tồn tại!", 'error');
    
    if (file_exists($envExampleFile)) {
        printMessage("File .env.example được tìm thấy", 'info');
        printMessage("Đang copy .env.example → .env...", 'info');
        
        if (copy($envExampleFile, $envFile)) {
            printMessage("✅ Đã tạo file .env từ .env.example", 'success');
        } else {
            printMessage("❌ Không thể copy file .env.example", 'error');
            printMessage("Vui lòng tạo thủ công: copy .env.example .env", 'warning');
            
            if (!$isCLI) {
                echo "</div></body></html>";
            }
            exit(1);
        }
    } else {
        printMessage("❌ Cả .env và .env.example đều không tồn tại!", 'error');
        
        if (!$isCLI) {
            echo "</div></body></html>";
        }
        exit(1);
    }
}

printMessage("File .env tồn tại", 'success');

// ============================================================================
// STEP 3: Read and Update .env
// ============================================================================
printHeader("✏️ Bước 3: Cập nhật APP_URL trong .env");

$envContent = file_get_contents($envFile);
$originalContent = $envContent;

// Find current APP_URL
if (preg_match('/^APP_URL=(.*)$/m', $envContent, $matches)) {
    $currentAppUrl = trim($matches[1]);
    printMessage("APP_URL hiện tại: <code>$currentAppUrl</code>", 'info');
    
    if ($currentAppUrl === $correctAppUrl) {
        printMessage("✅ APP_URL đã đúng, không cần sửa!", 'success');
    } else {
        printMessage("⚠️ APP_URL sai! Đang sửa...", 'warning');
        
        // Replace APP_URL
        $envContent = preg_replace(
            '/^APP_URL=.*$/m',
            "APP_URL=$correctAppUrl",
            $envContent
        );
        
        if (file_put_contents($envFile, $envContent)) {
            printMessage("✅ Đã cập nhật APP_URL thành: <code>$correctAppUrl</code>", 'success');
        } else {
            printMessage("❌ Không thể ghi file .env", 'error');
            printMessage("Vui lòng sửa thủ công:", 'warning');
            
            if (!$isCLI) {
                echo "<pre>APP_URL=$correctAppUrl</pre>";
            }
        }
    }
} else {
    printMessage("⚠️ Không tìm thấy APP_URL trong .env", 'warning');
    printMessage("Đang thêm APP_URL...", 'info');
    
    // Append APP_URL
    $envContent .= "\nAPP_URL=$correctAppUrl\n";
    
    if (file_put_contents($envFile, $envContent)) {
        printMessage("✅ Đã thêm APP_URL vào .env", 'success');
    } else {
        printMessage("❌ Không thể ghi file .env", 'error');
    }
}

// ============================================================================
// STEP 4: Clear Config Cache
// ============================================================================
printHeader("🗑️ Bước 4: Xóa Config Cache");

$configCache = $rootDir . '/bootstrap/cache/config.php';

if (file_exists($configCache)) {
    if (@unlink($configCache)) {
        printMessage("✅ Đã xóa config cache", 'success');
    } else {
        printMessage("⚠️ Không thể xóa config cache", 'warning');
        printMessage("Vui lòng xóa thủ công: bootstrap/cache/config.php", 'info');
    }
} else {
    printMessage("ℹ️ Config cache không tồn tại (OK)", 'info');
}

// ============================================================================
// STEP 5: Verify Routes
// ============================================================================
printHeader("🔗 Bước 5: Kiểm tra Routes");

$routesFile = $rootDir . '/routes/web.php';

if (file_exists($routesFile)) {
    printMessage("✅ File routes/web.php tồn tại", 'success');
    
    $routesContent = file_get_contents($routesFile);
    
    // Check for basic routes
    $routes = [
        'home' => "Route::get('/', ",
        'login' => "Route::get('/login', ",
        'register' => "Route::get('/register', ",
    ];
    
    foreach ($routes as $name => $pattern) {
        if (strpos($routesContent, $pattern) !== false) {
            printMessage("✅ Route '$name' tồn tại", 'success');
        } else {
            printMessage("⚠️ Route '$name' không tìm thấy", 'warning');
        }
    }
} else {
    printMessage("❌ File routes/web.php không tồn tại", 'error');
}

// ============================================================================
// STEP 6: Test URLs
// ============================================================================
printHeader("🧪 Bước 6: Test URLs");

if (!$isCLI) {
    $testUrls = [
        'Home' => $correctAppUrl . '/',
        'Login' => $correctAppUrl . '/login',
        'Register' => $correctAppUrl . '/register',
    ];
    
    echo "<div class='info'>";
    echo "<strong>Test các URLs sau:</strong><br><br>";
    foreach ($testUrls as $name => $url) {
        echo "✅ <strong>$name:</strong> <a href='$url' target='_blank' style='color:#3490dc;'>$url</a><br>";
    }
    echo "</div>";
}

// ============================================================================
// SUMMARY & NEXT STEPS
// ============================================================================
printHeader("📊 Tổng kết");

printMessage("✅ Fix hoàn tất! APP_URL đã được cập nhật.", 'success');

if (!$isCLI) {
    echo "<div class='info'>";
    echo "<strong>⚠️ Lưu ý quan trọng:</strong><br><br>";
    echo "1. APP_URL đã được cập nhật trong .env<br>";
    echo "2. Config cache đã được xóa<br>";
    echo "3. Hãy test lại website<br>";
    echo "4. Nếu vẫn lỗi, thử xóa cache browser (Ctrl+Shift+Delete)<br>";
    echo "</div>";
    
    echo "<h2>🎯 Bước tiếp theo</h2>";
    echo "<div style='margin-top:20px;'>";
    echo "<a href='$correctAppUrl/' class='btn'>🏠 Test Homepage</a>";
    echo "<a href='$correctAppUrl/login' class='btn'>🔐 Test Login</a>";
    echo "<a href='$correctAppUrl/register' class='btn'>📝 Test Register</a>";
    echo "</div>";
    
    echo "<h2>🔧 Manual Fix (Nếu cần)</h2>";
    echo "<div class='info'>";
    echo "<strong>Nếu script không hoạt động, sửa thủ công:</strong><br><br>";
    echo "1. Mở file <code>.env</code><br>";
    echo "2. Tìm dòng <code>APP_URL=...</code><br>";
    echo "3. Sửa thành: <code>APP_URL=$correctAppUrl</code><br>";
    echo "4. Save file<br>";
    echo "5. Xóa file <code>bootstrap/cache/config.php</code><br>";
    echo "6. Refresh website<br>";
    echo "</div>";
    
    echo "<div style='text-align:center;margin-top:30px;color:#666;font-size:0.9em;'>";
    echo "Laravel cPanel Fix Script v1.0 | " . date('Y-m-d H:i:s');
    echo "</div>";
    
    echo "</div></body></html>";
}

?>

