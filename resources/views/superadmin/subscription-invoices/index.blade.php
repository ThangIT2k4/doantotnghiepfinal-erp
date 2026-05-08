@extends('layouts.superadmin')

@section('title', 'Quản lý Hóa đơn Đăng ký')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Subscription Invoices</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-invoice me-2"></i>
                Quản lý Hóa đơn Đăng ký
            </h1>
            <p class="text-muted mb-0">Quản lý tất cả hóa đơn đăng ký gói dịch vụ</p>
        </div>
    </div>

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

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Bộ lọc và tìm kiếm</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('superadmin.subscription-invoices.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Tìm kiếm</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   value="{{ request('search') }}" placeholder="Số hóa đơn, tổ chức...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">Trạng thái</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                                <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Đã hoàn tiền</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="organization_id">Tổ chức</label>
                            <select name="organization_id" id="organization_id" class="form-control">
                                <option value="">Tất cả</option>
                                @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ request('organization_id') == $org->id ? 'selected' : '' }}>
                                    {{ $org->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="plan_id">Gói dịch vụ</label>
                            <select name="plan_id" id="plan_id" class="form-control">
                                <option value="">Tất cả</option>
                                @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="btn-group w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="{{ route('superadmin.subscription-invoices.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card shadow">
        <div class="card-body">
            @if($invoices->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Số hóa đơn</th>
                            <th>Tổ chức</th>
                            <th>Gói dịch vụ</th>
                            <th>Số tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Hạn thanh toán</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                        <tr>
                            <td><strong>{{ $invoice->invoice_number }}</strong></td>
                            <td>{{ $invoice->subscription->organization->name ?? 'N/A' }}</td>
                            <td>{{ $invoice->subscription->plan->name ?? 'N/A' }}</td>
                            <td>
                                <strong class="text-primary">
                                    {{ number_format($invoice->amount, 0, ',', '.') }} {{ $invoice->currency }}
                                </strong>
                            </td>
                            <td>
                                <span class="badge bg-{{ $invoice->getStatusColor() }}">
                                    {{ $invoice->getStatusLabel() }}
                                </span>
                            </td>
                            <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('superadmin.subscription-invoices.show', $invoice) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($invoice->status !== 'paid')
                                <form method="POST" action="{{ route('superadmin.subscription-invoices.mark-paid', $invoice) }}" 
                                      class="d-inline" onsubmit="return confirm('Xác nhận đánh dấu hóa đơn này là đã thanh toán?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $invoices->appends(request()->query())->links('vendor.pagination.custom') }}
            </div>
            @else
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                Không tìm thấy hóa đơn nào.
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

