@extends('layouts.app')

@section('content')
<!-- Search Section -->
<section class="search-section">
    <div class="container">
        <div class="search-form-container">
            <form action="{{ route('property.index') }}" method="GET" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Địa điểm</label>
                        <select name="location" class="form-select">
                            <option value="">Tất cả địa điểm</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->city }}" {{ request('location') == $location->city ? 'selected' : '' }}>
                                    {{ $location->city }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Loại bất động sản</label>
                        <select name="property_type" class="form-select">
                            <option value="">Tất cả loại bất động sản</option>
                            @foreach($propertyTypes as $type)
                                <option value="{{ $type->id }}" {{ request('property_type') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Giá thuê</label>
                        <select name="price_range" class="form-select">
                            <option value="">Tất cả mức giá</option>
                            @foreach($priceRanges as $range)
                                <option value="{{ $range['min'] }}-{{ $range['max'] }}" {{ request('price_range') == $range['min'].'-'.$range['max'] ? 'selected' : '' }}>
                                    {{ $range['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Diện tích</label>
                        <select name="area_range" class="form-select">
                            <option value="">Tất cả diện tích</option>
                            @foreach($areaRanges as $range)
                                <option value="{{ $range['min'] }}-{{ $range['max'] }}" {{ request('area_range') == $range['min'].'-'.$range['max'] ? 'selected' : '' }}>
                                    {{ $range['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i>
                            Tìm kiếm
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Results Section -->
<section class="results-section">
    <div class="container">
        @if(session('booking_success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                <strong>Đặt lịch thành công!</strong> 
                {{ session('message') }}
                @if(session('viewing_id'))
                    <br><small>Mã lịch hẹn: #{{ session('viewing_id') }}</small>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Lỗi!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        <div class="section-header">
            <h2>Danh Sách Bất Động Sản</h2>
            <p>Tìm thấy {{ $properties->total() }} bất động sản phù hợp với yêu cầu của bạn</p>
        </div>

        @if($properties->count() > 0)
            <div class="properties-grid">
                @foreach($properties as $property)
                <div class="property-card" onclick="window.location.href='{{ route('property.show', $property->id) }}'">
                    <div class="property-image">
                        @if($property->images && count($property->images) > 0)
                            @php
                                $propertyImage = $property->images[0];
                                if (strpos($propertyImage, 'http://') === 0 || strpos($propertyImage, 'https://') === 0) {
                                    $propertyImageUrl = $propertyImage;
                                } else {
                                    $propertyImagePath = preg_replace('#^storage/#', '', $propertyImage);
                                    if (strpos($propertyImagePath, 'images/') !== 0) {
                                        $propertyImagePath = 'images/' . ltrim($propertyImagePath, '/');
                                    }
                                    $propertyImageUrl = Storage::url($propertyImagePath);
                                }
                            @endphp
                            <img src="{{ $propertyImageUrl }}" alt="{{ $property->name }}">
                        @else
                            <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=300&fit=crop" alt="{{ $property->name }}">
                        @endif
                        <div class="property-badges">
                            @if($property->created_at->diffInDays(now()) <= 7)
                                <span class="badge new">Mới</span>
                            @endif
                            <span class="badge type">{{ $property->propertyType->name ?? 'N/A' }}</span>
                        </div>
                        <button class="favorite-btn" onclick="event.stopPropagation(); toggleFavorite({{ $property->id }})">
                            <i class="fas fa-heart"></i>
                        </button>
                        <div class="image-indicators">
                            <span class="indicator active"></span>
                            <span class="indicator"></span>
                            <span class="indicator"></span>
                        </div>
                    </div>
                    <div class="property-content">
                        <h3>{{ $property->name }}</h3>
                        <div class="property-location">
                            @if($property->location2025)
                                <i class="fas fa-map-marker-alt text-success"></i>
                                <div class="address-content">
                                    <span class="address-text">{{ $property->new_address }}</span>
                                    <small class="address-label">Địa chỉ mới (2025)</small>
                                </div>
                            @elseif($property->location)
                                <i class="fas fa-map-marker-alt text-warning"></i>
                                <div class="address-content">
                                    <span class="address-text">{{ $property->old_address }}</span>
                                    <small class="address-label">Địa chỉ cũ</small>
                                </div>
                            @else
                                <i class="fas fa-exclamation-triangle text-muted"></i>
                                <span>Chưa có địa chỉ</span>
                            @endif
                        </div>
                        <div class="property-details">
                            <div class="detail">
                                <i class="fas fa-building"></i>
                                <span>{{ $property->total_floors ?? 'N/A' }} tầng</span>
                            </div>
                            <div class="detail">
                                <i class="fas fa-door-open"></i>
                                <span>{{ $property->total_rooms ?? 'N/A' }} phòng</span>
                            </div>
                            <div class="detail">
                                <i class="fas fa-home"></i>
                                <span>{{ $property->units->count() }} phòng trống</span>
                            </div>
                        </div>
                        @if($property->description)
                        <div class="property-description">
                            <p>{{ Str::limit($property->description, 100) }}</p>
                        </div>
                        @endif
                        <div class="property-footer">
                            <div class="price-info">
                                @php
                                    // Since we only load available units, we can use them directly
                                    $minPrice = $property->units->min('base_rent');
                                    $maxPrice = $property->units->max('base_rent');
                                @endphp
                                @if($minPrice && $maxPrice)
                                    @if($minPrice == $maxPrice)
                                        <div class="price">{{ number_format($minPrice, 0, ',', '.') }} VNĐ/tháng</div>
                                    @else
                                        <div class="price">{{ number_format($minPrice, 0, ',', '.') }} - {{ number_format($maxPrice, 0, ',', '.') }} VNĐ/tháng</div>
                                    @endif
                                @else
                                    <div class="price">Liên hệ</div>
                                @endif
                            </div>
                            <a href="{{ route('property.show', $property->id) }}" class="btn-view" onclick="event.stopPropagation()">
                                <i class="fas fa-eye"></i>
                                Chi tiết
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                {{ $properties->appends(request()->query())->links('vendor.pagination.custom') }}
            </div>
        @else
            <div class="no-results">
                <div class="no-results-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>Không tìm thấy bất động sản nào</h3>
                <p>Hãy thử điều chỉnh bộ lọc tìm kiếm của bạn</p>
                <a href="{{ route('property.index') }}" class="btn btn-primary">Xem tất cả bất động sản</a>
            </div>
        @endif
    </div>
</section>
@endsection

@push('styles')
<style>
.search-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 0;
    margin-top: 80px;
}

.search-form-container {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr auto;
    gap: 20px;
    align-items: end;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.form-select {
    padding: 12px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.btn-search {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.results-section {
    padding: 60px 0;
    background: #f8f9fa;
}

.section-header {
    text-align: center;
    margin-bottom: 50px;
}

.section-header h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 15px;
}

.section-header p {
    font-size: 1.1rem;
    color: #666;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.property-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    cursor: pointer;
}

.property-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.property-image {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.property-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.property-card:hover .property-image img {
    transform: scale(1.05);
}

.property-badges {
    position: absolute;
    top: 15px;
    left: 15px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge.new {
    background: #ff6b35;
    color: white;
}

.badge.type {
    background: rgba(255,255,255,0.9);
    color: #333;
    backdrop-filter: blur(10px);
}

.favorite-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255,255,255,0.9);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.favorite-btn:hover {
    background: #ff6b35;
    color: white;
}

.image-indicators {
    position: absolute;
    bottom: 15px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
}

.indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    transition: all 0.3s ease;
}

.indicator.active {
    background: white;
}

.property-content {
    padding: 25px;
}

.property-content h3 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 15px;
    line-height: 1.3;
}

.property-location {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 15px;
    color: #666;
    font-size: 14px;
}

.address-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.address-text {
    font-size: 14px;
    line-height: 1.3;
}

.address-label {
    font-size: 11px;
    font-weight: 600;
    opacity: 0.8;
}

.text-success + .address-content .address-label {
    color: #28a745;
}

.text-warning + .address-content .address-label {
    color: #ffc107;
}

.property-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 15px;
}

.detail {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #666;
    font-size: 14px;
}

.property-description {
    margin-bottom: 20px;
}

.property-description p {
    color: #666;
    font-size: 14px;
    line-height: 1.5;
}

.property-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price {
    font-size: 1.2rem;
    font-weight: 700;
    color: #ff6b35;
}

.btn-view {
    background: #667eea;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
}

.btn-view:hover {
    background: #5a6fd8;
    color: white;
    text-decoration: none;
}

.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 50px;
}

.no-results {
    text-align: center;
    padding: 80px 20px;
}

.no-results-icon {
    font-size: 4rem;
    color: #ccc;
    margin-bottom: 20px;
}

.no-results h3 {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 10px;
}

.no-results p {
    color: #666;
    margin-bottom: 30px;
}

/* Success Alert */
.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    border: 1px solid #c3e6cb;
    color: #155724;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1);
}

.alert-success i {
    color: #28a745;
    margin-right: 10px;
}

.alert-success strong {
    font-weight: 700;
}

.alert-success small {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Error Alert */
.alert-danger {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    border: 1px solid #f5c6cb;
    color: #721c24;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.1);
}

.alert-danger i {
    color: #dc3545;
    margin-right: 10px;
}

.alert-danger strong {
    font-weight: 700;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .properties-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .section-header h2 {
        font-size: 2rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Property index functions with notification system
function toggleFavorite(propertyId) {
    if (window.Notify && typeof window.Notify.confirm === 'function') {
        window.Notify.confirm({
            title: 'Thêm vào yêu thích',
            message: 'Bạn có muốn thêm bất động sản này vào danh sách yêu thích?',
            type: 'info',
            confirmText: 'Thêm',
            cancelText: 'Hủy',
            onConfirm: () => {
                // Simulate adding to favorites
                console.log('Added property', propertyId, 'to favorites');
                alert('Đã thêm vào danh sách yêu thích!');
            },
            onCancel: () => {
                console.log('User cancelled adding to favorites');
            }
        });
    } else {
        // Fallback
        alert('Chức năng yêu thích sẽ được tích hợp sau');
    }
}

// Show success notification if redirected from booking
document.addEventListener('DOMContentLoaded', function() {
    // Check if we have success message from booking
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('booking_success') === '1') {
        const viewingId = urlParams.get('viewing_id');
        if (window.Notify && typeof window.Notify.toast === 'function') {
            window.Notify.toast({
                title: 'Đặt lịch thành công!',
                message: 'Chúng tôi sẽ liên hệ lại để xác nhận lịch hẹn của bạn.' + (viewingId ? ` Mã lịch hẹn: #${viewingId}` : ''),
                type: 'success',
                duration: 8000,
                showProgress: true
            });
        } else {
            // Fallback
            alert('Đặt lịch thành công! Chúng tôi sẽ liên hệ lại để xác nhận lịch hẹn của bạn.' + (viewingId ? ` Mã lịch hẹn: #${viewingId}` : ''));
        }
    }
});

// Property card click handler with confirmation
function viewProperty(propertyId) {
    if (window.Notify && typeof window.Notify.confirm === 'function') {
        window.Notify.confirm({
            title: 'Xem chi tiết bất động sản',
            message: 'Bạn có muốn xem chi tiết bất động sản này?',
            type: 'info',
            confirmText: 'Xem',
            cancelText: 'Hủy',
            onConfirm: () => {
                window.location.href = `/properties/${propertyId}`;
            },
            onCancel: () => {
                console.log('User cancelled viewing property');
            }
        });
    } else {
        // Fallback
        window.location.href = `/properties/${propertyId}`;
    }
}
</script>
@endpush
