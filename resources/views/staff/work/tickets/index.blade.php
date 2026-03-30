@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Ticket')

@section('content')
<main class="main-content">
<div class="container-fluid">
        {{-- 1. Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý Ticket',
            'subtitle' => 'Quản lý các ticket bảo trì và sự cố',
            'icon' => 'fas fa-ticket-alt',
            'actions' => [
                [
                    'variant' => 'primary',
                    'label' => 'Tạo Ticket Mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.tickets.create')
                ]
            ]
        ])

        {{-- 2. Statistics Cards với HTMX --}}
        <div id="statistics-cards-container">
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted ?? [],
                'currentFilter' => $currentStatus ?? request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'tickets-table-container',
                'action' => route('staff.tickets.index'),
                'columns' => 6
            ])
        </div>

        {{-- 3. Filters với HTMX --}}
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.tickets.index'),
            'tableContainerId' => 'tickets-table-container',
            'fields' => [
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'options' => [
                        '' => 'Tất cả',
                        'open' => 'Mở',
                        'in_progress' => 'Đang xử lý',
                        'resolved' => 'Đã giải quyết',
                        'closed' => 'Đã đóng',
                        'cancelled' => 'Đã hủy',
                    ],
                    'value' => request('status'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả trạng thái'
                ],
                [
                    'name' => 'priority_id',
                    'label' => 'Độ ưu tiên',
                    'type' => 'select',
                    'options' => collect(['' => 'Tất cả'])->merge(
                        ($priorities ?? [])->mapWithKeys(function($priority) {
                            return [$priority->id => $priority->name];
                        })
                    )->toArray(),
                    'value' => request('priority_id'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả độ ưu tiên'
                ],
                [
                    'name' => 'assigned_to',
                    'label' => 'Người phụ trách',
                    'type' => 'select',
                    'options' => collect(['' => 'Tất cả'])->merge(
                        ($users ?? [])->mapWithKeys(function($user) {
                            return [$user->id => $user->full_name];
                        })
                    )->toArray(),
                    'value' => request('assigned_to'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả người phụ trách'
                ],
                [
                    'name' => 'created_by',
                    'label' => 'Người tạo',
                    'type' => 'select',
                    'options' => collect(['' => 'Tất cả'])->merge(
                        ($users ?? [])->mapWithKeys(function($user) {
                            return [$user->id => $user->full_name];
                        })
                    )->toArray(),
                    'value' => request('created_by'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả người tạo'
                ],
                [
                    'name' => 'unit_id',
                    'label' => 'Phòng',
                    'type' => 'select',
                    'options' => collect(['' => 'Tất cả'])->merge(
                        ($units ?? [])->mapWithKeys(function($unit) {
                            return [$unit->id => ($unit->property ? $unit->property->name : 'N/A') . ' - ' . $unit->code];
                        })
                    )->toArray(),
                    'value' => request('unit_id'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả phòng'
                ],
                [
                    'name' => 'lease_id',
                    'label' => 'Hợp đồng',
                    'type' => 'select',
                    'options' => collect(['' => 'Tất cả'])->merge(
                        ($leases ?? [])->mapWithKeys(function($lease) {
                            return [$lease->id => ($lease->contract_no ?: 'HD#' . $lease->id) . ' - ' . ($lease->tenant ? $lease->tenant->full_name : 'N/A')];
                        })
                    )->toArray(),
                    'value' => request('lease_id'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả hợp đồng'
                ],
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Tìm theo tiêu đề hoặc mô tả...',
                    'value' => request('search'),
                    'live_search' => true,
                    'col' => 'col-md-12',
                ],
            ],
            'resetUrl' => route('staff.tickets.index')
        ])

        {{-- 4. Table Container (sẽ được cập nhật bằng HTMX) --}}
        @include('staff.work.tickets.partials.table', [
            'tickets' => $tickets,
            'sortBy' => $sortBy ?? request('sort_by', 'created_at'),
            'sortOrder' => $sortOrder ?? request('sort_order', 'desc')
        ])
    </div>
</main>

@push('scripts')
<script>
// Delete ticket function
function deleteTicket(id, name) {
    Notify.confirmDelete(`ticket "${name}"`, function() {
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        fetch(`/staff/tickets/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Xóa thành công!');
                
                // Reload table via HTMX
                setTimeout(() => {
                    const form = document.getElementById('index-filters-form');
                    if (form && typeof htmx !== 'undefined') {
                        htmx.trigger(form, 'submit');
                    } else {
                        location.reload();
                    }
                }, 1500);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Không thể xóa ticket');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi xóa ticket. Vui lòng thử lại.', 'Lỗi hệ thống');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
}
</script>
@endpush
@endsection
