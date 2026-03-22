@extends('layouts.auth')

@section('title', 'Xác thực OTP - Quên mật khẩu')

@section('content')
<div class="auth-page-wrapper">
    <div class="circle circle1"></div>
    <div class="circle circle2"></div>

    <div class="container">
        <div class="header">
            <h1>Xác thực OTP</h1>
            <p>Nhập mã OTP để đặt lại mật khẩu</p>
        </div>

        <div id="alertContainer"></div>

        <div class="email-display">
            <i class="fas fa-envelope me-2"></i>
            <span id="verificationEmail">{{ $email ?? 'user@example.com' }}</span>
        </div>

        <form id="otpVerificationForm" novalidate>
            @csrf
            <input type="hidden" id="email" name="email" value="{{ $email ?? '' }}">
            <div class="otp-input-group">
                <input type="text" class="otp-digit" maxlength="1" data-index="0" autocomplete="off" pattern="[0-9]">
                <input type="text" class="otp-digit" maxlength="1" data-index="1" autocomplete="off" pattern="[0-9]">
                <input type="text" class="otp-digit" maxlength="1" data-index="2" autocomplete="off" pattern="[0-9]">
                <input type="text" class="otp-digit" maxlength="1" data-index="3" autocomplete="off" pattern="[0-9]">
                <input type="text" class="otp-digit" maxlength="1" data-index="4" autocomplete="off" pattern="[0-9]">
                <input type="text" class="otp-digit" maxlength="1" data-index="5" autocomplete="off" pattern="[0-9]">
            </div>
            <input type="hidden" id="otpCode" name="otp_code">
        </form>

        <div class="otp-timer" id="timer">
            <i class="fas fa-clock me-2"></i>
            <span id="timerText">Mã OTP chưa được gửi</span>
        </div>

        <button type="button" class="signup-btn" id="verifyBtn" disabled>
            <i class="fas fa-check me-2"></i>Xác thực OTP
        </button>

        <div class="otp-resend" id="resendContainer">
            <p>Không nhận được mã? 
                <a href="#" id="resendLink" onclick="resendOtp(event)">
                    <i class="fas fa-redo me-1"></i>Gửi lại mã OTP
                </a>
            </p>
        </div>

        <div class="footer">
            <a href="{{ route('password.forgot') }}">
                <i class="fas fa-arrow-left me-2"></i>Quay lại
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
    --input-bg: rgba(255, 255, 255, 0.16);
    --input-border: rgba(255, 255, 255, 0.35);
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
    background: linear-gradient(0deg, rgba(5, 12, 24, 0.4), rgba(5, 12, 24, 0));
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
    margin-bottom: 34px;
}

.auth-page-wrapper .header h1 {
    color: var(--text-main);
    font-family: 'Space Grotesk', 'Plus Jakarta Sans', sans-serif;
    font-size: 2.2rem;
    letter-spacing: 0.4px;
    font-weight: 700;
    margin-bottom: 10px;
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
    margin-bottom: 30px;
    font-weight: 600;
    color: #e4f3ff;
    font-size: 0.93rem;
    display: flex;
    justify-content: center;
    align-items: center;
    word-break: break-all;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.24);
}

.auth-page-wrapper .otp-input-group {
    display: flex;
    gap: 11px;
    justify-content: center;
    margin: 32px 0 24px;
}

.auth-page-wrapper .otp-digit {
    width: 58px;
    height: 62px;
    text-align: center;
    font-size: 1.78rem;
    font-weight: 700;
    border: 1.5px solid var(--input-border);
    border-radius: 14px;
    background: var(--input-bg);
    color: #ffffff;
    transition: transform 0.25s ease, border-color 0.25s ease, background 0.25s ease, box-shadow 0.25s ease;
    outline: none;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.24), 0 10px 22px rgba(6, 14, 28, 0.22);
    caret-color: transparent;
}

.auth-page-wrapper .otp-digit:focus {
    border-color: rgba(103, 255, 220, 0.88);
    background: rgba(255, 255, 255, 0.24);
    transform: translateY(-2px);
    box-shadow: 0 0 0 4px rgba(85, 244, 213, 0.17), inset 0 1px 0 rgba(255, 255, 255, 0.38);
}

.auth-page-wrapper .otp-digit.filled {
    border-color: rgba(120, 248, 255, 0.95);
    background: rgba(84, 225, 255, 0.18);
}

.auth-page-wrapper .otp-timer {
    text-align: center;
    margin: 18px 0 6px;
    color: rgba(231, 247, 255, 0.72);
    font-size: 0.92rem;
    font-weight: 500;
    display: flex;
    justify-content: center;
    align-items: center;
}

.auth-page-wrapper .otp-timer.active {
    color: #ffe6c7;
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
    margin-top: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 14px 30px rgba(43, 208, 245, 0.3);
}

.auth-page-wrapper .signup-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(53, 220, 242, 0.36);
    filter: brightness(1.03);
}

.auth-page-wrapper .signup-btn:active:not(:disabled) {
    transform: translateY(0);
}

.auth-page-wrapper .signup-btn:disabled {
    opacity: 0.68;
    cursor: not-allowed;
    filter: saturate(0.45);
    box-shadow: none;
}

.auth-page-wrapper .otp-resend {
    text-align: center;
    margin: 22px 0;
    font-size: 0.92rem;
    color: rgba(234, 247, 255, 0.75);
}

.auth-page-wrapper .otp-resend a {
    color: var(--accent-blue);
    text-decoration: none;
    font-weight: 700;
    transition: color 0.2s ease, text-shadow 0.2s ease;
}

