<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
               <img src="{{ asset('assets/image/logo2.svg') }}" alt="ZoroRMS Logo" style="width: 50px; height: 50px; object-fit: contain;">
                <div class="logo-content">
                    {{-- <span class="logo-text">Bảng điều khiển nhân viên</span> --}}
                    @php
                        $currentUser = auth()->user();
                        $organizationId = $currentUser->getCurrentOrganizationId();
                        $userRoles = $organizationId ? $currentUser->organizationRoles($organizationId)->get() : collect();
                        $fullName = $currentUser->userProfile?->full_name ?? $currentUser->email;
                    @endphp
                    @if($fullName || $userRoles->count() > 0)
                        <div class="user-info">
                            @if($fullName)
                                <span class="user-name">{{ $fullName }}</span>
                            @endif
                            @if($userRoles->count() > 0)
                                <div class="user-roles">
                                    @foreach($userRoles as $role)
                                        <span class="badge badge-sm bg-info">{{ $role->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle" type="button" title="Đóng/mở sidebar" aria-label="Đóng/mở sidebar">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <!-- Dashboard -->
            <a href="{{ route('staff.dashboard') }}" class="nav-item {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <!-- Thông báo -->
            <a href="{{ route('staff.notifications.index') }}" class="nav-item {{ request()->routeIs('staff.notifications.*') ? 'active' : '' }}" id="staffNotificationNavItem">
                <i class="fas fa-bell" id="staffNotificationIcon"></i>
                <span>Thông báo</span>
                <span class="notification-badge" id="staffNotificationBadge" style="display: none;">0</span>
            </a>
            
            <!-- Chat với AI -->
            <a href="{{ route('chat.index') }}" class="nav-item {{ request()->routeIs('chat.*') ? 'active' : '' }}">
                <i class="fas fa-robot"></i>
                <span>Chat với AI</span>
            </a>
            
            <!-- PARTY MANAGEMENT MODULE -->
            <div class="nav-group" data-group="party">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.users.*') || request()->routeIs('staff.vendors.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Quản lý người dùng</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.users.index') }}" class="submenu-item">
                        <i class="fas fa-list"></i>
                        <span>Người dùng</span>
                    </a>
                    <a href="{{ route('staff.vendors.index') }}" class="submenu-item">
                        <i class="fas fa-building"></i>
                        <span>Nhà cung cấp</span>
                    </a>
                </div>
            </div>
            
            <!-- CRM MODULE -->
            <div class="nav-group" data-group="crm">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.leads.*') || request()->routeIs('staff.reviews.*') || request()->routeIs('staff.viewings.*') || request()->routeIs('staff.tenants.*') ? 'active' : '' }}">
                    <i class="fas fa-handshake"></i>
                    <span>CRM</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.leads.index') }}" class="submenu-item">
                        <i class="fas fa-user-plus"></i>
                        <span>Khách hàng tiềm năng</span>
                    </a>
                    <a href="{{ route('staff.tenants.index') }}" class="submenu-item">
                        <i class="fas fa-user-friends"></i>
                        <span>Khách thuê</span>
                    </a>
                    <a href="{{ route('staff.viewings.index') }}" class="submenu-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>Lịch xem phòng</span>
                    </a>
                    <a href="{{ route('staff.reviews.index') }}" class="submenu-item">
                        <i class="fas fa-star"></i>
                        <span>Đánh giá</span>
                    </a>
                </div>
            </div>
            
            <!-- ASSET MANAGEMENT MODULE -->
            <div class="nav-group" data-group="asset">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.properties.*') || request()->routeIs('staff.units.*') || request()->routeIs('staff.meters.*') || request()->routeIs('staff.meter-readings.*') || request()->routeIs('staff.property-types.*') ? 'active' : '' }}">
                    <i class="fas fa-building"></i>
                    <span>Quản lý tài sản</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.properties.index') }}" class="submenu-item">
                        <i class="fas fa-list"></i>
                        <span>Bất động sản</span>
                    </a>
                    <a href="{{ route('staff.units.index') }}" class="submenu-item">
                        <i class="fas fa-home"></i>
                        <span>Phòng/Căn hộ</span>
                    </a>
                    <a href="{{ route('staff.property-types.index') }}" class="submenu-item">
                        <i class="fas fa-tags"></i>
                        <span>Loại bất động sản</span>
                    </a>
                    <a href="{{ route('staff.meters.index') }}" class="submenu-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Đồng hồ</span>
                    </a>
                    <a href="{{ route('staff.meter-readings.index') }}" class="submenu-item">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Chỉ số đồng hồ</span>
                    </a>
                </div>
            </div>
            
            <!-- CONTRACT MODULE -->
            <div class="nav-group" data-group="contract">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.leases.*') || request()->routeIs('staff.booking-deposits.*') || request()->routeIs('staff.deposit-refunds.*') || request()->routeIs('staff.master-leases.*') ? 'active' : '' }}">
                    <i class="fas fa-file-contract"></i>
                    <span>Hợp đồng</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.leases.index') }}" class="submenu-item">
                        <i class="fas fa-file-contract"></i>
                        <span>Hợp đồng thuê</span>
                    </a>
                    <a href="{{ route('staff.master-leases.index') }}" class="submenu-item">
                        <i class="fas fa-building"></i>
                        <span>Hợp đồng chính</span>
                    </a>
                    <a href="{{ route('staff.booking-deposits.index') }}" class="submenu-item">
                        <i class="fas fa-credit-card"></i>
                        <span>Đặt cọc</span>
                    </a>
                    <a href="{{ route('staff.deposit-refunds.index') }}" class="submenu-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Hoàn tiền cọc</span>
                    </a>
                </div>
            </div>
            
            <!-- FINANCE MODULE -->
            <div class="nav-group" data-group="finance">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.company-invoices.*') || request()->routeIs('staff.invoices.*') || request()->routeIs('staff.payments.*') || request()->routeIs('staff.cash-outflows.*') || request()->routeIs('staff.financial-*') ? 'active' : '' }}">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Tài chính</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.company-invoices.index') }}" class="submenu-item">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Hóa đơn công ty</span>
                    </a>
                    <a href="{{ route('staff.invoices.index') }}" class="submenu-item">
                        <i class="fas fa-file-invoice"></i>
                        <span>Hóa đơn</span>
                    </a>
                    <a href="{{ route('staff.payments.index') }}" class="submenu-item">
                        <i class="fas fa-credit-card"></i>
                        <span>Thanh toán</span>
                    </a>
                    {{--
                    <a href="{{ route('staff.cash-outflows.index') }}" class="submenu-item">
                        <i class="fas fa-arrow-down"></i>
                        <span>Chi tiêu</span>
                    </a>
                    <a href="{{ route('staff.financial-management.index') }}" class="submenu-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Quản lý tài chính</span>
                    </a>
                    --}}
                </div>
            </div>
            
            <!-- WORK MANAGEMENT MODULE -->
            <div class="nav-group" data-group="work">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.tickets.*') ? 'active' : '' }}">
                    <i class="fas fa-tasks"></i>
                    <span>Quản lý công việc</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.tickets.index') }}" class="submenu-item">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Phiếu yêu cầu</span>
                    </a>
                </div>
            </div>
            
            <!-- HR MODULE -->
            <div class="nav-group" data-group="hr">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.staff.*') || request()->routeIs('staff.commission-*') || request()->routeIs('staff.payroll-*') || request()->routeIs('staff.salary-*') ? 'active' : '' }}">
                    <i class="fas fa-users-cog"></i>
                    <span>Nhân sự</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.staff.index') }}" class="submenu-item">
                        <i class="fas fa-user-tie"></i>
                        <span>Nhân viên</span>
                    </a>
                    {{--
                    <a href="{{ route('staff.commission-events.index') }}" class="submenu-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Sự kiện hoa hồng</span>
                    </a>
                    <a href="{{ route('staff.commission-policies.index') }}" class="submenu-item">
                        <i class="fas fa-file-contract"></i>
                        <span>Chính sách hoa hồng</span>
                    </a>
                    <a href="{{ route('staff.payroll-cycles.index') }}" class="submenu-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Chu kỳ lương</span>
                    </a>
                    <a href="{{ route('staff.payroll-payslips.index') }}" class="submenu-item">
                        <i class="fas fa-file-invoice"></i>
                        <span>Phiếu lương</span>
                    </a>
                    <a href="{{ route('staff.salary-contracts.index') }}" class="submenu-item">
                        <i class="fas fa-file-contract"></i>
                        <span>Hợp đồng lương</span>
                    </a>
                    <a href="{{ route('staff.salary-advances.index') }}" class="submenu-item">
                        <i class="fas fa-arrow-up"></i>
                        <span>Tạm ứng lương</span>
                    </a>
                    --}}
                </div>
            </div>
            
            <!-- EXPORT MODULE -->
            <div class="nav-group" data-group="export">
                <a href="{{ route('staff.excel-export.index') }}" class="nav-item {{ request()->routeIs('staff.excel-export.*') ? 'active' : '' }}">
                    <i class="fas fa-file-excel"></i>
                    <span>Xuất Excel</span>
                </a>
            </div>
            
            <!-- SUBSCRIPTION MODULE -->
            <div class="nav-group" data-group="subscription">
                <a href="{{ route('staff.subscriptions.index') }}" class="nav-item {{ request()->routeIs('staff.subscriptions.*') ? 'active' : '' }}">
                    <i class="fas fa-box"></i>
                    <span>Đăng ký gói dịch vụ</span>
                </a>
            </div>
            
            <!-- SEPAY MODULE -->
            <div class="nav-group" data-group="sepay">
                <a href="#" class="nav-item has-submenu nav-parent {{ ((request()->routeIs('staff.sepay.*') && !request()->routeIs('staff.sepay.settings*')) || request()->routeIs('staff.webhook-logs.*')) ? 'active' : '' }}">
                    <i class="fas fa-university"></i>
                    <span>Sepay</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.sepay.index') }}" class="submenu-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Bảng điều khiển Sepay</span>
                    </a>
                    <a href="{{ route('staff.sepay.transactions') }}" class="submenu-item">
                        <i class="fas fa-list"></i>
                        <span>Giao dịch</span>
                    </a>
                    <a href="{{ route('staff.webhook-logs.index') }}" class="submenu-item">
                        <i class="fas fa-list-alt"></i>
                        <span>Nhật ký Webhook</span>
                    </a>
                </div>
            </div>
            
            <!-- SETTINGS MODULE -->
            <div class="nav-group" data-group="settings">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.system-settings.*') || request()->routeIs('staff.booking-deposit-settings.*') || request()->routeIs('staff.payment-cycle-settings.*') || request()->routeIs('staff.lease-service-settings.*') || request()->routeIs('staff.organization-banking.*') || request()->routeIs('staff.sepay.settings*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i>
                    <span>Cài đặt</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.system-settings.index') }}" class="submenu-item {{ request()->routeIs('staff.system-settings.*') ? 'active' : '' }}">
                        <i class="fas fa-cogs"></i>
                        <span>Cài đặt hệ thống</span>
                    </a>
                    <a href="{{ route('staff.profile.show') }}" class="submenu-item">
                        <i class="fas fa-user"></i>
                        <span>Hồ sơ cá nhân</span>
                    </a>
                    <a href="{{ route('staff.trash.index') }}" class="submenu-item {{ request()->routeIs('staff.trash.*') ? 'active' : '' }}">
                        <i class="fas fa-trash-restore"></i>
                        <span>Thùng rác</span>
                    </a>
                    <a href="{{ route('logout.get') }}" class="submenu-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Đăng xuất</span>
                    </a>
                </div>
            </div>
        </nav>
    </aside>
    
    <!-- Main Content Area -->
    <div class="main-content-wrapper">
        @yield('content')
    </div>
