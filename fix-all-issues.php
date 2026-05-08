<?php
/**
 * Fix All Issues - Complete Solution
 * 
 * File này sẽ fix TẤT CẢ vấn đề có thể gây ra lỗi 405
 * 
 * Truy cập: http://103.18.6.36/~lpi0g927o3nw/fix-all-issues.php
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

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Fix All Issues</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
            .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
            h2 { color: #555; margin-top: 30px; }
            .success { color: #4CAF50; padding: 10px; margin: 5px 0; background: #f0f9f0; border-left: 4px solid #4CAF50; }
            .error { color: #dc3545; padding: 10px; margin: 5px 0; background: #fff5f5; border-left: 4px solid #dc3545; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            .info { color: #666; padding: 10px; margin: 5px 0; background: #f9f9f9; border-left: 4px solid #999; }
            .key-box { background: #f4f4f4; padding: 15px; border-left: 4px solid #4CAF50; margin: 20px 0; word-break: break-all; font-family: monospace; font-size: 14px; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔧 Fix All Issues - Complete Solution</h1>
            
            <?php
            $allFixed = true;
            $appKeyGenerated = false;
            $appKey = '';

            // Step 1: Clear ALL cache files
            echo '<h2>Step 1: Clearing ALL Cache</h2>';
            
            $cacheFiles = [
                'bootstrap/cache/routes.php',
                'bootstrap/cache/config.php',
                'bootstrap/cache/services.php',
                'bootstrap/cache/packages.php',
            ];
            
            foreach ($cacheFiles as $cacheFile) {
                $fullPath = __DIR__.'/'.$cacheFile;
                if (file_exists($fullPath)) {
                    if (unlink($fullPath)) {
                        echo '<div class="success">✅ Deleted: ' . $cacheFile . '</div>';
                    } else {
                        echo '<div class="error">❌ Failed to delete: ' . $cacheFile . '</div>';
                        $allFixed = false;
                    }
                }
            }
            
            // Also delete any other cache files in bootstrap/cache
            $cacheDir = __DIR__.'/bootstrap/cache';
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir.'/*.php');
                foreach ($files as $file) {
                    if (is_file($file) && basename($file) !== '.gitignore') {
                        unlink($file);
                        echo '<div class="success">✅ Deleted: bootstrap/cache/' . basename($file) . '</div>';
                    }
                }
            }

            // Clear view cache
            $viewCachePath = __DIR__.'/storage/framework/views';
            if (is_dir($viewCachePath)) {
                $files = glob($viewCachePath.'/*');
                $count = 0;
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                        $count++;
                    }
                }
                if ($count > 0) {
                    echo '<div class="success">✅ View cache cleared (' . $count . ' files)</div>';
                }
            }

            // Clear application cache
            $appCachePath = __DIR__.'/storage/framework/cache/data';
            if (is_dir($appCachePath)) {
                $files = glob($appCachePath.'/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }

            // Step 2: Check and fix APP_KEY
            echo '<h2>Step 2: Checking and Fixing APP_KEY</h2>';
            
            $envFile = __DIR__.'/.env';
            $envContent = '';
            
            if (file_exists($envFile)) {
                $envContent = file_get_contents($envFile);
                
                // Check if APP_KEY exists and is valid
                if (preg_match('/APP_KEY=base64:[A-Za-z0-9+\/]+=*/', $envContent)) {
                    echo '<div class="success">✅ APP_KEY already exists and is valid</div>';
                } else {
                    // Generate new APP_KEY
                    $appKey = 'base64:'.base64_encode(random_bytes(32));
                    $appKeyGenerated = true;
                    
                    echo '<div class="warning">⚠️ APP_KEY not found or invalid. Generated new key:</div>';
                    echo '<div class="key-box"><code>APP_KEY=' . htmlspecialchars($appKey) . '</code></div>';
                    
                    // Try to update .env file
                    if (preg_match('/APP_KEY=.*/', $envContent)) {
                        // Replace existing
                        $newEnvContent = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $appKey, $envContent);
                    } else {
                        // Add after APP_ENV
                        if (preg_match('/(APP_ENV=.*)/', $envContent, $matches)) {
                            $newEnvContent = preg_replace('/(APP_ENV=.*)/', "$1\nAPP_KEY=" . $appKey, $envContent);
                        } else {
                            $newEnvContent = "APP_KEY=" . $appKey . "\n" . $envContent;
                        }
                    }
                    
                    if (is_writable($envFile)) {
                        if (file_put_contents($envFile, $newEnvContent)) {
                            echo '<div class="success">✅ APP_KEY đã được tự động thêm vào file .env!</div>';
                        } else {
                            echo '<div class="error">❌ Không thể tự động cập nhật .env. Vui lòng copy APP_KEY ở trên và thêm thủ công.</div>';
                            $allFixed = false;
                        }
                    } else {
                        echo '<div class="error">❌ File .env không thể ghi được. Vui lòng copy APP_KEY ở trên và thêm thủ công.</div>';
                        $allFixed = false;
                    }
                }
            } else {
                echo '<div class="error">❌ File .env không tồn tại! Bạn cần tạo file này trước.</div>';
                $allFixed = false;
            }

            // Step 3: Check routes file
            echo '<h2>Step 3: Verifying Routes</h2>';
            
            $routesFile = __DIR__.'/routes/web.php';
            if (file_exists($routesFile)) {
                $routesContent = file_get_contents($routesFile);
                if (strpos($routesContent, "Route::get('/',") !== false) {
                    echo '<div class="success">✅ Route "/" found in routes/web.php</div>';
                } else {
                    echo '<div class="error">❌ Route "/" NOT found in routes/web.php</div>';
                    $allFixed = false;
                }
            } else {
                echo '<div class="error">❌ routes/web.php not found</div>';
                $allFixed = false;
            }

            // Step 4: Check IndexController
            echo '<h2>Step 4: Verifying IndexController</h2>';
            
            $controllerFile = __DIR__.'/app/Http/Controllers/IndexController.php';
            if (file_exists($controllerFile)) {
                echo '<div class="success">✅ IndexController.php exists</div>';
                
                $controllerContent = file_get_contents($controllerFile);
                if (strpos($controllerContent, 'function index') !== false || strpos($controllerContent, 'public function index') !== false) {
                    echo '<div class="success">✅ index() method found</div>';
                } else {
                    echo '<div class="error">❌ index() method NOT found</div>';
                    $allFixed = false;
                }
            } else {
                echo '<div class="error">❌ IndexController.php not found</div>';
                $allFixed = false;
            }

            // Step 5: Check .htaccess files
            echo '<h2>Step 5: Checking .htaccess Files</h2>';
            
            $htaccessRoot = __DIR__.'/.htaccess';
            $htaccessPublic = __DIR__.'/public/.htaccess';
            
            if (file_exists($htaccessRoot)) {
                echo '<div class="success">✅ .htaccess exists in root</div>';
            } else {
                echo '<div class="warning">⚠️ .htaccess not found in root. Creating...</div>';
                $htaccessContent = "<IfModule mod_rewrite.c>\n    RewriteEngine On\n    RewriteRule ^(.*)$ public/\$1 [L]\n</IfModule>\n";
                if (file_put_contents($htaccessRoot, $htaccessContent)) {
                    echo '<div class="success">✅ Created .htaccess in root</div>';
                } else {
                    echo '<div class="error">❌ Failed to create .htaccess in root</div>';
                    $allFixed = false;
                }
            }
            
            if (file_exists($htaccessPublic)) {
                echo '<div class="success">✅ .htaccess exists in public/</div>';
            } else {
                echo '<div class="warning">⚠️ .htaccess not found in public/</div>';
            }

            // Step 6: Check permissions
            echo '<h2>Step 6: Checking Permissions</h2>';
            
            $storagePath = __DIR__.'/storage';
            $cachePath = __DIR__.'/bootstrap/cache';
            
            if (is_writable($storagePath)) {
                echo '<div class="success">✅ storage/ is writable</div>';
            } else {
                echo '<div class="warning">⚠️ storage/ may not be writable (should be 755)</div>';
            }
            
            if (is_writable($cachePath)) {
                echo '<div class="success">✅ bootstrap/cache/ is writable</div>';
            } else {
                echo '<div class="warning">⚠️ bootstrap/cache/ may not be writable (should be 755)</div>';
            }

            // Summary
            echo '<h2>📊 Summary</h2>';
            
            if ($allFixed && !$appKeyGenerated) {
                echo '<div class="success" style="font-weight: bold; font-size: 18px; padding: 20px;">';
                echo '🎉 All issues fixed! Please refresh your website now.';
                echo '</div>';
            } elseif ($appKeyGenerated) {
                echo '<div class="success" style="font-weight: bold; font-size: 18px; padding: 20px;">';
                echo '✅ APP_KEY has been generated and added to .env!';
                echo '</div>';
                echo '<div class="warning">';
                echo '<strong>⚠️ Important:</strong> If APP_KEY was added automatically, please refresh your website.';
                echo 'If you need to add it manually, copy the key above and add it to .env file.';
                echo '</div>';
            } else {
                echo '<div class="error" style="font-weight: bold;">';
                echo '❌ Some issues remain. Please fix them above.';
                echo '</div>';
            }
            ?>

            <div class="warning" style="margin-top: 30px;">
                <strong>⚠️ Next Steps:</strong>
                <ol>
                    <li>If APP_KEY was generated, ensure it's in your .env file</li>
                    <li><strong>If still getting 405 error, run debug-routes.php to see what's wrong:</strong> <code>http://103.18.6.36/~lpi0g927o3nw/debug-routes.php</code></li>
                    <li><strong>Refresh your website:</strong> <code>http://103.18.6.36/~lpi0g927o3nw/public/</code></li>
                    <li>Try accessing with trailing slash: <code>http://103.18.6.36/~lpi0g927o3nw/public/</code></li>
                    <li><strong>DELETE THESE FILES</strong> after use for security!</li>
                </ol>
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
            <p><strong>Line:</strong> <?php echo $e->getLine(); ?></p>
            <p><strong>File:</strong> <?php echo htmlspecialchars($e->getFile()); ?></p>
        </div>
    </body>
    </html>
    <?php
}
?>

