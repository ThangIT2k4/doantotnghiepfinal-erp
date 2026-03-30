@extends('layouts.staff_dashboard')

@section('title', 'Thống kê khách hàng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Thống kê khách hàng',
            'subtitle' => 'Phân tích hiệu suất và xu hướng khách hàng',
            'icon' => 'fas fa-chart-bar',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.tenants.index')
                ]
            ]
        ])

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Tổng khách hàng</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_tenants'] ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Khách hàng hoạt động</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active_tenants'] ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Có hợp đồng</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['tenants_with_leases'] ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-contract fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Mới trong tháng</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['new_tenants_this_month'] ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Tenants by Month Chart -->
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>Xu hướng khách hàng (12 tháng gần nhất)
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="tenantsByMonthChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics -->
        <div class="row">
            <!-- Tenants with/without Leases -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-pie-chart me-2"></i>Phân bố hợp đồng
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="leasesChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Summary Table -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Tóm tắt thống kê
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Chỉ số</th>
                                        <th>Giá trị</th>
                                        <th>Tỷ lệ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Tổng khách hàng</strong></td>
                                        <td>{{ $stats['total_tenants'] ?? 0 }}</td>
                                        <td>100%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Khách hàng hoạt động</strong></td>
                                        <td>{{ $stats['active_tenants'] ?? 0 }}</td>
                                        <td>{{ $stats['total_tenants'] > 0 ? round((($stats['active_tenants'] ?? 0) / $stats['total_tenants']) * 100, 1) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Có hợp đồng</strong></td>
                                        <td>{{ $stats['tenants_with_leases'] ?? 0 }}</td>
                                        <td>{{ $stats['total_tenants'] > 0 ? round((($stats['tenants_with_leases'] ?? 0) / $stats['total_tenants']) * 100, 1) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Mới trong tháng</strong></td>
                                        <td>{{ $stats['new_tenants_this_month'] ?? 0 }}</td>
                                        <td>{{ $stats['total_tenants'] > 0 ? round((($stats['new_tenants_this_month'] ?? 0) / $stats['total_tenants']) * 100, 1) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Có hợp đồng hoạt động</strong></td>
                                        <td>{{ $tenantsWithLeases ?? 0 }}</td>
                                        <td>{{ $stats['total_tenants'] > 0 ? round((($tenantsWithLeases ?? 0) / $stats['total_tenants']) * 100, 1) : 0 }}%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Chưa có hợp đồng</strong></td>
                                        <td>{{ $tenantsWithoutLeases ?? 0 }}</td>
                                        <td>{{ $stats['total_tenants'] > 0 ? round((($tenantsWithoutLeases ?? 0) / $stats['total_tenants']) * 100, 1) : 0 }}%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tenants by Month Chart
    const tenantsByMonthCtx = document.getElementById('tenantsByMonthChart');
    if (tenantsByMonthCtx) {
        const tenantsByMonthData = @json($tenantsByMonth);
        const monthLabels = tenantsByMonthData.map(item => {
            const [year, month] = item.month.split('-');
            return new Date(year, month - 1).toLocaleDateString('vi-VN', { month: 'short', year: 'numeric' });
        });
        const monthValues = tenantsByMonthData.map(item => item.count);
        
        new Chart(tenantsByMonthCtx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Số khách hàng mới',
                    data: monthValues,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    }

    // Leases Distribution Chart
    const leasesCtx = document.getElementById('leasesChart');
    if (leasesCtx) {
        const tenantsWithLeases = {{ $tenantsWithLeases ?? 0 }};
        const tenantsWithoutLeases = {{ $tenantsWithoutLeases ?? 0 }};
        
        new Chart(leasesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Có hợp đồng hoạt động', 'Chưa có hợp đồng'],
                datasets: [{
                    data: [tenantsWithLeases, tenantsWithoutLeases],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(108, 117, 125, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(108, 117, 125, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>
@endpush

@push('styles')
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
}

canvas {
    max-height: 300px;
}
</style>
@endpush

