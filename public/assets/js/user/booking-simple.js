// Simple Booking Page JavaScript
var slotCounter = 1;

// Add new time slot
function addTimeSlot() {
    slotCounter++;
    var timeSlotsContainer = document.getElementById('timeSlots');
    
    if (!timeSlotsContainer) {
        console.error('timeSlots container not found');
        return;
    }
    
    var newSlot = document.createElement('div');
    newSlot.className = 'time-slot';
    newSlot.setAttribute('data-slot', slotCounter);
    
    var today = new Date().toISOString().split('T')[0];
    
    newSlot.innerHTML = 
        '<div class="time-slot-header">' +
            '<h5>Khung thời gian ' + slotCounter + '</h5>' +
            '<button type="button" class="btn-remove-slot" onclick="removeTimeSlot(' + slotCounter + ')">' +
                '<i class="fas fa-trash"></i>' +
            '</button>' +
        '</div>' +
        '<div class="row">' +
            '<div class="col-md-4 mb-3">' +
                '<label class="form-label">Ngày <span class="required">*</span></label>' +
                '<input type="date" class="form-control" name="slots[' + slotCounter + '][date]" required min="' + today + '">' +
            '</div>' +
            '<div class="col-md-4 mb-3">' +
                '<label class="form-label">Từ giờ <span class="required">*</span></label>' +
                '<input type="time" class="form-control" name="slots[' + slotCounter + '][start_time]" required>' +
            '</div>' +
            '<div class="col-md-4 mb-3">' +
                '<label class="form-label">Đến giờ <span class="required">*</span></label>' +
                '<input type="time" class="form-control" name="slots[' + slotCounter + '][end_time]" required>' +
            '</div>' +
        '</div>' +
        '<div class="mb-3">' +
            '<label class="form-label">Ghi chú cho khung thời gian này</label>' +
            '<input type="text" class="form-control" name="slots[' + slotCounter + '][note]" placeholder="Ví dụ: Sáng chủ nhật, sau 18h...">' +
        '</div>';
    
    timeSlotsContainer.appendChild(newSlot);
    
    // Add animation
    newSlot.style.opacity = '0';
    newSlot.style.transform = 'translateY(20px)';
    setTimeout(function() {
        newSlot.style.transition = 'all 0.3s ease';
        newSlot.style.opacity = '1';
        newSlot.style.transform = 'translateY(0)';
    }, 10);
    
    // Update remove button visibility
    updateRemoveButtonVisibility();
    
    // Show success message
    showSimpleToast('Đã thêm khung thời gian mới', 'success');
}

// Remove time slot
function removeTimeSlot(slotId) {
    var slot = document.querySelector('[data-slot="' + slotId + '"]');
    if (slot) {
        // Add animation
        slot.style.transition = 'all 0.3s ease';
        slot.style.opacity = '0';
        slot.style.transform = 'translateX(-100%)';
        
        setTimeout(function() {
            slot.remove();
            updateRemoveButtonVisibility();
            showSimpleToast('Đã xóa khung thời gian', 'info');
        }, 300);
    }
}

// Update remove button visibility
function updateRemoveButtonVisibility() {
    var slots = document.querySelectorAll('.time-slot');
    for (var i = 0; i < slots.length; i++) {
        var removeBtn = slots[i].querySelector('.btn-remove-slot');
        if (removeBtn) {
            removeBtn.style.display = slots.length > 1 ? 'block' : 'none';
        }
    }
}

// Simple toast notification
function showSimpleToast(message, type) {
    // Remove existing toasts
    var existingToasts = document.querySelectorAll('.simple-toast');
    for (var i = 0; i < existingToasts.length; i++) {
        existingToasts[i].remove();
    }
    
    var toast = document.createElement('div');
    toast.className = 'simple-toast toast-' + type;
    toast.innerHTML = '<span>' + message + '</span>';
    
    // Add toast styles if not already added
    if (!document.querySelector('#simple-toast-styles')) {
        var styles = document.createElement('style');
        styles.id = 'simple-toast-styles';
        styles.textContent = 
            '.simple-toast {' +
                'position: fixed;' +
                'top: 20px;' +
                'right: 20px;' +
                'background: white;' +
                'padding: 16px 20px;' +
                'border-radius: 8px;' +
                'box-shadow: 0 4px 12px rgba(0,0,0,0.15);' +
                'z-index: 1000;' +
                'animation: slideInRight 0.3s ease;' +
                'border-left: 4px solid;' +
            '}' +
            '.toast-success { border-left-color: #10b981; color: #10b981; }' +
            '.toast-error { border-left-color: #ef4444; color: #ef4444; }' +
            '.toast-info { border-left-color: #3b82f6; color: #3b82f6; }' +
            '@keyframes slideInRight {' +
                'from { transform: translateX(100%); opacity: 0; }' +
                'to { transform: translateX(0); opacity: 1; }' +
            '}';
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(function() {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 3000);
}

// Phone number formatting
function formatPhoneNumber(input) {
    var value = input.value.replace(/\D/g, '');
    
    // Limit to 10 digits
    if (value.length > 10) {
        value = value.substring(0, 10);
    }
    
    // Format as XXX XXX XXXX
    if (value.length >= 6) {
        value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
    } else if (value.length >= 3) {
        value = value.replace(/(\d{3})(\d{1,3})/, '$1 $2');
    }
    
    input.value = value;
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to today
    var dateInputs = document.querySelectorAll('input[type="date"]');
    var today = new Date().toISOString().split('T')[0];
    for (var i = 0; i < dateInputs.length; i++) {
        dateInputs[i].min = today;
    }
    
    // Phone number formatting
    var phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    }
    
    // Purpose option styling
    var purposeOptions = document.querySelectorAll('.form-check');
    for (var i = 0; i < purposeOptions.length; i++) {
        purposeOptions[i].addEventListener('click', function() {
            var radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                
                // Update styling
                for (var j = 0; j < purposeOptions.length; j++) {
                    purposeOptions[j].classList.remove('selected');
                }
                this.classList.add('selected');
            }
        });
    }
    
    // Form submission
    var form = document.getElementById('bookingForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var submitBtn = form.querySelector('.btn-submit');
            
            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            // Simulate API call
            setTimeout(function() {
                // Hide loading state
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
                
                // Show success modal if Bootstrap is available
                if (typeof bootstrap !== 'undefined') {
                    var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                } else {
                    alert('Đặt lịch thành công! Chủ nhà sẽ liên hệ với bạn sớm.');
                }
                
                // Reset form
                form.reset();
                
                // Show success message
                showSimpleToast('Đặt lịch thành công!', 'success');
            }, 2000);
        });
    }
    
    // Update remove button visibility on load
    updateRemoveButtonVisibility();
});
