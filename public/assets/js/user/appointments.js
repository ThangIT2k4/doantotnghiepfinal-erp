// Appointments Page JavaScript
// Global variables
let currentAppointmentId = null;
let appointmentCards = [];
let filterTabs = [];
let searchInput = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Appointments page loaded');
    
    // Wait for Bootstrap to be available
    if (typeof bootstrap === 'undefined') {
        console.warn('Bootstrap not loaded, retrying...');
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined') {
                initializeAppointments();
            } else {
                console.error('Bootstrap failed to load');
            }
        }, 1000);
        return;
    }
    
    initializeAppointments();
});

// Initialize appointments functionality
function initializeAppointments() {
    console.log('Initializing appointments functionality');
    
    // Initialize elements
    filterTabs = document.querySelectorAll('.filter-tab');
    searchInput = document.getElementById('searchInput');
    appointmentCards = document.querySelectorAll('.appointment-card');
    
    console.log('Elements found:', {
        filterTabs: filterTabs.length,
        searchInput: searchInput ? 'Yes' : 'No',
        appointmentCards: appointmentCards.length
    });
    
    // Initialize filter functionality
    initializeFilters();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize appointment actions
    initializeAppointmentActions();
    
    // Show welcome notification
    if (appointmentCards.length > 0) {
        window.Notify?.success(
            `Bạn có ${appointmentCards.length} lịch hẹn`,
            'Chào mừng trở lại!'
        );
    }
}

// Initialize filter functionality
function initializeFilters() {
    if (filterTabs.length > 0) {
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const status = this.dataset.status;
                console.log('Filter tab clicked:', status);
                
                // Update active tab
                filterTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Filter cards
                filterCards(status);
            });
        });
    }
}

// Initialize search functionality with debounce
let searchTimeout = null;
function initializeSearch() {
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            const activeTab = document.querySelector('.filter-tab.active');
            const currentStatus = activeTab ? activeTab.dataset.status : 'all';
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Debounce search (wait 500ms after user stops typing)
            searchTimeout = setTimeout(() => {
                console.log('Search triggered:', { searchTerm, currentStatus });
                filterCards(currentStatus, searchTerm);
            }, 500);
        });
    }
}

// Initialize appointment actions
function initializeAppointmentActions() {
    // Add event listeners for cancel buttons
    document.addEventListener('click', function(e) {
        const cancelButton = e.target.closest('.btn-outline-danger');
        if (cancelButton && cancelButton.textContent.includes('Hủy lịch')) {
            e.preventDefault();
            e.stopPropagation();
            
            const id = cancelButton.getAttribute('data-id') || 
                      cancelButton.getAttribute('onclick')?.match(/\d+/)?.[0];
            
            if (id) {
                console.log('Cancel button clicked, ID:', id);
                cancelAppointment(id);
            }
        }
    });
}

// Filter cards function with AJAX
function filterCards(status, searchTerm = '') {
    // Show loading state
    showLoadingState();
    
    // Get appointments route
    const appointmentsRoute = window.appointmentsRoute || '/tenant/appointments';
    
    // Build query parameters
    const params = new URLSearchParams();
    if (status && status !== 'all') {
        params.append('status', status);
    }
    if (searchTerm) {
        params.append('search', searchTerm);
    }
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Make AJAX request
    fetch(`${appointmentsRoute}?${params.toString()}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken || ''
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update appointments list
            updateAppointmentsList(data.viewings);
            
            // Update statistics
            updateStatistics(data.stats);
            
            // Hide loading state
            hideLoadingState();
        } else {
            throw new Error(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Error filtering appointments:', error);
        hideLoadingState();
        window.Notify?.error('Có lỗi xảy ra khi lọc lịch hẹn. Vui lòng thử lại.');
    });
}

// Show loading state
function showLoadingState() {
    const appointmentsList = document.querySelector('.appointments-list');
    if (appointmentsList) {
        appointmentsList.innerHTML = `
            <div class="loading-state text-center py-5">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="mt-3 text-muted">Đang tải dữ liệu...</p>
            </div>
        `;
    }
}

// Hide loading state
function hideLoadingState() {
    const loadingState = document.querySelector('.loading-state');
    if (loadingState) {
        loadingState.remove();
    }
}

// Update appointments list with new data
function updateAppointmentsList(viewings) {
    const appointmentsList = document.querySelector('.appointments-list');
    if (!appointmentsList) return;
    
    if (viewings.length === 0) {
        appointmentsList.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3>Không có lịch hẹn nào</h3>
                <p>Không tìm thấy lịch hẹn phù hợp với bộ lọc của bạn.</p>
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Tìm phòng ngay
                </a>
            </div>
        `;
        return;
    }
    
    // Generate HTML for each viewing
    let html = '';
    viewings.forEach(viewing => {
        html += generateAppointmentCard(viewing);
    });
    
    appointmentsList.innerHTML = html;
    
    // Re-initialize appointment cards
    appointmentCards = document.querySelectorAll('.appointment-card');
    
    // Re-initialize appointment actions
    initializeAppointmentActions();
}

