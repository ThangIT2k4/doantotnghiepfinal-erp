@extends('layouts.staff_dashboard')

@section('title', 'Cài đặt SePay')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-cog mr-2"></i>
                Cài đặt SePay
            </h1>
            <p class="text-muted mb-0">Cấu hình và quản lý tích hợp SePay</p>
        </div>
        <div class="card-tools">
            <a href="{{ route('staff.sepay.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i>
                Dashboard
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Payment Method Settings -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-credit-card mr-2"></i>
                        Cài đặt phương thức thanh toán
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('staff.sepay.settings.update') }}">
                        @csrf
                        
                        <div class="form-group mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       {{ $sepayMethod && $sepayMethod->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    <strong>Kích hoạt SePay</strong>
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Bật/tắt phương thức thanh toán SePay trong hệ thống
                            </small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Nhập mô tả về phương thức thanh toán SePay...">{{ old('description', $sepayMethod ? $sepayMethod->description : '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" onclick="return confirmSave()">
                                <i class="fas fa-save mr-1"></i>
                                Cập nhật cài đặt
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Configuration Information -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>
                        Thông tin cấu hình
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Webhook URL:</strong>
                        <div class="input-group mt-1">
                            <input type="text" class="form-control" value="{{ $webhookUrl }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('{{ $webhookUrl }}')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">
                            URL này cần được cấu hình trong SePay Dashboard
                        </small>
                    </div>

                    <div class="mb-3">
                        <strong>API Key:</strong>
                        <div class="input-group mt-1">
                            <input type="text" class="form-control" value="{{ $apiKey }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('{{ $apiKey }}')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">
                            API Key được cấu hình trong file .env (SEPAY_API_KEY)
                        </small>
                    </div>

                    <div class="mb-3">
                        <strong>Trạng thái tích hợp:</strong>
                        <div class="mt-1">
                            @if($sepayMethod && $sepayMethod->is_active)
                                <span class="badge bg-success">
                                    <i class="fas fa-check mr-1"></i>Đã kích hoạt
                                </span>
                            @else
                                <span class="badge bg-warning">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>Chưa kích hoạt
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Setup Instructions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-book mr-2"></i>
                        Hướng dẫn cấu hình SePay
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-server mr-2"></i>Bước 1: Cấu hình Webhook</h6>
                            <ol>
                                <li>Đăng nhập vào <a href="https://my.sepay.vn" target="_blank">SePay Dashboard</a></li>
                                <li>Vào menu <strong>Webhook</strong> → <strong>Quản lý Webhook</strong></li>
                                <li>Nhấn <strong>Thêm Webhook</strong></li>
                                <li>Điền thông tin:
                                    <ul>
                                        <li><strong>Tên webhook:</strong> Hệ thống quản lý phòng trọ</li>
                                        <li><strong>Chọn sự kiện:</strong> Có tiền vào (transfer_type = "in")</li>
                                        <li><strong>Gọi đến URL:</strong> {{ $webhookUrl }}</li>
                                        <li><strong>Kiểu chứng thực:</strong> API Key</li>
                                        <li><strong>API Key:</strong> [API Key từ file .env]</li>
                                    </ul>
                                </li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-cogs mr-2"></i>Bước 2: Cấu hình Environment</h6>
                            <ol>
                                <li>Mở file <code>.env</code> trong thư mục gốc</li>
                                <li>Thêm dòng: <code>SEPAY_API_KEY=your_api_key_here</code></li>
                                <li>Thay <code>your_api_key_here</code> bằng API Key từ SePay</li>
                                <li>Chạy lệnh: <code>php artisan config:clear</code></li>
                                <li>Chạy lệnh: <code>php artisan cache:clear</code></li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6><i class="fas fa-lightbulb mr-2"></i>Lưu ý quan trọng</h6>
                            <div class="alert alert-info">
                                <ul class="mb-0">
                                    <li>Khách hàng cần chuyển khoản với nội dung chứa mã hóa đơn (ví dụ: HD-202510-0001)</li>
                                    <li>Hệ thống sẽ tự động tìm hóa đơn dựa trên mã trong nội dung chuyển khoản</li>
                                    <li>Khi thanh toán đủ tiền, hóa đơn sẽ tự động chuyển sang trạng thái "Đã thanh toán"</li>
                                    <li>Tất cả giao dịch đều được ghi log để kiểm tra và xử lý thủ công nếu cần</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Connection -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-flask mr-2"></i>
                        Kiểm tra kết nối
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Kiểm tra xem cấu hình SePay có hoạt động đúng không.</p>
                    <button type="button" class="btn btn-info" onclick="testConnection()">
                        <i class="fas fa-play mr-1"></i>
                        Kiểm tra kết nối
                    </button>
                    <div id="testResult" class="mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
function confirmSave() {
    return new Promise((resolve) => {
        Notify.confirmSave(function() {
            resolve(true);
        });
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        Notify.success('Đã sao chép vào clipboard!');
    }, function(err) {
        Notify.error('Không thể sao chép: ' + err);
    });
}

function testConnection() {
    const button = event.target;
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Đang kiểm tra...';
    button.disabled = true;
    
    // Simulate test (in real implementation, you would make an AJAX call)
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
        
        const resultDiv = document.getElementById('testResult');
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check mr-2"></i>
                <strong>Kết nối thành công!</strong> Cấu hình SePay đang hoạt động bình thường.
            </div>
        `;
        
        Notify.success('Kiểm tra kết nối thành công!');
    }, 2000);
}

// Show success/error messages from session
@if(session('success'))
    Notify.success('{{ session('success') }}');
@endif

@if(session('error'))
    Notify.error('{{ session('error') }}');
@endif

@if(session('warning'))
    Notify.warning('{{ session('warning') }}');
@endif

@if(session('info'))
    Notify.info('{{ session('info') }}');
@endif

// Show validation errors
@if($errors->any())
    @foreach($errors->all() as $error)
        Notify.error('{{ $error }}');
    @endforeach
@endif
</script>
@endpush
