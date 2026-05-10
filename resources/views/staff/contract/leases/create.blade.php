@extends('layouts.staff_dashboard')

@section('title', 'Thêm Hợp đồng Mới')

@section('content')
<main class="main-content">
    <header class="header">
        <div class="header-content">
            <div class="header-info">
                <h1>Thêm Hợp đồng Mới</h1>
                <p>Tạo hợp đồng thuê mới cho khách hàng</p>
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
                <form id="leaseForm" method="POST" action="{{ route('staff.leases.store') }}">
                    @csrf
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Thông tin cơ bản</h5>
                            
                            <!-- Số hợp đồng - đưa lên đầu, readonly, tự động sinh -->
                            <div class="mb-3">
                                <label class="form-label">Số hợp đồng <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="contract_no" id="contractNo" class="form-control" placeholder="Đang tạo mã hợp đồng..." readonly style="background-color: #f8f9fa;">
                                    <button type="button" id="generateContractNo" class="btn btn-outline-primary" title="Tạo mã hợp đồng mới">
                                        <i class="fas fa-sync-alt"></i> Tự sinh
                                    </button>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle text-info"></i>
                                    Mã hợp đồng sẽ được tự động sinh theo format HD + số tăng dần (VD: HD000001)
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
                                    Chọn booking deposit đã thanh toán để tự động điền thông tin bất động sản, phòng, lead và nhân viên. Hệ thống sẽ tự động tạo hóa đơn đầu tiên với item âm trừ tiền cọc đã thanh toán.
                                </div>
                            </div>
                            @endif
                            
                            <div class="mb-3">
                                <label class="form-label">Bất động sản <span class="text-danger">*</span></label>
                                <select name="property_id" id="propertySelect" class="form-select" required>
                                    <option value="">Chọn bất động sản</option>
                                    @foreach ($properties as $property)
                                    <option value="{{ $property->id }}" {{ (old('property_id', $propertyId ?? null) == $property->id) ? 'selected' : '' }}>{{ $property->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phòng <span class="text-danger">*</span></label>
                                <select name="unit_id" id="unitSelect" class="form-select" required>
                                    <option value="">Chọn bất động sản trước</option>
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
                                    Hệ thống sẽ tự động tạo tài khoản khách thuê từ thông tin lead.
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
                            <input type="hidden" name="tenant_id" id="tenant_id" value="">

                            <div class="mb-3">
                                <label class="form-label">Nhân viên phụ trách</label>
                                <select name="agent_id" id="agentSelect" class="form-select">
                                    <option value="">Chọn nhân viên</option>
                                    @if($managers && $managers->count() > 0)
                                        <optgroup label="Managers">
                                            @foreach ($managers as $manager)
                                            <option value="{{ $manager->id }}" 
                                                {{ (old('agent_id', $defaultAgentId ?? $currentUserId ?? null) == $manager->id) ? 'selected' : '' }}>
                                                {{ $manager->userProfile->full_name ?? $manager->full_name ?? 'N/A' }}
                                            </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if($agents && $agents->count() > 0)
                                        <optgroup label="Agents">
                                            @foreach ($agents as $agent)
                                            <option value="{{ $agent->id }}" 
                                                {{ (old('agent_id', $defaultAgentId ?? $currentUserId ?? null) == $agent->id) ? 'selected' : '' }}>
                                                {{ $agent->userProfile->full_name ?? $agent->full_name ?? 'N/A' }}
                                            </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle text-info"></i>
                                    Tự động điền theo agent phụ trách bất động sản (ID cao nhất) hoặc người đang tạo nếu chưa có agent
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
                                           value="{{ old('start_date', now()->format('Y-m-d')) }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ngày kết thúc <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" class="form-control" 
                                           value="{{ old('end_date', now()->addYear()->format('Y-m-d')) }}" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tiền thuê/tháng <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="rent_amount" id="rentAmount" class="form-control money-input" placeholder="VD: 5.000.000" min="0" required>
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
                                    <input type="text" name="deposit_amount" id="depositAmount" class="form-control money-input" placeholder="VD: 10.000.000" min="0">
                                    <span class="input-group-text">đ</span>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle text-info"></i>
                                    Sẽ tự động điền theo phòng khi chọn phòng (có thể chỉnh sửa)
                                </small>
                            </div>

                            {{-- Trạng thái hợp đồng sẽ được quản lý ở trang show, không cho phép thay đổi ở form create/edit --}}
                            <input type="hidden" name="status" value="draft">

                            <div class="mb-3">
                                <label class="form-label">Ngày ký hợp đồng</label>
                                <input type="date" name="signed_at" class="form-control" 
                                       value="{{ old('signed_at', now()->format('Y-m-d')) }}">
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
                                            <option value="{{ $set->id }}">
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
                                            <option value="{{ $cycle->id }}" {{ old('payment_cycle_id') == $cycle->id ? 'selected' : '' }}>
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
                                        'label' => 'Tạo hợp đồng',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.leases.index')
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
    const contractNoInput = document.getElementById('contractNo');
    const generateContractNoBtn = document.getElementById('generateContractNo');
    const tenantSelect = document.getElementById('tenant_id');
    const tenantRequired = document.getElementById('tenant-required');
    const tenantHelpText = document.getElementById('tenant-help-text');
    const agentSelect = document.getElementById('agentSelect');
    const currentUserId = {{ Auth::id() ?? 'null' }};
    const paymentCycleSelect = document.getElementById('payment_cycle_id');
    const leaseServiceSetSelect = document.getElementById('lease_services_id');
    
    // Variable to store pending unit ID from booking deposit
    let pendingUnitIdFromBookingDeposit = null;
    
    // Track if user has manually selected payment cycle
    let paymentCycleManuallySelected = false;
    
    // Track if user has manually selected lease service set
    let leaseServiceSetManuallySelected = false;
    
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
        // Use 'input' event for more accurate user interaction detection
        // Or track click/keyboard events
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
    
    // Auto-fill người tạo vào nhân viên phụ trách khi load trang
    if (agentSelect && currentUserId && !agentSelect.value) {
        agentSelect.value = currentUserId;
        console.log('Auto-filled agent with current user:', currentUserId);
    }
    
    // Store units data for auto-fill
    let unitsData = {};
    
    // Auto-select property and unit from query parameters
    @if(isset($propertyId) && $propertyId)
    const propertyIdFromUrl = {{ $propertyId }};
    @if(isset($unitId) && $unitId)
    const unitIdFromUrl = {{ $unitId }};
    @else
    const unitIdFromUrl = null;
    @endif
    @else
    const propertyIdFromUrl = null;
    const unitIdFromUrl = null;
    @endif

    // Booking deposit selection handler
    const bookingDepositSelect = document.getElementById('booking_deposit_id');
    if (bookingDepositSelect) {
        bookingDepositSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const bookingDepositId = this.value;
            
            if (selectedOption.value) {
                const propertyId = selectedOption.getAttribute('data-property-id');
                const unitId = selectedOption.getAttribute('data-unit-id');
                const leadId = selectedOption.getAttribute('data-lead-id');
                const agentId = selectedOption.getAttribute('data-agent-id');
                const amount = selectedOption.getAttribute('data-amount');
                
                // Auto-fill property and wait for units to load
                if (propertyId && propertySelect) {
                    // Store unitId to select after units are loaded (convert to string for comparison)
                    // Only set pending if unitId is not empty
                    if (unitId && unitId.trim() !== '' && unitSelect) {
                        pendingUnitIdFromBookingDeposit = String(unitId);
                        console.log('Booking deposit selected - pending unit ID:', pendingUnitIdFromBookingDeposit, 'property ID:', propertyId, 'booking deposit ID:', bookingDepositId);
                    } else {
                        console.log('Booking deposit selected - no unit ID available');
                    }
                    
                    propertySelect.value = propertyId;
                    // Trigger property change to load units (sẽ tự động gửi booking_deposit_id)
                    propertySelect.dispatchEvent(new Event('change'));
                } else if (unitId && unitId.trim() !== '' && unitSelect) {
                    // If no property change needed, just select unit directly
                    unitSelect.value = unitId;
                    unitSelect.dispatchEvent(new Event('change'));
                }
                
                // Nếu đã chọn property, reload units với booking_deposit_id mới
                if (propertySelect && propertySelect.value) {
                    const currentPropertyId = propertySelect.value;
                    let unitsUrl = `/staff/api/properties/${currentPropertyId}/units`;
                    if (bookingDepositId) {
                        unitsUrl += `?booking_deposit_id=${bookingDepositId}`;
                    }
                    
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
                        
                        // Clear units data
                        unitsData = {};
                        
                        data.forEach(unit => {
                            // Store unit data for auto-fill
                            unitsData[unit.id] = unit;
                            
                            const option = document.createElement('option');
                            option.value = unit.id;
                            option.textContent = unit.code || `Phòng ${unit.id}`;
                            if (unit.floor) {
                                option.textContent += ` (Tầng ${unit.floor})`;
                            }
                            
                            // Kiểm tra nếu phòng đã có hợp đồng hoạt động
                            if (unit.has_active_lease) {
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
                        
                        // Auto-select unit if pending
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
                                    console.log('✅ Auto-filled unit from booking deposit:', option.value);
                                    break;
                                }
                            }
                            pendingUnitIdFromBookingDeposit = null;
                        }
                    })
                    .catch(error => {
                        console.error('Error reloading units:', error);
                    });
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
                
                // Show notification
                if (window.Notify) {
                    Notify.success('Đã tự động điền thông tin từ booking deposit. Vui lòng kiểm tra lại các trường.', 'Thông báo');
                }
            }
        });
    }

    // Lead selection handler (if lead select exists)
    const leadSelect = document.getElementById('lead_id');
    if (leadSelect) {
        leadSelect.addEventListener('change', function() {
            const leadId = this.value;
            const leadInfoContainer = document.getElementById('lead-info');
            const leadInfoContent = document.getElementById('lead-info-content');
            
            if (leadId) {
                // Show loading state
                leadInfoContent.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Đang tải thông tin lead...</div>';
                leadInfoContainer.style.display = 'block';
                
                // Load lead information via AJAX
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
                            let html = `
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <strong>Tên:</strong> ${lead.name}
                                        </div>
                                        <div class="mb-2">
                                            <strong>Số điện thoại:</strong> ${lead.phone}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <strong>Email:</strong> ${lead.email || 'N/A'}
                                        </div>
                                        <div class="mb-2">
                                            <strong>Ngân sách:</strong> 
                            `;
                            
                            if (lead.budget_min && lead.budget_max) {
                                html += `${formatCurrency(lead.budget_min)}đ - ${formatCurrency(lead.budget_max)}đ`;
                            } else if (lead.budget_min) {
                                html += `Từ ${formatCurrency(lead.budget_min)}đ`;
                            } else if (lead.budget_max) {
                                html += `Đến ${formatCurrency(lead.budget_max)}đ`;
                            } else {
                                html += 'Chưa xác định';
                            }
                            
                            html += `
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            // Kiểm tra xem lead đã có tenant_id/user_id chưa (đã là khách thuê)
                            // Check cả tenant_id và user_id (alias) để tương thích
                            const tenantId = lead.tenant_id || lead.user_id;
                            const hasUserId = tenantId !== null && tenantId !== undefined && tenantId !== '';
                            
                            if (hasUserId) {
                                // Lead đã là khách thuê → sử dụng tài khoản hiện có
                                html += `
                                <div class="alert alert-info mb-0 mt-2">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Đã là khách thuê:</strong> Lead này đã có tài khoản khách thuê. Hệ thống sẽ sử dụng tài khoản hiện có, không tạo mới để tránh trùng lặp dữ liệu.
                                </div>
                                `;
                            } else {
                                // Lead chưa có user → tạo mới
                                html += `
                                <div class="alert alert-success mb-0 mt-2">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Tự động:</strong> Hệ thống sẽ tạo tài khoản khách thuê từ thông tin lead này (mật khẩu mặc định: 12345678).
                                </div>
                                `;
                            }
                            
                            console.log('Lead tenant_id/user_id check:', {
                                'lead.tenant_id': lead.tenant_id,
                                'lead.user_id': lead.user_id,
                                'tenantId': tenantId,
                                'hasUserId': hasUserId,
                                'type_tenant_id': typeof lead.tenant_id,
                                'type_user_id': typeof lead.user_id
                            });
                            
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
    
    // Auto-fill lead from selectedBookingDeposit when page loads
    @if(isset($selectedBookingDeposit) && $selectedBookingDeposit)
    @php
        $selectedBookingDepositLeadId = $selectedBookingDeposit->lead_id ?? null;
        $selectedBookingDepositAgentId = $selectedBookingDeposit->agent_id ?? null;
    @endphp
    
    @if($selectedBookingDepositLeadId)
    const selectedBookingDepositLeadId = {{ $selectedBookingDepositLeadId }};
    @else
    const selectedBookingDepositLeadId = null;
    @endif
    
    @if($selectedBookingDepositAgentId)
    const selectedBookingDepositAgentId = {{ $selectedBookingDepositAgentId }};
    @else
    const selectedBookingDepositAgentId = null;
    @endif
    
    // Auto-fill lead if booking deposit is pre-selected
    if (bookingDepositSelect && bookingDepositSelect.value && leadSelect) {
        // Get lead_id from selected booking deposit option
        const selectedOption = bookingDepositSelect.options[bookingDepositSelect.selectedIndex];
        if (selectedOption) {
            const leadIdFromBookingDeposit = selectedOption.getAttribute('data-lead-id');
            if (leadIdFromBookingDeposit && leadIdFromBookingDeposit.trim() !== '' && leadIdFromBookingDeposit !== 'null') {
                // Check if lead exists in dropdown
                const leadOption = Array.from(leadSelect.options).find(opt => opt.value == leadIdFromBookingDeposit);
                if (leadOption) {
                    leadSelect.value = leadIdFromBookingDeposit;
                    leadSelect.dispatchEvent(new Event('change'));
                    console.log('Auto-filled lead from selectedBookingDeposit option:', leadIdFromBookingDeposit);
                } else {
                    console.warn('Lead ID from booking deposit not found in dropdown:', leadIdFromBookingDeposit);
                }
            }
        }
    }
    
    // Also try to auto-fill directly from selectedBookingDepositLeadId
    if (selectedBookingDepositLeadId && leadSelect) {
        // Check if lead exists in dropdown
        const leadOption = Array.from(leadSelect.options).find(opt => opt.value == selectedBookingDepositLeadId);
        if (leadOption) {
            leadSelect.value = selectedBookingDepositLeadId;
            leadSelect.dispatchEvent(new Event('change'));
            console.log('Auto-filled lead directly from selectedBookingDepositLeadId:', selectedBookingDepositLeadId);
        } else {
            console.warn('selectedBookingDepositLeadId not found in dropdown:', selectedBookingDepositLeadId);
        }
    }
    
    // Auto-fill agent if available
    if (selectedBookingDepositAgentId && agentSelect) {
        const agentOption = Array.from(agentSelect.options).find(opt => opt.value == selectedBookingDepositAgentId);
        if (agentOption) {
            agentSelect.value = selectedBookingDepositAgentId;
            console.log('Auto-filled agent from selectedBookingDeposit:', selectedBookingDepositAgentId);
        } else {
            console.warn('selectedBookingDepositAgentId not found in dropdown:', selectedBookingDepositAgentId);
        }
    }
    @endif

    // Helper function to format currency
    function formatCurrency(value) {
        if (!value) return '0';
        return parseInt(value).toLocaleString('vi-VN');
    }

    // Tự động sinh mã hợp đồng khi trang load
    generateContractNumber();

    // Generate contract number function
    function generateContractNumber() {
        fetch('/staff/api/leases/next-contract-number', {
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
            if (data.error || !data.success) {
                throw new Error(data.error || 'Không thể sinh mã hợp đồng');
            }
            if (data.contract_no) {
                contractNoInput.value = data.contract_no;
            } else {
                throw new Error('Không nhận được mã hợp đồng từ server');
            }
        })
        .catch(error => {
            console.error('Error generating contract number:', error);
            Notify.error('Không thể sinh mã hợp đồng. Vui lòng thử lại.', 'Lỗi sinh mã');
        });
    }

    // Generate contract number button click handler
    generateContractNoBtn.addEventListener('click', function() {
        generateContractNumber();
    });

    // Property change handler
    propertySelect.addEventListener('change', function() {
        const propertyId = this.value;
        
        console.log('🏠 Property changed:', propertyId);
        
        if (!propertyId) {
            unitSelect.innerHTML = '<option value="">Chọn bất động sản trước</option>';
            unitSelect.disabled = true;
            return;
        }

        // Lấy booking_deposit_id từ dropdown (nếu có)
        const bookingDepositId = bookingDepositSelect ? bookingDepositSelect.value : null;
        
        // Build URL với booking_deposit_id nếu có
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
                
                // Clear units data
                unitsData = {};
                
                data.forEach(unit => {
                    // Store unit data for auto-fill
                    unitsData[unit.id] = unit;
                    
                    const option = document.createElement('option');
                    option.value = unit.id;
                    option.textContent = unit.code || `Phòng ${unit.id}`;
                    if (unit.floor) {
                        option.textContent += ` (Tầng ${unit.floor})`;
                    }
                    
                    // Kiểm tra nếu phòng đã có hợp đồng hoạt động
                    if (unit.has_active_lease) {
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
                
                // Bỏ disabled để có thể chọn phòng
                unitSelect.disabled = false;
                
                // Auto-select unit if unit_id is in URL
                if (unitIdFromUrl) {
                    console.log('🔍 Auto-selecting unit from URL:', unitIdFromUrl);
                    
                    // Try to find and select the unit (handle both string and number comparison)
                    let unitFound = false;
                    for (let i = 0; i < unitSelect.options.length; i++) {
                        const option = unitSelect.options[i];
                        if (!option.value || option.value === '') continue;
                        
                        // Compare as both string and number
                        if (String(option.value) === String(unitIdFromUrl) || 
                            parseInt(option.value) === parseInt(unitIdFromUrl)) {
                            unitSelect.value = option.value;
                            unitFound = true;
                            console.log('✅ Unit from URL found and selected:', option.value, option.textContent);
                            break;
                        }
                    }
                    
                    if (!unitFound) {
                        console.warn('⚠️ Unit from URL not found in dropdown:', unitIdFromUrl);
                    } else {
                        // Trigger change event to auto-fill rent and deposit
                        unitSelect.dispatchEvent(new Event('change'));
                    }
                }
                
                // Auto-select unit from booking deposit if pending (only if no unit from URL)
                // Priority: URL parameter > booking deposit
                if (pendingUnitIdFromBookingDeposit && !unitIdFromUrl) {
                    console.log('🔍 Checking for pending unit ID from booking deposit:', pendingUnitIdFromBookingDeposit, 'Type:', typeof pendingUnitIdFromBookingDeposit);
                    
                    // Get all available unit IDs with details
                    const availableUnitIds = Array.from(unitSelect.options)
                        .filter(opt => opt.value && opt.value !== '')
                        .map(opt => ({
                            value: opt.value, 
                            valueType: typeof opt.value, 
                            text: opt.textContent, 
                            disabled: opt.disabled
                        }));
                    
                    console.log('📋 Available units (' + availableUnitIds.length + '):', availableUnitIds);
                    console.log('📋 Available unit IDs only:', availableUnitIds.map(u => u.value).join(', '));
                    
                    // Try to find unit by value (convert to string and number for comparison)
                    const pendingUnitIdStr = String(pendingUnitIdFromBookingDeposit).trim();
                    const pendingUnitIdNum = parseInt(pendingUnitIdFromBookingDeposit);
                    let unitFound = false;
                    
                    // Check all options - try both string and number comparison
                    for (let i = 0; i < unitSelect.options.length; i++) {
                        const option = unitSelect.options[i];
                        if (!option.value || option.value === '') continue;
                        
                        // Try string comparison
                        const optionValueStr = String(option.value).trim();
                        const optionValueNum = parseInt(option.value);
                        
                        // Check if unit ID matches (either as string or number)
                        const isMatch = (optionValueStr === pendingUnitIdStr || 
                                       optionValueNum === pendingUnitIdNum ||
                                       String(option.value) === pendingUnitIdStr);
                        
                        if (isMatch) {
                            console.log('🎯 Unit match found:', {
                                optionValue: option.value,
                                optionValueStr: optionValueStr,
                                optionValueNum: optionValueNum,
                                pendingStr: pendingUnitIdStr,
                                pendingNum: pendingUnitIdNum,
                                disabled: option.disabled,
                                text: option.textContent
                            });
                            
                            if (!option.disabled) {
                                unitSelect.value = option.value;
                                unitSelect.dispatchEvent(new Event('change'));
                                console.log('✅ Auto-filled unit from booking deposit:', option.value, option.textContent);
                                unitFound = true;
                                break;
                            } else {
                                console.warn('⚠️ Unit found but disabled:', option.value, option.textContent);
                            }
                        }
                    }
                    
                    if (!unitFound) {
                        console.error('❌ Unit not found or disabled:', pendingUnitIdFromBookingDeposit);
                        console.error('Available unit IDs:', availableUnitIds.map(u => u.value).join(', '));
                        console.error('Pending unit ID (string):', pendingUnitIdStr);
                        console.error('Pending unit ID (number):', pendingUnitIdNum);
                    }
                    
                    // Clear pending unit ID
                    pendingUnitIdFromBookingDeposit = null;
                } else if (pendingUnitIdFromBookingDeposit && unitIdFromUrl) {
                    console.log('⏭️ Skipping booking deposit unit auto-fill because unit from URL has priority:', unitIdFromUrl);
                    pendingUnitIdFromBookingDeposit = null;
                }
            })
            .catch(error => {
                console.error('Error loading units:', error);
                Notify.error('Không thể tải danh sách phòng', 'Lỗi');
                unitSelect.disabled = false;
                unitSelect.innerHTML = '<option value="">Lỗi tải danh sách phòng</option>';
            });
        
        // Auto-fill agent based on property assignment
        loadPropertyAgent(propertyId);
        
        // Auto-fill payment cycle based on property
        loadPropertyPaymentCycle(propertyId);
        
        // Auto-fill lease service set based on property
        loadPropertyLeaseServiceSet(propertyId);
    });

    // Unit change handler - auto-fill rent and deposit
    unitSelect.addEventListener('change', function() {
        const unitId = this.value;
        const rentAmountInput = document.getElementById('rentAmount');
        const depositAmountInput = document.getElementById('depositAmount');
        
        if (unitId) {
            // Auto-select booking deposit if it matches the unit and isn't already selected
            if (bookingDepositSelect && (!bookingDepositSelect.value || bookingDepositSelect.value === '')) {
                const matchedOption = Array.from(bookingDepositSelect.options).find(opt => opt.getAttribute('data-unit-id') == unitId);
                if (matchedOption && matchedOption.value) {
                    bookingDepositSelect.value = matchedOption.value;
                    if (window.Notify) {
                        Notify.success('Đã tự động chọn Đặt cọc tương ứng với phòng này', 'Thông báo');
                    }
                    
                    // Auto-fill lead and agent from the matched booking deposit
                    const leadId = matchedOption.getAttribute('data-lead-id');
                    const agentId = matchedOption.getAttribute('data-agent-id');
                    
                    if (leadId && leadSelect) {
                        leadSelect.value = leadId;
                        leadSelect.dispatchEvent(new Event('change'));
                    }
                    if (agentId && agentSelect) {
                        agentSelect.value = agentId;
                    }
                }
            }
            
            if (unitsData[unitId]) {
                const unit = unitsData[unitId];
                
                // Auto-fill rent amount if exists and not already filled
                if (unit.base_rent && unit.base_rent > 0) {
                    if (!rentAmountInput.value || rentAmountInput.value.trim() === '') {
                        // Set raw number value (remove decimals), let input formatter handle formatting
                        const rentValue = Math.round(parseFloat(unit.base_rent));
                        rentAmountInput.value = rentValue.toString();
                        console.log('Auto-filled rent:', rentValue);
                        // Trigger input event for number formatter
                        rentAmountInput.dispatchEvent(new Event('input'));
                    }
                }
                
                // Auto-fill deposit amount if exists and not already filled
                if (unit.deposit_amount && unit.deposit_amount > 0) {
                    if (!depositAmountInput.value || depositAmountInput.value.trim() === '') {
                        // Set raw number value (remove decimals), let input formatter handle formatting
                        const depositValue = Math.round(parseFloat(unit.deposit_amount));
                        depositAmountInput.value = depositValue.toString();
                        console.log('Auto-filled deposit:', depositValue);
                        // Trigger input event for number formatter
                        depositAmountInput.dispatchEvent(new Event('input'));
                    }
                }
            }
        }
    });

    // Auto-fill suggested lease service set on page load (if available)
    @if(isset($suggestedLeaseServiceSetId) && $suggestedLeaseServiceSetId)
    const suggestedLeaseServiceSetId = {{ $suggestedLeaseServiceSetId }};
    if (leaseServiceSetSelect && !leaseServiceSetManuallySelected) {
        const suggestedOption = Array.from(leaseServiceSetSelect.options).find(
            opt => opt.value == suggestedLeaseServiceSetId
        );
        if (suggestedOption) {
            leaseServiceSetSelect.value = suggestedLeaseServiceSetId;
            console.log('✅ Auto-filled suggested lease service set on page load:', suggestedOption.textContent);
            // Trigger change event to load and display services preview
            leaseServiceSetSelect.dispatchEvent(new Event('change'));
        }
    }
    @endif

    // Auto-fill suggested payment cycle on page load (if available)
    @if(isset($suggestedPaymentCycleId) && $suggestedPaymentCycleId)
    const suggestedPaymentCycleId = {{ $suggestedPaymentCycleId }};
    if (paymentCycleSelect && !paymentCycleManuallySelected) {
        const suggestedOption = Array.from(paymentCycleSelect.options).find(
            opt => opt.value == suggestedPaymentCycleId
        );
        if (suggestedOption) {
            paymentCycleSelect.value = suggestedPaymentCycleId;
            console.log('✅ Auto-filled suggested payment cycle on page load:', suggestedOption.textContent);
        }
    }
    @endif

    // Auto-select property if property_id is in URL (on page load)
    if (propertySelect && propertyIdFromUrl) {
        propertySelect.value = propertyIdFromUrl;
        // Trigger change event to load units and auto-fill payment cycle
        propertySelect.dispatchEvent(new Event('change'));
    } else {
        // Nếu không có property từ URL, vẫn thử auto-fill default hoặc newest
        loadDefaultOrNewestLeaseServiceSet();
        loadDefaultOrNewestPaymentCycle();
    }

    // Handle lease service set dropdown change (already declared at top)
    if (leaseServiceSetSelect) {
        // Add another listener for loading services preview
        leaseServiceSetSelect.addEventListener('change', function() {
            const setId = this.value;
            if (setId) {
                // Load lease service set and show preview
                loadLeaseServiceSet(setId);
            } else {
                // Clear preview if no set selected
                servicesPreview.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Vui lòng chọn nhóm dịch vụ để xem danh sách dịch vụ.</div>';
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

    // Load and auto-fill agent from property assignment
    function loadPropertyAgent(propertyId) {
        if (!propertyId) {
            return;
        }
        
        if (!agentSelect) {
            return;
        }
        
        // Fetch property details with assigned agents
        fetch(`/staff/api/properties/${propertyId}`, {
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
            if (data && data.property) {
                const property = data.property;
                
                // Check if property has assigned agents (role_key = 'agent')
                if (property.assigned_users && property.assigned_users.length > 0) {
                    // Get agents only (filter by role_key = 'agent')
                    const assignedAgents = property.assigned_users.filter(user => 
                        user.pivot && user.pivot.role_key === 'agent'
                    );
                    
                    if (assignedAgents.length > 0) {
                        // Get the agent with highest ID (most recent assignment)
                        const latestAgent = assignedAgents.reduce((prev, current) => 
                            (current.id > prev.id) ? current : prev
                        );
                        
                        // Logic: CHỈ đổi nếu agent của property KHÁC người tạo
                        if (latestAgent.id != currentUserId) {
                            agentSelect.value = latestAgent.id;
                            console.log('Property has different agent, changed to:', latestAgent.full_name || latestAgent.id);
                        } else {
                            // Property agent = người tạo → KHÔNG đổi (giữ nguyên)
                            console.log('Property agent is current user, keeping current selection');
                        }
                    } else {
                        // No agent assigned → GIỮ NGUYÊN (người tạo)
                        console.log('No agent assigned to property, keeping current user');
                    }
                } else {
                    // No users assigned → GIỮ NGUYÊN (người tạo)
                    console.log('No users assigned to property, keeping current user');
                }
            }
        })
        .catch(error => {
            console.error('Error loading property agent:', error);
            // On error → GIỮ NGUYÊN
        });
    }

    // Load and auto-fill payment cycle from property
    function loadPropertyPaymentCycle(propertyId) {
        if (!propertyId) {
            console.log('❌ loadPropertyPaymentCycle: No propertyId provided');
            // Nếu không có property, vẫn thử lấy default hoặc newest
            loadDefaultOrNewestPaymentCycle();
            return;
        }
        
        if (!paymentCycleSelect) {
            console.log('❌ loadPropertyPaymentCycle: paymentCycleSelect not found');
            return;
        }
        
        // Only auto-fill if user hasn't manually selected a payment cycle
        if (paymentCycleManuallySelected) {
            console.log('⏭️ loadPropertyPaymentCycle: User has manually selected payment cycle, skipping auto-fill');
            return;
        }
        
        console.log('🔄 Loading payment cycle for property:', propertyId);
        
        // Fetch property payment cycle
        fetch(`/staff/api/properties/${propertyId}/payment-cycle`, {
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
            console.log('📦 Payment cycle API response:', data);
            
            if (data && data.success && data.property) {
                const property = data.property;
                
                // Priority: property.payment_cycle > property.effective_payment_cycle > default > newest
                let paymentCycleToUse = null;
                
                if (property.payment_cycle && property.payment_cycle.id) {
                    // Ưu tiên 1: Property has its own payment cycle
                    paymentCycleToUse = property.payment_cycle;
                    console.log('✅ Property has its own payment cycle:', paymentCycleToUse.name || paymentCycleToUse.cycle_type_name);
                } else if (property.effective_payment_cycle && property.effective_payment_cycle.id) {
                    // Ưu tiên 2: Use effective payment cycle (organization default hoặc newest)
                    paymentCycleToUse = property.effective_payment_cycle;
                    console.log('✅ Using effective payment cycle:', paymentCycleToUse.name || paymentCycleToUse.cycle_type_name);
                } else {
                    // Ưu tiên 3: Fallback về default payment cycle của organization
                    @if($defaultPaymentCycle)
                        paymentCycleToUse = {
                            id: {{ $defaultPaymentCycle->id }},
                            name: '{{ addslashes($defaultPaymentCycle->name ?? $defaultPaymentCycle->cycle_type_name ?? '') }}'
                        };
                        console.log('✅ Using organization default payment cycle:', paymentCycleToUse.name);
                    @else
                        // Ưu tiên 4: Lấy mới nhất nếu không có default
                        const newestOption = Array.from(paymentCycleSelect.options)
                            .filter(opt => opt.value && opt.value !== '')
                            .sort((a, b) => {
                                // Sort by option index (assuming newer items are added later)
                                return Array.from(paymentCycleSelect.options).indexOf(b) - Array.from(paymentCycleSelect.options).indexOf(a);
                            })[0];
                        
                        if (newestOption) {
                            paymentCycleToUse = {
                                id: newestOption.value,
                                name: newestOption.textContent
                            };
                            console.log('✅ Using newest payment cycle:', paymentCycleToUse.name);
                        } else {
                            console.log('❌ No payment cycle found');
                        }
                    @endif
                }
                
                // Auto-select payment cycle if found (nhưng vẫn cho phép null)
                if (paymentCycleToUse && paymentCycleToUse.id) {
                    // Check if the payment cycle exists in the dropdown
                    const cycleOption = Array.from(paymentCycleSelect.options).find(
                        opt => opt.value == paymentCycleToUse.id
                    );
                    
                    if (cycleOption) {
                        paymentCycleSelect.value = paymentCycleToUse.id;
                        console.log('✅ Auto-filled payment cycle:', paymentCycleToUse.name);
                    } else {
                        console.warn('⚠️ Payment cycle not found in dropdown:', paymentCycleToUse.id);
                    }
                } else {
                    console.log('No payment cycle found for property - leaving empty (nullable)');
                }
            }
        })
        .catch(error => {
            console.error('Error loading property payment cycle:', error);
            // On error → thử lấy default hoặc newest
            loadDefaultOrNewestPaymentCycle();
        });
    }
    
    // Helper function to load default or newest payment cycle
    function loadDefaultOrNewestPaymentCycle() {
        if (!paymentCycleSelect || paymentCycleManuallySelected) {
            return;
        }
        
        // Ưu tiên: default -> newest
        @if($defaultPaymentCycle)
            const defaultOption = Array.from(paymentCycleSelect.options).find(
                opt => opt.value == {{ $defaultPaymentCycle->id }}
            );
            if (defaultOption) {
                paymentCycleSelect.value = defaultOption.value;
                console.log('✅ Auto-filled default payment cycle:', defaultOption.textContent);
                return;
            }
        @endif
        
        // Nếu không có default, lấy mới nhất (option đầu tiên có value, sau option rỗng)
        const options = Array.from(paymentCycleSelect.options);
        const firstNonEmptyOption = options.find(opt => opt.value && opt.value !== '');
        if (firstNonEmptyOption) {
            paymentCycleSelect.value = firstNonEmptyOption.value;
            console.log('✅ Auto-filled newest payment cycle:', firstNonEmptyOption.textContent);
        }
    }

    // Load and auto-fill lease service set from property
    function loadPropertyLeaseServiceSet(propertyId) {
        if (!propertyId) {
            console.log('❌ loadPropertyLeaseServiceSet: No propertyId provided');
            // Nếu không có property, vẫn thử lấy default hoặc newest
            loadDefaultOrNewestLeaseServiceSet();
            return;
        }
        
        if (!leaseServiceSetSelect) {
            console.log('❌ loadPropertyLeaseServiceSet: leaseServiceSetSelect not found');
            return;
        }
        
        // Only auto-fill if user hasn't manually selected a lease service set
        if (leaseServiceSetManuallySelected) {
            console.log('⏭️ loadPropertyLeaseServiceSet: User has manually selected lease service set, skipping auto-fill');
            return;
        }
        
        console.log('🔄 Loading lease service set for property:', propertyId);
        
        // Fetch property lease service set
        fetch(`/staff/api/properties/${propertyId}/lease-service-set`, {
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
            console.log('📦 Lease service set API response:', data);
            
            if (data && data.success && data.property) {
                const property = data.property;
                
                // Priority: property.lease_service_set > property.effective_lease_service_set > default > newest
                let leaseServiceSetToUse = null;
                
                if (property.lease_service_set && property.lease_service_set.id) {
                    // Ưu tiên 1: Property has its own lease service set
                    leaseServiceSetToUse = property.lease_service_set;
                    console.log('✅ Property has its own lease service set:', leaseServiceSetToUse.name);
                } else if (property.effective_lease_service_set && property.effective_lease_service_set.id) {
                    // Ưu tiên 2: Use effective lease service set (organization default)
                    leaseServiceSetToUse = property.effective_lease_service_set;
                    console.log('✅ Using effective lease service set (organization default):', leaseServiceSetToUse.name);
                } else {
                    // Ưu tiên 3: Fallback về default lease service set của organization
                    @if($defaultLeaseServiceSet)
                        leaseServiceSetToUse = {
                            id: {{ $defaultLeaseServiceSet->id }},
                            name: '{{ addslashes($defaultLeaseServiceSet->name) }}'
                        };
                        console.log('✅ Using organization default lease service set:', leaseServiceSetToUse.name);
                    @else
                        // Ưu tiên 4: Lấy mới nhất nếu không có default
                        const newestOption = Array.from(leaseServiceSetSelect.options)
                            .filter(opt => opt.value && opt.value !== '')
                            .sort((a, b) => {
                                // Sort by option index (assuming newer items are added later)
                                return Array.from(leaseServiceSetSelect.options).indexOf(b) - Array.from(leaseServiceSetSelect.options).indexOf(a);
                            })[0];
                        
                        if (newestOption) {
                            leaseServiceSetToUse = {
                                id: newestOption.value,
                                name: newestOption.textContent
                            };
                            console.log('✅ Using newest lease service set:', leaseServiceSetToUse.name);
                        } else {
                            console.log('❌ No lease service set found');
                        }
                    @endif
                }
                
                // Auto-select lease service set if found (nhưng vẫn cho phép null)
                if (leaseServiceSetToUse && leaseServiceSetToUse.id) {
                    // Check if the lease service set exists in the dropdown
                    const setOption = Array.from(leaseServiceSetSelect.options).find(
                        opt => opt.value == leaseServiceSetToUse.id
                    );
                    
                    if (setOption) {
                        leaseServiceSetSelect.value = leaseServiceSetToUse.id;
                        console.log('✅ Auto-filled lease service set:', leaseServiceSetToUse.name);
                        
                        // Trigger change event to load and display services preview
                        leaseServiceSetSelect.dispatchEvent(new Event('change'));
                    } else {
                        console.warn('⚠️ Lease service set not found in dropdown:', leaseServiceSetToUse.id);
                    }
                } else {
                    console.log('No lease service set found for property - leaving empty (nullable)');
                }
            }
        })
        .catch(error => {
            console.error('Error loading property lease service set:', error);
            // On error → thử lấy default hoặc newest
            loadDefaultOrNewestLeaseServiceSet();
        });
    }
    
    // Helper function to load default or newest lease service set
    function loadDefaultOrNewestLeaseServiceSet() {
        if (!leaseServiceSetSelect || leaseServiceSetManuallySelected) {
            return;
        }
        
        // Ưu tiên: default -> newest
        @if($defaultLeaseServiceSet)
            const defaultOption = Array.from(leaseServiceSetSelect.options).find(
                opt => opt.value == {{ $defaultLeaseServiceSet->id }}
            );
            if (defaultOption) {
                leaseServiceSetSelect.value = defaultOption.value;
                console.log('✅ Auto-filled default lease service set:', defaultOption.textContent);
                leaseServiceSetSelect.dispatchEvent(new Event('change'));
                return;
            }
        @endif
        
        // Nếu không có default, lấy mới nhất (option đầu tiên có value, sau option rỗng)
        const options = Array.from(leaseServiceSetSelect.options);
        const firstNonEmptyOption = options.find(opt => opt.value && opt.value !== '');
        if (firstNonEmptyOption) {
            leaseServiceSetSelect.value = firstNonEmptyOption.value;
            console.log('✅ Auto-filled newest lease service set:', firstNonEmptyOption.textContent);
            leaseServiceSetSelect.dispatchEvent(new Event('change'));
        }
    }

    // Form submission
    document.getElementById('leaseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate: lead_id must be provided
        const leadSelect = document.getElementById('lead_id');
        const leadId = leadSelect ? leadSelect.value : '';
        
        if (!leadId) {
            Notify.error('Vui lòng chọn lead.', 'Thiếu thông tin');
            return;
        }
        
        // Kiểm tra phòng đã có hợp đồng hoạt động
        const selectedUnit = unitSelect.options[unitSelect.selectedIndex];
        if (selectedUnit && selectedUnit.disabled) {
            Notify.error('Phòng này đã có hợp đồng hoạt động. Vui lòng chọn phòng khác hoặc chấm dứt hợp đồng hiện tại trước.', 'Không thể tạo hợp đồng');
            return;
        }
        
        if (window.Preloader) {
            window.Preloader.show();
        }

        // Unformat money inputs before submitting (remove dots, convert to pure number)
        const rentInput = document.getElementById('rentAmount');
        const depositInput = document.getElementById('depositAmount');
        
        if (rentInput && rentInput.value) {
            rentInput.value = rentInput.value.replace(/\./g, '');
            console.log('Unformatted rent:', rentInput.value);
        }
        
        if (depositInput && depositInput.value) {
            depositInput.value = depositInput.value.replace(/\./g, '');
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
                } else if (data.lease_id) {
                    setTimeout(() => {
                        window.location.href = `/staff/leases/${data.lease_id}`;
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
            Notify.error('Không thể tạo hợp đồng: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
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
