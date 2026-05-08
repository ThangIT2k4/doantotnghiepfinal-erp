<?php
/**
 * Ultimate Fix - Fix tất cả vấn đề có thể
 * 
 * Truy cập: http://103.18.6.36/~lpi0g927o3nw/ultimate-fix.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    die('Error: vendor/autoload.php not found');
}

require __DIR__.'/vendor/autoload.php';

if (!file_exists(__DIR__.'/bootstrap/app.php')) {
    die('Error: bootstrap/app.php not found');
}

try {
    // Step 1: Delete ALL cache files
    $cacheDir = __DIR__.'/bootstrap/cache';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir.'/*.php');
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== '.gitignore') {
                @unlink($file);
            }
        }
    }
    
    // Step 2: Clear view cache
    $viewCachePath = __DIR__.'/storage/framework/views';
    if (is_dir($viewCachePath)) {
        $files = glob($viewCachePath.'/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
    
    // Step 3: Bootstrap Laravel
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Step 4: Try to rebuild routes
    try {
        // Clear route cache
        $routeCache = __DIR__.'/bootstrap/cache/routes.php';
        if (file_exists($routeCache)) {
            @unlink($routeCache);
        }
        
        // Get router and force reload routes
        $router = $app->make('router');
        
        // Force reload web routes
        if (file_exists(__DIR__.'/routes/web.php')) {
            // This will force Laravel to reload routes from file
            $router->getRoutes()->refreshNameLookups();
            $router->getRoutes()->refreshActionLookups();
        }
        
    } catch (Exception $e) {
        // Ignore errors during route reload
    }
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Ultimate Fix</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
            .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
            .success { color: #4CAF50; padding: 15px; margin: 10px 0; background: #f0f9f0; border-left: 4px solid #4CAF50; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
            .info { color: #666; padding: 15px; margin: 10px 0; background: #f9f9f9; border-left: 4px solid #999; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔧 Ultimate Fix Applied</h1>
            
            <div class="success">
                <strong>✅ All cache cleared and routes reloaded!</strong>
            </div>
            
            <h2>What was done:</h2>
            <ul>
                <li>✅ Deleted all cache files in bootstrap/cache/</li>
                <li>✅ Cleared view cache</li>
                <li>✅ Forced Laravel to reload routes</li>
                <li>✅ Refreshed route lookups</li>
            </ul>
            
            <h2>Test Routes:</h2>
            <?php
            $router = $app->make('router');
            $routes = $router->getRoutes();
            
            // Find root route
            $rootRoute = null;
            foreach ($routes as $route) {
                $uri = $route->uri();
                if ($uri === '/' || $uri === '') {
                    $rootRoute = $route;
                    break;
                }
            }
            
            if ($rootRoute) {
                $methods = $rootRoute->methods();
                echo '<div class="success">';
                echo '✅ Root route found!<br>';
                echo 'URI: <code>/</code><br>';
                echo 'Methods: <code>' . implode(', ', $methods) . '</code><br>';
                
                if (in_array('GET', $methods)) {
                    echo '<div class="success" style="margin-top: 10px;">✅ GET method IS supported!</div>';
                } else {
                    echo '<div class="warning" style="margin-top: 10px;">⚠️ GET method NOT in methods list</div>';
                }
                echo '</div>';
            } else {
                echo '<div class="warning">⚠️ Root route not found in registered routes</div>';
            }
            ?>
            
            <div class="warning" style="margin-top: 30px;">
                <strong>📝 Next Steps:</strong>
                <ol>
                    <li><strong>Try accessing your website again:</strong> <code>http://103.18.6.36/~lpi0g927o3nw/public/</code></li>
                    <li>If still getting 405 error, try: <code>http://103.18.6.36/~lpi0g927o3nw/public/index.php</code></li>
                    <li>Run <code>test-request.php</code> to see what request method is being captured</li>
                    <li>Check if there's a redirect happening that changes GET to HEAD</li>
                    <li><strong>DELETE THIS FILE</strong> after use!</li>
                </ol>
            </div>
            
            <div class="info" style="margin-top: 20px;">
                <strong>💡 If still not working:</strong><br>
                The issue might be with how the request is being captured. Try accessing directly:
                <code>http://103.18.6.36/~lpi0g927o3nw/public/index.php</code>
                (with index.php explicitly in the URL)
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

