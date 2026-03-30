@props([
    'stats' => [],
    'currentFilter' => null, // Current active filter value (e.g., status)
    'filterKey' => 'status', // Query parameter key for filtering
    'onFilterClick' => 'filterByStatus', // JavaScript function name for filter click or 'htmx-filter' for HTMX
    'onClearClick' => 'clearAllFilters', // JavaScript function name for clear all or 'htmx-clear' for HTMX
    'tableContainerId' => 'index-table-container', // Table container ID for HTMX
    'action' => null, // Action URL for HTMX (defaults to current URL)
    'columns' => 6, // Number of columns (2, 3, 4, 6, 12)
])

@php
    // Calculate column class
    $colClass = match($columns) {
        2 => 'col-md-6',
        3 => 'col-md-4',
        4 => 'col-md-3',
        6 => 'col-md-2',
        12 => 'col-md-1',
        default => 'col-md-2'
    };
    
    // Default stats structure
    $defaultStats = [
        'total' => ['value' => 0, 'label' => 'Tổng cộng', 'icon' => 'fa-list', 'color' => 'primary', 'filter' => ''],
    ];
    
    // Merge with provided stats
    $mergedStats = [];
    foreach ($stats as $key => $stat) {
        if (is_array($stat)) {
            $mergedStats[$key] = array_merge([
                'value' => 0,
                'label' => ucfirst($key),
                'icon' => 'fa-circle',
                'color' => 'secondary',
                'filter' => $key,
            ], $stat);
        } else {
            // Simple format: 'key' => value
            $mergedStats[$key] = [
                'value' => $stat,
                'label' => ucfirst($key),
                'icon' => 'fa-circle',
                'color' => 'secondary',
                'filter' => $key,
            ];
        }
    }
    
    // Ensure total is first
    if (isset($mergedStats['total'])) {
        $total = $mergedStats['total'];
        unset($mergedStats['total']);
        $mergedStats = ['total' => $total] + $mergedStats;
    }
@endphp

