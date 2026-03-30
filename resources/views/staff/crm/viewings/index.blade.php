@extends('layouts.staff_dashboard')

@section('title', 'Quản lý lịch hẹn')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý lịch hẹn',
            'subtitle' => 'Quản lý tất cả lịch hẹn xem phòng trong tổ chức',
            'icon' => 'fas fa-calendar-alt',
            'actions' => [
                [
                    'variant' => 'primary',
                    'label' => 'Tạo lịch hẹn mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.viewings.create')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Lịch',
                    'icon' => 'fas fa-calendar',
                    'url' => route('staff.viewings.calendar')
                ],
                [
                    'variant' => 'success',
                    'label' => 'Thống kê',
                    'icon' => 'fas fa-chart-bar',
                    'url' => route('staff.viewings.statistics')
                ]
            ]
        ])

        <!-- Statistics Cards -->
        @php
            $stats = $stats ?? [
                'total' => 0,
                'requested' => 0,
                'confirmed' => 0,
                'done' => 0,
                'no_show' => 0,
                'cancelled' => 0,
            ];
            
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-list',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'requested' => [
                    'value' => $stats['requested'] ?? 0,
                    'label' => 'Chờ xác nhận',
                    'icon' => 'fa-clock',
                    'color' => 'warning',
                    'filter' => 'requested',
                ],
                'confirmed' => [
                    'value' => $stats['confirmed'] ?? 0,
                    'label' => 'Đã xác nhận',
                    'icon' => 'fa-check',
                    'color' => 'info',
                    'filter' => 'confirmed',
                ],
                'done' => [
                    'value' => $stats['done'] ?? 0,
                    'label' => 'Hoàn thành',
                    'icon' => 'fa-check-circle',
                    'color' => 'success',
                    'filter' => 'done',
                ],
                'no_show' => [
                    'value' => $stats['no_show'] ?? 0,
                    'label' => 'Không đến',
                    'icon' => 'fa-user-times',
                    'color' => 'danger',
                    'filter' => 'no_show',
                ],
                'cancelled' => [
                    'value' => $stats['cancelled'] ?? 0,
                    'label' => 'Đã hủy',
                    'icon' => 'fa-times',
                    'color' => 'secondary',
                    'filter' => 'cancelled',
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
                'tableContainerId' => 'viewings-table-container',
                'action' => route('staff.viewings.index'),
                'columns' => 6
            ])
        </div>

        <!-- Filters với HTMX -->
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.viewings.index'),
            'tableContainerId' => 'viewings-table-container',
            'statsContainerId' => 'stats-container',
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Tên khách, SĐT, email...',
                    'value' => request('search'),
                    'col' => 'col-md-3',
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'empty_option' => 'Tất cả trạng thái',
                    'options' => [
                        'requested' => 'Chờ xác nhận',
                        'confirmed' => 'Đã xác nhận',
                        'done' => 'Hoàn thành',
                        'no_show' => 'Không đến',
                        'cancelled' => 'Đã hủy',
                    ],
                    'value' => request('status', ''),
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'property_id',
                    'label' => 'Bất động sản',
                    'type' => 'select',
                    'empty_option' => 'Tất cả BĐS',
                    'options' => $properties->pluck('name', 'id')->toArray(),
                    'value' => request('property_id', ''),
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'agent_id',
                    'label' => 'Agent',
                    'type' => 'select',
                    'empty_option' => 'Tất cả Agent',
                    'options' => $agents->pluck('full_name', 'id')->toArray(),
                    'value' => request('agent_id', ''),
                    'col' => 'col-md-2',
                ],
            ],
            'showReset' => true,
            'resetUrl' => route('staff.viewings.index')
        ])

        <!-- Table -->
        @include('staff.crm.viewings.partials.table', [
            'viewings' => $viewings,
            'sortBy' => $sortBy ?? 'schedule_at',
            'sortOrder' => $sortOrder ?? 'desc'
        ])
    </div>
</main>
@endsection

@push('scripts')
<script>
// HTMX đã tự động handle filters, không cần JavaScript functions này nữa
// filterByStatus() và clearAllFilters() đã được thay thế bằng HTMX attributes

function deleteViewing(id, name) {
    if (typeof Notify !== 'undefined' && Notify.confirmDelete) {
        Notify.confirmDelete(`lịch hẹn "${name}"`, () => {
            if (window.Preloader) {
                window.Preloader.show();
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
                if (window.Preloader) {
                    window.Preloader.hide();
                }
                return;
            }
            fetch(`/staff/viewings/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
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
                    Notify.success(data.message, 'Đã xóa!');
                    // Reload table via HTMX if available, otherwise reload page
                    setTimeout(() => {
                        if (typeof htmx !== 'undefined') {
                            htmx.ajax('GET', window.location.href, {
                                target: '#viewings-table-container',
                                swap: 'innerHTML'
                            });
                        } else {
                            window.location.reload();
                        }
                    }, 1000);
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Không thể xóa lịch hẹn: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        });
    } else {
        // Fallback if Notify is not available
        if (confirm('Bạn có chắc chắn muốn xóa lịch hẹn "' + name + '"?')) {
            if (window.Preloader) {
                window.Preloader.show();
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                alert('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.');
                if (window.Preloader) {
                    window.Preloader.hide();
                }
                return;
            }
            fetch(`/staff/viewings/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
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
                    alert('Lịch hẹn đã được xóa thành công!');
                    // Reload table via HTMX if available, otherwise reload page
                    setTimeout(() => {
                        if (typeof htmx !== 'undefined') {
                            htmx.ajax('GET', window.location.href, {
                                target: '#viewings-table-container',
                                swap: 'innerHTML'
                            });
                        } else {
                            window.location.reload();
                        }
                    }, 1000);
                } else {
                    alert('Có lỗi xảy ra: ' + (data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Không thể xóa lịch hẹn: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    }
}
</script>
@endpush
