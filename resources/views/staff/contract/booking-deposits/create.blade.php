@extends('layouts.staff_dashboard')

@section('title', 'Tạo đặt cọc mới')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Tạo đặt cọc mới',
            'subtitle' => 'Thêm đặt cọc mới cho khách hàng',
            'icon' => 'fas fa-hand-holding-usd',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.booking-deposits.index')
                ]
            ]
        ])

        {{-- Create Form --}}
        <form id="booking-deposit-form" method="POST" action="{{ route('staff.booking-deposits.store') }}">
            @csrf
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-hand-holding-usd me-2"></i>Thông tin đặt cọc
                            </h6>
                        </div>
                        <div class="card-body">
                            
                            <!-- Viewing Selection -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label">
                                        <i class="fas fa-eye me-1"></i>Chọn viewing (tùy chọn)
                                    </label>
                                    <select name="viewing_id" id="viewing-select" class="form-select">
                                        <option value="">Chọn viewing để tự động điền thông tin</option>
                                        @foreach($viewings as $viewing)
                                        <option value="{{ $viewing->id }}" 
                                                data-property-id="{{ $viewing->property_id ?? '' }}"
                                                data-unit-id="{{ $viewing->unit_id ?? '' }}"
                                                data-lead-id="{{ $viewing->lead_id ?? '' }}"
                                                data-agent-id="{{ $viewing->agent_id ?? '' }}"
                                                {{ isset($viewingId) && $viewingId == $viewing->id ? 'selected' : '' }}>
                                            Viewing #{{ $viewing->id }} - 
                                            @if($viewing->property)
                                                {{ $viewing->property->name }}
                                            @endif
                                            @if($viewing->unit)
                                                - {{ $viewing->unit->code }}
                                            @endif
                                            @if($viewing->lead)
                                                - {{ $viewing->lead->name }}
                                            @endif
                                            ({{ $viewing->schedule_at ? $viewing->schedule_at->format('d/m/Y H:i') : 'N/A' }})
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Chọn viewing để tự động điền thông tin: Bất động sản, Phòng, Khách hàng tiềm năng, và Nhân viên
                                    </div>
                                </div>
                            </div>

                            <!-- Property Selection -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label required">Bất động sản</label>
                                    <select name="property_id" id="property-select" class="form-select" required>
                                        <option value="">Chọn bất động sản</option>
                                        @foreach($properties as $property)
                                        <option value="{{ $property->id }}" {{ isset($selectedPropertyId) && $selectedPropertyId == $property->id ? 'selected' : '' }}>
                                            {{ $property->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <!-- Unit Selection -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label required">Phòng</label>
                                    <select name="unit_id" id="unit-select" class="form-select" required {{ isset($selectedPropertyId) && $selectedPropertyId ? '' : 'disabled' }}>
                                        <option value="">Chọn phòng</option>
                                        @if(isset($selectedPropertyId) && $selectedPropertyId && isset($units))
                                            @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" {{ isset($selectedUnitId) && $selectedUnitId == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->code }} - {{ $unit->property->name ?? '' }}
                                            </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    <div class="invalid-feedback"></div>
                                    <div class="form-text">Chỉ hiển thị các phòng trống (available) chưa có hợp đồng thuê đang hoạt động. Phòng đang có booking deposit vẫn hiển thị để có thể tạo booking deposit mới.</div>
                                </div>
                            </div>

                            <!-- Agent Selection -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label required">Nhân viên</label>
                                    <select name="agent_id" id="agent-select" class="form-select" required>
                                        <option value="">Chọn nhân viên</option>
                                        @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" {{ $user && $user->id == $agent->id ? 'selected' : '' }}>
                                            {{ $agent->userProfile->full_name ?? $agent->full_name ?? 'N/A' }} 
                                            @if($agent->userProfile && $agent->userProfile->phone)
                                                ({{ $agent->userProfile->phone }})
                                            @elseif($agent->phone)
                                                ({{ $agent->phone }})
                                            @endif
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <!-- Amount and Type -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label required">Số tiền (VNĐ)</label>
                                    <input type="text" name="amount" id="amount" class="form-control money-input" 
                                           placeholder="Nhập số tiền (ví dụ: 2.000.000)" required>
                                    <div class="invalid-feedback"></div>
                                    <div class="form-text">Nhập số tiền (sẽ tự động format với dấu chấm)</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Loại đặt cọc</label>
                                    <select name="deposit_type" id="deposit-type" class="form-select" required>
                                        <option value="">Chọn loại đặt cọc</option>
                                        <option value="booking">Đặt cọc</option>
                                        <option value="security">Cọc an ninh</option>
                                        <option value="advance">Trả trước</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            {{-- Trạng thái sẽ được quản lý ở trang show, không cho phép thay đổi ở form create/edit --}}
                            <input type="hidden" name="payment_status" value="pending_approval">

                            <!-- Hold Until -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label required">Giữ chỗ đến</label>
                                    <input type="datetime-local" name="hold_until" id="hold-until" class="form-control" 
                                           min="{{ now()->format('Y-m-d\TH:i') }}" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Ghi chú</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3" 
                                              placeholder="Ghi chú thêm về đặt cọc..."></textarea>
                                </div>
                            </div>

                            <!-- Lead Selection -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label required">Khách hàng tiềm năng</label>
                                    <select name="lead_id" id="lead-select" class="form-select" required>
                                        <option value="">Chọn khách hàng tiềm năng</option>
                                        @foreach($leads as $lead)
                                        <option value="{{ $lead->id }}" 
                                                data-tenant-id="{{ $lead->tenant_id ?? '' }}"
                                                {{ isset($selectedLeadId) && $selectedLeadId == $lead->id ? 'selected' : '' }}>
                                            {{ $lead->name }} ({{ $lead->phone }})
                                            @if($lead->tenant_id)
                                                - Đã có tài khoản
                                            @endif
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                    <div class="form-text">Nếu lead đã có tài khoản, hệ thống sẽ tự động điền thông tin tenant</div>
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
                                    'label' => 'Tạo đặt cọc',
                                    'icon' => 'fas fa-save'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Hủy',
                                    'icon' => 'fas fa-times',
                                    'url' => route('staff.booking-deposits.index')
                                ]
                            ]
                        ])
                    </div>
                </div>
                
                {{-- Card Hướng dẫn --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-question-circle me-2"></i>Hướng dẫn
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Thông tin cần thiết</h6>
                            <ul class="mb-0 small">
                                <li>Chọn bất động sản trước khi chọn phòng</li>
                                <li>Chỉ hiển thị phòng chưa có hợp đồng thuê</li>
                                <li>Chọn loại khách hàng phù hợp</li>
                                <li>Nhập thông tin chính xác</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Card Thống kê --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Thống kê
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-primary mb-1" id="total-deposits">-</h4>
                                    <small class="text-muted">Tổng đặt cọc</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-warning mb-1" id="pending-deposits">-</h4>
                                <small class="text-muted">Chờ duyệt</small>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection

@push('styles')
<style>
.required::after {
    content: " *";
    color: #dc3545;
}

/* Form validation styles */
.is-invalid {
    border-color: #dc3545;
}

.is-valid {
    border-color: #198754;
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
}

.valid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #198754;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/number-formatter.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load statistics
    loadStatistics();
    
    // Set default hold until to 7 days from now
    const now = new Date();
    now.setDate(now.getDate() + 7);
    document.getElementById('hold-until').value = now.toISOString().slice(0, 16);
    
    // Get property_id and unit_id from URL query string
    const urlParams = new URLSearchParams(window.location.search);
    const urlPropertyId = urlParams.get('property_id');
    const urlUnitId = urlParams.get('unit_id');
    
    const propertySelect = document.getElementById('property-select');
    const unitSelect = document.getElementById('unit-select');
    
    // Priority: URL params > Server pre-selected value
    let propertyIdToUse = null;
    
    if (urlPropertyId && propertySelect) {
        // Convert to string to ensure matching with option values
        const propertyIdStr = String(urlPropertyId);
        
        // Try to set property value immediately
        propertySelect.value = propertyIdStr;
        
        // Verify it was set correctly
        if (propertySelect.value === propertyIdStr) {
            propertyIdToUse = propertyIdStr;
            console.log('Property selected from URL:', propertyIdStr);
        } else {
            // If not set, check if the property exists in the select options
            const propertyOption = Array.from(propertySelect.options).find(opt => opt.value === propertyIdStr);
            
            if (propertyOption) {
                propertySelect.value = propertyIdStr;
                propertyIdToUse = propertyIdStr;
                console.log('Property found and selected:', propertyIdStr);
            } else {
                console.warn('Property ID from URL not found in options:', propertyIdStr);
                console.log('Available property options:', Array.from(propertySelect.options).map(opt => ({value: opt.value, text: opt.text})));
            }
        }
    } else if (propertySelect && propertySelect.value) {
        // Use property_id pre-selected from server
        propertyIdToUse = propertySelect.value;
        console.log('Property pre-selected from server:', propertyIdToUse);
    }
    
    // If we have a property ID, load units
    if (propertyIdToUse) {
        // Check if units are already loaded from server (if property was pre-selected)
        const hasUnitsFromServer = unitSelect && unitSelect.options.length > 1; // More than just "Chọn phòng"
        
        if (hasUnitsFromServer) {
            // Units already loaded from server, just select the unit if needed
            console.log('Units already loaded from server');
            if (urlUnitId && unitSelect) {
                const unitIdStr = String(urlUnitId);
                const unitOption = Array.from(unitSelect.options).find(opt => opt.value === unitIdStr);
                if (unitOption) {
                    unitSelect.value = unitIdStr;
                    console.log('Unit selected from URL:', unitIdStr);
                } else {
                    console.warn('Unit ID from URL not found in options:', unitIdStr);
                }
            }
            unitSelect.disabled = false;
        } else {
            // Load units for the selected property
            loadUnits(propertyIdToUse, function() {
                // After units are loaded, select the unit if unit_id is in URL or pre-selected
                if (urlUnitId && unitSelect) {
                    // Convert to string to ensure matching
                    const unitIdStr = String(urlUnitId);
                    // Wait a bit for options to be populated
                    setTimeout(() => {
                        const unitOption = Array.from(unitSelect.options).find(opt => opt.value === unitIdStr);
                        if (unitOption) {
                            unitSelect.value = unitIdStr;
                            console.log('Unit selected from URL after loading:', unitIdStr);
                        } else {
                            console.warn('Unit ID from URL not found in options:', unitIdStr);
                        }
                    }, 100);
                } else if (unitSelect && unitSelect.value) {
                    // Unit already pre-selected from server, keep it
                    console.log('Unit pre-selected from server:', unitSelect.value);
                }
            });
            unitSelect.disabled = false;
        }
    }

    // Handle viewing selection from URL
    const urlViewingId = urlParams.get('viewing_id');
    if (urlViewingId) {
        const viewingSelect = document.getElementById('viewing-select');
        if (viewingSelect) {
            const viewingIdStr = String(urlViewingId);
            const viewingOption = Array.from(viewingSelect.options).find(opt => opt.value === viewingIdStr);
            if (viewingOption) {
                viewingSelect.value = viewingIdStr;
                // Trigger change to auto-fill fields
                viewingSelect.dispatchEvent(new Event('change'));
            }
        }
    }
});

