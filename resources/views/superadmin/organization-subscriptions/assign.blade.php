@extends('layouts.superadmin')

@section('title', 'Gán Gói Dịch Vụ')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4"><i class="fas fa-plus-circle me-2"></i>Gán Gói Dịch Vụ cho {{ $organization->name }}</h1>

    @if($currentSubscription && $currentSubscription->isValid())
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Tổ chức này đang có gói <strong>{{ $currentSubscription->plan->name }}</strong> ({{ $currentSubscription->getStatusLabel() }}).
        Gán gói mới sẽ hủy gói hiện tại.
    </div>
    @endif

    <form action="{{ route('superadmin.organizations.subscription.store', $organization->id) }}" method="POST">
        @csrf
        
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Chọn gói dịch vụ</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($plans as $plan)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-2 {{ $currentSubscription && $currentSubscription->plan_id == $plan->id ? 'border-primary' : '' }}">
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="plan_id" id="plan_{{ $plan->id }}" 
                                           value="{{ $plan->id }}" {{ $currentSubscription && $currentSubscription->plan_id == $plan->id ? 'checked' : '' }} required>
                                    <label class="form-check-label w-100" for="plan_{{ $plan->id }}">
                                        <h5>{{ $plan->name }}</h5>
                                        <p class="text-muted small">{{ $plan->description }}</p>
                                        
                                        <div class="mb-2">
                                            <strong>Giá:</strong><br>
                                            <span>{{ number_format($plan->price_monthly, 0, ',', '.') }} VND/tháng</span><br>
                                            <span>{{ number_format($plan->price_yearly, 0, ',', '.') }} VND/năm</span>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <strong>Dùng thử:</strong> {{ $plan->trial_days }} ngày
                                        </div>
                                        
                                        <div class="mt-3">
                                            <small class="text-muted d-block">
                                                <i class="fas fa-check text-success"></i> 
                                                {{ $plan->features->count() }} tính năng
                                            </small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Cấu hình đăng ký</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Chu kỳ thanh toán</label>
                            <select name="payment_cycle" class="form-control" required>
                                <option value="monthly">Hàng tháng</option>
                                <option value="yearly">Hàng năm</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Cổng thanh toán</label>
                            <select name="payment_gateway" class="form-control" required>
                                <option value="manual">Thủ công</option>
                                <option value="vnpay">VNPay</option>
                                <option value="momo">MoMo</option>
                                <option value="sepay">SePay</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="start_trial" value="1" id="start_trial" checked>
                            <label class="form-check-label" for="start_trial">
                                Bắt đầu với dùng thử (nếu có)
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="auto_renew" value="1" id="auto_renew">
                            <label class="form-check-label" for="auto_renew">
                                Tự động gia hạn
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Gán gói</button>
            <a href="{{ route('superadmin.organizations.show', $organization->id) }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection

