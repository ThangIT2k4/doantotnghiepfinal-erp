@extends('layouts.app')

@section('title', 'Đánh giá của tôi')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/reviews.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/user/reviews-enhanced.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
/* Reviews Container with Blue Theme */
.reviews-container {
    background: linear-gradient(to bottom, #F0F4FF 0%, #ffffff 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

/* Review Cards with Blue Theme */
.review-card-blue {
    background: white;
    border-radius: 16px;
    padding: 0;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid var(--blue-border);
    overflow: hidden;
}

.review-card-blue:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.15);
    border-color: var(--blue-light);
}

.review-status-blue {
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 1px solid var(--blue-border);
}

.review-status-blue.published {
    background: #D4EDDA;
    color: #155724;
}

.review-status-blue.replied {
    background: #D1ECF1;
    color: #0C5460;
}

.review-content-blue {
    padding: 1.5rem;
}

.review-title-blue {
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

.review-rating-blue {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.review-rating-blue .stars {
    color: #FFD700;
}

.review-rating-blue .rating-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--blue-primary);
}

.review-text-blue {
    margin-bottom: 1rem;
}

.review-text-blue h5 {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.review-text-blue p {
    color: #666;
    font-style: italic;
}

.review-meta-blue {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #F5F5F5;
}

.review-actions-blue {
    padding: 1rem 1.5rem;
    background: var(--blue-bg-light);
    border-top: 1px solid var(--blue-border);
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.review-actions-blue .btn {
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.review-actions-blue .btn:hover {
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

/* Status-specific colors for review stat cards */
.stat-card-blue.published .stat-icon,
.stat-card-blue[data-filter="published"] .stat-icon {
    color: var(--status-active);
}

.stat-card-blue.published .stat-content h3,
.stat-card-blue[data-filter="published"] .stat-content h3 {
    color: var(--status-active);
}

.stat-card-blue.published.active-filter {
    background: var(--status-active-light) !important;
    border-color: var(--status-active-border) !important;
    box-shadow: 0 6px 25px rgba(40, 167, 69, 0.4) !important;
}

.stat-card-blue.published.active-filter::before {
    background: var(--status-active-gradient);
    height: 5px;
}

.stat-card-blue.replied .stat-icon,
.stat-card-blue[data-filter="replied"] .stat-icon {
    color: #0C5460;
}

.stat-card-blue.replied .stat-content h3,
.stat-card-blue[data-filter="replied"] .stat-content h3 {
    color: #0C5460;
}

.stat-card-blue.replied.active-filter {
    background: #D1ECF1 !important;
    border-color: #0C5460 !important;
    box-shadow: 0 6px 25px rgba(12, 84, 96, 0.4) !important;
}

.stat-card-blue.replied.active-filter::before {
    background: linear-gradient(135deg, #0C5460 0%, #17A2B8 100%);
    height: 5px;
}

/* Filter tab colors for reviews */
.filter-tab-blue[data-status="published"].active {
    background: var(--status-active-gradient);
    border-color: var(--status-active-border);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.filter-tab-blue[data-status="replied"].active {
    background: linear-gradient(135deg, #0C5460 0%, #17A2B8 100%);
    border-color: #0C5460;
    box-shadow: 0 4px 15px rgba(12, 84, 96, 0.3);
}

.filter-tab-blue[data-status="pending"].active {
    background: var(--status-expiring-gradient);
    border-color: var(--status-expiring-border);
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .review-content-blue {
        padding: 1rem;
    }
    
    .review-actions-blue {
        padding: 1rem;
    }
}
</style>
@endpush

@push('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="{{ asset('assets/js/notifications.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/user/reviews.js') }}?v={{ time() }}"></script>
<script>
// Page-specific initialization
document.addEventListener('DOMContentLoaded', function() {
    ReviewsModule.initIndex();
    
    // Handle delete buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-review-btn')) {
            const btn = e.target.closest('.delete-review-btn');
            e.preventDefault();
            const reviewId = btn.dataset.reviewId;
            const reviewTitle = btn.dataset.reviewTitle || 'đánh giá này';
            
            if (typeof Notify !== 'undefined' && Notify.confirmDelete) {
                Notify.confirmDelete(reviewTitle, function() {
                    fetch(`/tenant/reviews/${reviewId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Notify.success(data.message);
                            // Reload using HTMX if available
                            if (typeof htmx !== 'undefined') {
                                htmx.ajax('GET', '{{ route("tenant.reviews.index") }}', {
                                    target: '#reviews-list-container',
                                    swap: 'innerHTML'
                                });
                            } else {
                            window.location.reload();
                            }
                        } else {
                            Notify.error(data.message || 'Có lỗi xảy ra khi xóa đánh giá');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting review:', error);
                        Notify.error('Có lỗi xảy ra khi xóa đánh giá: ' + error.message);
                    });
                });
            } else {
                if (confirm(`Bạn có chắc chắn muốn xóa ${reviewTitle}?`)) {
                    fetch(`/tenant/reviews/${reviewId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert('Đánh giá đã được xóa thành công!');
                            window.location.reload();
                        } else {
                            alert('Lỗi: ' + (data.message || 'Có lỗi xảy ra khi xóa đánh giá'));
                        }
                    })
                    .catch(error => {
                        console.error('Fallback delete error:', error);
                        alert('Có lỗi xảy ra khi xóa đánh giá: ' + error.message);
                    });
                }
            }
        }
    });
    
    // Handle thank buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.thank-btn')) {
            const btn = e.target.closest('.thank-btn');
            e.preventDefault();
            const reviewId = btn.dataset.reviewId;
            
            if (typeof Notify !== 'undefined' && Notify.confirm) {
                Notify.confirm({
                    title: 'Gửi lời cảm ơn',
                    message: 'Bạn có muốn gửi lời cảm ơn đến chủ nhà không?',
                    type: 'info',
                    confirmText: 'Gửi cảm ơn',
                    onConfirm: function() {
                        ReviewsModule.thankLandlord(reviewId);
                    }
                });
            } else {
                ReviewsModule.thankLandlord(reviewId);
            }
        }
    });
    
    // Handle share buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.share-btn')) {
            const btn = e.target.closest('.share-btn');
            e.preventDefault();
            const reviewId = btn.dataset.reviewId;
            ReviewsModule.shareReview(reviewId);
        }
    });
});
</script>
@endpush

