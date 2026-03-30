@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Nhà cung cấp')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý Nhà cung cấp',
            'subtitle' => 'Quản lý thông tin nhà cung cấp và tích hợp thanh toán',
            'icon' => 'fas fa-building',
            'actions' => [
                [
                    'variant' => 'primary',
                    'label' => 'Thêm nhà cung cấp',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.vendors.create')
                ]
            ]
        ])

        <!-- Statistics Cards -->
        @php
            $stats = $stats ?? [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'suspended' => 0,
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
                    'filter' => 'active',
                ],
                'inactive' => [
                    'value' => $stats['inactive'] ?? 0,
                    'label' => 'Không hoạt động',
                    'icon' => 'fa-times-circle',
                    'color' => 'danger',
                    'filter' => 'inactive',
                ],
                'suspended' => [
                    'value' => $stats['suspended'] ?? 0,
                    'label' => 'Tạm ngưng',
                    'icon' => 'fa-pause-circle',
                    'color' => 'warning',
                    'filter' => 'suspended',
                ],
            ];
        @endphp
        <div id="stats-container">
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted ?? $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter', // Use HTMX instead of JavaScript
                'onClearClick' => 'htmx-clear', // Use HTMX instead of JavaScript
                'tableContainerId' => 'vendors-table-container',
                'action' => route('staff.vendors.index'),
                'columns' => 4
            ])
        </div>

        <!-- Filters với HTMX -->
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.vendors.index'),
            'tableContainerId' => 'vendors-table-container',
            'statsContainerId' => 'stats-container',
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Tên, mã số thuế, email, SĐT...',
                    'value' => request('search'),
                    'col' => 'col-md-3',
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        'active' => 'Hoạt động',
                        'inactive' => 'Không hoạt động',
                        'suspended' => 'Tạm ngưng',
                    ],
                    'value' => request('status', ''),
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'vendor_type',
                    'label' => 'Loại',
                    'type' => 'select',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        'company' => 'Công ty',
                        'individual' => 'Cá nhân',
                    ],
                    'value' => request('vendor_type', ''),
                    'col' => 'col-md-2',
                ],
            ],
            'showReset' => true,
            'resetUrl' => route('staff.vendors.index')
        ])

        <!-- Table -->
        <div id="vendors-table-container">
            @include('staff.finance.vendors.partials.table', [
                'vendors' => $vendors,
            ])
        </div>
    </div>
</main>

@push('scripts')
<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if Notify is available
    if (typeof window.Notify === 'undefined') {
        // Fallback to native confirm
        window.deleteVendor = function(id, name) {
            if (confirm(`Bạn có chắc chắn muốn xóa nhà cung cấp "${name}"?`)) {
                deleteVendorAction(id);
            }
        };
    } else {
        window.deleteVendor = function(id, name) {
            Notify.confirmDelete(`nhà cung cấp "${name}"`, () => {
                deleteVendorAction(id);
            });
        };
    }
    
});

// HTMX đã tự động handle filters, không cần JavaScript functions này nữa
// filterByStatus() và clearAllFilters() đã được thay thế bằng HTMX attributes

function deleteVendorAction(id) {
    // Show preloader
    if (window.Preloader) {
        window.Preloader.show();
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
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
    
    fetch(`/staff/vendors/${id}`, {
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
                Notify.success(data.message || 'Đã xóa nhà cung cấp thành công!', 'Đã xóa!');
            } else {
                alert('Đã xóa nhà cung cấp thành công!');
            }
            // Reload table via HTMX
            if (typeof htmx !== 'undefined') {
                setTimeout(() => {
                    const form = document.getElementById('index-filters-form');
                    if (form) {
                        htmx.trigger(form, 'submit');
                    } else {
                        // Fallback: reload page
                        window.location.reload();
                    }
                }, 1000);
            } else {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa nhà cung cấp', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra khi xóa nhà cung cấp: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể xóa nhà cung cấp: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        } else {
            alert('Không thể xóa nhà cung cấp: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
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
