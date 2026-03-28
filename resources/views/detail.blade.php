@extends('layouts.app')

@section('title', 'Chi tiết phòng trọ')

@section('content')
    @push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/detail.css') }}">
    @endpush

    @push('scripts')
    <script src="{{ asset('assets/js/detail.js') }}"></script>
    @endpush
    
    <!-- Room Detail Hero Section -->
    <section style="padding: 1px 0; background: #f8f9fa;">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}" style="color: #ff6b35;">Trang chủ</a></li>
                    <li class="breadcrumb-item active">Chi tiết phòng trọ</li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Left Column - Images and Info -->
                <div class="col-lg-8">
                    <!-- Image Gallery -->
                    <div class="room-gallery mb-4">
                        <div class="main-image mb-3">
                            <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?ixlib=rb-4.0.3&w=800&h=400&fit=crop" 
                                 class="img-fluid rounded main-room-image" alt="Phòng trọ chính">
                        </div>
                        <div class="row">
                            <div class="col-3">
                                <img src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&w=200&h=150&fit=crop" 
                                     class="img-fluid rounded gallery-thumb" alt="Ảnh 1">
                            </div>
                            <div class="col-3">
                                <img src="https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&w=200&h=150&fit=crop" 
                                     class="img-fluid rounded gallery-thumb" alt="Ảnh 2">
                            </div>
                            <div class="col-3">
                                <img src="https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?ixlib=rb-4.0.3&w=200&h=150&fit=crop" 
                                     class="img-fluid rounded gallery-thumb" alt="Ảnh 3">
                            </div>
                            <div class="col-3">
                                <div class="gallery-more d-flex align-items-center justify-content-center rounded bg-dark text-white">
                                    <span><i class="fas fa-images me-2"></i>+5 ảnh</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Room Title and Price -->
                    <div class="room-header mb-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h1 class="h2 mb-2">Phòng trọ cao cấp Cầu Giấy - Đầy đủ tiện nghi</h1>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-map-marker-alt me-2"></i>123 Đường Cầu Giấy, Phường Dịch Vọng, Quận Cầu Giấy, Hà Nội
                                </p>
                                <div class="room-meta d-flex gap-4">
                                    <span><i class="fas fa-expand-arrows-alt me-1"></i>25m²</span>
                                    <span><i class="fas fa-users me-1"></i>2 người</span>
                                    <span><i class="fas fa-calendar-alt me-1"></i>Đăng 2 ngày trước</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="price-tag-large mb-2">2.5 triệu/tháng</div>
                                <small class="text-muted">Chưa bao gồm phí dịch vụ</small>
                            </div>
                        </div>
                    </div>

                    <!-- Room Features -->
                    <div class="room-features mb-4">
                        <h4 class="mb-3">Tiện nghi phòng trọ</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Wifi miễn phí</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Điều hòa</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Nóng lạnh</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Tủ lạnh</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Máy giặt chung</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Bảo vệ 24/7</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Thang máy</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Gửi xe miễn phí</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="room-description mb-4">
                        <h4 class="mb-3">Mô tả chi tiết</h4>
                        <div class="description-content">
                            <p>Phòng trọ cao cấp tại Cầu Giấy với đầy đủ tiện nghi hiện đại. Phòng được thiết kế thoáng mát, 
                            sạch sẽ với ánh sáng tự nhiên tốt. Vị trí thuận lợi gần các trường đại học, trung tâm thương mại 
                            và các tuyến xe bus.</p>
                            
                            <p>Phòng bao gồm:</p>
                            <ul>
                                <li>Giường đôi với nệm cao cấp</li>
                                <li>Tủ quần áo lớn 3 cánh</li>
                                <li>Bàn học và ghế</li>
                                <li>Tủ lạnh mini</li>
                                <li>Điều hòa Inverter tiết kiệm điện</li>
                                <li>Nhà vệ sinh riêng với bình nóng lạnh</li>
                            </ul>
                            
                            <p>Khu vực xung quanh có đầy đủ tiện ích: siêu thị, nhà hàng, quán ăn, ngân hàng, bệnh viện. 
                            Giao thông thuận lợi với nhiều tuyến xe bus đi các quận trung tâm.</p>
                        </div>
                    </div>

                    <!-- Location Map -->
                    <div class="room-location mb-4">
                        <h4 class="mb-3">Vị trí</h4>
                        <div class="map-container bg-light rounded p-4 text-center">
                            <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Bản đồ sẽ được hiển thị ở đây</p>
                            <small class="text-muted">123 Đường Cầu Giấy, Phường Dịch Vọng, Quận Cầu Giấy, Hà Nội</small>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Contact and Actions -->
                <div class="col-lg-4">
                    <div class="contact-card sticky-top">
                        <!-- Owner Info -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&w=60&h=60&fit=crop&crop=face" 
                                         class="rounded-circle me-3" width="60" height="60" alt="Chủ trọ">
                                    <div>
                                        <h6 class="mb-1">Anh Minh</h6>
                                        <small class="text-muted">Chủ trọ</small>
                                        <div class="text-warning small">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <span class="text-muted ms-1">(4.8)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-success btn-lg">
                                        <i class="fas fa-phone me-2"></i>0987 654 321
                                    </button>
                                    <button class="btn btn-primary">
                                        <i class="fab fa-facebook-messenger me-2"></i>Chat Messenger
                                    </button>
                                    <a href="{{ route('tenant.booking', $id ?? 1) }}" class="btn btn-info">
                                        <i class="fa fa-calendar me-2"></i>Hẹn lịch
                                    </a>
                                   
                                </div>
                            </div>
                        </div>

                        <!-- Price Info -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="card-title">Thông tin giá thuê</h6>
                                <div class="price-breakdown">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Tiền phòng:</span>
                                        <strong>2.500.000đ</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Tiền điện:</span>
                                        <span>3.500đ/kWh</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Tiền nước:</span>
                                        <span>100.000đ/người</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Tiền cọc:</span>
                                        <span>1 tháng</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Tổng chi phí hàng tháng:</strong>
                                        <strong class="text-danger">~2.800.000đ</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Thao tác nhanh</h6>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-danger">
                                        <i class="fas fa-heart me-2"></i>Yêu thích
                                    </button>
                                    <button class="btn btn-outline-primary">
                                        <i class="fas fa-share me-2"></i>Chia sẻ
                                    </button>
                                    <button class="btn btn-outline-warning">
                                        <i class="fas fa-flag me-2"></i>Báo cáo tin
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Rooms -->
    <section class="py-5 bg-light">
        <div class="container">
            <h3 class="section-title">Phòng trọ tương tự</h3>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card room-card">
                        <a href="{{ route('detail', 4) }}" class="text-decoration-none text-dark">
                            <img src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&w=400&h=200&fit=crop" class="room-image" alt="Phòng trọ">
                            <div class="card-body">
                                <div class="price-tag mb-2">2.2 triệu/tháng</div>
                                <h6 class="card-title">Phòng trọ Cầu Giấy gần ĐH Thương Mại</h6>
                                <p class="text-muted small">
                                    <i class="fas fa-map-marker-alt me-1"></i>Cầu Giấy, Hà Nội
                                </p>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><i class="fas fa-expand-arrows-alt me-1"></i>22m²</span>
                                    <span><i class="fas fa-users me-1"></i>2 người</span>
                                    <span><i class="fas fa-wifi me-1"></i>Wifi</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card room-card">
                        <a href="{{ route('detail', 5) }}" class="text-decoration-none text-dark">
                            <img src="https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?ixlib=rb-4.0.3&w=400&h=200&fit=crop" class="room-image" alt="Phòng trọ">
                            <div class="card-body">
                                <div class="price-tag mb-2">2.8 triệu/tháng</div>
                                <h6 class="card-title">Studio cao cấp full nội thất</h6>
                                <p class="text-muted small">
                                    <i class="fas fa-map-marker-alt me-1"></i>Cầu Giấy, Hà Nội
                                </p>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><i class="fas fa-expand-arrows-alt me-1"></i>28m²</span>
                                    <span><i class="fas fa-users me-1"></i>2 người</span>
                                    <span><i class="fas fa-kitchen-set me-1"></i>Bếp</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card room-card">
                        <a href="{{ route('detail', 6) }}" class="text-decoration-none text-dark">
                            <img src="https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?ixlib=rb-4.0.3&w=400&h=200&fit=crop" class="room-image" alt="Phòng trọ">
                            <div class="card-body">
                                <div class="price-tag mb-2">2.0 triệu/tháng</div>
                                <h6 class="card-title">Phòng trọ sinh viên giá rẻ</h6>
                                <p class="text-muted small">
                                    <i class="fas fa-map-marker-alt me-1"></i>Cầu Giấy, Hà Nội
                                </p>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><i class="fas fa-expand-arrows-alt me-1"></i>20m²</span>
                                    <span><i class="fas fa-users me-1"></i>1 người</span>
                                    <span><i class="fas fa-motorcycle me-1"></i>Gửi xe</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Floating Buttons -->
    <div class="floating-buttons">
        <a href="#" class="floating-btn" title="Chat hỗ trợ">
            <i class="fas fa-comments"></i>
        </a>
        <a href="tel:19001234" class="floating-btn" title="Gọi điện">
            <i class="fas fa-phone"></i>
        </a>
    </div>

@endsection

