@forelse($notifications as $notification)
    @php
        $isImportant = str_contains($notification->subject, 'quá hạn') || 
                       str_contains($notification->subject, 'khẩn cấp') || 
                       str_contains($notification->subject, 'hết hạn');
        $isUnread = $notification->status === 'queued';
    @endphp
    
    <div class="notification-card-blue {{ $isUnread ? 'unread' : 'read' }} 
        {{ $isImportant ? 'important' : '' }}" 
        data-status="{{ $isUnread ? 'unread' : 'read' }}" 
        data-type="{{ $notification->type }}">
        
        <!-- Status Badge -->
        <div class="notification-status-badge-blue {{ $isUnread ? 'unread' : 'read' }}">
            <i class="{{ $isUnread ? 'fas fa-circle' : 'fas fa-check-circle' }}"></i>
            <span>{{ $isUnread ? 'Chưa đọc' : 'Đã đọc' }}</span>
        </div>
        
        <!-- Main Content -->
        <div class="notification-content-blue">
            <div class="row">
                <div class="col-md-1">
                    <div class="notification-icon-blue {{ $notification->type }}">
                        <i class="{{ $notification->icon }}"></i>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="notification-info-blue">
                        <div class="notification-header-blue">
                            <h4 class="notification-title-blue">{{ $notification->subject }}</h4>
                            <div class="notification-time-blue">
                                <i class="fas fa-clock me-1"></i>
                                {{ $notification->created_at->diffForHumans() }}
                            </div>
                        </div>
                        <div class="notification-message-blue">
                            {!! nl2br(e($notification->content)) !!}
                        </div>
                        <div class="notification-meta-blue">
                            <span class="meta-item-blue">
                                <i class="fas fa-tag me-1"></i>
                                {{ ucfirst($notification->type) }}
                            </span>
                            <span class="meta-item-blue">
                                <i class="fas fa-calendar me-1"></i>
                                {{ $notification->created_at->format('d/m/Y H:i') }}
                            </span>
                            @if($isImportant)
                            <span class="meta-item-blue important">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Quan trọng
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="notification-actions-blue">
                        @if(isset($notification->entity_link) && $notification->entity_link)
                        <a href="{{ $notification->entity_link }}" 
                           class="btn btn-primary btn-sm w-100 mb-2" 
                           onclick="markAsReadAndNavigate({{ $notification->id }}, event)"
                           style="border-radius: 10px; font-weight: 600; transition: all 0.3s ease; background: var(--blue-primary); border: 2px solid var(--blue-primary); color: white; text-decoration: none; display: inline-block;">
                            <i class="fas fa-external-link-alt me-1"></i>Xem chi tiết
                        </a>
                        @endif
                        
                        <button class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="viewNotificationDetail({{ $notification->id }})" style="border-radius: 10px; font-weight: 600; transition: all 0.3s ease; border: 2px solid var(--blue-primary); color: var(--blue-primary); background: white;">
                            <i class="fas fa-eye me-1"></i>Xem thông báo
                        </button>
                        
                        @if($isUnread)
                        <button class="btn btn-outline-success btn-sm w-100" 
                                hx-post="{{ route('tenant.notifications.mark-read', $notification->id) }}?type={{ request('type', '') }}&status={{ request('status', 'all') }}&search={{ request('search', '') }}"
                                hx-target="#notifications-list-container"
                                hx-swap="innerHTML"
                                hx-indicator="#htmx-loading"
                                hx-headers='{"HX-Request": "true", "X-CSRF-TOKEN": "{{ csrf_token() }}"}'
                                style="border-radius: 10px; font-weight: 600; transition: all 0.3s ease; border: 2px solid #10b981; color: #10b981; background: white;">
                            <i class="fas fa-check me-1"></i>Đánh dấu đã đọc
                        </button>
                        @else
                        <button class="btn btn-outline-secondary btn-sm w-100" disabled style="border-radius: 10px; font-weight: 600;">
                            <i class="fas fa-check me-1"></i>Đã đọc
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@empty
    <!-- Empty State -->
    <div class="empty-state-blue">
        <div class="empty-icon-blue">
            <i class="fas fa-bell-slash"></i>
        </div>
        <h3>Không có thông báo nào</h3>
        <p>Không tìm thấy thông báo nào phù hợp với bộ lọc hiện tại.</p>
    </div>
@endforelse

<!-- Pagination -->
@if($notifications->hasPages())
    {{ $notifications->appends(request()->query())->links('vendor.pagination.custom', [
        'tableContainerId' => 'notifications-list-container',
        'htmxIndicator' => '#htmx-loading'
    ]) }}
@endif

