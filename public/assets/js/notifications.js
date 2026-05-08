/**
 * Notification System for QLPhongTro
 * Unified popup confirmations and toast notifications
 * 
 * @author QLPhongTro Team
 * @version 1.0.0
 */

// Check if NotificationSystem is already defined
if (typeof NotificationSystem === 'undefined') {
    class NotificationSystem {
    constructor() {
        this.init();
    }

    init() {
        // Create notification container if not exists
        this.createNotificationContainer();
        
        // Create confirmation modal if not exists
        this.createConfirmationModal();
        
        // Auto-dismiss toasts after 5 seconds
        this.autoDismissToasts();
    }

    /**
     * Create notification container for toasts
     */
    createNotificationContainer() {
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }
    }

    /**
     * Create confirmation modal
     */
    createConfirmationModal() {
        if (!document.getElementById('confirmation-modal')) {
            const modal = document.createElement('div');
            modal.id = 'confirmation-modal';
            modal.className = 'modal fade';
            modal.setAttribute('tabindex', '-1');
            modal.setAttribute('aria-labelledby', 'confirmation-modal-label');
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmation-title">
                                <i class="fas fa-question-circle text-warning"></i>
                                Xác nhận hành động
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="confirmation-content">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i id="confirmation-icon" class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                                    </div>
                                    <div>
                                        <p id="confirmation-message" class="mb-0">Bạn có chắc chắn muốn thực hiện hành động này?</p>
                                        <small id="confirmation-details" class="text-muted"></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Hủy
                            </button>
                            <button type="button" class="btn btn-danger" id="confirmation-confirm">
                                <i class="fas fa-check"></i> Xác nhận
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
    }

    /**
     * Show confirmation popup
     * @param {Object} options - Configuration options
     * @param {string} options.title - Modal title
     * @param {string} options.message - Main message
     * @param {string} options.details - Additional details (optional)
     * @param {string} options.type - Type: 'danger', 'warning', 'info', 'success'
     * @param {string} options.confirmText - Confirm button text
     * @param {string} options.cancelText - Cancel button text
     * @param {Function} options.onConfirm - Callback when confirmed
     * @param {Function} options.onCancel - Callback when cancelled
     *
     * Hỗ trợ: confirm({ ... }) | confirm(title, message, onConfirm) | confirm(message, onConfirm)
     */
    confirm(arg1, arg2, arg3) {
        let options = {};
        if (arg1 !== null && typeof arg1 === 'object' && !Array.isArray(arg1)) {
            options = arg1;
        } else if (typeof arg1 === 'string' && typeof arg2 === 'string' && typeof arg3 === 'function') {
            options = { title: arg1, message: arg2, onConfirm: arg3 };
        } else if (typeof arg1 === 'string' && typeof arg2 === 'function') {
            options = { message: arg1, onConfirm: arg2 };
        }

        const {
            title = 'Xác nhận hành động',
            message = 'Bạn có chắc chắn muốn thực hiện hành động này?',
            details = '',
            type = 'warning',
            confirmText = 'Xác nhận',
            cancelText = 'Hủy',
            onConfirm = () => {},
            onCancel = () => {}
        } = options;

        // Set modal content
        document.getElementById('confirmation-title').innerHTML = `
            <i class="fas fa-question-circle text-${this.getTypeColor(type)}"></i>
            ${title}
        `;
        
        // Check if message contains HTML (has tags)
        const hasHtml = /<[a-z][\s\S]*>/i.test(message);
        const confirmationContent = document.getElementById('confirmation-content');
        
        if (hasHtml) {
            // If message contains HTML, replace the entire content area
            confirmationContent.innerHTML = message;
        } else {
            // Use default layout for plain text
            const messageEl = document.getElementById('confirmation-message');
            const detailsEl = document.getElementById('confirmation-details');
            if (messageEl) messageEl.textContent = message;
            if (detailsEl) detailsEl.textContent = details;
            
            // Set icon based on type
            const icon = document.getElementById('confirmation-icon');
            if (icon) {
                icon.className = `fas ${this.getTypeIcon(type)} text-${this.getTypeColor(type)} fa-2x`;
            }
        }

        // Set button styles
        const confirmBtn = document.getElementById('confirmation-confirm');
        const cancelBtn = document.querySelector('#confirmation-modal .btn-secondary');
        
        confirmBtn.className = `btn btn-${this.getTypeColor(type)}`;
        confirmBtn.innerHTML = `<i class="fas fa-check"></i> ${confirmText}`;
        cancelBtn.innerHTML = `<i class="fas fa-times"></i> ${cancelText}`;

        // Remove existing event listeners
        const newConfirmBtn = confirmBtn.cloneNode(true);
        const newCancelBtn = cancelBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);

        // Add new event listeners
        newConfirmBtn.addEventListener('click', () => {
            const modalElement = document.getElementById('confirmation-modal');
            const inst = modalElement && bootstrap.Modal.getInstance(modalElement);

            const activeElement = document.activeElement;
            if (activeElement && activeElement.blur) {
                activeElement.blur();
            }

            if (inst) {
                inst.hide();
            }
            onConfirm();
        });

        newCancelBtn.addEventListener('click', () => {
            onCancel();
        });

        const modalElement = document.getElementById('confirmation-modal');
        if (!modalElement || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return;
        }

        modalElement.removeAttribute('aria-hidden');

        function readScrollSnapshot() {
            const main = document.querySelector('.superadmin-main');
            return {
                winY: window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0,
                mainTop: main ? main.scrollTop : 0
            };
        }

        function applyScrollSnapshot(snap) {
            if (!snap) return;
            const y = snap.winY || 0;
            try {
                window.scrollTo({ top: y, left: 0, behavior: 'instant' });
            } catch (e) {
                window.scrollTo(0, y);
            }
            const main = document.querySelector('.superadmin-main');
            if (main && typeof snap.mainTop === 'number') {
                main.scrollTop = snap.mainTop;
            }
        }

        if (!modalElement.dataset.confirmModalBound) {
            modalElement.dataset.confirmModalBound = '1';
            modalElement.addEventListener('show.bs.modal', function() {
                modalElement.removeAttribute('aria-hidden');
                /* Lưu cả window + .superadmin-main (layout có thể cuộn trong main) */
                try {
                    modalElement.dataset._scrollSnap = JSON.stringify(readScrollSnapshot());
                } catch (e) {
                    modalElement.dataset._scrollSnap = '';
                }
            });
            modalElement.addEventListener('shown.bs.modal', function() {
                modalElement.removeAttribute('aria-hidden');
                let snap = null;
                try {
                    snap = modalElement.dataset._scrollSnap ? JSON.parse(modalElement.dataset._scrollSnap) : null;
                } catch (e) {
                    snap = null;
                }
                requestAnimationFrame(function() {
                    applyScrollSnapshot(snap || readScrollSnapshot());
                });
                const cb = document.getElementById('confirmation-confirm');
                if (cb && typeof cb.focus === 'function') {
                    try {
                        cb.focus({ preventScroll: true });
                    } catch (e) {
                        cb.focus();
                    }
                }
            });
            modalElement.addEventListener('hide.bs.modal', function() {
                const activeElement = document.activeElement;
                if (activeElement && activeElement.blur) {
                    activeElement.blur();
                }
            });
            modalElement.addEventListener('hidden.bs.modal', function() {
                modalElement.setAttribute('aria-hidden', 'true');
                let snap = null;
                try {
                    snap = modalElement.dataset._scrollSnap ? JSON.parse(modalElement.dataset._scrollSnap) : null;
                } catch (e) {
                    snap = null;
                }
                requestAnimationFrame(function() {
                    applyScrollSnapshot(snap || readScrollSnapshot());
                });
            });
        }

        /* Instance cũ (focus: true) gây scroll theo focus — một lần / trang: dispose rồi tạo lại */
        let bsModal = bootstrap.Modal.getInstance(modalElement);
        if (!modalElement.dataset.bsModalNoAutofocus) {
            if (bsModal) {
                bsModal.dispose();
            }
            bsModal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: false
            });
            modalElement.dataset.bsModalNoAutofocus = '1';
        } else if (!bsModal) {
            bsModal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: false
            });
        }

        bsModal.show();
    }

    /**
     * Show toast notification
     * @param {Object} options - Configuration options
     * @param {string} options.title - Toast title
     * @param {string} options.message - Toast message
     * @param {string} options.type - Type: 'success', 'error', 'warning', 'info'
     * @param {number} options.duration - Auto-dismiss duration in ms (0 = no auto-dismiss)
     * @param {boolean} options.showProgress - Show progress bar
     * @param {Array} options.actions - Action buttons array
     */
    toast(arg1, arg2) {
        let options = {};
        if (arg1 !== null && typeof arg1 === 'object' && !Array.isArray(arg1)) {
            options = arg1;
        } else if (typeof arg1 === 'string' && typeof arg2 === 'string') {
            const types = ['success', 'error', 'warning', 'info'];
            options = types.includes(arg2)
                ? { message: arg1, type: arg2 }
                : { title: arg2, message: arg1, type: 'info' };
        } else {
            options = {};
        }

         const {
             title = '',
             message = '',
             type = 'info',
             duration = 8000,
             showProgress = true,
             actions = []
         } = options;

        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast-notification toast-${type}`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = this.createToastHTML(toastId, title, message, type, showProgress, actions);

        // Add to container
        document.getElementById('notification-container').appendChild(toast);

        // Initialize Bootstrap toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: duration > 0,
            delay: duration
        });

        // Debug log
        console.log(`Toast created with duration: ${duration}ms, autohide: ${duration > 0}`);

        // Show toast
        bsToast.show();

        // If duration is set, manually hide after the specified time
        if (duration > 0) {
            setTimeout(() => {
                console.log(`Manually hiding toast ${toastId} after ${duration}ms`);
                bsToast.hide();
            }, duration);
        }

        // Handle progress bar
        if (showProgress && duration > 0) {
            this.animateProgressBar(toastId, duration);
        }

        // Handle action buttons
        this.handleToastActions(toastId, actions);

        // Auto remove from DOM after hide
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });

        return toastId;
    }

    /**
     * Create toast HTML
     */
    createToastHTML(id, title, message, type, showProgress, actions) {
        const progressBar = showProgress ? `
            <div class="toast-progress">
                <div class="toast-progress-bar" id="progress-${id}"></div>
            </div>
        ` : '';

        const actionButtons = actions.length > 0 ? `
            <div class="toast-actions">
                ${actions.map(action => `
                    <button type="button" class="btn btn-sm btn-outline-${action.type || 'primary'}" 
                            data-action="${action.action}" data-toast-id="${id}">
                        ${action.icon ? `<i class="${action.icon}"></i> ` : ''}${action.text}
                    </button>
                `).join('')}
            </div>
        ` : '';

        return `
            <div class="toast-content">
                <div class="toast-header">
                    <div class="toast-icon">
                        <i class="${this.getTypeIcon(type)}"></i>
                    </div>
                    <div class="toast-info">
                        ${title ? `<div class="toast-title">${title}</div>` : ''}
                        <div class="toast-message">${message}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                ${actionButtons}
            </div>
            ${progressBar}
        `;
    }

    /**
     * Animate progress bar
     */
    animateProgressBar(toastId, duration) {
        const progressBar = document.getElementById(`progress-${toastId}`);
        if (progressBar) {
            progressBar.style.transition = `width ${duration}ms linear`;
            progressBar.style.width = '0%';
            setTimeout(() => {
                progressBar.style.width = '100%';
            }, 100);
        }
    }

    /**
     * Handle toast action buttons
     */
    handleToastActions(toastId, actions) {
        actions.forEach(action => {
            const button = document.querySelector(`[data-action="${action.action}"][data-toast-id="${toastId}"]`);
            if (button && action.handler) {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    action.handler(toastId);
                });
            }
        });
    }

    /**
     * Auto dismiss toasts
     */
    autoDismissToasts() {
        setInterval(() => {
            const toasts = document.querySelectorAll('.toast-notification');
            toasts.forEach(toast => {
                const bsToast = bootstrap.Toast.getInstance(toast);
                // Only remove if toast is hidden AND not currently showing
                if (bsToast && bsToast._isShown === false && toast.classList.contains('hide')) {
                    toast.remove();
                }
            });
        }, 5000); // Check every 5 seconds instead of 1 second
    }

    /**
     * Get type color
     */
    getTypeColor(type) {
        const colors = {
            success: 'success',
            error: 'danger',
            warning: 'warning',
            info: 'info',
            danger: 'danger'
        };
        return colors[type] || 'info';
    }

    /**
     * Get type icon
     */
    getTypeIcon(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle',
            danger: 'fas fa-times-circle'
        };
        return icons[type] || 'fas fa-info-circle';
    }

     /**
      * Quick success toast
      */
     success(message, title = 'Thành công!') {
         return this.toast({ title, message, type: 'success', duration: 5000 });
     }

     /**
      * Quick error toast
      */
     error(message, title = 'Lỗi!') {
         return this.toast({ title, message, type: 'error', duration: 8000 });
     }

     /**
      * Quick warning toast
      */
     warning(message, title = 'Cảnh báo!') {
         return this.toast({ title, message, type: 'warning', duration: 6000 });
     }

     /**
      * Quick info toast
      */
     info(message, title = 'Thông tin') {
         return this.toast({ title, message, type: 'info', duration: 4000 });
     }

    /**
     * Quick delete confirmation
     */
    confirmDelete(itemName = 'mục này', onConfirm) {
        return this.confirm({
            title: 'Xác nhận xóa',
            message: `Bạn có chắc chắn muốn xóa ${itemName}?`,
            details: 'Hành động này có thể được khôi phục.',
            type: 'danger',
            confirmText: 'Xóa',
            onConfirm
        });
    }

    /**
     * Quick cancel confirmation for appointments
     */
    confirmCancelAppointment(onConfirm) {
        return this.confirm({
            title: 'Xác nhận hủy lịch hẹn',
            message: 'Bạn có chắc chắn muốn hủy lịch hẹn này?',
            details: 'Lịch hẹn sẽ được hủy và không thể khôi phục.',
            type: 'warning',
            confirmText: 'Hủy lịch',
            cancelText: 'Không',
            onConfirm
        });
    }

    /**
     * Quick mark completed confirmation
     */
    confirmMarkCompleted(onConfirm) {
        return this.confirm({
            title: 'Xác nhận hoàn thành',
            message: 'Bạn có chắc chắn đã xem phòng này chưa?',
            details: 'Lịch hẹn sẽ được đánh dấu là hoàn thành.',
            type: 'info',
            confirmText: 'Đã xem',
            cancelText: 'Chưa',
            onConfirm
        });
    }

    /**
     * Quick save confirmation
     */
    confirmSave(onConfirm) {
        return this.confirm({
            title: 'Xác nhận lưu',
            message: 'Bạn có chắc chắn muốn lưu thay đổi?',
            type: 'info',
            confirmText: 'Lưu',
            onConfirm
        });
    }
}

// CSS Styles
const notificationStyles = `
<style>
.notification-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
}

