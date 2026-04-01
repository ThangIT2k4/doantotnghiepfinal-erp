@extends('layouts.app')

@section('title', 'Hóa đơn của tôi')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/invoices.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
/* Invoices Container */
.invoices-container {
    background: linear-gradient(to bottom, #F0F4FF 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

/* Invoice Cards with Blue Theme */
.invoice-card-blue {
    background: white;
    border-radius: 16px;
    padding: 0;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
    transition: all 0.3s ease;
    overflow: hidden;
}

.invoice-card-blue:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.15);
    border-color: var(--blue-light);
}

.invoice-status-blue {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 1px solid var(--blue-border);
}

.invoice-status-blue.paid {
    background: #D4EDDA;
    color: #155724;
}

.invoice-status-blue.pending {
    background: #FFF3CD;
    color: #856404;
}

.invoice-status-blue.overdue {
    background: #F8D7DA;
    color: #721C24;
}

.invoice-content-blue {
    padding: 1.5rem;
}

/* Property Image */
.property-image {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.property-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.invoice-card-blue:hover .property-image img {
    transform: scale(1.05);
}

.invoice-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 1;
}

/* Invoice Info Section */
.invoice-info-blue {
    background: var(--blue-bg-light);
    padding: 1.5rem;
    border-radius: 12px;
}

.invoice-id-blue {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--blue-primary);
    margin-bottom: 0.75rem;
}

.invoice-date-blue {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 0.5rem;
}

.due-date-blue.overdue {
    color: #dc3545;
    font-weight: 600;
}

.invoice-period-blue {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--blue-border);
}

/* Property Info */
.property-name-blue {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--blue-primary);
    margin-bottom: 1rem;
}

.unit-code-blue {
    font-size: 0.9em;
    color: #666;
    font-weight: normal;
}

.property-address-blue {
    margin-bottom: 1rem;
}

.address-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.address-item {
    margin-bottom: 0.5rem;
}

.address-label {
    font-size: 0.85em;
    color: #999;
    font-weight: 500;
    display: block;
    margin-bottom: 0.25rem;
}

.address-value {
    font-size: 0.95em;
    color: #333;
}

/* Invoice Details */
.invoice-details-blue {
    margin-top: 1rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #F5F5F5;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item .label {
    font-weight: 600;
    color: #666;
    flex: 0 0 40%;
}

.detail-item .value {
    color: #333;
    text-align: right;
    flex: 1;
}

.detail-item .value.price {
    color: var(--blue-primary);
    font-weight: 700;
    font-size: 1.1rem;
}

/* Invoice Amount Section */
.invoice-amount-section {
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

.payment-method-section {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--blue-border);
}

.method-label {
    font-size: 0.85em;
    color: #666;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.method-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: white;
    border-radius: 8px;
    border: 1px solid var(--blue-border);
}

.payment-date-section,
.due-date-section {
    margin-top: 1rem;
}

.date-label {
    font-size: 0.85em;
    color: #666;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.date-value {
    font-size: 1rem;
    font-weight: 600;
}

.due-date-section.overdue .date-value {
    color: #dc3545;
}

