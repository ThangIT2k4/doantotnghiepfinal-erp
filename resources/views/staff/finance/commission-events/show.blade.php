@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Sự kiện Hoa hồng')

@section('content')
<main class="main-content">
<div class="container-fluid">
    <!-- Page Header -->
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết Sự kiện Hoa hồng',
            'subtitle' => '#' . $commissionEvent->id . ($commissionEvent->policy ? ' - ' . $commissionEvent->policy->title : ''),
            'icon' => 'fas fa-chart-line',
            'breadcrumbs' => [
                ['label' => 'Sự kiện Hoa hồng', 'url' => route('staff.commission-events.index')],
                ['label' => '#' . $commissionEvent->id, 'active' => true]
            ]
        ])

    <div class="row">
        <!-- Event Details -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin Sự kiện</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>ID Sự kiện:</strong></td>
                                    <td><span class="badge bg-secondary">#{{ $commissionEvent->id }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Nhân viên:</strong></td>
                                    <td>
                                        @if($commissionEvent->agent)
                                            <div>
                                                <strong>{{ $commissionEvent->agent->full_name }}</strong>
                                                <br><small class="text-muted">{{ $commissionEvent->agent->email }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">Chưa gán</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Người nhận hoa hồng:</strong></td>
                                    <td>
                                        @if($commissionEvent->user)
                                            <div>
                                                <strong>{{ $commissionEvent->user->full_name }}</strong>
                                                <br><small class="text-muted">{{ $commissionEvent->user->email }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">Chưa gán</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Chính sách:</strong></td>
                                    <td>
                                        @if($commissionEvent->policy)
                                            <div>
                                                <strong>{{ $commissionEvent->policy->title }}</strong>
                                                <br><small class="text-muted">{{ $commissionEvent->policy->code ?? 'N/A' }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">Chưa gán chính sách</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Sự kiện kích hoạt:</strong></td>
                                    <td>
                                        @php
                                            $triggerLabels = [
                                                'deposit_paid' => 'Thanh toán cọc',
                                                'lease_signed' => 'Ký hợp đồng',
                                                'invoice_paid' => 'Thanh toán hóa đơn',
                                                'viewing_done' => 'Hoàn thành xem phòng',
                                                'listing_published' => 'Đăng tin'
                                            ];
                                        @endphp
                                        <span class="badge bg-info">{{ $triggerLabels[$commissionEvent->trigger_event] ?? $commissionEvent->trigger_event }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Ngày xảy ra:</strong></td>
                                    <td>{{ $commissionEvent->occurred_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Trạng thái:</strong></td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'approved' => 'info',
                                                'paid' => 'success',
                                                'reversed' => 'danger',
                                                'cancelled' => 'secondary'
                                            ];
                                            $statusLabels = [
                                                'pending' => 'Chờ duyệt',
                                                'approved' => 'Đã duyệt',
                                                'paid' => 'Đã thanh toán',
                                                'reversed' => 'Đã hoàn',
                                                'cancelled' => 'Đã hủy'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $statusColors[$commissionEvent->status] }}">
                                            {{ $statusLabels[$commissionEvent->status] }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày tạo:</strong></td>
                                    <td>{{ $commissionEvent->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Cập nhật cuối:</strong></td>
                                    <td>{{ $commissionEvent->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Records -->
            @if($commissionEvent->lease || $commissionEvent->unit)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Bản ghi liên quan</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($commissionEvent->lease)
                        <div class="col-md-6">
                            <h6>Hợp đồng thuê</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td><a href="{{ route('staff.leases.show', $commissionEvent->lease->id) }}">#{{ $commissionEvent->lease->id }}</a></td>
                                </tr>
                                <tr>
                                    <td><strong>Khách thuê:</strong></td>
                                    <td>{{ $commissionEvent->lease->tenant->full_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phòng:</strong></td>
                                    <td>{{ $commissionEvent->lease->unit->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày bắt đầu:</strong></td>
                                    <td>{{ $commissionEvent->lease->start_date ? $commissionEvent->lease->start_date->format('d/m/Y') : 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        @endif
                        @if($commissionEvent->unit)
                        <div class="col-md-6">
                            <h6>Phòng/Đơn vị</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td><strong>Tên:</strong></td>
                                    <td>{{ $commissionEvent->unit->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Bất động sản:</strong></td>
                                    <td>{{ $commissionEvent->unit->property->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Giá thuê:</strong></td>
                                    <td>{{ number_format($commissionEvent->unit->rent_price, 0, ',', '.') }} VND/tháng</td>
                                </tr>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Financial Information -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin tài chính</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-12 mb-3">
                            <h4 class="text-primary">{{ number_format($commissionEvent->amount_base, 0, ',', '.') }}</h4>
                            <p class="mb-0 text-muted">Số tiền gốc (VND)</p>
                        </div>
                        <div class="col-12 mb-3">
                            <h4 class="text-success">{{ number_format($commissionEvent->commission_total, 0, ',', '.') }}</h4>
                            <p class="mb-0 text-muted">Hoa hồng (VND)</p>
                        </div>
                        @if($commissionEvent->commission_total > 0 && $commissionEvent->amount_base > 0)
                        <div class="col-12">
                            <h4 class="text-info">{{ number_format(($commissionEvent->commission_total / $commissionEvent->amount_base) * 100, 2) }}%</h4>
                            <p class="mb-0 text-muted">Tỷ lệ hoa hồng</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>


            <!-- Actions -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cogs me-2"></i>Thao tác
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        // Primary Actions: Sửa, Xóa, Quay lại
                        $primaryActions = [
                            [
                                'type' => 'link',
                                'variant' => 'primary',
                                'label' => 'Sửa',
                                'icon' => 'fas fa-edit',
                                'iconPosition' => 'left',
                                'url' => route('staff.commission-events.edit', $commissionEvent->id),
                                'class' => 'w-100'
                            ],
                            [
                                'type' => 'button',
                                'variant' => 'danger',
                                'label' => 'Xóa',
                                'icon' => 'fas fa-trash-alt',
                                'iconPosition' => 'left',
                                'onclick' => "deleteEvent({$commissionEvent->id})",
                                'class' => 'w-100'
                            ],
                            [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Quay lại',
                                'icon' => 'fas fa-arrow-left',
                                'iconPosition' => 'left',
                                'url' => route('staff.commission-events.index'),
                                'class' => 'w-100'
                            ]
                        ];
                        
                        // Status Actions: Dropdown cho các nút chuyển trạng thái
                        $statusActions = [];
                        
                        // Có thể chuyển từ pending -> approved
                        if ($commissionEvent->status == 'pending') {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'success',
                                'label' => 'Duyệt sự kiện',
                                'icon' => 'fas fa-check',
                                'iconPosition' => 'left',
                                'onclick' => "approveEvent({$commissionEvent->id})",
                                'class' => 'w-100'
                            ];
                        }
                        
                        // Có thể chuyển từ approved -> paid
                        if ($commissionEvent->status == 'approved') {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'primary',
                                'label' => 'Đánh dấu đã thanh toán',
                                'icon' => 'fas fa-money-bill',
                                'iconPosition' => 'left',
                                'onclick' => "markAsPaid({$commissionEvent->id})",
                                'class' => 'w-100'
                            ];
                        }
                        
                        // Có thể chuyển từ paid -> reversed (hoàn)
                        if ($commissionEvent->status == 'paid') {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'warning',
                                'label' => 'Hoàn hoa hồng',
                                'icon' => 'fas fa-undo',
                                'iconPosition' => 'left',
                                'onclick' => "updateEventStatus({$commissionEvent->id}, 'reversed')",
                                'class' => 'w-100'
                            ];
                        }
                        
                        // Có thể hủy nếu chưa paid
                        if (in_array($commissionEvent->status, ['pending', 'approved'])) {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'danger',
                                'label' => 'Hủy sự kiện',
                                'icon' => 'fas fa-times',
                                'iconPosition' => 'left',
                                'onclick' => "updateEventStatus({$commissionEvent->id}, 'cancelled')",
                                'class' => 'w-100'
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
        </div>
    </div>
</div>
</main>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show session messages
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

// Delete event function with enhanced notifications
function deleteEvent(eventId) {
    Notify.confirmDelete('Bạn có chắc chắn muốn xóa sự kiện hoa hồng này?', () => {
        // Show loading notification
        const loadingToast = Notify.toast({
            title: 'Đang xóa...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });

        fetch(`/staff/commission-events/${eventId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            // Hide loading notification
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success('Xóa sự kiện hoa hồng thành công!', 'Thành công!');
                // Redirect to index page after a short delay
                setTimeout(() => {
                    window.location.href = '{{ route('staff.commission-events.index') }}';
                }, 1500);
            } else {
                Notify.error(data.message || 'Không thể xóa sự kiện hoa hồng. Vui lòng thử lại.', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            Notify.error('Đã xảy ra lỗi khi xóa sự kiện hoa hồng. Vui lòng kiểm tra kết nối và thử lại.', 'Lỗi hệ thống!');
        });
    });
}

// Approve event function with enhanced notifications
function approveEvent(eventId) {
    Notify.confirm({
        title: 'Duyệt sự kiện hoa hồng',
        message: 'Bạn có chắc chắn muốn duyệt sự kiện hoa hồng này?',
        details: 'Sau khi duyệt, sự kiện hoa hồng sẽ được chuyển sang trạng thái đã duyệt.',
        type: 'success',
        confirmText: 'Duyệt',
        onConfirm: () => {
            // Show loading toast
            const loadingToast = Notify.toast({
                title: 'Đang duyệt...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });

            fetch(`/staff/commission-events/${eventId}/approve`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }

                if (data.success) {
                    Notify.success(data.message || 'Duyệt sự kiện hoa hồng thành công!', 'Thành công!');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    Notify.error(data.message || 'Không thể duyệt sự kiện hoa hồng.', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                
                Notify.error('Đã xảy ra lỗi khi duyệt sự kiện hoa hồng. Vui lòng thử lại.', 'Lỗi hệ thống!');
            });
        }
    });
}

// Mark as paid function with enhanced notifications
function markAsPaid(eventId) {
    Notify.confirm({
        title: 'Đánh dấu đã thanh toán',
        message: 'Bạn có chắc chắn muốn đánh dấu sự kiện hoa hồng này là đã thanh toán?',
        details: 'Sau khi đánh dấu, sự kiện hoa hồng sẽ được chuyển sang trạng thái đã thanh toán.',
        type: 'success',
        confirmText: 'Xác nhận',
        onConfirm: () => {
            // Show loading toast
            const loadingToast = Notify.toast({
                title: 'Đang cập nhật...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });

            fetch(`/staff/commission-events/${eventId}/mark-as-paid`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }

                if (data.success) {
                    Notify.success(data.message || 'Đánh dấu đã thanh toán thành công!', 'Thành công!');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    Notify.error(data.message || 'Không thể đánh dấu đã thanh toán.', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                
                Notify.error('Đã xảy ra lỗi khi đánh dấu đã thanh toán. Vui lòng thử lại.', 'Lỗi hệ thống!');
            });
        }
    });
}

// Update event status function (for reversed, cancelled)
function updateEventStatus(eventId, status) {
    const statusLabels = {
        'reversed': 'Hoàn hoa hồng',
        'cancelled': 'Hủy sự kiện'
    };
    
    const statusMessages = {
        'reversed': 'Bạn có chắc chắn muốn hoàn hoa hồng này?',
        'cancelled': 'Bạn có chắc chắn muốn hủy sự kiện hoa hồng này?'
    };
    
    Notify.confirm({
        title: statusLabels[status] || 'Chuyển trạng thái',
        message: statusMessages[status] || `Bạn có chắc chắn muốn chuyển trạng thái sang "${status}"?`,
        details: 'Hành động này không thể hoàn tác.',
        type: status === 'cancelled' ? 'warning' : 'info',
        confirmText: 'Xác nhận',
        onConfirm: () => {
            // Show loading toast
    const loadingToast = Notify.toast({
                title: 'Đang cập nhật...',
        message: 'Vui lòng chờ trong giây lát',
        type: 'info',
        duration: 0
    });

            fetch(`/staff/commission-events/${eventId}/update-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }

                if (data.success) {
                    Notify.success(data.message || 'Cập nhật trạng thái thành công!', 'Thành công!');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    Notify.error(data.message || 'Không thể cập nhật trạng thái.', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                
                Notify.error('Đã xảy ra lỗi khi cập nhật trạng thái. Vui lòng thử lại.', 'Lỗi hệ thống!');
            });
        }
    });
}

</script>
@endpush
