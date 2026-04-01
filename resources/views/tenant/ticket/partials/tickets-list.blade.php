@forelse($tickets as $ticket)
    <div class="col-md-12 mb-4">
        <div class="ticket-card-blue">
            <div class="ticket-header-blue">
                <div class="ticket-info-blue">
                    <div class="ticket-title-blue">
                        <h5 class="mb-1">
                            <a href="{{ route('tenant.tickets.show', $ticket->id) }}" class="text-decoration-none">
                                {{ $ticket->title }}
                            </a>
                        </h5>
                        <div class="ticket-meta-blue">
                            <span class="ticket-id-blue">#{{ $ticket->id }}</span>
                            <span class="ticket-date-blue">
                                <i class="fas fa-calendar-alt me-1"></i>
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="ticket-badges-blue">
                    <span class="priority-badge-blue priority-{{ $ticket->priority }}">
                        {{ $ticket->priority_label }}
                    </span>
                    @php
                        $statusLabels = [
                            'open' => 'Mở',
                            'in_progress' => 'Đang xử lý',
                            'resolved' => 'Đã giải quyết',
                            'closed' => 'Đã đóng',
                            'cancelled' => 'Đã hủy'
                        ];
                    @endphp
                    <span class="status-badge-blue status-{{ $ticket->status }}">
                        {{ $statusLabels[$ticket->status] ?? ucfirst($ticket->status) }}
                    </span>
                </div>
            </div>
            
            <div class="ticket-body-blue">
                <div class="ticket-description-blue">
                    <p class="mb-2">{{ Str::limit($ticket->description, 150) }}</p>
                    @php
                        $hasImage = $ticket->documents()
                            ->where('document_type', 'image')
                            ->exists();
                    @endphp
                    @if($hasImage)
                        <div class="mt-2">
                            <i class="fas fa-image text-info" title="Có hình ảnh đính kèm"></i>
                            <small class="text-muted ms-1">Có hình ảnh</small>
                        </div>
                    @endif
                </div>
                
                <div class="ticket-details-blue">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item-blue">
                                <label>Địa chỉ:</label>
                                <div class="address-info-blue">
                                    <div class="address-item-blue">
                                        <span class="address-label-blue">Tòa nhà:</span>
                                        <span class="address-value-blue">{{ $ticket->property_name ?: 'Chưa xác định' }}</span>
                                    </div>
                                    <div class="address-item-blue">
                                        <span class="address-label-blue">Địa chỉ:</span>
                                        <span class="address-value-blue">
                                            @if($ticket->location_address)
                                                {{ $ticket->location_address }}
                                                @if($ticket->location2025_address && $ticket->location2025_address != $ticket->location_address)
                                                    <br><small class="text-muted">(2025: {{ $ticket->location2025_address }})</small>
                                                @endif
                                            @elseif($ticket->location2025_address)
                                                {{ $ticket->location2025_address }}
                                            @else
                                                Chưa xác định
                                            @endif
                                        </span>
                                    </div>
                                    <div class="address-item-blue">
                                        <span class="address-label-blue">Phòng:</span>
                                        <span class="address-value-blue">{{ $ticket->unit_name ?: 'Chưa xác định' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item-blue">
                                <label>Người xử lý:</label>
                                <span>{{ $ticket->assigned_to_name ?: 'Chưa phân công' }}</span>
                            </div>
                            <div class="detail-item-blue">
                                <label>Cập nhật cuối:</label>
                                <span>{{ $ticket->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="ticket-actions-blue">
                <a href="{{ route('tenant.tickets.show', $ticket->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-eye me-1"></i>Xem chi tiết
                </a>
                @if(in_array($ticket->status, ['open', 'in_progress']))
                    <a href="{{ route('tenant.tickets.edit', $ticket->id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-edit me-1"></i>Chỉnh sửa
                    </a>
                    <form method="POST" action="{{ route('tenant.tickets.destroy', $ticket->id) }}" 
                          class="d-inline delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-ban me-1"></i>Hủy
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="col-12">
        <div class="empty-state-blue">
            <div class="empty-icon-blue">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <h3>Chưa có ticket nào</h3>
            <p>Bạn chưa tạo ticket nào. Hãy tạo ticket đầu tiên để báo cáo sự cố hoặc yêu cầu sửa chữa.</p>
            <a href="{{ route('tenant.tickets.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Tạo ticket đầu tiên
            </a>
        </div>
    </div>
@endforelse

@if($tickets->hasPages())
    <div class="col-12">
        <div class="pagination-section-blue">
            {{ $tickets->appends(request()->query())->links('vendor.pagination.custom', [
                'tableContainerId' => 'tickets-list-container',
                'htmxIndicator' => '#htmx-loading'
            ]) }}
        </div>
    </div>
@endif

