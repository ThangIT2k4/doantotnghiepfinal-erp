@extends('layouts.superadmin')

@section('title', 'Chỉnh sửa Người dùng')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}">
<style>
    .form-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .section-title {
        color: #495057;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #dee2e6;
    }
    
    .organization-role-item {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }
    
    .organization-role-item:hover {
        border-color: #007bff;
        box-shadow: 0 2px 8px rgba(0,123,255,0.1);
    }
    
    .remove-org-btn {
        color: #dc3545;
        cursor: pointer;
        transition: color 0.3s ease;
    }
    
    .remove-org-btn:hover {
        color: #c82333;
    }
    
    .add-org-btn {
        border: 2px dashed #dee2e6;
        background: transparent;
        color: #6c757d;
        transition: all 0.3s ease;
    }
    
    .add-org-btn:hover {
        border-color: #007bff;
        color: #007bff;
        background: rgba(0,123,255,0.05);
    }
    
    .user-info-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="user-info-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-2">
                    <i class="fas fa-user-edit me-2"></i>Chỉnh sửa Người dùng
                </h1>
                <p class="mb-0">{{ $user->full_name }} ({{ $user->email }})</p>
            </div>
            <a href="{{ route('superadmin.users.show', $user) }}" class="btn btn-light">
                <i class="fas fa-eye me-2"></i>Xem chi tiết
            </a>
        </div>
    </div>

    <form id="editUserForm">
        @csrf
        @method('PUT')
        
        <!-- Basic Information -->
        <div class="form-section">
            <h5 class="section-title">
                <i class="fas fa-user me-2"></i>Thông tin cơ bản
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="{{ $user->full_name }}" required>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="{{ $user->email }}" required>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" id="phone" name="phone" 
                               value="{{ $user->phone }}">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Để trống nếu không muốn thay đổi">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="1" {{ $user->status ? 'selected' : '' }}>Hoạt động</option>
                            <option value="0" {{ !$user->status ? 'selected' : '' }}>Tạm dừng</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Organizations and Roles -->
        <div class="form-section">
            <h5 class="section-title">
                <i class="fas fa-building me-2"></i>Tổ chức và Vai trò
            </h5>
            <div id="organizationsContainer">
                @foreach($user->organizations as $index => $org)
                <div class="organization-role-item" id="org-item-{{ $index + 1 }}">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="form-label">Tổ chức</label>
                            <select class="form-select" name="organizations[]" required>
                                <option value="">Chọn tổ chức</option>
                                @forelse($organizations as $organization)
                                    <option value="{{ $organization->id }}" 
                                            {{ $org->id == $organization->id ? 'selected' : '' }}>
                                        {{ $organization->name }}
                                    </option>
                                @empty
                                    <option value="" disabled>Không có tổ chức nào</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Vai trò trong tổ chức</label>
                            <select class="form-select" name="org_roles[]">
                                @forelse($roles as $role)
                                    <option value="{{ $role->id }}" 
                                            {{ $org->pivot->role_id == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @empty
                                    <option value="" disabled>Không có vai trò nào</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                        onclick="removeOrganization({{ $index + 1 }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <button type="button" class="btn add-org-btn w-100 py-3" onclick="addOrganization()">
                <i class="fas fa-plus me-2"></i>Thêm tổ chức
            </button>
        </div>

        <!-- Global Roles -->
        <div class="form-section">
            <h5 class="section-title">
                <i class="fas fa-shield-alt me-2"></i>Vai trò toàn cục
            </h5>
            <div class="row">
                @forelse($roles as $role)
                <div class="col-md-4 col-lg-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="roles[]" 
                               value="{{ $role->id }}" id="role_{{ $role->id }}"
                               {{ $user->userRoles->contains($role->id) ? 'checked' : '' }}>
                        <label class="form-check-label" for="role_{{ $role->id }}">
                            {{ $role->name }}
                        </label>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Không có vai trò nào được tìm thấy. Vui lòng tạo vai trò trước khi chỉnh sửa người dùng.
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('superadmin.users.show', $user) }}" class="btn btn-outline-secondary">
                <i class="fas fa-times me-2"></i>Hủy
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Cập nhật Người dùng
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
// Debug notification system
console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
console.log('Notify available:', typeof Notify !== 'undefined');
if (typeof Notify !== 'undefined') {
    console.log('Notify methods:', Object.getOwnPropertyNames(Notify));
}
</script>
<script>
let organizationCount = {{ $user->organizations->count() }};

// Add organization function
function addOrganization() {
    organizationCount++;
    const container = document.getElementById('organizationsContainer');
    
    const orgItem = document.createElement('div');
    orgItem.className = 'organization-role-item';
    orgItem.id = `org-item-${organizationCount}`;
    
    orgItem.innerHTML = `
        <div class="row align-items-center">
            <div class="col-md-5">
                <label class="form-label">Tổ chức</label>
                <select class="form-select" name="organizations[]" required>
                    <option value="">Chọn tổ chức</option>
                    @forelse($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @empty
                        <option value="" disabled>Không có tổ chức nào</option>
                    @endforelse
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Vai trò trong tổ chức</label>
                <select class="form-select" name="org_roles[]">
                    @forelse($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @empty
                        <option value="" disabled>Không có vai trò nào</option>
                    @endforelse
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeOrganization(${organizationCount})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(orgItem);
}

// Remove organization function
function removeOrganization(id) {
    const item = document.getElementById(`org-item-${id}`);
    if (item) {
        item.remove();
    }
}

// Form submission
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (typeof Notify !== 'undefined') {
        Notify.info('Đang cập nhật người dùng...', 'Đang xử lý');
    }
    
    const formData = new FormData(this);
    
    fetch('{{ route("superadmin.users.update", $user) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof Notify !== 'undefined') {
                Notify.success(data.message, 'Thành công');
            } else {
                alert(data.message);
            }
            
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            }
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message, 'Lỗi');
            } else {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Update User Error:', error);
        if (typeof Notify !== 'undefined') {
            Notify.error('Có lỗi xảy ra khi cập nhật người dùng', 'Lỗi');
        } else {
            alert('Có lỗi xảy ra khi cập nhật người dùng');
        }
    });
});
</script>
@endpush
