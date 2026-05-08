<?php
/**
 * ============================================================================
 * CHECK GOOGLE OAUTH CONFIGURATION
 * ============================================================================
 * 
 * Script kiểm tra cấu hình Google OAuth sau khi deploy
 * 
 * CÁCH DÙNG:
 * 1. Upload file này lên root của project trên server
 * 2. Truy cập: https://ZoroRMS.click/check-google-oauth.php
 * 3. Kiểm tra các thông tin cấu hình
 * 4. XÓA FILE NÀY SAU KHI DÙNG XONG để bảo mật!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm Tra Cấu Hình Google OAuth</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #e3342f;
            border-bottom: 3px solid #e3342f;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        h2 {
            color: #3490dc;
            margin-top: 30px;
            margin-bottom: 15px;
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
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #4285f4;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 0;
            font-weight: 500;
        }
        .btn:hover {
            background: #357ae8;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: 600;
        }
        .status-ok { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Kiểm Tra Cấu Hình Google OAuth</h1>

        <?php
        // Kiểm tra biến môi trường
        $clientId = env('GOOGLE_CLIENT_ID');
        $clientSecret = env('GOOGLE_CLIENT_SECRET');
        $redirectUri = env('GOOGLE_REDIRECT_URI');
        
        $hasClientId = !empty($clientId);
        $hasClientSecret = !empty($clientSecret);
        $hasRedirectUri = !empty($redirectUri);
        $allConfigured = $hasClientId && $hasClientSecret && $hasRedirectUri;
        ?>

        <h2>1. Biến Môi Trường (.env)</h2>
        <table>
            <thead>
                <tr>
                    <th>Biến</th>
                    <th>Giá trị</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>GOOGLE_CLIENT_ID</strong></td>
                    <td>
                        <?php if ($hasClientId): ?>
                            <code><?php echo htmlspecialchars(substr($clientId, 0, 50)) . '...'; ?></code>
                        <?php else: ?>
                            <span class="error">❌ CHƯA CẤU HÌNH</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($hasClientId): ?>
                            <span class="status-badge status-ok">✅ OK</span>
                        <?php else: ?>
                            <span class="status-badge status-error">❌ THIẾU</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>GOOGLE_CLIENT_SECRET</strong></td>
                    <td>
                        <?php if ($hasClientSecret): ?>
                            <code>***<?php echo htmlspecialchars(substr($clientSecret, -10)); ?></code>
                        <?php else: ?>
                            <span class="error">❌ CHƯA CẤU HÌNH</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($hasClientSecret): ?>
                            <span class="status-badge status-ok">✅ OK</span>
                        <?php else: ?>
                            <span class="status-badge status-error">❌ THIẾU</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>GOOGLE_REDIRECT_URI</strong></td>
                    <td>
                        <?php if ($hasRedirectUri): ?>
                            <code><?php echo htmlspecialchars($redirectUri); ?></code>
                        <?php else: ?>
                            <span class="error">❌ CHƯA CẤU HÌNH</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($hasRedirectUri): ?>
                            <span class="status-badge status-ok">✅ OK</span>
                        <?php else: ?>
                            <span class="status-badge status-error">❌ THIẾU</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php if (!$allConfigured): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Cảnh báo:</strong> Một hoặc nhiều biến môi trường chưa được cấu hình. 
                Vui lòng thêm vào file <code>.env</code> trên server.
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>✅ Tốt:</strong> Tất cả biến môi trường đã được cấu hình.
            </div>
        <?php endif; ?>

        <h2>2. Config Services</h2>
        <?php
        $config = config('services.google');
        ?>
        <pre><?php print_r($config); ?></pre>

        <?php if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect'])): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Cảnh báo:</strong> Config chưa đầy đủ. Có thể do:
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Biến môi trường chưa được thêm vào <code>.env</code></li>
                    <li>Config cache chưa được clear: <code>php artisan config:clear</code></li>
                </ul>
            </div>
        <?php endif; ?>

        <h2>3. Routes</h2>
        <?php
        try {
            $googleRoute = route('auth.google');
            $callbackRoute = route('auth.google.callback');
            ?>
            <ul style="list-style: none; padding: 0;">
                <li style="margin: 10px 0;">
                    <strong>auth.google:</strong> 
                    <a href="<?php echo $googleRoute; ?>" target="_blank" class="btn">
                        <?php echo $googleRoute; ?>
                    </a>
                </li>
                <li style="margin: 10px 0;">
                    <strong>auth.google.callback:</strong> 
                    <code><?php echo $callbackRoute; ?></code>
                </li>
            </ul>
            <?php
        } catch (Exception $e) {
            echo "<div class='alert alert-warning'>";
            echo "<strong>⚠️ Lỗi:</strong> Không thể load routes. " . htmlspecialchars($e->getMessage());
            echo "</div>";
        }
        ?>

        <h2>4. Kiểm Tra Redirect URI</h2>
        <?php
        $currentDomain = $_SERVER['HTTP_HOST'] ?? 'unknown';
        $currentProtocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $expectedRedirect = $currentProtocol . '://' . $currentDomain . '/auth/google/callback';
        ?>
        <table>
            <tr>
                <th>Loại</th>
                <th>Giá trị</th>
                <th>Trạng thái</th>
            </tr>
            <tr>
                <td>Redirect URI trong .env</td>
                <td><code><?php echo htmlspecialchars($redirectUri ?: 'Chưa cấu hình'); ?></code></td>
                <td>
                    <?php if ($redirectUri === $expectedRedirect): ?>
                        <span class="status-badge status-ok">✅ Khớp</span>
                    <?php elseif ($redirectUri): ?>
                        <span class="status-badge status-error">❌ Không khớp</span>
                    <?php else: ?>
                        <span class="status-badge status-error">❌ Chưa cấu hình</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Redirect URI mong đợi</td>
                <td><code><?php echo htmlspecialchars($expectedRedirect); ?></code></td>
                <td>-</td>
            </tr>
        </table>

        <?php if ($redirectUri && $redirectUri !== $expectedRedirect): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Cảnh báo:</strong> Redirect URI trong <code>.env</code> không khớp với domain hiện tại.
                <br><br>
                <strong>Nên sửa thành:</strong>
                <code><?php echo htmlspecialchars($expectedRedirect); ?></code>
            </div>
        <?php endif; ?>

        <h2>5. Test Đăng Nhập Google</h2>
        <?php if ($allConfigured): ?>
            <p>
                <a href="<?php echo route('auth.google'); ?>" class="btn" target="_blank">
                    🔵 Test Đăng Nhập Google
                </a>
            </p>
            <p style="color: #666; font-size: 0.9em; margin-top: 10px;">
                Click nút trên để test đăng nhập Google. Nếu redirect đến Google Login → Cấu hình đúng ✅
            </p>
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>⚠️ Không thể test:</strong> Vui lòng cấu hình đầy đủ biến môi trường trước.
            </div>
        <?php endif; ?>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">

        <div class="alert alert-warning">
            <strong>🔒 Bảo mật:</strong> 
            <strong>XÓA FILE NÀY SAU KHI KIỂM TRA XONG!</strong>
            <br>
            File này chứa thông tin nhạy cảm và không nên để trên server production.
        </div>

        <p style="color: #666; font-size: 0.9em; text-align: center; margin-top: 30px;">
            Google OAuth Configuration Checker | <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>
</body>
</html>

