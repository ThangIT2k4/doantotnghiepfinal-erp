<?php
/**
 * ============================================================================
 * CLEAR ALL CACHE - Deploy Fix
 * ============================================================================
 * 
 * Script này xóa tất cả cache files để fix lỗi khi deploy
 * 
 * CÁCH DÙNG:
 * 1. Upload file này lên root của project trên server
 * 2. Truy cập: https://your-domain.com/clear-cache-deploy.php
 * 3. ⚠️ XÓA FILE NÀY SAU KHI DÙNG XONG để bảo mật!
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
    <title>Clear Cache - Deploy Fix</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
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
        .result {
            padding: 15px;
            margin: 10px 0;
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
        .result.info {
            background: #d1ecf1;
            color: #0c5460;
        }
        .icon {
            margin-right: 10px;
            font-size: 1.2em;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Clear All Cache</h1>

        <?php
        $results = [];
        
        // 1. Xóa cache files trong bootstrap/cache
        echo '<h2 style="color: #667eea; margin-top: 20px;">1. Xóa Bootstrap Cache Files</h2>';
        
        $cacheFiles = [
            'bootstrap/cache/routes-v7.php',
            'bootstrap/cache/config.php',
            'bootstrap/cache/packages.php',
            'bootstrap/cache/services.php',
        ];
        
        foreach ($cacheFiles as $file) {
            if (file_exists($file)) {
                if (unlink($file)) {
                    $results[] = ['type' => 'success', 'msg' => "✅ Đã xóa: $file"];
                    echo '<div class="result success"><span class="icon">✅</span> Đã xóa: <code>' . htmlspecialchars($file) . '</code></div>';
                } else {
                    $results[] = ['type' => 'error', 'msg' => "❌ Không thể xóa: $file"];
                    echo '<div class="result error"><span class="icon">❌</span> Không thể xóa: <code>' . htmlspecialchars($file) . '</code> (Permission issue?)</div>';
                }
            } else {
                $results[] = ['type' => 'info', 'msg' => "ℹ️ Không tồn tại: $file"];
                echo '<div class="result info"><span class="icon">ℹ️</span> Không tồn tại: <code>' . htmlspecialchars($file) . '</code></div>';
            }
        }
        
        // 2. Xóa view cache
        echo '<h2 style="color: #667eea; margin-top: 20px;">2. Xóa View Cache</h2>';
        
        $viewCacheDir = 'storage/framework/views';
        if (is_dir($viewCacheDir)) {
            $files = glob($viewCacheDir . '/*.php');
            $deleted = 0;
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
            if ($deleted > 0) {
                $results[] = ['type' => 'success', 'msg' => "✅ Đã xóa $deleted view cache files"];
                echo '<div class="result success"><span class="icon">✅</span> Đã xóa <strong>' . $deleted . '</strong> view cache files</div>';
            } else {
                $results[] = ['type' => 'info', 'msg' => "ℹ️ Không có view cache files"];
                echo '<div class="result info"><span class="icon">ℹ️</span> Không có view cache files</div>';
            }
        } else {
            $results[] = ['type' => 'info', 'msg' => "ℹ️ View cache directory không tồn tại"];
            echo '<div class="result info"><span class="icon">ℹ️</span> View cache directory không tồn tại</div>';
        }
        
        // 3. Xóa compiled class files
        echo '<h2 style="color: #667eea; margin-top: 20px;">3. Xóa Compiled Class Files</h2>';
        
        $compiledDir = 'bootstrap/cache';
        if (is_dir($compiledDir)) {
            $compiledFiles = glob($compiledDir . '/*.php');
            $deleted = 0;
            foreach ($compiledFiles as $file) {
                $fileName = basename($file);
                // Không xóa các file cần thiết, chỉ xóa cache
                if (in_array($fileName, ['routes-v7.php', 'config.php', 'packages.php', 'services.php'])) {
                    continue; // Đã xóa ở trên
                }
                if (is_file($file)) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
            if ($deleted > 0) {
                $results[] = ['type' => 'success', 'msg' => "✅ Đã xóa $deleted compiled files"];
                echo '<div class="result success"><span class="icon">✅</span> Đã xóa <strong>' . $deleted . '</strong> compiled files</div>';
            } else {
                $results[] = ['type' => 'info', 'msg' => "ℹ️ Không có compiled files"];
                echo '<div class="result info"><span class="icon">ℹ️</span> Không có compiled files</div>';
            }
        }
        
        // 4. Thử clear cache bằng Laravel (nếu có thể)
        echo '<h2 style="color: #667eea; margin-top: 20px;">4. Clear Cache bằng Laravel Artisan</h2>';
        
        try {
            if (file_exists('vendor/autoload.php') && file_exists('bootstrap/app.php')) {
                require __DIR__.'/vendor/autoload.php';
                $app = require_once __DIR__.'/bootstrap/app.php';
                $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
                $kernel->bootstrap();
                
                // Clear config cache
                try {
                    Artisan::call('config:clear');
                    $results[] = ['type' => 'success', 'msg' => "✅ Config cache cleared"];
                    echo '<div class="result success"><span class="icon">✅</span> Config cache cleared</div>';
                } catch (Exception $e) {
                    echo '<div class="result info"><span class="icon">ℹ️</span> Config cache: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                
                // Clear route cache
                try {
                    Artisan::call('route:clear');
                    $results[] = ['type' => 'success', 'msg' => "✅ Route cache cleared"];
                    echo '<div class="result success"><span class="icon">✅</span> Route cache cleared</div>';
                } catch (Exception $e) {
                    echo '<div class="result info"><span class="icon">ℹ️</span> Route cache: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                
                // Clear view cache
                try {
                    Artisan::call('view:clear');
                    $results[] = ['type' => 'success', 'msg' => "✅ View cache cleared"];
                    echo '<div class="result success"><span class="icon">✅</span> View cache cleared</div>';
                } catch (Exception $e) {
                    echo '<div class="result info"><span class="icon">ℹ️</span> View cache: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                
                // Clear application cache
                try {
                    Artisan::call('cache:clear');
                    $results[] = ['type' => 'success', 'msg' => "✅ Application cache cleared"];
                    echo '<div class="result success"><span class="icon">✅</span> Application cache cleared</div>';
                } catch (Exception $e) {
                    echo '<div class="result info"><span class="icon">ℹ️</span> Application cache: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                
            } else {
                echo '<div class="result info"><span class="icon">ℹ️</span> Không thể load Laravel - Đã xóa cache files thủ công ở trên</div>';
            }
        } catch (Exception $e) {
            echo '<div class="result info"><span class="icon">ℹ️</span> Không thể dùng Artisan: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // Tóm tắt
        $successCount = count(array_filter($results, function($r) { return $r['type'] === 'success'; }));
        
        echo '<div style="margin-top: 30px; padding: 20px; background: #d4edda; border-radius: 8px; text-align: center;">';
        echo '<h2 style="color: #155724; margin-bottom: 10px;">✅ Hoàn tất!</h2>';
        echo '<p style="color: #155724; font-size: 1.1em;">Đã xóa cache thành công. Bây giờ hãy refresh website và kiểm tra lại.</p>';
        echo '</div>';
        ?>

        <div class="warning-box">
            ⚠️ BẢO MẬT: XÓA FILE clear-cache-deploy.php SAU KHI DÙNG XONG!
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="check-error.php" style="background: #667eea; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; display: inline-block;">🔍 Check Error</a>
        </div>
    </div>
</body>
</html>

