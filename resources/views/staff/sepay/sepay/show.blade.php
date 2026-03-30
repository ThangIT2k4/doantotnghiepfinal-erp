@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết giao dịch SePay')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-credit-card mr-2"></i>
                Chi tiết giao dịch SePay
            </h1>
            <p class="text-muted mb-0">Thông tin chi tiết giao dịch #{{ $transaction->sepay_transaction_id }}</p>
        </div>
        <div class="card-tools">
            <a href="{{ route('staff.sepay.transactions') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i>
                Quay lại
            </a>
            @if($transaction->status == 'failed')
                <button type="button" class="btn btn-warning btn-sm" onclick="retryTransaction({{ $transaction->id }})">
                    <i class="fas fa-redo mr-1"></i>
                    Thử lại
                </button>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Transaction Details -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>
                        Thông tin giao dịch
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>ID Giao dịch SePay:</strong></td>
                                    <td><span class="badge bg-light text-dark">#{{ $transaction->sepay_transaction_id }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Mã tham chiếu:</strong></td>
                                    <td>{{ $transaction->reference_code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày giao dịch:</strong></td>
                                    <td>
                                        <strong>{{ $transaction->transaction_date ? $transaction->transaction_date->format('d/m/Y H:i:s') : 'N/A' }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Ngân hàng:</strong></td>
                                    <td>{{ $transaction->gateway ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Loại giao dịch:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->transfer_type == 'in' ? 'success' : 'warning' }}">
                                            {{ $transaction->transfer_type == 'in' ? 'Tiền vào' : 'Tiền ra' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Số tiền:</strong></td>
                                    <td><strong class="text-success h5">{{ number_format($transaction->amount) }}đ</strong></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Phí giao dịch:</strong></td>
                                    <td>{{ $transaction->fee ? number_format($transaction->fee) . 'đ' : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Trạng thái:</strong></td>
                                    <td>
                                        @if($transaction->status == 'processed')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check mr-1"></i>Thành công
                                            </span>
                                        @elseif($transaction->status == 'failed')
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times mr-1"></i>Thất bại
                                            </span>
                                        @elseif($transaction->status == 'pending')
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock mr-1"></i>Đang chờ
                                            </span>
                                        @elseif($transaction->status == 'duplicate')
                                            <span class="badge bg-info">
                                                <i class="fas fa-copy mr-1"></i>Trùng lặp
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">{{ $transaction->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày tạo:</strong></td>
                                    <td>{{ $transaction->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Cập nhật lần cuối:</strong></td>
                                    <td>{{ $transaction->updated_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Thời gian xử lý:</strong></td>
                                    <td>{{ $transaction->created_at->diffForHumans() }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($transaction->content)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6><i class="fas fa-comment mr-2"></i>Nội dung giao dịch</h6>
                                <div class="alert alert-info">
                                    {{ $transaction->content }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($transaction->error_message)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6><i class="fas fa-exclamation-triangle mr-2"></i>Thông báo lỗi</h6>
                                <div class="alert alert-danger">
                                    {{ $transaction->error_message }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Related Information -->
        <div class="col-md-4">
            <!-- Invoice Information -->
            @if($transaction->invoice)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-file-invoice mr-2"></i>
                            Thông tin hóa đơn
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Mã hóa đơn:</strong>
                            <br>
                            <a href="{{ route('staff.invoices.show', $transaction->invoice_id) }}" class="text-primary">
                                {{ $transaction->invoice->invoice_no }}
                            </a>
                        </div>
                        
                        @if($transaction->invoice->lease)
                            <div class="mb-3">
                                <strong>Tài sản:</strong>
                                <br>
                                <span class="text-muted">{{ $transaction->invoice->lease->property->name ?? 'N/A' }}</span>
                            </div>
                            
                            @if($transaction->invoice->lease->tenant)
                                <div class="mb-3">
                                    <strong>Khách hàng:</strong>
                                    <br>
                                    <span class="text-muted">{{ $transaction->invoice->lease->tenant->name ?? 'N/A' }}</span>
                                </div>
                            @endif
                        @endif

                        <div class="mb-3">
                            <strong>Tổng tiền hóa đơn:</strong>
                            <br>
                            <span class="text-success">{{ number_format($transaction->invoice->total_amount) }}đ</span>
                        </div>

                        <div class="mb-3">
                            <strong>Trạng thái hóa đơn:</strong>
                            <br>
                            <span class="badge bg-{{ $transaction->invoice->status == 'paid' ? 'success' : 'warning' }}">
                                {{ $transaction->invoice->status == 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Payment Information -->
            @if($transaction->payment)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-money-bill-wave mr-2"></i>
                            Thông tin thanh toán
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>ID Thanh toán:</strong>
                            <br>
                            <span class="badge bg-light text-dark">#{{ $transaction->payment->id }}</span>
                        </div>

                        @if($transaction->payment->payerUser)
                            <div class="mb-3">
                                <strong>Người thanh toán:</strong>
                                <br>
                                <span class="text-muted">{{ $transaction->payment->payerUser->name ?? 'N/A' }}</span>
                            </div>
                        @endif

                        <div class="mb-3">
                            <strong>Số tiền thanh toán:</strong>
                            <br>
                            <span class="text-success">{{ number_format($transaction->payment->amount) }}đ</span>
                        </div>

                        <div class="mb-3">
                            <strong>Ngày thanh toán:</strong>
                            <br>
                            <span class="text-muted">{{ $transaction->payment->paid_at ? $transaction->payment->paid_at->format('d/m/Y H:i') : 'N/A' }}</span>
                        </div>

                        <div class="mb-3">
                            <strong>Trạng thái thanh toán:</strong>
                            <br>
                            <span class="badge bg-{{ $transaction->payment->status == 'success' ? 'success' : 'warning' }}">
                                {{ $transaction->payment->status == 'success' ? 'Thành công' : 'Đang chờ' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-cogs mr-2"></i>
                        Thao tác
                    </h5>
                </div>
                <div class="card-body">
                    @if($transaction->status == 'failed')
                        <button type="button" class="btn btn-warning btn-block mb-2" onclick="retryTransaction({{ $transaction->id }})">
                            <i class="fas fa-redo mr-1"></i>
                            Thử lại giao dịch
                        </button>
                    @endif
                    
                    <a href="{{ route('staff.sepay.transactions') }}" class="btn btn-secondary btn-block mb-2">
                        <i class="fas fa-list mr-1"></i>
                        Danh sách giao dịch
                    </a>
                    
                    @if($transaction->invoice)
                        <a href="{{ route('staff.invoices.show', $transaction->invoice_id) }}" class="btn btn-info btn-block">
                            <i class="fas fa-file-invoice mr-1"></i>
                            Xem hóa đơn
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Forms -->
<form id="retryForm" method="POST" style="display: none;">
    @csrf
</form>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
function retryTransaction(transactionId) {
    Notify.confirm({
        title: 'Thử lại giao dịch',
        message: 'Bạn có chắc chắn muốn thử lại giao dịch này?',
        type: 'warning',
        confirmText: 'Thử lại',
        onConfirm: function() {
            const form = document.getElementById('retryForm');
            form.action = `/staff/sepay/${transactionId}/retry`;
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
