@extends('layouts.staff_dashboard')

@section('title', 'Trạng thái thanh toán')

@section('content')
<div class="container-fluid" style="padding: 20px;">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('staff.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('staff.company-invoices.index') }}">Hóa đơn công ty</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('staff.company-invoices.show', $companyInvoice->id) }}">Chi tiết hóa đơn</a></li>
                        <li class="breadcrumb-item active">Trạng thái thanh toán</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    <i class="fas fa-credit-card me-2"></i>Trạng thái thanh toán
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-invoice me-2"></i>
                        Hóa đơn: {{ $companyInvoice->invoice_no }}
                    </h5>
                </div>
                <div class="card-body">
                    @if($payment)
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Thông tin thanh toán</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>ID thanh toán:</strong></td>
                                        <td><code>{{ $payment->id }}</code></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Số tiền:</strong></td>
                                        <td>
                                            <span class="h5 text-success">
                                                {{ number_format($payment->amount) }} VND
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phương thức:</strong></td>
                                        <td>
                                            @php
                                                $paymentMethodKey = $payment->paymentMethod ? $payment->paymentMethod->key_code : null;
                                            @endphp
                                            <span class="badge bg-{{ $paymentMethodKey == 'cash' ? 'success' : ($paymentMethodKey == 'sepay' ? 'info' : 'secondary') }}">
                                                @if($payment->paymentMethod)
                                                    {{ $payment->paymentMethod->name }}
                                                @else
                                                    N/A
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Trạng thái:</strong></td>
                                        <td>
                                            <span class="badge bg-{{ $payment->status == 'success' ? 'success' : ($payment->status == 'pending' ? 'warning' : ($payment->status == 'failed' ? 'danger' : 'secondary')) }}">
                                                @switch($payment->status)
                                                    @case('success')
                                                        Thành công
                                                        @break
                                                    @case('pending')
                                                        Đang chờ
                                                        @break
                                                    @case('failed')
                                                        Thất bại
                                                        @break
                                                    @case('reversed')
                                                        Đã hoàn trả
                                                        @break
                                                    @default
                                                        {{ ucfirst($payment->status) }}
                                                @endswitch
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ngày tạo:</strong></td>
                                        <td>{{ $payment->created_at->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Cập nhật lần cuối:</strong></td>
                                        <td>{{ $payment->updated_at->format('d/m/Y H:i:s') }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-3">Thông tin hóa đơn</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Số hóa đơn:</strong></td>
                                        <td>{{ $companyInvoice->invoice_no }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Người nhận:</strong></td>
                                        <td>
                                            @if($companyInvoice->vendor_id)
                                                <span class="badge bg-info me-1">Nhà cung cấp</span>
                                                {{ $companyInvoice->vendor->name ?? 'N/A' }}
                                            @elseif($companyInvoice->user_id)
                                                <span class="badge bg-success me-1">Người dùng</span>
                                                {{ $companyInvoice->user->full_name ?? 'N/A' }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tổng tiền:</strong></td>
                                        <td>{{ number_format($companyInvoice->total_amount) }} VND</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Trạng thái hóa đơn:</strong></td>
                                        <td>
                                            @include('staff.components.status-badge', [
                                                'status' => $companyInvoice->status,
                                                'type' => 'company-invoice'
                                            ])
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        @if($payment->note)
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-muted mb-2">Ghi chú</h6>
                                <div class="alert alert-light">
                                    {{ $payment->note }}
                                </div>
                            </div>
                        </div>
                        @endif

                        @php
                            $paymentMethodKey = $payment->paymentMethod ? $payment->paymentMethod->key_code : null;
                        @endphp
                        @if($paymentMethodKey == 'sepay' && $payment->status == 'pending')
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle me-2"></i>Hướng dẫn thanh toán Sepay</h6>
                                    <p class="mb-0">
                                        Vui lòng chuyển khoản theo thông tin QR code đã hiển thị. 
                                        Hệ thống sẽ tự động cập nhật trạng thái khi nhận được xác nhận từ ngân hàng.
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3em;"></i>
                            <h4 class="mt-3">Không tìm thấy thông tin thanh toán</h4>
                            <p class="text-muted">Thanh toán này không tồn tại hoặc bạn không có quyền truy cập.</p>
                            <a href="{{ route('staff.company-invoices.show', $companyInvoice->id) }}" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại hóa đơn
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('staff.company-invoices.show', $companyInvoice->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại hóa đơn
                        </a>
                        <a href="{{ route('staff.company-invoices.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Danh sách hóa đơn
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-refresh for pending payments
@if($payment && $payment->status === 'pending')
setInterval(() => {
    location.reload();
}, 10000); // Refresh every 10 seconds
@endif
</script>
@endpush
