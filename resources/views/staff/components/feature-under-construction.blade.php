@props([
    'title' => 'Chức năng đang triển khai',
    'message' => 'Tính năng này đang được phát triển. Vui lòng quay lại sau.',
    'redirectSeconds' => 4,
    'fallbackRoute' => 'staff.dashboard',
])

@php
    $previousUrl = url()->previous();
    $currentUrl = url()->current();
    $hasValidPrevious = !empty($previousUrl) && $previousUrl !== $currentUrl;
    $redirectUrl = $hasValidPrevious ? $previousUrl : route($fallbackRoute);
@endphp

<main class="main-content">
    <div class="container-fluid">
        <div class="card shadow-sm border-warning">
            <div class="card-body text-center py-5">
                <i class="fas fa-tools fa-3x text-warning mb-3"></i>
                <h4 class="mb-2">{{ $title }}</h4>
                <p class="text-muted mb-3">{{ $message }}</p>
                <p class="text-muted mb-4">
                    Hệ thống sẽ tự động chuyển trang sau {{ $redirectSeconds }} giây.
                </p>

                <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại trang trước
                </button>
            </div>
        </div>
    </div>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        if (window.history.length > 1) {
            window.history.back();
            return;
        }
        window.location.href = @json($redirectUrl);
    }, {{ (int) $redirectSeconds * 1000 }});
});
</script>
@endpush
