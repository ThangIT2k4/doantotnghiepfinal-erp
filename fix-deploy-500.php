<?php
/**
 * ============================================================================
 * FIX DEPLOY 500 ERROR - Tổng hợp xử lý lỗi 500
 * ============================================================================
 * 
 * Script này tự động xử lý các vấn đề thường gặp khi deploy gây lỗi 500
 * 
 * CÁCH DÙNG:
 * 1. Upload file này lên root của project trên server
 * 2. Truy cập: https://your-domain.com/fix-deploy-500.php
 * 3. Script sẽ tự động kiểm tra và fix các vấn đề
 * 4. ⚠️ XÓA FILE NÀY SAU KHI DÙNG XONG để bảo mật!
 */

// Bật error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Deploy 500 Error</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
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
        .step {
            margin: 25px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .step h2 {
            color: #667eea;
            margin-bottom: 15px;
        }
        .result {
            padding: 12px;
            margin: 8px 0;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }
        .result.success {
            background: #d4edda;
            color: #155724;
        }
        .result.error {
            background: #f8d7da;
            color: #721c24;
        }
        .result.warning {
            background: #fff3cd;
            color: #856404;
        }
        .result.info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .icon {
            margin-right: 10px;
            font-size: 1.2em;
        }
        .summary {
            background: #d4edda;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
            text-align: center;
        }
        .summary h2 {
            color: #155724;
            margin-bottom: 15px;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Deploy 500 Error</h1>
        <p style="color: #666; margin-bottom: 30px;">Script này sẽ tự động kiểm tra và xử lý các vấn đề thường gặp khi deploy.</p>

        <?php
        $fixes = [];
        $errors = [];
        
        // STEP 1: Xóa cache files
        echo '<div class="step">';
        echo '<h2>Bước 1: Xóa Cache Files</h2>';
        
        $cacheFiles = [
            'bootstrap/cache/routes-v7.php',
            'bootstrap/cache/config.php',
            'bootstrap/cache/packages.php',
            'bootstrap/cache/services.php',
        ];
        
        $deletedCount = 0;
        foreach ($cacheFiles as $file) {
            if (file_exists($file)) {
                if (unlink($file)) {
                    $deletedCount++;
                    echo '<div class="result success"><span class="icon">✅</span> Đã xóa: ' . htmlspecialchars($file) . '</div>';
                } else {
                    $errors[] = "Không thể xóa: $file";
                    echo '<div class="result error"><span class="icon">❌</span> Không thể xóa: ' . htmlspecialchars($file) . '</div>';
                }
            }
        }
        
        if ($deletedCount > 0) {
            $fixes[] = "Đã xóa $deletedCount cache files";
        }
        
        // Xóa view cache
        $viewCacheDir = 'storage/framework/views';
        if (is_dir($viewCacheDir)) {
            $viewFiles = glob($viewCacheDir . '/*.php');
            $viewDeleted = 0;
            foreach ($viewFiles as $file) {
                if (is_file($file) && unlink($file)) {
                    $viewDeleted++;
                }
            }
            if ($viewDeleted > 0) {
                $fixes[] = "Đã xóa $viewDeleted view cache files";
                echo '<div class="result success"><span class="icon">✅</span> Đã xóa ' . $viewDeleted . ' view cache files</div>';
            }
        }
        
        echo '</div>';
        
        // STEP 2: Kiểm tra và tạo APP_KEY nếu cần
        echo '<div class="step">';
        echo '<h2>Bước 2: Kiểm tra APP_KEY</h2>';
        
        if (file_exists('.env')) {
            $env = file_get_contents('.env');
            
            if (preg_match('/APP_KEY=base64:[A-Za-z0-9+\/=]+/', $env)) {
                echo '<div class="result success"><span class="icon">✅</span> APP_KEY đã được set</div>';
            } else {
                // Tạo APP_KEY mới
                $newKey = 'base64:' . base64_encode(random_bytes(32));
                
                // Cập nhật .env
                if (preg_match('/APP_KEY=.*/', $env)) {
                    $env = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $newKey, $env);
                } else {
                    $env .= "\nAPP_KEY=" . $newKey . "\n";
                }
                
                if (file_put_contents('.env', $env)) {
                    $fixes[] = "Đã tạo và cập nhật APP_KEY";
                    echo '<div class="result success"><span class="icon">✅</span> Đã tạo và cập nhật APP_KEY</div>';
                } else {
                    $errors[] = "Không thể cập nhật APP_KEY trong .env";
                    echo '<div class="result error"><span class="icon">❌</span> Không thể cập nhật APP_KEY - Cần set thủ công</div>';
                    echo '<div class="result info"><span class="icon">ℹ️</span> APP_KEY cần set: <code>' . htmlspecialchars($newKey) . '</code></div>';
                }
            }
        } else {
            $errors[] = "File .env không tồn tại";
            echo '<div class="result error"><span class="icon">❌</span> File .env không tồn tại - Cần tạo file .env</div>';
        }
        
        echo '</div>';
        
        // STEP 3: Kiểm tra permissions
        echo '<div class="step">';
        echo '<h2>Bước 3: Kiểm tra Permissions</h2>';
        
        $dirs = [
            'storage' => 'Storage folder',
            'bootstrap/cache' => 'Bootstrap cache folder',
        ];
        
        foreach ($dirs as $dir => $name) {
            if (is_dir($dir)) {
                if (is_writable($dir)) {
                    echo '<div class="result success"><span class="icon">✅</span> ' . $name . ' có quyền ghi</div>';
                } else {
                    $errors[] = "$name không có quyền ghi";
                    echo '<div class="result warning"><span class="icon">⚠️</span> ' . $name . ' KHÔNG có quyền ghi - Cần set permission 755 hoặc 775</div>';
                }
            } else {
                $errors[] = "$name không tồn tại";
                echo '<div class="result error"><span class="icon">❌</span> ' . $name . ' không tồn tại</div>';
            }
        }
        
        echo '</div>';
        
        // STEP 4: Kiểm tra vendor
        echo '<div class="step">';
        echo '<h2>Bước 4: Kiểm tra Vendor</h2>';
        
        if (is_dir('vendor')) {
            if (file_exists('vendor/autoload.php')) {
                echo '<div class="result success"><span class="icon">✅</span> Vendor folder và autoload tồn tại</div>';
            } else {
                $errors[] = "Vendor autoload không tồn tại";
                echo '<div class="result error"><span class="icon">❌</span> Vendor autoload không tồn tại - Cần chạy composer install</div>';
            }
        } else {
            $errors[] = "Vendor folder không tồn tại";
            echo '<div class="result error"><span class="icon">❌</span> Vendor folder không tồn tại - Cần upload vendor hoặc chạy composer install</div>';
        }
        
        echo '</div>';
        
        // STEP 5: Kiểm tra file cấu hình quan trọng
        echo '<div class="step">';
        echo '<h2>Bước 5: Kiểm tra Files Quan Trọng</h2>';
        
        $importantFiles = [
            'bootstrap/app.php' => 'Bootstrap app file',
            'public/index.php' => 'Public index file',
            'composer.json' => 'Composer config',
        ];
        
        foreach ($importantFiles as $file => $name) {
            if (file_exists($file)) {
                echo '<div class="result success"><span class="icon">✅</span> ' . $name . ' tồn tại</div>';
            } else {
                $errors[] = "$name không tồn tại";
                echo '<div class="result error"><span class="icon">❌</span> ' . $name . ' không tồn tại</div>';
            }
        }
        
        // Thử load Laravel (nếu có thể)
        echo '<h3 style="margin-top: 15px; color: #667eea;">Test Laravel (Optional):</h3>';
        
        try {
            if (file_exists('vendor/autoload.php') && file_exists('bootstrap/app.php')) {
                require __DIR__.'/vendor/autoload.php';
                $app = require_once __DIR__.'/bootstrap/app.php';
                $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
                $kernel->bootstrap();
                
                echo '<div class="result success"><span class="icon">✅</span> Laravel load thành công</div>';
                
                // Test database
                try {
                    $pdo = DB::connection()->getPdo();
                    echo '<div class="result success"><span class="icon">✅</span> Database connection thành công</div>';
                } catch (Exception $e) {
                    $warnings[] = "Database connection failed: " . $e->getMessage();
                    echo '<div class="result warning"><span class="icon">⚠️</span> Database connection thất bại: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                
            } else {
                echo '<div class="result info"><span class="icon">ℹ️</span> Không thể load Laravel - Cần fix các vấn đề ở trên trước</div>';
            }
        } catch (Exception $e) {
            $warnings[] = "Laravel load error: " . $e->getMessage();
            echo '<div class="result warning"><span class="icon">⚠️</span> Không thể load Laravel: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        echo '</div>';
        
        // Summary
        echo '<div class="summary">';
        echo '<h2>📊 Tóm tắt</h2>';
        echo '<p style="font-size: 1.1em; margin: 10px 0;">';
        echo '<strong>Đã xử lý:</strong> ' . count($fixes) . ' vấn đề<br>';
        echo '<strong>Còn lỗi:</strong> ' . count($errors) . ' vấn đề';
        echo '</p>';
        
        if (count($fixes) > 0) {
            echo '<div style="text-align: left; margin-top: 15px;">';
            echo '<strong>✅ Đã fix:</strong><ul style="margin-left: 20px; margin-top: 10px;">';
            foreach ($fixes as $fix) {
                echo '<li>' . htmlspecialchars($fix) . '</li>';
            }
            echo '</ul></div>';
        }
        
        if (count($errors) > 0) {
            echo '<div style="text-align: left; margin-top: 15px; background: #f8d7da; padding: 15px; border-radius: 6px;">';
            echo '<strong>❌ Cần xử lý thêm:</strong><ul style="margin-left: 20px; margin-top: 10px;">';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
        }
        
        if (count($errors) == 0 && count($fixes) > 0) {
            echo '<p style="font-size: 1.2em; margin-top: 20px; color: #155724; font-weight: bold;">✅ Tất cả đã được xử lý! Hãy refresh website và kiểm tra lại.</p>';
        } elseif (count($errors) > 0) {
            echo '<p style="font-size: 1.1em; margin-top: 20px; color: #721c24; font-weight: bold;">⚠️ Vẫn còn một số lỗi cần xử lý. Xem chi tiết ở trên.</p>';
        }
        
        echo '</div>';
        ?>

        <div class="warning-box">
            ⚠️ BẢO MẬT: XÓA FILE fix-deploy-500.php SAU KHI DÙNG XONG!
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="check-error.php" style="background: #667eea; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; display: inline-block; margin: 5px;">🔍 Check Error</a>
            <a href="test-database.php" style="background: #28a745; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; display: inline-block; margin: 5px;">🗄️ Test Database</a>
        </div>
    </div>
</body>
</html>

