@php
    $sortBy = $sortBy ?? request('sort_by', 'created_at');
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

<div class="col-12" id="master-leases-table-container">
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="fas fa-file-contract me-2"></i>Danh sách hợp đồng thuê lại
            <span class="badge bg-primary ms-2">{{ $leases->total() }}</span>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>
                            <a href="{{ $generateSortUrl('contract_no') }}" 
                               hx-get="{{ $generateSortUrl('contract_no') }}"
                               hx-target="#master-leases-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Số hợp đồng
                                {!! $getSortIcon('contract_no') !!}
                            </a>
                        </th>
                        <th>Bất động sản</th>
                        <th>Chủ nhà</th>
                        <th>
                            <a href="{{ $generateSortUrl('start_date') }}" 
                               hx-get="{{ $generateSortUrl('start_date') }}"
                               hx-target="#master-leases-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Thời gian
                                {!! $getSortIcon('start_date') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('base_rent') }}" 
                               hx-get="{{ $generateSortUrl('base_rent') }}"
                               hx-target="#master-leases-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Tiền thuê
                                {!! $getSortIcon('base_rent') !!}
                            </a>
                        </th>
                        <th>Phòng</th>
                        <th>
                            <a href="{{ $generateSortUrl('status') }}" 
                               hx-get="{{ $generateSortUrl('status') }}"
                               hx-target="#master-leases-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Trạng thái
                                {!! $getSortIcon('status') !!}
                            </a>
                        </th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leases as $lease)
                        <tr>
                            <td>
                                <strong>{{ $lease->contract_no }}</strong>
                                @if($lease->is_active)
                                    <span class="badge badge-success">Hoạt động</span>
                                @elseif($lease->is_expired)
                                    <span class="badge badge-danger">Hết hạn</span>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <strong>{{ $lease->property->name }}</strong>
                                    @if($lease->property->location)
                                        <br><small class="text-muted">{{ $lease->property->location->address }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($lease->landlord)
                                    <div>
                                        <strong>{{ $lease->landlord->full_name }}</strong>
                                        @if($lease->landlord->phone)
                                            <br><small class="text-muted">{{ $lease->landlord->phone }}</small>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">Chưa có</span>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <strong>Từ:</strong> {{ $lease->start_date->format('d/m/Y') }}<br>
                                    <strong>Đến:</strong> {{ $lease->end_date->format('d/m/Y') }}
                                    @if($lease->days_until_expiry <= 30 && $lease->status == 'active')
                                        <br><small class="text-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Còn {{ $lease->days_until_expiry }} ngày
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <strong>{{ $lease->formatted_base_rent }}</strong>
                                <br><small class="text-muted">{{ $lease->billing_cycle_label }}</small>
                                @if($lease->revenue_share_pct)
                                    <br><small class="text-info">
                                        Chia sẻ: {{ $lease->revenue_share_pct }}%
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $lease->units->count() }} phòng</span>
                                @if($lease->units->count() > 0)
                                    <br><small class="text-muted">
                                        {{ $lease->units->pluck('code')->implode(', ') }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                @include('staff.components.status-badge', [
                                    'status' => $lease->status,
                                    'type' => 'master_lease'
                                ])
                            </td>
                            <td>
                                <div class="btn-group table-actions" role="group">
                                    <a href="{{ route('staff.master-leases.show', $lease) }}" 
                                       class="btn btn-outline-primary btn-icon-only" 
                                       title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('staff.master-leases.edit', $lease) }}" 
                                       class="btn btn-outline-warning btn-icon-only" 
                                       title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-outline-danger btn-icon-only" 
                                            onclick="deleteMasterLease({{ $lease->id }}, '{{ addslashes($lease->contract_no ?? 'Hợp đồng #' . $lease->id) }}')" 
                                            title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-file-contract fa-3x mb-3 text-muted"></i>
                                <br>Chưa có hợp đồng thuê lại nào
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
                {{ $leases->appends(request()->query())->links('vendor.pagination.custom', ['tableContainerId' => 'master-leases-table-container']) }}
            </div>
        </div>
    @endif
</div>
</div>

