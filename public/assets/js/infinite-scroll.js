/**
 * Infinite Scroll - Load more content as user scrolls
 * Alternative to pagination for better UX
 */

(function() {
    'use strict';

    class InfiniteScroll {
        constructor(options = {}) {
            this.container = options.container || document.querySelector('[data-infinite-scroll]');
            this.loadMoreUrl = options.loadMoreUrl || this.container?.dataset.infiniteScroll;
            this.page = parseInt(options.page || this.container?.dataset.page || 1);
            this.loading = false;
            this.hasMore = true;
            this.loadingClass = options.loadingClass || 'loading';
            this.sentinelSelector = options.sentinelSelector || '#infinite-scroll-sentinel';
            
            if (!this.container || !this.loadMoreUrl) {
                console.warn('InfiniteScroll: Container or loadMoreUrl not found');
                return;
            }
            
            this.init();
        }

        init() {
            // Create sentinel element
            this.createSentinel();
            
            // Setup intersection observer
            this.setupObserver();
            
            // Handle manual load more button
            this.setupLoadMoreButton();
        }

        createSentinel() {
            // Remove existing sentinel if any
            const existing = this.container.querySelector(this.sentinelSelector);
            if (existing) {
                existing.remove();
            }
            
            const sentinel = document.createElement('div');
            sentinel.id = this.sentinelSelector.replace('#', '');
            sentinel.className = 'infinite-scroll-sentinel';
            sentinel.style.height = '20px';
            sentinel.style.width = '100%';
            
            // Find table body or container to append to
            const tbody = this.container.querySelector('tbody');
            if (tbody) {
                tbody.parentNode.appendChild(sentinel);
            } else {
                this.container.appendChild(sentinel);
            }
            
            this.sentinel = sentinel;
        }

        setupObserver() {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !this.loading && this.hasMore) {
                        this.loadMore();
                    }
                });
            }, {
                rootMargin: '200px' // Start loading 200px before reaching sentinel
            });
            
            this.observer.observe(this.sentinel);
        }

        setupLoadMoreButton() {
            const button = this.container.querySelector('[data-load-more]');
            if (button) {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.loadMore();
                });
            }
        }

        async loadMore() {
            if (this.loading || !this.hasMore) {
                return;
            }
            
            this.loading = true;
            this.container.classList.add(this.loadingClass);
            
            const loader = this.showLoader();
            
            try {
                const url = new URL(this.loadMoreUrl, window.location.href);
                url.searchParams.set('page', this.page + 1);
                url.searchParams.set('ajax', '1');
                
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Append new items
                    this.appendContent(data);
                    
                    // Update stats if provided
                    if (data.stats_html) {
                        this.updateStats(data.stats_html);
                    }
                    
                    this.page++;
                    
                    // Check if there's more data
                    this.hasMore = this.checkHasMore(data);
                    
                    // Update URL without reload
                    if (data.url) {
                        window.history.pushState({}, '', data.url);
                    }
                } else {
                    console.error('Failed to load more:', data.message || 'Unknown error');
                    this.hasMore = false;
                }
            } catch (error) {
                console.error('Error loading more:', error);
                this.showError('Có lỗi xảy ra khi tải thêm dữ liệu. Vui lòng thử lại.');
            } finally {
                this.loading = false;
                this.container.classList.remove(this.loadingClass);
                this.hideLoader(loader);
            }
        }

        appendContent(data) {
            if (data.table_html) {
                const temp = document.createElement('div');
                temp.innerHTML = data.table_html;
                
                // Find table body in new content
                const newTbody = temp.querySelector('tbody');
                const currentTbody = this.container.querySelector('tbody');
                
                if (newTbody && currentTbody) {
                    // Append rows
                    const rows = Array.from(newTbody.querySelectorAll('tr'));
                    rows.forEach(row => {
                        currentTbody.appendChild(row);
                    });
                } else {
                    // Fallback: append entire content
                    const content = temp.querySelector('[data-infinite-scroll-content]') || temp.firstElementChild;
                    if (content) {
                        this.container.appendChild(content);
                    }
                }
            }
        }

        updateStats(statsHtml) {
            const statsContainer = document.querySelector('#stats-container, .statistics-cards');
            if (statsContainer && statsHtml) {
                statsContainer.innerHTML = statsHtml;
            }
        }

        checkHasMore(data) {
            // Check if there are more items
            if (data.has_more !== undefined) {
                return data.has_more;
            }
            
            // Check if we got any new items
            if (data.table_html) {
                const temp = document.createElement('div');
                temp.innerHTML = data.table_html;
                const rows = temp.querySelectorAll('tbody tr, [data-item]');
                return rows.length > 0;
            }
            
            return false;
        }

        showLoader() {
            const loader = document.createElement('div');
            loader.className = 'infinite-scroll-loader text-center py-4';
            loader.innerHTML = `
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="text-muted mt-2 mb-0">Đang tải thêm dữ liệu...</p>
            `;
            
            // Insert before sentinel
            if (this.sentinel && this.sentinel.parentNode) {
                this.sentinel.parentNode.insertBefore(loader, this.sentinel);
            } else {
                this.container.appendChild(loader);
            }
            
            return loader;
        }

        hideLoader(loader) {
            if (loader && loader.parentNode) {
                loader.remove();
            }
        }

        showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-warning alert-dismissible fade show';
            errorDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            if (this.sentinel && this.sentinel.parentNode) {
                this.sentinel.parentNode.insertBefore(errorDiv, this.sentinel);
            } else {
                this.container.appendChild(errorDiv);
            }
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.remove();
                }
            }, 5000);
        }

        destroy() {
            if (this.observer) {
                this.observer.disconnect();
            }
            if (this.sentinel && this.sentinel.parentNode) {
                this.sentinel.remove();
            }
        }
    }

    // Auto-initialize
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-infinite-scroll]').forEach(container => {
            new InfiniteScroll({
                container: container,
                loadMoreUrl: container.dataset.infiniteScroll,
                page: parseInt(container.dataset.page || 1)
            });
        });
    });

    // Export for manual initialization
    window.InfiniteScroll = InfiniteScroll;
})();

