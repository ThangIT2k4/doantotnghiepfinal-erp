@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Hợp đồng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với breadcrumbs --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết Hợp đồng',
            'subtitle' => 'Thông tin chi tiết hợp đồng thuê: ' . ($lease->contract_no ?? 'Hợp đồng #' . $lease->id),
            'icon' => 'fas fa-file-contract',
            'breadcrumbs' => [
                ['label' => 'Hợp đồng', 'url' => route('staff.leases.index')],
                ['label' => $lease->contract_no ?? 'Hợp đồng #' . $lease->id, 'active' => true]
            ],
            'actions' => [
                [
                    'label' => 'Tải PDF',
                    'icon' => 'fas fa-file-pdf',
                    'url' => route('staff.leases.download', $lease->id),
                    'color' => 'danger',
                    'type' => 'link'
                ]
            ]
        ])
    
    <div class="content" id="content">
        <!-- Tabs Navigation -->
        @php
            $serviceItems = $lease->getEffectiveLeaseServiceSet()?->items ?? collect();
        @endphp
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary active" onclick="toggleLeaseTab('basic-info', this)">
                        <i class="fas fa-info-circle"></i> Thông tin cơ bản
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="toggleLeaseTab('payment-cycle', this)">
                        <i class="fas fa-calendar-alt"></i> Chu kỳ thanh toán
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="toggleLeaseTab('property-info', this)">
                        <i class="fas fa-building"></i> Bất động sản
                    </button>
                    @if ($serviceItems->count() > 0)
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleLeaseTab('services', this)">
                        <i class="fas fa-concierge-bell"></i> Dịch vụ <span class="badge bg-secondary">{{ $serviceItems->count() }}</span>
                    </button>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="toggleLeaseTab('invoices', this)">
                        <i class="fas fa-receipt"></i> Hóa đơn <span class="badge bg-secondary">{{ isset($invoices) ? $invoices->count() : 0 }}</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleLeaseTab('tickets', this)">
                        <i class="fas fa-ticket-alt"></i> Ticket <span class="badge bg-secondary">{{ isset($tickets) ? $tickets->count() : 0 }}</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleLeaseTab('ticket-deposits', this)">
                        <i class="fas fa-money-bill-wave"></i> Ticket trừ cọc <span class="badge bg-secondary">{{ isset($ticketDepositLogs) ? $ticketDepositLogs->count() : 0 }}</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-dark" onclick="toggleLeaseTab('meters', this)">
                        <i class="fas fa-tachometer-alt"></i> Chỉ số công tơ <span class="badge bg-secondary">{{ isset($meters) ? $meters->count() : 0 }}</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleLeaseTab('documents', this)">
                        <i class="fas fa-folder-open"></i> Tài liệu <span class="badge bg-secondary">{{ $lease->documents ? $lease->documents->count() : 0 }}</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleLeaseTab('residents', this)">
                        <i class="fas fa-users"></i> Người ở <span class="badge bg-secondary">{{ $lease->residents ? $lease->residents->count() : 0 }}</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="expandAllLeaseTabs()">
                        <i class="fas fa-expand"></i> Mở tất cả
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="collapseAllLeaseTabs()">
                        <i class="fas fa-compress"></i> Đóng tất cả
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Expiration Alert -->
                @if($isExpiringSoon || $isExpired)
                <div class="alert {{ $isExpired ? 'alert-danger' : 'alert-warning' }} mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas {{ $isExpired ? 'fa-exclamation-triangle' : 'fa-clock' }} me-2 fs-4"></i>
                        <div>
                            <strong>{{ $isExpired ? 'CẢNH BÁO: Hợp đồng đã hết hạn!' : 'Cảnh báo: Hợp đồng sắp hết hạn' }}</strong>
                            <div class="mt-1">
                                @if($isExpired)
                                    Hợp đồng đã hết hạn <strong>{{ abs($daysUntilExpiry) }} ngày</strong>. Vui lòng gia hạn hoặc chấm dứt hợp đồng.
                                @else
                                    Hợp đồng sẽ hết hạn trong <strong>{{ $daysUntilExpiry }} ngày</strong> ({{ $lease->end_date->format('d/m/Y') }}). Vui lòng chuẩn bị gia hạn hợp đồng.
                                @endif
                            </div>
                            @if($lease->status === 'active' && $isExpiringSoon && !$isExpired)
                            <div class="mt-2">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#renewalModal">
                                    <i class="fas fa-sync-alt"></i> Gia hạn hợp đồng
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Basic Information -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-basic-info">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Thông tin cơ bản</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Số hợp đồng</label>
                                    <div>
                                        @if ($lease->contract_no)
                                        <code class="bg-light px-2 py-1 rounded">{{ $lease->contract_no }}</code>
                                        @else
                                        <span class="text-muted">Chưa có</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small">Trạng thái</label>
                                    <div id="lease-status-badge">
                                        @include('staff.components.status-badge', [
                                            'status' => $lease->status,
                                            'type' => 'lease'
                                        ])
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Thời hạn hợp đồng</label>
                                    <div>
                                        <strong>Từ:</strong> {{ $lease->start_date ? \Carbon\Carbon::parse($lease->start_date)->format('d/m/Y') : '-' }}<br>
                                        <strong>Đến:</strong> {{ $lease->end_date ? \Carbon\Carbon::parse($lease->end_date)->format('d/m/Y') : '-' }}
                                        @if ($lease->end_date && \Carbon\Carbon::parse($lease->end_date)->isPast())
                                        <br><small class="text-danger"><strong>Đã hết hạn</strong></small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Tiền thuê/tháng</label>
                                    <div>
                                        <span class="h5 text-success">{{ number_format($lease->rent_amount) }} VND</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Tiền cọc</label>
                                    <div>
                                        <span class="h6">{{ number_format($lease->deposit_amount) }} VND</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Ngày tạo hóa đơn</label>
                                    <div>
                                        @php
                                            $effectiveCycle = $lease->getEffectivePaymentCycle();
                                            $billingDay = $effectiveCycle?->billing_day ?? null;
                                        @endphp
                                        @if($billingDay)
                                            Ngày {{ $billingDay }} hàng tháng (từ Payment Cycle: {{ $effectiveCycle->name }})
                                        @else
                                            <span class="text-muted">Chưa được cài đặt</span>
                                        @endif
                                    </div>
                                </div>

                                @if ($lease->signed_at)
                                <div class="mb-3">
                                    <label class="text-muted small">Ngày ký hợp đồng</label>
                                    <div>
                                        {{ \Carbon\Carbon::parse($lease->signed_at)->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Cycle Information -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-payment-cycle" style="display: none;">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Chu kỳ thanh toán</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                @php
                                    $effectiveCycle = $lease->getEffectivePaymentCycle();
                                @endphp
                                <div class="mb-3">
                                    <label class="text-muted small">Chu kỳ thanh toán</label>
                                    <div>
                                        @if($effectiveCycle)
                                            @switch($effectiveCycle->cycle_type)
                                                @case('monthly')
                                                    <span class="badge bg-primary fs-6">Hàng tháng</span>
                                                    @break
                                                @case('quarterly')
                                                    <span class="badge bg-info fs-6">Hàng quý</span>
                                                    @break
                                                @case('yearly')
                                                    <span class="badge bg-success fs-6">Hàng năm</span>
                                                    @break
                                                @case('custom')
                                                    <span class="badge bg-warning fs-6">
                                                        {{ $effectiveCycle->custom_months ? $effectiveCycle->custom_months . ' tháng' : 'Tùy chỉnh' }}
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary fs-6">{{ $effectiveCycle->cycle_type }}</span>
                                            @endswitch
                                        @else
                                            <span class="text-muted">Chưa thiết lập</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Hạn thanh toán</label>
                                    <div>
                                        {{-- payment_day đã được xóa, chỉ dùng invoice_payment_days từ organization --}}
                                        <span class="text-muted">Sử dụng invoice_payment_days từ organization</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Ngày tạo hóa đơn</label>
                                    <div>
                                        @php
                                            $effectiveCycle = $lease->getEffectivePaymentCycle();
                                            $billingDay = $effectiveCycle?->billing_day ?? null;
                                        @endphp
                                        @if($billingDay)
                                            <strong>Ngày {{ $billingDay }}</strong> (từ Payment Cycle: {{ $effectiveCycle->name }})
                                        @else
                                            <span class="text-muted">Chưa thiết lập</span>
                                        @endif
                                    </div>
                                </div>

                                @if($lease->lease_payment_notes)
                                <div class="mb-3">
                                    <label class="text-muted small">Ghi chú chu kỳ thanh toán</label>
                                    <div>
                                        <div class="bg-light p-3 rounded">
                                            {{ $lease->lease_payment_notes }}
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Property Information -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-property-info" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-building"></i> Thông tin bất động sản</h5>
                    </div>
                    <div class="card-body">
                        @if ($lease->unit && $lease->unit->property)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Bất động sản</label>
                                    <div>
                                        <strong>{{ $lease->unit->property->name }}</strong>
                                        @if ($lease->unit->property->propertyType)
                                        <br><small class="text-muted">{{ $lease->unit->property->propertyType->name }}</small>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Phòng</label>
                                    <div>
                                        <span class="badge bg-info">{{ $lease->unit->code ?? 'Phòng ' . $lease->unit->id }}</span>
                                        @if ($lease->unit->floor)
                                        <br><small class="text-muted">Tầng {{ $lease->unit->floor }}</small>
                                        @endif
                                        @if ($lease->unit->area_m2)
                                        <br><small class="text-muted">Diện tích: {{ $lease->unit->area_m2 }} m²</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Địa chỉ</label>
                                    <div>
                                        @if ($lease->unit->property->location)
                                        <div class="mb-1">
                                            <small class="text-primary">
                                                <i class="fas fa-map-marker-alt"></i> <strong>Cũ:</strong>
                                            </small>
                                            <br>
                                            <small>
                                                {{ $lease->unit->property->location->street }},
                                                {{ $lease->unit->property->location->ward }},
                                                {{ $lease->unit->property->location->district }},
                                                {{ $lease->unit->property->location->city }}
                                            </small>
                                        </div>
                                        @endif
                                        
                                        @if ($lease->unit->property->location2025)
                                        <div>
                                            <small class="text-success">
                                                <i class="fas fa-map-marker-alt"></i> <strong>Mới 2025:</strong>
                                            </small>
                                            <br>
                                            <small>
                                                {{ $lease->unit->property->location2025->street }},
                                                {{ $lease->unit->property->location2025->ward }},
                                                {{ $lease->unit->property->location2025->city }}
                                            </small>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="text-muted">Không có thông tin bất động sản</div>
                        @endif
                    </div>
                </div>

                <!-- Services -->
                @php
                    $effectiveServiceSet = $lease->getEffectiveLeaseServiceSet();
                    $serviceItems = $effectiveServiceSet?->items ?? collect();
                @endphp
                @if ($serviceItems->count() > 0)
                <div class="card shadow-sm mb-4 tab-content" id="tab-services" style="display: none;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-concierge-bell"></i> Dịch vụ kèm theo</h5>
                        @if($effectiveServiceSet)
                            <small class="d-block mt-1">Bộ dịch vụ: {{ $effectiveServiceSet->name }}</small>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Dịch vụ</th>
                                        <th>Giá</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($serviceItems as $item)
                                    <tr>
                                        <td>{{ $item->service->name ?? 'N/A' }}</td>
                                        <td>{{ number_format($item->price, 0, ',', '.') }} VND</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Invoices Tab -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-invoices" style="display: none;">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-receipt"></i> Hóa đơn ({{ isset($invoices) ? $invoices->count() : 0 }})</h5>
                    </div>
                    <div class="card-body">
                                <div class="d-flex justify-content-end mb-3">
                                    <a href="{{ route('staff.invoices.create', ['lease_id' => $lease->id]) }}" class="btn btn-sm btn-success" title="Tạo hóa đơn mới">
                                        <i class="fas fa-plus"></i> Tạo Hóa đơn
                                    </a>
                                </div>
                            @if(isset($invoices) && $invoices->count() > 0)
                                <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                            <tr>
                                                <th>Mã HĐ</th>
                                                <th>Ngày phát hành</th>
                                                <th>Hạn</th>
                                                <th>Tổng</th>
                                                <th>Trạng thái</th>
                                                <th>Chi tiết</th>
                                                    <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($invoices as $inv)
                                            <tr>
                                                <td>
                                                    <strong>{{ $inv->invoice_no ?? ('INV#' . $inv->id) }}</strong>
                                                    <div class="text-muted small">#{{ $inv->id }}</div>
                                                </td>
                                                <td>{{ optional($inv->issue_date)->format('d/m/Y') }}</td>
                                                <td>{{ optional($inv->due_date)->format('d/m/Y') }}</td>
                                                <td class="fw-bold text-success">{{ number_format($inv->total_amount, 0, ',', '.') }} VND</td>
                                                <td>
                                                    @switch($inv->status)
                                                        @case('draft')<span class="badge bg-secondary">Nháp</span>@break
                                                        @case('issued')<span class="badge bg-primary">Đã phát hành</span>@break
                                                        @case('paid')<span class="badge bg-success">Đã thanh toán</span>@break
                                                        @case('overdue')<span class="badge bg-danger">Quá hạn</span>@break
                                                        @case('cancelled')<span class="badge bg-warning">Đã hủy</span>@break
                                                        @default <span class="badge bg-light text-dark">{{ $inv->status }}</span>
                                                    @endswitch
                                                </td>
                                                <td>
                                                    @if($inv->items && $inv->items->count())
                                                        <ul class="mb-0 small">
                                                            @foreach($inv->items as $it)
                                                                <li>{{ $it->description }} ({{ number_format($it->amount, 0, ',', '.') }} VND)</li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-muted small">Không có dòng</span>
                                                    @endif
                                                </td>
                                                    <td>
                                                        <a href="{{ route('staff.invoices.show', $inv->id) }}" class="btn btn-sm btn-outline-primary" title="Xem chi tiết hóa đơn">
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
                                        <p class="text-muted">Chưa có hóa đơn</p>
                                    </div>
                            @endif
                    </div>
                </div>

                <!-- Tickets Tab -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-tickets" style="display: none;">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0"><i class="fas fa-ticket-alt"></i> Ticket ({{ isset($tickets) ? $tickets->count() : 0 }})</h5>
                    </div>
                    <div class="card-body">
                                <div class="d-flex justify-content-end mb-3">
                                    <a href="{{ route('staff.tickets.create', ['lease_id' => $lease->id]) }}" class="btn btn-sm btn-success" title="Tạo ticket mới">
                                        <i class="fas fa-plus"></i> Tạo Ticket
                                    </a>
                    </div>
                            @if(isset($tickets) && $tickets->count() > 0)
                                <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Tiêu đề</th>
                                                <th>Trạng thái</th>
                                                <th>Ưu tiên</th>
                                                <th>Nhật ký gần nhất</th>
                                                    <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($tickets as $tk)
                                            <tr>
                                                    <td>
                                                        <a href="{{ route('staff.tickets.show', $tk->id) }}" class="text-decoration-none">
                                                            #{{ $tk->id }}
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('staff.tickets.show', $tk->id) }}" class="text-decoration-none">
                                                            {{ $tk->title }}
                                                        </a>
                                                    </td>
                                                <td>
                                                    @switch($tk->status)
                                                        @case('open')<span class="badge bg-primary">Mở</span>@break
                                                        @case('in_progress')<span class="badge bg-warning">Đang xử lý</span>@break
                                                        @case('resolved')<span class="badge bg-success">Đã giải quyết</span>@break
                                                        @case('closed')<span class="badge bg-secondary">Đã đóng</span>@break
                                                        @case('cancelled')<span class="badge bg-danger">Đã hủy</span>@break
                                                    @endswitch
                                                </td>
                                                <td>
                                                    @switch($tk->priority)
                                                        @case('low')<span class="badge bg-secondary">Thấp</span>@break
                                                        @case('medium')<span class="badge bg-info">TB</span>@break
                                                        @case('high')<span class="badge bg-warning">Cao</span>@break
                                                        @case('urgent')<span class="badge bg-danger">Khẩn</span>@break
                                                    @endswitch
                                                </td>
                                                <td>
                                                    @if($tk->logs && $tk->logs->count())
                                                        <div class="small">
                                                            <strong>{{ $tk->logs->first()->action }}</strong>
                                                            <div class="text-muted">{{ $tk->logs->first()->created_at?->format('d/m/Y H:i') }}</div>
                                                        </div>
                                                    @else
                                                        <span class="text-muted small">-</span>
                                                    @endif
                                                </td>
                                                    <td>
                                                        <a href="{{ route('staff.tickets.show', $tk->id) }}" class="btn btn-sm btn-outline-warning">
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
                                        <p class="text-muted">Chưa có ticket liên quan</p>
                                    </div>
                            @endif
                    </div>
                </div>

                <!-- Ticket Deposits Tab -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-ticket-deposits" style="display: none;">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> Ticket trừ cọc ({{ isset($ticketDepositLogs) ? $ticketDepositLogs->count() : 0 }})</h5>
                    </div>
                    <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <strong>Tổng khoản trừ:</strong>
                                        <span class="fw-bold text-warning ms-2">{{ number_format(isset($ticketDepositLogs) ? $ticketDepositLogs->sum('cost_amount') : 0, 0, ',', '.') }} VND</span>
                    </div>
                                    <a href="{{ route('staff.tickets.create', ['lease_id' => $lease->id]) }}" class="btn btn-sm btn-success" title="Tạo ticket mới">
                                        <i class="fas fa-plus"></i> Tạo Ticket
                                    </a>
                            </div>
                            @if(isset($ticketDepositLogs) && $ticketDepositLogs->count() > 0)
                                <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                            <tr>
                                                <th>Ticket</th>
                                                <th>Hành động</th>
                                                <th>Chi phí</th>
                                                <th>Ghi chú</th>
                                                <th>Ngày</th>
                                                    <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($ticketDepositLogs as $log)
                                            <tr>
                                                    <td>
                                                        @if($log->ticket_id)
                                                            <a href="{{ route('staff.tickets.show', $log->ticket_id) }}" class="text-decoration-none">
                                                                #{{ $log->ticket_id }}
                                                            </a>
                                                        @else
                                                            #{{ $log->ticket_id }}
                                                        @endif
                                                    </td>
                                                <td>{{ $log->action }}</td>
                                                <td class="fw-bold text-warning">{{ number_format($log->cost_amount, 0, ',', '.') }} VND</td>
                                                <td>{{ $log->cost_note ?? '-' }}</td>
                                                <td>{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                                                    <td>
                                                        @if($log->ticket_id)
                                                            <a href="{{ route('staff.tickets.show', $log->ticket_id) }}" class="btn btn-sm btn-outline-warning">
                                                                <i class="fas fa-eye"></i> Xem
                                                            </a>
                                                        @endif
                                                    </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                    <div class="text-center py-4">
                                        <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Không có khoản trừ nào</p>
                                    </div>
                            @endif
                    </div>
                </div>

                <!-- Meters Tab -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-meters" style="display: none;">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-tachometer-alt"></i> Chỉ số công tơ ({{ isset($meters) ? $meters->count() : 0 }})</h5>
                    </div>
                    <div class="card-body">
                                <div class="d-flex justify-content-end mb-3">
                                    <div class="btn-group">
                                        @if($lease->unit_id)
                                            <a href="{{ route('staff.meters.create', ['unit_id' => $lease->unit_id, 'property_id' => $lease->unit->property_id ?? null]) }}" class="btn btn-sm btn-success" title="Tạo công tơ mới">
                                                <i class="fas fa-plus"></i> Tạo Công tơ
                                            </a>
                                        @else
                                            <a href="{{ route('staff.meters.create') }}" class="btn btn-sm btn-success" title="Tạo công tơ mới">
                                                <i class="fas fa-plus"></i> Tạo Công tơ
                                            </a>
                                        @endif
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

                <!-- Documents Tab -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-documents" style="display: none;">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-folder-open"></i> Tài liệu ({{ $lease->documents ? $lease->documents->count() : 0 }})</h5>
                    </div>
                    <div class="card-body">
                                <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                            <i class="fas fa-upload"></i> Tải lên tài liệu
                        </button>
                    </div>
                        @if($lease->documents && $lease->documents->count() > 0)
                            <div class="row g-3">
                                @foreach($lease->documents as $document)
                                <div class="col-md-4">
                                    <div class="card border">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <i class="fas {{ $document->getFileIcon() }} fa-2x text-primary"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-2">
                                                    <h6 class="mb-1 text-truncate" title="{{ $document->file_name }}">
                                                        {{ $document->file_name }}
                                                    </h6>
                                                    <small class="text-muted d-block">
                                                        <i class="fas fa-user"></i> {{ $document->uploader->full_name ?? 'N/A' }}
                                                    </small>
                                                    <small class="text-muted d-block">
                                                        <i class="fas fa-calendar"></i> {{ $document->created_at->format('d/m/Y H:i') }}
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="mt-2 d-flex gap-2">
                                                <a href="{{ $document->file_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Xem
                                                </a>
                                                <a href="{{ $document->file_url }}" download class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-download"></i> Tải
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument({{ $document->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                                    <div class="text-center py-4">
                                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Chưa có tài liệu nào được tải lên</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Residents Tab -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-residents" style="display: none;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-users"></i> Người ở ({{ $lease->residents ? $lease->residents->count() : 0 }})</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-end mb-3">
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addResidentModal">
                                <i class="fas fa-plus"></i> Thêm người ở
                            </button>
                        </div>
                        @if($lease->residents && $lease->residents->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Họ tên</th>
                                            <th>Số điện thoại</th>
                                            <th>CMND/CCCD</th>
                                            <th>Ghi chú</th>
                                            <th>Tài khoản</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($lease->residents as $resident)
                                        <tr>
                                            <td>
                                                <strong>{{ $resident->name }}</strong>
                                            </td>
                                            <td>{{ $resident->phone ?? '-' }}</td>
                                            <td>{{ $resident->id_number ?? '-' }}</td>
                                            <td>{{ $resident->note ?? '-' }}</td>
                                            <td>
                                                @if($resident->user_id)
                                                    <span class="badge bg-success">Đã tạo</span>
                                                    @if($resident->user)
                                                        <br><small class="text-muted">{{ $resident->user->email ?? $resident->user->phone }}</small>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">Chưa tạo</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteResident({{ $resident->id }}, '{{ addslashes($resident->name) }}')">
                                                    <i class="fas fa-trash"></i> Xóa
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có người ở nào được thêm vào hợp đồng</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">

                   <!-- Actions -->
                   <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            Thao tác
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
                                    'url' => route('staff.leases.edit', $lease->id),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteLease({$lease->id}, '" . addslashes($lease->contract_no ?? 'Hợp đồng #' . $lease->id) . "')",
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Quay lại',
                                    'icon' => 'fas fa-arrow-left',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.leases.index'),
                                    'class' => 'w-100'
                                ]
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            if($lease->status !== 'draft') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'warning',
                                    'label' => 'Chuyển về Nháp',
                                    'icon' => 'fas fa-file-alt',
                                    'onclick' => "updateLeaseStatus('draft')"
                                ];
                            }
                            
                            if($lease->status !== 'active') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Kích hoạt',
                                    'icon' => 'fas fa-check-circle',
                                    'onclick' => "updateLeaseStatus('active')"
                                ];
                            }
                            
                            if($lease->status !== 'expired') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'secondary',
                                    'label' => 'Đánh dấu Hết hạn',
                                    'icon' => 'fas fa-clock',
                                    'onclick' => "updateLeaseStatus('expired')"
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
                                <div id="lease-status-actions">
                                    @include('staff.components.action-buttons', [
                                        'layout' => 'dropdown',
                                        'size' => 'sm',
                                        'dropdownLabel' => 'Chuyển trạng thái',
                                        'actions' => $statusActions
                                    ])
                                </div>
                            @else
                                <div id="lease-status-actions"></div>
                            @endif
                            
                            {{-- Nút xem chi tiết hoàn cọc (nếu có) --}}
                            @if($lease->depositRefunds && $lease->depositRefunds->count() > 0)
                                @php
                                    $latestDepositRefund = $lease->depositRefunds->sortByDesc('created_at')->first();
                                @endphp
                                <a href="{{ route('staff.deposit-refunds.show', $latestDepositRefund->id) }}" 
                                   class="btn btn-info btn-sm w-100">
                                    <i class="fas fa-money-bill-wave me-1"></i>
                                    Xem chi tiết hoàn cọc
                                </a>
                            @endif
                            
                            {{-- Nút tạo hóa đơn (chỉ hiển thị khi hợp đồng đang active) --}}
                            @if($lease->status === 'active')
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-success btn-sm dropdown-toggle w-100" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-file-invoice"></i> Tạo hóa đơn
                                </button>
                                <ul class="dropdown-menu w-100">
                                    @if(!$hasFirstInvoice)
                                    <li>
                                        <form action="{{ route('staff.leases.create-invoice', $lease->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="invoice_type" value="first">
                                            <button type="submit" class="dropdown-item">
                                                <i class="fas fa-file-invoice-dollar"></i> Tạo hóa đơn đầu tiên
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    @if($hasFirstInvoice && isset($canCreateCycleInvoice) && $canCreateCycleInvoice)
                                    <li>
                                        <form action="{{ route('staff.leases.create-invoice', $lease->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="invoice_type" value="cycle">
                                            <button type="submit" class="dropdown-item">
                                                <i class="fas fa-calendar-alt"></i> Tạo hóa đơn cho chu kỳ
                                                @if(isset($nextUnpaidCycle))
                                                <small class="text-muted d-block">
                                                    Chu kỳ {{ $nextUnpaidCycle['cycle_number'] }}: 
                                                    {{ $nextUnpaidCycle['cycle_start']->format('d/m/Y') }} - 
                                                    {{ $nextUnpaidCycle['cycle_end']->format('d/m/Y') }}
                                                </small>
                                                @endif
                                            </button>
                                        </form>
                                    </li>
                                    @elseif($hasFirstInvoice && isset($canCreateCycleInvoice) && !$canCreateCycleInvoice)
                                    <li>
                                        <a class="dropdown-item disabled text-muted" href="#" tabindex="-1" aria-disabled="true">
                                            <i class="fas fa-calendar-alt"></i> Tạo hóa đơn cho chu kỳ
                                            <small class="d-block">Chưa đến hạn hoặc đã tạo đầy đủ</small>
                                        </a>
                                    </li>
                                    @endif
                                    <li>
                                        <form action="{{ route('staff.leases.create-invoice', $lease->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="invoice_type" value="normal">
                                            <button type="submit" class="dropdown-item">
                                                <i class="fas fa-file-alt"></i> Tạo hóa đơn thông thường
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                            @endif
                            
                            @if($lease->status === 'active' && $isExpiringSoon && !$isExpired)
                            <button class="btn btn-success btn-sm w-100" data-bs-toggle="modal" data-bs-target="#renewalModal">
                                <i class="fas fa-sync-alt me-2"></i>Gia hạn hợp đồng
                            </button>
                            @endif
                            
                            <button class="btn btn-warning btn-sm w-100" onclick="showTerminateLeaseModal()">
                                <i class="fas fa-ban me-2"></i>Chấm dứt hợp đồng
                            </button>
                        </div>
                    </div>
                </div>

                
                <!-- Tenant Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Thông tin khách thuê</h5>
                    </div>
                    <div class="card-body">
                        @if ($lease->tenant)
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $lease->tenant->full_name }}</h6>
                                @if ($lease->tenant->email)
                                <small class="text-muted">{{ $lease->tenant->email }}</small>
                                @endif
                            </div>
                        </div>
                        
                        @if ($lease->tenant->phone)
                        <div class="mb-2">
                            <small class="text-muted">Số điện thoại:</small><br>
                            <strong>{{ $lease->tenant->phone }}</strong>
                        </div>
                        @endif

                        <div class="mb-2">
                            <small class="text-muted">Trạng thái:</small><br>
                            @if ($lease->tenant->status)
                            <span class="badge bg-success">Hoạt động</span>
                            @else
                            <span class="badge bg-warning">Tạm ngưng</span>
                            @endif
                        </div>
                        @else
                        <div class="text-muted">Không có thông tin khách thuê</div>
                        @endif
                    </div>
                </div>

                <!-- Agent Information -->
                @if ($lease->agent)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Nhân viên phụ trách</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-user-tie text-white"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $lease->agent->full_name }}</h6>
                                @if ($lease->agent->email)
                                <small class="text-muted">{{ $lease->agent->email }}</small>
                                @endif
                            </div>
                        </div>
                        
                        @if ($lease->agent->phone)
                        <div class="mb-2">
                            <small class="text-muted">Số điện thoại:</small><br>
                            <strong>{{ $lease->agent->phone }}</strong>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Organization Information -->
                @if ($lease->organization)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Tổ chức</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-sm bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-building text-white"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $lease->organization->name }}</h6>
                                @if ($lease->organization->email)
                                <small class="text-muted">{{ $lease->organization->email }}</small>
                                @endif
                            </div>
                        </div>
                        
                        @if ($lease->organization->phone)
                        <div class="mb-2">
                            <small class="text-muted">Số điện thoại:</small><br>
                            <strong>{{ $lease->organization->phone }}</strong>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

             
            </div>
        </div>
    </div>
    </div>