.toast-notification {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    margin-bottom: 10px;
    overflow: hidden;
    border-left: 4px solid #007bff;
    min-width: 300px;
}

.toast-notification.toast-success {
    border-left-color: #28a745;
}

.toast-notification.toast-error {
    border-left-color: #dc3545;
}

.toast-notification.toast-warning {
    border-left-color: #ffc107;
}

.toast-notification.toast-info {
    border-left-color: #17a2b8;
}

.toast-content {
    padding: 0;
}

.toast-header {
    display: flex;
    align-items: flex-start;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
}

.toast-icon {
    margin-right: 12px;
    margin-top: 2px;
}

.toast-icon i {
    font-size: 20px;
}

.toast-info {
    flex: 1;
}

.toast-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
    color: #333;
}

.toast-message {
    font-size: 13px;
    color: #666;
    line-height: 1.4;
}

.toast-actions {
    padding: 8px 16px 12px;
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.toast-progress {
    height: 3px;
    background: #f0f0f0;
    overflow: hidden;
}

.toast-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #007bff, #0056b3);
    width: 100%;
    transition: width linear;
}

.toast-success .toast-progress-bar {
    background: linear-gradient(90deg, #28a745, #1e7e34);
}

.toast-error .toast-progress-bar {
    background: linear-gradient(90deg, #dc3545, #c82333);
}

.toast-warning .toast-progress-bar {
    background: linear-gradient(90deg, #ffc107, #e0a800);
}

.toast-info .toast-progress-bar {
    background: linear-gradient(90deg, #17a2b8, #138496);
}

.btn-close {
    background: none;
    border: none;
    font-size: 18px;
    color: #999;
    cursor: pointer;
    padding: 0;
    margin-left: 8px;
}

.btn-close:hover {
    color: #666;
}

/* Animation */
.toast-notification {
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .notification-container {
        left: 10px;
        right: 10px;
        max-width: none;
    }
    
    .toast-notification {
        min-width: auto;
    }
}
</style>
`;

// Inject styles
if (!document.getElementById('notification-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'notification-styles';
    styleElement.innerHTML = notificationStyles;
    document.head.appendChild(styleElement);
}

// Initialize global instance
window.Notify = new NotificationSystem();

// Add debug methods to global
window.testToastDuration = function(duration = 10000) {
    console.log(`Testing toast with ${duration}ms duration`);
    return window.Notify.toast({
        title: 'Test Duration',
        message: `Toast này sẽ hiển thị trong ${duration/1000} giây`,
        type: 'info',
        duration: duration,
        showProgress: true
    });
};

// Quick test methods
window.test5s = () => window.testToastDuration(5000);
window.test10s = () => window.testToastDuration(10000);
window.test20s = () => window.testToastDuration(20000);
window.testNoHide = () => window.testToastDuration(0);

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationSystem;
}

} // End of NotificationSystem class definition

// Global AJAX error handler for JSON responses
(function() {
    'use strict';

    // Helper function to handle organization access error
    function handleOrganizationError(data) {
        // Show notification
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error(data.message || 'Bạn chưa được gắn vào tổ chức nào. Vui lòng liên hệ quản trị viên.');
        } else {
            alert(data.message || 'Bạn chưa được gắn vào tổ chức nào. Vui lòng liên hệ quản trị viên.');
        }
        
        // Redirect if specified
        if (data.redirect) {
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
        } else {
            // Redirect to previous page or dashboard
            setTimeout(() => {
                if (document.referrer && document.referrer !== window.location.href) {
                    window.location.href = document.referrer;
                } else {
                    window.location.href = '/dashboard';
                }
            }, 1500);
        }
    }

    // Intercept fetch requests for error responses
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args)
            .then(response => {
                // Clone response to read body without consuming it
                const clonedResponse = response.clone();
                
                // Check if response is JSON and has error status
                if (response.status === 403 && clonedResponse.headers.get('content-type')?.includes('application/json')) {
                    // Check body for organization error
                    clonedResponse.json().then(data => {
                        // Handle organization access error
                        if (data.error === 'no_organization' || data.message) {
                            handleOrganizationError(data);
                        }
                    }).catch(() => {
                        // Not JSON or error reading, ignore
                    });
                }
                
                // Return original response
                return response;
            });
    };

    // Intercept jQuery AJAX requests if jQuery is available
    if (typeof jQuery !== 'undefined') {
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            // Only handle JSON responses with 403 status
            if (xhr.status === 403 && xhr.getResponseHeader('content-type')?.includes('application/json')) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    
                    // Handle organization access error
                    if (data.error === 'no_organization' || data.message) {
                        handleOrganizationError(data);
                    }
                } catch (e) {
                    // Not JSON, ignore
                }
            }
        });
    }
})();
