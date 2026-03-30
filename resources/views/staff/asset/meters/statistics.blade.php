@extends('layouts.staff_dashboard')

@section('title', 'Thống kê công tơ đo')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-chart-bar me-2"></i>Thống kê công tơ đo
                    </h1>
                    <p class="text-muted mb-0">Báo cáo tổng quan về công tơ đo trong hệ thống</p>
                </div>
                <a href="{{ route('staff.meters.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Quay lại
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng số công tơ
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $statistics['total_meters'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tachometer-alt fa-2x text-gray-300"></i>
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
                                Công tơ hoạt động
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $statistics['active_meters'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                Công tơ ngừng hoạt động
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $statistics['inactive_meters'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
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
                                Có số liệu đo
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $statistics['meters_with_readings'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Meters by Service -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Phân bố theo loại dịch vụ
                    </h6>
                </div>
                <div class="card-body">
                    @if($statistics['by_service']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Loại dịch vụ</th>
                                        <th>Số lượng</th>
                                        <th>Tỷ lệ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statistics['by_service'] as $service => $count)
                                        <tr>
                                            <td>{{ $service }}</td>
                                            <td>
                                                <span class="badge bg-primary">{{ $count }}</span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: {{ $statistics['total_meters'] > 0 ? ($count / $statistics['total_meters']) * 100 : 0 }}%"
                                                         aria-valuenow="{{ $count }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="{{ $statistics['total_meters'] }}">
                                                        {{ $statistics['total_meters'] > 0 ? round(($count / $statistics['total_meters']) * 100, 1) : 0 }}%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-chart-pie fa-2x text-muted mb-3"></i>
                            <h6 class="text-muted">Chưa có dữ liệu</h6>
                            <p class="text-muted">Không có công tơ nào được phân loại theo dịch vụ.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Meters by Property -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-building me-2"></i>Phân bố theo bất động sản
                    </h6>
                </div>
                <div class="card-body">
                    @if($statistics['by_property']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Bất động sản</th>
                                        <th>Số lượng</th>
                                        <th>Tỷ lệ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statistics['by_property'] as $property => $count)
                                        <tr>
                                            <td>{{ $property }}</td>
                                            <td>
                                                <span class="badge bg-success">{{ $count }}</span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: {{ $statistics['total_meters'] > 0 ? ($count / $statistics['total_meters']) * 100 : 0 }}%"
                                                         aria-valuenow="{{ $count }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="{{ $statistics['total_meters'] }}">
                                                        {{ $statistics['total_meters'] > 0 ? round(($count / $statistics['total_meters']) * 100, 1) : 0 }}%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-building fa-2x text-muted mb-3"></i>
                            <h6 class="text-muted">Chưa có dữ liệu</h6>
                            <p class="text-muted">Không có công tơ nào được phân loại theo bất động sản.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Information -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle me-2"></i>Tóm tắt thống kê
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Tình trạng hoạt động:</h6>
                            <ul class="list-unstyled">
                                <li>
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Công tơ hoạt động: <strong>{{ $statistics['active_meters'] }}</strong> 
                                    ({{ $statistics['total_meters'] > 0 ? round(($statistics['active_meters'] / $statistics['total_meters']) * 100, 1) : 0 }}%)
                                </li>
                                <li>
                                    <i class="fas fa-times-circle text-danger me-2"></i>
                                    Công tơ ngừng hoạt động: <strong>{{ $statistics['inactive_meters'] }}</strong> 
                                    ({{ $statistics['total_meters'] > 0 ? round(($statistics['inactive_meters'] / $statistics['total_meters']) * 100, 1) : 0 }}%)
                                </li>
                            </ul>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary">Tình trạng đo số liệu:</h6>
                            <ul class="list-unstyled">
                                <li>
                                    <i class="fas fa-chart-line text-info me-2"></i>
                                    Có số liệu đo: <strong>{{ $statistics['meters_with_readings'] }}</strong> 
                                    ({{ $statistics['total_meters'] > 0 ? round(($statistics['meters_with_readings'] / $statistics['total_meters']) * 100, 1) : 0 }}%)
                                </li>
                                <li>
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    Chưa có số liệu: <strong>{{ $statistics['meters_without_readings'] }}</strong> 
                                    ({{ $statistics['total_meters'] > 0 ? round(($statistics['meters_without_readings'] / $statistics['total_meters']) * 100, 1) : 0 }}%)
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    @if($statistics['meters_without_readings'] > 0)
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Chú ý:</strong> Có {{ $statistics['meters_without_readings'] }} công tơ chưa có số liệu đo. 
                            Hãy kiểm tra và thêm số liệu đo cho các công tơ này.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.text-xs {
    font-size: 0.7rem;
}

.font-weight-bold {
    font-weight: 700 !important;
}

.text-uppercase {
    text-transform: uppercase !important;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

.text-gray-300 {
    color: #dddfeb !important;
}
</style>
@endpush
