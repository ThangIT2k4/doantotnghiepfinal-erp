@extends('layouts.app')

@section('title', 'Dashboard - Quản lý cá nhân')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/dashboard.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script>
    // Pass dashboard data from server to JavaScript
    window.dashboardData = @json($dashboardData ?? []);
</script>
<script src="{{ asset('assets/js/user/dashboard.js') }}?v={{ time() }}"></script>
@endpush

@section('content')
@php
    // Debug: Kiểm tra dữ liệu từ controller
    if (!isset($dashboardData) || empty($dashboardData)) {
        \Illuminate\Support\Facades\Log::error('Dashboard data is empty or not set', [
            'dashboardData' => $dashboardData ?? null,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
        ]);
    }
@endphp
<div class="dashboard-container">
    <div class="container-fluid">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="welcome-section">
                        <div class="welcome-avatar">
                            @if($user->avatar)
                                <img src="{{ $user->avatar }}" alt="Avatar" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->full_name ?? 'User') }}&background=667eea&color=fff&size=60'">
                            @else
                                <img src="{{ asset('assets/image/logo2.svg') }}" alt="Avatar" style="width: 50px; height: 50px; object-fit: contain;">
                            @endif
                        </div>
                        <div class="welcome-text">
                            <h1 class="welcome-title">Xin chào, {{ $user->full_name ?? ($user->email ?? 'User') }}!</h1>
                            <p class="welcome-subtitle">Chào mừng bạn quay lại. Hôm nay là {{ \Carbon\Carbon::now()->locale('vi')->isoFormat('dddd, D [tháng] M, YYYY') }}</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Main Dashboard Content -->
        <div class="row">
            <!-- Left Column -->
            <div class="col-xl-8 col-lg-7 mb-4">
                <!-- Quick Access Cards -->
                <div class="quick-access-section">
                    <h3 class="section-title">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Truy cập nhanh
                    </h3>
                    <div class="row">
                        
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('tenant.profile') }}" class="quick-access-card">
                                <div class="card-icon">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Hồ sơ cá nhân</h4>
                                    <p>Xem/Cập nhật thông tin</p>
                                </div>
                                <div class="card-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('tenant.contracts.index') }}" class="quick-access-card">
                                <div class="card-icon contracts">
                                    <i class="fas fa-file-signature"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Hợp đồng</h4>
                                    <p>Xem hợp đồng thuê nhà</p>
                                    @if(($dashboardData['stats']['contracts']['total'] ?? 0) > 0)
                                        <div class="card-badge">{{ $dashboardData['stats']['contracts']['total'] }} hợp đồng</div>
                                    @endif
                                </div>
                                <div class="card-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('tenant.invoices.index') }}" class="quick-access-card">
                                <div class="card-icon invoices">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Hóa đơn</h4>
                                    <p>Thanh toán & lịch sử</p>
                                    @if(($dashboardData['stats']['invoices']['total'] ?? 0) > 0)
                                        <div class="card-badge">{{ $dashboardData['stats']['invoices']['total'] }} hóa đơn</div>
                                    @endif
                                </div>
                                <div class="card-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('tenant.payments.index') }}" class="quick-access-card">
                                <div class="card-icon payments">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Thanh toán</h4>
                                    <p>Thanh toán & lịch sử</p>
                                    @if(($dashboardData['stats']['invoices']['total'] ?? 0) > 0)
                                        <div class="card-badge">{{ $dashboardData['stats']['invoices']['total'] }} hóa đơn</div>
                                    @endif
                                </div>
                                <div class="card-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('tenant.notifications') }}" class="quick-access-card">
                                <div class="card-icon notifications">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Thông báo</h4>
                                    <p>Tin nhắn & cập nhật</p>
                                    @if(($dashboardData['stats']['notifications']['unread'] ?? 0) > 0)
                                        <div class="card-badge">{{ $dashboardData['stats']['notifications']['unread'] }} mới</div>
                                    @endif
                                </div>
                                <div class="card-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('tenant.tickets.index') }}" class="quick-access-card">
                                <div class="card-icon maintenance">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Tickets</h4>
                                    <p>Yêu cầu sửa chữa & bảo trì</p>
                                    @if(($dashboardData['stats']['tickets']['open'] ?? 0) > 0)
                                        <div class="card-badge">{{ $dashboardData['stats']['tickets']['open'] }} đang xử lý</div>
                                    @endif
                                </div>
                                <div class="card-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('tenant.reviews.index') }}" class="quick-access-card">
                                <div class="card-icon reviews">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="card-content">
                                    <h4>Đánh giá</h4>
                                    <p>Đánh giá phòng trọ</p>
                                    @if(($dashboardData['stats']['reviews']['pending'] ?? 0) > 0)
                                        <div class="card-badge">{{ $dashboardData['stats']['reviews']['pending'] }} chưa đánh giá</div>
                                    @endif
                                </div>
                                <div class="card-arrow">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="recent-activity-section">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-history me-2"></i>
                            Hoạt động gần đây
                        </h3>
                        <a href="#" class="view-all-link">Xem tất cả</a>
                    </div>
                    <div class="activity-list">
                        @forelse($dashboardData['recentActivities'] ?? [] as $activity)
                            <div class="activity-item">
                                <div class="activity-icon {{ $activity['icon'] ?? 'info' }}">
                                    <i class="{{ $activity['icon_class'] ?? 'fas fa-info-circle' }}"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">{{ $activity['title'] ?? '' }}</div>
                                    <div class="activity-description">{{ $activity['description'] ?? '' }}</div>
                                    <div class="activity-time">{{ $activity['time'] ?? '' }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="activity-item">
                                <div class="activity-icon info">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">Chưa có hoạt động</div>
                                    <div class="activity-description">Sẽ hiển thị các hoạt động gần đây của bạn tại đây</div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-xl-4 col-lg-5 mb-4">
                <!-- Current Rental -->
                <div class="current-rental-section">
                    <h3 class="section-title">
                        <i class="fas fa-home me-2"></i>
                        Phòng hiện tại
                    </h3>
                    @if($dashboardData['currentRental'] && $dashboardData['currentRental']['lease'])
                        @php
                            $rental = $dashboardData['currentRental'];
                            $lease = $rental['lease'];
                            $property = $rental['property'];
                            $unit = $rental['unit'];
                        @endphp
                        <div class="rental-card">
                            <div class="rental-image">
                                @if($property && $property->image_url)
                                    <img src="{{ $property->image_url }}" alt="Phòng trọ" onerror="this.src='https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=300&h=200&fit=crop'">
                                @else
                                    <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=300&h=200&fit=crop" alt="Phòng trọ">
                                @endif
                                <div class="rental-status active">Đang thuê</div>
                            </div>
                            <div class="rental-info">
                                <h4 class="rental-title">{{ $property->name ?? 'Phòng trọ' }}</h4>
                                <p class="rental-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    {{ $property->address ?? 'N/A' }}
                                </p>
                                <div class="rental-details">
                                    <div class="detail-item">
                                        <span class="label">Giá thuê:</span>
                                        <span class="value">{{ number_format($lease->rent_amount ?? 0, 0, ',', '.') }} VNĐ/tháng</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Ngày thuê:</span>
                                        <span class="value">{{ $lease->start_date ? \Carbon\Carbon::parse($lease->start_date)->format('d/m/Y') : 'N/A' }}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Hết hạn:</span>
                                        <span class="value">{{ $lease->end_date ? \Carbon\Carbon::parse($lease->end_date)->format('d/m/Y') : 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="rental-actions">
                                    <a href="{{ route('tenant.contracts.index') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-file-contract me-1"></i>Xem hợp đồng
                                    </a>
                                    <button class="btn btn-outline-success btn-sm" onclick="showComingSoon('Gia hạn')">
                                        <i class="fas fa-refresh me-1"></i>Gia hạn
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rental-card">
                            <div class="rental-info text-center py-4">
                                <i class="fas fa-home fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Bạn chưa có phòng đang thuê</p>
                                {{-- <a href="{{ route('home') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search me-1"></i>Tìm phòng ngay
                                </a> --}}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Upcoming Events -->
                <div class="upcoming-events-section">
                    <h3 class="section-title">
                        <i class="fas fa-clock me-2"></i>
                        Sự kiện sắp tới
                    </h3>
                    <div class="events-list">
                        @forelse($dashboardData['upcomingEvents'] ?? [] as $event)
                            @php
                                $eventDate = \Carbon\Carbon::parse($event['date']);
                                $day = $eventDate->format('d');
                                $month = 'T' . $eventDate->format('m');
                            @endphp
                            <div class="event-item {{ $event['urgent'] ?? false ? 'urgent' : '' }}">
                                <div class="event-date">
                                    <div class="day">{{ $day }}</div>
                                    <div class="month">{{ $month }}</div>
                                </div>
                                <div class="event-content">
                                    <div class="event-title">{{ $event['title'] ?? '' }}</div>
                                    <div class="event-description">{{ $event['description'] ?? '' }}</div>
                                    <div class="event-time">
                                        <i class="fas fa-clock"></i>
                                        @if(isset($event['time']))
                                            {{ $event['time'] }}
                                        @elseif(isset($event['time_remaining']))
                                            @if($event['time_remaining'] > 0)
                                                {{ $event['time_remaining'] }} ngày nữa
                                            @elseif($event['time_remaining'] == 0)
                                                Hôm nay
                                            @else
                                                Quá hạn {{ abs($event['time_remaining']) }} ngày
                                            @endif
                                        @else
                                            {{ $eventDate->format('d/m/Y') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="event-action">
                                    @if(isset($event['action_url']))
                                        <a href="{{ $event['action_url'] }}" class="btn btn-sm {{ $event['urgent'] ?? false ? 'btn-danger' : 'btn-outline-primary' }}">
                                            <i class="fas fa-{{ $event['type'] === 'invoice' ? 'credit-card' : ($event['type'] === 'appointment' ? 'eye' : 'file-contract') }}"></i>
                                        </a>
                                    @else
                                        <button class="btn btn-sm btn-outline-primary" onclick="showComingSoon('{{ $event['title'] ?? '' }}')">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="event-item">
                                <div class="event-content text-center py-3">
                                    <p class="text-muted mb-0">Không có sự kiện sắp tới</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="quick-stats-section">
                    <h3 class="section-title">
                        <i class="fas fa-chart-pie me-2"></i>
                        Thống kê nhanh
                    </h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">{{ $dashboardData['quickStats']['completed_appointments'] ?? 0 }}</div>
                                <div class="stat-label">Lịch hẹn đã hoàn thành</div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">{{ number_format($dashboardData['quickStats']['average_rating'] ?? 0, 1) }}</div>
                                <div class="stat-label">Đánh giá trung bình</div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-money-bill"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">
                                    @php
                                        $totalPaid = $dashboardData['quickStats']['total_paid'] ?? 0;
                                        if ($totalPaid >= 1000000000) {
                                            echo number_format($totalPaid / 1000000000, 1) . 'B';
                                        } elseif ($totalPaid >= 1000000) {
                                            echo number_format($totalPaid / 1000000, 1) . 'M';
                                        } elseif ($totalPaid >= 1000) {
                                            echo number_format($totalPaid / 1000, 0) . 'K';
                                        } else {
                                            echo number_format($totalPaid, 0);
                                        }
                                    @endphp
                                </div>
                                <div class="stat-label">Tổng đã thanh toán (VNĐ)</div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="stat-info">
                                <div class="stat-value">{{ $dashboardData['quickStats']['total_rooms_rented'] ?? 0 }}</div>
                                <div class="stat-label">Phòng đã thuê</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Coming Soon Modal -->
<div class="modal fade" id="comingSoonModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="coming-soon-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h4 class="mt-3">Tính năng sắp ra mắt!</h4>
                <p id="comingSoonMessage">Chức năng này đang được phát triển và sẽ có mặt trong phiên bản tiếp theo.</p>
                <div class="coming-soon-features">
                    <div class="feature-item">
                        <i class="fas fa-check text-success"></i>
                        <span>Giao diện thân thiện</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check text-success"></i>
                        <span>Tính năng đầy đủ</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check text-success"></i>
                        <span>Bảo mật cao</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đã hiểu</button>
            </div>
        </div>
    </div>
</div>
@endsection

