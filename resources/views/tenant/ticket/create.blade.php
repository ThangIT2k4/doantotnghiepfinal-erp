@extends('layouts.app')

@section('title', 'Tạo ticket mới')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/tenant/tickets.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/tenant/tickets.js') }}?v={{ time() }}"></script>
<script>
// Page-specific initialization
document.addEventListener('DOMContentLoaded', function() {
    TicketModule.initCreate();
    
    // Image upload with drag & drop
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    const previewImg = document.getElementById('previewImg');
    
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files.length > 0) {
                const file = e.target.files[0];
                
                if (file.type.startsWith('image/')) {
                    // Check file size (2MB limit)
                    if (file.size > 2 * 1024 * 1024) {
                        alert(`File "${file.name}" quá lớn (>2MB). Vui lòng chọn file nhỏ hơn.`);
                        imageInput.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        imagePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    alert(`File "${file.name}" không phải là hình ảnh. Vui lòng chọn file hình ảnh.`);
                    imageInput.value = '';
                }
            }
        });
    }
});

// Drag and drop functions
function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.style.borderColor = '#007bff';
    e.currentTarget.style.backgroundColor = '#f8f9fa';
}

function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.style.borderColor = '#dee2e6';
    e.currentTarget.style.backgroundColor = 'transparent';
}

function handleDrop(e, inputId) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.style.borderColor = '#dee2e6';
    e.currentTarget.style.backgroundColor = 'transparent';
    
    const files = e.dataTransfer.files;
    const input = document.getElementById(inputId);
    
    if (files.length > 0) {
        const file = files[0];
        if (file.type.startsWith('image/')) {
            // Check file size (2MB limit)
            if (file.size > 2 * 1024 * 1024) {
                alert(`File "${file.name}" quá lớn (>2MB). Vui lòng chọn file nhỏ hơn.`);
                return;
            }
            
            // Create a new FileList-like object
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            input.dispatchEvent(event);
        } else {
            alert(`File "${file.name}" không phải là hình ảnh. Vui lòng chọn file hình ảnh.`);
        }
    }
}

function removeImagePreview() {
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    
    if (imageInput) {
        imageInput.value = '';
    }
    if (imagePreview) {
        imagePreview.style.display = 'none';
    }
}
</script>
@endpush

