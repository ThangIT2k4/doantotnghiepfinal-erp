@extends('layouts.staff_dashboard')

@section('title', 'Thêm khách hàng mới')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Thêm khách hàng mới',
            'subtitle' => 'Tạo tài khoản khách hàng mới trong hệ thống',
            'icon' => 'fas fa-user-plus',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.tenants.index')
                ]
            ]
        ])

        <!-- Form -->
        <form id="create-tenant-form" method="POST" action="{{ route('staff.tenants.store') }}">
            @csrf
            <div class="row">
                <!-- Cột trái: Form chính (col-lg-8) -->
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-user me-2"></i>Thông tin khách hàng
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="full_name" class="font-weight-bold">Họ và tên <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('full_name') is-invalid @enderror" id="full_name" name="full_name" value="{{ old('full_name') }}" required>
                                    @error('full_name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone" class="font-weight-bold">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" required>
                                    @error('phone')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="font-weight-bold">Email  <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password" class="font-weight-bold">Mật khẩu <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <hr>
                        <h6 class="font-weight-bold text-primary mb-3">Thông tin cá nhân</h6>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="dob" class="font-weight-bold">Ngày sinh</label>
                                    <input type="date" class="form-control" id="dob" name="dob">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="gender" class="font-weight-bold">Giới tính</label>
                                    <select class="form-control" id="gender" name="gender">
                                        <option value="">Chọn giới tính</option>
                                        <option value="male">Nam</option>
                                        <option value="female">Nữ</option>
                                        <option value="other">Khác</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="id_number" class="font-weight-bold">Số CCCD/CMND</label>
                                    <input type="text" class="form-control" id="id_number" name="id_number">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address" class="font-weight-bold">Địa chỉ</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="note" class="font-weight-bold">Ghi chú</label>
                                    <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        </div>
                    </div>
                </div>
                
                <!-- Cột phải: Sidebar (col-lg-4) -->
                <div class="col-lg-4">
                    <!-- Card Thao tác (chứa action-buttons với layout dọc) -->
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
                                        'label' => 'Lưu khách hàng',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.tenants.index')
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
                                <h6><i class="fas fa-info-circle"></i> Thông tin cần thiết</h6>
                                <ul class="mb-0 small">
                                    <li>Họ và tên và số điện thoại là bắt buộc</li>
                                    <li>Mật khẩu tối thiểu 6 ký tự</li>
                                    <li>Số điện thoại phải duy nhất trong hệ thống</li>
                                    <li>Email phải duy nhất nếu được cung cấp</li>
                                    <li>Khách hàng sẽ được tự động thêm vào tổ chức hiện tại</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('create-tenant-form');
    if (!form) return;
    
    // Form submission with AJAX
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous validation
        clearValidation();
        
        // Disable submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        
        // Show preloader if available
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json().then(data => {
                    return { data, status: response.status };
                });
            }
            
            // If not JSON, try to get text
            return response.text().then(text => {
                return { 
                    data: { success: false, message: 'Lỗi phản hồi từ server (không phải JSON)' },
                    status: response.status 
                };
            });
        })
        .then(({ data, status }) => {
            // Hide preloader
            if (window.Preloader) {
                window.Preloader.hide();
            }
            
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            
            // Check if response is error status
            if (status >= 400) {
                // Show validation errors if any
                if (data.errors) {
                    showValidationErrors(data.errors);
                    
                    // Show error notification
                    let errorMessage = 'Có lỗi validation:\n';
                    for (let field in data.errors) {
                        errorMessage += `- ${data.errors[field][0]}\n`;
                    }
                    
                    showErrorNotification(errorMessage);
                } else {
                    // Show general error
                    showErrorNotification(data.message || 'Không thể tạo khách hàng. Vui lòng kiểm tra lại thông tin.');
                }
                return;
            }
            
            if (data.success) {
                // Show success notification
                showSuccessNotification(data.message || 'Tạo khách hàng thành công!');
                
                // Redirect if specified
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    setTimeout(() => {
                        window.location.href = '{{ route("staff.tenants.index") }}';
                    }, 1500);
                }
            } else {
                // Show validation errors if any
                if (data.errors) {
                    showValidationErrors(data.errors);
                    
                    // Show error notification
                    let errorMessage = 'Có lỗi validation:\n';
                    for (let field in data.errors) {
                        errorMessage += `- ${data.errors[field][0]}\n`;
                    }
                    
                    showErrorNotification(errorMessage);
                } else {
                    // Show general error
                    showErrorNotification(data.message || 'Không thể tạo khách hàng. Vui lòng kiểm tra lại thông tin.');
                }
            }
        })
        .catch(error => {
            // Hide preloader
            if (window.Preloader) {
                window.Preloader.hide();
            }
            
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            
            showErrorNotification('Đã xảy ra lỗi khi tạo khách hàng. Vui lòng thử lại sau.');
        });
    });
});

function clearValidation() {
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
}

function showValidationErrors(errors) {
    Object.keys(errors).forEach(field => {
        const input = document.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('is-invalid');
            let feedbackElement = input.parentElement.querySelector('.invalid-feedback');
            if (!feedbackElement) {
                feedbackElement = document.createElement('div');
                feedbackElement.className = 'invalid-feedback';
                input.parentElement.appendChild(feedbackElement);
            }
            feedbackElement.textContent = errors[field][0];
        }
    });
}

function showSuccessNotification(message) {
    if (typeof window.Notify !== 'undefined') {
        Notify.success(message, 'Thành công!');
    } else {
        alert(message);
    }
}

function showErrorNotification(message) {
    if (typeof window.Notify !== 'undefined') {
        Notify.error(message, 'Lỗi!');
    } else {
        alert(message);
    }
}
</script>
@endpush
