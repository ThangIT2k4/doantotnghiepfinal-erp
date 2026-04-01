@extends('layouts.app')

@section('title', 'Chọn phương thức thanh toán')

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
.payment-content-blue {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 1rem;
    overflow-x: visible;
    width: 100%;
}

.invoice-summary-blue {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
}

.invoice-summary-blue h4 {
    color: var(--blue-primary);
    margin-bottom: 1.5rem;
    font-weight: 700;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #F5F5F5;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-item.total {
    border-top: 2px solid var(--blue-border);
    padding-top: 1rem;
    margin-top: 1rem;
    font-weight: 700;
    font-size: 1.2em;
    color: var(--blue-primary);
}

.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

/* Đảm bảo tất cả các card hiển thị trên mobile */
@media (max-width: 576px) {
    .payment-methods {
        grid-template-columns: 1fr !important;
    }
}

.method-card-blue {
    border: 2px solid var(--blue-border);
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    position: relative;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    z-index: 1;
    display: block;
    width: 100%;
}

.method-card-blue::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: var(--blue-gradient);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.method-card-blue:hover {
    border-color: var(--blue-primary);
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.2);
}

.method-card-blue:hover::before {
    transform: scaleX(1);
}

.method-card-blue.selected {
    border-color: var(--blue-primary);
    background: var(--blue-bg-light);
    border-width: 3px;
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.3);
}

.method-card-blue.selected::before {
    transform: scaleX(1);
    height: 5px;
}

.method-card-blue.selected::after {
    content: '✓';
    position: absolute;
    top: 15px;
    right: 15px;
    background: var(--blue-primary);
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2em;
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.4);
}

.method-icon-blue {
    font-size: 3.5em;
    margin-bottom: 1rem;
    color: var(--blue-primary);
    transition: all 0.3s ease;
}

.method-card-blue:hover .method-icon-blue {
    transform: scale(1.1);
}

.method-title-blue {
    font-size: 1.4em;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: var(--blue-primary);
}

.method-description-blue {
    color: #666;
    margin-bottom: 1rem;
    line-height: 1.6;
    font-size: 0.95rem;
}

.method-features-blue {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
}

.method-features-blue li {
    padding: 0.5rem 0;
    color: #555;
    font-size: 0.9em;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.method-features-blue li::before {
    content: '✓';
    color: var(--blue-primary);
    font-weight: bold;
    font-size: 1.1em;
}

.payment-actions-blue {
    text-align: center;
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-pay-blue {
    background: var(--blue-gradient);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 12px;
    font-size: 1.1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 200px;
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.3);
}

.btn-pay-blue:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(39, 102, 236, 0.4);
}

