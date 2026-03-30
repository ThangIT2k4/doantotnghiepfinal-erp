@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Chính sách Hoa hồng')

@section('content')
<main class="main-content">
<div class="container-fluid">
    <!-- Page Header -->
    @include('staff.components.show-page-header', [
        'title' => 'Chi tiết Chính sách Hoa hồng',
        'subtitle' => $commissionPolicy->title,
        'icon' => 'fas fa-file-contract',
        'breadcrumbs' => [
            ['label' => 'Chính sách Hoa hồng', 'url' => route('staff.commission-policies.index')],
            ['label' => $commissionPolicy->title, 'active' => true]
        ]
    ])

    <div class="row">
        <!-- Policy Details -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin Chính sách</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Mã chính sách:</strong></td>
                                    <td><span class="badge bg-secondary">{{ $commissionPolicy->code }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Tên chính sách:</strong></td>
                                    <td>{{ $commissionPolicy->title }}</td>
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
                                        <span class="badge bg-info">{{ $triggerLabels[$commissionPolicy->trigger_event] ?? $commissionPolicy->trigger_event }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Cơ sở tính toán:</strong></td>
                                    <td>
                                        @if($commissionPolicy->basis == 'cash')
                                            <span class="badge bg-success">Tiền mặt</span>
                                        @else
                                            <span class="badge bg-warning">Dồn tích</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Loại tính toán:</strong></td>
                                    <td>
                                        @php
                                            $calcLabels = [
                                                'percent' => 'Phần trăm',
                                                'flat' => 'Số tiền cố định',
                                                'tiered' => 'Bậc thang'
                                            ];
                                        @endphp
                                        {{ $calcLabels[$commissionPolicy->calc_type] ?? $commissionPolicy->calc_type }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Giá trị:</strong></td>
                                    <td>
                                        @if($commissionPolicy->calc_type == 'percent')
                                            <span class="text-primary"><strong>{{ $commissionPolicy->percent_value }}%</strong></span>
                                        @elseif($commissionPolicy->calc_type == 'flat')
                                            <span class="text-primary"><strong>{{ number_format($commissionPolicy->flat_amount, 0, ',', '.') }} VND</strong></span>
                                        @else
                                            <span class="text-primary"><strong>Bậc thang</strong></span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Trạng thái:</strong></td>
                                    <td>
                                        @if($commissionPolicy->active)
                                            <span class="badge bg-success">Hoạt động</span>
                                        @else
                                            <span class="badge bg-secondary">Không hoạt động</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày tạo:</strong></td>
                                    <td>{{ $commissionPolicy->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($commissionPolicy->apply_limit_months || $commissionPolicy->min_amount || $commissionPolicy->cap_amount)
                    <hr>
                    <h6 class="font-weight-bold text-primary">Điều kiện áp dụng</h6>
                    <div class="row">
                        @if($commissionPolicy->apply_limit_months)
                        <div class="col-md-4">
                            <strong>Giới hạn tháng:</strong> {{ $commissionPolicy->apply_limit_months }} tháng
                        </div>
                        @endif
                        @if($commissionPolicy->min_amount)
                        <div class="col-md-4">
                            <strong>Số tiền tối thiểu:</strong> {{ number_format($commissionPolicy->min_amount, 0, ',', '.') }} VND
                        </div>
                        @endif
                        @if($commissionPolicy->cap_amount)
                        <div class="col-md-4">
                            <strong>Số tiền tối đa:</strong> {{ number_format($commissionPolicy->cap_amount, 0, ',', '.') }} VND
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Recent Events -->
            @if(isset($events) && $events->count() > 0)
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Sự kiện gần đây</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nhân viên</th>
                                    <th>Ngày xảy ra</th>
                                    <th>Hoa hồng</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($events as $event)
                                <tr>
                                    <td><span class="badge bg-secondary">#{{ $event->id }}</span></td>
                                    <td>
                                        @if($event->agent)
                                            {{ $event->agent->full_name ?? $event->agent->email }}
                                        @else
                                            <span class="text-muted">Chưa gán</span>
                                        @endif
                                    </td>
                                    <td>{{ $event->occurred_at->format('d/m/Y H:i') }}</td>
                                    <td><strong class="text-success">{{ number_format($event->commission_total, 0, ',', '.') }} VND</strong></td>
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
                                        <span class="badge bg-{{ $statusColors[$event->status] ?? 'secondary' }}">
                                            {{ $statusLabels[$event->status] ?? $event->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('staff.commission-events.show', $event->id) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($events->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $events->links('vendor.pagination.custom', [
                                'tableContainerId' => 'commission-events-list-container',
                                'htmxIndicator' => '#htmx-loading-index-filters-form'
                            ]) }}
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Statistics and Actions -->
        <div class="col-lg-4">
            <!-- Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thống kê</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary">{{ isset($events) ? $events->total() : ($commissionPolicy->events->count() ?? 0) }}</h4>
                                <p class="mb-0 text-muted">Sự kiện</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success">
                                {{ number_format(isset($policyStats) ? $policyStats['total_commission'] : ($commissionPolicy->events->sum('commission_total') ?? 0), 0, ',', '.') }}
                            </h4>
                            <p class="mb-0 text-muted">Tổng hoa hồng (VND)</p>
                        </div>
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
                                'url' => route('staff.commission-policies.edit', $commissionPolicy->id),
                                'class' => 'w-100'
                            ],
                            [
                                'type' => 'button',
                                'variant' => 'danger',
                                'label' => 'Xóa',
                                'icon' => 'fas fa-trash-alt',
                                'iconPosition' => 'left',
                                'onclick' => "deletePolicy({$commissionPolicy->id}, '" . addslashes($commissionPolicy->title) . "')",
                                'class' => 'w-100'
                            ],
                            [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Quay lại',
                                'icon' => 'fas fa-arrow-left',
                                'iconPosition' => 'left',
                                'url' => route('staff.commission-policies.index'),
                                'class' => 'w-100'
                            ]
                        ];
                        
                        // Status Actions: Dropdown cho các nút chuyển trạng thái
                        $statusActions = [];
                        
                        // Có thể kích hoạt nếu đang không hoạt động
                        if (!$commissionPolicy->active) {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'success',
                                'label' => 'Kích hoạt',
                                'icon' => 'fas fa-check',
                                'iconPosition' => 'left',
                                'onclick' => "togglePolicyStatus({$commissionPolicy->id}, true)",
                                'class' => 'w-100'
                            ];
                        }
                        
                        // Có thể tạm ngưng nếu đang hoạt động
                        if ($commissionPolicy->active) {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'warning',
                                'label' => 'Tạm ngưng',
                                'icon' => 'fas fa-pause',
                                'iconPosition' => 'left',
                                'onclick' => "togglePolicyStatus({$commissionPolicy->id}, false)",
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

// Delete policy function
function deletePolicy(policyId, policyTitle) {
    Notify.confirmDelete(`chính sách hoa hồng "${policyTitle}"`, () => {
        // Show loading notification
        const loadingToast = Notify.toast({
            title: 'Đang xóa...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });

        fetch(`/staff/commission-policies/${policyId}`, {
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
                Notify.success(data.message || 'Xóa chính sách hoa hồng thành công!', 'Thành công!');
                setTimeout(() => {
                    window.location.href = '{{ route("staff.commission-policies.index") }}';
                }, 1500);
            } else {
                Notify.error(data.message || 'Không thể xóa chính sách hoa hồng.', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Đã xảy ra lỗi khi xóa chính sách hoa hồng. Vui lòng thử lại.', 'Lỗi hệ thống!');
        });
    });
}

// Toggle policy status function
function togglePolicyStatus(policyId, activate) {
    const action = activate ? 'kích hoạt' : 'tạm ngưng';
    const newStatus = activate ? 1 : 0;
    
    Notify.confirm({
        title: 'Chuyển trạng thái chính sách',
        message: `Bạn có chắc chắn muốn ${action} chính sách hoa hồng này?`,
        type: activate ? 'success' : 'warning',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: () => {
            // Show loading notification
            const loadingToast = Notify.toast({
                title: 'Đang cập nhật...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });

            fetch(`/staff/commission-policies/${policyId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    active: newStatus
                })
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
                    const actionText = newStatus ? 'kích hoạt' : 'tạm ngưng';
                    Notify.success(`Đã ${actionText} chính sách hoa hồng thành công!`, 'Thành công!');
                    // Reload page after a short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    Notify.error(data.message || 'Không thể cập nhật trạng thái chính sách hoa hồng.', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Hide loading notification
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                
                Notify.error('Đã xảy ra lỗi khi cập nhật trạng thái chính sách hoa hồng. Vui lòng thử lại.', 'Lỗi hệ thống!');
            });
        }
    });
}
</script>
@endpush
