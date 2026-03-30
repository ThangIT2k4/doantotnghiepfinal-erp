@php
    $sortBy = $sortBy ?? request('sort_by', 'created_at');
    $sortOrder = $sortOrder ?? request('sort_order', 'desc');
    
    // Generate sort URL for HTMX
    $generateSortUrl = function($field) use ($sortBy, $sortOrder) {
        $query = request()->query();
        unset($query['ajax']);
        $query['sort_by'] = $field;
        $query['sort_order'] = ($sortBy === $field && $sortOrder === 'asc') ? 'desc' : 'asc';
        return request()->url() . '?' . http_build_query($query);
    };
    
    // Get sort icon
    $getSortIcon = function($field) use ($sortBy, $sortOrder) {
        if ($sortBy !== $field) {
            return '<i class="fas fa-sort text-muted"></i>';
        }
        return $sortOrder === 'asc' 
            ? '<i class="fas fa-sort-up text-primary"></i>' 
            : '<i class="fas fa-sort-down text-primary"></i>';
    };
@endphp

<div class="col-12" id="payments-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-credit-card me-2"></i>Danh sách Thanh toán
                @if($payments && $payments->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $payments->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($payments && $payments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>
                                    <a href="{{ $generateSortUrl('invoice_no') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('invoice_no') }}"
                                       hx-target="#payments-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Hóa đơn
                                        {!! $getSortIcon('invoice_no') !!}
                                    </a>
                                </th>
                                <th>Phương thức</th>
                                <th>
                                    <a href="{{ $generateSortUrl('amount') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('amount') }}"
                                       hx-target="#payments-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Số tiền
                                        {!! $getSortIcon('amount') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('status') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('status') }}"
                                       hx-target="#payments-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Trạng thái
                                        {!! $getSortIcon('status') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('paid_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('paid_at') }}"
                                       hx-target="#payments-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày thanh toán
                                        {!! $getSortIcon('paid_at') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('created_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('created_at') }}"
                                       hx-target="#payments-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày tạo
                                        {!! $getSortIcon('created_at') !!}
                                    </a>
                                </th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#{{ $payment->id }}</span>
                                    </td>
                                    <td>
                                        @if($payment->invoice)
                                            <a href="{{ route('staff.invoices.show', $payment->invoice) }}" class="text-info text-decoration-none">
                                                <strong>#{{ $payment->invoice->invoice_no ?? $payment->invoice->id }}</strong>
                                            </a>
                                            @if($payment->invoice->lease && $payment->invoice->lease->property)
                                                <br><small class="text-muted">{{ $payment->invoice->lease->property->name ?? 'N/A' }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $payment->method->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $payment->formatted_amount }}</strong>
                                    </td>
                                    <td>
                                        @include('staff.components.status-badge', [
                                            'status' => $payment->status,
                                            'type' => 'payment'
                                        ])
                                    </td>
                                    <td>
                                        <small>
                                            {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : 'N/A' }}
                                        </small>
                                    </td>
                                    <td>
                                        <small>{{ $payment->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.payments.show', $payment) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.payments.edit', $payment) }}" 
                                               class="btn btn-outline-warning btn-icon-only" 
                                               title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($payment->status !== \App\Models\Payment::STATUS_SUCCESS)
                                                <button type="button" 
                                                        class="btn btn-outline-success btn-icon-only" 
                                                        onclick="markAsPaid({{ $payment->id }})" 
                                                        title="Đánh dấu đã thanh toán">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-icon-only" 
                                                    onclick="deletePayment({{ $payment->id }}, '{{ addslashes('Thanh toán #' . $payment->id) }}')" 
                                                    title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($payments->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {!! $payments->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'payments-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) !!}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có thanh toán nào</h5>
                    <p class="text-muted mb-3">Bắt đầu tạo thanh toán đầu tiên</p>
                    <a href="{{ route('staff.payments.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm Thanh toán mới
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

