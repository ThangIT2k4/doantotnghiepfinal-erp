@extends('layouts.staff_dashboard')

@section('title', 'Tạo Hóa đơn Mới')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Thêm Hóa đơn mới',
            'subtitle' => 'Tạo hóa đơn mới cho hợp đồng thuê',
            'icon' => 'fas fa-user-plus',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.invoices.index')
                ]
            ]
        ])

        {{-- 2. Form với Layout Full Width (cho form dài) --}}
        <form id="invoiceForm" method="POST" action="{{ route('staff.invoices.store') }}">
            @csrf
            
            @if(isset($isCycleInvoice) && $isCycleInvoice)
            <input type="hidden" name="cycle_invoice" value="1">
            @endif
            @if(isset($isFirstInvoice) && $isFirstInvoice && isset($prefillData))
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle"></i>
                <strong>Hóa đơn đầu tiên:</strong> Thông tin đã được điền sẵn theo logic hóa đơn đầu tiên. Vui lòng kiểm tra và tạo hóa đơn.
            </div>
            @endif
            @if(isset($isCycleInvoice) && $isCycleInvoice)
            <div class="alert alert-warning mb-3">
                <i class="fas fa-calendar-alt"></i>
                <strong>Hóa đơn chu kỳ:</strong> 
                @if(isset($prefillData) && isset($prefillData['cycle_number']))
                    Tạo hóa đơn cho chu kỳ {{ $prefillData['cycle_number'] }} 
                    (từ {{ \Carbon\Carbon::parse($prefillData['cycle_start'])->format('d/m/Y') }} 
                    đến {{ \Carbon\Carbon::parse($prefillData['cycle_end'])->format('d/m/Y') }}).
                    Thông tin đã được điền sẵn dựa trên meter readings và dịch vụ.
                @else
                    Tạo hóa đơn cho chu kỳ thanh toán. Vui lòng điền thông tin và các item.
                @endif
            </div>
            @endif
            @if(isset($selectedLeaseId) && $selectedLeaseId && !isset($isFirstInvoice) && !isset($isCycleInvoice))
            <div class="alert alert-secondary mb-3">
                <i class="fas fa-file-alt"></i>
                <strong>Hóa đơn thông thường:</strong> Hợp đồng đã được chọn. Vui lòng điền thông tin và các item.
            </div>
            @endif

            {{-- Card 1: Thông tin cơ bản --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Số hóa đơn <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="invoice_no" id="invoice_no" class="form-control" 
                                   value="{{ $previewInvoiceNo ?? '' }}" 
                                   placeholder="Đang tạo mã hóa đơn..." 
                                   readonly 
                                   style="background-color: #f8f9fa;">
                            {{-- <button type="button" id="generateInvoiceNo" class="btn btn-outline-primary" title="Tạo mã hóa đơn mới">
                                <i class="fas fa-sync-alt"></i> Tự sinh
                            </button> --}}
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle text-info"></i>
                            Mã hóa đơn sẽ được tự động sinh theo format HD-YYYYMM-XXXX (VD: HD-202511-0014)
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Hợp đồng</label>
                        <select name="lease_id" id="leaseSelect" class="form-select">
                            <option value="">Chọn hợp đồng</option>
                            @foreach ($leases as $lease)
                            <option value="{{ $lease->id }}" 
                                    {{ (isset($prefillData) && isset($prefillData['lease_id']) && $prefillData['lease_id'] == $lease->id) ? 'selected' : '' }}
                                    data-rent="{{ $lease->rent_amount }}"
                                    data-tenant="{{ $lease->tenant->full_name ?? 'N/A' }}"
                                    data-property="{{ $lease->unit->property->name ?? 'N/A' }}">
                                {{ $lease->contract_no ?? 'HD#' . $lease->id }} - {{ $lease->tenant->full_name ?? 'N/A' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Đặt cọc</label>
                        <select name="booking_deposit_id" id="bookingDepositSelect" class="form-select">
                            <option value="">Chọn đặt cọc</option>
                            @foreach ($bookingDeposits as $bookingDeposit)
                            <option value="{{ $bookingDeposit->id }}" 
                                    {{ (isset($prefillData) && isset($prefillData['booking_deposit_id']) && $prefillData['booking_deposit_id'] == $bookingDeposit->id) ? 'selected' : '' }}
                                    data-amount="{{ $bookingDeposit->amount }}"
                                    data-customer="{{ $bookingDeposit->tenantUser->full_name ?? $bookingDeposit->lead->name ?? 'N/A' }}"
                                    data-property="{{ $bookingDeposit->unit->property->name ?? 'N/A' }}">
                                {{ $bookingDeposit->reference_number ?? 'BD#' . $bookingDeposit->id }} - 
                                {{ $bookingDeposit->tenantUser->full_name ?? $bookingDeposit->lead->name ?? 'N/A' }} - 
                                {{ $bookingDeposit->unit->property->name ?? 'N/A' }}
                            </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Chọn hợp đồng hoặc đặt cọc (chọn một trong hai)</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ngày phát hành <span class="text-danger">*</span></label>
                                <input type="date" name="issue_date" class="form-control" 
                                       value="{{ isset($prefillData) && isset($prefillData['issue_date']) ? $prefillData['issue_date'] : date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Hạn thanh toán <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" class="form-control" 
                                       value="{{ isset($prefillData) && isset($prefillData['due_date']) ? $prefillData['due_date'] : date('Y-m-d', strtotime('+30 days')) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Loại hóa đơn <span class="text-danger">*</span></label>
                        <select name="invoice_type" id="invoiceTypeSelect" class="form-select">
                            <option value="">Tự động xác định</option>
                            <option value="monthly_rent" {{ (isset($isCycleInvoice) && $isCycleInvoice) ? 'selected' : '' }}>Tiền thuê hàng tháng</option>
                            <option value="first_invoice" {{ (isset($isFirstInvoice) && $isFirstInvoice) ? 'selected' : '' }}>Hoá đơn đầu</option>
                            <option value="booking_deposit">Hoá đơn đặt cọc</option>
                            <option value="other">Khác</option>
                        </select>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle text-info"></i>
                            Để trống để hệ thống tự động xác định loại hóa đơn dựa trên hợp đồng/đặt cọc
                        </small>
                    </div>

                    {{-- Trạng thái luôn là 'draft' khi tạo mới, không hiển thị trong form --}}
                    <input type="hidden" name="status" value="draft">
                    
                    <div class="mb-3">
                        <label class="form-label">Người tạo <span class="text-danger">*</span></label>
                        <select name="created_by" id="created_by" class="form-select" required>
                            <option value="">Chọn người tạo</option>
                            @foreach ($managersAndAgents as $user)
                            <option value="{{ $user->id }}" 
                                    {{ (Auth::id() == $user->id) ? 'selected' : '' }}
                                    data-name="{{ $user->userProfile->full_name ?? $user->email ?? 'N/A' }}">
                                {{ $user->userProfile->full_name ?? $user->email ?? 'N/A' }}
                                @if($user->userRoles->contains('key_code', 'manager'))
                                    <span class="badge bg-primary">Manager</span>
                                @elseif($user->userRoles->contains('key_code', 'agent'))
                                    <span class="badge bg-info">Agent</span>
                                @endif
                            </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Tự động điền với người đang đăng nhập</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="note" class="form-control" rows="3" 
                                  placeholder="Ghi chú thêm cho hóa đơn">{{ isset($prefillData) && isset($prefillData['note']) ? $prefillData['note'] : '' }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Card 2: Chi tiết hóa đơn --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Chi tiết hóa đơn
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Tiền tệ</label>
                        <select name="currency" class="form-select">
                            <option value="VND" {{ (isset($prefillData) && isset($prefillData['currency']) && $prefillData['currency'] == 'VND') ? 'selected' : '' }}>VND</option>
                            <option value="USD" {{ (isset($prefillData) && isset($prefillData['currency']) && $prefillData['currency'] == 'USD') ? 'selected' : '' }}>USD</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tổng tiền trước thuế</label>
                                <input type="number" name="subtotal" id="subtotal" class="form-control" 
                                       step="0.01" min="0" value="{{ isset($prefillData) && isset($prefillData['subtotal']) ? $prefillData['subtotal'] : 0 }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Thuế</label>
                                <input type="number" name="tax_amount" id="tax_amount" class="form-control" 
                                       step="0.01" min="0" value="{{ isset($prefillData) && isset($prefillData['tax_amount']) ? $prefillData['tax_amount'] : 0 }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Giảm giá</label>
                                <input type="number" name="discount_amount" id="discount_amount" class="form-control" 
                                       step="0.01" min="0" value="{{ isset($prefillData) && isset($prefillData['discount_amount']) ? $prefillData['discount_amount'] : 0 }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tổng tiền <span class="text-danger">*</span></label>
                                <input type="number" name="total_amount" id="total_amount" class="form-control" 
                                       step="0.01" min="0" value="{{ isset($prefillData) && isset($prefillData['total_amount']) ? $prefillData['total_amount'] : 0 }}" required readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 3: Chi tiết các khoản --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Chi tiết các khoản
                        </h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-success btn-sm" id="loadServices">
                                <i class="fas fa-sync"></i> Tải dịch vụ từ hợp đồng
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addItem">
                                <i class="fas fa-plus"></i> Thêm khoản
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="invoiceItems">
                        @if(isset($prefillData) && !empty($prefillData['items']))
                            @foreach($prefillData['items'] as $index => $item)
                            <div class="invoice-item border rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label">Loại</label>
                                        <select name="items[{{ $index }}][item_type]" class="form-select item-type">
                                            <option value="rent" {{ $item['item_type'] == 'rent' ? 'selected' : '' }}>Tiền thuê</option>
                                            <option value="service" {{ $item['item_type'] == 'service' ? 'selected' : '' }}>Dịch vụ</option>
                                            <option value="meter" {{ $item['item_type'] == 'meter' ? 'selected' : '' }}>Đồng hồ</option>
                                            <option value="deposit" {{ $item['item_type'] == 'deposit' ? 'selected' : '' }}>Cọc</option>
                                            <option value="other" {{ $item['item_type'] == 'other' ? 'selected' : '' }}>Khác</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Mô tả</label>
                                        <input type="text" name="items[{{ $index }}][description]" class="form-control" 
                                               value="{{ $item['description'] }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Số lượng</label>
                                        <input type="number" name="items[{{ $index }}][quantity]" class="form-control item-quantity" 
                                               step="1" min="1" value="{{ (int)($item['quantity'] ?? 1) }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Đơn giá</label>
                                        <input type="number" name="items[{{ $index }}][unit_price]" class="form-control item-unit-price" 
                                               step="0.01" value="{{ $item['unit_price'] }}" required>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">Thành tiền</label>
                                        <input type="number" name="items[{{ $index }}][amount]" class="form-control item-amount" 
                                               step="0.01" min="0" value="{{ $item['amount'] }}" required readonly>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-outline-danger remove-item" style="display: {{ count($prefillData['items']) > 1 ? 'block' : 'none' }};">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            @php
                                $itemIndex = count($prefillData['items']);
                            @endphp
                        @else
                            <!-- Default rent item -->
                            <div class="invoice-item border rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label">Loại</label>
                                        <select name="items[0][item_type]" class="form-select item-type">
                                            <option value="rent">Tiền thuê</option>
                                            <option value="service">Dịch vụ</option>
                                            <option value="meter">Đồng hồ</option>
                                            <option value="deposit">Cọc</option>
                                            <option value="other">Khác</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Mô tả</label>
                                        <input type="text" name="items[0][description]" class="form-control" 
                                               value="Tiền thuê phòng" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Số lượng</label>
                                        <input type="number" name="items[0][quantity]" class="form-control item-quantity" 
                                               step="1" min="1" value="1" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Đơn giá</label>
                                        <input type="number" name="items[0][unit_price]" class="form-control item-unit-price" 
                                               step="0.01" min="0" value="0" required>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">Thành tiền</label>
                                        <input type="number" name="items[0][amount]" class="form-control item-amount" 
                                               step="0.01" min="0" value="0" required readonly>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-outline-danger remove-item" style="display: none;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @php
                                $itemIndex = 1;
                            @endphp
                        @endif
                    </div>
                </div>
            </div>

            {{-- Form Actions: Layout ngang cho form dài --}}
            <div class="card shadow-sm">
                <div class="card-body">
                    @include('staff.components.action-buttons', [
                        'layout' => 'horizontal',
                        'size' => 'md',
                        'actions' => [
                            [
                                'type' => 'submit',
                                'variant' => 'primary',
                                'label' => 'Tạo hóa đơn',
                                'icon' => 'fas fa-save'
                            ],
                            [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Hủy',
                                'icon' => 'fas fa-times',
                                'url' => route('staff.invoices.index')
                            ]
                        ]
                    ])
                </div>
            </div>
        </form>
    </div>
</main>
@endsection

@push('scripts')
<script>
let itemIndex = {{ $itemIndex ?? 1 }};

// Auto-fill form if prefillData exists
@if(isset($prefillData) && !empty($prefillData['items']))
document.addEventListener('DOMContentLoaded', function() {
    // Calculate totals on load
    calculateTotals();
    
    // Add event listeners to all items
    document.querySelectorAll('.invoice-item').forEach(item => {
        addItemEventListeners(item);
    });
});
@endif

// Calculate totals
function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('.item-amount').forEach(input => {
        subtotal += parseFloat(input.value) || 0;
    });
    
    const taxAmount = parseFloat(document.getElementById('tax_amount').value) || 0;
    const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
    const totalAmount = subtotal + taxAmount - discountAmount;
    
    document.getElementById('subtotal').value = subtotal.toFixed(2);
    document.getElementById('total_amount').value = totalAmount.toFixed(2);
}

// Calculate item amount
function calculateItemAmount(itemElement) {
    const quantity = parseInt(itemElement.querySelector('.item-quantity').value) || 1;
    const unitPrice = parseFloat(itemElement.querySelector('.item-unit-price').value) || 0;
    const amount = quantity * unitPrice;
    
    itemElement.querySelector('.item-amount').value = amount.toFixed(2);
    calculateTotals();
}

// Add item event listeners
function addItemEventListeners(itemElement) {
    itemElement.querySelector('.item-quantity').addEventListener('input', () => calculateItemAmount(itemElement));
    itemElement.querySelector('.item-unit-price').addEventListener('input', () => calculateItemAmount(itemElement));
}

// Add service item from lease
function addServiceItem(service, index) {
    const itemsContainer = document.getElementById('invoiceItems');
    const newItem = document.createElement('div');
    newItem.className = 'invoice-item border rounded p-3 mb-3';
    newItem.innerHTML = `
        <div class="row">
            <div class="col-md-2">
                <label class="form-label">Loại</label>
                <select name="items[${index}][item_type]" class="form-select item-type">
                    <option value="service" selected>Dịch vụ</option>
                    <option value="rent">Tiền thuê</option>
                    <option value="meter">Đồng hồ</option>
                    <option value="deposit">Cọc</option>
                    <option value="other">Khác</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Mô tả</label>
                <input type="text" name="items[${index}][description]" class="form-control" 
                       value="${service.service_name}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Số lượng</label>
                <input type="number" name="items[${index}][quantity]" class="form-control item-quantity" 
                       step="1" min="1" value="1" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Đơn giá</label>
                <input type="number" name="items[${index}][unit_price]" class="form-control item-unit-price" 
                       step="0.01" min="0" value="${service.price}" required>
            </div>
            <div class="col-md-1">
                <label class="form-label">Thành tiền</label>
                <input type="number" name="items[${index}][amount]" class="form-control item-amount" 
                       step="0.01" min="0" value="0" required readonly>
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-outline-danger remove-item">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    itemsContainer.appendChild(newItem);
    addItemEventListeners(newItem);
}

// Add new item
document.getElementById('addItem').addEventListener('click', function() {
    const itemsContainer = document.getElementById('invoiceItems');
    const newItem = document.createElement('div');
    newItem.className = 'invoice-item border rounded p-3 mb-3';
    newItem.innerHTML = `
        <div class="row">
            <div class="col-md-2">
                <label class="form-label">Loại</label>
                <select name="items[${itemIndex}][item_type]" class="form-select item-type">
                    <option value="rent">Tiền thuê</option>
                    <option value="service">Dịch vụ</option>
                    <option value="meter">Đồng hồ</option>
                    <option value="deposit">Cọc</option>
                    <option value="other">Khác</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Mô tả</label>
                <input type="text" name="items[${itemIndex}][description]" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Số lượng</label>
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control item-quantity" 
                       step="1" min="1" value="1" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Đơn giá</label>
                <input type="number" name="items[${itemIndex}][unit_price]" class="form-control item-unit-price" 
                       step="0.01" min="0" value="0" required>
            </div>
            <div class="col-md-1">
                <label class="form-label">Thành tiền</label>
                <input type="number" name="items[${itemIndex}][amount]" class="form-control item-amount" 
                       step="0.01" min="0" value="0" required readonly>
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-outline-danger remove-item">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    itemsContainer.appendChild(newItem);
    addItemEventListeners(newItem);
    itemIndex++;
    
    // Show remove buttons for all items
    document.querySelectorAll('.remove-item').forEach(btn => btn.style.display = 'block');
});

// Remove item handler
document.getElementById('invoiceItems').addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        e.target.closest('.invoice-item').remove();
        calculateTotals();
        
        // Hide remove buttons if only one item left
        if (document.querySelectorAll('.invoice-item').length === 1) {
            document.querySelectorAll('.remove-item').forEach(btn => btn.style.display = 'none');
        }
    }
});

