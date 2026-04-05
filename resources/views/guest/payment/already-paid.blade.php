@php
use Illuminate\Support\Facades\Storage;
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@if(isset($existingPayment) && $existingPayment && $existingPayment->status === 'pending') Chờ giao dịch @else Đã thanh toán @endif - {{ $invoice->invoice_no }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/image/logo2.svg') }}">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .success-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 500px;
            text-align: center;
            padding: 50px 30px;
        }
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
            animation: checkmark 0.8s ease-in-out;
        }
        @keyframes checkmark {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        h1 {
            color: #333;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="success-card">
        @if(isset($existingPayment) && $existingPayment && $existingPayment->status === 'pending')
            <div class="success-icon" style="color: #ffc107;">
                <i class="fas fa-clock"></i>
            </div>
            <h1>Chờ giao dịch</h1>
            <p class="text-muted">Yêu cầu thanh toán của bạn đã được gửi và đang chờ xác nhận từ nhân viên.</p>

            <div class="info-box">
                <div class="info-row">
                    <strong>Số hóa đơn:</strong>
                    <span>{{ $invoice->invoice_no }}</span>
                </div>
                <div class="info-row">
                    <strong>Số tiền:</strong>
                    <span class="fw-bold">{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</span>
                </div>
                @if($existingPayment->method)
                <div class="info-row">
                    <strong>Phương thức:</strong>
                    <span>{{ $existingPayment->method->name }}</span>
                </div>
                @endif
                <div class="info-row">
                    <strong>Trạng thái:</strong>
                    <span class="badge bg-warning">Chờ giao dịch</span>
                </div>
                @php
                    $paymentImage = $existingPayment->documents()
                        ->where('document_type', 'image')
                        ->orderBy('sort_order')
                        ->orderBy('created_at')
                        ->first();
                @endphp
                @if($paymentImage)
                <div class="info-row">
                    <strong>Ảnh đối chiếu:</strong>
                    <span>
                        @php
                            // Get raw file_url (relative path) from database, not through accessor
                            $rawFileUrl = $paymentImage->getRawOriginal('file_url');
                            // Build correct URL
                            $imageUrl = str_starts_with($rawFileUrl, 'http://') || str_starts_with($rawFileUrl, 'https://') 
                                ? $rawFileUrl 
                                : asset('storage/' . ltrim($rawFileUrl, '/'));
                        @endphp
                        <a href="{{ $imageUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i> Xem ảnh
                        </a>
                    </span>
                </div>
                @endif
            </div>

            <p class="mt-4">
                <i class="fas fa-info-circle text-warning"></i> 
                Vui lòng chờ nhân viên xác nhận thanh toán. Chúng tôi sẽ liên hệ với bạn sớm nhất.
            </p>

            <!-- Auto check payment status -->
            <script>
                let pendingPaymentId = {{ $existingPayment->id }};
                let pendingCheckInterval = setInterval(function() {
                    $.ajax({
                        url: `/guest/payment/{{ $invoice->id }}/status/${pendingPaymentId}`,
                        method: 'GET',
                        data: { token: '{{ request()->query("token") }}' },
                        success: function(response) {
                            if (response.success) {
                                if (response.payment_status === 'success' || response.invoice_status === 'paid') {
                                    clearInterval(pendingCheckInterval);
                                    // Reload để hiển thị trạng thái đã thanh toán
                                    window.location.reload();
                                }
                            }
                        }
                    });
                }, 10000); // Check every 10 seconds
            </script>
        @else
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Đã thanh toán!</h1>
            <p class="text-muted">Hóa đơn này đã được thanh toán thành công.</p>

            <div class="info-box">
                <div class="info-row">
                    <strong>Số hóa đơn:</strong>
                    <span>{{ $invoice->invoice_no }}</span>
                </div>
                <div class="info-row">
                    <strong>Số tiền:</strong>
                    <span class="text-success fw-bold">{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</span>
                </div>
                <div class="info-row">
                    <strong>Trạng thái:</strong>
                    <span class="badge bg-success">Đã thanh toán</span>
                </div>
            </div>

            <p class="mt-4">
                <i class="fas fa-info-circle text-info"></i> 
                Cảm ơn bạn đã hoàn tất thanh toán. Chúng tôi sẽ liên hệ với bạn sớm nhất.
            </p>
        @endif
    </div>
</body>
</html>

