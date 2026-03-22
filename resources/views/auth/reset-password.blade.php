@extends('layouts.auth')

@section('title', 'Đặt lại mật khẩu')

@section('content')
<div class="auth-page-wrapper">
    <div class="circle circle1"></div>
    <div class="circle circle2"></div>

    <div class="container">
        <div class="header">
            <h1>Đặt lại mật khẩu</h1>
            <p>Tạo mật khẩu mới cho tài khoản của bạn</p>
        </div>

        <div id="alertContainer"></div>

        <div class="email-display">
            <i class="fas fa-envelope me-2"></i>
            <span id="resetEmail">{{ $email ?? 'user@example.com' }}</span>
        </div>

        <form id="resetPasswordForm" method="POST" action="{{ route('password.reset.submit') }}" novalidate>
            @csrf
            <input type="hidden" name="email" value="{{ $email ?? '' }}">
            <input type="hidden" name="otp_code" value="{{ $otpCode ?? '' }}">

            <div class="form-group">
                <label for="password">Mật khẩu mới</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Nhập mật khẩu mới (tối thiểu 8 ký tự)"
                        required
                        minlength="8"
                        autocomplete="new-password"
                    >
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    <div class="check-icon">✓</div>
                </div>
                <x-form.error for="password" />
            </div>

            <div class="form-group">
                <label for="password_confirmation">Xác nhận mật khẩu</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        placeholder="Nhập lại mật khẩu mới"
                        required
                        autocomplete="new-password"
                    >
                    <i class="fas fa-eye password-toggle" id="togglePasswordConfirm"></i>
                    <div class="check-icon">✓</div>
                </div>
            </div>

            <button type="submit" class="signup-btn">
                <i class="fas fa-key me-2"></i>Đặt lại mật khẩu
            </button>
        </form>

        <div class="footer">
            <a href="{{ route('login') }}">
                <i class="fas fa-arrow-left me-2"></i>Quay lại đăng nhập
            </a>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap');

.auth-page-wrapper {
    --text-main: #f3fbff;
    --text-muted: rgba(226, 242, 255, 0.8);
    --glass-bg: rgba(248, 255, 255, 0.12);
    --glass-border: rgba(255, 255, 255, 0.32);
    --input-bg: rgba(255, 255, 255, 0.14);
    --input-border: rgba(255, 255, 255, 0.34);
    --accent-blue: #40dbff;
    --accent-green: #4df7c7;
    --accent-deep: #052334;
    font-family: 'Plus Jakarta Sans', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background:
        radial-gradient(circle at 14% 16%, rgba(79, 203, 255, 0.48) 0%, rgba(79, 203, 255, 0) 38%),
        radial-gradient(circle at 84% 8%, rgba(81, 255, 197, 0.32) 0%, rgba(81, 255, 197, 0) 32%),
        linear-gradient(140deg, #071223 0%, #0f1d34 48%, #101b2f 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    overflow: hidden;
    padding: 2rem;
}

.auth-page-wrapper,
.auth-page-wrapper * {
    box-sizing: border-box;
}

.auth-page-wrapper::before,
.auth-page-wrapper::after {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.auth-page-wrapper::before {
    background: repeating-linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.04) 0px,
        rgba(255, 255, 255, 0.04) 1px,
        transparent 1px,
        transparent 26px
    );
    opacity: 0.45;
}

.auth-page-wrapper::after {
    background: linear-gradient(0deg, rgba(5, 12, 24, 0.42), rgba(5, 12, 24, 0));
}

.auth-page-wrapper .circle {
    position: absolute;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.28);
    box-shadow: inset 0 0 40px rgba(255, 255, 255, 0.22);
}

.auth-page-wrapper .circle1 {
    width: 380px;
    height: 380px;
    bottom: -170px;
    left: -130px;
    background: radial-gradient(circle at 30% 30%, rgba(95, 229, 255, 0.45), rgba(95, 229, 255, 0.03) 70%);
    animation: floatOne 14s ease-in-out infinite;
}

