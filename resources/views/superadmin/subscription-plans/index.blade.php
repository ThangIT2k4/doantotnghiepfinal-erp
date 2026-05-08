@extends('layouts.superadmin')

@section('title', 'Quản lý Gói Dịch Vụ')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Subscription Plans</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-box me-2"></i>
                Quản lý Gói Dịch Vụ
            </h1>
            <p class="text-muted mb-0">Quản lý các gói đăng ký cho tổ chức</p>
        </div>
        <div>
            <a href="{{ route('superadmin.subscription-plans.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>
                Tạo Gói Mới
            </a>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Bộ lọc và tìm kiếm</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('superadmin.subscription-plans.index') }}" class="filters-form">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search">Tìm kiếm</label>
                            <input type="text" 
                                   name="search" 
                                   id="search" 
                                   class="form-control" 
                                   value="{{ request('search') }}" 
                                   placeholder="Tên, mã gói...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Trạng thái</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
                                <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Không hoạt động</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="type">Loại</label>
                            <select name="type" id="type" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="standard" {{ request('type') == 'standard' ? 'selected' : '' }}>Chuẩn</option>
                                <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Tùy chỉnh</option>
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
                                <a href="{{ route('superadmin.subscription-plans.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Plans Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Danh sách gói dịch vụ</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Tên Gói</th>
                            <th>Giá tháng</th>
                            <th>Giá năm</th>
                            <th>Trial</th>
                            <th>Đang dùng</th>
                            <th>Trạng thái</th>
                            <th>Loại</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $plan->code }}</span></td>
                            <td>
                                <strong>{{ $plan->name }}</strong><br>
                                <small class="text-muted">{{ Str::limit($plan->description, 50) }}</small>
                            </td>
                            <td>{{ number_format($plan->price_monthly, 0, ',', '.') }} {{ $plan->currency }}</td>
                            <td>{{ number_format($plan->price_yearly, 0, ',', '.') }} {{ $plan->currency }}</td>
                            <td>{{ $plan->trial_days }} ngày</td>
                            <td>
                                <span class="badge bg-info">{{ $plan->subscriptions_count }} tổ chức</span>
                            </td>
                            <td>
                                @if($plan->is_active)
                                    <span class="badge bg-success">Hoạt động</span>
                                @else
                                    <span class="badge bg-secondary">Không hoạt động</span>
                                @endif
                            </td>
                            <td>
                                @if($plan->is_custom)
                                    <span class="badge bg-warning">Tùy chỉnh</span>
                                @else
                                    <span class="badge bg-primary">Chuẩn</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('superadmin.subscription-plans.show', $plan->id) }}" 
                                       class="btn btn-sm btn-info" 
                                       title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('superadmin.subscription-plans.edit', $plan->id) }}" 
                                       class="btn btn-sm btn-warning" 
                                       title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('superadmin.subscription-plans.duplicate', $plan->id) }}" 
                                          method="POST" 
                                          class="d-inline">
                                        @csrf
                                        <button type="submit" 
                                                class="btn btn-sm btn-secondary" 
                                                title="Nhân bản">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('superadmin.subscription-plans.toggle-status', $plan->id) }}" 
                                          method="POST" 
                                          class="d-inline">
                                        @csrf
                                        <button type="submit" 
                                                class="btn btn-sm {{ $plan->is_active ? 'btn-secondary' : 'btn-success' }}" 
                                                title="{{ $plan->is_active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                    @if($plan->subscriptions_count == 0)
                                    <form action="{{ route('superadmin.subscription-plans.destroy', $plan->id) }}" 
                                          method="POST" 
                                          class="d-inline" 
                                          onsubmit="return confirm('Bạn có chắc muốn xóa gói này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-sm btn-danger" 
                                                title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có gói dịch vụ nào</p>
                                <a href="{{ route('superadmin.subscription-plans.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Tạo gói đầu tiên
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($plans->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $plans->appends(request()->query())->links('vendor.pagination.custom', [
                    'contentTypeOverride' => 'gói dịch vụ',
                    'contentIconOverride' => 'fas fa-box'
                ]) }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

