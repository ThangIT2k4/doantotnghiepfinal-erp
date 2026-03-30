@extends('layouts.staff_dashboard')

@section('title', 'Tạo đơn ứng lương')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Tạo đơn ứng lương',
            'subtitle' => 'Tạo đơn ứng lương mới cho nhân viên',
            'icon' => 'fas fa-plus',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.salary-advances.index')
                ]
            ]
        ])

        <form id="create-salary-advance-form" method="POST" action="{{ route('staff.salary-advances.store') }}">
            @csrf
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    {{-- Card 1: Thông tin đơn ứng lương --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin đơn ứng lương
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="user_id" class="form-label">
                                            Nhân viên <span class="text-danger">*</span>
                                        </label>
                                        <select name="user_id" id="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
                                            <option value="">Chọn nhân viên</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->userProfile->full_name ?? $user->email ?? 'N/A' }} ({{ $user->email }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('user_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">
                                            Số tiền ứng <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" name="amount" id="amount" class="form-control money-input @error('amount') is-invalid @enderror" 
                                                   value="{{ old('amount') ? number_format(old('amount'), 0, ',', '.') : '' }}" 
                                                   placeholder="VD: 5.000.000" min="0" required>
                                            <select name="currency" class="form-control @error('currency') is-invalid @enderror" style="max-width: 100px;">
                                                <option value="VND" {{ old('currency', 'VND') == 'VND' ? 'selected' : '' }}>VND</option>
                                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                                            </select>
                                        </div>
                                        @error('amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @error('currency')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="advance_date" class="form-label">
                                            Ngày ứng <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" name="advance_date" id="advance_date" class="form-control @error('advance_date') is-invalid @enderror" 
                                               value="{{ old('advance_date', date('Y-m-d')) }}" required>
                                        @error('advance_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="expected_repayment_date" class="form-label">
                                            Ngày trả dự kiến <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" name="expected_repayment_date" id="expected_repayment_date" class="form-control @error('expected_repayment_date') is-invalid @enderror" 
                                               value="{{ old('expected_repayment_date') }}" required>
                                        @error('expected_repayment_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="reason" class="form-label">
                                    Lý do ứng lương <span class="text-danger">*</span>
                                </label>
                                <textarea name="reason" id="reason" class="form-control @error('reason') is-invalid @enderror" 
                                          rows="3" required placeholder="Nhập lý do ứng lương...">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="repayment_method" class="form-label">
                                    Phương thức trả <span class="text-danger">*</span>
                                </label>
                                <select name="repayment_method" id="repayment_method" class="form-control @error('repayment_method') is-invalid @enderror" required>
                                    @foreach($repaymentMethods as $key => $label)
                                        <option value="{{ $key }}" {{ old('repayment_method') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('repayment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Conditional fields based on repayment method -->
                            <div id="installment_fields" style="display: none;">
                                <div class="mb-3">
                                    <label for="installment_months" class="form-label">Số tháng trả góp</label>
                                    <input type="number" name="installment_months" id="installment_months" class="form-control @error('installment_months') is-invalid @enderror" 
                                           value="{{ old('installment_months') }}" min="1" max="12">
                                    @error('installment_months')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div id="payroll_deduction_fields" style="display: none;">
                                <div class="mb-3">
                                    <label for="monthly_deduction" class="form-label">Số tiền trừ hàng tháng (VND)</label>
                                    <input type="text" name="monthly_deduction" id="monthly_deduction" class="form-control money-input @error('monthly_deduction') is-invalid @enderror" 
                                           value="{{ old('monthly_deduction') ? number_format(old('monthly_deduction'), 0, ',', '.') : '' }}" 
                                           placeholder="VD: 1.000.000" min="0">
                                    <small class="form-text text-muted">Để trống để tự động tính toán</small>
                                    @error('monthly_deduction')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="note" class="form-label">Ghi chú</label>
                                <textarea name="note" id="note" class="form-control @error('note') is-invalid @enderror" 
                                          rows="2" placeholder="Ghi chú thêm (không bắt buộc)...">{{ old('note') }}</textarea>
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
                                        'url' => route('staff.salary-advances.index')
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
                                <h6><i class="fas fa-info-circle"></i> Phương thức trả</h6>
                                <ul class="mb-0 small">
                                    <li><strong>Trừ lương:</strong> Tự động trừ vào lương hàng tháng</li>
                                    <li><strong>Thanh toán trực tiếp:</strong> Nhân viên trả trực tiếp</li>
                                    <li><strong>Trả góp:</strong> Chia thành nhiều tháng</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle"></i> Lưu ý</h6>
                                <ul class="mb-0 small">
                                    <li>Đơn ứng lương sẽ ở trạng thái "Chờ duyệt"</li>
                                    <li>Cần duyệt trước khi có hiệu lực</li>
                                    <li>Có thể chỉnh sửa khi chưa duyệt</li>
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
<link rel="stylesheet" href="{{ asset('assets/css/user/notifications.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/number-formatter.js') }}"></script>
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const repaymentMethodSelect = document.getElementById('repayment_method');
    const installmentFields = document.getElementById('installment_fields');
    const payrollDeductionFields = document.getElementById('payroll_deduction_fields');
    
    function toggleFields() {
        const method = repaymentMethodSelect.value;
        
        // Hide all conditional fields
        installmentFields.style.display = 'none';
        payrollDeductionFields.style.display = 'none';
        
        // Show relevant fields
        if (method === 'installment') {
            installmentFields.style.display = 'block';
        } else if (method === 'payroll_deduction') {
            payrollDeductionFields.style.display = 'block';
        }
    }
    
    repaymentMethodSelect.addEventListener('change', toggleFields);
    
    // Initialize on page load
    toggleFields();
    
    // Set default expected repayment date (1 month from advance date)
    const advanceDateInput = document.getElementById('advance_date');
    const expectedRepaymentDateInput = document.getElementById('expected_repayment_date');
    
    advanceDateInput.addEventListener('change', function() {
        if (this.value && !expectedRepaymentDateInput.value) {
            const advanceDate = new Date(this.value);
            const expectedDate = new Date(advanceDate);
            expectedDate.setMonth(expectedDate.getMonth() + 1);
            expectedRepaymentDateInput.value = expectedDate.toISOString().split('T')[0];
        }
    });
    
    // Unformat money inputs before form submission
    const form = document.getElementById('create-salary-advance-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Process money inputs before submit
            if (window.NumberFormatter && window.NumberFormatter.processForm) {
                window.NumberFormatter.processForm(this);
            } else {
                // Fallback: manually unformat money inputs
                const moneyInputs = this.querySelectorAll('.money-input');
                moneyInputs.forEach(input => {
                    if (input.value) {
                        // Remove dots and other non-digit characters
                        input.value = input.value.replace(/\./g, '').replace(/\D/g, '');
                    }
                });
            }
        });
    }
});
</script>
@endpush
