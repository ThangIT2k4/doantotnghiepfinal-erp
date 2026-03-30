@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa phòng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" và "Xem chi tiết" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Chỉnh sửa phòng',
            'subtitle' => 'Cập nhật thông tin phòng: ' . $unit->code,
            'icon' => 'fas fa-edit',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.units.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.units.show', $unit->id)
                ]
            ]
        ])

        {{-- 2. Form với Layout 2 Cột --}}
        <form id="unitForm" method="POST" action="{{ route('staff.units.update', $unit->id) }}">
            @csrf
            @method('PUT')
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-building me-2"></i>Thông tin phòng
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                {{-- Property Selection --}}
                                <div class="col-md-6">
                                    <label for="property_id" class="form-label">
                                        Bất động sản <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('property_id') is-invalid @enderror" 
                                            id="property_id" 
                                            name="property_id" 
                                            required>
                                        <option value="">Chọn bất động sản</option>
                                        @foreach($properties as $property)
                                            <option value="{{ $property->id }}" 
                                                    {{ old('property_id', $unit->property_id) == $property->id ? 'selected' : '' }}>
                                                {{ $property->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('property_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Unit Code --}}
                                <div class="col-md-6">
                                    <label for="code" class="form-label">
                                        Mã phòng <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('code') is-invalid @enderror" 
                                           id="code" 
                                           name="code" 
                                           value="{{ old('code', $unit->code) }}" 
                                           placeholder="VD: A101, B205"
                                           required>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Floor --}}
                                <div class="col-md-3">
                                    <label for="floor" class="form-label">
                                        Tầng <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('floor') is-invalid @enderror" 
                                           id="floor" 
                                           name="floor" 
                                           value="{{ old('floor', $unit->floor) }}" 
                                           min="0" 
                                           placeholder="0"
                                           required>
                                    @error('floor')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Area --}}
                                <div class="col-md-3">
                                    <label for="area_m2" class="form-label">
                                        Diện tích (m²) <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('area_m2') is-invalid @enderror" 
                                           id="area_m2" 
                                           name="area_m2" 
                                           value="{{ old('area_m2', $unit->area_m2) }}" 
                                           min="0" 
                                           step="0.01" 
                                           placeholder="0.00"
                                           required>
                                    @error('area_m2')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Unit Type --}}
                                <div class="col-md-3">
                                    <label for="unit_type" class="form-label">
                                        Loại phòng <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('unit_type') is-invalid @enderror" 
                                            id="unit_type" 
                                            name="unit_type" 
                                            required>
                                        <option value="">Chọn loại phòng</option>
                                        <option value="room" {{ old('unit_type', $unit->unit_type) == 'room' ? 'selected' : '' }}>Phòng</option>
                                        <option value="apartment" {{ old('unit_type', $unit->unit_type) == 'apartment' ? 'selected' : '' }}>Căn hộ</option>
                                        <option value="dorm" {{ old('unit_type', $unit->unit_type) == 'dorm' ? 'selected' : '' }}>Ký túc xá</option>
                                        <option value="shared" {{ old('unit_type', $unit->unit_type) == 'shared' ? 'selected' : '' }}>Chung</option>
                                    </select>
                                    @error('unit_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Max Occupancy --}}
                                <div class="col-md-3">
                                    <label for="max_occupancy" class="form-label">
                                        Số người tối đa <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('max_occupancy') is-invalid @enderror" 
                                           id="max_occupancy" 
                                           name="max_occupancy" 
                                           value="{{ old('max_occupancy', $unit->max_occupancy) }}" 
                                           min="1" 
                                           placeholder="1"
                                           required>
                                    @error('max_occupancy')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Base Rent --}}
                                <div class="col-md-6">
                                    <label for="base_rent" class="form-label">
                                        Giá thuê cơ bản (đồng) <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control money-input @error('base_rent') is-invalid @enderror" 
                                           id="base_rent" 
                                           name="base_rent" 
                                           value="{{ old('base_rent', $unit->base_rent ? number_format($unit->base_rent, 0, ',', '.') : '') }}" 
                                           placeholder="VD: 5.000.000"
                                           required>
                                    @error('base_rent')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Deposit Amount --}}
                                <div class="col-md-6">
                                    <label for="deposit_amount" class="form-label">Tiền cọc (đồng)</label>
                                    <input type="text" 
                                           class="form-control money-input @error('deposit_amount') is-invalid @enderror" 
                                           id="deposit_amount" 
                                           name="deposit_amount" 
                                           value="{{ old('deposit_amount', $unit->deposit_amount ? number_format($unit->deposit_amount, 0, ',', '.') : '') }}" 
                                           placeholder="VD: 10.000.000">
                                    @error('deposit_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Status --}}
                                <div class="col-md-6">
                                    <label for="status" class="form-label">
                                        Trạng thái <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('status') is-invalid @enderror" 
                                            id="status" 
                                            name="status" 
                                            required>
                                        <option value="available" {{ old('status', $unit->status) == 'available' ? 'selected' : '' }}>Có sẵn</option>
                                        <option value="reserved" {{ old('status', $unit->status) == 'reserved' ? 'selected' : '' }}>Đã đặt</option>
                                        <option value="occupied" {{ old('status', $unit->status) == 'occupied' ? 'selected' : '' }}>Đã thuê</option>
                                        <option value="maintenance" {{ old('status', $unit->status) == 'maintenance' ? 'selected' : '' }}>Bảo trì</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Note --}}
                                <div class="col-md-6">
                                    <label for="note" class="form-label">Ghi chú</label>
                                    <textarea class="form-control @error('note') is-invalid @enderror" 
                                              id="note" 
                                              name="note" 
                                              rows="3" 
                                              placeholder="Ghi chú về phòng...">{{ old('note', $unit->note) }}</textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Images Section --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-images me-2"></i>Hình ảnh phòng
                            </h6>
                        </div>
                        <div class="card-body">
                            <label class="form-label">Hình ảnh hiện tại</label>
                            @php
                                $unitImages = $unit->documents()
                                    ->where('document_type', 'image')
                                    ->orderBy('sort_order')
                                    ->orderBy('created_at')
                                    ->get();
                            @endphp
                            @if($unitImages && $unitImages->count() > 0)
                                <div class="row mb-3" id="existing-images">
                                    @foreach($unitImages as $document)
                                        @php
                                            // Lấy file_url từ document (đã là relative path không có storage/ prefix)
                                            $filePath = $document->getRawOriginal('file_url') ?? $document->file_url;
                                            
                                            // Nếu đã là full URL, sử dụng trực tiếp
                                            if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
                                                $imageUrl = $filePath;
                                            } else {
                                                // Path đã không có storage/ prefix, chỉ cần thêm vào URL
                                                $imageUrl = asset('storage/' . ltrim($filePath, '/'));
                                            }
                                        @endphp
                                        <div class="col-md-3 mb-2" data-document-id="{{ $document->id }}">
                                            <div class="position-relative">
                                                <img src="{{ $imageUrl }}" alt="Unit Image" class="img-thumbnail" style="width: 100%; height: 150px; object-fit: cover;">
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="removeExistingImage(this, {{ $document->id }})">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">Chưa có hình ảnh nào</p>
                            @endif
                            
                            <label class="form-label">Thêm hình ảnh mới</label>
                            <div class="image-upload-area" id="imageUploadArea" style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease;" ondrop="handleDrop(event, 'images')" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="mb-2">Kéo thả ảnh vào đây hoặc click để chọn</p>
                                <input type="file" 
                                       name="images[]" 
                                       id="images" 
                                       class="form-control" 
                                       accept="image/*" 
                                       multiple
                                       style="display: none;">
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('images').click()">
                                    <i class="fas fa-folder-open me-2"></i>Chọn ảnh
                                </button>
                            </div>
                            <div class="form-text">Chọn nhiều hình ảnh cùng lúc bằng cách giữ Ctrl (Windows) hoặc Cmd (Mac) và click chọn nhiều file. Định dạng: JPEG, PNG, JPG, GIF, WebP. Tối đa 5MB mỗi file.</div>
                            
                            <!-- Image Preview -->
                            <div id="image-preview" class="row g-2 mt-3"></div>
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
                                        'label' => 'Cập nhật phòng',
                                        'icon' => 'fas fa-save',
                                        'class' => 'w-100'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.units.show', $unit->id),
                                        'class' => 'w-100'
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    {{-- Card Thông tin hiện tại --}}
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold mb-1">Mã phòng:</label>
                                <p class="mb-0">{{ $unit->code }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold mb-1">Bất động sản:</label>
                                <p class="mb-0">{{ $unit->property->name }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold mb-1">Tầng:</label>
                                <p class="mb-0">Tầng {{ $unit->floor }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold mb-1">Diện tích:</label>
                                <p class="mb-0">{{ number_format($unit->area_m2, 2) }}m²</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold mb-1">Loại phòng:</label>
                                <p class="mb-0">
                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $unit->unit_type)) }}</span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold mb-1">Giá thuê:</label>
                                <p class="mb-0 text-primary fw-bold">{{ number_format($unit->base_rent) }}đ</p>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold mb-1">Trạng thái:</label>
                                <p class="mb-0">
                                    @switch($unit->status)
                                        @case('available')
                                            <span class="badge bg-success">Có sẵn</span>
                                            @break
                                        @case('reserved')
                                            <span class="badge bg-info">Đã đặt</span>
                                            @break
                                        @case('occupied')
                                            <span class="badge bg-primary">Đã thuê</span>
                                            @break
                                        @case('maintenance')
                                            <span class="badge bg-warning">Bảo trì</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $unit->status }}</span>
                                    @endswitch
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

