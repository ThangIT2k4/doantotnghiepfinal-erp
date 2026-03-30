@extends('layouts.staff_dashboard')

@section('title', 'Sửa thông tin người dùng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Sửa thông tin người dùng',
            'subtitle' => 'Cập nhật thông tin tài khoản: ' . ($targetUser->userProfile->full_name ?? $targetUser->email),
            'icon' => 'fas fa-user-edit',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.users.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.users.show', $targetUser->id)
                ]
            ]
        ])

        <!-- Edit Form -->
        <form id="edit-user-form" action="{{ route('staff.users.update', $targetUser->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-user me-2"></i>Thông tin người dùng
                            </h6>
                        </div>
                        <div class="card-body">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="{{ old('full_name', $targetUser->userProfile->full_name ?? '') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="{{ old('email', $targetUser->email) }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Số điện thoại</label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="{{ old('phone', $targetUser->phone) }}">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Mật khẩu mới</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                        <div class="form-text">Để trống nếu không muốn thay đổi mật khẩu</div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="role_id" class="form-label">Vai trò <span class="text-danger">*</span></label>
                                        <select class="form-select" id="role_id" name="role_id" required>
                                            <option value="">Chọn vai trò</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" 
                                                        {{ old('role_id', $targetUser->userRoles->first()?->id) == $role->id ? 'selected' : '' }}>
                                                    {{ $role->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Trạng thái</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="1" {{ old('status', $targetUser->status) == '1' ? 'selected' : '' }}>Hoạt động</option>
                                            <option value="0" {{ old('status', $targetUser->status) == '0' ? 'selected' : '' }}>Tạm ngưng</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Card Thao tác -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-cogs me-2"></i>Thao tác
                            </h6>
                        </div>
                        <div class="card-body">
                            @include('staff.components.action-buttons', [
                                'layout' => 'vertical',
                                'size' => 'md',
                                'actions' => [
                                    [
                                        'type' => 'submit',
                                        'variant' => 'primary',
                                        'label' => 'Cập nhật',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.users.show', $targetUser->id)
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    <!-- User Info Card -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                            </h6>
                        </div>
                        <div class="card-body">
                        <div class="mb-3">
                            <h6 class="mb-0">{{ $targetUser->userProfile->full_name ?? $targetUser->email }}</h6>
                            <small class="text-muted">{{ $targetUser->email }}</small>
                        </div>

                        <div class="mb-3">
                            <h6>Vai trò hiện tại:</h6>
                            @if($targetUser->userRoles->count() > 0)
                                @foreach($targetUser->userRoles as $role)
                                    <span class="badge bg-info">{{ $role->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">Chưa có vai trò</span>
                            @endif
                        </div>

                        <div class="mb-3">
                            <h6>Trạng thái:</h6>
                            @if($targetUser->status)
                                <span class="badge bg-success">Hoạt động</span>
                            @else
                                <span class="badge bg-warning">Tạm ngưng</span>
                            @endif
                        </div>

                        <div class="mb-3">
                            <h6>Thông tin khác:</h6>
                            <ul class="list-unstyled mb-0">
                                <li><small class="text-muted">ID: #{{ $targetUser->id }}</small></li>
                                <li><small class="text-muted">Tạo lúc: {{ $targetUser->created_at->format('d/m/Y H:i') }}</small></li>
                                @if($targetUser->last_login_at)
                                    <li><small class="text-muted">Đăng nhập cuối: {{ $targetUser->last_login_at->format('d/m/Y H:i') }}</small></li>
                                @endif
                            </ul>
                        </div>

                        @if($targetUser->id === auth()->id())
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Bạn đang chỉnh sửa tài khoản của chính mình
                            </div>
                        @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-user-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show preloader
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        const formData = new FormData(this);
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token not found');
            Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
            if (window.Preloader) {
                window.Preloader.hide();
            }
            return;
        }

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Thành công!');
                setTimeout(() => {
                    window.location.href = data.redirect || '{{ route("staff.users.show", $targetUser->id) }}';
                }, 1500);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể cập nhật người dùng: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
});
</script>
@endpush
@endsection
