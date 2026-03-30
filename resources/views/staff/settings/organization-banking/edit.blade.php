@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa tài khoản ngân hàng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-edit me-2"></i>Chỉnh sửa tài khoản ngân hàng
                </h1>
                <p class="text-muted mb-0">Cập nhật thông tin tài khoản ngân hàng</p>
            </div>
            <div>
                <a href="{{ route('staff.organization-banking.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                </a>
            </div>
        </div>

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('staff.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('staff.organization-banking.index') }}">Tài khoản ngân hàng</a></li>
                <li class="breadcrumb-item active">Chỉnh sửa</li>
            </ol>
        </nav>

        <!-- Form -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-university me-2"></i>Thông tin tài khoản ngân hàng
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="bankingAccountForm">
                            @csrf
                            @method('PUT')
                            
                            <div class="mb-3">
                                <label for="sepay_bank_id" class="form-label required">Ngân hàng</label>
                                <select class="form-select" id="sepay_bank_id" name="sepay_bank_id" required>
                                    <option value="">-- Chọn ngân hàng --</option>
                                    @foreach($sepayBanks as $bank)
                                        <option value="{{ $bank->id }}" 
                                                data-sepay-name="{{ $bank->sepay_name ?? $bank->short_name ?? $bank->name }}"
                                                {{ $bankingAccount->sepay_bank_id == $bank->id ? 'selected' : '' }}>
                                            {{ $bank->name }} ({{ $bank->code }})
                                            @if($bank->sepay_name)
                                                - SePay: {{ $bank->sepay_name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback"></div>
                                <div class="form-text">Chọn ngân hàng từ danh sách được SePay hỗ trợ</div>
                            </div>

                            <div class="mb-3">
                                <label for="account_number" class="form-label required">Số tài khoản</label>
                                <input type="text" class="form-control" id="account_number" name="account_number" 
                                       value="{{ $bankingAccount->account_number }}" required>
                                <div class="invalid-feedback"></div>
                                <div class="form-text">Số tài khoản ngân hàng</div>
                            </div>

                            <div class="mb-3">
                                <label for="account_name" class="form-label required">Tên chủ tài khoản</label>
                                <input type="text" class="form-control" id="account_name" name="account_name" 
                                       value="{{ $bankingAccount->account_name }}" required>
                                <div class="invalid-feedback"></div>
                                <div class="form-text">Tên chủ tài khoản (chính xác như trong ngân hàng)</div>
                            </div>

                            <div class="mb-3">
                                <label for="branch" class="form-label">Chi nhánh</label>
                                <input type="text" class="form-control" id="branch" name="branch" 
                                       value="{{ $bankingAccount->branch }}">
                                <div class="invalid-feedback"></div>
                                <div class="form-text">Chi nhánh ngân hàng (tùy chọn)</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                                   {{ $bankingAccount->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">
                                                Hoạt động
                                            </label>
                                        </div>
                                        <div class="form-text">Tài khoản này có đang hoạt động không?</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" 
                                                   {{ $bankingAccount->is_default ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_default">
                                                Đặt làm mặc định
                                            </label>
                                        </div>
                                        <div class="form-text">Tài khoản này sẽ được sử dụng mặc định cho thanh toán</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Ghi chú</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3">{{ $bankingAccount->notes }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('staff.organization-banking.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Hủy
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Cập nhật
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
document.getElementById('bankingAccountForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Clear previous validation
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    
    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang cập nhật...';
    submitBtn.disabled = true;
    
    // Prepare form data
    const formData = new FormData(this);
    
    // Convert checkboxes
    formData.set('is_active', document.getElementById('is_active').checked ? '1' : '0');
    formData.set('is_default', document.getElementById('is_default').checked ? '1' : '0');
    
    // Submit
    fetch('{{ route("staff.organization-banking.update", $bankingAccount->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-HTTP-Method-Override': 'PUT'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            Notify.success(data.message, 'Thành công!');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            }
        } else {
            // Show validation errors
            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    const input = document.getElementById(field);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.nextElementSibling;
                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                            feedback.textContent = data.errors[field][0];
                        }
                    }
                });
            }
            Notify.error(data.message, 'Lỗi!');
        }
    })
    .catch(error => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        console.error('Error:', error);
        Notify.error('Có lỗi xảy ra. Vui lòng thử lại.', 'Lỗi hệ thống');
    });
});
</script>
@endpush

