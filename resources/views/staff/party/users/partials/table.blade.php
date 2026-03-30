@php
    $sortBy = $sortBy ?? request('sort_by', 'created_at');
    $sortOrder = $sortOrder ?? request('sort_order', 'desc');
    
    // Generate sort URL
    $generateSortUrl = function($field) use ($sortBy, $sortOrder) {
        $query = request()->query();
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

{{-- Only render table content, no card structure (card is already in index-table component) --}}
@if($users->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>
                        <a href="{{ $generateSortUrl('id') }}" class="text-decoration-none text-dark">
                            ID
                            {!! $getSortIcon('id') !!}
                        </a>
                    </th>
                    <th>
                        <a href="{{ $generateSortUrl('full_name') }}" class="text-decoration-none text-dark">
                            Thông tin
                            {!! $getSortIcon('full_name') !!}
                        </a>
                    </th>
                    <th>Vai trò</th>
                    <th>
                        <a href="{{ $generateSortUrl('status') }}" class="text-decoration-none text-dark">
                            Trạng thái
                            {!! $getSortIcon('status') !!}
                        </a>
                    </th>
                    <th>
                        <a href="{{ $generateSortUrl('created_at') }}" class="text-decoration-none text-dark">
                            Ngày tạo
                            {!! $getSortIcon('created_at') !!}
                        </a>
                    </th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>
                            <span class="badge bg-secondary">#{{ $user->id }}</span>
                        </td>
                        <td>
                            <div>
                                <h6 class="mb-0">{{ $user->userProfile->full_name ?? $user->email }}</h6>
                                <small class="text-muted">{{ $user->email }}</small>
                                @if($user->phone)
                                    <br><small class="text-muted">{{ $user->phone }}</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($user->userRoles->count() > 0)
                                @foreach($user->userRoles as $role)
                                    <span class="badge bg-info">{{ $role->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">Chưa có vai trò</span>
                            @endif
                        </td>
                        <td>
                            @if($user->status)
                                <span class="badge bg-success">Hoạt động</span>
                            @else
                                <span class="badge bg-warning">Tạm ngưng</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ $user->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td>
                            <div class="btn-group table-actions" role="group">
                                <a href="{{ route('staff.users.show', $user->id) }}" 
                                   class="btn btn-outline-primary btn-icon-only" 
                                   title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('staff.users.edit', $user->id) }}" 
                                   class="btn btn-outline-warning btn-icon-only" 
                                   title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if($user->id !== auth()->id())
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-icon-only" 
                                            title="Xóa"
                                            onclick="deleteUser({{ $user->id }}, '{{ addslashes($user->userProfile->full_name ?? $user->email) }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    @if($users->hasPages())
        <div class="d-flex justify-content-center mt-4 mb-3">
            {{ $users->appends(request()->query())->links('vendor.pagination.custom', [
                'tableContainerId' => 'users-table-container',
                'htmxIndicator' => '#htmx-loading-index-filters-form'
            ]) }}
        </div>
    @endif
@else
    <div class="text-center py-5">
        <i class="fas fa-users fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Chưa có người dùng nào</h5>
        <p class="text-muted mb-3">Bắt đầu tạo mục đầu tiên</p>
        <a href="{{ route('staff.users.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Thêm người dùng mới
        </a>
    </div>
@endif
