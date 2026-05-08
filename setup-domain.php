<?php
/**
 * ============================================================================
 * SETUP DOMAIN/SUBDOMAIN - Laravel trên cPanel
 * ============================================================================
 * 
 * Script tự động cấu hình domain/subdomain cho Laravel
 * - Tự động phát hiện domain hiện tại
 * - Cập nhật APP_URL trong .env
 * - Xóa config cache
 * - Kiểm tra Document Root
 * 
 * CÁCH DÙNG:
 * 1. Cấu hình domain/subdomain trong cPanel trước
 * 2. Set Document Root trỏ đến thư mục public/
 * 3. Upload file này lên root
 * 4. Truy cập: http://yourdomain.com/setup-domain.php
 * 5. Script sẽ tự động fix APP_URL
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
    echo "<title>Setup Domain/Subdomain - Laravel</title>";
    echo "<style>
        body{font-family:Arial,sans-serif;max-width:1000px;margin:30px auto;padding:20px;background:#f5f5f5;}
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
        .btn:hover{background:#2779bd;}
        .btn-success{background:#28a745;}
        .btn-success:hover{background:#218838;}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:15px;margin-top:15px;}
        .card{background:#f8f9fa;padding:15px;border-radius:8px;border-left:4px solid #3490dc;}
    </style></head><body><div class='container'>";
    echo "<h1>🌐 Setup Domain/Subdomain cho Laravel</h1>";
}

function printMessage($message, $type = 'info') {
    global $isCLI;
    $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️', 'info' => 'ℹ️'];
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
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "$message\n";
        echo str_repeat("=", 60) . "\n";
    } else {
        echo "<h2>$message</h2>";
    }
}

$rootDir = __DIR__;
$envFile = $rootDir . '/.env';

// ============================================================================
// STEP 1: Detect Current Domain
// ============================================================================
printHeader("🔍 Bước 1: Phát hiện Domain hiện tại");

if (!$isCLI) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    
    // Remove script name from URI
    $basePath = str_replace(basename($scriptName), '', $requestUri);
    $basePath = rtrim($basePath, '/');
    
    // Construct APP_URL
    // If accessing via domain directly (not subdirectory), basePath should be empty
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $scriptPath = dirname($scriptName);
    
    // Check if we're in subdirectory or root
    if (strpos($scriptPath, '/public') !== false || strpos($documentRoot, '/public') !== false) {
        // We're in subdirectory mode
        $correctAppUrl = $protocol . '://' . $host . $basePath;
    } else {
        // We're in root domain mode
        $correctAppUrl = $protocol . '://' . $host;
    }
    
    printMessage("Protocol: <code>$protocol</code>", 'info');
    printMessage("Host (Domain): <code>$host</code>", 'info');
    printMessage("Document Root: <code>$documentRoot</code>", 'info');
    printMessage("Script Path: <code>$scriptPath</code>", 'info');
    printMessage("Detected APP_URL: <code>$correctAppUrl</code>", 'success');
    
    // Check if domain is subdirectory or main domain
    if (strpos($host, '~') !== false || strpos($host, '103.18.6.36') !== false) {
        printMessage("⚠️ Bạn đang dùng IP hoặc subdirectory. Hãy cấu hình domain/subdomain trong cPanel trước!", 'warning');
    } else {
        printMessage("✅ Domain được phát hiện: <code>$host</code>", 'success');
    }
} else {
    printMessage("Chạy qua CLI - Không thể tự động phát hiện domain", 'warning');
    $correctAppUrl = 'http://yourdomain.com';
}

// ============================================================================
// STEP 2: Check Document Root
// ============================================================================
printHeader("📂 Bước 2: Kiểm tra Document Root");

if (!$isCLI) {
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $publicIndex = $documentRoot . '/index.php';
    $publicHtaccess = $documentRoot . '/.htaccess';
    
    printMessage("Document Root: <code>$documentRoot</code>", 'info');
    
    if (file_exists($publicIndex)) {
        printMessage("✅ File index.php tồn tại trong Document Root", 'success');
        
        // Check if it's Laravel's public/index.php
        $indexContent = file_get_contents($publicIndex);
        if (strpos($indexContent, 'bootstrap/app.php') !== false) {
            printMessage("✅ Document Root đã trỏ đúng đến thư mục public/", 'success');
        } else {
            printMessage("⚠️ File index.php không phải của Laravel public/", 'warning');
            printMessage("Đảm bảo Document Root trỏ đến: <code>/home/username/lpi0g927o3nw/public</code>", 'warning');
        }
    } else {
        printMessage("❌ File index.php không tồn tại trong Document Root", 'error');
        printMessage("Document Root phải trỏ đến thư mục <code>public/</code> của Laravel", 'error');
    }
    
    if (file_exists($publicHtaccess)) {
        printMessage("✅ File .htaccess tồn tại trong Document Root", 'success');
    } else {
        printMessage("⚠️ File .htaccess không tồn tại", 'warning');
    }
}

// ============================================================================
// STEP 3: Check .env file
// ============================================================================
printHeader("📄 Bước 3: Kiểm tra file .env");

if (!file_exists($envFile)) {
    printMessage("File .env không tồn tại!", 'error');
    
    $envExampleFile = $rootDir . '/.env.example';
    if (file_exists($envExampleFile)) {
        printMessage("Đang copy .env.example → .env...", 'info');
        if (copy($envExampleFile, $envFile)) {
            printMessage("✅ Đã tạo file .env", 'success');
        } else {
            printMessage("❌ Không thể tạo file .env", 'error');
            if (!$isCLI) {
                echo "</div></body></html>";
            }
            exit(1);
        }
    } else {
        printMessage("❌ File .env.example không tồn tại", 'error');
        if (!$isCLI) {
            echo "</div></body></html>";
        }
        exit(1);
    }
}

printMessage("✅ File .env tồn tại", 'success');

// ============================================================================
// STEP 4: Update APP_URL
// ============================================================================
printHeader("✏️ Bước 4: Cập nhật APP_URL trong .env");

$envContent = file_get_contents($envFile);
$originalContent = $envContent;

// Find current APP_URL
if (preg_match('/^APP_URL=(.*)$/m', $envContent, $matches)) {
    $currentAppUrl = trim($matches[1]);
    printMessage("APP_URL hiện tại: <code>$currentAppUrl</code>", 'info');
    
    if ($currentAppUrl === $correctAppUrl) {
        printMessage("✅ APP_URL đã đúng, không cần sửa!", 'success');
    } else {
        printMessage("⚠️ APP_URL cần được cập nhật", 'warning');
        printMessage("Từ: <code>$currentAppUrl</code>", 'info');
        printMessage("Thành: <code>$correctAppUrl</code>", 'success');
        
        // Replace APP_URL
        $envContent = preg_replace(
            '/^APP_URL=.*$/m',
            "APP_URL=$correctAppUrl",
            $envContent
        );
        
        if (file_put_contents($envFile, $envContent)) {
            printMessage("✅ Đã cập nhật APP_URL thành công!", 'success');
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
    
    $envContent .= "\nAPP_URL=$correctAppUrl\n";
    
    if (file_put_contents($envFile, $envContent)) {
        printMessage("✅ Đã thêm APP_URL vào .env", 'success');
    } else {
        printMessage("❌ Không thể ghi file .env", 'error');
    }
}

// ============================================================================
// STEP 5: Clear Cache
// ============================================================================
printHeader("🗑️ Bước 5: Xóa Cache");

$cacheFiles = [
    'bootstrap/cache/config.php',
    'bootstrap/cache/routes-v7.php',
    'bootstrap/cache/packages.php',
    'bootstrap/cache/services.php',
];

$deletedCount = 0;
foreach ($cacheFiles as $cacheFile) {
    $fullPath = $rootDir . '/' . $cacheFile;
    if (file_exists($fullPath)) {
        if (@unlink($fullPath)) {
            printMessage("✅ Đã xóa: $cacheFile", 'success');
            $deletedCount++;
        } else {
            printMessage("⚠️ Không thể xóa: $cacheFile", 'warning');
        }
    }
}

if ($deletedCount > 0) {
    printMessage("✅ Đã xóa $deletedCount file(s) cache", 'success');
} else {
    printMessage("ℹ️ Không có cache nào cần xóa", 'info');
}

// ============================================================================
// STEP 6: Test URLs
// ============================================================================
printHeader("🧪 Bước 6: Test URLs");

if (!$isCLI) {
    $testUrls = [
        'Homepage' => $correctAppUrl . '/',
        'Login' => $correctAppUrl . '/login',
        'Register' => $correctAppUrl . '/register',
    ];
    
    echo "<div class='info'>";
    echo "<strong>Test các URLs sau:</strong><br><br>";
    foreach ($testUrls as $name => $url) {
        echo "✅ <strong>$name:</strong> <a href='$url' target='_blank' class='btn'>$url</a><br><br>";
    }
    echo "</div>";
}

// ============================================================================
// SUMMARY
// ============================================================================
printHeader("📊 Tổng kết");

printMessage("✅ Setup domain hoàn tất!", 'success');

if (!$isCLI) {
    echo "<div class='info'>";
    echo "<strong>✅ Đã hoàn thành:</strong><br>";
    echo "1. Phát hiện domain: <code>$host</code><br>";
    echo "2. Cập nhật APP_URL: <code>$correctAppUrl</code><br>";
    echo "3. Xóa config cache<br>";
    echo "4. Sẵn sàng test!<br>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<strong>⚠️ Lưu ý quan trọng:</strong><br><br>";
    echo "1. <strong>Document Root phải trỏ đến thư mục public/</strong><br>";
    echo "   → Vào cPanel → Domains/Subdomains → Settings<br>";
    echo "   → Set Document Root: <code>/home/username/lpi0g927o3nw/public</code><br><br>";
    echo "2. <strong>Nếu vẫn lỗi, clear browser cache</strong> (Ctrl+Shift+Delete)<br><br>";
    echo "3. <strong>Xóa file này sau khi dùng xong</strong> để bảo mật<br>";
    echo "</div>";
    
    echo "<h2>📋 Checklist</h2>";
    echo "<div class='card'>";
    echo "<input type='checkbox'> Domain/Subdomain đã được thêm vào cPanel<br>";
    echo "<input type='checkbox'> Document Root đã trỏ đến public/<br>";
    echo "<input type='checkbox'> APP_URL đã được cập nhật<br>";
    echo "<input type='checkbox'> Cache đã được xóa<br>";
    echo "<input type='checkbox'> Đã test homepage<br>";
    echo "<input type='checkbox'> Đã test login/register<br>";
    echo "</div>";
    
    echo "<div style='text-align:center;margin-top:30px;color:#666;font-size:0.9em;'>";
    echo "Laravel Domain Setup Script v1.0 | " . date('Y-m-d H:i:s');
    echo "</div>";
    
    echo "</div></body></html>";
}

?>

