@extends('layouts.app')

@section('title', 'Lịch sử thanh toán')

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/payments.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
/* Payment Status Colors */
:root {
    --status-payment-success: #10b981;
    --status-payment-success-light: #d1fae5;
    --status-payment-success-border: #10b981;
    --status-payment-success-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);

    --status-payment-pending: #f59e0b;
    --status-payment-pending-light: #fef3c7;
    --status-payment-pending-border: #f59e0b;
    --status-payment-pending-gradient: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);

    --status-payment-failed: #ef4444;
    --status-payment-failed-light: #fee2e2;
    --status-payment-failed-border: #ef4444;
    --status-payment-failed-gradient: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);

    --status-payment-refunded: #06b6d4;
    --status-payment-refunded-light: #cffafe;
    --status-payment-refunded-border: #06b6d4;
    --status-payment-refunded-gradient: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);

    --status-payment-total: #2766ec;
    --status-payment-total-light: #dbeafe;
    --status-payment-total-border: #2766ec;
    --status-payment-total-gradient: linear-gradient(135deg, #1E4FC8 0%, #2766ec 100%);
}

/* Payments Container */
.payments-container {
    background: linear-gradient(to bottom, #F0F4FF 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

/* Payment Cards with Blue Theme */
.payment-card-blue {
    background: white;
    border-radius: 16px;
    padding: 0;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
    transition: all 0.3s ease;
    overflow: hidden;
}

.payment-card-blue:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.15);
    border-color: var(--blue-light);
}

.payment-status-blue {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 1px solid var(--blue-border);
}

.payment-status-blue.success {
    background: #D4EDDA;
    color: #155724;
}

.payment-status-blue.pending {
    background: #FFF3CD;
    color: #856404;
}

.payment-status-blue.failed {
    background: #F8D7DA;
    color: #721C24;
}

.payment-status-blue.refunded {
    background: #D1ECF1;
    color: #0C5460;
}

.payment-content-blue {
    padding: 1.5rem;
}

.payment-info-blue {
    background: var(--blue-bg-light);
    border-radius: 12px;
    padding: 1.5rem;
}

.payment-id-blue {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--blue-primary);
    margin-bottom: 0.75rem;
}

.payment-amount-section {
    background: var(--blue-bg-light);
    padding: 1.5rem;
    border-radius: 12px;
    height: 100%;
}

.amount-header {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--blue-border);
}

.amount-label {
    font-size: 0.85em;
    color: #666;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.amount-value {
    font-size: 1.8rem;
    color: var(--blue-primary);
    font-weight: 700;
}

.amount-currency {
    font-size: 0.9em;
    color: #666;
    margin-top: 0.25rem;
}

.payment-actions-blue {
    padding: 1rem 1.5rem;
    background: var(--blue-bg-light);
    border-top: 1px solid var(--blue-border);
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.payment-actions-blue .btn {
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.payment-actions-blue .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.2);
}

/* Empty State */
.empty-state-blue {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.empty-state-blue .empty-icon {
    font-size: 4rem;
    color: var(--blue-light);
    margin-bottom: 1.5rem;
}

.empty-state-blue h3 {
    color: var(--blue-primary);
    font-weight: 700;
    margin-bottom: 1rem;
}

.empty-state-blue p {
    color: #666;
    font-size: 1.1rem;
    margin-bottom: 2rem;
}

/* Status-specific colors for payment stat cards */
.stat-card-blue.total .stat-icon,
.stat-card-blue[data-filter="all"] .stat-icon {
    color: var(--status-payment-total);
}

.stat-card-blue.total .stat-content h3,
.stat-card-blue[data-filter="all"] .stat-content h3 {
    color: var(--status-payment-total);
}

.stat-card-blue.total:hover,
.stat-card-blue[data-filter="all"]:hover {
    border-color: var(--status-payment-total);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.25);
}

.stat-card-blue.total.active-filter {
    background: var(--status-payment-total-light) !important;
    border-color: var(--status-payment-total-border) !important;
    box-shadow: 0 6px 25px rgba(39, 102, 236, 0.4) !important;
}

.stat-card-blue.total.active-filter::before {
    background: var(--status-payment-total-gradient) !important;
    height: 5px;
}

