@extends('layouts.staff_dashboard')

@section('title', 'Button & Status Badge Preview')

@include('staff.components.button-styles')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">
                <i class="fas fa-palette me-2"></i>Xem trước Buttons & Status Badges
            </h1>
            <p class="text-muted mb-4">Xem trước tất cả các buttons và status badges có sẵn trong hệ thống</p>

            <!-- Buttons Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-mouse-pointer me-2"></i>Buttons
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Button Variants -->
                    <div class="mb-4">
                        <h6 class="mb-3">Các loại Button</h6>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'primary', 'label' => 'Primary'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'secondary', 'label' => 'Secondary'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'success', 'label' => 'Success'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'danger', 'label' => 'Danger'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'warning', 'label' => 'Warning'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'info', 'label' => 'Info'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'light', 'label' => 'Light'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'dark', 'label' => 'Dark'])
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'outline-primary', 'label' => 'Outline Primary'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'outline-secondary', 'label' => 'Outline Secondary'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'outline-success', 'label' => 'Outline Success'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'outline-danger', 'label' => 'Outline Danger'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'outline-warning', 'label' => 'Outline Warning'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'outline-info', 'label' => 'Outline Info'])
                        </div>
                    </div>

                    <!-- Button Sizes -->
                    <div class="mb-4">
                        <h6 class="mb-3">Kích thước Button</h6>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'primary', 'size' => 'sm', 'label' => 'Nhỏ'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'primary', 'size' => 'md', 'label' => 'Vừa'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'primary', 'size' => 'lg', 'label' => 'Lớn'])
                        </div>
                    </div>

                    <!-- Buttons with Icons -->
                    <div class="mb-4">
                        <h6 class="mb-3">Buttons có Icon</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'primary', 'label' => 'Chỉnh sửa', 'icon' => 'fas fa-edit'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'success', 'label' => 'Lưu', 'icon' => 'fas fa-save'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'danger', 'label' => 'Xóa', 'icon' => 'fas fa-trash'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'info', 'label' => 'Xem', 'icon' => 'fas fa-eye'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'warning', 'label' => 'Cảnh báo', 'icon' => 'fas fa-exclamation-triangle'])
                        </div>
                    </div>

                    <!-- Icon Only Buttons (Flex Style - như Invoices Index) -->
                    <div class="mb-4">
                        <h6 class="mb-3">Icon Only Buttons (Flex Style - như Invoices Index)</h6>
                        <div class="mb-3">
                            <p class="text-muted small mb-2">Flex Style (đồng bộ trên tất cả trang):</p>
                            <div class="d-flex gap-2">
                                @include('staff.components.button', ['type' => 'button', 'variant' => 'outline-info', 'icon' => 'fas fa-eye', 'iconPosition' => 'only', 'tooltip' => 'Xem chi tiết', 'size' => 'sm'])
                                @include('staff.components.button', ['type' => 'button', 'variant' => 'outline-primary', 'icon' => 'fas fa-edit', 'iconPosition' => 'only', 'tooltip' => 'Sửa', 'size' => 'sm'])
                                @include('staff.components.button', ['type' => 'button', 'variant' => 'outline-success', 'icon' => 'fas fa-check', 'iconPosition' => 'only', 'tooltip' => 'Duyệt', 'size' => 'sm'])
                                @include('staff.components.button', ['type' => 'button', 'variant' => 'outline-danger', 'icon' => 'fas fa-trash-alt', 'iconPosition' => 'only', 'tooltip' => 'Xóa', 'size' => 'sm'])
                            </div>
                        </div>
                        <p class="text-muted small mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Buttons có màu sắc phân biệt rõ ràng: Xem (xanh nhạt), Sửa (xanh đậm), Duyệt (xanh lá), Xóa (đỏ). 
                            Không có border và underline. Hover có background color đậm hơn và shadow để dễ nhận biết.
                        </p>
                    </div>

                    <!-- Buttons with Badges -->
                    <div class="mb-4">
                        <h6 class="mb-3">Buttons có Badge</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'primary', 'label' => 'Thông báo', 'icon' => 'fas fa-bell', 'badge' => '5'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'info', 'label' => 'Tin nhắn', 'icon' => 'fas fa-envelope', 'badge' => '12'])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'warning', 'label' => 'Cảnh báo', 'icon' => 'fas fa-exclamation-circle', 'badge' => '3'])
                        </div>
                    </div>

                    <!-- Disabled & Loading States -->
                    <div class="mb-4">
                        <h6 class="mb-3">Trạng thái Vô hiệu & Đang tải</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'primary', 'label' => 'Vô hiệu', 'disabled' => true])
                            @include('staff.components.button', ['type' => 'button', 'variant' => 'primary', 'label' => 'Đang tải...', 'loading' => true])
                        </div>
                    </div>

                    <!-- Action Buttons Component -->
                    <div class="mb-4">
                        <h6 class="mb-3">Action Buttons Component (Ngang)</h6>
                        @include('staff.components.action-buttons', [
                            'layout' => 'horizontal',
                            'actions' => [
                                ['type' => 'button', 'variant' => 'primary', 'label' => 'Chỉnh sửa', 'icon' => 'fas fa-edit'],
                                ['type' => 'button', 'variant' => 'success', 'label' => 'Lưu', 'icon' => 'fas fa-save'],
                                ['type' => 'button', 'variant' => 'danger', 'label' => 'Xóa', 'icon' => 'fas fa-trash'],
                            ]
                        ])
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3">Action Buttons Component (Dọc)</h6>
                        @include('staff.components.action-buttons', [
                            'layout' => 'vertical',
                            'size' => 'md',
                            'actions' => [
                                ['type' => 'button', 'variant' => 'primary', 'label' => 'Chỉnh sửa', 'icon' => 'fas fa-edit'],
                                ['type' => 'button', 'variant' => 'danger', 'label' => 'Xóa', 'icon' => 'fas fa-trash-alt', 'class' => 'btn-danger-hover'],
                            ]
                        ])
                        <p class="text-muted small mt-2">Nút Xóa có màu đỏ đậm hơn để dễ nhận biết hành động nguy hiểm</p>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3">Action Buttons Component (Dropdown - với màu sắc phân biệt)</h6>
                        @include('staff.components.action-buttons', [
                            'layout' => 'dropdown',
                            'dropdownLabel' => 'Hành động',
                            'actions' => [
                                ['label' => 'Xem chi tiết', 'icon' => 'fas fa-eye', 'url' => '#', 'variant' => 'info'],
                                ['label' => 'Chỉnh sửa', 'icon' => 'fas fa-edit', 'url' => '#', 'variant' => 'primary'],
                                ['label' => 'Duyệt', 'icon' => 'fas fa-check', 'url' => '#', 'variant' => 'success'],
                                ['divider' => true],
                                ['label' => 'Xóa', 'icon' => 'fas fa-trash-alt', 'url' => '#', 'variant' => 'danger'],
                            ]
                        ])
                        <p class="text-muted small mt-2">Mỗi hành động có màu sắc riêng để dễ phân biệt: Xem chi tiết (xanh nhạt), Chỉnh sửa (xanh đậm), Duyệt (xanh lá), Xóa (đỏ)</p>
                    </div>
                </div>
            </div>

            <!-- Status Badges Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-tags me-2"></i>Status Badges
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Viewing Statuses -->
                    <div class="mb-4">
                        <h6 class="mb-3">Viewing Statuses</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @include('staff.components.status-badge', ['status' => 'requested', 'type' => 'viewing'])
                            @include('staff.components.status-badge', ['status' => 'scheduled', 'type' => 'viewing'])
                            @include('staff.components.status-badge', ['status' => 'confirmed', 'type' => 'viewing'])
                            @include('staff.components.status-badge', ['status' => 'completed', 'type' => 'viewing'])
                            @include('staff.components.status-badge', ['status' => 'cancelled', 'type' => 'viewing'])
                            @include('staff.components.status-badge', ['status' => 'no_show', 'type' => 'viewing'])
                        </div>
                    </div>

                    <!-- Booking Deposit Statuses -->
                    <div class="mb-4">
                        <h6 class="mb-3">Booking Deposit Statuses</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @include('staff.components.status-badge', ['status' => 'pending', 'type' => 'booking-deposit'])
                            @include('staff.components.status-badge', ['status' => 'pending_approval', 'type' => 'booking-deposit'])
                            @include('staff.components.status-badge', ['status' => 'confirmed', 'type' => 'booking-deposit'])
                            @include('staff.components.status-badge', ['status' => 'paid', 'type' => 'booking-deposit'])
                            @include('staff.components.status-badge', ['status' => 'cancelled', 'type' => 'booking-deposit'])
                            @include('staff.components.status-badge', ['status' => 'refunded', 'type' => 'booking-deposit'])
                        </div>
                    </div>

                    <!-- Lease Statuses -->
                    <div class="mb-4">
                        <h6 class="mb-3">Lease Statuses</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @include('staff.components.status-badge', ['status' => 'draft', 'type' => 'lease'])
                            @include('staff.components.status-badge', ['status' => 'pending', 'type' => 'lease'])
                            @include('staff.components.status-badge', ['status' => 'active', 'type' => 'lease'])
                            @include('staff.components.status-badge', ['status' => 'expired', 'type' => 'lease'])
                            @include('staff.components.status-badge', ['status' => 'terminated', 'type' => 'lease'])
                            @include('staff.components.status-badge', ['status' => 'cancelled', 'type' => 'lease'])
                        </div>
                    </div>

                    <!-- Invoice Statuses -->
                    <div class="mb-4">
                        <h6 class="mb-3">Invoice Statuses</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @include('staff.components.status-badge', ['status' => 'draft', 'type' => 'invoice'])
                            @include('staff.components.status-badge', ['status' => 'pending_approval', 'type' => 'invoice'])
                            @include('staff.components.status-badge', ['status' => 'issued', 'type' => 'invoice'])
                            @include('staff.components.status-badge', ['status' => 'paid', 'type' => 'invoice'])
                            @include('staff.components.status-badge', ['status' => 'overdue', 'type' => 'invoice'])
                            @include('staff.components.status-badge', ['status' => 'cancelled', 'type' => 'invoice'])
                        </div>
                    </div>

                    <!-- Company Invoice Statuses -->
                    <div class="mb-4">
                        <h6 class="mb-3">Company Invoice Statuses</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @include('staff.components.status-badge', ['status' => 'draft', 'type' => 'company-invoice'])
                            @include('staff.components.status-badge', ['status' => 'pending', 'type' => 'company-invoice'])
                            @include('staff.components.status-badge', ['status' => 'approved', 'type' => 'company-invoice'])
                            @include('staff.components.status-badge', ['status' => 'paid', 'type' => 'company-invoice'])
                            @include('staff.components.status-badge', ['status' => 'overdue', 'type' => 'company-invoice'])
                            @include('staff.components.status-badge', ['status' => 'cancelled', 'type' => 'company-invoice'])
                        </div>
                        <p class="text-muted small mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Status "Quá hạn" và "Đã hủy" hiển thị rõ ràng với màu sắc và icon phân biệt: Quá hạn (đỏ với icon cảnh báo), Đã hủy (đen với icon times-circle).
                        </p>
                    </div>

                    <!-- Payment Statuses -->
                    <div class="mb-4">
                        <h6 class="mb-3">Payment Statuses</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @include('staff.components.status-badge', ['status' => 'pending', 'type' => 'payment'])
                            @include('staff.components.status-badge', ['status' => 'processing', 'type' => 'payment'])
                            @include('staff.components.status-badge', ['status' => 'success', 'type' => 'payment'])
                            @include('staff.components.status-badge', ['status' => 'failed', 'type' => 'payment'])
                            @include('staff.components.status-badge', ['status' => 'cancelled', 'type' => 'payment'])
                        </div>
                    </div>

                    <!-- Ticket Statuses -->
                    <div class="mb-4">
                        <h6 class="mb-3">Ticket Statuses</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @include('staff.components.status-badge', ['status' => 'open', 'type' => 'ticket'])
                            @include('staff.components.status-badge', ['status' => 'in_progress', 'type' => 'ticket'])
                            @include('staff.components.status-badge', ['status' => 'resolved', 'type' => 'ticket'])
                            @include('staff.components.status-badge', ['status' => 'closed', 'type' => 'ticket'])
                            @include('staff.components.status-badge', ['status' => 'cancelled', 'type' => 'ticket'])
                        </div>
                    </div>

                    <!-- Lead Statuses -->
                    <div class="mb-4">
                        <h6 class="mb-3">Lead Statuses</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @include('staff.components.status-badge', ['status' => 'new', 'type' => 'lead'])
                            @include('staff.components.status-badge', ['status' => 'contacted', 'type' => 'lead'])
                            @include('staff.components.status-badge', ['status' => 'qualified', 'type' => 'lead'])
                            @include('staff.components.status-badge', ['status' => 'proposal', 'type' => 'lead'])
                            @include('staff.components.status-badge', ['status' => 'negotiation', 'type' => 'lead'])
                            @include('staff.components.status-badge', ['status' => 'converted', 'type' => 'lead'])
                            @include('staff.components.status-badge', ['status' => 'lost', 'type' => 'lead'])
                        </div>
                    </div>
                </div>
            </div>

            <!-- Real Examples from Index Pages -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-code me-2"></i>Ví dụ thực tế từ các trang Index/Show
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Viewings Index Style (Flex Style) -->
                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-calendar-check me-2"></i>Viewings Index Style (Flex Style - đồng bộ)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Khách hàng</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($viewing)
                                    <tr>
                                        <td>#{{ $viewing->id }}</td>
                                        <td>{{ $viewing->customer_name ?? 'N/A' }}</td>
                                        <td>@include('staff.components.status-badge', ['status' => $viewing->status ?? 'requested', 'type' => 'viewing'])</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                @include('staff.components.button', [
                                                    'type' => 'link',
                                                    'variant' => 'outline-primary',
                                                    'size' => 'sm',
                                                    'icon' => 'fas fa-eye',
                                                    'iconPosition' => 'only',
                                                    'tooltip' => 'Xem chi tiết',
                                                    'url' => route('staff.viewings.show', $viewing->id)
                                                ])
                                                @include('staff.components.button', [
                                                    'type' => 'link',
                                                    'variant' => 'outline-warning',
                                                    'size' => 'sm',
                                                    'icon' => 'fas fa-edit',
                                                    'iconPosition' => 'only',
                                                    'tooltip' => 'Sửa',
                                                    'url' => route('staff.viewings.edit', $viewing->id)
                                                ])
                                                @if(in_array($viewing->status ?? 'requested', ['requested', 'confirmed']))
                                                @include('staff.components.button', [
                                                    'type' => 'button',
                                                    'variant' => 'outline-danger',
                                                    'size' => 'sm',
                                                    'icon' => 'fas fa-trash-alt',
                                                    'iconPosition' => 'only',
                                                    'tooltip' => 'Xóa',
                                                    'onclick' => "alert('Xóa viewing #{{ $viewing->id }}')"
                                                ])
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @else
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Không có dữ liệu viewing mẫu</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Invoices Index Style -->
                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-file-invoice me-2"></i>Invoices Index Style (Flex Layout)
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã hóa đơn</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_no ?? 'INV#' . $invoice->id }}</td>
                                        <td>@include('staff.components.status-badge', ['status' => $invoice->status ?? 'draft', 'type' => 'invoice'])</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                @include('staff.components.button', [
                                                    'type' => 'link',
                                                    'variant' => 'outline-info',
                                                    'size' => 'sm',
                                                    'icon' => 'fas fa-eye',
                                                    'iconPosition' => 'only',
                                                    'tooltip' => 'Xem chi tiết',
                                                    'url' => route('staff.invoices.show', $invoice->id)
                                                ])
                                                @include('staff.components.button', [
                                                    'type' => 'link',
                                                    'variant' => 'outline-primary',
                                                    'size' => 'sm',
                                                    'icon' => 'fas fa-edit',
                                                    'iconPosition' => 'only',
                                                    'tooltip' => 'Chỉnh sửa',
                                                    'url' => route('staff.invoices.edit', $invoice->id)
                                                ])
                                                @if(($invoice->status ?? 'draft') === 'draft')
                                                @include('staff.components.button', [
                                                    'type' => 'button',
                                                    'variant' => 'outline-success',
                                                    'size' => 'sm',
                                                    'icon' => 'fas fa-paper-plane',
                                                    'iconPosition' => 'only',
                                                    'tooltip' => 'Phát hành',
                                                    'onclick' => "alert('Phát hành hóa đơn')"
                                                ])
                                                @endif
                                                @if(!in_array($invoice->status ?? 'draft', ['paid', 'cancelled']))
                                                @include('staff.components.button', [
                                                    'type' => 'button',
                                                    'variant' => 'outline-success',
                                                    'size' => 'sm',
                                                    'icon' => 'fas fa-check',
                                                    'iconPosition' => 'only',
                                                    'tooltip' => 'Đánh dấu đã thanh toán',
                                                    'onclick' => "alert('Đánh dấu đã thanh toán')"
                                                ])
                                                @endif
                                                @include('staff.components.button', [
                                                    'type' => 'button',
                                                    'variant' => 'outline-danger',
                                                    'size' => 'sm',
                                                    'icon' => 'fas fa-trash-alt',
                                                    'iconPosition' => 'only',
                                                    'tooltip' => 'Xóa',
                                                    'onclick' => "alert('Xóa hóa đơn')"
                                                ])
                                            </div>
                                        </td>
                                    </tr>
                                    @else
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Không có dữ liệu invoice mẫu</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Booking Deposits with Status -->
                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-money-bill-wave me-2"></i>Booking Deposits với Status Badge
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Số tham chiếu</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($bookingDeposit)
                                    <tr>
                                        <td>{{ $bookingDeposit->reference_no ?? 'BD#' . $bookingDeposit->id }}</td>
                                        <td>@include('staff.components.status-badge', ['status' => $bookingDeposit->status ?? 'pending', 'type' => 'booking-deposit'])</td>
                                        <td>
                                            @include('staff.components.button', [
                                                'type' => 'link',
                                                'variant' => 'outline-primary',
                                                'size' => 'sm',
                                                'icon' => 'fas fa-eye',
                                                'iconPosition' => 'only',
                                                'tooltip' => 'Xem chi tiết',
                                                'url' => route('staff.booking-deposits.show', $bookingDeposit->id)
                                            ])
                                        </td>
                                    </tr>
                                    @else
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Không có dữ liệu booking deposit mẫu</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Action Buttons in Show Page Header -->
                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-user-circle me-2"></i>Action Buttons trong Show Page Header
                        </h6>
                        <div class="card">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">
                                        <i class="fas fa-user me-2"></i>Thông tin người dùng
                                    </h6>
                                    <small class="text-white-50">ID: #123</small>
                                </div>
                                <div>
                                    @include('staff.components.action-buttons', [
                                        'layout' => 'horizontal',
                                        'size' => 'sm',
                                        'actions' => [
                                            ['type' => 'link', 'variant' => 'light', 'label' => 'Chỉnh sửa', 'icon' => 'fas fa-edit', 'url' => '#'],
                                            ['type' => 'link', 'variant' => 'light', 'label' => 'Xem lịch sử', 'icon' => 'fas fa-history', 'url' => '#'],
                                            ['type' => 'button', 'variant' => 'light', 'label' => 'Xóa tài khoản', 'icon' => 'fas fa-trash-alt', 'onclick' => "alert('Xóa tài khoản')", 'class' => 'text-danger'],
                                        ]
                                    ])
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="mb-0 text-muted">Đây là ví dụ về action buttons trong header của trang show</p>
                            </div>
                        </div>
                    </div>

                    <!-- Dropdown Action Buttons -->
                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-ellipsis-v me-2"></i>Dropdown Action Buttons (như trong các trang show)
                        </h6>
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-file-contract me-2"></i>Hợp đồng #HD001
                                </h6>
                                @include('staff.components.action-buttons', [
                                    'layout' => 'dropdown',
                                    'dropdownLabel' => 'Hành động',
                                    'size' => 'sm',
                                    'actions' => [
                                        ['label' => 'Xem chi tiết', 'icon' => 'fas fa-eye', 'url' => '#', 'variant' => 'info'],
                                        ['label' => 'Chỉnh sửa', 'icon' => 'fas fa-edit', 'url' => '#', 'variant' => 'primary'],
                                        ['label' => 'Duyệt', 'icon' => 'fas fa-check', 'url' => '#', 'variant' => 'success'],
                                        ['divider' => true],
                                        ['label' => 'Xóa', 'icon' => 'fas fa-trash-alt', 'url' => '#', 'variant' => 'danger'],
                                    ]
                                ])
                            </div>
                            <div class="card-body">
                                <p class="mb-0 text-muted">Dropdown button có màu xanh đậm, các action items có màu sắc phân biệt</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Form Redirect Guide -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-code me-2"></i>Hướng dẫn: Redirect về trang Show sau khi Edit
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>Áp dụng cho các trang Edit chưa có redirect về Show
                        </h6>
                        <p class="mb-0">Sau khi submit form edit thành công, hệ thống sẽ tự động chuyển về trang show thay vì trang index.</p>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-server me-2"></i>1. Controller - Thêm redirect vào JSON response
                        </h6>
                        <pre class="bg-light p-3 rounded"><code>// Trong Controller update method
