@extends('layouts.staff_dashboard')

@section('title', 'Tạo hợp đồng lương')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Tạo hợp đồng lương',
            'subtitle' => 'Tạo hợp đồng lương mới cho nhân viên',
            'icon' => 'fas fa-file-contract',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.salary-contracts.index')
                ]
            ]
        ])

        <form id="create-salary-contract-form" method="POST" action="{{ route('staff.salary-contracts.store') }}">
            @csrf
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    {{-- Card 1: Thông tin hợp đồng lương --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin hợp đồng lương
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
                                                    {{ $user->full_name ?? 'N/A' }} ({{ $user->email }})
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
                                        <label for="base_salary" class="form-label">
                                            Lương cơ bản <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="text" name="base_salary" id="base_salary" class="form-control money-input @error('base_salary') is-invalid @enderror" 
                                                   value="{{ old('base_salary') ? number_format((float)old('base_salary'), 0, ',', '.') : '' }}" required>
                                            <select name="currency" class="form-control @error('currency') is-invalid @enderror" style="max-width: 100px;">
                                                <option value="VND" {{ old('currency', 'VND') == 'VND' ? 'selected' : '' }}>VND</option>
                                                <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
                                            </select>
                                        </div>
                                        @error('base_salary')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @error('currency')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="pay_cycle" class="form-label">
                                            Chu kỳ trả lương <span class="text-danger">*</span>
                                        </label>
                                        <select name="pay_cycle" id="pay_cycle" class="form-control @error('pay_cycle') is-invalid @enderror" required>
                                            @foreach($payCycles as $key => $label)
                                                <option value="{{ $key }}" {{ old('pay_cycle') == $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('pay_cycle')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="pay_day" class="form-label">
                                            Ngày trả lương <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="pay_day" id="pay_day" class="form-control @error('pay_day') is-invalid @enderror" 
                                               value="{{ old('pay_day', 1) }}" min="1" max="31" required>
                                        <small class="form-text text-muted">Ngày trong tháng (1-31)</small>
                                        @error('pay_day')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">
                                            Trạng thái <span class="text-danger">*</span>
                                        </label>
                                        <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                            @foreach($statuses as $key => $label)
                                                <option value="{{ $key }}" {{ old('status', 'active') == $key ? 'selected' : '' }}>
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
                                    <div class="mb-3">
                                        <label for="effective_from" class="form-label">
                                            Ngày hiệu lực <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" name="effective_from" id="effective_from" class="form-control @error('effective_from') is-invalid @enderror" 
                                               value="{{ old('effective_from', date('Y-m-d')) }}" required>
                                        @error('effective_from')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="effective_to" class="form-label">Ngày hết hạn</label>
                                        <input type="date" name="effective_to" id="effective_to" class="form-control @error('effective_to') is-invalid @enderror" 
                                               value="{{ old('effective_to') }}">
                                        <small class="form-text text-muted">Để trống nếu không có thời hạn</small>
                                        @error('effective_to')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card 2: Phụ cấp --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-money-bill-wave me-2"></i>Phụ cấp
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row" id="allowances-container">
                                <div class="col-md-4">
                                    <div class="input-group mb-2">
                                        <input type="text" name="allowance_names[]" class="form-control" placeholder="Tên phụ cấp" value="Phụ cấp ăn trưa">
                                        <input type="text" name="allowance_amounts[]" class="form-control money-input" placeholder="Số tiền" value="{{ number_format(500000, 0, ',', '.') }}">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeAllowance(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group mb-2">
                                        <input type="text" name="allowance_names[]" class="form-control" placeholder="Tên phụ cấp" value="Phụ cấp xăng xe">
                                        <input type="text" name="allowance_amounts[]" class="form-control money-input" placeholder="Số tiền" value="{{ number_format(300000, 0, ',', '.') }}">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeAllowance(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group mb-2">
                                        <input type="text" name="allowance_names[]" class="form-control" placeholder="Tên phụ cấp" value="Phụ cấp điện thoại">
                                        <input type="text" name="allowance_amounts[]" class="form-control money-input" placeholder="Số tiền" value="{{ number_format(200000, 0, ',', '.') }}">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeAllowance(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addAllowance()">
                                <i class="fas fa-plus"></i> Thêm phụ cấp
                            </button>
                        </div>
                    </div>

                    {{-- Card 3: Mục tiêu KPI --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-bullseye me-2"></i>Mục tiêu KPI
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row" id="kpi-container">
                                <div class="col-md-6">
                                    <div class="input-group mb-2">
                                        <input type="text" name="kpi_names[]" class="form-control" placeholder="Tên KPI" value="Doanh số bán hàng">
                                        <input type="text" name="kpi_targets[]" class="form-control money-input" placeholder="Mục tiêu" value="{{ number_format(10000000, 0, ',', '.') }}">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeKPI(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group mb-2">
                                        <input type="text" name="kpi_names[]" class="form-control" placeholder="Tên KPI" value="Tỷ lệ hoa hồng">
                                        <input type="text" name="kpi_targets[]" class="form-control number-input" placeholder="Mục tiêu (%)" value="5">
                                        <button type="button" class="btn btn-outline-danger" onclick="removeKPI(this)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addKPI()">
                                <i class="fas fa-plus"></i> Thêm KPI
                            </button>
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
                                        'url' => route('staff.salary-contracts.index')
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
                                <h6><i class="fas fa-info-circle"></i> Chu kỳ trả lương</h6>
                                <ul class="mb-0 small">
                                    <li><strong>Hàng tháng:</strong> Trả lương mỗi tháng</li>
                                    <li><strong>Hàng tuần:</strong> Trả lương mỗi tuần</li>
                                    <li><strong>Hàng ngày:</strong> Trả lương mỗi ngày</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle"></i> Lưu ý</h6>
                                <ul class="mb-0 small">
                                    <li>Mỗi nhân viên chỉ có 1 hợp đồng hoạt động</li>
                                    <li>Có thể chỉnh sửa khi chưa chấm dứt</li>
                                    <li>Phụ cấp và KPI có thể để trống</li>
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
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script src="{{ asset('assets/js/number-formatter.js') }}"></script>
<script>
function addAllowance() {
    const container = document.getElementById('allowances-container');
    const newRow = document.createElement('div');
    newRow.className = 'col-md-4';
    newRow.innerHTML = `
        <div class="input-group mb-2">
            <input type="text" name="allowance_names[]" class="form-control" placeholder="Tên phụ cấp">
            <input type="text" name="allowance_amounts[]" class="form-control money-input" placeholder="Số tiền">
            <button type="button" class="btn btn-outline-danger" onclick="removeAllowance(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
    // Initialize number formatter for new input
    if (typeof initNumberFormatter === 'function') {
        initNumberFormatter(newRow);
    }
}

function removeAllowance(button) {
    button.closest('.col-md-4').remove();
}

function addKPI() {
    const container = document.getElementById('kpi-container');
    const newRow = document.createElement('div');
    newRow.className = 'col-md-6';
    newRow.innerHTML = `
        <div class="input-group mb-2">
            <input type="text" name="kpi_names[]" class="form-control" placeholder="Tên KPI">
            <input type="text" name="kpi_targets[]" class="form-control money-input" placeholder="Mục tiêu">
            <button type="button" class="btn btn-outline-danger" onclick="removeKPI(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
    // Initialize number formatter for new input
    if (typeof initNumberFormatter === 'function') {
        initNumberFormatter(newRow);
    }
}

function removeKPI(button) {
    button.closest('.col-md-6').remove();
}

// Process form data before submit
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('create-salary-contract-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        try {
            // Helper function to unformat values
            const unformatValue = function(value) {
                if (!value) return '';
                return value.toString().replace(/\./g, '');
            };
            
            // Use NumberFormatter.processForm if available
            if (window.NumberFormatter && typeof window.NumberFormatter.processForm === 'function') {
                window.NumberFormatter.processForm(form);
            } else {
                // Fallback: manually unformat all number inputs
                // Unformat base_salary
                const baseSalaryInput = document.getElementById('base_salary');
                if (baseSalaryInput && baseSalaryInput.value) {
                    baseSalaryInput.value = unformatValue(baseSalaryInput.value);
                }
                
                // Unformat allowance amounts
                const allowanceAmounts = document.querySelectorAll('input[name="allowance_amounts[]"]');
                allowanceAmounts.forEach(input => {
                    if (input.value) {
                        input.value = unformatValue(input.value);
                    }
                });
                
                // Unformat KPI targets
                const kpiTargets = document.querySelectorAll('input[name="kpi_targets[]"]');
                kpiTargets.forEach(input => {
                    if (input.value) {
                        input.value = unformatValue(input.value);
                    }
                });
            }
            
            // Process allowances (after unformat)
            const allowanceNames = document.querySelectorAll('input[name="allowance_names[]"]');
            const allowanceAmounts = document.querySelectorAll('input[name="allowance_amounts[]"]');
            const allowances = {};
            
            for (let i = 0; i < allowanceNames.length; i++) {
                const name = allowanceNames[i].value.trim();
                let amount = 0;
                if (allowanceAmounts[i] && allowanceAmounts[i].value) {
                    // Value should already be unformatted by NumberFormatter.processForm or fallback
                    // But ensure it's unformatted by removing dots
                    const unformatted = allowanceAmounts[i].value.replace(/\./g, '');
                    amount = parseFloat(unformatted) || 0;
                }
                if (name && amount > 0) {
                    allowances[name] = amount;
                }
            }
            
            // Add hidden input for allowances
            const allowanceInput = document.createElement('input');
            allowanceInput.type = 'hidden';
            allowanceInput.name = 'allowances_json';
            allowanceInput.value = JSON.stringify(allowances);
            form.appendChild(allowanceInput);
            
            // Process KPI targets (after unformat)
            const kpiNames = document.querySelectorAll('input[name="kpi_names[]"]');
            const kpiTargets = document.querySelectorAll('input[name="kpi_targets[]"]');
            const kpiTargetsObj = {};
            
            for (let i = 0; i < kpiNames.length; i++) {
                const name = kpiNames[i].value.trim();
                let target = 0;
                if (kpiTargets[i] && kpiTargets[i].value) {
                    // Value should already be unformatted by NumberFormatter.processForm or fallback
                    // But ensure it's unformatted by removing dots
                    const unformatted = kpiTargets[i].value.replace(/\./g, '');
                    target = parseFloat(unformatted) || 0;
                }
                if (name && target > 0) {
                    kpiTargetsObj[name] = target;
                }
            }
            
            // Add hidden input for KPI targets
            const kpiInput = document.createElement('input');
            kpiInput.type = 'hidden';
            kpiInput.name = 'kpi_target_json';
            kpiInput.value = JSON.stringify(kpiTargetsObj);
            form.appendChild(kpiInput);
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                submitBtn.disabled = true;
                
                // Re-enable button after 5 seconds as fallback
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            }
        } catch (error) {
            console.error('Error processing form:', error);
            // Allow form to submit anyway
        }
    });
});
</script>
@endpush