.stat-card-blue.success .stat-icon,
.stat-card-blue[data-filter="success"] .stat-icon {
    color: var(--status-payment-success);
}

.stat-card-blue.success .stat-content h3,
.stat-card-blue[data-filter="success"] .stat-content h3 {
    color: var(--status-payment-success);
}

.stat-card-blue.success:hover,
.stat-card-blue[data-filter="success"]:hover {
    border-color: var(--status-payment-success);
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.25);
}

.stat-card-blue.success.active-filter {
    background: var(--status-payment-success-light) !important;
    border-color: var(--status-payment-success-border) !important;
    box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4) !important;
}

.stat-card-blue.success.active-filter::before {
    background: var(--status-payment-success-gradient) !important;
    height: 5px;
}

.stat-card-blue.pending .stat-icon,
.stat-card-blue[data-filter="pending"] .stat-icon {
    color: var(--status-payment-pending);
}

.stat-card-blue.pending .stat-content h3,
.stat-card-blue[data-filter="pending"] .stat-content h3 {
    color: var(--status-payment-pending);
}

.stat-card-blue.pending:hover,
.stat-card-blue[data-filter="pending"]:hover {
    border-color: var(--status-payment-pending);
    box-shadow: 0 8px 30px rgba(245, 158, 11, 0.25);
}

.stat-card-blue.pending.active-filter {
    background: var(--status-payment-pending-light) !important;
    border-color: var(--status-payment-pending-border) !important;
    box-shadow: 0 6px 25px rgba(245, 158, 11, 0.4) !important;
}

.stat-card-blue.pending.active-filter::before {
    background: var(--status-payment-pending-gradient) !important;
    height: 5px;
}

.stat-card-blue.failed .stat-icon,
.stat-card-blue[data-filter="failed"] .stat-icon {
    color: var(--status-payment-failed);
}

.stat-card-blue.failed .stat-content h3,
.stat-card-blue[data-filter="failed"] .stat-content h3 {
    color: var(--status-payment-failed);
}

.stat-card-blue.failed:hover,
.stat-card-blue[data-filter="failed"]:hover {
    border-color: var(--status-payment-failed);
    box-shadow: 0 8px 30px rgba(239, 68, 68, 0.25);
}

.stat-card-blue.failed.active-filter {
    background: var(--status-payment-failed-light) !important;
    border-color: var(--status-payment-failed-border) !important;
    box-shadow: 0 6px 25px rgba(239, 68, 68, 0.4) !important;
}

.stat-card-blue.failed.active-filter::before {
    background: var(--status-payment-failed-gradient) !important;
    height: 5px;
}

/* Status-specific colors for payment filter tabs */
.filter-tab-blue[data-status="all"]:hover:not(.active) {
    background: var(--status-payment-total-light);
    border-color: var(--status-payment-total);
    color: var(--status-payment-total);
}

.filter-tab-blue[data-status="all"].active {
    background: var(--status-payment-total-gradient);
    border-color: var(--status-payment-total-border);
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.3);
    color: white;
}

.filter-tab-blue[data-status="success"]:hover:not(.active) {
    background: var(--status-payment-success-light);
    border-color: var(--status-payment-success);
    color: var(--status-payment-success);
}

.filter-tab-blue[data-status="success"].active {
    background: var(--status-payment-success-gradient);
    border-color: var(--status-payment-success-border);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    color: white;
}

.filter-tab-blue[data-status="pending"]:hover:not(.active) {
    background: var(--status-payment-pending-light);
    border-color: var(--status-payment-pending);
    color: var(--status-payment-pending);
}

.filter-tab-blue[data-status="pending"].active {
    background: var(--status-payment-pending-gradient);
    border-color: var(--status-payment-pending-border);
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    color: white;
}

.filter-tab-blue[data-status="failed"]:hover:not(.active) {
    background: var(--status-payment-failed-light);
    border-color: var(--status-payment-failed);
    color: var(--status-payment-failed);
}

