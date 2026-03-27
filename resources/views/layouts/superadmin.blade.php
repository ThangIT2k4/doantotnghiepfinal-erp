<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin - SaaS Platform ZoroRMS')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/image/logo2.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Custom Super Admin CSS -->
    <link href="{{ asset('assets/css/superadmin/superadmin.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ asset('assets/css/preloader.css') }}?v={{ time() }}" rel="stylesheet">
    
    @stack('styles')
    <link href="{{ asset('assets/css/dashboard-glass-ui.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ asset('assets/css/pagination-custom.css') }}?v={{ time() }}" rel="stylesheet">
</head>
<body class="superadmin-body glass-ui-dashboard">
    <x-preloader />

    <!-- Main Container -->
    <div class="superadmin-container">
        <!-- Sidebar -->
        <aside class="superadmin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <i class="fas fa-crown text-warning"></i>
                    <span class="brand-text">Super Admin</span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Thu gọn hoặc mở rộng menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <!-- Dashboard -->
                <div class="nav-section">
                    <a href="{{ route('superadmin.dashboard') }}" class="nav-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Bảng điều khiển</span>
                    </a>
                </div>

                <!-- Organizations Management -->
                <div class="nav-section">
                    <a href="{{ route('superadmin.organizations.index') }}" class="nav-section-title">
                        <i class="fas fa-building"></i>
                        <span>Tổ chức</span>
                    </a>
                    <div class="nav-section-children">
                        <a href="{{ route('superadmin.organizations.index') }}" class="nav-item {{ request()->routeIs('superadmin.organizations.*') ? 'active' : '' }}">
                            <i class="fas fa-list"></i>
                            <span>Tất cả Tổ chức</span>
                        </a>
                        <a href="{{ route('superadmin.organizations.create') }}" class="nav-item">
                            <i class="fas fa-plus"></i>
                            <span>Thêm Tổ chức</span>
                        </a>
                    </div>
                </div>
                <!-- Subscription Plans Management -->
                <div class="nav-section">
                    <a href="{{ route('superadmin.subscription-plans.index') }}" class="nav-section-title">
                        <i class="fas fa-box"></i>
                        <span>Gói đăng ký</span>
                    </a>
                    <div class="nav-section-children">
                        <a href="{{ route('superadmin.subscription-plans.index') }}" class="nav-item {{ request()->routeIs('superadmin.subscription-plans.*') ? 'active' : '' }}">
                            <i class="fas fa-list"></i>
                            <span>Tất cả Gói đăng ký</span>
                        </a>
                        <a href="{{ route('superadmin.subscription-plans.create') }}" class="nav-item">
                            <i class="fas fa-plus"></i>
                            <span>Thêm Gói đăng ký</span>
                        </a>
                    </div>
                </div>

                <!-- Subscriptions Management -->
                <div class="nav-section">
                    <a href="{{ route('superadmin.subscriptions.index') }}" class="nav-section-title">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Đăng ký</span>
                    </a>
                    <div class="nav-section-children">
                        <a href="{{ route('superadmin.subscriptions.index') }}" class="nav-item {{ request()->routeIs('superadmin.subscriptions.index') ? 'active' : '' }}">
                            <i class="fas fa-list"></i>
                            <span>Tất cả Đăng ký</span>
                        </a>
                        <a href="{{ route('superadmin.subscription-invoices.index') }}" class="nav-item {{ request()->routeIs('superadmin.subscription-invoices.*') ? 'active' : '' }}">
                            <i class="fas fa-file-invoice"></i>
                            <span>Hóa đơn Đăng ký</span>
                        </a>
                    </div>
                </div>

                <!-- Trial Leads Management -->
                <div class="nav-section">
                    <a href="{{ route('superadmin.trial-leads') }}" class="nav-section-title">
                        <i class="fas fa-gift"></i>
                        <span>Khách hàng</span>
                    </a>
                    <div class="nav-section-children">
                        <a href="{{ route('superadmin.trial-leads') }}" class="nav-item {{ request()->routeIs('superadmin.trial-leads') ? 'active' : '' }}">
                            <i class="fas fa-gift"></i>
                            <span>Đăng ký Dùng thử</span>
                        </a>
                    </div>
                </div>

                <!-- SePay Management -->
                <div class="nav-section">
                    <a href="{{ route('superadmin.sepay.index') }}" class="nav-section-title">
                        <i class="fas fa-credit-card"></i>
                        <span>Quản lý SePay</span>
                    </a>
                    <div class="nav-section-children">
                        <a href="{{ route('superadmin.sepay.index') }}" class="nav-item {{ request()->routeIs('superadmin.sepay.index') || request()->routeIs('superadmin.sepay.show') ? 'active' : '' }}">
                            <i class="fas fa-list"></i>
                            <span>Tất cả Giao dịch</span>
                        </a>
                        <a href="{{ route('superadmin.sepay.settlement') }}" class="nav-item {{ request()->routeIs('superadmin.sepay.settlement') ? 'active' : '' }}">
                            <i class="fas fa-calculator"></i>
                            <span>Báo cáo Thanh toán</span>
                        </a>
                    </div>
                </div>

                <!-- Company Invoices Management -->
                

                <!-- Users Management -->
                <div class="nav-section">
                    <a href="{{ route('superadmin.users.index') }}" class="nav-section-title">
                        <i class="fas fa-users"></i>
                        <span>Người dùng</span>
                    </a>
                    <div class="nav-section-children">
                        <a href="{{ route('superadmin.users.index') }}" class="nav-item {{ request()->routeIs('superadmin.users.*') ? 'active' : '' }}">
                            <i class="fas fa-list"></i>
                            <span>Tất cả Người dùng</span>
                        </a>
                        <a href="{{ route('superadmin.users.create') }}" class="nav-item">
                            <i class="fas fa-user-plus"></i>
                            <span>Thêm Người dùng</span>
                        </a>
                    </div>
                </div>

                <!-- Revenue & Analytics - TO BE IMPLEMENTED -->
                <div class="nav-section">
                    <div class="nav-section-title">
                        <i class="fas fa-chart-line"></i>
                        <span>Phân tích</span>
                    </div>
                    <div class="nav-section-children">
                        <a href="#" class="nav-item disabled" onclick="return false;">
                            <i class="fas fa-dollar-sign"></i>
                            <span>Phân tích Doanh thu</span>
                        </a>
                        <a href="#" class="nav-item disabled" onclick="return false;">
                            <i class="fas fa-chart-bar"></i>
                            <span>Chỉ số Tăng trưởng</span>
                        </a>
                    </div>
                </div>

                <!-- Trash Management -->
                <div class="nav-section">
                    <a href="{{ route('superadmin.trash.index') }}" class="nav-section-title">
                        <i class="fas fa-trash-alt"></i>
                        <span>Quản lý Dữ liệu</span>
                    </a>
                    <div class="nav-section-children">
                        <a href="{{ route('superadmin.trash.index') }}" class="nav-item {{ request()->routeIs('superadmin.trash.*') ? 'active' : '' }}">
                            <i class="fas fa-trash-restore"></i>
                            <span>Thùng rác</span>
                        </a>
                    </div>
                </div>

                <!-- System Management - TO BE IMPLEMENTED -->
                <div class="nav-section">
                    <div class="nav-section-title">
                        <i class="fas fa-cogs"></i>
                        <span>Hệ thống</span>
                    </div>
                    <div class="nav-section-children">
                        <a href="#" class="nav-item disabled" onclick="return false;">
                            <i class="fas fa-heartbeat"></i>
                            <span>Sức khỏe Hệ thống</span>
                        </a>
                        <a href="#" class="nav-item disabled" onclick="return false;">
                            <i class="fas fa-file-alt"></i>
                            <span>Nhật ký Hệ thống</span>
                        </a>
                        <a href="#" class="nav-item disabled" onclick="return false;">
                            <i class="fas fa-sliders-h"></i>
                            <span>Cài đặt</span>
                        </a>
                    </div>
                </div>

                <!-- Support & Tickets - TO BE IMPLEMENTED -->
                <div class="nav-section">
                    <div class="nav-section-title">
                        <i class="fas fa-headset"></i>
                        <span>Hỗ trợ</span>
                    </div>
                    <div class="nav-section-children">
                        <a href="#" class="nav-item disabled" onclick="return false;">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Phiếu Hỗ trợ</span>
                        </a>
                        <a href="#" class="nav-item disabled" onclick="return false;">
                            <i class="fas fa-history"></i>
                            <span>Nhật ký Kiểm toán</span>
                        </a>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="superadmin-main">
            <!-- Top Header -->
            <header class="superadmin-header">
                <div class="header-left">
                    <button class="sidebar-toggle d-lg-none" id="mobileSidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title-container">
                        <div class="page-title">
                            <h1 class="page-title-text">@yield('title', 'Bảng điều khiển Super Admin')</h1>
                            @hasSection('subtitle')
                            <p class="page-subtitle">@yield('subtitle')</p>
                            @endif
                        </div>
                        <div class="breadcrumb-container">
                            @yield('breadcrumb')
                        </div>
                    </div>
                </div>
                
                <div class="header-right">
                    <!-- Quick Actions -->
                    <div class="header-actions">
                        <button class="btn btn-outline-primary btn-sm" onclick="clearSuperAdminCache()" title="Refresh Data">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="user-menu dropdown">
                        <button class="user-menu-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <span class="user-name">Super Admin</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Hồ sơ</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Cài đặt</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item border-0 bg-transparent w-100 text-start">
                                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="superadmin-content">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Notifications -->
    <script src="{{ asset('assets/js/notifications.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('assets/js/preloader.js') }}?v={{ time() }}"></script>
    <!-- Super Admin JS -->
    <script src="{{ asset('assets/js/superadmin/superadmin.js') }}?v={{ time() }}"></script>
    
    @stack('scripts')
</body>
</html>