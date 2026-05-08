<?php
/**
 * Fix Lỗi 405 Method Not Allowed cho SePay Webhook
 * 
 * Script này sẽ clear tất cả cache để fix lỗi 405 khi SePay test URL webhook
 * 
 * Cách sử dụng:
 * 1. Upload file này lên thư mục PUBLIC của Laravel trên server (public/)
 * 2. Truy cập: https://ZoroRMS.click/fix-sepay-webhook-405.php
 * 3. Xem kết quả và xóa file này sau khi xong (bảo mật)
 * 
 * LƯU Ý: File phải được đặt trong thư mục public/ (không phải root)
 */

// Security: Chỉ cho phép chạy từ browser hoặc CLI
if (php_sapi_name() !== 'cli' && !isset($_SERVER['HTTP_HOST'])) {
    die('Access denied');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix SePay Webhook 405 Error</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
            margin: 10px 0;
        }
        .error {
            color: #f44336;
            font-weight: bold;
            margin: 10px 0;
        }
        .info {
            color: #2196F3;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .step {
            margin: 15px 0;
            padding: 10px;
            background: #f9f9f9;
            border-left: 4px solid #4CAF50;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix SePay Webhook 405 Error</h1>
        
        <?php
        // Xác định root directory của Laravel (lên 1 cấp từ public/)
        $publicDir = __DIR__;
        $rootDir = dirname($publicDir); // Lên 1 cấp từ public/ về root
        
        $errors = [];
        $success = [];
        
        echo '<div class="step">';
        echo '<h3>📋 Đang kiểm tra và clear cache...</h3>';
        echo '</div>';
        
        // 1. Clear route cache
        echo '<div class="step">';
        echo '<strong>[1/5] Clear Route Cache</strong><br>';
        $routeCacheFiles = [
            'bootstrap/cache/routes-v7.php',
            'bootstrap/cache/routes.php',
        ];
        
        foreach ($routeCacheFiles as $file) {
            $filePath = $rootDir . '/' . $file;
            if (file_exists($filePath)) {
                if (unlink($filePath)) {
                    echo '<span class="success">✓ Đã xóa: ' . $file . '</span><br>';
                    $success[] = $file;
                } else {
                    echo '<span class="error">✗ Không thể xóa: ' . $file . '</span><br>';
                    $errors[] = $file;
                }
            } else {
                echo '<span class="info">- File không tồn tại: ' . $file . ' (OK)</span><br>';
            }
        }
        echo '</div>';
        
        // 2. Clear config cache
        echo '<div class="step">';
        echo '<strong>[2/5] Clear Config Cache</strong><br>';
        $configCache = $rootDir . '/bootstrap/cache/config.php';
        if (file_exists($configCache)) {
            if (unlink($configCache)) {
                echo '<span class="success">✓ Đã xóa: bootstrap/cache/config.php</span><br>';
                $success[] = 'config.php';
            } else {
                echo '<span class="error">✗ Không thể xóa: bootstrap/cache/config.php</span><br>';
                $errors[] = 'config.php';
            }
        } else {
            echo '<span class="info">- File không tồn tại (OK)</span><br>';
        }
        echo '</div>';
        
        // 3. Clear view cache
        echo '<div class="step">';
        echo '<strong>[3/5] Clear View Cache</strong><br>';
        $viewsDir = $rootDir . '/storage/framework/views';
        if (is_dir($viewsDir)) {
            $files = glob($viewsDir . '/*.php');
            $deleted = 0;
            foreach ($files as $file) {
                if (is_file($file) && unlink($file)) {
                    $deleted++;
                }
            }
            if ($deleted > 0) {
                echo '<span class="success">✓ Đã xóa ' . $deleted . ' file view cache</span><br>';
                $success[] = 'view cache';
            } else {
                echo '<span class="info">- Không có file view cache để xóa (OK)</span><br>';
            }
        } else {
            echo '<span class="info">- Thư mục views không tồn tại (OK)</span><br>';
        }
        echo '</div>';
        
        // 4. Clear application cache
        echo '<div class="step">';
        echo '<strong>[4/5] Clear Application Cache</strong><br>';
        $appCacheDir = $rootDir . '/storage/framework/cache/data';
        if (is_dir($appCacheDir)) {
            $files = glob($appCacheDir . '/*');
            $deleted = 0;
            foreach ($files as $file) {
                if (is_file($file) && unlink($file)) {
                    $deleted++;
                } elseif (is_dir($file)) {
                    // Xóa cả thư mục con
                    array_map('unlink', glob($file . '/*'));
                    rmdir($file);
                    $deleted++;
                }
            }
            if ($deleted > 0) {
                echo '<span class="success">✓ Đã xóa application cache</span><br>';
                $success[] = 'app cache';
            } else {
                echo '<span class="info">- Không có application cache để xóa (OK)</span><br>';
            }
        } else {
            echo '<span class="info">- Thư mục cache không tồn tại (OK)</span><br>';
        }
        echo '</div>';
        
        // 5. Clear bootstrap cache files
        echo '<div class="step">';
        echo '<strong>[5/5] Clear Bootstrap Cache Files</strong><br>';
        $bootstrapCacheDir = $rootDir . '/bootstrap/cache';
        if (is_dir($bootstrapCacheDir)) {
            $cacheFiles = glob($bootstrapCacheDir . '/*.php');
            $deleted = 0;
            foreach ($cacheFiles as $file) {
                $fileName = basename($file);
                // Giữ lại .gitignore
                if ($fileName !== '.gitignore' && is_file($file)) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
            if ($deleted > 0) {
                echo '<span class="success">✓ Đã xóa ' . $deleted . ' file bootstrap cache</span><br>';
                $success[] = 'bootstrap cache';
            } else {
                echo '<span class="info">- Không có bootstrap cache để xóa (OK)</span><br>';
            }
        }
        echo '</div>';
        
        // Summary
        echo '<div style="margin-top: 30px; padding: 20px; background: #e8f5e9; border-radius: 4px;">';
        echo '<h2>✅ Kết quả</h2>';
        echo '<p><strong>Đã xóa thành công:</strong> ' . count($success) . ' loại cache</p>';
        if (count($errors) > 0) {
            echo '<p class="error"><strong>Lỗi:</strong> ' . count($errors) . ' file không thể xóa</p>';
        }
        echo '</div>';
        
        // Test URL
        echo '<div class="warning" style="margin-top: 20px;">';
        echo '<h3>🧪 Test Webhook URL</h3>';
        echo '<p>Bây giờ bạn có thể test URL webhook:</p>';
        echo '<p><code>https://ZoroRMS.click/api/webhooks/sepay</code></p>';
        echo '<p>URL này sẽ trả về JSON xác nhận endpoint đang hoạt động.</p>';
        echo '</div>';
        
        // Security warning
        echo '<div class="warning" style="margin-top: 20px; background: #ffebee; border-color: #f44336;">';
        echo '<h3>⚠️ QUAN TRỌNG - Bảo mật</h3>';
        echo '<p><strong>Vui lòng XÓA file này ngay sau khi sử dụng!</strong></p>';
        echo '<p>File: <code>fix-sepay-webhook-405.php</code></p>';
        echo '</div>';
        ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 4px;">
            <h3>📝 Bước tiếp theo</h3>
            <ol>
                <li>Test URL webhook: <code>https://ZoroRMS.click/api/webhooks/sepay</code></li>
                <li>Cấu hình lại webhook trong SePay Dashboard với URL: <code>https://ZoroRMS.click/api/webhooks/sepay</code></li>
                <li><strong>XÓA file này</strong> để bảo mật (file nằm trong thư mục <code>public/</code>)</li>
            </ol>
        </div>
        
        <div class="info" style="margin-top: 20px;">
            <strong>ℹ️ Thông tin:</strong>
            <p>File này đang chạy từ: <code><?php echo htmlspecialchars($publicDir); ?></code></p>
            <p>Laravel root directory: <code><?php echo htmlspecialchars($rootDir); ?></code></p>
        </div>
    </div>
</body>
</html>

