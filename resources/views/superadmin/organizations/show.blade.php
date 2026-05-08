@extends('layouts.superadmin')

@section('title', 'Chi tiết Tổ chức')
@section('subtitle', 'Thông tin chi tiết tổ chức')

@section('content')
<div class="page-header">
    <div class="header-left">
        <h1 class="page-title">
            <i class="fas fa-building"></i>
            {{ $organization->name }}
        </h1>
        <p class="page-subtitle">Thông tin chi tiết tổ chức</p>
    </div>
    <div class="header-right">
        <div class="btn-group">
            <a href="{{ route('superadmin.organizations.edit', $organization) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i>
                Chỉnh sửa
            </a>
            <a href="{{ route('superadmin.organizations.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i>
                Quay lại
            </a>
        </div>
    </div>
</div>

    <div class="content-body">
        <div class="row">
            <!-- Organization Details -->
            <div class="col-lg-8">
                <div class="details-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-info-circle"></i>
                            Thông tin cơ bản
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label>Tên tổ chức</label>
                                    <p>{{ $organization->name }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label>Email</label>
                                    <p>
                                        <a href="mailto:{{ $organization->email }}">{{ $organization->email }}</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label>Số điện thoại</label>
                                    <p>{{ $organization->phone ?: 'Chưa cập nhật' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label>Trạng thái</label>
                                    <p>
                                        <span class="status-badge {{ $organization->status ? 'active' : 'inactive' }}">
                                            {{ $organization->status ? 'Hoạt động' : 'Tạm dừng' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        @if($organization->address)
                        <div class="info-item">
                            <label>Địa chỉ</label>
                            <p>{{ $organization->address }}</p>
                        </div>
                        @endif
                        
                        @if($organization->description)
                        <div class="info-item">
                            <label>Mô tả</label>
                            <p>{{ $organization->description }}</p>
                        </div>
                        @endif
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label>Ngày tạo</label>
                                    <p>{{ $organization->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item">
                                    <label>Cập nhật lần cuối</label>
                                    <p>{{ $organization->updated_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Subscription Information -->
                <div class="details-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-box"></i>
                            Subscription
                        </h5>
                        <a href="{{ route('superadmin.organizations.subscription.show', $organization->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> Chi tiết
                        </a>
                    </div>
                    <div class="card-body">
                        @php
                            $subscription = $organization->activeSubscription;
                        @endphp
                        
                        @if($subscription)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Gói hiện tại</label>
                                        <p><strong>{{ $subscription->plan->name }}</strong></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Trạng thái</label>
                                        <p>
                                            <span class="badge bg-{{ $subscription->getStatusColor() }}">
                                                {{ $subscription->getStatusLabel() }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Kết thúc</label>
                                        <p>{{ $subscription->current_period_end ? $subscription->current_period_end->format('d/m/Y') : '-' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label>Còn lại</label>
                                        <p>
                                            @if($subscription->daysUntilExpiry() !== null)
                                                <strong>{{ $subscription->daysUntilExpiry() }} ngày</strong>
                                            @else
                                                -
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('superadmin.organizations.subscription.show', $organization->id) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye me-1"></i> Xem chi tiết
                                </a>
                                <a href="{{ route('superadmin.organizations.subscription.assign', $organization->id) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-exchange-alt me-1"></i> Đổi gói
                                </a>
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Tổ chức chưa có gói dịch vụ nào.
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('superadmin.organizations.subscription.assign', $organization->id) }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Gán gói dịch vụ
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Settings -->
                @if($organization->settings && count($organization->settings) > 0)
                <div class="details-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-cogs"></i>
                            Cài đặt
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="settings-list">
                            @foreach($organization->settings as $key => $value)
                            <div class="setting-item">
                                <label>{{ ucfirst(str_replace('_', ' ', $key)) }}</label>
                                <span class="setting-value">
                                    @if(is_bool($value))
                                        <span class="badge bg-{{ $value ? 'success' : 'secondary' }}">
                                            {{ $value ? 'Bật' : 'Tắt' }}
                                        </span>
                                    @else
                                        {{ $value }}
                                    @endif
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
            
            <!-- Statistics -->
            <div class="col-lg-4">
                <div class="stats-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-bar"></i>
                            Thống kê
                        </h5>
                    </div>
                <div class="card-body">
                    <!-- User Statistics -->
                    <div class="stat-item">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['users']->total_users ?? 0 }}</h3>
                            <p>Tổng người dùng</p>
                        </div>
                    </div>
                    
                    <!-- Property Statistics -->
                    <div class="stat-item">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['properties']->total_properties ?? 0 }}</h3>
                            <p>Tổng bất động sản</p>
                        </div>
                    </div>
                    
                    <!-- Unit Statistics -->
                    <div class="stat-item">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['units']->total_units ?? 0 }}</h3>
                            <p>Tổng phòng</p>
                        </div>
                    </div>
                    
                    <!-- Active Leases -->
                    <div class="stat-item">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['leases']->active_leases ?? 0 }}</h3>
                            <p>Hợp đồng hoạt động</p>
                        </div>
                    </div>
                </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="actions-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-bolt"></i>
                            Thao tác nhanh
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="action-buttons">
                            <button type="button" 
                                    class="btn btn-{{ $organization->status ? 'secondary' : 'success' }} w-100 mb-2"
                                    onclick="toggleStatus({{ $organization->id }}, {{ $organization->status ? 'false' : 'true' }})">
                                <i class="fas fa-{{ $organization->status ? 'pause' : 'play' }}"></i>
                                {{ $organization->status ? 'Tạm dừng' : 'Kích hoạt' }}
                            </button>
                            
                            <button type="button" 
                                    class="btn btn-danger w-100"
                                    onclick="deleteOrganization({{ $organization->id }}, '{{ $organization->name }}')">
                                <i class="fas fa-trash"></i>
                                Xóa tổ chức
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detailed Statistics -->
        <div class="row mt-4">
            <!-- User Statistics by Role -->
            <div class="col-lg-6 mb-4">
                <div class="details-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-users-cog"></i>
                            Thống kê người dùng theo vai trò
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-primary">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ $stats['users']->tenant_count ?? 0 }}</h4>
                                        <p>Tenant</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-info">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ $stats['users']->agent_count ?? 0 }}</h4>
                                        <p>Agent</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-success">
                                        <i class="fas fa-user-shield"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ $stats['users']->manager_count ?? 0 }}</h4>
                                        <p>Manager</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-warning">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ $stats['users']->admin_count ?? 0 }}</h4>
                                        <p>Admin</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-dark">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ $stats['users']->landlord_count ?? 0 }}</h4>
                                        <p>Landlord</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Property & Unit Statistics -->
            <div class="col-lg-6 mb-4">
                <div class="details-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-pie"></i>
                            Thống kê bất động sản & phòng
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ $stats['properties']->active_properties ?? 0 }}</h4>
                                        <p>BDS hoạt động</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-secondary">
                                        <i class="fas fa-pause-circle"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ $stats['properties']->inactive_properties ?? 0 }}</h4>
                                        <p>BDS tạm dừng</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-info">
                                        <i class="fas fa-door-open"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ $stats['units']->available_units ?? 0 }}</h4>
                                        <p>Phòng trống</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-warning">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ $stats['units']->occupied_units ?? 0 }}</h4>
                                        <p>Phòng đã thuê</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Financial Statistics -->
        <div class="row mt-4">
            <div class="col-lg-6 mb-4">
                <div class="details-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-dollar-sign"></i>
                            Thống kê tài chính
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-success">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ number_format($stats['leases']->total_monthly_rent ?? 0) }}đ</h4>
                                        <p>Tổng tiền thuê/tháng</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-info">
                                        <i class="fas fa-calculator"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ number_format($stats['leases']->avg_monthly_rent ?? 0) }}đ</h4>
                                        <p>Tiền thuê trung bình</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="details-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-percentage"></i>
                            Thống kê hoa hồng
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-warning">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ $stats['commissions']->total_commissions ?? 0 }}</h4>
                                        <p>Tổng giao dịch hoa hồng</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="stat-item-small">
                                    <div class="stat-icon-small bg-primary">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                    <div class="stat-content-small">
                                        <h4>{{ number_format($stats['commissions']->total_commission_amount ?? 0) }}đ</h4>
                                        <p>Tổng tiền hoa hồng</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Users List -->
        @if(($stats['users']->total_users ?? 0) > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="details-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users"></i>
                            Danh sách người dùng ({{ $organization->users_count ?? 0 }})
                        </h5>
                        <div class="btn-group">
                            <a href="{{ route('superadmin.users.create') }}?organization_id={{ $organization->id }}" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-user-plus me-1"></i>Thêm User
                            </a>
                            <a href="{{ route('superadmin.users.index') }}?organization_id={{ $organization->id }}" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-list me-1"></i>Xem tất cả
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tên</th>
                                        <th>Email</th>
                                        <th>Vai trò</th>
                                        <th>Ngày tham gia</th>
                                        <th width="100">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($organization->users as $user)
                                    <tr>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    {{ strtoupper(substr($user->full_name, 0, 1)) }}
                                                </div>
                                                <span>{{ $user->full_name }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            @if($user->organization_roles && count($user->organization_roles) > 0)
                                                @foreach($user->organization_roles as $role)
                                                <span class="badge bg-info">{{ $role->name }}</span>
                                                @endforeach
                                            @else
                                                <span class="badge bg-secondary">No roles</span>
                                            @endif
                                        </td>
                                        <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('superadmin.users.show', $user) }}" 
                                                   class="btn btn-outline-info btn-sm" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('superadmin.users.edit', $user) }}" 
                                                   class="btn btn-outline-warning btn-sm" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection

@push('styles')
<style>
/* Small stat items */
.stat-item-small {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #dee2e6;
    transition: all 0.3s ease;
}

.stat-item-small:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.stat-icon-small {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: white;
    font-size: 18px;
}

.stat-content-small h4 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
}

.stat-content-small p {
    margin: 0;
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Color variations for small icons */
.stat-icon-small.bg-primary { background: linear-gradient(135deg, #007bff, #0056b3); }
.stat-icon-small.bg-info { background: linear-gradient(135deg, #17a2b8, #117a8b); }
.stat-icon-small.bg-success { background: linear-gradient(135deg, #28a745, #1e7e34); }
.stat-icon-small.bg-warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
.stat-icon-small.bg-secondary { background: linear-gradient(135deg, #6c757d, #545b62); }
.stat-icon-small.bg-dark { background: linear-gradient(135deg, #343a40, #212529); }
.details-card, .stats-card, .actions-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.card-header {
    padding: 1.5rem 1.5rem 0;
    border-bottom: none;
}

.card-title {
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-body {
    padding: 1.5rem;
}

.info-item {
    margin-bottom: 1.5rem;
}

.info-item label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    display: block;
}

.info-item p {
    margin: 0;
    color: #2c3e50;
    font-size: 1rem;
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

.settings-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background-color: #f8f9fa;
    border-radius: 6px;
}

.setting-item label {
    font-weight: 500;
    margin: 0;
}

.setting-value {
    font-weight: 600;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #e9ecef;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.stat-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
}

.stat-content p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.action-buttons .btn {
    font-weight: 500;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.9rem;
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
                        // Redirect to organizations list
                        setTimeout(() => {
                            window.location.href = '{{ route("superadmin.organizations.index") }}';
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
    }
}
</script>
@endpush
