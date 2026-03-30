@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Hóa đơn Công ty')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với solid variants --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý Hóa đơn Công ty',
            'subtitle' => 'Quản lý hóa đơn công ty trong hệ thống',
            'icon' => 'fas fa-file-invoice',
            'actions' => [
                [
                    'variant' => 'success',        // Solid
                    'label' => 'Thống kê',
                    'icon' => 'fas fa-chart-bar',
                    'url' => '#',
                    'onclick' => "$('#statisticsModal').modal('show'); return false;"
                ],
                [
                    'variant' => 'primary',        // Solid
                    'label' => 'Thêm hóa đơn mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.company-invoices.create')
                ]
            ]
        ])

        {{-- 2. Statistics Cards với HTMX --}}
        <div id="statistics-cards-container">
            @php
                $statsFormatted = [
                    'total' => [
                        'value' => $stats['total'] ?? 0,
                        'label' => 'Tổng cộng',
                        'icon' => 'fa-list',
                        'color' => 'primary',
                        'filter' => '',
                    ],
                    'master_lease' => [
                        'value' => $stats['master_lease'] ?? 0,
                        'label' => 'Hợp đồng tổng',
                        'icon' => 'fa-file-contract',
                        'color' => 'info',
                        'filter' => 'master_lease',
                    ],
                    'ticket_cost' => [
                        'value' => $stats['ticket_cost'] ?? 0,
                        'label' => 'Chi phí ticket',
                        'icon' => 'fa-ticket-alt',
                        'color' => 'warning',
                        'filter' => 'ticket_cost',
                    ],
                    'deposit_refund' => [
                        'value' => $stats['deposit_refund'] ?? 0,
                        'label' => 'Hoàn tiền cọc',
                        'icon' => 'fa-money-bill-wave',
                        'color' => 'success',
                        'filter' => 'deposit_refund',
                    ],
                    // payroll_payslip (temporarily disabled)
                    // 'payroll_payslip' => [
                    //     'value' => $stats['payroll_payslip'] ?? 0,
                    //     'label' => 'Lương nhân viên',
                    //     'icon' => 'fa-money-check-alt',
                    //     'color' => 'primary',
                    //     'filter' => 'payroll_payslip',
                    // ],
                    'utility' => [
                        'value' => $stats['utility'] ?? 0,
                        'label' => 'Tiện ích',
                        'icon' => 'fa-bolt',
                        'color' => 'secondary',
                        'filter' => 'utility',
                    ],
                    'maintenance' => [
                        'value' => $stats['maintenance'] ?? 0,
                        'label' => 'Bảo trì',
                        'icon' => 'fa-tools',
                        'color' => 'danger',
                        'filter' => 'maintenance',
                    ],
                    'service' => [
                        'value' => $stats['service'] ?? 0,
                        'label' => 'Dịch vụ',
                        'icon' => 'fa-concierge-bell',
                        'color' => 'info',
                        'filter' => 'service',
                    ],
                    'supply' => [
                        'value' => $stats['supply'] ?? 0,
                        'label' => 'Cung cấp',
                        'icon' => 'fa-box',
                        'color' => 'warning',
                        'filter' => 'supply',
                    ],
                    'other' => [
                        'value' => $stats['other'] ?? 0,
                        'label' => 'Khác',
                        'icon' => 'fa-ellipsis-h',
                        'color' => 'dark',
                        'filter' => 'other',
                    ],
                ];
                $currentType = request('invoice_type', '');
            @endphp
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => $currentType,
                'filterKey' => 'invoice_type',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'company-invoices-table-container',
                'action' => route('staff.company-invoices.index'),
                'columns' => 6
            ])
        </div>

        {{-- 3. Filters với HTMX --}}
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.company-invoices.index'),
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Số hóa đơn, mô tả...',
                    'value' => request('search'),
                    'live_search' => true,
                    'col' => 'col-md-2'
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
                    'options' => $types,
                    'value' => request('invoice_type'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả loại'
                ],
                [
                    'name' => 'vendor_id',
                    'label' => 'Nhà cung cấp',
                    'type' => 'select',
                    'options' => $vendors->pluck('name', 'id')->toArray(),
                    'value' => request('vendor_id'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả nhà cung cấp'
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
                ],
            ],
            'tableContainerId' => 'company-invoices-table-container',
            'resetUrl' => route('staff.company-invoices.index')
        ])

        {{-- 4. Table Container (sẽ được cập nhật bằng HTMX) --}}
            @include('staff.finance.company-invoices.partials.table', [
                'invoices' => $invoices,
                'statuses' => $statuses,
                'types' => $types,
                'sortBy' => $sortBy ?? request('sort_by', 'created_at'),
                'sortOrder' => $sortOrder ?? request('sort_order', 'desc')
            ])
    </div>
