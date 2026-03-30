@extends('layouts.staff_dashboard')

@section('title', 'Báo cáo Thuế')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Báo cáo Thuế',
            'subtitle' => 'Tổng hợp thuế VAT theo tháng/quý/năm',
            'icon' => 'fas fa-file-invoice-dollar',
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
                <form method="GET" action="{{ route('staff.financial-management.tax-reports') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Năm</label>
                        <select name="year" class="form-select">
                            @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quý</label>
                        <select name="quarter" class="form-select">
                            <option value="" {{ $quarter == null ? 'selected' : '' }}>Cả năm</option>
                            <option value="1" {{ $quarter == 1 ? 'selected' : '' }}>Quý 1</option>
                            <option value="2" {{ $quarter == 2 ? 'selected' : '' }}>Quý 2</option>
                            <option value="3" {{ $quarter == 3 ? 'selected' : '' }}>Quý 3</option>
                            <option value="4" {{ $quarter == 4 ? 'selected' : '' }}>Quý 4</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tiền tệ</label>
                        <select name="currency" class="form-select">
                            <option value="all" {{ $currency == 'all' ? 'selected' : '' }}>Tất cả</option>
                            <option value="VND" {{ $currency == 'VND' ? 'selected' : '' }}>VND</option>
                            <option value="USD" {{ $currency == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="EUR" {{ $currency == 'EUR' ? 'selected' : '' }}>EUR</option>
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

        <!-- Calculation Notes -->
        @if(isset($summary['calculation_note']))
        <div class="card shadow-sm mb-4 border-info">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle"></i> Cách tính các chỉ số thuế
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Tổng thuế VAT:</strong> {{ $summary['calculation_note']['total_tax'] ?? '' }}
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Tổng giá trị hóa đơn:</strong> {{ $summary['calculation_note']['total_amount'] ?? '' }}
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Tỷ lệ thuế:</strong> {{ $summary['calculation_note']['tax_rate'] ?? '' }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Summary -->
        @if(is_array($summary) && count($summary) > 0)
        <div class="row mb-4">
            @if($quarter)
            <div class="col-md-4">
                <div class="card shadow-sm border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Tổng thuế Quý {{ $quarter }}</h6>
                        <h3 class="text-danger mb-0">{{ number_format($summary['total_tax'], 0, ',', '.') }} VNĐ</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Tổng doanh thu</h6>
                        <h3 class="text-primary mb-0">{{ number_format($summary['total_amount'], 0, ',', '.') }} VNĐ</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm border-info">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Tỷ lệ thuế</h6>
                        <h3 class="text-info mb-0">{{ number_format($summary['tax_rate'], 2) }}%</h3>
                    </div>
                </div>
            </div>
            @else
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar"></i> Tổng hợp theo Tháng</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tháng</th>
                                        <th class="text-end">Tổng thuế</th>
                                        <th class="text-end">Tổng doanh thu</th>
                                        <th class="text-end">Tỷ lệ thuế</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($summary as $month => $data)
                                    <tr>
                                        <td><strong>{{ $month }}</strong></td>
                                        <td class="text-end text-danger">{{ number_format($data['total_tax'], 0, ',', '.') }} VNĐ</td>
                                        <td class="text-end text-primary">{{ number_format($data['total_amount'], 0, ',', '.') }} VNĐ</td>
                                        <td class="text-end text-info">{{ number_format($data['tax_rate'], 2) }}%</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Tax Data Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-table"></i> Chi tiết Thuế</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tháng</th>
                                <th>Tiền tệ</th>
                                <th class="text-end">Tổng thuế</th>
                                <th class="text-end">Tổng doanh thu</th>
                                <th class="text-end">Tỷ lệ thuế</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($taxData as $data)
                            <tr>
                                <td><strong>{{ $data->month }}</strong></td>
                                <td><span class="badge bg-secondary">{{ $data->currency }}</span></td>
                                <td class="text-end text-danger">{{ number_format($data->total_tax, 0, ',', '.') }} {{ $data->currency }}</td>
                                <td class="text-end text-primary">{{ number_format($data->total_amount, 0, ',', '.') }} {{ $data->currency }}</td>
                                <td class="text-end text-info">
                                    {{ $data->total_amount > 0 ? number_format(($data->total_tax / $data->total_amount) * 100, 2) : 0 }}%
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>Không có dữ liệu thuế trong khoảng thời gian này</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
</main>
@endsection

