{{-- Chatbox Widget - Luôn hiển thị ở góc màn hình --}}
<div id="chatboxWidget" class="chatbox-widget">
    {{-- Floating Button --}}
    <button id="chatboxToggle" class="chatbox-toggle-btn" title="Mở chat với AI">
        <i class="fas fa-robot"></i>
        <span class="chatbox-badge" id="chatboxBadge" style="display: none;">1</span>
    </button>

    {{-- Chatbox Container --}}
    <div id="chatboxContainer" class="chatbox-container chatbox-closed">
        {{-- Header --}}
        <div class="chatbox-header">
            <div class="d-flex align-items-center">
                <i class="fas fa-robot me-2"></i>
                <span class="fw-bold">Trợ lý AI</span>
                <span class="badge bg-success ms-2" id="chatboxConnectionStatus" style="font-size: 0.7rem;">
                    <i class="fas fa-circle"></i> Đang kết nối...
                </span>
            </div>
            <button id="chatboxMinimize" class="btn btn-sm btn-link text-white p-0" title="Thu nhỏ">
                <i class="fas fa-minus"></i>
            </button>
            <button id="chatboxClose" class="btn btn-sm btn-link text-white p-0 ms-2" title="Đóng">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Messages Area --}}
        <div class="chatbox-messages" id="chatboxMessages">
            <div class="text-center text-muted py-4" id="chatboxEmptyState">
                <i class="fas fa-robot fa-2x mb-2 text-primary"></i>
                <p class="small mb-1 fw-bold">Trợ lý AI ZoroRMS</p>
                <p class="small mb-0 text-muted">Chào bạn! Tôi có thể giúp gì về hệ thống?</p>
            </div>
        </div>

        {{-- Input Area --}}
        <div class="chatbox-input">
            <form id="chatboxForm" class="d-flex gap-2">
                <input 
                    type="text" 
                    class="form-control form-control-sm" 
                    id="chatboxInput" 
                    placeholder="Ví dụ: Làm thế nào để tạo hợp đồng thuê?"
                    autocomplete="off"
                >
                <button type="submit" class="btn btn-primary btn-sm" id="chatboxSendBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.chatbox-widget {
    position: fixed !important;
    bottom: 20px !important;
    right: 20px !important;
    z-index: 9998 !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    pointer-events: all !important;
}

.chatbox-toggle-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.chatbox-toggle-btn:hover {
    transform: scale(1.1) !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2) !important;
}

.chatbox-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.chatbox-container {
    position: fixed !important;
    bottom: 100px !important;
    right: 20px !important;
    width: 380px;
    height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease;
    z-index: 9997 !important;
    pointer-events: all !important;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chatbox-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 14px;
}

.chatbox-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f8f9fa;
    scroll-behavior: smooth;
}

