@extends('layouts.app')

@section('title', 'Chi tiết hóa đơn')

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/invoices.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/user/invoices-show.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
/* Invoice Detail Container */
.invoice-detail-container {
    background: linear-gradient(to bottom, #F0F4FF 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

/* Modern Header with Blue Gradient Theme */
.invoice-header-modern {
    background: linear-gradient(135deg, #1E4FC8 0%, #2766ec 50%, #4A85F0 100%);
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(39, 102, 236, 0.3);
    color: white;
    position: relative;
    overflow: hidden;
}

.invoice-header-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 50%, transparent 100%);
    pointer-events: none;
}

.invoice-header-modern .invoice-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #FFFFFF;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    position: relative;
    z-index: 1;
}

.invoice-header-modern .invoice-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
    position: relative;
    z-index: 1;
}

.invoice-header-modern .invoice-number {
    background: rgba(255, 255, 255, 0.25);
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-weight: 600;
    backdrop-filter: blur(10px);
    color: #FFFFFF;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.invoice-header-modern .invoice-status-badge {
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

.invoice-header-modern .invoice-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}

.invoice-header-modern .btn {
    background: rgba(255, 255, 255, 0.95);
    color: #2766ec;
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.invoice-header-modern .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    background: #FFFFFF;
    color: #2766ec;
}

/* Modern Tables */
.table-modern {
    border-radius: 12px;
    overflow: hidden;
}

.table-modern thead {
    background: var(--blue-gradient);
    color: white;
}

.table-modern thead th {
    border: none;
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table-modern tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #F5F5F5;
}

.table-modern tbody tr:hover {
    background: var(--blue-bg-light);
    transform: scale(1.01);
}

.table-modern tbody td {
    padding: 1rem;
    vertical-align: middle;
}

/* Service Items */
.service-item {
    display: block;
    font-size: 0.9em;
    color: #555;
    margin-bottom: 8px;
    padding: 0.5rem;
    background: var(--blue-bg-light);
    border-radius: 8px;
    border-left: 3px solid var(--blue-primary);
}

.service-item strong {
    color: var(--blue-primary);
}

/* Status Badges */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge.status-paid {
    background: #D4EDDA;
    color: #155724;
}

.status-badge.status-issued {
    background: #FFF3CD;
    color: #856404;
}

.status-badge.status-overdue {
    background: #F8D7DA;
    color: #721C24;
}

.status-badge.status-draft {
    background: #E2E3E5;
    color: #383D41;
}

.status-badge.status-cancelled {
    background: #F5C6CB;
    color: #721C24;
}

/* Address Items */
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

/* Section Headers */
.section-header {
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--blue-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title i {
    color: var(--blue-primary);
}

/* Payment Timeline */
.payment-timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 2rem;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -2rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--blue-border);
}

.timeline-marker {
    position: absolute;
    left: -2.5rem;
    top: 0;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--blue-primary);
    color: white;
    font-size: 0.8rem;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(39, 102, 236, 0.3);
}

.timeline-marker.success {
    background: #28a745;
}

.timeline-content {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
}

.payment-details {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #F5F5F5;
}

.payment-details .label {
    font-weight: 600;
    color: #666;
    margin-right: 0.5rem;
}

.payment-details .value {
    color: #333;
}

/* Tab Navigation with Blue Theme */
.tab-navigation-orange .btn,
.card .btn-outline-primary {
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid var(--blue-border);
    background: white;
    color: var(--blue-primary);
}

.tab-navigation-orange .btn.active,
.card .btn-outline-primary.active {
    background: var(--blue-gradient);
    color: #FFFFFF !important;
    border-color: var(--blue-primary);
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.3);
    font-weight: 700;
}

.tab-navigation-orange .btn:not(.active):hover,
.card .btn-outline-primary:not(.active):hover {
    background: var(--blue-bg-light);
    border-color: var(--blue-light);
    transform: translateY(-2px);
    color: var(--blue-primary);
}

/* Tab Content with Blue Accents */
.tab-content {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    margin-top: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
}

.tab-content.hidden {
    display: none !important;
}

