@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Hợp đồng Thuê Lại')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với breadcrumbs --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết Hợp đồng Thuê Lại',
            'subtitle' => 'Thông tin chi tiết hợp đồng thuê lại: ' . ($masterLease->contract_no ?? 'Hợp đồng #' . $masterLease->id),
            'icon' => 'fas fa-file-contract',
            'breadcrumbs' => [
                ['label' => 'Hợp đồng Thuê Lại', 'url' => route('staff.master-leases.index')],
                ['label' => $masterLease->contract_no ?? 'Hợp đồng #' . $masterLease->id, 'active' => true]
            ]
        ])
    
    <div class="content" id="content">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Tabs Navigation -->
                @php
                    $tabs = [
                        'basic-info' => [
                            'label' => 'Thông tin cơ bản',
                            'icon' => 'fas fa-info-circle',
                            'color' => 'primary'
                        ],
                        'financial-info' => [
                            'label' => 'Thông tin tài chính',
                            'icon' => 'fas fa-money-bill-wave',
                            'color' => 'success'
                        ],
                        'units' => [
                            'label' => 'Phòng trong hợp đồng',
                            'icon' => 'fas fa-home',
                            'color' => 'info',
                            'badge' => $masterLease->units->count()
                        ],
                        'company-invoices' => [
                            'label' => 'Hóa đơn công ty',
                            'icon' => 'fas fa-file-invoice',
                            'color' => 'warning',
                            'badge' => $masterLease->companyInvoices ? $masterLease->companyInvoices->count() : 0
                        ],
                    ];
                    
                    if($masterLease->note) {
                        $tabs['notes'] = [
                            'label' => 'Ghi chú',
                            'icon' => 'fas fa-sticky-note',
                            'color' => 'secondary'
                        ];
                    }
                @endphp
                @include('staff.components.tab-navigation', [
                    'tabs' => $tabs,
                    'storageKey' => 'masterLeaseTabStates',
                    'defaultVisible' => ['basic-info']
                ])

                <!-- Basic Information -->
                <div class="card shadow-sm mt-4 tab-content" id="tab-basic-info">
                    <div class="card-header bg-primary text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Số hợp đồng</label>
                                    <div>
                                        @if ($masterLease->contract_no)
                                        <code class="bg-light px-2 py-1 rounded">{{ $masterLease->contract_no }}</code>
                                        @else
                                        <span class="text-muted">Chưa có</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small">Trạng thái</label>
                                    <div class="status-container">
                                        @include('staff.components.status-badge', [
                                            'status' => $masterLease->status,
                                            'type' => 'master_lease',
                                            'additionalClass' => 'fs-6 px-3 py-2'
                                        ])
                                        @if($masterLease->status === 'active' && $masterLease->is_active)
                                            <br><small class="text-success mt-1 d-block">
                                                <i class="fas fa-clock me-1"></i>
                                                Đang hoạt động - Còn {{ $masterLease->days_until_expiry }} ngày
                                            </small>
                                        @elseif($masterLease->status === 'active' && !$masterLease->is_active)
                                            <br><small class="text-warning mt-1 d-block">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Chưa đến ngày bắt đầu hoặc đã hết hạn
                                            </small>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Bất động sản</label>
                                    <div>
                                        <strong>{{ $masterLease->property->name }}</strong>
                                        @if($masterLease->property->location)
                                            <br><small class="text-muted">{{ $masterLease->property->location->address }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Chủ nhà</label>
                                    <div>
                                        @if($masterLease->landlord)
                                            <strong>{{ $masterLease->landlord->full_name }}</strong>
                                            @if($masterLease->landlord->phone)
                                                <br><small class="text-muted">{{ $masterLease->landlord->phone }}</small>
                                            @endif
                                            @if($masterLease->landlord->email)
                                                <br><small class="text-muted">{{ $masterLease->landlord->email }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Thời hạn hợp đồng</label>
                                    <div>
                                        <strong>Từ:</strong> {{ $masterLease->start_date->format('d/m/Y') }}<br>
                                        <strong>Đến:</strong> {{ $masterLease->end_date->format('d/m/Y') }}
                                        @if($masterLease->is_active)
                                            <br><small class="text-info">
                                                <i class="fas fa-clock"></i>
                                                Còn {{ $masterLease->days_until_expiry }} ngày
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Information -->
                <div class="card shadow-sm mt-4 tab-content" id="tab-financial-info" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-money-bill-wave me-2"></i>Thông tin tài chính
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Tiền thuê cơ bản</label>
                                    <div>
                                        <strong class="text-primary">{{ $masterLease->formatted_base_rent }}</strong>
                                        <br><small class="text-muted">{{ $masterLease->billing_cycle_label }}</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Tiền cọc</label>
                                    <div>
                                        <strong>{{ number_format($masterLease->deposit_amount, 0, ',', '.') }} {{ $masterLease->rent_currency }}</strong>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Tỷ lệ chia sẻ doanh thu</label>
                                    <div>
                                        @if($masterLease->revenue_share_pct)
                                            <strong class="text-info">{{ $masterLease->revenue_share_pct }}%</strong>
                                        @else
                                            <span class="text-muted">Không có</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Ngày thanh toán</label>
                                    <div>
                                        <strong>Ngày {{ $masterLease->billing_day }} hàng {{ $masterLease->billing_cycle_label }}</strong>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Số ngày đến hạn</label>
                                    <div>
                                        <strong>{{ $masterLease->due_in_days }} ngày</strong>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Tổng tiền thuê dự kiến</label>
                                    <div>
                                        <strong class="text-success">{{ number_format($masterLease->calculateTotalRent(), 0, ',', '.') }} {{ $masterLease->rent_currency }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Units Information -->
                <div class="card shadow-sm mt-4 tab-content" id="tab-units" style="display: none;">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-home me-2"></i>Phòng trong hợp đồng
                            @if($masterLease->units->count() > 0)
                                <span class="badge bg-light text-dark ms-2">{{ $masterLease->units->count() }}</span>
                            @endif
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($masterLease->units->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Mã phòng</th>
                                            <th>Loại phòng</th>
                                            <th>Diện tích</th>
                                            <th>Tiền thuê</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($masterLease->units as $unit)
                                        <tr>
                                            <td><code>{{ $unit->code }}</code></td>
                                            <td>{{ $unit->unit_type }}</td>
                                            <td>{{ $unit->area_m2 }} m²</td>
                                            <td>{{ number_format($unit->base_rent, 0, ',', '.') }} VND</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-home fa-2x mb-2"></i>
                                <p>Chưa có phòng nào trong hợp đồng này</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Company Invoices -->
                <div class="card shadow-sm mt-4 tab-content" id="tab-company-invoices" style="display: none;">
                    <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-file-invoice me-2"></i>Hóa đơn công ty
                            @if($masterLease->companyInvoices && $masterLease->companyInvoices->count() > 0)
                                <span class="badge bg-light text-dark ms-2">{{ $masterLease->companyInvoices->count() }}</span>
                            @endif
                        </h6>
                        <a href="{{ route('staff.company-invoices.create', ['master_lease_id' => $masterLease->id, 'source_type' => 'master_lease']) }}" class="btn btn-sm btn-light">
                            <i class="fas fa-plus"></i> Tạo hóa đơn
                        </a>
                    </div>
                    <div class="card-body">
                        {{-- Debug: Uncomment to check invoice count --}}
                        {{-- <div class="alert alert-info">
                            <strong>Debug:</strong> 
                            Master Lease ID: {{ $masterLease->id }}<br>
                            Company Invoices Count: {{ $masterLease->companyInvoices ? $masterLease->companyInvoices->count() : 0 }}<br>
                            @if($masterLease->companyInvoices && $masterLease->companyInvoices->count() > 0)
                                @foreach($masterLease->companyInvoices as $inv)
                                    Invoice ID: {{ $inv->id }}, No: {{ $inv->invoice_no }}, Master Lease ID: {{ $inv->master_lease_id }}<br>
                                @endforeach
                            @endif
                        </div> --}}
                        @if($masterLease->companyInvoices && $masterLease->companyInvoices->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Số hóa đơn</th>
                                            <th>Ngày phát hành</th>
                                            <th>Hạn thanh toán</th>
                                            <th>Tổng tiền</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($masterLease->companyInvoices as $invoice)
                                        <tr>
                                            <td>
                                                <strong>{{ $invoice->invoice_no ?? ('CI#' . $invoice->id) }}</strong>
                                            </td>
                                            <td>{{ $invoice->issue_date ? $invoice->issue_date->format('d/m/Y') : 'N/A' }}</td>
                                            <td>{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}</td>
                                            <td class="fw-bold text-success">{{ number_format($invoice->total_amount, 0, ',', '.') }} {{ $invoice->currency ?? 'VND' }}</td>
                                            <td>
                                                @include('staff.components.status-badge', [
                                                    'status' => $invoice->status,
                                                    'type' => 'invoice'
                                                ])
                                            </td>
                                            <td>
                                                <a href="{{ route('staff.company-invoices.show', $invoice->id) }}" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i> Xem
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có hóa đơn công ty</p>
                                <a href="{{ route('staff.company-invoices.create', ['master_lease_id' => $masterLease->id, 'source_type' => 'master_lease']) }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-plus"></i> Tạo hóa đơn đầu tiên
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Notes -->
                @if($masterLease->note)
                <div class="card shadow-sm mt-4 tab-content" id="tab-notes" style="display: none;">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-sticky-note me-2"></i>Ghi chú
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $masterLease->note }}</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Thao tác</h5>
                    </div>
                    <div class="card-body">
                        @php
                            // Primary actions: Sửa, Xóa, Quay lại
                            $primaryActions = [
                                [
                                    'type' => 'link',
                                    'variant' => 'primary',
                                    'label' => 'Chỉnh sửa',
                                    'icon' => 'fas fa-edit',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.master-leases.edit', $masterLease->id),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'success',
                                    'label' => 'Tạo hóa đơn công ty',
                                    'icon' => 'fas fa-file-invoice',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.company-invoices.create', ['master_lease_id' => $masterLease->id, 'source_type' => 'master_lease']),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteMasterLease({$masterLease->id}, '" . addslashes($masterLease->contract_no ?? 'Hợp đồng #' . $masterLease->id) . "')",
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Quay lại',
                                    'icon' => 'fas fa-arrow-left',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.master-leases.index'),
                                    'class' => 'w-100'
                                ]
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            // Sử dụng labels từ component status-badge (type: master_lease)
                            $masterLeaseStatusLabels = [
                                'draft' => 'Nháp',
                                'active' => 'Hoạt động',
                                'expired' => 'Hết hạn',
                                'terminated' => 'Chấm dứt'
                            ];
                            
                            $statusActions = [];
                            
                            if($masterLease->status !== 'draft') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'warning',
                                    'label' => 'Chuyển về ' . $masterLeaseStatusLabels['draft'],
                                    'icon' => 'fas fa-file-alt',
                                    'onclick' => "updateMasterLeaseStatus('draft')"
                                ];
                            }
                            
                            if($masterLease->status !== 'active') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Chuyển về ' . $masterLeaseStatusLabels['active'],
                                    'icon' => 'fas fa-check-circle',
                                    'onclick' => "updateMasterLeaseStatus('active')"
                                ];
                            }
                            
                            if($masterLease->status !== 'terminated') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Chuyển về ' . $masterLeaseStatusLabels['terminated'],
                                    'icon' => 'fas fa-stop-circle',
                                    'onclick' => "updateMasterLeaseStatus('terminated')"
                                ];
                            }
                            
                            if($masterLease->status !== 'expired') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'secondary',
                                    'label' => 'Chuyển về ' . $masterLeaseStatusLabels['expired'],
                                    'icon' => 'fas fa-clock',
                                    'onclick' => "updateMasterLeaseStatus('expired')"
                                ];
                            }
                        @endphp
                        
                        <div class="d-grid gap-2">
                            {{-- Primary Actions: Sửa, Tạo hóa đơn, Xóa, Quay lại (vertical) --}}
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

                <!-- Statistics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Thống kê</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Số phòng:</span>
                                <strong>{{ $masterLease->units->count() }}</strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Tổng tiền thuê:</span>
                                <strong class="text-success">{{ number_format($masterLease->calculateTotalRent(), 0, ',', '.') }} {{ $masterLease->rent_currency }}</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Tổng chi trả:</span>
                                <strong class="text-danger">{{ number_format($masterLease->getTotalOutflows(), 0, ',', '.') }} VND</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Lợi nhuận dự kiến:</span>
                                <strong class="text-info">{{ number_format($masterLease->getExpectedProfit(), 0, ',', '.') }} {{ $masterLease->rent_currency }}</strong>
                            </div>
                        </div>
                        
                        @php
                            $startDate = \Carbon\Carbon::parse($masterLease->start_date);
                            $endDate = \Carbon\Carbon::parse($masterLease->end_date);
                            $totalMonths = max(1, $startDate->diffInMonths($endDate) + 1);
                            $cycleMonths = (int) ($masterLease->billing_cycle ?: 1);
                            $cycles = (int) ceil($totalMonths / $cycleMonths);
                        @endphp
                        <div class="mt-3 pt-3 border-top">
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Logic tính toán:</strong>
                            </small>
                            <small class="text-muted d-block">
                                • Số tháng hợp đồng: {{ $totalMonths }} tháng (từ {{ $startDate->format('d/m/Y') }} đến {{ $endDate->format('d/m/Y') }})
                            </small>
                            <small class="text-muted d-block">
                                • Chu kỳ thanh toán: {{ $cycleMonths }} tháng/chu kỳ
                            </small>
                            <small class="text-muted d-block">
                                • Số chu kỳ: {{ $cycles }} chu kỳ
                            </small>
                            <small class="text-muted d-block">
                                • Tiền thuê/chu kỳ: {{ number_format($masterLease->base_rent, 0, ',', '.') }} {{ $masterLease->rent_currency }}
                            </small>
                            <small class="text-muted d-block">
                                • Tổng tiền thuê = {{ $cycles }} × {{ number_format($masterLease->base_rent, 0, ',', '.') }} = {{ number_format($masterLease->calculateTotalRent(), 0, ',', '.') }} {{ $masterLease->rent_currency }}
                            </small>
                            <small class="text-muted d-block">
                                • Tổng chi trả: {{ number_format($masterLease->getTotalOutflows(), 0, ',', '.') }} VND
                            </small>
                            <small class="text-muted d-block">
                                • Lợi nhuận = {{ number_format($masterLease->calculateTotalRent(), 0, ',', '.') }} - {{ number_format($masterLease->getTotalOutflows(), 0, ',', '.') }} = {{ number_format($masterLease->getExpectedProfit(), 0, ',', '.') }} {{ $masterLease->rent_currency }}
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lịch sử</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Tạo hợp đồng</h6>
                                    <p class="timeline-text">{{ $masterLease->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            
                            @if($masterLease->updated_at != $masterLease->created_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Cập nhật lần cuối</h6>
                                    <p class="timeline-text">{{ $masterLease->updated_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</main>
@endsection

@push('styles')
<style>
/* Status Badge Styling */
.badge {
    font-weight: 500;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    font-size: 0.75rem;
}

.badge.bg-success {
    background: linear-gradient(135deg, #28a745, #20c997) !important;
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}

.badge.bg-warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14) !important;
    box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
}

.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545, #e83e8c) !important;
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}

.badge.bg-secondary {
    background: linear-gradient(135deg, #6c757d, #495057) !important;
    box-shadow: 0 2px 4px rgba(108, 117, 125, 0.3);
}

/* Status container */
.status-container {
    position: relative;
}

.status-container .badge {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-content {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 5px;
    border-left: 3px solid #007bff;
}

.timeline-title {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: 600;
}

.timeline-text {
    margin: 0;
    font-size: 12px;
    color: #6c757d;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/tab-navigation.js') }}"></script>
<script>
// Initialize tab navigation for this page
document.addEventListener('DOMContentLoaded', function() {
    TabNavigation.init('masterLeaseTabStates', ['basic-info']);
});

// Function to update master lease status
function updateMasterLeaseStatus(newStatus) {
    // Status labels đồng bộ với component status-badge (type: master_lease)
    // Labels này phải khớp với component status-badge.blade.php
    const statusLabels = {
        'draft': 'Nháp',
        'active': 'Hoạt động',
        'expired': 'Hết hạn',
        'terminated': 'Chấm dứt'
    };
    
    const statusLabel = statusLabels[newStatus] || newStatus;
    
    Notify.confirm({
        title: 'Xác nhận chuyển trạng thái',
        message: `Bạn có chắc chắn muốn chuyển hợp đồng sang trạng thái "${statusLabel}"?`,
        type: 'warning',
        confirmText: 'Xác nhận',
        onConfirm: function() {
            // Hiển thị loading toast
            const loadingToast = Notify.toast({
                title: 'Đang xử lý...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0 // Không tự động đóng
            });
            
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('_method', 'PATCH');
            formData.append('status', newStatus);
            
            fetch(`/staff/master-leases/{{ $masterLease->id }}/status`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
            })
            .then(response => {
                // Đóng loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Notify.success(data.message || `Trạng thái hợp đồng đã được cập nhật thành "${statusLabel}".`);
                    // Reload page after 1.5 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra khi cập nhật trạng thái hợp đồng.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Có lỗi xảy ra khi cập nhật trạng thái hợp đồng. Vui lòng thử lại.', 'Lỗi hệ thống');
            });
        }
    });
}

// Function to delete master lease
window.deleteMasterLease = function(id, name) {
    
    if (typeof Notify === 'undefined') {
        alert('Notify is not defined. Please check if the notification system is loaded.');
        return;
    }
    
    Notify.confirmDelete(`hợp đồng "${name}"`, function() {
        // Hiển thị loading toast
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0 // Không tự động đóng
        });
        
        fetch(`/staff/master-leases/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
        .then(response => {
            // Đóng loading toast
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Hiển thị thông báo thành công
                Notify.success(data.message, 'Xóa thành công!');
                
                // Redirect to index page after 1.5 seconds
                setTimeout(() => {
                    window.location.href = '{{ route("staff.master-leases.index") }}';
                }, 1500);
            } else {
                // Hiển thị thông báo lỗi
                Notify.error(data.message, 'Không thể xóa hợp đồng');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Hiển thị thông báo lỗi
            Notify.error('Có lỗi xảy ra khi xóa hợp đồng. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
    });
};

$(document).ready(function() {
    // Any additional initialization code can go here
});
</script>
@endpush
