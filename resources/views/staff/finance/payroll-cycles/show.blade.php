@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết Kỳ Lương')

@section('content')
<main class="main-content">
<div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết Kỳ Lương',
            'subtitle' => 'Thông tin chi tiết về kỳ lương: ' . \Carbon\Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->format('m/Y'),
            'icon' => 'fas fa-calendar-alt',
            'breadcrumbs' => [
                ['label' => 'Kỳ Lương', 'url' => route('staff.payroll-cycles.index')],
                ['label' => \Carbon\Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->format('m/Y'), 'active' => true]
            ]
        ])

    <div class="row">
            {{-- Nội dung chính --}}
        <div class="col-lg-8">
                {{-- Card 1: Thông tin Kỳ Lương --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin Kỳ Lương
                        </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Kỳ lương:</strong></td>
                                    <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->format('m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Trạng thái:</strong></td>
                                    <td>
                                            @include('staff.components.status-badge', [
                                                'status' => $payrollCycle->status,
                                                'type' => 'payroll-cycle'
                                            ])
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày tạo:</strong></td>
                                    <td>{{ $payrollCycle->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Số phiếu lương:</strong></td>
                                    <td><span class="badge bg-primary">{{ $totalEmployees }}</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày khóa:</strong></td>
                                    <td>
                                        @if($payrollCycle->locked_at)
                                            {{ \Carbon\Carbon::parse($payrollCycle->locked_at)->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-muted">Chưa khóa</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày thanh toán:</strong></td>
                                    <td>
                                        @if($payrollCycle->paid_at)
                                            {{ \Carbon\Carbon::parse($payrollCycle->paid_at)->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-muted">Chưa thanh toán</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    @if($payrollCycle->note)
                    <hr>
                    <div>
                        <strong>Ghi chú:</strong>
                        <p class="mb-0">{{ $payrollCycle->note }}</p>
                    </div>
                    @endif
                </div>
            </div>

                {{-- Card 2: Thống kê --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Thống kê
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-primary">{{ $totalEmployees }}</h4>
                                    <p class="mb-0 text-muted">Nhân viên</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success">{{ number_format($totalNet, 0, ',', '.') }}</h4>
                                <p class="mb-0 text-muted">Tổng thực lĩnh (VND)</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h5 class="text-info">{{ number_format($totalGross, 0, ',', '.') }}</h5>
                                    <p class="mb-0 text-muted">Tổng lương (VND)</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5 class="text-warning">{{ number_format($totalDeductions, 0, ',', '.') }}</h5>
                                <p class="mb-0 text-muted">Tổng khấu trừ (VND)</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Danh sách Phiếu Lương --}}
                @if($payrollCycle->payslips->count() > 0)
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Danh sách Phiếu Lương
                        </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Lương cơ bản</th>
                                    <th>Phụ cấp</th>
                                    <th>Hoa hồng</th>
                                    <th>Tổng lương</th>
                                    <th>Khấu trừ</th>
                                    <th>Thực lĩnh</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payrollCycle->payslips as $payslip)
                                <tr>
                                    <td>
                                        <div>
                                                <strong>{{ $payslip->user->userProfile->full_name ?? $payslip->user->email }}</strong>
                                            <br><small class="text-muted">{{ $payslip->user->email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $salaryContract = \App\Models\SalaryContract::where('user_id', $payslip->user_id)
                                                ->where('status', 'active')
                                                ->first();
                                            $basicSalary = $salaryContract ? $salaryContract->base_salary : 0;
                                        @endphp
                                        <strong>{{ number_format($basicSalary, 0, ',', '.') }} VND</strong>
                                    </td>
                                    <td>
                                        @php
                                            $allowances = 0;
                                            if ($salaryContract && $salaryContract->allowances_json) {
                                                foreach ($salaryContract->allowances_json as $allowance) {
                                                    $allowances += $allowance;
                                                }
                                            }
                                        @endphp
                                        {{ number_format($allowances, 0, ',', '.') }} VND
                                    </td>
                                    <td>
                                        @php
                                            $periodStart = \Carbon\Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->startOfMonth();
                                            $periodEnd = \Carbon\Carbon::createFromFormat('Y-m', $payrollCycle->period_month)->endOfMonth();
                                            $commission = \App\Models\CommissionEvent::where('agent_id', $payslip->user_id)
                                                ->where('status', 'paid')
                                                ->whereBetween('occurred_at', [$periodStart, $periodEnd])
                                                ->sum('commission_total');
                                        @endphp
                                        <span class="text-success">{{ number_format($commission, 0, ',', '.') }} VND</span>
                                    </td>
                                    <td>
                                        <strong class="text-primary">{{ number_format($payslip->gross_amount, 0, ',', '.') }} VND</strong>
                                    </td>
                                    <td>
                                        {{ number_format($payslip->deduction_amount, 0, ',', '.') }} VND
                                    </td>
                                    <td>
                                        <strong class="text-success">{{ number_format($payslip->net_amount, 0, ',', '.') }} VND</strong>
                                    </td>
                                    <td>
                                            @include('staff.components.status-badge', [
                                                'status' => $payslip->status,
                                                'type' => 'payment'
                                            ])
                                    </td>
                                    <td>
                                            <div class="btn-group table-actions" role="group">
                                            <a href="{{ route('staff.payroll-payslips.show', $payslip->id) }}" 
                                                   class="btn btn-outline-primary btn-icon-only" 
                                                   title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($payrollCycle->status === 'open')
                                                <a href="{{ route('staff.payroll-payslips.edit', $payslip->id) }}" 
                                                       class="btn btn-outline-warning btn-icon-only" 
                                                       title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

            {{-- Card "Thao tác" bên phải --}}
        <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Thao tác
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            // Primary actions: Sửa, Xóa, Quay lại (hiển thị vertical)
                            $primaryActions = [];
                            
                            if($payrollCycle->status === 'open') {
                                $primaryActions[] = [
                                    'type' => 'link',
                                    'variant' => 'primary',
                                    'label' => 'Sửa',
                                    'icon' => 'fas fa-edit',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.payroll-cycles.edit', $payrollCycle->id),
                                    'class' => 'w-100'
                                ];
                                
                                $primaryActions[] = [
                                    'type' => 'link',
                                    'variant' => 'info',
                                    'label' => 'Preview phiếu lương',
                                    'icon' => 'fas fa-eye',
                                    'iconPosition' => 'left',
                                    'url' => route('staff.payroll-cycles.preview-payslips', $payrollCycle->id),
                                    'class' => 'w-100'
                                ];
                                
                                $primaryActions[] = [
                                    'type' => 'button',
                                    'variant' => 'success',
                                    'label' => 'Tạo phiếu lương',
                                    'icon' => 'fas fa-calculator',
                                    'iconPosition' => 'left',
                                    'onclick' => "generatePayslips({$payrollCycle->id})",
                                    'class' => 'w-100'
                                ];
                                
                                if($payrollCycle->payslips->count() > 0) {
                                    $primaryActions[] = [
                                        'type' => 'button',
                                        'variant' => 'primary',
                                        'label' => 'Sync hoa hồng',
                                        'icon' => 'fas fa-sync',
                                        'iconPosition' => 'left',
                                        'onclick' => "syncCommissionEvents({$payrollCycle->id})",
                                        'class' => 'w-100'
                                    ];
                                }
                            }
                            
                            if($payrollCycle->status === 'open' && $payrollCycle->payslips->count() == 0) {
                                $primaryActions[] = [
                                    'type' => 'button',
                                    'variant' => 'danger',
                                    'label' => 'Xóa',
                                    'icon' => 'fas fa-trash-alt',
                                    'iconPosition' => 'left',
                                    'onclick' => "deleteCycle({$payrollCycle->id})",
                                    'class' => 'w-100'
                                ];
                            }
                            
                            $primaryActions[] = [
                                'type' => 'link',
                                'variant' => 'secondary',
                                'label' => 'Quay lại',
                                'icon' => 'fas fa-arrow-left',
                                'iconPosition' => 'left',
                                'url' => route('staff.payroll-cycles.index'),
                                'class' => 'w-100'
                            ];
                            
                            // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                            $statusActions = [];
                            
                            if($payrollCycle->status === 'open') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'warning',
                                    'label' => 'Khóa kỳ lương',
                                    'icon' => 'fas fa-lock',
                                    'onclick' => "lockCycle({$payrollCycle->id})"
                                ];
                            }
                            
                            if($payrollCycle->status === 'locked') {
                                $statusActions[] = [
                                    'type' => 'button',
                                    'variant' => 'info',
                                    'label' => 'Đánh dấu đã thanh toán',
                                    'icon' => 'fas fa-check-circle',
                                    'onclick' => "updateStatus('paid')"
                                ];
                            }
                        @endphp
                        
                        <div class="d-grid gap-2">
                            {{-- Primary Actions: Sửa, Preview, Tạo phiếu lương, Sync, Xóa, Quay lại (vertical) --}}
                            @if(count($primaryActions) > 0)
                                @include('staff.components.action-buttons', [
                                    'layout' => 'vertical',
                                    'size' => 'sm',
                                    'actions' => $primaryActions
                                ])
                            @endif
                            
                            {{-- Status Actions: Dropdown cho các nút chuyển trạng thái --}}
                            @if(count($statusActions) > 0)
                                @include('staff.components.action-buttons', [
                                    'layout' => 'dropdown',
                                    'size' => 'sm',
                                    'dropdownLabel' => 'Chuyển trạng thái',
                                    'actions' => $statusActions
                                ])
                        @endif
                            
                            @if($payrollCycle->status !== 'open')
                                <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i>
                            Kỳ lương đã được khóa và không thể chỉnh sửa
                        </div>
                    @endif
                </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa kỳ lương này?</p>
                <p class="text-danger"><strong>Hành động này không thể hoàn tác!</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/notifications.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/notifications.js') }}"></script>
<script>
function deleteCycle(cycleId) {
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = `/staff/payroll-cycles/${cycleId}`;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

function updateStatus(newStatus) {
    const statusLabels = {
        'open': 'Mở',
        'locked': 'Đã khóa',
        'paid': 'Đã thanh toán'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển sang trạng thái "${statusLabels[newStatus]}"?`,
        type: newStatus === 'paid' ? 'info' : 'warning',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            // Show loading
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            // Gửi request
            const formData = new FormData();
            formData.append('status', newStatus);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('{{ route("staff.payroll-cycles.update-status", $payrollCycle->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }
                
                if (data.success) {
                    Notify.success(data.message || 'Đã cập nhật trạng thái thành công!', 'Thành công!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Không thể cập nhật trạng thái: ' + error.message, 'Lỗi hệ thống!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    });
}

function generatePayslips(cycleId) {
    Notify.confirm({
        title: 'Tạo phiếu lương',
        message: 'Bạn có chắc chắn muốn tạo phiếu lương cho kỳ lương này?',
        details: 'Hệ thống sẽ tự động tính toán lương cho tất cả nhân viên trong kỳ này.',
        type: 'info',
        confirmText: 'Tạo phiếu lương',
        onConfirm: () => {
            // Show loading state
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

            // Show loading toast
            const loadingToast = Notify.toast({
                title: 'Đang tạo phiếu lương...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });

            fetch(`/staff/payroll-cycles/${cycleId}/generate-payslips`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }

                if (data.success) {
                    Notify.success(data.message, 'Tạo phiếu lương thành công!');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    Notify.error(data.message, 'Lỗi tạo phiếu lương');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                
                Notify.error('Có lỗi xảy ra khi tạo phiếu lương. Vui lòng thử lại.', 'Lỗi hệ thống');
            })
            .finally(() => {
                // Restore button state
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    });
}

function lockCycle(cycleId) {
    Notify.confirm({
        title: 'Khóa kỳ lương',
        message: 'Bạn có chắc chắn muốn khóa kỳ lương này?',
        details: 'Sau khi khóa sẽ không thể chỉnh sửa hoặc tạo phiếu lương mới.',
        type: 'warning',
        confirmText: 'Khóa kỳ lương',
        onConfirm: () => {
            // Show loading state
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

            // Show loading toast
            const loadingToast = Notify.toast({
                title: 'Đang khóa kỳ lương...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });

            fetch(`/staff/payroll-cycles/${cycleId}/lock`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }

                if (data.success) {
                    Notify.success(data.message, 'Khóa kỳ lương thành công!');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    Notify.error(data.message, 'Lỗi khóa kỳ lương');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                
                Notify.error('Có lỗi xảy ra khi khóa kỳ lương. Vui lòng thử lại.', 'Lỗi hệ thống');
            })
            .finally(() => {
                // Restore button state
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    });
}

function syncCommissionEvents(cycleId) {
    Notify.confirm({
        title: 'Sync hoa hồng',
        message: 'Bạn có chắc chắn muốn sync hoa hồng đã duyệt vào các phiếu lương đã tồn tại?',
        details: 'Hệ thống sẽ kiểm tra và thêm các hoa hồng đã duyệt vào phiếu lương, sau đó cập nhật trạng thái thành đã trả.',
        type: 'info',
        confirmText: 'Sync',
        onConfirm: () => {
            // Show loading state
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

            // Show loading toast
            const loadingToast = Notify.toast({
                title: 'Đang sync hoa hồng...',
                message: 'Vui lòng chờ trong giây lát',
                type: 'info',
                duration: 0
            });

            fetch(`/staff/payroll-cycles/${cycleId}/sync-commission-events`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }

                if (data.success) {
                    Notify.success(data.message, 'Sync hoa hồng thành công!');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    Notify.error(data.message, 'Lỗi sync hoa hồng');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Hide loading toast
                const toastElement = document.getElementById(loadingToast);
                if (toastElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement);
                    if (bsToast) bsToast.hide();
                }
                
                Notify.error('Có lỗi xảy ra khi sync hoa hồng. Vui lòng thử lại.', 'Lỗi hệ thống');
            })
            .finally(() => {
                // Restore button state
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    });
}
</script>
@endpush
