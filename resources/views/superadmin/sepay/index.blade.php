@extends('layouts.superadmin')

@section('title', 'Quản lý SePay - Toàn hệ thống')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">SePay Management</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-credit-card me-2"></i>
                Quản lý SePay - Toàn hệ thống
            </h1>
            <p class="text-muted mb-0">Theo dõi và quyết toán giao dịch SePay cho tất cả tổ chức</p>
        </div>
        <div>
            <a href="{{ route('superadmin.sepay.settlement') }}" class="btn btn-success me-2">
                <i class="fas fa-calculator me-1"></i>
                Báo cáo quyết toán
            </a>
            <a href="{{ route('superadmin.sepay.export', request()->query()) }}" class="btn btn-primary">
                <i class="fas fa-file-export me-1"></i>
                Xuất Excel
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng giao dịch
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Thành công
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['processed']) }}</div>
                            <div class="text-xs text-success">{{ number_format($stats['processed_amount']) }} VNĐ</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Đang chờ
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['pending']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Thất bại
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['failed']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter me-2"></i>
                Bộ lọc
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('superadmin.sepay.index') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="organization_id">Tổ chức</label>
                            <select name="organization_id" id="organization_id" class="form-control">
                                <option value="">Tất cả tổ chức</option>
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}" {{ $organizationId == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }} ({{ $org->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_from">Từ ngày</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $dateFrom }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_to">Đến ngày</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $dateTo }}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">Trạng thái</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="processed" {{ $status == 'processed' ? 'selected' : '' }}>Thành công</option>
                                <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Đang chờ</option>
                                <option value="failed" {{ $status == 'failed' ? 'selected' : '' }}>Thất bại</option>
                                <option value="duplicate" {{ $status == 'duplicate' ? 'selected' : '' }}>Trùng lặp</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Tìm kiếm</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   value="{{ $search }}" 
                                   placeholder="Mã GD, nội dung, mã HĐ...">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>
                            Lọc
                        </button>
                        <a href="{{ route('superadmin.sepay.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>
                            Xóa bộ lọc
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list me-2"></i>
                Danh sách giao dịch ({{ $transactions->total() }})
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Mã GD</th>
                            <th>Ngày GD</th>
                            <th>Tổ chức</th>
                            <th>Hóa đơn</th>
                            <th>Khách hàng</th>
                            <th>Ngân hàng</th>
                            <th>Số tiền</th>
                            <th>Nội dung</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>
                                    <small class="text-muted">#{{ $transaction->sepay_transaction_id }}</small>
                                </td>
                                <td>
                                    <small>{{ $transaction->transaction_date ? $transaction->transaction_date->format('d/m/Y H:i') : 'N/A' }}</small>
                                </td>
                                <td>
                                    @if($transaction->invoice && $transaction->invoice->organization)
                                        <span class="badge badge-info">{{ $transaction->invoice->organization->code }}</span>
                                        <br>
                                        <small>{{ $transaction->invoice->organization->name }}</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($transaction->invoice)
                                        <a href="{{ route('superadmin.sepay.show', $transaction->id) }}" class="text-primary">
                                            {{ $transaction->invoice->invoice_no }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($transaction->invoice && $transaction->invoice->lease && $transaction->invoice->lease->tenant)
                                        <small>{{ $transaction->invoice->lease->tenant->name }}</small>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-secondary">{{ $transaction->gateway ?? 'N/A' }}</span>
                                    @if($transaction->account_number)
                                        <br><small>{{ $transaction->account_number }}</small>
                                    @endif
                                </td>
                                <td>
                                    <strong class="text-success">{{ number_format($transaction->amount) }}</strong>
                                </td>
                                <td>
                                    <small class="text-truncate d-inline-block" style="max-width: 150px;">
                                        {{ $transaction->content }}
                                    </small>
                                </td>
                                <td>
                                    @if($transaction->status == 'processed')
                                        <span class="badge badge-success">Thành công</span>
                                    @elseif($transaction->status == 'pending')
                                        <span class="badge badge-warning">Đang chờ</span>
                                    @elseif($transaction->status == 'failed')
                                        <span class="badge badge-danger">Thất bại</span>
                                    @elseif($transaction->status == 'duplicate')
                                        <span class="badge badge-secondary">Trùng lặp</span>
                                    @else
                                        <span class="badge badge-light">{{ $transaction->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('superadmin.sepay.show', $transaction->id) }}" 
                                       class="btn btn-sm btn-info" 
                                       title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Không có giao dịch nào
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($transactions->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $transactions->appends(request()->query())->links('vendor.pagination.custom', [
                        'contentTypeOverride' => 'giao dịch',
                        'contentIconOverride' => 'fas fa-exchange-alt'
                    ]) }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Auto-submit form when filters change
    document.querySelectorAll('#filterForm select').forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
</script>
@endpush
@endsection

