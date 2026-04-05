<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['is_success'] ?? false ? 'Xác nhận thanh toán' : 'Thông báo thanh toán đặt cọc' }}</title>
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
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .amount {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            text-align: center;
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
                @if($data['is_success'] ?? false)
                    ✅ Thanh toán thành công
                @elseif($data['is_reminder'] ?? false)
                    ⏰ Nhắc nhở thanh toán
                @else
                    💳 Thông báo thanh toán đặt cọc
                @endif
            </h1>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $data['tenant_name'] }}</strong>,</p>

            @if($data['is_success'] ?? false)
                <div class="success">
                    <p style="margin: 0;">✅ Thanh toán đặt cọc của bạn đã được xác nhận thành công!</p>
                </div>

                <p>Cảm ơn bạn đã hoàn tất thanh toán đặt cọc cho:</p>
            @elseif($data['is_reminder'] ?? false)
                <div class="alert">
                    <p style="margin: 0;">⚠️ Đây là lời nhắc thanh toán đặt cọc sắp hết hạn!</p>
                </div>

                <p>Bạn vẫn chưa hoàn tất thanh toán đặt cọc cho:</p>
            @else
                <p>Bạn đã đặt cọc thành công cho bất động sản. Vui lòng hoàn tất thanh toán để giữ chỗ:</p>
            @endif

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Bất động sản:</span>
                    <span class="info-value">{{ $data['property_name'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Căn hộ:</span>
                    <span class="info-value">{{ $data['unit_code'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mã đặt cọc:</span>
                    <span class="info-value">{{ $data['booking_reference'] }}</span>
                </div>
                @if(!($data['is_success'] ?? false))
                    @if(isset($data['invoice_no']))
                    <div class="info-row">
                        <span class="info-label">Số hóa đơn:</span>
                        <span class="info-value">{{ $data['invoice_no'] }}</span>
                    </div>
                    @endif
                @endif
            </div>

            <div class="amount">
                {{ number_format($data['amount'], 0, ',', '.') }} VNĐ
            </div>

            @if($data['is_success'] ?? false)
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Thời gian thanh toán:</span>
                        <span class="info-value">{{ $data['paid_at']->format('d/m/Y H:i') }}</span>
                    </div>
                </div>

                <p>Chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất để hoàn tất thủ tục thuê căn hộ.</p>
            @else
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Hạn thanh toán:</span>
                        <span class="info-value" style="color: #dc3545; font-weight: bold;">
                            {{ $data['payment_due_date']->format('d/m/Y H:i') }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Giữ chỗ đến:</span>
                        <span class="info-value">{{ $data['hold_until']->format('d/m/Y') }}</span>
                    </div>
                </div>

                @if($data['is_reminder'] ?? false)
                    <div class="alert">
                        <p style="margin: 0;">⚠️ Vui lòng thanh toán trước <strong>{{ $data['payment_due_date']->format('d/m/Y H:i') }}</strong> để tránh mất chỗ!</p>
                    </div>
                @endif

                <div class="button-container">
                    <a href="{{ $data['payment_url'] }}" class="button">
                        💳 Thanh toán ngay
                    </a>
                </div>

                <p style="font-size: 12px; color: #666;">
                    Hoặc copy link sau vào trình duyệt:<br>
                    <span class="link">{{ $data['payment_url'] }}</span>
                </p>

                <div class="info-box">
                    <p style="margin: 0; font-size: 14px;">
                        <strong>Lưu ý:</strong> Nếu không thanh toán đúng hạn, đặt cọc của bạn sẽ tự động bị hủy.
                    </p>
                </div>
            @endif

            <p>Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi.</p>

            <p>Trân trọng,<br><strong>{{ $data['organization_name'] ?? 'ZoroRMS Team' }}</strong></p>
        </div>

        <div class="footer">
            <p>Email này được gửi tự động. Vui lòng không trả lời email này.</p>
            <p>&copy; 2024 ZoroRMS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

