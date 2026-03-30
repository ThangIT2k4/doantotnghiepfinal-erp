@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết phòng')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header (không có actions) --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết phòng',
            'subtitle' => 'Thông tin chi tiết về phòng: ' . $unit->code,
            'icon' => 'fas fa-building',
            'breadcrumbs' => [
                ['label' => 'Phòng', 'url' => route('staff.units.index')],
                ['label' => $unit->code, 'active' => true]
            ]
        ])

        <!-- Unit Images -->
        @if($unit->images && count($unit->images) > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-images me-2"></i>Hình ảnh phòng
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($unit->images as $index => $image)
                            @php
                                // Handle both old format (array) and new format (string)
                                $imagePath = is_array($image) ? ($image['original'] ?? $image['url'] ?? $image) : $image;
                                
                                // Check if already full URL
                                if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
                                    $imageUrl = $imagePath;
                                } else {
                                    // Path đã không có storage/ prefix, chỉ cần thêm vào URL
                                    $imageUrl = asset('storage/' . ltrim($imagePath, '/'));
                                }
                                
                                $imageFilename = is_array($image) ? ($image['filename'] ?? basename($imagePath)) : basename($imagePath);
                            @endphp
                            <div class="col-md-3 col-sm-4 col-6">
                                <div class="image-item position-relative">
                                    <img src="{{ $imageUrl }}" 
                                         alt="Hình ảnh phòng {{ $unit->code }}" 
                                         class="img-fluid rounded shadow-sm"
                                         style="height: 150px; object-fit: cover; width: 100%; cursor: pointer;"
                                         onclick="openImageModal('{{ $imageUrl }}', '{{ $imageFilename }}')">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- 2. Content --}}
        <div class="row mb-4">
            {{-- Nội dung chính --}}
            <div class="col-lg-8">
                {{-- Unit Information --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Mã phòng:</label>
                                <p class="mb-0">{{ $unit->code }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Bất động sản:</label>
                                <p class="mb-0">{{ $unit->property->name }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tầng:</label>
                                <p class="mb-0">Tầng {{ $unit->floor }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Diện tích:</label>
                                <p class="mb-0">{{ number_format($unit->area_m2, 2) }}m²</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Loại phòng:</label>
                                <p class="mb-0">
                                    <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $unit->unit_type)) }}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Số người tối đa:</label>
                                <p class="mb-0">{{ $unit->max_occupancy }} người</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Giá thuê cơ bản:</label>
                                <p class="mb-0 text-primary fw-bold">{{ number_format($unit->base_rent) }}đ</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tiền cọc:</label>
                                <p class="mb-0">{{ $unit->deposit_amount ? number_format($unit->deposit_amount) . 'đ' : 'Chưa cập nhật' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Trạng thái:</label>
                                <p class="mb-0">
                                    @switch($unit->status)
                                        @case('available')
                                            <span class="badge bg-success">Trống</span>
                                            @break
                                        @case('occupied')
                                            <span class="badge bg-primary">Đã thuê</span>
                                            @break
                                        @case('maintenance')
                                            <span class="badge bg-warning">Bảo trì</span>
                                            @break
                                        @case('unavailable')
                                            <span class="badge bg-danger">Không khả dụng</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $unit->status }}</span>
                                    @endswitch
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Ngày tạo:</label>
                                <p class="mb-0">{{ $unit->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            @if($unit->note)
                            <div class="col-12">
                                <label class="form-label fw-bold">Ghi chú:</label>
                                <p class="mb-0">{{ $unit->note }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card "Thông tin khách hàng" và "Thao tác" bên phải --}}
            <div class="col-lg-4">
                {{-- Statistics Card --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Thống kê
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Tổng hợp đồng:</span>
                                    <span class="fw-bold">{{ $stats['total_leases'] }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Hợp đồng hoạt động:</span>
                                    <span class="fw-bold text-success">{{ $stats['active_leases'] }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Tổng doanh thu:</span>
                                    <span class="fw-bold text-primary">{{ number_format($stats['total_revenue']) }}đ</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Còn nợ:</span>
                                    <span class="fw-bold text-danger">{{ number_format($stats['outstanding_amount']) }}đ</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Đặt cọc:</span>
                                    <span class="fw-bold text-info">{{ $stats['booking_deposits'] }}</span>
                                </div>
                            </div>
                            @if($stats['average_rent'])
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Giá thuê TB:</span>
                                    <span class="fw-bold text-warning">{{ number_format($stats['average_rent']) }}đ</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Thao tác --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Thao tác
                        </h6>
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
                                    'url' => route('staff.units.edit', $unit->id),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteUnit({$unit->id}, '" . addslashes($unit->code) . "')",
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Quay lại',
                                    'icon' => 'fas fa-arrow-left',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.units.index'),
                                    'class' => 'w-100'
                                ]
                            ];
                            
                            // Additional actions: Tạo đặt cọc, Tạo hợp đồng, Tạo lịch hẹn, Lịch sử bảo trì
                            $additionalActions = [];
                            
                            if (!$unit->currentLease && $unit->status == 'available') {
                                $additionalActions[] = [
                                    'type' => 'link',
                                    'variant' => 'success',
                                    'label' => 'Tạo đặt cọc',
                                    'icon' => 'fas fa-hand-holding-usd',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.booking-deposits.create', ['property_id' => $unit->property_id, 'unit_id' => $unit->id]),
                                    'class' => 'w-100'
                                ];
                                $additionalActions[] = [
                                    'type' => 'link',
                                    'variant' => 'primary',
                                    'label' => 'Tạo hợp đồng',
                                    'icon' => 'fas fa-file-contract',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.leases.create', ['property_id' => $unit->property_id, 'unit_id' => $unit->id]),
                                    'class' => 'w-100'
                                ];
                            }
                            
                            // Tạo lịch hẹn - disable khi unit đã thuê
                            $viewingUrl = route('staff.viewings.create', ['property_id' => $unit->property_id, 'unit_id' => $unit->id]);
                            $isUnitRented = $unit->status === 'occupied' || $unit->is_rented;
                            $additionalActions[] = [
                                'type' => 'link',
                                'variant' => 'info',
                                'label' => 'Tạo lịch hẹn',
                                'icon' => 'fas fa-calendar-plus',
                                'iconPosition' => 'left',
                                'url' => $isUnitRented ? '#' : $viewingUrl,
                                'class' => 'w-100' . ($isUnitRented ? ' disabled' : ''),
                                'disabled' => $isUnitRented,
                                'title' => $isUnitRented ? 'Không thể tạo lịch hẹn cho phòng đã thuê' : ''
                            ];
                            
                            $additionalActions[] = [
                                'type' => 'link',
                                'variant' => 'info',
                                'label' => 'Lịch sử bảo trì',
                                'icon' => 'fas fa-tools',
                                'iconPosition' => 'left',
                                'url' => route('staff.units.maintenance-history', $unit->id),
                                'class' => 'w-100'
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            if($unit->status != 'available') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Chuyển về Trống',
                                    'icon' => 'fas fa-door-open',
                                    'onclick' => "updateUnitStatus('available')"
                                ];
                            }
                            
                            if($unit->status != 'occupied') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'primary',
                                    'label' => 'Chuyển về Đã thuê',
                                    'icon' => 'fas fa-home',
                                    'onclick' => "updateUnitStatus('occupied')"
                                ];
                            }
                            
                            if($unit->status != 'maintenance') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'warning',
                                    'label' => 'Chuyển về Bảo trì',
                                    'icon' => 'fas fa-tools',
                                    'onclick' => "updateUnitStatus('maintenance')"
                                ];
                            }
                            
                            if($unit->status != 'unavailable') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Chuyển về Không khả dụng',
                                    'icon' => 'fas fa-ban',
                                    'onclick' => "updateUnitStatus('unavailable')"
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
                            
                            {{-- Additional Actions: Tạo đặt cọc, Tạo hợp đồng, Lịch sử bảo trì (vertical) --}}
                            @if(count($additionalActions) > 0)
                                @include('staff.components.action-buttons', [
                                    'layout' => 'vertical',
                                    'size' => 'sm',
                                    'actions' => $additionalActions
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
            </div>
        </div>

        <!-- Current Lease -->
        @if($unit->currentLease)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-file-contract me-2"></i>Hợp đồng hiện tại
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Khách hàng:</label>
                                <p class="mb-0">{{ $unit->currentLease->tenant->full_name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Giá thuê:</label>
                                <p class="mb-0 text-primary fw-bold">{{ number_format($unit->currentLease->rent_amount) }}đ</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Ngày bắt đầu:</label>
                                <p class="mb-0">{{ $unit->currentLease->start_date ? \Carbon\Carbon::parse($unit->currentLease->start_date)->format('d/m/Y') : 'N/A' }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Ngày kết thúc:</label>
                                <p class="mb-0">{{ $unit->currentLease->end_date ? \Carbon\Carbon::parse($unit->currentLease->end_date)->format('d/m/Y') : 'N/A' }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Trạng thái:</label>
                                <p class="mb-0">
                                    @switch($unit->currentLease->status)
                                        @case('draft')
                                            <span class="badge bg-secondary">Nháp</span>
                                            @break
                                        @case('active')
                                            <span class="badge bg-success">Hoạt động</span>
                                            @break
                                        @case('terminated')
                                            <span class="badge bg-danger">Chấm dứt</span>
                                            @break
                                        @case('expired')
                                            <span class="badge bg-warning">Hết hạn</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($unit->currentLease->status) }}</span>
                                    @endswitch
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Ngày tạo:</label>
                                <p class="mb-0">{{ $unit->currentLease->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif


        <!-- Tabs -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <ul class="nav nav-tabs card-header-tabs" id="unitTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="leases-tab" data-bs-toggle="tab" data-bs-target="#leases" type="button" role="tab">
                                    <i class="fas fa-file-contract me-1"></i>Hợp đồng ({{ $leases->count() }})
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="deposits-tab" data-bs-toggle="tab" data-bs-target="#deposits" type="button" role="tab">
                                    <i class="fas fa-hand-holding-usd me-1"></i>Đặt cọc ({{ $bookingDeposits->count() }})
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab">
                                    <i class="fas fa-receipt me-1"></i>Hóa đơn ({{ $invoices->count() }})
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tickets-tab" data-bs-toggle="tab" data-bs-target="#tickets" type="button" role="tab">
                                    <i class="fas fa-ticket-alt me-1"></i>Ticket ({{ $tickets->count() }})
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="meters-tab" data-bs-toggle="tab" data-bs-target="#meters" type="button" role="tab">
                                    <i class="fas fa-tachometer-alt me-1"></i>Công tơ đo ({{ isset($meters) ? $meters->count() : 0 }})
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="unitTabsContent">
                            <!-- Leases Tab -->
                            <div class="tab-pane fade show active" id="leases" role="tabpanel">
                                @if($leases->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Khách hàng</th>
                                                    <th>Giá thuê</th>
                                                    <th>Ngày bắt đầu</th>
                                                    <th>Ngày kết thúc</th>
                                                    <th>Trạng thái</th>
                                                    <th>Doanh thu</th>
                                                    <th>Còn nợ</th>
                                                    <th>Ngày tạo</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($leases as $lease)
                                                <tr>
                                                    <td>{{ $lease->tenant->full_name ?? 'N/A' }}</td>
                                                    <td class="text-primary fw-bold">{{ number_format($lease->rent_amount) }}đ</td>
                                                    <td>{{ $lease->start_date ? \Carbon\Carbon::parse($lease->start_date)->format('d/m/Y') : 'N/A' }}</td>
                                                    <td>{{ $lease->end_date ? \Carbon\Carbon::parse($lease->end_date)->format('d/m/Y') : 'N/A' }}</td>
                                                    <td>
                                                        @switch($lease->status)
                                                            @case('draft')
                                                                <span class="badge bg-secondary">Nháp</span>
                                                                @break
                                                            @case('active')
                                                                <span class="badge bg-success">Hoạt động</span>
                                                                @break
                                                            @case('terminated')
                                                                <span class="badge bg-danger">Chấm dứt</span>
                                                                @break
                                                            @case('expired')
                                                                <span class="badge bg-warning">Hết hạn</span>
                                                                @break
                                                            @default
                                                                <span class="badge bg-secondary">{{ ucfirst($lease->status) }}</span>
                                                        @endswitch
                                                    </td>
                                                    <td>
                                                        @php
                                                            $leaseRevenue = \App\Models\Payment::whereHas('invoice', function($q) use ($lease) {
                                                                $q->where('lease_id', $lease->id);
                                                            })->where('status', 'success')->sum('amount');
                                                        @endphp
                                                        <span class="text-success fw-bold">{{ number_format($leaseRevenue) }}đ</span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $leaseInvoices = \App\Models\Invoice::where('lease_id', $lease->id)
                                                                ->whereIn('status', ['issued', 'overdue'])
                                                                ->get();
                                                            $leaseOutstanding = $leaseInvoices->sum(function($invoice) {
                                                                $paidAmount = $invoice->payments()->where('status', 'success')->sum('amount');
                                                                return max(0, $invoice->total_amount - $paidAmount);
                                                            });
                                                        @endphp
                                                        @if($leaseOutstanding > 0)
                                                            <span class="text-danger fw-bold">{{ number_format($leaseOutstanding) }}đ</span>
                                                        @else
                                                            <span class="text-success">Đã thanh toán</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $lease->created_at->format('d/m/Y H:i') }}</td>
                                                    <td>
                                                        <a href="{{ route('staff.leases.show', $lease->id) }}" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i> Xem
                                                        </a>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Chưa có hợp đồng nào</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Booking Deposits Tab -->
                            <div class="tab-pane fade" id="deposits" role="tabpanel">
                                @if($bookingDeposits->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Khách hàng</th>
                                                    <th>Số tiền</th>
                                                    <th>Loại cọc</th>
                                                    <th>Trạng thái</th>
                                                    <th>Ngày hết hạn</th>
                                                    <th>Ngày tạo</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($bookingDeposits as $deposit)
                                                <tr>
                                                    <td>
                                                        @if($deposit->tenantUser)
                                                            {{ $deposit->tenantUser->full_name ?? 'N/A' }}
                                                        @elseif($deposit->lead)
                                                            {{ $deposit->lead->name ?? 'N/A' }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td class="text-primary fw-bold">{{ number_format($deposit->amount) }}đ</td>
                                                    <td>
                                                        @switch($deposit->deposit_type)
                                                            @case('booking')
                                                                <span class="badge bg-info">Đặt cọc</span>
                                                                @break
                                                            @case('security')
                                                                <span class="badge bg-warning">Cọc an ninh</span>
                                                                @break
                                                            @case('advance')
                                                                <span class="badge bg-success">Tạm ứng</span>
                                                                @break
                                                            @default
                                                                <span class="badge bg-secondary">{{ ucfirst($deposit->deposit_type) }}</span>
                                                        @endswitch
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $deposit->getStatusBadgeClass() }}">
                                                            {{ $deposit->getStatusText() }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($deposit->hold_until)
                                                            {{ \Carbon\Carbon::parse($deposit->hold_until)->format('d/m/Y H:i') }}
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $deposit->created_at->format('d/m/Y H:i') }}</td>
                                                    <td>
                                                        <a href="{{ route('staff.booking-deposits.show', $deposit->id) }}" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-eye"></i> Xem
                                                        </a>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <i class="fas fa-hand-holding-usd fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Chưa có đặt cọc nào</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Invoices Tab -->
                            <div class="tab-pane fade" id="invoices" role="tabpanel">
                                @if($invoices->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Số hóa đơn</th>
                                                    <th>Khách hàng</th>
                                                    <th>Số tiền</th>
                                                    <th>Đã thanh toán</th>
                                                    <th>Còn nợ</th>
                                                    <th>Trạng thái</th>
                                                    <th>Ngày đến hạn</th>
                                                    <th>Ngày tạo</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($invoices as $invoice)
                                                <tr>
                                                    <td class="fw-bold">{{ $invoice->invoice_no ?? 'N/A' }}</td>
                                                    <td>{{ $invoice->lease->tenant->full_name ?? 'N/A' }}</td>
                                                    <td class="text-primary fw-bold">{{ number_format($invoice->total_amount) }}đ</td>
                                                    <td>
                                                        @php
                                                            $paidAmount = $invoice->payments()->where('status', 'success')->sum('amount');
                                                        @endphp
                                                        <span class="text-success fw-bold">{{ number_format($paidAmount) }}đ</span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $outstanding = $invoice->total_amount - $paidAmount;
                                                        @endphp
                                                        @if($outstanding > 0)
                                                            <span class="text-danger fw-bold">{{ number_format($outstanding) }}đ</span>
                                                        @else
                                                            <span class="text-success">Đã thanh toán</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @switch($invoice->status)
                                                            @case('draft')
                                                                <span class="badge bg-secondary">Nháp</span>
                                                                @break
                                                            @case('issued')
                                                                <span class="badge bg-info">Đã phát hành</span>
                                                                @break
                                                            @case('paid')
                                                                <span class="badge bg-success">Đã thanh toán</span>
                                                                @break
                                                            @case('overdue')
                                                                <span class="badge bg-danger">Quá hạn</span>
                                                                @break
                                                            @case('cancelled')
                                                                <span class="badge bg-warning">Đã hủy</span>
                                                                @break
                                                            @default
                                                                <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                                        @endswitch
                                                    </td>
                                                    <td>
                                                        @if($invoice->due_date)
                                                            {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                                                    <td>
                                                        <a href="{{ route('staff.invoices.show', $invoice->id) }}" class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-eye"></i> Xem
                                                        </a>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Chưa có hóa đơn nào</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Tickets Tab -->
                            <div class="tab-pane fade" id="tickets" role="tabpanel">
                                @if($tickets->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Tiêu đề</th>
                                                    <th>Mô tả</th>
                                                    <th>Độ ưu tiên</th>
                                                    <th>Trạng thái</th>
                                                    <th>Người tạo</th>
                                                    <th>Người phụ trách</th>
                                                    <th>Ngày tạo</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($tickets as $ticket)
                                                <tr>
                                                    <td class="fw-bold">{{ $ticket->title }}</td>
                                                    <td>{{ Str::limit($ticket->description, 50) }}</td>
                                                    <td>
                                                        @switch($ticket->priority)
                                                            @case('low')
                                                                <span class="badge bg-info">Thấp</span>
                                                                @break
                                                            @case('medium')
                                                                <span class="badge bg-warning">Trung bình</span>
                                                                @break
                                                            @case('high')
                                                                <span class="badge bg-danger">Cao</span>
                                                                @break
                                                            @case('urgent')
                                                                <span class="badge bg-dark">Khẩn cấp</span>
                                                                @break
                                                            @default
                                                                <span class="badge bg-secondary">{{ ucfirst($ticket->priority) }}</span>
                                                        @endswitch
                                                    </td>
                                                    <td>
                                                        @switch($ticket->status)
                                                            @case('open')
                                                                <span class="badge bg-primary">Mở</span>
                                                                @break
                                                            @case('in_progress')
                                                                <span class="badge bg-warning">Đang xử lý</span>
                                                                @break
                                                            @case('resolved')
                                                                <span class="badge bg-success">Đã giải quyết</span>
                                                                @break
                                                            @case('closed')
                                                                <span class="badge bg-secondary">Đã đóng</span>
                                                                @break
                                                            @case('cancelled')
                                                                <span class="badge bg-danger">Đã hủy</span>
                                                                @break
                                                            @default
                                                                <span class="badge bg-secondary">{{ ucfirst($ticket->status) }}</span>
                                                        @endswitch
                                                    </td>
                                                    <td>{{ $ticket->createdBy->full_name ?? 'N/A' }}</td>
                                                    <td>{{ $ticket->assignedTo->full_name ?? 'Chưa phân công' }}</td>
                                                    <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                                                    <td>
                                                        <a href="{{ route('staff.tickets.show', $ticket->id) }}" class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-eye"></i> Xem
                                                        </a>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Chưa có ticket nào</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Meters Tab -->
                            <div class="tab-pane fade" id="meters" role="tabpanel">
                                <div class="d-flex justify-content-end mb-3">
                                    <div class="btn-group">
                                        <a href="{{ route('staff.meters.create', ['unit_id' => $unit->id, 'property_id' => $unit->property_id]) }}" class="btn btn-sm btn-success" title="Tạo công tơ mới">
                                            <i class="fas fa-plus"></i> Tạo Công tơ
                                        </a>
                                        @if(isset($meters) && $meters->count() > 0)
                                            <button type="button" class="btn btn-sm btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="visually-hidden">Toggle Dropdown</span>
                                            </button>
                                            <ul class="dropdown-menu">
                                                @foreach($meters as $meter)
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('staff.meter-readings.create', ['meter_id' => $meter->id]) }}">
                                                        <i class="fas fa-tachometer-alt me-2"></i>Thêm số đo - {{ $meter->service->name ?? 'Meter #' . $meter->id }} ({{ $meter->serial_no }})
                                                    </a>
                                                </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                                @if(isset($meters) && $meters->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Công tơ</th>
                                                    <th>Serial</th>
                                                    <th>Dịch vụ</th>
                                                    <th>Số đo cuối</th>
                                                    <th>Ngày đo cuối</th>
                                                    <th>Trạng thái</th>
                                                    <th>Ngày lắp đặt</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($meters as $meter)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('staff.meters.show', $meter->id) }}" class="text-decoration-none fw-bold">
                                                            {{ $meter->service->name ?? 'Meter #' . $meter->id }}
                                                        </a>
                                                    </td>
                                                    <td><code>{{ $meter->serial_no }}</code></td>
                                                    <td>{{ $meter->service->name ?? 'N/A' }}</td>
                                                    <td>
                                                        @if($meter->readings && $meter->readings->count() > 0)
                                                            <strong class="text-primary">{{ number_format($meter->readings->first()->value, 3) }}</strong>
                                                            @if($meter->service)
                                                                <small class="text-muted">{{ $meter->service->unit_label }}</small>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">Chưa có</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($meter->readings && $meter->readings->count() > 0)
                                                            {{ $meter->readings->first()->reading_date->format('d/m/Y') }}
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $meter->status ? 'bg-success' : 'bg-secondary' }}">
                                                            {{ $meter->status ? 'Hoạt động' : 'Ngừng hoạt động' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($meter->installed_at)
                                                            {{ \Carbon\Carbon::parse($meter->installed_at)->format('d/m/Y') }}
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="{{ route('staff.meters.show', $meter->id) }}" class="btn btn-outline-primary" title="Xem chi tiết">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="{{ route('staff.meter-readings.create', ['meter_id' => $meter->id]) }}" class="btn btn-outline-success" title="Thêm số đo">
                                                                <i class="fas fa-plus"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <i class="fas fa-tachometer-alt fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Chưa có công tơ nào được lắp đặt cho phòng này</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Hình ảnh phòng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Hình ảnh phòng" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if Notify is available
    if (typeof window.Notify === 'undefined') {
        // Fallback to native confirm
        window.deleteUnit = function(id, name) {
            if (confirm(`Bạn có chắc chắn muốn xóa phòng "${name}"?`)) {
                deleteUnitAction(id);
            }
        };
    } else {
        window.deleteUnit = function(id, name) {
            Notify.confirmDelete(`phòng "${name}"`, () => {
                deleteUnitAction(id);
            });
        };
    }
});

function deleteUnitAction(id) {
    // Show preloader
    if (window.Preloader) {
        window.Preloader.show();
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error('CSRF token not found');
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
        } else {
            alert('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.');
        }
        if (window.Preloader) {
            window.Preloader.hide();
        }
        return;
    }

    fetch(`/staff/units/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof window.Notify !== 'undefined') {
                Notify.success(data.message || 'Đã xóa phòng thành công!', 'Đã xóa!');
            } else {
                alert('Đã xóa phòng thành công!');
            }
            setTimeout(() => {
                window.location.href = '{{ route("staff.units.index") }}';
            }, 1000);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa phòng', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra khi xóa phòng: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể xóa phòng: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        } else {
            alert('Không thể xóa phòng: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
        }
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
}

function openImageModal(imageUrl, filename) {
    document.getElementById('modalImage').src = imageUrl;
    document.getElementById('imageModalLabel').textContent = filename;
    new bootstrap.Modal(document.getElementById('imageModal')).show();
}

function updateUnitStatus(newStatus) {
    const statusLabels = {
        'available': 'Trống',
        'occupied': 'Đã thuê',
        'maintenance': 'Bảo trì',
        'unavailable': 'Không khả dụng'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển sang trạng thái "${statusLabels[newStatus]}"?`,
        type: newStatus === 'unavailable' ? 'danger' : 'warning',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const formData = new FormData();
            formData.append('status', newStatus);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('{{ route("staff.units.update-status", $unit->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
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
}

function deleteImage(unitId, imagePath) {
    if (confirm('Bạn có chắc chắn muốn xóa hình ảnh này?')) {
        // Show loading
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        fetch(`/staff/units/${unitId}/images`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                image_path: imagePath
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove image from DOM
                const imageContainer = button.closest('.col-md-3');
                imageContainer.remove();
                
                // Check if no images left
                const remainingImages = document.querySelectorAll('.image-item');
                if (remainingImages.length === 0) {
                    // Hide images section
                    const imagesSection = document.querySelector('.row.mb-4');
                    if (imagesSection) {
                        imagesSection.remove();
                    }
                }
                
                Notify.success('Đã xóa hình ảnh thành công!');
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa hình ảnh.');
                button.innerHTML = originalContent;
                button.disabled = false;
            }
        })
        .catch(error => {
            Notify.error('Có lỗi xảy ra khi xóa hình ảnh.');
            button.innerHTML = originalContent;
            button.disabled = false;
        });
    }
}

// Display flash messages as toast notifications
document.addEventListener('DOMContentLoaded', function() {
    @if(session('success'))
        Notify.success('{{ session('success') }}', 'Thành công!');
    @endif
    
    @if(session('error'))
        Notify.error('{{ session('error') }}', 'Lỗi!');
    @endif
    
    @if(session('warning'))
        Notify.warning('{{ session('warning') }}', 'Cảnh báo!');
    @endif
    
    @if(session('info'))
        Notify.info('{{ session('info') }}', 'Thông tin!');
    @endif
});
</script>
@endpush

@push('styles')
<style>
.image-item {
    transition: transform 0.3s ease;
}

.image-item:hover {
    transform: scale(1.05);
}

.btn-delete-image {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.image-item:hover .btn-delete-image {
    opacity: 1;
}

#modalImage {
    max-height: 70vh;
    object-fit: contain;
}
</style>
@endpush

