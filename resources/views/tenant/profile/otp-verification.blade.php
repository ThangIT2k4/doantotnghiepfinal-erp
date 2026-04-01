@extends('layouts.app')

@section('title', 'Xác thực Email OTP')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/profile.css') }}?v={{ time() }}">
<style>
    .otp-verification-container {
        max-width: 500px;
        margin: 0 auto;
        padding: 2rem 0;
    }
    .otp-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    .otp-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }
    .otp-header h1 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }
    .otp-header p {
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
    }
    .otp-body {
        padding: 2rem;
    }
    .otp-form-group {
        margin-bottom: 1.5rem;
    }
    .otp-form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #374151;
    }
    .otp-form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }
    .otp-form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .otp-input-group {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        margin: 1rem 0;
    }
    .otp-digit {
        width: 50px;
        height: 50px;
        text-align: center;
        font-size: 1.5rem;
        font-weight: bold;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        background: #f9fafb;
        transition: all 0.3s ease;
    }
    .otp-digit:focus {
        outline: none;
        border-color: #667eea;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .otp-digit.filled {
        border-color: #667eea;
        background: #fff;
    }
    .otp-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }
    .btn-otp {
        flex: 1;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
        display: inline-block;
    }
    .btn-otp-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-otp-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    .btn-otp-secondary {
        background: #f3f4f6;
        color: #374151;
        border: 2px solid #e5e7eb;
    }
    .btn-otp-secondary:hover {
        background: #e5e7eb;
    }
    .btn-otp:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }
    .otp-timer {
        text-align: center;
        margin: 1rem 0;
        color: #6b7280;
    }
    .otp-timer.active {
        color: #ef4444;
    }
    .otp-resend {
        text-align: center;
        margin-top: 1rem;
    }
    .otp-resend a {
        color: #667eea;
        text-decoration: none;
        font-weight: 500;
    }
    .otp-resend a:hover {
        text-decoration: underline;
    }
    .otp-resend.disabled {
        opacity: 0.5;
        pointer-events: none;
    }
    .alert-otp {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }
    .alert-otp-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    .alert-otp-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    .alert-otp-info {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }
    .otp-instructions {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    .otp-instructions h4 {
        margin: 0 0 0.5rem 0;
        color: #374151;
        font-size: 0.9rem;
    }
    .otp-instructions ul {
        margin: 0;
        padding-left: 1.2rem;
        color: #6b7280;
        font-size: 0.85rem;
    }
    .otp-instructions li {
        margin-bottom: 0.25rem;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="otp-verification-container">
        <div class="otp-card">
            <div class="otp-header">
                <h1><i class="fas fa-shield-alt me-2"></i>Xác thực Email</h1>
                <p>Nhập mã OTP đã được gửi đến email của bạn</p>
            </div>
            
            <div class="otp-body">
                <!-- Alert Messages -->
                <div id="alertContainer"></div>
                
                <!-- Instructions -->
                <div class="otp-instructions">
                    <h4><i class="fas fa-info-circle me-2"></i>Hướng dẫn:</h4>
                    <ul>
                        <li>Kiểm tra hộp thư email của bạn</li>
                        <li>Nhập mã 6 chữ số đã được gửi</li>
                        <li>Mã có hiệu lực trong 2 phút</li>
                        <li>Không chia sẻ mã này với ai khác</li>
                    </ul>
                </div>

                <!-- Email Display -->
                <div class="otp-form-group">
                    <label class="otp-form-label">
                        <i class="fas fa-envelope me-2"></i>Email đang xác thực:
                    </label>
                    <div class="otp-form-control" style="background: #f9fafb; cursor: not-allowed;">
                        <span id="verificationEmail">{{ $verificationEmail ?? $user->email ?? 'user@example.com' }}</span>
                    </div>
                    <div class="form-text mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        <span id="emailChangeInfo">Đang xác thực email mới để thay đổi email đăng nhập</span>
                    </div>
                </div>

                <!-- OTP Input -->
                <form id="otpVerificationForm">
                    @csrf
                    <div class="otp-form-group">
                        <label class="otp-form-label">
                            <i class="fas fa-key me-2"></i>Mã xác thực OTP:
                        </label>
                        <div class="otp-input-group">
                            <input type="text" class="otp-digit" maxlength="1" data-index="0" autocomplete="off">
                            <input type="text" class="otp-digit" maxlength="1" data-index="1" autocomplete="off">
                            <input type="text" class="otp-digit" maxlength="1" data-index="2" autocomplete="off">
                            <input type="text" class="otp-digit" maxlength="1" data-index="3" autocomplete="off">
                            <input type="text" class="otp-digit" maxlength="1" data-index="4" autocomplete="off">
                            <input type="text" class="otp-digit" maxlength="1" data-index="5" autocomplete="off">
                        </div>
                        <input type="hidden" id="otpCode" name="otp_code">
                        <input type="hidden" id="email" name="email" value="{{ $user->email ?? '' }}">
                    </div>

                    <!-- Timer -->
                    <div class="otp-timer" id="timer">
                        <i class="fas fa-clock me-2"></i>
                        <span id="timerText">Mã OTP chưa được gửi</span>
                    </div>

                    <!-- Actions -->
                    <div class="otp-actions">
                        <button type="button" class="btn-otp btn-otp-secondary" onclick="goBack()">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại
                        </button>
                        <button type="submit" class="btn-otp btn-otp-primary" id="verifyBtn" disabled>
                            <i class="fas fa-check me-2"></i>Xác thực
                        </button>
                    </div>
                </form>

                <!-- Resend OTP -->
                <div class="otp-resend" id="resendContainer">
                    <p>Không nhận được mã? 
                        <a href="#" id="resendLink" onclick="resendOtp(event)">
                            <i class="fas fa-redo me-1"></i>Gửi lại mã OTP
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let timerInterval;
let timeLeft = 0;
let canResend = false;

// Initialize OTP inputs
document.addEventListener('DOMContentLoaded', function() {
    const otpInputs = document.querySelectorAll('.otp-digit');
    
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            const value = e.target.value;
            
            // Only allow numbers
            if (!/^\d$/.test(value)) {
                e.target.value = '';
                return;
            }
            
            // Move to next input
            if (value && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
            
            // Update hidden input
            updateOtpCode();
            
            // Check if all fields are filled
            checkFormValidity();
        });
        
        input.addEventListener('keydown', function(e) {
            // Handle backspace
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }
            
            // Handle paste
            if (e.key === 'v' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                handlePaste(e);
            }
        });
        
        input.addEventListener('paste', handlePaste);
    });
    
    // Form submission
    document.getElementById('otpVerificationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        verifyOtp();
    });
    
    // Check if we have a valid OTP status
    checkOtpStatus();
    
    // Check for pending email verification from sessionStorage
    const pendingEmail = sessionStorage.getItem('pendingEmailVerification');
    if (pendingEmail) {
        document.getElementById('verificationEmail').textContent = pendingEmail;
        document.getElementById('email').value = pendingEmail;
        document.getElementById('emailChangeInfo').textContent = 'Đang xác thực email mới để thay đổi email đăng nhập';
        sessionStorage.removeItem('pendingEmailVerification');
    } else {
        document.getElementById('emailChangeInfo').textContent = 'Đang xác thực email hiện tại';
    }
});