</div>

@push('styles')
<style>
.logo-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-top: 4px;
}

.user-name {
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    line-height: 1.3;
}

.user-roles {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.user-roles .badge {
    font-size: 10px;
    padding: 2px 6px;
    font-weight: 500;
}

.sidebar.collapsed .user-info {
    opacity: 0;
    visibility: hidden;
    height: 0;
    overflow: hidden;
}

.sidebar.collapsed .logo-content {
    gap: 0;
}

/* Submenu Styles */
.nav-group {
    position: relative;
}

.nav-parent {
    position: relative;
    cursor: pointer;
}

.submenu-arrow {
    margin-left: auto;
    transition: transform 0.3s ease;
    font-size: 0.8rem;
}

.nav-group.active .submenu-arrow {
    transform: rotate(180deg);
}

.submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease, opacity 0.3s ease;
    opacity: 0;
    visibility: hidden;
    background-color: #ffffff !important;
    border-left: 3px solid #e2e8f0;
}

.nav-group.active .submenu {
    max-height: 5000px;
    opacity: 1;
    visibility: visible;
    background-color: #ffffff !important;
}

.sidebar:not(.collapsed) .nav-parent.active + .submenu {
    max-height: 5000px;
    opacity: 1;
    visibility: visible;
    background-color: #ffffff !important;
}

