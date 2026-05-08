@extends('layouts.superadmin')

@section('title', 'Quản lý Hóa đơn Công ty')
@section('subtitle', 'Quản lý tất cả hóa đơn công ty từ các tổ chức')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Company Invoices</li>
    </ol>
</nav>
@endsection

@section('content')
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

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Bộ lọc và tìm kiếm</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('superadmin.company-invoices.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Tìm kiếm</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   value="{{ request('search') }}" placeholder="Số hóa đơn, mô tả, nhà cung cấp...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">Trạng thái</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Nháp</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                                <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Quá hạn</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="invoice_type">Loại hóa đơn</label>
                            <select name="invoice_type" id="invoice_type" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="vendor" {{ request('invoice_type') == 'vendor' ? 'selected' : '' }}>Nhà cung cấp</option>
                                <option value="lease" {{ request('invoice_type') == 'lease' ? 'selected' : '' }}>Hợp đồng thuê</option>
                                <option value="ticket" {{ request('invoice_type') == 'ticket' ? 'selected' : '' }}>Ticket</option>
                                <option value="payroll" {{ request('invoice_type') == 'payroll' ? 'selected' : '' }}>Lương</option>
                                <option value="deposit_refund" {{ request('invoice_type') == 'deposit_refund' ? 'selected' : '' }}>Hoàn tiền cọc</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
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
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="btn-group w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Tìm kiếm
                                </button>
                                <a href="{{ route('superadmin.company-invoices.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Xóa
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
                            <th>Nhà cung cấp</th>
                            <th>Loại</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày phát hành</th>
                            <th>Hạn thanh toán</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                        <tr>
                            <td><strong>{{ $invoice->invoice_no }}</strong></td>
                            <td>{{ $invoice->organization->name ?? 'N/A' }}</td>
                            <td>{{ $invoice->vendor->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-info">
                                    {{ ucfirst(str_replace('_', ' ', $invoice->invoice_type ?? 'N/A')) }}
                                </span>
                            </td>
                            <td>
                                <strong class="text-primary">
                                    {{ number_format($invoice->total_amount ?? 0, 0, ',', '.') }} {{ $invoice->currency ?? 'VND' }}
                                </strong>
                            </td>
                            <td>
                                <span class="badge bg-{{ $invoice->status == 'paid' ? 'success' : ($invoice->status == 'overdue' ? 'danger' : ($invoice->status == 'pending' ? 'warning' : 'secondary')) }}">
                                    {{ ucfirst($invoice->status ?? 'N/A') }}
                                </span>
                            </td>
                            <td>{{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') : 'N/A' }}</td>
                            <td>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('superadmin.company-invoices.show', $invoice) }}" 
                                   class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
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

