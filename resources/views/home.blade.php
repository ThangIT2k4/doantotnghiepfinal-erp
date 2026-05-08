 @extends('layouts.app')

 @section('content')
 <!-- Hero Section -->
<section class="hero-section">
    <div class="hero-background">
        <div class="hero-overlay"></div>
    </div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <div class="hero-badge">
                    <i class="fas fa-home"></i>
                    <span>Tìm Phòng Trọ Dễ Dàng</span>
                </div>
                <h1 class="hero-title">
                    Khám Phá Những Phòng Trọ
                    <span class="highlight">Tuyệt Vời Nhất</span>
                    Tại Hà Nội
                </h1>
                <p class="hero-description">
                    Tìm kiếm và thuê phòng trọ chất lượng cao với giá cả hợp lý từ hàng ngàn chủ nhà uy tín trên toàn quốc. 
                    Đăng ký ngay để nhận thông báo về những phòng trọ mới nhất.
                </p>
                {{-- <div class="hero-stats">
                    @if(isset($activePropertiesStats))
                    <div class="stat-item">
                        <div class="stat-number">{{ number_format($activePropertiesStats['total_active_properties']) }}</div>
                        <div class="stat-label">Bất động sản</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">{{ number_format($activePropertiesStats['available_units']) }}</div>
                        <div class="stat-label">Phòng trống</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">{{ $activePropertiesStats['occupancy_rate'] }}%</div>
                        <div class="stat-label">Tỷ lệ lấp đầy</div>
                    </div>
                    @endif
                </div> --}}
            </div>

            <!-- Search Form -->
            <div class="search-container">
                <div class="search-card">
                    <div class="search-header">
                        <h3>Tìm kiếm phòng trọ</h3>
                        <p>Hàng ngàn phòng trọ đang chờ bạn</p>
                    </div>

                    <form action="{{ route('search') }}" method="POST" class="search-form">
                        @csrf
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Khu vực</label>
                                <select name="location" class="form-select">
                                    <option value="">Chọn khu vực</option>
                                    @foreach($locations as $city => $data)
                                        <optgroup label="{{ $city }}">
                                            @foreach($data['wards'] as $ward)
                                                <option value="{{ $ward }}">{{ $ward }}, {{ $city }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Loại bất động sản</label>
                                <select name="property_type" class="form-select">
                                    <option value="">Tất cả loại</option>
                                    @php
                                        $propertyTypes = \App\Models\PropertyType::where('status', 1)
                                            ->whereNull('deleted_at')
                                            ->orderBy('name')
                                            ->get();
                                    @endphp
                                    @foreach($propertyTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Giá thuê</label>
                                <select name="price_range" class="form-select">
                                    @foreach($priceRanges as $range)
                                        <option value="{{ $range['value'] }}">{{ $range['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Diện tích</label>
                                <select name="area_range" class="form-select">
                                    @foreach($areaRanges as $range)
                                        <option value="{{ $range['value'] }}">{{ $range['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                            Tìm kiếm ngay
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2>Bạn Muốn Tìm Loại Phòng Nào?</h2>
            <p>Có nhiều sự lựa chọn khác nhau để bạn có thể tìm phòng phù hợp với nhu cầu và ngân sách.</p>
        </div>

        <div class="categories-grid">
            @foreach($categories as $category)
            <div class="category-card" onclick="window.location.href='{{ route('property.index', ['property_type' => $category['id']]) }}'">
                <div class="category-icon {{ $category['color'] }}">
                    <i class="{{ $category['icon'] }}"></i>
                </div>
                <div class="category-content">
                    <h3>{{ $category['name'] }}</h3>
                    <p>{{ $category['description'] }}</p>
                    <div class="category-footer">
                        <span class="count">{{ number_format($category['count']) }} phòng</span>
                        <span class="view-link">Xem ngay <i class="fas fa-arrow-right"></i></span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="section-footer">
            <a href="{{ route('property.index') }}" class="btn-outline">
                Xem tất cả danh mục
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Featured Properties Section -->
<section class="featured-properties-section">
    <div class="container">
        <div class="section-header">
            <div class="section-badge">
                <i class="fas fa-star"></i>
                <span>Phòng Trọ Nổi Bật</span>
            </div>
            <h2>Khám Phá Những Phòng Trọ Được Quan Tâm Nhất</h2>
            <p>Khám phá những phòng trọ mới nhất và được quan tâm nhất từ các chủ nhà uy tín trên toàn quốc.</p>
        </div>

        <div class="properties-grid">
            @forelse($featuredProperties as $property)
            <div class="property-card" onclick="window.location.href='{{ route('property.show', $property['id']) }}'">
                <div class="property-image">
                    <img src="{{ $property['image'] }}" alt="{{ $property['title'] }}" loading="lazy">
                    <div class="property-badges">
                        @if($property['is_new'])
                        <span class="badge new">Mới</span>
                        @endif
                        <span class="badge type">{{ $property['type'] }}</span>
                    </div>
                    <button class="favorite-btn" onclick="event.stopPropagation()">
                        <i class="fas fa-heart"></i>
                    </button>
                    </div>
                
                <div class="property-content">
                    <h3 class="property-title">{{ $property['title'] }}</h3>
                    
                    <div class="property-location">
                        @if(isset($property['location2025']) && $property['location2025'])
                            <i class="fas fa-map-marker-alt text-success"></i>
                            <div class="address-content">
                                <span class="address-text">{{ $property['location2025'] }}</span>
                                <small class="address-label">Địa chỉ mới (2025)</small>
                            </div>
                        @elseif(isset($property['location']) && $property['location'])
                            <i class="fas fa-map-marker-alt text-warning"></i>
                            <div class="address-content">
                                <span class="address-text">{{ $property['location'] }}</span>
                                <small class="address-label">Địa chỉ cũ</small>
                            </div>
                        @else
                            <i class="fas fa-exclamation-triangle text-muted"></i>
                            <span>Chưa có địa chỉ</span>
                        @endif
                    </div>
                    
                    <div class="property-details">
                        <div class="detail-item">
                            <i class="fas fa-expand-arrows-alt"></i>
                            <span>{{ $property['area_range'] }}</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-door-open"></i>
                            <span>{{ $property['available_units'] }}/{{ $property['total_units'] }} phòng trống</span>
                        </div>
                    </div>
                    
                    @if(!empty($property['amenities']))
                    <div class="property-amenities">
                        @foreach($property['amenities'] as $amenity)
                            <span class="amenity-tag">{{ $amenity }}</span>
                        @endforeach
                    </div>
                    @endif
                    
                    <div class="property-footer">
                        <div class="price">{{ $property['price_range'] }}</div>
                        <a href="{{ route('property.show', $property['id']) }}" class="btn-view" onclick="event.stopPropagation()">
                            <i class="fas fa-eye"></i>
                            Chi tiết
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="no-properties">
                <div class="no-properties-icon">
                    <i class="fas fa-home"></i>
                    </div>
                <h3>Chưa có bất động sản nào</h3>
                <p>Hãy quay lại sau để xem những bất động sản mới nhất!</p>
                <a href="{{ route('property.index') }}" class="btn-primary">
                    Xem tất cả bất động sản
                        </a>
                    </div>
            @endforelse
            </div>

        <div class="section-footer">
            <a href="{{ route('property.index') }}" class="btn-primary">
                Xem tất cả bất động sản
                <i class="fas fa-arrow-right"></i>
            </a>
                    </div>
                </div>
</section>

<!-- Stats Section -->
@if(isset($activePropertiesStats))
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                    </div>
                <div class="stat-content">
                    <div class="stat-number">{{ number_format($activePropertiesStats['total_active_properties']) }}</div>
                    <div class="stat-label">Bất động sản đang hoạt động</div>
                    </div>
                </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-home"></i>
                    </div>
                <div class="stat-content">
                    <div class="stat-number">{{ number_format($activePropertiesStats['total_units']) }}</div>
                    <div class="stat-label">Tổng số phòng</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-key"></i>
                    </div>
                <div class="stat-content">
                    <div class="stat-number">{{ number_format($activePropertiesStats['available_units']) }}</div>
                    <div class="stat-label">Phòng đang trống</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                    </div>
                <div class="stat-content">
                    <div class="stat-number">{{ $activePropertiesStats['occupancy_rate'] }}%</div>
                    <div class="stat-label">Tỷ lệ lấp đầy</div>
                    </div>
                </div>
            </div>
        </div>
</section>
@endif

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Sẵn sàng tìm phòng trọ mơ ước?</h2>
            <p>Hàng ngàn phòng trọ chất lượng đang chờ bạn khám phá</p>
            <div class="cta-buttons">
                <a href="{{ route('property.index') }}" class="btn-primary">
                    <i class="fas fa-search"></i>
                    Tìm kiếm ngay
                </a>
                <a href="{{ route('contact') }}" class="btn-outline">
                    <i class="fas fa-phone"></i>
                    Liên hệ tư vấn
                </a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
/* Hero Section */
.hero-section {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    overflow: hidden;
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=1920&h=1080&fit=crop') center/cover;
    z-index: 1;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
}

.hero-content {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 60px;
    align-items: center;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 20px;
    backdrop-filter: blur(10px);
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 800;
    color: white;
    line-height: 1.2;
    margin-bottom: 20px;
}

.hero-title .highlight {
    background: linear-gradient(45deg, #ffd700, #ffed4e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-description {
    font-size: 1.2rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.6;
    margin-bottom: 30px;
}

.hero-stats {
    display: flex;
    gap: 30px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8);
}

/* Search Container */
.search-container {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
}

.search-header {
    text-align: center;
    margin-bottom: 25px;
}

.search-header h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 8px;
}

.search-header p {
    color: #666;
    font-size: 0.95rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.form-select {
    padding: 12px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: white;
}

.form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.search-btn {
    width: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 15px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

/* Categories Section */
.categories-section {
    padding: 80px 0;
    background: #f8f9fa;
}

.section-header {
    text-align: center;
    margin-bottom: 60px;
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
    max-width: 600px;
    margin: 0 auto;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.category-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    border-color: #667eea;
}

.category-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    font-size: 1.5rem;
    color: white;
}

.category-icon.blue { background: linear-gradient(135deg, #667eea, #764ba2); }
.category-icon.green { background: linear-gradient(135deg, #56ab2f, #a8e6cf); }
.category-icon.purple { background: linear-gradient(135deg, #8e2de2, #4a00e0); }
.category-icon.orange { background: linear-gradient(135deg, #f093fb, #f5576c); }
.category-icon.pink { background: linear-gradient(135deg, #ff9a9e, #fecfef); }
.category-icon.indigo { background: linear-gradient(135deg, #a8edea, #fed6e3); }
.category-icon.teal { background: linear-gradient(135deg, #d299c2, #fef9d7); }
.category-icon.red { background: linear-gradient(135deg, #ff6b6b, #ee5a24); }

.category-content h3 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 10px;
}

.category-content p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
}

.category-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.count {
    font-weight: 600;
    color: #667eea;
}

.view-link {
    color: #667eea;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s ease;
}

.category-card:hover .view-link {
    transform: translateX(5px);
}

/* Featured Properties Section */
.featured-properties-section {
    padding: 80px 0;
    background: white;
}

.section-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 20px;
}

.properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.property-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
}

.property-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    border-color: #667eea;
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
    background: rgba(255, 255, 255, 0.9);
    color: #333;
    backdrop-filter: blur(10px);
}

.favorite-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.9);
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

.property-content {
    padding: 25px;
}

.property-title {
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
    gap: 20px;
    margin-bottom: 15px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #666;
    font-size: 14px;
}

.property-amenities {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
}

.amenity-tag {
    background: #f0f2f5;
    color: #666;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 500;
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

/* No Properties */
.no-properties {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
}

.no-properties-icon {
    font-size: 4rem;
    color: #ccc;
    margin-bottom: 20px;
}

.no-properties h3 {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 10px;
}

.no-properties p {
    color: #666;
    margin-bottom: 30px;
}

/* Stats Section */
.stats-section {
    padding: 80px 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 15px;
    opacity: 0.9;
}

.stat-content .stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.stat-content .stat-label {
    font-size: 1rem;
    opacity: 0.9;
}

/* CTA Section */
.cta-section {
    padding: 80px 0;
    background: #f8f9fa;
    text-align: center;
}

.cta-content h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 15px;
}

.cta-content p {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 30px;
}

.cta-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px 30px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    color: white;
    text-decoration: none;
}

.btn-outline {
    background: transparent;
    color: #667eea;
    padding: 15px 30px;
    border: 2px solid #667eea;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-outline:hover {
    background: #667eea;
    color: white;
    text-decoration: none;
}

.section-footer {
    text-align: center;
    margin-top: 50px;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-content {
        grid-template-columns: 1fr;
        gap: 40px;
        text-align: center;
    }
    
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-stats {
        justify-content: center;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .properties-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
}

@media (max-width: 480px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .section-header h2 {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
