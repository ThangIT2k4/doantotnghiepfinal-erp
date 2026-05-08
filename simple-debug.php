<?php
/**
 * Simple Debug - Kiểm tra cơ bản
 * 
 * Truy cập: http://103.18.6.36/~lpi0g927o3nw/simple-debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Debug</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .success { color: #4CAF50; padding: 10px; margin: 5px 0; background: #f0f9f0; border-left: 4px solid #4CAF50; }
        .error { color: #dc3545; padding: 10px; margin: 5px 0; background: #fff5f5; border-left: 4px solid #dc3545; }
        .info { color: #666; padding: 10px; margin: 5px 0; background: #f9f9f9; border-left: 4px solid #999; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Simple Debug</h1>
        
        <h2>Step 1: Basic Checks</h2>
        <?php
        echo '<div class="info">PHP Version: ' . phpversion() . '</div>';
        echo '<div class="info">Current Directory: ' . __DIR__ . '</div>';
        ?>
        
        <h2>Step 2: Check Files</h2>
        <?php
        $files = [
            'vendor/autoload.php',
            'bootstrap/app.php',
            'routes/web.php',
            '.env',
            'public/index.php',
        ];
        
        foreach ($files as $file) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                echo '<div class="success">✅ ' . $file . ' exists</div>';
            } else {
                echo '<div class="error">❌ ' . $file . ' NOT found</div>';
            }
        }
        ?>
        
        <h2>Step 3: Check Routes File</h2>
        <?php
        $routesFile = __DIR__ . '/routes/web.php';
        if (file_exists($routesFile)) {
            $content = file_get_contents($routesFile);
            
            // Check for Route::get('/', ...)
            if (strpos($content, "Route::get('/',") !== false) {
                echo '<div class="success">✅ Found Route::get("/", ...) in routes/web.php</div>';
                
                // Extract the route line
                $lines = explode("\n", $content);
                foreach ($lines as $lineNum => $line) {
                    if (strpos($line, "Route::get('/',") !== false) {
                        echo '<div class="info">Line ' . ($lineNum + 1) . ': <code>' . htmlspecialchars(trim($line)) . '</code></div>';
                    }
                }
            } else {
                echo '<div class="error">❌ Route::get("/", ...) NOT found in routes/web.php</div>';
            }
        } else {
            echo '<div class="error">❌ routes/web.php not found</div>';
        }
        ?>
        
        <h2>Step 4: Check Cache Files</h2>
        <?php
        $cacheFiles = [
            'bootstrap/cache/routes.php',
            'bootstrap/cache/config.php',
            'bootstrap/cache/services.php',
            'bootstrap/cache/packages.php',
        ];
        
        foreach ($cacheFiles as $cacheFile) {
            $fullPath = __DIR__ . '/' . $cacheFile;
            if (file_exists($fullPath)) {
                echo '<div class="error">❌ ' . $cacheFile . ' STILL EXISTS (should be deleted!)</div>';
            } else {
                echo '<div class="success">✅ ' . $cacheFile . ' not found (good)</div>';
            }
        }
        ?>
        
        <h2>Step 5: Check .env File</h2>
        <?php
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            
            if (preg_match('/APP_KEY=base64:[A-Za-z0-9+\/]+=*/', $envContent)) {
                echo '<div class="success">✅ APP_KEY exists in .env</div>';
            } else {
                echo '<div class="error">❌ APP_KEY not found or invalid in .env</div>';
            }
            
            if (strpos($envContent, 'APP_URL=') !== false) {
                echo '<div class="success">✅ APP_URL exists in .env</div>';
            } else {
                echo '<div class="error">❌ APP_URL not found in .env</div>';
            }
        } else {
            echo '<div class="error">❌ .env file not found</div>';
        }
        ?>
        
        <h2>Step 6: Try Loading Laravel</h2>
        <?php
        if (file_exists(__DIR__.'/vendor/autoload.php') && file_exists(__DIR__.'/bootstrap/app.php')) {
            try {
                require __DIR__.'/vendor/autoload.php';
                echo '<div class="success">✅ vendor/autoload.php loaded successfully</div>';
                
                $app = require_once __DIR__.'/bootstrap/app.php';
                echo '<div class="success">✅ bootstrap/app.php loaded successfully</div>';
                
                $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
                $kernel->bootstrap();
                echo '<div class="success">✅ Laravel bootstrapped successfully</div>';
                
                // Try to get router
                $router = $app->make('router');
                $routes = $router->getRoutes();
                
                echo '<div class="success">✅ Router loaded. Total routes: ' . count($routes) . '</div>';
                
                // Check root route
                $foundRoot = false;
                foreach ($routes as $route) {
                    $uri = $route->uri();
                    if ($uri === '/' || $uri === '') {
                        $foundRoot = true;
                        $methods = $route->methods();
                        echo '<div class="info">';
                        echo 'Root route found:<br>';
                        echo 'URI: <code>' . htmlspecialchars($uri ?: '/') . '</code><br>';
                        echo 'Methods: <code>' . implode(', ', $methods) . '</code><br>';
                        
                        if (in_array('GET', $methods)) {
                            echo '<div class="success">✅ GET method is supported!</div>';
                        } else {
                            echo '<div class="error">❌ GET method is NOT supported! Only: ' . implode(', ', $methods) . '</div>';
                        }
                        echo '</div>';
                        break;
                    }
                }
                
                if (!$foundRoot) {
                    echo '<div class="error">❌ Root route "/" NOT found in registered routes!</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Error loading Laravel: ' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '<div class="error">File: ' . htmlspecialchars($e->getFile()) . '</div>';
                echo '<div class="error">Line: ' . $e->getLine() . '</div>';
            }
        } else {
            echo '<div class="error">❌ Cannot load Laravel - missing files</div>';
        }
        ?>
        
        <h2>📊 Summary</h2>
        <div class="info">
            <strong>Next steps:</strong>
            <ol>
                <li>Check the errors above</li>
                <li>If cache files still exist, delete them manually in File Manager</li>
                <li>If route "/" doesn't support GET, there's a serious routing issue</li>
                <li>Check Laravel logs: <code>storage/logs/laravel.log</code></li>
            </ol>
        </div>
    </div>
</body>
</html>