.submenu-item {
    display: flex;
    align-items: center;
    padding: 12px 16px 12px 48px;
    color: #334155 !important;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
    background-color: transparent;
}

.submenu-item:hover {
    background-color: #f1f5f9 !important;
    color: #1e40af !important;
    border-left-color: #3b82f6;
}

.submenu-item.active {
    background-color: #dbeafe !important;
    color: #1e40af !important;
    border-left-color: #3b82f6;
    font-weight: 600;
}

.submenu-item i {
    margin-right: 12px;
    width: 16px;
    text-align: center;
    font-size: 13px;
    color: inherit;
}

.submenu-item span {
    color: inherit;
    font-weight: inherit;
}
</style>
@endpush

@push('scripts')
<script>
/**
 * Staff Header Notifications JavaScript
 * Cập nhật realtime số lượng thông báo chưa đọc
 */

// Store original page title
let originalTitle = document.title;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Store original title if not already stored
    if (!originalTitle || originalTitle.includes('(')) {
        originalTitle = document.title.replace(/^\(\d+\)\s*/, '');
    }
    
    // Load initial notification count
    updateStaffNotificationCount();
    
    // Update notification count every 30 seconds
    setInterval(updateStaffNotificationCount, 30000);
});

/**
 * Update notification count in header and browser tab
 */
