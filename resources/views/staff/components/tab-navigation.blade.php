@props(['tabs', 'storageKey' => 'tabStates', 'defaultVisible' => []])

<!-- Tabs Navigation -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            @foreach($tabs as $tabId => $tab)
                <button type="button" 
                        class="btn btn-sm btn-outline-{{ $tab['color'] ?? 'primary' }} {{ in_array($tabId, $defaultVisible) ? 'active' : '' }}" 
                        onclick="toggleTab('{{ $tabId }}', this, '{{ $storageKey }}')">
                    @if(isset($tab['icon']))
                        <i class="{{ $tab['icon'] }} me-2"></i>
                    @endif
                    {{ $tab['label'] }}
                    @if(isset($tab['badge']))
                        <span class="badge bg-secondary ms-2">{{ $tab['badge'] }}</span>
                    @endif
                </button>
            @endforeach
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="expandAllTabs('{{ $storageKey }}')">
                <i class="fas fa-expand"></i> Mở tất cả
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="collapseAllTabs('{{ $storageKey }}', {{ json_encode($defaultVisible) }})">
                <i class="fas fa-compress"></i> Đóng tất cả
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
.tab-content {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.btn.active {
    font-weight: 700 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Tab Navigation Active State - Blue Theme */
.card .btn-outline-primary.active {
    background: var(--blue-gradient, linear-gradient(135deg, #1E4FC8 0%, #2766ec 50%, #4A85F0 100%)) !important;
    color: #FFFFFF !important;
    border-color: var(--blue-primary, #2766ec) !important;
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.3) !important;
    font-weight: 700 !important;
}

.card .btn-outline-primary.active i {
    color: #FFFFFF !important;
}

.card .btn-outline-primary.active .badge {
    background: rgba(255, 255, 255, 0.3) !important;
    color: #FFFFFF !important;
}

.tab-content.hidden {
    display: none !important;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/tab-navigation.js') }}"></script>
@endpush

