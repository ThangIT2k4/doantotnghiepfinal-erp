@extends('layouts.staff_dashboard')

@section('title', 'Sửa Loại Bất động sản')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Sửa Loại Bất động sản',
            'subtitle' => 'Cập nhật thông tin loại bất động sản',
            'icon' => 'fas fa-edit',
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
        <form id="propertyTypeForm" method="POST" action="{{ route('staff.property-types.update', $propertyType->id) }}">
            @csrf
            @method('PUT')
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
                                               required value="{{ old('key_code', $propertyType->key_code) }}">
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
                                               required value="{{ old('name', $propertyType->name) }}">
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
                                                   placeholder="vd: fas fa-building, fas fa-home" value="{{ old('icon', $propertyType->icon) }}">
                                            <span class="input-group-text" id="iconPreview">
                                                @if ($propertyType->icon)
                                                    <i class="{{ $propertyType->icon }}"></i>
                                                @else
                                                    <i class="fas fa-building"></i>
                                                @endif
                                            </span>
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
                                            <option value="1" {{ old('status', $propertyType->status) == 1 ? 'selected' : '' }}>Hoạt động</option>
                                            <option value="0" {{ old('status', $propertyType->status) == 0 ? 'selected' : '' }}>Tạm ngưng</option>
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
                                          rows="3">{{ old('description', $propertyType->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Card 2: Property Count Info --}}
                    @if ($propertyType->properties_count > 0)
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin sử dụng
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Lưu ý:</strong> Loại bất động sản này đang được sử dụng bởi {{ $propertyType->properties_count }} bất động sản.
                            </div>
                        </div>
                    </div>
                    @endif
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
                                        'label' => 'Cập nhật',
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
                    Notify.success(data.message || 'Đã cập nhật loại bất động sản thành công!', 'Thành công!');
                } else {
                    alert('Đã cập nhật loại bất động sản thành công!');
                }
                setTimeout(() => {
                    const redirectUrl = data.redirect || '{{ route("staff.property-types.show", $propertyType->id) }}';
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
                Notify.error('Có lỗi xảy ra khi cập nhật loại bất động sản: ' + error.message, 'Lỗi hệ thống!');
            } else {
                alert('Có lỗi xảy ra khi cập nhật loại bất động sản: ' + error.message);
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
