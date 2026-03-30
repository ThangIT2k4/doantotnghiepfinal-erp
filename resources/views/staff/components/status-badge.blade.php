@props([
    'status' => null,
    'type' => 'default', // 'viewing', 'booking-deposit', 'lease', 'master_lease', 'invoice', 'company-invoice', 'payment', 'ticket', 'lead', 'unit', 'cash_outflow', 'payroll-cycle', 'payroll-payslip', 'salary-contract', 'salary-advance', 'default'
    'label' => null,
    'class' => null,
    'additionalClass' => null
])

@php
    // Status mappings for different types
    $statusMappings = [
        'viewing' => [
            'requested' => ['label' => 'Yêu cầu', 'class' => 'warning', 'icon' => 'fa-clock'],
            'scheduled' => ['label' => 'Đã lên lịch', 'class' => 'info', 'icon' => 'fa-calendar-check'],
            'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'primary', 'icon' => 'fa-check-circle'],
            'completed' => ['label' => 'Hoàn thành', 'class' => 'success', 'icon' => 'fa-check'],
            'cancelled' => ['label' => 'Hủy', 'class' => 'danger', 'icon' => 'fa-times'],
            'no_show' => ['label' => 'Không đến', 'class' => 'warning', 'icon' => 'fa-user-times'],
        ],
        'booking-deposit' => [
            'pending' => ['label' => 'Chờ xử lý', 'class' => 'warning', 'icon' => 'fa-clock'],
            'pending_approval' => ['label' => 'Chờ duyệt', 'class' => 'warning', 'icon' => 'fa-hourglass-half'],
            'confirmed' => ['label' => 'Đã xác nhận', 'class' => 'success', 'icon' => 'fa-check-circle'],
            'paid' => ['label' => 'Đã thanh toán', 'class' => 'success', 'icon' => 'fa-money-bill-wave'],
            'cancelled' => ['label' => 'Hủy', 'class' => 'danger', 'icon' => 'fa-times'],
            'refunded' => ['label' => 'Hoàn tiền', 'class' => 'info', 'icon' => 'fa-undo'],
        ],
        'lease' => [
            'draft' => ['label' => 'Nháp', 'class' => 'secondary', 'icon' => 'fa-file'],
            'pending' => ['label' => 'Chờ xử lý', 'class' => 'warning', 'icon' => 'fa-clock'],
            'active' => ['label' => 'Đang hoạt động', 'class' => 'success', 'icon' => 'fa-check-circle'],
            'expired' => ['label' => 'Hết hạn', 'class' => 'danger', 'icon' => 'fa-calendar-times'],
            'terminated' => ['label' => 'Chấm dứt', 'class' => 'danger', 'icon' => 'fa-ban'],
            'cancelled' => ['label' => 'Hủy', 'class' => 'secondary', 'icon' => 'fa-times'],
        ],
        'master_lease' => [
            'draft' => ['label' => 'Nháp', 'class' => 'secondary', 'icon' => 'fa-file-alt'],
            'active' => ['label' => 'Hoạt động', 'class' => 'success', 'icon' => 'fa-check-circle'],
            'expired' => ['label' => 'Hết hạn', 'class' => 'danger', 'icon' => 'fa-calendar-times'],
            'terminated' => ['label' => 'Chấm dứt', 'class' => 'warning', 'icon' => 'fa-ban'],
        ],
        'invoice' => [
            'draft' => ['label' => 'Nháp', 'class' => 'secondary', 'icon' => 'fa-file'],
            'pending' => ['label' => 'Chờ duyệt', 'class' => 'warning', 'icon' => 'fa-hourglass-half'],
            'pending_approval' => ['label' => 'Chờ duyệt', 'class' => 'warning', 'icon' => 'fa-hourglass-half'],
            'approved' => ['label' => 'Đã duyệt', 'class' => 'info', 'icon' => 'fa-check'],
            'issued' => ['label' => 'Đã phát hành', 'class' => 'info', 'icon' => 'fa-file-invoice'],
            'paid' => ['label' => 'Đã thanh toán', 'class' => 'success', 'icon' => 'fa-check-circle'],
            'overdue' => ['label' => 'Quá hạn', 'class' => 'danger', 'icon' => 'fa-exclamation-triangle'],
            'cancelled' => ['label' => 'Đã hủy', 'class' => 'warning', 'icon' => 'fa-times'],
        ],
        'company-invoice' => [
            'draft' => ['label' => 'Nháp', 'class' => 'secondary', 'icon' => 'fa-file'],
            'pending' => ['label' => 'Chờ duyệt', 'class' => 'warning', 'icon' => 'fa-hourglass-half'],
            'approved' => ['label' => 'Đã duyệt', 'class' => 'info', 'icon' => 'fa-check'],
            'paid' => ['label' => 'Đã thanh toán', 'class' => 'success', 'icon' => 'fa-check-circle'],
            'overdue' => ['label' => 'Quá hạn', 'class' => 'danger', 'icon' => 'fa-exclamation-triangle'],
            'cancelled' => ['label' => 'Đã hủy', 'class' => 'dark', 'icon' => 'fa-times-circle'],
        ],
        'payment' => [
            'pending' => ['label' => 'Chờ thanh toán', 'class' => 'warning', 'icon' => 'fa-clock'],
            'processing' => ['label' => 'Đang xử lý', 'class' => 'info', 'icon' => 'fa-spinner'],
            'success' => ['label' => 'Thành công', 'class' => 'success', 'icon' => 'fa-check-circle'],
            'failed' => ['label' => 'Thất bại', 'class' => 'danger', 'icon' => 'fa-times'],
            'refunded' => ['label' => 'Đã hoàn tiền', 'class' => 'info', 'icon' => 'fa-undo'],
            'cancelled' => ['label' => 'Hủy', 'class' => 'secondary', 'icon' => 'fa-times'],
        ],
        'ticket' => [
            'open' => ['label' => 'Mở', 'class' => 'info', 'icon' => 'fa-folder-open'],
            'in_progress' => ['label' => 'Đang xử lý', 'class' => 'warning', 'icon' => 'fa-spinner'],
            'resolved' => ['label' => 'Đã giải quyết', 'class' => 'success', 'icon' => 'fa-check'],
            'closed' => ['label' => 'Đã đóng', 'class' => 'secondary', 'icon' => 'fa-times'],
            'cancelled' => ['label' => 'Hủy', 'class' => 'danger', 'icon' => 'fa-ban'],
        ],
        'lead' => [
            'new' => ['label' => 'Mới', 'class' => 'warning', 'icon' => 'fa-star'],
            'contacted' => ['label' => 'Đã liên hệ', 'class' => 'info', 'icon' => 'fa-phone'],
            'qualified' => ['label' => 'Đủ điều kiện', 'class' => 'primary', 'icon' => 'fa-check'],
            'proposal' => ['label' => 'Đề xuất', 'class' => 'secondary', 'icon' => 'fa-file-alt'],
            'negotiation' => ['label' => 'Thương lượng', 'class' => 'warning', 'icon' => 'fa-handshake'],
            'converted' => ['label' => 'Đã chuyển đổi', 'class' => 'success', 'icon' => 'fa-check-circle'],
            'lost' => ['label' => 'Mất khách', 'class' => 'danger', 'icon' => 'fa-times'],
        ],
        'unit' => [
            'available' => ['label' => 'Trống', 'class' => 'success', 'icon' => 'fa-check-circle'],
            'occupied' => ['label' => 'Đã thuê', 'class' => 'primary', 'icon' => 'fa-home'],
            'reserved' => ['label' => 'Đã đặt', 'class' => 'info', 'icon' => 'fa-calendar-check'],
            'maintenance' => ['label' => 'Bảo trì', 'class' => 'warning', 'icon' => 'fa-tools'],
            'unavailable' => ['label' => 'Không khả dụng', 'class' => 'danger', 'icon' => 'fa-ban'],
        ],
        'cash_outflow' => [
            'pending' => ['label' => 'Đang chờ', 'class' => 'warning', 'icon' => 'fa-clock'],
            'success' => ['label' => 'Thành công', 'class' => 'success', 'icon' => 'fa-check-circle'],
            'failed' => ['label' => 'Thất bại', 'class' => 'danger', 'icon' => 'fa-times-circle'],
            'reversed' => ['label' => 'Đã hoàn trả', 'class' => 'info', 'icon' => 'fa-undo'],
        ],
        'payroll-cycle' => [
            'open' => ['label' => 'Mở', 'class' => 'success', 'icon' => 'fa-unlock'],
            'locked' => ['label' => 'Đã khóa', 'class' => 'warning', 'icon' => 'fa-lock'],
            'paid' => ['label' => 'Đã thanh toán', 'class' => 'info', 'icon' => 'fa-check-circle'],
        ],
        'payroll-payslip' => [
            'pending' => ['label' => 'Chờ thanh toán', 'class' => 'warning', 'icon' => 'fa-clock'],
            'paid' => ['label' => 'Đã thanh toán', 'class' => 'success', 'icon' => 'fa-check-circle'],
        ],
        'salary-contract' => [
            'active' => ['label' => 'Đang hoạt động', 'class' => 'success', 'icon' => 'fa-check-circle'],
            'inactive' => ['label' => 'Tạm dừng', 'class' => 'warning', 'icon' => 'fa-pause-circle'],
            'terminated' => ['label' => 'Đã chấm dứt', 'class' => 'danger', 'icon' => 'fa-times-circle'],
        ],
        'salary-advance' => [
            'pending' => ['label' => 'Chờ duyệt', 'class' => 'warning', 'icon' => 'fa-clock'],
            'approved' => ['label' => 'Đã duyệt', 'class' => 'success', 'icon' => 'fa-check-circle'],
            'rejected' => ['label' => 'Đã từ chối', 'class' => 'danger', 'icon' => 'fa-times-circle'],
            'repaid' => ['label' => 'Đã hoàn trả', 'class' => 'info', 'icon' => 'fa-check-double'],
            'partially_repaid' => ['label' => 'Hoàn trả một phần', 'class' => 'primary', 'icon' => 'fa-percent'],
        ],
        'deposit-refund' => [
            'pending' => ['label' => 'Chờ phê duyệt', 'class' => 'warning', 'icon' => 'fa-clock'],
            'approved' => ['label' => 'Đã phê duyệt', 'class' => 'primary', 'icon' => 'fa-check-circle'],
            'paid' => ['label' => 'Đã thanh toán', 'class' => 'success', 'icon' => 'fa-money-bill-wave'],
            'cancelled' => ['label' => 'Đã hủy', 'class' => 'secondary', 'icon' => 'fa-times-circle'],
        ],
    ];

    // Get status config
    $statusConfig = null;
    if ($status && isset($statusMappings[$type]) && isset($statusMappings[$type][$status])) {
        $statusConfig = $statusMappings[$type][$status];
    } elseif ($label && $class) {
        // Use provided label and class
        $statusConfig = ['label' => $label, 'class' => $class, 'icon' => null];
    } else {
        // Default fallback
        $statusConfig = ['label' => ucfirst($status ?? 'N/A'), 'class' => 'secondary', 'icon' => null];
    }

    $badgeClass = $statusConfig['class'] ?? 'secondary';
    $badgeLabel = $statusConfig['label'] ?? ucfirst($status ?? 'N/A');
    $badgeIcon = $statusConfig['icon'] ?? null;
    
    // Combine additional classes if provided
    $spanClasses = 'badge bg-' . $badgeClass;
    if (isset($badgeIcon)) {
        $spanClasses .= ' d-inline-flex align-items-center gap-1';
    }
    if ($additionalClass) {
        $spanClasses .= ' ' . $additionalClass;
    }
@endphp

<span class="{{ $spanClasses }}">
    @if($badgeIcon)
        <i class="fas {{ $badgeIcon }}"></i>
    @endif
    {{ $badgeLabel }}
</span>

