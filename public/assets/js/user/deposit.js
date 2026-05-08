// Deposit Page JavaScript
var currentStep = 1;
var selectedPaymentMethod = null;
var transactionData = {};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeDeposit();
    setupPaymentMethods();
    setupStepNavigation();
    setupFormValidation();
    setupReceiptActions();
});

// Initialize deposit functionality
function initializeDeposit() {
    // Set default step
    showStep(1);
    
    // Generate transaction ID
    transactionData.id = generateTransactionId();
    transactionData.date = new Date().toLocaleString('vi-VN');
    
    console.log('Deposit page initialized');
}

// Setup payment method selection
function setupPaymentMethods() {
    var paymentMethods = document.querySelectorAll('.payment-method');
    
    for (var i = 0; i < paymentMethods.length; i++) {
        paymentMethods[i].addEventListener('click', function() {
            selectPaymentMethod(this);
        });
    }
    
    // Radio button change event
    var radioButtons = document.querySelectorAll('input[name="payment_method"]');
    for (var j = 0; j < radioButtons.length; j++) {
        radioButtons[j].addEventListener('change', function() {
            var method = document.querySelector('[data-method="' + this.value + '"]');
            if (method) {
                selectPaymentMethod(method);
            }
        });
    }
}

// Select payment method
function selectPaymentMethod(methodElement) {
    // Remove previous selections
    var allMethods = document.querySelectorAll('.payment-method');
    for (var i = 0; i < allMethods.length; i++) {
        allMethods[i].classList.remove('selected');
    }
    
    // Add selection to current method
    methodElement.classList.add('selected');
    
    // Update radio button
    var radio = methodElement.querySelector('input[type="radio"]');
    if (radio) {
        radio.checked = true;
        selectedPaymentMethod = radio.value;
    }
    
    // Enable next button
    var nextBtn = document.getElementById('nextStep1');
    if (nextBtn) {
        nextBtn.disabled = false;
    }
    
    console.log('Selected payment method:', selectedPaymentMethod);
}

// Setup step navigation
function setupStepNavigation() {
    // Step 1 -> Step 2
    var nextStep1 = document.getElementById('nextStep1');
    if (nextStep1) {
        nextStep1.addEventListener('click', function() {
            if (selectedPaymentMethod) {
                showStep(2);
                updateSelectedMethodDisplay();
            } else {
                showToast('Vui lòng chọn phương thức thanh toán', 'error');
            }
        });
    }
    
    // Step 2 -> Step 1 (Back)
    var backStep2 = document.getElementById('backStep2');
    if (backStep2) {
        backStep2.addEventListener('click', function() {
            showStep(1);
        });
    }
    
    // Step 2 -> Step 3 (Confirm Payment)
    var confirmPayment = document.getElementById('confirmPayment');
    if (confirmPayment) {
        confirmPayment.addEventListener('click', function() {
            if (validateStep2()) {
                processPayment();
            }
        });
    }
}

