@php
    $sortBy = $sortBy ?? request('sort_by', 'created_at');
    $sortOrder = $sortOrder ?? request('sort_order', 'desc');
    
    // Generate sort URL for HTMX
    $generateSortUrl = function($field) use ($sortBy, $sortOrder) {
        $query = request()->query();
        unset($query['ajax']);
        $query['sort_by'] = $field;
        $query['sort_order'] = ($sortBy === $field && $sortOrder === 'asc') ? 'desc' : 'asc';
        return request()->url() . '?' . http_build_query($query);
    };
    
    // Get sort icon
    $getSortIcon = function($field) use ($sortBy, $sortOrder) {
        if ($sortBy !== $field) {
            return '<i class="fas fa-sort text-muted"></i>';
        }
        return $sortOrder === 'asc' 
            ? '<i class="fas fa-sort-up text-primary"></i>' 
            : '<i class="fas fa-sort-down text-primary"></i>';
    };
@endphp

<div class="col-12" id="staff-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-users me-2"></i>Danh sách Nhân viên
                @if($staff && $staff->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $staff->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($staff && $staff->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">
                                    <a href="{{ $generateSortUrl('id') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('id') }}"
                                       hx-target="#staff-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        # {!! $getSortIcon('id') !!}
                                    </a>
                                </th>
                                <th width="18%">
                                    <a href="{{ $generateSortUrl('full_name') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('full_name') }}"
                                       hx-target="#staff-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Nhân viên {!! $getSortIcon('full_name') !!}
                                    </a>
                                </th>
                                <th width="13%">Vai trò</th>
                                <th width="8%">BĐS quản lý</th>
                                <th width="12%">Workload</th>
                                <th width="12%">Hiệu suất (30d)</th>
                                <th width="8%">
                                    <a href="{{ $generateSortUrl('status') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('status') }}"
                                       hx-target="#staff-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Trạng thái {!! $getSortIcon('status') !!}
                                    </a>
                                </th>
                                <th width="10%">
                                    <a href="{{ $generateSortUrl('created_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('created_at') }}"
                                       hx-target="#staff-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày tạo {!! $getSortIcon('created_at') !!}
                                    </a>
                                </th>
                                <th width="10%">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($staff as $member)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#{{ $member->id }}</span>
                                    </td>
                <td>
                    <div>
                        <strong>{{ $member->full_name ?? 'N/A' }}</strong>
                        <br>
                        <small class="text-muted">{{ $member->email }}</small>
                        @if($member->phone)
                        <br>
                        <small class="text-muted"><i class="fas fa-phone fa-xs"></i> {{ $member->phone }}</small>
                        @endif
                    </div>
                </td>
                <td>
                    @foreach($member->organizationRoles as $role)
                    <span class="badge bg-info">{{ $role->name }}</span>
                    @endforeach
                </td>
                <td>
                    <span class="badge bg-primary">{{ $member->assignedProperties->count() }} BĐS</span>
                </td>
                <td>
                    @if(isset($member->workload))
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fas fa-user-tag text-primary"></i> Leads:</span>
                            <strong class="text-primary">{{ $member->workload['active_leads'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fas fa-eye text-info"></i> Viewings:</span>
                            <strong class="text-info">{{ $member->workload['active_viewings'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span><i class="fas fa-calendar-check text-warning"></i> Bookings:</span>
                            <strong class="text-warning">{{ $member->workload['pending_bookings'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="fas fa-file-contract text-success"></i> Leases:</span>
                            <strong class="text-success">{{ $member->workload['active_leases'] }}</strong>
                        </div>
                    </div>
                    @else
                    <span class="text-muted small">Chưa có dữ liệu</span>
                    @endif
                </td>
                <td>
                    @if(isset($member->performance))
                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Leads:</span>
                            <strong>{{ $member->performance['leads_count'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Leases:</span>
                            <strong>{{ $member->performance['leases_count'] }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Conversion:</span>
                            <strong class="text-{{ $member->performance['conversion_rate'] >= 15 ? 'success' : ($member->performance['conversion_rate'] >= 10 ? 'warning' : 'danger') }}">{{ $member->performance['conversion_rate'] }}%</strong>
                        </div>
                    </div>
                    @else
                    <span class="text-muted small">Chưa có dữ liệu</span>
                    @endif
                </td>
                <td>
                    @if($member->status)
                    <span class="badge bg-success">Hoạt động</span>
                    @else
                    <span class="badge bg-secondary">Tạm ngưng</span>
                    @endif
                </td>
                <td>
                    <small>{{ $member->created_at->format('d/m/Y') }}</small>
                </td>
                <td>
                    <div class="btn-group table-actions" role="group">
                        <a href="{{ route('staff.staff.show', $member->id) }}" 
                           class="btn btn-outline-primary btn-icon-only" 
                           title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('staff.staff.edit', $member->id) }}" 
                           class="btn btn-outline-warning btn-icon-only" 
                           title="Sửa">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" 
                                class="btn btn-outline-{{ $member->status ? 'warning' : 'success' }} btn-icon-only" 
                                onclick="toggleStaffStatus({{ $member->id }}, '{{ addslashes($member->full_name ?? $member->email) }}', {{ $member->status ? 'true' : 'false' }})" 
                                title="{{ $member->status ? 'Tạm ngưng' : 'Kích hoạt' }}">
                            <i class="fas fa-{{ $member->status ? 'pause' : 'play' }}"></i>
                        </button>
                        <button type="button" 
                                class="btn btn-outline-danger btn-icon-only" 
                                onclick="deleteStaff({{ $member->id }}, '{{ addslashes($member->full_name ?? $member->email) }}')" 
                                title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($staff->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {!! $staff->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'staff-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) !!}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có nhân viên nào</h5>
                    <p class="text-muted mb-3">Bắt đầu thêm nhân viên đầu tiên</p>
                    <a href="{{ route('staff.staff.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm nhân viên đầu tiên
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

