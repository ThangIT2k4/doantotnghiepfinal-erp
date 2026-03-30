@extends('layouts.staff_dashboard')

@section('title', 'Tạo yêu cầu hoàn tiền cọc')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Tạo yêu cầu hoàn tiền cọc',
            'subtitle' => 'Tạo yêu cầu hoàn tiền cọc cho tổ chức',
            'icon' => 'fas fa-plus',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.deposit-refunds.index')
                ]
            ]
        ])

        {{-- Form với Layout 2 Cột --}}
        <form id="create-deposit-refund-form" method="POST" action="{{ route('staff.deposit-refunds.store') }}">
            @csrf
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
                                <label for="lease_id" class="form-label">
                                    Chọn hợp đồng <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('lease_id') is-invalid @enderror" 
                                        id="lease_id" 
                                        name="lease_id" 
                                        required>
                                    <option value="">-- Chọn hợp đồng --</option>
                                    @foreach($leases as $lease)
                                        <option value="{{ $lease->id }}" 
                                                data-deposit="{{ $lease->deposit_amount }}"
                                                data-tenant="{{ $lease->tenant->full_name ?? 'N/A' }}"
                                                data-agent="{{ $lease->agent->full_name ?? 'N/A' }}"
                                                data-property="{{ $lease->unit->property->name ?? 'N/A' }}"
                                                data-unit="{{ $lease->unit->code ?? 'N/A' }}"
                                                {{ old('lease_id') == $lease->id ? 'selected' : '' }}>
                                            {{ $lease->contract_no ?? 'Hợp đồng #' . $lease->id }} - {{ $lease->unit->property->name ?? 'N/A' }} ({{ $lease->unit->code ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('lease_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                                    <option value="cash" {{ old('refund_method') === 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                                    <option value="bank_transfer" {{ old('refund_method') === 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                                    <option value="wallet" {{ old('refund_method') === 'wallet' ? 'selected' : '' }}>Ví điện tử</option>
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
                                          placeholder="Ghi chú về yêu cầu hoàn tiền...">{{ old('notes') }}</textarea>
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
                                        'label' => 'Tạo yêu cầu',
                                        'icon' => 'fas fa-save',
                                        'onclick' => 'submitRefundForm()'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.deposit-refunds.index')
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    {{-- Card Hướng dẫn --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-question-circle me-2"></i>Hướng dẫn
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Thông tin cần thiết</h6>
                                <ul class="mb-0 small">
                                    <li>Chọn hợp đồng và phương thức hoàn tiền là bắt buộc</li>
                                    <li>Hệ thống sẽ tự động tính số tiền hoàn lại dựa trên tiền cọc và các khoản đã trừ</li>
                                    <li>Yêu cầu sẽ được tạo ở trạng thái "Chờ phê duyệt"</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Lease Information Preview --}}
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin hợp đồng
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="lease-info-preview" class="text-center text-muted py-4">
                                <i class="fas fa-file-contract fa-2x mb-2"></i>
                                <p>Chọn hợp đồng để xem thông tin</p>
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
<script>
// Submit refund form with confirmation
function submitRefundForm() {
    const leaseId = document.getElementById('lease_id').value;
    const refundMethod = document.getElementById('refund_method').value;
    
    if (!leaseId) {
        Notify.error('Vui lòng chọn hợp đồng!');
        return;
    }
    
    if (!refundMethod) {
        Notify.error('Vui lòng chọn phương thức hoàn tiền!');
        return;
    }
    
    Notify.confirm({
        title: 'Tạo yêu cầu hoàn tiền',
        message: 'Bạn có chắc chắn muốn tạo yêu cầu hoàn tiền cọc này?',
        details: 'Yêu cầu sẽ được tạo và chuyển sang trạng thái "Chờ phê duyệt".',
        type: 'info',
        confirmText: 'Tạo yêu cầu',
        cancelText: 'Hủy',
        onConfirm: function() {
            document.getElementById('create-deposit-refund-form').submit();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const leaseSelect = document.getElementById('lease_id');
    const leaseInfoPreview = document.getElementById('lease-info-preview');

    leaseSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value) {
            const depositAmount = parseFloat(selectedOption.dataset.deposit) || 0;
            const tenantName = selectedOption.dataset.tenant;
            const agentName = selectedOption.dataset.agent;
            const propertyName = selectedOption.dataset.property;
            const unitCode = selectedOption.dataset.unit;
            
            // Update lease info preview
            leaseInfoPreview.innerHTML = `
                <div class="text-start">
                    <div class="mb-2">
                        <strong>Khách thuê:</strong> ${tenantName}
                    </div>
                    <div class="mb-2">
                        <strong>Agent:</strong> ${agentName}
                    </div>
                    <div class="mb-2">
                        <strong>Bất động sản:</strong> ${propertyName}
                    </div>
                    <div class="mb-2">
                        <strong>Phòng:</strong> ${unitCode}
                    </div>
                    <div class="mb-2">
                        <strong>Tiền cọc:</strong> <span class="text-primary fw-bold">${new Intl.NumberFormat('vi-VN').format(depositAmount)}đ</span>
                    </div>
                </div>
            `;
        } else {
            leaseInfoPreview.innerHTML = `
                <i class="fas fa-file-contract fa-2x mb-2"></i>
                <p>Chọn hợp đồng để xem thông tin</p>
            `;
        }
    });
    
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
});
</script>
@endpush
