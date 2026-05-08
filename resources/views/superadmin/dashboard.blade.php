@extends('layouts.superadmin')

@section('title', 'Super Admin Dashboard - SaaS Platform')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active d-inline-flex align-items-center gap-1" aria-current="page">
            <i class="fas fa-tachometer-alt opacity-75" aria-hidden="true"></i>
            <span>Dashboard</span>
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid superadmin-dashboard-page">
    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3 pb-3 superadmin-dashboard-head">
        <div class="flex-grow-1 min-w-0">
            <h1 class="h3 mb-1 fw-semibold text-gray-800 superadmin-dashboard-title d-flex align-items-center flex-wrap gap-2">
                <i class="fas fa-crown text-warning flex-shrink-0" aria-hidden="true"></i>
                <span>Super Admin Dashboard</span>
            </h1>
            <p class="text-muted mb-0 small">Tổng quan toàn bộ hệ thống SaaS Platform</p>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <button type="button" onclick="clearSuperAdminCache()" class="btn btn-outline-primary rounded-pill px-3 fw-semibold">
                <i class="fas fa-sync-alt me-1"></i>
                Làm mới dữ liệu
            </button>
        </div>
    </div>

    <!-- Primary SaaS Metrics -->
    <div class="row mb-3 g-3">
        <div class="col-xl-3 col-md-6 mb-0">
            <div class="card superadmin-stat-card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row g-0 align-items-center">
                        <div class="col me-2 min-w-0">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Tổ chức
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                {{ $dashboardData['totalOrganizations'] ?? 0 }}
                            </div>
                            <div class="text-xs text-success">
                                <i class="fas fa-arrow-up"></i> +{{ $dashboardData['newOrganizationsThisMonth'] ?? 0 }} tháng này
                            </div>
                        </div>
                        <div class="col-auto ps-1">
                            <div class="superadmin-stat-card__icon" aria-hidden="true"><i class="fas fa-building fa-fw"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-0">
            <div class="card superadmin-stat-card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row g-0 align-items-center">
                        <div class="col me-2 min-w-0">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                Người dùng
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                {{ $dashboardData['totalUsers'] ?? 0 }}
                            </div>
                            <div class="text-xs text-success">
                                <i class="fas fa-arrow-up"></i> +{{ $dashboardData['newUsersThisMonth'] ?? 0 }} tháng này
                            </div>
                        </div>
                        <div class="col-auto ps-1">
                            <div class="superadmin-stat-card__icon" aria-hidden="true"><i class="fas fa-users fa-fw"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-0">
            <div class="card superadmin-stat-card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row g-0 align-items-center">
                        <div class="col me-2 min-w-0">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                Subscriptions
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                {{ number_format($dashboardData['activeSubscriptions'] ?? 0) }}
                            </div>
                            <div class="text-xs text-success">
                                <i class="fas fa-check-circle"></i> {{ number_format($dashboardData['totalSubscriptions'] ?? 0) }} tổng số
                            </div>
                        </div>
                        <div class="col-auto ps-1">
                            <div class="superadmin-stat-card__icon" aria-hidden="true"><i class="fas fa-credit-card fa-fw"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-0">
            <div class="card superadmin-stat-card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row g-0 align-items-center">
                        <div class="col me-2 min-w-0">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                Trial Subscriptions
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                {{ number_format($dashboardData['trialSubscriptions'] ?? 0) }}
                            </div>
                            <div class="text-xs text-muted">
                                Đang dùng thử
                            </div>
                        </div>
                        <div class="col-auto ps-1">
                            <div class="superadmin-stat-card__icon" aria-hidden="true"><i class="fas fa-clock fa-fw"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary SaaS Metrics -->
    <div class="row mb-3 g-3">
        <div class="col-xl-3 col-md-6 mb-0">
            <div class="card superadmin-stat-card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row g-0 align-items-center">
                        <div class="col me-2 min-w-0">
                            <div class="text-xs fw-bold text-secondary text-uppercase mb-1">
                                Subscription Plans
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                {{ number_format($dashboardData['activeSubscriptionPlans'] ?? 0) }}
                            </div>
                            <div class="text-xs text-success">
                                <i class="fas fa-check-circle"></i> {{ number_format($dashboardData['totalSubscriptionPlans'] ?? 0) }} tổng số
                            </div>
                        </div>
                        <div class="col-auto ps-1">
                            <div class="superadmin-stat-card__icon" aria-hidden="true"><i class="fas fa-list-alt fa-fw"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-0">
            <div class="card superadmin-stat-card border-left-dark shadow h-100 py-2">
                <div class="card-body">
                    <div class="row g-0 align-items-center">
                        <div class="col me-2 min-w-0">
                            <div class="text-xs fw-bold text-dark text-uppercase mb-1">
                                Subscription Invoices
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                {{ number_format($dashboardData['totalSubscriptionInvoices'] ?? 0) }}
                            </div>
                            <div class="text-xs text-success">
                                <i class="fas fa-check-circle"></i> {{ number_format($dashboardData['paidSubscriptionInvoices'] ?? 0) }} đã thanh toán
                            </div>
                        </div>
                        <div class="col-auto ps-1">
                            <div class="superadmin-stat-card__icon" aria-hidden="true"><i class="fas fa-file-invoice-dollar fa-fw"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-0">
            <div class="card superadmin-stat-card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row g-0 align-items-center">
                        <div class="col me-2 min-w-0">
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">
                                Pending Invoices
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                {{ number_format($dashboardData['pendingSubscriptionInvoices'] ?? 0) }}
                            </div>
                            <div class="text-xs text-warning">
                                <i class="fas fa-exclamation-circle"></i> Chờ thanh toán
                            </div>
                        </div>
                        <div class="col-auto ps-1">
                            <div class="superadmin-stat-card__icon" aria-hidden="true"><i class="fas fa-hourglass-half fa-fw"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-0">
            <div class="card superadmin-stat-card border-left-light shadow h-100 py-2">
                <div class="card-body">
                    <div class="row g-0 align-items-center">
                        <div class="col me-2 min-w-0">
                            <div class="text-xs fw-bold text-secondary text-uppercase mb-1">
                                Tổ chức hoạt động
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                {{ number_format($dashboardData['activeOrganizations'] ?? 0) }}
                            </div>
                            <div class="text-xs text-muted">
                                / {{ number_format($dashboardData['totalOrganizations'] ?? 0) }} tổng số
                            </div>
                        </div>
                        <div class="col-auto ps-1">
                            <div class="superadmin-stat-card__icon" aria-hidden="true"><i class="fas fa-check-circle fa-fw"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-3 g-3">
        <!-- MRR Growth Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card superadmin-panel-card shadow-sm mb-0 h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0">Tăng trưởng hệ thống</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="systemGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Organizations Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card superadmin-panel-card shadow-sm mb-0 h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0">Phân bố tổ chức</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="organizationsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional SaaS Charts -->
    <div class="row mb-3 g-3">
        <!-- Subscription Growth Chart -->
        <div class="col-xl-6 col-lg-6">
            <div class="card superadmin-panel-card shadow-sm mb-0 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0">Tăng trưởng Subscriptions</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="subscriptionGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Growth Chart -->
        <div class="col-xl-6 col-lg-6">
            <div class="card superadmin-panel-card shadow-sm mb-0 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0">User Growth & Retention</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Health & Performance -->
    <div class="row mb-3 g-3">
        <!-- System Health -->
        <div class="col-lg-4 mb-0">
            <div class="card superadmin-panel-card shadow-sm mb-0 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0">System Health</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-xs">Server Status</span>
                            <span class="badge rounded-pill text-bg-success">Online</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-xs">Database</span>
                            <span class="badge rounded-pill text-bg-success">Healthy</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-xs">API Response Time</span>
                            <span class="text-xs text-success">{{ $dashboardData['apiResponseTime'] ?? 0 }}ms</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-xs">Uptime</span>
                            <span class="text-xs text-muted">{{ $dashboardData['systemUptime'] ?? '99.9%' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="col-lg-4 mb-0">
            <div class="card superadmin-panel-card shadow-sm mb-0 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0">Performance</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-xs">Active Sessions</span>
                            <span class="text-xs text-primary">{{ $dashboardData['activeSessions'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-xs">Page Load Time</span>
                            <span class="text-xs text-success">{{ $dashboardData['pageLoadTime'] ?? 0 }}ms</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-xs">Memory Usage</span>
                            <span class="text-xs text-warning">{{ $dashboardData['memoryUsage'] ?? 0 }}%</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-xs">CPU Usage</span>
                            <span class="text-xs text-info">{{ $dashboardData['cpuUsage'] ?? 0 }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Metrics -->
        <div class="col-lg-4 mb-0">
            <div class="card superadmin-panel-card shadow-sm mb-0 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0">Business Health</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-xs">Conversion Rate</span>
                            <span class="text-xs text-success">{{ number_format($dashboardData['conversionRate'] ?? 0, 1) }}%</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-xs">Support Tickets</span>
                            <span class="text-xs text-warning">{{ $dashboardData['openSupportTickets'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-xs">Feature Requests</span>
                            <span class="text-xs text-info">{{ $dashboardData['featureRequests'] ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-xs">Customer Satisfaction</span>
                            <span class="text-xs text-success">{{ number_format($dashboardData['customerSatisfaction'] ?? 0, 1) }}/5</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities & Top Organizations -->
    <div class="row mb-2 g-3">
        <!-- Recent Activities -->
        <div class="col-lg-6 mb-0">
            <div class="card superadmin-panel-card shadow-sm mb-0 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0">Hoạt động gần đây</h6>
                </div>
                <div class="card-body">
                    @forelse($dashboardData['recentActivities'] ?? [] as $activity)
                    <div class="d-flex align-items-center mb-3 gap-3">
                        <div class="flex-shrink-0">
                            <div class="icon-circle bg-primary">
                                <i class="fas fa-{{ $activity->action_type === 'created' ? 'plus' : ($activity->action_type === 'updated' ? 'edit' : 'trash') }} text-white"></i>
                            </div>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="small text-gray-500">{{ $activity->created_at->diffForHumans() }}</div>
                            <div class="text-xs">{{ $activity->description ?? 'Hoạt động hệ thống' }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-gray-300 mb-2"></i>
                        <p class="text-muted">Không có hoạt động nào</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Top Organizations -->
        <div class="col-lg-6 mb-0">
            <div class="card superadmin-panel-card shadow-sm mb-0 h-100">
                <div class="card-header py-3">
                    <h6 class="m-0">Tổ chức hàng đầu</h6>
                </div>
                <div class="card-body">
                    @forelse($dashboardData['topOrganizations'] ?? [] as $org)
                    <div class="d-flex align-items-center mb-3 gap-3">
                        <div class="flex-shrink-0">
                            <div class="icon-circle bg-success">
                                <span class="text-white font-weight-bold">{{ strtoupper(substr($org->name, 0, 1)) }}</span>
                            </div>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="text-xs font-weight-bold text-gray-800">{{ $org->name }}</div>
                            <div class="text-xs text-gray-500">
                                <span class="badge rounded-pill text-bg-primary">{{ $org->users_count ?? 0 }} users</span>
                                <span class="badge rounded-pill text-bg-success">{{ $org->properties_count ?? 0 }} properties</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="fas fa-building fa-2x text-gray-300 mb-2"></i>
                        <p class="text-muted">Không có tổ chức nào</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Tiêu đề dashboard: tách khối, đồng bộ glass theme */
.glass-ui-dashboard .superadmin-dashboard-head {
    border-bottom: 1px solid rgba(163, 211, 255, 0.42);
}
.glass-ui-dashboard .superadmin-dashboard-title {
    font-family: 'Space Grotesk', 'Plus Jakarta Sans', sans-serif;
    letter-spacing: -0.02em;
    color: var(--glass-text, #0f2942) !important;
}

/* Vạch màu KPI: dashboard-glass-ui (.superadmin-stat-card) */

.icon-circle {
    height: 2.5rem;
    width: 2.5rem;
    border-radius: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.chart-area {
    position: relative;
    height: 13rem;
    width: 100%;
}

.chart-pie {
    position: relative;
    height: 15rem;
    width: 100%;
}

@media (max-width: 768px) {
    .chart-area {
        height: 9rem;
    }
    
    .chart-pie {
        height: 12rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    // System Growth Chart - Organizations and Users
    const systemGrowthCtx = document.getElementById('systemGrowthChart');
    if (systemGrowthCtx) {
        @php
            $systemGrowthData = $dashboardData['systemGrowthChartData'] ?? ['labels' => [], 'organizations' => [], 'users' => []];
        @endphp
        const systemGrowthData = @json($systemGrowthData);
        
        new Chart(systemGrowthCtx, {
            type: 'line',
            data: {
                labels: systemGrowthData.labels.length > 0 ? systemGrowthData.labels : ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6'],
                datasets: [{
                    label: 'Tổ chức',
                    data: systemGrowthData.organizations.length > 0 ? systemGrowthData.organizations : [0, 0, 0, 0, 0, 0],
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                }, {
                    label: 'Người dùng',
                    data: systemGrowthData.users.length > 0 ? systemGrowthData.users : [0, 0, 0, 0, 0, 0],
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    tension: 0.4,
                    fill: false,
                    yAxisID: 'y'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN');
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Organizations Chart
    const orgCtx = document.getElementById('organizationsChart');
    if (orgCtx) {
        new Chart(orgCtx, {
            type: 'doughnut',
            data: {
                labels: ['Hoạt động', 'Tạm dừng', 'Mới tạo'],
                datasets: [{
                    data: [{{ $dashboardData['activeOrganizations'] ?? 0 }}, {{ $dashboardData['inactiveOrganizations'] ?? 0 }}, {{ $dashboardData['newOrganizations'] ?? 0 }}],
                    backgroundColor: ['#1cc88a', '#e74a3b', '#f6c23e']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Subscription Growth Chart
    const subscriptionGrowthCtx = document.getElementById('subscriptionGrowthChart');
    if (subscriptionGrowthCtx) {
        @php
            $subscriptionGrowthData = $dashboardData['subscriptionGrowthChartData'] ?? ['labels' => [], 'active' => [], 'trial' => []];
        @endphp
        const subscriptionGrowthData = @json($subscriptionGrowthData);
        
        new Chart(subscriptionGrowthCtx, {
            type: 'bar',
            data: {
                labels: subscriptionGrowthData.labels.length > 0 ? subscriptionGrowthData.labels : ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6'],
                datasets: [{
                    label: 'Active Subscriptions',
                    data: subscriptionGrowthData.active.length > 0 ? subscriptionGrowthData.active : [0, 0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(28, 200, 138, 0.8)',
                    borderColor: '#1cc88a',
                    borderWidth: 1
                }, {
                    label: 'Trial Subscriptions',
                    data: subscriptionGrowthData.trial.length > 0 ? subscriptionGrowthData.trial : [0, 0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(246, 194, 62, 0.8)',
                    borderColor: '#f6c23e',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN');
                            }
                        }
                    }
                }
            }
        });
    }

    // User Growth Chart - Use data from controller
    const userGrowthCtx = document.getElementById('userGrowthChart');
    if (userGrowthCtx) {
        @php
            $userGrowthData = $dashboardData['userGrowthChartData'] ?? ['newUsers' => [], 'retainedUsers' => []];
            $userGrowthLabels = $dashboardData['mrrGrowthChartData']['labels'] ?? ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6'];
        @endphp
        const userGrowthChartData = @json($userGrowthData);
        const userGrowthLabels = @json($userGrowthLabels);
        
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: userGrowthLabels,
                datasets: [{
                    label: 'New Users',
                    data: userGrowthChartData.newUsers.length > 0 ? userGrowthChartData.newUsers : [0, 0, 0, 0, 0, 0],
                    borderColor: '#36b9cc',
                    backgroundColor: 'rgba(54, 185, 204, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Retained Users',
                    data: userGrowthChartData.retainedUsers.length > 0 ? userGrowthChartData.retainedUsers : [0, 0, 0, 0, 0, 0],
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush