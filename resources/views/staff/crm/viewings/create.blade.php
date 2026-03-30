@extends('layouts.staff_dashboard')

@section('title', 'Tạo lịch hẹn mới')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Tạo lịch hẹn mới',
            'subtitle' => 'Thêm lịch hẹn xem phòng mới',
            'icon' => 'fas fa-calendar-plus',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.viewings.index')
                ]
            ]
        ])

        <!-- Create Form -->
        <form id="create-viewing-form" action="{{ route('staff.viewings.store') }}" method="POST">
            @csrf
            <div class="row">
                <!-- Main Form -->
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-calendar me-2"></i>Thông tin lịch hẹn
                            </h6>
                        </div>
                        <div class="card-body">
                            
                            <div class="row">
                                <!-- Customer Type Selection -->
                                <div class="col-12 mb-4">
                                    <label class="form-label">Loại khách hàng <span class="text-danger">*</span></label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="customer_type" id="customer_type_lead" value="lead" {{ old('customer_type', 'lead') == 'lead' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="customer_type_lead">
                                                    <i class="fas fa-user-plus me-1"></i>Lead mới (chưa có tài khoản)
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="customer_type" id="customer_type_tenant" value="tenant" {{ old('customer_type') == 'tenant' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="customer_type_tenant">
                                                    <i class="fas fa-user me-1"></i>Khách thuê (đã có tài khoản)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    @error('customer_type')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Lead Section -->
                                <div id="leadSection" class="col-12 mb-4" style="display: {{ old('customer_type', 'lead') == 'lead' ? 'block' : 'none' }}">
                                    <div class="card border-warning">
                                        <div class="card-header bg-warning bg-opacity-10">
                                            <h6 class="mb-0">
                                                <i class="fas fa-user-plus me-1"></i>Thông tin Lead
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <!-- Lead Selection -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="lead_id" class="form-label">Chọn Lead</label>
                                                    <select class="form-select @error('lead_id') is-invalid @enderror" id="lead_id" name="lead_id">
                                                        <option value="">Chọn lead hoặc nhập thông tin mới</option>
                                                        @foreach($leads as $lead)
                                                            <option value="{{ $lead->id }}" {{ old('lead_id') == $lead->id ? 'selected' : '' }}>
                                                                {{ $lead->name }} - {{ $lead->phone }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('lead_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <!-- Lead Name -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="lead_name" class="form-label">Tên khách hàng <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('lead_name') is-invalid @enderror" 
                                                           id="lead_name" name="lead_name" value="{{ old('lead_name') }}" 
                                                           data-required-when="lead" {{ old('customer_type', 'lead') == 'lead' ? 'required' : '' }}>
                                                    @error('lead_name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <!-- Lead Phone -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="lead_phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('lead_phone') is-invalid @enderror" 
                                                           id="lead_phone" name="lead_phone" value="{{ old('lead_phone') }}" 
                                                           data-required-when="lead" {{ old('customer_type', 'lead') == 'lead' ? 'required' : '' }}>
                                                    @error('lead_phone')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <!-- Lead Email -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="lead_email" class="form-label">Email <span class="text-danger">*</span></label>
                                                    <input type="email" class="form-control @error('lead_email') is-invalid @enderror" 
                                                           id="lead_email" name="lead_email" value="{{ old('lead_email') }}">
                                                    @error('lead_email')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tenant Section -->
                                <div id="tenantSection" class="col-12 mb-4" style="display: {{ old('customer_type') == 'tenant' ? 'block' : 'none' }}">
                                    <div class="card border-info">
                                        <div class="card-header bg-info bg-opacity-10">
                                            <h6 class="mb-0">
                                                <i class="fas fa-user me-1"></i>Thông tin Khách thuê
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <!-- Tenant Selection -->
                                                <div class="col-12 mb-3">
                                                    <label for="tenant_id" class="form-label">Chọn khách thuê <span class="text-danger">*</span></label>
                                                    <select class="form-select @error('tenant_id') is-invalid @enderror" id="tenant_id" name="tenant_id">
                                                        <option value="">Chọn khách thuê</option>
                                                        @foreach($tenants as $tenant)
                                                            <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                                                                {{ $tenant->userProfile->full_name ?? $tenant->full_name ?? $tenant->name ?? $tenant->email ?? 'N/A' }} - {{ $tenant->email }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('tenant_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Property Selection -->
                                <div class="col-md-6 mb-3">
                                    <label for="property_id" class="form-label">Bất động sản <span class="text-danger">*</span></label>
                                    <select class="form-select @error('property_id') is-invalid @enderror" id="property_id" name="property_id" required>
                                        <option value="">Chọn bất động sản</option>
                                        @foreach($properties as $property)
                                            <option value="{{ $property->id }}" {{ old('property_id', $propertyId ?? '') == $property->id ? 'selected' : '' }}>
                                                {{ $property->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('property_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Unit Selection -->
                                <div class="col-md-6 mb-3">
                                    <label for="unit_id" class="form-label">Phòng <span class="text-danger">*</span></label>
                                    <select class="form-select @error('unit_id') is-invalid @enderror" id="unit_id" name="unit_id" required {{ $propertyId ? '' : 'disabled' }}>
                                        <option value="">Chọn bất động sản trước</option>
                                        @if($selectedUnit)
                                            <option value="{{ $selectedUnit->id }}" selected>{{ $selectedUnit->code }}</option>
                                        @endif
                                    </select>
                                    @error('unit_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Agent Selection -->
                                <div class="col-md-6 mb-3">
                                    <label for="agent_id" class="form-label">Agent phụ trách</label>
                                    <select class="form-select @error('agent_id') is-invalid @enderror" id="agent_id" name="agent_id">
                                        <option value="">Chọn agent</option>
                                        @foreach($agents as $agent)
                                            <option value="{{ $agent->id }}" {{ old('agent_id', $defaultAgentId ?? '') == $agent->id ? 'selected' : '' }}>
                                                {{ $agent->userProfile->full_name ?? $agent->full_name ?? $agent->name ?? $agent->email ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('agent_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Schedule Date & Time -->
                                <div class="col-md-6 mb-3">
                                    <label for="schedule_at" class="form-label">Thời gian hẹn <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control @error('schedule_at') is-invalid @enderror" 
                                           id="schedule_at" name="schedule_at" value="{{ old('schedule_at') }}" required>
                                    @error('schedule_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Status -->
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                        <option value="requested" {{ old('status', 'requested') == 'requested' ? 'selected' : '' }}>Chờ xác nhận</option>
                                        <option value="confirmed" {{ old('status') == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Note -->
                                <div class="col-12 mb-3">
                                    <label for="note" class="form-label">Ghi chú</label>
                                    <textarea class="form-control @error('note') is-invalid @enderror" 
                                              id="note" name="note" rows="3" placeholder="Ghi chú về lịch hẹn...">{{ old('note') }}</textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Actions Card -->
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
                                        'label' => 'Tạo lịch hẹn',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.viewings.index')
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    <!-- Guide Card -->
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
                                    <li>Loại khách hàng, bất động sản, phòng và thời gian hẹn là bắt buộc</li>
                                    <li>Chọn Lead hoặc nhập thông tin Lead mới</li>
                                    <li>Hoặc chọn Khách thuê từ danh sách</li>
                                    <li>Phòng sẽ được tải tự động sau khi chọn bất động sản</li>
                                    <li>Agent phụ trách có thể để trống</li>
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
    const form = document.getElementById('create-viewing-form');
    if (!form) return;
    
    // Customer type toggle
    const customerTypeRadios = document.querySelectorAll('input[name="customer_type"]');
    const leadSection = document.getElementById('leadSection');
    const tenantSection = document.getElementById('tenantSection');
    
    function toggleRequiredFields(customerType) {
        if (customerType === 'lead') {
            leadSection.style.display = 'block';
            tenantSection.style.display = 'none';
            // Enable required fields in lead section
            const leadRequiredFields = leadSection.querySelectorAll('[data-required-when="lead"]');
            leadRequiredFields.forEach(field => {
                field.setAttribute('required', 'required');
            });
            // Disable tenant field
            const tenantField = document.getElementById('tenant_id');
            if (tenantField) {
                tenantField.removeAttribute('required');
                tenantField.value = '';
            }
        } else if (customerType === 'tenant') {
            leadSection.style.display = 'none';
            tenantSection.style.display = 'block';
            // Remove required from all lead fields to prevent validation errors
            const leadFields = leadSection.querySelectorAll('[data-required-when="lead"], [name="lead_email"], [name="lead_id"]');
            leadFields.forEach(field => {
                field.removeAttribute('required');
                // Clear values but keep lead_id select as is (user might want to keep selection)
                if (field.name !== 'lead_id' && field.type !== 'hidden') {
                    field.value = '';
                }
            });
            // Enable required for tenant field
            const tenantField = document.getElementById('tenant_id');
            if (tenantField) {
                tenantField.setAttribute('required', 'required');
            }
        }
    }
    
    customerTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            toggleRequiredFields(this.value);
        });
    });
    
    // Initialize on page load
    const selectedCustomerType = document.querySelector('input[name="customer_type"]:checked');
    if (selectedCustomerType) {
        toggleRequiredFields(selectedCustomerType.value);
    }
    
    // Lead selection handler
    const leadSelect = document.getElementById('lead_id');
    const leadNameInput = document.getElementById('lead_name');
    const leadPhoneInput = document.getElementById('lead_phone');
    const leadEmailInput = document.getElementById('lead_email');
    
    if (leadSelect) {
        leadSelect.addEventListener('change', function() {
            const leadId = this.value;
            
            if (leadId) {
                // Fetch lead data and populate fields
                fetch(`{{ route('staff.leads.show', ':id') }}`.replace(':id', leadId), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.success && data.lead) {
                            if (leadNameInput) leadNameInput.value = data.lead.name || '';
                            if (leadPhoneInput) leadPhoneInput.value = data.lead.phone || '';
                            if (leadEmailInput) leadEmailInput.value = data.lead.email || '';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching lead data:', error);
                    });
            } else {
                // Clear fields when no lead is selected
                if (leadNameInput) leadNameInput.value = '';
                if (leadPhoneInput) leadPhoneInput.value = '';
                if (leadEmailInput) leadEmailInput.value = '';
            }
        });
        
        // Clear lead_id when user manually enters data
        [leadNameInput, leadPhoneInput, leadEmailInput].forEach(input => {
            if (input) {
                input.addEventListener('input', function() {
                    if (leadSelect.value) {
                        leadSelect.value = '';
                    }
                });
            }
        });
    }
    
    // Property change handler
    const propertySelect = document.getElementById('property_id');
    const unitSelect = document.getElementById('unit_id');
    
    // Function to load units for a property
    function loadUnitsForProperty(propertyId, selectedUnitId = null) {
        if (!propertyId) {
            unitSelect.disabled = true;
            unitSelect.innerHTML = '<option value="">Chọn bất động sản trước</option>';
            return;
        }
        
        // Enable unit select
        unitSelect.disabled = false;
        
        // Fetch units for this property
        fetch(`/api/properties/${propertyId}/units`)
            .then(response => response.json())
            .then(data => {
                unitSelect.innerHTML = '<option value="">Chọn phòng</option>';
                data.forEach(unit => {
                    const selected = selectedUnitId && unit.id == selectedUnitId ? 'selected' : '';
                    // Display unit info: code - floor/area/rent
                    const unitInfo = `${unit.code} - Tầng ${unit.floor || 'N/A'}, ${unit.area_m2 || 'N/A'}m² (Giá: ${unit.base_rent?.toLocaleString() || 'N/A'}đ)`;
                    unitSelect.innerHTML += `<option value="${unit.id}" ${selected}>${unitInfo}</option>`;
                });
            })
            .catch(error => {
                console.error('Error fetching units:', error);
                unitSelect.innerHTML = '<option value="">Lỗi tải danh sách phòng</option>';
            });
    }
    
    // Handle property change
    propertySelect.addEventListener('change', function() {
        loadUnitsForProperty(this.value);
    });
    
    // Auto-load units if property_id is pre-selected (from query params)
    @if($propertyId)
        loadUnitsForProperty('{{ $propertyId }}', {{ $unitId ?? 'null' }});
    @endif
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get current customer type
        const currentCustomerType = document.querySelector('input[name="customer_type"]:checked')?.value;
        
        // Clean up required attributes based on customer type before validation
        if (currentCustomerType === 'tenant') {
            // Remove required from all lead fields
            const leadFields = leadSection.querySelectorAll('[data-required-when="lead"]');
            leadFields.forEach(field => {
                field.removeAttribute('required');
            });
        } else if (currentCustomerType === 'lead') {
            // Remove required from tenant field
            const tenantField = document.getElementById('tenant_id');
            if (tenantField) {
                tenantField.removeAttribute('required');
            }
        }
        
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
            // Clear previous validation errors
            form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            form.querySelectorAll('.invalid-feedback').forEach(el => {
                el.remove();
            });
            
            // Handle 422 validation errors
            if (response.status === 422) {
                const data = await response.json();
                
                // Display validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('is-invalid');
                            let feedbackElement = input.parentElement.querySelector('.invalid-feedback');
                            if (!feedbackElement) {
                                feedbackElement = document.createElement('div');
                                feedbackElement.className = 'invalid-feedback';
                                input.parentElement.appendChild(feedbackElement);
                            }
                            feedbackElement.textContent = data.errors[field][0];
                        }
                    });
                }
                
                // Show error message
                const errorMessage = data.message || 'Vui lòng kiểm tra lại thông tin đã nhập.';
                Notify.error(errorMessage, 'Lỗi xác thực!');
                return;
            }
            
            // Handle other errors
            if (!response.ok) {
                const errorText = await response.text();
                let errorData;
                try {
                    errorData = JSON.parse(errorText);
                } catch (e) {
                    errorData = { message: `HTTP error! status: ${response.status}` };
                }
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }
            
            return response.json();
        })
        .then(data => {
            if (!data) return; // Already handled in previous then
            
            if (data.success) {
                Notify.success(data.message, 'Thành công!');
                setTimeout(() => {
                    window.location.href = data.redirect || '{{ route("staff.viewings.index") }}';
                }, 1500);
            } else {
                if (data.errors) {
                    // Show validation errors
                    Object.keys(data.errors).forEach(field => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('is-invalid');
                            let feedbackElement = input.parentElement.querySelector('.invalid-feedback');
                            if (!feedbackElement) {
                                feedbackElement = document.createElement('div');
                                feedbackElement.className = 'invalid-feedback';
                                input.parentElement.appendChild(feedbackElement);
                            }
                            feedbackElement.textContent = data.errors[field][0];
                        }
                    });
                }
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể tạo lịch hẹn: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
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
