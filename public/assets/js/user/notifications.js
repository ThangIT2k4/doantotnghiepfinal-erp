/**
 * Tenant Notifications JavaScript
 * Handles dynamic notification loading and interactions
 */

// Global variables
let notificationBadge = null;
let notificationItems = null;
let unreadCount = 0;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    notificationBadge = document.getElementById('notificationBadge');
    notificationItems = document.getElementById('notificationItems');
    
    if (notificationBadge && notificationItems) {
        loadNotifications();
        loadUnreadCount();
        
        // Auto-refresh every 30 seconds (simple polling)
        setInterval(function() {
            loadUnreadCount();
            loadNotifications();
        }, 30000);
    }
    
    // Initialize filter functionality
    initializeFilters();
});

/**
 * Load recent notifications for header dropdown
 */
function loadNotifications() {
    if (!notificationItems) return;
    
    fetch('/tenant/notifications/recent')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotifications(data.notifications);
            } else {
                showNotificationError('Không thể tải thông báo');
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            showNotificationError('Lỗi khi tải thông báo');
        });
}

/**
 * Load unread count
 */
function loadUnreadCount() {
    if (!notificationBadge) return;
    
    fetch('/tenant/notifications/unread-count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                unreadCount = data.unread_count;
                updateNotificationBadge();
            }
        })
        .catch(error => {
            console.error('Error loading unread count:', error);
        });
}


/**
 * Display notifications in dropdown
 */
function displayNotifications(notifications) {
    if (!notificationItems) return;
    
    if (notifications.length === 0) {
        notificationItems.innerHTML = `
            <div class="text-center p-3 text-muted">
                <i class="fas fa-bell-slash fa-2x mb-2"></i>
                <div>Không có thông báo nào</div>
            </div>
        `;
        return;
    }
    
    let html = '';
    notifications.forEach(notification => {
        const isUnread = notification.status === 'queued';
        const timeAgo = getTimeAgo(notification.created_at);
        const iconClass = getNotificationIcon(notification);
        
        html += `
            <div class="notification-item ${isUnread ? 'unread' : ''}" data-id="${notification.id}">
                <div class="item-icon ${getNotificationType(notification)}">
                    <i class="${iconClass}"></i>
                </div>
                <div class="item-content">
                    <div class="item-title">${notification.subject}</div>
                    <div class="item-message">${truncateText(notification.content, 50)}</div>
                    <div class="item-time">${timeAgo}</div>
                </div>
            </div>
        `;
    });
    
    notificationItems.innerHTML = html;
}

/**
 * Update notification badge
 */
function updateNotificationBadge() {
    if (!notificationBadge) return;
    
    if (unreadCount > 0) {
        notificationBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        notificationBadge.style.display = 'inline-block';
    } else {
        notificationBadge.style.display = 'none';
    }
}

/**
 * Mark notification as read
 */
function markAsRead(notificationId) {
    fetch(`/tenant/notifications/${notificationId}/mark-read`, {
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
            const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.classList.remove('unread');
                notificationElement.classList.add('read');
            }
            
            // Update badge
            unreadCount = Math.max(0, unreadCount - 1);
            updateNotificationBadge();
            
            // Show success message
            showNotificationSuccess('Đã đánh dấu đã đọc');
        } else {
            showNotificationError(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
        showNotificationError('Lỗi khi đánh dấu đã đọc');
    });
}

/**
 * Mark notification as read and navigate to entity link
 */
function markAsReadAndNavigate(notificationId, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Get the link URL first (before async operations)
    let linkUrl = null;
    if (event && event.currentTarget) {
        // event.currentTarget is the element that has the onclick handler (the <a> tag)
        linkUrl = event.currentTarget.href;
    } else if (event && event.target) {
        // Fallback: try to find the <a> tag
        const link = event.target.closest('a');
        if (link && link.href) {
            linkUrl = link.href;
        }
    }
    
    // If no link found, try to get from the notification element
    if (!linkUrl) {
        const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
        if (notificationElement) {
            const linkElement = notificationElement.querySelector('a[href]');
            if (linkElement && linkElement.href) {
                linkUrl = linkElement.href;
            }
        }
    }
    
    // Mark as read first
    fetch(`/tenant/notifications/${notificationId}/mark-read`, {
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
            const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.classList.remove('unread');
                notificationElement.classList.add('read');
            }
            
            // Update badge
            unreadCount = Math.max(0, unreadCount - 1);
            updateNotificationBadge();
        }
        
        // Navigate to the link
        if (linkUrl) {
            window.location.href = linkUrl;
        } else {
            console.warn('No link URL found for notification:', notificationId);
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
        // Still navigate even if mark as read fails
        if (linkUrl) {
            window.location.href = linkUrl;
        }
    });
}