DB::commit();

return response()->json([
    'success' => true,
    'message' => 'Đã cập nhật thành công!',
    'redirect' => route('staff.{resource}.show', $model->id)  // Thêm dòng này
]);</code></pre>
                        <p class="text-muted small mt-2">
                            <strong>Ví dụ:</strong> <code>route('staff.users.show', $targetUser->id)</code> hoặc <code>route('staff.vendors.show', $vendor->id)</code>
                        </p>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-file-code me-2"></i>2. View - Cập nhật JavaScript trong edit.blade.php
                        </h6>
                        <pre class="bg-light p-3 rounded"><code>// Trong phần JavaScript xử lý form submit
.then(data => {
    if (data.success) {
        Notify.success(data.message, 'Thành công!');
        setTimeout(() => {
            // Sử dụng redirect từ response, fallback về show page
            window.location.href = data.redirect || '@{{ route("staff.{resource}.show", $model->id) }}';
        }, 1500);
    } else {
        Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
    }
})</code></pre>
                        <p class="text-muted small mt-2">
                            <strong>Lưu ý:</strong> Thay <code>{resource}</code> và <code>$model</code> bằng tên resource và biến model thực tế của bạn.
                        </p>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-check-circle me-2 text-success"></i>Ví dụ hoàn chỉnh - UserController
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="small">Controller (app/Http/Controllers/Staff/UserController.php):</h6>
                                <pre class="bg-light p-2 rounded small"><code>return response()->json([
    'success' => true,
    'message' => 'Người dùng đã được cập nhật thành công!',
    'redirect' => route('staff.users.show', $targetUser->id)
]);</code></pre>
                            </div>
                            <div class="col-md-6">
                                <h6 class="small">View (resources/views/staff/party/users/edit.blade.php):</h6>
                                <pre class="bg-light p-2 rounded small"><code>.then(data => {
    if (data.success) {
        Notify.success(data.message, 'Thành công!');
        setTimeout(() => {
            window.location.href = data.redirect || '@{{ route("staff.users.show", $targetUser->id) }}';
        }, 1500);
    }
})</code></pre>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>Lưu ý quan trọng
                        </h6>
                        <ul class="mb-0 small">
                            <li>Đảm bảo route <code>staff.{resource}.show</code> đã được định nghĩa trong <code>routes/web.php</code></li>
                            <li>Kiểm tra biến model trong view có đúng tên không (ví dụ: <code>$targetUser</code>, <code>$vendor</code>, <code>$property</code>)</li>
                            <li>Thời gian delay 1500ms (1.5 giây) để người dùng có thể đọc thông báo thành công</li>
                            <li>Fallback về show page nếu response không có redirect (đảm bảo luôn redirect đúng)</li>
                        </ul>
                    </div>

                    <div class="alert alert-success">
                        <h6 class="alert-heading">
                            <i class="fas fa-list-check me-2"></i>Danh sách các trang đã áp dụng
                        </h6>
                        <ul class="mb-0 small">
                            <li><strong>✅ Users Edit:</strong> <code>/staff/users/{id}/edit</code> → redirect về <code>/staff/users/{id}</code></li>
                            <li><strong>⏳ Các trang khác:</strong> Cần kiểm tra và áp dụng tương tự</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

