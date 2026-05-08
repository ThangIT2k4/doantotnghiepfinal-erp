<?php
/**
 * ============================================================================
 * CLEAR ALL CACHE VIA WEB BROWSER
 * ============================================================================
 * 
 * Script này giúp clear tất cả cache của Laravel trên hosting mà không cần terminal
 * 
 * CÁCH DÙNG:
 * 1. Upload file này lên root của project trên server
 * 2. Truy cập: https://your-domain.com/clear-all-cache.php
 * 3. ⚠️ XÓA FILE NÀY SAU KHI DÙNG XONG để bảo mật!
 * 
 * CHỨC NĂNG:
 * - Clear config cache
 * - Clear route cache
 * - Clear view cache
 * - Clear application cache
 * - Clear compiled classes
 * - Optimize autoloader
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
    <title>Clear All Cache</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            color: #f5576c;
            border-bottom: 3px solid #f5576c;
            padding-bottom: 15px;
            margin-bottom: 30px;
            font-size: 2em;
        }
        h2 {
            color: #f093fb;
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
            border-left: 4px solid #f5576c;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Clear All Cache</h1>

        <?php
        try {
            // Load Laravel
            if (!file_exists('vendor/autoload.php')) {
                throw new Exception('❌ Không tìm thấy vendor/autoload.php. Vui lòng cài đặt Composer packages trước.');
            }

            require __DIR__.'/vendor/autoload.php';

            if (!file_exists('bootstrap/app.php')) {
                throw new Exception('❌ Không tìm thấy bootstrap/app.php');
            }

            $app = require_once __DIR__.'/bootstrap/app.php';
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();

            echo "<div class='output'>";
            echo "<div class='info'>Laravel Application Loaded Successfully</div>\n";
            echo "</div>";

            // Danh sách các cache cần clear
            $cacheOperations = [
                [
                    'name' => 'Config Cache',
                    'command' => 'config:clear',
                    'description' => 'Clear configuration cache',
                ],
                [
                    'name' => 'Route Cache',
                    'command' => 'route:clear',
                    'description' => 'Clear route cache',
                ],
                [
                    'name' => 'View Cache',
                    'command' => 'view:clear',
                    'description' => 'Clear compiled view files',
                ],
                [
                    'name' => 'Application Cache',
                    'command' => 'cache:clear',
                    'description' => 'Clear application cache',
                ],
                [
                    'name' => 'Compiled Classes',
                    'command' => 'clear-compiled',
                    'description' => 'Remove compiled class file',
                ],
            ];

            echo "<h2>Clearing Cache...</h2>";

            $results = [];
            foreach ($cacheOperations as $operation) {
                echo "<div class='step'>";
                echo "<strong>{$operation['name']}</strong> - {$operation['description']}<br>";

                try {
                    ob_start();
                    Artisan::call($operation['command']);
                    $output = Artisan::output();
                    ob_end_clean();

                    echo "<span class='badge badge-success'>✅ Success</span>";
                    echo "<div class='output' style='margin-top:10px; font-size:12px;'>";
                    echo htmlspecialchars($output);
                    echo "</div>";

                    $results[] = ['name' => $operation['name'], 'success' => true];

                } catch (Exception $e) {
                    echo "<span class='badge badge-error'>❌ Error</span>";
                    echo "<div class='error'>" . htmlspecialchars($e->getMessage()) . "</div>";

                    $results[] = ['name' => $operation['name'], 'success' => false, 'error' => $e->getMessage()];
                }

                echo "</div>";
            }

            // Clear cache files manually (fallback)
            echo "<h2>Manual Cache Cleanup</h2>";
            echo "<div class='output'>";

            $cacheFiles = [
                'bootstrap/cache/config.php',
                'bootstrap/cache/packages.php',
                'bootstrap/cache/routes-v7.php',
                'bootstrap/cache/services.php',
            ];

            foreach ($cacheFiles as $file) {
                if (file_exists($file)) {
                    if (@unlink($file)) {
                        echo "✅ Deleted: $file\n";
                    } else {
                        echo "⚠️  Could not delete: $file\n";
                    }
                } else {
                    echo "ℹ️  Not found: $file (OK)\n";
                }
            }

            echo "</div>";

            // Optimize for production
            echo "<h2>Optimization</h2>";
            echo "<div class='step'>";

            try {
                echo "⚙️ Optimizing autoloader...<br>";
                ob_start();
                Artisan::call('optimize');
                $output = Artisan::output();
                ob_end_clean();

                echo "<span class='badge badge-success'>✅ Optimized</span>";
                echo "<div class='output' style='margin-top:10px; font-size:12px;'>";
                echo htmlspecialchars($output);
                echo "</div>";

            } catch (Exception $e) {
                echo "<span class='badge badge-error'>❌ Error</span>";
                echo "<div class='error'>" . htmlspecialchars($e->getMessage()) . "</div>";
            }

            echo "</div>";

            // Summary
            echo "<h2>Summary</h2>";
            echo "<div class='output'>";

            $successCount = count(array_filter($results, fn($r) => $r['success']));
            $totalCount = count($results);

            echo "Total operations: $totalCount\n";
            echo "Successful: $successCount\n";
            echo "Failed: " . ($totalCount - $successCount) . "\n";

            if ($successCount === $totalCount) {
                echo "\n<div class='success'>✅ ALL CACHE CLEARED SUCCESSFULLY!</div>\n";
            } else {
                echo "\n<div class='error'>⚠️ Some operations failed. Check details above.</div>\n";
            }

            echo "</div>";

            // Next steps
            echo "<div class='warning'>";
            echo "<strong>📋 Bước tiếp theo:</strong><br>";
            echo "1. ✅ Test lại website của bạn<br>";
            echo "2. ✅ Test chức năng đăng nhập Google<br>";
            echo "3. ✅ Kiểm tra các chức năng quan trọng<br>";
            echo "4. ⚠️ <strong>XÓA FILE clear-all-cache.php này để bảo mật!</strong><br>";
            echo "</div>";

        } catch (Exception $e) {
            echo "<div class='output'>";
            echo "<div class='error'>❌ LỖI: " . htmlspecialchars($e->getMessage()) . "</div>\n";
            echo "<div class='info'>\nStack trace:\n" . htmlspecialchars($e->getTraceAsString()) . "</div>";
            echo "</div>";

            echo "<div class='warning'>";
            echo "<strong>⚠️ Lỗi xảy ra!</strong><br>";
            echo "Nếu lỗi liên quan đến vendor/autoload.php:<br>";
            echo "- Chạy file install-packages.php trước<br>";
            echo "- Hoặc upload thư mục vendor đầy đủ từ local<br>";
            echo "</div>";
        }
        ?>

        <div class="warning" style="margin-top: 30px;">
            <strong>🔒 BẢO MẬT:</strong><br>
            <strong style="color: #dc3545;">⚠️ XÓA FILE clear-all-cache.php SAU KHI DÙNG XONG!</strong><br>
            File này có thể được sử dụng để thực thi các lệnh Artisan và nên được xóa để bảo mật.
        </div>

        <p style="color: #666; font-size: 0.9em; text-align: center; margin-top: 30px;">
            Laravel Cache Cleaner | <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>
</body>
</html>

