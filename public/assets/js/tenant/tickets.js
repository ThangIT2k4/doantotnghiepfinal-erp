/**
 * Ticket Module for QLPhongTro
 * Handles all ticket-related frontend interactions
 * Modern ES6+ module pattern with clean architecture
 */

const TicketModule = (function() {
    'use strict';
    
    // Private variables
    let currentPage = null;
    let ticketId = null;
    let hasUnsavedChanges = false;
    let draftTimer = null;
    
    // Configuration
    const CONFIG = {
        DRAFT_SAVE_DELAY: 2000,
        DRAFT_VALID_HOURS: 24,
        NOTIFICATION_DURATION: {
            SUCCESS: 5000,
            ERROR: 8000,
            WARNING: 6000,
            INFO: 4000
        }
    };
    
    // ========================================
    // UTILITY FUNCTIONS
    // ========================================
    
    /**
     * Wait for Notify system to be ready
     */
    function waitForNotify(callback) {
        if (window.Notify && typeof window.Notify.confirmDelete === 'function' && 
            typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            callback();
        } else {
            setTimeout(() => waitForNotify(callback), 100);
        }
    }
    
    /**
     * Debounce function for performance
     */
    function debounce(func, wait) {
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
    
    /**
     * Show loading state on button
     */
    function setButtonLoading(button, loading = true, text = '') {
        if (!button) return;
        
        if (loading) {
            button.disabled = true;
            button.classList.add('btn-loading');
            if (text) {
                button.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${text}`;
            }
        } else {
            button.disabled = false;
            button.classList.remove('btn-loading');
        }
    }
    
    /**
     * Validate form fields
     */
    function validateForm(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;
        
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    }
    
    // ========================================
    // DRAFT MANAGEMENT
    // ========================================
    
    /**
     * Save draft to localStorage
     */
    function saveDraft(data, key) {
        try {
            const draft = {
                ...data,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem(key, JSON.stringify(draft));
        } catch (e) {
            console.warn('Could not save draft:', e);
        }
    }
    
    /**
     * Load draft from localStorage
     */
    function loadDraft(key) {
        try {
            const saved = localStorage.getItem(key);
            return saved ? JSON.parse(saved) : null;
        } catch (e) {
            console.warn('Could not load draft:', e);
            return null;
        }
    }
    
    /**
     * Clear draft from localStorage
     */
    function clearDraft(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {
            console.warn('Could not clear draft:', e);
        }
    }
    
    /**
     * Check if draft is still valid
     */
    function isDraftValid(draft) {
        if (!draft || !draft.timestamp) return false;
        
        const draftDate = new Date(draft.timestamp);
        const now = new Date();
        const diffHours = (now - draftDate) / (1000 * 60 * 60);
        
        return diffHours < CONFIG.DRAFT_VALID_HOURS;
    }
    
    /**
     * Show draft recovery popup
     */
    function showDraftRecovery(draft, key, restoreCallback) {
        if (!window.Notify) return;
        
        const draftDate = new Date(draft.timestamp);
        
        window.Notify.toast({
            title: 'Bản nháp đã lưu',
            message: `Có bản nháp từ ${draftDate.toLocaleString()}. Bạn có muốn khôi phục?`,
            type: 'info',
            duration: 10000,
            actions: [{
                text: 'Khôi phục',
                type: 'primary',
                action: 'restore',
                handler: function() {
                    restoreCallback(draft);
                    clearDraft(key);
                    window.Notify.success('Đã khôi phục bản nháp!');
                }
            }, {
                text: 'Bỏ qua',
                type: 'secondary',
                action: 'ignore',
                handler: function() {
                    clearDraft(key);
                }
            }]
        });
    }
    
    // ========================================
    // FORM HANDLERS
    // ========================================
    
    /**
     * Handle form submission with validation and loading
     */
    function handleFormSubmit(e) {
        const form = e.target;
        
        // Check if form is already being processed
        if (form.dataset.confirmed === 'true') {
            return; // Allow form to submit
        }
        
        // Check if this is a delete form
        const methodInput = form.querySelector('input[name="_method"]');
        const isDeleteForm = methodInput && methodInput.value === 'DELETE';
        
        if (isDeleteForm) {
            e.preventDefault();
            e.stopPropagation();
            
            waitForNotify(() => {
                window.Notify.confirm({
                    title: 'Xác nhận hủy ticket',
                    message: 'Bạn có chắc chắn muốn hủy ticket này?',
                    details: 'Ticket sẽ được chuyển sang trạng thái "Đã hủy" và không thể khôi phục.',
                    type: 'warning',
                    confirmText: 'Hủy ticket',
                    cancelText: 'Không',
                    onConfirm: function() {
                        // Close modal
                        const confirmModal = document.getElementById('confirmation-modal');
                        if (confirmModal) {
                            const modalInstance = bootstrap.Modal.getInstance(confirmModal);
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                        }
                        
                        // Show loading
                        window.Notify.info('Đang hủy ticket...', 'Đang xử lý');
                        
                        // Mark form as confirmed and submit
                        form.dataset.confirmed = 'true';
                        const submitBtn = form.querySelector('button[type="submit"]');
                        setButtonLoading(submitBtn, true, 'Đang hủy...');
                        
                        setTimeout(() => {
                            if (form.requestSubmit) {
                                form.requestSubmit();
                            } else {
                                form.submit();
                            }
                        }, 300);
                    }
                });
            });
        } else {
            // Handle other form submissions
            if (!validateForm(form.id)) {
                e.preventDefault();
                window.Notify?.error('Vui lòng điền đầy đủ thông tin bắt buộc!', 'Thiếu thông tin');
                return;
            }
            
            // Show loading notification
            window.Notify?.info('Đang xử lý yêu cầu...', 'Đang xử lý');
            
            // Set loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            setButtonLoading(submitBtn, true, 'Đang xử lý...');
            
            // Clear draft on successful submission
            if (currentPage === 'create') {
                clearDraft('ticket_draft');
            } else if (currentPage === 'edit' && ticketId) {
                clearDraft(`ticket_edit_draft_${ticketId}`);
            }
        }
    }
    
    /**
     * Handle lease selection change
     */
    function handleLeaseChange(e) {
        const selectedOption = e.target.options[e.target.selectedIndex];
        const unitId = selectedOption.dataset.unitId;
        const unitCode = selectedOption.dataset.unitCode;
        const propertyName = selectedOption.dataset.propertyName;
        
        const unitIdInput = document.getElementById('unit_id');
        const unitInfo = document.getElementById('unitInfo');
        const unitInfoContent = document.getElementById('unitInfoContent');
        
        if (unitId && unitIdInput && unitInfo && unitInfoContent) {
            // Set unit_id
            unitIdInput.value = unitId;
            
            // Show unit info with animation
            unitInfoContent.innerHTML = `
                <div class="unit-info-item">
                    <span class="unit-info-label">Tòa nhà:</span>
                    <span class="unit-info-value">${propertyName}</span>
                </div>
                <div class="unit-info-item">
                    <span class="unit-info-label">Phòng:</span>
                    <span class="unit-info-value">${unitCode}</span>
                </div>
            `;
            unitInfo.classList.remove('d-none');
            
            // Show success notification
            window.Notify?.success('Đã chọn phòng thành công!', 'Thông tin phòng');
        } else if (unitIdInput && unitInfo) {
            unitIdInput.value = '';
            unitInfo.classList.add('d-none');
        }
    }
    
    /**
     * Auto-save draft functionality
     */
    function setupAutoSave(formId, draftKey) {
        const form = document.getElementById(formId);
        if (!form) return;
        
        const inputs = ['title', 'description', 'priority', 'lease_id'];
        
        inputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', debounce(function() {
                    clearTimeout(draftTimer);
                    draftTimer = setTimeout(() => {
                        const draft = {
                            title: document.getElementById('title')?.value || '',
                            description: document.getElementById('description')?.value || '',
                            priority: document.getElementById('priority')?.value || '',
                            lease_id: document.getElementById('lease_id')?.value || ''
                        };
                        saveDraft(draft, draftKey);
                    }, CONFIG.DRAFT_SAVE_DELAY);
                }, 500));
            }
        });
    }
    
    /**
     * Setup unsaved changes warning
     */
    function setupUnsavedChangesWarning(formId) {
        const form = document.getElementById(formId);
        if (!form) return;
        
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            if (!input.readOnly && !input.disabled) {
                input.addEventListener('input', function() {
                    hasUnsavedChanges = true;
                });
            }
        });
        
        // Warn before leaving
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'Bạn có thay đổi chưa được lưu. Bạn có chắc chắn muốn rời khỏi trang?';
            }
        });
        
        // Clear flag on form submit
        form.addEventListener('submit', function() {
            hasUnsavedChanges = false;
        });
    }
    
    // ========================================
    // PAGE-SPECIFIC INITIALIZATION
    // ========================================
    
    /**
     * Initialize Index page
     */
    function initIndex() {
        currentPage = 'index';
        
        // Handle URL success messages
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('created') === 'true') {
            window.Notify?.success('Ticket đã được tạo thành công!', 'Tạo ticket');
        }
        if (urlParams.get('updated') === 'true') {
            window.Notify?.success('Ticket đã được cập nhật thành công!', 'Cập nhật ticket');
        }
        if (urlParams.get('cancelled') === 'true') {
            window.Notify?.success('Ticket đã được hủy thành công!', 'Hủy ticket');
        }
        
        // Handle form submissions
        document.addEventListener('submit', handleFormSubmit);
        
        // Enhanced ticket card interactions
        const ticketCards = document.querySelectorAll('.ticket-card');
        ticketCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
            });
        });
        
        // Filter form handling
        const filterForm = document.querySelector('.filters-form');
        if (filterForm) {
            filterForm.addEventListener('submit', function() {
                window.Notify?.info('Đang lọc dữ liệu...', 'Đang tìm kiếm');
            });
        }
        
        // Stats cards interaction
        const statsCards = document.querySelectorAll('.stat-card');
        statsCards.forEach(card => {
            card.addEventListener('click', function() {
                window.Notify?.info('Đang cập nhật thống kê...', 'Thống kê');
            });
        });
    }
    
    /**
     * Initialize Show page
     */
    function initShow(id) {
        currentPage = 'show';
        ticketId = id;
        
        // Handle URL success messages
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('updated') === 'true') {
            window.Notify?.success('Ticket đã được cập nhật thành công!', 'Cập nhật ticket');
        }
        
        // Handle form submissions
        document.addEventListener('submit', handleFormSubmit);
        
        // Enhanced sidebar cards
        const sidebarCards = document.querySelectorAll('.sidebar-card');
        sidebarCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
            });
        });
        
        // Enhanced content cards
        const contentCards = document.querySelectorAll('.content-card');
        contentCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-1px)';
                this.style.boxShadow = '0 6px 20px rgba(0,0,0,0.12)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
            });
        });
        
        // Timeline interactions
        const timelineItems = document.querySelectorAll('.timeline-item');
        timelineItems.forEach(item => {
            item.addEventListener('click', function() {
                const title = this.querySelector('.timeline-title');
                if (title) {
                    window.Notify?.info(`Xem chi tiết: ${title.textContent}`, 'Lịch sử');
                }
            });
        });
        
        // Add refresh button
        const ticketActions = document.querySelector('.ticket-actions');
        if (ticketActions) {
            const refreshBtn = document.createElement('button');
            refreshBtn.className = 'btn btn-outline-primary btn-sm';
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Làm mới';
            refreshBtn.addEventListener('click', function() {
                window.Notify?.info('Đang làm mới dữ liệu...', 'Làm mới');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            });
            ticketActions.appendChild(refreshBtn);
        }
        
        // Add print button
        if (ticketActions) {
            const printBtn = document.createElement('button');
            printBtn.className = 'btn btn-outline-secondary btn-sm';
            printBtn.innerHTML = '<i class="fas fa-print me-1"></i>In';
            printBtn.addEventListener('click', function() {
                window.Notify?.info('Đang chuẩn bị in...', 'In ticket');
                setTimeout(() => {
                    window.print();
                }, 500);
            });
            ticketActions.appendChild(printBtn);
        }
    }
    
    /**
     * Initialize Create page
     */
    function initCreate() {
        currentPage = 'create';
        
        // Setup lease selection
        const leaseSelect = document.getElementById('lease_id');
        if (leaseSelect) {
            leaseSelect.addEventListener('change', handleLeaseChange);
            
            // Trigger on page load if has old value
            if (leaseSelect.value) {
                leaseSelect.dispatchEvent(new Event('change'));
            }
        }
        
        // Setup form handling
        const form = document.getElementById('ticketForm');
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }
        
        // Setup auto-save
        setupAutoSave('ticketForm', 'ticket_draft');
        
        // Load draft on page load
        const savedDraft = loadDraft('ticket_draft');
        if (savedDraft && isDraftValid(savedDraft)) {
            showDraftRecovery(savedDraft, 'ticket_draft', function(draft) {
                if (draft.title) document.getElementById('title').value = draft.title;
                if (draft.description) document.getElementById('description').value = draft.description;
                if (draft.priority) document.getElementById('priority').value = draft.priority;
                if (draft.lease_id) {
                    document.getElementById('lease_id').value = draft.lease_id;
                    document.getElementById('lease_id').dispatchEvent(new Event('change'));
                }
            });
        }
    }
    
    /**
     * Initialize Edit page
     */
    function initEdit(id, status) {
        currentPage = 'edit';
        ticketId = id;
        
        // Setup form handling
        const form = document.getElementById('ticketForm');
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }
        
        // Setup auto-save if status is open
        if (status === 'open') {
            setupAutoSave('ticketForm', `ticket_edit_draft_${id}`);
            setupUnsavedChangesWarning('ticketForm');
        }
        
        // Load draft on page load
        const draftKey = `ticket_edit_draft_${id}`;
        const savedDraft = loadDraft(draftKey);
        if (savedDraft && isDraftValid(savedDraft)) {
            showDraftRecovery(savedDraft, draftKey, function(draft) {
                const titleInput = document.getElementById('title');
                const descriptionInput = document.getElementById('description');
                const prioritySelect = document.getElementById('priority');
                
                if (draft.title && !titleInput.readOnly) titleInput.value = draft.title;
                if (draft.description && !descriptionInput.readOnly) descriptionInput.value = draft.description;
                if (draft.priority && !prioritySelect.disabled) prioritySelect.value = draft.priority;
            });
        }
        
        // Track form changes
        const inputs = [document.getElementById('title'), document.getElementById('description'), document.getElementById('priority')];
        inputs.forEach(input => {
            if (input && !input.readOnly && !input.disabled) {
                input.addEventListener('input', function() {
                    hasUnsavedChanges = true;
                    window.Notify?.info('Bạn có thay đổi chưa được lưu', 'Thay đổi chưa lưu');
                });
            }
        });
    }
    
    // ========================================
    // PUBLIC API
    // ========================================
    
    return {
        initIndex: initIndex,
        initShow: initShow,
        initCreate: initCreate,
        initEdit: initEdit,
        
        // Utility methods for external use
        waitForNotify: waitForNotify,
        validateForm: validateForm,
        setButtonLoading: setButtonLoading
    };
})();

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // The specific page initialization will be called from individual blade files
    console.log('Ticket Module loaded and ready');
});
