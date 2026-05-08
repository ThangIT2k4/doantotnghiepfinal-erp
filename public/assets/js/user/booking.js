// Booking Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bookingForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    // Test notification system
    console.log('window.Notify:', window.Notify);
    
    form.addEventListener('submit', function(e) {
        // Show confirmation dialog before submitting
        if (window.Notify && typeof window.Notify.confirm === 'function') {
            e.preventDefault(); // Prevent default submission
            
            window.Notify.confirm({
                title: 'Xác nhận đặt lịch',
                message: 'Bạn có chắc chắn muốn đặt lịch xem phòng với thông tin đã nhập?',
                type: 'info',
                confirmText: 'Đặt lịch',
                cancelText: 'Hủy',
                onConfirm: () => {
                    // Disable button and show loading
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                    
                    // Submit form
                    form.submit();
                },
                onCancel: () => {
                    console.log('User cancelled booking');
                }
            });
        } else {
            // Fallback - just disable button and show loading
            console.log('Notification system not available, using fallback');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        }
    });
    
    // Add form validation with notifications
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', function() {
            if (!this.value.trim()) {
                if (window.Notify && typeof window.Notify.warning === 'function') {
                    window.Notify.warning('Vui lòng điền đầy đủ thông tin bắt buộc');
                }
            }
        });
    });
    
    // Dynamic validation based on authentication status
    const isAuthenticated = window.isAuthenticated || false;
    if (isAuthenticated) {
        // Remove required attribute from lead fields for authenticated users
        const leadFields = ['lead_name', 'lead_phone', 'lead_email'];
        leadFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.removeAttribute('required');
        }
    });
}

    // Add date validation
    const dateField = document.getElementById('schedule_date');
    if (dateField) {
        dateField.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate <= today) {
                if (window.Notify && typeof window.Notify.warning === 'function') {
                    window.Notify.warning('Vui lòng chọn ngày trong tương lai');
                }
                this.value = '';
            }
        });
    }
});