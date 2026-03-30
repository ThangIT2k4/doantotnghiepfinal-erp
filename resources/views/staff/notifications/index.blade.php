@extends('layouts.staff_dashboard')

@section('title', 'Thông báo')

@section('content')
<main class="main-content">
<div class="container-fluid">
    {{-- 1. Page Header --}}
    @include('staff.components.index-page-header', [
        'title' => 'Thông báo',
        'subtitle' => 'Quản lý và theo dõi thông báo hệ thống',
        'icon' => 'fas fa-bell',
        'actions' => [
            [
                'variant' => 'primary',
                'label' => 'Đánh dấu tất cả đã đọc',
                'icon' => 'fas fa-check-double',
                'onclick' => 'markAllAsRead()'
            ],
            [
                'variant' => 'secondary',
                'label' => 'Quay lại',
                'icon' => 'fas fa-arrow-left',
                'url' => route('staff.dashboard')
            ]
        ]
    ])

    {{-- 2. Statistics Cards --}}
    @php
        $statsFormatted = [
            'total' => [
                'value' => $stats['total'] ?? 0,
                'label' => 'Tổng thông báo',
                'icon' => 'fa-bell',
                'color' => 'primary',
                'filter' => '',
            ],
            'unread' => [
                'value' => $stats['unread'] ?? 0,
                'label' => 'Chưa đọc',
                'icon' => 'fa-envelope',
                'color' => 'warning',
                'filter' => 'unread',
            ],
            'read' => [
                'value' => $stats['read'] ?? 0,
                'label' => 'Đã đọc',
                'icon' => 'fa-check-circle',
                'color' => 'success',
                'filter' => 'read',
            ],
            'important' => [
                'value' => $stats['important'] ?? 0,
                'label' => 'Quan trọng',
                'icon' => 'fa-exclamation-triangle',
                'color' => 'danger',
                'filter' => 'important',
            ],
        ];
    @endphp
    <div id="stats-container">
        @include('staff.components.statistics-cards', [
            'stats' => $statsFormatted,
            'currentFilter' => request('status', ''),
            'filterKey' => 'status',
            'onFilterClick' => 'htmx-filter',
            'onClearClick' => 'htmx-clear',
            'tableContainerId' => 'notifications-table-container',
            'action' => route('staff.notifications.index'),
            'columns' => 4
        ])
    </div>

    {{-- 3. Filters với HTMX --}}
    @php
        $filterFields = [
            [
                'name' => 'search',
                'label' => 'Tìm kiếm',
                'type' => 'text',
                'col' => 'col-md-4',
                'placeholder' => 'Tìm kiếm thông báo...',
                'value' => request('search'),
            ],
            [
                'name' => 'type',
                'label' => 'Loại thông báo',
                'type' => 'select',
                'col' => 'col-md-3',
                'empty_option' => 'Tất cả loại',
                'options' => [
                    'contract' => 'Hợp đồng',
                    'payment' => 'Thanh toán',
                    'expiry' => 'Hết hạn',
                    'report' => 'Báo cáo',
                    'staff' => 'Nhân viên',
                ],
                'value' => request('type'),
            ],
            [
                'name' => 'status',
                'label' => 'Trạng thái',
                'type' => 'select',
                'col' => 'col-md-3',
                'empty_option' => 'Tất cả',
                'options' => [
                    'unread' => 'Chưa đọc',
                    'read' => 'Đã đọc',
                    'important' => 'Quan trọng',
                ],
                'value' => request('status', 'all'),
            ],
        ];
    @endphp
    @include('staff.components.index-filters-htmx', [
        'action' => route('staff.notifications.index'),
        'tableContainerId' => 'notifications-table-container',
        'statsContainerId' => 'stats-container',
        'fields' => $filterFields,
        'showReset' => true,
        'resetUrl' => route('staff.notifications.index')
    ])

    {{-- 4. Notifications List --}}
    <div id="notifications-table-container">
        @include('staff.notifications.partials.list', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount ?? 0
        ])
    </div>
</div>
</main>

<!-- Notification Detail Modal -->
<div class="modal fade" id="notificationDetailModal" tabindex="-1" aria-labelledby="notificationDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationDetailModalLabel">Chi tiết thông báo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="notificationDetailContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-success" id="markReadBtn" onclick="markCurrentNotificationAsRead()" style="display: none;">
                    <i class="fas fa-check me-1"></i>Đánh dấu đã đọc
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.notification-card {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
    transition: all 0.3s ease;
    background: #fff;
}

.notification-card:hover {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transform: translateY(-1px);
}

.notification-card.unread {
    border-left: 4px solid #f6c23e;
    background: #fffbf0;
}

.notification-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f8f9fc;
}

.notification-title {
    color: #5a5c69;
    font-size: 1rem;
    line-height: 1.4;
}

.notification-message {
    color: #4b5563;
    line-height: 1.5;
    margin-bottom: 12px;
    font-size: 0.95rem;
}

.notification-meta {
    border-top: 1px solid #e3e6f0;
    padding-top: 8px;
}

.notification-actions {
    display: flex;
    gap: 4px;
}

