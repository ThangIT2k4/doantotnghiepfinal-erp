@props([
    'items' => null, // Paginated collection or array
    'columns' => [], // Array of column definitions
    'emptyMessage' => 'Chưa có dữ liệu',
    'emptyIcon' => 'fa-inbox',
    'emptyAction' => null, // Action button config for empty state
    'sortable' => true, // Enable sorting
    'sortBy' => null, // Current sort field
    'sortOrder' => null, // Current sort order (asc/desc)
    'sortUrl' => null, // Base URL for sorting (defaults to current URL)
    'actions' => [], // Array of action configs per row (function that receives item)
    'rowActions' => null, // Alternative: function that returns actions for each row
    'tableClass' => 'table-hover',
    'showCount' => true, // Show item count in header
    'tableContainerId' => 'index-table-container', // Container ID for AJAX updates
    'selectable' => true, // Enable row selection with checkboxes
    'bulkActions' => [], // Array of bulk action configs: ['label' => 'Xóa', 'action' => 'delete', 'url' => route(...), 'method' => 'POST', 'confirm' => true]
    'itemIdField' => 'id', // Field name to use as item ID for selection
])

@php
    // Get items count
    $itemsCount = $items ? (method_exists($items, 'count') ? $items->count() : count($items)) : 0;
    $hasItems = $itemsCount > 0;
    
    // Default sort URL
    $baseSortUrl = $sortUrl ?? request()->url();
    
    // Generate sort URL
    $generateSortUrl = function($field) use ($baseSortUrl, $sortBy, $sortOrder) {
        $query = request()->query();
        // Remove ajax parameter for HTMX requests
        unset($query['ajax']);
        $query['sort_by'] = $field;
        $query['sort_order'] = ($sortBy === $field && $sortOrder === 'asc') ? 'desc' : 'asc';
        return $baseSortUrl . '?' . http_build_query($query);
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

<!-- Table -->
<div class="row" id="{{ $tableContainerId }}" hx-preserve="true">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>Danh sách
                    @if($showCount && $hasItems)
                        <span class="badge bg-primary ms-2">{{ $itemsCount }}</span>
                    @endif
                </h5>
            </div>
            <div class="card-body p-0">
                @if($hasItems)
                    @if($selectable && count($bulkActions) > 0)
                        <div class="bulk-actions-toolbar p-3 border-bottom bg-light" style="display: none;">
                            <div class="d-flex align-items-center gap-2">
                                <span class="selected-count fw-bold text-primary">0</span>
                                <span>mục đã chọn</span>
                                <div class="ms-auto d-flex gap-2">
                                    @foreach($bulkActions as $bulkAction)
                                        <button type="button" 
                                                class="btn btn-sm btn-{{ $bulkAction['variant'] ?? 'secondary' }} bulk-action-btn"
                                                data-action="{{ $bulkAction['action'] ?? '' }}"
                                                data-url="{{ $bulkAction['url'] ?? '' }}"
                                                data-method="{{ $bulkAction['method'] ?? 'POST' }}"
                                                data-confirm="{{ $bulkAction['confirm'] ?? true }}"
                                                data-confirm-message="{{ $bulkAction['confirmMessage'] ?? 'Bạn có chắc chắn muốn thực hiện thao tác này?' }}">
                                            @if(isset($bulkAction['icon']))
                                                <i class="{{ $bulkAction['icon'] }} me-1"></i>
                                            @endif
                                            {{ $bulkAction['label'] ?? 'Thao tác' }}
                                        </button>
                                    @endforeach
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                                        <i class="fas fa-times me-1"></i>Bỏ chọn
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table {{ $tableClass }} mb-0">
                            <thead>
                                <tr>
                                    @if($selectable)
                                        <th style="width: 40px;">
                                            <input type="checkbox" class="form-check-input select-all-checkbox" id="select-all-{{ $tableContainerId }}">
                                        </th>
                                    @endif
                                    @foreach($columns as $column)
                                        @php
                                            $label = $column['label'] ?? ucfirst($column['name'] ?? '');
                                            $name = $column['name'] ?? '';
                                            $sortable = ($column['sortable'] ?? true) && $sortable;
                                        @endphp
                                        <th>
                                            @if($sortable && $name)
                                                <a href="{{ $generateSortUrl($name) }}" 
                                                   class="text-decoration-none text-dark sort-link" 
                                                   data-sort-field="{{ $name }}"
                                                   hx-get="{{ $generateSortUrl($name) }}"
                                                   hx-target="#{{ $tableContainerId }}"
                                                   hx-swap="innerHTML"
                                                   hx-push-url="true"
                                                   hx-trigger="click"
                                                   style="cursor: pointer;">
                                                    {{ $label }}
                                                    {!! $getSortIcon($name) !!}
                                                </a>
                                            @else
                                                {{ $label }}
                                            @endif
                                        </th>
                                    @endforeach
                                    @if(count($actions) > 0 || $rowActions)
                                        <th>Thao tác</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    @php
                                        $itemId = data_get($item, $itemIdField);
                                    @endphp
                                    <tr data-item-id="{{ $itemId }}">
                                        @if($selectable)
                                            <td>
                                                <input type="checkbox" 
                                                       class="form-check-input row-checkbox" 
                                                       value="{{ $itemId }}"
                                                       data-item-id="{{ $itemId }}">
                                            </td>
                                        @endif
                                        @foreach($columns as $column)
                                            @php
                                                $name = $column['name'] ?? '';
                                                $format = $column['format'] ?? null; // Function or closure
                                                $component = $column['component'] ?? null; // Component name
                                                $slot = $column['slot'] ?? null; // Slot name
                                            @endphp
                                            <td>
                                                @if($component)
                                                    @include($component, ['item' => $item, 'column' => $column])
                                                @elseif($slot && isset($column['slotContent']))
                                                    {!! $column['slotContent']($item) !!}
                                                @elseif($format && is_callable($format))
                                                    {!! $format($item) !!}
                                                @elseif($name)
                                                    @php
                                                        try {
                                                            $value = data_get($item, $name);
                                                            echo $value !== null ? $value : '-';
                                                        } catch (\Exception $e) {
                                                            echo '-';
                                                        }
                                                    @endphp
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        @endforeach
                                        
                                        @if(count($actions) > 0 || $rowActions)
                                            <td>
                                                @php
                                                    $rowActionsList = $rowActions ? $rowActions($item) : $actions;
                                                @endphp
                                                @if(count($rowActionsList) > 0)
                                                    <div class="btn-group table-actions" role="group">
                                                        @foreach($rowActionsList as $action)
                                                            @php
                                                                // Replace placeholders in action config
                                                                $actionConfig = [];
                                                                foreach ($action as $key => $value) {
                                                                    if (is_string($value)) {
                                                                        // Replace {id}, {item.id}, etc.
                                                                        $actionConfig[$key] = preg_replace_callback('/\{([^}]+)\}/', function($matches) use ($item) {
                                                                            return data_get($item, $matches[1], $matches[0]);
                                                                        }, $value);
                                                                    } else {
                                                                        $actionConfig[$key] = $value;
                                                                    }
                                                                }
                                                            @endphp
                                                            @include('staff.components.button', array_merge([
                                                                'type' => 'link',
                                                                'size' => 'sm',
                                                                'iconPosition' => 'only',
                                                            ], $actionConfig))
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if(method_exists($items, 'hasPages') && $items->hasPages())
                        <div class="d-flex justify-content-center mt-4 mb-3">
                            {{ $items->appends(request()->query())->links('vendor.pagination.custom', [
                                'tableContainerId' => $tableContainerId ?? 'index-table-container'
                            ]) }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <i class="fas {{ $emptyIcon }} fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">{{ $emptyMessage }}</h5>
                        @if($emptyAction)
                            <p class="text-muted mb-3">Bắt đầu tạo mục đầu tiên</p>
                            @include('staff.components.button', array_merge([
                                'type' => 'link',
                                'variant' => 'primary',
                            ], $emptyAction))
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
}

.table td {
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.table th a {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.table th a:hover {
    color: #0d6efd !important;
}

/* Bulk Actions Toolbar */
.bulk-actions-toolbar {
    transition: all 0.3s ease;
}

.table tbody tr.selected {
    background-color: #e7f3ff !important;
}

.table tbody tr.selected:hover {
    background-color: #d0e7ff !important;
}

/* Table Actions - Bỏ border và underline */
.table .table-actions .btn,
.table .table-actions .btn-icon-only,
.table .table-actions a.btn {
    border: none !important;
    text-decoration: none !important;
    background: transparent !important;
}

.table .table-actions .btn:hover,
.table .table-actions .btn-icon-only:hover,
.table .table-actions a.btn:hover,
.table .table-actions .btn:focus,
.table .table-actions .btn-icon-only:focus,
.table .table-actions a.btn:focus,
.table .table-actions .btn:active,
.table .table-actions .btn-icon-only:active,
.table .table-actions a.btn:active {
    border: none !important;
    text-decoration: none !important;
    background: transparent !important;
}

.table .table-actions .btn-outline-primary:hover {
    background-color: rgba(13, 110, 253, 0.1) !important;
    color: #0d6efd !important;
}

.table .table-actions .btn-outline-warning:hover {
    background-color: rgba(255, 193, 7, 0.1) !important;
    color: #ffc107 !important;
}

.table .table-actions .btn-outline-danger:hover {
    background-color: rgba(220, 53, 69, 0.1) !important;
    color: #dc3545 !important;
}

.table .table-actions .btn-outline-info:hover {
    background-color: rgba(13, 202, 240, 0.1) !important;
    color: #0dcaf0 !important;
}

.table .table-actions .btn-outline-secondary:hover {
    background-color: rgba(108, 117, 125, 0.1) !important;
    color: #6c757d !important;
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';
    
    // Initialize AJAX sort and selection for index-table component
    const tableContainerId = '{{ $tableContainerId }}';
    const formId = 'index-filters-form'; // Default form ID from index-filters
    const selectable = {{ $selectable ? 'true' : 'false' }};
    
    // Selection management
    function updateSelection() {
        const tableContainer = document.getElementById(tableContainerId);
        if (!tableContainer) return;
        
        const checkboxes = tableContainer.querySelectorAll('.row-checkbox');
        const selectAllCheckbox = tableContainer.querySelector('.select-all-checkbox');
        const toolbar = tableContainer.querySelector('.bulk-actions-toolbar');
        const selectedCount = tableContainer.querySelector('.selected-count');
        
        if (!checkboxes.length) return;
        
        const checked = Array.from(checkboxes).filter(cb => cb.checked);
        const allChecked = checked.length === checkboxes.length && checkboxes.length > 0;
        const someChecked = checked.length > 0;
        
        // Update select all checkbox
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
        }
        
        // Update toolbar visibility
        if (toolbar) {
            toolbar.style.display = someChecked ? 'block' : 'none';
        }
        
        // Update count
        if (selectedCount) {
            selectedCount.textContent = checked.length;
        }
        
        // Update row classes
        checkboxes.forEach(function(checkbox) {
            const row = checkbox.closest('tr');
            if (checkbox.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
        });
    }
    
    // Get selected IDs
    function getSelectedIds() {
        const tableContainer = document.getElementById(tableContainerId);
        if (!tableContainer) return [];
        
        const checkboxes = tableContainer.querySelectorAll('.row-checkbox:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }
    
    // Clear selection
    window.clearSelection = function() {
        const tableContainer = document.getElementById(tableContainerId);
        if (!tableContainer) return;
        
        const checkboxes = tableContainer.querySelectorAll('.row-checkbox');
        const selectAllCheckbox = tableContainer.querySelector('.select-all-checkbox');
        
        checkboxes.forEach(cb => cb.checked = false);
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
        
        updateSelection();
    };
    
    // Attach checkbox handlers
    function attachCheckboxHandlers() {
        if (!selectable) return;
        
        const tableContainer = document.getElementById(tableContainerId);
        if (!tableContainer) return;
        
        // Select all checkbox
        const selectAllCheckbox = tableContainer.querySelector('.select-all-checkbox');
        if (selectAllCheckbox && !selectAllCheckbox.dataset.handlerAttached) {
            selectAllCheckbox.dataset.handlerAttached = 'true';
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = tableContainer.querySelectorAll('.row-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateSelection();
            });
        }
        
        // Row checkboxes
        const checkboxes = tableContainer.querySelectorAll('.row-checkbox');
        checkboxes.forEach(function(checkbox) {
            if (checkbox.dataset.handlerAttached === 'true') return;
            checkbox.dataset.handlerAttached = 'true';
            
            checkbox.addEventListener('change', function() {
                updateSelection();
            });
        });
    }
    
    // Attach bulk action handlers
    function attachBulkActionHandlers() {
        const tableContainer = document.getElementById(tableContainerId);
        if (!tableContainer) return;
        
        const bulkActionBtns = tableContainer.querySelectorAll('.bulk-action-btn');
        bulkActionBtns.forEach(function(btn) {
            if (btn.dataset.handlerAttached === 'true') return;
            btn.dataset.handlerAttached = 'true';
            
            btn.addEventListener('click', function() {
                const selectedIds = getSelectedIds();
                if (selectedIds.length === 0) {
                    if (typeof Notify !== 'undefined') {
                        Notify.warning('Vui lòng chọn ít nhất một mục', 'Cảnh báo');
                    } else {
                        alert('Vui lòng chọn ít nhất một mục');
                    }
                    return;
                }
                
                const action = this.dataset.action;
                const url = this.dataset.url;
                const method = this.dataset.method || 'POST';
                const needConfirm = this.dataset.confirm === 'true';
                const confirmMessage = this.dataset.confirmMessage || 'Bạn có chắc chắn muốn thực hiện thao tác này?';
                
                if (!url) {
                    console.error('Bulk action URL is not defined');
                    return;
                }
                
                const performAction = function() {
                    if (window.Preloader) {
                        window.Preloader.show();
                    }
                    
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) {
                        console.error('CSRF token not found');
                        if (window.Preloader) {
                            window.Preloader.hide();
                        }
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('ids', JSON.stringify(selectedIds));
                    formData.append('_token', csrfToken.getAttribute('content'));
                    
                    // Add action parameter if exists
                    if (action) {
                        formData.append('action', action);
                    }
                    
                    // Add method override if needed
                    if (method !== 'POST' && method !== 'GET') {
                        formData.append('_method', method);
                    }
                    
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            if (typeof Notify !== 'undefined') {
                                Notify.success(data.message || 'Thao tác thành công!', 'Thành công');
                            } else {
                                alert(data.message || 'Thao tác thành công!');
                            }
                            
                            // Clear selection
                            clearSelection();
                            
                            // Reload table
                            if (typeof window.loadTableData === 'function') {
                                window.loadTableData();
                            } else {
                                window.location.reload();
                            }
                        } else {
                            throw new Error(data.message || 'Có lỗi xảy ra');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (typeof Notify !== 'undefined') {
                            Notify.error(error.message || 'Có lỗi xảy ra khi thực hiện thao tác', 'Lỗi');
                        } else {
                            alert('Có lỗi xảy ra: ' + error.message);
                        }
                    })
                    .finally(() => {
                        if (window.Preloader) {
                            window.Preloader.hide();
                        }
                    });
                };
                
                if (needConfirm) {
                    if (typeof Notify !== 'undefined' && Notify.confirm) {
                        Notify.confirm(confirmMessage, 'Xác nhận', performAction);
                    } else if (confirm(confirmMessage)) {
                        performAction();
                    }
                } else {
                    performAction();
                }
            });
        });
    }
    
    // Attach sort link handlers
    function attachSortLinkHandlers() {
        const tableContainer = document.getElementById(tableContainerId);
        if (!tableContainer) return;
        
        // Find sort links in the table
        const sortLinks = tableContainer.querySelectorAll('a.sort-link, a[href*="sort_by"]');
        if (sortLinks.length === 0) return;
        
        sortLinks.forEach(function(link) {
            // Check if handler already attached
            if (link.dataset.sortHandlerAttached === 'true') return;
            
            // Mark as attached
            link.dataset.sortHandlerAttached = 'true';
            
            // Attach click handler
            link.addEventListener('click', function(e) {
                // Check if link has HTMX attributes - if so, let HTMX handle it
                if (this.hasAttribute('hx-get') || this.hasAttribute('hx-post') || this.hasAttribute('hx-put') || this.hasAttribute('hx-delete')) {
                    // HTMX is handling this, skip
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                // Try to find form (from index-filters component)
                const form = document.getElementById(formId);
                
                // Get sort parameters from URL
                const url = new URL(this.href);
                const sortBy = url.searchParams.get('sort_by');
                const sortOrder = url.searchParams.get('sort_order');
                
                if (form) {
                    // If form exists (index-filters is present), use it
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
                    
                    // Submit via AJAX if loadTableData function exists
                    if (typeof window.loadTableData === 'function') {
                        window.loadTableData();
                    } else if (typeof loadTableData === 'function') {
                        loadTableData();
                    } else {
                        // Fallback to normal navigation
                        window.location.href = this.href;
                    }
                } else {
                    // No form found, use direct navigation with AJAX if possible
                    // Try to reload table container via AJAX
                    if (typeof window.loadTableData === 'function') {
                        // Update URL first
                        window.history.pushState({}, '', this.href);
                        window.loadTableData();
                    } else {
                        // Fallback to normal navigation
                        window.location.href = this.href;
                    }
                }
            });
        });
    }
    
    // Initialize on DOM ready
    function initializeTableHandlers() {
        attachSortLinkHandlers();
        attachCheckboxHandlers();
        attachBulkActionHandlers();
        updateSelection();
        
        // Re-attach handlers after AJAX updates
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    const tableContainer = document.getElementById(tableContainerId);
                    if (tableContainer) {
                        // Reset handlers for new sort links
                        const newLinks = tableContainer.querySelectorAll('a.sort-link, a[href*="sort_by"]');
                        newLinks.forEach(function(link) {
                            link.dataset.sortHandlerAttached = 'false';
                        });
                        
                        // Reset handlers for checkboxes
                        const newCheckboxes = tableContainer.querySelectorAll('.row-checkbox, .select-all-checkbox');
                        newCheckboxes.forEach(function(cb) {
                            cb.dataset.handlerAttached = 'false';
                        });
                        
                        // Reset handlers for bulk actions
                        const newBulkBtns = tableContainer.querySelectorAll('.bulk-action-btn');
                        newBulkBtns.forEach(function(btn) {
                            btn.dataset.handlerAttached = 'false';
                        });
                    }
                    attachSortLinkHandlers();
                    attachCheckboxHandlers();
                    attachBulkActionHandlers();
                    updateSelection();
                }
            });
        });
        
        const tableContainer = document.getElementById(tableContainerId);
        if (tableContainer) {
            observer.observe(tableContainer, {
                childList: true,
                subtree: true
            });
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeTableHandlers);
    } else {
        initializeTableHandlers();
    }
})();
</script>
@endpush

