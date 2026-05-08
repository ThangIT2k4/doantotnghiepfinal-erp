@extends('layouts.superadmin')

@section('title', 'Báo cáo quyết toán SePay')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.sepay.index') }}">SePay Management</a></li>
        <li class="breadcrumb-item active" aria-current="page">Báo cáo quyết toán</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-calculator me-2"></i>
                Báo cáo quyết toán SePay
            </h1>
            <p class="text-muted mb-0">Tổng hợp giao dịch theo tổ chức để quyết toán</p>
        </div>
        <div>
            <a href="{{ route('superadmin.sepay.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Quay lại
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-calendar me-2"></i>
                Chọn khoảng thời gian
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('superadmin.sepay.settlement') }}">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_from">Từ ngày</label>
                            <input type="date" 
                                   name="date_from" 
                                   id="date_from" 
                                   class="form-control" 
                                   value="{{ $dateFrom }}"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date_to">Đến ngày</label>
                            <input type="date" 
                                   name="date_to" 
                                   id="date_to" 
                                   class="form-control" 
                                   value="{{ $dateTo }}"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>
                                Xem báo cáo
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Stats -->
    @php
        $totalTransactions = $settlements->sum('total_transactions');
        $totalAmount = $settlements->sum('total_amount');
        $totalSuccess = $settlements->sum('success_count');
        $totalSuccessAmount = $settlements->sum('success_amount');
    @endphp

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng số tổ chức
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $settlements->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tổng giao dịch
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalTransactions) }}</div>
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
                                Giao dịch thành công
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalSuccess) }}</div>
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
                                Tổng tiền thành công
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalSuccessAmount) }}</div>
                            <div class="text-xs text-muted">VNĐ</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settlement Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table me-2"></i>
                Báo cáo theo tổ chức ({{ $settlements->count() }})
            </h6>
            <div>
                <small class="text-muted">Từ {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} đến {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</small>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã</th>
                            <th>Tên tổ chức</th>
                            <th class="text-center">Tổng GD</th>
                            <th class="text-center">GD thành công</th>
                            <th class="text-right">Tổng tiền</th>
                            <th class="text-right">Tiền thành công</th>
                            <th class="text-center">Tỷ lệ</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $index => $settlement)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <span class="badge badge-info">{{ $settlement->organization_code }}</span>
                                </td>
                                <td>
                                    <strong>{{ $settlement->organization_name }}</strong>
                                </td>
                                <td class="text-center">
                                    {{ number_format($settlement->total_transactions) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-success">{{ number_format($settlement->success_count) }}</span>
                                </td>
                                <td class="text-right">
                                    <span class="text-muted">{{ number_format($settlement->total_amount) }}</span>
                                </td>
                                <td class="text-right">
                                    <strong class="text-success">{{ number_format($settlement->success_amount) }}</strong>
                                </td>
                                <td class="text-center">
                                    @php
                                        $rate = $settlement->total_transactions > 0 
                                            ? ($settlement->success_count / $settlement->total_transactions * 100) 
                                            : 0;
                                    @endphp
                                    <span class="badge {{ $rate >= 90 ? 'badge-success' : ($rate >= 70 ? 'badge-warning' : 'badge-danger') }}">
                                        {{ number_format($rate, 1) }}%
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('superadmin.sepay.index', [
                                            'organization_id' => $settlement->organization_id,
                                            'date_from' => $dateFrom,
                                            'date_to' => $dateTo,
                                            'status' => 'processed'
                                        ]) }}" 
                                           class="btn btn-sm btn-info" 
                                           title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('superadmin.sepay.export', [
                                            'organization_id' => $settlement->organization_id,
                                            'date_from' => $dateFrom,
                                            'date_to' => $dateTo
                                        ]) }}" 
                                           class="btn btn-sm btn-success" 
                                           title="Xuất Excel">
                                            <i class="fas fa-file-export"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Không có dữ liệu trong khoảng thời gian này
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($settlements->count() > 0)
                        <tfoot class="bg-light">
                            <tr>
                                <th colspan="3" class="text-right">TỔNG CỘNG:</th>
                                <th class="text-center">{{ number_format($totalTransactions) }}</th>
                                <th class="text-center">{{ number_format($totalSuccess) }}</th>
                                <th class="text-right">{{ number_format($totalAmount) }}</th>
                                <th class="text-right"><strong class="text-success">{{ number_format($totalSuccessAmount) }}</strong></th>
                                <th class="text-center">
                                    @php
                                        $totalRate = $totalTransactions > 0 ? ($totalSuccess / $totalTransactions * 100) : 0;
                                    @endphp
                                    <span class="badge {{ $totalRate >= 90 ? 'badge-success' : ($totalRate >= 70 ? 'badge-warning' : 'badge-danger') }}">
                                        {{ number_format($totalRate, 1) }}%
                                    </span>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <!-- Export All Button -->
    @if($settlements->count() > 0)
        <div class="text-center mb-4">
            <a href="{{ route('superadmin.sepay.export', [
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]) }}" 
               class="btn btn-success btn-lg">
                <i class="fas fa-file-export me-2"></i>
                Xuất toàn bộ báo cáo Excel
            </a>
        </div>
    @endif
</div>
@endsection

