@extends('layouts.app')

@section('title', 'Yêu cầu sửa chữa')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/maintenance.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/user/maintenance.js') }}?v={{ time() }}"></script>
@endpush

@section('content')
<div class="maintenance-container">
    <div class="container">
        <!-- Page Header -->
        <div class="maintenance-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div>
                            <h1 class="page-title">Yêu cầu sửa chữa</h1>
                            <p class="page-subtitle">Tạo và theo dõi các yêu cầu bảo trì, sửa chữa</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="header-actions">
                        <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Về Dashboard
                        </a>
                        <button class="btn btn-primary ms-2" onclick="openCreateRequestModal()">
                            <i class="fas fa-plus me-2"></i>Tạo yêu cầu
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-section">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>3</h3>
                            <p>Chờ xử lý</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card processing">
                        <div class="stat-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="stat-content">
                            <h3>2</h3>
                            <p>Đang sửa chữa</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card completed">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>8</h3>
                            <p>Hoàn thành</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card total">
                        <div class="stat-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="stat-content">
                            <h3>13</h3>
                            <p>Tổng yêu cầu</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Tìm kiếm theo mã yêu cầu..." id="searchInput">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="categoryFilter">
                        <option value="">Tất cả danh mục</option>
                        <option value="plumbing">Hệ thống nước</option>
                        <option value="electrical">Điện</option>
                        <option value="appliance">Thiết bị</option>
                        <option value="furniture">Nội thất</option>
                        <option value="other">Khác</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-status="all">Tất cả</button>
                        <button class="filter-tab" data-status="pending">Chờ xử lý</button>
                        <button class="filter-tab" data-status="processing">Đang sửa</button>
                        <button class="filter-tab" data-status="completed">Hoàn thành</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Requests List -->
        <div class="requests-list">
            <!-- Request Item 1 - Processing -->
            <div class="request-card" data-status="processing" data-category="plumbing">
                <div class="request-status processing">
                    <i class="fas fa-cog fa-spin"></i>
                    <span>Đang sửa chữa</span>
                </div>
                <div class="request-content">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="request-info">
                                <div class="request-id">YC001</div>
                                <div class="request-date">20/12/2023</div>
                                <div class="request-category plumbing">
                                    <i class="fas fa-tint"></i>
                                    <span>Hệ thống nước</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="property-info">
                                <h4 class="property-name">Phòng trọ cao cấp Cầu Giấy</h4>
                                <p class="property-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    123 Đường Cầu Giấy, Hà Nội
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="request-details">
                                <div class="issue-title">Vòi nước bị rò rỉ</div>
                                <div class="issue-description">Vòi nước trong phòng tắm bị rò rỉ, cần thay thế gấp</div>
                                <div class="priority high">
                                    <i class="fas fa-exclamation-circle"></i>
                                    Cao
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="technician-info">
                                <div class="tech-avatar">
                                    <img src="https://ui-avatars.com/api/?name=Nguyen+Van+B&background=3b82f6&color=fff&size=40" alt="Kỹ thuật viên">
                                </div>
                                <div class="tech-name">Anh Minh</div>
                                <div class="tech-phone">0987 654 321</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="request-actions">
                    <button class="btn btn-outline-primary btn-sm" onclick="trackRequest('YC001')">
                        <i class="fas fa-eye me-1"></i>Theo dõi
                    </button>
                    <a href="tel:0987654321" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-phone me-1"></i>Gọi KTV
                    </a>
                    <button class="btn btn-outline-warning btn-sm" onclick="editRequest('YC001')">
                        <i class="fas fa-edit me-1"></i>Chỉnh sửa
                    </button>
                </div>
            </div>

            <!-- Request Item 2 - Pending -->
            <div class="request-card" data-status="pending" data-category="electrical">
                <div class="request-status pending">
                    <i class="fas fa-clock"></i>
                    <span>Chờ xử lý</span>
                </div>
                <div class="request-content">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="request-info">
                                <div class="request-id">YC002</div>
                                <div class="request-date">22/12/2023</div>
                                <div class="request-category electrical">
                                    <i class="fas fa-bolt"></i>
                                    <span>Điện</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="property-info">
                                <h4 class="property-name">Homestay Hạnh Đào</h4>
                                <p class="property-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    789 Đường Hạnh Đào, Hà Nội
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="request-details">
                                <div class="issue-title">Ổ cắm điện bị hỏng</div>
                                <div class="issue-description">Ổ cắm ở phòng ngủ không hoạt động, cần kiểm tra và sửa chữa</div>
                                <div class="priority medium">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Trung bình
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="technician-info">
                                <div class="tech-status">Chưa phân công</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="request-actions">
                    <button class="btn btn-outline-primary btn-sm" onclick="trackRequest('YC002')">
                        <i class="fas fa-eye me-1"></i>Theo dõi
                    </button>
                    <button class="btn btn-outline-warning btn-sm" onclick="editRequest('YC002')">
                        <i class="fas fa-edit me-1"></i>Chỉnh sửa
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="cancelRequest('YC002')">
                        <i class="fas fa-times me-1"></i>Hủy
                    </button>
                </div>
            </div>

            <!-- Request Item 3 - Completed -->
            <div class="request-card" data-status="completed" data-category="appliance">
                <div class="request-status completed">
                    <i class="fas fa-check-circle"></i>
                    <span>Hoàn thành</span>
                </div>
                <div class="request-content">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="request-info">
                                <div class="request-id">YC003</div>
                                <div class="request-date">15/12/2023</div>
                                <div class="request-category appliance">
                                    <i class="fas fa-tv"></i>
                                    <span>Thiết bị</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="property-info">
                                <h4 class="property-name">Phòng trọ cao cấp Cầu Giấy</h4>
                                <p class="property-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    123 Đường Cầu Giấy, Hà Nội
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="request-details">
                                <div class="issue-title">Máy lạnh không mát</div>
                                <div class="issue-description">Máy lạnh hoạt động nhưng không làm mát được</div>
                                <div class="priority low">
                                    <i class="fas fa-info-circle"></i>
                                    Thấp
                                </div>
                                <div class="completion-date">
                                    <i class="fas fa-calendar-check"></i>
                                    Hoàn thành: 18/12/2023
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="technician-info">
                                <div class="tech-avatar">
                                    <img src="https://ui-avatars.com/api/?name=Le+Van+C&background=10b981&color=fff&size=40" alt="Kỹ thuật viên">
                                </div>
                                <div class="tech-name">Anh Cường</div>
                                <div class="tech-rating">
                                    <div class="stars">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <span>5.0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="request-actions">
                    <button class="btn btn-outline-primary btn-sm" onclick="trackRequest('YC003')">
                        <i class="fas fa-eye me-1"></i>Xem chi tiết
                    </button>
                    <button class="btn btn-outline-success btn-sm" onclick="rateService('YC003')">
                        <i class="fas fa-star me-1"></i>Đánh giá
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="downloadReport('YC003')">
                        <i class="fas fa-download me-1"></i>Báo cáo
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div class="empty-state" style="display: none;">
                <div class="empty-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <h3>Không có yêu cầu nào</h3>
                <p>Bạn chưa có yêu cầu sửa chữa nào. Hãy tạo yêu cầu mới khi cần!</p>
                <button class="btn btn-primary" onclick="openCreateRequestModal()">
                    <i class="fas fa-plus me-2"></i>Tạo yêu cầu đầu tiên
                </button>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-section">
            <nav aria-label="Maintenance requests pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <span class="page-link">Trước</span>
                    </li>
                    <li class="page-item active">
                        <span class="page-link">1</span>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="#">2</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="#">Sau</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Create Request Modal -->
