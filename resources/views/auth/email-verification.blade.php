@extends('layouts.auth')

@section('title', 'Xác thực Email')

@section('content')
<div class="auth-page-wrapper">
    <div class="circle circle1"></div>
    <div class="circle circle2"></div>

    <div class="container">
        <div class="header">
            <h1>Xác thực Email</h1>
            <p class="verification-sub">Bước 2 / 2 — Nhập mã OTP đã gửi đến email của bạn</p>
        </div>

        <div id="alertContainer"></div>

        <div class="auth-loading-overlay" id="authLoadingOverlay" aria-hidden="true">
            <div class="auth-loading-card" role="status" aria-live="polite">
                <div class="auth-loading-orbit" aria-hidden="true"></div>
                <p id="authLoadingText">Đang xử lý...</p>
            </div>
        </div>

        <div class="email-display">
            <i class="fas fa-envelope me-2"></i>
            <span id="verificationEmail">{{ $user->email ?? 'user@example.com' }}</span>
        </div>

        <form id="otpVerificationForm" novalidate>
            @csrf
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
            <span class="btn-loader" aria-hidden="true"></span>
            <i class="fas fa-check btn-icon" aria-hidden="true"></i>
            <span class="btn-text">Xác thực</span>
        </button>

        <div class="otp-resend" id="resendContainer">
            <p>Không nhận được mã? 
                <a href="#" id="resendLink" onclick="resendOtp(event)">
                    <span class="resend-loader" aria-hidden="true"></span>
                    <i class="fas fa-redo resend-icon" aria-hidden="true"></i>
                    <span class="resend-text">Gửi lại mã OTP</span>
                </a>
            </p>
        </div>

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
    position: relative;
    isolation: isolate;
    overflow: hidden;
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

.auth-page-wrapper .signup-btn .btn-loader {
    width: 17px;
    height: 17px;
    border-radius: 50%;
    border: 2px solid rgba(5, 35, 52, 0.3);
    border-top-color: rgba(5, 35, 52, 0.95);
    display: none;
    animation: spinClockwise 0.8s linear infinite;
    position: relative;
    z-index: 2;
}

.auth-page-wrapper .signup-btn .btn-icon,
.auth-page-wrapper .signup-btn .btn-text {
    position: relative;
    z-index: 2;
}

.auth-page-wrapper .signup-btn.is-loading {
    pointer-events: none;
    filter: saturate(0.86);
}

.auth-page-wrapper .signup-btn.is-loading::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, transparent 0%, rgba(255, 255, 255, 0.44) 45%, transparent 82%);
    transform: translateX(-140%);
    animation: shimmerSlide 1.2s ease-in-out infinite;
    z-index: 1;
}

.auth-page-wrapper .signup-btn.is-loading .btn-loader {
    display: inline-block;
}

.auth-page-wrapper .signup-btn.is-loading .btn-icon {
    display: none;
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
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.auth-page-wrapper .otp-resend a:hover {
    color: var(--accent-green);
    text-shadow: 0 0 18px rgba(69, 255, 215, 0.48);
}

.auth-page-wrapper .otp-resend.disabled {
    opacity: 0.45;
    pointer-events: none;
}

.auth-page-wrapper .otp-resend .resend-loader {
    width: 13px;
    height: 13px;
    border-radius: 50%;
    border: 2px solid rgba(64, 219, 255, 0.35);
    border-top-color: rgba(77, 247, 199, 0.95);
    display: none;
    animation: spinClockwise 0.75s linear infinite;
}

.auth-page-wrapper .otp-resend a.is-loading {
    color: #a0f2ff;
    text-shadow: 0 0 14px rgba(64, 219, 255, 0.35);
    pointer-events: none;
}

.auth-page-wrapper .otp-resend a.is-loading .resend-loader {
    display: inline-block;
}

.auth-page-wrapper .otp-resend a.is-loading .resend-icon {
    display: none;
}

.auth-page-wrapper .auth-loading-overlay {
    position: absolute;
    inset: 0;
    z-index: 6;
    background: rgba(4, 14, 27, 0.56);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.2s ease, visibility 0.2s ease;
}

.auth-page-wrapper .auth-loading-overlay.show {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

.auth-page-wrapper .auth-loading-card {
    min-width: 250px;
    padding: 20px 22px;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.35);
    background: linear-gradient(150deg, rgba(255, 255, 255, 0.26), rgba(255, 255, 255, 0.08));
    box-shadow: 0 18px 36px rgba(5, 12, 24, 0.42);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    text-align: center;
}

.auth-page-wrapper .auth-loading-orbit {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    position: relative;
}

.auth-page-wrapper .auth-loading-orbit::before,
.auth-page-wrapper .auth-loading-orbit::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    border: 3px solid transparent;
}

.auth-page-wrapper .auth-loading-orbit::before {
    border-top-color: rgba(111, 250, 255, 0.95);
    border-right-color: rgba(111, 250, 255, 0.55);
    animation: spinClockwise 0.95s linear infinite;
}

.auth-page-wrapper .auth-loading-orbit::after {
    inset: 9px;
    border-bottom-color: rgba(77, 247, 199, 0.92);
    border-left-color: rgba(77, 247, 199, 0.55);
    animation: spinCounterClockwise 0.78s linear infinite;
}

.auth-page-wrapper #authLoadingText {
    margin: 0;
    color: #e8f9ff;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.2px;
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