/* Responsive */
@media (max-width: 768px) {
    .invoice-header-modern {
        padding: 1.5rem;
    }
    
    .invoice-header-modern .invoice-title {
        font-size: 1.5rem;
    }
    
    .tab-content {
        padding: 1.5rem;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/user/invoices-show.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/tab-navigation.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tab navigation - wait a bit for tab-navigation.js to load
    setTimeout(function() {
        if (typeof TabNavigation !== 'undefined') {
            TabNavigation.init('invoiceTabs', ['overview']);
        } else if (typeof toggleTab !== 'undefined') {
            // Fallback: manually initialize if TabNavigation not available
            const defaultVisible = ['overview'];
            document.querySelectorAll('.tab-content').forEach(tab => {
                const tabId = tab.id.replace('tab-', '');
                if (!defaultVisible.includes(tabId)) {
                    tab.style.display = 'none';
                }
            });
        }
    }, 100);
});
</script>
@endpush

@section('content')
<div class="page-container-blue">
    <div class="container">
        <!-- Modern Invoice Header -->
        <div class="invoice-header-modern">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3" style="position: relative; z-index: 1;">
                <ol class="breadcrumb mb-0" style="background: rgba(255, 255, 255, 0.2); padding: 0.75rem 1rem; border-radius: 10px; backdrop-filter: blur(10px);">
                    <li class="breadcrumb-item">
                        <a href="{{ route('tenant.dashboard') }}" style="color: rgba(255, 255, 255, 0.9); text-decoration: none;">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('tenant.invoices.index') }}" style="color: rgba(255, 255, 255, 0.9); text-decoration: none;">
                            <i class="fas fa-file-invoice me-1"></i>Hóa đơn
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: rgba(255, 255, 255, 1);">
                        <i class="fas fa-file-invoice-dollar me-1"></i>Chi tiết
                    </li>
                </ol>
            </nav>
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="invoice-title-section">
                        <h1 class="invoice-title">{{ $invoice->lease->unit->property->name }}</h1>
                        <div class="invoice-meta">
                            <span class="invoice-number">Mã hóa đơn: {{ $invoice->invoice_no ?? 'HD' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</span>
                            <span class="invoice-status-badge {{ $invoice->status }}">
                                @switch($invoice->status)
                                    @case('paid')
                                        <i class="fas fa-check-circle"></i> Đã thanh toán
                                        @break
                                    @case('issued')
                                        @if($isOverdue)
                                            <i class="fas fa-exclamation-triangle"></i> Quá hạn
                                        @else
                                            <i class="fas fa-clock"></i> Chờ thanh toán
                                        @endif
                                        @break
                                    @case('draft')
                                        <i class="fas fa-edit"></i> Nháp
                                        @break
                                    @case('cancelled')
                                        <i class="fas fa-times"></i> Đã hủy
                                        @break
                                @endswitch
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="invoice-actions">
                        <a href="{{ route('tenant.invoices.index') }}" class="btn" style="background: white; color: var(--blue-primary); border: 2px solid var(--blue-primary); font-weight: 600; padding: 0.75rem 1.5rem; margin-right: 0.5rem; border-radius: 12px; transition: all 0.3s ease; text-decoration: none;">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại
                        </a>
                        <a href="{{ route('tenant.invoices.download', $invoice->id) }}" class="btn" style="background: white; color: var(--blue-primary); border: 2px solid var(--blue-primary); font-weight: 600; padding: 0.75rem 1.5rem; margin-right: 0.5rem; border-radius: 12px; transition: all 0.3s ease; text-decoration: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>Tải PDF
                        </a>
                        @if($invoice->status === 'issued')
                            <a href="{{ route('tenant.payments.methods', $invoice->id) }}" class="btn {{ $isOverdue ? 'btn-danger' : 'btn-success' }}" style="font-weight: 600; padding: 0.75rem 1.5rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); {{ $isOverdue ? 'color: white;' : 'background: white; color: var(--blue-primary); border: 2px solid var(--blue-primary);' }} text-decoration: none; border-radius: 12px; transition: all 0.3s ease;">
                                <i class="fas fa-credit-card me-1"></i>{{ $isOverdue ? 'Thanh toán ngay' : 'Thanh toán' }}
                            </a>
                        @endif
                        @if($invoice->status === 'paid')
                            @php
                                $payment = $invoice->payments()->orderBy('created_at', 'desc')->first();
                            @endphp
                            @if($payment)
                                <a href="{{ route('tenant.payments.status', $payment->id) }}" class="btn" style="background: white; color: var(--blue-primary); border: 2px solid var(--blue-primary); font-weight: 600; padding: 0.75rem 1.5rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); border-radius: 12px; transition: all 0.3s ease; text-decoration: none;">
                                    <i class="fas fa-eye me-1"></i>Xem thanh toán
                                </a>
                            @else
                                <a href="{{ route('tenant.payments.index', ['invoice_id' => $invoice->id]) }}" class="btn" style="background: white; color: var(--blue-primary); border: 2px solid var(--blue-primary); font-weight: 600; padding: 0.75rem 1.5rem; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); border-radius: 12px; transition: all 0.3s ease; text-decoration: none;">
                                    <i class="fas fa-eye me-1"></i>Xem thanh toán
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        @php
            $tabs = [
                'overview' => [
                    'label' => 'Tổng quan',
                    'icon' => 'fas fa-info-circle',
                    'color' => 'primary'
                ],
                'items' => [
                    'label' => 'Chi tiết hóa đơn',
                    'icon' => 'fas fa-list',
                    'color' => 'primary'
                ],
            ];
            if($invoice->status === 'paid' && $invoice->paid_at) {
                $tabs['payment-history'] = [
                    'label' => 'Lịch sử thanh toán',
                    'icon' => 'fas fa-history',
                    'color' => 'primary'
                ];
            }
        @endphp
        
        @include('staff.components.tab-navigation', [
            'tabs' => $tabs,
            'storageKey' => 'invoiceTabs',
            'defaultVisible' => ['overview']
        ])

        <!-- Tab Contents -->
        <!-- Overview Tab -->
        <div id="tab-overview" class="tab-content" style="display: block;">
            <div class="row">
                <!-- Basic Information -->
                <div class="col-lg-6 mb-4">
                    @php
                        $basicInfoItems = [
                            ['label' => 'Mã hóa đơn', 'value' => $invoice->invoice_no ?? 'HD' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT)],
                            ['label' => 'Ngày phát hành', 'value' => $invoice->issue_date->format('d/m/Y H:i')],
                            ['label' => 'Ngày đến hạn', 'value' => $invoice->due_date->format('d/m/Y'), 'class' => $isOverdue ? 'text-danger' : ''],
                        ];
                        // Lấy payment để hiển thị ngày thanh toán nếu có
                        $payment = $invoice->payments()->orderBy('created_at', 'desc')->first();
                        
                        if($invoice->status === 'paid' && $payment) {
                            $paidAt = $payment->paid_at ?? ($invoice->paid_at ?? null);
                            if($paidAt) {
                                $paidAtDate = is_string($paidAt) ? \Carbon\Carbon::parse($paidAt) : $paidAt;
                                $basicInfoItems[] = ['label' => 'Ngày thanh toán', 'value' => $paidAtDate->format('d/m/Y H:i'), 'class' => 'text-success'];
                            } else {
                                $basicInfoItems[] = ['label' => 'Ngày thanh toán', 'value' => 'N/A', 'class' => 'text-success'];
                            }
                        }
                        
                        // Xử lý trạng thái - lấy từ invoice, không phải payment
                        $invoiceStatus = $invoice->status ?? 'draft';
                        $statusBadge = match(strtolower($invoiceStatus)) {
                            'paid' => ['type' => 'badge', 'badgeVariant' => 'success', 'value' => 'Đã thanh toán'],
                            'issued' => $isOverdue ? ['type' => 'badge', 'badgeVariant' => 'danger', 'value' => 'Quá hạn'] : ['type' => 'badge', 'badgeVariant' => 'warning', 'value' => 'Chờ thanh toán'],
                            'draft' => ['type' => 'badge', 'badgeVariant' => 'secondary', 'value' => 'Nháp'],
                            'cancelled' => ['type' => 'badge', 'badgeVariant' => 'dark', 'value' => 'Đã hủy'],
                            default => ['type' => 'badge', 'badgeVariant' => 'secondary', 'value' => 'Không xác định (' . $invoiceStatus . ')']
                        };
                        $basicInfoItems[] = array_merge(['label' => 'Trạng thái'], $statusBadge);
                    @endphp
                    @include('tenant.components.info-card', [
                        'title' => 'Thông tin cơ bản',
                        'icon' => 'fas fa-info-circle',
                        'items' => $basicInfoItems
                    ])
                </div>

                <!-- Property Information -->
                <div class="col-lg-6 mb-4">
                    @php
                        $locationAddress = null;
                        $location2025Address = null;
                        
                        if ($invoice->lease->unit->property->location) {
                            $addressParts = [];
                            if ($invoice->lease->unit->property->location->street) $addressParts[] = $invoice->lease->unit->property->location->street;
                            if ($invoice->lease->unit->property->location->ward) $addressParts[] = $invoice->lease->unit->property->location->ward;
                            if ($invoice->lease->unit->property->location->district) $addressParts[] = $invoice->lease->unit->property->location->district;
                            if ($invoice->lease->unit->property->location->city) $addressParts[] = $invoice->lease->unit->property->location->city;
                            if ($invoice->lease->unit->property->location->country && $invoice->lease->unit->property->location->country !== 'Vietnam') $addressParts[] = $invoice->lease->unit->property->location->country;
                            $locationAddress = !empty($addressParts) ? implode(', ', $addressParts) : null;
                        }
                        
                        if ($invoice->lease->unit->property->location2025) {
                            $addressParts2025 = [];
                            if ($invoice->lease->unit->property->location2025->street) $addressParts2025[] = $invoice->lease->unit->property->location2025->street;
                            if ($invoice->lease->unit->property->location2025->ward) $addressParts2025[] = $invoice->lease->unit->property->location2025->ward;
                            if ($invoice->lease->unit->property->location2025->city) $addressParts2025[] = $invoice->lease->unit->property->location2025->city;
                            if ($invoice->lease->unit->property->location2025->country && $invoice->lease->unit->property->location2025->country !== 'Vietnam') $addressParts2025[] = $invoice->lease->unit->property->location2025->country;
                            $location2025Address = !empty($addressParts2025) ? implode(', ', $addressParts2025) : null;
                        }
                        
                        $propertyInfoItems = [
                            ['label' => 'Tên phòng', 'value' => $invoice->lease->unit->property->name . ($invoice->lease->unit->code ? ' - ' . $invoice->lease->unit->code : '')],
                            ['label' => 'Loại phòng', 'value' => $invoice->lease->unit->property->propertyType->name ?? 'N/A'],
                            ['label' => 'Diện tích', 'value' => $invoice->lease->unit->area_m2 ? $invoice->lease->unit->area_m2 . ' m²' : 'N/A'],
                        ];
                        
                        // Thêm địa chỉ vào items nếu có
                        if($location2025Address) {
                            $propertyInfoItems[] = ['label' => 'Địa chỉ mới', 'value' => $location2025Address];
                        }
                        if($locationAddress) {
                            $propertyInfoItems[] = ['label' => 'Địa chỉ cũ', 'value' => $locationAddress];
                        }
                    @endphp
                    @include('tenant.components.info-card', [
                        'title' => 'Thông tin phòng',
                        'icon' => 'fas fa-home',
                        'items' => $propertyInfoItems
                    ])
                </div>

                <!-- Financial Information -->
                <div class="col-lg-6 mb-4">
                    @php
                        // Ưu tiên lấy payment có paid_at (bất kể status) - vì payment có thể pending nhưng đã có paid_at
                        $payment = $invoice->payments()
                            ->whereNotNull('paid_at')
                            ->orderBy('paid_at', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->with('method')
                            ->first();
                        
                        // Nếu không có payment có paid_at, lấy payment thành công (status = success)
                        if(!$payment) {
                            $payment = $invoice->payments()
                                ->where('status', 'success')
                                ->orderBy('created_at', 'desc')
                                ->with('method')
                                ->first();
                        }
                        
                        // Nếu vẫn không có, lấy payment mới nhất
                        if(!$payment) {
                            $payment = $invoice->payments()
                                ->orderBy('created_at', 'desc')
                                ->with('method')
                                ->first();
                        }
                        
                        $financialInfoItems = [
                            ['label' => 'Tổng tiền', 'value' => number_format($invoice->total_amount) . ' VNĐ', 'type' => 'price'],
                        ];
                        if($invoice->status === 'paid') {
                            if($payment) {
                                // Lấy phương thức thanh toán từ payment
                                $paymentMethodName = 'N/A';
                                if($payment->method) {
                                    $paymentMethodName = $payment->method->name ?? 'N/A';
                                }
                                
                                $paymentMethodBadge = match(strtolower($paymentMethodName)) {
                                    'momo' => ['type' => 'badge', 'badgeVariant' => 'primary', 'value' => 'MoMo'],
                                    'bank', 'chuyển khoản', 'chuyen khoan' => ['type' => 'badge', 'badgeVariant' => 'info', 'value' => 'Chuyển khoản'],
                                    'vnpay' => ['type' => 'badge', 'badgeVariant' => 'success', 'value' => 'VNPay'],
                                    'zalopay' => ['type' => 'badge', 'badgeVariant' => 'warning', 'value' => 'ZaloPay'],
                                    default => ['value' => $paymentMethodName]
                                };
                                $financialInfoItems[] = array_merge(['label' => 'Phương thức thanh toán'], $paymentMethodBadge);
                                
                                // Lấy ngày thanh toán từ payment hoặc invoice
                                $paidAt = $payment->paid_at ?? ($invoice->paid_at ?? null);
                                if($paidAt) {
                                    $paidAtDate = is_string($paidAt) ? \Carbon\Carbon::parse($paidAt) : $paidAt;
                                    $financialInfoItems[] = ['label' => 'Ngày thanh toán', 'value' => $paidAtDate->format('d/m/Y H:i'), 'class' => 'text-success'];
                                } else {
                                    $financialInfoItems[] = ['label' => 'Ngày thanh toán', 'value' => 'N/A'];
                                }
                                
                                // Lấy mã giao dịch từ payment
                                if($payment->txn_ref) {
                                    $financialInfoItems[] = ['label' => 'Mã giao dịch', 'value' => $payment->txn_ref];
                                }
                            } else {
                                // Nếu không có payment, lấy từ invoice
                                if($invoice->paid_at) {
                                    $paidAtDate = is_string($invoice->paid_at) ? \Carbon\Carbon::parse($invoice->paid_at) : $invoice->paid_at;
                                    $financialInfoItems[] = ['label' => 'Ngày thanh toán', 'value' => $paidAtDate->format('d/m/Y H:i'), 'class' => 'text-success'];
                                } else {
                                    $financialInfoItems[] = ['label' => 'Ngày thanh toán', 'value' => 'N/A'];
                                }
                                
                                // Lấy phương thức từ invoice nếu có
                                if($invoice->payment_method) {
                                    $paymentMethodBadge = match(strtolower($invoice->payment_method)) {
                                        'momo' => ['type' => 'badge', 'badgeVariant' => 'primary', 'value' => 'MoMo'],
                                        'bank' => ['type' => 'badge', 'badgeVariant' => 'info', 'value' => 'Chuyển khoản'],
                                        'vnpay' => ['type' => 'badge', 'badgeVariant' => 'success', 'value' => 'VNPay'],
                                        'zalopay' => ['type' => 'badge', 'badgeVariant' => 'warning', 'value' => 'ZaloPay'],
                                        default => ['value' => $invoice->payment_method]
                                    };
                                    $financialInfoItems[] = array_merge(['label' => 'Phương thức thanh toán'], $paymentMethodBadge);
                                }
                                
                                // Lấy mã giao dịch từ invoice nếu có
                                if($invoice->payment_reference) {
                                    $financialInfoItems[] = ['label' => 'Mã giao dịch', 'value' => $invoice->payment_reference];
                                }
                            }
                        }
                    @endphp
                    @include('tenant.components.info-card', [
                        'title' => 'Thông tin tài chính',
                        'icon' => 'fas fa-money-bill-wave',
                        'items' => $financialInfoItems
                    ])
                </div>

                <!-- Contact Information -->
                <div class="col-lg-6 mb-4">
                    @php
                        $contactInfoItems = [
                            ['label' => 'Chủ nhà/Agent', 'value' => $invoice->lease->agent ? ($invoice->lease->agent->full_name ?? $invoice->lease->agent->name) : 'N/A'],
                            ['label' => 'Số điện thoại', 'value' => $invoice->lease->agent && $invoice->lease->agent->phone ? $invoice->lease->agent->phone : 'N/A', 'type' => 'link', 'link' => $invoice->lease->agent && $invoice->lease->agent->phone ? 'tel:' . $invoice->lease->agent->phone : null],
                            ['label' => 'Email', 'value' => $invoice->lease->agent && $invoice->lease->agent->email ? $invoice->lease->agent->email : 'N/A', 'type' => 'link', 'link' => $invoice->lease->agent && $invoice->lease->agent->email ? 'mailto:' . $invoice->lease->agent->email : null],
                            ['label' => 'Người thuê', 'value' => $invoice->lease->tenant->full_name ?? $invoice->lease->tenant->name],
                        ];
                    @endphp
                    @include('tenant.components.info-card', [
                        'title' => 'Thông tin liên hệ',
                        'icon' => 'fas fa-users',
                        'items' => $contactInfoItems
                    ])
                </div>
            </div>
        </div>

        <!-- Items Tab -->
        <div id="tab-items" class="tab-content" style="display: none;">
            <div class="section-header mb-4">
                <h2 class="section-title">
                    <i class="fas fa-list me-2"></i>Chi tiết hóa đơn
                </h2>
            </div>

            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mô tả</th>
                            <th>Số lượng</th>
                            <th>Đơn giá</th>
                            <th>Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->description }}</td>
                                <td>{{ $item->quantity ?? 1 }}</td>
                                <td>{{ number_format($item->unit_price) }} VNĐ</td>
                                <td class="price">{{ number_format($item->amount) }} VNĐ</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row" style="background: var(--blue-bg-light);">
                            <td colspan="4" class="text-end"><strong>Tổng cộng:</strong></td>
                            <td class="price"><strong>{{ number_format($invoice->total_amount) }} VNĐ</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Payment History Tab -->
        @if($invoice->status === 'paid' && $invoice->paid_at)
        <div id="tab-payment-history" class="tab-content" style="display: none;">
            <div class="section-header mb-4">
                <h2 class="section-title">
                    <i class="fas fa-history me-2"></i>Lịch sử thanh toán
                </h2>
            </div>

            <div class="payment-timeline">
                <div class="timeline-item">
                    <div class="timeline-marker success">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="timeline-content">
                        <h6>Thanh toán thành công</h6>
                        <p class="text-muted">{{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y H:i') : 'N/A' }}</p>
                        <div class="payment-details">
                            <div class="row">
                                <div class="col-md-6">
                                    <span class="label">Phương thức:</span>
                                    <span class="value">
                                        @switch($invoice->payment_method)
                                            @case('momo')
                                                MoMo
                                                @break
                                            @case('bank')
                                                Chuyển khoản ngân hàng
                                                @break
                                            @case('vnpay')
                                                VNPay
                                                @break
                                            @case('zalopay')
                                                ZaloPay
                                                @break
                                            @default
                                                N/A
                                        @endswitch
                                    </span>
                                </div>
                                @if($invoice->payment_reference)
                                    <div class="col-md-6">
                                        <span class="label">Mã giao dịch:</span>
                                        <span class="value">{{ $invoice->payment_reference }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection
