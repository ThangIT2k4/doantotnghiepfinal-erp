@extends('layouts.superadmin')

@section('title', 'Quản lý Dữ liệu đã Xóa')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Dữ liệu đã Xóa</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-trash-alt me-2"></i>
                Quản lý Dữ liệu đã Xóa
            </h1>
            <p class="text-muted mb-0">Xem và khôi phục dữ liệu đã xóa mềm trong hệ thống</p>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Bộ lọc và tìm kiếm</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('superadmin.trash.index') }}" class="filters-form">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="table">Chọn bảng</label>
                            <select name="table" id="table" class="form-control">
                                @foreach($availableTables as $tableKey => $config)
                                    <option value="{{ $tableKey }}" {{ $table == $tableKey ? 'selected' : '' }}>
                                        {{ $config['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if($tableConfig['organization_field'])
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="organization_id">Tổ chức</label>
                            <select name="organization_id" id="organization_id" class="form-control select2-organization">
                                <option value="">Tất cả tổ chức</option>
                                @foreach($organizations as $org)
                                    <option value="{{ $org->id }}" {{ $organizationId == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="deleted_from">Từ ngày xóa</label>
                            <input type="date" 
                                   name="deleted_from" 
                                   id="deleted_from" 
                                   class="form-control" 
                                   value="{{ $deletedFrom }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="deleted_to">Đến ngày xóa</label>
                            <input type="date" 
                                   name="deleted_to" 
                                   id="deleted_to" 
                                   class="form-control" 
                                   value="{{ $deletedTo }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Tìm kiếm</label>
                            <input type="text" 
                                   name="search" 
                                   id="search" 
                                   class="form-control" 
                                   value="{{ $search }}" 
                                   placeholder="Tên, email, số điện thoại, mã...">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100" title="Tìm kiếm">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <a href="{{ route('superadmin.trash.index', ['table' => $table]) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-redo"></i> Xóa bộ lọc
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Records Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Danh sách dữ liệu đã xóa - {{ $tableConfig['name'] }}
                <span class="badge bg-secondary ms-2">{{ $records->total() }}</span>
            </h6>
            @if($records->count() > 0)
            <div>
                <button type="button" class="btn btn-sm btn-success" onclick="restoreSelected()">
                    <i class="fas fa-undo me-1"></i>Khôi phục đã chọn
                </button>
                <button type="button" class="btn btn-sm btn-danger" onclick="forceDeleteSelected()">
                    <i class="fas fa-trash me-1"></i>Xóa vĩnh viễn đã chọn
                </button>
            </div>
            @endif
        </div>
        <div class="card-body">
            @if($records->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="select-all" onchange="toggleSelectAll()">
                                </th>
                                <th>ID</th>
                                <th>Tên/Mô tả</th>
                                @if($tableConfig['organization_field'])
                                <th>Tổ chức</th>
                                @endif
                                <th>Ngày xóa</th>
                                <th>Người xóa</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $record)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="record-checkbox" value="{{ $record->id }}">
                                    </td>
                                    <td>#{{ $record->id }}</td>
                                    <td>
                                        @if(isset($record->name))
                                            {{ $record->name }}
                                        @elseif(isset($record->lead_name))
                                            {{ $record->lead_name }} ({{ $record->lead_phone }})
                                        @elseif(isset($record->code))
                                            {{ $record->code }}
                                        @elseif(isset($record->email))
                                            {{ $record->email }}
                                        @else
                                            Bản ghi #{{ $record->id }}
                                        @endif
                                    </td>
                                    @if($tableConfig['organization_field'])
                                    <td>
                                        @if($record->organization)
                                            {{ $record->organization->name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    @endif
                                    <td>
                                        <small>{{ $record->deleted_at ? $record->deleted_at->format('d/m/Y H:i') : '-' }}</small>
                                    </td>
                                    <td>
                                        @if(isset($record->deleted_by) && $record->deletedBy)
                                            <small>{{ $record->deletedBy->full_name ?? $record->deletedBy->email }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-success" 
                                                    onclick="restoreRecord('{{ $table }}', {{ $record->id }})"
                                                    title="Khôi phục">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    onclick="forceDeleteRecord('{{ $table }}', {{ $record->id }})"
                                                    title="Xóa vĩnh viễn">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $records->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Không có dữ liệu đã xóa nào</h5>
                    <p class="text-muted">Thùng rác trống</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px;
    padding-left: 12px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
    right: 10px;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// Initialize Select2 for organization dropdown
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        $('.select2-organization').select2({
            placeholder: 'Tìm kiếm tổ chức...',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() {
                    return "Không tìm thấy kết quả";
                },
                searching: function() {
                    return "Đang tìm kiếm...";
                }
            }
        });
    }
});

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
    if (!confirm('Bạn có chắc chắn muốn khôi phục bản ghi này?')) {
        return;
    }
    
    if (window.Preloader) window.Preloader.show();
    
    fetch(`/superadmin/trash/${table}/${id}/restore`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof Notify !== 'undefined') {
                Notify.success(data.message, 'Thành công!');
            } else {
                alert(data.message);
            }
            setTimeout(() => location.reload(), 1000);
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message, 'Lỗi!');
            } else {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof Notify !== 'undefined') {
            Notify.error('Có lỗi xảy ra: ' + error.message, 'Lỗi!');
        } else {
            alert('Có lỗi xảy ra: ' + error.message);
        }
    })
    .finally(() => {
        if (window.Preloader) window.Preloader.hide();
    });
}

function forceDeleteRecord(table, id) {
    if (!confirm('Bạn có chắc chắn muốn XÓA VĨNH VIỄN bản ghi này? Hành động này không thể hoàn tác!')) {
        return;
    }
    
    if (window.Preloader) window.Preloader.show();
    
    fetch(`/superadmin/trash/${table}/${id}/force-delete`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof Notify !== 'undefined') {
                Notify.success(data.message, 'Thành công!');
            } else {
                alert(data.message);
            }
            setTimeout(() => location.reload(), 1000);
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message, 'Lỗi!');
            } else {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof Notify !== 'undefined') {
            Notify.error('Có lỗi xảy ra: ' + error.message, 'Lỗi!');
        } else {
            alert('Có lỗi xảy ra: ' + error.message);
        }
    })
    .finally(() => {
        if (window.Preloader) window.Preloader.hide();
    });
}

