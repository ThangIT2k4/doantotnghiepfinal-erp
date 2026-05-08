<?php
/**
 * Complete Route Fix Helper
 * 
 * File này sẽ:
 * 1. Clear tất cả cache
 * 2. Kiểm tra routes
 * 3. Rebuild route cache
 * 4. Kiểm tra IndexController
 * 
 * Truy cập: http://103.18.6.36/~lpi0g927o3nw/fix-route-complete.php
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
        <title>Complete Route Fix</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
            .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
            .success { color: #4CAF50; padding: 10px; margin: 5px 0; background: #f0f9f0; border-left: 4px solid #4CAF50; }
            .error { color: #dc3545; padding: 10px; margin: 5px 0; background: #fff5f5; border-left: 4px solid #dc3545; }
            .info { color: #666; padding: 10px; margin: 5px 0; background: #f9f9f9; border-left: 4px solid #999; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔧 Complete Route Fix</h1>
            
            <?php
            $results = [];
            $errors = [];
            $warnings = [];

            // Step 1: Clear route cache
            echo '<h2>Step 1: Clearing Route Cache</h2>';
            $routeCache = __DIR__.'/bootstrap/cache/routes.php';
            if (file_exists($routeCache)) {
                if (unlink($routeCache)) {
                    $results[] = '✅ Route cache deleted';
                    echo '<div class="success">✅ Route cache deleted</div>';
                } else {
                    $errors[] = '❌ Failed to delete route cache';
                    echo '<div class="error">❌ Failed to delete route cache</div>';
                }
            } else {
                $results[] = 'ℹ️ Route cache not found (already cleared)';
                echo '<div class="info">ℹ️ Route cache not found (already cleared)</div>';
            }

            // Step 2: Clear config cache
            echo '<h2>Step 2: Clearing Config Cache</h2>';
            $configCache = __DIR__.'/bootstrap/cache/config.php';
            if (file_exists($configCache)) {
                if (unlink($configCache)) {
                    $results[] = '✅ Config cache deleted';
                    echo '<div class="success">✅ Config cache deleted</div>';
                } else {
                    $errors[] = '❌ Failed to delete config cache';
                    echo '<div class="error">❌ Failed to delete config cache</div>';
                }
            } else {
                echo '<div class="info">ℹ️ Config cache not found</div>';
            }

            // Step 3: Clear view cache
            echo '<h2>Step 3: Clearing View Cache</h2>';
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
                    $results[] = "✅ View cache cleared ({$count} files)";
                    echo '<div class="success">✅ View cache cleared (' . $count . ' files)</div>';
                } else {
                    echo '<div class="info">ℹ️ View cache already empty</div>';
                }
            }

            // Step 4: Clear application cache
            echo '<h2>Step 4: Clearing Application Cache</h2>';
            $appCachePath = __DIR__.'/storage/framework/cache/data';
            if (is_dir($appCachePath)) {
                $files = glob($appCachePath.'/*');
                $count = 0;
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                        $count++;
                    }
                }
                if ($count > 0) {
                    $results[] = "✅ Application cache cleared ({$count} files)";
                    echo '<div class="success">✅ Application cache cleared (' . $count . ' files)</div>';
                } else {
                    echo '<div class="info">ℹ️ Application cache already empty</div>';
                }
            }

            // Step 5: Check routes file
            echo '<h2>Step 5: Checking Routes File</h2>';
            $routesFile = __DIR__.'/routes/web.php';
            if (file_exists($routesFile)) {
                $routesContent = file_get_contents($routesFile);
                if (strpos($routesContent, "Route::get('/',") !== false) {
                    $results[] = '✅ Route "/" found in routes/web.php';
                    echo '<div class="success">✅ Route "/" found in routes/web.php</div>';
                } else {
                    $errors[] = '❌ Route "/" NOT found in routes/web.php';
                    echo '<div class="error">❌ Route "/" NOT found in routes/web.php</div>';
                }
            } else {
                $errors[] = '❌ routes/web.php not found';
                echo '<div class="error">❌ routes/web.php not found</div>';
            }

            // Step 6: Check IndexController
            echo '<h2>Step 6: Checking IndexController</h2>';
            $controllerFile = __DIR__.'/app/Http/Controllers/IndexController.php';
            if (file_exists($controllerFile)) {
                $results[] = '✅ IndexController.php exists';
                echo '<div class="success">✅ IndexController.php exists</div>';
                
                // Check if index method exists
                $controllerContent = file_get_contents($controllerFile);
                if (strpos($controllerContent, 'function index') !== false || strpos($controllerContent, 'public function index') !== false) {
                    $results[] = '✅ index() method found';
                    echo '<div class="success">✅ index() method found</div>';
                } else {
                    $errors[] = '❌ index() method NOT found in IndexController';
                    echo '<div class="error">❌ index() method NOT found in IndexController</div>';
                }
            } else {
                $errors[] = '❌ IndexController.php not found';
                echo '<div class="error">❌ IndexController.php not found</div>';
            }

            // Step 7: Check .env file
            echo '<h2>Step 7: Checking .env File</h2>';
            $envFile = __DIR__.'/.env';
            if (file_exists($envFile)) {
                $results[] = '✅ .env file exists';
                echo '<div class="success">✅ .env file exists</div>';
                
                $envContent = file_get_contents($envFile);
                if (strpos($envContent, 'APP_KEY=') !== false && strpos($envContent, 'APP_KEY=') !== strpos($envContent, 'APP_KEY=')) {
                    if (preg_match('/APP_KEY=base64:.+/', $envContent)) {
                        $results[] = '✅ APP_KEY is set';
                        echo '<div class="success">✅ APP_KEY is set</div>';
                    } else {
                        $warnings[] = '⚠️ APP_KEY may not be properly set';
                        echo '<div class="warning">⚠️ APP_KEY may not be properly set</div>';
                    }
                } else {
                    $warnings[] = '⚠️ APP_KEY not found in .env';
                    echo '<div class="warning">⚠️ APP_KEY not found in .env</div>';
                }
            } else {
                $errors[] = '❌ .env file not found';
                echo '<div class="error">❌ .env file not found - You MUST create this file!</div>';
            }

            // Step 8: Check permissions
            echo '<h2>Step 8: Checking Permissions</h2>';
            $storagePath = __DIR__.'/storage';
            $cachePath = __DIR__.'/bootstrap/cache';
            
            if (is_writable($storagePath)) {
                $results[] = '✅ storage/ is writable';
                echo '<div class="success">✅ storage/ is writable</div>';
            } else {
                $warnings[] = '⚠️ storage/ may not be writable';
                echo '<div class="warning">⚠️ storage/ may not be writable (check permissions: 755)</div>';
            }
            
            if (is_writable($cachePath)) {
                $results[] = '✅ bootstrap/cache/ is writable';
                echo '<div class="success">✅ bootstrap/cache/ is writable</div>';
            } else {
                $warnings[] = '⚠️ bootstrap/cache/ may not be writable';
                echo '<div class="warning">⚠️ bootstrap/cache/ may not be writable (check permissions: 755)</div>';
            }

            // Summary
            echo '<h2>📊 Summary</h2>';
            if (empty($errors)) {
                echo '<div class="success" style="font-weight: bold; font-size: 18px;">';
                echo '🎉 All checks passed! Please refresh your website now.';
                echo '</div>';
            } else {
                echo '<div class="error" style="font-weight: bold;">';
                echo '❌ Some errors found. Please fix them before continuing.';
                echo '</div>';
            }

            if (!empty($warnings)) {
                echo '<div class="warning">';
                echo '<strong>⚠️ Warnings:</strong> These should be fixed for best results.';
                echo '</div>';
            }
            ?>

            <div class="warning" style="margin-top: 30px;">
                <strong>⚠️ Important:</strong>
                <ol>
                    <li>Refresh your website: <code>http://103.18.6.36/~lpi0g927o3nw/public/</code></li>
                    <li>If still having issues, check the errors above</li>
                    <li><strong>DELETE THIS FILE</strong> after use for security!</li>
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

