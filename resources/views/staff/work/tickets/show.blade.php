@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Ticket #' . $ticket->id)

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết Ticket #' . $ticket->id,
            'subtitle' => 'Thông tin chi tiết về ticket: ' . $ticket->title,
            'icon' => 'fas fa-ticket-alt',
            'breadcrumbs' => [
                ['label' => 'Ticket', 'url' => route('staff.tickets.index')],
                ['label' => 'Ticket #' . $ticket->id, 'active' => true]
            ]
        ])

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Ticket Details -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin Ticket</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Tiêu đề</h6>
                            <p class="mb-3">{{ $ticket->title }}</p>
                            
                            <h6 class="text-muted">Mô tả</h6>
                            <p class="mb-3">{{ $ticket->description ?: 'Không có mô tả' }}</p>
                            
                            @if($ticket->image)
                            <h6 class="text-muted">Hình ảnh đính kèm</h6>
                            <div class="mb-3">
                                <img src="{{ $ticket->image_url }}" alt="Ticket image" class="img-fluid rounded shadow-sm" style="max-width: 400px; max-height: 400px; cursor: pointer;" onclick="openImageModal('{{ $ticket->image_url }}')">
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle"></i> Click vào ảnh để xem kích thước lớn
                                </small>
                            </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Trạng thái</h6>
                            @php
                                $statusColors = [
                                    'open' => 'success',
                                    'in_progress' => 'warning',
                                    'resolved' => 'info',
                                    'closed' => 'secondary',
                                    'cancelled' => 'danger'
                                ];
                                $statusLabels = [
                                    'open' => 'Mở',
                                    'in_progress' => 'Đang xử lý',
                                    'resolved' => 'Đã giải quyết',
                                    'closed' => 'Đã đóng',
                                    'cancelled' => 'Đã hủy'
                                ];
                            @endphp
                            <p class="mb-3">
                                <span class="badge bg-{{ $statusColors[$ticket->status] }} fs-6">
                                    {{ $statusLabels[$ticket->status] }}
                                </span>
                            </p>
                            
                            <h6 class="text-muted">Độ ưu tiên</h6>
                            @php
                                $priorityCode = $ticket->priorityRelation?->key_code ?? 'medium';
                                $priorityColors = [
                                    'low' => 'secondary',
                                    'medium' => 'primary',
                                    'high' => 'warning',
                                    'urgent' => 'danger'
                                ];
                                $priorityLabels = [
                                    'low' => 'Thấp',
                                    'medium' => 'Trung bình',
                                    'high' => 'Cao',
                                    'urgent' => 'Khẩn cấp'
                                ];
                            @endphp
                            <p class="mb-3">
                                <span class="badge bg-{{ $priorityColors[$priorityCode] ?? 'secondary' }} fs-6">
                                    {{ $priorityLabels[$priorityCode] ?? ucfirst($priorityCode) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-folder-open me-2"></i>Tài liệu Ticket
                    </h6>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                        <i class="fas fa-upload"></i> Tải lên tài liệu
                    </button>
                </div>
                <div class="card-body">
                    @if($ticket->documents && $ticket->documents->count() > 0)
                        <div class="row g-3">
                            @foreach($ticket->documents as $document)
                            <div class="col-md-4">
                                <div class="card border">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <i class="fas {{ $document->getFileIcon() }} fa-2x text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <h6 class="mb-1 text-truncate" title="{{ $document->file_name }}">
                                                    {{ $document->file_name }}
                                                </h6>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-user"></i> {{ $document->uploader->full_name ?? 'N/A' }}
                                                </small>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-calendar"></i> {{ $document->created_at->format('d/m/Y H:i') }}
                                                </small>
                                            </div>
                                        </div>
                                        <div class="mt-2 d-flex gap-2">
                                            <a href="{{ $document->file_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Xem
                                            </a>
                                            <a href="{{ $document->file_url }}" download class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-download"></i> Tải
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument({{ $document->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <p>Chưa có tài liệu nào được tải lên</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Ticket Logs -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Nhật ký Ticket</h6>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLogModal">
                        <i class="fas fa-plus"></i> Thêm nhật ký
                    </button>
                </div>
                <div class="card-body">
                    @if($ticket->logs->count() > 0)
                        <div class="timeline">
                            @foreach($ticket->logs as $log)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $log->action }}</h6>
                                            <p class="mb-1">{{ $log->detail }}</p>
                                            @if($log->cost_amount > 0)
                                                <div class="alert alert-info py-2 px-3 mb-2">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong>Chi phí:</strong> <span class="text-primary fw-bold">{{ number_format($log->cost_amount, 0, ',', '.') }} VND</span>
                                                    @if($log->cost_note)
                                                                <br><small class="text-muted">{{ $log->cost_note }}</small>
                                                    @endif
                                                    <br><small><strong>Hạch toán:</strong> 
                                                        @php
                                                            $chargeLabels = [
                                                                'none' => 'Không hạch toán',
                                                                'tenant_deposit' => 'Trừ vào cọc',
                                                                'tenant_invoice' => 'Thêm vào hóa đơn',
                                                                'landlord' => 'Chủ trọ chịu',
                                                                'self_pay_vendor' => 'Tự chi trả (Vendor)'
                                                            ];
                                                                    $chargeColors = [
                                                                        'tenant_deposit' => 'warning',
                                                                        'tenant_invoice' => 'info',
                                                                        'landlord' => 'success',
                                                                        'self_pay_vendor' => 'secondary',
                                                                        'none' => 'light'
                                                                    ];
                                                        @endphp
                                                                <span class="badge bg-{{ $chargeColors[$log->charge_to] ?? 'light' }}">
                                                        {{ $chargeLabels[$log->charge_to] ?? $log->charge_to }}
                                                                </span>
                                                            </small>
                                                            @if($log->linked_invoice_id)
                                                                <br>
                                                                <a href="{{ route('staff.invoices.show', $log->linked_invoice_id) }}" 
                                                                   class="btn btn-sm btn-outline-primary mt-2" 
                                                                   title="Xem hóa đơn">
                                                                    <i class="fas fa-file-invoice"></i> Xem hóa đơn #{{ $log->linked_invoice_id }}
                                                                </a>
                                                            @endif
                                                            @if($log->charge_to === 'self_pay_vendor' && $log->companyInvoice)
                                                                <br>
                                                                <a href="{{ route('staff.company-invoices.show', $log->companyInvoice->id) }}" 
                                                                   class="btn btn-sm btn-outline-success mt-2" 
                                                                   title="Xem hóa đơn công ty">
                                                                    <i class="fas fa-file-invoice-dollar"></i> Xem hóa đơn công ty #{{ $log->companyInvoice->id }}
                                                                </a>
                                                            @endif
                                                            @if($log->vendor_id && $log->vendor)
                                                                <br><small class="text-muted">
                                                                    <i class="fas fa-tools"></i> Vendor: <strong>{{ $log->vendor->name }}</strong>
                                                                </small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if($log->warranty_expires_at)
                                                @php
                                                    $warrantyStatus = $log->warranty_status;
                                                    $daysUntilExpiry = $log->days_until_warranty_expires;
                                                    $statusColors = [
                                                        'active' => 'success',
                                                        'expiring_soon' => 'warning',
                                                        'expired' => 'danger',
                                                        'none' => 'secondary'
                                                    ];
                                                    $statusLabels = [
                                                        'active' => 'Đang bảo hành',
                                                        'expiring_soon' => 'Sắp hết hạn',
                                                        'expired' => 'Đã hết hạn',
                                                        'none' => 'Không có bảo hành'
                                                    ];
                                                @endphp
                                                <div class="alert alert-{{ $statusColors[$warrantyStatus] ?? 'secondary' }} py-2 px-3 mb-2">
                                                    <strong><i class="fas fa-shield-alt"></i> Bảo hành:</strong>
                                                    <span class="badge bg-{{ $statusColors[$warrantyStatus] ?? 'secondary' }}">
                                                        {{ $statusLabels[$warrantyStatus] ?? 'N/A' }}
                                                    </span>
                                                    @if($daysUntilExpiry !== null)
                                                        <br><small>
                                                            Hết hạn: {{ $log->warranty_expires_at->format('d/m/Y') }}
                                                            @if($warrantyStatus === 'expiring_soon' || $warrantyStatus === 'active')
                                                                (Còn {{ $daysUntilExpiry }} ngày)
                                                            @elseif($warrantyStatus === 'expired')
                                                                (Đã hết hạn {{ abs($daysUntilExpiry) }} ngày)
                                                            @endif
                                                        </small>
                                                    @endif
                                                    @if($log->warranty_period_days)
                                                        <br><small class="text-muted">
                                                            Thời hạn: {{ $log->warranty_period_days }} ngày
                                                    </small>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                {{ $log->actor->full_name ?? 'System' }}<br>
                                                {{ $log->created_at->format('d/m/Y H:i') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-2x text-muted mb-3"></i>
                            <p class="text-muted">Chưa có nhật ký nào</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Maintenance Cost Summary -->
            @php
                $totalCost = $ticket->logs->sum('cost_amount');
                $costByChargeTo = $ticket->logs->where('cost_amount', '>', 0)->groupBy('charge_to');
                $chargeToLabels = [
                    'tenant_deposit' => 'Trừ vào cọc',
                    'tenant_invoice' => 'Thêm vào hóa đơn',
                    'landlord' => 'Chủ trọ chịu',
                    'self_pay_vendor' => 'Tự chi trả (Vendor)',
                    'none' => 'Không hạch toán'
                ];
            @endphp
            @if($totalCost > 0)
            <div class="card shadow mb-4 border-primary">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-money-bill-wave me-2"></i>Tổng hợp chi phí
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="text-primary mb-3">
                                <strong>{{ number_format($totalCost, 0, ',', '.') }} VND</strong>
                            </h4>
                            <div class="small text-muted">
                                Tổng chi phí bảo trì/sửa chữa
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-2">Phân bổ chi phí:</h6>
                            <ul class="list-unstyled mb-0">
                                @foreach($costByChargeTo as $chargeTo => $logs)
                                    @php
                                        $chargeTotal = $logs->sum('cost_amount');
                                    @endphp
                                    @if($chargeTotal > 0)
                                        <li class="mb-2">
                                            <span class="badge bg-secondary me-2">{{ $chargeToLabels[$chargeTo] ?? $chargeTo }}</span>
                                            <strong>{{ number_format($chargeTotal, 0, ',', '.') }} VND</strong>
                                            <small class="text-muted">({{ $logs->count() }} mục)</small>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Ticket Info -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin liên kết</h6>
                </div>
                <div class="card-body">
                    @if($ticket->unit)
                        <h6 class="text-muted">Phòng</h6>
                        <p class="mb-3">
                            <strong>{{ $ticket->unit->property->name }}</strong><br>
                            Phòng: {{ $ticket->unit->code }}<br>
                            <small class="text-muted">{{ $ticket->unit->area_m2 }}m²</small>
                        </p>
                    @endif

                    @if($ticket->lease)
                        <h6 class="text-muted">Hợp đồng</h6>
                        <p class="mb-3">
                            <strong>{{ $ticket->lease->contract_no ?: 'HD#' . $ticket->lease->id }}</strong><br>
                            Khách thuê: {{ $ticket->lease->tenant->full_name }}<br>
                            <small class="text-muted">
                                {{ $ticket->lease->start_date->format('d/m/Y') }} - 
                                {{ $ticket->lease->end_date->format('d/m/Y') }}
                            </small>
                        </p>
                    @endif

                    <h6 class="text-muted">Người tạo</h6>
                    <p class="mb-3">{{ $ticket->createdBy->full_name ?? 'N/A' }}</p>

                    <h6 class="text-muted">Người phụ trách</h6>
                    <p class="mb-3">{{ $ticket->assignedTo->full_name ?? 'Chưa giao' }}</p>

                    <h6 class="text-muted">Ngày tạo</h6>
                    <p class="mb-3">{{ $ticket->created_at->format('d/m/Y H:i') }}</p>

                    <h6 class="text-muted">Cập nhật cuối</h6>
                    <p class="mb-0">{{ $ticket->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <!-- Warranty Summary -->
            @php
                $warranties = $ticket->logs->whereNotNull('warranty_expires_at');
                $activeWarranties = $warranties->filter(function($log) {
                    return $log->hasActiveWarranty();
                });
                $expiringWarranties = $warranties->filter(function($log) {
                    return $log->warranty_status === 'expiring_soon';
                });
                $expiredWarranties = $warranties->filter(function($log) {
                    return $log->warranty_status === 'expired';
                });
            @endphp
            @if($warranties->count() > 0)
            <div class="card shadow mb-4 border-info">
                <div class="card-header py-3 bg-info text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-shield-alt me-2"></i>Tổng hợp bảo hành
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h5 class="text-success">{{ $activeWarranties->count() }}</h5>
                            <small class="text-muted">Đang bảo hành</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <h5 class="text-warning">{{ $expiringWarranties->count() }}</h5>
                            <small class="text-muted">Sắp hết hạn</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <h5 class="text-danger">{{ $expiredWarranties->count() }}</h5>
                            <small class="text-muted">Đã hết hạn</small>
                        </div>
                    </div>
                    @if($expiringWarranties->count() > 0)
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>Cảnh báo:</strong> Có {{ $expiringWarranties->count() }} bảo hành sắp hết hạn trong 30 ngày tới
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Card "Thao tác" --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>Thao tác
                    </h5>
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
                                'url' => route('staff.tickets.edit', $ticket->id),
                                'class' => 'w-100'
                            ],
                            [
                                'type' => 'button',
                                'variant' => 'danger',
                                'label' => 'Xóa',
                                'icon' => 'fas fa-trash-alt',
                                'iconPosition' => 'left',
                                'onclick' => "deleteTicket({$ticket->id}, '" . addslashes($ticket->title) . "')",
                                'class' => 'w-100'
                            ],
                            [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Quay lại',
                                'icon' => 'fas fa-arrow-left',
                                'iconPosition' => 'left',
                                'url' => route('staff.tickets.index'),
                                'class' => 'w-100'
                            ]
                        ];
                        
                        // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                        $statusActions = [];
                        
                        if($ticket->status !== 'open') {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'success',
                                'label' => 'Mở lại',
                                'icon' => 'fas fa-folder-open',
                                'iconPosition' => 'left',
                                'onclick' => "updateStatus('open')",
                                'class' => 'w-100'
                            ];
                        }
                        
                        if($ticket->status !== 'in_progress') {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'warning',
                                'label' => 'Bắt đầu xử lý',
                                'icon' => 'fas fa-play',
                                'iconPosition' => 'left',
                                'onclick' => "updateStatus('in_progress')",
                                'class' => 'w-100'
                            ];
                        }
                        
                        if($ticket->status !== 'resolved') {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'info',
                                'label' => 'Đánh dấu đã giải quyết',
                                'icon' => 'fas fa-check',
                                'iconPosition' => 'left',
                                'onclick' => "updateStatus('resolved')",
                                'class' => 'w-100'
                            ];
                        }
                        
                        if($ticket->status !== 'closed') {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'secondary',
                                'label' => 'Đóng ticket',
                                'icon' => 'fas fa-lock',
                                'iconPosition' => 'left',
                                'onclick' => "updateStatus('closed')",
                                'class' => 'w-100'
                            ];
                        }
                        
                        if($ticket->status !== 'cancelled') {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'danger',
                                'label' => 'Hủy ticket',
                                'icon' => 'fas fa-times',
                                'iconPosition' => 'left',
                                'onclick' => "updateStatus('cancelled')",
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

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tải lên tài liệu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadDocumentForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Chọn file <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="document" required 
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                        <small class="text-muted">Hỗ trợ: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF (tối đa 20MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Tải lên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Log Modal -->
<div class="modal fade" id="addLogModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm nhật ký</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addLogForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hành động <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="action" required 
                                   placeholder="Ví dụ: Kiểm tra, Sửa chữa, Hoàn thành...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hạch toán chi phí <span class="text-danger">*</span></label>
                            <select class="form-select" name="charge_to" id="charge_to" required>
                                <option value="none">Không hạch toán</option>
                                @if($ticket->lease_id)
                                <option value="tenant_deposit">Trừ vào cọc</option>
                                <option value="tenant_invoice">Thêm vào hóa đơn</option>
                                @endif
                                <option value="landlord">Chủ trọ chịu</option>
                                <option value="self_pay_vendor">Tự chi trả (Vendor)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 d-none" id="vendor_field">
                            <label class="form-label">Nhà cung cấp (Vendor) <span class="text-danger">*</span></label>
                            <select class="form-select" name="vendor_id">
                                <option value="">-- Chọn nhà cung cấp --</option>
                                @foreach(($vendors ?? []) as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Chi tiết</label>
                            <textarea class="form-control" name="detail" rows="3" 
                                      placeholder="Mô tả chi tiết hành động..."></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Chi phí (VND)</label>
                            <input type="number" class="form-control" name="cost_amount" 
                                   min="0" step="1000" placeholder="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ghi chú chi phí</label>
                            <input type="text" class="form-control" name="cost_note" 
                                   placeholder="Mô tả chi phí...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Thời hạn bảo hành (ngày)</label>
                            <input type="number" class="form-control" name="warranty_period_days" 
                                   min="0" max="3650" placeholder="0">
                            <small class="text-muted">Để trống nếu không có bảo hành</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ngày hết hạn bảo hành</label>
                            <input type="date" class="form-control" name="warranty_expires_at" 
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                            <small class="text-muted">Hoặc chọn ngày hết hạn cụ thể</small>
                        </div>
                        <div class="col-md-12 mb-3 d-none" id="invoice_document_field">
                            <label class="form-label">Hóa đơn/Chứng từ <span class="text-muted">(Tùy chọn)</span></label>
                            <input type="file" class="form-control" name="invoice_document" id="invoice_document"
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Upload hình ảnh hóa đơn, chứng từ thanh toán (PDF, JPG, PNG, DOC, XLS - Max 10MB)
                            </small>
                            <div id="invoice_document_preview" class="mt-2" style="display: none;">
                                <div class="alert alert-success py-2 px-3">
                                    <i class="fas fa-file-alt me-2"></i>
                                    <span id="invoice_document_name"></span>
                                    <button type="button" class="btn btn-sm btn-link text-danger float-end" onclick="removeInvoiceDocument()">
                                        <i class="fas fa-times"></i> Xóa
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Thêm nhật ký</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hình ảnh đính kèm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Ticket image" class="img-fluid">
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #007bff;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #007bff;
}
</style>
@endpush

@push('scripts')
<script>
// Delete ticket function
function deleteTicket(id, name) {
    Notify.confirmDelete(`ticket "${name}"`, function() {
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        fetch(`/staff/tickets/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Xóa thành công!');
                setTimeout(() => {
                    window.location.href = '{{ route("staff.tickets.index") }}';
                }, 1500);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Không thể xóa ticket');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi xóa ticket. Vui lòng thử lại.', 'Lỗi hệ thống');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
}

// Update status function
window.updateStatus = function(newStatus) {
    const statusLabels = {
        'in_progress': 'Đang xử lý',
        'resolved': 'Đã giải quyết',
        'closed': 'Đã đóng',
        'cancelled': 'Đã hủy'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển ticket sang trạng thái "${statusLabels[newStatus]}"?`,
        type: newStatus === 'cancelled' ? 'danger' : 'warning',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
        // Show loading
        const loadingToast = Notify.toast({
            title: 'Đang cập nhật...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });
        
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        // Get ticket data from PHP
        const ticketData = {
            title: {!! json_encode($ticket->title) !!},
            description: {!! json_encode($ticket->description) !!},
            priority_id: {!! json_encode($ticket->priority_id) !!},
            unit_id: {!! json_encode($ticket->unit_id) !!},
            lease_id: {!! json_encode($ticket->lease_id) !!},
            assigned_to: {!! json_encode($ticket->assigned_to) !!}
        };
        
        // Ensure all required fields are sent
        formData.append('title', ticketData.title || '');
        formData.append('description', ticketData.description || '');
        formData.append('priority_id', ticketData.priority_id || '');
        formData.append('status', newStatus);
        
        // Handle nullable fields - only append if not null
        if (ticketData.unit_id !== null && ticketData.unit_id !== '') {
            formData.append('unit_id', ticketData.unit_id);
        }
        
        if (ticketData.lease_id !== null && ticketData.lease_id !== '') {
            formData.append('lease_id', ticketData.lease_id);
        }
        
        if (ticketData.assigned_to !== null && ticketData.assigned_to !== '') {
            formData.append('assigned_to', ticketData.assigned_to);
        }
        
        // Use POST with _method=PUT for Laravel method spoofing
        formData.append('_method', 'PUT');
        
        const updateUrl = '{{ route("staff.tickets.update", $ticket->id) }}';
        
        fetch(updateUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            // Hide loading
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            
            // Parse response body for error messages
            return response.json().then(data => {
                if (!response.ok) {
                    // Include validation errors in the error object
                    const error = new Error(`HTTP error! status: ${response.status}`);
                    error.response = data;
                    throw error;
                }
                return data;
            });
        })
        .then(data => {
            
            if (data.success) {
                Notify.success(data.message, 'Cập nhật thành công!');
                setTimeout(() => location.reload(), 1500);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra khi cập nhật', 'Lỗi cập nhật');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Show validation errors if available
            let errorMessage = 'Có lỗi xảy ra. Vui lòng thử lại.';
            if (error.response) {
                if (error.response.message) {
                    errorMessage = error.response.message;
                } else if (error.response.errors) {
                    const errors = Object.values(error.response.errors).flat();
                    errorMessage = errors.join(', ');
                }
            }
            
            Notify.error(errorMessage, 'Lỗi cập nhật');
        });
        }
    });
};

// Add log form
document.getElementById('addLogForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate vendor when self_pay_vendor is selected
    const chargeTo = this.querySelector('[name="charge_to"]').value;
    const vendorId = this.querySelector('[name="vendor_id"]').value;
    if (chargeTo === 'self_pay_vendor' && !vendorId) {
        Notify.error('Vui lòng chọn nhà cung cấp (Vendor)', 'Lỗi');
        return;
    }
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang thêm...';
    submitBtn.disabled = true;
    
    fetch(`/staff/tickets/{{ $ticket->id }}/logs`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Notify.success(data.message, 'Thêm thành công!');
            setTimeout(() => location.reload(), 1500);
        } else {
            Notify.error(data.message, 'Lỗi thêm nhật ký');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Notify.error('Có lỗi xảy ra. Vui lòng thử lại.', 'Lỗi hệ thống');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Toggle vendor field and invoice document field based on charge_to
const chargeToSelect = document.getElementById('charge_to');
const vendorField = document.getElementById('vendor_field');
const invoiceDocumentField = document.getElementById('invoice_document_field');
if (chargeToSelect) {
    chargeToSelect.addEventListener('change', function() {
        if (this.value === 'self_pay_vendor') {
            vendorField.classList.remove('d-none');
            invoiceDocumentField.classList.remove('d-none');
        } else {
            vendorField.classList.add('d-none');
            invoiceDocumentField.classList.add('d-none');
            const select = vendorField.querySelector('select[name="vendor_id"]');
            if (select) select.value = '';
            // Reset invoice document
            const fileInput = document.getElementById('invoice_document');
            if (fileInput) {
                fileInput.value = '';
                document.getElementById('invoice_document_preview').style.display = 'none';
            }
        }
    });
}

// Show invoice document preview
const invoiceDocumentInput = document.getElementById('invoice_document');
if (invoiceDocumentInput) {
    invoiceDocumentInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Check file size (10MB max)
            if (file.size > 10 * 1024 * 1024) {
                Notify.error('Kích thước file không được vượt quá 10MB', 'Lỗi');
                this.value = '';
                return;
            }
            
            document.getElementById('invoice_document_name').textContent = file.name;
            document.getElementById('invoice_document_preview').style.display = 'block';
        } else {
            document.getElementById('invoice_document_preview').style.display = 'none';
        }
    });
}

// Remove invoice document
window.removeInvoiceDocument = function() {
    const fileInput = document.getElementById('invoice_document');
    if (fileInput) {
        fileInput.value = '';
        document.getElementById('invoice_document_preview').style.display = 'none';
    }
};

// Upload Document Form
const uploadDocumentForm = document.getElementById('uploadDocumentForm');
if (uploadDocumentForm) {
    uploadDocumentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải lên...';
        submitBtn.disabled = true;
        
        fetch('{{ route("staff.tickets.documents.upload", $ticket->id) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Tải lên thành công!');
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('uploadDocumentModal'));
                if (modal) modal.hide();
                
                // Reset form
                this.reset();
                
                // Reload page after 1.5 seconds
                setTimeout(() => location.reload(), 1500);
            } else {
                Notify.error(data.message, 'Lỗi tải lên');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi tải lên tài liệu. Vui lòng thử lại.', 'Lỗi hệ thống');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
}

// Delete Document
function deleteDocument(documentId) {
    Notify.confirmDelete('tài liệu này', function() {
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });
        
        fetch(`{{ route("staff.tickets.documents.delete", [$ticket->id, ":documentId"]) }}`.replace(':documentId', documentId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Xóa thành công!');
                setTimeout(() => location.reload(), 1500);
            } else {
                Notify.error(data.message, 'Không thể xóa tài liệu');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi xóa tài liệu. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
    });
}

// Image modal function
function openImageModal(imageUrl) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    if (modal && modalImage) {
        modalImage.src = imageUrl;
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}
</script>
@endpush
