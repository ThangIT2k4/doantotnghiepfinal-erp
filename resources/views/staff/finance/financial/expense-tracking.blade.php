@extends('layouts.staff_dashboard')

@section('title', 'Theo dõi Chi phí')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Theo dõi Chi phí & Phân loại',
            'subtitle' => 'Phân loại và theo dõi chi phí theo danh mục và nhà cung cấp',
            'icon' => 'fas fa-list-alt',
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
                <form method="GET" action="{{ route('staff.financial-management.expense-tracking') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Từ ngày</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Đến ngày</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Danh mục</label>
                        <select name="category" class="form-select">
                            <option value="all" {{ $category == 'all' ? 'selected' : '' }}>Tất cả</option>
                            <option value="master_lease" {{ $category == 'master_lease' ? 'selected' : '' }}>Hợp đồng thuê chính</option>
                            <option value="ticket_cost" {{ $category == 'ticket_cost' ? 'selected' : '' }}>Chi phí bảo trì</option>
                            <option value="deposit_refund" {{ $category == 'deposit_refund' ? 'selected' : '' }}>Hoàn tiền cọc</option>
                            <option value="payroll_payslip" {{ $category == 'payroll_payslip' ? 'selected' : '' }}>Lương nhân viên</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Áp dụng
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Category Statistics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Thống kê theo Danh mục</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Danh mục</th>
                                        <th class="text-end">Số lượng</th>
                                        <th class="text-end">Tổng tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categoryStats as $stat)
                                    <tr>
                                        <td>{{ ucfirst(str_replace('_', ' ', is_array($stat) ? ($stat['invoice_type'] ?? 'N/A') : ($stat->invoice_type ?? 'N/A'))) }}</td>
                                        <td class="text-end">{{ is_array($stat) ? ($stat['count'] ?? 0) : ($stat->count ?? 0) }}</td>
                                        <td class="text-end">{{ number_format(is_array($stat) ? ($stat['total_amount'] ?? 0) : ($stat->total_amount ?? 0), 0, ',', '.') }} VNĐ</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Không có dữ liệu</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-building"></i> Top 10 Đối tượng</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Đối tượng</th>
                                        <th class="text-end">Số lượng</th>
                                        <th class="text-end">Tổng tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($vendorStats as $stat)
                                    <tr>
                                        <td>{{ is_array($stat) ? ($stat['recipient_name'] ?? ($stat['vendor'] ?? null)?->name ?? 'N/A') : ($stat->recipient_name ?? $stat->vendor?->name ?? 'N/A') }}</td>
                                        <td class="text-end">{{ is_array($stat) ? ($stat['count'] ?? 0) : ($stat->count ?? 0) }}</td>
                                        <td class="text-end">{{ number_format(is_array($stat) ? ($stat['total_amount'] ?? 0) : ($stat->total_amount ?? 0), 0, ',', '.') }} VNĐ</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Không có dữ liệu</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses List -->
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Danh sách Chi phí ({{ $expenses->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mã hóa đơn</th>
                                <th>Đối tượng</th>
                                <th>Danh mục</th>
                                <th>Ngày phát hành</th>
                                <th class="text-end">Số tiền</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $expense)
                            <tr>
                                <td>
                                    <strong>{{ $expense['invoice_no'] }}</strong>
                                    @if($expense['type'] == 'cash_outflow')
                                    <small class="text-muted">(Chi phí tiền mặt)</small>
                                    @endif
                                </td>
                                <td>{{ $expense['recipient_name'] ?? $expense['vendor_name'] ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ ucfirst(str_replace('_', ' ', $expense['category'])) }}
                                    </span>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($expense['date'])->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format($expense['amount'], 0, ',', '.') }} VNĐ</td>
                                <td>
                                    @if($expense['status'] == 'paid')
                                    <span class="badge bg-success">Đã thanh toán</span>
                                    @elseif($expense['status'] == 'approved')
                                    <span class="badge bg-info">Đã duyệt</span>
                                    @else
                                    <span class="badge bg-secondary">{{ ucfirst($expense['status']) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group table-actions" role="group">
                                        @if($expense['type'] == 'company_invoice')
                                        <a href="{{ route('staff.company-invoices.show', $expense['id']) }}" class="btn btn-outline-primary btn-icon-only" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @else
                                        <a href="{{ route('staff.cash-outflows.show', $expense['id']) }}" class="btn btn-outline-info btn-icon-only" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>Không có chi phí nào trong khoảng thời gian này</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
</main>
@endsection

