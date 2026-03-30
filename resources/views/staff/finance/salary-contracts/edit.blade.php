@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa hợp đồng lương')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Chỉnh sửa hợp đồng lương',
            'subtitle' => 'Cập nhật thông tin hợp đồng lương #' . $salaryContract->id,
            'icon' => 'fas fa-file-contract',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.salary-contracts.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.salary-contracts.show', $salaryContract->id)
                ]
            ]
        ])

        <form id="edit-salary-contract-form" method="POST" action="{{ route('staff.salary-contracts.update', $salaryContract->id) }}">
            @csrf
            @method('PUT')
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
                                                <option value="{{ $user->id }}" {{ old('user_id', $salaryContract->user_id) == $user->id ? 'selected' : '' }}>
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
                                                   value="{{ old('base_salary', $salaryContract->base_salary) ? number_format((float)old('base_salary', $salaryContract->base_salary), 0, ',', '.') : '' }}" required>
                                            <select name="currency" class="form-control @error('currency') is-invalid @enderror" style="max-width: 100px;">
                                                <option value="VND" {{ old('currency', $salaryContract->currency) == 'VND' ? 'selected' : '' }}>VND</option>
                                                <option value="USD" {{ old('currency', $salaryContract->currency) == 'USD' ? 'selected' : '' }}>USD</option>
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
                                                <option value="{{ $key }}" {{ old('pay_cycle', $salaryContract->pay_cycle) == $key ? 'selected' : '' }}>
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
                                               value="{{ old('pay_day', $salaryContract->pay_day) }}" min="1" max="31" required>
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
                                                <option value="{{ $key }}" {{ old('status', $salaryContract->status) == $key ? 'selected' : '' }}>
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
                                               value="{{ old('effective_from', $salaryContract->effective_from->format('Y-m-d')) }}" required>
                                        @error('effective_from')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="effective_to" class="form-label">Ngày hết hạn</label>
                                        <input type="date" name="effective_to" id="effective_to" class="form-control @error('effective_to') is-invalid @enderror" 
                                               value="{{ old('effective_to', $salaryContract->effective_to ? $salaryContract->effective_to->format('Y-m-d') : '') }}">
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
                                @if($salaryContract->allowances_json && count($salaryContract->allowances_json) > 0)
                                    @foreach($salaryContract->allowances_json as $name => $amount)
                                        <div class="col-md-4">
                                            <div class="input-group mb-2">
                                                <input type="text" name="allowance_names[]" class="form-control" placeholder="Tên phụ cấp" value="{{ $name }}">
                                                <input type="text" name="allowance_amounts[]" class="form-control money-input" placeholder="Số tiền" value="{{ number_format((float)$amount, 0, ',', '.') }}">
                                                <button type="button" class="btn btn-outline-danger" onclick="removeAllowance(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="col-md-4">
                                        <div class="input-group mb-2">
                                            <input type="text" name="allowance_names[]" class="form-control" placeholder="Tên phụ cấp">
                                            <input type="text" name="allowance_amounts[]" class="form-control money-input" placeholder="Số tiền">
                                            <button type="button" class="btn btn-outline-danger" onclick="removeAllowance(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endif
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
                                @if($salaryContract->kpi_target_json && count($salaryContract->kpi_target_json) > 0)
                                    @foreach($salaryContract->kpi_target_json as $name => $target)
                                        <div class="col-md-6">
                                            <div class="input-group mb-2">
                                                <input type="text" name="kpi_names[]" class="form-control" placeholder="Tên KPI" value="{{ $name }}">
                                                <input type="text" name="kpi_targets[]" class="form-control money-input" placeholder="Mục tiêu" value="{{ number_format((float)$target, 0, ',', '.') }}">
                                                <button type="button" class="btn btn-outline-danger" onclick="removeKPI(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="col-md-6">
                                        <div class="input-group mb-2">
                                            <input type="text" name="kpi_names[]" class="form-control" placeholder="Tên KPI">
                                            <input type="text" name="kpi_targets[]" class="form-control money-input" placeholder="Mục tiêu">
                                            <button type="button" class="btn btn-outline-danger" onclick="removeKPI(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endif
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
                                        'label' => 'Cập nhật',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.salary-contracts.show', $salaryContract->id)
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    {{-- Card Thông tin hiện tại --}}
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Ngày tạo:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-calendar-plus me-1 text-muted"></i>
                                    {{ $salaryContract->created_at->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold small text-muted mb-1">Cập nhật lần cuối:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-calendar-edit me-1 text-muted"></i>
                                    {{ $salaryContract->updated_at->format('d/m/Y H:i:s') }}
                                </div>
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
    const form = document.getElementById('edit-salary-contract-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
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
            
            // Submit via AJAX
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...';
            submitBtn.disabled = true;
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Notify.success(data.message, 'Thành công!');
                    setTimeout(() => {
                        window.location.href = data.redirect || '{{ route("staff.salary-contracts.show", $salaryContract->id) }}';
                    }, 1500);
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Có lỗi xảy ra khi cập nhật hợp đồng lương', 'Lỗi hệ thống!');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        } catch (error) {
            console.error('Error processing form:', error);
            Notify.error('Có lỗi xảy ra khi xử lý form', 'Lỗi!');
        }
    });
});
</script>
@endpush
