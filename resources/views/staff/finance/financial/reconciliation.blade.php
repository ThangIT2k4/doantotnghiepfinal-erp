@extends('layouts.staff_dashboard')

@section('title', 'Đối soát Thu chi')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Đối soát Thu chi',
            'subtitle' => 'Đối chiếu thu nhập, chi phí và thanh toán thực tế',
            'icon' => 'fas fa-balance-scale',
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
                <form method="GET" action="{{ route('staff.financial-management.reconciliation') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Từ ngày</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Đến ngày</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quick Select</label>
                        <select class="form-select" onchange="setDateRange(this.value)">
                            <option value="">Chọn nhanh...</option>
                            <option value="month">Tháng này</option>
                            <option value="last_month">Tháng trước</option>
                            <option value="3_months">3 tháng gần nhất</option>
                            <option value="6_months">6 tháng gần nhất</option>
                            <option value="year">Năm nay</option>
                            <option value="all">Tất cả</option>
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

        <!-- Calculation Notes -->
        @if(isset($reconciliation['calculation_note']))
        <div class="card shadow-sm mb-4 border-info">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle"></i> Cách tính các chỉ số đối soát
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Tổng hóa đơn:</strong> {{ $reconciliation['calculation_note']['invoices_total'] ?? '' }}
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Hóa đơn đã thanh toán:</strong> {{ $reconciliation['calculation_note']['invoices_paid'] ?? '' }}
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Hóa đơn chưa thanh toán:</strong> {{ $reconciliation['calculation_note']['invoices_pending'] ?? '' }}
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Tổng chi phí:</strong> {{ $reconciliation['calculation_note']['expenses_total'] ?? '' }}
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Tổng thanh toán thực tế:</strong> {{ $reconciliation['calculation_note']['payments_total'] ?? '' }}
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Dòng tiền ròng:</strong> {{ $reconciliation['calculation_note']['net_cash_flow'] ?? '' }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Tổng Hóa đơn</h6>
                        <h3 class="text-primary mb-0">{{ number_format($reconciliation['invoices_total'], 0, ',', '.') }} VNĐ</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-success">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Hóa đơn Đã thanh toán</h6>
                        <h3 class="text-success mb-0">{{ number_format($reconciliation['invoices_paid'], 0, ',', '.') }} VNĐ</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Tổng Chi phí</h6>
                        <h3 class="text-warning mb-0">{{ number_format($reconciliation['expenses_total'], 0, ',', '.') }} VNĐ</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-{{ $reconciliation['net_cash_flow'] >= 0 ? 'success' : 'danger' }}">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Dòng tiền ròng</h6>
                        <h3 class="mb-0 {{ $reconciliation['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($reconciliation['net_cash_flow'], 0, ',', '.') }} VNĐ
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reconciliation Details -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Chi tiết Đối chiếu</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="table-primary">
                                <th>Mục</th>
                                <th class="text-end">Số tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Tổng giá trị hóa đơn</strong></td>
                                <td class="text-end">{{ number_format($reconciliation['invoices_total'], 0, ',', '.') }} VNĐ</td>
                            </tr>
                            <tr>
                                <td><strong>Hóa đơn đã thanh toán</strong></td>
                                <td class="text-end text-success">{{ number_format($reconciliation['invoices_paid'], 0, ',', '.') }} VNĐ</td>
                            </tr>
                            <tr>
                                <td><strong>Hóa đơn chưa thanh toán</strong></td>
                                <td class="text-end text-warning">{{ number_format($reconciliation['invoices_pending'], 0, ',', '.') }} VNĐ</td>
                            </tr>
                            <tr>
                                <td><strong>Tổng chi phí từ Hóa đơn công ty</strong></td>
                                <td class="text-end text-warning">{{ number_format($reconciliation['company_invoice_total'] ?? 0, 0, ',', '.') }} VNĐ</td>
                            </tr>
                            <tr>
                                <td><strong>Tổng chi phí từ Dòng tiền ra</strong></td>
                                <td class="text-end text-warning">{{ number_format($reconciliation['cash_outflow_total'] ?? 0, 0, ',', '.') }} VNĐ</td>
                            </tr>
                            <tr>
                                <td><strong>Tổng thanh toán thực tế</strong></td>
                                <td class="text-end text-info">{{ number_format($reconciliation['payments_total'], 0, ',', '.') }} VNĐ</td>
                            </tr>
                            <tr>
                                <td><strong>Chênh lệch (Hóa đơn đã thanh toán - Thanh toán thực tế)</strong></td>
                                <td class="text-end {{ abs($reconciliation['difference']) < 1 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($reconciliation['difference'], 0, ',', '.') }} VNĐ
                                    @if(abs($reconciliation['difference']) >= 1)
                                    <small class="text-muted d-block">(Cần kiểm tra)</small>
                                    @endif
                                </td>
                            </tr>
                            <tr class="table-{{ $reconciliation['net_cash_flow'] >= 0 ? 'success' : 'danger' }}">
                                <td><strong>Dòng tiền ròng (Thu - Chi)</strong></td>
                                <td class="text-end">
                                    <strong>{{ number_format($reconciliation['net_cash_flow'], 0, ',', '.') }} VNĐ</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detailed Expenses -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Chi tiết Chi phí</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Hóa đơn Công ty ({{ $expenseData['company_invoices']->count() }})</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã hóa đơn</th>
                                        <th>Ngày</th>
                                        <th class="text-end">Số tiền</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($expenseData['company_invoices']->take(10) as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_no }}</td>
                                        <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</td>
                                        <td class="text-end">{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</td>
                                        <td>
                                            <span class="badge bg-{{ $invoice->status == 'paid' ? 'success' : 'warning' }}">
                                                {{ ucfirst($invoice->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Không có hóa đơn công ty</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($expenseData['company_invoices']->count() > 10)
                        <small class="text-muted">Hiển thị 10 trên tổng {{ $expenseData['company_invoices']->count() }} hóa đơn</small>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Dòng tiền ra ({{ $expenseData['cash_outflows']->count() }})</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã</th>
                                        <th>Ngày</th>
                                        <th class="text-end">Số tiền</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($expenseData['cash_outflows']->take(10) as $outflow)
                                    <tr>
                                        <td>OUT-{{ $outflow->id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($outflow->paid_at)->format('d/m/Y') }}</td>
                                        <td class="text-end">{{ number_format($outflow->amount, 0, ',', '.') }} VNĐ</td>
                                        <td>
                                            <span class="badge bg-{{ $outflow->status == 'success' ? 'success' : 'warning' }}">
                                                {{ ucfirst($outflow->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Không có dòng tiền ra</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($expenseData['cash_outflows']->count() > 10)
                        <small class="text-muted">Hiển thị 10 trên tổng {{ $expenseData['cash_outflows']->count() }} dòng tiền ra</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Income -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Chi tiết Thu nhập ({{ $incomeData->count() }})</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Mã hóa đơn</th>
                                <th>Ngày phát hành</th>
                                <th>Hạn thanh toán</th>
                                <th class="text-end">Số tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomeData->take(20) as $invoice)
                            <tr>
                                <td><strong>{{ $invoice->invoice_no }}</strong></td>
                                <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format($invoice->total_amount, 0, ',', '.') }} VNĐ</td>
                                <td>
                                    @if($invoice->status == 'paid')
                                    <span class="badge bg-success">Đã thanh toán</span>
                                    @elseif($invoice->status == 'issued')
                                    <span class="badge bg-info">Đã phát hành</span>
                                    @elseif($invoice->status == 'overdue')
                                    <span class="badge bg-danger">Quá hạn</span>
                                    @else
                                    <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Không có hóa đơn thu nhập</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($incomeData->count() > 20)
                <small class="text-muted">Hiển thị 20 trên tổng {{ $incomeData->count() }} hóa đơn</small>
                @endif
            </div>
        </div>
    </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
function setDateRange(value) {
    const today = new Date();
    const dateFrom = document.querySelector('input[name="date_from"]');
    const dateTo = document.querySelector('input[name="date_to"]');
    
    switch(value) {
        case 'month':
            dateFrom.value = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            dateTo.value = today.toISOString().split('T')[0];
            break;
        case 'last_month':
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            dateFrom.value = new Date(lastMonth.getFullYear(), lastMonth.getMonth(), 1).toISOString().split('T')[0];
            dateTo.value = new Date(lastMonth.getFullYear(), lastMonth.getMonth() + 1, 0).toISOString().split('T')[0];
            break;
        case '3_months':
            dateFrom.value = new Date(today.getFullYear(), today.getMonth() - 3, 1).toISOString().split('T')[0];
            dateTo.value = today.toISOString().split('T')[0];
            break;
        case '6_months':
            dateFrom.value = new Date(today.getFullYear(), today.getMonth() - 6, 1).toISOString().split('T')[0];
            dateTo.value = today.toISOString().split('T')[0];
            break;
        case 'year':
            dateFrom.value = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
            dateTo.value = today.toISOString().split('T')[0];
            break;
        case 'all':
            dateFrom.value = '2020-01-01'; // Hoặc một ngày rất xa trong quá khứ
            dateTo.value = today.toISOString().split('T')[0];
            break;
    }
}
</script>
@endpush

