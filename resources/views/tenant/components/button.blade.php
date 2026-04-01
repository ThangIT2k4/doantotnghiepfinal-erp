@props([
    'type' => 'button', // 'button', 'submit', 'link', 'anchor'
    'variant' => 'primary', // 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark', 'outline-primary', 'outline-secondary', etc.
    'size' => 'md', // 'sm', 'md', 'lg'
    'icon' => null, // Icon class (e.g., 'fas fa-edit')
    'iconPosition' => 'left', // 'left', 'right', 'only'
    'label' => null,
    'url' => null,
    'onclick' => null,
    'disabled' => false,
    'loading' => false,
    'badge' => null,
    'tooltip' => null,
    'class' => '',
    'id' => null,
])

@php
    // Size classes
    $sizeClasses = [
        'sm' => 'btn-sm',
        'md' => '',
        'lg' => 'btn-lg'
    ];
    $sizeClass = $sizeClasses[$size] ?? '';

    // Variant classes
    $variantClass = strpos($variant, 'outline-') === 0 
        ? "btn btn-{$variant}" 
        : "btn btn-{$variant}";
    
    // Icon-only button styling
    $isIconOnly = ($iconPosition === 'only' || ($icon && !$label));
    if ($isIconOnly) {
        $class .= ' btn-icon-only';
    }
    
    // Combine classes
    $buttonClasses = trim("{$variantClass} {$sizeClass} {$class}");
    
    // Icon position
    $showIconLeft = ($icon && ($iconPosition === 'left' || $iconPosition === 'only'));
    $showIconRight = ($icon && $iconPosition === 'right');
    $showLabel = ($label && $iconPosition !== 'only');
    
    // Determine if it's a link or button
    $isLink = ($type === 'link' || $type === 'anchor' || $url);
    $tag = $isLink ? 'a' : 'button';
    
    // Attributes
    $attributes = [];
    if ($id) $attributes['id'] = $id;
    if ($tooltip) $attributes['title'] = $tooltip;
    if ($disabled) $attributes['disabled'] = true;
    if ($onclick) $attributes['onclick'] = $onclick;
    if ($url && $isLink) $attributes['href'] = $url;
    if ($type === 'submit') $attributes['type'] = 'submit';
    if ($type === 'button' && !$isLink) $attributes['type'] = 'button';
    
    // Loading state
    if ($loading) {
        $buttonClasses .= ' position-relative';
    }
@endphp

<{{ $tag }} 
    class="{{ $buttonClasses }}"
    @foreach($attributes as $key => $value)
        {{ $key }}="{{ $value }}"
    @endforeach
    @if($loading)
        disabled
    @endif
>
    @if($loading)
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
    @endif
    
    @if($showIconLeft && !$loading)
        <i class="{{ $icon }} me-1"></i>
    @endif
    
    @if($showLabel)
        {{ $label }}
    @endif
    
    @if($badge)
        <span class="badge bg-light text-dark ms-2">{{ $badge }}</span>
    @endif
    
    @if($showIconRight && !$loading)
        <i class="{{ $icon }} ms-1"></i>
    @endif
</{{ $tag }}>

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
    background-color: rgba(13, 110, 253, 0.2);
    border-color: #0d6efd;
    color: #0d6efd;
    box-shadow: 0 2px 6px rgba(13, 110, 253, 0.3);
}

.btn-outline-warning.btn-icon-only {
    color: #ffc107;
    border-color: #ffc107;
}

