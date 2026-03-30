@extends('layouts.staff_dashboard')

@section('title', 'Lịch sử bảo trì - ' . $unit->code)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Lịch sử bảo trì</h1>
            <p class="mb-0">
                <strong>{{ $unit->property->name }}</strong> - Phòng: <strong>{{ $unit->code }}</strong>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.units.show', $unit->id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <a href="{{ route('staff.tickets.create') }}?unit_id={{ $unit->id }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tạo Ticket Mới
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow border-primary">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Tổng số ticket</h6>
                    <h3 class="mb-0">{{ $statistics['total_tickets'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow border-warning">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Đang xử lý</h6>
                    <h3 class="mb-0 text-warning">
                        {{ $statistics['open_tickets'] + $statistics['in_progress_tickets'] }}
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow border-success">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Tổng chi phí</h6>
                    <h3 class="mb-0 text-success">
                        {{ number_format($statistics['total_cost'], 0, ',', '.') }} VND
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow border-info">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Bảo hành</h6>
                    <h3 class="mb-0 text-info">
                        {{ $statistics['warranties']['active'] }} đang BH
                        @if($statistics['warranties']['expiring_soon'] > 0)
                            <small class="text-warning">({{ $statistics['warranties']['expiring_soon'] }} sắp hết)</small>
                        @endif
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Breakdown -->
    @if($statistics['total_cost'] > 0)
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Phân bổ chi phí</h6>
        </div>
        <div class="card-body">
            <div class="row">
                @php
                    $chargeToLabels = [
                        'tenant_deposit' => 'Trừ vào cọc',
                        'tenant_invoice' => 'Thêm vào hóa đơn',
                        'landlord' => 'Chủ trọ chịu',
                        'self_pay_vendor' => 'Tự chi trả (Vendor)',
                        'none' => 'Không hạch toán'
                    ];
                @endphp
                @foreach($statistics['cost_by_charge_to'] as $chargeTo => $amount)
                    @if($amount > 0)
                    <div class="col-md-6 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>{{ $chargeToLabels[$chargeTo] ?? $chargeTo }}</span>
                            <strong class="text-primary">{{ number_format($amount, 0, ',', '.') }} VND</strong>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Tickets List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách Tickets ({{ $tickets->count() }})</h6>
        </div>
        <div class="card-body">
            @if($tickets->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tiêu đề</th>
                                <th>Trạng thái</th>
                                <th>Độ ưu tiên</th>
                                <th>Chi phí</th>
                                <th>Người tạo</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tickets as $ticket)
                            <tr>
                                <td>#{{ $ticket->id }}</td>
                                <td>
                                    <div class="fw-bold">{{ $ticket->title }}</div>
                                    @if($ticket->description)
                                        <small class="text-muted">{{ Str::limit($ticket->description, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'open' => 'success',
                                            'in_progress' => 'warning',
                                            'resolved' => 'info',
                                            'closed' => 'secondary',
                                            'cancelled' => 'danger'
                                        ];
                                        $statusLabels = [
                                            'open' => 'Mở',
                                            'in_progress' => 'Đang xử lý',
                                            'resolved' => 'Đã giải quyết',
                                            'closed' => 'Đã đóng',
                                            'cancelled' => 'Đã hủy'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$ticket->status] }}">
                                        {{ $statusLabels[$ticket->status] }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $priorityCode = $ticket->priorityRelation?->key_code ?? 'medium';
                                        $priorityColors = [
                                            'low' => 'secondary',
                                            'medium' => 'primary',
                                            'high' => 'warning',
                                            'urgent' => 'danger'
                                        ];
                                        $priorityLabels = [
                                            'low' => 'Thấp',
                                            'medium' => 'Trung bình',
                                            'high' => 'Cao',
                                            'urgent' => 'Khẩn cấp'
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $priorityColors[$priorityCode] ?? 'secondary' }}">
                                        {{ $priorityLabels[$priorityCode] ?? ucfirst($priorityCode) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $totalCost = $ticket->logs->sum('cost_amount');
                                    @endphp
                                    @if($totalCost > 0)
                                        <strong class="text-primary">{{ number_format($totalCost, 0, ',', '.') }} VND</strong>
                                    @else
                                        <span class="text-muted">0 VND</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">
                                        {{ $ticket->createdBy->full_name ?? 'N/A' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        {{ $ticket->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('staff.tickets.show', $ticket->id) }}" 
                                       class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có ticket nào</h5>
                    <p class="text-muted">Chưa có ticket bảo trì nào được tạo cho phòng này.</p>
                    <a href="{{ route('staff.tickets.create') }}?unit_id={{ $unit->id }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tạo Ticket Đầu Tiên
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

