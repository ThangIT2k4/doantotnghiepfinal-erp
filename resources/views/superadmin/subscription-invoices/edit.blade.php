@extends('layouts.superadmin')

@section('title', 'Chỉnh sửa Hóa đơn Đăng ký')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.subscription-invoices.index') }}">Subscription Invoices</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.subscription-invoices.show', $subscriptionInvoice) }}">{{ $subscriptionInvoice->invoice_number }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-edit me-2"></i>
                Chỉnh sửa Hóa đơn: {{ $subscriptionInvoice->invoice_number }}
            </h1>
        </div>
        <div>
            <a href="{{ route('superadmin.subscription-invoices.show', $subscriptionInvoice) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
    </div>

    <!-- Session Messages -->
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
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
                    <form method="POST" action="{{ route('superadmin.subscription-invoices.update', $subscriptionInvoice) }}">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control" required>
                                    <option value="pending" {{ old('status', $subscriptionInvoice->status) == 'pending' ? 'selected' : '' }}>Chờ thanh toán</option>
                                    <option value="paid" {{ old('status', $subscriptionInvoice->status) == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                                    <option value="failed" {{ old('status', $subscriptionInvoice->status) == 'failed' ? 'selected' : '' }}>Thất bại</option>
                                    <option value="refunded" {{ old('status', $subscriptionInvoice->status) == 'refunded' ? 'selected' : '' }}>Đã hoàn tiền</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Hạn thanh toán</label>
                                <input type="date" name="due_date" id="due_date" class="form-control" 
                                       value="{{ old('due_date', $subscriptionInvoice->due_date ? $subscriptionInvoice->due_date->format('Y-m-d') : '') }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">Phương thức thanh toán</label>
                                <input type="text" name="payment_method" id="payment_method" class="form-control" 
                                       value="{{ old('payment_method', $subscriptionInvoice->payment_method) }}" 
                                       placeholder="sepay, manual, etc.">
                            </div>
                            <div class="col-md-6">
                                <label for="gateway_transaction_id" class="form-label">Mã giao dịch</label>
                                <input type="text" name="gateway_transaction_id" id="gateway_transaction_id" class="form-control" 
                                       value="{{ old('gateway_transaction_id', $subscriptionInvoice->gateway_transaction_id) }}" 
                                       placeholder="Mã giao dịch từ gateway">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="paid_at" class="form-label">Ngày thanh toán</label>
                                <input type="datetime-local" name="paid_at" id="paid_at" class="form-control" 
                                       value="{{ old('paid_at', $subscriptionInvoice->paid_at ? $subscriptionInvoice->paid_at->format('Y-m-d\TH:i') : '') }}">
                                <small class="text-muted">Chỉ điền khi trạng thái là "Đã thanh toán"</small>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Khi bạn thay đổi trạng thái sang "Đã thanh toán", subscription sẽ được kích hoạt tự động.
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('superadmin.subscription-invoices.show', $subscriptionInvoice) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Hủy
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin hiện tại</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Số hóa đơn</small>
                        <p class="mb-0"><strong>{{ $subscriptionInvoice->invoice_number }}</strong></p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Tổ chức</small>
                        <p class="mb-0">{{ $subscriptionInvoice->subscription->organization->name ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Gói dịch vụ</small>
                        <p class="mb-0">{{ $subscriptionInvoice->subscription->plan->name ?? 'N/A' }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Số tiền</small>
                        <h5 class="text-primary mb-0">
                            {{ number_format($subscriptionInvoice->amount, 0, ',', '.') }} {{ $subscriptionInvoice->currency }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

