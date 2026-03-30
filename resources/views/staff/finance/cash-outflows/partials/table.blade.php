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

<div class="col-12" id="cash-outflows-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-arrow-down me-2"></i>Danh sách Dòng tiền ra
                @if($cashOutflows && $cashOutflows->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $cashOutflows->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($cashOutflows && $cashOutflows->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nhà cung cấp</th>
                                <th>
                                    <a href="{{ $generateSortUrl('amount') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('amount') }}"
                                       hx-target="#cash-outflows-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Số tiền
                                        {!! $getSortIcon('amount') !!}
                                    </a>
                                </th>
                                <th>Phương thức</th>
                                <th>
                                    <a href="{{ $generateSortUrl('status') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('status') }}"
                                       hx-target="#cash-outflows-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Trạng thái
                                        {!! $getSortIcon('status') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('created_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('created_at') }}"
                                       hx-target="#cash-outflows-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày tạo
                                        {!! $getSortIcon('created_at') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('paid_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('paid_at') }}"
                                       hx-target="#cash-outflows-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày thanh toán
                                        {!! $getSortIcon('paid_at') !!}
                                    </a>
                                </th>
                                <th>Ghi chú</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cashOutflows as $cashOutflow)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#{{ $cashOutflow->id }}</span>
                                    </td>
                                    <td>
                                        @if($cashOutflow->vendor)
                                            <a href="{{ route('staff.vendors.show', $cashOutflow->vendor) }}" class="text-primary text-decoration-none">
                                                <strong>{{ $cashOutflow->vendor->name }}</strong>
                                            </a>
                                        @elseif($cashOutflow->companyInvoice && $cashOutflow->companyInvoice->user)
                                            <a href="{{ route('staff.users.show', $cashOutflow->companyInvoice->user) }}" class="text-info text-decoration-none">
                                                <strong>{{ $cashOutflow->companyInvoice->user->full_name ?? $cashOutflow->companyInvoice->user->name }}</strong>
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format($cashOutflow->amount, 0, ',', '.') }} VND</strong>
                                    </td>
                                    <td>
                                        @if($cashOutflow->paymentMethod)
                                            <span class="badge bg-info">
                                                {{ $cashOutflow->paymentMethod->name }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @include('staff.components.status-badge', [
                                            'status' => $cashOutflow->status,
                                            'type' => 'cash_outflow'
                                        ])
                                    </td>
                                    <td>
                                        <small>{{ $cashOutflow->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        @if($cashOutflow->paid_at)
                                            <small><strong>{{ $cashOutflow->paid_at->format('d/m/Y H:i') }}</strong></small>
                                        @else
                                            <span class="text-muted">Chưa thanh toán</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($cashOutflow->note)
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $cashOutflow->note }}">
                                                {{ Str::limit($cashOutflow->note, 50) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.cash-outflows.show', $cashOutflow) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.cash-outflows.edit', $cashOutflow) }}" 
                                               class="btn btn-outline-warning btn-icon-only" 
                                               title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-icon-only" 
                                                    onclick="deleteCashOutflow({{ $cashOutflow->id }}, '{{ addslashes($cashOutflow->note ?? 'Dòng tiền ra #' . $cashOutflow->id) }}')" 
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
                
                @if($cashOutflows->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {!! $cashOutflows->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'cash-outflows-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) !!}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-arrow-down fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có dòng tiền ra nào</h5>
                    <p class="text-muted mb-3">Bắt đầu tạo dòng tiền ra đầu tiên</p>
                    <a href="{{ route('staff.cash-outflows.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm Dòng tiền ra mới
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

