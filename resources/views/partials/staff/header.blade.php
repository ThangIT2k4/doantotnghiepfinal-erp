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
            <a href="{{ route('staff.notifications.index') }}" class="nav-item {{ request()->routeIs('staff.notifications.*') ? 'active' : '' }}" id="staffNotificationNavItem">
                <i class="fas fa-bell" id="staffNotificationIcon"></i>
                <span>Thông báo</span>
                <span class="notification-badge" id="staffNotificationBadge" style="display: none;">0</span>
            </a>
            
            <!-- PARTY MANAGEMENT MODULE -->
            <div class="nav-group" data-group="party">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.users.*') || request()->routeIs('staff.staff.*') || request()->routeIs('staff.tenants.*') || request()->routeIs('staff.user-banking.*') || request()->routeIs('staff.capabilities.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span>Party Management</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.users.index') }}" class="submenu-item">
                        <i class="fas fa-list"></i>
                        <span>Users</span>
                    </a>
                    <a href="{{ route('staff.staff.index') }}" class="submenu-item">
                        <i class="fas fa-user-tie"></i>
                        <span>Staff</span>
                    </a>
                    <a href="{{ route('staff.tenants.index') }}" class="submenu-item">
                        <i class="fas fa-user-friends"></i>
                        <span>Tenants</span>
                    </a>
                    <a href="{{ route('staff.user-banking.index') }}" class="submenu-item">
                        <i class="fas fa-bank"></i>
                        <span>User Banking</span>
                    </a>
                    <a href="{{ route('staff.capabilities.index') }}" class="submenu-item">
                        <i class="fas fa-key"></i>
                        <span>Capabilities</span>
                    </a>
                </div>
            </div>
            
            <!-- CRM MODULE -->
            <div class="nav-group" data-group="crm">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.leads.*') || request()->routeIs('staff.reviews.*') || request()->routeIs('staff.viewings.*') ? 'active' : '' }}">
                    <i class="fas fa-handshake"></i>
                    <span>CRM</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.leads.index') }}" class="submenu-item">
                        <i class="fas fa-user-plus"></i>
                        <span>Leads</span>
                    </a>
                    <a href="{{ route('staff.viewings.index') }}" class="submenu-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>Viewings</span>
                    </a>
                    <a href="{{ route('staff.reviews.index') }}" class="submenu-item">
                        <i class="fas fa-star"></i>
                        <span>Reviews</span>
                    </a>
                </div>
            </div>
            
            <!-- ASSET MANAGEMENT MODULE -->
            <div class="nav-group" data-group="asset">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.properties.*') || request()->routeIs('staff.units.*') || request()->routeIs('staff.meters.*') || request()->routeIs('staff.meter-readings.*') || request()->routeIs('staff.property-types.*') ? 'active' : '' }}">
                    <i class="fas fa-building"></i>
                    <span>Asset Management</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.properties.index') }}" class="submenu-item">
                        <i class="fas fa-list"></i>
                        <span>Properties</span>
                    </a>
                    <a href="{{ route('staff.units.index') }}" class="submenu-item">
                        <i class="fas fa-home"></i>
                        <span>Units</span>
                    </a>
                    <a href="{{ route('staff.property-types.index') }}" class="submenu-item">
                        <i class="fas fa-tags"></i>
                        <span>Property Types</span>
                    </a>
                    <a href="{{ route('staff.meters.index') }}" class="submenu-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Meters</span>
                    </a>
                    <a href="{{ route('staff.meter-readings.index') }}" class="submenu-item">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Meter Readings</span>
                    </a>
                </div>
            </div>
            
            <!-- CONTRACT MODULE -->
            <div class="nav-group" data-group="contract">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.leases.*') || request()->routeIs('staff.master-leases.*') || request()->routeIs('staff.booking-deposits.*') || request()->routeIs('staff.deposit-refunds.*') ? 'active' : '' }}">
                    <i class="fas fa-file-contract"></i>
                    <span>Contract</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.leases.index') }}" class="submenu-item">
                        <i class="fas fa-file-contract"></i>
                        <span>Leases</span>
                    </a>
                    <a href="{{ route('staff.master-leases.index') }}" class="submenu-item">
                        <i class="fas fa-building"></i>
                        <span>Master Leases</span>
                    </a>
                    <a href="{{ route('staff.booking-deposits.index') }}" class="submenu-item">
                        <i class="fas fa-credit-card"></i>
                        <span>Booking Deposits</span>
                    </a>
                    <a href="{{ route('staff.deposit-refunds.index') }}" class="submenu-item">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Deposit Refunds</span>
                    </a>
                </div>
            </div>
            
            <!-- BILLING MODULE -->
            <div class="nav-group" data-group="billing">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.invoices.*') || request()->routeIs('staff.payments.*') || request()->routeIs('staff.payment-cycle-settings.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>AR & Billing</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.invoices.index') }}" class="submenu-item">
                        <i class="fas fa-file-invoice"></i>
                        <span>Invoices</span>
                    </a>
                    <a href="{{ route('staff.payments.index') }}" class="submenu-item">
                        <i class="fas fa-credit-card"></i>
                        <span>Payments</span>
                    </a>
                    <a href="{{ route('staff.payment-cycle-settings.index') }}" class="submenu-item">
                        <i class="fas fa-cog"></i>
                        <span>Payment Cycle Settings</span>
                    </a>
                </div>
            </div>
            
            <!-- WORK MANAGEMENT MODULE -->
            <div class="nav-group" data-group="work">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.tickets.*') ? 'active' : '' }}">
                    <i class="fas fa-tasks"></i>
                    <span>Work Management</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.tickets.index') }}" class="submenu-item">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Tickets</span>
                    </a>
                </div>
            </div>
            
            <!-- FINANCE MODULE -->
            <div class="nav-group" data-group="finance">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.vendors.*') || request()->routeIs('staff.company-invoices.*') || request()->routeIs('staff.cash-outflows.*') || request()->routeIs('staff.commission-*') || request()->routeIs('staff.payroll-*') || request()->routeIs('staff.salary-*') || request()->routeIs('staff.financial-*') || request()->routeIs('staff.excel-export.*') ? 'active' : '' }}">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Finance</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.vendors.index') }}" class="submenu-item">
                        <i class="fas fa-building"></i>
                        <span>Vendors</span>
                    </a>
                    <a href="{{ route('staff.company-invoices.index') }}" class="submenu-item">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>Company Invoices</span>
                    </a>
                    {{--
                    <a href="{{ route('staff.cash-outflows.index') }}" class="submenu-item">
                        <i class="fas fa-arrow-down"></i>
                        <span>Cash Outflows</span>
                    </a>
                    <a href="{{ route('staff.commission-events.index') }}" class="submenu-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Commission Events</span>
                    </a>
                    <a href="{{ route('staff.commission-policies.index') }}" class="submenu-item">
                        <i class="fas fa-file-contract"></i>
                        <span>Commission Policies</span>
                    </a>
                    <a href="{{ route('staff.payroll-cycles.index') }}" class="submenu-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Payroll Cycles</span>
                    </a>
                    <a href="{{ route('staff.payroll-payslips.index') }}" class="submenu-item">
                        <i class="fas fa-file-invoice"></i>
                        <span>Payroll Payslips</span>
                    </a>
                    <a href="{{ route('staff.salary-contracts.index') }}" class="submenu-item">
                        <i class="fas fa-file-contract"></i>
                        <span>Salary Contracts</span>
                    </a>
                    <a href="{{ route('staff.salary-advances.index') }}" class="submenu-item">
                        <i class="fas fa-arrow-up"></i>
                        <span>Salary Advances</span>
                    </a>
                    <a href="{{ route('staff.financial-management.index') }}" class="submenu-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Financial Management</span>
                    </a>
                    --}}
                    <a href="{{ route('staff.excel-export.index') }}" class="submenu-item">
                        <i class="fas fa-file-excel"></i>
                        <span>Excel Export</span>
                    </a>
                </div>
            </div>
            
            <!-- SEPAY MODULE -->
            <div class="nav-group" data-group="sepay">
                <a href="#" class="nav-item has-submenu nav-parent {{ request()->routeIs('staff.sepay.*') || request()->routeIs('staff.webhook-logs.*') ? 'active' : '' }}">
                    <i class="fas fa-university"></i>
                    <span>Sepay</span>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </a>
                <div class="submenu">
                    <a href="{{ route('staff.sepay.index') }}" class="submenu-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Sepay Dashboard</span>
                    </a>
                    <a href="{{ route('staff.sepay.transactions') }}" class="submenu-item">
                        <i class="fas fa-list"></i>
                        <span>Transactions</span>
                    </a>
                    <a href="{{ route('staff.sepay.settings') }}" class="submenu-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="{{ route('staff.webhook-logs.index') }}" class="submenu-item">
                        <i class="fas fa-list-alt"></i>
                        <span>Webhook Logs</span>
                    </a>
                </div>
            </div>
            
            <!-- TRASH MANAGEMENT MODULE -->
            <div class="nav-group" data-group="trash">
                <a href="{{ route('staff.trash.index') }}" class="nav-item {{ request()->routeIs('staff.trash.*') ? 'active' : '' }}">
                    <i class="fas fa-trash-restore"></i>
                    <span>Thùng rác</span>
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- Main Content Area -->
    <div class="main-content-wrapper">
        @yield('content')
    </div>
</div>

