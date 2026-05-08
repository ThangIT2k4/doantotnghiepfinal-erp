/**
 * Viewings Management JavaScript
 * Handles form interactions, AJAX calls, and validations
 */

$(document).ready(function() {
    // Initialize form interactions
    initializeCustomerTypeToggle();
    initializePropertyUnitLoading();
    initializeFormValidation();
    initializeDateTimePicker();
    initializeFormSubmissionHandling();
});

/**
 * Toggle between Lead and Tenant sections
 */
function initializeCustomerTypeToggle() {
    $('input[name="customer_type"]').on('change', function() {
        const customerType = $(this).val();
        
        if (customerType === 'lead') {
            $('#leadSection').show();
            $('#tenantSection').hide();
            
            // Clear tenant fields
            $('#tenant_id').val('');
        } else if (customerType === 'tenant') {
            $('#leadSection').hide();
            $('#tenantSection').show();
            
            // Clear lead fields
            $('#lead_id').val('');
            $('#lead_name').val('');
            $('#lead_phone').val('');
            $('#lead_email').val('');
        }
    });
}

/**
 * Load units when property is selected
 */
function initializePropertyUnitLoading() {
    $('#property_id').on('change', function() {
        const propertyId = $(this).val();
        
        if (propertyId) {
            loadUnits(propertyId);
        } else {
            $('#unit_id').html('<option value="">Chọn bất động sản trước</option>').prop('disabled', true);
        }
    });
}

/**
 * Load units for a property via AJAX
 */
function loadUnits(propertyId) {
    const unitSelect = $('#unit_id');
    
    // Show loading state
    unitSelect.html('<option value="">Đang tải...</option>').prop('disabled', true);
    
    $.ajax({
        url: '/agent/viewings/get-units',
        method: 'GET',
        data: {
            property_id: propertyId
        },
        success: function(response) {
            let options = '<option value="">Chọn phòng</option>';
            
            // Get current selected unit (for edit form)
            const currentUnitId = unitSelect.data('current-unit-id');
            const currentUnitCode = unitSelect.data('current-unit-code');
            
            if (response.length > 0) {
                response.forEach(function(unit) {
                    const selected = (currentUnitId && unit.id == currentUnitId) ? 'selected' : '';
                    options += `<option value="${unit.id}" ${selected}>${unit.code}</option>`;
                });
                
                // If current unit is not in available units but exists, add it
                if (currentUnitId && currentUnitCode && !response.find(u => u.id == currentUnitId)) {
                    options += `<option value="${currentUnitId}" selected>${currentUnitCode} (Hiện tại)</option>`;
                }
            } else {
                // If no available units but current unit exists, show it
                if (currentUnitId && currentUnitCode) {
                    options += `<option value="${currentUnitId}" selected>${currentUnitCode} (Hiện tại)</option>`;
                } else {
                    options = '<option value="">Không có phòng nào khả dụng</option>';
                }
            }
            
            unitSelect.html(options).prop('disabled', false);
        },
        error: function(xhr, status, error) {
            console.error('Error loading units:', error);
            unitSelect.html('<option value="">Lỗi khi tải danh sách phòng</option>').prop('disabled', true);
            
            // Show error message using unified notification system
            if (typeof window.Notify !== 'undefined') {
                window.Notify.error('Lỗi khi tải danh sách phòng. Vui lòng thử lại.');
            } else {
                showAlert('Lỗi khi tải danh sách phòng. Vui lòng thử lại.', 'error');
            }
        }
    });
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const form = $('#viewingForm');
    
    form.on('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
    });
    
    // Real-time validation
    $('#lead_name, #lead_phone').on('blur', function() {
        validateLeadFields();
    });
    
    $('#tenant_id').on('change', function() {
        validateTenantField();
    });
}

/**
 * Validate the entire form
 */
function validateForm() {
    let isValid = true;
    
    // Validate customer type
    const customerType = $('input[name="customer_type"]:checked').val();
    if (!customerType) {
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Vui lòng chọn loại khách hàng.');
        } else {
            showAlert('Vui lòng chọn loại khách hàng.', 'error');
        }
        isValid = false;
    }
    
    // Validate based on customer type
    if (customerType === 'lead') {
        if (!validateLeadFields()) {
            isValid = false;
        }
    } else if (customerType === 'tenant') {
        if (!validateTenantField()) {
            isValid = false;
        }
    }
    
    // Validate required fields
    if (!$('#property_id').val()) {
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Vui lòng chọn bất động sản.');
        } else {
            showAlert('Vui lòng chọn bất động sản.', 'error');
        }
        isValid = false;
    }
    
    if (!$('#unit_id').val()) {
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Vui lòng chọn phòng.');
        } else {
            showAlert('Vui lòng chọn phòng.', 'error');
        }
        isValid = false;
    }
    
    if (!$('#schedule_at').val()) {
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Vui lòng chọn thời gian hẹn.');
        } else {
            showAlert('Vui lòng chọn thời gian hẹn.', 'error');
        }
        isValid = false;
    }
    
    // Validate schedule time is in the future
    const scheduleTime = new Date($('#schedule_at').val());
    const now = new Date();
    
    if (scheduleTime <= now) {
        if (typeof window.Notify !== 'undefined') {
            window.Notify.error('Thời gian hẹn phải là thời gian trong tương lai.');
        } else {
            showAlert('Thời gian hẹn phải là thời gian trong tương lai.', 'error');
        }
        isValid = false;
    }
    
    return isValid;
}