.auth-page-wrapper .circle2 {
    width: 290px;
    height: 290px;
    top: -120px;
    right: -70px;
    background: radial-gradient(circle at 40% 20%, rgba(90, 255, 206, 0.45), rgba(90, 255, 206, 0.03) 70%);
    animation: floatTwo 12s ease-in-out infinite;
}

.auth-page-wrapper .container {
    width: 100%;
    max-width: 680px;
    padding: 52px 52px 42px;
    border-radius: 28px;
    border: 1px solid var(--glass-border);
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.2), var(--glass-bg));
    backdrop-filter: blur(20px) saturate(140%);
    -webkit-backdrop-filter: blur(20px) saturate(140%);
    box-shadow: 0 26px 58px rgba(3, 8, 19, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.35);
    position: relative;
    z-index: 1;
    overflow: hidden;
    animation: fadeSlideUp 0.65s ease both;
}

.auth-page-wrapper .container::before {
    content: '';
    position: absolute;
    top: -180px;
    right: -100px;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.24), rgba(255, 255, 255, 0) 72%);
    pointer-events: none;
}

.auth-page-wrapper .header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-page-wrapper .header h1 {
    color: var(--text-main);
    font-family: 'Space Grotesk', 'Plus Jakarta Sans', sans-serif;
    font-size: 2.15rem;
    letter-spacing: 0.4px;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 6px 24px rgba(4, 13, 28, 0.4);
}

.auth-page-wrapper .header p {
    color: var(--text-muted);
    font-size: 0.95rem;
    font-weight: 500;
}

.auth-page-wrapper .email-display {
    background: rgba(255, 255, 255, 0.11);
    border: 1px solid rgba(255, 255, 255, 0.28);
    border-radius: 14px;
    padding: 14px 16px;
    text-align: center;
    margin-bottom: 24px;
    font-weight: 600;
    color: #e4f3ff;
    font-size: 0.93rem;
    display: flex;
    justify-content: center;
    align-items: center;
    word-break: break-all;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.24);
}

.auth-page-wrapper .google-btn {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid rgba(255, 255, 255, 0.32);
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.14);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    cursor: pointer;
    transition: transform 0.25s ease, border-color 0.25s ease, background 0.25s ease;
    margin-bottom: 26px;
    font-size: 0.95rem;
    color: #e8f6ff;
    text-decoration: none;
    font-weight: 600;
}

.auth-page-wrapper .google-btn:hover {
    border-color: rgba(143, 251, 255, 0.78);
    background: rgba(255, 255, 255, 0.21);
    transform: translateY(-2px);
}

.auth-page-wrapper .google-icon {
    width: 20px;
    height: 20px;
    border-radius: 5px;
    background: linear-gradient(45deg, #4285f4, #34a853, #fbbc05, #ea4335);
    box-shadow: 0 4px 10px rgba(1, 9, 23, 0.35);
}

.auth-page-wrapper .divider {
    text-align: center;
    margin: 26px 0 24px;
    position: relative;
}

.auth-page-wrapper .divider::before,
.auth-page-wrapper .divider::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 39%;
    height: 1px;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.08), rgba(194, 235, 255, 0.55));
}

.auth-page-wrapper .divider::before {
    left: 0;
}

.auth-page-wrapper .divider::after {
    right: 0;
    transform: rotate(180deg);
}

.auth-page-wrapper .divider span {
    color: rgba(231, 247, 255, 0.75);
    font-size: 0.85rem;
    background: rgba(11, 27, 45, 0.55);
    border: 1px solid rgba(255, 255, 255, 0.17);
    padding: 4px 12px;
    border-radius: 999px;
    position: relative;
}

.auth-page-wrapper .form-group {
    margin-bottom: 20px;
    position: relative;
}

.auth-page-wrapper .form-group label {
    display: block;
    color: rgba(228, 245, 255, 0.9);
    font-size: 0.82rem;
    margin-bottom: 8px;
    font-weight: 600;
    letter-spacing: 0.2px;
}

