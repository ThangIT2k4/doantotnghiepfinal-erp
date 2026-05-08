<?php
/**
 * ============================================================================
 * GENERATE APP KEY
 * ============================================================================
 * 
 * Script này giúp tạo APP_KEY cho Laravel mà không cần terminal
 * 
 * CÁCH DÙNG:
 * 1. Upload file này lên root của project trên server
 * 2. Truy cập: https://your-domain.com/generate-key.php
 * 3. Copy key được tạo vào file .env
 * 4. ⚠️ XÓA FILE NÀY SAU KHI DÙNG XONG để bảo mật!
 */

// Tạo key mới
$key = 'base64:' . base64_encode(random_bytes(32));

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate APP_KEY</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 700px;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 30px;
            font-size: 2em;
            text-align: center;
        }
        .key-box {
            background: #f8f9fa;
            border: 2px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            position: relative;
        }
        .key-label {
            color: #667eea;
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        .key-value {
            background: #1e1e1e;
            color: #4af;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            word-break: break-all;
            user-select: all;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            margin: 10px 0;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5568d3;
        }
        .instructions {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .instructions ol {
            margin-left: 20px;
            margin-top: 10px;
        }
        .instructions li {
            margin: 8px 0;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
        }
        .success-message {
            display: none;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Generate APP_KEY</h1>

        <div class="key-box">
            <div class="key-label">Your Generated APP_KEY:</div>
            <div class="key-value" id="appKey"><?php echo htmlspecialchars($key); ?></div>
        </div>

        <button class="btn" onclick="copyKey()">📋 Copy Key to Clipboard</button>
        
        <div class="success-message" id="successMessage">
            ✅ Copied to clipboard!
        </div>

        <div class="instructions">
            <strong>📋 Hướng dẫn sử dụng:</strong>
            <ol>
                <li>Click nút <strong>"Copy Key"</strong> bên trên</li>
                <li>Vào <strong>cPanel File Manager</strong></li>
                <li>Mở file <code>.env</code> trong thư mục root</li>
                <li>Tìm dòng <code>APP_KEY=</code></li>
                <li>Thay thế bằng key đã copy:</li>
            </ol>
            <div style="background: #1e1e1e; color: #4af; padding: 10px; border-radius: 4px; margin-top: 10px; font-family: monospace;">
                APP_KEY=<?php echo htmlspecialchars($key); ?>
            </div>
            <ol start="6" style="margin-top: 10px;">
                <li><strong>Lưu file .env</strong></li>
                <li>Chạy file <code>clear-all-cache.php</code> để clear cache</li>
                <li><strong>⚠️ XÓA FILE generate-key.php này sau khi xong!</strong></li>
            </ol>
        </div>

        <div class="warning">
            🔒 BẢO MẬT: XÓA FILE NÀY SAU KHI COPY KEY!
        </div>

        <p style="color: #666; font-size: 0.9em; text-align: center; margin-top: 30px;">
            Laravel APP_KEY Generator | <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>

    <script>
        function copyKey() {
            const keyElement = document.getElementById('appKey');
            const key = keyElement.textContent;
            
            // Create temporary textarea
            const textarea = document.createElement('textarea');
            textarea.value = key;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            
            // Select and copy
            textarea.select();
            textarea.setSelectionRange(0, 99999); // For mobile
            
            try {
                document.execCommand('copy');
                
                // Show success message
                const successMessage = document.getElementById('successMessage');
                successMessage.style.display = 'block';
                
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 3000);
                
            } catch (err) {
                alert('Không thể copy tự động. Vui lòng copy thủ công.');
            }
            
            document.body.removeChild(textarea);
        }
    </script>
</body>
</html>

