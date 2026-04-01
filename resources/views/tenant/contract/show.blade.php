@extends('layouts.app')

@section('title', 'Chi tiết hợp đồng')

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/contracts.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/user/contracts-show.css') }}?v={{ time() }}">
<style>
/* Contract Detail Container */
.contract-detail-container {
    background: linear-gradient(to bottom, #F0F4FF 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

/* Modern Header with Blue Gradient Theme - Custom for Contract Show */
.contract-header-modern {
    background: linear-gradient(135deg, #1E4FC8 0%, #2766ec 50%, #4A85F0 100%);
    border-radius: 20px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(39, 102, 236, 0.3);
    color: white;
    position: relative;
    overflow: hidden;
}

.contract-header-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 50%, transparent 100%);
    pointer-events: none;
}

.contract-header-modern .contract-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #FFFFFF;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    position: relative;
    z-index: 1;
}

.contract-header-modern .contract-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
    position: relative;
    z-index: 1;
}

.contract-header-modern .contract-number {
    background: rgba(255, 255, 255, 0.25);
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-weight: 600;
    backdrop-filter: blur(10px);
    color: #FFFFFF;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.contract-header-modern .contract-status-badge {
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

.contract-header-modern .contract-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}

.contract-header-modern .btn {
    background: rgba(255, 255, 255, 0.95);
    color: #2766ec;
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.contract-header-modern .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    background: #FFFFFF;
    color: #2766ec;
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

/* Status Badges with Blue Theme */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge.status-active,
.status-badge.status-paid {
    background: #D4EDDA;
    color: #155724;
}

.status-badge.status-expiring,
.status-badge.status-issued {
    background: #FFF3CD;
    color: #856404;
}

.status-badge.status-expired,
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

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #999;
}

.empty-state i {
    font-size: 3rem;
    color: #DDD;
    margin-bottom: 1rem;
}

