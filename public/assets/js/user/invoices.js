// Invoice Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeInvoiceFilters();
    initializePaymentModal();
    initializeInvoiceActions();
});

// Initialize filter functionality - using HTMX without form submission
function initializeInvoiceFilters() {
    const filterTabs = document.querySelectorAll('.filter-tab-blue');
    const statusInput = document.getElementById('statusInput');
    const searchInput = document.getElementById('searchInput');
    const monthFilter = document.getElementById('monthFilter');
    const filterForm = document.getElementById('filterForm');

    if (!filterForm || !searchInput) return;

    const baseUrl = window.location.pathname;
    var isInitialized = false;
    var submitTimeout = null;
    var lastSearchValue = searchInput.value || '';
    var lastStatusValue = statusInput ? (statusInput.value || 'all') : 'all';
    var lastMonthValue = monthFilter ? (monthFilter.value || '') : '';

    // Debounce function to prevent multiple rapid submissions
    function debouncedSearch() {
        if (!isInitialized) {
            return;
        }
        
        var currentSearchValue = searchInput.value || '';
        var currentStatusValue = statusInput ? (statusInput.value || 'all') : 'all';
        var currentMonthValue = monthFilter ? (monthFilter.value || '') : '';
        
        // Check if values actually changed
        if (currentSearchValue === lastSearchValue && 
            currentStatusValue === lastStatusValue && 
            currentMonthValue === lastMonthValue) {
            return;
        }
        
        // Update last values
        lastSearchValue = currentSearchValue;
        lastStatusValue = currentStatusValue;
        lastMonthValue = currentMonthValue;
        
        if (submitTimeout) {
            clearTimeout(submitTimeout);
        }
        
        submitTimeout = setTimeout(function() {
            if (typeof htmx !== 'undefined' && isInitialized) {
                // Build URL with query parameters
                var params = new URLSearchParams();
                if (currentSearchValue) {
                    params.append('search', currentSearchValue);
                }
                if (currentStatusValue && currentStatusValue !== 'all') {
                    params.append('status', currentStatusValue);
                }
                if (currentMonthValue) {
                    params.append('month', currentMonthValue);
                }
                
                var fullUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');
                
                // Use htmx.ajax to make request - no form submission
                htmx.ajax('GET', fullUrl, {
                    target: '#invoices-list-container',
                    swap: 'innerHTML',
                    pushUrl: true,
                    indicator: '#htmx-loading'
                });
            }
        }, 500); // 500ms debounce delay
    }

    // Filter tabs - HTMX handles these via attributes, but update status input
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const status = this.dataset.status;
            if (statusInput) {
                statusInput.value = status;
            }
        });
    });

    // Search input with debounce
    searchInput.addEventListener('input', function() {
        debouncedSearch();
    });
    
    // Prevent Enter key from submitting form
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            e.stopPropagation();
            debouncedSearch();
            return false;
        }
    });

    // Month filter
    if (monthFilter) {
        monthFilter.addEventListener('change', function() {
            debouncedSearch();
        });
    }
    
    // Mark as initialized after a short delay to prevent initial triggers
    setTimeout(function() {
        isInitialized = true;
        lastSearchValue = searchInput.value || '';
        lastStatusValue = statusInput ? (statusInput.value || 'all') : 'all';
        lastMonthValue = monthFilter ? (monthFilter.value || '') : '';
    }, 500);
    
    // Update values after HTMX swap to prevent false triggers
    document.body.addEventListener('htmx:afterSwap', function(event) {
        if (event.detail.target.id === 'invoices-list-container') {
            setTimeout(function() {
                lastSearchValue = searchInput.value || '';
                lastStatusValue = statusInput ? (statusInput.value || 'all') : 'all';
                lastMonthValue = monthFilter ? (monthFilter.value || '') : '';
            }, 100);
        }
    });
}

