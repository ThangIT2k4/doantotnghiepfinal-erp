@extends('layouts.superadmin')

@section('title', 'Đăng ký Dùng thử - Khách hàng tiềm năng')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('superadmin.dashboard') }}">
                <i class="fas fa-tachometer-alt me-1"></i>
                Dashboard
            </a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">
            <i class="fas fa-gift me-1"></i>
            Đăng ký Dùng thử
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-gift text-primary me-2"></i>
                Đăng ký Dùng thử
            </h1>
            <p class="text-muted mb-0">Danh sách khách hàng đăng ký dùng thử từ trang chủ</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng cộng
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Mới
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['new'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Đã liên hệ
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['contacted'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-phone fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Đã chuyển đổi
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['converted'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter me-1"></i>Bộ lọc
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('superadmin.trial-leads') }}" id="filter-form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tìm kiếm</label>
                        <input type="text" name="search" class="form-control" 
                               value="{{ request('search') }}" 
                               placeholder="Tên, SĐT, Email...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>Mới</option>
                            <option value="contacted" {{ request('status') == 'contacted' ? 'selected' : '' }}>Đã liên hệ</option>
                            <option value="qualified" {{ request('status') == 'qualified' ? 'selected' : '' }}>Đủ điều kiện</option>
                            <option value="converted" {{ request('status') == 'converted' ? 'selected' : '' }}>Đã chuyển đổi</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Tìm kiếm
                        </button>
                        <a href="{{ route('superadmin.trial-leads') }}" class="btn btn-secondary">
                            <i class="fas fa-redo me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Leads Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-users me-1"></i>Danh sách đăng ký dùng thử
            </h6>
        </div>
        <div class="card-body">
            @if($leads->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Họ tên</th>
                                <th>Số điện thoại</th>
                                <th>Email</th>
                                <th>Gói quan tâm</th>
                                <th>Trạng thái</th>
                                <th>Ngày đăng ký</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leads as $lead)
                                <tr>
                                    <td>#{{ $lead->id }}</td>
                                    <td>
                                        <strong>{{ $lead->name }}</strong>
                                    </td>
                                    <td>
                                        <a href="tel:{{ $lead->phone }}" class="text-decoration-none">
                                            <i class="fas fa-phone me-1"></i>{{ $lead->phone }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="mailto:{{ $lead->email }}" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>{{ $lead->email }}
                                        </a>
                                    </td>
                                    <td>
                                        @php
                                            $note = $lead->note ?? '';
                                            $planInterest = '';
                                            if (preg_match('/Gói quan tâm:\s*(.+)/', $note, $matches)) {
                                                $planInterest = trim($matches[1]);
                                            }
                                        @endphp
                                        @if($planInterest)
                                            <span class="badge bg-info">{{ $planInterest }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'new' => 'info',
                                                'contacted' => 'warning',
                                                'qualified' => 'primary',
                                                'converted' => 'success',
                                                'lost' => 'danger',
                                            ];
                                            $statusLabels = [
                                                'new' => 'Mới',
                                                'contacted' => 'Đã liên hệ',
                                                'qualified' => 'Đủ điều kiện',
                                                'converted' => 'Đã chuyển đổi',
                                                'lost' => 'Đã mất',
                                            ];
                                            $color = $statusColors[$lead->status] ?? 'secondary';
                                            $label = $statusLabels[$lead->status] ?? $lead->status;
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ $label }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $lead->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        <a href="{{ route('staff.leads.show', $lead->id) }}" 
                                           class="btn btn-sm btn-primary" 
                                           title="Xem chi tiết"
                                           target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $leads->appends(request()->query())->links('vendor.pagination.custom', [
                        'contentTypeOverride' => 'khách hàng tiềm năng',
                        'contentIconOverride' => 'fas fa-user-plus'
                    ]) }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                    <p class="text-muted">Chưa có đăng ký dùng thử nào</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

