<?php
/**
 * ============================================================================
 * OPTIMIZE FOR PRODUCTION
 * ============================================================================
 * 
 * Script tối ưu Laravel cho môi trường production
 * 
 * CÁCH DÙNG:
 * 1. Upload file này lên root của project trên server
 * 2. Truy cập: https://your-domain.com/optimize-production.php
 * 3. ⚠️ XÓA FILE NÀY SAU KHI CHẠY XONG!
 * 
 * CHỨC NĂNG:
 * - Cache config
 * - Cache routes
 * - Cache views
 * - Optimize autoloader
 * - Clear old cache
 */

set_time_limit(120);
ini_set('memory_limit', '256M');
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Optimize for Production</title>
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
        h2 {
            color: #764ba2;
            margin: 25px 0 15px;
            font-size: 1.3em;
        }
        .output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.8;
            margin: 15px 0;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .info {
            color: #17a2b8;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .step {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #667eea;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            margin-right: 10px;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-error { background: #f8d7da; color: #721c24; }
        .progress {
            background: #e9ecef;
            border-radius: 8px;
            height: 30px;
            margin: 15px 0;
            overflow: hidden;
        }
        .progress-bar {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            line-height: 30px;
            color: white;
            text-align: center;
            font-weight: bold;
            transition: width 0.5s;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚡ Optimize for Production</h1>

        <?php
        try {
            // Load Laravel
            if (!file_exists('vendor/autoload.php')) {
                throw new Exception('❌ Không tìm thấy vendor/autoload.php');
            }

            require __DIR__.'/vendor/autoload.php';
            $app = require_once __DIR__.'/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();

            echo "<div class='output'>";
            echo "<div class='info'>Laravel Application Loaded</div>\n";
            echo "</div>";

            // Bước 1: Clear old cache
            echo "<h2>🧹 Step 1: Clear Old Cache</h2>";
            
            $clearOperations = [
                ['name' => 'Config', 'command' => 'config:clear'],
                ['name' => 'Route', 'command' => 'route:clear'],
                ['name' => 'View', 'command' => 'view:clear'],
                ['name' => 'Cache', 'command' => 'cache:clear'],
            ];

            $step1Success = true;
            foreach ($clearOperations as $op) {
                echo "<div class='step'>";
                echo "Clearing {$op['name']} cache...";
                
                try {
                    Artisan::call($op['command']);
                    echo " <span class='badge badge-success'>✅ Done</span>";
                } catch (Exception $e) {
                    echo " <span class='badge badge-error'>❌ Error</span>";
                    $step1Success = false;
                }
                
                echo "</div>";
            }

            // Bước 2: Cache for production
            echo "<h2>🚀 Step 2: Cache for Production</h2>";
            
            $cacheOperations = [
                [
                    'name' => 'Config Cache',
                    'command' => 'config:cache',
                    'description' => 'Cache configuration files for faster loading'
                ],
                [
                    'name' => 'Route Cache',
                    'command' => 'route:cache',
                    'description' => 'Cache route definitions for faster routing'
                ],
                [
                    'name' => 'View Cache',
                    'command' => 'view:cache',
                    'description' => 'Compile Blade templates for faster rendering'
                ],
            ];

            $step2Success = true;
            foreach ($cacheOperations as $op) {
                echo "<div class='step'>";
                echo "<strong>{$op['name']}</strong><br>";
                echo "<small>{$op['description']}</small><br>";
                
                try {
                    ob_start();
                    Artisan::call($op['command']);
                    $output = Artisan::output();
                    ob_end_clean();
                    
                    echo "<span class='badge badge-success'>✅ Cached</span>";
                    echo "<div class='output' style='margin-top:10px; font-size:12px;'>";
                    echo htmlspecialchars($output);
                    echo "</div>";
                } catch (Exception $e) {
                    echo "<span class='badge badge-error'>❌ Failed</span>";
                    echo "<div class='error'>" . htmlspecialchars($e->getMessage()) . "</div>";
                    $step2Success = false;
                }
                
                echo "</div>";
            }

            // Bước 3: Optimize autoloader
            echo "<h2>⚙️ Step 3: Optimize Autoloader</h2>";
            echo "<div class='step'>";
            
            try {
                ob_start();
                Artisan::call('optimize');
                $output = Artisan::output();
                ob_end_clean();
                
                echo "<span class='badge badge-success'>✅ Optimized</span>";
                echo "<div class='output' style='margin-top:10px; font-size:12px;'>";
                echo htmlspecialchars($output);
                echo "</div>";
                $step3Success = true;
            } catch (Exception $e) {
                echo "<span class='badge badge-error'>❌ Failed</span>";
                echo "<div class='error'>" . htmlspecialchars($e->getMessage()) . "</div>";
                $step3Success = false;
            }
            
            echo "</div>";

            // Bước 4: Verify cache files
            echo "<h2>✅ Step 4: Verify Cache Files</h2>";
            echo "<div class='output'>";
            
            $cacheFiles = [
                'bootstrap/cache/config.php' => 'Config cache',
                'bootstrap/cache/routes-v7.php' => 'Route cache',
                'bootstrap/cache/packages.php' => 'Package manifest',
            ];
            
            $verifySuccess = true;
            foreach ($cacheFiles as $file => $description) {
                if (file_exists($file)) {
                    $size = filesize($file);
                    $sizeKB = round($size / 1024, 2);
                    echo "✅ $description: $file ($sizeKB KB)\n";
                } else {
                    echo "⚠️  $description: Not found (might be optional)\n";
                }
            }
            
            echo "</div>";

            // Summary
            echo "<h2>📊 Summary</h2>";
            echo "<div class='output'>";
            
            $allSuccess = $step1Success && $step2Success && $step3Success;
            
            echo "Step 1 (Clear): " . ($step1Success ? "✅ Success" : "❌ Failed") . "\n";
            echo "Step 2 (Cache): " . ($step2Success ? "✅ Success" : "❌ Failed") . "\n";
            echo "Step 3 (Optimize): " . ($step3Success ? "✅ Success" : "❌ Failed") . "\n";
            
            if ($allSuccess) {
                echo "\n<div class='success'>✅ ALL OPTIMIZATION COMPLETED!</div>\n";
            } else {
                echo "\n<div class='error'>⚠️ Some optimizations failed. Check details above.</div>\n";
            }
            
            echo "</div>";

            // Performance tips
            echo "<div class='warning'>";
            echo "<strong>💡 Performance Tips:</strong><br><br>";
            echo "1. ✅ Đã cache config, routes, views<br>";
            echo "2. ✅ Đã optimize autoloader<br>";
            echo "3. ⚠️ Đảm bảo <code>APP_DEBUG=false</code> trong .env<br>";
            echo "4. ⚠️ Sử dụng <code>CACHE_DRIVER=redis</code> hoặc <code>memcached</code> nếu có thể<br>";
            echo "5. ⚠️ Sử dụng <code>SESSION_DRIVER=redis</code> hoặc <code>database</code> cho nhiều server<br>";
            echo "6. ⚠️ Enable OPcache trên PHP (nếu hosting hỗ trợ)<br>";
            echo "</div>";

            // Next steps
            echo "<div class='warning'>";
            echo "<strong>📋 Bước tiếp theo:</strong><br><br>";
            echo "1. ✅ Test lại website<br>";
            echo "2. ✅ Kiểm tra tốc độ load trang (nên nhanh hơn trước)<br>";
            echo "3. ⚠️ Nếu sửa .env, chạy lại script này để cache config mới<br>";
            echo "4. ⚠️ Nếu sửa routes, chạy lại để cache routes mới<br>";
            echo "5. ⚠️ <strong>XÓA FILE optimize-production.php này để bảo mật!</strong><br>";
            echo "</div>";

        } catch (Exception $e) {
            echo "<div class='output'>";
            echo "<div class='error'>❌ LỖI: " . htmlspecialchars($e->getMessage()) . "</div>\n";
            echo "</div>";

            echo "<div class='warning'>";
            echo "<strong>⚠️ Lỗi xảy ra!</strong><br>";
            echo "Đảm bảo vendor đã được cài đặt đầy đủ.";
            echo "</div>";
        }
        ?>

        <div class="warning" style="margin-top: 30px;">
            <strong>🔒 BẢO MẬT:</strong><br>
            <strong style="color: #dc3545;">⚠️ XÓA FILE optimize-production.php SAU KHI CHẠY XONG!</strong>
        </div>

        <p style="color: #666; font-size: 0.9em; text-align: center; margin-top: 30px;">
            Laravel Production Optimizer | <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>
</body>
</html>

