@extends('layouts.staff_dashboard')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Chi tiết thanh toán')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với breadcrumbs --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết thanh toán',
            'subtitle' => 'Thông tin chi tiết về thanh toán #' . $payment->id,
            'icon' => 'fas fa-credit-card',
            'breadcrumbs' => [
                ['label' => 'Thanh toán', 'url' => route('staff.payments.index')],
                ['label' => 'Thanh toán #' . $payment->id, 'active' => true]
            ]
        ])

        {{-- 2. Content --}}
        <div class="row">
            {{-- Nội dung chính --}}
            <div class="col-lg-8">
                {{-- Card 1: Thông tin cơ bản --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">ID thanh toán:</label>
                                    <div class="p-2 bg-light rounded">
                                        <span class="badge bg-light text-dark">#{{ $payment->id }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Trạng thái:</label>
                                    <div class="p-2 bg-light rounded">
                                        @include('staff.components.status-badge', [
                                            'status' => $payment->status,
                                            'type' => 'payment'
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Hóa đơn:</label>
                                    <div class="p-2 bg-light rounded">
                                        @if($payment->invoice)
                                            <a href="{{ route('staff.invoices.show', $payment->invoice) }}" class="text-info text-decoration-none">
                                                <strong>#{{ $payment->invoice->invoice_no ?? $payment->invoice->id }}</strong>
                                            </a>
                                            @if($payment->invoice->lease && $payment->invoice->lease->property)
                                                <br><small class="text-muted">{{ $payment->invoice->lease->property->name ?? 'N/A' }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Số tiền:</label>
                                    <div class="p-2 bg-light rounded">
                                        <strong class="text-primary h5">{{ $payment->formatted_amount }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Phương thức:</label>
                                    <div class="p-2 bg-light rounded">
                                        {{ $payment->method->name ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Ngày thanh toán:</label>
                                    <div class="p-2 bg-light rounded">
                                        {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Mã tham chiếu:</label>
                                    <div class="p-2 bg-light rounded">
                                        {{ $payment->txn_ref ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Ngày tạo:</label>
                                    <div class="p-2 bg-light rounded">
                                        {{ $payment->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($payment->note)
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted mb-1">Ghi chú:</label>
                            <div class="p-2 bg-light rounded">
                                {{ $payment->note }}
                            </div>
                        </div>
                        @endif

                        @php
                            $paymentImage = $payment->documents()
                                ->where('document_type', 'image')
                                ->orderBy('sort_order')
                                ->orderBy('created_at')
                                ->first();
                        @endphp
                        @if($paymentImage)
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted mb-1">Ảnh tài liệu đối chiếu:</label>
                            <div class="p-2 bg-light rounded">
                                @php
                                    // Get raw file_url (relative path) from database, not through accessor
                                    $rawFileUrl = $paymentImage->getRawOriginal('file_url');
                                    // Build correct URL
                                    $imageUrl = str_starts_with($rawFileUrl, 'http://') || str_starts_with($rawFileUrl, 'https://') 
                                        ? $rawFileUrl 
                                        : asset('storage/' . ltrim($rawFileUrl, '/'));
                                @endphp
                                <a href="{{ $imageUrl }}" target="_blank">
                                    <img src="{{ $imageUrl }}" alt="Payment image" style="max-width: 300px; max-height: 300px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; cursor: pointer;">
                                </a>
                                <br><small class="text-muted">Click để xem ảnh gốc</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Card "Thao tác" bên phải --}}
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Thao tác
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            // Primary actions: Sửa, Xóa, Quay lại (hiển thị vertical)
                            $primaryActions = [
                                [
                                    'type' => 'link',
                                    'variant' => 'primary',
                                    'label' => 'Sửa',
                                    'icon' => 'fas fa-edit',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.payments.edit', $payment->id),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deletePayment({$payment->id}, '" . addslashes('Thanh toán #' . $payment->id) . "')",
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Quay lại',
                                    'icon' => 'fas fa-arrow-left',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.payments.index'),
                                    'class' => 'w-100'
                                ]
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            if($payment->status !== \App\Models\Payment::STATUS_PENDING) {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'warning',
                                    'label' => 'Chờ thanh toán',
                                    'icon' => 'fas fa-clock',
                                    'onclick' => "updatePaymentStatus('pending')"
                                ];
                            }
                            
                            if($payment->status !== \App\Models\Payment::STATUS_SUCCESS) {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Thành công',
                                    'icon' => 'fas fa-check-circle',
                                    'onclick' => "updatePaymentStatus('success')"
                                ];
                            }
                            
                            if($payment->status !== \App\Models\Payment::STATUS_FAILED) {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Thất bại',
                                    'icon' => 'fas fa-times',
                                    'onclick' => "updatePaymentStatus('failed')"
                                ];
                            }
                            
                            if($payment->status !== \App\Models\Payment::STATUS_REFUNDED) {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'info',
                                    'label' => 'Đã hoàn tiền',
                                    'icon' => 'fas fa-undo',
                                    'onclick' => "updatePaymentStatus('refunded')"
                                ];
                            }
                        @endphp
                        
                        <div class="d-grid gap-2">
                            {{-- Primary Actions: Sửa, Xóa, Quay lại (vertical) --}}
                            @include('staff.components.action-buttons', [
                                'layout' => 'vertical',
                                'size' => 'sm',
                                'actions' => $primaryActions
                            ])
                            
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
                
            </div>
        </div>
    </div>
</main>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
// Update payment status
window.updatePaymentStatus = function(newStatus) {
    const statusLabels = {
        'pending': 'Chờ thanh toán',
        'success': 'Thành công',
        'failed': 'Thất bại',
        'refunded': 'Đã hoàn tiền'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển sang trạng thái "${statusLabels[newStatus]}"?`,
        type: newStatus === 'failed' ? 'danger' : 'warning',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const formData = new FormData();
            formData.append('status', newStatus);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('{{ route("staff.payments.update-status", $payment->id) }}', {
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
                    Notify.success(data.message || 'Đã cập nhật trạng thái thành công!', 'Thành công!');
                    // Reload ngay lập tức
                    window.location.reload();
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Không thể cập nhật trạng thái: ' + error.message, 'Lỗi hệ thống!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    });
};

function deletePayment(paymentId, paymentLabel) {
    Notify.confirmDelete(paymentLabel, function() {
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('_method', 'DELETE');
        
        fetch(`/staff/payments/${paymentId}`, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || !data.error) {
                Notify.success('Thanh toán đã được xóa thành công!', 'Thành công!');
                setTimeout(() => {
                    window.location.href = '{{ route("staff.payments.index") }}';
                }, 1500);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể xóa thanh toán', 'Lỗi hệ thống!');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
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