// Show filter loading state
function showFilterLoading() {
    const invoicesList = document.querySelector('.invoices-list');
    if (invoicesList) {
        invoicesList.style.opacity = '0.6';
        invoicesList.style.pointerEvents = 'none';
        
        // Add loading overlay
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'filter-loading-overlay';
        loadingOverlay.innerHTML = `
            <div class="loading-content">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Đang lọc hóa đơn...</p>
            </div>
        `;
        loadingOverlay.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 15px;
        `;
        
        invoicesList.style.position = 'relative';
        invoicesList.appendChild(loadingOverlay);
    }
}

// Initialize payment modal
function initializePaymentModal() {
    const paymentModal = document.getElementById('paymentModal');
    const methodOptions = document.querySelectorAll('.method-option');
    const confirmBtn = document.getElementById('confirmPaymentBtn');

    methodOptions.forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Update UI
            methodOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            
            // Enable confirm button
            confirmBtn.disabled = false;
        });
    });
}

// Initialize invoice actions
function initializeInvoiceActions() {
    // Add click handlers for invoice cards
    const invoiceCards = document.querySelectorAll('.invoice-card');
    invoiceCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on buttons or links
            if (e.target.closest('button') || e.target.closest('a')) {
                return;
            }
            
            // Find the view details button and click it
            const viewBtn = this.querySelector('a[href*="show"]');
            if (viewBtn) {
                viewBtn.click();
            }
        });
    });
    
    // Initialize pagination
    initializePagination();
}

// Initialize pagination functionality
function initializePagination() {
    const paginationLinks = document.querySelectorAll('.pagination .page-link');
    const pagination = document.querySelector('.pagination');
    const paginationSection = document.querySelector('.pagination-section');
    
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Add loading state
            if (pagination) {
                pagination.classList.add('loading');
            }
            
            // Navigate to the page
            const url = this.getAttribute('href');
            if (url) {
                window.location.href = url;
            }
        });
    });
    
    // Handle pagination controls
    const paginationControls = document.querySelectorAll('.pagination-controls .btn');
    paginationControls.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Add loading state
            if (pagination) {
                pagination.classList.add('loading');
            }
            
            // Navigate to the page
            const url = this.getAttribute('href');
            if (url) {
                window.location.href = url;
            }
        });
    });
    
    // Add smooth scroll to pagination when navigating
    if (paginationSection) {
        // Add visible class immediately for initial load
        setTimeout(() => {
            paginationSection.classList.add('visible');
        }, 100);
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        });
        
        observer.observe(paginationSection);
    }
}

// Pay invoice function
function payInvoice(invoiceId) {
    // Get invoice data (you might want to fetch this via AJAX)
    const invoiceCard = document.querySelector(`[onclick*="${invoiceId}"]`).closest('.invoice-card');
    const invoiceData = extractInvoiceData(invoiceCard);
    
    // Populate payment modal
    populatePaymentModal(invoiceData);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

// Extract invoice data from card
function extractInvoiceData(card) {
    const invoiceId = card.querySelector('.invoice-id').textContent;
    const propertyName = card.querySelector('.property-name').textContent;
    const period = card.querySelector('.invoice-period').textContent;
    const totalAmount = card.querySelector('.breakdown-total span:last-child').textContent;
    
    return {
        id: invoiceId,
        property: propertyName,
        period: period,
        amount: totalAmount
    };
}

// Populate payment modal
function populatePaymentModal(data) {
    document.getElementById('paymentInvoiceId').textContent = data.id;
    document.getElementById('paymentProperty').textContent = data.property;
    document.getElementById('paymentPeriod').textContent = data.period;
    document.getElementById('paymentAmount').textContent = data.amount;
}

// Process payment
function processPayment() {
    const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!selectedMethod) {
        alert('Vui lòng chọn phương thức thanh toán');
        return;
    }

    const invoiceId = document.getElementById('paymentInvoiceId').textContent;
    const paymentMethod = selectedMethod.value;
    
    // Show processing modal
    const processingModal = new bootstrap.Modal(document.getElementById('processingModal'));
    processingModal.show();
    
    // Hide payment modal
    const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
    paymentModal.hide();
    
    // Simulate payment processing
    simulatePaymentProcessing(invoiceId, paymentMethod);
}

// Simulate payment processing
function simulatePaymentProcessing(invoiceId, paymentMethod) {
    const progressBar = document.getElementById('paymentProgress');
    let progress = 0;
    
    const interval = setInterval(() => {
        progress += Math.random() * 30;
        if (progress > 100) progress = 100;
        
        progressBar.style.width = progress + '%';
        
        if (progress >= 100) {
            clearInterval(interval);
            
            // Simulate API call
            setTimeout(() => {
                // Hide processing modal
                const processingModal = bootstrap.Modal.getInstance(document.getElementById('processingModal'));
                processingModal.hide();
                
                // Show success message
                showSuccessMessage('Thanh toán thành công!');
                
                // Reload page to update invoice status
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }, 1000);
        }
    }, 200);
}

// View invoice details
function viewInvoice(invoiceId) {
    // Redirect to invoice detail page
    window.location.href = `/tenant/invoices/${invoiceId}`;
}

// Download invoice PDF
function downloadInvoice(invoiceId) {
    // Show download progress
    showDownloadProgress();
    
    // Simulate download
    setTimeout(() => {
        // In a real application, this would trigger the actual download
        const link = document.createElement('a');
        link.href = `/tenant/invoices/${invoiceId}/download`;
        link.download = `invoice-${invoiceId}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        hideDownloadProgress();
    }, 2000);
}

