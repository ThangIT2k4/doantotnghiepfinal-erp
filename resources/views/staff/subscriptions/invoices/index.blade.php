@extends('layouts.staff_dashboard')

@section('title', 'Hóa đơn Đăng ký')

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
        @include('staff.components.index-page-header', [
            'title' => 'Hóa đơn Đăng ký',
            'subtitle' => 'Danh sách hóa đơn đăng ký gói dịch vụ',
            'icon' => 'fas fa-file-invoice',
            'actions' => [
                [
                    'variant' => 'primary',
                    'label' => 'Đăng ký Gói Mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.subscriptions.index')
                ]
            ]
        ])

        <!-- Invoices Table -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>Danh sách hóa đơn
                    @if($invoices->count() > 0)
                        <span class="badge bg-primary ms-2">{{ $invoices->total() }}</span>
                    @endif
                </h5>
            </div>
            <div class="card-body p-0">
                @if($invoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Số hóa đơn</th>
                                <th>Gói dịch vụ</th>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Hạn thanh toán</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                            <tr>
                                <td>
                                    <strong>{{ $invoice->invoice_number }}</strong>
                                </td>
                                <td>
                                    {{ $invoice->subscription->plan->name ?? 'N/A' }}
                                </td>
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
                                <td>
                                    <small>{{ $invoice->created_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    @if($invoice->due_date)
                                        <small>{{ $invoice->due_date->format('d/m/Y') }}</small>
                                        @if($invoice->isOverdue())
                                            <br><span class="badge bg-danger small">Quá hạn</span>
                                        @endif
                                    @else
                                        <small class="text-muted">N/A</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group table-actions" role="group">
                                        <a href="{{ route('staff.subscriptions.invoices.show', $invoice) }}" 
                                           class="btn btn-outline-primary btn-icon-only" 
                                           title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($invoice->status === 'pending' && $invoice->payment_method === 'sepay')
                                        <a href="{{ route('staff.subscriptions.payment', $invoice) }}" 
                                           class="btn btn-outline-success btn-icon-only" 
                                           title="Thanh toán">
                                            <i class="fas fa-credit-card"></i>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($invoices->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        {{ $invoices->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'subscription-invoices-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) }}
                    </div>
                </div>
                @endif
                @else
                <div class="card-body text-center py-5">
                    <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có hóa đơn nào</h5>
                    <p class="text-muted mb-0">Bạn chưa có hóa đơn đăng ký nào.</p>
                    <a href="{{ route('staff.subscriptions.index') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-1"></i>Đăng ký gói dịch vụ
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</main>
@endsection
