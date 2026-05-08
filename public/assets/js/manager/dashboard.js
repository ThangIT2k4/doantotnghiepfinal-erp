/**
 * DOMContentLoaded Event Listener
 * MỤC ĐÍCH: Khởi tạo dashboard khi DOM đã load xong
 * 
 * LUỒNG XỬ LÝ:
 * 1. Cache HTML ban đầu của dashboard → Dùng để restore nhanh khi cần
 * 2. Khởi tạo sidebar (toggle, collapse, mobile support)
 * 3. Khởi tạo navigation (menu items, submenus)
 * 4. Khởi tạo biểu đồ doanh thu
 * 5. Update stats với animation
 * 6. Tự động refresh stats mỗi 30 giây
 */
document.addEventListener('DOMContentLoaded', function() {
    // Cache HTML ban đầu của dashboard → Dùng để restore nhanh khi navigate về dashboard
    try {
        const contentEl = document.getElementById('content'); // Tìm element chứa nội dung dashboard
        if (contentEl && !window.__dashboardInitialHTML) {
            // Lưu HTML ban đầu vào biến global → Dùng để restore mà không cần fetch lại
            window.__dashboardInitialHTML = contentEl.innerHTML;
        }
    } catch (_) {} // Bỏ qua lỗi nếu không tìm thấy element
    
    // Khởi tạo các chức năng của dashboard
    initSidebar(); // Khởi tạo sidebar: toggle, collapse, mobile support
    initNavigation(); // Khởi tạo navigation: menu items, submenus
    initChart(); // Khởi tạo biểu đồ doanh thu và hoa hồng
    updateStats(); // Update stats với animation
    
    // Tự động refresh stats mỗi 30 giây → Đảm bảo dữ liệu luôn mới nhất
    setInterval(updateStats, 30000);
});

/**
 * Function: initSidebar()
 * MỤC ĐÍCH: Khởi tạo chức năng sidebar (toggle, collapse, mobile support)
 * 
 * LUỒNG XỬ LÝ:
 * 1. Tìm sidebar và sidebarToggle elements
 * 2. Thêm event listener cho toggle button
 * 3. Lưu/restore state từ localStorage
 * 4. Xử lý mobile sidebar (auto-close khi click outside)
 * 5. Xử lý window resize (auto-collapse trên mobile)
 */
function initSidebar() {
    // Tìm sidebar và toggle button → Dùng để điều khiển sidebar
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (!sidebar || !sidebarToggle) {
        return;
    }
    
    // Thêm event listener cho toggle button → Khi click sẽ collapse/expand sidebar
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed'); // Toggle class 'collapsed' → CSS sẽ xử lý hiển thị
        
        // Đóng tất cả submenus khi collapse → Tránh submenu bị lộ khi sidebar thu nhỏ
        if (sidebar.classList.contains('collapsed')) {
            document.querySelectorAll('.submenu').forEach(menu => {
                menu.classList.remove('open'); // Đóng tất cả submenus
            });
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active'); // Bỏ active class khỏi tất cả nav items
            });
        }
        
        // Lưu state vào localStorage → Dùng để restore state khi reload trang
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed); // Lưu 'true' hoặc 'false'
        
        // Thêm animation class → Tạo hiệu ứng mượt mà khi toggle
        sidebar.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    });
    
    // Restore sidebar state từ localStorage → Khôi phục trạng thái đã lưu
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true') {
        sidebar.classList.add('collapsed'); // Nếu đã collapse trước đó thì collapse lại
    }
    
    // Mobile sidebar toggle: Tự động đóng khi click bên ngoài sidebar
    if (window.innerWidth <= 768) {
        document.addEventListener('click', function(e) {
            // Nếu click không phải trong sidebar và không phải toggle button → Đóng sidebar
            if (!sidebar.contains(e.target) && !e.target.closest('.sidebar-toggle')) {
                sidebar.classList.remove('open'); // Đóng sidebar trên mobile
            }
        });
    }
    
    // Xử lý window resize: Tự động collapse sidebar trên mobile
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('collapsed'); // Tự động collapse khi màn hình nhỏ
        }
    });
}

