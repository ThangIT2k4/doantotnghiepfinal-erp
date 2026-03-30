@php
    $sortBy = $sortBy ?? request('sort_by', 'schedule_at');
    $sortOrder = $sortOrder ?? request('sort_order', 'desc');
    
    // Generate sort URL
    $generateSortUrl = function($field) use ($sortBy, $sortOrder) {
        $query = request()->query();
        // Remove ajax parameter for HTMX requests
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

<div class="col-12" id="viewings-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Danh sách lịch hẹn
                @if($viewings->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $viewings->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($viewings->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <a href="{{ $generateSortUrl('id') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('id') }}"
                                       hx-target="#viewings-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        ID
                                        {!! $getSortIcon('id') !!}
                                    </a>
                                </th>
                                <th>Khách hàng</th>
                                <th>Loại</th>
                                <th>Bất động sản</th>
                                <th>Phòng</th>
                                <th>
                                    <a href="{{ $generateSortUrl('schedule_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('schedule_at') }}"
                                       hx-target="#viewings-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Thời gian hẹn
                                        {!! $getSortIcon('schedule_at') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('status') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('status') }}"
                                       hx-target="#viewings-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Trạng thái
                                        {!! $getSortIcon('status') !!}
                                    </a>
                                </th>
                                <th>Agent</th>
                                <th>
                                    <a href="{{ $generateSortUrl('created_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('created_at') }}"
                                       hx-target="#viewings-table-container"
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
                            @foreach($viewings as $viewing)
                                <tr>
                                    <td>
                                        <span class="text-muted">#{{ $viewing->id }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0">{{ $viewing->customer_name }}</h6>
                                            <small class="text-muted">
                                                @if($viewing->tenant)
                                                    {{ $viewing->tenant->email }}
                                                @else
                                                    {{ $viewing->lead_phone }}
                                                    @if($viewing->lead_email)
                                                        • {{ $viewing->lead_email }}
                                                    @endif
                                                @endif
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $viewing->customer_type === 'lead' ? 'bg-warning' : 'bg-success' }}">
                                            <i class="fas {{ $viewing->customer_type === 'lead' ? 'fa-user' : 'fa-user-tie' }} me-1"></i>
                                            {{ $viewing->customer_type === 'lead' ? 'Lead' : 'Tenant' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $viewing->property->name }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        @if($viewing->unit)
                                            <span class="badge bg-light text-dark">
                                                {{ $viewing->unit->code }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $viewing->schedule_at->format('d/m/Y') }}</strong>
                                        </div>
                                        <small class="text-muted">{{ $viewing->schedule_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        @include('staff.components.status-badge', [
                                            'status' => $viewing->status,
                                            'type' => 'viewing'
                                        ])
                                    </td>
                                    <td>
                                        @if($viewing->agent)
                                            <div>
                                                <strong>{{ $viewing->agent->full_name }}</strong>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $viewing->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.viewings.show', $viewing->id) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.viewings.edit', $viewing->id) }}" 
                                               class="btn btn-outline-warning btn-icon-only" 
                                               title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-icon-only" 
                                                    title="Xóa"
                                                    onclick="deleteViewing({{ $viewing->id }}, '{{ addslashes($viewing->customer_name) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($viewings->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {{ $viewings->appends(request()->query())->links('vendor.pagination.custom', [
                            'contentTypeOverride' => 'lịch hẹn',
                            'contentIconOverride' => 'fas fa-calendar-alt',
                            'tableContainerId' => 'viewings-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có lịch hẹn nào</h5>
                    <p class="text-muted">Bắt đầu tạo lịch hẹn đầu tiên</p>
                    <a href="{{ route('staff.viewings.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Tạo lịch hẹn mới
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