.auth-page-wrapper .input-wrapper {
    position: relative;
}

.auth-page-wrapper .form-group input {
    width: 100%;
    padding: 14px 46px 14px 16px;
    border: 1.5px solid var(--input-border);
    border-radius: 13px;
    font-size: 0.95rem;
    transition: border-color 0.25s ease, background 0.25s ease, box-shadow 0.25s ease;
    outline: none;
    background: var(--input-bg);
    color: #ffffff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25), 0 10px 20px rgba(6, 14, 28, 0.16);
}

.auth-page-wrapper .form-group input::placeholder {
    color: rgba(220, 238, 250, 0.62);
}

.auth-page-wrapper .form-group input:focus {
    border-color: rgba(122, 251, 255, 0.95);
    background: rgba(255, 255, 255, 0.22);
    box-shadow: 0 0 0 4px rgba(85, 244, 213, 0.14), inset 0 1px 0 rgba(255, 255, 255, 0.32);
}

.auth-page-wrapper .check-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    background: linear-gradient(135deg, #5cf4d0, #45d8ff);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #053046;
    font-size: 11px;
    font-weight: 800;
    opacity: 0;
    transition: opacity 0.25s;
}

.auth-page-wrapper .form-group input:valid ~ .check-icon {
    opacity: 1;
}

.auth-page-wrapper .password-toggle {
    position: absolute;
    right: 44px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(222, 242, 255, 0.76);
    cursor: pointer;
    font-size: 0.88rem;
    z-index: 2;
    transition: color 0.25s;
}

.auth-page-wrapper .password-toggle:hover {
    color: #93f9ff;
}

.auth-page-wrapper .form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    gap: 10px;
}

.auth-page-wrapper .checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 0.88rem;
    color: rgba(230, 246, 255, 0.86);
    user-select: none;
}

.auth-page-wrapper .checkbox-label input[type="checkbox"] {
    width: 17px;
    height: 17px;
    margin: 0;
    cursor: pointer;
    accent-color: #57e5ff;
}

.auth-page-wrapper .forgot-link {
    color: #8defff;
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 600;
    transition: color 0.25s ease;
}

.auth-page-wrapper .forgot-link:hover {
    color: #55ffd2;
}

.auth-page-wrapper .signup-btn {
    width: 100%;
    padding: 15px 18px;
    background: linear-gradient(115deg, rgba(71, 236, 255, 0.95) 0%, rgba(61, 201, 255, 0.92) 50%, rgba(74, 245, 189, 0.94) 100%);
    color: var(--accent-deep);
    border: none;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 800;
    cursor: pointer;
    transition: transform 0.25s ease, box-shadow 0.25s ease, filter 0.25s ease;
    margin-top: 12px;
    box-shadow: 0 14px 30px rgba(43, 208, 245, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.auth-page-wrapper .signup-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(53, 220, 242, 0.36);
    filter: brightness(1.03);
}

.auth-page-wrapper .signup-btn:active {
    transform: translateY(0);
}

.auth-page-wrapper .footer {
    text-align: center;
    margin-top: 22px;
    color: rgba(228, 245, 255, 0.74);
    font-size: 0.92rem;
}

.auth-page-wrapper .footer a {
    color: #b9ecff;
    text-decoration: none;
    font-weight: 700;
    transition: color 0.25s ease;
    display: inline-flex;
    align-items: center;
}

.auth-page-wrapper .footer a:hover {
    color: var(--accent-green);
}

.auth-page-wrapper .text-danger {
    color: #ffd4dd;
    font-size: 0.78rem;
    margin-top: 7px;
    display: block;
    font-weight: 600;
}

.auth-page-wrapper #alertContainer {
    margin-bottom: 16px;
}

.auth-page-wrapper .alert {
    padding: 12px 14px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid;
}

.auth-page-wrapper .alert-success {
    background: rgba(22, 163, 74, 0.2);
    color: #d6ffe7;
    border-color: rgba(74, 222, 128, 0.42);
}

