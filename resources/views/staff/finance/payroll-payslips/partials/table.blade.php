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

<div class="col-12" id="payslips-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-file-invoice-dollar me-2"></i>Danh sách Phiếu Lương
                @if($payslips && $payslips->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $payslips->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($payslips && $payslips->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nhân viên</th>
                                <th>Kỳ lương</th>
                                <th>Lương cơ bản</th>
                                <th>Hoa hồng</th>
                                <th>
                                    <a href="{{ $generateSortUrl('gross_amount') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('gross_amount') }}"
                                       hx-target="#payslips-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Tổng lương
                                        {!! $getSortIcon('gross_amount') !!}
                                    </a>
                                </th>
                                <th>Khấu trừ</th>
                                <th>
                                    <a href="{{ $generateSortUrl('net_amount') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('net_amount') }}"
                                       hx-target="#payslips-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Thực lĩnh
                                        {!! $getSortIcon('net_amount') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('status') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('status') }}"
                                       hx-target="#payslips-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Trạng thái
                                        {!! $getSortIcon('status') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('paid_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('paid_at') }}"
                                       hx-target="#payslips-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày thanh toán
                                        {!! $getSortIcon('paid_at') !!}
                                    </a>
                                </th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payslips as $payslip)
                                <tr>
                                    <td>
                                        <div>
                                            <strong>{{ $payslip->user->userProfile->full_name ?? $payslip->user->email }}</strong>
                                            <br><small class="text-muted">{{ $payslip->user->email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ \Carbon\Carbon::createFromFormat('Y-m', $payslip->payrollCycle->period_month)->format('m/Y') }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $salaryContract = \App\Models\SalaryContract::where('user_id', $payslip->user_id)
                                                ->where('status', 'active')
                                                ->first();
                                            $basicSalary = $salaryContract ? $salaryContract->base_salary : 0;
                                        @endphp
                                        <strong>{{ number_format($basicSalary, 0, ',', '.') }} VND</strong>
                                    </td>
                                    <td>
                                        @php
                                            $periodStart = \Carbon\Carbon::createFromFormat('Y-m', $payslip->payrollCycle->period_month)->startOfMonth();
                                            $periodEnd = \Carbon\Carbon::createFromFormat('Y-m', $payslip->payrollCycle->period_month)->endOfMonth();
                                            $commission = \App\Models\CommissionEvent::where('agent_id', $payslip->user_id)
                                                ->where('status', 'paid')
                                                ->whereBetween('occurred_at', [$periodStart, $periodEnd])
                                                ->sum('commission_total');
                                        @endphp
                                        <span class="text-success">{{ number_format($commission, 0, ',', '.') }} VND</span>
                                    </td>
                                    <td>
                                        <strong class="text-primary">{{ number_format($payslip->gross_amount, 0, ',', '.') }} VND</strong>
                                    </td>
                                    <td>
                                        {{ number_format($payslip->deduction_amount, 0, ',', '.') }} VND
                                    </td>
                                    <td>
                                        <strong class="text-success">{{ number_format($payslip->net_amount, 0, ',', '.') }} VND</strong>
                                    </td>
                                    <td>
                                        @include('staff.components.status-badge', [
                                            'status' => $payslip->status,
                                            'type' => 'payroll-payslip'
                                        ])
                                    </td>
                                    <td>
                                        @if($payslip->paid_at)
                                            <small>{{ \Carbon\Carbon::parse($payslip->paid_at)->format('d/m/Y H:i') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.payroll-payslips.show', $payslip->id) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($payslip->payrollCycle->status === 'open')
                                                <a href="{{ route('staff.payroll-payslips.edit', $payslip->id) }}" 
                                                   class="btn btn-outline-warning btn-icon-only" 
                                                   title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            @if($payslip->status === 'pending')
                                                <button type="button" class="btn btn-outline-success btn-icon-only" 
                                                        onclick="markAsPaid({{ $payslip->id }})" 
                                                        title="Đánh dấu đã thanh toán">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                            @if($payslip->payrollCycle->status === 'open')
                                                <button type="button" class="btn btn-outline-danger btn-icon-only" 
                                                        onclick="deletePayslip({{ $payslip->id }})" 
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
                
                @if($payslips->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {!! $payslips->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'payslips-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) !!}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có phiếu lương nào</h5>
                    <p class="text-muted mb-3">Phiếu lương sẽ xuất hiện khi tạo kỳ lương và tạo phiếu lương cho nhân viên.</p>
                    @if($isManager ?? false)
                        <a href="{{ route('staff.payroll-cycles.index') }}" class="btn btn-primary">
                            <i class="fas fa-calendar-alt me-1"></i>Quản lý kỳ lương
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

