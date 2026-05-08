@extends('layouts.superadmin')

@section('title', 'Quản lý Tổ chức')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Organizations</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-building me-2"></i>
                Quản lý Tổ chức
            </h1>
            <p class="text-muted mb-0">Quản lý tất cả các tổ chức trong hệ thống</p>
        </div>
        <div>
            <a href="{{ route('superadmin.organizations.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>
                Thêm Tổ chức
            </a>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Bộ lọc và tìm kiếm</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('superadmin.organizations.index') }}" class="filters-form">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search">Tìm kiếm</label>
                            <input type="text" 
                                   name="search" 
                                   id="search" 
                                   class="form-control" 
                                   value="{{ request('search') }}" 
                                   placeholder="Tên, email, số điện thoại...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Trạng thái</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
                                <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Tạm dừng</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="sort_by">Sắp xếp theo</label>
                            <select name="sort_by" id="sort_by" class="form-control">
                                <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Ngày tạo</option>
                                <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Tên</option>
                                <option value="email" {{ request('sort_by') == 'email' ? 'selected' : '' }}>Email</option>
                                <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>Trạng thái</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="btn-group w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="{{ route('superadmin.organizations.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Organizations Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách tổ chức</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tổ chức</th>
                            <th>Liên hệ</th>
                            <th>Người dùng</th>
                            <th>Tài sản</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($organizations as $organization)
                        <tr>
                            <td>
                                <div class="organization-info">
                                    <div class="org-avatar">
                                        {{ strtoupper(substr($organization->name, 0, 1)) }}
                                    </div>
                                    <div class="org-details">
                                        <h6 class="org-name">{{ $organization->name }}</h6>
                                        @if($organization->description)
                                        <small class="text-muted">{{ Str::limit($organization->description, 50) }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <span>{{ $organization->email }}</span>
                                    </div>
                                    @if($organization->phone)
                                    <div class="contact-item">
                                        <i class="fas fa-phone"></i>
                                        <span>{{ $organization->phone }}</span>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $organization->users_count }}</span>
                            </td>
                            <td>
                                <span class="badge bg-success">{{ $organization->properties_count }}</span>
                            </td>
                            <td>
                                <span class="status-badge {{ $organization->status ? 'active' : 'inactive' }}">
                                    {{ $organization->status ? 'Hoạt động' : 'Tạm dừng' }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $organization->created_at->format('d/m/Y') }}</small>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('superadmin.organizations.show', $organization) }}" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('superadmin.organizations.edit', $organization) }}" 
                                       class="btn btn-sm btn-outline-warning" 
                                       title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-{{ $organization->status ? 'secondary' : 'success' }}"
                                            onclick="toggleOrganizationStatus({{ $organization->id }}, {{ $organization->status ? 'false' : 'true' }})"
                                            title="{{ $organization->status ? 'Tạm dừng' : 'Kích hoạt' }}">
                                        <i class="fas fa-{{ $organization->status ? 'pause' : 'play' }}"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="deleteOrganization({{ $organization->id }}, '{{ $organization->name }}')"
                                            title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                    <h5>Không có tổ chức nào</h5>
                                    <p class="text-muted">Chưa có tổ chức nào trong hệ thống</p>
                                    <a href="{{ route('superadmin.organizations.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        Thêm tổ chức đầu tiên
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($organizations->hasPages())
            <div class="table-footer">
                {{ $organizations->appends(request()->query())->links('vendor.pagination.custom', [
                    'contentTypeOverride' => 'tổ chức',
                    'contentIconOverride' => 'fas fa-building'
                ]) }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.organization-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.org-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 16px;
}

.org-name {
    margin: 0;
    font-weight: 600;
    color: #2c3e50;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.contact-item i {
    width: 14px;
    color: #6c757d;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-badge.active {
    background-color: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background-color: #f8d7da;
    color: #721c24;
}

.action-buttons {
    display: flex;
    gap: 4px;
}

.action-buttons .btn {
    padding: 4px 8px;
    border-radius: 4px;
}

.empty-state {
    padding: 2rem;
}

.table-footer {
    padding: 1rem;
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
}
</style>
@endpush

@push('scripts')
<script>
// Toggle organization status
function toggleStatus(organizationId, newStatus) {
    const action = newStatus ? 'kích hoạt' : 'tạm dừng';
    
    if (typeof Notify !== 'undefined') {
        Notify.confirm(
            `Bạn có chắc chắn muốn ${action} tổ chức này?`,
            function() {
                // Show loading
                Notify.toast('Đang cập nhật trạng thái...', 'info');
                
                fetch(`/superadmin/organizations/${organizationId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notify.toast(data.message, 'success');
                        // Reload page to show updated status
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Notify.toast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Notify.toast('Có lỗi xảy ra khi cập nhật trạng thái', 'error');
                });
            }
        );
    } else {
        // Fallback if Notify is not available
        if (confirm(`Bạn có chắc chắn muốn ${action} tổ chức này?`)) {
            fetch(`/superadmin/organizations/${organizationId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật trạng thái');
            });
        }
    }
}

// Toggle organization status
function toggleOrganizationStatus(organizationId, newStatus) {
    const action = newStatus ? 'kích hoạt' : 'tạm dừng';
    
    if (typeof Notify !== 'undefined') {
        Notify.confirm(
            `Bạn có chắc chắn muốn ${action} tổ chức này?`,
            function() {
                Notify.toast('Đang cập nhật trạng thái tổ chức...', 'info');
                
                fetch(`/superadmin/organizations/${organizationId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notify.toast(`Tổ chức đã được ${action} thành công!`, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Notify.toast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Organization Toggle Error:', error);
                    Notify.toast('Có lỗi xảy ra khi cập nhật trạng thái tổ chức', 'error');
                });
            }
        );
    } else {
        // Fallback if Notify is not available
        if (confirm(`Bạn có chắc chắn muốn ${action} tổ chức này?`)) {
            fetch(`/superadmin/organizations/${organizationId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Tổ chức đã được ${action} thành công!`);
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Organization Toggle Error:', error);
                alert('Có lỗi xảy ra khi cập nhật trạng thái tổ chức');
            });
        }
    }
}

// Delete organization
function deleteOrganization(organizationId, organizationName) {
    if (typeof Notify !== 'undefined') {
        Notify.confirmDelete(
            `Bạn có chắc chắn muốn xóa tổ chức "${organizationName}"? Hành động này không thể hoàn tác.`,
            function() {
                // Show loading
                Notify.toast('Đang xóa tổ chức...', 'info');
                
                fetch(`/superadmin/organizations/${organizationId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notify.toast(data.message, 'success');
                        // Reload page to show updated list
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Notify.toast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Notify.toast('Có lỗi xảy ra khi xóa tổ chức', 'error');
                });
            }
        );
    } else {
        // Fallback if Notify is not available
        if (confirm(`Bạn có chắc chắn muốn xóa tổ chức "${organizationName}"?`)) {
            fetch(`/superadmin/organizations/${organizationId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xóa tổ chức');
            });
        }
    }
}
</script>
@endpush