// View receipt
function viewReceipt(invoiceId) {
    // In a real application, this would show the receipt
    alert('Chức năng xem biên lai đang được phát triển');
}

// Export invoices to Excel
function exportInvoices() {
    // Get current filter parameters
    const params = new URLSearchParams(window.location.search);
    
    // Show loading
    showLoading('Đang xuất file Excel...');
    
    // Simulate export
    setTimeout(() => {
        // In a real application, this would trigger the actual export
        const link = document.createElement('a');
        link.href = `/tenant/invoices/export?${params.toString()}`;
        link.download = `invoices-${new Date().toISOString().split('T')[0]}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        hideLoading();
    }, 2000);
}

// Show download progress
function showDownloadProgress() {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'downloadProgressModal';
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="download-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <h4 class="mt-3">Đang tạo file PDF...</h4>
                    <p>Vui lòng chờ trong giây lát</p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%" id="downloadProgressBar"></div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    // Animate progress
    const progressBar = document.getElementById('downloadProgressBar');
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 20;
        if (progress > 100) progress = 100;
        progressBar.style.width = progress + '%';
        
        if (progress >= 100) {
            clearInterval(interval);
        }
    }, 100);
}

// Hide download progress
function hideDownloadProgress() {
    const modal = document.getElementById('downloadProgressModal');
    if (modal) {
        const bootstrapModal = bootstrap.Modal.getInstance(modal);
        bootstrapModal.hide();
        modal.remove();
    }
}

// Show success message
function showSuccessMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Show loading
function showLoading(message = 'Đang xử lý...') {
    const loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.innerHTML = `
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">${message}</p>
        </div>
    `;
    loading.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    
    document.body.appendChild(loading);
}

// Hide loading
function hideLoading() {
    const loading = document.querySelector('.loading-overlay');
    if (loading) {
        loading.remove();
    }
}

// Utility function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// Utility function to format date
function formatDate(date) {
    return new Intl.DateTimeFormat('vi-VN').format(new Date(date));
}

// Add CSS for loading overlay
const style = document.createElement('style');
style.textContent = `
    .loading-overlay .loading-content {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        text-align: center;
    }
    
    .method-option {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .method-option:hover {
        background-color: #f8f9fa;
    }
    
    .method-option.selected {
        background-color: #e3f2fd;
        border-color: #2196f3;
    }
    
    .invoice-card {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .invoice-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
`;
document.head.appendChild(style);