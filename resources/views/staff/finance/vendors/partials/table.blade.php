@php
    $sortBy = $sortBy ?? request('sort_by', 'id');
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

<div class="col-12">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Danh sách nhà cung cấp
                @if($vendors->count() > 0)
                    <span class="badge bg-primary ms-2">{{ $vendors->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body p-0">
            @if($vendors->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>
                                    <a href="{{ $generateSortUrl('id') }}" 
                                       class="text-decoration-none text-dark sort-link" 
                                       data-sort-field="id"
                                       hx-get="{{ $generateSortUrl('id') }}"
                                       hx-target="#vendors-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       hx-trigger="click"
                                       style="cursor: pointer;">
                                        ID
                                        {!! $getSortIcon('id') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('name') }}" 
                                       class="text-decoration-none text-dark sort-link" 
                                       data-sort-field="name"
                                       hx-get="{{ $generateSortUrl('name') }}"
                                       hx-target="#vendors-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       hx-trigger="click"
                                       style="cursor: pointer;">
                                        Tên nhà cung cấp
                                        {!! $getSortIcon('name') !!}
                                    </a>
                                </th>
                                <th>Mã số thuế</th>
                                <th>Điện thoại</th>
                                <th>Email</th>
                                <th>
                                    <a href="{{ $generateSortUrl('vendor_type') }}" 
                                       class="text-decoration-none text-dark sort-link" 
                                       data-sort-field="vendor_type"
                                       hx-get="{{ $generateSortUrl('vendor_type') }}"
                                       hx-target="#vendors-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       hx-trigger="click"
                                       style="cursor: pointer;">
                                        Loại
                                        {!! $getSortIcon('vendor_type') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('status') }}" 
                                       class="text-decoration-none text-dark sort-link" 
                                       data-sort-field="status"
                                       hx-get="{{ $generateSortUrl('status') }}"
                                       hx-target="#vendors-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       hx-trigger="click"
                                       style="cursor: pointer;">
                                        Trạng thái
                                        {!! $getSortIcon('status') !!}
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('created_at') }}" 
                                       class="text-decoration-none text-dark sort-link" 
                                       data-sort-field="created_at"
                                       hx-get="{{ $generateSortUrl('created_at') }}"
                                       hx-target="#vendors-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       hx-trigger="click"
                                       style="cursor: pointer;">
                                        Ngày tạo
                                        {!! $getSortIcon('created_at') !!}
                                    </a>
                                </th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vendors as $vendor)
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#{{ $vendor->id }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('staff.vendors.show', $vendor->id) }}" class="text-decoration-none">
                                            <strong>{{ $vendor->name }}</strong>
                                        </a>
                                    </td>
                                    <td>
                                        @if($vendor->tax_code)
                                            <code class="bg-light px-2 py-1 rounded">{{ $vendor->tax_code }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($vendor->phone)
                                            <i class="fas fa-phone text-muted me-1"></i>{{ $vendor->phone }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($vendor->email)
                                            <i class="fas fa-envelope text-muted me-1"></i>{{ $vendor->email }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $vendor->vendor_type_label }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $vendor->status_badge_class }}">{{ $vendor->status_label }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $vendor->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.vendors.show', $vendor->id) }}" 
                                               class="btn btn-outline-primary btn-icon-only" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.vendors.edit', $vendor->id) }}" 
                                               class="btn btn-outline-warning btn-icon-only" 
                                               title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-icon-only" 
                                                    title="Xóa"
                                                    onclick="deleteVendor({{ $vendor->id }}, '{{ addslashes($vendor->name) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(method_exists($vendors, 'hasPages'))
                    @if($vendors->hasPages())
                        <div class="d-flex justify-content-center mt-4 mb-3">
                            {{ $vendors->appends(request()->query())->links('vendor.pagination.custom', [
                                'contentTypeOverride' => 'nhà cung cấp',
                                'contentIconOverride' => 'fas fa-building',
                                'tableContainerId' => 'vendors-table-container',
                                'htmxIndicator' => '#htmx-loading-index-filters-form'
                            ]) }}
                        </div>
                    @endif
                @elseif(method_exists($vendors, 'total') && method_exists($vendors, 'perPage'))
                    @if($vendors->total() > $vendors->perPage())
                        <div class="d-flex justify-content-center mt-4 mb-3">
                            {{ $vendors->appends(request()->query())->links('vendor.pagination.custom', [
                                'contentTypeOverride' => 'nhà cung cấp',
                                'contentIconOverride' => 'fas fa-building',
                                'tableContainerId' => 'vendors-table-container',
                                'htmxIndicator' => '#htmx-loading-index-filters-form'
                            ]) }}
                        </div>
                    @endif
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Chưa có nhà cung cấp nào</h5>
                    <p class="text-muted mb-3">Bắt đầu tạo mục đầu tiên</p>
                    <a href="{{ route('staff.vendors.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm nhà cung cấp
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

