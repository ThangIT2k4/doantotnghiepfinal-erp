// Invoice Detail JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializePaymentModal();
    initializeInvoiceActions();
});

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
    // Add any specific actions for the detail page
    console.log('Invoice detail page initialized');
}

// Pay invoice function
function payInvoice(invoiceId) {
    // Show payment modal
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

// Process payment
function processPayment() {
    const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!selectedMethod) {
        alert('Vui lòng chọn phương thức thanh toán');
        return;
    }

    const paymentMethod = selectedMethod.value;
    const paymentReference = generatePaymentReference(paymentMethod);
    
    // Show processing modal
    const processingModal = new bootstrap.Modal(document.getElementById('processingModal'));
    processingModal.show();
    
    // Hide payment modal
    const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
    paymentModal.hide();
    
    // Make API call to process payment
    processPaymentAPI(paymentMethod, paymentReference);
}

// Generate payment reference
function generatePaymentReference(method) {
    const timestamp = Date.now();
    const random = Math.floor(Math.random() * 1000);
    return `${method.toUpperCase()}_${timestamp}_${random}`;
}

// Process payment API call
function processPaymentAPI(paymentMethod, paymentReference) {
    const invoiceId = getInvoiceIdFromUrl();
    
    fetch(`/tenant/invoices/${invoiceId}/pay`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            payment_method: paymentMethod,
            payment_reference: paymentReference
        })
    })
    .then(response => response.json())
    .then(data => {
        // Hide processing modal
        const processingModal = bootstrap.Modal.getInstance(document.getElementById('processingModal'));
        processingModal.hide();
        
        if (data.success) {
            showSuccessMessage('Thanh toán thành công!');
            
            // Reload page to update invoice status
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showErrorMessage(data.message || 'Có lỗi xảy ra khi thanh toán');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Hide processing modal
        const processingModal = bootstrap.Modal.getInstance(document.getElementById('processingModal'));
        processingModal.hide();
        
        showErrorMessage('Có lỗi xảy ra khi thanh toán');
    });
}

// Get invoice ID from URL
function getInvoiceIdFromUrl() {
    const path = window.location.pathname;
    const segments = path.split('/');
    return segments[segments.length - 1];
}

// Download invoice PDF
function downloadInvoice(invoiceId) {
    // Show download progress
    showDownloadProgress();
    
    // Make API call to download
    fetch(`/tenant/invoices/${invoiceId}/download`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.blob())
    .then(blob => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `invoice-${invoiceId}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        hideDownloadProgress();
    })
    .catch(error => {
        console.error('Error:', error);
        hideDownloadProgress();
        showErrorMessage('Có lỗi xảy ra khi tải file PDF');
    });
}

// Print invoice
function printInvoice() {
    // Create print-friendly version
    const printContent = createPrintContent();
    
    // Open print window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
}

// Create print content
function createPrintContent() {
    const invoiceData = extractInvoiceData();
    
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Hóa đơn ${invoiceData.invoiceNo}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .invoice-info { margin-bottom: 20px; }
                .invoice-info table { width: 100%; border-collapse: collapse; }
                .invoice-info td { padding: 5px; border: 1px solid #ddd; }
                .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .items-table th, .items-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                .items-table th { background-color: #f5f5f5; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
                .footer { margin-top: 30px; text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>HÓA ĐƠN THANH TOÁN</h1>
                <h2>Mã hóa đơn: ${invoiceData.invoiceNo}</h2>
            </div>
            
            <div class="invoice-info">
                <table>
                    <tr>
                        <td><strong>Ngày phát hành:</strong></td>
                        <td>${invoiceData.issueDate}</td>
                        <td><strong>Ngày đến hạn:</strong></td>
                        <td>${invoiceData.dueDate}</td>
                    </tr>
                    <tr>
                        <td><strong>Phòng:</strong></td>
                        <td>${invoiceData.propertyName}</td>
                        <td><strong>Trạng thái:</strong></td>
                        <td>${invoiceData.status}</td>
                    </tr>
                </table>
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mô tả</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    ${invoiceData.items.map((item, index) => `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.description}</td>
                            <td>${item.quantity}</td>
                            <td>${formatCurrency(item.unitPrice)}</td>
                            <td>${formatCurrency(item.amount)}</td>
                        </tr>
                    `).join('')}
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4"><strong>Tổng cộng:</strong></td>
                        <td><strong>${formatCurrency(invoiceData.totalAmount)}</strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="footer">
                <p>Cảm ơn bạn đã sử dụng dịch vụ!</p>
                <p>In ngày: ${new Date().toLocaleDateString('vi-VN')}</p>
            </div>
        </body>
        </html>
    `;
}

// Extract invoice data from page
function extractInvoiceData() {
    const invoiceNo = document.querySelector('.invoice-number').textContent.replace('Mã hóa đơn: ', '');
    const issueDate = document.querySelector('.info-item:nth-child(2) .info-value').textContent;
    const dueDate = document.querySelector('.info-item:nth-child(3) .info-value').textContent;
    const propertyName = document.querySelector('.invoice-title').textContent;
    const status = document.querySelector('.invoice-status-badge').textContent.trim();
    const totalAmount = document.querySelector('.total-row .price').textContent.replace(/[^\d]/g, '');
    
    const items = Array.from(document.querySelectorAll('.invoice-items-table tbody tr')).map(row => {
        const cells = row.querySelectorAll('td');
        return {
            description: cells[1].textContent,
            quantity: cells[2].textContent,
            unitPrice: cells[3].textContent.replace(/[^\d]/g, ''),
            amount: cells[4].textContent.replace(/[^\d]/g, '')
        };
    });
    
    return {
        invoiceNo,
        issueDate,
        dueDate,
        propertyName,
        status,
        totalAmount,
        items
    };
}

// View receipt
function viewReceipt(invoiceId) {
    // In a real application, this would show the receipt
    alert('Chức năng xem biên lai đang được phát triển');
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

// Show error message
function showErrorMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-exclamation-circle me-2"></i>
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

// Utility function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// Add CSS for styling
const style = document.createElement('style');
style.textContent = `
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
    
    .payment-timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-marker {
        position: absolute;
        left: -30px;
        top: 0;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
    }
    
    .timeline-marker.success {
        background-color: #28a745;
    }
    
    .timeline-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 3px solid #28a745;
    }
    
    .payment-details .row {
        margin-top: 10px;
    }
    
    .payment-details .label {
        font-weight: bold;
        margin-right: 10px;
    }
`;
document.head.appendChild(style);
