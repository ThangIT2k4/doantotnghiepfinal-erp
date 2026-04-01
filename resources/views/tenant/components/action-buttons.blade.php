@props([
    'actions' => [], // Array of action configs
    'size' => 'sm', // 'sm', 'md', 'lg'
    'layout' => 'horizontal', // 'horizontal', 'vertical', 'dropdown'
    'dropdownLabel' => 'Hành động',
    'class' => '',
])

@php
    // Luôn sử dụng Flex Style để đồng bộ
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
            @include('tenant.components.button', array_merge([
                'type' => $action['type'] ?? $defaultType,
                'size' => $size,
            ], $action))
        @endforeach
    </div>
@endif

@push('styles')
<style>
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
    .dropdown-menu-status-actions {
        max-height: 300px;
        min-width: 180px;
    }
}
</style>
@endpush

