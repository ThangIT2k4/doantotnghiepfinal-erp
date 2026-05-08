<?php
/**
 * ============================================================================
 * CHECK VENDOR PACKAGES
 * ============================================================================
 * 
 * Script kiểm tra các packages quan trọng đã được cài đặt chưa
 * 
 * CÁCH DÙNG:
 * 1. Upload file này lên root của project trên server
 * 2. Truy cập: https://your-domain.com/check-vendor-packages.php
 * 3. ⚠️ XÓA FILE NÀY SAU KHI KIỂM TRA XONG!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Vendor Packages</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
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
            color: #fa709a;
            border-bottom: 3px solid #fa709a;
            padding-bottom: 15px;
            margin-bottom: 30px;
            font-size: 2em;
        }
        h2 {
            color: #fee140;
            color: #d68a00;
            margin: 25px 0 15px;
            font-size: 1.3em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-error { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .summary-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #dee2e6;
        }
        .summary-number {
            font-size: 2em;
            font-weight: bold;
            margin: 10px 0;
        }
        .summary-label {
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Check Vendor Packages</h1>

        <?php
        // Kiểm tra vendor folder
        if (!is_dir('vendor')) {
            echo "<div class='error-box'>";
            echo "<strong>❌ Thư mục vendor không tồn tại!</strong><br>";
            echo "Bạn cần cài đặt Composer packages trước.<br>";
            echo "Chạy file <code>install-packages.php</code> hoặc upload thư mục vendor từ local.";
            echo "</div>";
            exit;
        }

        // Kiểm tra autoload
        if (!file_exists('vendor/autoload.php')) {
            echo "<div class='error-box'>";
            echo "<strong>❌ File vendor/autoload.php không tồn tại!</strong><br>";
            echo "Vendor folder bị lỗi. Vui lòng cài đặt lại Composer packages.";
            echo "</div>";
            exit;
        }

        // Danh sách packages cần kiểm tra
        $requiredPackages = [
            'laravel/framework' => [
                'name' => 'Laravel Framework',
                'path' => 'vendor/laravel/framework',
                'critical' => true,
                'description' => 'Core Laravel framework'
            ],
            'laravel/socialite' => [
                'name' => 'Laravel Socialite',
                'path' => 'vendor/laravel/socialite',
                'critical' => true,
                'description' => 'OAuth authentication (Google Login)'
            ],
            'laravel/tinker' => [
                'name' => 'Laravel Tinker',
                'path' => 'vendor/laravel/tinker',
                'critical' => false,
                'description' => 'REPL for Laravel'
            ],
            'maatwebsite/excel' => [
                'name' => 'Laravel Excel',
                'path' => 'vendor/maatwebsite/excel',
                'critical' => false,
                'description' => 'Excel import/export'
            ],
            'guzzlehttp/guzzle' => [
                'name' => 'Guzzle HTTP Client',
                'path' => 'vendor/guzzlehttp/guzzle',
                'critical' => true,
                'description' => 'HTTP client (required by Socialite)'
            ],
            'symfony/http-foundation' => [
                'name' => 'Symfony HTTP Foundation',
                'path' => 'vendor/symfony/http-foundation',
                'critical' => true,
                'description' => 'HTTP abstraction layer'
            ],
            'monolog/monolog' => [
                'name' => 'Monolog',
                'path' => 'vendor/monolog/monolog',
                'critical' => false,
                'description' => 'Logging library'
            ],
        ];

        // Kiểm tra từng package
        $results = [];
        $totalPackages = count($requiredPackages);
        $installedCount = 0;
        $criticalMissing = [];

        foreach ($requiredPackages as $key => $package) {
            $exists = is_dir($package['path']);
            $results[$key] = [
                'name' => $package['name'],
                'path' => $package['path'],
                'exists' => $exists,
                'critical' => $package['critical'],
                'description' => $package['description']
            ];

            if ($exists) {
                $installedCount++;
            } elseif ($package['critical']) {
                $criticalMissing[] = $package['name'];
            }
        }

        // Summary
        echo "<h2>📊 Tổng Quan</h2>";
        echo "<div class='summary'>";
        
        echo "<div class='summary-card'>";
        echo "<div class='summary-label'>Tổng Packages</div>";
        echo "<div class='summary-number'>$totalPackages</div>";
        echo "</div>";
        
        echo "<div class='summary-card' style='border-color: #28a745;'>";
        echo "<div class='summary-label'>Đã Cài Đặt</div>";
        echo "<div class='summary-number' style='color: #28a745;'>$installedCount</div>";
        echo "</div>";
        
        $missingCount = $totalPackages - $installedCount;
        $missingColor = $missingCount > 0 ? '#dc3545' : '#28a745';
        echo "<div class='summary-card' style='border-color: $missingColor;'>";
        echo "<div class='summary-label'>Thiếu</div>";
        echo "<div class='summary-number' style='color: $missingColor;'>$missingCount</div>";
        echo "</div>";
        
        $criticalMissingCount = count($criticalMissing);
        $criticalColor = $criticalMissingCount > 0 ? '#dc3545' : '#28a745';
        echo "<div class='summary-card' style='border-color: $criticalColor;'>";
        echo "<div class='summary-label'>Critical Missing</div>";
        echo "<div class='summary-number' style='color: $criticalColor;'>$criticalMissingCount</div>";
        echo "</div>";
        
        echo "</div>";

        // Warning nếu thiếu critical packages
        if ($criticalMissingCount > 0) {
            echo "<div class='error-box'>";
            echo "<strong>⚠️ CẢNH BÁO: Thiếu packages quan trọng!</strong><br><br>";
            echo "Các packages sau là bắt buộc nhưng chưa được cài đặt:<br>";
            echo "<ul style='margin-left: 20px; margin-top: 10px;'>";
            foreach ($criticalMissing as $packageName) {
                echo "<li><strong>$packageName</strong></li>";
            }
            echo "</ul>";
            echo "<br><strong>Hành động:</strong> Chạy file <code>install-packages.php</code> để cài đặt.";
            echo "</div>";
        } elseif ($installedCount === $totalPackages) {
            echo "<div class='info'>";
            echo "<strong>✅ Tuyệt vời!</strong> Tất cả packages đã được cài đặt đầy đủ.";
            echo "</div>";
        }

        // Chi tiết packages
        echo "<h2>📋 Chi Tiết Packages</h2>";
        echo "<table>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>Package</th>";
        echo "<th>Description</th>";
        echo "<th>Type</th>";
        echo "<th>Status</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        foreach ($results as $key => $result) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($result['name']) . "</strong><br>";
            echo "<small style='color: #666;'>" . htmlspecialchars($key) . "</small></td>";
            echo "<td>" . htmlspecialchars($result['description']) . "</td>";
            
            if ($result['critical']) {
                echo "<td><span class='badge badge-error'>Critical</span></td>";
            } else {
                echo "<td><span class='badge badge-warning'>Optional</span></td>";
            }
            
            if ($result['exists']) {
                echo "<td><span class='badge badge-success'>✅ Installed</span></td>";
            } else {
                echo "<td><span class='badge badge-error'>❌ Missing</span></td>";
            }
            
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";

        // Kiểm tra composer.json
        echo "<h2>📄 Composer.json</h2>";
        if (file_exists('composer.json')) {
            echo "<div class='info'>";
            echo "<strong>✅ composer.json found</strong><br>";
            
            $composerContent = file_get_contents('composer.json');
            $composerData = json_decode($composerContent, true);
            
            if ($composerData && isset($composerData['require'])) {
                echo "<br><strong>Required packages in composer.json:</strong><br>";
                echo "<ul style='margin-left: 20px; margin-top: 10px;'>";
                foreach ($composerData['require'] as $package => $version) {
                    echo "<li><code>$package</code>: $version</li>";
                }
                echo "</ul>";
            }
            echo "</div>";
        } else {
            echo "<div class='error-box'>";
            echo "❌ composer.json not found";
            echo "</div>";
        }

        // Kiểm tra installed.json
        echo "<h2>📦 Installed Packages Info</h2>";
        $installedJsonPath = 'vendor/composer/installed.json';
        if (file_exists($installedJsonPath)) {
            $installedContent = file_get_contents($installedJsonPath);
            $installedData = json_decode($installedContent, true);
            
            if ($installedData) {
                $packages = $installedData['packages'] ?? $installedData;
                $totalInstalled = count($packages);
                
                echo "<div class='info'>";
                echo "<strong>✅ Total installed packages: $totalInstalled</strong><br>";
                echo "<small>Includes all dependencies</small>";
                echo "</div>";
            }
        }

        // Hướng dẫn
        if ($installedCount < $totalPackages) {
            echo "<div class='warning'>";
            echo "<strong>📋 Hướng Dẫn Cài Đặt Packages:</strong><br><br>";
            
            echo "<strong>Cách 1: Qua Web (Nếu hosting hỗ trợ)</strong><br>";
            echo "1. Upload file <code>install-packages.php</code> vào thư mục root<br>";
            echo "2. Truy cập: <code>https://your-domain.com/install-packages.php</code><br>";
            echo "3. Đợi cài đặt hoàn tất (2-5 phút)<br>";
            echo "4. Xóa file sau khi xong<br><br>";
            
            echo "<strong>Cách 2: Upload từ Local</strong><br>";
            echo "1. Trên máy local, chạy: <code>composer install --no-dev --optimize-autoloader</code><br>";
            echo "2. Nén thư mục vendor: <code>zip -r vendor.zip vendor/</code><br>";
            echo "3. Upload vendor.zip lên server<br>";
            echo "4. Extract trong thư mục root<br>";
            echo "5. Xóa vendor.zip<br>";
            echo "</div>";
        }

        // Kết luận
        echo "<div style='margin-top: 30px; padding: 20px; border-radius: 8px; text-align: center;'>";
        
        if ($criticalMissingCount === 0) {
            echo "<div style='color: #28a745; font-size: 1.5em; font-weight: bold;'>✅ ALL CRITICAL PACKAGES INSTALLED!</div>";
            
            if ($installedCount === $totalPackages) {
                echo "<p style='margin-top: 10px; color: #666;'>Tất cả packages đã sẵn sàng. Bạn có thể tiếp tục deploy.</p>";
            } else {
                echo "<p style='margin-top: 10px; color: #666;'>Các packages bắt buộc đã có. Packages optional thiếu không ảnh hưởng chức năng chính.</p>";
            }
        } else {
            echo "<div style='color: #dc3545; font-size: 1.5em; font-weight: bold;'>❌ MISSING CRITICAL PACKAGES!</div>";
            echo "<p style='margin-top: 10px; color: #666;'>Vui lòng cài đặt các packages còn thiếu trước khi tiếp tục.</p>";
        }
        
        echo "</div>";
        ?>

        <div class="warning" style="margin-top: 30px;">
            <strong>🔒 BẢO MẬT:</strong><br>
            <strong style="color: #dc3545;">⚠️ XÓA FILE check-vendor-packages.php SAU KHI KIỂM TRA XONG!</strong>
        </div>

        <p style="color: #666; font-size: 0.9em; text-align: center; margin-top: 30px;">
            Vendor Packages Checker | <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>
</body>
</html>

