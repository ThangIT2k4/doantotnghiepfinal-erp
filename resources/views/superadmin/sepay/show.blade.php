@extends('layouts.superadmin')

@section('title', 'Chi tiết giao dịch SePay')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('superadmin.sepay.index') }}">SePay Management</a></li>
        <li class="breadcrumb-item active" aria-current="page">Chi tiết giao dịch</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-receipt me-2"></i>
                Chi tiết giao dịch SePay
            </h1>
            <p class="text-muted mb-0">Mã giao dịch: <strong>#{{ $transaction->sepay_transaction_id }}</strong></p>
        </div>
        <div>
            <a href="{{ route('superadmin.sepay.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Transaction Info -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle me-2"></i>
                        Thông tin giao dịch
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th style="width: 40%;">Mã giao dịch SePay:</th>
                                <td><strong>{{ $transaction->sepay_transaction_id }}</strong></td>
                            </tr>
                            <tr>
                                <th>Ngày giao dịch:</th>
                                <td>{{ $transaction->transaction_date ? $transaction->transaction_date->format('d/m/Y H:i:s') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Ngân hàng:</th>
                                <td>
                                    <span class="badge badge-secondary">{{ $transaction->gateway ?? 'N/A' }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Số tài khoản:</th>
                                <td>{{ $transaction->account_number ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Loại giao dịch:</th>
                                <td>
                                    @if($transaction->transfer_type == 'in')
                                        <span class="badge badge-success">Tiền vào</span>
                                    @elseif($transaction->transfer_type == 'out')
                                        <span class="badge badge-danger">Tiền ra</span>
                                    @else
                                        {{ $transaction->transfer_type ?? 'N/A' }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Số tiền:</th>
                                <td><strong class="text-success" style="font-size: 1.2em;">{{ number_format($transaction->amount) }} VNĐ</strong></td>
                            </tr>
                            <tr>
                                <th>Số dư (lũy kế):</th>
                                <td>{{ $transaction->accumulated ? number_format($transaction->accumulated) . ' VNĐ' : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Nội dung chuyển khoản:</th>
                                <td><code>{{ $transaction->content }}</code></td>
                            </tr>
                            <tr>
                                <th>Mã tham chiếu:</th>
                                <td>{{ $transaction->reference_code ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Mã code:</th>
                                <td>{{ $transaction->code ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Trạng thái:</th>
                                <td>
                                    @if($transaction->status == 'processed')
                                        <span class="badge badge-success">Thành công</span>
                                    @elseif($transaction->status == 'pending')
                                        <span class="badge badge-warning">Đang chờ</span>
                                    @elseif($transaction->status == 'failed')
                                        <span class="badge badge-danger">Thất bại</span>
                                    @elseif($transaction->status == 'duplicate')
                                        <span class="badge badge-secondary">Trùng lặp</span>
                                    @else
                                        <span class="badge badge-light">{{ $transaction->status }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Ngày nhận webhook:</th>
                                <td>{{ $transaction->created_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>Ngày xử lý:</th>
                                <td>{{ $transaction->processed_at ? $transaction->processed_at->format('d/m/Y H:i:s') : 'Chưa xử lý' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    @if($transaction->error_message)
                        <div class="alert alert-danger mt-3">
                            <strong>Lỗi:</strong> {{ $transaction->error_message }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Invoice & Organization Info -->
        <div class="col-lg-6 mb-4">
            <!-- Organization Info -->
            @if($transaction->invoice && $transaction->invoice->organization)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-building me-2"></i>
                            Thông tin tổ chức
                        </h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <th style="width: 40%;">Mã tổ chức:</th>
                                    <td><span class="badge badge-info">{{ $transaction->invoice->organization->code }}</span></td>
                                </tr>
                                <tr>
                                    <th>Tên tổ chức:</th>
                                    <td><strong>{{ $transaction->invoice->organization->name }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>{{ $transaction->invoice->organization->email ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Điện thoại:</th>
                                    <td>{{ $transaction->invoice->organization->phone ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Địa chỉ:</th>
                                    <td>{{ $transaction->invoice->organization->address ?? 'N/A' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Invoice Info -->
            @if($transaction->invoice)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-file-invoice me-2"></i>
                            Thông tin hóa đơn
                        </h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <th style="width: 40%;">Mã hóa đơn:</th>
                                    <td><strong>{{ $transaction->invoice->invoice_no }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Ngày lập:</th>
                                    <td>{{ $transaction->invoice->invoice_date->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Hạn thanh toán:</th>
                                    <td>{{ $transaction->invoice->due_date ? $transaction->invoice->due_date->format('d/m/Y') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Tổng tiền:</th>
                                    <td><strong>{{ number_format($transaction->invoice->total_amount) }} VNĐ</strong></td>
                                </tr>
                                <tr>
                                    <th>Đã thanh toán:</th>
                                    <td><strong class="text-success">{{ number_format($transaction->invoice->paid_amount) }} VNĐ</strong></td>
                                </tr>
                                <tr>
                                    <th>Còn lại:</th>
                                    <td><strong class="text-danger">{{ number_format($transaction->invoice->total_amount - $transaction->invoice->paid_amount) }} VNĐ</strong></td>
                                </tr>
                                <tr>
                                    <th>Trạng thái:</th>
                                    <td>
                                        @if($transaction->invoice->payment_status == 'paid')
                                            <span class="badge badge-success">Đã thanh toán</span>
                                        @elseif($transaction->invoice->payment_status == 'partial')
                                            <span class="badge badge-warning">Thanh toán một phần</span>
                                        @elseif($transaction->invoice->payment_status == 'unpaid')
                                            <span class="badge badge-danger">Chưa thanh toán</span>
                                        @else
                                            <span class="badge badge-light">{{ $transaction->invoice->payment_status }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Invoice Items -->
                        @if($transaction->invoice->items && $transaction->invoice->items->count() > 0)
                            <h6 class="mt-4 mb-3">Chi tiết hóa đơn:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Dịch vụ</th>
                                            <th class="text-right">Số lượng</th>
                                            <th class="text-right">Đơn giá</th>
                                            <th class="text-right">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transaction->invoice->items as $item)
                                            <tr>
                                                <td>{{ $item->description }}</td>
                                                <td class="text-right">{{ $item->quantity }}</td>
                                                <td class="text-right">{{ number_format($item->unit_price) }}</td>
                                                <td class="text-right"><strong>{{ number_format($item->amount) }}</strong></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Tenant Info -->
                @if($transaction->invoice->lease && $transaction->invoice->lease->tenant)
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-user me-2"></i>
                                Thông tin khách hàng
                            </h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%;">Tên khách hàng:</th>
                                        <td><strong>{{ $transaction->invoice->lease->tenant->name }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Số điện thoại:</th>
                                        <td>{{ $transaction->invoice->lease->tenant->phone ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td>{{ $transaction->invoice->lease->tenant->email ?? 'N/A' }}</td>
                                    </tr>
                                    @if($transaction->invoice->lease->unit)
                                        <tr>
                                            <th>Phòng:</th>
                                            <td>
                                                {{ $transaction->invoice->lease->unit->unit_no }}
                                                @if($transaction->invoice->lease->unit->property)
                                                    - {{ $transaction->invoice->lease->unit->property->name }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Payment Info -->
            @if($transaction->payment)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            Thông tin thanh toán
                        </h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <th style="width: 40%;">Mã thanh toán:</th>
                                    <td><strong>{{ $transaction->payment->payment_no }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Ngày thanh toán:</th>
                                    <td>{{ $transaction->payment->payment_date->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Số tiền:</th>
                                    <td><strong class="text-success">{{ number_format($transaction->payment->amount) }} VNĐ</strong></td>
                                </tr>
                                <tr>
                                    <th>Phương thức:</th>
                                    <td>{{ $transaction->payment->paymentMethod->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Người xác nhận:</th>
                                    <td>{{ $transaction->payment->payerUser->name ?? 'Hệ thống (tự động)' }}</td>
                                </tr>
                                @if($transaction->payment->note)
                                    <tr>
                                        <th>Ghi chú:</th>
                                        <td>{{ $transaction->payment->note }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Raw Webhook Data -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-code me-2"></i>
                        Dữ liệu webhook gốc
                    </h6>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded"><code>{{ json_encode(json_decode($transaction->raw_data ?? '{}'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

