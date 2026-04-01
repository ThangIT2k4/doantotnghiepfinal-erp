@php
    use Illuminate\Support\Facades\Storage;
@endphp

@forelse($contracts as $contract)
    @php
        $now = \Carbon\Carbon::now();
        $endDate = \Carbon\Carbon::parse($contract->end_date);
        $remainingDays = $now->diffInDays($endDate, false);
        $isExpired = $endDate < $now;
        $isExpiring = !$isExpired && $remainingDays <= 30;
        
        if ($isExpired) {
            $status = 'expired';
            $statusText = 'Đã hết hạn';
            $statusIcon = 'fas fa-times-circle';
            $statusClass = 'expired';
        } elseif ($isExpiring) {
            $status = 'expiring';
            $statusText = 'Sắp hết hạn';
            $statusIcon = 'fas fa-exclamation-triangle';
            $statusClass = 'expiring';
        } else {
            $status = 'active';
            $statusText = 'Đang hiệu lực';
            $statusIcon = 'fas fa-check-circle';
            $statusClass = 'active';
        }
        
        // Calculate remaining time text
        if ($isExpired) {
            $remainingText = 'Đã hết hạn';
        } elseif ($remainingDays < 30) {
            $remainingText = "Còn " . abs(round($remainingDays)) . " ngày";
        } else {
            $remainingMonths = floor(abs($remainingDays) / 30);
            $remainingText = "Còn {$remainingMonths} tháng";
        }
    @endphp
    
    <div class="contract-card-blue" data-status="{{ $status }}">
        <div class="contract-status-blue {{ $statusClass }}">
            <i class="{{ $statusIcon }}"></i>
            <span>{{ $statusText }}</span>
        </div>
        <div class="contract-content-blue">
            <div class="row">
                <div class="col-md-3">
                    <div class="property-image" style="border-radius: 12px; overflow: hidden; margin-bottom: 1rem;">
                        @if($contract->unit->property->images && count($contract->unit->property->images) > 0)
                            <img src="{{ Storage::url($contract->unit->property->images[0]) }}" 
                                 alt="{{ $contract->unit->property->name }}"
                                 style="width: 100%; height: 200px; object-fit: cover;">
                        @else
                            <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=300&h=200&fit=crop" 
                                 alt="{{ $contract->unit->property->name }}"
                                 style="width: 100%; height: 200px; object-fit: cover;">
                        @endif
                        <div class="contract-type" style="position: absolute; top: 10px; right: 10px;">
                            <span class="badge" style="background: var(--blue-gradient); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600;">Thuê phòng</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="contract-info">
                        <h4 class="contract-title-blue">
                            {{ $contract->unit->property->name }}
                            @if($contract->unit->code)
                                <span class="unit-code" style="font-size: 0.9em; color: #666; font-weight: normal;">- {{ $contract->unit->code }}</span>
                            @endif
                        </h4>
                        <div class="property-address-blue">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="address-info">
                                @php
                                    $locationAddress = null;
                                    $location2025Address = null;
                                    
                                    if ($contract->unit->property->location) {
                                        $addressParts = [];
                                        if ($contract->unit->property->location->street) $addressParts[] = $contract->unit->property->location->street;
                                        if ($contract->unit->property->location->ward) $addressParts[] = $contract->unit->property->location->ward;
                                        if ($contract->unit->property->location->district) $addressParts[] = $contract->unit->property->location->district;
                                        if ($contract->unit->property->location->city) $addressParts[] = $contract->unit->property->location->city;
                                        if ($contract->unit->property->location->country && $contract->unit->property->location->country !== 'Vietnam') $addressParts[] = $contract->unit->property->location->country;
                                        $locationAddress = !empty($addressParts) ? implode(', ', $addressParts) : null;
                                    }
                                    
                                    if ($contract->unit->property->location2025) {
                                        $addressParts2025 = [];
                                        if ($contract->unit->property->location2025->street) $addressParts2025[] = $contract->unit->property->location2025->street;
                                        if ($contract->unit->property->location2025->ward) $addressParts2025[] = $contract->unit->property->location2025->ward;
                                        if ($contract->unit->property->location2025->city) $addressParts2025[] = $contract->unit->property->location2025->city;
                                        if ($contract->unit->property->location2025->country && $contract->unit->property->location2025->country !== 'Vietnam') $addressParts2025[] = $contract->unit->property->location2025->country;
                                        $location2025Address = !empty($addressParts2025) ? implode(', ', $addressParts2025) : null;
                                    }
                                @endphp
                                @if($locationAddress)
                                    <div class="address-item">
                                        <span class="address-label">Địa chỉ cũ:</span>
                                        <span class="address-value">{{ $locationAddress }}</span>
                                    </div>
                                @endif
                                @if($location2025Address)
                                    <div class="address-item">
                                        <span class="address-label">Địa chỉ mới:</span>
                                        <span class="address-value">{{ $location2025Address }}</span>
                                    </div>
                                @endif
                                @if(!$locationAddress && !$location2025Address)
                                    <span class="address-value">Địa chỉ chưa cập nhật</span>
                                @endif
                            </div>
                        </div>
                        <div class="contract-details-blue">
                            <div class="detail-item">
                                <span class="label">Mã hợp đồng:</span>
                                <span class="value">{{ $contract->contract_no ?? 'HD' . str_pad($contract->id, 6, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Ngày ký:</span>
                                <span class="value">{{ $contract->signed_at ? $contract->signed_at->format('d/m/Y') : 'Chưa ký' }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Ngày bắt đầu:</span>
                                <span class="value">{{ $contract->start_date->format('d/m/Y') }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Ngày kết thúc:</span>
                                <span class="value">{{ $contract->end_date->format('d/m/Y') }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Chủ nhà:</span>
                                <span class="value">
                                    @if($contract->agent)
                                        {{ $contract->agent->full_name ?? $contract->agent->name }} - {{ $contract->agent->phone ?? 'N/A' }}
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Giá thuê:</span>
                                <span class="value price">{{ number_format($contract->rent_amount) }} VNĐ/tháng</span>
                            </div>
                            @php
                                $effectiveServiceSet = $contract->getEffectiveLeaseServiceSet();
                                $serviceItems = $effectiveServiceSet?->items ?? collect();
                            @endphp
                            @if($serviceItems->count() > 0)
                                <div class="detail-item">
                                    <span class="label">Dịch vụ đi kèm:</span>
                                    <div class="value">
                                        @foreach($serviceItems as $item)
                                            <span class="service-item" style="display: block; font-size: 0.85em; color: #555; margin-bottom: 2px;">
                                                {{ $item->service->name ?? 'N/A' }}: {{ number_format($item->price, 0, ',', '.') }} VNĐ
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="contract-dates" style="background: var(--blue-bg-light); padding: 1.5rem; border-radius: 12px; height: 100%;">
                        <div class="date-item" style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--blue-border);">
                            <div class="date-label" style="font-size: 0.85em; color: #666; font-weight: 600; margin-bottom: 0.5rem;">Ngày ký</div>
                            <div class="date-value" style="font-size: 1.1rem; color: #333; font-weight: 600;">{{ $contract->signed_at ? $contract->signed_at->format('d/m/Y') : 'Chưa ký' }}</div>
                        </div>
                        <div class="date-item" style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--blue-border);">
                            <div class="date-label" style="font-size: 0.85em; color: #666; font-weight: 600; margin-bottom: 0.5rem;">Ngày bắt đầu</div>
                            <div class="date-value" style="font-size: 1.1rem; color: #333; font-weight: 600;">{{ $contract->start_date->format('d/m/Y') }}</div>
                        </div>
                        <div class="date-item {{ $isExpired ? 'expired' : ($isExpiring ? 'urgent' : '') }}" style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--blue-border);">
                            <div class="date-label" style="font-size: 0.85em; color: #666; font-weight: 600; margin-bottom: 0.5rem;">Ngày kết thúc</div>
                            <div class="date-value" style="font-size: 1.1rem; color: {{ $isExpired ? '#dc3545' : ($isExpiring ? '#ffc107' : '#333') }}; font-weight: 600;">{{ $contract->end_date->format('d/m/Y') }}</div>
                        </div>
                        <div class="remaining-time {{ $isExpired ? 'expired' : ($isExpiring ? 'urgent' : '') }}" style="text-align: center; padding: 1rem; background: white; border-radius: 10px; border: 2px solid {{ $isExpired ? '#dc3545' : ($isExpiring ? '#ffc107' : 'var(--blue-primary)') }};">
                            <i class="{{ $isExpired ? 'fas fa-times-circle' : ($isExpiring ? 'fas fa-exclamation-circle' : 'fas fa-calendar-alt') }}" style="font-size: 1.5rem; color: {{ $isExpired ? '#dc3545' : ($isExpiring ? '#ffc107' : 'var(--blue-primary)') }}; margin-bottom: 0.5rem; display: block;"></i>
                            <span style="font-weight: 700; color: {{ $isExpired ? '#dc3545' : ($isExpiring ? '#ffc107' : 'var(--blue-primary)') }}; font-size: 1.1rem;">{{ $remainingText }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="contract-actions-blue">
            <a href="{{ route('tenant.contracts.show', $contract->id) }}" class="btn btn-outline-blue">
                <i class="fas fa-eye me-2"></i>Xem chi tiết
            </a>
            
            @if($contract->agent && $contract->agent->phone)
                <a href="tel:{{ $contract->agent->phone }}" class="btn btn-outline-blue">
                    <i class="fas fa-phone me-2"></i>Liên hệ
                </a>
            @endif
        </div>
    </div>
@empty
    <!-- Empty State -->
    <div class="empty-state-blue">
        <div class="empty-icon">
            <i class="fas fa-file-contract"></i>
        </div>
        <h3>Không có hợp đồng nào</h3>
        <p>Bạn chưa có hợp đồng thuê nhà nào. Hãy tìm kiếm và thuê phòng mới!</p>
        <a href="{{ route('home') }}" class="btn btn-primary-blue">
            <i class="fas fa-search me-2"></i>Tìm phòng ngay
        </a>
    </div>
@endforelse

@if(isset($contracts) && $contracts->hasPages())
    <div class="pagination-section" style="margin-top: 2rem;">
        <nav aria-label="Contracts pagination">
            {{ $contracts->appends(request()->query())->links('vendor.pagination.custom', [
                'tableContainerId' => 'contracts-list-container',
                'htmxIndicator' => '#htmx-loading'
            ]) }}
        </nav>
    </div>
@endif
