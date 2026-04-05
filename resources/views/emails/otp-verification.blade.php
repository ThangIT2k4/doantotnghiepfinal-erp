<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác thực OTP</title>
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
        .otp-code {
            background-color: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .otp-code .code {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .alert {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
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
            <h1>🔐 Mã xác thực OTP</h1>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $userName }}</strong>,</p>

            <p>Chúng tôi nhận được yêu cầu xác thực email của bạn. Vui lòng sử dụng mã OTP bên dưới để hoàn tất quá trình xác thực:</p>

            <div class="otp-code">
                <div class="code">{{ $otpCode }}</div>
                <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">
                    Mã này có hiệu lực trong {{ $expiryMinutes }} phút
                </p>
            </div>

            <div class="alert">
                <p style="margin: 0;">
                    ⚠️ <strong>Lưu ý quan trọng:</strong> Không chia sẻ mã này với bất kỳ ai. Chúng tôi sẽ không bao giờ yêu cầu bạn cung cấp mã OTP qua điện thoại hoặc email.
                </p>
            </div>

            <div class="info-box">
                <p style="margin: 0; font-size: 14px;">
                    <strong>🛡️ Mẹo bảo mật:</strong><br>
                    • Luôn kiểm tra địa chỉ email người gửi<br>
                    • Không nhấp vào các liên kết đáng ngờ<br>
                    • Sử dụng mật khẩu mạnh và duy nhất<br>
                    • Báo cáo ngay nếu bạn nhận được email đáng ngờ
                </p>
            </div>

            <p>Nếu bạn không yêu cầu mã xác thực này, vui lòng bỏ qua email này hoặc liên hệ với chúng tôi để được hỗ trợ.</p>

            <p>Trân trọng,<br><strong>{{ $organizationName ?? 'ZoroRMS Team' }}</strong></p>
        </div>

        <div class="footer">
            <p>Email này được gửi tự động. Vui lòng không trả lời email này.</p>
            <p>&copy; {{ date('Y') }} ZoroRMS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
