@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa đơn ứng lương')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Chỉnh sửa đơn ứng lương',
            'subtitle' => 'Cập nhật thông tin đơn ứng lương #' . $salaryAdvance->id,
            'icon' => 'fas fa-edit',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.salary-advances.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.salary-advances.show', $salaryAdvance->id)
                ]
            ]
        ])

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin đơn ứng lương</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('staff.salary-advances.update', $salaryAdvance->id) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="user_id">Nhân viên <span class="text-danger">*</span></label>
                                    <select name="user_id" id="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
                                        <option value="">Chọn nhân viên</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('user_id', $salaryAdvance->user_id) == $user->id ? 'selected' : '' }}>
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
                                <div class="form-group">
                                    <label for="amount">Số tiền ứng <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="amount" id="amount" class="form-control money-input @error('amount') is-invalid @enderror" 
                                               value="{{ old('amount', $salaryAdvance->amount ? number_format($salaryAdvance->amount, 0, ',', '.') : '') }}" 
                                               placeholder="VD: 5.000.000" min="0" required>
                                        <select name="currency" class="form-control @error('currency') is-invalid @enderror" style="max-width: 100px;">
                                            <option value="VND" {{ old('currency', $salaryAdvance->currency) == 'VND' ? 'selected' : '' }}>VND</option>
                                            <option value="USD" {{ old('currency', $salaryAdvance->currency) == 'USD' ? 'selected' : '' }}>USD</option>
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
                                <div class="form-group">
                                    <label for="advance_date">Ngày ứng <span class="text-danger">*</span></label>
                                    <input type="date" name="advance_date" id="advance_date" class="form-control @error('advance_date') is-invalid @enderror" 
                                           value="{{ old('advance_date', $salaryAdvance->advance_date->format('Y-m-d')) }}" required>
                                    @error('advance_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expected_repayment_date">Ngày trả dự kiến <span class="text-danger">*</span></label>
                                    <input type="date" name="expected_repayment_date" id="expected_repayment_date" class="form-control @error('expected_repayment_date') is-invalid @enderror" 
                                           value="{{ old('expected_repayment_date', $salaryAdvance->expected_repayment_date->format('Y-m-d')) }}" required>
                                    @error('expected_repayment_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reason">Lý do ứng lương <span class="text-danger">*</span></label>
                            <textarea name="reason" id="reason" class="form-control @error('reason') is-invalid @enderror" 
                                      rows="3" required placeholder="Nhập lý do ứng lương...">{{ old('reason', $salaryAdvance->reason) }}</textarea>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="repayment_method">Phương thức trả <span class="text-danger">*</span></label>
                            <select name="repayment_method" id="repayment_method" class="form-control @error('repayment_method') is-invalid @enderror" required>
                                @foreach($repaymentMethods as $key => $label)
                                    <option value="{{ $key }}" {{ old('repayment_method', $salaryAdvance->repayment_method) == $key ? 'selected' : '' }}>
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
                            <div class="form-group">
                                <label for="installment_months">Số tháng trả góp</label>
                                <input type="number" name="installment_months" id="installment_months" class="form-control @error('installment_months') is-invalid @enderror" 
                                       value="{{ old('installment_months', $salaryAdvance->installment_months) }}" min="1" max="12">
                                @error('installment_months')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div id="payroll_deduction_fields" style="display: none;">
                            <div class="form-group">
                                <label for="monthly_deduction">Số tiền trừ hàng tháng (VND)</label>
                                <input type="text" name="monthly_deduction" id="monthly_deduction" class="form-control money-input @error('monthly_deduction') is-invalid @enderror" 
                                       value="{{ old('monthly_deduction', $salaryAdvance->monthly_deduction ? number_format($salaryAdvance->monthly_deduction, 0, ',', '.') : '') }}" 
                                       placeholder="VD: 1.000.000" min="0">
                                <small class="form-text text-muted">Để trống để tự động tính toán</small>
                                @error('monthly_deduction')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="note">Ghi chú</label>
                            <textarea name="note" id="note" class="form-control @error('note') is-invalid @enderror" 
                                      rows="2" placeholder="Ghi chú thêm (không bắt buộc)...">{{ old('note', $salaryAdvance->note) }}</textarea>
                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Form Actions --}}
                        <div class="card shadow-sm mt-4">
                            <div class="card-body">
                                @include('staff.components.action-buttons', [
                                    'layout' => 'horizontal',
                                    'size' => 'md',
                                    'actions' => [
                                        [
                                            'type' => 'submit',
                                            'variant' => 'primary',
                                            'label' => 'Cập nhật đơn ứng lương',
                                            'icon' => 'fas fa-save'
                                        ],
                                        [
                                            'type' => 'link',
                                            'variant' => 'secondary',
                                            'label' => 'Hủy',
                                            'icon' => 'fas fa-times',
                                            'url' => route('staff.salary-advances.show', $salaryAdvance->id)
                                        ]
                                    ]
                                ])
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin hiện tại</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Lưu ý:</strong> Chỉ có thể chỉnh sửa đơn ứng lương đang chờ duyệt hoặc đã từ chối.
                    </div>
                    
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td><strong>Trạng thái:</strong></td>
                            <td>
                                <span class="badge bg-{{ $salaryAdvance->status_color }}">
                                    {{ $salaryAdvance->status_label }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Ngày tạo:</strong></td>
                            <td>{{ $salaryAdvance->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Cập nhật cuối:</strong></td>
                            <td>{{ $salaryAdvance->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
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
    
    // Unformat money inputs before form submission
    document.querySelector('form').addEventListener('submit', function(e) {
        e.preventDefault();
        
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
        
        // Submit via AJAX
        const formData = new FormData(this);
        
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }
            
            if (data.success) {
                Notify.success(data.message || 'Đã cập nhật thành công!', 'Thành công!');
                setTimeout(() => {
                    window.location.href = data.redirect || '{{ route("staff.salary-advances.show", $salaryAdvance->id) }}';
                }, 1500);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể cập nhật: ' + error.message, 'Lỗi hệ thống!');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
});
</script>
@endpush
