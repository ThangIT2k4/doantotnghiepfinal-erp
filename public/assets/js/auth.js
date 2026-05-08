
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.querySelector('.password-toggle');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }

    // Form submission handler (backend handles redirect)
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            const btn = document.querySelector('.btn-login');
            if (!btn) return;
            btn.dataset.originalText = btn.textContent;
            btn.textContent = 'Đang đăng nhập...';
            btn.disabled = true;
        });
    }

    // Add floating animation to illustration elements
    function addFloatingAnimation() {
        const person = document.querySelector('.person');
        const laptop = document.querySelector('.laptop');
        
        if (person && laptop) {
            setInterval(() => {
                person.style.transform = `translateY(${Math.sin(Date.now() * 0.002) * 3}px)`;
                laptop.style.transform = `translateY(${Math.sin(Date.now() * 0.003) * 2}px)`;
            }, 50);
        }
    }

    // Start animations when page loads
    window.addEventListener('load', addFloatingAnimation);


    // Register (enable only when phone-based step UI present)
    (function () {
        const phoneInput = document.getElementById('phoneNumber');
        if (!phoneInput) {
            return; // Email-based register; let backend handle
        }

        function validatePhone(phone) {
            const phoneRegex = /(84|0[3|5|7|8|9])+([0-9]{8})\b/;
            return phoneRegex.test(phone);
        }

        function showStep(stepNumber) {
            document.querySelectorAll('.step-1, .step-2, .step-3').forEach(step => {
                step.style.display = 'none';
            });
            document.querySelectorAll('.step-1-content').forEach(content => {
                content.style.display = stepNumber === 1 ? 'block' : 'none';
            });
            const target = document.querySelector(`.step-${stepNumber}`);
            if (target) target.style.display = 'block';
        }

        window.backToPhone = function() { showStep(1); };

        const phoneForm = document.getElementById('registerForm');
        if (phoneForm) {
            phoneForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const phone = phoneInput.value.trim();
                const validation = document.getElementById('phoneValidation');
                if (!validatePhone(phone)) {
                    if (validation) {
                        validation.style.display = 'block';
                        validation.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>Số điện thoại không hợp lệ';
                        validation.style.borderColor = '#e74c3c';
                        validation.style.color = '#e74c3c';
                    }
                    return;
                }
                const btn = document.querySelector('.btn-register');
                const originalText = btn ? btn.textContent : '';
                if (btn) { btn.textContent = 'Đang gửi mã...'; btn.disabled = true; }
                setTimeout(() => {
                    const disp = document.getElementById('phoneDisplay');
                    if (disp) disp.textContent = phone;
                    showStep(2);
                    if (btn) { btn.textContent = originalText; btn.disabled = false; }
                }, 2000);
            });
        }

        const otpForm = document.getElementById('otpForm');
        if (otpForm) {
            otpForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = this.querySelector('.btn-register');
                const originalText = btn ? btn.textContent : '';
                if (btn) { btn.textContent = 'Đang xác thực...'; btn.disabled = true; }
                setTimeout(() => {
                    showStep(3);
                    if (btn) { btn.textContent = originalText; btn.disabled = false; }
                }, 1500);
            });
        }

        const completeForm = document.getElementById('completeForm');
        if (completeForm) {
            completeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const passwords = this.querySelectorAll('input[type="password"]');
                if (passwords[0] && passwords[1] && passwords[0].value !== passwords[1].value) {
                    alert('Mật khẩu không khớp!');
                    return;
                }
                const btn = this.querySelector('.btn-register');
                const originalText = btn ? btn.textContent : '';
                if (btn) { btn.textContent = 'Đang đăng ký...'; btn.disabled = true; }
                setTimeout(() => {
                    alert('Đăng ký thành công!');
                    if (btn) { btn.textContent = originalText; btn.disabled = false; }
                }, 2000);
            });
        }

        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.startsWith('84')) {
                value = value.substring(2);
                value = '0' + value;
            }
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            e.target.value = value;
            const validation = document.getElementById('phoneValidation');
            if (!validation) return;
            if (value.length >= 10 && validatePhone(value)) {
                validation.style.display = 'block';
                validation.innerHTML = '<i class="fas fa-check-circle me-2"></i>Số điện thoại hợp lệ';
                validation.style.borderColor = '#27ae60';
                validation.style.color = '#27ae60';
            } else if (value.length > 0) {
                validation.style.display = 'block';
                validation.innerHTML = '<i class="fas fa-info-circle me-2"></i>Vui lòng nhập số điện thoại hợp lệ (10 số)';
                validation.style.borderColor = '#f39c12';
                validation.style.color = '#f39c12';
            } else {
                validation.style.display = 'none';
            }
        });
    })();

        // Add floating animation to illustration elements
        function addFloatingAnimation() {
            const person = document.querySelector('.person');
            const laptop = document.querySelector('.laptop');
            
            if (person && laptop) {
                setInterval(() => {
                    person.style.transform = `translateY(${Math.sin(Date.now() * 0.002) * 3}px)`;
                    laptop.style.transform = `translateY(${Math.sin(Date.now() * 0.003) * 2}px)`;
                }, 50);
            }
        }

        // Start animations when page loads
        window.addEventListener('load', addFloatingAnimation);