// Maintenance Page JavaScript
var currentRequestId = null;
var currentRating = { quality: 0, service: 0 };
var maintenanceData = {};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeMaintenance();
    setupFilters();
    setupSearch();
    setupImageUpload();
    setupStarRating();
    loadMaintenanceData();
});

// Initialize maintenance functionality
function initializeMaintenance() {
    console.log('Maintenance page initialized');
    
    // Setup tooltips if Bootstrap is available
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Animate stat cards
    animateStatCards();
}

// Setup filter functionality
function setupFilters() {
    var filterTabs = document.querySelectorAll('.filter-tab');
    var categoryFilter = document.getElementById('categoryFilter');
    
    // Status filter tabs
    for (var i = 0; i < filterTabs.length; i++) {
        filterTabs[i].addEventListener('click', function() {
            var status = this.getAttribute('data-status');
            
            // Update active tab
            for (var j = 0; j < filterTabs.length; j++) {
                filterTabs[j].classList.remove('active');
            }
            this.classList.add('active');
            
            // Filter requests
            filterRequests();
        });
    }
    
    // Category filter
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            filterRequests();
        });
    }
}

// Filter requests by status and category
function filterRequests() {
    var activeTab = document.querySelector('.filter-tab.active');
    var status = activeTab ? activeTab.getAttribute('data-status') : 'all';
    var categoryFilter = document.getElementById('categoryFilter');
    var selectedCategory = categoryFilter ? categoryFilter.value : '';
    
    var requests = document.querySelectorAll('.request-card');
    var visibleCount = 0;
    
    for (var i = 0; i < requests.length; i++) {
        var request = requests[i];
        var requestStatus = request.getAttribute('data-status');
        var requestCategory = request.getAttribute('data-category');
        
        var statusMatch = (status === 'all' || requestStatus === status);
        var categoryMatch = (!selectedCategory || requestCategory === selectedCategory);
        
        if (statusMatch && categoryMatch) {
            request.style.display = 'block';
            visibleCount++;
        } else {
            request.style.display = 'none';
        }
    }
    
    // Show/hide empty state
    var emptyState = document.querySelector('.empty-state');
    if (visibleCount === 0) {
        emptyState.style.display = 'block';
    } else {
        emptyState.style.display = 'none';
    }
    
    console.log('Filtered requests:', status, selectedCategory, 'visible:', visibleCount);
}

// Setup search functionality
function setupSearch() {
    var searchInput = document.getElementById('searchInput');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var searchTerm = this.value.toLowerCase();
            searchRequests(searchTerm);
        });
    }
}

// Search requests
function searchRequests(searchTerm) {
    var requests = document.querySelectorAll('.request-card');
    var visibleCount = 0;
    
    for (var i = 0; i < requests.length; i++) {
        var request = requests[i];
        var requestId = request.querySelector('.request-id').textContent.toLowerCase();
        var issueTitle = request.querySelector('.issue-title').textContent.toLowerCase();
        
        if (requestId.includes(searchTerm) || issueTitle.includes(searchTerm)) {
            request.style.display = 'block';
            visibleCount++;
        } else {
            request.style.display = 'none';
        }
    }
    
    // Show/hide empty state
    var emptyState = document.querySelector('.empty-state');
    if (visibleCount === 0 && searchTerm.length > 0) {
        emptyState.style.display = 'block';
        emptyState.querySelector('h3').textContent = 'Không tìm thấy yêu cầu nào';
        emptyState.querySelector('p').textContent = 'Không có yêu cầu nào khớp với từ khóa "' + searchTerm + '".';
    } else if (visibleCount > 0) {
        emptyState.style.display = 'none';
    }
}

