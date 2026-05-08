// Rooms Create Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeCreatePage();
});

// Initialize create page
function initializeCreatePage() {
    setupImageUpload();
    setupFormValidation();
    setupAmenitiesSelection();
    setupTemplateSelection();
    setupDragAndDrop();
    
    // Check for duplicate parameter
    const urlParams = new URLSearchParams(window.location.search);
    const duplicateId = urlParams.get('duplicate');
    if (duplicateId) {
        loadRoomForDuplicate(duplicateId);
    }
}

// Setup image upload functionality
function setupImageUpload() {
    const uploadArea = document.getElementById('imageUploadArea');
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');

    if (!uploadArea || !imageInput || !imagePreview) return;

    // Click to upload
    uploadArea.addEventListener('click', () => {
        imageInput.click();
    });

    // File input change
    imageInput.addEventListener('change', (e) => {
        handleImageFiles(e.target.files);
    });
}

// Setup drag and drop
function setupDragAndDrop() {
    const uploadArea = document.getElementById('imageUploadArea');
    if (!uploadArea) return;

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        handleImageFiles(files);
    });
}

// Handle image files
function handleImageFiles(files) {
    const imagePreview = document.getElementById('imagePreview');
    
    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            // Check file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                showToast('File quá lớn. Vui lòng chọn file nhỏ hơn 5MB.', 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = (e) => {
                addImagePreview(e.target.result, file.name);
            };
            reader.readAsDataURL(file);
        } else {
            showToast('Vui lòng chọn file hình ảnh hợp lệ.', 'error');
        }
    });
}

// Add image preview
function addImagePreview(src, filename) {
    const imagePreview = document.getElementById('imagePreview');
    const previewItem = document.createElement('div');
    previewItem.className = 'image-preview-item';
    previewItem.innerHTML = `
        <img src="${src}" alt="${filename}">
        <button type="button" class="remove-btn" onclick="removeImagePreview(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    imagePreview.appendChild(previewItem);
    
    // Add animation
    previewItem.style.opacity = '0';
    previewItem.style.transform = 'scale(0.8)';
    setTimeout(() => {
        previewItem.style.transition = 'all 0.3s ease';
        previewItem.style.opacity = '1';
        previewItem.style.transform = 'scale(1)';
    }, 100);
}

// Remove image preview
function removeImagePreview(button) {
    const previewItem = button.parentElement;
    previewItem.style.transition = 'all 0.3s ease';
    previewItem.style.opacity = '0';
    previewItem.style.transform = 'scale(0.8)';
    
    setTimeout(() => {
        previewItem.remove();
    }, 300);
}

// Setup form validation
function setupFormValidation() {
    const form = document.getElementById('roomForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            saveRoom();
        });
        
        // Real-time validation
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', validateField);
            field.addEventListener('input', clearFieldError);
        });
    }
}

// Validate individual field
function validateField(e) {
    const field = e.target;
    const value = field.value.trim();
    
    if (field.hasAttribute('required') && !value) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        return false;
    } else {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        return true;
    }
}

// Clear field error
function clearFieldError(e) {
    const field = e.target;
    if (field.value.trim()) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
    }
}

// Setup amenities selection
function setupAmenitiesSelection() {
    const amenityItems = document.querySelectorAll('.amenity-item');
    amenityItems.forEach(item => {
        const checkbox = item.querySelector('input[type="checkbox"]');
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
            });
        }
    });
}

// Setup template selection
function setupTemplateSelection() {
    const templateCards = document.querySelectorAll('.template-card');
    templateCards.forEach(card => {
        card.addEventListener('click', function() {
            templateCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
}

// Save room
function saveRoom() {
    const form = document.getElementById('roomForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'error');
        return;
    }
    
    const formData = new FormData(form);
    
    // Show loading
    const saveBtn = document.querySelector('.btn-success');
    const originalText = saveBtn.textContent;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang lưu...';
    saveBtn.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        // Reset button
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
        
        // Show success message
        showToast('Phòng đã được tạo thành công!', 'success');
        
        // Redirect to rooms list
        setTimeout(() => {
            window.location.href = '/agent/rooms';
        }, 1500);
    }, 2000);
}

// Preview room
function previewRoom() {
    const title = document.getElementById('roomTitle')?.value || 'Tên phòng';
    const price = document.getElementById('roomPrice')?.value || '0';
    const area = document.getElementById('roomArea')?.value || '0';
    const address = document.getElementById('roomAddress')?.value || 'Địa chỉ...';
    const description = document.getElementById('roomDescription')?.value || 'Mô tả phòng...';
    
    // Update preview modal
    document.getElementById('previewTitle').textContent = title;
    document.getElementById('previewPrice').textContent = new Intl.NumberFormat('vi-VN').format(price);
    document.getElementById('previewArea').textContent = area;
    document.getElementById('previewAddress').textContent = address;
    document.getElementById('previewDescription').textContent = description;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
}

// Save draft
function saveDraft() {
    const formData = new FormData(document.getElementById('roomForm'));
    
    // Show loading
    const draftBtn = document.querySelector('.btn-outline-secondary');
    const originalText = draftBtn.textContent;
    draftBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang lưu...';
    draftBtn.disabled = true;
    
    setTimeout(() => {
        draftBtn.textContent = originalText;
        draftBtn.disabled = false;
        showToast('Nháp đã được lưu thành công!', 'success');
    }, 1000);
}

// Duplicate from template
function duplicateFromTemplate() {
    const modal = new bootstrap.Modal(document.getElementById('templateModal'));
    modal.show();
}

// Select template
function selectTemplate(template) {
    const templates = {
        'basic': {
            amenities: ['wifi', 'aircon'],
            priority: 'normal',
            featured: false,
            capacity: 2
        },
        'premium': {
            amenities: ['wifi', 'aircon', 'washing', 'kitchen', 'parking', 'security', 'elevator', 'balcony'],
            priority: 'premium',
            featured: true,
            capacity: 4
        }
    };
    
    const selectedTemplate = templates[template];
    if (selectedTemplate) {
        // Set amenities
        document.querySelectorAll('input[name="amenities[]"]').forEach(checkbox => {
            checkbox.checked = selectedTemplate.amenities.includes(checkbox.value);
            const amenityItem = checkbox.closest('.amenity-item');
            if (checkbox.checked) {
                amenityItem.classList.add('selected');
            } else {
                amenityItem.classList.remove('selected');
            }
        });
        
        // Set priority
        document.getElementById('roomPriority').value = selectedTemplate.priority;
        
        // Set featured
        document.getElementById('roomFeatured').checked = selectedTemplate.featured;
        
        // Set capacity
        document.getElementById('roomCapacity').value = selectedTemplate.capacity;
        
        showToast(`Đã áp dụng mẫu ${template === 'basic' ? 'cơ bản' : 'cao cấp'}`, 'success');
    }
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('templateModal'));
    modal.hide();
}

// Load room for duplicate
function loadRoomForDuplicate(roomId) {
    showToast(`Đang tải dữ liệu phòng #${roomId} để sao chép...`, 'info');
    
    // Simulate loading room data
    setTimeout(() => {
        // In real app, load room data from API
        document.getElementById('roomTitle').value = 'Phòng trọ cao cấp Cầu Giấy (Copy)';
        document.getElementById('roomType').value = 'phongtro';
        document.getElementById('roomPrice').value = '2500000';
        document.getElementById('roomArea').value = '25';
        document.getElementById('roomDistrict').value = 'caugiay';
        document.getElementById('roomAddress').value = '123 Đường Cầu Giấy, Phường Dịch Vọng, Quận Cầu Giấy, Hà Nội';
        document.getElementById('roomDescription').value = 'Phòng trọ cao cấp với đầy đủ tiện ích hiện đại...';
        
        // Set amenities
        const amenities = ['wifi', 'aircon', 'washing', 'security'];
        document.querySelectorAll('input[name="amenities[]"]').forEach(checkbox => {
            checkbox.checked = amenities.includes(checkbox.value);
            const amenityItem = checkbox.closest('.amenity-item');
            if (checkbox.checked) {
                amenityItem.classList.add('selected');
            }
        });
        
        showToast('Đã tải dữ liệu phòng để sao chép', 'success');
    }, 1000);
}

