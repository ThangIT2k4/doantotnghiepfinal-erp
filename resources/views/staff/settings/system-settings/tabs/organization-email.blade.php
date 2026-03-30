<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-envelope me-2"></i>
                    Cấu hình Email SMTP
                </h6>
            </div>
            <div class="card-body">
                <form id="emailSettingForm">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Lưu ý:</strong> Mật khẩu email sẽ được mã hóa tự động khi lưu. 
                        Nếu bạn không muốn thay đổi mật khẩu, hãy để trống trường mật khẩu.
                        <br><br>
                        <strong>Đối với Gmail:</strong> Bạn cần sử dụng <strong>App Password</strong> (không phải mật khẩu thông thường). 
                        Vui lòng tạo App Password tại: 
                        <a href="https://myaccount.google.com/apppasswords" target="_blank" class="alert-link">
                            https://myaccount.google.com/apppasswords
                        </a>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mail_host" class="form-label">SMTP Host</label>
                                <input type="text" name="mail_host" id="mail_host" class="form-control" 
                                       value="{{ $emailSetting->mail_host ?? '' }}" 
                                       placeholder="smtp.gmail.com" maxlength="255">
                                <div class="form-text">Ví dụ: smtp.gmail.com, smtp.mailtrap.io</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mail_port" class="form-label">SMTP Port</label>
                                <input type="number" name="mail_port" id="mail_port" class="form-control" 
                                       value="{{ $emailSetting->mail_port ?? '' }}" 
                                       placeholder="587" min="1" max="65535">
                                <div class="form-text">Thường là 587 (TLS) hoặc 465 (SSL)</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mail_encryption" class="form-label">Mã hóa</label>
                                <select name="mail_encryption" id="mail_encryption" class="form-select">
                                    <option value="">Không mã hóa</option>
                                    <option value="tls" {{ ($emailSetting->mail_encryption ?? '') == 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ ($emailSetting->mail_encryption ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mail_from_address" class="form-label">Địa chỉ Email gửi</label>
                                <input type="email" name="mail_from_address" id="mail_from_address" class="form-control" 
                                       value="{{ $emailSetting->mail_from_address ?? '' }}" 
                                       placeholder="noreply@example.com" maxlength="255">
                                <div class="form-text">Email sẽ hiển thị là người gửi</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mail_username" class="form-label">Tên đăng nhập SMTP</label>
                                <input type="text" name="mail_username" id="mail_username" class="form-control" 
                                       value="{{ $emailSetting->mail_username ?? '' }}" 
                                       placeholder="your-email@gmail.com" maxlength="255">
                                <div class="form-text">Thường là địa chỉ email của bạn</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mail_password" class="form-label">Mật khẩu SMTP</label>
                                <input type="password" name="mail_password" id="mail_password" class="form-control" 
                                       placeholder="Để trống nếu không muốn thay đổi" maxlength="255">
                                <div class="form-text">
                                    Để trống nếu không muốn thay đổi mật khẩu hiện tại.
                                    @if(($emailSetting->mail_host ?? '') && str_contains(strtolower($emailSetting->mail_host ?? ''), 'gmail'))
                                        <br><strong class="text-warning">Gmail:</strong> Sử dụng App Password (16 ký tự), không phải mật khẩu thông thường.
                                    @endif
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-secondary" onclick="testEmailConnection()">
                            <i class="fas fa-vial me-2"></i>Kiểm tra kết nối
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-spinner fa-spin d-none me-2" id="emailFormSpinner"></i>
                            <i class="fas fa-save me-2"></i>
                            <span id="emailFormSubmitText">Lưu cấu hình</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('emailSettingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    clearEmailFormValidation();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const spinner = document.getElementById('emailFormSpinner');
    const submitText = document.getElementById('emailFormSubmitText');
    
    spinner.classList.remove('d-none');
    submitText.textContent = 'Đang lưu...';
    submitBtn.disabled = true;
    
    fetch(`{{ route('staff.organization-email-settings.update') }}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Notify.success(data.message, 'Thành công!');
            // Clear password field after successful save
            document.getElementById('mail_password').value = '';
        } else {
            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    const input = document.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.parentNode.querySelector('.invalid-feedback');
                        if (feedback) {
                            feedback.textContent = data.errors[field][0];
                        }
                    }
                });
            }
            Notify.error(data.message || 'Có lỗi xảy ra khi lưu cấu hình email.', 'Lỗi!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Notify.error('Có lỗi xảy ra khi lưu cấu hình email.', 'Lỗi hệ thống');
    })
    .finally(() => {
        spinner.classList.add('d-none');
        submitText.textContent = 'Lưu cấu hình';
        submitBtn.disabled = false;
    });
});

function clearEmailFormValidation() {
    document.querySelectorAll('#emailSettingForm .is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('#emailSettingForm .invalid-feedback').forEach(el => {
        el.textContent = '';
    });
}

function testEmailConnection() {
    const form = document.getElementById('emailSettingForm');
    const formData = new FormData(form);
    
    // Remove _method field to avoid PUT method issue
    formData.delete('_method');
    formData.append('test_connection', '1');
    
    // If password is empty, try to get from existing settings (will be handled by backend)
    if (!formData.get('mail_password')) {
        // Backend will use existing password if available
    }
    
    Notify.info('Đang kiểm tra kết nối email...', 'Đang xử lý');
    
    fetch(`{{ route('staff.organization-email-settings.test-connection') }}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Notify.success(data.message || 'Kết nối email thành công!', 'Thành công!');
        } else {
            Notify.error(data.message || 'Kết nối email thất bại. Vui lòng kiểm tra lại cấu hình.', 'Lỗi!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Notify.error('Có lỗi xảy ra khi kiểm tra kết nối email.', 'Lỗi hệ thống');
    });
}
</script>
@endpush