/**
 * Validate lead fields
 */
function validateLeadFields() {
    const leadName = $('#lead_name').val().trim();
    const leadPhone = $('#lead_phone').val().trim();
    
    if (!leadName) {
        showFieldError('lead_name', 'Vui lòng nhập tên khách hàng.');
        return false;
    }
    
    if (!leadPhone) {
        showFieldError('lead_phone', 'Vui lòng nhập số điện thoại.');
        return false;
    }
    
    // Validate phone format (basic)
    const phoneRegex = /^[0-9+\-\s()]{10,15}$/;
    if (!phoneRegex.test(leadPhone)) {
        showFieldError('lead_phone', 'Số điện thoại không hợp lệ.');
        return false;
    }
    
    clearFieldError('lead_name');
    clearFieldError('lead_phone');
    return true;
}

/**
 * Validate tenant field
 */
function validateTenantField() {
    const tenantId = $('#tenant_id').val();
    
    if (!tenantId) {
        showFieldError('tenant_id', 'Vui lòng chọn khách thuê.');
        return false;
    }
    
    clearFieldError('tenant_id');
    return true;
}

/**
 * Show field error
 */
function showFieldError(fieldId, message) {
    const field = $('#' + fieldId);
    field.addClass('is-invalid');
    
    // Remove existing error message
    field.siblings('.invalid-feedback').remove();
    
    // Add new error message
    field.after(`<div class="invalid-feedback">${message}</div>`);
}

/**
 * Clear field error
 */
function clearFieldError(fieldId) {
    const field = $('#' + fieldId);
    field.removeClass('is-invalid');
    field.siblings('.invalid-feedback').remove();
}

/**
 * Initialize datetime picker
 */
function initializeDateTimePicker() {
    // Set minimum date to today
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    const minDate = tomorrow.toISOString().slice(0, 16);
    $('#schedule_at').attr('min', minDate);
}

/**
 * Show alert message using unified notification system
 */