/**
 * Function: initNavigation()
 * MỤC ĐÍCH: Khởi tạo chức năng navigation (menu items, submenus)
 * 
 * LUỒNG XỬ LÝ:
 * 1. Tìm tất cả nav items
 * 2. Thêm event listener cho mỗi nav item
 * 3. Nếu có submenu thì toggle submenu, nếu không thì navigate bình thường
 */
function initNavigation() {
    // Staff layout already has dedicated sidebar navigation logic in header_erp.
    // Skip legacy navigation handlers here to avoid double toggle/state conflicts.
    if (document.getElementById('staffNotificationNavItem')) {
        return;
    }

    // Tìm tất cả nav items → Dùng để thêm event listeners
    const navItems = document.querySelectorAll('.nav-item');
    const sidebar = document.getElementById('sidebar');
    
    // Thêm event listener cho mỗi nav item → Xử lý click vào menu
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Khi sidebar đang thu gọn trên desktop, click icon sẽ bung sidebar trước
            if (sidebar && sidebar.classList.contains('collapsed') && window.innerWidth > 768) {
                e.preventDefault();
                sidebar.classList.remove('collapsed');
                localStorage.setItem('sidebarCollapsed', 'false');
                return;
            }

            // Kiểm tra nếu nav item có submenu → Nếu có thì toggle submenu thay vì navigate
            if (this.classList.contains('has-submenu')) {
                e.preventDefault(); // Ngăn navigate → Chỉ toggle submenu
                toggleSubmenu(this); // Gọi function toggle submenu
            }
            // Nếu không có submenu, để browser navigate tự nhiên (không preventDefault)
        });
    });
}

/**
 * Function: toggleSubmenu()
 * MỤC ĐÍCH: Toggle (mở/đóng) submenu của một nav item
 * 
 * LUỒNG XỬ LÝ:
 * 1. Tìm submenu element (nextElementSibling)
 * 2. Đóng tất cả submenus khác (chỉ mở 1 submenu tại một thời điểm)
 * 3. Bỏ active class khỏi tất cả nav items
 * 4. Toggle submenu hiện tại (mở nếu đang đóng, đóng nếu đang mở)
 * 
 * @param {HTMLElement} navItem - Nav item element cần toggle submenu
 */
function toggleSubmenu(navItem) {
    // Tìm submenu element (element ngay sau nav item) → Dùng để toggle
    const submenu = navItem.nextElementSibling;
    const isOpen = submenu.classList.contains('open'); // Kiểm tra submenu đang mở hay đóng
    
    // Đóng tất cả submenus khác → Chỉ mở 1 submenu tại một thời điểm
    document.querySelectorAll('.submenu').forEach(menu => {
        menu.classList.remove('open'); // Đóng tất cả submenus
    });
    
    // Bỏ active class khỏi tất cả nav items → Chỉ highlight nav item đang active
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active'); // Bỏ active class
    });
    
    // Toggle submenu hiện tại → Mở nếu đang đóng, đóng nếu đang mở
    if (!isOpen) {
        submenu.classList.add('open'); // Mở submenu
        navItem.classList.add('active'); // Highlight nav item
    }
}

/**
 * Function: loadPageContent()
 * MỤC ĐÍCH: Load nội dung trang dựa trên navigation (Router approach)
 * 
 * LUỒNG XỬ LÝ:
 * 1. Định nghĩa routes mapping (page name → URL)
 * 2. Tìm route tương ứng với page name
 * 3. Navigate đến route hoặc fallback về dashboard
 * 
 * @param {string} page - Tên trang cần load (ví dụ: 'properties')
 */
