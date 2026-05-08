<?php
/**
 * Debug Routes - Kiểm tra routes đang được đăng ký
 * 
 * Truy cập: http://103.18.6.36/~lpi0g927o3nw/debug-routes.php
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

    $router = $app->make('router');
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Debug Routes</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 1200px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
            .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
            .success { color: #4CAF50; padding: 10px; margin: 5px 0; background: #f0f9f0; border-left: 4px solid #4CAF50; }
            .error { color: #dc3545; padding: 10px; margin: 5px 0; background: #fff5f5; border-left: 4px solid #dc3545; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            .info { color: #666; padding: 10px; margin: 5px 0; background: #f9f9f9; border-left: 4px solid #999; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
            th { background: #f4f4f4; font-weight: bold; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔍 Debug Routes</h1>
            
            <?php
            // Get all routes
            $routes = $router->getRoutes();
            
            echo '<h2>All Registered Routes:</h2>';
            echo '<table>';
            echo '<tr><th>Method</th><th>URI</th><th>Name</th><th>Action</th></tr>';
            
            $foundRootRoute = false;
            $rootRouteInfo = null;
            
            foreach ($routes as $route) {
                $methods = $route->methods();
                $uri = $route->uri();
                $name = $route->getName();
                $action = $route->getActionName();
                
                // Check if this is the root route
                if ($uri === '/' || $uri === '') {
                    $foundRootRoute = true;
                    $rootRouteInfo = [
                        'methods' => $methods,
                        'uri' => $uri,
                        'name' => $name,
                        'action' => $action
                    ];
                    echo '<tr style="background: #fff3cd;">';
                } else {
                    echo '<tr>';
                }
                
                echo '<td>' . implode(', ', $methods) . '</td>';
                echo '<td><code>' . htmlspecialchars($uri ?: '/') . '</code></td>';
                echo '<td>' . htmlspecialchars($name ?: '-') . '</code></td>';
                echo '<td>' . htmlspecialchars($action) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            
            // Check root route specifically
            echo '<h2>Root Route ("/") Analysis:</h2>';
            
            if ($foundRootRoute && $rootRouteInfo) {
                echo '<div class="info">';
                echo '<strong>Route found:</strong><br>';
                echo 'URI: <code>' . htmlspecialchars($rootRouteInfo['uri'] ?: '/') . '</code><br>';
                echo 'Methods: <code>' . implode(', ', $rootRouteInfo['methods']) . '</code><br>';
                echo 'Name: <code>' . htmlspecialchars($rootRouteInfo['name'] ?: '-') . '</code><br>';
                echo 'Action: <code>' . htmlspecialchars($rootRouteInfo['action']) . '</code><br>';
                echo '</div>';
                
                // Check if GET is in methods
                if (in_array('GET', $rootRouteInfo['methods'])) {
                    echo '<div class="success">✅ GET method is supported for route "/"</div>';
                } else {
                    echo '<div class="error">❌ GET method is NOT in supported methods!</div>';
                    echo '<div class="warning">';
                    echo '<strong>Problem found!</strong> Route "/" does not support GET method.<br>';
                    echo 'Supported methods: ' . implode(', ', $rootRouteInfo['methods']) . '<br>';
                    echo 'This is why you get the 405 error!';
                    echo '</div>';
                }
                
                // Check if HEAD is the only method
                if (count($rootRouteInfo['methods']) === 1 && in_array('HEAD', $rootRouteInfo['methods'])) {
                    echo '<div class="error">';
                    echo '<strong>❌ CRITICAL ISSUE:</strong> Route "/" only supports HEAD method!<br>';
                    echo 'This is very unusual. Possible causes:<br>';
                    echo '<ul>';
                    echo '<li>Route cache is corrupted</li>';
                    echo '<li>Routes file is not being loaded correctly</li>';
                    echo '<li>There is a middleware issue</li>';
                    echo '<li>Laravel bootstrap issue</li>';
                    echo '</ul>';
                    echo '</div>';
                }
            } else {
                echo '<div class="error">❌ Root route "/" NOT found in registered routes!</div>';
            }
            
            // Try to manually check routes/web.php
            echo '<h2>Checking routes/web.php file:</h2>';
            $routesFile = __DIR__.'/routes/web.php';
            if (file_exists($routesFile)) {
                $routesContent = file_get_contents($routesFile);
                
                // Check for Route::get('/', ...)
                if (preg_match('/Route::get\([\'"]\/[\'"]/', $routesContent)) {
                    echo '<div class="success">✅ Found Route::get("/", ...) in routes/web.php</div>';
                } else {
                    echo '<div class="error">❌ Route::get("/", ...) NOT found in routes/web.php</div>';
                }
                
                // Show the actual route definition
                if (preg_match('/Route::get\([\'"]\/[\'"].*?\);/s', $routesContent, $matches)) {
                    echo '<div class="info">';
                    echo '<strong>Route definition found:</strong><br>';
                    echo '<code>' . htmlspecialchars($matches[0]) . '</code>';
                    echo '</div>';
                }
            }
            
            // Check for route cache
            echo '<h2>Checking for Route Cache:</h2>';
            $routeCache = __DIR__.'/bootstrap/cache/routes.php';
            if (file_exists($routeCache)) {
                echo '<div class="error">❌ Route cache file STILL EXISTS: bootstrap/cache/routes.php</div>';
                echo '<div class="warning">This cache file might be corrupted. Try deleting it manually.</div>';
            } else {
                echo '<div class="success">✅ No route cache file found (good)</div>';
            }
            
            // Check config cache
            $configCache = __DIR__.'/bootstrap/cache/config.php';
            if (file_exists($configCache)) {
                echo '<div class="warning">⚠️ Config cache file exists: bootstrap/cache/config.php</div>';
                echo '<div class="info">This might affect route loading. Consider deleting it.</div>';
            }
            ?>

            <div class="warning" style="margin-top: 30px;">
                <strong>💡 Recommendations:</strong>
                <ol>
                    <li>If route "/" only shows HEAD method, try deleting ALL cache files manually in File Manager</li>
                    <li>Check if there are any middleware that might be affecting routes</li>
                    <li>Try accessing with trailing slash: <code>http://103.18.6.36/~lpi0g927o3nw/public/</code></li>
                    <li>Check Laravel logs: <code>storage/logs/laravel.log</code></li>
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