/**
 * Mark all notifications as read
 */
function markAllAsRead() {
    fetch('/tenant/notifications/mark-all-read', {
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
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
                item.classList.add('read');
            });
            
            // Update badge
            unreadCount = 0;
            updateNotificationBadge();
            
            // Show success message
            showNotificationSuccess('Đã đánh dấu tất cả đã đọc');
        } else {
            showNotificationError(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
        showNotificationError('Lỗi khi đánh dấu tất cả đã đọc');
    });
}

/**
 * Mark all header notifications as read
 */
function markAllHeaderAsRead() {
    markAllAsRead();
}

/**
 * Initialize filter functionality
 */
function initializeFilters() {
    // Search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const filterForm = document.getElementById('filterForm');
                if (filterForm && filterForm.tagName === 'FORM') {
                    filterForm.submit();
                }
            }, 500);
        });
    }
    
    // Type filter
    const typeFilter = document.getElementById('typeFilter');
    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            const filterForm = document.getElementById('filterForm');
            if (filterForm && filterForm.tagName === 'FORM') {
                filterForm.submit();
            }
        });
    }
}

/**
 * Filter by status
 */
function filterByStatus(status) {
    const form = document.getElementById('filterForm');
    if (!form || form.tagName !== 'FORM') {
        return;
    }
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = status;
    form.appendChild(statusInput);
    form.submit();
}

/**
 * Get notification type from subject/content
 */
function getNotificationType(notification) {
    const subject = notification.subject.toLowerCase();
    const content = notification.content.toLowerCase();
    
    if (subject.includes('thanh toán') || content.includes('hóa đơn')) {
        return 'payment';
    } else if (subject.includes('hợp đồng') || content.includes('hợp đồng')) {
        return 'contract';
    } else if (subject.includes('lịch hẹn') || content.includes('lịch hẹn')) {
        return 'appointment';
    } else if (subject.includes('đánh giá') || content.includes('đánh giá')) {
        return 'review';
    } else if (subject.includes('sửa chữa') || content.includes('sửa chữa')) {
        return 'maintenance';
    }
    
    return 'system';
}

/**
 * Get notification icon based on type
 */
function getNotificationIcon(notification) {
    const type = getNotificationType(notification);
    
    switch (type) {
        case 'payment':
            return 'fas fa-credit-card';
        case 'contract':
            return 'fas fa-file-contract';
        case 'appointment':
            return 'fas fa-calendar';
        case 'review':
            return 'fas fa-star';
        case 'maintenance':
            return 'fas fa-tools';
        default:
            return 'fas fa-bell';
    }
}

/**
 * Get time ago string
 */
