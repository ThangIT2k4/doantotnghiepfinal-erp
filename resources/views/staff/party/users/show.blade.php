@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết người dùng')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        <!-- Page Header -->
        @include('staff.components.show-page-header', [
            'title' => 'Chi tiết người dùng',
            'subtitle' => 'Thông tin chi tiết về tài khoản: ' . ($targetUser->userProfile->full_name ?? $targetUser->email),
            'icon' => 'fas fa-user',
            'breadcrumbs' => [
                ['label' => 'Người dùng', 'url' => route('staff.users.index')],
                ['label' => $targetUser->userProfile->full_name ?? $targetUser->email, 'active' => true]
            ]
        ])

        <!-- Tabs Navigation -->
        @include('staff.components.tab-navigation', [
            'tabs' => [
                'basic-info' => [
                    'label' => 'Thông tin cơ bản',
                    'icon' => 'fas fa-info-circle',
                    'color' => 'primary'
                ],
                'kyc-info' => [
                    'label' => 'Thông tin KYC',
                    'icon' => 'fas fa-id-card',
                    'color' => 'info'
                ],
                'roles-info' => [
                    'label' => 'Vai trò',
                    'icon' => 'fas fa-user-tag',
                    'color' => 'success'
                ],
                'capabilities' => [
                    'label' => 'Phân quyền',
                    'icon' => 'fas fa-key',
                    'color' => 'warning'
                ],
                'banking-info' => [
                    'label' => 'Thông tin ngân hàng',
                    'icon' => 'fas fa-university',
                    'color' => 'success'
                ]
            ],
            'storageKey' => 'userTabStates',
            'defaultVisible' => ['basic-info']
        ])

        <!-- User Details -->
        <div class="row">
            <!-- User Profile Card -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="mb-3">{{ $targetUser->userProfile->full_name ?? $targetUser->email }}</h4>
                        
                        <div class="mb-3">
                            <p class="mb-1">
                                <i class="fas fa-envelope me-2 text-muted"></i>
                                <a href="mailto:{{ $targetUser->email }}" class="text-decoration-none">{{ $targetUser->email }}</a>
                            </p>
                            @if($targetUser->phone)
                                <p class="mb-1">
                                    <i class="fas fa-phone me-2 text-muted"></i>
                                    <a href="tel:{{ $targetUser->phone }}" class="text-decoration-none">{{ $targetUser->phone }}</a>
                                </p>
                            @endif
                        </div>
                        
                        <!-- Status Badge -->
                        <div class="mb-3">
                            @if($targetUser->status)
                                <span class="badge bg-success fs-6">Hoạt động</span>
                            @else
                                <span class="badge bg-warning fs-6">Tạm ngưng</span>
                            @endif
                        </div>

                        <!-- Role Badges -->
                        <div class="mb-3">
                            <h6>Vai trò:</h6>
                            @if($targetUser->userRoles->count() > 0)
                                @foreach($targetUser->userRoles as $role)
                                    <span class="badge bg-info me-1">{{ $role->name }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">Chưa có vai trò</span>
                            @endif
                        </div>

                        <!-- Card Thao tác -->
                        <div class="card shadow-sm mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cogs me-2"></i>Thao tác
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    {{-- Nút Sửa --}}
                                    <a href="{{ route('staff.users.edit', $targetUser->id) }}" 
                                       class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-edit me-1"></i>Sửa
                                    </a>
                                    
                                    {{-- Nút chuyển trạng thái có điều kiện --}}
                                    @if($targetUser->id !== auth()->id())
                                        @if($targetUser->status)
                                            <button type="button" 
                                                    class="btn btn-warning btn-sm w-100" 
                                                    onclick="updateStatus(0)">
                                                <i class="fas fa-pause me-1"></i>Tạm dừng
                                            </button>
                                        @else
                                            <button type="button" 
                                                    class="btn btn-success btn-sm w-100" 
                                                    onclick="updateStatus(1)">
                                                <i class="fas fa-play me-1"></i>Kích hoạt
                                            </button>
                                        @endif
                                        
                                        {{-- Nút Xóa --}}
                                        <button type="button" 
                                                class="btn btn-danger btn-sm w-100"
                                                onclick="deleteUser({{ $targetUser->id }}, '{{ addslashes($targetUser->userProfile->full_name ?? $targetUser->email) }}')">
                                            <i class="fas fa-trash-alt me-1"></i>Xóa tài khoản
                                        </button>
                                    @endif
                                    
                                    {{-- Nút Quay lại --}}
                                    <a href="{{ route('staff.users.index') }}" 
                                       class="btn btn-secondary btn-sm w-100">
                                        <i class="fas fa-arrow-left me-1"></i>Quay lại
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Information -->
            <div class="col-lg-8">
                <div class="card shadow-sm tab-content" id="tab-basic-info">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin chi tiết
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">ID người dùng:</label>
                                    <div class="p-2 bg-light rounded">
                                        <span class="badge bg-secondary">#{{ $targetUser->id }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Trạng thái:</label>
                                    <div class="p-2 bg-light rounded">
                                        @if($targetUser->status)
                                            <span class="badge bg-success">Hoạt động</span>
                                        @else
                                            <span class="badge bg-warning">Tạm ngưng</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Họ và tên:</label>
                                    <div class="p-2 bg-light rounded">{{ $targetUser->userProfile->full_name ?? 'Chưa cập nhật' }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email:</label>
                                    <div class="p-2 bg-light rounded">{{ $targetUser->email }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Số điện thoại:</label>
                                    <div class="p-2 bg-light rounded">
                                        {{ $targetUser->phone ?? 'Chưa cập nhật' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Vai trò:</label>
                                    <div class="p-2 bg-light rounded">
                                        @if($targetUser->userRoles->count() > 0)
                                            @foreach($targetUser->userRoles as $role)
                                                <span class="badge bg-info me-1">{{ $role->name }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">Chưa có vai trò</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày tạo:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ $targetUser->created_at->format('d/m/Y H:i:s') }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Cập nhật cuối:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ $targetUser->updated_at->format('d/m/Y H:i:s') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($targetUser->last_login_at)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Đăng nhập cuối:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-sign-in-alt me-1"></i>
                                        {{ $targetUser->last_login_at->format('d/m/Y H:i:s') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($targetUser->deleted_at)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày xóa:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-trash me-1"></i>
                                        {{ $targetUser->deleted_at->format('d/m/Y H:i:s') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- KYC Information -->
                @if($targetUser->userProfile)
                <div class="card shadow-sm mt-4 tab-content" id="tab-kyc-info" style="display: none;">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-id-card me-2"></i>Thông tin KYC (Know Your Customer)
                        </h6>
                        <span class="badge {{ $targetUser->userProfile->isKycComplete() ? 'bg-success' : 'bg-warning' }}">
                            {{ $targetUser->userProfile->getKycCompletionPercentage() }}% hoàn thành
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày sinh:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ $targetUser->userProfile->formatted_dob ?? 'Chưa cập nhật' }}
                                        @if($targetUser->userProfile->dob)
                                            <small class="text-muted">({{ $targetUser->userProfile->age }} tuổi)</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Giới tính:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-{{ $targetUser->userProfile->gender == 'male' ? 'mars' : ($targetUser->userProfile->gender == 'female' ? 'venus' : 'genderless') }} me-1"></i>
                                        {{ $targetUser->userProfile->gender_text }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Số CMND/CCCD:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-id-card me-1"></i>
                                        {{ $targetUser->userProfile->id_number ?? 'Chưa cập nhật' }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngày cấp CMND/CCCD:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        {{ $targetUser->userProfile->formatted_id_issued_at ?? 'Chưa cập nhật' }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Địa chỉ thường trú:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        {{ $targetUser->userProfile->address ?? 'Chưa cập nhật' }}
                                    </div>
                                </div>
                                @if($targetUser->userProfile->note)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ghi chú:</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        {{ $targetUser->userProfile->note }}
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        
                        @if(!$targetUser->userProfile->isKycComplete())
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Thông tin KYC chưa đầy đủ:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($targetUser->userProfile->getMissingKycFields() as $field)
                                    <li>{{ $field }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @else
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Thông tin KYC đã hoàn thành!</strong> Tài khoản đã được xác thực đầy đủ.
                        </div>
                        @endif
                    </div>
                </div>
                @else
                <div class="card shadow-sm mt-4 tab-content" id="tab-kyc-info" style="display: none;">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-id-card me-2"></i>Thông tin KYC (Know Your Customer)
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="text-muted">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <h5>Chưa có thông tin KYC</h5>
                            <p>Người dùng chưa cập nhật thông tin xác thực danh tính.</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Role Details -->
                @if($targetUser->userRoles->count() > 0)
                <div class="card shadow-sm mt-4 tab-content" id="tab-roles-info" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-user-tag me-2"></i>Chi tiết vai trò
                        </h6>
                    </div>
                    <div class="card-body">
                        @foreach($targetUser->userRoles as $role)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $role->name }}</h6>
                                    <small class="text-muted">Mã vai trò: {{ $role->key_code }}</small>
                                </div>
                                <span class="badge bg-info">{{ $role->name }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Banking Information -->
                <div class="card shadow-sm mt-4 tab-content" id="tab-banking-info" style="display: none;">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-university me-2"></i>Thông tin ngân hàng
                        </h6>
                        @include('staff.components.button', [
                            'type' => 'link',
                            'variant' => 'light',
                            'size' => 'sm',
                            'label' => 'Chỉnh sửa',
                            'icon' => 'fas fa-edit',
                            'url' => route('staff.user-banking.edit', ['user_banking' => $targetUser])
                        ])
                    </div>
                    <div class="card-body">
                        @if($targetUser->hasValidBankingInfo())
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td style="width: 40%"><strong>Ngân hàng:</strong></td>
                                            <td>{{ $targetUser->sepayBank->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Mã ngân hàng:</strong></td>
                                            <td>{{ $targetUser->sepayBank->code ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Số tài khoản:</strong></td>
                                            <td>{{ $targetUser->account_number }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tên chủ tài khoản:</strong></td>
                                            <td>{{ $targetUser->account_holder_name }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td style="width: 40%"><strong>Chi nhánh:</strong></td>
                                            <td>{{ $targetUser->branch_name ?? 'Chưa cập nhật' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Mã chi nhánh:</strong></td>
                                            <td>{{ $targetUser->branch_code ?? 'Chưa cập nhật' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Mã SWIFT:</strong></td>
                                            <td>{{ $targetUser->swift_code ?? 'Chưa cập nhật' }}</td>
                                        </tr>
                                        @if($targetUser->banking_notes)
                                            <tr>
                                                <td><strong>Ghi chú:</strong></td>
                                                <td>{{ $targetUser->banking_notes }}</td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>

                            <!-- Recent Transactions -->
                            @if($targetUser->companyInvoices->count() > 0 || $targetUser->cashOutflows->count() > 0)
                                <hr class="my-4">
                                <h6 class="mb-3">
                                    <i class="fas fa-history me-2"></i>Lịch sử giao dịch gần đây
                                </h6>
                                <div class="row">
                                    <!-- Company Invoices -->
                                    @if($targetUser->companyInvoices->count() > 0)
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Hóa đơn công ty</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Số hóa đơn</th>
                                                            <th>Loại</th>
                                                            <th>Số tiền</th>
                                                            <th>Trạng thái</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($targetUser->companyInvoices->take(5) as $invoice)
                                                            <tr>
                                                                <td>{{ $invoice->invoice_no }}</td>
                                                                <td>{{ $invoice->invoice_type }}</td>
                                                                <td>{{ number_format($invoice->total_amount, 0, ',', '.') }} VND</td>
                                                                <td>
                                                                    <span class="badge bg-{{ $invoice->status == 'paid' ? 'success' : 'warning' }}">
                                                                        {{ $invoice->status }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Cash Outflows -->
                                    @if($targetUser->cashOutflows->count() > 0)
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Chi trả</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Mô tả</th>
                                                            <th>Số tiền</th>
                                                            <th>Trạng thái</th>
                                                            <th>Ngày</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($targetUser->cashOutflows->take(5) as $outflow)
                                                            <tr>
                                                                <td>{{ $outflow->description }}</td>
                                                                <td>{{ number_format($outflow->amount, 0, ',', '.') }} VND</td>
                                                                <td>
                                                                    <span class="badge bg-{{ $outflow->status == 'success' ? 'success' : 'warning' }}">
                                                                        {{ $outflow->status }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ $outflow->paid_at ? $outflow->paid_at->format('d/m/Y') : 'N/A' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <p class="text-muted">Chưa có thông tin ngân hàng</p>
                                @include('staff.components.button', [
                                    'type' => 'link',
                                    'variant' => 'primary',
                                    'label' => 'Thêm thông tin ngân hàng',
                                    'icon' => 'fas fa-plus',
                                    'url' => route('staff.user-banking.edit', ['user_banking' => $targetUser])
                                ])
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Capabilities -->
                <div class="card shadow-sm mt-4 tab-content" id="tab-capabilities" style="display: none;">
                    <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-key me-2"></i>Phân quyền (Capabilities)
                        </h6>
                        @include('staff.components.button', [
                            'type' => 'link',
                            'variant' => 'light',
                            'size' => 'sm',
                            'label' => 'Quản lý quyền',
                            'icon' => 'fas fa-cog',
                            'url' => route('staff.users.capabilities', $targetUser->id)
                        ])
                    </div>
                    <div class="card-body">
                        @if(isset($allCapabilities) && $allCapabilities->count() > 0)
                            <!-- Tabs Navigation -->
                            <div class="mb-3">
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($allCapabilities as $category => $caps)
                                        <button type="button" class="btn btn-sm btn-outline-primary {{ $loop->first ? 'active' : '' }}" onclick="toggleCapabilityCategory('{{ $category }}', this)">
                                            <i class="fas fa-{{ $category == 'ticket' ? 'ticket-alt' : ($category == 'lease' ? 'file-contract' : ($category == 'invoice' ? 'file-invoice' : ($category == 'property' ? 'building' : ($category == 'party' ? 'users' : 'folder')))) }} me-2"></i>
                                            {{ ucfirst(str_replace('_', ' ', $category)) }}
                                            <span class="badge bg-secondary ms-2">{{ $caps->count() }}</span>
                                        </button>
                                    @endforeach
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="expandAllCapabilityCategories()">
                                        <i class="fas fa-expand"></i> Mở tất cả
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="collapseAllCapabilityCategories()">
                                        <i class="fas fa-compress"></i> Đóng tất cả
                                    </button>
                                </div>
                            </div>

                            <!-- Capabilities by Category -->
                            @foreach($allCapabilities as $category => $caps)
                                <div class="capability-category-content" id="category-{{ $category }}" style="{{ $loop->first ? '' : 'display: none;' }}">
                                    <div class="card border mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">
                                                <i class="fas fa-{{ $category == 'ticket' ? 'ticket-alt' : ($category == 'lease' ? 'file-contract' : ($category == 'invoice' ? 'file-invoice' : ($category == 'property' ? 'building' : ($category == 'party' ? 'users' : 'folder')))) }} me-2"></i>
                                                {{ ucfirst(str_replace('_', ' ', $category)) }}
                                                <small class="text-muted">({{ $caps->count() }} quyền)</small>
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 50%">Quyền</th>
                                                            <th style="width: 25%">Trạng thái</th>
                                                            <th style="width: 25%">Nguồn</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($caps as $cap)
                                                            @php
                                                                $hasCap = isset($userCapabilities[$cap->key_code]) && $userCapabilities[$cap->key_code];
                                                                $isOverride = $orgUser && $orgUser->capabilityOverrides->where('capability_id', $cap->id)->isNotEmpty();
                                                                $override = $orgUser ? $orgUser->capabilityOverrides->where('capability_id', $cap->id)->first() : null;
                                                                $overrideGranted = $override && $override->granted && !$override->revoked_at;
                                                                $overrideDenied = $override && (!$override->granted || $override->revoked_at);
                                                            @endphp
                                                            <tr>
                                                                <td>
                                                                    <div class="small">
                                                                        <strong>{{ $cap->name }}</strong>
                                                                        <br>
                                                                        <code class="text-muted">{{ $cap->key_code }}</code>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    @if($hasCap)
                                                                        <span class="badge bg-success">Có quyền</span>
                                                                    @else
                                                                        <span class="badge bg-secondary">Không có</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if($isOverride)
                                                                        @if($overrideGranted)
                                                                            <span class="badge bg-info" title="Override: Cấp quyền">
                                                                                <i class="fas fa-edit"></i> Override
                                                                            </span>
                                                                        @elseif($overrideDenied)
                                                                            <span class="badge bg-warning" title="Override: Từ chối">
                                                                                <i class="fas fa-ban"></i> Override
                                                                            </span>
                                                                        @endif
                                                                    @else
                                                                        <span class="badge bg-secondary" title="Từ role mặc định">
                                                                            <i class="fas fa-tag"></i> Role
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-key fa-3x mb-3"></i>
                                <p>Chưa có quyền được cấp. Quyền mặc định từ role sẽ được áp dụng.</p>
                                @include('staff.components.button', [
                                    'type' => 'link',
                                    'variant' => 'primary',
                                    'label' => 'Quản lý quyền',
                                    'icon' => 'fas fa-cog',
                                    'url' => route('staff.users.capabilities', $targetUser->id)
                                ])
                            </div>
                        @endif
                    </div>
                </div>

                
            </div>
        </div>
    </div>
</main>

@push('styles')
<style>
.capability-category-content {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.btn.active {
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.capability-category-content.hidden {
    display: none !important;
}

.table-responsive {
    max-height: 400px;
    overflow-y: auto;
}

.table thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/tab-navigation.js') }}"></script>
<script>
// Initialize tab navigation for this page
document.addEventListener('DOMContentLoaded', function() {
    TabNavigation.init('userTabStates', ['basic-info']);
});

// Capability Category Tab Management
const capabilityCategoryStates = {};

// Initialize category states
document.addEventListener('DOMContentLoaded', function() {
    const savedStates = localStorage.getItem('capabilityCategoryStates');
    if (savedStates) {
        try {
            const parsed = JSON.parse(savedStates);
            Object.assign(capabilityCategoryStates, parsed);
        } catch (e) {
            console.error('Error loading category states:', e);
        }
    }
    
    // Initialize all categories - first category visible by default
    @if(isset($allCapabilities) && $allCapabilities->count() > 0)
        @foreach($allCapabilities as $category => $caps)
            @if($loop->first)
                capabilityCategoryStates['{{ $category }}'] = true;
            @else
                if (capabilityCategoryStates['{{ $category }}'] === undefined) {
                    capabilityCategoryStates['{{ $category }}'] = false;
                }
            @endif
        @endforeach
    @endif
    
    // Restore category states
    Object.keys(capabilityCategoryStates).forEach(category => {
        const content = document.getElementById(`category-${category}`);
        const button = document.querySelector(`button[onclick*="toggleCapabilityCategory('${category}'"]`);
        if (content && button) {
            if (capabilityCategoryStates[category]) {
                content.style.display = '';
                button.classList.add('active');
            } else {
                content.style.display = 'none';
                button.classList.remove('active');
            }
        }
    });
    
    // Save states to localStorage
    localStorage.setItem('capabilityCategoryStates', JSON.stringify(capabilityCategoryStates));
});

// Toggle capability category visibility
function toggleCapabilityCategory(category, button) {
    const content = document.getElementById(`category-${category}`);
    if (!content) return;
    
    capabilityCategoryStates[category] = !capabilityCategoryStates[category];
    
    if (capabilityCategoryStates[category]) {
        content.style.display = '';
        button.classList.add('active');
    } else {
        content.style.display = 'none';
        button.classList.remove('active');
    }
    
    // Save states to localStorage
    localStorage.setItem('capabilityCategoryStates', JSON.stringify(capabilityCategoryStates));
}

// Expand all capability categories
function expandAllCapabilityCategories() {
    @if(isset($allCapabilities) && $allCapabilities->count() > 0)
        @foreach($allCapabilities as $category => $caps)
            const content{{ $loop->index }} = document.getElementById('category-{{ $category }}');
            const button{{ $loop->index }} = document.querySelector(`button[onclick*="toggleCapabilityCategory('{{ $category }}'"]`);
            if (content{{ $loop->index }}) {
                content{{ $loop->index }}.style.display = '';
                capabilityCategoryStates['{{ $category }}'] = true;
            }
            if (button{{ $loop->index }}) {
                button{{ $loop->index }}.classList.add('active');
            }
        @endforeach
    @endif
    
    localStorage.setItem('capabilityCategoryStates', JSON.stringify(capabilityCategoryStates));
}

// Collapse all capability categories
function collapseAllCapabilityCategories() {
    @if(isset($allCapabilities) && $allCapabilities->count() > 0)
        @foreach($allCapabilities as $category => $caps)
            const content{{ $loop->index }} = document.getElementById('category-{{ $category }}');
            const button{{ $loop->index }} = document.querySelector(`button[onclick*="toggleCapabilityCategory('{{ $category }}'"]`);
            if (content{{ $loop->index }}) {
                content{{ $loop->index }}.style.display = 'none';
                capabilityCategoryStates['{{ $category }}'] = false;
            }
            if (button{{ $loop->index }}) {
                button{{ $loop->index }}.classList.remove('active');
            }
        @endforeach
    @endif
    
    localStorage.setItem('capabilityCategoryStates', JSON.stringify(capabilityCategoryStates));
}

// Update user status
window.updateStatus = function(newStatus) {
    const statusLabels = {
        1: 'Kích hoạt',
        0: 'Tạm dừng'
    };
    
    const statusLabel = statusLabels[newStatus];
    const actionLabel = newStatus ? 'kích hoạt' : 'tạm dừng';
    
    Notify.confirm({
        title: 'Xác nhận thay đổi trạng thái',
        message: `Bạn có chắc muốn ${actionLabel} người dùng này?`,
        type: newStatus ? 'warning' : 'warning',
        confirmText: 'Xác nhận',
        cancelText: 'Hủy',
        onConfirm: function() {
            // Show loading
            if (window.Preloader) {
                window.Preloader.show();
            }
            
            // Gửi request
            const formData = new FormData();
            formData.append('status', newStatus);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            fetch('{{ route("staff.users.update-status", $targetUser->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Có lỗi xảy ra');
                }
                
                if (data.success) {
                    Notify.success(data.message || `Người dùng đã được ${actionLabel} thành công!`, 'Thành công!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Notify.error('Không thể cập nhật trạng thái: ' + error.message, 'Lỗi hệ thống!');
            })
            .finally(() => {
                if (window.Preloader) {
                    window.Preloader.hide();
                }
            });
        }
    });
};

function deleteUser(id, name) {
    Notify.confirmDelete(`người dùng "${name}"`, () => {
        // Show preloader
        if (window.Preloader) {
            window.Preloader.show();
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token not found');
            Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
            if (window.Preloader) {
                window.Preloader.hide();
            }
            return;
        }

        fetch(`/staff/users/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Notify.success(data.message, 'Đã xóa!');
                setTimeout(() => {
                    window.location.href = '{{ route("staff.users.index") }}';
                }, 1000);
            } else {
                Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notify.error('Không thể xóa người dùng: ' + error.message + '. Vui lòng thử lại sau hoặc liên hệ Admin để được hỗ trợ.', 'Lỗi hệ thống!');
        })
        .finally(() => {
            if (window.Preloader) {
                window.Preloader.hide();
            }
        });
    });
}
</script>
@endpush
@endsection
