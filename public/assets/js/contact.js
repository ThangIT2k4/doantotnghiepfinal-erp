// Contact Page JavaScript
var chatMessages = [];

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeContact();
    setupContactForm();
    setupChat();
});

// Initialize contact functionality
function initializeContact() {
    console.log('Contact page initialized');
    
    // Phone number formatting
    var phoneInput = document.getElementById('contactPhone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    }
}

// Setup contact form
function setupContactForm() {
    var form = document.getElementById('contactForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitContactForm();
        });
    }
}

// Submit contact form
function submitContactForm() {
    var name = document.getElementById('contactName').value;
    var phone = document.getElementById('contactPhone').value;
    var email = document.getElementById('contactEmail').value;
    var subject = document.getElementById('contactSubject').value;
    var message = document.getElementById('contactMessage').value;
    
    // Validate required fields
    if (!name || !phone || !email || !message) {
        showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'error');
        return;
    }
    
    // Validate email
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showToast('Email không hợp lệ', 'error');
        return;
    }
    
    // Show loading
    var submitBtn = document.querySelector('#contactForm button[type="submit"]');
    var originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang gửi...';
    submitBtn.disabled = true;
    
    // Simulate API call
    setTimeout(function() {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        // Reset form
        document.getElementById('contactForm').reset();
        
        // Show success
        showToast('Tin nhắn đã được gửi thành công! Chúng tôi sẽ phản hồi trong 24h.', 'success');
        
        console.log('Contact form submitted:', {
            name: name,
            phone: phone,
            email: email,
            subject: subject,
            message: message
        });
    }, 2000);
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

// Open live chat
function openLiveChat() {
    if (typeof bootstrap !== 'undefined') {
        var chatModal = new bootstrap.Modal(document.getElementById('liveChatModal'));
        chatModal.show();
        
        // Initialize chat
        initializeChat();
    } else {
        alert('Tính năng chat trực tuyến sẽ được cập nhật sớm');
    }
}

// Setup chat functionality
function setupChat() {
    var chatInput = document.getElementById('chatInput');
    
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
}

// Initialize chat
function initializeChat() {
    chatMessages = [
        {
            type: 'bot',
            text: 'Xin chào! Tôi là trợ lý ảo của PhòngTrọ24. Tôi có thể giúp gì cho bạn hôm nay?',
            time: 'Vừa xong'
        }
    ];
    
    updateChatDisplay();
}

// Send chat message
function sendMessage() {
    var input = document.getElementById('chatInput');
    var message = input.value.trim();
    
    if (!message) return;
    
    // Add user message
    chatMessages.push({
        type: 'user',
        text: message,
        time: 'Vừa xong'
    });
    
    // Clear input
    input.value = '';
    
    // Update display
    updateChatDisplay();
    
    // Simulate bot response
    setTimeout(function() {
        var botResponse = generateBotResponse(message);
        chatMessages.push({
            type: 'bot',
            text: botResponse,
            time: 'Vừa xong'
        });
        updateChatDisplay();
    }, 1000);
}

// Generate bot response
function generateBotResponse(userMessage) {
    var message = userMessage.toLowerCase();
    
    if (message.includes('giá') || message.includes('tiền')) {
        return 'Giá phòng trọ tại Hà Nội dao động từ 1.5 - 15 triệu/tháng tùy theo vị trí và chất lượng. Bạn có thể xem chi tiết giá từng phòng trên website.';
    } else if (message.includes('thuê') || message.includes('phòng')) {
        return 'Để thuê phòng, bạn có thể: 1) Tìm kiếm phòng phù hợp 2) Đặt lịch xem phòng 3) Ký hợp đồng và đặt cọc. Tôi có thể hỗ trợ bạn từng bước!';
    } else if (message.includes('hợp đồng')) {
        return 'Hợp đồng thuê nhà cần có đầy đủ thông tin hai bên, thời hạn thuê, giá cả, và các điều khoản rõ ràng. Chúng tôi có mẫu hợp đồng chuẩn để bạn tham khảo.';
    } else if (message.includes('thanh toán')) {
        return 'Chúng tôi hỗ trợ nhiều phương thức thanh toán: chuyển khoản ngân hàng, ví điện tử (MoMo, ZaloPay), VNPay. Tất cả đều an toàn và bảo mật.';
    } else if (message.includes('xin chào') || message.includes('hello')) {
        return 'Xin chào! Rất vui được hỗ trợ bạn. Bạn cần tư vấn về vấn đề gì?';
    } else {
        return 'Cảm ơn bạn đã liên hệ! Để được hỗ trợ tốt nhất, vui lòng gọi hotline 1900 123 456 hoặc gửi email đến support@phongtro24.com.';
    }
}

// Update chat display
function updateChatDisplay() {
    var chatContainer = document.getElementById('chatMessages');
    if (!chatContainer) return;
    
    chatContainer.innerHTML = '';
    
    for (var i = 0; i < chatMessages.length; i++) {
        var msg = chatMessages[i];
        var messageHtml = `
            <div class="message ${msg.type}">
                <div class="message-avatar">
                    <img src="${msg.type === 'bot' ? 
                        'https://ui-avatars.com/api/?name=Support&background=ff6b35&color=fff&size=40' : 
                        'https://ui-avatars.com/api/?name=User&background=3b82f6&color=fff&size=40'}" 
                        alt="${msg.type}">
                </div>
                <div class="message-content">
                    <div class="message-text">${msg.text}</div>
                    <div class="message-time">${msg.time}</div>
                </div>
            </div>
        `;
        chatContainer.insertAdjacentHTML('beforeend', messageHtml);
    }
    
    // Scroll to bottom
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

// Show toast notification
function showToast(message, type) {
    // Remove existing toasts
    var existingToasts = document.querySelectorAll('.custom-toast');
    for (var i = 0; i < existingToasts.length; i++) {
        existingToasts[i].remove();
    }
    
    var toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        toastContainer.style.cssText = 'position:fixed;top:20px;right:20px;z-index:1050;';
        document.body.appendChild(toastContainer);
    }
    
    var toast = document.createElement('div');
    toast.className = 'custom-toast toast-' + type;
    toast.style.cssText = 'background:white;padding:16px 20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.15);margin-bottom:10px;display:flex;align-items:center;gap:12px;min-width:300px;animation:slideInRight 0.3s ease;border-left:4px solid;';
    
    var icon = 'info-circle';
    var color = '#3b82f6';
    
    if (type === 'success') {
        icon = 'check-circle';
        color = '#10b981';
    } else if (type === 'error') {
        icon = 'times-circle';
        color = '#ef4444';
    } else if (type === 'warning') {
        icon = 'exclamation-triangle';
        color = '#f59e0b';
    }
    
    toast.style.borderLeftColor = color;
    toast.innerHTML = '<i class="fas fa-' + icon + '" style="color:' + color + ';font-size:1.2rem;"></i><span>' + message + '</span>';
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 4 seconds
    setTimeout(function() {
        if (toast.parentElement) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(function() {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }
    }, 4000);
}
