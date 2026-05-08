// Rooms Management JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeRoomsPage();
});

// Initialize rooms page
function initializeRoomsPage() {
    setupEventListeners();
    setupImageUpload();
    setupFilters();
    setupTableInteractions();
    loadRoomsData();
}

// Setup event listeners
function setupEventListeners() {
    // Search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }

    // Filter selects
    const filterSelects = document.querySelectorAll('#typeFilter, #statusFilter, #priceFilter, #districtFilter, #sortFilter');
    filterSelects.forEach(select => {
        select.addEventListener('change', handleFilterChange);
    });

    // View toggle buttons
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            viewButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            toggleTableView(this.dataset.view);
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            closeAllDropdowns();
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

    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#dc2626';
        uploadArea.style.backgroundColor = '#fef2f2';
    });

    uploadArea.addEventListener('dragleave', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#d1d5db';
        uploadArea.style.backgroundColor = 'transparent';
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#d1d5db';
        uploadArea.style.backgroundColor = 'transparent';
        
        const files = e.dataTransfer.files;
        handleImageFiles(files);
    });

    // File input change
    imageInput.addEventListener('change', (e) => {
        handleImageFiles(e.target.files);
    });
}

// Handle image files
function handleImageFiles(files) {
    const imagePreview = document.getElementById('imagePreview');
    
    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                addImagePreview(e.target.result, file.name);
            };
            reader.readAsDataURL(file);
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
}

// Remove image preview
function removeImagePreview(button) {
    button.parentElement.remove();
}

// Setup filters
function setupFilters() {
    // Initialize filter values from URL params
    const urlParams = new URLSearchParams(window.location.search);
    const filters = ['type', 'status', 'price', 'district', 'sort'];
    
    filters.forEach(filter => {
        const value = urlParams.get(filter);
        if (value) {
            const select = document.getElementById(filter + 'Filter');
            if (select) {
                select.value = value;
            }
        }
    });
}

// Setup table interactions
function setupTableInteractions() {
    // Select all checkbox
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', toggleSelectAll);
    }

    // Individual checkboxes
    const roomCheckboxes = document.querySelectorAll('.room-checkbox');
    roomCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectAllState);
    });
}

// Handle search
function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    filterRooms();
    updateURL();
}

// Handle filter change
function handleFilterChange() {
    filterRooms();
    updateURL();
}

// Filter rooms based on current filters
function filterRooms() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const typeFilter = document.getElementById('typeFilter')?.value || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    const priceFilter = document.getElementById('priceFilter')?.value || '';
    const districtFilter = document.getElementById('districtFilter')?.value || '';
    const sortFilter = document.getElementById('sortFilter')?.value || 'newest';

    const roomRows = document.querySelectorAll('.room-row');
    let visibleCount = 0;

    roomRows.forEach(row => {
        const title = row.querySelector('.room-title')?.textContent.toLowerCase() || '';
        const address = row.querySelector('.room-address')?.textContent.toLowerCase() || '';
        const type = row.dataset.type || '';
        const status = row.dataset.status || '';
        const price = parseFloat(row.dataset.price) || 0;
        const district = row.dataset.district || '';

        let isVisible = true;

        // Search filter
        if (searchTerm && !title.includes(searchTerm) && !address.includes(searchTerm)) {
            isVisible = false;
        }

        // Type filter
        if (typeFilter && type !== typeFilter) {
            isVisible = false;
        }

        // Status filter
        if (statusFilter && status !== statusFilter) {
            isVisible = false;
        }

        // Price filter
        if (priceFilter) {
            const [min, max] = priceFilter.split('-').map(p => parseFloat(p));
            if (max && (price < min || price > max)) {
                isVisible = false;
            } else if (!max && price < min) {
                isVisible = false;
            }
        }

        // District filter
        if (districtFilter && district !== districtFilter) {
            isVisible = false;
        }

        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });

    // Update display count
    const displayCount = document.getElementById('displayCount');
    if (displayCount) {
        displayCount.textContent = visibleCount;
    }

    // Sort visible rows
    sortRooms(sortFilter);
}

