@props([
    'action' => null, // Form action URL
    'method' => 'GET',
    'fields' => [], // Array of filter fields
    'showReset' => true, // Show reset button
    'resetUrl' => null, // Reset URL (defaults to current route without params)
    'tableContainerId' => 'index-table-container', // Table container ID for HTMX updates
    'statsContainerId' => null, // Stats container ID (optional, for separate stats update)
    'debounceDelay' => 500, // Debounce delay in milliseconds
    'formId' => 'index-filters-form', // Form ID
    'showSubmitButton' => false, // Show submit button (usually not needed with live search)
])

@php
    $filterFields = $fields;
    $formAction = $action ?? request()->url();
    $resetUrl = $resetUrl ?? request()->url();
@endphp

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <form id="{{ $formId }}" 
                      method="{{ $method }}" 
                      action="{{ $formAction }}"
                      hx-get="{{ $formAction }}"
                      hx-target="#{{ $tableContainerId }}"
                      hx-swap="innerHTML"
                      hx-include="*"
                      hx-indicator="#htmx-loading-{{ $formId }}"
                      hx-push-url="true"
                      hx-trigger="submit"
                      class="row g-3 index-filters-form">
                    
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
                            $liveSearch = $field['live_search'] ?? true;
                        @endphp
                        
                        <div class="{{ $colSize }}">
                            <label for="filter_{{ $name }}" class="form-label">{{ $label }}</label>
                            
                            @if($type === 'select')
                                <select class="form-select {{ $class }}" 
                                        id="filter_{{ $name }}" 
                                        name="{{ $name }}" 
                                        {{ $required ? 'required' : '' }}>
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
                                <input type="date" 
                                       class="form-control {{ $class }}" 
                                       id="filter_{{ $name }}" 
                                       name="{{ $name }}" 
                                       value="{{ $value }}" 
                                       placeholder="{{ $placeholder }}" 
                                       {{ $required ? 'required' : '' }}>
                            @elseif($type === 'date-range')
                                <div class="input-group">
                                    <input type="date" 
                                           class="form-control {{ $class }}" 
                                           id="filter_{{ $name }}_from" 
                                           name="{{ $name }}_from" 
                                           value="{{ request($name . '_from') }}" 
                                           placeholder="Từ ngày">
                                    <span class="input-group-text">-</span>
                                    <input type="date" 
                                           class="form-control {{ $class }}" 
                                           id="filter_{{ $name }}_to" 
                                           name="{{ $name }}_to" 
                                           value="{{ request($name . '_to') }}" 
                                           placeholder="Đến ngày">
                                </div>
                            @else
                                <input type="{{ $type }}" 
                                       class="form-control {{ $class }}" 
                                       id="filter_{{ $name }}" 
                                       name="{{ $name }}" 
                                       value="{{ $value }}" 
                                       placeholder="{{ $placeholder }}" 
                                       {{ $required ? 'required' : '' }}>
                            @endif
                        </div>
                    @endforeach
                    
                    @if($showReset || $showSubmitButton)
                        <div class="col-md-auto d-flex align-items-end">
                            <div class="d-flex gap-2">
                                @if($showSubmitButton)
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Tìm kiếm
                                    </button>
                                @endif
                                @if($showReset)
                                    <button type="button"
                                            class="btn btn-secondary"
                                            hx-get="{{ $resetUrl }}"
                                            hx-target="#{{ $tableContainerId }}"
                                            hx-swap="innerHTML"
                                            hx-push-url="true"
                                            hx-indicator="#htmx-loading-{{ $formId }}"
                                            hx-trigger="click"
                                            id="reset-filters-btn-{{ $formId }}">
                                        <i class="fas fa-times me-1"></i>Xóa bộ lọc
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Loading Indicator -->
<div id="htmx-loading-{{ $formId }}" class="htmx-indicator position-fixed top-50 start-50 translate-middle" style="z-index: 9999;">
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Đang tải...</span>
    </div>
</div>

@push('styles')
<style>
.htmx-indicator {
    opacity: 0;
    transition: opacity 200ms ease-in;
    pointer-events: none;
}
.htmx-request .htmx-indicator,
.htmx-request.htmx-indicator {
    opacity: 1;
}
#{{ $tableContainerId }}.htmx-request {
    opacity: 0.6;
    pointer-events: none;
}
</style>
@endpush

