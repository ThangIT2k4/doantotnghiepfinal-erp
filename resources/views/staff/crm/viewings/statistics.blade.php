@extends('layouts.staff_dashboard')

@section('title', 'Thống kê lịch hẹn')

@php
function getStatusBadgeClass($status) {
    switch($status) {
        case 'requested':
            return 'bg-warning text-dark';
        case 'confirmed':
            return 'bg-info text-white';
        case 'done':
        case 'completed':
            return 'bg-success text-white';
        case 'no_show':
            return 'bg-danger text-white';
        case 'cancelled':
            return 'bg-secondary text-white';
        default:
            return 'bg-light text-dark';
    }
}

function getStatusText($status) {
    switch($status) {
        case 'requested':
            return 'Chờ xác nhận';
        case 'confirmed':
            return 'Đã xác nhận';
        case 'done':
        case 'completed':
            return 'Hoàn thành';
        case 'no_show':
            return 'Không đến';
        case 'cancelled':
            return 'Đã hủy';
        default:
            return 'Không xác định';
    }
}
@endphp

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-chart-bar me-2"></i>Thống kê lịch hẹn
                        </h1>
                        <p class="text-muted mb-0">Phân tích hiệu suất và xu hướng lịch hẹn</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('staff.viewings.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-1"></i>Danh sách
                        </a>
                        <a href="{{ route('staff.viewings.calendar') }}" class="btn btn-outline-info">
                            <i class="fas fa-calendar me-1"></i>Lịch
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="GET" action="{{ route('staff.viewings.statistics') }}" class="row g-3">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Từ ngày</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="{{ $startDate }}">
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">Đến ngày</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="{{ $endDate }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Cập nhật
                                    </button>
                                    <a href="{{ route('staff.viewings.statistics') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0">{{ $totalViewings }}</h4>
                                <p class="mb-0">Tổng lịch hẹn</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-calendar-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0">{{ $statusCounts->get('done', 0) }}</h4>
                                <p class="mb-0">Hoàn thành</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0">{{ $statusCounts->get('requested', 0) }}</h4>
                                <p class="mb-0">Chờ xác nhận</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0">{{ $statusCounts->get('confirmed', 0) }}</h4>
                                <p class="mb-0">Đã xác nhận</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-calendar-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Status Distribution -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-pie-chart me-2"></i>Phân bố trạng thái
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Agent Performance -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-tie me-2"></i>Hiệu suất Agent
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="agentChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Property Performance -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-building me-2"></i>Hiệu suất Bất động sản
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="propertyChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics -->
        <div class="row">
            <!-- Status Breakdown -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Chi tiết trạng thái
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Trạng thái</th>
                                        <th>Số lượng</th>
                                        <th>Tỷ lệ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statusCounts as $status => $count)
                                        <tr>
                                            <td>
                                                <span class="badge {{ getStatusBadgeClass($status) }}">
                                                    {{ getStatusText($status) }}
                                                </span>
                                            </td>
                                            <td>{{ $count }}</td>
                                            <td>{{ $totalViewings > 0 ? round(($count / $totalViewings) * 100, 1) : 0 }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Agents -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-trophy me-2"></i>Top Agent
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Agent</th>
                                        <th>Số lịch hẹn</th>
                                        <th>Tỷ lệ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($agentCounts as $agentId => $count)
                                        @if($agents->has($agentId))
                                            <tr>
                                                <td>{{ $agents->get($agentId)->full_name }}</td>
                                                <td>{{ $count }}</td>
                                                <td>{{ $totalViewings > 0 ? round(($count / $totalViewings) * 100, 1) : 0 }}%</td>
                                            </tr>
                                        @endif
                                    @endforeach
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
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusData = @json($statusCounts);
    const statusLabels = Object.keys(statusData).map(status => getStatusText(status));
    const statusValues = Object.values(statusData);
    const statusColors = Object.keys(statusData).map(status => getStatusColor(status));
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusValues,
                backgroundColor: statusColors,
                borderWidth: 2,
                borderColor: '#fff'
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

    // Agent Chart
    const agentCtx = document.getElementById('agentChart').getContext('2d');
    const agentData = @json($agentCounts);
    const agents = @json($agents);
    
    // console.log('Agent data:', agentData);
    // console.log('Agents:', agents);
    // console.log('Agents type:', typeof agents);
    // console.log('Agents is array:', Array.isArray(agents));
    
    // Ensure agents is an array
    const agentsArray = Array.isArray(agents) ? agents : Object.values(agents || {});
    
    const agentLabels = Object.keys(agentData).map(id => {
        const agent = agentsArray.find(a => a.id == id);
        return agent ? agent.full_name : 'Unknown';
    });
    const agentValues = Object.values(agentData);
    
    // console.log('Agent labels:', agentLabels);
    // console.log('Agent values:', agentValues);
    
    // Only create chart if we have data
    if (agentLabels.length > 0 && agentValues.length > 0) {
        new Chart(agentCtx, {
            type: 'bar',
            data: {
                labels: agentLabels,
                datasets: [{
                    label: 'Số lịch hẹn',
                    data: agentValues,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    } else {
        // Show "No data" message
        agentCtx.fillStyle = '#666';
        agentCtx.font = '16px Arial';
        agentCtx.textAlign = 'center';
        agentCtx.fillText('Không có dữ liệu', agentCtx.canvas.width / 2, agentCtx.canvas.height / 2);
    }

    // Property Chart
    const propertyCtx = document.getElementById('propertyChart').getContext('2d');
    const propertyData = @json($propertyCounts);
    const properties = @json($properties);
    
    // console.log('Property data:', propertyData);
    // console.log('Properties:', properties);
    // console.log('Properties type:', typeof properties);
    // console.log('Properties is array:', Array.isArray(properties));
    
    // Ensure properties is an array
    const propertiesArray = Array.isArray(properties) ? properties : Object.values(properties || {});
    
    const propertyLabels = Object.keys(propertyData).map(id => {
        const property = propertiesArray.find(p => p.id == id);
        return property ? property.name : 'Unknown';
    });
    const propertyValues = Object.values(propertyData);
    
    // console.log('Property labels:', propertyLabels);
    // console.log('Property values:', propertyValues);
    
    // Only create chart if we have data
    if (propertyLabels.length > 0 && propertyValues.length > 0) {
        new Chart(propertyCtx, {
            type: 'bar',
            data: {
                labels: propertyLabels,
                datasets: [{
                    label: 'Số lịch hẹn',
                    data: propertyValues,
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    } else {
        // Show "No data" message
        propertyCtx.fillStyle = '#666';
        propertyCtx.font = '16px Arial';
        propertyCtx.textAlign = 'center';
        propertyCtx.fillText('Không có dữ liệu', propertyCtx.canvas.width / 2, propertyCtx.canvas.height / 2);
    }
});

function getStatusText(status) {
    const statusTexts = {
        'requested': 'Chờ xác nhận',
        'confirmed': 'Đã xác nhận',
        'done': 'Hoàn thành',
        'no_show': 'Không đến',
        'cancelled': 'Đã hủy'
    };
    return statusTexts[status] || 'Không xác định';
}

function getStatusBadgeClass(status) {
    const statusClasses = {
        'requested': 'bg-warning',
        'confirmed': 'bg-info',
        'done': 'bg-success',
        'no_show': 'bg-danger',
        'cancelled': 'bg-secondary'
    };
    return statusClasses[status] || 'bg-secondary';
}

function getStatusColor(status) {
    const statusColors = {
        'requested': '#ffc107',
        'confirmed': '#17a2b8',
        'done': '#28a745',
        'no_show': '#dc3545',
        'cancelled': '#6c757d'
    };
    return statusColors[status] || '#6c757d';
}
</script>
@endpush

@push('styles')
<style>
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

.table td {
    vertical-align: middle;
}

.badge {
    font-size: 0.75rem;
}

canvas {
    max-height: 300px;
}
</style>
@endpush