function loadPageContent(page) {
    // Định nghĩa routes mapping → Map page name sang URL
    const routes = {
        'properties': '/manager/properties', // Route cho trang properties
    };
    
    // Tìm route tương ứng với page name → Dùng để navigate
    const route = routes[page];
    
    if (route) {
        // Navigate đến route → Chuyển đến URL tương ứng
        window.location.href = route;
    } else {
        // Fallback: Nếu không tìm thấy route → Chuyển về dashboard
        console.warn(`Route not found for page: ${page}`);
        window.location.href = '/manager/dashboard';
    }
}

/**
 * Function: restoreDashboard()
 * MỤC ĐÍCH: Khôi phục dashboard về trạng thái ban đầu
 * 
 * LUỒNG XỬ LÝ:
 * 1. Thử restore từ cached HTML (nhanh nhất)
 * 2. Nếu không có cache, fetch dashboard từ server
 * 3. Extract #content từ HTML response
 * 4. Re-init chart và stats
 * 5. Nếu lỗi, hiển thị thông báo
 */
function restoreDashboard(){
    const content = document.getElementById('content'); // Tìm element chứa nội dung
    
    // Bước 1: Thử restore từ cached HTML → Nhanh nhất, không cần fetch
    if (window.__dashboardInitialHTML && typeof window.__dashboardInitialHTML === 'string' && window.__dashboardInitialHTML.length) {
        content.innerHTML = window.__dashboardInitialHTML; // Restore HTML từ cache
        initChart(); // Re-init chart vì HTML đã thay đổi
        updateStats(); // Update stats
        return; // Dừng, không cần fetch
    }

    // Bước 2: Fallback - Fetch dashboard từ server → Nếu không có cache
    fetch('/agent/dashboard', { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(r => r.text()) // Parse HTML response
        .then(html => {
            // Tạo temporary div để parse HTML → Dùng để extract #content
            const tmp = document.createElement('div');
            tmp.innerHTML = html; // Parse HTML string
            const newContent = tmp.querySelector('#content'); // Tìm #content element
            if (newContent) {
                content.innerHTML = newContent.innerHTML; // Restore HTML từ server
                // Cache HTML cho lần sau → Lần sau sẽ restore nhanh hơn
                window.__dashboardInitialHTML = newContent.innerHTML;
                initChart(); // Re-init chart
                updateStats(); // Update stats
            }
        })
        .catch(() => {
            // Xử lý lỗi: Hiển thị thông báo nếu không thể fetch
            content.innerHTML = '<div class="alert alert-warning">Không thể tải lại dashboard. Vui lòng tải lại trang.</div>';
        });
}

// Router-based navigation - content creation functions removed
// All navigation now uses actual routes instead of dynamic content

/**
 * BIỂU ĐỒ DOANH THU VÀ HOA HỒNG
 * 
 * MỤC ĐÍCH:
 * - Khởi tạo và hiển thị biểu đồ doanh thu và hoa hồng 6 tháng gần nhất
 * - Sử dụng Chart.js để vẽ biểu đồ line chart với 2 datasets: Doanh thu và Hoa hồng
 * - Load dữ liệu từ API endpoint /staff/dashboard/revenue-chart
 * 
 * LUỒNG XỬ LÝ:
 * 1. Kiểm tra canvas element có tồn tại không
 * 2. Destroy chart cũ nếu đã tồn tại (tránh memory leak)
 * 3. Hiển thị loading state
 * 4. Fetch dữ liệu từ API
 * 5. Format labels sang tiếng Việt (Tháng 1, Tháng 2, ...)
 * 6. Tạo Chart.js instance với 2 datasets (Doanh thu và Hoa hồng)
 * 7. Xử lý lỗi nếu có
 * 
 * DỮ LIỆU TỪ API:
 * - Endpoint: /staff/dashboard/revenue-chart
 * - Response: { success: true, data: { labels: ['01/2024', ...], revenue: [1.5, ...], commission: [0.8, ...] } }
 * - labels: Array 6 phần tử (6 tháng gần nhất, format: mm/yyyy)
 * - revenue: Array 6 phần tử (doanh thu từng tháng, đơn vị: triệu VND)
 * - commission: Array 6 phần tử (hoa hồng từng tháng, đơn vị: triệu VND)
 */
let revenueChart = null; // Biến global để lưu Chart.js instance → Dùng để destroy khi cần

/**
 * Function: initChart()
 * MỤC ĐÍCH: Khởi tạo biểu đồ doanh thu và hoa hồng
 * 
 * LUỒNG XỬ LÝ:
 * 1. Tìm canvas element với id="revenueChart"
 * 2. Destroy chart cũ nếu đã tồn tại
 * 3. Hiển thị loading indicator
 * 4. Fetch dữ liệu từ API /staff/dashboard/revenue-chart
 * 5. Format labels sang tiếng Việt
 * 6. Tạo Chart.js instance với 2 datasets
 * 7. Xử lý lỗi nếu có
 */
function initChart() {
    // Tìm canvas element → Dùng để vẽ biểu đồ
    const canvas = document.getElementById('revenueChart');
    if (!canvas) return; // Nếu không tìm thấy canvas thì dừng → Tránh lỗi
    
    // Lấy 2D context từ canvas → Dùng để vẽ biểu đồ Chart.js
    const ctx = canvas.getContext('2d');
    
    // Destroy chart cũ nếu đã tồn tại → Tránh memory leak và conflict khi re-init
    if (revenueChart) {
        revenueChart.destroy();
    }
    
    // Hiển thị loading state → Thông báo cho user biết đang tải dữ liệu
    const chartContainer = canvas.closest('.chart-container') || canvas.parentElement;
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'chart-loading';
    loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...';
    if (chartContainer) {
        chartContainer.appendChild(loadingDiv); // Thêm loading indicator vào container
    }
    
    // Fetch dữ liệu từ API endpoint → Lấy dữ liệu doanh thu và hoa hồng 6 tháng gần nhất
    fetch('/staff/dashboard/revenue-chart', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest', // Header để Laravel nhận biết đây là AJAX request
            'Accept': 'application/json' // Chỉ nhận JSON response
        }
    })
    .then(response => response.json()) // Parse JSON response → Convert sang JavaScript object
    .then(data => {
        // Xóa loading indicator → Dữ liệu đã load xong
        if (loadingDiv.parentElement) {
            loadingDiv.remove();
        }
        
        // Kiểm tra response có success và data không → Đảm bảo dữ liệu hợp lệ
        if (data.success && data.data) {
            const chartData = data.data; // Lấy data object từ response
            
            // Format labels sang tiếng Việt → Dùng để hiển thị trên trục X
            // Input: ['01/2024', '02/2024', ...] → Output: ['Tháng 1 2024', 'Tháng 2 2024', ...]
            const monthNames = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 
                               'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
            const formattedLabels = chartData.labels.map(label => {
                const [month, year] = label.split('/'); // Tách "01/2024" thành ["01", "2024"]
                return `${monthNames[parseInt(month) - 1]} ${year}`; // Convert "01" → "Tháng 1"
            });
            
            // Tạo Chart.js revenue chart với styling đẹp → Hiển thị biểu đồ line chart
            revenueChart = new Chart(ctx, {
                type: 'line', // Loại biểu đồ: Line chart → Hiển thị xu hướng theo thời gian
                data: {
                    labels: formattedLabels, // Labels cho trục X (6 tháng)
                    datasets: [
                        {
                            // Dataset 1: Doanh thu
                            label: 'Doanh thu', // Tên hiển thị trong legend
                            data: chartData.revenue, // Dữ liệu doanh thu (đơn vị: triệu VND)
                            borderColor: '#667eea', // Màu đường line (tím)
                            backgroundColor: 'rgba(102, 126, 234, 0.1)', // Màu fill (tím nhạt)
                            tension: 0.4, // Độ cong của đường line (0.4 = smooth curve)
                            fill: true, // Fill area dưới đường line → Tạo hiệu ứng đẹp
                            borderWidth: 3, // Độ dày đường line
                            pointRadius: 5, // Kích thước điểm trên line
                            pointHoverRadius: 7, // Kích thước điểm khi hover (lớn hơn)
                            pointBackgroundColor: '#667eea', // Màu điểm
                            pointBorderColor: '#ffffff', // Màu viền điểm
                            pointBorderWidth: 2, // Độ dày viền điểm
                            pointHoverBackgroundColor: '#764ba2', // Màu điểm khi hover
                            pointHoverBorderColor: '#ffffff', // Màu viền điểm khi hover
                            pointHoverBorderWidth: 3 // Độ dày viền điểm khi hover
                        },
                        {
                            // Dataset 2: Hoa hồng
                            label: 'Hoa hồng', // Tên hiển thị trong legend
                            data: chartData.commission, // Dữ liệu hoa hồng (đơn vị: triệu VND)
                            borderColor: '#10b981', // Màu đường line (xanh lá)
                            backgroundColor: 'rgba(16, 185, 129, 0.1)', // Màu fill (xanh lá nhạt)
                            tension: 0.4, // Độ cong của đường line
                            fill: true, // Fill area dưới đường line
                            borderWidth: 3, // Độ dày đường line
                            pointRadius: 5, // Kích thước điểm
                            pointHoverRadius: 7, // Kích thước điểm khi hover
                            pointBackgroundColor: '#10b981', // Màu điểm
                            pointBorderColor: '#ffffff', // Màu viền điểm
                            pointBorderWidth: 2, // Độ dày viền điểm
                            pointHoverBackgroundColor: '#059669', // Màu điểm khi hover
                            pointHoverBorderColor: '#ffffff', // Màu viền điểm khi hover
                            pointHoverBorderWidth: 3 // Độ dày viền điểm khi hover
                        }
                    ]
                },
                options: {
                    responsive: true, // Tự động resize theo kích thước container
                    maintainAspectRatio: false, // Không giữ tỷ lệ → Cho phép resize tự do
                    interaction: {
                        mode: 'index', // Hiển thị tooltip cho tất cả datasets cùng index
                        intersect: false // Tooltip hiển thị khi hover gần điểm, không cần chính xác
                    },
                    plugins: {
                        // Legend: Hiển thị chú thích (Doanh thu, Hoa hồng)
                        legend: {
                            display: true, // Hiển thị legend
                            position: 'top', // Vị trí legend: trên cùng
                            labels: {
                                usePointStyle: true, // Dùng point style thay vì box
                                padding: 15, // Khoảng cách giữa các label
                                font: {
                                    size: 13, // Kích thước font
                                    weight: '500' // Độ đậm font
                                },
                                color: '#64748b' // Màu chữ
                            }
                        },
                        // Tooltip: Hiển thị thông tin khi hover
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)', // Màu nền tooltip (đen mờ)
                            padding: 12, // Khoảng cách bên trong tooltip
                            titleFont: {
                                size: 14, // Kích thước font tiêu đề
                                weight: '600' // Độ đậm font tiêu đề
                            },
                            bodyFont: {
                                size: 13 // Kích thước font nội dung
                            },
                            borderColor: 'rgba(255, 255, 255, 0.1)', // Màu viền tooltip
                            borderWidth: 1, // Độ dày viền
                            cornerRadius: 8, // Bo góc tooltip
                            displayColors: true, // Hiển thị màu của dataset
                            callbacks: {
                                // Custom format label trong tooltip → Hiển thị giá trị đầy đủ (VND)
                                label: function(context) {
                                    let label = context.dataset.label || ''; // Lấy tên dataset (Doanh thu/Hoa hồng)
                                    if (label) {
                                        label += ': '; // Thêm dấu ": "
                                    }
                                    // Format giá trị: Nhân với 1,000,000 (vì data là triệu) và format VND
                                    label += new Intl.NumberFormat('vi-VN', {
                                        style: 'currency', // Format tiền tệ
                                        currency: 'VND', // Đơn vị VND
                                        minimumFractionDigits: 0, // Không có số thập phân
                                        maximumFractionDigits: 0 // Không có số thập phân
                                    }).format(context.parsed.y * 1000000); // Nhân với 1,000,000 để convert triệu → VND
                                    return label; // Trả về label đã format: "Doanh thu: 1,500,000 VND"
                                }
                            }
                        }
                    },
                    scales: {
                        // Trục X: Hiển thị tháng
                        x: {
                            grid: {
                                display: false // Ẩn grid lines trên trục X
                            },
                            ticks: {
                                font: {
                                    size: 12 // Kích thước font
                                },
                                color: '#64748b' // Màu chữ
                            }
                        },
                        // Trục Y: Hiển thị giá trị (triệu VND)
                        y: {
                            beginAtZero: true, // Bắt đầu từ 0 → Hiển thị đầy đủ range
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)', // Màu grid lines (xám nhạt)
                                drawBorder: false // Không vẽ border
                            },
                            ticks: {
                                // Custom format: Hiển thị "1.5M" thay vì "1.5"
                                callback: function(value) {
                                    return value.toFixed(1) + 'M'; // Format: "1.5M"
                                },
                                font: {
                                    size: 12 // Kích thước font
                                },
                                color: '#64748b', // Màu chữ
                                padding: 10 // Khoảng cách padding
                            }
                        }
                    },
                    // Animation: Hiệu ứng khi chart load
                    animation: {
                        duration: 1000, // Thời gian animation: 1 giây
                        easing: 'easeInOutQuart' // Kiểu easing: smooth
                    }
                }
            });
        } else {
            // Xử lý khi response không có data hoặc success = false
            // Xóa loading indicator
            if (loadingDiv && loadingDiv.parentElement) {
                loadingDiv.remove();
            }
            // Hiển thị error message
            if (chartContainer) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'chart-error';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Không thể tải dữ liệu biểu đồ';
                chartContainer.appendChild(errorDiv);
            }
        }
    })
    .catch(error => {
        // Xử lý lỗi network hoặc parse JSON
        console.error('Error loading chart data:', error);
        // Xóa loading indicator
        if (loadingDiv && loadingDiv.parentElement) {
            loadingDiv.remove();
        }
        // Hiển thị error message
        if (chartContainer) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'chart-error';
            errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Lỗi khi tải dữ liệu';
            chartContainer.appendChild(errorDiv);
        }
    });
}