// Show specific step
function showStep(stepNumber) {
    currentStep = stepNumber;
    
    // Update progress steps
    var steps = document.querySelectorAll('.step');
    for (var i = 0; i < steps.length; i++) {
        var step = steps[i];
        var stepNum = parseInt(step.getAttribute('data-step'));
        
        step.classList.remove('active', 'completed');
        
        if (stepNum < stepNumber) {
            step.classList.add('completed');
        } else if (stepNum === stepNumber) {
            step.classList.add('active');
        }
    }
    
    // Update step panels
    var panels = document.querySelectorAll('.step-panel');
    for (var j = 0; j < panels.length; j++) {
        panels[j].classList.remove('active');
    }
    
    var activePanel = document.getElementById('step' + stepNumber);
    if (activePanel) {
        activePanel.classList.add('active');
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    console.log('Showing step:', stepNumber);
}

// Update selected method display in step 2
function updateSelectedMethodDisplay() {
    var selectedMethodDiv = document.getElementById('selectedMethod');
    if (!selectedMethodDiv || !selectedPaymentMethod) return;
    
    var methodInfo = getPaymentMethodInfo(selectedPaymentMethod);
    
    selectedMethodDiv.innerHTML = 
        '<div class="icon" style="background: ' + methodInfo.color + ';">' +
            methodInfo.icon +
        '</div>' +
        '<div>' +
            '<strong>' + methodInfo.name + '</strong>' +
            '<br><small>' + methodInfo.description + '</small>' +
        '</div>';
}

// Get payment method info
function getPaymentMethodInfo(method) {
    var methods = {
        'bank': {
            name: 'Chuyển khoản ngân hàng',
            description: 'Chuyển tiền trực tiếp qua tài khoản ngân hàng',
            icon: '<i class="fas fa-university"></i>',
            color: 'var(--primary)'
        },
        'momo': {
            name: 'Ví MoMo',
            description: 'Thanh toán nhanh chóng qua ví điện tử MoMo',
            icon: '<img src="https://developers.momo.vn/v3/assets/images/logo.png" style="width:24px;" alt="MoMo">',
            color: '#d82d8b'
        },
        'zalopay': {
            name: 'ZaloPay',
            description: 'Thanh toán an toàn với ví điện tử ZaloPay',
            icon: '<img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-ZaloPay-Square.png" style="width:24px;" alt="ZaloPay">',
            color: '#0068ff'
        },
        'vnpay': {
            name: 'VNPay',
            description: 'Cổng thanh toán trực tuyến VNPay',
            icon: '<img src="https://vnpay.vn/s1/statics.vnpay.vn/2023/9/06ncktiwd6dc1694418196384.png" style="width:24px;" alt="VNPay">',
            color: '#1a73e8'
        }
    };
    
    return methods[method] || methods['bank'];
}

// Setup form validation
function setupFormValidation() {
    // Real-time validation for required fields
    var requiredFields = ['renterName', 'renterPhone', 'renterEmail', 'renterID'];
    
    for (var i = 0; i < requiredFields.length; i++) {
        var field = document.getElementById(requiredFields[i]);
        if (field) {
            field.addEventListener('input', checkStep2Validation);
            field.addEventListener('blur', function() {
                validateField(this);
            });
        }
    }
    
    // Terms agreement
    var agreeTerms = document.getElementById('agreeTerms');
    if (agreeTerms) {
        agreeTerms.addEventListener('change', checkStep2Validation);
    }
    
    // Phone number formatting
    var phoneInput = document.getElementById('renterPhone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    }
}

// Validate individual field
function validateField(field) {
    var isValid = true;
    var value = field.value.trim();
    
    // Remove existing error styling
    field.classList.remove('is-invalid');
    
    if (field.hasAttribute('required') && !value) {
        isValid = false;
    } else if (field.type === 'email' && value) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
        }
    } else if (field.id === 'renterPhone' && value) {
        var phoneRegex = /^\d{10,11}$/;
        var cleanPhone = value.replace(/\s/g, '');
        if (!phoneRegex.test(cleanPhone)) {
            isValid = false;
        }
    } else if (field.id === 'renterID' && value) {
        var idRegex = /^\d{9,12}$/;
        if (!idRegex.test(value)) {
            isValid = false;
        }
    }
    
    if (!isValid) {
        field.classList.add('is-invalid');
    }
    
    return isValid;
}

// Check step 2 validation
function checkStep2Validation() {
    var requiredFields = ['renterName', 'renterPhone', 'renterEmail', 'renterID'];
    var allValid = true;
    
    for (var i = 0; i < requiredFields.length; i++) {
        var field = document.getElementById(requiredFields[i]);
        if (field && !validateField(field)) {
            allValid = false;
        }
    }
    
    var agreeTerms = document.getElementById('agreeTerms');
    if (agreeTerms && !agreeTerms.checked) {
        allValid = false;
    }
    
    var confirmBtn = document.getElementById('confirmPayment');
    if (confirmBtn) {
        confirmBtn.disabled = !allValid;
    }
    
    return allValid;
}

// Validate step 2
function validateStep2() {
    var isValid = checkStep2Validation();
    
    if (!isValid) {
        showToast('Vui lòng điền đầy đủ thông tin và đồng ý với điều khoản', 'error');
        
        // Focus on first invalid field
        var invalidField = document.querySelector('.is-invalid');
        if (invalidField) {
            invalidField.focus();
        }
    }
    
    return isValid;
}

// Format phone number
function formatPhoneNumber(input) {
    var value = input.value.replace(/\D/g, '');
    
    // Limit to 10 digits
    if (value.length > 10) {
        value = value.substring(0, 10);
    }
    
    // Format as XXX XXX XXXX
    if (value.length >= 6) {
        value = value.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
    } else if (value.length >= 3) {
        value = value.replace(/(\d{3})(\d{1,3})/, '$1 $2');
    }
    
    input.value = value;
}

// Process payment
function processPayment() {
    // Collect form data
    transactionData.renterName = document.getElementById('renterName').value;
    transactionData.renterPhone = document.getElementById('renterPhone').value;
    transactionData.renterEmail = document.getElementById('renterEmail').value;
    transactionData.renterID = document.getElementById('renterID').value;
    transactionData.paymentMethod = selectedPaymentMethod;
    
    // Show loading modal
    showLoadingModal();
    
    // Simulate payment processing
    setTimeout(function() {
        hideLoadingModal();
        
        // Simulate success (90% success rate)
        if (Math.random() > 0.1) {
            showSuccessModal();
            setTimeout(function() {
                hideSuccessModal();
                showStep(3);
                populateReceipt();
            }, 2000);
        } else {
            showToast('Thanh toán thất bại. Vui lòng thử lại.', 'error');
        }
    }, 3000);
}

