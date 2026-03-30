@extends('layouts.staff_dashboard')

@section('title', 'Tạo Hóa đơn Công ty')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Thêm Hóa đơn Công ty mới',
            'subtitle' => 'Tạo hóa đơn công ty mới trong hệ thống',
            'icon' => 'fas fa-user-plus',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.company-invoices.index')
                ]
            ]
        ])

        {{-- 2. Form với Layout Full Width (cho form dài) --}}
        <form id="invoice-form" method="POST" action="{{ route('staff.company-invoices.store') }}" enctype="multipart/form-data">
            @csrf
            
            {{-- Card 1: Thông tin cơ bản --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Người nhận <span class="text-danger">*</span></label>
                                <div class="mb-2">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="recipient_type" id="recipientVendor" value="vendor" {{ old('recipient_type', old('vendor_id') ? 'vendor' : (old('user_id') ? 'user' : 'vendor')) == 'vendor' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="recipientVendor">Nhà cung cấp</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="recipient_type" id="recipientUser" value="user" {{ old('recipient_type', old('user_id') ? 'user' : '') == 'user' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="recipientUser">Người dùng</label>
                                        </div>
                                </div>
                                <select name="vendor_id" id="vendorSelect" class="form-select mb-2 @error('vendor_id') is-invalid @enderror" {{ old('recipient_type', 'vendor') == 'vendor' ? '' : 'style=display:none;' }}>
                                        <option value="">Chọn nhà cung cấp</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vendor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                <select name="user_id" id="userSelect" class="form-select @error('user_id') is-invalid @enderror" {{ old('recipient_type') == 'user' || old('user_id') ? '' : 'style=display:none;' }}>
                                        <option value="">Chọn người dùng</option>
                                        @isset($users)
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->full_name }} {{ $user->email ? ' - ' . $user->email : '' }}
                                                </option>
                                            @endforeach
                                        @endisset
                                    </select>
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @error('recipient')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Loại hóa đơn <span class="text-danger">*</span></label>
                                <select name="invoice_type" id="invoice_type" class="form-select @error('invoice_type') is-invalid @enderror" required onchange="loadSourceData()">
                                        <option value="">Chọn loại hóa đơn</option>
                                        @foreach($types as $key => $label)
                                            <option value="{{ $key }}" {{ old('invoice_type', $selectedMasterLease ? 'master_lease' : '') == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('invoice_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            <div class="mb-3" id="source-data-group" style="display: {{ $selectedMasterLease ? 'block' : 'none' }};">
                                <label class="form-label">Nguồn dữ liệu <span class="text-danger" id="source-data-required" style="display: none;">*</span></label>
                                <select name="source_id" class="form-select @error('deposit_refund_id') is-invalid @enderror @error('master_lease_id') is-invalid @enderror @error('ticket_id') is-invalid @enderror @error('payroll_payslip_id') is-invalid @enderror" id="source-data-select">
                                        <option value="">Chọn nguồn dữ liệu</option>
                                        @if($selectedMasterLease && isset($sourceData))
                                            @foreach($sourceData as $source)
                                                <option value="{{ $source->id }}" {{ $selectedMasterLease->id == $source->id ? 'selected' : '' }}>
                                                    {{ $source->contract_no ?? 'Hợp đồng #' . $source->id }} - {{ $source->property->name ?? '' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('deposit_refund_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('master_lease_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('ticket_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('payroll_payslip_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                            <div class="mb-3">
                                <label class="form-label">Người tạo <span class="text-danger">*</span></label>
                                <select name="created_by" class="form-select @error('created_by') is-invalid @enderror" required>
                                        <option value="">Chọn người tạo</option>
                                        @isset($users)
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ old('created_by', Auth::id()) == $user->id ? 'selected' : '' }}>
                                                    {{ $user->full_name }} {{ $user->email ? ' - ' . $user->email : '' }}
                                                </option>
                                            @endforeach
                                        @endisset
                                    </select>
                                    @error('created_by')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            {{-- Trạng thái luôn là 'draft' khi tạo mới, không hiển thị trong form --}}
                            <input type="hidden" name="status" value="draft">
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ngày phát hành <span class="text-danger">*</span></label>
                                <input type="date" name="issue_date" class="form-control @error('issue_date') is-invalid @enderror" 
                                       value="{{ old('issue_date', $prefilledData['issue_date'] ?? date('Y-m-d')) }}" required>
                                    @error('issue_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            <div class="mb-3">
                                <label class="form-label">Ngày đến hạn <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" 
                                       value="{{ old('due_date', $prefilledData['due_date'] ?? date('Y-m-d', strtotime('+30 days'))) }}" required>
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            <div class="mb-3">
                                <label class="form-label">Đơn vị tiền tệ</label>
                                <select name="currency" class="form-select @error('currency') is-invalid @enderror">
                                        <option value="VND" {{ old('currency', $prefilledData['currency'] ?? 'VND') == 'VND' ? 'selected' : '' }}>VND</option>
                                        <option value="USD" {{ old('currency', $prefilledData['currency'] ?? '') == 'USD' ? 'selected' : '' }}>USD</option>
                                        <option value="EUR" {{ old('currency', $prefilledData['currency'] ?? '') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                    </select>
                                    @error('currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 2: Chi tiết các khoản --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Chi tiết các khoản
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="items-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 15%">Loại</th>
                                                <th>Mô tả</th>
                                                <th style="width: 12%" class="text-end">Số lượng</th>
                                                <th style="width: 16%" class="text-end">Đơn giá</th>
                                                <th style="width: 16%" class="text-end">Thành tiền</th>
                                                <th style="width: 6%"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="items-body">
                                            <!-- Rows will be added dynamically -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="6">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addItemRow()">
                                                        <i class="fas fa-plus"></i> Thêm dòng
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addDepositAndFirstRent()">
                                                        <i class="fas fa-list"></i> Thêm "Cọc" + "Thuê tháng đầu"
                                                    </button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                    </div>
                </div>
            </div>

            {{-- Card 3: Thông tin tài chính --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Thông tin tài chính
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tổng tiền trước thuế <span class="text-danger">*</span></label>
                                    <input type="text" name="subtotal" id="subtotal" class="form-control money-input @error('subtotal') is-invalid @enderror" 
                                           value="{{ old('subtotal', $prefilledData['subtotal'] ?? '') }}" required onchange="calculateTotal()">
                                    @error('subtotal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Số tiền thuế</label>
                                    <input type="number" name="tax_amount" class="form-control @error('tax_amount') is-invalid @enderror" 
                                           value="{{ old('tax_amount', 0) }}" step="0.01" min="0" onchange="calculateTotal()">
                                    @error('tax_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Số tiền giảm giá</label>
                                    <input type="number" name="discount_amount" class="form-control @error('discount_amount') is-invalid @enderror" 
                                           value="{{ old('discount_amount', 0) }}" step="0.01" min="0" onchange="calculateTotal()">
                                    @error('discount_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tổng tiền thanh toán <span class="text-danger">*</span></label>
                                    <input type="text" name="total_amount" id="total_amount" class="form-control money-input @error('total_amount') is-invalid @enderror" 
                                           value="{{ old('total_amount', $prefilledData['total_amount'] ?? '') }}" required readonly>
                                    @error('total_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 4: Thông tin bổ sung --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-file-alt me-2"></i>Thông tin bổ sung
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                          rows="3" placeholder="Mô tả về hóa đơn">{{ old('description', $prefilledData['description'] ?? '') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea name="note" class="form-control @error('note') is-invalid @enderror" 
                                          rows="3" placeholder="Ghi chú bổ sung">{{ old('note') }}</textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Tài liệu đính kèm</label>
                                <div class="mb-2">
                                    <input type="file" name="attachment" id="attachment" class="form-control @error('attachment') is-invalid @enderror" 
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                    <small class="form-text text-muted">Cho phép: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, GIF (Tối đa 20MB)</small>
                                </div>
                                <div class="mb-2">
                                    <label class="text-muted">Hoặc nhập URL:</label>
                                    <input type="url" name="attachment_url" class="form-control @error('attachment_url') is-invalid @enderror" 
                                           value="{{ old('attachment_url') }}" placeholder="https://example.com/document.pdf">
                                </div>
                                @error('attachment')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('attachment_url')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div id="attachment-preview" class="mt-2" style="display: none;">
                                    <small class="text-success">
                                        <i class="fas fa-check-circle"></i> 
                                        <span id="attachment-name"></span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                        <!-- Hidden fields for source data -->
                        <input type="hidden" name="master_lease_id" id="master-lease-id" value="{{ old('master_lease_id', $selectedMasterLease->id ?? '') }}">
                        <input type="hidden" name="ticket_id" id="ticket-id" value="{{ old('ticket_id', '') }}">
                        <input type="hidden" name="ticket_log_id" id="ticket-log-id" value="{{ old('ticket_log_id', '') }}">
                        <input type="hidden" name="deposit_refund_id" id="deposit-refund-id" value="{{ old('deposit_refund_id', '') }}">
                        <input type="hidden" name="payroll_payslip_id" id="payroll-payslip-id" value="{{ old('payroll_payslip_id', '') }}">
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
                                'type' => 'button',
                                'variant' => 'secondary',
                                'label' => 'Làm mới',
                                'icon' => 'fas fa-undo',
                                'onclick' => 'resetForm()'
                            ],
                            [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Hủy',
                                'icon' => 'fas fa-times',
                                'url' => route('staff.company-invoices.index')
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
<script src="{{ asset('assets/js/number-formatter.js') }}"></script>
<script>
// Calculate total amount
function calculateTotal() {
    // Prefer computing from items if present
    let computedSubtotal = 0;
    const rows = document.querySelectorAll('#items-body tr');
    if (rows.length > 0) {
        rows.forEach(function(row) {
            const qty = parseInt(row.querySelector('.item-qty')?.value) || 1;
            const unit = parseFloat(row.querySelector('.item-unit')?.value) || 0;
            const amount = qty * unit;
            computedSubtotal += amount;
            const amountInput = row.querySelector('.item-amount');
            if (amountInput) {
                amountInput.value = amount.toFixed(2);
            }
        });
        $('input[name="subtotal"]').val(computedSubtotal.toFixed(2));
    }

    const subtotal = parseFloat($('input[name="subtotal"]').val()) || 0;
    const taxAmount = parseFloat($('input[name="tax_amount"]').val()) || 0;
    const discountAmount = parseFloat($('input[name="discount_amount"]').val()) || 0;
    
    const total = subtotal + taxAmount - discountAmount;
    $('input[name="total_amount"]').val(total.toFixed(2));
}

let itemIndex = 0;

function addItemRow(preset) {
    const tbody = document.getElementById('items-body');
    const idx = itemIndex++;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="items[${idx}][item_type]" class="form-control">
                <option value="rent" ${preset?.type === 'rent' ? 'selected' : ''}>Thuê</option>
                <option value="deposit" ${preset?.type === 'deposit' ? 'selected' : ''}>Cọc</option>
                <option value="service" ${preset?.type === 'service' ? 'selected' : ''}>Dịch vụ</option>
                <option value="meter" ${preset?.type === 'meter' ? 'selected' : ''}>Chỉ số</option>
                <option value="ticket_cost" ${preset?.type === 'ticket_cost' ? 'selected' : ''}>Chi phí ticket</option>
                <option value="other" ${!preset || (preset?.type && !['rent','deposit','service','meter','ticket_cost'].includes(preset.type)) ? 'selected' : ''}>Khác</option>
            </select>
        </td>
        <td>
            <input type="text" name="items[${idx}][description]" class="form-control" placeholder="Mô tả" value="${preset?.desc || ''}">
        </td>
        <td>
            <input type="number" step="1" min="1" name="items[${idx}][quantity]" class="form-control text-end item-qty" value="${preset?.qty ?? 1}" oninput="calculateTotal()">
        </td>
        <td>
            <input type="number" step="0.01" min="0" name="items[${idx}][unit_price]" class="form-control text-end item-unit" value="${preset?.unit ?? 0}" oninput="calculateTotal()">
        </td>
        <td>
            <input type="number" step="0.01" min="0" name="items[${idx}][amount]" class="form-control text-end item-amount" value="0" readonly>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); calculateTotal();">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    calculateTotal();
}

function addDepositAndFirstRent() {
    addItemRow({ type: 'deposit', desc: 'Tiền đặt cọc', qty: 1, unit: 0 });
    addItemRow({ type: 'rent', desc: 'Tiền thuê tháng đầu', qty: 1, unit: 0 });
}

// Load source data based on invoice type
function loadSourceData() {
    const invoiceType = $('select[name="invoice_type"]').val();
    const sourceDataGroup = $('#source-data-group');
    const sourceDataSelect = $('#source-data-select');
    
    console.log('loadSourceData called with invoice_type:', invoiceType);
    
    // Clear all source IDs when invoice type changes
    $('#master-lease-id, #ticket-id, #ticket-log-id, #deposit-refund-id, #payroll-payslip-id').val('');
    
    if (!invoiceType || !['master_lease', 'ticket_cost', 'deposit_refund', 'payroll_payslip'].includes(invoiceType)) {
        sourceDataGroup.hide();
        $('#source-data-required').hide();
        sourceDataSelect.removeAttr('required');
        return;
    }
    
    sourceDataGroup.show();
    $('#source-data-required').show();
    sourceDataSelect.attr('required', 'required');
    sourceDataSelect.html('<option value="">Đang tải...</option>');
    
    // Map invoice_type to API parameter
    const apiInvoiceType = invoiceType === 'master_lease' ? 'master_lease' : 
                          invoiceType === 'ticket_cost' ? 'ticket' :
                          invoiceType === 'deposit_refund' ? 'deposit_refund' :
                          invoiceType === 'payroll_payslip' ? 'payroll_payslip' : null;
    
    console.log('Loading source data for type:', apiInvoiceType);
    
    $.ajax({
        url: '{{ route("staff.api.company-invoices.source-data") }}',
        method: 'GET',
        data: { 
            invoice_type: apiInvoiceType
        },
        success: function(response) {
            console.log('Source data response:', response);
            if (response.success && response.data && response.data.length > 0) {
                sourceDataSelect.html('<option value="">Chọn nguồn dữ liệu</option>');
                
                response.data.forEach(function(item) {
                    sourceDataSelect.append(`
                        <option value="${item.id}" data-amount="${item.amount || 0}">
                            ${item.text}
                        </option>
                    `);
                });
                
                // Auto-select master lease if it was pre-selected
                @if(isset($selectedMasterLease) && $selectedMasterLease)
                    const masterLeaseId = {{ $selectedMasterLease->id }};
                    const masterLeaseOption = sourceDataSelect.find(`option[value="${masterLeaseId}"]`);
                    if (masterLeaseOption.length > 0) {
                        sourceDataSelect.val(masterLeaseId);
                        // Trigger change to set master_lease_id
                        sourceDataSelect.trigger('change');
                        console.log('Auto-selected master lease:', masterLeaseId);
                    }
                @endif
            } else {
                sourceDataSelect.html('<option value="">Không có dữ liệu</option>');
                console.warn('No source data available');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading source data:', error, xhr.responseText);
            sourceDataSelect.html('<option value="">Lỗi tải dữ liệu</option>');
        }
    });
}

// Handle source data selection
$('#source-data-select').on('change', function() {
    const selectedOption = $(this).find('option:selected');
    const invoiceType = $('select[name="invoice_type"]').val();
    const amount = parseFloat(selectedOption.data('amount')) || 0;
    const selectedValue = selectedOption.val();
    
    // Source type will be auto-set in controller based on invoice_type
    
    // Clear all source IDs
    $('#master-lease-id, #ticket-id, #ticket-log-id, #deposit-refund-id, #payroll-payslip-id').val('');
    
    // Set appropriate source ID
    switch(invoiceType) {
        case 'master_lease':
            if (selectedValue) {
                $('#master-lease-id').val(selectedValue);
                console.log('Master Lease ID set to:', selectedValue);
            }
            break;
        case 'ticket_cost':
            if (selectedValue) {
                $('#ticket-id').val(selectedValue);
            }
            break;
        case 'deposit_refund':
            if (selectedValue) {
                $('#deposit-refund-id').val(selectedValue);
            }
            break;
        case 'payroll_payslip':
            if (selectedValue) {
                $('#payroll-payslip-id').val(selectedValue);
            }
            break;
    }
    
    // Auto-fill amount if available
    if (amount > 0) {
        $('input[name="subtotal"]').val(amount);
        calculateTotal();
    }
});

// Reset form
function resetForm() {
    if (confirm('Bạn có chắc chắn muốn làm mới form? Tất cả dữ liệu sẽ bị mất.')) {
        document.getElementById('invoice-form').reset();
        $('#source-data-group').hide();
        calculateTotal();
    }
}

// Form validation
$('#invoice-form').on('submit', function(e) {
    // Unformat money inputs before submission
    if (window.NumberFormatter && window.NumberFormatter.getUnformattedValue) {
        const subtotalInput = $('input[name="subtotal"]');
        const totalAmountInput = $('input[name="total_amount"]');
        if (subtotalInput.length) {
            subtotalInput.val(window.NumberFormatter.getUnformattedValue(subtotalInput[0]));
        }
        if (totalAmountInput.length) {
            totalAmountInput.val(window.NumberFormatter.getUnformattedValue(totalAmountInput[0]));
        }
    }
    
    const subtotal = parseFloat($('input[name="subtotal"]').val()) || 0;
    const totalAmount = parseFloat($('input[name="total_amount"]').val()) || 0;
    
    if (subtotal <= 0) {
        e.preventDefault();
        alert('Tổng tiền trước thuế phải lớn hơn 0');
        return false;
    }
    
    if (totalAmount <= 0) {
        e.preventDefault();
        alert('Tổng tiền thanh toán phải lớn hơn 0');
        return false;
    }
    
    // Validate type-specific required fields
    const invoiceType = $('select[name="invoice_type"]').val();
    if (invoiceType === 'deposit_refund') {
        const depositRefundId = $('#deposit-refund-id').val();
        if (!depositRefundId || depositRefundId === '') {
            e.preventDefault();
            alert('Vui lòng chọn deposit refund từ danh sách nguồn dữ liệu.');
            $('#source-data-select').focus();
            return false;
        }
    }
    if (invoiceType === 'master_lease') {
        const masterLeaseId = $('#master-lease-id').val();
        if (!masterLeaseId || masterLeaseId === '') {
            e.preventDefault();
            alert('Vui lòng chọn master lease từ danh sách nguồn dữ liệu.');
            $('#source-data-select').focus();
            return false;
        }
    }
    if (invoiceType === 'ticket_cost') {
        const ticketId = $('#ticket-id').val();
        const ticketLogId = $('#ticket-log-id').val();
        if ((!ticketId || ticketId === '') && (!ticketLogId || ticketLogId === '')) {
            e.preventDefault();
            alert('Vui lòng chọn ticket từ danh sách nguồn dữ liệu.');
            $('#source-data-select').focus();
            return false;
        }
    }
    if (invoiceType === 'payroll_payslip') {
        const payrollPayslipId = $('#payroll-payslip-id').val();
        if (!payrollPayslipId || payrollPayslipId === '') {
            e.preventDefault();
            alert('Vui lòng chọn payroll payslip từ danh sách nguồn dữ liệu.');
            $('#source-data-select').focus();
            return false;
        }
    }
    
    // Show loading
    $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang tạo...');
});

// Initialize
$(document).ready(function() {
    calculateTotal();
    
    // Load source data if invoice type is already selected
    if ($('select[name="invoice_type"]').val()) {
        loadSourceData();
    }

    // Recipient toggle
    function toggleRecipient() {
        const type = $('input[name="recipient_type"]:checked').val();
        if (type === 'vendor') {
            $('#vendorSelect').show();
            $('#userSelect').hide();
            // Clear user selection to avoid posting stale value
            $('#userSelect').val('');
        } else {
            $('#vendorSelect').hide();
            $('#userSelect').show();
            // Clear vendor selection to avoid posting stale value
            $('#vendorSelect').val('');
        }
    }

    $('input[name="recipient_type"]').on('change', toggleRecipient);
    toggleRecipient();

    // Handle file upload preview
    $('#attachment').on('change', function() {
        const file = this.files[0];
        if (file) {
            $('#attachment-name').text(file.name);
            $('#attachment-preview').show();
            // Clear URL input if file is selected
            $('input[name="attachment_url"]').val('');
        } else {
            $('#attachment-preview').hide();
        }
    });
    
    // Clear file input if URL is entered
    $('input[name="attachment_url"]').on('input', function() {
        if ($(this).val()) {
            $('#attachment').val('');
            $('#attachment-preview').hide();
        }
    });
    
    // Auto-load source data if master lease is pre-selected
    @if(isset($selectedMasterLease) && $selectedMasterLease)
        const invoiceType = $('#invoice_type').val();
        if (invoiceType === 'master_lease') {
            // Ensure source data group is visible
            $('#source-data-group').show();
            
            // If source data is already loaded in the select (from server), select it
            const sourceSelect = $('#source-data-select');
            const masterLeaseId = {{ $selectedMasterLease->id }};
            
            // Check if option with master lease ID exists and select it
            const masterLeaseOption = sourceSelect.find(`option[value="${masterLeaseId}"]`);
            if (masterLeaseOption.length > 0) {
                sourceSelect.val(masterLeaseId);
                // Trigger change to set master_lease_id
                sourceSelect.trigger('change');
                console.log('Auto-selected master lease on page load:', masterLeaseId);
            } else {
                // If not loaded yet, load source data first, then select
                loadSourceData();
                // Wait for AJAX to complete, then select
                setTimeout(function() {
                    sourceSelect.val(masterLeaseId);
                    sourceSelect.trigger('change');
                    console.log('Auto-selected master lease after AJAX:', masterLeaseId);
                }, 500);
            }
        }
        
        // Auto-select user if master lease has landlord
        @if($selectedMasterLease->landlord_user_id)
            $('#recipientUser').prop('checked', true);
            toggleRecipient();
            $('#userSelect').val('{{ $selectedMasterLease->landlord_user_id }}');
        @endif
        
        // Auto-fill amount and add item row if prefilled data exists
        @if(isset($prefilledData) && !empty($prefilledData))
            @if(!empty($prefilledData['subtotal']))
                // Format and set subtotal
                const subtotal = {{ $prefilledData['subtotal'] ?? 0 }};
                if (window.NumberFormatter && window.NumberFormatter.setValue) {
                    window.NumberFormatter.setValue('#subtotal', subtotal);
                    window.NumberFormatter.setValue('#total_amount', subtotal);
                } else {
                    $('#subtotal').val(subtotal.toLocaleString('vi-VN').replace(/,/g, '.'));
                    $('#total_amount').val(subtotal.toLocaleString('vi-VN').replace(/,/g, '.'));
                }
                
                // Clear any existing empty rows first
                $('#items-body tr').each(function() {
                    const qty = parseInt($(this).find('.item-qty').val()) || 1;
                    const unit = parseFloat($(this).find('.item-unit').val()) || 0;
                    const desc = $(this).find('input[name*="[description]"]').val() || '';
                    // Remove empty rows (no description and no amount)
                    if (!desc.trim() && qty <= 0 && unit === 0) {
                        $(this).remove();
                    }
                });
                
                // Add item row with master lease rent
                @php
                    $propertyName = $selectedMasterLease->property->name ?? '';
                    $contractNo = $selectedMasterLease->contract_no ?? '';
                    $billingCycleMonths = $selectedMasterLease->billing_cycle ?? 1;
                    $billingCycleLabel = $billingCycleMonths == 1 ? 'Hàng tháng' : "{$billingCycleMonths} tháng";
                    $itemDesc = "Tiền thuê - {$propertyName} - Hợp đồng {$contractNo} ({$billingCycleLabel})";
                @endphp
                addItemRow({
                    type: 'rent',
                    desc: '{{ $itemDesc }}',
                    qty: 1,
                    unit: {{ $prefilledData['subtotal'] ?? 0 }}
                });
                
                // Recalculate total
                calculateTotal();
            @endif
        @endif
    @endif
    
    // If no items exist after all initialization, start with an empty row for convenience
    if (document.querySelectorAll('#items-body tr').length === 0) {
        addItemRow();
    }
});
</script>
@endpush

@push('styles')
<style>
.form-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

hr {
    margin: 2rem 0;
    border-top: 2px solid #dee2e6;
}

.text-danger {
    color: #dc3545 !important;
}

.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}
</style>
@endpush
