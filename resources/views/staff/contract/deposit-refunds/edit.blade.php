@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa yêu cầu hoàn tiền cọc')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Sửa thông tin yêu cầu hoàn tiền cọc',
            'subtitle' => 'Cập nhật thông tin yêu cầu hoàn tiền cọc #' . $depositRefund->id,
            'icon' => 'fas fa-edit',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.deposit-refunds.index')
                ],
                [
                    'variant' => 'info',
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.deposit-refunds.show', $depositRefund->id)
                ]
            ]
        ])

        {{-- Form với Layout 2 Cột --}}
        <form id="edit-deposit-refund-form" method="POST" action="{{ route('staff.deposit-refunds.update', $depositRefund->id) }}">
            @csrf
            @method('PUT')
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    {{-- Card 1: Thông tin yêu cầu hoàn tiền --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-edit me-2"></i>Thông tin yêu cầu hoàn tiền
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Hợp đồng</label>
                                <div class="form-control-plaintext">
                                    <strong>{{ $depositRefund->lease->contract_no ?? 'Hợp đồng #' . $depositRefund->lease_id }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $depositRefund->lease->unit->property->name ?? 'N/A' }} - {{ $depositRefund->lease->unit->code ?? 'N/A' }}</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Khách thuê</label>
                                <div class="form-control-plaintext">
                                    <strong>{{ $depositRefund->tenant->full_name ?? 'N/A' }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $depositRefund->tenant->email ?? 'N/A' }}</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Agent</label>
                                <div class="form-control-plaintext">
                                    <strong>{{ $depositRefund->agent->full_name ?? 'N/A' }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $depositRefund->agent->email ?? 'N/A' }}</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="refund_method" class="form-label">
                                    Phương thức hoàn tiền <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('refund_method') is-invalid @enderror" 
                                        id="refund_method" 
                                        name="refund_method" 
                                        required>
                                    <option value="">-- Chọn phương thức --</option>
                                    <option value="cash" {{ old('refund_method', $depositRefund->refund_method) === 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                                    <option value="bank_transfer" {{ old('refund_method', $depositRefund->refund_method) === 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                                    <option value="wallet" {{ old('refund_method', $depositRefund->refund_method) === 'wallet' ? 'selected' : '' }}>Ví điện tử</option>
                                </select>
                                @error('refund_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Ghi chú</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" 
                                          name="notes" 
                                          rows="3" 
                                          placeholder="Ghi chú về yêu cầu hoàn tiền...">{{ old('notes', $depositRefund->notes) }}</textarea>
                                @error('notes')
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
                                        'type' => 'button',
                                        'variant' => 'primary',
                                        'label' => 'Cập nhật',
                                        'icon' => 'fas fa-save',
                                        'onclick' => 'submitEditForm()'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.deposit-refunds.show', $depositRefund->id)
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    {{-- Card Thông tin hiện tại --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Trạng thái:</label>
                                <div class="p-2 bg-light rounded">
                                    @include('staff.components.status-badge', [
                                        'status' => $depositRefund->status,
                                        'type' => 'deposit-refund'
                                    ])
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Ngày tạo:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-calendar-plus me-1 text-muted"></i>
                                    {{ $depositRefund->created_at->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold small text-muted mb-1">Cập nhật lần cuối:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-calendar-edit me-1 text-muted"></i>
                                    {{ $depositRefund->updated_at->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Deposit Calculation --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-calculator me-2"></i>Tính toán hoàn tiền
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-12 mb-3">
                                    <h6 class="text-primary">Tiền cọc gốc</h6>
                                    <h5 class="text-primary fw-bold">{{ number_format($depositRefund->original_deposit_amount) }}đ</h5>
                                </div>
                                <div class="col-12 mb-3">
                                    <h6 class="text-warning">Đã trừ từ cọc</h6>
                                    <h5 class="text-warning fw-bold">{{ number_format($depositRefund->deducted_amount) }}đ</h5>
                                    @if($depositRefund->deduction_details && isset($depositRefund->deduction_details['ticket_deposits']))
                                        <small class="text-muted">Từ tickets: {{ number_format($depositRefund->deduction_details['ticket_deposits']) }}đ</small>
                                    @endif
                                </div>
                                <div class="col-12">
                                    <h6 class="text-success">Số tiền hoàn lại</h6>
                                    <h4 class="text-success fw-bold">{{ number_format($depositRefund->refund_amount) }}đ</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Warning --}}
                    <div class="card shadow-sm border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-exclamation-triangle me-2"></i>Lưu ý
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0 small">
                                <i class="fas fa-info-circle me-1"></i>
                                Chỉ có thể chỉnh sửa yêu cầu hoàn tiền khi đang ở trạng thái "Chờ phê duyệt".
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection

@push('scripts')
<script>
// Submit edit form with confirmation
function submitEditForm() {
    const refundMethod = document.getElementById('refund_method').value;
    
    if (!refundMethod) {
        Notify.error('Vui lòng chọn phương thức hoàn tiền!');
        return;
    }
    
    Notify.confirm({
        title: 'Cập nhật yêu cầu hoàn tiền',
        message: 'Bạn có chắc chắn muốn cập nhật thông tin yêu cầu hoàn tiền này?',
        details: 'Thông tin sẽ được cập nhật và lưu vào hệ thống.',
        type: 'info',
        confirmText: 'Cập nhật',
        cancelText: 'Hủy',
        onConfirm: function() {
            const form = document.getElementById('edit-deposit-refund-form');
            
            // Submit form via AJAX
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
                        // Redirect về show page
                        window.location.href = data.redirect || '{{ route("staff.deposit-refunds.show", $depositRefund->id) }}';
                    }, 1500);
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Fallback to regular form submit
                form.submit();
            });
        }
    });
}

// Show success/error messages from session
@if(session('success'))
    Notify.success('{{ session('success') }}');
@endif

@if(session('error'))
    Notify.error('{{ session('error') }}');
@endif

@if(session('warning'))
    Notify.warning('{{ session('warning') }}');
@endif

@if(session('info'))
    Notify.info('{{ session('info') }}');
@endif
</script>
@endpush
