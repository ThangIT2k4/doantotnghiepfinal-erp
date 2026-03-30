@extends('layouts.staff_dashboard')

@section('title', 'Staff Dashboard')

@section('content')
{{-- Dashboard chính cho Staff (Manager và Agent) --}}
{{-- Hiển thị thống kê tổng quan, biểu đồ, và các thông tin quan trọng --}}
<main class="main-content">
    {{-- Header: Hiển thị tiêu đề và thông tin user --}}
    <header class="header">
        <div class="header-content">
            <div class="header-info">
                <h1>Tổng quan hoạt động kinh doanh và hiệu suất</h1>
                {{-- Lấy thông tin user và roles để hiển thị --}}
                @php
                // Lấy user hiện tại đang đăng nhập → Dùng để lấy thông tin cá nhân
                $currentUser = auth()->user();
                // Lấy organization ID hiện tại → Dùng để filter data theo organization
                $organizationId = $currentUser->getCurrentOrganizationId();
                // Lấy danh sách roles của user trong organization → Dùng để hiển thị badges
                $userRoles = $organizationId ? $currentUser->organizationRoles($organizationId)->get() : collect();
                // Lấy tên đầy đủ từ userProfile, nếu không có thì dùng email → Dùng để hiển thị tên user
                $fullName = $currentUser->userProfile?->full_name ?? $currentUser->email;
                @endphp
            {{-- Hiển thị thông tin user và roles nếu có --}}
            @if($fullName || $userRoles->count() > 0)
                <div class="user-info">
                    @if($fullName)
                        {{-- Hiển thị tên đầy đủ của user --}}
                        <span class="user-name">{{ $fullName }}</span>
                    @endif
                    @if($userRoles->count() > 0)
                        {{-- Hiển thị danh sách roles của user dưới dạng badges --}}
                        <div class="user-roles">
                            @foreach($userRoles as $role)
                                <span class="badge badge-sm bg-info">{{ $role->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
            </div>
            {{-- Các action buttons: Làm mới cache và thêm BĐS mới --}}
            <div class="header-actions">
                {{-- Button làm mới cache: Xóa cache dashboard và reload dữ liệu mới nhất --}}
                <button onclick="clearDashboardCache()" class="btn btn-outline-secondary me-2" title="Làm mới dữ liệu">
                    <i class="fas fa-sync-alt"></i>
                    Làm mới
                </button>
                {{-- Button thêm BĐS mới: Chuyển đến trang tạo property mới --}}
                <a href="{{ route('staff.properties.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Thêm BĐS mới
                </a>
            </div>
        </div>
    </header>
    
    <div class="content" id="content">
        {{-- Key Performance Stats: 4 thẻ thống kê chính --}}
        {{-- Hiển thị số liệu quan trọng nhất: BĐS, Occupancy, Viewings, Conversion/Leases --}}
        <div class="stats-grid">
            {{-- Thẻ 1: Số lượng BĐS quản lý --}}
            {{-- Manager: Hiển thị tổng số properties của organization --}}
            {{-- Agent: Hiển thị số properties được assign cho agent --}}
            <div class="stat-card primary">
                <div class="stat-header">
                    <span class="stat-title">BĐS Quản lý</span>
                    <i class="fas fa-building stat-icon"></i>
                </div>
                {{-- Hiển thị số lượng properties: Manager dùng properties_count, Agent dùng total_properties --}}
                <div class="stat-value">{{ $isManager ? ($dashboardData['stats']['properties_count'] ?? 0) : ($dashboardData['stats']['total_properties'] ?? 0) }}</div>
                <div class="stat-footer">
                    <span class="stat-label">Tổng tài sản</span>
                </div>
            </div>
            
            {{-- Thẻ 2: Tỷ lệ lấp đầy (Occupancy Rate) --}}
            {{-- Hiển thị phần trăm phòng đã được thuê so với tổng số phòng --}}
            <div class="stat-card success">
                <div class="stat-header">
                    <span class="stat-title">Tỷ lệ lấp đầy</span>
                    <i class="fas fa-chart-pie stat-icon"></i>
                </div>
                <div class="stat-value">
                    @if($isManager)
                        {{-- Manager: Occupancy rate đã được tính sẵn trong controller --}}
                        {{ $dashboardData['stats']['occupancy_rate'] ?? 0 }}%
                    @else
                        {{-- Agent: Tính occupancy rate từ total_units và occupied_units --}}
                        @php
                            // Lấy tổng số units và số units đã thuê → Dùng để tính occupancy rate
                            $totalUnits = $dashboardData['stats']['total_units'] ?? 0;
                            $occupiedUnits = $dashboardData['stats']['occupied_units'] ?? 0;
                            // Tính occupancy rate: (occupied / total) * 100 → Dùng để hiển thị tỷ lệ lấp đầy
                            $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;
                        @endphp
                        {{ $occupancyRate }}%
                    @endif
                </div>
                <div class="stat-footer">
                    <span class="stat-label">
                        {{-- Hiển thị số phòng đã thuê / tổng số phòng --}}
                        @if($isManager)
                            {{ $dashboardData['stats']['occupied_units'] ?? 0 }}/{{ $dashboardData['stats']['total_units'] ?? 0 }} phòng
                        @else
                            {{ $dashboardData['stats']['occupied_units'] ?? 0 }}/{{ $dashboardData['stats']['total_units'] ?? 0 }} phòng
                        @endif
                    </span>
                </div>
            </div>
            
            {{-- Thẻ 3: Lịch xem phòng (Viewings) --}}
            {{-- Manager: Hiển thị upcoming viewings (sắp tới) --}}
            {{-- Agent: Hiển thị viewings hôm nay --}}
            <div class="stat-card warning">
                <div class="stat-header">
                    <span class="stat-title">Lịch xem phòng</span>
                    <i class="fas fa-calendar-check stat-icon"></i>
                </div>
                <div class="stat-value">
                    @if($isManager)
                        {{-- Manager: Số viewings sắp tới (confirmed, schedule_at >= now) --}}
                        {{ $dashboardData['stats']['upcoming_viewings'] ?? 0 }}
                    @else
                        {{-- Agent: Số viewings hôm nay (schedule_at = today) --}}
                        {{ $dashboardData['stats']['today_viewings'] ?? 0 }}
                    @endif
                </div>
                <div class="stat-footer">
                    <span class="stat-label">
                        @if($isManager)
                            Lịch hẹn sắp tới
                        @else
                            Lịch hẹn hôm nay
                        @endif
                    </span>
                </div>
            </div>
            
            {{-- Thẻ 4: Conversion Rate (Manager) hoặc Active Leases (Agent) --}}
            {{-- Manager: Hiển thị tỷ lệ chuyển đổi từ leads sang converted --}}
            {{-- Agent: Hiển thị số hợp đồng đang hoạt động --}}
            <div class="stat-card info">
                <div class="stat-header">
                    <span class="stat-title">
                        @if($isManager)
                            Tỷ lệ chuyển đổi
                        @else
                            Hợp đồng hoạt động
                        @endif
                    </span>
                    <i class="fas fa-{{ $isManager ? 'percentage' : 'file-contract' }} stat-icon"></i>
                </div>
                <div class="stat-value">
                    @if($isManager)
                        {{-- Manager: Conversion rate = (converted_leads / total_leads) * 100 --}}
                        {{ $dashboardData['stats']['conversion_rate'] ?? 0 }}%
                    @else
                        {{-- Agent: Số leases đang active mà agent quản lý --}}
                        {{ $dashboardData['stats']['active_leases'] ?? 0 }}
                    @endif
                </div>
                <div class="stat-footer">
                    <span class="stat-label">
                        @if($isManager)
                            {{-- Manager: Hiển thị số leads đã converted / tổng số leads --}}
                            {{ $dashboardData['stats']['converted_leads'] ?? 0 }}/{{ $dashboardData['stats']['total_leads'] ?? 0 }} leads
                        @else
                            Hợp đồng đang hoạt động
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Revenue & Commission Stats: Chỉ hiển thị cho Manager --}}
        {{-- Hiển thị doanh thu, hoa hồng, và các tasks cần xử lý --}}
        @if($isManager && isset($dashboardData['revenue']))
        <div class="revenue-stats">
            {{-- Thẻ 1: Doanh thu tháng này --}}
            {{-- Hiển thị tổng doanh thu từ invoices đã thanh toán trong tháng hiện tại --}}
            <div class="stat-card-large revenue">
                <div class="stat-header">
                    <div>
                        <h3>Doanh thu tháng này</h3>
                        <p class="text-muted">Tổng thu từ hóa đơn</p>
                    </div>
                    <i class="fas fa-dollar-sign stat-icon-large"></i>
                </div>
                {{-- Hiển thị doanh thu: Chia cho 1,000,000 để hiển thị đơn vị triệu (M) --}}
                <div class="stat-value-large">{{ number_format(($dashboardData['revenue']['monthly_revenue'] ?? 0) / 1000000, 1) }}M</div>
                {{-- Hiển thị trend: Tăng/giảm so với tháng trước --}}
                <div class="stat-trend {{ ($dashboardData['revenue']['revenue_growth'] ?? 0) >= 0 ? 'up' : 'down' }}">
                    <i class="fas fa-arrow-{{ ($dashboardData['revenue']['revenue_growth'] ?? 0) >= 0 ? 'up' : 'down' }}"></i>
                    <span>{{ ($dashboardData['revenue']['revenue_growth'] ?? 0) >= 0 ? '+' : '' }}{{ $dashboardData['revenue']['revenue_growth'] ?? 0 }}% so với tháng trước</span>
                </div>
            </div>

            <div class="stat-card-large commission">
                <div class="stat-header">
                    <div>
                        <h3>Hoa hồng tháng này</h3>
                        <p class="text-muted">Tổng hoa hồng dự kiến</p>
                    </div>
                    <i class="fas fa-hand-holding-usd stat-icon-large"></i>
                </div>
                <div class="stat-value-large">{{ number_format(($dashboardData['revenue']['monthly_commission'] ?? 0) / 1000000, 1) }}M</div>
                <div class="stat-trend {{ ($dashboardData['revenue']['commission_growth'] ?? 0) >= 0 ? 'up' : 'down' }}">
                    <i class="fas fa-arrow-{{ ($dashboardData['revenue']['commission_growth'] ?? 0) >= 0 ? 'up' : 'down' }}"></i>
                    <span>{{ ($dashboardData['revenue']['commission_growth'] ?? 0) >= 0 ? '+' : '' }}{{ $dashboardData['revenue']['commission_growth'] ?? 0 }}% so với tháng trước</span>
                </div>
            </div>

            <div class="stat-card-large pending">
                <div class="stat-header">
                    <div>
                        <h3>Cần xử lý</h3>
                        <p class="text-muted">Hóa đơn & tickets</p>
                    </div>
                    <i class="fas fa-exclamation-circle stat-icon-large"></i>
                </div>
                <div class="stat-value-large">{{ ($dashboardData['revenue']['pending_invoices'] ?? 0) + ($dashboardData['revenue']['open_tickets'] ?? 0) }}</div>
                <div class="stat-details">
                    <span>{{ $dashboardData['revenue']['pending_invoices'] ?? 0 }} hóa đơn, {{ $dashboardData['revenue']['open_tickets'] ?? 0 }} tickets</span>
                </div>
            </div>
        </div>
        
        {{-- Agent Commission Stats: Chỉ hiển thị cho Agent --}}
        {{-- Hiển thị tổng quan hoa hồng và đặt cọc đang xử lý --}}
        @elseif($isAgent && isset($dashboardData['commissionSummary']))
        <div class="revenue-stats">
            {{-- Thẻ 1: Tổng hoa hồng đã nhận --}}
            {{-- Hiển thị tổng commission đã thanh toán (status = 'paid') --}}
            {{--
            <div class="stat-card-large revenue">
                <div class="stat-header">
                    <div>
                        <h3>Tổng hoa hồng đã nhận</h3>
                        <p class="text-muted">Tổng hoa hồng đã thanh toán</p>
                    </div>
                    <i class="fas fa-dollar-sign stat-icon-large"></i>
                </div>
                <div class="stat-value-large">{{ number_format(($dashboardData['commissionSummary']['total_paid'] ?? 0) / 1000000, 1) }}M</div>
                <div class="stat-details">
                    <span>Đã nhận trong tháng này: {{ number_format(($dashboardData['commissionSummary']['this_month'] ?? 0) / 1000, 0) }}K</span>
                </div>
            </div>

            <div class="stat-card-large commission">
                <div class="stat-header">
                    <div>
                        <h3>Hoa hồng chờ thanh toán</h3>
                        <p class="text-muted">Tổng hoa hồng đang chờ</p>
                    </div>
                    <i class="fas fa-hand-holding-usd stat-icon-large"></i>
                </div>
                <div class="stat-value-large">{{ number_format(($dashboardData['commissionSummary']['total_pending'] ?? 0) / 1000000, 1) }}M</div>
                <div class="stat-details">
                    <span>Chờ thanh toán tháng này: {{ number_format(($dashboardData['commissionSummary']['this_month_pending'] ?? 0) / 1000, 0) }}K</span>
                </div>
            </div>
            --}}

            <div class="stat-card-large pending">
                <div class="stat-header">
                    <div>
                        <h3>Đặt cọc đang xử lý</h3>
                        <p class="text-muted">Booking deposits</p>
                    </div>
                    <i class="fas fa-credit-card stat-icon-large"></i>
                </div>
                <div class="stat-value-large">{{ $dashboardData['stats']['active_bookings'] ?? 0 }}</div>
                <div class="stat-details">
                    <span>{{ $dashboardData['stats']['total_viewings'] ?? 0 }} lượt xem phòng, {{ $dashboardData['stats']['my_leads'] ?? 0 }} leads</span>
                </div>
            </div>
        </div>
        @endif
        
        {{-- Main Dashboard Grid: Layout chính với 2 cột --}}
        {{-- Cột trái: Charts và data visualization --}}
        {{-- Cột phải: Quick actions và alerts --}}
        <div class="dashboard-grid">
            {{-- Left Column: Charts & Data --}}
            <div class="chart-section">
                {{-- Revenue Chart: Biểu đồ doanh thu và hoa hồng 6 tháng gần nhất --}}
                {{-- 
                    LUỒNG XỬ LÝ:
                    1. Canvas element được render trong HTML với id="revenueChart"
                    2. Khi trang load, JavaScript function initChart() được gọi (trong dashboard.js)
                    3. initChart() fetch dữ liệu từ API endpoint /staff/dashboard/revenue-chart
                    4. API trả về JSON: { success: true, data: { labels: [...], revenue: [...], commission: [...] } }
                    5. JavaScript format labels sang tiếng Việt (Tháng 1, Tháng 2, ...)
                    6. Tạo Chart.js instance với 2 datasets: Doanh thu (tím) và Hoa hồng (xanh lá)
                    7. Hiển thị biểu đồ line chart với animation
                    
                    DỮ LIỆU:
                    - labels: Array 6 phần tử (6 tháng gần nhất, format: "01/2024", "02/2024", ...)
                    - revenue: Array 6 phần tử (doanh thu từng tháng, đơn vị: triệu VND)
                    - commission: Array 6 phần tử (hoa hồng từng tháng, đơn vị: triệu VND)
                    
                    API ENDPOINT:
                    - Route: GET /staff/dashboard/revenue-chart
                    - Controller: DashboardController@getRevenueChartData()
                    - Response: JSON với labels, revenue, commission data
                --}}
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Biểu đồ doanh thu 6 tháng</h3>
                        <div class="card-actions">
                            {{-- Button xuất báo cáo (chưa implement) --}}
                          
                        </div>
                    </div>
                    <div class="card-content">
                        {{-- 
                            Canvas element: Dùng để vẽ biểu đồ Chart.js
                            - id="revenueChart": JavaScript sẽ tìm element này để khởi tạo chart
                            - Chart được khởi tạo tự động khi trang load (trong dashboard.js)
                            - Nếu không có dữ liệu, sẽ hiển thị loading hoặc error message
                        --}}
                        <div class="chart-container" style="position: relative; height: 350px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Occupancy & Availability: Chỉ hiển thị cho Manager --}}
                {{-- Hiển thị tình trạng phòng: Trống, Đã thuê, Đặt cọc, Bảo trì --}}
                @if($isManager && isset($dashboardData['occupancy']))
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-home"></i> Tình trạng phòng</h3>
                    </div>
                    <div class="card-content">
                        <div class="occupancy-grid">
                            {{-- Phòng trống: Units không có lease và không maintenance --}}
                            <div class="occupancy-item available">
                                <div class="occupancy-icon"><i class="fas fa-check-circle"></i></div>
                                <div class="occupancy-info">
                                    <div class="occupancy-value">{{ $dashboardData['occupancy']['available'] ?? 0 }}</div>
                                    <div class="occupancy-label">Trống</div>
                                </div>
                            </div>
                            <div class="occupancy-item occupied">
                                <div class="occupancy-icon"><i class="fas fa-users"></i></div>
                                <div class="occupancy-info">
                                    <div class="occupancy-value">{{ $dashboardData['occupancy']['occupied'] ?? 0 }}</div>
                                    <div class="occupancy-label">Đã thuê</div>
                                </div>
                            </div>
                            <div class="occupancy-item reserved">
                                <div class="occupancy-icon"><i class="fas fa-clock"></i></div>
                                <div class="occupancy-info">
                                    <div class="occupancy-value">{{ $dashboardData['occupancy']['reserved'] ?? 0 }}</div>
                                    <div class="occupancy-label">Đặt cọc</div>
                                </div>
                            </div>
                            <div class="occupancy-item maintenance">
                                <div class="occupancy-icon"><i class="fas fa-tools"></i></div>
                                <div class="occupancy-info">
                                    <div class="occupancy-value">{{ $dashboardData['occupancy']['maintenance'] ?? 0 }}</div>
                                    <div class="occupancy-label">Bảo trì</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Agent Properties Overview: Chỉ hiển thị cho Agent --}}
                {{-- Hiển thị danh sách 5 properties được assign cho agent với stats chi tiết --}}
                @elseif($isAgent && isset($dashboardData['properties']))
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-building"></i> Bất động sản được gán</h3>
                    </div>
                    <div class="card-content">
                        <div class="properties-list">
                            {{-- Loop qua 5 properties đầu tiên được assign cho agent --}}
                            @forelse($dashboardData['properties']->take(5) as $property)
                            <div class="property-item">
                                <div class="property-info">
                                    {{-- Tên property --}}
                                    <div class="property-name">{{ $property->name }}</div>
                                    {{-- Stats: Tổng số phòng và occupancy rate --}}
                                    <div class="property-stats">
                                        <span>{{ $property->total_units ?? 0 }} phòng</span>
                                        <span class="separator">|</span>
                                        <span>{{ $property->occupancy_rate ?? 0 }}% lấp đầy</span>
                                    </div>
                                </div>
                                <div class="property-status">
                                    {{-- Badge màu dựa trên occupancy rate: >= 80% (success), >= 50% (warning), < 50% (info) --}}
                                    <span class="badge badge-{{ $property->occupancy_rate >= 80 ? 'success' : ($property->occupancy_rate >= 50 ? 'warning' : 'info') }}">
                                        {{ $property->available_units ?? 0 }} trống
                                    </span>
                                </div>
                            </div>
                            @empty
                            {{-- Hiển thị message nếu agent chưa được assign property nào --}}
                            <p class="text-muted">Chưa có bất động sản được gán</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endif

                {{-- Top Performers: Chỉ hiển thị cho Manager --}}
                {{-- Hiển thị top 5 agents có commission cao nhất trong tháng hiện tại --}}
                @if($isManager && isset($dashboardData['topPerformers']))
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-trophy"></i> Top CTV/Nhân viên</h3>
                    </div>
                    <div class="card-content">
                        <div class="top-agents-list">
                            {{-- Loop qua top 5 agents, sắp xếp theo commission giảm dần --}}
                            @forelse ($dashboardData['topPerformers'] as $index => $agent)
                            <div class="agent-item">
                                {{-- Rank: Vị trí trong top (1-5) --}}
                                <div class="agent-rank rank-{{ $index + 1 }}">{{ $index + 1 }}</div>
                                <div class="agent-info">
                                    {{-- Tên đầy đủ của agent --}}
                                    <div class="agent-name">{{ $agent->full_name ?? 'N/A' }}</div>
                                    {{-- Số lượng deals (giao dịch) trong tháng --}}
                                    <div class="agent-stats">{{ $agent->deals ?? 0 }} giao dịch</div>
                                </div>
                                {{-- Tổng commission: Chia cho 1,000,000 để hiển thị đơn vị triệu (M) --}}
                                <div class="agent-commission">{{ number_format(($agent->total_commission ?? 0) / 1000000, 1) }}M</div>
                            </div>
                            @empty
                            {{-- Hiển thị message nếu chưa có dữ liệu --}}
                            <p class="text-muted">Chưa có dữ liệu</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endif
            </div>
            
            {{-- Right Sidebar: Quick Actions & Alerts --}}
            <div class="right-sidebar">
                {{-- Quick Actions: Các thao tác nhanh thường dùng --}}
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Thao tác nhanh</h3>
                    </div>
                    <div class="card-content">
                        {{-- Button thêm BĐS mới: Chuyển đến trang tạo property --}}
                        <a href="{{ route('staff.properties.create') }}" class="quick-action-btn">
                            <i class="fas fa-building"></i>
                            <span>Thêm BĐS mới</span>
                        </a>
                        {{-- Button thêm tài khoản: Chuyển đến trang tạo user --}}
                        <a href="{{ route('staff.users.create') }}" class="quick-action-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Thêm tài khoản</span>
                        </a>
                        {{-- Button gán nhân viên: Chuyển đến trang quản lý staff --}}
                        <a href="{{ route('staff.staff.index') }}" class="quick-action-btn">
                            <i class="fas fa-user-tie"></i>
                            <span>Gán nhân viên</span>
                        </a>
                    </div>
                </div>

                {{-- Urgent Tasks: Chỉ hiển thị cho Manager --}}
                {{-- Hiển thị các tasks cần xử lý gấp: Overdue invoices, expiring leases, pending viewings --}}
                @if($isManager && isset($dashboardData['urgentTasks']))
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bell"></i> Cần xử lý ngay</h3>
                    </div>
                    <div class="card-content">
                        {{-- Alert 1: Hóa đơn quá hạn (status = 'overdue') --}}
                        <div class="alert-item urgent">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div class="alert-content">
                                <div class="alert-title">Hóa đơn quá hạn</div>
                                <div class="alert-value">{{ $dashboardData['urgentTasks']['overdue_invoices'] ?? 0 }} hóa đơn</div>
                            </div>
                            {{-- Link đến trang quản lý invoices --}}
                            <a href="{{ route('staff.invoices.index') }}" class="alert-action">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <div class="alert-item warning">
                            <i class="fas fa-file-contract"></i>
                            <div class="alert-content">
                                <div class="alert-title">HĐ sắp hết hạn</div>
                                <div class="alert-value">{{ $dashboardData['urgentTasks']['expiring_leases'] ?? 0 }} hợp đồng</div>
                            </div>
                            <a href="{{ route('staff.leases.index') }}" class="alert-action">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <div class="alert-item info">
                            <i class="fas fa-calendar"></i>
                            <div class="alert-content">
                                <div class="alert-title">Lịch hẹn chờ duyệt</div>
                                <div class="alert-value">{{ $dashboardData['urgentTasks']['pending_viewings'] ?? 0 }} lịch</div>
                            </div>
                            <a href="#" class="alert-action">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                @endif
                
                {{-- Recent Activities: Chỉ hiển thị cho Manager --}}
                {{-- Hiển thị 5 audit logs gần nhất từ audit_logs table --}}
                @if($isManager && isset($dashboardData['recentActivities']))
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Hoạt động gần đây</h3>
                    </div>
                    <div class="card-content">
                        <div class="activity-list">
                            {{-- Loop qua 5 audit logs gần nhất --}}
                            @forelse ($dashboardData['recentActivities'] as $activity)
                            <div class="activity-item">
                                <div class="activity-details">
                                    <div class="activity-header">
                                        {{-- Tên người thực hiện action --}}
                                        <span class="activity-user">{{ $activity->full_name ?? 'Unknown' }}</span>
                                        {{-- Loại action (create, update, delete, ...) --}}
                                        <span class="badge badge-primary">{{ $activity->action }}</span>
                                    </div>
                                    {{-- Entity type và ID: Ví dụ "Property #123" --}}
                                    <div class="activity-action">{{ $activity->entity_type }} #{{ $activity->entity_id }}</div>
                                    {{-- Thời gian: Hiển thị dạng "2 giờ trước" --}}
                                    <div class="activity-time">{{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}</div>
                                </div>
                            </div>
                            @empty
                            {{-- Hiển thị message nếu chưa có hoạt động nào --}}
                            <p class="text-muted">Chưa có hoạt động</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Bottom Section: Detailed Analytics (Manager only) --}}
        {{-- Hiển thị phân tích chi tiết trong 30 ngày qua: Leads, Viewings, Leases, Deposits --}}
        @if($isManager && isset($dashboardData['analytics']))
        <div class="analytics-section">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Phân tích chi tiết</h3>
                    <div class="card-actions">
                        {{-- Select time range (chưa implement filter) --}}
                        <select class="form-select form-select-sm" id="analyticsTimeRange">
                            <option value="7">7 ngày</option>
                            <option value="30" selected>30 ngày</option>
                            <option value="90">90 ngày</option>
                        </select>
                    </div>
                </div>
                <div class="card-content">
                    <div class="analytics-grid">
                        {{-- Analytics Item 1: Leads mới --}}
                        {{-- Số lượng leads được tạo trong 30 ngày qua --}}
                        <div class="analytics-item">
                            <div class="analytics-icon leads">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="analytics-info">
                                <div class="analytics-label">Leads mới</div>
                                <div class="analytics-value">{{ $dashboardData['analytics']['new_leads'] ?? 0 }}</div>
                                {{-- Trend: Hardcoded (chưa tính toán thực tế) --}}
                                <div class="analytics-trend">+12% vs tháng trước</div>
                            </div>
                        </div>

                        <div class="analytics-item">
                            <div class="analytics-icon viewings">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="analytics-info">
                                <div class="analytics-label">Lượt xem phòng</div>
                                <div class="analytics-value">{{ $dashboardData['analytics']['total_viewings'] ?? 0 }}</div>
                                <div class="analytics-trend">+8% vs tháng trước</div>
                            </div>
                        </div>

                        <div class="analytics-item">
                            <div class="analytics-icon contracts">
                                <i class="fas fa-file-signature"></i>
                            </div>
                            <div class="analytics-info">
                                <div class="analytics-label">Hợp đồng ký mới</div>
                                <div class="analytics-value">{{ $dashboardData['analytics']['new_leases'] ?? 0 }}</div>
                                <div class="analytics-trend">+5% vs tháng trước</div>
                            </div>
                        </div>

                        <div class="analytics-item">
                            <div class="analytics-icon deposits">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="analytics-info">
                                <div class="analytics-label">Đặt cọc mới</div>
                                <div class="analytics-value">{{ $dashboardData['analytics']['new_deposits'] ?? 0 }}</div>
                                <div class="analytics-trend">+3% vs tháng trước</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</main>

