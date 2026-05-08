<div class="card">
    <h2>👤 User Profile</h2>
    <p style="margin-top: 15px;">
        Đây là trang profile của user với ID: <strong><?php echo htmlspecialchars($user['id']); ?></strong>
    </p>
</div>

<div class="user-info">
    <div>
        <div class="label">User ID</div>
        <div class="value"><?php echo htmlspecialchars($user['id']); ?></div>
    </div>
    
    <div>
        <div class="label">Full Name</div>
        <div class="value"><?php echo htmlspecialchars($user['name']); ?></div>
    </div>
    
    <div>
        <div class="label">Email Address</div>
        <div class="value"><?php echo htmlspecialchars($user['email']); ?></div>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <h2>🔍 Try Other Users</h2>
    <p style="margin-top: 15px;">
        Thay đổi ID trong URL để xem profile của user khác:
    </p>
    <ul style="margin-top: 15px;">
        <li><a href="<?php echo str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))); ?>/user/1" style="color: #667eea;">User ID: 1 (John Doe)</a></li>
        <li><a href="<?php echo str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))); ?>/user/2" style="color: #667eea;">User ID: 2 (Jane Smith)</a></li>
        <li><a href="<?php echo str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))); ?>/user/123" style="color: #667eea;">User ID: 123 (Test User)</a></li>
        <li><a href="<?php echo str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))); ?>/user/999" style="color: #667eea;">User ID: 999 (Not Found - Test 404)</a></li>
    </ul>
</div>

<div class="card" style="margin-top: 20px;">
    <h2>💡 How Dynamic Routing Works</h2>
    <p style="margin-top: 15px; line-height: 1.8;">
        Route pattern: <code>/user/:id</code><br>
        Current URL: <code><?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></code><br>
        Extracted ID: <code><?php echo htmlspecialchars($user['id']); ?></code>
    </p>
    <p style="margin-top: 15px;">
        Router tự động extract parameter <code>:id</code> từ URL và pass vào Controller method!
    </p>
</div>

