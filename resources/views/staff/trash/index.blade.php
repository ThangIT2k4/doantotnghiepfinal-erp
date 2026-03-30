@extends('layouts.staff_dashboard')

@section('title', 'Quản lý Dữ liệu đã Xóa')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.index-page-header', [
            'title' => 'Quản lý Dữ liệu đã Xóa',
            'subtitle' => 'Xem và khôi phục dữ liệu đã xóa mềm trong tổ chức',
            'icon' => 'fas fa-trash-alt',
            'actions' => []
        ])

        <!-- Filters với HTMX -->
        @include('staff.components.index-filters-htmx', [
            'action' => route('staff.trash.index'),
            'tableContainerId' => 'trash-table-container',
            'fields' => [
                [
                    'name' => 'table',
                    'label' => 'Chọn bảng',
                    'type' => 'select',
                    'empty_option' => 'Chọn bảng',
                    'options' => collect($availableTables)->mapWithKeys(function($config, $key) {
                        return [$key => $config['name']];
                    })->toArray(),
                    'value' => $table,
                    'col' => 'col-md-2',
                    'live_search' => true,
                ],
                [
                    'name' => 'deleted_from',
                    'label' => 'Từ ngày xóa',
                    'type' => 'date',
                    'value' => $deletedFrom,
                    'col' => 'col-md-2',
                    'live_search' => true,
                ],
                [
                    'name' => 'deleted_to',
                    'label' => 'Đến ngày xóa',
                    'type' => 'date',
                    'value' => $deletedTo,
                    'col' => 'col-md-2',
                    'live_search' => true,
                ],
                [
                    'name' => 'search',
                    'label' => 'Tìm kiếm',
                    'type' => 'text',
                    'placeholder' => 'Tên, email, số điện thoại, mã...',
                    'value' => $search,
                    'col' => 'col-md-4',
                    'live_search' => true,
                ],
            ],
            'showReset' => true,
            'resetUrl' => route('staff.trash.index', ['table' => $table])
        ])

        <!-- Table -->
        @include('staff.trash.partials.table', [
            'records' => $records,
            'table' => $table,
            'tableConfig' => $tableConfig,
            'sortBy' => $sortBy ?? 'deleted_at',
            'sortOrder' => $sortOrder ?? 'desc'
        ])
    </div>
</main>
@endsection

@push('scripts')
<script>
// Helper function to reload table via HTMX
function reloadTable() {
    if (typeof htmx !== 'undefined') {
        const currentUrl = window.location.href;
        htmx.ajax('GET', currentUrl, {
            target: '#trash-table-container',
            swap: 'innerHTML',
            headers: {
                'HX-Request': 'true'
            }
        });
    } else {
        window.location.reload();
    }
}

function toggleSelectAll() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.record-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

function getSelectedIds() {
    const checkboxes = document.querySelectorAll('.record-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function restoreRecord(table, id) {
    if (typeof Notify !== 'undefined' && Notify.confirm) {
        Notify.confirm({
            title: 'Xác nhận khôi phục',
            message: 'Bạn có chắc chắn muốn khôi phục bản ghi này?',
            type: 'warning',
            confirmText: 'Khôi phục',
            cancelText: 'Hủy',
            onConfirm: () => {
                performRestore(table, id);
            }
        });
    } else {
        if (!confirm('Bạn có chắc chắn muốn khôi phục bản ghi này?')) {
            return;
        }
        performRestore(table, id);
    }
}

function performRestore(table, id) {
    if (window.Preloader) window.Preloader.show();
    
    fetch(`/staff/trash/${table}/${id}/restore`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }).catch(() => {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof Notify !== 'undefined') {
                Notify.success(data.message || 'Đã khôi phục thành công!', 'Thành công!');
            } else {
                alert(data.message || 'Đã khôi phục thành công!');
            }
            setTimeout(() => reloadTable(), 1000);
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMessage = error.message || 'Có lỗi xảy ra khi khôi phục bản ghi';
        if (typeof Notify !== 'undefined') {
            Notify.error(errorMessage, 'Lỗi!');
        } else {
            alert(errorMessage);
        }
    })
    .finally(() => {
        if (window.Preloader) window.Preloader.hide();
    });
}

function forceDeleteRecord(table, id) {
    if (typeof Notify !== 'undefined' && Notify.confirm) {
        Notify.confirm({
            title: 'Xác nhận xóa vĩnh viễn',
            message: 'Bạn có chắc chắn muốn XÓA VĨNH VIỄN bản ghi này?',
            details: 'Hành động này không thể hoàn tác!',
            type: 'danger',
            confirmText: 'Xóa vĩnh viễn',
            cancelText: 'Hủy',
            onConfirm: () => {
                performForceDelete(table, id);
            }
        });
    } else {
        if (!confirm('Bạn có chắc chắn muốn XÓA VĨNH VIỄN bản ghi này? Hành động này không thể hoàn tác!')) {
            return;
        }
        performForceDelete(table, id);
    }
}

