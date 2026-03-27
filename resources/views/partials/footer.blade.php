<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <!-- Company Info -->
            <div class="footer-section">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <span class="logo-text">ZoroRMS</span>
                </div>
                <p>Nền tảng tìm kiếm phòng trọ hàng đầu Việt Nam, kết nối chủ nhà và người thuê một cách nhanh chóng, an toàn.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-section">
                <h3>Liên kết nhanh</h3>
                <ul>
                    <li><a href="{{ route('home') }}">Trang chủ</a></li>
                    <li><a href="{{ route('property.index') }}">Tìm kiếm phòng</a></li>
                    <li><a href="#">Đăng tin cho thuê</a></li>
                    <li><a href="{{ route('news.index') }}">Tin tức</a></li>
                    <li><a href="{{ route('contact') }}">Hướng dẫn</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div class="footer-section">
                <h3>Hỗ trợ</h3>
                <ul>
                    <li><a href="{{ route('contact') }}">Câu hỏi thường gặp</a></li>
                    <li><a href="#">Chính sách bảo mật</a></li>
                    <li><a href="#">Điều khoản sử dụng</a></li>
                    <li><a href="#">Liên hệ</a></li>
                    <li><a href="#">Báo cáo vấn đề</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="footer-section">
                <h3>Liên hệ</h3>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>0123 456 789</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>support@phongtroya.vn</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Phố Huế, Hai Bà Trưng, Hà Nội</span>
                    </div>
                </div>
                
                <div class="newsletter">
                    <h4>Đăng ký nhận tin</h4>
                    <div class="newsletter-form">
                        <input type="email" placeholder="Email của bạn">
                        <button class="btn-primary">Đăng ký</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© 2024 PhòngTrọ24. Tất cả quyền được bảo lưu.</p>
            <p>Thiết kế bởi Lovable</p>
        </div>
    </div>
</footer>
