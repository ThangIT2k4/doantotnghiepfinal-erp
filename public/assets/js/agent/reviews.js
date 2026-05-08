/**
 * Agent Reviews Management JavaScript
 * Handles tab synchronization, AJAX requests, and real-time updates
 */

class AgentReviewsManager {
    constructor() {
        this.currentTab = 'all';
        this.currentReviewId = null;
        this.currentReplyId = null;
        this.refreshInterval = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.startAutoRefresh();
        
        // Check if we're on the show page
        const reviewIdMatch = window.location.pathname.match(/\/agent\/reviews\/(\d+)/);
        if (reviewIdMatch) {
            this.currentReviewId = reviewIdMatch[1];
        } else {
            this.loadTabContent('all');
        }
    }

    bindEvents() {
        // Tab switching
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                const target = e.target.getAttribute('data-bs-target');
                this.currentTab = target.replace('#', '');
                this.loadTabContent(this.currentTab);
            });
        });

        // Search functionality
        document.querySelectorAll('[id^="search-"]').forEach(input => {
            input.addEventListener('input', this.debounce((e) => {
                const tab = e.target.id.replace('search-', '');
                this.loadTabContent(tab);
            }, 500));
        });

        // Rating filter
        document.querySelectorAll('[id^="rating-filter-"]').forEach(select => {
            select.addEventListener('change', (e) => {
                const tab = e.target.id.replace('rating-filter-', '');
                this.loadTabContent(tab);
            });
        });

        // Reply modal events
        document.getElementById('submit-reply')?.addEventListener('click', () => {
            this.submitReply();
        });

        document.getElementById('submit-edit-reply')?.addEventListener('click', () => {
            this.submitEditReply();
        });

        // Close modals
        document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.clearModals();
            });
        });
    }

    startAutoRefresh() {
        // Auto refresh every 30 seconds
        this.refreshInterval = setInterval(() => {
            this.loadTabContent(this.currentTab, true);
        }, 30000);
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    async loadTabContent(tab, silent = false) {
        if (!silent) {
            this.showLoading(tab);
        }

        try {
            const params = new URLSearchParams();
            params.append('status', this.getStatusFromTab(tab));
            params.append('rating', this.getRatingFilter(tab));
            params.append('search', this.getSearchTerm(tab));

            const response = await fetch(`/agent/api/reviews/data?${params}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            this.updateTabContent(tab, data);
            this.updateStats(data.stats);

        } catch (error) {
            console.error('Error loading tab content:', error);
            this.showError(tab, 'Không thể tải dữ liệu. Vui lòng thử lại.');
        }
    }

    getStatusFromTab(tab) {
        const statusMap = {
            'all': 'all',
            'pending-reply': 'pending_reply',
            'replied': 'replied',
            'recent': 'recent'
        };
        return statusMap[tab] || 'all';
    }

    getRatingFilter(tab) {
        const select = document.getElementById(`rating-filter-${tab}`);
        return select ? select.value : '';
    }

    getSearchTerm(tab) {
        const input = document.getElementById(`search-${tab}`);
        return input ? input.value : '';
    }

    showLoading(tab) {
        const content = document.getElementById(`${tab}-content`);
        if (content) {
            content.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2 text-muted">Đang tải dữ liệu...</p>
                </div>
            `;
        }
    }

    showError(tab, message) {
        const content = document.getElementById(`${tab}-content`);
        if (content) {
            content.innerHTML = `
                <div class="text-center py-4">
                    <i class="mdi mdi-alert-circle text-danger" style="font-size: 2rem;"></i>
                    <p class="mt-2 text-danger">${message}</p>
                    <button class="btn btn-primary btn-sm" onclick="agentReviewsManager.loadTabContent('${tab}')">
                        <i class="mdi mdi-refresh me-1"></i>Thử lại
                    </button>
                </div>
            `;
        }
    }

    updateTabContent(tab, data) {
        const content = document.getElementById(`${tab}-content`);
        if (!content) return;

        if (data.reviews && data.reviews.length > 0) {
            content.innerHTML = this.generateReviewsHTML(data.reviews);
        } else {
            content.innerHTML = `
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="mdi mdi-comment-outline" style="font-size: 4rem; color: #dee2e6;"></i>
                    </div>
                    <h5 class="text-muted">Chưa có đánh giá nào</h5>
                    <p class="text-muted">Hiện tại chưa có đánh giá nào cho tab này.</p>
                </div>
            `;
        }
    }

    generateReviewsHTML(reviews) {
        return reviews.map(review => `
            <div class="col-12 mb-3">
                <div class="card review-card" data-review-id="${review.id}">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            <a href="/agent/reviews/${review.id}" class="text-decoration-none">
                                                ${review.title}
                                            </a>
                                        </h5>
                                        <div class="text-muted small mb-2">
                                            <i class="mdi mdi-map-marker me-1"></i>
                                            ${review.unit?.property?.name || 'N/A'} - ${review.unit?.code || 'N/A'}
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rating me-3">
                                                ${this.generateStarsHTML(review.overall_rating)}
                                                <span class="ms-1 fw-bold">${review.overall_rating}/5</span>
                                            </div>
                                            ${(review.replies_count || review.replies?.length || 0) > 0 ? 
                                                '<span class="badge bg-success">Đã phản hồi</span>' : 
                                                '<span class="badge bg-warning">Chờ phản hồi</span>'
                                            }
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-muted small">
                                            ${new Date(review.created_at).toLocaleDateString('vi-VN')}
                                        </div>
                                        <div class="text-muted small">
                                            bởi ${review.tenant?.full_name || review.tenant?.name || 'Khách hàng'}
                                        </div>
                                    </div>
                                </div>
                                <div class="review-content mb-3">
                                    <p class="mb-2">${this.truncateText(review.content, 200)}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex flex-column gap-2">
                                    <a href="/agent/reviews/${review.id}" class="btn btn-outline-primary btn-sm">
                                        <i class="mdi mdi-eye me-1"></i>Xem chi tiết
                                    </a>
                                    ${(review.replies_count || review.replies?.length || 0) === 0 ? 
                                        `<button class="btn btn-primary btn-sm" onclick="agentReviewsManager.openReplyModal(${review.id})">
                                            <i class="mdi mdi-reply me-1"></i>Phản hồi
                                        </button>` :
                                        `<button class="btn btn-success btn-sm" onclick="agentReviewsManager.openReplyModal(${review.id})">
                                            <i class="mdi mdi-reply me-1"></i>Thêm phản hồi
                                        </button>`
                                    }
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    generateStarsHTML(rating) {
        let html = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                html += '<i class="mdi mdi-star text-warning"></i>';
            } else if (i - 0.5 <= rating) {
                html += '<i class="mdi mdi-star-half-full text-warning"></i>';
            } else {
                html += '<i class="mdi mdi-star-outline text-muted"></i>';
            }
        }
        return html;
    }

    truncateText(text, length) {
        return text.length > length ? text.substring(0, length) + '...' : text;
    }

    updateStats(stats) {
        // Update statistics cards
        const statElements = {
            'total': document.querySelector('.widget-flat:nth-child(1) h3'),
            'avg_rating': document.querySelector('.widget-flat:nth-child(2) h3'),
            'pending_reply': document.querySelector('.widget-flat:nth-child(3) h3'),
            'replied': document.querySelector('.widget-flat:nth-child(4) h3')
        };

        if (statElements.total) statElements.total.textContent = stats.total;
        if (statElements.avg_rating) statElements.avg_rating.textContent = `${stats.avg_rating}/5`;
        if (statElements.pending_reply) statElements.pending_reply.textContent = stats.pending_reply;
        if (statElements.replied) statElements.replied.textContent = stats.replied;

        // Update badge counts
        const pendingBadge = document.querySelector('#pending-reply-tab .badge');
        if (pendingBadge) {
            if (stats.pending_reply > 0) {
                pendingBadge.textContent = stats.pending_reply;
                pendingBadge.style.display = 'inline';
            } else {
                pendingBadge.style.display = 'none';
            }
        }
    }

    async openReplyModal(reviewId) {
        this.currentReviewId = reviewId;
        
        try {
            // For now, use simple modal content without loading from server
            // This avoids the complex HTML parsing issues
            const modal = document.getElementById('replyModal');
            if (!modal) {
                alert('Modal không tồn tại');
                return;
            }

            // Update modal content with basic info
            const reviewDetails = document.getElementById('review-details');
            if (reviewDetails) {
                reviewDetails.innerHTML = `
                    <div class="border rounded p-3 bg-light">
                        <h6 class="mb-2">Đánh giá #${reviewId}</h6>
                        <div class="row">
                            <div class="col-12">
                                <small class="text-muted">Bạn đang phản hồi đánh giá này</small>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Clear form
            const replyContent = document.getElementById('reply-content');
            if (replyContent) {
                replyContent.value = '';
            }
            
            // Show modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

        } catch (error) {
            console.error('Error opening reply modal:', error);
            alert('Không thể mở modal phản hồi. Vui lòng thử lại.');
        }
    }

    async submitReply() {
        const contentElement = document.getElementById('reply-content');
        if (!contentElement) {
            alert('Không tìm thấy form phản hồi');
            return;
        }
        
        const content = contentElement.value.trim();
        
        if (content.length < 10) {
            alert('Nội dung phản hồi phải có ít nhất 10 ký tự');
            return;
        }
        
        if (content.length > 1000) {
            alert('Nội dung phản hồi không được vượt quá 1000 ký tự');
            return;
        }

        const submitBtn = document.getElementById('submit-reply') || document.querySelector('#reply-form button[type="submit"]');
        if (!submitBtn) {
            alert('Không tìm thấy nút gửi phản hồi');
            return;
        }
        
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i>Đang gửi...';
        submitBtn.disabled = true;

        try {
            const response = await fetch(`/agent/reviews/${this.currentReviewId}/reply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ content })
            });

            const data = await response.json();

            if (data.success) {
                // Close modal if it exists
                const modal = bootstrap.Modal.getInstance(document.getElementById('replyModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Show success message
                this.showToast('success', data.message);
                
                // Clear form
                const replyContent = document.getElementById('reply-content');
                if (replyContent) replyContent.value = '';
                
                // Refresh replies list if on show page
                if (document.getElementById('replies-list')) {
                    this.refreshRepliesList();
                } else {
                    // Refresh current tab if on index page
                    this.loadTabContent(this.currentTab);
                }
                
            } else {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }

        } catch (error) {
            console.error('Error submitting reply:', error);
            alert('Không thể gửi phản hồi: ' + error.message);
        } finally {
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    }

    async editReply(replyId, currentContent) {
        this.currentReplyId = replyId;
        document.getElementById('edit-reply-content').value = currentContent;
        
        const modal = new bootstrap.Modal(document.getElementById('editReplyModal'));
        modal.show();
    }

    async submitEditReply() {
        const content = document.getElementById('edit-reply-content').value.trim();
        
        if (content.length < 10) {
            alert('Nội dung phản hồi phải có ít nhất 10 ký tự');
            return;
        }
        
        if (content.length > 1000) {
            alert('Nội dung phản hồi không được vượt quá 1000 ký tự');
            return;
        }

        const submitBtn = document.getElementById('submit-edit-reply');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i>Đang cập nhật...';
        submitBtn.disabled = true;

        try {
            const response = await fetch(`/agent/reviews/${this.currentReviewId}/replies/${this.currentReplyId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ content })
            });

            const data = await response.json();

            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editReplyModal'));
                modal.hide();
                
                // Show success message
                this.showToast('success', data.message);
                
                // Refresh current tab
                this.loadTabContent(this.currentTab);
                
            } else {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }

        } catch (error) {
            console.error('Error updating reply:', error);
            alert('Không thể cập nhật phản hồi: ' + error.message);
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    async deleteReply(replyId) {
        if (!confirm('Bạn có chắc chắn muốn xóa phản hồi này?')) {
            return;
        }

        try {
            const response = await fetch(`/agent/reviews/${this.currentReviewId}/replies/${replyId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                // Show success message
                this.showToast('success', data.message);
                
                // Refresh current tab
                this.loadTabContent(this.currentTab);
                
            } else {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }

        } catch (error) {
            console.error('Error deleting reply:', error);
            alert('Không thể xóa phản hồi: ' + error.message);
        }
    }

    clearModals() {
        this.currentReplyId = null;
        const replyContent = document.getElementById('reply-content');
        const editReplyContent = document.getElementById('edit-reply-content');
        
        if (replyContent) replyContent.value = '';
        if (editReplyContent) editReplyContent.value = '';
    }

    async refreshRepliesList() {
        try {
            const response = await fetch(`/agent/reviews/${this.currentReviewId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Could not load review details');
            }

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Extract replies list
            const newRepliesList = doc.querySelector('#replies-list');
            if (newRepliesList) {
                document.getElementById('replies-list').innerHTML = newRepliesList.innerHTML;
            }

        } catch (error) {
            console.error('Error refreshing replies list:', error);
        }
    }

    async loadAllReviews() {
        try {
            const params = new URLSearchParams();
            params.append('status', 'all');
            params.append('limit', '1000'); // Load all reviews

            const response = await fetch(`/agent/api/reviews/data?${params}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            this.updateTabContent('all', data);
            this.updateStats(data.stats);
            
            this.showToast('success', `Đã tải ${data.reviews.length} đánh giá`);

        } catch (error) {
            console.error('Error loading all reviews:', error);
            this.showToast('error', 'Không thể tải tất cả đánh giá. Vui lòng thử lại.');
        }
    }

    showToast(type, message) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        // Add to toast container
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }

        toastContainer.appendChild(toast);

        // Show toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        // Remove after hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
}

// Global functions for backward compatibility
function refreshReviews(status) {
    if (window.agentReviewsManager) {
        window.agentReviewsManager.loadTabContent(status);
    }
}

function loadAllReviews() {
    if (window.agentReviewsManager) {
        window.agentReviewsManager.loadAllReviews();
    }
}

function openReplyModal(reviewId) {
    if (window.agentReviewsManager) {
        window.agentReviewsManager.openReplyModal(reviewId);
    }
}

function editReply(replyId, content) {
    if (window.agentReviewsManager) {
        window.agentReviewsManager.editReply(replyId, content);
    }
}

function deleteReply(replyId) {
    if (window.agentReviewsManager) {
        window.agentReviewsManager.deleteReply(replyId);
    }
}

function submitReply(reviewId, content) {
    if (window.agentReviewsManager) {
        window.agentReviewsManager.currentReviewId = reviewId;
        document.getElementById('reply-content').value = content;
        window.agentReviewsManager.submitReply();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.agentReviewsManager = new AgentReviewsManager();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.agentReviewsManager) {
        window.agentReviewsManager.stopAutoRefresh();
    }
});
