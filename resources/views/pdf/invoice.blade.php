<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn {{ $invoice->invoice_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20mm;
            background: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #1E4FC8;
        }
        
        .header h1 {
            color: #1E4FC8;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .invoice-info {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .invoice-info-row {
            display: table-row;
        }
        
        .invoice-info-cell {
            display: table-cell;
            padding: 8px 0;
            width: 50%;
        }
        
        .invoice-info-label {
            font-weight: bold;
            color: #555;
        }
        
        .invoice-info-value {
            color: #333;
        }
        
        .parties {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .party {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .party:first-child {
            margin-right: 10px;
        }
        
        .party:last-child {
            margin-left: 10px;
        }
        
        .party-title {
            font-weight: bold;
            font-size: 14px;
            color: #1E4FC8;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #1E4FC8;
        }
        
        .party-info {
            font-size: 11px;
            line-height: 1.8;
        }
        
        .party-info p {
            margin: 5px 0;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table thead {
            background: #1E4FC8;
            color: #fff;
        }
        
        .items-table th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
        }
        
        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        
        .items-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .summary {
            margin-top: 20px;
            margin-left: auto;
            width: 300px;
        }
        
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .summary-label {
            display: table-cell;
            text-align: right;
            padding-right: 15px;
            font-weight: 600;
            color: #555;
        }
        
        .summary-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
            color: #333;
            width: 120px;
        }
        
        .summary-total {
            border-top: 2px solid #1E4FC8;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 16px;
        }
        
        .summary-total .summary-label {
            color: #1E4FC8;
            font-size: 16px;
        }
        
        .summary-total .summary-value {
            color: #1E4FC8;
            font-size: 18px;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .note-section {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #1E4FC8;
        }
        
        .note-section h4 {
            color: #1E4FC8;
            margin-bottom: 10px;
            font-size: 13px;
        }
        
        .note-section p {
            font-size: 11px;
            line-height: 1.6;
            color: #555;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-paid {
            background: #28a745;
            color: #fff;
        }
        
        .status-issued {
            background: #ffc107;
            color: #333;
        }
        
        .status-overdue {
            background: #dc3545;
            color: #fff;
        }
        
        .status-draft {
            background: #6c757d;
            color: #fff;
        }
        
        .status-cancelled {
            background: #6c757d;
            color: #fff;
        }
        
        @media print {
            .container {
                padding: 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>HÓA ĐƠN</h1>
            <div class="subtitle">INVOICE</div>
        </div>
        
        <!-- Invoice Info -->
        <div class="invoice-info">
            <div class="invoice-info-row">
                <div class="invoice-info-cell">
                    <span class="invoice-info-label">Số hóa đơn:</span>
                    <span class="invoice-info-value"> {{ $invoice->invoice_no }}</span>
                </div>
                <div class="invoice-info-cell">
                    <span class="invoice-info-label">Trạng thái:</span>
                    <span class="invoice-info-value">
                        @if($invoice->status == 'paid')
                            <span class="status-badge status-paid">Đã thanh toán</span>
                        @elseif($invoice->status == 'issued')
                            @if($invoice->isOverdue())
                                <span class="status-badge status-overdue">Quá hạn</span>
                            @else
                                <span class="status-badge status-issued">Đã phát hành</span>
                            @endif
                        @elseif($invoice->status == 'draft')
                            <span class="status-badge status-draft">Nháp</span>
                        @elseif($invoice->status == 'cancelled')
                            <span class="status-badge status-cancelled">Đã hủy</span>
                        @endif
                    </span>
                </div>
            </div>
            <div class="invoice-info-row">
                <div class="invoice-info-cell">
                    <span class="invoice-info-label">Ngày phát hành:</span>
                    <span class="invoice-info-value"> {{ $invoice->issue_date->format('d/m/Y') }}</span>
                </div>
                <div class="invoice-info-cell">
                    <span class="invoice-info-label">Hạn thanh toán:</span>
                    <span class="invoice-info-value"> {{ $invoice->due_date->format('d/m/Y') }}</span>
                </div>
            </div>
            <div class="invoice-info-row">
                <div class="invoice-info-cell">
                    <span class="invoice-info-label">Loại hóa đơn:</span>
                    <span class="invoice-info-value"> {{ $invoice->getInvoiceTypeLabel() }}</span>
                </div>
                <div class="invoice-info-cell">
                    <span class="invoice-info-label">Số hợp đồng:</span>
                    <span class="invoice-info-value"> {{ $invoice->lease->contract_no ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        
        <!-- Parties -->
        <div class="parties">
            <div class="party">
                <div class="party-title">BÊN BÁN</div>
                <div class="party-info">
                    <p><strong>{{ $invoice->organization->name ?? 'N/A' }}</strong></p>
                    @if($invoice->organization->address)
                        <p>{{ $invoice->organization->address }}</p>
                    @endif
                    @if($invoice->organization->phone)
                        <p>Điện thoại: {{ $invoice->organization->phone }}</p>
                    @endif
                    @if($invoice->organization->email)
                        <p>Email: {{ $invoice->organization->email }}</p>
                    @endif
                    @if($invoice->organization->tax_code)
                        <p>Mã số thuế: {{ $invoice->organization->tax_code }}</p>
                    @endif
                </div>
            </div>
            <div class="party">
                <div class="party-title">BÊN MUA</div>
                <div class="party-info">
                    @if($invoice->lease && $invoice->lease->tenant)
                        <p><strong>{{ $invoice->lease->tenant->full_name ?? $invoice->lease->tenant->name ?? 'N/A' }}</strong></p>
                        @if($invoice->lease->tenant->phone)
                            <p>Điện thoại: {{ $invoice->lease->tenant->phone }}</p>
                        @endif
                        @if($invoice->lease->tenant->email)
                            <p>Email: {{ $invoice->lease->tenant->email }}</p>
                        @endif
                        @if($invoice->lease->unit)
                            <p>Địa chỉ: {{ $invoice->lease->unit->code ?? '' }}
                            @if($invoice->lease->unit->property)
                                - {{ $invoice->lease->unit->property->name ?? '' }}
                            @endif
                            </p>
                        @endif
                    @else
                        <p>N/A</p>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">STT</th>
                    <th style="width: 45%;">Mô tả</th>
                    <th style="width: 10%;" class="text-center">Số lượng</th>
                    <th style="width: 20%;" class="text-right">Đơn giá</th>
                    <th style="width: 20%;" class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $index = 1;
                @endphp
                @forelse($invoice->items as $item)
                    <tr>
                        <td>{{ $index++ }}</td>
                        <td>{{ $item->description ?? 'N/A' }}</td>
                        <td class="text-center">{{ number_format($item->quantity ?? 1, 0) }}</td>
                        <td class="text-right">{{ number_format($item->unit_price ?? 0, 0) }} {{ $invoice->currency ?? 'VND' }}</td>
                        <td class="text-right">{{ number_format($item->amount ?? 0, 0) }} {{ $invoice->currency ?? 'VND' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px; color: #999;">
                            Không có mục nào
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Summary -->
        <div class="summary">
            <div class="summary-row">
                <div class="summary-label">Tạm tính:</div>
                <div class="summary-value">{{ number_format($invoice->subtotal ?? 0, 0) }} {{ $invoice->currency ?? 'VND' }}</div>
            </div>
            @if($invoice->discount_amount > 0)
            <div class="summary-row">
                <div class="summary-label">Giảm giá:</div>
                <div class="summary-value">-{{ number_format($invoice->discount_amount, 0) }} {{ $invoice->currency ?? 'VND' }}</div>
            </div>
            @endif
            @if($invoice->tax_amount > 0)
            <div class="summary-row">
                <div class="summary-label">Thuế (VAT):</div>
                <div class="summary-value">{{ number_format($invoice->tax_amount, 0) }} {{ $invoice->currency ?? 'VND' }}</div>
            </div>
            @endif
            <div class="summary-row summary-total">
                <div class="summary-label">TỔNG CỘNG:</div>
                <div class="summary-value">{{ number_format($invoice->total_amount ?? 0, 0) }} {{ $invoice->currency ?? 'VND' }}</div>
            </div>
            @if($invoice->status == 'paid' && $invoice->paid_amount > 0)
            <div class="summary-row" style="margin-top: 10px;">
                <div class="summary-label">Đã thanh toán:</div>
                <div class="summary-value" style="color: #28a745;">{{ number_format($invoice->paid_amount, 0) }} {{ $invoice->currency ?? 'VND' }}</div>
            </div>
            @endif
        </div>
        
        <!-- Note Section -->
        @if($invoice->note)
        <div class="note-section">
            <h4>Ghi chú:</h4>
            <p>{{ $invoice->note }}</p>
        </div>
        @endif
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>Hóa đơn được tạo tự động bởi hệ thống</strong></p>
            <p>In ngày: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>

