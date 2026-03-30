@extends('layouts.staff_dashboard')

@section('title', 'Chỉnh sửa Đánh giá')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Chỉnh sửa Đánh giá</h1>
            <p class="text-muted">Cập nhật thông tin đánh giá #{{ $review->id }}</p>
        </div>
        <div>
            <a href="{{ route('staff.reviews.show', $review->id) }}" class="btn btn-outline-info">
                <i class="fas fa-eye"></i> Xem chi tiết
            </a>
            <a href="{{ route('staff.reviews.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <form action="{{ route('staff.reviews.update', $review->id) }}" method="POST" id="editReviewForm">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-star"></i> Thông tin cơ bản</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Khách thuê</label>
                                <input type="text" class="form-control" value="{{ $review->tenant ? $review->tenant->full_name : 'N/A' }}" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phòng</label>
                                <input type="text" class="form-control" 
                                       value="@if($review->unit){{ $review->unit->property ? $review->unit->property->name : 'N/A' }} - {{ $review->unit->code }}@else N/A @endif" 
                                       readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="published" {{ old('status', $review->status) == 'published' ? 'selected' : '' }}>Đã đăng</option>
                                    <option value="hidden" {{ old('status', $review->status) == 'hidden' ? 'selected' : '' }}>Đã ẩn</option>
                                </select>
                                @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Giới thiệu</label>
                                <select name="recommend" class="form-select @error('recommend') is-invalid @enderror">
                                    <option value="">-- Chọn --</option>
                                    <option value="yes" {{ old('recommend', $review->recommend) == 'yes' ? 'selected' : '' }}>Có, tôi sẽ giới thiệu</option>
                                    <option value="maybe" {{ old('recommend', $review->recommend) == 'maybe' ? 'selected' : '' }}>Có thể</option>
                                    <option value="no" {{ old('recommend', $review->recommend) == 'no' ? 'selected' : '' }}>Không</option>
                                </select>
                                @error('recommend')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ratings -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Điểm Đánh giá</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tổng điểm</label>
                                <select name="overall_rating" class="form-select @error('overall_rating') is-invalid @enderror">
                                    <option value="">-- Chọn điểm --</option>
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ old('overall_rating', $review->overall_rating) == $i ? 'selected' : '' }}>
                                            {{ $i }} sao
                                        </option>
                                    @endfor
                                </select>
                                @error('overall_rating')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vị trí</label>
                                <select name="location_rating" class="form-select @error('location_rating') is-invalid @enderror">
                                    <option value="">-- Chọn điểm --</option>
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ old('location_rating', $review->location_rating) == $i ? 'selected' : '' }}>
                                            {{ $i }} sao
                                        </option>
                                    @endfor
                                </select>
                                @error('location_rating')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Chất lượng</label>
                                <select name="quality_rating" class="form-select @error('quality_rating') is-invalid @enderror">
                                    <option value="">-- Chọn điểm --</option>
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ old('quality_rating', $review->quality_rating) == $i ? 'selected' : '' }}>
                                            {{ $i }} sao
                                        </option>
                                    @endfor
                                </select>
                                @error('quality_rating')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dịch vụ</label>
                                <select name="service_rating" class="form-select @error('service_rating') is-invalid @enderror">
                                    <option value="">-- Chọn điểm --</option>
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ old('service_rating', $review->service_rating) == $i ? 'selected' : '' }}>
                                            {{ $i }} sao
                                        </option>
                                    @endfor
                                </select>
                                @error('service_rating')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Giá cả</label>
                                <select name="price_rating" class="form-select @error('price_rating') is-invalid @enderror">
                                    <option value="">-- Chọn điểm --</option>
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ old('price_rating', $review->price_rating) == $i ? 'selected' : '' }}>
                                            {{ $i }} sao
                                        </option>
                                    @endfor
                                </select>
                                @error('price_rating')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Review Content -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-comment"></i> Nội dung Đánh giá</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề</label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                                   value="{{ old('title', $review->title) }}" maxlength="255">
                            @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nội dung</label>
                            <textarea name="content" class="form-control @error('content') is-invalid @enderror" 
                                      rows="6" placeholder="Nhập nội dung đánh giá...">{{ old('content', $review->content) }}</textarea>
                            @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs"></i> Thao tác</h5>
                    </div>
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-save"></i> Cập nhật đánh giá
                        </button>
                        <a href="{{ route('staff.reviews.show', $review->id) }}" class="btn btn-outline-info w-100 mb-2">
                            <i class="fas fa-eye"></i> Xem chi tiết
                        </a>
                        <a href="{{ route('staff.reviews.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times"></i> Hủy bỏ
                        </a>
                    </div>
                </div>

                <!-- Current Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Thông tin hiện tại</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Ngày tạo:</small>
                            <div>{{ $review->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Cập nhật lần cuối:</small>
                            <div>{{ $review->updated_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Lượt xem:</small>
                            <div>{{ $review->view_count ?? 0 }}</div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Lượt hữu ích:</small>
                            <div>{{ $review->helpful_count ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card shadow-sm border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Lưu ý</h6>
                    </div>
                    <div class="card-body">
                        <ul class="small mb-0">
                            <li>Thay đổi trạng thái sẽ ảnh hưởng đến hiển thị công khai</li>
                            <li>Chỉnh sửa điểm đánh giá cần cẩn thận</li>
                            <li>Nội dung đánh giá sẽ được cập nhật ngay lập tức</li>
                            <li>Không thể thay đổi thông tin khách thuê và phòng</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Session Messages -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission
    document.getElementById('editReviewForm').addEventListener('submit', function(e) {
        e.preventDefault();

        if (window.Preloader) {
            window.Preloader.show();
        }

        const formData = new FormData(this);

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
            
            // Check if response is successful
            if (response.ok) {
                // Try to parse as JSON, fallback to redirect if not JSON
                return response.json().catch(() => {
                    // If not JSON, assume it's a redirect response
                    return { success: true, redirect: '{{ route("staff.reviews.show", $review->id) }}' };
                });
            } else {
                // Handle error responses
                return response.json().catch(() => {
                    return { success: false, message: 'Lỗi phản hồi từ server' };
                });
            }
        })
        .then(data => {
            if (data.success || data.redirect) {
                Notify.success('Cập nhật đánh giá thành công!');
                setTimeout(() => {
                    window.location.href = data.redirect || '{{ route("staff.reviews.show", $review->id) }}';
                }, 1500);
            } else {
                Notify.error(data.message || 'Không thể cập nhật đánh giá. Vui lòng kiểm tra lại thông tin.');
            }
        })
        .catch(error => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
            console.error('Error:', error);
            Notify.error('Đã xảy ra lỗi khi cập nhật đánh giá. Vui lòng thử lại.');
        });
    });
});
</script>
@endpush
