@extends('layouts.app')

@section('title', 'Viết đánh giá')

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/reviews.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/user/reviews-enhanced.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/notifications.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
/* Review Create Form with Blue Theme */
.review-form-container {
    margin-top: 2rem;
}

.review-form-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
}

.review-form-card .form-label {
    font-weight: 600;
    color: var(--blue-primary);
    margin-bottom: 0.75rem;
}

.review-form-card .form-control,
.review-form-card .form-select {
    border: 1px solid var(--blue-border);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.review-form-card .form-control:focus,
.review-form-card .form-select:focus {
    border-color: var(--blue-primary);
    box-shadow: 0 0 0 0.25rem rgba(39, 102, 236, 0.15);
}

.review-form-card .required {
    color: #dc3545;
}

/* Star Rating with Blue Theme */
.star-rating-large i,
.star-rating-small i {
    color: #ddd;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.5rem;
}

.star-rating-small i {
    font-size: 1.2rem;
}

.star-rating-large i.active,
.star-rating-small i.active {
    color: #FFD700;
}

.star-rating-large i:hover,
.star-rating-small i:hover {
    color: #FFD700;
    transform: scale(1.1);
}

.rating-text {
    margin-top: 0.5rem;
    color: var(--blue-primary);
    font-weight: 600;
}

/* Highlight Options with Blue Theme */
.highlight-options {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.highlight-option {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border: 2px solid var(--blue-border);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.highlight-option:hover {
    border-color: var(--blue-primary);
    background: var(--blue-bg-light);
    transform: translateY(-2px);
}

.highlight-option input[type="checkbox"] {
    margin-right: 0.5rem;
}

.highlight-option input[type="checkbox"]:checked + .option-text {
    color: var(--blue-primary);
    font-weight: 600;
}

.highlight-option:has(input[type="checkbox"]:checked) {
    border-color: var(--blue-primary);
    background: var(--blue-bg-light);
}

/* Recommend Options */
.recommend-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.recommend-option {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border: 2px solid var(--blue-border);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.recommend-option:hover {
    border-color: var(--blue-primary);
    background: var(--blue-bg-light);
}

.recommend-option input[type="radio"] {
    margin-right: 0.5rem;
}

.recommend-option:has(input[type="radio"]:checked) {
    border-color: var(--blue-primary);
    background: var(--blue-bg-light);
}

/* Image Upload Area */
.image-upload-area {
    border: 2px dashed var(--blue-border) !important;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: var(--blue-bg-light);
}

.image-upload-area:hover {
    border-color: var(--blue-primary) !important;
    background: rgba(39, 102, 236, 0.05);
}

.image-upload-area i {
    color: var(--blue-primary);
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--blue-border);
}

.form-actions .btn {
    border-radius: 12px;
    font-weight: 600;
    padding: 0.75rem 2rem;
    transition: all 0.3s ease;
}

.form-actions .btn-primary {
    background: var(--blue-gradient);
    border: none;
    color: white;
}

.form-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(39, 102, 236, 0.3);
}

.form-actions .btn-secondary {
    background: white;
    border: 2px solid var(--blue-border);
    color: var(--blue-primary);
}

.form-actions .btn-secondary:hover {
    background: var(--blue-bg-light);
    border-color: var(--blue-primary);
}

/* Responsive */
@media (max-width: 768px) {
    .review-form-card {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>
@endpush

@push('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="{{ asset('assets/js/notifications.js') }}?v={{ time() }}"></script>
<script src="{{ asset('assets/js/user/reviews.js') }}?v={{ time() }}"></script>
<script>
// Page-specific initialization
document.addEventListener('DOMContentLoaded', function() {
    ReviewsModule.initCreate();
    
    // Initialize notification system
    if (typeof Notify !== 'undefined') {
        // Show welcome message
        Notify.info('Chia sẻ trải nghiệm của bạn để giúp người khác tìm được phòng trọ phù hợp!', 'Chào mừng đến với hệ thống đánh giá');
    }
    
    // Enhanced form validation with notifications
    const form = document.getElementById('writeReviewForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Create form submitted');
            // Show loading notification
            if (typeof Notify !== 'undefined') {
                Notify.info('Đang xử lý đánh giá của bạn...', 'Vui lòng chờ');
            }
            
            // Show loading state on button
            const submitBtn = document.getElementById('submitReviewBtn');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang đăng...';
                submitBtn.disabled = true;
                
                // Reset after 5 seconds if no response
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    }
    
    // Image upload with drag & drop for multiple images
    const reviewImageInput = document.getElementById('reviewImages');
    const reviewImagePreview = document.getElementById('reviewImagePreview');
    
    if (reviewImageInput) {
        reviewImageInput.addEventListener('change', function(e) {
            reviewImagePreview.innerHTML = '';
            
            if (e.target.files && e.target.files.length > 0) {
                const files = Array.from(e.target.files);
                
                // Check max 5 images
                if (files.length > 5) {
                    alert('Bạn chỉ có thể tải lên tối đa 5 ảnh. Vui lòng chọn lại.');
                    this.value = '';
                    return;
                }
                
                let loadedCount = 0;
                const totalFiles = files.length;
                
                files.forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        // Check file size (2MB limit)
                        if (file.size > 2 * 1024 * 1024) {
                            alert(`File "${file.name}" quá lớn (>2MB). Vui lòng chọn file nhỏ hơn.`);
                            return;
                        }
                        
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            loadedCount++;
                            
                            const col = document.createElement('div');
                            col.className = 'col-md-4 mb-2';
                            col.innerHTML = `
                                <div class="image-preview-item position-relative">
                                    <img src="${e.target.result}" class="img-thumbnail" style="height: 150px; object-fit: cover; width: 100%;">
                                    <div class="position-absolute top-0 start-0 bg-dark bg-opacity-75 text-white px-1 rounded-bottom-end" style="font-size: 0.7rem;">
                                        ${file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name}
                                    </div>
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 btn-remove-review-image" 
                                            data-index="${index}" title="Xóa ảnh" style="margin: 5px;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            `;
                            reviewImagePreview.appendChild(col);
                            
                            // Show completion message
                            if (loadedCount === totalFiles) {
                                if (typeof Notify !== 'undefined') {
                                    Notify.success(`Đã tải ${loadedCount} ảnh thành công!`);
                                }
                            }
                        };
                        reader.readAsDataURL(file);
                    } else {
                        alert(`File "${file.name}" không phải là hình ảnh. Vui lòng chọn file hình ảnh.`);
                    }
                });
            }
        });
    }
    
    // Handle remove image button click
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-review-image')) {
            const button = e.target.closest('.btn-remove-review-image');
            const imageContainer = button.closest('.col-md-4');
            
            // Add fade out animation
            imageContainer.style.transition = 'opacity 0.3s ease';
            imageContainer.style.opacity = '0';
            
            setTimeout(() => {
                // Get all preview containers to find the index
                const allContainers = Array.from(reviewImagePreview.querySelectorAll('.col-md-4'));
                const containerIndex = allContainers.indexOf(imageContainer);
                
                imageContainer.remove();
                
                // Update file input
                const remainingImages = reviewImagePreview.querySelectorAll('.col-md-4');
                if (remainingImages.length === 0) {
                    if (reviewImageInput) {
                        reviewImageInput.value = '';
                    }
                } else {
                    // Rebuild file list without removed file
                    const dt = new DataTransfer();
                    const currentFiles = Array.from(reviewImageInput.files);
                    
                    currentFiles.forEach((file, idx) => {
                        if (idx !== containerIndex) {
                            dt.items.add(file);
                        }
                    });
                    
                    reviewImageInput.files = dt.files;
                    
                    // Update data-index attributes for remaining images
                    remainingImages.forEach((container, newIdx) => {
                        const removeBtn = container.querySelector('.btn-remove-review-image');
                        if (removeBtn) {
                            // Find the original index of this file
                            let originalIdx = 0;
                            let skipped = 0;
                            for (let i = 0; i < currentFiles.length; i++) {
                                if (i === containerIndex) {
                                    skipped++;
                                    continue;
                                }
                                if (newIdx === originalIdx) {
                                    removeBtn.setAttribute('data-index', i.toString());
                                    break;
                                }
                                originalIdx++;
                            }
                        }
                    });
                }
            }, 300);
        }
    });
});

