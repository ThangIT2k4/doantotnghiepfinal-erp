@extends('layouts.staff_dashboard')

@section('title', 'Quản lý hóa đơn')

@section('content')
<main class="main-content">
    <div class="container-fluid">

        <!-- Session Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- 1. Page Header với solid variants --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý hóa đơn',
            'subtitle' => 'Danh sách tất cả hóa đơn trong hệ thống',
            'icon' => 'fas fa-file-invoice',
            'actions' => [
                [
                    'variant' => 'primary',        // Solid
                    'label' => 'Tạo hóa đơn mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.invoices.create')
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
                'tableContainerId' => 'invoices-table-container',
                'action' => route('staff.invoices.index'),
                'columns' => 6
            ])
        </div>

        {{-- 3. Filters với HTMX --}}
        @php
            $statuses = [
                'draft' => 'Nháp',
                'issued' => 'Đã phát hành',
                'paid' => 'Đã thanh toán',
                'overdue' => 'Quá hạn',
                'cancelled' => 'Đã hủy'
            ];
            $invoiceTypes = [
                'monthly_rent' => 'Tiền thuê hàng tháng',
                'first_invoice' => 'Hoá đơn đầu',
                'booking_deposit' => 'Hoá đơn đặt cọc',
                'other' => 'Khác'
            ];
        @endphp
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.invoices.index'),
            'tableContainerId' => 'invoices-table-container',
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Số hóa đơn, tên khách thuê, BĐS...',
                    'value' => request('search'),
                    'live_search' => true,
                    'col' => 'col-md-3'
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'options' => $statuses,
                    'value' => request('status'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả trạng thái'
                ],
                [
                    'name' => 'invoice_type',
                    'label' => 'Loại hóa đơn',
                    'type' => 'select',
                    'options' => $invoiceTypes,
                    'value' => request('invoice_type'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả loại'
                ],
                [
                    'name' => 'lease_id',
                    'label' => 'Hợp đồng',
                    'type' => 'select',
                    'options' => $leases->pluck('contract_no', 'id')->map(function($contractNo, $id) use ($leases) {
                        $lease = $leases->firstWhere('id', $id);
                        return ($contractNo ?? 'HD#' . $id) . ' - ' . ($lease->tenant->full_name ?? 'N/A');
                    })->toArray(),
                    'value' => request('lease_id'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả hợp đồng'
                ],
                [
                    'name' => 'date_from',
                    'label' => 'Từ ngày',
                    'type' => 'date',
                    'value' => request('date_from'),
                    'live_search' => true,
                    'col' => 'col-md-2'
                ],
                [
                    'name' => 'date_to',
                    'label' => 'Đến ngày',
                    'type' => 'date',
                    'value' => request('date_to'),
                    'live_search' => true,
                    'col' => 'col-md-2'
                ]
            ],
            'resetUrl' => route('staff.invoices.index')
        ])

        {{-- 4. Table Container (sẽ được cập nhật bằng HTMX) --}}
        @include('staff.billing.invoices.partials.table', [
            'invoices' => $invoices,
            'sortBy' => $sortBy ?? request('sort_by', 'created_at'),
            'sortOrder' => $sortOrder ?? request('sort_order', 'desc')
        ])
    </div>
</main>
@endsection

@push('styles')
<style>
.stat-card {
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.stat-card.border-primary,
.stat-card.border-warning,
.stat-card.border-success,
.stat-card.border-danger,
.stat-card.border-info,
.stat-card.border-secondary {
    border-color: currentColor !important;
}
</style>
@endpush

@push('scripts')
<script>

function deleteInvoice(id, name) {
    // Sử dụng notification system
    Notify.confirmDelete(`hóa đơn "${name}"`, function() {
        // Hiển thị loading toast
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0 // Không tự động đóng
        });
        
        fetch(`/staff/invoices/${id}`, {
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
                // Hiển thị thông báo lỗi
                Notify.error(data.message, 'Không thể xóa hóa đơn');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Hiển thị thông báo lỗi
            Notify.error('Có lỗi xảy ra khi xóa hóa đơn. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
    });
}

function markAsPaid(id) {
    Notify.confirm({
        title: 'Đánh dấu đã thanh toán',
        message: 'Bạn có chắc chắn muốn đánh dấu hóa đơn này là đã thanh toán?',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const loadingToast = Notify.toast({
                title: 'Đang xử lý...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });
            
            fetch(`/staff/invoices/${id}/mark-paid`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Notify.success(data.message, 'Thành công!');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        // Refresh table using loadTableData
                        if (typeof loadTableData === 'function') {
                            loadTableData();
                    } else {
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                        }
                    }
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Có lỗi xảy ra khi đánh dấu hóa đơn. Vui lòng thử lại.', 'Lỗi hệ thống');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    });
}

function issueInvoice(id) {
    Notify.confirm({
        title: 'Phát hành hóa đơn',
        message: 'Bạn có chắc chắn muốn phát hành hóa đơn này?',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const loadingToast = Notify.toast({
                title: 'Đang xử lý...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });
            
            fetch(`/staff/invoices/${id}/issue`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Notify.success(data.message, 'Thành công!');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        // Refresh table using loadTableData
                        if (typeof loadTableData === 'function') {
                            loadTableData();
                    } else {
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                        }
                    }
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Có lỗi xảy ra khi phát hành hóa đơn. Vui lòng thử lại.', 'Lỗi hệ thống');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    });
}
</script>
@endpush
