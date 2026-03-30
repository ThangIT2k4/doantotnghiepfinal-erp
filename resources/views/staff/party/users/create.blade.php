@extends('layouts.staff_dashboard')

@section('title', 'Thêm người dùng mới')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Thêm người dùng mới',
            'subtitle' => 'Tạo tài khoản người dùng mới trong hệ thống',
            'icon' => 'fas fa-user-plus',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.users.index')
                ]
            ]
        ])

        <!-- Create Form -->
        <form id="create-user-form" action="{{ route('staff.users.store') }}" method="POST">
            @csrf
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
                                               value="{{ old('full_name') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="{{ old('email') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Số điện thoại</label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="{{ old('phone') }}">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password" name="password" required>
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
                                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
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
                                            <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Hoạt động</option>
                                            <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Tạm ngưng</option>
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
                                        'label' => 'Lưu',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.users.index')
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    <!-- Card Hướng dẫn -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-question-circle me-2"></i>Hướng dẫn
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Lưu ý quan trọng:</h6>
                                <ul class="mb-0">
                                    <li>Email phải là duy nhất trong hệ thống</li>
                                    <li>Mật khẩu tối thiểu 6 ký tự</li>
                                    <li>Số điện thoại phải là duy nhất (nếu có)</li>
                                    <li>Vai trò sẽ quyết định quyền truy cập</li>
                                </ul>
                            </div>

                            <div class="mt-3">
                                <h6>Vai trò trong hệ thống:</h6>
                                <ul class="list-unstyled">
                                    @foreach($roles as $role)
                                        <li class="mb-1">
                                            <span class="badge bg-info me-2">{{ $role->name }}</span>
                                            <small class="text-muted">{{ $role->key_code }}</small>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
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
    const form = document.getElementById('create-user-form');
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
        .then(async response => {
            // Try to parse JSON response even if status is not OK
            let data;
            try {
                data = await response.json();
            } catch (e) {
                // If response is not JSON, create error object
                const text = await response.text();
                throw new Error(text || `HTTP error! status: ${response.status}`);
            }
            
            if (!response.ok) {
                // If response has error message, throw it
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            
            return data;
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Thành công!');
                setTimeout(() => {
                    window.location.href = '{{ route("staff.users.index") }}';
                }, 1500);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMessage = error.message || 'Không thể tạo người dùng. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.';
            Notify.error(errorMessage, 'Lỗi hệ thống!');
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
