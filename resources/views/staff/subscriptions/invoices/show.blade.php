@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Hóa đơn')

@section('content')
<main class="main-content">
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

        <!-- Page Header -->
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết Hóa đơn',
            'subtitle' => 'Hóa đơn #' . $invoice->invoice_number,
            'icon' => 'fas fa-file-invoice',
            'breadcrumbs' => [
                ['label' => 'Hóa đơn', 'url' => route('staff.subscriptions.invoices.index')],
                ['label' => $invoice->invoice_number, 'active' => true]
            ]
        ])

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin hóa đơn
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Số hóa đơn</small>
                                <strong>{{ $invoice->invoice_number }}</strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Trạng thái</small>
                                <span class="badge bg-{{ $invoice->getStatusColor() }}">
                                    {{ $invoice->getStatusLabel() }}
                                </span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Gói dịch vụ</small>
                                <strong>{{ $invoice->subscription->plan->name ?? 'N/A' }}</strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Chu kỳ thanh toán</small>
                                <strong>{{ $invoice->subscription->payment_cycle === 'yearly' ? 'Hàng năm' : 'Hàng tháng' }}</strong>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Số tiền</small>
                                <h4 class="text-primary mb-0">
                                    {{ number_format($invoice->amount, 0, ',', '.') }} {{ $invoice->currency }}
                                </h4>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Phương thức thanh toán</small>
                                <strong>
                                    {{ $invoice->payment_method === 'sepay' ? 'Chuyển khoản SePay' : ($invoice->payment_method === 'manual' ? 'Thủ công' : 'N/A') }}
                                </strong>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Ngày tạo</small>
                                <strong>{{ $invoice->created_at->format('d/m/Y H:i') }}</strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Hạn thanh toán</small>
                                <strong>
                                    {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}
                                    @if($invoice->isOverdue())
                                        <span class="badge bg-danger ms-2">Quá hạn</span>
                                    @endif
                                </strong>
                            </div>
                        </div>

                        @if($invoice->paid_at)
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Ngày thanh toán</small>
                                <strong>{{ $invoice->paid_at->format('d/m/Y H:i') }}</strong>
                            </div>
                            @if($invoice->gateway_transaction_id)
                            <div class="col-md-6">
                                <small class="text-muted d-block">Mã giao dịch</small>
                                <strong>{{ $invoice->gateway_transaction_id }}</strong>
                            </div>
                            @endif
                        </div>
                        @endif

                        @if($invoice->status === 'pending' && $invoice->payment_method === 'sepay')
                        <hr>
                        <div class="d-flex gap-2">
                            <a href="{{ route('staff.subscriptions.payment', $invoice) }}" class="btn btn-primary">
                                <i class="fas fa-credit-card me-1"></i>Thanh toán
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-receipt me-1"></i>Tóm tắt
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block mb-1">Tổng tiền</small>
                            <h3 class="text-primary mb-0">
                                {{ number_format($invoice->amount, 0, ',', '.') }} 
                                <small class="fs-6">{{ $invoice->currency }}</small>
                            </h3>
                        </div>
                        <hr>
                        <div class="mb-2">
                            <small class="text-muted d-block">Trạng thái</small>
                            <span class="badge bg-{{ $invoice->getStatusColor() }}">
                                {{ $invoice->getStatusLabel() }}
                            </span>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted d-block">Gói dịch vụ</small>
                            <strong class="small">{{ $invoice->subscription->plan->name ?? 'N/A' }}</strong>
                        </div>
                        @if($invoice->due_date)
                        <div class="mb-2">
                            <small class="text-muted d-block">Hạn thanh toán</small>
                            <strong class="small">{{ $invoice->due_date->format('d/m/Y') }}</strong>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
