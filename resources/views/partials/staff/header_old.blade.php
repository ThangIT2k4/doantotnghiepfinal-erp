<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <span class="logo-text">Staff Panel</span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle" 
                    title="Đóng/mở sidebar" aria-label="Đóng/mở sidebar">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                <span class="sr-only">Đóng/mở sidebar</span>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <!-- Dashboard -->
            <a href="{{ route('staff.dashboard') }}" class="nav-item {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <!-- Thông báo -->
            <a href="{{ route('staff.notifications') }}" class="nav-item {{ request()->routeIs('staff.notifications.*') ? 'active' : '' }}" id="managerNotificationNavItem">
                <i class="fas fa-bell" id="managerNotificationIcon"></i>
                <span>Thông báo</span>
                <span class="notification-badge" id="managerNotificationBadge" style="display: none;">0</span>
            </a>
            
            <!-- QUẢN LÝ BẤT ĐỘNG SẢN -->
            <div class="nav-group" data-group="property">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.properties.*') || request()->routeIs('staff.units.*') ? 'active' : '' }}">
                    <i class="fas fa-building"></i>
                    <span>Bất động sản</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.properties.index') }}" class="submenu-item">
                        <i class="fas fa-list"></i>
                        <span>Danh sách BĐS</span>
                    </a>
                    <a href="{{ route('staff.properties.create') }}" class="submenu-item">
                        <i class="fas fa-plus"></i>
                        <span>Thêm BĐS mới</span>
                    </a>
                    <a href="{{ route('staff.units.index') }}" class="submenu-item">
                        <i class="fas fa-home"></i>
                        <span>Quản lý phòng</span>
                    </a>
                    <a href="{{ route('staff.units.create') }}" class="submenu-item">
                        <i class="fas fa-plus"></i>
                        <span>Thêm phòng mới</span>
                    </a>
                    <a href="{{ route('staff.property-types.index') }}" class="submenu-item">
                        <i class="fas fa-tags"></i>
                        <span>Loại BĐS</span>
                    </a>
                </div>
            </div>

            <!-- QUẢN LÝ NGƯỜI DÙNG -->
            <div class="nav-group" data-group="users">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.users.*') || request()->routeIs('staff.staff.*') || request()->routeIs('staff.tenants.*') || request()->routeIs('staff.leads.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Người dùng</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.users.index') }}" class="submenu-item">
                        <i class="fas fa-list"></i>
                        <span>Tất cả người dùng</span>
                    </a>
                    <a href="{{ route('staff.staff.index') }}" class="submenu-item">
                        <i class="fas fa-user-tie"></i>
                        <span>CTV/Nhân viên</span>
                    </a>
                    <a href="{{ route('staff.capabilities.index') }}" class="submenu-item">
                        <i class="fas fa-key"></i>
                        <span>Quản lý quyền</span>
                    </a>
                    <a href="{{ route('staff.users.capabilities', auth()->user()->id) }}" class="submenu-item">
                        <i class="fas fa-key"></i>
                        <span>Quản lý quyền người dùng</span>
                    </a>
                    <a href="{{ route('staff.tenants.index') }}" class="submenu-item">
                        <i class="fas fa-user-friends"></i>
                        <span>Khách hàng</span>
                    </a>
                    <a href="{{ route('staff.leads.index') }}" class="submenu-item">
                        <i class="fas fa-user-plus"></i>
                        <span>Leads</span>
                    </a>
                    <a href="{{ route('staff.users.create') }}" class="submenu-item">
                        <i class="fas fa-plus"></i>
                        <span>Thêm tài khoản</span>
                    </a>
                    <a href="{{ route('staff.user-banking.index') }}" class="submenu-item">
                        <i class="fas fa-bank"></i>
                        <span>Quản lý tài khoản ngân hàng người dùng</span>
                    </a>
                </div>
            </div>            
            <!-- QUẢN LÝ HỢP ĐỒNG & THUÊ -->
            <div class="nav-group" data-group="contracts">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.leases.*') || request()->routeIs('staff.master-leases.*') || request()->routeIs('staff.booking-deposits.*') || request()->routeIs('staff.deposit-refunds.*') ? 'active' : '' }}">
                    <i class="fas fa-file-contract"></i>
                    <span>Hợp đồng & Thuê</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.leases.index') }}" class="submenu-item">
                        <i class="fas fa-file-contract"></i>
                        <span>Hợp đồng thuê</span>
                    </a>
                    <a href="{{ route('staff.master-leases.index') }}" class="submenu-item">
                        <i class="fas fa-building"></i>
                        <span>Hợp đồng thuê lại</span>
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

            <!-- QUẢN LÝ TÀI CHÍNH -->
            <div class="nav-group" data-group="finance">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.invoices.*') || request()->routeIs('staff.company-invoices.*') || request()->routeIs('staff.payments.*') || request()->routeIs('staff.cash-outflows.*') || request()->routeIs('staff.vendors.*') || request()->routeIs('staff.sepay.*') ? 'active' : '' }}">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Tài chính</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.invoices.index') }}" class="submenu-item">
                        <i class="fas fa-file-invoice"></i>
                        <span>Hóa đơn</span>
                    </a>
                    <a href="{{ route('staff.company-invoices.index') }}" class="submenu-item">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Hóa đơn công ty</span>
                    </a>
                    <a href="{{ route('staff.payments.index') }}" class="submenu-item">
                        <i class="fas fa-credit-card"></i>
                        <span>Thanh toán</span>
                    </a>
                    <a href="{{ route('staff.cash-outflows.index') }}" class="submenu-item">
                        <i class="fas fa-arrow-down"></i>
                        <span>Dòng tiền ra</span>
                    </a>
                    <a href="{{ route('staff.vendors.index') }}" class="submenu-item">
                        <i class="fas fa-building"></i>
                        <span>Nhà cung cấp</span>
                    </a>
                    <a href="{{ route('staff.sepay.index') }}" class="submenu-item">
                        <i class="fas fa-university"></i>
                        <span>Quản lý SePay</span>
                    </a>
                </div>
            </div>

            <!-- QUẢN LÝ CÔNG TƠ & ĐIỆN NƯỚC -->
            <div class="nav-group" data-group="meters">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.meters.*') || request()->routeIs('staff.meter-readings.*') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Công tơ & Điện nước</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.meters.index') }}" class="submenu-item">
                        <i class="fas fa-list"></i>
                        <span>Danh sách công tơ</span>
                    </a>
                    <a href="{{ route('staff.meters.create') }}" class="submenu-item">
                        <i class="fas fa-plus"></i>
                        <span>Thêm công tơ mới</span>
                    </a>
                    <a href="{{ route('staff.meter-readings.index') }}" class="submenu-item">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Số liệu đo</span>
                    </a>
                    <a href="{{ route('staff.meter-readings.create') }}" class="submenu-item">
                        <i class="fas fa-plus"></i>
                        <span>Thêm số liệu đo</span>
                    </a>
                    <a href="{{ route('staff.meters.statistics') }}" class="submenu-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Thống kê công tơ</span>
                    </a>
                    <a href="{{ route('staff.meter-readings.statistics') }}" class="submenu-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Thống kê số liệu</span>
                    </a>
                </div>
            </div>
            
            <!-- QUẢN LÝ LỊCH HẸN & XEM PHÒNG -->
            <div class="nav-group" data-group="viewings">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.viewings.*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Lịch hẹn & Xem phòng</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.viewings.index') }}" class="submenu-item">
                        <i class="fas fa-list"></i>
                        <span>Danh sách lịch hẹn</span>
                    </a>
                    <a href="{{ route('staff.viewings.calendar') }}" class="submenu-item">
                        <i class="fas fa-calendar"></i>
                        <span>Lịch tổng quan</span>
                    </a>
                    <a href="{{ route('staff.viewings.create') }}" class="submenu-item">
                        <i class="fas fa-plus"></i>
                        <span>Thêm lịch hẹn mới</span>
                    </a>
                    <a href="{{ route('staff.viewings.today') }}" class="submenu-item">
                        <i class="fas fa-calendar-day"></i>
                        <span>Lịch hôm nay</span>
                    </a>
                    <a href="{{ route('staff.viewings.statistics') }}" class="submenu-item">
                        <i class="fas fa-chart-bar"></i>    
                        <span>Thống kê lịch hẹn</span>
                    </a>
                </div>
            </div>

            <!-- QUẢN LÝ HỖ TRỢ & BẢO TRÌ -->
            <div class="nav-group" data-group="support">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.tickets.*') || request()->routeIs('staff.reviews.*') ? 'active' : '' }}">
                    <i class="fas fa-tools"></i>
                    <span>Hỗ trợ & Bảo trì</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.tickets.index') }}" class="submenu-item">
                        <i class="fas fa-tools"></i>
                        <span>Tickets</span>
                    </a>
                    <a href="{{ route('staff.reviews.index') }}" class="submenu-item">
                        <i class="fas fa-star"></i>
                        <span>Đánh giá</span>
                    </a>
                </div>
            </div>

            <!-- QUẢN LÝ NHÂN SỰ & LƯƠNG -->
            <div class="nav-group" data-group="hr">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.salary-contracts.*') || request()->routeIs('staff.payroll-cycles.*') || request()->routeIs('staff.payroll-payslips.*') || request()->routeIs('staff.salary-advances.*') || request()->routeIs('staff.commission-policies.*') || request()->routeIs('staff.commission-events.*') ? 'active' : '' }}">
                    <i class="fas fa-user-tie"></i>
                    <span>Nhân sự & Lương</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.salary-contracts.index') }}" class="submenu-item">
                        <i class="fas fa-file-contract"></i>
                        <span>Hợp đồng lương</span>
                    </a>
                    <a href="{{ route('staff.payroll-cycles.index') }}" class="submenu-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Kỳ lương</span>
                    </a>
                    <a href="{{ route('staff.payroll-payslips.index') }}" class="submenu-item">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Phiếu lương</span>
                    </a>
                    <a href="{{ route('staff.salary-advances.index') }}" class="submenu-item">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span>Ứng lương</span>
                    </a>
                    <a href="{{ route('staff.commission-policies.index') }}" class="submenu-item">
                        <i class="fas fa-percentage"></i>
                        <span>Chính sách hoa hồng</span>
                    </a>
                    <a href="{{ route('staff.commission-events.index') }}" class="submenu-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Sự kiện hoa hồng</span>
                    </a>
                </div>
            </div>

            <!-- CÔNG CỤ & BÁO CÁO -->
            <div class="nav-group" data-group="tools">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.excel-export.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i>
                    <span>Công cụ & Báo cáo</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.excel-export.index') }}" class="submenu-item">
                <i class="fas fa-file-excel"></i>
                <span>Xuất Excel</span>
            </a>
            <a href="{{ route('staff.financial-management.index') }}" class="submenu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Quản lý tài chính</span>
            </a>
            <a href="{{ route('staff.financial-management.cash-flow-forecast') }}" class="submenu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Dự đoán dòng tiền</span>
            </a>
            <a href="{{ route('staff.financial-management.expense-tracking') }}" class="submenu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Theo dõi chi phí</span>
            </a>
            <a href="{{ route('staff.financial-management.payment-reminders') }}" class="submenu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Nhắc thanh toán</span>
            </a>
            <a href="{{ route('staff.financial-management.reconciliation') }}" class="submenu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Báo cáo đối chiếu</span>
            </a>
            <a href="{{ route('staff.financial-management.tax-reports') }}" class="submenu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Báo cáo thuế</span>
            </a>
                </div>
            </div>
            
            <!-- CÀI ĐẶT -->
            <div class="nav-group" data-group="settings">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.profile') || request()->routeIs('staff.settings.general') || request()->routeIs('staff.payment-cycle-settings.*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i>
                    <span>Cài đặt</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.profile') }}" class="submenu-item">
                        <i class="fas fa-user"></i>
                        <span>Hồ sơ cá nhân</span>
                    </a>
                    <a href="{{ route('staff.settings.general') }}" class="submenu-item">
                        <i class="fas fa-sliders-h"></i>
                        <span>Cài đặt chung</span>
                    </a>
                    <a href="{{ route('staff.payment-cycle-settings.index') }}" class="submenu-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>Chu kỳ thanh toán</span>
                    </a>
                </div>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <a href="{{ route('staff.profile') }}" title="Xem hồ sơ">
                        @if(auth()->user()?->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="avatar-img">
                        @else
                            <i class="fas fa-user-shield"></i>
                        @endif
                    </a>
                </div>
                <div class="user-details">
                    <div class="user-name">{{ auth()->user()?->full_name ?? 'Manager' }}</div>
                    <div class="user-email">{{ auth()->user()?->email ?? '' }}</div>
                    <div class="user-role">
                        <span class="role-badge">Manager</span>
                    </div>
                </div>
            </div>
            <div class="logout-section">
                <a href="{{ route('logout') }}" class="logout-btn" 
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   title="Đăng xuất">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng xuất</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </aside>

@push('styles')
<style>
.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    animation: pulse 2s infinite;
    z-index: 10;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.nav-item {
    position: relative;
    transition: all 0.3s ease;
}

/* Red styling when there are unread notifications */
.nav-item.has-unread-notifications {
    background: rgba(220, 53, 69, 0.1);
    border-radius: 8px;
    border-left: 3px solid #dc3545;
}

.nav-item.has-unread-notifications .notification-icon {
    color: #dc3545 !important;
    animation: bellShake 1s ease-in-out infinite;
}

.nav-item.has-unread-notifications span {
    color: #dc3545 !important;
    font-weight: 600;
}

@keyframes bellShake {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-10deg); }
    75% { transform: rotate(10deg); }
}

