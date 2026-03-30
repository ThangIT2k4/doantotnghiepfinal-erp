@extends('layouts.staff_dashboard')

@section('title', 'Cài đặt hệ thống')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Cài đặt hệ thống',
            'subtitle' => 'Quản lý và cấu hình các thiết lập hệ thống',
            'icon' => 'fas fa-cog',
            'actions' => []
        ])

        <!-- Session Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Tabs Navigation -->
        @include('staff.components.tab-navigation', [
            'tabs' => [
                'booking-deposit' => [
                    'label' => 'Đặt cọc',
                    'icon' => 'fas fa-hand-holding-usd',
                    'color' => 'primary'
                ],
                'payment-cycle' => [
                    'label' => 'Chu kỳ thanh toán',
                    'icon' => 'fas fa-calendar-alt',
                    'color' => 'info'
                ],
                'lease-service' => [
                    'label' => 'Dịch vụ hợp đồng',
                    'icon' => 'fas fa-concierge-bell',
                    'color' => 'success'
                ],
                'organization-banking' => [
                    'label' => 'Tài khoản ngân hàng',
                    'icon' => 'fas fa-university',
                    'color' => 'warning'
                ],
                'organization-email' => [
                    'label' => 'Cấu hình Email',
                    'icon' => 'fas fa-envelope',
                    'color' => 'info'
                ],
                'organization' => [
                    'label' => 'Thông tin tổ chức',
                    'icon' => 'fas fa-building',
                    'color' => 'primary'
                ],
                'services' => [
                    'label' => 'Dịch vụ',
                    'icon' => 'fas fa-list',
                    'color' => 'secondary'
                ]
            ],
            'storageKey' => 'systemSettingsTabs',
            'defaultVisible' => ['booking-deposit']
        ])

        <!-- Tab Contents -->
        <!-- Booking Deposit Tab -->
        <div id="tab-booking-deposit" class="tab-content">
            @include('staff.settings.system-settings.tabs.booking-deposit', $bookingDepositData)
        </div>

        <!-- Payment Cycle Tab -->
        <div id="tab-payment-cycle" class="tab-content hidden">
            @include('staff.settings.system-settings.tabs.payment-cycle', $paymentCycleData)
        </div>

        <!-- Lease Service Tab -->
        <div id="tab-lease-service" class="tab-content hidden">
            @include('staff.settings.system-settings.tabs.lease-service', $leaseServiceData)
        </div>

        <!-- Organization Banking Tab -->
        <div id="tab-organization-banking" class="tab-content hidden">
            @include('staff.settings.system-settings.tabs.organization-banking', $organizationBankingData)
        </div>

        <!-- Organization Email Tab -->
        <div id="tab-organization-email" class="tab-content hidden">
            @include('staff.settings.system-settings.tabs.organization-email', $organizationEmailData)
        </div>

        <!-- Organization Tab -->
        <div id="tab-organization" class="tab-content hidden">
            @include('staff.settings.system-settings.tabs.organization', $organizationData)
        </div>

        <!-- Services Tab -->
        <div id="tab-services" class="tab-content hidden">
            @include('staff.settings.system-settings.tabs.services', $servicesData)
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
// Tab navigation functionality
function toggleTab(tabId, button, storageKey) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll(`[onclick*="toggleTab('${tabId}'"]`).forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    const selectedTab = document.getElementById(`tab-${tabId}`);
    if (selectedTab) {
        selectedTab.classList.remove('hidden');
    }
    
    // Add active class to button
    if (button) {
        button.classList.add('active');
    }
    
    // Save to localStorage
    if (storageKey) {
        localStorage.setItem(`${storageKey}_active`, tabId);
    }
}

function expandAllTabs(storageKey) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('hidden');
    });
    document.querySelectorAll(`[onclick*="toggleTab"]`).forEach(btn => {
        btn.classList.add('active');
    });
}

function collapseAllTabs(storageKey, defaultVisible) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    document.querySelectorAll(`[onclick*="toggleTab"]`).forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show default visible tabs
    if (defaultVisible && defaultVisible.length > 0) {
        defaultVisible.forEach(tabId => {
            const tab = document.getElementById(`tab-${tabId}`);
            if (tab) {
                tab.classList.remove('hidden');
            }
            const button = document.querySelector(`[onclick*="toggleTab('${tabId}'"]`);
            if (button) {
                button.classList.add('active');
            }
        });
    }
}

// Load saved tab state on page load
document.addEventListener('DOMContentLoaded', function() {
    const storageKey = 'systemSettingsTabs';
    
    // Check for active_tab from session (redirect from old routes)
    @if(session('active_tab'))
        const activeTabFromSession = '{{ session('active_tab') }}';
        const button = document.querySelector(`[onclick*="toggleTab('${activeTabFromSession}'"]`);
        if (button) {
            toggleTab(activeTabFromSession, button, storageKey);
            return;
        }
    @endif
    
    // Check localStorage for saved tab
    const savedTab = localStorage.getItem(`${storageKey}_active`);
    
    if (savedTab) {
        const button = document.querySelector(`[onclick*="toggleTab('${savedTab}'"]`);
        if (button) {
            toggleTab(savedTab, button, storageKey);
        }
    } else {
        // Show first tab by default
        const firstTab = document.querySelector('[onclick*="toggleTab"]');
        if (firstTab) {
            const tabId = firstTab.getAttribute('onclick').match(/'([^']+)'/)[1];
            toggleTab(tabId, firstTab, storageKey);
        }
    }
});
</script>
@endpush