/**
 * Function: updateStats()
 * MỤC ĐÍCH: Update stats với animation (pulse effect)
 * 
 * LUỒNG XỬ LÝ:
 * 1. Tìm tất cả stat-value elements
 * 2. Thêm pulse animation cho mỗi stat (scale 1.05 → 1)
 * 3. Animation kéo dài 200ms
 */
function updateStats() {
    // Tìm tất cả stat-value elements → Dùng để thêm animation
    const statValues = document.querySelectorAll('.stat-value');
    
    statValues.forEach(stat => {
        // Thêm pulse animation: Scale lên 1.05 → Tạo hiệu ứng nhấp nháy
        stat.style.transform = 'scale(1.05)'; // Phóng to 5%
        setTimeout(() => {
            stat.style.transform = 'scale(1)'; // Trở về kích thước bình thường sau 200ms
        }, 200);
    });
}

/**
 * UTILITY FUNCTIONS
 * Các hàm tiện ích để format số, tiền tệ, và thời gian
 */

/**
 * Function: formatNumber()
 * MỤC ĐÍCH: Format số theo định dạng Việt Nam (thêm dấu phẩy ngăn cách hàng nghìn)
 * 
 * @param {number} num - Số cần format
 * @returns {string} Số đã format (ví dụ: 1,500,000)
 * 
 * VÍ DỤ:
 * formatNumber(1500000) → "1.500.000"
 */
