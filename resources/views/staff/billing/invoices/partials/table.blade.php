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

<div class="col-12" id="invoices-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-file-invoice me-2"></i>Danh sách Hóa đơn
                @if($invoices && $invoices->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $invoices->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($invoices && $invoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>
                                    <a href="{{ $generateSortUrl('invoice_no') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('invoice_no') }}"
                                       hx-target="#invoices-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Số hóa đơn
                                        {!! $getSortIcon('invoice_no') !!}
                                    </a>
                                </th>
                                <th>Loại</th>
                                <th>Hợp đồng</th>
                                <th>Khách thuê</th>
                                <th>Bất động sản</th>
                                <th>
                                    <a href="{{ $generateSortUrl('issue_date') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('issue_date') }}"
                                       hx-target="#invoices-table-container"
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
                                       hx-target="#invoices-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Hạn thanh toán
                                        {!! $getSortIcon('due_date') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('total_amount') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('total_amount') }}"
                                       hx-target="#invoices-table-container"
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
                                       hx-target="#invoices-table-container"
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
                                        <span class="badge bg-secondary">#{{ $invoice->id }}</span>
                                    </td>
                                    <td>
                                        @if ($invoice->invoice_no)
                                            <code class="bg-light px-2 py-1 rounded">{{ $invoice->invoice_no }}</code>
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($invoice->invoice_type)
                                            @php
                                                $typeColors = [
                                                    'monthly_rent' => 'primary',
                                                    'first_invoice' => 'success',
                                                    'booking_deposit' => 'warning',
                                                    'other' => 'secondary'
                                                ];
                                                $color = $typeColors[$invoice->invoice_type] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">{{ $invoice->getInvoiceTypeLabel() }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($invoice->lease_id && $invoice->lease)
                                            <strong>{{ $invoice->lease->contract_no ?? 'HD#' . $invoice->lease->id }}</strong>
                                        @elseif ($invoice->booking_deposit_id && $invoice->bookingDeposit)
                                            <span class="badge bg-warning">Đặt cọc</span>
                                            <br><small class="text-muted">{{ $invoice->bookingDeposit->reference_number ?? 'BD#' . $invoice->bookingDeposit->id }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($invoice->lease_id && $invoice->lease && $invoice->lease->tenant)
                                            <div>
                                                <strong>{{ $invoice->lease->tenant->full_name }}</strong>
                                                @if ($invoice->lease->tenant->phone)
                                                    <br><small class="text-muted">{{ $invoice->lease->tenant->phone }}</small>
                                                @endif
                                            </div>
                                        @elseif ($invoice->booking_deposit_id && $invoice->bookingDeposit)
                                            <div>
                                                @if ($invoice->bookingDeposit->tenantUser)
                                                    <strong>{{ $invoice->bookingDeposit->tenantUser->full_name ?? 'N/A' }}</strong>
                                                    @if ($invoice->bookingDeposit->tenantUser->phone)
                                                        <br><small class="text-muted">{{ $invoice->bookingDeposit->tenantUser->phone }}</small>
                                                    @endif
                                                @elseif ($invoice->bookingDeposit->lead)
                                                    <strong>{{ $invoice->bookingDeposit->lead->name }}</strong>
                                                    @if ($invoice->bookingDeposit->lead->phone)
                                                        <br><small class="text-muted">{{ $invoice->bookingDeposit->lead->phone }}</small>
                                                    @endif
                                                    <br><small class="badge bg-warning">Lead</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($invoice->lease_id && $invoice->lease && $invoice->lease->unit && $invoice->lease->unit->property)
                                            <strong>{{ $invoice->lease->unit->property->name }}</strong>
                                            @if ($invoice->lease->unit->code)
                                                <br><small class="text-muted">Phòng {{ $invoice->lease->unit->code }}</small>
                                            @endif
                                        @elseif ($invoice->booking_deposit_id && $invoice->bookingDeposit && $invoice->bookingDeposit->unit && $invoice->bookingDeposit->unit->property)
                                            <strong>{{ $invoice->bookingDeposit->unit->property->name }}</strong>
                                            @if ($invoice->bookingDeposit->unit->code)
                                                <br><small class="text-muted">Phòng {{ $invoice->bookingDeposit->unit->code }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <small>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <strong class="text-primary">{{ number_format($invoice->total_amount, 0, ',', '.') }} VND</strong>
                                    </td>
                                    <td>
                                        @include('staff.components.status-badge', [
                                            'status' => $invoice->status,
                                            'type' => 'invoice'
                                        ])
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.invoices.show', $invoice->id) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.invoices.edit', $invoice->id) }}" 
                                               class="btn btn-outline-warning btn-icon-only" 
                                               title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($invoice->status === 'draft')
                                                <button class="btn btn-outline-success btn-icon-only" 
                                                        onclick="issueInvoice({{ $invoice->id }})" 
                                                        title="Phát hành">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            @endif
                                            @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                                                <button class="btn btn-outline-success btn-icon-only" 
                                                        onclick="markAsPaid({{ $invoice->id }})" 
                                                        title="Đánh dấu đã thanh toán">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-outline-danger btn-icon-only" 
                                                    onclick="deleteInvoice({{ $invoice->id }}, '{{ addslashes($invoice->invoice_no ?? 'Hóa đơn #' . $invoice->id) }}')" 
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
                
                @if($invoices->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {!! $invoices->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'invoices-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) !!}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có hóa đơn nào</h5>
                    <p class="text-muted mb-3">Bắt đầu tạo hóa đơn đầu tiên</p>
                    <a href="{{ route('staff.invoices.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm Hóa đơn mới
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

