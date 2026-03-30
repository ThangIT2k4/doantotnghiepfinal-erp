@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa Hóa đơn Công ty')

@section('content')
<main class="main-content">
<div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" và "Xem chi tiết" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Sửa thông tin Hóa đơn Công ty',
            'subtitle' => 'Cập nhật thông tin hóa đơn: ' . ($companyInvoice->invoice_no ?? 'Hóa đơn #' . $companyInvoice->id),
            'icon' => 'fas fa-user-edit',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.company-invoices.index')
                ],
                [
                    'variant' => 'info',           // ✅ Solid
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.company-invoices.show', $companyInvoice)
                ]
            ]
        ])

        {{-- Display success/error messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Có lỗi xảy ra:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- 2. Form với Layout Full Width (cho form dài) --}}
                <form id="invoice-form" method="POST" action="{{ route('staff.company-invoices.update', $companyInvoice) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
            
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
                                            <input class="form-check-input" type="radio" name="recipient_type" id="recipientVendor" value="vendor" {{ old('recipient_type', $companyInvoice->vendor_id ? 'vendor' : ($companyInvoice->user_id ? 'user' : 'vendor')) == 'vendor' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="recipientVendor">Nhà cung cấp</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="recipient_type" id="recipientUser" value="user" {{ old('recipient_type', $companyInvoice->user_id ? 'user' : '') == 'user' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="recipientUser">Người dùng</label>
                                        </div>
                                    </div>
                                    <select name="vendor_id" id="vendorSelect" class="form-control mb-2 @error('vendor_id') is-invalid @enderror" {{ old('recipient_type', $companyInvoice->vendor_id ? 'vendor' : 'vendor') == 'vendor' ? '' : 'style=display:none;' }}>
                                        <option value="">Chọn nhà cung cấp</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" {{ old('vendor_id', $companyInvoice->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vendor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <select name="user_id" id="userSelect" class="form-control @error('user_id') is-invalid @enderror" {{ old('recipient_type', $companyInvoice->user_id ? 'user' : '') == 'user' ? '' : 'style=display:none;' }}>
                                        <option value="">Chọn người dùng</option>
                                        @isset($users)
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ old('user_id', $companyInvoice->user_id) == $user->id ? 'selected' : '' }}>
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
                                @php
                                    $hasSourceData = $companyInvoice->master_lease_id || $companyInvoice->ticket_log_id || $companyInvoice->ticket_id || $companyInvoice->deposit_refund_id || $companyInvoice->payroll_payslip_id;
                                @endphp
                                
                                @if($hasSourceData)
                                    {{-- Readonly khi đã có source data --}}
                                    <input type="text" class="form-control bg-light" value="{{ $types[$companyInvoice->invoice_type] ?? $companyInvoice->invoice_type }}" readonly>
                                    <input type="hidden" name="invoice_type" value="{{ $companyInvoice->invoice_type }}">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Loại hóa đơn không thể thay đổi
                                    </small>
                                @else
                                    {{-- Dropdown khi chưa có source data --}}
                                    <select name="invoice_type" id="invoice_type" class="form-select @error('invoice_type') is-invalid @enderror" required onchange="loadSourceData()">
                                        <option value="">Chọn loại hóa đơn</option>
                                        @foreach($types as $key => $label)
                                            <option value="{{ $key }}" {{ old('invoice_type', $companyInvoice->invoice_type) == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('invoice_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                                </div>

                            <div class="mb-3" id="source-data-group" style="display: {{ in_array($companyInvoice->invoice_type, ['master_lease', 'ticket_cost', 'deposit_refund', 'payroll_payslip']) ? 'block' : 'none' }};">
                                <label class="form-label">Nguồn dữ liệu <span class="text-danger" id="source-data-required" style="display: {{ in_array($companyInvoice->invoice_type, ['master_lease', 'ticket_cost', 'deposit_refund', 'payroll_payslip']) ? 'inline' : 'none' }};">*</span></label>
                                
                                @php
                                    $hasSourceData = $companyInvoice->master_lease_id || $companyInvoice->ticket_log_id || $companyInvoice->ticket_id || $companyInvoice->deposit_refund_id || $companyInvoice->payroll_payslip_id;
                                    $sourceDisplay = '';
                                    $sourceValue = '';
                                    
                                    if ($companyInvoice->master_lease_id) {
                                        $sourceDisplay = ($companyInvoice->masterLease->contract_no ?? 'Hợp đồng #' . $companyInvoice->master_lease_id) . ' - ' . ($companyInvoice->masterLease->property->name ?? 'N/A');
                                        $sourceValue = $companyInvoice->master_lease_id;
                                    } elseif ($companyInvoice->ticket_log_id && $companyInvoice->ticketLog) {
                                        $sourceDisplay = 'Ticket #' . ($companyInvoice->ticketLog->ticket_id ?? $companyInvoice->ticket_id) . ' - ' . ($companyInvoice->ticketLog->action ?? 'Chi phí ticket') . ' - ' . number_format($companyInvoice->ticketLog->cost_amount ?? 0, 0, ',', '.') . ' VND';
                                        $sourceValue = $companyInvoice->ticket_log_id;
                                    } elseif ($companyInvoice->ticket_id && $companyInvoice->ticket) {
                                        $sourceDisplay = 'Ticket #' . $companyInvoice->ticket_id . ' - ' . ($companyInvoice->ticket->title ?? 'Chi phí ticket');
                                        $sourceValue = $companyInvoice->ticket_id;
                                    } elseif ($companyInvoice->deposit_refund_id) {
                                        $sourceDisplay = 'Deposit Refund #' . $companyInvoice->deposit_refund_id;
                                        $sourceValue = $companyInvoice->deposit_refund_id;
                                    } elseif ($companyInvoice->payroll_payslip_id) {
                                        $sourceDisplay = 'Payroll Payslip #' . $companyInvoice->payroll_payslip_id;
                                        $sourceValue = $companyInvoice->payroll_payslip_id;
                                    }
                                @endphp
                                
                                @if($hasSourceData)
                                    {{-- Hiển thị readonly khi đã có source data --}}
                                    <input type="text" class="form-control bg-light" value="{{ $sourceDisplay }}" readonly>
                                    <input type="hidden" name="source_id" value="{{ $sourceValue }}">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Nguồn dữ liệu không thể thay đổi sau khi tạo
                                    </small>
                                @else
                                    {{-- Dropdown khi chưa có source data (create mode) --}}
                                    <select name="source_id" class="form-select @error('deposit_refund_id') is-invalid @enderror @error('master_lease_id') is-invalid @enderror @error('ticket_id') is-invalid @enderror @error('payroll_payslip_id') is-invalid @enderror" id="source-data-select">
                                        <option value="">Chọn nguồn dữ liệu</option>
                                    </select>
                                @endif
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
                                <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                        <option value="draft" {{ old('status', $companyInvoice->status) == 'draft' ? 'selected' : '' }}>Nháp</option>
                                        <option value="pending" {{ old('status', $companyInvoice->status) == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                                        <option value="approved" {{ old('status', $companyInvoice->status) == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                                        <option value="paid" {{ old('status', $companyInvoice->status) == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                                        <option value="overdue" {{ old('status', $companyInvoice->status) == 'overdue' ? 'selected' : '' }}>Quá hạn</option>
                                        <option value="cancelled" {{ old('status', $companyInvoice->status) == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ngày phát hành <span class="text-danger">*</span></label>
                                    <input type="date" name="issue_date" class="form-control @error('issue_date') is-invalid @enderror" 
                                           value="{{ old('issue_date', $companyInvoice->issue_date->format('Y-m-d')) }}" required>
                                    @error('issue_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            <div class="mb-3">
                                <label class="form-label">Ngày đến hạn <span class="text-danger">*</span></label>
                                    <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" 
                                           value="{{ old('due_date', $companyInvoice->due_date->format('Y-m-d')) }}" required>
                                    @error('due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            <div class="mb-3">
                                <label class="form-label">Đơn vị tiền tệ</label>
                                <select name="currency" class="form-select @error('currency') is-invalid @enderror">
                                        <option value="VND" {{ old('currency', $companyInvoice->currency) == 'VND' ? 'selected' : '' }}>VND</option>
                                        <option value="USD" {{ old('currency', $companyInvoice->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                                        <option value="EUR" {{ old('currency', $companyInvoice->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
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
                                @foreach($companyInvoice->items as $index => $item)
                                <tr>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                        <select name="items[{{ $index }}][item_type]" class="form-control">
                                            <option value="rent" {{ $item->item_type == 'rent' ? 'selected' : '' }}>Thuê</option>
                                            <option value="deposit" {{ $item->item_type == 'deposit' ? 'selected' : '' }}>Cọc</option>
                                            <option value="service" {{ $item->item_type == 'service' ? 'selected' : '' }}>Dịch vụ</option>
                                            <option value="meter" {{ $item->item_type == 'meter' ? 'selected' : '' }}>Chỉ số</option>
                                            <option value="ticket_cost" {{ $item->item_type == 'ticket_cost' ? 'selected' : '' }}>Chi phí ticket</option>
                                            <option value="other" {{ $item->item_type == 'other' ? 'selected' : '' }}>Khác</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][description]" class="form-control" placeholder="Mô tả" value="{{ $item->description }}">
                                    </td>
                                    <td>
                                        <input type="number" step="1" min="1" name="items[{{ $index }}][quantity]" class="form-control text-end item-qty" value="{{ (int)$item->quantity }}" oninput="calculateTotal()">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][unit_price]" class="form-control text-end item-unit money-input" value="{{ number_format($item->unit_price, 0, ',', '.') }}" oninput="calculateTotal()">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][amount]" class="form-control text-end item-amount money-input" value="{{ number_format($item->amount, 0, ',', '.') }}" readonly>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); calculateTotal();">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
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
                                       value="{{ old('subtotal', number_format($companyInvoice->subtotal, 0, ',', '.')) }}" required onchange="calculateTotal()">
                                    @error('subtotal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Số tiền thuế</label>
                                    <input type="text" name="tax_amount" id="tax_amount" class="form-control money-input @error('tax_amount') is-invalid @enderror" 
                                           value="{{ old('tax_amount', number_format($companyInvoice->tax_amount ?? 0, 0, ',', '.')) }}" onchange="calculateTotal()">
                                    @error('tax_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Số tiền giảm giá</label>
                                    <input type="text" name="discount_amount" id="discount_amount" class="form-control money-input @error('discount_amount') is-invalid @enderror" 
                                           value="{{ old('discount_amount', number_format($companyInvoice->discount_amount ?? 0, 0, ',', '.')) }}" onchange="calculateTotal()">
                                    @error('discount_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tổng tiền thanh toán <span class="text-danger">*</span></label>
                                <input type="text" name="total_amount" id="total_amount" class="form-control money-input @error('total_amount') is-invalid @enderror" 
                                       value="{{ old('total_amount', number_format($companyInvoice->total_amount, 0, ',', '.')) }}" required readonly>
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
                                              rows="3" placeholder="Mô tả về hóa đơn">{{ old('description', $companyInvoice->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                    <textarea name="note" class="form-control @error('note') is-invalid @enderror" 
                                              rows="3" placeholder="Ghi chú bổ sung">{{ old('note', $companyInvoice->note) }}</textarea>
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
                                        @if($companyInvoice->attachment_url)
                                            <div class="mt-2">
                                                <small class="text-muted">Tài liệu hiện tại:</small>
                                                <a href="{{ $companyInvoice->attachment_url }}" target="_blank" class="ms-2">
                                                    <i class="fas fa-external-link-alt"></i> {{ $companyInvoice->attachment_url }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="mb-2">
                                        <label class="text-muted">Hoặc nhập URL:</label>
                                        <input type="url" name="attachment_url" class="form-control @error('attachment_url') is-invalid @enderror" 
                                               value="{{ old('attachment_url', $companyInvoice->attachment_url) }}" placeholder="https://example.com/document.pdf">
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
                        </div>
                    </div>

                <!-- Hidden fields for source data -->
                <input type="hidden" name="master_lease_id" id="master-lease-id" value="{{ old('master_lease_id', $companyInvoice->master_lease_id ?? '') }}">
                <input type="hidden" name="ticket_id" id="ticket-id" value="{{ old('ticket_id', $companyInvoice->ticket_id ?? '') }}">
                <input type="hidden" name="ticket_log_id" id="ticket-log-id" value="{{ old('ticket_log_id', $companyInvoice->ticket_log_id ?? '') }}">
                <input type="hidden" name="deposit_refund_id" id="deposit-refund-id" value="{{ old('deposit_refund_id', $companyInvoice->deposit_refund_id ?? '') }}">
                <input type="hidden" name="payroll_payslip_id" id="payroll-payslip-id" value="{{ old('payroll_payslip_id', $companyInvoice->payroll_payslip_id ?? '') }}">

            {{-- Card 5: Thông tin hiện tại (nếu cần) --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                    </h6>
                </div>
                <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Ngày tạo:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-calendar-plus me-1 text-muted"></i>
                                    {{ $companyInvoice->created_at->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Cập nhật lần cuối:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-calendar-edit me-1 text-muted"></i>
                                    {{ $companyInvoice->updated_at->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                        </div>
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
                                'label' => 'Cập nhật hóa đơn',
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
                                'variant' => 'info',
                                'label' => 'Xem chi tiết',
                                'icon' => 'fas fa-eye',
                                'url' => route('staff.company-invoices.show', $companyInvoice)
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
            
            // Get unit price (unformat if needed)
            let unit = 0;
            const unitInput = row.querySelector('.item-unit');
            if (unitInput) {
                if (window.NumberFormatter && window.NumberFormatter.getUnformattedValue) {
                    unit = parseFloat(window.NumberFormatter.getUnformattedValue(unitInput)) || 0;
                } else {
                    unit = parseFloat(unitInput.value.replace(/\./g, '').replace(',', '.')) || 0;
                }
            }
            
            const amount = qty * unit;
            computedSubtotal += amount;
            
            // Update amount input with formatted value
            const amountInput = row.querySelector('.item-amount');
            if (amountInput) {
                if (window.NumberFormatter && window.NumberFormatter.setValue) {
                    window.NumberFormatter.setValue(amountInput, amount);
                } else {
                    amountInput.value = amount.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                }
            }
        });
        // Update subtotal with computed value
        if (window.NumberFormatter && window.NumberFormatter.setValue) {
            window.NumberFormatter.setValue('#subtotal', computedSubtotal);
        } else {
            $('input[name="subtotal"]').val(computedSubtotal.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 0 }));
        }
    }

    // Get subtotal (unformat if needed)
    let subtotal = 0;
    if (window.NumberFormatter && window.NumberFormatter.getUnformattedValue) {
        const subtotalInput = document.querySelector('#subtotal');
        if (subtotalInput) {
            subtotal = parseFloat(window.NumberFormatter.getUnformattedValue(subtotalInput)) || 0;
        }
    } else {
        subtotal = parseFloat($('input[name="subtotal"]').val()) || 0;
    }
    
    // Get tax amount (unformat if needed)
    let taxAmount = 0;
    if (window.NumberFormatter && window.NumberFormatter.getUnformattedValue) {
        const taxAmountInput = document.querySelector('#tax_amount');
        if (taxAmountInput) {
            taxAmount = parseFloat(window.NumberFormatter.getUnformattedValue(taxAmountInput)) || 0;
        }
    } else {
        taxAmount = parseFloat($('input[name="tax_amount"]').val()) || 0;
    }
    
    // Get discount amount (unformat if needed)
    let discountAmount = 0;
    if (window.NumberFormatter && window.NumberFormatter.getUnformattedValue) {
        const discountAmountInput = document.querySelector('#discount_amount');
        if (discountAmountInput) {
            discountAmount = parseFloat(window.NumberFormatter.getUnformattedValue(discountAmountInput)) || 0;
        }
    } else {
        discountAmount = parseFloat($('input[name="discount_amount"]').val()) || 0;
    }
    
    const total = subtotal + taxAmount - discountAmount;
    
    // Format total amount
    if (window.NumberFormatter && window.NumberFormatter.setValue) {
        window.NumberFormatter.setValue('#total_amount', total);
    } else {
        $('input[name="total_amount"]').val(total.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 0 }));
    }
}

let itemIndex = {{ $companyInvoice->items->count() }};

function addItemRow(preset) {
    const tbody = document.getElementById('items-body');
    const idx = itemIndex++;
    const tr = document.createElement('tr');
    
    // Format unit price for display
    const unitValue = preset?.unit ?? 0;
    const formattedUnit = unitValue.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    
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
            <input type="text" name="items[${idx}][unit_price]" class="form-control text-end item-unit money-input" value="${formattedUnit}" oninput="calculateTotal()">
        </td>
        <td>
            <input type="text" name="items[${idx}][amount]" class="form-control text-end item-amount money-input" value="0" readonly>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); calculateTotal();">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    
    // Initialize number formatter for new row
    if (window.NumberFormatter && window.NumberFormatter.init) {
        const row = tbody.lastElementChild;
        const unitInput = row.querySelector('.item-unit');
        const amountInput = row.querySelector('.item-amount');
        if (unitInput) window.NumberFormatter.init(unitInput);
        if (amountInput) window.NumberFormatter.init(amountInput);
    }
    
    calculateTotal();
}

function addDepositAndFirstRent() {
    addItemRow({ type: 'deposit', desc: 'Tiền đặt cọc', qty: 1, unit: 0 });
    addItemRow({ type: 'rent', desc: 'Tiền thuê tháng đầu', qty: 1, unit: 0 });
}

// Reset form
function resetForm() {
    if (confirm('Bạn có chắc chắn muốn làm mới form? Tất cả thay đổi sẽ bị mất.')) {
        document.getElementById('invoice-form').reset();
        calculateTotal();
    }
}

// Form submission - simple submit with number unformatting (giống như file create)
$('#invoice-form').on('submit', function(e) {
    // Sync source data to hidden fields before submission
    syncSourceDataToHiddenFields();
    
    // Unformat money inputs before submission (giống như file create)
    if (window.NumberFormatter && window.NumberFormatter.getUnformattedValue) {
        const subtotalInput = $('input[name="subtotal"]');
        const totalAmountInput = $('input[name="total_amount"]');
        if (subtotalInput.length) {
            subtotalInput.val(window.NumberFormatter.getUnformattedValue(subtotalInput[0]));
        }
        if (totalAmountInput.length) {
            totalAmountInput.val(window.NumberFormatter.getUnformattedValue(totalAmountInput[0]));
        }
        
        // Unformat tax_amount and discount_amount
        const taxAmountInput = $('input[name="tax_amount"]');
        const discountAmountInput = $('input[name="discount_amount"]');
        if (taxAmountInput.length) {
            taxAmountInput.val(window.NumberFormatter.getUnformattedValue(taxAmountInput[0]));
        }
        if (discountAmountInput.length) {
            discountAmountInput.val(window.NumberFormatter.getUnformattedValue(discountAmountInput[0]));
        }
        
        // Unformat item inputs
        $('input[name*="[unit_price]"]').each(function() {
            if (this.classList.contains('money-input')) {
                $(this).val(window.NumberFormatter.getUnformattedValue(this));
            }
        });
        $('input[name*="[amount]"]').each(function() {
            if (this.classList.contains('money-input')) {
                $(this).val(window.NumberFormatter.getUnformattedValue(this));
            }
        });
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
    $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...');
});

// Load source data based on invoice type
function loadSourceData() {
    const invoiceType = $('select[name="invoice_type"]').val();
    const sourceDataGroup = $('#source-data-group');
    const sourceDataSelect = $('#source-data-select');
    
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
    
    // Get current source ID from hidden fields BEFORE loading (to preserve it)
    const currentMasterLeaseId = $('#master-lease-id').val();
    const currentTicketId = $('#ticket-id').val();
    const currentTicketLogId = $('#ticket-log-id').val();
    const currentDepositRefundId = $('#deposit-refund-id').val();
    const currentPayrollPayslipId = $('#payroll-payslip-id').val();
    
    let currentSourceId = null;
    if (invoiceType === 'master_lease' && currentMasterLeaseId) {
        currentSourceId = currentMasterLeaseId;
    } else if (invoiceType === 'ticket_cost' && currentTicketId) {
        currentSourceId = currentTicketId;
    } else if (invoiceType === 'ticket_cost' && currentTicketLogId) {
        currentSourceId = currentTicketLogId;
    } else if (invoiceType === 'deposit_refund' && currentDepositRefundId) {
        currentSourceId = currentDepositRefundId;
    } else if (invoiceType === 'payroll_payslip' && currentPayrollPayslipId) {
        currentSourceId = currentPayrollPayslipId;
    }
    
    // Also check if dropdown already has a selected value
    const existingSelectedValue = sourceDataSelect.val();
    if (!currentSourceId && existingSelectedValue) {
        currentSourceId = existingSelectedValue;
    }
    
    sourceDataSelect.html('<option value="">Đang tải...</option>');
    
    const apiInvoiceType = invoiceType === 'master_lease' ? 'master_lease' : 
                          invoiceType === 'ticket_cost' ? 'ticket' :
                          invoiceType === 'deposit_refund' ? 'deposit_refund' :
                          invoiceType === 'payroll_payslip' ? 'payroll_payslip' : null;
    
    $.ajax({
        url: '{{ route("staff.api.company-invoices.source-data") }}',
        method: 'GET',
        data: { invoice_type: apiInvoiceType },
        success: function(response) {
            if (response.success && response.data && response.data.length > 0) {
                sourceDataSelect.html('<option value="">Chọn nguồn dữ liệu</option>');
                response.data.forEach(function(item) {
                    const isSelected = currentSourceId && item.id == currentSourceId;
                    sourceDataSelect.append(`
                        <option value="${item.id}" data-amount="${item.amount || 0}" ${isSelected ? 'selected' : ''}>
                            ${item.text}
                        </option>
                    `);
                });
                
                // Ensure the value is selected and synced
                if (currentSourceId) {
                    sourceDataSelect.val(currentSourceId);
                    // Sync to hidden field after setting dropdown value
                    syncSourceDataToHiddenFields();
                }
            } else {
                sourceDataSelect.html('<option value="">Không có dữ liệu</option>');
            }
        },
        error: function() {
            sourceDataSelect.html('<option value="">Lỗi tải dữ liệu</option>');
        }
    });
}

// Function to sync source data select to hidden fields
function syncSourceDataToHiddenFields() {
    const selectedOption = $('#source-data-select').find('option:selected');
    const invoiceType = $('select[name="invoice_type"]').val();
    const selectedValue = selectedOption.val();
    
    // Clear all source IDs first
    $('#master-lease-id, #ticket-id, #ticket-log-id, #deposit-refund-id, #payroll-payslip-id').val('');
    
    // Set appropriate source ID
    if (selectedValue) {
        switch(invoiceType) {
            case 'master_lease':
                $('#master-lease-id').val(selectedValue);
                console.log('Master Lease ID set to:', selectedValue);
                break;
            case 'ticket_cost':
                $('#ticket-id').val(selectedValue);
                break;
            case 'deposit_refund':
                $('#deposit-refund-id').val(selectedValue);
                break;
            case 'payroll_payslip':
                $('#payroll-payslip-id').val(selectedValue);
                break;
        }
    }
}

// Handle source data selection
$('#source-data-select').on('change', function() {
    syncSourceDataToHiddenFields();
});

// Initialize
$(document).ready(function() {
    calculateTotal();
    
    // First, sync existing dropdown value to hidden field immediately (if dropdown has value from server)
    const sourceSelect = $('#source-data-select');
    const existingDropdownValue = sourceSelect.val();
    if (existingDropdownValue) {
        syncSourceDataToHiddenFields();
    }
    
    // Load source data if invoice type requires it
    const invoiceType = $('#invoice_type').val();
    if (invoiceType && ['master_lease', 'ticket_cost', 'deposit_refund', 'payroll_payslip'].includes(invoiceType)) {
        // Load source data (it will preserve and select the current source ID)
        loadSourceData();
    } else {
        // Even if not loading source data, sync existing values
        syncSourceDataToHiddenFields();
    }
    
    // Also sync on page load after a short delay to ensure DOM is ready
    setTimeout(function() {
        syncSourceDataToHiddenFields();
    }, 100);
    
    // Initialize number formatter for money inputs
    if (window.NumberFormatter && window.NumberFormatter.init) {
        window.NumberFormatter.init('#subtotal');
        window.NumberFormatter.init('#tax_amount');
        window.NumberFormatter.init('#discount_amount');
        window.NumberFormatter.init('#total_amount');
        
        // Initialize number formatter for existing item inputs
        document.querySelectorAll('#items-body .item-unit, #items-body .item-amount').forEach(function(input) {
            window.NumberFormatter.init(input);
        });
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
    
    // If no items exist, start with an empty row for convenience
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
