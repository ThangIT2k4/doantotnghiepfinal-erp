{{--
    ========================================
    INLINE PRELOADER (for AJAX/SPA)
    Preloader nhỏ gọn để dùng trong page
    
    Usage: <x-preloader-inline />
    ========================================
--}}

@props([
    'size' => 'md', // sm, md, lg
    'color' => 'primary' // primary, success, danger, warning, info
])

@php
    $sizeClasses = [
        'sm' => 'width: 30px; height: 30px;',
        'md' => 'width: 50px; height: 50px;',
        'lg' => 'width: 70px; height: 70px;'
    ];
    
    $colorClasses = [
        'primary' => '#667eea',
        'success' => '#10b981',
        'danger' => '#ef4444',
        'warning' => '#f59e0b',
        'info' => '#3b82f6'
    ];
@endphp

<div class="inline-preloader" style="display: flex; justify-content: center; align-items: center; padding: 20px;">
    <div style="{{ $sizeClasses[$size] ?? $sizeClasses['md'] }} position: relative;">
        <div style="
            width: 100%;
            height: 100%;
            border: 3px solid rgba(102, 126, 234, 0.2);
            border-top-color: {{ $colorClasses[$color] ?? $colorClasses['primary'] }};
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        "></div>
    </div>
</div>

<style>
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

