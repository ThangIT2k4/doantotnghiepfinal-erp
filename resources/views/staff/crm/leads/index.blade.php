@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Leads - Khách hàng tiềm năng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý Leads',
            'subtitle' => 'Quản lý khách hàng tiềm năng trong hệ thống',
            'icon' => 'fas fa-users',
            'actions' => [
                [
                    'variant' => 'success',
                    'label' => 'Thống kê',
                    'icon' => 'fas fa-chart-bar',
                    'url' => route('staff.leads.statistics')
                ],
                [
                    'variant' => 'primary',
                    'label' => 'Thêm Lead mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.leads.create')
                ]
            ]
        ])

        <!-- Statistics Cards -->
        @php
            $stats = $stats ?? [
                'total' => 0,
                'new' => 0,
                'contacted' => 0,
                'qualified' => 0,
                'converted' => 0,
                'lost' => 0,
            ];
            
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-list',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'new' => [
                    'value' => $stats['new'] ?? 0,
                    'label' => 'Mới',
                    'icon' => 'fa-user-plus',
                    'color' => 'info',
                    'filter' => 'new',
                ],
                'contacted' => [
                    'value' => $stats['contacted'] ?? 0,
                    'label' => 'Đã liên hệ',
                    'icon' => 'fa-phone',
                    'color' => 'warning',
                    'filter' => 'contacted',
                ],
                'qualified' => [
                    'value' => $stats['qualified'] ?? 0,
                    'label' => 'Đủ điều kiện',
                    'icon' => 'fa-check',
                    'color' => 'primary',
                    'filter' => 'qualified',
                ],
                'converted' => [
                    'value' => $stats['converted'] ?? 0,
                    'label' => 'Đã chuyển đổi',
                    'icon' => 'fa-check-circle',
                    'color' => 'success',
                    'filter' => 'converted',
                ],
                'lost' => [
                    'value' => $stats['lost'] ?? 0,
                    'label' => 'Đã mất',
                    'icon' => 'fa-times',
                    'color' => 'danger',
                    'filter' => 'lost',
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
                'tableContainerId' => 'leads-table-container',
                'action' => route('staff.leads.index'),
                'columns' => 6
            ])
        </div>

        <!-- Filters với HTMX -->
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.leads.index'),
            'tableContainerId' => 'leads-table-container',
            'statsContainerId' => 'stats-container',
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Tên, SĐT, Email...',
                    'value' => request('search'),
                    'col' => 'col-md-3',
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        'new' => 'Mới',
                        'contacted' => 'Đã liên hệ',
                        'qualified' => 'Đủ điều kiện',
                        'proposal' => 'Đề xuất',
                        'negotiation' => 'Thương lượng',
                        'converted' => 'Đã chuyển đổi',
                        'lost' => 'Mất khách',
                    ],
                    'value' => request('status', ''),
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'source',
                    'label' => 'Nguồn',
                    'type' => 'select',
                    'empty_option' => 'Tất cả',
                    'options' => $sources->mapWithKeys(function($source) {
                        return [$source => $source];
                    })->toArray(),
                    'value' => request('source', ''),
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'budget_min',
                    'label' => 'Ngân sách tối thiểu',
                    'type' => 'number',
                    'placeholder' => '0',
                    'value' => request('budget_min'),
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'budget_max',
                    'label' => 'Ngân sách tối đa',
                    'type' => 'number',
                    'placeholder' => '0',
                    'value' => request('budget_max'),
                    'col' => 'col-md-2',
                ],
            ],
            'showReset' => true,
            'resetUrl' => route('staff.leads.index')
        ])

        <!-- Table -->
        @include('staff.crm.leads.partials.table', [
            'leads' => $leads,
            'sortBy' => request('sort_by', 'id'),
            'sortOrder' => request('sort_order', 'desc'),
        ])
    </div>
</main>

@push('scripts')
<script>
// HTMX đã tự động handle filters, không cần JavaScript functions này nữa
// filterByStatus() và clearAllFilters() đã được thay thế bằng HTMX attributes

function deleteLead(id, name) {
    if (typeof Notify !== 'undefined' && Notify.confirmDelete) {
        Notify.confirmDelete(`lead "${name}"`, () => {
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

            fetch(`/staff/leads/${id}`, {
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
                    // Reload table and stats via HTMX
                    setTimeout(() => {
                        const url = '{{ route("staff.leads.index") }}';
                        htmx.ajax('GET', url, {
                            target: '#leads-table-container',
                            swap: 'innerHTML'
                        });
                    }, 1000);
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Không thể xóa lead: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        });
    } else {
        if (confirm('Bạn có chắc chắn muốn xóa lead "' + name + '"?')) {
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

            fetch(`/staff/leads/${id}`, {
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
                    alert('Lead đã được xóa thành công!');
                    // Reload table and stats via HTMX
                    setTimeout(() => {
                        const url = '{{ route("staff.leads.index") }}';
                        htmx.ajax('GET', url, {
                            target: '#leads-table-container',
                            swap: 'innerHTML'
                        });
                    }, 1000);
                } else {
                    alert('Có lỗi xảy ra: ' + (data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Không thể xóa lead: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
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
@endsection