@push('scripts')
<script>
(function() {
    'use strict';
    
    const formId = '{{ $formId }}';
    const form = document.getElementById(formId);
    const tableContainerId = '{{ $tableContainerId }}';
    
    if (!form) return;
    
    // Store initial form values to detect changes
    function getFormValues() {
        const formData = new FormData(form);
        const values = {};
        for (const [key, value] of formData.entries()) {
            // Normalize empty values to empty string for consistent comparison
            // Trim text input values to remove whitespace
            if (typeof value === 'string') {
                values[key] = value.trim() || '';
            } else {
                values[key] = value || '';
            }
        }
        // Also include empty fields (not in FormData) for comparison
        form.querySelectorAll('input, select').forEach(function(field) {
            const name = field.name;
            if (name && !values.hasOwnProperty(name)) {
                values[name] = '';
            }
        });
        return JSON.stringify(values);
    }
    
    let lastFormValues = getFormValues();
    let isInitialized = false;
    
    // Debounce function to prevent multiple rapid submissions
    let submitTimeout = null;
    function debouncedSubmit() {
        if (!isInitialized) {
            // Not initialized yet, don't submit
            return;
        }
        
        // Check if form values actually changed
        const currentValues = getFormValues();
        if (currentValues === lastFormValues) {
            // No change, don't submit
            return;
        }
        
        // Update last values before submitting
        lastFormValues = currentValues;
        
        if (submitTimeout) {
            clearTimeout(submitTimeout);
        }
        submitTimeout = setTimeout(function() {
            if (typeof htmx !== 'undefined' && isInitialized) {
                htmx.trigger(form, 'submit');
            }
        }, 100);
    }
    
    // Initialize Select2 and ensure it triggers HTMX
    function initializeSelect2() {
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            return;
        }
        
        const selects = form.querySelectorAll('select.select2');
        selects.forEach(function(select) {
            const $select = $(select);
            
            // Destroy existing Select2 if any
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
            
            // Ensure Select2 change triggers HTMX form submit (debounced)
            $select.off('select2:select select2:unselect change').on('select2:select select2:unselect change', function() {
                debouncedSubmit();
            });
        });
    }
    
    // Handle text input with debounce
    const debounceDelay = {{ $debounceDelay }};
    const textInputs = form.querySelectorAll('input[type="text"], input[type="number"]');
    let textInputTimeout = null;
    textInputs.forEach(function(input) {
        // Trim input value on blur to remove whitespace
        input.addEventListener('blur', function() {
            if (this.type === 'text' && this.value) {
                this.value = this.value.trim();
            }
        });
        
        input.addEventListener('input', function() {
            // Always trigger submit when input changes (including clearing)
            // The debouncedSubmit function will check if values actually changed
            if (textInputTimeout) {
                clearTimeout(textInputTimeout);
            }
            textInputTimeout = setTimeout(function() {
                debouncedSubmit();
            }, debounceDelay);
        });
    });
    
    // Handle select and date changes
    const selectsAndDates = form.querySelectorAll('select:not(.select2), input[type="date"]');
    selectsAndDates.forEach(function(field) {
        field.addEventListener('change', function() {
            // Always trigger submit when field changes (including reset to default)
            // The debouncedSubmit function will check if values actually changed
            debouncedSubmit();
        });
    });
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                initializeSelect2();
                // Mark as initialized after a short delay to prevent initial triggers
                setTimeout(function() {
                    isInitialized = true;
                    lastFormValues = getFormValues(); // Update after initialization
                }, 500);
            }, 100);
        });
    } else {
        setTimeout(function() {
            initializeSelect2();
            // Mark as initialized after a short delay to prevent initial triggers
            setTimeout(function() {
                isInitialized = true;
                lastFormValues = getFormValues(); // Update after initialization
            }, 500);
        }, 100);
    }
    
    // Function to reset form fields to default values
    function resetFormFields() {
        const form = document.getElementById(formId);
        if (!form) return;
        
        // Store old values before reset
        const oldValues = getFormValues();
        
        form.querySelectorAll('input, select').forEach(function(field) {
            if (field.type === 'text' || field.type === 'number' || field.type === 'date') {
                field.value = '';
            } else if (field.tagName === 'SELECT') {
                // Reset to first option (empty option if exists)
                const firstOption = field.querySelector('option[value=""]');
                if (firstOption) {
                    field.value = '';
                } else {
                    field.selectedIndex = 0;
                }
                // Trigger change event for Select2 if it exists
                if (field.classList.contains('select2') && typeof $ !== 'undefined') {
                    $(field).trigger('change');
                } else {
                    // Trigger native change event for non-Select2 fields
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });
        
        // Check if values actually changed, if so trigger submit
        const newValues = getFormValues();
        if (oldValues !== newValues && isInitialized) {
            // Values changed, trigger submit
            lastFormValues = newValues;
            if (typeof htmx !== 'undefined') {
                htmx.trigger(form, 'submit');
            }
        } else {
            // No change, just update last values
            lastFormValues = newValues;
        }
    }
    
    // Re-initialize Select2 after HTMX updates
    document.body.addEventListener('htmx:afterSwap', function(event) {
        if (event.detail.target.id === tableContainerId || event.detail.target.closest('#' + tableContainerId)) {
            setTimeout(function() {
                initializeSelect2();
                // Check if URL has no query params (reset case) and sync form fields
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.toString() === '') {
                    // URL has no params, reset form fields
                    resetFormFields();
                } else {
                    // Update form values after swap to prevent false triggers
                    lastFormValues = getFormValues();
                }
            }, 100);
        }
    });
    
    // Handle reset button click - the button already has hx-get, so it will trigger HTMX request
    // We just need to clear form fields to sync with the reset
    const resetBtn = document.getElementById('reset-filters-btn-{{ $formId }}');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            // Clear form fields immediately to sync with reset URL
            // The hx-get on the button will handle the actual request
            const form = document.getElementById(formId);
            if (form) {
                form.querySelectorAll('input, select').forEach(function(field) {
                    if (field.type === 'text' || field.type === 'number' || field.type === 'date') {
                        field.value = '';
                    } else if (field.tagName === 'SELECT') {
                        const firstOption = field.querySelector('option[value=""]');
                        if (firstOption) {
                            field.value = '';
                        } else {
                            field.selectedIndex = 0;
                        }
                        if (field.classList.contains('select2') && typeof $ !== 'undefined') {
                            $(field).trigger('change');
                        }
                    }
                });
                // Update last form values after reset
                setTimeout(function() {
                    lastFormValues = getFormValues();
                }, 100);
            }
        });
    }
    
})();
</script>
@endpush

