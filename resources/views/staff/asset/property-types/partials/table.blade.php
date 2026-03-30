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

<div class="col-12" id="property-types-table-container">
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>Danh sách loại bất động sản
            <span class="badge bg-primary ms-2">{{ $propertyTypes->total() }}</span>
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
                               hx-target="#property-types-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                ID
                                {!! $getSortIcon('id') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('key_code') }}" 
                               hx-get="{{ $generateSortUrl('key_code') }}"
                               hx-target="#property-types-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Mã Code
                                {!! $getSortIcon('key_code') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('name') }}" 
                               hx-get="{{ $generateSortUrl('name') }}"
                               hx-target="#property-types-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Tên
                                {!! $getSortIcon('name') !!}
                            </a>
                        </th>
                        <th>Icon</th>
                        <th>Phạm vi</th>
                        <th>Số BĐS</th>
                        <th>
                            <a href="{{ $generateSortUrl('status') }}" 
                               hx-get="{{ $generateSortUrl('status') }}"
                               hx-target="#property-types-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Trạng thái
                                {!! $getSortIcon('status') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('created_at') }}" 
                               hx-get="{{ $generateSortUrl('created_at') }}"
                               hx-target="#property-types-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Ngày tạo
                                {!! $getSortIcon('created_at') !!}
                            </a>
                        </th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($propertyTypes as $type)
                    <tr>
                        <td>{{ $type->id }}</td>
                        <td>
                            <code class="bg-light px-2 py-1 rounded">{{ $type->key_code }}</code>
                        </td>
                        <td>
                            <strong>{{ $type->name }}</strong>
                        </td>
                        <td>
                            @if ($type->icon)
                            <i class="{{ $type->icon }} text-primary fa-lg"></i>
                            @else
                            <i class="fas fa-building text-muted"></i>
                            @endif
                        </td>
                        <td>
                            @if($type->organization_id)
                                @if($type->organization)
                                    <span class="badge bg-primary" title="Loại BĐS riêng của tổ chức: {{ $type->organization->name }}">
                                        <i class="fas fa-building me-1"></i>
                                        {{ Str::limit($type->organization->name, 20) }}
                                    </span>
                                @else
                                    <span class="badge bg-primary" title="Loại BĐS riêng của tổ chức">
                                        <i class="fas fa-building me-1"></i>
                                        Tổ chức
                                    </span>
                                @endif
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $type->properties_count }}</span>
                        </td>
                        <td>
                            @if ($type->status == 1)
                            <span class="badge bg-success">Hoạt động</span>
                            @else
                            <span class="badge bg-warning">Tạm ngưng</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">{{ $type->created_at ? $type->created_at->format('d/m/Y H:i') : '-' }}</small>
                        </td>
                        <td>
                            <div class="btn-group table-actions" role="group">
                                <a href="{{ route('staff.property-types.show', $type->id) }}" 
                                   class="btn btn-outline-primary btn-icon-only" 
                                   title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                @if(isset($canManage) && $canManage)
                                    <a href="{{ route('staff.property-types.edit', $type->id) }}" 
                                       class="btn btn-outline-warning btn-icon-only" 
                                       title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-icon-only" 
                                            onclick="deletePropertyType({{ $type->id }}, '{{ addslashes($type->name) }}')" 
                                            title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Không có loại bất động sản nào</h5>
                            <p class="text-muted">Hãy thêm loại bất động sản đầu tiên để bắt đầu quản lý.</p>
                            <a href="{{ route('staff.property-types.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Thêm loại đầu tiên
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if ($propertyTypes->hasPages())
    <div class="card-footer bg-white">
        <div class="d-flex justify-content-center">
            {{ $propertyTypes->appends(request()->query())->links('vendor.pagination.custom', ['tableContainerId' => 'property-types-table-container']) }}
        </div>
    </div>
    @endif
</div>
</div>

