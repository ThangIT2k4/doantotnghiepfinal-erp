@extends('layouts.staff_dashboard')

@section('title', 'Quản lý thanh toán')

@section('content')
<main class="main-content">
    <div class="container-fluid">

        {{-- 1. Page Header với solid variants --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý thanh toán',
            'subtitle' => 'Danh sách tất cả thanh toán trong hệ thống',
            'icon' => 'fas fa-credit-card',
            'actions' => [
                [
                    'variant' => 'primary',        // ✅ Solid
                    'label' => 'Tạo thanh toán mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.payments.create')
                ]
            ]
        ])

        {{-- 2. Statistics Cards với HTMX --}}
        <div id="statistics-cards-container">
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted ?? [],
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'payments-table-container',
                'action' => route('staff.payments.index'),
                'columns' => 5
            ])
        </div>

        {{-- 3. Filters với HTMX --}}
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.payments.index'),
            'tableContainerId' => 'payments-table-container',
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Mã giao dịch, ghi chú, hóa đơn, khách hàng...',
                    'value' => request('search'),
                    'live_search' => true,
                    'col' => 'col-md-4'
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'options' => $statuses ?? [],
                    'value' => request('status'),
                    'live_search' => true,
                    'col' => 'col-md-3',
                    'empty_option' => 'Tất cả trạng thái'
                ]
            ],
            'resetUrl' => route('staff.payments.index')
        ])

        {{-- 4. Table Container (sẽ được cập nhật bằng HTMX) --}}
        @include('staff.billing.payments.partials.table', [
            'payments' => $payments,
            'statuses' => $statuses ?? [],
            'sortBy' => $sortBy ?? request('sort_by', 'created_at'),
            'sortOrder' => $sortOrder ?? request('sort_order', 'desc')
        ])
    </div>
</main>
@endsection

@push('scripts')
<script>

function markAsPaid(paymentId) {
    Notify.confirm({
        title: 'Xác nhận thanh toán',
        message: 'Bạn có chắc chắn muốn đánh dấu thanh toán này là đã thanh toán?',
        type: 'info',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch(`/staff/payments/${paymentId}/mark-as-paid`, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success || !data.error) {
                    Notify.success('Thanh toán đã được đánh dấu là thành công!', 'Thành công!');
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
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Không thể cập nhật trạng thái thanh toán', 'Lỗi hệ thống!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    });
}

function deletePayment(paymentId, paymentLabel) {
    Notify.confirmDelete(paymentLabel, function() {
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('_method', 'DELETE');
        
        fetch(`/staff/payments/${paymentId}`, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || !data.error) {
                Notify.success('Thanh toán đã được xóa thành công!', 'Thành công!');
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
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể xóa thanh toán', 'Lỗi hệ thống!');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
}

// Show success/error messages from session
@if(session('success'))
    Notify.success('{{ session('success') }}');
@endif

@if(session('error'))
    Notify.error('{{ session('error') }}');
@endif

@if(session('warning'))
    Notify.warning('{{ session('warning') }}');
@endif

@if(session('info'))
    Notify.info('{{ session('info') }}');
@endif
</script>
@endpush
