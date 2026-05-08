@extends('layouts.superadmin')

@section('title', 'Chi tiết Hóa đơn Công ty')
@section('subtitle', 'Hóa đơn #' . $companyInvoice->invoice_no)

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.company-invoices.index') }}">Company Invoices</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $companyInvoice->invoice_no }}</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Action Buttons -->
    <div class="d-flex justify-content-end mb-4">
        <a href="{{ route('superadmin.company-invoices.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Quay lại
        </a>
    </div>

    <!-- Session Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin hóa đơn</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Số hóa đơn:</strong><br>
                            {{ $companyInvoice->invoice_no }}
                        </div>
                        <div class="col-md-6">
                            <strong>Trạng thái:</strong><br>
                            <span class="badge bg-{{ $companyInvoice->status == 'paid' ? 'success' : ($companyInvoice->status == 'overdue' ? 'danger' : ($companyInvoice->status == 'pending' ? 'warning' : 'secondary')) }}">
                                {{ ucfirst($companyInvoice->status ?? 'N/A') }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Tổ chức:</strong><br>
                            {{ $companyInvoice->organization->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Nhà cung cấp:</strong><br>
                            {{ $companyInvoice->vendor->name ?? 'N/A' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Loại hóa đơn:</strong><br>
                            <span class="badge bg-info">
                                {{ ucfirst(str_replace('_', ' ', $companyInvoice->invoice_type ?? 'N/A')) }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Mô tả:</strong><br>
                            {{ $companyInvoice->description ?? 'N/A' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Ngày phát hành:</strong><br>
                            {{ $companyInvoice->issue_date ? \Carbon\Carbon::parse($companyInvoice->issue_date)->format('d/m/Y') : 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Hạn thanh toán:</strong><br>
                            {{ $companyInvoice->due_date ? \Carbon\Carbon::parse($companyInvoice->due_date)->format('d/m/Y') : 'N/A' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Tổng tiền:</strong><br>
                            <h4 class="text-primary mb-0">
                                {{ number_format($companyInvoice->total_amount ?? 0, 0, ',', '.') }} {{ $companyInvoice->currency ?? 'VND' }}
                            </h4>
                        </div>
                        <div class="col-md-6">
                            <strong>Người tạo:</strong><br>
                            {{ $companyInvoice->creator->full_name ?? $companyInvoice->creator->name ?? 'N/A' }}
                        </div>
                    </div>

                    @if($companyInvoice->paid_at)
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Ngày thanh toán:</strong><br>
                            {{ \Carbon\Carbon::parse($companyInvoice->paid_at)->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            @if($companyInvoice->items && $companyInvoice->items->count() > 0)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Chi tiết hạng mục</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Mô tả</th>
                                    <th>Số lượng</th>
                                    <th>Đơn giá</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($companyInvoice->items as $item)
                                <tr>
                                    <td>{{ $item->description ?? 'N/A' }}</td>
                                    <td>{{ $item->quantity ?? 0 }}</td>
                                    <td>{{ number_format($item->unit_price ?? 0, 0, ',', '.') }} {{ $companyInvoice->currency ?? 'VND' }}</td>
                                    <td>{{ number_format(($item->quantity ?? 0) * ($item->unit_price ?? 0), 0, ',', '.') }} {{ $companyInvoice->currency ?? 'VND' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Tổng cộng:</th>
                                    <th>{{ number_format($companyInvoice->total_amount ?? 0, 0, ',', '.') }} {{ $companyInvoice->currency ?? 'VND' }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tóm tắt</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Tổng tiền</small>
                        <h3 class="text-primary mb-0">
                            {{ number_format($companyInvoice->total_amount ?? 0, 0, ',', '.') }} 
                            <small class="fs-6">{{ $companyInvoice->currency ?? 'VND' }}</small>
                        </h3>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <small class="text-muted">Trạng thái</small>
                        <p class="mb-0">
                            <span class="badge bg-{{ $companyInvoice->status == 'paid' ? 'success' : ($companyInvoice->status == 'overdue' ? 'danger' : ($companyInvoice->status == 'pending' ? 'warning' : 'secondary')) }}">
                                {{ ucfirst($companyInvoice->status ?? 'N/A') }}
                            </span>
                        </p>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Tổ chức</small>
                        <p class="mb-0"><strong>{{ $companyInvoice->organization->name ?? 'N/A' }}</strong></p>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Nhà cung cấp</small>
                        <p class="mb-0"><strong>{{ $companyInvoice->vendor->name ?? 'N/A' }}</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

