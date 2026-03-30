@extends('layouts.staff_dashboard')

@section('title', 'Thêm dịch vụ')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('staff.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('staff.services.index') }}">Quản lý dịch vụ</a></li>
                        <li class="breadcrumb-item active">Thêm dịch vụ</li>
                    </ol>
                </div>
                <h4 class="page-title">Thêm dịch vụ</h4>
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
                        <i class="mdi mdi-plus-circle me-2"></i>
                        Thông tin dịch vụ
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.services.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="key_code" class="form-label">Mã dịch vụ</label>
                            <input type="text" class="form-control @error('key_code') is-invalid @enderror" 
                                   id="key_code" name="key_code" 
                                   value="{{ old('key_code') }}" 
                                   placeholder="VD: ELEC, WATER, INTERNET">
                            @error('key_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Mã định danh dịch vụ (tùy chọn). Nếu không nhập, hệ thống sẽ tự động tạo.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Tên dịch vụ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" 
                                   value="{{ old('name') }}" 
                                   placeholder="VD: Điện, Nước, Internet" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Tên hiển thị của dịch vụ
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pricing_type" class="form-label">Loại giá</label>
                            <select class="form-select @error('pricing_type') is-invalid @enderror" 
                                    id="pricing_type" name="pricing_type">
                                <option value="fixed" {{ old('pricing_type', 'fixed') == 'fixed' ? 'selected' : '' }}>Cố định</option>
                                <option value="per_unit" {{ old('pricing_type') == 'per_unit' ? 'selected' : '' }}>Theo đơn vị</option>
                                <option value="per_area" {{ old('pricing_type') == 'per_area' ? 'selected' : '' }}>Theo diện tích</option>
                            </select>
                            @error('pricing_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Loại tính giá cho dịch vụ này
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="unit_label" class="form-label">Nhãn đơn vị</label>
                            <input type="text" class="form-control @error('unit_label') is-invalid @enderror" 
                                   id="unit_label" name="unit_label" 
                                   value="{{ old('unit_label', 'tháng') }}" 
                                   placeholder="VD: tháng, kWh, m³">
                            @error('unit_label')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Đơn vị tính cho dịch vụ (VD: tháng, kWh, m³, số)
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" 
                                      rows="3" 
                                      placeholder="Mô tả chi tiết về dịch vụ">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @php
                            $user = Auth::user();
                            $currentOrgId = $user->getCurrentOrganizationId();
                        @endphp
                        
                        @if(!$currentOrgId)
                            {{-- User not in any organization - can create global services --}}
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" 
                                           id="is_global" name="is_global" 
                                           value="1" {{ old('is_global', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_global">
                                        <i class="fas fa-globe me-1"></i>
                                        Dịch vụ toàn hệ thống
                                    </label>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Nếu bật: dịch vụ này sẽ được dùng chung cho tất cả tổ chức.<br>
                                    Nếu tắt: dịch vụ chỉ dùng riêng cho tổ chức hiện tại.
                                </div>
                            </div>
                        @else
                            {{-- User in organization - creates org-specific services --}}
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Lưu ý:</strong> Dịch vụ bạn tạo sẽ chỉ được sử dụng cho tổ chức của bạn.
                            </div>
                        @endif

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-1"></i>
                                Lưu
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
                        Hướng dẫn
                    </h5>
                </div>
                <div class="card-body">
                    <h6>Loại giá:</h6>
                    <ul class="small">
                        <li><strong>Cố định:</strong> Giá cố định mỗi tháng (VD: phí quản lý)</li>
                        <li><strong>Theo đơn vị:</strong> Giá tính theo số lượng đơn vị (VD: điện, nước)</li>
                        <li><strong>Theo diện tích:</strong> Giá tính theo diện tích (VD: phí vệ sinh)</li>
                    </ul>
                    
                    <h6 class="mt-3">Nhãn đơn vị:</h6>
                    <ul class="small">
                        <li>Điện: <code>kWh</code></li>
                        <li>Nước: <code>m³</code></li>
                        <li>Internet: <code>tháng</code></li>
                        <li>Phí quản lý: <code>tháng</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

