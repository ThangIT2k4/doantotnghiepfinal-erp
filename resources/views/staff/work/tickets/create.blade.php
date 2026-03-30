@extends('layouts.staff_dashboard')

@section('title', 'Tạo Ticket Mới')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Tạo Ticket Mới',
            'subtitle' => 'Tạo ticket bảo trì hoặc sự cố mới',
            'icon' => 'fas fa-ticket-alt',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.tickets.index')
                ]
            ]
        ])

    <form id="ticketForm" method="POST" action="{{ route('staff.tickets.store') }}" enctype="multipart/form-data">
        @csrf
        
        <div class="row">
            {{-- Cột trái: Form chính (col-lg-8) --}}
            <div class="col-lg-8">
                {{-- Card 1: Thông tin Ticket --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin Ticket
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                       id="title" name="title" value="{{ old('title') }}" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="priority_id" class="form-label">Độ ưu tiên <span class="text-danger">*</span></label>
                                <select class="form-select @error('priority_id') is-invalid @enderror" 
                                        id="priority_id" name="priority_id" required>
                                    <option value="">Chọn độ ưu tiên</option>
                                    @foreach($priorities ?? [] as $priority)
                                        <option value="{{ $priority->id }}" {{ old('priority_id') == $priority->id ? 'selected' : '' }}>
                                            {{ $priority->name ?: ucfirst($priority->key_code) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="assigned_to" class="form-label">Người phụ trách</label>
                                <select class="form-select @error('assigned_to') is-invalid @enderror" 
                                        id="assigned_to" name="assigned_to">
                                    <option value="">Chọn người phụ trách</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('assigned_to', Auth::id()) == $user->id ? 'selected' : '' }}>
                                            {{ $user->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('assigned_to')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="description" class="form-label">Mô tả chi tiết</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="4" 
                                          placeholder="Mô tả chi tiết về vấn đề cần xử lý...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="image" class="form-label">Hình ảnh đính kèm</label>
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
                                    <i class="fas fa-info-circle"></i>
                                    Định dạng: JPEG, PNG, JPG, GIF, WebP. Kích thước tối đa: 2MB
                                </small>
                                <div id="imagePreview" class="mt-3" style="display: none;">
                                    <div class="position-relative d-inline-block">
                                        <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 300px; object-fit: cover;">
                                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="removeImagePreview()" title="Xóa ảnh" style="margin: 5px;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cột phải: Sidebar (col-lg-4) --}}
            <div class="col-lg-4">
                {{-- Card Liên kết --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-link me-2"></i>Liên kết
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="property_id" class="form-label">Bất động sản</label>
                            <select class="form-select @error('property_id') is-invalid @enderror" 
                                    id="property_id" name="property_id">
                                <option value="">Chọn bất động sản (tùy chọn)</option>
                                @foreach($properties ?? [] as $property)
                                    <option value="{{ $property->id }}" {{ old('property_id', $prefilledPropertyId ?? '') == $property->id ? 'selected' : '' }}>
                                        {{ $property->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('property_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="unit_id" class="form-label">Phòng</label>
                            <select class="form-select @error('unit_id') is-invalid @enderror" 
                                    id="unit_id" name="unit_id">
                                <option value="">Chọn phòng (tùy chọn)</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" data-property-id="{{ $unit->property_id }}" {{ old('unit_id', $prefilledUnitId ?? '') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->property->name }} - {{ $unit->code }}
                                    </option>
                                @endforeach
                            </select>
                            @error('unit_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="lease_id" class="form-label">Hợp đồng</label>
                            <select class="form-select @error('lease_id') is-invalid @enderror" 
                                    id="lease_id" name="lease_id">
                                <option value="">Chọn hợp đồng (tùy chọn)</option>
                                @foreach($leases as $lease)
                                    <option value="{{ $lease->id }}" 
                                            data-unit-id="{{ $lease->unit_id }}" 
                                            data-property-id="{{ $lease->unit->property_id ?? '' }}" 
                                            {{ old('lease_id', $prefilledLeaseId ?? '') == $lease->id ? 'selected' : '' }}>
                                        {{ $lease->contract_no ?: 'HD#' . $lease->id }} - {{ $lease->tenant->full_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('lease_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

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
                                    'label' => 'Tạo Ticket',
                                    'icon' => 'fas fa-save',
                                    'iconPosition' => 'left'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Hủy',
                                    'icon' => 'fas fa-times',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.tickets.index')
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
                            <h6><i class="fas fa-info-circle"></i> Thông tin cần thiết</h6>
                            <ul class="mb-0 small">
                                <li>Tiêu đề và độ ưu tiên là bắt buộc</li>
                                <li>Có thể đính kèm hình ảnh minh họa</li>
                                <li>Có thể liên kết với bất động sản, phòng hoặc hợp đồng</li>
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
    const form = document.getElementById('ticketForm');
    const propertySelect = document.getElementById('property_id');
    const unitSelect = document.getElementById('unit_id');
    const leaseSelect = document.getElementById('lease_id');
    const assignedToSelect = document.getElementById('assigned_to');
    const currentUserId = {{ Auth::id() }};
    
    // Store all unit and lease options
    const allUnitOptions = Array.from(unitSelect.querySelectorAll('option'));
    const allLeaseOptions = Array.from(leaseSelect.querySelectorAll('option'));
    
    // Mặc định fill người tạo khi load trang (nếu chưa có old value)
    if (!assignedToSelect.value || assignedToSelect.value === '') {
        assignedToSelect.value = currentUserId;
    }
    
    // Filter units when property is selected
    propertySelect.addEventListener('change', function() {
        const propertyId = this.value;
        
        // Reset unit and lease selects (kế thừa từ property)
        unitSelect.value = '';
        leaseSelect.innerHTML = '<option value="">Chọn hợp đồng (tùy chọn)</option>';
        leaseSelect.disabled = false;
        
        if (!propertyId) {
            // If no property selected, restore all options
            unitSelect.innerHTML = '<option value="">Chọn phòng (tùy chọn)</option>';
            allUnitOptions.forEach(option => {
                if (option.value) {
                    unitSelect.appendChild(option.cloneNode(true));
                }
            });
            leaseSelect.innerHTML = '<option value="">Chọn hợp đồng (tùy chọn)</option>';
            allLeaseOptions.forEach(option => {
                if (option.value) {
                    leaseSelect.appendChild(option.cloneNode(true));
                }
            });
            return;
        }
        
        // Filter units by property (kế thừa từ property)
        unitSelect.innerHTML = '<option value="">Chọn phòng (tùy chọn)</option>';
        allUnitOptions.forEach(option => {
            if (option.value && option.dataset.propertyId == propertyId) {
                unitSelect.appendChild(option.cloneNode(true));
            }
        });
        
        // Reset lease select khi property thay đổi
        leaseSelect.innerHTML = '<option value="">Chọn hợp đồng (tùy chọn)</option>';
        
        // Kiểm tra và cập nhật assigned_to khi chọn property
        // Fetch property manager để kiểm tra
        fetch(`{{ url('/staff/api/tickets/properties') }}/${propertyId}/manager`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.manager) {
                    // Có quản lý: kiểm tra người tạo có phải quản lý property không
                    if (data.manager.id == currentUserId) {
                        // Người tạo là quản lý property → giữ nguyên
                        assignedToSelect.value = currentUserId;
                    } else {
                        // Người tạo không phải quản lý → chuyển sang quản lý mới nhất
                        assignedToSelect.value = data.manager.id;
                    }
                } else {
                    // Không có quản lý → giữ nguyên người tạo
                    assignedToSelect.value = currentUserId;
                }
            }
        })
        .catch(error => {
            console.error('Error loading property manager:', error);
        });
    });
    
    // Filter leases when unit is selected (kế thừa từ unit)
    unitSelect.addEventListener('change', function() {
        const unitId = this.value;
        const selectedPropertyId = propertySelect.value;
        
        // Reset lease select
        leaseSelect.innerHTML = '<option value="">Chọn hợp đồng (tùy chọn)</option>';
        leaseSelect.disabled = true;
        
        if (!unitId) {
            // If no unit selected, filter leases by property (nếu có property được chọn)
            if (selectedPropertyId) {
                // Lọc leases theo property
                leaseSelect.innerHTML = '<option value="">Chọn hợp đồng (tùy chọn)</option>';
                allLeaseOptions.forEach(option => {
                    if (option.value && option.dataset.propertyId == selectedPropertyId) {
                        leaseSelect.appendChild(option.cloneNode(true));
                    }
                });
            } else {
                // Restore all leases
                leaseSelect.innerHTML = '<option value="">Chọn hợp đồng (tùy chọn)</option>';
                allLeaseOptions.forEach(option => {
                    if (option.value) {
                        leaseSelect.appendChild(option.cloneNode(true));
                    }
                });
            }
            leaseSelect.disabled = false;
            return;
        }
        
        // Show loading
        leaseSelect.innerHTML = '<option value="">Đang tải...</option>';
        
        // Fetch leases for selected unit (kế thừa từ unit)
        fetch(`{{ url('/staff/api/tickets/units') }}/${unitId}/leases`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.leases && data.leases.length > 0) {
                leaseSelect.innerHTML = '<option value="">Chọn hợp đồng (tùy chọn)</option>';
                data.leases.forEach(lease => {
                    const option = document.createElement('option');
                    option.value = lease.id;
                    option.textContent = (lease.contract_no || 'HD#' + lease.id) + ' - ' + (lease.tenant?.full_name || 'N/A');
                    leaseSelect.appendChild(option);
                });
            } else {
                leaseSelect.innerHTML = '<option value="">Không có hợp đồng nào</option>';
            }
            leaseSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error loading leases:', error);
            leaseSelect.innerHTML = '<option value="">Lỗi khi tải hợp đồng</option>';
            leaseSelect.disabled = false;
        });
    });
    
    // Image preview
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    Notify.error('Kích thước file không được vượt quá 2MB', 'Lỗi');
                    imageInput.value = '';
                    imagePreview.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
            }
        });
    }
    
    // Drag and drop functions
    window.handleDragOver = function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.style.borderColor = '#007bff';
        e.currentTarget.style.backgroundColor = '#f8f9fa';
    };
    
    window.handleDragLeave = function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.style.borderColor = '#dee2e6';
        e.currentTarget.style.backgroundColor = 'transparent';
    };
    
    window.handleDrop = function(e, inputId) {
        e.preventDefault();
        e.stopPropagation();
        e.currentTarget.style.borderColor = '#dee2e6';
        e.currentTarget.style.backgroundColor = 'transparent';
        
        const files = e.dataTransfer.files;
        const input = document.getElementById(inputId);
        
        if (files.length > 0 && input) {
            const file = files[0];
            if (file.type.startsWith('image/')) {
                // Check file size (2MB limit)
                if (file.size > 2 * 1024 * 1024) {
                    Notify.error('File quá lớn (>2MB). Vui lòng chọn file nhỏ hơn.', 'Lỗi');
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
                Notify.warning('File không phải là hình ảnh. Vui lòng chọn file hình ảnh.', 'Cảnh báo');
            }
        }
    };
    
    window.removeImagePreview = function() {
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        
        if (imageInput) {
            imageInput.value = '';
        }
        if (imagePreview) {
            imagePreview.style.display = 'none';
        }
    };
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tạo...';
        submitBtn.disabled = true;
        
        // Submit form
        const formData = new FormData(form);
        
        fetch(form.action, {
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
                Notify.success(data.message, 'Tạo thành công!');
                
                // Redirect after success
                setTimeout(() => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.href = '{{ route("staff.tickets.index") }}';
                    }
                }, 1500);
            } else {
                Notify.error(data.message, 'Lỗi tạo ticket');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi tạo ticket. Vui lòng thử lại.', 'Lỗi hệ thống');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});
</script>
@endpush
