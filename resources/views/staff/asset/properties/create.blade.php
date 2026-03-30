@extends('layouts.staff_dashboard')

@section('title', 'Thêm Bất động sản')
@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Thêm Bất động sản mới',
            'subtitle' => 'Tạo bất động sản mới trong hệ thống',
            'icon' => 'fas fa-building',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.properties.index')
                ]
            ]
        ])

        {{-- Form với Layout Full Width --}}
        <form id="propertyForm" method="POST" action="{{ route('staff.properties.store') }}" enctype="multipart/form-data">
            @csrf
            
            {{-- Card 1: Thông tin cơ bản --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tên BĐS <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Loại BĐS</label>
                                <select name="property_type_id" class="form-select @error('property_type_id') is-invalid @enderror">
                                    <option value="">-- Chọn loại --</option>
                                    @foreach ($propertyTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('property_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name_local ?? $type->name }}</option>
                                    @endforeach
                                </select>
                                @error('property_type_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Số tầng</label>
                                <input type="number" name="total_floors" class="form-control @error('total_floors') is-invalid @enderror" value="{{ old('total_floors') }}" min="1">
                                @error('total_floors')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Hoạt động</option>
                                    <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Tạm ngưng</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Card 2: Hình ảnh --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-images me-2"></i>Hình ảnh
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Hình ảnh</label>
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
                    </div>
                    
                    <!-- Image Preview -->
                    <div id="image-preview" class="row g-2 mt-3"></div>
                </div>
            </div>

            {{-- Card 3: Nhân viên phụ trách --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Nhân viên phụ trách
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Quản lý (Manager)</label>
                                <select name="assigned_manager_id" id="assigned_manager_id" class="form-select @error('assigned_manager_id') is-invalid @enderror">
                                    <option value="">-- Chọn quản lý --</option>
                                    @if($managers && $managers->count() > 0)
                                        @foreach($managers as $manager)
                                            <option value="{{ $manager->id }}" {{ old('assigned_manager_id') == $manager->id ? 'selected' : '' }}>
                                                {{ $manager->userProfile->full_name ?? $manager->full_name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="form-text">Chọn quản lý phụ trách bất động sản này</div>
                                @error('assigned_manager_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nhân viên (Agent)</label>
                                <select name="assigned_agent_ids[]" id="assigned_agent_ids" class="form-select @error('assigned_agent_ids') is-invalid @enderror" multiple>
                                    @if($agents && $agents->count() > 0)
                                        @foreach($agents as $agent)
                                            <option value="{{ $agent->id }}" {{ in_array($agent->id, old('assigned_agent_ids', [])) ? 'selected' : '' }}>
                                                {{ $agent->userProfile->full_name ?? $agent->full_name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="form-text">Chọn một hoặc nhiều nhân viên phụ trách (giữ Ctrl để chọn nhiều)</div>
                                @error('assigned_agent_ids')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 4: Chu kỳ thanh toán --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Chu kỳ thanh toán
                    </h6>
                </div>
                <div class="card-body">
                    @if($organizationPaymentCycle)
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Chu kỳ thanh toán mặc định:</strong> {{ $organizationPaymentCycle->cycle_type_name }}
                            <br>
                            <small>Chu kỳ thanh toán sẽ tự động sử dụng cài đặt của tổ chức nếu không chọn chu kỳ khác.</small>
                        </div>
                        <!-- Hidden field to auto-select organization payment cycle -->
                        <input type="hidden" name="payment_cycle_id" id="payment_cycle_id" value="{{ $organizationPaymentCycle->id }}">
                    @else
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Tổ chức chưa có cài đặt chu kỳ thanh toán mặc định.
                        </div>
                    @endif
                    
                    <div class="mb-3">
                        <label class="form-label">Chọn chu kỳ thanh toán (tùy chọn)</label>
                        <select name="payment_cycle_id_override" id="payment_cycle_id_override" class="form-select">
                            <option value="">-- Sử dụng chu kỳ mặc định --</option>
                            @if($paymentCycles && $paymentCycles->count() > 0)
                                @foreach($paymentCycles as $cycle)
                                    <option value="{{ $cycle->id }}" {{ (old('payment_cycle_id_override') == $cycle->id || (old('payment_cycle_id_override') === null && $cycle->is_default && $organizationPaymentCycle && $organizationPaymentCycle->id == $cycle->id)) ? 'selected' : '' }}>
                                        {{ $cycle->name ?? $cycle->cycle_type_name }}
                                        @if($cycle->is_default)
                                            (Mặc định)
                                        @endif
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <div class="form-text">
                            <i class="fas fa-info-circle text-info"></i>
                            Chọn chu kỳ thanh toán cho bất động sản này. Nếu không chọn, sẽ dùng chu kỳ mặc định của tổ chức.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 4.5: Nhóm dịch vụ --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-list-alt me-2"></i>Nhóm dịch vụ
                    </h6>
                </div>
                <div class="card-body">
                    @if($defaultLeaseServiceSet)
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Nhóm dịch vụ mặc định:</strong> {{ $defaultLeaseServiceSet->name }}
                            @if($defaultLeaseServiceSet->items)
                                ({{ $defaultLeaseServiceSet->items->count() }} dịch vụ)
                            @endif
                            <br>
                            <small>Nhóm dịch vụ sẽ tự động sử dụng cài đặt của tổ chức nếu không chọn nhóm khác.</small>
                        </div>
                    @else
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Tổ chức chưa có cài đặt nhóm dịch vụ mặc định.
                        </div>
                    @endif
                    
                    <div class="mb-3">
                        <label class="form-label">Chọn nhóm dịch vụ (tùy chọn)</label>
                        <select name="lease_services_id" id="lease_services_id" class="form-select @error('lease_services_id') is-invalid @enderror">
                            <option value="">-- Sử dụng nhóm dịch vụ mặc định --</option>
                            @if($leaseServiceSets && $leaseServiceSets->count() > 0)
                                @foreach($leaseServiceSets as $set)
                                    <option value="{{ $set->id }}" {{ (old('lease_services_id') == $set->id || (old('lease_services_id') === null && $set->is_default && $defaultLeaseServiceSet && $defaultLeaseServiceSet->id == $set->id)) ? 'selected' : '' }}>
                                        {{ $set->name }}
                                        @if($set->items)
                                            ({{ $set->items->count() }} dịch vụ)
                                        @endif
                                        @if($set->is_default)
                                            (Mặc định)
                                        @endif
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <div class="form-text">
                            <i class="fas fa-info-circle text-info"></i>
                            Chọn nhóm dịch vụ cho bất động sản này. Nếu không chọn, sẽ dùng nhóm dịch vụ mặc định của tổ chức.
                        </div>
                        @error('lease_services_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Card 5: Địa chỉ (Hệ thống cũ) --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>Địa chỉ (Hệ thống cũ)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tỉnh/Thành phố</label>
                                <select name="province_code" id="provinceSelect" class="form-select @error('province_code') is-invalid @enderror">
                                    <option value="">-- Chọn tỉnh/TP --</option>
                                    @foreach ($provinces->sortBy(fn($p) => $p->name_local ?? $p->name) as $province)
                                    <option value="{{ $province->code }}" {{ old('province_code') == $province->code ? 'selected' : '' }}>{{ $province->name_local ?? $province->name }}</option>
                                    @endforeach
                                </select>
                                @error('province_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Quận/Huyện</label>
                                <select name="district_code" id="districtSelect" class="form-select @error('district_code') is-invalid @enderror" disabled>
                                    <option value="">-- Chọn quận/huyện --</option>
                                </select>
                                @error('district_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Phường/Xã</label>
                                <select name="ward_code" id="wardSelect" class="form-select @error('ward_code') is-invalid @enderror" disabled>
                                    <option value="">-- Chọn phường/xã --</option>
                                </select>
                                @error('ward_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Địa chỉ chi tiết</label>
                        <input type="text" name="street" class="form-control @error('street') is-invalid @enderror" value="{{ old('street') }}" placeholder="Số nhà, tên đường...">
                        @error('street')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Card 6: Địa chỉ (Hệ thống mới 2025) --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt text-primary me-2"></i>Địa chỉ (Hệ thống mới 2025)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tỉnh/Thành phố</label>
                                <select name="province_code_2025" id="provinceSelect2025" class="form-select @error('province_code_2025') is-invalid @enderror">
                                    <option value="">-- Chọn tỉnh/TP --</option>
                                    @foreach ($provinces2025->sortBy(fn($p) => $p->name_local ?? $p->name) as $province)
                                    <option value="{{ $province->code }}" {{ old('province_code_2025') == $province->code ? 'selected' : '' }}>{{ $province->name_local ?? $province->name }}</option>
                                    @endforeach
                                </select>
                                @error('province_code_2025')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phường/Xã</label>
                                <select name="ward_code_2025" id="wardSelect2025" class="form-select @error('ward_code_2025') is-invalid @enderror" disabled>
                                    <option value="">-- Chọn phường/xã --</option>
                                </select>
                                @error('ward_code_2025')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Địa chỉ chi tiết</label>
                        <input type="text" name="street_2025" class="form-control @error('street_2025') is-invalid @enderror" value="{{ old('street_2025') }}" placeholder="Số nhà, tên đường...">
                        @error('street_2025')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Form Actions: Layout ngang cho form dài --}}
            <div class="card shadow-sm">
                <div class="card-body">
                    @include('staff.components.action-buttons', [
                        'layout' => 'horizontal',
                        'size' => 'md',
                        'actions' => [
                            [
                                'type' => 'submit',
                                'variant' => 'primary',
                                'label' => 'Tạo Bất động sản',
                                'icon' => 'fas fa-save'
                            ],
                            [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Hủy',
                                'icon' => 'fas fa-times',
                                'url' => route('staff.properties.index')
                            ]
                        ]
                    ])
                </div>
            </div>
        </form>
    </div>
</main>

@push('styles')
<style>
.image-upload-area {
    transition: all 0.3s ease;
}

.image-upload-area:hover {
    border-color: #007bff !important;
    background-color: #f8f9fa !important;
}

.image-upload-area.dragover {
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('propertyForm');
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
            // Try to parse JSON response even if status is not ok
            const contentType = response.headers.get('content-type');
            let data;
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                data = { success: false, message: 'Lỗi không xác định từ server' };
            }
            
            if (!response.ok) {
                // If response is not ok, throw error with message from server
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            
            return data;
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Thành công!');
                setTimeout(() => {
                    if (data.property_id) {
                        window.location.href = '{{ route("staff.properties.show", ":id") }}'.replace(':id', data.property_id);
                    } else {
                        window.location.href = '{{ route("staff.properties.index") }}';
                    }
                }, 1500);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMessage = error.message || 'Đã xảy ra lỗi không xác định';
            Notify.error('Không thể tạo bất động sản: ' + errorMessage + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
});

// Simple cascading dropdowns - Old Location
document.addEventListener('DOMContentLoaded', function() {
    const provinceSelect = document.getElementById('provinceSelect');
    if (provinceSelect) {
        provinceSelect.addEventListener('change', function() {
            const provinceCode = this.value;
            const districtSelect = document.getElementById('districtSelect');
            const wardSelect = document.getElementById('wardSelect');
            
            // Clear dependent selects
            districtSelect.innerHTML = '<option value="">-- Chọn quận/huyện --</option>';
            wardSelect.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
            
            if (!provinceCode) {
                districtSelect.disabled = true;
                wardSelect.disabled = true;
                return;
            }
            
            // Load districts
            fetch(`/staff/api/geo/districts/${provinceCode}`)
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data) && data.length > 0) {
                        // Sort by name_local or name alphabetically
                        data.sort((a, b) => {
                            const aName = (a.name_local || a.name || '').toLowerCase();
                            const bName = (b.name_local || b.name || '').toLowerCase();
                            return aName.localeCompare(bName, 'vi');
                        });
                        
                        data.forEach(d => {
                            const opt = document.createElement('option');
                            opt.value = d.code;
                            opt.textContent = d.name_local || d.name;
                            districtSelect.appendChild(opt);
                        });
                        districtSelect.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error loading districts:', error);
                });
        });
    }

    const districtSelect = document.getElementById('districtSelect');
    if (districtSelect) {
        districtSelect.addEventListener('change', function() {
            const districtCode = this.value;
            const wardSelect = document.getElementById('wardSelect');
            
            // Clear ward select
            wardSelect.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
            
            if (!districtCode) {
                wardSelect.disabled = true;
                return;
            }
            
            // Load wards
            fetch(`/staff/api/geo/wards/${districtCode}`)
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data) && data.length > 0) {
                        // Sort by name_local or name alphabetically
                        data.sort((a, b) => {
                            const aName = (a.name_local || a.name || '').toLowerCase();
                            const bName = (b.name_local || b.name || '').toLowerCase();
                            return aName.localeCompare(bName, 'vi');
                        });
                        
                        data.forEach(w => {
                            const opt = document.createElement('option');
                            opt.value = w.code;
                            opt.textContent = w.name_local || w.name;
                            wardSelect.appendChild(opt);
                        });
                        wardSelect.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error loading wards:', error);
                });
        });
    }

    // New location 2025 cascading dropdowns
    const provinceSelect2025 = document.getElementById('provinceSelect2025');
    if (provinceSelect2025) {
        provinceSelect2025.addEventListener('change', function() {
            const provinceCode = this.value;
            const wardSelect2025 = document.getElementById('wardSelect2025');
            
            // Clear ward select
            wardSelect2025.innerHTML = '<option value="">-- Chọn phường/xã --</option>';
            
            if (!provinceCode) {
                wardSelect2025.disabled = true;
                return;
            }
            
            // Load wards 2025
            fetch(`/staff/api/geo/wards-2025/${provinceCode}`)
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data) && data.length > 0) {
                        // Sort by name_local or name alphabetically
                        data.sort((a, b) => {
                            const aName = (a.name_local || a.name || '').toLowerCase();
                            const bName = (b.name_local || b.name || '').toLowerCase();
                            return aName.localeCompare(bName, 'vi');
                        });
                        
                        data.forEach(w => {
                            const opt = document.createElement('option');
                            opt.value = w.code;
                            opt.textContent = w.name_local || w.name;
                            wardSelect2025.appendChild(opt);
                        });
                        wardSelect2025.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error loading wards-2025:', error);
                });
        });
    }
});

// Payment cycle change handler
document.addEventListener('DOMContentLoaded', function() {
    const paymentCycleIdInput = document.getElementById('payment_cycle_id'); // Hidden input
    const paymentCycleOverrideSelect = document.getElementById('payment_cycle_id_override');
    const cycleTypeSelect = document.getElementById('cycle_type');
    const customFields = document.getElementById('custom_payment_fields');
    const customMonthsField = document.getElementById('custom_months_field');

    // When selecting override payment cycle, update hidden input and clear cycle_type
    if (paymentCycleOverrideSelect) {
        paymentCycleOverrideSelect.addEventListener('change', function() {
            if (this.value) {
                if (paymentCycleIdInput) paymentCycleIdInput.value = this.value;
                if (cycleTypeSelect) cycleTypeSelect.value = '';
                if (customFields) customFields.style.display = 'none';
            } else {
                // Reset to organization default
                @if($organizationPaymentCycle)
                    if (paymentCycleIdInput) paymentCycleIdInput.value = '{{ $organizationPaymentCycle->id }}';
                @endif
            }
        });
    }

    // When selecting cycle_type, clear payment_cycle_id and override
    if (cycleTypeSelect) {
        cycleTypeSelect.addEventListener('change', function() {
            if (this.value) {
                if (paymentCycleIdInput) paymentCycleIdInput.value = '';
                if (paymentCycleOverrideSelect) paymentCycleOverrideSelect.value = '';
                if (customFields) customFields.style.display = 'block';
                
                // Show/hide custom months field
                if (this.value === 'custom') {
                    if (customMonthsField) customMonthsField.style.display = 'block';
                } else {
                    if (customMonthsField) customMonthsField.style.display = 'none';
                }
            } else {
                if (customFields) customFields.style.display = 'none';
                // Reset to organization default
                @if($organizationPaymentCycle)
                    if (paymentCycleIdInput) paymentCycleIdInput.value = '{{ $organizationPaymentCycle->id }}';
                @endif
            }
        });
    }
});

// Image upload functions for multiple files
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('image-preview');

    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            imagePreview.innerHTML = '';
            
            if (e.target.files && e.target.files.length > 0) {
                // Show loading message
                const loadingDiv = document.createElement('div');
                loadingDiv.className = 'col-12';
                loadingDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải ảnh...</div>';
                imagePreview.appendChild(loadingDiv);
                
                let loadedCount = 0;
                const totalFiles = e.target.files.length;
                
                Array.from(e.target.files).forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        // Check file size (5MB limit)
                        if (file.size > 5 * 1024 * 1024) {
                            Notify.warning(`File "${file.name}" quá lớn (>5MB). Vui lòng chọn file nhỏ hơn.`);
                            return;
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            loadedCount++;
                            
                            // Remove loading message if this is the first image
                            if (loadedCount === 1) {
                                const loadingDiv = imagePreview.querySelector('.col-12');
                                if (loadingDiv) loadingDiv.remove();
                            }
                            
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
                            
                            // Show completion message
                            if (loadedCount === totalFiles) {
                                Notify.success(`Đã tải ${loadedCount} ảnh thành công!`);
                            }
                        };
                        reader.readAsDataURL(file);
                    } else {
                        Notify.warning(`File "${file.name}" không phải là hình ảnh. Vui lòng chọn file hình ảnh.`);
                    }
                });
            }
        });
    }
});

// Drag and drop functions
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
        // Create a new FileList-like object
        const dt = new DataTransfer();
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                dt.items.add(file);
            } else {
                Notify.warning(`File "${file.name}" không phải là hình ảnh. Vui lòng chọn file hình ảnh.`);
            }
        });
        
        if (dt.items.length > 0) {
            input.files = dt.files;
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            input.dispatchEvent(event);
        }
    }
}

function removeImagePreview(button) {
    const imageContainer = button.closest('.col-md-4');
    const imagePreview = document.getElementById('image-preview');
    
    // Add fade out animation
    imageContainer.style.transition = 'opacity 0.3s ease';
    imageContainer.style.opacity = '0';
    
    setTimeout(() => {
        imageContainer.remove();
        
        // If no images left, clear the file input
        const remainingImages = imagePreview.querySelectorAll('.col-md-4');
        if (remainingImages.length === 0) {
            const imageInput = document.getElementById('images');
            if (imageInput) {
                imageInput.value = '';
            }
        }
    }, 300);
}
</script>
@endpush
@endsection

