/**
 * Reviews System JavaScript
 * Handles review CRUD operations with notification system integration
 */

// Reviews Module
const ReviewsModule = (function() {
    // Private variables
    let currentReviewId = null;
    let reviewableLeases = [];

    // Private methods
    function loadReviewableLeases() {
        fetch('/tenant/api/reviews/reviewable-leases')
            .then(response => response.json())
            .then(data => {
                reviewableLeases = data;
                updateLeaseSelect();
            })
            .catch(error => {
                console.error('Error loading reviewable leases:', error);
                if (typeof Notify !== 'undefined') {
                    Notify.error('Không thể tải danh sách phòng để đánh giá');
                } else {
                    console.error('Không thể tải danh sách phòng để đánh giá');
                }
            });
    }

    function updateLeaseSelect() {
        const select = document.getElementById('reviewProperty');
        if (!select) return;
        
        // Clear existing options except the first one
        select.innerHTML = '<option value="">Chọn phòng bạn đã/đang thuê</option>';
        
        reviewableLeases.forEach(lease => {
            const option = document.createElement('option');
            option.value = lease.id;
            option.textContent = `${lease.property_name} - ${lease.unit_name} (${lease.rent_amount.toLocaleString()} VNĐ/tháng)`;
            select.appendChild(option);
        });
    }

    function initializeStarRatings() {
        // Overall rating
        const overallRating = document.getElementById('overallRating');
        if (overallRating) {
            setupStarRating(overallRating, 'overall');
        }
        
        // Detail ratings
        const detailRatings = ['locationRating', 'qualityRating', 'serviceRating', 'priceRating'];
        detailRatings.forEach(ratingId => {
            const rating = document.getElementById(ratingId);
            if (rating) {
                setupStarRating(rating, ratingId.replace('Rating', ''));
            }
        });
    }

    function setupStarRating(element, type) {
        const stars = element.querySelectorAll('i');
        let currentRating = 0;
        
        stars.forEach((star, index) => {
            star.addEventListener('click', function() {
                currentRating = index + 1;
                updateStarDisplay(stars, currentRating);
                updateRatingText(type, currentRating);
                updateHiddenInput(type, currentRating);
            });
            
            star.addEventListener('mouseenter', function() {
                updateStarDisplay(stars, index + 1);
            });
        });
        
        element.addEventListener('mouseleave', function() {
            updateStarDisplay(stars, currentRating);
        });
    }

    function updateHiddenInput(type, rating) {
        const input = document.getElementById(`${type}RatingInput`);
        if (input) {
            input.value = rating;
        }
    }

    function updateStarDisplay(stars, rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.className = 'fas fa-star selected';
            } else {
                star.className = 'far fa-star';
            }
        });
    }

    function updateRatingText(type, rating) {
        const ratingTexts = {
            1: 'Rất tệ',
            2: 'Tệ',
            3: 'Bình thường',
            4: 'Tốt',
            5: 'Tuyệt vời'
        };
        
        const textElement = document.querySelector(`#${type}Rating`).nextElementSibling;
        if (textElement && textElement.classList.contains('rating-text')) {
            textElement.textContent = ratingTexts[rating] || 'Chưa đánh giá';
        }
    }

    function initializeFormValidation() {
        // Handle different form IDs for different pages
        const formIds = ['writeReviewForm'];
        let form = null;
        
        for (const formId of formIds) {
            form = document.getElementById(formId);
            if (form) break;
        }
        
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                submitReviewForm();
            });
        }
    }

    function initializeFilters() {
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                filterReviews();
            });
        }

        // Rating filter
        const ratingFilter = document.getElementById('ratingFilter');
        if (ratingFilter) {
            ratingFilter.addEventListener('change', function() {
                filterReviews();
            });
        }

        // Status filter tabs
        const filterTabs = document.querySelectorAll('.filter-tab');
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                filterTabs.forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                filterReviews();
            });
        });
    }

    function initializeImagePreview() {
        const imageInput = document.getElementById('reviewImages');
        if (imageInput) {
            imageInput.addEventListener('change', function() {
                const files = this.files;
                const preview = document.getElementById('reviewImagePreview');
                preview.innerHTML = '';

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const previewItem = document.createElement('div');
                            previewItem.className = 'preview-item';
                            previewItem.innerHTML = `
                                <img src="${e.target.result}" alt="Preview">
                                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">
                                    <i class="fas fa-times"></i>
                                </button>
                            `;
                            preview.appendChild(previewItem);
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });
        }
    }

    function filterReviews() {
        const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
        const ratingFilter = document.getElementById('ratingFilter')?.value || '';
        const activeTab = document.querySelector('.filter-tab.active');
        const statusFilter = activeTab?.dataset.status || 'all';

        const reviewCards = document.querySelectorAll('.review-card');
        
        reviewCards.forEach(card => {
            const propertyTitle = card.querySelector('.property-title')?.textContent.toLowerCase() || '';
            const cardRating = card.dataset.rating || '';
            const cardStatus = card.dataset.status || '';

            let show = true;

            // Search filter
            if (searchTerm && !propertyTitle.includes(searchTerm)) {
                show = false;
            }

            // Rating filter
            if (ratingFilter && cardRating !== ratingFilter) {
                show = false;
            }

            // Status filter
            if (statusFilter !== 'all' && cardStatus !== statusFilter) {
                show = false;
            }

            card.style.display = show ? 'block' : 'none';
        });
    }

    function submitReviewForm() {
        const leaseId = document.getElementById('reviewProperty')?.value;
        const title = document.getElementById('reviewTitle')?.value;
        const content = document.getElementById('reviewContent')?.value;
        const overallRating = getOverallRating();

        // Validation
        if (!leaseId) {
            if (typeof Notify !== 'undefined') {
                Notify.error('Vui lòng chọn phòng để đánh giá');
            } else {
                alert('Vui lòng chọn phòng để đánh giá');
            }
            return;
        }

        if (!title || title.length < 5) {
            if (typeof Notify !== 'undefined') {
                Notify.error('Tiêu đề phải có ít nhất 5 ký tự');
            } else {
                alert('Tiêu đề phải có ít nhất 5 ký tự');
            }
            return;
        }

        if (!content || content.length < 50) {
            if (typeof Notify !== 'undefined') {
                Notify.error('Nội dung phải có ít nhất 50 ký tự');
            } else {
                alert('Nội dung phải có ít nhất 50 ký tự');
            }
            return;
        }
        
        if (!overallRating) {
            if (typeof Notify !== 'undefined') {
                Notify.error('Vui lòng đánh giá tổng thể');
            } else {
                alert('Vui lòng đánh giá tổng thể');
            }
            return;
        }
        
        // Prepare form data
        const submitData = new FormData();
        if (leaseId) {
            submitData.append('lease_id', leaseId);
        }
        submitData.append('title', title);
        submitData.append('content', content);
        submitData.append('overall_rating', overallRating);
        
        // Add detail ratings
        const detailRatings = ['location', 'quality', 'service', 'price'];
        detailRatings.forEach(rating => {
            const value = getDetailRating(rating);
            submitData.append(`${rating}_rating`, value || 0);
        });
        
        // Add highlights
        const highlights = getSelectedHighlights();
        if (highlights.length > 0) {
            highlights.forEach(highlight => {
                submitData.append('highlights[]', highlight);
            });
        }
        
        // Add recommendation
        const recommend = getRecommendation();
        if (recommend) {
            submitData.append('recommend', recommend);
        }
        
        // Add images
        const images = document.getElementById('reviewImages')?.files;
        if (images && images.length > 0) {
            for (let i = 0; i < images.length; i++) {
                submitData.append('images[]', images[i]);
            }
        }
        
        // Submit review
        submitReview(submitData, '/tenant/reviews', 'POST');
    }

    function getOverallRating() {
        const stars = document.querySelectorAll('#overallRating i.selected');
        return stars.length;
    }

    function getDetailRating(type) {
        const stars = document.querySelectorAll(`#${type}Rating i.selected`);
        return stars.length;
    }

    function getSelectedHighlights() {
        const highlights = [];
        const checkboxes = document.querySelectorAll('input[name="highlights"]:checked');
        checkboxes.forEach(checkbox => {
            highlights.push(checkbox.value);
        });
        return highlights;
    }

    function getRecommendation() {
        const radio = document.querySelector('input[name="recommend"]:checked');
        return radio ? radio.value : null;
    }

    function submitReview(formData, url = '/tenant/reviews', method = 'POST') {
        // Show loading state - handle both modal and page forms
        const submitBtn = document.querySelector('#writeReviewModal .btn-primary') || 
                         document.querySelector('#writeReviewForm .btn-primary') ||
                         document.querySelector('#submitReviewBtn') ||
                         document.querySelector('form .btn-primary');
        
        let originalText = '';
        if (submitBtn) {
            originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
            submitBtn.disabled = true;
            submitBtn.classList.add('btn-loading');
            
            // Show spinner
            const spinner = submitBtn.querySelector('.spinner-border');
            if (spinner) {
                spinner.classList.remove('d-none');
            }
        }
        
        // Debug: Log form data
        console.log('Submitting review with data:');
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }
        
        fetch(url, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.errors) {
                console.log('Validation errors:', data.errors);
            }
            if (data.success) {
                if (typeof Notify !== 'undefined') {
                    Notify.success(data.message);
                } else {
                    alert('Thành công: ' + data.message);
                }
                // Close modal if exists, otherwise redirect
                const modal = document.getElementById('writeReviewModal');
                if (modal) {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                
                setTimeout(() => {
                    if (modal) {
                        window.location.reload();
                    } else {
                        // For page forms, redirect to reviews index
                        window.location.href = '/tenant/reviews';
                    }
                }, 1500);
            } else {
                if (typeof Notify !== 'undefined') {
                    Notify.error(data.message);
                    if (data.errors) {
                        // Show validation errors
                        Object.values(data.errors).forEach(error => {
                            if (typeof Notify !== 'undefined') {
                                Notify.error(error[0]);
                            }
                        });
                    }
                } else {
                    alert('Lỗi: ' + data.message);
                    if (data.errors) {
                        Object.values(data.errors).forEach(error => {
                            alert('Lỗi: ' + error[0]);
                        });
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error submitting review:', error);
            if (typeof Notify !== 'undefined') {
                Notify.error('Có lỗi xảy ra khi đăng đánh giá');
            } else {
                alert('Có lỗi xảy ra khi đăng đánh giá');
            }
        })
        .finally(() => {
            // Reset button state
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-loading');
                
                // Hide spinner
                const spinner = submitBtn.querySelector('.spinner-border');
                if (spinner) {
                    spinner.classList.add('d-none');
                }
            }
        });
    }

    // Public methods
    return {
        initIndex: function() {
            this.initializeReviews();
        },

        initCreate: function() {
            this.initializeStarRatings();
            this.initializeFormValidation();
            this.initializeImagePreview();
        },


        initShow: function() {
            // Initialize show page specific functionality
        },

        initializeReviews: function() {
            // Load reviewable leases for modal
            loadReviewableLeases();
            
            // Initialize star ratings
            this.initializeStarRatings();
            
            // Initialize form validation
            this.initializeFormValidation();
            
            // Initialize filters
            initializeFilters();
            
            // Initialize image preview
            this.initializeImagePreview();
        },

        initializeStarRatings: function() {
            initializeStarRatings();
        },

        initializeFormValidation: function() {
            initializeFormValidation();
        },

        initializeImagePreview: function() {
            initializeImagePreview();
        },

        loadExistingRatings: function() {
            // Load existing ratings for edit form
            const overallRating = document.getElementById('overallRatingInput')?.value;
            if (overallRating) {
                const stars = document.querySelectorAll('#overallRating i');
                stars.forEach((star, index) => {
                    if (index < overallRating) {
                        star.className = 'fas fa-star selected';
                    } else {
                        star.className = 'far fa-star';
                    }
                });
                updateRatingText('overall', overallRating);
            }

            // Load detail ratings
            const detailRatings = ['location', 'quality', 'service', 'price'];
            detailRatings.forEach(rating => {
                const ratingValue = document.getElementById(`${rating}RatingInput`)?.value;
                if (ratingValue) {
                    const stars = document.querySelectorAll(`#${rating}Rating i`);
                    stars.forEach((star, index) => {
                        if (index < ratingValue) {
                            star.className = 'fas fa-star selected';
                        } else {
                            star.className = 'far fa-star';
                        }
                    });
                    updateRatingText(rating, ratingValue);
                }
            });
        },

        openWriteReviewModal: function() {
            const modal = new bootstrap.Modal(document.getElementById('writeReviewModal'));
            modal.show();
        },

        viewReviewDetails: function(reviewId) {
            currentReviewId = reviewId;
            // Load review details via AJAX
            fetch(`/tenant/reviews/${reviewId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('reviewDetailsContent').innerHTML = data.html;
                        const modal = new bootstrap.Modal(document.getElementById('reviewDetailsModal'));
                        modal.show();
                    }
                })
                .catch(error => {
                    console.error('Error loading review details:', error);
                });
        },


        deleteReview: function(reviewId) {
            if (typeof Notify !== 'undefined' && Notify.confirmDelete) {
                Notify.confirmDelete('đánh giá này', function() {
                    fetch(`/tenant/reviews/${reviewId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            if (typeof Notify !== 'undefined') {
                                Notify.success(data.message);
                            }
                            window.location.reload();
                        } else {
                            if (typeof Notify !== 'undefined') {
                                Notify.error(data.message || 'Có lỗi xảy ra khi xóa đánh giá');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting review:', error);
                        if (typeof Notify !== 'undefined') {
                            Notify.error('Có lỗi xảy ra khi xóa đánh giá: ' + error.message);
                        }
                    });
                });
            } else {
                // Fallback: direct delete without confirmation
                fetch(`/tenant/reviews/${reviewId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Lỗi: ' + (data.message || 'Có lỗi xảy ra khi xóa đánh giá'));
                    }
                })
                .catch(error => {
                    console.error('Error deleting review:', error);
                    alert('Có lỗi xảy ra khi xóa đánh giá: ' + error.message);
                });
            }
        },

        thankLandlord: function(reviewId) {
            currentReviewId = reviewId;
            const modal = new bootstrap.Modal(document.getElementById('thankModal'));
            modal.show();
        },

        shareReview: function(reviewId) {
            // Share functionality
            if (navigator.share) {
                navigator.share({
                    title: 'Đánh giá phòng trọ',
                    text: 'Xem đánh giá này',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    if (typeof Notify !== 'undefined') {
                        Notify.success('Đã sao chép link vào clipboard');
                    } else {
                        alert('Đã sao chép link vào clipboard');
                    }
                });
            }
        },

        submitReview: function() {
            submitReviewForm();
        },


        shareCurrentReview: function() {
            if (currentReviewId) {
                this.shareReview(currentReviewId);
            }
        },

        sendThankYou: function() {
            const message = document.getElementById('thankMessage')?.value || '';
            if (currentReviewId) {
                fetch(`/tenant/reviews/${currentReviewId}/reply`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        content: message || 'Cảm ơn bạn đã phản hồi!',
                        parent_reply_id: null
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof Notify !== 'undefined') {
                            Notify.success('Lời cảm ơn đã được gửi!');
                        } else {
                            alert('Lời cảm ơn đã được gửi!');
                        }
                        const modal = bootstrap.Modal.getInstance(document.getElementById('thankModal'));
                        modal.hide();
                        window.location.reload();
                    } else {
                        if (typeof Notify !== 'undefined') {
                            Notify.error(data.message);
                        } else {
                            alert('Lỗi: ' + data.message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error sending thank you:', error);
                    if (typeof Notify !== 'undefined') {
                        Notify.error('Có lỗi xảy ra khi gửi lời cảm ơn');
                    } else {
                        alert('Có lỗi xảy ra khi gửi lời cảm ơn');
                    }
                });
            }
        }
    };
})();

// Legacy function names for backward compatibility
function initializeReviews() {
    ReviewsModule.initializeReviews();
}

function openWriteReviewModal() {
    ReviewsModule.openWriteReviewModal();
}

function viewReviewDetails(reviewId) {
    ReviewsModule.viewReviewDetails(reviewId);
}


function deleteReview(reviewId) {
    ReviewsModule.deleteReview(reviewId);
}

function thankLandlord(reviewId) {
    ReviewsModule.thankLandlord(reviewId);
}

function shareReview(reviewId) {
    ReviewsModule.shareReview(reviewId);
}

function submitReview() {
    ReviewsModule.submitReview();
}


function shareCurrentReview() {
    ReviewsModule.shareCurrentReview();
}

function sendThankYou() {
    ReviewsModule.sendThankYou();
}