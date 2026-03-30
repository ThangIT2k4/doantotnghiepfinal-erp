@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết đặt cọc')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header with Breadcrumbs --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết đặt cọc',
            'subtitle' => 'Thông tin chi tiết về đặt cọc: ' . $bookingDeposit->reference_number,
            'icon' => 'fas fa-hand-holding-usd',
            'breadcrumbs' => [
                ['label' => 'Đặt cọc', 'url' => route('staff.booking-deposits.index')],
                ['label' => $bookingDeposit->reference_number, 'active' => true]
            ]
        ])

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Booking Deposit Details -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-hand-holding-usd me-2"></i>Thông tin đặt cọc
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Mã đặt cọc</label>
                                    <p class="mb-0">
                                        <span class="badge bg-primary fs-6">{{ $bookingDeposit->reference_number }}</span>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Số tiền</label>
                                    <p class="mb-0 fs-5 text-primary fw-bold">
                                        {{ number_format($bookingDeposit->amount, 0, ',', '.') }}đ
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Loại đặt cọc</label>
                                    <p class="mb-0">
                                        @switch($bookingDeposit->deposit_type)
                                            @case('booking')
                                                <span class="badge bg-primary">Đặt cọc</span>
                                                @break
                                            @case('security')
                                                <span class="badge bg-info">Cọc an ninh</span>
                                                @break
                                            @case('advance')
                                                <span class="badge bg-warning">Trả trước</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $bookingDeposit->deposit_type }}</span>
                                        @endswitch
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Trạng thái</label>
                                    <p class="mb-0">
                                        @switch($bookingDeposit->payment_status)
                                            @case('pending_approval')
                                                <span class="badge bg-warning fs-6">Chờ duyệt</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning fs-6">Chờ thanh toán</span>
                                                @break
                                            @case('paid')
                                                <span class="badge bg-success fs-6">Đã thanh toán</span>
                                                @break
                                            @case('refunded')
                                                <span class="badge bg-secondary fs-6">Hoàn tiền</span>
                                                @break
                                            @case('expired')
                                                <span class="badge bg-danger fs-6">Hết hạn</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-danger fs-6">Đã hủy</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-dark fs-6">{{ $bookingDeposit->payment_status }}</span>
                                        @endswitch
                                    </p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Giữ chỗ đến</label>
                                    <p class="mb-0">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        {{ $bookingDeposit->hold_until ? $bookingDeposit->hold_until->format('d/m/Y H:i') : '-' }}
                                        @if($bookingDeposit->hold_until && $bookingDeposit->hold_until < now())
                                            <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Đã hết hạn</small>
                                        @endif
                                    </p>
                                </div>
                                
                                @if($bookingDeposit->payment_due_date)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Hạn chót thanh toán</label>
                                    <p class="mb-0">
                                        <i class="fas fa-clock me-2"></i>
                                        <strong>{{ $bookingDeposit->payment_due_date->format('d/m/Y H:i') }}</strong>
                                        @if($bookingDeposit->payment_status === 'pending' && $bookingDeposit->payment_due_date > now())
                                            <br><small class="text-info countdown-timer" data-due-date="{{ $bookingDeposit->payment_due_date->format('Y-m-d H:i:s') }}" id="payment-due-countdown">
                                                <i class="fas fa-hourglass-half me-1"></i>
                                                <span class="countdown-text">Đang tính...</span>
                                            </small>
                                        @elseif($bookingDeposit->payment_due_date < now() && $bookingDeposit->payment_status === 'pending')
                                            <br><small class="text-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Đã quá hạn
                                            </small>
                                        @elseif($bookingDeposit->payment_due_date < now())
                                            <br><small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Đã quá hạn (đã xử lý)
                                            </small>
                                        @endif
                                    </p>
                                </div>
                                @endif
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày tạo</label>
                                    <p class="mb-0">
                                        <i class="fas fa-clock me-2"></i>
                                        {{ $bookingDeposit->created_at->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                                
                                @if($bookingDeposit->approved_at)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày duyệt</label>
                                    <p class="mb-0">
                                        <i class="fas fa-check-circle me-2 text-success"></i>
                                        {{ $bookingDeposit->approved_at->format('d/m/Y H:i') }}
                                        @if($bookingDeposit->approvedBy)
                                            <br><small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                Bởi: {{ $bookingDeposit->approvedBy->userProfile->full_name ?? $bookingDeposit->approvedBy->full_name ?? 'N/A' }}
                                            </small>
                                        @endif
                                    </p>
                                </div>
                                @endif
                                
                                @if($bookingDeposit->paid_at)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày thanh toán</label>
                                    <p class="mb-0">
                                        <i class="fas fa-money-bill-wave me-2 text-success"></i>
                                        {{ $bookingDeposit->paid_at->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                                @endif
                                
                                @if($bookingDeposit->refunded_at)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày hoàn tiền</label>
                                    <p class="mb-0">
                                        <i class="fas fa-undo me-2 text-secondary"></i>
                                        {{ $bookingDeposit->refunded_at->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                                @endif
                            </div>
                        </div>
                        
                        @if($bookingDeposit->notes)
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label fw-bold">Ghi chú</label>
                                <div class="alert alert-light">
                                    {{ $bookingDeposit->notes }}
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Property and Unit Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-building me-2"></i>Thông tin bất động sản
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Bất động sản</label>
                                    <p class="mb-0">
                                        @if($bookingDeposit->unit && $bookingDeposit->unit->property)
                                            <strong>{{ $bookingDeposit->unit->property->name }}</strong>
                                            @if($bookingDeposit->unit->property->address)
                                                <br><small class="text-muted">{{ $bookingDeposit->unit->property->address }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Phòng</label>
                                    <p class="mb-0">
                                        @if($bookingDeposit->unit)
                                            <span class="badge bg-info fs-6">{{ $bookingDeposit->unit->code ?? 'Phòng ' . $bookingDeposit->unit->id }}</span>
                                            @if($bookingDeposit->unit->floor)
                                                <br><small class="text-muted">Tầng {{ $bookingDeposit->unit->floor }}</small>
                                            @endif
                                            @if($bookingDeposit->unit->area)
                                                <br><small class="text-muted">Diện tích: {{ $bookingDeposit->unit->area }}m²</small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>Thông tin khách hàng
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($bookingDeposit->tenantUser)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tên khách thuê</label>
                                    <p class="mb-0">{{ $bookingDeposit->tenantUser->full_name }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <p class="mb-0">{{ $bookingDeposit->tenantUser->email }}</p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Số điện thoại</label>
                                    <p class="mb-0">{{ $bookingDeposit->tenantUser->phone ?? '-' }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Loại khách hàng</label>
                                    <p class="mb-0">
                                        <span class="badge bg-success">Khách thuê</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        @elseif($bookingDeposit->lead)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tên khách hàng tiềm năng</label>
                                    <p class="mb-0">{{ $bookingDeposit->lead->name }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <p class="mb-0">{{ $bookingDeposit->lead->email ?? '-' }}</p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Số điện thoại</label>
                                    <p class="mb-0">{{ $bookingDeposit->lead->phone ?? '-' }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Loại khách hàng</label>
                                    <p class="mb-0">
                                        <span class="badge bg-warning">Khách hàng tiềm năng</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Không có thông tin khách hàng
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Agent Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-user-tie me-2"></i>Thông tin nhân viên
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($bookingDeposit->agent)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tên nhân viên</label>
                                    <p class="mb-0">{{ $bookingDeposit->agent->userProfile->full_name ?? $bookingDeposit->agent->full_name ?? 'N/A' }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <p class="mb-0">{{ $bookingDeposit->agent->email ?? '-' }}</p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Số điện thoại</label>
                                    <p class="mb-0">{{ $bookingDeposit->agent->userProfile->phone ?? $bookingDeposit->agent->phone ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Không có thông tin nhân viên
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Card Thao tác --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Thao tác
                        </h6>
                    </div>
                    <div class="card-body">
                        @php
                            // Primary actions: Sửa, Xóa, Quay lại
                            $primaryActions = [
                                [
                                    'type' => 'link',
                                    'variant' => 'primary',
                                    'label' => 'Sửa',
                                    'icon' => 'fas fa-edit',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.booking-deposits.edit', $bookingDeposit->id),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteDeposit({$bookingDeposit->id}, '" . addslashes($bookingDeposit->reference_number) . "')",
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Quay lại',
                                    'icon' => 'fas fa-arrow-left',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.booking-deposits.index'),
                                    'class' => 'w-100'
                                ]
                            ];
                            
                            // Additional actions: Tạo hóa đơn, Xem hóa đơn, Tạo hợp đồng, Xem hợp đồng
                            $additionalActions = [];
                            
                            // Nút tạo hóa đơn
                            if(in_array($bookingDeposit->payment_status, ['pending', 'paid']) && !$hasInvoice) {
                                $additionalActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Tạo hóa đơn',
                                    'icon' => 'fas fa-file-invoice',
                                    'iconPosition' => 'left',
                                    'onclick' => "createInvoice({$bookingDeposit->id})",
                                    'class' => 'w-100'
                                ];
                            }
                            
                            // Nút xem hóa đơn
                            if($hasInvoice && $invoice) {
                                $additionalActions[] = [
                                    'type' => 'link',
                                    'variant' => 'info',
                                    'label' => 'Xem hóa đơn',
                                    'icon' => 'fas fa-file-invoice',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.invoices.show', $invoice->id),
                                    'class' => 'w-100'
                                ];
                            }
                            
                            // Nút tạo hợp đồng
                            if($bookingDeposit->payment_status === 'paid' && !$hasLease) {
                                $additionalActions[] = [
                                    'type' => 'button',
                                    'variant' => 'primary',
                                    'label' => 'Tạo hợp đồng',
                                    'icon' => 'fas fa-file-contract',
                                    'iconPosition' => 'left',
                                    'onclick' => "createLease({$bookingDeposit->id})",
                                    'class' => 'w-100'
                                ];
                            }
                            
                            // Nút xem hợp đồng
                            if($hasLease && $lease) {
                                $additionalActions[] = [
                                    'type' => 'link',
                                    'variant' => 'info',
                                    'label' => 'Xem hợp đồng',
                                    'icon' => 'fas fa-file-contract',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.leases.show', $lease->id),
                                    'class' => 'w-100'
                                ];
                            }
                            
                            // Status actions: Các nút chuyển trạng thái (chỉ chuyển trạng thái)
                            $statusActions = [];
                            
                            // Dropdown chuyển trạng thái
                            if(!empty($canTransitionTo)) {
                                foreach($canTransitionTo as $status) {
                                    $statusLabels = [
                                        'pending_approval' => 'Chuyển về Chờ phê duyệt',
                                        'pending' => 'Phê duyệt (Chờ thanh toán)',
                                        'paid' => 'Đánh dấu Đã thanh toán',
                                        'expired' => 'Đánh dấu Hết hạn',
                                        'cancelled' => 'Hủy đặt cọc',
                                        'refunded' => 'Hoàn tiền',
                                    ];
                                    
                                    $statusIcons = [
                                        'pending_approval' => 'fas fa-hourglass-half',
                                        'pending' => 'fas fa-check-circle',
                                        'paid' => 'fas fa-money-bill',
                                        'expired' => 'fas fa-clock',
                                        'cancelled' => 'fas fa-times-circle',
                                        'refunded' => 'fas fa-undo',
                                    ];
                                    
                                    $statusActions[] = [
                                        'type' => 'button',
                                        'variant' => 'secondary',
                                        'label' => $statusLabels[$status] ?? $status,
                                        'icon' => $statusIcons[$status] ?? 'fas fa-exchange-alt',
                                        'iconPosition' => 'left',
                                        'onclick' => "updateStatus('{$status}')",
                                        'class' => 'w-100',
                                        'data-status' => $status
                                    ];
                                }
                            }
                        @endphp
                        
                        <div class="d-grid gap-2">
                            {{-- Primary Actions: Sửa, Xóa, Quay lại (vertical) --}}
                            @include('staff.components.action-buttons', [
                                'layout' => 'vertical',
                                'size' => 'sm',
                                'actions' => $primaryActions
                            ])
                            
                            {{-- Additional Actions: Tạo hóa đơn, Tạo hợp đồng (vertical) --}}
                            @if(count($additionalActions) > 0)
                                <hr class="my-2">
                                @include('staff.components.action-buttons', [
                                    'layout' => 'vertical',
                                    'size' => 'sm',
                                    'actions' => $additionalActions
                                ])
                            @endif
                            
                            {{-- Status Actions: Dropdown cho các nút chuyển trạng thái --}}
                            @if(count($statusActions) > 0)
                                <hr class="my-2">
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

                <!-- Status Timeline -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Lịch sử trạng thái
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Tạo đặt cọc</h6>
                                    <p class="timeline-text">{{ $bookingDeposit->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            
                            @if($bookingDeposit->approved_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Đã duyệt</h6>
                                    <p class="timeline-text">
                                        {{ $bookingDeposit->approved_at->format('d/m/Y H:i') }}
                                        @if($bookingDeposit->approvedBy)
                                            <br><small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                Bởi: {{ $bookingDeposit->approvedBy->userProfile->full_name ?? $bookingDeposit->approvedBy->full_name ?? 'N/A' }}
                                            </small>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @endif
                            
                            @if($bookingDeposit->paid_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Đã thanh toán</h6>
                                    <p class="timeline-text">{{ $bookingDeposit->paid_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            @endif
                            
                            @if($bookingDeposit->refunded_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-secondary"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Đã hoàn tiền</h6>
                                    <p class="timeline-text">{{ $bookingDeposit->refunded_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
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
<style>
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.form-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.fw-bold {
    font-weight: 600;
}

.badge {
    font-size: 0.8rem;
    padding: 0.5rem 0.75rem;
    border-radius: 10px;
}

.btn {
    border-radius: 10px;
    padding: 12px 20px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-marker {
    position: absolute;
    left: -2rem;
    top: 0.25rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 3px #e9ecef;
}

.timeline-content {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 10px;
    border-left: 3px solid #e9ecef;
}

.timeline-title {
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #495057;
}

.timeline-text {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0;
}

</style>
@endpush


@push('scripts')
<script>
// Countdown timer for payment due date
let countdownInterval = null;

function initCountdownTimer() {
    const timer = document.getElementById('payment-due-countdown');
    if (!timer) return;
    
    updateCountdown(timer);
    
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    countdownInterval = setInterval(() => {
        updateCountdown(timer);
    }, 1000);
}

function updateCountdown(timerElement) {
    const dueDateStr = timerElement.getAttribute('data-due-date');
    if (!dueDateStr) return;
    
    const dueDate = new Date(dueDateStr.replace(' ', 'T'));
    const now = new Date();
    const diff = dueDate - now;
    
    const countdownText = timerElement.querySelector('.countdown-text');
    if (!countdownText) return;
    
    if (diff <= 0) {
        countdownText.textContent = 'Đã quá hạn';
        timerElement.classList.remove('text-info');
        timerElement.classList.add('text-danger');
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
        return;
    }
    
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    let timeString = '';
    if (days > 0) {
        timeString = `${days} ngày ${hours} giờ ${minutes} phút`;
    } else if (hours > 0) {
        timeString = `${hours} giờ ${minutes} phút ${seconds} giây`;
    } else if (minutes > 0) {
        timeString = `${minutes} phút ${seconds} giây`;
    } else {
        timeString = `${seconds} giây`;
    }
    
    countdownText.textContent = `Còn lại: ${timeString}`;
    
    // Change color based on remaining time
    if (days === 0 && hours < 24) {
        timerElement.classList.remove('text-info');
        timerElement.classList.add('text-warning');
    }
    if (days === 0 && hours < 1) {
        timerElement.classList.remove('text-warning');
        timerElement.classList.add('text-danger');
    }
}

// Initialize countdown on page load
document.addEventListener('DOMContentLoaded', function() {
    initCountdownTimer();
    
    @if(session('warning'))
        Notify.warning('{{ session('warning') }}', 'Cảnh báo!');
    @endif
});

function approveDeposit(id) {
    Notify.confirm('Bạn có chắc chắn muốn duyệt đặt cọc này?', function() {
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });
        
        fetch(`/staff/booking-deposits/${id}/approve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
        .then(response => {
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Duyệt thành công!');
                setTimeout(() => location.reload(), 1500);
            } else {
                Notify.error(data.message, 'Không thể duyệt đặt cọc');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi duyệt đặt cọc. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
    });
}

function markPaid(id) {
    Notify.confirm('Bạn có chắc chắn muốn đánh dấu đã thanh toán?', function() {
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });
        
        fetch(`/staff/booking-deposits/${id}/mark-paid`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
        .then(response => {
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Cập nhật thành công!');
                setTimeout(() => location.reload(), 1500);
            } else {
                Notify.error(data.message, 'Không thể cập nhật trạng thái');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi cập nhật trạng thái. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
    });
}

function refundDeposit(id) {
    Notify.confirm('Bạn có chắc chắn muốn hoàn tiền cho đặt cọc này?', function() {
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });
        
        fetch(`/staff/booking-deposits/${id}/refund`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
        .then(response => {
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Hoàn tiền thành công!');
                setTimeout(() => location.reload(), 1500);
            } else {
                Notify.error(data.message, 'Không thể hoàn tiền');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi hoàn tiền. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
    });
}

function cancelDeposit(id) {
    Notify.confirm('Bạn có chắc chắn muốn hủy đặt cọc này?', function() {
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });
        
        fetch(`/staff/booking-deposits/${id}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
        .then(response => {
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Hủy đặt cọc thành công!');
                setTimeout(() => location.reload(), 1500);
            } else {
                Notify.error(data.message, 'Không thể hủy đặt cọc');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi hủy đặt cọc. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
    });
}

function deleteDeposit(id, reference) {
    Notify.confirmDelete(`đặt cọc "${reference}"`, function() {
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });
        
        fetch(`/staff/booking-deposits/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
        .then(response => {
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Xóa thành công!');
                setTimeout(() => {
                    window.location.href = '{{ route("staff.booking-deposits.index") }}';
                }, 1500);
            } else {
                Notify.error(data.message, 'Không thể xóa đặt cọc');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi xóa đặt cọc. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
    });
}

// Status change function
window.updateStatus = function(status) {
    const statusLabels = {
        'pending_approval': 'Chờ duyệt',
        'pending': 'Chờ thanh toán',
        'paid': 'Đã thanh toán',
        'refunded': 'Đã hoàn tiền',
        'expired': 'Hết hạn',
        'cancelled': 'Đã hủy',
    };
    
    const currentStatus = '{{ $bookingDeposit->payment_status }}';
    const currentLabel = statusLabels[currentStatus] || currentStatus;
    const newLabel = statusLabels[status] || status;
    
    Notify.confirm({
        title: 'Xác nhận chuyển trạng thái',
        message: `Bạn có chắc chắn muốn chuyển đặt cọc từ trạng thái "${currentLabel}" sang "${newLabel}"?`,
        type: 'warning',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("staff.booking-deposits.update-status", $bookingDeposit->id) }}';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfInput);
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'payment_status';
            statusInput.value = status;
            form.appendChild(statusInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
};

// Create invoice function
window.createInvoice = function(id) {
    Notify.confirm({
        title: 'Xác nhận tạo hóa đơn',
        message: 'Bạn có chắc chắn muốn tạo hóa đơn cho đặt cọc này?',
        type: 'success',
        confirmText: 'Tạo hóa đơn',
        cancelText: 'Hủy',
        onConfirm: function() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("staff.booking-deposits.create-invoice", $bookingDeposit->id) }}';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
};

// Create lease function
window.createLease = function(id) {
    Notify.confirm({
        title: 'Xác nhận tạo hợp đồng',
        message: 'Bạn có chắc chắn muốn tạo hợp đồng từ đặt cọc này?',
        type: 'success',
        confirmText: 'Tạo hợp đồng',
        cancelText: 'Hủy',
        onConfirm: function() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("staff.booking-deposits.create-lease", $bookingDeposit->id) }}';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
};

// Status change with notification system (for form-based status changes)
document.querySelectorAll('.update-status-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const form = this.closest('form');
        if (!form) {
            console.error('Form not found');
            return;
        }
        
        const status = form.dataset.status || form.querySelector('input[name="payment_status"]')?.value;
        if (!status) {
            console.error('Status not found');
            return;
        }
        
        // Get status labels
        const statusLabels = {
            'pending_approval': 'Chờ duyệt',
            'pending': 'Chờ thanh toán',
            'paid': 'Đã thanh toán',
            'refunded': 'Đã hoàn tiền',
            'expired': 'Hết hạn',
            'cancelled': 'Đã hủy',
        };
        
        const currentStatus = '{{ $bookingDeposit->payment_status }}';
        const newStatus = status;
        const currentLabel = statusLabels[currentStatus] || currentStatus;
        const newLabel = statusLabels[newStatus] || newStatus;
        
        // Use notification system
        Notify.confirm({
            title: 'Xác nhận chuyển trạng thái',
            message: `Bạn có chắc chắn muốn chuyển đặt cọc từ trạng thái "${currentLabel}" sang "${newLabel}"?`,
            type: 'warning',
            confirmText: 'Xác nhận',
            cancelText: 'Hủy',
            onConfirm: () => {
                form.submit();
            }
        });
    });
});
</script>
@endpush