</main>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đánh dấu đã thanh toán</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="payment-form">
                <div class="modal-body">
                    <input type="hidden" id="payment-invoice-id">
                    <div class="form-group">
                        <label>Phương thức thanh toán <span class="text-danger">*</span></label>
                        <select name="payment_method_id" class="form-control" required>
                            <option value="">Chọn phương thức thanh toán</option>
                            @foreach(\App\Models\PaymentMethod::all() as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày thanh toán <span class="text-danger">*</span></label>
                        <input type="date" name="paid_at" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Mã giao dịch</label>
                        <input type="text" name="txn_ref" class="form-control" placeholder="Mã giao dịch">
                    </div>
                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Ghi chú về thanh toán"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Xác nhận thanh toán</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Statistics Modal -->
<div class="modal fade" id="statisticsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thống kê Hóa đơn Công ty</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="statistics-content">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i><br>
                    Đang tải thống kê...
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>

// Individual actions
function approveInvoice(invoiceId) {
    if (typeof Notify !== 'undefined' && Notify.confirm) {
        Notify.confirm('Bạn có chắc chắn muốn duyệt hóa đơn này?', () => {
            fetch(`/staff/company-invoices/${invoiceId}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                    Notify.success(data.message, 'Thành công!');
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
                Notify.error('Có lỗi xảy ra', 'Lỗi!');
            });
        });
    } else {
    if (confirm('Bạn có chắc chắn muốn duyệt hóa đơn này?')) {
        fetch(`/staff/company-invoices/${invoiceId}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            console.error('Error:', error);
                alert('Có lỗi xảy ra');
        });
        }
    }
}

function cancelInvoice(invoiceId) {
    if (typeof Notify !== 'undefined' && Notify.confirm) {
        Notify.confirm('Bạn có chắc chắn muốn hủy hóa đơn này?', () => {
            fetch(`/staff/company-invoices/${invoiceId}/cancel`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Notify.success(data.message, 'Thành công!');
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
                Notify.error('Có lỗi xảy ra', 'Lỗi!');
            });
        });
    } else {
    if (confirm('Bạn có chắc chắn muốn hủy hóa đơn này?')) {
        fetch(`/staff/company-invoices/${invoiceId}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            console.error('Error:', error);
                alert('Có lỗi xảy ra');
        });
        }
    }
}

function markOverdue(invoiceId) {
    if (typeof Notify !== 'undefined' && Notify.confirm) {
        Notify.confirm('Bạn có chắc chắn muốn đánh dấu hóa đơn này là quá hạn?', () => {
            fetch(`/staff/company-invoices/${invoiceId}/mark-overdue`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Notify.success(data.message, 'Thành công!');
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
                Notify.error('Có lỗi xảy ra', 'Lỗi!');
            });
        });
    } else {
    if (confirm('Bạn có chắc chắn muốn đánh dấu hóa đơn này là quá hạn?')) {
        fetch(`/staff/company-invoices/${invoiceId}/mark-overdue`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            console.error('Error:', error);
                alert('Có lỗi xảy ra');
        });
        }
    }
}

function markAsPaid(invoiceId) {
    document.getElementById('payment-invoice-id').value = invoiceId;
    $('#paymentModal').modal('show');
}

function deleteInvoice(invoiceId, invoiceNo = '') {
    const message = invoiceNo 
        ? `hóa đơn "${invoiceNo}"`
        : 'hóa đơn này';
    
    if (typeof Notify !== 'undefined' && Notify.confirmDelete) {
        Notify.confirmDelete(message, () => {
        fetch(`/staff/company-invoices/${invoiceId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                    Notify.success(data.message, 'Đã xóa!');
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
                Notify.error('Có lỗi xảy ra', 'Lỗi!');
            });
        });
    } else {
        const confirmMessage = invoiceNo 
            ? `Bạn có chắc chắn muốn xóa hóa đơn "${invoiceNo}"? Hành động này không thể hoàn tác.`
            : 'Bạn có chắc chắn muốn xóa hóa đơn này? Hành động này không thể hoàn tác.';
        
        if (confirm(confirmMessage)) {
            fetch(`/staff/company-invoices/${invoiceId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
            } else {
                    alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(error => {
            console.error('Error:', error);
                alert('Có lỗi xảy ra');
        });
        }
    }
}

// Payment form submission
document.getElementById('payment-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const invoiceId = document.getElementById('payment-invoice-id').value;
    const formData = new FormData(this);
    formData.append('_token', '{{ csrf_token() }}');
    
    fetch(`/staff/company-invoices/${invoiceId}/mark-paid`, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof Notify !== 'undefined') {
                Notify.success(data.message, 'Thành công!');
            } else {
                alert(data.message);
            }
            $('#paymentModal').modal('hide');
            setTimeout(() => {
                const form = document.getElementById('index-filters-form');
                if (form && typeof htmx !== 'undefined') {
                    htmx.trigger(form, 'submit');
            } else {
                    location.reload();
                }
                }, 1500);
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof Notify !== 'undefined') {
            Notify.error('Có lỗi xảy ra', 'Lỗi!');
        } else {
            alert('Có lỗi xảy ra');
        }
    });
});

// Statistics
document.getElementById('statistics-btn')?.addEventListener('click', function() {
    $('#statisticsModal').modal('show');
    
    fetch('{{ route("staff.company-invoices.statistics") }}', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const stats = data.stats;
            const typeStats = data.type_stats;
            
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Tổng quan</h6>
                        <table class="table table-sm">
                            <tr><td>Tổng số hóa đơn:</td><td><strong>${stats.total_count}</strong></td></tr>
                            <tr><td>Tổng giá trị:</td><td><strong>${new Intl.NumberFormat('vi-VN').format(stats.total_amount)} VND</strong></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Theo trạng thái</h6>
                        <table class="table table-sm">
                            <tr><td>Nháp:</td><td>${stats.draft_count} (${new Intl.NumberFormat('vi-VN').format(stats.draft_amount)} VND)</td></tr>
                            <tr><td>Chờ duyệt:</td><td>${stats.pending_count} (${new Intl.NumberFormat('vi-VN').format(stats.pending_amount)} VND)</td></tr>
                            <tr><td>Đã duyệt:</td><td>${stats.approved_count} (${new Intl.NumberFormat('vi-VN').format(stats.approved_amount)} VND)</td></tr>
                            <tr><td>Đã thanh toán:</td><td>${stats.paid_count} (${new Intl.NumberFormat('vi-VN').format(stats.paid_amount)} VND)</td></tr>
                            <tr><td>Quá hạn:</td><td>${stats.overdue_count} (${new Intl.NumberFormat('vi-VN').format(stats.overdue_amount)} VND)</td></tr>
                            <tr><td>Đã hủy:</td><td>${stats.cancelled_count} (${new Intl.NumberFormat('vi-VN').format(stats.cancelled_amount)} VND)</td></tr>
                        </table>
                    </div>
                </div>
            `;
            
            if (typeStats && typeStats.length > 0) {
                html += `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Theo loại hóa đơn</h6>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Loại</th>
                                        <th>Số lượng</th>
                                        <th>Tổng giá trị</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                
                typeStats.forEach(type => {
                    html += `
                        <tr>
                            <td>${type.invoice_type}</td>
                            <td>${type.count}</td>
                            <td>${new Intl.NumberFormat('vi-VN').format(type.total_amount)} VND</td>
                        </tr>
                    `;
                });
                
                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }
            
            document.getElementById('statistics-content').innerHTML = html;
        } else {
            document.getElementById('statistics-content').innerHTML = '<div class="text-danger">Có lỗi xảy ra khi tải thống kê</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('statistics-content').innerHTML = '<div class="text-danger">Có lỗi xảy ra khi tải thống kê</div>';
    });
});
</script>
@endpush
