<div class="card">
    <h2>ℹ️ About <?php echo htmlspecialchars($framework); ?></h2>
    <p style="margin-top: 15px; line-height: 1.8;">
        Đây là một MVC framework minimal được tạo ra cho mục đích học tập và testing.
        Framework này không cần bất kỳ dependencies nào, chỉ cần PHP thuần.
    </p>
</div>

<div class="card">
    <h2>🎯 Framework Features</h2>
    <ul style="margin-top: 15px;">
        <?php foreach ($features as $feature): ?>
            <li>✅ <?php echo htmlspecialchars($feature); ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="card">
    <h2>🏗️ Architecture</h2>
    <div style="margin-top: 15px;">
        <p><strong>Model</strong> - Quản lý data và business logic</p>
        <p style="margin-top: 10px;"><strong>View</strong> - Hiển thị giao diện người dùng</p>
        <p style="margin-top: 10px;"><strong>Controller</strong> - Xử lý request và điều phối luồng</p>
    </div>
</div>

<div class="card">
    <h2>📂 File Structure</h2>
    <pre style="background: #f4f4f4; padding: 15px; border-radius: 6px; margin-top: 15px; overflow-x: auto;">
test-mvc-simple/
├── index.php           # Main entry point & router
├── views/
│   ├── layout.php      # Master layout
│   ├── home.php        # Home view
│   ├── about.php       # About view
│   ├── user/
│   │   └── profile.php # User profile view
│   └── 404.php         # Not found view
└── README.md           # Documentation
    </pre>
</div>

<div class="card">
    <h2>💡 How It Works</h2>
    <ol style="margin-top: 15px; margin-left: 20px; line-height: 1.8;">
        <li>Request đến <code>index.php</code></li>
        <li><code>Router</code> match URL với route đã định nghĩa</li>
        <li>Gọi <code>Controller</code> tương ứng</li>
        <li><code>Controller</code> xử lý logic và gọi <code>View</code></li>
        <li><code>View</code> render HTML và trả về response</li>
    </ol>
</div>

<div class="card">
    <h2>🚀 Quick Start</h2>
    <p style="margin-top: 15px;"><strong>Step 1:</strong> Upload thư mục <code>test-mvc-simple</code> lên server</p>
    <p style="margin-top: 10px;"><strong>Step 2:</strong> Truy cập: <code>http://yourdomain.com/test-mvc-simple/</code></p>
    <p style="margin-top: 10px;"><strong>Step 3:</strong> Explore và customize!</p>
</div>