function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Vừa xong';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} phút trước`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} giờ trước`;
    } else {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} ngày trước`;
    }
}

/**
 * Truncate text
 */
function truncateText(text, maxLength) {
    if (text.length <= maxLength) {
        return text;
    }
    return text.substring(0, maxLength) + '...';
}

/**
 * Show notification error in UI
 */
function showNotificationError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger alert-dismissible fade show';
    errorDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.notifications-container .container') || document.querySelector('.page-container-blue .container');
    if (container) {
        container.insertBefore(errorDiv, container.firstChild);
    } else {
        console.error('Notification Error:', message);
    }
}

/**
 * Show notification success in UI
 */
function showNotificationSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success alert-dismissible fade show';
    successDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.notifications-container .container') || document.querySelector('.page-container-blue .container');
    if (container) {
        container.insertBefore(successDiv, container.firstChild);
    } else {
        console.log('Notification Success:', message);
    }
}

/**
 * View notification detail
 */
function viewNotificationDetail(id) {
    const modal = new bootstrap.Modal(document.getElementById('notificationDetailModal'));
    modal.show();
    
    // Store current notification ID for other functions
    window.currentNotificationId = id;
    
    // Load notification details
    fetch(`/tenant/notifications/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notification = data.notification;
                const type = getNotificationType(notification);
                const icon = getNotificationIcon(notification);
                
                let actionButtons = '';
                
                // Ưu tiên sử dụng entity_link từ notification nếu có
                if (notification.entity_link) {
                    actionButtons = `<a href="${notification.entity_link}" 
                                       class="btn btn-primary btn-sm me-2"
                                       onclick="markAsReadAndNavigate(${notification.id}, event)">
                        <i class="fas fa-external-link-alt me-1"></i>Xem chi tiết
                    </a>`;
                } else {
                    // Fallback: sử dụng type-based links
                    if (type === 'payment') {
                        actionButtons = `<a href="/tenant/invoices" class="btn btn-danger btn-sm">
                            <i class="fas fa-credit-card me-1"></i>Thanh toán ngay
                        </a>`;
                    } else if (type === 'contract') {
                        actionButtons = `<a href="/tenant/contracts" class="btn btn-warning btn-sm">
                            <i class="fas fa-refresh me-1"></i>Gia hạn ngay
                        </a>`;
                    } else if (type === 'appointment') {
                        actionButtons = `<a href="/tenant/appointments" class="btn btn-success btn-sm">
                            <i class="fas fa-calendar me-1"></i>Xem lịch hẹn
                        </a>`;
                    } else if (type === 'review') {
                        actionButtons = `<a href="/tenant/reviews" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>Xem phản hồi
                        </a>`;
                    } else if (type === 'maintenance') {
                        actionButtons = `<a href="/tenant/maintenance" class="btn btn-info btn-sm">
                            <i class="fas fa-tools me-1"></i>Theo dõi
                        </a>`;
                    }
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
 * Mark current notification as read
 */
function markCurrentNotificationAsRead() {
    if (window.currentNotificationId) {
        markAsRead(window.currentNotificationId);
        const modal = bootstrap.Modal.getInstance(document.getElementById('notificationDetailModal'));
        modal.hide();
    }
}

/**
 * Reply to notification
 */
function replyToNotification() {
    // This could open a reply modal or redirect to a contact form
    alert('Tính năng phản hồi sẽ được phát triển trong phiên bản tiếp theo.');
}

/**
 * Open notification settings modal
 */
function openNotificationSettings() {
    const modal = new bootstrap.Modal(document.getElementById('settingsModal'));
    modal.show();
    
    // Load settings
    loadNotificationSettings();
}

/**
 * Load notification settings
 */
function loadNotificationSettings() {
    const modalBody = document.getElementById('settingsModalBody');
    
    fetch('/tenant/notifications/settings/preferences')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSettingsForm(data.preferences);
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Không thể tải cài đặt thông báo.
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading notification settings:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Lỗi khi tải cài đặt thông báo.
                </div>
            `;
        });
}

/**
 * Render settings form
 */
function renderSettingsForm(preferences) {
    const modalBody = document.getElementById('settingsModalBody');
    
    let html = `
        <div class="settings-container">
            <p class="text-muted mb-4">
                <i class="fas fa-info-circle me-2"></i>
                Chọn loại thông báo bạn muốn nhận qua email và trong ứng dụng.
            </p>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <tr>
                            <th style="width: 40%;">Loại thông báo</th>
                            <th class="text-center" style="width: 30%;">
                                <i class="fas fa-envelope me-1"></i>Email
                            </th>
                            <th class="text-center" style="width: 30%;">
                                <i class="fas fa-bell me-1"></i>Trong ứng dụng
                            </th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    preferences.forEach(pref => {
        const emailChecked = pref.email_enabled ? 'checked' : '';
        const inAppChecked = pref.in_app_enabled ? 'checked' : '';
        
        html += `
            <tr>
                <td>
                    <strong>${pref.label}</strong>
                </td>
                <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" 
                               id="email_${pref.entity_type}" 
                               data-entity="${pref.entity_type}"
                               data-type="email"
                               ${emailChecked}>
                    </div>
                </td>
                <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" 
                               id="inapp_${pref.entity_type}" 
                               data-entity="${pref.entity_type}"
                               data-type="inapp"
                               ${inAppChecked}>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    modalBody.innerHTML = html;
}

/**
 * Save notification settings
 */
function saveNotificationSettings() {
    const saveBtn = document.getElementById('saveSettingsBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';
    
    // Collect all preferences
    const preferences = [];
    const checkboxes = document.querySelectorAll('#settingsModalBody input[type="checkbox"]');
    
    const entityTypes = new Set();
    checkboxes.forEach(checkbox => {
        entityTypes.add(checkbox.getAttribute('data-entity'));
    });
    
    entityTypes.forEach(entityType => {
        const emailCheckbox = document.getElementById(`email_${entityType}`);
        const inAppCheckbox = document.getElementById(`inapp_${entityType}`);
        
        preferences.push({
            entity_type: entityType,
            email_enabled: emailCheckbox ? emailCheckbox.checked : true,
            in_app_enabled: inAppCheckbox ? inAppCheckbox.checked : true,
        });
    });
    
    fetch('/tenant/notifications/settings/preferences', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ preferences: preferences })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotificationSuccess('Cài đặt đã được lưu thành công');
            const modal = bootstrap.Modal.getInstance(document.getElementById('settingsModal'));
            modal.hide();
        } else {
            showNotificationError(data.message || 'Có lỗi xảy ra khi lưu cài đặt');
        }
    })
    .catch(error => {
        console.error('Error saving notification settings:', error);
        showNotificationError('Lỗi khi lưu cài đặt');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

