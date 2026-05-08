@extends('layouts.superadmin')

@section('title', 'Chỉnh sửa Tổ chức')
@section('subtitle', 'Cập nhật thông tin tổ chức: ' . $organization->name)

@section('content')
<div class="page-header">
    <div class="header-left">
        <h1 class="page-title">
            <i class="fas fa-edit"></i>
            Chỉnh sửa Tổ chức
        </h1>
        <p class="page-subtitle">Cập nhật thông tin tổ chức: {{ $organization->name }}</p>
    </div>
    <div class="header-right">
        <div class="btn-group">
            <a href="{{ route('superadmin.organizations.show', $organization) }}" class="btn btn-outline-info">
                <i class="fas fa-eye"></i>
                Xem chi tiết
            </a>
            <a href="{{ route('superadmin.organizations.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i>
                Quay lại
            </a>
        </div>
    </div>
</div>

    <div class="content-body">
        <div class="form-card">
            <form id="organizationForm" method="POST" action="{{ route('superadmin.organizations.update', $organization) }}">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-lg-8">
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Thông tin cơ bản
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="required">Tên tổ chức</label>
                                        <input type="text" 
                                               name="name" 
                                               id="name" 
                                               class="form-control @error('name') is-invalid @enderror" 
                                               value="{{ old('name', $organization->name) }}" 
                                               required>
                                        @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="required">Email</label>
                                        <input type="email" 
                                               name="email" 
                                               id="email" 
                                               class="form-control @error('email') is-invalid @enderror" 
                                               value="{{ old('email', $organization->email) }}" 
                                               required>
                                        @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Số điện thoại</label>
                                        <input type="text" 
                                               name="phone" 
                                               id="phone" 
                                               class="form-control @error('phone') is-invalid @enderror" 
                                               value="{{ old('phone', $organization->phone) }}">
                                        @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status" class="required">Trạng thái</label>
                                        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                            <option value="1" {{ old('status', $organization->status) == '1' ? 'selected' : '' }}>Hoạt động</option>
                                            <option value="0" {{ old('status', $organization->status) == '0' ? 'selected' : '' }}>Tạm dừng</option>
                                        </select>
                                        @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Địa chỉ</label>
                                <textarea name="address" 
                                          id="address" 
                                          class="form-control @error('address') is-invalid @enderror" 
                                          rows="3">{{ old('address', $organization->address) }}</textarea>
                                @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Mô tả</label>
                                <textarea name="description" 
                                          id="description" 
                                          class="form-control @error('description') is-invalid @enderror" 
                                          rows="4" 
                                          placeholder="Mô tả về tổ chức...">{{ old('description', $organization->description) }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- Settings -->
                    <div class="col-lg-4">
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-cogs"></i>
                                Cài đặt
                            </h5>
                            
                            <div class="settings-group">
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               name="settings[allow_registration]" 
                                               id="allow_registration" 
                                               class="form-check-input" 
                                               value="1" 
                                               {{ old('settings.allow_registration', $organization->settings['allow_registration'] ?? false) ? 'checked' : '' }}>
                                        <label for="allow_registration" class="form-check-label">
                                            Cho phép đăng ký
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Cho phép người dùng tự đăng ký vào tổ chức</small>
                                </div>
                                
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               name="settings[auto_approve]" 
                                               id="auto_approve" 
                                               class="form-check-input" 
                                               value="1" 
                                               {{ old('settings.auto_approve', $organization->settings['auto_approve'] ?? false) ? 'checked' : '' }}>
                                        <label for="auto_approve" class="form-check-label">
                                            Tự động phê duyệt
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Tự động phê duyệt yêu cầu đăng ký</small>
                                </div>
                                
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               name="settings[email_notifications]" 
                                               id="email_notifications" 
                                               class="form-check-input" 
                                               value="1" 
                                               {{ old('settings.email_notifications', $organization->settings['email_notifications'] ?? true) ? 'checked' : '' }}>
                                        <label for="email_notifications" class="form-check-label">
                                            Thông báo email
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Gửi thông báo qua email</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Organization Stats -->
                        <div class="form-section">
                            <h5 class="section-title">
                                <i class="fas fa-chart-bar"></i>
                                Thống kê
                            </h5>
                            
                            <div class="stats-group">
                                <div class="stat-item">
                                    <div class="stat-icon bg-primary">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h4>{{ $organization->users->count() }}</h4>
                                        <p>Người dùng</p>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-icon bg-success">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h4>{{ $organization->properties->count() }}</h4>
                                        <p>Tài sản</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
                                <i class="fas fa-save"></i>
                                Cập nhật Tổ chức
                            </button>
                            <a href="{{ route('superadmin.organizations.show', $organization) }}" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="fas fa-times"></i>
                                Hủy
                            </a>
                        </div>
                    </div>
                </div>
            </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.form-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 2rem;
}

.form-section {
    margin-bottom: 2rem;
}

.section-title {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label.required::after {
    content: ' *';
    color: #dc3545;
}

.settings-group {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.form-check {
    margin-bottom: 1rem;
}

.form-check-label {
    font-weight: 500;
    color: #495057;
}

.form-text {
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

.stats-group {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #e9ecef;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.stat-content h4 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
}

.stat-content p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.form-actions {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    margin-top: 2rem;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
}

#submitBtn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('organizationForm');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function(e) {
        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...';
        
        // Show loading notification
        if (typeof Notify !== 'undefined') {
            Notify.toast('Đang cập nhật tổ chức...', 'info');
        }
    });
    
    // Handle form validation errors
    @if($errors->any())
        if (typeof Notify !== 'undefined') {
            Notify.toast('Vui lòng kiểm tra lại thông tin', 'error');
        }
    @endif
});
</script>
@endpush