/* Invoice Actions */
.invoice-actions-blue {
    padding: 1rem 1.5rem;
    background: var(--blue-bg-light);
    border-top: 1px solid var(--blue-border);
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.invoice-actions-blue .btn {
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.invoice-actions-blue .btn:hover {
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

.empty-state-blue .empty-icon,
.empty-state-blue .empty-icon-blue {
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

/* Month Filter Select */
.month-filter-blue {
    width: 100%;
}

.month-select-blue {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--blue-border);
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: var(--blue-bg-light);
    color: #333;
}

.month-select-blue:focus {
    outline: none;
    border-color: var(--blue-primary);
    background: white;
    box-shadow: 0 0 0 0.2rem rgba(39, 102, 236, 0.25);
}

/* HTMX Loading Indicator */
.htmx-indicator-blue {
    text-align: center;
    padding: 3rem;
}

.htmx-indicator-blue .spinner-border {
    color: var(--blue-primary);
    width: 3rem;
    height: 3rem;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/user/invoices.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/user/invoices-htmx.js') }}?v={{ time() }}"></script>
@endpush

@section('content')
<div class="page-container-blue">
    <div class="container">
        <!-- Page Header -->
        @include('tenant.components.page-header', [
            'title' => 'Hóa đơn của tôi',
            'subtitle' => 'Quản lý và theo dõi các hóa đơn thanh toán',
            'icon' => 'fas fa-file-invoice-dollar',
            'actions' => [
                ['label' => 'Về Dashboard', 'url' => route('tenant.dashboard'), 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary'],
                
            ]
        ])

        <!-- Stats Cards -->
        @php
            $invoiceStats = [
                [
                    'icon' => 'fas fa-check-circle',
                    'value' => $stats['paid'],
                    'label' => 'Đã thanh toán',
                    'active' => request('status') == 'paid',
                    'data-filter' => 'paid',
                    'statusClass' => 'paid',
                    'amount' => number_format($stats['paid_amount'] / 1000000, 1) . 'M VNĐ',
                    'hx-get' => route('tenant.invoices.index', ['status' => 'paid', 'search' => request('search'), 'month' => request('month')]),
                    'hx-target' => '#invoices-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để lọc hóa đơn đã thanh toán'
                ],
                [
                    'icon' => 'fas fa-clock',
                    'value' => $stats['pending'],
                    'label' => 'Chờ thanh toán',
                    'active' => request('status') == 'pending',
                    'data-filter' => 'pending',
                    'statusClass' => 'pending',
                    'amount' => number_format($stats['pending_amount'] / 1000000, 1) . 'M VNĐ',
                    'hx-get' => route('tenant.invoices.index', ['status' => 'pending', 'search' => request('search'), 'month' => request('month')]),
                    'hx-target' => '#invoices-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để lọc hóa đơn chờ thanh toán'
                ],
                [
                    'icon' => 'fas fa-exclamation-triangle',
                    'value' => $stats['overdue'],
                    'label' => 'Quá hạn',
                    'active' => request('status') == 'overdue',
                    'data-filter' => 'overdue',
                    'statusClass' => 'overdue',
                    'amount' => number_format($stats['overdue_amount'] / 1000000, 1) . 'M VNĐ',
                    'hx-get' => route('tenant.invoices.index', ['status' => 'overdue', 'search' => request('search'), 'month' => request('month')]),
                    'hx-target' => '#invoices-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để lọc hóa đơn quá hạn'
                ],
                [
                    'icon' => 'fas fa-calculator',
                    'value' => $stats['total'],
                    'label' => 'Tổng hóa đơn',
                    'active' => request('status', 'all') == 'all',
                    'data-filter' => 'all',
                    'statusClass' => 'total',
                    'amount' => number_format($stats['total_amount'] / 1000000, 1) . 'M VNĐ',
                    'hx-get' => route('tenant.invoices.index', ['status' => 'all', 'search' => request('search'), 'month' => request('month')]),
                    'hx-target' => '#invoices-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để xem tất cả hóa đơn'
                ]
            ];
        @endphp
        @include('tenant.components.stats-cards', [
            'stats' => $invoiceStats,
            'columns' => 4,
            'class' => 'mb-4'
        ])

        <!-- Filter and Search -->
        @php
            $filterTabs = [
                [
                    'label' => 'Tất cả',
                    'value' => 'all',
                    'active' => request('status', 'all') == 'all',
                    'hx-get' => route('tenant.invoices.index', ['status' => 'all', 'search' => request('search'), 'month' => request('month')]),
                    'hx-target' => '#invoices-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-folder'
                ],
                [
                    'label' => 'Đã thanh toán',
                    'value' => 'paid',
                    'active' => request('status') == 'paid',
                    'hx-get' => route('tenant.invoices.index', ['status' => 'paid', 'search' => request('search'), 'month' => request('month')]),
                    'hx-target' => '#invoices-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-check-circle'
                ],
                [
                    'label' => 'Chờ thanh toán',
                    'value' => 'pending',
                    'active' => request('status') == 'pending',
                    'hx-get' => route('tenant.invoices.index', ['status' => 'pending', 'search' => request('search'), 'month' => request('month')]),
                    'hx-target' => '#invoices-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-clock'
                ],
                [
                    'label' => 'Quá hạn',
                    'value' => 'overdue',
                    'active' => request('status') == 'overdue',
                    'hx-get' => route('tenant.invoices.index', ['status' => 'overdue', 'search' => request('search'), 'month' => request('month')]),
                    'hx-target' => '#invoices-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-exclamation-triangle'
                ]
            ];
            
            $additionalFields = '<div class="month-filter-blue">
                <select class="form-select month-select-blue" name="month" id="monthFilter">
                    <option value="">Tất cả tháng</option>';
            for($i = 0; $i < 12; $i++) {
                                    $date = \Carbon\Carbon::now()->subMonths($i);
                                    $value = $date->format('Y-m');
                                    $label = $date->format('m/Y');
                $selected = request('month') == $value ? 'selected' : '';
                $additionalFields .= '<option value="' . $value . '" ' . $selected . '>Tháng ' . $label . '</option>';
            }
            $additionalFields .= '</select>
            </div>';
                                @endphp
        @include('tenant.components.filter-section', [
            'searchPlaceholder' => 'Tìm kiếm theo mã hóa đơn, tên phòng, bất động sản...',
            'searchValue' => request('search'),
            'filters' => $filterTabs,
            'formId' => 'filterForm',
            'searchInputId' => 'searchInput',
            'hxGet' => route('tenant.invoices.index'),
            'hxTarget' => '#invoices-list-container',
            'hxSwap' => 'innerHTML',
            'hxPushUrl' => 'true',
            'hxIndicator' => '#htmx-loading',
            'hxTrigger' => 'input delay:500ms from:#searchInput, change from:#monthFilter',
            'additionalFields' => $additionalFields
        ])

        <!-- HTMX Loading Indicator -->
        <div id="htmx-loading" class="htmx-indicator-blue" style="display: none;">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Đang tải...</span>
                    </div>
            <p class="mt-2 text-muted">Đang tải dữ liệu...</p>
        </div>

        <!-- Invoices List -->
        <div class="invoices-list" id="invoices-list-container">
            @include('tenant.invoice.partials.invoices-list', ['invoices' => $invoices])
        </div>
    </div>
</div>

<!-- Payment Method Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chọn phương thức thanh toán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="invoice-summary mb-4">
                    <h6>Thông tin hóa đơn</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="summary-item">
                                <span class="label">Mã hóa đơn:</span>
                                <span class="value" id="paymentInvoiceId">-</span>
                            </div>
                            <div class="summary-item">
                                <span class="label">Phòng:</span>
                                <span class="value" id="paymentProperty">-</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="summary-item">
                                <span class="label">Kỳ thanh toán:</span>
                                <span class="value" id="paymentPeriod">-</span>
                            </div>
                            <div class="summary-item total">
                                <span class="label">Tổng tiền:</span>
                                <span class="value" id="paymentAmount">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payment-methods">
                    <h6>Chọn phương thức thanh toán</h6>
                    <div class="method-options">
                        <div class="method-option" data-method="momo">
                            <div class="method-icon">
                                <img src="https://developers.momo.vn/v3/assets/images/logo.png" alt="MoMo" style="width: 40px;">
                            </div>
                            <div class="method-info">
                                <h6>Ví MoMo</h6>
                                <p>Thanh toán nhanh chóng với ví điện tử</p>
                            </div>
                            <div class="method-select">
                                <input type="radio" name="payment_method" value="momo" id="method_momo">
                                <label for="method_momo"></label>
                            </div>
                        </div>

                        <div class="method-option" data-method="bank">
                            <div class="method-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="method-info">
                                <h6>Chuyển khoản ngân hàng</h6>
                                <p>Chuyển tiền qua tài khoản ngân hàng</p>
                            </div>
                            <div class="method-select">
                                <input type="radio" name="payment_method" value="bank" id="method_bank">
                                <label for="method_bank"></label>
                            </div>
                        </div>

                        <div class="method-option" data-method="vnpay">
                            <div class="method-icon">
                                <img src="https://vnpay.vn/s1/statics.vnpay.vn/2023/9/06ncktiwd6dc1694418196384.png" alt="VNPay" style="width: 40px;">
                            </div>
                            <div class="method-info">
                                <h6>VNPay</h6>
                                <p>Cổng thanh toán trực tuyến VNPay</p>
                            </div>
                            <div class="method-select">
                                <input type="radio" name="payment_method" value="vnpay" id="method_vnpay">
                                <label for="method_vnpay"></label>
                            </div>
                        </div>

                        <div class="method-option" data-method="zalopay">
                            <div class="method-icon">
                                <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-ZaloPay-Square.png" alt="ZaloPay" style="width: 40px;">
                            </div>
                            <div class="method-info">
                                <h6>ZaloPay</h6>
                                <p>Thanh toán an toàn với ZaloPay</p>
                            </div>
                            <div class="method-select">
                                <input type="radio" name="payment_method" value="zalopay" id="method_zalopay">
                                <label for="method_zalopay"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="processPayment()" disabled id="confirmPaymentBtn">
                    <i class="fas fa-credit-card me-1"></i>Xác nhận thanh toán
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Detail Modal -->
<div class="modal fade" id="invoiceDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết hóa đơn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="invoice-detail-content" id="invoiceDetailContent">
                    <!-- Invoice details will be loaded here -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Đang tải thông tin hóa đơn...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-success" onclick="downloadCurrentInvoice()">
                    <i class="fas fa-download me-1"></i>Tải PDF
                </button>
                <button type="button" class="btn btn-primary" onclick="printInvoice()">
                    <i class="fas fa-print me-1"></i>In hóa đơn
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Processing Modal -->
<div class="modal fade" id="processingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="processing-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h4 class="mt-3">Đang xử lý thanh toán...</h4>
                <p>Vui lòng không đóng trang này</p>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%" id="paymentProgress"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
