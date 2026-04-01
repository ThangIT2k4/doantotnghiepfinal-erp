@extends('layouts.app')

@section('title', 'Quản lý lịch hẹn')

@php
    $viewings = $viewings ?? collect();
    $stats = [
        'pending' => $viewings->where('status', 'requested')->count(),
        'confirmed' => $viewings->where('status', 'confirmed')->count(),
        'cancelled' => $viewings->where('status', 'cancelled')->count(),
    ];
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/appointments.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script>
    // Pass route to JavaScript
    window.appointmentsRoute = '{{ route("tenant.appointments") }}';
</script>
<script src="{{ asset('assets/js/notifications.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/user/appointments.js') }}?v={{ time() }}"></script>
@endpush

@section('content')
<div class="appointments-container">
    <div class="container">
        <!-- Header -->
        <div class="appointments-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div>
                            <h1 class="page-title">Quản lý lịch hẹn</h1>
                            <p class="page-subtitle">Theo dõi và quản lý các lịch hẹn xem phòng của bạn</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-primary btn-modern">
                        <i class="fas fa-tachometer-alt me-2"></i>Về Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-section">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['pending'] }}</h3>
                            <p>Chờ xác nhận</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon confirmed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['confirmed'] }}</h3>
                            <p>Đã xác nhận</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon cancelled">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['cancelled'] }}</h3>
                            <p>Đã hủy</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter and Search -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Tìm kiếm theo tên phòng, địa chỉ..." id="searchInput">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-status="all">Tất cả</button>
                        <button class="filter-tab" data-status="requested">Chờ xác nhận</button>
                        <button class="filter-tab" data-status="confirmed">Đã xác nhận</button>
                        <button class="filter-tab" data-status="cancelled">Đã hủy</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments List -->
        <div class="appointments-list">
            @forelse($viewings as $viewing)
                <div class="appointment-card" data-status="{{ $viewing->status }}" data-id="{{ $viewing->id }}">
                    <div class="appointment-status {{ $viewing->status }}">
                        @switch($viewing->status)
                            @case('requested')
                                <i class="fas fa-clock"></i>
                                <span>Chờ xác nhận</span>
                                @break
                            @case('confirmed')
                                <i class="fas fa-check-circle"></i>
                                <span>Đã xác nhận</span>
                                @break
                            @case('done')
                                <i class="fas fa-calendar-check"></i>
                                <span>Đã hoàn thành</span>
                                @break
                            @case('no_show')
                                <i class="fas fa-user-times"></i>
                                <span>Không đến</span>
                                @break
                            @case('cancelled')
                                <i class="fas fa-times-circle"></i>
                                <span>Đã hủy</span>
                                @break
                        @endswitch
                    </div>
                    <div class="appointment-content">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="property-image">
                                    @if($viewing->property && $viewing->property->images && count($viewing->property->images) > 0)
                                        <img src="{{ Storage::url($viewing->property->images[0]) }}" alt="{{ $viewing->property->title }}">
                                    @else
                                        <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=300&h=200&fit=crop" alt="Phòng trọ">
                                    @endif
                                    @if($viewing->unit)
                                        <div class="property-badges">
                                            <span class="badge unit">{{ $viewing->unit->code }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="property-info">
                                    <h4 class="property-title">
                                        {{ $viewing->property->name ?? 'Không có thông tin' }}
                                    </h4>
                                    <div class="property-location">
                                        @if($viewing->property->new_address && $viewing->property->new_address !== 'Chưa có địa chỉ mới')
                                            <p class="mb-1">
                                                <i class="fas fa-map-marker-alt text-success"></i>
                                                <strong>Địa chỉ mới (2025):</strong> {{ $viewing->property->new_address }}
                                            </p>
                                        @endif
                                        
                                        @if($viewing->property->old_address && $viewing->property->old_address !== 'Chưa có địa chỉ cũ')
                                            <p class="mb-0">
                                                <i class="fas fa-map-marker-alt text-warning"></i>
                                                <strong>Địa chỉ cũ:</strong> {{ $viewing->property->old_address }}
                                            </p>
                                        @endif
                                        
                                        @if((!$viewing->property->new_address || $viewing->property->new_address === 'Chưa có địa chỉ mới') && (!$viewing->property->old_address || $viewing->property->old_address === 'Chưa có địa chỉ cũ'))
                                            <p class="mb-0">
                                                <i class="fas fa-map-marker-alt text-muted"></i>
                                                Không có địa chỉ
                                            </p>
                                        @endif
                                    </div>
                                    <div class="property-details">
                                        @if($viewing->unit)
                                            <span class="detail">
                                                <i class="fas fa-expand-arrows-alt"></i>
                                                {{ $viewing->unit->area_m2 }}m²
                                            </span>
                                            <span class="detail">
                                                <i class="fas fa-users"></i>
                                                {{ $viewing->unit->max_occupancy }} người
                                            </span>
                                            <span class="detail price">
                                                <i class="fas fa-money-bill-wave"></i>
                                                {{ number_format($viewing->unit->base_rent, 0, ',', '.') }} VNĐ/tháng
                                            </span>
                                        @else
                                            <span class="detail">
                                                <i class="fas fa-building"></i>
                                                Xem toàn bộ tòa nhà
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="appointment-info">
                                    <div class="appointment-time">
                                        <i class="fas fa-calendar"></i>
                                        <div>
                                            <strong>{{ $viewing->schedule_at->format('d/m/Y') }}</strong>
                                            <span>{{ $viewing->schedule_at->format('H:i') }} - {{ $viewing->schedule_at->addHour()->format('H:i') }}</span>
                                        </div>
                                    </div>
                                    @if($viewing->agent)
                                        <div class="appointment-contact">
                                            <i class="fas fa-user"></i>
                                            <div>
                                                <strong>{{ $viewing->agent->full_name ?? 'Không có tên' }}</strong>
                                                <span>{{ $viewing->agent->phone ?? 'Không có SĐT' }}</span>
                                            </div>
                                        </div>
                                    @endif
                                    @if($viewing->note)
                                        <div class="appointment-note">
                                            <i class="fas fa-sticky-note"></i>
                                            <div>
                                                <strong>Ghi chú:</strong>
                                                <span>{{ $viewing->note }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="appointment-actions">
                        @switch($viewing->status)
                            @case('requested')
                                <a href="{{ route('tenant.appointments.edit', $viewing->id) }}" class="btn btn-outline-warning">
                                    <i class="fas fa-edit"></i>
                                    Sửa lịch
                                </a>
                                <button class="btn btn-outline-danger" onclick="cancelAppointment({{ $viewing->id }})" data-id="{{ $viewing->id }}">
                                    <i class="fas fa-times"></i>
                                    Hủy lịch
                                </button>
                                <a href="{{ route('tenant.appointments.show', $viewing->id) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                    Xem chi tiết
                                </a>
                                @if($viewing->agent && $viewing->agent->phone)
                                    <a href="tel:{{ $viewing->agent->phone }}" class="btn btn-success">
                                        <i class="fas fa-phone"></i>
                                        Gọi điện
                                    </a>
                                @endif
                                @break
                            @case('confirmed')
                                <a href="{{ route('tenant.appointments.show', $viewing->id) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                    Xem chi tiết
                                </a>
                                @if($viewing->agent && $viewing->agent->phone)
                                    <a href="tel:{{ $viewing->agent->phone }}" class="btn btn-success">
                                        <i class="fas fa-phone"></i>
                                        Gọi điện
                                    </a>
                                @endif
                                @break
                            @case('done')
                                <button class="btn btn-outline-primary" onclick="rateProperty({{ $viewing->id }})">
                                    <i class="fas fa-star"></i>
                                    Đánh giá
                                </button>
                                @if($viewing->unit)
                                    <a href="{{ route('tenant.deposit', $viewing->unit->id) }}" class="btn btn-outline-success">
                                        <i class="fas fa-home"></i>
                                        Thuê phòng
                                    </a>
                                @endif
                                @if($viewing->agent && $viewing->agent->phone)
                                    <a href="tel:{{ $viewing->agent->phone }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-phone"></i>
                                        Gọi lại
                                    </a>
                                @endif
                                @break
                            @case('cancelled')
                                <a href="{{ route('tenant.appointments.show', $viewing->id) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                    Xem chi tiết
                                </a>
                                @if($viewing->property)
                                    <a href="{{ route('property.show', $viewing->property->id) }}" class="btn btn-outline-info">
                                        <i class="fas fa-redo"></i>
                                        Đặt lại
                                    </a>
                                @endif
                                @break
                        @endswitch
                    </div>
                </div>
            @empty
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h3>Không có lịch hẹn nào</h3>
                    <p>Bạn chưa có lịch hẹn xem phòng nào. Hãy tìm kiếm và đặt lịch xem phòng mới!</p>
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Tìm phòng ngay
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        {{-- <div class="pagination-section">
            <nav aria-label="Appointments pagination">
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
                        <a class="page-link" href="#">3</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="#">Sau</a>
                    </li>
                </ul>
            </nav>
        </div> --}}
    </div>
</div>
@endsection