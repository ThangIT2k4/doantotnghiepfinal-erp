@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Nhà cung cấp')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}">
@endpush

@section('content')
@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notify.success('{{ session('success') }}', 'Thành công!');
        });
    </script>
@endif

@if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notify.error('{{ session('error') }}', 'Lỗi!');
        });
    </script>
@endif

@if(session('warning'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notify.warning('{{ session('warning') }}', 'Cảnh báo!');
        });
    </script>
@endif

@if(session('info'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Notify.info('{{ session('info') }}', 'Thông tin');
        });
    </script>
@endif

<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết Nhà cung cấp',
            'subtitle' => 'Thông tin chi tiết nhà cung cấp: ' . $vendor->name,
            'icon' => 'fas fa-building',
            'breadcrumbs' => [
                ['label' => 'Nhà cung cấp', 'url' => route('staff.vendors.index')],
                ['label' => $vendor->name, 'active' => true]
            ]
        ])

        <!-- Tab Navigation - Chỉ dùng cho các tab có liên kết với các trang khác -->
        @include('staff.components.tab-navigation', [
            'tabs' => [
                'company-invoices' => [
                    'label' => 'Hóa đơn công ty',
                    'icon' => 'fas fa-file-invoice',
                    'color' => 'info',
                    'badge' => $companyInvoices->count()
                ]
            ],
            'storageKey' => 'vendorTabStates',
            'defaultVisible' => ['company-invoices']
        ])
    
        <!-- Content -->
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Basic Information -->
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
                                    <label class="text-muted small">Tên nhà cung cấp</label>
                                    <div>
                                        <strong>{{ $vendor->name }}</strong>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="text-muted small">Loại nhà cung cấp</label>
                                    <div>
                                        <span class="badge bg-info">{{ $vendor->vendor_type_label }}</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Mã số thuế</label>
                                    <div>
                                        @if($vendor->tax_code)
                                            <code class="bg-light px-2 py-1 rounded">{{ $vendor->tax_code }}</code>
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Giấy phép kinh doanh</label>
                                    <div>
                                        @if($vendor->business_license)
                                            <code class="bg-light px-2 py-1 rounded">{{ $vendor->business_license }}</code>
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Trạng thái</label>
                                    <div>
                                        <span class="badge {{ $vendor->status_badge_class }}">{{ $vendor->status_label }}</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Ngày tạo</label>
                                    <div>
                                        <strong>{{ $vendor->created_at->format('d/m/Y H:i') }}</strong>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Cập nhật lần cuối</label>
                                    <div>
                                        <strong>{{ $vendor->updated_at->format('d/m/Y H:i') }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-address-book me-2"></i>Thông tin liên hệ
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Số điện thoại</label>
                                    <div>
                                        @if($vendor->phone)
                                            <strong><i class="fas fa-phone text-muted"></i> {{ $vendor->phone }}</strong>
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Email</label>
                                    <div>
                                        @if($vendor->email)
                                            <strong><i class="fas fa-envelope text-muted"></i> {{ $vendor->email }}</strong>
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Địa chỉ</label>
                                    <div>
                                        @if($vendor->address)
                                            <strong>{{ $vendor->address }}</strong>
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small">Người liên hệ</label>
                                    <div>
                                        @if($vendor->contact_person)
                                            <strong><i class="fas fa-user text-muted"></i> {{ $vendor->contact_person }}</strong>
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">SĐT liên hệ</label>
                                    <div>
                                        @if($vendor->contact_phone)
                                            <strong><i class="fas fa-phone text-muted"></i> {{ $vendor->contact_phone }}</strong>
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="text-muted small">Email liên hệ</label>
                                    <div>
                                        @if($vendor->contact_email)
                                            <strong><i class="fas fa-envelope text-muted"></i> {{ $vendor->contact_email }}</strong>
                                        @else
                                            <span class="text-muted">Chưa có</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Banking Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-university me-2"></i>Thông tin ngân hàng (Tích hợp Sepay)
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($vendor->sepayBank || $vendor->account_number)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="text-muted small">Tên ngân hàng</label>
                                        <div>
                                            @if($vendor->sepayBank)
                                                <strong>{{ $vendor->sepayBank->name }}</strong>
                                            @else
                                                <span class="text-muted">Chưa có</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-muted small">Mã ngân hàng</label>
                                        <div>
                                            @if($vendor->sepayBank)
                                                <code class="bg-light px-2 py-1 rounded">{{ $vendor->sepayBank->code }}</code>
                                            @else
                                                <span class="text-muted">Chưa có</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-muted small">Tên viết tắt</label>
                                        <div>
                                            @if($vendor->sepayBank)
                                                <code class="bg-primary text-white px-2 py-1 rounded">{{ $vendor->sepayBank->short_name }}</code>
                                            @else
                                                <span class="text-muted">Chưa có</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-muted small">Số tài khoản</label>
                                        <div>
                                            @if($vendor->account_number)
                                                <strong>{{ $vendor->account_number }}</strong>
                                            @else
                                                <span class="text-muted">Chưa có</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-muted small">Tên chủ tài khoản</label>
                                        <div>
                                            @if($vendor->account_holder_name)
                                                <strong>{{ $vendor->account_holder_name }}</strong>
                                            @else
                                                <span class="text-muted">Chưa có</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="text-muted small">Tên chi nhánh</label>
                                        <div>
                                            @if($vendor->branch_name)
                                                <strong>{{ $vendor->branch_name }}</strong>
                                            @else
                                                <span class="text-muted">Chưa có</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-muted small">Mã chi nhánh</label>
                                        <div>
                                            @if($vendor->branch_code)
                                                <code class="bg-light px-2 py-1 rounded">{{ $vendor->branch_code }}</code>
                                            @else
                                                <span class="text-muted">Chưa có</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-muted small">BIN Code</label>
                                        <div>
                                            @if($vendor->sepayBank)
                                                <code class="bg-info text-white px-2 py-1 rounded">{{ $vendor->sepayBank->bin }}</code>
                                            @else
                                                <span class="text-muted">Chưa có</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-muted small">Mã SWIFT</label>
                                        <div>
                                            @if($vendor->swift_code)
                                                <code class="bg-light px-2 py-1 rounded">{{ $vendor->swift_code }}</code>
                                            @else
                                                <span class="text-muted">Chưa có</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="text-muted small">Ghi chú ngân hàng</label>
                                        <div>
                                            @if($vendor->banking_notes)
                                                <p class="mb-0">{{ $vendor->banking_notes }}</p>
                                            @else
                                                <span class="text-muted">Chưa có</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    @if($vendor->sepayBank && $vendor->account_number)
                                    <div class="mb-3">
                                        <label class="text-muted small">QR Thanh toán SePay</label>
                                        <div id="qrCodeContainer" class="p-3 bg-light rounded border text-center">
                                            <i class="fas fa-qrcode fa-3x text-primary mb-2"></i>
                                            <div>
                                                <strong>{{ $vendor->sepayBank->name ?? 'Ngân hàng' }}</strong><br>
                                                <small class="text-muted">
                                                    TK: {{ $vendor->account_number }}<br>
                                                    @if($vendor->sepayBank)
                                                        BIN: {{ $vendor->sepayBank->bin }} | {{ $vendor->sepayBank->short_name }}
                                                    @endif
                                                </small>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary mt-2" onclick="generateQRCode()">
                                                <i class="fas fa-qrcode"></i> Tạo QR Code
                                            </button>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-university fa-3x mb-3"></i>
                                <p>Chưa có thông tin ngân hàng</p>
                                <a href="{{ route('staff.vendors.edit', $vendor->id) }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i>
                                    Thêm thông tin ngân hàng
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Company Invoices Tab -->
                <div class="card shadow-sm mb-4 tab-content" id="tab-company-invoices">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-file-invoice me-2"></i>Hóa đơn công ty
                        </h6>
                        <a href="{{ route('staff.company-invoices.index', ['vendor_id' => $vendor->id]) }}" 
                           class="btn btn-sm btn-primary">
                            <i class="fas fa-filter me-1"></i>Lọc tất cả
                        </a>
                    </div>
                    <div class="card-body">
                        @if($companyInvoices->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Số hóa đơn</th>
                                            <th>Loại</th>
                                            <th>Ngày phát hành</th>
                                            <th>Ngày đến hạn</th>
                                            <th>Tổng tiền</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $statusClasses = [
                                                'draft' => 'badge-secondary',
                                                'pending' => 'badge-warning',
                                                'approved' => 'badge-info',
                                                'paid' => 'badge-success',
                                                'overdue' => 'badge-danger',
                                                'cancelled' => 'badge-dark'
                                            ];
                                            $statusLabels = [
                                                'draft' => 'Nháp',
                                                'pending' => 'Chờ duyệt',
                                                'approved' => 'Đã duyệt',
                                                'paid' => 'Đã thanh toán',
                                                'overdue' => 'Quá hạn',
                                                'cancelled' => 'Đã hủy'
                                            ];
                                            $typeLabels = [
                                                'master_lease' => 'Hợp đồng thuê',
                                                'ticket_cost' => 'Chi phí ticket',
                                                'deposit_refund' => 'Hoàn tiền cọc',
                                                'payroll_payslip' => 'Lương nhân viên',
                                                'utility' => 'Tiện ích',
                                                'maintenance' => 'Bảo trì',
                                                'service' => 'Dịch vụ',
                                                'supply' => 'Cung cấp',
                                                'other' => 'Khác'
                                            ];
                                        @endphp
                                        @foreach($companyInvoices as $invoice)
                                            <tr>
                                                <td>
                                                    <strong>{{ $invoice->invoice_no }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $typeLabels[$invoice->invoice_type] ?? $invoice->invoice_type }}</span>
                                                </td>
                                                <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
                                                <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                                                <td>
                                                    <strong class="text-success">{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</strong>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $statusClasses[$invoice->status] ?? 'badge-secondary' }}">
                                                        {{ $statusLabels[$invoice->status] ?? $invoice->status }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('staff.company-invoices.show', $invoice->id) }}" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> Xem
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-file-invoice fa-3x mb-3"></i>
                                <p>Chưa có hóa đơn công ty nào</p>
                                <a href="{{ route('staff.company-invoices.index', ['vendor_id' => $vendor->id]) }}" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Tạo hóa đơn
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Actions -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
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
                                    'url' => route('staff.vendors.edit', $vendor->id),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteVendor({$vendor->id}, '" . addslashes($vendor->name) . "')",
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Quay lại',
                                    'icon' => 'fas fa-arrow-left',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.vendors.index'),
                                    'class' => 'w-100'
                                ]
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            if($vendor->status !== 'active') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Kích hoạt',
                                    'icon' => 'fas fa-check-circle',
                                    'onclick' => "updateVendorStatus('active')"
                                ];
                            }
                            
                            if($vendor->status !== 'inactive') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'secondary',
                                    'label' => 'Ngừng hoạt động',
                                    'icon' => 'fas fa-pause-circle',
                                    'onclick' => "updateVendorStatus('inactive')"
                                ];
                            }
                            
                            if($vendor->status !== 'suspended') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'warning',
                                    'label' => 'Tạm ngưng',
                                    'icon' => 'fas fa-ban',
                                    'onclick' => "updateVendorStatus('suspended')"
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

                <!-- Statistics -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Thống kê
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Tổng thanh toán:</span>
                                <strong class="text-success">{{ $vendor->formatted_total_payments }}</strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Thanh toán cuối:</span>
                                <strong class="text-info">{{ $vendor->last_payment_date ?? 'Chưa có' }}</strong>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Số giao dịch:</span>
                                <strong>{{ $vendor->cashOutflows->count() }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Lịch sử
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Tạo nhà cung cấp</h6>
                                    <p class="timeline-text">{{ $vendor->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            
                            @if($vendor->updated_at != $vendor->created_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Cập nhật lần cuối</h6>
                                    <p class="timeline-text">{{ $vendor->updated_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>


@push('styles')
<style>
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
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script src="{{ asset('js/sepay-banks.js') }}"></script>
<script>
// Update vendor status
function updateVendorStatus(newStatus) {
    const statusLabels = {
        'active': 'Hoạt động',
        'inactive': 'Không hoạt động',
        'suspended': 'Tạm ngưng'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển nhà cung cấp sang trạng thái "${statusLabels[newStatus]}"?`,
        type: newStatus === 'suspended' ? 'warning' : 'info',
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
            
            fetch('{{ route("staff.vendors.update-status", $vendor->id) }}', {
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

// Delete vendor from button
function deleteVendorFromButton(button) {
    const vendorId = button.getAttribute('data-vendor-id');
    const vendorName = button.getAttribute('data-vendor-name');
    deleteVendor(vendorId, vendorName);
}

// Delete vendor
function deleteVendor(vendorId, vendorName) {
    // Validate parameters
    if (!vendorId || vendorId === 'undefined' || vendorId === 'null') {
        Notify.error('Không thể xác định ID nhà cung cấp', 'Lỗi');
        return;
    }
    
    // Use notification system confirmation
    Notify.confirm({
        title: 'Xác nhận xóa nhà cung cấp',
        message: `Bạn có chắc chắn muốn xóa nhà cung cấp "${vendorName}"?`,
        details: 'Hành động này có thể được khôi phục.',
        type: 'danger',
        confirmText: 'Xóa',
        cancelText: 'Hủy',
        onConfirm: function() {
            const loadingToast = Notify.toast({
                title: 'Đang xử lý...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0,
                showProgress: false
            });
            
            // Use the correct DELETE route for vendors
            const deleteUrl = window.location.origin + '/staff/vendors/' + vendorId;
            
            // Use form submit instead of AJAX to avoid browser issues
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = deleteUrl;
            form.style.display = 'none';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            
            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            
            form.submit();
        }
    });
}

// QR Code generation for SePay
function generateQRCode() {
    const vendorBankCode = '{{ $vendor->sepayBank->code ?? "" }}';
    const vendorAccountNumber = '{{ $vendor->account_number }}';
    const vendorBankName = '{{ $vendor->sepayBank->name ?? "" }}';
    const vendorBankShortName = '{{ $vendor->sepayBank->short_name ?? "" }}';
    
    if (!vendorBankCode || !vendorAccountNumber) {
        Notify.error('Thiếu thông tin ngân hàng để tạo QR code');
        return;
    }
    
    // Show input modal first
    showQRInputModal(vendorBankCode, vendorAccountNumber, vendorBankShortName, vendorBankName);
}

function showQRInputModal(bankCode, accountNumber, bankShortName, bankName) {
    // Create input modal HTML
    const inputModalHtml = `
        <div class="modal fade" id="qrInputModal" tabindex="-1" aria-labelledby="qrInputModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="qrInputModalLabel">
                            <i class="fas fa-qrcode text-primary"></i>
                            Nhập thông tin QR Code
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <form id="qrInputForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="qrAmount" class="form-label">
                                    Số tiền (VNĐ) <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="qrAmount" 
                                       name="amount" 
                                       value="100000" 
                                       min="1000" 
                                       step="1000" 
                                       required
                                       placeholder="Nhập số tiền">
                                <small class="form-text text-muted">
                                    Số tiền tối thiểu: 1,000 VNĐ
                                </small>
                            </div>
                            <div class="mb-3">
                                <label for="qrDescription" class="form-label">
                                    Nội dung thanh toán
                                </label>
                                <textarea class="form-control" 
                                          id="qrDescription" 
                                          name="description" 
                                          rows="3" 
                                          placeholder="Nhập nội dung thanh toán (tùy chọn)">Thanh toán cho {{ $vendor->name }}</textarea>
                                <small class="form-text text-muted">
                                    Nội dung sẽ hiển thị trên QR Code
                                </small>
                            </div>
                            <div class="alert alert-info mb-0">
                                <small>
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Thông tin ngân hàng:</strong> ${bankName}<br>
                                    <strong>Số tài khoản:</strong> ${accountNumber}
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Hủy
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Tạo QR Code
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing input modal
    const existingInputModal = document.getElementById('qrInputModal');
    if (existingInputModal) {
        const existingModalInstance = bootstrap.Modal.getInstance(existingInputModal);
        if (existingModalInstance) {
            existingModalInstance.hide();
            existingInputModal.addEventListener('hidden.bs.modal', function() {
                existingInputModal.remove();
            }, { once: true });
        } else {
            existingInputModal.remove();
        }
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', inputModalHtml);
    
    // Get the new modal element
    const inputModalElement = document.getElementById('qrInputModal');
    const inputForm = document.getElementById('qrInputForm');
    
    // Handle form submission
    inputForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const amount = document.getElementById('qrAmount').value;
        const description = document.getElementById('qrDescription').value || 'Thanh toán cho {{ $vendor->name }}';
        
        // Validate amount
        if (!amount || parseFloat(amount) < 1000) {
            Notify.error('Số tiền phải lớn hơn hoặc bằng 1,000 VNĐ', 'Lỗi!');
            return;
        }
        
        // Close input modal
        const inputModalInstance = bootstrap.Modal.getInstance(inputModalElement);
        if (inputModalInstance) {
            inputModalInstance.hide();
        }
        
        // Show QR preview modal after input modal is closed
        inputModalElement.addEventListener('hidden.bs.modal', function() {
            showQRCodeModal(bankCode, accountNumber, bankShortName, bankName, amount, description);
        }, { once: true });
    });
    
    // Show modal after a short delay
    setTimeout(() => {
        const modal = new bootstrap.Modal(inputModalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        modal.show();
        
        // Focus on amount input when modal is shown
        inputModalElement.addEventListener('shown.bs.modal', function() {
            const amountInput = document.getElementById('qrAmount');
            if (amountInput) {
                amountInput.focus();
                amountInput.select();
            }
        }, { once: true });
    }, 100);
}

function showQRCodeModal(bankCode, accountNumber, bankShortName, bankName, amount, description) {
    try {
        // Generate QR URL using SePay format
        const qrUrl = `https://qr.sepay.vn/img?acc=${accountNumber}&bank=${bankShortName}&amount=${amount}&des=${encodeURIComponent(description)}`;
        
        // Get bank BIN from vendor data
        const vendorBankBin = '{{ $vendor->sepayBank->bin ?? "" }}';
        
        // Create modal HTML
        const modalHtml = `
        <div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="qrCodeModalLabel">
                            <i class="fas fa-qrcode text-primary"></i>
                            QR Thanh toán SePay
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            <div id="qrCodeCanvas" class="border rounded p-3 bg-white d-inline-block"></div>
                        </div>
                        <div class="row text-start">
                            <div class="col-6">
                                <small class="text-muted">Ngân hàng:</small><br>
                                <strong>${bankName}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Số tài khoản:</small><br>
                                <strong>${accountNumber}</strong>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-muted">Số tiền:</small><br>
                                <strong class="text-success">${parseInt(amount).toLocaleString('vi-VN')} VNĐ</strong>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-muted">BIN:</small><br>
                                <code>${vendorBankBin || bankShortName}</code>
                            </div>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">Nội dung:</small><br>
                            <em>${description}</em>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-primary" onclick="downloadQRCode()">
                            <i class="fas fa-download"></i> Tải xuống
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
        // Remove existing modal properly
        const existingModal = document.getElementById('qrCodeModal');
        if (existingModal) {
            // Close modal if it's open
            const existingModalInstance = bootstrap.Modal.getInstance(existingModal);
            if (existingModalInstance) {
                existingModalInstance.hide();
                // Wait for modal to finish hiding before removing
                existingModal.addEventListener('hidden.bs.modal', function() {
                    existingModal.remove();
                }, { once: true });
            } else {
                existingModal.remove();
            }
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Get the new modal element
        const newModalElement = document.getElementById('qrCodeModal');
        
        // Show modal after a short delay to ensure DOM is ready
        setTimeout(() => {
            const modal = new bootstrap.Modal(newModalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modal.show();
            
            // Generate QR code after modal is shown
            generateQRCodeCanvas(qrUrl);
            
            // Ensure focus is on the modal when it's shown
            newModalElement.addEventListener('shown.bs.modal', function() {
                // Focus on the close button or first focusable element
                const closeButton = newModalElement.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.focus();
                }
            }, { once: true });
        }, 100);
        
    } catch (error) {
        Notify.error('Không thể tạo QR Code: ' + error.message);
    }
}

function generateQRCodeCanvas(qrUrl) {
    const qrContainer = document.getElementById('qrCodeCanvas');
    
    // Show loading state
    qrContainer.innerHTML = `
        <div class="d-flex align-items-center justify-content-center" style="height: 200px;">
            <div class="text-center">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div>Đang tạo QR Code...</div>
            </div>
        </div>
    `;
    
    try {
        // Create image element
        const img = document.createElement('img');
        img.src = qrUrl;
        img.alt = 'SePay QR Code';
        img.className = 'img-fluid';
        img.style.maxWidth = '200px';
        img.style.maxHeight = '200px';
        
        // Handle image load
        img.onload = function() {
            qrContainer.innerHTML = '';
            qrContainer.appendChild(img);
            
            // Add QR URL info
            const qrInfo = document.createElement('div');
            qrInfo.className = 'mt-2';
            qrInfo.innerHTML = `
                <small class="text-muted">
                    <i class="fas fa-link"></i> 
                    <a href="${qrUrl}" target="_blank" class="text-decoration-none">
                        Xem QR Code gốc
                    </a>
                </small>
            `;
            qrContainer.appendChild(qrInfo);
        };
        
        // Handle image error
        img.onerror = function() {
            qrContainer.innerHTML = `
                <div class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle fa-3x mb-2"></i>
                    <div>Không thể tạo QR Code</div>
                    <small>Vui lòng kiểm tra thông tin ngân hàng</small>
                </div>
            `;
        };
        
    } catch (error) {
        qrContainer.innerHTML = `
            <div class="text-center text-danger">
                <i class="fas fa-exclamation-triangle fa-3x mb-2"></i>
                <div>Lỗi tạo QR Code</div>
                <small>${error.message}</small>
            </div>
        `;
    }
}

function downloadQRCode() {
    const img = document.querySelector('#qrCodeCanvas img');
    if (img) {
        // Create a link to download the image directly
        const link = document.createElement('a');
        link.href = img.src;
        link.download = `sepay-qr-${Date.now()}.png`;
        link.click();
    } else {
        Notify.error('Không tìm thấy QR Code để tải xuống');
    }
}
</script>
@endpush
@endsection
