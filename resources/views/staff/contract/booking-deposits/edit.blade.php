@extends('layouts.staff_dashboard')

@section('title', 'Sửa đặt cọc')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Sửa đặt cọc',
            'subtitle' => 'Chỉnh sửa thông tin đặt cọc: ' . $bookingDeposit->reference_number,
            'icon' => 'fas fa-edit',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.booking-deposits.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.booking-deposits.show', $bookingDeposit->id)
                ]
            ]
        ])

        {{-- Edit Form --}}
        <form id="booking-deposit-edit-form" method="POST" action="{{ route('staff.booking-deposits.update', $bookingDeposit->id) }}">
            @csrf
            @method('PUT')
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-edit me-2"></i>Chỉnh sửa thông tin đặt cọc
                            </h6>
                        </div>
                        <div class="card-body">
                            
                            <!-- Property Selection -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label required">Bất động sản</label>
                                    <select name="property_id" id="property-select" class="form-select" required>
                                        <option value="">Chọn bất động sản</option>
                                        @foreach($properties as $property)
                                        <option value="{{ $property->id }}" 
                                                {{ $bookingDeposit->unit && $bookingDeposit->unit->property_id == $property->id ? 'selected' : '' }}>
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
                                    <select name="unit_id" id="unit-select" class="form-select" required>
                                        <option value="">Chọn phòng</option>
                                        @foreach($units as $unit)
                                        <option value="{{ $unit->id }}" 
                                                {{ $bookingDeposit->unit_id == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->code }} - {{ $unit->property->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                    <div class="form-text">Chỉ hiển thị các phòng trong tổ chức của bạn</div>
                                </div>
                            </div>

                            <!-- Agent Selection -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label required">Nhân viên</label>
                                    <select name="agent_id" id="agent-select" class="form-select" required>
                                        <option value="">Chọn nhân viên</option>
                                        @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" 
                                                {{ $bookingDeposit->agent_id == $agent->id ? 'selected' : '' }}>
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
                                           value="{{ number_format($bookingDeposit->amount, 0, ',', '.') }}"
                                           placeholder="Nhập số tiền (ví dụ: 2.000.000)" required>
                                    <div class="invalid-feedback"></div>
                                    <div class="form-text">Nhập số tiền (sẽ tự động format với dấu chấm)</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Loại đặt cọc</label>
                                    <select name="deposit_type" id="deposit-type" class="form-select" required>
                                        <option value="">Chọn loại đặt cọc</option>
                                        <option value="booking" {{ $bookingDeposit->deposit_type == 'booking' ? 'selected' : '' }}>Đặt cọc</option>
                                        <option value="security" {{ $bookingDeposit->deposit_type == 'security' ? 'selected' : '' }}>Cọc an ninh</option>
                                        <option value="advance" {{ $bookingDeposit->deposit_type == 'advance' ? 'selected' : '' }}>Trả trước</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            {{-- Trạng thái sẽ được quản lý ở trang show, không cho phép thay đổi ở form create/edit --}}
                            <input type="hidden" name="payment_status" value="{{ $bookingDeposit->payment_status }}">

                            <!-- Hold Until -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label required">Giữ chỗ đến</label>
                                    <input type="datetime-local" name="hold_until" id="hold-until" class="form-control" 
                                           value="{{ $bookingDeposit->hold_until ? $bookingDeposit->hold_until->format('Y-m-d\TH:i') : '' }}"
                                           min="{{ now()->format('Y-m-d\TH:i') }}" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Ghi chú</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3" 
                                              placeholder="Ghi chú thêm về đặt cọc...">{{ $bookingDeposit->notes }}</textarea>
                                </div>
                            </div>
                            
                            @if($bookingDeposit->payment_due_date)
                            <!-- Payment Due Date Info (Read-only) -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Hạn chót thanh toán:</strong> 
                                        {{ $bookingDeposit->payment_due_date->format('d/m/Y H:i') }}
                                        @if($bookingDeposit->payment_due_date < now() && $bookingDeposit->payment_status === 'pending')
                                            <span class="text-danger ms-2"><i class="fas fa-exclamation-triangle"></i> Đã quá hạn</span>
                                        @endif
                                        <br>
                                        @php
                                            $defaultPaymentCycle = $bookingDeposit->organization?->defaultPaymentCycle;
                                            $paymentDueMinutes = $defaultPaymentCycle?->payment_due_hours ?? 4320; // Default 72 hours = 4320 minutes
                                            $paymentDueHours = floor($paymentDueMinutes / 60);
                                        @endphp
                                        <small>Hạn chót thanh toán được tự động tính khi phê duyệt đặt cọc (thời gian phê duyệt + {{ $paymentDueHours }} giờ)</small>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Lead Selection -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label class="form-label required">Khách hàng tiềm năng</label>
                                    <select name="lead_id" id="lead-select" class="form-select" required>
                                        <option value="">Chọn khách hàng tiềm năng</option>
                                        @foreach($leads as $lead)
                                        <option value="{{ $lead->id }}" 
                                                data-tenant-id="{{ $lead->tenant_id ?? '' }}"
                                                {{ $bookingDeposit->lead_id == $lead->id ? 'selected' : '' }}>
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
                                    'label' => 'Cập nhật đặt cọc',
                                    'icon' => 'fas fa-save'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Hủy',
                                    'icon' => 'fas fa-times',
                                    'url' => route('staff.booking-deposits.show', $bookingDeposit->id)
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
                            <label class="form-label fw-bold small text-muted mb-1">Mã đặt cọc:</label>
                            <div class="p-2 bg-light rounded">
                                <span class="badge bg-primary">{{ $bookingDeposit->reference_number }}</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted mb-1">Trạng thái:</label>
                            <div class="p-2 bg-light rounded">
                                @switch($bookingDeposit->payment_status)
                                    @case('pending_approval')
                                        <span class="badge bg-warning">Chờ duyệt</span>
                                        @break
                                    @case('pending')
                                        <span class="badge bg-warning">Chờ thanh toán</span>
                                        @break
                                    @case('paid')
                                        <span class="badge bg-success">Đã thanh toán</span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-danger">Đã hủy</span>
                                        @break
                                    @case('refunded')
                                        <span class="badge bg-secondary">Hoàn tiền</span>
                                        @break
                                    @default
                                        <span class="badge bg-light text-dark">{{ $bookingDeposit->payment_status }}</span>
                                @endswitch
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted mb-1">Ngày tạo:</label>
                            <div class="p-2 bg-light rounded">
                                <i class="fas fa-calendar-plus me-1 text-muted"></i>
                                {{ $bookingDeposit->created_at->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                        
                        @if($bookingDeposit->approved_at)
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted mb-1">Ngày duyệt:</label>
                            <div class="p-2 bg-light rounded">
                                <i class="fas fa-check-circle me-1 text-success"></i>
                                {{ $bookingDeposit->approved_at->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                        @endif
                        
                        @if($bookingDeposit->paid_at)
                        <div class="mb-0">
                            <label class="form-label fw-bold small text-muted mb-1">Ngày thanh toán:</label>
                            <div class="p-2 bg-light rounded">
                                <i class="fas fa-money-bill-wave me-1 text-success"></i>
                                {{ $bookingDeposit->paid_at->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                        @endif
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
    // Form is ready
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


// Form submission
document.getElementById('booking-deposit-edit-form').addEventListener('submit', function(e) {
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
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang cập nhật...';
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
            Notify.success(data.message, 'Cập nhật đặt cọc thành công!');
            
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                // Fallback về show page
                setTimeout(() => {
                    window.location.href = '{{ route("staff.booking-deposits.show", $bookingDeposit->id) }}';
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
            
            Notify.error(data.message, 'Không thể cập nhật đặt cọc');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Notify.error('Có lỗi xảy ra khi cập nhật đặt cọc. Vui lòng thử lại.', 'Lỗi hệ thống');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

    function loadUnits(propertyId) {
        const unitSelect = document.getElementById('unit-select');
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
            } else {
                unitSelect.innerHTML = '<option value="">Không có phòng nào</option>';
            }
        })
        .catch(error => {
            console.error('Error loading units:', error);
            unitSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
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
