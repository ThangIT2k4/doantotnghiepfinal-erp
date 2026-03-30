@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Dòng tiền ra')

@section('content')
<main class="main-content">
<div class="container-fluid">
        {{-- 1. Page Header với breadcrumbs --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết Dòng tiền ra',
            'subtitle' => 'Thông tin chi tiết về dòng tiền ra #' . $cashOutflow->id,
            'icon' => 'fas fa-arrow-down',
            'breadcrumbs' => [
                ['label' => 'Dòng tiền ra', 'url' => route('staff.cash-outflows.index')],
                ['label' => '#' . $cashOutflow->id, 'active' => true]
            ]
        ])

        {{-- 2. Content --}}
    <div class="row">
            {{-- Nội dung chính --}}
            <div class="col-lg-8">
                {{-- Card: Thông tin cơ bản --}}
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
                                    <label class="form-label fw-bold small text-muted mb-1">ID:</label>
                                    <div class="p-2 bg-light rounded">
                                        #{{ $cashOutflow->id }}
                                    </div>
                                </div>
                            </div>
                        <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Trạng thái:</label>
                                    <div class="p-2 bg-light rounded">
                                        @include('staff.components.status-badge', [
                                            'status' => $cashOutflow->status,
                                            'type' => 'cash_outflow'
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Nhà cung cấp/Khách hàng:</label>
                                    <div class="p-2 bg-light rounded">
                                                @if($cashOutflow->vendor)
                                            <a href="{{ route('staff.vendors.show', $cashOutflow->vendor) }}" class="text-primary text-decoration-none">
                                                <i class="fas fa-building me-1"></i>{{ $cashOutflow->vendor->name }}
                                                    </a>
                                                @elseif($cashOutflow->companyInvoice && $cashOutflow->companyInvoice->user)
                                            <a href="{{ route('staff.users.show', $cashOutflow->companyInvoice->user) }}" class="text-info text-decoration-none">
                                                <i class="fas fa-user me-1"></i>{{ $cashOutflow->companyInvoice->user->full_name ?? $cashOutflow->companyInvoice->user->name }}
                                                    </a>
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
                                        <span class="h5 text-success mb-0">
                                            {{ number_format($cashOutflow->amount, 0, ',', '.') }} VND
                                                </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Phương thức thanh toán:</label>
                                    <div class="p-2 bg-light rounded">
                                        @if($cashOutflow->paymentMethod)
                                            <span class="badge bg-info">{{ $cashOutflow->paymentMethod->name }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Mã giao dịch:</label>
                                    <div class="p-2 bg-light rounded">
                                                @if($cashOutflow->transaction_ref)
                                                    @if(strpos($cashOutflow->transaction_ref, '/storage/') !== false)
                                                        <a href="{{ $cashOutflow->transaction_ref }}" target="_blank" class="btn btn-sm btn-info">
                                                            <i class="fas fa-file"></i> Xem tài liệu
                                                        </a>
                                                    @else
                                                        <code>{{ $cashOutflow->transaction_ref }}</code>
                                                    @endif
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                    </div>
                                </div>
                            </div>
                                </div>
                            </div>
                        </div>

                {{-- Card: Thông tin thanh toán --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Thông tin thanh toán
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                        <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Ngày tạo:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-calendar-plus me-1 text-muted"></i>
                                        {{ $cashOutflow->created_at->format('d/m/Y H:i:s') }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Ngày cập nhật:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-calendar-edit me-1 text-muted"></i>
                                        {{ $cashOutflow->updated_at->format('d/m/Y H:i:s') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Ngày thanh toán:</label>
                                    <div class="p-2 bg-light rounded">
                                        @if($cashOutflow->paid_at)
                                            <i class="fas fa-calendar-check me-1 text-muted"></i>
                                            {{ $cashOutflow->paid_at->format('d/m/Y H:i:s') }}
                                        @else
                                            <span class="text-muted">Chưa thanh toán</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if($cashOutflow->companyInvoice)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-1">Hóa đơn liên quan:</label>
                                    <div class="p-2 bg-light rounded">
                                        <a href="{{ route('staff.company-invoices.show', $cashOutflow->companyInvoice) }}" class="text-primary text-decoration-none">
                                            <i class="fas fa-file-invoice me-1"></i>{{ $cashOutflow->companyInvoice->invoice_no }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card: Ghi chú --}}
                    @if($cashOutflow->note)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-sticky-note me-2"></i>Ghi chú
                        </h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">{{ $cashOutflow->note }}</p>
                                </div>
                            </div>
                @endif

                {{-- Card: Tài liệu đính kèm --}}
                @php
                    $attachments = $cashOutflow->documents;
                    $receiptImages = $attachments->filter(function($doc) {
                        return $doc->document_type === 'image' || 
                               in_array(strtolower(pathinfo($doc->file_name ?? '', PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                    });
                    $documents = $attachments->filter(function($doc) {
                        return $doc->document_type !== 'image' && 
                               !in_array(strtolower(pathinfo($doc->file_name ?? '', PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                    });
                    $otherImages = collect(); // Không còn phân biệt theo attachment_type
                @endphp
                @if($receiptImages->count() > 0 || $documents->count() > 0 || $otherImages->count() > 0)
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-paperclip me-2"></i>Tài liệu đính kèm
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($receiptImages->count() > 0)
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-2">Ảnh biên lai:</label>
                                    <div class="row">
                                        @foreach($receiptImages as $attachment)
                                            @php
                                                $rawFileUrl = $attachment->getRawOriginal('file_url');
                                                $imageUrl = str_starts_with($rawFileUrl, 'http://') || str_starts_with($rawFileUrl, 'https://')
                                                    ? $rawFileUrl
                                                    : asset('storage/' . ltrim($rawFileUrl, '/'));
                                            @endphp
                                            <div class="col-md-4 mb-3">
                                                <div class="p-2 bg-light rounded">
                                                    <a href="{{ $imageUrl }}" target="_blank" class="d-block text-center">
                                                        <img src="{{ $imageUrl }}" alt="{{ $attachment->file_name }}" 
                                                             style="max-width: 100%; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; cursor: pointer;">
                                                    </a>
                                                    <small class="text-muted d-block text-center mt-2">{{ $attachment->file_name }}</small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($otherImages->count() > 0)
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-2">Ảnh khác:</label>
                                    <div class="row">
                                        @foreach($otherImages as $attachment)
                                            @php
                                                $rawFileUrl = $attachment->getRawOriginal('file_url');
                                                $imageUrl = str_starts_with($rawFileUrl, 'http://') || str_starts_with($rawFileUrl, 'https://')
                                                    ? $rawFileUrl
                                                    : asset('storage/' . ltrim($rawFileUrl, '/'));
                                            @endphp
                                            <div class="col-md-4 mb-3">
                                                <div class="p-2 bg-light rounded">
                                                    <a href="{{ $imageUrl }}" target="_blank" class="d-block text-center">
                                                        <img src="{{ $imageUrl }}" alt="{{ $attachment->file_name }}" 
                                                             style="max-width: 100%; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; cursor: pointer;">
                                                    </a>
                                                    <small class="text-muted d-block text-center mt-2">{{ $attachment->file_name }}</small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($documents->count() > 0)
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-2">Tài liệu:</label>
                                    <div class="list-group">
                                        @foreach($documents as $attachment)
                                            @php
                                                $rawFileUrl = $attachment->getRawOriginal('file_url');
                                                $fileUrl = str_starts_with($rawFileUrl, 'http://') || str_starts_with($rawFileUrl, 'https://')
                                                    ? $rawFileUrl
                                                    : asset('storage/' . ltrim($rawFileUrl, '/'));
                                            @endphp
                                            <a href="{{ $fileUrl }}" target="_blank" class="list-group-item list-group-item-action">
                                                <i class="fas fa-file me-2"></i>{{ $attachment->file_name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
                    </div>
            
            {{-- Card "Thao tác" bên phải --}}
            <div class="col-lg-4">
                <div class="card shadow-sm">
                                <div class="card-header">
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
                                    'url' => route('staff.cash-outflows.edit', $cashOutflow->id),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteCashOutflow({$cashOutflow->id}, '" . addslashes($cashOutflow->note ?? 'Dòng tiền ra #' . $cashOutflow->id) . "')",
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Quay lại',
                                    'icon' => 'fas fa-arrow-left',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.cash-outflows.index'),
                                    'class' => 'w-100'
                                ]
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            if($cashOutflow->status !== 'pending') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'warning',
                                    'label' => 'Đang chờ',
                                    'icon' => 'fas fa-clock',
                                    'iconPosition' => 'left',
                                    'onclick' => "updateCashOutflowStatus('pending')",
                                    'class' => 'w-100'
                                ];
                            }
                            
                            if($cashOutflow->status !== 'success') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Thành công',
                                    'icon' => 'fas fa-check-circle',
                                    'iconPosition' => 'left',
                                    'onclick' => "updateCashOutflowStatus('success')",
                                    'class' => 'w-100'
                                ];
                            }
                            
                            if($cashOutflow->status !== 'failed') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Thất bại',
                                    'icon' => 'fas fa-times-circle',
                                    'iconPosition' => 'left',
                                    'onclick' => "updateCashOutflowStatus('failed')",
                                    'class' => 'w-100'
                                ];
                            }
                            
                            if($cashOutflow->status !== 'reversed') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'info',
                                    'label' => 'Đã hoàn trả',
                                    'icon' => 'fas fa-undo',
                                    'iconPosition' => 'left',
                                    'onclick' => "updateCashOutflowStatus('reversed')",
                                    'class' => 'w-100'
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

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
// Session notifications
@if(session('success'))
    document.addEventListener('DOMContentLoaded', function() {
        Notify.success('{{ session('success') }}', 'Thành công!');
    });
@endif

@if(session('error'))
    document.addEventListener('DOMContentLoaded', function() {
        Notify.error('{{ session('error') }}', 'Lỗi!');
    });
@endif

// Update cash outflow status
window.updateCashOutflowStatus = function(newStatus) {
    const statusLabels = {
        'pending': 'Đang chờ',
        'success': 'Thành công',
        'failed': 'Thất bại',
        'reversed': 'Đã hoàn trả'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển sang trạng thái "${statusLabels[newStatus]}"?`,
        type: newStatus === 'failed' ? 'danger' : (newStatus === 'success' ? 'success' : 'warning'),
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            // Gửi request
            const formData = new FormData();
            formData.append('status', newStatus);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('{{ route("staff.cash-outflows.update-status", $cashOutflow->id) }}', {
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
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
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

function deleteCashOutflow(cashOutflowId, note) {
    Notify.confirmDelete(note || 'Dòng tiền ra #' + cashOutflowId, function() {
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('_method', 'DELETE');

            fetch(`/staff/cash-outflows/${cashOutflowId}`, {
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
                Notify.success('Dòng tiền ra đã được xóa thành công!', 'Thành công!');
                setTimeout(() => {
                    window.location.href = '{{ route("staff.cash-outflows.index") }}';
                }, 1500);
                } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            Notify.error('Không thể xóa dòng tiền ra', 'Lỗi hệ thống!');
            })
            .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
}
</script>
@endpush
