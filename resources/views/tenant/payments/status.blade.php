@extends('layouts.app')

@section('title', 'Trạng thái thanh toán')

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/payments.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
/* Payment Status Container */
.payment-status-container {
    background: linear-gradient(to bottom, #F0F4FF 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

/* Modern Header with Blue Gradient Theme */
.payment-header-modern {
    background: linear-gradient(135deg, #1E4FC8 0%, #2766ec 50%, #4A85F0 100%);
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(39, 102, 236, 0.3);
    color: white;
    position: relative;
    overflow: hidden;
}

.payment-header-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 50%, transparent 100%);
    pointer-events: none;
}

.payment-header-modern .payment-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #FFFFFF;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    position: relative;
    z-index: 1;
}

.payment-header-modern .payment-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
    position: relative;
    z-index: 1;
}

.payment-header-modern .payment-number {
    background: rgba(255, 255, 255, 0.25);
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-weight: 600;
    backdrop-filter: blur(10px);
    color: #FFFFFF;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.payment-header-modern .payment-status-badge {
    padding: 0.5rem 1.2rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    color: #FFFFFF;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* Status Card */
.status-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
}

.status-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid var(--blue-border);
}

.status-icon {
    font-size: 4em;
    margin-bottom: 1rem;
}

.status-icon.pending {
    color: #ffc107;
}

.status-icon.success {
    color: #28a745;
}

.status-icon.failed {
    color: #dc3545;
}

.status-icon.refunded {
    color: #17a2b8;
}

.status-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--blue-primary);
}

.status-subtitle {
    color: #666;
    font-size: 1.1rem;
}

.payment-details {
    background: var(--blue-bg-light);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.payment-details h5 {
    color: var(--blue-primary);
    font-weight: 700;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--blue-border);
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: #666;
}

.detail-value {
    color: #333;
    font-weight: 500;
}

.invoice-info {
    background: var(--blue-bg-light);
    border: 1px solid var(--blue-border);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.invoice-info h5 {
    color: var(--blue-primary);
    font-weight: 700;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    justify-content: center;
    margin-top: 2rem;
}

.btn-action {
    border-radius: 12px;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.2);
}

.refresh-info {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1.5rem;
    text-align: center;
    color: #856404;
}

