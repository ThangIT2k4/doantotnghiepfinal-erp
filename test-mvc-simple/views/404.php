<div class="card" style="border-left-color: #dc3545;">
    <h2>❌ 404 - Page Not Found</h2>
    <p style="margin-top: 15px; font-size: 1.1em;">
        Xin lỗi, trang bạn tìm kiếm không tồn tại.
    </p>
</div>

<div class="card">
    <h2>🔍 Request Information</h2>
    <div style="margin-top: 15px;">
        <p><strong>Request URI:</strong> <code><?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></code></p>
        <p style="margin-top: 10px;"><strong>Request Method:</strong> <code><?php echo htmlspecialchars($_SERVER['REQUEST_METHOD']); ?></code></p>
    </div>
</div>

<div class="card">
    <h2>📋 Available Routes</h2>
    <ul style="margin-top: 15px;">
        <li><a href="<?php echo str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); ?>/" style="color: #667eea;">Home Page</a></li>
        <li><a href="<?php echo str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); ?>/about" style="color: #667eea;">About Page</a></li>
        <li><a href="<?php echo str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); ?>/user/123" style="color: #667eea;">User Profile</a></li>
        <li><a href="<?php echo str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); ?>/api/test" style="color: #667eea;">API Test</a></li>
    </ul>
</div>

