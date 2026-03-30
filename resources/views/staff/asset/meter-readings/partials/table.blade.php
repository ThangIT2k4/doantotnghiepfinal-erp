@php
    $sortBy = $sortBy ?? request('sort_by', 'reading_date');
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

<div class="col-12" id="meter-readings-table-container">
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>Danh sách số liệu đo
            <span class="badge bg-primary ms-2">{{ $readings->total() }}</span>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>
                            <a href="{{ $generateSortUrl('reading_date') }}" 
                               hx-get="{{ $generateSortUrl('reading_date') }}"
                               hx-target="#meter-readings-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Ngày đo
                                {!! $getSortIcon('reading_date') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('meter') }}" 
                               hx-get="{{ $generateSortUrl('meter') }}"
                               hx-target="#meter-readings-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Công tơ
                                {!! $getSortIcon('meter') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('property') }}" 
                               hx-get="{{ $generateSortUrl('property') }}"
                               hx-target="#meter-readings-table-container"
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
                               hx-target="#meter-readings-table-container"
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
                               hx-target="#meter-readings-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Dịch vụ
                                {!! $getSortIcon('service') !!}
                            </a>
                        </th>
                        <th>
                            <a href="{{ $generateSortUrl('value') }}" 
                               hx-get="{{ $generateSortUrl('value') }}"
                               hx-target="#meter-readings-table-container"
                               hx-swap="innerHTML"
                               hx-push-url="true"
                               class="text-decoration-none text-dark"
                               style="cursor: pointer;">
                                Giá trị
                                {!! $getSortIcon('value') !!}
                            </a>
                        </th>
                        <th>Người đo</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($readings as $reading)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $reading->reading_date->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $reading->reading_date->format('H:i') }}</small>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $reading->meter->serial_no }}</div>
                                <small class="text-muted">ID: {{ $reading->meter->id }}</small>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $reading->meter->property?->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $reading->meter->property?->address ?? 'Chưa có địa chỉ' }}</small>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $reading->meter->unit?->code ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $reading->meter->unit?->unit_type ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $reading->meter->service?->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $reading->meter->service?->key_code ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <div class="fw-bold text-primary">
                                    {{ number_format($reading->value, 3) }}
                                </div>
                                <small class="text-muted">{{ $reading->meter->service?->unit_label ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $reading->takenBy?->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $reading->takenBy?->email ?? '' }}</small>
                            </td>
                            <td>
                                @if($reading->note)
                                    <span class="text-truncate d-inline-block" style="max-width: 100px;" title="{{ $reading->note }}">
                                        {{ $reading->note }}
                                    </span>
                                @else
                                    <span class="text-muted">Không có</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group table-actions" role="group">
                                    <a href="{{ route('staff.meter-readings.show', $reading->id) }}" 
                                       class="btn btn-outline-primary btn-icon-only" 
                                       title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('staff.meter-readings.edit', $reading->id) }}" 
                                       class="btn btn-outline-warning btn-icon-only" 
                                       title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-icon-only" 
                                            onclick="deleteReading({{ $reading->id }}, '{{ $reading->reading_date->format('d/m/Y') }}')" 
                                            title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Chưa có số liệu đo nào</h5>
                                <p class="text-muted">Hãy thêm số liệu đo đầu tiên để bắt đầu quản lý.</p>
                                <a href="{{ route('staff.meter-readings.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Thêm số liệu đo
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    @if($readings->hasPages())
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-center">
                {{ $readings->appends(request()->query())->links('vendor.pagination.custom', ['tableContainerId' => 'meter-readings-table-container']) }}
            </div>
        </div>
    @endif
</div>
</div>

