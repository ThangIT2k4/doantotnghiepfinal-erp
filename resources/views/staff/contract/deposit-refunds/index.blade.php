@extends('layouts.staff_dashboard')

@section('title', 'Quản lý hoàn tiền cọc')

@section('content')
<main class="main-content">
<div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý hoàn tiền cọc',
            'subtitle' => 'Quản lý tất cả yêu cầu hoàn tiền cọc trong tổ chức',
            'icon' => 'fas fa-money-bill-wave',
            'actions' => [
                [
                    'variant' => 'primary',
                    'label' => 'Tạo yêu cầu hoàn tiền',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.deposit-refunds.create')
                ]
            ]
        ])

        {{-- Statistics Cards --}}
        @php
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng yêu cầu',
                    'icon' => 'fa-list',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'pending' => [
                    'value' => $stats['pending'] ?? 0,
                    'label' => 'Chờ phê duyệt',
                    'icon' => 'fa-clock',
                    'color' => 'warning',
                    'filter' => 'pending',
                ],
                'approved' => [
                    'value' => $stats['approved'] ?? 0,
                    'label' => 'Đã phê duyệt',
                    'icon' => 'fa-check-circle',
                    'color' => 'info',
                    'filter' => 'approved',
                ],
                'paid' => [
                    'value' => $stats['paid'] ?? 0,
                    'label' => 'Đã thanh toán',
                    'icon' => 'fa-money-bill-wave',
                    'color' => 'success',
                    'filter' => 'paid',
                ],
                'cancelled' => [
                    'value' => $stats['cancelled'] ?? 0,
                    'label' => 'Đã hủy',
                    'icon' => 'fa-times-circle',
                    'color' => 'secondary',
                    'filter' => 'cancelled',
                ],
                'total_amount' => [
                    'value' => (float)($stats['total_amount'] ?? 0),
                    'label' => 'Tổng tiền',
                    'icon' => 'fa-dollar-sign',
                    'color' => 'success',
                    'filter' => '',
                    'format' => 'currency',
                ],
            ];
        @endphp
        @include('staff.components.statistics-cards', [
            'stats' => $statsFormatted,
            'currentFilter' => request('status', ''),
            'filterKey' => 'status',
            'onFilterClick' => 'htmx-filter',
            'onClearClick' => 'htmx-clear',
            'tableContainerId' => 'deposit-refunds-table-container',
            'action' => route('staff.deposit-refunds.index'),
            'columns' => 6
        ])

        {{-- Filters với HTMX --}}
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.deposit-refunds.index'),
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Mã hợp đồng, tên khách thuê, agent...',
                    'value' => request('search'),
                    'live_search' => true,
                    'col' => 'col-md-2'
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'options' => [
                        '' => 'Tất cả',
                        'pending' => 'Chờ phê duyệt',
                        'approved' => 'Đã phê duyệt',
                        'paid' => 'Đã thanh toán',
                        'cancelled' => 'Đã hủy'
                    ],
                    'value' => request('status'),
                    'live_search' => true,
                    'col' => 'col-md-2'
                ],
                [
                    'name' => 'refund_method',
                    'label' => 'Phương thức',
                    'type' => 'select',
                    'options' => [
                        '' => 'Tất cả',
                        'cash' => 'Tiền mặt',
                        'bank_transfer' => 'Chuyển khoản',
                        'wallet' => 'Ví điện tử'
                    ],
                    'value' => request('refund_method'),
                    'live_search' => true,
                    'col' => 'col-md-2'
                ],
                [
                    'name' => 'agent_id',
                    'label' => 'Agent',
                    'type' => 'select',
                    'options' => collect($agents ?? [])->pluck('full_name', 'id')->prepend('Tất cả agents', '')->toArray(),
                    'value' => request('agent_id'),
                    'live_search' => true,
                    'col' => 'col-md-2'
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
            'tableContainerId' => 'deposit-refunds-table-container',
            'resetUrl' => route('staff.deposit-refunds.index')
        ])

        {{-- Table Container --}}
        <div id="deposit-refunds-table-container">
            @include('staff.contract.deposit-refunds.partials.table', [
                'depositRefunds' => $depositRefunds
            ])
        </div>
    </div>
</main>

