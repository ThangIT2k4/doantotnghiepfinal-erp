// User Dashboard JavaScript
// Dashboard data will be loaded from server via Blade template
var dashboardData = window.dashboardData || {
    stats: {
        appointments: { total: 0, this_week: 0, trend: 'stable' },
        contracts: { total: 0, active: 0, trend: 'stable' },
        invoices: { total: 0, this_month: 0, trend: 'stable' },
        notifications: { unread: 0, today: 0, trend: 'stable' },
        tickets: { total: 0, open: 0, trend: 'stable' },
        reviews: { total: 0, pending: 0, trend: 'stable' }
    },
    quickStats: {
        completed_appointments: 0,
        average_rating: 0,
        total_paid: 0,
        total_rooms_rented: 0
    }
};

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    setupEventListeners();
    loadDashboardData();
    startAutoRefresh();
});

// Initialize dashboard functionality
function initializeDashboard() {
    console.log('Dashboard initialized');
    
    // Set current date
    updateCurrentDate();
    
    // Initialize tooltips if Bootstrap is available
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Animate stat cards on load
    animateStatCards();
}

// Setup event listeners
function setupEventListeners() {
    // Quick access cards
    var quickAccessCards = document.querySelectorAll('.quick-access-card');
    for (var i = 0; i < quickAccessCards.length; i++) {
        quickAccessCards[i].addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        quickAccessCards[i].addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-3px) scale(1)';
        });
    }
    
    // Stat cards hover effects
    var statCards = document.querySelectorAll('.stat-card');
    for (var j = 0; j < statCards.length; j++) {
        statCards[j].addEventListener('click', function() {
            var cardType = this.classList[1]; // appointments, contracts, etc.
            handleStatCardClick(cardType);
        });
    }
    
    // Activity items click
    var activityItems = document.querySelectorAll('.activity-item');
    for (var k = 0; k < activityItems.length; k++) {
        activityItems[k].addEventListener('click', function() {
            this.style.transform = 'translateX(10px)';
            setTimeout(() => {
                this.style.transform = 'translateX(5px)';
            }, 150);
        });
    }
}