@push('styles')
<style>
.chart-container {
    position: relative;
    height: 350px;
    min-height: 350px;
}

.chart-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    gap: 10px;
    color: #667eea;
    font-size: 14px;
    font-weight: 500;
    z-index: 10;
}

.chart-loading i {
    font-size: 18px;
    animation: spin 1s linear infinite;
}

.chart-error {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    gap: 10px;
    color: #f56565;
    font-size: 14px;
    font-weight: 500;
    padding: 20px;
    background: #fee2e2;
    border-radius: 8px;
    border: 1px solid #fca5a5;
}

.chart-error i {
    font-size: 18px;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.card-content {
    padding: 1.5rem;
}

/* Tam an toan bo thong tin lien quan hoa hong */
.stat-card-large.commission,
.agent-commission {
    display: none !important;
}
</style>
@endpush

@push('scripts')
<script>
/**
 * Function: clearDashboardCache()
 * MỤC ĐÍCH: Xóa cache dashboard và reload dữ liệu mới nhất từ database
 * 
 * LUỒNG XỬ LÝ:
 * 1. Hiển thị confirm dialog để xác nhận
 * 2. Gửi POST request đến /staff/dashboard/clear-cache
 * 3. Nếu thành công, reload trang để hiển thị dữ liệu mới
 * 4. Nếu lỗi, hiển thị thông báo lỗi
 */
function clearDashboardCache() {
    // Kiểm tra nếu có Notify library (notification system)
    if (typeof Notify !== 'undefined') {
        // Hiển thị confirm dialog với Notify
        Notify.confirm(
            'Làm mới dữ liệu',
            'Bạn có chắc chắn muốn làm mới dữ liệu dashboard? Thao tác này sẽ xóa cache và tải lại dữ liệu mới nhất.',
            function() {
                // Hiển thị loading toast → Thông báo đang xử lý
                Notify.toast('Đang làm mới dữ liệu...', 'info');
                
                // Gửi AJAX request đến API endpoint để xóa cache
                fetch('{{ route("staff.dashboard.clear-cache") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') // CSRF token để bảo mật
                    }
                })
                .then(response => response.json()) // Parse JSON response
                .then(data => {
                    if (data.success) {
                        // Nếu thành công: Hiển thị success toast → Reload trang sau 1 giây
                        Notify.toast('Dữ liệu đã được làm mới thành công!', 'success');
                        setTimeout(() => {
                            window.location.reload(); // Reload trang để hiển thị dữ liệu mới
                        }, 1000);
                    } else {
                        // Nếu thất bại: Hiển thị error toast
                        Notify.toast('Có lỗi xảy ra khi làm mới dữ liệu', 'error');
                    }
                })
                .catch(error => {
                    // Nếu có lỗi network: Log error và hiển thị error toast
                    console.error('Error:', error);
                    Notify.toast('Có lỗi xảy ra khi làm mới dữ liệu', 'error');
                });
            }
        );
    } else {
        // Fallback: Nếu không có Notify library, dùng confirm() và alert()
        if (confirm('Bạn có chắc chắn muốn làm mới dữ liệu dashboard?')) {
            // Gửi AJAX request tương tự
            fetch('{{ route("staff.dashboard.clear-cache") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Dữ liệu đã được làm mới thành công!');
                    window.location.reload(); // Reload trang
                } else {
                    alert('Có lỗi xảy ra khi làm mới dữ liệu');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi làm mới dữ liệu');
            });
        }
    }
}
</script>
@endpush
@endsection
