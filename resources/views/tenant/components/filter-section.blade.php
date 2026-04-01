@props([
    'searchPlaceholder' => 'Tìm kiếm...',
    'searchValue' => '',
    'filters' => [], // Array of filter tabs: ['label' => '', 'value' => '', 'active' => false, 'icon' => '', 'hx-get' => '', 'hx-target' => '', 'hx-swap' => 'innerHTML', 'hx-push-url' => 'true', 'hx-indicator' => '', 'hx-trigger' => 'click']
    'formId' => 'filterForm',
    'searchInputId' => 'searchInput',
    'hxGet' => null, // HTMX GET URL for form
    'hxTarget' => null, // HTMX target container
    'hxSwap' => 'innerHTML',
    'hxPushUrl' => 'true',
    'hxIndicator' => null,
    'hxTrigger' => 'input delay:500ms from:#searchInput',
    'additionalFields' => null, // Additional form fields (e.g., month selector)
    'class' => '',
])

<div class="filter-section-blue {{ $class }}" id="filter-section-container">
    <div id="{{ $formId }}">
        
        <div class="row align-items-center">
            <div class="col-md-{{ !empty($filters) && !$additionalFields ? '6' : ($additionalFields && !empty($filters) ? '4' : '12') }}">
                <div class="search-box-blue">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="{{ $searchInputId }}"
                           name="search" 
                           value="{{ $searchValue }}"
                           placeholder="{{ $searchPlaceholder }}"
                           class="form-control"
                           @if($hxGet && $hxTarget)
                           hx-get="{{ $hxGet }}"
                           hx-target="{{ $hxTarget }}"
                           hx-swap="{{ $hxSwap }}"
                           @if($hxPushUrl)
                           hx-push-url="{{ $hxPushUrl }}"
                           @endif
                           @if($hxIndicator)
                           hx-indicator="{{ $hxIndicator }}"
                           @endif
                           @if($hxTrigger)
                           hx-trigger="{{ $hxTrigger }}"
                           @endif
                           @endif>
                </div>
            </div>
            
            @if($additionalFields)
            <div class="col-md-{{ !empty($filters) ? '4' : '6' }}">
                {!! $additionalFields !!}
            </div>
            @endif
            
            @if(!empty($filters))
            <div class="col-md-{{ $additionalFields ? '4' : '6' }}">
                <div class="filter-tabs-blue">
                    @foreach($filters as $filter)
                    <button type="button" 
                            class="filter-tab-blue {{ isset($filter['active']) && $filter['active'] ? 'active' : '' }}"
                            data-status="{{ $filter['value'] ?? '' }}"
                            @if(isset($filter['hx-get']) && $filter['hx-get'])
                            hx-get="{{ $filter['hx-get'] }}"
                            @if(isset($filter['hx-target']) && $filter['hx-target'])
                            hx-target="{{ $filter['hx-target'] }}"
                            @elseif($hxTarget)
                            hx-target="{{ $hxTarget }}"
                            @endif
                            hx-swap="{{ $filter['hx-swap'] ?? $hxSwap }}"
                            @if(isset($filter['hx-push-url']))
                            hx-push-url="{{ $filter['hx-push-url'] }}"
                            @elseif($hxPushUrl)
                            hx-push-url="{{ $hxPushUrl }}"
                            @endif
                            @if(isset($filter['hx-indicator']) && $filter['hx-indicator'])
                            hx-indicator="{{ $filter['hx-indicator'] }}"
                            @elseif($hxIndicator)
                            hx-indicator="{{ $hxIndicator }}"
                            @endif
                            hx-trigger="{{ $filter['hx-trigger'] ?? 'click' }}"
                            @elseif(isset($filter['onclick']))
                            onclick="{{ $filter['onclick'] }}"
                            @endif>
                        @if(isset($filter['icon']) && $filter['icon'])
                            <i class="{{ $filter['icon'] }} me-2"></i>
                        @endif
                        {{ $filter['label'] ?? '' }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        <input type="hidden" name="status" id="statusInput" value="{{ request('status', 'all') }}">
    </div>
</div>

@push('styles')
<style>
/* Filter Section with Blue Theme */
.filter-section-blue {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
}

.search-box-blue {
    position: relative;
    display: flex;
    align-items: center;
}

.search-box-blue i {
    position: absolute;
    left: 1rem;
    color: var(--blue-primary);
    font-size: 1.1rem;
    z-index: 1;
}

.search-box-blue input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 3rem;
    border: 2px solid var(--blue-border);
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: var(--blue-bg-light);
}

.search-box-blue input:focus {
    outline: none;
    border-color: var(--blue-primary);
    background: white;
    box-shadow: 0 0 0 0.2rem rgba(39, 102, 236, 0.25);
}

.filter-tabs-blue {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.filter-tab-blue {
    padding: 0.75rem 1.5rem;
    border: 2px solid var(--blue-border);
    background: white;
    color: var(--blue-primary);
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
}

.filter-tab-blue:hover {
    background: var(--blue-bg-light);
    border-color: var(--blue-light);
    transform: translateY(-2px);
}

/* Status-specific hover colors for filter tabs */
.filter-tab-blue[data-status="active"]:hover:not(.active) {
    background: var(--status-active-light);
    border-color: var(--status-active);
    color: var(--status-active);
}

.filter-tab-blue[data-status="expiring"]:hover:not(.active) {
    background: var(--status-expiring-light);
    border-color: var(--status-expiring);
    color: var(--status-expiring);
}

.filter-tab-blue[data-status="expired"]:hover:not(.active) {
    background: var(--status-expired-light);
    border-color: var(--status-expired);
    color: var(--status-expired);
}

.filter-tab-blue[data-status="all"]:hover:not(.active) {
    background: var(--status-all-light);
    border-color: var(--status-all);
    color: var(--status-all);
}

/* Invoice status hover colors */
.filter-tab-blue[data-status="paid"]:hover:not(.active) {
    background: var(--status-active-light);
    border-color: var(--status-active);
    color: var(--status-active);
}

.filter-tab-blue[data-status="pending"]:hover:not(.active) {
    background: var(--status-expiring-light);
    border-color: var(--status-expiring);
    color: var(--status-expiring);
}

.filter-tab-blue[data-status="overdue"]:hover:not(.active) {
    background: var(--status-expired-light);
    border-color: var(--status-expired);
    color: var(--status-expired);
}

.filter-tab-blue.active {
    background: var(--blue-gradient);
    color: white;
    border-color: var(--blue-primary);
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.3);
    font-weight: 700 !important;
}

/* Status-specific colors for filter tabs */
.filter-tab-blue[data-status="active"].active {
    background: var(--status-active-gradient);
    border-color: var(--status-active-border);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.filter-tab-blue[data-status="expiring"].active {
    background: var(--status-expiring-gradient);
    border-color: var(--status-expiring-border);
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
}

.filter-tab-blue[data-status="expired"].active {
    background: var(--status-expired-gradient);
    border-color: var(--status-expired-border);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

.filter-tab-blue[data-status="all"].active {
    background: var(--status-all-gradient);
    border-color: var(--status-all-border);
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.3);
}

/* Invoice status active colors */
.filter-tab-blue[data-status="paid"].active {
    background: var(--status-active-gradient);
    border-color: var(--status-active-border);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.filter-tab-blue[data-status="pending"].active {
    background: var(--status-expiring-gradient);
    border-color: var(--status-expiring-border);
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
}

.filter-tab-blue[data-status="overdue"].active {
    background: var(--status-expired-gradient);
    border-color: var(--status-expired-border);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}
</style>
@endpush

