// Profile Edit Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Profile edit page loaded');
    
    // Initialize profile edit functionality
    initializeProfileEdit();
    
    // Auto-hide alerts after 5 seconds
    autoHideAlerts();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize password toggle
    initializePasswordToggle();
    
    // Initialize date validation
    initializeDateValidation();
});

function initializeProfileEdit() {
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
    
    // Add loading states to submit button
    const submitButton = document.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.addEventListener('click', function() {
            addLoadingState(this);
        });
    }
    
    // Add confirmation for logout
    const logoutButton = document.querySelector('button[type="submit"]');
    if (logoutButton && logoutButton.textContent.includes('Đăng xuất')) {
        logoutButton.addEventListener('click', function(e) {
            e.preventDefault();
            showLogoutConfirmation(this);
        });
    }
    
    // Add form change detection
    detectFormChanges();
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

function initializeFormValidation() {
    const form = document.getElementById('profileEditForm') || document.querySelector('form');
    if (!form) return;
    
    // Real-time validation
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearFieldError(this);
        });
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            showNotification('error', 'Vui lòng kiểm tra lại thông tin đã nhập');
        }
    });
}

function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name;
    let isValid = true;
    let errorMessage = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'Trường này là bắt buộc';
    }
    
    // Email validation
    if (fieldName === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Email không hợp lệ';
        }
    }
    
    // Phone validation
    if (fieldName === 'phone' && value) {
        const phoneRegex = /^[0-9+\-\s()]+$/;
        if (!phoneRegex.test(value) || value.length < 10) {
            isValid = false;
            errorMessage = 'Số điện thoại không hợp lệ';
        }
    }
    
    // Password validation
    if (fieldName === 'password' && value) {
        if (value.length < 8) {
            isValid = false;
            errorMessage = 'Mật khẩu phải có ít nhất 8 ký tự';
        }
    }
    
    // Password confirmation validation
    if (fieldName === 'password_confirmation' && value) {
        const passwordField = document.querySelector('input[name="password"]');
        if (passwordField && value !== passwordField.value) {
            isValid = false;
            errorMessage = 'Mật khẩu xác nhận không khớp';
        }
    }
    
    // ID number validation
    if (fieldName === 'id_number' && value) {
        const idRegex = /^[0-9]{9,12}$/;
        if (!idRegex.test(value)) {
            isValid = false;
            errorMessage = 'Số CMND/CCCD phải có 9-12 chữ số';
        }
    }
    
    if (!isValid) {
        showFieldError(field, errorMessage);
    } else {
        clearFieldError(field);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('is-invalid');
    
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function validateForm() {
    const form = document.getElementById('profileEditForm') || document.querySelector('form');
    if (!form) return true;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function initializePasswordToggle() {
    const passwordFields = document.querySelectorAll('input[type="password"]');
    
    passwordFields.forEach(field => {
        const wrapper = document.createElement('div');
        wrapper.className = 'password-toggle';
        
        field.parentNode.insertBefore(wrapper, field);
        wrapper.appendChild(field);
        
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle-btn';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
        
        wrapper.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
            field.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    });
}

function initializeDateValidation() {
    const dateFields = document.querySelectorAll('input[type="date"]');
    
    dateFields.forEach(field => {
        field.addEventListener('change', function() {
            const value = this.value;
            const fieldName = this.name;
            
            if (value) {
                const selectedDate = new Date(value);
                const today = new Date();
                
                // DOB validation - must be in the past
                if (fieldName === 'dob') {
                    if (selectedDate >= today) {
                        showFieldError(this, 'Ngày sinh phải trong quá khứ');
                        return;
                    }
                    
                    // Check if age is reasonable (between 16 and 120)
                    const age = today.getFullYear() - selectedDate.getFullYear();
                    if (age < 16 || age > 120) {
                        showFieldError(this, 'Tuổi phải từ 16 đến 120');
                        return;
                    }
                }
                
                // ID issued date validation - must be in the past
                if (fieldName === 'id_issued_at') {
                    if (selectedDate > today) {
                        showFieldError(this, 'Ngày cấp phải trong quá khứ');
                        return;
                    }
                }
                
                clearFieldError(this);
            }
        });
    });
}

function detectFormChanges() {
    const form = document.getElementById('profileEditForm') || document.querySelector('form');
    if (!form) return;
    
    const originalData = new FormData(form);
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const currentData = new FormData(form);
            let hasChanges = false;
            
            for (let [key, value] of currentData.entries()) {
                if (originalData.get(key) !== value) {
                    hasChanges = true;
                    break;
                }
            }
            
            // Add visual indicator for unsaved changes
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                if (hasChanges) {
                    submitButton.classList.add('btn-warning');
                    submitButton.classList.remove('btn-primary');
                    submitButton.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Có thay đổi chưa lưu';
                } else {
                    submitButton.classList.add('btn-primary');
                    submitButton.classList.remove('btn-warning');
                    submitButton.innerHTML = '<i class="fas fa-save me-1"></i>Cập nhật thông tin';
                }
            }
        });
    });
}

function addLoadingState(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang cập nhật...';
    button.disabled = true;
    
    // Re-enable after 5 seconds (fallback)
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 5000);
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
                const form = button.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    } else {
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
    
    // Ctrl+S to save form
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        const submitButton = document.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.click();
        }
    }
});

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
