@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa Ticket #' . $ticket->id)

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" và "Xem chi tiết" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Chỉnh sửa Ticket',
            'subtitle' => 'Cập nhật thông tin ticket: ' . $ticket->title,
            'icon' => 'fas fa-edit',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.tickets.index')
                ],
                [
                    'variant' => 'info',            // ✅ Solid
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.tickets.show', $ticket->id)
                ]
            ]
        ])

    <form id="ticketForm" method="POST" action="{{ route('staff.tickets.update', $ticket->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
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
                                       id="title" name="title" value="{{ old('title', $ticket->title) }}" required>
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
                                        <option value="{{ $priority->id }}" {{ old('priority_id', $ticket->priority_id) == $priority->id ? 'selected' : '' }}>
                                            {{ $priority->name ?: ucfirst($priority->key_code) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" name="status" required>
                                    <option value="open" {{ old('status', $ticket->status) == 'open' ? 'selected' : '' }}>Mở</option>
                                    <option value="in_progress" {{ old('status', $ticket->status) == 'in_progress' ? 'selected' : '' }}>Đang xử lý</option>
                                    <option value="resolved" {{ old('status', $ticket->status) == 'resolved' ? 'selected' : '' }}>Đã giải quyết</option>
                                    <option value="closed" {{ old('status', $ticket->status) == 'closed' ? 'selected' : '' }}>Đã đóng</option>
                                    <option value="cancelled" {{ old('status', $ticket->status) == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="assigned_to" class="form-label">Người phụ trách</label>
                                <select class="form-select @error('assigned_to') is-invalid @enderror" 
                                        id="assigned_to" name="assigned_to">
                                    <option value="">Chọn người phụ trách</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('assigned_to', $ticket->assigned_to) == $user->id ? 'selected' : '' }}>
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
                                          placeholder="Mô tả chi tiết về vấn đề cần xử lý...">{{ old('description', $ticket->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="image" class="form-label">Hình ảnh đính kèm</label>
                                
                                @php
                                    $primaryImage = $ticket->documents()
                                        ->where('document_type', 'image')
                                        ->where('is_primary', true)
                                        ->orderBy('sort_order')
                                        ->orderBy('created_at')
                                        ->first();
                                @endphp
                                
                                @if($primaryImage)
                                    <div class="mb-3">
                                        @php
                                            $imageUrl = str_starts_with($primaryImage->file_url, 'http://') || str_starts_with($primaryImage->file_url, 'https://') 
                                                ? $primaryImage->file_url 
                                                : asset('storage/' . ltrim($primaryImage->file_url, '/'));
                                        @endphp
                                        <div class="position-relative d-inline-block">
                                            <img src="{{ $imageUrl }}" alt="Current image" class="img-thumbnail" style="max-width: 300px; max-height: 300px; object-fit: cover;">
                                            <div class="position-absolute top-0 start-0 bg-dark bg-opacity-75 text-white px-2 py-1 rounded-bottom-end" style="font-size: 0.8rem;">
                                                Ảnh hiện tại
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
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
                                    Định dạng: JPEG, PNG, JPG, GIF, WebP. Kích thước tối đa: 2MB. Để trống nếu không muốn thay đổi ảnh.
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
                                    <option value="{{ $property->id }}" {{ old('property_id', $ticket->property_id) == $property->id ? 'selected' : '' }}>
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
                                    <option value="{{ $unit->id }}" data-property-id="{{ $unit->property_id }}" {{ old('unit_id', $ticket->unit_id) == $unit->id ? 'selected' : '' }}>
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
                                            {{ old('lease_id', $ticket->lease_id) == $lease->id ? 'selected' : '' }}>
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

                {{-- Card Thông tin hiện tại --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted mb-1">Người tạo:</label>
                            <div class="p-2 bg-light rounded">
                                <i class="fas fa-user me-1 text-muted"></i>
                                {{ $ticket->createdBy->full_name ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted mb-1">Ngày tạo:</label>
                            <div class="p-2 bg-light rounded">
                                <i class="fas fa-calendar-plus me-1 text-muted"></i>
                                {{ $ticket->created_at->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-bold small text-muted mb-1">Cập nhật lần cuối:</label>
                            <div class="p-2 bg-light rounded">
                                <i class="fas fa-calendar-edit me-1 text-muted"></i>
                                {{ $ticket->updated_at->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card Thao tác (chứa action-buttons với layout dọc) --}}
                <div class="card shadow-sm">
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
                                    'label' => 'Cập nhật Ticket',
                                    'icon' => 'fas fa-save',
                                    'iconPosition' => 'left'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Hủy',
                                    'icon' => 'fas fa-times',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.tickets.show', $ticket->id)
                                ]
                            ]
                        ])
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
    const currentLeaseId = '{{ old("lease_id", $ticket->lease_id) }}';
    
    // Store all unit and lease options
    const allUnitOptions = Array.from(unitSelect.querySelectorAll('option'));
    const allLeaseOptions = Array.from(leaseSelect.querySelectorAll('option'));
    
    // Filter units and leases on page load if property is already selected
    const currentPropertyId = propertySelect.value;
    if (currentPropertyId) {
        // Filter units by current property
        unitSelect.innerHTML = '<option value="">Chọn phòng (tùy chọn)</option>';
        allUnitOptions.forEach(option => {
            if (option.value && option.dataset.propertyId == currentPropertyId) {
                unitSelect.appendChild(option.cloneNode(true));
            }
        });
        // Restore selected unit if it exists
        const currentUnitId = '{{ old("unit_id", $ticket->unit_id) }}';
        if (currentUnitId) {
            unitSelect.value = currentUnitId;
        }
        
        // Filter leases by current property
        leaseSelect.innerHTML = '<option value="">Chọn hợp đồng (tùy chọn)</option>';
        allLeaseOptions.forEach(option => {
            if (option.value && option.dataset.propertyId == currentPropertyId) {
                leaseSelect.appendChild(option.cloneNode(true));
            }
        });
        // Restore selected lease if it exists
        if (currentLeaseId) {
            leaseSelect.value = currentLeaseId;
        }
    }
    
    // Filter units when property is selected
    propertySelect.addEventListener('change', function() {
        const propertyId = this.value;
        
        // Reset unit and lease selects
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
            // Restore previous lease selection if it exists
            if (currentLeaseId) {
                leaseSelect.value = currentLeaseId;
            }
            return;
        }
        
        // Filter units by property
        unitSelect.innerHTML = '<option value="">Chọn phòng (tùy chọn)</option>';
        allUnitOptions.forEach(option => {
            if (option.value && option.dataset.propertyId == propertyId) {
                unitSelect.appendChild(option.cloneNode(true));
            }
        });
        
        // Reset lease select when property changes
        leaseSelect.innerHTML = '<option value="">Chọn hợp đồng (tùy chọn)</option>';
    });
    
    // Filter leases when unit is selected
    unitSelect.addEventListener('change', function() {
        const unitId = this.value;
        const selectedPropertyId = propertySelect.value;
        
        // Save current selected lease
        const currentSelectedLease = leaseSelect.value;
        
        // Reset lease select
        leaseSelect.innerHTML = '<option value="">Chọn hợp đồng (tùy chọn)</option>';
        leaseSelect.disabled = true;
        
        if (!unitId) {
            // If no unit selected, filter leases by property (if property is selected)
            if (selectedPropertyId) {
                // Filter leases by property
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
            // Restore previous selection if it exists
            if (currentLeaseId) {
                leaseSelect.value = currentLeaseId;
                }
            }
            leaseSelect.disabled = false;
            return;
        }
        
        // Show loading
        leaseSelect.innerHTML = '<option value="">Đang tải...</option>';
        
        // Fetch leases for selected unit
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
                
                // Try to restore previous selection if it's still valid
                if (currentSelectedLease && Array.from(leaseSelect.options).some(opt => opt.value === currentSelectedLease)) {
                    leaseSelect.value = currentSelectedLease;
                }
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
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...';
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
                Notify.success(data.message, 'Cập nhật thành công!');
                
                // Redirect after success - sử dụng redirect từ response, fallback về show page
                setTimeout(() => {
                    window.location.href = data.redirect || '{{ route("staff.tickets.show", $ticket->id) }}';
                }, 1500);
            } else {
                Notify.error(data.message, 'Lỗi cập nhật ticket');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi cập nhật ticket. Vui lòng thử lại.', 'Lỗi hệ thống');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});
</script>
@endpush