// Auto-save functionality
let autoSaveTimeout;
function setupAutoSave() {
    const form = document.getElementById('roomForm');
    if (form) {
        form.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                saveDraft();
            }, 30000); // Auto-save every 30 seconds
        });
    }
}

// Price formatting
function setupPriceFormatting() {
    const priceInput = document.getElementById('roomPrice');
    if (priceInput) {
        priceInput.addEventListener('input', function() {
            let value = this.value.replace(/[^\d]/g, '');
            if (value) {
                this.value = parseInt(value).toLocaleString('vi-VN');
            }
        });
    }
}

// Area validation
function setupAreaValidation() {
    const areaInput = document.getElementById('roomArea');
    if (areaInput) {
        areaInput.addEventListener('input', function() {
            let value = this.value.replace(/[^\d.]/g, '');
            if (value && parseFloat(value) > 0) {
                this.value = value;
            }
        });
    }
}

// Initialize additional features
document.addEventListener('DOMContentLoaded', function() {
    setupAutoSave();
    setupPriceFormatting();
    setupAreaValidation();
});

// Utility functions
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${getToastIcon(type)} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Add to container
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(toast);
    
    // Show toast
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove after hide
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function getToastIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Form reset
function resetForm() {
    if (confirm('Bạn có chắc chắn muốn làm mới form? Tất cả dữ liệu sẽ bị mất.')) {
        document.getElementById('roomForm').reset();
        document.getElementById('imagePreview').innerHTML = '';
        document.querySelectorAll('.amenity-item').forEach(item => {
            item.classList.remove('selected');
        });
        showToast('Form đã được làm mới', 'info');
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + S to save
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        saveRoom();
    }
    
    // Ctrl + Shift + S to save draft
    if (e.ctrlKey && e.shiftKey && e.key === 'S') {
        e.preventDefault();
        saveDraft();
    }
    
    // Ctrl + P to preview
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        previewRoom();
    }
});
