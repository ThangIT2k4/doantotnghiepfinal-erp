@extends('layouts.superadmin')

@section('title', 'Subscription Invoices')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-receipt me-2"></i>
                Invoices - {{ $organization->name }}
            </h1>
            <p class="text-muted mb-0">Gói: <strong>{{ $subscription->plan->name }}</strong></p>
        </div>
        <div>
            <a href="{{ route('superadmin.organizations.subscription.show', $organization->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách Invoices</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Số hóa đơn</th>
                            <th>Số tiền</th>
                            <th>Ngày đáo hạn</th>
                            <th>Ngày thanh toán</th>
                            <th>Trạng thái</th>
                            <th>Phương thức</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                        <tr>
                            <td>
                                <strong>{{ $invoice->invoice_number }}</strong>
                            </td>
                            <td>
                                <strong>{{ $invoice->getFormattedAmount() }}</strong>
                            </td>
                            <td>
                                {{ $invoice->due_date->format('d/m/Y') }}
                                @if($invoice->isOverdue())
                                    <br><small class="text-danger"><i class="fas fa-exclamation-circle"></i> Quá hạn</small>
                                @endif
                            </td>
                            <td>
                                {{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $invoice->getStatusColor() }}">
                                    {{ $invoice->getStatusLabel() }}
                                </span>
                            </td>
                            <td>{{ $invoice->payment_method ?? '-' }}</td>
                            <td class="text-center">
                                @if($invoice->isPending())
                                <form action="#" method="POST" class="d-inline" onsubmit="return confirm('Xác nhận đã thanh toán?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" title="Đánh dấu đã thanh toán">
                                        <i class="fas fa-check"></i> Đã thanh toán
                                    </button>
                                </form>
                                @else
                                <button class="btn btn-sm btn-info" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có invoice nào</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($invoices->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $invoices->appends(request()->query())->links('vendor.pagination.custom') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

