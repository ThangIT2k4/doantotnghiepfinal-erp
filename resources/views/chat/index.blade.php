@extends('layouts.staff_dashboard')

@section('title', 'Chat với AI')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="chat-icon-wrapper me-3">
                            <i class="fas fa-robot fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold">Trợ lý AI ZoroRMS</h4>
                            <p class="text-muted mb-0 small">Hỏi đáp về hệ thống và nghiệp vụ</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-success" id="connectionStatus">
                            <i class="fas fa-circle"></i> <span id="connectionText">Đang kết nối...</span>
                        </span>
                        <button class="btn btn-sm btn-outline-secondary" @click="clearChat()" title="Xóa lịch sử" x-data x-init="$watch('messages', () => {})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <!-- Chat Container - Alpine.js Integration -->
                <div x-data="aiChat()" x-init="init()" style="display:flex;flex-direction:column;height:calc(100vh - 280px);min-height:600px;background:#f8f9fa">
                    <!-- Messages Area -->
                    <div x-ref="messagesContainer" class="chat-messages">
                        <template x-if="messages.length === 0">
                            <div class="chat-empty-state" id="emptyState">
                                <div class="empty-state-icon">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <h5 class="empty-state-title">Chào bạn! Tôi là trợ lý AI</h5>
                                <p class="empty-state-text">Tôi có thể giúp bạn về:</p>
                                <div class="suggested-questions">
                                    <button type="button" class="suggest-btn" @click="useQuick('Làm thế nào để tạo hợp đồng thuê?')">
                                        <i class="fas fa-file-contract"></i> Tạo hợp đồng thuê
                                    </button>
                                    <button type="button" class="suggest-btn" @click="useQuick('Cách tạo hóa đơn như thế nào?')">
                                        <i class="fas fa-file-invoice"></i> Tạo hóa đơn
                                    </button>
                                    <button type="button" class="suggest-btn" @click="useQuick('Quy trình đặt cọc ra sao?')">
                                        <i class="fas fa-credit-card"></i> Quy trình đặt cọc
                                    </button>
                                    <button type="button" class="suggest-btn" @click="useQuick('Thông tin liên hệ ZoroRMS?')">
                                        <i class="fas fa-phone"></i> Thông tin liên hệ
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template x-for="(msg, idx) in messages" :key="idx">
                            <div :class="'chat-message chat-message-' + msg.role">
                                <div class="chat-message-avatar">
                                    <i :class="msg.role === 'user' ? 'fas fa-user' : 'fas fa-robot'"></i>
                                </div>
                                <div class="chat-message-content-wrapper">
                                    <div class="chat-message-content" x-text="msg.content"></div>
                                    <div class="chat-message-time" x-text="formatTime()"></div>
                                </div>
                            </div>
                        </template>

                        <template x-if="isLoading">
                            <div class="chat-message chat-message-ai">
                                <div class="chat-message-avatar">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="chat-message-content-wrapper">
                                    <div class="chat-message-content chat-loading">
                                        <span></span><span></span><span></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Input Area -->
                    <div class="chat-input-area">
                        <form class="chat-form" @submit.prevent="sendMessage">
                            <div class="input-wrapper">
                                <textarea 
                                    x-model="userMessage"
                                    @keydown.enter.prevent="sendMessage"
                                    class="form-control chat-input-field" 
                                    rows="1"
                                    placeholder="Nhập câu hỏi của bạn..."
                                    style="resize:none"
                                ></textarea>
                                <button type="submit" class="btn btn-primary chat-send-btn" :disabled="isLoading" title="Gửi (Enter)">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                            <div class="input-hint">
                                <small>
                                    <i class="fas fa-keyboard"></i> Nhấn <kbd>Enter</kbd> để gửi, <kbd>Shift + Enter</kbd> để xuống dòng
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@push('styles')
<style>
/* Chat Container */
.chat-container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 280px);
    min-height: 600px;
    background: #f8f9fa;
}

/* Chat Icon Wrapper */
.chat-icon-wrapper {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

/* Messages Area */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    scroll-behavior: smooth;
}

/* Empty State */
.chat-empty-state {
    text-align: center;
    padding: 60px 20px;
    max-width: 600px;
    margin: 0 auto;
}

.empty-state-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 24px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 48px;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
}

.empty-state-title {
    font-size: 24px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 12px;
}

.empty-state-text {
    color: #718096;
    margin-bottom: 32px;
    font-size: 15px;
}

/* Suggested Questions */
.suggested-questions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    max-width: 800px;
    margin: 0 auto;
}

.suggest-btn {
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    background: white;
    color: #4a5568;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 8px;
}