// Booking deposit selection handler
document.getElementById('bookingDepositSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const leaseSelect = document.getElementById('leaseSelect');
    
    if (selectedOption.value) {
        // Clear lease selection if booking deposit is selected
        if (leaseSelect.value) {
            leaseSelect.value = '';
        }
        
        const bookingDepositId = selectedOption.value;
        const amount = selectedOption.getAttribute('data-amount') || 0;
        
        // Update first item with booking deposit amount
        const rentItem = document.querySelector('.invoice-item');
        if (rentItem) {
            const rentInput = rentItem.querySelector('.item-unit-price');
            const rentDescription = rentItem.querySelector('input[name*="[description]"]');
            const itemTypeSelect = rentItem.querySelector('.item-type');
            
            if (rentInput) rentInput.value = amount;
            if (rentDescription) rentDescription.value = 'Đặt cọc - ' + selectedOption.getAttribute('data-property');
            if (itemTypeSelect) itemTypeSelect.value = 'deposit';
            
            calculateItemAmount(rentItem);
        }
        
        Notify.info('Đã chọn đặt cọc. Bạn vẫn có thể chọn hợp đồng (sẽ thay thế đặt cọc).', 'Thông báo');
    } else {
        // If booking deposit is cleared, allow lease selection
        Notify.info('Đã bỏ chọn đặt cọc. Bạn có thể chọn hợp đồng.', 'Thông báo');
    }
});

