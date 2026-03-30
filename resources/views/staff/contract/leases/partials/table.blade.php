@php
    $sortBy = $sortBy ?? request('sort_by', 'id');
    $sortOrder = $sortOrder ?? request('sort_order', 'desc');
    
    // Generate sort URL
    $generateSortUrl = function($field) use ($sortBy, $sortOrder) {
        $query = request()->query();
        unset($query['ajax']); // Remove ajax parameter for HTMX
        $query['sort_by'] = $field;
        $query['sort_order'] = ($sortBy === $field && $sortOrder === 'asc') ? 'desc' : 'asc';
        return request()->url() . '?' . http_build_query($query);
    };
    
    // Get sort icon
    $getSortIcon = function($field) use ($sortBy, $sortOrder) {
        if ($sortBy !== $field) {
            return '<i class="fas fa-sort ms-1 text-muted"></i>';
        }
        return $sortOrder === 'asc' 
            ? '<i class="fas fa-sort-up ms-1 text-primary"></i>' 
            : '<i class="fas fa-sort-down ms-1 text-primary"></i>';
    };
@endphp

<div class="col-12" id="leases-table-container">
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="fas fa-file-contract me-2"></i>Danh sách hợp đồng
            <span class="badge bg-primary ms-2">{{ $leases->total() }}</span>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>
                            <a href="{{ $generateSortUrl('id') }}" 
                               hx-get="{{ $generateSortUrl('id') }}"
                               hx-target="#leases-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                ID
                                {!! $getSortIcon('id') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('contract_no') }}" 
                               hx-get="{{ $generateSortUrl('contract_no') }}"
                               hx-target="#leases-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Số hợp đồng
                                {!! $getSortIcon('contract_no') !!}
                            </a>
                        </th>
                        <th>Bất động sản</th>
                        <th>Phòng</th>
                        <th>Khách thuê</th>
                        <th>Nhân viên</th>
                        <th>
                            <a href="{{ $generateSortUrl('start_date') }}" 
                               hx-get="{{ $generateSortUrl('start_date') }}"
                               hx-target="#leases-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Thời hạn
                                {!! $getSortIcon('start_date') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('rent_amount') }}" 
                               hx-get="{{ $generateSortUrl('rent_amount') }}"
                               hx-target="#leases-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Tiền thuê
                                {!! $getSortIcon('rent_amount') !!}
                            </a>
                        </th>
                        <th>Chu kỳ thanh toán</th>
                        <th>Ngày tạo hóa đơn</th>
                        <th>
                            <a href="{{ $generateSortUrl('status') }}" 
                               hx-get="{{ $generateSortUrl('status') }}"
                               hx-target="#leases-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Trạng thái
                                {!! $getSortIcon('status') !!}
                            </a>
                        </th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leases as $lease)
                        <tr>
                            <td>{{ $lease->id }}</td>
                            <td>
                                @if($lease->contract_no)
                                    <span class="badge bg-primary">{{ $lease->contract_no }}</span>
                                @else
                                    <span class="text-muted">Chưa có</span>
                                @endif
                            </td>
                            <td>
                                @if($lease->unit && $lease->unit->property)
                                    <strong>{{ $lease->unit->property->name }}</strong>
                                    @if($lease->unit->property->propertyType)
                                        <br><small class="text-muted">{{ $lease->unit->property->propertyType->name }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($lease->unit)
                                    <span class="badge bg-info">{{ $lease->unit->code ?? 'Phòng ' . $lease->unit->id }}</span>
                                    @if($lease->unit->floor)
                                        <br><small class="text-muted">Tầng {{ $lease->unit->floor }}</small>
                                    @endif
                                    @if($lease->status === 'active')
                                        <br><small class="text-success"><i class="fas fa-check-circle"></i> Đang có hợp đồng hoạt động</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($lease->tenant)
                                    <div class="d-flex flex-column">
                                        <strong>{{ $lease->tenant->full_name }}</strong>
                                        @if($lease->tenant->phone)
                                            <small class="text-muted">{{ $lease->tenant->phone }}</small>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($lease->agent)
                                    {{ $lease->agent->full_name }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    @if($lease->start_date)
                                        <strong>{{ $lease->start_date->format('d/m/Y') }}</strong>
                                    @endif
                                    @if($lease->end_date)
                                        <small class="text-muted">Đến {{ $lease->end_date->format('d/m/Y') }}</small>
                                        @if($lease->status === 'active')
                                            @php
                                                $daysUntilExpiry = floor(now()->diffInDays($lease->end_date, false));
                                                $isExpiringSoon = $daysUntilExpiry >= 0 && $daysUntilExpiry <= 30;
                                                $isExpired = $daysUntilExpiry < 0;
                                            @endphp
                                            @if($isExpired)
                                                <span class="badge bg-danger mt-1">
                                                    <i class="fas fa-exclamation-triangle"></i> Đã hết hạn ({{ abs($daysUntilExpiry) }} ngày)
                                                </span>
                                            @elseif($isExpiringSoon)
                                                <span class="badge bg-warning mt-1">
                                                    <i class="fas fa-clock"></i> Sắp hết hạn ({{ $daysUntilExpiry }} ngày)
                                                </span>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($lease->rent_amount)
                                    <strong>{{ number_format($lease->rent_amount, 0, ',', '.') }}đ</strong>
                                    @if($lease->deposit_amount)
                                        <br><small class="text-muted">Cọc: {{ number_format($lease->deposit_amount, 0, ',', '.') }}đ</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $effectiveCycle = $lease->getEffectivePaymentCycle();
                                @endphp
                                @if($effectiveCycle)
                                    @switch($effectiveCycle->cycle_type)
                                        @case('monthly')
                                            <span class="badge bg-primary">Hàng tháng</span>
                                            @break
                                        @case('quarterly')
                                            <span class="badge bg-info">Hàng quý</span>
                                            @break
                                        @case('yearly')
                                            <span class="badge bg-success">Hàng năm</span>
                                            @break
                                        @case('custom')
                                            <span class="badge bg-warning">
                                                {{ $effectiveCycle->custom_months ? $effectiveCycle->custom_months . ' tháng' : 'Tùy chỉnh' }}
                                            </span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $effectiveCycle->cycle_type }}</span>
                                    @endswitch
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $effectiveCycle = $lease->getEffectivePaymentCycle();
                                @endphp
                                @if($effectiveCycle && $effectiveCycle->billing_day)
                                    <span class="badge bg-primary">Ngày {{ $effectiveCycle->billing_day }}</span>
                                    <br><small class="text-muted">({{ $effectiveCycle->cycle_type_name ?? $effectiveCycle->name ?? 'N/A' }})</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($lease->status === 'active')
                                    <span class="badge bg-success">Đang hoạt động</span>
                                @elseif($lease->status === 'draft')
                                    <span class="badge bg-warning">Nháp</span>
                                @elseif($lease->status === 'terminated')
                                    <span class="badge bg-danger">Đã chấm dứt</span>
                                @elseif($lease->status === 'expired')
                                    <span class="badge bg-secondary">Đã hết hạn</span>
                                @else
                                    <span class="badge bg-light text-dark">{{ $lease->status }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group table-actions" role="group">
                                    <a href="{{ route('staff.leases.show', $lease->id) }}" 
                                       class="btn btn-outline-primary btn-icon-only" 
                                       title="Xem">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('staff.leases.edit', $lease->id) }}" 
                                       class="btn btn-outline-warning btn-icon-only" 
                                       title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-outline-danger btn-icon-only" 
                                            onclick="deleteLease({{ $lease->id }}, '{{ addslashes($lease->contract_no ?? 'Hợp đồng #' . $lease->id) }}')" 
                                            title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">
                                <i class="fas fa-file-contract fa-3x mb-3 text-muted"></i>
                                <br>Chưa có hợp đồng nào
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($leases->hasPages())
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-center">
                {{ $leases->appends(request()->query())->links('vendor.pagination.custom', ['tableContainerId' => 'leases-table-container']) }}
            </div>
        </div>
    @endif
</div>
</div>