@section('content')
<div class="page-container-blue">
    <div class="container">
        <!-- Page Header -->
        @include('tenant.components.page-header', [
            'title' => 'Đánh giá của tôi',
            'subtitle' => 'Viết đánh giá và theo dõi phản hồi từ chủ nhà',
            'icon' => 'fas fa-star',
            'actions' => [
                [
                    'label' => 'Quay lại Dashboard',
                    'url' => route('tenant.dashboard'),
                    'icon' => 'fas fa-arrow-left',
                    'variant' => 'outline-secondary'
                ],
                [
                    'label' => 'Viết đánh giá',
                    'url' => route('tenant.reviews.create'),
                    'icon' => 'fas fa-edit',
                    'variant' => 'outline-primary'
                ]
            ]
        ])

        <!-- Error Messages -->
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Success Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Stats Cards -->
        @php
            $statsData = [
                [
                    'icon' => 'fas fa-star',
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng đánh giá',
                    'active' => request('status', 'all') == 'all',
                    'data-filter' => 'all',
                    'hx-get' => route('tenant.reviews.index', ['status' => 'all', 'search' => request('search'), 'rating' => request('rating')]),
                    'hx-target' => '#reviews-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để xem tất cả đánh giá',
                    'amount' => ($stats['avg_rating'] ?? 0) . '/5 ⭐',
                    'statusClass' => 'total'
                ],
                [
                    'icon' => 'fas fa-clock',
                    'value' => $stats['pending'] ?? 0,
                    'label' => 'Chờ đánh giá',
                    'active' => request('status') == 'pending',
                    'data-filter' => 'pending',
                    'hx-get' => route('tenant.reviews.index', ['status' => 'pending', 'search' => request('search'), 'rating' => request('rating')]),
                    'hx-target' => '#reviews-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để xem phòng chờ đánh giá',
                    'statusClass' => 'pending'
                ],
                [
                    'icon' => 'fas fa-check-circle',
                    'value' => ($stats['total'] ?? 0) - ($stats['pending'] ?? 0),
                    'label' => 'Đã đăng',
                    'active' => request('status') == 'published',
                    'data-filter' => 'published',
                    'hx-get' => route('tenant.reviews.index', ['status' => 'published', 'search' => request('search'), 'rating' => request('rating')]),
                    'hx-target' => '#reviews-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để xem đánh giá đã đăng',
                    'statusClass' => 'published'
                ],
                [
                    'icon' => 'fas fa-reply',
                    'value' => $stats['replied'] ?? 0,
                    'label' => 'Có phản hồi',
                    'active' => request('status') == 'replied',
                    'data-filter' => 'replied',
                    'hx-get' => route('tenant.reviews.index', ['status' => 'replied', 'search' => request('search'), 'rating' => request('rating')]),
                    'hx-target' => '#reviews-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading',
                    'title' => 'Click để xem đánh giá có phản hồi',
                    'statusClass' => 'replied'
                ]
            ];
        @endphp
        @include('tenant.components.stats-cards', [
            'stats' => $statsData,
            'columns' => 4
        ])

        <!-- Filter and Search -->
        @php
            $filterTabs = [
                [
                    'label' => 'Tất cả',
                    'value' => 'all',
                    'active' => request('status', 'all') == 'all',
                    'icon' => 'fas fa-folder',
                    'hx-get' => route('tenant.reviews.index', ['status' => 'all', 'search' => request('search'), 'rating' => request('rating')]),
                    'hx-target' => '#reviews-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading'
                ],
                [
                    'label' => 'Chờ đánh giá',
                    'value' => 'pending',
                    'active' => request('status') == 'pending',
                    'icon' => 'fas fa-clock',
                    'hx-get' => route('tenant.reviews.index', ['status' => 'pending', 'search' => request('search'), 'rating' => request('rating')]),
                    'hx-target' => '#reviews-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading'
                ],
                [
                    'label' => 'Đã đăng',
                    'value' => 'published',
                    'active' => request('status') == 'published',
                    'icon' => 'fas fa-check-circle',
                    'hx-get' => route('tenant.reviews.index', ['status' => 'published', 'search' => request('search'), 'rating' => request('rating')]),
                    'hx-target' => '#reviews-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading'
                ],
                [
                    'label' => 'Có phản hồi',
                    'value' => 'replied',
                    'active' => request('status') == 'replied',
                    'icon' => 'fas fa-reply',
                    'hx-get' => route('tenant.reviews.index', ['status' => 'replied', 'search' => request('search'), 'rating' => request('rating')]),
                    'hx-target' => '#reviews-list-container',
                    'hx-swap' => 'innerHTML',
                    'hx-push-url' => 'true',
                    'hx-indicator' => '#htmx-loading'
                ]
            ];
        @endphp
        @include('tenant.components.filter-section', [
            'searchPlaceholder' => 'Tìm kiếm theo tên phòng, địa chỉ...',
            'searchValue' => request('search', ''),
            'filters' => $filterTabs,
            'formId' => 'filterForm',
            'searchInputId' => 'searchInput',
            'hxGet' => route('tenant.reviews.index'),
            'hxTarget' => '#reviews-list-container',
            'hxSwap' => 'innerHTML',
            'hxPushUrl' => 'true',
            'hxIndicator' => '#htmx-loading',
            'hxTrigger' => 'input delay:500ms from:#searchInput',
            'additionalFields' => view('tenant.reviews.partials.rating-filter', ['selectedRating' => request('rating')])->render()
        ])

        <!-- HTMX Loading Indicator -->
        <div id="htmx-loading" class="htmx-indicator-blue" style="display: none;">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
            <p class="mt-2 text-muted">Đang tải dữ liệu...</p>
        </div>

        <!-- Reviews List -->
        <div class="reviews-list" id="reviews-list-container">
            @include('tenant.reviews.partials.reviews-list', ['reviews' => $reviews])
        </div>

    </div>
</div>
@endsection