// Update current date
function updateCurrentDate() {
    var now = new Date();
    var dateStr = now.toLocaleDateString('vi-VN', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    var subtitleElement = document.querySelector('.welcome-subtitle');
    if (subtitleElement) {
        subtitleElement.textContent = 'Chào mừng bạn quay lại. Hôm nay là ' + dateStr;
    }
}

// Animate stat cards
function animateStatCards() {
    var statCards = document.querySelectorAll('.stat-card');
    
    for (var i = 0; i < statCards.length; i++) {
        (function(index, card) {
            setTimeout(function() {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                
                setTimeout(function() {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 50);
            }, index * 100);
        })(i, statCards[i]);
    }
}

// Handle stat card clicks
function handleStatCardClick(cardType) {
    var routes = {
        'appointments': '/appointments',
        'contracts': '#',
        'invoices': '#',
        'notifications': '#'
    };
    
    if (routes[cardType] && routes[cardType] !== '#') {
        window.location.href = routes[cardType];
    } else {
        showComingSoon('Chức năng ' + getCardTypeName(cardType));
    }
}

// Get card type display name
function getCardTypeName(cardType) {
    var names = {
        'appointments': 'Lịch hẹn',
        'contracts': 'Hợp đồng',
        'invoices': 'Hóa đơn',
        'notifications': 'Thông báo'
    };
    return names[cardType] || cardType;
}

// Load dashboard data
function loadDashboardData() {
    // Simulate API call to load dashboard data
    setTimeout(function() {
        updateStatNumbers();
        loadRecentActivity();
        loadUpcomingEvents();
        updateQuickStats();
    }, 500);
}

// Update stat numbers with animation
function updateStatNumbers() {
    // Get values from server data (already displayed in HTML)
    // Don't override values from server - they are already correct
    // This function is kept for compatibility but won't change displayed values
    var statElements = document.querySelectorAll('.stat-card .stat-number');
    statElements.forEach(function(element) {
        // Values are already set from server, just animate if needed
        var currentValue = parseInt(element.textContent) || 0;
        if (currentValue > 0) {
            // Animate from 0 to current value
            animateNumberToElement(element, currentValue);
        }
    });
}

// Animate number to specific element
function animateNumberToElement(element, targetValue) {
    var currentValue = 0;
    var increment = targetValue / 30;
    var timer = setInterval(function() {
        currentValue += increment;
        if (currentValue >= targetValue) {
            currentValue = targetValue;
            clearInterval(timer);
        }
        element.textContent = Math.floor(currentValue);
    }, 50);
}

// Animate number counting
function animateNumber(selector, targetValue) {
    var element = document.querySelector(selector);
    if (!element) return;
    
    var currentValue = 0;
    var increment = targetValue / 30;
    var timer = setInterval(function() {
        currentValue += increment;
        if (currentValue >= targetValue) {
            currentValue = targetValue;
            clearInterval(timer);
        }
        element.textContent = Math.floor(currentValue);
    }, 50);
}

// Load recent activity (simulate)
function loadRecentActivity() {
    // This would typically fetch from an API
    console.log('Recent activity loaded');
}

// Load upcoming events (simulate)
function loadUpcomingEvents() {
    // This would typically fetch from an API
    console.log('Upcoming events loaded');
}

// Update quick stats - don't override server values
function updateQuickStats() {
    // Values are already set from server in the Blade template
    // This function is kept for compatibility but won't change displayed values
    // Just animate existing values if needed
    var quickStats = document.querySelectorAll('.stat-item .stat-value');
    quickStats.forEach(function(element) {
        var text = element.textContent.trim();
        // Don't override if value already exists from server
        if (text && text !== '0' && text !== '0.0') {
            // Value is already set from server, just animate if it's a number
            var numericValue = parseFloat(text.replace(/[^\d.]/g, ''));
            if (!isNaN(numericValue) && numericValue > 0) {
                // Optional: animate the number if you want
                // animateNumberToElement(element, numericValue);
            }
        }
    });
}

// Start auto refresh for real-time updates
function startAutoRefresh() {
    // Refresh dashboard data every 5 minutes
    setInterval(function() {
        refreshDashboardData();
    }, 5 * 60 * 1000);
    
    // Update time every minute
    setInterval(function() {
        updateCurrentDate();
    }, 60 * 1000);
}

// Refresh dashboard data
function refreshDashboardData() {
    // Simulate data refresh
    console.log('Dashboard data refreshed at:', new Date().toLocaleTimeString());
    
    // Add subtle animation to indicate refresh
    var header = document.querySelector('.dashboard-header');
    if (header) {
        header.style.opacity = '0.8';
        setTimeout(function() {
            header.style.opacity = '1';
        }, 200);
    }
}

// Show coming soon modal
function showComingSoon(featureName) {
    var modal = document.getElementById('comingSoonModal');
    var message = document.getElementById('comingSoonMessage');
    
    if (message) {
        message.textContent = featureName + ' đang được phát triển và sẽ có mặt trong phiên bản tiếp theo.';
    }
    
    if (typeof bootstrap !== 'undefined' && modal) {
        var comingSoonModal = new bootstrap.Modal(modal);
        comingSoonModal.show();
    } else {
        alert(featureName + ' sắp ra mắt!');
    }
}

// Navigate to appointments
function goToAppointments() {
    window.location.href = '/appointments';
}

// Handle quick action clicks
function handleQuickAction(action) {
    var actions = {
        'search': '/home',
        'book': '/booking/1',
        'extend': '#',
        'repair': '#',
        'rate': '#'
    };
    
    if (actions[action] && actions[action] !== '#') {
        window.location.href = actions[action];
    } else {
        showComingSoon('Tính năng này');
    }
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// Format date
function formatDate(date) {
    return new Date(date).toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Show toast notification
function showToast(message, type) {
    // Remove existing toasts
    var existingToasts = document.querySelectorAll('.dashboard-toast');
    for (var i = 0; i < existingToasts.length; i++) {
        existingToasts[i].remove();
    }
    
    var toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        toastContainer.style.cssText = 'position:fixed;top:20px;right:20px;z-index:1050;';
        document.body.appendChild(toastContainer);
    }
    
    var toast = document.createElement('div');
    toast.className = 'dashboard-toast toast-' + type;
    toast.style.cssText = 'background:white;padding:16px 20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.15);margin-bottom:10px;display:flex;align-items:center;gap:12px;min-width:300px;animation:slideInRight 0.3s ease;border-left:4px solid;';
    
    var icon = 'info-circle';
    var color = '#3b82f6';
    
    if (type === 'success') {
        icon = 'check-circle';
        color = '#10b981';
    } else if (type === 'error') {
        icon = 'times-circle';
        color = '#ef4444';
    } else if (type === 'warning') {
        icon = 'exclamation-triangle';
        color = '#f59e0b';
    }
    
    toast.style.borderLeftColor = color;
    toast.innerHTML = '<i class="fas fa-' + icon + '" style="color:' + color + ';font-size:1.2rem;"></i><span>' + message + '</span>';
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 4 seconds
    setTimeout(function() {
        if (toast.parentElement) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(function() {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }
    }, 4000);
}

// Handle responsive behavior
function handleResponsive() {
    var isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // Mobile-specific adjustments
        var statCards = document.querySelectorAll('.stat-card');
        for (var i = 0; i < statCards.length; i++) {
            statCards[i].style.marginBottom = '15px';
        }
    }
}

// Window resize handler
window.addEventListener('resize', handleResponsive);

// Page visibility change handler
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Page became visible, refresh data
        refreshDashboardData();
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Alt + 1: Go to appointments
    if (e.altKey && e.key === '1') {
        e.preventDefault();
        goToAppointments();
    }
    
    // Alt + 2: Search rooms
    if (e.altKey && e.key === '2') {
        e.preventDefault();
        window.location.href = '/home';
    }
    
    // Alt + 3: Book appointment
    if (e.altKey && e.key === '3') {
        e.preventDefault();
        window.location.href = '/booking/1';
    }
});

// Performance monitoring
var performanceMonitor = {
    startTime: Date.now(),
    
    logLoadTime: function() {
        var loadTime = Date.now() - this.startTime;
        console.log('Dashboard loaded in:', loadTime + 'ms');
        
        if (loadTime > 3000) {
            console.warn('Dashboard load time is slow:', loadTime + 'ms');
        }
    }
};

// Log performance when page is fully loaded
window.addEventListener('load', function() {
    performanceMonitor.logLoadTime();
});

// Add CSS animations if not present
if (!document.querySelector('#dashboard-animations')) {
    var style = document.createElement('style');
    style.id = 'dashboard-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeInUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
        
        .animate-pulse {
            animation: pulse 2s infinite;
        }
    `;
    document.head.appendChild(style);
}

// Initialize responsive behavior
handleResponsive();
