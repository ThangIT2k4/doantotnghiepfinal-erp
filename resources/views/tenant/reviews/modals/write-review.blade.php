{{-- Write Review Modal --}}
<div class="modal fade" id="writeReviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Viết đánh giá</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="writeReviewForm">
                    <div class="mb-4">
                        <label for="reviewProperty" class="form-label">Chọn phòng để đánh giá <span class="required">*</span></label>
                        <select class="form-select" id="reviewProperty" name="lease_id" required>
                            <option value="">Chọn phòng bạn đã/đang thuê</option>
                        </select>
                    </div>

                    <div class="rating-sections">
                        <div class="rating-section mb-4">
                            <label class="form-label">Đánh giá tổng thể <span class="required">*</span></label>
                            <div class="star-rating-large" id="overallRating">
                                <i class="fas fa-star" data-rating="1"></i>
                                <i class="fas fa-star" data-rating="2"></i>
                                <i class="fas fa-star" data-rating="3"></i>
                                <i class="fas fa-star" data-rating="4"></i>
                                <i class="fas fa-star" data-rating="5"></i>
                            </div>
                            <div class="rating-text">Chưa đánh giá</div>
                        </div>

                        <div class="detailed-ratings">
                            <h6>Đánh giá chi tiết</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Vị trí</label>
                                    <div class="star-rating-small" id="locationRating">
                                        <i class="fas fa-star" data-rating="1"></i>
                                        <i class="fas fa-star" data-rating="2"></i>
                                        <i class="fas fa-star" data-rating="3"></i>
                                        <i class="fas fa-star" data-rating="4"></i>
                                        <i class="fas fa-star" data-rating="5"></i>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Chất lượng phòng</label>
                                    <div class="star-rating-small" id="qualityRating">
                                        <i class="fas fa-star" data-rating="1"></i>
                                        <i class="fas fa-star" data-rating="2"></i>
                                        <i class="fas fa-star" data-rating="3"></i>
                                        <i class="fas fa-star" data-rating="4"></i>
                                        <i class="fas fa-star" data-rating="5"></i>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Thái độ chủ nhà</label>
                                    <div class="star-rating-small" id="serviceRating">
                                        <i class="fas fa-star" data-rating="1"></i>
                                        <i class="fas fa-star" data-rating="2"></i>
                                        <i class="fas fa-star" data-rating="3"></i>
                                        <i class="fas fa-star" data-rating="4"></i>
                                        <i class="fas fa-star" data-rating="5"></i>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Giá cả</label>
                                    <div class="star-rating-small" id="priceRating">
                                        <i class="fas fa-star" data-rating="1"></i>
                                        <i class="fas fa-star" data-rating="2"></i>
                                        <i class="fas fa-star" data-rating="3"></i>
                                        <i class="fas fa-star" data-rating="4"></i>
                                        <i class="fas fa-star" data-rating="5"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="reviewTitle" class="form-label">Tiêu đề đánh giá <span class="required">*</span></label>
                        <input type="text" class="form-control" id="reviewTitle" name="title" placeholder="Ví dụ: Phòng tuyệt vời, chủ nhà thân thiện" required>
                    </div>

                    <div class="mb-4">
                        <label for="reviewContent" class="form-label">Nội dung đánh giá <span class="required">*</span></label>
                        <textarea class="form-control" id="reviewContent" name="content" rows="6" placeholder="Chia sẻ chi tiết trải nghiệm của bạn về phòng trọ này..." required></textarea>
                        <div class="form-text">Tối thiểu 50 ký tự</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Điểm nổi bật</label>
                        <div class="highlight-options">
                            <label class="highlight-option">
                                <input type="checkbox" name="highlights" value="clean">
                                <span class="option-text">
                                    <i class="fas fa-sparkles"></i>
                                    Sạch sẽ
                                </span>
                            </label>
                            <label class="highlight-option">
                                <input type="checkbox" name="highlights" value="location">
                                <span class="option-text">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Vị trí tốt
                                </span>
                            </label>
                            <label class="highlight-option">
                                <input type="checkbox" name="highlights" value="price">
                                <span class="option-text">
                                    <i class="fas fa-dollar-sign"></i>
                                    Giá hợp lý
                                </span>
                            </label>
                            <label class="highlight-option">
                                <input type="checkbox" name="highlights" value="friendly">
                                <span class="option-text">
                                    <i class="fas fa-smile"></i>
                                    Chủ nhà thân thiện
                                </span>
                            </label>
                            <label class="highlight-option">
                                <input type="checkbox" name="highlights" value="quiet">
                                <span class="option-text">
                                    <i class="fas fa-volume-mute"></i>
                                    Yên tĩnh
                                </span>
                            </label>
                            <label class="highlight-option">
                                <input type="checkbox" name="highlights" value="convenient">
                                <span class="option-text">
                                    <i class="fas fa-shopping-cart"></i>
                                    Tiện ích
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="reviewImages" class="form-label">Hình ảnh (tùy chọn)</label>
                        <input type="file" class="form-control" id="reviewImages" multiple accept="image/*">
                        <div class="form-text">Tải lên hình ảnh thực tế của phòng</div>
                        <div id="reviewImagePreview" class="image-preview"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bạn có giới thiệu phòng này không?</label>
                        <div class="recommend-options">
                            <label class="recommend-option">
                                <input type="radio" name="recommend" value="yes">
                                <span class="option-text">
                                    <i class="fas fa-thumbs-up text-success"></i>
                                    Có, tôi sẽ giới thiệu
                                </span>
                            </label>
                            <label class="recommend-option">
                                <input type="radio" name="recommend" value="maybe">
                                <span class="option-text">
                                    <i class="fas fa-meh text-warning"></i>
                                    Có thể
                                </span>
                            </label>
                            <label class="recommend-option">
                                <input type="radio" name="recommend" value="no">
                                <span class="option-text">
                                    <i class="fas fa-thumbs-down text-danger"></i>
                                    Không
                                </span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="ReviewsModule.submitReview()">
                    <i class="fas fa-paper-plane me-1"></i>Đăng đánh giá
                </button>
            </div>
        </div>
    </div>
</div>
