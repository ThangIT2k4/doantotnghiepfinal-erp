@extends('layouts.staff_dashboard')

@section('title', 'Nhật Ký Webhook - SePay')

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

        {{-- 1. Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Nhật Ký Webhook - SePay',
            'subtitle' => 'Quản lý và theo dõi các webhook thanh toán từ SePay',
            'icon' => 'fas fa-list-alt'
        ])

        {{-- 2. Statistics Cards với HTMX --}}
        <div id="statistics-cards-container">
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted ?? [],
                'currentFilter' => request('status', ''),
                'filterKey' => 'status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'tableContainerId' => 'webhook-logs-table-container',
                'action' => route('staff.webhook-logs.index'),
                'columns' => 6
            ])
        </div>

        {{-- 3. Filters với HTMX --}}
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.webhook-logs.index'),
            'tableContainerId' => 'webhook-logs-table-container',
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Mã giao dịch, hóa đơn, nội dung...',
                    'value' => request('search'),
                    'live_search' => true,
                    'col' => 'col-md-3'
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'options' => [
                        'pending' => 'Đang chờ',
                        'processed' => 'Thành công',
                        'failed' => 'Thất bại',
                        'duplicate' => 'Trùng lặp'
                    ],
                    'value' => request('status'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'empty_option' => 'Tất cả trạng thái'
                ],
                [
                    'name' => 'date_from',
                    'label' => 'Từ ngày',
                    'type' => 'date',
                    'value' => request('date_from'),
                    'live_search' => true,
                    'col' => 'col-md-2'
                ],
                [
                    'name' => 'date_to',
                    'label' => 'Đến ngày',
                    'type' => 'date',
                    'value' => request('date_to'),
                    'live_search' => true,
                    'col' => 'col-md-2'
                ]
            ],
            'resetUrl' => route('staff.webhook-logs.index')
        ])

        {{-- 4. Table Container --}}
        <div id="webhook-logs-table-container">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID Giao Dịch</th>
                                    <th>Ngày GD</th>
                                    <th>Ngân Hàng</th>
                                    <th>Số Tiền</th>
                                    <th>Nội Dung</th>
                                    <th>Hóa Đơn</th>
                                    <th>Trạng Thái</th>
                                    <th>Hành Động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($webhookLogs as $log)
                                <tr>
                                    <td>
                                        <strong>#{{ $log->sepay_transaction_id }}</strong><br>
                                        <small class="text-muted">{{ $log->reference_code }}</small>
                                    </td>
                                    <td>
                                        {{ $log->transaction_date ? $log->transaction_date->format('d/m/Y H:i') : '-' }}<br>
                                        <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>{{ $log->gateway ?? '-' }}</td>
                                    <td>
                                        <strong class="text-success">{{ number_format($log->amount) }}đ</strong><br>
                                        <small class="text-muted">{{ $log->transfer_type == 'in' ? 'Tiền vào' : 'Tiền ra' }}</small>
                                    </td>
                                    <td>
                                        <small>{{ Str::limit($log->content, 50) }}</small>
                                    </td>
                                    <td>
                                        @if($log->invoice)
                                            <a href="{{ route('staff.invoices.show', $log->invoice_id) }}" 
                                               class="text-primary" title="Xem hóa đơn">
                                                <i class="fas fa-file-invoice me-1"></i>{{ $log->invoice->invoice_no }}
                                            </a>
                                        @elseif(isset($log->subscriptionInvoice) && $log->subscriptionInvoice)
                                            <a href="{{ route('staff.subscriptions.invoices.show', $log->subscriptionInvoice->id) }}" 
                                               class="text-primary" title="Xem hóa đơn đăng ký">
                                                <i class="fas fa-file-invoice me-1"></i>{{ $log->subscriptionInvoice->invoice_number }}
                                            </a>
                                        @else
                                            @php
                                                // Tìm subscription invoice từ content nếu chưa có
                                                $subscriptionInvoice = null;
                                                if ($log->content) {
                                                    $cleanContent = str_replace('-', '', $log->content);
                                                    // Tìm subscription invoice (SUB{YYYYMMDD}{random} hoặc SUB{YYYYMMDD}{subscription_id})
                                                    if (preg_match('/SUB\d{8}\w+/i', $cleanContent, $matches)) {
                                                        $invoiceNo = strtoupper($matches[0]);
                                                        $subscriptionInvoice = \App\Models\SubscriptionInvoice::where('invoice_number', $invoiceNo)
                                                            ->whereHas('subscription', function($q) {
                                                                $q->where('organization_id', \Illuminate\Support\Facades\Auth::user()->organization_id ?? null);
                                                            })
                                                            ->first();
                                                    }
                                                }
                                            @endphp
                                            @if($subscriptionInvoice)
                                                <a href="{{ route('staff.subscriptions.invoices.show', $subscriptionInvoice->id) }}" 
                                                   class="text-primary" title="Xem hóa đơn đăng ký">
                                                    <i class="fas fa-file-invoice me-1"></i>{{ $subscriptionInvoice->invoice_number }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->status == 'processed')
                                            <span class="badge bg-success">Thành công</span>
                                        @elseif($log->status == 'failed')
                                            <span class="badge bg-danger">Thất bại</span>
                                        @elseif($log->status == 'pending')
                                            <span class="badge bg-warning">Đang chờ</span>
                                        @elseif($log->status == 'duplicate')
                                            <span class="badge bg-info">Trùng lặp</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $log->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('staff.webhook-logs.show', $log->id) }}" 
                                               class="btn btn-sm btn-outline-primary btn-icon-only"
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($log->status == 'failed')
                                                <form action="{{ route('staff.webhook-logs.retry', $log->id) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-warning btn-icon-only"
                                                            title="Thử lại"
                                                            onclick="return confirm('Bạn có muốn thử lại webhook này?')">
                                                        <i class="fas fa-redo"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">Chưa có webhook nào</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-3">
                        {{ $webhookLogs->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'webhook-logs-table-container',
                            'contentTypeOverride' => 'webhook',
                            'contentIconOverride' => 'fas fa-list-alt',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
