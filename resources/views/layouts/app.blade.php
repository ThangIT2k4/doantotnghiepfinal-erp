<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $isTenantArea = request()->is('tenant*');
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @auth
        <meta name="user-id" content="{{ auth()->id() }}">
        @endauth

        <title>@yield('title', config('app.name', 'ZoroRMS'))</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('assets/image/logo2.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        @if($isTenantArea)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
        @endif
        
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        
        <!-- Custom CSS -->
        
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/preloader.css') }}?v={{ time() }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/notifications.css') }}?v={{ time() }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/home.css') }}?v={{ time() }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/pagination-custom.css') }}?v={{ time() }}">
        @if($isTenantArea)
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/tenant/tenant-glass-ui.css') }}?v={{ time() }}">
        @endif
        <!-- Additional CSS -->
        @stack('styles')
    </head>
    <body class="{{ $isTenantArea ? 'glass-ui-tenant' : '' }}">
        {{-- Preloader --}}
        <x-preloader />

        @include('partials.header')

        <main>
            @yield('content')
        </main>

        {{-- @include('partials.footer') --}}
        
        <!-- Scripts -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- HTMX for filters and AJAX without JavaScript -->
        <script src="https://unpkg.com/htmx.org@1.9.10"></script>
        
        <script src="{{ asset('assets/js/preloader.js') }}?v={{ time() }}"></script>
        <script src="{{ asset('assets/js/home.js') }}?v={{ time() }}"></script>
        <script src="{{ asset('assets/js/notifications.js') }}?v={{ time() }}"></script>
        
        @auth
        <!-- Tenant notifications for real-time updates -->
        <script src="{{ asset('assets/js/user/notifications.js') }}?v={{ time() }}"></script>
        @endauth
        
        <!-- Additional Scripts -->
        @stack('scripts')
    </body>
</html>


