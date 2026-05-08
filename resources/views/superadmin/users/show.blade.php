@extends('layouts.superadmin')

@section('title', 'Chi tiết Người dùng')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/notification-system.css') }}">
<style>
    .user-profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
    }
    
    .user-avatar-large {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 36px;
        border: 4px solid rgba(255,255,255,0.3);
    }
    
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-left: 4px solid #007bff;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
    }
    
    .stat-card.success { border-left-color: #28a745; }
    .stat-card.warning { border-left-color: #ffc107; }
    .stat-card.danger { border-left-color: #dc3545; }
    .stat-card.info { border-left-color: #17a2b8; }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
        margin-bottom: 15px;
    }
    
    .stat-icon.bg-primary { background: linear-gradient(135deg, #007bff, #0056b3); }
    .stat-icon.bg-success { background: linear-gradient(135deg, #28a745, #1e7e34); }
    .stat-icon.bg-warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
    .stat-icon.bg-danger { background: linear-gradient(135deg, #dc3545, #c82333); }
    .stat-icon.bg-info { background: linear-gradient(135deg, #17a2b8, #138496); }
    
    .info-section {
        background: white;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .section-title {
        color: #495057;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #dee2e6;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 500;
        color: #6c757d;
    }
    
    .info-value {
        color: #495057;
    }
    
    .role-badge {
        font-size: 0.8rem;
        margin: 2px;
    }
    
    .organization-item {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 10px;
        border-left: 4px solid #007bff;
    }
    
    .table th {
        background: #f8f9fa;
        border: none;
        font-weight: 600;
        color: #495057;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- User Profile Header -->
    <div class="user-profile-header">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <div class="user-avatar-large">
                    {{ strtoupper(substr($user->full_name, 0, 2)) }}
                </div>
            </div>
            <div class="col-md-6">
                <h2 class="mb-2">{{ $user->full_name }}</h2>
                <p class="mb-1">
                    <i class="fas fa-envelope me-2"></i>{{ $user->email }}
                </p>
                @if($user->phone)
                    <p class="mb-1">
                        <i class="fas fa-phone me-2"></i>{{ $user->phone }}
                    </p>
                @endif
                <p class="mb-0">
                    <span class="badge {{ $user->status ? 'bg-success' : 'bg-danger' }}">
                        {{ $user->status ? 'Hoạt động' : 'Tạm dừng' }}
                    </span>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('superadmin.users.edit', $user) }}" class="btn btn-light">
                        <i class="fas fa-edit me-2"></i>Chỉnh sửa
                    </a>
                    <button type="button" 
                            class="btn btn-outline-light"
                            onclick="toggleUserStatus({{ $user->id }}, {{ $user->status ? 'false' : 'true' }})">
                        <i class="fas fa-{{ $user->status ? 'pause' : 'play' }} me-2"></i>
                        {{ $user->status ? 'Tạm dừng' : 'Kích hoạt' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-icon bg-success">
                    <i class="fas fa-building"></i>
                </div>
                <h3 class="mb-1">{{ $stats['total_organizations'] }}</h3>
                <p class="mb-0 text-muted">Tổ chức tham gia</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-percentage"></i>
                </div>
                <h3 class="mb-1">{{ $stats['total_commissions'] }}</h3>
                <p class="mb-0 text-muted">Hoa hồng</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card info">
                <div class="stat-icon bg-info">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3 class="mb-1">{{ number_format($stats['total_commission_amount']) }}đ</h3>
                <p class="mb-0 text-muted">Tổng hoa hồng</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card danger">
                <div class="stat-icon bg-danger">
                    <i class="fas fa-file-contract"></i>
                </div>
                <h3 class="mb-1">{{ $stats['active_salary_contracts'] }}</h3>
                <p class="mb-0 text-muted">Hợp đồng lương</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Basic Information -->
        <div class="col-md-6">
            <div class="info-section">
                <h5 class="section-title">
                    <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                </h5>
                <div class="info-item">
                    <span class="info-label">Họ và tên:</span>
                    <span class="info-value">{{ $user->full_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $user->email }}</span>
                </div>
                @if($user->phone)
                <div class="info-item">
                    <span class="info-label">Số điện thoại:</span>
                    <span class="info-value">{{ $user->phone }}</span>
                </div>
                @endif
                <div class="info-item">
                    <span class="info-label">Trạng thái:</span>
                    <span class="info-value">
                        <span class="badge {{ $user->status ? 'bg-success' : 'bg-danger' }}">
                            {{ $user->status ? 'Hoạt động' : 'Tạm dừng' }}
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Đăng nhập cuối:</span>
                    <span class="info-value">
                        {{ $stats['last_login'] ? $stats['last_login']->format('d/m/Y H:i') : 'Chưa đăng nhập' }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Ngày tạo:</span>
                    <span class="info-value">{{ $user->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- Organizations and Roles -->
        <div class="col-md-6">
            <div class="info-section">
                <h5 class="section-title">
                    <i class="fas fa-building me-2"></i>Tổ chức và Vai trò
                </h5>
                @if($user->organizations->count() > 0)
                    @foreach($user->organizations as $org)
                        <div class="organization-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $org->name }}</h6>
                                    @if($org->pivot->role_id)
                                        @php
                                            $role = \App\Models\Role::find($org->pivot->role_id);
                                        @endphp
                                        @if($role)
                                            <span class="badge bg-info role-badge">{{ $role->name }}</span>
                                        @endif
                                    @endif
                                </div>
                                <span class="badge bg-success">Hoạt động</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted">Chưa tham gia tổ chức nào</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Global Roles -->
    <div class="row">
        <div class="col-12">
            <div class="info-section">
                <h5 class="section-title">
                    <i class="fas fa-shield-alt me-2"></i>Vai trò toàn cục
                </h5>
                @if($user->userRoles->count() > 0)
                    @foreach($user->userRoles as $role)
                        <span class="badge bg-primary role-badge">{{ $role->name }}</span>
                    @endforeach
                @else
                    <p class="text-muted">Chưa có vai trò toàn cục</p>
                @endif
            </div>
        </div>
    </div>

    <!-- KYC Information -->
    <div class="row">
        <div class="col-12">
            <div class="info-section">
                <h5 class="section-title">
                    <i class="fas fa-id-card me-2"></i>Thông tin KYC (Know Your Customer)
                    @if($user->userProfile)
                        <span class="badge {{ $user->userProfile->isKycComplete() ? 'bg-success' : 'bg-warning' }} ms-2">
                            {{ $user->userProfile->getKycCompletionPercentage() }}% hoàn thành
                        </span>
                    @endif
                </h5>
                
                @if($user->userProfile)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Ngày sinh:</span>
                                <span class="info-value">
                                    @if($user->userProfile->dob)
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ $user->userProfile->formatted_dob }}
                                        <small class="text-muted">({{ $user->userProfile->age }} tuổi)</small>
                                    @else
                                        <span class="text-muted">Chưa cập nhật</span>
                                    @endif
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Giới tính:</span>
                                <span class="info-value">
                                    @if($user->userProfile->gender)
                                        <i class="fas fa-{{ $user->userProfile->gender == 'male' ? 'mars' : ($user->userProfile->gender == 'female' ? 'venus' : 'genderless') }} me-1"></i>
                                        {{ $user->userProfile->gender_text }}
                                    @else
                                        <span class="text-muted">Chưa cập nhật</span>
                                    @endif
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Số CMND/CCCD:</span>
                                <span class="info-value">
                                    @if($user->userProfile->id_number)
                                        <i class="fas fa-id-card me-1"></i>
                                        {{ $user->userProfile->id_number }}
                                    @else
                                        <span class="text-muted">Chưa cập nhật</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <span class="info-label">Ngày cấp CMND/CCCD:</span>
                                <span class="info-value">
                                    @if($user->userProfile->id_issued_at)
                                        <i class="fas fa-calendar-check me-1"></i>
                                        {{ $user->userProfile->formatted_id_issued_at }}
                                    @else
                                        <span class="text-muted">Chưa cập nhật</span>
                                    @endif
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Địa chỉ thường trú:</span>
                                <span class="info-value">
                                    @if($user->userProfile->address)
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        {{ $user->userProfile->address }}
                                    @else
                                        <span class="text-muted">Chưa cập nhật</span>
                                    @endif
                                </span>
                            </div>
                            @if($user->userProfile->note)
                            <div class="info-item">
                                <span class="info-label">Ghi chú:</span>
                                <span class="info-value">
                                    <i class="fas fa-sticky-note me-1"></i>
                                    {{ $user->userProfile->note }}
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    @if(!$user->userProfile->isKycComplete())
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Thông tin KYC chưa đầy đủ:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($user->userProfile->getMissingKycFields() as $field)
                                <li>{{ $field }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @else
                    <div class="alert alert-success mt-3">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Thông tin KYC đã hoàn thành!</strong> Tài khoản đã được xác thực đầy đủ.
                    </div>
                    @endif
                @else
                    <div class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <h5>Chưa có thông tin KYC</h5>
                            <p>Người dùng chưa cập nhật thông tin xác thực danh tính.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Commission Events -->
    @if($user->commissionEvents->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="info-section">
                <h5 class="section-title">
                    <i class="fas fa-percentage me-2"></i>Lịch sử Hoa hồng
                </h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Loại</th>
                                <th>Số tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($user->commissionEvents->take(10) as $event)
                            <tr>
                                <td>{{ $event->created_at->format('d/m/Y') }}</td>
                                <td>{{ $event->event_type }}</td>
                                <td>{{ number_format($event->commission_total) }}đ</td>
                                <td>
                                    <span class="badge bg-success">Hoàn thành</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
</script>
@endpush
