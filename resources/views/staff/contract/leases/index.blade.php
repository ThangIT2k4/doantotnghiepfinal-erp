@extends('layouts.staff_dashboard')

@section('title', 'Quản lý hợp đồng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với solid variants --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý hợp đồng',
            'subtitle' => 'Danh sách tất cả hợp đồng thuê trong hệ thống',
            'icon' => 'fas fa-file-contract',
            'actions' => [
                [
                    'variant' => 'primary',
                    'label' => 'Thêm hợp đồng mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.leases.create')
                ]
            ]
        ])

        {{-- 2. Statistics Cards --}}
        <div id="stats-container">
            @php
                $statsFormatted = [
                    'total' => [
                        'value' => ($stats['draft'] ?? 0) + ($stats['active'] ?? 0) + ($stats['expired'] ?? 0) + ($stats['terminated'] ?? 0),
                        'label' => 'Tổng cộng',
                        'icon' => 'fa-list',
                        'color' => 'primary',
                        'filter' => '',
                    ],
                    'draft' => [
                        'value' => $stats['draft'] ?? 0,
                        'label' => 'Nháp',
                        'icon' => 'fa-file-alt',
                        'color' => 'warning',
                        'filter' => 'draft',
                        'filterKey' => 'status',
                    ],
                    'active' => [
                        'value' => $stats['active'] ?? 0,
                        'label' => 'Đang hoạt động',
                        'icon' => 'fa-check-circle',
                        'color' => 'success',
                        'filter' => 'active',
                        'filterKey' => 'status',
                    ],
                    'expired' => [
                        'value' => $stats['expired'] ?? 0,
                        'label' => 'Đã hết hạn',
                        'icon' => 'fa-clock',
                        'color' => 'secondary',
                        'filter' => 'expired',
                        'filterKey' => 'status',
                    ],
                    'terminated' => [
                        'value' => $stats['terminated'] ?? 0,
                        'label' => 'Đã chấm dứt',
                        'icon' => 'fa-times-circle',
                        'color' => 'danger',
                        'filter' => 'terminated',
                        'filterKey' => 'status',
                    ],
                    'due_for_invoicing' => [
                        'value' => $stats['due_for_invoicing'] ?? 0,
                        'label' => 'Đến hạn lập hóa đơn',
                        'icon' => 'fa-file-invoice',
                        'color' => 'info',
                        'filter' => 'due_for_invoicing',
                        'filterKey' => 'due_for_invoicing',
                    ],
                ];
            @endphp
            @php
                // Determine current filter for stats highlighting
                $currentFilter = '';
                if (request('due_for_invoicing') == '1') {
                    $currentFilter = 'due_for_invoicing';
                } elseif (request('status')) {
                    $currentFilter = request('status');
                }
            @endphp
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => $currentFilter,
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'columns' => 6,
                'action' => route('staff.leases.index'),
                'tableContainerId' => 'leases-table-container'
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
                    'placeholder' => 'Số hợp đồng, tên khách thuê, BĐS...',
                    'value' => request('search'),
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        'draft' => 'Nháp',
                        'active' => 'Đang hoạt động',
                        'terminated' => 'Đã chấm dứt',
                        'expired' => 'Đã hết hạn',
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
                        return [$tenant->id => $tenant->full_name];
                    })->toArray(),
                    'value' => request('tenant_id'),
                ],
                [
                    'name' => 'agent_id',
                    'label' => 'Nhân viên',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => collect($agents)->mapWithKeys(function($agent) {
                        return [$agent->id => $agent->full_name];
                    })->toArray(),
                    'value' => request('agent_id'),
                ],
            ];
        @endphp
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.leases.index'),
            'tableContainerId' => 'leases-table-container',
            'fields' => $filterFields,
            'showReset' => true,
            'resetUrl' => route('staff.leases.index'),
            'liveSearch' => false
        ])

        {{-- 4. Table với outline variants cho actions --}}
        @include('staff.contract.leases.partials.table', [
            'leases' => $leases,
            'sortBy' => $sortBy ?? request('sort_by', 'id'),
            'sortOrder' => $sortOrder ?? request('sort_order', 'desc')
        ])
    </div>
</main>

@endsection

@push('scripts')
<script>
// HTMX đã tự động handle filters, không cần filterByStatus() và clearAllFilters() nữa

function deleteLease(id, name) {
    // Sử dụng notification system
    Notify.confirmDelete(`hợp đồng "${name}"`, function() {
        // Hiển thị loading toast
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0 // Không tự động đóng
        });
        
        fetch(`/staff/leases/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
        .then(response => {
            // Đóng loading toast
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Hiển thị thông báo thành công
                Notify.success(data.message, 'Xóa thành công!');
                
                // Reload table and stats via HTMX
                setTimeout(() => {
                    const url = '{{ route("staff.leases.index") }}';
                    htmx.ajax('GET', url, {
                        target: '#leases-table-container',
                        swap: 'innerHTML'
                    });
                }, 1000);
            } else {
                // Hiển thị thông báo lỗi
                Notify.error(data.message, 'Không thể xóa hợp đồng');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Hiển thị thông báo lỗi
            Notify.error('Có lỗi xảy ra khi xóa hợp đồng. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
    });
}
</script>
@endpush
