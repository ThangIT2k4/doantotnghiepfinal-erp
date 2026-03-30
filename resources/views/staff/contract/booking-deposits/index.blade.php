@extends('layouts.staff_dashboard')

@section('title', 'Quản lý đặt cọc')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý đặt cọc',
            'subtitle' => 'Danh sách tất cả đặt cọc trong hệ thống',
            'icon' => 'fas fa-hand-holding-usd',
            'actions' => [
                [
                    'variant' => 'secondary',
                    'label' => 'Cài đặt',
                    'icon' => 'fas fa-cog',
                    'url' => route('staff.booking-deposit-settings.index'),
                    'condition' => auth()->user()->can('contract.lease.view')
                ],
                [
                    'variant' => 'primary',
                    'label' => 'Tạo đặt cọc mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.booking-deposits.create')
                ]
            ]
        ])

        {{-- Session Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Statistics Cards --}}
        @php
            $stats = $stats ?? [
                'total' => 0,
                'pending_approval' => 0,
                'pending' => 0,
                'paid' => 0,
                'cancelled' => 0,
                'refunded' => 0,
                'expired' => 0,
                'paid_without_lease' => 0,
            ];
            
            $statsFormatted = [
                'total' => [
                    'value' => $stats['total'] ?? 0,
                    'label' => 'Tổng cộng',
                    'icon' => 'fa-list',
                    'color' => 'primary',
                    'filter' => '',
                ],
                'pending_approval' => [
                    'value' => $stats['pending_approval'] ?? 0,
                    'label' => 'Chờ duyệt',
                    'icon' => 'fa-clock',
                    'color' => 'warning',
                    'filter' => 'pending_approval',
                    'filterKey' => 'payment_status',
                ],
                'pending' => [
                    'value' => $stats['pending'] ?? 0,
                    'label' => 'Chờ thanh toán',
                    'icon' => 'fa-hourglass-half',
                    'color' => 'warning',
                    'filter' => 'pending',
                    'filterKey' => 'payment_status',
                ],
                'paid' => [
                    'value' => $stats['paid'] ?? 0,
                    'label' => 'Đã thanh toán',
                    'icon' => 'fa-money-bill-wave',
                    'color' => 'success',
                    'filter' => 'paid',
                    'filterKey' => 'payment_status',
                ],
                'paid_without_lease' => [
                    'value' => $stats['paid_without_lease'] ?? 0,
                    'label' => 'Đã thanh toán nhưng chưa có hợp đồng',
                    'icon' => 'fa-exclamation-triangle',
                    'color' => 'info',
                    'filter' => 'paid_without_lease',
                    'filterKey' => 'paid_without_lease',
                ],
                'cancelled' => [
                    'value' => $stats['cancelled'] ?? 0,
                    'label' => 'Đã hủy',
                    'icon' => 'fa-times',
                    'color' => 'danger',
                    'filter' => 'cancelled',
                    'filterKey' => 'payment_status',
                ],
            ];
            
            // Determine current filter for stats highlighting
            $currentFilter = '';
            if (request('paid_without_lease') == '1') {
                $currentFilter = 'paid_without_lease';
            } elseif (request('payment_status')) {
                $currentFilter = request('payment_status');
            }
        @endphp
        
        <div id="stats-container">
            @include('staff.components.statistics-cards', [
                'stats' => $statsFormatted,
                'currentFilter' => $currentFilter,
                'filterKey' => 'payment_status',
                'onFilterClick' => 'htmx-filter',
                'onClearClick' => 'htmx-clear',
                'columns' => 6,
                'action' => route('staff.booking-deposits.index'),
                'tableContainerId' => 'booking-deposits-table-container'
            ])
        </div>

        {{-- Filters --}}
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.booking-deposits.index'),
            'tableContainerId' => 'booking-deposits-table-container',
            'formId' => 'booking-deposits-filters-form',
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Mã đặt cọc, tên khách thuê, BĐS...',
                    'value' => request('search'),
                    'col' => 'col-md-3',
                ],
                [
                    'name' => 'payment_status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'value' => request('payment_status'),
                    'options' => [
                        '' => 'Tất cả',
                        'pending_approval' => 'Chờ duyệt',
                        'pending' => 'Chờ thanh toán',
                        'paid' => 'Đã thanh toán',
                        'refunded' => 'Hoàn tiền',
                        'expired' => 'Hết hạn',
                        'cancelled' => 'Đã hủy',
                    ],
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'deposit_type',
                    'label' => 'Loại đặt cọc',
                    'type' => 'select',
                    'value' => request('deposit_type'),
                    'options' => [
                        '' => 'Tất cả',
                        'booking' => 'Đặt cọc',
                        'security' => 'Cọc an ninh',
                        'advance' => 'Trả trước',
                    ],
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'property_id',
                    'label' => 'Bất động sản',
                    'type' => 'select',
                    'value' => request('property_id'),
                    'options' => collect($properties ?? [])->mapWithKeys(function($property) {
                        return [$property->id => $property->name];
                    })->prepend('Tất cả', '')->toArray(),
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'agent_id',
                    'label' => 'Nhân viên',
                    'type' => 'select',
                    'value' => request('agent_id'),
                    'options' => collect($agents ?? [])->mapWithKeys(function($agent) {
                        $name = $agent->userProfile->full_name ?? $agent->full_name ?? 'N/A';
                        return [$agent->id => $name];
                    })->prepend('Tất cả', '')->toArray(),
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'viewing_id',
                    'label' => 'Lịch hẹn',
                    'type' => 'select',
                    'value' => request('viewing_id'),
                    'options' => collect($viewings ?? [])->mapWithKeys(function($viewing) {
                        $label = '#' . $viewing->id . ' - ' . ($viewing->property->name ?? 'N/A');
                        if ($viewing->unit) {
                            $label .= ' / ' . $viewing->unit->code;
                        }
                        if ($viewing->lead) {
                            $label .= ' - ' . $viewing->lead->name;
                        }
                        return [$viewing->id => $label];
                    })->prepend('Tất cả', '')->toArray(),
                    'col' => 'col-md-2',
                ],
            ],
            'showReset' => true,
            'resetUrl' => route('staff.booking-deposits.index'),
        ])

        {{-- Table --}}
        @include('staff.contract.booking-deposits.partials.table', [
            'bookingDeposits' => $bookingDeposits ?? collect(),
            'sortBy' => request('sort_by', 'created_at'),
            'sortOrder' => request('sort_order', 'desc')
        ])
    </div>
