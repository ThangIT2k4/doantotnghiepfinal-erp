@extends('layouts.staff_dashboard')

@section('title', 'Preview Phiếu Lương')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Preview Phiếu Lương</h1>
            <p class="mb-0">Kỳ lương: {{ \Carbon\Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->format('m/Y') }}</p>
        </div>
        <div>
            <a href="{{ route('staff.payroll-cycles.show', $payrollCycle->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <button type="button" class="btn btn-success" onclick="createPayslips()">
                <i class="fas fa-check"></i> Tạo phiếu lương
            </button>
        </div>
    </div>

    @if(count($previewPayslips) > 0)
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    Danh sách Preview Phiếu Lương ({{ count($previewPayslips) }} phiếu)
                </h6>
            </div>
            <div class="card-body">
                <form id="previewForm">
                    @csrf
                    <div class="accordion" id="payslipsAccordion">
                        @foreach($previewPayslips as $index => $payslip)
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header" id="heading{{ $index }}">
                                <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" 
                                        aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" 
                                        aria-controls="collapse{{ $index }}">
                                    <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                        <div>
                                            <strong>{{ $payslip['user_name'] }}</strong>
                                            <br><small class="text-muted">{{ $payslip['user_email'] }}</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary">Tổng lương: {{ number_format($payslip['gross_amount'], 0, ',', '.') }} VND</span>
                                            <br>
                                            <span class="badge bg-success">Thực lĩnh: {{ number_format($payslip['net_amount'], 0, ',', '.') }} VND</span>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" 
                                 aria-labelledby="heading{{ $index }}" data-bs-parent="#payslipsAccordion">
                                <div class="accordion-body">
                                    <input type="hidden" name="payslips[{{ $index }}][user_id]" value="{{ $payslip['user_id'] }}">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <strong>Lương cơ bản:</strong>
                                            <p>{{ number_format($payslip['basic_salary'], 0, ',', '.') }} VND</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Phụ cấp:</strong>
                                            <p>{{ number_format($payslip['allowances'], 0, ',', '.') }} VND</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Hoa hồng:</strong>
                                            <p class="text-success">{{ number_format($payslip['commission'], 0, ',', '.') }} VND</p>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Khấu trừ:</strong>
                                            <p class="text-danger">{{ number_format($payslip['deduction_amount'], 0, ',', '.') }} VND</p>
                                        </div>
                                    </div>

                                    <hr>

                                    <h6 class="mb-3">Chi tiết Items:</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Loại</th>
                                                    <th>Tên</th>
                                                    <th>Dấu</th>
                                                    <th>Số tiền</th>
                                                    <th>Ghi chú</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody id="itemsTable{{ $index }}">
                                                @foreach($payslip['items'] as $itemIndex => $item)
                                                <tr>
                                                    <td>
                                                        <select name="payslips[{{ $index }}][items][{{ $itemIndex }}][item_type]" 
                                                                class="form-select form-select-sm">
                                                            <option value="basic_salary" {{ $item['item_type'] == 'basic_salary' ? 'selected' : '' }}>Lương cơ bản</option>
                                                            <option value="allowance" {{ $item['item_type'] == 'allowance' ? 'selected' : '' }}>Phụ cấp</option>
                                                            <option value="commission" {{ $item['item_type'] == 'commission' ? 'selected' : '' }}>Hoa hồng</option>
                                                            <option value="salary_advance_deduction" {{ $item['item_type'] == 'salary_advance_deduction' ? 'selected' : '' }}>Trừ tạm ứng</option>
                                                            <option value="other" {{ $item['item_type'] == 'other' ? 'selected' : '' }}>Khác</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" 
                                                               name="payslips[{{ $index }}][items][{{ $itemIndex }}][item_name]" 
                                                               value="{{ $item['item_name'] }}" 
                                                               class="form-control form-control-sm">
                                                    </td>
                                                    <td>
                                                        <select name="payslips[{{ $index }}][items][{{ $itemIndex }}][sign]" 
                                                                class="form-select form-select-sm">
                                                            <option value="1" {{ $item['sign'] == 1 ? 'selected' : '' }}>+ (Thu nhập)</option>
                                                            <option value="-1" {{ $item['sign'] == -1 ? 'selected' : '' }}>- (Khấu trừ)</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="number" 
                                                               name="payslips[{{ $index }}][items][{{ $itemIndex }}][amount]" 
                                                               value="{{ $item['amount'] }}" 
                                                               step="0.01" 
                                                               class="form-control form-control-sm item-amount" 
                                                               data-payslip-index="{{ $index }}">
                                                    </td>
                                                    <td>
                                                        <input type="text" 
                                                               name="payslips[{{ $index }}][items][{{ $itemIndex }}][note]" 
                                                               value="{{ $item['note'] ?? '' }}" 
                                                               class="form-control form-control-sm">
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                onclick="removeItem(this, {{ $index }})">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                    <input type="hidden" name="payslips[{{ $index }}][items][{{ $itemIndex }}][ref_type]" value="{{ $item['ref_type'] ?? '' }}">
                                                    <input type="hidden" name="payslips[{{ $index }}][items][{{ $itemIndex }}][ref_id]" value="{{ $item['ref_id'] ?? '' }}">
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <button type="button" class="btn btn-sm btn-primary" 
                                            onclick="addItem({{ $index }})">
                                        <i class="fas fa-plus"></i> Thêm item
                                    </button>

                                    <div class="mt-3">
                                        <strong>Tổng lương:</strong> 
                                        <span id="totalGross{{ $index }}" class="text-primary">
                                            {{ number_format($payslip['gross_amount'], 0, ',', '.') }} VND
                                        </span>
                                        <br>
                                        <strong>Tổng khấu trừ:</strong> 
                                        <span id="totalDeduction{{ $index }}" class="text-danger">
                                            {{ number_format($payslip['deduction_amount'], 0, ',', '.') }} VND
                                        </span>
                                        <br>
                                        <strong>Thực lĩnh:</strong> 
                                        <span id="totalNet{{ $index }}" class="text-success">
                                            {{ number_format($payslip['net_amount'], 0, ',', '.') }} VND
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Không có phiếu lương nào để preview</h5>
                <p class="text-muted">Tất cả nhân viên đã có phiếu lương cho kỳ này.</p>
                <a href="{{ route('staff.payroll-cycles.show', $payrollCycle->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
    @endif
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/notifications.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
let itemCounters = {};
@foreach($previewPayslips as $index => $payslip)
    itemCounters[{{ $index }}] = {{ count($payslip['items']) }};
