@extends('layouts.staff_dashboard')

@section('title', 'Quản lý công tơ đo')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với solid variants --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý công tơ đo',
            'subtitle' => 'Quản lý các công tơ đo điện, nước trong bất động sản',
            'icon' => 'fas fa-tachometer-alt',
            'actions' => [
                [
                    'variant' => 'success',
                    'label' => 'Thống kê',
                    'icon' => 'fas fa-chart-bar',
                    'url' => route('staff.meters.statistics')
                ],
                [
                    'variant' => 'primary',
                    'label' => 'Thêm công tơ mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.meters.create')
                ]
            ]
        ])

        {{-- 2. Statistics Cards --}}
        @php
            $stats = $stats ?? [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
            ];
            
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-list',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'active' => [
                    'value' => $stats['active'] ?? 0,
                    'label' => 'Hoạt động',
                    'icon' => 'fa-check-circle',
                    'color' => 'success',
                    'filter' => '1',
                ],
                'inactive' => [
                    'value' => $stats['inactive'] ?? 0,
                    'label' => 'Ngừng hoạt động',
                    'icon' => 'fa-pause-circle',
                    'color' => 'warning',
                    'filter' => '0',
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
                'tableContainerId' => 'meters-table-container',
                'action' => route('staff.meters.index'),
                'columns' => 3
            ])
        </div>

        {{-- 3. Filters với HTMX --}}
        @php
            $filterFields = [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'col' => 'col-md-2',
                    'placeholder' => 'Số seri...',
                    'value' => request('search'),
                ],
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
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả trạng thái',
                    'options' => [
                        '1' => 'Hoạt động',
                        '0' => 'Ngừng hoạt động',
                    ],
                    'value' => request('status'),
                ],
                [
                    'name' => 'deleted',
                    'label' => 'Trạng thái xóa',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Chưa xóa',
                    'options' => [
                        'only' => 'Chỉ đã xóa',
                        'with' => 'Tất cả',
                    ],
                    'value' => request('deleted'),
                ],
            ];
        @endphp
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.meters.index'),
            'tableContainerId' => 'meters-table-container',
            'statsContainerId' => 'stats-container',
            'fields' => $filterFields,
            'showReset' => true,
            'resetUrl' => route('staff.meters.index')
        ])

        {{-- 4. Table với outline variants cho actions --}}
        @include('staff.asset.meters.partials.table', [
            'meters' => $meters,
            'sortBy' => $sortBy ?? request('sort_by', 'id'),
            'sortOrder' => $sortOrder ?? request('sort_order', 'desc')
        ])
    </div>
</main>

@endsection

@push('scripts')
<script>
// HTMX đã tự động handle filters, không cần filterByStatus() và clearAllFilters() nữa

let currentMeterId = null;

function deleteMeter(meterId, serialNo) {
    if (typeof window.Notify === 'undefined') {
        if (confirm(`Bạn có chắc chắn muốn xóa công tơ đo "${serialNo}"?`)) {
            deleteMeterAction(meterId);
        }
    } else {
        Notify.confirmDelete(`công tơ đo "${serialNo}"`, () => {
            deleteMeterAction(meterId);
        });
    }
}

function deleteMeterAction(meterId) {
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

    fetch(`/staff/meters/${meterId}`, {
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
                Notify.success(data.message || 'Đã xóa công tơ đo thành công!', 'Đã xóa!');
            } else {
                alert('Đã xóa công tơ đo thành công!');
            }
            setTimeout(() => {
                // Reload table and stats via HTMX
                const url = '{{ route("staff.meters.index") }}';
                htmx.ajax('GET', url, {
                    target: '#meters-table-container',
                    swap: 'innerHTML'
                });
            }, 1000);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa công tơ đo', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra khi xóa công tơ đo: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể xóa công tơ đo: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        } else {
            alert('Không thể xóa công tơ đo: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
        }
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
}

function restoreMeter(meterId, serialNo) {
    if (typeof window.Notify === 'undefined') {
        if (confirm(`Bạn có chắc chắn muốn khôi phục công tơ đo ${serialNo}?`)) {
            restoreMeterAction(meterId);
        }
    } else {
        Notify.confirm({
            title: 'Xác nhận khôi phục',
            message: `Bạn có chắc chắn muốn khôi phục công tơ đo "${serialNo}"?`,
            type: 'warning',
            confirmText: 'Xác nhận',
            cancelText: 'Hủy',
            onConfirm: function() {
                restoreMeterAction(meterId);
            }
        });
    }
}

function restoreMeterAction(meterId) {
    if (window.Preloader) {
        window.Preloader.show();
    }

    fetch(`/staff/meters/${meterId}/restore`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof window.Notify !== 'undefined') {
                Notify.success(data.message || 'Đã khôi phục công tơ đo thành công!', 'Thành công!');
            } else {
                alert('Đã khôi phục công tơ đo thành công!');
            }
            setTimeout(() => {
                // Reload table and stats via HTMX
                const url = '{{ route("staff.meters.index") }}';
                htmx.ajax('GET', url, {
                    target: '#meters-table-container',
                    swap: 'innerHTML'
                });
            }, 1000);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể khôi phục công tơ đo: ' + error.message, 'Lỗi hệ thống!');
        } else {
            alert('Không thể khôi phục công tơ đo: ' + error.message);
        }
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
}

function forceDeleteMeter(meterId, serialNo) {
    if (typeof window.Notify === 'undefined') {
        if (confirm(`Bạn có chắc chắn muốn xóa VĨNH VIỄN công tơ đo ${serialNo}?\n\nHành động này không thể hoàn tác!`)) {
            forceDeleteMeterAction(meterId);
        }
    } else {
        Notify.confirm({
            title: 'Xác nhận xóa vĩnh viễn',
            message: `Bạn có chắc chắn muốn xóa VĨNH VIỄN công tơ đo "${serialNo}"?\n\nHành động này không thể hoàn tác!`,
            type: 'danger',
            confirmText: 'Xác nhận xóa',
            cancelText: 'Hủy',
            onConfirm: function() {
                forceDeleteMeterAction(meterId);
            }
        });
    }
}

function forceDeleteMeterAction(meterId) {
    if (window.Preloader) {
        window.Preloader.show();
    }

    fetch(`/staff/meters/${meterId}/force-delete`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof window.Notify !== 'undefined') {
                Notify.success(data.message || 'Đã xóa vĩnh viễn công tơ đo thành công!', 'Thành công!');
            } else {
                alert('Đã xóa vĩnh viễn công tơ đo thành công!');
            }
            setTimeout(() => {
                // Reload table and stats via HTMX
                const url = '{{ route("staff.meters.index") }}';
                htmx.ajax('GET', url, {
                    target: '#meters-table-container',
                    swap: 'innerHTML'
                });
            }, 1000);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể xóa vĩnh viễn công tơ đo: ' + error.message, 'Lỗi hệ thống!');
        } else {
            alert('Không thể xóa vĩnh viễn công tơ đo: ' + error.message);
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
