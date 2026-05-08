<?php
/**
 * Clear Route Cache Helper
 * 
 * Sử dụng file này để clear route cache khi gặp lỗi 405
 * 
 * Cách sử dụng:
 * 1. Upload file này lên server (thư mục root: public_html/)
 * 2. Truy cập: http://103.18.6.36/~lpi0g927o3nw/clear-route-cache.php
 * 3. XÓA FILE NÀY sau khi dùng xong (bảo mật!)
 */

// Kiểm tra vendor/autoload.php
if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    die('Error: vendor/autoload.php not found. Please ensure vendor/ folder is uploaded.');
}

require __DIR__.'/vendor/autoload.php';

// Kiểm tra bootstrap/app.php
if (!file_exists(__DIR__.'/bootstrap/app.php')) {
    die('Error: bootstrap/app.php not found.');
}

try {
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Clear Route Cache</title>
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
                padding: 10px;
                margin: 5px 0;
                background: #f0f9f0;
                border-left: 4px solid #4CAF50;
            }
            .error {
                color: #dc3545;
                padding: 10px;
                margin: 5px 0;
                background: #fff5f5;
                border-left: 4px solid #dc3545;
            }
            .info {
                color: #666;
                padding: 10px;
                margin: 5px 0;
                background: #f9f9f9;
                border-left: 4px solid #999;
            }
            .warning {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 15px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🧹 Clearing Route Cache</h1>
            
            <?php
            $results = [];
            $errors = [];

            // Clear route cache
            $routeCache = __DIR__.'/bootstrap/cache/routes.php';
            if (file_exists($routeCache)) {
                if (unlink($routeCache)) {
                    $results[] = '✅ Route cache cleared successfully';
                } else {
                    $errors[] = '❌ Failed to delete route cache file';
                }
            } else {
                $results[] = 'ℹ️ Route cache file not found (already cleared)';
            }

            // Clear config cache
            $configCache = __DIR__.'/bootstrap/cache/config.php';
            if (file_exists($configCache)) {
                if (unlink($configCache)) {
                    $results[] = '✅ Config cache cleared';
                } else {
                    $errors[] = '❌ Failed to delete config cache file';
                }
            } else {
                $results[] = 'ℹ️ Config cache file not found (already cleared)';
            }

            // Clear view cache
            $viewCachePath = __DIR__.'/storage/framework/views';
            if (is_dir($viewCachePath)) {
                $files = glob($viewCachePath.'/*');
                $count = 0;
                foreach ($files as $file) {
                    if (is_file($file)) {
                        if (unlink($file)) {
                            $count++;
                        }
                    }
                }
                if ($count > 0) {
                    $results[] = "✅ View cache cleared ({$count} files)";
                } else {
                    $results[] = 'ℹ️ View cache already empty';
                }
            }

            // Clear application cache
            $appCachePath = __DIR__.'/storage/framework/cache/data';
            if (is_dir($appCachePath)) {
                $files = glob($appCachePath.'/*');
                $count = 0;
                foreach ($files as $file) {
                    if (is_file($file)) {
                        if (unlink($file)) {
                            $count++;
                        }
                    }
                }
                if ($count > 0) {
                    $results[] = "✅ Application cache cleared ({$count} files)";
                }
            }

            // Display results
            if (!empty($results)) {
                echo '<h2>✅ Results:</h2>';
                foreach ($results as $result) {
                    echo '<div class="success">' . htmlspecialchars($result) . '</div>';
                }
            }

            if (!empty($errors)) {
                echo '<h2>❌ Errors:</h2>';
                foreach ($errors as $error) {
                    echo '<div class="error">' . htmlspecialchars($error) . '</div>';
                }
            }

            if (empty($errors)) {
                echo '<div class="success" style="margin-top: 20px; font-weight: bold;">';
                echo '🎉 All cache cleared successfully!';
                echo '</div>';
            }
            ?>

            <div class="warning" style="margin-top: 30px;">
                <strong>⚠️ Lưu ý bảo mật:</strong>
                <p>Vui lòng <strong>XÓA FILE NÀY</strong> sau khi sử dụng để đảm bảo bảo mật!</p>
            </div>

            <div class="info" style="margin-top: 20px;">
                <strong>📝 Bước tiếp theo:</strong>
                <ol>
                    <li>Refresh website: <code>http://103.18.6.36/~lpi0g927o3nw/public/</code></li>
                    <li>Nếu vẫn lỗi, kiểm tra file <code>.env</code> và <code>APP_KEY</code></li>
                    <li>Kiểm tra permissions của <code>storage/</code> và <code>bootstrap/cache/</code></li>
                </ol>
            </div>
        </div>
    </body>
    </html>
    <?php
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error - Clear Cache</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .error {
                background: #f8d7da;
                border-left: 4px solid #dc3545;
                padding: 15px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>❌ Error occurred:</h2>
            <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
            <p><strong>Please check:</strong></p>
            <ul>
                <li>vendor/ folder is uploaded correctly</li>
                <li>bootstrap/app.php exists</li>
                <li>PHP version is 8.2+</li>
                <li>File permissions are correct</li>
            </ul>
        </div>
    </body>
    </html>
    <?php
}
?>