// Lease selection handler
document.getElementById('leaseSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const bookingDepositSelect = document.getElementById('bookingDepositSelect');
    
    if (selectedOption.value) {
        // Clear booking deposit selection if lease is selected
        if (bookingDepositSelect.value) {
            bookingDepositSelect.value = '';
        }
        
        const leaseId = selectedOption.value;
        
        // Show loading
        const loadingToast = Notify.toast({
            title: 'Đang tải...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });
        
        // Fetch lease details
        fetch(`/staff/api/invoices/leases/${leaseId}/details`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Lease details response:', data);
            
            // Hide loading
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            
            // Clear existing items except the first one
            const itemsContainer = document.getElementById('invoiceItems');
            const existingItems = itemsContainer.querySelectorAll('.invoice-item');
            for (let i = 1; i < existingItems.length; i++) {
                existingItems[i].remove();
            }
            
            // Update rent item (first item) - set as rent type
            const rentItem = document.querySelector('.invoice-item');
            if (rentItem) {
                // Set item type to "rent"
                const itemTypeSelect = rentItem.querySelector('.item-type');
                if (itemTypeSelect) {
                    itemTypeSelect.value = 'rent';
                }
                
                // Update description
                const rentDescription = rentItem.querySelector('input[name*="[description]"]');
                if (rentDescription) {
                    rentDescription.value = 'Tiền thuê phòng';
                }
                
                // Update unit price with rent amount
                const rentInput = rentItem.querySelector('.item-unit-price');
                if (rentInput) {
                    rentInput.value = data.rent_amount || 0;
                    console.log('Updated rent amount:', data.rent_amount);
                }
                
                // Set quantity to 1 for rent
                const rentQuantity = rentItem.querySelector('.item-quantity');
                if (rentQuantity) {
                    rentQuantity.value = 1;
                }
                
                calculateItemAmount(rentItem);
            }
            
            // Update subtotal and total
            calculateTotals();
            
            // Note: Services are NOT automatically added when selecting lease
            // User must click "Tải dịch vụ từ hợp đồng" button to add services
            
            Notify.success('Đã tải thông tin hợp đồng', 'Thành công!');
        })
        .catch(error => {
            // Hide loading
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            
            console.error('Error:', error);
            Notify.error('Không thể tải thông tin hợp đồng', 'Lỗi!');
        });
    }
});

