@php
    $sortBy = $sortBy ?? request('sort_by', 'id');
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

<div class="col-12" id="leads-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Danh sách Leads
                @if($leads->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $leads->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($leads->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <a href="{{ $generateSortUrl('id') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('id') }}"
                                       hx-target="#leads-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        ID
                                        {!! $getSortIcon('id') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('name') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('name') }}"
                                       hx-target="#leads-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Thông tin khách hàng
                                        {!! $getSortIcon('name') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('source') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('source') }}"
                                       hx-target="#leads-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Nguồn
                                        {!! $getSortIcon('source') !!}
                                    </a>
                                </th>
                                <th>Ngân sách</th>
                                <th>
                                    <a href="{{ $generateSortUrl('status') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('status') }}"
                                       hx-target="#leads-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Trạng thái
                                        {!! $getSortIcon('status') !!}
                                    </a>
                                </th>
                                <th>Lịch hẹn</th>
                                <th>
                                    <a href="{{ $generateSortUrl('created_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('created_at') }}"
                                       hx-target="#leads-table-container"
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
                            @foreach($leads as $lead)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#{{ $lead->id }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0">{{ $lead->name }}</h6>
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-1"></i>{{ $lead->phone }}
                                                @if($lead->email)
                                                    <br><i class="fas fa-envelope me-1"></i>{{ $lead->email }}
                                                @endif
                                                @if($lead->desired_city)
                                                    <br><i class="fas fa-map-marker-alt me-1"></i>{{ $lead->desired_city }}
                                                @endif
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $lead->source }}</span>
                                    </td>
                                    <td>
                                        @if($lead->budget_min || $lead->budget_max)
                                            <div class="text-nowrap">
                                                @if($lead->budget_min && $lead->budget_max)
                                                    {{ number_format($lead->budget_min) }} - {{ number_format($lead->budget_max) }} VNĐ
                                                @elseif($lead->budget_min)
                                                    Từ {{ number_format($lead->budget_min) }} VNĐ
                                                @else
                                                    Đến {{ number_format($lead->budget_max) }} VNĐ
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">Chưa xác định</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusLabels = [
                                                'new' => ['label' => 'Mới', 'class' => 'warning'],
                                                'contacted' => ['label' => 'Đã liên hệ', 'class' => 'info'],
                                                'qualified' => ['label' => 'Đủ điều kiện', 'class' => 'primary'],
                                                'proposal' => ['label' => 'Đề xuất', 'class' => 'secondary'],
                                                'negotiation' => ['label' => 'Thương lượng', 'class' => 'warning'],
                                                'converted' => ['label' => 'Đã chuyển đổi', 'class' => 'success'],
                                                'lost' => ['label' => 'Mất khách', 'class' => 'danger']
                                            ];
                                            $status = $statusLabels[$lead->status] ?? ['label' => $lead->status, 'class' => 'secondary'];
                                        @endphp
                                        <span class="badge bg-{{ $status['class'] }}">{{ $status['label'] }}</span>
                                    </td>
                                    <td>
                                        @if($lead->viewings->count() > 0)
                                            @php
                                                $latestViewing = $lead->viewings->first();
                                            @endphp
                                            <div class="d-flex align-items-center">
                                                <div class="me-2">
                                                    <i class="fas fa-calendar-check text-success"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold small">
                                                        {{ $latestViewing->schedule_at->format('d/m/Y H:i') }}
                                                    </div>
                                                    @if($latestViewing->agent)
                                                        <div class="text-muted small">
                                                            <i class="fas fa-user-tie me-1"></i>{{ $latestViewing->agent->full_name }}
                                                        </div>
                                                    @endif
                                                    @if($latestViewing->unit)
                                                        <div class="text-muted small">
                                                            <i class="fas fa-building me-1"></i>{{ $latestViewing->unit->property->name ?? 'N/A' }}
                                                        </div>
                                                    @endif
                                                    @if($lead->viewings->count() > 1)
                                                        <div class="text-info small">
                                                            <i class="fas fa-plus-circle me-1"></i>{{ $lead->viewings->count() - 1 }} lịch khác
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">
                                                <i class="fas fa-calendar-times me-1"></i>Chưa có lịch hẹn
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $lead->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.leads.show', $lead->id) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.leads.edit', $lead->id) }}" 
                                               class="btn btn-outline-warning btn-icon-only" 
                                               title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-icon-only" 
                                                    title="Xóa"
                                                    onclick="deleteLead({{ $lead->id }}, '{{ addslashes($lead->name) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($leads->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {{ $leads->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'leads-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có lead nào</h5>
                    <p class="text-muted mb-3">Bắt đầu tạo mục đầu tiên</p>
                    <a href="{{ route('staff.leads.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm Lead mới
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