.refresh-info i {
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.sepay-instructions {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1.5rem;
}

.sepay-instructions h6 {
    color: #0c5460;
    font-weight: 700;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sepay-instructions ul {
    margin: 0;
    padding-left: 1.5rem;
}

.sepay-instructions li {
    margin-bottom: 0.75rem;
    color: #0c5460;
}

.qr-code-container {
    margin-top: 1rem;
}

.qr-code-container img {
    max-width: 250px;
    height: auto;
    border: 2px solid var(--blue-border);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .payment-header-modern {
        padding: 1.5rem;
    }
    
    .payment-header-modern .payment-title {
        font-size: 1.5rem;
    }
    
    .status-card {
        padding: 1.5rem;
    }
    
    .actions {
        flex-direction: column;
    }
    
    .btn-action {
        width: 100%;
    }
}
</style>
@endpush

@section('content')
<div class="page-container-blue">
    <div class="container">
        <!-- Modern Payment Header -->
        <div class="payment-header-modern">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3" style="position: relative; z-index: 1;">
                <ol class="breadcrumb mb-0" style="background: rgba(255, 255, 255, 0.2); padding: 0.75rem 1rem; border-radius: 10px; backdrop-filter: blur(10px);">
                    <li class="breadcrumb-item">
                        <a href="{{ route('tenant.dashboard') }}" style="color: rgba(255, 255, 255, 0.9); text-decoration: none;">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('tenant.payments.index') }}" style="color: rgba(255, 255, 255, 0.9); text-decoration: none;">
                            <i class="fas fa-credit-card me-1"></i>Thanh toán
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: rgba(255, 255, 255, 1);">
                        <i class="fas fa-info-circle me-1"></i>Chi tiết
                    </li>
                </ol>
            </nav>
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="payment-title-section">
                        <h1 class="payment-title">
                            @if($payment->invoice && $payment->invoice->lease && $payment->invoice->lease->unit && $payment->invoice->lease->unit->property)
                                {{ $payment->invoice->lease->unit->property->name }}
                            @else
                                Thanh toán #{{ $payment->id }}
                            @endif
                        </h1>
                        <div class="payment-meta">
                            <span class="payment-number">Mã thanh toán: #{{ $payment->id }}</span>
                            @php
                                $statusConfig = [
                                    'pending' => ['icon' => 'fas fa-clock', 'text' => 'Đang chờ'],
                                    'success' => ['icon' => 'fas fa-check-circle', 'text' => 'Thành công'],
                                    'failed' => ['icon' => 'fas fa-times-circle', 'text' => 'Thất bại'],
                                    'refunded' => ['icon' => 'fas fa-undo', 'text' => 'Hoàn tiền']
                                ];
                                $status = $statusConfig[$payment->status] ?? $statusConfig['pending'];
                            @endphp
                            <span class="payment-status-badge">
                                <i class="{{ $status['icon'] }}"></i> {{ $status['text'] }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="payment-actions" style="position: relative; z-index: 1;">
                        <div class="payment-amount-display" style="margin-bottom: 1rem;">
                            <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 0.5rem;">Số tiền</div>
                            <div style="font-size: 2rem; font-weight: 700; text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);">
                                {{ number_format($payment->amount) }} VNĐ
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                            @if($payment->status === 'pending')
                                <button class="btn" onclick="refreshStatus()" style="background: rgba(255, 255, 255, 0.25); color: white; border: 1px solid rgba(255, 255, 255, 0.3); font-weight: 600; padding: 0.75rem 1.5rem; backdrop-filter: blur(10px);">
                                    <i class="fas fa-sync-alt me-1"></i>Làm mới
                                </button>
                            @endif
                            
                            @if($payment->invoice)
                                <a href="{{ route('tenant.invoices.show', $payment->invoice->id) }}" class="btn" style="background: rgba(255, 255, 255, 0.25); color: white; border: 1px solid rgba(255, 255, 255, 0.3); font-weight: 600; padding: 0.75rem 1.5rem; backdrop-filter: blur(10px); text-decoration: none;">
                                    <i class="fas fa-file-invoice me-1"></i>Xem hóa đơn
                                </a>
                            @endif
                            
                            <a href="{{ route('tenant.payments.index') }}" class="btn" style="background: rgba(255, 255, 255, 0.25); color: white; border: 1px solid rgba(255, 255, 255, 0.3); font-weight: 600; padding: 0.75rem 1.5rem; backdrop-filter: blur(10px); text-decoration: none;">
                                <i class="fas fa-arrow-left me-1"></i>Quay lại
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Card -->
        <div class="status-card">
            <div class="status-header">
                @php
                    $statusConfig = [
                        'pending' => [
                            'icon' => 'fas fa-clock',
                            'title' => 'Đang chờ xử lý',
                            'subtitle' => 'Thanh toán đang được xử lý',
                            'class' => 'pending'
                        ],
                        'success' => [
                            'icon' => 'fas fa-check-circle',
                            'title' => 'Thanh toán thành công',
                            'subtitle' => 'Giao dịch đã được xác nhận',
                            'class' => 'success'
                        ],
                        'failed' => [
                            'icon' => 'fas fa-times-circle',
                            'title' => 'Thanh toán thất bại',
                            'subtitle' => 'Giao dịch không thành công',
                            'class' => 'failed'
                        ],
                        'refunded' => [
                            'icon' => 'fas fa-undo',
                            'title' => 'Đã hoàn tiền',
                            'subtitle' => 'Giao dịch đã được hoàn tiền',
                            'class' => 'refunded'
                        ]
                    ];
                    $config = $statusConfig[$payment->status] ?? $statusConfig['pending'];
                @endphp
                
                <div class="status-icon {{ $config['class'] }}">
                    <i class="{{ $config['icon'] }}"></i>
                </div>
                <div class="status-title">{{ $config['title'] }}</div>
                <div class="status-subtitle">{{ $config['subtitle'] }}</div>
            </div>

            <!-- Payment Details -->
            <div class="payment-details">
                <h5><i class="fas fa-credit-card"></i>Chi tiết thanh toán</h5>
                <div class="detail-item">
                    <span class="detail-label">Mã thanh toán:</span>
                    <span class="detail-value">#{{ $payment->id }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Phương thức:</span>
                    <span class="detail-value">
                        @if($payment->method)
                            @if($payment->method->key_code === 'cash')
                                <i class="fas fa-money-bill-wave me-1"></i>Tiền mặt
                            @elseif($payment->method->key_code === 'sepay')
                                <i class="fas fa-qrcode me-1"></i>Sepay
                            @else
                                <i class="fas fa-university me-1"></i>{{ $payment->method->name }}
                            @endif
                        @else
                            Không xác định
                        @endif
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Số tiền:</span>
                    <span class="detail-value"><strong style="color: var(--blue-primary); font-size: 1.1rem;">{{ number_format($payment->amount) }} VNĐ</strong></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Ngày tạo:</span>
                    <span class="detail-value">{{ $payment->created_at->format('d/m/Y H:i') }}</span>
                </div>
                @if($payment->paid_at)
                <div class="detail-item">
                    <span class="detail-label">Ngày thanh toán:</span>
                    <span class="detail-value">{{ $payment->paid_at->format('d/m/Y H:i') }}</span>
                </div>
                @endif
                @if($payment->txn_ref)
                <div class="detail-item">
                    <span class="detail-label">Mã giao dịch:</span>
                    <span class="detail-value">{{ $payment->txn_ref }}</span>
                </div>
                @endif
                @if($payment->note)
                <div class="detail-item">
                    <span class="detail-label">Ghi chú:</span>
                    <span class="detail-value">{{ $payment->note }}</span>
                </div>
                @endif
            </div>

            <!-- Invoice Information -->
            @if($payment->invoice)
            <div class="invoice-info">
                <h5><i class="fas fa-file-invoice"></i>Thông tin hóa đơn</h5>
                <div class="detail-item">
                    <span class="detail-label">Mã hóa đơn:</span>
                    <span class="detail-value">{{ $payment->invoice->invoice_no ?? 'HD' . str_pad($payment->invoice->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                @if($payment->invoice->lease && $payment->invoice->lease->unit)
                <div class="detail-item">
                    <span class="detail-label">Phòng:</span>
                    <span class="detail-value">
                        @if($payment->invoice->lease->unit->property)
                            {{ $payment->invoice->lease->unit->property->name }}
                        @endif
                        @if($payment->invoice->lease->unit->code)
                            - {{ $payment->invoice->lease->unit->code }}
                        @endif
                    </span>
                </div>
                @endif
                @if($payment->invoice->issue_date)
                <div class="detail-item">
                    <span class="detail-label">Kỳ thanh toán:</span>
                    <span class="detail-value">Tháng {{ $payment->invoice->issue_date->format('m/Y') }}</span>
                </div>
                @endif
                <div class="detail-item">
                    <span class="detail-label">Trạng thái hóa đơn:</span>
                    <span class="detail-value">
                        @if($payment->invoice->status === 'paid')
                            <span class="badge bg-success">Đã thanh toán</span>
                        @elseif($payment->invoice->status === 'issued')
                            <span class="badge bg-warning">Chờ thanh toán</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($payment->invoice->status) }}</span>
                        @endif
                    </span>
                </div>
            </div>
            @endif

            <!-- Sepay Instructions (if applicable) -->
            @if($payment->method && $payment->method->key_code === 'sepay' && $payment->status === 'pending')
            <div class="sepay-instructions">
                <h6><i class="fas fa-info-circle"></i>Hướng dẫn thanh toán Sepay</h6>
                <ul>
                    <li>Chuyển khoản đúng số tiền: <strong>{{ number_format($payment->amount) }} VNĐ</strong></li>
                    <li>Nội dung chuyển khoản: <strong>THANH TOAN HOA DON {{ $payment->invoice->invoice_no ?? 'HD' . str_pad($payment->invoice->id, 6, '0', STR_PAD_LEFT) }}</strong></li>
                    <li>Hệ thống sẽ tự động xác nhận khi nhận được chuyển khoản</li>
                    <li>Thời gian xác nhận: 5-15 phút sau khi chuyển khoản</li>
                </ul>
                
                <!-- QR Code for Sepay -->
                @php
                    $bankName = config('services.sepay.bank_name', 'TPBank');
                    $accountNumber = config('services.sepay.account_number', '46166378666');
                    $qrUrl = 'https://qr.sepay.vn/img?' . http_build_query([
                        'acc' => $accountNumber,
                        'bank' => $bankName,
                        'amount' => $payment->amount,
                        'des' => 'THANH TOAN HOA DON ' . ($payment->invoice->invoice_no ?? 'HD' . str_pad($payment->invoice->id, 6, '0', STR_PAD_LEFT))
                    ]);
                @endphp
                
                <div class="mt-3 text-center">
                    <h6 style="margin-bottom: 1rem;"><i class="fas fa-qrcode me-2"></i>Quét mã QR để chuyển khoản</h6>
                    <div class="qr-code-container">
                        <img src="{{ $qrUrl }}" alt="QR Code SePay">
                        <p class="mt-2"><small>Quét mã QR bằng app ngân hàng</small></p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Auto-refresh info for pending payments -->
            @if($payment->status === 'pending')
            <div class="refresh-info">
                <i class="fas fa-sync-alt me-2"></i>
                Trang này sẽ tự động cập nhật khi có thay đổi trạng thái
            </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-refresh for pending payments
@if($payment->status === 'pending')
let refreshInterval;

function startAutoRefresh() {
    refreshInterval = setInterval(() => {
        fetch(`/tenant/api/payments/status/{{ $payment->id }}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.payment.status !== '{{ $payment->status }}') {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error checking payment status:', error);
            });
    }, 10000); // Check every 10 seconds
}

function refreshStatus() {
    location.reload();
}

// Start auto-refresh when page loads
document.addEventListener('DOMContentLoaded', function() {
    startAutoRefresh();
});

// Stop auto-refresh when page is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        clearInterval(refreshInterval);
    } else {
        startAutoRefresh();
    }
});
@endif
</script>
@endpush
