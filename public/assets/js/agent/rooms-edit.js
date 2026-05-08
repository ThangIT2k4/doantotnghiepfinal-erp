// Rooms Edit Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeEditPage();
});

// Initialize edit page
function initializeEditPage() {
    setupImageUpload();
    setupFormValidation();
    setupAmenitiesSelection();
    setupCurrentImages();
    setupTimeline();
    loadRoomData();
    setupAutoSave();
    setupPriceFormatting();
    setupAreaValidation();
}

// Load room data
function loadRoomData() {
    const roomId = document.getElementById('roomId')?.value;
    if (!roomId) return;
    
    showToast('Đang tải dữ liệu phòng...', 'info');
    
    // Simulate API call
    setTimeout(() => {
        // In real app, load room data from API
        populateFormWithData();
        showToast('Đã tải dữ liệu phòng', 'success');
    }, 1000);
}

// Populate form with room data
function populateFormWithData() {
    // This would normally come from API
    const roomData = {
        title: 'Phòng trọ cao cấp Cầu Giấy',
        type: 'phongtro',
        price: '2500000',
        area: '25',
        district: 'caugiay',
        ward: 'Dịch Vọng',
        address: '123 Đường Cầu Giấy, Phường Dịch Vọng, Quận Cầu Giấy, Hà Nội',
        description: 'Phòng trọ cao cấp với đầy đủ tiện ích hiện đại, vị trí thuận lợi gần trung tâm thành phố. Phòng được trang bị đầy đủ nội thất cơ bản, WiFi miễn phí, điều hòa, máy giặt. An ninh 24/7, có bảo vệ và camera giám sát.',
        amenities: ['wifi', 'aircon', 'washing', 'security'],
        status: 'available',
        priority: 'high',
        capacity: '2',
        featured: true,
        published: true
    };
    
    // Populate form fields
    document.getElementById('roomTitle').value = roomData.title;
    document.getElementById('roomType').value = roomData.type;
    document.getElementById('roomPrice').value = roomData.price;
    document.getElementById('roomArea').value = roomData.area;
    document.getElementById('roomDistrict').value = roomData.district;
    document.getElementById('roomWard').value = roomData.ward;
    document.getElementById('roomAddress').value = roomData.address;
    document.getElementById('roomDescription').value = roomData.description;
    document.getElementById('roomStatus').value = roomData.status;
    document.getElementById('roomPriority').value = roomData.priority;
    document.getElementById('roomCapacity').value = roomData.capacity;
    document.getElementById('roomFeatured').checked = roomData.featured;
    document.getElementById('roomPublished').checked = roomData.published;
    
    // Set amenities
    document.querySelectorAll('input[name="amenities[]"]').forEach(checkbox => {
        checkbox.checked = roomData.amenities.includes(checkbox.value);
        const amenityItem = checkbox.closest('.amenity-item');
        if (checkbox.checked) {
            amenityItem.classList.add('selected');
        }
    });
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
    
    // Drag and drop
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

// Setup current images
function setupCurrentImages() {
    const removeButtons = document.querySelectorAll('.current-image-item .remove-btn');
    removeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const imageItem = this.closest('.current-image-item');
            if (confirm('Bạn có chắc chắn muốn xóa hình ảnh này?')) {
                imageItem.style.transition = 'all 0.3s ease';
                imageItem.style.opacity = '0';
                imageItem.style.transform = 'scale(0.8)';
                
                setTimeout(() => {
                    imageItem.remove();
                    showToast('Hình ảnh đã được xóa', 'success');
                }, 300);
            }
        });
    });
}

// Setup timeline
function setupTimeline() {
    const timelineItems = document.querySelectorAll('.timeline-item');
    timelineItems.forEach((item, index) => {
        // Add animation delay
        item.style.animationDelay = `${index * 0.1}s`;
        item.classList.add('fade-in');
    });
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

// Save room
function saveRoom() {
    const form = document.getElementById('roomForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'error');
        return;
    }
    
    const formData = new FormData(form);
    const roomId = document.getElementById('roomId')?.value;
    
    // Show loading
    const saveBtn = document.querySelector('.btn-warning');
    const originalText = saveBtn.textContent;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang cập nhật...';
    saveBtn.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        // Reset button
        saveBtn.textContent = originalText;
        saveBtn.disabled = false;
        
        // Show success message
        showToast('Phòng đã được cập nhật thành công!', 'success');
        
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

// Duplicate room
function duplicateRoom() {
    const roomId = document.getElementById('roomId')?.value;
    if (confirm('Bạn có chắc chắn muốn sao chép phòng này?')) {
        window.location.href = `/agent/rooms/create?duplicate=${roomId}`;
    }
}

// View history
function viewHistory() {
    showToast('Mở lịch sử thay đổi', 'info');
    // In real app, open history modal or navigate to history page
}

// Delete room
function deleteRoom() {
    const roomId = document.getElementById('roomId')?.value;
    if (confirm('Bạn có chắc chắn muốn xóa phòng này? Hành động này không thể hoàn tác.')) {
        // Show loading
        showToast('Đang xóa phòng...', 'info');
        
        // Simulate API call
        setTimeout(() => {
            showToast('Phòng đã được xóa thành công', 'success');
            setTimeout(() => {
                window.location.href = '/agent/rooms';
            }, 1500);
        }, 2000);
    }
}

// Remove image
function removeImage(imageId) {
    if (confirm('Bạn có chắc chắn muốn xóa hình ảnh này?')) {
        showToast('Hình ảnh đã được xóa', 'success');
        // In real app, make API call to remove image
    }
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
    if (confirm('Bạn có chắc chắn muốn làm mới form? Tất cả thay đổi sẽ bị mất.')) {
        loadRoomData();
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
    
    // Ctrl + D to duplicate
    if (e.ctrlKey && e.key === 'd') {
        e.preventDefault();
        duplicateRoom();
    }
});

// Track changes
let hasChanges = false;
function trackChanges() {
    const form = document.getElementById('roomForm');
    if (form) {
        form.addEventListener('input', function() {
            hasChanges = true;
        });
    }
}

// Warn before leaving if there are unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = 'Bạn có thay đổi chưa được lưu. Bạn có chắc chắn muốn rời khỏi trang?';
    }
});

// Initialize change tracking
document.addEventListener('DOMContentLoaded', function() {
    trackChanges();
});
