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

<div class="col-12" id="tickets-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-ticket-alt me-2"></i>Danh sách Ticket
                @if($tickets && $tickets->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $tickets->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($tickets && $tickets->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <a href="{{ $generateSortUrl('id') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('id') }}"
                                       hx-target="#tickets-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        ID
                                        {!! $getSortIcon('id') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('title') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('title') }}"
                                       hx-target="#tickets-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Tiêu đề
                                        {!! $getSortIcon('title') !!}
                                    </a>
                                </th>
                                <th>Trạng thái</th>
                                <th>Ưu tiên</th>
                                <th>Phòng/HĐ</th>
                                <th>Nhật ký gần nhất</th>
                                <th>Người tạo</th>
                                <th>Người phụ trách</th>
                                <th>
                                    <a href="{{ $generateSortUrl('created_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('created_at') }}"
                                       hx-target="#tickets-table-container"
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
                            @foreach($tickets as $ticket)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#{{ $ticket->id }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $ticket->title }}</div>
                                        @if($ticket->description)
                                            <small class="text-muted">{{ Str::limit($ticket->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @include('staff.components.status-badge', [
                                            'status' => $ticket->status,
                                            'type' => 'ticket'
                                        ])
                                    </td>
                                    <td>
                                        @php
                                            $priorityCode = $ticket->priorityRelation?->key_code ?? 'medium';
                                            $priorityColors = [
                                                'low' => 'secondary',
                                                'medium' => 'primary',
                                                'high' => 'warning',
                                                'urgent' => 'danger'
                                            ];
                                            $priorityLabels = [
                                                'low' => 'TB',
                                                'medium' => 'TB',
                                                'high' => 'Cao',
                                                'urgent' => 'KCấp'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $priorityColors[$priorityCode] ?? 'secondary' }}">
                                            {{ $priorityLabels[$priorityCode] ?? ucfirst($priorityCode) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($ticket->unit)
                                            <div class="small">
                                                <strong>{{ $ticket->unit->code }}</strong>
                                            </div>
                                        @endif
                                        @if($ticket->lease)
                                            <div class="small text-muted">
                                                {{ $ticket->lease->contract_no ?: 'HD#' . $ticket->lease->id }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $latestLog = $ticket->logs->sortByDesc('created_at')->first();
                                        @endphp
                                        @if($latestLog)
                                            <div class="small">
                                                <div class="fw-bold text-truncate" style="max-width: 200px;" title="{{ $latestLog->action }}">
                                                    {{ $latestLog->action }}
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-user"></i> {{ $latestLog->actor->full_name ?? 'System' }}
                                                    <br>
                                                    <i class="fas fa-clock"></i> {{ $latestLog->created_at->format('d/m/Y H:i') }}
                                                </small>
                                            </div>
                                        @else
                                            <span class="text-muted small">Chưa có nhật ký</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $ticket->createdBy->full_name ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $ticket->assignedTo->full_name ?? 'Chưa giao' }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $ticket->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.tickets.show', $ticket->id) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.tickets.edit', $ticket->id) }}" 
                                               class="btn btn-outline-warning btn-icon-only" 
                                               title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-icon-only" 
                                                    title="Xóa"
                                                    onclick="deleteTicket({{ $ticket->id }}, '{{ addslashes($ticket->title) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($tickets->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {!! $tickets->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'tickets-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) !!}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Không có ticket nào</h5>
                    <p class="text-muted mb-3">Chưa có ticket nào được tạo hoặc không tìm thấy kết quả phù hợp.</p>
                    <a href="{{ route('staff.tickets.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Tạo Ticket Đầu Tiên
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

