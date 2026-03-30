@extends('layouts.staff_dashboard')

@section('title', 'Thêm nhân viên mới')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Thêm nhân viên mới',
            'subtitle' => 'Tạo tài khoản nhân viên và gắn bất động sản',
            'icon' => 'fas fa-user-plus',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.staff.index')
                ]
            ]
        ])

        <form action="{{ route('staff.staff.store') }}" method="POST" id="createStaffForm">
            @csrf
            
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-8">
                    <!-- Basic Information -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user"></i> Thông tin cơ bản</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name') }}" required>
                                    @error('full_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                    @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Số điện thoại</label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                                    @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                    @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Tối thiểu 6 ký tự</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Vai trò <span class="text-danger">*</span></label>
                                    <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                                        <option value="">-- Chọn vai trò --</option>
                                        @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select" required>
                                        <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Hoạt động</option>
                                        <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>Tạm ngưng</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Property Assignment -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-building"></i> Gắn bất động sản</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Chọn bất động sản quản lý</label>
                                <select name="properties[]" id="propertySelect" class="form-select" multiple>
                                    @foreach($properties as $property)
                                    <option value="{{ $property->id }}" {{ in_array($property->id, old('properties', [])) ? 'selected' : '' }}>
                                        {{ $property->name }} @if($property->location) - {{ $property->location->city }} @endif
                                    </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Giữ Ctrl để chọn nhiều bất động sản</small>
                            </div>

                            <div id="selectedProperties" class="mt-3">
                                <!-- Selected properties will be displayed here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-4">
                    <!-- Quick Actions -->
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
                                        'url' => route('staff.staff.index')
                                    ]
                                ]
                            ])
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="card shadow-sm border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-info-circle"></i> Hướng dẫn</h6>
                        </div>
                        <div class="card-body">
                            <ul class="small mb-0">
                                <li>Các trường có dấu <span class="text-danger">*</span> là bắt buộc</li>
                                <li>Email phải là duy nhất trong hệ thống</li>
                                <li>Mật khẩu tối thiểu 6 ký tự</li>
                                <li>Có thể gắn nhiều bất động sản cùng lúc</li>
                                <li>Các cấu hình nghiệp vụ nâng cao có thể thiết lập sau khi tạo</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container--default .select2-selection--multiple {
    min-height: 100px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for property selection
    $('#propertySelect').select2({
        placeholder: 'Chọn bất động sản...',
        allowClear: true,
        width: '100%'
    });

    // Handle form submission
    document.getElementById('createStaffForm').addEventListener('submit', function(e) {
        e.preventDefault();

        if (window.Preloader) {
            window.Preloader.show();
        }

        const formData = new FormData(this);

        fetch(this.action, {
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
                return response.json();
            }
            // If not JSON, return error
            return { success: false, message: 'Lỗi phản hồi từ server' };
        })
        .then(data => {
            if (window.Preloader) {
                window.Preloader.hide();
            }

            if (data.success || data.redirect) {
                if (typeof Notify !== 'undefined') {
                    Notify.success(data.message || 'Tạo nhân viên thành công!');
                } else if (typeof window.Notify !== 'undefined') {
                    window.Notify.success(data.message || 'Tạo nhân viên thành công!');
                } else {
                    alert(data.message || 'Tạo nhân viên thành công!');
                }
                setTimeout(() => {
                    window.location.href = data.redirect || '{{ route("staff.staff.index") }}';
                }, 1500);
            } else {
                // Show validation errors if any
                if (data.errors) {
                    let errorMessage = 'Lỗi validation:\n';
                    for (let field in data.errors) {
                        errorMessage += `- ${data.errors[field][0]}\n`;
                    }
                    if (typeof Notify !== 'undefined') {
                        Notify.error(errorMessage);
                    } else if (typeof window.Notify !== 'undefined') {
                        window.Notify.error(errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                } else {
                    if (typeof Notify !== 'undefined') {
                        Notify.error(data.message || 'Không thể tạo nhân viên. Vui lòng kiểm tra lại thông tin.');
                    } else if (typeof window.Notify !== 'undefined') {
                        window.Notify.error(data.message || 'Không thể tạo nhân viên. Vui lòng kiểm tra lại thông tin.');
                    } else {
                        alert(data.message || 'Không thể tạo nhân viên. Vui lòng kiểm tra lại thông tin.');
                    }
                }
            }
        })
        .catch(error => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
            console.error('Error:', error);
            if (typeof Notify !== 'undefined') {
                Notify.error('Đã xảy ra lỗi khi tạo nhân viên. Vui lòng thử lại.');
            } else if (typeof window.Notify !== 'undefined') {
                window.Notify.error('Đã xảy ra lỗi khi tạo nhân viên. Vui lòng thử lại.');
            } else {
                alert('Đã xảy ra lỗi khi tạo nhân viên. Vui lòng thử lại.');
            }
        });
    });

    // Display selected properties
    $('#propertySelect').on('change', function() {
        const selectedOptions = $(this).find(':selected');
        const container = document.getElementById('selectedProperties');
        
        if (selectedOptions.length > 0) {
            let html = '<div class="alert alert-info"><strong>Đã chọn:</strong><ul class="mb-0 mt-2">';
            selectedOptions.each(function() {
                html += `<li>${$(this).text()}</li>`;
            });
            html += '</ul></div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '';
        }
    });
});
</script>
@endpush
@endsection


