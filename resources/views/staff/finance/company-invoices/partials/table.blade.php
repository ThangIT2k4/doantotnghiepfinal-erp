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

<div class="col-12" id="company-invoices-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-file-invoice me-2"></i>Danh sách Hóa đơn Công ty
                @if($invoices->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $invoices->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($invoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <a href="{{ $generateSortUrl('invoice_no') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('invoice_no') }}"
                                       hx-target="#company-invoices-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Số hóa đơn
                                        {!! $getSortIcon('invoice_no') !!}
                                    </a>
                                </th>
                                <th>Người nhận</th>
                                <th>
                                    <a href="{{ $generateSortUrl('invoice_type') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('invoice_type') }}"
                                       hx-target="#company-invoices-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Loại
                                        {!! $getSortIcon('invoice_type') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('issue_date') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('issue_date') }}"
                                       hx-target="#company-invoices-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày phát hành
                                        {!! $getSortIcon('issue_date') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('due_date') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('due_date') }}"
                                       hx-target="#company-invoices-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày đến hạn
                                        {!! $getSortIcon('due_date') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('total_amount') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('total_amount') }}"
                                       hx-target="#company-invoices-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Tổng tiền
                                        {!! $getSortIcon('total_amount') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('status') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('status') }}"
                                       hx-target="#company-invoices-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Trạng thái
                                        {!! $getSortIcon('status') !!}
                                    </a>
                                </th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td>
                                        <div>
                                            <strong>{{ $invoice->invoice_no }}</strong>
                                            @if($invoice->description)
                                                <br><small class="text-muted">{{ Str::limit($invoice->description, 50) }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($invoice->vendor_id)
                                            <span class="badge bg-info me-1">Nhà cung cấp</span>
                                            {{ $invoice->vendor->name ?? 'N/A' }}
                                        @elseif($invoice->user_id)
                                            <span class="badge bg-success me-1">Người dùng</span>
                                            {{ $invoice->user->full_name ?? 'N/A' }}
                                        @else
                                            <span class="text-muted">Không có</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $types[$invoice->invoice_type] ?? $invoice->invoice_type }}</span>
                                    </td>
                                    <td>
                                        <small>{{ $invoice->issue_date->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $invoice->due_date->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($invoice->total_amount, 0, ',', '.') }} {{ $invoice->currency }}</strong>
                                    </td>
                                    <td>
                                        @include('staff.components.status-badge', [
                                            'status' => $invoice->status,
                                            'type' => 'company-invoice'
                                        ])
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.company-invoices.show', $invoice) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.company-invoices.edit', $invoice) }}" 
                                               class="btn btn-outline-warning btn-icon-only" 
                                               title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($invoice->cashOutflows()->count() === 0)
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-icon-only" 
                                                        title="Xóa"
                                                        onclick="deleteInvoice({{ $invoice->id }}, '{{ addslashes($invoice->invoice_no) }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($invoices->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {!! $invoices->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'company-invoices-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) !!}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có hóa đơn nào</h5>
                    <p class="text-muted mb-3">Bắt đầu tạo hóa đơn đầu tiên</p>
                    <a href="{{ route('staff.company-invoices.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm Hóa đơn mới
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>


