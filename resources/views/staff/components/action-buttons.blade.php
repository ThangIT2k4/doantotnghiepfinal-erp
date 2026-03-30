@props([
    'actions' => [], // Array of action configs
    'size' => 'sm', // 'sm', 'md', 'lg'
    'layout' => 'horizontal', // 'horizontal', 'vertical', 'dropdown'
    'dropdownLabel' => 'Hành động',
    'class' => '',
])

@php
    // Luôn sử dụng Flex Style (như Invoices Index) để đồng bộ
    $wrapperClass = $layout === 'vertical' ? 'd-grid gap-2' : 'd-flex gap-2 flex-wrap';
@endphp

@if($layout === 'dropdown')
    <div class="dropdown {{ $class }}">
        <button class="btn btn-outline-primary btn-{{ $size }} dropdown-toggle dropdown-action-btn {{ strpos($class, 'w-100') !== false ? 'w-100' : '' }}" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-exchange-alt me-1"></i>{{ $dropdownLabel }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-status-actions">
            @foreach($actions as $action)
                @php
                    $icon = $action['icon'] ?? null;
                    $label = $action['label'] ?? 'Action';
                    $url = $action['url'] ?? '#';
                    $onclick = $action['onclick'] ?? null;
                    $disabled = $action['disabled'] ?? false;
                    $divider = $action['divider'] ?? false;
                    $variant = $action['variant'] ?? null;
                    
                    // Map variant to dropdown item class with enhanced colors
                    $itemClass = 'dropdown-item dropdown-item-colored';
                    if ($variant === 'danger' || $variant === 'outline-danger') {
                        $itemClass .= ' text-danger dropdown-item-danger';
                    } elseif ($variant === 'success' || $variant === 'outline-success') {
                        $itemClass .= ' text-success dropdown-item-success';
                    } elseif ($variant === 'warning' || $variant === 'outline-warning') {
                        $itemClass .= ' text-warning dropdown-item-warning';
                    } elseif ($variant === 'info' || $variant === 'outline-info') {
                        $itemClass .= ' text-info dropdown-item-info';
                    } elseif ($variant === 'primary' || $variant === 'outline-primary') {
                        $itemClass .= ' text-primary dropdown-item-primary';
                    }
                @endphp
                @if($divider)
                    <li><hr class="dropdown-divider"></li>
                @else
                    <li>
                        <a class="{{ $itemClass }} {{ $disabled ? 'disabled' : '' }}" 
                           href="{{ $onclick ? 'javascript:void(0)' : $url }}"
                           @if($onclick) onclick="{{ $onclick }}; return false;" @endif
                           @if($disabled) tabindex="-1" aria-disabled="true" @endif>
                            @if($icon)
                                <i class="{{ $icon }} me-2"></i>
                            @endif
                            {{ $label }}
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
@else
    <div class="{{ $wrapperClass }} {{ $class }}">
        @foreach($actions as $action)
            @php
                // Preserve custom class if provided
                $actionClass = $action['class'] ?? '';
                $action['class'] = $actionClass;
                
                // Determine type: if onclick or type='button' is specified, use button, otherwise use link
                $defaultType = (isset($action['onclick']) || (isset($action['type']) && $action['type'] === 'button')) 
                    ? 'button' 
                    : 'link';
            @endphp
            @include('staff.components.button', array_merge([
                'type' => $action['type'] ?? $defaultType,
                'size' => $size,
            ], $action))
        @endforeach
    </div>
@endif

@push('styles')
<style>
/* Icon Only Buttons - Modern Style với màu sắc như index pages */
.btn-icon-only {
    min-width: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    border-width: 1px;
}

.btn-icon-only:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.btn-icon-only:active {
    transform: translateY(0);
}

/* Enhanced hover colors for icon-only buttons - với background color đậm hơn */
.btn-outline-info.btn-icon-only {
    color: #0dcaf0;
    border-color: #0dcaf0;
}

.btn-outline-info.btn-icon-only:hover {
    background-color: rgba(13, 202, 240, 0.2);
    border-color: #0dcaf0;
    color: #0dcaf0;
    box-shadow: 0 2px 6px rgba(13, 202, 240, 0.3);
}

.btn-outline-primary.btn-icon-only {
    color: #0d6efd;
    border-color: #0d6efd;
}

.btn-outline-primary.btn-icon-only:hover {
    background-color: rgba(10, 88, 202, 0.25) !important;
    border-color: #0a58ca !important;
    color: #0a58ca !important;
    box-shadow: 0 2px 6px rgba(10, 88, 202, 0.4);
}

.btn-outline-primary:hover {
    background-color: rgba(10, 88, 202, 0.25) !important;
    border-color: #0a58ca !important;
    color: #0a58ca !important;
    box-shadow: 0 2px 6px rgba(10, 88, 202, 0.4);
}

