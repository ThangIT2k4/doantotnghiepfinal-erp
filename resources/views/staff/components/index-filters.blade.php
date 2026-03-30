@props([
    'action' => null, // Form action URL
    'method' => 'GET',
    'fields' => [], // Array of filter fields
    'showReset' => true, // Show reset button
    'resetUrl' => null, // Reset URL (defaults to current route without params)
    'liveSearch' => true, // Enable live search (auto submit on input)
    'debounceDelay' => 500, // Debounce delay in milliseconds
    'formId' => 'index-filters-form', // Form ID
    'tableContainerId' => 'index-table-container', // Table container ID for AJAX updates
    'useAjax' => true, // Use AJAX instead of page reload
])

@php
    // Default fields structure
    // Each field should have: name, label, type (text, select, date, etc.), options (for select), placeholder, value
    $defaultFields = [];
    
    // Merge with provided fields
    $filterFields = $fields;
@endphp

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <form id="{{ $formId }}" method="{{ $method }}" action="{{ $action ?? request()->url() }}" class="row g-3 index-filters-form">
                    @foreach($filterFields as $field)
                        @php
                            $name = $field['name'] ?? '';
                            $label = $field['label'] ?? ucfirst($name);
                            $type = $field['type'] ?? 'text';
                            $placeholder = $field['placeholder'] ?? '';
                            $value = $field['value'] ?? request($name);
                            $options = $field['options'] ?? [];
                            $colSize = $field['col'] ?? 'col-md-3';
                            $required = $field['required'] ?? false;
                            $class = $field['class'] ?? '';
                        @endphp
                        
                        <div class="{{ $colSize }}">
                            <label for="filter_{{ $name }}" class="form-label">{{ $label }}</label>
                            
                            @if($type === 'select')
                                <select class="form-select index-filter-select {{ $class }}" id="filter_{{ $name }}" name="{{ $name }}" {{ $required ? 'required' : '' }}
                                        data-live-search="{{ $liveSearch ? 'true' : 'false' }}">
                                    @if(isset($field['empty_option']))
                                        <option value="" {{ $value == '' || $value === null ? 'selected' : '' }}>{{ $field['empty_option'] }}</option>
                                    @endif
                                    @foreach($options as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" {{ (string)$value === (string)$optionValue ? 'selected' : '' }}>
                                            {{ $optionLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif($type === 'date')
                                <input type="date" class="form-control index-filter-date {{ $class }}" id="filter_{{ $name }}" 
                                       name="{{ $name }}" value="{{ $value }}" placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}
                                       data-live-search="{{ $liveSearch ? 'true' : 'false' }}">
                            @elseif($type === 'date-range')
                                <div class="input-group">
                                    <input type="date" class="form-control index-filter-date {{ $class }}" id="filter_{{ $name }}_from" 
                                           name="{{ $name }}_from" value="{{ request($name . '_from') }}" placeholder="Từ ngày"
                                           data-live-search="{{ $liveSearch ? 'true' : 'false' }}">
                                    <span class="input-group-text">-</span>
                                    <input type="date" class="form-control index-filter-date {{ $class }}" id="filter_{{ $name }}_to" 
                                           name="{{ $name }}_to" value="{{ request($name . '_to') }}" placeholder="Đến ngày"
                                           data-live-search="{{ $liveSearch ? 'true' : 'false' }}">
                                </div>
                            @else
                                <input type="{{ $type }}" class="form-control index-filter-input {{ $class }}" id="filter_{{ $name }}" 
                                       name="{{ $name }}" value="{{ $value }}" placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}
                                       data-live-search="{{ $liveSearch && $type === 'text' ? 'true' : 'false' }}">
                            @endif
                        </div>
                    @endforeach
                    
                    @if($showReset || count($filterFields) > 0)
                        <div class="col-md-auto d-flex align-items-end">
                            <div class="d-flex gap-2">
                                @if(!$liveSearch)
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Tìm kiếm
                                    </button>
                                @endif
                                @if($showReset)
                                    <a href="{{ $resetUrl ?? request()->url() }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>Xóa bộ lọc
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

@if($liveSearch || $useAjax)
@push('scripts')
<script>
(function() {
    'use strict';
    
    const formId = '{{ $formId }}';
    const debounceDelay = {{ $debounceDelay }};
    const form = document.getElementById(formId);
    
    if (!form) return;
    
    let searchTimeout = null;
    let isSubmitting = false;
    const tableContainerId = '{{ $tableContainerId }}';
    const useAjax = {{ $useAjax ? 'true' : 'false' }};
    const formAction = form.action || window.location.href;
    
    // Make loadTableData globally accessible for stats cards
    window.loadTableData = function() {
        return loadTableDataInternal();
    };
    
    // Attach sort link handler to container
    function attachSortLinkHandler() {
        const container = document.getElementById(tableContainerId);
        if (!container) {
            // Retry after a short delay if container not found
            setTimeout(attachSortLinkHandler, 200);
            return;
        }
        
        // Check if table exists in container
        const table = container.querySelector('table');
        if (!table) {
            // Retry after a short delay if table not found
            setTimeout(attachSortLinkHandler, 200);
            return;
        }
        
        // Check if sort links exist in table
        // Support both: class "sort-link" and href containing "sort_by"
        const sortLinks = container.querySelectorAll('table thead th a.sort-link, table thead th a[href*="sort_by"]');
        if (sortLinks.length === 0) {
            // Retry after a short delay if sort links not found
            setTimeout(attachSortLinkHandler, 200);
            return;
        }
        
        // Remove any existing sort link handler
        const existingHandler = container._sortLinkHandler;
        if (existingHandler) {
            container.removeEventListener('click', existingHandler, true);
        }
        
        // Create new handler
        const sortLinkHandler = function(e) {
            // Support both: class "sort-link" and href containing "sort_by"
            const sortLink = e.target.closest('table thead th a.sort-link, table thead th a[href*="sort_by"]');
            if (sortLink) {
                // Check if link has HTMX attributes - if so, let HTMX handle it
                if (sortLink.hasAttribute('hx-get') || sortLink.hasAttribute('hx-post') || sortLink.hasAttribute('hx-put') || sortLink.hasAttribute('hx-delete')) {
                    // HTMX is handling this, skip
                    return;
                }
                
                // Check if index-table component has already handled this (via data attribute)
                // If so, let index-table handle it
                if (sortLink.dataset.sortHandlerAttached === 'true') {
                    // index-table is handling this, skip
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                const url = new URL(sortLink.href);
                const sortBy = url.searchParams.get('sort_by');
                const sortOrder = url.searchParams.get('sort_order');
                
                // Update or create sort_by input
                let sortByInput = form.querySelector('input[name="sort_by"]');
                if (!sortByInput) {
                    sortByInput = document.createElement('input');
                    sortByInput.type = 'hidden';
                    sortByInput.name = 'sort_by';
                    form.appendChild(sortByInput);
                }
                sortByInput.value = sortBy || '';
                
                // Update or create sort_order input
                let sortOrderInput = form.querySelector('input[name="sort_order"]');
                if (!sortOrderInput) {
                    sortOrderInput = document.createElement('input');
                    sortOrderInput.type = 'hidden';
                    sortOrderInput.name = 'sort_order';
                    form.appendChild(sortOrderInput);
                }
                sortOrderInput.value = sortOrder || 'asc';
                
                // Remove page input to reset to page 1
                const pageInput = form.querySelector('input[name="page"]');
                if (pageInput) {
                    pageInput.remove();
                }
                
                // Submit via AJAX
                loadTableDataInternal();
            }
        };
        
        // Store handler reference to remove later
        container._sortLinkHandler = sortLinkHandler;
        
        // Attach event listener using capture phase
        container.addEventListener('click', sortLinkHandler, true);
    }
    
    // Load table data via AJAX
    async function loadTableDataInternal() {
        // Prevent multiple submissions
        if (isSubmitting) return;
        isSubmitting = true;
        
        // Clear any existing timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
            searchTimeout = null;
        }
        
        // Show loading indicator if available
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        // Get form data
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        // Convert FormData to URLSearchParams
        // Only include non-empty values (except page, sort_by, sort_order which are handled separately)
        for (const [key, value] of formData.entries()) {
            // Skip empty values (except page, sort_by, sort_order)
            if (key === 'page' || key === 'sort_by' || key === 'sort_order') {
                if (value) {
                    params.append(key, value);
                }
            } else {
                // Convert value to string for safe comparison
                const stringValue = String(value || '');
                const trimmedValue = stringValue.trim();
                
                // Only include non-empty values
                if (trimmedValue !== '') {
                    // For select fields, check if empty option is selected
                    const field = form.querySelector(`[name="${key}"]`);
                    if (field && field.tagName === 'SELECT') {
                        // Check if first option is empty option (value is empty string)
                        const firstOption = field.options[0];
                        const isEmptyOption = firstOption && (firstOption.value === '' || firstOption.value === null);
                        
                        // Only skip if the selected value matches the empty option value
                        // Otherwise, include the value (even if it's '0', as it might be a valid filter value)
                        if (isEmptyOption && trimmedValue === '') {
                            // Skip empty option
                        } else {
                            params.append(key, trimmedValue);
                        }
                    } else {
                        params.append(key, trimmedValue);
                    }
                }
            }
        }
        
        // Add AJAX header
        params.append('ajax', '1');
        
        try {
            const response = await fetch(formAction + '?' + params.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });
            
            const contentType = response.headers.get('content-type');
            
            if (!response.ok) {
                // Try to get error message from response
                let errorMessage = 'Network response was not ok';
                if (contentType && contentType.includes('application/json')) {
                    try {
                        const errorData = await response.json();
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {
                        // Ignore JSON parse errors
                    }
                }
                throw new Error(errorMessage);
            }
            
            // Update URL without reload
            const newUrl = formAction + '?' + params.toString();
            window.history.pushState({}, '', newUrl);
            
            if (contentType && contentType.includes('application/json')) {
                // Handle JSON response
                const data = await response.json();
                if (data.success) {
                    if (data.table_html) {
                        // Check if table_html already contains card structure
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = data.table_html;
                        const hasCard = tempDiv.querySelector('.card');
                        
                        const container = document.getElementById(tableContainerId);
                        if (container) {
                            // Check if container has card structure
                            const containerCard = container.querySelector('.card');
                            
                            if (hasCard) {
                                // table_html already has card structure, replace container content directly
                                container.innerHTML = data.table_html;
                            } else if (containerCard) {
                                // Container has card, but table_html doesn't - update card body
                                const cardBody = containerCard.querySelector('.card-body');
                                if (cardBody) {
                                    cardBody.innerHTML = data.table_html;
                                } else {
                                    // No card-body, update card content directly
                                    containerCard.innerHTML = '<div class="card-body">' + data.table_html + '</div>';
                                }
                            } else {
                                // Neither has card structure - check if container has existing content
                                if (container.innerHTML.trim() && !container.querySelector('.card')) {
                                    // Container has content but no card - replace directly
                                    container.innerHTML = data.table_html;
                                } else {
                                    // Empty container or needs card - wrap in card
                                    container.innerHTML = '<div class="card shadow-sm"><div class="card-body">' + data.table_html + '</div></div>';
                                }
                            }
                        } else {
                            // Fallback: use updateTableContent
                            updateTableContent(data.table_html);
                        }
                    } else if (data.html) {
                        // Fallback for 'html' key
                        updateTableContent(data.html);
                    }
                    if (data.stats_html) {
                        updateStatsContent(data.stats_html);
                    }
                } else {
                    throw new Error(data.message || 'Có lỗi xảy ra khi tải dữ liệu');
                }
            } else {
                // Handle HTML response - extract table content
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const tableContainer = doc.getElementById(tableContainerId);
                
                if (tableContainer) {
                    updateTableContent(tableContainer.innerHTML);
                }
                
                // Update stats if exists
                const statsContainer = doc.querySelector('.statistics-cards-container') || 
                                      doc.getElementById('statistics-cards-container');
                if (statsContainer) {
                    updateStatsContent(statsContainer.innerHTML);
                }
            }
        } catch (error) {
            console.error('Error loading table data:', error);
            let errorMessage = error.message || 'Không thể tải dữ liệu. Vui lòng thử lại.';
            
            if (typeof Notify !== 'undefined') {
                Notify.error(errorMessage, 'Lỗi!');
            } else {
                alert(errorMessage);
            }
        } finally {
            isSubmitting = false;
            if (window.Preloader) {
                window.Preloader.hide();
            }
        }
    }
    
    // Update table content
    function updateTableContent(html) {
        const container = document.getElementById(tableContainerId);
        if (container) {
            container.innerHTML = html;
            
            // Re-initialize any scripts that need to run after content update
            if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                // Re-initialize Select2 if needed
                $(container).find('.select2').each(function() {
                    const $select = $(this);
                    // Destroy existing Select2 instance if any
                    if ($select.data('select2')) {
                        $select.select2('destroy');
                    }
                    // Initialize Select2
                    $select.select2({
                        placeholder: function() {
                            return $(this).data('placeholder') || 'Chọn...';
                        },
                        allowClear: true,
                        width: '100%'
                    });
                });
            }
            
            // Re-attach sort link handler after table update
            // Use a longer delay to ensure DOM is fully updated
            setTimeout(() => {
                attachSortLinkHandler();
            }, 200);
            
            // Re-initialize pagination links to use AJAX
            // Wait a bit for DOM to be ready
            setTimeout(() => {
                const paginationLinks = container.querySelectorAll('.pagination a, .pagination-controls a');
                paginationLinks.forEach(link => {
                    // Remove existing listeners to avoid duplicates
                    const newLink = link.cloneNode(true);
                    link.parentNode.replaceChild(newLink, link);
                    
                    newLink.addEventListener('click', function(e) {
                        e.preventDefault();
                        const url = new URL(this.href);
                        // Update form with pagination params
                        const page = url.searchParams.get('page');
                        if (page) {
                            // Create a hidden input for page
                            let pageInput = form.querySelector('input[name="page"]');
                            if (!pageInput) {
                                pageInput = document.createElement('input');
                                pageInput.type = 'hidden';
                                pageInput.name = 'page';
                                form.appendChild(pageInput);
                            }
                            pageInput.value = page;
                        } else {
                            // Remove page input if no page param
                            const pageInput = form.querySelector('input[name="page"]');
                            if (pageInput) {
                                pageInput.remove();
                            }
                        }
                        // Submit via AJAX
                        loadTableDataInternal();
                    });
                });
            }, 100);
            
            // Re-initialize event listeners for new content
            // Note: Don't call initializeLiveSearch() here to avoid duplicate listeners
            // The original listeners are still active
        }
    }
    
    // Update stats content
    function updateStatsContent(html) {
        const statsContainer = document.getElementById('statistics-cards-container') || 
                               document.querySelector('.statistics-cards-container');
        if (statsContainer && html) {
            statsContainer.innerHTML = html;
            // Stats cards use event delegation (attached to container), so no need to re-attach handlers
            // The click handler is attached to the container in statistics-cards.blade.php
        }
    }
    
    // Submit form function (fallback to page reload if AJAX fails)
    function submitForm() {
        if (useAjax) {
            loadTableDataInternal();
        } else {
            // Prevent multiple submissions
            if (isSubmitting) return;
            isSubmitting = true;
            
            // Clear any existing timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
                searchTimeout = null;
            }
            
            // Show loading indicator if available
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            // Submit form - use native form submit to ensure page reload
            setTimeout(function() {
                form.submit();
            }, 100);
        }
    }
    
    // Debounced submit function
    function debouncedSubmit() {
        // Clear existing timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Set new timeout
        searchTimeout = setTimeout(function() {
            submitForm();
        }, debounceDelay);
    }
    
    // Initialize sort params from URL
    function initializeSortParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const sortBy = urlParams.get('sort_by');
        const sortOrder = urlParams.get('sort_order');
        
        if (sortBy) {
            let sortByInput = form.querySelector('input[name="sort_by"]');
            if (!sortByInput) {
                sortByInput = document.createElement('input');
                sortByInput.type = 'hidden';
                sortByInput.name = 'sort_by';
                form.appendChild(sortByInput);
            }
            sortByInput.value = sortBy;
        }
        
        if (sortOrder) {
            let sortOrderInput = form.querySelector('input[name="sort_order"]');
            if (!sortOrderInput) {
                sortOrderInput = document.createElement('input');
                sortOrderInput.type = 'hidden';
                sortOrderInput.name = 'sort_order';
                form.appendChild(sortOrderInput);
            }
            sortOrderInput.value = sortOrder;
        }
    }
    
    // Initialize after DOM is ready
    function initializeLiveSearch() {
        // Initialize sort params from URL
        initializeSortParams();
        
        // Use event delegation to avoid duplicate listeners
        // Handle text inputs with live search
        form.addEventListener('input', function(e) {
            if (e.target.matches('.index-filter-input[data-live-search="true"]')) {
                debouncedSubmit();
            }
        });
        
        // Handle Enter key - submit immediately
        form.addEventListener('keydown', function(e) {
            if (e.target.matches('.index-filter-input[data-live-search="true"]') && e.key === 'Enter') {
                e.preventDefault();
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                submitForm();
            }
        });
        
        // Handle select changes - submit immediately (no debounce)
        form.addEventListener('change', function(e) {
            // If useAjax is true, submit all select changes via AJAX
            // If liveSearch is true, only submit selects with data-live-search="true"
            if (useAjax) {
                submitForm();
            } else if (e.target.matches('.index-filter-select[data-live-search="true"]')) {
                submitForm();
            }
        });
        
        // Handle Select2 changes if Select2 is used
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            const selects = form.querySelectorAll('.index-filter-select[data-live-search="true"]');
            selects.forEach(select => {
                // Check if Select2 is initialized
                if ($(select).data('select2')) {
                    // Remove existing handlers to avoid duplicates
                    $(select).off('select2:select select2:unselect');
                    // Use Select2 change event
                    $(select).on('select2:select select2:unselect', function() {
                        submitForm();
                    });
                }
            });
        }
        
        // Handle date inputs - submit immediately (no debounce)
        form.addEventListener('change', function(e) {
            // If useAjax is true, submit all date changes via AJAX
            // If liveSearch is true, only submit dates with data-live-search="true"
            if (useAjax && e.target.matches('.index-filter-date')) {
                submitForm();
            } else if (e.target.matches('.index-filter-date[data-live-search="true"]')) {
                submitForm();
            }
        });
        
        // Handle form submit event (when user clicks "Tìm kiếm" button)
        form.addEventListener('submit', function(e) {
            // Remove page input to reset to page 1
            const pageInput = form.querySelector('input[name="page"]');
            if (pageInput) {
                pageInput.remove();
            }
            
            // Submit via AJAX if enabled
            if (useAjax) {
                e.preventDefault();
                e.stopPropagation();
                submitForm();
            }
            // If useAjax is false, let form submit naturally (no preventDefault)
        });
    }
    
    // Auto-load table via AJAX on page load to ensure sort links work
    function autoLoadTableOnInit() {
        // Always auto-load table via AJAX on initial page load
        // This ensures sort links, stats, and filters are properly initialized
        // Only load if useAjax is enabled
        if (!useAjax) {
            return;
        }
        
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        
        // Reset form fields that are not in URL (except ajax parameter)
        form.querySelectorAll('select, input[type="text"], input[type="date"]').forEach(field => {
            const fieldName = field.name;
            // Skip if field is page, sort_by, sort_order (handled separately)
            if (fieldName === 'page' || fieldName === 'sort_by' || fieldName === 'sort_order') {
                return;
            }
            
            // If field is not in URL, reset to empty/default value
            if (!urlParams.has(fieldName)) {
                if (field.tagName === 'SELECT') {
                    field.selectedIndex = 0; // Select first option (empty option)
                } else {
                    field.value = '';
                }
            }
        });
        
        // Check if table container already has content (from server-side render)
        const container = document.getElementById(tableContainerId);
        if (container && container.innerHTML.trim() !== '') {
            // Table already loaded from server, but we still want to load via AJAX
            // to ensure sort links and stats are properly initialized
            setTimeout(function() {
                loadTableDataInternal();
            }, 100);
        } else {
            // Table not loaded yet, load via AJAX immediately
            setTimeout(function() {
                loadTableDataInternal();
            }, 100);
        }
    }
    
    // Wait for Select2 to initialize if it exists
    if (typeof $ !== 'undefined') {
        $(document).ready(function() {
            // Wait a bit for Select2 to fully initialize
            setTimeout(function() {
                initializeLiveSearch();
                // Auto-load table via AJAX on initial page load
                autoLoadTableOnInit();
            }, 300);
        });
    } else {
        // Initialize immediately if no Select2
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initializeLiveSearch();
                // Auto-load table via AJAX on initial page load
                autoLoadTableOnInit();
            });
        } else {
            initializeLiveSearch();
            // Auto-load table via AJAX on initial page load
            autoLoadTableOnInit();
        }
    }
})();
</script>
@endpush
@endif

