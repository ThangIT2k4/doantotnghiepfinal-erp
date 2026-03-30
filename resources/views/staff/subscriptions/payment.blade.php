@extends('layouts.staff_dashboard')

@section('title', 'Thanh toán Đăng ký')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}">
<style>
/* Container */
.payment-methods-container {
    max-width: 100%;
    padding: 20px;
    background: #f8fafc;
    min-height: 100vh;
}

/* Page Header */
.payment-header {
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 20px 25px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.payment-header-left h3 {
    font-size: 1.5em;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 5px 0;
}

.payment-header-left p {
    color: #64748b;
    font-size: 0.95em;
    margin: 0;
}

.payment-header-right {
    text-align: right;
}

.payment-header-right .invoice-code {
    font-size: 0.9em;
    color: #64748b;
    display: block;
    margin-bottom: 5px;
}

.payment-header-right .invoice-amount {
    font-size: 1.8em;
    font-weight: 700;
    color: #0d6efd;
}

/* Main Layout Grid */
.payment-main-layout {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

/* Left Sidebar */
.payment-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Invoice Summary Card */
.invoice-summary {
    background: white;
    border-radius: 12px;
    padding: 25px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.invoice-summary h6 {
    font-size: 1.1em;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
}

.summary-item {
    padding: 12px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
}

.summary-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.summary-item-label {
    font-size: 0.9em;
    color: #64748b;
    font-weight: 500;
}

.summary-item-value {
    font-size: 1em;
    color: #1e293b;
    font-weight: 600;
}

.summary-item.total {
    background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
    padding: 15px;
    border-radius: 10px;
    margin-top: 10px;
}

.summary-item.total .summary-item-label,
.summary-item.total .summary-item-value {
    color: white;
}

/* Payment Methods - Vertical List */
.payment-methods-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.payment-methods-section h6 {
    font-size: 1.1em;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
}

.payment-methods {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* Method Card - Horizontal Layout */
.method-card {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 18px 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
}

.method-card:hover {
    border-color: #0d6efd;
    background: white;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
}

.method-card.selected {
    border-color: #0d6efd;
    background: linear-gradient(135deg, #e7f1ff 0%, #f0f7ff 100%);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}

.method-card.selected::before {
    content: '✓';
    position: absolute;
    top: 50%;
    right: 20px;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    background: #0d6efd;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1em;
    font-weight: bold;
}

/* Method Icon */
.method-icon {
    font-size: 2.2em;
    color: #0d6efd;
    flex-shrink: 0;
    width: 50px;
    text-align: center;
}

.method-card.selected .method-icon {
    color: #0056b3;
}

/* Method Content */
.method-content {
    flex: 1;
}

/* Method Title */
.method-title {
    font-size: 1.1em;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 3px;
}

/* Method Description */
.method-description {
    color: #64748b;
    font-size: 0.85em;
    line-height: 1.4;
    margin: 0;
}

/* Main Content Area */
.payment-content {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Sepay Info Section */
.sepay-info {
    display: none;
    background: white;
    border-radius: 12px;
    padding: 25px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.sepay-info h6 {
    font-size: 1.1em;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e2e8f0;
}

/* Bank Info Layout */
.bank-info-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 25px;
    align-items: start;
}

/* Bank Items Grid */
.bank-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

/* Bank Item */
.bank-item {
    background: #f8fafc;
    padding: 15px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.bank-item:hover {
    border-color: #0d6efd;
    background: #e7f1ff;
}

.bank-item strong {
    display: block;
    color: #64748b;
    font-size: 0.85em;
    font-weight: 600;
    margin-bottom: 5px;
}

.bank-item span {
    color: #1e293b;
    font-size: 1em;
    font-weight: 600;
}

/* QR Code Container */
#qrCodeContainer {
    text-align: center;
    padding: 20px;
    background: #f8fafc;
    border-radius: 10px;
    border: 2px dashed #cbd5e1;
    min-height: 280px;
    display: flex;
    align-items: center;
    justify-content: center;
}

#qrCodeContainer img {
    max-width: 250px;
    height: auto;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
    background: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Payment Actions */
.payment-actions {
    background: white;
    border-radius: 12px;
    padding: 20px 25px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    align-items: center;
}

/* Buttons */
.btn-back {
    background: white;
    color: #475569;
    border: 2px solid #e2e8f0;
    padding: 12px 28px;
    border-radius: 8px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-back:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}

.btn-pay {
    background: #0d6efd;
    color: white;
    border: 2px solid #0d6efd;
    padding: 12px 35px;
    border-radius: 8px;
    font-size: 1em;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

.btn-pay:hover:not(:disabled) {
    background: #0056b3;
    border-color: #0056b3;
    box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4);
}

.btn-pay:disabled {
    background: #94a3b8;
    border-color: #94a3b8;
    cursor: not-allowed;
    opacity: 0.7;
    box-shadow: none;
}

/* Loading Spinner */
#bankLoading {
    text-align: center;
    padding: 20px;
    color: #0d6efd;
    font-weight: 600;
}

#bankLoading i {
    margin-right: 10px;
    font-size: 1.2em;
}

/* Alert */
.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 4px solid;
}

.alert-info {
    background: #e7f1ff;
    border-left-color: #0d6efd;
    color: #084298;
}

/* Responsive */
@media (max-width: 1200px) {
    .payment-main-layout {
        grid-template-columns: 350px 1fr;
    }
    
    .bank-info-layout {
        grid-template-columns: 1fr;
    }
    
    #qrCodeContainer {
        margin-top: 20px;
    }
}

@media (max-width: 992px) {
    .payment-main-layout {
        grid-template-columns: 1fr;
    }
    
    .payment-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .payment-header-right {
        text-align: left;
    }
    
    .bank-info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .payment-methods-container {
        padding: 15px;
    }
    
    .payment-actions {
        flex-direction: column;
    }
    
    .btn-back,
    .btn-pay {
        width: 100%;
    }
}
</style>
@endpush

@section('content')
<div class="payment-methods-container">
    <!-- Page Header -->
    <div class="payment-header">
        <div class="payment-header-left">
            <h3><i class="fas fa-credit-card me-2"></i>Thanh toán đăng ký</h3>
            <p>Chọn phương thức thanh toán phù hợp với bạn</p>
        </div>
        <div class="payment-header-right">
            <span class="invoice-code">{{ $invoice->invoice_number }}</span>
            <div class="invoice-amount">{{ number_format($invoice->amount, 0, ',', '.') }} {{ $invoice->currency }}</div>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="payment-main-layout">
        <!-- Left Sidebar -->
        <div class="payment-sidebar">
            <!-- Invoice Summary -->
            <div class="invoice-summary">
                <h6><i class="fas fa-file-invoice me-2"></i>Thông tin hóa đơn</h6>
                <div class="summary-item">
                    <span class="summary-item-label">Số hóa đơn</span>
                    <span class="summary-item-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-item-label">Gói dịch vụ</span>
                    <span class="summary-item-value">{{ $invoice->subscription->plan->name ?? 'N/A' }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-item-label">Trạng thái</span>
                    <span class="summary-item-value">
                        @if($invoice->status === 'pending')
                            <span class="badge bg-warning">Chờ thanh toán</span>
                        @elseif($invoice->status === 'paid')
                            <span class="badge bg-success">Đã thanh toán</span>
                        @else
                            <span class="badge bg-secondary">{{ $invoice->getStatusLabel() }}</span>
                        @endif
                    </span>
                </div>
                <div class="summary-item">
                    <span class="summary-item-label">Ngày tạo</span>
                    <span class="summary-item-value">{{ $invoice->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-item-label">Hạn thanh toán</span>
                    <span class="summary-item-value">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}</span>
                </div>
                <div class="summary-item total">
                    <span class="summary-item-label">Tổng tiền</span>
                    <span class="summary-item-value">{{ number_format($invoice->amount, 0, ',', '.') }} {{ $invoice->currency }}</span>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="payment-methods-section">
                <h6><i class="fas fa-wallet me-2"></i>Phương thức thanh toán</h6>
                <div class="payment-methods">
                    <div class="method-card" data-method="cash" id="methodCash">
                        <div class="method-icon"><i class="fas fa-money-bill-wave"></i></div>
                        <div class="method-content">
                            <div class="method-title">Tiền mặt</div>
                            <div class="method-description">Thanh toán trực tiếp tại văn phòng</div>
                        </div>
                    </div>
                    <div class="method-card" data-method="sepay" id="methodSepay">
                        <div class="method-icon"><i class="fas fa-university"></i></div>
                        <div class="method-content">
                            <div class="method-title">Chuyển khoản</div>
                            <div class="method-description">Thanh toán qua QR Code hoặc ngân hàng</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="payment-content">
            <!-- Sepay Information -->
            <div class="sepay-info" id="sepayInfo">
                <h6><i class="fas fa-info-circle me-2"></i>Thông tin chuyển khoản</h6>
                <div id="bankLoading" style="display:none">
                    <i class="fas fa-spinner fa-spin"></i> Đang tải thông tin ngân hàng...
                </div>
                <div class="bank-info-layout">
                    <div id="bankInfo" class="bank-info-grid"></div>
                    <div id="qrCodeContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Upload Section -->
    <div class="sepay-info" id="documentSection" style="display: none;">
        <h6><i class="fas fa-file-upload me-2"></i>Tài liệu thanh toán (tùy chọn)</h6>
        <div class="bank-info-layout">
            <div class="bank-info-grid">
                <div class="bank-item" style="grid-column: 1 / -1;">
                    <strong>Tải lên tài liệu:</strong>
                    <input type="file" name="document" id="document" class="form-control mt-2" 
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" style="padding: 8px;">
                    <small class="form-text text-muted d-block mt-2">
                        Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF - Tối đa 20MB
                    </small>
                    <div id="document-preview" class="mt-2" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-file"></i> <span id="document-name"></span>
                            <button type="button" class="btn btn-sm btn-danger float-right" onclick="removeDocument()">
                                <i class="fas fa-times"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Actions -->
    <div class="payment-actions">
        <button class="btn-back" id="btnBack">
            <i class="fas fa-arrow-left me-2"></i>Quay lại
        </button>
        <button id="payButton" class="btn-pay" disabled>
            <i class="fas fa-check-circle me-2"></i>Xác nhận thanh toán
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
var selectedMethod = null;
var currentPaymentId = null;
var bankInfoCache = null;

// Expose all handlers globally
window.selectPaymentMethod = function(method) {
    document.querySelectorAll('.method-card').forEach(function(card) {
        card.classList.remove('selected');
    });
    
    var methodCard = document.querySelector('[data-method="' + method + '"]');
    if (methodCard) {
        methodCard.classList.add('selected');
    }
    
    selectedMethod = method;
    var payButton = document.getElementById('payButton');
    if (payButton) {
        payButton.disabled = false;
    }
    
    var sepayInfo = document.getElementById('sepayInfo');
    if (!sepayInfo) return;
    
    if (method === 'sepay') {
        sepayInfo.style.display = 'block';
        loadBankInfo();
    } else {
        sepayInfo.style.display = 'none';
    }
    
    var docSection = document.getElementById('documentSection');
    if (docSection) {
        docSection.style.display = 'block';
    }
}

function loadBankInfo() {
    // Use bank config from server (SaaS organization bank account)
    var bankInfo = {
        bank_name: '{{ $bankConfig['bank_name'] ?? 'TPBank' }}',
        account_number: '{{ $bankConfig['account_number'] ?? '46166378666' }}',
        account_holder_name: '{{ $bankConfig['account_name'] ?? 'Le Xuan Thanh Quan' }}',
        branch: '{{ $bankConfig['branch'] ?? 'Chi nhánh Hà Nội' }}',
        bank_short_name: '{{ $bankConfig['bank_name'] ?? 'TPBank' }}'
    };
    
    bankInfoCache = bankInfo;
    renderBank(bankInfo);
    renderQR(bankInfo);
    
    var payButton = document.getElementById('payButton');
    if (payButton) {
        payButton.disabled = false;
    }
}

function renderBank(info) {
    var bankInfoEl = document.getElementById('bankInfo');
    if (!bankInfoEl) return;
    
    var html = '';
    html += '<div class="bank-item"><strong>Ngân hàng:</strong> <span>' + (info.bank_name || 'N/A') + '</span></div>';
    html += '<div class="bank-item"><strong>Số TK:</strong> <span>' + (info.account_number || 'N/A') + '</span></div>';
    html += '<div class="bank-item"><strong>Chủ TK:</strong> <span>' + (info.account_holder_name || info.account_name || 'N/A') + '</span></div>';
    html += '<div class="bank-item"><strong>Số tiền:</strong> <span>{{ number_format($invoice->amount, 0, ',', '.') }} {{ $invoice->currency }}</span></div>';
    html += '<div class="bank-item"><strong>Nội dung:</strong> <span>{{ $content }}</span></div>';
    
    bankInfoEl.innerHTML = html;
}

function renderQR(info) {
    var qrContainer = document.getElementById('qrCodeContainer');
    if (!qrContainer) return;
    
    if (!info.account_number || !info.bank_short_name) {
        qrContainer.innerHTML = '';
        return;
    }
    
    var amount = {{ (int)$invoice->amount }};
    var invoiceNo = '{{ $invoice->invoice_number }}';
    var content = '{{ $content }}';
    
    var qs = new URLSearchParams({
        acc: info.account_number,
        bank: info.bank_short_name,
        amount: amount,
        des: content
    });
    
    var url = 'https://qr.sepay.vn/img?' + qs.toString();
    qrContainer.innerHTML = '<img src="' + url + '" alt="QR Code" onerror="showQRError()">';
}

window.processPayment = function() {
    if (!selectedMethod) {
        Notify.warning('Vui lòng chọn phương thức', 'Cảnh báo');
        return;
    }
    
    var payButton = document.getElementById('payButton');
    if (payButton) {
        payButton.disabled = true;
    }
    
    var url = selectedMethod === 'cash' 
        ? '/staff/api/subscriptions/payment/{{ $invoice->id }}/cash'
        : '/staff/api/subscriptions/payment/{{ $invoice->id }}/sepay';
    
    // Create FormData to support file upload
    var formData = new FormData();
    var docInput = document.getElementById('document');
    if (docInput && docInput.files.length > 0) {
        formData.append('document', docInput.files[0]);
    }
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {
        if (data && data.success) {
            currentPaymentId = data.payment_id || data.invoice_id;
            Notify.success('Tạo thanh toán thành công. Đang chuyển hướng...', 'Thành công!');
            setTimeout(function() {
                window.location.href = '{{ route("staff.subscriptions.invoices.show", $invoice) }}';
            }, 1500);
        } else {
            Notify.error((data && data.message) || 'Lỗi tạo thanh toán', 'Lỗi');
            if (payButton) {
                payButton.disabled = false;
            }
        }
    })
    .catch(function(error) {
        Notify.error('Có lỗi khi xử lý thanh toán', 'Lỗi');
        if (payButton) {
            payButton.disabled = false;
        }
    });
}

function removeDocument() {
    var docInput = document.getElementById('document');
    if (docInput) {
        docInput.value = '';
    }
    var preview = document.getElementById('document-preview');
    if (preview) {
        preview.style.display = 'none';
    }
}

window.goBack = function() {
    window.history.back();
}

window.showQRError = function() {
    var qrContainer = document.getElementById('qrCodeContainer');
    if (qrContainer) {
        qrContainer.innerHTML = '<div class="alert alert-warning">Không thể tải QR</div>';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var cashMethod = document.getElementById('methodCash');
    var sepayMethod = document.getElementById('methodSepay');
    var backButton = document.getElementById('btnBack');
    var payButton = document.getElementById('payButton');
    
    if (cashMethod) {
        cashMethod.addEventListener('click', function() {
            window.selectPaymentMethod('cash');
        });
    }
    
    if (sepayMethod) {
        sepayMethod.addEventListener('click', function() {
            window.selectPaymentMethod('sepay');
        });
    }
    
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.goBack();
        });
    }
    
    if (payButton) {
        payButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.processPayment();
        });
    }
    
    // Handle document upload preview
    var docInput = document.getElementById('document');
    if (docInput) {
        docInput.addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                var preview = document.getElementById('document-preview');
                var name = document.getElementById('document-name');
                if (preview) {
                    preview.style.display = 'block';
                }
                if (name) {
                    name.textContent = file.name;
                }
            } else {
                var preview = document.getElementById('document-preview');
                if (preview) {
                    preview.style.display = 'none';
                }
            }
        });
    }
});
</script>
@endpush
