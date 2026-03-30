@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Hợp đồng Thuê Lại')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với solid variants --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý Hợp đồng Thuê Lại',
            'subtitle' => 'Danh sách tất cả hợp đồng thuê lại trong hệ thống',
            'icon' => 'fas fa-file-contract',
            'actions' => [
                [
                    'variant' => 'primary',
                    'label' => 'Tạo hợp đồng mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.master-leases.create')
                ]
            ]
        ])

        {{-- 2. Statistics Cards --}}
        <div id="stats-container">
            @php
                $statsFormatted = [
                    'total' => [
                        'value' => $stats['total'] ?? 0,
                        'label' => 'Tất cả',
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
                        'label' => 'Hoạt động',
                        'icon' => 'fa-check-circle',
                        'color' => 'success',
                        'filter' => 'active',
                        'filterKey' => 'status',
                    ],
                    'expired' => [
                        'value' => $stats['expired'] ?? 0,
                        'label' => 'Hết hạn',
                        'icon' => 'fa-clock',
                        'color' => 'secondary',
                        'filter' => 'expired',
                        'filterKey' => 'status',
                    ],
                    'terminated' => [
                        'value' => $stats['terminated'] ?? 0,
                        'label' => 'Chấm dứt',
                        'icon' => 'fa-times-circle',
                        'color' => 'danger',
                        'filter' => 'terminated',
                        'filterKey' => 'status',
                    ],
                ];
            @endphp
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'columns' => 5,
                'action' => route('staff.master-leases.index'),
                'tableContainerId' => 'master-leases-table-container'
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
                    'placeholder' => 'Số hợp đồng, tên bất động sản...',
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
                        'active' => 'Hoạt động',
                        'terminated' => 'Chấm dứt',
                        'expired' => 'Hết hạn',
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
                    'name' => 'landlord_user_id',
                    'label' => 'Chủ nhà',
                    'type' => 'select',
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả',
                    'options' => collect($landlords)->mapWithKeys(function($landlord) {
                        return [$landlord->id => $landlord->full_name];
                    })->toArray(),
                    'value' => request('landlord_user_id'),
                ],
                [
                    'name' => 'date_from',
                    'label' => 'Từ ngày',
                    'type' => 'date',
                    'col' => 'col-md-1',
                    'value' => request('date_from'),
                ],
                [
                    'name' => 'date_to',
                    'label' => 'Đến ngày',
                    'type' => 'date',
                    'col' => 'col-md-1',
                    'value' => request('date_to'),
                ],
            ];
        @endphp
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.master-leases.index'),
            'tableContainerId' => 'master-leases-table-container',
            'fields' => $filterFields,
            'showReset' => true,
            'resetUrl' => route('staff.master-leases.index'),
            'liveSearch' => false
        ])

        {{-- 4. Table với outline variants cho actions --}}
        @include('staff.contract.master-leases.partials.table', [
            'leases' => $leases,
            'sortBy' => $sortBy ?? request('sort_by', 'created_at'),
            'sortOrder' => $sortOrder ?? request('sort_order', 'desc')
        ])
    </div>
</main>

@endsection

@push('scripts')
<script>
// HTMX đã tự động handle filters, không cần filterByStatus() và clearAllFilters() nữa

function deleteMasterLease(id, name) {
    // Sử dụng notification system
    Notify.confirmDelete(`hợp đồng "${name}"`, function() {
        // Hiển thị loading toast
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0 // Không tự động đóng
        });
        
        fetch(`/staff/master-leases/${id}`, {
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
                    const url = '{{ route("staff.master-leases.index") }}';
                    htmx.ajax('GET', url, {
                        target: '#master-leases-table-container',
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