function updateOtpCode() {
    const otpInputs = document.querySelectorAll('.otp-digit');
    const otpCode = Array.from(otpInputs).map(input => input.value).join('');
    document.getElementById('otpCode').value = otpCode;
}

function checkFormValidity() {
    const otpCode = document.getElementById('otpCode').value;
    const verifyBtn = document.getElementById('verifyBtn');
    
    if (otpCode.length === 6) {
        verifyBtn.disabled = false;
        document.querySelectorAll('.otp-digit').forEach(input => {
            input.classList.add('filled');
        });
    } else {
        verifyBtn.disabled = true;
        document.querySelectorAll('.otp-digit').forEach(input => {
            input.classList.remove('filled');
        });
    }
}

function handlePaste(e) {
    e.preventDefault();
    const pasteData = (e.clipboardData || window.clipboardData).getData('text');
    const cleanData = pasteData.replace(/\D/g, '').slice(0, 6);
    
    const otpInputs = document.querySelectorAll('.otp-digit');
    cleanData.split('').forEach((digit, index) => {
        if (otpInputs[index]) {
            otpInputs[index].value = digit;
        }
    });
    
    updateOtpCode();
    checkFormValidity();
    
    // Focus last filled input
    const lastFilledIndex = Math.min(cleanData.length - 1, otpInputs.length - 1);
    if (otpInputs[lastFilledIndex]) {
        otpInputs[lastFilledIndex].focus();
    }
}

