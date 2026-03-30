@extends('layouts.staff_dashboard')

@section('title', 'Tạo Dòng tiền ra mới')

@section('content')
<main class="main-content">
<div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Tạo Dòng tiền ra mới',
            'subtitle' => 'Tạo dòng tiền ra mới trong hệ thống',
            'icon' => 'fas fa-plus',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.cash-outflows.index')
                ]
            ]
        ])

        {{-- 2. Form với Layout 2 Cột --}}
        <form id="create-cash-outflow-form" method="POST" action="{{ route('staff.cash-outflows.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    {{-- Card 1: Thông tin cơ bản --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                            </h6>
                        </div>
                        <div class="card-body">
                            {{-- Mã giao dịch (tự sinh, readonly) - Lên trên cùng --}}
    <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="transaction_ref" class="form-label">Mã giao dịch</label>
                                        <input type="text" 
                                               class="form-control bg-light" 
                                               id="transaction_ref" 
                                               name="transaction_ref" 
                                               value="" 
                                               readonly
                                               placeholder="Mã sẽ được tự động tạo">
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i> Mã giao dịch sẽ được tự động tạo khi lưu
                                        </small>
                    </div>
                </div>

                            <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="company_invoice_id" class="form-label">Hóa đơn công ty</label>
                                        <select name="company_invoice_id" id="company_invoice_id" class="form-select @error('company_invoice_id') is-invalid @enderror">
                                            <option value="">-- Chọn hóa đơn công ty (tùy chọn) --</option>
                                            @foreach($companyInvoices ?? [] as $invoice)
                                                <option value="{{ $invoice['id'] }}" 
                                                        data-vendor-name="{{ $invoice['vendor_name'] }}"
                                                        data-total-amount="{{ $invoice['total_amount'] }}"
                                                        data-outstanding-amount="{{ $invoice['outstanding_amount'] }}"
                                                        {{ old('company_invoice_id') == $invoice['id'] ? 'selected' : '' }}>
                                                    {{ $invoice['invoice_no'] }} - {{ $invoice['vendor_name'] }} ({{ number_format($invoice['outstanding_amount'], 0, ',', '.') }} VND)
                                            </option>
                                        @endforeach
                                    </select>
                                        @error('company_invoice_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                        <small class="form-text text-muted">Chọn hóa đơn công ty để tự động điền thông tin</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                            <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">
                                            Số tiền (VND) <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control money-input @error('amount') is-invalid @enderror" 
                                               id="amount" 
                                               name="amount" 
                                               value="{{ old('amount') }}" 
                                               placeholder="Ví dụ: 1.000.000"
                                               required>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                            </div>
                        </div>

                            <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_method_id" class="form-label">
                                            Phương thức thanh toán <span class="text-danger">*</span>
                                        </label>
                                        <select name="payment_method_id" id="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror" required>
                                        <option value="">-- Chọn phương thức --</option>
                                            @foreach($paymentMethods as $paymentMethod)
                                                <option value="{{ $paymentMethod->id }}" {{ old('payment_method_id') == $paymentMethod->id ? 'selected' : '' }}>
                                                    {{ $paymentMethod->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                        @error('payment_method_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Trạng thái luôn là 'pending' khi tạo mới, không hiển thị trong form --}}
                            <input type="hidden" name="status" value="pending">
                            
                            <div class="row">
                            <div class="col-md-6">

                            <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="paid_at" class="form-label">Ngày thanh toán</label>
                                        <input type="datetime-local" 
                                               class="form-control @error('paid_at') is-invalid @enderror" 
                                               id="paid_at" 
                                               name="paid_at" 
                                               value="{{ old('paid_at') }}">
                                        @error('paid_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                        <small class="form-text text-muted">Để trống nếu chưa thanh toán</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                            <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="document" class="form-label">Tài liệu</label>
                                        <input type="file" 
                                               class="form-control @error('document') is-invalid @enderror" 
                                               id="document" 
                                               name="document" 
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                    @error('document')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Upload tài liệu (PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF) - Tối đa 20MB</small>
                                    <div id="document-preview" class="mt-2" style="display: none;">
                                        <div class="alert alert-info">
                                            <i class="fas fa-file"></i> <span id="document-name"></span>
                                                <button type="button" class="btn btn-sm btn-danger float-end" onclick="removeDocument()">
                                                <i class="fas fa-times"></i> Xóa
                                            </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="note" class="form-label">Ghi chú</label>
                                <textarea class="form-control @error('note') is-invalid @enderror" 
                                          id="note" 
                                          name="note" 
                                          rows="3" 
                                          placeholder="Nhập ghi chú về dòng tiền ra này...">{{ old('note') }}</textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                            </div>
                                </div>
                            </div>
                        </div>

                {{-- Cột phải: Sidebar (col-lg-4) --}}
                <div class="col-lg-4">
                    {{-- Card Thao tác (chứa action-buttons với layout dọc) --}}
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
                                        'label' => 'Lưu',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.cash-outflows.index')
                                    ]
                                ]
                            ])
                        </div>
                    </div>

                    {{-- Card Hướng dẫn --}}
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
                                    <li>Số tiền, phương thức thanh toán và trạng thái là bắt buộc</li>
                                    <li>Mã giao dịch sẽ được tự động tạo khi lưu</li>
                                    <li>Có thể chọn hóa đơn công ty để tự động điền thông tin</li>
                                    <li>Có thể upload tài liệu để lưu trữ</li>
                                    <li>Ngày thanh toán sẽ tự động điền khi chọn trạng thái "Thành công"</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/number-formatter.js') }}"></script>
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
// Session notifications
@if(session('success'))
    document.addEventListener('DOMContentLoaded', function() {
        Notify.success('{{ session('success') }}', 'Thành công!');
    });
@endif

@if(session('error'))
    document.addEventListener('DOMContentLoaded', function() {
        Notify.error('{{ session('error') }}', 'Lỗi!');
    });
@endif

// Generate transaction reference preview
function generateTransactionRefPreview() {
    const paymentMethodSelect = document.getElementById('payment_method_id');
    const transactionRefInput = document.getElementById('transaction_ref');
    
    if (!paymentMethodSelect || !transactionRefInput) return;
    
    const paymentMethodId = paymentMethodSelect.value;
    const selectedOption = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
    const paymentMethodName = selectedOption ? selectedOption.text : '';
    
    if (!paymentMethodId) {
        transactionRefInput.value = '';
        return;
    }
    
    // Generate preview transaction ref (will be finalized on server)
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const orgId = '{{ Auth::user()->organizationUsers->first()?->organization_id ?? "ORG" }}';
    
    // Preview format: CO-{key_code}-{org_id}-{year}-{month}-{sequence}
    // For preview, we'll use a placeholder for key_code and sequence
    // The actual key_code will be determined by payment method on server
    transactionRefInput.value = `CO-XXXX-${orgId}-${year}-${month}-XXXXXX`;
    transactionRefInput.title = 'Mã giao dịch sẽ được tự động tạo khi lưu';
}

// Auto-fill company invoice information
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('create-cash-outflow-form');
    if (!form) return;
    
    const companyInvoiceSelect = document.getElementById('company_invoice_id');
    const amountInput = document.getElementById('amount');
    const noteTextarea = document.getElementById('note');
    const paymentMethodSelect = document.getElementById('payment_method_id');
    const transactionRefInput = document.getElementById('transaction_ref');
    
    // Generate transaction ref preview when payment method changes
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            generateTransactionRefPreview();
        });
    }
    
    // Handle company invoice selection
    if (companyInvoiceSelect) {
        companyInvoiceSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const companyInvoiceId = this.value;
            
            if (!companyInvoiceId) {
                // Clear auto-filled data if no invoice selected
                return;
            }
            
            // Show loading
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            // Fetch company invoice information
            fetch(`{{ route('staff.api.cash-outflows.company-invoice', ':id') }}`.replace(':id', companyInvoiceId), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(async response => {
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }
                
                if (data.success && data.data) {
                    const invoice = data.data;
                    
                    // Auto-fill amount with outstanding amount
                    if (amountInput && invoice.outstanding_amount) {
                        const formattedAmount = parseFloat(invoice.outstanding_amount).toLocaleString('vi-VN', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        });
                        amountInput.value = formattedAmount.replace(/\./g, '');
                        
                        // Trigger input event to format
                        if (window.NumberFormatter && window.NumberFormatter.formatInput) {
                            window.NumberFormatter.formatInput(amountInput);
                        }
                    }
                    
                    // Auto-fill note with invoice information
                    if (noteTextarea) {
                        let noteText = `Thanh toán hóa đơn công ty: ${invoice.invoice_no}`;
                        if (invoice.vendor_name) {
                            noteText += ` - ${invoice.vendor_name}`;
                        }
                        if (invoice.description) {
                            noteText += `\n${invoice.description}`;
                        }
                        noteTextarea.value = noteText;
                    }
                    
                    // Generate transaction ref preview
                    generateTransactionRefPreview();
                    
                    Notify.success('Đã tự động điền thông tin từ hóa đơn công ty', 'Thành công!');
    } else {
                    Notify.error('Không thể tải thông tin hóa đơn công ty', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Không thể tải thông tin hóa đơn công ty: ' + error.message, 'Lỗi!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        });
    }
    
    // Unformat number inputs before submission
    form.addEventListener('submit', function(e) {
        // Unformat number inputs before submission
        if (window.NumberFormatter && window.NumberFormatter.processForm) {
            window.NumberFormatter.processForm(form);
    }
});

// Auto-fill paid_at when status is success
    const statusSelect = document.getElementById('status');
    const paidAtField = document.getElementById('paid_at');
    
    if (statusSelect && paidAtField) {
        statusSelect.addEventListener('change', function() {
    if (this.value === 'success' && !paidAtField.value) {
        const now = new Date();
        const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        paidAtField.value = localDateTime;
    }
});
    }

// Handle document upload
    const documentInput = document.getElementById('document');
    if (documentInput) {
        documentInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        document.getElementById('document-name').textContent = file.name;
        document.getElementById('document-preview').style.display = 'block';
                // Note: Document URL will be saved to transaction_ref on server
    } else {
        document.getElementById('document-preview').style.display = 'none';
    }
        });
    }
    
    // Generate initial transaction ref preview
    generateTransactionRefPreview();
});

function removeDocument() {
    document.getElementById('document').value = '';
    document.getElementById('document-preview').style.display = 'none';
}
</script>
@endpush