<!-- Statistics Cards -->
<div class="row mb-4 statistics-cards-container">
    @foreach($mergedStats as $key => $stat)
        @php
            $value = $stat['value'] ?? 0;
            $label = $stat['label'] ?? ucfirst($key);
            $icon = $stat['icon'] ?? 'fa-circle';
            $color = $stat['color'] ?? 'secondary';
            $filter = $stat['filter'] ?? $key;
            $format = $stat['format'] ?? 'number';
            $isActive = ($currentFilter === $filter || ($currentFilter === null && $filter === ''));
            $borderClass = $isActive ? "border-{$color}" : '';
            
            // Check if using HTMX
            $useHtmx = ($onFilterClick === 'htmx-filter' || $onClearClick === 'htmx-clear');
            
            // Build URL for HTMX
            if ($useHtmx) {
                $actionUrl = $action ?? request()->url();
                $queryParams = request()->query();
                
                // Allow each stat to have its own filterKey, fallback to global filterKey
                $statFilterKey = $stat['filterKey'] ?? $filterKey;
                
                if ($filter === '') {
                    // Clear filter - remove the filter key and also clear other filter keys
                    unset($queryParams[$statFilterKey]);
                    unset($queryParams[$filterKey]); // Also clear the default filter key
                    // Clear has_lease if it exists (for tenants page)
                    unset($queryParams['has_lease']);
                    // Clear due_for_invoicing if it exists (for leases page)
                    unset($queryParams['due_for_invoicing']);
                    // Clear paid_without_lease if it exists (for booking deposits page)
                    unset($queryParams['paid_without_lease']);
                } else {
                    // Clear other filter keys first (both default and has_lease)
                    unset($queryParams[$filterKey]);
                    unset($queryParams['has_lease']); // Clear has_lease when clicking other stats
                    // Clear due_for_invoicing when clicking other stats (for leases page)
                    unset($queryParams['due_for_invoicing']);
                    // Clear paid_without_lease when clicking other stats (for booking deposits page)
                    unset($queryParams['paid_without_lease']);
                    // Set filter with the stat's specific filterKey
                    // Special case: if filterKey is 'due_for_invoicing' or 'paid_without_lease', set value to '1'
                    if (($statFilterKey === 'due_for_invoicing' && $filter === 'due_for_invoicing') ||
                        ($statFilterKey === 'paid_without_lease' && $filter === 'paid_without_lease')) {
                        $queryParams[$statFilterKey] = '1';
                    } else {
                        $queryParams[$statFilterKey] = $filter;
                    }
                }
                // Reset to page 1
                unset($queryParams['page']);
                
                // Build URL - if no params, just return base URL
                if (empty($queryParams)) {
                    $htmxUrl = $actionUrl;
                } else {
                    $htmxUrl = $actionUrl . '?' . http_build_query($queryParams);
                }
            }
            
            // Format value based on format type
            // If format is 'text', use value as-is (already formatted)
            if ($format === 'text') {
                $formattedValue = $value;
            } elseif ($format === 'currency') {
                // Ensure value is numeric before formatting
                $numericValue = is_numeric($value) ? (float)$value : 0;
                $formattedValue = number_format($numericValue, 0, ',', '.') . 'đ';
            } else {
                // Ensure value is numeric before formatting
                $numericValue = is_numeric($value) ? (float)$value : 0;
                $formattedValue = number_format($numericValue);
            }
        @endphp
        <div class="{{ $colClass }}">
            <div class="card stat-card {{ $borderClass }}" 
                 @if($useHtmx)
                 hx-get="{{ $htmxUrl }}"
                 hx-target="#{{ $tableContainerId }}"
                 hx-swap="innerHTML"
                 hx-push-url="true"
                 hx-indicator="#htmx-loading-index-filters-form"
                 @else
                 data-filter-value="{{ $filter }}"
                 data-filter-key="{{ $filterKey }}"
                 data-on-click="{{ ($filter === '') ? $onClearClick : "{$onFilterClick}('{$filter}')" }}"
                 @endif
                 style="cursor: pointer; transition: all 0.3s;">
                <div class="card-body text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="fas {{ $icon }} fa-2x text-{{ $color }}"></i>
                    </div>
                    <h3 class="mb-0">{{ $formattedValue }}</h3>
                    <small class="text-muted">{{ $label }}</small>
                </div>
            </div>
        </div>
    @endforeach
</div>

@push('scripts')
<script>
(function() {
    'use strict';
    
    // Use event delegation for stats cards
    function initializeStatsCards() {
        const statsContainer = document.getElementById('statistics-cards-container') || 
                               document.querySelector('.statistics-cards-container');
        
        if (!statsContainer) return;
        
        // Use event delegation to handle clicks (only for non-HTMX cards)
        statsContainer.addEventListener('click', function(e) {
            const card = e.target.closest('.stat-card[data-on-click]');
            if (!card) return;
            
            // Skip if card has HTMX attributes (HTMX will handle it)
            if (card.hasAttribute('hx-get') || card.hasAttribute('hx-post')) {
                return;
            }
            
            const onClick = card.getAttribute('data-on-click');
            const filterValue = card.getAttribute('data-filter-value');
            
            if (onClick.includes('clearAllFilters')) {
                if (typeof window.clearAllFilters === 'function') {
                    window.clearAllFilters();
                }
            } else if (onClick.includes('filterBy')) {
                // Support any filterBy function (filterByStatus, filterByService, etc.)
                const functionName = onClick.split('(')[0];
                if (typeof window[functionName] === 'function') {
                    window[functionName](filterValue);
                }
            }
        });
    }
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeStatsCards);
    } else {
        initializeStatsCards();
    }
})();
</script>
@endpush

@push('styles')
@include('staff.components.index-styles')
@endpush

