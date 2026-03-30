@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết lịch hẹn')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết lịch hẹn',
            'subtitle' => 'Thông tin chi tiết lịch hẹn #' . $viewing->id,
            'icon' => 'fas fa-calendar-check',
            'breadcrumbs' => [
                ['label' => 'Lịch hẹn', 'url' => route('staff.viewings.index')],
                ['label' => 'Lịch hẹn #' . $viewing->id, 'active' => true]
            ]
        ])

        <!-- Viewing Details -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin lịch hẹn
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">ID Lịch hẹn:</label>
                                <div class="text-muted">#{{ $viewing->id }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Trạng thái:</label>
                                <div>
                                    <span class="badge {{ $viewing->status_badge_class }}">
                                        {{ $viewing->status_text }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Thời gian hẹn:</label>
                                <div>
                                    <i class="fas fa-calendar me-1"></i>
                                    {{ $viewing->schedule_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Loại khách hàng:</label>
                                <div>
                                    <span class="customer-type-badge {{ $viewing->customer_type }}">
                                        <i class="fas {{ $viewing->getCustomerTypeIcon() }}"></i>
                                        {{ $viewing->customer_type_text }}
                                    </span>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            @if($viewing->customer_type === 'lead')
                                                <i class="fas fa-info-circle me-1"></i>
                                                Khách hàng tiềm năng (chưa có tài khoản)
                                            @else
                                                <i class="fas fa-user-check me-1"></i>
                                                Khách thuê (đã có tài khoản hệ thống)
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Bất động sản:</label>
                                <div>
                                    <i class="fas fa-building me-1"></i>
                                    {{ $viewing->property->name }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Phòng:</label>
                                <div>
                                    @if($viewing->unit)
                                        <i class="fas fa-door-open me-1"></i>
                                        {{ $viewing->unit->code }} - {{ $viewing->unit->name }}
                                    @else
                                        <span class="text-muted">Chưa chọn phòng</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Agent phụ trách:</label>
                                <div>
                                    @if($viewing->agent)
                                        <i class="fas fa-user-tie me-1"></i>
                                        {{ $viewing->agent->full_name }}
                                    @else
                                        <span class="text-muted">Chưa phân công</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold">Địa chỉ:</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 bg-light">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                                <strong>Địa chỉ cũ:</strong>
                                            </div>
                                            <div class="text-muted">
                                                @if($viewing->property && $viewing->property->location)
                                                    {{ $viewing->property->location->address }}
                                                @else
                                                    <span class="text-muted">Chưa có thông tin địa chỉ cũ</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 bg-light">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-map-pin me-2 text-warning"></i>
                                                <strong>Địa chỉ mới 2025:</strong>
                                            </div>
                                            <div class="text-muted">
                                                @if($viewing->property && $viewing->property->location2025)
                                                    {{ $viewing->property->location2025->address }}
                                                @else
                                                    <span class="text-muted">Chưa có thông tin địa chỉ mới</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($viewing->note)
                            <hr>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ghi chú:</label>
                                <div class="bg-light p-3 rounded">
                                    {{ $viewing->note }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Customer Info Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>Thông tin khách hàng
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="mb-0">{{ $viewing->customer_name }}</h6>
                            <small class="text-muted">
                                @if($viewing->customer_type === 'tenant' && $viewing->tenant)
                                    {{ $viewing->tenant->email }}
                                @else
                                    {{ $viewing->lead_phone }}
                                    @if($viewing->lead_email)
                                        • {{ $viewing->lead_email }}
                                    @endif
                                @endif
                            </small>
                        </div>

                        @if($viewing->customer_type === 'lead')
                            <div class="mb-3">
                                <label class="form-label fw-bold">Thông tin Lead:</label>
                                <ul class="list-unstyled mb-0">
                                    <li><strong>SĐT:</strong> {{ $viewing->lead_phone }}</li>
                                    @if($viewing->lead_email)
                                        <li><strong>Email:</strong> {{ $viewing->lead_email }}</li>
                                    @endif
                                    @if($viewing->lead)
                                        <li><strong>Lead ID:</strong> #{{ $viewing->lead->id }}</li>
                                    @endif
                                </ul>
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label fw-bold">Thông tin Khách thuê:</label>
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Email:</strong> {{ $viewing->tenant->email }}</li>
                                    <li><strong>ID:</strong> #{{ $viewing->tenant->id }}</li>
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Actions Card -->
                <div class="card shadow-sm">
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
                                    'url' => route('staff.viewings.edit', $viewing->id),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Quay lại',
                                    'icon' => 'fas fa-arrow-left',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.viewings.index'),
                                    'class' => 'w-100'
                                ]
                            ];
                            
                            // Thêm nút Xóa cho tất cả trạng thái
                            $primaryActions[] = [
                                'type' => 'button',
                                'variant' => 'danger',
                                'label' => 'Xóa',
                                'icon' => 'fas fa-trash-alt',
                                'iconPosition' => 'left',
                                'onclick' => "deleteViewing({$viewing->id}, '" . addslashes($viewing->customer_name) . "')",
                                'class' => 'w-100'
                            ];
                            
                            // Additional actions: Tạo đặt cọc, Tạo hợp đồng (chỉ hiển thị khi có property và unit)
                            $additionalActions = [];
                            if ($viewing->property_id && $viewing->unit_id) {
                                // Build URL parameters for booking deposit
                                $bookingDepositParams = [
                                    'property_id' => $viewing->property_id,
                                    'unit_id' => $viewing->unit_id,
                                    'viewing_id' => $viewing->id
                                ];
                                // Add lead_id if viewing has lead
                                if ($viewing->lead_id) {
                                    $bookingDepositParams['lead_id'] = $viewing->lead_id;
                                }
                                
                                // Build URL parameters for lease
                                $leaseParams = [
                                    'property_id' => $viewing->property_id,
                                    'unit_id' => $viewing->unit_id,
                                    'viewing_id' => $viewing->id
                                ];
                                // Add lead_id if viewing has lead
                                if ($viewing->lead_id) {
                                    $leaseParams['lead_id'] = $viewing->lead_id;
                                }
                                
                                $additionalActions[] = [
                                    'type' => 'link',
                                    'variant' => 'success',
                                    'label' => 'Tạo đặt cọc',
                                    'icon' => 'fas fa-hand-holding-usd',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.booking-deposits.create', $bookingDepositParams),
                                    'class' => 'w-100'
                                ];
                                
                                $additionalActions[] = [
                                    'type' => 'link',
                                    'variant' => 'info',
                                    'label' => 'Tạo hợp đồng',
                                    'icon' => 'fas fa-file-contract',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.leases.create', $leaseParams),
                                    'class' => 'w-100'
                                ];
                            }
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            if($viewing->status === 'requested') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Xác nhận',
                                    'icon' => 'fas fa-check',
                                    'onclick' => "confirmViewing({$viewing->id})"
                                ];
                            }
                            
                            if(in_array($viewing->status, ['requested', 'confirmed'])) {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'warning',
                                    'label' => 'Hủy lịch hẹn',
                                    'icon' => 'fas fa-times',
                                    'onclick' => "cancelViewing({$viewing->id})"
                                ];
                            }
                            
                            if($viewing->status === 'confirmed') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'info',
                                    'label' => 'Hoàn thành',
                                    'icon' => 'fas fa-check-circle',
                                    'onclick' => "markDoneViewing({$viewing->id})"
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
                            
                            {{-- Additional Actions: Tạo đặt cọc, Tạo hợp đồng (vertical) --}}
                            @if(count($additionalActions) > 0)
                                <hr class="my-2">
                                @include('staff.components.action-buttons', [
                                    'layout' => 'vertical',
                                    'size' => 'sm',
                                    'actions' => $additionalActions
                                ])
                            @endif
                            
                            {{-- Status Actions: Dropdown cho các nút chuyển trạng thái --}}
                            @if(count($statusActions) > 0)
                                <hr class="my-2">
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

