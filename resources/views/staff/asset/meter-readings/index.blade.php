@extends('layouts.staff_dashboard')

@section('title', 'Quản lý số liệu đo')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với solid variants --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý số liệu đo',
            'subtitle' => 'Quản lý các số liệu đo điện, nước từ công tơ',
            'icon' => 'fas fa-clipboard-list',
            'actions' => [
                [
                    'variant' => 'success',
                    'label' => 'Thống kê',
                    'icon' => 'fas fa-chart-bar',
                    'url' => route('staff.meter-readings.statistics')
                ],
                [
                    'variant' => 'primary',
                    'label' => 'Thêm số liệu đo',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.meter-readings.create')
                ]
            ]
        ])

        {{-- 2. Statistics Cards --}}
        @php
            $stats = $stats ?? ['total' => 0];
            $allServices = $allServices ?? collect();
            
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-list',
                    'color' => 'primary',
                    'filter' => '',
                ],
            ];
            
            // Add service statistics
            foreach ($allServices as $service) {
                $serviceKey = 'service_' . $service->id;
                if (isset($stats[$serviceKey]) && $stats[$serviceKey] > 0) {
                    $statsFormatted[$serviceKey] = [
                        'value' => $stats[$serviceKey] ?? 0,
                        'label' => $service->name,
                        'icon' => 'fa-circle',
                        'color' => 'info',
                        'filter' => (string)$service->id,
                        'filterKey' => 'service_id',
                    ];
                }
            }
        @endphp
        <div id="stats-container">
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('service_id', ''),
                'filterKey' => 'service_id',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'meter-readings-table-container',
                'action' => route('staff.meter-readings.index'),
                'columns' => count($statsFormatted) > 4 ? 6 : 4
            ])
        </div>

        {{-- 3. Filters với HTMX --}}
        @php
            $filterFields = [
                [
                    'name' => 'property_id',
                    'label' => 'Bất động sản',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả bất động sản',
                    'options' => collect($properties)->mapWithKeys(function($property) {
                        return [$property->id => $property->name];
                    })->toArray(),
                    'value' => request('property_id'),
                ],
                [
                    'name' => 'service_id',
                    'label' => 'Loại dịch vụ',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả dịch vụ',
                    'options' => collect($services)->mapWithKeys(function($service) {
                        return [$service->id => $service->name . ' (' . $service->key_code . ')'];
                    })->toArray(),
                    'value' => request('service_id'),
                ],
                [
                    'name' => 'date_from',
                    'label' => 'Từ ngày',
                    'type' => 'date',
                    'col' => 'col-md-2',
                    'value' => request('date_from'),
                ],
                [
                    'name' => 'date_to',
                    'label' => 'Đến ngày',
                    'type' => 'date',
                    'col' => 'col-md-2',
                    'value' => request('date_to'),
                ],
                [
                    'name' => 'taken_by',
                    'label' => 'Người đo',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả người đo',
                    'options' => $readings->pluck('takenBy')->unique()->filter()->mapWithKeys(function($user) {
                        return [$user->id => $user->name];
                    })->toArray(),
                    'value' => request('taken_by'),
                ],
            ];
        @endphp
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.meter-readings.index'),
            'tableContainerId' => 'meter-readings-table-container',
            'statsContainerId' => 'stats-container',
            'fields' => $filterFields,
            'showReset' => true,
            'resetUrl' => route('staff.meter-readings.index')
        ])

        {{-- 4. Table với outline variants cho actions --}}
        @include('staff.asset.meter-readings.partials.table', [
            'readings' => $readings,
            'sortBy' => $sortBy ?? request('sort_by', 'reading_date'),
            'sortOrder' => $sortOrder ?? request('sort_order', 'desc')
        ])
    </div>
</main>

@endsection

@push('scripts')
<script>
// HTMX đã tự động handle filters, không cần filterByService() và clearAllFilters() nữa

function deleteReading(readingId, readingDate) {
    if (typeof window.Notify === 'undefined') {
        if (confirm(`Bạn có chắc chắn muốn xóa số liệu đo ngày ${readingDate}?`)) {
            deleteReadingAction(readingId);
        }
    } else {
        Notify.confirmDelete(`số liệu đo ngày ${readingDate}`, () => {
            deleteReadingAction(readingId);
        });
    }
}

function deleteReadingAction(readingId) {
    if (window.Preloader) {
        window.Preloader.show();
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error('CSRF token not found');
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
        } else {
            alert('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.');
        }
        if (window.Preloader) {
            window.Preloader.hide();
        }
        return;
    }

    fetch(`/staff/meter-readings/${readingId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof window.Notify !== 'undefined') {
                Notify.success(data.message || 'Đã xóa số liệu đo thành công!', 'Đã xóa!');
            } else {
                alert('Đã xóa số liệu đo thành công!');
            }
            setTimeout(() => {
                // Reload table and stats via HTMX
                const url = '{{ route("staff.meter-readings.index") }}';
                htmx.ajax('GET', url, {
                    target: '#meter-readings-table-container',
                    swap: 'innerHTML'
                });
            }, 1000);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa số liệu đo', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra khi xóa số liệu đo: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể xóa số liệu đo: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        } else {
            alert('Không thể xóa số liệu đo: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
        }
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
}
</script>
@endpush
