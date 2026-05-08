<?php
/**
 * Generate APP_KEY Helper
 * 
 * File này sẽ tạo APP_KEY cho Laravel
 * 
 * Truy cập: http://103.18.6.36/~lpi0g927o3nw/generate-app-key.php
 * XÓA FILE NÀY sau khi dùng xong!
 */

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    die('Error: vendor/autoload.php not found');
}

require __DIR__.'/vendor/autoload.php';

if (!file_exists(__DIR__.'/bootstrap/app.php')) {
    die('Error: bootstrap/app.php not found');
}

try {
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // Generate APP_KEY
    $key = 'base64:'.base64_encode(random_bytes(32));

    // Check if .env exists
    $envFile = __DIR__.'/.env';
    $envContent = '';
    
    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
    }

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Generate APP_KEY</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
            .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
            .success { color: #4CAF50; padding: 15px; margin: 20px 0; background: #f0f9f0; border-left: 4px solid #4CAF50; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            .info { color: #666; padding: 15px; margin: 20px 0; background: #f9f9f9; border-left: 4px solid #999; }
            .key-box { background: #f4f4f4; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0; word-break: break-all; font-family: monospace; font-size: 14px; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
            .button { background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 10px 5px; }
            .button:hover { background: #45a049; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔑 Generate APP_KEY</h1>
            
            <?php
            // Check if APP_KEY already exists
            $hasAppKey = false;
            if (file_exists($envFile)) {
                $hasAppKey = preg_match('/APP_KEY=base64:.+/', $envContent);
            }

            if ($hasAppKey) {
                echo '<div class="warning">';
                echo '<strong>⚠️ APP_KEY already exists in .env file!</strong><br>';
                echo 'If you want to generate a new one, you need to manually edit .env file.';
                echo '</div>';
            } else {
                echo '<div class="success">';
                echo '<strong>✅ Generated APP_KEY:</strong>';
                echo '</div>';
                
                echo '<div class="key-box">';
                echo '<code>APP_KEY=' . htmlspecialchars($key) . '</code>';
                echo '</div>';

                // Try to update .env file automatically
                if (file_exists($envFile)) {
                    // Check if APP_KEY line exists but empty
                    if (preg_match('/APP_KEY=\s*$/', $envContent) || !preg_match('/APP_KEY=/', $envContent)) {
                        // Add or update APP_KEY
                        if (preg_match('/APP_KEY=/', $envContent)) {
                            // Replace existing empty APP_KEY
                            $newEnvContent = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $key, $envContent);
                        } else {
                            // Add APP_KEY after APP_ENV
                            if (preg_match('/(APP_ENV=.*)/', $envContent, $matches)) {
                                $newEnvContent = preg_replace('/(APP_ENV=.*)/', "$1\nAPP_KEY=" . $key, $envContent);
                            } else {
                                // Add at the beginning
                                $newEnvContent = "APP_KEY=" . $key . "\n" . $envContent;
                            }
                        }
                        
                        // Try to write to file
                        if (is_writable($envFile)) {
                            if (file_put_contents($envFile, $newEnvContent)) {
                                echo '<div class="success">';
                                echo '<strong>✅ APP_KEY đã được tự động thêm vào file .env!</strong><br>';
                                echo 'File .env đã được cập nhật.';
                                echo '</div>';
                            } else {
                                echo '<div class="warning">';
                                echo '<strong>⚠️ Không thể tự động cập nhật file .env</strong><br>';
                                echo 'Vui lòng copy APP_KEY ở trên và thêm vào file .env thủ công.';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="warning">';
                            echo '<strong>⚠️ File .env không thể ghi được</strong><br>';
                            echo 'Vui lòng copy APP_KEY ở trên và thêm vào file .env thủ công.';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="info">';
                        echo '<strong>ℹ️ File .env đã có APP_KEY (có thể đang trống)</strong><br>';
                        echo 'Vui lòng copy APP_KEY ở trên và thay thế trong file .env.';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="warning">';
                    echo '<strong>⚠️ File .env không tồn tại</strong><br>';
                    echo 'Vui lòng tạo file .env trước, sau đó thêm APP_KEY ở trên vào.';
                    echo '</div>';
                }

                echo '<div class="info">';
                echo '<strong>📝 Hướng dẫn thêm APP_KEY vào .env thủ công:</strong>';
                echo '<ol>';
                echo '<li>Vào cPanel → File Manager</li>';
                echo '<li>Vào thư mục <code>public_html/</code></li>';
                echo '<li>Mở file <code>.env</code> (bật Show Hidden Files nếu không thấy)</li>';
                echo '<li>Tìm dòng <code>APP_KEY=</code></li>';
                echo '<li>Thay thế bằng: <code>APP_KEY=' . htmlspecialchars($key) . '</code></li>';
                echo '<li>Lưu file</li>';
                echo '</ol>';
                echo '</div>';
            }
            ?>

            <div class="warning" style="margin-top: 30px;">
                <strong>⚠️ Bước tiếp theo:</strong>
                <ol>
                    <li>Đảm bảo APP_KEY đã được thêm vào file .env</li>
                    <li>Refresh website: <code>http://103.18.6.36/~lpi0g927o3nw/public/</code></li>
                    <li><strong>XÓA FILE NÀY</strong> sau khi hoàn tất (bảo mật!)</li>
                </ol>
            </div>

            <div style="margin-top: 20px;">
                <a href="?regenerate=1" class="button">🔄 Generate New Key</a>
                <a href="fix-route-complete.php" class="button">🔍 Check Again</a>
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
        <title>Error</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>❌ Error:</h2>
            <p><?php echo htmlspecialchars($e->getMessage()); ?></p>
        </div>
    </body>
    </html>
    <?php
}
?>

