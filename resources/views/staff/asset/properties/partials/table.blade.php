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
            return '<i class="fas fa-sort text-muted"></i>';
        }
        return $sortOrder === 'asc' 
            ? '<i class="fas fa-sort-up text-primary"></i>' 
            : '<i class="fas fa-sort-down text-primary"></i>';
    };
@endphp

<div class="col-12" id="properties-table-container">
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>Danh sách Bất động sản ({{ $properties->total() }} kết quả)
        </h6>
    </div>
    <div class="card-body">
        @if($properties->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="{{ $generateSortUrl('id') }}" 
                                   hx-get="{{ $generateSortUrl('id') }}"
                                   hx-target="#properties-table-container"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   class="text-decoration-none text-dark"
                                   style="cursor: pointer;">
                                    ID
                                    {!! $getSortIcon('id') !!}
                                </a>
                            </th>
                            <th>
                                <a href="{{ $generateSortUrl('name') }}" 
                                   hx-get="{{ $generateSortUrl('name') }}"
                                   hx-target="#properties-table-container"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   class="text-decoration-none text-dark"
                                   style="cursor: pointer;">
                                    Tên BĐS
                                    {!! $getSortIcon('name') !!}
                                </a>
                            </th>
                            <th>Loại</th>
                            <th>Địa chỉ</th>
                            <th>Chủ sở hữu</th>
                            <th>Tổng phòng</th>
                            <th>Tỷ lệ lấp đầy</th>
                            <th>
                                <a href="{{ $generateSortUrl('status') }}" 
                                   hx-get="{{ $generateSortUrl('status') }}"
                                   hx-target="#properties-table-container"
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
                        @foreach($properties as $property)
                        <tr>
                            <td>#{{ $property->id }}</td>
                            <td>
                                <strong>{{ $property->name }}</strong>
                                @if ($property->total_floors)
                                <br><small class="text-muted">{{ $property->total_floors }} tầng</small>
                                @endif
                            </td>
                            <td>
                                @if ($property->propertyType)
                                <span class="badge bg-info">{{ $property->propertyType->name_local ?? $property->propertyType->name }}</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="address-info">
                                    @if ($property->location2025)
                                    <div class="mb-1">
                                        <small class="text-success">
                                            <i class="fas fa-map-marker-alt"></i> <strong>Mới 2025:</strong>
                                        </small>
                                        <br>
                                        <small>
                                            {{ $property->location2025->street }},
                                            {{ $property->location2025->ward }},
                                            {{ $property->location2025->city }}
                                        </small>
                                    </div>
                                    @endif
                                    
                                    @if ($property->location)
                                    <div>
                                        <small class="text-primary">
                                            <i class="fas fa-map-marker-alt"></i> <strong>Cũ:</strong>
                                        </small>
                                        <br>
                                        <small>
                                            {{ $property->location->street }},
                                            {{ $property->location->ward }},
                                            {{ $property->location->district }},
                                            {{ $property->location->city }}
                                        </small>
                                    </div>
                                    @endif
                                    
                                    @if (!$property->location && !$property->location2025)
                                    <span class="text-muted">Chưa có địa chỉ</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($property->getCurrentLandlord())
                                <div class="small">
                                    <strong>{{ $property->getCurrentLandlord()->full_name }}</strong>
                                </div>
                                @else
                                <span class="text-muted">Chưa có hợp đồng thuê lại</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="badge bg-primary">{{ $property->units->count() ?? 0 }}</span>
                                    <small class="text-muted">Tổng: {{ $property->units->count() }}</small>
                                </div>
                            </td>
                            <td>
                                @php
                                    $occupancyRate = $property->getOccupancyRate();
                                    $occupancyStatus = $property->getOccupancyStatusAttribute();
                                @endphp
                                <div class="d-flex flex-column">
                                    <div class="progress" style="height: 8px; width: 60px;">
                                        <div class="progress-bar 
                                            @if($occupancyStatus == 'full') bg-danger
                                            @elseif($occupancyStatus == 'high') bg-warning
                                            @elseif($occupancyStatus == 'medium') bg-info
                                            @else bg-success
                                            @endif" 
                                            role="progressbar" 
                                            style="width: {{ $occupancyRate }}%">
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ $occupancyRate }}%</small>
                                    <small class="badge 
                                        @if($occupancyStatus == 'full') bg-danger
                                        @elseif($occupancyStatus == 'high') bg-warning
                                        @elseif($occupancyStatus == 'medium') bg-info
                                        @else bg-success
                                        @endif">
                                        {{ $property->occupancy_status_text }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                @if ($property->status == 1)
                                <span class="badge bg-success">Hoạt động</span>
                                @else
                                <span class="badge bg-warning">Tạm ngưng</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group table-actions" role="group">
                                    {{-- Xem chi tiết - outline-primary --}}
                                    <a href="{{ route('staff.properties.show', $property->id) }}" 
                                       class="btn btn-outline-primary btn-icon-only" 
                                       title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    {{-- Sửa - outline-warning --}}
                                    <a href="{{ route('staff.properties.edit', $property->id) }}" 
                                       class="btn btn-outline-warning btn-icon-only" 
                                       title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    {{-- Xóa - outline-danger --}}
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-icon-only" 
                                            title="Xóa"
                                            onclick="deleteProperty({{ $property->id }}, '{{ addslashes($property->name) }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{-- Pagination --}}
            @if($properties->hasPages())
                <div class="mt-3">
                    {{ $properties->appends(request()->query())->links('vendor.pagination.custom', ['tableContainerId' => 'properties-table-container']) }}
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Không có bất động sản nào</h5>
                <p class="text-muted">Chưa có bất động sản nào hoặc không tìm thấy kết quả phù hợp.</p>
            </div>
        @endif
    </div>
</div>
</div>

@push('styles')
<style>
.address-info {
    max-width: 300px;
}
.address-info small {
    line-height: 1.3;
}
.address-info .text-primary,
.address-info .text-success {
    font-weight: 600;
}
</style>
@endpush