// Load maintenance data
function loadMaintenanceData() {
    // Simulate API call to load maintenance data
    maintenanceData = {
        'YC001': {
            id: 'YC001',
            title: 'Vòi nước bị rò rỉ',
            description: 'Vòi nước trong phòng tắm bị rò rỉ, cần thay thế gấp',
            property: 'Phòng trọ cao cấp Cầu Giấy',
            category: 'plumbing',
            priority: 'high',
            status: 'processing',
            createdDate: '20/12/2023',
            technician: {
                name: 'Anh Minh',
                phone: '0987 654 321',
                avatar: 'https://ui-avatars.com/api/?name=Nguyen+Van+B&background=3b82f6&color=fff&size=40'
            },
            timeline: [
                { title: 'Yêu cầu được tạo', description: 'Bạn đã tạo yêu cầu sửa chữa', time: '20/12/2023 09:00', status: 'completed' },
                { title: 'Đã tiếp nhận', description: 'Yêu cầu đã được tiếp nhận và xử lý', time: '20/12/2023 10:30', status: 'completed' },
                { title: 'Phân công kỹ thuật viên', description: 'Anh Minh đã được phân công xử lý', time: '20/12/2023 14:00', status: 'completed' },
                { title: 'Đang sửa chữa', description: 'Kỹ thuật viên đang thực hiện sửa chữa', time: '21/12/2023 08:00', status: 'current' },
                { title: 'Hoàn thành', description: 'Sửa chữa hoàn tất, chờ xác nhận', time: '', status: 'pending' }
            ]
        },
        'YC002': {
            id: 'YC002',
            title: 'Ổ cắm điện bị hỏng',
            description: 'Ổ cắm ở phòng ngủ không hoạt động, cần kiểm tra và sửa chữa',
            property: 'Homestay Hạnh Đào',
            category: 'electrical',
            priority: 'medium',
            status: 'pending',
            createdDate: '22/12/2023',
            timeline: [
                { title: 'Yêu cầu được tạo', description: 'Bạn đã tạo yêu cầu sửa chữa', time: '22/12/2023 14:30', status: 'completed' },
                { title: 'Đang xử lý', description: 'Yêu cầu đang được xem xét', time: '', status: 'current' },
                { title: 'Phân công kỹ thuật viên', description: 'Sẽ phân công kỹ thuật viên phù hợp', time: '', status: 'pending' },
                { title: 'Thực hiện sửa chữa', description: 'Kỹ thuật viên sẽ liên hệ và sửa chữa', time: '', status: 'pending' },
                { title: 'Hoàn thành', description: 'Sửa chữa hoàn tất', time: '', status: 'pending' }
            ]
        },
        'YC003': {
            id: 'YC003',
            title: 'Máy lạnh không mát',
            description: 'Máy lạnh hoạt động nhưng không làm mát được',
            property: 'Phòng trọ cao cấp Cầu Giấy',
            category: 'appliance',
            priority: 'low',
            status: 'completed',
            createdDate: '15/12/2023',
            completedDate: '18/12/2023',
            technician: {
                name: 'Anh Cường',
                phone: '0901 234 567',
                avatar: 'https://ui-avatars.com/api/?name=Le+Van+C&background=10b981&color=fff&size=40',
                rating: 5.0
            },
            timeline: [
                { title: 'Yêu cầu được tạo', description: 'Bạn đã tạo yêu cầu sửa chữa', time: '15/12/2023 16:00', status: 'completed' },
                { title: 'Đã tiếp nhận', description: 'Yêu cầu đã được tiếp nhận', time: '16/12/2023 08:00', status: 'completed' },
                { title: 'Phân công kỹ thuật viên', description: 'Anh Cường đã được phân công', time: '16/12/2023 10:00', status: 'completed' },
                { title: 'Thực hiện sửa chữa', description: 'Đã thay gas và vệ sinh máy lạnh', time: '18/12/2023 09:00', status: 'completed' },
                { title: 'Hoàn thành', description: 'Sửa chữa hoàn tất, máy lạnh hoạt động bình thường', time: '18/12/2023 11:00', status: 'completed' }
            ]
        }
    };
    
    console.log('Maintenance data loaded');
}

