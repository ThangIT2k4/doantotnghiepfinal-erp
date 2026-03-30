@php
    $sortBy = $sortBy ?? request('sort_by', 'created_at');
    $sortOrder = $sortOrder ?? request('sort_order', 'desc');
    
    // Generate sort URL
    $generateSortUrl = function($field) use ($sortBy, $sortOrder) {
        $query = request()->query();
        unset($query['ajax']); // Remove ajax parameter for HTMX
        $query['sort_by'] = $field;
        $query['sort_order'] = ($sortBy === $field && $sortOrder === 'asc') ? 'desc' : 'asc';
        return request()->url() . '?' . http_build_query($query);
    };
    
    // Get sort icon
    $getSortIcon = function($field) use ($sortBy, $sortOrder) {
        if ($sortBy !== $field) {
            return '<i class="fas fa-sort text-muted"></i>';
        }
        return $sortOrder === 'asc' 
            ? '<i class="fas fa-sort-up text-primary"></i>' 
            : '<i class="fas fa-sort-down text-primary"></i>';
    };
@endphp

<div class="col-12" id="reviews-table-container">
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>Danh sách Đánh giá ({{ $reviews->total() }} kết quả)
        </h6>
    </div>
    <div class="card-body">
        @if($reviews->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <a href="{{ $generateSortUrl('id') }}" 
                                   hx-get="{{ $generateSortUrl('id') }}"
                                   hx-target="#reviews-table-container"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   class="text-decoration-none text-dark"
                                   style="cursor: pointer;">
                                    ID
                                    {!! $getSortIcon('id') !!}
                                </a>
                            </th>
                            <th>Khách thuê</th>
                            <th>Phòng</th>
                            <th>
                                <a href="{{ $generateSortUrl('overall_rating') }}" 
                                   hx-get="{{ $generateSortUrl('overall_rating') }}"
                                   hx-target="#reviews-table-container"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   class="text-decoration-none text-dark"
                                   style="cursor: pointer;">
                                    Điểm
                                    {!! $getSortIcon('overall_rating') !!}
                                </a>
                            </th>
                            <th>
                                <a href="{{ $generateSortUrl('title') }}" 
                                   hx-get="{{ $generateSortUrl('title') }}"
                                   hx-target="#reviews-table-container"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   class="text-decoration-none text-dark"
                                   style="cursor: pointer;">
                                    Tiêu đề
                                    {!! $getSortIcon('title') !!}
                                </a>
                            </th>
                            <th>
                                <a href="{{ $generateSortUrl('status') }}" 
                                   hx-get="{{ $generateSortUrl('status') }}"
                                   hx-target="#reviews-table-container"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   class="text-decoration-none text-dark"
                                   style="cursor: pointer;">
                                    Trạng thái
                                    {!! $getSortIcon('status') !!}
                                </a>
                            </th>
                            <th>Giới thiệu</th>
                            <th>
                                <a href="{{ $generateSortUrl('created_at') }}" 
                                   hx-get="{{ $generateSortUrl('created_at') }}"
                                   hx-target="#reviews-table-container"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   class="text-decoration-none text-dark"
                                   style="cursor: pointer;">
                                    Ngày tạo
                                    {!! $getSortIcon('created_at') !!}
                                </a>
                            </th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reviews as $review)
                        <tr>
                            <td>#{{ $review->id }}</td>
                            <td>
                                <div class="small">
                                    <strong>{{ $review->tenant ? ($review->tenant->full_name ?? $review->tenant->name ?? 'N/A') : 'N/A' }}</strong>
                                </div>
                            </td>
                            <td>
                                @if($review->unit)
                                    <div class="small">
                                        <strong>{{ $review->unit->property ? $review->unit->property->name : 'N/A' }}</strong><br>
                                        Phòng: {{ $review->unit->code }}
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $review->overall_rating)
                                            <i class="fas fa-star text-warning"></i>
                                        @else
                                            <i class="far fa-star text-muted"></i>
                                        @endif
                                    @endfor
                                    <span class="ms-2 small">{{ $review->overall_rating }}/5</span>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $review->title ?: 'Không có tiêu đề' }}</div>
                                @if($review->content)
                                    <small class="text-muted">{{ Str::limit($review->content, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'published' => 'success',
                                        'hidden' => 'warning',
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    $statusLabels = [
                                        'published' => 'Đã đăng',
                                        'hidden' => 'Đã ẩn',
                                        'pending' => 'Chờ duyệt',
                                        'approved' => 'Đã duyệt',
                                        'rejected' => 'Đã từ chối'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$review->status] ?? 'secondary' }}">
                                    {{ $statusLabels[$review->status] ?? ucfirst($review->status) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $recommendColors = [
                                        'yes' => 'success',
                                        'maybe' => 'warning',
                                        'no' => 'danger'
                                    ];
                                    $recommendLabels = [
                                        'yes' => 'Có',
                                        'maybe' => 'Có thể',
                                        'no' => 'Không'
                                    ];
                                @endphp
                                @if($review->recommend)
                                    <span class="badge bg-{{ $recommendColors[$review->recommend] ?? 'secondary' }}">
                                        {{ $recommendLabels[$review->recommend] ?? ucfirst($review->recommend) }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="small">
                                    {{ $review->created_at->format('d/m/Y H:i') }}
                                </div>
                            </td>
                            <td>
                                <div class="btn-group table-actions" role="group">
                                    {{-- Xem chi tiết - outline-primary --}}
                                    <a href="{{ route('staff.reviews.show', $review->id) }}" 
                                       class="btn btn-outline-primary btn-icon-only" 
                                       title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($reviews->hasPages())
                <div class="mt-3">
                    {{ $reviews->appends(request()->query())->links('vendor.pagination.custom', ['tableContainerId' => 'reviews-table-container']) }}
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Không có đánh giá nào</h5>
                <p class="text-muted">Chưa có đánh giá nào hoặc không tìm thấy kết quả phù hợp.</p>
            </div>
        @endif
    </div>
</div>
</div>

