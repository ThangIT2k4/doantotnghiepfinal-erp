@php
    $sortBy = $sortBy ?? request('sort_by', 'created_at');
    $sortOrder = $sortOrder ?? request('sort_order', 'desc');
    
    $generateSortUrl = function($column) use ($sortBy, $sortOrder) {
        $query = request()->query();
        $query['sort_by'] = $column;
        $query['sort_order'] = ($sortBy === $column && $sortOrder === 'asc') ? 'desc' : 'asc';
        unset($query['ajax']);
        return request()->url() . '?' . http_build_query($query);
    };
@endphp

<div class="col-12" id="booking-deposits-table-container">
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="{{ $generateSortUrl('id') }}" 
                                   hx-get="{{ $generateSortUrl('id') }}"
                                   hx-target="#booking-deposits-table-container"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   class="text-decoration-none text-dark"
                                   style="cursor: pointer;">
                                    ID
                                    @if($sortBy == 'id')
                                        <i class="fas fa-sort-{{ $sortOrder == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                    @endif
                                </a>
                            </th>
                            <th>
                                <a href="{{ $generateSortUrl('reference_number') }}" 
                                   hx-get="{{ $generateSortUrl('reference_number') }}"
                                   hx-target="#booking-deposits-table-container"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   class="text-decoration-none text-dark"
                                   style="cursor: pointer;">
                                    Mã đặt cọc
                                    @if($sortBy == 'reference_number')
                                        <i class="fas fa-sort-{{ $sortOrder == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Bất động sản</th>
                            <th>Phòng</th>
                            <th>Khách hàng</th>
                            <th>Lịch hẹn</th>
                            <th>Nhân viên</th>
                            <th>
                                <a href="{{ $generateSortUrl('amount') }}" 
                                   hx-get="{{ $generateSortUrl('amount') }}"
                                   hx-target="#booking-deposits-table-container"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   class="text-decoration-none text-dark"
                                   style="cursor: pointer;">
                                    Số tiền
                                    @if($sortBy == 'amount')
                                        <i class="fas fa-sort-{{ $sortOrder == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Loại</th>
                            <th>
                                <a href="{{ $generateSortUrl('hold_until') }}" 
                                   hx-get="{{ $generateSortUrl('hold_until') }}"
                                   hx-target="#booking-deposits-table-container"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   class="text-decoration-none text-dark"
                                   style="cursor: pointer;">
                                    Giữ chỗ đến
                                    @if($sortBy == 'hold_until')
                                        <i class="fas fa-sort-{{ $sortOrder == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                    @endif
                                </a>
                            </th>
                            <th>
                                <a href="{{ $generateSortUrl('payment_status') }}" 
                                   hx-get="{{ $generateSortUrl('payment_status') }}"
                                   hx-target="#booking-deposits-table-container"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   class="text-decoration-none text-dark"
                                   style="cursor: pointer;">
                                    Trạng thái
                                    @if($sortBy == 'payment_status')
                                        <i class="fas fa-sort-{{ $sortOrder == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                    @else
                                        <i class="fas fa-sort ms-1 text-muted"></i>
                                    @endif
                                </a>
                            </th>
                            <th>Hạn chót thanh toán</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($bookingDeposits && $bookingDeposits->count() > 0)
                            @foreach($bookingDeposits as $deposit)
                            <tr>
                                <td>{{ $deposit->id }}</td>
                                <td>
                                    <span class="badge bg-primary">{{ $deposit->reference_number }}</span>
                                </td>
                                <td>
                                    @if($deposit->unit && $deposit->unit->property)
                                        <strong>{{ $deposit->unit->property->name }}</strong>
                                        @if($deposit->unit->property->propertyType)
                                            <br><small class="text-muted">{{ $deposit->unit->property->propertyType->name }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($deposit->unit)
                                        <span class="badge bg-info">{{ $deposit->unit->code ?? 'Phòng ' . $deposit->unit->id }}</span>
                                        @if($deposit->unit->floor)
                                            <br><small class="text-muted">Tầng {{ $deposit->unit->floor }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($deposit->tenantUser)
                                        <div class="d-flex flex-column">
                                            <strong>{{ $deposit->tenantUser->full_name }}</strong>
                                            @if($deposit->tenantUser->phone)
                                                <small class="text-muted">{{ $deposit->tenantUser->phone }}</small>
                                            @endif
                                            <small class="badge bg-success">Khách hàng</small>
                                        </div>
                                    @elseif($deposit->lead)
                                        <div class="d-flex flex-column">
                                            <strong>{{ $deposit->lead->name }}</strong>
                                            @if($deposit->lead->phone)
                                                <small class="text-muted">{{ $deposit->lead->phone }}</small>
                                            @endif
                                            <small class="badge bg-warning">Lead</small>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($deposit->viewing)
                                        <a href="{{ route('staff.viewings.show', $deposit->viewing->id) }}" 
                                           class="text-decoration-none" 
                                           title="Xem chi tiết lịch hẹn">
                                            <span class="badge bg-info">
                                                <i class="fas fa-calendar-check me-1"></i>
                                                #{{ $deposit->viewing->id }}
                                            </span>
                                        </a>
                                        @if($deposit->viewing->schedule_at)
                                            <br><small class="text-muted">
                                                {{ $deposit->viewing->schedule_at->format('d/m/Y H:i') }}
                                            </small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($deposit->agent)
                                        {{ $deposit->agent->userProfile->full_name ?? $deposit->agent->full_name ?? 'N/A' }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ number_format($deposit->amount, 0, ',', '.') }}đ</strong>
                                </td>
                                <td>
                                    @switch($deposit->deposit_type)
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
                                            <span class="badge bg-secondary">{{ $deposit->deposit_type }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    @if($deposit->hold_until)
                                        <div class="d-flex flex-column">
                                            <strong>{{ $deposit->hold_until->format('d/m/Y') }}</strong>
                                            <small class="text-muted">{{ $deposit->hold_until->format('H:i') }}</small>
                                            @if($deposit->hold_until < now())
                                                <small class="text-danger">Đã hết hạn</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @switch($deposit->payment_status)
                                            @case('pending_approval')
                                                <span class="badge bg-warning">Chờ duyệt</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning">Chờ thanh toán</span>
                                                @break
                                            @case('paid')
                                                <span class="badge bg-success">Đã thanh toán</span>
                                                @break
                                            @case('refunded')
                                                <span class="badge bg-secondary">Hoàn tiền</span>
                                                @break
                                            @case('expired')
                                                <span class="badge bg-danger">Hết hạn</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-danger">Đã hủy</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-dark">{{ $deposit->payment_status }}</span>
                                        @endswitch
                                        @if($deposit->lease && $deposit->lease->status === 'active')
                                            <span class="badge bg-info">
                                                <i class="fas fa-file-contract"></i> Đã ký hợp đồng
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($deposit->payment_due_date)
                                        <div class="d-flex flex-column">
                                            <strong>{{ $deposit->payment_due_date->format('d/m/Y H:i') }}</strong>
                                            @if($deposit->payment_status === 'pending' && $deposit->payment_due_date > now())
                                                <small class="text-info countdown-timer" data-due-date="{{ $deposit->payment_due_date->format('Y-m-d H:i:s') }}" data-deposit-id="{{ $deposit->id }}">
                                                    <i class="fas fa-hourglass-half me-1"></i>
                                                    <span class="countdown-text">Đang tính...</span>
                                                </small>
                                            @elseif($deposit->payment_due_date < now() && $deposit->payment_status === 'pending')
                                                <small class="text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Đã quá hạn
                                                </small>
                                            @elseif($deposit->payment_due_date < now())
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Đã quá hạn (đã xử lý)
                                                </small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group table-actions" role="group">
                                        <a href="{{ route('staff.booking-deposits.show', $deposit->id) }}" 
                                           class="btn btn-outline-primary btn-icon-only" 
                                           title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('staff.booking-deposits.edit', $deposit->id) }}" 
                                           class="btn btn-outline-warning btn-icon-only" 
                                           title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        @if($deposit->payment_status === 'pending_approval')
                                            <button class="btn btn-outline-success btn-icon-only" 
                                                    onclick="approveDeposit({{ $deposit->id }})" 
                                                    title="Duyệt">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        @endif
                                        
                                        @if(in_array($deposit->payment_status, ['pending_approval', 'pending', 'expired']))
                                            <button class="btn btn-outline-danger btn-icon-only" 
                                                    onclick="cancelDeposit({{ $deposit->id }})" 
                                                    title="Hủy">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif
                                        
                                        @if(in_array($deposit->payment_status, ['pending_approval', 'pending', 'paid']))
                                            <button class="btn btn-outline-secondary btn-icon-only" 
                                                    onclick="refundDeposit({{ $deposit->id }})" 
                                                    title="Hoàn tiền">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        @endif
                                        
                                        <button class="btn btn-outline-danger btn-icon-only" 
                                                onclick="deleteDeposit({{ $deposit->id }}, '{{ addslashes($deposit->reference_number) }}')" 
                                                title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">
                                    <i class="fas fa-hand-holding-usd fa-3x mb-3 text-muted"></i>
                                    <br>Chưa có đặt cọc nào
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($bookingDeposits && $bookingDeposits->hasPages())
                {{ $bookingDeposits->appends(request()->query())->links('vendor.pagination.custom', ['tableContainerId' => 'booking-deposits-table-container']) }}
            @endif
        </div>
    </div>
</div>


