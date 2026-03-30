@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Hóa đơn Công ty')

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
        {{-- 1. Page Header với breadcrumbs --}}
        @php
            $headerActions = [];
            // Add approve button to header if status is pending
            if($companyInvoice->status === 'pending') {
                $headerActions[] = [
                    'type' => 'button',
                    'color' => 'success',
                    'label' => 'Duyệt hóa đơn',
                    'icon' => 'fas fa-check-circle',
                    'onclick' => "approveInvoice({$companyInvoice->id})"
                ];
            }
        @endphp
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết Hóa đơn Công ty',
            'subtitle' => 'Thông tin chi tiết về hóa đơn công ty: ' . $companyInvoice->invoice_no,
            'icon' => 'fas fa-file-invoice',
            'breadcrumbs' => [
                ['label' => 'Hóa đơn Công ty', 'url' => route('staff.company-invoices.index')],
                ['label' => $companyInvoice->invoice_no, 'active' => true]
            ],
            'actions' => $headerActions
        ])

        {{-- 2. Content --}}
    <div class="row">
            {{-- Nội dung chính --}}
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin chi tiết
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Thông tin hóa đơn</h5>
                                
                                <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Số hóa đơn:</strong></td>
                                            <td>{{ $companyInvoice->invoice_no }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Người nhận:</strong></td>
                                            <td>
                                                @if($companyInvoice->vendor_id)
                                                    <span class="badge bg-info me-1">Nhà cung cấp</span>
                                                    {{ $companyInvoice->vendor->name ?? 'N/A' }}
                                                @elseif($companyInvoice->user_id)
                                                    <span class="badge bg-success me-1">Người dùng</span>
                                                    {{ $companyInvoice->user->full_name ?? 'N/A' }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Loại hóa đơn:</strong></td>
                                            <td>
                                                @php
                                                    $types = [
                                                        'master_lease' => 'Hợp đồng tổng',
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
                                                <span class="badge badge-info">{{ $types[$companyInvoice->invoice_type] ?? $companyInvoice->invoice_type }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Ngày phát hành:</strong></td>
                                            <td>{{ $companyInvoice->issue_date->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Ngày đến hạn:</strong></td>
                                            <td>{{ $companyInvoice->due_date->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Trạng thái:</strong></td>
                                            <td>
                                                @include('staff.components.status-badge', [
                                                    'status' => $companyInvoice->status,
                                                    'type' => 'company-invoice'
                                                ])
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Người tạo:</strong></td>
                                            <td>{{ $companyInvoice->creator->full_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Ngày tạo:</strong></td>
                                            <td>{{ $companyInvoice->created_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h5 class="mb-3">Thông tin tài chính</h5>
                                
                                <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Tổng tiền trước thuế:</strong></td>
                                            <td>{{ number_format($companyInvoice->subtotal, 0, ',', '.') }} {{ $companyInvoice->currency }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Số tiền thuế:</strong></td>
                                            <td>{{ number_format($companyInvoice->tax_amount, 0, ',', '.') }} {{ $companyInvoice->currency }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Số tiền giảm giá:</strong></td>
                                            <td>{{ number_format($companyInvoice->discount_amount, 0, ',', '.') }} {{ $companyInvoice->currency }}</td>
                                        </tr>
                                        <tr class="table-active">
                                            <td><strong>Tổng tiền thanh toán:</strong></td>
                                            <td><strong>{{ number_format($companyInvoice->total_amount, 0, ',', '.') }} {{ $companyInvoice->currency }}</strong></td>
                                        </tr>
                                </table>
                            </div>
                        </div>

                        @if(($companyInvoice->items && $companyInvoice->items->count() > 0))
                            <hr>
                            <h5 class="mb-3">Chi tiết các khoản (companyInvoices-item)</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%">Loại</th>
                                            <th>Mô tả</th>
                                            <th class="text-end" style="width: 12%">Số lượng</th>
                                            <th class="text-end" style="width: 16%">Đơn giá</th>
                                            <th class="text-end" style="width: 16%">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($companyInvoice->items as $item)
                                            <tr>
                                                <td><span class="badge bg-secondary text-uppercase">{{ $item->item_type }}</span></td>
                                                <td>{{ $item->description }}</td>
                                                <td class="text-end">{{ number_format($item->quantity, 3, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format($item->unit_price, 0, ',', '.') }} {{ $companyInvoice->currency }}</td>
                                                <td class="text-end"><strong>{{ number_format($item->amount, 0, ',', '.') }} {{ $companyInvoice->currency }}</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        <!-- Description and Notes -->
                        @if($companyInvoice->description || $companyInvoice->note)
                            <hr>
                            <div class="row">
                                @if($companyInvoice->description)
                                    <div class="col-md-6">
                                        <h6>Mô tả</h6>
                                        <p class="text-muted">{{ $companyInvoice->description }}</p>
                                    </div>
                                @endif
                                @if($companyInvoice->note)
                                    <div class="col-md-6">
                                        <h6>Ghi chú</h6>
                                        <p class="text-muted">{{ $companyInvoice->note }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Source Information -->
                        @if($companyInvoice->source_type)
                            <hr>
                            <h6>Thông tin nguồn</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>Loại nguồn:</strong></td>
                                            <td>{{ $companyInvoice->source_type }}</td>
                                        </tr>
                                        @if($companyInvoice->masterLease)
                                            <tr>
                                                <td><strong>Hợp đồng tổng:</strong></td>
                                                <td>{{ $companyInvoice->masterLease->contract_no }} - {{ $companyInvoice->masterLease->property->name ?? 'N/A' }}</td>
                                            </tr>
                                        @endif
                                        @if($companyInvoice->ticket)
                                            <tr>
                                                <td><strong>Ticket:</strong></td>
                                                <td>#{{ $companyInvoice->ticket->id }} - {{ $companyInvoice->ticket->unit->property->name ?? 'N/A' }}</td>
                                            </tr>
                                        @endif
                                        @if($companyInvoice->depositRefund)
                                            <tr>
                                                <td><strong>Hoàn tiền cọc:</strong></td>
                                                <td>{{ $companyInvoice->depositRefund->lease->tenant->full_name ?? 'N/A' }} - {{ $companyInvoice->depositRefund->lease->unit->property->name ?? 'N/A' }}</td>
                                            </tr>
                                        @endif
                                        @if($companyInvoice->payrollPayslip)
                                            <tr>
                                                <td><strong>Phiếu lương:</strong></td>
                                                <td>{{ $companyInvoice->payrollPayslip->user->full_name ?? 'N/A' }} - Tháng {{ $companyInvoice->payrollPayslip->payrollCycle->period_month ?? 'N/A' }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        @endif

                        <!-- Attachment -->
                        @php
                            $attachments = $companyInvoice->documents;
                            $imageAttachments = $attachments->filter(function($doc) {
                                return in_array($doc->document_type, ['image']) || 
                                       in_array(strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            });
                            $otherAttachments = $attachments->filter(function($doc) {
                                return !in_array($doc->document_type, ['image']) && 
                                       !in_array(strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            });
                        @endphp
                        @if($imageAttachments->count() > 0 || $otherAttachments->count() > 0 || $companyInvoice->attachment_url)
                            <hr>
                            <h6>Tài liệu đính kèm</h6>
                            
                            @if($imageAttachments->count() > 0)
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-2">Ảnh đính kèm:</label>
                                    <div class="row">
                                        @foreach($imageAttachments as $attachment)
                                            @php
                                                $rawFileUrl = $attachment->getRawOriginal('file_url');
                                                $imageUrl = str_starts_with($rawFileUrl, 'http://') || str_starts_with($rawFileUrl, 'https://')
                                                    ? $rawFileUrl
                                                    : asset('storage/' . ltrim($rawFileUrl, '/'));
                                            @endphp
                                            <div class="col-md-4 mb-3">
                                                <div class="p-2 bg-light rounded">
                                                    <a href="{{ $imageUrl }}" target="_blank" class="d-block text-center">
                                                        <img src="{{ $imageUrl }}" alt="{{ $attachment->file_name }}" 
                                                             style="max-width: 100%; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px; cursor: pointer;">
                                                    </a>
                                                    <small class="text-muted d-block text-center mt-2">{{ $attachment->file_name }}</small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            @if($otherAttachments->count() > 0)
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted mb-2">Tài liệu khác:</label>
                                    <div class="list-group">
                                        @foreach($otherAttachments as $attachment)
                                            @php
                                                $rawFileUrl = $attachment->getRawOriginal('file_url');
                                                $fileUrl = str_starts_with($rawFileUrl, 'http://') || str_starts_with($rawFileUrl, 'https://')
                                                    ? $rawFileUrl
                                                    : asset('storage/' . ltrim($rawFileUrl, '/'));
                                            @endphp
                                            <a href="{{ $fileUrl }}" target="_blank" class="list-group-item list-group-item-action">
                                                <i class="fas fa-file me-2"></i>{{ $attachment->file_name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            @if($companyInvoice->attachment_url && $attachments->count() == 0)
                                <a href="{{ $companyInvoice->attachment_url }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-external-link-alt"></i> Xem tài liệu
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Card "Thao tác" bên phải --}}
            <div class="col-lg-4">
                {{-- Card "Thao tác" với action-buttons --}}
                <div class="card shadow-sm">
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
                                    'url' => route('staff.company-invoices.edit', $companyInvoice->id),
                                    'class' => 'w-100'
                                ],
                            ];
                            
                            if($companyInvoice->cashOutflows()->count() === 0) {
                                $primaryActions[] = [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteInvoice({$companyInvoice->id}, '" . addslashes($companyInvoice->invoice_no) . "')",
                                    'class' => 'w-100'
                                ];
                            }
                            
                            $primaryActions[] = [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Quay lại',
                                'icon' => 'fas fa-arrow-left',
                                'iconPosition' => 'left',
                                'url' => route('staff.company-invoices.index'),
                                'class' => 'w-100'
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            // Nếu status là draft, cho phép chuyển sang các trạng thái khác
                            if($companyInvoice->status === 'draft') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'info',
                                    'label' => 'Chuyển sang Chờ duyệt',
                                    'icon' => 'fas fa-clock',
                                    'onclick' => "updateInvoiceStatus({$companyInvoice->id}, 'pending')"
                                ];
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Chuyển sang Đã duyệt',
                                    'icon' => 'fas fa-check-circle',
                                    'onclick' => "updateInvoiceStatus({$companyInvoice->id}, 'approved')"
                                ];
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Hủy hóa đơn',
                                    'icon' => 'fas fa-times-circle',
                                    'onclick' => "cancelInvoice({$companyInvoice->id})"
                                ];
                            }
                            
                            // Nếu status là cancelled, cho phép chuyển sang các trạng thái khác
                            if($companyInvoice->status === 'cancelled') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'secondary',
                                    'label' => 'Chuyển về Nháp',
                                    'icon' => 'fas fa-edit',
                                    'onclick' => "updateInvoiceStatus({$companyInvoice->id}, 'draft')"
                                ];
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'info',
                                    'label' => 'Chuyển về Chờ duyệt',
                                    'icon' => 'fas fa-clock',
                                    'onclick' => "updateInvoiceStatus({$companyInvoice->id}, 'pending')"
                                ];
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Chuyển về Đã duyệt',
                                    'icon' => 'fas fa-check-circle',
                                    'onclick' => "updateInvoiceStatus({$companyInvoice->id}, 'approved')"
                                ];
                            }
                            
                            if($companyInvoice->status === 'pending') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Duyệt hóa đơn',
                                    'icon' => 'fas fa-check-circle',
                                    'onclick' => "approveInvoice({$companyInvoice->id})"
                                ];
                            }
                            
                            if($companyInvoice->status === 'approved') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'warning',
                                    'label' => 'Đánh dấu quá hạn',
                                    'icon' => 'fas fa-exclamation-triangle',
                                    'onclick' => "markOverdue({$companyInvoice->id})"
                                ];
                            }
                            
                            if(!in_array($companyInvoice->status, ['paid', 'cancelled', 'draft'])) {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Hủy hóa đơn',
                                    'icon' => 'fas fa-times-circle',
                                    'onclick' => "cancelInvoice({$companyInvoice->id})"
                                ];
                            }
                            
                            if($companyInvoice->status !== 'paid') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'primary',
                                    'label' => 'Đánh dấu đã thanh toán',
                                    'icon' => 'fas fa-money-bill-wave',
                                    'onclick' => "markAsPaid({$companyInvoice->id})"
                                ];
                            }
                            
                            if(in_array($companyInvoice->status, ['approved', 'pending', 'overdue']) && $companyInvoice->status !== 'paid') {
                                $statusActions[] = [
                                    'type' => 'link',
                                    'variant' => 'success',
                                    'label' => 'Thanh toán',
                                    'icon' => 'fas fa-credit-card',
                                    'url' => route('staff.company-invoices.payment', $companyInvoice->id)
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

                <!-- Payment History -->
                @if($companyInvoice->cashOutflows()->count() > 0)
                    <div class="card shadow-sm mt-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Lịch sử thanh toán</h6>
                        </div>
                        <div class="card-body">
                            @foreach($companyInvoice->cashOutflows as $outflow)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <small class="text-muted">{{ $outflow->paid_at->format('d/m/Y') }}</small><br>
                                        <strong>{{ number_format($outflow->amount, 0, ',', '.') }} {{ $companyInvoice->currency }}</strong>
                                    </div>
                                    <div class="text-right">
                                        <span class="badge badge-success">{{ $outflow->paymentMethod->name ?? 'N/A' }}</span><br>
                                        @if($outflow->txn_ref)
                                            <small class="text-muted">{{ $outflow->txn_ref }}</small>
                                        @endif
                                        <br>
                                        <a href="{{ route('staff.cash-outflows.show', $outflow->id) }}" class="btn btn-sm btn-info mt-1" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i> Xem
                                        </a>
                                    </div>
                                </div>
                                @if(!$loop->last)
                                    <hr class="my-2">
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đánh dấu đã thanh toán</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="payment-form">
                <div class="modal-body">
                    <input type="hidden" id="payment-invoice-id">
                    <div class="form-group">
                        <label>Phương thức thanh toán <span class="text-danger">*</span></label>
                        <select name="payment_method_id" class="form-control" required>
                            <option value="">Chọn phương thức thanh toán</option>
                            @foreach(\App\Models\PaymentMethod::all() as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày thanh toán <span class="text-danger">*</span></label>
                        <input type="date" name="paid_at" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Mã giao dịch</label>
                        <input type="text" name="txn_ref" class="form-control" placeholder="Mã giao dịch">
                    </div>
                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Ghi chú về thanh toán"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Xác nhận thanh toán</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
// Toast notification function
function showToast(message, type = 'success') {
    if (type === 'success') {
        Notify.success(message, 'Thành công!');
    } else if (type === 'error') {
        Notify.error(message, 'Lỗi!');
    } else if (type === 'warning') {
        Notify.warning(message, 'Cảnh báo!');
    } else {
        Notify.info(message, 'Thông tin');
    }
}

// Individual actions
function approveInvoice(invoiceId) {
    Notify.confirm({
        title: 'Xác nhận duyệt hóa đơn',
        message: 'Bạn có chắc chắn muốn duyệt hóa đơn này?',
        type: 'info',
        confirmText: 'Duyệt',
        onConfirm: function() {
            $.ajax({
                url: `/staff/company-invoices/${invoiceId}/approve`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showToast(response.message, 'success');
                        location.reload();
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showToast(response?.message || 'Có lỗi xảy ra', 'error');
                }
            });
        }
    });
}

function cancelInvoice(invoiceId) {
    if (confirm('Bạn có chắc chắn muốn hủy hóa đơn này?')) {
        $.ajax({
            url: `/staff/company-invoices/${invoiceId}/cancel`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    location.reload();
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showToast(response?.message || 'Có lỗi xảy ra', 'error');
            }
        });
    }
}

function markOverdue(invoiceId) {
    if (confirm('Bạn có chắc chắn muốn đánh dấu hóa đơn này là quá hạn?')) {
        $.ajax({
            url: `/staff/company-invoices/${invoiceId}/mark-overdue`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    location.reload();
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showToast(response?.message || 'Có lỗi xảy ra', 'error');
            }
        });
    }
}

function markAsPaid(invoiceId) {
    $('#payment-invoice-id').val(invoiceId);
    $('#paymentModal').modal('show');
}

function deleteInvoice(invoiceId) {
    if (confirm('Bạn có chắc chắn muốn xóa hóa đơn này? Hành động này không thể hoàn tác.')) {
        $.ajax({
            url: `/staff/company-invoices/${invoiceId}`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    window.location.href = '{{ route("staff.company-invoices.index") }}';
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showToast(response?.message || 'Có lỗi xảy ra', 'error');
            }
        });
    }
}

// Update invoice status (for cancelled status transitions)
function updateInvoiceStatus(invoiceId, newStatus) {
    const statusLabels = {
        'draft': 'Nháp',
        'pending': 'Chờ duyệt',
        'approved': 'Đã duyệt',
        'overdue': 'Quá hạn',
        'paid': 'Đã thanh toán',
        'cancelled': 'Đã hủy'
    };
    
    const statusLabel = statusLabels[newStatus] || newStatus;
    
    Notify.confirm({
        title: 'Xác nhận chuyển trạng thái',
        message: `Bạn có chắc chắn muốn chuyển hóa đơn sang trạng thái "${statusLabel}"?`,
        type: 'info',
        confirmText: 'Xác nhận',
        onConfirm: function() {
            $.ajax({
                url: `/staff/company-invoices/${invoiceId}/update-status`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        showToast(response.message, 'success');
                        location.reload();
                    } else {
                        showToast(response.message, 'error');
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showToast(response?.message || 'Có lỗi xảy ra', 'error');
                }
            });
        }
    });
}

// Payment form submission
$('#payment-form').on('submit', function(e) {
    e.preventDefault();
    
    const invoiceId = $('#payment-invoice-id').val();
    const formData = $(this).serialize();
    
    $.ajax({
        url: `/staff/company-invoices/${invoiceId}/mark-paid`,
        method: 'POST',
        data: formData + '&_token={{ csrf_token() }}',
        success: function(response) {
            if (response.success) {
                showToast(response.message, 'success');
                $('#paymentModal').modal('hide');
                location.reload();
            } else {
                showToast(response.message, 'error');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            showToast(response?.message || 'Có lỗi xảy ra', 'error');
        }
    });
});
</script>
@endpush

@push('styles')
<style>
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.table-borderless td {
    border: none;
    padding: 0.5rem 0;
}

.table-active {
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75em;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}
</style>
@endpush
