@forelse($reviews as $review)
    @include('tenant.reviews.partials.review-card', ['review' => $review])
@empty
    @include('tenant.reviews.partials.empty-state')
@endforelse

@if($reviews->hasPages())
    <div class="pagination-section" style="margin-top: 2rem;">
        <nav aria-label="Reviews pagination">
            {{ $reviews->appends(request()->query())->links('vendor.pagination.custom', [
                'tableContainerId' => 'reviews-list-container',
                'htmxIndicator' => '#htmx-loading'
            ]) }}
        </nav>
    </div>
@endif

