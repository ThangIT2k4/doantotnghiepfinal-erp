@php
use Illuminate\Support\Facades\Storage;
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán đặt cọc - {{ $bookingDeposit->reference_number }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/image/logo2.svg') }}">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
            overflow-x: hidden;
        }
        .payment-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
        }
        .payment-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .payment-body {
            padding: 30px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
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
        .amount-display {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            text-align: center;
            margin: 30px 0;
        }
        .payment-method {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: block !important;
            width: 100%;
        }
        .payment-method:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        .payment-method.active {
            border-color: #667eea;
            background: #f8f9ff;
        }
        .payment-method i {
            font-size: 32px;
            margin-right: 15px;
        }
        .btn-pay {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: bold;
            border-radius: 10px;
            width: 100%;
        }
        .btn-pay:hover {
            opacity: 0.9;
        }
        .countdown {
            text-align: center;
            background: #fff3cd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .countdown-time {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
        }
        .qr-section {
            display: none;
            padding: 20px;
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            margin-top: 20px;
        }
        .qr-section h5 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        .bank-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .bank-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .bank-item strong {
            display: block;
            color: #333;
            margin-bottom: 5px;
        }
        .bank-item span {
            color: #666;
            font-family: monospace;
            font-size: 0.9em;
        }
        .qr-code-container {
            text-align: center;
            margin-top: 20px;
            display: block;
            min-height: 50px;
        }
        .qr-code {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            background: white;
            display: inline-block;
        }
        .qr-code img {
            max-width: 300px;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        @media (max-width: 768px) {
            .bank-info {
                grid-template-columns: 1fr;
            }
            .qr-code img {
                max-width: 100%;
            }
            .payment-method {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            .payment-body {
                padding: 20px 15px;
            }
        }
        
        @media (max-width: 576px) {
            .payment-card {
                margin: 0 10px;
            }
            .payment-body {
                padding: 15px;
            }
            .amount-display {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-card">
            <div class="payment-header">
                <h1><i class="fas fa-home"></i> Thanh toán đặt cọc</h1>
                <p>Hoàn tất thanh toán để giữ chỗ</p>
            </div>

            <div class="payment-body">
                <!-- Countdown -->
                @if($bookingDeposit->payment_due_date && $bookingDeposit->payment_due_date->isFuture())
                <div class="countdown">
                    <p class="mb-2">⏰ Thời gian còn lại để thanh toán:</p>
                    <div class="countdown-time" id="countdown"></div>
                </div>
                @endif

                <!-- Thông tin đặt cọc -->
                <div class="info-section">
                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Thông tin đặt cọc</h5>
                    
                    <div class="info-row">
                        <strong>Bất động sản:</strong>
                        <span>{{ $bookingDeposit->unit->property->name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <strong>Căn hộ:</strong>
                        <span>{{ $bookingDeposit->unit->code ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <strong>Mã đặt cọc:</strong>
                        <span>{{ $bookingDeposit->reference_number }}</span>
                    </div>
                    <div class="info-row">
                        <strong>Số hóa đơn:</strong>
                        <span>{{ $invoice->invoice_no }}</span>
                    </div>
                </div>

                <!-- Thông tin khách hàng -->
                <div class="info-section">
                    <h5 class="mb-3"><i class="fas fa-user"></i> Thông tin khách hàng</h5>
                    
                    <div class="info-row">
                        <strong>Họ tên:</strong>
                        <span>{{ $tenantInfo['name'] }}</span>
                    </div>
                    <div class="info-row">
                        <strong>Email:</strong>
                        <span>{{ $tenantInfo['email'] }}</span>
                    </div>
                    <div class="info-row">
                        <strong>Số điện thoại:</strong>
                        <span>{{ $tenantInfo['phone'] }}</span>
                    </div>
                </div>

                <!-- Số tiền -->
                <div class="amount-display">
                    {{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ
                </div>

                @if(isset($existingPayment) && $existingPayment)
                    <!-- Đã có payment, redirect đến already-paid -->
                    <script>
                        window.location.href = '{{ route("guest.payment.show", ["invoice" => $invoice->id, "token" => $token]) }}';
                    </script>
                @else
                    <!-- Phương thức thanh toán -->
                    <h5 class="mb-3"><i class="fas fa-credit-card"></i> Chọn phương thức thanh toán</h5>
                    
                    <!-- Tiền mặt -->
                    <div class="payment-method" data-method="cash">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-money-bill-wave text-success"></i>
                            <div>
                                <h6 class="mb-0">Tiền mặt</h6>
                                <small class="text-muted">Thanh toán trực tiếp với agent</small>
                            </div>
                        </div>
                    </div>

                    <!-- Chuyển khoản trực tuyến (Ngân hàng tổ chức) -->
                    @if($organizationBank && $bankConfig)
                    <div class="payment-method" data-method="bank_qr">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-building text-info"></i>
                            <div>
                                <h6 class="mb-0">Chuyển khoản trực tuyến</h6>
                                <small class="text-muted">Chuyển vào ngân hàng tổ chức - Chờ xác nhận thủ công</small>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Chuyển khoản qua SePay (Tự động cập nhật) -->
                    {{-- Debug: canUseSepay = {{ var_export($canUseSepay ?? null, true) }} --}}
                    <script>
                        console.log('Guest payment - Sepay availability:', {
                            canUseSepay: {{ isset($canUseSepay) && $canUseSepay ? 'true' : 'false' }},
                            viewport_width: window.innerWidth,
                            isMobile: window.innerWidth < 768
                        });
                    </script>
                    @if(isset($canUseSepay) && $canUseSepay)
                    <div class="payment-method" data-method="sepay">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-university text-primary"></i>
                            <div>
                                <h6 class="mb-0">Chuyển khoản qua SePay</h6>
                                <small class="text-muted">Tự động cập nhật ngay khi chuyển khoản</small>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Thông báo: Nếu không có phương thức chuyển khoản nào -->
                    @if((!$organizationBank || !$bankConfig) && (!isset($canUseSepay) || !$canUseSepay))
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Lưu ý:</strong> Hiện tại không có phương thức chuyển khoản nào khả dụng. 
                        Vui lòng sử dụng phương thức thanh toán tiền mặt hoặc liên hệ với nhân viên để được hỗ trợ.
                    </div>
                    @endif

                    <!-- Image Upload Section -->
                    <div class="mt-4" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 10px; padding: 20px;">
                        <h6><i class="fas fa-image me-2"></i>Ảnh tài liệu đối chiếu (tùy chọn)</h6>
                        <div class="form-group">
                            <div class="image-upload-area" id="imageUploadArea" style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease;" ondrop="handleDrop(event, 'paymentImage')" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="mb-2">Kéo thả ảnh vào đây hoặc click để chọn</p>
                                <input type="file" 
                                       name="image" 
                                       id="paymentImage" 
                                       class="form-control" 
                                       accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                       style="display: none;">
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('paymentImage').click()">
                                    <i class="fas fa-folder-open me-2"></i>Chọn ảnh
                                </button>
                            </div>
                            <small class="form-text text-muted d-block mt-2">Hỗ trợ: JPEG, PNG, JPG, GIF, WebP (tối đa 5MB). Tải lên ảnh biên lai/chứng từ thanh toán để nhân viên tra soát.</small>
                            <div id="imagePreview" class="mt-3" style="display: none;">
                                <div class="position-relative d-inline-block">
                                    <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 300px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="removeImagePreview()" title="Xóa ảnh" style="margin: 5px;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nút thanh toán -->
                    <button id="btnPay" class="btn btn-primary btn-pay mt-4" disabled>
                        <i class="fas fa-check-circle"></i> Xác nhận thanh toán
                    </button>
                @endif

                <!-- QR Code Section (for SePay) -->
                <div id="qrSection" class="qr-section">
                    <h5><i class="fas fa-info-circle me-2"></i>Thông tin chuyển khoản</h5>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Hướng dẫn:</strong> Quét mã QR hoặc chuyển khoản theo thông tin bên dưới. 
                        Nội dung chuyển khoản phải chính xác để hệ thống tự động xác nhận.
                    </div>
                    
                    <div class="bank-info" id="bankInfo">
                        <!-- Bank info will be loaded here -->
                    </div>
                    
                    <div class="qr-code-container" id="qrCodeContainer">
                        <!-- QR code will be loaded here -->
                    </div>

                    <button id="btnCheckStatus" class="btn btn-outline-primary mt-3">
                        <i class="fas fa-sync-alt"></i> Kiểm tra trạng thái thanh toán
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery & Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/notifications.js') }}"></script>
    
    <script>
        $(document).ready(function() {
            // Debug: Log payment methods on load
            console.log('Guest payment page loaded');
            const paymentMethods = document.querySelectorAll('.payment-method');
            console.log('Payment methods found:', paymentMethods.length);
            paymentMethods.forEach((method, index) => {
                const methodType = method.getAttribute('data-method');
                const isVisible = window.getComputedStyle(method).display !== 'none';
                const opacity = window.getComputedStyle(method).opacity;
                const visibility = window.getComputedStyle(method).visibility;
                console.log(`Method ${index + 1}: ${methodType}, Display: ${isVisible}, Opacity: ${opacity}, Visibility: ${visibility}`);
            });
            
            // Display error notification if any
            @if(session('error'))
                if (typeof Notify !== 'undefined') {
                    Notify.error('{{ session('error') }}', 'Lỗi!');
                }
            @endif
            
            @if(session('warning'))
                if (typeof Notify !== 'undefined') {
                    Notify.warning('{{ session('warning') }}', 'Cảnh báo!');
                }
            @endif
            
            @if(session('success'))
                if (typeof Notify !== 'undefined') {
                    Notify.success('{{ session('success') }}', 'Thành công!');
                }
            @endif
            
            let selectedMethod = null;
            let paymentId = null;
            const token = '{{ $token }}';
            const invoiceId = {{ $invoice->id }};

            // Countdown timer
            @if($bookingDeposit->payment_due_date && $bookingDeposit->payment_due_date->isFuture())
            const countdownDate = new Date("{{ $bookingDeposit->payment_due_date->toIso8601String() }}").getTime();
            const countdownElement = document.getElementById("countdown");

            const x = setInterval(function() {
                const now = new Date().getTime();
                const distance = countdownDate - now;

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                countdownElement.innerHTML = days + "d " + hours + "h " + minutes + "m " + seconds + "s ";

                if (distance < 0) {
                    clearInterval(x);
                    countdownElement.innerHTML = "ĐÃ HẾT HẠN";
                    $('#btnPay').prop('disabled', true);
                }
            }, 1000);
            @endif

            // Bank config from PHP (public, no API call needed)
            const bankConfig = @json($bankConfig ?? []);
            const invoiceAmount = {{ $invoice->total_amount }};
            const invoiceNo = '{{ $invoice->invoice_no }}';

            // Select payment method
            $('.payment-method').click(function() {
                $('.payment-method').removeClass('active');
                $(this).addClass('active');
                selectedMethod = $(this).data('method');
                $('#btnPay').prop('disabled', false);
                
                // Nếu chọn bank_qr hoặc sepay, hiển thị thông tin ngân hàng và QR preview ngay
                if (selectedMethod === 'bank_qr') {
                    // Chuyển khoản trực tuyến - dùng ngân hàng tổ chức
                    displayBankInfoPreview(bankConfig);
                    createQRPreview(bankConfig);
                    $('#qrSection').show();
                } else if (selectedMethod === 'sepay') {
                    // Chuyển khoản qua SePay - dùng ngân hàng SaaS
                    const sepayConfig = @json($sepayConfig ?? []);
                    console.log('Sepay config:', sepayConfig);
                    if (sepayConfig && sepayConfig.account_number && sepayConfig.bank_name) {
                        displayBankInfoPreview(sepayConfig);
                        createQRPreview(sepayConfig);
                        $('#qrSection').show();
                    } else {
                        console.error('Invalid sepay config:', sepayConfig);
                        if (typeof Notify !== 'undefined') {
                            Notify.warning('Thông tin ngân hàng SePay chưa được cấu hình. Vui lòng liên hệ hỗ trợ.', 'Cảnh báo!');
                        } else {
                            alert('Thông tin ngân hàng SePay chưa được cấu hình. Vui lòng liên hệ hỗ trợ.');
                        }
                    }
                } else {
                    $('#qrSection').hide();
                }
            });

            // Process payment
            $('#btnPay').click(function() {
                if (!selectedMethod) {
                    if (typeof Notify !== 'undefined') {
                        Notify.warning('Vui lòng chọn phương thức thanh toán', 'Cảnh báo!');
                    } else {
                        alert('Vui lòng chọn phương thức thanh toán');
                    }
                    return;
                }

                // Hiển thị popup xác nhận
                const confirmMessage = 'Bạn có chắc chắn muốn xác nhận thanh toán?\n\n' +
                    'Số tiền: ' + new Intl.NumberFormat('vi-VN').format(invoiceAmount) + ' VNĐ\n' +
                    'Phương thức: ' + (selectedMethod === 'cash' ? 'Tiền mặt' : 'Chuyển khoản ngân hàng');
                
                if (typeof Notify !== 'undefined' && Notify.confirm) {
                    Notify.confirm({
                        title: 'Xác nhận thanh toán',
                        message: confirmMessage,
                        confirmText: 'Xác nhận',
                        cancelText: 'Hủy',
                        onConfirm: function() {
                            processPayment();
                        }
                    });
                } else {
                    if (!confirm(confirmMessage)) {
                        return;
                    }
                    processPayment();
                }
            });
            
            // Function to process payment
            function processPayment() {

                const btn = $('#btnPay');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');

                let url = '';
                if (selectedMethod === 'cash') {
                    url = `/guest/payment/${invoiceId}/cash`;
                } else if (selectedMethod === 'bank_qr') {
                    url = `/guest/payment/${invoiceId}/bank_qr`;
                } else if (selectedMethod === 'sepay') {
                    url = `/guest/payment/${invoiceId}/sepay`;
                }

                // Create FormData for file upload
                const formData = new FormData();
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                formData.append('token', token);
                const imageFile = document.getElementById('paymentImage').files[0];
                if (imageFile) {
                    formData.append('image', imageFile);
                }

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            paymentId = response.payment_id;

                            if (selectedMethod === 'cash') {
                                if (typeof Notify !== 'undefined') {
                                    Notify.success('Thanh toán đã được ghi nhận! Vui lòng chờ xác nhận.', 'Thành công!');
                                }
                                // Reload trang để cập nhật trạng thái
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            } else if (selectedMethod === 'bank_qr' || selectedMethod === 'sepay') {
                                // Validate response data
                                if (!response.qr_url) {
                                    console.error('QR URL missing in response:', response);
                                    if (typeof Notify !== 'undefined') {
                                        Notify.error('Lỗi: Không có URL mã QR. Vui lòng thử lại.', 'Lỗi!');
                                    } else {
                                        alert('Lỗi: Không có URL mã QR. Vui lòng thử lại.');
                                    }
                                    btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Xác nhận thanh toán');
                                    return;
                                }
                                
                                if (!response.bank_info) {
                                    console.error('Bank info missing in response:', response);
                                    if (typeof Notify !== 'undefined') {
                                        Notify.error('Lỗi: Thiếu thông tin ngân hàng. Vui lòng thử lại.', 'Lỗi!');
                                    } else {
                                        alert('Lỗi: Thiếu thông tin ngân hàng. Vui lòng thử lại.');
                                    }
                                    btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Xác nhận thanh toán');
                                    return;
                                }
                                
                                console.log('QR URL:', response.qr_url);
                                console.log('Bank Info:', response.bank_info);
                                
                                // Display bank info in grid layout (similar to methods.blade.php)
                                displayBankInfo(response.bank_info);
                                
                                // Display QR code
                                displayQRCode(response.qr_url, response.bank_info);
                                
                                // Show QR section
                                $('#qrSection').show();
                                
                                if (typeof Notify !== 'undefined') {
                                    Notify.success('Mã QR đã được tạo. Vui lòng quét mã để thanh toán.', 'Thành công!');
                                }
                                
                                // Bắt đầu kiểm tra trạng thái thanh toán tự động
                                autoCheckPaymentStatus();
                            }
                        } else {
                            if (typeof Notify !== 'undefined') {
                                Notify.error(response.message || 'Có lỗi xảy ra', 'Lỗi!');
                            } else {
                                alert(response.message);
                            }
                            btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Xác nhận thanh toán');
                        }
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON?.message || 'Có lỗi xảy ra. Vui lòng thử lại.';
                        if (typeof Notify !== 'undefined') {
                            Notify.error(errorMessage, 'Lỗi!');
                        } else {
                            alert('Có lỗi xảy ra: ' + errorMessage);
                        }
                        btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Xác nhận thanh toán');
                    }
                });
            }

            // Check payment status
            let statusCheckInterval = null;
            function autoCheckPaymentStatus() {
                if (statusCheckInterval) {
                    clearInterval(statusCheckInterval);
                }

                statusCheckInterval = setInterval(function() {
                    checkPaymentStatus();
                }, 10000); // Check every 10 seconds
            }

            function checkPaymentStatus() {
                if (!paymentId) return;

                $.ajax({
                    url: `/guest/payment/${invoiceId}/status/${paymentId}`,
                    method: 'GET',
                    data: { token: token },
                    success: function(response) {
                        if (response.success) {
                            if (response.payment_status === 'success' || response.invoice_status === 'paid') {
                                clearInterval(statusCheckInterval);
                                
                                // Hiển thị thông báo thành công
                                $('.payment-body').html(`
                                    <div class="alert alert-success text-center">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <h4>Thanh toán thành công!</h4>
                                        <p>Hóa đơn của bạn đã được thanh toán thành công.</p>
                                        <p class="mb-0"><small>Đang chuyển hướng...</small></p>
                                    </div>
                                `);
                                
                                // Redirect về trang already-paid sau 2 giây
                                setTimeout(function() {
                                    window.location.href = window.location.href;
                                }, 2000);
                            } else if (response.payment_status === 'pending') {
                                // Payment vẫn đang pending, không làm gì
                                console.log('Payment still pending...');
                            }
                        }
                    }
                });
            }

            $('#btnCheckStatus').click(function() {
                checkPaymentStatus();
            });

            // Display bank info preview (when selecting Sepay method)
            function displayBankInfoPreview(bankConfig) {
                const bankInfoContainer = $('#bankInfo');
                // Loại bỏ dấu gạch khỏi mã hóa đơn
                const cleanInvoiceNo = invoiceNo.replace(/-/g, '');
                const content = cleanInvoiceNo;
                
                bankInfoContainer.html(`
                    <div class="bank-item">
                        <strong>Ngân hàng:</strong>
                        <span>${bankConfig.bank_name || 'N/A'}</span>
                    </div>
                    <div class="bank-item">
                        <strong>Số tài khoản:</strong>
                        <span>${bankConfig.account_number || 'N/A'}</span>
                    </div>
                    <div class="bank-item">
                        <strong>Tên chủ tài khoản:</strong>
                        <span>${bankConfig.account_name || 'N/A'}</span>
                    </div>
                    <div class="bank-item">
                        <strong>Số tiền:</strong>
                        <span>${new Intl.NumberFormat('vi-VN').format(invoiceAmount)} VNĐ</span>
                    </div>
                    <div class="bank-item">
                        <strong>Nội dung:</strong>
                        <span>${content}</span>
                    </div>
                `);
            }

            // Create QR preview (when selecting Sepay method)
            function createQRPreview(bankConfig) {
                // Validate bank config
                if (!bankConfig || !bankConfig.account_number || !bankConfig.bank_name) {
                    console.error('Invalid bank config for QR preview:', bankConfig);
                    const qrContainer = $('#qrCodeContainer');
                    if (qrContainer.length) {
                        qrContainer.html(`
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Không thể tạo QR Code</h6>
                                <p>Thông tin ngân hàng chưa đầy đủ. Vui lòng thử lại sau khi xác nhận thanh toán.</p>
                            </div>
                        `);
                    }
                    return;
                }
                
                // Loại bỏ dấu gạch khỏi mã hóa đơn
                const cleanInvoiceNo = invoiceNo.replace(/-/g, '');
                const content = cleanInvoiceNo;
                
                // Tạo URL QR code SePay
                const qrUrl = `https://qr.sepay.vn/img?${new URLSearchParams({
                    acc: bankConfig.account_number,
                    bank: bankConfig.bank_name,
                    amount: invoiceAmount,
                    des: content
                })}`;
                
                console.log('Creating QR preview:', {
                    amount: invoiceAmount,
                    content: content,
                    qrUrl: qrUrl,
                    bankConfig: bankConfig
                });
                
                // Hiển thị QR code preview
                const qrContainer = $('#qrCodeContainer');
                if (qrContainer.length) {
                    qrContainer.html(`
                        <div class="qr-code">
                            <h6><i class="fas fa-qrcode me-2"></i>Mã QR Thanh Toán</h6>
                            <img src="${qrUrl}" alt="QR Code SePay" style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 8px;" onerror="window.showQRError()" onload="console.log('QR preview loaded successfully:', this.src)">
                            <p class="mt-2"><small>Quét mã QR bằng app ngân hàng để chuyển khoản</small></p>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="window.downloadQRCode('${qrUrl}')">
                                    <i class="fas fa-download me-1"></i>Tải QR Code
                                </button>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <strong>Số tiền:</strong> ${invoiceAmount.toLocaleString('vi-VN')} VNĐ<br>
                                    <strong>Nội dung:</strong> ${content}
                                </small>
                            </div>
                        </div>
                    `);
                    qrContainer.show();
                    console.log('QR preview displayed successfully');
                }
            }

            // Display bank info in grid layout (after payment is processed)
            function displayBankInfo(bankInfo) {
                const bankInfoContainer = $('#bankInfo');
                const amount = bankInfo.amount || invoiceAmount;
                // Loại bỏ dấu gạch khỏi mã hóa đơn
                const cleanInvoiceNo = invoiceNo.replace(/-/g, '');
                const content = bankInfo.content || cleanInvoiceNo;
                
                bankInfoContainer.html(`
                    <div class="bank-item">
                        <strong>Ngân hàng:</strong>
                        <span>${bankInfo.bank_name || 'N/A'}</span>
                    </div>
                    <div class="bank-item">
                        <strong>Số tài khoản:</strong>
                        <span>${bankInfo.account_number || 'N/A'}</span>
                    </div>
                    <div class="bank-item">
                        <strong>Tên chủ tài khoản:</strong>
                        <span>${bankInfo.account_name || 'N/A'}</span>
                    </div>
                    <div class="bank-item">
                        <strong>Số tiền:</strong>
                        <span>${new Intl.NumberFormat('vi-VN').format(amount)} VNĐ</span>
                    </div>
                    <div class="bank-item">
                        <strong>Nội dung:</strong>
                        <span>${content}</span>
                    </div>
                `);
            }

            // Display QR code (after payment is processed)
            function displayQRCode(qrUrl, bankInfo) {
                const qrContainer = $('#qrCodeContainer');
                const amount = bankInfo.amount || invoiceAmount;
                // Loại bỏ dấu gạch khỏi mã hóa đơn
                const cleanInvoiceNo = invoiceNo.replace(/-/g, '');
                const content = bankInfo.content || cleanInvoiceNo;
                
                qrContainer.html(`
                    <div class="qr-code">
                        <h6><i class="fas fa-qrcode me-2"></i>Mã QR Thanh Toán</h6>
                        <img src="${qrUrl}" alt="QR Code SePay" onerror="window.showQRError()" onload="console.log('QR image loaded successfully:', this.src)">
                        <p class="mt-2"><small>Quét mã QR bằng app ngân hàng để chuyển khoản</small></p>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="window.downloadQRCode('${qrUrl}')">
                                <i class="fas fa-download me-1"></i>Tải QR Code
                            </button>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <strong>Số tiền:</strong> ${new Intl.NumberFormat('vi-VN').format(amount)} VNĐ<br>
                                <strong>Nội dung:</strong> ${content}
                            </small>
                        </div>
                    </div>
                `);
                qrContainer.show();
            }

            // Make functions global for onclick handlers
            window.downloadQRCode = function(qrUrl) {
                // Tạo link tải QR code
                const link = document.createElement('a');
                link.href = qrUrl;
                link.download = 'qr-code-sepay.png';
                link.target = '_blank';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            };

            window.showQRError = function() {
                console.error('Failed to load QR image');
                const qrContainer = $('#qrCodeContainer');
                qrContainer.html(`
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Không thể tải QR Code</h6>
                        <p>Vui lòng thử lại hoặc liên hệ hỗ trợ.</p>
                        <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-refresh me-1"></i>Thử lại
                        </button>
                    </div>
                `);
            };

            // Image preview
            $('#paymentImage').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Check file size (5MB limit)
                    if (file.size > 5 * 1024 * 1024) {
                        if (typeof Notify !== 'undefined') {
                            Notify.warning('File quá lớn (>5MB). Vui lòng chọn file nhỏ hơn.', 'Cảnh báo!');
                        } else {
                            alert('File quá lớn (>5MB). Vui lòng chọn file nhỏ hơn.');
                        }
                        $(this).val('');
                        $('#imagePreview').hide();
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewImg').attr('src', e.target.result);
                        $('#imagePreview').show();
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#imagePreview').hide();
                }
            });
            
            // Drag and drop functions
            window.handleDragOver = function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.currentTarget.style.borderColor = '#007bff';
                e.currentTarget.style.backgroundColor = '#f8f9fa';
            };
            
            window.handleDragLeave = function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.currentTarget.style.borderColor = '#dee2e6';
                e.currentTarget.style.backgroundColor = 'transparent';
            };
            
            window.handleDrop = function(e, inputId) {
                e.preventDefault();
                e.stopPropagation();
                e.currentTarget.style.borderColor = '#dee2e6';
                e.currentTarget.style.backgroundColor = 'transparent';
                
                const files = e.dataTransfer.files;
                const input = document.getElementById(inputId);
                
                if (files.length > 0 && input) {
                    const file = files[0];
                    if (file.type.startsWith('image/')) {
                        // Check file size (5MB limit)
                        if (file.size > 5 * 1024 * 1024) {
                            if (typeof Notify !== 'undefined') {
                                Notify.warning('File quá lớn (>5MB). Vui lòng chọn file nhỏ hơn.', 'Cảnh báo!');
                            } else {
                                alert('File quá lớn (>5MB). Vui lòng chọn file nhỏ hơn.');
                            }
                            return;
                        }
                        
                        // Create a new FileList-like object
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;
                        
                        // Trigger change event
                        $(input).trigger('change');
                    } else {
                        if (typeof Notify !== 'undefined') {
                            Notify.warning('File không phải là hình ảnh. Vui lòng chọn file hình ảnh.', 'Cảnh báo!');
                        } else {
                            alert('File không phải là hình ảnh. Vui lòng chọn file hình ảnh.');
                        }
                    }
                }
            };
            
            window.removeImagePreview = function() {
                $('#paymentImage').val('');
                $('#imagePreview').hide();
            };
        });
    </script>
</body>
</html>