</main>

@endsection

@push('modals')
<!-- Terminate Lease Modal -->
<div class="modal fade" id="terminateLeaseModal" tabindex="-1" aria-labelledby="terminateLeaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="terminateLeaseModalLabel">Chấm dứt hợp đồng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="terminateLeaseForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Lý do chấm dứt <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="termination_reason" rows="3" required placeholder="Nhập lý do chấm dứt hợp đồng..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ngày chấm dứt <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="termination_date" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}" required>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="refundDepositSwitch" name="refund_deposit" value="1">
                        <label class="form-check-label" for="refundDepositSwitch">
                            <strong>Hoàn cọc cho khách thuê</strong>
                        </label>
                    </div>
                    
                    <!-- Thông tin tính toán hoàn tiền -->
                    <div id="refundCalculation" style="display: none;">
                        <div class="alert alert-info">
                            <h6 class="mb-3"><i class="fas fa-calculator"></i> Tính toán hoàn tiền</h6>
                            
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <tbody>
                                        <tr>
                                            <td><strong>Tiền cọc ban đầu</strong></td>
                                            <td class="text-end">{{ number_format($depositAmount, 0, ',', '.') }} VND</td>
                                        </tr>
                                        @if($unpaidTotal > 0)
                                        <tr>
                                            <td>
                                                <strong>Trừ: Hóa đơn chưa thanh toán</strong>
                                                <small class="d-block text-muted">
                                                    @foreach($unpaidInvoices as $invoice)
                                                        - {{ $invoice->invoice_no ?? 'HD#' . $invoice->id }}: {{ number_format($invoice->remaining_amount, 0, ',', '.') }} VND<br>
                                                    @endforeach
                                                </small>
                                            </td>
                                            <td class="text-end text-danger">- {{ number_format($unpaidTotal, 0, ',', '.') }} VND</td>
                                        </tr>
                                        @endif
                                        @if($ticketDepositTotal > 0)
                                        <tr>
                                            <td>
                                                <strong>Trừ: Chi phí ticket trừ cọc</strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle"></i> 
                                                    {{ isset($ticketDepositLogs) ? $ticketDepositLogs->count() : 0 }} khoản trừ từ ticket 
                                                    - <a href="javascript:void(0)" onclick="document.getElementById('tab-selector').value='tab-ticket-deposits'; document.getElementById('tab-selector').dispatchEvent(new Event('change'));" class="text-primary">
                                                        Xem chi tiết
                                                    </a>
                                                </small>
                                            </td>
                                            <td class="text-end text-danger">- {{ number_format($ticketDepositTotal, 0, ',', '.') }} VND</td>
                                        </tr>
                                        @endif
                                        <tr class="table-{{ $refundAmount > 0 ? 'success' : ($refundAmount < 0 ? 'danger' : 'warning') }}">
                                            <td><strong>Số tiền hoàn lại</strong></td>
                                            <td class="text-end">
                                                <strong>
                                                    @if($refundAmount > 0)
                                                        {{ number_format($refundAmount, 0, ',', '.') }} VND
                                                        <span class="badge bg-success ms-2">Hoàn tiền</span>
                                                    @elseif($refundAmount < 0)
                                                        {{ number_format(abs($refundAmount), 0, ',', '.') }} VND
                                                        <span class="badge bg-danger ms-2">Còn nợ</span>
                                                    @else
                                                        0 VND
                                                        <span class="badge bg-warning ms-2">Không hoàn/bù</span>
                                                    @endif
                                                </strong>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                @if($refundAmount > 0)
                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Hệ thống sẽ tự động tạo:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Company Invoice (hoàn tiền cho khách thuê)</li>
                                            <li>Deposit Refund record ({{ number_format($refundAmount, 0, ',', '.') }} VND)</li>
                                        </ul>
                                    </div>
                                @elseif($refundAmount < 0)
                                    <div class="alert alert-danger mb-0">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Hệ thống sẽ tự động tạo:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Invoice (thu thêm {{ number_format(abs($refundAmount), 0, ',', '.') }} VND từ khách thuê)</li>
                                            <li>Hủy các hóa đơn chưa thanh toán</li>
                                        </ul>
                                    </div>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Không phát sinh hoàn/bù:</strong> Tiền cọc đã được trừ hết bởi hóa đơn chưa thanh toán và chi phí ticket.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning">Xác nhận chấm dứt</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-labelledby="uploadDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadDocumentModalLabel">Tải lên tài liệu hợp đồng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadDocumentForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Chọn tài liệu <span class="text-danger">*</span></label>
                        <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" required>
                        <small class="form-text text-muted">Cho phép: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, GIF (Tối đa 20MB)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả/Ghi chú</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Nhập mô tả cho tài liệu..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Tải lên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Resident Modal -->
