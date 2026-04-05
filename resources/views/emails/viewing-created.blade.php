<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận lịch hẹn xem phòng - {{ $data['property_name'] }}</title>
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
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-requested {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .alert {
            background-color: #d1ecf1;
            border-left: 4px solid #0c5460;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Xác nhận lịch hẹn xem phòng</h1>
        </div>

        <div class="content">
            <p>Xin chào <strong>{{ $data['lead_name'] }}</strong>,</p>

            <p>Chúng tôi xin xác nhận lịch hẹn xem phòng của bạn đã được tạo thành công:</p>

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Bất động sản:</span>
                    <span class="info-value"><strong>{{ $data['property_name'] }}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Căn hộ:</span>
                    <span class="info-value">{{ $data['unit_code'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Thời gian hẹn:</span>
                    <span class="info-value">
                        <strong>{{ \Carbon\Carbon::parse($data['schedule_at'])->format('d/m/Y H:i') }}</strong>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Trạng thái:</span>
                    <span class="info-value">
                        <span class="status-badge status-{{ $data['status'] }}">
                            @if($data['status'] === 'requested')
                                Chờ xác nhận
                            @elseif($data['status'] === 'confirmed')
                                Đã xác nhận
                            @else
                                {{ $data['status'] }}
                            @endif
                        </span>
                    </span>
                </div>
                @if(isset($data['agent_name']) && $data['agent_name'])
                <div class="info-row">
                    <span class="info-label">Agent phụ trách:</span>
                    <span class="info-value">{{ $data['agent_name'] }}</span>
                </div>
                @endif
                @if(isset($data['note']) && $data['note'])
                <div class="info-row">
                    <span class="info-label">Ghi chú:</span>
                    <span class="info-value">{{ $data['note'] }}</span>
                </div>
                @endif
            </div>

            @if($data['status'] === 'requested')
            <div class="alert">
                <p style="margin: 0;">
                    ℹ️ Lịch hẹn của bạn đang ở trạng thái <strong>"Chờ xác nhận"</strong>. 
                    Chúng tôi sẽ liên hệ với bạn sớm nhất để xác nhận lịch hẹn.
                </p>
            </div>
            @elseif($data['status'] === 'confirmed')
            <div class="alert">
                <p style="margin: 0;">
                    ✅ Lịch hẹn của bạn đã được <strong>xác nhận</strong>. 
                    Vui lòng có mặt đúng giờ tại địa chỉ trên.
                </p>
            </div>
            @endif

            <p>Nếu bạn có bất kỳ thắc mắc nào hoặc cần thay đổi lịch hẹn, vui lòng liên hệ với chúng tôi.</p>

            <p>Trân trọng,<br><strong>{{ $data['organization_name'] ?? 'ZoroRMS Team' }}</strong></p>
        </div>

        <div class="footer">
            <p>Email này được gửi tự động. Vui lòng không trả lời email này.</p>
            <p>&copy; 2024 ZoroRMS. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

