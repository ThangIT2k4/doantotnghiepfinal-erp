@props([
    'stats' => [], // Array of stat items: ['icon' => '', 'value' => '', 'label' => '', 'url' => '', 'active' => false, 'data-filter' => '', 'hx-get' => '', 'hx-target' => '', 'hx-swap' => 'innerHTML', 'hx-push-url' => 'true', 'hx-indicator' => '', 'title' => '', 'amount' => '', 'statusClass' => '']
    'columns' => 4, // Number of columns (1-4)
    'class' => '',
])

@php
    $colClass = match($columns) {
        1 => 'col-12',
        2 => 'col-md-6',
        3 => 'col-md-4',
        4 => 'col-md-6 col-lg-3',
        default => 'col-md-6 col-lg-3'
    };
@endphp

<div class="stats-section-blue {{ $class }}" id="stats-cards-container">
    <div class="row g-4">
        @foreach($stats as $stat)
        <div class="{{ $colClass }}">
            @php
                $statClasses = ['stat-card-blue'];
                if (isset($stat['statusClass']) && $stat['statusClass']) {
                    $statClasses[] = $stat['statusClass'];
                }
                if (isset($stat['active']) && $stat['active']) {
                    $statClasses[] = 'active-filter';
                }
                if (isset($stat['data-filter']) && $stat['data-filter']) {
                    $statClasses[] = $stat['data-filter'];
                }
            @endphp
            <div class="{{ implode(' ', $statClasses) }}" 
                 @if(isset($stat['data-filter']) && $stat['data-filter']) 
                 data-filter="{{ $stat['data-filter'] }}"
                 @endif
                 @if(isset($stat['hx-get']) && $stat['hx-get'])
                 hx-get="{{ $stat['hx-get'] }}"
                 @if(isset($stat['hx-target']) && $stat['hx-target'])
                 hx-target="{{ $stat['hx-target'] }}"
                 @endif
                 hx-swap="{{ $stat['hx-swap'] ?? 'innerHTML' }}"
                 @if(isset($stat['hx-push-url']))
                 hx-push-url="{{ $stat['hx-push-url'] }}"
                 @endif
                 @if(isset($stat['hx-indicator']) && $stat['hx-indicator'])
                 hx-indicator="{{ $stat['hx-indicator'] }}"
                 @endif
                 @if(isset($stat['hx-trigger']) && $stat['hx-trigger'])
                 hx-trigger="{{ $stat['hx-trigger'] }}"
                 @else
                 hx-trigger="click"
                 @endif
                 @elseif(isset($stat['url']) && $stat['url']) 
                 onclick="window.location.href='{{ $stat['url'] }}'"
                 @endif
                 @if(isset($stat['title']) && $stat['title'])
                 title="{{ $stat['title'] }}"
                 @endif>
                <div class="stat-icon">
                    <i class="{{ $stat['icon'] ?? 'fas fa-chart-line' }}"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stat['value'] ?? '0' }}</h3>
                    <p>{{ $stat['label'] ?? 'Label' }}</p>
                    @if(isset($stat['amount']) && $stat['amount'])
                    <div class="stat-amount" style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">{{ $stat['amount'] }}</div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

@push('styles')
<style>
/* Stats Cards with Blue Theme */
.stats-section-blue {
    margin-bottom: 2rem;
}

.stat-card-blue {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 2px solid var(--blue-border);
    cursor: pointer;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.stat-card-blue::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: var(--blue-gradient);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.stat-card-blue:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.2);
    border-color: var(--blue-light);
}

.stat-card-blue:hover::before {
    transform: scaleX(1);
}

/* Status-specific hover colors */
.stat-card-blue.active:hover,
.stat-card-blue[data-filter="active"]:hover {
    border-color: var(--status-active);
    box-shadow: 0 8px 30px rgba(40, 167, 69, 0.25);
}

.stat-card-blue.expiring:hover,
.stat-card-blue[data-filter="expiring"]:hover {
    border-color: var(--status-expiring);
    box-shadow: 0 8px 30px rgba(255, 152, 0, 0.25);
}

.stat-card-blue.expired:hover,
.stat-card-blue[data-filter="expired"]:hover {
    border-color: var(--status-expired);
    box-shadow: 0 8px 30px rgba(220, 53, 69, 0.25);
}

.stat-card-blue.total:hover,
.stat-card-blue[data-filter="all"]:hover {
    border-color: var(--status-all);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.25);
}

.stat-card-blue.active-filter {
    background: var(--blue-bg-light);
    border-color: var(--blue-primary) !important;
    border-width: 3px !important;
    box-shadow: 0 6px 25px rgba(39, 102, 236, 0.35);
    transform: translateY(-3px);
}

.stat-card-blue.active-filter::before {
    transform: scaleX(1);
    height: 5px;
    background: var(--blue-primary);
}