<div class="modal fade" id="addResidentModal" tabindex="-1" aria-labelledby="addResidentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addResidentModalLabel">Thêm người ở</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addResidentForm">
                @csrf
                <div class="modal-body">
                    <!-- Selection Type -->
                    <div class="mb-3">
                        <label class="form-label">Chọn cách thêm <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="user_type" id="userTypeNew" value="new" checked>
                            <label class="btn btn-outline-primary" for="userTypeNew">
                                <i class="fas fa-user-plus"></i> Thêm mới
                            </label>
                            
                            <input type="radio" class="btn-check" name="user_type" id="userTypeExisting" value="existing">
                            <label class="btn btn-outline-info" for="userTypeExisting">
                                <i class="fas fa-user-check"></i> Chọn từ người dùng có sẵn
                            </label>
                        </div>
                    </div>

                    <!-- New User Form -->
                    <div id="newUserForm">
                        <div class="mb-3">
                            <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="residentName" class="form-control" required placeholder="Nhập họ tên người ở">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="residentPhone" class="form-control" required placeholder="Nhập số điện thoại">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CMND/CCCD</label>
                            <input type="text" name="id_number" id="residentIdNumber" class="form-control" placeholder="Nhập số CMND/CCCD">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="note" id="residentNote" class="form-control" rows="3" placeholder="Nhập ghi chú (nếu có)"></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="create_user_account" id="createUserAccount" value="1">
                            <label class="form-check-label" for="createUserAccount">
                                <strong>Tạo tài khoản người dùng</strong>
                            </label>
                        </div>
                        <div id="emailField" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="residentEmail" class="form-control" placeholder="Nhập email">
                                <small class="form-text text-muted">Email sẽ được sử dụng để đăng nhập. Mật khẩu mặc định: 12345678</small>
                            </div>
                        </div>
                    </div>

                    <!-- Existing User Form -->
                    <div id="existingUserForm" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Chọn người dùng <span class="text-danger">*</span></label>
                            <select name="existing_user_id" id="existingUserId" class="form-select">
                                <option value="">-- Chọn người dùng --</option>
                                @foreach($users ?? [] as $user)
                                    <option value="{{ $user->id }}" data-phone="{{ $user->phone ?? '' }}">
                                        {{ $user->full_name ?? 'N/A' }} - {{ $user->email ?? $user->phone ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Chọn người dùng từ danh sách</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="existingPhone" class="form-control" required placeholder="Nhập số điện thoại">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CMND/CCCD</label>
                            <input type="text" name="id_number" id="existingIdNumber" class="form-control" placeholder="Nhập số CMND/CCCD (nếu có)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="note" id="existingNote" class="form-control" rows="3" placeholder="Nhập ghi chú (nếu có)"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Thêm người ở
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Renewal Modal -->
<div class="modal fade" id="renewalModal" tabindex="-1" aria-labelledby="renewalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="renewalModalLabel">Gia hạn hợp đồng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="renewalForm">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Gia hạn hợp đồng sẽ tạo một hợp đồng mới với thông tin từ hợp đồng hiện tại. Hợp đồng cũ sẽ tự động chuyển sang trạng thái "expired".
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ngày bắt đầu hợp đồng mới <span class="text-danger">*</span></label>
                        <input type="date" name="new_start_date" class="form-control" value="{{ $lease->end_date ? \Carbon\Carbon::parse($lease->end_date)->addDay()->format('Y-m-d') : '' }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ngày kết thúc hợp đồng mới <span class="text-danger">*</span></label>
                        <input type="date" name="new_end_date" class="form-control" 
                               min="{{ $lease->end_date ? \Carbon\Carbon::parse($lease->end_date)->addDay()->format('Y-m-d') : '' }}" 
                               required>
                        <small class="form-text text-muted">Phải sau ngày bắt đầu hợp đồng mới</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tiền thuê/tháng mới</label>
                        <div class="input-group">
                            <input type="number" name="new_rent_amount" class="form-control" value="{{ $lease->rent_amount }}" min="0" step="1000">
                            <span class="input-group-text">VND</span>
                        </div>
                        <small class="form-text text-muted">Để trống nếu giữ nguyên tiền thuê hiện tại</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú gia hạn</label>
                        <textarea name="renewal_notes" class="form-control" rows="3" placeholder="Nhập ghi chú về việc gia hạn hợp đồng..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-sync-alt"></i> Gia hạn hợp đồng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
// Tab management for lease show page
let leaseTabStates = {};

// Load tab states from localStorage
document.addEventListener('DOMContentLoaded', function() {
    // Hiển thị warning message nếu có
    @if(session('warning'))
        Notify.warning('{{ session('warning') }}', 'Cảnh báo!');
    @endif
    
    const savedStates = localStorage.getItem('leaseTabStates');
    if (savedStates) {
        leaseTabStates = JSON.parse(savedStates);
    } else {
        // Default: only basic-info visible
        leaseTabStates = {
            'basic-info': true,
            'payment-cycle': false,
            'property-info': false,
            'services': false,
            'invoices': false,
            'tickets': false,
            'ticket-deposits': false,
            'meters': false,
            'documents': false,
            'residents': false
        };
    }
    
    // Apply saved states
    Object.keys(leaseTabStates).forEach(tabId => {
        const tab = document.getElementById(`tab-${tabId}`);
        const button = document.querySelector(`button[onclick="toggleLeaseTab('${tabId}', this)"]`);
        if (tab && button) {
            if (leaseTabStates[tabId]) {
                tab.style.display = '';
                button.classList.add('active');
            } else {
                tab.style.display = 'none';
                button.classList.remove('active');
            }
        }
    });
});

// Toggle tab visibility
function toggleLeaseTab(tabId, button) {
    const tab = document.getElementById(`tab-${tabId}`);
    if (!tab) return;
    
    leaseTabStates[tabId] = !leaseTabStates[tabId];
    
    if (leaseTabStates[tabId]) {
        tab.style.display = '';
        button.classList.add('active');
    } else {
        tab.style.display = 'none';
        button.classList.remove('active');
    }
    
    // Save to localStorage
    localStorage.setItem('leaseTabStates', JSON.stringify(leaseTabStates));
}

// Expand all tabs
function expandAllLeaseTabs() {
    Object.keys(leaseTabStates).forEach(tabId => {
        leaseTabStates[tabId] = true;
        const tab = document.getElementById(`tab-${tabId}`);
        const button = document.querySelector(`button[onclick="toggleLeaseTab('${tabId}', this)"]`);
        if (tab && button) {
            tab.style.display = '';
            button.classList.add('active');
        }
    });
    localStorage.setItem('leaseTabStates', JSON.stringify(leaseTabStates));
}

// Collapse all tabs except basic-info
function collapseAllLeaseTabs() {
    Object.keys(leaseTabStates).forEach(tabId => {
        if (tabId !== 'basic-info') { // Keep basic-info always visible
            leaseTabStates[tabId] = false;
            const tab = document.getElementById(`tab-${tabId}`);
            const button = document.querySelector(`button[onclick="toggleLeaseTab('${tabId}', this)"]`);
            if (tab && button) {
                tab.style.display = 'none';
                button.classList.remove('active');
            }
        }
    });
    localStorage.setItem('leaseTabStates', JSON.stringify(leaseTabStates));
}

function deleteLease(id, name) {
    Notify.confirmDelete(`hợp đồng "${name}"`, () => {
        // Show preloader
        if (window.Preloader) {
            window.Preloader.show();
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token not found');
            Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
            if (window.Preloader) {
                window.Preloader.hide();
            }
            return;
        }

        fetch(`/staff/leases/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
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
                Notify.success(data.message, 'Đã xóa!');
                setTimeout(() => {
                    window.location.href = '{{ route("staff.leases.index") }}';
                }, 1000);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể xóa hợp đồng: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
}

// Toggle refund calculation display
document.getElementById('refundDepositSwitch').addEventListener('change', function() {
    const refundCalculation = document.getElementById('refundCalculation');
    if (this.checked) {
        refundCalculation.style.display = 'block';
    } else {
        refundCalculation.style.display = 'none';
    }
});

// Update lease status function
function updateLeaseStatus(newStatus) {
    const statusLabels = {
        'draft': 'Nháp',
        'active': 'Hoạt động',
        'terminated': 'Chấm dứt',
        'expired': 'Hết hạn'
    };
    
    const currentStatus = '{{ $lease->status }}';
    const currentStatusLabel = statusLabels[currentStatus] || currentStatus;
    const newStatusLabel = statusLabels[newStatus] || newStatus;
    
    // Create confirmation message
    let message = `Bạn có chắc chắn muốn chuyển hợp đồng từ trạng thái "${currentStatusLabel}" sang "${newStatusLabel}"?`;
    
    // Add warning for specific transitions
    let details = '';
    if (currentStatus === 'active' && newStatus === 'draft') {
        details = '⚠️ Cảnh báo: Chuyển hợp đồng đang hoạt động về Nháp có thể ảnh hưởng đến trạng thái phòng và các hóa đơn liên quan.';
    } else if (currentStatus === 'active' && newStatus === 'expired') {
        details = '⚠️ Cảnh báo: Đánh dấu hợp đồng đang hoạt động là Hết hạn sẽ ảnh hưởng đến trạng thái phòng.';
    } else if (currentStatus === 'terminated' && newStatus === 'active') {
        details = '⚠️ Cảnh báo: Kích hoạt lại hợp đồng đã chấm dứt sẽ ảnh hưởng đến trạng thái phòng.';
    } else if (currentStatus === 'expired' && newStatus === 'active') {
        details = '⚠️ Cảnh báo: Kích hoạt lại hợp đồng đã hết hạn sẽ ảnh hưởng đến trạng thái phòng.';
    }
    
    // Use notification system
    Notify.confirm({
        title: 'Xác nhận chuyển trạng thái',
        message: message,
        details: details,
        type: details ? 'warning' : 'info',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            // Show loading
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            // Send request
            const formData = new FormData();
            formData.append('status', newStatus);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('{{ route("staff.leases.update-status", $lease->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                const data = await response.json();
                
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }
                
                // Hiển thị thông báo thành công và reload trang
                Notify.success(data.message || 'Đã cập nhật trạng thái thành công!', 'Thành công!');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
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

// Show terminate lease modal
function showTerminateLeaseModal() {
    // Show the Bootstrap modal for form input
    const modalElement = document.getElementById('terminateLeaseModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

// Terminate lease submit
const terminateLeaseForm = document.getElementById('terminateLeaseForm');
if (terminateLeaseForm) {
    terminateLeaseForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        const original = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        submitBtn.disabled = true;

        const formData = new FormData(form);

        fetch(`/staff/leases/{{ $lease->id }}/terminate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        }).then(r => r.json())
        .then(data => {
            if (data.success) {
                // Hide modal
                const modalElement = document.getElementById('terminateLeaseModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) modal.hide();
                }
                Notify.success(data.message || 'Đã chấm dứt hợp đồng thành công');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra');
                submitBtn.innerHTML = original;
                submitBtn.disabled = false;
            }
        }).catch(err => {
            console.error(err);
            Notify.error('Lỗi hệ thống.');
            submitBtn.innerHTML = original;
            submitBtn.disabled = false;
        });
    });
}

// Upload document handler
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadDocumentForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const original = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải lên...';
            submitBtn.disabled = true;

            const formData = new FormData(form);

            fetch('{{ route("staff.leases.documents.upload", $lease->id) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw { status: response.status, data: data };
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Hide modal
                    const modalElement = document.getElementById('uploadDocumentModal');
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();
                    }
                    // Reset form
                    form.reset();
                    Notify.success(data.message || 'Tải lên tài liệu thành công');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra khi tải lên tài liệu');
                    submitBtn.innerHTML = original;
                    submitBtn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                let errorMessage = 'Lỗi hệ thống khi tải lên tài liệu.';
                
                if (err.data) {
                    if (err.data.message) {
                        errorMessage = err.data.message;
                    } else if (err.data.errors) {
                        // Validation errors
                        const errors = Object.values(err.data.errors).flat();
                        errorMessage = errors.join(', ');
                    }
                }
                
                Notify.error(errorMessage);
                submitBtn.innerHTML = original;
                submitBtn.disabled = false;
            });
        });
    }
});

