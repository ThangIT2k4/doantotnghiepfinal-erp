@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'breadcrumbs' => null,
    'actions' => []
])

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            @if($icon)
                <i class="{{ $icon }} me-2"></i>
            @endif
            {{ $title }}
        </h1>
        @if($subtitle)
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        @endif
        @if($breadcrumbs)
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 mt-2">
                    @foreach($breadcrumbs as $breadcrumb)
                        @if(isset($breadcrumb['url']))
                            <li class="breadcrumb-item">
                                <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                            </li>
                        @else
                            <li class="breadcrumb-item active">{{ $breadcrumb['label'] }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif
    </div>
    @if(count($actions) > 0)
        <div class="d-flex gap-2">
            @foreach($actions as $action)
                @php
                    $color = $action['color'] ?? 'primary';
                    $btnClass = "btn btn-{$color}";
                    $type = $action['type'] ?? 'link'; // 'link' or 'button'
                    $onclick = $action['onclick'] ?? null;
                    $url = $action['url'] ?? '#';
                @endphp
                @if($type === 'button' || $onclick)
                    <button type="button" class="{{ $btnClass }}" 
                            @if($onclick) onclick="{{ $onclick }}" @endif
                            @if($type === 'button' && !$onclick && $url !== '#') onclick="window.location.href='{{ $url }}'" @endif>
                        @if(isset($action['icon']))
                            <i class="{{ $action['icon'] }} me-1"></i>
                        @endif
                        {{ $action['label'] }}
                    </button>
                @else
                    <a href="{{ $url }}" class="{{ $btnClass }}">
                        @if(isset($action['icon']))
                            <i class="{{ $action['icon'] }} me-1"></i>
                        @endif
                        {{ $action['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    @endif
</div>
