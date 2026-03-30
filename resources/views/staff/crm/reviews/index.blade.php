@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Đánh giá')

@section('content')
<main class="main-content">
<div class="container-fluid">
        {{-- 1. Page Header với solid variants --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                @include('staff.components.index-page-header', [
                    'title' => 'Quản lý Đánh giá',
                    'subtitle' => 'Theo dõi và quản lý các đánh giá từ khách thuê',
                    'icon' => 'fas fa-star'
                ])
            </div>
            <div>
                <a href="{{ route('staff.reviews.statistics') }}" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i> Thống kê
                </a>
            </div>
        </div>

        {{-- 2. Statistics Cards --}}
        @php
            $stats = $stats ?? [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
            ];
            
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-list',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'pending' => [
                    'value' => $stats['pending'] ?? 0,
                    'label' => 'Chờ duyệt',
                    'icon' => 'fa-clock',
                    'color' => 'warning',
                    'filter' => 'pending',
                ],
                'approved' => [
                    'value' => $stats['approved'] ?? 0,
                    'label' => 'Đã duyệt',
                    'icon' => 'fa-check-circle',
                    'color' => 'success',
                    'filter' => 'approved',
                ],
                'rejected' => [
                    'value' => $stats['rejected'] ?? 0,
                    'label' => 'Đã từ chối',
                    'icon' => 'fa-times-circle',
                    'color' => 'danger',
                    'filter' => 'rejected',
                ],
            ];
        @endphp
        <div id="stats-container">
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter', // Use HTMX instead of JavaScript
                'onClearClick' => 'htmx-clear', // Use HTMX instead of JavaScript
                'tableContainerId' => 'reviews-table-container',
                'action' => route('staff.reviews.index'),
                'columns' => 4
            ])
        </div>

        {{-- 3. Filters với HTMX --}}
        @php
            $filterFields = [
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        'pending' => 'Chờ duyệt',
                        'approved' => 'Đã duyệt',
                        'rejected' => 'Đã từ chối',
                    ],
                    'value' => request('status'),
                ],
                [
                    'name' => 'property_id',
                    'label' => 'Bất động sản',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => collect($properties)->mapWithKeys(function($property) {
                        return [$property->id => $property->name];
                    })->toArray(),
                    'value' => request('property_id'),
                ],
                [
                    'name' => 'tenant_id',
                    'label' => 'Khách thuê',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => collect($tenants)->mapWithKeys(function($tenant) {
                        return [$tenant->id => $tenant->full_name ?? $tenant->name ?? 'N/A'];
                    })->toArray(),
                    'value' => request('tenant_id'),
                ],
                [
                    'name' => 'rating_min',
                    'label' => 'Điểm từ',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        '1' => '1 sao',
                        '2' => '2 sao',
                        '3' => '3 sao',
                        '4' => '4 sao',
                        '5' => '5 sao',
                    ],
                    'value' => request('rating_min'),
                ],
                [
                    'name' => 'rating_max',
                    'label' => 'Điểm đến',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        '1' => '1 sao',
                        '2' => '2 sao',
                        '3' => '3 sao',
                        '4' => '4 sao',
                        '5' => '5 sao',
                    ],
                    'value' => request('rating_max'),
                ],
                [
                    'name' => 'recommend',
                    'label' => 'Giới thiệu',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => [
                                            'yes' => 'Có',
                                            'maybe' => 'Có thể',
                        'no' => 'Không',
                    ],
                    'value' => request('recommend'),
                ],
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'col' => 'col-md-6',
                    'placeholder' => 'Tìm theo tiêu đề, nội dung, tên khách thuê...',
                    'value' => request('search'),
                ],
                [
                    'name' => 'date_from',
                    'label' => 'Từ ngày',
                    'type' => 'date',
                    'col' => 'col-md-3',
                    'value' => request('date_from'),
                ],
                [
                    'name' => 'date_to',
                    'label' => 'Đến ngày',
                    'type' => 'date',
                    'col' => 'col-md-3',
                    'value' => request('date_to'),
                ],
            ];
        @endphp
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.reviews.index'),
            'tableContainerId' => 'reviews-table-container',
            'statsContainerId' => 'stats-container',
            'fields' => $filterFields,
            'showReset' => true,
            'resetUrl' => route('staff.reviews.index')
        ])

        {{-- 4. Table với outline variants cho actions --}}
        @include('staff.crm.reviews.partials.table', [
            'reviews' => $reviews,
            'sortBy' => $sortBy ?? request('sort_by', 'created_at'),
            'sortOrder' => $sortOrder ?? request('sort_order', 'desc')
        ])
    </div>
</main>
@endsection

@push('scripts')
<script>
// HTMX đã tự động handle filters, không cần JavaScript functions này nữa
// filterByStatus() và clearAllFilters() đã được thay thế bằng HTMX attributes
</script>
@endpush

