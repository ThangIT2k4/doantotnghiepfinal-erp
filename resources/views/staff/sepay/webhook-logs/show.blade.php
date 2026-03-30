@extends('layouts.staff_dashboard')

@section('title', 'Chi Tiết Webhook Log #' . $webhookLog->sepay_transaction_id)

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

        {{-- Page Header --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi Tiết Webhook Log',
            'subtitle' => 'ID Giao Dịch: #' . $webhookLog->sepay_transaction_id,
            'icon' => 'fas fa-list-alt',
            'breadcrumbs' => [
                ['label' => 'SePay', 'url' => route('staff.sepay.index')],
                ['label' => 'Webhook Logs', 'url' => route('staff.webhook-logs.index')],
                ['label' => 'Chi tiết']
            ],
            'actions' => [
                [
                    'type' => 'link',
                    'color' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.webhook-logs.index')
                ],
                ...($webhookLog->status == 'failed' ? [[
                    'type' => 'button',
                    'color' => 'warning',
                    'label' => 'Thử Lại',
                    'icon' => 'fas fa-redo',
                    'onclick' => "document.getElementById('retry-form').submit();"
                ]] : [])
            ]
        ])

        @if($webhookLog->status == 'failed')
            <form id="retry-form" action="{{ route('staff.webhook-logs.retry', $webhookLog->id) }}" method="POST" class="d-none">
                @csrf
            </form>
        @endif

        <div class="row">
            <!-- Webhook Information -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông Tin Webhook
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%">Trạng thái:</td>
                                <td>
                                    @if($webhookLog->status == 'processed')
                                        <span class="badge bg-success">Thành công</span>
                                    @elseif($webhookLog->status == 'failed')
                                        <span class="badge bg-danger">Thất bại</span>
                                    @elseif($webhookLog->status == 'pending')
                                        <span class="badge bg-warning">Đang chờ</span>
                                    @elseif($webhookLog->status == 'duplicate')
                                        <span class="badge bg-info">Trùng lặp</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">ID Giao Dịch SePay:</td>
                                <td><strong>{{ $webhookLog->sepay_transaction_id }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Ngân hàng:</td>
                                <td>{{ $webhookLog->gateway ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Số tài khoản:</td>
                                <td>{{ $webhookLog->account_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Mã tham chiếu:</td>
                                <td>{{ $webhookLog->reference_code ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Thời gian giao dịch:</td>
                                <td>{{ $webhookLog->transaction_date ? $webhookLog->transaction_date->format('d/m/Y H:i:s') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Loại giao dịch:</td>
                                <td>
                                    @if($webhookLog->transfer_type == 'in')
                                        <span class="badge bg-success">Tiền vào</span>
                                    @else
                                        <span class="badge bg-warning">Tiền ra</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Số tiền:</td>
                                <td><strong class="text-success">{{ number_format($webhookLog->amount) }}đ</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Nội dung chuyển khoản:</td>
                                <td>{{ $webhookLog->content ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Thời gian nhận webhook:</td>
                                <td>{{ $webhookLog->created_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Thời gian xử lý:</td>
                                <td>{{ $webhookLog->processed_at ? $webhookLog->processed_at->format('d/m/Y H:i:s') : '-' }}</td>
                            </tr>
                        </table>

                        @if($webhookLog->error_message)
                            <div class="alert alert-danger mt-3 mb-0">
                                <strong>Lỗi:</strong> {{ $webhookLog->error_message }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Invoice & Payment Information -->
            <div class="col-md-6">
                <!-- Invoice Information -->
                @if($webhookLog->invoice)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-file-invoice me-2"></i>Thông Tin Hóa Đơn
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%">Mã hóa đơn:</td>
                                <td>
                                    <a href="{{ route('staff.invoices.show', $webhookLog->invoice_id) }}" class="text-primary">
                                        <strong>{{ $webhookLog->invoice->invoice_no }}</strong>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Trạng thái:</td>
                                <td>
                                    @if($webhookLog->invoice->status == 'paid')
                                        <span class="badge bg-success">Đã thanh toán</span>
                                    @elseif($webhookLog->invoice->status == 'issued')
                                        <span class="badge bg-warning">Đã phát hành</span>
                                    @elseif($webhookLog->invoice->status == 'overdue')
                                        <span class="badge bg-danger">Quá hạn</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $webhookLog->invoice->status }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tổng tiền:</td>
                                <td><strong>{{ number_format($webhookLog->invoice->total_amount) }}đ</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Đã thanh toán:</td>
                                <td><strong class="text-success">{{ number_format($webhookLog->invoice->paid_amount) }}đ</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Còn lại:</td>
                                <td><strong class="text-danger">{{ number_format($webhookLog->invoice->remaining_amount) }}đ</strong></td>
                            </tr>
                            @if($webhookLog->invoice->lease)
                            <tr>
                                <td class="text-muted">Khách thuê:</td>
                                <td>{{ $webhookLog->invoice->lease->tenant->full_name ?? '-' }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
                @endif

                <!-- Payment Information -->
                @if($webhookLog->payment)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-money-bill-wave me-2"></i>Thông Tin Thanh Toán
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 40%">ID Thanh toán:</td>
                                <td><strong>#{{ $webhookLog->payment_id }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Số tiền:</td>
                                <td><strong class="text-success">{{ number_format($webhookLog->payment->amount) }}đ</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Phương thức:</td>
                                <td>{{ $webhookLog->payment->method->name ?? 'Chuyển khoản ngân hàng' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Thời gian thanh toán:</td>
                                <td>{{ $webhookLog->payment->paid_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Trạng thái:</td>
                                <td>
                                    @if($webhookLog->payment->status == 'success')
                                        <span class="badge bg-success">Thành công</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $webhookLog->payment->status }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Ghi chú:</td>
                                <td>{{ $webhookLog->payment->note ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Raw Data -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-code me-2"></i>Dữ Liệu Webhook (JSON)
                </h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($webhookLog->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
        </div>
    </div>
</main>
@endsection
