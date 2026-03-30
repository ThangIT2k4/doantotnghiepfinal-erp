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

<div class="col-12" id="salary-contracts-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-file-contract me-2"></i>Danh sách Hợp đồng Lương
                @if($salaryContracts && $salaryContracts->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $salaryContracts->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($salaryContracts && $salaryContracts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nhân viên</th>
                                <th>
                                    <a href="{{ $generateSortUrl('base_salary') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('base_salary') }}"
                                       hx-target="#salary-contracts-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Lương cơ bản
                                        {!! $getSortIcon('base_salary') !!}
                                    </a>
                                </th>
                                <th>Chu kỳ trả</th>
                                <th>
                                    <a href="{{ $generateSortUrl('effective_from') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('effective_from') }}"
                                       hx-target="#salary-contracts-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày hiệu lực
                                        {!! $getSortIcon('effective_from') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('effective_to') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('effective_to') }}"
                                       hx-target="#salary-contracts-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày hết hạn
                                        {!! $getSortIcon('effective_to') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('status') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('status') }}"
                                       hx-target="#salary-contracts-table-container"
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
                            @foreach($salaryContracts as $contract)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#{{ $contract->id }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $contract->user->userProfile->full_name ?? $contract->user->email }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $contract->user->email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($contract->base_salary, 0, ',', '.') }} {{ $contract->currency }}</strong>
                                    </td>
                                    <td>
                                        @switch($contract->pay_cycle)
                                            @case('monthly')
                                                <span class="badge bg-info">Hàng tháng</span>
                                                @break
                                            @case('weekly')
                                                <span class="badge bg-warning">Hàng tuần</span>
                                                @break
                                            @case('daily')
                                                <span class="badge bg-success">Hàng ngày</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        <small>{{ $contract->effective_from->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $contract->effective_to ? $contract->effective_to->format('d/m/Y') : 'Không giới hạn' }}</small>
                                    </td>
                                    <td>
                                        @include('staff.components.status-badge', [
                                            'status' => $contract->status,
                                            'type' => 'salary-contract'
                                        ])
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.salary-contracts.show', $contract->id) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($contract->status !== 'terminated')
                                                <a href="{{ route('staff.salary-contracts.edit', $contract->id) }}" 
                                                   class="btn btn-outline-warning btn-icon-only" 
                                                   title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            @if($contract->status !== 'active')
                                                <button type="button" 
                                                        class="btn btn-outline-danger btn-icon-only" 
                                                        title="Xóa"
                                                        onclick="deleteSalaryContract({{ $contract->id }})">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($salaryContracts->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {!! $salaryContracts->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'salary-contracts-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) !!}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có hợp đồng lương nào</h5>
                    <p class="text-muted mb-3">Hãy tạo hợp đồng lương đầu tiên để bắt đầu quản lý.</p>
                    @if($isManager ?? false)
                        <a href="{{ route('staff.salary-contracts.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Tạo hợp đồng lương mới
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

