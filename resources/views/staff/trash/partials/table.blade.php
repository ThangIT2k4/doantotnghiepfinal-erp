@php
    $sortBy = $sortBy ?? request('sort_by', 'deleted_at');
    $sortOrder = $sortOrder ?? request('sort_order', 'desc');
    
    // Generate sort URL
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

<div class="col-12" id="trash-table-container">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>Danh sách dữ liệu đã xóa - {{ $tableConfig['name'] }}
                @if($records->count() > 0)
                    <span class="badge bg-secondary ms-2">{{ $records->total() }}</span>
                @endif
            </h5>
            @if($records->count() > 0)
            <div>
                <button type="button" class="btn btn-sm btn-success" onclick="restoreSelected()">
                    <i class="fas fa-undo me-1"></i>Khôi phục đã chọn
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="forceDeleteSelected()">
                    <i class="fas fa-trash me-1"></i>Xóa vĩnh viễn đã chọn
                </button>
            </div>
            @endif
        </div>
        <div class="card-body p-0">
            @if($records->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                                </th>
                                <th>
                                    <a href="{{ $generateSortUrl('id') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('id') }}"
                                       hx-target="#trash-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        ID
                                        {!! $getSortIcon('id') !!}
                                    </a>
                                </th>
                                <th>Tên/Mô tả</th>
                                <th>
                                    <a href="{{ $generateSortUrl('deleted_at') }}" 
                                       class="text-decoration-none text-dark sort-link"
                                       hx-get="{{ $generateSortUrl('deleted_at') }}"
                                       hx-target="#trash-table-container"
                                       hx-swap="innerHTML"
                                       hx-push-url="true"
                                       style="cursor: pointer;">
                                        Ngày xóa
                                        {!! $getSortIcon('deleted_at') !!}
                                    </a>
                                </th>
                                <th>Người xóa</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $record)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="record-checkbox" value="{{ $record->id }}">
                                    </td>
                                    <td>
                                        <span class="text-muted">#{{ $record->id }}</span>
                                    </td>
                                    <td>
                                        @if(isset($record->name))
                                            {{ $record->name }}
                                        @elseif(isset($record->lead_name))
                                            <div>
                                                <strong>{{ $record->lead_name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $record->lead_phone }}</small>
                                            </div>
                                        @elseif(isset($record->code))
                                            {{ $record->code }}
                                        @elseif(isset($record->email))
                                            {{ $record->email }}
                                        @else
                                            Bản ghi #{{ $record->id }}
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $record->deleted_at ? $record->deleted_at->format('d/m/Y H:i') : '-' }}</small>
                                    </td>
                                    <td>
                                        @if(isset($record->deleted_by) && $record->deletedBy)
                                            <small>{{ $record->deletedBy->full_name ?? $record->deletedBy->email }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group table-actions" role="group">
                                            <button type="button" 
                                                    class="btn btn-outline-success btn-icon-only" 
                                                    onclick="restoreRecord('{{ $table }}', {{ $record->id }})"
                                                    title="Khôi phục">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-icon-only" 
                                                    onclick="forceDeleteRecord('{{ $table }}', {{ $record->id }})"
                                                    title="Xóa vĩnh viễn">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($records->hasPages())
                    <div class="d-flex justify-content-center mt-4 mb-3">
                        {{ $records->appends(request()->query())->links('vendor.pagination.custom', [
                            'contentTypeOverride' => 'bản ghi đã xóa',
                            'contentIconOverride' => 'fas fa-trash-alt',
                            'tableContainerId' => 'trash-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Không có dữ liệu đã xóa nào</h5>
                    <p class="text-muted">Thùng rác trống</p>
                </div>
            @endif
        </div>
    </div>
</div>