<!-- Mark Done Modal -->
<div class="modal fade" id="markDoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đánh dấu hoàn thành</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="mark-done-form" method="POST" action="{{ route('staff.viewings.mark-done', $viewing->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="result_note" class="form-label">Ghi chú kết quả</label>
                        <textarea class="form-control" id="result_note" name="result_note" rows="3" placeholder="Ghi chú về kết quả lịch hẹn..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Hoàn thành</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmViewing(id) {
    Notify.confirm({
        title: 'Xác nhận lịch hẹn',
        message: 'Bạn có chắc chắn muốn xác nhận lịch hẹn này?',
        type: 'info',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('{{ route("staff.viewings.confirm", ":id") }}'.replace(':id', id), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    
                    if (!response.ok) {
                        throw new Error(data.message || 'Có lỗi xảy ra');
                    }
                    
                    if (data.success) {
                        Notify.success(data.message || 'Đã xác nhận lịch hẹn thành công!', 'Thành công!');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                    }
                } else {
                    // If not JSON, it might be a redirect or HTML error
                    const text = await response.text();
                    throw new Error('Server trả về response không phải JSON. Vui lòng thử lại.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Không thể xác nhận lịch hẹn: ' + error.message, 'Lỗi hệ thống!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    });
}

function cancelViewing(id) {
    Notify.confirm({
        title: 'Hủy lịch hẹn',
        message: 'Bạn có chắc chắn muốn hủy lịch hẹn này?',
        type: 'warning',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('{{ route("staff.viewings.cancel", ":id") }}'.replace(':id', id), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    
                    if (!response.ok) {
                        throw new Error(data.message || 'Có lỗi xảy ra');
                    }
                    
                    if (data.success) {
                        Notify.success(data.message || 'Đã hủy lịch hẹn thành công!', 'Thành công!');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                    }
                } else {
                    // If not JSON, it might be a redirect or HTML error
                    const text = await response.text();
                    throw new Error('Server trả về response không phải JSON. Vui lòng thử lại.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Không thể hủy lịch hẹn: ' + error.message, 'Lỗi hệ thống!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    });
}

function markDoneViewing(id) {
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('markDoneModal'));
    modal.show();
    
    // Handle form submission
    const form = document.getElementById('mark-done-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    
                    if (!response.ok) {
                        throw new Error(data.message || 'Có lỗi xảy ra');
                    }
                    
                    if (data.success) {
                        Notify.success(data.message || 'Đã đánh dấu hoàn thành thành công!', 'Thành công!');
                        modal.hide();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                    }
                } else {
                    // If not JSON, it might be a redirect or HTML error
                    const text = await response.text();
                    throw new Error('Server trả về response không phải JSON. Vui lòng thử lại.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Không thể đánh dấu hoàn thành: ' + error.message, 'Lỗi hệ thống!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }, { once: true }); // Use once: true to prevent multiple event listeners
    }
}

function deleteViewing(id, name) {
    if (typeof Notify !== 'undefined' && Notify.confirmDelete) {
        Notify.confirmDelete(`lịch hẹn "${name}"`, () => {
            if (window.Preloader) {
                window.Preloader.show();
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
                if (window.Preloader) {
                    window.Preloader.hide();
                }
                return;
            }
            fetch(`/staff/viewings/${id}`, {
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
                        window.location.href = '{{ route("staff.viewings.index") }}';
                    }, 1000);
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Không thể xóa lịch hẹn: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        });
    } else {
        // Fallback if Notify is not available
        if (confirm('Bạn có chắc chắn muốn xóa lịch hẹn "' + name + '"?')) {
            if (window.Preloader) {
                window.Preloader.show();
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                alert('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.');
                if (window.Preloader) {
                    window.Preloader.hide();
                }
                return;
            }
            fetch(`/staff/viewings/${id}`, {
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
                    alert('Lịch hẹn đã được xóa thành công!');
                    setTimeout(() => {
                        window.location.href = '{{ route("staff.viewings.index") }}';
                    }, 1000);
                } else {
                    alert('Có lỗi xảy ra: ' + (data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Không thể xóa lịch hẹn: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    }
}
</script>
@endpush

@push('styles')
<style>
.customer-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.customer-type-badge.lead {
    background-color: #fff3e0;
    color: #f57c00;
}

.customer-type-badge.tenant {
    background-color: #e8f5e8;
    color: #2e7d32;
}
</style>
@endpush
