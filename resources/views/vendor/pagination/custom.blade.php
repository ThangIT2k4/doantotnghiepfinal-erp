{{--
    Custom Pagination Component
    
    This component automatically detects the content type based on the model class.
    You can also manually override the content type and icon by passing data to the view:
    
    Usage examples:
    1. Automatic detection (default):
       {{ $items->links('vendor.pagination.custom') }}
    
    2. Manual override:
       {{ $items->appends(request()->query())->links('vendor.pagination.custom', [
           'contentTypeOverride' => 'sản phẩm',
           'contentIconOverride' => 'fas fa-box'
       ]) }}
    
    Supported models (auto-detected):
    - Lease: 'hợp đồng' with 'fas fa-file-contract'
    - Invoice: 'hóa đơn' with 'fas fa-file-invoice'  
    - Payment: 'thanh toán' with 'fas fa-credit-card'
    - Ticket: 'phiếu hỗ trợ' with 'fas fa-ticket-alt'
    - Viewing: 'lịch xem' with 'fas fa-calendar-check'
    - User: 'người dùng' with 'fas fa-users'
    - Property: 'bất động sản' with 'fas fa-building'
    - Unit: 'phòng' with 'fas fa-door-open'
--}}

@if ($paginator->hasPages())
    @php
        // Get table container ID from view data or request
        // Default to 'notifications-list-container' for notifications, otherwise use 'index-table-container'
        $tableContainerId = $tableContainerId ?? request('tableContainerId', 'notifications-list-container');
        
        // Get HTMX indicator ID from view data or request
        // Default to '#htmx-loading-index-filters-form' for backward compatibility, or '#htmx-loading' for notifications
        $htmxIndicator = $htmxIndicator ?? request('htmxIndicator', '#htmx-loading-index-filters-form');
        
        // Determine content type based on route or data
        $contentType = 'mục';
        $contentIcon = 'fas fa-list';
        
        // Check if we can determine content type from the data
        $firstItem = $paginator->firstItem();
        if ($firstItem !== null) {
                // Try to get the first item to determine type
                $firstModel = $paginator->items()[0] ?? null;
                if ($firstModel) {
                    $modelClass = get_class($firstModel);
                    switch ($modelClass) {
                        case 'App\Models\Lease':
                            $contentType = 'hợp đồng';
                            $contentIcon = 'fas fa-file-contract';
                            break;
                        case 'App\Models\Invoice':
                            $contentType = 'hóa đơn';
                            $contentIcon = 'fas fa-file-invoice';
                            break;
                        case 'App\Models\Payment':
                            $contentType = 'thanh toán';
                            $contentIcon = 'fas fa-credit-card';
                            break;
                        case 'App\Models\Ticket':
                            $contentType = 'phiếu hỗ trợ';
                            $contentIcon = 'fas fa-ticket-alt';
                            break;
                        case 'App\Models\Viewing':
                            $contentType = 'lịch xem';
                            $contentIcon = 'fas fa-calendar-check';
                            break;
                        case 'App\Models\User':
                            $contentType = 'người dùng';
                            $contentIcon = 'fas fa-users';
                            break;
                        case 'App\Models\Property':
                            $contentType = 'bất động sản';
                            $contentIcon = 'fas fa-building';
                            break;
                        case 'App\Models\Organization':
                            $contentType = 'tổ chức';
                            $contentIcon = 'fas fa-building';
                            break;
                        case 'App\Models\Unit':
                            $contentType = 'phòng';
                            $contentIcon = 'fas fa-door-open';
                            break;
                        default:
                            $contentType = 'mục';
                            $contentIcon = 'fas fa-list';
                    }
                }
            }
        
        // Allow manual override via view data
        if (isset($contentTypeOverride)) {
            $contentType = $contentTypeOverride;
        }
        if (isset($contentIconOverride)) {
            $contentIcon = $contentIconOverride;
        }
    @endphp
    
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="pagination-glass">
        <p class="pagination-glass__summary">
            <i class="{{ $contentIcon }}" aria-hidden="true"></i>
            <span>
                Hiển thị <strong>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</strong>
                trong tổng số <strong>{{ $paginator->total() }}</strong> {{ $contentType }}
            </span>
        </p>

        <ul class="pagination pagination-glass__list pagination-sm" role="menubar" aria-label="Pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link" aria-hidden="true">
                        <i class="fas fa-angle-left"></i>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" 
                       href="{{ $paginator->previousPageUrl() }}" 
                       rel="prev" 
                       aria-label="@lang('pagination.previous')"
                       hx-get="{{ $paginator->previousPageUrl() }}"
                       hx-target="#{{ $tableContainerId }}"
                       hx-swap="innerHTML"
                       hx-push-url="true"
                       hx-indicator="{{ $htmxIndicator }}">
                        <i class="fas fa-angle-left"></i>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" 
                                   href="{{ $url }}" 
                                   aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                   hx-get="{{ $url }}"
                                   hx-target="#{{ $tableContainerId }}"
                                   hx-swap="innerHTML"
                                   hx-push-url="true"
                                   hx-indicator="{{ $htmxIndicator }}">
                                    {{ $page }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" 
                       href="{{ $paginator->nextPageUrl() }}" 
                       rel="next" 
                       aria-label="@lang('pagination.next')"
                       hx-get="{{ $paginator->nextPageUrl() }}"
                       hx-target="#{{ $tableContainerId }}"
                       hx-swap="innerHTML"
                       hx-push-url="true"
                       hx-indicator="{{ $htmxIndicator }}">
                        <i class="fas fa-angle-right"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link" aria-hidden="true">
                        <i class="fas fa-angle-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
