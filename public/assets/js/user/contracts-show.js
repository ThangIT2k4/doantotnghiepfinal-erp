// Contract Show Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tabs
    initializeTabs();
    
    // Initialize invoice filters
    initializeInvoiceFilters();
    
    // Initialize print functionality
    initializePrintFunctionality();
    
    // Initialize modals
    initializeModals();
});

// Initialize meter reading tabs
function initializeTabs() {
    const tabButtons = document.querySelectorAll('#meterTabs button[data-bs-toggle="tab"]');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Show corresponding tab content
            const targetId = this.getAttribute('data-bs-target');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabPanes.forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });
}

// Initialize invoice filters
function initializeInvoiceFilters() {
    const filterTabs = document.querySelectorAll('.invoice-filters .filter-tab');
    const invoiceRows = document.querySelectorAll('.invoices-table tbody tr');
    
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all filter tabs
            filterTabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Get filter status
            const status = this.getAttribute('data-status');
            
            // Filter invoice rows
            invoiceRows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    const statusBadge = row.querySelector('.status-badge');
                    if (statusBadge) {
                        const rowStatus = statusBadge.classList.contains(`status-${status}`);
                        row.style.display = rowStatus ? '' : 'none';
                    }
                }
            });
        });
    });
}

// Initialize print functionality - DISABLED: Feature not yet implemented
function initializePrintFunctionality() {
    // Feature not yet implemented
    // const printButton = document.querySelector('[onclick="printContract()"]');
    // if (printButton) {
    //     printButton.addEventListener('click', function() {
    //         window.print();
    //     });
    // }
}

// Initialize modals
function initializeModals() {
    // Initialize Bootstrap modals
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        new bootstrap.Modal(modal);
    });
}

// Download contract PDF - DISABLED: Feature not yet implemented
function downloadContract(contractId) {
    console.warn('Download contract feature is not yet implemented');
    // Feature not yet implemented
}

// Print contract - DISABLED: Feature not yet implemented
function printContract() {
    console.warn('Print contract feature is not yet implemented');
    // Feature not yet implemented
    // window.print();
}

// Renew contract - DISABLED: Feature not yet implemented
function renewContract(contractId) {
    console.warn('Renew contract feature is not yet implemented');
    // Feature not yet implemented
}

// Confirm renewal - DISABLED: Feature not yet implemented
function confirmRenewal() {
    console.warn('Confirm renewal feature is not yet implemented');
    // Feature not yet implemented
}

// View invoice details
function viewInvoice(invoiceId) {
    // In a real implementation, you would open an invoice detail modal or navigate to invoice page
    showNotification(`Xem chi tiết hóa đơn ${invoiceId}`, 'info');
    
    // Example: Open invoice detail modal
    // fetch(`/tenant/invoices/${invoiceId}`)
    // .then(response => response.text())
    // .then(html => {
    //     const modalBody = document.getElementById('invoiceDetailContent');
    //     modalBody.innerHTML = html;
    //     const modal = new bootstrap.Modal(document.getElementById('invoiceDetailModal'));
    //     modal.show();
    // })
    // .catch(error => {
    //     console.error('Error loading invoice details:', error);
    //     showNotification('Có lỗi xảy ra khi tải chi tiết hóa đơn', 'error');
    // });
}

// Pay invoice
function payInvoice(invoiceId) {
    // In a real implementation, you would redirect to payment page or open payment modal
    showNotification(`Chuyển đến trang thanh toán hóa đơn ${invoiceId}`, 'info');
    
    // Example: Redirect to payment page
    // window.location.href = `/tenant/invoices/${invoiceId}/pay`;
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Utility function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// Utility function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

// Export functions for global access
// Disabled functions - features not yet implemented
// window.downloadContract = downloadContract;
// window.printContract = printContract;
// window.renewContract = renewContract;
// window.confirmRenewal = confirmRenewal;
window.viewInvoice = viewInvoice;
window.payInvoice = payInvoice;
