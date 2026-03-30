@extends('layouts.staff_dashboard')

@section('title', 'Tạo thanh toán mới')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Tạo thanh toán mới',
            'subtitle' => 'Thêm thanh toán mới vào hệ thống',
            'icon' => 'fas fa-plus',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.payments.index')
                ]
            ]
        ])

        {{-- 2. Form với Layout 2 Cột --}}
        <form id="create-payment-form" method="POST" action="{{ route('staff.payments.store') }}" enctype="multipart/form-data">
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
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="invoice_id" class="form-label">
                                            Hóa đơn <span class="text-danger">*</span>
                                        </label>
                                        <select name="invoice_id" id="invoice_id" 
                                                class="form-select @error('invoice_id') is-invalid @enderror" 
                                                required>
                                            <option value="">-- Chọn hóa đơn --</option>
                                            @foreach($invoices as $invoice)
                                                <option value="{{ $invoice->id }}" 
                                                    {{ (old('invoice_id') == $invoice->id || (isset($selectedInvoice) && $selectedInvoice && $selectedInvoice->id == $invoice->id)) ? 'selected' : '' }}>
                                                    #{{ $invoice->id }} - 
                                                    @if($invoice->lease && $invoice->lease->tenant)
                                                        {{ $invoice->lease->tenant->full_name ?? $invoice->lease->tenant->name ?? 'N/A' }}
                                                        @if($invoice->lease->property)
                                                            ({{ $invoice->lease->property->name ?? 'N/A' }})
                                                        @endif
                                                    @elseif($invoice->bookingDeposit)
                                                        @if($invoice->bookingDeposit->tenantUser)
                                                            {{ $invoice->bookingDeposit->tenantUser->full_name ?? 'N/A' }}
                                                        @elseif($invoice->bookingDeposit->lead)
                                                            {{ $invoice->bookingDeposit->lead->name ?? 'N/A' }}
                                                        @else
                                                            N/A
                                                        @endif
                                                        @if($invoice->bookingDeposit->unit && $invoice->bookingDeposit->unit->property)
                                                            ({{ $invoice->bookingDeposit->unit->property->name ?? 'N/A' }})
                                                        @endif
                                                    @else
                                                        N/A
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('invoice_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payer_user_id" class="form-label">Khách hàng</label>
                                        <select name="payer_user_id" id="payer_user_id" 
                                                class="form-select @error('payer_user_id') is-invalid @enderror">
                                            <option value="">-- Chọn khách hàng --</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ old('payer_user_id') == $user->id ? 'selected' : '' }}
                                                        data-priority="{{ $user->priority ?? 'low' }}"
                                                        data-pending-count="{{ $user->pending_invoices_count ?? 0 }}">
                                                    {{ $user->full_name ?? $user->email }} ({{ $user->email }})
                                                    @if(isset($user->priority) && $user->priority === 'high')
                                                         Có {{ $user->pending_invoices_count ?? 0 }} hóa đơn chưa thanh toán
                                                    @elseif(isset($user->priority) && $user->priority === 'medium')
                                                         Đã có thanh toán
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('payer_user_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="lead_id" class="form-label">Lead</label>
                                        <select name="lead_id" id="lead_id" 
                                                class="form-select @error('lead_id') is-invalid @enderror">
                                            <option value="">-- Chọn lead --</option>
                                            @foreach($leads as $lead)
                                                <option value="{{ $lead->id }}" {{ old('lead_id') == $lead->id ? 'selected' : '' }}>
                                                    {{ $lead->name }} ({{ $lead->phone }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted">Chọn lead nếu thanh toán từ đặt cọc có lead</small>
                                        @error('lead_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="method_id" class="form-label">Phương thức thanh toán <span class="text-danger">*</span></label>
                                        <select name="method_id" id="method_id" 
                                                class="form-select @error('method_id') is-invalid @enderror" required>
                                            <option value="">-- Chọn phương thức --</option>
                                            @foreach($paymentMethods as $method)
                                                <option value="{{ $method->id }}" {{ old('method_id') == $method->id ? 'selected' : '' }}>
                                                    {{ $method->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('method_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">
                                            Số tiền <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" name="amount" id="amount" 
                                                   class="form-control @error('amount') is-invalid @enderror" 
                                                   value="{{ old('amount') }}" 
                                                   step="0.01" min="0" required>
                                            <span class="input-group-text">VND</span>
                                        </div>
                                        @error('amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="paid_at" class="form-label">
                                            Ngày thanh toán <span class="text-danger">*</span>
                                        </label>
                                        <input type="datetime-local" name="paid_at" id="paid_at" 
                                               class="form-control @error('paid_at') is-invalid @enderror" 
                                               value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}" 
                                               required>
                                        @error('paid_at')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                {{-- Trạng thái luôn là 'pending' khi tạo mới, không hiển thị trong form --}}
                                <input type="hidden" name="status" value="pending">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="txn_ref" class="form-label">Mã giao dịch</label>
                                        <input type="text" name="txn_ref" id="txn_ref" 
                                               class="form-control @error('txn_ref') is-invalid @enderror" 
                                               value="{{ old('txn_ref') }}" 
                                               maxlength="150">
                                        @error('txn_ref')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Ảnh tài liệu đối chiếu</label>
                                <div class="image-upload-area" id="imageUploadArea" style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease;" ondrop="handleDrop(event, 'image')" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <p class="mb-2">Kéo thả ảnh vào đây hoặc click để chọn</p>
                                    <input type="file" 
                                           name="image" 
                                           id="image" 
                                           class="form-control @error('image') is-invalid @enderror" 
                                           accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                           style="display: none;">
                                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('image').click()">
                                        <i class="fas fa-folder-open me-2"></i>Chọn ảnh
                                    </button>
                                </div>
                                <div class="form-text">
                                    Hỗ trợ: JPEG, PNG, JPG, GIF, WebP (tối đa 5MB)
                                </div>
                                @error('image')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div id="imagePreview" class="mt-3" style="display: none;">
                                    <div class="position-relative d-inline-block">
                                        <img id="previewImg" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 300px; object-fit: cover;">
                                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="removeImagePreview()" title="Xóa ảnh" style="margin: 5px;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="note" class="form-label">Ghi chú</label>
                                <textarea name="note" id="note" rows="3" 
                                          class="form-control @error('note') is-invalid @enderror" 
                                          placeholder="Nhập ghi chú về thanh toán...">{{ old('note') }}</textarea>
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
                                        'label' => 'Tạo thanh toán',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.payments.index')
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
                                    <li>Hóa đơn, số tiền và ngày thanh toán là bắt buộc</li>
                                    <li>Trạng thái mặc định là "Chờ thanh toán"</li>
                                    <li>Có thể tải ảnh tài liệu đối chiếu (tối đa 5MB)</li>
                                    <li>Mã giao dịch và ghi chú là tùy chọn</li>
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

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
// Sắp xếp khách hàng theo độ ưu tiên
document.addEventListener('DOMContentLoaded', function() {
    const userSelect = document.getElementById('payer_user_id');
    if (userSelect) {
        const options = Array.from(userSelect.options);
        const placeholder = options[0]; // Lưu option "-- Chọn khách hàng --"
        const userOptions = options.slice(1); // Lấy các option khách hàng
        
        // Sắp xếp theo độ ưu tiên: high -> medium -> low
        userOptions.sort((a, b) => {
            const priorityOrder = { 'high': 0, 'medium': 1, 'low': 2 };
            const aPriority = a.getAttribute('data-priority') || 'low';
            const bPriority = b.getAttribute('data-priority') || 'low';
            
            if (priorityOrder[aPriority] !== priorityOrder[bPriority]) {
                return priorityOrder[aPriority] - priorityOrder[bPriority];
            }
            
            // Nếu cùng độ ưu tiên, sắp xếp theo tên
            return a.textContent.localeCompare(b.textContent);
        });
        
        // Xóa tất cả options và thêm lại theo thứ tự mới
        userSelect.innerHTML = '';
        userSelect.appendChild(placeholder);
        userOptions.forEach(option => userSelect.appendChild(option));
    }
});

// Image preview
document.getElementById('image')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Check file size (5MB limit)
        if (file.size > 5 * 1024 * 1024) {
            Notify.error('File quá lớn (>5MB). Vui lòng chọn file nhỏ hơn.', 'Lỗi');
            this.value = '';
            document.getElementById('imagePreview').style.display = 'none';
            return;
        }
        
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

// Drag and drop functions
window.handleDragOver = function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.style.borderColor = '#007bff';
    e.currentTarget.style.backgroundColor = '#f8f9fa';
};

window.handleDragLeave = function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.style.borderColor = '#dee2e6';
    e.currentTarget.style.backgroundColor = 'transparent';
};

window.handleDrop = function(e, inputId) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.style.borderColor = '#dee2e6';
    e.currentTarget.style.backgroundColor = 'transparent';
    
    const files = e.dataTransfer.files;
    const input = document.getElementById(inputId);
    
    if (files.length > 0 && input) {
        const file = files[0];
        if (file.type.startsWith('image/')) {
            // Check file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                Notify.error('File quá lớn (>5MB). Vui lòng chọn file nhỏ hơn.', 'Lỗi');
                return;
            }
            
            // Create a new FileList-like object
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            input.dispatchEvent(event);
        } else {
            Notify.warning('File không phải là hình ảnh. Vui lòng chọn file hình ảnh.', 'Cảnh báo');
        }
    }
};

window.removeImagePreview = function() {
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    
    if (imageInput) {
        imageInput.value = '';
    }
    if (imagePreview) {
        imagePreview.style.display = 'none';
    }
};

// Auto-fill invoice details when invoice is selected
function loadInvoiceDetails(invoiceId) {
    if (!invoiceId) {
        return;
    }
    
    // Show loading indicator if available
    if (window.Preloader) {
        window.Preloader.show();
    }
    
    // Fetch invoice details
    fetch(`{{ route('staff.payments.invoice-details', ['invoiceId' => 'PLACEHOLDER']) }}`.replace('PLACEHOLDER', invoiceId), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
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
        if (data.success && data.invoice) {
            const invoice = data.invoice;
            
            // Auto-fill payer_user_id hoặc lead_id (chỉ một trong hai)
            const payerUserIdSelect = document.getElementById('payer_user_id');
            const leadIdSelect = document.getElementById('lead_id');
            
            // Ưu tiên user trước, nếu không có user thì mới fill lead
            if (invoice.payer_user_id) {
                if (payerUserIdSelect) {
                    payerUserIdSelect.value = invoice.payer_user_id;
                }
                // Clear lead nếu đã chọn user
                if (leadIdSelect) {
                    leadIdSelect.value = '';
                }
            } else {
                // Nếu không có user, fill lead
                if (leadIdSelect) {
                    const leadId = invoice.lead_id || (invoice.booking_deposit && invoice.booking_deposit.lead_id);
                    if (leadId) {
                        leadIdSelect.value = leadId;
                    }
                }
                // Clear user nếu đã chọn lead
                if (payerUserIdSelect) {
                    payerUserIdSelect.value = '';
                }
            }
            
            // Auto-fill amount
            const amountInput = document.getElementById('amount');
            if (amountInput && invoice.total_amount) {
                amountInput.value = parseFloat(invoice.total_amount).toFixed(2);
            }
            
            console.log('✅ Invoice details auto-filled:', invoice);
        } else {
            console.error('Failed to load invoice details:', data.message);
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message || 'Không thể tải thông tin hóa đơn', 'Lỗi!');
            }
        }
    })
    .catch(error => {
        console.error('Error loading invoice details:', error);
        if (typeof Notify !== 'undefined') {
            Notify.error('Không thể tải thông tin hóa đơn: ' + error.message, 'Lỗi!');
        }
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
}

document.getElementById('invoice_id')?.addEventListener('change', function() {
    const invoiceId = this.value;
    if (!invoiceId) {
        // Clear fields if no invoice selected
        const payerUserIdSelect = document.getElementById('payer_user_id');
        const leadIdSelect = document.getElementById('lead_id');
        const amountInput = document.getElementById('amount');
        if (payerUserIdSelect) payerUserIdSelect.value = '';
        if (leadIdSelect) leadIdSelect.value = '';
        if (amountInput) amountInput.value = '';
        return;
    }
    
    loadInvoiceDetails(invoiceId);
});

// Auto-load invoice details if invoice_id is pre-selected (from query parameter)
document.addEventListener('DOMContentLoaded', function() {
    const invoiceSelect = document.getElementById('invoice_id');
    if (invoiceSelect && invoiceSelect.value) {
        // Delay slightly to ensure page is fully loaded
        setTimeout(() => {
            loadInvoiceDetails(invoiceSelect.value);
        }, 300);
    }
});

// Mutual exclusion: Khi chọn user thì clear lead, khi chọn lead thì clear user
document.getElementById('payer_user_id')?.addEventListener('change', function() {
    const payerUserId = this.value;
    const leadIdSelect = document.getElementById('lead_id');
    
    if (payerUserId && leadIdSelect) {
        // Nếu chọn user, clear lead
        leadIdSelect.value = '';
    }
});

document.getElementById('lead_id')?.addEventListener('change', function() {
    const leadId = this.value;
    const payerUserIdSelect = document.getElementById('payer_user_id');
    
    if (leadId && payerUserIdSelect) {
        // Nếu chọn lead, clear user
        payerUserIdSelect.value = '';
    }
});

// Form submission with AJAX
document.getElementById('create-payment-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (window.Preloader) {
        window.Preloader.show();
    }
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Notify.success(data.message || 'Thanh toán đã được tạo thành công!', 'Thành công!');
            setTimeout(() => {
                window.location.href = data.redirect || '{{ route("staff.payments.index") }}';
            }, 1500);
        } else {
            Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Notify.error('Không thể tạo thanh toán: ' + error.message, 'Lỗi hệ thống!');
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
});

// Show validation errors
@if($errors->any())
    @foreach($errors->all() as $error)
        Notify.error('{{ $error }}');
    @endforeach
@endif

// Show success/error messages from session
@if(session('success'))
    Notify.success('{{ session('success') }}');
@endif

@if(session('error'))
    Notify.error('{{ session('error') }}');
@endif
</script>
@endpush