// Property change handler
document.getElementById('property-select').addEventListener('change', function() {
    const propertyId = this.value;
    const unitSelect = document.getElementById('unit-select');
    
    if (propertyId) {
        loadUnits(propertyId);
        unitSelect.disabled = false;
    } else {
        unitSelect.innerHTML = '<option value="">Chọn phòng</option>';
        unitSelect.disabled = true;
    }
});

// Viewing change handler - Auto-fill fields from viewing
const viewingSelect = document.getElementById('viewing-select');
if (viewingSelect) {
    viewingSelect.addEventListener('change', function() {
        const viewingId = this.value;
        const selectedOption = this.options[this.selectedIndex];
        
        if (viewingId && selectedOption) {
            const propertyId = selectedOption.dataset.propertyId;
            const unitId = selectedOption.dataset.unitId;
            const leadId = selectedOption.dataset.leadId;
            const agentId = selectedOption.dataset.agentId;
            
            let fieldsFilled = [];
            
            // Fill property
            if (propertyId) {
                const propertySelect = document.getElementById('property-select');
                if (propertySelect) {
                    const propertyOption = Array.from(propertySelect.options).find(opt => opt.value === propertyId);
                    if (propertyOption) {
                        propertySelect.value = propertyId;
                        fieldsFilled.push('Bất động sản');
                        // Trigger change to load units
                        propertySelect.dispatchEvent(new Event('change'));
                        
                        // After units are loaded, select the unit
                        setTimeout(() => {
                            if (unitId) {
                                const unitSelect = document.getElementById('unit-select');
                                if (unitSelect) {
                                    // Wait for units to load
                                    let attempts = 0;
                                    const maxAttempts = 20; // 2 seconds max
                                    const checkUnit = setInterval(() => {
                                        attempts++;
                                        const unitOption = Array.from(unitSelect.options).find(opt => opt.value === unitId);
                                        if (unitOption) {
                                            unitSelect.value = unitId;
                                            unitSelect.disabled = false;
                                            fieldsFilled.push('Phòng');
                                            clearInterval(checkUnit);
                                        } else if (unitSelect.options.length > 1 || attempts >= maxAttempts) {
                                            // Units loaded but unit not found, or timeout
                                            clearInterval(checkUnit);
                                        }
                                    }, 100);
                                }
                            }
                        }, 500);
                    }
                }
            }
            
            // Fill lead
            if (leadId) {
                const leadSelect = document.getElementById('lead-select');
                if (leadSelect) {
                    const leadOption = Array.from(leadSelect.options).find(opt => opt.value === leadId);
                    if (leadOption) {
                        leadSelect.value = leadId;
                        fieldsFilled.push('Khách hàng tiềm năng');
                    }
                }
            }
            
            // Fill agent
            if (agentId) {
                const agentSelect = document.getElementById('agent-select');
                if (agentSelect) {
                    const agentOption = Array.from(agentSelect.options).find(opt => opt.value === agentId);
                    if (agentOption) {
                        agentSelect.value = agentId;
                        fieldsFilled.push('Nhân viên');
                    }
                }
            }
            
            // Show notification
            if (fieldsFilled.length > 0) {
                Notify.success(`Đã tự động điền: ${fieldsFilled.join(', ')}`, 'Thông báo');
            } else {
                Notify.warning('Viewing này không có đủ thông tin để điền tự động', 'Thông báo');
            }
        } else if (!viewingId) {
            // Clear fields when viewing is deselected (optional - you can remove this if you want to keep filled values)
            // Uncomment below if you want to clear fields when viewing is deselected
            // document.getElementById('property-select').value = '';
            // document.getElementById('unit-select').value = '';
            // document.getElementById('lead-select').value = '';
            // document.getElementById('agent-select').value = '';
        }
    });
}


