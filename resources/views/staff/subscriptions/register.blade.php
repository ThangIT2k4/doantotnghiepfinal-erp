@extends('layouts.staff_dashboard')

@section('title', 'Đăng ký Gói Dịch Vụ')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Session Messages -->
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Page Header -->
        @include('staff.components.show-page-header', [
            'title' => 'Đăng ký Gói: ' . $subscriptionPlan->name,
            'subtitle' => 'Chọn chu kỳ thanh toán và phương thức thanh toán',
            'icon' => 'fas fa-box',
            'breadcrumbs' => [
                ['label' => 'Gói Dịch Vụ', 'url' => route('staff.subscriptions.index')],
                ['label' => 'Đăng ký', 'active' => true]
            ]
        ])

        @if($activeSubscription && $activeSubscription->isValid())
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Chuyển gói dịch vụ:</strong> Bạn đang sử dụng gói <strong>{{ $activeSubscription->plan->name ?? 'N/A' }}</strong>. 
            Sau khi thanh toán thành công, gói mới sẽ được kích hoạt và gói cũ sẽ bị hủy.
        </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin gói dịch vụ
                        </h5>
                    </div>
                    <div class="card-body">
                        <h4 class="mb-3">{{ $subscriptionPlan->name }}</h4>
                        @if($subscriptionPlan->description)
                        <p class="text-muted mb-3">{{ $subscriptionPlan->description }}</p>
                        @endif

                        @php
                            // Chỉ hiển thị các features limit: max_leases, max_properties, max_units, max_users
                            $displayFeatures = $subscriptionPlan->features->filter(function($feature) {
                                return in_array($feature->feature_key, ['max_leases', 'max_properties', 'max_units', 'max_users']);
                            });
                        @endphp
                        @if($displayFeatures->count() > 0)
                        <h6 class="mb-2">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            Tính năng:
                        </h6>
                        <ul class="list-unstyled mb-0">
                            @foreach($displayFeatures as $feature)
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <span>
                                    {{ $feature->feature_name }}
                                    @if($feature->isLimit())
                                        @php
                                            $limit = $feature->getValue();
                                        @endphp
                                        @if($limit == -1)
                                            <span class="badge bg-info ms-1">Không giới hạn</span>
                                        @elseif($limit > 0)
                                            <span class="badge bg-primary ms-1">{{ number_format($limit, 0, ',', '.') }}</span>
                                        @endif
                                    @endif
                                </span>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-credit-card me-2"></i>Thông tin thanh toán
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.subscriptions.store', $subscriptionPlan) }}" id="subscriptionForm">
                            @csrf

                            <!-- Payment Cycle -->
                            <div class="mb-4">
                                <label class="form-label fw-bold mb-3">
                                    Chu kỳ thanh toán <span class="text-danger">*</span>
                                </label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card border-2" id="monthly-card" style="cursor: pointer; border-color: #0d6efd !important;">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_cycle" id="monthly" value="monthly" checked>
                                                    <label class="form-check-label w-100" for="monthly" style="cursor: pointer;">
                                                        <strong class="d-block mb-1">Hàng tháng</strong>
                                                        <span class="text-muted small">
                                                            {{ number_format($subscriptionPlan->price_monthly, 0, ',', '.') }} {{ $subscriptionPlan->currency }}/tháng
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-2" id="yearly-card" style="cursor: pointer; border-color: #dee2e6 !important;">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_cycle" id="yearly" value="yearly">
                                                    <label class="form-check-label w-100" for="yearly" style="cursor: pointer;">
                                                        <strong class="d-block mb-1">Hàng năm</strong>
                                                        <span class="text-muted small">
                                                            {{ number_format($subscriptionPlan->price_yearly, 0, ',', '.') }} {{ $subscriptionPlan->currency }}/năm
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="mb-4">
                                <label class="form-label fw-bold mb-3">
                                    Phương thức thanh toán <span class="text-danger">*</span>
                                </label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card border-2" id="sepay-card" style="cursor: pointer; border-color: #0d6efd !important;">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="sepay" value="sepay" checked>
                                                    <label class="form-check-label w-100" for="sepay" style="cursor: pointer;">
                                                        <strong class="d-block mb-1">
                                                            <i class="fas fa-university me-1"></i>Chuyển khoản SePay
                                                        </strong>
                                                        <span class="text-muted small">Thanh toán qua chuyển khoản ngân hàng</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-2" id="manual-card" style="cursor: pointer; border-color: #dee2e6 !important;">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="manual" value="manual">
                                                    <label class="form-check-label w-100" for="manual" style="cursor: pointer;">
                                                        <strong class="d-block mb-1">
                                                            <i class="fas fa-hand-holding-usd me-1"></i>Thanh toán thủ công
                                                        </strong>
                                                        <span class="text-muted small">Liên hệ admin để xác nhận</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                @include('staff.components.form-actions', [
                                    'submitLabel' => 'Tiếp tục',
                                    'submitIcon' => 'fas fa-arrow-right',
                                    'submitVariant' => 'primary',
                                    'submitSize' => 'md',
                                    'cancelLabel' => 'Quay lại',
                                    'cancelIcon' => 'fas fa-arrow-left',
                                    'cancelVariant' => 'secondary',
                                    'cancelSize' => 'md',
                                    'cancelUrl' => route('staff.subscriptions.index'),
                                    'showCancel' => true,
                                    'showSubmit' => true,
                                    'justify' => 'between'
                                ])
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-receipt me-1"></i>Tổng thanh toán
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="price-display">
                            <div class="mb-3">
                                <small class="text-muted d-block">Gói dịch vụ</small>
                                <strong>{{ $subscriptionPlan->name }}</strong>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Chu kỳ</small>
                                <strong id="cycle-display">Hàng tháng</strong>
                            </div>
                            <hr>
                            <div>
                                <small class="text-muted d-block mb-1">Tổng tiền</small>
                                <h3 class="text-primary mb-0">
                                    <span id="amount-display">{{ number_format($subscriptionPlan->price_monthly, 0, ',', '.') }}</span> 
                                    <small class="fs-6">{{ $subscriptionPlan->currency }}</small>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@push('styles')