@push('scripts')
<script src="{{ asset('assets/js/number-formatter.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('unitForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous validation
        clearValidation();
        
        // Show preloader
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        // Process number formatting before submit
        if (window.NumberFormatter && window.NumberFormatter.processForm) {
            window.NumberFormatter.processForm(form);
        }
        
        // Convert formatted values to raw values for submission
        const moneyFields = ['base_rent', 'deposit_amount'];
        moneyFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && window.NumberFormatter) {
                const rawValue = window.NumberFormatter.getValue(field);
                // Update the field's value directly to raw value for submission
                field.value = rawValue;
            }
        });
        
        // Get form data as FormData to support file uploads
        const formData = new FormData(form);
        // Add _method for Laravel to recognize PUT
        formData.append('_method', 'PUT');
        
        // Send request
        fetch('{{ route("staff.units.update", $unit->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid response from server');
                }
            }
        })
        .then(data => {
            if (data.success) {
                if (typeof window.Notify !== 'undefined') {
                    Notify.success(data.message || 'Cập nhật phòng thành công!', 'Thành công!');
                } else {
                    alert(data.message || 'Cập nhật phòng thành công!');
                }
                
                // Redirect to show page (use redirect from response, fallback to show page)
                setTimeout(() => {
                    window.location.href = data.redirect || '{{ route("staff.units.show", $unit->id) }}';
                }, 1500);
            } else {
                if (data.errors) {
                    showValidationErrors(data.errors);
                } else {
                    if (typeof window.Notify !== 'undefined') {
                        Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                    } else {
                        alert(data.message || 'Có lỗi xảy ra');
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof window.Notify !== 'undefined') {
                Notify.error('Có lỗi xảy ra khi cập nhật phòng. Vui lòng thử lại.', 'Lỗi hệ thống!');
            } else {
                alert('Có lỗi xảy ra khi cập nhật phòng. Vui lòng thử lại.');
            }
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
});

function clearValidation() {
    const form = document.getElementById('unitForm');
    const inputs = form.querySelectorAll('.form-control, .form-select');
    inputs.forEach(input => {
        input.classList.remove('is-invalid');
        const feedback = input.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = '';
        }
    });
}

function showValidationErrors(errors) {
    Object.keys(errors).forEach(field => {
        const input = document.getElementById(field);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = input.parentNode.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.textContent = errors[field][0];
            }
        }
    });
}

