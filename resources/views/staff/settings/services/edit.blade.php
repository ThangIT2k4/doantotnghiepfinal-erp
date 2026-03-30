@extends('layouts.staff_dashboard')

@section('title', 'Sửa dịch vụ')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('staff.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('staff.services.index') }}">Quản lý dịch vụ</a></li>
                        <li class="breadcrumb-item active">Sửa dịch vụ</li>
                    </ol>
                </div>
                <h4 class="page-title">Sửa dịch vụ</h4>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-pencil me-2"></i>
                        Thông tin dịch vụ
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.services.update', $service->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="key_code" class="form-label">Mã dịch vụ</label>
                            <input type="text" class="form-control @error('key_code') is-invalid @enderror" 
                                   id="key_code" name="key_code" 
                                   value="{{ old('key_code', $service->key_code) }}" 
                                   placeholder="VD: ELEC, WATER, INTERNET">
                            @error('key_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Mã định danh dịch vụ (tùy chọn)
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Tên dịch vụ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" 
                                   value="{{ old('name', $service->name) }}" 
                                   placeholder="VD: Điện, Nước, Internet" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="pricing_type" class="form-label">Loại giá</label>
                            <select class="form-select @error('pricing_type') is-invalid @enderror" 
                                    id="pricing_type" name="pricing_type">
                                <option value="fixed" {{ old('pricing_type', $service->pricing_type ?? 'fixed') == 'fixed' ? 'selected' : '' }}>Cố định</option>
                                <option value="per_unit" {{ old('pricing_type', $service->pricing_type) == 'per_unit' ? 'selected' : '' }}>Theo đơn vị</option>
                                <option value="per_area" {{ old('pricing_type', $service->pricing_type) == 'per_area' ? 'selected' : '' }}>Theo diện tích</option>
                            </select>
                            @error('pricing_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="unit_label" class="form-label">Nhãn đơn vị</label>
                            <input type="text" class="form-control @error('unit_label') is-invalid @enderror" 
                                   id="unit_label" name="unit_label" 
                                   value="{{ old('unit_label', $service->unit_label ?? 'tháng') }}" 
                                   placeholder="VD: tháng, kWh, m³">
                            @error('unit_label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" 
                                      rows="3" 
                                      placeholder="Mô tả chi tiết về dịch vụ">{{ old('description', $service->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-1"></i>
                                Cập nhật
                            </button>
                            <a href="{{ route('staff.services.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left me-1"></i>
                                Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="mdi mdi-information-outline me-2"></i>
                        Thông tin dịch vụ
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Phạm vi sử dụng:</strong><br>
                        @if($service->organization_id)
                            <span class="badge bg-primary">
                                <i class="fas fa-building me-1"></i>
                                Dịch vụ riêng của tổ chức
                            </span>
                            @if($service->organization)
                                <p class="mt-1 mb-0 small">
                                    <i class="fas fa-arrow-right me-1"></i>
                                    {{ $service->organization->name }}
                                </p>
                            @endif
                        @else
                            <span class="badge bg-success">
                                <i class="fas fa-globe me-1"></i>
                                Dịch vụ toàn hệ thống
                            </span>
                            <p class="mt-1 mb-0 small text-muted">
                                Dùng chung cho tất cả tổ chức
                            </p>
                        @endif
                    </div>
                    <hr>
                    <p><strong>Mã dịch vụ:</strong> {{ $service->key_code ?? '-' }}</p>
                    <p><strong>Tên dịch vụ:</strong> {{ $service->name }}</p>
                    <p><strong>Loại giá:</strong> 
                        @php
                            $pricingTypes = [
                                'fixed' => 'Cố định',
                                'per_unit' => 'Theo đơn vị',
                                'per_area' => 'Theo diện tích',
                            ];
                        @endphp
                        {{ $pricingTypes[$service->pricing_type] ?? $service->pricing_type ?? 'Cố định' }}
                    </p>
                    <p><strong>Đơn vị:</strong> {{ $service->unit_label ?? 'tháng' }}</p>
                    @if($service->description)
                        <p><strong>Mô tả:</strong> {{ $service->description }}</p>
                    @endif
                    <hr>
                    <p class="small text-muted mb-0">
                        <i class="mdi mdi-clock-outline me-1"></i>
                        Tạo: {{ $service->created_at->format('d/m/Y H:i') }}
                    </p>
                    <p class="small text-muted mb-0">
                        <i class="mdi mdi-update me-1"></i>
                        Cập nhật: {{ $service->updated_at->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

