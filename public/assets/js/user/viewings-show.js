// Viewing Show Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Clean modal implementation
    let currentViewingId = null;
    const cancelModal = document.getElementById('cancelModal');
    const cancelButton = document.querySelector('[onclick*="cancelViewing"]');
    const confirmButton = document.querySelector('[onclick="confirmCancel()"]');
    
    // Handle cancel button click
    if (cancelButton) {
        cancelButton.addEventListener('click', function(e) {
            e.preventDefault();
            const viewingId = this.getAttribute('data-viewing-id') || window.viewingId || null;
            showCancelModal(viewingId);
        });
    }
    
    // Handle confirm cancel
    if (confirmButton) {
        confirmButton.addEventListener('click', function(e) {
            e.preventDefault();
            if (currentViewingId) {
                confirmCancelViewing(currentViewingId);
            }
        });
    }
    
    // Modal event listeners
    if (cancelModal) {
        cancelModal.addEventListener('hidden.bs.modal', function() {
            currentViewingId = null;
        });
    }
    
    // Time remaining update
    updateTimeRemaining();
    setInterval(updateTimeRemaining, 60000);
});

function showCancelModal(viewingId) {
    currentViewingId = viewingId;
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

function confirmCancelViewing(viewingId) {
    if (!viewingId) return;
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('cancelModal'));
    
    // Show loading state
    const confirmBtn = document.querySelector('[onclick="confirmCancel()"]');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    confirmBtn.disabled = true;
    
    // Use appointments route for consistency
    const cancelUrl = window.appointmentsCancelRoute || `/tenant/appointments/${viewingId}/cancel` || `/viewings/${viewingId}/cancel`;
    fetch(cancelUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification('success', data.message || 'Hủy lịch thành công');
            modal.hide();
            setTimeout(() => {
                window.location.href = window.appointmentsRoute || '/tenant/appointments';
            }, 1500);
        } else {
            showNotification('error', data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Cancel viewing error:', error);
        showNotification('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
    })
    .finally(() => {
        // Reset button state
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    });
}

function showNotification(type, message) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = 'custom-notification alert alert-dismissible fade show position-fixed';
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
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

function updateTimeRemaining() {
    const timeElement = document.getElementById('timeRemaining');
    if (!timeElement) return;
    
    try {
        const scheduleTime = new Date(window.scheduleTime || '{{ $viewing->schedule_at->toISOString() }}');
        const now = new Date();
        
        if (scheduleTime > now) {
            const diff = scheduleTime - now;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            
            let timeText = '';
            if (days > 0) timeText += `${days} ngày `;
            if (hours > 0) timeText += `${hours} giờ `;
            if (minutes > 0) timeText += `${minutes} phút`;
            
            timeElement.innerHTML = `<i class="fas fa-clock me-1"></i>${timeText.trim() || 'Sắp tới'}`;
        } else {
            timeElement.innerHTML = '<i class="fas fa-times me-1"></i><span>Đã qua</span>';
        }
    } catch (error) {
        console.error('Time update error:', error);
        timeElement.textContent = 'Không xác định';
    }
}

// Clean up any existing event listeners
window.cancelViewing = function(viewingId) {
    showCancelModal(viewingId);
};

window.confirmCancel = function() {
    if (currentViewingId) {
        confirmCancelViewing(currentViewingId);
    }
};