function updateStaffNotificationCount() {
    // Check if badge element exists before making request
    const badge = document.getElementById('staffNotificationBadge');
    if (!badge) {
        return;
    }
    
    fetch('{{ route("staff.notifications.unread-count") }}', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Response is not JSON');
            }
            return response.json();
        })
        .then(data => {
            const badge = document.getElementById('staffNotificationBadge');
            const count = data.count || data.unread_count || 0;
            
            // Update badge in header
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'inline-flex';
                    // Add animation class if count changed
                    badge.classList.add('has-unread');
                } else {
                    badge.style.display = 'none';
                    badge.classList.remove('has-unread');
                }
            }
            
            // Update browser tab title
            if (count > 0) {
                document.title = `(${count}) ${originalTitle}`;
            } else {
                document.title = originalTitle;
            }
        })
        .catch(error => {
            // Only log errors that aren't network-related to avoid console spam
            // Network errors are common when the server is unreachable or the route doesn't exist
            if (error.name !== 'TypeError' || !error.message.includes('Failed to fetch')) {
                console.error('Error updating notification count:', error);
            }
        });
}

// Expose function globally for manual updates
window.updateStaffNotificationCount = updateStaffNotificationCount;

/**
 * Staff Sidebar Navigation JavaScript
 * Xử lý toggle submenu trong sidebar
 */
document.addEventListener('DOMContentLoaded', function() {
    initStaffNavigation();
});