function performForceDelete(table, id) {
    if (window.Preloader) window.Preloader.show();
    
    fetch(`/staff/trash/${table}/${id}/force-delete`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }).catch(() => {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof Notify !== 'undefined') {
                Notify.success(data.message || 'Đã xóa vĩnh viễn thành công!', 'Thành công!');
            } else {
                alert(data.message || 'Đã xóa vĩnh viễn thành công!');
            }
            setTimeout(() => reloadTable(), 1000);
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMessage = error.message || 'Có lỗi xảy ra khi xóa vĩnh viễn bản ghi';
        if (typeof Notify !== 'undefined') {
            Notify.error(errorMessage, 'Lỗi!');
        } else {
            alert(errorMessage);
        }
    })
    .finally(() => {
        if (window.Preloader) window.Preloader.hide();
    });
}

function restoreSelected() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        if (typeof Notify !== 'undefined') {
            Notify.warning('Vui lòng chọn ít nhất một bản ghi', 'Cảnh báo');
        } else {
            alert('Vui lòng chọn ít nhất một bản ghi');
        }
        return;
    }
    
    if (typeof Notify !== 'undefined' && Notify.confirm) {
        Notify.confirm({
            title: 'Xác nhận khôi phục',
            message: `Bạn có chắc chắn muốn khôi phục ${ids.length} bản ghi đã chọn?`,
            type: 'warning',
            confirmText: 'Khôi phục',
            cancelText: 'Hủy',
            onConfirm: () => {
                performRestoreMultiple(ids);
            }
        });
    } else {
        if (!confirm(`Bạn có chắc chắn muốn khôi phục ${ids.length} bản ghi đã chọn?`)) {
            return;
        }
        performRestoreMultiple(ids);
    }
}

function performRestoreMultiple(ids) {
    if (window.Preloader) window.Preloader.show();
    
    fetch(`/staff/trash/{{ $table }}/restore-multiple`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }).catch(() => {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof Notify !== 'undefined') {
                Notify.success(data.message || 'Đã khôi phục thành công!', 'Thành công!');
            } else {
                alert(data.message || 'Đã khôi phục thành công!');
            }
            setTimeout(() => reloadTable(), 1000);
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMessage = error.message || 'Có lỗi xảy ra khi khôi phục bản ghi';
        if (typeof Notify !== 'undefined') {
            Notify.error(errorMessage, 'Lỗi!');
        } else {
            alert(errorMessage);
        }
    })
    .finally(() => {
        if (window.Preloader) window.Preloader.hide();
    });
}

function forceDeleteSelected() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        if (typeof Notify !== 'undefined') {
            Notify.warning('Vui lòng chọn ít nhất một bản ghi', 'Cảnh báo');
        } else {
            alert('Vui lòng chọn ít nhất một bản ghi');
        }
        return;
    }
    
    if (typeof Notify !== 'undefined' && Notify.confirm) {
        Notify.confirm({
            title: 'Xác nhận xóa vĩnh viễn',
            message: `Bạn có chắc chắn muốn XÓA VĨNH VIỄN ${ids.length} bản ghi đã chọn?`,
            details: 'Hành động này không thể hoàn tác!',
            type: 'danger',
            confirmText: 'Xóa vĩnh viễn',
            cancelText: 'Hủy',
            onConfirm: () => {
                performForceDeleteMultiple(ids);
            }
        });
    } else {
        if (!confirm(`Bạn có chắc chắn muốn XÓA VĨNH VIỄN ${ids.length} bản ghi đã chọn? Hành động này không thể hoàn tác!`)) {
            return;
        }
        performForceDeleteMultiple(ids);
    }
}

function performForceDeleteMultiple(ids) {
    if (window.Preloader) window.Preloader.show();
    
    fetch(`/staff/trash/{{ $table }}/force-delete-multiple`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Có lỗi xảy ra');
            }).catch(() => {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof Notify !== 'undefined') {
                Notify.success(data.message || 'Đã xóa vĩnh viễn thành công!', 'Thành công!');
            } else {
                alert(data.message || 'Đã xóa vĩnh viễn thành công!');
            }
            setTimeout(() => reloadTable(), 1000);
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMessage = error.message || 'Có lỗi xảy ra khi xóa vĩnh viễn bản ghi';
        if (typeof Notify !== 'undefined') {
            Notify.error(errorMessage, 'Lỗi!');
        } else {
            alert(errorMessage);
        }
    })
    .finally(() => {
        if (window.Preloader) window.Preloader.hide();
    });
}
</script>
@endpush