function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num); // Format theo locale vi-VN
}

/**
 * Function: formatCurrency()
 * MỤC ĐÍCH: Format số tiền theo định dạng VND (thêm đơn vị VND)
 * 
 * @param {number} amount - Số tiền cần format
 * @returns {string} Số tiền đã format (ví dụ: 1.500.000 ₫)
 * 
 * VÍ DỤ:
 * formatCurrency(1500000) → "1.500.000 ₫"
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency', // Format tiền tệ
        currency: 'VND' // Đơn vị VND
    }).format(amount);
}

/**
 * Function: timeAgo()
 * MỤC ĐÍCH: Tính thời gian đã trôi qua từ một thời điểm (hiển thị dạng "X phút/giờ/ngày trước")
 * 
 * LUỒNG XỬ LÝ:
 * 1. Tính khoảng cách thời gian (diff) giữa now và date
 * 2. Convert sang phút, giờ, hoặc ngày
 * 3. Trả về string tương ứng
 * 
 * @param {Date} date - Thời điểm cần tính
 * @returns {string} Thời gian đã trôi qua (ví dụ: "5 phút trước", "2 giờ trước", "3 ngày trước")
 * 
 * VÍ DỤ:
 * timeAgo(new Date(Date.now() - 300000)) → "5 phút trước"
 * timeAgo(new Date(Date.now() - 7200000)) → "2 giờ trước"
 */
