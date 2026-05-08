@extends('layouts.superadmin')

@section('title', 'Tạo Gói Dịch Vụ Mới')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.subscription-plans.index') }}">Subscription Plans</a></li>
        <li class="breadcrumb-item active">Create</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4"><i class="fas fa-box me-2"></i>Tạo Gói Dịch Vụ Mới</h1>

    <form action="{{ route('superadmin.subscription-plans.store') }}" method="POST">
        @csrf
        
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Thông tin cơ bản</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Mã gói <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tên gói <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Giá tháng <span class="text-danger">*</span></label>
                            <input type="number" name="price_monthly" class="form-control @error('price_monthly') is-invalid @enderror" value="{{ old('price_monthly', 0) }}" min="0" step="0.01" required>
                            @error('price_monthly')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Giá năm <span class="text-danger">*</span></label>
                            <input type="number" name="price_yearly" class="form-control @error('price_yearly') is-invalid @enderror" value="{{ old('price_yearly', 0) }}" min="0" step="0.01" required>
                            @error('price_yearly')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tiền tệ <span class="text-danger">*</span></label>
                            <select name="currency" class="form-control @error('currency') is-invalid @enderror" required>
                                <option value="VND" {{ old('currency', 'VND') == 'VND' ? 'selected' : '' }}>VND</option>
                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                            </select>
                            @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Số ngày dùng thử</label>
                            <input type="number" name="trial_days" class="form-control @error('trial_days') is-invalid @enderror" value="{{ old('trial_days', 14) }}" min="0" required>
                            @error('trial_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Thứ tự</label>
                            <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', 0) }}" min="0" required>
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="is_active" class="form-control @error('is_active') is-invalid @enderror" required>
                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Hoạt động</option>
                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Không hoạt động</option>
                            </select>
                            @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="hidden" name="is_custom" value="0">
                        <input class="form-check-input" type="checkbox" name="is_custom" value="1" id="is_custom" {{ old('is_custom') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_custom">Gói tùy chỉnh</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tính năng gói</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Cấu hình các tính năng và giới hạn cho gói này</p>
                
                @foreach($availableFeatures as $index => $feature)
                <div class="feature-row mb-3 p-3 border rounded">
                    <input type="hidden" name="features[{{ $index }}][feature_key]" value="{{ $feature['key'] }}">
                    <input type="hidden" name="features[{{ $index }}][feature_name]" value="{{ $feature['name'] }}">
                    <input type="hidden" name="features[{{ $index }}][feature_type]" value="{{ $feature['type'] }}">
                    
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <strong>{{ $feature['name'] }}</strong><br>
                            <small class="text-muted">{{ $feature['key'] }}</small>
                        </div>
                        <div class="col-md-6">
                            @if($feature['type'] === 'limit')
                                @php
                                    $oldValue = old("features.{$index}.feature_value", 10);
                                @endphp
                                <input type="number" 
                                       name="features[{{ $index }}][feature_value]" 
                                       class="form-control @error("features.{$index}.feature_value") is-invalid @enderror" 
                                       placeholder="Giới hạn (-1 = không giới hạn)" 
                                       value="{{ $oldValue }}"
                                       required>
                                @error("features.{$index}.feature_value")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @else
                                @php
                                    $oldValue = old("features.{$index}.feature_value", '1');
                                @endphp
                                <select name="features[{{ $index }}][feature_value]" class="form-control @error("features.{$index}.feature_value") is-invalid @enderror" required>
                                    <option value="1" {{ $oldValue == '1' ? 'selected' : '' }}>Bật</option>
                                    <option value="0" {{ $oldValue == '0' ? 'selected' : '' }}>Tắt</option>
                                </select>
                                @error("features.{$index}.feature_value")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Lưu gói</button>
            <a href="{{ route('superadmin.subscription-plans.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection

