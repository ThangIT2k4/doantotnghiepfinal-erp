@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Loại Bất động sản')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với breadcrumbs --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết Loại Bất động sản',
            'subtitle' => 'Thông tin chi tiết về loại bất động sản: ' . $propertyType->name,
            'icon' => 'fas fa-building',
            'breadcrumbs' => [
                ['label' => 'Loại Bất động sản', 'url' => route('staff.property-types.index')],
                ['label' => $propertyType->name, 'active' => true]
            ]
        ])

        <div class="row">
            {{-- Nội dung chính --}}
            <div class="col-lg-8">
                {{-- Card 1: Thông tin cơ bản --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Mã Code:</label>
                                    <div>
                                        <code class="bg-light px-2 py-1 rounded">{{ $propertyType->key_code }}</code>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tên loại BĐS:</label>
                                    <div><strong>{{ $propertyType->name }}</strong></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Icon:</label>
                                    <div>
                                        @if ($propertyType->icon)
                                            <i class="{{ $propertyType->icon }} text-primary fa-2x"></i>
                                            <span class="ms-2 text-muted">{{ $propertyType->icon }}</span>
                                        @else
                                            <i class="fas fa-building text-muted fa-2x"></i>
                                            <span class="ms-2 text-muted">Không có icon</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Trạng thái:</label>
                                    <div>
                                        @if ($propertyType->status == 1)
                                        <span class="badge bg-success">Hoạt động</span>
                                        @else
                                        <span class="badge bg-warning">Tạm ngưng</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả:</label>
                            <div class="bg-light p-3 rounded">
                                {{ $propertyType->description ?? 'Không có mô tả' }}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Số bất động sản:</label>
                                    <div>
                                        <span class="badge bg-info fs-6">{{ $propertyType->properties_count ?? 0 }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày tạo:</label>
                                    <div>{{ $propertyType->created_at->format('d/m/Y H:i:s') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Properties using this type --}}
                @if ($propertyType->properties_count > 0)
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-building me-2"></i>Bất động sản sử dụng loại này ({{ $propertyType->properties_count }})
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên BĐS</th>
                                        <th>Chủ sở hữu</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($propertyType->properties as $property)
                                    <tr>
                                        <td>{{ $property->id }}</td>
                                        <td>
                                            <strong>{{ $property->name }}</strong>
                                        </td>
                                        <td>
                                            {{ $property->owner_name }}
                                        </td>
                                        <td>
                                            @if ($property->status == 1)
                                            <span class="badge bg-success">Hoạt động</span>
                                            @else
                                            <span class="badge bg-warning">Tạm ngưng</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $property->created_at->format('d/m/Y') }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('staff.properties.show', $property->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Không có bất động sản nào</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                {{-- Card Thông tin thời gian --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>Thông tin thời gian
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Ngày tạo:</small>
                            <div>{{ $propertyType->created_at->format('d/m/Y H:i:s') }}</div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Cập nhật lần cuối:</small>
                            <div>{{ $propertyType->updated_at->format('d/m/Y H:i:s') }}</div>
                        </div>
                        @if ($propertyType->deleted_at)
                        <div class="mb-2">
                            <small class="text-muted">Đã xóa:</small>
                            <div class="text-danger">{{ $propertyType->deleted_at->format('d/m/Y H:i:s') }}</div>
                        </div>
                        @endif
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
                            $primaryActions = [];
                            
                            if($canManage) {
                                $primaryActions[] = [
                                    'type' => 'link',
                                    'variant' => 'primary',
                                    'label' => 'Chỉnh sửa',
                                    'icon' => 'fas fa-edit',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.property-types.edit', $propertyType->id),
                                    'class' => 'w-100'
                                ];
                                
                                if ($propertyType->properties_count == 0) {
                                    $primaryActions[] = [
                                        'type' => 'button',
                                        'variant' => 'danger',
                                        'label' => 'Xóa',
                                        'icon' => 'fas fa-trash',
                                        'iconPosition' => 'left',
                                        'onclick' => "deletePropertyType({$propertyType->id}, '" . addslashes($propertyType->name) . "')",
                                        'class' => 'w-100'
                                    ];
                                } else {
                                    $primaryActions[] = [
                                        'type' => 'button',
                                        'variant' => 'danger',
                                        'label' => 'Xóa (' . $propertyType->properties_count . ' BĐS)',
                                        'icon' => 'fas fa-trash',
                                        'iconPosition' => 'left',
                                        'onclick' => "alert('Không thể xóa vì đang được sử dụng bởi {$propertyType->properties_count} bất động sản.')",
                                        'class' => 'w-100',
                                        'disabled' => true
                                    ];
                                }
                            }
                            
                            $primaryActions[] = [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Quay lại',
                                'icon' => 'fas fa-arrow-left',
                                'iconPosition' => 'left',
                                'url' => route('staff.property-types.index'),
                                'class' => 'w-100'
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            if($canManage) {
                                if($propertyType->status != 1) {
                                    $statusActions[] = [
                                        'type' => 'button',
                                        'variant' => 'success',
                                        'label' => 'Chuyển về Hoạt động',
                                        'icon' => 'fas fa-check-circle',
                                        'onclick' => "updatePropertyTypeStatus(1)"
                                    ];
                                }
                                
                                if($propertyType->status != 0) {
                                    $statusActions[] = [
                                        'type' => 'button',
                                        'variant' => 'warning',
                                        'label' => 'Chuyển về Tạm ngưng',
                                        'icon' => 'fas fa-pause-circle',
                                        'onclick' => "updatePropertyTypeStatus(0)"
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
            </div>
        </div>
    </div>
</main>

@endsection

@push('scripts')
<script>
function deletePropertyType(id, name) {
    if (typeof window.Notify === 'undefined') {
        if (confirm(`Bạn có chắc chắn muốn xóa loại bất động sản "${name}"?`)) {
            deletePropertyTypeAction(id);
        }
    } else {
        Notify.confirmDelete(`loại bất động sản "${name}"`, () => {
            deletePropertyTypeAction(id);
        });
    }
}

function deletePropertyTypeAction(id) {
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

    fetch(`/staff/property-types/${id}`, {
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
                Notify.success(data.message || 'Đã xóa loại bất động sản thành công!', 'Đã xóa!');
            } else {
                alert('Đã xóa loại bất động sản thành công!');
            }
            setTimeout(() => {
                window.location.href = '{{ route("staff.property-types.index") }}';
            }, 1000);
        } else {
            if (typeof window.Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra khi xóa loại bất động sản', 'Lỗi!');
            } else {
                alert('Có lỗi xảy ra khi xóa loại bất động sản: ' + (data.message || 'Lỗi không xác định'));
            }
        }
    })
    .catch(error => {
        if (typeof window.Notify !== 'undefined') {
            Notify.error('Không thể xóa loại bất động sản: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        } else {
            alert('Không thể xóa loại bất động sản: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
        }
    })
    .finally(() => {
        if (window.Preloader) {
            window.Preloader.hide();
        }
    });
}

function updatePropertyTypeStatus(newStatus) {
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

    fetch(`{{ route('staff.property-types.update-status', $propertyType->id) }}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            status: newStatus
        })
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
                Notify.success(data.message || 'Đã cập nhật trạng thái thành công!', 'Thành công!');
            } else {
                alert('Đã cập nhật trạng thái thành công!');
            }
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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
            Notify.error('Không thể cập nhật trạng thái: ' + error.message, 'Lỗi hệ thống!');
        } else {
            alert('Không thể cập nhật trạng thái: ' + error.message);
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