// Form submission
document.getElementById('booking-deposit-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Process number formatting before submit
    if (window.NumberFormatter && window.NumberFormatter.processForm) {
        window.NumberFormatter.processForm(this);
    }
    
    // Clear previous validation
    clearValidation();
    
    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang tạo...';
    submitBtn.disabled = true;
    
    // Prepare form data
    const formData = new FormData(this);
    
    
    // Submit form
    fetch(this.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Notify.success(data.message, 'Tạo đặt cọc thành công!');
            
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            }
        } else {
            // Show validation errors
            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    const input = document.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.parentNode.querySelector('.invalid-feedback');
                        if (feedback) {
                            feedback.textContent = data.errors[field][0];
                        }
                    }
                });
            }
            
            Notify.error(data.message, 'Không thể tạo đặt cọc');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Notify.error('Có lỗi xảy ra khi tạo đặt cọc. Vui lòng thử lại.', 'Lỗi hệ thống');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

    function loadUnits(propertyId, callback) {
        const unitSelect = document.getElementById('unit-select');
        if (!unitSelect) return;
        
        unitSelect.innerHTML = '<option value="">Đang tải...</option>';
        
        const url = `/staff/api/properties/${propertyId}/units`;
        
        fetch(url)
        .then(response => response.json())
        .then(data => {
            unitSelect.innerHTML = '<option value="">Chọn phòng</option>';
            
            // Handle both formats: data.units array or direct array
            let unitsArray = [];
            if (data && data.units && Array.isArray(data.units)) {
                unitsArray = data.units;
            } else if (Array.isArray(data)) {
                unitsArray = data;
            } else if (data && Array.isArray(data)) {
                unitsArray = data;
            }
            
            if (unitsArray && unitsArray.length > 0) {
                unitsArray.forEach(unit => {
                    const option = document.createElement('option');
                    option.value = unit.id;
                    option.textContent = `${unit.code || 'Phòng ' + unit.id} - ${unit.property_name || 'Property'}`;
                    unitSelect.appendChild(option);
                });
                
                // Enable unit select
                unitSelect.disabled = false;
                
                // Call callback if provided (e.g., to select a specific unit)
                if (callback && typeof callback === 'function') {
                    callback();
                }
            } else {
                unitSelect.innerHTML = '<option value="">Không có phòng nào</option>';
                unitSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error loading units:', error);
            unitSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
            unitSelect.disabled = true;
        });
    }


function loadStatistics() {
    fetch('{{ route("staff.booking-deposits.statistics") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-deposits').textContent = data.total || 0;
            document.getElementById('pending-deposits').textContent = data.pending || 0;
        })
        .catch(error => {
            console.error('Error loading statistics:', error);
        });
}

function clearValidation() {
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.textContent = '';
    });
}
</script>
@endpush