// Generate appointment card HTML
function generateAppointmentCard(viewing) {
    const statusConfig = {
        'requested': { icon: 'fa-clock', text: 'Chờ xác nhận', class: 'requested' },
        'confirmed': { icon: 'fa-check-circle', text: 'Đã xác nhận', class: 'confirmed' },
        'done': { icon: 'fa-calendar-check', text: 'Đã hoàn thành', class: 'done' },
        'no_show': { icon: 'fa-user-times', text: 'Không đến', class: 'no_show' },
        'cancelled': { icon: 'fa-times-circle', text: 'Đã hủy', class: 'cancelled' }
    };
    
    const statusInfo = statusConfig[viewing.status] || { icon: 'fa-question', text: 'Không xác định', class: 'unknown' };
    
    // Calculate end time (add 1 hour)
    // Parse schedule time from format "HH:mm"
    let endTimeStr = '';
    if (viewing.schedule_time) {
        const [hours, minutes] = viewing.schedule_time.split(':').map(Number);
        const endHour = (hours + 1) % 24;
        endTimeStr = `${String(endHour).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    } else {
        endTimeStr = '--:--';
    }
    
    // Generate actions based on status
    let actionsHtml = '';
    if (viewing.status === 'requested') {
        actionsHtml = `
            <a href="/tenant/appointments/${viewing.id}/edit" class="btn btn-outline-warning">
                <i class="fas fa-edit"></i> Sửa lịch
            </a>
            <button class="btn btn-outline-danger" onclick="cancelAppointment(${viewing.id})" data-id="${viewing.id}">
                <i class="fas fa-times"></i> Hủy lịch
            </button>
            <a href="/tenant/appointments/${viewing.id}" class="btn btn-outline-primary">
                <i class="fas fa-eye"></i> Xem chi tiết
            </a>
            ${viewing.agent && viewing.agent.phone ? `
                <a href="tel:${viewing.agent.phone}" class="btn btn-success">
                    <i class="fas fa-phone"></i> Gọi điện
                </a>
            ` : ''}
        `;
    } else if (viewing.status === 'confirmed') {
        actionsHtml = `
            <a href="/tenant/appointments/${viewing.id}" class="btn btn-outline-primary">
                <i class="fas fa-eye"></i> Xem chi tiết
            </a>
            ${viewing.agent && viewing.agent.phone ? `
                <a href="tel:${viewing.agent.phone}" class="btn btn-success">
                    <i class="fas fa-phone"></i> Gọi điện
                </a>
            ` : ''}
        `;
    } else if (viewing.status === 'done') {
        actionsHtml = `
            <button class="btn btn-outline-primary" onclick="rateProperty(${viewing.id})">
                <i class="fas fa-star"></i> Đánh giá
            </button>
            ${viewing.unit ? `
                <a href="/tenant/deposit/${viewing.unit.id}" class="btn btn-outline-success">
                    <i class="fas fa-home"></i> Thuê phòng
                </a>
            ` : ''}
            ${viewing.agent && viewing.agent.phone ? `
                <a href="tel:${viewing.agent.phone}" class="btn btn-outline-secondary">
                    <i class="fas fa-phone"></i> Gọi lại
                </a>
            ` : ''}
        `;
    } else if (viewing.status === 'cancelled') {
        actionsHtml = `
            <a href="/tenant/appointments/${viewing.id}" class="btn btn-outline-primary">
                <i class="fas fa-eye"></i> Xem chi tiết
            </a>
            ${viewing.property ? `
                <a href="/properties/${viewing.property.id}" class="btn btn-outline-info">
                    <i class="fas fa-redo"></i> Đặt lại
                </a>
            ` : ''}
        `;
    }
    
    // Generate address HTML
    let addressHtml = '';
    if (viewing.property.new_address && viewing.property.new_address !== 'Chưa có địa chỉ mới') {
        addressHtml += `
            <p class="mb-1">
                <i class="fas fa-map-marker-alt text-success"></i>
                <strong>Địa chỉ mới (2025):</strong> ${viewing.property.new_address}
            </p>
        `;
    }
    if (viewing.property.old_address && viewing.property.old_address !== 'Chưa có địa chỉ cũ') {
        addressHtml += `
            <p class="mb-0">
                <i class="fas fa-map-marker-alt text-warning"></i>
                <strong>Địa chỉ cũ:</strong> ${viewing.property.old_address}
            </p>
        `;
    }
    if (!addressHtml) {
        addressHtml = `
            <p class="mb-0">
                <i class="fas fa-map-marker-alt text-muted"></i>
                Không có địa chỉ
            </p>
        `;
    }
    
    // Generate unit details HTML
    let unitDetailsHtml = '';
    if (viewing.unit) {
        unitDetailsHtml = `
            <span class="detail">
                <i class="fas fa-expand-arrows-alt"></i>
                ${viewing.unit.area_m2}m²
            </span>
            <span class="detail">
                <i class="fas fa-users"></i>
                ${viewing.unit.max_occupancy} người
            </span>
            <span class="detail price">
                <i class="fas fa-money-bill-wave"></i>
                ${new Intl.NumberFormat('vi-VN').format(viewing.unit.base_rent)} VNĐ/tháng
            </span>
        `;
    } else {
        unitDetailsHtml = `
            <span class="detail">
                <i class="fas fa-building"></i>
                Xem toàn bộ tòa nhà
            </span>
        `;
    }
    
    return `
        <div class="appointment-card" data-status="${viewing.status}" data-id="${viewing.id}">
            <div class="appointment-status ${viewing.status}">
                <i class="fas ${statusInfo.icon}"></i>
                <span>${statusInfo.text}</span>
            </div>
            <div class="appointment-content">
                <div class="row">
                    <div class="col-md-3">
                        <div class="property-image">
                            <img src="${viewing.property.image}" alt="${viewing.property.name}">
                            ${viewing.unit ? `
                                <div class="property-badges">
                                    <span class="badge unit">${viewing.unit.code}</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="property-info">
                            <h4 class="property-title">${viewing.property.name}</h4>
                            <div class="property-location">
                                ${addressHtml}
                            </div>
                            <div class="property-details">
                                ${unitDetailsHtml}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="appointment-info">
                            <div class="appointment-time">
                                <i class="fas fa-calendar"></i>
                                <div>
                                    <strong>${viewing.schedule_date}</strong>
                                    <span>${viewing.schedule_time} - ${endTimeStr}</span>
                                </div>
                            </div>
                            ${viewing.agent ? `
                                <div class="appointment-contact">
                                    <i class="fas fa-user"></i>
                                    <div>
                                        <strong>${viewing.agent.name}</strong>
                                        <span>${viewing.agent.phone}</span>
                                    </div>
                                </div>
                            ` : ''}
                            ${viewing.note ? `
                                <div class="appointment-note">
                                    <i class="fas fa-sticky-note"></i>
                                    <div>
                                        <strong>Ghi chú:</strong>
                                        <span>${viewing.note}</span>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
            <div class="appointment-actions">
                ${actionsHtml}
            </div>
        </div>
    `;
}

// Update statistics
function updateStatistics(stats) {
    // Update pending count
    const pendingElement = document.querySelector('.stat-card .stat-content h3');
    if (pendingElement && document.querySelector('.stat-icon.pending')) {
        const pendingCard = document.querySelector('.stat-icon.pending').closest('.stat-card');
        if (pendingCard) {
            const h3 = pendingCard.querySelector('.stat-content h3');
            if (h3) h3.textContent = stats.pending || 0;
        }
    }
    
    // Update confirmed count
    const confirmedCard = document.querySelector('.stat-icon.confirmed')?.closest('.stat-card');
    if (confirmedCard) {
        const h3 = confirmedCard.querySelector('.stat-content h3');
        if (h3) h3.textContent = stats.confirmed || 0;
    }
    
    // Update cancelled count
    const cancelledCard = document.querySelector('.stat-icon.cancelled')?.closest('.stat-card');
    if (cancelledCard) {
        const h3 = cancelledCard.querySelector('.stat-content h3');
        if (h3) h3.textContent = stats.cancelled || 0;
    }
}

// Cancel appointment function
function cancelAppointment(id) {
    console.log('cancelAppointment called with id:', id);
    
    if (!id) {
        window.Notify?.error('Không tìm thấy ID lịch hẹn');
        return;
    }
    
    currentAppointmentId = id;
    
    // Use notification system for confirmation
    window.Notify?.confirm({
        title: 'Xác nhận hủy lịch hẹn',
        message: 'Bạn có chắc chắn muốn hủy lịch hẹn này không?',
        details: 'Lịch hẹn sẽ được hủy và không thể khôi phục.',
        type: 'warning',
        confirmText: 'Hủy lịch',
        cancelText: 'Không',
        onConfirm: () => {
            showCancelReasonModal();
        }
    });
}

// Show cancel reason modal
function showCancelReasonModal() {
    // Remove existing modal if any
    const existingModal = document.getElementById('cancelReasonModal');
    if (existingModal) {
        const modalInstance = bootstrap.Modal.getInstance(existingModal);
        if (modalInstance) {
            modalInstance.dispose();
        }
        existingModal.remove();
    }
    
    // Create dynamic modal for cancel reason
    const modalHtml = `
        <div class="modal fade" id="cancelReasonModal" tabindex="-1" aria-labelledby="cancelReasonModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelReasonModalLabel">
                            <i class="fas fa-times-circle text-danger"></i>
                            Hủy lịch hẹn
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Vui lòng cho biết lý do hủy lịch hẹn (tùy chọn):</p>
                        <div class="mb-3">
                            <textarea class="form-control" id="cancelReason" rows="4" 
                                placeholder="Nhập lý do hủy lịch..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Không
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmCancel()">
                            <i class="fas fa-check"></i> Xác nhận hủy
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Wait for DOM to be ready
    setTimeout(() => {
        const modalElement = document.getElementById('cancelReasonModal');
        if (modalElement) {
            // Show modal
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modal.show();
            
            // Focus on textarea
            setTimeout(() => {
                document.getElementById('cancelReason')?.focus();
            }, 300);
        }
    }, 100);
}

// Confirm cancel function
function confirmCancel() {
    console.log('confirmCancel called, currentAppointmentId:', currentAppointmentId);
    
    if (!currentAppointmentId) {
        window.Notify?.error('Không tìm thấy ID lịch hẹn');
        return;
    }
    
    const reason = document.getElementById('cancelReason')?.value || '';
    console.log('Cancel reason:', reason);
    
    // Show loading state
    const confirmBtn = document.querySelector('#cancelReasonModal .btn-danger');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    confirmBtn.disabled = true;
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        window.Notify?.error('Không tìm thấy CSRF token');
        resetButton(confirmBtn, originalText);
        return;
    }
    
    const token = csrfToken.getAttribute('content');
    if (!token) {
        window.Notify?.error('CSRF token trống');
        resetButton(confirmBtn, originalText);
        return;
    }
    
    console.log('Making API call to:', `/tenant/appointments/${currentAppointmentId}/cancel`);
    
    // Make API call
    fetch(`/tenant/appointments/${currentAppointmentId}/cancel`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            reason: reason
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            if (response.status === 419) {
                throw new Error('CSRF token mismatch. Vui lòng refresh trang và thử lại.');
            } else if (response.status === 401) {
                throw new Error('Bạn chưa đăng nhập. Vui lòng đăng nhập lại.');
            } else if (response.status === 403) {
                throw new Error('Bạn không có quyền thực hiện hành động này.');
            } else if (response.status === 404) {
                throw new Error('Không tìm thấy lịch hẹn này.');
            } else {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Close modal
            cleanupModal('cancelReasonModal');
            
            // Show success notification
            window.Notify?.success('Đã hủy lịch hẹn thành công!');
            
            // Reload page after delay
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            window.Notify?.error(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.Notify?.error(error.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
    })
    .finally(() => {
        resetButton(confirmBtn, originalText);
        currentAppointmentId = null;
    });
}

// Helper function to reset button
function resetButton(button, originalText) {
    if (button) {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

// Helper function to cleanup modal
function cleanupModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            modalInstance.dispose();
        }
        modal.remove();
    }
}

// Edit appointment function
function editAppointment(id) {
    console.log('editAppointment called with id:', id);
    window.location.href = `/tenant/appointments/${id}/edit`;
}

// Rate property function
function rateProperty(id) {
    console.log('rateProperty called with id:', id);
    currentAppointmentId = id;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('ratingModal');
    if (existingModal) {
        const modalInstance = bootstrap.Modal.getInstance(existingModal);
        if (modalInstance) {
            modalInstance.dispose();
        }
        existingModal.remove();
    }
    
    // Create rating modal
    const modalHtml = `
        <div class="modal fade" id="ratingModal" tabindex="-1" aria-labelledby="ratingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ratingModalLabel">
                            <i class="fas fa-star text-warning"></i>
                            Đánh giá phòng trọ
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="rating-section mb-4">
                            <label class="form-label fw-bold">Đánh giá tổng thể</label>
                            <div class="star-rating">
                                <i class="fas fa-star" data-rating="1"></i>
                                <i class="fas fa-star" data-rating="2"></i>
                                <i class="fas fa-star" data-rating="3"></i>
                                <i class="fas fa-star" data-rating="4"></i>
                                <i class="fas fa-star" data-rating="5"></i>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="reviewText" class="form-label fw-bold">Nhận xét</label>
                            <textarea class="form-control" id="reviewText" rows="4" 
                                placeholder="Chia sẻ trải nghiệm của bạn..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                        <button type="button" class="btn btn-primary" onclick="submitRating()">
                            <i class="fas fa-paper-plane"></i> Gửi đánh giá
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Wait for DOM to be ready
    setTimeout(() => {
        const modalElement = document.getElementById('ratingModal');
        if (modalElement) {
            // Show modal
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            modal.show();
            
            // Initialize star rating
            initializeStarRating();
        }
    }, 100);
}

// Initialize star rating
function initializeStarRating() {
    const stars = document.querySelectorAll('#ratingModal .star-rating .fas.fa-star');
    const starRatingContainer = document.querySelector('#ratingModal .star-rating');
    
    if (stars.length > 0) {
        stars.forEach((star, index) => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add('active');
                } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.style.color = '#ffc107';
                } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
        
        if (starRatingContainer) {
            starRatingContainer.addEventListener('mouseleave', function() {
                const stars = this.querySelectorAll('.fas.fa-star');
                stars.forEach(star => {
                    if (star.classList.contains('active')) {
                        star.style.color = '#ffc107';
                } else {
                        star.style.color = '#ddd';
                    }
                });
            });
        }
    }
}

// Submit rating function
function submitRating() {
    if (!currentAppointmentId) return;
    
    const rating = document.querySelector('#ratingModal .star-rating .fas.fa-star.active')?.dataset.rating || 0;
    const review = document.getElementById('reviewText')?.value || '';
    
    if (rating == 0) {
        window.Notify?.warning('Vui lòng chọn đánh giá');
        return;
    }
    
    // Show loading state
    const submitBtn = document.querySelector('#ratingModal .btn-primary');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
    submitBtn.disabled = true;
    
    // Simulate API call (replace with actual implementation)
    setTimeout(() => {
        // Close modal
        cleanupModal('ratingModal');
        
        // Show success notification
        window.Notify?.success('Cảm ơn bạn đã đánh giá!');
        
        // Reset
        currentAppointmentId = null;
        resetButton(submitBtn, originalText);
    }, 1500);
}

// Reschedule appointment function
function rescheduleAppointment(id) {
    console.log('rescheduleAppointment called with id:', id);
    window.Notify?.info('Chức năng đổi lịch đang được phát triển');
}

// Store appointments route for AJAX calls
window.appointmentsRoute = window.appointmentsRoute || '/tenant/appointments';