// Rooms Page JavaScript
var currentView = 'grid';
var appliedFilters = {};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeRooms();
    setupFilters();
    setupSearch();
    setupViewToggle();
    updateResultsCount();
});

// Initialize rooms functionality
function initializeRooms() {
    console.log('Rooms page initialized');
    
    // Setup tooltips if Bootstrap is available
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// Setup filter functionality
function setupFilters() {
    var filters = ['typeFilter', 'priceFilter', 'areaFilter', 'districtFilter', 'sortFilter'];
    
    for (var i = 0; i < filters.length; i++) {
        var filter = document.getElementById(filters[i]);
        if (filter) {
            filter.addEventListener('change', function() {
                applyFilters();
            });
        }
    }
}

// Setup search functionality
function setupSearch() {
    var searchInput = document.getElementById('searchInput');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            applyFilters();
        });
    }
}

// Setup view toggle
function setupViewToggle() {
    var viewBtns = document.querySelectorAll('.view-btn');
    
    for (var i = 0; i < viewBtns.length; i++) {
        viewBtns[i].addEventListener('click', function() {
            var view = this.getAttribute('data-view');
            switchView(view);
        });
    }
}

// Switch view between grid and list
function switchView(view) {
    currentView = view;
    var roomsGrid = document.getElementById('roomsGrid');
    var viewBtns = document.querySelectorAll('.view-btn');
    
    // Update buttons
    for (var i = 0; i < viewBtns.length; i++) {
        viewBtns[i].classList.remove('active');
    }
    document.querySelector('[data-view="' + view + '"]').classList.add('active');
    
    // Update grid class
    if (view === 'list') {
        roomsGrid.classList.add('list-view');
    } else {
        roomsGrid.classList.remove('list-view');
    }
    
    console.log('View switched to:', view);
}

// Apply all filters
function applyFilters() {
    var type = document.getElementById('typeFilter').value;
    var price = document.getElementById('priceFilter').value;
    var area = document.getElementById('areaFilter').value;
    var district = document.getElementById('districtFilter').value;
    var sort = document.getElementById('sortFilter').value;
    var search = document.getElementById('searchInput').value.toLowerCase();
    
    var rooms = document.querySelectorAll('.room-card');
    var visibleRooms = [];
    
    // Filter rooms
    for (var i = 0; i < rooms.length; i++) {
        var room = rooms[i];
        var roomType = room.getAttribute('data-type');
        var roomPrice = parseFloat(room.getAttribute('data-price'));
        var roomArea = parseFloat(room.getAttribute('data-area'));
        var roomDistrict = room.getAttribute('data-district');
        var roomTitle = room.querySelector('.room-title').textContent.toLowerCase();
        var roomAddress = room.querySelector('.room-address').textContent.toLowerCase();
        
        var typeMatch = !type || roomType === type;
        var priceMatch = checkPriceRange(roomPrice, price);
        var areaMatch = checkAreaRange(roomArea, area);
        var districtMatch = !district || roomDistrict === district;
        var searchMatch = !search || roomTitle.includes(search) || roomAddress.includes(search);
        
        if (typeMatch && priceMatch && areaMatch && districtMatch && searchMatch) {
            room.style.display = 'block';
            visibleRooms.push(room);
        } else {
            room.style.display = 'none';
        }
    }
    
    // Sort visible rooms
    if (sort && visibleRooms.length > 0) {
        sortRooms(visibleRooms, sort);
    }
    
    // Update results count
    updateResultsCount(visibleRooms.length);
    
    // Show/hide empty state
    var emptyState = document.querySelector('.empty-state');
    if (visibleRooms.length === 0) {
        emptyState.style.display = 'block';
    } else {
        emptyState.style.display = 'none';
    }
    
    console.log('Filters applied:', {
        type: type,
        price: price,
        area: area,
        district: district,
        search: search,
        visible: visibleRooms.length
    });
}

// Check price range
function checkPriceRange(price, range) {
    if (!range) return true;
    
    if (range === '0-2') return price < 2;
    if (range === '2-5') return price >= 2 && price < 5;
    if (range === '5-10') return price >= 5 && price < 10;
    if (range === '10-20') return price >= 10 && price < 20;
    if (range === '20+') return price >= 20;
    
    return true;
}

// Check area range
function checkAreaRange(area, range) {
    if (!range) return true;
    
    if (range === '0-20') return area < 20;
    if (range === '20-30') return area >= 20 && area < 30;
    if (range === '30-50') return area >= 30 && area < 50;
    if (range === '50-100') return area >= 50 && area < 100;
    if (range === '100+') return area >= 100;
    
    return true;
}