@keyframes spinClockwise {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@keyframes spinCounterClockwise {
    from {
        transform: rotate(360deg);
    }
    to {
        transform: rotate(0deg);
    }
}

@keyframes shimmerSlide {
    100% {
        transform: translateX(130%);
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

    .auth-page-wrapper .auth-loading-card {
        min-width: 210px;
        padding: 18px 16px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .auth-page-wrapper .circle1,
    .auth-page-wrapper .circle2,
    .auth-page-wrapper .container,
    .auth-page-wrapper .otp-digit,
    .auth-page-wrapper .signup-btn,
    .auth-page-wrapper .auth-loading-orbit::before,
    .auth-page-wrapper .auth-loading-orbit::after,
    .auth-page-wrapper .signup-btn::after,
    .auth-page-wrapper .signup-btn .btn-loader,
    .auth-page-wrapper .otp-resend .resend-loader {
        animation: none;
        transition: none;
    }
}
</style>

<script>
let timerInterval;
let timeLeft = 0;
let canResend = false;
let isVerifying = false;
let isResending = false;

const VERIFY_DEFAULT_TEXT = 'Xác thực';
const VERIFY_LOADING_TEXT = 'Đang xác thực OTP...';
const RESEND_DEFAULT_TEXT = 'Gửi lại mã OTP';
const RESEND_LOADING_TEXT = 'Đang gửi mã mới...';

document.addEventListener('DOMContentLoaded', function() {
    @if(session('success'))
    showAlert('success', @json(session('success')));
    @endif
    @if(session('warning'))
    showAlert('warning', @json(session('warning')));
    @endif
    @if(session('info'))
    showAlert('info', @json(session('info')));
    @endif

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
    
    if (otpCode.length === 6 && !isVerifying) {
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

function setGlobalLoadingState(isLoading, message = 'Đang xử lý...') {
    const loadingOverlay = document.getElementById('authLoadingOverlay');
    const loadingText = document.getElementById('authLoadingText');

    if (!loadingOverlay || !loadingText) {
        return;
    }

    loadingText.textContent = message;
    loadingOverlay.classList.toggle('show', isLoading);
    loadingOverlay.setAttribute('aria-hidden', isLoading ? 'false' : 'true');
}

function setVerifyLoadingState(isLoading, message = VERIFY_LOADING_TEXT) {
    const verifyBtn = document.getElementById('verifyBtn');
    const btnText = verifyBtn.querySelector('.btn-text');

    isVerifying = isLoading;
    verifyBtn.classList.toggle('is-loading', isLoading);
    btnText.textContent = isLoading ? message : VERIFY_DEFAULT_TEXT;

    if (isLoading) {
        verifyBtn.disabled = true;
    } else {
        checkFormValidity();
    }
}

function setResendLoadingState(isLoading, message = RESEND_LOADING_TEXT) {
    const resendLink = document.getElementById('resendLink');
    const resendText = resendLink.querySelector('.resend-text');

    isResending = isLoading;
    resendLink.classList.toggle('is-loading', isLoading);
    resendText.textContent = isLoading ? message : RESEND_DEFAULT_TEXT;
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
    if (isVerifying) {
        return;
    }

    const form = document.getElementById('otpVerificationForm');
    const formData = new FormData(form);
    let shouldKeepLoading = false;

    setVerifyLoadingState(true);
    setGlobalLoadingState(true, 'Đang xác thực mã OTP...');
    
    fetch('{{ route("auth.email-verification.verify") }}', {
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
            shouldKeepLoading = true;
            setGlobalLoadingState(true, 'Xác thực thành công, đang chuyển hướng...');
            setTimeout(() => {
                window.location.href = data.redirect_url;
            }, 2000);
        } else {
            showAlert('error', data.message);
            setGlobalLoadingState(false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Có lỗi xảy ra khi xác thực OTP. Vui lòng thử lại.');
        setGlobalLoadingState(false);
    })
    .finally(() => {
        if (!shouldKeepLoading) {
            setVerifyLoadingState(false);
        }
    });
}

function resendOtp(e) {
    e.preventDefault();

    if (isResending) {
        return;
    }
    
    if (!canResend) {
        showAlert('error', 'Vui lòng đợi trước khi gửi lại mã OTP.');
        return;
    }

    setResendLoadingState(true);
    setGlobalLoadingState(true, 'Đang gửi lại mã OTP...');
    
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    
    fetch('{{ route("auth.email-verification.resend") }}', {
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
            startTimer(120);
            canResend = false;
        } else {
            showAlert('error', data.message);
        }

        setGlobalLoadingState(false);
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Có lỗi xảy ra khi gửi lại mã OTP.');
        setGlobalLoadingState(false);
    })
    .finally(() => {
        setResendLoadingState(false);
    });
}

function checkOtpStatus() {
    fetch('{{ route("auth.email-verification.status") }}', {
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
    if (typeof window.authGlassToast === 'function') {
        window.authGlassToast(type, message, 5000);
        return;
    }

    const alertContainer = document.getElementById('alertContainer');
    const alertClass = `alert-${type}`;
    const iconClass = type === 'success' ? 'fa-check-circle'
        : type === 'error' ? 'fa-exclamation-circle'
        : type === 'warning' ? 'fa-exclamation-triangle'
        : 'fa-info-circle';
    
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
