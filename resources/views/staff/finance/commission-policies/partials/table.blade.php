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
    
    $triggerLabels = [
        'deposit_paid' => 'Thanh toán cọc',
        'lease_signed' => 'Ký hợp đồng',
        'invoice_paid' => 'Thanh toán hóa đơn',
        'viewing_done' => 'Hoàn thành xem phòng',
        'listing_published' => 'Đăng tin'
    ];
    
    $calcLabels = [
        'percent' => 'Phần trăm',
        'flat' => 'Số tiền cố định',
        'tiered' => 'Bậc thang'
    ];
    
    // Ensure $policies exists and is a collection
    if (!isset($policies) || !$policies) {
        $policies = collect([]);
    }
@endphp

<div class="col-12" id="commission-policies-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-file-contract me-2"></i>Danh sách Chính sách Hoa hồng
                @if(isset($policies) && $policies && method_exists($policies, 'count') && $policies->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $policies->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if(isset($policies) && $policies && method_exists($policies, 'count') && $policies->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="8%">
                                    <a href="{{ $generateSortUrl('id') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('id') }}"
                                       hx-target="#commission-policies-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        ID {!! $getSortIcon('id') !!}
                                    </a>
                                </th>
                                <th width="10%">
                                    <a href="{{ $generateSortUrl('code') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('code') }}"
                                       hx-target="#commission-policies-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Mã {!! $getSortIcon('code') !!}
                                    </a>
                                </th>
                                <th width="20%">
                                    <a href="{{ $generateSortUrl('title') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('title') }}"
                                       hx-target="#commission-policies-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Tên chính sách {!! $getSortIcon('title') !!}
                                    </a>
                                </th>
                                <th width="15%">
                                    <a href="{{ $generateSortUrl('trigger_event') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('trigger_event') }}"
                                       hx-target="#commission-policies-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Sự kiện kích hoạt {!! $getSortIcon('trigger_event') !!}
                                    </a>
                                </th>
                                <th width="12%">
                                    <a href="{{ $generateSortUrl('calc_type') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('calc_type') }}"
                                       hx-target="#commission-policies-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Loại tính toán {!! $getSortIcon('calc_type') !!}
                                    </a>
                                </th>
                                <th width="12%">Giá trị</th>
                                <th width="10%">
                                    <a href="{{ $generateSortUrl('active') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('active') }}"
                                       hx-target="#commission-policies-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Trạng thái {!! $getSortIcon('active') !!}
                                    </a>
                                </th>
                                <th width="8%">Số sự kiện</th>
                                <th width="12%">
                                    <a href="{{ $generateSortUrl('created_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('created_at') }}"
                                       hx-target="#commission-policies-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày tạo {!! $getSortIcon('created_at') !!}
                                    </a>
                                </th>
                                <th width="12%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($policies as $policy)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#{{ $policy->id }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $policy->code }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $policy->title }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $triggerLabels[$policy->trigger_event] ?? $policy->trigger_event }}</span>
                                    </td>
                                    <td>
                                        {{ $calcLabels[$policy->calc_type] ?? $policy->calc_type }}
                                    </td>
                                    <td>
                                        @if($policy->calc_type == 'percent')
                                            <strong class="text-primary">{{ $policy->percent_value }}%</strong>
                                        @elseif($policy->calc_type == 'flat')
                                            <strong class="text-primary">{{ number_format($policy->flat_amount, 0, ',', '.') }} VND</strong>
                                        @else
                                            <span class="text-muted">Bậc thang</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($policy->active)
                                            <span class="badge bg-success">Hoạt động</span>
                                        @else
                                            <span class="badge bg-secondary">Không hoạt động</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $policy->events_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <small>{{ $policy->created_at->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.commission-policies.show', $policy->id) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.commission-policies.edit', $policy->id) }}" 
                                               class="btn btn-outline-warning btn-icon-only" 
                                               title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-icon-only" 
                                                    onclick="deletePolicy({{ $policy->id }}, '{{ addslashes($policy->title) }}')" 
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
                
                @if($policies->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {!! $policies->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'commission-policies-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) !!}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có chính sách hoa hồng nào</h5>
                    <p class="text-muted mb-3">Hãy tạo chính sách hoa hồng đầu tiên để bắt đầu quản lý.</p>
                    <a href="{{ route('staff.commission-policies.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Tạo chính sách mới
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

