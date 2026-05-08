// Edit Appointment Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Form validation
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const date = document.getElementById('schedule_date').value;
        const time = document.getElementById('schedule_time').value;
        
        if (!date || !time) {
            showAlert('error', 'Vui lòng điền đầy đủ thông tin ngày và giờ');
            return;
        }
        
        // Check if date is in the future
        const selectedDate = new Date(date + ' ' + time);
        const now = new Date();
        
        if (selectedDate <= now) {
            showAlert('error', 'Ngày và giờ hẹn phải trong tương lai');
            return;
        }
        
        // Submit form
        submitForm();
    });
    
    function submitForm() {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        console.log('Submitting form data:', data);
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...';
        
        fetch(window.updateRoute || `/tenant/appointments/${window.viewingId}/update`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Update response:', data);
            
            if (data.success) {
                showAlert('success', data.message || 'Đã cập nhật lịch hẹn thành công');
                setTimeout(() => {
                    window.location.href = window.appointmentsRoute || '/tenant/appointments';
                }, 2000);
            } else {
                showAlert('error', data.message || 'Có lỗi xảy ra khi cập nhật');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi';
        });
    }
    
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            ${message}
        `;
        
        const container = document.querySelector('.edit-form-container');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
});