// Drag and drop functions for review images
window.handleReviewImageDragOver = function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.style.borderColor = 'var(--blue-primary, #2766ec)';
    e.currentTarget.style.backgroundColor = 'rgba(39, 102, 236, 0.1)';
};

window.handleReviewImageDragLeave = function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.style.borderColor = 'var(--blue-border, #D6E4FF)';
    e.currentTarget.style.backgroundColor = 'var(--blue-bg-light, #F0F4FF)';
};

window.handleReviewImageDrop = function(e, inputId) {
    e.preventDefault();
    e.stopPropagation();
    e.currentTarget.style.borderColor = 'var(--blue-border, #D6E4FF)';
    e.currentTarget.style.backgroundColor = 'var(--blue-bg-light, #F0F4FF)';
    
    const files = e.dataTransfer.files;
    const input = document.getElementById(inputId);
    
    if (files.length > 0 && input) {
        // Check max 5 images
        if (files.length > 5) {
            alert('Bạn chỉ có thể tải lên tối đa 5 ảnh. Vui lòng chọn lại.');
            return;
        }
        
        // Create a new FileList-like object
        const dt = new DataTransfer();
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                // Check file size (2MB limit)
                if (file.size > 2 * 1024 * 1024) {
                    alert(`File "${file.name}" quá lớn (>2MB). Vui lòng chọn file nhỏ hơn.`);
                    return;
                }
                dt.items.add(file);
            } else {
                alert(`File "${file.name}" không phải là hình ảnh. Vui lòng chọn file hình ảnh.`);
            }
        });
        
        if (dt.items.length > 0) {
            input.files = dt.files;
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            input.dispatchEvent(event);
        }
    }
};
</script>
@endpush

