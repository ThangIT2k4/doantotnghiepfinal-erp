@extends('layouts.app')

@section('title', 'Tìm phòng trọ')

@section('content')
<!-- Hero Section -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <div class="hero-badge">
                    <i class="fas fa-search"></i>
                    <span>Tìm Kiếm Phòng Trọ</span>
                </div>
                <h1 class="hero-title">
                    Khám Phá Những Phòng Trọ
                    <span class="highlight">Phù Hợp Nhất</span>
                    Với Bạn
                </h1>
                <p class="hero-description">
                    Tìm kiếm và thuê phòng trọ chất lượng cao với giá cả hợp lý từ hàng ngàn chủ nhà uy tín trên toàn quốc.
                </p>
            </div>

            <div class="search-section">
                <div class="search-card">
                    <div class="search-tabs">
                        <button class="tab-btn active" data-tab="rent">
                            <i class="fas fa-home"></i>
                            Thuê phòng
                        </button>
                        <button class="tab-btn" data-tab="post">
                            <i class="fas fa-plus"></i>
                            Đăng tin
                        </button>
                    </div>

                    <form action="{{ route('search') }}" method="POST" class="search-form">
                        @csrf
                        <div class="form-row">
                            <div class="form-group">
                                <label>Khu vực</label>
                                <select name="location" class="form-select">
                                    <option value="">Chọn khu vực</option>
                                    @foreach($locations as $city => $data)
                                        <optgroup label="{{ $city }}">
                                            @foreach($data['wards'] as $ward)
                                                <option value="{{ $ward }}" {{ request('location') == $ward ? 'selected' : '' }}>{{ $ward }}, {{ $city }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Loại phòng</label>
                                <select name="unit_type" class="form-select">
                                    @foreach($unitTypes as $type)
                                        <option value="{{ $type['value'] }}" {{ request('unit_type') == $type['value'] ? 'selected' : '' }}>{{ $type['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Giá thuê</label>
                                <select name="price_range" class="form-select">
                                    @foreach($priceRanges as $range)
                                        <option value="{{ $range['value'] }}" {{ request('price_range') == $range['value'] ? 'selected' : '' }}>{{ $range['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Diện tích</label>
                                <select name="area_range" class="form-select">
                                    @foreach($areaRanges as $range)
                                        <option value="{{ $range['value'] }}" {{ request('area_range') == $range['value'] ? 'selected' : '' }}>{{ $range['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i>
                            Tìm kiếm ngay
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Results Section -->
<section class="results-section">
    <div class="container">
        <div class="section-header">
            <h2>Kết Quả Tìm Kiếm</h2>
            <p>Tìm thấy {{ $units->total() }} phòng trọ phù hợp với yêu cầu của bạn</p>
        </div>

        @if($units->count() > 0)
            <div class="properties-grid">
                @foreach($units as $unit)
                <div class="property-card" onclick="window.location.href='{{ route('detail', $unit->id) }}'">
                    <div class="property-image">
                        @if($unit->images && count($unit->images) > 0)
                            <img src="{{ Storage::url($unit->images[0]) }}" alt="{{ $unit->property->name }}">
                        @else
                            <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=300&fit=crop" alt="{{ $unit->property->name }}">
                        @endif
                        <div class="property-badges">
                            @if($unit->created_at->diffInDays(now()) <= 7)
                                <span class="badge new">Mới</span>
                            @endif
                            <span class="badge type">
                                @switch($unit->unit_type)
                                    @case('room')
                                        Phòng trọ
                                        @break
                                    @case('apartment')
                                        Chung cư mini
                                        @break
                                    @case('dorm')
                                        Chung cư cao cấp
                                        @break
                                    @case('shared')
                                        Nhà nguyên căn
                                        @break
                                @endswitch
                            </span>
                        </div>
                        <button class="favorite-btn" onclick="event.stopPropagation()">
                            <i class="fas fa-heart"></i>
                        </button>
                        <div class="image-indicators">
                            <span class="indicator active"></span>
                            <span class="indicator"></span>
                            <span class="indicator"></span>
                        </div>
                    </div>
                    <div class="property-content">
                        <h3>{{ $unit->property->name }}</h3>
                        <div class="property-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>
                                @if($unit->property->location2025)
                                    {{ $unit->property->location2025->street }}, {{ $unit->property->location2025->ward }}, {{ $unit->property->location2025->city }}
                                @else
                                    Địa chỉ chưa cập nhật
                                @endif
                            </span>
                        </div>
                        <div class="property-details">
                            <div class="detail">
                                <i class="fas fa-expand-arrows-alt"></i>
                                <span>{{ $unit->area_m2 ?? 'N/A' }}m²</span>
                            </div>
                            <div class="detail">
                                <i class="fas fa-users"></i>
                                <span>{{ $unit->max_occupancy }} người</span>
                            </div>
                        </div>
                        @if($unit->amenities && $unit->amenities->count() > 0)
                        <div class="property-amenities">
                            @foreach($unit->amenities->take(3) as $amenity)
                                <span class="amenity-badge">{{ $amenity->name }}</span>
                            @endforeach
                            @if($unit->amenities->count() > 3)
                                <span class="amenity-badge">+{{ $unit->amenities->count() - 3 }}</span>
                            @endif
                        </div>
                        @endif
                        <div class="property-footer">
                            <div class="price">{{ number_format($unit->base_rent, 0, ',', '.') }} VNĐ/tháng</div>
                            <a href="{{ route('detail', $unit->id) }}" class="btn-view" onclick="event.stopPropagation()">
                                <i class="fas fa-eye"></i>
                                Chi tiết
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="pagination-wrapper">
                {{ $units->appends(request()->query())->links('vendor.pagination.custom') }}
            </div>
        @else
            <div class="no-results">
                <div class="no-results-icon">
                    <i class="fas fa-search fa-3x"></i>
                </div>
                <h3>Không tìm thấy phòng trọ nào</h3>
                <p>Hãy thử điều chỉnh bộ lọc tìm kiếm của bạn để có kết quả tốt hơn.</p>
                <a href="{{ route('home') }}" class="btn-hero">
                    <i class="fas fa-arrow-left"></i>
                    Quay lại trang chủ
                </a>
            </div>
        @endif
    </div>
</section>
@endsection

@push('styles')
<style>
.results-section {
    padding: 60px 0;
    background-color: #f8f9fa;
}

.section-header {
    text-align: center;
    margin-bottom: 40px;
}

.section-header h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 10px;
}

.section-header p {
    font-size: 1.1rem;
    color: #6c757d;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.property-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    cursor: pointer;
}

.property-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.property-image {
    position: relative;
    height: 200px;
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
    gap: 5px;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.badge.new {
    background-color: #28a745;
    color: white;
}

.badge.type {
    background-color: #007bff;
    color: white;
}

.favorite-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 35px;
    height: 35px;
    border: none;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.9);
    color: #6c757d;
    cursor: pointer;
    transition: all 0.3s ease;
}

.favorite-btn:hover {
    background-color: #dc3545;
    color: white;
}

.favorite-btn.active {
    background-color: #dc3545;
    color: white;
}

.image-indicators {
    position: absolute;
    bottom: 15px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 5px;
}

.indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.5);
    transition: background-color 0.3s ease;
}

.indicator.active {
    background-color: white;
}

.property-content {
    padding: 20px;
}

.property-content h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 10px;
    line-height: 1.3;
}

.property-location {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    color: #6c757d;
    font-size: 0.9rem;
}

.property-location i {
    margin-right: 5px;
    color: #007bff;
}

.property-details {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.detail {
    display: flex;
    align-items: center;
    color: #6c757d;
    font-size: 0.9rem;
}

.detail i {
    margin-right: 5px;
    color: #007bff;
}

.property-amenities {
    margin-bottom: 15px;
}

.amenity-badge {
    display: inline-block;
    padding: 2px 6px;
    margin: 2px;
    background-color: #e9ecef;
    color: #495057;
    border-radius: 3px;
    font-size: 0.75rem;
}

.property-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #28a745;
}

.btn-view {
    padding: 8px 16px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: background-color 0.3s ease;
}

.btn-view:hover {
    background-color: #0056b3;
    color: white;
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 40px;
}

.no-results {
    text-align: center;
    padding: 60px 20px;
}

.no-results-icon {
    margin-bottom: 20px;
    color: #6c757d;
}

.no-results h3 {
    font-size: 1.5rem;
    color: #2c3e50;
    margin-bottom: 10px;
}

.no-results p {
    color: #6c757d;
    margin-bottom: 30px;
}

.btn-hero {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-hero:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    color: white;
}

@media (max-width: 768px) {
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