// Auto-calculate end date based on start date and original lease duration
(function() {
    const renewalForm = document.getElementById('renewalForm');
    if (!renewalForm) return;
    
    const newStartDateInput = renewalForm.querySelector('input[name="new_start_date"]');
    const newEndDateInput = renewalForm.querySelector('input[name="new_end_date"]');
    
    // Calculate original lease duration in months
    @if($lease->start_date && $lease->end_date)
        const originalStartDate = new Date('{{ $lease->start_date->format('Y-m-d') }}');
        const originalEndDate = new Date('{{ $lease->end_date->format('Y-m-d') }}');
        const monthsDiff = (originalEndDate.getFullYear() - originalStartDate.getFullYear()) * 12 
            + (originalEndDate.getMonth() - originalStartDate.getMonth());
        const daysDiff = Math.floor((originalEndDate - originalStartDate) / (1000 * 60 * 60 * 24));
    @else
        const monthsDiff = 12; // Default to 12 months
        const daysDiff = 365; // Default to 365 days
    @endif
    
    // Auto-calculate end date when start date changes
    if (newStartDateInput && newEndDateInput) {
        newStartDateInput.addEventListener('change', function() {
            if (this.value) {
                const startDate = new Date(this.value);
                // Calculate end date: same duration as original lease
                const endDate = new Date(startDate);
                endDate.setMonth(endDate.getMonth() + monthsDiff);
                // If days are important, add the remaining days
                if (daysDiff % 30 !== 0) {
                    endDate.setDate(endDate.getDate() + (daysDiff % 30));
                }
                
                // Format as YYYY-MM-DD
                const year = endDate.getFullYear();
                const month = String(endDate.getMonth() + 1).padStart(2, '0');
                const day = String(endDate.getDate()).padStart(2, '0');
                newEndDateInput.value = `${year}-${month}-${day}`;
                
                // Update min attribute
                const minDate = new Date(startDate);
                minDate.setDate(minDate.getDate() + 1);
                const minYear = minDate.getFullYear();
                const minMonth = String(minDate.getMonth() + 1).padStart(2, '0');
                const minDay = String(minDate.getDate()).padStart(2, '0');
                newEndDateInput.min = `${minYear}-${minMonth}-${minDay}`;
            }
        });
    }
})();