.btn-outline-primary:focus {
    background-color: rgba(10, 88, 202, 0.25) !important;
    border-color: #0a58ca !important;
    color: #0a58ca !important;
    box-shadow: 0 0 0 0.25rem rgba(10, 88, 202, 0.3);
}

/* Primary Button - Màu xanh đậm hơn khi hover */
.btn-primary:hover {
    background-color: #0a58ca !important;
    border-color: #0a58ca !important;
    color: #fff !important;
    box-shadow: 0 2px 6px rgba(10, 88, 202, 0.4);
}

.btn-primary:focus {
    background-color: #0a58ca !important;
    border-color: #0a58ca !important;
    color: #fff !important;
    box-shadow: 0 0 0 0.25rem rgba(10, 88, 202, 0.3);
}

.btn-primary.btn-icon-only:hover {
    background-color: #0a58ca !important;
    border-color: #0a58ca !important;
    color: #fff !important;
    box-shadow: 0 2px 6px rgba(10, 88, 202, 0.4);
}

.btn-primary.btn-icon-only:focus {
    background-color: #0a58ca !important;
    border-color: #0a58ca !important;
    color: #fff !important;
    box-shadow: 0 0 0 0.25rem rgba(10, 88, 202, 0.3);
}

.btn-outline-warning.btn-icon-only {
    color: #ffc107;
    border-color: #ffc107;
}

.btn-outline-warning.btn-icon-only:hover {
    background-color: rgba(13, 110, 253, 0.25) !important;
    border-color: #0a58ca !important;
    color: #0a58ca !important;
    box-shadow: 0 2px 6px rgba(10, 88, 202, 0.4);
}

.btn-outline-warning:hover {
    background-color: rgba(13, 110, 253, 0.25) !important;
    border-color: #0a58ca !important;
    color: #0a58ca !important;
    box-shadow: 0 2px 6px rgba(10, 88, 202, 0.4);
}

.btn-outline-warning:focus {
    background-color: rgba(13, 110, 253, 0.25) !important;
    border-color: #0a58ca !important;
    color: #0a58ca !important;
    box-shadow: 0 0 0 0.25rem rgba(10, 88, 202, 0.3);
}

.btn-outline-success.btn-icon-only {
    color: #198754;
    border-color: #198754;
}

.btn-outline-success.btn-icon-only:hover {
    background-color: rgba(25, 135, 84, 0.2);
    border-color: #198754;
    color: #198754;
    box-shadow: 0 2px 6px rgba(25, 135, 84, 0.3);
}

.btn-outline-danger.btn-icon-only {
    color: #dc3545;
    border-color: #dc3545;
}

.btn-outline-danger.btn-icon-only:hover {
    background-color: rgba(220, 53, 69, 0.2);
    border-color: #dc3545;
    color: #dc3545;
    box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
}

/* Button Group - Icon Only Buttons (như Viewings Index) */
.btn-group .btn-icon-only {
    border-radius: 0;
    border-right: none; /* Bỏ border bên phải */
    margin-left: 0;
}

.btn-group .btn-icon-only:first-child {
    border-top-left-radius: 0.375rem;
    border-bottom-left-radius: 0.375rem;
    border-right: none;
}

.btn-group .btn-icon-only:last-child {
    border-top-right-radius: 0.375rem;
    border-bottom-right-radius: 0.375rem;
    border-right: 1px solid; /* Giữ border bên phải cho button cuối */
}

.btn-group .btn-icon-only:not(:first-child):not(:last-child) {
    border-right: none;
}

/* Bỏ underline cho links trong buttons */
.btn-group a.btn,
.btn-group a.btn:hover,
.btn-group a.btn:focus,
.btn-group a.btn:active {
    text-decoration: none;
}

/* Đảm bảo border color phù hợp với variant */
.btn-group .btn-outline-primary.btn-icon-only {
    border-color: #0d6efd;
}

.btn-group .btn-outline-primary.btn-icon-only:last-child {
    border-right-color: #0d6efd;
}

.btn-group .btn-outline-warning.btn-icon-only {
    border-color: #ffc107;
}

.btn-group .btn-outline-warning.btn-icon-only:last-child {
    border-right-color: #ffc107;
}

.btn-group .btn-outline-danger.btn-icon-only {
    border-color: #dc3545;
}

.btn-group .btn-outline-danger.btn-icon-only:last-child {
    border-right-color: #dc3545;
}

.btn-group .btn-outline-info.btn-icon-only {
    border-color: #0dcaf0;
}

.btn-group .btn-outline-info.btn-icon-only:last-child {
    border-right-color: #0dcaf0;
}