function verifyOtp() {
    const form = document.getElementById('otpVerificationForm');
    const formData = new FormData(form);
    const verifyBtn = document.getElementById('verifyBtn');
    
    verifyBtn.disabled = true;
    verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xác thực...';
    
    fetch('{{ route("tenant.profile.otp.verify") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                window.location.href = '{{ route("tenant.profile.edit") }}';
            }, 2000);
        } else {
            showAlert('error', data.message);
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="fas fa-check me-2"></i>Xác thực';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Có lỗi xảy ra khi xác thực OTP. Vui lòng thử lại.');
        verifyBtn.disabled = false;
        verifyBtn.innerHTML = '<i class="fas fa-check me-2"></i>Xác thực';
    });
}

function resendOtp(e) {
    e.preventDefault();
    
    if (!canResend) {
        showAlert('error', 'Vui lòng đợi trước khi gửi lại mã OTP.');
        return;
    }
    
    const email = document.getElementById('email').value;
    const resendLink = document.getElementById('resendLink');
    
    resendLink.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang gửi...';
    resendLink.style.pointerEvents = 'none';
    
    fetch('{{ route("tenant.profile.otp.resend") }}', {
        method: 'POST',
        body: new FormData(Object.assign(document.createElement('form'), {
            innerHTML: `
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="email" value="${email}">
            `
        })),
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            startTimer(600); // 10 minutes
            canResend = false;
        } else {
            showAlert('error', data.message);
        }
        
        resendLink.innerHTML = '<i class="fas fa-redo me-1"></i>Gửi lại mã OTP';
        resendLink.style.pointerEvents = 'auto';
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Có lỗi xảy ra khi gửi lại mã OTP.');
        resendLink.innerHTML = '<i class="fas fa-redo me-1"></i>Gửi lại mã OTP';
        resendLink.style.pointerEvents = 'auto';
    });
}

function checkOtpStatus() {
    fetch('{{ route("tenant.profile.otp.status") }}', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.has_valid_otp && data.remaining_seconds > 0) {
            startTimer(data.remaining_seconds);
        } else {
            showAlert('info', 'Chưa có mã OTP hợp lệ. Vui lòng yêu cầu gửi mã mới.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function startTimer(seconds) {
    clearInterval(timerInterval);
    timeLeft = Math.max(0, Math.floor(Number(seconds)));
    canResend = false;
    
    const timerElement = document.getElementById('timer');
    const timerText = document.getElementById('timerText');
    const resendContainer = document.getElementById('resendContainer');
    
    timerElement.classList.add('active');
    resendContainer.classList.add('disabled');

    function tick() {
        const total = Math.max(0, Math.floor(timeLeft));
        const minutes = Math.floor(total / 60);
        const secs = total % 60;

        if (minutes > 0) {
            timerText.textContent = `Mã OTP còn hiệu lực: ${minutes} phút ${secs.toString().padStart(2, '0')} giây`;
        } else {
            timerText.textContent = `Mã OTP còn hiệu lực: ${secs} giây`;
        }

        if (total <= 0) {
            clearInterval(timerInterval);
            timerText.textContent = 'Mã OTP đã hết hạn';
            timerElement.classList.remove('active');
            resendContainer.classList.remove('disabled');
            canResend = true;
            return;
        }
        timeLeft--;
    }

    tick();
    timerInterval = setInterval(tick, 1000);
}

function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer');
    const alertClass = `alert-otp-${type}`;
    const iconClass = type === 'success' ? 'fa-check-circle' : 
                     type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    
    alertContainer.innerHTML = `
        <div class="alert-otp ${alertClass}">
            <i class="fas ${iconClass} me-2"></i>
            ${message}
        </div>
    `;
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        alertContainer.innerHTML = '';
    }, 5000);
}

function goBack() {
    window.history.back();
}
</script>
@endpush
