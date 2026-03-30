@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa Phiếu Lương')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Chỉnh sửa Phiếu Lương',
            'subtitle' => 'Cập nhật thông tin phiếu lương: ' . ($payrollPayslip->user->userProfile->full_name ?? $payrollPayslip->user->email) . ' - ' . \Carbon\Carbon::createFromFormat('Y-m', $payrollPayslip->payrollCycle->period_month)->format('m/Y'),
            'icon' => 'fas fa-edit',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.payroll-payslips.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.payroll-payslips.show', $payrollPayslip->id)
                ]
            ]
        ])

        <form id="edit-payslip-form" method="POST" action="{{ route('staff.payroll-payslips.update', $payrollPayslip->id) }}">
            @csrf
            @method('PUT')
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    {{-- Card 1: Thông tin cơ bản --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin Phiếu Lương
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nhân viên</label>
                                    <div class="form-control-plaintext">
                                        <strong>{{ $payrollPayslip->user->userProfile->full_name ?? $payrollPayslip->user->email }}</strong>
                                        <br><small class="text-muted">{{ $payrollPayslip->user->email }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kỳ lương</label>
                                    <div class="form-control-plaintext">
                                        <span class="badge bg-secondary">
                                            {{ \Carbon\Carbon::createFromFormat('Y-m', $payrollPayslip->payrollCycle->period_month)->format('m/Y') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card 2: Chi tiết Items --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>Chi tiết Phiếu Lương
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1">Tổng hợp</h6>
                                </div>
                                <div class="text-end">
                                    <div class="mb-1">
                                        <small class="text-muted">Tổng lương:</small>
                                        <strong class="text-primary" id="gross-amount-display">
                                            {{ number_format($payrollPayslip->gross_amount, 0, ',', '.') }} VND
                                        </strong>
                                    </div>
                                    <div class="mb-1">
                                        <small class="text-muted">Khấu trừ:</small>
                                        <span class="text-danger" id="deduction-amount-display">
                                            {{ number_format($payrollPayslip->deduction_amount, 0, ',', '.') }} VND
                                        </span>
                                    </div>
                                    <div>
                                        <small class="text-muted">Thực lĩnh:</small>
                                        <strong class="text-success" id="net-amount-display">
                                            {{ number_format($payrollPayslip->net_amount, 0, ',', '.') }} VND
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered items-table">
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
                                    <tbody id="items-tbody">
                                        @php
                                            $itemIndex = 0;
                                        @endphp
                                        @foreach($payrollPayslip->items as $item)
                                        <tr class="item-row">
                                            <td>
                                                <select name="items[{{ $itemIndex }}][item_type]" 
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
                                                       name="items[{{ $itemIndex }}][item_name]" 
                                                       value="{{ $item->item_name }}" 
                                                       class="form-control form-control-sm" required>
                                            </td>
                                            <td>
                                                <select name="items[{{ $itemIndex }}][sign]" 
                                                        class="form-select form-select-sm item-sign">
                                                    <option value="1" {{ $item->sign == 1 ? 'selected' : '' }}>+ (Thu nhập)</option>
                                                    <option value="-1" {{ $item->sign == -1 ? 'selected' : '' }}>- (Khấu trừ)</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       name="items[{{ $itemIndex }}][amount]" 
                                                       value="{{ abs($item->amount) }}" 
                                                       step="0.01" 
                                                       min="0"
                                                       class="form-control form-control-sm item-amount" 
                                                       required>
                                            </td>
                                            <td>
                                    <input type="text" 
                                                       name="items[{{ $itemIndex }}][note]" 
                                                       value="{{ $item->note }}" 
                                                       class="form-control form-control-sm">
                                            </td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger remove-item">
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
                                                        class="btn btn-sm btn-primary add-item">
                                                    <i class="fas fa-plus me-1"></i>Thêm item
                                                </button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Card 3: Ghi chú --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-sticky-note me-2"></i>Ghi chú
                            </h6>
                                </div>
                        <div class="card-body">
                                    <textarea class="form-control @error('note') is-invalid @enderror" 
                                              name="note" 
                                              rows="3" 
                                              placeholder="Ghi chú về phiếu lương...">{{ old('note', $payrollPayslip->note) }}</textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                        </div>
                    </div>
                </div>

                {{-- Cột phải: Sidebar (col-lg-4) --}}
                <div class="col-lg-4">
                    {{-- Card Thao tác --}}
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
                                        'url' => route('staff.payroll-payslips.show', $payrollPayslip->id)
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
                                    {{ $payrollPayslip->created_at->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold small text-muted mb-1">Cập nhật lần cuối:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-calendar-edit me-1 text-muted"></i>
                                    {{ $payrollPayslip->updated_at->format('d/m/Y H:i:s') }}
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
<script src="{{ asset('assets/js/number-formatter.js') }}"></script>
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-payslip-form');
    if (!form) return;
    
    // Item counter
    let itemCounter = {{ $payrollPayslip->items->count() }};

    // Function to calculate totals from items
    function calculateTotals() {
        const tbody = document.getElementById('items-tbody');
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
        document.getElementById('gross-amount-display').textContent = 
            grossAmount.toLocaleString('vi-VN') + ' VND';
        document.getElementById('deduction-amount-display').textContent = 
            deductionAmount.toLocaleString('vi-VN') + ' VND';
        document.getElementById('net-amount-display').textContent = 
            netAmount.toLocaleString('vi-VN') + ' VND';
    }

    // Add item button
    document.querySelector('.add-item')?.addEventListener('click', function() {
        const tbody = document.getElementById('items-tbody');
        const itemIndex = itemCounter;

        const row = document.createElement('tr');
        row.className = 'item-row';
        row.innerHTML = `
            <td>
                <select name="items[${itemIndex}][item_type]" 
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
                       name="items[${itemIndex}][item_name]" 
                       value="" 
                       class="form-control form-control-sm" required>
            </td>
            <td>
                <select name="items[${itemIndex}][sign]" 
                        class="form-select form-select-sm item-sign">
                    <option value="1" selected>+ (Thu nhập)</option>
                    <option value="-1">- (Khấu trừ)</option>
                </select>
            </td>
            <td>
                <input type="number" 
                       name="items[${itemIndex}][amount]" 
                       value="0" 
                       step="0.01" 
                       min="0"
                       class="form-control form-control-sm item-amount" 
                       required>
            </td>
            <td>
                <input type="text" 
                       name="items[${itemIndex}][note]" 
                       value="" 
                       class="form-control form-control-sm">
            </td>
            <td>
                <button type="button" 
                        class="btn btn-sm btn-danger remove-item">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);
        itemCounter++;

        // Attach event listeners to new row
        attachItemListeners(row);
        calculateTotals();
    });

    // Remove item button
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('.item-row');
            row.remove();
            calculateTotals();
        });
    });

    // Attach listeners to existing items
    function attachItemListeners(row) {
        const signSelect = row.querySelector('.item-sign');
        const amountInput = row.querySelector('.item-amount');
        const removeBtn = row.querySelector('.remove-item');

        if (signSelect) {
            signSelect.addEventListener('change', calculateTotals);
        }
        if (amountInput) {
            amountInput.addEventListener('input', calculateTotals);
            amountInput.addEventListener('change', calculateTotals);
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
                calculateTotals();
            });
        }
    }

    // Initialize listeners for all existing items
    document.querySelectorAll('.item-row').forEach(row => {
        attachItemListeners(row);
    });

    // Initialize totals
    calculateTotals();
    
    // Form submit handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...';
        submitButton.disabled = true;
        
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
                    window.location.href = data.redirect || '{{ route("staff.payroll-payslips.show", $payrollPayslip->id) }}';
                }, 1500);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi cập nhật phiếu lương', 'Lỗi hệ thống!');
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        });
    });
});
</script>
@endpush