.chatbox-message {
    margin-bottom: 12px;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

.chatbox-message-user {
    display: flex;
    justify-content: flex-end;
}

.chatbox-message-ai {
    display: flex;
    justify-content: flex-start;
}

.chatbox-message-content {
    max-width: 80%;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 13px;
    line-height: 1.5;
    word-wrap: break-word;
}

.chatbox-message-user .chatbox-message-content {
    background: #667eea;
    color: white;
    border-bottom-right-radius: 4px;
}

.chatbox-message-ai .chatbox-message-content {
    background: white;
    color: #333;
    border: 1px solid #e0e0e0;
    border-bottom-left-radius: 4px;
}

.chatbox-message-time {
    font-size: 10px;
    color: #999;
    margin-top: 4px;
    padding: 0 4px;
}

.chatbox-input {
    padding: 12px;
    background: white;
    border-top: 1px solid #e0e0e0;
}

.chatbox-input .form-control {
    font-size: 13px;
}

.chatbox-loading {
    display: inline-flex;
    gap: 4px;
    padding: 10px 14px;
}

.chatbox-loading span {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #999;
    animation: bounce 1.4s infinite ease-in-out both;
}

.chatbox-loading span:nth-child(1) { animation-delay: -0.32s; }
.chatbox-loading span:nth-child(2) { animation-delay: -0.16s; }

@keyframes bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

/* Scrollbar styling */
.chatbox-messages::-webkit-scrollbar {
    width: 6px;
}

.chatbox-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.chatbox-messages::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.chatbox-messages::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Responsive */
@media (max-width: 768px) {
    .chatbox-container {
        width: calc(100vw - 40px);
        height: calc(100vh - 100px);
        bottom: 80px;
        right: 20px;
    }
}
</style>

<script>
(function() {
    const widget = document.getElementById('chatboxWidget');
    const toggleBtn = document.getElementById('chatboxToggle');
    const container = document.getElementById('chatboxContainer');
    const minimizeBtn = document.getElementById('chatboxMinimize');
    const closeBtn = document.getElementById('chatboxClose');
    const form = document.getElementById('chatboxForm');
    const input = document.getElementById('chatboxInput');
    const messagesArea = document.getElementById('chatboxMessages');
    const emptyState = document.getElementById('chatboxEmptyState');
    const sendBtn = document.getElementById('chatboxSendBtn');
    const connectionStatus = document.getElementById('chatboxConnectionStatus');
    
    let conversationHistory = [];
    let isProcessing = false;
    let isOpen = false;

    // Load saved state (chỉ load nếu đã mở trước đó)
    const savedState = localStorage.getItem('chatboxOpen');
    // Không tự động mở, để người dùng tự quyết định

    // Toggle chatbox
    toggleBtn.addEventListener('click', function() {
        if (isOpen) {
            closeChatbox();
        } else {
            openChatbox();
        }
    });

    // Minimize
    minimizeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        closeChatbox();
    });

    // Close
    closeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        closeChatbox();
    });

    function openChatbox() {
        container.classList.remove('chatbox-closed');
        container.classList.add('chatbox-open');
        isOpen = true;
        localStorage.setItem('chatboxOpen', 'true');
        setTimeout(() => input.focus(), 100);
        checkConnection();
    }

    function closeChatbox() {
        container.classList.remove('chatbox-open');
        container.classList.add('chatbox-closed');
        isOpen = false;
        localStorage.setItem('chatboxOpen', 'false');
    }

    // Form submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    // Enter key
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Check connection
    async function checkConnection() {
        try {
            const response = await fetch('{{ route("chat.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    message: 'test',
                    conversation_history: []
                })
            });

            const data = await response.json();
            
            if (data.success) {
                connectionStatus.innerHTML = '<i class="fas fa-circle"></i> Đã kết nối';
                connectionStatus.className = 'badge bg-success ms-2';
                conversationHistory = [];
            } else {
                connectionStatus.innerHTML = '<i class="fas fa-circle"></i> Lỗi';
                connectionStatus.className = 'badge bg-danger ms-2';
            }
        } catch (error) {
            connectionStatus.innerHTML = '<i class="fas fa-circle"></i> Lỗi';
            connectionStatus.className = 'badge bg-danger ms-2';
        }
    }

    // Send message
    async function sendMessage() {
        const message = input.value.trim();
        
        if (!message || isProcessing) {
            return;
        }

        addMessage('user', message);
        input.value = '';
        isProcessing = true;
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        conversationHistory.push({
            role: 'user',
            content: message
        });

        const loadingId = showLoading();

        try {
            const response = await fetch('{{ route("chat.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    message: message,
                    conversation_history: conversationHistory.slice(0, -1)
                })
            });

            const data = await response.json();
            
            removeLoading(loadingId);

            if (data.success) {
                addMessage('ai', data.message);
                conversationHistory.push({
                    role: 'model',
                    content: data.message
                });
            } else {
                showError('Lỗi: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            removeLoading(loadingId);
            showError('Đã xảy ra lỗi: ' + error.message);
        } finally {
            isProcessing = false;
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            input.focus();
        }
    }

    // Add message
    function addMessage(role, content) {
        if (emptyState) {
            emptyState.remove();
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbox-message chatbox-message-${role}`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'chatbox-message-content';
        contentDiv.textContent = content;
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'chatbox-message-time';
        timeDiv.textContent = new Date().toLocaleTimeString('vi-VN', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });

        const wrapper = document.createElement('div');
        wrapper.appendChild(contentDiv);
        wrapper.appendChild(timeDiv);
        messageDiv.appendChild(wrapper);

        messagesArea.appendChild(messageDiv);
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    // Show loading
    function showLoading() {
        if (emptyState) {
            emptyState.remove();
        }

        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'chatbox-message chatbox-message-ai';
        loadingDiv.id = 'chatbox-loading';
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'chatbox-loading';
        contentDiv.innerHTML = '<span></span><span></span><span></span>';
        
        loadingDiv.appendChild(contentDiv);
        messagesArea.appendChild(loadingDiv);
        messagesArea.scrollTop = messagesArea.scrollHeight;

        return 'chatbox-loading';
    }

    // Remove loading
    function removeLoading(id) {
        const loading = document.getElementById(id);
        if (loading) {
            loading.remove();
        }
    }

    // Show error
    function showError(message) {
        if (emptyState) {
            emptyState.remove();
        }

        const errorDiv = document.createElement('div');
        errorDiv.className = 'chatbox-message chatbox-message-ai';
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'chatbox-message-content';
        contentDiv.style.background = '#fee';
        contentDiv.style.color = '#c33';
        contentDiv.textContent = '⚠️ ' + message;
        
        errorDiv.appendChild(contentDiv);
        messagesArea.appendChild(errorDiv);
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }
})();
</script>

