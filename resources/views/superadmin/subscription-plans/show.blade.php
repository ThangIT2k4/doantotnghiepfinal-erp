@extends('layouts.superadmin')

@section('title', 'Chi tiết Gói Dịch Vụ')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.subscription-plans.index') }}">Subscription Plans</a></li>
        <li class="breadcrumb-item active">{{ $subscriptionPlan->name }}</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-box me-2"></i>
                {{ $subscriptionPlan->name }}
            </h1>
            <p class="text-muted mb-0">{{ $subscriptionPlan->description }}</p>
        </div>
        <div>
            <a href="{{ route('superadmin.subscription-plans.edit', $subscriptionPlan->id) }}" class="btn btn-warning">
                <i class="fas fa-edit me-1"></i> Chỉnh sửa
            </a>
            <a href="{{ route('superadmin.subscription-plans.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Plan Information -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin gói</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Mã gói:</th>
                            <td><span class="badge bg-secondary">{{ $subscriptionPlan->code }}</span></td>
                        </tr>
                        <tr>
                            <th>Giá tháng:</th>
                            <td><strong>{{ number_format($subscriptionPlan->price_monthly, 0, ',', '.') }} {{ $subscriptionPlan->currency }}</strong></td>
                        </tr>
                        <tr>
                            <th>Giá năm:</th>
                            <td><strong>{{ number_format($subscriptionPlan->price_yearly, 0, ',', '.') }} {{ $subscriptionPlan->currency }}</strong></td>
                        </tr>
                        <tr>
                            <th>Thời gian dùng thử:</th>
                            <td>{{ $subscriptionPlan->trial_days }} ngày</td>
                        </tr>
                        <tr>
                            <th>Trạng thái:</th>
                            <td>
                                @if($subscriptionPlan->is_active)
                                    <span class="badge bg-success">Hoạt động</span>
                                @else
                                    <span class="badge bg-secondary">Không hoạt động</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Loại:</th>
                            <td>
                                @if($subscriptionPlan->is_custom)
                                    <span class="badge bg-warning">Tùy chỉnh</span>
                                @else
                                    <span class="badge bg-primary">Chuẩn</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Tổ chức đang dùng:</th>
                            <td><span class="badge bg-info">{{ $activeSubscriptionsCount }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Plan Features -->
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tính năng</h6>
                </div>
                <div class="card-body">
                    @if($subscriptionPlan->features->count() > 0)
                    <div class="list-group">
                        @foreach($subscriptionPlan->features as $feature)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $feature->feature_name }}</strong>
                                    <br><small class="text-muted">{{ $feature->feature_key }}</small>
                                </div>
                                <div>
                                    @if($feature->feature_type === 'limit')
                                        @php
                                            $limit = $feature->getValue();
                                        @endphp
                                        @if($limit === -1)
                                            <span class="badge bg-success">Không giới hạn</span>
                                        @else
                                            <span class="badge bg-info">{{ $limit }}</span>
                                        @endif
                                    @elseif($feature->feature_type === 'boolean')
                                        @if($feature->getValue())
                                            <span class="badge bg-success"><i class="fas fa-check"></i> Có</span>
                                        @else
                                            <span class="badge bg-secondary"><i class="fas fa-times"></i> Không</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted">Chưa có tính năng nào được cấu hình</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Organizations Using This Plan -->
    @if($subscriptionPlan->subscriptions->count() > 0)
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Tổ chức đang sử dụng gói này</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tổ chức</th>
                            <th>Trạng thái</th>
                            <th>Chu kỳ</th>
                            <th>Bắt đầu</th>
                            <th>Kết thúc</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subscriptionPlan->subscriptions as $subscription)
                        <tr>
                            <td>
                                <a href="{{ route('superadmin.organizations.show', $subscription->organization_id) }}">
                                    {{ $subscription->organization->name ?? 'N/A' }}
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-{{ $subscription->getStatusColor() }}">
                                    {{ $subscription->getStatusLabel() }}
                                </span>
                            </td>
                            <td>{{ ucfirst($subscription->payment_cycle) }}</td>
                            <td>{{ $subscription->current_period_start ? $subscription->current_period_start->format('d/m/Y') : '-' }}</td>
                            <td>{{ $subscription->current_period_end ? $subscription->current_period_end->format('d/m/Y') : '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

