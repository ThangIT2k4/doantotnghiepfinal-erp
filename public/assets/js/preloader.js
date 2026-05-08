/**
 * ========================================
 * PRELOADER SCRIPT
 * Quản lý hiển thị và ẩn preloader
 * ======================================== 
 */

(function() {
    'use strict';

    // Configuration
    const config = {
        minDisplayTime: 500,        // Minimum time to show preloader (ms)
        maxDisplayTime: 5000,       // Maximum time before force hide (ms)
        fadeOutDuration: 500,       // Fade out animation duration (ms)
        simulateLoading: false      // UI Auth-style không còn thanh %
    };

    // Preloader instance
    const Preloader = {
        element: null,
        percentageElement: null,
        startTime: null,
        progress: 0,
        interval: null,

        /**
         * Initialize preloader
         */
        init: function() {
            this.element = document.getElementById('preloader');
            if (!this.element) return;

            this.percentageElement = this.element.querySelector('.preloader__percentage');
            this.startTime = Date.now();

            if (config.simulateLoading && this.percentageElement) {
                this.simulateProgress();
            }

            // Listen for page load
            if (document.readyState === 'complete') {
                this.hide();
            } else {
                window.addEventListener('load', () => this.hide());
            }

            // Force hide after max time
            setTimeout(() => {
                if (this.element && !this.element.classList.contains('fade-out')) {
                    console.warn('Preloader force hidden after max time');
                    this.hide(true);
                }
            }, config.maxDisplayTime);
        },

        /**
         * Simulate loading progress
         */
        simulateProgress: function() {
            this.interval = setInterval(() => {
                if (this.progress < 90) {
                    // Slower progress as it approaches 90%
                    const increment = Math.random() * (10 - this.progress / 10);
                    this.progress += increment;
                    this.updatePercentage(Math.floor(this.progress));
                }
            }, 100);
        },

        /**
         * Update percentage display
         */
        updatePercentage: function(percent) {
            if (this.percentageElement) {
                this.percentageElement.textContent = `${percent}%`;
            }
        },

        /**
         * Hide preloader
         */
        hide: function(force = false) {
            if (!this.element) return;

            const elapsed = Date.now() - this.startTime;
            const delay = force ? 0 : Math.max(0, config.minDisplayTime - elapsed);

            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
                this.progress = 100;
                this.updatePercentage(100);
            }

            setTimeout(() => {
                // Add fade-out class
                this.element.classList.add('fade-out');

                // Remove from DOM after animation
                setTimeout(() => {
                    if (this.element && this.element.parentNode) {
                        this.element.parentNode.removeChild(this.element);
                    }
                    // Dispatch custom event
                    window.dispatchEvent(new Event('preloaderHidden'));
                }, config.fadeOutDuration);
            }, delay);
        },

        /**
         * Show preloader (for manual control)
         */
        show: function() {
            if (!this.element) return;
            this.element.classList.remove('fade-out');
            this.element.style.display = 'flex';
            this.progress = 0;
            this.updatePercentage(0);
            if (config.simulateLoading && this.percentageElement) {
                this.simulateProgress();
            }
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => Preloader.init());
    } else {
        Preloader.init();
    }

    // Expose to window for manual control
    window.Preloader = Preloader;

})();

/**
 * Usage Examples:
 * 
 * 1. Manual show/hide:
 *    window.Preloader.show();
 *    window.Preloader.hide();
 * 
 * 2. Listen for preloader hidden event:
 *    window.addEventListener('preloaderHidden', function() {
 *        console.log('Preloader has been hidden');
 *    });
 * 
 * 3. Show preloader before AJAX request:
 *    window.Preloader.show();
 *    fetch('/api/data')
 *        .then(() => window.Preloader.hide());
 */

