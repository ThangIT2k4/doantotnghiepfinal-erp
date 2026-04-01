@props([
    'title' => '',
    'icon' => null,
    'items' => [], // Array of ['label' => '', 'value' => '', 'type' => 'text|price|badge']
    'class' => '',
])

<div class="info-card-modern {{ $class }}">
    @if($title || $icon)
    <div class="info-card-title">
        @if($icon)
        <i class="{{ $icon }}"></i>
        @endif
        @if($title)
        <span>{{ $title }}</span>
        @endif
    </div>
    @endif

    @if(!empty($items))
    <div class="info-items">
        @foreach($items as $item)
        <div class="info-item">
            <div class="info-label">{{ $item['label'] ?? '' }}</div>
            <div class="info-value {{ isset($item['type']) && $item['type'] === 'price' ? 'price' : '' }}">
                @if(isset($item['type']) && $item['type'] === 'badge')
                    <span class="badge bg-{{ $item['badgeVariant'] ?? 'primary' }}">{{ $item['value'] ?? '' }}</span>
                @else
                    {{ $item['value'] ?? '' }}
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @isset($slot)
        {{ $slot }}
    @endisset
</div>

@push('styles')
<style>
/* Modern Info Cards */
.info-card-modern {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid var(--blue-border);
    height: 100%;
}

.info-card-modern:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.15);
    border-color: var(--blue-light);
}

.info-card-modern .info-card-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--blue-primary);
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 3px solid var(--blue-gradient);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.info-card-modern .info-card-title i {
    font-size: 1.5rem;
}

.info-card-modern .info-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1rem 0;
    border-bottom: 1px solid #F5F5F5;
}

.info-card-modern .info-item:last-child {
    border-bottom: none;
}

.info-card-modern .info-label {
    font-weight: 600;
    color: #666;
    flex: 0 0 40%;
}

.info-card-modern .info-value {
    color: #333;
    text-align: right;
    flex: 1;
    font-weight: 500;
}

.info-card-modern .info-value.price {
    color: var(--blue-primary);
    font-weight: 700;
    font-size: 1.1rem;
}
</style>
@endpush

