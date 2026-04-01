@extends('layouts.app')

@section('title', 'Hợp đồng của tôi')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/contracts.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
/* Contracts Container */
.contracts-container {
    background: linear-gradient(to bottom, #F0F4FF 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

/* Stats Cards with Blue Theme */
.stats-section-blue {
    margin-bottom: 2rem;
}

.stat-card-blue {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 2px solid var(--blue-border);
    cursor: pointer;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.stat-card-blue::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: var(--blue-gradient);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.stat-card-blue:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.2);
    border-color: var(--blue-light);
}

.stat-card-blue:hover::before {
    transform: scaleX(1);
}

/* Status-specific hover colors */
.stat-card-blue.active:hover,
.stat-card-blue[data-filter="active"]:hover {
    border-color: var(--status-active);
    box-shadow: 0 8px 30px rgba(40, 167, 69, 0.25);
}

.stat-card-blue.expiring:hover,
.stat-card-blue[data-filter="expiring"]:hover {
    border-color: var(--status-expiring);
    box-shadow: 0 8px 30px rgba(255, 152, 0, 0.25);
}

.stat-card-blue.expired:hover,
.stat-card-blue[data-filter="expired"]:hover {
    border-color: var(--status-expired);
    box-shadow: 0 8px 30px rgba(220, 53, 69, 0.25);
}

.stat-card-blue.total:hover,
.stat-card-blue[data-filter="all"]:hover {
    border-color: var(--status-all);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.25);
}

.stat-card-blue.active-filter {
    background: var(--blue-bg-light);
    border-color: var(--blue-primary) !important;
    border-width: 3px !important;
    box-shadow: 0 6px 25px rgba(39, 102, 236, 0.35);
    transform: translateY(-3px);
}

.stat-card-blue.active-filter::before {
    transform: scaleX(1);
    height: 5px;
    background: var(--blue-primary);
}

/* Override active-filter colors based on status - these will take precedence */
.stat-card-blue.active.active-filter {
    background: var(--status-active-light) !important;
    border-color: var(--status-active-border) !important;
    box-shadow: 0 6px 25px rgba(40, 167, 69, 0.4) !important;
}

.stat-card-blue.active.active-filter::before {
    background: var(--status-active-gradient) !important;
    height: 5px;
}

.stat-card-blue.expiring.active-filter {
    background: var(--status-expiring-light) !important;
    border-color: var(--status-expiring-border) !important;
    box-shadow: 0 6px 25px rgba(255, 152, 0, 0.4) !important;
}

.stat-card-blue.expiring.active-filter::before {
    background: var(--status-expiring-gradient) !important;
    height: 5px;
}

.stat-card-blue.expired.active-filter {
    background: var(--status-expired-light) !important;
    border-color: var(--status-expired-border) !important;
    box-shadow: 0 6px 25px rgba(220, 53, 69, 0.4) !important;
}

.stat-card-blue.expired.active-filter::before {
    background: var(--status-expired-gradient) !important;
    height: 5px;
}

.stat-card-blue.total.active-filter {
    background: var(--status-all-light) !important;
    border-color: var(--status-all-border) !important;
    box-shadow: 0 6px 25px rgba(39, 102, 236, 0.4) !important;
}

.stat-card-blue.total.active-filter::before {
    background: var(--status-all-gradient) !important;
    height: 5px;
}

.stat-card-blue .stat-icon {
    font-size: 2.5rem;
    color: var(--blue-primary);
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.stat-card-blue:hover .stat-icon {
    transform: scale(1.1);
}

.stat-card-blue .stat-content h3 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--blue-primary);
    margin-bottom: 0.5rem;
}

.stat-card-blue .stat-content p {
    font-size: 1rem;
    color: #666;
    margin: 0;
    font-weight: 500;
}

/* Status-specific colors for stat cards */
.stat-card-blue.active .stat-icon,
.stat-card-blue[data-filter="active"] .stat-icon {
    color: var(--status-active);
}

.stat-card-blue.active .stat-content h3,
.stat-card-blue[data-filter="active"] .stat-content h3 {
    color: var(--status-active);
}

.stat-card-blue.active.active-filter {
    background: var(--status-active-light);
    border-color: var(--status-active-border) !important;
}