function initStaffNavigation() {
    const sidebar = document.getElementById('sidebar');
    const isDesktopCollapsed = () => {
        return Boolean(sidebar && sidebar.classList.contains('collapsed') && window.innerWidth > 768);
    };

    const closeSubmenuGroup = (group) => {
        if (!group) return;
        
        group.classList.remove('active');

        const submenu = group.querySelector('.submenu');
        if (submenu) {
            submenu.style.maxHeight = '0';
            submenu.style.visibility = 'hidden';
            submenu.style.opacity = '0';
        }

        const parent = group.querySelector('.nav-parent');
        if (parent) {
            parent.classList.remove('active');
        }
    };

    const closeAllSubmenus = () => {
        document.querySelectorAll('.nav-group').forEach(group => {
            closeSubmenuGroup(group);
        });
    };

    // Handle sidebar toggle - close all submenus
    if (sidebar) {
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            const originalClickHandler = sidebarToggle.onclick;
            sidebarToggle.addEventListener('click', function() {
                // Close all submenus when collapsing
                if (!sidebar.classList.contains('collapsed')) {
                    closeAllSubmenus();
                }
            });
        }
    }

    // Handle submenu toggles
    document.querySelectorAll('.nav-parent').forEach(parent => {
        parent.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Nếu sidebar đang thu gọn ở desktop thì bung ra trước, không mở submenu rỗng
            if (isDesktopCollapsed()) {
                sidebar.classList.remove('collapsed');
                localStorage.setItem('sidebarCollapsed', 'false');
                return;
            }
            
            const navGroup = this.closest('.nav-group');
            if (!navGroup) return;
            
            const submenu = navGroup.querySelector('.submenu');
            if (!submenu) return;
            
            // Close other submenus
            document.querySelectorAll('.nav-group').forEach(group => {
                if (group !== navGroup && group.classList.contains('active')) {
                    closeSubmenuGroup(group);
                }
            });
            
            // Toggle current submenu
            const isActive = navGroup.classList.contains('active');
            
            if (isActive) {
                closeSubmenuGroup(navGroup);
            } else {
                navGroup.classList.add('active');
                submenu.style.maxHeight = submenu.scrollHeight + 'px';
                submenu.style.visibility = 'visible';
                submenu.style.opacity = '1';
                this.classList.add('active');
            }
        });
    });
    
    // Handle active states - auto-open submenu if current route matches
    const normalizePath = (path) => {
        if (!path) {
            return '/';
        }

        const normalized = path.split('?')[0].split('#')[0].replace(/\/+$/, '');
        return normalized || '/';
    };

    const currentPath = normalizePath(window.location.pathname);
    const isRouteMatch = (candidatePath) => {
        const targetPath = normalizePath(candidatePath);

        if (targetPath === '/') {
            return currentPath === '/';
        }

        return currentPath === targetPath || currentPath.startsWith(targetPath + '/');
    };

    const openParentSubmenu = (item) => {
        if (isDesktopCollapsed()) {
            return;
        }

        const submenu = item.closest('.submenu');
        if (!submenu) {
            return;
        }

        const navGroup = submenu.closest('.nav-group');
        if (!navGroup) {
            return;
        }

        navGroup.classList.add('active');
        submenu.style.maxHeight = submenu.scrollHeight + 'px';
        submenu.style.visibility = 'visible';

        const navParent = navGroup.querySelector('.nav-parent');
        if (navParent) {
            navParent.classList.add('active');
        }
    };

    // Keep submenu open when server already marks parent as active
    document.querySelectorAll('.nav-parent.active').forEach(parent => {
        if (isDesktopCollapsed()) {
            return;
        }

        const submenu = parent.nextElementSibling;
        if (submenu && submenu.classList.contains('submenu')) {
            const navGroup = parent.closest('.nav-group');
            if (navGroup) {
                navGroup.classList.add('active');
            }
            submenu.style.maxHeight = submenu.scrollHeight + 'px';
            submenu.style.visibility = 'visible';
        }
    });

    const matchedItems = [];

    document.querySelectorAll('.nav-item, .submenu-item').forEach(item => {
        const href = item.getAttribute('href');
        if (!href || href === '#') {
            return;
        }

        let itemPath = href;
        try {
            itemPath = new URL(href, window.location.origin).pathname;
        } catch (_) {
            itemPath = href;
        }

        const normalizedItemPath = normalizePath(itemPath);
        const isExactMatch = currentPath === normalizedItemPath;
        const isPrefixMatch = !isExactMatch && normalizedItemPath !== '/' && currentPath.startsWith(normalizedItemPath + '/');

        if (!isExactMatch && !isPrefixMatch) {
            return;
        }

        matchedItems.push({
            item,
            path: normalizedItemPath,
            exact: isExactMatch
        });
    });

    // Chỉ active item phù hợp nhất: ưu tiên exact match, sau đó ưu tiên path dài nhất
    if (matchedItems.length > 0) {
        matchedItems.sort((a, b) => {
            if (a.exact !== b.exact) {
                return a.exact ? -1 : 1;
            }
            return b.path.length - a.path.length;
        });

        const bestMatch = matchedItems[0];

        // Clear duplicate active/open states before applying best match
        document.querySelectorAll('.nav-item.active, .submenu-item.active').forEach(activeItem => {
            activeItem.classList.remove('active');
        });

        document.querySelectorAll('.nav-group').forEach(group => {
            closeSubmenuGroup(group);
        });

        const bestMatchGroup = bestMatch.item.closest('.nav-group');

        // Ở trạng thái thu gọn desktop chỉ đánh dấu icon cha, không bung submenu
        if (isDesktopCollapsed()) {
            if (bestMatchGroup) {
                const bestParent = bestMatchGroup.querySelector('.nav-parent');
                if (bestParent) {
                    bestParent.classList.add('active');
                }
            } else {
                bestMatch.item.classList.add('active');
            }
            return;
        }

        bestMatch.item.classList.add('active');
        openParentSubmenu(bestMatch.item);
    }
}
</script>
@endpush