// Sort rooms
function sortRooms(sortBy) {
    const tbody = document.getElementById('roomsTableBody');
    const rows = Array.from(tbody.querySelectorAll('.room-row:not([style*="display: none"])'));

    rows.sort((a, b) => {
        switch (sortBy) {
            case 'newest':
                return new Date(b.dataset.createdAt) - new Date(a.dataset.createdAt);
            case 'oldest':
                return new Date(a.dataset.createdAt) - new Date(b.dataset.createdAt);
            case 'price-asc':
                return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            case 'price-desc':
                return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
            case 'area-asc':
                return parseFloat(a.dataset.area) - parseFloat(b.dataset.area);
            case 'area-desc':
                return parseFloat(b.dataset.area) - parseFloat(a.dataset.area);
            default:
                return 0;
        }
    });

    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

// Update URL with current filters
function updateURL() {
    const searchTerm = document.getElementById('searchInput')?.value || '';
    const typeFilter = document.getElementById('typeFilter')?.value || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    const priceFilter = document.getElementById('priceFilter')?.value || '';
    const districtFilter = document.getElementById('districtFilter')?.value || '';
    const sortFilter = document.getElementById('sortFilter')?.value || '';

    const params = new URLSearchParams();
    if (searchTerm) params.set('search', searchTerm);
    if (typeFilter) params.set('type', typeFilter);
    if (statusFilter) params.set('status', statusFilter);
    if (priceFilter) params.set('price', priceFilter);
    if (districtFilter) params.set('district', districtFilter);
    if (sortFilter) params.set('sort', sortFilter);

    const newURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.history.replaceState({}, '', newURL);
}

// Clear all filters
function clearFilters() {
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const priceFilter = document.getElementById('priceFilter');
    const districtFilter = document.getElementById('districtFilter');
    const sortFilter = document.getElementById('sortFilter');
    
    if (searchInput) searchInput.value = '';
    if (typeFilter) typeFilter.value = '';
    if (statusFilter) statusFilter.value = '';
    if (priceFilter) priceFilter.value = '';
    if (districtFilter) districtFilter.value = '';
    if (sortFilter) sortFilter.value = 'newest';
    
    filterRooms();
    updateURL();
}

// Toggle select all
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const roomCheckboxes = document.querySelectorAll('.room-checkbox');
    
    if (selectAllCheckbox) {
        roomCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
    }
}

// Update select all state
function updateSelectAllState() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const roomCheckboxes = document.querySelectorAll('.room-checkbox');
    const checkedBoxes = document.querySelectorAll('.room-checkbox:checked');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = checkedBoxes.length === roomCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < roomCheckboxes.length;
    }
}

// Toggle table view
function toggleTableView(view) {
    const tableContainer = document.querySelector('.table-container');
    const table = document.getElementById('roomsTable');
    
    if (tableContainer) {
        if (view === 'grid') {
            tableContainer.innerHTML = createGridView();
        } else {
            // Restore table view
            loadRoomsData();
        }
    }
}

// Create grid view
function createGridView() {
    const rooms = getRoomsData();
    let gridHTML = '<div class="rooms-grid">';
    
    rooms.forEach(room => {
        gridHTML += createRoomCard(room);
    });
    
    gridHTML += '</div>';
    return gridHTML;
}

