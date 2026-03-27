<div x-data="aiChat()" x-init="init()" class="ai-chat-container" style="max-width:420px;">
    <style>
        .ai-chat-container{font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.12);overflow:hidden;background:#fff}
        .chat-header{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff}
        .messages-container{max-height:380px;overflow:auto;padding:12px;background:#f8f9fa;display:flex;flex-direction:column;gap:10px}
        .message{display:flex;gap:10px}
        .message-content{padding:10px;border-radius:8px;background:#fff;border:1px solid #e6e6e6;max-width:78%}
        .user-message .message-content{background:#667eea;color:#fff;margin-left:auto}
        .ai-message .message-content{background:#fff;color:#333;margin-right:auto}
        .input-area{padding:12px;background:#fff;border-top:1px solid #e6e6e6}
        .input-area textarea{width:100%;min-height:50px;padding:10px;border:1px solid #ddd;border-radius:6px}
        .send-row{display:flex;gap:8px;margin-top:8px}
        .send-btn{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer}
        .quick-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
        .quick-btn{background:#f0f0f0;border:1px solid #ddd;padding:6px 10px;border-radius:18px;cursor:pointer}
        .tool-call, .tool-result{background:rgba(0,0,0,0.03);padding:6px;border-radius:6px;margin-top:8px;font-size:0.85rem}
    </style>

    <div class="chat-header">
        <div>
            <strong>AI Assistant</strong>
            <div style="font-size:0.85rem;opacity:0.9">Gemini Flash 2.5</div>
        </div>
        <button type="button" @click="clearChat" style="background:rgba(255,255,255,0.15);border:none;color:white;padding:6px 8px;border-radius:6px">Clear</button>
    </div>

    <div class="messages-container" x-ref="messagesContainer">
        <template x-if="messages.length === 0">
            <div style="text-align:center;padding:12px;background:#fff;border-radius:8px;border:1px dashed #e6e6e6;color:#666">
                <div style="font-weight:600;margin-bottom:6px">👋 Xin chào</div>
                <div style="font-size:0.9rem">Gõ yêu cầu của bạn, ví dụ: "Tạo bất động sản mới tại 123 Nguyễn Huệ"</div>
            </div>
        </template>

        <template x-for="(msg, idx) in messages" :key="idx">
            <div :class="msg.role === 'user' ? 'message user-message' : 'message ai-message'">
                <div class="message-content" x-html="formatMessage(msg.content)"></div>
                <div style="width:100%">
                    <template x-if="msg.tool_calls && msg.tool_calls.length">
                        <div class="tool-call">
                            <strong>🔧 Actions:</strong>
                            <template x-for="(t, i) in msg.tool_calls" :key="i">
                                <div style="margin-top:6px;"><span style="background:#667eea;color:#fff;padding:2px 6px;border-radius:4px;font-weight:600">[[ t.name ]]</span>
                                    <pre style="white-space:pre-wrap;margin:6px 0 0 0;background:transparent;border:none;padding:0">[[ JSON.stringify(t.args, null, 2) ]]</pre>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="msg.tool_results && msg.tool_results.length">
                        <div class="tool-result">
                            <strong>📋 Results:</strong>
                            <template x-for="(r, i) in msg.tool_results" :key="i">
                                <div style="margin-top:6px;"><span style="background:#764ba2;color:#fff;padding:2px 6px;border-radius:4px;font-weight:600">[[ r.tool_name ]]</span>
                                    <pre style="white-space:pre-wrap;margin:6px 0 0 0;background:transparent;border:none;padding:0">[[ JSON.stringify(r.result, null, 2) ]]</pre>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="isLoading">
            <div class="message ai-message">
                <div class="message-content">Thinking... <span style="opacity:0.6">⏳</span></div>
            </div>
        </template>
    </div>

    <div class="input-area">
        <textarea x-model="userMessage" @keydown.ctrl.enter.prevent="sendMessage" placeholder="Gõ tin nhắn và nhấn Ctrl+Enter để gửi"></textarea>
        <div class="send-row">
            <button class="send-btn" type="button" @click="sendMessage" :disabled="isLoading">Send</button>
            <div class="quick-actions">
                <button class="quick-btn" type="button" @click="useQuick('List all properties')">List all properties</button>
                <button class="quick-btn" type="button" @click="useQuick('Create a new property named Apartment A at 123 Main St')">Create property</button>
            </div>
        </div>
    </div>

    <script>
        function aiChat(){
            return {
                messages: [],
                userMessage: '',
                isLoading: false,
                init(){},
                formatMessage(text){ if(!text) return ''; return text.replace(/\n/g, '<br>'); },
                useQuick(text){ this.userMessage = text; },
                clearChat(){ this.messages = []; this.userMessage = ''; },
                async sendMessage(){
                    if(!this.userMessage.trim() || this.isLoading) return;
                    const message = this.userMessage.trim();
                    this.messages.push({role:'user', content: message});
                    this.userMessage = '';
                    this.isLoading = true;
                    this.scrollToBottom();
                    try{
                        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                        const csrf = tokenMeta ? tokenMeta.getAttribute('content') : '';
                        const res = await fetch('/api/ai-chat/message', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            },
                            body: JSON.stringify({message: message, history: this.messages})
                        });
                        const data = await res.json();
                        if(data.success){
                            this.messages.push({role:'assistant', content: data.message, tool_calls: data.tool_calls || [], tool_results: data.tool_results || []});
                            if(data.history){
                                this.messages = data.history.map(h => ({role: h.role, content: h.content, tool_calls: h.tool_calls || [], tool_results: h.tool_results || []}));
                            }
                        } else {
                            this.messages.push({role:'assistant', content: data.message || 'Lỗi khi gọi AI'});
                        }
                    }catch(e){
                        this.messages.push({role:'assistant', content: 'Lỗi: ' + (e.message || e)});
                    }finally{
                        this.isLoading = false;
                        this.scrollToBottom();
                    }
                },
                scrollToBottom(){
                    setTimeout(()=>{
                        const el = this.$root ? this.$root.querySelector('.messages-container') : this.$el ? this.$el.querySelector('.messages-container') : document.querySelector('.messages-container');
                        if(el) el.scrollTop = el.scrollHeight;
                    }, 80);
                }
            }
        }
    </script>
</div>