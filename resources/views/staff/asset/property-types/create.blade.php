@extends('layouts.staff_dashboard')

@section('title', 'Thêm Loại Bất động sản')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Thêm Loại Bất động sản mới',
            'subtitle' => 'Nhập thông tin loại bất động sản',
            'icon' => 'fas fa-plus',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.property-types.index')
                ]
            ]
        ])

        {{-- 2. Form với Layout 2 Cột --}}
        <form id="propertyTypeForm" method="POST" action="{{ route('staff.property-types.store') }}">
            @csrf
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    {{-- Card 1: Thông tin cơ bản --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="key_code" class="form-label">
                                            Mã Code <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="key_code" id="key_code" class="form-control @error('key_code') is-invalid @enderror" 
                                               required placeholder="vd: phong_tro, chung_cu_mini" value="{{ old('key_code') }}">
                                        <small class="form-text text-muted">Mã định danh duy nhất cho loại bất động sản (không dấu, viết thường)</small>
                                        @error('key_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">
                                            Tên loại BĐS <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                               required placeholder="vd: Phòng trọ, Chung cư mini" value="{{ old('name') }}">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="icon" class="form-label">Icon Font Awesome</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-icons"></i></span>
                                            <input type="text" name="icon" id="iconInput" class="form-control @error('icon') is-invalid @enderror" 
                                                   placeholder="vd: fas fa-building, fas fa-home" value="{{ old('icon') }}">
                                            <span class="input-group-text" id="iconPreview"><i class="fas fa-building"></i></span>
                                        </div>
                                        <small class="form-text text-muted">Class icon Font Awesome (tùy chọn)</small>
                                        @error('icon')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Trạng thái hoạt động</label>
                                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                            <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Hoạt động</option>
                                            <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>Tạm ngưng</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Mô tả</label>
                                <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" 
                                          rows="3" placeholder="Mô tả chi tiết về loại bất động sản...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @php
                                $user = Auth::user();
                                $currentOrgId = $user->getCurrentOrganizationId();
                            @endphp
                            
                            @if(!$currentOrgId)
                                {{-- User not in any organization - can create global property types --}}
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" 
                                               id="is_global" name="is_global" 
                                               value="1" {{ old('is_global', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_global">
                                            <i class="fas fa-globe me-1"></i>
                                            Loại BĐS toàn hệ thống
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Nếu bật: loại BĐS này sẽ được dùng chung cho tất cả tổ chức.<br>
                                        Nếu tắt: loại BĐS chỉ dùng riêng cho tổ chức hiện tại.
                                    </div>
                                </div>
                            @else
                                {{-- User in organization - creates org-specific property types --}}
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Lưu ý:</strong> Loại bất động sản bạn tạo sẽ chỉ được sử dụng cho tổ chức của bạn.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Cột phải: Sidebar (col-lg-4) --}}
                <div class="col-lg-4">
                    {{-- Card Thao tác (chứa action-buttons với layout dọc) --}}
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
                                        'label' => 'Lưu loại BĐS',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.property-types.index')
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    {{-- Card Hướng dẫn --}}
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-question-circle me-2"></i>Hướng dẫn
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle me-2"></i>Lưu ý quan trọng:</h6>
                                <ul class="mb-0 small">
                                    <li>Mã Code phải là duy nhất và không có dấu</li>
                                    <li>Tên loại BĐS sẽ được hiển thị trong hệ thống</li>
                                    <li>Icon giúp dễ dàng nhận biết loại bất động sản</li>
                                    <li>Mô tả giúp giải thích chi tiết về loại bất động sản</li>
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
    const form = document.getElementById('propertyTypeForm');
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
            if (typeof window.Notify !== 'undefined') {
                Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
            } else {
                alert('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.');
            }
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
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }
            
            if (data.success) {
                if (typeof window.Notify !== 'undefined') {
                    Notify.success(data.message || 'Đã tạo loại bất động sản thành công!', 'Thành công!');
                } else {
                    alert('Đã tạo loại bất động sản thành công!');
                }
                setTimeout(() => {
                    const redirectUrl = data.redirect || '{{ route("staff.property-types.index") }}';
                    window.location.href = redirectUrl;
                }, 1500);
            } else {
                if (typeof window.Notify !== 'undefined') {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                } else {
                    alert('Có lỗi xảy ra: ' + (data.message || 'Lỗi không xác định'));
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof window.Notify !== 'undefined') {
                Notify.error('Có lỗi xảy ra khi tạo loại bất động sản: ' + error.message, 'Lỗi hệ thống!');
            } else {
                alert('Có lỗi xảy ra khi tạo loại bất động sản: ' + error.message);
            }
        })
        .finally(() => {
            // Hide preloader
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
});

// Auto-generate key_code from name
document.querySelector('input[name="name"]').addEventListener('input', function() {
    const nameInput = this;
    const keyCodeInput = document.querySelector('input[name="key_code"]');
    
    if (!keyCodeInput.value || !keyCodeInput.dataset.userModified) {
        // Convert Vietnamese to non-accented
        const keyCode = nameInput.value
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '_')
            .trim();
        keyCodeInput.value = keyCode;
    }
});

// Mark key_code as user-modified if user manually edits it
document.querySelector('input[name="key_code"]').addEventListener('input', function() {
    this.dataset.userModified = 'true';
});

// Icon preview
document.getElementById('iconInput').addEventListener('input', function() {
    const iconPreview = document.getElementById('iconPreview');
    const iconValue = this.value.trim();
    if (iconValue) {
        iconPreview.innerHTML = `<i class="${iconValue}"></i>`;
    } else {
        iconPreview.innerHTML = '<i class="fas fa-building"></i>';
    }
});
</script>
@endpush