// Load services from lease button handler
document.getElementById('loadServices').addEventListener('click', function() {
    const leaseSelect = document.getElementById('leaseSelect');
    const leaseId = leaseSelect.value;
    
    if (!leaseId) {
        Notify.warning('Vui lòng chọn hợp đồng trước', 'Cảnh báo!');
        return;
    }
    
    // Show loading
    const loadingToast = Notify.toast({
        title: 'Đang tải...',
        message: 'Vui lòng chờ trong giây lát',
        type: 'info',
        duration: 0
    });
    
    // Fetch lease details
    fetch(`/staff/api/invoices/leases/${leaseId}/details`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || `HTTP error! status: ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Lease details response (loadServices):', data);
        
        // Hide loading
        const toastElement = document.getElementById(loadingToast);
        if (toastElement) {
            const bsToast = bootstrap.Toast.getInstance(toastElement);
            if (bsToast) bsToast.hide();
        }
        
        // Check if request was successful
        if (!data.success) {
            Notify.error(data.message || 'Không thể tải dịch vụ từ hợp đồng', 'Lỗi!');
            return;
        }
        
        // Clear existing items except the first one
        const itemsContainer = document.getElementById('invoiceItems');
        const existingItems = itemsContainer.querySelectorAll('.invoice-item');
        for (let i = 1; i < existingItems.length; i++) {
            existingItems[i].remove();
        }
        
        // Update rent item (first item) - set as rent type
        const rentItem = document.querySelector('.invoice-item');
        if (rentItem) {
            // Set item type to "rent"
            const itemTypeSelect = rentItem.querySelector('.item-type');
            if (itemTypeSelect) {
                itemTypeSelect.value = 'rent';
            }
            
            // Update description
            const rentDescription = rentItem.querySelector('input[name*="[description]"]');
            if (rentDescription) {
                rentDescription.value = 'Tiền thuê phòng';
            }
            
            // Update unit price with rent amount
            const rentInput = rentItem.querySelector('.item-unit-price');
            if (rentInput) {
                rentInput.value = data.rent_amount || 0;
                console.log('Updated rent amount (loadServices):', data.rent_amount);
            }
            
            // Set quantity to 1 for rent
            const rentQuantity = rentItem.querySelector('.item-quantity');
            if (rentQuantity) {
                rentQuantity.value = 1;
            }
            
            calculateItemAmount(rentItem);
        }
        
        // Update subtotal and total
        calculateTotals();
        
        // Add service items with quantity 0
        if (data.services && data.services.length > 0) {
            console.log('Adding services (loadServices):', data.services);
            data.services.forEach((service, index) => {
                addServiceItem(service, itemIndex + index);
            });
            itemIndex += data.services.length;
            
            // Show remove buttons if more than one item
            if (document.querySelectorAll('.invoice-item').length > 1) {
                document.querySelectorAll('.remove-item').forEach(btn => btn.style.display = 'block');
            }
            
            Notify.success(`Đã thêm ${data.services.length} dịch vụ từ hợp đồng`, 'Thành công!');
        } else {
            Notify.info('Hợp đồng này không có dịch vụ nào', 'Thông báo');
        }
    })
    .catch(error => {
        // Hide loading
        const toastElement = document.getElementById(loadingToast);
        if (toastElement) {
            const bsToast = bootstrap.Toast.getInstance(toastElement);
            if (bsToast) bsToast.hide();
        }
        
        console.error('Error:', error);
        Notify.error('Không thể tải dịch vụ từ hợp đồng', 'Lỗi!');
    });
});

// Tax and discount handlers
document.getElementById('tax_amount').addEventListener('input', calculateTotals);
document.getElementById('discount_amount').addEventListener('input', calculateTotals);

// Generate invoice number button - sẽ được tạo tự động khi submit form
// Nếu muốn preview trước, có thể gọi API hoặc để server tự động tạo
document.getElementById('generateInvoiceNo')?.addEventListener('click', function() {
    const invoiceNoInput = document.getElementById('invoice_no');
    // Số hóa đơn sẽ được tạo tự động khi submit form
    // Nút này chỉ để refresh preview
    invoiceNoInput.value = '{{ $previewInvoiceNo ?? "" }}';
    Notify.info('Số hóa đơn sẽ được tạo tự động khi lưu', 'Thông tin');
});

// Initialize
addItemEventListeners(document.querySelector('.invoice-item'));

// Form submission
document.getElementById('invoiceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (window.Preloader) {
        window.Preloader.show();
    }

    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Notify.success(data.message, 'Thành công!');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            }
        } else {
            Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Notify.error('Không thể tạo hóa đơn: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
});
</script>
@endpush
