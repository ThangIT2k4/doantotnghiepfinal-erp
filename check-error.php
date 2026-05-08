<?php
/**
 * ============================================================================
 * CHECK ERROR - Xem lỗi chi tiết khi deploy
 * ============================================================================
 * 
 * Script này giúp kiểm tra và hiển thị lỗi chi tiết khi gặp lỗi 500
 * 
 * CÁCH DÙNG:
 * 1. Upload file này lên root của project trên server
 * 2. Truy cập: https://your-domain.com/check-error.php
 * 3. Xem các lỗi và thông tin chi tiết
 * 4. ⚠️ XÓA FILE NÀY SAU KHI DÙNG XONG để bảo mật!
 */

// Bật error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Error Checker</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 30px;
            font-size: 2em;
        }
        .section {
            margin: 25px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .check-item {
            padding: 10px;
            margin: 8px 0;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        .check-item.success {
            background: #d4edda;
            color: #155724;
        }
        .check-item.error {
            background: #f8d7da;
            color: #721c24;
        }
        .check-item.warning {
            background: #fff3cd;
            color: #856404;
        }
        .check-item.info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .icon {
            margin-right: 10px;
            font-size: 1.2em;
        }
        pre {
            background: #1e1e1e;
            color: #4af;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin: 10px 0;
        }
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Laravel Error Checker</h1>

        <?php
        $errors = [];
        $warnings = [];
        $success = [];

        // 1. Kiểm tra .env
        echo '<div class="section">';
        echo '<h2>1. Kiểm tra file .env</h2>';
        
        if (file_exists('.env')) {
            $success[] = 'File .env tồn tại';
            echo '<div class="check-item success"><span class="icon">✅</span> File .env tồn tại</div>';
            
            $env = file_get_contents('.env');
            
            // Kiểm tra APP_KEY
            if (preg_match('/APP_KEY=base64:[A-Za-z0-9+\/=]+/', $env)) {
                $success[] = 'APP_KEY đã được set';
                echo '<div class="check-item success"><span class="icon">✅</span> APP_KEY đã được set</div>';
            } else {
                $errors[] = 'APP_KEY chưa được set';
                echo '<div class="check-item error"><span class="icon">❌</span> APP_KEY chưa được set - Cần chạy generate-key.php</div>';
            }
            
            // Kiểm tra database config
            if (strpos($env, 'DB_DATABASE=') !== false) {
                preg_match('/DB_DATABASE=(.+)/', $env, $matches);
                $dbName = trim($matches[1] ?? '');
                if (!empty($dbName) && $dbName !== 'your_database_name') {
                    $success[] = 'DB_DATABASE đã được cấu hình';
                    echo '<div class="check-item success"><span class="icon">✅</span> DB_DATABASE đã được cấu hình</div>';
                } else {
                    $warnings[] = 'DB_DATABASE chưa được cấu hình đúng';
                    echo '<div class="check-item warning"><span class="icon">⚠️</span> DB_DATABASE chưa được cấu hình đúng</div>';
                }
            } else {
                $errors[] = 'DB_DATABASE chưa được set';
                echo '<div class="check-item error"><span class="icon">❌</span> DB_DATABASE chưa được set</div>';
            }
            
            if (strpos($env, 'DB_USERNAME=') !== false) {
                $success[] = 'DB_USERNAME đã được set';
                echo '<div class="check-item success"><span class="icon">✅</span> DB_USERNAME đã được set</div>';
            } else {
                $errors[] = 'DB_USERNAME chưa được set';
                echo '<div class="check-item error"><span class="icon">❌</span> DB_USERNAME chưa được set</div>';
            }
            
        } else {
            $errors[] = 'File .env không tồn tại';
            echo '<div class="check-item error"><span class="icon">❌</span> File .env KHÔNG tồn tại - Cần tạo file .env</div>';
        }
        echo '</div>';

        // 2. Kiểm tra vendor
        echo '<div class="section">';
        echo '<h2>2. Kiểm tra vendor folder</h2>';
        
        if (is_dir('vendor')) {
            $success[] = 'Vendor folder tồn tại';
            echo '<div class="check-item success"><span class="icon">✅</span> Vendor folder tồn tại</div>';
            
            if (is_dir('vendor/laravel')) {
                $success[] = 'Laravel packages đã được cài đặt';
                echo '<div class="check-item success"><span class="icon">✅</span> Laravel packages đã được cài đặt</div>';
            } else {
                $errors[] = 'Laravel packages chưa được cài đặt';
                echo '<div class="check-item error"><span class="icon">❌</span> Laravel packages chưa được cài đặt - Cần chạy composer install</div>';
            }
            
            if (file_exists('vendor/autoload.php')) {
                $success[] = 'Autoload file tồn tại';
                echo '<div class="check-item success"><span class="icon">✅</span> Autoload file tồn tại</div>';
            } else {
                $errors[] = 'Autoload file không tồn tại';
                echo '<div class="check-item error"><span class="icon">❌</span> Autoload file không tồn tại</div>';
            }
        } else {
            $errors[] = 'Vendor folder không tồn tại';
            echo '<div class="check-item error"><span class="icon">❌</span> Vendor folder KHÔNG tồn tại - Cần upload vendor hoặc chạy composer install</div>';
        }
        echo '</div>';

        // 3. Kiểm tra storage permissions
        echo '<div class="section">';
        echo '<h2>3. Kiểm tra Storage Permissions</h2>';
        
        $storagePath = 'storage';
        if (is_dir($storagePath)) {
            $success[] = 'Storage folder tồn tại';
            echo '<div class="check-item success"><span class="icon">✅</span> Storage folder tồn tại</div>';
            
            if (is_writable($storagePath)) {
                $success[] = 'Storage folder có quyền ghi';
                echo '<div class="check-item success"><span class="icon">✅</span> Storage folder có quyền ghi</div>';
            } else {
                $errors[] = 'Storage folder không có quyền ghi';
                echo '<div class="check-item error"><span class="icon">❌</span> Storage folder KHÔNG có quyền ghi - Cần set permission 755 hoặc 775</div>';
            }
            
            // Kiểm tra các thư mục con
            $requiredDirs = ['storage/logs', 'storage/framework', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views'];
            foreach ($requiredDirs as $dir) {
                if (!is_dir($dir)) {
                    $warnings[] = "Thư mục $dir không tồn tại";
                    echo '<div class="check-item warning"><span class="icon">⚠️</span> Thư mục ' . $dir . ' không tồn tại - Sẽ tự tạo khi cần</div>';
                }
            }
        } else {
            $errors[] = 'Storage folder không tồn tại';
            echo '<div class="check-item error"><span class="icon">❌</span> Storage folder KHÔNG tồn tại</div>';
        }
        echo '</div>';

        // 4. Kiểm tra bootstrap/cache
        echo '<div class="section">';
        echo '<h2>4. Kiểm tra Bootstrap/Cache</h2>';
        
        $cachePath = 'bootstrap/cache';
        if (is_dir($cachePath)) {
            $success[] = 'Bootstrap/cache folder tồn tại';
            echo '<div class="check-item success"><span class="icon">✅</span> Bootstrap/cache folder tồn tại</div>';
            
            if (is_writable($cachePath)) {
                $success[] = 'Bootstrap/cache có quyền ghi';
                echo '<div class="check-item success"><span class="icon">✅</span> Bootstrap/cache có quyền ghi</div>';
            } else {
                $errors[] = 'Bootstrap/cache không có quyền ghi';
                echo '<div class="check-item error"><span class="icon">❌</span> Bootstrap/cache KHÔNG có quyền ghi - Cần set permission 755 hoặc 775</div>';
            }
            
            // Kiểm tra cache files
            $cacheFiles = [
                'bootstrap/cache/routes-v7.php',
                'bootstrap/cache/config.php',
                'bootstrap/cache/packages.php',
                'bootstrap/cache/services.php'
            ];
            
            $hasCacheFiles = false;
            foreach ($cacheFiles as $file) {
                if (file_exists($file)) {
                    $hasCacheFiles = true;
                    echo '<div class="check-item warning"><span class="icon">⚠️</span> File cache tồn tại: ' . $file . ' - Có thể gây lỗi nếu từ môi trường khác</div>';
                }
            }
            
            if (!$hasCacheFiles) {
                echo '<div class="check-item info"><span class="icon">ℹ️</span> Không có cache files - OK (sẽ tự tạo khi cần)</div>';
            }
        } else {
            $errors[] = 'Bootstrap/cache folder không tồn tại';
            echo '<div class="check-item error"><span class="icon">❌</span> Bootstrap/cache folder KHÔNG tồn tại</div>';
        }
        echo '</div>';

        // 5. Thử load Laravel
        echo '<div class="section">';
        echo '<h2>5. Thử Load Laravel</h2>';
        
        try {
            if (file_exists('vendor/autoload.php')) {
                require __DIR__.'/vendor/autoload.php';
                echo '<div class="check-item success"><span class="icon">✅</span> Autoload thành công</div>';
                
                if (file_exists('bootstrap/app.php')) {
                    $app = require_once __DIR__.'/bootstrap/app.php';
                    echo '<div class="check-item success"><span class="icon">✅</span> Bootstrap app thành công</div>';
                    
                    try {
                        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
                        $kernel->bootstrap();
                        echo '<div class="check-item success"><span class="icon">✅</span> Kernel bootstrap thành công</div>';
                        
                        // Thử kết nối database
                        try {
                            $pdo = DB::connection()->getPdo();
                            echo '<div class="check-item success"><span class="icon">✅</span> Database connection thành công</div>';
                            echo '<div class="check-item info"><span class="icon">ℹ️</span> Connected to: ' . DB::connection()->getDatabaseName() . '</div>';
                        } catch (Exception $e) {
                            $errors[] = 'Database connection failed: ' . $e->getMessage();
                            echo '<div class="check-item error"><span class="icon">❌</span> Database connection thất bại</div>';
                            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                        }
                        
                    } catch (Exception $e) {
                        $errors[] = 'Kernel bootstrap failed: ' . $e->getMessage();
                        echo '<div class="check-item error"><span class="icon">❌</span> Kernel bootstrap thất bại</div>';
                        echo '<pre>' . htmlspecialchars($e->getMessage()) . "\nFile: " . $e->getFile() . "\nLine: " . $e->getLine() . '</pre>';
                    }
                } else {
                    $errors[] = 'Bootstrap app.php không tồn tại';
                    echo '<div class="check-item error"><span class="icon">❌</span> Bootstrap app.php không tồn tại</div>';
                }
            } else {
                $errors[] = 'Vendor autoload không tồn tại';
                echo '<div class="check-item error"><span class="icon">❌</span> Vendor autoload không tồn tại</div>';
            }
        } catch (Exception $e) {
            $errors[] = 'Error loading Laravel: ' . $e->getMessage();
            echo '<div class="check-item error"><span class="icon">❌</span> Lỗi khi load Laravel</div>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . "\n\nFile: " . $e->getFile() . "\nLine: " . $e->getLine() . "\n\nStack trace:\n" . $e->getTraceAsString() . '</pre>';
        }
        echo '</div>';

        // 6. Tóm tắt
        echo '<div class="section">';
        echo '<h2>📊 Tóm tắt</h2>';
        echo '<div class="check-item success"><span class="icon">✅</span> Thành công: ' . count($success) . ' mục</div>';
        echo '<div class="check-item warning"><span class="icon">⚠️</span> Cảnh báo: ' . count($warnings) . ' mục</div>';
        echo '<div class="check-item error"><span class="icon">❌</span> Lỗi: ' . count($errors) . ' mục</div>';
        echo '</div>';

        // 7. Hướng dẫn fix
        if (count($errors) > 0) {
            echo '<div class="section">';
            echo '<h2>🔧 Hướng dẫn xử lý</h2>';
            echo '<ol style="margin-left: 20px; line-height: 2;">';
            
            if (in_array('APP_KEY chưa được set', $errors)) {
                echo '<li><strong>APP_KEY chưa được set:</strong> Chạy file <code>generate-key.php</code> để tạo APP_KEY</li>';
            }
            
            if (in_array('File .env không tồn tại', $errors)) {
                echo '<li><strong>File .env không tồn tại:</strong> Copy file <code>.env.example</code> thành <code>.env</code> và cấu hình</li>';
            }
            
            if (in_array('Vendor folder không tồn tại', $errors) || in_array('Laravel packages chưa được cài đặt', $errors)) {
                echo '<li><strong>Vendor thiếu:</strong> Upload vendor folder hoặc chạy composer install</li>';
            }
            
            if (in_array('Storage folder không có quyền ghi', $errors) || in_array('Bootstrap/cache không có quyền ghi', $errors)) {
                echo '<li><strong>Permissions sai:</strong> Set permission 755 hoặc 775 cho <code>storage/</code> và <code>bootstrap/cache/</code></li>';
            }
            
            if (in_array('Database connection thất bại', $errors)) {
                echo '<li><strong>Database connection lỗi:</strong> Kiểm tra thông tin DB trong <code>.env</code> và chạy <code>test-database.php</code></li>';
            }
            
            echo '<li><strong>Xóa cache:</strong> Chạy file <code>clear-cache-deploy.php</code> để xóa cache</li>';
            echo '</ol>';
            echo '</div>';
        }
        ?>

        <div class="warning-box">
            ⚠️ BẢO MẬT: XÓA FILE check-error.php SAU KHI DÙNG XONG!
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="clear-cache-deploy.php" class="btn">🧹 Clear Cache</a>
            <a href="generate-key.php" class="btn">🔑 Generate APP_KEY</a>
            <a href="test-database.php" class="btn">🗄️ Test Database</a>
        </div>
    </div>
</body>
</html>

