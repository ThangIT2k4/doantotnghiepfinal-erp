/**
 * Manager Header Notifications JavaScript
 */

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Manager header notifications initialized');
    
    // Load initial notification count
    updateNotificationCount();
    
    // Update notification count every 30 seconds
    setInterval(updateNotificationCount, 30000);
});

/**
 * Update notification count in header
 */
function updateNotificationCount() {
    // Check if we're on a staff page (staffNotificationBadge exists) - if so, skip this function
    // as the header_erp.blade.php script will handle it
    if (document.getElementById('staffNotificationBadge')) {
        return;
    }
    
    fetch('/staff/notifications/unread-count', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            return response.json();
        })
        .then(data => {
            const badge = document.getElementById('managerNotificationBadge');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(error => {
            // Only log errors that aren't network-related to avoid console spam
            if (error.name !== 'TypeError' || !error.message.includes('Failed to fetch')) {
                console.error('Error updating notification count:', error);
            }
        });
}

/**
 * Mark all notifications as read (from header)
 */
function markAllHeaderAsRead() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error('CSRF token not found');
        return;
    }
    
    fetch('/staff/notifications/mark-all-read', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update notification count
            updateNotificationCount();
            
            if (typeof Notify !== 'undefined') {
                Notify.success('Đã đánh dấu tất cả thông báo là đã đọc', 'Thành công');
            }
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi');
            }
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
        if (typeof Notify !== 'undefined') {
            Notify.error('Có lỗi xảy ra khi cập nhật thông báo', 'Lỗi');
        }
    });
}
