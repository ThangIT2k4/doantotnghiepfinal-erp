{{-- Review Details Modal --}}
<div class="modal fade" id="reviewDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết đánh giá</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="review-details-content" id="reviewDetailsContent">
                    <!-- Content will be loaded by JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-outline-primary" onclick="ReviewsModule.editCurrentReview()">
                    <i class="fas fa-edit me-1"></i>Chỉnh sửa
                </button>
                <button type="button" class="btn btn-outline-info" onclick="ReviewsModule.shareCurrentReview()">
                    <i class="fas fa-share me-1"></i>Chia sẻ
                </button>
            </div>
        </div>
    </div>
</div>
