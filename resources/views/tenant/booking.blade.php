@extends('layouts.app')

@section('title', 'Đặt lịch xem phòng')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/booking.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="booking-container">
    <div class="container">
        <!-- Header -->
        <div class="booking-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div>
                            <h1 class="page-title">Đặt lịch xem phòng</h1>
                            <p class="page-subtitle">Đặt lịch hẹn xem phòng trọ một cách dễ dàng và nhanh chóng</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </div>
        </div>

        <!-- Booking Form -->
        <div class="booking-form-container">
            @if($property)
                <div class="property-info-card">
                    <h5><i class="fas fa-building"></i> {{ $property->name }}</h5>
                    
                    @if($property->location2025)
                        <div class="mb-2">
                            <i class="fas fa-map-marker-alt"></i>
                            <strong>Địa chỉ mới (2025):</strong> {{ $property->new_address }}
                        </div>
                    @endif
                    
                    @if($property->location)
                        <div class="mb-2">
                            <i class="fas fa-map-marker-alt"></i>
                            <strong>Địa chỉ cũ:</strong> {{ $property->old_address }}
                        </div>
                    @endif
                    
                    @if($assignedAgent)
                        <div class="mt-3 pt-3" style="border-top: 1px solid rgba(59, 130, 246, 0.2);">
                            <i class="fas fa-user-tie"></i>
                            <strong>Agent phụ trách:</strong> {{ $assignedAgent->full_name }}
                            @if($assignedAgent->phone)
                                | <i class="fas fa-phone"></i> {{ $assignedAgent->phone }}
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if($unit)
                {{-- Debug info --}}
                {{-- @if(config('app.debug'))
                    <div class="alert alert-info">
                        <small>
                            <strong>Debug Unit Data:</strong><br>
                            ID: {{ $unit->id }}<br>
                            Code: {{ $unit->code ?? 'N/A' }}<br>
                            Base Rent: {{ $unit->base_rent ?? 'N/A' }}<br>
                            Deposit Amount: {{ $unit->deposit_amount ?? 'N/A' }}<br>
                            Status: {{ $unit->status ?? 'N/A' }}
                        </small>
                    </div>
                @endif --}}
                
                <div class="unit-info-card">
                    <h6><i class="fas fa-home"></i> Thông tin phòng</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong><i class="fas fa-door-open"></i> Tên phòng:</strong> 
                                <span>{{ $unit->code ?? 'Phòng ' . $unit->id }}</span>
                            </p>
                            <p class="mb-2">
                                <strong><i class="fas fa-money-bill-wave"></i> Tiền thuê:</strong> 
                                <span>{{ number_format($unit->base_rent) }} VNĐ/tháng</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong><i class="fas fa-shield-alt"></i> Tiền cọc:</strong> 
                                <span>{{ number_format($unit->deposit_amount ?? $unit->base_rent) }} VNĐ</span>
                            </p>
                            <p class="mb-0">
                                <strong><i class="fas fa-calculator"></i> Tổng cần chuẩn bị:</strong> 
                                <span>{{ number_format(($unit->base_rent ?? 0) + ($unit->deposit_amount ?? $unit->base_rent ?? 0)) }} VNĐ</span>
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @if($userInfo)
                <div class="user-info-card">
                    <i class="fas fa-check-circle"></i>
                    <strong>Thông tin đã được điền sẵn từ tài khoản của bạn.</strong>
                    <br>
                    <small>Bạn có thể quản lý lịch hẹn trong tài khoản sau khi đặt lịch thành công.</small>
                </div>
            @else
                <div class="user-info-card">
                    <i class="fas fa-info-circle"></i>
                    <strong>Chưa đăng nhập?</strong>
                    <br>
                    <small>Hãy <a href="{{ route('login') }}" style="color: #92400e; text-decoration: underline;">đăng nhập</a> hoặc <a href="{{ route('register') }}" style="color: #92400e; text-decoration: underline;">đăng ký tài khoản</a> để quản lý lịch hẹn dễ dàng hơn!</small>
                </div>
            @endif

            <form id="bookingForm" method="POST" action="{{ route('tenant.booking.store', $property->id ?? 1) }}">
                @csrf
                
                <input type="hidden" name="property_id" value="{{ $property->id ?? 1 }}">
                @if($unit)
                    <input type="hidden" name="unit_id" value="{{ $unit->id }}">
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="lead_name" class="form-label">
                                <i class="fas fa-user"></i> Họ và tên <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('lead_name') is-invalid @enderror" 
                                   id="lead_name" 
                                   name="lead_name" 
                                   value="{{ old('lead_name', $userInfo['name'] ?? '') }}" 
                                   required>
                            @error('lead_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="lead_phone" class="form-label">
                                <i class="fas fa-phone"></i> Số điện thoại <span class="text-danger">*</span>
                            </label>
                            <input type="tel" 
                                   class="form-control @error('lead_phone') is-invalid @enderror" 
                                   id="lead_phone" 
                                   name="lead_phone" 
                                   value="{{ old('lead_phone', $userInfo['phone'] ?? '') }}" 
                                   required>
                            @error('lead_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="lead_email" class="form-label">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" 
                           class="form-control @error('lead_email') is-invalid @enderror" 
                           id="lead_email" 
                           name="lead_email" 
                           value="{{ old('lead_email', $userInfo['email'] ?? '') }}">
                    @error('lead_email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="schedule_date" class="form-label">
                                <i class="fas fa-calendar"></i> Ngày xem <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   class="form-control @error('schedule_date') is-invalid @enderror" 
                                   id="schedule_date" 
                                   name="schedule_date" 
                                   value="{{ old('schedule_date') }}" 
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}" 
                                   required>
                            @error('schedule_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="schedule_time" class="form-label">
                                <i class="fas fa-clock"></i> Giờ xem <span class="text-danger">*</span>
                            </label>
                            <select class="form-control @error('schedule_time') is-invalid @enderror" 
                                    id="schedule_time" 
                                    name="schedule_time" 
                                    required>
                                <option value="">Chọn giờ</option>
                                <option value="08:00">08:00 - 09:00</option>
                                <option value="09:00">09:00 - 10:00</option>
                                <option value="10:00">10:00 - 11:00</option>
                                <option value="11:00">11:00 - 12:00</option>
                                <option value="14:00">14:00 - 15:00</option>
                                <option value="15:00">15:00 - 16:00</option>
                                <option value="16:00">16:00 - 17:00</option>
                                <option value="17:00">17:00 - 18:00</option>
                            </select>
                            @error('schedule_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="note" class="form-label">
                        <i class="fas fa-sticky-note"></i> Ghi chú
                    </label>
                    <textarea class="form-control @error('note') is-invalid @enderror" 
                              id="note" 
                              name="note" 
                              rows="3" 
                              placeholder="Ghi chú thêm về yêu cầu xem phòng...">{{ old('note') }}</textarea>
                    @error('note')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-calendar-check"></i>
                        Đặt lịch xem phòng
                    </button>
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Quay lại
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Pass data to external JS
    window.isAuthenticated = {{ $userInfo ? 'true' : 'false' }};
</script>
<script src="{{ asset('assets/js/user/booking.js') }}?v={{ time() }}"></script>
@endpush