.notification-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .notification-actions {
        flex-direction: column;
        gap: 2px;
    }
    
    .notification-card {
        padding: 0.75rem;
    }
}

/* Modal styles */
.modal-content {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.modal-header {
    background: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

/* Detail content styles */
.notification-detail {
    padding: 1rem 0;
}

.detail-header {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e3e6f0;
}

.detail-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f8f9fc;
    margin-right: 1rem;
}

.detail-icon.contract { background: #e3f2fd; color: #1976d2; }
.detail-icon.payment { background: #e8f5e8; color: #2e7d32; }
.detail-icon.expiry { background: #fff3e0; color: #f57c00; }
.detail-icon.report { background: #f3e5f5; color: #7b1fa2; }
.detail-icon.staff { background: #e0f2f1; color: #00695c; }

.detail-info {
    flex: 1;
}

.detail-title {
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
}

.detail-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
    color: #6c757d;
}

.meta-item.unread {
    color: #f6c23e;
    font-weight: 600;
}

.meta-item.important {
    color: #e74a3b;
    font-weight: 600;
}

.detail-content {
    margin-bottom: 1.5rem;
}

.detail-content h6 {
    color: #495057;
    margin-bottom: 0.75rem;
    font-weight: 600;
}

.content-text {
    background: #f8f9fc;
    padding: 1rem;
    border-radius: 0.35rem;
    border-left: 4px solid #4e73df;
    line-height: 1.6;
    color: #495057;
}

.detail-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.detail-actions .btn {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}
</style>
@endpush

@push('scripts')
<script>
function markAsRead(notificationId) {
    fetch(`/staff/notifications/${notificationId}/mark-read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload using HTMX to update both stats and list
            htmx.trigger('#index-filters-form', 'submit');
        } else {
            alert('Có lỗi xảy ra: ' + (data.message || 'Lỗi không xác định'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi cập nhật thông báo');
    });
}

function markAllAsRead() {
    fetch('/staff/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload using HTMX to update both stats and list
            htmx.trigger('#index-filters-form', 'submit');
        } else {
            alert('Có lỗi xảy ra: ' + (data.message || 'Lỗi không xác định'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi cập nhật thông báo');
    });
}

function viewNotificationDetail(notificationId) {
    fetch(`/staff/notifications/${notificationId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = new bootstrap.Modal(document.getElementById('notificationDetailModal'));
            const content = document.getElementById('notificationDetailContent');
            const notification = data.notification;
            
            content.innerHTML = `
                <div class="notification-detail">
                    <div class="detail-header">
                        <div class="detail-icon ${notification.type}">
                            <i class="${notification.icon}"></i>
                        </div>
                        <div class="detail-info">
                            <h5 class="detail-title">${notification.subject}</h5>
                            <div class="detail-meta">
                                <span class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    ${new Date(notification.created_at).toLocaleString('vi-VN')}
                                </span>
                                <span class="meta-item ${notification.status === 'queued' ? 'unread' : ''}">
                                    <i class="fas fa-${notification.status === 'queued' ? 'circle' : 'check-circle'}"></i>
                                    ${notification.status === 'queued' ? 'Chưa đọc' : 'Đã đọc'}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="detail-content">
                        <h6>Nội dung</h6>
                        <div class="content-text">${notification.content.replace(/\n/g, '<br>')}</div>
                    </div>
                    ${notification.entity_link ? `
                    <div class="detail-actions">
                        <a href="${notification.entity_link}" class="btn btn-primary" onclick="markAsReadAndNavigate(${notificationId}, event)">
                            <i class="fas fa-external-link-alt me-1"></i>Xem chi tiết
                        </a>
                    </div>
                    ` : ''}
                </div>
            `;
            
            if (notification.status === 'queued') {
                document.getElementById('markReadBtn').style.display = 'block';
                document.getElementById('markReadBtn').setAttribute('onclick', `markAsRead(${notificationId})`);
            } else {
                document.getElementById('markReadBtn').style.display = 'none';
            }
            
            modal.show();
        } else {
            alert('Có lỗi xảy ra: ' + (data.message || 'Lỗi không xác định'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi tải chi tiết thông báo');
    });
}

function deleteNotification(notificationId) {
    if (!confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
        return;
    }
    
    fetch(`/staff/notifications/${notificationId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload using HTMX to update both stats and list
            htmx.trigger('#index-filters-form', 'submit');
        } else {
            alert('Có lỗi xảy ra: ' + (data.message || 'Lỗi không xác định'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xóa thông báo');
    });
}

function markCurrentNotificationAsRead() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('notificationDetailModal'));
    const notificationId = document.querySelector('[data-notification-id]')?.getAttribute('data-notification-id');
    if (notificationId) {
        markAsRead(notificationId);
        modal.hide();
    }
}

function markAsReadAndNavigate(notificationId, event) {
    // Mark as read in background (don't wait for response)
    fetch(`/staff/notifications/${notificationId}/mark-read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    }).catch(error => {
        console.error('Error marking notification as read:', error);
    });
    
    // Allow navigation to proceed
    // Don't prevent default, let the link work normally
}
</script>
@endpush
