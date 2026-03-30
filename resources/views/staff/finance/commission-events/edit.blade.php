@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa Sự kiện Hoa hồng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Chỉnh sửa Sự kiện Hoa hồng',
            'subtitle' => '#' . $commissionEvent->id . ($commissionEvent->policy ? ' - ' . $commissionEvent->policy->title : ''),
            'icon' => 'fas fa-edit',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.commission-events.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.commission-events.show', $commissionEvent->id)
                ]
            ]
        ])

    <form action="{{ route('staff.commission-events.update', $commissionEvent->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <!-- Basic Information -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Thông tin cơ bản</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nhân viên <span class="text-danger">*</span></label>
                                <select class="form-select @error('agent_id') is-invalid @enderror" 
                                        name="agent_id" id="agent_id" required>
                                    <option value="">Chọn nhân viên</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" {{ old('agent_id', $commissionEvent->agent_id) == $agent->id ? 'selected' : '' }}>
                                            {{ $agent->full_name }} ({{ $agent->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('agent_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Chính sách hoa hồng <span class="text-danger">*</span></label>
                                <select class="form-select @error('policy_id') is-invalid @enderror" 
                                        name="policy_id" id="policy_id" required>
                                    <option value="">Chọn chính sách</option>
                                    @foreach($policies as $policy)
                                        <option value="{{ $policy->id }}" 
                                                data-trigger="{{ $policy->trigger_event }}"
                                                data-calc-type="{{ $policy->calc_type }}"
                                                data-percent="{{ $policy->percent_value }}"
                                                data-flat="{{ $policy->flat_amount }}"
                                                {{ old('policy_id', $commissionEvent->policy_id) == $policy->id ? 'selected' : '' }}>
                                            {{ $policy->title }} ({{ $policy->code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('policy_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sự kiện kích hoạt <span class="text-danger">*</span></label>
                                <select class="form-select @error('trigger_event') is-invalid @enderror" 
                                        name="trigger_event" id="trigger_event" required>
                                    <option value="">Chọn sự kiện kích hoạt</option>
                                    @foreach($triggerEvents as $key => $label)
                                        <option value="{{ $key }}" {{ old('trigger_event', $commissionEvent->trigger_event) == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('trigger_event')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày xảy ra <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control @error('occurred_at') is-invalid @enderror" 
                                       name="occurred_at" id="occurred_at" value="{{ old('occurred_at', $commissionEvent->occurred_at->format('Y-m-d\TH:i')) }}" required>
                                @error('occurred_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Records -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Bản ghi liên quan</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Hợp đồng thuê</label>
                                <select class="form-select @error('lease_id') is-invalid @enderror" 
                                        name="lease_id" id="lease_id">
                                    <option value="">Chọn hợp đồng thuê</option>
                                    @foreach($leases as $lease)
                                        @php
                                            $contractNo = $lease->contract_no ?? 'HD' . str_pad($lease->id, 6, '0', STR_PAD_LEFT);
                                            $propertyName = $lease->unit->property->name ?? 'N/A';
                                            $unitCode = $lease->unit->code ?? ($lease->unit->name ?? 'N/A');
                                            $displayText = $contractNo . ' - ' . $propertyName . ' - ' . $unitCode;
                                        @endphp
                                        <option value="{{ $lease->id }}" 
                                                data-rent-amount="{{ $lease->rent_amount ?? 0 }}"
                                                data-unit-id="{{ $lease->unit_id ?? '' }}"
                                                {{ old('lease_id', $commissionEvent->lease_id) == $lease->id ? 'selected' : '' }}>
                                            {{ $displayText }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('lease_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Amount Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Thông tin số tiền</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số tiền gốc (VND) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control money-input @error('amount_base') is-invalid @enderror" 
                                       name="amount_base" id="amount_base" value="{{ old('amount_base', $commissionEvent->amount_base ? number_format($commissionEvent->amount_base, 0, ',', '.') : '') }}" 
                                       placeholder="VD: 10.000.000" min="0" required>
                                @error('amount_base')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hoa hồng tính toán (VND)</label>
                                <input type="text" class="form-control money-input" id="calculated_commission" 
                                       readonly placeholder="Sẽ được tính tự động">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hoa hồng thực tế (VND)</label>
                                <input type="text" class="form-control money-input @error('commission_total') is-invalid @enderror" 
                                       name="commission_total" id="commission_total" value="{{ old('commission_total', $commissionEvent->commission_total ? number_format($commissionEvent->commission_total, 0, ',', '.') : '') }}" 
                                       placeholder="VD: 500.000" min="0">
                                @error('commission_total')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        name="status" id="status">
                                    @foreach($statuses as $key => $label)
                                        <option value="{{ $key }}" {{ old('status', $commissionEvent->status) == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions & Preview -->
            <div class="col-lg-4">
                <!-- Commission Preview -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Xem trước hoa hồng</h6>
                    </div>
                    <div class="card-body">
                        <div id="commission-preview">
                            <p class="text-muted">Chọn chính sách và nhập số tiền để xem trước</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card shadow">
                    <div class="card-body">
                        @include('staff.components.action-buttons', [
                            'layout' => 'vertical',
                            'size' => 'md',
                            'actions' => [
                                [
                                    'type' => 'submit',
                                    'variant' => 'primary',
                                    'label' => 'Cập nhật',
                                    'icon' => 'fas fa-save'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Hủy',
                                    'icon' => 'fas fa-times',
                                    'url' => route('staff.commission-events.show', $commissionEvent->id)
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
    // Show session messages
    @if(session('success'))
        Notify.success('{{ session('success') }}', 'Thành công!');
    @endif

    @if(session('error'))
        Notify.error('{{ session('error') }}', 'Lỗi!');
    @endif

    @if(session('warning'))
        Notify.warning('{{ session('warning') }}', 'Cảnh báo!');
    @endif

    @if(session('info'))
        Notify.info('{{ session('info') }}', 'Thông tin!');
    @endif

    // Initialize commission calculation
    calculateCommission();
});

// Calculate commission when policy or amount changes
function calculateCommission() {
    const policySelect = document.getElementById('policy_id');
    const amountInput = document.getElementById('amount_base');
    const calculatedInput = document.getElementById('calculated_commission');
    const totalInput = document.getElementById('commission_total');
    const preview = document.getElementById('commission-preview');
    
    if (!policySelect.value || !amountInput.value) {
        calculatedInput.value = '';
        preview.innerHTML = '<p class="text-muted">Chọn chính sách và nhập số tiền để xem trước</p>';
        return;
    }
    
    const selectedOption = policySelect.options[policySelect.selectedIndex];
    const calcType = selectedOption.dataset.calcType;
    const percentValue = parseFloat(selectedOption.dataset.percent) || 0;
    const flatAmount = parseFloat(selectedOption.dataset.flat) || 0;
    // Unformat amount for calculation
    const amountUnformatted = amountInput.value ? amountInput.value.replace(/\./g, '') : '0';
    const amount = parseFloat(amountUnformatted) || 0;
    
    let calculated = 0;
    let previewHtml = '';
    
    if (calcType === 'percent') {
        calculated = (amount * percentValue) / 100;
        previewHtml = `
            <div class="mb-2">
                <strong>Loại:</strong> Phần trăm (${percentValue}%)
            </div>
            <div class="mb-2">
                <strong>Số tiền gốc:</strong> ${amount.toLocaleString('vi-VN')} VND
            </div>
            <div class="mb-2">
                <strong>Hoa hồng:</strong> ${calculated.toLocaleString('vi-VN')} VND
            </div>
        `;
    } else if (calcType === 'flat') {
        calculated = flatAmount;
        previewHtml = `
            <div class="mb-2">
                <strong>Loại:</strong> Số tiền cố định
            </div>
            <div class="mb-2">
                <strong>Số tiền gốc:</strong> ${amount.toLocaleString('vi-VN')} VND
            </div>
            <div class="mb-2">
                <strong>Hoa hồng:</strong> ${calculated.toLocaleString('vi-VN')} VND
            </div>
        `;
    } else {
        previewHtml = '<p class="text-muted">Loại tính toán bậc thang chưa được hỗ trợ</p>';
    }
    
    // Format calculated value for display
    calculatedInput.value = calculated > 0 ? calculated.toLocaleString('vi-VN').replace(/,/g, '.') : '';
    preview.innerHTML = previewHtml;
}

// Form submission with loading state
function submitForm() {
    const form = document.querySelector('form');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Show loading notification
    const loadingToast = Notify.toast({
        title: 'Đang cập nhật sự kiện...',
        message: 'Vui lòng chờ trong giây lát',
        type: 'info',
        duration: 0
    });

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...';

    // Submit form
    form.submit();
}

// Form validation with notifications
function validateForm() {
    const requiredFields = [
        { id: 'agent_id', name: 'Nhân viên' },
        { id: 'policy_id', name: 'Chính sách hoa hồng' },
        { id: 'trigger_event', name: 'Sự kiện kích hoạt' },
        { id: 'occurred_at', name: 'Ngày xảy ra' },
        { id: 'amount_base', name: 'Số tiền gốc' }
    ];

    for (const field of requiredFields) {
        const element = document.getElementById(field.id);
        if (!element) {
            console.error(`Element with id '${field.id}' not found`);
            Notify.error(`Không tìm thấy trường ${field.name.toLowerCase()}.`, 'Lỗi hệ thống!');
            return false;
        }
        if (!element.value || !element.value.trim()) {
            Notify.warning(`Vui lòng nhập ${field.name.toLowerCase()}.`, 'Thiếu thông tin!');
            element.focus();
            return false;
        }
    }

    // Validate amount
    const amountElement = document.getElementById('amount_base');
    if (!amountElement) {
        console.error('Element with id "amount_base" not found');
        Notify.error('Không tìm thấy trường số tiền gốc.', 'Lỗi hệ thống!');
        return false;
    }
    // Unformat amount for validation
    const amountUnformatted = amountElement.value ? amountElement.value.replace(/\./g, '') : '';
    const amount = parseFloat(amountUnformatted);
    if (isNaN(amount) || amount <= 0) {
        Notify.warning('Số tiền gốc phải lớn hơn 0.', 'Dữ liệu không hợp lệ!');
        amountElement.focus();
        return false;
    }

    // Validate commission total if provided
    const commissionTotalElement = document.getElementById('commission_total');
    if (commissionTotalElement && commissionTotalElement.value) {
        // Unformat commission total for validation
        const commissionTotalUnformatted = commissionTotalElement.value.replace(/\./g, '');
        const commissionTotal = parseFloat(commissionTotalUnformatted);
        if (isNaN(commissionTotal) || commissionTotal < 0) {
            Notify.warning('Hoa hồng thực tế phải lớn hơn hoặc bằng 0.', 'Dữ liệu không hợp lệ!');
            commissionTotalElement.focus();
            return false;
        }
    }

    // Validate date
    const occurredAtElement = document.getElementById('occurred_at');
    if (!occurredAtElement) {
        console.error('Element with id "occurred_at" not found');
        Notify.error('Không tìm thấy trường ngày xảy ra.', 'Lỗi hệ thống!');
        return false;
    }
    const occurredAt = new Date(occurredAtElement.value);
    const now = new Date();
    if (isNaN(occurredAt.getTime())) {
        Notify.warning('Ngày xảy ra không hợp lệ.', 'Dữ liệu không hợp lệ!');
        occurredAtElement.focus();
        return false;
    }
    if (occurredAt > now) {
        Notify.warning('Ngày xảy ra không thể lớn hơn ngày hiện tại.', 'Dữ liệu không hợp lệ!');
        occurredAtElement.focus();
        return false;
    }

    // Validate status if provided
    const statusElement = document.getElementById('status');
    if (statusElement && statusElement.value) {
        const validStatuses = ['pending', 'approved', 'paid', 'cancelled'];
        if (!validStatuses.includes(statusElement.value)) {
            Notify.warning('Trạng thái không hợp lệ.', 'Dữ liệu không hợp lệ!');
            statusElement.focus();
            return false;
        }
    }

    // Validate lease if provided
    const leaseElement = document.getElementById('lease_id');
    if (leaseElement && leaseElement.value) {
        const leaseId = parseInt(leaseElement.value);
        if (isNaN(leaseId) || leaseId <= 0) {
            Notify.warning('Hợp đồng thuê không hợp lệ.', 'Dữ liệu không hợp lệ!');
            leaseElement.focus();
            return false;
        }
    }


    // Validate policy if provided
    const policyElement = document.getElementById('policy_id');
    if (policyElement && policyElement.value) {
        const policyId = parseInt(policyElement.value);
        if (isNaN(policyId) || policyId <= 0) {
            Notify.warning('Chính sách hoa hồng không hợp lệ.', 'Dữ liệu không hợp lệ!');
            policyElement.focus();
            return false;
        }
    }

    // Validate trigger event if provided
    const triggerEventElement = document.getElementById('trigger_event');
    if (triggerEventElement && triggerEventElement.value) {
        const validTriggerEvents = ['deposit_paid', 'lease_signed', 'invoice_paid', 'viewing_done', 'listing_published'];
        if (!validTriggerEvents.includes(triggerEventElement.value)) {
            Notify.warning('Sự kiện kích hoạt không hợp lệ.', 'Dữ liệu không hợp lệ!');
            triggerEventElement.focus();
            return false;
        }
    }

    // Validate agent if provided
    const agentElement = document.getElementById('agent_id');
    if (agentElement && agentElement.value) {
        const agentId = parseInt(agentElement.value);
        if (isNaN(agentId) || agentId <= 0) {
            Notify.warning('Nhân viên không hợp lệ.', 'Dữ liệu không hợp lệ!');
            agentElement.focus();
            return false;
        }
    }

    return true;
}

// Cancel form function
function cancelForm() {
    Notify.confirm('Bạn có chắc chắn muốn hủy chỉnh sửa sự kiện hoa hồng? Thay đổi chưa lưu sẽ bị mất.', () => {
        // Show loading notification
        const loadingToast = Notify.toast({
            title: 'Đang chuyển hướng...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 1000
        });

        // Navigate back to show page
        window.location.href = '{{ route('staff.commission-events.show', $commissionEvent->id) }}';
    });
}

// Event listeners
document.getElementById('policy_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        document.getElementById('trigger_event').value = selectedOption.dataset.trigger;
    }
    calculateCommission();
});

document.getElementById('amount_base').addEventListener('input', calculateCommission);

// Lease selection handler - auto-fill amount and unit
const leaseSelect = document.getElementById('lease_id');
if (leaseSelect) {
    // Create hidden input for unit_id if not exists
    let unitIdInput = document.querySelector('input[name="unit_id"]');
    if (!unitIdInput) {
        unitIdInput = document.createElement('input');
        unitIdInput.type = 'hidden';
        unitIdInput.name = 'unit_id';
        leaseSelect.closest('.card-body').appendChild(unitIdInput);
    }
    
    // Set initial unit_id if lease is pre-selected
    if (leaseSelect.value) {
        const selectedOption = leaseSelect.options[leaseSelect.selectedIndex];
        if (selectedOption && selectedOption.dataset.unitId) {
            unitIdInput.value = selectedOption.dataset.unitId;
        }
    }
    
    leaseSelect.addEventListener('change', function() {
        const leaseId = this.value;
        const amountInput = document.getElementById('amount_base');
        
        if (leaseId) {
            const selectedOption = this.options[this.selectedIndex];
            const rentAmount = parseFloat(selectedOption.dataset.rentAmount) || 0;
            const unitId = selectedOption.dataset.unitId || '';
            
            // Auto-fill amount if not already filled
            if (rentAmount > 0 && (!amountInput.value || amountInput.value.trim() === '')) {
                // Format amount for display
                const formattedAmount = Math.round(rentAmount).toLocaleString('vi-VN').replace(/,/g, '.');
                amountInput.value = formattedAmount;
                // Trigger input event to trigger commission calculation
                amountInput.dispatchEvent(new Event('input'));
            }
            
            // Set unit_id as hidden field
            if (unitId) {
                unitIdInput.value = unitId;
            } else {
                unitIdInput.value = '';
            }
        } else {
            // Clear unit_id when no lease selected
            unitIdInput.value = '';
        }
    });
}

// Agent selection handler - filter leases by agent
const agentSelect = document.getElementById('agent_id');
if (agentSelect && leaseSelect) {
    // Store all leases initially (from server)
    const allLeases = Array.from(leaseSelect.options).map(option => ({
        value: option.value,
        text: option.text,
        rentAmount: option.dataset.rentAmount || '0',
        unitId: option.dataset.unitId || ''
    }));

    agentSelect.addEventListener('change', function() {
        const agentId = this.value;
        const currentLeaseValue = leaseSelect.value; // Lưu giá trị hiện tại
        
        // Clear current options except the first one
        leaseSelect.innerHTML = '<option value="">Chọn hợp đồng thuê</option>';
        
        if (agentId) {
            // Show loading state
            leaseSelect.disabled = true;
            leaseSelect.innerHTML = '<option value="">Đang tải hợp đồng...</option>';
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            // Fetch leases filtered by agent_id
            fetch('{{ route("staff.api.commission-events.leases-by-agent") }}?agent_id=' + encodeURIComponent(agentId), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            })
            .then(response => {
                // Check if response is ok
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'HTTP error! status: ' + response.status);
                    }).catch(() => {
                        throw new Error('HTTP error! status: ' + response.status);
                    });
                }
                return response.json();
            })
            .then(data => {
                leaseSelect.innerHTML = '<option value="">Chọn hợp đồng thuê</option>';
                
                if (data.success && data.leases && Array.isArray(data.leases) && data.leases.length > 0) {
                    data.leases.forEach(lease => {
                        if (lease && lease.id) {
                            const option = document.createElement('option');
                            option.value = lease.id;
                            option.textContent = lease.text || ('HD' + String(lease.id).padStart(6, '0'));
                            option.dataset.rentAmount = lease.rent_amount || '0';
                            option.dataset.unitId = lease.unit_id || '';
                            
                            // Restore selected value if it still exists in filtered list
                            if (currentLeaseValue && currentLeaseValue == lease.id) {
                                option.selected = true;
                            }
                            
                            leaseSelect.appendChild(option);
                        }
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Không có hợp đồng nào';
                    leaseSelect.appendChild(option);
                }
                
                leaseSelect.disabled = false;
                
                // Trigger change event if a lease was restored
                if (currentLeaseValue && leaseSelect.value == currentLeaseValue) {
                    leaseSelect.dispatchEvent(new Event('change'));
                }
            })
            .catch(error => {
                console.error('Error fetching leases:', error);
                leaseSelect.innerHTML = '<option value="">Lỗi khi tải hợp đồng. Vui lòng thử lại.</option>';
                leaseSelect.disabled = false;
                
                // Show user-friendly error message
                if (window.Notify) {
                    Notify.error('Không thể tải danh sách hợp đồng. Vui lòng thử lại sau.', 'Lỗi!');
                }
            });
        } else {
            // If no agent selected, show all leases
            allLeases.forEach(lease => {
                if (lease.value) { // Skip the first empty option
                    const option = document.createElement('option');
                    option.value = lease.value;
                    option.textContent = lease.text;
                    option.dataset.rentAmount = lease.rentAmount;
                    option.dataset.unitId = lease.unitId;
                    
                    // Restore selected value if it exists
                    if (currentLeaseValue && currentLeaseValue == lease.value) {
                        option.selected = true;
                    }
                    
                    leaseSelect.appendChild(option);
                }
            });
            
            leaseSelect.disabled = false;
            
            // Trigger change event if a lease was restored
            if (currentLeaseValue && leaseSelect.value == currentLeaseValue) {
                leaseSelect.dispatchEvent(new Event('change'));
            }
        }
    });
    
    // Auto-filter leases on page load if agent is already selected
    if (agentSelect.value) {
        agentSelect.dispatchEvent(new Event('change'));
    }
}

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const submitBtn = form.querySelector('button[type="submit"]');
    const cancelBtn = form.querySelector('a[href*="show"]');

    // Unformat money inputs before form submission
    form.addEventListener('submit', function(e) {
        // Unformat all money inputs
        if (window.NumberFormatter && window.NumberFormatter.processFormBeforeSubmit) {
            window.NumberFormatter.processFormBeforeSubmit(form);
        } else {
            // Fallback: manually unformat
            const moneyInputs = form.querySelectorAll('.money-input');
            moneyInputs.forEach(input => {
                if (input.value && !input.readOnly) {
                    input.value = input.value.replace(/\./g, '');
                }
            });
        }
    });

    // Override form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (validateForm()) {
            submitForm();
        }
    });

    // Override cancel button
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            cancelForm();
        });
    }
});
</script>
@endpush
