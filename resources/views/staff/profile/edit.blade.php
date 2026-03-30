@extends('layouts.staff_dashboard')

@section('title', 'Sửa thông tin cá nhân')

@section('content')
<main class="main-content">
    <div class="container-fluid">
        {{-- 1. Page Header với nút "Quay lại" và "Xem chi tiết" --}}
        @include('staff.components.index-page-header', [
            'title' => 'Sửa thông tin cá nhân',
            'subtitle' => 'Cập nhật thông tin tài khoản của bạn',
            'icon' => 'fas fa-user-edit',
            'actions' => [
                [
                    'variant' => 'secondary',      // ✅ Solid
                    'label' => 'Quay lại',
                    'icon' => 'fas fa-arrow-left',
                    'url' => route('staff.dashboard')
                ],
                [
                    'variant' => 'info',           // ✅ Solid
                    'label' => 'Xem chi tiết',
                    'icon' => 'fas fa-eye',
                    'url' => route('staff.profile.show')
                ]
            ]
        ])

        {{-- 2. Form với Layout 2 Cột --}}
        <form id="edit-profile-form" method="POST" action="{{ route('staff.profile.update') }}">
            @csrf
            @method('PUT')
            <div class="row">
                {{-- Cột trái: Form chính (col-lg-8) --}}
                <div class="col-lg-8">
                    {{-- Card 1: Thông tin cơ bản --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">
                                            Họ và tên <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control @error('full_name') is-invalid @enderror" 
                                               id="full_name" 
                                               name="full_name" 
                                               value="{{ old('full_name', $userProfile->full_name ?? '') }}" 
                                               required>
                                        @error('full_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            Email <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="email" 
                                                   class="form-control @error('email') is-invalid @enderror" 
                                                   id="email" 
                                                   name="email" 
                                                   value="{{ old('email', $user->email) }}" 
                                                   required>
                                            <button type="button" 
                                                    class="btn btn-outline-primary" 
                                                    id="btn-send-otp" 
                                                    style="display: none;">
                                                <i class="fas fa-paper-plane me-1"></i>Gửi OTP
                                            </button>
                                        </div>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div id="email-otp-section" style="display: none;" class="mt-3 p-3 bg-light rounded">
                                            <div class="mb-2">
                                                <label for="otp_code" class="form-label small">Mã OTP xác nhận</label>
                                                <div class="input-group">
                                                    <input type="text" 
                                                           class="form-control" 
                                                           id="otp_code" 
                                                           name="otp_code" 
                                                           placeholder="Nhập mã OTP 6 số"
                                                           maxlength="6"
                                                           pattern="[0-9]{6}">
                                                    <button type="button" 
                                                            class="btn btn-primary" 
                                                            id="btn-verify-otp">
                                                        <i class="fas fa-check me-1"></i>Xác nhận
                                                    </button>
                                                </div>
                                                <div class="form-text">
                                                    Mã OTP đã được gửi đến email mới. Vui lòng kiểm tra hộp thư.
                                                </div>
                                            </div>
                                            <div id="otp-status" class="small"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Số điện thoại</label>
                                        <input type="text" 
                                               class="form-control @error('phone') is-invalid @enderror" 
                                               id="phone" 
                                               name="phone" 
                                               value="{{ old('phone', $user->phone) }}">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                                        <input type="password" 
                                               class="form-control @error('current_password') is-invalid @enderror" 
                                               id="current_password" 
                                               name="current_password">
                                        <div class="form-text">Nhập mật khẩu hiện tại nếu muốn đổi mật khẩu</div>
                                        @error('current_password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Mật khẩu mới</label>
                                        <input type="password" 
                                               class="form-control @error('password') is-invalid @enderror" 
                                               id="password" 
                                               name="password">
                                        <div class="form-text">Để trống nếu không muốn thay đổi mật khẩu</div>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password_confirmation" class="form-label">Xác nhận mật khẩu mới</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="password_confirmation" 
                                               name="password_confirmation">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card 2: Thông tin KYC --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-id-card me-2"></i>Thông tin KYC
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="dob" class="form-label">Ngày sinh</label>
                                        <input type="date" 
                                               class="form-control @error('dob') is-invalid @enderror" 
                                               id="dob" 
                                               name="dob" 
                                               value="{{ old('dob', $userProfile->dob ? $userProfile->dob->format('Y-m-d') : '') }}"
                                               max="{{ date('Y-m-d', strtotime('-1 day')) }}">
                                        @error('dob')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="gender" class="form-label">Giới tính</label>
                                        <select class="form-select @error('gender') is-invalid @enderror" 
                                                id="gender" 
                                                name="gender">
                                            <option value="">-- Chọn giới tính --</option>
                                            <option value="male" {{ old('gender', $userProfile->gender) === 'male' ? 'selected' : '' }}>Nam</option>
                                            <option value="female" {{ old('gender', $userProfile->gender) === 'female' ? 'selected' : '' }}>Nữ</option>
                                            <option value="other" {{ old('gender', $userProfile->gender) === 'other' ? 'selected' : '' }}>Khác</option>
                                        </select>
                                        @error('gender')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="id_number" class="form-label">Số CMND/CCCD</label>
                                        <input type="text" 
                                               class="form-control @error('id_number') is-invalid @enderror" 
                                               id="id_number" 
                                               name="id_number" 
                                               value="{{ old('id_number', $userProfile->id_number) }}">
                                        @error('id_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="id_issued_at" class="form-label">Ngày cấp CMND/CCCD</label>
                                        <input type="date" 
                                               class="form-control @error('id_issued_at') is-invalid @enderror" 
                                               id="id_issued_at" 
                                               name="id_issued_at" 
                                               value="{{ old('id_issued_at', $userProfile->id_issued_at ? $userProfile->id_issued_at->format('Y-m-d') : '') }}"
                                               max="{{ date('Y-m-d') }}">
                                        @error('id_issued_at')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="id_card_place" class="form-label">Nơi cấp CMND/CCCD</label>
                                        <input type="text" 
                                               class="form-control @error('id_card_place') is-invalid @enderror" 
                                               id="id_card_place" 
                                               name="id_card_place" 
                                               value="{{ old('id_card_place', $userProfile->id_card_place) }}">
                                        @error('id_card_place')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tax_code" class="form-label">Mã số thuế</label>
                                        <input type="text" 
                                               class="form-control @error('tax_code') is-invalid @enderror" 
                                               id="tax_code" 
                                               name="tax_code" 
                                               value="{{ old('tax_code', $userProfile->tax_code) }}">
                                        @error('tax_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Địa chỉ thường trú</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          id="address" 
                                          name="address" 
                                          rows="2">{{ old('address', $userProfile->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="note" class="form-label">Ghi chú</label>
                                <textarea class="form-control @error('note') is-invalid @enderror" 
                                          id="note" 
                                          name="note" 
                                          rows="3">{{ old('note', $userProfile->note) }}</textarea>
                                @error('note')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Card 3: Thông tin ngân hàng --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-university me-2"></i>Thông tin ngân hàng
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sepay_bank_id" class="form-label">Ngân hàng</label>
                                        <select class="form-select @error('sepay_bank_id') is-invalid @enderror" 
                                                id="sepay_bank_id" 
                                                name="sepay_bank_id">
                                            <option value="">-- Chọn ngân hàng --</option>
                                            @foreach($banks as $bank)
                                                <option value="{{ $bank->id }}" 
                                                        {{ old('sepay_bank_id', $userProfile->sepay_bank_id) == $bank->id ? 'selected' : '' }}>
                                                    {{ $bank->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('sepay_bank_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="account_number" class="form-label">Số tài khoản</label>
                                        <input type="text" 
                                               class="form-control @error('account_number') is-invalid @enderror" 
                                               id="account_number" 
                                               name="account_number" 
                                               value="{{ old('account_number', $userProfile->account_number) }}">
                                        @error('account_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="account_holder_name" class="form-label">Tên chủ tài khoản</label>
                                        <input type="text" 
                                               class="form-control @error('account_holder_name') is-invalid @enderror" 
                                               id="account_holder_name" 
                                               name="account_holder_name" 
                                               value="{{ old('account_holder_name', $userProfile->account_holder_name) }}">
                                        @error('account_holder_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="branch_name" class="form-label">Tên chi nhánh</label>
                                        <input type="text" 
                                               class="form-control @error('branch_name') is-invalid @enderror" 
                                               id="branch_name" 
                                               name="branch_name" 
                                               value="{{ old('branch_name', $userProfile->branch_name) }}">
                                        @error('branch_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="branch_code" class="form-label">Mã chi nhánh</label>
                                        <input type="text" 
                                               class="form-control @error('branch_code') is-invalid @enderror" 
                                               id="branch_code" 
                                               name="branch_code" 
                                               value="{{ old('branch_code', $userProfile->branch_code) }}">
                                        @error('branch_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="swift_code" class="form-label">Mã SWIFT</label>
                                        <input type="text" 
                                               class="form-control @error('swift_code') is-invalid @enderror" 
                                               id="swift_code" 
                                               name="swift_code" 
                                               value="{{ old('swift_code', $userProfile->swift_code) }}">
                                        @error('swift_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="banking_notes" class="form-label">Ghi chú ngân hàng</label>
                                <textarea class="form-control @error('banking_notes') is-invalid @enderror" 
                                          id="banking_notes" 
                                          name="banking_notes" 
                                          rows="2">{{ old('banking_notes', $userProfile->banking_notes) }}</textarea>
                                @error('banking_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Cột phải: Sidebar (col-lg-4) --}}
                <div class="col-lg-4">
                    {{-- Card Thao tác (chứa action-buttons với layout dọc) --}}
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-cogs me-2"></i>Thao tác
                            </h6>
                        </div>
                        <div class="card-body">
                            @include('staff.components.action-buttons', [
                                'layout' => 'vertical',
                                'size' => 'md',
                                'actions' => [
                                    [
                                        'type' => 'submit',
                                        'variant' => 'primary',
                                        'label' => 'Cập nhật',
                                        'icon' => 'fas fa-save'
                                    ],
                                    [
                                        'type' => 'link',
                                        'variant' => 'secondary',
                                        'label' => 'Hủy',
                                        'icon' => 'fas fa-times',
                                        'url' => route('staff.profile.show')
                                    ]
                                ]
                            ])
                        </div>
                    </div>
                    
                    {{-- Card Thông tin hiện tại --}}
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Thông tin hiện tại
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Họ và tên:</label>
                                <div class="p-2 bg-light rounded">
                                    {{ $userProfile->full_name ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Email:</label>
                                <div class="p-2 bg-light rounded">
                                    {{ $user->email }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted mb-1">Số điện thoại:</label>
                                <div class="p-2 bg-light rounded">
                                    {{ $user->phone ?? 'Chưa cập nhật' }}
                                </div>
                            </div>
                            <div class="mb-0">
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
            </div>
        </form>
    </div>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-profile-form');
    if (!form) return;
    
    // Email change OTP flow
    const emailInput = document.getElementById('email');
    const btnSendOtp = document.getElementById('btn-send-otp');
    const btnVerifyOtp = document.getElementById('btn-verify-otp');
    const otpSection = document.getElementById('email-otp-section');
    const otpInput = document.getElementById('otp_code');
    const otpStatus = document.getElementById('otp-status');
    const currentEmail = '{{ $user->email }}';
    let emailVerified = false;
    
    // Check if email changed
    function checkEmailChange() {
        const newEmail = emailInput.value.trim();
        if (newEmail === currentEmail) {
            btnSendOtp.style.display = 'none';
            otpSection.style.display = 'none';
            emailVerified = true;
        } else if (newEmail && newEmail.includes('@')) {
            btnSendOtp.style.display = 'block';
            emailVerified = false;
        } else {
            btnSendOtp.style.display = 'none';
            otpSection.style.display = 'none';
            emailVerified = false;
        }
    }
    
    // Monitor email input
    emailInput.addEventListener('input', checkEmailChange);
    emailInput.addEventListener('blur', checkEmailChange);
    
    // Send OTP
    btnSendOtp.addEventListener('click', function() {
        const newEmail = emailInput.value.trim();
        
        if (!newEmail || !newEmail.includes('@')) {
            if (window.Notify) {
                Notify.error('Vui lòng nhập email hợp lệ.', 'Lỗi!');
            }
            return;
        }
        
        if (newEmail === currentEmail) {
            if (window.Notify) {
                Notify.info('Email này là email hiện tại của bạn.', 'Thông báo');
            }
            return;
        }
        
        // Disable button
        btnSendOtp.disabled = true;
        btnSendOtp.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang gửi...';
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        
        // Send request
        fetch('{{ route("staff.profile.email.send-otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email: newEmail })
        })
        .then(async response => {
            const data = await response.json();
            
            if (data.success) {
                if (window.Notify) {
                    Notify.success(data.message, 'Thành công!');
                }
                otpSection.style.display = 'block';
                otpInput.focus();
                otpStatus.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Mã OTP đã được gửi. Vui lòng kiểm tra email.</span>';
            } else {
                if (window.Notify) {
                    Notify.error(data.message || 'Không thể gửi mã OTP. Vui lòng thử lại.', 'Lỗi!');
                }
                otpStatus.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>' + (data.message || 'Lỗi gửi OTP') + '</span>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (window.Notify) {
                Notify.error('Không thể gửi mã OTP. Vui lòng thử lại.', 'Lỗi hệ thống!');
            }
            otpStatus.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Lỗi hệ thống</span>';
        })
        .finally(() => {
            btnSendOtp.disabled = false;
            btnSendOtp.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Gửi OTP';
        });
    });
    
    // Verify OTP
    btnVerifyOtp.addEventListener('click', function() {
        const newEmail = emailInput.value.trim();
        const otpCode = otpInput.value.trim();
        
        if (!otpCode || otpCode.length !== 6) {
            if (window.Notify) {
                Notify.error('Vui lòng nhập mã OTP 6 số.', 'Lỗi!');
            }
            otpInput.focus();
            return;
        }
        
        // Disable button
        btnVerifyOtp.disabled = true;
        btnVerifyOtp.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang xác thực...';
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        
        // Send request
        fetch('{{ route("staff.profile.email.verify-otp") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken ? csrfToken.getAttribute('content') : '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                email: newEmail,
                otp_code: otpCode 
            })
        })
        .then(async response => {
            const data = await response.json();
            
            if (data.success) {
                if (window.Notify) {
                    Notify.success(data.message, 'Thành công!');
                }
                emailVerified = true;
                otpStatus.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Email đã được xác thực. Bạn có thể cập nhật thông tin ngay bây giờ.</span>';
                otpInput.disabled = true;
                btnVerifyOtp.disabled = true;
            } else {
                if (window.Notify) {
                    Notify.error(data.message || 'Mã OTP không hợp lệ.', 'Lỗi!');
                }
                otpStatus.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>' + (data.message || 'Mã OTP không hợp lệ') + '</span>';
                otpInput.focus();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (window.Notify) {
                Notify.error('Không thể xác thực OTP. Vui lòng thử lại.', 'Lỗi hệ thống!');
            }
            otpStatus.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Lỗi hệ thống</span>';
        })
        .finally(() => {
            btnVerifyOtp.disabled = false;
            btnVerifyOtp.innerHTML = '<i class="fas fa-check me-1"></i>Xác nhận';
        });
    });
    
    // Allow Enter key to verify OTP
    otpInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            btnVerifyOtp.click();
        }
    });
    
    // Form submit handler
    form.addEventListener('submit', function(e) {
        // Check if email changed and not verified
        const newEmail = emailInput.value.trim();
        if (newEmail !== currentEmail && !emailVerified) {
            e.preventDefault();
            if (window.Notify) {
                Notify.error('Vui lòng xác thực email mới bằng OTP trước khi cập nhật.', 'Cần xác thực email!');
            }
            otpSection.style.display = 'block';
            if (!otpInput.value) {
                otpInput.focus();
            }
            return;
        }
        e.preventDefault();
        
        // Show loading
        if (window.Preloader) {
            window.Preloader.show();
        }
        
        // Disable submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...';
        }
        
        // Get form data
        const formData = new FormData(form);
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token not found');
            if (window.Notify) {
                Notify.error('Lỗi bảo mật: Không tìm thấy CSRF token. Vui lòng tải lại trang và thử lại.', 'Lỗi bảo mật!');
            }
            if (window.Preloader) {
                window.Preloader.hide();
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Cập nhật';
            }
            return;
        }
        
        // Send request (use POST method with _method=PUT in FormData)
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            let data;
            try {
                const text = await response.text();
                data = text ? JSON.parse(text) : {};
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                data = { 
                    success: false,
                    message: 'Không thể phân tích phản hồi từ server' 
                };
            }
            
            if (!response.ok) {
                // Handle validation errors
                if (response.status === 422 && data.errors) {
                    // Clear previous errors
                    form.querySelectorAll('.is-invalid').forEach(el => {
                        el.classList.remove('is-invalid');
                    });
                    form.querySelectorAll('.invalid-feedback').forEach(el => {
                        el.textContent = '';
                    });
                    
                    // Show new errors
                    Object.keys(data.errors).forEach(field => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('is-invalid');
                            const feedback = input.parentElement.querySelector('.invalid-feedback');
                            if (feedback) {
                                feedback.textContent = Array.isArray(data.errors[field]) 
                                    ? data.errors[field][0] 
                                    : data.errors[field];
                            } else {
                                // Create feedback element if it doesn't exist
                                const feedbackDiv = document.createElement('div');
                                feedbackDiv.className = 'invalid-feedback';
                                feedbackDiv.textContent = Array.isArray(data.errors[field]) 
                                    ? data.errors[field][0] 
                                    : data.errors[field];
                                input.parentElement.appendChild(feedbackDiv);
                            }
                        }
                    });
                    
                    if (window.Notify) {
                        Notify.error(data.message || 'Vui lòng kiểm tra lại thông tin đã nhập.', 'Lỗi xác thực!');
                    }
                } else {
                    if (window.Notify) {
                        Notify.error(data.message || 'Có lỗi xảy ra khi cập nhật thông tin.', 'Lỗi!');
                    }
                }
                return;
            }
            
            if (data.success) {
                if (window.Notify) {
                    Notify.success(data.message, 'Thành công!');
                }
                
                // Redirect after delay
                setTimeout(() => {
                    window.location.href = data.redirect || '{{ route("staff.profile.show") }}';
                }, 1500);
            } else {
                if (window.Notify) {
                    Notify.error(data.message || 'Có lỗi xảy ra', 'Lỗi!');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (window.Notify) {
                Notify.error('Không thể cập nhật thông tin. Vui lòng thử lại.', 'Lỗi hệ thống!');
            }
        })
        .finally(() => {
            // Hide loading
            if (window.Preloader) {
                window.Preloader.hide();
            }
            
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Cập nhật';
            }
        });
    });
});
</script>
@endpush
@endsection

