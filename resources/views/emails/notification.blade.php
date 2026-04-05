<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
        }
        .notification-content {
            font-size: 15px;
            line-height: 1.8;
            color: #495057;
            white-space: pre-line;
        }
        .alert {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
        }
        .error {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            padding: 16px 48px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            border: none;
        }
        .button:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .button-container {
            text-align: center;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .link {
            word-break: break-all;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                @if($type === 'info')
                    ℹ️ {{ $subject }}
                @elseif($type === 'success')
                    ✅ {{ $subject }}
                @elseif($type === 'warning')
                    ⚠️ {{ $subject }}
                @elseif($type === 'error')
                    ❌ {{ $subject }}
                @else
                    🔔 {{ $subject }}
                @endif
            </h1>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $userName }}</strong>,</p>

            <div class="info-box {{ $type === 'success' ? 'success' : ($type === 'error' ? 'error' : ($type === 'warning' ? 'alert' : '')) }}">
                <div class="notification-content">{{ $content }}</div>
            </div>

            @if($actionUrl)
            <div class="button-container">
                <a href="{{ $actionUrl }}" class="button">
                    {{ $actionText ?? 'Xem chi tiết' }}
                </a>
            </div>

            <p style="font-size: 12px; color: #666;">
                Hoặc copy link sau vào trình duyệt:<br>
                <span class="link">{{ $actionUrl }}</span>
            </p>
            @endif

            <p>Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi.</p>

            <p>Trân trọng,<br><strong>{{ $organizationName ?? 'ZoroRMS Team' }}</strong></p>
        </div>

        <div class="footer">
            <p>Email này được gửi tự động. Vui lòng không trả lời email này.</p>
            <p>&copy; {{ date('Y') }} ZoroRMS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

