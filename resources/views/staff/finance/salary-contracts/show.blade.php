@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết hợp đồng lương')

@section('content')
<main class="main-content">
<div class="container-fluid">
        {{-- Page Header --}}
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết hợp đồng lương',
            'subtitle' => 'Thông tin chi tiết hợp đồng lương #' . $salaryContract->id,
            'icon' => 'fas fa-file-contract',
            'breadcrumbs' => [
                ['label' => 'Hợp đồng lương', 'url' => route('staff.salary-contracts.index')],
                ['label' => 'Hợp đồng #' . $salaryContract->id . ' - ' . ($salaryContract->user->userProfile->full_name ?? $salaryContract->user->email), 'active' => true]
            ]
        ])

    <div class="row">
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông tin cơ bản</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td>{{ $salaryContract->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Nhân viên:</strong></td>
                                    <td>
                                        <div>
                                            <strong>{{ $salaryContract->user->userProfile->full_name ?? $salaryContract->user->email }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $salaryContract->user->email }}</small>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Lương cơ bản:</strong></td>
                                    <td>
                                        <strong class="text-primary">
                                            {{ number_format($salaryContract->base_salary) }} {{ $salaryContract->currency }}
                                        </strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Chu kỳ trả:</strong></td>
                                    <td>
                                        @switch($salaryContract->pay_cycle)
                                            @case('monthly')
                                                <span class="badge bg-info">Hàng tháng</span>
                                                @break
                                            @case('weekly')
                                                <span class="badge bg-warning">Hàng tuần</span>
                                                @break
                                            @case('daily')
                                                <span class="badge bg-success">Hàng ngày</span>
                                                @break
                                        @endswitch
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày trả lương:</strong></td>
                                    <td>Ngày {{ $salaryContract->pay_day }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Trạng thái:</strong></td>
                                    <td>
                                        @include('staff.components.status-badge', [
                                            'status' => $salaryContract->status,
                                            'type' => 'salary-contract'
                                        ])
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày hiệu lực:</strong></td>
                                    <td>{{ $salaryContract->effective_from->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày hết hạn:</strong></td>
                                    <td>{{ $salaryContract->effective_to ? $salaryContract->effective_to->format('d/m/Y') : 'Không giới hạn' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Ngày tạo:</strong></td>
                                    <td>{{ $salaryContract->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Cập nhật cuối:</strong></td>
                                    <td>{{ $salaryContract->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Allowances -->
            @if($salaryContract->allowances_json && count($salaryContract->allowances_json) > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Phụ cấp</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tên phụ cấp</th>
                                        <th>Số tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($salaryContract->allowances_json as $name => $amount)
                                        <tr>
                                            <td>{{ $name }}</td>
                                            <td>
                                                <strong class="text-success">
                                                    {{ number_format($amount) }} {{ $salaryContract->currency }}
                                                </strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Tổng phụ cấp:</th>
                                        <th>
                                            <strong class="text-primary">
                                                {{ number_format(array_sum($salaryContract->allowances_json)) }} {{ $salaryContract->currency }}
                                            </strong>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- KPI Targets -->
            @if($salaryContract->kpi_target_json && count($salaryContract->kpi_target_json) > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Mục tiêu KPI</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tên KPI</th>
                                        <th>Mục tiêu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($salaryContract->kpi_target_json as $name => $target)
                                        <tr>
                                            <td>{{ $name }}</td>
                                            <td>
                                                <strong class="text-info">
                                                    {{ number_format($target) }}
                                                    @if(strpos($name, 'tỷ lệ') !== false || strpos($name, 'phần trăm') !== false)
                                                        %
                                                    @else
                                                        {{ $salaryContract->currency }}
                                                    @endif
                                                </strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Salary Summary -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tóm tắt lương</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Lương cơ bản:</span>
                                <strong>{{ number_format($salaryContract->base_salary) }} {{ $salaryContract->currency }}</strong>
                            </div>
                            @if($salaryContract->allowances_json && count($salaryContract->allowances_json) > 0)
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Phụ cấp:</span>
                                    <strong class="text-success">{{ number_format(array_sum($salaryContract->allowances_json)) }} {{ $salaryContract->currency }}</strong>
                                </div>
                            @endif
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span><strong>Tổng lương:</strong></span>
                                <strong class="text-primary">
                                    {{ number_format($salaryContract->base_salary + ($salaryContract->allowances_json ? array_sum($salaryContract->allowances_json) : 0)) }} {{ $salaryContract->currency }}
                                </strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Thông tin bổ sung</h6>
                                <ul class="mb-0">
                                    <li>Chu kỳ trả: {{ $salaryContract->pay_cycle === 'monthly' ? 'Hàng tháng' : ($salaryContract->pay_cycle === 'weekly' ? 'Hàng tuần' : 'Hàng ngày') }}</li>
                                    <li>Ngày trả: {{ $salaryContract->pay_day }}</li>
                                    <li>Trạng thái: 
                                        @switch($salaryContract->status)
                                            @case('active')
                                                <span class="badge bg-success">Đang hoạt động</span>
                                                @break
                                            @case('inactive')
                                                <span class="badge bg-warning">Tạm dừng</span>
                                                @break
                                            @case('terminated')
                                                <span class="badge bg-danger">Đã chấm dứt</span>
                                                @break
                                        @endswitch
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>Thao tác
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        // Primary actions: Sửa, Xóa, Quay lại (hiển thị vertical)
                        $primaryActions = [];
                        
                        // Only show edit if not terminated
                        if($salaryContract->status !== 'terminated') {
                            $primaryActions[] = [
                                'type' => 'link',
                                'variant' => 'primary',
                                'label' => 'Sửa',
                                'icon' => 'fas fa-edit',
                                'iconPosition' => 'left',
                                'url' => route('staff.salary-contracts.edit', $salaryContract->id),
                                'class' => 'w-100'
                            ];
                        }
                        
                        // Show delete if inactive or terminated (not active)
                        if($salaryContract->status !== 'active') {
                            $primaryActions[] = [
                                'type' => 'button',
                                'variant' => 'danger',
                                'label' => 'Xóa',
                                'icon' => 'fas fa-trash-alt',
                                'iconPosition' => 'left',
                                'onclick' => "deleteContract({$salaryContract->id})",
                                'class' => 'w-100'
                            ];
                        }
                        
                        $primaryActions[] = [
                            'type' => 'link',
                            'variant' => 'secondary',
                            'label' => 'Quay lại',
                            'icon' => 'fas fa-arrow-left',
                            'iconPosition' => 'left',
                            'url' => route('staff.salary-contracts.index'),
                            'class' => 'w-100'
                        ];
                        
                        // Status actions: Các nút chuyển trạng thái (hiển thị trong dropdown)
                        $statusActions = [];
                        
                        if($salaryContract->status !== 'active') {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'success',
                                'label' => 'Kích hoạt',
                                'icon' => 'fas fa-check-circle',
                                'iconPosition' => 'left',
                                'onclick' => "updateStatus('active')",
                                'class' => 'w-100'
                            ];
                        }
                        
                        if($salaryContract->status !== 'inactive') {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'warning',
                                'label' => 'Tạm dừng',
                                'icon' => 'fas fa-pause-circle',
                                'iconPosition' => 'left',
                                'onclick' => "updateStatus('inactive')",
                                'class' => 'w-100'
                            ];
                        }
                        
                        if($salaryContract->status !== 'terminated') {
                            $statusActions[] = [
                                'type' => 'button',
                                'variant' => 'danger',
                                'label' => 'Chấm dứt',
                                'icon' => 'fas fa-stop-circle',
                                'iconPosition' => 'left',
                                'onclick' => "updateStatus('terminated')",
                                'class' => 'w-100'
                            ];
                        }
                    @endphp
                    
                    <div class="d-grid gap-2">
                        {{-- Primary Actions: Sửa, Xóa, Quay lại (vertical) --}}
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
                    </div>
                </div>
            </div>

            <!-- Contract Status -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Trạng thái hợp đồng</h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        @switch($salaryContract->status)
                            @case('active')
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5 class="text-success">Đang hoạt động</h5>
                                <p class="text-muted">Hợp đồng đang có hiệu lực và được sử dụng để tính lương.</p>
                                @break
                            @case('inactive')
                                <i class="fas fa-pause-circle fa-3x text-warning mb-3"></i>
                                <h5 class="text-warning">Tạm dừng</h5>
                                <p class="text-muted">Hợp đồng đã bị tạm dừng, có thể kích hoạt lại.</p>
                                @break
                            @case('terminated')
                                <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                                <h5 class="text-danger">Đã chấm dứt</h5>
                                <p class="text-muted">Hợp đồng đã được chấm dứt và không thể kích hoạt lại.</p>
                                @break
                        @endswitch
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
                <p>Bạn có chắc chắn muốn xóa hợp đồng lương này?</p>
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
function deleteContract(contractId) {
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = `/staff/salary-contracts/${contractId}`;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Update status function
window.updateStatus = function(newStatus) {
    const statusLabels = {
        'active': 'Đang hoạt động',
        'inactive': 'Tạm dừng',
        'terminated': 'Đã chấm dứt'
    };
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn chuyển sang trạng thái "${statusLabels[newStatus]}"?`,
        type: newStatus === 'terminated' ? 'danger' : 'warning',
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
            
            fetch('{{ route("staff.salary-contracts.update-status", $salaryContract->id) }}', {
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
};

function deleteContract(contractId) {
    Notify.confirm({
        title: 'Xác nhận xóa',
        message: 'Bạn có chắc chắn muốn xóa hợp đồng lương này?',
        details: 'Hành động này không thể hoàn tác!',
        type: 'danger',
        confirmText: 'Xóa',
        cancelText: 'Hủy',
        onConfirm: function() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/staff/salary-contracts/${contractId}`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
            form.appendChild(csrfToken);
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            form.appendChild(methodField);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush
