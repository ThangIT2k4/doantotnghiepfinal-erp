@extends('layouts.staff_dashboard')

@section('title', 'Sửa lịch hẹn')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Sửa lịch hẹn',
            'subtitle' => 'Cập nhật thông tin lịch hẹn #' . $viewing->id,
            'icon' => 'fas fa-calendar-edit',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.viewings.index')
                ],
                [
                    'variant' => 'info',           // ✅ Solid
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.viewings.show', $viewing->id)
                ]
            ]
        ])

        <!-- Edit Form -->
        <form id="edit-viewing-form" action="{{ route('staff.viewings.update', $viewing->id) }}" method="POST">
            @csrf
            @method('PUT')
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
                                                <input class="form-check-input" type="radio" name="customer_type" id="customer_type_lead" value="lead" {{ old('customer_type', $viewing->customer_type) == 'lead' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="customer_type_lead">
                                                    <i class="fas fa-user-plus me-1"></i>Lead mới (chưa có tài khoản)
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="customer_type" id="customer_type_tenant" value="tenant" {{ old('customer_type', $viewing->customer_type) == 'tenant' ? 'checked' : '' }}>
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
                                <div id="leadSection" class="col-12 mb-4" style="display: {{ old('customer_type', $viewing->customer_type) == 'lead' ? 'block' : 'none' }}">
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
                                                            <option value="{{ $lead->id }}" {{ old('lead_id', $viewing->lead_id) == $lead->id ? 'selected' : '' }}>
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
                                                           id="lead_name" name="lead_name" value="{{ old('lead_name', $viewing->customer_name) }}">
                                                    @error('lead_name')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <!-- Lead Phone -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="lead_phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control @error('lead_phone') is-invalid @enderror" 
                                                           id="lead_phone" name="lead_phone" value="{{ old('lead_phone', $viewing->lead_phone) }}">
                                                    @error('lead_phone')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <!-- Lead Email -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="lead_email" class="form-label">Email <span class="text-danger">*</span></label>
                                                    <input type="email" class="form-control @error('lead_email') is-invalid @enderror" 
                                                           id="lead_email" name="lead_email" value="{{ old('lead_email', $viewing->lead_email) }}" required>
                                                    @error('lead_email')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tenant Section -->
                                <div id="tenantSection" class="col-12 mb-4" style="display: {{ old('customer_type', $viewing->customer_type) == 'tenant' ? 'block' : 'none' }}">
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
                                                            <option value="{{ $tenant->id }}" {{ old('tenant_id', $viewing->tenant_id) == $tenant->id ? 'selected' : '' }}>
                                                                {{ $tenant->full_name }} - {{ $tenant->email }}
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
                                            <option value="{{ $property->id }}" {{ old('property_id', $viewing->property_id) == $property->id ? 'selected' : '' }}>
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
                                    <select class="form-select @error('unit_id') is-invalid @enderror" id="unit_id" name="unit_id" required>
                                        <option value="">Chọn phòng</option>
                                        @if($viewing->unit)
                                            <option value="{{ $viewing->unit->id }}" selected>
                                                {{ $viewing->unit->code }} - {{ $viewing->unit->name }}
                                            </option>
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
                                            <option value="{{ $agent->id }}" {{ old('agent_id', $viewing->agent_id) == $agent->id ? 'selected' : '' }}>
                                                {{ $agent->full_name }}
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
                                           id="schedule_at" name="schedule_at" value="{{ old('schedule_at', $viewing->schedule_at->format('Y-m-d\TH:i')) }}" required>
                                    @error('schedule_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Status -->
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                        <option value="requested" {{ old('status', $viewing->status) == 'requested' ? 'selected' : '' }}>Chờ xác nhận</option>
                                        <option value="confirmed" {{ old('status', $viewing->status) == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                                        <option value="done" {{ old('status', $viewing->status) == 'done' ? 'selected' : '' }}>Hoàn thành</option>
                                        <option value="no_show" {{ old('status', $viewing->status) == 'no_show' ? 'selected' : '' }}>Không đến</option>
                                        <option value="cancelled" {{ old('status', $viewing->status) == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Note -->
                                <div class="col-12 mb-3">
                                    <label for="note" class="form-label">Ghi chú</label>
                                    <textarea class="form-control @error('note') is-invalid @enderror" 
                                              id="note" name="note" rows="3" placeholder="Ghi chú về lịch hẹn...">{{ old('note', $viewing->note) }}</textarea>
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
                                        'label' => 'Cập nhật lịch hẹn',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.viewings.show', $viewing->id)
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    <!-- Current Info Card -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Trạng thái:</label>
                                <div>
                                    @include('staff.components.status-badge', [
                                        'status' => $viewing->status,
                                        'type' => 'viewing'
                                    ])
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Khách hàng:</label>
                                <div>{{ $viewing->customer_name }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Bất động sản:</label>
                                <div>{{ $viewing->property->name }}</div>
                            </div>
                            @if($viewing->unit)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phòng:</label>
                                <div>{{ $viewing->unit->code }} - {{ $viewing->unit->name }}</div>
                            </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label fw-bold">Thời gian hẹn:</label>
                                <div>{{ $viewing->schedule_at->format('d/m/Y H:i') }}</div>
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
    const form = document.getElementById('edit-viewing-form');
    if (!form) return;
    
    // Customer type toggle
    const customerTypeRadios = document.querySelectorAll('input[name="customer_type"]');
    const leadSection = document.getElementById('leadSection');
    const tenantSection = document.getElementById('tenantSection');
    
    customerTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'lead') {
                leadSection.style.display = 'block';
                tenantSection.style.display = 'none';
            } else {
                leadSection.style.display = 'none';
                tenantSection.style.display = 'block';
            }
        });
    });
    
    // Property change handler
    const propertySelect = document.getElementById('property_id');
    const unitSelect = document.getElementById('unit_id');
    
    propertySelect.addEventListener('change', function() {
        const propertyId = this.value;
        
        if (propertyId) {
            // Enable unit select
            unitSelect.disabled = false;
            
            // Fetch units for this property
            fetch(`/api/properties/${propertyId}/units`)
                .then(response => response.json())
                .then(data => {
                    unitSelect.innerHTML = '<option value="">Chọn phòng</option>';
                    data.forEach(unit => {
                        // Display unit info: code - floor/area/rent
                        const unitInfo = `${unit.code} - Tầng ${unit.floor || 'N/A'}, ${unit.area_m2 || 'N/A'}m² (Giá: ${unit.base_rent?.toLocaleString() || 'N/A'}đ)`;
                        unitSelect.innerHTML += `<option value="${unit.id}">${unitInfo}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Error fetching units:', error);
                    unitSelect.innerHTML = '<option value="">Lỗi tải danh sách phòng</option>';
                });
        } else {
            unitSelect.disabled = true;
            unitSelect.innerHTML = '<option value="">Chọn bất động sản trước</option>';
        }
    });
    
    // Form submission
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
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Thành công!');
                setTimeout(() => {
                    window.location.href = data.redirect || '{{ route("staff.viewings.show", $viewing->id) }}';
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
            Notify.error('Không thể cập nhật lịch hẹn: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
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