// Renewal form handler
document.getElementById('renewalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    const original = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    submitBtn.disabled = true;

    const formData = new FormData(form);

    fetch(`/staff/leases/{{ $lease->id }}/renew`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Notify.success(data.message || 'Gia hạn hợp đồng thành công');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                setTimeout(() => window.location.reload(), 1500);
            }
        } else {
            Notify.error(data.message || 'Có lỗi xảy ra khi gia hạn hợp đồng');
            submitBtn.innerHTML = original;
            submitBtn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        Notify.error('Lỗi hệ thống khi gia hạn hợp đồng.');
        submitBtn.innerHTML = original;
        submitBtn.disabled = false;
    });
});

// Delete document handler
function deleteDocument(documentId) {
    Notify.confirmDelete('tài liệu này', function() {
        fetch('{{ route("staff.leases.documents.delete", [$lease->id, ":documentId"]) }}'.replace(':documentId', documentId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Notify.success(data.message || 'Đã xóa tài liệu');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa tài liệu');
            }
        })
        .catch(err => {
            console.error(err);
            Notify.error('Lỗi hệ thống khi xóa tài liệu.');
        });
    });
}

// Auto-calculate new end date based on old contract duration
const newStartDateInput = document.querySelector('input[name="new_start_date"]');
const newEndDateInput = document.querySelector('input[name="new_end_date"]');

