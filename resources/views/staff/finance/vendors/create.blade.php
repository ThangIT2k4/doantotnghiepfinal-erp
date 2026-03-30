@extends('layouts.staff_dashboard')

@section('title', 'Thêm Nhà cung cấp')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}">
@endpush

@section('content')
@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notify.success('{{ session('success') }}', 'Thành công!');
        });
    </script>
@endif

@if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notify.error('{{ session('error') }}', 'Lỗi!');
        });
    </script>
@endif

@if(session('warning'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notify.warning('{{ session('warning') }}', 'Cảnh báo!');
        });
    </script>
@endif

@if(session('info'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notify.info('{{ session('info') }}', 'Thông tin');
        });
    </script>
@endif

<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Thêm Nhà cung cấp',
            'subtitle' => 'Tạo nhà cung cấp mới với thông tin đầy đủ',
            'icon' => 'fas fa-building',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.vendors.index')
                ]
            ]
        ])
    
        <!-- Form -->
        <form id="create-vendor-form" method="POST" action="{{ route('staff.vendors.store') }}">
            @csrf
            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Basic Information -->
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
                                        <label for="name" class="form-label">Tên nhà cung cấp <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               id="name" name="name" value="{{ old('name') }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="vendor_type" class="form-label">Loại nhà cung cấp <span class="text-danger">*</span></label>
                                        <select class="form-select @error('vendor_type') is-invalid @enderror" id="vendor_type" name="vendor_type" required>
                                            <option value="">Chọn loại</option>
                                            <option value="company" {{ old('vendor_type') == 'company' ? 'selected' : '' }}>Công ty</option>
                                            <option value="individual" {{ old('vendor_type') == 'individual' ? 'selected' : '' }}>Cá nhân</option>
                                        </select>
                                        @error('vendor_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Mã số thuế</label>
                                        <input type="text" class="form-control @error('tax_code') is-invalid @enderror" 
                                               name="tax_code" value="{{ old('tax_code') }}">
                                        @error('tax_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Giấy phép kinh doanh</label>
                                        <input type="text" class="form-control @error('business_license') is-invalid @enderror" 
                                               name="business_license" value="{{ old('business_license') }}">
                                        @error('business_license')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Hoạt động</option>
                                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Không hoạt động</option>
                                            <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Tạm ngưng</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                               name="phone" value="{{ old('phone') }}">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                               name="email" value="{{ old('email') }}">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Địa chỉ</label>
                                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                                  name="address" rows="3">{{ old('address') }}</textarea>
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-address-book me-2"></i>Thông tin liên hệ
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Người liên hệ</label>
                                        <input type="text" class="form-control @error('contact_person') is-invalid @enderror" 
                                               name="contact_person" value="{{ old('contact_person') }}">
                                        @error('contact_person')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">SĐT liên hệ</label>
                                        <input type="text" class="form-control @error('contact_phone') is-invalid @enderror" 
                                               name="contact_phone" value="{{ old('contact_phone') }}">
                                        @error('contact_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email liên hệ</label>
                                        <input type="email" class="form-control @error('contact_email') is-invalid @enderror" 
                                               name="contact_email" value="{{ old('contact_email') }}">
                                        @error('contact_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Banking Information -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-university me-2"></i>Thông tin ngân hàng (Tích hợp Sepay)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Ngân hàng (SePay)</label>
                                        <select class="form-select @error('sepay_bank_id') is-invalid @enderror" 
                                                name="sepay_bank_id" id="sepayBankSelect">
                                            <option value="">Chọn ngân hàng</option>
                                            @foreach($sepayBanks as $bank)
                                                <option value="{{ $bank->id }}" 
                                                        {{ old('sepay_bank_id') == $bank->id ? 'selected' : '' }}
                                                        data-code="{{ $bank->code }}"
                                                        data-name="{{ $bank->name }}"
                                                        data-short-name="{{ $bank->short_name }}"
                                                        data-bin="{{ $bank->bin }}">
                                                    {{ $bank->name }} ({{ $bank->short_name }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('sepay_bank_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            Chỉ hiển thị các ngân hàng được hỗ trợ bởi SePay
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Số tài khoản</label>
                                        <input type="text" class="form-control @error('account_number') is-invalid @enderror" 
                                               name="account_number" value="{{ old('account_number') }}">
                                        @error('account_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tên chủ tài khoản</label>
                                        <input type="text" class="form-control @error('account_holder_name') is-invalid @enderror" 
                                               name="account_holder_name" value="{{ old('account_holder_name') }}">
                                        @error('account_holder_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tên chi nhánh</label>
                                        <input type="text" class="form-control @error('branch_name') is-invalid @enderror" 
                                               name="branch_name" value="{{ old('branch_name') }}">
                                        @error('branch_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Mã chi nhánh</label>
                                        <input type="text" class="form-control @error('branch_code') is-invalid @enderror" 
                                               name="branch_code" value="{{ old('branch_code') }}">
                                        @error('branch_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Mã SWIFT</label>
                                        <input type="text" class="form-control @error('swift_code') is-invalid @enderror" 
                                               name="swift_code" value="{{ old('swift_code') }}">
                                        @error('swift_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Ghi chú ngân hàng</label>
                                        <textarea class="form-control @error('banking_notes') is-invalid @enderror" 
                                                  name="banking_notes" rows="3">{{ old('banking_notes') }}</textarea>
                                        @error('banking_notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                <!-- Card Thao tác -->
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
                                    'label' => 'Lưu nhà cung cấp',
                                    'icon' => 'fas fa-save'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Hủy',
                                    'icon' => 'fas fa-times',
                                    'url' => route('staff.vendors.index')
                                ]
                            ]
                        ])
                    </div>
                </div>
                
                <!-- Card Hướng dẫn -->
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
                                <li>Tên nhà cung cấp là bắt buộc</li>
                                <li>Chọn loại nhà cung cấp phù hợp</li>
                                <li>Thông tin ngân hàng để tích hợp Sepay</li>
                                <li>Có thể cập nhật sau khi tạo</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
// Initialize SePay bank integration
document.addEventListener('DOMContentLoaded', function() {
    initializeSePayBanks();
});

function initializeSePayBanks() {
    const bankSelect = document.getElementById('sepayBankSelect');
    
    // Add event listener for bank change
    bankSelect.addEventListener('change', handleBankChange);
    
    // Set old value if exists
    const oldBankId = '{{ old("sepay_bank_id") }}';
    if (oldBankId) {
        bankSelect.value = oldBankId;
    }
    
    // Check if we should show preview on page load
    setTimeout(() => {
        handleBankChange();
        
        // Also check if account number is already filled
        const accountNumberInput = document.querySelector('input[name="account_number"]');
        if (accountNumberInput && accountNumberInput.value.trim()) {
            const bankSelect = document.getElementById('sepayBankSelect');
            if (bankSelect.value) {
                const selectedOption = bankSelect.options[bankSelect.selectedIndex];
                if (selectedOption) {
                    const bankCode = selectedOption.getAttribute('data-code');
                    const bankShortName = selectedOption.getAttribute('data-short-name');
                    updateQRPreview(bankCode, bankShortName);
                }
            }
        }
    }, 100);
}

function handleBankChange() {
    const bankSelect = document.getElementById('sepayBankSelect');
    
    if (bankSelect.value) {
        const selectedOption = bankSelect.options[bankSelect.selectedIndex];
        const bankCode = selectedOption.getAttribute('data-code');
        const bankName = selectedOption.getAttribute('data-name');
        const bankShortName = selectedOption.getAttribute('data-short-name');
        const bankBin = selectedOption.getAttribute('data-bin');
        
        // Show QR preview if account number is filled
        updateQRPreview(bankCode, bankShortName);
    } else {
        hideQRPreview();
    }
}

function updateQRPreview(bankCode, bankShortName) {
    const accountNumberInput = document.querySelector('input[name="account_number"]');
    const accountNumber = accountNumberInput ? accountNumberInput.value.trim() : '';
    
    if (bankCode && accountNumber) {
        showQRPreview(bankCode, bankShortName, accountNumber);
    } else {
        hideQRPreview();
    }
}

function showQRPreview(bankCode, bankShortName, accountNumber) {
    // Remove existing QR preview
    hideQRPreview();
    
    // Get bank info from selected option
    const bankSelect = document.getElementById('sepayBankSelect');
    const selectedOption = bankSelect.options[bankSelect.selectedIndex];
    const bankName = selectedOption.getAttribute('data-name');
    const bankBin = selectedOption.getAttribute('data-bin');
    
    // Create QR preview container
    const qrContainer = document.createElement('div');
    qrContainer.id = 'qrPreview';
    qrContainer.className = 'mt-3 p-3 bg-light rounded border';
    qrContainer.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="me-3">
                <i class="fas fa-qrcode fa-2x text-primary"></i>
            </div>
            <div>
                <h6 class="mb-1">QR Thanh toán SePay</h6>
                <small class="text-muted">
                    <strong>${bankShortName}</strong> - ${bankName}<br>
                    TK: ${accountNumber} | BIN: ${bankBin}
                </small>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="showQRInputModal('${bankCode}', '${accountNumber}', '${bankShortName}')">
                        <i class="fas fa-eye"></i> Xem QR Code
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Insert after bank select
    const bankSelectContainer = document.getElementById('sepayBankSelect').closest('.mb-3');
    bankSelectContainer.appendChild(qrContainer);
}

function showQRInputModal(bankCode, accountNumber, bankShortName) {
    // Get bank info from selected option
    const bankSelect = document.getElementById('sepayBankSelect');
    const selectedOption = bankSelect.options[bankSelect.selectedIndex];
    const bankName = selectedOption.getAttribute('data-name');
    
    // Create input modal HTML
    const inputModalHtml = `
        <div class="modal fade" id="qrInputModal" tabindex="-1" aria-labelledby="qrInputModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="qrInputModalLabel">
                            <i class="fas fa-qrcode text-primary"></i>
                            Nhập thông tin QR Code
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <form id="qrInputForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="qrAmount" class="form-label">
                                    Số tiền (VNĐ) <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="qrAmount" 
                                       name="amount" 
                                       value="100000" 
                                       min="1000" 
                                       step="1000" 
                                       required
                                       placeholder="Nhập số tiền">
                                <small class="form-text text-muted">
                                    Số tiền tối thiểu: 1,000 VNĐ
                                </small>
                            </div>
                            <div class="mb-3">
                                <label for="qrDescription" class="form-label">
                                    Nội dung thanh toán
                                </label>
                                <textarea class="form-control" 
                                          id="qrDescription" 
                                          name="description" 
                                          rows="3" 
                                          placeholder="Nhập nội dung thanh toán (tùy chọn)">Thanh toán cho nhà cung cấp</textarea>
                                <small class="form-text text-muted">
                                    Nội dung sẽ hiển thị trên QR Code
                                </small>
                            </div>
                            <div class="alert alert-info mb-0">
                                <small>
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Thông tin ngân hàng:</strong> ${bankName}<br>
                                    <strong>Số tài khoản:</strong> ${accountNumber}
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Hủy
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Tạo QR Code
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing input modal
    const existingInputModal = document.getElementById('qrInputModal');
    if (existingInputModal) {
        const existingModalInstance = bootstrap.Modal.getInstance(existingInputModal);
        if (existingModalInstance) {
            existingModalInstance.hide();
            existingInputModal.addEventListener('hidden.bs.modal', function() {
                existingInputModal.remove();
            }, { once: true });
        } else {
            existingInputModal.remove();
        }
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', inputModalHtml);
    
    // Get the new modal element
    const inputModalElement = document.getElementById('qrInputModal');
    const inputForm = document.getElementById('qrInputForm');
    
    // Handle form submission
    inputForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const amount = document.getElementById('qrAmount').value;
        const description = document.getElementById('qrDescription').value || 'Thanh toán cho nhà cung cấp';
        
        // Validate amount
        if (!amount || parseFloat(amount) < 1000) {
            Notify.error('Số tiền phải lớn hơn hoặc bằng 1,000 VNĐ', 'Lỗi!');
            return;
        }
        
        // Close input modal
        const inputModalInstance = bootstrap.Modal.getInstance(inputModalElement);
        if (inputModalInstance) {
            inputModalInstance.hide();
        }
        
        // Show QR preview modal after input modal is closed
        inputModalElement.addEventListener('hidden.bs.modal', function() {
            showQRPreviewModal(bankCode, accountNumber, bankShortName, amount, description);
        }, { once: true });
    });
    
    // Show modal after a short delay
    setTimeout(() => {
        const modal = new bootstrap.Modal(inputModalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        modal.show();
        
        // Focus on amount input when modal is shown
        inputModalElement.addEventListener('shown.bs.modal', function() {
            const amountInput = document.getElementById('qrAmount');
            if (amountInput) {
                amountInput.focus();
                amountInput.select();
            }
        }, { once: true });
    }, 100);
}

function showQRPreviewModal(bankCode, accountNumber, bankShortName, amount, description) {
    // Get bank info from selected option
    const bankSelect = document.getElementById('sepayBankSelect');
    const selectedOption = bankSelect.options[bankSelect.selectedIndex];
    const bankName = selectedOption.getAttribute('data-name');
    const bankBin = selectedOption.getAttribute('data-bin');
    
    try {
        // Generate QR URL using SePay format
        const qrUrl = `https://qr.sepay.vn/img?acc=${accountNumber}&bank=${bankShortName}&amount=${amount}&des=${encodeURIComponent(description)}`;
        
        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" id="qrPreviewModal" tabindex="-1" aria-labelledby="qrPreviewModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="qrPreviewModalLabel">
                                <i class="fas fa-qrcode text-primary"></i>
                                QR Thanh toán SePay Preview
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body text-center">
                            <div class="mb-3">
                                <div id="qrPreviewCanvas" class="border rounded p-3 bg-white d-inline-block"></div>
                            </div>
                            <div class="row text-start">
                                <div class="col-6">
                                    <small class="text-muted">Ngân hàng:</small><br>
                                    <strong>${bankName}</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Số tài khoản:</small><br>
                                    <strong>${accountNumber}</strong>
                                </div>
                                <div class="col-6 mt-2">
                                    <small class="text-muted">Số tiền:</small><br>
                                    <strong class="text-success">${parseInt(amount).toLocaleString('vi-VN')} VNĐ</strong>
                                </div>
                                <div class="col-6 mt-2">
                                    <small class="text-muted">BIN:</small><br>
                                    <code>${bankBin}</code>
                                </div>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">Nội dung:</small><br>
                                <em>${description}</em>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                            <button type="button" class="btn btn-primary" onclick="downloadPreviewQRCode()">
                                <i class="fas fa-download"></i> Tải xuống
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal properly
        const existingModal = document.getElementById('qrPreviewModal');
        if (existingModal) {
            // Close modal if it's open
            const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
            if (existingModalInstance) {
                existingModalInstance.hide();
                // Wait for modal to finish hiding before removing
                existingModal.addEventListener('hidden.bs.modal', function() {
                    existingModal.remove();
                }, { once: true });
            } else {
            existingModal.remove();
            }
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Get the new modal element
        const newModalElement = document.getElementById('qrPreviewModal');
        
        // Create qrData object with all necessary information
        const qrData = {
            bank_code: bankCode,
            account_number: accountNumber,
            bank_short_name: bankShortName,
            amount: amount,
            description: description
        };
        
        // Show modal after a short delay to ensure DOM is ready
        setTimeout(() => {
            const modal = new bootstrap.Modal(newModalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modal.show();
            
            // Generate QR code after modal is shown
            generatePreviewQRCode(qrData, qrUrl);
            
            // Ensure focus is on the modal when it's shown
            newModalElement.addEventListener('shown.bs.modal', function() {
                // Focus on the close button or first focusable element
                const closeButton = newModalElement.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.focus();
                }
            }, { once: true });
        }, 100);
        
    } catch (error) {
        Notify.error('Không thể tạo QR Code: ' + error.message);
    }
}

function generatePreviewQRCode(qrData, qrUrl) {
    const qrContainer = document.getElementById('qrPreviewCanvas');
    
    // Show loading state
    qrContainer.innerHTML = `
        <div class="d-flex align-items-center justify-content-center" style="height: 200px;">
            <div class="text-center">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div>Đang tạo QR Code...</div>
            </div>
        </div>
    `;
    
    try {
        // Use the provided qrUrl or generate it if not provided
        const finalQrUrl = qrUrl || `https://qr.sepay.vn/img?acc=${qrData.account_number}&bank=${qrData.bank_short_name}&amount=${qrData.amount}&des=${encodeURIComponent(qrData.description)}`;
        
        // Create image element
        const img = document.createElement('img');
        img.src = finalQrUrl;
        img.alt = 'SePay QR Code';
        img.className = 'img-fluid';
        img.style.maxWidth = '200px';
        img.style.maxHeight = '200px';
        
        // Handle image load
        img.onload = function() {
            qrContainer.innerHTML = '';
            qrContainer.appendChild(img);
            
            // Add QR URL info
            const qrInfo = document.createElement('div');
            qrInfo.className = 'mt-2';
            qrInfo.innerHTML = `
                <small class="text-muted">
                    <i class="fas fa-link"></i> 
                    <a href="${finalQrUrl}" target="_blank" class="text-decoration-none">
                        Xem QR Code gốc
                    </a>
                </small>
            `;
            qrContainer.appendChild(qrInfo);
        };
        
        // Handle image error
        img.onerror = function() {
            qrContainer.innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle fa-3x mb-2"></i>
                    <div>Không thể tạo QR Code</div>
                    <small>Vui lòng kiểm tra thông tin ngân hàng</small>
                </div>
            `;
        };
        
    } catch (error) {
        qrContainer.innerHTML = `
            <div class="text-center text-danger">
                <i class="fas fa-exclamation-triangle fa-3x mb-2"></i>
                <div>Lỗi tạo QR Code</div>
                <small>${error.message}</small>
            </div>
        `;
    }
}

function downloadPreviewQRCode() {
    const img = document.querySelector('#qrPreviewCanvas img');
    if (img) {
        // Create a canvas to convert image to downloadable format
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = img.naturalWidth || 200;
        canvas.height = img.naturalHeight || 200;
        
        // Draw image to canvas
        ctx.drawImage(img, 0, 0);
        
        // Create download link
        const link = document.createElement('a');
        link.download = `sepay-qr-preview-${Date.now()}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    } else {
        Notify.error('Không tìm thấy QR Code để tải xuống');
    }
}

function hideQRPreview() {
    const existingQR = document.getElementById('qrPreview');
    if (existingQR) {
        existingQR.remove();
    }
}

// Listen for account number changes
document.addEventListener('DOMContentLoaded', function() {
    const accountNumberInput = document.querySelector('input[name="account_number"]');
    if (accountNumberInput) {
        // Use both 'input' and 'change' events for better compatibility
        accountNumberInput.addEventListener('input', function() {
            updateQRPreviewFromInputs();
        });
        
        accountNumberInput.addEventListener('change', function() {
            updateQRPreviewFromInputs();
        });
        
        // Also trigger on paste
        accountNumberInput.addEventListener('paste', function() {
            setTimeout(() => {
                updateQRPreviewFromInputs();
            }, 10);
        });
    }
});

function updateQRPreviewFromInputs() {
    const bankSelect = document.getElementById('sepayBankSelect');
    if (bankSelect && bankSelect.value) {
        const selectedOption = bankSelect.options[bankSelect.selectedIndex];
        if (selectedOption) {
            const bankCode = selectedOption.getAttribute('data-code');
            const bankShortName = selectedOption.getAttribute('data-short-name');
            if (bankCode && bankShortName) {
                updateQRPreview(bankCode, bankShortName);
            }
        }
    } else {
        hideQRPreview();
    }
}

document.getElementById('create-vendor-form').addEventListener('submit', function(e) {
    // Show loading notification
    const loadingToast = Notify.toast({
        title: 'Đang xử lý...',
        message: 'Vui lòng chờ trong giây lát',
        type: 'info',
        duration: 0,
        showProgress: false
    });
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
    submitBtn.disabled = true;
    
    // Re-enable button after 10 seconds as fallback
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        // Hide loading toast
        const toast = document.getElementById(loadingToast);
        if (toast) {
            const bsToast = bootstrap.Toast.getInstance(toast);
            if (bsToast) {
                bsToast.hide();
            }
        }
    }, 10000);
});
</script>
@endpush
@endsection
