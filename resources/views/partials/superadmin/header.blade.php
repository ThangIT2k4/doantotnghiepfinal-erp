<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-crown"></i>
                <span class="logo-text">Super Admin</span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <a href="{{ route('superadmin.dashboard') }}" class="nav-item {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            
            <div class="nav-group" data-group="organizations">
                <a href="#" class="nav-item has-submenu nav-parent">
                    <i class="fas fa-building"></i>
                    <span>Organizations</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('superadmin.organizations.index') }}" class="submenu-item {{ request()->routeIs('superadmin.organizations.index') ? 'active' : '' }}">
                        <i class="fas fa-list"></i>
                        <span>All Organizations</span>
                    </a>
                    <a href="{{ route('superadmin.organizations.create') }}" class="submenu-item {{ request()->routeIs('superadmin.organizations.create') ? 'active' : '' }}">
                        <i class="fas fa-plus"></i>
                        <span>Add Organization</span>
                    </a>
                </div>
            </div>
            <div class="nav-group" data-group="subscription-plans">
                <a href="{{ route('superadmin.subscription-plans.index') }}" class="submenu-item {{ request()->routeIs('superadmin.subscription-plans.*') ? 'active' : '' }}">
                <i class="fas fa-box"></i>
                    <span>Subscription Plans</span>
                </a>
            </div>
            <div class="nav-group" data-group="subscriptions">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('superadmin.subscriptions.*') || request()->routeIs('superadmin.subscription-invoices.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Subscriptions</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('superadmin.subscriptions.index') }}" class="submenu-item {{ request()->routeIs('superadmin.subscriptions.index') ? 'active' : '' }}">
                        <i class="fas fa-list"></i>
                        <span>All Subscriptions</span>
                    </a>
                    <a href="{{ route('superadmin.subscription-invoices.index') }}" class="submenu-item {{ request()->routeIs('superadmin.subscription-invoices.*') ? 'active' : '' }}">
                        <i class="fas fa-file-invoice"></i>
                        <span>Subscription Invoices</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-group" data-group="company-invoices">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('superadmin.company-invoices.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice"></i>
                    <span>Company Invoices</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('superadmin.company-invoices.index') }}" class="submenu-item {{ request()->routeIs('superadmin.company-invoices.index') ? 'active' : '' }}">
                        <i class="fas fa-list"></i>
                        <span>All Company Invoices</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-group" data-group="users">
                <a href="#" class="nav-item has-submenu nav-parent">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="#" class="submenu-item disabled" onclick="return false;">
                        <i class="fas fa-list"></i>
                        <span>All Users</span>
                    </a>
                    <a href="#" class="submenu-item disabled" onclick="return false;">
                        <i class="fas fa-user-plus"></i>
                        <span>Add User</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-group" data-group="analytics">
                <a href="#" class="nav-item has-submenu nav-parent">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="#" class="submenu-item disabled" onclick="return false;">
                        <i class="fas fa-dollar-sign"></i>
                        <span>Revenue Analytics</span>
                    </a>
                    <a href="#" class="submenu-item disabled" onclick="return false;">
                        <i class="fas fa-chart-bar"></i>
                        <span>Growth Metrics</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-group" data-group="system">
                <a href="#" class="nav-item has-submenu nav-parent">
                    <i class="fas fa-cogs"></i>
                    <span>System</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="#" class="submenu-item disabled" onclick="return false;">
                        <i class="fas fa-heartbeat"></i>
                        <span>System Health</span>
                    </a>
                    <a href="#" class="submenu-item disabled" onclick="return false;">
                        <i class="fas fa-file-alt"></i>
                        <span>System Logs</span>
                    </a>
                    <a href="#" class="submenu-item disabled" onclick="return false;">
                        <i class="fas fa-sliders-h"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-group" data-group="support">
                <a href="#" class="nav-item has-submenu nav-parent">
                    <i class="fas fa-headset"></i>
                    <span>Support</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="#" class="submenu-item disabled" onclick="return false;">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Support Tickets</span>
                    </a>
                    <a href="#" class="submenu-item disabled" onclick="return false;">
                        <i class="fas fa-history"></i>
                        <span>Audit Logs</span>
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- User Info -->
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="user-details">
                    <div class="user-name">{{ Auth::user()->full_name ?? Auth::user()->name }}</div>
                    <div class="user-role">Super Admin</div>
                    <form method="POST" action="{{ route('logout') }}" class="logout-form">
                        @csrf
                        <button type="submit" class="logout-btn" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
           
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="mobile-menu-toggle d-lg-none" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="page-title">
                    <h1>@yield('title', 'Super Admin Dashboard')</h1>
                    <p class="page-subtitle">@yield('subtitle', 'Quản lý hệ thống SaaS')</p>
                </div>
            </div>
            
            <div class="header-right">
                <div class="header-actions">
                    <button class="btn btn-outline-primary btn-sm" onclick="clearSuperAdminCache()" title="Refresh Data">
                        <i class="fas fa-sync-alt"></i>
                        <span class="d-none d-md-inline">Refresh</span>
                    </button>
                </div>
                
                {{-- <div class="user-menu dropdown">
                    <button class="user-menu-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            {{ strtoupper(substr(Auth::user()->full_name ?? Auth::user()->name, 0, 1)) }}
                        </div>
                        <span class="user-name d-none d-md-inline">{{ Auth::user()->full_name ?? Auth::user()->name }}</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div> --}}
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            @yield('content')
        </div>
    </main>
</div>