<div class="modal fade" id="createRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo yêu cầu sửa chữa mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createRequestForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="requestProperty" class="form-label">Chọn phòng <span class="required">*</span></label>
                            <select class="form-select" id="requestProperty" required>
                                <option value="">Chọn phòng cần sửa chữa</option>
                                <option value="room1">Phòng trọ cao cấp Cầu Giấy</option>
                                <option value="room2">Homestay Hạnh Đào</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="requestCategory" class="form-label">Danh mục <span class="required">*</span></label>
                            <select class="form-select" id="requestCategory" required>
                                <option value="">Chọn danh mục sửa chữa</option>
                                <option value="plumbing">🚿 Hệ thống nước</option>
                                <option value="electrical">⚡ Điện</option>
                                <option value="appliance">📺 Thiết bị</option>
                                <option value="furniture">🪑 Nội thất</option>
                                <option value="other">🔧 Khác</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="requestPriority" class="form-label">Mức độ ưu tiên <span class="required">*</span></label>
                            <select class="form-select" id="requestPriority" required>
                                <option value="">Chọn mức độ ưu tiên</option>
                                <option value="low">🟢 Thấp - Không gấp</option>
                                <option value="medium">🟡 Trung bình - Cần sửa sớm</option>
                                <option value="high">🔴 Cao - Khẩn cấp</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="requestTime" class="form-label">Thời gian mong muốn</label>
                            <select class="form-select" id="requestTime">
                                <option value="">Chọn thời gian</option>
                                <option value="morning">Sáng (8:00 - 12:00)</option>
                                <option value="afternoon">Chiều (13:00 - 17:00)</option>
                                <option value="evening">Tối (18:00 - 20:00)</option>
                                <option value="anytime">Bất kỳ lúc nào</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="requestTitle" class="form-label">Tiêu đề vấn đề <span class="required">*</span></label>
                        <input type="text" class="form-control" id="requestTitle" placeholder="Ví dụ: Vòi nước bị rò rỉ" required>
                    </div>
                    <div class="mb-3">
                        <label for="requestDescription" class="form-label">Mô tả chi tiết <span class="required">*</span></label>
                        <textarea class="form-control" id="requestDescription" rows="4" placeholder="Mô tả chi tiết vấn đề cần sửa chữa..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="requestImages" class="form-label">Hình ảnh (tùy chọn)</label>
                        <input type="file" class="form-control" id="requestImages" multiple accept="image/*">
                        <div class="form-text">Có thể tải lên nhiều hình ảnh để mô tả vấn đề</div>
                        <div id="imagePreview" class="image-preview"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="submitRequest()">
                    <i class="fas fa-paper-plane me-1"></i>Gửi yêu cầu
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Track Request Modal -->
<div class="modal fade" id="trackModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Theo dõi yêu cầu sửa chữa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="tracking-content" id="trackingContent">
                    <!-- Content will be loaded by JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-success" onclick="rateCurrentService()" id="rateServiceBtn" style="display: none;">
                    <i class="fas fa-star me-1"></i>Đánh giá dịch vụ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Rating Modal -->
