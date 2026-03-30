@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa khách hàng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Sửa thông tin khách hàng',
            'subtitle' => 'Cập nhật thông tin khách hàng: ' . ($tenant->userProfile->full_name ?? $tenant->email),
            'icon' => 'fas fa-user-edit',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.tenants.index')
                ],
                [
                    'variant' => 'info',           // ✅ Solid
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.tenants.show', $tenant->id)
                ]
            ]
        ])

        <!-- Form -->
        <form id="edit-tenant-form" method="POST" action="{{ route('staff.tenants.update', $tenant->id) }}">
            @csrf
            @method('PUT')
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
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="{{ $tenant->full_name }}" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone" class="font-weight-bold">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="{{ $tenant->phone }}" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="font-weight-bold">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" 
                                           value="{{ $tenant->email }}" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password" class="font-weight-bold">Mật khẩu mới</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <small class="form-text text-muted">Để trống nếu không muốn thay đổi mật khẩu</small>
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
                                    <input type="date" class="form-control" id="dob" name="dob" 
                                           value="{{ $tenant->userProfile && $tenant->userProfile->dob ? \Carbon\Carbon::parse($tenant->userProfile->dob)->format('Y-m-d') : '' }}">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="gender" class="font-weight-bold">Giới tính</label>
                                    <select class="form-control" id="gender" name="gender">
                                        <option value="">Chọn giới tính</option>
                                        <option value="male" {{ ($tenant->userProfile->gender ?? '') == 'male' ? 'selected' : '' }}>Nam</option>
                                        <option value="female" {{ ($tenant->userProfile->gender ?? '') == 'female' ? 'selected' : '' }}>Nữ</option>
                                        <option value="other" {{ ($tenant->userProfile->gender ?? '') == 'other' ? 'selected' : '' }}>Khác</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="id_number" class="font-weight-bold">Số CCCD/CMND</label>
                                    <input type="text" class="form-control" id="id_number" name="id_number" 
                                           value="{{ $tenant->userProfile->id_number ?? '' }}">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="address" class="font-weight-bold">Địa chỉ</label>
                                    <textarea class="form-control" id="address" name="address" rows="2">{{ $tenant->userProfile->address ?? '' }}</textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="note" class="font-weight-bold">Ghi chú</label>
                                    <textarea class="form-control" id="note" name="note" rows="3">{{ $tenant->userProfile->note ?? '' }}</textarea>
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
                                        'label' => 'Cập nhật khách hàng',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.tenants.show', $tenant->id)
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    <!-- Card Thông tin hiện tại -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <h5 class="mb-2">{{ $tenant->userProfile->full_name ?? $tenant->email }}</h5>
                                <p class="text-muted">{{ $tenant->phone }}</p>
                            </div>

                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h6 class="font-weight-bold text-primary">{{ $tenant->total_leases ?? 0 }}</h6>
                                        <small class="text-muted">Hợp đồng</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h6 class="font-weight-bold text-success">
                                        {{ number_format($tenant->total_payments ?? 0, 0, ',', '.') }}đ
                                    </h6>
                                    <small class="text-muted">Đã thanh toán</small>
                                </div>
                            </div>

                            <hr>

                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Lưu ý</h6>
                                <ul class="mb-0 small">
                                    <li>Mật khẩu chỉ thay đổi khi nhập mật khẩu mới</li>
                                    <li>Số điện thoại phải duy nhất trong hệ thống</li>
                                    <li>Email phải duy nhất nếu được cung cấp</li>
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
    const form = document.getElementById('edit-tenant-form');
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
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json().then(data => ({ data, status: response.status }));
            }
            return response.text().then(text => ({
                data: { success: false, message: 'Lỗi phản hồi từ server' },
                status: response.status
            }));
        })
        .then(({ data, status }) => {
            // Hide preloader
            if (window.Preloader) {
                window.Preloader.hide();
            }
            
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            
            if (status >= 400) {
                if (data.errors) {
                    showValidationErrors(data.errors);
                    showErrorNotification('Có lỗi validation. Vui lòng kiểm tra lại thông tin.');
                } else {
                    showErrorNotification(data.message || 'Không thể cập nhật khách hàng. Vui lòng kiểm tra lại thông tin.');
                }
                return;
            }
            
            if (data.success) {
                Notify.success(data.message || 'Cập nhật khách hàng thành công!', 'Thành công!');
                setTimeout(() => {
                    // Sử dụng redirect từ response, fallback về show page
                    window.location.href = data.redirect || '{{ route("staff.tenants.show", $tenant->id) }}';
                }, 1500);
            } else {
                if (data.errors) {
                    showValidationErrors(data.errors);
                    showErrorNotification('Có lỗi validation. Vui lòng kiểm tra lại thông tin.');
                } else {
                    showErrorNotification(data.message || 'Không thể cập nhật khách hàng. Vui lòng kiểm tra lại thông tin.');
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
            
            showErrorNotification('Đã xảy ra lỗi khi cập nhật khách hàng. Vui lòng thử lại sau.');
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

function showErrorNotification(message) {
    if (typeof window.Notify !== 'undefined') {
        Notify.error(message, 'Lỗi!');
    } else {
        alert(message);
    }
}
</script>
@endpush