<!-- Mark Paid Modals -->
@foreach($depositRefunds as $refund)
    @if($refund->status === 'approved')
        <div class="modal fade" id="markPaidModal{{ $refund->id }}" tabindex="-1" aria-labelledby="markPaidModal{{ $refund->id }}Label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="markPaidModal{{ $refund->id }}Label">
                            <i class="fas fa-money-bill-wave me-2"></i>Đánh dấu đã thanh toán
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('staff.deposit-refunds.mark-paid', $refund->id) }}">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Thông tin hoàn tiền:</strong> {{ number_format($refund->refund_amount) }}đ - {{ $refund->refund_method_label }}
                            </div>
                            
                            <div class="mb-3">
                                <label for="refund_reference{{ $refund->id }}" class="form-label">Mã tham chiếu thanh toán</label>
                                <small class="text-muted d-block mb-2">(Để trống để tự động tạo)</small>
                                <input type="text" class="form-control" id="refund_reference{{ $refund->id }}" name="refund_reference" 
                                       placeholder="Nhập mã giao dịch, số hóa đơn... (để trống để tự động tạo)">
                                <div class="form-text">Ví dụ: GD123456, HD789012, hoặc mã giao dịch ngân hàng</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="payment_notes{{ $refund->id }}" class="form-label">Ghi chú thanh toán</label>
                                <textarea class="form-control" id="payment_notes{{ $refund->id }}" name="payment_notes" rows="2" 
                                          placeholder="Ghi chú về việc thanh toán..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="button" class="btn btn-success" onclick="markRefundPaid({{ $refund->id }})">
                                <i class="fas fa-check me-1"></i>Đánh dấu đã thanh toán
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection

@push('scripts')
<script>
// Approve refund function
function approveRefund(refundId) {
    Notify.confirm({
        title: 'Phê duyệt hoàn tiền',
        message: 'Bạn có chắc chắn muốn phê duyệt yêu cầu hoàn tiền này?',
        details: 'Yêu cầu sẽ được chuyển sang trạng thái "Đã phê duyệt" và có thể thực hiện thanh toán.',
        type: 'success',
        confirmText: 'Phê duyệt',
        cancelText: 'Hủy',
        onConfirm: function() {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/staff/deposit-refunds/${refundId}/approve`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Cancel refund function
function cancelRefund(refundId) {
    Notify.confirm({
        title: 'Hủy yêu cầu hoàn tiền',
        message: 'Bạn có chắc chắn muốn hủy yêu cầu hoàn tiền này?',
        details: 'Yêu cầu sẽ được chuyển sang trạng thái "Đã hủy" và không thể khôi phục.',
        type: 'danger',
        confirmText: 'Hủy yêu cầu',
        cancelText: 'Không',
        onConfirm: function() {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/staff/deposit-refunds/${refundId}/cancel`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Mark refund as paid function
function markRefundPaid(refundId) {
    const refundReference = document.getElementById(`refund_reference${refundId}`).value;
    const paymentNotes = document.getElementById(`payment_notes${refundId}`).value;
    
    // refund_reference is optional, will be auto-generated if empty
    const detailsText = refundReference.trim() 
        ? `Mã tham chiếu: ${refundReference}` 
        : 'Mã tham chiếu sẽ được tự động tạo';
    
    Notify.confirm({
        title: 'Đánh dấu đã thanh toán',
        message: 'Bạn có chắc chắn đã thực hiện thanh toán hoàn tiền?',
        details: detailsText,
        type: 'success',
        confirmText: 'Đã thanh toán',
        cancelText: 'Chưa',
        onConfirm: function() {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/staff/deposit-refunds/${refundId}/mark-paid`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            const refundRefInput = document.createElement('input');
            refundRefInput.type = 'hidden';
            refundRefInput.name = 'refund_reference';
            refundRefInput.value = refundReference;
            
            const paymentNotesInput = document.createElement('input');
            paymentNotesInput.type = 'hidden';
            paymentNotesInput.name = 'payment_notes';
            paymentNotesInput.value = paymentNotes;
            
            form.appendChild(csrfToken);
            form.appendChild(refundRefInput);
            form.appendChild(paymentNotesInput);
            
            document.body.appendChild(form);
            form.submit();
        }
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