.btn-outline-warning.btn-icon-only:hover {
    background-color: rgba(255, 193, 7, 0.2);
    border-color: #ffc107;
    color: #ffc107;
    box-shadow: 0 2px 6px rgba(255, 193, 7, 0.3);
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

/* Button Group - Icon Only Buttons */
.btn-group .btn-icon-only {
    border-radius: 0;
    border-width: 1px;
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
    border-right: 1px solid;
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

/* Flex Layout - Bỏ border và underline cho icon-only buttons */
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

/* Edit Button - Màu xanh đậm hơn khi hover */
.btn-outline-warning.btn-icon-only:hover,
.btn-outline-warning:hover {
    background-color: rgba(13, 110, 253, 0.25) !important;
    border-color: #0a58ca !important;
    color: #0a58ca !important;
    box-shadow: 0 2px 6px rgba(10, 88, 202, 0.4);
}

.btn-outline-warning.btn-icon-only:focus,
.btn-outline-warning:focus {
    background-color: rgba(13, 110, 253, 0.25) !important;
    border-color: #0a58ca !important;
    color: #0a58ca !important;
    box-shadow: 0 0 0 0.25rem rgba(10, 88, 202, 0.3);
}

/* Outline Primary - Blue Theme #2766ec */
.btn-outline-primary {
    color: var(--blue-primary, #2766ec) !important;
    border-color: var(--blue-primary, #2766ec) !important;
}

.btn-outline-primary.btn-icon-only:hover,
.btn-outline-primary:hover {
    background-color: var(--blue-bg-light, #F0F4FF) !important;
    border-color: var(--blue-primary, #2766ec) !important;
    color: var(--blue-primary, #2766ec) !important;
    box-shadow: 0 2px 6px rgba(39, 102, 236, 0.3);
    transform: translateY(-1px);
}

.btn-outline-primary.btn-icon-only:focus,
.btn-outline-primary:focus {
    background-color: var(--blue-bg-light, #F0F4FF) !important;
    border-color: var(--blue-primary, #2766ec) !important;
    color: var(--blue-primary, #2766ec) !important;
    box-shadow: 0 0 0 0.25rem rgba(39, 102, 236, 0.25);
}

/* Primary Button - Blue Theme #2766ec */
.btn-primary {
    background-color: var(--blue-primary, #2766ec) !important;
    border-color: var(--blue-primary, #2766ec) !important;
    color: #fff !important;
}

.btn-primary:hover {
    background-color: var(--blue-dark, #1E4FC8) !important;
    border-color: var(--blue-dark, #1E4FC8) !important;
    color: #fff !important;
    box-shadow: 0 2px 6px rgba(39, 102, 236, 0.4);
    transform: translateY(-1px);
}

.btn-primary:focus {
    background-color: var(--blue-dark, #1E4FC8) !important;
    border-color: var(--blue-dark, #1E4FC8) !important;
    color: #fff !important;
    box-shadow: 0 0 0 0.25rem rgba(39, 102, 236, 0.3);
}

.btn-primary.btn-icon-only:hover {
    background-color: var(--blue-dark, #1E4FC8) !important;
    border-color: var(--blue-dark, #1E4FC8) !important;
    color: #fff !important;
    box-shadow: 0 2px 6px rgba(39, 102, 236, 0.4);
}

.btn-primary.btn-icon-only:focus {
    background-color: var(--blue-dark, #1E4FC8) !important;
    border-color: var(--blue-dark, #1E4FC8) !important;
    color: #fff !important;
    box-shadow: 0 0 0 0.25rem rgba(39, 102, 236, 0.3);
}

/* Blue Theme Button - btn-outline-blue */
.btn-outline-blue {
    color: var(--blue-primary, #2766ec) !important;
    border-color: var(--blue-primary, #2766ec) !important;
    background: transparent;
}

.btn-outline-blue:hover {
    background-color: var(--blue-primary, #2766ec) !important;
    border-color: var(--blue-primary, #2766ec) !important;
    color: #fff !important;
    box-shadow: 0 2px 6px rgba(39, 102, 236, 0.3);
    transform: translateY(-1px);
}

.btn-outline-blue:focus {
    background-color: var(--blue-primary, #2766ec) !important;
    border-color: var(--blue-primary, #2766ec) !important;
    color: #fff !important;
    box-shadow: 0 0 0 0.25rem rgba(39, 102, 236, 0.25);
}

/* Primary Blue Button */
.btn-primary-blue {
    background-color: var(--blue-primary, #2766ec) !important;
    border-color: var(--blue-primary, #2766ec) !important;
    color: #fff !important;
}

.btn-primary-blue:hover {
    background-color: var(--blue-dark, #1E4FC8) !important;
    border-color: var(--blue-dark, #1E4FC8) !important;
    color: #fff !important;
    box-shadow: 0 2px 6px rgba(39, 102, 236, 0.4);
    transform: translateY(-1px);
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .btn-icon-only {
        min-width: 36px;
        padding: 0.25rem 0.5rem;
    }
}
</style>
@endpush