// Image upload handling
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('image-preview');

    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            imagePreview.innerHTML = '';
            
            if (e.target.files && e.target.files.length > 0) {
                Array.from(e.target.files).forEach((file, index) => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const col = document.createElement('div');
                        col.className = 'col-md-4 mb-2';
                        col.innerHTML = `
                            <div class="image-preview-item position-relative">
                                <img src="${e.target.result}" class="img-thumbnail" style="height: 100px; object-fit: cover; width: 100%;">
                                <div class="position-absolute top-0 start-0 bg-dark bg-opacity-75 text-white px-1 rounded-bottom-end" style="font-size: 0.7rem;">
                                    ${file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name}
                                </div>
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 btn-remove" 
                                        onclick="removeImagePreview(this)" title="Xóa ảnh">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        `;
                        imagePreview.appendChild(col);
                    };
                    
                    reader.readAsDataURL(file);
                });
            }
        });
    }
});

function removeImagePreview(button) {
    const imageContainer = button.closest('.col-md-4');
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('image-preview');
    
    imageContainer.style.transition = 'opacity 0.3s ease';
    imageContainer.style.opacity = '0';
    
    setTimeout(() => {
        imageContainer.remove();
        if (imagePreview.querySelectorAll('.col-md-4').length === 0 && imageInput) {
            imageInput.value = '';
        }
    }, 300);
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.style.borderColor = '#007bff';
    e.currentTarget.style.backgroundColor = '#f8f9fa';
}

function handleDragLeave(e) {
    e.preventDefault();
    e.currentTarget.style.borderColor = '#dee2e6';
    e.currentTarget.style.backgroundColor = 'transparent';
}

function handleDrop(e, inputId) {
    e.preventDefault();
    e.currentTarget.style.borderColor = '#dee2e6';
    e.currentTarget.style.backgroundColor = 'transparent';
    
    const files = e.dataTransfer.files;
    const input = document.getElementById(inputId);
    
    if (files.length > 0) {
        const dt = new DataTransfer();
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                dt.items.add(file);
            }
        });
        
        input.files = dt.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function removeExistingImage(button, documentId) {
    if (confirm('Bạn có chắc chắn muốn xóa hình ảnh này?')) {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'deleted_image_ids[]';
        hiddenInput.value = documentId;
        document.getElementById('unitForm').appendChild(hiddenInput);
        
        const imageContainer = button.closest('.col-md-3');
        imageContainer.style.transition = 'opacity 0.3s ease';
        imageContainer.style.opacity = '0';
        
        setTimeout(() => {
            imageContainer.remove();
        }, 300);
    }
}
</script>

<style>
.image-upload-area {
    transition: all 0.3s ease;
}

.image-upload-area:hover {
    border-color: #007bff !important;
    background-color: #e3f2fd !important;
    transform: scale(1.02);
}

.image-preview-item {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
}

.image-preview-item img {
    transition: transform 0.3s ease;
}

.image-preview-item:hover img {
    transform: scale(1.05);
}

.image-preview-item .btn-remove {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.image-preview-item:hover .btn-remove {
    opacity: 1;
}
</style>
@endpush
@endsection
