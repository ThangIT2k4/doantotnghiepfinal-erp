@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết công tơ đo')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với breadcrumbs --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết công tơ đo',
            'subtitle' => 'Thông tin chi tiết công tơ: ' . $meter->serial_no,
            'icon' => 'fas fa-tachometer-alt',
            'breadcrumbs' => [
                ['label' => 'Công tơ đo', 'url' => route('staff.meters.index')],
                ['label' => $meter->serial_no, 'active' => true]
            ]
        ])

        <div class="row">
            {{-- Nội dung chính --}}
            <div class="col-lg-8">
                {{-- Card 1: Thông tin công tơ đo --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin công tơ đo
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Số seri:</label>
                                    <p class="mb-0">{{ $meter->serial_no }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Bất động sản:</label>
                                    <p class="mb-0">{{ $meter->property->name }}</p>
                                    <small class="text-muted">{{ $meter->property->address ?? 'Chưa có địa chỉ' }}</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Phòng:</label>
                                    <p class="mb-0">{{ $meter->unit->code }} - {{ $meter->unit->unit_type }}</p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Loại dịch vụ:</label>
                                    <p class="mb-0">{{ $meter->service->name }} ({{ $meter->service->key_code }})</p>
                                    @if($meter->service->unit_label)
                                        <small class="text-muted">Đơn vị: {{ $meter->service->unit_label }}</small>
                                    @endif
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày lắp đặt:</label>
                                    <p class="mb-0">{{ $meter->installed_at->format('d/m/Y') }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Trạng thái:</label>
                                    <p class="mb-0">
                                        @if($meter->status)
                                            <span class="badge bg-success">Hoạt động</span>
                                        @else
                                            <span class="badge bg-secondary">Ngừng hoạt động</span>
                                        @endif
                                    </p>
                                </div>
                                
                                @if($meter->deleted_at)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Trạng thái xóa:</label>
                                    <p class="mb-0">
                                        <span class="badge bg-danger">Đã xóa</span>
                                        @if($meter->deletedBy)
                                            <br><small class="text-muted">Xóa bởi: {{ $meter->deletedBy->name }} - {{ $meter->deleted_at->format('d/m/Y H:i') }}</small>
                                        @endif
                                    </p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Hợp đồng hiện tại --}}
                @if($currentLease)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-file-contract me-2"></i>Hợp đồng hiện tại
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Khách thuê:</label>
                                    <p class="mb-0">{{ $currentLease->tenant->name ?? 'N/A' }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày bắt đầu:</label>
                                    <p class="mb-0">{{ $currentLease->start_date->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày kết thúc:</label>
                                    <p class="mb-0">{{ $currentLease->end_date->format('d/m/Y') }}</p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Trạng thái hợp đồng:</label>
                                    <p class="mb-0">
                                        <span class="badge bg-success">{{ ucfirst($currentLease->status) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Card 3: Thống kê sử dụng --}}
                @if($statistics)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>Thống kê sử dụng
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-primary">{{ $statistics['total_readings'] }}</h4>
                                    <p class="text-muted mb-0">Tổng số lần đo</p>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-success">{{ number_format($statistics['average_usage'], 2) }}</h4>
                                    <p class="text-muted mb-0">Mức sử dụng TB</p>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-info">
                                        @if($statistics['usage_trend'] === 'increasing')
                                            <i class="fas fa-arrow-up text-danger"></i>
                                        @elseif($statistics['usage_trend'] === 'decreasing')
                                            <i class="fas fa-arrow-down text-success"></i>
                                        @else
                                            <i class="fas fa-minus text-warning"></i>
                                        @endif
                                    </h4>
                                    <p class="text-muted mb-0">Xu hướng sử dụng</p>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h4 class="text-warning">
                                        @if($statistics['last_reading'])
                                            {{ number_format($statistics['last_reading']->value, 3) }}
                                        @else
                                            N/A
                                        @endif
                                    </h4>
                                    <p class="text-muted mb-0">Số liệu cuối</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Card 4: Lịch sử đo gần đây --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>Lịch sử đo gần đây
                            </h6>
                            <a href="{{ route('staff.meter-readings.index', ['meter_id' => $meter->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>Xem tất cả
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($meter->readings->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Ngày đo</th>
                                            <th>Giá trị</th>
                                            <th>Người đo</th>
                                            <th>Ghi chú</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($meter->readings->take(5) as $reading)
                                            <tr>
                                                <td>{{ $reading->reading_date->format('d/m/Y') }}</td>
                                                <td>
                                                    <span class="fw-bold">{{ number_format($reading->value, 3) }}</span>
                                                    <small class="text-muted">{{ $meter->service->unit_label }}</small>
                                                </td>
                                                <td>{{ $reading->takenBy->name ?? 'N/A' }}</td>
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
                                                    <a href="{{ route('staff.meter-readings.show', $reading->id) }}" 
                                                       class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-list fa-2x text-muted mb-3"></i>
                                <h6 class="text-muted">Chưa có số liệu đo nào</h6>
                                <p class="text-muted">Hãy thêm số liệu đo đầu tiên để bắt đầu theo dõi.</p>
                                <a href="{{ route('staff.meter-readings.create', ['meter_id' => $meter->id]) }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Thêm số liệu đo
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
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
                            $primaryActions = [];
                            
                            if(!$meter->deleted_at) {
                                $primaryActions[] = [
                                    'type' => 'link',
                                    'variant' => 'primary',
                                    'label' => 'Sửa',
                                    'icon' => 'fas fa-edit',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.meters.edit', $meter->id),
                                    'class' => 'w-100'
                                ];
                                
                                $primaryActions[] = [
                                    'type' => 'link',
                                    'variant' => 'info',
                                    'label' => 'Thêm số liệu đo',
                                    'icon' => 'fas fa-plus',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.meter-readings.create', ['meter_id' => $meter->id]),
                                    'class' => 'w-100'
                                ];
                                
                                $primaryActions[] = [
                                    'type' => 'link',
                                    'variant' => 'info',
                                    'label' => 'Xem tất cả số liệu',
                                    'icon' => 'fas fa-list',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.meter-readings.index', ['meter_id' => $meter->id]),
                                    'class' => 'w-100'
                                ];
                                
                                $primaryActions[] = [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteMeter({$meter->id}, '" . addslashes($meter->serial_no) . "')",
                                    'class' => 'w-100'
                                ];
                            } else {
                                $primaryActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Khôi phục',
                                    'icon' => 'fas fa-undo',
                                    'iconPosition' => 'left',
                                    'onclick' => "restoreMeter({$meter->id}, '" . addslashes($meter->serial_no) . "')",
                                    'class' => 'w-100'
                                ];
                                
                                $primaryActions[] = [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa vĩnh viễn',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "forceDeleteMeter({$meter->id}, '" . addslashes($meter->serial_no) . "')",
                                    'class' => 'w-100'
                                ];
                            }
                            
                            $primaryActions[] = [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Quay lại',
                                'icon' => 'fas fa-arrow-left',
                                'iconPosition' => 'left',
                                'url' => route('staff.meters.index'),
                                'class' => 'w-100'
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            if(!$meter->deleted_at) {
                                if($meter->status != 1) {
                                    $statusActions[] = [
                                        'type' => 'button',
                                        'variant' => 'success',
                                        'label' => 'Chuyển về Hoạt động',
                                        'icon' => 'fas fa-check-circle',
                                        'onclick' => "updateMeterStatus(1)"
                                    ];
                                }
                                
                                if($meter->status != 0) {
                                    $statusActions[] = [
                                        'type' => 'button',
                                        'variant' => 'warning',
                                        'label' => 'Chuyển về Ngừng hoạt động',
                                        'icon' => 'fas fa-pause-circle',
                                        'onclick' => "updateMeterStatus(0)"
                                    ];
                                }
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

                {{-- Card Trạng thái --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Trạng thái
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            @if($meter->status)
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-success">Hoạt động</h5>
                                <p class="text-muted">Công tơ đang hoạt động bình thường</p>
                            @else
                                <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                                <h5 class="text-danger">Ngừng hoạt động</h5>
                                <p class="text-muted">Công tơ đã bị vô hiệu hóa</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Lịch sử thanh toán --}}
                @if($billingHistory && count($billingHistory) > 0)
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-receipt me-2"></i>Lịch sử thanh toán
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach($billingHistory as $billing)
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="mb-1">{{ $billing['period'] }}</h6>
                                            <p class="mb-1 text-muted">{{ $billing['usage'] }} {{ $meter->service->unit_label }}</p>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-{{ $billing['status'] === 'paid' ? 'success' : 'warning' }}">
                                                {{ $billing['status'] === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                                            </span>
                                            <p class="mb-0 fw-bold">{{ number_format($billing['amount']) }} VNĐ</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</main>

@endsection

@push('scripts')
<script>
function deleteMeter(meterId, serialNo) {
    if (typeof window.Notify === 'undefined') {
        if (confirm(`Bạn có chắc chắn muốn xóa công tơ đo "${serialNo}"?`)) {
            deleteMeterAction(meterId);
        }
    } else {
        Notify.confirmDelete(`công tơ đo "${serialNo}"`, () => {
            deleteMeterAction(meterId);
        });
    }
}

function deleteMeterAction(meterId) {
    if (window.Preloader) {
        window.Preloader.show();
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error('CSRF token not found');
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
        } else {
            alert('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.');
        }
        if (window.Preloader) {
            window.Preloader.hide();
        }
        return;
    }

    fetch(`/staff/meters/${meterId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof window.Notify !== 'undefined') {
                Notify.success(data.message || 'Đã xóa công tơ đo thành công!', 'Đã xóa!');
            } else {
                alert('Đã xóa công tơ đo thành công!');
            }
            setTimeout(() => {
                window.location.href = '{{ route("staff.meters.index") }}';
            }, 1500);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa công tơ đo', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra khi xóa công tơ đo: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể xóa công tơ đo: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        } else {
            alert('Không thể xóa công tơ đo: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
        }
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
}

function restoreMeter(meterId, serialNo) {
    if (typeof window.Notify === 'undefined') {
        if (confirm(`Bạn có chắc chắn muốn khôi phục công tơ đo ${serialNo}?`)) {
            restoreMeterAction(meterId);
        }
    } else {
        Notify.confirm({
            title: 'Xác nhận khôi phục',
            message: `Bạn có chắc chắn muốn khôi phục công tơ đo "${serialNo}"?`,
            type: 'warning',
            confirmText: 'Xác nhận',
            cancelText: 'Hủy',
            onConfirm: function() {
                restoreMeterAction(meterId);
            }
        });
    }
}

function restoreMeterAction(meterId) {
    if (window.Preloader) {
        window.Preloader.show();
    }

    fetch(`/staff/meters/${meterId}/restore`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof window.Notify !== 'undefined') {
                Notify.success(data.message || 'Đã khôi phục công tơ đo thành công!', 'Thành công!');
            } else {
                alert('Đã khôi phục công tơ đo thành công!');
            }
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể khôi phục công tơ đo: ' + error.message, 'Lỗi hệ thống!');
        } else {
            alert('Không thể khôi phục công tơ đo: ' + error.message);
        }
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
}

function forceDeleteMeter(meterId, serialNo) {
    if (typeof window.Notify === 'undefined') {
        if (confirm(`Bạn có chắc chắn muốn xóa VĨNH VIỄN công tơ đo ${serialNo}?\n\nHành động này không thể hoàn tác!`)) {
            forceDeleteMeterAction(meterId);
        }
    } else {
        Notify.confirm({
            title: 'Xác nhận xóa vĩnh viễn',
            message: `Bạn có chắc chắn muốn xóa VĨNH VIỄN công tơ đo "${serialNo}"?\n\nHành động này không thể hoàn tác!`,
            type: 'danger',
            confirmText: 'Xác nhận xóa',
            cancelText: 'Hủy',
            onConfirm: function() {
                forceDeleteMeterAction(meterId);
            }
        });
    }
}

function forceDeleteMeterAction(meterId) {
    if (window.Preloader) {
        window.Preloader.show();
    }

    fetch(`/staff/meters/${meterId}/force-delete`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof window.Notify !== 'undefined') {
                Notify.success(data.message || 'Đã xóa vĩnh viễn công tơ đo thành công!', 'Thành công!');
            } else {
                alert('Đã xóa vĩnh viễn công tơ đo thành công!');
            }
            setTimeout(() => {
                window.location.href = '/staff/meters';
            }, 1500);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể xóa vĩnh viễn công tơ đo: ' + error.message, 'Lỗi hệ thống!');
        } else {
            alert('Không thể xóa vĩnh viễn công tơ đo: ' + error.message);
        }
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
}

function updateMeterStatus(newStatus) {
    const statusLabels = {
        1: 'Hoạt động',
        0: 'Ngừng hoạt động'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển sang trạng thái "${statusLabels[newStatus]}"?`,
        type: newStatus === 0 ? 'warning' : 'success',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const formData = new FormData();
            formData.append('status', newStatus);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('{{ route("staff.meters.update-status", $meter->id) }}', {
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
</script>
@endpush
