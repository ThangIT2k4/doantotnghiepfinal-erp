@extends('layouts.staff_dashboard')

@section('title', 'Cài đặt đặt cọc')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Cài đặt đặt cọc',
            'subtitle' => 'Cấu hình thời gian chờ thanh toán sau khi phê duyệt',
            'icon' => 'fas fa-cog',
            'actions' => []
        ])

        <!-- Session Messages -->
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

        <div class="row">
            <!-- Payment Due Time Settings -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Cài đặt thời gian chờ thanh toán
                        </h6>
                    </div>
                    <div class="card-body">
                        <form id="paymentDueTimeForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_due_hours" class="form-label">
                                            Số giờ <span class="text-danger">*</span>
                                        </label>
                                        @php
                                            $defaultPaymentCycle = $organization->defaultPaymentCycle;
                                            $paymentDueMinutes = $defaultPaymentCycle->payment_due_hours ?? 4320; // Default 72 hours = 4320 minutes
                                        @endphp
                                        <input type="number" 
                                               class="form-control" 
                                               id="payment_due_hours" 
                                               name="payment_due_hours" 
                                               value="{{ floor($paymentDueMinutes / 60) }}" 
                                               min="0" 
                                               max="720" 
                                               required>
                                        <div class="form-text">Số giờ chờ thanh toán (0-720 giờ)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_due_minutes" class="form-label">
                                            Số phút <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="payment_due_minutes" 
                                               name="payment_due_minutes" 
                                               value="{{ $paymentDueMinutes % 60 }}" 
                                               min="0" 
                                               max="59" 
                                               required>
                                        <div class="form-text">Số phút chờ thanh toán (0-59 phút)</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Thời gian chờ thanh toán hiện tại:</strong> 
                                    @php
                                        $currentHours = floor($paymentDueMinutes / 60);
                                        $currentMinutes = $paymentDueMinutes % 60;
                                        $totalHours = $paymentDueMinutes / 60;
                                    @endphp
                                    <span id="current-time-display">
                                        {{ $currentHours }} giờ {{ $currentMinutes }} phút 
                                        ({{ number_format($totalHours, 2) }} giờ = {{ number_format($totalHours / 24, 2) }} ngày)
                                    </span>
                                </div>
                            </div>
                            
                            @include('staff.components.action-buttons', [
                                'layout' => 'horizontal',
                                'size' => 'md',
                                'actions' => [
                                    [
                                        'type' => 'button',
                                        'variant' => 'primary',
                                        'label' => 'Lưu cài đặt',
                                        'icon' => 'fas fa-save',
                                        'iconPosition' => 'left',
                                        'onclick' => 'updatePaymentDueTime()'
                                    ]
                                ]
                            ])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
function updatePaymentDueTime() {
    const hours = parseInt(document.getElementById('payment_due_hours').value) || 0;
    const minutes = parseInt(document.getElementById('payment_due_minutes').value) || 0;
    
    if (hours < 0 || hours > 720) {
        Notify.error('Vui lòng nhập số giờ hợp lệ (0-720 giờ)', 'Dữ liệu không hợp lệ');
        return;
    }
    
    if (minutes < 0 || minutes > 59) {
        Notify.error('Vui lòng nhập số phút hợp lệ (0-59 phút)', 'Dữ liệu không hợp lệ');
        return;
    }
    
    if (hours === 0 && minutes === 0) {
        Notify.error('Thời gian chờ thanh toán phải lớn hơn 0', 'Dữ liệu không hợp lệ');
        return;
    }
    
    // Calculate total minutes
    const totalMinutes = (hours * 60) + minutes;
    
    const loadingToast = Notify.toast({
        title: 'Đang cập nhật...',
        message: 'Vui lòng chờ trong giây lát',
        type: 'info',
        duration: 0
    });
    
    fetch('{{ route("staff.booking-deposit-settings.update-payment-due-hours") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            payment_due_hours: totalMinutes // Store as minutes in database (backward compatible with hours field)
        })
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
            Notify.success(data.message, 'Cập nhật thành công!');
            // Update the display
            const totalMinutes = data.payment_due_hours;
            const displayHours = Math.floor(totalMinutes / 60);
            const displayMinutes = totalMinutes % 60;
            const totalHours = (totalMinutes / 60).toFixed(2);
            const totalDays = (totalMinutes / 60 / 24).toFixed(2);
            
            const currentTimeDisplay = document.getElementById('current-time-display');
            if (currentTimeDisplay) {
                currentTimeDisplay.textContent = `${displayHours} giờ ${displayMinutes} phút (${totalHours} giờ = ${totalDays} ngày)`;
            }
            
            // Update form values
            document.getElementById('payment_due_hours').value = displayHours;
            document.getElementById('payment_due_minutes').value = displayMinutes;
        } else {
            Notify.error(data.message, 'Không thể cập nhật');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Notify.error('Có lỗi xảy ra khi cập nhật cài đặt. Vui lòng thử lại.', 'Lỗi hệ thống');
    });
}
</script>
@endpush

