@extends('layouts.staff_dashboard')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Chỉnh sửa thanh toán')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>
                        Chỉnh sửa thanh toán #{{ $payment->id }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('staff.payments.show', $payment) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Quay lại
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('staff.payments.update', $payment) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_id" class="required">Hóa đơn</label>
                                    <select name="invoice_id" id="invoice_id" class="form-control @error('invoice_id') is-invalid @enderror" required>
                                        <option value="">-- Chọn hóa đơn --</option>
                                        @foreach($invoices as $invoice)
                                            <option value="{{ $invoice->id }}" {{ old('invoice_id', $payment->invoice_id) == $invoice->id ? 'selected' : '' }}>
                                                #{{ $invoice->id }} - {{ $invoice->user->name ?? 'N/A' }} 
                                                @if($invoice->lease)
                                                    ({{ $invoice->lease->property->name ?? 'N/A' }})
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
                                <div class="form-group">
                                    <label for="payer_user_id">Khách hàng</label>
                                    <select name="payer_user_id" id="payer_user_id" class="form-control @error('payer_user_id') is-invalid @enderror">
                                        <option value="">-- Chọn khách hàng --</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('payer_user_id', $payment->payer_user_id) == $user->id ? 'selected' : '' }}
                                                    data-priority="{{ $user->priority ?? 'low' }}"
                                                    data-pending-count="{{ $user->pending_invoices_count ?? 0 }}">
                                                {{ $user->full_name }} ({{ $user->email }})
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
                                <div class="form-group">
                                    <label for="method_id">Phương thức thanh toán</label>
                                    <select name="method_id" id="method_id" class="form-control @error('method_id') is-invalid @enderror">
                                        <option value="">-- Chọn phương thức --</option>
                                        @foreach($paymentMethods as $method)
                                            <option value="{{ $method->id }}" {{ old('method_id', $payment->method_id) == $method->id ? 'selected' : '' }}>
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
                                <div class="form-group">
                                    <label for="amount" class="required">Số tiền</label>
                                    <div class="input-group">
                                        <input type="number" name="amount" id="amount" 
                                               class="form-control @error('amount') is-invalid @enderror" 
                                               value="{{ old('amount', $payment->amount) }}" step="0.01" min="0" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">VND</span>
                                        </div>
                                    </div>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="paid_at" class="required">Ngày thanh toán</label>
                                    <input type="datetime-local" name="paid_at" id="paid_at" 
                                           class="form-control @error('paid_at') is-invalid @enderror" 
                                           value="{{ old('paid_at', $payment->paid_at ? $payment->paid_at->format('Y-m-d\TH:i') : '') }}" required>
                                    @error('paid_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status" class="required">Trạng thái</label>
                                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                        @foreach($statuses as $value => $label)
                                            <option value="{{ $value }}" {{ old('status', $payment->status) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="txn_ref">Mã giao dịch</label>
                                    <input type="text" name="txn_ref" id="txn_ref" 
                                           class="form-control @error('txn_ref') is-invalid @enderror" 
                                           value="{{ old('txn_ref', $payment->txn_ref) }}" maxlength="150">
                                    @error('txn_ref')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="image">Ảnh tài liệu đối chiếu</label>
                                    @php
                                        $primaryImage = $payment->documents()
                                            ->where('document_type', 'image')
                                            ->orderBy('sort_order')
                                            ->orderBy('created_at')
                                            ->first();
                                    @endphp
                                    
                                    @if($primaryImage)
                                        <div class="mb-3">
                                            @php
                                                $imageUrl = str_starts_with($primaryImage->file_url, 'http://') || str_starts_with($primaryImage->file_url, 'https://') 
                                                    ? $primaryImage->file_url 
                                                    : asset('storage/' . ltrim($primaryImage->file_url, '/'));
                                            @endphp
                                            <div class="position-relative d-inline-block">
                                                <img src="{{ $imageUrl }}" alt="Current image" class="img-thumbnail" style="max-width: 300px; max-height: 300px; object-fit: cover;">
                                                <div class="position-absolute top-0 start-0 bg-dark bg-opacity-75 text-white px-2 py-1 rounded-bottom-end" style="font-size: 0.8rem;">
                                                    Ảnh hiện tại
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
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
                                    <small class="form-text text-muted">Hỗ trợ: JPEG, PNG, JPG, GIF, WebP (tối đa 5MB). Để trống nếu không muốn thay đổi.</small>
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
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="note">Ghi chú</label>
                                    <textarea name="note" id="note" rows="3" 
                                              class="form-control @error('note') is-invalid @enderror" 
                                              placeholder="Nhập ghi chú về thanh toán...">{{ old('note', $payment->note) }}</textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary" onclick="return confirmSave()">
                                    <i class="fas fa-save mr-1"></i>
                                    Cập nhật thanh toán
                                </button>
                                <a href="{{ route('staff.payments.show', $payment) }}" class="btn btn-secondary">
                                    <i class="fas fa-times mr-1"></i>
                                    Hủy
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
function confirmSave() {
    return new Promise((resolve) => {
        Notify.confirmSave(function() {
            resolve(true);
        });
    });
}

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

// Image preview
document.getElementById('image').addEventListener('change', function(e) {
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
</script>
@endpush