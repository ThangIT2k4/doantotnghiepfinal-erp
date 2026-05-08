<?php
/**
 * QUICK FIX 500 ERROR - Phiên bản tối giản
 * Chạy file này nếu các file khác bị lỗi 500
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quick Fix 500</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; }
    </style>
</head>
<body>
    <h1>🔧 Quick Fix 500 Error</h1>
    
    <?php
    $results = [];
    
    // 1. Xóa cache
    echo '<div class="box"><h2>1. Xóa Cache Files</h2>';
    $files = [
        'bootstrap/cache/routes-v7.php',
        'bootstrap/cache/config.php',
        'bootstrap/cache/packages.php',
        'bootstrap/cache/services.php',
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            if (@unlink($file)) {
                echo '<div class="success">✅ Đã xóa: ' . $file . '</div>';
                $results[] = 'deleted';
            } else {
                echo '<div class="error">❌ Không thể xóa: ' . $file . '</div>';
            }
        }
    }
    echo '</div>';
    
    // 2. Kiểm tra APP_KEY
    echo '<div class="box"><h2>2. Kiểm tra APP_KEY</h2>';
    if (file_exists('.env')) {
        $env = file_get_contents('.env');
        if (preg_match('/APP_KEY=base64:[A-Za-z0-9+\/=]+/', $env)) {
            echo '<div class="success">✅ APP_KEY đã có</div>';
        } else {
            $key = 'base64:' . base64_encode(random_bytes(32));
            if (preg_match('/APP_KEY=.*/', $env)) {
                $env = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $key, $env);
            } else {
                $env .= "\nAPP_KEY=" . $key . "\n";
            }
            if (@file_put_contents('.env', $env)) {
                echo '<div class="success">✅ Đã tạo APP_KEY</div>';
                $results[] = 'key_created';
            } else {
                echo '<div class="error">❌ Không thể cập nhật .env. Key cần set: ' . $key . '</div>';
            }
        }
    } else {
        echo '<div class="error">❌ File .env không tồn tại</div>';
    }
    echo '</div>';
    
    // 3. Kiểm tra permissions
    echo '<div class="box"><h2>3. Kiểm tra Permissions</h2>';
    $dirs = ['storage', 'bootstrap/cache'];
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            if (is_writable($dir)) {
                echo '<div class="success">✅ ' . $dir . ' có quyền ghi</div>';
            } else {
                echo '<div class="error">❌ ' . $dir . ' không có quyền ghi - Set 755 trong cPanel</div>';
            }
        }
    }
    echo '</div>';
    
    // Summary
    echo '<div class="box" style="background: #d4edda;">';
    echo '<h2>✅ Hoàn tất!</h2>';
    echo '<p>Đã xử lý ' . count($results) . ' vấn đề. Refresh website và kiểm tra lại.</p>';
    echo '<p style="color: red; font-weight: bold;">⚠️ XÓA FILE NÀY SAU KHI XONG!</p>';
    echo '</div>';
    ?>
</body>
</html>

