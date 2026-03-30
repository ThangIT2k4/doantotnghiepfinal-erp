<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-building me-2"></i>
            Thông tin tổ chức
        </h5>
    </div>
    <div class="card-body">
        <form id="organizationNameForm">
            @csrf
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="organizationName" class="form-label">
                            <i class="fas fa-building me-1"></i>
                            Tên tổ chức <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="organizationName" 
                            name="name" 
                            value="{{ $organization->name }}" 
                            required
                            minlength="2"
                            maxlength="255"
                            placeholder="Nhập tên tổ chức"
                        >
                        <div class="form-text">
                            Tên tổ chức sẽ được hiển thị trong email gửi cho khách hàng thay vì "ZoroRMS Team"
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    Lưu thay đổi
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    <i class="fas fa-undo me-1"></i>
                    Đặt lại
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('organizationNameForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Disable button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';
    
    const formData = new FormData(form);
    
    try {
        const response = await fetch('{{ route("staff.settings.organization.update-name") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            showAlert('success', data.message);
            
            // Update form value if needed
            if (data.data && data.data.name) {
                document.getElementById('organizationName').value = data.data.name;
            }
        } else {
            showAlert('danger', data.message || 'Có lỗi xảy ra khi cập nhật tên tổ chức');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('danger', 'Có lỗi xảy ra khi cập nhật tên tổ chức');
    } finally {
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

function resetForm() {
    document.getElementById('organizationName').value = '{{ $organization->name }}';
}

function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert after form
    const form = document.getElementById('organizationNameForm');
    form.parentNode.insertBefore(alertDiv, form.nextSibling);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>

