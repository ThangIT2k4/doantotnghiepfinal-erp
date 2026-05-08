@extends('layouts.superadmin')

@section('title', 'Chi tiết Hóa đơn Đăng ký')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.subscription-invoices.index') }}">Subscription Invoices</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $subscriptionInvoice->invoice_number }}</li>
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
                Chi tiết Hóa đơn: {{ $subscriptionInvoice->invoice_number }}
            </h1>
        </div>
        <div>
            <a href="{{ route('superadmin.subscription-invoices.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
            <a href="{{ route('superadmin.subscription-invoices.edit', $subscriptionInvoice) }}" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i>Chỉnh sửa
            </a>
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
                            {{ $subscriptionInvoice->invoice_number }}
                        </div>
                        <div class="col-md-6">
                            <strong>Trạng thái:</strong><br>
                            <span class="badge bg-{{ $subscriptionInvoice->getStatusColor() }}">
                                {{ $subscriptionInvoice->getStatusLabel() }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Tổ chức:</strong><br>
                            {{ $subscriptionInvoice->subscription->organization->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Gói dịch vụ:</strong><br>
                            {{ $subscriptionInvoice->subscription->plan->name ?? 'N/A' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Số tiền:</strong><br>
                            <span class="text-primary fs-4">
                                {{ number_format($subscriptionInvoice->amount, 0, ',', '.') }} {{ $subscriptionInvoice->currency }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Phương thức thanh toán:</strong><br>
                            {{ $subscriptionInvoice->payment_method ?? 'N/A' }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Ngày tạo:</strong><br>
                            {{ $subscriptionInvoice->created_at->format('d/m/Y H:i') }}
                        </div>
                        <div class="col-md-6">
                            <strong>Hạn thanh toán:</strong><br>
                            {{ $subscriptionInvoice->due_date ? $subscriptionInvoice->due_date->format('d/m/Y') : 'N/A' }}
                        </div>
                    </div>

                    @if($subscriptionInvoice->paid_at)
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Ngày thanh toán:</strong><br>
                            {{ $subscriptionInvoice->paid_at->format('d/m/Y H:i') }}
                        </div>
                        @if($subscriptionInvoice->gateway_transaction_id)
                        <div class="col-md-6">
                            <strong>Mã giao dịch:</strong><br>
                            {{ $subscriptionInvoice->gateway_transaction_id }}
                        </div>
                        @endif
                    </div>
                    @endif

                    @php
                        // Filter chỉ lấy ảnh (document_type = 'image' hoặc extension là ảnh)
                        $images = $subscriptionInvoice->documents->filter(function($doc) {
                            $imageTypes = ['image'];
                            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
                            $extension = strtolower(pathinfo($doc->file_name ?? '', PATHINFO_EXTENSION));
                            return in_array($doc->document_type, $imageTypes) || 
                                   in_array($extension, $imageExtensions);
                        });
                    @endphp
                    @if($images && $images->count() > 0)
                    <div class="row mb-3">
                        <div class="col-12">
                            <hr>
                            <h6 class="mb-3">
                                <i class="fas fa-images me-2"></i>
                                <strong>Ảnh chứng từ thanh toán ({{ $images->count() }})</strong>
                            </h6>
                            <div class="row">
                                @foreach($images as $image)
                                    @php
                                        // Get raw file_url (relative path) from database, not through accessor
                                        $rawFileUrl = $image->getRawOriginal('file_url');
                                        // Build correct URL
                                        $imageUrl = str_starts_with($rawFileUrl, 'http://') || str_starts_with($rawFileUrl, 'https://') 
                                            ? $rawFileUrl 
                                            : asset('storage/' . ltrim($rawFileUrl, '/'));
                                    @endphp
                                    <div class="col-md-4 col-lg-3 mb-3">
                                        <div class="card border">
                                            <a href="{{ $imageUrl }}" target="_blank" class="d-block text-center p-2" 
                                               style="text-decoration: none; background-color: #f8f9fa;">
                                                <img src="{{ $imageUrl }}" alt="Payment proof" 
                                                     class="img-fluid"
                                                     style="max-height: 200px; width: auto; border-radius: 4px; cursor: pointer; object-fit: contain;">
                                            </a>
                                            <div class="card-body p-2">
                                                <small class="text-muted d-block text-center">
                                                    <i class="fas fa-file-image me-1"></i>
                                                    {{ $image->file_name ?? 'Ảnh chứng từ' }}
                                                </small>
                                                <small class="text-muted d-block text-center mt-1">
                                                    <i class="fas fa-external-link-alt me-1"></i>
                                                    Click để xem ảnh gốc
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thao tác</h6>
                </div>
                <div class="card-body">
                    @if($subscriptionInvoice->status !== 'paid')
                    <form method="POST" action="{{ route('superadmin.subscription-invoices.mark-paid', $subscriptionInvoice) }}" 
                          onsubmit="return confirm('Xác nhận đánh dấu hóa đơn này là đã thanh toán? Subscription sẽ được kích hoạt tự động.');">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-check me-1"></i>Đánh dấu đã thanh toán
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('superadmin.subscription-invoices.edit', $subscriptionInvoice) }}" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-edit me-1"></i>Chỉnh sửa
                    </a>

                    @if(in_array($subscriptionInvoice->status, ['pending', 'failed']))
                    <form method="POST" action="{{ route('superadmin.subscription-invoices.destroy', $subscriptionInvoice) }}" 
                          onsubmit="return confirm('Xác nhận xóa hóa đơn này?');" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-trash me-1"></i>Xóa
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

