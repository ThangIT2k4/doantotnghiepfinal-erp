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

<div class="col-12" id="meters-table-container">
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>Danh sách công tơ đo
            <span class="badge bg-primary ms-2">{{ $meters->total() }}</span>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>
                            <a href="{{ $generateSortUrl('serial_no') }}" 
                               hx-get="{{ $generateSortUrl('serial_no') }}"
                               hx-target="#meters-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Mã công tơ
                                {!! $getSortIcon('serial_no') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('property') }}" 
                               hx-get="{{ $generateSortUrl('property') }}"
                               hx-target="#meters-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Bất động sản
                                {!! $getSortIcon('property') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('unit') }}" 
                               hx-get="{{ $generateSortUrl('unit') }}"
                               hx-target="#meters-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Phòng
                                {!! $getSortIcon('unit') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('service') }}" 
                               hx-get="{{ $generateSortUrl('service') }}"
                               hx-target="#meters-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Dịch vụ
                                {!! $getSortIcon('service') !!}
                            </a>
                        </th>
                        <th>Số liệu cuối</th>
                        <th>Ngày đo cuối</th>
                        <th>
                            <a href="{{ $generateSortUrl('status') }}" 
                               hx-get="{{ $generateSortUrl('status') }}"
                               hx-target="#meters-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Trạng thái
                                {!! $getSortIcon('status') !!}
                            </a>
                        </th>
                        <th>Trạng thái xóa</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($meters as $meter)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $meter->serial_no }}</div>
                                <small class="text-muted">ID: {{ $meter->id }}</small>
                            </td>
                            <td>
                                @if($meter->property)
                                    <div class="fw-bold">{{ $meter->property->name }}</div>
                                    <small class="text-muted">{{ $meter->property->address ?? 'Chưa có địa chỉ' }}</small>
                                @else
                                    <div class="fw-bold text-muted">N/A</div>
                                    <small class="text-muted">Property không tồn tại</small>
                                @endif
                            </td>
                            <td>
                                @if($meter->unit)
                                    <div class="fw-bold">{{ $meter->unit->code }}</div>
                                    <small class="text-muted">{{ $meter->unit->unit_type }}</small>
                                @else
                                    <div class="fw-bold text-muted">N/A</div>
                                    <small class="text-muted">Unit không tồn tại</small>
                                @endif
                            </td>
                            <td>
                                @if($meter->service)
                                    <div class="fw-bold">{{ $meter->service->name }}</div>
                                    <small class="text-muted">{{ $meter->service->key_code }}</small>
                                @else
                                    <div class="fw-bold text-muted">N/A</div>
                                    <small class="text-muted">Service không tồn tại</small>
                                @endif
                            </td>
                            <td>
                                @if($meter->readings->count() > 0)
                                    <div class="fw-bold text-primary">
                                        {{ number_format($meter->readings->first()->value, 3) }}
                                    </div>
                                    <small class="text-muted">{{ $meter->service->unit_label }}</small>
                                @else
                                    <span class="text-muted">Chưa có số liệu</span>
                                @endif
                            </td>
                            <td>
                                @if($meter->readings->count() > 0)
                                    <div>{{ $meter->readings->first()->reading_date->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $meter->readings->first()->takenBy->name ?? 'N/A' }}</small>
                                @else
                                    <span class="text-muted">Chưa có</span>
                                @endif
                            </td>
                            <td>
                                @if($meter->status)
                                    <span class="badge bg-success">Hoạt động</span>
                                @else
                                    <span class="badge bg-secondary">Ngừng hoạt động</span>
                                @endif
                            </td>
                            <td>
                                @if($meter->deleted_at)
                                    <span class="badge bg-danger">Đã xóa</span>
                                    @if($meter->deletedBy)
                                        <br><small class="text-muted">{{ $meter->deletedBy->name }}</small>
                                    @endif
                                @else
                                    <span class="badge bg-success">Bình thường</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group table-actions" role="group">
                                    <a href="{{ route('staff.meters.show', $meter->id) }}" 
                                       class="btn btn-outline-primary btn-icon-only" 
                                       title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    @if($meter->deleted_at)
                                        <!-- Actions for soft deleted meters -->
                                        <button type="button" 
                                                class="btn btn-outline-success btn-icon-only" 
                                                onclick="restoreMeter({{ $meter->id }}, '{{ addslashes($meter->serial_no) }}')" 
                                                title="Khôi phục">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-icon-only" 
                                                onclick="forceDeleteMeter({{ $meter->id }}, '{{ addslashes($meter->serial_no) }}')" 
                                                title="Xóa vĩnh viễn">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    @else
                                        <!-- Actions for active meters -->
                                        <a href="{{ route('staff.meters.edit', $meter->id) }}" 
                                           class="btn btn-outline-warning btn-icon-only" 
                                           title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('staff.meter-readings.create', ['meter_id' => $meter->id]) }}" 
                                           class="btn btn-outline-info btn-icon-only" 
                                           title="Thêm số liệu đo">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-icon-only" 
                                                onclick="deleteMeter({{ $meter->id }}, '{{ addslashes($meter->serial_no) }}')" 
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
                                <i class="fas fa-tachometer-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Chưa có công tơ đo nào</h5>
                                <p class="text-muted">Hãy thêm công tơ đo đầu tiên để bắt đầu quản lý.</p>
                                <a href="{{ route('staff.meters.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Thêm công tơ mới
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($meters->hasPages())
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-center">
                {{ $meters->appends(request()->query())->links('vendor.pagination.custom', ['tableContainerId' => 'meters-table-container']) }}
            </div>
        </div>
    @endif
</div>
</div>

