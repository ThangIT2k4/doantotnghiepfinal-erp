@if($reviews->count() > 0)
    <div class="row">
        @foreach($reviews as $review)
            <div class="col-12 mb-3">
                <div class="card review-card" data-review-id="{{ $review->id }}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Review Header -->
                                <div class="d-flex align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            <a href="{{ route('staff.reviews.show', $review->id) }}" class="text-decoration-none">
                                                {{ $review->title }}
                                            </a>
                                        </h5>
                                        <div class="text-muted small mb-2">
                                            <i class="mdi mdi-map-marker me-1"></i>
                                            {{ $review->property_name }} - {{ $review->unit_name }}
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <!-- Star Rating -->
                                            <div class="rating me-3">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= $review->overall_rating)
                                                        <i class="mdi mdi-star text-warning"></i>
                                                    @elseif($i - 0.5 <= $review->overall_rating)
                                                        <i class="mdi mdi-star-half-full text-warning"></i>
                                                    @else
                                                        <i class="mdi mdi-star-outline text-muted"></i>
                                                    @endif
                                                @endfor
                                                <span class="ms-1 fw-bold">{{ $review->overall_rating }}/5</span>
                                            </div>
                                            <!-- Status Badge -->
                                            @if($review->replies_count > 0)
                                                <span class="badge bg-success">Đã phản hồi</span>
                                            @else
                                                <span class="badge bg-warning">Chờ phản hồi</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-muted small">
                                            {{ $review->created_at->format('d/m/Y H:i') }}
                                        </div>
                                        <div class="text-muted small">
                                            bởi {{ $review->tenant_name ?? 'Khách hàng' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Review Content -->
                                <div class="review-content mb-3">
                                    <p class="mb-2">{{ Str::limit($review->content, 200) }}</p>
                                    
                                    {{-- <!-- Debug Ratings -->
                                    @if(config('app.debug'))
                                        <div class="debug-ratings mb-2" style="background: #f8f9fa; padding: 0.5rem; border-radius: 0.25rem; font-size: 0.8rem;">
                                            <strong>Debug Ratings:</strong>
                                            Location: {{ $review->location_rating ?? 'null' }} | 
                                            Quality: {{ $review->quality_rating ?? 'null' }} | 
                                            Service: {{ $review->service_rating ?? 'null' }} | 
                                            Price: {{ $review->price_rating ?? 'null' }}
                                        </div>
                                    @endif --}}

                                    <!-- Detail Ratings -->
                                    @if($review->location_rating || $review->quality_rating || $review->service_rating || $review->price_rating)
                                        <div class="detail-ratings">
                                            <div class="row">
                                                @if($review->location_rating)
                                                    <div class="col-sm-6 col-md-3 mb-1">
                                                        <small class="text-muted">Vị trí:</small>
                                                        <div class="rating-small">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                @if($i <= $review->location_rating)
                                                                    <i class="mdi mdi-star text-warning"></i>
                                                                @else
                                                                    <i class="mdi mdi-star-outline text-muted"></i>
                                                                @endif
                                                            @endfor
                                                            <span class="ms-1 small text-muted">({{ $review->location_rating }})</span>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($review->quality_rating)
                                                    <div class="col-sm-6 col-md-3 mb-1">
                                                        <small class="text-muted">Chất lượng:</small>
                                                        <div class="rating-small">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                @if($i <= $review->quality_rating)
                                                                    <i class="mdi mdi-star text-warning"></i>
                                                                @else
                                                                    <i class="mdi mdi-star-outline text-muted"></i>
                                                                @endif
                                                            @endfor
                                                            <span class="ms-1 small text-muted">({{ $review->quality_rating }})</span>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($review->service_rating)
                                                    <div class="col-sm-6 col-md-3 mb-1">
                                                        <small class="text-muted">Dịch vụ:</small>
                                                        <div class="rating-small">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                @if($i <= $review->service_rating)
                                                                    <i class="mdi mdi-star text-warning"></i>
                                                                @else
                                                                    <i class="mdi mdi-star-outline text-muted"></i>
                                                                @endif
                                                            @endfor
                                                            <span class="ms-1 small text-muted">({{ $review->service_rating }})</span>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($review->price_rating)
                                                    <div class="col-sm-6 col-md-3 mb-1">
                                                        <small class="text-muted">Giá cả:</small>
                                                        <div class="rating-small">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                @if($i <= $review->price_rating)
                                                                    <i class="mdi mdi-star text-warning"></i>
                                                                @else
                                                                    <i class="mdi mdi-star-outline text-muted"></i>
                                                                @endif
                                                            @endfor
                                                            <span class="ms-1 small text-muted">({{ $review->price_rating }})</span>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Highlights -->
                                    @if($review->highlights && count($review->highlights) > 0)
                                        <div class="highlights mt-2">
                                            <small class="text-muted">Điểm nổi bật:</small>
                                            <div class="mt-1">
                                                @foreach($review->highlights as $highlight)
                                                    <span class="badge bg-light text-dark me-1 mb-1">{{ $highlight }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Recommend -->
                                    @if($review->recommend)
                                        <div class="recommend mt-2">
                                            <small class="text-muted">Khuyến nghị:</small>
                                            <span class="badge bg-info ms-1">{{ $review->recommend_label }}</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Replies Preview -->
                                @if($review->replies_count > 0)
                                    <div class="replies-preview">
                                        <div class="d-flex align-items-center text-muted small">
                                            <i class="mdi mdi-comment-multiple me-1"></i>
                                            {{ $review->replies_count }} phản hồi
                                            <a href="{{ route('staff.reviews.show', $review->id) }}" class="ms-2 text-primary">
                                                Xem chi tiết
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-4">
                                <!-- Action Buttons -->
                                <div class="d-flex flex-column gap-2">
                                    <a href="{{ route('staff.reviews.show', $review->id) }}" class="btn btn-outline-primary btn-sm">
                                        <i class="mdi mdi-eye me-1"></i>Xem chi tiết
                                    </a>
                                    
                                    @if($review->replies_count == 0)
                                        <button class="btn btn-primary btn-sm" onclick="openReplyModal({{ $review->id }})">
                                            <i class="mdi mdi-reply me-1"></i>Phản hồi
                                        </button>
                                    @else
                                        <button class="btn btn-success btn-sm" onclick="openReplyModal({{ $review->id }})">
                                            <i class="mdi mdi-reply me-1"></i>Thêm phản hồi
                                        </button>
                                    @endif
                                </div>

                                <!-- Review Stats -->
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span><i class="mdi mdi-eye me-1"></i>{{ $review->view_count ?? 0 }} lượt xem</span>
                                        <span><i class="mdi mdi-thumb-up me-1"></i>{{ $review->helpful_count ?? 0 }} hữu ích</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

@else
    <div class="text-center py-5">
        <div class="mb-3">
            <i class="mdi mdi-comment-outline" style="font-size: 4rem; color: #dee2e6;"></i>
        </div>
        <h5 class="text-muted">Chưa có đánh giá nào</h5>
        <p class="text-muted">Hiện tại chưa có đánh giá nào cho các bất động sản được phân quản lý.</p>
    </div>
@endif

<style>
.rating {
    font-size: 1rem;
}

.rating-small {
    font-size: 0.8rem;
}

.rating i {
    margin-right: 1px;
}

.review-card {
    transition: all 0.3s ease;
    border: 1px solid #e3e6f0;
}

.review-card:hover {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border-color: #5a5c69;
}

.detail-ratings .rating-small i {
    font-size: 0.7rem;
}

.highlights .badge {
    font-size: 0.75rem;
}

.replies-preview {
    border-top: 1px solid #e3e6f0;
    padding-top: 0.75rem;
    margin-top: 0.75rem;
}
</style>