.empty-state p {
    font-size: 1.1rem;
    margin: 0;
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

/* Breadcrumb with Blue */
.breadcrumb-nav .breadcrumb {
    background: transparent;
    padding: 0;
    margin-bottom: 1.5rem;
}

.breadcrumb-nav .breadcrumb-item a {
    color: var(--blue-primary);
    text-decoration: none;
    transition: all 0.2s ease;
}

.breadcrumb-nav .breadcrumb-item a:hover {
    color: var(--blue-dark);
    text-decoration: underline;
}

.breadcrumb-nav .breadcrumb-item.active {
    color: #666;
}

/* Action Buttons */
.action-buttons-modern {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.action-buttons-modern .btn {
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.action-buttons-modern .btn-success {
    background: #28A745;
    border-color: #28A745;
}

.action-buttons-modern .btn-primary {
    background: #007BFF;
    border-color: #007BFF;
}

.action-buttons-modern .btn-warning,
.action-buttons-modern .btn-outline-warning {
    background: var(--blue-gradient);
    border-color: var(--blue-primary);
    color: white;
}

.action-buttons-modern .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .contract-header-modern {
        padding: 1.5rem;
    }
    
    .contract-header-modern .contract-title {
        font-size: 1.5rem;
    }
    
    .info-card-modern {
        padding: 1.5rem;
    }
    
    .tab-content {
        padding: 1.5rem;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/user/contracts-show.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/tab-navigation.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tab navigation - wait a bit for tab-navigation.js to load
    setTimeout(function() {
        if (typeof TabNavigation !== 'undefined') {
            TabNavigation.init('contractTabs', ['overview']);
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
        <!-- Breadcrumb -->
        @include('tenant.components.page-header', [
            'title' => $contract->unit->property->name,
            'subtitle' => 'Chi tiết hợp đồng',
            'icon' => 'fas fa-file-contract',
            
            'actions' => [
                ['label' => 'Quay lại', 'url' => route('tenant.contracts.index'), 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary']
            ]
        ])

        <!-- Modern Contract Header -->
        <div class="contract-header-modern">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="contract-title-section">
                        <h1 class="contract-title">{{ $contract->unit->property->name }}</h1>
                        <div class="contract-meta">
                            <span class="contract-number">Mã hợp đồng: {{ $contract->contract_no ?? 'HD' . str_pad($contract->id, 6, '0', STR_PAD_LEFT) }}</span>
                            <span class="contract-status-badge {{ $isExpired ? 'expired' : ($isExpiring ? 'expiring' : 'active') }}">
                                @if($isExpired)
                                    <i class="fas fa-times-circle"></i> Đã hết hạn
                                @elseif($isExpiring)
                                    <i class="fas fa-exclamation-triangle"></i> Sắp hết hạn
                                @else
                                    <i class="fas fa-check-circle"></i> Đang hiệu lực
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="contract-actions">
                        <a href="{{ route('tenant.contracts.index') }}" class="btn" style="background: white; color: var(--blue-primary); border: 2px solid var(--blue-primary); font-weight: 600; padding: 0.75rem 1.5rem; margin-right: 0.5rem; border-radius: 12px; transition: all 0.3s ease; text-decoration: none;">
                            <i class="fas fa-arrow-left me-1"></i>Quay lại
                        </a>
                        <a href="{{ route('tenant.contracts.download', $contract->id) }}" class="btn" style="background: white; color: var(--blue-primary); border: 2px solid var(--blue-primary); font-weight: 600; padding: 0.75rem 1.5rem; border-radius: 12px; transition: all 0.3s ease; text-decoration: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);" target="_blank">
                            <i class="fas fa-file-pdf me-1"></i>Tải PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        @php
            $effectiveServiceSet = $contract->getEffectiveLeaseServiceSet();
            $serviceItems = $effectiveServiceSet?->items ?? collect();
            
            $tabs = [
                'overview' => [
                    'label' => 'Tổng quan',
                    'icon' => 'fas fa-info-circle',
                    'color' => 'primary'
                ],
                'property' => [
                    'label' => 'Thông tin phòng',
                    'icon' => 'fas fa-home',
                    'color' => 'primary'
                ],
                'financial' => [
                    'label' => 'Tài chính',
                    'icon' => 'fas fa-money-bill-wave',
                    'color' => 'primary'
                ],
                'services' => [
                    'label' => 'Dịch vụ',
                    'icon' => 'fas fa-concierge-bell',
                    'color' => 'primary',
                    'badge' => $serviceItems->count() > 0 ? $serviceItems->count() : null
                ],
                'contact' => [
                    'label' => 'Liên hệ',
                    'icon' => 'fas fa-users',
                    'color' => 'primary'
                ],
                'meters' => [
                    'label' => 'Chỉ số công tơ',
                    'icon' => 'fas fa-tachometer-alt',
                    'color' => 'primary',
                    'badge' => count($meterReadingsSummary) > 0 ? count($meterReadingsSummary) : null
                ],
                'invoices' => [
                    'label' => 'Hóa đơn',
                    'icon' => 'fas fa-file-invoice',
                    'color' => 'primary',
                    'badge' => $invoices->total() > 0 ? $invoices->total() : null
                ]
            ];
        @endphp
        
        @include('staff.components.tab-navigation', [
            'tabs' => $tabs,
            'storageKey' => 'contractTabs',
            'defaultVisible' => ['overview']
        ])

        <!-- Tab Contents -->
        <!-- Overview Tab -->
        <div id="tab-overview" class="tab-content" style="display: block;">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    @include('tenant.components.info-card', [
                        'title' => 'Thông tin cơ bản',
                        'icon' => 'fas fa-info-circle',
                        'items' => [
                            ['label' => 'Mã hợp đồng', 'value' => $contract->contract_no ?? 'HD' . str_pad($contract->id, 6, '0', STR_PAD_LEFT)],
                            ['label' => 'Ngày ký', 'value' => $contract->signed_at ? $contract->signed_at->format('d/m/Y H:i') : 'Chưa ký'],
                            ['label' => 'Ngày bắt đầu', 'value' => $contract->start_date->format('d/m/Y')],
                            ['label' => 'Ngày kết thúc', 'value' => $contract->end_date->format('d/m/Y')],
                            ['label' => 'Thời gian còn lại', 'value' => $isExpired ? 'Đã hết hạn' : ($remainingDays < 30 ? 'Còn ' . $remainingDays . ' ngày' : 'Còn ' . floor($remainingDays / 30) . ' tháng')]
                        ]
                    ])
                </div>
                <div class="col-lg-6 mb-4">
                    @include('tenant.components.info-card', [
                        'title' => 'Tóm tắt hợp đồng',
                        'icon' => 'fas fa-chart-line',
                        'items' => [
                            ['label' => 'Trạng thái', 'value' => $isExpired ? 'Đã hết hạn' : ($isExpiring ? 'Sắp hết hạn' : 'Đang hiệu lực'), 'type' => 'badge', 'badgeVariant' => $isExpired ? 'danger' : ($isExpiring ? 'warning' : 'success')],
                            ['label' => 'Giá thuê', 'value' => number_format($contract->rent_amount) . ' VNĐ/tháng', 'type' => 'price'],
                            ['label' => 'Tiền cọc', 'value' => number_format($contract->deposit_amount) . ' VNĐ', 'type' => 'price'],
                            ['label' => 'Chu kỳ thanh toán', 'value' => $contract->lease_payment_cycle ?? 'Hàng tháng'],
                            ['label' => 'Ngày thanh toán', 'value' => ($contract->lease_payment_day ?? $contract->billing_day) . ' hàng tháng']
                        ]
                    ])
                </div>
            </div>
        </div>

        <!-- Property Tab -->
        <div id="tab-property" class="tab-content" style="display: none;">
            <div class="info-card-modern">
                <h3 class="info-card-title">
                    <i class="fas fa-home"></i>Thông tin phòng
                </h3>
                <div class="info-content">
                    <div class="info-item">
                        <span class="info-label">Tên phòng:</span>
                        <span class="info-value">
                            {{ $contract->unit->property->name }}
                            @if($contract->unit->code)
                                - {{ $contract->unit->code }}
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Địa chỉ:</span>
                        <div class="info-value">
                            @php
                                $locationAddress = null;
                                $location2025Address = null;
                                
                                if ($contract->unit->property->location) {
                                    $addressParts = [];
                                    if ($contract->unit->property->location->street) $addressParts[] = $contract->unit->property->location->street;
                                    if ($contract->unit->property->location->ward) $addressParts[] = $contract->unit->property->location->ward;
                                    if ($contract->unit->property->location->district) $addressParts[] = $contract->unit->property->location->district;
                                    if ($contract->unit->property->location->city) $addressParts[] = $contract->unit->property->location->city;
                                    if ($contract->unit->property->location->country && $contract->unit->property->location->country !== 'Vietnam') $addressParts[] = $contract->unit->property->location->country;
                                    $locationAddress = !empty($addressParts) ? implode(', ', $addressParts) : null;
                                }
                                
                                if ($contract->unit->property->location2025) {
                                    $addressParts2025 = [];
                                    if ($contract->unit->property->location2025->street) $addressParts2025[] = $contract->unit->property->location2025->street;
                                    if ($contract->unit->property->location2025->ward) $addressParts2025[] = $contract->unit->property->location2025->ward;
                                    if ($contract->unit->property->location2025->city) $addressParts2025[] = $contract->unit->property->location2025->city;
                                    if ($contract->unit->property->location2025->country && $contract->unit->property->location2025->country !== 'Vietnam') $addressParts2025[] = $contract->unit->property->location2025->country;
                                    $location2025Address = !empty($addressParts2025) ? implode(', ', $addressParts2025) : null;
                                }
                            @endphp
                            @if($locationAddress)
                                <div class="address-item">
                                    <span class="address-label">Địa chỉ cũ:</span>
                                    <span class="address-value">{{ $locationAddress }}</span>
                                </div>
                            @endif
                            @if($location2025Address)
                                <div class="address-item">
                                    <span class="address-label">Địa chỉ mới:</span>
                                    <span class="address-value">{{ $location2025Address }}</span>
                                </div>
                            @endif
                            @if(!$locationAddress && !$location2025Address)
                                <span class="address-value">Địa chỉ chưa cập nhật</span>
                            @endif
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Loại phòng:</span>
                        <span class="info-value">{{ $contract->unit->property->propertyType->name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Diện tích:</span>
                        <span class="info-value">{{ $contract->unit->area_m2 ? $contract->unit->area_m2 . ' m²' : 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tầng:</span>
                        <span class="info-value">{{ $contract->unit->floor ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Tab -->
        <div id="tab-financial" class="tab-content" style="display: none;">
            <div class="info-card-modern">
                <h3 class="info-card-title">
                    <i class="fas fa-money-bill-wave"></i>Thông tin tài chính
                </h3>
                <div class="info-content">
                    <div class="info-item">
                        <span class="info-label">Giá thuê:</span>
                        <span class="info-value price">{{ number_format($contract->rent_amount) }} VNĐ/tháng</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tiền cọc:</span>
                        <span class="info-value price">{{ number_format($contract->deposit_amount) }} VNĐ</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Chu kỳ thanh toán:</span>
                        <span class="info-value">{{ $contract->lease_payment_cycle ?? 'Hàng tháng' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Ngày thanh toán:</span>
                        <span class="info-value">{{ $contract->lease_payment_day ?? $contract->billing_day }} hàng tháng</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Tab -->
        <div id="tab-services" class="tab-content" style="display: none;">
            <div class="info-card-modern">
                <h3 class="info-card-title">
                    <i class="fas fa-concierge-bell"></i>Dịch vụ đi kèm
                </h3>
                <div class="info-content">
                    @if($serviceItems->count() > 0)
                        @if($effectiveServiceSet)
                            <div class="info-item mb-4">
                                <span class="info-label">Bộ dịch vụ:</span>
                                <span class="info-value">
                                    <strong>{{ $effectiveServiceSet->name }}</strong>
                                    @if($effectiveServiceSet->description)
                                        <br><small class="text-muted">{{ $effectiveServiceSet->description }}</small>
                                    @endif
                                </span>
                            </div>
                        @endif
                        
                        <div class="table-responsive mt-3">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Tên dịch vụ</th>
                                        <th>Đơn giá</th>
                                        <th>Đơn vị</th>
                                        <th>Mô tả</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($serviceItems as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $item->service->name ?? 'N/A' }}</strong>
                                                @if($item->service->code)
                                                    <br><small class="text-muted">Mã: {{ $item->service->code }}</small>
                                                @endif
                                            </td>
                                            <td class="price">
                                                <strong>{{ number_format($item->price, 0, ',', '.') }} VNĐ</strong>
                                            </td>
                                            <td>
                                                {{ $item->service->unit_label ?? 'N/A' }}
                                            </td>
                                            <td>
                                                @if($item->service->description)
                                                    <small class="text-muted">{{ Str::limit($item->service->description, 100) }}</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4 p-3" style="background: var(--blue-bg-light); border-radius: 12px; border-left: 4px solid var(--blue-primary);">
                            <h6 class="mb-2" style="color: var(--blue-primary);">
                                <i class="fas fa-info-circle me-2"></i>Lưu ý về dịch vụ
                            </h6>
                            <ul class="mb-0" style="padding-left: 1.5rem; color: #666;">
                                <li>Các dịch vụ này sẽ được tính vào hóa đơn hàng tháng của bạn</li>
                                <li>Giá dịch vụ có thể thay đổi theo thỏa thuận với chủ nhà</li>
                                <li>Vui lòng liên hệ với chủ nhà/agent nếu có thắc mắc về dịch vụ</li>
                            </ul>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-concierge-bell"></i>
                            <p>Hợp đồng này không có dịch vụ đi kèm</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Contact Tab -->
        <div id="tab-contact" class="tab-content" style="display: none;">
            <div class="info-card-modern">
                <h3 class="info-card-title">
                    <i class="fas fa-users"></i>Thông tin liên hệ
                </h3>
                <div class="info-content">
                    <div class="info-item">
                        <span class="info-label">Chủ nhà/Agent:</span>
                        <span class="info-value">
                            @if($contract->agent)
                                {{ $contract->agent->full_name ?? $contract->agent->name }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Số điện thoại:</span>
                        <span class="info-value">
                            @if($contract->agent && $contract->agent->phone)
                                <a href="tel:{{ $contract->agent->phone }}">{{ $contract->agent->phone }}</a>
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value">
                            @if($contract->agent && $contract->agent->email)
                                <a href="mailto:{{ $contract->agent->email }}">{{ $contract->agent->email }}</a>
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Người thuê:</span>
                        <span class="info-value">{{ $contract->tenant->full_name ?? $contract->tenant->name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meters Tab -->
        <div id="tab-meters" class="tab-content" style="display: none;">
            <!-- Meter Readings Tabs -->
            <ul class="nav nav-tabs mb-4" id="meterTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab">
                        <i class="fas fa-chart-line me-1"></i>Tóm tắt gần nhất
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                        <i class="fas fa-history me-1"></i>Lịch sử đầy đủ
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="meterTabContent">
                <!-- Summary Tab -->
                <div class="tab-pane fade show active" id="summary" role="tabpanel">
                    <div class="meter-summary">
                        @forelse($meterReadingsSummary as $serviceName => $readings)
                            <div class="meter-service-group mb-4">
                                <h4 class="service-name mb-3" style="color: var(--blue-primary); font-weight: 700;">{{ $serviceName }}</h4>
                                <div class="table-responsive">
                                    <table class="table table-modern">
                                        <thead>
                                            <tr>
                                                <th>Ngày ghi</th>
                                                <th>Chỉ số cũ</th>
                                                <th>Chỉ số mới</th>
                                                <th>Tiêu thụ</th>
                                                <th>Đơn giá</th>
                                                <th>Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($readings->filter(function($reading) use ($contract) {
                                                return $reading->reading_date >= $contract->start_date;
                                            }) as $reading)
                                                @php
                                                    $previousReading = $reading->meter->readings()
                                                        ->where('reading_date', '<', $reading->reading_date)
                                                        ->where('reading_date', '>=', $contract->start_date)
                                                        ->latest('reading_date')
                                                        ->first();
                                                    
                                                    if (!$previousReading) {
                                                        $previousReading = $reading->meter->readings()
                                                            ->where('reading_date', '<', $contract->start_date)
                                                            ->latest('reading_date')
                                                            ->first();
                                                        $usage = 0;
                                                    } else {
                                                        $usage = max(0, $reading->value - $previousReading->value);
                                                    }
                                                    
                                                    $effectiveServiceSet = $contract->getEffectiveLeaseServiceSet();
                                                    $serviceItem = $effectiveServiceSet?->items->where('service_id', $reading->meter->service_id)->first();
                                                    $price = $serviceItem?->price ?? 0;
                                                    $total = $usage * $price;
                                                @endphp
                                                <tr>
                                                    <td>{{ $reading->reading_date->format('d/m/Y') }}</td>
                                                    <td>{{ $previousReading ? number_format($previousReading->value, 3) : '0.000' }}</td>
                                                    <td>{{ number_format($reading->value, 3) }}</td>
                                                    <td>{{ number_format($usage, 3) }}</td>
                                                    <td>{{ number_format($price) }} VNĐ</td>
                                                    <td class="price">{{ number_format($total) }} VNĐ</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <i class="fas fa-tachometer-alt"></i>
                                <p>Chưa có dữ liệu chỉ số công tơ</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- History Tab -->
                <div class="tab-pane fade" id="history" role="tabpanel">
                    <div class="meter-history">
                        @forelse($meterReadingsHistory as $serviceName => $readings)
                            <div class="meter-service-group mb-4">
                                <h4 class="service-name mb-3" style="color: var(--blue-primary); font-weight: 700;">{{ $serviceName }}</h4>
                                <div class="table-responsive">
                                    <table class="table table-modern">
                                        <thead>
                                            <tr>
                                                <th>Ngày ghi</th>
                                                <th>Chỉ số cũ</th>
                                                <th>Chỉ số mới</th>
                                                <th>Tiêu thụ</th>
                                                <th>Đơn giá</th>
                                                <th>Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($readings->filter(function($reading) use ($contract) {
                                                return $reading->reading_date >= $contract->start_date;
                                            }) as $reading)
                                                @php
                                                    $previousReading = $reading->meter->readings()
                                                        ->where('reading_date', '<', $reading->reading_date)
                                                        ->where('reading_date', '>=', $contract->start_date)
                                                        ->latest('reading_date')
                                                        ->first();
                                                    
                                                    if (!$previousReading) {
                                                        $previousReading = $reading->meter->readings()
                                                            ->where('reading_date', '<', $contract->start_date)
                                                            ->latest('reading_date')
                                                            ->first();
                                                        $usage = 0;
                                                    } else {
                                                        $usage = max(0, $reading->value - $previousReading->value);
                                                    }
                                                    
                                                    $effectiveServiceSet = $contract->getEffectiveLeaseServiceSet();
                                                    $serviceItem = $effectiveServiceSet?->items->where('service_id', $reading->meter->service_id)->first();
                                                    $price = $serviceItem?->price ?? 0;
                                                    $total = $usage * $price;
                                                @endphp
                                                <tr>
                                                    <td>{{ $reading->reading_date->format('d/m/Y') }}</td>
                                                    <td>{{ $previousReading ? number_format($previousReading->value, 3) : '0.000' }}</td>
                                                    <td>{{ number_format($reading->value, 3) }}</td>
                                                    <td>{{ number_format($usage, 3) }}</td>
                                                    <td>{{ number_format($price) }} VNĐ</td>
                                                    <td class="price">{{ number_format($total) }} VNĐ</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <i class="fas fa-tachometer-alt"></i>
                                <p>Chưa có dữ liệu chỉ số công tơ</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices Tab -->
        <div id="tab-invoices" class="tab-content" style="display: none;">
            <!-- Invoice Filters -->
            <div class="invoice-filters mb-4">
                <div class="filter-tabs">
                    <button class="filter-tab active" data-status="all">Tất cả</button>
                    <button class="filter-tab" data-status="draft">Nháp</button>
                    <button class="filter-tab" data-status="issued">Chưa trả</button>
                    <button class="filter-tab" data-status="paid">Đã trả</button>
                    <button class="filter-tab" data-status="overdue">Quá hạn</button>
                    <button class="filter-tab" data-status="cancelled">Đã hủy</button>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Mã hóa đơn</th>
                            <th>Ngày phát hành</th>
                            <th>Ngày đến hạn</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->invoice_no ?? 'HD' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
                                <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                                <td class="price">{{ number_format($invoice->total_amount) }} VNĐ</td>
                                <td>
                                    <span class="status-badge status-{{ $invoice->status }}">
                                        @switch($invoice->status)
                                            @case('draft')
                                                <i class="fas fa-edit"></i> Nháp
                                                @break
                                            @case('issued')
                                                <i class="fas fa-clock"></i> Chưa trả
                                                @break
                                            @case('paid')
                                                <i class="fas fa-check"></i> Đã trả
                                                @break
                                            @case('overdue')
                                                <i class="fas fa-exclamation-triangle"></i> Quá hạn
                                                @break
                                            @case('cancelled')
                                                <i class="fas fa-times"></i> Đã hủy
                                                @break
                                        @endswitch
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        @include('tenant.components.button', [
                                            'type' => 'button',
                                            'variant' => 'outline-primary',
                                            'size' => 'sm',
                                            'icon' => 'fas fa-eye',
                                            'iconPosition' => 'only',
                                            'tooltip' => 'Xem chi tiết',
                                            'onclick' => "viewInvoice('{$invoice->id}')"
                                        ])
                                        @if($invoice->status === 'issued')
                                            @include('tenant.components.button', [
                                                'type' => 'button',
                                                'variant' => 'success',
                                                'size' => 'sm',
                                                'label' => 'Thanh toán',
                                                'icon' => 'fas fa-credit-card',
                                                'onclick' => "payInvoice('{$invoice->id}')"
                                            ])
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-file-invoice"></i>
                                        <p>Chưa có hóa đơn nào</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Invoices Pagination -->
            @if($invoices->hasPages())
                <div class="pagination-section mt-4">
                    {{ $invoices->appends(request()->query())->links('vendor.pagination.custom') }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Modals removed - features not yet implemented --}}
@endsection
