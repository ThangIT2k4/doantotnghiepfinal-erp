@extends('layouts.app')

@section('title', 'Tin tức')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/news.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="news-container">
    <div class="container">
        <!-- Page Header -->
        <div class="news-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div>
                    <h1 class="page-title">Tin tức</h1>
                    <p class="page-subtitle">Cập nhật thông tin mới nhất về thị trường bất động sản</p>
                </div>
            </div>
        </div>

        <!-- Featured News -->
        <div class="featured-section">
            <h2 class="section-title">Tin nổi bật</h2>
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="featured-article">
                        <div class="article-image">
                            <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=600&h=400&fit=crop" alt="Tin nổi bật">
                            <div class="article-category">Thị trường</div>
                        </div>
                        <div class="article-content">
                            <h3 class="article-title">Thị trường cho thuê nhà ở Hà Nội: Xu hướng và dự báo 2024</h3>
                            <p class="article-excerpt">
                                Thị trường cho thuê nhà tại Hà Nội đang có những biến động tích cực với mức giá ổn định 
                                và nguồn cung dồi dào. Các chuyên gia dự báo xu hướng phát triển mạnh trong năm 2024...
                            </p>
                            <div class="article-meta">
                                <span class="article-date">
                                    <i class="fas fa-calendar"></i>
                                    25/12/2023
                                </span>
                                <span class="article-author">
                                    <i class="fas fa-user"></i>
                                    Nguyễn Văn A
                                </span>
                                <span class="article-views">
                                    <i class="fas fa-eye"></i>
                                    1,234 lượt xem
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="trending-articles">
                        <h4>Đang thịnh hành</h4>
                        <div class="trending-item">
                            <div class="trending-number">1</div>
                            <div class="trending-content">
                                <h5>5 tiêu chí chọn phòng trọ an toàn</h5>
                                <div class="trending-meta">
                                    <span>2,156 lượt xem</span>
                                </div>
                            </div>
                        </div>
                        <div class="trending-item">
                            <div class="trending-number">2</div>
                            <div class="trending-content">
                                <h5>Hướng dẫn ký hợp đồng thuê nhà</h5>
                                <div class="trending-meta">
                                    <span>1,987 lượt xem</span>
                                </div>
                            </div>
                        </div>
                        <div class="trending-item">
                            <div class="trending-number">3</div>
                            <div class="trending-content">
                                <h5>Kinh nghiệm thuê phòng cho sinh viên</h5>
                                <div class="trending-meta">
                                    <span>1,543 lượt xem</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- News Categories -->
        <div class="categories-section">
            <div class="category-tabs">
                <button class="category-tab active" data-category="all">Tất cả</button>
                <button class="category-tab" data-category="market">Thị trường</button>
                <button class="category-tab" data-category="tips">Mẹo hay</button>
                <button class="category-tab" data-category="legal">Pháp lý</button>
                <button class="category-tab" data-category="trends">Xu hướng</button>
            </div>
        </div>

        <!-- News Articles -->
        <div class="articles-section">
            <div class="row">
                <!-- Article 1 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <article class="news-article" data-category="tips">
                        <div class="article-image">
                            <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=400&h=250&fit=crop" alt="Mẹo thuê phòng">
                            <div class="article-category">Mẹo hay</div>
                        </div>
                        <div class="article-content">
                            <h4 class="article-title">10 câu hỏi cần đặt khi thuê phòng trọ</h4>
                            <p class="article-excerpt">
                                Những câu hỏi quan trọng bạn nên đặt ra khi xem phòng để đảm bảo chọn được nơi ở phù hợp...
                            </p>
                            <div class="article-meta">
                                <span class="article-date">24/12/2023</span>
                                <span class="article-views">892 lượt xem</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Article 2 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <article class="news-article" data-category="legal">
                        <div class="article-image">
                            <img src="https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=400&h=250&fit=crop" alt="Pháp lý">
                            <div class="article-category">Pháp lý</div>
                        </div>
                        <div class="article-content">
                            <h4 class="article-title">Quyền lợi và nghĩa vụ của người thuê nhà</h4>
                            <p class="article-excerpt">
                                Hiểu rõ quyền lợi và nghĩa vụ của mình khi thuê nhà để bảo vệ lợi ích hợp pháp...
                            </p>
                            <div class="article-meta">
                                <span class="article-date">23/12/2023</span>
                                <span class="article-views">1,156 lượt xem</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Article 3 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <article class="news-article" data-category="market">
                        <div class="article-image">
                            <img src="https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=400&h=250&fit=crop" alt="Thị trường">
                            <div class="article-category">Thị trường</div>
                        </div>
                        <div class="article-content">
                            <h4 class="article-title">Giá thuê nhà tại Hà Nội quý 4/2023</h4>
                            <p class="article-excerpt">
                                Báo cáo chi tiết về mức giá thuê nhà trung bình tại các quận trong Hà Nội...
                            </p>
                            <div class="article-meta">
                                <span class="article-date">22/12/2023</span>
                                <span class="article-views">2,034 lượt xem</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Article 4 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <article class="news-article" data-category="trends">
                        <div class="article-image">
                            <img src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400&h=250&fit=crop" alt="Xu hướng">
                            <div class="article-category">Xu hướng</div>
                        </div>
                        <div class="article-content">
                            <h4 class="article-title">Xu hướng thiết kế phòng trọ hiện đại</h4>
                            <p class="article-excerpt">
                                Những ý tưởng thiết kế thông minh giúp tối ưu hóa không gian phòng trọ nhỏ...
                            </p>
                            <div class="article-meta">
                                <span class="article-date">21/12/2023</span>
                                <span class="article-views">756 lượt xem</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Article 5 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <article class="news-article" data-category="tips">
                        <div class="article-image">
                            <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=250&fit=crop" alt="Mẹo hay">
                            <div class="article-category">Mẹo hay</div>
                        </div>
                        <div class="article-content">
                            <h4 class="article-title">Cách tiết kiệm chi phí khi thuê phòng trọ</h4>
                            <p class="article-excerpt">
                                Những mẹo hay giúp bạn giảm thiểu chi phí thuê phòng mà vẫn đảm bảo chất lượng...
                            </p>
                            <div class="article-meta">
                                <span class="article-date">20/12/2023</span>
                                <span class="article-views">1,432 lượt xem</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Article 6 -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <article class="news-article" data-category="market">
                        <div class="article-image">
                            <img src="https://images.unsplash.com/photo-1571055107559-3e67626fa8be?w=400&h=250&fit=crop" alt="Thị trường">
                            <div class="article-category">Thị trường</div>
                        </div>
                        <div class="article-content">
                            <h4 class="article-title">Top 5 khu vực cho thuê phòng trọ tốt nhất</h4>
                            <p class="article-excerpt">
                                Điểm danh những khu vực có mức giá hợp lý và tiện ích đầy đủ cho người thuê...
                            </p>
                            <div class="article-meta">
                                <span class="article-date">19/12/2023</span>
                                <span class="article-views">987 lượt xem</span>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