// Animate stat cards
function animateStatCards() {
    var statCards = document.querySelectorAll('.stat-card');
    
    for (var i = 0; i < statCards.length; i++) {
        (function(index, card) {
            setTimeout(function() {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                
                setTimeout(function() {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 50);
            }, index * 100);
        })(i, statCards[i]);
    }
}

// Open create request modal
function openCreateRequestModal() {
    if (typeof bootstrap !== 'undefined') {
        var createModal = new bootstrap.Modal(document.getElementById('createRequestModal'));
        createModal.show();
    } else {
        alert('Tính năng tạo yêu cầu sửa chữa');
    }
}

// Setup image upload
function setupImageUpload() {
    var imageInput = document.getElementById('requestImages');
    
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            handleImageUpload(this);
        });
    }
}

// Handle image upload
function handleImageUpload(input) {
    var preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (input.files) {
        for (var i = 0; i < input.files.length; i++) {
            var file = input.files[i];
            var reader = new FileReader();
            
            reader.onload = function(e) {
                var previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.innerHTML = 
                    '<img src="' + e.target.result + '" alt="Preview">' +
                    '<button type="button" class="remove-btn" onclick="removePreviewImage(this)">' +
                        '<i class="fas fa-times"></i>' +
                    '</button>';
                preview.appendChild(previewItem);
            };
            
            reader.readAsDataURL(file);
        }
    }
}

// Remove preview image
function removePreviewImage(button) {
    button.parentElement.remove();
}

// Submit new request
function submitRequest() {
    var form = document.getElementById('createRequestForm');
    var formData = new FormData(form);
    
    // Validate required fields
    var requiredFields = ['requestProperty', 'requestCategory', 'requestPriority', 'requestTitle', 'requestDescription'];
    var isValid = true;
    
    for (var i = 0; i < requiredFields.length; i++) {
        var field = document.getElementById(requiredFields[i]);
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    }
    
    if (!isValid) {
        showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'error');
        return;
    }
    
    // Generate new request ID
    var newId = 'YC' + String(Date.now()).slice(-3);
    
    // Simulate API call
    showToast('Đang gửi yêu cầu...', 'info');
    
    setTimeout(function() {
        // Hide modal
        if (typeof bootstrap !== 'undefined') {
            var createModal = bootstrap.Modal.getInstance(document.getElementById('createRequestModal'));
            if (createModal) {
                createModal.hide();
            }
        }
        
        // Show success
        showSuccessModal('Yêu cầu đã được tạo!', 'Yêu cầu sửa chữa ' + newId + ' đã được gửi thành công. Chúng tôi sẽ liên hệ với bạn sớm.');
        
        // Reset form
        form.reset();
        document.getElementById('imagePreview').innerHTML = '';
        
        // Add new request to list (simulate)
        console.log('New request created:', newId);
    }, 2000);
}

// Track request
function trackRequest(requestId) {
    currentRequestId = requestId;
    var requestData = maintenanceData[requestId];
    
    if (!requestData) {
        showToast('Không tìm thấy thông tin yêu cầu', 'error');
        return;
    }
    
    // Show tracking modal
    if (typeof bootstrap !== 'undefined') {
        var trackModal = new bootstrap.Modal(document.getElementById('trackModal'));
        trackModal.show();
        
        // Load tracking details
        loadTrackingDetails(requestData);
    } else {
        alert('Theo dõi yêu cầu: ' + requestData.title);
    }
}