function restoreSelected() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        alert('Vui lòng chọn ít nhất một bản ghi');
        return;
    }
    
    if (!confirm(`Bạn có chắc chắn muốn khôi phục ${ids.length} bản ghi đã chọn?`)) {
        return;
    }
    
    if (window.Preloader) window.Preloader.show();
    
    fetch(`/superadmin/trash/{{ $table }}/restore-multiple`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof Notify !== 'undefined') {
                Notify.success(data.message, 'Thành công!');
            } else {
                alert(data.message);
            }
            setTimeout(() => location.reload(), 1000);
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message, 'Lỗi!');
            } else {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof Notify !== 'undefined') {
            Notify.error('Có lỗi xảy ra: ' + error.message, 'Lỗi!');
        } else {
            alert('Có lỗi xảy ra: ' + error.message);
        }
    })
    .finally(() => {
        if (window.Preloader) window.Preloader.hide();
    });
}

function forceDeleteSelected() {
    const ids = getSelectedIds();
    if (ids.length === 0) {
        alert('Vui lòng chọn ít nhất một bản ghi');
        return;
    }
    
    if (!confirm(`Bạn có chắc chắn muốn XÓA VĨNH VIỄN ${ids.length} bản ghi đã chọn? Hành động này không thể hoàn tác!`)) {
        return;
    }
    
    if (window.Preloader) window.Preloader.show();
    
    fetch(`/superadmin/trash/{{ $table }}/force-delete-multiple`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof Notify !== 'undefined') {
                Notify.success(data.message, 'Thành công!');
            } else {
                alert(data.message);
            }
            setTimeout(() => location.reload(), 1000);
        } else {
            if (typeof Notify !== 'undefined') {
                Notify.error(data.message, 'Lỗi!');
            } else {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof Notify !== 'undefined') {
            Notify.error('Có lỗi xảy ra: ' + error.message, 'Lỗi!');
        } else {
            alert('Có lỗi xảy ra: ' + error.message);
        }
    })
    .finally(() => {
        if (window.Preloader) window.Preloader.hide();
    });
}
</script>
@endpush