.auth-page-wrapper .otp-resend a:hover {
    color: var(--accent-green);
    text-shadow: 0 0 18px rgba(69, 255, 215, 0.48);
}

.auth-page-wrapper .otp-resend.disabled {
    opacity: 0.45;
    pointer-events: none;
}

.auth-page-wrapper .footer {
    text-align: center;
    margin-top: 8px;
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
    display: flex;
    align-items: center;
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
        font-size: 1.8rem;
    }

    .auth-page-wrapper .otp-input-group {
        gap: 9px;
    }

    .auth-page-wrapper .otp-digit {
        width: 49px;
        height: 54px;
        font-size: 1.45rem;
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
        font-size: 1.58rem;
    }

    .auth-page-wrapper .header p {
        font-size: 0.88rem;
    }

    .auth-page-wrapper .otp-input-group {
        gap: 7px;
    }

    .auth-page-wrapper .otp-digit {
        width: 42px;
        height: 48px;
        border-radius: 12px;
        font-size: 1.22rem;
    }

    .auth-page-wrapper .signup-btn {
        font-size: 0.95rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .auth-page-wrapper .circle1,
    .auth-page-wrapper .circle2,
    .auth-page-wrapper .container,
    .auth-page-wrapper .otp-digit,
    .auth-page-wrapper .signup-btn {
        animation: none;
        transition: none;
    }
}
</style>

<script>
let timerInterval;
let timeLeft = 0;
let canResend = false;

document.addEventListener('DOMContentLoaded', function() {
    const otpInputs = document.querySelectorAll('.otp-digit');
    
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            const value = e.target.value;
            
            if (!/^\d$/.test(value)) {
                e.target.value = '';
                return;
            }
            
            if (value && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
            
            updateOtpCode();
            checkFormValidity();
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }
            
            if (e.key === 'v' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                handlePaste(e);
            }
        });
        
        input.addEventListener('paste', handlePaste);
    });
    
    document.getElementById('verifyBtn').addEventListener('click', verifyOtp);
    checkOtpStatus();
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
    
    const lastFilledIndex = Math.min(cleanData.length - 1, otpInputs.length - 1);
    if (otpInputs[lastFilledIndex]) {
        otpInputs[lastFilledIndex].focus();
    }
}

function verifyOtp() {
    const form = document.getElementById('otpVerificationForm');
    const formData = new FormData(form);
    const verifyBtn = document.getElementById('verifyBtn');
    
    // Ensure email is included in form data
    const emailInput = document.getElementById('email');
    if (emailInput && emailInput.value) {
        formData.append('email', emailInput.value);
    }
    
    verifyBtn.disabled = true;
    verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xác thực...';
    
    fetch('{{ route("password.verify-otp") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
        },
        credentials: 'same-origin' // Ensure cookies are sent
    })
    .then(response => {
        // Check if response is ok
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Có lỗi xảy ra khi xác thực OTP.');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.redirect_url) {
            showAlert('success', data.message);
            // Use a shorter delay and ensure redirect URL is valid
            setTimeout(() => {
                if (data.redirect_url && data.redirect_url.trim() !== '') {
                    window.location.href = data.redirect_url;
                } else {
                    // Fallback to reset password route
                    window.location.href = '{{ route("password.reset") }}';
                }
            }, 1500);
        } else {
            showAlert('error', data.message || 'Có lỗi xảy ra khi xác thực OTP.');
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="fas fa-check me-2"></i>Xác thực OTP';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', error.message || 'Có lỗi xảy ra khi xác thực OTP. Vui lòng thử lại.');
        verifyBtn.disabled = false;
        verifyBtn.innerHTML = '<i class="fas fa-check me-2"></i>Xác thực OTP';
    });
}

function resendOtp(e) {
    e.preventDefault();
    
    if (!canResend) {
        showAlert('error', 'Vui lòng đợi trước khi gửi lại mã OTP.');
        return;
    }
    
    const resendLink = document.getElementById('resendLink');
    resendLink.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang gửi...';
    resendLink.style.pointerEvents = 'none';
    
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('email', '{{ $email ?? "" }}');
    
    fetch('{{ route("password.resend-otp") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            // Reset timer to 120 seconds (2 minutes) for rate limiting
            startTimer(120);
            canResend = false;
        } else {
            // Handle rate limiting
            if (data.rate_limit && data.remaining_seconds) {
                const remainingMinutes = Math.ceil(data.remaining_seconds / 60);
                showAlert('error', data.message || `Vui lòng đợi ${remainingMinutes} phút trước khi gửi lại mã OTP.`);
                // Start timer with remaining seconds
                startTimer(data.remaining_seconds);
                canResend = false;
            } else {
                showAlert('error', data.message || 'Có lỗi xảy ra khi gửi lại mã OTP.');
            }
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
    // Check if OTP was sent
    const email = '{{ $email ?? "" }}';
    if (email) {
        startTimer(120);
    }
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
    if (typeof window.authGlassToast === 'function') {
        window.authGlassToast(type, message, 5000);
        return;
    }

    const alertContainer = document.getElementById('alertContainer');
    const alertClass = `alert-${type}`;
    const iconClass = type === 'success' ? 'fa-check-circle' : 
                     type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    
    alertContainer.innerHTML = `
        <div class="alert ${alertClass}">
            <i class="fas ${iconClass} me-2"></i>
            ${message}
        </div>
    `;
    
    setTimeout(() => {
        alertContainer.innerHTML = '';
    }, 5000);
}
</script>
@endsection