.stat-card-blue .stat-icon {
    font-size: 2.5rem;
    color: var(--blue-primary);
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.stat-card-blue:hover .stat-icon {
    transform: scale(1.1);
}

.stat-card-blue .stat-content h3 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--blue-primary);
    margin-bottom: 0.5rem;
}

.stat-card-blue .stat-content p {
    font-size: 1rem;
    color: #666;
    margin: 0;
    font-weight: 500;
}

/* Status-specific colors for stat cards */
.stat-card-blue.active .stat-icon,
.stat-card-blue[data-filter="active"] .stat-icon {
    color: var(--status-active);
}

.stat-card-blue.active .stat-content h3,
.stat-card-blue[data-filter="active"] .stat-content h3 {
    color: var(--status-active);
}

.stat-card-blue.active.active-filter {
    background: var(--status-active-light) !important;
    border-color: var(--status-active-border) !important;
    box-shadow: 0 6px 25px rgba(40, 167, 69, 0.4) !important;
}

.stat-card-blue.active.active-filter::before {
    background: var(--status-active-gradient);
    height: 5px;
}

.stat-card-blue.expiring .stat-icon,
.stat-card-blue[data-filter="expiring"] .stat-icon {
    color: var(--status-expiring);
}

.stat-card-blue.expiring .stat-content h3,
.stat-card-blue[data-filter="expiring"] .stat-content h3 {
    color: var(--status-expiring);
}

.stat-card-blue.expiring.active-filter {
    background: var(--status-expiring-light) !important;
    border-color: var(--status-expiring-border) !important;
    box-shadow: 0 6px 25px rgba(255, 152, 0, 0.4) !important;
}

.stat-card-blue.expiring.active-filter::before {
    background: var(--status-expiring-gradient);
    height: 5px;
}

.stat-card-blue.expired .stat-icon,
.stat-card-blue[data-filter="expired"] .stat-icon {
    color: var(--status-expired);
}

.stat-card-blue.expired .stat-content h3,
.stat-card-blue[data-filter="expired"] .stat-content h3 {
    color: var(--status-expired);
}

.stat-card-blue.expired.active-filter {
    background: var(--status-expired-light) !important;
    border-color: var(--status-expired-border) !important;
    box-shadow: 0 6px 25px rgba(220, 53, 69, 0.4) !important;
}

.stat-card-blue.expired.active-filter::before {
    background: var(--status-expired-gradient);
    height: 5px;
}

.stat-card-blue.total .stat-icon,
.stat-card-blue[data-filter="all"] .stat-icon {
    color: var(--status-all);
}

.stat-card-blue.total .stat-content h3,
.stat-card-blue[data-filter="all"] .stat-content h3 {
    color: var(--status-all);
}

.stat-card-blue.total.active-filter {
    background: var(--status-all-light) !important;
    border-color: var(--status-all-border) !important;
    box-shadow: 0 6px 25px rgba(39, 102, 236, 0.4) !important;
}

.stat-card-blue.total.active-filter::before {
    background: var(--status-all-gradient);
    height: 5px;
}

/* Invoice status colors for stat cards */
.stat-card-blue.paid .stat-icon,
.stat-card-blue[data-filter="paid"] .stat-icon {
    color: var(--status-active);
}

.stat-card-blue.paid .stat-content h3,
.stat-card-blue[data-filter="paid"] .stat-content h3 {
    color: var(--status-active);
}

.stat-card-blue.paid.active-filter {
    background: var(--status-active-light) !important;
    border-color: var(--status-active-border) !important;
    box-shadow: 0 6px 25px rgba(40, 167, 69, 0.4) !important;
}

.stat-card-blue.paid.active-filter::before {
    background: var(--status-active-gradient);
    height: 5px;
}

.stat-card-blue.pending .stat-icon,
.stat-card-blue[data-filter="pending"] .stat-icon {
    color: var(--status-expiring);
}

.stat-card-blue.pending .stat-content h3,
.stat-card-blue[data-filter="pending"] .stat-content h3 {
    color: var(--status-expiring);
}

.stat-card-blue.pending.active-filter {
    background: var(--status-expiring-light) !important;
    border-color: var(--status-expiring-border) !important;
    box-shadow: 0 6px 25px rgba(255, 152, 0, 0.4) !important;
}

.stat-card-blue.pending.active-filter::before {
    background: var(--status-expiring-gradient);
    height: 5px;
}

.stat-card-blue.overdue .stat-icon,
.stat-card-blue[data-filter="overdue"] .stat-icon {
    color: var(--status-expired);
}

.stat-card-blue.overdue .stat-content h3,
.stat-card-blue[data-filter="overdue"] .stat-content h3 {
    color: var(--status-expired);
}

.stat-card-blue.overdue.active-filter {
    background: var(--status-expired-light) !important;
    border-color: var(--status-expired-border) !important;
    box-shadow: 0 6px 25px rgba(220, 53, 69, 0.4) !important;
}