/* Enhanced notification badge */
.notification-badge.has-unread {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
    animation: pulse 1.5s infinite, glow 2s infinite;
}

@keyframes glow {
    0%, 100% { box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4); }
    50% { box-shadow: 0 2px 12px rgba(220, 53, 69, 0.6); }
}

/* User info improvements */
.user-info {
    display: flex;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 10px;
}

.user-avatar {
    margin-right: 12px;
}

.user-avatar a {
    display: block;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.user-avatar a:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.user-details {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 600;
    color: #fff;
    font-size: 14px;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-email {
    color: rgba(255, 255, 255, 0.7);
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

.user-role {
    margin-top: 4px;
}

.role-badge {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.logout-section {
    padding: 0 15px 15px;
}

.logout-btn {
    display: flex;
    align-items: center;
    padding: 10px 12px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.logout-btn:hover {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
    border-color: rgba(220, 53, 69, 0.3);
    transform: translateX(2px);
}

.logout-btn i {
    margin-right: 8px;
    font-size: 14px;
}

.logout-btn span {
    font-size: 13px;
    font-weight: 500;
}

/* Active submenu item styling */
.submenu-item.active {
    background: rgba(102, 126, 234, 0.2);
    border-left: 3px solid #667eea;
    color: #667eea !important;
}

.submenu-item.active i {
    color: #667eea !important;
}

/* Menu search styling */
.menu-search::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.menu-search:focus {
    outline: none;
    border-color: #667eea;
    background: rgba(255, 255, 255, 0.15);
}

/* Keyboard shortcuts hint */
.keyboard-hint {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    z-index: 1000;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.keyboard-hint.show {
    opacity: 1;
}

/* Enhanced hover effects */
.nav-item:hover, .submenu-item:hover {
    transform: translateX(2px);
    transition: all 0.2s ease;
}

.nav-item:active, .submenu-item:active {
    transform: scale(0.98);
}

/* Responsive improvements */
@media (max-width: 768px) {
    .user-details {
        display: none;
    }
    
    .user-avatar {
        margin-right: 0;
    }
    
    .logout-btn span {
        display: none;
    }
    
    .logout-btn {
        justify-content: center;
        padding: 10px;
    }
    
    .sidebar-footer {
        padding: 10px;
    }
    
    .user-info {
        padding: 10px;
    }
}

@media (max-width: 480px) {
    .sidebar {
        width: 60px;
    }
    
    .sidebar .nav-item span,
    .sidebar .submenu-item span {
        display: none;
    }
    
    .sidebar .submenu {
        position: absolute;
        left: 60px;
        top: 0;
        background: #2c3e50;
        min-width: 200px;
        border-radius: 0 8px 8px 0;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load notification count on page load
    loadNotificationCount();
    
    // Refresh notification count every 30 seconds
    setInterval(loadNotificationCount, 30000);
    
    function loadNotificationCount() {
        fetch('/manager/notifications/unread-count')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('managerNotificationBadge');
                    const navItem = document.getElementById('managerNotificationNavItem');
                    const icon = document.getElementById('managerNotificationIcon');
                    
                    if (data.unread_count > 0) {
                        // Show badge with count
                        badge.textContent = data.unread_count;
                        badge.style.display = 'flex';
                        badge.classList.add('has-unread');
                        
                        // Add red styling to nav item
                        navItem.classList.add('has-unread-notifications');
                        
                        // Change icon to red and add shake animation
                        icon.style.color = '#dc3545';
                        
                        // Add visual feedback
                        navItem.style.transform = 'scale(1.02)';
                        setTimeout(() => {
                            navItem.style.transform = 'scale(1)';
                        }, 200);
                        
                    } else {
                        // Hide badge and remove red styling
                        badge.style.display = 'none';
                        badge.classList.remove('has-unread');
                        navItem.classList.remove('has-unread-notifications');
                        
                        // Reset icon color
                        icon.style.color = '';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading notification count:', error);
            });
    }
    
    // Add click effect for notification nav item
    const notificationNavItem = document.getElementById('managerNotificationNavItem');
    if (notificationNavItem) {
        notificationNavItem.addEventListener('click', function() {
            // Add click animation
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    }
    
    // Handle submenu toggles
    const navGroups = document.querySelectorAll('.nav-group');
    navGroups.forEach(group => {
        const navParent = group.querySelector('.nav-parent');
        const submenu = group.querySelector('.submenu');
        const submenuArrow = group.querySelector('.submenu-arrow');
        
        if (navParent && submenu) {
            navParent.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Toggle the open class on the nav-group
                group.classList.toggle('open');
                
                // Rotate the arrow
                if (submenuArrow) {
                    submenuArrow.style.transform = group.classList.contains('open') 
                        ? 'rotate(180deg)' 
                        : 'rotate(0deg)';
                }
                
                // Close other open submenus
                navGroups.forEach(otherGroup => {
                    if (otherGroup !== group && otherGroup.classList.contains('open')) {
                        otherGroup.classList.remove('open');
                        const otherArrow = otherGroup.querySelector('.submenu-arrow');
                        if (otherArrow) {
                            otherArrow.style.transform = 'rotate(0deg)';
                        }
                    }
                });
            });
        }
    });
    
    // Auto-open submenu if current route matches
    const currentPath = window.location.pathname;
    navGroups.forEach(group => {
        const submenuItems = group.querySelectorAll('.submenu-item');
        let shouldOpen = false;
        
        submenuItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && currentPath.includes(href.replace('/manager', ''))) {
                shouldOpen = true;
            }
        });
        
        if (shouldOpen) {
            group.classList.add('open');
            const submenuArrow = group.querySelector('.submenu-arrow');
            if (submenuArrow) {
                submenuArrow.style.transform = 'rotate(180deg)';
            }
        }
    });
    
    // Add keyboard navigation support
    document.addEventListener('keydown', function(e) {
        // Alt + M to toggle sidebar
        if (e.altKey && e.key === 'm') {
            e.preventDefault();
            const sidebarToggle = document.getElementById('sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.click();
            }
        }
        
        // Alt + N to go to notifications
        if (e.altKey && e.key === 'n') {
            e.preventDefault();
            const notificationLink = document.getElementById('managerNotificationNavItem');
            if (notificationLink) {
                notificationLink.click();
            }
        }
    });
    
    // Add tooltip functionality
    const navItems = document.querySelectorAll('.nav-item, .submenu-item');
    navItems.forEach(item => {
        const title = item.getAttribute('title') || item.querySelector('span')?.textContent;
        if (title) {
            item.setAttribute('title', title);
        }
    });
    
    // Add smooth scroll for submenu items
    const submenuItems = document.querySelectorAll('.submenu-item');
    submenuItems.forEach(item => {
        item.addEventListener('click', function() {
            // Add active class to clicked item
            submenuItems.forEach(otherItem => otherItem.classList.remove('active'));
            this.classList.add('active');
            
            // Add click animation
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
    
    // Add search functionality (if needed in future)
    function addSearchFunctionality() {
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Tìm kiếm menu...';
        searchInput.className = 'menu-search';
        searchInput.style.cssText = `
            width: 100%;
            padding: 8px 12px;
            margin: 10px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 14px;
        `;
        
        const sidebarNav = document.querySelector('.sidebar-nav');
        if (sidebarNav) {
            sidebarNav.insertBefore(searchInput, sidebarNav.firstChild);
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const allItems = document.querySelectorAll('.nav-item, .submenu-item');
                
                allItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        item.style.display = '';
                        item.closest('.nav-group')?.classList.add('open');
                    } else {
                        item.style.display = searchTerm ? 'none' : '';
                    }
                });
            });
        }
    }
    
    // Uncomment the line below to enable search functionality
    // addSearchFunctionality();
});
</script>
@endpush