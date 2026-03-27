<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Staff Dashboard ZoroRMS')</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('assets/image/logo2.svg') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/manager/dashboard.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/preloader.css') }}?v={{ time() }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/notifications.css') }}?v={{ time() }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/chatbox-fix.css') }}?v={{ time() }}">
        @stack('styles')
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/dashboard-glass-ui.css') }}?v={{ time() }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/pagination-custom.css') }}?v={{ time() }}">
    </head>
    <body class="staff-dashboard-body glass-ui-dashboard">
        {{-- Preloader --}}
        <x-preloader style="minimal" />

        <main>
            @include('partials.staff.header_erp')
        </main>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <!-- HTMX for filters and AJAX without JavaScript -->
        <script src="https://unpkg.com/htmx.org@1.9.10"></script>
        <!-- Alpine.js for reactive components -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script src="{{ asset('assets/js/preloader.js') }}?v={{ time() }}"></script>
        <script src="{{ asset('assets/js/notifications.js') }}?v={{ time() }}"></script>
        <script src="{{ asset('assets/js/number-formatter.js') }}?v={{ time() }}"></script>
        <script src="{{ asset('assets/js/prefetch.js') }}?v={{ time() }}"></script>
        <script src="{{ asset('assets/js/infinite-scroll.js') }}?v={{ time() }}"></script>
        <script src="{{ asset('assets/js/manager/dashboard.js') }}"></script>
        <script src="{{ asset('assets/js/manager/header-notifications.js') }}"></script>
        <script>
        // Update notification IDs for staff
        $(document).ready(function() {
            // Update notification badge and nav item IDs if they exist
            const managerBadge = document.getElementById('managerNotificationBadge');
            const staffBadge = document.getElementById('staffNotificationBadge');
            const managerNavItem = document.getElementById('managerNotificationNavItem');
            const staffNavItem = document.getElementById('staffNotificationNavItem');
            
            // If manager elements exist, update to staff
            if (managerBadge && !staffBadge) {
                managerBadge.id = 'staffNotificationBadge';
            }
            if (managerNavItem && !staffNavItem) {
                managerNavItem.id = 'staffNotificationNavItem';
                const icon = document.getElementById('managerNotificationIcon');
                if (icon) {
                    icon.id = 'staffNotificationIcon';
                }
            }
        });
        </script>
        
        <!-- Use existing notification system -->
        <script>
        $(document).ready(function() {
            // Show success notification
            @if(session('success'))
                if (typeof window.Notify !== 'undefined') {
                    window.Notify.success('{{ session('success') }}');
                } else if (typeof NotificationSystem !== 'undefined') {
                    new NotificationSystem().success('{{ session('success') }}');
                } else {
                    alert('{{ session('success') }}');
                }
            @endif
            
            // Show error notification
            @if(session('error'))
                if (typeof window.Notify !== 'undefined') {
                    window.Notify.error('{{ session('error') }}');
                } else if (typeof NotificationSystem !== 'undefined') {
                    new NotificationSystem().error('{{ session('error') }}');
                } else {
                    alert('{{ session('error') }}');
                }
            @endif
            
            // Show warning notification
            @if(session('warning'))
                if (typeof window.Notify !== 'undefined') {
                    window.Notify.warning('{{ session('warning') }}', 'Cảnh báo!');
                } else if (typeof NotificationSystem !== 'undefined') {
                    new NotificationSystem().warning('{{ session('warning') }}', 'Cảnh báo!');
                } else {
                    alert('{{ session('warning') }}');
                }
            @endif
        });
        </script>
        
        @stack('modals')
        
        @stack('scripts')
        
        <!-- Toast Container -->
        <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>
        
        <!-- Chatbox Widget -->
        @php
            $canUseChat = false;
            if (Auth::check()) {
                $user = Auth::user();
                $organizationUser = \App\Models\OrganizationUser::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->first();
                if ($organizationUser && $organizationUser->organization) {
                    $canUseChat = $organizationUser->organization->canUseFeature('enable_chat');
                }
            }
        @endphp
        @if($canUseChat)
            @include('components.chatbox-widget')
        @endif
    </body>
</html>

