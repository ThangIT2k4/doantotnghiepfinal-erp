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

<div class="col-12" id="salary-advances-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-money-bill-wave me-2"></i>Danh sách Đơn Ứng Lương
                @if($salaryAdvances && $salaryAdvances->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $salaryAdvances->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($salaryAdvances && $salaryAdvances->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nhân viên</th>
                                <th>
                                    <a href="{{ $generateSortUrl('amount') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('amount') }}"
                                       hx-target="#salary-advances-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Số tiền
                                        {!! $getSortIcon('amount') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('advance_date') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('advance_date') }}"
                                       hx-target="#salary-advances-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày ứng
                                        {!! $getSortIcon('advance_date') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('expected_repayment_date') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('expected_repayment_date') }}"
                                       hx-target="#salary-advances-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày trả dự kiến
                                        {!! $getSortIcon('expected_repayment_date') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('status') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('status') }}"
                                       hx-target="#salary-advances-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Trạng thái
                                        {!! $getSortIcon('status') !!}
                                    </a>
                                </th>
                                <th>Phương thức trả</th>
                                <th>
                                    <a href="{{ $generateSortUrl('remaining_amount') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('remaining_amount') }}"
                                       hx-target="#salary-advances-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Còn lại
                                        {!! $getSortIcon('remaining_amount') !!}
                                    </a>
                                </th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salaryAdvances as $advance)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#{{ $advance->id }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $advance->user->userProfile->full_name ?? $advance->user->email }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $advance->user->email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($advance->amount, 0, ',', '.') }} {{ $advance->currency }}</strong>
                                    </td>
                                    <td>
                                        <small>{{ $advance->advance_date->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $advance->expected_repayment_date->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        @include('staff.components.status-badge', [
                                            'status' => $advance->status,
                                            'type' => 'salary-advance'
                                        ])
                                    </td>
                                    <td>{{ $advance->repayment_method_label }}</td>
                                    <td>
                                        <strong class="text-danger">
                                            {{ number_format($advance->remaining_amount, 0, ',', '.') }} {{ $advance->currency }}
                                        </strong>
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.salary-advances.show', $advance->id) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($advance->canBeDeleted())
                                                <a href="{{ route('staff.salary-advances.edit', $advance->id) }}" 
                                                   class="btn btn-outline-warning btn-icon-only" 
                                                   title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-icon-only" 
                                                        title="Xóa"
                                                        onclick="deleteAdvance({{ $advance->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                            @if($advance->canBeApproved())
                                                <button type="button" 
                                                        class="btn btn-outline-success btn-icon-only" 
                                                        title="Duyệt"
                                                        onclick="approveAdvance({{ $advance->id }})">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-icon-only" 
                                                        title="Từ chối"
                                                        onclick="rejectAdvance({{ $advance->id }})">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif
                                            @if($advance->canBeRepaid())
                                                <button type="button" 
                                                        class="btn btn-outline-info btn-icon-only" 
                                                        title="Thêm thanh toán"
                                                        onclick="addRepayment({{ $advance->id }})">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($salaryAdvances->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {!! $salaryAdvances->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'salary-advances-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) !!}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có đơn ứng lương nào</h5>
                    <p class="text-muted mb-3">Hãy tạo đơn ứng lương đầu tiên để bắt đầu quản lý.</p>
                    @if($isManager ?? false)
                        <a href="{{ route('staff.salary-advances.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Tạo đơn ứng lương mới
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