// Create room card for grid view
function createRoomCard(room) {
    return `
        <div class="room-card" data-id="${room.id}">
            <div class="room-card-image">
                <img src="${room.image}" alt="${room.title}">
                <div class="room-card-badges">
                    <span class="status-badge status-${room.status}">${getStatusText(room.status)}</span>
                </div>
            </div>
            <div class="room-card-content">
                <h4 class="room-card-title">${room.title}</h4>
                <p class="room-card-address">${room.address}</p>
                <div class="room-card-meta">
                    <span class="room-card-price">${formatPrice(room.price)}</span>
                    <span class="room-card-area">${room.area}m²</span>
                </div>
                <div class="room-card-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewRoom(${room.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning" onclick="editRoom(${room.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRoom(${room.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Load rooms data
function loadRoomsData() {
    // Simulate API call
    showLoading();
    
    setTimeout(() => {
        // In real app, this would be an API call
        hideLoading();
        updateStats();
    }, 1000);
}

// Show loading state
function showLoading() {
    const tableContainer = document.querySelector('.table-container');
    if (tableContainer) {
        tableContainer.classList.add('loading');
    }
}

// Hide loading state
function hideLoading() {
    const tableContainer = document.querySelector('.table-container');
    if (tableContainer) {
        tableContainer.classList.remove('loading');
    }
}

// Update stats
function updateStats() {
    // Animate stat numbers
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach(stat => {
        const finalValue = parseInt(stat.textContent);
        animateNumber(stat, 0, finalValue, 1000);
    });
}

// Animate number
function animateNumber(element, start, end, duration) {
    const startTime = performance.now();
    
    function updateNumber(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = Math.floor(start + (end - start) * progress);
        
        element.textContent = current.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        }
    }
    
    requestAnimationFrame(updateNumber);
}

// Get rooms data (mock data)
function getRoomsData() {
    return [
        {
            id: 1,
            title: 'Phòng trọ cao cấp Cầu Giấy',
            address: '123 Đường Cầu Giấy, Phường Dịch Vọng, Quận Cầu Giấy, Hà Nội',
            price: 2500000,
            area: 25,
            status: 'available',
            type: 'phongtro',
            image: 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=400&h=300&fit=crop'
        },
        {
            id: 2,
            title: 'Chung cư mini Mạnh Hà',
            address: '456 Đường Mạnh Hà, Quận Hoàng Mai, Hà Nội',
            price: 10000000,
            area: 45,
            status: 'rented',
            type: 'chungcumini',
            image: 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=400&h=300&fit=crop'
        },
        {
            id: 3,
            title: 'Homestay Hạnh Đào',
            address: '789 Đường Hạnh Đào, Quận Hoàng Mai, Hà Nội',
            price: 8000000,
            area: 35,
            status: 'maintenance',
            type: 'phongtro',
            image: 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400&h=300&fit=crop'
        }
    ];
}

// Get status text
function getStatusText(status) {
    const statusMap = {
        'available': 'Còn trống',
        'rented': 'Đã cho thuê',
        'maintenance': 'Bảo trì',
        'inactive': 'Không hoạt động'
    };
    return statusMap[status] || status;
}

// Format price
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN').format(price) + ' VNĐ/tháng';
}

// Toggle dropdown
function toggleDropdown(button) {
    const dropdown = button.nextElementSibling;
    closeAllDropdowns();
    dropdown.classList.add('show');
}

// Close all dropdowns
function closeAllDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach(dropdown => {
        dropdown.classList.remove('show');
    });
}

// Room actions
function viewRoom(id) {
    // Navigate to room detail page
    window.location.href = `/agent/rooms/${id}`;
}

function editRoom(id) {
    // Navigate to edit page
    window.location.href = `/agent/rooms/${id}/edit`;
}

function duplicateRoom(id) {
    // Navigate to create page with duplicate data
    window.location.href = `/agent/rooms/create?duplicate=${id}`;
}

function deleteRoom(id) {
    window.currentDeleteId = id;
    const deleteModalElement = document.getElementById('deleteModal');
    if (deleteModalElement) {
        const deleteModal = new bootstrap.Modal(deleteModalElement);
        deleteModal.show();
    }
}

function toggleStatus(id) {
    // Toggle room status
    showToast('Trạng thái phòng đã được cập nhật', 'success');
}

function viewHistory(id) {
    // Show room history
    showToast('Mở lịch sử phòng', 'info');
}

// Modal functions
function openCreateModal() {
    // Navigate to create room page
    window.location.href = '/agent/rooms/create';
}

function openRoomModal(mode, id = null) {
    // This function is kept for compatibility but redirects to actual pages
    if (mode === 'create') {
        window.location.href = '/agent/rooms/create';
    } else if (mode === 'edit') {
        window.location.href = `/agent/rooms/${id}/edit`;
    } else if (mode === 'duplicate') {
        window.location.href = `/agent/rooms/create?duplicate=${id}`;
    }
}

function clearRoomForm() {
    const form = document.getElementById('roomForm');
    const preview = document.getElementById('imagePreview');
    if (form) form.reset();
    if (preview) preview.innerHTML = '';
}

function loadRoomData(id, isDuplicate = false) {
    // Load room data for editing
    const room = getRoomsData().find(r => r.id === id);
    if (room) {
        const titleField = document.getElementById('roomTitle');
        const typeField = document.getElementById('roomType');
        const priceField = document.getElementById('roomPrice');
        const areaField = document.getElementById('roomArea');
        
        if (titleField) titleField.value = isDuplicate ? room.title + ' (Copy)' : room.title;
        if (typeField) typeField.value = room.type;
        if (priceField) priceField.value = room.price;
        if (areaField) areaField.value = room.area;
        // Load other fields...
    }
}

function saveRoom() {
    // Validate form
    if (!validateRoomForm()) {
        return;
    }
    
    // Show loading
    const saveBtn = document.querySelector('#roomModal .btn-primary');
    if (saveBtn) {
        const originalText = saveBtn.textContent;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
        saveBtn.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
            // Reset button
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('roomModal'));
            if (modal) modal.hide();
            
            // Show success message
            showToast('Phòng đã được lưu thành công', 'success');
            
            // Refresh table
            loadRoomsData();
        }, 2000);
    }
}

function validateRoomForm() {
    const requiredFields = ['roomTitle', 'roomType', 'roomPrice', 'roomArea', 'roomAddress'];
    let isValid = true;
    
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        }
    });
    
    if (!isValid) {
        showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'error');
    }
    
    return isValid;
}

function confirmDelete() {
    const id = window.currentDeleteId;
    
    // Show loading
    const deleteBtn = document.querySelector('#deleteModal .btn-danger');
    if (deleteBtn) {
        const originalText = deleteBtn.textContent;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xóa...';
        deleteBtn.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
            // Reset button
            deleteBtn.textContent = originalText;
            deleteBtn.disabled = false;
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            if (modal) modal.hide();
            
            // Show success message
            showToast('Phòng đã được xóa thành công', 'success');
            
            // Remove row from table
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                row.remove();
            }
            
            // Update count
            updateDisplayCount();
        }, 1500);
    }
}

function updateDisplayCount() {
    const visibleRows = document.querySelectorAll('.room-row:not([style*="display: none"])');
    const displayCount = document.getElementById('displayCount');
    if (displayCount) {
        displayCount.textContent = visibleRows.length;
    }
}

function refreshTable() {
    loadRoomsData();
    showToast('Bảng đã được làm mới', 'info');
}

function exportRooms() {
    showToast('Đang xuất dữ liệu...', 'info');
    
    // Simulate export
    setTimeout(() => {
        showToast('Xuất dữ liệu thành công', 'success');
    }, 2000);
}

// Utility functions
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

// Initialize page based on current page
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('/create') || currentPage.includes('/edit')) {
        // These pages have their own JS files
        return;
    } else {
        initializeRoomsPage();
    }
});
