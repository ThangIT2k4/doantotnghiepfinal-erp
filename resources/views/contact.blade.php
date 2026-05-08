@extends('layouts.app')

@section('title', 'Liên hệ')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/contact.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/contact.js') }}?v={{ time() }}"></script>
@endpush

@section('content')
<div class="contact-container">
    <div class="container">
        <!-- Page Header -->
        <div class="contact-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <div>
                    <h1 class="page-title">Liên hệ với chúng tôi</h1>
                    <p class="page-subtitle">Chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7</p>
                </div>
            </div>
        </div>

        <!-- Contact Methods -->
        <div class="contact-methods">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="contact-card">
                        <div class="contact-icon phone">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h4>Hotline</h4>
                        <p>Gọi ngay để được tư vấn</p>
                        <div class="contact-info">
                            <a href="tel:1900123456" class="contact-link">1900 123 456</a>
                            <small>Miễn phí từ 8:00 - 22:00</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="contact-card">
                        <div class="contact-icon email">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Email</h4>
                        <p>Gửi email cho chúng tôi</p>
                        <div class="contact-info">
                            <a href="mailto:support@phongtro24.com" class="contact-link">support@phongtro24.com</a>
                            <small>Phản hồi trong 24h</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="contact-card">
                        <div class="contact-icon chat">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4>Live Chat</h4>
                        <p>Trò chuyện trực tiếp</p>
                        <div class="contact-info">
                            <button class="contact-link btn-chat" onclick="openLiveChat()">Bắt đầu chat</button>
                            <small>Online 8:00 - 22:00</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form & Info -->
        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="contact-form-section">
                    <h3>Gửi tin nhắn cho chúng tôi</h3>
                    <form id="contactForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contactName" class="form-label">Họ và tên <span class="required">*</span></label>
                                <input type="text" class="form-control" id="contactName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contactPhone" class="form-label">Số điện thoại <span class="required">*</span></label>
                                <input type="tel" class="form-control" id="contactPhone" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="contactEmail" class="form-label">Email <span class="required">*</span></label>
                            <input type="email" class="form-control" id="contactEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="contactSubject" class="form-label">Chủ đề</label>
                            <select class="form-select" id="contactSubject">
                                <option value="">Chọn chủ đề</option>
                                <option value="support">Hỗ trợ kỹ thuật</option>
                                <option value="rental">Tư vấn thuê nhà</option>
                                <option value="complaint">Khiếu nại</option>
                                <option value="suggestion">Góp ý</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="contactMessage" class="form-label">Tin nhắn <span class="required">*</span></label>
                            <textarea class="form-control" id="contactMessage" rows="6" placeholder="Nhập nội dung tin nhắn của bạn..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Gửi tin nhắn
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-info-section">
                    <h3>Thông tin liên hệ</h3>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <h5>Địa chỉ văn phòng</h5>
                            <p>123 Đường Láng, Phường Láng Thượng, Quận Đống Đa, Hà Nội</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-content">
                            <h5>Giờ làm việc</h5>
                            <p>Thứ 2 - Thứ 6: 8:00 - 18:00<br>
                               Thứ 7 - Chủ nhật: 8:00 - 17:00</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="info-content">
                            <h5>Hỗ trợ khẩn cấp</h5>
                            <p>Hotline 24/7: <a href="tel:0987654321">0987 654 321</a><br>
                               Email: emergency@phongtro24.com</p>
                        </div>
                    </div>

                    <div class="social-links">
                        <h5>Kết nối với chúng tôi</h5>
                        <div class="social-buttons">
                            <a href="#" class="social-btn facebook">
                                <i class="fab fa-facebook-f"></i>
                                Facebook
                            </a>
                            <a href="#" class="social-btn zalo">
                                <i class="fas fa-comment"></i>
                                Zalo
                            </a>
                            <a href="#" class="social-btn telegram">
                                <i class="fab fa-telegram"></i>
                                Telegram
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map Section -->
        <div class="map-section">
            <h3>Vị trí văn phòng</h3>
            <div class="map-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724.6962917785285!2d105.80730731533309!3d21.013715993648984!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135ab86cece9ac1%3A0x5438daa0b2fb8579!2zMTIzIFAuIEzDoW5nLCBMw6FuZyBUaMaw4bujbmcsIMSQ4buRbmcgxJBhLCBIw6AgTuG7mWksIFZp4buHdCBOYW0!5e0!3m2!1svi!2s!4v1640000000000!5m2!1svi!2s" 
                    width="100%" 
                    height="300" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <h3>Câu hỏi thường gặp</h3>
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            Làm thế nào để đăng tin cho thuê phòng?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Bạn có thể đăng tin cho thuê phòng bằng cách click vào nút "Đăng tin" trên header, sau đó điền đầy đủ thông tin về phòng trọ của mình.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Phí dịch vụ của PhòngTrọ24 là bao nhiêu?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            PhòngTrọ24 hoàn toàn miễn phí cho người tìm phòng. Chủ nhà chỉ trả phí khi có giao dịch thành công.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            Làm sao để đảm bảo an toàn khi thuê phòng?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Hãy luôn xem phòng trực tiếp, kiểm tra giấy tờ chủ nhà, đọc kỹ hợp đồng và thanh toán qua các kênh chính thức.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Live Chat Modal -->
<div class="modal fade" id="liveChatModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-comments me-2"></i>
                    Live Chat - Hỗ trợ trực tuyến
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="chat-container">
                    <div class="chat-messages" id="chatMessages">
                        <div class="message bot">
                            <div class="message-avatar">
                                <img src="https://ui-avatars.com/api/?name=Support&background=ff6b35&color=fff&size=40" alt="Support">
                            </div>
                            <div class="message-content">
                                <div class="message-text">
                                    Xin chào! Tôi là trợ lý ảo của PhòngTrọ24. Tôi có thể giúp gì cho bạn hôm nay?
                                </div>
                                <div class="message-time">Vừa xong</div>
                            </div>
                        </div>
                    </div>
                    <div class="chat-input">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Nhập tin nhắn..." id="chatInput">
                            <button class="btn btn-primary" onclick="sendMessage()">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