.stat-card-blue.active.active-filter::before {
    background: var(--status-active-gradient);
}

.stat-card-blue.expiring .stat-icon,
.stat-card-blue[data-filter="expiring"] .stat-icon {
    color: var(--status-expiring);
}

.stat-card-blue.expiring .stat-content h3,
.stat-card-blue[data-filter="expiring"] .stat-content h3 {
    color: var(--status-expiring);
}

.stat-card-blue.expiring.active-filter {
    background: var(--status-expiring-light);
    border-color: var(--status-expiring-border) !important;
}

.stat-card-blue.expiring.active-filter::before {
    background: var(--status-expiring-gradient);
}

.stat-card-blue.expired .stat-icon,
.stat-card-blue[data-filter="expired"] .stat-icon {
    color: var(--status-expired);
}

.stat-card-blue.expired .stat-content h3,
.stat-card-blue[data-filter="expired"] .stat-content h3 {
    color: var(--status-expired);
}

.stat-card-blue.expired.active-filter {
    background: var(--status-expired-light);
    border-color: var(--status-expired-border) !important;
}

.stat-card-blue.expired.active-filter::before {
    background: var(--status-expired-gradient);
}

.stat-card-blue.total .stat-icon,
.stat-card-blue[data-filter="all"] .stat-icon {
    color: var(--status-all);
}

.stat-card-blue.total .stat-content h3,
.stat-card-blue[data-filter="all"] .stat-content h3 {
    color: var(--status-all);
}

.stat-card-blue.total.active-filter {
    background: var(--status-all-light);
    border-color: var(--status-all-border) !important;
}

.stat-card-blue.total.active-filter::before {
    background: var(--status-all-gradient);
}

/* Filter Section with Blue Theme */
.filter-section-blue {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
}

.search-box-blue {
    position: relative;
    display: flex;
    align-items: center;
}

.search-box-blue i {
    position: absolute;
    left: 1rem;
    color: var(--blue-primary);
    font-size: 1.1rem;
    z-index: 1;
}

.search-box-blue input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 3rem;
    border: 2px solid var(--blue-border);
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: var(--blue-bg-light);
}

.search-box-blue input:focus {
    outline: none;
    border-color: var(--blue-primary);
    background: white;
    box-shadow: 0 0 0 0.2rem rgba(39, 102, 236, 0.25);
}

.filter-tabs-blue {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.filter-tab-blue {
    padding: 0.75rem 1.5rem;
    border: 2px solid var(--blue-border);
    background: white;
    color: var(--blue-primary);
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
}

.filter-tab-blue:hover {
    background: var(--blue-bg-light);
    border-color: var(--blue-light);
    transform: translateY(-2px);
}

/* Status-specific hover colors for filter tabs */
.filter-tab-blue[data-status="active"]:hover:not(.active) {
    background: var(--status-active-light);
    border-color: var(--status-active);
    color: var(--status-active);
}

.filter-tab-blue[data-status="expiring"]:hover:not(.active) {
    background: var(--status-expiring-light);
    border-color: var(--status-expiring);
    color: var(--status-expiring);
}

.filter-tab-blue[data-status="expired"]:hover:not(.active) {
    background: var(--status-expired-light);
    border-color: var(--status-expired);
    color: var(--status-expired);
}

.filter-tab-blue[data-status="all"]:hover:not(.active) {
    background: var(--status-all-light);
    border-color: var(--status-all);
    color: var(--status-all);
}

.filter-tab-blue.active {
    background: var(--blue-gradient);
    color: white;
    border-color: var(--blue-primary);
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.3);
}

/* Status-specific colors for filter tabs */
.filter-tab-blue[data-status="active"].active {
    background: var(--status-active-gradient);
    border-color: var(--status-active-border);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.filter-tab-blue[data-status="expiring"].active {
    background: var(--status-expiring-gradient);
    border-color: var(--status-expiring-border);
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
}

.filter-tab-blue[data-status="expired"].active {
    background: var(--status-expired-gradient);
    border-color: var(--status-expired-border);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

.filter-tab-blue[data-status="all"].active {
    background: var(--status-all-gradient);
    border-color: var(--status-all-border);
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.3);
}

/* Contract Cards with Blue Theme */
.contract-card-blue {
    background: white;
    border-radius: 16px;
    padding: 0;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid var(--blue-border);
    overflow: hidden;
}

.contract-card-blue:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.15);
    border-color: var(--blue-light);
}