<style>
.card.border-2 {
    transition: all 0.3s ease;
}

.card.border-2:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.card.border-2[style*="border-color: #0d6efd"] {
    background-color: #f0f7ff;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthlyRadio = document.getElementById('monthly');
    const yearlyRadio = document.getElementById('yearly');
    const monthlyCard = document.getElementById('monthly-card');
    const yearlyCard = document.getElementById('yearly-card');
    const cycleDisplay = document.getElementById('cycle-display');
    const amountDisplay = document.getElementById('amount-display');
    
    const monthlyPrice = {{ $subscriptionPlan->price_monthly }};
    const yearlyPrice = {{ $subscriptionPlan->price_yearly }};

    // Payment method cards
    const sepayRadio = document.getElementById('sepay');
    const manualRadio = document.getElementById('manual');
    const sepayCard = document.getElementById('sepay-card');
    const manualCard = document.getElementById('manual-card');

    function updatePrice() {
        if (yearlyRadio.checked) {
            cycleDisplay.textContent = 'Hàng năm';
            amountDisplay.textContent = yearlyPrice.toLocaleString('vi-VN');
            monthlyCard.style.borderColor = '#dee2e6';
            monthlyCard.style.backgroundColor = '';
            yearlyCard.style.borderColor = '#0d6efd';
            yearlyCard.style.backgroundColor = '#f0f7ff';
        } else {
            cycleDisplay.textContent = 'Hàng tháng';
            amountDisplay.textContent = monthlyPrice.toLocaleString('vi-VN');
            yearlyCard.style.borderColor = '#dee2e6';
            yearlyCard.style.backgroundColor = '';
            monthlyCard.style.borderColor = '#0d6efd';
            monthlyCard.style.backgroundColor = '#f0f7ff';
        }
    }

    function updatePaymentMethod() {
        if (manualRadio.checked) {
            sepayCard.style.borderColor = '#dee2e6';
            sepayCard.style.backgroundColor = '';
            manualCard.style.borderColor = '#0d6efd';
            manualCard.style.backgroundColor = '#f0f7ff';
        } else {
            manualCard.style.borderColor = '#dee2e6';
            manualCard.style.backgroundColor = '';
            sepayCard.style.borderColor = '#0d6efd';
            sepayCard.style.backgroundColor = '#f0f7ff';
        }
    }

    // Card click handlers
    monthlyCard.addEventListener('click', function() {
        monthlyRadio.checked = true;
        updatePrice();
    });

    yearlyCard.addEventListener('click', function() {
        yearlyRadio.checked = true;
        updatePrice();
    });

    sepayCard.addEventListener('click', function() {
        sepayRadio.checked = true;
        updatePaymentMethod();
    });

    manualCard.addEventListener('click', function() {
        manualRadio.checked = true;
        updatePaymentMethod();
    });

    // Radio change handlers
    monthlyRadio.addEventListener('change', updatePrice);
    yearlyRadio.addEventListener('change', updatePrice);
    sepayRadio.addEventListener('change', updatePaymentMethod);
    manualRadio.addEventListener('change', updatePaymentMethod);

    // Initialize
    updatePrice();
    updatePaymentMethod();
});
</script>
@endpush
@endsection
