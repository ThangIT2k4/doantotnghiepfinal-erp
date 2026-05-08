/**
 * HTMX Event Handlers for Contracts Page
 * Handles active state updates and smooth transitions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Update active states when HTMX swaps content
    document.body.addEventListener('htmx:afterSwap', function(event) {
        if (event.detail.target.id === 'contracts-list-container') {
            updateActiveStates();
        }
    });

    // Update active states when URL changes (browser back/forward)
    document.body.addEventListener('htmx:afterSettle', function(event) {
        updateActiveStates();
    });

    // Update active states when HTMX request completes
    document.body.addEventListener('htmx:afterRequest', function(event) {
        updateActiveStates();
    });

    // Update active states when HTMX updates browser history
    document.body.addEventListener('htmx:afterHistoryUpdate', function(event) {
        updateActiveStates();
    });

    // Update active states on page load
    updateActiveStates();

    // Handle click on stat cards - update immediately
    document.addEventListener('click', function(event) {
        const statCard = event.target.closest('.stat-card-blue[data-filter]');
        if (statCard) {
            const filterValue = statCard.getAttribute('data-filter');
            // Update immediately for better UX
            document.querySelectorAll('.stat-card-blue[data-filter]').forEach(card => {
                if (card.getAttribute('data-filter') === filterValue) {
                    card.classList.add('active-filter');
                } else {
                    card.classList.remove('active-filter');
                }
            });
            // Also update after HTMX completes
            setTimeout(function() {
                updateActiveStates();
            }, 200);
        }

        // Handle click on filter tabs - update immediately
        const filterTab = event.target.closest('.filter-tab-blue[data-status]');
        if (filterTab) {
            const status = filterTab.getAttribute('data-status');
            // Update immediately for better UX
            document.querySelectorAll('.filter-tab-blue[data-status]').forEach(tab => {
                if (tab.getAttribute('data-status') === status) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
            // Update stats cards too
            document.querySelectorAll('.stat-card-blue[data-filter]').forEach(card => {
                if (card.getAttribute('data-filter') === status) {
                    card.classList.add('active-filter');
                } else {
                    card.classList.remove('active-filter');
                }
            });
            // Also update after HTMX completes
            setTimeout(function() {
                updateActiveStates();
            }, 200);
        }
    });
});

/**
 * Update active states for stats cards and filter tabs based on URL params
 */
function updateActiveStates() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status') || 'all';

    // Update stats cards - fix selector to match actual class name
    document.querySelectorAll('.stat-card-blue[data-filter]').forEach(card => {
        const cardFilter = card.getAttribute('data-filter');
        if (cardFilter === status) {
            card.classList.add('active-filter');
        } else {
            card.classList.remove('active-filter');
        }
    });

    // Update filter tabs
    document.querySelectorAll('.filter-tab-blue').forEach(tab => {
        const tabStatus = tab.getAttribute('data-status') || 'all';
        if (tabStatus === status) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });
}

