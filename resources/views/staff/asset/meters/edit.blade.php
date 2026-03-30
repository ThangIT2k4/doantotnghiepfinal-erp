@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa công tơ đo')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" và "Xem chi tiết" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Chỉnh sửa công tơ đo',
            'subtitle' => 'Cập nhật thông tin công tơ đo: ' . $meter->serial_no,
            'icon' => 'fas fa-edit',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.meters.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.meters.show', $meter->id)
                ]
            ]
        ])

        {{-- 2. Form với Layout 2 Cột --}}
        <form id="edit-meter-form" method="POST" action="{{ route('staff.meters.update', $meter->id) }}">
            @csrf
            @method('PUT')
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    {{-- Card 1: Thông tin công tơ đo --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-tachometer-alt me-2"></i>Thông tin công tơ đo
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="property_id" class="form-label">
                                            Bất động sản <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('property_id') is-invalid @enderror" 
                                                id="property_id" name="property_id" required>
                                            <option value="">Chọn bất động sản</option>
                                            @foreach($properties as $property)
                                                <option value="{{ $property->id }}" 
                                                        {{ old('property_id', $meter->property_id) == $property->id ? 'selected' : '' }}>
                                                    {{ $property->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('property_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="unit_id" class="form-label">
                                            Phòng <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('unit_id') is-invalid @enderror" 
                                                id="unit_id" name="unit_id" required>
                                            <option value="">Chọn phòng</option>
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->id }}" 
                                                        {{ old('unit_id', $meter->unit_id) == $unit->id ? 'selected' : '' }}>
                                                    {{ $unit->code }} - {{ $unit->unit_type }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('unit_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="service_id" class="form-label">
                                            Loại dịch vụ <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('service_id') is-invalid @enderror" 
                                                id="service_id" name="service_id" required>
                                            <option value="">Chọn loại dịch vụ</option>
                                            @foreach($services as $service)
                                                <option value="{{ $service->id }}" 
                                                        {{ old('service_id', $meter->service_id) == $service->id ? 'selected' : '' }}>
                                                    {{ $service->name }} ({{ $service->key_code }})
                                                    @if($service->unit_label)
                                                        - {{ $service->unit_label }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('service_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="serial_no" class="form-label">
                                            Số seri công tơ <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control @error('serial_no') is-invalid @enderror" 
                                               id="serial_no" name="serial_no" 
                                               value="{{ old('serial_no', $meter->serial_no) }}" 
                                               placeholder="Nhập số seri công tơ" required>
                                        @error('serial_no')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="installed_at" class="form-label">
                                            Ngày lắp đặt <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control @error('installed_at') is-invalid @enderror" 
                                               id="installed_at" name="installed_at" 
                                               value="{{ old('installed_at', $meter->installed_at->format('Y-m-d')) }}" required>
                                        @error('installed_at')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="status" name="status" value="1" 
                                                   {{ old('status', $meter->status) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="status">
                                                Hoạt động
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                                        'label' => 'Cập nhật công tơ',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.meters.show', $meter->id)
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
                                <label class="form-label fw-bold">Bất động sản:</label>
                                <p class="mb-0">{{ $meter->property->name }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phòng:</label>
                                <p class="mb-0">{{ $meter->unit->code }} - {{ $meter->unit->unit_type }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Dịch vụ:</label>
                                <p class="mb-0">{{ $meter->service->name }} ({{ $meter->service->key_code }})</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Số seri:</label>
                                <p class="mb-0">{{ $meter->serial_no }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ngày lắp đặt:</label>
                                <p class="mb-0">{{ $meter->installed_at->format('d/m/Y') }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Trạng thái:</label>
                                <p class="mb-0">
                                    @if($meter->status)
                                        <span class="badge bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge bg-secondary">Ngừng hoạt động</span>
                                    @endif
                                </p>
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
    const propertySelect = document.getElementById('property_id');
    const unitSelect = document.getElementById('unit_id');
    
    // Load units when property changes
    propertySelect.addEventListener('change', function() {
        const propertyId = this.value;
        unitSelect.innerHTML = '<option value="">Đang tải...</option>';
        
        if (propertyId) {
            fetch(`{{ route('staff.meters.get-units') }}?property_id=${propertyId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                unitSelect.innerHTML = '<option value="">Chọn phòng</option>';
                
                if (data.units && data.units.length > 0) {
                    data.units.forEach(unit => {
                        const option = document.createElement('option');
                        option.value = unit.id;
                        option.textContent = `${unit.code} - ${unit.unit_type}`;
                        // Select current unit if it matches
                        if (unit.id == {{ $meter->unit_id }}) {
                            option.selected = true;
                        }
                        unitSelect.appendChild(option);
                    });
                } else {
                    unitSelect.innerHTML = '<option value="">Không có phòng nào</option>';
                }
            })
            .catch(error => {
                console.error('Error loading units:', error);
                unitSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
                
                // Show error notification
                if (typeof Notify !== 'undefined') {
                    Notify.error('Không thể tải danh sách phòng. Vui lòng thử lại.');
                }
            });
        } else {
            unitSelect.innerHTML = '<option value="">Chọn phòng</option>';
        }
    });
    
    // Form submission with loading state
    const form = document.getElementById('edit-meter-form');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang cập nhật...';
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
                    Notify.success(data.message || 'Đã cập nhật công tơ đo thành công!', 'Thành công!');
                } else {
                    alert('Đã cập nhật công tơ đo thành công!');
                }
                setTimeout(() => {
                    // Sử dụng redirect từ response, fallback về show page
                    window.location.href = data.redirect || '{{ route("staff.meters.show", $meter->id) }}';
                }, 1500);
            } else {
                if (typeof window.Notify !== 'undefined') {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                } else {
                    alert('Có lỗi xảy ra: ' + (data.message || 'Lỗi không xác định'));
                }
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof window.Notify !== 'undefined') {
                Notify.error('Có lỗi xảy ra khi cập nhật công tơ đo: ' + error.message, 'Lỗi hệ thống!');
            } else {
                alert('Có lỗi xảy ra khi cập nhật công tơ đo: ' + error.message);
            }
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
});
</script>
@endpush
