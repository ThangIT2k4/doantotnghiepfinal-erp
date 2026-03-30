@extends('layouts.staff_dashboard')

@section('title', 'Quản lý SePay')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-credit-card mr-2"></i>
                Quản lý SePay
            </h1>
            <p class="text-muted mb-0">Dashboard quản lý giao dịch thanh toán SePay</p>
        </div>
        <div class="card-tools">
            <a href="{{ route('staff.sepay.transactions') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-list mr-1"></i>
                Xem tất cả giao dịch
            </a>
            <a href="{{ route('staff.sepay.settings') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-cog mr-1"></i>
                Cài đặt
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <!-- Today -->
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Hôm nay</h6>
                            <h3 class="mb-0">{{ number_format($stats['today']['transactions']) }}</h3>
                            <small>{{ number_format($stats['today']['amount']) }}đ</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-day fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small>Tỷ lệ thành công: {{ $stats['today']['success_rate'] }}%</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yesterday -->
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Hôm qua</h6>
                            <h3 class="mb-0">{{ number_format($stats['yesterday']['transactions']) }}</h3>
                            <small>{{ number_format($stats['yesterday']['amount']) }}đ</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-minus fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small>Tỷ lệ thành công: {{ $stats['yesterday']['success_rate'] }}%</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Tháng này</h6>
                            <h3 class="mb-0">{{ number_format($stats['this_month']['transactions']) }}</h3>
                            <small>{{ number_format($stats['this_month']['amount']) }}đ</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-alt fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small>Tỷ lệ thành công: {{ $stats['this_month']['success_rate'] }}%</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total -->
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Tổng cộng</h6>
                            <h3 class="mb-0">{{ number_format($stats['total']['transactions']) }}</h3>
                            <small>{{ number_format($stats['total']['amount']) }}đ</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small>Đang chờ: {{ $stats['total']['pending'] }} | Thất bại: {{ $stats['total']['failed'] }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart and Recent Transactions -->
    <div class="row">
        <!-- Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-chart-area mr-2"></i>
                        Biểu đồ giao dịch 30 ngày qua
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="transactionChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-clock mr-2"></i>
                        Giao dịch gần đây
                    </h5>
                </div>
                <div class="card-body p-0">
                    @forelse($recentTransactions as $transaction)
                        <div class="border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>#{{ $transaction->sepay_transaction_id }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $transaction->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="text-right">
                                    <span class="badge bg-{{ $transaction->status == 'processed' ? 'success' : ($transaction->status == 'failed' ? 'danger' : 'warning') }}">
                                        {{ $transaction->status == 'processed' ? 'Thành công' : ($transaction->status == 'failed' ? 'Thất bại' : 'Đang chờ') }}
                                    </span>
                                    <br>
                                    <strong class="text-success">{{ number_format($transaction->amount) }}đ</strong>
                                </div>
                            </div>
                            @if($transaction->invoice)
                                <div class="mt-2">
                                    <small class="text-primary">
                                        <i class="fas fa-file-invoice mr-1"></i>
                                        {{ $transaction->invoice->invoice_no }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-3 text-center text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>Chưa có giao dịch nào</p>
                        </div>
                    @endforelse
                </div>
                <div class="card-footer">
                    <a href="{{ route('staff.sepay.transactions') }}" class="btn btn-sm btn-outline-primary btn-block">
                        Xem tất cả giao dịch
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-bolt mr-2"></i>
                        Thao tác nhanh
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="{{ route('staff.sepay.transactions', ['status' => 'failed']) }}" class="btn btn-outline-danger btn-block">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                Giao dịch thất bại
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('staff.sepay.transactions', ['status' => 'pending']) }}" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-clock mr-2"></i>
                                Giao dịch đang chờ
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('staff.sepay.export') }}" class="btn btn-outline-info btn-block">
                                <i class="fas fa-download mr-2"></i>
                                Xuất báo cáo
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('staff.sepay.settings') }}" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-cog mr-2"></i>
                                Cài đặt SePay
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart data
const chartData = @json($chartData);

// Create chart
const ctx = document.getElementById('transactionChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.map(item => item.date),
        datasets: [
            {
                label: 'Số giao dịch',
                data: chartData.map(item => item.transactions),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                yAxisID: 'y'
            },
            {
                label: 'Số tiền (VND)',
                data: chartData.map(item => item.amount),
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Số giao dịch'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Số tiền (VND)'
                },
                grid: {
                    drawOnChartArea: false,
                },
            }
        }
    }
});

// Show success/error messages from session
@if(session('success'))
    Notify.success('{{ session('success') }}');
@endif

@if(session('error'))
    Notify.error('{{ session('error') }}');
@endif

@if(session('warning'))
    Notify.warning('{{ session('warning') }}');
@endif

@if(session('info'))
    Notify.info('{{ session('info') }}');
@endif
</script>
@endpush