.contract-status-blue {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 1px solid var(--blue-border);
}

.contract-status-blue.active {
    background: #D4EDDA;
    color: #155724;
}

.contract-status-blue.expiring {
    background: #FFF3CD;
    color: #856404;
}

.contract-status-blue.expired {
    background: #F8D7DA;
    color: #721C24;
}

.contract-content-blue {
    padding: 1.5rem;
}

.contract-title-blue {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--blue-primary);
    margin-bottom: 1rem;
}

.property-address-blue {
    color: #666;
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.property-address-blue i {
    color: var(--blue-primary);
    margin-top: 0.25rem;
}

.contract-details-blue .detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #F5F5F5;
}

.contract-details-blue .detail-item:last-child {
    border-bottom: none;
}

.contract-details-blue .label {
    font-weight: 600;
    color: #666;
    flex: 0 0 40%;
}

.contract-details-blue .value {
    color: #333;
    text-align: right;
    flex: 1;
}

.contract-details-blue .value.price {
    color: var(--blue-primary);
    font-weight: 700;
    font-size: 1.1rem;
}

.contract-actions-blue {
    padding: 1rem 1.5rem;
    background: var(--blue-bg-light);
    border-top: 1px solid var(--blue-border);
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.contract-actions-blue .btn {
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.contract-actions-blue .btn:hover {
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

/* HTMX Loading */
.htmx-indicator-blue {
    text-align: center;
    padding: 3rem;
}

.htmx-indicator-blue .spinner-border {
    color: var(--blue-primary);
    width: 3rem;
    height: 3rem;
}

/* Responsive */
@media (max-width: 768px) {
    .contracts-header-blue {
        padding: 1.5rem;
    }
    
    .contracts-header-blue .page-title {
        font-size: 1.5rem;
    }
    
    .stat-card-blue {
        padding: 1.5rem;
    }
    
    .filter-section-blue {
        padding: 1rem;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/user/contracts.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/user/contracts-htmx.js') }}?v={{ time() }}"></script>
@endpush

@section('content')
<div class="page-container-blue">
    <div class="container">
        <!-- Page Header -->
        @include('tenant.components.page-header', [
            'title' => 'Hợp đồng của tôi',
            'subtitle' => 'Quản lý và theo dõi các hợp đồng thuê nhà',
            'icon' => 'fas fa-file-contract',
            'actions' => [
                [
                        'label' => 'Quay lại Dashboard',
                    'url' => route('tenant.dashboard'),
                        'icon' => 'fas fa-arrow-left',
                    'variant' => 'outline-secondary'
                ]
            ]
                    ])

        <!-- Stats Cards -->
        <div class="stats-section-blue" id="stats-container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card-blue active {{ request('status') == 'active' ? 'active-filter' : '' }}" 
                         data-filter="active"
                         hx-get="{{ route('tenant.contracts.index', ['status' => 'active', 'search' => request('search')]) }}"
                         hx-target="#contracts-list-container"
                         hx-swap="innerHTML"
                         hx-push-url="true"
                         hx-indicator="#htmx-loading"
                         title="Click để lọc hợp đồng đang hiệu lực">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['active'] }}</h3>
                            <p><i class="fas fa-check-circle me-2"></i>Đang hiệu lực</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card-blue expiring {{ request('status') == 'expiring' ? 'active-filter' : '' }}" 
                         data-filter="expiring"
                         hx-get="{{ route('tenant.contracts.index', ['status' => 'expiring', 'search' => request('search')]) }}"
                         hx-target="#contracts-list-container"
                         hx-swap="innerHTML"
                         hx-push-url="true"
                         hx-indicator="#htmx-loading"
                         title="Click để lọc hợp đồng sắp hết hạn">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['expiring'] }}</h3>
                            <p>Sắp hết hạn</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card-blue expired {{ request('status') == 'expired' ? 'active-filter' : '' }}" 
                         data-filter="expired"
                         hx-get="{{ route('tenant.contracts.index', ['status' => 'expired', 'search' => request('search')]) }}"
                         hx-target="#contracts-list-container"
                         hx-swap="innerHTML"
                         hx-push-url="true"
                         hx-indicator="#htmx-loading"
                         title="Click để lọc hợp đồng đã hết hạn">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['expired'] }}</h3>
                            <p>Đã hết hạn</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card-blue total {{ request('status', 'all') == 'all' ? 'active-filter' : '' }}" 
                         data-filter="all"
                         hx-get="{{ route('tenant.contracts.index', ['status' => 'all', 'search' => request('search')]) }}"
                         hx-target="#contracts-list-container"
                         hx-swap="innerHTML"
                         hx-push-url="true"
                         hx-indicator="#htmx-loading"
                         title="Click để xem tất cả hợp đồng">
                        <div class="stat-icon">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['total'] }}</h3>
                            <p>Tổng hợp đồng</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter and Search -->
        @php
            $filterTabs = [
                [
                    'label' => 'Tất cả',
                    'value' => 'all',
                    'active' => request('status', 'all') == 'all',
                    'url' => route('tenant.contracts.index', ['status' => 'all', 'search' => request('search')]),
                    'icon' => 'fas fa-folder'
                ],
                [
                    'label' => 'Đang hiệu lực',
                    'value' => 'active',
                    'active' => request('status') == 'active',
                    'url' => route('tenant.contracts.index', ['status' => 'active', 'search' => request('search')]),
                    'icon' => 'fas fa-check-circle'
                ],
                [
                    'label' => 'Sắp hết hạn',
                    'value' => 'expiring',
                    'active' => request('status') == 'expiring',
                    'url' => route('tenant.contracts.index', ['status' => 'expiring', 'search' => request('search')]),
                    'icon' => 'fas fa-clock'
                ],
                [
                    'label' => 'Đã hết hạn',
                    'value' => 'expired',
                    'active' => request('status') == 'expired',
                    'url' => route('tenant.contracts.index', ['status' => 'expired', 'search' => request('search')]),
                    'icon' => 'fas fa-times-circle'
                ]
            ];
        @endphp
        <div class="filter-section-blue">
            <div id="filterForm">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="search-box-blue">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Tìm kiếm theo tên phòng, địa chỉ..." 
                                   id="searchInput"
                                   autocomplete="off">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="filter-tabs-blue">
                            @foreach($filterTabs as $tab)
                            <button type="button" 
                                    class="filter-tab-blue {{ $tab['active'] ? 'active' : '' }}" 
                                    data-status="{{ $tab['value'] }}"
                                    hx-get="{{ $tab['url'] }}"
                                    hx-target="#contracts-list-container"
                                    hx-swap="innerHTML"
                                    hx-push-url="true"
                                    hx-indicator="#htmx-loading"
                                    hx-trigger="click">
                                @if(isset($tab['icon']))
                                    <i class="{{ $tab['icon'] }} me-2"></i>
                                @endif
                                {{ $tab['label'] }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <input type="hidden" name="status" id="statusInput" value="{{ request('status', 'all') }}">
            </div>
        </div>

        <!-- HTMX Loading Indicator -->
        <div id="htmx-loading" class="htmx-indicator-blue" style="display: none;">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
            <p class="mt-2 text-muted">Đang tải dữ liệu...</p>
        </div>

        <!-- Contracts List -->
        <div class="contracts-list" id="contracts-list-container">
            @include('tenant.contract.partials.contracts-list', ['contracts' => $contracts])
        </div>

    </div>
</div>

<!-- Contract Detail Modal -->
<div class="modal fade" id="contractDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1E4FC8 0%, #2766ec 50%, #4A85F0 100%); color: white;">
                <h5 class="modal-title">Chi tiết hợp đồng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="contract-detail-content" id="contractDetailContent">
                    <div class="text-center">
                        <div class="spinner-border" style="color: #2766ec;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Đang tải thông tin hợp đồng...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                {{-- Download and Print buttons removed - features not yet implemented --}}
            </div>
        </div>
    </div>
</div>

{{-- Modals removed - features not yet implemented (Renewal, Download) --}}
@endsection
