@extends('layouts.app')

@section('title', 'Chỉnh sửa hồ sơ')

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/profile-edit.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
/* Ensure header text is white on edit page */
.page-header-blue .page-title,
.page-header-blue .page-subtitle,
.page-header-blue .header-icon i {
    color: #FFFFFF !important;
}

/* Override any gradient or other color styles */
.page-header-blue .page-title {
    background: none !important;
    -webkit-background-clip: unset !important;
    -webkit-text-fill-color: #FFFFFF !important;
    background-clip: unset !important;
    color: #FFFFFF !important;
}

.page-header-blue .page-subtitle {
    color: #FFFFFF !important;
}
/* Form Container with White Background */
.form-container-blue {
    background: #ffffff;
    min-height: 100vh;
    padding: 2rem 0;
}

/* Modern Card with Blue Theme */
.modern-card-blue {
    background: white;
    border-radius: 16px;
    padding: 0;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--blue-border);
    overflow: hidden;
}

.card-header-modern-blue {
    padding: 1.5rem;
    background: var(--blue-bg-light);
    border-bottom: 1px solid var(--blue-border);
    display: flex;
    align-items: center;
}

.card-header-modern-blue h5 {
    color: var(--blue-primary);
    font-weight: 700;
    margin: 0;
}

.card-body-modern-blue {
    padding: 1.5rem;
}

/* Form Grid - Improved for Banking Info */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label-modern {
    font-weight: 600;
    color: var(--blue-primary);
    font-size: 0.9rem;
    display: flex;
    align-items: center;
}

.form-control-modern {
    border: 2px solid var(--blue-border);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: var(--blue-bg-light);
}

.form-control-modern:focus {
    outline: none;
    border-color: var(--blue-primary);
    background: white;
    box-shadow: 0 0 0 0.2rem rgba(39, 102, 236, 0.25);
}

/* Alert Modern Blue */
.alert-modern-blue {
    border-radius: 12px;
    border: 1px solid var(--blue-border);
    padding: 1rem;
    background: var(--blue-bg-light);
}

.alert-modern-blue .alert-content {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    flex-wrap: wrap;
}

/* Profile Sidebar */
.profile-edit-sidebar-blue {
    position: sticky;
    top: 2rem;
}

.profile-avatar {
    position: relative;
    display: inline-block;
    margin-bottom: 1rem;
}

.avatar-img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid var(--blue-primary);
    object-fit: cover;
}

.avatar-status {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 3px solid white;
}

.avatar-status.online {
    background: var(--status-active);
}