.btn-group .btn-outline-success.btn-icon-only {
    border-color: #198754;
}

.btn-group .btn-outline-success.btn-icon-only:last-child {
    border-right-color: #198754;
}

/* Delete Button - Enhanced Color */
.btn-danger-hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: #fff;
}

.btn-danger-hover:hover {
    background-color: #bb2d3b;
    border-color: #b02a37;
    color: #fff;
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
}

.btn-danger-hover:focus {
    background-color: #bb2d3b;
    border-color: #b02a37;
    color: #fff;
    box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.5);
}

/* Dropdown Menu - Enhanced Colors with Background */
.dropdown-menu .dropdown-item-colored {
    transition: all 0.2s ease;
}

.dropdown-menu .dropdown-item-primary {
    color: #0d6efd !important;
}

.dropdown-menu .dropdown-item-primary:hover {
    background-color: rgba(13, 110, 253, 0.15) !important;
    color: #0d6efd !important;
    font-weight: 500;
}

.dropdown-menu .dropdown-item-success {
    color: #198754 !important;
}

.dropdown-menu .dropdown-item-success:hover {
    background-color: rgba(25, 135, 84, 0.15) !important;
    color: #198754 !important;
    font-weight: 500;
}

.dropdown-menu .dropdown-item-danger {
    color: #dc3545 !important;
}

.dropdown-menu .dropdown-item-danger:hover {
    background-color: rgba(220, 53, 69, 0.15) !important;
    color: #dc3545 !important;
    font-weight: 500;
}

.dropdown-menu .dropdown-item-warning {
    color: #ffc107 !important;
}

.dropdown-menu .dropdown-item-warning:hover {
    background-color: rgba(255, 193, 7, 0.15) !important;
    color: #ffc107 !important;
    font-weight: 500;
}

.dropdown-menu .dropdown-item-info {
    color: #0dcaf0 !important;
}

.dropdown-menu .dropdown-item-info:hover {
    background-color: rgba(13, 202, 240, 0.15) !important;
    color: #0dcaf0 !important;
    font-weight: 500;
}

/* Dropdown Action Button - Enhanced Color */
.dropdown-action-btn {
    transition: all 0.2s ease;
}

.dropdown-action-btn:hover {
    background-color: rgba(13, 110, 253, 0.1);
    border-color: #0d6efd;
    color: #0d6efd;
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
}

.dropdown-action-btn:focus,
.dropdown-action-btn.show {
    background-color: rgba(13, 110, 253, 0.15);
    border-color: #0d6efd;
    color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Icon spacing in dropdown */
.dropdown-menu .dropdown-item i {
    width: 18px;
    text-align: center;
}

/* Flex Layout - Bỏ border và underline cho icon-only buttons (như Invoices Index) */
.d-flex.gap-2 .btn-icon-only,
.d-flex.gap-2 a.btn {
    text-decoration: none;
}

.d-flex.gap-2 .btn-icon-only:hover,
.d-flex.gap-2 a.btn:hover,
.d-flex.gap-2 .btn-icon-only:focus,
.d-flex.gap-2 a.btn:focus,
.d-flex.gap-2 .btn-icon-only:active,
.d-flex.gap-2 a.btn:active {
    text-decoration: none;
}

/* Bỏ border cho outline buttons trong flex layout */
.d-flex.gap-2 .btn-outline-primary,
.d-flex.gap-2 .btn-outline-warning,
.d-flex.gap-2 .btn-outline-danger,
.d-flex.gap-2 .btn-outline-info,
.d-flex.gap-2 .btn-outline-success {
    border: none;
}

.d-flex.gap-2 .btn-outline-primary:hover,
.d-flex.gap-2 .btn-outline-warning:hover,
.d-flex.gap-2 .btn-outline-danger:hover,
.d-flex.gap-2 .btn-outline-info:hover,
.d-flex.gap-2 .btn-outline-success:hover {
    border: none;
}

/* Dropdown Menu Positioning - Đảm bảo không bị che */
.dropdown-menu-status-actions {
    z-index: 1050 !important;
    max-height: 400px;
    overflow-y: auto;
    min-width: 200px;
}

/* Đảm bảo dropdown không bị che bởi card hoặc container */
.card-body .dropdown {
    position: static;
}

.card-body .dropdown-menu-status-actions {
    position: absolute !important;
    z-index: 1050 !important;
    margin-top: 0.125rem;
}

/* Đảm bảo dropdown hiển thị đúng khi có nhiều items */
.dropdown-menu-status-actions {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(0, 0, 0, 0.15);
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .btn-icon-only {
        min-width: 36px;
        padding: 0.25rem 0.5rem;
    }
    
    .dropdown-menu-status-actions {
        max-height: 300px;
        min-width: 180px;
    }
}
</style>
@endpush