function timeAgo(date) {
    const now = new Date(); // Thời gian hiện tại
    const diff = now - date; // Khoảng cách thời gian (milliseconds)
    const minutes = Math.floor(diff / 60000); // Convert sang phút
    const hours = Math.floor(diff / 3600000); // Convert sang giờ
    const days = Math.floor(diff / 86400000); // Convert sang ngày
    
    // Trả về string tương ứng với khoảng thời gian
    if (minutes < 60) {
        return `${minutes} phút trước`; // Nếu < 60 phút → Hiển thị phút
    } else if (hours < 24) {
        return `${hours} giờ trước`; // Nếu < 24 giờ → Hiển thị giờ
    } else {
        return `${days} ngày trước`; // Nếu >= 24 giờ → Hiển thị ngày
    }
}

/**
 * RESPONSIVE BEHAVIOR
 * Xử lý responsive cho sidebar khi resize window
 */
window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    
    // Nếu màn hình <= 768px (mobile) → Bỏ collapsed class để sidebar hiển thị đầy đủ
    if (window.innerWidth <= 768) {
        sidebar.classList.remove('collapsed'); // Hiển thị sidebar đầy đủ trên mobile
    }
});

/**
 * QUICK ACTION BUTTONS INTERACTIVITY
 * Thêm hiệu ứng click cho quick action buttons
 */