<div class="modal fade" id="ratingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đánh giá dịch vụ sửa chữa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="rating-section">
                    <div class="mb-4">
                        <label class="form-label">Đánh giá chất lượng sửa chữa</label>
                        <div class="star-rating" id="qualityRating">
                            <i class="fas fa-star" data-rating="1"></i>
                            <i class="fas fa-star" data-rating="2"></i>
                            <i class="fas fa-star" data-rating="3"></i>
                            <i class="fas fa-star" data-rating="4"></i>
                            <i class="fas fa-star" data-rating="5"></i>
                        </div>
                        <div class="rating-text">Chưa đánh giá</div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Đánh giá thái độ kỹ thuật viên</label>
                        <div class="star-rating" id="serviceRating">
                            <i class="fas fa-star" data-rating="1"></i>
                            <i class="fas fa-star" data-rating="2"></i>
                            <i class="fas fa-star" data-rating="3"></i>
                            <i class="fas fa-star" data-rating="4"></i>
                            <i class="fas fa-star" data-rating="5"></i>
                        </div>
                        <div class="rating-text">Chưa đánh giá</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ratingComment" class="form-label">Nhận xét</label>
                        <textarea class="form-control" id="ratingComment" rows="4" placeholder="Chia sẻ trải nghiệm của bạn về dịch vụ sửa chữa..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Bạn có muốn sử dụng dịch vụ này lần sau?</label>
                        <div class="recommend-options">
                            <label class="recommend-option">
                                <input type="radio" name="recommend" value="yes">
                                <span class="option-text">
                                    <i class="fas fa-thumbs-up text-success"></i>
                                    Có, tôi sẽ giới thiệu
                                </span>
                            </label>
                            <label class="recommend-option">
                                <input type="radio" name="recommend" value="maybe">
                                <span class="option-text">
                                    <i class="fas fa-meh text-warning"></i>
                                    Có thể
                                </span>
                            </label>
                            <label class="recommend-option">
                                <input type="radio" name="recommend" value="no">
                                <span class="option-text">
                                    <i class="fas fa-thumbs-down text-danger"></i>
                                    Không
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="submitRating()">
                    <i class="fas fa-paper-plane me-1"></i>Gửi đánh giá
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="success-icon">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
                <h4 class="text-success mt-3" id="successTitle">Thành công!</h4>
                <p id="successMessage">Yêu cầu của bạn đã được gửi thành công.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">Đã hiểu</button>
            </div>
        </div>
    </div>
</div>
@endsection
