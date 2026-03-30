@extends('layouts.staff_dashboard')

@section('title', 'Quản lý nhân viên')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý nhân viên',
            'subtitle' => 'Quản lý nhân viên và bất động sản được gắn' . (auth()->user()->organizations()->first() ? ' - Tổ chức: ' . auth()->user()->organizations()->first()->name : ''),
            'icon' => 'fas fa-users',
            'actions' => [
                [
                    'variant' => 'primary',
                    'label' => 'Thêm nhân viên mới',
                    'icon' => 'fas fa-plus',
                    'url' => route('staff.staff.create')
                ],
                
            ]
        ])

        <!-- Performance Leaderboard -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-trophy"></i> Bảng Xếp Hạng Hiệu Suất (30 Ngày)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <i class="fas fa-crown fa-2x text-warning mb-2"></i>
                                    <h6 class="mb-1">Top Conversion Rate</h6>
                                    @php
                                        $topConversion = collect($staff->items())
                                            ->where('performance.conversion_rate', '>', 0)
                                            ->sortByDesc(function($s) { return $s->performance['conversion_rate'] ?? 0; })
                                            ->first();
                                    @endphp
                                    @if($topConversion)
                                    <strong class="text-success">{{ $topConversion->full_name }}</strong>
                                    <br><small class="text-muted">{{ $topConversion->performance['conversion_rate'] ?? 0 }}%</small>
                                    @else
                                    <span class="text-muted">Chưa có dữ liệu</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <i class="fas fa-handshake fa-2x text-success mb-2"></i>
                                    <h6 class="mb-1">Top Leases</h6>
                                    @php
                                        $topLeases = collect($staff->items())
                                            ->sortByDesc(function($s) { return $s->performance['leases_count'] ?? 0; })
                                            ->first();
                                    @endphp
                                    @if($topLeases && ($topLeases->performance['leases_count'] ?? 0) > 0)
                                    <strong class="text-success">{{ $topLeases->full_name }}</strong>
                                    <br><small class="text-muted">{{ $topLeases->performance['leases_count'] ?? 0 }} hợp đồng</small>
                                    @else
                                    <span class="text-muted">Chưa có dữ liệu</span>
                                    @endif
                                </div>
                            </div>
                            {{--
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <i class="fas fa-dollar-sign fa-2x text-info mb-2"></i>
                                    <h6 class="mb-1">Top Commission</h6>
                                    @php
                                        $topCommission = collect($staff->items())
                                            ->sortByDesc(function($s) { return $s->performance['commission_earned'] ?? 0; })
                                            ->first();
                                    @endphp
                                    @if($topCommission && ($topCommission->performance['commission_earned'] ?? 0) > 0)
                                    <strong class="text-info">{{ $topCommission->full_name }}</strong>
                                    <br><small class="text-muted">{{ number_format($topCommission->performance['commission_earned'] ?? 0, 0, ',', '.') }} VNĐ</small>
                                    @else
                                    <span class="text-muted">Chưa có dữ liệu</span>
                                    @endif
                                </div>
                            </div>
                            --}}
                            <div class="col-md-3 mb-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                    <h6 class="mb-1">Most Active Leads</h6>
                                    @php
                                        $topLeads = collect($staff->items())
                                            ->sortByDesc(function($s) { return $s->performance['leads_count'] ?? 0; })
                                            ->first();
                                    @endphp
                                    @if($topLeads && ($topLeads->performance['leads_count'] ?? 0) > 0)
                                    <strong class="text-primary">{{ $topLeads->full_name }}</strong>
                                    <br><small class="text-muted">{{ $topLeads->performance['leads_count'] ?? 0 }} leads</small>
                                    @else
                                    <span class="text-muted">Chưa có dữ liệu</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.staff.index'),
            'tableContainerId' => 'staff-table-container',
            'fields' => [
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Tên, email, số điện thoại...',
                    'value' => request('search'),
                    'live_search' => true,
                    'col' => 'col-md-3',
                ],
                [
                    'name' => 'role_id',
                    'label' => 'Vai trò',
                    'type' => 'select',
                    'empty_option' => 'Tất cả vai trò',
                    'options' => $roles->mapWithKeys(function($role) {
                        return [$role->id => $role->name];
                    })->toArray(),
                    'value' => request('role_id'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                    'class' => 'select2',
                ],
                [
                    'name' => 'status',
                    'label' => 'Trạng thái',
                    'type' => 'select',
                    'empty_option' => 'Tất cả',
                    'options' => [
                        '1' => 'Hoạt động',
                        '0' => 'Tạm ngưng',
                    ],
                    'value' => request('status'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'date_from',
                    'label' => 'Từ ngày',
                    'type' => 'date',
                    'value' => request('date_from'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                ],
                [
                    'name' => 'date_to',
                    'label' => 'Đến ngày',
                    'type' => 'date',
                    'value' => request('date_to'),
                    'live_search' => true,
                    'col' => 'col-md-2',
                ],
            ],
            'resetUrl' => route('staff.staff.index')
        ])

        <!-- Staff Table Container (sẽ được cập nhật bằng HTMX) -->
        @include('staff.party.staff.partials.table', [
            'staff' => $staff,
            'sortBy' => $sortBy ?? request('sort_by', 'created_at'),
            'sortOrder' => $sortOrder ?? request('sort_order', 'desc')
        ])
    </div>
