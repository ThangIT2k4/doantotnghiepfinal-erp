@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Loại Bất động sản')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với solid variants --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý Loại Bất động sản',
            'subtitle' => 'Danh sách tất cả loại bất động sản trong hệ thống',
            'icon' => 'fas fa-building',
            'actions' => [
                [
                    'type' => 'button',
                    'variant' => 'danger',
                    'label' => 'Xóa loại không dùng',
                    'icon' => 'fas fa-trash-alt',
                    'onclick' => 'deleteUnusedPropertyTypes()'
                ],
                [
                    'variant' => 'primary',
                    'label' => 'Thêm loại mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.property-types.create')
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
                    'label' => 'Tạm ngưng',
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
                'tableContainerId' => 'property-types-table-container',
                'action' => route('staff.property-types.index'),
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
                    'col' => 'col-md-4',
                    'placeholder' => 'Tên, mã code...',
                    'value' => request('search'),
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả trạng thái',
                    'options' => [
                        '1' => 'Hoạt động',
                        '0' => 'Tạm ngưng',
                    ],
                    'value' => request('status'),
                ],
            ];
        @endphp
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.property-types.index'),
            'tableContainerId' => 'property-types-table-container',
            'statsContainerId' => 'stats-container',
            'fields' => $filterFields,
            'showReset' => true,
            'resetUrl' => route('staff.property-types.index')
        ])

        {{-- 4. Table với outline variants cho actions --}}
        @include('staff.asset.property-types.partials.table', [
            'propertyTypes' => $propertyTypes,
            'sortBy' => $sortBy ?? request('sort_by', 'id'),
            'sortOrder' => $sortOrder ?? request('sort_order', 'desc'),
            'canManage' => $canManage ?? false
        ])
    </div>
</main>

@endsection

@push('scripts')
<script>
// HTMX đã tự động handle filters, không cần filterByStatus() và clearAllFilters() nữa

function getCsrfToken() {
    let token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) return token;
    
    token = document.querySelector('input[name="_token"]')?.value;
    if (token) return token;
    
    const form = document.querySelector('form');
    if (form) {
        const input = form.querySelector('input[name="_token"]');
        if (input) return input.value;
    }
    
    return '';
}

function deleteUnusedPropertyTypes() {
    if (typeof window.Notify !== 'undefined') {
        window.Notify.confirm({
            title: 'Xác nhận xóa',
            message: 'Bạn có chắc muốn xóa tất cả các loại bất động sản không được sử dụng?',
            type: 'danger',
            confirmText: 'Xóa',
            cancelText: 'Hủy',
            onConfirm: function() {
                performDeleteUnusedPropertyTypes();
            }
        });
    } else {
        if (confirm('Bạn có chắc muốn xóa tất cả các loại bất động sản không được sử dụng?')) {
            performDeleteUnusedPropertyTypes();
        }
    }
}

function performDeleteUnusedPropertyTypes() {
    const csrfToken = getCsrfToken();
    
    if (typeof window.Notify !== 'undefined') {
        window.Notify.info('Đang xóa các loại bất động sản không sử dụng...', 'Đang xử lý');
    }
    
    fetch(`{{ route('staff.property-types.delete-unused') }}`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.success(
                    data.message || `Đã xóa ${data.deleted_count || 0} loại bất động sản không sử dụng thành công!`,
                    'Thành công'
                );
                setTimeout(() => window.location.reload(), 1500);
            } else {
                alert(data.message || 'Đã xóa các loại bất động sản không sử dụng thành công!');
                window.location.reload();
            }
        } else {
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error(data.error || 'Có lỗi xảy ra khi xóa các loại bất động sản không sử dụng.', 'Lỗi');
            } else {
                alert(data.error || 'Có lỗi xảy ra khi xóa các loại bất động sản không sử dụng.');
            }
        }
    })
    .catch(error => {
        console.error('Error deleting unused property types:', error);
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Có lỗi xảy ra khi xóa các loại bất động sản không sử dụng.', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi xóa các loại bất động sản không sử dụng.');
        }
    });
}

function deletePropertyType(id, name) {
    if (typeof window.Notify === 'undefined') {
        if (confirm(`Bạn có chắc chắn muốn xóa loại bất động sản "${name}"?`)) {
            deletePropertyTypeAction(id);
        }
    } else {
        Notify.confirmDelete(`loại bất động sản "${name}"`, () => {
            deletePropertyTypeAction(id);
        });
    }
}

function deletePropertyTypeAction(id) {
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

    fetch(`/staff/property-types/${id}`, {
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
                Notify.success(data.message || 'Đã xóa loại bất động sản thành công!', 'Đã xóa!');
            } else {
                alert('Đã xóa loại bất động sản thành công!');
            }
            setTimeout(() => {
                // Reload table and stats via HTMX
                const url = '{{ route("staff.property-types.index") }}';
                htmx.ajax('GET', url, {
                    target: '#property-types-table-container',
                    swap: 'innerHTML'
                });
            }, 1000);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa loại bất động sản', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra khi xóa loại bất động sản: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể xóa loại bất động sản: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        } else {
            alert('Không thể xóa loại bất động sản: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
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
