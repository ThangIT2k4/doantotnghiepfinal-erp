/**
 * Prefetch Links - Load pages before user clicks
 * Improves perceived performance by loading content in advance
 */

(function() {
    'use strict';

    class LinkPrefetcher {
        constructor() {
            this.prefetchedUrls = new Set();
            this.prefetchDelay = 100; // Delay before prefetch (ms)
            this.init();
        }

        init() {
            // Prefetch on hover
            this.setupHoverPrefetch();
            
            // Prefetch links in viewport
            this.setupViewportPrefetch();
            
            // Prefetch pagination links
            this.setupPaginationPrefetch();
        }

        setupHoverPrefetch() {
            document.addEventListener('mouseover', (e) => {
                const link = e.target.closest('a[data-prefetch], .pagination a, .pagination-controls a');
                if (link && link.href && !this.prefetchedUrls.has(link.href)) {
                    clearTimeout(link.prefetchTimeout);
                    link.prefetchTimeout = setTimeout(() => {
                        this.prefetch(link.href);
                    }, this.prefetchDelay);
                }
            });
        }

        setupViewportPrefetch() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const link = entry.target;
                        if (link.href && !this.prefetchedUrls.has(link.href)) {
                            this.prefetch(link.href);
                        }
                    }
                });
            }, {
                rootMargin: '200px' // Start prefetching 200px before link enters viewport
            });

            // Observe all prefetchable links
            document.querySelectorAll('a[data-prefetch], .pagination a, .pagination-controls a').forEach(link => {
                observer.observe(link);
            });
        }

        setupPaginationPrefetch() {
            // Prefetch next page when user is near bottom
            const pagination = document.querySelector('.pagination');
            if (pagination) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const nextLink = pagination.querySelector('.pagination .page-item.active + .page-item a');
                            if (nextLink && !this.prefetchedUrls.has(nextLink.href)) {
                                this.prefetch(nextLink.href);
                            }
                        }
                    });
                }, {
                    rootMargin: '300px'
                });
                
                observer.observe(pagination);
            }
        }

        prefetch(url) {
            if (this.prefetchedUrls.has(url)) {
                return;
            }

            // Use link prefetch for same-origin
            if (this.isSameOrigin(url)) {
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = url;
                document.head.appendChild(link);
                this.prefetchedUrls.add(url);
            } else {
                // For cross-origin or AJAX prefetch
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    cache: 'force-cache'
                }).catch(() => {
                    // Silently fail - prefetch is best effort
                });
                this.prefetchedUrls.add(url);
            }
        }

        isSameOrigin(url) {
            try {
                const linkUrl = new URL(url, window.location.href);
                return linkUrl.origin === window.location.origin;
            } catch {
                return false;
            }
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new LinkPrefetcher();
        });
    } else {
        new LinkPrefetcher();
    }
})();

