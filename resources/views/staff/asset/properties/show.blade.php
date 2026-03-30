@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết bất động sản')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header (không có actions) --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết Bất động sản',
            'subtitle' => 'Thông tin chi tiết về bất động sản: ' . $property->name,
            'icon' => 'fas fa-building',
            'breadcrumbs' => [
                ['label' => 'Bất động sản', 'url' => route('staff.properties.index')],
                ['label' => $property->name, 'active' => true]
            ]
        ])

    <!-- Property Details -->
    <div class="row">
        <!-- Main Info -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tên bất động sản:</label>
                                <div class="p-2 bg-light rounded">{{ $property->name }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Loại bất động sản:</label>
                                <div class="p-2 bg-light rounded">
                                    @if ($property->propertyType)
                                        <i class="{{ $property->propertyType->icon }} me-2"></i>
                                        {{ $property->propertyType->name }}
                                    @else
                                        <span class="text-muted">Chưa phân loại</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Chủ sở hữu:</label>
                                <div class="p-2 bg-light rounded">
                                    @if ($property->getCurrentLandlord())
                                        <i class="fas fa-user me-2"></i>
                                        {{ $property->getCurrentLandlord()->full_name }}
                                        <small class="text-muted d-block">(Từ hợp đồng thuê lại)</small>
                                    @else
                                        <span class="text-muted">Chưa có hợp đồng thuê lại</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Trạng thái:</label>
                                <div class="p-2 bg-light rounded">
                                    @if ($property->status == 1)
                                        <span class="badge bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge bg-warning">Tạm ngưng</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tổng số tầng:</label>
                                <div class="p-2 bg-light rounded">
                                    {{ $property->total_floors ?? 'Chưa xác định' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tổng số phòng:</label>
                                <div class="p-2 bg-light rounded">
                                    {{ $property->units->count() }}
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Mô tả:</label>
                                <div class="p-2 bg-light rounded">
                                    {{ $property->description ?? 'Chưa có mô tả' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Property Images -->
            @if ($property->images && count($property->images) > 0)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-images me-2"></i>Hình ảnh bất động sản
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach ($property->images as $index => $image)
                        @php
                            // Handle both string and array formats
                            $imagePath = is_array($image) ? ($image['original'] ?? $image['url'] ?? $image) : $image;
                            
                            // Check if already a full URL
                            if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
                                // Already a full URL, use it directly
                                $imageUrl = $imagePath;
                            } else {
                                // Path đã không có storage/ prefix, chỉ cần thêm vào URL
                                $imageUrl = asset('storage/' . ltrim($imagePath, '/'));
                            }
                        @endphp
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="card property-image-card">
                                <img src="{{ $imageUrl }}" 
                                     class="card-img-top property-image" 
                                     alt="Hình ảnh {{ $index + 1 }}"
                                     style="height: 200px; object-fit: cover; cursor: pointer;"
                                     data-bs-toggle="modal" 
                                     data-bs-target="#imageModal"
                                     data-bs-image="{{ $imageUrl }}"
                                     data-bs-title="Hình ảnh {{ $index + 1 }}">
                                <div class="card-body p-2">
                                    <small class="text-muted">Hình ảnh {{ $index + 1 }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Location Info -->
            @if ($property->location || $property->location2025)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-map-marker-alt me-2"></i>Địa chỉ bất động sản
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- New Location 2025 -->
                        @if ($property->location2025)
                        <div class="{{ $property->location ? 'col-md-6' : 'col-12' }} mb-3">
                            <h6 class="fw-bold mb-3 text-success border-bottom pb-2">
                                <i class="fas fa-map-marker-alt me-2"></i>Hệ thống mới 2025
                            </h6>
                            <div class="p-3 bg-light rounded">
                                @php
                                    $addressParts = array_filter([
                                        $property->location2025->street,
                                        $property->location2025->ward,
                                        $property->location2025->city,
                                        $property->location2025->country
                                    ]);
                                @endphp
                                <p class="mb-2">
                                    <i class="fas fa-map-marker-alt text-success me-2"></i>
                                    <strong>{{ implode(', ', $addressParts) }}</strong>
                                </p>
                                @if($property->location2025->lat && $property->location2025->lng)
                                <p class="mb-1 small text-muted">
                                    <i class="fas fa-location-dot me-1"></i>Tọa độ: {{ $property->location2025->lat }}, {{ $property->location2025->lng }}
                                </p>
                                @endif
                                @if($property->location2025->postal_code)
                                <p class="mb-0 small text-muted">
                                    <i class="fas fa-envelope me-1"></i>Mã bưu điện: {{ $property->location2025->postal_code }}
                                </p>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Old Location -->
                        @if ($property->location)
                        <div class="{{ $property->location2025 ? 'col-md-6' : 'col-12' }} mb-3">
                            <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                                <i class="fas fa-map-marker-alt me-2"></i>Hệ thống cũ
                            </h6>
                            <div class="p-3 bg-light rounded">
                                @php
                                    $addressParts = array_filter([
                                        $property->location->street,
                                        $property->location->ward,
                                        $property->location->district,
                                        $property->location->city,
                                        $property->location->country
                                    ]);
                                @endphp
                                <p class="mb-2">
                                    <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                    <strong>{{ implode(', ', $addressParts) }}</strong>
                                </p>
                                @if($property->location->lat && $property->location->lng)
                                <p class="mb-1 small text-muted">
                                    <i class="fas fa-location-dot me-1"></i>Tọa độ: {{ $property->location->lat }}, {{ $property->location->lng }}
                                </p>
                                @endif
                                @if($property->location->postal_code)
                                <p class="mb-0 small text-muted">
                                    <i class="fas fa-envelope me-1"></i>Mã bưu điện: {{ $property->location->postal_code }}
                                </p>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Card Thông tin bất động sản --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Thông tin bất động sản
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold mb-1">Trạng thái:</label>
                        <div>
                            @if ($property->status == 1)
                                <span class="badge bg-success">Hoạt động</span>
                            @else
                                <span class="badge bg-warning">Tạm ngưng</span>
                            @endif
                        </div>
                    </div>
                    
                    @php
                        $totalUnits = $property->units->count();
                        $occupiedUnits = $property->units->filter(function($unit) {
                            return $unit->leases()->where('status', 'active')->whereNull('deleted_at')->exists();
                        })->count();
                        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;
                    @endphp
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold mb-1">Tỷ lệ lấp đầy:</label>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Phòng đã sử dụng</span>
                            <span class="fw-bold small">{{ $occupiedUnits }}/{{ $totalUnits }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar 
                                @if($occupancyRate >= 90) bg-danger
                                @elseif($occupancyRate >= 70) bg-warning
                                @elseif($occupancyRate >= 50) bg-info
                                @else bg-success
                                @endif"
                                style="width: {{ $occupancyRate }}%">
                            </div>
                        </div>
                        <small class="text-muted">{{ $occupancyRate }}%</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold mb-1">Chủ sở hữu:</label>
                        <div>
                            @if ($property->getCurrentLandlord())
                                <i class="fas fa-user me-2 text-muted"></i>
                                <strong>{{ $property->getCurrentLandlord()->full_name }}</strong>
                                <small class="text-muted d-block mt-1">(Từ hợp đồng thuê lại)</small>
                            @else
                                <span class="text-muted">Chưa có hợp đồng thuê lại</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card Gói dịch vụ và Chu kỳ thanh toán --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-cog me-2"></i>Gói dịch vụ & Chu kỳ thanh toán
                    </h6>
                </div>
                <div class="card-body">
                    {{-- Payment Cycle Info --}}
                    @php
                        $effectivePaymentCycle = $property->getEffectivePaymentCycle();
                    @endphp
                    <div class="mb-4">
                        <label class="form-label fw-bold mb-2">
                            <i class="fas fa-calendar-alt me-2 text-primary"></i>Chu kỳ thanh toán:
                        </label>
                        @if($effectivePaymentCycle)
                            <div class="p-2 bg-light rounded">
                                <div class="mb-2">
                                    <strong>{{ $effectivePaymentCycle->name ?? $effectivePaymentCycle->cycle_type_name }}</strong>
                                    @if($property->payment_cycle_id)
                                        <span class="badge bg-info ms-2">Riêng BĐS</span>
                                    @else
                                        <span class="badge bg-secondary ms-2">Mặc định</span>
                                    @endif
                                </div>
                                <div class="small text-muted">
                                    <div><i class="fas fa-info-circle me-1"></i>Loại: {{ $effectivePaymentCycle->cycle_type_name }}</div>
                                    @if($effectivePaymentCycle->billing_day)
                                        <div><i class="fas fa-calendar-day me-1"></i>Ngày tạo hóa đơn: {{ $effectivePaymentCycle->billing_day }}</div>
                                    @endif
                                    @if($effectivePaymentCycle->payment_due_hours)
                                        <div><i class="fas fa-clock me-1"></i>Thời hạn thanh toán: {{ round($effectivePaymentCycle->payment_due_hours / 24, 1) }} ngày</div>
                                    @endif
                                    @if($effectivePaymentCycle->invoice_timing)
                                        <div><i class="fas fa-receipt me-1"></i>Thời điểm tạo hóa đơn: 
                                            {{ $effectivePaymentCycle->invoice_timing === 'start_of_cycle' ? 'Đầu chu kỳ' : 'Cuối chu kỳ' }}
                                        </div>
                                    @endif
                                    @if($effectivePaymentCycle->notes)
                                        <div class="mt-1"><i class="fas fa-sticky-note me-1"></i>{{ $effectivePaymentCycle->notes }}</div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="p-2 bg-light rounded">
                                <span class="text-muted">Chưa cấu hình chu kỳ thanh toán</span>
                            </div>
                        @endif
                    </div>

                    {{-- Service Package Info --}}
                    @php
                        $effectiveLeaseServiceSet = $property->getEffectiveLeaseServiceSet();
                    @endphp
                    <div>
                        <label class="form-label fw-bold mb-2">
                            <i class="fas fa-box me-2 text-success"></i>Gói dịch vụ:
                        </label>
                        @if($effectiveLeaseServiceSet)
                            <div class="p-2 bg-light rounded">
                                <div class="mb-2">
                                    <strong>{{ $effectiveLeaseServiceSet->name }}</strong>
                                    @if($property->lease_services_id)
                                        <span class="badge bg-info ms-2">Riêng BĐS</span>
                                    @else
                                        <span class="badge bg-secondary ms-2">Mặc định</span>
                                    @endif
                                </div>
                                @if($effectiveLeaseServiceSet->description)
                                    <div class="small text-muted mb-2">{{ $effectiveLeaseServiceSet->description }}</div>
                                @endif
                                @if($effectiveLeaseServiceSet->items && $effectiveLeaseServiceSet->items->count() > 0)
                                    <div class="mt-2">
                                        <small class="text-muted d-block mb-1">Danh sách dịch vụ:</small>
                                        <ul class="list-unstyled mb-0">
                                            @foreach($effectiveLeaseServiceSet->items as $item)
                                                <li class="small mb-1">
                                                    <i class="fas fa-check-circle text-success me-1"></i>
                                                    <strong>{{ $item->service->name ?? 'N/A' }}</strong>
                                                    @if($item->price)
                                                        <span class="text-muted">- {{ number_format($item->price, 0, ',', '.') }} đ</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    <div class="small text-muted">Chưa có dịch vụ nào trong gói</div>
                                @endif
                            </div>
                        @else
                            <div class="p-2 bg-light rounded">
                                <span class="text-muted">Chưa cấu hình gói dịch vụ</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Card Thao tác --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>Thao tác
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        // Primary actions: Sửa, Xóa, Quay lại (hiển thị vertical)
                        $primaryActions = [
                            [
                                'type' => 'link',
                                'variant' => 'primary',
                                'label' => 'Sửa',
                                'icon' => 'fas fa-edit',
                                'iconPosition' => 'left',
                                'url' => route('staff.properties.edit', $property->id),
                                'class' => 'w-100'
                            ],
                            [
                                'type' => 'button',
                                'variant' => 'danger',
                                'label' => 'Xóa',
                                'icon' => 'fas fa-trash-alt',
                                'iconPosition' => 'left',
                                'onclick' => "deleteProperty({$property->id}, '" . addslashes($property->name) . "')",
                                'class' => 'w-100'
                            ],
                            [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Quay lại',
                                'icon' => 'fas fa-arrow-left',
                                'iconPosition' => 'left',
                                'url' => route('staff.properties.index'),
                                'class' => 'w-100'
                            ]
                        ];
                        
                        // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                        $statusActions = [];
                        
                        if($property->status != 1) {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'success',
                                'label' => 'Kích hoạt',
                                'icon' => 'fas fa-check-circle',
                                'onclick' => "updatePropertyStatus(1)"
                            ];
                        }
                        
                        if($property->status != 0) {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'warning',
                                'label' => 'Tạm ngưng',
                                'icon' => 'fas fa-pause-circle',
                                'onclick' => "updatePropertyStatus(0)"
                            ];
                        }
                    @endphp
                    
                    <div class="d-grid gap-2">
                        {{-- Primary Actions: Sửa, Xóa, Quay lại (vertical) --}}
                        @include('staff.components.action-buttons', [
                            'layout' => 'vertical',
                            'size' => 'sm',
                            'actions' => $primaryActions
                        ])
                        
                        {{-- Status Actions: Dropdown cho các nút chuyển trạng thái --}}
                        @if(count($statusActions) > 0)
                            @include('staff.components.action-buttons', [
                                'layout' => 'dropdown',
                                'size' => 'sm',
                                'dropdownLabel' => 'Chuyển trạng thái',
                                'actions' => $statusActions
                            ])
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Card Hành động nhanh --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>Hành động nhanh
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('staff.units.create') }}?property_id={{ $property->id }}" class="btn btn-info btn-sm">
                            <i class="fas fa-plus me-1"></i> Thêm phòng
                        </a>
                        @php
                            $masterLease = $property->masterLeases->first();
                        @endphp
                        @if($masterLease)
                            <a href="{{ route('staff.master-leases.show', $masterLease->id) }}" class="btn btn-success btn-sm">
                                <i class="fas fa-file-contract me-1"></i> Xem hợp đồng chính
                            </a>
                        @else
                            <a href="{{ route('staff.master-leases.create', ['property_id' => $property->id]) }}" class="btn btn-success btn-sm">
                                <i class="fas fa-file-contract me-1"></i> Tạo hợp đồng chính
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Card Thông tin hệ thống --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info me-2"></i>Thông tin hệ thống
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">ID:</small>
                        <span class="fw-bold">{{ $property->id }}</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Ngày tạo:</small>
                        <span class="fw-bold">{{ $property->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Cập nhật cuối:</small>
                        <span class="fw-bold">{{ $property->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if ($property->deleted_at)
                    <div class="mb-2">
                        <small class="text-muted">Đã xóa:</small>
                        <span class="fw-bold text-danger">{{ $property->deleted_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Units List -->
    @if ($property->units->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-door-open me-2"></i>Danh sách phòng ({{ $property->units->count() }})
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tên phòng</th>
                                    <th>Diện tích</th>
                                    <th>Giá thuê</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày tạo</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($property->units as $unit)
                                <tr>
                                    <td>
                                        <strong>{{ $unit->code }}</strong>
                                        @if ($unit->note)
                                        <br><small class="text-muted">{{ $unit->note }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $unit->area_m2 ? number_format($unit->area_m2, 2) . ' m²' : 'N/A' }}</td>
                                    <td>
                                        @if ($unit->base_rent)
                                            {{ number_format($unit->base_rent, 0, ',', '.') }} đ/tháng
                                        @else
                                            <span class="text-muted">Chưa xác định</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($unit->status == 'available')
                                            <span class="badge bg-success">Trống</span>
                                        @elseif ($unit->status == 'occupied')
                                            <span class="badge bg-danger">Đã thuê</span>
                                        @elseif ($unit->status == 'reserved')
                                            <span class="badge bg-info">Đã đặt</span>
                                        @elseif ($unit->status == 'maintenance')
                                            <span class="badge bg-warning">Bảo trì</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $unit->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $unit->created_at->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('staff.units.show', $unit->id) }}" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.units.edit', $unit->id) }}" class="btn btn-sm btn-outline-warning" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if ($unit->status == 'available')
                                            <a href="{{ route('staff.booking-deposits.create', ['property_id' => $property->id, 'unit_id' => $unit->id]) }}" class="btn btn-sm btn-outline-info" title="Tạo đặt cọc">
                                                <i class="fas fa-hand-holding-usd"></i>
                                            </a>
                                            <a href="{{ route('staff.leases.create', ['property_id' => $property->id, 'unit_id' => $unit->id]) }}" class="btn btn-sm btn-outline-success" title="Tạo hợp đồng">
                                                <i class="fas fa-file-contract"></i>
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    </div>
</main>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Hình ảnh bất động sản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid" alt="Hình ảnh bất động sản">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.property-image {
    transition: transform 0.3s ease;
}

.property-image:hover {
    transform: scale(1.05);
}

.property-image-card {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    overflow: hidden;
    transition: box-shadow 0.3s ease;
}

.property-image-card:hover {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

#modalImage {
    max-height: 70vh;
    width: auto;
    border-radius: 0.35rem;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image modal functionality
    const imageModal = document.getElementById('imageModal');
    if (imageModal) {
        imageModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const imageSrc = button.getAttribute('data-bs-image');
            const imageTitle = button.getAttribute('data-bs-title');
            
            const modalImage = imageModal.querySelector('#modalImage');
            const modalTitle = imageModal.querySelector('#imageModalLabel');
            
            modalImage.src = imageSrc;
            modalTitle.textContent = imageTitle;
        });
    }
});

function deleteProperty(id, name) {
    Notify.confirmDelete(`bất động sản "${name}"`, () => {
        if (window.Preloader) {
            window.Preloader.show();
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
            if (window.Preloader) {
                window.Preloader.hide();
            }
            return;
        }

        fetch(`/staff/properties/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Đã xóa!');
                setTimeout(() => {
                    window.location.href = '{{ route("staff.properties.index") }}';
                }, 1000);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể xóa bất động sản: ' + error.message, 'Lỗi hệ thống!');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
}

function updatePropertyStatus(status) {
    const statusLabels = {
        1: 'Hoạt động',
        0: 'Tạm ngưng'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển sang trạng thái "${statusLabels[status]}"?`,
        type: status === 0 ? 'warning' : 'success',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const formData = new FormData();
            formData.append('status', status);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('{{ route("staff.properties.update-status", $property->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }
                
                if (data.success) {
                    Notify.success(data.message || 'Đã cập nhật trạng thái thành công!', 'Thành công!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Không thể cập nhật trạng thái: ' + error.message, 'Lỗi hệ thống!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    });
}

// Display flash messages as toast notifications
document.addEventListener('DOMContentLoaded', function() {
    @if(session('success'))
        Notify.success('{{ session('success') }}', 'Thành công!');
    @endif
    
    @if(session('error'))
        Notify.error('{{ session('error') }}', 'Lỗi!');
    @endif
    
    @if(session('warning'))
        Notify.warning('{{ session('warning') }}', 'Cảnh báo!');
    @endif
    
    @if(session('info'))
        Notify.info('{{ session('info') }}', 'Thông tin!');
    @endif
});
</script>
@endpush
