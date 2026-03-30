@extends('layouts.staff_dashboard')

@section('title', 'Thêm số liệu đo mới')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Thêm số liệu đo mới',
            'subtitle' => 'Ghi nhận số liệu đo từ công tơ',
            'icon' => 'fas fa-plus',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.meter-readings.index')
                ]
            ]
        ])

        {{-- 2. Form với Layout 2 Cột --}}
        <form id="create-meter-reading-form" method="POST" action="{{ route('staff.meter-readings.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    {{-- Card 1: Thông tin số liệu đo --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-clipboard-list me-2"></i>Thông tin số liệu đo
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="filter_property_id" class="form-label">
                                            Bất động sản
                                        </label>
                                        <select class="form-select" id="filter_property_id">
                                            <option value="">Tất cả bất động sản</option>
                                            @foreach($properties ?? [] as $property)
                                                <option value="{{ $property->id }}" 
                                                        {{ old('property_id', request('property_id', $selectedProperty?->id)) == $property->id ? 'selected' : '' }}>
                                                    {{ $property->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="filter_unit_id" class="form-label">
                                            Phòng
                                        </label>
                                        <select class="form-select" id="filter_unit_id">
                                            <option value="">Tất cả phòng</option>
                                            @foreach($units ?? [] as $unit)
                                                <option value="{{ $unit->id }}" 
                                                        {{ old('unit_id', request('unit_id', $selectedUnit?->id)) == $unit->id ? 'selected' : '' }}>
                                                    {{ $unit->code }} - {{ ucfirst(str_replace('_', ' ', $unit->unit_type)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="meter_id" class="form-label">
                                            Công tơ đo <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('meter_id') is-invalid @enderror" 
                                                id="meter_id" name="meter_id" required>
                                            <option value="">Chọn công tơ đo</option>
                                            @foreach($meters as $meter)
                                                <option value="{{ $meter->id }}" 
                                                        {{ old('meter_id', request('meter_id', $selectedMeter?->id)) == $meter->id ? 'selected' : '' }}
                                                        data-property-id="{{ $meter->property_id }}"
                                                        data-unit-id="{{ $meter->unit_id }}"
                                                        data-service-name="{{ $meter->service->name }}">
                                                    {{ $meter->serial_no }} - {{ $meter->property->name }} - {{ $meter->unit->code }} ({{ $meter->service->name }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('meter_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="reading_date" class="form-label">
                                            Ngày đo <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control @error('reading_date') is-invalid @enderror" 
                                               id="reading_date" name="reading_date" 
                                               value="{{ old('reading_date', date('Y-m-d')) }}" required>
                                        @error('reading_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="value" class="form-label">
                                            Giá trị đo <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" step="0.001" class="form-control @error('value') is-invalid @enderror" 
                                               id="value" name="value" 
                                               value="{{ old('value') }}" 
                                               placeholder="Nhập giá trị đo" required>
                                        @error('value')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">
                                            <span id="unitLabel">Đơn vị sẽ hiển thị sau khi chọn công tơ</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Hình ảnh công tơ</label>
                                        <input type="file" class="form-control @error('image') is-invalid @enderror" 
                                               id="image" name="image" accept="image/*">
                                        @error('image')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Chọn hình ảnh chụp công tơ (tùy chọn)</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="note" class="form-label">Ghi chú</label>
                                <textarea class="form-control @error('note') is-invalid @enderror" 
                                          id="note" name="note" rows="3" 
                                          placeholder="Nhập ghi chú (tùy chọn)">{{ old('note') }}</textarea>
                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Cột phải: Sidebar (col-lg-4) --}}
                <div class="col-lg-4">
                    {{-- Card Số liệu đo cuối --}}
                    <div class="card shadow-sm mb-4" id="lastReadingCard" style="display: none;">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Số liệu đo cuối
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="lastReadingInfo">
                                <!-- Last reading info will be loaded here -->
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
                                        'label' => 'Lưu số liệu đo',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.meter-readings.index')
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
                                    <li>Giá trị đo phải lớn hơn hoặc bằng số liệu đo trước đó</li>
                                    <li>Ngày đo không được là ngày trong tương lai</li>
                                    <li>Hình ảnh giúp xác minh số liệu đo</li>
                                    <li>Ghi chú giúp theo dõi các trường hợp đặc biệt</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Cảnh báo:</h6>
                                <p class="mb-0 small">Số liệu đo không chính xác có thể ảnh hưởng đến tính toán hóa đơn.</p>
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
    const filterPropertySelect = document.getElementById('filter_property_id');
    const filterUnitSelect = document.getElementById('filter_unit_id');
    const meterSelect = document.getElementById('meter_id');
    const valueInput = document.getElementById('value');
    const unitLabel = document.getElementById('unitLabel');
    const lastReadingCard = document.getElementById('lastReadingCard');
    const lastReadingInfo = document.getElementById('lastReadingInfo');
    
    // Store all meter options (clone to preserve original)
    const allMeterOptions = Array.from(meterSelect.options).map(opt => opt.cloneNode(true));
    
    // Function to filter meters based on property and unit
    function filterMeters() {
        const selectedPropertyId = filterPropertySelect ? filterPropertySelect.value : '';
        const selectedUnitId = filterUnitSelect ? filterUnitSelect.value : '';
        
        // Clear current options (except first empty option)
        meterSelect.innerHTML = '<option value="">Chọn công tơ đo</option>';
        
        // Filter and add matching options
        allMeterOptions.forEach(option => {
            if (option.value === '') return; // Skip empty option
            
            const propertyId = option.getAttribute('data-property-id');
            const unitId = option.getAttribute('data-unit-id');
            
            let show = true;
            
            if (selectedPropertyId && propertyId !== selectedPropertyId) {
                show = false;
            }
            
            if (selectedUnitId && unitId !== selectedUnitId) {
                show = false;
            }
            
            if (show) {
                meterSelect.appendChild(option.cloneNode(true));
            }
        });
        
        // Reset meter selection if current selection is not in filtered list
        const currentMeterId = meterSelect.value;
        const availableOptions = Array.from(meterSelect.options).map(opt => opt.value);
        if (currentMeterId && !availableOptions.includes(currentMeterId)) {
            meterSelect.value = '';
            lastReadingCard.style.display = 'none';
        }
    }
    
    // Initialize filter on page load
    if (filterPropertySelect && filterUnitSelect) {
        // Load units if property is already selected
        const selectedPropertyId = filterPropertySelect.value;
        if (selectedPropertyId) {
            // Load units for selected property
            fetch(`{{ route('staff.meters.get-units') }}?property_id=${selectedPropertyId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.units) {
                    data.units.forEach(unit => {
                        const option = document.createElement('option');
                        option.value = unit.id;
                        option.textContent = `${unit.code} - ${unit.unit_type ? unit.unit_type.replace('_', ' ') : ''}`;
                        filterUnitSelect.appendChild(option);
                    });
                    // Apply filter after units are loaded
                    filterMeters();
                }
            })
            .catch(error => {
                console.error('Error loading units:', error);
                filterMeters();
            });
        } else {
            filterMeters();
        }
    }
    
    // Load units when property changes
    if (filterPropertySelect) {
        filterPropertySelect.addEventListener('change', function() {
        const propertyId = this.value;
        
        // Reset unit filter
        filterUnitSelect.innerHTML = '<option value="">Tất cả phòng</option>';
        filterUnitSelect.value = '';
        
        if (propertyId) {
            // Load units for selected property via AJAX
            fetch(`{{ route('staff.meters.get-units') }}?property_id=${propertyId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.units) {
                    data.units.forEach(unit => {
                        const option = document.createElement('option');
                        option.value = unit.id;
                        option.textContent = `${unit.code} - ${unit.unit_type ? unit.unit_type.replace('_', ' ') : ''}`;
                        filterUnitSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading units:', error);
            });
        }
        
        // Filter meters
        filterMeters();
        });
    }
    
    // Filter meters when unit changes
    if (filterUnitSelect) {
        filterUnitSelect.addEventListener('change', function() {
            filterMeters();
        });
    }
    
    // Function to load last reading
    function loadLastReading(meterId) {
        if (!meterId) {
            lastReadingCard.style.display = 'none';
            return;
        }
        
        fetch(`{{ route('staff.meter-readings.get-last-reading') }}?meter_id=${meterId}`, {
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
            if (data.lastReading) {
                const reading = data.lastReading;
                lastReadingInfo.innerHTML = `
                    <div class="mb-2">
                        <label class="form-label fw-bold">Ngày đo cuối:</label>
                        <p class="mb-0">${new Date(reading.reading_date).toLocaleDateString('vi-VN')}</p>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Giá trị cuối:</label>
                        <p class="mb-0">${parseFloat(reading.value).toFixed(3)}</p>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Người đo:</label>
                        <p class="mb-0">${reading.taken_by ? reading.taken_by.name : 'N/A'}</p>
                    </div>
                `;
                lastReadingCard.style.display = 'block';
                
                // Set minimum value
                valueInput.min = reading.value;
                valueInput.placeholder = `Tối thiểu: ${parseFloat(reading.value).toFixed(3)}`;
            } else {
                lastReadingInfo.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-info-circle fa-2x text-muted mb-3"></i>
                        <h6 class="text-muted">Chưa có số liệu đo</h6>
                        <p class="text-muted small">Đây sẽ là số liệu đo đầu tiên cho công tơ này.</p>
                    </div>
                `;
                lastReadingCard.style.display = 'block';
                
                // Remove minimum value
                valueInput.min = 0;
                valueInput.placeholder = 'Nhập giá trị đo';
            }
        })
        .catch(error => {
            console.error('Error loading last reading:', error);
            lastReadingCard.style.display = 'none';
            
            // Show error notification
            if (typeof Notify !== 'undefined') {
                Notify.error('Không thể tải số liệu đo cuối. Vui lòng thử lại.');
            }
        });
    }
    
    // Load last reading when meter changes
    meterSelect.addEventListener('change', function() {
        loadLastReading(this.value);
    });
    
    // Load last reading on page load if meter_id is in URL or already selected
    const urlParams = new URLSearchParams(window.location.search);
    const meterIdFromUrl = urlParams.get('meter_id');
    
    if (meterIdFromUrl) {
        // Set meter select value
        meterSelect.value = meterIdFromUrl;
        // Load last reading
        loadLastReading(meterIdFromUrl);
    } else if (meterSelect.value) {
        // If meter is already selected (from old value), load last reading
        loadLastReading(meterSelect.value);
    }
    
    // Form submission with loading state
    const form = document.getElementById('create-meter-reading-form');
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
                    Notify.success(data.message || 'Đã tạo số liệu đo thành công!', 'Thành công!');
                } else {
                    alert('Đã tạo số liệu đo thành công!');
                }
                setTimeout(() => {
                    const redirectUrl = data.redirect || '{{ route("staff.meter-readings.index") }}';
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
                Notify.error('Có lỗi xảy ra khi lưu số liệu đo: ' + error.message, 'Lỗi hệ thống!');
            } else {
                alert('Có lỗi xảy ra khi lưu số liệu đo: ' + error.message);
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