// Load tracking details
function loadTrackingDetails(requestData) {
    var content = document.getElementById('trackingContent');
    var rateBtn = document.getElementById('rateServiceBtn');
    
    // Show loading initially
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Đang tải thông tin theo dõi...</p>
        </div>
    `;
    
    // Show rate button if completed
    if (requestData.status === 'completed' && rateBtn) {
        rateBtn.style.display = 'inline-block';
    } else if (rateBtn) {
        rateBtn.style.display = 'none';
    }
    
    // Simulate loading delay
    setTimeout(function() {
        var priorityText = requestData.priority === 'high' ? 'Cao' : 
                         requestData.priority === 'medium' ? 'Trung bình' : 'Thấp';
        var priorityColor = requestData.priority === 'high' ? 'danger' : 
                           requestData.priority === 'medium' ? 'warning' : 'success';
        
        content.innerHTML = `
            <div class="tracking-header">
                <div class="tracking-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="tracking-info">
                    <h4>${requestData.title}</h4>
                    <p>Mã yêu cầu: ${requestData.id} | Mức độ: <span class="text-${priorityColor}">${priorityText}</span></p>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="info-card">
                        <h6><i class="fas fa-home me-2"></i>Thông tin phòng</h6>
                        <p><strong>Phòng:</strong> ${requestData.property}</p>
                        <p><strong>Danh mục:</strong> ${getCategoryName(requestData.category)}</p>
                        <p><strong>Ngày tạo:</strong> ${requestData.createdDate}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card">
                        <h6><i class="fas fa-info-circle me-2"></i>Mô tả vấn đề</h6>
                        <p>${requestData.description}</p>
                        ${requestData.technician ? 
                            `<p><strong>Kỹ thuật viên:</strong> ${requestData.technician.name} - ${requestData.technician.phone}</p>` : 
                            '<p><strong>Kỹ thuật viên:</strong> Chưa phân công</p>'
                        }
                    </div>
                </div>
            </div>
            
            <div class="tracking-timeline">
                <h6 class="mb-3"><i class="fas fa-history me-2"></i>Tiến trình xử lý</h6>
                ${requestData.timeline.map(function(item) {
                    return `
                        <div class="timeline-item ${item.status}">
                            <div class="timeline-content">
                                <div class="timeline-title">${item.title}</div>
                                <div class="timeline-description">${item.description}</div>
                                ${item.time ? `<div class="timeline-time"><i class="fas fa-clock"></i>${item.time}</div>` : ''}
                                ${item.status === 'current' && requestData.technician ? 
                                    `<div class="timeline-meta">
                                        <div class="timeline-tech">
                                            <img src="${requestData.technician.avatar}" alt="${requestData.technician.name}">
                                            <span>${requestData.technician.name}</span>
                                        </div>
                                        <a href="tel:${requestData.technician.phone}" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-phone"></i> Gọi điện
                                        </a>
                                    </div>` : ''
                                }
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
            
            ${requestData.status === 'completed' && !requestData.rated ? 
                `<div class="alert alert-info mt-4">
                    <h6><i class="fas fa-star me-2"></i>Đánh giá dịch vụ</h6>
                    <p>Yêu cầu đã hoàn thành. Hãy đánh giá chất lượng dịch vụ để giúp chúng tôi cải thiện!</p>
                    <button class="btn btn-primary btn-sm" onclick="rateService('${requestData.id}')">
                        <i class="fas fa-star me-1"></i>Đánh giá ngay
                    </button>
                </div>` : ''
            }
        `;
    }, 1000);
}

// Get category name
function getCategoryName(category) {
    var categories = {
        'plumbing': 'Hệ thống nước',
        'electrical': 'Điện',
        'appliance': 'Thiết bị',
        'furniture': 'Nội thất',
        'other': 'Khác'
    };
    return categories[category] || category;
}

// Rate service
function rateService(requestId) {
    currentRequestId = requestId;
    currentRating = { quality: 0, service: 0 };
    
    // Reset rating display
    var ratingContainers = document.querySelectorAll('.star-rating');
    for (var i = 0; i < ratingContainers.length; i++) {
        var stars = ratingContainers[i].querySelectorAll('i');
        for (var j = 0; j < stars.length; j++) {
            stars[j].classList.remove('active');
        }
    }
    
    var ratingTexts = document.querySelectorAll('.rating-text');
    for (var k = 0; k < ratingTexts.length; k++) {
        ratingTexts[k].textContent = 'Chưa đánh giá';
    }
    
    document.getElementById('ratingComment').value = '';
    
    // Reset recommend options
    var recommendInputs = document.querySelectorAll('input[name="recommend"]');
    for (var l = 0; l < recommendInputs.length; l++) {
        recommendInputs[l].checked = false;
    }
    
    // Show rating modal
    if (typeof bootstrap !== 'undefined') {
        var ratingModal = new bootstrap.Modal(document.getElementById('ratingModal'));
        ratingModal.show();
    }
}

// Rate current service from tracking modal
function rateCurrentService() {
    if (currentRequestId) {
        rateService(currentRequestId);
    }
}

// Setup star rating
function setupStarRating() {
    var ratingContainers = document.querySelectorAll('.star-rating');
    
    for (var i = 0; i < ratingContainers.length; i++) {
        setupSingleRating(ratingContainers[i]);
    }
}

// Setup single rating container
function setupSingleRating(container) {
    var stars = container.querySelectorAll('i');
    var ratingType = container.id === 'qualityRating' ? 'quality' : 'service';
    var ratingText = container.nextElementSibling;
    
    for (var i = 0; i < stars.length; i++) {
        stars[i].addEventListener('click', function() {
            var rating = parseInt(this.getAttribute('data-rating'));
            currentRating[ratingType] = rating;
            
            // Update star display
            for (var j = 0; j < stars.length; j++) {
                if (j < rating) {
                    stars[j].classList.add('active');
                } else {
                    stars[j].classList.remove('active');
                }
            }
            
            // Update rating text
            var ratingTexts = ['Rất tệ', 'Tệ', 'Trung bình', 'Tốt', 'Rất tốt'];
            ratingText.textContent = ratingTexts[rating - 1];
        });
        
        stars[i].addEventListener('mouseover', function() {
            var rating = parseInt(this.getAttribute('data-rating'));
            
            // Highlight stars on hover
            for (var j = 0; j < stars.length; j++) {
                if (j < rating) {
                    stars[j].style.color = '#fbbf24';
                } else {
                    stars[j].style.color = '';
                }
            }
        });
    }
    
    // Reset hover effect
    container.addEventListener('mouseleave', function() {
        var currentRatingValue = currentRating[ratingType];
        for (var i = 0; i < stars.length; i++) {
            if (i < currentRatingValue) {
                stars[i].style.color = '#fbbf24';
            } else {
                stars[i].style.color = '';
            }
        }
    });
}

// Submit rating
function submitRating() {
    var comment = document.getElementById('ratingComment').value;
    var recommendInputs = document.querySelectorAll('input[name="recommend"]');
    var recommend = null;
    
    for (var i = 0; i < recommendInputs.length; i++) {
        if (recommendInputs[i].checked) {
            recommend = recommendInputs[i].value;
            break;
        }
    }
    
    if (currentRating.quality === 0 || currentRating.service === 0) {
        showToast('Vui lòng đánh giá đầy đủ chất lượng và thái độ dịch vụ', 'error');
        return;
    }
    
    // Hide modal
    if (typeof bootstrap !== 'undefined') {
        var ratingModal = bootstrap.Modal.getInstance(document.getElementById('ratingModal'));
        if (ratingModal) {
            ratingModal.hide();
        }
    }
    
    // Simulate API call
    showToast('Đang gửi đánh giá...', 'info');
    
    setTimeout(function() {
        // Update request data
        if (maintenanceData[currentRequestId]) {
            maintenanceData[currentRequestId].rated = true;
            maintenanceData[currentRequestId].rating = {
                quality: currentRating.quality,
                service: currentRating.service,
                comment: comment,
                recommend: recommend
            };
        }
        
        showToast('Cảm ơn bạn đã đánh giá! Đánh giá của bạn giúp chúng tôi cải thiện dịch vụ.', 'success');
        
        console.log('Rating submitted:', {
            requestId: currentRequestId,
            quality: currentRating.quality,
            service: currentRating.service,
            comment: comment,
            recommend: recommend
        });
    }, 1500);
}

// Edit request
function editRequest(requestId) {
    showToast('Tính năng chỉnh sửa yêu cầu sẽ được cập nhật sớm', 'info');
}

// Cancel request
function cancelRequest(requestId) {
    if (confirm('Bạn có chắc chắn muốn hủy yêu cầu này không?')) {
        showToast('Đang hủy yêu cầu...', 'info');
        
        setTimeout(function() {
            // Update request status (simulate)
            var requestCard = document.querySelector('.request-card[data-status="pending"]');
            if (requestCard) {
                requestCard.style.opacity = '0.5';
                requestCard.querySelector('.request-status').innerHTML = 
                    '<i class="fas fa-times-circle"></i><span>Đã hủy</span>';
                requestCard.querySelector('.request-status').className = 'request-status cancelled';
            }
            
            showToast('Đã hủy yêu cầu thành công', 'success');
        }, 1000);
    }
}

// Download report
function downloadReport(requestId) {
    var requestData = maintenanceData[requestId];
    
    if (!requestData) {
        showToast('Không tìm thấy thông tin yêu cầu', 'error');
        return;
    }
    
    showToast('Đang tạo báo cáo...', 'info');
    
    setTimeout(function() {
        // Generate report content
        var reportContent = generateReport(requestData);
        var blob = new Blob([reportContent], { type: 'text/plain' });
        
        // Create download link
        var url = window.URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'bao-cao-sua-chua-' + requestData.id.toLowerCase() + '.txt';
        
        // Trigger download
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Clean up
        window.URL.revokeObjectURL(url);
        
        showToast('Đã tải xuống báo cáo thành công!', 'success');
    }, 2000);
}

// Generate report content
function generateReport(requestData) {
    return `
BÁO CÁO SỬA CHỮA
================

Mã yêu cầu: ${requestData.id}
Tiêu đề: ${requestData.title}
Mô tả: ${requestData.description}

THÔNG TIN CHI TIẾT:
- Phòng: ${requestData.property}
- Danh mục: ${getCategoryName(requestData.category)}
- Mức độ ưu tiên: ${requestData.priority}
- Trạng thái: ${requestData.status}
- Ngày tạo: ${requestData.createdDate}
${requestData.completedDate ? '- Ngày hoàn thành: ' + requestData.completedDate : ''}

${requestData.technician ? 
  `KỸ THUẬT VIÊN:
- Tên: ${requestData.technician.name}
- Số điện thoại: ${requestData.technician.phone}
${requestData.technician.rating ? '- Đánh giá: ' + requestData.technician.rating + '/5 sao' : ''}` : ''
}

TIẾN TRÌNH XỬ LÝ:
${requestData.timeline.map(function(item, index) {
    return `${index + 1}. ${item.title} ${item.time ? '(' + item.time + ')' : ''}
   ${item.description}`;
}).join('\n')}

${requestData.rating ? 
  `ĐÁNH GIÁ DỊCH VỤ:
- Chất lượng: ${requestData.rating.quality}/5 sao
- Thái độ: ${requestData.rating.service}/5 sao
- Nhận xét: ${requestData.rating.comment}
- Giới thiệu: ${requestData.rating.recommend === 'yes' ? 'Có' : requestData.rating.recommend === 'no' ? 'Không' : 'Có thể'}` : ''
}

Ngày tạo báo cáo: ${new Date().toLocaleDateString('vi-VN')}
    `;
}

// Show success modal
function showSuccessModal(title, message) {
    document.getElementById('successTitle').textContent = title;
    document.getElementById('successMessage').textContent = message;
    
    if (typeof bootstrap !== 'undefined') {
        var successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
    } else {
        alert(title + '\n' + message);
    }
}

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
        document.body.appendChild(toastContainer);
    }
    
    var toast = document.createElement('div');
    toast.className = 'custom-toast ' + type;
    
    var icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    else if (type === 'error') icon = 'times-circle';
    else if (type === 'warning') icon = 'exclamation-triangle';
    
    toast.innerHTML = '<i class="fas fa-' + icon + '"></i><span>' + message + '</span>';
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 4 seconds
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
    }, 4000);
}