.filter-tab-blue[data-status="failed"].active {
    background: var(--status-payment-failed-gradient);
    border-color: var(--status-payment-failed-border);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    color: white;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/user/invoices.js') }}?v={{ time() }}"></script>
@endpush

@section('content')
<div class="page-container-blue">
    <div class="container">
        <!-- Page Header -->
        @include('tenant.components.page-header', [
            'title' => 'Lịch sử thanh toán',
            'subtitle' => 'Theo dõi tất cả các giao dịch thanh toán của bạn',
            'icon' => 'fas fa-credit-card',
            'actions' => [
                ['label' => 'Về Dashboard', 'url' => route('tenant.dashboard'), 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary'],
                ['label' => 'Hóa đơn', 'url' => route('tenant.invoices.index'), 'icon' => 'fas fa-file-invoice', 'variant' => 'outline-primary'],
            ]
        ])

        <!-- Stats Cards -->
        <div id="stats-cards-container">
            @php
                $paymentStats = [
                    [
                        'icon' => 'fas fa-list',
                        'value' => $stats['total'],
                        'label' => 'Tổng giao dịch',
                        'active' => request('status', 'all') == 'all',
                        'data-filter' => 'all',
                        'statusClass' => 'total',
                        'hx-get' => route('tenant.payments.index', ['status' => 'all', 'search' => request('search')]),
                        'hx-target' => '#payments-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading',
                        'title' => 'Click để xem tất cả giao dịch'
                    ],
                    [
                        'icon' => 'fas fa-check-circle',
                        'value' => $stats['success'],
                        'label' => 'Thành công',
                        'active' => request('status') == 'success',
                        'data-filter' => 'success',
                        'statusClass' => 'success',
                        'hx-get' => route('tenant.payments.index', ['status' => 'success', 'search' => request('search')]),
                        'hx-target' => '#payments-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading',
                        'title' => 'Click để lọc giao dịch thành công'
                    ],
                    [
                        'icon' => 'fas fa-clock',
                        'value' => $stats['pending'],
                        'label' => 'Đang chờ',
                        'active' => request('status') == 'pending',
                        'data-filter' => 'pending',
                        'statusClass' => 'pending',
                        'hx-get' => route('tenant.payments.index', ['status' => 'pending', 'search' => request('search')]),
                        'hx-target' => '#payments-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading',
                        'title' => 'Click để lọc giao dịch đang chờ'
                    ],
                    [
                        'icon' => 'fas fa-times-circle',
                        'value' => $stats['failed'],
                        'label' => 'Thất bại',
                        'active' => request('status') == 'failed',
                        'data-filter' => 'failed',
                        'statusClass' => 'failed',
                        'hx-get' => route('tenant.payments.index', ['status' => 'failed', 'search' => request('search')]),
                        'hx-target' => '#payments-list-container',
                        'hx-swap' => 'innerHTML',
                        'hx-push-url' => 'true',
                        'hx-indicator' => '#htmx-loading',
                        'title' => 'Click để lọc giao dịch thất bại'
                    ]
                ];
            @endphp
            @include('tenant.components.stats-cards', [
                'stats' => $paymentStats,
                'columns' => 4,
                'class' => 'mb-4'
            ])
        </div>

        <!-- Filter and Search -->
        @php
            $filterTabs = [
                [
                    'label' => 'Tất cả',
                    'value' => 'all',
                    'active' => request('status', 'all') == 'all',
                    'hx-get' => route('tenant.payments.index', ['status' => 'all', 'search' => request('search')]),
                    'hx-target' => '#payments-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-folder'
                ],
                [
                    'label' => 'Thành công',
                    'value' => 'success',
                    'active' => request('status') == 'success',
                    'hx-get' => route('tenant.payments.index', ['status' => 'success', 'search' => request('search')]),
                    'hx-target' => '#payments-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-check-circle'
                ],
                [
                    'label' => 'Đang chờ',
                    'value' => 'pending',
                    'active' => request('status') == 'pending',
                    'hx-get' => route('tenant.payments.index', ['status' => 'pending', 'search' => request('search')]),
                    'hx-target' => '#payments-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-clock'
                ],
                [
                    'label' => 'Thất bại',
                    'value' => 'failed',
                    'active' => request('status') == 'failed',
                    'hx-get' => route('tenant.payments.index', ['status' => 'failed', 'search' => request('search')]),
                    'hx-target' => '#payments-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-times-circle'
                ]
            ];
        @endphp
        @include('tenant.components.filter-section', [
            'searchPlaceholder' => 'Tìm kiếm theo mã giao dịch, hóa đơn...',
            'searchValue' => request('search'),
            'filters' => $filterTabs,
            'formId' => 'filterForm',
            'searchInputId' => 'searchInput',
            'hxGet' => route('tenant.payments.index'),
            'hxTarget' => '#payments-list-container',
            'hxSwap' => 'innerHTML',
            'hxPushUrl' => 'true',
            'hxIndicator' => '#htmx-loading',
            'hxTrigger' => 'input delay:500ms from:#searchInput',
        ])

        <!-- HTMX Loading Indicator -->
        <div id="htmx-loading" class="htmx-indicator-blue" style="display: none;">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Đang tải...</span>
                </div>
            <p class="mt-2 text-muted">Đang tải dữ liệu...</p>
            </div>

        <!-- Payments List -->
        <div class="payments-list" id="payments-list-container">
            @include('tenant.payments.partials.payments-list', ['payments' => $payments])
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrCodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QR Code Thanh toán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qrCodeContent">
                    <!-- QR code will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Ensure HTMX only replaces the correct container and doesn't break the layout
document.body.addEventListener('htmx:beforeSwap', function(evt) {
    // Only allow swapping into specific containers
    const allowedTargets = ['payments-list-container', 'stats-cards-container', 'filter-section-container'];
    if (allowedTargets.includes(evt.detail.target.id)) {
        evt.detail.shouldSwap = true;
    } else {
        // Prevent swapping into other elements that might break the layout
        console.warn('HTMX: Attempted to swap into unauthorized target:', evt.detail.target.id);
        evt.detail.shouldSwap = false;
    }
});

// Show QR Code
function showQRCode(paymentId) {
    // Get payment data and generate QR code
    fetch(`/tenant/api/payments/status/${paymentId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            // Try to get error message from response
            return response.json().then(err => {
                throw new Error(err.message || 'Không thể tải thông tin thanh toán');
            }).catch(() => {
                throw new Error('Không thể tải thông tin thanh toán. Mã lỗi: ' + response.status);
            });
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Không thể tải thông tin thanh toán');
            return;
        }
        
        if (data.payment && data.payment.method && data.payment.method.key_code === 'sepay') {
            let qrUrl, bankInfo;
            
            // Use QR info from backend if available
            if (data.payment.qr_info) {
                qrUrl = data.payment.qr_info.qr_url;
                bankInfo = data.payment.qr_info.bank_info;
            } else {
                // Fallback: generate QR URL on frontend
            const bankName = 'TPBank';
            const accountNumber = '46166378666';
            const amount = data.payment.amount;
            const content = `THANH TOAN HOA DON ${data.payment.invoice.invoice_no || 'HD' + String(data.payment.invoice.id).padStart(6, '0')}`;
            
                qrUrl = `https://qr.sepay.vn/img?${new URLSearchParams({
                acc: accountNumber,
                bank: bankName,
                amount: amount,
                des: content
            })}`;
                
                bankInfo = {
                    bank_name: bankName,
                    account_number: accountNumber,
                    amount: amount,
                    content: content
                };
            }
            
            document.getElementById('qrCodeContent').innerHTML = `
                <div class="qr-code-container">
                    <img src="${qrUrl}" alt="QR Code" style="max-width: 200px; height: auto; border: 1px solid #ddd; border-radius: 8px;">
                    <p class="mt-3"><strong>Số tiền:</strong> ${parseFloat(bankInfo.amount).toLocaleString('vi-VN')} VNĐ</p>
                    <p><strong>Nội dung:</strong> ${bankInfo.content}</p>
                    ${bankInfo.account_number ? `<p><small class="text-muted">Số tài khoản: ${bankInfo.account_number}</small></p>` : ''}
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="downloadQRCode('${qrUrl}')">
                        <i class="fas fa-download me-1"></i>Tải QR Code
                    </button>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('qrCodeModal'));
            modal.show();
        } else {
            alert('Không thể hiển thị QR Code cho thanh toán này');
        }
    })
    .catch(error => {
        console.error('Error loading payment details:', error);
        alert('Không thể tải thông tin thanh toán. Vui lòng thử lại.');
    });
}

function downloadQRCode(qrUrl) {
    const link = document.createElement('a');
    link.href = qrUrl;
    link.download = 'qr-code-payment.png';
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
@endpush
