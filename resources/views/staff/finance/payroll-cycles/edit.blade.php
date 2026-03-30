@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa Kỳ Lương')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Chỉnh sửa Kỳ Lương',
            'subtitle' => 'Cập nhật thông tin kỳ lương: ' . \Carbon\Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->format('m/Y'),
            'icon' => 'fas fa-edit',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.payroll-cycles.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.payroll-cycles.show', $payrollCycle->id)
                ]
            ]
        ])

        <form id="edit-payroll-cycle-form" method="POST" action="{{ route('staff.payroll-cycles.update', $payrollCycle->id) }}">
            @csrf
            @method('PUT')
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    {{-- Card 1: Thông tin Kỳ Lương --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin Kỳ Lương
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kỳ lương</label>
                                    <div class="form-control-plaintext">
                                        <strong>{{ \Carbon\Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->format('m/Y') }}</strong>
                                        <small class="text-muted d-block mt-1">Không thể thay đổi kỳ lương</small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Trạng thái</label>
                                    <div class="form-control-plaintext">
                                        @include('staff.components.status-badge', [
                                            'status' => $payrollCycle->status,
                                            'type' => 'payroll-cycle'
                                        ])
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="note" class="form-label">Ghi chú</label>
                                    <textarea class="form-control @error('note') is-invalid @enderror" 
                                              id="note" 
                                              name="note" 
                                              rows="3" 
                                              placeholder="Ghi chú về kỳ lương...">{{ old('note', $payrollCycle->note) }}</textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card 2: Danh sách Payslips và Items --}}
                    @if($payrollCycle->payslips && $payrollCycle->payslips->count() > 0)
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>Danh sách Phiếu Lương
                            </h6>
                        </div>
                        <div class="card-body">
                            @foreach($payrollCycle->payslips as $payslipIndex => $payslip)
                            <div class="payslip-section mb-4 border rounded p-3" data-payslip-id="{{ $payslip->id }}">
                                <input type="hidden" name="payslips[{{ $payslipIndex }}][id]" value="{{ $payslip->id }}">
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-1">
                                            <strong>{{ $payslip->user->userProfile->full_name ?? $payslip->user->email }}</strong>
                                        </h6>
                                        <small class="text-muted">{{ $payslip->user->email }}</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="mb-1">
                                            <small class="text-muted">Tổng lương:</small>
                                            <strong class="text-primary" id="gross-amount-{{ $payslip->id }}">
                                                {{ number_format($payslip->gross_amount, 0, ',', '.') }} VND
                                            </strong>
                                        </div>
                                        <div class="mb-1">
                                            <small class="text-muted">Khấu trừ:</small>
                                            <span class="text-danger" id="deduction-amount-{{ $payslip->id }}">
                                                {{ number_format($payslip->deduction_amount, 0, ',', '.') }} VND
                                            </span>
                                        </div>
                                        <div>
                                            <small class="text-muted">Thực lĩnh:</small>
                                            <strong class="text-success" id="net-amount-{{ $payslip->id }}">
                                                {{ number_format($payslip->net_amount, 0, ',', '.') }} VND
                                            </strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered items-table" data-payslip-id="{{ $payslip->id }}">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 15%">Loại</th>
                                                <th style="width: 25%">Tên</th>
                                                <th style="width: 10%">Dấu</th>
                                                <th style="width: 20%">Số tiền (VND)</th>
                                                <th style="width: 20%">Ghi chú</th>
                                                <th style="width: 10%">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="items-tbody-{{ $payslip->id }}">
                                            @php
                                                $itemIndex = 0;
                                            @endphp
                                            @foreach($payslip->items as $item)
                                            <tr class="item-row">
                                                <td>
                                                    <select name="payslips[{{ $payslipIndex }}][items][{{ $itemIndex }}][item_type]" 
                                                            class="form-select form-select-sm">
                                                        <option value="basic_salary" {{ $item->item_type === 'basic_salary' ? 'selected' : '' }}>Lương cơ bản</option>
                                                        <option value="allowance" {{ $item->item_type === 'allowance' ? 'selected' : '' }}>Phụ cấp</option>
                                                        <option value="commission" {{ $item->item_type === 'commission' ? 'selected' : '' }}>Hoa hồng</option>
                                                        <option value="salary_advance_deduction" {{ $item->item_type === 'salary_advance_deduction' ? 'selected' : '' }}>Trừ tạm ứng</option>
                                                        <option value="other" {{ $item->item_type === 'other' ? 'selected' : '' }}>Khác</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           name="payslips[{{ $payslipIndex }}][items][{{ $itemIndex }}][item_name]" 
                                                           value="{{ $item->item_name }}" 
                                                           class="form-control form-control-sm" required>
                                                </td>
                                                <td>
                                                    <select name="payslips[{{ $payslipIndex }}][items][{{ $itemIndex }}][sign]" 
                                                            class="form-select form-select-sm item-sign" 
                                                            data-payslip-id="{{ $payslip->id }}">
                                                        <option value="1" {{ $item->sign == 1 ? 'selected' : '' }}>+ (Thu nhập)</option>
                                                        <option value="-1" {{ $item->sign == -1 ? 'selected' : '' }}>- (Khấu trừ)</option>
                                                    </select>
                                                </td>
                                                <td>
                                                <input type="number" 
                                                       name="payslips[{{ $payslipIndex }}][items][{{ $itemIndex }}][amount]" 
                                                       value="{{ abs($item->amount) }}" 
                                                       step="0.01" 
                                                       min="0"
                                                       class="form-control form-control-sm item-amount" 
                                                       data-payslip-id="{{ $payslip->id }}" 
                                                       required>
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           name="payslips[{{ $payslipIndex }}][items][{{ $itemIndex }}][note]" 
                                                           value="{{ $item->note }}" 
                                                           class="form-control form-control-sm">
                                                </td>
                                                <td>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger remove-item" 
                                                            data-payslip-id="{{ $payslip->id }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @php
                                                $itemIndex++;
                                            @endphp
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="6">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-primary add-item" 
                                                            data-payslip-id="{{ $payslip->id }}"
                                                            data-payslip-index="{{ $payslipIndex }}">
                                                        <i class="fas fa-plus me-1"></i>Thêm item
                                                    </button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
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
                                        'url' => route('staff.payroll-cycles.show', $payrollCycle->id)
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
                                    {{ $payrollCycle->created_at->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold small text-muted mb-1">Cập nhật lần cuối:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-calendar-edit me-1 text-muted"></i>
                                    {{ $payrollCycle->updated_at->format('d/m/Y H:i:s') }}
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

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Item counters for each payslip
    const itemCounters = {};
    @foreach($payrollCycle->payslips as $payslip)
    itemCounters[{{ $payslip->id }}] = {{ $payslip->items->count() }};
    @endforeach

    // Function to calculate totals for a payslip
    function calculatePayslipTotals(payslipId) {
        const tbody = document.getElementById(`items-tbody-${payslipId}`);
        if (!tbody) return;

        let grossAmount = 0;
        let deductionAmount = 0;

        tbody.querySelectorAll('.item-row').forEach(row => {
            const sign = parseInt(row.querySelector('.item-sign').value);
            const amount = parseFloat(row.querySelector('.item-amount').value) || 0;

            // Dựa trên sign: 1 = thu nhập, -1 = khấu trừ
            if (sign === 1) {
                grossAmount += amount;
            } else {
                deductionAmount += amount;
            }
        });

        const netAmount = grossAmount - deductionAmount;

        // Update display
        document.getElementById(`gross-amount-${payslipId}`).textContent = 
            grossAmount.toLocaleString('vi-VN') + ' VND';
        document.getElementById(`deduction-amount-${payslipId}`).textContent = 
            deductionAmount.toLocaleString('vi-VN') + ' VND';
        document.getElementById(`net-amount-${payslipId}`).textContent = 
            netAmount.toLocaleString('vi-VN') + ' VND';
    }

    // Add item button
    document.querySelectorAll('.add-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const payslipId = this.dataset.payslipId;
            const payslipIndex = this.dataset.payslipIndex;
            const tbody = document.getElementById(`items-tbody-${payslipId}`);
            const itemIndex = itemCounters[payslipId] || 0;

            const row = document.createElement('tr');
            row.className = 'item-row';
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
                           class="form-control form-control-sm" required>
                </td>
                <td>
                    <select name="payslips[${payslipIndex}][items][${itemIndex}][sign]" 
                            class="form-select form-select-sm item-sign" 
                            data-payslip-id="${payslipId}">
                        <option value="1" selected>+ (Thu nhập)</option>
                        <option value="-1">- (Khấu trừ)</option>
                    </select>
                </td>
                <td>
                    <input type="number" 
                           name="payslips[${payslipIndex}][items][${itemIndex}][amount]" 
                           value="0" 
                           step="0.01" 
                           min="0"
                           class="form-control form-control-sm item-amount" 
                           data-payslip-id="${payslipId}" 
                           required>
                </td>
                <td>
                    <input type="text" 
                           name="payslips[${payslipIndex}][items][${itemIndex}][note]" 
                           value="" 
                           class="form-control form-control-sm">
                </td>
                <td>
                    <button type="button" 
                            class="btn btn-sm btn-danger remove-item" 
                            data-payslip-id="${payslipId}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(row);
            itemCounters[payslipId] = itemIndex + 1;

            // Attach event listeners to new row
            attachItemListeners(row, payslipId);
            calculatePayslipTotals(payslipId);
        });
    });

    // Remove item button
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const payslipId = this.dataset.payslipId;
            const row = this.closest('.item-row');
            row.remove();
            calculatePayslipTotals(payslipId);
        });
    });

    // Attach listeners to existing items
    function attachItemListeners(row, payslipId) {
        const signSelect = row.querySelector('.item-sign');
        const amountInput = row.querySelector('.item-amount');
        const removeBtn = row.querySelector('.remove-item');

        if (signSelect) {
            signSelect.addEventListener('change', () => calculatePayslipTotals(payslipId));
        }
        if (amountInput) {
            amountInput.addEventListener('input', () => calculatePayslipTotals(payslipId));
            amountInput.addEventListener('change', () => calculatePayslipTotals(payslipId));
            // Ngăn nhập số âm
            amountInput.addEventListener('keydown', function(e) {
                if (e.key === '-' || e.key === 'e' || e.key === 'E' || e.key === '+') {
                    e.preventDefault();
                }
            });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                row.remove();
                calculatePayslipTotals(payslipId);
            });
        }
    }

    // Initialize listeners for all existing items
    document.querySelectorAll('.item-row').forEach(row => {
        const payslipId = row.closest('.items-table').dataset.payslipId;
        attachItemListeners(row, payslipId);
    });

    // Form submission
    const form = document.getElementById('edit-payroll-cycle-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate: Check if total matches items for each payslip
        let isValid = true;
        let errorMessage = '';

        @foreach($payrollCycle->payslips as $payslip)
        const payslipId{{ $payslip->id }} = {{ $payslip->id }};
        const tbody{{ $payslip->id }} = document.getElementById(`items-tbody-${payslipId{{ $payslip->id }}}`);
        if (tbody{{ $payslip->id }}) {
            let calculatedGross = 0;
            let calculatedDeduction = 0;
            
            tbody{{ $payslip->id }}.querySelectorAll('.item-row').forEach(row => {
                const sign = parseInt(row.querySelector('.item-sign').value);
                const amount = parseFloat(row.querySelector('.item-amount').value) || 0;
                
                if (sign === 1) {
                    calculatedGross += amount;
                } else {
                    calculatedDeduction += amount;
                }
            });
            
            const calculatedNet = calculatedGross - calculatedDeduction;
            
            // Note: We don't validate against existing payslip amounts since we're updating them
            // The constraint is that gross_amount = sum of items with sign=1
            // This is already enforced by the calculation above
        }
        @endforeach

        if (!isValid) {
            Notify.error(errorMessage, 'Lỗi validation!');
            return;
        }
        
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        
        // Show loading
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang cập nhật...';
        
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
                    window.location.href = data.redirect || '{{ route("staff.payroll-cycles.show", $payrollCycle->id) }}';
                }, 1500);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi cập nhật kỳ lương', 'Lỗi hệ thống!');
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        });
    });
});
</script>
@endpush