if (newStartDateInput && newEndDateInput) {
    newStartDateInput.addEventListener('change', function() {
        const oldStartDate = new Date('{{ $lease->start_date }}');
        const oldEndDate = new Date('{{ $lease->end_date }}');
        const durationMs = oldEndDate - oldStartDate;
        const durationDays = durationMs / (1000 * 60 * 60 * 24);
        
        if (this.value) {
            const newStart = new Date(this.value);
            const newEnd = new Date(newStart);
            newEnd.setDate(newEnd.getDate() + durationDays);
            
            // Format to YYYY-MM-DD
            const formattedEnd = newEnd.toISOString().split('T')[0];
            newEndDateInput.value = formattedEnd;
        }
    });
}

// Add Resident Form Handler
document.addEventListener('DOMContentLoaded', function() {
    const addResidentForm = document.getElementById('addResidentForm');
    const userTypeNew = document.getElementById('userTypeNew');
    const userTypeExisting = document.getElementById('userTypeExisting');
    const newUserForm = document.getElementById('newUserForm');
    const existingUserForm = document.getElementById('existingUserForm');
    const createUserAccount = document.getElementById('createUserAccount');
    const emailField = document.getElementById('emailField');
    const existingUserId = document.getElementById('existingUserId');
    const existingPhone = document.getElementById('existingPhone');

    // Auto-fill phone when selecting existing user
    if (existingUserId && existingPhone) {
        existingUserId.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const phone = selectedOption.getAttribute('data-phone') || '';
            existingPhone.value = phone;
        });
    }

    // Toggle between new and existing user forms
    if (userTypeNew && userTypeExisting) {
        userTypeNew.addEventListener('change', function() {
            if (this.checked) {
                newUserForm.style.display = 'block';
                existingUserForm.style.display = 'none';
                // Clear existing user form
                if (existingUserId) {
                    existingUserId.value = '';
                }
            }
        });

        userTypeExisting.addEventListener('change', function() {
            if (this.checked) {
                newUserForm.style.display = 'none';
                existingUserForm.style.display = 'block';
                // Clear new user form
                document.getElementById('residentName').value = '';
                document.getElementById('residentPhone').value = '';
                document.getElementById('residentEmail').value = '';
                createUserAccount.checked = false;
                emailField.style.display = 'none';
                
                // Form is shown, no initialization needed
            }
        });
    }

    // No initialization needed for simple dropdown

    // Toggle email field based on create_user_account checkbox
    if (createUserAccount && emailField) {
        createUserAccount.addEventListener('change', function() {
            if (this.checked) {
                emailField.style.display = 'block';
                document.getElementById('residentEmail').required = true;
            } else {
                emailField.style.display = 'none';
                document.getElementById('residentEmail').required = false;
                document.getElementById('residentEmail').value = '';
            }
        });
    }

    // Form submit handler
    if (addResidentForm) {
        addResidentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const original = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang thêm...';
            submitBtn.disabled = true;

            // Validate form
            const userType = form.querySelector('input[name="user_type"]:checked')?.value;
            if (userType === 'existing') {
                const selectedUserId = existingUserId.value;
                if (!selectedUserId) {
                    Notify.error('Vui lòng chọn người dùng');
                    submitBtn.innerHTML = original;
                    submitBtn.disabled = false;
                    return;
                }
            } else {
                if (!form.querySelector('#residentName').value || !form.querySelector('#residentPhone').value) {
                    Notify.error('Vui lòng điền đầy đủ thông tin bắt buộc');
                    submitBtn.innerHTML = original;
                    submitBtn.disabled = false;
                    return;
                }
                if (createUserAccount.checked && !form.querySelector('#residentEmail').value) {
                    Notify.error('Vui lòng nhập email khi tạo tài khoản');
                    submitBtn.innerHTML = original;
                    submitBtn.disabled = false;
                    return;
                }
            }

            const formData = new FormData(form);

            fetch('{{ route("staff.tenants.add-resident", $lease->id) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw { status: response.status, data: data };
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success || !data.error) {
                    // Hide modal
                    const modalElement = document.getElementById('addResidentModal');
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();
                    }
                    // Reset form
                    form.reset();
                    newUserForm.style.display = 'block';
                    existingUserForm.style.display = 'none';
                    emailField.style.display = 'none';
                    userSearchResults.style.display = 'none';
                    userTypeNew.checked = true;
                    Notify.success('Thêm người ở thành công');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    Notify.error(data.message || data.error || 'Có lỗi xảy ra khi thêm người ở');
                    submitBtn.innerHTML = original;
                    submitBtn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                let errorMessage = 'Lỗi hệ thống khi thêm người ở.';
                
                if (err.data) {
                    if (err.data.message) {
                        errorMessage = err.data.message;
                    } else if (err.data.errors) {
                        const errors = Object.values(err.data.errors).flat();
                        errorMessage = errors.join(', ');
                    } else if (err.data.error) {
                        errorMessage = err.data.error;
                    }
                }
                
                Notify.error(errorMessage);
                submitBtn.innerHTML = original;
                submitBtn.disabled = false;
            });
        });
    }
});

// Delete Resident Handler
function deleteResident(residentId, residentName) {
    Notify.confirmDelete(`người ở "${residentName}"`, function() {
        fetch(`/staff/leases/{{ $lease->id }}/residents/${residentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Notify.success(data.message || 'Đã xóa người ở');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa người ở');
            }
        })
        .catch(err => {
            console.error(err);
            Notify.error('Lỗi hệ thống khi xóa người ở.');
        });
    });
}
</script>
@endpush
