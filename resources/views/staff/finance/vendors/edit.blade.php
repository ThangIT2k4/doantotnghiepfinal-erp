@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa Nhà cung cấp')

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
            'title' => 'Chỉnh sửa Nhà cung cấp',
            'subtitle' => 'Cập nhật thông tin nhà cung cấp: ' . $vendor->name,
            'icon' => 'fas fa-building',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.vendors.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.vendors.show', $vendor->id)
                ]
            ]
        ])
    
        <!-- Form -->
        <form id="edit-vendor-form" method="POST" action="{{ route('staff.vendors.update', $vendor->id) }}">
            @csrf
            @method('PUT')
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
                                        <label class="form-label">Tên nhà cung cấp <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               name="name" value="{{ old('name', $vendor->name) }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Loại nhà cung cấp <span class="text-danger">*</span></label>
                                        <select class="form-select @error('vendor_type') is-invalid @enderror" name="vendor_type" required>
                                            <option value="">Chọn loại</option>
                                            <option value="company" {{ old('vendor_type', $vendor->vendor_type) == 'company' ? 'selected' : '' }}>Công ty</option>
                                            <option value="individual" {{ old('vendor_type', $vendor->vendor_type) == 'individual' ? 'selected' : '' }}>Cá nhân</option>
                                        </select>
                                        @error('vendor_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Mã số thuế</label>
                                        <input type="text" class="form-control @error('tax_code') is-invalid @enderror" 
                                               name="tax_code" value="{{ old('tax_code', $vendor->tax_code) }}">
                                        @error('tax_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Giấy phép kinh doanh</label>
                                        <input type="text" class="form-control @error('business_license') is-invalid @enderror" 
                                               name="business_license" value="{{ old('business_license', $vendor->business_license) }}">
                                        @error('business_license')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                                            <option value="active" {{ old('status', $vendor->status) == 'active' ? 'selected' : '' }}>Hoạt động</option>
                                            <option value="inactive" {{ old('status', $vendor->status) == 'inactive' ? 'selected' : '' }}>Không hoạt động</option>
                                            <option value="suspended" {{ old('status', $vendor->status) == 'suspended' ? 'selected' : '' }}>Tạm ngưng</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                               name="phone" value="{{ old('phone', $vendor->phone) }}">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                               name="email" value="{{ old('email', $vendor->email) }}">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Địa chỉ</label>
                                        <textarea class="form-control @error('address') is-invalid @enderror" 
                                                  name="address" rows="3">{{ old('address', $vendor->address) }}</textarea>
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
                                               name="contact_person" value="{{ old('contact_person', $vendor->contact_person) }}">
                                        @error('contact_person')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">SĐT liên hệ</label>
                                        <input type="text" class="form-control @error('contact_phone') is-invalid @enderror" 
                                               name="contact_phone" value="{{ old('contact_phone', $vendor->contact_phone) }}">
                                        @error('contact_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email liên hệ</label>
                                        <input type="email" class="form-control @error('contact_email') is-invalid @enderror" 
                                               name="contact_email" value="{{ old('contact_email', $vendor->contact_email) }}">
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
                                                        {{ old('sepay_bank_id', $vendor->sepay_bank_id) == $bank->id ? 'selected' : '' }}
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
                                               name="account_number" value="{{ old('account_number', $vendor->account_number) }}">
                                        @error('account_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Tên chủ tài khoản</label>
                                        <input type="text" class="form-control @error('account_holder_name') is-invalid @enderror" 
                                               name="account_holder_name" value="{{ old('account_holder_name', $vendor->account_holder_name) }}">
                                        @error('account_holder_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tên chi nhánh</label>
                                        <input type="text" class="form-control @error('branch_name') is-invalid @enderror" 
                                               name="branch_name" value="{{ old('branch_name', $vendor->branch_name) }}">
                                        @error('branch_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Mã chi nhánh</label>
                                        <input type="text" class="form-control @error('branch_code') is-invalid @enderror" 
                                               name="branch_code" value="{{ old('branch_code', $vendor->branch_code) }}">
                                        @error('branch_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Mã SWIFT</label>
                                        <input type="text" class="form-control @error('swift_code') is-invalid @enderror" 
                                               name="swift_code" value="{{ old('swift_code', $vendor->swift_code) }}">
                                        @error('swift_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Ghi chú ngân hàng</label>
                                        <textarea class="form-control @error('banking_notes') is-invalid @enderror" 
                                                  name="banking_notes" rows="3">{{ old('banking_notes', $vendor->banking_notes) }}</textarea>
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
                                    'label' => 'Cập nhật',
                                    'icon' => 'fas fa-save'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Hủy',
                                    'icon' => 'fas fa-times',
                                    'url' => route('staff.vendors.show', $vendor->id)
                                ]
                            ]
                        ])
                    </div>
                </div>

                <!-- Vendor Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin nhà cung cấp
                        </h6>
                    </div>
                    <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">ID:</span>
                                    <strong>#{{ $vendor->id }}</strong>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Trạng thái hiện tại:</span>
                                    <span class="badge {{ $vendor->status_badge_class }}">{{ $vendor->status_label }}</span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Loại:</span>
                                    <span class="badge bg-info">{{ $vendor->vendor_type_label }}</span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Tạo lúc:</span>
                                    <strong>{{ $vendor->created_at->format('d/m/Y H:i') }}</strong>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Cập nhật lần cuối:</span>
                                    <strong>{{ $vendor->updated_at->format('d/m/Y H:i') }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- Help -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-question-circle me-2"></i>Hướng dẫn
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Lưu ý khi chỉnh sửa</h6>
                            <ul class="mb-0 small">
                                <li>Thông tin ngân hàng ảnh hưởng đến Sepay</li>
                                <li>Thay đổi trạng thái có thể ảnh hưởng đến thanh toán</li>
                                <li>Lưu lại để áp dụng thay đổi</li>
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
    
    // Set current value if exists
    const currentBankId = '{{ $vendor->sepay_bank_id }}';
    if (currentBankId) {
        bankSelect.value = currentBankId;
        handleBankChange();
    }
    
    // Set old value if exists (for validation errors)
    const oldBankId = '{{ old("sepay_bank_id") }}';
    if (oldBankId) {
        bankSelect.value = oldBankId;
        handleBankChange();
    }
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
    const accountNumber = document.querySelector('input[name="account_number"]').value;
    
    if (bankCode && accountNumber) {
        showQRPreview(bankCode, bankShortName, accountNumber);
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
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="showQRPreviewModal('${bankCode}', '${accountNumber}', '${bankShortName}')">
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

function showQRPreviewModal(bankCode, accountNumber, bankShortName) {
    const amount = prompt('Nhập số tiền để tạo QR Code (VNĐ):') || '100000';
    const description = prompt('Nhập nội dung thanh toán:') || 'Thanh toán cho nhà cung cấp';
    
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
            <div class="modal fade" id="qrPreviewModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-qrcode text-primary"></i>
                                QR Thanh toán SePay Preview
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
        
        // Remove existing modal
        const existingModal = document.getElementById('qrPreviewModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('qrPreviewModal'));
        modal.show();
        
        // Generate QR code
        generatePreviewQRCode(qrUrl);
        
    } catch (error) {
        Notify.error('Không thể tạo QR Code: ' + error.message);
    }
}

function generatePreviewQRCode(qrUrl) {
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
        // Create image element
        const img = document.createElement('img');
        img.src = qrUrl;
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
                    <a href="${qrUrl}" target="_blank" class="text-decoration-none">
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
        accountNumberInput.addEventListener('input', function() {
            const bankSelect = document.getElementById('sepayBankSelect');
            if (bankSelect.value) {
                const selectedOption = bankSelect.options[bankSelect.selectedIndex];
                const bankCode = selectedOption.getAttribute('data-code');
                const bankShortName = selectedOption.getAttribute('data-short-name');
                updateQRPreview(bankCode, bankShortName);
            }
        });
    }
});

document.getElementById('edit-vendor-form').addEventListener('submit', function(e) {
    // Show loading notification
    const loadingToast = Notify.toast({
        title: 'Đang cập nhật...',
        message: 'Vui lòng chờ trong giây lát',
        type: 'info',
        duration: 0,
        showProgress: false
    });
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...';
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
