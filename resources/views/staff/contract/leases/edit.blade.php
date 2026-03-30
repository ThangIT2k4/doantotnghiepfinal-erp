@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa Hợp đồng')

@section('content')
<main class="main-content">
    <header class="header">
        <div class="header-content">
            <div class="header-info">
                <h1>Chỉnh sửa Hợp đồng</h1>
                <p>Cập nhật thông tin hợp đồng thuê</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('staff.leases.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại
                </a>
            </div>
        </div>
    </header>
    
    <div class="content" id="content">
        <div class="card">
            <div class="card-body">
                <form id="leaseForm" method="POST" action="{{ route('staff.leases.update', $lease->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Thông tin cơ bản</h5>
                            
                            <!-- Số hợp đồng - đưa lên đầu, readonly, hiển thị mã hiện tại -->
                            <div class="mb-3">
                                <label class="form-label">Số hợp đồng</label>
                                <div class="input-group">
                                    <input type="text" name="contract_no" class="form-control" 
                                           value="{{ old('contract_no', $lease->contract_no) }}" 
                                           placeholder="{{ $lease->contract_no ?? 'Chưa có mã hợp đồng' }}" 
                                           readonly 
                                           style="background-color: #f8f9fa;">
                                    <span class="input-group-text">
                                        <i class="fas fa-file-contract text-primary"></i>
                                    </span>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-lock text-warning"></i>
                                    Mã hợp đồng không thể thay đổi sau khi đã tạo
                                </small>
                            </div>

                            @if($bookingDeposits && $bookingDeposits->count() > 0)
                            <div class="mb-3">
                                <label class="form-label">
                                    Booking Deposit đã thanh toán (Tùy chọn)
                                </label>
                                <select name="booking_deposit_id" id="booking_deposit_id" class="form-select">
                                    <option value="">Chọn booking deposit (để tự động điền thông tin)</option>
                                    @if(isset($selectedBookingDepositId) && $selectedBookingDepositId)
                                    <option value="{{ $selectedBookingDepositId }}" selected
                                        data-property-id="{{ $selectedBookingDeposit->unit->property_id ?? '' }}"
                                        data-unit-id="{{ $selectedBookingDeposit->unit_id ?? '' }}"
                                        data-lead-id="{{ $selectedBookingDeposit->lead_id ?? '' }}"
                                        data-agent-id="{{ $selectedBookingDeposit->agent_id ?? '' }}"
                                        data-amount="{{ $selectedBookingDeposit->amount ?? 0 }}">
                                        {{ $selectedBookingDeposit->reference_number ?? 'BD#' . $selectedBookingDeposit->id }} - 
                                        {{ $selectedBookingDeposit->unit->property->name ?? 'N/A' }} / 
                                        {{ $selectedBookingDeposit->unit->code ?? 'N/A' }} - 
                                        {{ number_format($selectedBookingDeposit->amount ?? 0, 0, ',', '.') }}đ
                                        @if($selectedBookingDeposit->lead)
                                            - {{ $selectedBookingDeposit->lead->name }}
                                        @endif
                                    </option>
                                    @endif
                                    @foreach ($bookingDeposits as $bookingDeposit)
                                    @if(!isset($selectedBookingDepositId) || $selectedBookingDepositId != $bookingDeposit->id)
                                    <option value="{{ $bookingDeposit->id }}" 
                                        data-property-id="{{ $bookingDeposit->unit->property_id ?? '' }}"
                                        data-unit-id="{{ $bookingDeposit->unit_id ?? '' }}"
                                        data-lead-id="{{ $bookingDeposit->lead_id ?? '' }}"
                                        data-agent-id="{{ $bookingDeposit->agent_id ?? '' }}"
                                        data-amount="{{ $bookingDeposit->amount ?? 0 }}">
                                        {{ $bookingDeposit->reference_number ?? 'BD#' . $bookingDeposit->id }} - 
                                        {{ $bookingDeposit->unit->property->name ?? 'N/A' }} / 
                                        {{ $bookingDeposit->unit->code ?? 'N/A' }} - 
                                        {{ number_format($bookingDeposit->amount ?? 0, 0, ',', '.') }}đ
                                        @if($bookingDeposit->lead)
                                            - {{ $bookingDeposit->lead->name }}
                                        @endif
                                    </option>
                                    @endif
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-info-circle text-info"></i>
                                    Chọn booking deposit đã thanh toán để tự động điền thông tin bất động sản, phòng, lead và nhân viên.
                                </div>
                            </div>
                            @endif
                            
                            <div class="mb-3">
                                <label class="form-label">Bất động sản <span class="text-danger">*</span></label>
                                <select name="property_id" id="propertySelect" class="form-select" required>
                                    <option value="">Chọn bất động sản</option>
                                    @foreach ($properties as $property)
                                    <option value="{{ $property->id }}" {{ $property->id == old('property_id', $lease->unit->property_id) ? 'selected' : '' }}>
                                        {{ $property->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phòng <span class="text-danger">*</span></label>
                                <select name="unit_id" id="unitSelect" class="form-select" required>
                                    <option value="">Chọn phòng</option>
                                    @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}" {{ $unit->id == old('unit_id', $lease->unit_id) ? 'selected' : '' }}>
                                        {{ $unit->code ?? 'Phòng ' . $unit->id }}
                                        @if ($unit->floor) (Tầng {{ $unit->floor }}) @endif
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Lead <span class="text-danger">*</span>
                                </label>
                                <select name="lead_id" id="lead_id" class="form-select" required>
                                    <option value="">Chọn lead</option>
                                    @if($leads && $leads->count() > 0)
                                        @foreach ($leads as $leadOption)
                                        <option value="{{ $leadOption->id }}" 
                                                data-user-id="{{ $leadOption->user_id ?? '' }}"
                                                {{ isset($selectedLeadId) && $selectedLeadId == $leadOption->id ? 'selected' : '' }}>
                                            {{ $leadOption->name }} ({{ $leadOption->phone }})
                                            @if($leadOption->user_id)
                                                - Đã có tài khoản
                                            @endif
                                        </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>Không có lead nào</option>
                                    @endif
                                </select>
                                <div class="form-text">
                                    <i class="fas fa-info-circle text-info"></i>
                                    Hệ thống sẽ tự động tạo hoặc sử dụng tài khoản khách thuê từ thông tin lead.
                                </div>
                                
                                <!-- Lead Information Display -->
                                <div id="lead-info" class="mt-3" style="display: none;">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white py-2">
                                            <h6 class="mb-0">
                                                <i class="fas fa-user me-2"></i>Thông tin Lead
                                            </h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <div id="lead-info-content">
                                                <!-- Lead info will be loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden tenant_id field - will be auto-filled from lead -->
                            <input type="hidden" name="tenant_id" id="tenant_id" value="{{ old('tenant_id', $lease->tenant_id) }}">

                            <div class="mb-3">
                                <label class="form-label">Nhân viên phụ trách</label>
                                <select name="agent_id" id="agentSelect" class="form-select">
                                    <option value="">Chọn nhân viên</option>
                                    @if($managers && $managers->count() > 0)
                                        <optgroup label="Managers">
                                            @foreach ($managers as $manager)
                                            <option value="{{ $manager->id }}" 
                                                {{ (old('agent_id', $lease->agent_id ?? $currentUserId ?? null) == $manager->id) ? 'selected' : '' }}>
                                                {{ $manager->userProfile->full_name ?? $manager->full_name ?? 'N/A' }}
                                            </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if($agents && $agents->count() > 0)
                                        <optgroup label="Agents">
                                            @foreach ($agents as $agent)
                                            <option value="{{ $agent->id }}" 
                                                {{ (old('agent_id', $lease->agent_id ?? $currentUserId ?? null) == $agent->id) ? 'selected' : '' }}>
                                                {{ $agent->userProfile->full_name ?? $agent->full_name ?? 'N/A' }}
                                            </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle text-info"></i>
                                    Tự động điền theo agent phụ trách bất động sản (ID cao nhất) hoặc người đang chỉnh sửa nếu chưa có agent
                                </small>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Thông tin hợp đồng</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" class="form-control" 
                                           value="{{ old('start_date', $lease->start_date ? $lease->start_date->format('Y-m-d') : '') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" class="form-control" 
                                           value="{{ old('end_date', $lease->end_date ? $lease->end_date->format('Y-m-d') : '') }}" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tiền thuê/tháng <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="rent_amount" id="rentAmount" class="form-control money-input" 
                                           value="{{ old('rent_amount', $lease->rent_amount ? number_format($lease->rent_amount, 0, ',', '.') : '') }}" 
                                           placeholder="VD: 5.000.000" min="0" required>
                                    <span class="input-group-text">đ/tháng</span>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle text-info"></i>
                                    Sẽ tự động điền theo phòng khi chọn phòng (có thể chỉnh sửa)
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tiền cọc</label>
                                <div class="input-group">
                                    <input type="text" name="deposit_amount" id="depositAmount" class="form-control money-input" 
                                           value="{{ old('deposit_amount', $lease->deposit_amount ? number_format($lease->deposit_amount, 0, ',', '.') : '') }}" 
                                           placeholder="VD: 10.000.000" min="0">
                                    <span class="input-group-text">đ</span>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle text-info"></i>
                                    Sẽ tự động điền theo phòng khi chọn phòng (có thể chỉnh sửa)
                                </small>
                            </div>

                            {{-- Trạng thái hợp đồng sẽ được quản lý ở trang show, không cho phép thay đổi ở form create/edit --}}
                            <input type="hidden" name="status" value="{{ $lease->status }}">

                            <div class="mb-3">
                                <label class="form-label">Ngày ký hợp đồng</label>
                                <input type="date" name="signed_at" class="form-control" 
                                       value="{{ old('signed_at', $lease->signed_at ? $lease->signed_at->format('Y-m-d') : '') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Services Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3">Dịch vụ kèm theo</h5>
                            
                            @if($defaultLeaseServiceSet)
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Nhóm dịch vụ mặc định:</strong> {{ $defaultLeaseServiceSet->name }}
                                    @if($defaultLeaseServiceSet->items)
                                        ({{ $defaultLeaseServiceSet->items->count() }} dịch vụ)
                                    @endif
                                </div>
                            @endif
                            
                            {{-- Dropdown chọn nhóm dịch vụ --}}
                            <div class="mb-3">
                                <label class="form-label">Chọn nhóm dịch vụ</label>
                                <select name="lease_services_id" id="lease_services_id" class="form-select">
                                    <option value="">-- Chọn nhóm dịch vụ --</option>
                                    @if($leaseServiceSets && $leaseServiceSets->count() > 0)
                                        @foreach($leaseServiceSets as $set)
                                            <option value="{{ $set->id }}" {{ old('lease_services_id', $lease->lease_services_id) == $set->id ? 'selected' : '' }}>
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
                                <small class="form-text text-muted d-block mt-1">
                                    <i class="fas fa-info-circle text-info"></i>
                                    Tự động điền theo bất động sản đã chọn. Nếu bất động sản không có nhóm dịch vụ riêng, sẽ dùng nhóm dịch vụ mặc định của tổ chức. Bạn có thể chuyển đổi sang nhóm dịch vụ khác nếu cần.
                                </small>
                            </div>
                            
                            {{-- Hiển thị dịch vụ từ gói đã chọn (read-only) --}}
                            <div id="servicesPreview" class="mb-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Vui lòng chọn nhóm dịch vụ để xem danh sách dịch vụ.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Cycle Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3">Chu kỳ thanh toán</h5>
                            
                            @if($defaultPaymentCycle)
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Chu kỳ thanh toán mặc định:</strong> {{ $defaultPaymentCycle->cycle_type_name ?? $defaultPaymentCycle->name }}
                                </div>
                            @endif
                            
                            <div class="mb-3">
                                <label class="form-label">Chọn chu kỳ thanh toán (tùy chọn)</label>
                                <select name="payment_cycle_id" id="payment_cycle_id" class="form-select">
                                    <option value="">-- Sử dụng chu kỳ mặc định --</option>
                                    @if($paymentCycles && $paymentCycles->count() > 0)
                                        @foreach($paymentCycles as $cycle)
                                            <option value="{{ $cycle->id }}" {{ old('payment_cycle_id', $lease->payment_cycle_id) == $cycle->id ? 'selected' : '' }}>
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
                                    Tự động điền theo bất động sản đã chọn. Nếu bất động sản không có chu kỳ riêng, sẽ dùng chu kỳ mặc định của tổ chức. Bạn có thể chuyển đổi sang chu kỳ khác nếu cần.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="row mt-4">
                        <div class="col-12">
                            @include('staff.components.action-buttons', [
                                'layout' => 'horizontal',
                                'size' => 'md',
                                'actions' => [
                                    [
                                        'type' => 'submit',
                                        'variant' => 'primary',
                                        'label' => 'Cập nhật hợp đồng',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.leases.show', $lease->id)
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const propertySelect = document.getElementById('propertySelect');
    const unitSelect = document.getElementById('unitSelect');
    const servicesPreview = document.getElementById('servicesPreview');
    const tenantIdInput = document.getElementById('tenant_id');
    const agentSelect = document.getElementById('agentSelect');
    const currentUserId = {{ $currentUserId ?? Auth::id() ?? 'null' }};
    const paymentCycleSelect = document.getElementById('payment_cycle_id');
    const leaseServiceSetSelect = document.getElementById('lease_services_id');
    const leadSelect = document.getElementById('lead_id');
    const bookingDepositSelect = document.getElementById('booking_deposit_id');
    
    // Variable to store pending unit ID from booking deposit
    let pendingUnitIdFromBookingDeposit = null;
    
    // Track if user has manually selected payment cycle
    let paymentCycleManuallySelected = false;
    
    // Track if user has manually selected lease service set
    let leaseServiceSetManuallySelected = false;
    
    // Store units data for auto-fill
    let unitsData = {};
    
    // Track payment cycle selection changes (only on user interaction, not programmatic)
    if (paymentCycleSelect) {
        let userInteractingPaymentCycle = false;
        
        paymentCycleSelect.addEventListener('mousedown', function() {
            userInteractingPaymentCycle = true;
        });
        
        paymentCycleSelect.addEventListener('keydown', function() {
            userInteractingPaymentCycle = true;
        });
        
        paymentCycleSelect.addEventListener('change', function() {
            // Only mark as manually selected if user is interacting
            if (userInteractingPaymentCycle && this.value && this.value !== '') {
                paymentCycleManuallySelected = true;
                console.log('User manually selected payment cycle:', this.value);
            }
            userInteractingPaymentCycle = false; // Reset flag
        });
    }
    
    // Track lease service set selection changes (only on user interaction, not programmatic)
    if (leaseServiceSetSelect) {
        let userInteracting = false;
        
        leaseServiceSetSelect.addEventListener('mousedown', function() {
            userInteracting = true;
        });
        
        leaseServiceSetSelect.addEventListener('keydown', function() {
            userInteracting = true;
        });
        
        leaseServiceSetSelect.addEventListener('change', function() {
            // Only mark as manually selected if user is interacting
            if (userInteracting && this.value && this.value !== '') {
                leaseServiceSetManuallySelected = true;
                console.log('User manually selected lease service set:', this.value);
            }
            userInteracting = false; // Reset flag
        });
    }
    
    // Booking deposit selection handler
    if (bookingDepositSelect) {
        bookingDepositSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const bookingDepositId = this.value;
            
            if (selectedOption.value) {
                const propertyId = selectedOption.getAttribute('data-property-id');
                const unitId = selectedOption.getAttribute('data-unit-id');
                const leadId = selectedOption.getAttribute('data-lead-id');
                const agentId = selectedOption.getAttribute('data-agent-id');
                
                // Auto-fill property and wait for units to load
                if (propertyId && propertySelect) {
                    if (unitId && unitId.trim() !== '' && unitSelect) {
                        pendingUnitIdFromBookingDeposit = String(unitId);
                    }
                    propertySelect.value = propertyId;
                    propertySelect.dispatchEvent(new Event('change'));
                }
                
                // Auto-fill lead
                if (leadId && leadSelect) {
                    leadSelect.value = leadId;
                    leadSelect.dispatchEvent(new Event('change'));
                }
                
                // Auto-fill agent
                if (agentId && agentSelect) {
                    agentSelect.value = agentId;
                }
                
                if (window.Notify) {
                    Notify.success('Đã tự động điền thông tin từ booking deposit. Vui lòng kiểm tra lại các trường.', 'Thông báo');
                }
            }
        });
    }
    
    // Lead selection handler
    if (leadSelect) {
        leadSelect.addEventListener('change', function() {
            const leadId = this.value;
            const leadInfoContainer = document.getElementById('lead-info');
            const leadInfoContent = document.getElementById('lead-info-content');
            
            if (leadId) {
                leadInfoContent.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải thông tin lead...</div>';
                leadInfoContainer.style.display = 'block';
                
                fetch(`/staff/leads/${leadId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.lead) {
                        const lead = data.lead;
                        const tenantId = lead.tenant_id || lead.user_id;
                        const hasUserId = tenantId !== null && tenantId !== undefined && tenantId !== '';
                        
                        // Auto-fill tenant_id if lead has user
                        if (hasUserId && tenantIdInput) {
                            tenantIdInput.value = tenantId;
                        }
                        
                        let html = `
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2"><strong>Tên:</strong> ${lead.name}</div>
                                    <div class="mb-2"><strong>Số điện thoại:</strong> ${lead.phone}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2"><strong>Email:</strong> ${lead.email || 'N/A'}</div>
                                    <div class="mb-2"><strong>Ngân sách:</strong> 
                        `;
                        
                        if (lead.budget_min && lead.budget_max) {
                            html += `${parseInt(lead.budget_min).toLocaleString('vi-VN')}đ - ${parseInt(lead.budget_max).toLocaleString('vi-VN')}đ`;
                        } else if (lead.budget_min) {
                            html += `Từ ${parseInt(lead.budget_min).toLocaleString('vi-VN')}đ`;
                        } else if (lead.budget_max) {
                            html += `Đến ${parseInt(lead.budget_max).toLocaleString('vi-VN')}đ`;
                        } else {
                            html += 'Chưa xác định';
                        }
                        
                        html += `</div></div></div>`;
                        
                        if (hasUserId) {
                            html += `<div class="alert alert-info mb-0 mt-2"><i class="fas fa-info-circle me-2"></i><strong>Đã là khách thuê:</strong> Lead này đã có tài khoản khách thuê.</div>`;
                        } else {
                            html += `<div class="alert alert-success mb-0 mt-2"><i class="fas fa-check-circle me-2"></i><strong>Tự động:</strong> Hệ thống sẽ tạo hoặc sử dụng tài khoản khách thuê từ thông tin lead này.</div>`;
                        }
                        
                        leadInfoContent.innerHTML = html;
                    } else {
                        leadInfoContent.innerHTML = '<div class="text-center text-danger">Không tìm thấy thông tin lead</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading lead:', error);
                    leadInfoContent.innerHTML = '<div class="text-center text-danger">Lỗi tải thông tin lead</div>';
                });
            } else {
                leadInfoContainer.style.display = 'none';
            }
        });
        
        // Trigger change if pre-selected
        if (leadSelect.value) {
            leadSelect.dispatchEvent(new Event('change'));
        }
    }

    // Property change handler
    propertySelect.addEventListener('change', function() {
        const propertyId = this.value;
        
        if (!propertyId) {
            unitSelect.innerHTML = '<option value="">Chọn bất động sản trước</option>';
            unitSelect.disabled = true;
            return;
        }

        // Get booking_deposit_id from dropdown if exists
        const bookingDepositId = bookingDepositSelect ? bookingDepositSelect.value : null;
        let unitsUrl = `/staff/api/properties/${propertyId}/units`;
        if (bookingDepositId) {
            unitsUrl += `?booking_deposit_id=${bookingDepositId}`;
        }

        // Fetch units for selected property
        fetch(unitsUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            unitSelect.innerHTML = '<option value="">Chọn phòng</option>';
            if (data.error) {
                throw new Error(data.error);
            }
            
            unitsData = {};
            const currentUnitId = {{ $lease->unit_id }};
            
            data.forEach(unit => {
                unitsData[unit.id] = unit;
                
                const option = document.createElement('option');
                option.value = unit.id;
                option.textContent = unit.code || `Phòng ${unit.id}`;
                if (unit.floor) {
                    option.textContent += ` (Tầng ${unit.floor})`;
                }
                
                // Kiểm tra nếu phòng đã có hợp đồng hoạt động (trừ phòng hiện tại của hợp đồng này)
                if (unit.has_active_lease && unit.id != currentUnitId) {
                    option.textContent += ' - Đã có hợp đồng hoạt động';
                    option.disabled = true;
                    option.style.color = '#dc3545';
                }
                
                // Nếu phòng thuộc booking deposit đang chọn, highlight
                if (unit.belongs_to_selected_booking) {
                    option.textContent += ' - (Thuộc booking deposit đã chọn)';
                    option.style.color = '#28a745';
                    option.style.fontWeight = 'bold';
                }
                
                unitSelect.appendChild(option);
            });
            
            // Auto-select unit if pending from booking deposit
            if (pendingUnitIdFromBookingDeposit) {
                const pendingUnitIdStr = String(pendingUnitIdFromBookingDeposit).trim();
                const pendingUnitIdNum = parseInt(pendingUnitIdFromBookingDeposit);
                
                for (let i = 0; i < unitSelect.options.length; i++) {
                    const option = unitSelect.options[i];
                    if (!option.value || option.value === '') continue;
                    
                    const optionValueStr = String(option.value).trim();
                    const optionValueNum = parseInt(option.value);
                    
                    if ((optionValueStr === pendingUnitIdStr || optionValueNum === pendingUnitIdNum) && !option.disabled) {
                        unitSelect.value = option.value;
                        unitSelect.dispatchEvent(new Event('change'));
                        pendingUnitIdFromBookingDeposit = null;
                        break;
                    }
                }
            }
            
            unitSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error fetching units:', error);
            unitSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
            Notify.error('Không thể tải danh sách phòng. Vui lòng thử lại.', 'Lỗi tải dữ liệu');
        });
        
        // Auto-fill agent, payment cycle, and lease service set based on property
        loadPropertyAgent(propertyId);
        loadPropertyPaymentCycle(propertyId);
        loadPropertyLeaseServiceSet(propertyId);
    });
    
    // Unit change handler - auto-fill rent and deposit
    unitSelect.addEventListener('change', function() {
        const unitId = this.value;
        const rentAmountInput = document.getElementById('rentAmount');
        const depositAmountInput = document.getElementById('depositAmount');
        
        if (unitId && unitsData[unitId]) {
            const unit = unitsData[unitId];
            
            // Auto-fill rent amount if exists and not already filled
            if (unit.base_rent && unit.base_rent > 0 && rentAmountInput) {
                if (!rentAmountInput.value || rentAmountInput.value.trim() === '') {
                    const rentValue = Math.round(parseFloat(unit.base_rent));
                    rentAmountInput.value = rentValue.toString();
                    rentAmountInput.dispatchEvent(new Event('input'));
                }
            }
            
            // Auto-fill deposit amount if exists and not already filled
            if (unit.deposit_amount && unit.deposit_amount > 0 && depositAmountInput) {
                if (!depositAmountInput.value || depositAmountInput.value.trim() === '') {
                    const depositValue = Math.round(parseFloat(unit.deposit_amount));
                    depositAmountInput.value = depositValue.toString();
                    depositAmountInput.dispatchEvent(new Event('input'));
                }
            }
        }
    });
    
    // Load and auto-fill agent from property assignment
    function loadPropertyAgent(propertyId) {
        if (!propertyId || !agentSelect) return;
        
        fetch(`/staff/api/properties/${propertyId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data && data.property && data.property.assigned_users) {
                const assignedAgents = data.property.assigned_users.filter(user => 
                    user.pivot && user.pivot.role_key === 'agent'
                );
                
                if (assignedAgents.length > 0) {
                    const latestAgent = assignedAgents.reduce((prev, current) => 
                        (current.id > prev.id) ? current : prev
                    );
                    
                    if (latestAgent.id != currentUserId && !agentSelect.value) {
                        agentSelect.value = latestAgent.id;
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error loading property agent:', error);
        });
    }
    
    // Load and auto-fill payment cycle from property
    function loadPropertyPaymentCycle(propertyId) {
        if (!propertyId || !paymentCycleSelect || paymentCycleManuallySelected) return;
        
        fetch(`/staff/api/properties/${propertyId}/payment-cycle`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data && data.success && data.property) {
                let paymentCycleToUse = null;
                
                if (data.property.payment_cycle && data.property.payment_cycle.id) {
                    paymentCycleToUse = data.property.payment_cycle;
                } else if (data.property.effective_payment_cycle && data.property.effective_payment_cycle.id) {
                    paymentCycleToUse = data.property.effective_payment_cycle;
                }
                
                if (paymentCycleToUse && paymentCycleToUse.id) {
                    const cycleOption = Array.from(paymentCycleSelect.options).find(
                        opt => opt.value == paymentCycleToUse.id
                    );
                    if (cycleOption && !paymentCycleSelect.value) {
                        paymentCycleSelect.value = paymentCycleToUse.id;
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error loading property payment cycle:', error);
        });
    }
    
    // Load and auto-fill lease service set from property
    function loadPropertyLeaseServiceSet(propertyId) {
        if (!propertyId || !leaseServiceSetSelect || leaseServiceSetManuallySelected) return;
        
        fetch(`/staff/api/properties/${propertyId}/lease-service-set`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data && data.success && data.property) {
                let leaseServiceSetToUse = null;
                
                if (data.property.lease_service_set && data.property.lease_service_set.id) {
                    leaseServiceSetToUse = data.property.lease_service_set;
                } else if (data.property.effective_lease_service_set && data.property.effective_lease_service_set.id) {
                    leaseServiceSetToUse = data.property.effective_lease_service_set;
                }
                
                if (leaseServiceSetToUse && leaseServiceSetToUse.id) {
                    const setOption = Array.from(leaseServiceSetSelect.options).find(
                        opt => opt.value == leaseServiceSetToUse.id
                    );
                    if (setOption && !leaseServiceSetSelect.value) {
                        leaseServiceSetSelect.value = leaseServiceSetToUse.id;
                        leaseServiceSetSelect.dispatchEvent(new Event('change'));
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error loading property lease service set:', error);
        });
    }
    
    // Handle lease service set dropdown change (leaseServiceSetSelect already declared at top)
    if (leaseServiceSetSelect) {
        // Load preview khi trang được load lần đầu nếu đã có giá trị
        if (leaseServiceSetSelect.value) {
            loadLeaseServiceSet(leaseServiceSetSelect.value);
        }
        
        leaseServiceSetSelect.addEventListener('change', function() {
            const setId = this.value;
            if (setId) {
                // Load lease service set and show preview
                loadLeaseServiceSet(setId);
            } else {
                // Clear preview if no set selected
                servicesPreview.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Vui lòng chọn nhóm dịch vụ.</div>';
            }
        });
    }
    
    // Load lease service set by ID and show preview
    function loadLeaseServiceSet(setId) {
        if (!setId) {
            return;
        }
        
        fetch(`{{ route('staff.lease-service-settings.sets.show', ['id' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', setId), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 404) {
                    console.log('loadLeaseServiceSet: 404 - set not found');
                    servicesPreview.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Không tìm thấy nhóm dịch vụ.</div>';
                    return null;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success && data.leaseServiceSet && data.leaseServiceSet.items) {
                const set = data.leaseServiceSet;
                
                if (set.items && set.items.length > 0) {
                    // Build preview table
                    let tableHtml = '<label class="form-label">Dịch vụ trong gói đã chọn:</label>';
                    tableHtml += '<div class="table-responsive"><table class="table table-sm table-bordered">';
                    tableHtml += '<thead><tr><th>STT</th><th>Tên dịch vụ</th><th class="text-end">Giá</th></tr></thead>';
                    tableHtml += '<tbody>';
                    
                    set.items.forEach((item, index) => {
                        if (item.service) {
                            const price = item.price ? parseFloat(item.price).toLocaleString('vi-VN') : '0';
                            tableHtml += `<tr>
                                <td>${index + 1}</td>
                                <td>${item.service.name || 'N/A'}</td>
                                <td class="text-end">${price} đ</td>
                            </tr>`;
                        }
                    });
                    
                    tableHtml += '</tbody></table></div>';
                    servicesPreview.innerHTML = tableHtml;
                    
                    console.log('✅ Services preview loaded successfully from lease service set:', set.name);
                } else {
                    servicesPreview.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Chưa có dịch vụ nào trong gói đã chọn. Vui lòng chọn gói dịch vụ khác hoặc tạo gói dịch vụ mới.</div>';
                }
            } else {
                servicesPreview.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Không có dữ liệu dịch vụ.</div>';
            }
        })
        .catch(error => {
            console.error('Error loading lease service set:', error);
            servicesPreview.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Lỗi khi tải thông tin dịch vụ. Vui lòng thử lại.</div>';
        });
    }


    // Payment cycle change handler
    document.getElementById('lease_payment_cycle').addEventListener('change', function() {
        const customMonthsField = document.getElementById('lease_custom_months_field');
        if (this.value === 'custom') {
            customMonthsField.style.display = 'block';
        } else {
            customMonthsField.style.display = 'none';
        }
    });

    // Form submission
    document.getElementById('leaseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Kiểm tra phòng đã có hợp đồng hoạt động (trừ phòng hiện tại)
        const selectedUnit = unitSelect.options[unitSelect.selectedIndex];
        const currentUnitId = {{ $lease->unit_id }};
        if (selectedUnit && selectedUnit.disabled && selectedUnit.value != currentUnitId) {
            Notify.error('Phòng này đã có hợp đồng hoạt động. Vui lòng chọn phòng khác hoặc chấm dứt hợp đồng hiện tại trước.', 'Không thể cập nhật hợp đồng');
            return;
        }
        
        if (window.Preloader) {
            window.Preloader.show();
        }

        // Unformat money inputs before submitting (remove dots and non-digit characters)
        const rentInput = document.getElementById('rentAmount');
        const depositInput = document.getElementById('depositAmount');
        
        if (rentInput && rentInput.value) {
            rentInput.value = rentInput.value.replace(/[^\d]/g, '');
            console.log('Unformatted rent:', rentInput.value);
        }
        
        if (depositInput && depositInput.value) {
            depositInput.value = depositInput.value.replace(/[^\d]/g, '');
            console.log('Unformatted deposit:', depositInput.value);
        }
        

        const formData = new FormData(this);
        
        // Loại bỏ các trường nguy hiểm khỏi FormData (security check)
        const dangerousFields = ['organization_id', 'user_organization_id', 'org_id', 'user_org_id'];
        dangerousFields.forEach(field => {
            if (formData.has(field)) {
                formData.delete(field);
                console.warn('Removed dangerous field from form data:', field);
            }
        });
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server không trả về JSON. Có thể có lỗi xảy ra.');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Thành công!');
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    setTimeout(() => {
                        window.location.href = '/staff/leases/{{ $lease->id }}';
                    }, 1000);
                }
            } else {
                // Handle validation errors
                if (data.errors) {
                    let errorMessage = data.message || 'Dữ liệu không hợp lệ:';
                    errorMessage += '<ul style="text-align:left;margin:10px 0;">';
                    for (const field in data.errors) {
                        data.errors[field].forEach(error => {
                            errorMessage += `<li>${error}</li>`;
                        });
                    }
                    errorMessage += '</ul>';
                    Notify.error(errorMessage, 'Lỗi xác thực');
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể cập nhật hợp đồng: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
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
@endsection
