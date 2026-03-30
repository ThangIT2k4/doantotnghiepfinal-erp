@extends('layouts.staff_dashboard')

@section('title', 'Giao dịch SePay')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-list mr-2"></i>
                Giao dịch SePay
            </h1>
            <p class="text-muted mb-0">Danh sách tất cả giao dịch thanh toán SePay</p>
        </div>
        <div class="card-tools">
            <a href="{{ route('staff.sepay.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i>
                Dashboard
            </a>
            <a href="{{ route('staff.sepay.export', request()->query()) }}" class="btn btn-info btn-sm">
                <i class="fas fa-download mr-1"></i>
                Xuất Excel
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-filter mr-2"></i>
                Bộ lọc
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('staff.sepay.transactions') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Tìm kiếm</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Mã giao dịch, hóa đơn, nội dung...">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Trạng thái</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tất cả</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Từ ngày</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Đến ngày</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label for="amount_from" class="form-label">Số tiền từ</label>
                    <input type="number" class="form-control" id="amount_from" name="amount_from" 
                           value="{{ request('amount_from') }}" placeholder="0">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            <div class="row mt-3">
                <div class="col-12">
                    <a href="{{ route('staff.sepay.transactions') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-redo mr-1"></i>
                        Xóa bộ lọc
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-table mr-2"></i>
                Danh sách giao dịch
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID Giao Dịch</th>
                            <th>Ngày GD</th>
                            <th>Ngân Hàng</th>
                            <th>Số Tiền</th>
                            <th>Nội Dung</th>
                            <th>Hóa Đơn</th>
                            <th>Trạng Thái</th>
                            <th>Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr>
                            <td>
                                <div>
                                    <strong>#{{ $transaction->sepay_transaction_id }}</strong>
                                    @if($transaction->reference_code)
                                        <br><small class="text-muted">{{ $transaction->reference_code }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $transaction->transaction_date ? $transaction->transaction_date->format('d/m/Y H:i') : '-' }}</strong>
                                    <br><small class="text-muted">{{ $transaction->created_at->diffForHumans() }}</small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $transaction->gateway ?? '-' }}</strong>
                                    @if($transaction->transfer_type)
                                        <br><small class="text-muted">
                                            {{ $transaction->transfer_type == 'in' ? 'Tiền vào' : 'Tiền ra' }}
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong class="text-success">{{ number_format($transaction->amount) }}đ</strong>
                                    @if($transaction->fee > 0)
                                        <br><small class="text-muted">Phí: {{ number_format($transaction->fee) }}đ</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="{{ $transaction->content }}">
                                    {{ $transaction->content }}
                                </div>
                            </td>
                            <td>
                                @if($transaction->invoice)
                                    <a href="{{ route('staff.invoices.show', $transaction->invoice_id) }}" 
                                       class="text-primary">
                                        <strong>{{ $transaction->invoice->invoice_no }}</strong>
                                    </a>
                                    @if($transaction->invoice->lease)
                                        <br><small class="text-muted">{{ $transaction->invoice->lease->property->name ?? 'N/A' }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
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
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('staff.sepay.show', $transaction->id) }}" 
                                       class="btn btn-outline-primary" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($transaction->status == 'failed')
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="retryTransaction({{ $transaction->id }})" title="Thử lại">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <h5 class="text-muted">Không có giao dịch nào</h5>
                                <p class="text-muted">Chưa có giao dịch SePay nào được ghi nhận.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($transactions->hasPages())
            <div class="card-footer">
                {{ $transactions->appends(request()->query())->links('vendor.pagination.custom', [
                    'tableContainerId' => 'sepay-transactions-table-container',
                    'htmxIndicator' => '#htmx-loading-index-filters-form'
                ]) }}
            </div>
        @endif
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
