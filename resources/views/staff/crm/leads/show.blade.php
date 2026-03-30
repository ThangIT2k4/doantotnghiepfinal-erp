@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Lead')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết Lead',
            'subtitle' => 'Thông tin chi tiết về khách hàng tiềm năng: ' . $lead->name,
            'icon' => 'fas fa-user',
            'breadcrumbs' => [
                ['label' => 'Leads', 'url' => route('staff.leads.index')],
                ['label' => $lead->name, 'active' => true]
            ]
        ])

        <!-- Lead Details -->
        <div class="row">
            <!-- Lead Information -->
            <div class="col-lg-8">
                <!-- Tabs Navigation -->
                @include('staff.components.tab-navigation', [
                    'tabs' => [
                        'basic-info' => [
                            'label' => 'Thông tin chi tiết',
                            'icon' => 'fas fa-info-circle',
                            'color' => 'primary'
                        ],
                        'viewings' => [
                            'label' => 'Lịch hẹn xem phòng',
                            'icon' => 'fas fa-calendar-check',
                            'color' => 'info',
                            'badge' => $viewings->count()
                        ],
                        'booking-deposits' => [
                            'label' => 'Đặt cọc',
                            'icon' => 'fas fa-money-bill-wave',
                            'color' => 'success',
                            'badge' => $bookingDeposits->count()
                        ]
                    ],
                    'storageKey' => 'leadTabStates',
                    'defaultVisible' => ['basic-info']
                ])

                <!-- Basic Information -->
                <div class="card shadow-sm mt-4 tab-content" id="tab-basic-info">
                    <div class="card-header bg-primary text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin chi tiết
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">ID Lead:</label>
                                    <div class="p-2 bg-light rounded">
                                        <span class="badge bg-secondary">#{{ $lead->id }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Trạng thái:</label>
                                    <div class="p-2 bg-light rounded">
                                        @include('staff.components.status-badge', [
                                            'status' => $lead->status,
                                            'type' => 'lead'
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tên khách hàng:</label>
                                    <div class="p-2 bg-light rounded">{{ $lead->name }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Số điện thoại:</label>
                                    <div class="p-2 bg-light rounded">
                                        <a href="tel:{{ $lead->phone }}" class="text-decoration-none">
                                            <i class="fas fa-phone me-1"></i>{{ $lead->phone }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email:</label>
                                    <div class="p-2 bg-light rounded">
                                        @if($lead->email)
                                            <a href="mailto:{{ $lead->email }}" class="text-decoration-none">
                                                <i class="fas fa-envelope me-1"></i>{{ $lead->email }}
                                            </a>
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Thành phố mong muốn:</label>
                                    <div class="p-2 bg-light rounded">
                                        @if($lead->desired_city)
                                            <i class="fas fa-map-marker-alt me-1"></i>{{ $lead->desired_city }}
                                        @else
                                            <span class="text-muted">Chưa xác định</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nguồn:</label>
                                    <div class="p-2 bg-light rounded">
                                        <span class="badge bg-info">{{ $lead->source }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngân sách:</label>
                                    <div class="p-2 bg-light rounded">
                                        @if($lead->budget_min || $lead->budget_max)
                                            @if($lead->budget_min && $lead->budget_max)
                                                {{ number_format($lead->budget_min) }} - {{ number_format($lead->budget_max) }} VNĐ
                                            @elseif($lead->budget_min)
                                                Từ {{ number_format($lead->budget_min) }} VNĐ
                                            @else
                                                Đến {{ number_format($lead->budget_max) }} VNĐ
                                            @endif
                                        @else
                                            <span class="text-muted">Chưa xác định</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày tạo:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ $lead->created_at->format('d/m/Y H:i:s') }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Cập nhật cuối:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ $lead->updated_at->format('d/m/Y H:i:s') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($lead->note)
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ghi chú:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        {{ $lead->note }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($lead->deleted_at)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày xóa:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-trash me-1"></i>
                                        {{ $lead->deleted_at->format('d/m/Y H:i:s') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Viewings -->
                <div class="card shadow-sm mt-4 tab-content" id="tab-viewings" style="display: none;">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Lịch hẹn xem phòng
                            @if($viewings->count() > 0)
                                <span class="badge bg-light text-dark ms-2">{{ $viewings->count() }}</span>
                            @endif
                        </h6>
                        @if($viewings->count() > 0)
                            <a href="{{ route('staff.viewings.index', ['lead_id' => $lead->id]) }}" class="btn btn-sm btn-light">
                                <i class="fas fa-list me-1"></i>Xem tất cả
                            </a>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($viewings->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Ngày giờ</th>
                                            <th>Bất động sản</th>
                                            <th>Phòng</th>
                                            <th>Agent</th>
                                            <th>Trạng thái</th>
                                            <th>Ghi chú</th>
                                            <th style="width: 80px;">Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($viewings->take(10) as $viewing)
                                            <tr>
                                                <td>
                                                    <div class="text-nowrap">
                                                        {{ $viewing->schedule_at ? $viewing->schedule_at->format('d/m/Y') : 'N/A' }}
                                                        <br><small class="text-muted">
                                                            {{ $viewing->schedule_at ? $viewing->schedule_at->format('H:i') : '' }}
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>{{ $viewing->unit && $viewing->unit->property ? $viewing->unit->property->name : 'N/A' }}</td>
                                                <td>
                                                    @if($viewing->unit)
                                                        {{ $viewing->unit->unit_number ?? $viewing->unit->code ?? 'N/A' }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($viewing->agent)
                                                        {{ $viewing->agent->full_name ?? $viewing->agent->name ?? 'N/A' }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @include('staff.components.status-badge', [
                                                        'status' => $viewing->status,
                                                        'type' => 'viewing'
                                                    ])
                                                </td>
                                                <td>
                                                    @if($viewing->note)
                                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $viewing->note }}">
                                                            {{ $viewing->note }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted">Không có</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('staff.viewings.show', $viewing->id) }}" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if($viewings->count() > 10)
                                    <div class="text-center mt-3">
                                        <a href="{{ route('staff.viewings.index', ['lead_id' => $lead->id]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-list me-1"></i>Xem tất cả {{ $viewings->count() }} lịch hẹn
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có lịch hẹn xem phòng nào</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Booking Deposits -->
                <div class="card shadow-sm mt-4 tab-content" id="tab-booking-deposits" style="display: none;">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-money-bill-wave me-2"></i>Đặt cọc
                            @if($bookingDeposits->count() > 0)
                                <span class="badge bg-light text-dark ms-2">{{ $bookingDeposits->count() }}</span>
                            @endif
                        </h6>
                        @if($bookingDeposits->count() > 0)
                            <a href="{{ route('staff.booking-deposits.index', ['lead_id' => $lead->id]) }}" class="btn btn-sm btn-light">
                                <i class="fas fa-list me-1"></i>Xem tất cả
                            </a>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($bookingDeposits->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Số tham chiếu</th>
                                            <th>Bất động sản</th>
                                            <th>Phòng</th>
                                            <th>Số tiền</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày tạo</th>
                                            <th style="width: 80px;">Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($bookingDeposits->take(10) as $deposit)
                                            <tr>
                                                <td>
                                                    <code class="text-primary">{{ $deposit->reference_number }}</code>
                                                </td>
                                                <td>
                                                    @if($deposit->unit && $deposit->unit->property)
                                                        {{ $deposit->unit->property->name }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($deposit->unit)
                                                        {{ $deposit->unit->unit_number ?? $deposit->unit->code ?? 'N/A' }}
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <strong class="text-success">{{ number_format($deposit->amount) }} VNĐ</strong>
                                                </td>
                                                <td>
                                                    @include('staff.components.status-badge', [
                                                        'status' => $deposit->status ?? $deposit->payment_status,
                                                        'type' => 'booking-deposit'
                                                    ])
                                                </td>
                                                <td>{{ $deposit->created_at->format('d/m/Y H:i') }}</td>
                                                <td>
                                                    <a href="{{ route('staff.booking-deposits.show', $deposit->id) }}" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if($bookingDeposits->count() > 10)
                                    <div class="text-center mt-3">
                                        <a href="{{ route('staff.booking-deposits.index', ['lead_id' => $lead->id]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-list me-1"></i>Xem tất cả {{ $bookingDeposits->count() }} đặt cọc
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có đặt cọc nào</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Lead Profile Card -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="mb-3">{{ $lead->name }}</h4>
                        
                        <div class="mb-3">
                            <p class="mb-1">
                                <i class="fas fa-phone me-2 text-muted"></i>
                                <a href="tel:{{ $lead->phone }}" class="text-decoration-none">{{ $lead->phone }}</a>
                            </p>
                            @if($lead->email)
                                <p class="mb-1">
                                    <i class="fas fa-envelope me-2 text-muted"></i>
                                    <a href="mailto:{{ $lead->email }}" class="text-decoration-none">{{ $lead->email }}</a>
                                </p>
                            @endif
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="mb-3">
                            <label class="form-label fw-bold mb-1">Trạng thái:</label>
                            <div>
                                @include('staff.components.status-badge', [
                                    'status' => $lead->status,
                                    'type' => 'lead'
                                ])
                            </div>
                        </div>

                        <!-- Source Badge -->
                        <div class="mb-3">
                            <label class="form-label fw-bold mb-1">Nguồn:</label>
                            <div>
                                <span class="badge bg-info">{{ $lead->source }}</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        @php
                            // Primary actions: Sửa, Xóa, Quay lại (hiển thị vertical)
                            $primaryActions = [
                                [
                                    'type' => 'link',
                                    'variant' => 'primary',
                                    'label' => 'Sửa',
                                    'icon' => 'fas fa-edit',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.leads.edit', $lead->id),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa Lead',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteLead({$lead->id}, '" . addslashes($lead->name) . "')",
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Quay lại',
                                    'icon' => 'fas fa-arrow-left',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.leads.index'),
                                    'class' => 'w-100'
                                ]
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            if($lead->status !== 'new') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'info',
                                    'label' => 'Đánh dấu Mới',
                                    'icon' => 'fas fa-star',
                                    'onclick' => "updateLeadStatus('new')"
                                ];
                            }
                            
                            if($lead->status !== 'contacted') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'info',
                                    'label' => 'Đã liên hệ',
                                    'icon' => 'fas fa-phone',
                                    'onclick' => "updateLeadStatus('contacted')"
                                ];
                            }
                            
                            if($lead->status !== 'qualified') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'warning',
                                    'label' => 'Đủ điều kiện',
                                    'icon' => 'fas fa-check',
                                    'onclick' => "updateLeadStatus('qualified')"
                                ];
                            }
                            
                            if($lead->status !== 'proposal') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'secondary',
                                    'label' => 'Đề xuất',
                                    'icon' => 'fas fa-file-alt',
                                    'onclick' => "updateLeadStatus('proposal')"
                                ];
                            }
                            
                            if($lead->status !== 'negotiation') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'dark',
                                    'label' => 'Thương lượng',
                                    'icon' => 'fas fa-handshake',
                                    'onclick' => "updateLeadStatus('negotiation')"
                                ];
                            }
                            
                            if($lead->status !== 'converted') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Đã chuyển đổi',
                                    'icon' => 'fas fa-check-circle',
                                    'onclick' => "updateLeadStatus('converted')"
                                ];
                            }
                            
                            if($lead->status !== 'lost') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Đã mất',
                                    'icon' => 'fas fa-times-circle',
                                    'onclick' => "updateLeadStatus('lost')"
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