function showAlert(message, type = 'info') {
    // Use the unified notification system
    if (typeof window.Notify !== 'undefined') {
        const notificationType = type === 'error' ? 'error' : (type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'info'));
        const title = type === 'error' ? 'Lỗi' : (type === 'success' ? 'Thành công' : (type === 'warning' ? 'Cảnh báo' : 'Thông báo'));
        
        window.Notify.toast({
            title: title,
            message: message,
            type: notificationType,
            duration: type === 'error' ? 8000 : (type === 'success' ? 5000 : 6000)
        });
    } else {
        // Fallback to old alert system if Notify is not available
        const alertClass = type === 'error' ? 'alert-danger' : (type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-info'));
        const icon = type === 'error' ? 'fas fa-exclamation-triangle' : (type === 'success' ? 'fas fa-check-circle' : (type === 'warning' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle'));
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="${icon} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Remove existing alerts
        $('.alert').remove();
        
        // Add new alert at the top of the form
        $('#viewingForm').prepend(alertHtml);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
}

/**
 * Handle lead selection change
 */
$('#lead_id').on('change', function() {
    const leadId = $(this).val();
    
    if (leadId) {
        // Find the selected lead and populate fields
        const selectedOption = $(this).find('option:selected');
        const leadText = selectedOption.text();
        
        // Extract name and phone from option text (format: "Name - Phone")
        const parts = leadText.split(' - ');
        if (parts.length >= 2) {
            $('#lead_name').val(parts[0].trim());
            $('#lead_phone').val(parts[1].trim());
        }
    }
});

/**
 * Confirmation dialog for delete action using unified notification system
 */
function confirmDelete(viewingId, viewingTitle) {
    if (typeof window.Notify !== 'undefined') {
        // Use unified notification system for confirmation
        window.Notify.confirm({
            title: 'Xác nhận xóa lịch hẹn',
            message: `Bạn có chắc chắn muốn xóa lịch hẹn "${viewingTitle}"?`,
            details: 'Hành động này không thể hoàn tác.',
            type: 'danger',
            confirmText: 'Xóa',
            cancelText: 'Hủy',
            onConfirm: function() {
                // Create and submit delete form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/agent/viewings/${viewingId}`;
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = $('meta[name="csrf-token"]').attr('content');
                
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                
                form.appendChild(csrfToken);
                form.appendChild(methodField);
                document.body.appendChild(form);
                form.submit();
            },
            onCancel: function() {
                // User cancelled, no action needed
            }
        });
    } else {
        // Fallback to native confirm dialog
        if (confirm(`Bạn có chắc chắn muốn xóa lịch hẹn "${viewingTitle}"?\n\nHành động này không thể hoàn tác.`)) {
            // Create and submit delete form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/agent/viewings/${viewingId}`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = $('meta[name="csrf-token"]').attr('content');
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            
            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        }
    }
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Get status badge class
 */
function getStatusBadgeClass(status) {
    const statusClasses = {
        'requested': 'badge-warning',
        'confirmed': 'badge-info',
        'done': 'badge-success',
        'no_show': 'badge-danger',
        'cancelled': 'badge-secondary'
    };
    
    return statusClasses[status] || 'badge-light';
}

/**
 * Get status text in Vietnamese
 */
function getStatusText(status) {
    const statusTexts = {
        'requested': 'Chờ xác nhận',
        'confirmed': 'Đã xác nhận',
        'done': 'Hoàn thành',
        'no_show': 'Không đến',
        'cancelled': 'Đã hủy'
    };
    
    return statusTexts[status] || 'Không xác định';
}

/**
 * Initialize form submission handling
 */
function initializeFormSubmissionHandling() {
    // Handle form submission success/error messages from server
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');
    
    if (success && typeof window.Notify !== 'undefined') {
        let message = 'Thao tác thành công!';
        
        // Customize message based on success type
        switch(success) {
            case 'created':
                message = 'Lịch hẹn đã được tạo thành công!';
                break;
            case 'updated':
                message = 'Lịch hẹn đã được cập nhật thành công!';
                break;
            case 'deleted':
                message = 'Lịch hẹn đã được xóa thành công!';
                break;
        }
        
        window.Notify.success(message);
        
        // Clean URL by removing success parameter
        const newUrl = window.location.pathname + window.location.search.replace(/[?&]success=[^&]*/, '').replace(/^&/, '?');
        window.history.replaceState({}, document.title, newUrl);
    }
    
    if (error && typeof window.Notify !== 'undefined') {
        let message = 'Đã xảy ra lỗi!';
        
        // Customize message based on error type
        switch(error) {
            case 'validation':
                message = 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.';
                break;
            case 'not_found':
                message = 'Không tìm thấy lịch hẹn.';
                break;
            case 'permission':
                message = 'Bạn không có quyền thực hiện thao tác này.';
                break;
        }
        
        window.Notify.error(message);
        
        // Clean URL by removing error parameter
        const newUrl = window.location.pathname + window.location.search.replace(/[?&]error=[^&]*/, '').replace(/^&/, '?');
        window.history.replaceState({}, document.title, newUrl);
    }
}

/**
 * Show success notification
 */
function showSuccess(message, title = 'Thành công!') {
    if (typeof window.Notify !== 'undefined') {
        window.Notify.success(message, title);
    } else {
        showAlert(message, 'success');
    }
}

/**
 * Show error notification
 */
function showError(message, title = 'Lỗi!') {
    if (typeof window.Notify !== 'undefined') {
        window.Notify.error(message, title);
    } else {
        showAlert(message, 'error');
    }
}

/**
 * Show warning notification
 */
function showWarning(message, title = 'Cảnh báo!') {
    if (typeof window.Notify !== 'undefined') {
        window.Notify.warning(message, title);
    } else {
        showAlert(message, 'warning');
    }
}

/**
 * Show info notification
 */
function showInfo(message, title = 'Thông tin') {
    if (typeof window.Notify !== 'undefined') {
        window.Notify.info(message, title);
    } else {
        showAlert(message, 'info');
    }
}

/**
 * Test notification system integration
 * This function can be called from browser console to test notifications
 */
function testViewingNotifications() {
    if (typeof window.Notify === 'undefined') {
        console.error('Notification system not loaded!');
        return;
    }
    
    console.log('Testing viewing notifications...');
    
    // Test success notification
    setTimeout(() => {
        showSuccess('Lịch hẹn đã được tạo thành công!', 'Thành công!');
    }, 500);
    
    // Test error notification
    setTimeout(() => {
        showError('Lỗi khi tải danh sách phòng. Vui lòng thử lại.', 'Lỗi!');
    }, 2000);
    
    // Test warning notification
    setTimeout(() => {
        showWarning('Thời gian hẹn phải là thời gian trong tương lai.', 'Cảnh báo!');
    }, 3500);
    
    // Test info notification
    setTimeout(() => {
        showInfo('Đang tải danh sách phòng...', 'Thông tin');
    }, 5000);
    
    // Test confirmation dialog
    setTimeout(() => {
        window.Notify.confirm({
            title: 'Xác nhận xóa lịch hẹn',
            message: 'Bạn có chắc chắn muốn xóa lịch hẹn "Test Viewing"?',
            details: 'Hành động này không thể hoàn tác.',
            type: 'danger',
            confirmText: 'Xóa',
            cancelText: 'Hủy',
            onConfirm: function() {
                showSuccess('Lịch hẹn đã được xóa thành công!');
            },
            onCancel: function() {
                showInfo('Đã hủy thao tác xóa.');
            }
        });
    }, 7000);
    
    console.log('Notification tests completed. Check the notifications in the top-right corner.');
}

// Make test function available globally for debugging
window.testViewingNotifications = testViewingNotifications;
