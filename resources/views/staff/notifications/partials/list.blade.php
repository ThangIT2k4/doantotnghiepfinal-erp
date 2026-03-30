@php
    $notifications = $notifications ?? collect();
@endphp

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>Danh sách thông báo
            @if(isset($unreadCount) && $unreadCount > 0)
                <span class="badge bg-warning ms-2">{{ $unreadCount }} chưa đọc</span>
            @endif
        </h6>
    </div>
    <div class="card-body">
        @forelse($notifications as $notification)
            <div class="notification-card mb-3 {{ $notification->status === 'queued' ? 'unread' : '' }}" 
                 data-notification-id="{{ $notification->id }}">
                <div class="d-flex align-items-start">
                    <div class="notification-icon me-3">
                        <i class="{{ $notification->icon }} {{ $notification->status === 'queued' ? 'text-warning' : 'text-muted' }}"></i>
                    </div>
                    <div class="notification-content flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="notification-title mb-0 {{ $notification->status === 'queued' ? 'font-weight-bold' : '' }}">
                                {{ $notification->subject }}
                            </h6>
                            <div class="notification-actions">
                                @if($notification->status === 'queued')
                                    <button class="btn btn-sm btn-outline-success me-1" 
                                            onclick="markAsRead({{ $notification->id }})"
                                            title="Đánh dấu đã đọc">
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endif
                                @if(isset($notification->entity_link) && $notification->entity_link)
                                    <a href="{{ $notification->entity_link }}" 
                                       class="btn btn-sm btn-outline-primary me-1"
                                       onclick="markAsReadAndNavigate({{ $notification->id }}, event)"
                                       title="Xem chi tiết">
                                        <i class="fas fa-external-link-alt"></i> Xem chi tiết
                                    </a>
                                @endif
                                <button class="btn btn-sm btn-outline-info me-1" 
                                        onclick="viewNotificationDetail({{ $notification->id }})"
                                        title="Xem thông báo">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteNotification({{ $notification->id }})"
                                        title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="notification-message">
                            {!! nl2br(e($notification->content)) !!}
                        </div>
                        <div class="notification-meta">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                {{ $notification->created_at->format('d/m/Y H:i') }}
                                <span class="ms-3">
                                    <i class="fas fa-tag me-1"></i>
                                    {{ ucfirst($notification->type) }}
                                </span>
                                @if($notification->status === 'queued')
                                    <span class="ms-3 text-warning">
                                        <i class="fas fa-circle me-1"></i>Chưa đọc
                                    </span>
                                @else
                                    <span class="ms-3 text-success">
                                        <i class="fas fa-check-circle me-1"></i>Đã đọc
                                    </span>
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Không có thông báo nào</h5>
                <p class="text-muted">Bạn chưa có thông báo nào phù hợp với bộ lọc hiện tại.</p>
            </div>
        @endforelse

        <!-- Pagination -->
        @if($notifications->hasPages())
            {{ $notifications->appends(request()->query())->links('vendor.pagination.custom', [
                'tableContainerId' => 'notifications-table-container',
                'htmxIndicator' => '#htmx-loading-index-filters-form'
            ]) }}
        @endif
    </div>
</div>

