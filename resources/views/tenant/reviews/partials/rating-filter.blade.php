@php
    $selectedRating = $selectedRating ?? request('rating', '');
    $currentParams = request()->query();
    unset($currentParams['rating']);
@endphp
<select class="form-select" 
        name="rating" 
        id="ratingFilter"
        hx-get="{{ route('tenant.reviews.index', $currentParams) }}"
        hx-target="#reviews-list-container"
        hx-swap="innerHTML"
        hx-push-url="true"
        hx-indicator="#htmx-loading"
        hx-trigger="change"
        hx-include="[name='rating']"
        onchange="
            const params = new URLSearchParams(window.location.search);
            if (this.value) {
                params.set('rating', this.value);
            } else {
                params.delete('rating');
            }
            params.set('status', document.getElementById('statusInput')?.value || 'all');
            if (document.getElementById('searchInput')?.value) {
                params.set('search', document.getElementById('searchInput').value);
            }
            htmx.ajax('GET', '{{ route('tenant.reviews.index') }}?' + params.toString(), {
                target: '#reviews-list-container',
                swap: 'innerHTML'
            });
        ">
    <option value="">Tất cả đánh giá</option>
    <option value="5" {{ $selectedRating == '5' ? 'selected' : '' }}>⭐⭐⭐⭐⭐ 5 sao</option>
    <option value="4" {{ $selectedRating == '4' ? 'selected' : '' }}>⭐⭐⭐⭐ 4 sao</option>
    <option value="3" {{ $selectedRating == '3' ? 'selected' : '' }}>⭐⭐⭐ 3 sao</option>
    <option value="2" {{ $selectedRating == '2' ? 'selected' : '' }}>⭐⭐ 2 sao</option>
    <option value="1" {{ $selectedRating == '1' ? 'selected' : '' }}>⭐ 1 sao</option>
</select>