</main>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
/* Removed .avatar-circle - avatar display removed */

.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px;
    padding-left: 12px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}


/* Loading states */
.btn.loading {
    position: relative;
    color: transparent !important;
}

.btn.loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Notification toast positioning */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2').select2({
        placeholder: function() {
            return $(this).data('placeholder') || 'Chọn...';
        },
        allowClear: true,
        width: '100%'
    });

    // Show success message if redirected from create/edit
    @if(session('success'))
        Notify.success('{{ session('success') }}', 'Thành công!');
    @endif

    @if(session('error'))
        Notify.error('{{ session('error') }}', 'Lỗi!');
    @endif

    @if(session('warning'))
        Notify.warning('{{ session('warning') }}', 'Cảnh báo!');
    @endif

    @if(session('info'))
        Notify.info('{{ session('info') }}', 'Thông tin!');
    @endif

});

// Delete staff function with enhanced notifications
function deleteStaff(id, name) {
    Notify.confirmDelete(`Bạn có chắc chắn muốn xóa nhân viên "${name}"?`, () => {
        // Show loading notification
        const loadingToast = Notify.toast({
            title: 'Đang xóa...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });

        fetch(`/staff/staff/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            // Hide loading notification
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success('Xóa nhân viên thành công!', 'Thành công!');
                // Reload table via HTMX
                setTimeout(() => {
                    const form = document.getElementById('index-filters-form');
                    if (form && typeof htmx !== 'undefined') {
                        htmx.trigger(form, 'submit');
                    } else {
                        location.reload();
                    }
                }, 1500);
            } else {
                Notify.error(data.message || 'Không thể xóa nhân viên. Vui lòng thử lại.', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            Notify.error('Đã xảy ra lỗi khi xóa nhân viên. Vui lòng kiểm tra kết nối và thử lại.', 'Lỗi hệ thống!');
        });
    });
}

// Toggle staff status function
function toggleStaffStatus(id, name, currentStatus) {
    const action = currentStatus ? 'tạm ngưng' : 'kích hoạt';
    const newStatus = currentStatus ? 0 : 1;
    
    Notify.confirm(`Bạn có chắc chắn muốn ${action} nhân viên "${name}"?`, () => {
        // Show loading notification
        const loadingToast = Notify.toast({
            title: 'Đang cập nhật...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });

        fetch(`/staff/staff/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                status: newStatus
            })
        })
        .then(response => {
            // Hide loading notification
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const actionText = newStatus ? 'kích hoạt' : 'tạm ngưng';
                Notify.success(`Đã ${actionText} nhân viên thành công!`, 'Thành công!');
                // Reload table via HTMX
                setTimeout(() => {
                    const form = document.getElementById('index-filters-form');
                    if (form && typeof htmx !== 'undefined') {
                        htmx.trigger(form, 'submit');
                    } else {
                        location.reload();
                    }
                }, 1500);
            } else {
                Notify.error(data.message || 'Không thể cập nhật trạng thái nhân viên.', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Toggle status error:', error);
            Notify.error('Đã xảy ra lỗi khi cập nhật trạng thái. Vui lòng thử lại.', 'Lỗi hệ thống!');
        });
    });
}

// View staff details function - removed, using direct links now

// Search function with loading state
function performSearch() {
    const searchForm = document.querySelector('form[method="GET"]');
    if (searchForm) {
        // Show loading notification
        const loadingToast = Notify.toast({
            title: 'Đang tìm kiếm...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 2000
        });

        // Submit form
        searchForm.submit();
    }
}

// Clear filters function - handled by index-filters component

// Export staff data function
function exportStaffData() {
    Notify.confirm('Bạn có muốn xuất danh sách nhân viên ra file Excel?', () => {
        // Show loading notification
        const loadingToast = Notify.toast({
            title: 'Đang xuất dữ liệu...',
            message: 'Vui lòng chờ trong giây lát',
            type: 'info',
            duration: 0
        });

        // Get current search parameters
        const urlParams = new URLSearchParams(window.location.search);
        const exportUrl = `{{ route('staff.staff.index') }}/export?${urlParams.toString()}`;

        // Create temporary link for download
        const link = document.createElement('a');
        link.href = exportUrl;
        link.download = 'danh-sach-nhan-vien.xlsx';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Hide loading notification after a delay
        setTimeout(() => {
            const toastElement = document.getElementById(loadingToast);
            if (toastElement) {
                const bsToast = bootstrap.Toast.getInstance(toastElement);
                if (bsToast) bsToast.hide();
            }
            Notify.success('Xuất dữ liệu thành công!', 'Thành công!');
        }, 2000);
    });
}

// Form validation with notifications
function validateSearchForm() {
    const dateFrom = document.querySelector('input[name="date_from"]').value;
    const dateTo = document.querySelector('input[name="date_to"]').value;
    
    if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
        Notify.warning('Ngày bắt đầu không thể lớn hơn ngày kết thúc.', 'Cảnh báo!');
        return false;
    }
    
    return true;
}

// Add form validation to search form
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.querySelector('form[method="GET"]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            if (!validateSearchForm()) {
                e.preventDefault();
            }
        });
    }
});
</script>
@endpush
@endsection

