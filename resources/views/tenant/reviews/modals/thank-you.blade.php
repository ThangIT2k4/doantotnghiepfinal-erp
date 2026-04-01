{{-- Thank You Modal --}}
<div class="modal fade" id="thankModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="thank-icon">
                    <i class="fas fa-heart text-danger"></i>
                </div>
                <h4 class="mt-3">Cảm ơn chủ nhà</h4>
                <p>Gửi lời cảm ơn đến chủ nhà vì đã phản hồi đánh giá của bạn?</p>
                <textarea class="form-control" id="thankMessage" rows="3" placeholder="Viết lời cảm ơn (tùy chọn)..."></textarea>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" onclick="ReviewsModule.sendThankYou()">
                    <i class="fas fa-heart me-1"></i>Gửi cảm ơn
                </button>
            </div>
        </div>
    </div>
</div>
