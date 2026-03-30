@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Đánh giá')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Chi tiết Đánh giá</h1>
            <p class="mb-0">Đánh giá #{{ $review->id }} từ {{ $review->tenant ? $review->tenant->full_name : 'N/A' }}</p>
        </div>
        <div>
            <a href="{{ route('staff.reviews.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Review Details -->
        <div class="col-md-8">
            <!-- Review Information -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-star"></i> Thông tin Đánh giá</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Khách thuê:</label>
                            <div>{{ $review->tenant ? $review->tenant->full_name : 'N/A' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Phòng:</label>
                            <div>
                                @if($review->unit)
                                    {{ $review->unit->property ? $review->unit->property->name : 'N/A' }} - {{ $review->unit->code }}
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Hợp đồng:</label>
                            <div>
                                @if($review->lease)
                                    {{ $review->lease->contract_no ?: 'HD#' . $review->lease->id }}
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Trạng thái:</label>
                            <div>
                                @php
                                    $statusColors = [
                                        'published' => 'success',
                                        'hidden' => 'warning'
                                    ];
                                    $statusLabels = [
                                        'published' => 'Đã đăng',
                                        'hidden' => 'Đã ẩn'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$review->status] ?? 'secondary' }}">
                                    {{ $statusLabels[$review->status] ?? ucfirst($review->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ratings -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Điểm Đánh giá</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tổng điểm:</label>
                            <div class="d-flex align-items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $review->overall_rating)
                                        <i class="fas fa-star text-warning fa-lg"></i>
                                    @else
                                        <i class="far fa-star text-muted fa-lg"></i>
                                    @endif
                                @endfor
                                <span class="ms-2 h5 mb-0">{{ $review->overall_rating }}/5</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Giới thiệu:</label>
                            <div>
                                @php
                                    $recommendColors = [
                                        'yes' => 'success',
                                        'maybe' => 'warning',
                                        'no' => 'danger'
                                    ];
                                    $recommendLabels = [
                                        'yes' => 'Có, tôi sẽ giới thiệu',
                                        'maybe' => 'Có thể',
                                        'no' => 'Không'
                                    ];
                                @endphp
                                @if($review->recommend)
                                    <span class="badge bg-{{ $recommendColors[$review->recommend] ?? 'secondary' }}">
                                        {{ $recommendLabels[$review->recommend] ?? ucfirst($review->recommend) }}
                                    </span>
                                @else
                                    <span class="text-muted">Chưa chọn</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Vị trí:</label>
                            <div class="d-flex align-items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $review->location_rating)
                                        <i class="fas fa-star text-warning"></i>
                                    @else
                                        <i class="far fa-star text-muted"></i>
                                    @endif
                                @endfor
                                <span class="ms-2">{{ $review->location_rating }}/5</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Chất lượng:</label>
                            <div class="d-flex align-items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $review->quality_rating)
                                        <i class="fas fa-star text-warning"></i>
                                    @else
                                        <i class="far fa-star text-muted"></i>
                                    @endif
                                @endfor
                                <span class="ms-2">{{ $review->quality_rating }}/5</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Dịch vụ:</label>
                            <div class="d-flex align-items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $review->service_rating)
                                        <i class="fas fa-star text-warning"></i>
                                    @else
                                        <i class="far fa-star text-muted"></i>
                                    @endif
                                @endfor
                                <span class="ms-2">{{ $review->service_rating }}/5</span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Giá cả:</label>
                            <div class="d-flex align-items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $review->price_rating)
                                        <i class="fas fa-star text-warning"></i>
                                    @else
                                        <i class="far fa-star text-muted"></i>
                                    @endif
                                @endfor
                                <span class="ms-2">{{ $review->price_rating }}/5</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review Content -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-comment"></i> Nội dung Đánh giá</h5>
                </div>
                <div class="card-body">
                    @if($review->title)
                        <h6 class="fw-bold mb-3">{{ $review->title }}</h6>
                    @endif
                    
                    @if($review->content)
                        <div class="mb-3">
                            <p class="mb-0">{{ $review->content }}</p>
                        </div>
                    @endif
                    
                    @if($review->highlights && is_array($review->highlights) && count($review->highlights) > 0)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Điểm nổi bật:</label>
                            <ul class="mb-0">
                                @foreach($review->highlights as $highlight)
                                    <li>{{ $highlight }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    @if($review->images && is_array($review->images) && count($review->images) > 0)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Hình ảnh:</label>
                            <div class="row">
                                @foreach($review->images as $image)
                                    @php
                                        // Kiểm tra nếu $image đã là URL đầy đủ
                                        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
                                            $imageUrl = $image;
                                        } else {
                                            // Loại bỏ 'storage/' ở đầu nếu có
                                            $imagePath = ltrim($image, '/');
                                            if (str_starts_with($imagePath, 'storage/')) {
                                                $imagePath = substr($imagePath, 8); // Bỏ 'storage/' (8 ký tự)
                                            }
                                            $imageUrl = asset('storage/' . $imagePath);
                                        }
                                    @endphp
                                    <div class="col-md-3 mb-2">
                                        <img src="{{ $imageUrl }}" alt="Review image" class="img-thumbnail" style="width: 100%; height: 150px; object-fit: cover;">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Replies -->
            <div class="card shadow mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-reply"></i> Phản hồi ({{ $review->replies->count() }})</h5>
                </div>
                <div class="card-body">
                    @if($review->replies->count() > 0)
                        @foreach($review->replies as $reply)
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>{{ $reply->user ? $reply->user->full_name : 'N/A' }}</strong>
                                        <small class="text-muted ms-2">{{ $reply->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <p class="mb-0">{{ $reply->content }}</p>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">Chưa có phản hồi nào.</p>
                    @endif
                    
                    <!-- Add Reply Form -->
                    <form id="replyForm" class="mt-3">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Thêm phản hồi:</label>
                            <textarea name="content" class="form-control" rows="3" placeholder="Nhập phản hồi của bạn..." required></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            @include('staff.components.button', [
                                'type' => 'submit',
                                'variant' => 'primary',
                                'size' => 'md',
                                'label' => 'Gửi phản hồi',
                                'icon' => 'fas fa-paper-plane',
                                'iconPosition' => 'left'
                            ])
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column - Statistics & Info -->
        <div class="col-md-4">
            <!-- Review Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Thống kê</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Lượt xem:</small>
                        <div class="fw-bold">{{ $review->view_count ?? 0 }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Lượt hữu ích:</small>
                        <div class="fw-bold">{{ $review->helpful_count ?? 0 }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Số phản hồi:</small>
                        <div class="fw-bold">{{ $review->replies->count() }}</div>
                    </div>
                </div>
            </div>

            <!-- Review Info -->
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Thông tin</h6>
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
                    @if($review->deleted_at)
                        <div class="mb-2">
                            <small class="text-muted">Ngày xóa:</small>
                            <div>{{ $review->deleted_at->format('d/m/Y H:i') }}</div>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
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
    // Handle reply form submission
    document.getElementById('replyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('{{ route("staff.reviews.reply", $review->id) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Phản hồi thành công!');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                Notify.error(data.message || 'Không thể gửi phản hồi');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Đã xảy ra lỗi khi gửi phản hồi. Vui lòng thử lại.');
        });
    });
});

</script>
@endpush