.suggest-btn:hover {
    border-color: #667eea;
    background: #f7fafc;
    color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.suggest-btn i {
    font-size: 16px;
}

/* Messages */
.chat-message {
    margin-bottom: 20px;
    animation: fadeInUp 0.3s ease;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chat-message-user {
    flex-direction: row-reverse;
}

.chat-message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
}

.chat-message-user .chat-message-avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.chat-message-ai .chat-message-avatar {
    background: #e2e8f0;
    color: #4a5568;
}

.chat-message-content-wrapper {
    max-width: 70%;
    display: flex;
    flex-direction: column;
}

.chat-message-content {
    padding: 12px 16px;
    border-radius: 12px;
    word-wrap: break-word;
    line-height: 1.6;
    font-size: 14px;
}

.chat-message-user .chat-message-content {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.chat-message-ai .chat-message-content {
    background: white;
    color: #2d3748;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.chat-message-time {
    font-size: 11px;
    color: #a0aec0;
    margin-top: 4px;
    padding: 0 4px;
    text-align: right;
}

.chat-message-user .chat-message-time {
    text-align: left;
}

/* Input Area */
.chat-input-area {
    background: white;
    border-top: 1px solid #e2e8f0;
    padding: 16px 24px;
}

.chat-form {
    max-width: 100%;
}

.input-wrapper {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}

.chat-input-field {
    flex: 1;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 14px;
    transition: all 0.2s;
    min-height: 48px;
    max-height: 120px;
}

.chat-input-field:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.chat-send-btn {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    transition: all 0.2s;
}

.chat-send-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.chat-send-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.input-hint {
    margin-top: 10px;
    padding-left: 4px;
}

.input-hint small {
    font-size: 13px !important;
    color: #4a5568 !important;
    font-weight: 500;
}

.input-hint i {
    color: #667eea;
    margin-right: 4px;
}

.input-hint kbd {
    background: #f7fafc;
    border: 1px solid #cbd5e0;
    border-radius: 4px;
    padding: 3px 8px;
    font-size: 12px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-weight: 600;
    color: #2d3748;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

/* Loading */
.chat-loading {
    display: inline-flex;
    gap: 6px;
    padding: 12px 16px;
}

.chat-loading span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #a0aec0;
    animation: bounce 1.4s infinite ease-in-out both;
}

.chat-loading span:nth-child(1) { animation-delay: -0.32s; }
.chat-loading span:nth-child(2) { animation-delay: -0.16s; }

@keyframes bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

/* Connection Status */
#connectionStatus {
    font-size: 13px;
    padding: 6px 12px;
    border-radius: 20px;
}

#connectionStatus .fa-circle {
    font-size: 8px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Scrollbar */
.chat-messages::-webkit-scrollbar {
    width: 8px;
}

.chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 4px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

/* Error Message */
.chat-error {
    background: #fee;
    color: #c33;
    border: 1px solid #fcc;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 13px;
}

/* Responsive */
@media (max-width: 768px) {
    .chat-container {
        height: calc(100vh - 240px);
        min-height: 500px;
    }
    
    .chat-message-content-wrapper {
        max-width: 85%;
    }
    
    .suggested-questions {
        grid-template-columns: 1fr;
    }
    
    .chat-icon-wrapper {
        width: 50px;
        height: 50px;
        font-size: 24px;
    }
}
</style>
@endpush

@push('scripts')
<script>
function aiChat() {
    return {
        messages: [],
        userMessage: '',
        isLoading: false,
        
        init() {
            // Check connection on load
            this.checkConnection();
        },
        
        formatTime() {
            return new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        },
        
        async checkConnection() {
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
                const statusEl = document.getElementById('connectionStatus');
                const textEl = document.getElementById('connectionText');
                
                if (data.success) {
                    statusEl.className = 'badge bg-success';
                    textEl.textContent = 'Đã kết nối';
                } else {
                    statusEl.className = 'badge bg-danger';
                    textEl.textContent = 'Lỗi kết nối';
                }
            } catch (error) {
                const statusEl = document.getElementById('connectionStatus');
                const textEl = document.getElementById('connectionText');
                statusEl.className = 'badge bg-danger';
                textEl.textContent = 'Lỗi kết nối';
            }
        },
        
        useQuick(text) {
            this.userMessage = text;
            this.$nextTick(() => {
                this.sendMessage();
            });
        },
        
        clearChat() {
            if (confirm('Bạn có chắc muốn xóa toàn bộ lịch sử chat?')) {
                this.messages = [];
                this.userMessage = '';
            }
        },
        
        async sendMessage() {
            const message = this.userMessage.trim();
            
            if (!message || this.isLoading) {
                return;
            }
            
            this.messages.push({role: 'user', content: message});
            this.userMessage = '';
            this.isLoading = true;
            
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) container.scrollTop = container.scrollHeight;
            });
            
            try {
                const response = await fetch('{{ route("chat.send") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        message: message,
                        conversation_history: this.messages.slice(0, -1)
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.messages.push({role: 'ai', content: data.message});
                } else {
                    this.messages.push({role: 'ai', content: '⚠️ Lỗi: ' + (data.error || 'Unknown error')});
                }
            } catch (error) {
                this.messages.push({role: 'ai', content: '⚠️ Đã xảy ra lỗi: ' + error.message});
            } finally {
                this.isLoading = false;
                this.$nextTick(() => {
                    const container = this.$refs.messagesContainer;
                    if (container) container.scrollTop = container.scrollHeight;
                });
            }
        }
    }
}

// Add to window for onclick handlers
window.aiChatInstance = null;
document.addEventListener('Alpine.init', () => {
    const elem = document.querySelector('[x-data*="aiChat"]');
    if (elem) window.aiChatInstance = elem.__x;
});
</script>
@endpush
@endsection
