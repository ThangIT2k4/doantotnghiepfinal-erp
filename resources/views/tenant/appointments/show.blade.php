@extends('layouts.app')

@section('title', 'Chi tiết lịch đặt')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/viewings-show.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="show-container">
<div class="container">
        <!-- Header -->
        <div class="show-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="header-content">
                        <div class="header-icon">
                        <i class="fas fa-calendar-check"></i>
                        </div>
                        <div>
                            <h1 class="page-title">Chi tiết lịch đặt</h1>
                            <p class="page-subtitle">Xem thông tin chi tiết về lịch hẹn xem phòng của bạn</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                        <a href="{{ route('tenant.appointments') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Viewing Details -->
                <div class="modern-card">
                        <div class="card-header">
                        <h5 class="card-title">
                                <i class="fas fa-info-circle"></i>
                                Thông tin lịch đặt
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">Mã lịch đặt:</label>
                                        <span class="info-value">#{{ $viewing->id }}</span>
                                    </div>
                                    <div class="info-item">
                                    <label class="info-label">
                                        <i class="fas fa-info-circle text-primary"></i>
                                        Trạng thái:
                                    </label>
                                    <span class="status-badge-prominent" style="display: inline-block; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 700; background: linear-gradient(135deg, #f59e0b, #d97706); color: white; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);">
                                        <i class="fas fa-clock me-1"></i>
                                            {{ $viewing->getStatusText() }}
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Thời gian đặt:</label>
                                        <span class="info-value">{{ $viewing->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">Ngày xem:</label>
                                        <span class="info-value">{{ $viewing->schedule_at->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Giờ xem:</label>
                                        <span class="info-value">{{ $viewing->schedule_at->format('H:i') }}</span>
                                    </div>
                                    <div class="info-item">
                                    <label class="info-label">
                                        <i class="fas fa-hourglass-half text-success"></i>
                                        Thời gian còn lại:
                                    </label>
                                    <span class="time-remaining-prominent" id="timeRemaining" style="display: inline-block; padding: 6px 12px; border-radius: 15px; font-size: 0.9rem; font-weight: 600; background: linear-gradient(135deg, #10b981, #059669); color: white; box-shadow: 0 3px 10px rgba(16, 185, 129, 0.3);">
                                            @if($viewing->schedule_at > now())
                                            <i class="fas fa-clock me-1"></i>
                                                {{ $viewing->schedule_at->diffForHumans() }}
                                            @else
                                            <i class="fas fa-times me-1"></i>
                                            <span>Đã qua</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Property Information -->
                <div class="modern-card">
                        <div class="card-header">
                        <h5 class="card-title">
                                <i class="fas fa-building"></i>
                                Thông tin bất động sản
                            </h5>
                        </div>
                        <div class="card-body">
                        <div class="property-info-card">
                            <h6 class="fw-semibold" style="color: #1e40af; font-size: 1.2rem; margin-bottom: 15px;">{{ $viewing->property->name }}</h6>
                            
                            <div class="property-addresses" style="margin-bottom: 15px;">
                                @if($viewing->property->new_address && $viewing->property->new_address !== 'Chưa có địa chỉ mới')
                                    <p class="mb-1" style="color: #1e40af; font-size: 0.95rem;">
                                        <i class="fas fa-map-marker-alt text-success"></i>
                                        <strong>Địa chỉ mới (2025):</strong> {{ $viewing->property->new_address }}
                                    </p>
                                @endif
                                
                                @if($viewing->property->old_address && $viewing->property->old_address !== 'Chưa có địa chỉ cũ')
                                    <p class="mb-0" style="color: #1e40af; font-size: 0.95rem;">
                                        <i class="fas fa-map-marker-alt text-warning"></i>
                                        <strong>Địa chỉ cũ:</strong> {{ $viewing->property->old_address }}
                                    </p>
                                @endif
                                
                                @if((!$viewing->property->new_address || $viewing->property->new_address === 'Chưa có địa chỉ mới') && (!$viewing->property->old_address || $viewing->property->old_address === 'Chưa có địa chỉ cũ'))
                                    <p class="mb-0" style="color: #1e40af; font-size: 0.95rem;">
                                        <i class="fas fa-map-marker-alt text-muted"></i>
                                        Không có địa chỉ
                                    </p>
                                @endif
                            </div>
                            
                                    @if($viewing->property->description)
                                <p style="color: #1e40af;">{{ Str::limit($viewing->property->description, 200) }}</p>
                                    @endif
                                </div>
                        <div class="text-end">
                                    <a href="{{ route('property.show', $viewing->property->id) }}" class="btn btn-outline-primary" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                        Xem chi tiết
                                    </a>
                            </div>
                        </div>
                    </div>

                    <!-- Unit Information -->
                    @if($viewing->unit)
                <div class="modern-card">
                        <div class="card-header">
                        <h5 class="card-title">
                                <i class="fas fa-home"></i>
                                Thông tin phòng
                            </h5>
                        </div>
                        <div class="card-body">
                        <div class="unit-info-card">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label" style="color: #065f46;">Mã phòng:</label>
                                        <span class="info-value" style="color: #065f46; font-weight: 600;">{{ $viewing->unit->code }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label" style="color: #065f46;">Diện tích:</label>
                                        <span class="info-value" style="color: #065f46; font-weight: 600;">{{ $viewing->unit->area_m2 ?? 'N/A' }}m²</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label" style="color: #065f46;">Sức chứa:</label>
                                        <span class="info-value" style="color: #065f46; font-weight: 600;">{{ $viewing->unit->max_occupancy }} người</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label" style="color: #065f46;">Tầng:</label>
                                        <span class="info-value" style="color: #065f46; font-weight: 600;">Tầng {{ $viewing->unit->floor ?? 'N/A' }}</span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label" style="color: #065f46;">Giá thuê:</label>
                                        <span class="info-value" style="color: #065f46; font-weight: 700; font-size: 1.1rem;">
                                            {{ number_format($viewing->unit->base_rent, 0, ',', '.') }} VNĐ/tháng
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label" style="color: #065f46;">Tiền cọc:</label>
                                        <span class="info-value" style="color: #065f46; font-weight: 700; font-size: 1.1rem;">
                                            {{ number_format($viewing->unit->deposit_amount ?? $viewing->unit->base_rent, 0, ',', '.') }} VNĐ
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label" style="color: #065f46;">Trạng thái:</label>
                                        <span style="display: inline-block; padding: 6px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: 600; color: #28a745; border: 2px solid #28a745;">Trống</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Total Cost Summary -->
                            <div class="mt-3 pt-3" style="border-top: 1px solid rgba(16, 185, 129, 0.2);">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="info-item">
                                            <label class="info-label" style="color: #065f46; font-size: 1rem;">
                                                <i class="fas fa-calculator"></i> Tổng cần chuẩn bị:
                                            </label>
                                            <span class="info-value" style="color: #065f46; font-weight: 700; font-size: 1.2rem;">
                                                {{ number_format(($viewing->unit->base_rent ?? 0) + ($viewing->unit->deposit_amount ?? $viewing->unit->base_rent ?? 0), 0, ',', '.') }} VNĐ
                                            </span>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Notes -->
                    @if($viewing->note || $viewing->result_note)
                <div class="modern-card">
                        <div class="card-header">
                        <h5 class="card-title">
                                <i class="fas fa-sticky-note"></i>
                                Ghi chú
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($viewing->note)
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Ghi chú của bạn:</label>
                                    <div class="alert alert-light">
                                    <i class="fas fa-user"></i>
                                        {{ $viewing->note }}
                                    </div>
                                </div>
                            @endif
                            
                            @if($viewing->result_note)
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Ghi chú từ agent:</label>
                                    <div class="alert alert-info">
                                    <i class="fas fa-user-tie"></i>
                                        {{ $viewing->result_note }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Filter Section -->
                    <div class="modern-card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-filter"></i>
                                Lọc lịch hẹn
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('tenant.appointments', ['status' => 'all']) }}" class="btn btn-outline-primary {{ request('status') == 'all' || !request('status') ? 'active' : '' }}">
                                    <i class="fas fa-list"></i> Tất cả
                                </a>
                                <a href="{{ route('tenant.appointments', ['status' => 'requested']) }}" class="btn btn-outline-warning {{ request('status') == 'requested' ? 'active' : '' }}">
                                    <i class="fas fa-clock"></i> Chờ xác nhận
                                </a>
                                <a href="{{ route('tenant.appointments', ['status' => 'confirmed']) }}" class="btn btn-outline-info {{ request('status') == 'confirmed' ? 'active' : '' }}">
                                    <i class="fas fa-check-circle"></i> Đã xác nhận
                                </a>
                                <a href="{{ route('tenant.appointments', ['status' => 'cancelled']) }}" class="btn btn-outline-danger {{ request('status') == 'cancelled' ? 'active' : '' }}">
                                    <i class="fas fa-times-circle"></i> Đã hủy
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Agent Information -->
                <div class="modern-card">
                        <div class="card-header">
                        <h5 class="card-title">
                                <i class="fas fa-user-tie"></i>
                                Thông tin agent
                            </h5>
                        </div>
                        <div class="card-body">
                        @if($viewing->agent)
                            <div class="agent-info-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar-lg text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                        {{ substr($viewing->agent->name ?? 'A', 0, 1) }}
                                    </div>
                                    <div>
                                        <h6 class="mb-1" style="color: #92400e; font-weight: 700;">{{ $viewing->agent->name ?? 'Agent' }}</h6>
                                        <small style="color: #92400e;">Agent bất động sản</small>
                                    </div>
                                </div>
                                
                                <!-- Complete Agent Information -->
                                <div class="agent-details mt-3">
                                    @if($viewing->agent->phone)
                                        <div class="info-item mb-2">
                                            <label class="info-label" style="color: #92400e; font-size: 0.9rem;">
                                                <i class="fas fa-phone text-success"></i> Số điện thoại:
                                            </label>
                                            <span class="info-value" style="color: #92400e; font-weight: 600;">{{ $viewing->agent->phone }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($viewing->agent->email)
                                        <div class="info-item mb-2">
                                            <label class="info-label" style="color: #92400e; font-size: 0.9rem;">
                                                <i class="fas fa-envelope text-primary"></i> Email:
                                            </label>
                                            <span class="info-value" style="color: #92400e; font-weight: 600;">{{ $viewing->agent->email }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($viewing->agent->address)
                                        <div class="info-item mb-2">
                                            <label class="info-label" style="color: #92400e; font-size: 0.9rem;">
                                                <i class="fas fa-map-marker-alt text-danger"></i> Địa chỉ:
                                            </label>
                                            <span class="info-value" style="color: #92400e; font-weight: 600;">{{ $viewing->agent->address }}</span>
                                        </div>
                                    @endif
                                    
                                    @if($viewing->agent->organization)
                                        <div class="info-item mb-2">
                                            <label class="info-label" style="color: #92400e; font-size: 0.9rem;">
                                                <i class="fas fa-building text-info"></i> Công ty:
                                            </label>
                                            <span class="info-value" style="color: #92400e; font-weight: 600;">{{ $viewing->agent->organization->name ?? 'N/A' }}</span>
                                        </div>
                                    @endif
                                    
                                    {{-- @if($viewing->agent->created_at)
                                        <div class="info-item mb-2">
                                            <label class="info-label" style="color: #92400e; font-size: 0.9rem;">
                                                <i class="fas fa-calendar text-warning"></i> Tham gia:
                                            </label>
                                            <span class="info-value" style="color: #92400e; font-weight: 600;">{{ $viewing->agent->created_at->format('m/Y') }}</span>
                                        </div>
                                    @endif --}}
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                @if($viewing->agent->phone)
                                    <a href="tel:{{ $viewing->agent->phone }}" class="btn btn-success">
                                        <i class="fas fa-phone"></i>
                                        Gọi agent
                                    </a>
                                @endif
                                
                                    @if($viewing->agent->email)
                                        <a href="mailto:{{ $viewing->agent->email }}" class="btn btn-outline-primary">
                                            <i class="fas fa-envelope"></i>
                                            Gửi email
                                        </a>
                                    @endif
                                
                                @if($viewing->agent->phone)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $viewing->agent->phone) }}" class="btn btn-outline-success" target="_blank">
                                        <i class="fab fa-whatsapp"></i>
                                        WhatsApp
                                    </a>
                                @endif
                                </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-user-slash text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">Chưa có thông tin agent</p>
                            </div>
                        @endif
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    @if($viewing->status === 'requested' || $viewing->status === 'confirmed')
                <div class="modern-card">
                        <div class="card-header">
                        <h5 class="card-title">
                                <i class="fas fa-bolt"></i>
                                Thao tác nhanh
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-danger" onclick="cancelViewing({{ $viewing->id }})">
                                    <i class="fas fa-times"></i>
                                    Hủy lịch đặt
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Status History -->
                <div class="modern-card">
                        <div class="card-header">
                        <h5 class="card-title">
                                <i class="fas fa-history"></i>
                                Lịch sử trạng thái
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Tạo lịch đặt</h6>
                                    <p class="timeline-text">{{ $viewing->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                                
                                @if($viewing->status !== 'requested')
                                    <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                        <div class="timeline-content">
                                            <h6 class="timeline-title">{{ $viewing->getStatusText() }}</h6>
                                        <p class="timeline-text">{{ $viewing->updated_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
        <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                        <h5 class="modal-title" id="cancelModalLabel">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            Xác nhận hủy lịch
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn hủy lịch đặt này không?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Lưu ý:</strong> Sau khi hủy, bạn sẽ cần đặt lịch mới nếu muốn xem phòng.
                </div>
            </div>
            <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Không
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmCancel()">
                            <i class="fas fa-check"></i> Có, hủy lịch
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Pass data to external JS
    window.viewingId = {{ $viewing->id }};
    window.scheduleTime = '{{ $viewing->schedule_at->toISOString() }}';
    window.appointmentsRoute = '{{ route("tenant.appointments") }}';
    window.appointmentsCancelRoute = '{{ route("tenant.appointments.cancel", $viewing->id) }}';
</script>
<script src="{{ asset('assets/js/user/viewings-show.js') }}?v={{ time() }}"></script>
@endpush