@push('scripts')
<script src="{{ asset('js/tab-navigation.js') }}"></script>
<script>
// Initialize tab navigation for this page
document.addEventListener('DOMContentLoaded', function() {
    TabNavigation.init('leadTabStates', ['basic-info']);
});

// Update Lead Status
function updateLeadStatus(newStatus) {
    const statusLabels = {
        'new': 'Mới',
        'contacted': 'Đã liên hệ',
        'qualified': 'Đủ điều kiện',
        'proposal': 'Đề xuất',
        'negotiation': 'Thương lượng',
        'converted': 'Đã chuyển đổi',
        'lost': 'Đã mất'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển sang trạng thái "${statusLabels[newStatus]}"?`,
        type: newStatus === 'lost' ? 'danger' : 'warning',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            // Show loading
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            // Gửi request
            const formData = new FormData();
            formData.append('status', newStatus);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('{{ route("staff.leads.update-status", $lead->id) }}', {
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

function deleteLead(id, name) {
    if (typeof Notify !== 'undefined' && Notify.confirmDelete) {
        Notify.confirmDelete(`lead "${name}"`, () => {
        // Show preloader
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

        fetch(`/staff/leads/${id}`, {
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
                    window.location.href = '{{ route("staff.leads.index") }}';
                }, 1000);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể xóa lead: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
    } else {
        // Fallback if Notify is not available
        if (confirm('Bạn có chắc chắn muốn xóa lead "' + name + '"?')) {
            // Show preloader
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

            fetch(`/staff/leads/${id}`, {
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
                    alert('Lead đã được xóa thành công!');
                    setTimeout(() => {
                        window.location.href = '{{ route("staff.leads.index") }}';
                    }, 1000);
                } else {
                    alert('Có lỗi xảy ra: ' + (data.message || 'Không xác định'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Không thể xóa lead: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.');
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
@endsection