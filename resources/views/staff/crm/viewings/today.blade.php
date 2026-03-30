@extends('layouts.staff_dashboard')

@section('title', 'Lịch hẹn hôm nay')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-calendar-day me-2"></i>Lịch hẹn hôm nay
                        </h1>
                        <p class="text-muted mb-0">Danh sách lịch hẹn trong ngày {{ now()->format('d/m/Y') }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('staff.viewings.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Tạo lịch hẹn mới
                        </a>
                        <a href="{{ route('staff.viewings.calendar') }}" class="btn btn-outline-info">
                            <i class="fas fa-calendar me-1"></i>Lịch
                        </a>
                        <a href="{{ route('staff.viewings.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-1"></i>Tất cả
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Viewings -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Lịch hẹn hôm nay
                            <span class="badge bg-primary ms-2">{{ $viewings->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if($viewings->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-viewings table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Thời gian</th>
                                            <th>Khách hàng</th>
                                            <th>Loại</th>
                                            <th>Bất động sản</th>
                                            <th>Phòng</th>
                                            <th>Trạng thái</th>
                                            <th>Agent</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($viewings as $viewing)
                                            <tr>
                                                <td>
                                                    <div class="time-info">
                                                        <div class="time-badge {{ $viewing->schedule_at->isPast() ? 'past' : 'upcoming' }}">
                                                            {{ $viewing->schedule_at->format('H:i') }}
                                                        </div>
                                                        @if($viewing->schedule_at->isPast())
                                                            <small class="text-muted">Đã qua</small>
                                                        @else
                                                            <small class="text-success">Sắp tới</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="customer-details">
                                                        <div class="customer-name">{{ $viewing->customer_name }}</div>
                                                        <div class="customer-meta">
                                                            @if($viewing->tenant)
                                                                {{ $viewing->tenant->email }}
                                                            @else
                                                                {{ $viewing->lead_phone }}
                                                                @if($viewing->lead_email)
                                                                    • {{ $viewing->lead_email }}
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="customer-type-badge {{ $viewing->customer_type }}">
                                                        <i class="fas {{ $viewing->getCustomerTypeIcon() }}"></i>
                                                        {{ $viewing->customer_type_text }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong>{{ $viewing->property->name }}</strong>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($viewing->unit)
                                                        <span class="badge bg-light text-dark">
                                                            {{ $viewing->unit->code }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge {{ $viewing->status_badge_class }}">
                                                        {{ $viewing->status_text }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($viewing->agent)
                                                        <div>
                                                            <strong>{{ $viewing->agent->full_name }}</strong>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('staff.viewings.show', $viewing->id) }}" 
                                                           class="btn btn-outline-primary btn-action" title="Xem chi tiết">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('staff.viewings.edit', $viewing->id) }}" 
                                                           class="btn btn-outline-warning btn-action" title="Sửa">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @if($viewing->status === 'requested')
                                                            <form method="POST" action="{{ route('staff.viewings.confirm', $viewing->id) }}" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-outline-success btn-action" title="Xác nhận">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        @if($viewing->status === 'confirmed')
                                                            <button type="button" class="btn btn-outline-primary btn-action" 
                                                                    title="Hoàn thành" data-bs-toggle="modal" 
                                                                    data-bs-target="#markDoneModal{{ $viewing->id }}">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Mark Done Modal for each viewing -->
                                            <div class="modal fade" id="markDoneModal{{ $viewing->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Đánh dấu hoàn thành</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="{{ route('staff.viewings.mark-done', $viewing->id) }}">
                                                            @csrf
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="result_note_{{ $viewing->id }}" class="form-label">Ghi chú kết quả</label>
                                                                    <textarea class="form-control" id="result_note_{{ $viewing->id }}" name="result_note" rows="3" placeholder="Ghi chú về kết quả lịch hẹn..."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                                <button type="submit" class="btn btn-primary">Hoàn thành</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Không có lịch hẹn nào hôm nay</h5>
                                <p class="text-muted">Tất cả lịch hẹn đã được hoàn thành hoặc chưa có lịch hẹn nào</p>
                                <a href="{{ route('staff.viewings.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Tạo lịch hẹn mới
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        @if($viewings->count() > 0)
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $viewings->where('status', 'requested')->count() }}</h4>
                                    <p class="mb-0">Chờ xác nhận</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $viewings->where('status', 'confirmed')->count() }}</h4>
                                    <p class="mb-0">Đã xác nhận</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-calendar-check fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $viewings->where('status', 'done')->count() }}</h4>
                                    <p class="mb-0">Hoàn thành</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0">{{ $viewings->where('status', 'no_show')->count() }}</h4>
                                    <p class="mb-0">Không đến</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</main>
@endsection

@push('styles')
<style>
.time-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.time-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-weight: 600;
    font-size: 0.875rem;
}

.time-badge.upcoming {
    background-color: #d1ecf1;
    color: #0c5460;
}

.time-badge.past {
    background-color: #f8d7da;
    color: #721c24;
}

.customer-details {
    flex: 1;
}

.customer-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.customer-meta {
    font-size: 0.875rem;
    color: #666;
}

.customer-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.customer-type-badge.lead {
    background-color: #fff3e0;
    color: #f57c00;
}

.customer-type-badge.tenant {
    background-color: #e8f5e8;
    color: #2e7d32;
}

.btn-action {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.table-viewings th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
}

.table-viewings td {
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
}

.table-viewings tbody tr:hover {
    background-color: #f8f9fa;
}
</style>
@endpush