// Sort rooms
function sortRooms(rooms, sortType) {
    var roomsGrid = document.getElementById('roomsGrid');
    
    rooms.sort(function(a, b) {
        var aPrice = parseFloat(a.getAttribute('data-price'));
        var bPrice = parseFloat(b.getAttribute('data-price'));
        var aArea = parseFloat(a.getAttribute('data-area'));
        var bArea = parseFloat(b.getAttribute('data-area'));
        
        switch (sortType) {
            case 'price-asc':
                return aPrice - bPrice;
            case 'price-desc':
                return bPrice - aPrice;
            case 'area-asc':
                return aArea - bArea;
            case 'area-desc':
                return bArea - aArea;
            case 'newest':
                return 0; // Keep original order
            case 'popular':
                return Math.random() - 0.5; // Random for demo
            default:
                return 0;
        }
    });
    
    // Re-append sorted rooms
    for (var i = 0; i < rooms.length; i++) {
        roomsGrid.appendChild(rooms[i]);
    }
}

// Update results count
function updateResultsCount(count) {
    var resultsElement = document.getElementById('resultsCount');
    if (resultsElement) {
        if (count === undefined) {
            // Count visible rooms
            var visibleRooms = document.querySelectorAll('.room-card[style*="block"], .room-card:not([style*="none"])');
            count = visibleRooms.length;
        }
        resultsElement.textContent = count;
    }
}

// Clear all filters
function clearFilters() {
    // Reset all filter selects
    document.getElementById('typeFilter').value = '';
    document.getElementById('priceFilter').value = '';
    document.getElementById('areaFilter').value = '';
    document.getElementById('districtFilter').value = '';
    document.getElementById('sortFilter').value = 'newest';
    document.getElementById('searchInput').value = '';
    
    // Show all rooms
    var rooms = document.querySelectorAll('.room-card');
    for (var i = 0; i < rooms.length; i++) {
        rooms[i].style.display = 'block';
    }
    
    // Update count
    updateResultsCount();
    
    // Hide empty state
    var emptyState = document.querySelector('.empty-state');
    emptyState.style.display = 'none';
    
    showToast('Đã xóa tất cả bộ lọc', 'info');
}

// Toggle favorite
function toggleFavorite(button) {
    var icon = button.querySelector('i');
    
    if (button.classList.contains('active')) {
        button.classList.remove('active');
        icon.className = 'far fa-heart';
        showToast('Đã xóa khỏi danh sách yêu thích', 'info');
    } else {
        button.classList.add('active');
        icon.className = 'fas fa-heart';
        showToast('Đã thêm vào danh sách yêu thích', 'success');
    }
}

// Apply quick filter from modal
function applyQuickFilter() {
    var activeChips = document.querySelectorAll('.filter-chip.active');
    
    for (var i = 0; i < activeChips.length; i++) {
        var chip = activeChips[i];
        
        if (chip.hasAttribute('data-price')) {
            document.getElementById('priceFilter').value = chip.getAttribute('data-price');
        }
        if (chip.hasAttribute('data-type')) {
            document.getElementById('typeFilter').value = chip.getAttribute('data-type');
        }
        if (chip.hasAttribute('data-district')) {
            document.getElementById('districtFilter').value = chip.getAttribute('data-district');
        }
    }
    
    // Apply filters
    applyFilters();
    
    // Hide modal
    if (typeof bootstrap !== 'undefined') {
        var quickFilterModal = bootstrap.Modal.getInstance(document.getElementById('quickFilterModal'));
        if (quickFilterModal) {
            quickFilterModal.hide();
        }
    }
    
    showToast('Đã áp dụng bộ lọc nhanh', 'success');
}

// Setup quick filter chips
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('filter-chip')) {
        e.target.classList.toggle('active');
    }
});

// Show toast notification
function showToast(message, type) {
    // Remove existing toasts
    var existingToasts = document.querySelectorAll('.custom-toast');
    for (var i = 0; i < existingToasts.length; i++) {
        existingToasts[i].remove();
    }
    
    var toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        toastContainer.style.cssText = 'position:fixed;top:20px;right:20px;z-index:1050;';
        document.body.appendChild(toastContainer);
    }
    
    var toast = document.createElement('div');
    toast.className = 'custom-toast toast-' + type;
    toast.style.cssText = 'background:white;padding:16px 20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.15);margin-bottom:10px;display:flex;align-items:center;gap:12px;min-width:300px;animation:slideInRight 0.3s ease;border-left:4px solid;';
    
    var icon = 'info-circle';
    var color = '#3b82f6';
    
    if (type === 'success') {
        icon = 'check-circle';
        color = '#10b981';
    } else if (type === 'error') {
        icon = 'times-circle';
        color = '#ef4444';
    } else if (type === 'warning') {
        icon = 'exclamation-triangle';
        color = '#f59e0b';
    }
    
    toast.style.borderLeftColor = color;
    toast.innerHTML = '<i class="fas fa-' + icon + '" style="color:' + color + ';font-size:1.2rem;"></i><span>' + message + '</span>';
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(function() {
        if (toast.parentElement) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(function() {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }
    }, 3000);
}

// Add CSS for animations if not already present
if (!document.querySelector('#rooms-animations')) {
    var style = document.createElement('style');
    style.id = 'rooms-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
}
