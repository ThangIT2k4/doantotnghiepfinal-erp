@php
    $sortBy = $sortBy ?? request('sort_by', 'code');
    $sortOrder = $sortOrder ?? request('sort_order', 'asc');
    
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
            ? '<i class="fas fa-sort-up ms-1"></i>' 
            : '<i class="fas fa-sort-down ms-1"></i>';
    };
@endphp

<div class="col-12" id="units-table-container">
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>Danh sách phòng
            <span class="badge bg-primary ms-2">{{ $units->total() }}</span>
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
                               hx-target="#units-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                ID
                                {!! $getSortIcon('id') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('code') }}" 
                               hx-get="{{ $generateSortUrl('code') }}"
                               hx-target="#units-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Mã phòng
                                {!! $getSortIcon('code') !!}
                            </a>
                        </th>
                        <th>Bất động sản</th>
                        <th>Thông tin</th>
                        <th>Giá thuê</th>
                        <th>
                            <a href="{{ $generateSortUrl('status') }}" 
                               hx-get="{{ $generateSortUrl('status') }}"
                               hx-target="#units-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Trạng thái
                                {!! $getSortIcon('status') !!}
                            </a>
                        </th>
                        <th>Hợp đồng</th>
                        <th>Doanh thu</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $unit)
                    <tr>
                        <td>{{ $unit->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <i class="fas fa-door-open text-primary"></i>
                                </div>
                                <div>
                                    <strong>{{ $unit->code }}</strong>
                                    <br><small class="text-muted">Tầng {{ $unit->floor }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <strong>{{ $unit->property->name }}</strong>
                                <br><small class="text-muted">{{ $unit->property->address ?? 'Chưa có địa chỉ' }}</small>
                            </div>
                        </td>
                        <td>
                            <div>
                                <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $unit->unit_type)) }}</span>
                                <br><small class="text-muted">{{ $unit->area_m2 ? number_format($unit->area_m2, 2) . ' m²' : 'N/A' }} - {{ $unit->max_occupancy }} người</small>
                            </div>
                        </td>
                        <td>
                            <div>
                                <strong>{{ $unit->base_rent ? number_format($unit->base_rent, 0, ',', '.') . ' đ/tháng' : 'Chưa xác định' }}</strong>
                                @if($unit->deposit_amount)
                                    <br><small class="text-muted">Cọc: {{ number_format($unit->deposit_amount) }}đ</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($unit->currentLease)
                                <span class="badge bg-primary">Đã thuê</span>
                            @else
                                @switch($unit->status)
                                    @case('available')
                                        <span class="badge bg-success">Có sẵn</span>
                                        @break
                                    @case('reserved')
                                        <span class="badge bg-info">Đã đặt</span>
                                        @break
                                    @case('occupied')
                                        <span class="badge bg-primary">Đã thuê</span>
                                        @break
                                    @case('maintenance')
                                        <span class="badge bg-warning">Bảo trì</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ $unit->status }}</span>
                                @endswitch
                            @endif
                        </td>
                        <td>
                            @if($unit->currentLease)
                                <div>
                                    <span class="badge bg-success">Đang thuê</span>
                                    <br><small class="text-muted">{{ $unit->currentLease->tenant->full_name ?? 'N/A' }}</small>
                                </div>
                            @else
                                <span class="badge bg-light text-dark">Chưa có</span>
                            @endif
                        </td>
                        <td>
                            <div>
                                <strong>{{ number_format($unit->total_revenue ?? 0) }}đ</strong>
                                @if($unit->outstanding_amount > 0)
                                    <br><small class="text-danger">Còn nợ: {{ number_format($unit->outstanding_amount) }}đ</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="btn-group table-actions" role="group">
                                <a href="{{ route('staff.units.show', $unit->id) }}" 
                                   class="btn btn-outline-primary btn-icon-only" 
                                   title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('staff.units.edit', $unit->id) }}" 
                                   class="btn btn-outline-warning btn-icon-only" 
                                   title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if (!$unit->currentLease && $unit->status == 'available')
                                <a href="{{ route('staff.booking-deposits.create', ['property_id' => $unit->property_id, 'unit_id' => $unit->id]) }}" 
                                   class="btn btn-outline-info btn-icon-only" 
                                   title="Tạo đặt cọc">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </a>
                                <a href="{{ route('staff.leases.create', ['property_id' => $unit->property_id, 'unit_id' => $unit->id]) }}" 
                                   class="btn btn-outline-success btn-icon-only" 
                                   title="Tạo hợp đồng">
                                    <i class="fas fa-file-contract"></i>
                                </a>
                                @endif
                                <button class="btn btn-outline-danger btn-icon-only" 
                                        onclick="deleteUnit({{ $unit->id }}, '{{ addslashes($unit->code) }}')" 
                                        title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-building fa-3x mb-3 text-muted"></i>
                            <br>Chưa có phòng nào
                            <br><a href="{{ route('staff.units.create') }}" class="btn btn-primary mt-2">
                                <i class="fas fa-plus"></i> Thêm phòng
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    @if($units->hasPages())
        <div class="card-footer bg-white">
            {{ $units->appends(request()->query())->links('vendor.pagination.custom', ['tableContainerId' => 'units-table-container']) }}
        </div>
    @endif
</div>
</div>

