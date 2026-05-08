// Super Admin JavaScript - Standalone System

(function () {
    try {
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
    } catch (e) {
        /* ignore */
    }
})();

window.addEventListener('preloaderHidden', function () {
    window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
});

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Super Admin system
    initSuperAdminSidebar();
    initSuperAdminSectionMenus();
    initSuperAdminNavigation();
    initSuperAdminCharts();
    initSuperAdminNotifications();
    initSuperAdminMobileMenu();
    if (typeof window.Preloader === 'undefined') {
        hidePreloader();
    }
});

function isSuperAdminMobileView() {
    return window.innerWidth <= 991.98;
}

function removeSidebarOverlay() {
    const overlay = document.querySelector('.sidebar-overlay');
    if (overlay) {
        overlay.remove();
    }
}

function closeSuperAdminMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) {
        return;
    }

    sidebar.classList.remove('mobile-open');
    removeSidebarOverlay();
}

function openSuperAdminMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) {
        return;
    }

    sidebar.classList.add('mobile-open');

    if (!document.querySelector('.sidebar-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.addEventListener('click', closeSuperAdminMobileSidebar);
        document.body.appendChild(overlay);
    }
}

function toggleSuperAdminMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) {
        return;
    }

    if (sidebar.classList.contains('mobile-open')) {
        closeSuperAdminMobileSidebar();
    } else {
        openSuperAdminMobileSidebar();
    }
}

// Super Admin Sidebar Management
function initSuperAdminSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    if (!sidebar) {
        return;
    }
    
    // Sidebar header button: close on mobile, collapse on desktop
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (isSuperAdminMobileView()) {
                closeSuperAdminMobileSidebar();
                return;
            }

            sidebar.classList.toggle('collapsed');
            localStorage.setItem('superadminSidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }
    
    // Header mobile button toggle
    if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', function() {
            toggleSuperAdminMobileSidebar();
        });
    }
    
    // Restore sidebar state
    const savedState = localStorage.getItem('superadminSidebarCollapsed');
    if (!isSuperAdminMobileView() && savedState === 'true') {
        sidebar.classList.add('collapsed');
    } else if (isSuperAdminMobileView()) {
        sidebar.classList.remove('collapsed');
    }
}

/**
 * Nhóm có menu con: nhấn tiêu đề để mở/đóng (sidebar mở rộng / mobile).
 * Sidebar thu gọn (desktop): giữ điều hướng bằng link trên tiêu đề <a>; tiêu đề <div> thì mở rộng sidebar.
 */
function initSuperAdminSectionMenus() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) {
        return;
    }

    document.querySelectorAll('.superadmin-sidebar .nav-section').forEach(function (section) {
        const children = section.querySelector('.nav-section-children');
        const title = section.querySelector('.nav-section-title');
        if (!children || !title) {
            return;
        }

        if (children.querySelector('.nav-item.active')) {
            section.classList.add('is-open');
        }
        title.setAttribute('aria-expanded', section.classList.contains('is-open') ? 'true' : 'false');

        title.addEventListener('click', function (e) {
            const collapsedDesktop = sidebar.classList.contains('collapsed') && !isSuperAdminMobileView();

            if (collapsedDesktop) {
                if (title.tagName === 'A') {
                    return;
                }
                e.preventDefault();
                sidebar.classList.remove('collapsed');
                localStorage.setItem('superadminSidebarCollapsed', 'false');
                return;
            }

            e.preventDefault();
            section.classList.toggle('is-open');
            title.setAttribute('aria-expanded', section.classList.contains('is-open') ? 'true' : 'false');
        });
    });
}

// Super Admin Navigation
function initSuperAdminNavigation() {
    // Handle navigation clicks
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (this.classList.contains('disabled')) {
                return;
            }

            const sidebar = document.getElementById('sidebar');
            if (sidebar && sidebar.classList.contains('collapsed') && !isSuperAdminMobileView()) {
                e.preventDefault();
                sidebar.classList.remove('collapsed');
                localStorage.setItem('superadminSidebarCollapsed', 'false');
                return;
            }

            // Remove active from all items
            document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
            
            // Add active to clicked item
            this.classList.add('active');
            
            // Close mobile sidebar if open
            if (sidebar && sidebar.classList.contains('mobile-open')) {
                closeSuperAdminMobileSidebar();
            }
        });
    });
    
    // Handle disabled items
    document.querySelectorAll('.nav-item.disabled').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            if (typeof Notify !== 'undefined') {
                Notify.toast('Tính năng này sẽ được phát triển trong phiên bản tiếp theo', 'info');
            } else {
                alert('Tính năng này sẽ được phát triển trong phiên bản tiếp theo');
            }
        });
    });
}

// Super Admin Charts
function initSuperAdminCharts() {
    // Set default chart options
    Chart.defaults.font.family = 'Segoe UI, Tahoma, Geneva, Verdana, sans-serif';
    Chart.defaults.color = '#2c3e50';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.padding = 20;
    
    // Super Admin color palette
    window.superAdminColors = {
        primary: '#2c3e50',
        secondary: '#34495e',
        accent: '#e74c3c',
        success: '#27ae60',
        warning: '#f39c12',
        info: '#3498db',
        light: '#ecf0f1',
        dark: '#2c3e50'
    };
}

