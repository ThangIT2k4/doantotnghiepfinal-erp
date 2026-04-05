<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hợp đồng {{ $contract->contract_no }}</title>
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
            line-height: 1.8;
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
            text-transform: uppercase;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 14px;
            font-style: italic;
        }
        
        .contract-info {
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #1E4FC8;
        }
        
        .contract-info-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .contract-info-label {
            display: table-cell;
            width: 200px;
            font-weight: bold;
            color: #555;
        }
        
        .contract-info-value {
            display: table-cell;
            color: #333;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1E4FC8;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #1E4FC8;
        }
        
        .section-content {
            font-size: 12px;
            line-height: 2;
            text-align: justify;
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
        
        .terms-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .terms-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        
        .terms-table .term-number {
            width: 50px;
            font-weight: bold;
            color: #1E4FC8;
            text-align: center;
        }
        
        .terms-table .term-content {
            text-align: justify;
        }
        
        .property-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .property-info-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .property-info-label {
            display: table-cell;
            width: 150px;
            font-weight: bold;
            color: #555;
        }
        
        .property-info-value {
            display: table-cell;
            color: #333;
        }
        
        .financial-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .financial-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .financial-label {
            display: table-cell;
            text-align: right;
            padding-right: 15px;
            font-weight: 600;
            color: #555;
        }
        
        .financial-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
            color: #333;
            width: 150px;
        }
        
        .signature-section {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 20px;
        }
        
        .signature-line {
            border-top: 2px solid #333;
            width: 200px;
            margin: 60px auto 10px;
        }
        
        .signature-label {
            font-weight: bold;
            font-size: 13px;
            margin-top: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-active {
            background: #28a745;
            color: #fff;
        }
        
        .status-draft {
            background: #6c757d;
            color: #fff;
        }
        
        .status-terminated {
            background: #dc3545;
            color: #fff;
        }
        
        .status-expired {
            background: #ffc107;
            color: #333;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
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
            <h1>Hợp đồng thuê nhà</h1>
            <div class="subtitle">Rental Contract</div>
        </div>
        
        <!-- Contract Info -->
        <div class="contract-info">
            <div class="contract-info-row">
                <div class="contract-info-label">Số hợp đồng:</div>
                <div class="contract-info-value"><strong>{{ $contract->contract_no }}</strong></div>
            </div>
            <div class="contract-info-row">
                <div class="contract-info-label">Trạng thái:</div>
                <div class="contract-info-value">
                    @if($contract->status == 'active')
                        <span class="status-badge status-active">Đang hiệu lực</span>
                    @elseif($contract->status == 'draft')
                        <span class="status-badge status-draft">Nháp</span>
                    @elseif($contract->status == 'terminated')
                        <span class="status-badge status-terminated">Đã chấm dứt</span>
                    @elseif($contract->status == 'expired')
                        <span class="status-badge status-expired">Hết hạn</span>
                    @endif
                </div>
            </div>
            <div class="contract-info-row">
                <div class="contract-info-label">Ngày bắt đầu:</div>
                <div class="contract-info-value">{{ $contract->start_date->format('d/m/Y') }}</div>
            </div>
            <div class="contract-info-row">
                <div class="contract-info-label">Ngày kết thúc:</div>
                <div class="contract-info-value">{{ $contract->end_date->format('d/m/Y') }}</div>
            </div>
            @if($contract->signed_at)
            <div class="contract-info-row">
                <div class="contract-info-label">Ngày ký:</div>
                <div class="contract-info-value">{{ $contract->signed_at->format('d/m/Y H:i') }}</div>
            </div>
            @endif
        </div>
        
        <!-- Parties -->
        <div class="section">
            <div class="section-title">CÁC BÊN THAM GIA HỢP ĐỒNG</div>
            <div class="parties">
                <div class="party">
                    <div class="party-title">BÊN CHO THUÊ</div>
                    <div class="party-info">
                        <p><strong>{{ $contract->organization->name ?? 'N/A' }}</strong></p>
                        @if($contract->organization->address)
                            <p>{{ $contract->organization->address }}</p>
                        @endif
                        @if($contract->organization->phone)
                            <p>Điện thoại: {{ $contract->organization->phone }}</p>
                        @endif
                        @if($contract->organization->email)
                            <p>Email: {{ $contract->organization->email }}</p>
                        @endif
                        @if($contract->organization->tax_code)
                            <p>Mã số thuế: {{ $contract->organization->tax_code }}</p>
                        @endif
                        @if($contract->agent)
                            <p style="margin-top: 10px;"><strong>Người đại diện:</strong> {{ $contract->agent->full_name ?? $contract->agent->name ?? 'N/A' }}</p>
                        @endif
                    </div>
                </div>
                <div class="party">
                    <div class="party-title">BÊN THUÊ</div>
                    <div class="party-info">
                        @if($contract->tenant)
                            <p><strong>{{ $contract->tenant->full_name ?? $contract->tenant->name ?? 'N/A' }}</strong></p>
                            @if($contract->tenant->phone)
                                <p>Điện thoại: {{ $contract->tenant->phone }}</p>
                            @endif
                            @if($contract->tenant->email)
                                <p>Email: {{ $contract->tenant->email }}</p>
                            @endif
                            @if($contract->tenant->id_card)
                                <p>CMND/CCCD: {{ $contract->tenant->id_card }}</p>
                            @endif
                        @else
                            <p>N/A</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Property Info -->
        <div class="section">
            <div class="section-title">THÔNG TIN TÀI SẢN CHO THUÊ</div>
            <div class="property-info">
                @if($contract->unit)
                    <div class="property-info-row">
                        <div class="property-info-label">Mã phòng/Unit:</div>
                        <div class="property-info-value"><strong>{{ $contract->unit->code ?? 'N/A' }}</strong></div>
                    </div>
                    @if($contract->unit->property)
                        <div class="property-info-row">
                            <div class="property-info-label">Tên bất động sản:</div>
                            <div class="property-info-value">{{ $contract->unit->property->name ?? 'N/A' }}</div>
                        </div>
                        @if($contract->unit->property->location)
                            <div class="property-info-row">
                                <div class="property-info-label">Địa chỉ:</div>
                                <div class="property-info-value">
                                    @if($contract->unit->property->location->street)
                                        {{ $contract->unit->property->location->street }},
                                    @endif
                                    @if($contract->unit->property->location->ward)
                                        {{ $contract->unit->property->location->ward }},
                                    @endif
                                    @if($contract->unit->property->location->district)
                                        {{ $contract->unit->property->location->district }},
                                    @endif
                                    @if($contract->unit->property->location->city)
                                        {{ $contract->unit->property->location->city }}
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if($contract->unit->property->location2025)
                            <div class="property-info-row">
                                <div class="property-info-label">Địa chỉ:</div>
                                <div class="property-info-value">
                                    @if($contract->unit->property->location2025->street)
                                        {{ $contract->unit->property->location2025->street }},
                                    @endif
                                    @if($contract->unit->property->location2025->ward)
                                        {{ $contract->unit->property->location2025->ward }},
                                    @endif
                                    @if($contract->unit->property->location2025->city)
                                        {{ $contract->unit->property->location2025->city }}
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                    @if($contract->unit->area)
                        <div class="property-info-row">
                            <div class="property-info-label">Diện tích:</div>
                            <div class="property-info-value">{{ number_format($contract->unit->area, 0) }} m²</div>
                        </div>
                    @endif
                @else
                    <p>Không có thông tin</p>
                @endif
            </div>
        </div>
        
        <!-- Financial Terms -->
        <div class="section">
            <div class="section-title">ĐIỀU KHOẢN TÀI CHÍNH</div>
            <div class="financial-summary">
                <div class="financial-row">
                    <div class="financial-label">Tiền thuê hàng tháng:</div>
                    <div class="financial-value">{{ number_format($contract->rent_amount ?? 0, 0) }} VND</div>
                </div>
                @if($contract->deposit_amount > 0)
                <div class="financial-row">
                    <div class="financial-label">Tiền đặt cọc:</div>
                    <div class="financial-value">{{ number_format($contract->deposit_amount, 0) }} VND</div>
                </div>
                @endif
                @if($contract->paymentCycle)
                <div class="financial-row">
                    <div class="financial-label">Chu kỳ thanh toán:</div>
                    <div class="financial-value">{{ $contract->paymentCycle->name ?? 'N/A' }}</div>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Terms and Conditions -->
        <div class="section">
            <div class="section-title">ĐIỀU KHOẢN VÀ ĐIỀU KIỆN</div>
            <table class="terms-table">
                <tr>
                    <td class="term-number">1.</td>
                    <td class="term-content">
                        <strong>Thời hạn hợp đồng:</strong> Hợp đồng có hiệu lực từ ngày {{ $contract->start_date->format('d/m/Y') }} đến ngày {{ $contract->end_date->format('d/m/Y') }}.
                    </td>
                </tr>
                <tr>
                    <td class="term-number">2.</td>
                    <td class="term-content">
                        <strong>Tiền thuê:</strong> Bên thuê có nghĩa vụ thanh toán tiền thuê hàng tháng là {{ number_format($contract->rent_amount ?? 0, 0) }} VND theo đúng thời hạn quy định.
                    </td>
                </tr>
                @if($contract->deposit_amount > 0)
                <tr>
                    <td class="term-number">3.</td>
                    <td class="term-content">
                        <strong>Tiền đặt cọc:</strong> Bên thuê đã đặt cọc số tiền {{ number_format($contract->deposit_amount, 0) }} VND. Tiền đặt cọc sẽ được hoàn trả khi kết thúc hợp đồng nếu không có vi phạm.
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="term-number">4.</td>
                    <td class="term-content">
                        <strong>Quyền và nghĩa vụ:</strong> Bên cho thuê có quyền nhận tiền thuê đúng hạn và yêu cầu bên thuê bảo quản tài sản. Bên thuê có nghĩa vụ thanh toán đúng hạn và sử dụng tài sản đúng mục đích.
                    </td>
                </tr>
                <tr>
                    <td class="term-number">5.</td>
                    <td class="term-content">
                        <strong>Chấm dứt hợp đồng:</strong> Hợp đồng có thể được chấm dứt theo thỏa thuận của hai bên hoặc theo quy định của pháp luật.
                    </td>
                </tr>
                @if($contract->termination_date)
                <tr>
                    <td class="term-number">6.</td>
                    <td class="term-content">
                        <strong>Chấm dứt hợp đồng:</strong> Hợp đồng đã được chấm dứt vào ngày {{ $contract->termination_date->format('d/m/Y') }}.
                        @if($contract->termination_reason)
                            Lý do: {{ $contract->termination_reason }}
                        @endif
                    </td>
                </tr>
                @endif
            </table>
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">BÊN CHO THUÊ</div>
                <div style="margin-top: 5px; font-size: 11px;">
                    {{ $contract->organization->name ?? 'N/A' }}
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">BÊN THUÊ</div>
                <div style="margin-top: 5px; font-size: 11px;">
                    {{ $contract->tenant->full_name ?? $contract->tenant->name ?? 'N/A' }}
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>Hợp đồng được tạo tự động bởi hệ thống</strong></p>
            <p>In ngày: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>

