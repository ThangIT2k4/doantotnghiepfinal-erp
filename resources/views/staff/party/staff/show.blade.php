@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết nhân viên')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết nhân viên',
            'subtitle' => 'Thông tin chi tiết và hiệu suất',
            'icon' => 'fas fa-user',
            'breadcrumbs' => [
                ['label' => 'Nhân viên', 'url' => route('staff.staff.index')],
                ['label' => $staff->full_name ?? $staff->email, 'active' => true]
            ]
        ])

        <!-- Tabs Navigation -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary active" onclick="toggleTab('basic-info', this)">
                        <i class="fas fa-user"></i> Thông tin cơ bản
                    </button>
                    {{--
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="toggleTab('salary-info', this)">
                        <i class="fas fa-money-bill-wave"></i> Lương
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleTab('salary-history', this)">
                        <i class="fas fa-file-invoice-dollar"></i> Lịch sử lương
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="toggleTab('commission-info', this)">
                        <i class="fas fa-chart-line"></i> Hoa hồng
                    </button>
                    --}}
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="toggleTab('kpi-dashboard', this)">
                        <i class="fas fa-chart-bar"></i> KPI Dashboard
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="toggleTab('workload', this)">
                        <i class="fas fa-tasks"></i> Workload
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="toggleTab('activity-log', this)">
                        <i class="fas fa-history"></i> Lịch sử hoạt động
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleTab('performance-trend', this)">
                        <i class="fas fa-chart-line"></i> Xu hướng
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleTab('properties', this)">
                        <i class="fas fa-building"></i> Bất động sản
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="expandAllTabs()">
                        <i class="fas fa-expand"></i> Mở tất cả
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="collapseAllTabs()">
                        <i class="fas fa-compress"></i> Đóng tất cả
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-basic-info">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user"></i> Thông tin cơ bản</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Họ và tên</label>
                                <div class="fw-bold">{{ $staff->full_name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Email</label>
                                <div class="fw-bold">{{ $staff->email }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Số điện thoại</label>
                                <div class="fw-bold">{{ $staff->phone ?? 'Chưa cập nhật' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Tổ chức</label>
                                <div>
                                    @if($staff->organizations->count() > 0)
                                        @foreach($staff->organizations as $org)
                                            <span class="badge bg-primary">{{ $org->name }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">Chưa được gắn tổ chức</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Vai trò</label>
                                <div>
                                    @foreach($staff->organizationRoles as $role)
                                    <span class="badge bg-info">{{ $role->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Trạng thái</label>
                                <div>
                                    @if($staff->status)
                                    <span class="badge bg-success">Hoạt động</span>
                                    @else
                                    <span class="badge bg-secondary">Tạm ngưng</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Ngày tạo</label>
                                <div class="fw-bold">{{ $staff->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Salary Information (tam an) --}}
                {{--
                <div class="card shadow-sm mb-4 tab-content" id="tab-salary-info" style="display: none;">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> Hợp đồng lương</h5>
                        <button class="btn btn-sm btn-light" onclick="loadSalaryHistory()">
                            <i class="fas fa-history"></i> Lịch sử
                        </button>
                    </div>
                    <div class="card-body" id="salary-info-content">
                        @php
                            $activeSalary = $staff->salaryContracts()->where('status', 'active')->latest('effective_from')->first();
                        @endphp
                        @if($activeSalary)
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="text-muted small">Lương cơ bản</label>
                                <div class="h4 text-success mb-0">{{ number_format($activeSalary->base_salary, 0, ',', '.') }} VNĐ</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="text-muted small">Tổ chức</label>
                                <div class="fw-bold">{{ $activeSalary->organization->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="text-muted small">Ngày trả lương</label>
                                <div class="fw-bold">Ngày {{ $activeSalary->pay_day }} hàng tháng</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Hiệu lực từ</label>
                                <div>{{ $activeSalary->effective_from->format('d/m/Y') }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Hiệu lực đến</label>
                                <div>{{ $activeSalary->effective_to ? $activeSalary->effective_to->format('d/m/Y') : 'Không giới hạn' }}</div>
                            </div>
                        </div>
                        @else
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle"></i> Chưa có hợp đồng lương nào được thiết lập.
                        </div>
                        @endif
                    </div>
                </div>
                --}}

                {{-- Salary History (tam an) --}}
                {{--
                <div class="card shadow-sm mb-4 tab-content" id="tab-salary-history" style="display: none;">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-file-invoice-dollar"></i> Lịch sử lương (12 tháng gần nhất)</h5>
                    </div>
                    <div class="card-body" id="salary-history-content">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                            <p class="mt-2 text-muted">Đang tải dữ liệu...</p>
                        </div>
                    </div>
                </div>
                --}}

                {{--
                <script>
                // Lazy load salary history data
                const salaryHistoryData = @json($salaryHistory ?? collect());
                </script>
                --}}

                {{-- Commission Statistics (tam an) --}}
                {{--
                <div class="card shadow-sm mb-4 tab-content" id="tab-commission-info" style="display: none;">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Thống kê hoa hồng</h5>
                        <button class="btn btn-sm btn-light" onclick="loadCommissionDetails()">
                            <i class="fas fa-list"></i> Chi tiết
                        </button>
                    </div>
                    <div class="card-body" id="commission-info-content">
                        <div class="row text-center">
                            @php
                                $totalPending = $commissionStats->where('status', 'pending')->first();
                                $totalBooked = $commissionStats->where('status', 'booked')->first();
                                $totalPaid = $commissionStats->where('status', 'paid')->first();
                            @endphp
                            <div class="col-md-4 mb-3">
                                <div class="border rounded p-3">
                                    <div class="text-warning h2">{{ number_format($totalPending->total_amount ?? 0, 0, ',', '.') }} VNĐ</div>
                                    <div class="text-muted">Đang chờ ({{ $totalPending->count ?? 0 }})</div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="border rounded p-3">
                                    <div class="text-info h2">{{ number_format($totalBooked->total_amount ?? 0, 0, ',', '.') }} VNĐ</div>
                                    <div class="text-muted">Đã ghi nhận ({{ $totalBooked->count ?? 0 }})</div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="border rounded p-3">
                                    <div class="text-success h2">{{ number_format($totalPaid->total_amount ?? 0, 0, ',', '.') }} VNĐ</div>
                                    <div class="text-muted">Đã trả ({{ $totalPaid->count ?? 0 }})</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                --}}

                <!-- Performance KPI Dashboard -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-kpi-dashboard" style="display: none;">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> KPI Dashboard</h5>
                        <div class="d-flex align-items-center gap-2">
                            <form method="GET" action="{{ route('staff.staff.show', $staff->id) }}" id="kpiDateForm" class="d-flex align-items-center gap-2">
                                @if(request()->has('tab'))
                                <input type="hidden" name="tab" value="{{ request('tab') }}">
                                @endif
                                <input type="date" name="kpi_date_from" id="kpi_date_from" class="form-control form-control-sm" 
                                       value="{{ request('kpi_date_from', $performance['date_from'] ?? now()->subDays(30)->format('Y-m-d')) }}" 
                                       style="width: 150px;">
                                <span class="text-white">đến</span>
                                <input type="date" name="kpi_date_to" id="kpi_date_to" class="form-control form-control-sm" 
                                       value="{{ request('kpi_date_to', $performance['date_to'] ?? now()->format('Y-m-d')) }}" 
                                       style="width: 150px;">
                                <button type="submit" class="btn btn-sm btn-light">
                                    <i class="fas fa-search"></i> Áp dụng
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-light" onclick="resetKPIDates()">
                                    <i class="fas fa-redo"></i> Mặc định
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Toggle Visibility Controls -->
                        <div class="mb-3 d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleKPICard('leads')">
                                <i class="fas fa-eye"></i> Leads
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="toggleKPICard('viewings')">
                                <i class="fas fa-eye"></i> Viewings
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleKPICard('leases')">
                                <i class="fas fa-eye"></i> Leases
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="toggleKPICard('conversion')">
                                <i class="fas fa-eye"></i> Conversion
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-dark" onclick="toggleKPICard('bookings')">
                                <i class="fas fa-eye"></i> Bookings
                            </button>
                        </div>
                        
                        @if(isset($performance))
                        <div class="row text-center">
                            <div class="col-md-3 mb-3 kpi-card" data-kpi="leads">
                                <div class="p-3 bg-primary bg-opacity-10 rounded border border-primary">
                                    <i class="fas fa-user-tag fa-2x text-primary mb-2"></i>
                                    <div class="h3 text-primary mb-0">{{ $performance['leads_count'] }}</div>
                                    <small class="text-muted">Leads Mới</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 kpi-card" data-kpi="viewings">
                                <div class="p-3 bg-info bg-opacity-10 rounded border border-info">
                                    <i class="fas fa-eye fa-2x text-info mb-2"></i>
                                    <div class="h3 text-info mb-0">{{ $performance['viewings_count'] }}</div>
                                    <small class="text-muted">Viewings</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 kpi-card" data-kpi="leases">
                                <div class="p-3 bg-warning bg-opacity-10 rounded border border-warning">
                                    <i class="fas fa-handshake fa-2x text-warning mb-2"></i>
                                    <div class="h3 text-warning mb-0">{{ $performance['leases_count'] }}</div>
                                    <small class="text-muted">Hợp Đồng</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 kpi-card" data-kpi="conversion">
                                <div class="p-3 bg-success bg-opacity-10 rounded border border-success">
                                    <i class="fas fa-percentage fa-2x text-success mb-2"></i>
                                    <div class="h3 text-success mb-0">{{ $performance['conversion_rate'] }}%</div>
                                    <small class="text-muted">Conversion Rate</small>
                                </div>
                            </div>
                        </div>
                        <div class="row text-center mt-3">
                            <div class="col-md-6 mb-3 kpi-card" data-kpi="bookings">
                                <div class="p-3 bg-secondary bg-opacity-10 rounded border border-secondary">
                                    <i class="fas fa-calendar-check fa-2x text-secondary mb-2"></i>
                                    <div class="h4 text-secondary mb-0">{{ $performance['bookings_count'] }}</div>
                                    <small class="text-muted">Bookings</small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 text-muted small">
                            <i class="fas fa-info-circle"></i> 
                            Khoảng thời gian: {{ \Carbon\Carbon::parse($performance['date_from'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($performance['date_to'])->format('d/m/Y') }}
                        </div>
                        @else
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle"></i> Chưa có dữ liệu hiệu suất.
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Workload Breakdown -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-workload" style="display: none;">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-tasks"></i> Workload Hiện Tại</h5>
                    </div>
                    <div class="card-body" id="workload-content">
                        @if(isset($workload))
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-primary bg-opacity-10 rounded">
                                    <i class="fas fa-user-tag fa-2x text-primary mb-2"></i>
                                    <div class="h3 text-primary mb-0">{{ $workload['active_leads'] }}</div>
                                    <small class="text-muted">Leads Đang Xử Lý</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-info bg-opacity-10 rounded">
                                    <i class="fas fa-eye fa-2x text-info mb-2"></i>
                                    <div class="h3 text-info mb-0">{{ $workload['active_viewings'] }}</div>
                                    <small class="text-muted">Viewings Đã Xác Nhận</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-warning bg-opacity-10 rounded">
                                    <i class="fas fa-calendar-check fa-2x text-warning mb-2"></i>
                                    <div class="h3 text-warning mb-0">{{ $workload['pending_bookings'] }}</div>
                                    <small class="text-muted">Bookings Đang Chờ</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3 bg-success bg-opacity-10 rounded">
                                    <i class="fas fa-file-contract fa-2x text-success mb-2"></i>
                                    <div class="h3 text-success mb-0">{{ $workload['active_leases'] }}</div>
                                    <small class="text-muted">Hợp Đồng Đang Hoạt Động</small>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle"></i> Chưa có dữ liệu workload.
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Activity Log -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-activity-log" style="display: none;">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Lịch Sử Hoạt Động (30 Ngày Gần Nhất)</h5>
                    </div>
                    <div class="card-body" id="activity-log-content">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                            <p class="mt-2 text-muted">Đang tải dữ liệu...</p>
                        </div>
                    </div>
                </div>

                <!-- Monthly Performance Trend -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-performance-trend" style="display: none;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Xu Hướng Hiệu Suất (6 Tháng Gần Nhất)</h5>
                    </div>
                    <div class="card-body">
                        <div id="performance-trend-content">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Đang tải...</span>
                                </div>
                                <p class="mt-2 text-muted">Đang tải dữ liệu...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                // Lazy load performance trend data
                const monthlyTrendData = @json($monthlyTrend ?? []);
                </script>

                <!-- Assigned Properties -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-properties" style="display: none;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-building"></i> Bất động sản đang quản lý ({{ $staff->assignedProperties->count() }})</h5>
                    </div>
                    <div class="card-body" id="properties-content">
                        @if($staff->assignedProperties->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tên BĐS</th>
                                        <th>Loại</th>
                                        <th>Địa chỉ</th>
                                        <th>Tổng phòng</th>
                                        <th>Ngày gắn</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($staff->assignedProperties as $property)
                                    <tr>
                                        <td><strong>{{ $property->name }}</strong></td>
                                        <td>{{ $property->propertyType->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($property->location)
                                            {{ $property->location->city }}
                                            @else
                                            N/A
                                            @endif
                                        </td>
                                        <td><span class="badge bg-info">{{ $property->units->count() }} phòng</span></td>
                                        <td>{{ $property->pivot->assigned_at ? \Carbon\Carbon::parse($property->pivot->assigned_at)->format('d/m/Y') : 'N/A' }}</td>
                                        <td>
                                            <a href="{{ route('staff.properties.show', $property->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-3 text-muted">
                            <i class="fas fa-building fa-2x mb-2"></i>
                            <p>Chưa có bất động sản nào được gắn</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Quick Stats -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Thống kê tổng quan</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-building text-primary"></i> BĐS quản lý</span>
                                <strong class="h4 mb-0">{{ $staff->assignedProperties->count() }}</strong>
                            </div>
                        </div>
                        {{--
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-file-contract text-success"></i> Hợp đồng</span>
                                <strong class="h4 mb-0">{{ $staff->commissionEvents->count() }}</strong>
                            </div>
                        </div>
                        --}}
                        {{--
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-money-bill-wave text-warning"></i> Tổng HH</span>
                                @php
                                    $totalCommission = $commissionStats->sum('total_amount');
                                @endphp
                                <strong class="h5 mb-0">{{ number_format($totalCommission, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                        --}}
                    </div>
                </div>

                <!-- Organization Info -->
                @if($staff->organizationUsers->count() > 0)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-sitemap"></i> Tổ chức</h6>
                    </div>
                    <div class="card-body">
                        @foreach($staff->organizationUsers as $orgUser)
                        <div class="mb-2">
                            <strong>{{ $orgUser->organization->name }}</strong>
                            <br>
                            <small class="text-muted">
                                Vai trò: {{ $orgUser->role->name ?? 'N/A' }}
                            </small>
                            <br>
                            <span class="badge {{ $orgUser->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $orgUser->status }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Actions -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Thao tác
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            // Primary Actions: Sửa, Xóa, Quay lại
                            $primaryActions = [
                                [
                                    'type' => 'link',
                                    'variant' => 'primary',
                                    'label' => 'Sửa',
                                    'icon' => 'fas fa-edit',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.staff.edit', $staff->id),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteStaff({$staff->id}, '" . addslashes($staff->full_name ?? $staff->email) . "')",
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Quay lại',
                                    'icon' => 'fas fa-arrow-left',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.staff.index'),
                                    'class' => 'w-100'
                                ]
                            ];
                            
                            // Status Actions: Dropdown cho các nút chuyển trạng thái
                            $statusActions = [];
                            
                            // Có thể kích hoạt nếu đang tạm ngưng
                            if (!$staff->status) {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Kích hoạt',
                                    'icon' => 'fas fa-check',
                                    'iconPosition' => 'left',
                                    'onclick' => "toggleStaffStatus({$staff->id}, '" . addslashes($staff->full_name ?? $staff->email) . "', " . ($staff->status ? 'true' : 'false') . ")",
                                    'class' => 'w-100'
                                ];
                            }
                            
                            // Có thể tạm ngưng nếu đang hoạt động
                            if ($staff->status) {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'warning',
                                    'label' => 'Tạm ngưng',
                                    'icon' => 'fas fa-pause',
                                    'iconPosition' => 'left',
                                    'onclick' => "toggleStaffStatus({$staff->id}, '" . addslashes($staff->full_name ?? $staff->email) . "', " . ($staff->status ? 'true' : 'false') . ")",
                                    'class' => 'w-100'
                                ];
                            }
                        @endphp
                        
                        <div class="d-grid gap-2">
                            {{-- Primary Actions: Sửa, Xóa, Quay lại (vertical) --}}
                            @include('staff.components.action-buttons', [
                                'layout' => 'vertical',
                                'size' => 'sm',
                                'actions' => $primaryActions
                            ])
                            
                            {{-- Status Actions: Dropdown cho các nút chuyển trạng thái --}}
                            @if(count($statusActions) > 0)
                                @include('staff.components.action-buttons', [
                                    'layout' => 'dropdown',
                                    'size' => 'sm',
                                    'dropdownLabel' => 'Chuyển trạng thái',
                                    'actions' => $statusActions
                                ])
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@push('styles')
<style>
.kpi-card.hidden {
    display: none !important;
}

.kpi-card {
    transition: all 0.3s ease;
}

#performanceTrendChart {
    max-height: 400px;
}

.tab-content {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tab-content.loading {
    position: relative;
    min-height: 200px;
}

.btn.active {
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Tab Management System
const tabStates = {
    'basic-info': true, // Always visible by default
    'kpi-dashboard': false,
    'workload': false,
    'activity-log': false,
    'performance-trend': false,
    'properties': false
};

// Load tab states from localStorage
document.addEventListener('DOMContentLoaded', function() {
    const savedStates = localStorage.getItem('staffTabStates');
    if (savedStates) {
        try {
            const parsed = JSON.parse(savedStates);
            Object.assign(tabStates, parsed);
        } catch (e) {
            console.error('Error loading tab states:', e);
        }
    }
    
    // Restore tab states
    Object.keys(tabStates).forEach(tabId => {
        const tab = document.getElementById(`tab-${tabId}`);
        const button = document.querySelector(`button[onclick="toggleTab('${tabId}', this)"]`);
        if (tab && button) {
            if (tabStates[tabId]) {
                tab.style.display = '';
                button.classList.add('active');
            } else {
                tab.style.display = 'none';
                button.classList.remove('active');
            }
        }
    });
    
    // Load content for visible tabs
    Object.keys(tabStates).forEach(tabId => {
        if (tabStates[tabId]) {
            loadTabContent(tabId);
        }
    });
});

// Toggle tab visibility
function toggleTab(tabId, button) {
    const tab = document.getElementById(`tab-${tabId}`);
    if (!tab) return;
    
    tabStates[tabId] = !tabStates[tabId];
    
    if (tabStates[tabId]) {
        tab.style.display = '';
        button.classList.add('active');
        loadTabContent(tabId);
    } else {
        tab.style.display = 'none';
        button.classList.remove('active');
    }
    
    // Save to localStorage
    localStorage.setItem('staffTabStates', JSON.stringify(tabStates));
}

// Load tab content (lazy load)
function loadTabContent(tabId) {
    const tab = document.getElementById(`tab-${tabId}`);
    if (!tab) return;
    
    // Check if content already loaded
    if (tab.dataset.loaded === 'true') {
        return;
    }
    
    // Load content based on tab type
    switch(tabId) {
        case 'performance-trend':
            loadPerformanceTrendContent();
            break;
        case 'activity-log':
            loadActivityLogContent();
            break;
        case 'properties':
            loadPropertiesContent();
            break;
        case 'kpi-dashboard':
        case 'workload':
            // These are already loaded, just mark as loaded
            tab.dataset.loaded = 'true';
            break;
    }
}

// Load performance trend content
function loadPerformanceTrendContent() {
    const container = document.getElementById('performance-trend-content');
    if (!container || container.dataset.loaded === 'true') return;
    
    container.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
            <p class="mt-2 text-muted">Đang tải dữ liệu xu hướng...</p>
        </div>
    `;
    
    // Try to use existing data first
    if (monthlyTrendData && monthlyTrendData.length > 0) {
        container.innerHTML = '<div style="position: relative; height: 400px;"><canvas id="performanceTrendChart"></canvas></div>';
        const tab = document.getElementById('tab-performance-trend');
        tab.dataset.loaded = 'true';
        
        // Initialize chart after a short delay to ensure canvas is ready
        setTimeout(() => {
            initializePerformanceTrendChart();
        }, 100);
        return;
    }
    
    // If no data, try to load via AJAX
    fetch(`{{ route('staff.staff.show', $staff->id) }}?tab=performance-trend`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.html) {
            container.innerHTML = data.html;
            // Update global data if provided
            if (data.data) {
                window.monthlyTrendData = data.data;
            }
            document.getElementById('tab-performance-trend').dataset.loaded = 'true';
            
            // Re-initialize chart with new data
            setTimeout(() => {
                initializePerformanceTrendChart();
            }, 100);
        } else {
            throw new Error('No HTML content received');
        }
    })
    .catch(error => {
        console.error('Error loading performance trend:', error);
        container.innerHTML = `
            <div class="text-center py-3 text-muted">
                <i class="fas fa-chart-bar fa-2x mb-2"></i>
                <p>Chưa có dữ liệu xu hướng hoặc không thể tải dữ liệu</p>
            </div>
        `;
    });
}

// Load activity log content
function loadActivityLogContent() {
    const container = document.getElementById('activity-log-content');
    if (!container || container.dataset.loaded === 'true') return;
    
    container.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
            <p class="mt-2 text-muted">Đang tải lịch sử hoạt động...</p>
        </div>
    `;
    
    // Load via AJAX
    fetch(`{{ route('staff.staff.show', $staff->id) }}?tab=activity-log`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.html) {
            container.innerHTML = data.html;
            container.dataset.loaded = 'true';
            document.getElementById('tab-activity-log').dataset.loaded = 'true';
        } else {
            throw new Error('No HTML content received');
        }
    })
    .catch(error => {
        console.error('Error loading activity log:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Không thể tải lịch sử hoạt động. Vui lòng tải lại trang.
            </div>
        `;
    });
}

// Load properties content
function loadPropertiesContent() {
    const container = document.getElementById('properties-content');
    if (!container || container.dataset.loaded === 'true') return;
    
    // Properties are already loaded in page, just mark as loaded
    container.dataset.loaded = 'true';
    document.getElementById('tab-properties').dataset.loaded = 'true';
}

// Expand all tabs
function expandAllTabs() {
    Object.keys(tabStates).forEach(tabId => {
        if (tabId !== 'basic-info') { // Keep basic-info always visible
            tabStates[tabId] = true;
            const tab = document.getElementById(`tab-${tabId}`);
            const button = document.querySelector(`button[onclick="toggleTab('${tabId}', this)"]`);
            if (tab && button) {
                tab.style.display = '';
                button.classList.add('active');
                loadTabContent(tabId);
            }
        }
    });
    localStorage.setItem('staffTabStates', JSON.stringify(tabStates));
}

// Collapse all tabs
function collapseAllTabs() {
    Object.keys(tabStates).forEach(tabId => {
        if (tabId !== 'basic-info') { // Keep basic-info always visible
            tabStates[tabId] = false;
            const tab = document.getElementById(`tab-${tabId}`);
            const button = document.querySelector(`button[onclick="toggleTab('${tabId}', this)"]`);
            if (tab && button) {
                tab.style.display = 'none';
                button.classList.remove('active');
            }
        }
    });
    localStorage.setItem('staffTabStates', JSON.stringify(tabStates));
}

// Helper function to format numbers
function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num || 0);
}

// Initialize performance trend chart
function initializePerformanceTrendChart() {
    const ctx = document.getElementById('performanceTrendChart');
    if (!ctx) return;
    
    if (window.performanceChart) {
        window.performanceChart.destroy();
    }
    
    // Use window.monthlyTrendData if available (from AJAX), otherwise use local monthlyTrendData
    const monthlyData = window.monthlyTrendData || monthlyTrendData;
    if (!Array.isArray(monthlyData) || monthlyData.length === 0) {
        return;
    }
    
    window.performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month || ''),
            datasets: [
                {
                    label: 'Leads',
                    data: monthlyData.map(d => parseInt(d.leads) || 0),
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Viewings',
                    data: monthlyData.map(d => parseInt(d.viewings) || 0),
                    borderColor: 'rgb(13, 202, 240)',
                    backgroundColor: 'rgba(13, 202, 240, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Leases',
                    data: monthlyData.map(d => parseInt(d.leases) || 0),
                    borderColor: 'rgb(255, 193, 7)',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Xu Hướng Hiệu Suất (6 Tháng Gần Nhất)',
                    font: {
                        size: 16
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.dataset.yAxisID === 'y1') {
                                label += context.parsed.y + 'k VNĐ';
                            } else {
                                label += context.parsed.y;
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Số lượng'
                    }
                },
                
            }
        }
    });
}

// KPI Card visibility toggle
const kpiCardStates = {
    leads: true,
    viewings: true,
    leases: true,
    conversion: true,
    bookings: true
};

function toggleKPICard(type) {
    kpiCardStates[type] = !kpiCardStates[type];
    const cards = document.querySelectorAll(`.kpi-card[data-kpi="${type}"]`);
    const buttons = document.querySelectorAll(`button[onclick="toggleKPICard('${type}')"]`);
    
    cards.forEach(card => {
        if (kpiCardStates[type]) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
    
    // Update button icon
    buttons.forEach(button => {
        const icon = button.querySelector('i');
        const text = button.textContent.trim().replace(/^.*\s/, ''); // Get text after icon
        if (kpiCardStates[type]) {
            icon.className = 'fas fa-eye';
        } else {
            icon.className = 'fas fa-eye-slash';
        }
    });
}

// Reset KPI dates to default (30 days)
function resetKPIDates() {
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);
    
    document.getElementById('kpi_date_from').value = thirtyDaysAgo.toISOString().split('T')[0];
    document.getElementById('kpi_date_to').value = today.toISOString().split('T')[0];
    document.getElementById('kpiDateForm').submit();
}

// Performance trend chart is now loaded lazily via loadPerformanceTrendContent()


function toggleStaffStatus(id, name, currentStatus) {
    console.log('toggleStaffStatus called:', { id, name, currentStatus, type: typeof currentStatus });
    
    // Ensure currentStatus is boolean
    currentStatus = currentStatus === true || currentStatus === 'true' || currentStatus === 1;
    
    const action = currentStatus ? 'tạm ngưng' : 'kích hoạt';
    const newStatus = currentStatus ? 0 : 1;
    
    console.log('Processed:', { action, newStatus });
    
    Notify.confirm({
        title: 'Chuyển trạng thái nhân viên',
        message: `Bạn có chắc chắn muốn ${action} nhân viên "${name}"?`,
        type: currentStatus ? 'warning' : 'success',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: () => {
            // Show loading notification
            const loadingToast = Notify.toast({
                title: 'Đang cập nhật...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });

            fetch(`/staff/staff/${id}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: newStatus
                })
            })
            .then(response => {
                // Hide loading notification
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const actionText = newStatus ? 'kích hoạt' : 'tạm ngưng';
                    Notify.success(`Đã ${actionText} nhân viên thành công!`, 'Thành công!');
                    // Reload page after a short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    Notify.error(data.message || 'Không thể cập nhật trạng thái nhân viên.', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Hide loading notification
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                
                Notify.error('Đã xảy ra lỗi khi cập nhật trạng thái nhân viên. Vui lòng thử lại.', 'Lỗi hệ thống!');
            });
        }
    });
}

function deleteStaff(id, name) {
    Notify.confirmDelete(`nhân viên "${name}"`, () => {
        if (window.Preloader) {
            window.Preloader.show();
        }

        fetch(`/staff/staff/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (window.Preloader) {
                window.Preloader.hide();
            }

            if (data.success) {
                Notify.success(data.message || 'Xóa nhân viên thành công!', 'Thành công!');
                setTimeout(() => {
                    window.location.href = '{{ route("staff.staff.index") }}';
                }, 1500);
            } else {
                Notify.error(data.message || 'Không thể xóa nhân viên', 'Lỗi!');
            }
        })
        .catch(error => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
            console.error('Error:', error);
            Notify.error('Đã xảy ra lỗi khi xóa nhân viên. Vui lòng thử lại.', 'Lỗi hệ thống!');
        });
    });
}
</script>
@endpush
@endsection


