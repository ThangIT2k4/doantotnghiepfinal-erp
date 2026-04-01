@props([
    'title' => '',
    'subtitle' => '',
    'icon' => null,
    'headerActions' => [],
    'footer' => null,
    'class' => '',
    'hover' => true,
])

<div class="card-modern-blue {{ $hover ? 'card-hover' : '' }} {{ $class }}">
    @if($title || $icon || !empty($headerActions))
    <div class="card-header-modern">
        <div class="d-flex justify-content-between align-items-center">
            <div class="card-title-section">
                @if($icon)
                <i class="{{ $icon }} me-2"></i>
                @endif
                <div>
                    @if($title)
                    <h5 class="card-title mb-0">{{ $title }}</h5>
                    @endif
                    @if($subtitle)
                    <p class="card-subtitle mb-0">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @if(!empty($headerActions))
            <div class="card-header-actions">
                @foreach($headerActions as $action)
                    @include('tenant.components.button', array_merge([
                        'size' => 'sm',
                        'variant' => 'outline-primary',
                    ], $action))
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="card-body-modern">
        {{ $slot }}
    </div>

    @if($footer)
    <div class="card-footer-modern">
        {{ $footer }}
    </div>
    @endif
</div>

@push('styles')
<style>
/* Modern Card with Blue Theme */
.card-modern-blue {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid var(--blue-border);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.card-modern-blue.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.15);
    border-color: var(--blue-light);
}

.card-header-modern {
    padding: 1.5rem;
    background: var(--blue-bg-light);
    border-bottom: 2px solid var(--blue-border);
}

.card-header-modern .card-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--blue-primary);
    margin: 0;
}

.card-header-modern .card-subtitle {
    font-size: 0.9rem;
    color: #666;
    margin: 0.25rem 0 0 0;
}

.card-header-modern .card-title-section {
    display: flex;
    align-items: center;
}

.card-header-modern .card-title-section i {
    font-size: 1.5rem;
    color: var(--blue-primary);
}

.card-body-modern {
    padding: 1.5rem;
}

.card-footer-modern {
    padding: 1rem 1.5rem;
    background: var(--blue-bg-light);
    border-top: 1px solid var(--blue-border);
}
</style>
@endpush

