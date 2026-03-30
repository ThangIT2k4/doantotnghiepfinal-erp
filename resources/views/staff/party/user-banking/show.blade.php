@extends('layouts.staff_dashboard')

@section('title', 'Chi tiết thông tin ngân hàng - ' . $user->full_name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user mr-2"></i>
                        Chi tiết thông tin ngân hàng - {{ $user->full_name }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('staff.user-banking.edit', ['user_banking' => $user]) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Chỉnh sửa
                        </a>
                        <a href="{{ route('staff.user-banking.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Personal Information -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-user-circle mr-2"></i>
                                        Thông tin cá nhân
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Họ và tên:</strong></td>
                                            <td>{{ $user->full_name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>{{ $user->email ?? 'Chưa cập nhật' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Số điện thoại:</strong></td>
                                            <td>{{ $user->phone ?? 'Chưa cập nhật' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Mã số thuế:</strong></td>
                                            <td>{{ $user->tax_code ?? 'Chưa cập nhật' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Số CMND/CCCD:</strong></td>
                                            <td>{{ $user->id_card_number ?? 'Chưa cập nhật' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Ngày sinh:</strong></td>
                                            <td>{{ $user->birth_date ? $user->birth_date->format('d/m/Y') : 'Chưa cập nhật' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Giới tính:</strong></td>
                                            <td>{{ $user->gender_label }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Địa chỉ:</strong></td>
                                            <td>{{ $user->address ?? 'Chưa cập nhật' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Banking Information -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-university mr-2"></i>
                                        Thông tin ngân hàng
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($user->hasValidBankingInfo())
                                        <table class="table table-borderless">
                                            <tr>
                                                <td><strong>Ngân hàng:</strong></td>
                                                <td>{{ $user->sepayBank->name ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Mã ngân hàng:</strong></td>
                                                <td>{{ $user->sepayBank->code ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Số tài khoản:</strong></td>
                                                <td>{{ $user->account_number }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Tên chủ tài khoản:</strong></td>
                                                <td>{{ $user->account_holder_name }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Chi nhánh:</strong></td>
                                                <td>{{ $user->branch_name ?? 'Chưa cập nhật' }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Mã chi nhánh:</strong></td>
                                                <td>{{ $user->branch_code ?? 'Chưa cập nhật' }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Mã SWIFT:</strong></td>
                                                <td>{{ $user->swift_code ?? 'Chưa cập nhật' }}</td>
                                            </tr>
                                            @if($user->banking_notes)
                                                <tr>
                                                    <td><strong>Ghi chú:</strong></td>
                                                    <td>{{ $user->banking_notes }}</td>
                                                </tr>
                                            @endif
                                        </table>
                                    @else
                                        <div class="text-center py-4">
                                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                            <p class="text-muted">Chưa có thông tin ngân hàng</p>
                                            <a href="{{ route('staff.user-banking.edit', ['user_banking' => $user]) }}" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Thêm thông tin ngân hàng
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    @if($user->companyInvoices->count() > 0 || $user->cashOutflows->count() > 0)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-history mr-2"></i>
                                            Lịch sử giao dịch gần đây
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <!-- Company Invoices -->
                                            @if($user->companyInvoices->count() > 0)
                                                <div class="col-md-6">
                                                    <h6>Hóa đơn công ty</h6>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
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
                                                                        <td>{{ $invoice->invoice_no }}</td>
                                                                        <td>{{ $invoice->invoice_type }}</td>
                                                                        <td>{{ number_format($invoice->total_amount, 0, ',', '.') }} VND</td>
                                                                        <td>
                                                                            <span class="badge badge-{{ $invoice->status == 'paid' ? 'success' : 'warning' }}">
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
                                            @if($user->cashOutflows->count() > 0)
                                                <div class="col-md-6">
                                                    <h6>Chi trả</h6>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
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
                                                                        <td>{{ $outflow->description }}</td>
                                                                        <td>{{ number_format($outflow->amount, 0, ',', '.') }} VND</td>
                                                                        <td>
                                                                            <span class="badge badge-{{ $outflow->status == 'success' ? 'success' : 'warning' }}">
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
