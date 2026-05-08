<?php
/**
 * Fix 405 Method Not Allowed Error on cPanel
 * 
 * This script fixes the common "The GET method is not supported for route /"
 * error that occurs when deploying Laravel to cPanel.
 * 
 * The issue is usually caused by stale route cache from local development.
 * 
 * Usage: Upload this file to your root directory and run it once via browser
 * URL: http://yourdomain.com/~username/fix-405-route-error.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Fix 405 Route Error - Laravel on cPanel</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
    .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #e3342f; border-bottom: 3px solid #e3342f; padding-bottom: 10px; }
    h2 { color: #3490dc; margin-top: 30px; }
    .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 4px; margin: 10px 0; }
    .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px; margin: 10px 0; }
    .warning { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 12px; border-radius: 4px; margin: 10px 0; }
    .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 12px; border-radius: 4px; margin: 10px 0; }
    .step { background: #f8f9fa; padding: 15px; margin: 10px 0; border-left: 4px solid #3490dc; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
    pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; }
    .btn { display: inline-block; padding: 10px 20px; background: #3490dc; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 10px 0; }
    .btn:hover { background: #2779bd; }
    .btn-danger { background: #e3342f; }
    .btn-danger:hover { background: #cc1f1a; }
    ul { line-height: 1.8; }
    .file-path { color: #6c757d; font-size: 0.9em; font-family: 'Courier New', monospace; }
</style>";
echo "</head><body>";
echo "<div class='container'>";

echo "<h1>🔧 Fix 405 Method Not Allowed Error</h1>";

echo "<div class='info'>";
echo "<strong>Error:</strong> The GET method is not supported for route /. Supported methods: HEAD.<br>";
echo "<strong>Cause:</strong> Stale route cache from local development environment.<br>";
echo "<strong>Solution:</strong> Clear all Laravel caches, especially route cache.";
echo "</div>";

$rootDir = __DIR__;
$bootstrapCacheDir = $rootDir . '/bootstrap/cache';
$storageCacheDir = $rootDir . '/storage/framework/cache';
$storageViewsDir = $rootDir . '/storage/framework/views';

echo "<h2>📝 Step 1: Checking File Permissions</h2>";

$dirsToCheck = [
    'bootstrap/cache' => $bootstrapCacheDir,
    'storage' => $rootDir . '/storage',
    'storage/framework' => $rootDir . '/storage/framework',
    'storage/framework/cache' => $storageCacheDir,
    'storage/framework/views' => $storageViewsDir,
    'storage/logs' => $rootDir . '/storage/logs',
];

$permissionIssues = [];

foreach ($dirsToCheck as $name => $dir) {
    if (file_exists($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir);
        
        if ($writable) {
            echo "<div class='step'>✅ <code>$name</code> - Writable (Permissions: $perms)</div>";
        } else {
            echo "<div class='error'>❌ <code>$name</code> - NOT Writable (Permissions: $perms)</div>";
            $permissionIssues[] = $name;
        }
    } else {
        echo "<div class='warning'>⚠️ <code>$name</code> - Directory does not exist</div>";
    }
}

if (!empty($permissionIssues)) {
    echo "<div class='error'>";
    echo "<strong>⚠️ Permission Issues Found!</strong><br>";
    echo "Please set permissions to 755 or 775 for the following directories:<br>";
    echo "<ul>";
    foreach ($permissionIssues as $dir) {
        echo "<li><code>$dir</code></li>";
    }
    echo "</ul>";
    echo "Run this command via SSH or use cPanel File Manager:<br>";
    echo "<code>chmod -R 755 storage bootstrap/cache</code>";
    echo "</div>";
}

echo "<h2>🗑️ Step 2: Clearing Route Cache</h2>";

$cacheFilesToDelete = [
    'routes-v7.php' => $bootstrapCacheDir . '/routes-v7.php',
    'config.php' => $bootstrapCacheDir . '/config.php',
    'packages.php' => $bootstrapCacheDir . '/packages.php',
    'services.php' => $bootstrapCacheDir . '/services.php',
];

$deletedFiles = [];
$failedFiles = [];

foreach ($cacheFilesToDelete as $name => $file) {
    if (file_exists($file)) {
        if (is_writable($file)) {
            if (@unlink($file)) {
                echo "<div class='success'>✅ Deleted: <code>bootstrap/cache/$name</code></div>";
                $deletedFiles[] = $name;
            } else {
                echo "<div class='error'>❌ Failed to delete: <code>bootstrap/cache/$name</code></div>";
                $failedFiles[] = $name;
            }
        } else {
            echo "<div class='error'>❌ Cannot delete (not writable): <code>bootstrap/cache/$name</code></div>";
            $failedFiles[] = $name;
        }
    } else {
        echo "<div class='step'>ℹ️ File does not exist (already clean): <code>bootstrap/cache/$name</code></div>";
    }
}

echo "<h2>🧹 Step 3: Clearing Compiled Views</h2>";

if (is_dir($storageViewsDir)) {
    $viewFiles = glob($storageViewsDir . '/*.php');
    $deletedCount = 0;
    
    if ($viewFiles) {
        foreach ($viewFiles as $file) {
            if (is_file($file) && is_writable($file)) {
                if (@unlink($file)) {
                    $deletedCount++;
                }
            }
        }
        echo "<div class='success'>✅ Deleted $deletedCount compiled view file(s) from <code>storage/framework/views/</code></div>";
    } else {
        echo "<div class='step'>ℹ️ No compiled views to delete</div>";
    }
} else {
    echo "<div class='warning'>⚠️ Views directory does not exist: <code>storage/framework/views/</code></div>";
}

echo "<h2>🔍 Step 4: Checking .htaccess Files</h2>";

$htaccessRoot = $rootDir . '/.htaccess';
$htaccessPublic = $rootDir . '/public/.htaccess';

// Check root .htaccess
if (file_exists($htaccessRoot)) {
    echo "<div class='step'>✅ Root <code>.htaccess</code> exists</div>";
    $rootContent = file_get_contents($htaccessRoot);
    if (strpos($rootContent, 'RewriteEngine On') !== false) {
        echo "<div class='success'>✅ Root <code>.htaccess</code> has RewriteEngine enabled</div>";
    } else {
        echo "<div class='error'>❌ Root <code>.htaccess</code> missing RewriteEngine directive</div>";
    }
} else {
    echo "<div class='error'>❌ Root <code>.htaccess</code> does not exist</div>";
}

// Check public .htaccess
if (file_exists($htaccessPublic)) {
    echo "<div class='step'>✅ Public <code>.htaccess</code> exists</div>";
    $publicContent = file_get_contents($htaccessPublic);
    if (strpos($publicContent, 'RewriteEngine On') !== false) {
        echo "<div class='success'>✅ Public <code>.htaccess</code> has RewriteEngine enabled</div>";
    } else {
        echo "<div class='error'>❌ Public <code>.htaccess</code> missing RewriteEngine directive</div>";
    }
} else {
    echo "<div class='error'>❌ Public <code>.htaccess</code> does not exist</div>";
}

echo "<h2>⚙️ Step 5: Checking Configuration</h2>";

// Check if .env exists
$envFile = $rootDir . '/.env';
if (file_exists($envFile)) {
    echo "<div class='success'>✅ <code>.env</code> file exists</div>";
    
    // Parse .env file
    $envContent = file_get_contents($envFile);
    $envLines = explode("\n", $envContent);
    $envVars = [];
    
    foreach ($envLines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
    
    // Check important settings
    $appEnv = $envVars['APP_ENV'] ?? 'NOT SET';
    $appDebug = $envVars['APP_DEBUG'] ?? 'NOT SET';
    $appUrl = $envVars['APP_URL'] ?? 'NOT SET';
    
    echo "<div class='step'>";
    echo "Current Environment Settings:<br>";
    echo "<ul>";
    echo "<li><strong>APP_ENV:</strong> <code>$appEnv</code> " . ($appEnv === 'production' ? "✅" : "⚠️ Should be 'production'") . "</li>";
    echo "<li><strong>APP_DEBUG:</strong> <code>$appDebug</code> " . ($appDebug === 'false' ? "✅" : "⚠️ Should be 'false' in production") . "</li>";
    echo "<li><strong>APP_URL:</strong> <code>$appUrl</code></li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error'>❌ <code>.env</code> file does not exist</div>";
}

echo "<h2>📊 Summary</h2>";

if (empty($failedFiles) && empty($permissionIssues)) {
    echo "<div class='success'>";
    echo "<strong>✅ All cache files cleared successfully!</strong><br><br>";
    echo "Your Laravel application should now work properly. Try accessing your site:";
    echo "</div>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='/' class='btn'>🏠 Go to Home Page</a>";
    echo "<a href='/login' class='btn'>🔐 Go to Login</a>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<strong>✅ What was fixed:</strong>";
    echo "<ul>";
    if (!empty($deletedFiles)) {
        echo "<li>Deleted " . count($deletedFiles) . " cached file(s): " . implode(', ', $deletedFiles) . "</li>";
    }
    echo "<li>Cleared compiled views</li>";
    echo "<li>Your routes should now work correctly</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<strong>⚠️ Important Notes:</strong>";
    echo "<ul>";
    echo "<li>Do NOT run <code>php artisan route:cache</code> or <code>php artisan config:cache</code> on cPanel</li>";
    echo "<li>Always clear caches before deploying</li>";
    echo "<li>Make sure your APP_URL in .env matches your actual URL</li>";
    echo "<li>You can delete this script after the fix: <code>fix-405-route-error.php</code></li>";
    echo "</ul>";
    echo "</div>";
    
} else {
    echo "<div class='error'>";
    echo "<strong>❌ Some issues remain:</strong>";
    echo "<ul>";
    if (!empty($failedFiles)) {
        echo "<li>Failed to delete: " . implode(', ', $failedFiles) . "</li>";
        echo "<li><strong>Solution:</strong> Delete these files manually via cPanel File Manager</li>";
    }
    if (!empty($permissionIssues)) {
        echo "<li>Permission issues with: " . implode(', ', $permissionIssues) . "</li>";
        echo "<li><strong>Solution:</strong> Set permissions to 755 or 775 via cPanel File Manager</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<strong>Manual Steps:</strong>";
    echo "<ol>";
    echo "<li>Go to cPanel File Manager</li>";
    echo "<li>Navigate to <code>bootstrap/cache/</code></li>";
    echo "<li>Delete all PHP files in that directory</li>";
    echo "<li>Go to <code>storage/framework/views/</code></li>";
    echo "<li>Delete all PHP files in that directory</li>";
    echo "<li>Set permissions for <code>storage</code> and <code>bootstrap/cache</code> to 755</li>";
    echo "<li>Refresh your site</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<h2>🔧 Additional Help</h2>";

echo "<div class='step'>";
echo "<strong>If the problem persists:</strong>";
echo "<ol>";
echo "<li><strong>Check your route definition</strong> in <code>routes/web.php</code>:</li>";
echo "<pre>Route::get('/', [IndexController::class, 'index'])->name('home');</pre>";
echo "<li><strong>Verify IndexController exists</strong> at <code>app/Http/Controllers/IndexController.php</code></li>";
echo "<li><strong>Check .htaccess mod_rewrite is enabled</strong> on your server</li>";
echo "<li><strong>Verify APP_URL</strong> in .env matches your actual domain</li>";
echo "<li><strong>Clear browser cache</strong> (Ctrl+Shift+Delete)</li>";
echo "</ol>";
echo "</div>";

echo "<div class='info'>";
echo "<strong>Common cPanel Issues:</strong>";
echo "<ul>";
echo "<li><strong>Wrong Document Root:</strong> Should point to <code>public</code> folder</li>";
echo "<li><strong>.htaccess not working:</strong> Contact host to enable mod_rewrite</li>";
echo "<li><strong>PHP version:</strong> Laravel 12 requires PHP 8.3 or higher</li>";
echo "<li><strong>Cached routes:</strong> Never run artisan cache commands on cPanel</li>";
echo "</ul>";
echo "</div>";

echo "<hr style='margin: 30px 0;'>";
echo "<div style='text-align: center; color: #6c757d; font-size: 0.9em;'>";
echo "Laravel cPanel Deployment Fix Script v1.0<br>";
echo "Generated: " . date('Y-m-d H:i:s') . "<br>";
echo "PHP Version: " . PHP_VERSION . " | Laravel Version: 12.34.0";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>

