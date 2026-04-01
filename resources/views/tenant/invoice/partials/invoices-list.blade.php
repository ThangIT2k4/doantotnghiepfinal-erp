@php
    use Illuminate\Support\Facades\Storage;
@endphp

@forelse($invoices as $invoice)
    @php
        $now = \Carbon\Carbon::now();
        $dueDate = \Carbon\Carbon::parse($invoice->due_date);
        $isOverdue = $invoice->status === 'issued' && $dueDate < $now;
        
        if ($invoice->status === 'paid') {
            $status = 'paid';
            $statusText = 'Đã thanh toán';
            $statusIcon = 'fas fa-check-circle';
            $statusClass = 'paid';
        } elseif ($isOverdue) {
            $status = 'overdue';
            $statusText = 'Quá hạn';
            $statusIcon = 'fas fa-exclamation-triangle';
            $statusClass = 'overdue';
        } elseif ($invoice->status === 'issued') {
            $status = 'pending';
            $statusText = 'Chờ thanh toán';
            $statusIcon = 'fas fa-clock';
            $statusClass = 'pending';
        } elseif ($invoice->status === 'draft') {
            $status = 'draft';
            $statusText = 'Nháp';
            $statusIcon = 'fas fa-edit';
            $statusClass = 'draft';
        } else {
            $status = 'cancelled';
            $statusText = 'Đã hủy';
            $statusIcon = 'fas fa-times';
            $statusClass = 'cancelled';
        }
    @endphp
    
    <div class="invoice-card-blue" data-status="{{ $status }}">
        <div class="invoice-status-blue {{ $statusClass }}">
            <i class="{{ $statusIcon }}"></i>
            <span>{{ $statusText }}</span>
        </div>
        <div class="invoice-content-blue">
            <div class="row">
                <div class="col-md-3">
                    
                    <div class="invoice-info-blue" style="background: var(--blue-bg-light); padding: 1.5rem; border-radius: 12px;">
                        <div class="invoice-id-blue" style="font-size: 1.1rem; font-weight: 700; color: var(--blue-primary); margin-bottom: 0.75rem;">
                            {{ $invoice->invoice_no ?? 'HD' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}
                        </div>
                        <div class="invoice-date-blue" style="font-size: 0.9em; color: #666; margin-bottom: 0.5rem;">
                            <i class="fas fa-calendar-alt me-2"></i>Ngày tạo: {{ $invoice->issue_date->format('d/m/Y') }}
                        </div>
                        <div class="due-date-blue {{ $isOverdue ? 'overdue' : '' }}" style="font-size: 0.9em; color: {{ $isOverdue ? '#dc3545' : '#666' }}; font-weight: {{ $isOverdue ? '600' : '500' }};">
                            @if($invoice->status === 'paid')
                                <i class="fas fa-check-circle me-2"></i>Đã thanh toán: {{ $invoice->paid_at ? $invoice->paid_at->format('d/m/Y') : 'N/A' }}
                            @else
                                <i class="fas fa-clock me-2"></i>Hạn thanh toán: {{ $invoice->due_date->format('d/m/Y') }}
                            @endif
                        </div>
                        <div class="invoice-period-blue" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--blue-border);">
                            <i class="fas fa-calendar me-2"></i>
                            <strong>Kỳ:</strong> Tháng {{ $invoice->issue_date->format('m/Y') }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="property-info-blue">
                        <h4 class="property-name-blue" style="font-size: 1.5rem; font-weight: 700; color: var(--blue-primary); margin-bottom: 1rem;">
                            {{ $invoice->lease->unit->property->name }}
                            @if($invoice->lease->unit->code)
                                <span class="unit-code-blue" style="font-size: 0.9em; color: #666; font-weight: normal;">- {{ $invoice->lease->unit->code }}</span>
                            @endif
                        </h4>
                        <div class="property-address-blue" style="margin-bottom: 1rem;">
                            <i class="fas fa-map-marker-alt" style="color: var(--blue-primary); margin-right: 0.5rem;"></i>
                            <div class="address-info">
                                @php
                                    $locationAddress = null;
                                    $location2025Address = null;
                                    
                                    if ($invoice->lease->unit->property->location) {
                                        $addressParts = [];
                                        if ($invoice->lease->unit->property->location->street) $addressParts[] = $invoice->lease->unit->property->location->street;
                                        if ($invoice->lease->unit->property->location->ward) $addressParts[] = $invoice->lease->unit->property->location->ward;
                                        if ($invoice->lease->unit->property->location->district) $addressParts[] = $invoice->lease->unit->property->location->district;
                                        if ($invoice->lease->unit->property->location->city) $addressParts[] = $invoice->lease->unit->property->location->city;
                                        if ($invoice->lease->unit->property->location->country && $invoice->lease->unit->property->location->country !== 'Vietnam') $addressParts[] = $invoice->lease->unit->property->location->country;
                                        $locationAddress = !empty($addressParts) ? implode(', ', $addressParts) : null;
                                    }
                                    
                                    if ($invoice->lease->unit->property->location2025) {
                                        $addressParts2025 = [];
                                        if ($invoice->lease->unit->property->location2025->street) $addressParts2025[] = $invoice->lease->unit->property->location2025->street;
                                        if ($invoice->lease->unit->property->location2025->ward) $addressParts2025[] = $invoice->lease->unit->property->location2025->ward;
                                        if ($invoice->lease->unit->property->location2025->city) $addressParts2025[] = $invoice->lease->unit->property->location2025->city;
                                        if ($invoice->lease->unit->property->location2025->country && $invoice->lease->unit->property->location2025->country !== 'Vietnam') $addressParts2025[] = $invoice->lease->unit->property->location2025->country;
                                        $location2025Address = !empty($addressParts2025) ? implode(', ', $addressParts2025) : null;
                                    }
                                @endphp
                                @if($locationAddress)
                                    <div class="address-item" style="margin-bottom: 0.5rem;">
                                        <span class="address-label" style="font-size: 0.85em; color: #999; font-weight: 500; display: block; margin-bottom: 0.25rem;">Địa chỉ cũ:</span>
                                        <span class="address-value" style="font-size: 0.95em; color: #333;">{{ $locationAddress }}</span>
                                    </div>
                                @endif
                                @if($location2025Address)
                                    <div class="address-item" style="margin-bottom: 0.5rem;">
                                        <span class="address-label" style="font-size: 0.85em; color: #999; font-weight: 500; display: block; margin-bottom: 0.25rem;">Địa chỉ mới:</span>
                                        <span class="address-value" style="font-size: 0.95em; color: #333;">{{ $location2025Address }}</span>
                                    </div>
                                @endif
                                @if(!$locationAddress && !$location2025Address)
                                    <span class="address-value" style="font-size: 0.95em; color: #333;">Địa chỉ chưa cập nhật</span>
                                @endif
                            </div>
                        </div>
                        <div class="invoice-details-blue">
                            <div class="detail-item" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #F5F5F5;">
                                <span class="label" style="font-weight: 600; color: #666; flex: 0 0 40%;">Chủ nhà:</span>
                                <span class="value" style="color: #333; text-align: right; flex: 1;">
                                    @if($invoice->lease->agent)
                                        {{ $invoice->lease->agent->full_name ?? $invoice->lease->agent->name }}
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                            <div class="detail-item" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #F5F5F5;">
                                <span class="label" style="font-weight: 600; color: #666; flex: 0 0 40%;">Số điện thoại:</span>
                                <span class="value" style="color: #333; text-align: right; flex: 1;">
                                    @if($invoice->lease->agent && $invoice->lease->agent->phone)
                                        <a href="tel:{{ $invoice->lease->agent->phone }}" style="color: var(--blue-primary); text-decoration: none;">{{ $invoice->lease->agent->phone }}</a>
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                            @if($invoice->items->count() > 0)
                                <div class="detail-item" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #F5F5F5;">
                                    <span class="label" style="font-weight: 600; color: #666; flex: 0 0 40%;">Chi tiết:</span>
                                    <div class="value" style="text-align: right; flex: 1;">
                                        @foreach($invoice->items->take(3) as $item)
                                            <div style="font-size: 0.85em; color: #555; margin-bottom: 2px;">
                                                {{ $item->description }}: {{ number_format($item->amount) }} VNĐ
                                            </div>
                                        @endforeach
                                        @if($invoice->items->count() > 3)
                                            <div style="font-size: 0.85em; color: #999;">... và {{ $invoice->items->count() - 3 }} mục khác</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="invoice-amount-section" style="background: var(--blue-bg-light); padding: 1.5rem; border-radius: 12px; height: 100%;">
                        <div class="amount-header" style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 2px solid var(--blue-border);">
                            <div class="amount-label" style="font-size: 0.85em; color: #666; font-weight: 600; margin-bottom: 0.5rem;">Tổng tiền</div>
                            <div class="amount-value" style="font-size: 1.8rem; color: var(--blue-primary); font-weight: 700;">{{ number_format($invoice->total_amount) }}</div>
                            <div class="amount-currency" style="font-size: 0.9em; color: #666; margin-top: 0.25rem;">VNĐ</div>
                        </div>
                        <div class="payment-method-section" style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--blue-border);">
                            <div class="method-label" style="font-size: 0.85em; color: #666; font-weight: 600; margin-bottom: 0.5rem;">Phương thức thanh toán</div>
                            <div class="method-value">
                                @if($invoice->payment_method)
                                    <div class="method-badge" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: white; border-radius: 8px; border: 1px solid var(--blue-border);">
                                        @switch($invoice->payment_method)
                                            @case('momo')
                                                <img src="https://developers.momo.vn/v3/assets/images/logo.png" alt="MoMo" style="width: 24px; height: 24px;">
                                                <span style="font-weight: 600; color: #333;">MoMo</span>
                                                @break
                                            @case('bank')
                                                <i class="fas fa-university" style="color: var(--blue-primary); font-size: 1.2rem;"></i>
                                                <span style="font-weight: 600; color: #333;">Chuyển khoản</span>
                                                @break
                                            @case('vnpay')
                                                <img src="https://vnpay.vn/s1/statics.vnpay.vn/2023/9/06ncktiwd6dc1694418196384.png" alt="VNPay" style="width: 24px; height: 24px;">
                                                <span style="font-weight: 600; color: #333;">VNPay</span>
                                                @break
                                            @case('zalopay')
                                                <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-ZaloPay-Square.png" alt="ZaloPay" style="width: 24px; height: 24px;">
                                                <span style="font-weight: 600; color: #333;">ZaloPay</span>
                                                @break
                                        @endswitch
                                    </div>
                                @else
                                    <span style="color: #999; font-size: 0.9em;">Chưa chọn</span>
                                @endif
                            </div>
                        </div>
                        @if($invoice->status === 'paid' && $invoice->paid_at)
                            <div class="payment-date-section">
                                <div class="date-label" style="font-size: 0.85em; color: #666; font-weight: 600; margin-bottom: 0.5rem;">Ngày thanh toán</div>
                                <div class="date-value" style="font-size: 1rem; color: #28a745; font-weight: 600;">
                                    <i class="fas fa-check-circle me-2"></i>{{ $invoice->paid_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        @elseif($invoice->status === 'issued')
                            <div class="due-date-section {{ $isOverdue ? 'overdue' : '' }}">
                                <div class="date-label" style="font-size: 0.85em; color: #666; font-weight: 600; margin-bottom: 0.5rem;">Hạn thanh toán</div>
                                <div class="date-value" style="font-size: 1rem; color: {{ $isOverdue ? '#dc3545' : '#333' }}; font-weight: 600;">
                                    <i class="fas fa-clock me-2"></i>{{ $invoice->due_date->format('d/m/Y') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="invoice-actions-blue" style="padding: 1rem 1.5rem; background: var(--blue-bg-light); border-top: 1px solid var(--blue-border); display: flex; gap: 0.75rem; flex-wrap: wrap;">
            @if($invoice->status === 'issued')
                <a href="{{ route('tenant.payments.methods', $invoice->id) }}" class="btn {{ $isOverdue ? 'btn-danger' : 'btn-primary-blue' }}" style="border-radius: 10px; font-weight: 600; transition: all 0.3s ease;">
                    <i class="fas fa-credit-card me-1"></i>{{ $isOverdue ? 'Thanh toán ngay' : 'Thanh toán' }}
                </a>
            @endif
            <a href="{{ route('tenant.invoices.show', $invoice->id) }}" class="btn btn-outline-blue" style="border-radius: 10px; font-weight: 600; transition: all 0.3s ease;">
                <i class="fas fa-eye me-1"></i>Xem chi tiết
            </a>
            @if($invoice->status === 'paid')
                @php
                    $payment = $invoice->payments()->orderBy('created_at', 'desc')->first();
                @endphp
                @if($payment)
                    <a href="{{ route('tenant.payments.status', $payment->id) }}" class="btn btn-outline-info" style="border-radius: 10px; font-weight: 600; transition: all 0.3s ease;">
                        <i class="fas fa-eye me-1"></i>Xem thanh toán
                    </a>
                @else
                    <a href="{{ route('tenant.payments.index', ['invoice_id' => $invoice->id]) }}" class="btn btn-outline-info" style="border-radius: 10px; font-weight: 600; transition: all 0.3s ease;">
                        <i class="fas fa-eye me-1"></i>Xem thanh toán
                    </a>
                @endif
            @endif
        </div>
    </div>
@empty
    <!-- Empty State -->
    <div class="empty-state-blue">
        <div class="empty-icon">
            <i class="fas fa-file-invoice"></i>
        </div>
        <h3>Không có hóa đơn nào</h3>
        <p>Bạn chưa có hóa đơn nào. Hãy kiểm tra lại sau!</p>
    </div>
@endforelse

<!-- Pagination -->
@if($invoices->hasPages())
    {{ $invoices->appends(request()->query())->links('vendor.pagination.custom', [
        'tableContainerId' => 'invoices-list-container',
        'htmxIndicator' => '#htmx-loading'
    ]) }}
@endif

