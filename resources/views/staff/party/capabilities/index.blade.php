@extends('layouts.staff_dashboard')

@section('title', 'Danh sách Capabilities')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.show-page-header', [
            'title' => 'Danh sách Capabilities',
            'subtitle' => 'Tất cả các quyền trong hệ thống',
            'icon' => 'fas fa-key',
            'actions' => [
                [
                    'label' => 'Quay lại',
                    'url' => route('staff.users.index'),
                    'icon' => 'fas fa-arrow-left',
                    'color' => 'outline-secondary'
                ]
            ]
        ])

        <!-- Tabs Navigation -->
        @php
            $iconMap = [
                'ticket' => 'ticket-alt',
                'lease' => 'file-contract',
                'invoice' => 'file-invoice',
                'property' => 'building',
                'party' => 'users'
            ];
            $tabs = [];
            foreach($capabilities as $category => $caps) {
                $icon = $iconMap[$category] ?? 'folder';
                $tabs[$category] = [
                    'label' => ucfirst(str_replace('_', ' ', $category)),
                    'icon' => 'fas fa-' . $icon,
                    'color' => 'primary',
                    'badge' => $caps->count()
                ];
            }
        @endphp
        @include('staff.components.tab-navigation', [
            'tabs' => $tabs,
            'storageKey' => 'capabilitiesTabStates',
            'defaultVisible' => [$capabilities->keys()->first()]
        ])

        <!-- Capabilities by Category -->
        @foreach($capabilities as $category => $caps)
            @php
                $icon = $iconMap[$category] ?? 'folder';
            @endphp
            <div class="card shadow-sm mt-4 tab-content" id="tab-{{ $category }}" style="{{ $loop->first ? '' : 'display: none;' }}">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-{{ $icon }} me-2"></i>
                        {{ ucfirst(str_replace('_', ' ', $category)) }}
                        <small class="text-white-50">({{ $caps->count() }} quyền)</small>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30%">Mã</th>
                                    <th style="width: 50%">Tên</th>
                                    <th style="width: 20%">Thứ tự</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($caps as $cap)
                                    <tr>
                                        <td>
                                            <code class="text-primary">{{ $cap->key_code }}</code>
                                        </td>
                                        <td>
                                            <strong>{{ $cap->name }}</strong>
                                            @if($cap->description)
                                                <br>
                                                <small class="text-muted">{{ $cap->description }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $cap->display_order }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</main>

@push('scripts')
<script>
// Initialize tab navigation for this page
document.addEventListener('DOMContentLoaded', function() {
    TabNavigation.init('capabilitiesTabStates', ['{{ $capabilities->keys()->first() }}']);
});
</script>
@endpush
@endsection

