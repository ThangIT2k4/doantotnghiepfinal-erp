<?php
/**
 * ============================================================================
 * INSTALL COMPOSER PACKAGES VIA WEB BROWSER
 * ============================================================================
 * 
 * Script này giúp cài đặt Composer packages trên hosting mà không cần terminal
 * 
 * CÁCH DÙNG:
 * 1. Upload file này lên root của project trên server
 * 2. Truy cập: https://your-domain.com/install-packages.php
 * 3. Đợi cho đến khi cài đặt xong (2-5 phút)
 * 4. ⚠️ XÓA FILE NÀY SAU KHI DÙNG XONG để bảo mật!
 * 
 * YÊU CẦU:
 * - PHP >= 8.2
 * - shell_exec() hoặc exec() được enable
 * - Memory limit >= 256M (recommended 512M)
 * - Execution time >= 300s (5 phút)
 */

set_time_limit(300); // 5 phút
ini_set('memory_limit', '512M');
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài Đặt Composer Packages</title>
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
        h2 {
            color: #764ba2;
            margin: 25px 0 15px;
            font-size: 1.4em;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
        }
        .output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }
        .success {
            color: #28a745;
            font-weight: bold;
            font-size: 1.1em;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
            font-size: 1.1em;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
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
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
            vertical-align: middle;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
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
        <h1>📦 Cài Đặt Composer Packages</h1>

        <?php
        // Kiểm tra môi trường
        echo "<h2>1. Kiểm Tra Môi Trường</h2>";
        
        $checks = [
            'PHP Version' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'composer.json exists' => file_exists('composer.json'),
            'vendor folder writable' => is_writable(__DIR__) || !file_exists('vendor'),
            'shell_exec enabled' => function_exists('shell_exec'),
            'exec enabled' => function_exists('exec'),
        ];
        
        echo "<div class='step'>";
        foreach ($checks as $check => $result) {
            $badge = $result ? 'badge-success' : 'badge-error';
            $icon = $result ? '✅' : '❌';
            echo "<div><span class='badge $badge'>$icon</span> $check</div>";
        }
        echo "</div>";
        
        // Nếu có lỗi critical, dừng lại
        if (!$checks['composer.json exists']) {
            echo "<div class='error'>❌ Không tìm thấy composer.json. Đảm bảo file này tồn tại trong thư mục root.</div>";
            exit;
        }
        
        if (!$checks['shell_exec enabled'] && !$checks['exec enabled']) {
            echo "<div class='error'>";
            echo "❌ Hosting không hỗ trợ shell_exec() hoặc exec().<br>";
            echo "Bạn cần:<br>";
            echo "1. Liên hệ hosting provider để enable các function này<br>";
            echo "2. Hoặc upload thư mục vendor đầy đủ từ máy local<br>";
            echo "</div>";
            exit;
        }
        
        // Tiếp tục cài đặt
        echo "<h2>2. Chuẩn Bị Composer</h2>";
        echo "<div class='output'>";
        
        // Kiểm tra composer.phar
        if (!file_exists('composer.phar')) {
            echo "📥 Đang tải composer.phar từ getcomposer.org...\n";
            
            try {
                $composerSetup = file_get_contents('https://getcomposer.org/installer');
                
                if ($composerSetup === false) {
                    throw new Exception("Không thể tải composer installer");
                }
                
                file_put_contents('composer-setup.php', $composerSetup);
                echo "✅ Đã tải composer-setup.php\n\n";
                
                // Chạy composer installer
                echo "🔧 Đang cài đặt composer...\n";
                $output = shell_exec('php composer-setup.php 2>&1');
                echo $output . "\n";
                
                // Xóa file setup
                @unlink('composer-setup.php');
                
                if (!file_exists('composer.phar')) {
                    throw new Exception("Không thể tạo composer.phar");
                }
                
                echo "✅ Đã cài đặt composer.phar thành công\n\n";
                
            } catch (Exception $e) {
                echo "❌ LỖI: " . $e->getMessage() . "\n";
                echo "</div>";
                exit;
            }
        } else {
            echo "✅ composer.phar đã tồn tại\n\n";
        }
        
        echo "</div>";
        
        // Cài đặt packages
        echo "<h2>3. Cài Đặt Packages</h2>";
        echo "<div class='info'>";
        echo "<strong>⏳ Đang cài đặt packages...</strong><br>";
        echo "Quá trình này có thể mất 2-5 phút. Vui lòng không tắt trình duyệt.";
        echo "</div>";
        
        echo "<div class='output'>";
        
        // Chạy composer install
        $composerCommand = 'php composer.phar install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction 2>&1';
        
        echo "📦 Đang chạy: $composerCommand\n";
        echo str_repeat('=', 80) . "\n\n";
        
        // Execute và hiển thị output real-time
        $startTime = microtime(true);
        $output = shell_exec($composerCommand);
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        echo $output . "\n";
        echo str_repeat('=', 80) . "\n";
        echo "⏱️ Thời gian: {$duration}s\n\n";
        
        echo "</div>";
        
        // Kiểm tra kết quả
        echo "<h2>4. Kiểm Tra Kết Quả</h2>";
        echo "<div class='step'>";
        
        $results = [
            'vendor folder' => is_dir('vendor'),
            'vendor/autoload.php' => file_exists('vendor/autoload.php'),
            'laravel/framework' => is_dir('vendor/laravel/framework'),
            'laravel/socialite' => is_dir('vendor/laravel/socialite'),
            'maatwebsite/excel' => is_dir('vendor/maatwebsite/excel'),
        ];
        
        $allSuccess = true;
        foreach ($results as $item => $exists) {
            $badge = $exists ? 'badge-success' : 'badge-error';
            $icon = $exists ? '✅' : '❌';
            echo "<div><span class='badge $badge'>$icon</span> $item</div>";
            if (!$exists) $allSuccess = false;
        }
        
        echo "</div>";
        
        // Kết luận
        echo "<div style='margin-top: 30px; padding: 20px; border-radius: 8px; text-align: center;'>";
        
        if ($allSuccess) {
            echo "<div class='success' style='font-size: 1.5em;'>✅ CÀI ĐẶT THÀNH CÔNG!</div>";
            echo "<p style='margin-top: 15px; color: #666;'>Tất cả packages đã được cài đặt đầy đủ.</p>";
            
            echo "<div class='info' style='margin-top: 20px; text-align: left;'>";
            echo "<strong>📋 Bước tiếp theo:</strong><br>";
            echo "1. ✅ Cấu hình file .env với Google OAuth credentials<br>";
            echo "2. ✅ Chạy file clear-all-cache.php để clear cache<br>";
            echo "3. ✅ Test chức năng đăng nhập Google<br>";
            echo "4. ⚠️ <strong>XÓA FILE install-packages.php này để bảo mật!</strong><br>";
            echo "5. ⚠️ Có thể xóa composer.phar nếu không cần dùng nữa<br>";
            echo "</div>";
        } else {
            echo "<div class='error' style='font-size: 1.5em;'>❌ CÀI ĐẶT CHƯA HOÀN TẤT</div>";
            echo "<p style='margin-top: 15px; color: #666;'>Một số packages chưa được cài đặt đầy đủ.</p>";
            
            echo "<div class='warning' style='margin-top: 20px; text-align: left;'>";
            echo "<strong>⚠️ Khuyến nghị:</strong><br>";
            echo "1. Kiểm tra output bên trên để xem lỗi chi tiết<br>";
            echo "2. Thử chạy lại file này<br>";
            echo "3. Hoặc upload thư mục vendor đầy đủ từ máy local:<br>";
            echo "   - Trên local: composer install --no-dev --optimize-autoloader<br>";
            echo "   - Nén thư mục vendor thành vendor.zip<br>";
            echo "   - Upload lên server và extract<br>";
            echo "</div>";
        }
        
        echo "</div>";
        
        echo "<div class='warning' style='margin-top: 30px;'>";
        echo "<strong>🔒 BẢO MẬT:</strong><br>";
        echo "<strong style='color: #dc3545;'>⚠️ XÓA CÁC FILE SAU SAU KHI DÙNG XONG:</strong><br>";
        echo "- install-packages.php (file này)<br>";
        echo "- composer.phar (nếu không cần dùng nữa)<br>";
        echo "- composer-setup.php (nếu có)<br>";
        echo "</div>";
        ?>

        <p style="color: #666; font-size: 0.9em; text-align: center; margin-top: 30px;">
            Composer Package Installer | <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>
</body>
</html>