// Super Admin Notifications
function initSuperAdminNotifications() {
    // Add Super Admin specific styling to notification container
    if (typeof Notify !== 'undefined') {
        // Add Super Admin specific classes to notification container
        const container = document.getElementById('notification-container');
        if (container) {
            container.classList.add('superadmin-notifications');
        }
        
        // Override success method to add crown icon
        const originalSuccess = Notify.success;
        Notify.success = function(message, title = 'Thành công!') {
            const result = originalSuccess.call(this, message, title);
            
            // Add crown icon for Super Admin success notifications
            setTimeout(() => {
                const toasts = document.querySelectorAll('.toast-notification.toast-success');
                toasts.forEach(toast => {
                    const icon = toast.querySelector('.toast-icon i');
                    if (icon && !icon.classList.contains('fa-crown')) {
                        icon.className = 'fas fa-crown';
                    }
                });
            }, 100);
            
            return result;
        };
    }
}

// Super Admin Mobile Menu
function initSuperAdminMobileMenu() {
    // Handle window resize
    window.addEventListener('resize', function() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) {
            return;
        }

        if (!isSuperAdminMobileView()) {
            closeSuperAdminMobileSidebar();

            const savedState = localStorage.getItem('superadminSidebarCollapsed');
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
            }
        } else {
            sidebar.classList.remove('collapsed');
        }
    });
}

// Hide preloader
function hidePreloader() {
    const preloader = document.getElementById('preloader');
    if (preloader) {
        setTimeout(() => {
            preloader.style.opacity = '0';
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 300);
        }, 500);
    }
}

// Super Admin Cache Management
function clearSuperAdminCache() {
    if (typeof Notify !== 'undefined') {
        Notify.confirm({
            title: 'Làm mới dữ liệu Super Admin',
            message: 'Bạn có chắc chắn muốn làm mới tất cả dữ liệu Super Admin? Thao tác này sẽ xóa cache và tải lại dữ liệu mới nhất.',
            type: 'info',
            confirmText: 'Làm mới',
            onConfirm: function() {
                Notify.info('Đang làm mới dữ liệu Super Admin...');

                fetch('/superadmin/clear-cache', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notify.toast('Dữ liệu Super Admin đã được làm mới thành công!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Notify.toast('Có lỗi xảy ra khi làm mới dữ liệu Super Admin', 'error');
                    }
                })
                .catch(error => {
                    console.error('Super Admin Cache Error:', error);
                    Notify.toast('Có lỗi xảy ra khi làm mới dữ liệu Super Admin', 'error');
                });
            }
        });
    } else {
        // Fallback if Notify is not available
        if (confirm('Bạn có chắc chắn muốn làm mới tất cả dữ liệu Super Admin?')) {
            fetch('/superadmin/clear-cache', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Dữ liệu Super Admin đã được làm mới thành công!');
                    window.location.reload();
                } else {
                    alert('Có lỗi xảy ra khi làm mới dữ liệu Super Admin');
                }
            })
            .catch(error => {
                console.error('Super Admin Cache Error:', error);
                alert('Có lỗi xảy ra khi làm mới dữ liệu Super Admin');
            });
        }
    }
}

// Super Admin Organization Management
function toggleOrganizationStatus(organizationId, newStatus) {
    const action = newStatus ? 'kích hoạt' : 'tạm dừng';
    
    if (typeof Notify !== 'undefined') {
        Notify.confirm(
            `Bạn có chắc chắn muốn ${action} tổ chức này?`,
            function() {
                Notify.toast('Đang cập nhật trạng thái tổ chức...', 'info');
                
                fetch(`/superadmin/organizations/${organizationId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notify.toast(`Tổ chức đã được ${action} thành công!`, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Notify.toast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Organization Toggle Error:', error);
                    Notify.toast('Có lỗi xảy ra khi cập nhật trạng thái tổ chức', 'error');
                });
            }
        );
    }
}

// Super Admin Delete Organization
function deleteOrganization(organizationId, organizationName) {
    if (typeof Notify !== 'undefined') {
        Notify.confirmDelete(
            `Bạn có chắc chắn muốn xóa tổ chức "${organizationName}"? Hành động này không thể hoàn tác và sẽ ảnh hưởng đến tất cả dữ liệu liên quan.`,
            function() {
                Notify.toast('Đang xóa tổ chức...', 'info');
                
                fetch(`/superadmin/organizations/${organizationId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notify.toast('Tổ chức đã được xóa thành công!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Notify.toast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Organization Delete Error:', error);
                    Notify.toast('Có lỗi xảy ra khi xóa tổ chức', 'error');
                });
            }
        );
    }
}

// Super Admin Performance Monitoring
function initSuperAdminPerformance() {
    // Monitor page load performance
    window.addEventListener('load', function() {
        const loadTime = performance.now();
        console.log(`Super Admin Dashboard loaded in ${loadTime.toFixed(2)}ms`);
        
        // Send performance data to server (optional)
        if (loadTime > 3000) {
            console.warn('Super Admin Dashboard load time is slow:', loadTime);
        }
    });
}

// Initialize performance monitoring
document.addEventListener('DOMContentLoaded', function() {
    initSuperAdminPerformance();
});

// Super Admin Utility Functions
window.SuperAdmin = {
    // Clear cache
    clearCache: clearSuperAdminCache,
    
    // Organization management
    toggleOrganizationStatus: toggleOrganizationStatus,
    deleteOrganization: deleteOrganization,
    
    // Performance monitoring
    getPerformanceMetrics: function() {
        return {
            loadTime: performance.now(),
            memoryUsage: performance.memory ? performance.memory.usedJSHeapSize : 'N/A',
            timestamp: new Date().toISOString()
        };
    },
    
    // System info
    getSystemInfo: function() {
        return {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            cookieEnabled: navigator.cookieEnabled,
            onLine: navigator.onLine
        };
    }
};