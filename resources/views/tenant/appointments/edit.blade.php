@extends('layouts.app')

@section('title', 'Chỉnh sửa lịch hẹn')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/appointments-edit.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script>
    // Pass data to external JS
    window.viewingId = {{ $viewing->id }};
    window.updateRoute = '/tenant/appointments/{{ $viewing->id }}/update';
    window.appointmentsRoute = '{{ route("tenant.appointments") }}';
</script>
<script src="{{ asset('assets/js/user/appointments-edit.js') }}?v={{ time() }}"></script>
@endpush

@section('content')
<div class="edit-container">
    <div class="container">
        <!-- Header -->
        <div class="edit-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div>
                            <h1 class="page-title">Chỉnh sửa lịch hẹn</h1>
                            <p class="page-subtitle">Cập nhật thông tin lịch hẹn xem phòng</p>
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

        <!-- Property Info -->
        <div class="property-info">
            <h4 class="property-title">{{ $viewing->property->name ?? 'Không có thông tin' }}</h4>
            <div class="property-details">
                <div class="detail">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>{{ $viewing->property->location2025->full_address ?? 'Không có địa chỉ' }}</span>
                </div>
                @if($viewing->unit)
                    <div class="detail">
                        <i class="fas fa-expand-arrows-alt"></i>
                        <span>{{ $viewing->unit->area_m2 }}m²</span>
                    </div>
                    <div class="detail">
                        <i class="fas fa-users"></i>
                        <span>{{ $viewing->unit->max_occupancy }} người</span>
                    </div>
                    <div class="detail">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>{{ number_format($viewing->unit->base_rent, 0, ',', '.') }} VNĐ/tháng</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Edit Form -->
        <div class="edit-form-container">
            <form id="editForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="schedule_date" class="form-label">
                                <i class="fas fa-calendar"></i> Ngày hẹn <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   class="form-control" 
                                   id="schedule_date" 
                                   name="schedule_date" 
                                   value="{{ $viewing->schedule_at->format('Y-m-d') }}" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="schedule_time" class="form-label">
                                <i class="fas fa-clock"></i> Giờ hẹn <span class="text-danger">*</span>
                            </label>
                            <input type="time" 
                                   class="form-control" 
                                   id="schedule_time" 
                                   name="schedule_time" 
                                   value="{{ $viewing->schedule_at->format('H:i') }}" 
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="note" class="form-label">
                        <i class="fas fa-sticky-note"></i> Ghi chú
                    </label>
                    <textarea class="form-control" 
                              id="note" 
                              name="note" 
                              rows="4" 
                              placeholder="Nhập ghi chú thêm...">{{ $viewing->note }}</textarea>
                </div>
                
                <div class="form-group">
                    <div class="d-flex gap-3">
                        <button type="submit" id="submitBtn" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu thay đổi
                        </button>
                        <a href="{{ route('tenant.appointments') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
