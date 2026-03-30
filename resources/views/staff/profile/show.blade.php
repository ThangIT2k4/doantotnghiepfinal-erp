@extends('layouts.staff_dashboard')

@section('title', 'Hồ sơ cá nhân')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với breadcrumbs --}}
        @include('staff.components.show-page-header', [
            'title' => 'Hồ sơ cá nhân',
            'subtitle' => 'Thông tin chi tiết về tài khoản của bạn',
            'icon' => 'fas fa-user',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('staff.dashboard')],
                ['label' => ($user->userProfile->full_name ?? null) ?: $user->email, 'active' => true]
            ]
        ])

        {{-- 2. Content --}}
        <div class="row">
            {{-- Nội dung chính --}}
            <div class="col-lg-8">
                {{-- Card: Thông tin cơ bản --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Họ và tên:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-user me-2 text-muted"></i>
                                    {{ $user->userProfile?->full_name ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Email:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-envelope me-2 text-muted"></i>
                                    <a href="mailto:{{ $user->email }}" class="text-decoration-none">{{ $user->email }}</a>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Số điện thoại:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-phone me-2 text-muted"></i>
                                    @if($user->phone)
                                        <a href="tel:{{ $user->phone }}" class="text-decoration-none">{{ $user->phone }}</a>
                                    @else
                                        <span class="text-muted">Chưa cập nhật</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Trạng thái:</label>
                                <div class="p-2 bg-light rounded">
                                    @if($user->status)
                                        <span class="badge bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge bg-warning">Tạm ngưng</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card: Thông tin KYC --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-id-card me-2"></i>Thông tin KYC
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Ngày sinh:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-calendar me-2 text-muted"></i>
                                    {{ $userProfile?->formatted_dob ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Giới tính:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-venus-mars me-2 text-muted"></i>
                                    {{ $userProfile?->gender_text ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Số CMND/CCCD:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-id-card me-2 text-muted"></i>
                                    {{ $userProfile?->id_number ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Ngày cấp:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-calendar-check me-2 text-muted"></i>
                                    {{ $userProfile?->formatted_id_issued_at ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Nơi cấp:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    {{ $userProfile?->id_card_place ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Mã số thuế:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-file-invoice me-2 text-muted"></i>
                                    {{ $userProfile?->tax_code ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Địa chỉ thường trú:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-home me-2 text-muted"></i>
                                    {{ $userProfile?->address ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            @if($userProfile?->note)
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Ghi chú:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-sticky-note me-2 text-muted"></i>
                                    {{ $userProfile?->note }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card: Thông tin ngân hàng --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-university me-2"></i>Thông tin ngân hàng
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Ngân hàng:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-building me-2 text-muted"></i>
                                    {{ $sepayBank->name ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Số tài khoản:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-credit-card me-2 text-muted"></i>
                                    {{ $userProfile?->account_number ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Tên chủ tài khoản:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-user-tie me-2 text-muted"></i>
                                    {{ $userProfile?->account_holder_name ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Chi nhánh:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                    {{ $userProfile?->branch_name ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Mã chi nhánh:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-code me-2 text-muted"></i>
                                    {{ $userProfile?->branch_code ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Mã SWIFT:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-globe me-2 text-muted"></i>
                                    {{ $userProfile?->swift_code ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            @if($userProfile?->banking_notes)
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Ghi chú ngân hàng:</label>
                                <div class="p-2 bg-light rounded">
                                    <i class="fas fa-sticky-note me-2 text-muted"></i>
                                    {{ $userProfile?->banking_notes }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Card "Thao tác" bên phải --}}
            <div class="col-lg-4">
                {{-- Profile Card --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            @if($user->avatar)
                                <img src="{{ asset('storage/' . $user->avatar) }}" 
                                     alt="Avatar" 
                                     class="rounded-circle" 
                                     style="width: 120px; height: 120px; object-fit: cover;">
                            @else
                                <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center" 
                                     style="width: 120px; height: 120px;">
                                    <i class="fas fa-user fa-3x text-white"></i>
                                </div>
                            @endif
                        </div>
                        <h4 class="mb-2">{{ ($user->userProfile?->full_name) ?: $user->email }}</h4>
                        <p class="text-muted mb-2">{{ $user->email }}</p>
                        @if($user->phone)
                            <p class="text-muted mb-3">
                                <i class="fas fa-phone me-1"></i>{{ $user->phone }}
                            </p>
                        @endif
                        
                        {{-- Status Badge --}}
                        <div class="mb-3">
                            @if($user->status)
                                <span class="badge bg-success fs-6">Hoạt động</span>
                            @else
                                <span class="badge bg-warning fs-6">Tạm ngưng</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card "Thao tác" --}}
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>Thao tác
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            {{-- Nút Sửa --}}
                            <a href="{{ route('staff.profile.edit') }}" 
                               class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-edit me-1"></i>Sửa thông tin
                            </a>
                            
                            {{-- Nút Quay lại --}}
                            <a href="{{ route('staff.dashboard') }}" 
                               class="btn btn-secondary btn-sm w-100">
                                <i class="fas fa-arrow-left me-1"></i>Quay lại Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

