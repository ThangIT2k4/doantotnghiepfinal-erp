@extends('layouts.staff_dashboard')

@section('title', 'Quản lý khách hàng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý khách hàng',
            'subtitle' => 'Quản lý thông tin khách hàng thuê',
            'icon' => 'fas fa-users',
            'actions' => [
                [
                    'variant' => 'success',        // ✅ Solid variant (theo UI guide)
                    'label' => 'Thống kê',
                    'icon' => 'fas fa-chart-bar',
                    'url' => route('staff.tenants.statistics')
                ],
                [
                    'variant' => 'primary',        // ✅ Solid variant (theo UI guide)
                    'label' => 'Thêm khách hàng',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.tenants.create')
                ]
            ]
        ])

        <!-- Statistics Cards -->
        @php
            $stats = $stats ?? [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'has_lease' => 0,
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
                'has_lease' => [
                    'value' => $stats['has_lease'] ?? 0,
                    'label' => 'Có hợp đồng',
                    'icon' => 'fa-file-contract',
                    'color' => 'info',
                    'filter' => '1',
                    'filterKey' => 'has_lease',
                ],
                'inactive' => [
                    'value' => $stats['inactive'] ?? 0,
                    'label' => 'Đã xóa',
                    'icon' => 'fa-times-circle',
                    'color' => 'danger',
                    'filter' => 'inactive',
                ],
            ];
            
            // Determine current filter
            $currentFilter = '';
            if (request('has_lease') === '1') {
                $currentFilter = '1';
            } elseif (request('status')) {
                $currentFilter = request('status');
            }
        @endphp
        <div id="stats-container">
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => $currentFilter,
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter', // Use HTMX instead of JavaScript
                'onClearClick' => 'htmx-clear', // Use HTMX instead of JavaScript
                'tableContainerId' => 'tenants-table-container',
                'action' => route('staff.tenants.index'),
                'columns' => 4
            ])
        </div>

        <!-- Filters với HTMX -->
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.tenants.index'),
            'tableContainerId' => 'tenants-table-container',
            'statsContainerId' => 'stats-container',
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Tên, email, số điện thoại...',
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
                        'inactive' => 'Đã xóa',
                    ],
                    'value' => request('status', ''),
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'has_lease',
                    'label' => 'Có hợp đồng',
                    'type' => 'select',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        '1' => 'Có',
                        '0' => 'Không',
                    ],
                    'value' => request('has_lease', ''),
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'date_from',
                    'label' => 'Từ ngày',
                    'type' => 'date',
                    'value' => request('date_from'),
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'date_to',
                    'label' => 'Đến ngày',
                    'type' => 'date',
                    'value' => request('date_to'),
                    'col' => 'col-md-2',
                ],
            ],
            'showReset' => true,
            'resetUrl' => route('staff.tenants.index')
        ])

        <!-- Table -->
        @include('staff.party.tenants.partials.table', [
            'tenants' => $tenants,
            'sortBy' => request('sort_by', 'id'),
            'sortOrder' => request('sort_order', 'desc'),
        ])
    </div>
</main>

@push('scripts')
<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if Notify is available
    if (typeof window.Notify === 'undefined') {
        // Fallback to native confirm
        window.deleteTenant = function(id, name) {
            if (confirm(`Bạn có chắc chắn muốn xóa khách hàng "${name}"?`)) {
                deleteTenantAction(id);
            }
        };
    } else {
        window.deleteTenant = function(id, name) {
            Notify.confirmDelete(`khách hàng "${name}"`, () => {
                deleteTenantAction(id);
            });
        };
    }
});

// HTMX đã tự động handle filters, không cần JavaScript functions này nữa
// filterByStatus() và clearAllFilters() đã được thay thế bằng HTMX attributes

function deleteTenantAction(id) {
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

    fetch(`/staff/tenants/${id}`, {
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
                Notify.success(data.message || 'Đã xóa khách hàng thành công!', 'Đã xóa!');
            } else {
                alert('Đã xóa khách hàng thành công!');
            }
            // Reload table via HTMX if available, otherwise reload page
            setTimeout(() => {
                if (typeof htmx !== 'undefined') {
                    htmx.ajax('GET', window.location.href, {
                        target: '#tenants-table-container',
                        swap: 'innerHTML'
                    });
                } else {
                    window.location.reload();
                }
            }, 1000);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa khách hàng', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra khi xóa khách hàng: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể xóa khách hàng. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        } else {
            alert('Không thể xóa khách hàng. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
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