@section('content')
<div class="form-container-blue">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header-blue">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3" style="position: relative; z-index: 1;">
                <ol class="breadcrumb mb-0" style="background: rgba(255, 255, 255, 0.2); padding: 0.75rem 1rem; border-radius: 10px; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);">
                    <li class="breadcrumb-item">
                        <a href="{{ route('tenant.dashboard') }}" style="color: rgba(255, 255, 255, 0.9); text-decoration: none;">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('tenant.tickets.index') }}" style="color: rgba(255, 255, 255, 0.9); text-decoration: none;">
                            <i class="fas fa-ticket-alt me-1"></i>Ticket
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: rgba(255, 255, 255, 1);">
                        <i class="fas fa-plus-circle me-1"></i>Tạo mới
                    </li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div>
                        <h1 class="page-title">Tạo Ticket Mới</h1>
                        <p class="page-subtitle">Báo cáo sự cố hoặc yêu cầu sửa chữa cho phòng thuê của bạn</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="{{ route('tenant.tickets.index') }}" class="btn btn-outline-secondary" style="background: rgba(255, 255, 255, 0.25); color: white; border: 1px solid rgba(255, 255, 255, 0.3); font-weight: 600; padding: 0.75rem 1.5rem; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); text-decoration: none;">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại
                    </a>
                </div>
            </div>
        </div>

        <!-- Success Messages -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Error Messages -->
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Có lỗi xảy ra:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <!-- Main Form Card -->
                <div class="form-card-blue">
                    <div class="card-body">
                        <form method="POST" action="{{ route('tenant.tickets.store') }}" id="ticketForm" enctype="multipart/form-data">
                            @csrf
                            
                            <!-- Basic Information Section -->
                            <div class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Thông tin cơ bản
                            </div>

                            <!-- Lease Selection -->
                            <div class="form-group">
                                <label for="lease_id" class="form-label required">
                                    Hợp đồng
                                </label>
                                <select class="form-select @error('lease_id') is-invalid @enderror" 
                                        id="lease_id" name="lease_id" required>
                                    <option value="">-- Chọn hợp đồng --</option>
                                    @foreach($leases as $lease)
                                        <option value="{{ $lease->id }}" 
                                                data-unit-id="{{ $lease->unit_id }}"
                                                data-unit-code="{{ $lease->unit->code ?? '' }}"
                                                data-property-name="{{ $lease->unit->property->name ?? '' }}"
                                                {{ old('lease_id') == $lease->id ? 'selected' : '' }}>
                                            {{ $lease->unit->property->name ?? 'N/A' }} - Phòng {{ $lease->unit->code ?? 'N/A' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('lease_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Chọn hợp đồng thuê của bạn để xác định phòng cần sửa chữa
                                </small>
                            </div>

                            <!-- Hidden Unit ID -->
                            <input type="hidden" id="unit_id" name="unit_id" value="{{ old('unit_id') }}">

                            <!-- Unit Info Display -->
                            <div id="unitInfo" class="unit-info-card d-none">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-home me-2 text-primary"></i>
                                    <strong>Thông tin phòng đã chọn:</strong>
                                </div>
                                <div id="unitInfoContent"></div>
                            </div>

                            <!-- Title -->
                            <div class="form-group">
                                <label for="title" class="form-label required">
                                    Tiêu đề
                                </label>
                                <input type="text" 
                                       class="form-control @error('title') is-invalid @enderror" 
                                       id="title" 
                                       name="title" 
                                       value="{{ old('title') }}" 
                                       placeholder="VD: Vòi nước bị hỏng, Điện bị cúp, Cửa không khóa được..." 
                                       required>
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Priority -->
                            <div class="form-group">
                                <label for="priority_id" class="form-label required">
                                    Độ ưu tiên
                                </label>
                                <select class="form-select @error('priority_id') is-invalid @enderror" 
                                        id="priority_id" name="priority_id" required>
                                    <option value="">-- Chọn độ ưu tiên --</option>
                                    @foreach($priorities ?? [] as $priority)
                                        @php
                                            $priorityLabels = [
                                                'low' => '🟢 Thấp - Không cấp bách',
                                                'medium' => '🟡 Trung bình - Cần xử lý sớm',
                                                'high' => '🟠 Cao - Ảnh hưởng sinh hoạt',
                                                'urgent' => '🔴 Khẩn cấp - Cần xử lý ngay'
                                            ];
                                            $label = $priorityLabels[$priority->key_code] ?? ucfirst($priority->key_code);
                                        @endphp
                                        <option value="{{ $priority->id }}" {{ old('priority_id') == $priority->id ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Description Section -->
                            <div class="section-title">
                                <i class="fas fa-align-left"></i>
                                Mô tả chi tiết
                            </div>

                            <!-- Description -->
                            <div class="form-group">
                                <label for="description" class="form-label required">
                                    Mô tả sự cố/yêu cầu
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" 
                                          name="description" 
                                          rows="6" 
                                          placeholder="Mô tả chi tiết về sự cố hoặc yêu cầu sửa chữa. Ví dụ:&#10;- Thời gian xảy ra sự cố&#10;- Mức độ nghiêm trọng&#10;- Các thiết bị bị ảnh hưởng&#10;- Yêu cầu xử lý cụ thể..." 
                                          required>{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Mô tả càng chi tiết càng giúp chúng tôi xử lý nhanh hơn
                                </small>
                            </div>

                            <!-- Image Upload Section -->
                            <div class="section-title">
                                <i class="fas fa-image"></i>
                                Hình ảnh đính kèm
                            </div>

                            <div class="form-group">
                                <label for="image" class="form-label">
                                    Hình ảnh minh họa
                                </label>
                                <div class="image-upload-area" id="imageUploadArea" style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease;" ondrop="handleDrop(event, 'image')" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <p class="mb-2">Kéo thả ảnh vào đây hoặc click để chọn</p>
                                    <input type="file" 
                                           class="form-control @error('image') is-invalid @enderror" 
                                           id="image" 
                                           name="image" 
                                           accept="image/*"
                                           style="display: none;">
                                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('image').click()">
                                        <i class="fas fa-folder-open me-2"></i>Chọn ảnh
                                    </button>
                                </div>
                                @error('image')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Định dạng: JPEG, PNG, JPG, GIF, WebP. Kích thước tối đa: 2MB
                                </small>
                                <!-- Image Preview -->
                                <div id="image-preview" class="mt-3" style="display: none;">
                                    <div class="position-relative d-inline-block">
                                        <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 300px; object-fit: cover;">
                                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="removeImagePreview()" title="Xóa ảnh" style="margin: 5px;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-flex gap-3 justify-content-end">
                                <a href="{{ route('tenant.tickets.index') }}" class="btn btn-outline-blue">
                                    <i class="fas fa-times me-2"></i>Hủy
                                </a>
                                <button type="submit" class="btn btn-primary-blue" id="submitBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Gửi Ticket
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Help Card -->
                <div class="sidebar-card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-question-circle me-2"></i>Hướng dẫn</h6>
                    </div>
                    <div class="card-body">
                        <div class="help-item">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Độ ưu tiên:</h6>
                            <ul class="help-list">
                                <li><strong>🟢 Thấp:</strong> Sự cố nhỏ, không ảnh hưởng sinh hoạt</li>
                                <li><strong>🟡 Trung bình:</strong> Sự cố thông thường cần sửa chữa</li>
                                <li><strong>🟠 Cao:</strong> Sự cố ảnh hưởng sinh hoạt hàng ngày</li>
                                <li><strong>🔴 Khẩn cấp:</strong> Sự cố nguy hiểm, cần xử lý ngay</li>
                            </ul>
                        </div>
                        
                        <div class="help-item">
                            <h6><i class="fas fa-info-circle me-2"></i>Lưu ý:</h6>
                            <ul class="help-list">
                                <li>Chọn hợp đồng để tự động xác định phòng</li>
                                <li>Mô tả rõ ràng vấn đề để dễ xử lý</li>
                                <li>Ticket sẽ được gửi đến bộ phận quản lý</li>
                                <li>Bạn có thể theo dõi tiến độ trong danh sách</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Tips Card -->
                <div class="sidebar-card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Mẹo hay</h6>
                    </div>
                    <div class="card-body">
                        <div class="help-item">
                            <h6><i class="fas fa-camera me-2"></i>Chụp ảnh:</h6>
                            <p class="small mb-0">Nếu có thể, hãy chụp ảnh sự cố để mô tả rõ hơn</p>
                        </div>
                        
                        <div class="help-item">
                            <h6><i class="fas fa-clock me-2"></i>Thời gian:</h6>
                            <p class="small mb-0">Ghi rõ thời gian xảy ra sự cố nếu biết</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection