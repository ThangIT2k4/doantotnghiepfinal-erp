@extends('layouts.superadmin')

@section('title', 'Quản lý Subscriptions')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Subscriptions</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-invoice-dollar me-2"></i>
                Quản lý Subscriptions
            </h1>
            <p class="text-muted mb-0">Tất cả subscriptions trong hệ thống</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Bộ lọc</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('superadmin.subscriptions.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tìm kiếm tổ chức</label>
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Tên tổ chức...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="trial" {{ request('status') == 'trial' ? 'selected' : '' }}>Dùng thử</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Hoạt động</option>
                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Hết hạn</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Gói</label>
                            <select name="plan_id" class="form-control">
                                <option value="">Tất cả</option>
                                @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Chu kỳ</label>
                            <select name="payment_cycle" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="monthly" {{ request('payment_cycle') == 'monthly' ? 'selected' : '' }}>Hàng tháng</option>
                                <option value="yearly" {{ request('payment_cycle') == 'yearly' ? 'selected' : '' }}>Hàng năm</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="btn-group w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Lọc
                                </button>
                                <a href="{{ route('superadmin.subscriptions.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Xóa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Subscriptions Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách Subscriptions</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tổ chức</th>
                            <th>Gói</th>
                            <th>Trạng thái</th>
                            <th>Chu kỳ</th>
                            <th>Bắt đầu</th>
                            <th>Kết thúc</th>
                            <th>Còn lại</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions as $subscription)
                        <tr>
                            <td>
                                <a href="{{ route('superadmin.organizations.show', $subscription->organization_id) }}">
                                    <strong>{{ $subscription->organization->name ?? 'N/A' }}</strong>
                                </a><br>
                                <small class="text-muted">{{ $subscription->organization->email ?? '' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $subscription->plan->name ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $subscription->getStatusColor() }}">
                                    {{ $subscription->getStatusLabel() }}
                                </span>
                                @if($subscription->isOnTrial())
                                    <br><small class="text-muted">Trial</small>
                                @endif
                            </td>
                            <td>{{ ucfirst($subscription->payment_cycle) }}</td>
                            <td>{{ $subscription->current_period_start ? $subscription->current_period_start->format('d/m/Y') : '-' }}</td>
                            <td>{{ $subscription->current_period_end ? $subscription->current_period_end->format('d/m/Y') : '-' }}</td>
                            <td>
                                @if($subscription->daysUntilExpiry() !== null)
                                    @if($subscription->daysUntilExpiry() <= 7)
                                        <span class="text-danger">
                                            <strong>{{ $subscription->daysUntilExpiry() }} ngày</strong>
                                        </span>
                                    @else
                                        {{ $subscription->daysUntilExpiry() }} ngày
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('superadmin.organizations.subscription.show', $subscription->organization_id) }}" 
                                   class="btn btn-sm btn-info" 
                                   title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có subscription nào</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($subscriptions->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $subscriptions->appends(request()->query())->links('vendor.pagination.custom', [
                    'contentTypeOverride' => 'gói đăng ký',
                    'contentIconOverride' => 'fas fa-file-invoice-dollar'
                ]) }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

