@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân')

@include('tenant.components.theme-config')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/profile.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/css/tenant/form-blue-theme.css') }}?v={{ time() }}">
<style>
/* Profile Container with Blue Theme */
.profile-container-blue {
    background: linear-gradient(135deg, #E8F0FE 0%, #D6E4FF 50%, #C8DBFF 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

/* Modern Card with Blue Theme */
.modern-card-blue {
    background: white;
    border-radius: 16px;
    padding: 0;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(39, 102, 236, 0.15);
    border: 2px solid var(--blue-primary);
    overflow: hidden;
    transition: all 0.3s ease;
}

.modern-card-blue:hover {
    box-shadow: 0 8px 30px rgba(39, 102, 236, 0.25);
    transform: translateY(-2px);
}

.card-header-modern-blue {
    padding: 1.5rem;
    background: linear-gradient(135deg, var(--blue-primary) 0%, var(--blue-light) 100%);
    border-bottom: 2px solid var(--blue-primary);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header-modern-blue i {
    color: white;
}

/* Icon colors in info labels */
.info-label i {
    color: var(--blue-primary, #2766ec);
}

/* Alert icons */
.alert-modern-blue .alert-content i {
    color: var(--blue-primary, #2766ec);
}

/* Transaction section icons */
h6 i {
    color: var(--blue-primary, #2766ec);
}

.card-header-modern-blue h5 {
    color: white;
    font-weight: 700;
    margin: 0;
}

.card-body-modern-blue {
    padding: 1.5rem;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-label {
    font-weight: 600;
    color: var(--blue-primary, #2766ec);
    font-size: 0.9rem;
    display: flex;
    align-items: center;
}

.info-label i {
    color: var(--blue-primary, #2766ec);
}

.info-value {
    color: var(--blue-primary, #2766ec);
    font-size: 1rem;
    font-weight: 600;
    word-break: break-word;
}

/* KYC Progress - Improved Design */
.kyc-progress {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.75rem;
    min-width: 120px;
}

.kyc-progress-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background: var(--blue-bg-light, #F0F4FF);
    border-radius: 16px;
    border: 2px solid var(--blue-border, #D6E4FF);
}

.progress-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1rem;
    border: 4px solid;
    position: relative;
    background: white;
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.2);
    transition: all 0.3s ease;
}

.progress-circle.incomplete::before {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 4px solid transparent;
    border-top-color: currentColor;
    animation: spin 2s linear infinite;
    opacity: 0.3;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.progress-circle.complete {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-color: #059669;
    color: white;
    box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);
}

.progress-circle.incomplete {
    background: linear-gradient(135deg, var(--blue-primary, #2766ec) 0%, var(--blue-dark, #1E4FC8) 100%);
    border-color: var(--blue-primary, #2766ec);
    color: white;
    box-shadow: 0 4px 20px rgba(39, 102, 236, 0.4);
}

.progress-text {
    position: relative;
    z-index: 1;
    font-size: 1.2rem;
}

.progress-label {
    font-size: 0.9rem;
    color: var(--blue-primary, #2766ec);
    font-weight: 700;
    text-align: center;
    padding: 0.5rem 1rem;
    background: white;
    border-radius: 20px;
    border: 2px solid var(--blue-border, #D6E4FF);
    white-space: nowrap;
}

/* Profile Sidebar */
.profile-sidebar-blue {
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
    border: 4px solid var(--blue-primary, #2766ec);
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
    color: var(--blue-primary, #2766ec);
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.profile-email {
    color: var(--blue-primary, #2766ec);
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
}

.profile-stats {
    margin: 1.5rem 0;
    padding: 1.5rem;
    background: var(--blue-gradient, linear-gradient(135deg, #1E4FC8 0%, #2766ec 50%, #4A85F0 100%));
    border-radius: 12px;
    border: 2px solid var(--blue-primary, #2766ec);
}

.profile-stats .stat-label {
    color: rgba(255, 255, 255, 0.9);
}

.profile-stats .stat-number {
    color: white;
}

.stat-item {
    text-align: center;
}

/* Alert Modern Blue */
.alert-modern-blue {
    border-radius: 12px;
    border: 2px solid var(--blue-primary);
    padding: 1rem;
    background: var(--blue-bg-light);
}

.alert-modern-blue .alert-content {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

/* Table Styling */
.table thead th {
    background: var(--blue-bg-light, #F0F4FF);
    color: var(--blue-primary, #2766ec);
    font-weight: 700;
    border-bottom: 2px solid var(--blue-primary, #2766ec);
}

.table tbody tr:hover {
    background: var(--blue-bg-light, #F0F4FF);
}

.table tbody td {
    color: #333;
}

/* Security Actions */
.security-actions .btn {
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.security-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(39, 102, 236, 0.3);
}

/* Page Header - White text */
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

/* Responsive */
@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header-modern-blue {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>
@endpush

@section('content')
<div class="page-container-blue">
    <div class="container">
        <!-- Page Header -->
        @include('tenant.components.page-header', [
            'title' => 'Hồ sơ cá nhân',
            'subtitle' => 'Quản lý thông tin tài khoản và bảo mật',
            'icon' => 'fas fa-user-circle',
            'actions' => [
                [
                    'label' => 'Chỉnh sửa',
                    'url' => route('tenant.profile.edit'),
                    'icon' => 'fas fa-edit',
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
        @if(session('success'))
            <div class="alert alert-success alert-modern-blue alert-dismissible fade show" role="alert">
                <div class="alert-content">
                    <i class="fas fa-check-circle me-3"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

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
                <!-- Account Information Card -->
                <div class="modern-card-blue mb-4">
                    <div class="card-header-modern-blue">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user me-3"></i>
                            <h5 class="mb-0">Thông tin tài khoản</h5>
                        </div>
                    </div>
                    <div class="card-body-modern-blue">
                        <div class="info-grid">
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-user me-2"></i>Họ và tên
                                </label>
                                <div class="info-value">{{ auth()->user()?->full_name ?? 'Chưa cập nhật' }}</div>
                            </div>
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                                <div class="info-value">{{ auth()->user()?->email ?? 'Chưa cập nhật' }}</div>
                            </div>
                            <div class="info-item">
                                <label class="info-label">
                                    <i class="fas fa-phone me-2"></i>Số điện thoại
                                </label>
                                <div class="info-value">{{ auth()->user()?->phone ?? 'Chưa cập nhật' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KYC Information -->
                @if($userProfile)
                    <div class="modern-card-blue mb-4">
                        <div class="card-header-modern-blue">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-id-card me-3"></i>
                                    <h5 class="mb-0">Thông tin KYC (Know Your Customer)</h5>
                                </div>
                                <div class="kyc-progress">
                                    <div class="kyc-progress-wrapper">
                                        <div class="progress-circle {{ $userProfile->isKycComplete() ? 'complete' : 'incomplete' }}">
                                            <span class="progress-text">{{ $userProfile->getKycCompletionPercentage() }}%</span>
                                        </div>
                                        <span class="progress-label">{{ $userProfile->isKycComplete() ? 'Hoàn thành' : 'Chưa hoàn thành' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body-modern-blue">
                            <div class="info-grid">
                                <div class="info-item">
                                    <label class="info-label">
                                        <i class="fas fa-birthday-cake me-2"></i>Ngày sinh
                                    </label>
                                    <div class="info-value">{{ $userProfile->formatted_dob ?? 'Chưa cập nhật' }}</div>
                                </div>
                                <div class="info-item">
                                    <label class="info-label">
                                        <i class="fas fa-venus-mars me-2"></i>Giới tính
                                    </label>
                                    <div class="info-value">{{ $userProfile->gender_text ?? 'Chưa cập nhật' }}</div>
                                </div>
                                <div class="info-item">
                                    <label class="info-label">
                                        <i class="fas fa-id-card me-2"></i>Số CMND/CCCD
                                    </label>
                                    <div class="info-value">{{ $userProfile->id_number ?? 'Chưa cập nhật' }}</div>
                                </div>
                                <div class="info-item">
                                    <label class="info-label">
                                        <i class="fas fa-calendar-alt me-2"></i>Ngày cấp CMND/CCCD
                                    </label>
                                    <div class="info-value">{{ $userProfile->formatted_id_issued_at ?? 'Chưa cập nhật' }}</div>
                                </div>
                                <div class="info-item full-width">
                                    <label class="info-label">
                                        <i class="fas fa-map-marker-alt me-2"></i>Địa chỉ thường trú
                                    </label>
                                    <div class="info-value">{{ $userProfile->address ?? 'Chưa cập nhật' }}</div>
                                </div>
                                @if($userProfile->note)
                                    <div class="info-item full-width">
                                        <label class="info-label">
                                            <i class="fas fa-sticky-note me-2"></i>Ghi chú
                                        </label>
                                        <div class="info-value">{{ $userProfile->note }}</div>
                                    </div>
                                @endif
                            </div>
                            
                            @if(!$userProfile->isKycComplete())
                                <div class="alert alert-warning alert-modern-blue mt-4">
                                    <div class="alert-content">
                                        <i class="fas fa-exclamation-triangle me-3"></i>
                                        <div>
                                            <strong>Thông tin KYC chưa đầy đủ:</strong>
                                            <ul class="mb-0 mt-2">
                                                @foreach($userProfile->getMissingKycFields() as $field)
                                                    <li>{{ $field }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Banking Information -->
                <div class="modern-card-blue mb-4">
                    <div class="card-header-modern-blue">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-university me-3"></i>
                                <h5 class="mb-0">Thông tin ngân hàng</h5>
                            </div>
                            @if($user->hasValidBankingInfo())
                                <span class="badge" style="background: var(--blue-gradient); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600;">Đã cập nhật</span>
                            @else
                                <span class="badge" style="background: var(--status-expiring-gradient); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600;">Chưa cập nhật</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body-modern-blue">
                        @if($user->hasValidBankingInfo())
                            @php
                                $sepayBank = $userProfile?->sepayBank ?? $user->sepayBank;
                            @endphp
                            <div class="info-grid">
                                <div class="info-item">
                                    <label class="info-label">
                                        <i class="fas fa-university me-2"></i>Ngân hàng
                                    </label>
                                    <div class="info-value">
                                        <span class="badge" style="background: var(--blue-gradient); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600;">{{ $sepayBank->name ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <label class="info-label">
                                        <i class="fas fa-hashtag me-2"></i>Mã ngân hàng
                                    </label>
                                    <div class="info-value">{{ $sepayBank->code ?? 'N/A' }}</div>
                                </div>
                                <div class="info-item">
                                    <label class="info-label">
                                        <i class="fas fa-credit-card me-2"></i>Số tài khoản
                                    </label>
                                    <div class="info-value">
                                        <code style="background: var(--blue-bg-light); color: var(--blue-primary); padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; border: 1px solid var(--blue-border);">{{ $userProfile->account_number ?? $user->account_number ?? 'N/A' }}</code>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <label class="info-label">
                                        <i class="fas fa-user-tie me-2"></i>Tên chủ tài khoản
                                    </label>
                                    <div class="info-value">{{ $userProfile->account_holder_name ?? 'N/A' }}</div>
                                </div>
                                @if($userProfile->branch_name)
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-building me-2"></i>Chi nhánh
                                        </label>
                                        <div class="info-value">{{ $userProfile->branch_name }}</div>
                                    </div>
                                @endif
                                @if($userProfile->branch_code)
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-code me-2"></i>Mã chi nhánh
                                        </label>
                                        <div class="info-value">{{ $userProfile->branch_code }}</div>
                                    </div>
                                @endif
                                @if($userProfile->swift_code)
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-globe me-2"></i>Mã SWIFT
                                        </label>
                                        <div class="info-value">{{ $userProfile->swift_code }}</div>
                                    </div>
                                @endif
                                @if($userProfile->tax_code)
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-file-invoice me-2"></i>Mã số thuế
                                        </label>
                                        <div class="info-value">{{ $userProfile->tax_code }}</div>
                                    </div>
                                @endif
                                @if($userProfile->banking_notes)
                                    <div class="info-item full-width">
                                        <label class="info-label">
                                            <i class="fas fa-sticky-note me-2"></i>Ghi chú ngân hàng
                                        </label>
                                        <div class="info-value">{{ $userProfile->banking_notes }}</div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-exclamation-triangle mb-3" style="font-size: 3rem; color: var(--blue-primary, #2766ec);"></i>
                                <p style="color: var(--blue-primary, #2766ec); font-weight: 600; margin-bottom: 1rem;">Chưa có thông tin ngân hàng</p>
                                @include('tenant.components.button', [
                                    'type' => 'link',
                                    'variant' => 'primary-blue',
                                    'url' => route('tenant.profile.edit'),
                                    'icon' => 'fas fa-plus',
                                    'label' => 'Thêm thông tin ngân hàng'
                                ])
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Transactions -->
                @if($user->companyInvoices->count() > 0 || $user->cashOutflows->count() > 0)
                    <div class="modern-card-blue mb-4">
                        <div class="card-header-modern-blue">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-history me-3"></i>
                                <h5 class="mb-0">Lịch sử giao dịch gần đây</h5>
                            </div>
                        </div>
                        <div class="card-body-modern-blue">
                            <div class="row">
                                <!-- Company Invoices -->
                                @if($user->companyInvoices->count() > 0)
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <h6 class="mb-3" style="color: var(--blue-primary, #2766ec); font-weight: 700;">
                                            <i class="fas fa-file-invoice-dollar me-2"></i>Hóa đơn công ty
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Số hóa đơn</th>
                                                        <th>Loại</th>
                                                        <th>Số tiền</th>
                                                        <th>Trạng thái</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($user->companyInvoices as $invoice)
                                                        <tr>
                                                            <td>{{ $invoice->invoice_no ?? 'N/A' }}</td>
                                                            <td>{{ $invoice->invoice_type ?? 'N/A' }}</td>
                                                            <td>{{ number_format($invoice->total_amount ?? 0, 0, ',', '.') }} VND</td>
                                                            <td>
                                                                <span class="badge" style="background: {{ ($invoice->status ?? '') == 'paid' ? 'var(--status-active-gradient)' : 'var(--status-expiring-gradient)' }}; color: white; padding: 0.4rem 0.8rem; border-radius: 12px; font-weight: 600;">
                                                                    {{ $invoice->status ?? 'N/A' }}
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
                                @if($user->cashOutflows->count() > 0)
                                    <div class="col-md-6">
                                        <h6 class="mb-3" style="color: var(--blue-primary, #2766ec); font-weight: 700;">
                                            <i class="fas fa-money-bill-wave me-2"></i>Chi trả
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Mô tả</th>
                                                        <th>Số tiền</th>
                                                        <th>Trạng thái</th>
                                                        <th>Ngày</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($user->cashOutflows as $outflow)
                                                        <tr>
                                                            <td>{{ $outflow->note ?? 'N/A' }}</td>
                                                            <td>{{ number_format($outflow->amount ?? 0, 0, ',', '.') }} VND</td>
                                                            <td>
                                                                <span class="badge" style="background: {{ ($outflow->status ?? '') == 'success' ? 'var(--status-active-gradient)' : 'var(--status-expiring-gradient)' }}; color: white; padding: 0.4rem 0.8rem; border-radius: 12px; font-weight: 600;">
                                                                    {{ $outflow->status ?? 'N/A' }}
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
                        </div>
                    </div>
                @endif

                <!-- Security Card -->
                <div class="modern-card-blue">
                    <div class="card-header-modern-blue">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-shield-alt me-3"></i>
                            <h5 class="mb-0">Bảo mật</h5>
                        </div>
                    </div>
                    <div class="card-body-modern-blue">
                        <div class="security-actions">
                            @include('tenant.components.button', [
                                'type' => 'link',
                                'variant' => 'outline-primary',
                                'url' => route('tenant.profile.edit'),
                                'icon' => 'fas fa-key',
                                'label' => 'Đổi mật khẩu (Trong trang chỉnh sửa)'
                            ])
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="modern-card-blue profile-sidebar-blue">
                    <div class="card-body-modern-blue text-center">
                        <div class="profile-avatar">
                            <img class="avatar-img" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->full_name ?? 'User') }}&background=2766ec&color=fff&size=120" alt="avatar">
                            <div class="avatar-status online"></div>
                        </div>
                        <h5 class="profile-name">{{ auth()->user()?->full_name ?? 'User' }}</h5>
                        <div class="profile-email">{{ auth()->user()?->email ?? 'user@example.com' }}</div>
                        
                        <div class="profile-stats">
                            <div class="stat-item">
                                <div class="stat-number">{{ $userProfile ? $userProfile->getKycCompletionPercentage() : 0 }}%</div>
                                <div class="stat-label">KYC hoàn thành</div>
                            </div>
                        </div>

                        <div class="profile-actions">
                            @include('tenant.components.button', [
                                'type' => 'link',
                                'variant' => 'primary-blue',
                                'url' => route('tenant.dashboard'),
                                'icon' => 'fas fa-tachometer-alt',
                                'label' => 'Vào Dashboard',
                                'class' => 'w-100 mb-3'
                            ])
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                @include('tenant.components.button', [
                                    'type' => 'submit',
                                    'variant' => 'outline-danger',
                                    'icon' => 'fas fa-sign-out-alt',
                                    'label' => 'Đăng xuất',
                                    'class' => 'w-100 logout-btn'
                                ])
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('assets/js/user/profile.js') }}?v={{ time() }}"></script>
@endpush
@endsection
