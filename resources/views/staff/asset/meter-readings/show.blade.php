@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết số liệu đo')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với breadcrumbs --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết số liệu đo',
            'subtitle' => 'Thông tin chi tiết số liệu đo ngày ' . $meterReading->reading_date->format('d/m/Y'),
            'icon' => 'fas fa-clipboard-list',
            'breadcrumbs' => [
                ['label' => 'Số liệu đo', 'url' => route('staff.meter-readings.index')],
                ['label' => $meterReading->reading_date->format('d/m/Y'), 'active' => true]
            ]
        ])

        <div class="row">
            {{-- Nội dung chính --}}
            <div class="col-lg-8">
                {{-- Card 1: Thông tin số liệu đo --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin số liệu đo
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày đo:</label>
                                    <p class="mb-0">{{ $meterReading->reading_date->format('d/m/Y') }}</p>
                                    <small class="text-muted">{{ $meterReading->reading_date->format('H:i:s') }}</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Giá trị đo:</label>
                                    <p class="mb-0">
                                        <span class="h4 text-primary">{{ number_format($meterReading->value, 3) }}</span>
                                        <span class="text-muted">{{ $meterReading->meter->service->unit_label }}</span>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Người đo:</label>
                                    <p class="mb-0">{{ $meterReading->takenBy->name ?? 'N/A' }}</p>
                                    @if($meterReading->takenBy)
                                        <small class="text-muted">{{ $meterReading->takenBy->email }}</small>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Công tơ:</label>
                                    <p class="mb-0">{{ $meterReading->meter->serial_no }}</p>
                                    <small class="text-muted">ID: {{ $meterReading->meter->id }}</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Bất động sản:</label>
                                    <p class="mb-0">{{ $meterReading->meter->property->name }}</p>
                                    <small class="text-muted">{{ $meterReading->meter->property->address ?? 'Chưa có địa chỉ' }}</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Phòng:</label>
                                    <p class="mb-0">{{ $meterReading->meter->unit->code }} - {{ $meterReading->meter->unit->unit_type }}</p>
                                </div>
                            </div>
                        </div>
                        
                        @if($meterReading->note)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ghi chú:</label>
                                <p class="mb-0">{{ $meterReading->note }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Card 2: Hình ảnh công tơ --}}
                @if($meterReading->image_url)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-image me-2"></i>Hình ảnh công tơ
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <img src="{{ Storage::url($meterReading->image_url) }}" 
                             alt="Hình ảnh công tơ" 
                             class="img-fluid rounded shadow" 
                             style="max-width: 100%; max-height: 400px;">
                    </div>
                </div>
                @endif

                {{-- Card 3: So sánh sử dụng --}}
                @if($previousReading || $nextReading)
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>So sánh sử dụng
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @if($previousReading)
                            <div class="col-md-6">
                                <div class="text-center p-3 border rounded">
                                    <h6 class="text-muted">Lần đo trước</h6>
                                    <h4 class="text-primary">{{ number_format($previousReading->value, 3) }}</h4>
                                    <p class="text-muted mb-0">{{ $previousReading->reading_date->format('d/m/Y') }}</p>
                                    <small class="text-muted">{{ $previousReading->takenBy->name ?? 'N/A' }}</small>
                                </div>
                            </div>
                            @endif
                            
                            <div class="col-md-6">
                                <div class="text-center p-3 border rounded bg-light">
                                    <h6 class="text-muted">Lần đo hiện tại</h6>
                                    <h4 class="text-success">{{ number_format($meterReading->value, 3) }}</h4>
                                    <p class="text-muted mb-0">{{ $meterReading->reading_date->format('d/m/Y') }}</p>
                                    <small class="text-muted">{{ $meterReading->takenBy->name ?? 'N/A' }}</small>
                                </div>
                            </div>
                        </div>
                        
                        @if($previousReading)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="text-center p-3 border rounded bg-info bg-opacity-10">
                                        <h6 class="text-info">Mức sử dụng</h6>
                                        <h3 class="text-info">
                                            {{ number_format($meterReading->value - $previousReading->value, 3) }} 
                                            {{ $meterReading->meter->service->unit_label }}
                                        </h3>
                                        <p class="text-muted mb-0">
                                            Khoảng cách: {{ $meterReading->reading_date->diffInDays($previousReading->reading_date) }} ngày
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        @if($nextReading)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="text-center p-3 border rounded bg-warning bg-opacity-10">
                                        <h6 class="text-warning">Lần đo tiếp theo</h6>
                                        <h4 class="text-warning">{{ number_format($nextReading->value, 3) }}</h4>
                                        <p class="text-muted mb-0">{{ $nextReading->reading_date->format('d/m/Y') }}</p>
                                        <small class="text-muted">{{ $nextReading->takenBy->name ?? 'N/A' }}</small>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Card Thông tin công tơ --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-tachometer-alt me-2"></i>Thông tin công tơ
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Số seri:</label>
                            <p class="mb-0">{{ $meterReading->meter->serial_no }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Loại dịch vụ:</label>
                            <p class="mb-0">{{ $meterReading->meter->service->name }} ({{ $meterReading->meter->service->key_code }})</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Ngày lắp đặt:</label>
                            <p class="mb-0">{{ $meterReading->meter->installed_at->format('d/m/Y') }}</p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Trạng thái:</label>
                            <p class="mb-0">
                                @if($meterReading->meter->status)
                                    <span class="badge bg-success">Hoạt động</span>
                                @else
                                    <span class="badge bg-secondary">Ngừng hoạt động</span>
                                @endif
                            </p>
                        </div>
                        
                        <div class="d-grid">
                            <a href="{{ route('staff.meters.show', $meterReading->meter->id) }}" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-2"></i>Xem chi tiết công tơ
                            </a>
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
                                    'url' => route('staff.meter-readings.edit', $meterReading->id),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'info',
                                    'label' => 'Thêm số liệu mới',
                                    'icon' => 'fas fa-plus',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.meter-readings.create', ['meter_id' => $meterReading->meter->id]),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'info',
                                    'label' => 'Xem tất cả số liệu',
                                    'icon' => 'fas fa-list',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.meter-readings.index', ['meter_id' => $meterReading->meter->id]),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteReading({$meterReading->id}, '" . addslashes($meterReading->reading_date->format('d/m/Y')) . "')",
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Quay lại',
                                    'icon' => 'fas fa-arrow-left',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.meter-readings.index'),
                                    'class' => 'w-100'
                                ]
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái meter (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            if(!$meterReading->meter->deleted_at) {
                                if($meterReading->meter->status != 1) {
                                    $statusActions[] = [
                                        'type' => 'button',
                                        'variant' => 'success',
                                        'label' => 'Chuyển về Hoạt động',
                                        'icon' => 'fas fa-check-circle',
                                        'onclick' => "updateMeterStatus(1)"
                                    ];
                                }
                                
                                if($meterReading->meter->status != 0) {
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
                            
                            {{-- Status Actions: Dropdown cho các nút chuyển trạng thái meter --}}
                            @if(count($statusActions) > 0)
                                @include('staff.components.action-buttons', [
                                    'layout' => 'dropdown',
                                    'size' => 'sm',
                                    'dropdownLabel' => 'Chuyển trạng thái công tơ',
                                    'actions' => $statusActions
                                ])
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card Thống kê --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Thống kê
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <h4 class="text-primary">{{ $meterReading->meter->readings()->count() }}</h4>
                            <p class="text-muted mb-0">Tổng số lần đo</p>
                        </div>
                        
                        <hr>
                        
                        <div class="text-center">
                            <h4 class="text-success">
                                @if($meterReading->meter->readings()->count() > 1)
                                    {{ number_format($meterReading->meter->readings()->latest('reading_date')->first()->value - $meterReading->meter->readings()->oldest('reading_date')->first()->value, 3) }}
                                @else
                                    0
                                @endif
                            </h4>
                            <p class="text-muted mb-0">Tổng sử dụng</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@endsection

@push('scripts')
<script>
function updateMeterStatus(newStatus) {
    const statusLabels = {
        1: 'Hoạt động',
        0: 'Ngừng hoạt động'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển trạng thái công tơ sang "${statusLabels[newStatus]}"?`,
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
            
            fetch('{{ route("staff.meters.update-status", $meterReading->meter->id) }}', {
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

function deleteReading(readingId, readingDate) {
    if (typeof window.Notify === 'undefined') {
        if (confirm(`Bạn có chắc chắn muốn xóa số liệu đo ngày ${readingDate}?`)) {
            deleteReadingAction(readingId);
        }
    } else {
        Notify.confirmDelete(`số liệu đo ngày ${readingDate}`, () => {
            deleteReadingAction(readingId);
        });
    }
}

function deleteReadingAction(readingId) {
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

    fetch(`/staff/meter-readings/${readingId}`, {
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
                Notify.success(data.message || 'Đã xóa số liệu đo thành công!', 'Đã xóa!');
            } else {
                alert('Đã xóa số liệu đo thành công!');
            }
            setTimeout(() => {
                window.location.href = '{{ route("staff.meter-readings.index") }}';
            }, 1500);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa số liệu đo', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra khi xóa số liệu đo: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể xóa số liệu đo: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        } else {
            alert('Không thể xóa số liệu đo: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
        }
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
}
</script>
@endpush