</main>
@endsection

@push('scripts')
<script>
// Load countdown timers on page load and after HTMX swaps
document.addEventListener('DOMContentLoaded', function() {
    initCountdownTimers();
});

// Re-initialize countdown timers after HTMX swaps
document.body.addEventListener('htmx:afterSwap', function(event) {
    if (event.detail.target.id === 'booking-deposits-table-container') {
        initCountdownTimers();
    }
});

// Countdown timer functions
let countdownInterval = null;

function initCountdownTimers() {
    const timers = document.querySelectorAll('.countdown-timer');
    if (timers.length === 0) return;
    
    // Update all timers immediately
    timers.forEach(timer => updateCountdown(timer));
    
    // Create a single interval to update all timers
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    countdownInterval = setInterval(() => {
        timers.forEach(timer => updateCountdown(timer));
    }, 1000);
}

function updateCountdown(timerElement) {
    const dueDateStr = timerElement.getAttribute('data-due-date');
    if (!dueDateStr) return;
    
    const dueDate = new Date(dueDateStr.replace(' ', 'T'));
    const now = new Date();
    const diff = dueDate - now;
    
    const countdownText = timerElement.querySelector('.countdown-text');
    if (!countdownText) return;
    
    if (diff <= 0) {
        countdownText.textContent = 'Đã quá hạn';
        timerElement.classList.remove('text-info', 'text-warning');
        timerElement.classList.add('text-danger');
        // Check if all timers are expired
        const allTimers = document.querySelectorAll('.countdown-timer');
        const activeTimers = Array.from(allTimers).filter(t => {
            const tDueDateStr = t.getAttribute('data-due-date');
            if (!tDueDateStr) return false;
            const tDueDate = new Date(tDueDateStr.replace(' ', 'T'));
            return tDueDate > now;
        });
        if (activeTimers.length === 0 && countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
        return;
    }
    
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    let timeString = '';
    if (days > 0) {
        timeString = `${days} ngày ${hours} giờ ${minutes} phút`;
    } else if (hours > 0) {
        timeString = `${hours} giờ ${minutes} phút ${seconds} giây`;
    } else if (minutes > 0) {
        timeString = `${minutes} phút ${seconds} giây`;
    } else {
        timeString = `${seconds} giây`;
    }
    
    countdownText.textContent = `Còn lại: ${timeString}`;
    
    // Change color based on remaining time
    timerElement.classList.remove('text-info', 'text-warning', 'text-danger');
    if (days === 0 && hours < 1) {
        timerElement.classList.add('text-danger');
    } else if (days === 0 && hours < 24) {
        timerElement.classList.add('text-warning');
    } else {
        timerElement.classList.add('text-info');
    }
}

// Action functions
function approveDeposit(id) {
    Notify.confirm({
        title: 'Xác nhận duyệt đặt cọc',
        message: 'Bạn có chắc chắn muốn duyệt đặt cọc này?',
        type: 'success',
        confirmText: 'Duyệt',
        onConfirm: function() {
            const loadingToast = Notify.toast({
                title: 'Đang xử lý...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });
            
            fetch(`/staff/booking-deposits/${id}/approve`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
            })
            .then(response => {
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Notify.success(data.message, 'Duyệt thành công!');
                    setTimeout(() => {
                        const url = '{{ route("staff.booking-deposits.index") }}';
                        htmx.ajax('GET', url, {
                            target: '#booking-deposits-table-container',
                            swap: 'innerHTML'
                        });
                        htmx.ajax('GET', url, {
                            target: '#stats-container',
                            swap: 'innerHTML'
                        });
                    }, 1500);
                } else {
                    Notify.error(data.message, 'Không thể duyệt đặt cọc');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Có lỗi xảy ra khi duyệt đặt cọc. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
        }
    });
}

function cancelDeposit(id) {
    Notify.confirm({
        title: 'Xác nhận hủy đặt cọc',
        message: 'Bạn có chắc chắn muốn hủy đặt cọc này?',
        details: 'Hành động này có thể ảnh hưởng đến trạng thái phòng.',
        type: 'danger',
        confirmText: 'Hủy đặt cọc',
        onConfirm: function() {
            const loadingToast = Notify.toast({
                title: 'Đang xử lý...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });
            
            fetch(`/staff/booking-deposits/${id}/cancel`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
            })
            .then(response => {
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Notify.success(data.message, 'Hủy đặt cọc thành công!');
                    setTimeout(() => {
                        const url = '{{ route("staff.booking-deposits.index") }}';
                        htmx.ajax('GET', url, {
                            target: '#booking-deposits-table-container',
                            swap: 'innerHTML'
                        });
                        htmx.ajax('GET', url, {
                            target: '#stats-container',
                            swap: 'innerHTML'
                        });
                    }, 1500);
                } else {
                    Notify.error(data.message, 'Không thể hủy đặt cọc');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Có lỗi xảy ra khi hủy đặt cọc. Vui lòng thử lại.', 'Lỗi hệ thống');
            });
        }
    });
}

function refundDeposit(id) {
    Notify.confirm({
        title: 'Xác nhận hoàn tiền',
        message: 'Bạn có chắc chắn muốn hoàn tiền cho đặt cọc này?',
        details: 'Số tiền sẽ được hoàn lại cho khách hàng.',
        type: 'warning',
        confirmText: 'Hoàn tiền',
        onConfirm: function() {
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });
        
        fetch(`/staff/booking-deposits/${id}/refund`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
        .then(response => {
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Hoàn tiền thành công!');
                    setTimeout(() => {
                        const url = '{{ route("staff.booking-deposits.index") }}';
                        htmx.ajax('GET', url, {
                            target: '#booking-deposits-table-container',
                            swap: 'innerHTML'
                        });
                        htmx.ajax('GET', url, {
                            target: '#stats-container',
                            swap: 'innerHTML'
                        });
                    }, 1500);
            } else {
                Notify.error(data.message, 'Không thể hoàn tiền');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi hoàn tiền. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
        }
    });
}

function deleteDeposit(id, reference) {
    Notify.confirmDelete(`đặt cọc "${reference}"`, function() {
        const loadingToast = Notify.toast({
            title: 'Đang xử lý...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });
        
        fetch(`/staff/booking-deposits/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
        })
        .then(response => {
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Xóa thành công!');
                setTimeout(() => {
                    const url = '{{ route("staff.booking-deposits.index") }}';
                    htmx.ajax('GET', url, {
                        target: '#booking-deposits-table-container',
                        swap: 'innerHTML'
                    });
                    htmx.ajax('GET', url, {
                        target: '#stats-container',
                        swap: 'innerHTML'
                    });
                }, 1500);
            } else {
                Notify.error(data.message, 'Không thể xóa đặt cọc');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Có lỗi xảy ra khi xóa đặt cọc. Vui lòng thử lại.', 'Lỗi hệ thống');
        });
    });
}
</script>
@endpush