@endforeach

function addItem(payslipIndex) {
    const tbody = document.getElementById(`itemsTable${payslipIndex}`);
    const itemIndex = itemCounters[payslipIndex] || 0;
    
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <select name="payslips[${payslipIndex}][items][${itemIndex}][item_type]" 
                    class="form-select form-select-sm">
                <option value="basic_salary">Lương cơ bản</option>
                <option value="allowance">Phụ cấp</option>
                <option value="commission">Hoa hồng</option>
                <option value="salary_advance_deduction">Trừ tạm ứng</option>
                <option value="other" selected>Khác</option>
            </select>
        </td>
        <td>
            <input type="text" 
                   name="payslips[${payslipIndex}][items][${itemIndex}][item_name]" 
                   value="" 
                   class="form-control form-control-sm">
        </td>
        <td>
            <select name="payslips[${payslipIndex}][items][${itemIndex}][sign]" 
                    class="form-select form-select-sm">
                <option value="1" selected>+ (Thu nhập)</option>
                <option value="-1">- (Khấu trừ)</option>
            </select>
        </td>
        <td>
            <input type="number" 
                   name="payslips[${payslipIndex}][items][${itemIndex}][amount]" 
                   value="0" 
                   step="0.01" 
                   class="form-control form-control-sm item-amount" 
                   data-payslip-index="${payslipIndex}">
        </td>
        <td>
            <input type="text" 
                   name="payslips[${payslipIndex}][items][${itemIndex}][note]" 
                   value="" 
                   class="form-control form-control-sm">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" 
                    onclick="removeItem(this, ${payslipIndex})">
                <i class="fas fa-trash"></i>
            </button>
        </td>
        <input type="hidden" name="payslips[${payslipIndex}][items][${itemIndex}][ref_type]" value="">
        <input type="hidden" name="payslips[${payslipIndex}][items][${itemIndex}][ref_id]" value="">
    `;
    
    tbody.appendChild(row);
    itemCounters[payslipIndex] = itemIndex + 1;
    
    // Add event listener for amount change
    const amountInput = row.querySelector('.item-amount');
    amountInput.addEventListener('input', () => updateTotals(payslipIndex));
    
    // Add event listener for sign change
    const signSelect = row.querySelector('select[name*="[sign]"]');
    signSelect.addEventListener('change', () => updateTotals(payslipIndex));
}

function removeItem(button, payslipIndex) {
    const row = button.closest('tr');
    row.remove();
    updateTotals(payslipIndex);
}

function updateTotals(payslipIndex) {
    const tbody = document.getElementById(`itemsTable${payslipIndex}`);
    const rows = tbody.querySelectorAll('tr');
    
    let grossAmount = 0;
    let deductionAmount = 0;
    
    rows.forEach(row => {
        const amountInput = row.querySelector('.item-amount');
        const signSelect = row.querySelector('select[name*="[sign]"]');
        
        if (amountInput && signSelect) {
            const amount = parseFloat(amountInput.value) || 0;
            const sign = parseInt(signSelect.value);
            
            if (sign === 1) {
                grossAmount += amount;
            } else {
                deductionAmount += amount;
            }
        }
    });
    
    const netAmount = grossAmount - deductionAmount;
    
    document.getElementById(`totalGross${payslipIndex}`).textContent = 
        formatCurrency(grossAmount) + ' VND';
    document.getElementById(`totalDeduction${payslipIndex}`).textContent = 
        formatCurrency(deductionAmount) + ' VND';
    document.getElementById(`totalNet${payslipIndex}`).textContent = 
        formatCurrency(netAmount) + ' VND';
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(Math.round(amount));
}

// Add event listeners for existing amount inputs
document.addEventListener('DOMContentLoaded', function() {
    @foreach($previewPayslips as $index => $payslip)
        const amountInputs{{ $index }} = document.querySelectorAll(`#itemsTable{{ $index }} .item-amount`);
        amountInputs{{ $index }}.forEach(input => {
            input.addEventListener('input', () => updateTotals({{ $index }}));
        });
        
        const signSelects{{ $index }} = document.querySelectorAll(`#itemsTable{{ $index }} select[name*="[sign]"]`);
        signSelects{{ $index }}.forEach(select => {
            select.addEventListener('change', () => updateTotals({{ $index }}));
        });
    @endforeach
});

function createPayslips() {
    Notify.confirm({
        title: 'Tạo phiếu lương',
        message: 'Bạn có chắc chắn muốn tạo phiếu lương từ preview này?',
        details: 'Hệ thống sẽ tạo phiếu lương với các items đã được chỉnh sửa.',
        type: 'success',
        confirmText: 'Tạo phiếu lương',
        onConfirm: () => {
            const form = document.getElementById('previewForm');
            const formData = new FormData(form);
            
            // Show loading toast
            const loadingToast = Notify.toast({
                title: 'Đang tạo phiếu lương...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });

            fetch(`/staff/payroll-cycles/{{ $payrollCycle->id }}/create-from-preview`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }

                if (data.success) {
                    Notify.success(data.message, 'Tạo phiếu lương thành công!');
                    setTimeout(() => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.href = '/staff/payroll-cycles/{{ $payrollCycle->id }}';
                        }
                    }, 2000);
                } else {
                    Notify.error(data.message, 'Lỗi tạo phiếu lương');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                
                Notify.error('Có lỗi xảy ra khi tạo phiếu lương. Vui lòng thử lại.', 'Lỗi hệ thống');
            });
        }
    });
}
</script>
@endpush

