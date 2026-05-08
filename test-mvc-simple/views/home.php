<div class="card">
    <h2>🎉 <?php echo htmlspecialchars($message); ?></h2>
    <p style="margin-top: 15px; line-height: 1.8;">
        Đây là một MVC framework siêu đơn giản được xây dựng bằng PHP thuần.
        Framework này giúp bạn hiểu rõ cách hoạt động của pattern MVC mà không cần 
        phụ thuộc vào Laravel hay framework khác.
    </p>
</div>

<div class="card">
    <h2>📋 Features</h2>
    <ul style="margin-top: 15px;">
        <li>✅ <strong>Routing System</strong> - Hỗ trợ dynamic routes với parameters</li>
        <li>✅ <strong>MVC Pattern</strong> - Tách biệt Model, View, Controller</li>
        <li>✅ <strong>View Rendering</strong> - Render views với layout support</li>
        <li>✅ <strong>JSON API</strong> - Hỗ trợ JSON response cho API</li>
        <li>✅ <strong>Clean Code</strong> - Code đơn giản, dễ hiểu, dễ customize</li>
    </ul>
</div>

<div class="card">
    <h2>🧪 Try These Routes:</h2>
    <ul style="margin-top: 15px;">
        <li>
            <strong>Home:</strong> 
            <code><?php echo str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); ?>/</code>
        </li>
        <li>
            <strong>About:</strong> 
            <code><?php echo str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); ?>/about</code>
        </li>
        <li>
            <strong>User Profile:</strong> 
            <code><?php echo str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); ?>/user/123</code>
        </li>
        <li>
            <strong>API Test:</strong> 
            <code><?php echo str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); ?>/api/test</code>
        </li>
    </ul>
</div>

<div class="card">
    <h2>📊 Server Info</h2>
    <div style="margin-top: 15px;">
        <span class="badge">PHP: <?php echo PHP_VERSION; ?></span>
        <span class="badge">Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
        <span class="badge">Method: <?php echo $_SERVER['REQUEST_METHOD']; ?></span>
    </div>
</div>

