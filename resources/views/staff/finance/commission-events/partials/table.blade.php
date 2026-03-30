@php
    $sortBy = $sortBy ?? request('sort_by', 'id');
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
    
    $triggerLabels = [
        'deposit_paid' => 'Thanh toán cọc',
        'lease_signed' => 'Ký hợp đồng',
        'invoice_paid' => 'Thanh toán hóa đơn',
        'viewing_done' => 'Hoàn thành xem phòng',
        'listing_published' => 'Đăng tin'
    ];
    
    $statusColors = [
        'pending' => 'warning',
        'approved' => 'info',
        'paid' => 'success',
        'reversed' => 'danger',
        'cancelled' => 'secondary'
    ];
    
    $statusLabels = [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'paid' => 'Đã thanh toán',
        'reversed' => 'Đã hoàn',
        'cancelled' => 'Đã hủy'
    ];
    
    // Ensure $events exists and is a collection
    if (!isset($events) || !$events) {
        $events = collect([]);
    }
@endphp

<div class="col-12" id="commission-events-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-line me-2"></i>Danh sách Sự kiện Hoa hồng
                @if(isset($events) && $events && method_exists($events, 'count') && $events->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $events->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if(isset($events) && $events && method_exists($events, 'count') && $events->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="8%">
                                    <a href="{{ $generateSortUrl('id') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('id') }}"
                                       hx-target="#commission-events-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        ID {!! $getSortIcon('id') !!}
                                    </a>
                                </th>
                                <th width="15%">Nhân viên</th>
                                <th width="15%">Chính sách</th>
                                <th width="12%">
                                    <a href="{{ $generateSortUrl('trigger_event') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('trigger_event') }}"
                                       hx-target="#commission-events-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Sự kiện {!! $getSortIcon('trigger_event') !!}
                                    </a>
                                </th>
                                <th width="12%">Số tiền gốc</th>
                                <th width="12%">
                                    <a href="{{ $generateSortUrl('commission_total') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('commission_total') }}"
                                       hx-target="#commission-events-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Hoa hồng {!! $getSortIcon('commission_total') !!}
                                    </a>
                                </th>
                                <th width="10%">
                                    <a href="{{ $generateSortUrl('status') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('status') }}"
                                       hx-target="#commission-events-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Trạng thái {!! $getSortIcon('status') !!}
                                    </a>
                                </th>
                                <th width="12%">
                                    <a href="{{ $generateSortUrl('occurred_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('occurred_at') }}"
                                       hx-target="#commission-events-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày xảy ra {!! $getSortIcon('occurred_at') !!}
                                    </a>
                                </th>
                                <th width="12%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($events as $event)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#{{ $event->id }}</span>
                                    </td>
                                    <td>
                                        @if($event->agent)
                                            <div>
                                                <strong>{{ $event->agent->full_name }}</strong>
                                                <br><small class="text-muted">{{ $event->agent->email }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">Chưa gán</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($event->policy)
                                            <div>
                                                <strong>{{ $event->policy->title }}</strong>
                                                <br><small class="text-muted">{{ $event->policy->code ?? 'N/A' }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">Chưa gán chính sách</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $triggerLabels[$event->trigger_event] ?? $event->trigger_event }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($event->amount_base, 0, ',', '.') }} VND</strong>
                                    </td>
                                    <td>
                                        <strong class="text-success">{{ number_format($event->commission_total, 0, ',', '.') }} VND</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $statusColors[$event->status] }}">
                                            {{ $statusLabels[$event->status] }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $event->occurred_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.commission-events.show', $event->id) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.commission-events.edit', $event->id) }}" 
                                               class="btn btn-outline-warning btn-icon-only" 
                                               title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($event->status == 'pending')
                                                <button type="button" 
                                                        class="btn btn-outline-success btn-icon-only" 
                                                        onclick="approveEvent({{ $event->id }})" 
                                                        title="Duyệt">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                            @if($event->status == 'approved')
                                                <button type="button" 
                                                        class="btn btn-outline-primary btn-icon-only" 
                                                        onclick="markAsPaid({{ $event->id }})" 
                                                        title="Đánh dấu đã thanh toán">
                                                    <i class="fas fa-money-bill"></i>
                                                </button>
                                            @endif
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-icon-only" 
                                                    onclick="deleteEvent({{ $event->id }})" 
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
                
                @if($events->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {!! $events->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'commission-events-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) !!}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có sự kiện hoa hồng nào</h5>
                    <p class="text-muted mb-3">Các sự kiện hoa hồng sẽ xuất hiện khi có hoạt động liên quan.</p>
                    <a href="{{ route('staff.commission-events.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Tạo sự kiện đầu tiên
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>


