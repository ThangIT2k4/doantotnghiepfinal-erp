/**
 * Manager Notifications JavaScript
 */

// Global variables
let currentNotificationId = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Manager notifications initialized');
});

/**
 * Mark notification as read
 */
function markAsRead(notificationId) {
    fetch(`/manager/notifications/${notificationId}/mark-read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            const notificationCard = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationCard) {
                notificationCard.classList.remove('unread');
                
                // Update status badge
                const statusBadge = notificationCard.querySelector('.notification-meta .text-warning');
                if (statusBadge) {
                    statusBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i>Đã đọc';
                    statusBadge.className = 'ms-3 text-success';
                }
                
                // Remove mark as read button
                const markReadBtn = notificationCard.querySelector('button[onclick*="markAsRead"]');
                if (markReadBtn) {
                    markReadBtn.remove();
                }
                
                // Update icon
                const icon = notificationCard.querySelector('.notification-icon i');
                if (icon) {
                    icon.classList.remove('text-warning');
                    icon.classList.add('text-muted');
                }
            }
            
            // Show success message
            if (typeof Notify !== 'undefined') {
                Notify.success('Đã đánh dấu thông báo là đã đọc', 'Thành công');
            }
            
            // Update header notification count
            updateHeaderNotificationCount();
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi');
            }
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
        if (typeof Notify !== 'undefined') {
            Notify.error('Có lỗi xảy ra khi cập nhật thông báo', 'Lỗi');
        }
    });
}

/**
 * Mark all notifications as read
 */
function markAllAsRead() {
    // Show loading state on button instead of toast
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...';
    button.disabled = true;
    
    fetch('/manager/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message briefly before reload
            if (typeof Notify !== 'undefined') {
                Notify.success('Đã đánh dấu tất cả thông báo là đã đọc', 'Thành công');
            }
            // Reload page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            // Reset button on error
            button.innerHTML = originalText;
            button.disabled = false;
            
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi');
            }
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
        
        // Reset button on error
        button.innerHTML = originalText;
        button.disabled = false;
        
        if (typeof Notify !== 'undefined') {
            Notify.error('Có lỗi xảy ra khi cập nhật thông báo', 'Lỗi');
        }
    });
}

/**
 * View notification detail
 */
function viewNotificationDetail(notificationId) {
    const modal = new bootstrap.Modal(document.getElementById('notificationDetailModal'));
    modal.show();
    
    // Store current notification ID for other functions
    currentNotificationId = notificationId;
    
    // Load notification details
    fetch(`/manager/notifications/${notificationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notification = data.notification;
                const type = getNotificationType(notification);
                const icon = getNotificationIcon(notification);
                
                let actionButtons = '';
                if (type === 'contract') {
                    actionButtons = `<a href="/manager/leases" class="btn btn-warning btn-sm">
                        <i class="fas fa-file-contract me-1"></i>Xem hợp đồng
                    </a>`;
                } else if (type === 'payment') {
                    actionButtons = `<a href="/manager/payments" class="btn btn-danger btn-sm">
                        <i class="fas fa-credit-card me-1"></i>Xem thanh toán
                    </a>`;
                } else if (type === 'expiry') {
                    actionButtons = `<a href="/manager/leases" class="btn btn-warning btn-sm">
                        <i class="fas fa-exclamation-triangle me-1"></i>Xem hợp đồng sắp hết hạn
                    </a>`;
                } else if (type === 'report') {
                    actionButtons = `<a href="/manager/reports" class="btn btn-info btn-sm">
                        <i class="fas fa-chart-line me-1"></i>Xem báo cáo
                    </a>`;
                } else if (type === 'staff') {
                    actionButtons = `<a href="/manager/staff" class="btn btn-primary btn-sm">
                        <i class="fas fa-users me-1"></i>Xem nhân viên
                    </a>`;
                }
                
                const isUnread = notification.status === 'queued';
                const isImportant = notification.subject.includes('quá hạn') || 
                                  notification.subject.includes('khẩn cấp') || 
                                  notification.subject.includes('hết hạn');
                
                const detailHtml = `
                    <div class="notification-detail">
                        <div class="detail-header">
                            <div class="detail-icon ${type}">
                                <i class="${icon}"></i>
                            </div>
                            <div class="detail-info">
                                <h4 class="detail-title">${notification.subject}</h4>
                                <div class="detail-meta">
                                    <span class="meta-item">
                                        <i class="fas fa-tag me-1"></i>
                                        ${type.charAt(0).toUpperCase() + type.slice(1)}
                                    </span>
                                    <span class="meta-item">
                                        <i class="fas fa-calendar me-1"></i>
                                        ${new Date(notification.created_at).toLocaleDateString('vi-VN')} ${new Date(notification.created_at).toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'})}
                                    </span>
                                    <span class="meta-item ${isUnread ? 'unread' : 'read'}">
                                        <i class="fas fa-${isUnread ? 'circle' : 'check-circle'} me-1"></i>
                                        ${isUnread ? 'Chưa đọc' : 'Đã đọc'}
                                    </span>
                                    ${isImportant ? '<span class="meta-item important"><i class="fas fa-exclamation-triangle me-1"></i>Quan trọng</span>' : ''}
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-content">
                            <h6>Nội dung thông báo:</h6>
                            <div class="content-text">
                                ${notification.content.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                        
                        <div class="detail-actions">
                            ${actionButtons}
                        </div>
                    </div>
                `;
                
                document.getElementById('notificationDetailContent').innerHTML = detailHtml;
                
                // Update modal buttons based on notification status
                const markReadBtn = document.getElementById('markReadBtn');
                if (isUnread) {
                    markReadBtn.style.display = 'inline-block';
                    markReadBtn.classList.remove('btn-secondary');
                    markReadBtn.classList.add('btn-success');
                } else {
                    markReadBtn.style.display = 'none';
                }
                
            } else {
                document.getElementById('notificationDetailContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Không thể tải thông tin thông báo.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading notification detail:', error);
            document.getElementById('notificationDetailContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Lỗi khi tải thông tin thông báo.
                </div>
            `;
        });
}

/**
 * Mark current notification as read (from modal)
 */
function markCurrentNotificationAsRead() {
    if (currentNotificationId) {
        markAsRead(currentNotificationId);
        
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('notificationDetailModal'));
        if (modal) {
            modal.hide();
        }
    }
}

/**
 * Delete notification
 */
function deleteNotification(notificationId) {
    if (!confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
        return;
    }
    
    fetch(`/manager/notifications/${notificationId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove notification card from UI
            const notificationCard = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationCard) {
                notificationCard.remove();
            }
            
            if (typeof Notify !== 'undefined') {
                Notify.success('Đã xóa thông báo', 'Thành công');
            }
            
            // Update header notification count
            updateHeaderNotificationCount();
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi');
            }
        }
    })
    .catch(error => {
        console.error('Error deleting notification:', error);
        if (typeof Notify !== 'undefined') {
            Notify.error('Có lỗi xảy ra khi xóa thông báo', 'Lỗi');
        }
    });
}