.profile-name {
    color: var(--blue-primary);
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.profile-email {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

.help-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.help-item {
    display: flex;
    align-items: center;
    font-size: 0.9rem;
    color: #666;
}

/* Responsive */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>
@endpush

@section('content')
<div class="form-container-blue">
    <div class="container py-4">
        <!-- Page Header -->
        @include('tenant.components.page-header', [
            'title' => 'Chỉnh sửa hồ sơ cá nhân',
            'subtitle' => 'Cập nhật thông tin tài khoản và bảo mật',
            'icon' => 'fas fa-user-edit',
            'actions' => [
                [
                    'label' => 'Quay lại',
                    'url' => route('tenant.profile'),
                    'icon' => 'fas fa-arrow-left',
                    'variant' => 'outline-secondary'
                ],
                [
                    'label' => 'Về Dashboard',
                    'url' => route('tenant.dashboard'),
                    'icon' => 'fas fa-tachometer-alt',
                    'variant' => 'outline-secondary'
                ]
            ]
        ])

        <!-- Notifications -->
        @if($errors->any())
            <div class="alert alert-danger alert-modern-blue alert-dismissible fade show" role="alert">
                <div class="alert-content">
                    <i class="fas fa-exclamation-circle me-3"></i>
                    <div>
                        <strong>Có lỗi xảy ra:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <form method="POST" action="{{ route('tenant.profile.update') }}" id="profileEditForm">
                    @csrf
                    @method('PUT')

                    <!-- Basic Information -->
                    <div class="modern-card-blue mb-4">
                        <div class="card-header-modern-blue">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user me-3"></i>
                                <h5 class="mb-0">Thông tin cơ bản</h5>
                            </div>
                        </div>
                        <div class="card-body-modern-blue">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="full_name" class="form-label-modern">
                                        <i class="fas fa-user me-2"></i>Họ và tên <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control-modern @error('full_name') is-invalid @enderror" 
                                           id="full_name" 
                                           name="full_name" 
                                           value="{{ old('full_name', $user->full_name) }}" 
                                           required>
                                    @error('full_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone" class="form-label-modern">
                                        <i class="fas fa-phone me-2"></i>Số điện thoại
                                    </label>
                                    <input type="text" 
                                           class="form-control-modern @error('phone') is-invalid @enderror" 
                                           id="phone" 
                                           name="phone" 
                                           value="{{ old('phone', $user->phone) }}"
                                           placeholder="Nhập số điện thoại">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="email" class="form-label-modern">
                                        <i class="fas fa-envelope me-2"></i>Email <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="email" 
                                               class="form-control-modern @error('email') is-invalid @enderror" 
                                               id="email" 
                                               name="email" 
                                               value="{{ old('email', $user->email) }}" 
                                               required
                                               onchange="checkEmailChange()">
                                        <button type="button" 
                                                class="btn btn-outline-primary btn-sm" 
                                                id="verifyEmailBtn" 
                                                onclick="sendOtpForEmail()"
                                                style="display: none;">
                                            <i class="fas fa-shield-alt me-1"></i>Xác thực
                                        </button>
                                    </div>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div id="emailVerificationHelp" class="form-text" style="display: none;">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Email đã thay đổi. Vui lòng xác thực email mới trước khi lưu.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KYC Information -->
                    <div class="modern-card-blue mb-4">
                        <div class="card-header-modern-blue">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-id-card me-3"></i>
                                <h5 class="mb-0">Thông tin KYC (Know Your Customer)</h5>
                            </div>
                        </div>
                        <div class="card-body-modern-blue">
                            <div class="alert alert-info alert-modern-blue mb-4">
                                <div class="alert-content">
                                    <i class="fas fa-info-circle me-3"></i>
                                    <span>Thông tin KYC giúp xác thực danh tính và tăng độ tin cậy cho tài khoản của bạn.</span>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="dob" class="form-label-modern">
                                        <i class="fas fa-birthday-cake me-2"></i>Ngày sinh
                                    </label>
                                    <input type="date" 
                                           class="form-control-modern @error('dob') is-invalid @enderror" 
                                           id="dob" 
                                           name="dob" 
                                           value="{{ old('dob', $userProfile?->dob?->format('Y-m-d')) }}"
                                           max="{{ date('Y-m-d', strtotime('-1 day')) }}">
                                    @error('dob')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group">
                                    <label for="gender" class="form-label-modern">
                                        <i class="fas fa-venus-mars me-2"></i>Giới tính
                                    </label>
                                    <select class="form-control-modern @error('gender') is-invalid @enderror" 
                                            id="gender" 
                                            name="gender">
                                        <option value="">Chọn giới tính</option>
                                        <option value="male" {{ old('gender', $userProfile?->gender) == 'male' ? 'selected' : '' }}>Nam</option>
                                        <option value="female" {{ old('gender', $userProfile?->gender) == 'female' ? 'selected' : '' }}>Nữ</option>
                                        <option value="other" {{ old('gender', $userProfile?->gender) == 'other' ? 'selected' : '' }}>Khác</option>
                                    </select>
                                    @error('gender')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group">
                                    <label for="id_number" class="form-label-modern">
                                        <i class="fas fa-id-card me-2"></i>Số CMND/CCCD
                                    </label>
                                    <input type="text" 
                                           class="form-control-modern @error('id_number') is-invalid @enderror" 
                                           id="id_number" 
                                           name="id_number" 
                                           value="{{ old('id_number', $userProfile?->id_number) }}"
                                           placeholder="Nhập số CMND/CCCD">
                                    @error('id_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group">
                                    <label for="id_issued_at" class="form-label-modern">
                                        <i class="fas fa-calendar-alt me-2"></i>Ngày cấp CMND/CCCD
                                    </label>
                                    <input type="date" 
                                           class="form-control-modern @error('id_issued_at') is-invalid @enderror" 
                                           id="id_issued_at" 
                                           name="id_issued_at" 
                                           value="{{ old('id_issued_at', $userProfile?->id_issued_at?->format('Y-m-d')) }}"
                                           max="{{ date('Y-m-d') }}">
                                    @error('id_issued_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="address" class="form-label-modern">
                                        <i class="fas fa-map-marker-alt me-2"></i>Địa chỉ thường trú
                                    </label>
                                    <textarea class="form-control-modern @error('address') is-invalid @enderror" 
                                              id="address" 
                                              name="address" 
                                              rows="3"
                                              placeholder="Nhập địa chỉ thường trú">{{ old('address', $userProfile?->address) }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="note" class="form-label-modern">
                                        <i class="fas fa-sticky-note me-2"></i>Ghi chú
                                    </label>
                                    <textarea class="form-control-modern @error('note') is-invalid @enderror" 
                                              id="note" 
                                              name="note" 
                                              rows="2"
                                              placeholder="Ghi chú thêm (tùy chọn)">{{ old('note', $userProfile?->note) }}</textarea>
                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Banking Information -->
                    <div class="modern-card-blue mb-4">
                        <div class="card-header-modern-blue">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-university me-3"></i>
                                <h5 class="mb-0">Thông tin ngân hàng</h5>
                            </div>
                        </div>
                        <div class="card-body-modern-blue">
                            <div class="alert alert-info alert-modern-blue mb-4">
                                <div class="alert-content">
                                    <i class="fas fa-info-circle me-3"></i>
                                    <span>Thông tin ngân hàng giúp bạn nhận thanh toán qua SePay.</span>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="sepay_bank_id" class="form-label-modern">
                                        <i class="fas fa-university me-2"></i>Ngân hàng
                                    </label>
                                    <select class="form-control-modern @error('sepay_bank_id') is-invalid @enderror" 
                                            id="sepay_bank_id" 
                                            name="sepay_bank_id">
                                        <option value="">Chọn ngân hàng</option>
                                        @foreach($sepayBanks as $bank)
                                            <option value="{{ $bank->id }}" 
                                                    {{ old('sepay_bank_id', $userProfile?->sepay_bank_id ?? $user->sepay_bank_id) == $bank->id ? 'selected' : '' }}>
                                                {{ $bank->name }} ({{ $bank->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sepay_bank_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="account_number" class="form-label-modern">
                                        <i class="fas fa-credit-card me-2"></i>Số tài khoản
                                    </label>
                                    <input type="text" 
                                           class="form-control-modern @error('account_number') is-invalid @enderror" 
                                           id="account_number" 
                                           name="account_number" 
                                           value="{{ old('account_number', $userProfile?->account_number ?? $user->account_number) }}"
                                           placeholder="Nhập số tài khoản">
                                    @error('account_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="account_holder_name" class="form-label-modern">
                                        <i class="fas fa-user-tie me-2"></i>Tên chủ tài khoản
                                    </label>
                                    <input type="text" 
                                           class="form-control-modern @error('account_holder_name') is-invalid @enderror" 
                                           id="account_holder_name" 
                                           name="account_holder_name" 
                                           value="{{ old('account_holder_name', $userProfile?->account_holder_name ?? $user->account_holder_name) }}"
                                           placeholder="Nhập tên chủ tài khoản">
                                    @error('account_holder_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="branch_name" class="form-label-modern">
                                        <i class="fas fa-building me-2"></i>Tên chi nhánh
                                    </label>
                                    <input type="text" 
                                           class="form-control-modern @error('branch_name') is-invalid @enderror" 
                                           id="branch_name" 
                                           name="branch_name" 
                                           value="{{ old('branch_name', $userProfile?->branch_name ?? $user->branch_name) }}"
                                           placeholder="Tên chi nhánh">
                                    @error('branch_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="branch_code" class="form-label-modern">
                                        <i class="fas fa-code me-2"></i>Mã chi nhánh
                                    </label>
                                    <input type="text" 
                                           class="form-control-modern @error('branch_code') is-invalid @enderror" 
                                           id="branch_code" 
                                           name="branch_code" 
                                           value="{{ old('branch_code', $userProfile?->branch_code ?? $user->branch_code) }}"
                                           placeholder="Mã chi nhánh">
                                    @error('branch_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="swift_code" class="form-label-modern">
                                        <i class="fas fa-globe me-2"></i>Mã SWIFT
                                    </label>
                                    <input type="text" 
                                           class="form-control-modern @error('swift_code') is-invalid @enderror" 
                                           id="swift_code" 
                                           name="swift_code" 
                                           value="{{ old('swift_code', $userProfile?->swift_code ?? $user->swift_code) }}"
                                           placeholder="Mã SWIFT">
                                    @error('swift_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="tax_code" class="form-label-modern">
                                        <i class="fas fa-file-invoice me-2"></i>Mã số thuế
                                    </label>
                                    <input type="text" 
                                           class="form-control-modern @error('tax_code') is-invalid @enderror" 
                                           id="tax_code" 
                                           name="tax_code" 
                                           value="{{ old('tax_code', $userProfile?->tax_code ?? $user->tax_code) }}"
                                           placeholder="Nhập mã số thuế">
                                    @error('tax_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group full-width">
                                    <label for="banking_notes" class="form-label-modern">
                                        <i class="fas fa-sticky-note me-2"></i>Ghi chú ngân hàng
                                    </label>
                                    <textarea class="form-control-modern @error('banking_notes') is-invalid @enderror" 
                                              id="banking_notes" 
                                              name="banking_notes" 
                                              rows="3"
                                              placeholder="Ghi chú về thông tin ngân hàng">{{ old('banking_notes', $userProfile?->banking_notes ?? $user->banking_notes) }}</textarea>
                                    @error('banking_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Password Change -->
                    <div class="modern-card-blue mb-4">
                        <div class="card-header-modern-blue">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-lock me-3"></i>
                                <h5 class="mb-0">Thay đổi mật khẩu</h5>
                            </div>
                        </div>
                        <div class="card-body-modern-blue">
                            <div class="alert alert-info alert-modern-blue mb-4">
                                <div class="alert-content">
                                    <i class="fas fa-info-circle me-3"></i>
                                    <span>Để thay đổi mật khẩu, vui lòng nhập mật khẩu hiện tại và mật khẩu mới.</span>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="current_password" class="form-label-modern">
                                        <i class="fas fa-key me-2"></i>Mật khẩu hiện tại
                                    </label>
                                    <input type="password" 
                                           class="form-control-modern @error('current_password') is-invalid @enderror" 
                                           id="current_password" 
                                           name="current_password"
                                           placeholder="Nhập mật khẩu hiện tại">
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group">
                                    <label for="password" class="form-label-modern">
                                        <i class="fas fa-lock me-2"></i>Mật khẩu mới
                                    </label>
                                    <input type="password" 
                                           class="form-control-modern @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password"
                                           placeholder="Nhập mật khẩu mới">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="password_confirmation" class="form-label-modern">
                                        <i class="fas fa-check-circle me-2"></i>Xác nhận mật khẩu mới
                                    </label>
                                    <input type="password" 
                                           class="form-control-modern" 
                                           id="password_confirmation" 
                                           name="password_confirmation"
                                           placeholder="Nhập lại mật khẩu mới">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="modern-card-blue">
                        <div class="card-body-modern-blue">
                            <div class="form-actions">
                                <a href="{{ route('tenant.profile') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Hủy
                                </a>
                                <button type="submit" class="btn btn-primary-blue">
                                    <i class="fas fa-save me-2"></i>Cập nhật thông tin
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="modern-card-blue profile-edit-sidebar-blue">
                    <div class="card-body-modern-blue text-center">
                        <div class="profile-avatar">
                            <img class="avatar-img" 
                                 src="https://ui-avatars.com/api/?name={{ urlencode($user->full_name ?? 'User') }}&background=2766ec&color=fff&size=120" 
                                 alt="avatar">
                            <div class="avatar-status online"></div>
                        </div>
                        <h5 class="profile-name">{{ $user->full_name ?? 'User' }}</h5>
                        <div class="profile-email">{{ $user->email ?? 'user@example.com' }}</div>

                        <div class="profile-actions">
                            <a href="{{ route('tenant.dashboard') }}" class="btn btn-primary-blue w-100 mb-3">
                                <i class="fas fa-tachometer-alt me-2"></i>Vào Dashboard
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100 logout-btn">
                                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Help Card -->
                <div class="modern-card-blue mt-4">
                    <div class="card-header-modern-blue">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-question-circle me-3"></i>
                            <h6 class="mb-0">Hướng dẫn</h6>
                        </div>
                    </div>
                    <div class="card-body-modern-blue">
                        <div class="help-list">
                            <div class="help-item">
                                <i class="fas fa-check text-success me-2"></i>
                                <span>Cập nhật thông tin cơ bản</span>
                            </div>
                            <div class="help-item">
                                <i class="fas fa-check text-success me-2"></i>
                                <span>Thay đổi mật khẩu (tùy chọn)</span>
                            </div>
                            <div class="help-item">
                                <i class="fas fa-check text-success me-2"></i>
                                <span>Email phải là duy nhất</span>
                            </div>
                            <div class="help-item">
                                <i class="fas fa-check text-success me-2"></i>
                                <span>Mật khẩu tối thiểu 8 ký tự</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('assets/js/user/profile-edit.js') }}?v={{ time() }}"></script>
<script>
// Store original email for comparison
const originalEmail = '{{ $user->email }}';

function checkEmailChange() {
    const emailInput = document.getElementById('email');
    const verifyBtn = document.getElementById('verifyEmailBtn');
    const helpText = document.getElementById('emailVerificationHelp');
    
    if (emailInput.value !== originalEmail) {
        verifyBtn.style.display = 'inline-block';
        helpText.style.display = 'block';
    } else {
        verifyBtn.style.display = 'none';
        helpText.style.display = 'none';
    }
}

function sendOtpForEmail() {
    const emailInput = document.getElementById('email');
    const verifyBtn = document.getElementById('verifyEmailBtn');
    const email = emailInput.value;
    
    if (!email || email === originalEmail) {
        showNotification('error', 'Vui lòng nhập email mới khác với email hiện tại.');
        return;
    }
    
    // Show loading state
    verifyBtn.disabled = true;
    verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang gửi...';
    
    // Send OTP request
    fetch('{{ route("tenant.profile.email.send-verification") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            email: email
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            // Store email in sessionStorage for OTP verification page
            sessionStorage.setItem('pendingEmailVerification', email);
            // Redirect to OTP verification page
            setTimeout(() => {
                window.location.href = '{{ route("tenant.profile.otp-verification") }}';
            }, 2000);
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra khi gửi mã OTP. Vui lòng thử lại.');
    })
    .finally(() => {
        // Reset button state
        verifyBtn.disabled = false;
        verifyBtn.innerHTML = '<i class="fas fa-shield-alt me-1"></i>Xác thực';
    });
}

function showNotification(type, message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

// Check email verification status on page load
document.addEventListener('DOMContentLoaded', function() {
    checkEmailChange();
});
</script>
@endpush
@endsection