@section('content')
<div class="page-container-blue">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header-blue">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3" style="position: relative; z-index: 1;">
                <ol class="breadcrumb mb-0" style="background: rgba(255, 255, 255, 0.2); padding: 0.75rem 1rem; border-radius: 10px; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);">
                    <li class="breadcrumb-item">
                        <a href="{{ route('tenant.dashboard') }}" style="color: rgba(255, 255, 255, 0.9); text-decoration: none;">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('tenant.reviews.index') }}" style="color: rgba(255, 255, 255, 0.9); text-decoration: none;">
                            <i class="fas fa-star me-1"></i>Đánh giá
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page" style="color: rgba(255, 255, 255, 1);">
                        <i class="fas fa-edit me-1"></i>Viết đánh giá
                    </li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <h1 class="page-title">Viết đánh giá</h1>
                        <p class="page-subtitle">Chia sẻ trải nghiệm của bạn về phòng trọ</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="{{ route('tenant.reviews.index') }}" class="btn btn-outline-secondary" style="background: rgba(255, 255, 255, 0.25); color: white; border: 1px solid rgba(255, 255, 255, 0.3); font-weight: 600; padding: 0.75rem 1.5rem; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); text-decoration: none;">
                        <i class="fas fa-arrow-left me-1"></i>Quay lại
                    </a>
                </div>
            </div>
        </div>

        <!-- Enhanced Alert Messages with Notification Integration -->
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show notification-alert" role="alert">
                <div class="alert-content">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-text">
                        <strong>Lỗi:</strong> {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show notification-alert" role="alert">
                <div class="alert-content">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-text">
                        <strong>Thành công:</strong> {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        <!-- Review Form -->
        <div class="review-form-container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="review-form-card">
                        <form id="writeReviewForm" method="POST" action="{{ route('tenant.reviews.store') }}" enctype="multipart/form-data">
                            @csrf
                            
                            <div class="mb-4">
                                <label for="reviewProperty" class="form-label">Chọn phòng để đánh giá <span class="required">*</span></label>
                                <select class="form-select" id="reviewProperty" name="lease_id" required>
                                    <option value="">Chọn phòng bạn đã/đang thuê</option>
                                    @foreach($leases as $lease)
                                        @if($lease->unit && $lease->unit->property)
                                            <option value="{{ $lease->id }}">
                                                {{ $lease->unit->property->name ?? 'N/A' }} - {{ $lease->unit->name ?? 'N/A' }} 
                                                ({{ number_format($lease->rent_amount) }} VNĐ/tháng)
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('lease_id')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
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
                                    <input type="hidden" name="overall_rating" id="overallRatingInput" value="">
                                    @error('overall_rating')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
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
                                            <input type="hidden" name="location_rating" id="locationRatingInput" value="">
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
                                            <input type="hidden" name="quality_rating" id="qualityRatingInput" value="">
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
                                            <input type="hidden" name="service_rating" id="serviceRatingInput" value="">
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
                                            <input type="hidden" name="price_rating" id="priceRatingInput" value="">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="reviewTitle" class="form-label">Tiêu đề đánh giá <span class="required">*</span></label>
                                <input type="text" class="form-control" id="reviewTitle" name="title" 
                                       placeholder="Ví dụ: Phòng tuyệt vời, chủ nhà thân thiện" 
                                       value="{{ old('title') }}" required>
                                @error('title')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="reviewContent" class="form-label">Nội dung đánh giá <span class="required">*</span></label>
                                <textarea class="form-control" id="reviewContent" name="content" rows="6" 
                                          placeholder="Chia sẻ chi tiết trải nghiệm của bạn về phòng trọ này..." required>{{ old('content') }}</textarea>
                                <div class="form-text">Tối thiểu 50 ký tự</div>
                                @error('content')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Điểm nổi bật</label>
                                <div class="highlight-options">
                                    <label class="highlight-option">
                                        <input type="checkbox" name="highlights[]" value="clean" {{ in_array('clean', old('highlights', [])) ? 'checked' : '' }}>
                                        <span class="option-text">
                                            <i class="fas fa-sparkles"></i>
                                            Sạch sẽ
                                        </span>
                                    </label>
                                    <label class="highlight-option">
                                        <input type="checkbox" name="highlights[]" value="location" {{ in_array('location', old('highlights', [])) ? 'checked' : '' }}>
                                        <span class="option-text">
                                            <i class="fas fa-map-marker-alt"></i>
                                            Vị trí tốt
                                        </span>
                                    </label>
                                    <label class="highlight-option">
                                        <input type="checkbox" name="highlights[]" value="price" {{ in_array('price', old('highlights', [])) ? 'checked' : '' }}>
                                        <span class="option-text">
                                            <i class="fas fa-dollar-sign"></i>
                                            Giá hợp lý
                                        </span>
                                    </label>
                                    <label class="highlight-option">
                                        <input type="checkbox" name="highlights[]" value="friendly" {{ in_array('friendly', old('highlights', [])) ? 'checked' : '' }}>
                                        <span class="option-text">
                                            <i class="fas fa-smile"></i>
                                            Chủ nhà thân thiện
                                        </span>
                                    </label>
                                    <label class="highlight-option">
                                        <input type="checkbox" name="highlights[]" value="quiet" {{ in_array('quiet', old('highlights', [])) ? 'checked' : '' }}>
                                        <span class="option-text">
                                            <i class="fas fa-volume-mute"></i>
                                            Yên tĩnh
                                        </span>
                                    </label>
                                    <label class="highlight-option">
                                        <input type="checkbox" name="highlights[]" value="convenient" {{ in_array('convenient', old('highlights', [])) ? 'checked' : '' }}>
                                        <span class="option-text">
                                            <i class="fas fa-shopping-cart"></i>
                                            Tiện ích
                                        </span>
                                    </label>
                                </div>
                                @error('highlights')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="reviewImages" class="form-label">Hình ảnh (tùy chọn)</label>
                                <div class="image-upload-area" id="reviewImageUploadArea" style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s ease;" ondrop="handleReviewImageDrop(event, 'reviewImages')" ondragover="handleReviewImageDragOver(event)" ondragleave="handleReviewImageDragLeave(event)">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <p class="mb-2">Kéo thả ảnh vào đây hoặc click để chọn</p>
                                    <input type="file" 
                                           class="form-control" 
                                           id="reviewImages" 
                                           name="images[]" 
                                           multiple 
                                           accept="image/*"
                                           style="display: none;">
                                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('reviewImages').click()" style="background: white; color: var(--blue-primary); border: 2px solid var(--blue-primary); font-weight: 600;">
                                        <i class="fas fa-folder-open me-2"></i>Chọn ảnh
                                    </button>
                                </div>
                                <div class="form-text">Tải lên hình ảnh thực tế của phòng (tối đa 5 ảnh, mỗi ảnh tối đa 2MB)</div>
                                <div id="reviewImagePreview" class="row g-2 mt-3"></div>
                                @error('images')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Bạn có giới thiệu phòng này không?</label>
                                <div class="recommend-options">
                                    <label class="recommend-option">
                                        <input type="radio" name="recommend" value="yes" {{ old('recommend') == 'yes' ? 'checked' : '' }}>
                                        <span class="option-text">
                                            <i class="fas fa-thumbs-up text-success"></i>
                                            Có, tôi sẽ giới thiệu
                                        </span>
                                    </label>
                                    <label class="recommend-option">
                                        <input type="radio" name="recommend" value="maybe" {{ old('recommend') == 'maybe' ? 'checked' : '' }}>
                                        <span class="option-text">
                                            <i class="fas fa-meh text-warning"></i>
                                            Có thể
                                        </span>
                                    </label>
                                    <label class="recommend-option">
                                        <input type="radio" name="recommend" value="no" {{ old('recommend') == 'no' ? 'checked' : '' }}>
                                        <span class="option-text">
                                            <i class="fas fa-thumbs-down text-danger"></i>
                                            Không
                                        </span>
                                    </label>
                                </div>
                                @error('recommend')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary btn-lg" onclick="window.history.back()">
                                    <i class="fas fa-times me-2"></i>Hủy
                                </button>
                                <button type="submit" class="btn btn-primary btn-lg" id="submitReviewBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Đăng đánh giá
                                    <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
