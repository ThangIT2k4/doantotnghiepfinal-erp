/**
 * Number Formatter Utility
 * Auto-format number inputs with thousand separators (dots)
 * Usage: Add class "number-input" or "money-input" to input fields
 */

(function() {
    'use strict';

    /**
     * Format number with thousand separators (dots)
     * @param {string|number} value - The number to format
     * @returns {string} - Formatted number string (e.g., "100.000.000")
     */
    function formatNumber(value) {
        if (!value && value !== 0) return '';
        // Remove all non-digit characters
        const number = value.toString().replace(/\D/g, '');
        if (!number) return '';
        // Format with dots as thousand separators
        return number.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    /**
     * Remove formatting from number string
     * @param {string|number} value - The formatted number string
     * @returns {string} - Unformatted number string (digits only)
     */
    function unformatNumber(value) {
        if (!value && value !== 0) return '';
        // Remove all non-digit characters
        return value.toString().replace(/\D/g, '');
    }

    /**
     * Initialize number formatting for input fields
     */
    function initNumberFormatter() {
        // Find all inputs with number-input or money-input class
        const numberInputs = document.querySelectorAll('input.number-input, input.money-input');
        
        numberInputs.forEach(input => {
            // Skip if already initialized
            if (input.dataset.formatterInitialized === 'true') {
                return;
            }
            
            // Mark as initialized
            input.dataset.formatterInitialized = 'true';
            
            // Create hidden input for raw value if not exists
            let rawInput = input.nextElementSibling;
            if (!rawInput || rawInput.type !== 'hidden' || !rawInput.id.endsWith('_raw')) {
                rawInput = document.createElement('input');
                rawInput.type = 'hidden';
                rawInput.id = input.id + '_raw';
                rawInput.name = input.name + '_raw';
                input.parentNode.insertBefore(rawInput, input.nextSibling);
            }
            
            // Format existing value on page load
            if (input.value) {
                const unformatted = unformatNumber(input.value);
                input.value = formatNumber(unformatted);
                rawInput.value = unformatted;
            }
            
            // Format on input
            input.addEventListener('input', function(e) {
                const cursorPosition = this.selectionStart;
                const originalValue = this.value;
                const unformatted = unformatNumber(this.value);
                
                // Format the value
                const formatted = formatNumber(unformatted);
                this.value = formatted;
                
                // Update raw value
                rawInput.value = unformatted;
                
                // Restore cursor position
                const lengthDiff = formatted.length - originalValue.length;
                const newCursorPosition = Math.max(0, Math.min(cursorPosition + lengthDiff, formatted.length));
                this.setSelectionRange(newCursorPosition, newCursorPosition);
            });
            
            // Format on blur (when user leaves the field)
            input.addEventListener('blur', function() {
                if (this.value) {
                    const unformatted = unformatNumber(this.value);
                    this.value = formatNumber(unformatted);
                    rawInput.value = unformatted;
                }
            });
            
            // On focus, format if needed
            input.addEventListener('focus', function() {
                if (this.value) {
                    const unformatted = unformatNumber(this.value);
                    const formatted = formatNumber(unformatted);
                    if (this.value !== formatted) {
                        this.value = formatted;
                        rawInput.value = unformatted;
                    }
                }
            });
        });
    }

    /**
     * Get unformatted value from input field
     * @param {HTMLElement|string} input - Input element or input ID
     * @returns {string} - Unformatted number string
     */
    function getUnformattedValue(input) {
        const element = typeof input === 'string' ? document.getElementById(input) : input;
        if (!element) return '';
        
        // Always unformat from the visible input value to ensure accuracy
        // Don't rely on rawInput as it might be stale
        const currentValue = element.value || '';
        return unformatNumber(currentValue);
    }

    /**
     * Set formatted value to input field
     * @param {HTMLElement|string} input - Input element or input ID
     * @param {string|number} value - Value to set
     */
    function setFormattedValue(input, value) {
        const element = typeof input === 'string' ? document.getElementById(input) : input;
        if (!element) return;
        
        const unformatted = unformatNumber(value);
        const formatted = formatNumber(unformatted);
        
        element.value = formatted;
        
        const rawInput = document.getElementById(element.id + '_raw');
        if (rawInput) {
            rawInput.value = unformatted;
        }
    }

    /**
     * Process form before submission - convert formatted values to raw values
     * @param {HTMLFormElement} form - Form element
     */
    function processFormBeforeSubmit(form) {
        if (!form) return;
        
        const numberInputs = form.querySelectorAll('input.number-input, input.money-input');
        numberInputs.forEach(input => {
            const unformatted = unformatNumber(input.value);
            
            // Update the input value directly to raw value for submission
            // This ensures the form submits the unformatted value
            input.value = unformatted;
            
            // Also update hidden raw input if exists
            const rawInput = document.getElementById(input.id + '_raw');
            if (rawInput) {
                rawInput.value = unformatted;
            }
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNumberFormatter);
    } else {
        initNumberFormatter();
    }

    // Re-initialize on dynamic content (for AJAX loaded forms)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                initNumberFormatter();
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Expose utility functions globally
    window.NumberFormatter = {
        format: formatNumber,
        unformat: unformatNumber,
        getValue: getUnformattedValue,
        setValue: setFormattedValue,
        processForm: processFormBeforeSubmit
    };
})();

