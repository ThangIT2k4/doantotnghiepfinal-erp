@forelse($payments as $payment)
    @php
        $statusConfig = [
            'success' => [
                'text' => 'Thành công',
                'icon' => 'fas fa-check-circle',
                'class' => 'success'
            ],
            'pending' => [
                'text' => 'Đang chờ',
                'icon' => 'fas fa-clock',
                'class' => 'pending'
            ],
            'failed' => [
                'text' => 'Thất bại',
                'icon' => 'fas fa-times-circle',
                'class' => 'failed'
            ],
            'refunded' => [
                'text' => 'Hoàn tiền',
                'icon' => 'fas fa-undo',
                'class' => 'refunded'
            ]
        ];
        $status = $statusConfig[$payment->status] ?? $statusConfig['pending'];
    @endphp
    
    <div class="payment-card-blue" data-status="{{ $payment->status }}">
        <div class="payment-status-blue {{ $status['class'] }}">
            <i class="{{ $status['icon'] }}"></i>
            <span>{{ $status['text'] }}</span>
        </div>
        <div class="payment-content-blue">
            <div class="row">
                <div class="col-md-3">
                    <div class="payment-info-blue" style="background: var(--blue-bg-light); padding: 1.5rem; border-radius: 12px;">
                        <div class="payment-id-blue" style="font-size: 1.1rem; font-weight: 700; color: var(--blue-primary); margin-bottom: 0.75rem;">
                            #{{ $payment->id }}
                        </div>
                        @if($payment->txn_ref)
                        <div class="payment-txn-blue" style="font-size: 0.85em; color: #666; margin-bottom: 0.5rem;">
                            <i class="fas fa-hashtag me-2"></i>{{ $payment->txn_ref }}
                        </div>
                        @endif
                        <div class="payment-date-blue" style="font-size: 0.9em; color: #666; margin-bottom: 0.5rem;">
                            <i class="fas fa-calendar-alt me-2"></i>Ngày tạo: {{ $payment->created_at->format('d/m/Y') }}
                        </div>
                        @if($payment->paid_at)
                        <div class="paid-date-blue" style="font-size: 0.9em; color: #28a745; font-weight: 600;">
                            <i class="fas fa-check-circle me-2"></i>Thanh toán: {{ $payment->paid_at->format('d/m/Y H:i') }}
                        </div>
                        @endif
                        <div class="payment-method-info" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--blue-border);">
                            @if($payment->method)
                                <div class="method-badge" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: white; border-radius: 8px; border: 1px solid var(--blue-border);">
                                    @if($payment->method->key_code === 'cash')
                                        <i class="fas fa-money-bill-wave" style="color: var(--blue-primary); font-size: 1.2rem;"></i>
                                        <span style="font-weight: 600; color: #333;">Tiền mặt</span>
                                    @elseif($payment->method->key_code === 'sepay')
                                        <i class="fas fa-qrcode" style="color: var(--blue-primary); font-size: 1.2rem;"></i>
                                        <span style="font-weight: 600; color: #333;">Sepay</span>
                                    @else
                                        <i class="fas fa-university" style="color: var(--blue-primary); font-size: 1.2rem;"></i>
                                        <span style="font-weight: 600; color: #333;">{{ $payment->method->name }}</span>
                                    @endif
                                </div>
                            @else
                                <span style="color: #999; font-size: 0.9em;">Chưa xác định</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="invoice-info-blue">
                        <h4 class="invoice-title-blue" style="font-size: 1.5rem; font-weight: 700; color: var(--blue-primary); margin-bottom: 1rem;">
                            {{ $payment->invoice->invoice_no ?? 'HD' . str_pad($payment->invoice->id, 6, '0', STR_PAD_LEFT) }}
                        </h4>
                        <div class="property-info-blue" style="margin-bottom: 1rem;">
                            <h5 class="property-name-blue" style="font-size: 1.2rem; font-weight: 600; color: #333; margin-bottom: 0.5rem;">
                                {{ $payment->invoice->lease->unit->property->name ?? 'N/A' }}
                                @if($payment->invoice->lease->unit->unit_number ?? null)
                                    <span class="unit-code-blue" style="font-size: 0.9em; color: #666; font-weight: normal;">- {{ $payment->invoice->lease->unit->unit_number }}</span>
                                @endif
                            </h5>
                            <div class="property-address-blue">
                                <i class="fas fa-map-marker-alt" style="color: var(--blue-primary); margin-right: 0.5rem;"></i>
                                <span style="font-size: 0.95em; color: #666;">
                                    @if($payment->invoice->lease->unit->property->location)
                                        @php
                                            $addressParts = [];
                                            if ($payment->invoice->lease->unit->property->location->street) $addressParts[] = $payment->invoice->lease->unit->property->location->street;
                                            if ($payment->invoice->lease->unit->property->location->ward) $addressParts[] = $payment->invoice->lease->unit->property->location->ward;
                                            if ($payment->invoice->lease->unit->property->location->district) $addressParts[] = $payment->invoice->lease->unit->property->location->district;
                                            if ($payment->invoice->lease->unit->property->location->city) $addressParts[] = $payment->invoice->lease->unit->property->location->city;
                                            $address = !empty($addressParts) ? implode(', ', $addressParts) : 'Địa chỉ chưa cập nhật';
                                        @endphp
                                        {{ $address }}
                                    @else
                                        Địa chỉ chưa cập nhật
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="payment-details-blue">
                            <div class="detail-item" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #F5F5F5;">
                                <span class="label" style="font-weight: 600; color: #666; flex: 0 0 40%;">Kỳ thanh toán:</span>
                                <span class="value" style="color: #333; text-align: right; flex: 1;">
                                    Tháng {{ $payment->invoice->issue_date->format('m/Y') }}
                                </span>
                            </div>
                            @if($payment->note)
                            <div class="detail-item" style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #F5F5F5;">
                                <span class="label" style="font-weight: 600; color: #666; flex: 0 0 40%;">Ghi chú:</span>
                                <span class="value" style="color: #333; text-align: right; flex: 1; font-size: 0.9em;">
                                    {{ $payment->note }}
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="payment-amount-section" style="background: var(--blue-bg-light); padding: 1.5rem; border-radius: 12px; height: 100%;">
                        <div class="amount-header" style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 2px solid var(--blue-border);">
                            <div class="amount-label" style="font-size: 0.85em; color: #666; font-weight: 600; margin-bottom: 0.5rem;">Số tiền</div>
                            <div class="amount-value" style="font-size: 1.8rem; color: var(--blue-primary); font-weight: 700;">{{ number_format($payment->amount) }}</div>
                            <div class="amount-currency" style="font-size: 0.9em; color: #666; margin-top: 0.25rem;">VNĐ</div>
                        </div>
                        <div class="status-section" style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--blue-border);">
                            <div class="status-label" style="font-size: 0.85em; color: #666; font-weight: 600; margin-bottom: 0.5rem;">Trạng thái</div>
                            <div class="status-value">
                                <span class="status-badge-blue {{ $status['class'] }}" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: white; border-radius: 8px; border: 1px solid var(--blue-border);">
                                    <i class="{{ $status['icon'] }}"></i>
                                    <span style="font-weight: 600; color: #333;">{{ $status['text'] }}</span>
                                </span>
                            </div>
                        </div>
                        <div class="time-section">
                            <div class="time-label" style="font-size: 0.85em; color: #666; font-weight: 600; margin-bottom: 0.5rem;">Thời gian</div>
                            <div class="time-value" style="font-size: 0.95em; color: #333; font-weight: 500;">
                                <i class="fas fa-clock me-2"></i>{{ $payment->created_at->format('H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="payment-actions-blue" style="padding: 1rem 1.5rem; background: var(--blue-bg-light); border-top: 1px solid var(--blue-border); display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="{{ route('tenant.payments.status', $payment->id) }}" class="btn btn-outline-blue" style="border-radius: 10px; font-weight: 600; transition: all 0.3s ease;">
                <i class="fas fa-eye me-1"></i>Xem chi tiết
            </a>
            @if($payment->status === 'pending' && $payment->method && $payment->method->key_code === 'sepay')
                <button class="btn btn-outline-info" onclick="showQRCode({{ $payment->id }})" style="border-radius: 10px; font-weight: 600; transition: all 0.3s ease;" title="Xem QR Code">
                    <i class="fas fa-qrcode me-1"></i>QR Code
                </button>
            @endif
            @if($payment->invoice)
                <a href="{{ route('tenant.invoices.show', $payment->invoice->id) }}" class="btn btn-outline-primary" style="border-radius: 10px; font-weight: 600; transition: all 0.3s ease; border: 2px solid var(--blue-primary); color: var(--blue-primary); background: white;">
                    <i class="fas fa-file-invoice me-1"></i>Xem hóa đơn
                </a>
            @endif
        </div>
    </div>
@empty
    <!-- Empty State -->
    <div class="empty-state-blue">
        <div class="empty-icon">
            <i class="fas fa-credit-card"></i>
        </div>
        <h3>Không có giao dịch nào</h3>
        <p>Bạn chưa có giao dịch thanh toán nào. Hãy thanh toán hóa đơn để xem lịch sử.</p>
        <a href="{{ route('tenant.invoices.index') }}" class="btn btn-primary-blue">
            <i class="fas fa-file-invoice me-1"></i>Xem hóa đơn
        </a>
    </div>
@endforelse

<!-- Pagination -->
@if($payments->hasPages())
    {{ $payments->appends(request()->query())->links('vendor.pagination.custom', [
        'tableContainerId' => 'payments-list-container',
        'htmxIndicator' => '#htmx-loading'
    ]) }}
@endif

