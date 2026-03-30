@extends('layouts.staff_dashboard')

@section('title', 'Quản lý thông tin ngân hàng người dùng')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-university mr-2"></i>
                        Quản lý thông tin ngân hàng người dùng
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('staff.users.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Thêm mới
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('staff.user-banking.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tìm kiếm</label>
                                    <input type="text" name="search" class="form-control" 
                                           value="{{ request('search') }}" 
                                           placeholder="Tên, email, SĐT, mã số thuế...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Vai trò</label>
                                    <select name="role" class="form-control">
                                        <option value="">Tất cả vai trò</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->key_code }}" 
                                                    {{ request('role') == $role->key_code ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Trạng thái ngân hàng</label>
                                    <select name="banking_status" class="form-control">
                                        <option value="">Tất cả</option>
                                        <option value="has_banking" {{ request('banking_status') == 'has_banking' ? 'selected' : '' }}>
                                            Có thông tin ngân hàng
                                        </option>
                                        <option value="no_banking" {{ request('banking_status') == 'no_banking' ? 'selected' : '' }}>
                                            Chưa có thông tin ngân hàng
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Tìm kiếm
                                        </button>
                                        <a href="{{ route('staff.user-banking.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Xóa
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Users Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Thông tin cá nhân</th>
                                    <th>Vai trò</th>
                                    <th>Thông tin ngân hàng</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $index => $user)
                                    <tr>
                                        <td>{{ $users->firstItem() + $index }}</td>
                                        <td>
                                            <div>
                                                <strong>{{ $user->full_name }}</strong>
                                                @if($user->tax_code)
                                                    <br><small class="text-muted">MST: {{ $user->tax_code }}</small>
                                                @endif
                                                @if($user->phone)
                                                    <br><small class="text-muted">{{ $user->phone }}</small>
                                                @endif
                                                @if($user->email)
                                                    <br><small class="text-muted">{{ $user->email }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @foreach($user->organizationUsers as $orgUser)
                                                <span class="badge badge-info">{{ $orgUser->role->name }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            @if($user->hasValidBankingInfo())
                                                <div>
                                                    <strong>{{ $user->sepayBank->name ?? 'N/A' }}</strong>
                                                    <br><small class="text-muted">{{ $user->account_number }}</small>
                                                    <br><small class="text-muted">{{ $user->account_holder_name }}</small>
                                                </div>
                                            @else
                                                <span class="text-muted">Chưa có thông tin</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->hasValidBankingInfo())
                                                <span class="badge badge-success">Đã cập nhật</span>
                                            @else
                                                <span class="badge badge-warning">Chưa cập nhật</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('staff.user-banking.show', $user) }}" 
                                                   class="btn btn-info btn-sm" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('staff.user-banking.edit', ['user_banking' => $user]) }}" 
                                                   class="btn btn-warning btn-sm" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">Không có người dùng nào</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $users->appends(request()->query())->links('vendor.pagination.custom', [
                            'tableContainerId' => 'user-banking-table-container',
                            'htmxIndicator' => '#htmx-loading-index-filters-form'
                        ]) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-submit form on filter change
    $('select[name="role"], select[name="banking_status"]').change(function() {
        $(this).closest('form').submit();
    });
});
</script>
@endpush