.auth-page-wrapper .alert-error {
    background: rgba(239, 68, 68, 0.22);
    color: #ffe1e1;
    border-color: rgba(248, 113, 113, 0.5);
}

.auth-page-wrapper .alert-info {
    background: rgba(56, 189, 248, 0.2);
    color: #d8f4ff;
    border-color: rgba(125, 211, 252, 0.5);
}

@keyframes floatOne {
    0%,
    100% {
        transform: translate3d(0, 0, 0);
    }
    50% {
        transform: translate3d(26px, -16px, 0);
    }
}

@keyframes floatTwo {
    0%,
    100% {
        transform: translate3d(0, 0, 0);
    }
    50% {
        transform: translate3d(-20px, 14px, 0);
    }
}

@keyframes fadeSlideUp {
    from {
        opacity: 0;
        transform: translateY(18px) scale(0.985);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 768px) {
    .auth-page-wrapper {
        padding: 1.25rem;
    }

    .auth-page-wrapper .container {
        padding: 42px 26px 34px;
        border-radius: 22px;
    }

    .auth-page-wrapper .header h1 {
        font-size: 1.82rem;
    }
}

@media (max-width: 600px) {
    .auth-page-wrapper .form-options {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .auth-page-wrapper {
        padding: 0.9rem;
    }

    .auth-page-wrapper .container {
        padding: 36px 18px 28px;
    }

    .auth-page-wrapper .header h1 {
        font-size: 1.56rem;
    }

    .auth-page-wrapper .header p {
        font-size: 0.88rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .auth-page-wrapper .circle1,
    .auth-page-wrapper .circle2,
    .auth-page-wrapper .container,
    .auth-page-wrapper .google-btn,
    .auth-page-wrapper .signup-btn {
        animation: none;
        transition: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirmation');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
    
    if (togglePasswordConfirm && passwordConfirmInput) {
        togglePasswordConfirm.addEventListener('click', function() {
            const type = passwordConfirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordConfirmInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
    
    // Validate password match
    if (passwordInput && passwordConfirmInput) {
        passwordConfirmInput.addEventListener('input', function() {
            if (this.value && this.value !== passwordInput.value) {
                this.setCustomValidity('Mật khẩu không khớp');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Handle form submission with AJAX
    const form = document.getElementById('resetPasswordForm');
    const alertContainer = document.getElementById('alertContainer');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    function showAlert(message, type = 'success') {
        if (typeof window.authGlassToast === 'function') {
            window.authGlassToast(type, message, 5000);
            return;
        }

        alertContainer.innerHTML = `
            <div class="alert alert-${type}">
                ${message}
            </div>
        `;
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 5000);
    }
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
        
        // Clear previous alerts
        alertContainer.innerHTML = '';
        
        // Get form data
        const formData = new FormData(form);
        
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert(data.message, 'success');
                
                // Redirect after 2 seconds
                setTimeout(() => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        window.location.href = '{{ route("login") }}';
                    }
                }, 2000);
            } else {
                showAlert(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.', 'error');
                
                // Show validation errors if any
                if (data.errors) {
                    Object.keys(data.errors).forEach(key => {
                        const input = form.querySelector(`[name="${key}"]`);
                        if (input) {
                            input.style.borderColor = '#f56565';
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'text-danger';
                            errorDiv.textContent = data.errors[key][0];
                            input.parentElement.appendChild(errorDiv);
                        }
                    });
                }
                
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-key me-2"></i>Đặt lại mật khẩu';
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('Có lỗi xảy ra khi kết nối đến server. Vui lòng thử lại.', 'error');
            
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-key me-2"></i>Đặt lại mật khẩu';
        }
    });
    
    // Remove error styling on input change
    const inputs = form.querySelectorAll('input[type="password"]');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            this.style.borderColor = '';
            const errorDiv = this.parentElement.querySelector('.text-danger');
            if (errorDiv) {
                errorDiv.remove();
            }
        });
    });
});
</script>
@endsection
