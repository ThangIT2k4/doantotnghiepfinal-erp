@extends('layouts.staff_dashboard')

@section('title', 'Đăng ký Gói Dịch Vụ')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Session Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Đăng ký Gói Dịch Vụ',
            'subtitle' => 'Chọn và đăng ký gói dịch vụ cho tổ chức của bạn',
            'icon' => 'fas fa-box',
            'actions' => [
                [
                    'variant' => 'info',
                    'label' => 'Xem Hóa Đơn',
                    'icon' => 'fas fa-file-invoice',
                    'url' => route('staff.subscriptions.invoices.index')
                ]
            ]
        ])

        <!-- Current Subscription Info -->
        @if($activeSubscription && $hasValidSubscription)
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <h5 class="mb-2">
                        <i class="fas fa-info-circle me-2"></i>Gói Dịch Vụ Hiện Tại
                    </h5>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div>
                            <strong>{{ $activeSubscription->plan->name ?? 'N/A' }}</strong>
                        </div>
                        <div>
                            <span class="badge bg-{{ $activeSubscription->getStatusColor() }}">
                                {{ $activeSubscription->getStatusLabel() }}
                            </span>
                        </div>
                        @if($activeSubscription->current_period_end)
                        <div class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Hết hạn: {{ $activeSubscription->current_period_end->format('d/m/Y') }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Subscription Plans -->
        <div class="row">
            @forelse($plans as $plan)
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-1">{{ $plan->name }}</h5>
                        <small class="opacity-75">{{ $plan->code }}</small>
                    </div>
                    <div class="card-body">
                        @if($plan->description)
                        <p class="text-muted mb-3">{{ $plan->description }}</p>
                        @endif

                        <div class="mb-3">
                            <h3 class="text-primary mb-1">
                                {{ number_format($plan->price_monthly, 0, ',', '.') }} {{ $plan->currency }}
                                <small class="text-muted fs-6">/tháng</small>
                            </h3>
                            @if($plan->price_yearly > 0)
                            <p class="text-muted mb-0 small">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Hoặc {{ number_format($plan->price_yearly, 0, ',', '.') }} {{ $plan->currency }}/năm
                            </p>
                            @endif
                        </div>

                        @if($plan->trial_days > 0)
                        <div class="alert alert-success py-2 px-3 mb-3">
                            <i class="fas fa-gift me-2"></i>
                            <strong>Dùng thử {{ $plan->trial_days }} ngày</strong>
                        </div>
                        @endif

                        @php
                            // Phân loại features
                            $limitFeatures = $plan->features->filter(function($feature) {
                                return $feature->isLimit();
                            })->sortBy(function($feature) {
                                // Sắp xếp theo thứ tự: properties, units, users, leases
                                $order = ['max_properties' => 1, 'max_units' => 2, 'max_users' => 3, 'max_leases' => 4];
                                return $order[$feature->feature_key] ?? 99;
                            });
                            
                            $booleanFeatures = $plan->features->filter(function($feature) {
                                return $feature->isBoolean() && $feature->getValue() === true;
                            })->sortBy('feature_name');
                        @endphp

                        @if($limitFeatures->count() > 0)
                        <div class="mb-3">
                            <h6 class="mb-2">
                                <i class="fas fa-chart-line text-primary me-1"></i>
                                Giới hạn:
                            </h6>
                            <ul class="list-unstyled mb-0">
                                @foreach($limitFeatures as $feature)
                                <li class="mb-1">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <small>
                                        {{ $feature->feature_name }}
                                        @php
                                            $limit = $feature->getValue();
                                        @endphp
                                        @if($limit == -1)
                                            <span class="badge bg-info ms-1">Không giới hạn</span>
                                        @elseif($limit > 0)
                                            <span class="badge bg-primary ms-1">{{ number_format($limit, 0, ',', '.') }}</span>
                                        @else
                                            <span class="badge bg-secondary ms-1">0</span>
                                        @endif
                                    </small>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        @if($booleanFeatures->count() > 0)
                        <div class="mb-3">
                            <h6 class="mb-2">
                                <i class="fas fa-star text-warning me-1"></i>
                                Tính năng nâng cao:
                            </h6>
                            <ul class="list-unstyled mb-0">
                                @foreach($booleanFeatures as $feature)
                                <li class="mb-1">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <small>{{ $feature->feature_name }}</small>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                    <div class="card-footer bg-light">
                        @if($currentPlanId == $plan->id)
                        <button class="btn btn-success w-100" disabled>
                            <i class="fas fa-check me-1"></i>
                            Gói hiện tại
                        </button>
                        @else
                        <a href="{{ route('staff.subscriptions.register', $plan) }}" class="btn btn-primary w-100">
                            <i class="fas fa-arrow-right me-1"></i>
                            {{ $hasValidSubscription ? 'Chuyển gói' : 'Đăng ký ngay' }}
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Không có gói dịch vụ nào</h5>
                        <p class="text-muted mb-0">Hiện tại không có gói dịch vụ nào khả dụng.</p>
                    </div>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</main>
@endsection
