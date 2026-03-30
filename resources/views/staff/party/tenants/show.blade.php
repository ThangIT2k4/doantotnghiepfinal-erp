@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết khách hàng')

@section('content')
<main class="main-content">
<div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết khách hàng',
            'subtitle' => 'Thông tin chi tiết về khách hàng: ' . ($tenant->userProfile->full_name ?? $tenant->email),
            'icon' => 'fas fa-user',
            'breadcrumbs' => [
                ['label' => 'Khách hàng', 'url' => route('staff.tenants.index')],
                ['label' => $tenant->userProfile->full_name ?? $tenant->email, 'active' => true]
            ]
        ])

        <!-- Tabs Navigation -->
        @include('staff.components.tab-navigation', [
            'tabs' => [
                'leases' => [
                    'label' => 'Hợp đồng',
                    'icon' => 'fas fa-file-contract',
                    'color' => 'primary',
                    'badge' => $leases->count()
                ],
                'booking-deposits' => [
                    'label' => 'Đặt cọc',
                    'icon' => 'fas fa-money-bill-wave',
                    'color' => 'success',
                    'badge' => $bookingDeposits->count()
                ],
                'invoices' => [
                    'label' => 'Hóa đơn',
                    'icon' => 'fas fa-file-invoice',
                    'color' => 'info',
                    'badge' => $invoices->count()
                ],
                'tickets' => [
                    'label' => 'Ticket',
                    'icon' => 'fas fa-ticket-alt',
                    'color' => 'warning',
                    'badge' => $tickets->count()
                ]
            ],
            'storageKey' => 'tenantTabStates',
            'defaultVisible' => ['leases']
        ])

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng hợp đồng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_leases'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-contract fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Hợp đồng hoạt động</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active_leases'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tổng thanh toán</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['total_payments'], 0, ',', '.') }}đ
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Nợ còn lại</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($stats['outstanding_amount'], 0, ',', '.') }}đ
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Content Sections -->
        <div class="col-lg-8">
            <!-- Leases Section -->
            <div class="card shadow-sm mb-4 tab-content" id="tab-leases">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-file-contract me-2"></i> Hợp đồng ({{ $leases->count() }})
                    </h6>
                    <a href="{{ route('staff.leases.index', ['tenant_id' => $tenant->id]) }}" class="btn btn-sm btn-light">
                        <i class="fas fa-eye me-1"></i> Xem tất cả
                    </a>
                </div>
                <div class="card-body">
                    @if($leases->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Mã hợp đồng</th>
                                    <th>Phòng</th>
                                    <th>Ngày bắt đầu</th>
                                    <th>Ngày kết thúc</th>
                                    <th>Tiền thuê</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($leases as $lease)
                                <tr>
                                    <td>{{ $lease->contract_no ?? 'N/A' }}</td>
                                    <td>{{ $lease->unit->property->name ?? 'N/A' }} - {{ $lease->unit->code ?? 'N/A' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($lease->start_date)->format('d/m/Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($lease->end_date)->format('d/m/Y') }}</td>
                                    <td>{{ number_format($lease->rent_amount, 0, ',', '.') }}đ</td>
                                    <td>
                                        @switch($lease->status)
                                            @case('active') 
                                                <span style="background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Hoạt động</span> 
                                                @break
                                            @case('draft') 
                                                <span style="background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Nháp</span> 
                                                @break
                                            @case('terminated') 
                                                <span style="background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Chấm dứt</span> 
                                                @break
                                            @case('expired') 
                                                <span style="background-color: #ffc107; color: #212529; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Hết hạn</span> 
                                                @break
                                            @default 
                                                <span style="background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">{{ $lease->status }}</span> 
                                                @break
                                        @endswitch
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-file-contract fa-3x text-gray-300 mb-3"></i>
                        <p class="text-muted">Chưa có hợp đồng nào</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Booking Deposits Section -->
            <div class="card shadow-sm mb-4 tab-content" id="tab-booking-deposits" style="display: none;">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i> Đặt cọc ({{ $bookingDeposits->count() }})
                    </h6>
                    <a href="{{ route('staff.booking-deposits.index', ['tenant_id' => $tenant->id]) }}" class="btn btn-sm btn-light">
                        <i class="fas fa-eye me-1"></i> Xem tất cả
                    </a>
                </div>
                <div class="card-body">
                    @if($bookingDeposits->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Phòng</th>
                                    <th>Số tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bookingDeposits as $deposit)
                                <tr>
                                    <td>{{ $deposit->unit->property->name ?? 'N/A' }} - {{ $deposit->unit->code ?? 'N/A' }}</td>
                                    <td>{{ number_format($deposit->amount, 0, ',', '.') }}đ</td>
                                    <td>
                                        @switch($deposit->payment_status)
                                            @case('pending') <span class="badge badge-warning">Chờ xử lý</span> @break
                                            @case('paid') <span class="badge badge-success">Đã thanh toán</span> @break
                                            @case('refunded') <span class="badge badge-info">Đã hoàn</span> @break
                                            @case('cancelled') <span class="badge badge-danger">Đã hủy</span> @break
                                        @endswitch
                                    </td>
                                    <td>{{ $deposit->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-money-bill-wave fa-3x text-gray-300 mb-3"></i>
                        <p class="text-muted">Chưa có đặt cọc nào</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Invoices Section -->
            <div class="card shadow-sm mb-4 tab-content" id="tab-invoices" style="display: none;">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-file-invoice me-2"></i> Hóa đơn ({{ $invoices->count() }})
                    </h6>
                    <a href="{{ route('staff.invoices.index', ['tenant_id' => $tenant->id]) }}" class="btn btn-sm btn-light">
                        <i class="fas fa-eye me-1"></i> Xem tất cả
                    </a>
                </div>
                <div class="card-body">
                    @if($invoices->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Số hóa đơn</th>
                                    <th>Phòng</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->invoice_no ?? 'N/A' }}</td>
                                    <td>{{ $invoice->lease->unit->property->name ?? 'N/A' }} - {{ $invoice->lease->unit->code ?? 'N/A' }}</td>
                                    <td>{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</td>
                                    <td>
                                        @switch($invoice->status)
                                            @case('draft') <span class="badge badge-secondary">Nháp</span> @break
                                            @case('issued') <span class="badge badge-warning">Đã phát hành</span> @break
                                            @case('paid') <span class="badge badge-success">Đã thanh toán</span> @break
                                            @case('overdue') <span class="badge badge-danger">Quá hạn</span> @break
                                            @case('cancelled') <span class="badge badge-dark">Đã hủy</span> @break
                                            @default <span class="badge badge-light">{{ $invoice->status }}</span> @break
                                        @endswitch
                                    </td>
                                    <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-file-invoice fa-3x text-gray-300 mb-3"></i>
                        <p class="text-muted">Chưa có hóa đơn nào</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Tickets Section -->
            <div class="card shadow-sm mb-4 tab-content" id="tab-tickets" style="display: none;">
                <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-ticket-alt me-2"></i> Ticket ({{ $tickets->count() }})
                    </h6>
                    <a href="{{ route('staff.tickets.index', ['tenant_id' => $tenant->id]) }}" class="btn btn-sm btn-light">
                        <i class="fas fa-eye me-1"></i> Xem tất cả
                    </a>
                </div>
                <div class="card-body">
                    @if($tickets->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Tiêu đề</th>
                                    <th>Phòng</th>
                                    <th>Độ ưu tiên</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $ticket)
                                <tr>
                                    <td>{{ $ticket->title }}</td>
                                    <td>{{ $ticket->lease->unit->property->name ?? 'N/A' }} - {{ $ticket->lease->unit->code ?? 'N/A' }}</td>
                                    <td>
                                        @switch($ticket->priority)
                                            @case('low') 
                                                <span style="background-color: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Thấp</span> 
                                                @break
                                            @case('medium') 
                                                <span style="background-color: #ffc107; color: #212529; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Trung bình</span> 
                                                @break
                                            @case('high') 
                                                <span style="background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Cao</span> 
                                                @break
                                            @case('urgent') 
                                                <span style="background-color: #343a40; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">Khẩn cấp</span> 
                                                @break
                                            @default 
                                                <span style="background-color: #6c757d; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">{{ $ticket->priority }}</span> 
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        @switch($ticket->status)
                                            @case('open') <span class="badge badge-primary">Mở</span> @break
                                            @case('in_progress') <span class="badge badge-warning">Đang xử lý</span> @break
                                            @case('resolved') <span class="badge badge-success">Đã giải quyết</span> @break
                                            @case('closed') <span class="badge badge-secondary">Đã đóng</span> @break
                                            @case('cancelled') <span class="badge badge-danger">Đã hủy</span> @break
                                        @endswitch
                                    </td>
                                    <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-ticket-alt fa-3x text-gray-300 mb-3"></i>
                        <p class="text-muted">Chưa có ticket nào</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tenant Information -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin khách hàng</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h5 class="mb-2">{{ $tenant->userProfile->full_name ?? $tenant->email }}</h5>
                        <p class="text-muted">{{ $tenant->phone }}</p>
                        @if($tenant->email)
                        <p class="text-muted">{{ $tenant->email }}</p>
                        @endif
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-6">
                            <h6 class="font-weight-bold">Ngày sinh:</h6>
                            <p>{{ $tenant->userProfile && $tenant->userProfile->dob ? \Carbon\Carbon::parse($tenant->userProfile->dob)->format('d/m/Y') : 'Chưa cập nhật' }}</p>
                        </div>
                        <div class="col-6">
                            <h6 class="font-weight-bold">Giới tính:</h6>
                            <p>
                                @if($tenant->userProfile && $tenant->userProfile->gender)
                                    @switch($tenant->userProfile->gender)
                                        @case('male') Nam @break
                                        @case('female') Nữ @break
                                        @case('other') Khác @break
                                    @endswitch
                                @else
                                    Chưa cập nhật
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($tenant->userProfile && $tenant->userProfile->id_number)
                    <div class="row">
                        <div class="col-12">
                            <h6 class="font-weight-bold">CCCD/CMND:</h6>
                            <p>{{ $tenant->userProfile->id_number }}</p>
                        </div>
                    </div>
                    @endif

                    @if($tenant->userProfile && $tenant->userProfile->address)
                    <div class="row">
                        <div class="col-12">
                            <h6 class="font-weight-bold">Địa chỉ:</h6>
                            <p>{{ $tenant->userProfile->address }}</p>
                        </div>
                    </div>
                    @endif

                    @if($tenant->userProfile && $tenant->userProfile->note)
                    <div class="row">
                        <div class="col-12">
                            <h6 class="font-weight-bold">Ghi chú:</h6>
                            <p>{{ $tenant->userProfile->note }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-12">
                            <h6 class="font-weight-bold">Trạng thái:</h6>
                            @if($tenant->status == 1)
                            <span class="badge badge-success">Hoạt động</span>
                            @else
                            <span class="badge badge-secondary">Không hoạt động</span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h6 class="font-weight-bold">Ngày tạo:</h6>
                            <p>{{ $tenant->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card "Thao tác" -->
            <div class="card shadow-sm mb-4">
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
                                'url' => route('staff.tenants.edit', $tenant->id),
                                'class' => 'w-100'
                            ],
                            [
                                'type' => 'button',
                                'variant' => 'danger',
                                'label' => 'Xóa',
                                'icon' => 'fas fa-trash-alt',
                                'iconPosition' => 'left',
                                'onclick' => "deleteTenant({$tenant->id}, '" . addslashes($tenant->userProfile->full_name ?? $tenant->email) . "')",
                                'class' => 'w-100'
                            ],
                            [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Quay lại',
                                'icon' => 'fas fa-arrow-left',
                                'iconPosition' => 'left',
                                'url' => route('staff.tenants.index'),
                                'class' => 'w-100'
                            ]
                        ];
                        
                        // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                        $statusActions = [];
                        
                        if($tenant->status != 1) {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'success',
                                'label' => 'Kích hoạt',
                                'icon' => 'fas fa-check-circle',
                                'onclick' => "updateTenantStatus(1)"
                            ];
                        }
                        
                        if($tenant->status == 1) {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'secondary',
                                'label' => 'Vô hiệu hóa',
                                'icon' => 'fas fa-ban',
                                'onclick' => "updateTenantStatus(0)"
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
</main>
@endsection

@push('scripts')
<script src="{{ asset('js/tab-navigation.js') }}"></script>
<script>
// Initialize tab navigation for this page
document.addEventListener('DOMContentLoaded', function() {
    TabNavigation.init('tenantTabStates', ['leases']);
});

// Update tenant status function
window.updateTenantStatus = function(newStatus) {
    const statusLabels = {
        1: 'Hoạt động',
        0: 'Vô hiệu hóa'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển sang trạng thái "${statusLabels[newStatus]}"?`,
        type: newStatus == 0 ? 'warning' : 'info',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            // Show loading
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            // Gửi request
            const formData = new FormData();
            formData.append('status', newStatus);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('{{ route("staff.tenants.update-status", $tenant->id) }}', {
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
                Notify.error('Không thể cập nhật trạng thái. Vui lòng thử lại sau.', 'Lỗi hệ thống!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    });
};

// Delete tenant function
window.deleteTenant = function(id, name) {
    Notify.confirmDelete(`khách hàng "${name}"`, () => {
        deleteTenantAction(id);
    });
};

function deleteTenantAction(id) {
    // Show preloader
    if (window.Preloader) {
        window.Preloader.show();
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
        if (window.Preloader) {
            window.Preloader.hide();
        }
        return;
    }

    fetch(`/staff/tenants/${id}`, {
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
            Notify.success(data.message || 'Đã xóa khách hàng thành công!', 'Đã xóa!');
            setTimeout(() => {
                window.location.href = '{{ route("staff.tenants.index") }}';
            }, 1500);
        } else {
            Notify.error(data.message || 'Có lỗi xảy ra khi xóa khách hàng', 'Lỗi!');
        }
    })
    .catch(error => {
        Notify.error('Không thể xóa khách hàng. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
}
</script>
@endpush

@push('styles')
<style>
/* Ensure badges have proper styling */
.badge {
    display: inline-block;
    padding: 0.25em 0.4em;
    font-size: 75%;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
}

.badge-success {
    color: #fff;
    background-color: #28a745;
}

.badge-warning {
    color: #212529;
    background-color: #ffc107;
}

.badge-danger {
    color: #fff;
    background-color: #dc3545;
}

.badge-info {
    color: #fff;
    background-color: #17a2b8;
}

.badge-primary {
    color: #fff;
    background-color: #007bff;
}

.badge-secondary {
    color: #fff;
    background-color: #6c757d;
}

.badge-dark {
    color: #fff;
    background-color: #343a40;
}

.badge-light {
    color: #212529;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}
</style>
@endpush
