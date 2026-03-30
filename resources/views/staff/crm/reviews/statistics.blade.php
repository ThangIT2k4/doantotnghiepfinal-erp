@extends('layouts.staff_dashboard')

@section('title', 'Thống kê Đánh giá')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Thống kê Đánh giá',
            'subtitle' => 'Báo cáo chi tiết về đánh giá từ khách thuê',
            'icon' => 'fas fa-chart-bar'
        ])

        {{-- Back button --}}
        <div class="mb-3">
            <a href="{{ route('staff.reviews.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách
            </a>
        </div>

        {{-- 2. Overall Statistics Cards --}}
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Tổng đánh giá</h6>
                                <h3 class="mb-0">{{ number_format($totalReviews) }}</h3>
                            </div>
                            <div class="text-primary">
                                <i class="fas fa-list fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Đã duyệt</h6>
                                <h3 class="mb-0">{{ number_format($approvedReviews) }}</h3>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Chờ duyệt</h6>
                                <h3 class="mb-0">{{ number_format($pendingReviews) }}</h3>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Đã từ chối</h6>
                                <h3 class="mb-0">{{ number_format($rejectedReviews) }}</h3>
                            </div>
                            <div class="text-danger">
                                <i class="fas fa-times-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Average Ratings --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-star"></i> Đánh giá Trung bình</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2 text-center">
                                <h4 class="text-primary mb-1">{{ number_format($avgOverallRating, 1) }}</h4>
                                <small class="text-muted">Tổng thể</small>
                                <div class="mt-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $avgOverallRating ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <h4 class="text-info mb-1">{{ number_format($avgLocationRating, 1) }}</h4>
                                <small class="text-muted">Vị trí</small>
                                <div class="mt-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $avgLocationRating ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <h4 class="text-info mb-1">{{ number_format($avgQualityRating, 1) }}</h4>
                                <small class="text-muted">Chất lượng</small>
                                <div class="mt-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $avgQualityRating ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <h4 class="text-info mb-1">{{ number_format($avgServiceRating, 1) }}</h4>
                                <small class="text-muted">Dịch vụ</small>
                                <div class="mt-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $avgServiceRating ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <h4 class="text-info mb-1">{{ number_format($avgPriceRating, 1) }}</h4>
                                <small class="text-muted">Giá cả</small>
                                <div class="mt-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $avgPriceRating ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                                </div>
                            </div>
                            <div class="col-md-2 text-center">
                                <h4 class="text-success mb-1">{{ number_format($reviewsWithReplies) }}</h4>
                                <small class="text-muted">Có phản hồi</small>
                                <div class="mt-2">
                                    <i class="fas fa-comments text-success fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Rating Distribution --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Phân bố Đánh giá theo Sao</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Sao</th>
                                    <th>Số lượng</th>
                                    <th>Tỷ lệ</th>
                                    <th>Biểu đồ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalApproved = array_sum($ratingDistribution);
                                @endphp
                                @for($i = 5; $i >= 1; $i--)
                                    @php
                                        $count = $ratingDistribution[$i] ?? 0;
                                        $percentage = $totalApproved > 0 ? ($count / $totalApproved * 100) : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            @for($j = 1; $j <= 5; $j++)
                                                <i class="fas fa-star {{ $j <= $i ? 'text-warning' : 'text-muted' }}"></i>
                                            @endfor
                                        </td>
                                        <td><strong>{{ number_format($count) }}</strong></td>
                                        <td>{{ number_format($percentage, 1) }}%</td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-warning" role="progressbar" 
                                                     style="width: {{ $percentage }}%" 
                                                     aria-valuenow="{{ $percentage }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    {{ $percentage > 5 ? number_format($percentage, 1) . '%' : '' }}
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-thumbs-up"></i> Thống kê Giới thiệu</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $totalRecommend = $recommendYes + $recommendMaybe + $recommendNo;
                            $recommendYesPercent = $totalRecommend > 0 ? ($recommendYes / $totalRecommend * 100) : 0;
                            $recommendMaybePercent = $totalRecommend > 0 ? ($recommendMaybe / $totalRecommend * 100) : 0;
                            $recommendNoPercent = $totalRecommend > 0 ? ($recommendNo / $totalRecommend * 100) : 0;
                        @endphp
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Trạng thái</th>
                                    <th>Số lượng</th>
                                    <th>Tỷ lệ</th>
                                    <th>Biểu đồ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="fas fa-check-circle text-success"></i> Có</td>
                                    <td><strong>{{ number_format($recommendYes) }}</strong></td>
                                    <td>{{ number_format($recommendYesPercent, 1) }}%</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: {{ $recommendYesPercent }}%" 
                                                 aria-valuenow="{{ $recommendYesPercent }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $recommendYesPercent > 5 ? number_format($recommendYesPercent, 1) . '%' : '' }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-question-circle text-warning"></i> Có thể</td>
                                    <td><strong>{{ number_format($recommendMaybe) }}</strong></td>
                                    <td>{{ number_format($recommendMaybePercent, 1) }}%</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                 style="width: {{ $recommendMaybePercent }}%" 
                                                 aria-valuenow="{{ $recommendMaybePercent }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $recommendMaybePercent > 5 ? number_format($recommendMaybePercent, 1) . '%' : '' }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-times-circle text-danger"></i> Không</td>
                                    <td><strong>{{ number_format($recommendNo) }}</strong></td>
                                    <td>{{ number_format($recommendNoPercent, 1) }}%</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                 style="width: {{ $recommendNoPercent }}%" 
                                                 aria-valuenow="{{ $recommendNoPercent }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $recommendNoPercent > 5 ? number_format($recommendNoPercent, 1) . '%' : '' }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. Monthly Statistics Table --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Thống kê theo Tháng (12 tháng gần nhất)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Tháng</th>
                                        <th class="text-center">Tổng</th>
                                        <th class="text-center">Đã duyệt</th>
                                        <th class="text-center">Chờ duyệt</th>
                                        <th class="text-center">Đã từ chối</th>
                                        <th class="text-center">Đánh giá TB</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monthlyStats as $stat)
                                        <tr>
                                            <td><strong>{{ $stat['month_label'] }}</strong></td>
                                            <td class="text-center">{{ number_format($stat['total']) }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-success">{{ number_format($stat['approved']) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-warning">{{ number_format($stat['pending']) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-danger">{{ number_format($stat['rejected']) }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($stat['avg_rating'] > 0)
                                                    <strong>{{ number_format($stat['avg_rating'], 1) }}</strong>
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <i class="fas fa-star {{ $i <= $stat['avg_rating'] ? 'text-warning' : 'text-muted' }}" style="font-size: 0.8em;"></i>
                                                    @endfor
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-info">
                                        <td><strong>Tổng cộng</strong></td>
                                        <td class="text-center"><strong>{{ number_format($totalReviews) }}</strong></td>
                                        <td class="text-center"><strong>{{ number_format($approvedReviews) }}</strong></td>
                                        <td class="text-center"><strong>{{ number_format($pendingReviews) }}</strong></td>
                                        <td class="text-center"><strong>{{ number_format($rejectedReviews) }}</strong></td>
                                        <td class="text-center">
                                            @if($avgOverallRating > 0)
                                                <strong>{{ number_format($avgOverallRating, 1) }}</strong>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 6. Property Statistics Table --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-building"></i> Thống kê theo Bất động sản</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Bất động sản</th>
                                        <th class="text-center">Tổng</th>
                                        <th class="text-center">Đã duyệt</th>
                                        <th class="text-center">Chờ duyệt</th>
                                        <th class="text-center">Đã từ chối</th>
                                        <th class="text-center">Đánh giá TB</th>
                                        <th class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($propertyStats as $stat)
                                        <tr>
                                            <td>
                                                <strong>{{ $stat['property_name'] }}</strong>
                                            </td>
                                            <td class="text-center">{{ number_format($stat['total']) }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-success">{{ number_format($stat['approved']) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-warning">{{ number_format($stat['pending']) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-danger">{{ number_format($stat['rejected']) }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($stat['avg_rating'] > 0)
                                                    <strong>{{ number_format($stat['avg_rating'], 1) }}</strong>
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <i class="fas fa-star {{ $i <= $stat['avg_rating'] ? 'text-warning' : 'text-muted' }}" style="font-size: 0.8em;"></i>
                                                    @endfor
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('staff.reviews.index', ['property_id' => $stat['property_id']]) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Xem
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i> Không có dữ liệu
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
    </div>
</main>
@endsection

@push('styles')
<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid rgba(0, 0, 0, 0.125);
    }
    .card-header {
        font-weight: 600;
    }
    .progress {
        border-radius: 0.25rem;
    }
    .table th {
        font-weight: 600;
        border-top: none;
    }
    .badge {
        font-size: 0.875rem;
        padding: 0.35em 0.65em;
    }
</style>
@endpush

