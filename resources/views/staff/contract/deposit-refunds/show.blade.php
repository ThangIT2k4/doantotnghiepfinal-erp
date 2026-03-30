@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết yêu cầu hoàn tiền cọc')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header với Breadcrumbs --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết yêu cầu hoàn tiền cọc',
            'subtitle' => 'Thông tin chi tiết về yêu cầu hoàn tiền cọc #' . $depositRefund->id,
            'icon' => 'fas fa-money-bill-wave',
            'breadcrumbs' => [
                ['label' => 'Hoàn tiền cọc', 'url' => route('staff.deposit-refunds.index')],
                ['label' => 'Yêu cầu #' . $depositRefund->id, 'active' => true]
            ]
        ])

        <div class="row">
            {{-- Nội dung chính --}}
            <div class="col-lg-8">
                {{-- Refund Information --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin yêu cầu hoàn tiền
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="form-label text-muted small">Mã yêu cầu</label>
                                    <p class="mb-0 fw-bold">#{{ $depositRefund->id }}</p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="form-label text-muted small">Trạng thái</label>
                                    <p class="mb-0">
                                        @include('staff.components.status-badge', [
                                            'status' => $depositRefund->status,
                                            'type' => 'deposit-refund'
                                        ])
                                    </p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="form-label text-muted small">Phương thức hoàn tiền</label>
                                    <p class="mb-0">
                                        <span class="badge bg-info">{{ $depositRefund->refund_method_label }}</span>
                                    </p>
                                </div>
                                <div class="info-item mb-3">
                                    <label class="form-label text-muted small">Ngày tạo</label>
                                    <p class="mb-0">{{ $depositRefund->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <label class="form-label text-muted small">Người tạo</label>
                                    <p class="mb-0">{{ $depositRefund->creator->full_name ?? 'N/A' }}</p>
                                </div>
                                @if($depositRefund->approved_at)
                                    <div class="info-item mb-3">
                                        <label class="form-label text-muted small">Ngày phê duyệt</label>
                                        <p class="mb-0">{{ $depositRefund->approved_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <div class="info-item mb-3">
                                        <label class="form-label text-muted small">Người phê duyệt</label>
                                        <p class="mb-0">{{ $depositRefund->approver->full_name ?? 'N/A' }}</p>
                                    </div>
                                @endif
                                @if($depositRefund->paid_at)
                                    <div class="info-item mb-3">
                                        <label class="form-label text-muted small">Ngày thanh toán</label>
                                        <p class="mb-0">{{ $depositRefund->paid_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <div class="info-item mb-3">
                                        <label class="form-label text-muted small">Người thanh toán</label>
                                        <p class="mb-0">{{ $depositRefund->payer->full_name ?? 'N/A' }}</p>
                                    </div>
                                    @if($depositRefund->refund_reference)
                                        <div class="info-item mb-3">
                                            <label class="form-label text-muted small">Mã tham chiếu</label>
                                            <p class="mb-0 fw-bold">{{ $depositRefund->refund_reference }}</p>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                        
                        @if($depositRefund->notes)
                            <div class="info-item">
                                <label class="form-label text-muted small">Ghi chú</label>
                                <div class="bg-light p-3 rounded">
                                    {{ $depositRefund->notes }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Deposit Calculation --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-calculator me-2"></i>Tính toán hoàn tiền cọc
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="border-end">
                                    <h6 class="text-primary">Tiền cọc gốc</h6>
                                    <h4 class="text-primary fw-bold">{{ number_format($depositRefund->original_deposit_amount) }}đ</h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border-end">
                                    <h6 class="text-warning">Đã trừ từ cọc</h6>
                                    <h4 class="text-warning fw-bold">{{ number_format($depositRefund->deducted_amount) }}đ</h4>
                                    @if($depositRefund->deduction_details && isset($depositRefund->deduction_details['ticket_deposits']))
                                        <small class="text-muted">Từ tickets: {{ number_format($depositRefund->deduction_details['ticket_deposits']) }}đ</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-success">Số tiền hoàn lại</h6>
                                <h4 class="text-success fw-bold">{{ number_format($depositRefund->refund_amount) }}đ</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Card "Thao tác" bên phải --}}
            <div class="col-lg-4">
                {{-- Card "Thao tác" --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Thao tác
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            // Check if there's a company invoice for this deposit refund
                            $companyInvoice = \App\Models\CompanyInvoice::where('deposit_refund_id', $depositRefund->id)
                                ->where('organization_id', $depositRefund->organization_id)
                                ->first();
                            
                            // Primary actions: Sửa, Xem hóa đơn, Quay lại (hiển thị vertical)
                            $primaryActions = [];
                            
                            if($depositRefund->status === 'pending') {
                                $primaryActions[] = [
                                    'type' => 'link',
                                    'variant' => 'primary',
                                    'label' => 'Sửa',
                                    'icon' => 'fas fa-edit',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.deposit-refunds.edit', $depositRefund->id),
                                    'class' => 'w-100'
                                ];
                            }
                            
                            // Add "Xem hóa đơn hoàn cọc" button if company invoice exists
                            if($companyInvoice) {
                                $primaryActions[] = [
                                    'type' => 'link',
                                    'variant' => 'info',
                                    'label' => 'Xem hóa đơn hoàn cọc',
                                    'icon' => 'fas fa-file-invoice',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.company-invoices.show', $companyInvoice->id),
                                    'class' => 'w-100'
                                ];
                            }
                            
                            $primaryActions[] = [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Quay lại',
                                'icon' => 'fas fa-arrow-left',
                                'iconPosition' => 'left',
                                'url' => route('staff.deposit-refunds.index'),
                                'class' => 'w-100'
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            if($depositRefund->status === 'pending') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Phê duyệt',
                                    'icon' => 'fas fa-check-circle',
                                    'iconPosition' => 'left',
                                    'onclick' => "approveRefund({$depositRefund->id})",
                                    'class' => 'w-100'
                                ];
                                
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Hủy',
                                    'icon' => 'fas fa-times',
                                    'iconPosition' => 'left',
                                    'onclick' => "cancelRefund({$depositRefund->id})",
                                    'class' => 'w-100'
                                ];
                            } elseif($depositRefund->status === 'approved') {
                                // Use the companyInvoice variable already loaded above
                                if($companyInvoice) {
                                    $statusActions[] = [
                                        'type' => 'link',
                                        'variant' => 'primary',
                                        'label' => 'Thanh toán',
                                        'icon' => 'fas fa-money-bill-wave',
                                        'iconPosition' => 'left',
                                        'url' => route('staff.company-invoices.show', $companyInvoice->id),
                                        'class' => 'w-100'
                                    ];
                                } else {
                                    $statusActions[] = [
                                        'type' => 'button',
                                        'variant' => 'primary',
                                        'label' => 'Thanh toán',
                                        'icon' => 'fas fa-money-bill-wave',
                                        'iconPosition' => 'left',
                                        'onclick' => "window.location.href = '" . route('staff.deposit-refunds.mark-paid', $depositRefund->id) . "';",
                                        'class' => 'w-100'
                                    ];
                                }
                            }
                        @endphp
                        
                        <div class="d-grid gap-2">
                            {{-- Primary Actions: Sửa, Quay lại (vertical) --}}
                            @if(count($primaryActions) > 0)
                                @include('staff.components.action-buttons', [
                                    'layout' => 'vertical',
                                    'size' => 'sm',
                                    'actions' => $primaryActions
                                ])
                            @endif
                            
                            {{-- Status Actions: Dropdown cho các nút chuyển trạng thái --}}
                            @if(count($statusActions) > 0)
                                @include('staff.components.action-buttons', [
                                    'layout' => 'dropdown',
                                    'size' => 'sm',
                                    'dropdownLabel' => 'Chuyển trạng thái',
                                    'actions' => $statusActions
                                ])
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Lease Information --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-file-contract me-2"></i>Thông tin hợp đồng
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="info-item mb-3">
                            <label class="form-label text-muted small">Mã hợp đồng</label>
                            <p class="mb-0 fw-bold">
                                <a href="{{ route('staff.leases.show', $depositRefund->lease_id) }}" class="text-decoration-none">
                                    {{ $depositRefund->lease->contract_no ?? 'N/A' }}
                                </a>
                            </p>
                        </div>
                        <div class="info-item mb-3">
                            <label class="form-label text-muted small">Bất động sản</label>
                            <p class="mb-0">{{ $depositRefund->lease->unit->property->name ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item mb-3">
                            <label class="form-label text-muted small">Phòng</label>
                            <p class="mb-0">{{ $depositRefund->lease->unit->code ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item mb-3">
                            <label class="form-label text-muted small">Trạng thái hợp đồng</label>
                            <p class="mb-0">
                                @include('staff.components.status-badge', [
                                    'status' => $depositRefund->lease->status,
                                    'type' => 'lease'
                                ])
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Tenant Information --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>Thông tin khách thuê
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="info-item mb-3">
                            <label class="form-label text-muted small">Họ và tên</label>
                            <p class="mb-0 fw-bold">{{ $depositRefund->tenant->full_name ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item mb-3">
                            <label class="form-label text-muted small">Email</label>
                            <p class="mb-0">{{ $depositRefund->tenant->email ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item mb-3">
                            <label class="form-label text-muted small">Số điện thoại</label>
                            <p class="mb-0">{{ $depositRefund->tenant->phone ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Agent Information --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-user-tie me-2"></i>Thông tin Agent
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="info-item mb-3">
                            <label class="form-label text-muted small">Họ và tên</label>
                            <p class="mb-0 fw-bold">{{ $depositRefund->agent->full_name ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item mb-3">
                            <label class="form-label text-muted small">Email</label>
                            <p class="mb-0">{{ $depositRefund->agent->email ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item mb-3">
                            <label class="form-label text-muted small">Số điện thoại</label>
                            <p class="mb-0">{{ $depositRefund->agent->phone ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@endsection

@push('scripts')
<script>
// Approve refund function
function approveRefund(refundId) {
    Notify.confirm({
        title: 'Phê duyệt hoàn tiền',
        message: 'Bạn có chắc chắn muốn phê duyệt yêu cầu hoàn tiền này?',
        details: 'Yêu cầu sẽ được chuyển sang trạng thái "Đã phê duyệt" và có thể thực hiện thanh toán.',
        type: 'success',
        confirmText: 'Phê duyệt',
        cancelText: 'Hủy',
        onConfirm: function() {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/staff/deposit-refunds/${refundId}/approve`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Cancel refund function
function cancelRefund(refundId) {
    Notify.confirm({
        title: 'Hủy yêu cầu hoàn tiền',
        message: 'Bạn có chắc chắn muốn hủy yêu cầu hoàn tiền này?',
        details: 'Yêu cầu sẽ được chuyển sang trạng thái "Đã hủy" và không thể khôi phục.',
        type: 'danger',
        confirmText: 'Hủy yêu cầu',
        cancelText: 'Không',
        onConfirm: function() {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/staff/deposit-refunds/${refundId}/cancel`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
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