document.addEventListener('click', function(e) {
    // Kiểm tra nếu click vào quick-action-btn hoặc element bên trong nó
    if (e.target.matches('.quick-action-btn') || e.target.closest('.quick-action-btn')) {
        // Lấy button element (có thể là e.target hoặc parent)
        const button = e.target.matches('.quick-action-btn') ? e.target : e.target.closest('.quick-action-btn');
        
        // Thêm click effect: Scale xuống 0.95 → Tạo hiệu ứng nhấn
        button.style.transform = 'scale(0.95)'; // Thu nhỏ 5%
        setTimeout(() => {
            button.style.transform = 'scale(1)'; // Trở về kích thước bình thường sau 150ms
        }, 150);
        
        // Log action (placeholder) → Có thể thêm notification hoặc action khác
        console.log('Quick action clicked:', button.textContent.trim());
    }
});

/**
 * DYNAMIC CSS INJECTION
 * Thêm CSS động cho page header và các elements
 * MỤC ĐÍCH: Đảm bảo styling nhất quán mà không cần thêm vào file CSS riêng
 */
const style = document.createElement('style'); // Tạo style element
style.textContent = `
    /* Page header styling */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 32px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .page-header h2 {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }
    
    /* Stat value transition → Dùng cho animation */
    .stat-value {
        transition: transform 0.2s ease;
    }
    
    /* Quick action button transition → Dùng cho click effect */
    .quick-action-btn {
        transition: transform 0.15s ease;
    }
`;
document.head.appendChild(style); // Thêm style vào <head> → CSS sẽ được áp dụng
