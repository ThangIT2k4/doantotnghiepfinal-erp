<?php
/**
 * ============================================================================
 * TEST SERVE - Kiểm tra Web Server hoạt động
 * ============================================================================
 * 
 * File test đơn giản để kiểm tra:
 * - PHP có chạy được không
 * - Web server có hoạt động không
 * - Các extension PHP cần thiết
 * - Routing cơ bản
 * 
 * Truy cập: http://yourdomain.com/test-serve.php
 */

// Get current URL info
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
$currentUrl = $protocol . "://" . $host . $uri;
$method = $_SERVER['REQUEST_METHOD'];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Serve - Web Server Status</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .status-card {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .status-card.error {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        
        .status-card.warning {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
        
        .status-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .info-item .label {
            font-weight: 600;
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .info-item .value {
            color: #333;
            font-size: 1.1em;
            word-break: break-all;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin: 5px 5px 5px 0;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .test-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }
        
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Test Serve</h1>
            <p>Kiểm tra Web Server & PHP Configuration</p>
        </div>
        
        <div class="content">
            <!-- Server Status -->
            <div class="status-card">
                <h3>✅ Web Server đang hoạt động!</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Current URL</div>
                        <div class="value"><?php echo htmlspecialchars($currentUrl); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Request Method</div>
                        <div class="value"><span class="badge badge-success"><?php echo $method; ?></span></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Server Software</div>
                        <div class="value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">PHP Version</div>
                        <div class="value">
                            <span class="badge <?php echo version_compare(PHP_VERSION, '8.3.0', '>=') ? 'badge-success' : 'badge-warning'; ?>">
                                <?php echo PHP_VERSION; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PHP Extensions -->
            <div class="status-card">
                <h3>🔧 PHP Extensions</h3>
                <div style="margin-top: 15px;">
                    <?php
                    $requiredExtensions = [
                        'pdo' => 'PDO',
                        'pdo_mysql' => 'PDO MySQL',
                        'mbstring' => 'Mbstring',
                        'openssl' => 'OpenSSL',
                        'json' => 'JSON',
                        'curl' => 'cURL',
                        'zip' => 'ZIP',
                        'xml' => 'XML',
                        'gd' => 'GD',
                        'fileinfo' => 'FileInfo',
                    ];
                    
                    foreach ($requiredExtensions as $ext => $name) {
                        $loaded = extension_loaded($ext);
                        echo '<span class="badge ' . ($loaded ? 'badge-success' : 'badge-error') . '">';
                        echo ($loaded ? '✅' : '❌') . ' ' . $name;
                        echo '</span>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Request Information -->
            <div class="status-card">
                <h3>📋 Request Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Document Root</div>
                        <div class="value"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Script Filename</div>
                        <div class="value"><?php echo $_SERVER['SCRIPT_FILENAME'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Remote Address</div>
                        <div class="value"><?php echo $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="label">Server Protocol</div>
                        <div class="value"><?php echo $_SERVER['SERVER_PROTOCOL'] ?? 'N/A'; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Laravel Check -->
            <?php
            $laravelExists = file_exists(__DIR__ . '/../vendor/autoload.php') && file_exists(__DIR__ . '/../artisan');
            ?>
            <div class="status-card <?php echo $laravelExists ? '' : 'warning'; ?>">
                <h3><?php echo $laravelExists ? '✅' : '⚠️'; ?> Laravel Framework</h3>
                <?php if ($laravelExists): ?>
                    <p style="margin-top: 10px;">Laravel framework được phát hiện. Bạn có thể kiểm tra routing Laravel.</p>
                    <div class="button-group">
                        <a href="/" class="btn btn-success">🏠 Test Laravel Home Route</a>
                        <a href="/login" class="btn">🔐 Test Login Route</a>
                    </div>
                <?php else: ?>
                    <p style="margin-top: 10px; color: #856404;">Laravel framework không được phát hiện hoặc chưa cài đặt dependencies.</p>
                <?php endif; ?>
            </div>
            
            <!-- Test Routes -->
            <div class="test-section">
                <h3 style="margin-bottom: 20px;">🧪 Test Routes & Actions</h3>
                
                <div class="button-group">
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?test=phpinfo" class="btn">📊 PHP Info</a>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?test=extensions" class="btn">🔌 All Extensions</a>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?test=server" class="btn">🖥️ Server Variables</a>
                    <button onclick="testAjax()" class="btn">📡 Test AJAX</button>
                </div>
                
                <?php
                // Handle test parameter
                if (isset($_GET['test'])) {
                    echo '<div class="status-card" style="margin-top: 20px;">';
                    
                    switch ($_GET['test']) {
                        case 'phpinfo':
                            echo '<h3>PHP Information</h3>';
                            echo '<div style="margin-top: 15px;">';
                            echo '<iframe src="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?action=phpinfo" style="width: 100%; height: 500px; border: 1px solid #ddd; border-radius: 8px;"></iframe>';
                            echo '</div>';
                            break;
                            
                        case 'extensions':
                            echo '<h3>Loaded Extensions</h3>';
                            echo '<div style="margin-top: 15px; column-count: 3; column-gap: 20px;">';
                            $extensions = get_loaded_extensions();
                            sort($extensions);
                            foreach ($extensions as $ext) {
                                echo '<div style="margin-bottom: 8px;"><span class="badge badge-success">✅ ' . $ext . '</span></div>';
                            }
                            echo '</div>';
                            break;
                            
                        case 'server':
                            echo '<h3>Server Variables</h3>';
                            echo '<div style="margin-top: 15px; font-family: monospace; font-size: 0.9em; max-height: 400px; overflow-y: auto;">';
                            echo '<pre style="background: #f8f9fa; padding: 15px; border-radius: 8px;">';
                            print_r($_SERVER);
                            echo '</pre>';
                            echo '</div>';
                            break;
                    }
                    
                    echo '</div>';
                }
                
                // Handle phpinfo action
                if (isset($_GET['action']) && $_GET['action'] === 'phpinfo') {
                    phpinfo();
                    exit;
                }
                ?>
                
                <div id="ajax-result"></div>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Test Serve v1.0</strong> | Generated at <?php echo date('Y-m-d H:i:s'); ?></p>
            <p style="margin-top: 10px;">
                <a href="/" style="color: #667eea; text-decoration: none;">← Back to Home</a> | 
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="color: #667eea; text-decoration: none;">🔄 Refresh</a>
            </p>
        </div>
    </div>
    
    <script>
        function testAjax() {
            const resultDiv = document.getElementById('ajax-result');
            resultDiv.innerHTML = '<div class="status-card"><h3>⏳ Testing AJAX...</h3></div>';
            
            fetch('<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?ajax=test')
                .then(response => response.json())
                .then(data => {
                    resultDiv.innerHTML = `
                        <div class="status-card" style="margin-top: 20px;">
                            <h3>✅ AJAX Test Successful!</h3>
                            <div class="info-grid" style="margin-top: 15px;">
                                <div class="info-item">
                                    <div class="label">Status</div>
                                    <div class="value"><span class="badge badge-success">${data.status}</span></div>
                                </div>
                                <div class="info-item">
                                    <div class="label">Message</div>
                                    <div class="value">${data.message}</div>
                                </div>
                                <div class="info-item">
                                    <div class="label">Timestamp</div>
                                    <div class="value">${data.timestamp}</div>
                                </div>
                            </div>
                        </div>
                    `;
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                        <div class="status-card error" style="margin-top: 20px;">
                            <h3>❌ AJAX Test Failed</h3>
                            <p style="margin-top: 10px;">${error.message}</p>
                        </div>
                    `;
                });
        }
    </script>
</body>
</html>

<?php
// Handle AJAX test
if (isset($_GET['ajax']) && $_GET['ajax'] === 'test') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'AJAX working perfectly!',
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'php_version' => PHP_VERSION,
    ]);
    exit;
}
?>

