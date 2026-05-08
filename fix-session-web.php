<?php
/**
 * Fix Session Issues - Web Interface
 * 
 * Truy cập file này qua browser: https://your-domain.com/fix-session-web.php
 * Sau khi fix xong, XÓA FILE NÀY để bảo mật!
 * 
 * SECURITY WARNING: Xóa file này ngay sau khi sử dụng!
 */

// Security check - chỉ cho phép chạy trong môi trường cụ thể
$allowedIPs = []; // Thêm IP của bạn vào đây nếu muốn giới hạn
$allowedToken = 'CHANGE_THIS_SECRET_TOKEN_' . time(); // Thay đổi token này

// Check token
if (!isset($_GET['token']) || $_GET['token'] !== 'fix_session_2024') {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Session Fix Tool</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .error { background: #fee; border: 1px solid #fcc; padding: 15px; border-radius: 5px; }
            .success { background: #efe; border: 1px solid #cfc; padding: 15px; border-radius: 5px; }
            .warning { background: #ffe; border: 1px solid #fec; padding: 15px; border-radius: 5px; }
            .info { background: #eef; border: 1px solid #cef; padding: 15px; border-radius: 5px; }
            button { padding: 10px 20px; margin: 5px; cursor: pointer; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>⚠️ Security Token Required</h2>
            <p>Truy cập với token: <code>?token=fix_session_2024</code></p>
            <p><strong>URL đúng:</strong> <code>' . htmlspecialchars($_SERVER['REQUEST_URI']) . '?token=fix_session_2024</code></p>
        </div>
    </body>
    </html>
    ');
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Check if vendor exists
$vendorPath = __DIR__.'/vendor/autoload.php';
if (!file_exists($vendorPath)) {
    die('
    <div style="padding: 20px; background: #fee; border: 1px solid #f00;">
        <h2>Error: vendor/autoload.php not found</h2>
        <p>Path checked: ' . htmlspecialchars($vendorPath) . '</p>
        <p>Please ensure Composer dependencies are installed.</p>
    </div>
    ');
}

// Bootstrap Laravel with error handling
try {
    require $vendorPath;
    
    $bootstrapPath = __DIR__.'/bootstrap/app.php';
    if (!file_exists($bootstrapPath)) {
        throw new Exception('bootstrap/app.php not found at: ' . $bootstrapPath);
    }
    
    $app = require_once $bootstrapPath;
    
    if (!$app) {
        throw new Exception('Failed to bootstrap Laravel application');
    }
    
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    if (!$kernel) {
        throw new Exception('Failed to create Console Kernel');
    }
    
    $kernel->bootstrap();
    
} catch (Throwable $e) {
    die('
    <div style="padding: 20px; background: #fee; border: 1px solid #f00;">
        <h2>Error Bootstrapping Laravel</h2>
        <p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
        <p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>
        <p><strong>Line:</strong> ' . $e->getLine() . '</p>
        <pre style="background: #f5f5f5; padding: 10px; overflow-x: auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>
    </div>
    ');
}

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

$results = [];
$errors = [];
$warnings = [];

// Get action
$action = $_GET['action'] ?? 'check';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Session Issues - Web Tool</title>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 1000px; 
            margin: 20px auto; 
            padding: 20px; 
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .error { background: #fee; border-left: 4px solid #f00; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #efe; border-left: 4px solid #0a0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #ffe; border-left: 4px solid #fa0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #eef; border-left: 4px solid #00a; padding: 15px; margin: 10px 0; border-radius: 5px; }
        button { 
            padding: 12px 24px; 
            margin: 5px; 
            cursor: pointer; 
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        button:hover { background: #5568d3; }
        button.danger { background: #f56565; }
        button.danger:hover { background: #e53e3e; }
        pre { 
            background: #f5f5f5; 
            padding: 15px; 
            border-radius: 5px; 
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        .step { 
            background: #f9f9f9; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .step-number {
            display: inline-block;
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
        }
        .status-ok { color: #0a0; font-weight: bold; }
        .status-error { color: #f00; font-weight: bold; }
        .status-warning { color: #fa0; font-weight: bold; }
        a { color: #667eea; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .danger-zone {
            background: #fee;
            border: 2px solid #f00;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Session Issues - Web Tool</h1>
        
        <div class="warning">
            <strong>⚠️ CẢNH BÁO BẢO MẬT:</strong> Xóa file này ngay sau khi sử dụng xong!
        </div>

        <?php if ($action === 'check'): ?>
            <h2>📋 Kiểm tra hiện trạng</h2>
            
            <?php
            // Check 1: Session Driver
            $sessionDriver = config('session.driver');
            $results[] = [
                'name' => 'Session Driver',
                'value' => $sessionDriver,
                'status' => in_array($sessionDriver, ['database', 'redis']) ? 'ok' : 'warning',
                'message' => in_array($sessionDriver, ['database', 'redis']) 
                    ? '✓ Driver phù hợp cho Linux deploy' 
                    : '⚠️ Nên dùng "database" hoặc "redis" cho Linux deploy'
            ];
            
            // Check 2: Storage permissions
            $storagePath = storage_path('framework/sessions');
            $isWritable = is_writable($storagePath);
            $results[] = [
                'name' => 'Storage/Sessions Writable',
                'value' => $isWritable ? 'Yes' : 'No',
                'status' => $isWritable ? 'ok' : 'error',
                'message' => $isWritable 
                    ? '✓ Thư mục có quyền ghi' 
                    : '✗ Thư mục không có quyền ghi - Cần fix permissions'
            ];
            
            // Check 3: Sessions table (if database driver)
            if ($sessionDriver === 'database') {
                try {
                    $hasTable = DB::getSchemaBuilder()->hasTable('sessions');
                    $results[] = [
                        'name' => 'Sessions Table',
                        'value' => $hasTable ? 'Exists' : 'Missing',
                        'status' => $hasTable ? 'ok' : 'error',
                        'message' => $hasTable 
                            ? '✓ Bảng sessions đã tồn tại' 
                            : '✗ Bảng sessions chưa tồn tại - Cần tạo bảng'
                    ];
                } catch (Exception $e) {
                    $results[] = [
                        'name' => 'Sessions Table',
                        'value' => 'Error',
                        'status' => 'error',
                        'message' => '✗ Lỗi kiểm tra: ' . $e->getMessage()
                    ];
                }
            }
            
            // Check 4: Test session
            try {
                session()->put('test_session_key', 'test_value_' . time());
                session()->save();
                $testValue = session()->get('test_session_key');
                $sessionWorks = ($testValue !== null);
                $results[] = [
                    'name' => 'Session Test',
                    'value' => $sessionWorks ? 'Working' : 'Failed',
                    'status' => $sessionWorks ? 'ok' : 'error',
                    'message' => $sessionWorks 
                        ? '✓ Session hoạt động bình thường' 
                        : '✗ Session không hoạt động'
                ];
                session()->forget('test_session_key');
            } catch (Exception $e) {
                $results[] = [
                    'name' => 'Session Test',
                    'value' => 'Error',
                    'status' => 'error',
                    'message' => '✗ Lỗi test session: ' . $e->getMessage()
                ];
            }
            
            // Check 5: .env configuration
            $envPath = base_path('.env');
            if (File::exists($envPath)) {
                $envContent = File::get($envPath);
                $hasSessionDriver = strpos($envContent, 'SESSION_DRIVER') !== false;
                $results[] = [
                    'name' => '.env Configuration',
                    'value' => $hasSessionDriver ? 'Configured' : 'Missing',
                    'status' => $hasSessionDriver ? 'ok' : 'warning',
                    'message' => $hasSessionDriver 
                        ? '✓ .env có cấu hình SESSION_DRIVER' 
                        : '⚠️ .env thiếu cấu hình SESSION_DRIVER'
                ];
            }
            
            // Display results
            foreach ($results as $result) {
                $class = $result['status'] === 'ok' ? 'success' : ($result['status'] === 'error' ? 'error' : 'warning');
                echo "<div class='$class'>";
                echo "<strong>{$result['name']}:</strong> {$result['value']}<br>";
                echo $result['message'];
                echo "</div>";
            }
            ?>
            
            <h2>🔧 Actions</h2>
            <div class="step">
                <span class="step-number">1</span>
                <strong>Fix Permissions</strong> (nếu có quyền)
                <br><br>
                <a href="?token=fix_session_2024&action=fix_permissions">
                    <button>Fix Storage Permissions</button>
                </a>
            </div>
            
            <div class="step">
                <span class="step-number">2</span>
                <strong>Create Sessions Table</strong> (nếu dùng database driver)
                <br><br>
                <a href="?token=fix_session_2024&action=create_table">
                    <button>Create Sessions Table</button>
                </a>
            </div>
            
            <div class="step">
                <span class="step-number">3</span>
                <strong>Clear All Caches</strong>
                <br><br>
                <a href="?token=fix_session_2024&action=clear_cache">
                    <button>Clear All Caches</button>
                </a>
            </div>
            
            <div class="step">
                <span class="step-number">4</span>
                <strong>Test Session</strong>
                <br><br>
                <a href="?token=fix_session_2024&action=test_session">
                    <button>Test Session</button>
                </a>
            </div>
            
            <div class="step">
                <span class="step-number">5</span>
                <strong>Update .env Configuration</strong>
                <br><br>
                <a href="?token=fix_session_2024&action=update_env">
                    <button>Update .env</button>
                </a>
            </div>
            
        <?php elseif ($action === 'fix_permissions'): ?>
            <h2>🔧 Fix Permissions</h2>
            <?php
            $paths = [
                storage_path('framework/sessions'),
                storage_path('framework/cache'),
                storage_path('framework/views'),
                storage_path('logs'),
                bootstrap_path('cache'),
            ];
            
            $fixed = [];
            $failed = [];
            
            foreach ($paths as $path) {
                if (!is_dir($path)) {
                    try {
                        File::makeDirectory($path, 0755, true);
                        $fixed[] = "Created: $path";
                    } catch (Exception $e) {
                        $failed[] = "Cannot create: $path - " . $e->getMessage();
                    }
                }
                
                if (is_dir($path)) {
                    $isWritable = is_writable($path);
                    if (!$isWritable) {
                        $failed[] = "Cannot write to: $path (may need FTP/cPanel to fix)";
                    } else {
                        $fixed[] = "Writable: $path";
                    }
                }
            }
            
            if (count($fixed) > 0) {
                echo "<div class='success'><strong>✓ Fixed:</strong><ul>";
                foreach ($fixed as $msg) {
                    echo "<li>$msg</li>";
                }
                echo "</ul></div>";
            }
            
            if (count($failed) > 0) {
                echo "<div class='warning'><strong>⚠️ Cần fix thủ công qua FTP/cPanel:</strong><ul>";
                foreach ($failed as $msg) {
                    echo "<li>$msg</li>";
                }
                echo "</ul></div>";
                echo "<div class='info'><strong>Hướng dẫn fix thủ công:</strong><br>";
                echo "1. Vào cPanel File Manager hoặc FTP<br>";
                echo "2. Right-click vào thư mục <code>storage/framework/sessions</code><br>";
                echo "3. Chọn 'Change Permissions' hoặc 'CHMOD'<br>";
                echo "4. Đặt permissions = <code>775</code> hoặc <code>777</code><br>";
                echo "5. Làm tương tự cho <code>storage/</code> và <code>bootstrap/cache/</code></div>";
            }
            ?>
            <br>
            <a href="?token=fix_session_2024&action=check"><button>← Quay lại</button></a>
            
        <?php elseif ($action === 'create_table'): ?>
            <h2>📊 Create Sessions Table</h2>
            <?php
            try {
                // Check if table exists
                $hasTable = DB::getSchemaBuilder()->hasTable('sessions');
                
                if ($hasTable) {
                    echo "<div class='info'>Bảng sessions đã tồn tại.</div>";
                } else {
                    // Create migration
                    Artisan::call('session:table');
                    echo "<div class='success'>✓ Đã tạo migration file</div>";
                    
                    // Run migration
                    try {
                        Artisan::call('migrate', ['--force' => true]);
                        echo "<div class='success'>✓ Đã tạo bảng sessions thành công!</div>";
                    } catch (Exception $e) {
                        echo "<div class='error'>✗ Lỗi khi chạy migration: " . $e->getMessage() . "</div>";
                        echo "<div class='info'>Có thể cần chạy migration thủ công qua cPanel hoặc liên hệ hosting support.</div>";
                    }
                }
            } catch (Exception $e) {
                echo "<div class='error'>✗ Lỗi: " . $e->getMessage() . "</div>";
            }
            ?>
            <br>
            <a href="?token=fix_session_2024&action=check"><button>← Quay lại</button></a>
            
        <?php elseif ($action === 'clear_cache'): ?>
            <h2>🗑️ Clear All Caches</h2>
            <?php
            $commands = [
                'config:clear' => 'Config Cache',
                'cache:clear' => 'Application Cache',
                'view:clear' => 'View Cache',
                'route:clear' => 'Route Cache',
                'optimize:clear' => 'Optimize Cache',
            ];
            
            foreach ($commands as $cmd => $name) {
                try {
                    Artisan::call($cmd);
                    echo "<div class='success'>✓ Cleared: $name</div>";
                } catch (Exception $e) {
                    echo "<div class='warning'>⚠️ Error clearing $name: " . $e->getMessage() . "</div>";
                }
            }
            ?>
            <br>
            <a href="?token=fix_session_2024&action=check"><button>← Quay lại</button></a>
            
        <?php elseif ($action === 'test_session'): ?>
            <h2>🧪 Test Session</h2>
            <?php
            try {
                $testKey = 'test_' . time();
                $testValue = 'test_value_' . rand(1000, 9999);
                
                session()->put($testKey, $testValue);
                session()->save();
                
                $retrieved = session()->get($testKey);
                
                if ($retrieved === $testValue) {
                    echo "<div class='success'>✓ Session test PASSED!</div>";
                    echo "<div class='info'>Stored: <code>$testKey = $testValue</code><br>";
                    echo "Retrieved: <code>$retrieved</code></div>";
                } else {
                    echo "<div class='error'>✗ Session test FAILED!</div>";
                    echo "<div class='info'>Expected: <code>$testValue</code><br>";
                    echo "Got: <code>" . ($retrieved ?? 'null') . "</code></div>";
                }
                
                session()->forget($testKey);
            } catch (Exception $e) {
                echo "<div class='error'>✗ Session test ERROR: " . $e->getMessage() . "</div>";
            }
            ?>
            <br>
            <a href="?token=fix_session_2024&action=check"><button>← Quay lại</button></a>
            
        <?php elseif ($action === 'update_env'): ?>
            <h2>⚙️ Update .env Configuration</h2>
            <?php
            $envPath = base_path('.env');
            
            if (!File::exists($envPath)) {
                echo "<div class='error'>✗ File .env không tồn tại!</div>";
            } else {
                $envContent = File::get($envPath);
                $updates = [];
                
                // Check and add SESSION_DRIVER
                if (strpos($envContent, 'SESSION_DRIVER') === false) {
                    $envContent .= "\nSESSION_DRIVER=database\n";
                    $updates[] = 'SESSION_DRIVER=database';
                } else {
                    // Update if exists but not database
                    $envContent = preg_replace('/^SESSION_DRIVER=.*/m', 'SESSION_DRIVER=database', $envContent);
                    $updates[] = 'SESSION_DRIVER=database (updated)';
                }
                
                // Check and add SESSION_LIFETIME
                if (strpos($envContent, 'SESSION_LIFETIME') === false) {
                    $envContent .= "SESSION_LIFETIME=480\n";
                    $updates[] = 'SESSION_LIFETIME=480';
                }
                
                // Check and add SESSION_SECURE_COOKIE
                if (strpos($envContent, 'SESSION_SECURE_COOKIE') === false) {
                    $envContent .= "SESSION_SECURE_COOKIE=false\n";
                    $updates[] = 'SESSION_SECURE_COOKIE=false (set to true if using HTTPS)';
                }
                
                // Check and add SESSION_SAME_SITE
                if (strpos($envContent, 'SESSION_SAME_SITE') === false) {
                    $envContent .= "SESSION_SAME_SITE=lax\n";
                    $updates[] = 'SESSION_SAME_SITE=lax';
                }
                
                try {
                    File::put($envPath, $envContent);
                    echo "<div class='success'>✓ Đã cập nhật .env file!</div>";
                    echo "<div class='info'><strong>Các thay đổi:</strong><ul>";
                    foreach ($updates as $update) {
                        echo "<li>$update</li>";
                    }
                    echo "</ul></div>";
                    echo "<div class='warning'>⚠️ Cần clear config cache để áp dụng thay đổi!</div>";
                } catch (Exception $e) {
                    echo "<div class='error'>✗ Không thể ghi file .env: " . $e->getMessage() . "</div>";
                    echo "<div class='info'>Cần cập nhật thủ công qua FTP/cPanel File Manager.</div>";
                }
            }
            ?>
            <br>
            <a href="?token=fix_session_2024&action=clear_cache"><button>Clear Config Cache</button></a>
            <a href="?token=fix_session_2024&action=check"><button>← Quay lại</button></a>
        <?php endif; ?>
        
        <hr style="margin: 30px 0;">
        
        <div class="danger-zone">
            <h3>⚠️ QUAN TRỌNG: XÓA FILE NÀY SAU KHI SỬ DỤNG!</h3>
            <p>File này có thể được truy cập qua web, cần xóa ngay để bảo mật.</p>
            <p><strong>Cách xóa:</strong></p>
            <ol>
                <li>Vào cPanel File Manager hoặc FTP</li>
                <li>Tìm file <code>fix-session-web.php</code></li>
                <li>Xóa file này</li>
            </ol>
        </div>
        
        <div class="info">
            <h3>📝 Hướng dẫn sử dụng:</h3>
            <ol>
                <li>Bắt đầu với <strong>Kiểm tra hiện trạng</strong> để xem vấn đề</li>
                <li>Chạy từng action theo thứ tự: Fix Permissions → Create Table → Clear Cache → Test</li>
                <li>Nếu không có quyền, làm thủ công qua cPanel/FTP theo hướng dẫn</li>
                <li><strong>XÓA FILE NÀY</strong> sau khi hoàn thành!</li>
            </ol>
        </div>
    </div>
</body>
</html>