.btn-pay-blue:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-back-blue {
    background: white;
    color: var(--blue-primary);
    border: 2px solid var(--blue-border);
    padding: 0.75rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-back-blue:hover {
    background: var(--blue-bg-light);
    border-color: var(--blue-primary);
    transform: translateY(-2px);
}

.loading-blue {
    display: none;
    text-align: center;
    margin: 2rem 0;
}

.spinner-blue {
    border: 3px solid var(--blue-bg-light);
    border-top: 3px solid var(--blue-primary);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.sepay-info-blue {
    background: white;
    border: 1px solid var(--blue-border);
    border-radius: 16px;
    padding: 2rem;
    margin-top: 2rem;
    margin-bottom: 2rem;
    display: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.sepay-info-blue h5 {
    color: var(--blue-primary);
    margin-bottom: 1.5rem;
    font-weight: 700;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.bank-info-blue {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.bank-item-blue {
    background: var(--blue-bg-light);
    padding: 1.25rem;
    border-radius: 12px;
    border: 1px solid var(--blue-border);
}

.bank-item-blue strong {
    display: block;
    color: var(--blue-primary);
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
}

.bank-item-blue span {
    color: #333;
    font-family: monospace;
    font-size: 1rem;
    font-weight: 600;
}

.qr-code-container-blue {
    text-align: center;
    margin-top: 1.5rem;
    display: block;
    min-height: 50px;
}

.qr-code-blue {
    border: 1px solid var(--blue-border);
    border-radius: 16px;
    padding: 2rem;
    background: white;
    display: inline-block;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.qr-code-blue h6 {
    color: var(--blue-primary);
    margin-bottom: 1rem;
    font-weight: 600;
}

.image-upload-section-blue {
    background: white;
    border: 1px solid var(--blue-border);
    border-radius: 16px;
    padding: 1.5rem;
    margin-top: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.image-upload-section-blue h6 {
    color: var(--blue-primary);
    margin-bottom: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-blue {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    border: 1px solid var(--blue-border);
}

.alert-info-blue {
    background: var(--blue-bg-light);
    border-color: var(--blue-light);
    color: var(--blue-dark);
}

.alert-warning-blue {
    background: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

@media (max-width: 768px) {
    .payment-content-blue {
        padding: 0 0.5rem;
    }
    
    .payment-methods {
        grid-template-columns: 1fr !important;
        gap: 1rem;
    }
    
    .bank-info-blue {
        grid-template-columns: 1fr;
    }
    
    .invoice-summary-blue {
        padding: 1.5rem;
    }
    
    .method-card-blue {
        padding: 1.5rem;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        min-height: auto;
    }
    
    .payment-actions-blue {
        flex-direction: column;
    }
    
    .btn-back-blue, .btn-pay-blue {
        width: 100%;
    }
    
    .qr-code-blue img {
        max-width: 100% !important;
    }
    
    .sepay-info-blue {
        padding: 1rem;
    }
}
</style>
@endpush

@section('content')
<div class="page-container-blue" style="overflow-x: hidden; width: 100%;">
    <div class="container" style="overflow-x: hidden;">
        <div class="payment-content-blue">
            <!-- Page Header -->
            @include('tenant.components.page-header', [
                'title' => 'Chọn phương thức thanh toán',
                'subtitle' => 'Thanh toán hóa đơn #' . ($invoice->invoice_no ?? 'HD' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT)),
                'icon' => 'fas fa-credit-card',
                'actions' => [
                    ['label' => 'Quay lại', 'url' => route('tenant.invoices.index'), 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary']
                ],
                // 'breadcrumbs' => [
                //     ['label' => 'Dashboard', 'url' => route('tenant.dashboard')],
                //     ['label' => 'Hóa đơn', 'url' => route('tenant.invoices.index')],
                //     ['label' => 'Thanh toán', 'active' => true]
                // ]
            ])

            <!-- Invoice Summary -->
            <div class="invoice-summary-blue">
                <h4><i class="fas fa-file-invoice-dollar"></i>Thông tin hóa đơn</h4>
        <div class="row">
            <div class="col-md-6">
                <div class="summary-item">
                    <span>Mã hóa đơn:</span>
                    <span><strong>{{ $invoice->invoice_no ?? 'HD' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</strong></span>
                </div>
                <div class="summary-item">
                    <span>Phòng:</span>
                    <span>{{ $invoice->lease->unit->property->name }} - {{ $invoice->lease->unit->code }}</span>
                </div>
                <div class="summary-item">
                    <span>Kỳ thanh toán:</span>
                    <span>Tháng {{ $invoice->issue_date->format('m/Y') }}</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="summary-item">
                    <span>Ngày tạo:</span>
                    <span>{{ $invoice->issue_date->format('d/m/Y') }}</span>
                </div>
                <div class="summary-item">
                    <span>Hạn thanh toán:</span>
                    <span>{{ $invoice->due_date->format('d/m/Y') }}</span>
                </div>
                <div class="summary-item total">
                    <span>Tổng tiền:</span>
                    <span>{{ number_format($invoice->total_amount) }} VNĐ</span>
                </div>
            </div>
        </div>
    </div>

                <!-- Payment Methods -->
                <h4 class="mb-4" style="color: var(--blue-primary); font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-credit-card"></i>Chọn phương thức thanh toán
                </h4>
                
                <div class="payment-methods">
                    <!-- Cash Payment -->
                    <div class="method-card-blue" data-method="cash" onclick="selectPaymentMethod('cash')">
                        <div class="method-icon-blue">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="method-title-blue">Thanh toán tiền mặt</div>
                        <div class="method-description-blue">
                            Thanh toán trực tiếp bằng tiền mặt tại văn phòng
                        </div>
                        <ul class="method-features-blue">
                            <li>Không cần tài khoản ngân hàng</li>
                            <li>Xác nhận nhanh chóng</li>
                            <li>Phù hợp với người cao tuổi</li>
                        </ul>
                    </div>

                    <!-- Bank QR Payment - Chuyển khoản trực tuyến -->
                    @if($orgBankConfig)
                    <div class="method-card-blue" data-method="bank_qr" onclick="selectPaymentMethod('bank_qr')">
                        <div class="method-icon-blue">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="method-title-blue">Chuyển khoản trực tuyến</div>
                        <div class="method-description-blue">
                            Chuyển vào ngân hàng tổ chức - chờ xác nhận thủ công
                        </div>
                        <ul class="method-features-blue">
                            <li>Chuyển vào tài khoản tổ chức</li>
                            <li>Chờ xác nhận từ nhân viên</li>
                            <li>Thanh toán 24/7</li>
                        </ul>
                    </div>
                    @endif

                    <!-- Sepay Payment - Tự động cập nhật -->
                    {{-- Debug: canUseSepay = {{ var_export($canUseSepay ?? null, true) }}, orgBankConfig = {{ var_export($orgBankConfig ? true : false, true) }} --}}
                    <script>
                        console.log('Sepay availability check:', {
                            canUseSepay: {{ isset($canUseSepay) && $canUseSepay ? 'true' : 'false' }},
                            orgBankConfig: {{ $orgBankConfig ? 'true' : 'false' }},
                            viewport_width: window.innerWidth
                        });
                    </script>
                    @if(isset($canUseSepay) && $canUseSepay)
                    <div class="method-card-blue" data-method="sepay" onclick="selectPaymentMethod('sepay')">
                        <div class="method-icon-blue">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="method-title-blue">Chuyển khoản qua SePay</div>
                        <div class="method-description-blue">
                            Tự động cập nhật ngay khi chuyển khoản
                        </div>
                        <ul class="method-features-blue">
                            <li>Xác nhận tự động tức thì</li>
                            <li>Thanh toán 24/7</li>
                            <li>An toàn và nhanh chóng</li>
                        </ul>
                    </div>
                    @endif

                    <!-- Thông báo: Nếu không có phương thức chuyển khoản nào -->
                    @if(!$orgBankConfig && (!isset($canUseSepay) || !$canUseSepay))
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Lưu ý:</strong> Hiện tại không có phương thức chuyển khoản nào khả dụng. 
                        Vui lòng sử dụng phương thức thanh toán tiền mặt hoặc liên hệ với quản lý để được hỗ trợ.
                    </div>
                    @endif
                </div>

                <!-- Image Upload Section -->
                <div class="image-upload-section-blue">
                    <h6><i class="fas fa-image"></i>Ảnh tài liệu đối chiếu (tùy chọn)</h6>
                    <div class="form-group">
                        <input type="file" name="image" id="paymentImage" 
                               class="form-control" 
                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                               style="border: 2px solid var(--blue-border); border-radius: 12px; padding: 0.75rem;">
                        <small class="form-text text-muted mt-2 d-block">Hỗ trợ: JPEG, PNG, JPG, GIF, WebP (tối đa 5MB). Tải lên ảnh biên lai/chứng từ thanh toán để nhân viên tra soát.</small>
                        <div id="imagePreview" class="mt-3" style="display: none;">
                            <img id="previewImg" src="" alt="Preview" style="max-width: 300px; max-height: 300px; border: 2px solid var(--blue-border); border-radius: 12px; padding: 10px; background: white;">
                        </div>
                    </div>
                </div>

                <!-- Sepay Information (Hidden by default) -->
                <div class="sepay-info-blue" id="sepayInfo">
                    <h5><i class="fas fa-info-circle"></i>Thông tin chuyển khoản</h5>
                    <div class="alert alert-info-blue">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Hướng dẫn:</strong> Quét mã QR hoặc chuyển khoản theo thông tin bên dưới. 
                        Nội dung chuyển khoản phải chính xác để hệ thống tự động xác nhận.
                    </div>
                    
                    <div class="bank-info-blue" id="bankInfo">
                        <!-- Bank info will be loaded here -->
                    </div>
                    
                    <div class="qr-code-container-blue" id="qrCodeContainer">
                        <!-- QR code will be loaded here -->
                        <div id="qrFallback" style="display: none;">
                            <div class="alert alert-warning-blue">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Không thể tải QR Code</h6>
                                <p>Vui lòng thử lại hoặc liên hệ hỗ trợ.</p>
                                <button class="btn btn-sm btn-outline-primary" onclick="retryQRCode()" style="margin-top: 0.5rem;">
                                    <i class="fas fa-refresh me-1"></i>Thử lại
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div class="loading-blue" id="loading">
                    <div class="spinner-blue"></div>
                    <p style="color: var(--blue-primary); font-weight: 600;">Đang xử lý thanh toán...</p>
                </div>

                <!-- Payment Actions -->
                <div class="payment-actions-blue">
                    <button class="btn-back-blue" onclick="goBack()">
                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                    </button>
                    <button class="btn-pay-blue" id="payButton" onclick="processPayment()" disabled>
                        <i class="fas fa-credit-card me-2"></i>Xác nhận thanh toán
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 10px 40px rgba(39, 102, 236, 0.3);">
            <div class="modal-header" style="background: var(--blue-gradient); color: white; border: none; padding: 2rem; text-align: center; display: block;">
                <div class="success-icon mb-3" style="position: relative; z-index: 1;">
                    <div style="width: 100px; height: 100px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; backdrop-filter: blur(10px); border: 3px solid rgba(255, 255, 255, 0.3);">
                        <i class="fas fa-check-circle" style="font-size: 3.5em; color: white; text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);"></i>
                    </div>
                </div>
                <h4 style="color: white; font-weight: 700; font-size: 1.8rem; margin: 0; text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); position: relative; z-index: 1;">Thanh toán thành công!</h4>
            </div>
            <div class="modal-body text-center" style="padding: 2rem;">
                <p id="successMessage" style="color: #666; font-size: 1.1rem; margin-bottom: 2rem;">Yêu cầu thanh toán đã được tạo thành công.</p>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <button class="btn" onclick="viewPaymentStatus()" style="background: var(--blue-gradient); color: white; border: none; border-radius: 12px; padding: 0.75rem 2rem; font-weight: 600; box-shadow: 0 4px 15px rgba(39, 102, 236, 0.3); transition: all 0.3s ease; min-width: 180px;">
                        <i class="fas fa-eye me-2"></i>Xem trạng thái
                    </button>
                    <button class="btn" onclick="goToInvoices()" style="background: white; color: var(--blue-primary); border: 2px solid var(--blue-border); border-radius: 12px; padding: 0.75rem 2rem; font-weight: 600; transition: all 0.3s ease; min-width: 180px;">
                        <i class="fas fa-list me-2"></i>Danh sách hóa đơn
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let selectedMethod = null;
let currentPaymentId = null;

// Debug: Log payment methods on load
document.addEventListener('DOMContentLoaded', function() {
    const methods = document.querySelectorAll('.method-card-blue');
    console.log('Payment methods found:', methods.length);
    methods.forEach((method, index) => {
        const methodType = method.getAttribute('data-method');
        const isVisible = window.getComputedStyle(method).display !== 'none';
        console.log(`Method ${index + 1}: ${methodType}, Visible: ${isVisible}`);
    });
});

function selectPaymentMethod(method) {
    // Remove previous selection
    document.querySelectorAll('.method-card-blue').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selection to clicked card
    document.querySelector(`[data-method="${method}"]`).classList.add('selected');
    
    selectedMethod = method;
    document.getElementById('payButton').disabled = false;
    
    // Show/hide Sepay info (for both bank_qr and sepay)
    const sepayInfo = document.getElementById('sepayInfo');
    if (method === 'bank_qr' || method === 'sepay') {
        sepayInfo.style.display = 'block';
        loadSepayInfo(method);
    } else {
        sepayInfo.style.display = 'none';
    }
}

function loadSepayInfo(method) {
    // Bank configs from backend
    const orgBankConfig = @json($orgBankConfig ?? []);
    const sepayBankConfig = @json($sepayBankConfig ?? []);
    
    // Choose config based on method
    const bankConfig = (method === 'bank_qr') ? orgBankConfig : sepayBankConfig;
    
    console.log('Loading Sepay info for method:', method, {
        orgBankConfig: orgBankConfig,
        sepayBankConfig: sepayBankConfig,
        selectedBankConfig: bankConfig
    });
    
    if (bankConfig && bankConfig.bank_name && bankConfig.account_number) {
        displayBankInfo(bankConfig);
        // Tạo QR code preview
        createQRPreview(bankConfig);
    } else {
        console.warn('Bank config missing or incomplete, trying API fallback');
        // Fallback: load from API
        fetch('/tenant/api/payments/bank-config', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.bank_config) {
                    displayBankInfo(data.bank_config);
                    createQRPreview(data.bank_config);
                } else {
                    console.error('API fallback failed:', data);
                }
            })
            .catch(error => {
                console.error('Error loading bank config:', error);
            });
    }
}

function displayBankInfo(bankConfig) {
    const bankInfo = document.getElementById('bankInfo');
    bankInfo.innerHTML = `
        <div class="bank-item-blue">
            <strong>Ngân hàng:</strong>
            <span>${bankConfig.bank_name}</span>
        </div>
        <div class="bank-item-blue">
            <strong>Số tài khoản:</strong>
            <span>${bankConfig.account_number}</span>
        </div>
        <div class="bank-item-blue">
            <strong>Tên chủ tài khoản:</strong>
            <span>${bankConfig.account_name}</span>
        </div>
        <div class="bank-item-blue">
            <strong>Số tiền:</strong>
            <span>${new Intl.NumberFormat('vi-VN').format({{ $invoice->total_amount }})} VNĐ</span>
        </div>
        <div class="bank-item-blue">
            <strong>Nội dung:</strong>
            <span>{{ str_replace('-', '', $invoice->invoice_no ?? 'HD' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT)) }}</span>
        </div>
    `;
}

function createQRPreview(bankConfig) {
    // Validate bank config
    if (!bankConfig || !bankConfig.account_number || !bankConfig.bank_name) {
        console.error('Invalid bank config for QR preview:', bankConfig);
        const qrContainer = document.getElementById('qrCodeContainer');
        if (qrContainer) {
            qrContainer.innerHTML = `
                <div class="alert alert-warning-blue">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Không thể tạo QR Code</h6>
                    <p>Thông tin ngân hàng chưa đầy đủ. Vui lòng thử lại sau khi xác nhận thanh toán.</p>
                </div>
            `;
        }
        return;
    }
    
    // Lấy thông tin hóa đơn từ PHP
    const invoiceAmount = {{ $invoice->total_amount }};
    const invoiceNo = '{{ str_replace("-", "", $invoice->invoice_no ?? "HD" . str_pad($invoice->id, 6, "0", STR_PAD_LEFT)) }}';
    
    // Nội dung chuyển khoản: chỉ mã hóa đơn không có dấu gạch
    const content = invoiceNo;
    
    // Tạo URL QR code SePay
    const qrUrl = `https://qr.sepay.vn/img?${new URLSearchParams({
        acc: bankConfig.account_number,
        bank: bankConfig.bank_name,
        amount: invoiceAmount,
        des: content
    })}`;
    
    console.log('Creating QR preview:', {
        amount: invoiceAmount,
        content: content,
        qrUrl: qrUrl,
        bankConfig: bankConfig
    });
    
    // Hiển thị QR code preview
    const qrContainer = document.getElementById('qrCodeContainer');
    if (qrContainer) {
        qrContainer.innerHTML = `
            <div class="qr-code-blue">
                <h6><i class="fas fa-qrcode me-2"></i>Mã QR Thanh Toán</h6>
                <img src="${qrUrl}" alt="QR Code SePay" style="max-width: 250px; height: auto; border: 2px solid var(--blue-border); border-radius: 12px; padding: 10px; background: white;" onerror="showQRError()" onload="console.log('QR preview loaded successfully:', this.src)">
                <p class="mt-3" style="color: #666;"><small>Quét mã QR bằng app ngân hàng để chuyển khoản</small></p>
                <div class="mt-3">
                    <button class="btn btn-sm" onclick="downloadQRCode('${qrUrl}')" style="background: var(--blue-gradient); color: white; border: none; border-radius: 8px; padding: 0.5rem 1rem;">
                        <i class="fas fa-download me-1"></i>Tải QR Code
                    </button>
                </div>
                <div class="mt-3" style="text-align: left; background: var(--blue-bg-light); padding: 1rem; border-radius: 8px; border: 1px solid var(--blue-border);">
                    <small style="color: #333;">
                        <strong style="color: var(--blue-primary);">Số tiền:</strong> ${invoiceAmount.toLocaleString('vi-VN')} VNĐ<br>
                        <strong style="color: var(--blue-primary);">Nội dung:</strong> ${content}
                    </small>
                </div>
            </div>
        `;
        qrContainer.style.display = 'block';
        console.log('QR preview displayed successfully');
    }
}

function processPayment() {
    if (!selectedMethod) {
        alert('Vui lòng chọn phương thức thanh toán');
        return;
    }
    
    document.getElementById('loading').style.display = 'block';
    document.getElementById('payButton').disabled = true;
    
    let url = '';
    if (selectedMethod === 'cash') {
        url = `/tenant/api/payments/cash/{{ $invoice->id }}`;
    } else if (selectedMethod === 'bank_qr') {
        url = `/tenant/api/payments/bank_qr/{{ $invoice->id }}`;
    } else if (selectedMethod === 'sepay') {
        url = `/tenant/api/payments/sepay/{{ $invoice->id }}`;
    }
    
    // Create FormData for file upload
    const formData = new FormData();
    const imageFile = document.getElementById('paymentImage').files[0];
    if (imageFile) {
        formData.append('image', imageFile);
    }
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        credentials: 'same-origin',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loading').style.display = 'none';
        
        if (data.success) {
            currentPaymentId = data.payment_id;
            
            if ((selectedMethod === 'bank_qr' || selectedMethod === 'sepay') && data.qr_url) {
                // Display QR code for bank transfer
                console.log('QR URL:', data.qr_url);
                console.log('QR Container element:', document.getElementById('qrCodeContainer'));
                
                const qrContainer = document.getElementById('qrCodeContainer');
                if (qrContainer) {
                    qrContainer.innerHTML = `
                        <div class="qr-code-blue">
                            <h6><i class="fas fa-qrcode me-2"></i>Mã QR Thanh Toán</h6>
                            <img src="${data.qr_url}" alt="QR Code SePay" style="max-width: 250px; height: auto; border: 2px solid var(--blue-border); border-radius: 12px; padding: 10px; background: white;" onerror="showQRError()" onload="console.log('QR image loaded successfully:', this.src)">
                            <p class="mt-3" style="color: #666;"><small>Quét mã QR bằng app ngân hàng để chuyển khoản</small></p>
                            <div class="mt-3">
                                <button class="btn btn-sm" onclick="downloadQRCode('${data.qr_url}')" style="background: var(--blue-gradient); color: white; border: none; border-radius: 8px; padding: 0.5rem 1rem;">
                                    <i class="fas fa-download me-1"></i>Tải QR Code
                                </button>
                            </div>
                        </div>
                    `;
                    qrContainer.style.display = 'block';
                    console.log('QR code displayed successfully');
                } else {
                    console.error('QR container not found!');
                }
            }
            
            // Show success message
            let message = '';
            if (selectedMethod === 'cash') {
                message = 'Yêu cầu thanh toán tiền mặt đã được tạo. Vui lòng chờ agent xác thực.';
            } else if (selectedMethod === 'bank_qr') {
                message = 'Yêu cầu chuyển khoản đã được tạo. Vui lòng chuyển vào ngân hàng tổ chức và chờ xác nhận.';
            } else if (selectedMethod === 'sepay') {
                message = 'Yêu cầu thanh toán qua SePay đã được tạo. Chuyển khoản sẽ được cập nhật tự động.';
            }
            
            document.getElementById('successMessage').textContent = message;
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
            
            // Add hover effects to buttons
            setTimeout(() => {
                const modalButtons = document.querySelectorAll('#successModal .btn');
                modalButtons.forEach(btn => {
                    btn.addEventListener('mouseenter', function() {
                        if (this.style.background.includes('gradient')) {
                            this.style.transform = 'translateY(-2px)';
                            this.style.boxShadow = '0 6px 20px rgba(39, 102, 236, 0.4)';
                        } else {
                            this.style.background = 'var(--blue-bg-light)';
                            this.style.borderColor = 'var(--blue-primary)';
                            this.style.transform = 'translateY(-2px)';
                        }
                    });
                    btn.addEventListener('mouseleave', function() {
                        if (this.style.background.includes('gradient')) {
                            this.style.transform = 'translateY(0)';
                            this.style.boxShadow = '0 4px 15px rgba(39, 102, 236, 0.3)';
                        } else {
                            this.style.background = 'white';
                            this.style.borderColor = 'var(--blue-border)';
                            this.style.transform = 'translateY(0)';
                        }
                    });
                });
            }, 100);
        } else {
            alert('Lỗi: ' + data.message);
            document.getElementById('payButton').disabled = false;
        }
    })
    .catch(error => {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('payButton').disabled = false;
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xử lý thanh toán');
    });
}

function viewPaymentStatus() {
    if (currentPaymentId) {
        window.location.href = `/tenant/payments/status/${currentPaymentId}`;
    }
}

function goToInvoices() {
    window.location.href = '{{ route("tenant.invoices.index") }}';
}

function goBack() {
    window.history.back();
}

function downloadQRCode(qrUrl) {
    // Tạo link tải QR code
    const link = document.createElement('a');
    link.href = qrUrl;
    link.download = 'qr-code-sepay.png';
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function showQRError() {
    console.error('Failed to load QR image');
    const qrFallback = document.getElementById('qrFallback');
    if (qrFallback) {
        qrFallback.style.display = 'block';
    } else {
        console.error('qrFallback element not found');
    }
}

function retryQRCode() {
    console.log('Retrying QR code...');
    const qrFallback = document.getElementById('qrFallback');
    if (qrFallback) {
        qrFallback.style.display = 'none';
    }
    // Reload the page to retry
    location.reload();
}

function toggleTestQR() {
    const testQR = document.getElementById('testQR');
    if (testQR) {
        if (testQR.style.display === 'none') {
            testQR.style.display = 'block';
        } else {
            testQR.style.display = 'none';
        }
    } else {
        console.error('testQR element not found');
    }
}

// Image preview
document.getElementById('paymentImage').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('imagePreview').style.display = 'none';
    }
});

// Auto-refresh for Sepay payments
if (selectedMethod === 'sepay' && currentPaymentId) {
    setInterval(() => {
        fetch(`/tenant/api/payments/status/${currentPaymentId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.payment.status === 'success') {
                    location.reload();
                }
            })
            .catch(error => console.error('Error checking payment status:', error));
    }, 5000); // Check every 5 seconds
}
</script>
@endpush
