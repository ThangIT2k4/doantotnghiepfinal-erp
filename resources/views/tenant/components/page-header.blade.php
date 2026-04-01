@props([
    'title' => '',
    'subtitle' => '',
    'icon' => null,
    'actions' => [],
    'breadcrumbs' => [],
    'class' => '',
])

<div class="page-header-blue {{ $class }}">
    @if(!empty($breadcrumbs))
    <nav aria-label="breadcrumb" class="breadcrumb-blue mb-3">
        <ol class="breadcrumb mb-0">
            @foreach($breadcrumbs as $breadcrumb)
                @if(isset($breadcrumb['url']))
                    <li class="breadcrumb-item">
                        <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                    </li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['label'] }}</li>
                @endif
            @endforeach
        </ol>
    </nav>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="header-content">
            @if($icon)
            <div class="header-icon">
                <i class="{{ $icon }}"></i>
            </div>
            @endif
            <div>
                <h1 class="page-title">{{ $title }}</h1>
                @if($subtitle)
                <p class="page-subtitle">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        
        @if(!empty($actions))
        <div class="header-actions">
            @foreach($actions as $action)
                @php
                    // Default variant for back buttons
                    $defaultVariant = isset($action['variant']) ? $action['variant'] : 'outline-blue';
                    // Check if it's a back button
                    $isBackButton = (isset($action['label']) && (stripos($action['label'], 'quay lại') !== false || stripos($action['label'], 'back') !== false));
                    if ($isBackButton && !isset($action['variant'])) {
                        $defaultVariant = 'outline-secondary';
                    }
                @endphp
                @include('tenant.components.button', array_merge([
                    'type' => 'link',
                    'variant' => $defaultVariant,
                ], $action))
            @endforeach
        </div>
        @endif
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
@endpush

