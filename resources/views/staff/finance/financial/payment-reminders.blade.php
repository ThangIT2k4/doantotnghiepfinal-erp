@extends('layouts.staff_dashboard')

@section('title', 'Nhắc nhở Thanh toán')

@push('styles')
<style>
    .table th:last-child,
    .table td:last-child {
        white-space: nowrap;
        width: 1%;
        min-width: 150px;
    }
    .table .btn {
        min-width: 90px;
        margin: 2px;
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }
    .table .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        justify-content: center;
    }
    .table .btn-group .btn {
        margin: 0;
        flex: 0 0 auto;
    }
    .table .btn i {
        margin-right: 4px;
    }
    .action-buttons {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }
    .action-buttons .btn {
        width: 100%;
        min-width: 100px;
    }
    .action-buttons small {
        font-size: 0.7rem;
        line-height: 1.2;
    }
    @media (min-width: 768px) {
        .action-buttons {
            flex-direction: row;
            justify-content: center;
        }
        .action-buttons .btn {
            width: auto;
        }
    }
</style>
@endpush

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Nhắc nhở Thanh toán',
            'subtitle' => 'Theo dõi các hóa đơn sắp đến hạn và quá hạn thanh toán',
            'icon' => 'fas fa-bell',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.financial-management.index')
                ]
            ]
        ])
        
        <div class="content">
        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('staff.financial-management.payment-reminders') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Nhắc nhở trước (ngày)</label>
                        <select name="days_before" class="form-select">
                            <option value="3" {{ $daysBefore == 3 ? 'selected' : '' }}>3 ngày</option>
                            <option value="7" {{ $daysBefore == 7 ? 'selected' : '' }}>7 ngày</option>
                            <option value="14" {{ $daysBefore == 14 ? 'selected' : '' }}>14 ngày</option>
                            <option value="30" {{ $daysBefore == 30 ? 'selected' : '' }}>30 ngày</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="all" {{ $status == 'all' ? 'selected' : '' }}>Tất cả</option>
                            <option value="upcoming" {{ $status == 'upcoming' ? 'selected' : '' }}>Sắp đến hạn</option>
                            <option value="overdue" {{ $status == 'overdue' ? 'selected' : '' }}>Quá hạn</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Loại hóa đơn</label>
                        <select name="type" class="form-select">
                            <option value="tenant" {{ $type == 'tenant' ? 'selected' : '' }}>Hóa đơn thuê</option>
                            <option value="company" {{ $type == 'company' ? 'selected' : '' }}>Hóa đơn công ty</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Áp dụng
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            @if($type == 'tenant')
            <div class="col-md-6">
                <div class="card shadow-sm border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Sắp đến hạn (Hóa đơn thuê)</h6>
                        <h2 class="text-warning mb-0">{{ $stats['upcoming_count'] }}</h2>
                        <small class="text-muted">{{ number_format($stats['upcoming_total'], 0, ',', '.') }} VNĐ</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Quá hạn (Hóa đơn thuê)</h6>
                        <h2 class="text-danger mb-0">{{ $stats['overdue_count'] }}</h2>
                        <small class="text-muted">{{ number_format($stats['overdue_total'], 0, ',', '.') }} VNĐ</small>
                    </div>
                </div>
            </div>
            @else
            <div class="col-md-6">
                <div class="card shadow-sm border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Sắp đến hạn (Hóa đơn công ty)</h6>
                        <h2 class="text-warning mb-0">{{ $stats['upcoming_company_count'] }}</h2>
                        <small class="text-muted">{{ number_format($stats['upcoming_company_total'], 0, ',', '.') }} VNĐ</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Quá hạn (Hóa đơn công ty)</h6>
                        <h2 class="text-danger mb-0">{{ $stats['overdue_company_count'] }}</h2>
                        <small class="text-muted">{{ number_format($stats['overdue_company_total'], 0, ',', '.') }} VNĐ</small>
                    </div>
                </div>
            </div>
            @endif
        </div>

        @if($type == 'tenant')
        <!-- Upcoming Invoices -->
        @if($upcomingInvoices->count() > 0 && ($status == 'all' || $status == 'upcoming'))
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Hóa đơn Sắp đến hạn ({{ $upcomingInvoices->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mã hóa đơn</th>
                                <th>Người thuê</th>
                                <th>Ngày phát hành</th>
                                <th>Hạn thanh toán</th>
                                <th class="text-end">Số tiền</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingInvoices as $invoice)
                            <tr>
                                <td><strong>{{ $invoice->invoice_no }}</strong></td>
                                <td>
                                    @if($invoice->lease && $invoice->lease->tenant)
                                        {{ $invoice->lease->tenant->full_name }}
                                    @elseif($invoice->bookingDeposit && $invoice->bookingDeposit->lead)
                                        {{ $invoice->bookingDeposit->lead->full_name ?? $invoice->bookingDeposit->lead->name }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-warning">
                                        {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
                                    </span>
                                    <small class="text-muted">
                                        ({{ \Carbon\Carbon::parse($invoice->due_date)->diffForHumans() }})
                                    </small>
                                </td>
                                <td class="text-end">{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</td>
                                <td>
                                    @if($invoice->status == 'issued')
                                    <span class="badge bg-info">Đã phát hành</span>
                                    @elseif($invoice->status == 'overdue')
                                    <span class="badge bg-danger">Quá hạn</span>
                                    @else
                                    <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group table-actions" role="group">
                                        <a href="{{ route('staff.invoices.show', $invoice->id) }}" class="btn btn-outline-primary btn-icon-only" title="Xem chi tiết hóa đơn">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @php
                                            $lastReminder = $invoice->last_reminder_sent_at ?? null;
                                            $canSend = !$lastReminder || $lastReminder->diffInHours(now()) >= 1;
                                            $tenantName = 'N/A';
                                            $tenantEmail = 'N/A';
                                            if($invoice->lease && $invoice->lease->tenant) {
                                                $tenantName = $invoice->lease->tenant->full_name;
                                                $tenantEmail = $invoice->lease->tenant->email ?? 'N/A';
                                            } elseif($invoice->bookingDeposit && $invoice->bookingDeposit->lead) {
                                                $tenantName = $invoice->bookingDeposit->lead->full_name ?? $invoice->bookingDeposit->lead->name;
                                                $tenantEmail = $invoice->bookingDeposit->lead->email ?? 'N/A';
                                            }
                                        @endphp
                                        @if($lastReminder && !$canSend)
                                            <button type="button" class="btn btn-outline-secondary btn-icon-only" disabled title="Đã gửi email lúc {{ $lastReminder->format('d/m/Y H:i') }}">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        @else
                                            @php
                                                $daysRemaining = $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->diffInDays(now(), false) : 0;
                                                $isOverdue = $daysRemaining < 0;
                                            @endphp
                                            <button type="button" class="btn btn-outline-success btn-icon-only" onclick="sendTenantReminder({{ $invoice->id }}, '{{ $invoice->invoice_no }}', '{{ $tenantName }}', '{{ $tenantEmail }}', '{{ $invoice->total_amount }}', '{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}', {{ $daysRemaining }}, {{ $isOverdue ? 'true' : 'false' }})" title="Gửi email nhắc nhở thanh toán">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @if($lastReminder)
                                        <small class="text-muted d-block mt-1">
                                            {{ $lastReminder->diffForHumans() }}
                                        </small>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Overdue Invoices -->
        @if($overdueInvoices->count() > 0 && ($status == 'all' || $status == 'overdue'))
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> Hóa đơn Quá hạn ({{ $overdueInvoices->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mã hóa đơn</th>
                                <th>Người thuê</th>
                                <th>Ngày phát hành</th>
                                <th>Hạn thanh toán</th>
                                <th class="text-end">Số tiền</th>
                                <th>Số ngày quá hạn</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overdueInvoices as $invoice)
                            <tr class="table-danger">
                                <td><strong>{{ $invoice->invoice_no }}</strong></td>
                                <td>
                                    @if($invoice->lease && $invoice->lease->tenant)
                                        {{ $invoice->lease->tenant->full_name }}
                                    @elseif($invoice->bookingDeposit && $invoice->bookingDeposit->lead)
                                        {{ $invoice->bookingDeposit->lead->full_name ?? $invoice->bookingDeposit->lead->name }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-danger">
                                        {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td class="text-end">{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</td>
                                <td>
                                    <span class="badge bg-danger">
                                        {{ round(\Carbon\Carbon::parse($invoice->due_date)->diffInDays(now())) }} ngày
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group table-actions" role="group">
                                        <a href="{{ route('staff.invoices.show', $invoice->id) }}" class="btn btn-outline-primary btn-icon-only" title="Xem chi tiết hóa đơn">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @php
                                            $lastReminder = $invoice->last_reminder_sent_at ?? null;
                                            $canSend = !$lastReminder || $lastReminder->diffInHours(now()) >= 1;
                                            $tenantName = 'N/A';
                                            $tenantEmail = 'N/A';
                                            if($invoice->lease && $invoice->lease->tenant) {
                                                $tenantName = $invoice->lease->tenant->full_name;
                                                $tenantEmail = $invoice->lease->tenant->email ?? 'N/A';
                                            } elseif($invoice->bookingDeposit && $invoice->bookingDeposit->lead) {
                                                $tenantName = $invoice->bookingDeposit->lead->full_name ?? $invoice->bookingDeposit->lead->name;
                                                $tenantEmail = $invoice->bookingDeposit->lead->email ?? 'N/A';
                                            }
                                        @endphp
                                        @if($lastReminder && !$canSend)
                                            <button type="button" class="btn btn-outline-secondary btn-icon-only" disabled title="Đã gửi email lúc {{ $lastReminder->format('d/m/Y H:i') }}">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        @else
                                            @php
                                                $daysRemaining = $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->diffInDays(now(), false) : 0;
                                                $isOverdue = $daysRemaining < 0;
                                            @endphp
                                            <button type="button" class="btn btn-outline-success btn-icon-only" onclick="sendTenantReminder({{ $invoice->id }}, '{{ $invoice->invoice_no }}', '{{ $tenantName }}', '{{ $tenantEmail }}', '{{ $invoice->total_amount }}', '{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}', {{ $daysRemaining }}, {{ $isOverdue ? 'true' : 'false' }})" title="Gửi email nhắc nhở thanh toán">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @if($lastReminder)
                                        <small class="text-muted d-block mt-1">
                                            {{ $lastReminder->diffForHumans() }}
                                        </small>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($upcomingInvoices->count() == 0 && $overdueInvoices->count() == 0)
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5>Không có hóa đơn thuê cần nhắc nhở</h5>
                <p class="text-muted">Tất cả các hóa đơn thuê đều đã được thanh toán hoặc chưa đến hạn</p>
            </div>
        </div>
        @endif
        @else
        <!-- Upcoming Company Invoices -->
        @if($upcomingCompanyInvoices->count() > 0 && ($status == 'all' || $status == 'upcoming'))
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Hóa đơn Công ty Sắp đến hạn ({{ $upcomingCompanyInvoices->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mã hóa đơn</th>
                                <th>Nhà cung cấp/Người nhận</th>
                                <th>Ngày phát hành</th>
                                <th>Hạn thanh toán</th>
                                <th class="text-end">Số tiền</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingCompanyInvoices as $invoice)
                            <tr>
                                <td><strong>{{ $invoice->invoice_no ?? 'HDCT' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</strong></td>
                                <td>
                                    @if($invoice->vendor)
                                        {{ $invoice->vendor->name }}
                                    @elseif($invoice->user)
                                        {{ $invoice->user->full_name ?? $invoice->user->name }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $invoice->issue_date ? $invoice->issue_date->format('d/m/Y') : 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-warning">
                                        {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}
                                    </span>
                                    <small class="text-muted">
                                        @if($invoice->due_date)
                                            ({{ $invoice->due_date->diffForHumans() }})
                                        @endif
                                    </small>
                                </td>
                                <td class="text-end">{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</td>
                                <td>
                                    @if($invoice->status == 'pending')
                                    <span class="badge bg-warning">Chờ thanh toán</span>
                                    @elseif($invoice->status == 'approved')
                                    <span class="badge bg-info">Đã duyệt</span>
                                    @elseif($invoice->status == 'overdue')
                                    <span class="badge bg-danger">Quá hạn</span>
                                    @else
                                    <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group table-actions" role="group">
                                        <a href="{{ route('staff.company-invoices.show', $invoice->id) }}" class="btn btn-outline-primary btn-icon-only" title="Xem chi tiết hóa đơn">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @php
                                            $lastReminder = $invoice->last_reminder_sent_at ?? null;
                                            $canSend = !$lastReminder || $lastReminder->diffInHours(now()) >= 1; // Chỉ cho phép gửi lại sau 1 giờ
                                        @endphp
                                        @if($lastReminder && !$canSend)
                                            <button type="button" class="btn btn-outline-secondary btn-icon-only" disabled title="Đã gửi email lúc {{ $lastReminder->format('d/m/Y H:i') }}">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-outline-success btn-icon-only" onclick="sendReminder({{ $invoice->id }}, '{{ $invoice->invoice_no ?? 'HDCT' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}', '{{ $invoice->vendor->name ?? ($invoice->user->full_name ?? $invoice->user->name ?? 'N/A') }}', '{{ $invoice->total_amount }}', '{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}')" title="Gửi email nhắc nhở thanh toán">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @if($lastReminder)
                                        <small class="text-muted d-block mt-1">
                                            {{ $lastReminder->diffForHumans() }}
                                        </small>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Overdue Company Invoices -->
        @if($overdueCompanyInvoices->count() > 0 && ($status == 'all' || $status == 'overdue'))
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> Hóa đơn Công ty Quá hạn ({{ $overdueCompanyInvoices->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mã hóa đơn</th>
                                <th>Nhà cung cấp/Người nhận</th>
                                <th>Ngày phát hành</th>
                                <th>Hạn thanh toán</th>
                                <th class="text-end">Số tiền</th>
                                <th>Số ngày quá hạn</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overdueCompanyInvoices as $invoice)
                            <tr class="table-danger">
                                <td><strong>{{ $invoice->invoice_no ?? 'HDCT' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</strong></td>
                                <td>
                                    @if($invoice->vendor)
                                        {{ $invoice->vendor->name }}
                                    @elseif($invoice->user)
                                        {{ $invoice->user->full_name ?? $invoice->user->name }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $invoice->issue_date ? $invoice->issue_date->format('d/m/Y') : 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-danger">
                                        {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-end">{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</td>
                                <td>
                                    <span class="badge bg-danger">
                                        {{ $invoice->due_date ? now()->diffInDays($invoice->due_date) : 0 }} ngày
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group table-actions" role="group">
                                        <a href="{{ route('staff.company-invoices.show', $invoice->id) }}" class="btn btn-outline-primary btn-icon-only" title="Xem chi tiết hóa đơn">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @php
                                            $lastReminder = $invoice->last_reminder_sent_at ?? null;
                                            $canSend = !$lastReminder || $lastReminder->diffInHours(now()) >= 1; // Chỉ cho phép gửi lại sau 1 giờ
                                        @endphp
                                        @if($lastReminder && !$canSend)
                                            <button type="button" class="btn btn-outline-secondary btn-icon-only" disabled title="Đã gửi email lúc {{ $lastReminder->format('d/m/Y H:i') }}">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-outline-success btn-icon-only" onclick="sendReminder({{ $invoice->id }}, '{{ $invoice->invoice_no ?? 'HDCT' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}', '{{ $invoice->vendor->name ?? ($invoice->user->full_name ?? $invoice->user->name ?? 'N/A') }}', '{{ $invoice->total_amount }}', '{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}')" title="Gửi email nhắc nhở thanh toán">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @if($lastReminder)
                                        <small class="text-muted d-block mt-1">
                                            {{ $lastReminder->diffForHumans() }}
                                        </small>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($upcomingCompanyInvoices->count() == 0 && $overdueCompanyInvoices->count() == 0)
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5>Không có hóa đơn công ty cần nhắc nhở</h5>
                <p class="text-muted">Tất cả các hóa đơn công ty đều đã được thanh toán hoặc chưa đến hạn</p>
            </div>
        </div>
        @endif
        @endif
    </div>
    </div>
</main>

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
function sendTenantReminder(invoiceId, invoiceNo, tenantName, tenantEmail, amount, dueDate, daysRemaining, isOverdue) {
    // Lưu reference button trước khi gọi confirm
    const button = event.target.closest('button');
    
    const amountFormatted = new Intl.NumberFormat('vi-VN').format(amount);
    const daysText = Math.round(Math.abs(daysRemaining));
    const statusBadge = isOverdue 
        ? `<span class="badge bg-danger">Quá hạn ${daysText} ngày</span>`
        : `<span class="badge bg-warning">Còn ${daysText} ngày</span>`;
    
    const emailDisplay = tenantEmail && tenantEmail !== 'N/A' 
        ? `<a href="mailto:${tenantEmail}" class="text-decoration-none"><i class="fas fa-envelope me-1"></i>${tenantEmail}</a>`
        : `<span class="text-muted"><i class="fas fa-exclamation-triangle me-1"></i>Chưa có email</span>`;
    
    const message = `
        <div class="text-start">
            <p class="mb-3"><strong><i class="fas fa-envelope me-2"></i>Bạn có chắc chắn muốn gửi email nhắc nhở thanh toán?</strong></p>
            <div class="card border-primary mb-3">
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted" style="width: 140px;"><i class="fas fa-file-invoice me-2"></i>Mã hóa đơn:</td>
                                <td><strong>${invoiceNo}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="fas fa-user me-2"></i>Người nhận:</td>
                                <td><strong>${tenantName}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="fas fa-envelope me-2"></i>Email:</td>
                                <td>${emailDisplay}</td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="fas fa-money-bill-wave me-2"></i>Số tiền:</td>
                                <td><strong class="text-primary">${amountFormatted} VNĐ</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="fas fa-calendar-alt me-2"></i>Hạn thanh toán:</td>
                                <td><strong>${dueDate}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted"><i class="fas fa-clock me-2"></i>Trạng thái:</td>
                                <td>${statusBadge}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <small>Email sẽ được gửi đến địa chỉ email của người thuê và được ghi lại trong audit log.</small>
            </div>
        </div>
    `;
    
    Notify.confirm({
        title: '<i class="fas fa-envelope me-2"></i>Gửi email nhắc nhở',
        message: message,
        type: 'info',
        confirmText: '<i class="fas fa-paper-plane me-1"></i> Gửi email',
        cancelText: 'Hủy',
        onConfirm: function() {
            const loadingToast = Notify.toast({
                title: 'Đang gửi email...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0,
                showProgress: false
            });

            // Disable button ngay lập tức để tránh gửi nhiều lần
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                Notify.error('CSRF token không tìm thấy. Vui lòng tải lại trang.', 'Lỗi!');
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-envelope"></i>';
                }
                return;
            }

            fetch(`/staff/financial-management/invoices/${invoiceId}/send-reminder`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
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
                    Notify.success(data.message, 'Thành công!');
                    // Reload trang sau 1.5 giây để cập nhật trạng thái
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    Notify.error(data.message, 'Lỗi!');
                    // Re-enable button nếu có lỗi
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-envelope"></i> Gửi email';
                    }
                }
            })
            .catch(error => {
                console.error('Error sending reminder:', error);
                let errorMessage = 'Có lỗi xảy ra khi gửi email';
                if (error.message && error.message.includes('Failed to fetch')) {
                    errorMessage = 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng và thử lại.';
                } else if (error.message) {
                    errorMessage = error.message;
                }
                Notify.error(errorMessage, 'Lỗi!');
                // Re-enable button nếu có lỗi
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-envelope"></i>';
                }
            })
            .finally(() => {
                const toastElement = document.querySelector(`[data-toast-id="${loadingToast}"]`);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
            });
        }
    });
}

function sendReminder(companyInvoiceId, invoiceNo, recipientName, amount, dueDate) {
    // Lưu reference button trước khi gọi confirm
    const button = event.target.closest('button');
    
    const amountFormatted = new Intl.NumberFormat('vi-VN').format(amount);
    const message = `
        <div class="text-start">
            <p><strong>Bạn có chắc chắn muốn gửi email nhắc nhở thanh toán?</strong></p>
            <hr>
            <p class="mb-1"><strong>Mã hóa đơn:</strong> ${invoiceNo}</p>
            <p class="mb-1"><strong>Người nhận:</strong> ${recipientName}</p>
            <p class="mb-1"><strong>Số tiền:</strong> ${amountFormatted} VNĐ</p>
            <p class="mb-1"><strong>Hạn thanh toán:</strong> ${dueDate}</p>
            <hr>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle"></i> Email sẽ được gửi đến địa chỉ email của người nhận và được ghi lại trong audit log.
            </p>
        </div>
    `;
    
    Notify.confirm({
        title: '<i class="fas fa-envelope me-2"></i>Gửi email nhắc nhở',
        message: message,
        type: 'info',
        confirmText: '<i class="fas fa-paper-plane me-1"></i> Gửi email',
        cancelText: 'Hủy',
        onConfirm: function() {
            const loadingToast = Notify.toast({
                title: 'Đang gửi email...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0,
                showProgress: false
            });

            // Disable button ngay lập tức để tránh gửi nhiều lần
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                Notify.error('CSRF token không tìm thấy. Vui lòng tải lại trang.', 'Lỗi!');
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-envelope"></i>';
                }
                return;
            }

            fetch(`/staff/financial-management/company-invoices/${companyInvoiceId}/send-reminder`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
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
                    Notify.success(data.message, 'Thành công!');
                    // Reload trang sau 1.5 giây để cập nhật trạng thái
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    Notify.error(data.message, 'Lỗi!');
                    // Re-enable button nếu có lỗi
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-envelope"></i> Gửi email';
                    }
                }
            })
            .catch(error => {
                console.error('Error sending reminder:', error);
                let errorMessage = 'Có lỗi xảy ra khi gửi email';
                if (error.message && error.message.includes('Failed to fetch')) {
                    errorMessage = 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng và thử lại.';
                } else if (error.message) {
                    errorMessage = error.message;
                }
                Notify.error(errorMessage, 'Lỗi!');
                // Re-enable button nếu có lỗi
                if (button) {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-envelope"></i>';
                }
            })
            .finally(() => {
                const toastElement = document.querySelector(`[data-toast-id="${loadingToast}"]`);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
            });
        }
    });
}
</script>
@endpush
@endsection



