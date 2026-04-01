@extends('layouts.app')

@section('title', 'Ticket của tôi')

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/tenant/tickets.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
/* Tickets Container with Blue Theme */
.tickets-list-blue {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
}

/* Ticket Cards with Blue Theme */
.ticket-card-blue {
    background: white;
    border: 1px solid var(--blue-border);
    border-radius: 16px;
    padding: 0;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.ticket-card-blue:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.15);
    border-color: var(--blue-light);
}

.ticket-header-blue {
    padding: 1.5rem;
    border-bottom: 1px solid var(--blue-border);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.ticket-info-blue {
    flex: 1;
}

.ticket-title-blue h5 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--blue-primary);
    margin-bottom: 0.5rem;
}

.ticket-title-blue h5 a {
    color: var(--blue-primary);
    text-decoration: none;
    transition: color 0.3s ease;
}

.ticket-title-blue h5 a:hover {
    color: var(--blue-dark);
}

.ticket-meta-blue {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 0.5rem;
}

.ticket-id-blue {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.ticket-date-blue {
    font-size: 0.875rem;
    color: #6b7280;
}

.ticket-badges-blue {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.priority-badge-blue {
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.priority-badge-blue.priority-low {
    background: #dbeafe;
    color: #1e40af;
}

.priority-badge-blue.priority-medium {
    background: #fef3c7;
    color: #92400e;
}

.priority-badge-blue.priority-high {
    background: #fed7aa;
    color: #9a3412;
}

.priority-badge-blue.priority-urgent {
    background: #fee2e2;
    color: #991b1b;
}

.status-badge-blue {
    padding: 0.375rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge-blue.status-open {
    background: #3b82f6;
    color: #ffffff;
}

.status-badge-blue.status-in_progress {
    background: #f59e0b;
    color: #ffffff;
}

.status-badge-blue.status-resolved {
    background: #10b981;
    color: #ffffff;
}

.status-badge-blue.status-closed {
    background: #6b7280;
    color: #ffffff;
}

.status-badge-blue.status-cancelled {
    background: #ef4444;
    color: #ffffff;
}

.ticket-body-blue {
    padding: 1.5rem;
}

.ticket-description-blue {
    margin-bottom: 1.5rem;
    color: #4b5563;
    line-height: 1.6;
}

.ticket-details-blue {
    background: var(--blue-bg-light);
    border-radius: 12px;
    padding: 1.5rem;
}

.detail-item-blue {
    margin-bottom: 1rem;
}

.detail-item-blue:last-child {
    margin-bottom: 0;
}

.detail-item-blue label {
    font-weight: 600;
    color: var(--blue-primary);
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    display: block;
}

.detail-item-blue span {
    color: #4b5563;
    font-size: 0.95rem;
}

.address-info-blue {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.address-item-blue {
    display: flex;
    gap: 0.5rem;
}

.address-label-blue {
    font-weight: 600;
    color: #6b7280;
    min-width: 80px;
    font-size: 0.875rem;
}

.address-value-blue {
    color: #4b5563;
    font-size: 0.95rem;
}

.ticket-actions-blue {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--blue-border);
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

/* Empty state with Blue Theme */
.empty-state-blue {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    border: 1px solid var(--blue-border);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.empty-icon-blue {
    font-size: 4rem;
    color: var(--blue-primary);
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state-blue h3 {
    color: var(--blue-primary);
    margin-bottom: 8px;
    font-weight: 600;
}

.empty-state-blue p {
    color: #6b7280;
    margin: 0 0 1.5rem 0;
}

/* Priority Filter with Blue Theme */
.priority-filter-blue {
    width: 100%;
}

.priority-select-blue {
    border-radius: 12px;
    border: 2px solid var(--blue-border);
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: var(--blue-bg-light);
}

.priority-select-blue:focus {
    border-color: var(--blue-primary);
    background: white;
    box-shadow: 0 0 0 0.2rem rgba(39, 102, 236, 0.25);
    outline: none;
}

/* HTMX Loading Indicator */
.htmx-indicator-blue {
    text-align: center;
    padding: 2rem;
}

.htmx-indicator-blue .spinner-border {
    color: var(--blue-primary);
}

/* Pagination */
.pagination-section-blue {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--blue-border);
}

/* Status-specific colors for ticket stat cards */
.stat-card-blue.open .stat-icon,
.stat-card-blue[data-filter="open"] .stat-icon {
    color: var(--status-ticket-open);
}

.stat-card-blue.open .stat-content h3,
.stat-card-blue[data-filter="open"] .stat-content h3 {
    color: var(--status-ticket-open);
}

.stat-card-blue.open:hover,
.stat-card-blue[data-filter="open"]:hover {
    border-color: var(--status-ticket-open);
    box-shadow: 0 8px 30px rgba(59, 130, 246, 0.25);
}

.stat-card-blue.open.active-filter {
    background: var(--status-ticket-open-light) !important;
    border-color: var(--status-ticket-open-border) !important;
    box-shadow: 0 6px 25px rgba(59, 130, 246, 0.4) !important;
}

.stat-card-blue.open.active-filter::before {
    background: var(--status-ticket-open-gradient) !important;
    height: 5px;
}

.stat-card-blue.in_progress .stat-icon,
.stat-card-blue[data-filter="in_progress"] .stat-icon {
    color: var(--status-ticket-in_progress);
}

.stat-card-blue.in_progress .stat-content h3,
.stat-card-blue[data-filter="in_progress"] .stat-content h3 {
    color: var(--status-ticket-in_progress);
}

.stat-card-blue.in_progress:hover,
.stat-card-blue[data-filter="in_progress"]:hover {
    border-color: var(--status-ticket-in_progress);
    box-shadow: 0 8px 30px rgba(245, 158, 11, 0.25);
}

.stat-card-blue.in_progress.active-filter {
    background: var(--status-ticket-in_progress-light) !important;
    border-color: var(--status-ticket-in_progress-border) !important;
    box-shadow: 0 6px 25px rgba(245, 158, 11, 0.4) !important;
}

.stat-card-blue.in_progress.active-filter::before {
    background: var(--status-ticket-in_progress-gradient) !important;
    height: 5px;
}

.stat-card-blue.resolved .stat-icon,
.stat-card-blue[data-filter="resolved"] .stat-icon {
    color: var(--status-ticket-resolved);
}

.stat-card-blue.resolved .stat-content h3,
.stat-card-blue[data-filter="resolved"] .stat-content h3 {
    color: var(--status-ticket-resolved);
}

.stat-card-blue.resolved:hover,
.stat-card-blue[data-filter="resolved"]:hover {
    border-color: var(--status-ticket-resolved);
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.25);
}

.stat-card-blue.resolved.active-filter {
    background: var(--status-ticket-resolved-light) !important;
    border-color: var(--status-ticket-resolved-border) !important;
    box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4) !important;
}

.stat-card-blue.resolved.active-filter::before {
    background: var(--status-ticket-resolved-gradient) !important;
    height: 5px;
}

.stat-card-blue.closed .stat-icon,
.stat-card-blue[data-filter="closed"] .stat-icon {
    color: var(--status-ticket-closed);
}

.stat-card-blue.closed .stat-content h3,
.stat-card-blue[data-filter="closed"] .stat-content h3 {
    color: var(--status-ticket-closed);
}

.stat-card-blue.closed:hover,
.stat-card-blue[data-filter="closed"]:hover {
    border-color: var(--status-ticket-closed);
    box-shadow: 0 8px 30px rgba(107, 114, 128, 0.25);
}

.stat-card-blue.closed.active-filter {
    background: var(--status-ticket-closed-light) !important;
    border-color: var(--status-ticket-closed-border) !important;
    box-shadow: 0 6px 25px rgba(107, 114, 128, 0.4) !important;
}

.stat-card-blue.closed.active-filter::before {
    background: var(--status-ticket-closed-gradient) !important;
    height: 5px;
}

.stat-card-blue.cancelled .stat-icon,
.stat-card-blue[data-filter="cancelled"] .stat-icon {
    color: var(--status-ticket-cancelled);
}

.stat-card-blue.cancelled .stat-content h3,
.stat-card-blue[data-filter="cancelled"] .stat-content h3 {
    color: var(--status-ticket-cancelled);
}

.stat-card-blue.cancelled:hover,
.stat-card-blue[data-filter="cancelled"]:hover {
    border-color: var(--status-ticket-cancelled);
    box-shadow: 0 8px 30px rgba(239, 68, 68, 0.25);
}

.stat-card-blue.cancelled.active-filter {
    background: var(--status-ticket-cancelled-light) !important;
    border-color: var(--status-ticket-cancelled-border) !important;
    box-shadow: 0 6px 25px rgba(239, 68, 68, 0.4) !important;
}

.stat-card-blue.cancelled.active-filter::before {
    background: var(--status-ticket-cancelled-gradient) !important;
    height: 5px;
}

/* Status-specific colors for ticket filter tabs */
.filter-tab-blue[data-status="open"]:hover:not(.active) {
    background: var(--status-ticket-open-light);
    border-color: var(--status-ticket-open);
    color: var(--status-ticket-open);
}

.filter-tab-blue[data-status="open"].active {
    background: var(--status-ticket-open-gradient);
    border-color: var(--status-ticket-open-border);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.filter-tab-blue[data-status="in_progress"]:hover:not(.active) {
    background: var(--status-ticket-in_progress-light);
    border-color: var(--status-ticket-in_progress);
    color: var(--status-ticket-in_progress);
}

.filter-tab-blue[data-status="in_progress"].active {
    background: var(--status-ticket-in_progress-gradient);
    border-color: var(--status-ticket-in_progress-border);
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
}

.filter-tab-blue[data-status="resolved"]:hover:not(.active) {
    background: var(--status-ticket-resolved-light);
    border-color: var(--status-ticket-resolved);
    color: var(--status-ticket-resolved);
}

.filter-tab-blue[data-status="resolved"].active {
    background: var(--status-ticket-resolved-gradient);
    border-color: var(--status-ticket-resolved-border);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.filter-tab-blue[data-status="closed"]:hover:not(.active) {
    background: var(--status-ticket-closed-light);
    border-color: var(--status-ticket-closed);
    color: var(--status-ticket-closed);
}

.filter-tab-blue[data-status="closed"].active {
    background: var(--status-ticket-closed-gradient);
    border-color: var(--status-ticket-closed-border);
    box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
}

.filter-tab-blue[data-status="cancelled"]:hover:not(.active) {
    background: var(--status-ticket-cancelled-light);
    border-color: var(--status-ticket-cancelled);
    color: var(--status-ticket-cancelled);
}

.filter-tab-blue[data-status="cancelled"].active {
    background: var(--status-ticket-cancelled-gradient);
    border-color: var(--status-ticket-cancelled-border);
    box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/tenant/tickets.js') }}?v={{ time() }}"></script>
<script>
// Page-specific initialization
document.addEventListener('DOMContentLoaded', function() {
    if (typeof TicketModule !== 'undefined') {
        TicketModule.initIndex();
    }
});
</script>
@endpush

@section('content')
<div class="page-container-blue">
    <div class="container">
        <!-- Error Messages -->
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Page Header -->
        @include('tenant.components.page-header', [
            'title' => 'Ticket của tôi',
            'subtitle' => 'Quản lý và theo dõi các yêu cầu sửa chữa/bảo trì',
            'icon' => 'fas fa-ticket-alt',
            'actions' => [
                ['label' => 'Tạo ticket mới', 'url' => route('tenant.tickets.create'), 'icon' => 'fas fa-plus', 'variant' => 'outline-primary'],
                ['label' => 'Về Dashboard', 'url' => route('tenant.dashboard'), 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary'],
            ]
        ])

        <!-- Stats Cards -->
        @php
            $status = request('status', 'all');
            $priority = request('priority', 'all');
            $search = request('search', '');
            
            $ticketStats = [
                [
                    'icon' => 'fas fa-folder-open',
                    'value' => $stats['open'] ?? 0,
                    'label' => 'Đang mở',
                    'active' => $status === 'open',
                    'data-filter' => 'open',
                    'statusClass' => 'open',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'open', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để lọc ticket đang mở'
                ],
                [
                    'icon' => 'fas fa-cog',
                    'value' => $stats['in_progress'] ?? 0,
                    'label' => 'Đang xử lý',
                    'active' => $status === 'in_progress',
                    'data-filter' => 'in_progress',
                    'statusClass' => 'in_progress',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'in_progress', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để lọc ticket đang xử lý'
                ],
                [
                    'icon' => 'fas fa-check-circle',
                    'value' => $stats['resolved'] ?? 0,
                    'label' => 'Đã giải quyết',
                    'active' => $status === 'resolved',
                    'data-filter' => 'resolved',
                    'statusClass' => 'resolved',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'resolved', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để lọc ticket đã giải quyết'
                ],
                [
                    'icon' => 'fas fa-archive',
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'active' => $status === 'all',
                    'data-filter' => 'all',
                    'statusClass' => 'total',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'all', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để xem tất cả ticket'
                ]
            ];
        @endphp
        @include('tenant.components.stats-cards', [
            'stats' => $ticketStats,
            'columns' => 4,
            'class' => 'mb-4'
        ])

        <!-- Filter and Search -->
        @php
            $filterTabs = [
                [
                    'label' => 'Tất cả',
                    'value' => 'all',
                    'active' => $status === 'all',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'all', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-folder'
                ],
                [
                    'label' => 'Đang mở',
                    'value' => 'open',
                    'active' => $status === 'open',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'open', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-folder-open'
                ],
                [
                    'label' => 'Đang xử lý',
                    'value' => 'in_progress',
                    'active' => $status === 'in_progress',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'in_progress', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-cog'
                ],
                [
                    'label' => 'Đã giải quyết',
                    'value' => 'resolved',
                    'active' => $status === 'resolved',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'resolved', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-check-circle'
                ],
                [
                    'label' => 'Đã đóng',
                    'value' => 'closed',
                    'active' => $status === 'closed',
                    'hx-get' => route('tenant.tickets.index', ['status' => 'closed', 'priority' => $priority, 'search' => $search]),
                    'hx-target' => '#tickets-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'hx-trigger' => 'click',
                    'icon' => 'fas fa-archive'
                ]
            ];
            
            $priorities = \App\Models\TicketPriority::orderBy('id')->get();
            $additionalFields = '<div class="priority-filter-blue">
                <select class="form-select priority-select-blue" name="priority" id="priorityFilter" 
                        hx-get="' . route('tenant.tickets.index') . '"
                        hx-target="#tickets-list-container"
                        hx-swap="innerHTML"
                        hx-push-url="true"
                        hx-indicator="#htmx-loading"
                        hx-trigger="change"
                        hx-include="[name=\'search\'], [name=\'status\']">
                    <option value="all">Tất cả độ ưu tiên</option>';
            foreach ($priorities as $priorityOption) {
                $additionalFields .= '<option value="' . $priorityOption->key_code . '" ' . ($priority === $priorityOption->key_code ? 'selected' : '') . '>' . $priorityOption->name . '</option>';
            }
            $additionalFields .= '</select>
            </div>';
        @endphp
        @include('tenant.components.filter-section', [
            'searchPlaceholder' => 'Tìm kiếm ticket...',
            'searchValue' => $search,
            'filters' => $filterTabs,
            'formId' => 'filterForm',
            'searchInputId' => 'searchInput',
            'hxGet' => route('tenant.tickets.index'),
            'hxTarget' => '#tickets-list-container',
            'hxSwap' => 'innerHTML',
            'hxPushUrl' => 'true',
            'hxIndicator' => '#htmx-loading',
            'hxTrigger' => 'input delay:500ms from:#searchInput, change from:#priorityFilter',
            'additionalFields' => $additionalFields
        ])

        <!-- HTMX Loading Indicator -->
        <div id="htmx-loading" class="htmx-indicator-blue" style="display: none;">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
            <p class="mt-2 text-muted">Đang tải dữ liệu...</p>
        </div>

        <!-- Tickets List -->
        <div class="tickets-list-blue" id="tickets-list-container">
            <div class="row">
                @include('tenant.ticket.partials.tickets-list', ['tickets' => $tickets])
            </div>
        </div>
    </div>
</div>
@endsection