.stat-card-blue.overdue.active-filter::before {
    background: var(--status-expired-gradient);
    height: 5px;
}

/* Status-specific hover colors for invoice statuses */
.stat-card-blue.paid:hover,
.stat-card-blue[data-filter="paid"]:hover {
    border-color: var(--status-active);
    box-shadow: 0 8px 30px rgba(40, 167, 69, 0.25);
}

.stat-card-blue.pending:hover,
.stat-card-blue[data-filter="pending"]:hover {
    border-color: var(--status-expiring);
    box-shadow: 0 8px 30px rgba(255, 152, 0, 0.25);
}

.stat-card-blue.overdue:hover,
.stat-card-blue[data-filter="overdue"]:hover {
    border-color: var(--status-expired);
    box-shadow: 0 8px 30px rgba(220, 53, 69, 0.25);
}

/* Notification status colors for stat cards */
.stat-card-blue.total .stat-icon,
.stat-card-blue[data-filter="all"] .stat-icon {
    color: var(--status-notification-all, #2766ec);
}

.stat-card-blue.total .stat-content h3,
.stat-card-blue[data-filter="all"] .stat-content h3 {
    color: var(--status-notification-all, #2766ec);
}

.stat-card-blue.total:hover,
.stat-card-blue[data-filter="all"]:hover {
    border-color: var(--status-notification-all, #2766ec);
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.25);
}

.stat-card-blue.total.active-filter {
    background: var(--status-notification-all-light, #dbeafe) !important;
    border-color: var(--status-notification-all-border, #2766ec) !important;
    box-shadow: 0 6px 25px rgba(39, 102, 236, 0.4) !important;
}

.stat-card-blue.total.active-filter::before {
    background: var(--status-notification-all-gradient, linear-gradient(135deg, #1E4FC8 0%, #2766ec 100%)) !important;
    height: 5px;
}

.stat-card-blue.read .stat-icon,
.stat-card-blue[data-filter="read"] .stat-icon {
    color: var(--status-notification-read, #10b981);
}

.stat-card-blue.read .stat-content h3,
.stat-card-blue[data-filter="read"] .stat-content h3 {
    color: var(--status-notification-read, #10b981);
}

.stat-card-blue.read:hover,
.stat-card-blue[data-filter="read"]:hover {
    border-color: var(--status-notification-read, #10b981);
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.25);
}

.stat-card-blue.read.active-filter {
    background: var(--status-notification-read-light, #d1fae5) !important;
    border-color: var(--status-notification-read-border, #10b981) !important;
    box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4) !important;
}

.stat-card-blue.read.active-filter::before {
    background: var(--status-notification-read-gradient, linear-gradient(135deg, #059669 0%, #10b981 100%)) !important;
    height: 5px;
}

.stat-card-blue.unread .stat-icon,
.stat-card-blue[data-filter="unread"] .stat-icon {
    color: var(--status-notification-unread, #3b82f6);
}

.stat-card-blue.unread .stat-content h3,
.stat-card-blue[data-filter="unread"] .stat-content h3 {
    color: var(--status-notification-unread, #3b82f6);
}

.stat-card-blue.unread:hover,
.stat-card-blue[data-filter="unread"]:hover {
    border-color: var(--status-notification-unread, #3b82f6);
    box-shadow: 0 8px 30px rgba(59, 130, 246, 0.25);
}

.stat-card-blue.unread.active-filter {
    background: var(--status-notification-unread-light, #dbeafe) !important;
    border-color: var(--status-notification-unread-border, #3b82f6) !important;
    box-shadow: 0 6px 25px rgba(59, 130, 246, 0.4) !important;
}

.stat-card-blue.unread.active-filter::before {
    background: var(--status-notification-unread-gradient, linear-gradient(135deg, #2563eb 0%, #3b82f6 100%)) !important;
    height: 5px;
}

.stat-card-blue.important .stat-icon,
.stat-card-blue[data-filter="important"] .stat-icon {
    color: var(--status-notification-important, #ef4444);
}

.stat-card-blue.important .stat-content h3,
.stat-card-blue[data-filter="important"] .stat-content h3 {
    color: var(--status-notification-important, #ef4444);
}

.stat-card-blue.important:hover,
.stat-card-blue[data-filter="important"]:hover {
    border-color: var(--status-notification-important, #ef4444);
    box-shadow: 0 8px 30px rgba(239, 68, 68, 0.25);
}

.stat-card-blue.important.active-filter {
    background: var(--status-notification-important-light, #fee2e2) !important;
    border-color: var(--status-notification-important-border, #ef4444) !important;
    box-shadow: 0 6px 25px rgba(239, 68, 68, 0.4) !important;
}

.stat-card-blue.important.active-filter::before {
    background: var(--status-notification-important-gradient, linear-gradient(135deg, #dc2626 0%, #ef4444 100%)) !important;
    height: 5px;
}
</style>
@endpush

