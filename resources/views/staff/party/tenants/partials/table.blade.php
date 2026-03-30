@php
    $sortBy = $sortBy ?? request('sort_by', 'id');
    $sortOrder = $sortOrder ?? request('sort_order', 'desc');
    
    // Generate sort URL
    $generateSortUrl = function($field) use ($sortBy, $sortOrder) {
        $query = request()->query();
        // Remove ajax parameter for HTMX requests
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

<div class="col-12" id="tenants-table-container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Danh sách khách hàng
                @if($tenants->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $tenants->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($tenants->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <a href="{{ $generateSortUrl('id') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('id') }}"
                                       hx-target="#tenants-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        ID
                                        {!! $getSortIcon('id') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('full_name') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('full_name') }}"
                                       hx-target="#tenants-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Thông tin
                                        {!! $getSortIcon('full_name') !!}
                                    </a>
                                </th>
                                <th>Liên hệ</th>
                                <th>Ngày sinh</th>
                                <th>Giới tính</th>
                                <th>Hợp đồng</th>
                                <th>Trạng thái</th>
                                <th>
                                    <a href="{{ $generateSortUrl('created_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('created_at') }}"
                                       hx-target="#tenants-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày tạo
                                        {!! $getSortIcon('created_at') !!}
                                    </a>
                                </th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tenants as $tenant)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#{{ $tenant->id }}</span>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-0">{{ $tenant->userProfile->full_name ?? 'Chưa cập nhật' }}</h6>
                                            <small class="text-muted">ID: {{ $tenant->id }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-phone text-muted me-1"></i>
                                            {{ $tenant->phone ?? 'Chưa cập nhật' }}
                                        </div>
                                        <div>
                                            <i class="fas fa-envelope text-muted me-1"></i>
                                            {{ $tenant->email ?? 'Chưa cập nhật' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($tenant->userProfile && $tenant->userProfile->dob)
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($tenant->userProfile->dob)->format('d/m/Y') }}
                                            </small>
                                        @else
                                            <span class="text-muted">Chưa cập nhật</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($tenant->userProfile && $tenant->userProfile->gender)
                                            @switch($tenant->userProfile->gender)
                                                @case('male')
                                                    <span class="badge bg-info">Nam</span>
                                                    @break
                                                @case('female')
                                                    <span class="badge bg-danger">Nữ</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">Khác</span>
                                            @endswitch
                                        @else
                                            <span class="text-muted">Chưa cập nhật</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $activeLeases = \App\Models\Lease::where('tenant_id', $tenant->id)
                                                ->where('status', 'active')
                                                ->where('organization_id', auth()->user()->organizations()->first()->id ?? null)
                                                ->count();
                                        @endphp
                                        @if($activeLeases > 0)
                                            <span class="badge bg-success">{{ $activeLeases }} hợp đồng</span>
                                        @else
                                            <span class="badge bg-light text-dark">Chưa có</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($tenant->deleted_at)
                                            <span class="badge bg-danger">Đã xóa</span>
                                        @else
                                            <span class="badge bg-success">Hoạt động</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $tenant->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.tenants.show', $tenant->id) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.tenants.edit', $tenant->id) }}" 
                                               class="btn btn-outline-warning btn-icon-only" 
                                               title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-icon-only" 
                                                    title="Xóa"
                                                    onclick="deleteTenant({{ $tenant->id }}, '{{ addslashes($tenant->userProfile->full_name ?? $tenant->email) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(method_exists($tenants, 'hasPages'))
                    @if($tenants->hasPages())
                        <div class="d-flex justify-content-center mt-4 mb-3">
                            {{ $tenants->appends(request()->query())->links('vendor.pagination.custom', [
                                'contentTypeOverride' => 'khách hàng',
                                'contentIconOverride' => 'fas fa-users',
                                'tableContainerId' => 'tenants-table-container'
                            ]) }}
                        </div>
                    @endif
                @elseif(method_exists($tenants, 'total') && method_exists($tenants, 'perPage'))
                    @if($tenants->total() > $tenants->perPage())
                        <div class="d-flex justify-content-center mt-4 mb-3">
                            {{ $tenants->appends(request()->query())->links('vendor.pagination.custom', [
                                'contentTypeOverride' => 'khách hàng',
                                'contentIconOverride' => 'fas fa-users',
                                'tableContainerId' => 'tenants-table-container'
                            ]) }}
                        </div>
                    @endif
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có khách hàng nào</h5>
                    <p class="text-muted mb-3">Bắt đầu tạo mục đầu tiên</p>
                    <a href="{{ route('staff.tenants.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm khách hàng
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

