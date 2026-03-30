@php
    $sortBy = $sortBy ?? request('sort_by', 'period_month');
    $sortOrder = $sortOrder ?? request('sort_order', 'desc');
    
    // Generate sort URL for HTMX
    $generateSortUrl = function($field) use ($sortBy, $sortOrder) {
        $query = request()->query();
        unset($query['ajax']);
        $query['sort_by'] = $field;
        $query['sort_order'] = ($sortBy === $field && $sortOrder === 'asc') ? 'desc' : 'asc';
        return route('staff.payroll-cycles.index') . '?' . http_build_query($query);
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

@if($cycles->count() > 0)
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>
                        <a href="{{ $generateSortUrl('period_month') }}" 
                           class="text-decoration-none text-dark sort-link"
                           hx-get="{{ $generateSortUrl('period_month') }}"
                           hx-target="#payroll-cycles-table-container"
                           hx-swap="innerHTML"
                           hx-push-url="true"
                           style="cursor: pointer;">
                            Kỳ lương
                            {!! $getSortIcon('period_month') !!}
                        </a>
                    </th>
                    <th>
                        <a href="{{ $generateSortUrl('status') }}" 
                           class="text-decoration-none text-dark sort-link"
                           hx-get="{{ $generateSortUrl('status') }}"
                           hx-target="#payroll-cycles-table-container"
                           hx-swap="innerHTML"
                           hx-push-url="true"
                           style="cursor: pointer;">
                            Trạng thái
                            {!! $getSortIcon('status') !!}
                        </a>
                    </th>
                    <th>Số phiếu lương</th>
                    <th>Tổng lương</th>
                    <th>
                        <a href="{{ $generateSortUrl('locked_at') }}" 
                           class="text-decoration-none text-dark sort-link"
                           hx-get="{{ $generateSortUrl('locked_at') }}"
                           hx-target="#payroll-cycles-table-container"
                           hx-swap="innerHTML"
                           hx-push-url="true"
                           style="cursor: pointer;">
                            Ngày khóa
                            {!! $getSortIcon('locked_at') !!}
                        </a>
                    </th>
                    <th>
                        <a href="{{ $generateSortUrl('paid_at') }}" 
                           class="text-decoration-none text-dark sort-link"
                           hx-get="{{ $generateSortUrl('paid_at') }}"
                           hx-target="#payroll-cycles-table-container"
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
                @foreach($cycles as $cycle)
                <tr>
                    <td>
                        <strong>{{ \Carbon\Carbon::createFromFormat('Y-m', $cycle->period_month)->format('m/Y') }}</strong>
                    </td>
                    <td>
                        @include('staff.components.status-badge', [
                            'status' => $cycle->status,
                            'type' => 'payroll-cycle'
                        ])
                    </td>
                    <td>
                        <span class="badge bg-primary">{{ $cycle->payslips_count ?? 0 }}</span>
                    </td>
                    <td>
                        @php
                            $totalGross = $cycle->payslips->sum('gross_amount') ?? 0;
                        @endphp
                        <strong>{{ number_format($totalGross, 0, ',', '.') }} VND</strong>
                    </td>
                    <td>
                        @if($cycle->locked_at)
                            {{ \Carbon\Carbon::parse($cycle->locked_at)->format('d/m/Y H:i') }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($cycle->paid_at)
                            {{ \Carbon\Carbon::parse($cycle->paid_at)->format('d/m/Y H:i') }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($cycle->note)
                            <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $cycle->note }}">
                                {{ $cycle->note }}
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group table-actions" role="group">
                            <a href="{{ route('staff.payroll-cycles.show', $cycle->id) }}" 
                               class="btn btn-outline-primary btn-icon-only" 
                               title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($cycle->status === 'open')
                                <a href="{{ route('staff.payroll-cycles.edit', $cycle->id) }}" 
                                   class="btn btn-outline-warning btn-icon-only" 
                                   title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('staff.payroll-cycles.preview-payslips', $cycle->id) }}" 
                                   class="btn btn-outline-info btn-icon-only" 
                                   title="Preview phiếu lương">
                                    <i class="fas fa-search"></i>
                                </a>
                                <button type="button" class="btn btn-outline-success btn-icon-only" 
                                        onclick="generatePayslips({{ $cycle->id }})" 
                                        title="Tạo phiếu lương">
                                    <i class="fas fa-calculator"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-icon-only" 
                                        onclick="lockCycle({{ $cycle->id }})" 
                                        title="Khóa kỳ lương">
                                    <i class="fas fa-lock"></i>
                                </button>
                            @endif
                            @if($cycle->status === 'open' && ($cycle->payslips_count ?? 0) == 0)
                                <button type="button" class="btn btn-outline-danger btn-icon-only" 
                                        onclick="deleteCycle({{ $cycle->id }})" 
                                        title="Xóa">
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

    <!-- Pagination -->
    @if($cycles->hasPages())
        {{ $cycles->appends(request()->query())->links('vendor.pagination.custom', [
            'tableContainerId' => 'payroll-cycles-table-container',
            'htmxIndicator' => '#htmx-loading-index-filters-form'
        ]) }}
    @endif
@else
    <div class="text-center py-4">
        <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Chưa có kỳ lương nào</h5>
        <p class="text-muted">Hãy tạo kỳ lương đầu tiên để bắt đầu quản lý lương.</p>
        @if($isManager ?? false)
            <a href="{{ route('staff.payroll-cycles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tạo kỳ lương mới
            </a>
        @endif
    </div>
@endif

