// Profile Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Profile page loaded');
    
    // Initialize profile functionality
    initializeProfile();
    
    // Auto-hide alerts after 5 seconds
    autoHideAlerts();
    
    // Initialize tooltips if any
    initializeTooltips();
});

function initializeProfile() {
    // Add smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add loading states to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        if (button.type === 'submit' || button.textContent.includes('Cập nhật') || button.textContent.includes('Lưu')) {
            button.addEventListener('click', function() {
                addLoadingState(this);
            });
        }
    });
    
    // Add confirmation for logout
    const logoutButton = document.querySelector('button[type="submit"]');
    if (logoutButton && logoutButton.textContent.includes('Đăng xuất')) {
        logoutButton.addEventListener('click', function(e) {
            e.preventDefault();
            showLogoutConfirmation(this);
        });
    }
}

function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 500);
                }
            }, 5000);
        }
    });
}

function initializeTooltips() {
    // Initialize Bootstrap tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

function addLoadingState(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
    button.disabled = true;
    
    // Re-enable after 3 seconds (fallback)
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 3000);
}

function showLogoutConfirmation(button) {
    if (window.Notify && typeof window.Notify.confirm === 'function') {
        window.Notify.confirm({
            title: 'Xác nhận đăng xuất',
            message: 'Bạn có chắc chắn muốn đăng xuất khỏi tài khoản?',
            type: 'warning',
            confirmText: 'Đăng xuất',
            cancelText: 'Hủy',
            onConfirm: () => {
                // Submit the logout form
                const form = button.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    } else {
        // Fallback confirmation
        if (confirm('Bạn có chắc chắn muốn đăng xuất khỏi tài khoản?')) {
            const form = button.closest('form');
            if (form) {
                form.submit();
            }
        }
    }
}

// Add smooth animations for cards
function animateCards() {
    const cards = document.querySelectorAll('.modern-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, {
        threshold: 0.1
    });
    
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
}

// Initialize animations when page loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', animateCards);
} else {
    animateCards();
}

// Add keyboard navigation support
document.addEventListener('keydown', function(e) {
    // ESC key to close alerts
    if (e.key === 'Escape') {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            }
        });
    }
});

// Add copy to clipboard functionality for email
function addCopyToClipboard() {
    const emailElement = document.querySelector('input[type="email"]');
    if (emailElement && emailElement.value) {
        emailElement.addEventListener('click', function() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(this.value).then(() => {
                    showNotification('success', 'Đã sao chép email vào clipboard');
                });
            }
        });
        
        // Add visual indicator
        emailElement.style.cursor = 'pointer';
        emailElement.title = 'Click để sao chép email';
    }
}

// Initialize copy functionality
addCopyToClipboard();

function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px; 
        right: 20px; 
        z-index: 9999; 
        min-width: 300px; 
        max-width: 400px;
        background: ${type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #dc2626)'};
        color: white;
        border: none;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    `;
    
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close btn-close-white ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}
