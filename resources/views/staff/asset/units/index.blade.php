@extends('layouts.staff_dashboard')

@section('title', 'Quản lý phòng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với solid variants --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý phòng',
            'subtitle' => 'Quản lý phòng trên toàn tổ chức',
            'icon' => 'fas fa-building',
            'actions' => [
                [
                    'variant' => 'primary',
                    'label' => 'Thêm phòng',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.units.create')
                ]
            ]
        ])

        {{-- 2. Statistics Cards --}}
        @php
            $stats = $stats ?? [
                'total' => 0,
                'available' => 0,
                'occupied' => 0,
                'maintenance' => 0,
                'reserved' => 0,
            ];
            
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-list',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'available' => [
                    'value' => $stats['available'] ?? 0,
                    'label' => 'Trống',
                    'icon' => 'fa-door-open',
                    'color' => 'success',
                    'filter' => 'available',
                ],
                'occupied' => [
                    'value' => $stats['occupied'] ?? 0,
                    'label' => 'Đã thuê',
                    'icon' => 'fa-home',
                    'color' => 'info',
                    'filter' => 'occupied',
                ],
                'maintenance' => [
                    'value' => $stats['maintenance'] ?? 0,
                    'label' => 'Bảo trì',
                    'icon' => 'fa-tools',
                    'color' => 'warning',
                    'filter' => 'maintenance',
                ],
                'reserved' => [
                    'value' => $stats['reserved'] ?? 0,
                    'label' => 'Đã đặt',
                    'icon' => 'fa-bookmark',
                    'color' => 'secondary',
                    'filter' => 'reserved',
                ],
            ];
        @endphp
        <div id="stats-container">
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'units-table-container',
                'action' => route('staff.units.index'),
                'columns' => 5
            ])
        </div>

        {{-- 3. Filters với HTMX --}}
        @php
            $filterFields = [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'col' => 'col-md-3',
                    'placeholder' => 'Mã phòng, loại phòng, tên BĐS...',
                    'value' => request('search'),
                ],
                [
                    'name' => 'property_id',
                    'label' => 'Bất động sản',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả BĐS',
                    'options' => collect($properties)->mapWithKeys(function($property) {
                        return [$property->id => $property->name];
                    })->toArray(),
                    'value' => request('property_id'),
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        'available' => 'Có sẵn',
                        'reserved' => 'Đã đặt',
                        'occupied' => 'Đã thuê',
                        'maintenance' => 'Bảo trì',
                    ],
                    'value' => request('status'),
                ],
                [
                    'name' => 'unit_type',
                    'label' => 'Loại phòng',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        'room' => 'Phòng',
                        'apartment' => 'Căn hộ',
                        'dorm' => 'Ký túc xá',
                        'shared' => 'Chung',
                    ],
                    'value' => request('unit_type'),
                ],
                [
                    'name' => 'availability',
                    'label' => 'Khả dụng',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        'available' => 'Có thể thuê',
                        'occupied' => 'Đã thuê',
                    ],
                    'value' => request('availability'),
                ],
                [
                    'name' => 'rent_min',
                    'label' => 'Giá thuê từ',
                    'type' => 'number',
                    'col' => 'col-md-2',
                    'placeholder' => '0',
                    'value' => request('rent_min'),
                ],
                [
                    'name' => 'rent_max',
                    'label' => 'Giá thuê đến',
                    'type' => 'number',
                    'col' => 'col-md-2',
                    'placeholder' => '0',
                    'value' => request('rent_max'),
                ],
                [
                    'name' => 'area_min',
                    'label' => 'Diện tích từ (m²)',
                    'type' => 'number',
                    'col' => 'col-md-2',
                    'placeholder' => '0',
                    'value' => request('area_min'),
                ],
                [
                    'name' => 'area_max',
                    'label' => 'Diện tích đến (m²)',
                    'type' => 'number',
                    'col' => 'col-md-2',
                    'placeholder' => '0',
                    'value' => request('area_max'),
                ],
                [
                    'name' => 'per_page',
                    'label' => 'Hiển thị',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'options' => [
                        '10' => '10',
                        '25' => '25',
                        '50' => '50',
                        '100' => '100',
                    ],
                    'value' => request('per_page', '20'),
                ],
            ];
        @endphp
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.units.index'),
            'tableContainerId' => 'units-table-container',
            'statsContainerId' => 'stats-container',
            'fields' => $filterFields,
            'showReset' => true,
            'resetUrl' => route('staff.units.index')
        ])

        {{-- 4. Table với outline variants cho actions --}}
        @include('staff.asset.units.partials.table', [
            'units' => $units,
            'sortBy' => $sortBy ?? 'code',
            'sortOrder' => $sortOrder ?? 'asc'
        ])
    </div>
</main>

@push('scripts')
<script>
// HTMX đã tự động handle filters, không cần filterByStatus() và clearAllFilters() nữa

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if Notify is available
    if (typeof window.Notify === 'undefined') {
        // Fallback to native confirm
        window.deleteUnit = function(id, name) {
            if (confirm(`Bạn có chắc chắn muốn xóa phòng "${name}"?`)) {
                deleteUnitAction(id);
            }
        };
    } else {
        window.deleteUnit = function(id, name) {
            Notify.confirmDelete(`phòng "${name}"`, () => {
                deleteUnitAction(id);
            });
        };
    }
});

function deleteUnitAction(id) {
    // Show preloader
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

    fetch(`/staff/units/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
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
            if (typeof window.Notify !== 'undefined') {
                Notify.success(data.message || 'Đã xóa phòng thành công!', 'Đã xóa!');
            } else {
                alert('Đã xóa phòng thành công!');
            }
            setTimeout(() => {
                // Reload table and stats via HTMX
                const url = '{{ route("staff.units.index") }}';
                htmx.ajax('GET', url, {
                    target: '#units-table-container',
                    swap: 'innerHTML'
                });
            }, 1000);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa phòng', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra khi xóa phòng: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể xóa phòng: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        } else {
            alert('Không thể xóa phòng: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
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
@endsection
