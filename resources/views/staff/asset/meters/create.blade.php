@extends('layouts.staff_dashboard')

@section('title', 'Thêm công tơ đo mới')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Thêm công tơ đo mới',
            'subtitle' => 'Thêm công tơ đo điện, nước cho phòng',
            'icon' => 'fas fa-plus',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.meters.index')
                ]
            ]
        ])

        {{-- 2. Form với Layout 2 Cột --}}
        <form id="create-meter-form" method="POST" action="{{ route('staff.meters.store') }}">
            @csrf
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
                                                        {{ old('property_id', $selectedProperty?->id) == $property->id ? 'selected' : '' }}>
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
                                            @if($selectedProperty)
                                                @foreach($units as $unit)
                                                    <option value="{{ $unit->id }}" 
                                                            {{ old('unit_id', $selectedUnit?->id) == $unit->id ? 'selected' : '' }}>
                                                        {{ $unit->code }} - {{ $unit->unit_type }}
                                                    </option>
                                                @endforeach
                                            @endif
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
                                                        {{ old('service_id') == $service->id ? 'selected' : '' }}>
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
                                               value="{{ old('serial_no') }}" 
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
                                               value="{{ old('installed_at', date('Y-m-d')) }}" required>
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
                                                   {{ old('status', true) ? 'checked' : '' }}>
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
                                        'label' => 'Lưu công tơ',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.meters.index')
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
                                <h6><i class="fas fa-lightbulb me-2"></i>Lưu ý quan trọng:</h6>
                                <ul class="mb-0 small">
                                    <li>Số seri công tơ phải là duy nhất</li>
                                    <li>Chọn đúng loại dịch vụ (điện/nước)</li>
                                    <li>Ngày lắp đặt sẽ ảnh hưởng đến tính toán hóa đơn</li>
                                    <li>Công tơ phải được kích hoạt để có thể đo số liệu</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Cảnh báo:</h6>
                                <p class="mb-0 small">Sau khi tạo công tơ, bạn có thể thêm số liệu đo đầu tiên để bắt đầu theo dõi sử dụng.</p>
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
    
    // Get unit_id from URL query parameter
    const urlParams = new URLSearchParams(window.location.search);
    const unitIdFromUrl = urlParams.get('unit_id');
    
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
                        // Select unit if it matches URL parameter
                        if (unitIdFromUrl && unit.id == unitIdFromUrl) {
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
    
    // If property is already selected on page load and unit_id is in URL, trigger change to load units
    @if($selectedProperty && $selectedUnit)
        // Property and unit are already selected, units should be loaded
        // Just ensure unit is selected
        if (unitIdFromUrl && unitSelect.value != unitIdFromUrl) {
            unitSelect.value = unitIdFromUrl;
        }
    @elseif($selectedProperty)
        // Property is selected but unit might not be, trigger change to load units
        propertySelect.dispatchEvent(new Event('change'));
    @endif
    
    // Form submission with loading state
    const form = document.getElementById('create-meter-form');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';
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
                    Notify.success(data.message || 'Đã tạo công tơ đo thành công!', 'Thành công!');
                } else {
                    alert('Đã tạo công tơ đo thành công!');
                }
                setTimeout(() => {
                    const redirectUrl = data.redirect || '{{ route("staff.meters.index") }}';
                    window.location.href = redirectUrl;
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
                Notify.error('Có lỗi xảy ra khi lưu công tơ đo: ' + error.message, 'Lỗi hệ thống!');
            } else {
                alert('Có lỗi xảy ra khi lưu công tơ đo: ' + error.message);
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
