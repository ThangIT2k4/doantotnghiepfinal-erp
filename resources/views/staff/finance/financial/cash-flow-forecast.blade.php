@extends('layouts.staff_dashboard')

@section('title', 'Dự báo Dòng tiền')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Dự báo Dòng tiền',
            'subtitle' => 'Dự báo thu chi trong tương lai dựa trên dữ liệu lịch sử',
            'icon' => 'fas fa-chart-area',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.financial-management.index')
                ]
            ]
        ])
        
        <div class="content">
        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('staff.financial-management.cash-flow-forecast') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Số tháng dự báo</label>
                        <select name="months" class="form-select">
                            <option value="3" {{ $months == 3 ? 'selected' : '' }}>3 tháng</option>
                            <option value="6" {{ $months == 6 ? 'selected' : '' }}>6 tháng</option>
                            <option value="12" {{ $months == 12 ? 'selected' : '' }}>12 tháng</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Chu kỳ</label>
                        <select name="period" class="form-select">
                            <option value="month" {{ $period == 'month' ? 'selected' : '' }}>Tháng</option>
                            <option value="quarter" {{ $period == 'quarter' ? 'selected' : '' }}>Quý</option>
                            <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Năm</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Áp dụng
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Tổng thu nhập (Quá khứ)</h6>
                        <h3 class="text-primary mb-0">{{ number_format($summary['past_total_income'], 0, ',', '.') }} VNĐ</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-warning">
                    <div class="card-body">
                        <h6 class="text-muted">Tổng chi phí (Quá khứ)</h6>
                        <h3 class="text-warning mb-0">{{ number_format($summary['past_total_expense'], 0, ',', '.') }} VNĐ</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-{{ $summary['past_net'] >= 0 ? 'success' : 'danger' }}">
                    <div class="card-body">
                        <h6 class="text-muted">Dòng tiền ròng (Quá khứ)</h6>
                        <h3 class="mb-0 {{ $summary['past_net'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($summary['past_net'], 0, ',', '.') }} VNĐ
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forecast Chart -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line"></i> Biểu đồ Dự báo Dòng tiền
                    <small class="ms-2 opacity-75">({{ $months }} tháng tới)</small>
                </h5>
            </div>
            <div class="card-body" style="position: relative; height: 450px;">
                <canvas id="cashFlowChart"></canvas>
            </div>
            <div class="card-footer bg-light">
                <div class="row text-center">
                    <div class="col-md-4">
                        <small class="text-muted">Thu nhập trung bình</small>
                        <h6 class="text-success mb-0">{{ number_format($summary['forecast_total_income'] / $months, 0, ',', '.') }} VNĐ/tháng</h6>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Chi phí trung bình</small>
                        <h6 class="text-danger mb-0">{{ number_format($summary['forecast_total_expense'] / $months, 0, ',', '.') }} VNĐ/tháng</h6>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Dòng tiền ròng trung bình</small>
                        <h6 class="text-primary mb-0">{{ number_format($summary['forecast_net'] / $months, 0, ',', '.') }} VNĐ/tháng</h6>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calculation Notes -->
        @if(isset($summary['calculation_note']))
        <div class="card shadow-sm mb-4 border-info">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle"></i> Cách tính các chỉ số dự báo
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Tổng thu nhập quá khứ:</strong> {{ $summary['calculation_note']['past_total_income'] ?? '' }}
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Tổng chi phí quá khứ:</strong> {{ $summary['calculation_note']['past_total_expense'] ?? '' }}
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Dòng tiền ròng quá khứ:</strong> {{ $summary['calculation_note']['past_net'] ?? '' }}
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Tổng thu nhập dự báo:</strong> {{ $summary['calculation_note']['forecast_total_income'] ?? '' }}
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Tổng chi phí dự báo:</strong> {{ $summary['calculation_note']['forecast_total_expense'] ?? '' }}
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Dòng tiền ròng dự báo:</strong> {{ $summary['calculation_note']['forecast_net'] ?? '' }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Forecast Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-table"></i> Chi tiết Dự báo</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tháng</th>
                                <th class="text-end">Thu nhập dự báo</th>
                                <th class="text-end">Chi phí dự báo</th>
                                <th class="text-end">Dòng tiền ròng</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($forecast as $item)
                            <tr>
                                <td><strong>{{ $item['month'] }}</strong></td>
                                <td class="text-end text-success">{{ number_format($item['forecasted_income'], 0, ',', '.') }} VNĐ</td>
                                <td class="text-end text-danger">{{ number_format($item['forecasted_expense'], 0, ',', '.') }} VNĐ</td>
                                <td class="text-end {{ $item['forecasted_net'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>{{ number_format($item['forecasted_net'], 0, ',', '.') }} VNĐ</strong>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-primary">
                                <th>Tổng cộng</th>
                                <th class="text-end">{{ number_format($summary['forecast_total_income'], 0, ',', '.') }} VNĐ</th>
                                <th class="text-end">{{ number_format($summary['forecast_total_expense'], 0, ',', '.') }} VNĐ</th>
                                <th class="text-end">
                                    <strong>{{ number_format($summary['forecast_net'], 0, ',', '.') }} VNĐ</strong>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
</main>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('cashFlowChart');
    if (ctx) {
        const forecastData = @json($forecast);
        
        // Create gradient for income
        const incomeGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        incomeGradient.addColorStop(0, 'rgba(40, 167, 69, 0.3)');
        incomeGradient.addColorStop(1, 'rgba(40, 167, 69, 0.0)');
        
        // Create gradient for expense
        const expenseGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        expenseGradient.addColorStop(0, 'rgba(220, 53, 69, 0.3)');
        expenseGradient.addColorStop(1, 'rgba(220, 53, 69, 0.0)');
        
        // Create gradient for net
        const netGradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        netGradient.addColorStop(0, 'rgba(13, 110, 253, 0.3)');
        netGradient.addColorStop(1, 'rgba(13, 110, 253, 0.0)');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: forecastData.map(item => item.month),
                datasets: [
                    {
                        label: 'Thu nhập dự báo',
                        data: forecastData.map(item => item.forecasted_income),
                        borderColor: 'rgb(40, 167, 69)',
                        backgroundColor: incomeGradient,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: 'rgb(40, 167, 69)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgb(40, 167, 69)',
                        pointHoverBorderWidth: 3
                    },
                    {
                        label: 'Chi phí dự báo',
                        data: forecastData.map(item => item.forecasted_expense),
                        borderColor: 'rgb(220, 53, 69)',
                        backgroundColor: expenseGradient,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: 'rgb(220, 53, 69)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgb(220, 53, 69)',
                        pointHoverBorderWidth: 3
                    },
                    {
                        label: 'Dòng tiền ròng',
                        data: forecastData.map(item => item.forecasted_net),
                        borderColor: 'rgb(13, 110, 253)',
                        backgroundColor: netGradient,
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: 'rgb(13, 110, 253)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgb(13, 110, 253)',
                        pointHoverBorderWidth: 3
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
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 13,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            title: function(context) {
                                return 'Tháng: ' + context[0].label;
                            },
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VNĐ';
                                return label;
                            },
                            footer: function(tooltipItems) {
                                const net = tooltipItems.find(item => item.dataset.label === 'Dòng tiền ròng');
                                if (net && net.parsed.y < 0) {
                                    return 'Cảnh báo: Dòng tiền âm!';
                                }
                                return '';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            callback: function(value) {
                                // Format large numbers
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'M VNĐ';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'K VNĐ';
                                }
                                return new Intl.NumberFormat('vi-VN').format(value) + ' VNĐ';
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
});
</script>
@endpush

