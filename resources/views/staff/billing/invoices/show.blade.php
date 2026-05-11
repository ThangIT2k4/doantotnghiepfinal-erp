@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Hóa đơn')

@section('content')
<main class="main-content">
    <header class="header">
        <div class="header-content">
            <div class="header-info">
                <h1>Chi tiết Hóa đơn</h1>
                <p>Thông tin chi tiết hóa đơn #{{ $invoice->invoice_no ?? $invoice->id }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('staff.invoices.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại
                </a>
                <a href="{{ route('staff.invoices.download', $invoice->id) }}" class="btn btn-outline-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i>
                    Tải PDF
                </a>
                <a href="{{ route('staff.invoices.edit', $invoice->id) }}" class="btn btn-outline-primary">
                    <i class="fas fa-edit"></i>
                    Chỉnh sửa
                </a>
            </div>
        </div>
    </header>
    
    <div class="content" id="content">
        <div class="row">
            <!-- Invoice Details -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-invoice me-2"></i>
                            Thông tin hóa đơn
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Số hóa đơn:</strong></td>
                                        <td>
                                            @if ($invoice->invoice_no)
                                                <code class="bg-light px-2 py-1 rounded">{{ $invoice->invoice_no }}</code>
                                            @else
                                                <span class="text-muted">Chưa có</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Loại hóa đơn:</strong></td>
                                        <td>
                                            @if ($invoice->invoice_type)
                                                @php
                                                    $typeColors = [
                                                        'monthly_rent' => 'primary',
                                                        'first_invoice' => 'success',
                                                        'booking_deposit' => 'warning',
                                                        'other' => 'secondary'
                                                    ];
                                                    $color = $typeColors[$invoice->invoice_type] ?? 'secondary';
                                                @endphp
                                                <span class="badge bg-{{ $color }}">{{ $invoice->getInvoiceTypeLabel() }}</span>
                                            @else
                                                <span class="text-muted">Không xác định</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ngày phát hành:</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Hạn thanh toán:</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Trạng thái:</strong></td>
                                        <td>
                                            @php
                                            $statusClass = '';
                                            switch($invoice->status) {
                                            case 'draft': $statusClass = 'bg-secondary'; break;
                                            case 'issued': $statusClass = 'bg-info'; break;
                                            case 'paid': $statusClass = 'bg-success'; break;
                                            case 'overdue': $statusClass = 'bg-danger'; break;
                                            case 'cancelled': $statusClass = 'bg-warning'; break;
                                            default: $statusClass = 'bg-secondary'; break;
                                            }
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ ucfirst($invoice->status) }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Tiền tệ:</strong></td>
                                        <td>{{ $invoice->currency ?? 'VND' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tổng tiền trước thuế:</strong></td>
                                        <td>{{ number_format($invoice->subtotal, 0, ',', '.') }} VND</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Thuế:</strong></td>
                                        <td>{{ number_format($invoice->tax_amount, 0, ',', '.') }} VND</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Giảm giá:</strong></td>
                                        <td>{{ number_format($invoice->discount_amount, 0, ',', '.') }} VND</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tổng tiền:</strong></td>
                                        <td><strong class="text-primary">{{ number_format($invoice->total_amount, 0, ',', '.') }} VND</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        @if ($invoice->note)
                        <div class="mt-3">
                            <strong>Ghi chú:</strong>
                            <p class="text-muted">{{ $invoice->note }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Invoice Items -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            Chi tiết các khoản
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Loại</th>
                                        <th>Mô tả</th>
                                        <th>Số lượng</th>
                                        <th>Đơn giá</th>
                                        <th>Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoice->items as $item)
                                    <tr>
                                        <td>
                                            @php
                                            $typeLabels = [
                                                'rent' => 'Tiền thuê',
                                                'service' => 'Dịch vụ',
                                                'meter' => 'Đồng hồ',
                                                'deposit' => 'Cọc',
                                                'other' => 'Khác'
                                            ];
                                            @endphp
                                            <span class="badge bg-info">{{ $typeLabels[$item->item_type] ?? $item->item_type }}</span>
                                        </td>
                                        <td>{{ $item->description }}</td>
                                        <td>{{ (int)$item->quantity }}</td>
                                        <td>{{ number_format($item->unit_price, 0, ',', '.') }} VND</td>
                                        <td><strong>{{ number_format($item->amount, 0, ',', '.') }} VND</strong></td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Không có khoản nào</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Payments -->
                @if($invoice->payments->count() > 0)
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-credit-card me-2"></i>
                            Lịch sử thanh toán
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ngày thanh toán</th>
                                        <th>Số tiền</th>
                                        <th>Phương thức</th>
                                        <th>Trạng thái</th>
                                        <th>Người thanh toán</th>
                                        <th>Ghi chú</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->payments as $payment)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y H:i') }}</td>
                                        <td><strong>{{ number_format($payment->amount, 0, ',', '.') }} VND</strong></td>
                                        <td>{{ $payment->method->name ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                            $statusClass = '';
                                            switch($payment->status) {
                                            case 'pending': $statusClass = 'bg-warning'; break;
                                            case 'success': $statusClass = 'bg-success'; break;
                                            case 'failed': $statusClass = 'bg-danger'; break;
                                            case 'refunded': $statusClass = 'bg-info'; break;
                                            default: $statusClass = 'bg-secondary'; break;
                                            }
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ ucfirst($payment->status) }}</span>
                                        </td>
                                        <td>{{ $payment->payerUser->full_name ?? 'N/A' }}</td>
                                        <td>{{ $payment->note ?? '-' }}</td>
                                        <td>
                                            <a href="{{ route('staff.payments.show', $payment->id) }}" class="btn btn-sm btn-info" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i> Xem
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                @if(isset($qrUrl) && in_array($invoice->status, ['issued', 'overdue']))
                {{-- Card Thanh toán QR SePay --}}
                <div class="card shadow-sm mb-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-qrcode me-2"></i>Mã QR Thanh toán (SePay)
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <p class="text-muted small mb-2">Quét mã QR để tự động xác nhận thanh toán</p>
                        <div class="p-2 bg-light rounded d-inline-block mb-3 border">
                            <img src="{{ $qrUrl }}" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                        </div>
                        <div class="text-start p-2 bg-light rounded mb-3 small">
                            <div><strong>Ngân hàng:</strong> {{ $bankConfig['bank_name'] }}</div>
                            <div><strong>STK:</strong> {{ $bankConfig['account_number'] }}</div>
                            <div><strong>Chủ TK:</strong> {{ $bankConfig['account_name'] }}</div>
                            <div><strong>Số tiền:</strong> <span class="text-danger fw-bold">{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</span></div>
                            <div><strong>Nội dung:</strong> <span class="text-primary fw-bold">{{ $qrContent }}</span></div>
                        </div>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="copyQrLink('{{ $qrUrl }}')">
                                <i class="fas fa-copy me-1"></i> Copy Link QR
                            </button>
                            <a href="{{ $qrUrl }}" target="_blank" download class="btn btn-outline-success btn-sm">
                                <i class="fas fa-download me-1"></i> Mở hình QR
                            </a>
                        </div>
                    </div>
                </div>
                <script>
                    function copyQrLink(url) {
                        navigator.clipboard.writeText(url).then(function() {
                            if(typeof Notify !== 'undefined') {
                                Notify.success('Đã copy đường dẫn QR thành công!');
                            } else {
                                alert('Đã copy đường dẫn QR thành công!');
                            }
                        });
                    }
                </script>
                @endif

                <!-- Actions -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            Thao tác
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
                                    'url' => route('staff.invoices.edit', $invoice->id),
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteInvoice({$invoice->id}, '" . addslashes($invoice->invoice_no ?? 'Hóa đơn #' . $invoice->id) . "')",
                                    'class' => 'w-100'
                                ],
                                [
                                    'type' => 'link',
                                    'variant' => 'secondary',
                                    'label' => 'Quay lại',
                                    'icon' => 'fas fa-arrow-left',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.invoices.index'),
                                    'class' => 'w-100'
                                ]
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            // Allowed transitions based on current status
                            $allowedTransitions = [
                                'draft' => ['issued', 'cancelled'],
                                'issued' => ['paid', 'overdue', 'cancelled'],
                                'paid' => [], // Cannot change from paid
                                'overdue' => ['paid', 'cancelled'],
                                'cancelled' => [], // Cannot change from cancelled
                            ];
                            
                            $statusLabels = [
                                'draft' => 'Nháp',
                                'issued' => 'Phát hành',
                                'paid' => 'Thanh toán',
                                'overdue' => 'Quá hạn',
                                'cancelled' => 'Hủy',
                            ];
                            
                            $statusVariants = [
                                'draft' => 'secondary',
                                'issued' => 'info',
                                'paid' => 'success',
                                'overdue' => 'danger',
                                'cancelled' => 'warning',
                            ];
                            
                            $statusIcons = [
                                'draft' => 'fa-file-alt',
                                'issued' => 'fa-file-invoice',
                                'paid' => 'fa-check-circle',
                                'overdue' => 'fa-exclamation-triangle',
                                'cancelled' => 'fa-times',
                            ];
                            
                            foreach ($allowedTransitions[$invoice->status] ?? [] as $newStatus) {
                                // Thay thế nút "paid" bằng nút "Tạo thanh toán"
                                if ($newStatus === 'paid') {
                                    $statusActions[] = [
                                        'type' => 'link',
                                        'variant' => 'success',
                                        'label' => 'Tạo thanh toán',
                                        'icon' => 'fas fa-money-bill-wave',
                                        'iconPosition' => 'left',
                                        'url' => route('staff.payments.create', ['invoice_id' => $invoice->id]),
                                        'class' => 'w-100'
                                    ];
                                } else {
                                    $statusActions[] = [
                                        'type' => 'button',
                                        'variant' => $statusVariants[$newStatus] ?? 'secondary',
                                        'label' => $statusLabels[$newStatus] ?? $newStatus,
                                        'icon' => 'fas ' . ($statusIcons[$newStatus] ?? 'fa-exchange-alt'),
                                        'iconPosition' => 'left',
                                        'onclick' => "updateInvoiceStatus('{$newStatus}')",
                                        'class' => 'w-100'
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

                <!-- Lease Information -->
                @if($invoice->lease)
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-contract me-2"></i>
                            Thông tin hợp đồng
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td><strong>Số hợp đồng:</strong></td>
                                <td>{{ $invoice->lease->contract_no ?? 'HD#' . $invoice->lease->id }}</td>
                            </tr>
                            <tr>
                                <td><strong>Khách thuê:</strong></td>
                                <td>{{ $invoice->lease->tenant->full_name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Bất động sản:</strong></td>
                                <td>{{ $invoice->lease->unit->property->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Phòng:</strong></td>
                                <td>{{ $invoice->lease->unit->code ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Tiền thuê:</strong></td>
                                <td>{{ number_format($invoice->lease->rent_amount, 0, ',', '.') }} VND</td>
                            </tr>
                            <tr>
                                <td><strong>Thời hạn:</strong></td>
                                <td>
                                    {{ \Carbon\Carbon::parse($invoice->lease->start_date)->format('d/m/Y') }} - 
                                    {{ \Carbon\Carbon::parse($invoice->lease->end_date)->format('d/m/Y') }}
                                </td>
                            </tr>
                        </table>
                        <div class="mt-3">
                            <a href="{{ route('staff.leases.show', $invoice->lease->id) }}" class="btn btn-sm btn-outline-primary w-100">
                                <i class="fas fa-eye"></i> Xem chi tiết hợp đồng
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Booking Deposit Information -->
                @if($invoice->bookingDeposit)
                <div class="card shadow-sm {{ $invoice->lease ? 'mb-4' : '' }}">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-hand-holding-usd me-2"></i>
                            Thông tin đặt cọc
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td><strong>Mã đặt cọc:</strong></td>
                                <td>
                                    <span class="badge bg-primary">{{ $invoice->bookingDeposit->reference_number ?? 'BD#' . $invoice->bookingDeposit->id }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Khách hàng:</strong></td>
                                <td>
                                    @if($invoice->bookingDeposit->tenantUser)
                                        {{ $invoice->bookingDeposit->tenantUser->full_name ?? 'N/A' }}
                                        @if($invoice->bookingDeposit->tenantUser->phone)
                                            <br><small class="text-muted">{{ $invoice->bookingDeposit->tenantUser->phone }}</small>
                                        @endif
                                    @elseif($invoice->bookingDeposit->lead)
                                        {{ $invoice->bookingDeposit->lead->name }}
                                        @if($invoice->bookingDeposit->lead->phone)
                                            <br><small class="text-muted">{{ $invoice->bookingDeposit->lead->phone }}</small>
                                        @endif
                                        <br><small class="badge bg-warning">Lead</small>
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Bất động sản:</strong></td>
                                <td>{{ $invoice->bookingDeposit->unit->property->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Phòng:</strong></td>
                                <td>{{ $invoice->bookingDeposit->unit->code ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Số tiền đặt cọc:</strong></td>
                                <td><strong class="text-primary">{{ number_format($invoice->bookingDeposit->amount, 0, ',', '.') }} VND</strong></td>
                            </tr>
                            <tr>
                                <td><strong>Loại đặt cọc:</strong></td>
                                <td>
                                    @switch($invoice->bookingDeposit->deposit_type)
                                        @case('booking')
                                            <span class="badge bg-primary">Đặt cọc</span>
                                            @break
                                        @case('security')
                                            <span class="badge bg-info">Cọc an ninh</span>
                                            @break
                                        @case('advance')
                                            <span class="badge bg-warning">Trả trước</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $invoice->bookingDeposit->deposit_type }}</span>
                                    @endswitch
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Trạng thái:</strong></td>
                                <td>
                                    @switch($invoice->bookingDeposit->payment_status)
                                        @case('pending_approval')
                                            <span class="badge bg-warning">Chờ duyệt</span>
                                            @break
                                        @case('pending')
                                            <span class="badge bg-warning">Chờ thanh toán</span>
                                            @break
                                        @case('paid')
                                            <span class="badge bg-success">Đã thanh toán</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-danger">Đã hủy</span>
                                            @break
                                        @case('refunded')
                                            <span class="badge bg-secondary">Hoàn tiền</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ $invoice->bookingDeposit->payment_status }}</span>
                                    @endswitch
                                </td>
                            </tr>
                            @if($invoice->bookingDeposit->hold_until)
                            <tr>
                                <td><strong>Giữ chỗ đến:</strong></td>
                                <td>{{ \Carbon\Carbon::parse($invoice->bookingDeposit->hold_until)->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endif
                            @if($invoice->bookingDeposit->agent)
                            <tr>
                                <td><strong>Nhân viên:</strong></td>
                                <td>{{ $invoice->bookingDeposit->agent->userProfile->full_name ?? $invoice->bookingDeposit->agent->full_name ?? 'N/A' }}</td>
                            </tr>
                            @endif
                        </table>
                        <div class="mt-3">
                            <a href="{{ route('staff.booking-deposits.show', $invoice->bookingDeposit->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Xem chi tiết đặt cọc
                            </a>
                        </div>
                    </div>
                </div>
                @endif

               
            </div>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
function updateInvoiceStatus(newStatus) {
    const statusLabels = {
        'draft': 'Nháp',
        'issued': 'Phát hành',
        'paid': 'Thanh toán',
        'overdue': 'Quá hạn',
        'cancelled': 'Hủy'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển sang trạng thái "${statusLabels[newStatus]}"?`,
        type: newStatus === 'cancelled' ? 'danger' : 'warning',
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
            
            fetch('{{ route("staff.invoices.update-status", $invoice->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
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

function deleteInvoice(id, name) {
    Notify.confirmDelete(`hóa đơn "${name}"`, function() {
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });
        
        fetch(`/staff/invoices/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
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
                setTimeout(() => {
                    window.location.href = '{{ route("staff.invoices.index") }}';
                }, 1500);
            } else {
                Notify.error(data.message, 'Không thể xóa hóa đơn');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi xóa hóa đơn. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
    });
}

function markAsPaid(id) {
    Notify.confirm({
        title: 'Đánh dấu đã thanh toán',
        message: 'Bạn có chắc chắn muốn đánh dấu hóa đơn này là đã thanh toán?',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const loadingToast = Notify.toast({
                title: 'Đang xử lý...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });
            
            fetch(`/staff/invoices/${id}/mark-paid`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
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
                    Notify.success(data.message, 'Thành công!');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Có lỗi xảy ra khi đánh dấu hóa đơn. Vui lòng thử lại.', 'Lỗi hệ thống');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    });
}

function issueInvoice(id) {
    Notify.confirm({
        title: 'Phát hành hóa đơn',
        message: 'Bạn có chắc chắn muốn phát hành hóa đơn này?',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            const loadingToast = Notify.toast({
                title: 'Đang xử lý...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });
            
            fetch(`/staff/invoices/${id}/issue`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
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
                    Notify.success(data.message, 'Thành công!');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Có lỗi xảy ra khi phát hành hóa đơn. Vui lòng thử lại.', 'Lỗi hệ thống');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    });
}
</script>
@endpush