/**
 * Get notification type based on content
 */
function getNotificationType(notification) {
    const subject = notification.subject.toLowerCase();
    const content = notification.content.toLowerCase();

    if (subject.includes('hợp đồng') || subject.includes('contract')) {
        return 'contract';
    } else if (subject.includes('thanh toán') || subject.includes('payment')) {
        return 'payment';
    } else if (subject.includes('hết hạn') || subject.includes('expir')) {
        return 'expiry';
    } else if (subject.includes('báo cáo') || subject.includes('report')) {
        return 'report';
    } else if (subject.includes('nhân viên') || subject.includes('staff')) {
        return 'staff';
    } else {
        return 'general';
    }
}

/**
 * Get notification icon based on type
 */
function getNotificationIcon(notification) {
    const type = getNotificationType(notification);

    switch (type) {
        case 'contract':
            return 'fas fa-file-contract';
        case 'payment':
            return 'fas fa-credit-card';
        case 'expiry':
            return 'fas fa-exclamation-triangle';
        case 'report':
            return 'fas fa-chart-line';
        case 'staff':
            return 'fas fa-users';
        default:
            return 'fas fa-bell';
    }
}

/**
 * Update header notification count
 */
function updateHeaderNotificationCount() {
    fetch('/manager/notifications/unread-count')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('managerNotificationBadge');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error updating header notification count:', error);
        });
}