// Populate receipt with transaction data
function populateReceipt() {
    // Update transaction info
    document.getElementById('transactionId').textContent = transactionData.id;
    document.getElementById('transactionDate').textContent = transactionData.date;
    
    // Update renter info
    document.getElementById('receiptRenterName').textContent = transactionData.renterName;
    document.getElementById('receiptRenterPhone').textContent = transactionData.renterPhone;
    document.getElementById('receiptRenterEmail').textContent = transactionData.renterEmail;
    document.getElementById('receiptRenterID').textContent = transactionData.renterID;
    
    // Update payment method
    var methodInfo = getPaymentMethodInfo(transactionData.paymentMethod);
    var receiptMethodDiv = document.getElementById('receiptPaymentMethod');
    if (receiptMethodDiv) {
        receiptMethodDiv.innerHTML = 
            '<div class="icon" style="background: ' + methodInfo.color + ';">' +
                methodInfo.icon +
            '</div>' +
            '<div>' +
                '<strong>' + methodInfo.name + '</strong>' +
                '<br><small>Giao dịch thành công</small>' +
            '</div>';
    }
    
    // Update QR code with transaction ID
    var qrImg = document.querySelector('.qr-code img');
    if (qrImg) {
        qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' + transactionData.id;
    }
}

// Setup receipt actions
function setupReceiptActions() {
    var downloadBtn = document.getElementById('downloadReceipt');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', downloadReceipt);
    }
    
    var emailBtn = document.getElementById('emailReceipt');
    if (emailBtn) {
        emailBtn.addEventListener('click', emailReceipt);
    }
    
    var printBtn = document.getElementById('printReceipt');
    if (printBtn) {
        printBtn.addEventListener('click', printReceipt);
    }
}

// Download receipt
function downloadReceipt() {
    // In a real application, this would generate a PDF
    showToast('Tính năng tải biên lai sẽ được cập nhật sớm', 'info');
    
    // Simulate download
    var link = document.createElement('a');
    link.href = 'data:text/plain;charset=utf-8,' + encodeURIComponent('Biên lai thanh toán - Mã GD: ' + transactionData.id);
    link.download = 'bien-lai-' + transactionData.id + '.txt';
    link.click();
}

// Email receipt
function emailReceipt() {
    if (!transactionData.renterEmail) {
        showToast('Không tìm thấy địa chỉ email', 'error');
        return;
    }
    
    // Simulate email sending
    showToast('Đang gửi biên lai đến email ' + transactionData.renterEmail + '...', 'info');
    
    setTimeout(function() {
        showToast('Đã gửi biên lai đến email thành công!', 'success');
    }, 2000);
}

// Print receipt
function printReceipt() {
    // Hide non-printable elements
    var nonPrintElements = document.querySelectorAll('.receipt-actions, .step-actions, .progress-section');
    for (var i = 0; i < nonPrintElements.length; i++) {
        nonPrintElements[i].style.display = 'none';
    }
    
    // Print
    window.print();
    
    // Restore elements
    setTimeout(function() {
        for (var j = 0; j < nonPrintElements.length; j++) {
            nonPrintElements[j].style.display = '';
        }
    }, 1000);
}

// Generate transaction ID
function generateTransactionId() {
    var prefix = 'DP';
    var date = new Date();
    var dateStr = date.getFullYear().toString() + 
                 (date.getMonth() + 1).toString().padStart(2, '0') + 
                 date.getDate().toString().padStart(2, '0');
    var randomNum = Math.floor(Math.random() * 100).toString().padStart(2, '0');
    
    return prefix + dateStr + randomNum;
}

// Modal functions
function showLoadingModal() {
    if (typeof bootstrap !== 'undefined') {
        var loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();
    }
}

function hideLoadingModal() {
    if (typeof bootstrap !== 'undefined') {
        var loadingModal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
        if (loadingModal) {
            loadingModal.hide();
        }
    }
}

function showSuccessModal() {
    if (typeof bootstrap !== 'undefined') {
        var successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
    }
}

function hideSuccessModal() {
    if (typeof bootstrap !== 'undefined') {
        var successModal = bootstrap.Modal.getInstance(document.getElementById('successModal'));
        if (successModal) {
            successModal.hide();
        }
    }
}

// Toast notification function - using unified notification system
function showToast(message, type) {
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
        // Fallback to simple alert if Notify is not available
        alert(message);
    }
}

// Add CSS for animations if not already present
if (!document.querySelector('#deposit-animations')) {
    var style = document.createElement('style');
    style.id = 'deposit-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .form-control.is-invalid {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }
        
        @media print {
            .receipt-container {
                box-shadow: none !important;
                border: none !important;
            }
            
            .deposit-container {
                background: white !important;
            }
            
            .progress-section,
            .receipt-actions,
            .step-actions {
                display: none !important;
            }
        }
    `;
    document.head.appendChild(style);
}
