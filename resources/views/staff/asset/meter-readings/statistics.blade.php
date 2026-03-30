@extends('layouts.staff_dashboard')

@section('title', 'Thống kê số liệu đo')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-chart-bar me-2"></i>Thống kê số liệu đo
                    </h1>
                    <p class="text-muted mb-0">Báo cáo tổng quan về số liệu đo trong hệ thống</p>
                </div>
                <a href="{{ route('staff.meter-readings.index') }}" class="btn btn-outline-secondary">
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
                                Tổng số lần đo
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $statistics['total_readings'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                                Đo trong tháng này
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $statistics['readings_this_month'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
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
                                Đo tháng trước
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $statistics['readings_last_month'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
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
                                Tăng trưởng
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                @if($statistics['readings_last_month'] > 0)
                                    {{ round((($statistics['readings_this_month'] - $statistics['readings_last_month']) / $statistics['readings_last_month']) * 100, 1) }}%
                                @else
                                    N/A
                                @endif
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
        <!-- Readings by Service -->
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
                                        <th>Số lần đo</th>
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
                                                         style="width: {{ $statistics['total_readings'] > 0 ? ($count / $statistics['total_readings']) * 100 : 0 }}%"
                                                         aria-valuenow="{{ $count }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="{{ $statistics['total_readings'] }}">
                                                        {{ $statistics['total_readings'] > 0 ? round(($count / $statistics['total_readings']) * 100, 1) : 0 }}%
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
                            <p class="text-muted">Không có số liệu đo nào được phân loại theo dịch vụ.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Readings by Property -->
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
                                        <th>Số lần đo</th>
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
                                                         style="width: {{ $statistics['total_readings'] > 0 ? ($count / $statistics['total_readings']) * 100 : 0 }}%"
                                                         aria-valuenow="{{ $count }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="{{ $statistics['total_readings'] }}">
                                                        {{ $statistics['total_readings'] > 0 ? round(($count / $statistics['total_readings']) * 100, 1) : 0 }}%
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
                            <p class="text-muted">Không có số liệu đo nào được phân loại theo bất động sản.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Readings by User -->
    @if($statistics['by_taken_by']->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-users me-2"></i>Phân bố theo người đo
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Người đo</th>
                                    <th>Số lần đo</th>
                                    <th>Tỷ lệ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statistics['by_taken_by'] as $user => $count)
                                    <tr>
                                        <td>{{ $user }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ $count }}</span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-info" role="progressbar" 
                                                     style="width: {{ $statistics['total_readings'] > 0 ? ($count / $statistics['total_readings']) * 100 : 0 }}%"
                                                     aria-valuenow="{{ $count }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="{{ $statistics['total_readings'] }}">
                                                    {{ $statistics['total_readings'] > 0 ? round(($count / $statistics['total_readings']) * 100, 1) : 0 }}%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Summary Information -->
    <div class="row mt-4">
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
                            <h6 class="text-primary">Hoạt động đo:</h6>
                            <ul class="list-unstyled">
                                <li>
                                    <i class="fas fa-calendar-check text-success me-2"></i>
                                    Tháng này: <strong>{{ $statistics['readings_this_month'] }}</strong> lần đo
                                </li>
                                <li>
                                    <i class="fas fa-calendar text-info me-2"></i>
                                    Tháng trước: <strong>{{ $statistics['readings_last_month'] }}</strong> lần đo
                                </li>
                                <li>
                                    <i class="fas fa-chart-line text-warning me-2"></i>
                                    Tăng trưởng: 
                                    @if($statistics['readings_last_month'] > 0)
                                        <strong>{{ round((($statistics['readings_this_month'] - $statistics['readings_last_month']) / $statistics['readings_last_month']) * 100, 1) }}%</strong>
                                    @else
                                        <strong>N/A</strong>
                                    @endif
                                </li>
                            </ul>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary">Phân bố hoạt động:</h6>
                            <ul class="list-unstyled">
                                <li>
                                    <i class="fas fa-tachometer-alt text-primary me-2"></i>
                                    Tổng số lần đo: <strong>{{ $statistics['total_readings'] }}</strong>
                                </li>
                                <li>
                                    <i class="fas fa-users text-info me-2"></i>
                                    Số người đo: <strong>{{ $statistics['by_taken_by']->count() }}</strong>
                                </li>
                                <li>
                                    <i class="fas fa-building text-success me-2"></i>
                                    Số bất động sản: <strong>{{ $statistics['by_property']->count() }}</strong>
                                </li>
                            </ul>
                        </div>
                    </div>
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

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
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
