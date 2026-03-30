@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'actions' => []
])

<!-- Index Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0 text-gray-800">
                    @if($icon)
                        <i class="{{ $icon }} me-2"></i>
                    @endif
                    {{ $title }}
                </h1>
                @if($subtitle)
                    <p class="text-muted mb-0">{{ $subtitle }}</p>
                @endif
            </div>
            @if(count($actions) > 0)
                <div class="d-flex gap-2">
                    @foreach($actions as $action)
                        @include('staff.components.button', array_merge([
                            'type' => 'link',
                            'size' => 'md',
                        ], $action))
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

