document.addEventListener('DOMContentLoaded', function() {
    // Cache original dashboard markup for quick restore
    try {
        const contentEl = document.getElementById('content');
        if (contentEl && !window.__dashboardInitialHTML) {
            window.__dashboardInitialHTML = contentEl.innerHTML;
        }
    } catch (_) {}
    
    // Initialize dashboard
    initSidebar();
    initNavigation();
    initChart();
    updateStats();
    
    // Auto-refresh data every 30 seconds
    setInterval(updateStats, 30000);
});

// Sidebar functionality
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (!sidebar || !sidebarToggle) return;
    
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        
        // Close all submenus when collapsing
        if (sidebar.classList.contains('collapsed')) {
            document.querySelectorAll('.submenu').forEach(menu => {
                menu.classList.remove('open');
            });
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
        }
        
        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('agentSidebarCollapsed', isCollapsed);
        
        // Add animation class for smooth transition
        sidebar.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    });
    
    // Restore sidebar state from localStorage
    const savedState = localStorage.getItem('agentSidebarCollapsed');
    if (savedState === 'true') {
        sidebar.classList.add('collapsed');
    }
    
    // Handle mobile sidebar
    if (window.innerWidth <= 768) {
        sidebar.classList.add('collapsed');
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed');
        } else {
            const savedState = localStorage.getItem('agentSidebarCollapsed');
            if (savedState === 'false') {
                sidebar.classList.remove('collapsed');
            }
        }
    });
}

// Navigation functionality
function initNavigation() {
    // Handle submenu toggles
    document.querySelectorAll('.nav-parent').forEach(parent => {
        parent.addEventListener('click', function(e) {
            e.preventDefault();
            
            const navGroup = this.closest('.nav-group');
            const submenu = navGroup.querySelector('.submenu');
            
            // Close other submenus
            document.querySelectorAll('.nav-group').forEach(group => {
                if (group !== navGroup) {
                    group.classList.remove('active');
                    group.querySelector('.submenu').style.maxHeight = '0';
                }
            });
            
            // Toggle current submenu
            navGroup.classList.toggle('active');
            
            if (navGroup.classList.contains('active')) {
                submenu.style.maxHeight = submenu.scrollHeight + 'px';
            } else {
                submenu.style.maxHeight = '0';
            }
        });
    });
    
    // Handle active states
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item, .submenu-item').forEach(item => {
        const href = item.getAttribute('href');
        if (href && currentPath.includes(href.replace('/agent', ''))) {
            item.classList.add('active');
            
            // Open parent submenu if this is a submenu item
            const submenu = item.closest('.submenu');
            if (submenu) {
                const navGroup = submenu.closest('.nav-group');
                navGroup.classList.add('active');
                submenu.style.maxHeight = submenu.scrollHeight + 'px';
            }
        }
    });
}

// Chart functionality
function initChart() {
    // Initialize any charts if they exist
    const chartElements = document.querySelectorAll('.chart-container');
    
    chartElements.forEach(element => {
        const ctx = element.querySelector('canvas');
        if (ctx && typeof Chart !== 'undefined') {
            const chartType = element.dataset.chartType || 'line';
            const chartData = JSON.parse(element.dataset.chartData || '{}');
            
            new Chart(ctx, {
                type: chartType,
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: element.dataset.chartTitle || 'Chart'
                        }
                    }
                }
            });
        }
    });
}

// Stats update functionality
function updateStats() {
    // Update any real-time stats
    const statsElements = document.querySelectorAll('[data-stat-endpoint]');
    
    statsElements.forEach(element => {
        const endpoint = element.dataset.statEndpoint;
        const statType = element.dataset.statType;
        
        if (endpoint && statType) {
            fetch(endpoint, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data[statType] !== undefined) {
                    element.textContent = data[statType];
                    
                    // Add animation
                    element.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        element.style.transform = 'scale(1)';
                    }, 200);
                }
            })
            .catch(error => {
                console.error('Error updating stats:', error);
            });
        }
    });
}

// Utility functions
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function showLoading(element) {
    if (element) {
        element.classList.add('loading');
        element.style.pointerEvents = 'none';
    }
}

function hideLoading(element) {
    if (element) {
        element.classList.remove('loading');
        element.style.pointerEvents = 'auto';
    }
}

// Form handling
function initFormHandlers() {
    // Handle form submissions
    document.querySelectorAll('form[data-ajax]').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            showLoading(submitBtn);
            
            fetch(this.action, {
                method: this.method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading(submitBtn);
                
                if (data.success) {
                    showNotification(data.message || 'Operation completed successfully', 'success');
                    
                    // Redirect if specified
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    showNotification(data.message || 'An error occurred', 'error');
                }
            })
            .catch(error => {
                hideLoading(submitBtn);
                showNotification('An error occurred', 'error');
                console.error('Error:', error);
            });
        });
    });
}

// Initialize form handlers when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initFormHandlers();
});

// Export functions for global use
window.agentDashboard = {
    showNotification,
    showLoading,
    hideLoading,
    updateStats
};