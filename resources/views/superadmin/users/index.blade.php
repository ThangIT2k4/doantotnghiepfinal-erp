@extends('layouts.superadmin')

@section('title', 'Quản lý Người dùng')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/notification-system.css') }}">
<style>
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 14px;
    }
    
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .role-badge {
        font-size: 0.7rem;
        margin: 1px;
    }
    
    .search-filters {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .table-responsive {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    
    .table td {
        vertical-align: middle;
        border-color: #e9ecef;
    }
    
    .action-buttons .btn {
        margin: 0 2px;
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-users me-2"></i>Quản lý Người dùng
            </h1>
            <p class="text-muted mb-0">Quản lý tất cả người dùng trong hệ thống</p>
        </div>
        <a href="{{ route('superadmin.users.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Thêm Người dùng
        </a>
    </div>

    <!-- Search and Filters -->
    <div class="search-filters">
        <form method="GET" action="{{ route('superadmin.users.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Tìm kiếm</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="{{ request('search') }}" placeholder="Tên, email, số điện thoại...">
            </div>
            <div class="col-md-3">
                <label for="organization_id" class="form-label">Tổ chức</label>
                <select class="form-select" id="organization_id" name="organization_id">
                    <option value="">Tất cả tổ chức</option>
                    @forelse($organizations as $org)
                        <option value="{{ $org->id }}" {{ request('organization_id') == $org->id ? 'selected' : '' }}>
                            {{ $org->name }}
                        </option>
                    @empty
                        <option value="" disabled>Không có tổ chức nào</option>
                    @endforelse
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Trạng thái</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Tất cả</option>
                    <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
                    <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Tạm dừng</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="fas fa-search me-1"></i>Tìm kiếm
                </button>
                <a href="{{ route('superadmin.users.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Xóa bộ lọc
                </a>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Người dùng</th>
                    <th>Email</th>
                    <th>Tổ chức & Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Đăng nhập cuối</th>
                    <th>Ngày tạo</th>
                    <th width="120">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-3">
                                {{ strtoupper(substr($user->full_name, 0, 2)) }}
                            </div>
                            <div>
                                <div class="fw-bold">{{ $user->full_name }}</div>
                                @if($user->phone)
                                    <small class="text-muted">{{ $user->phone }}</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if($user->organizations->count() > 0)
                            @foreach($user->organizations as $org)
                                <div class="mb-1">
                                    <small class="text-muted">{{ $org->name }}:</small>
                                    @if($org->pivot->role_id)
                                        @php
                                            $role = \App\Models\Role::find($org->pivot->role_id);
                                        @endphp
                                        @if($role)
                                            <span class="badge bg-info role-badge">{{ $role->name }}</span>
                                        @else
                                            <span class="badge bg-secondary role-badge">Không có vai trò</span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary role-badge">Không có vai trò</span>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <span class="text-muted">Chưa có tổ chức</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge status-badge {{ $user->status ? 'bg-success' : 'bg-danger' }}">
                            {{ $user->status ? 'Hoạt động' : 'Tạm dừng' }}
                        </span>
                    </td>
                    <td>
                        @if($user->last_login_at)
                            <small>{{ $user->last_login_at->format('d/m/Y H:i') }}</small>
                        @else
                            <small class="text-muted">Chưa đăng nhập</small>
                        @endif
                    </td>
                    <td>
                        <small>{{ $user->created_at->format('d/m/Y') }}</small>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="{{ route('superadmin.users.show', $user) }}" 
                               class="btn btn-sm btn-outline-info" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('superadmin.users.edit', $user) }}" 
                               class="btn btn-sm btn-outline-warning" title="Chỉnh sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-outline-{{ $user->status ? 'secondary' : 'success' }}"
                                    onclick="toggleUserStatus({{ $user->id }}, {{ $user->status ? 'false' : 'true' }})"
                                    title="{{ $user->status ? 'Tạm dừng' : 'Kích hoạt' }}">
                                <i class="fas fa-{{ $user->status ? 'pause' : 'play' }}"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="deleteUser({{ $user->id }}, '{{ $user->full_name }}')"
                                    title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <p>Không tìm thấy người dùng nào</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $users->appends(request()->query())->links('vendor.pagination.custom') }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/notification-system.js') }}"></script>
<script>
function toggleUserStatus(userId, newStatus) {
    const action = newStatus ? 'kích hoạt' : 'tạm dừng';

    if (typeof Notify !== 'undefined') {
        Notify.confirm(
            `Bạn có chắc chắn muốn ${action} người dùng này?`,
            function() {
                Notify.toast('Đang cập nhật trạng thái người dùng...', 'info');

                fetch(`/superadmin/users/${userId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notify.toast(`Người dùng đã được ${action} thành công!`, 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Notify.toast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('User Toggle Error:', error);
                    Notify.toast('Có lỗi xảy ra khi cập nhật trạng thái người dùng', 'error');
                });
            }
        );
    } else {
        // Fallback if Notify is not available
        if (confirm(`Bạn có chắc chắn muốn ${action} người dùng này?`)) {
            fetch(`/superadmin/users/${userId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Người dùng đã được ${action} thành công!`);
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('User Toggle Error:', error);
                alert('Có lỗi xảy ra khi cập nhật trạng thái người dùng');
            });
        }
    }
}

function deleteUser(userId, userName) {
    if (typeof Notify !== 'undefined') {
        Notify.confirmDelete(
            `Bạn có chắc chắn muốn xóa người dùng "${userName}"?`,
            function() {
                Notify.toast('Đang xóa người dùng...', 'info');

                fetch(`/superadmin/users/${userId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Notify.toast('Người dùng đã được xóa thành công!', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        Notify.toast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('User Delete Error:', error);
                    Notify.toast('Có lỗi xảy ra khi xóa người dùng', 'error');
                });
            }
        );
    } else {
        // Fallback if Notify is not available
        if (confirm(`Bạn có chắc chắn muốn xóa người dùng "${userName}"?`)) {
            fetch(`/superadmin/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Người dùng đã được xóa thành công!');
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('User Delete Error:', error);
                alert('Có lỗi xảy ra khi xóa người dùng');
            });
        }
    }
}
</script>
@endpush
