@extends('layouts.app')

@section('title', 'Hóa đơn của tôi')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/invoices.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/user/invoices.js') }}?v={{ time() }}"></script>
@endpush

@section('content')
<div class="invoices-container">
    <div class="container">
        <!-- Page Header -->
        <div class="invoices-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <h1 class="page-title">Hóa đơn của tôi</h1>
                            <p class="page-subtitle">Quản lý và theo dõi các hóa đơn thanh toán</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="header-actions">
                        <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Về Dashboard
                        </a>
                        <button class="btn btn-primary ms-2" onclick="exportInvoices()">
                            <i class="fas fa-download me-2"></i>Xuất Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-section">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card paid">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>8</h3>
                            <p>Đã thanh toán</p>
                            <div class="stat-amount">45.5M VNĐ</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>2</h3>
                            <p>Chờ thanh toán</p>
                            <div class="stat-amount">5.0M VNĐ</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card overdue">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>1</h3>
                            <p>Quá hạn</p>
                            <div class="stat-amount">2.5M VNĐ</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card total">
                        <div class="stat-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="stat-content">
                            <h3>11</h3>
                            <p>Tổng hóa đơn</p>
                            <div class="stat-amount">53.0M VNĐ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter and Search -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Tìm kiếm theo mã hóa đơn..." id="searchInput">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="monthFilter">
                        <option value="">Tất cả tháng</option>
                        <option value="2023-12">Tháng 12/2023</option>
                        <option value="2023-11">Tháng 11/2023</option>
                        <option value="2023-10">Tháng 10/2023</option>
                        <option value="2023-09">Tháng 9/2023</option>
                        <option value="2023-08">Tháng 8/2023</option>
                        <option value="2023-07">Tháng 7/2023</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-status="all">Tất cả</button>
                        <button class="filter-tab" data-status="paid">Đã thanh toán</button>
                        <button class="filter-tab" data-status="pending">Chờ thanh toán</button>
                        <button class="filter-tab" data-status="overdue">Quá hạn</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoices List -->
        <div class="invoices-list">
            <!-- Invoice Item 1 - Overdue -->
            <div class="invoice-card" data-status="overdue" data-month="2023-12">
                <div class="invoice-status overdue">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Quá hạn</span>
                </div>
                <div class="invoice-content">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="invoice-info">
                                <div class="invoice-id">HD2023001</div>
                                <div class="invoice-date">Ngày tạo: 01/12/2023</div>
                                <div class="due-date overdue">Hạn thanh toán: 05/12/2023</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="property-info">
                                <h4 class="property-name">Phòng trọ cao cấp Cầu Giấy</h4>
                                <p class="property-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    123 Đường Cầu Giấy, Hà Nội
                                </p>
                                <div class="invoice-period">
                                    <i class="fas fa-calendar"></i>
                                    Tháng 12/2023
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="amount-info">
                                <div class="amount-breakdown">
                                    <div class="breakdown-item">
                                        <span>Tiền phòng:</span>
                                        <span>2.500.000 VNĐ</span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Tiền điện:</span>
                                        <span>0 VNĐ</span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Tiền nước:</span>
                                        <span>0 VNĐ</span>
                                    </div>
                                    <div class="breakdown-total">
                                        <span>Tổng cộng:</span>
                                        <span>2.500.000 VNĐ</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="payment-method">
                                <div class="method-label">Phương thức</div>
                                <div class="method-value">Chưa chọn</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="invoice-actions">
                    <button class="btn btn-danger btn-sm" onclick="payInvoice('HD2023001')">
                        <i class="fas fa-credit-card me-1"></i>Thanh toán ngay
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="viewInvoice('HD2023001')">
                        <i class="fas fa-eye me-1"></i>Xem chi tiết
                    </button>
                    <button class="btn btn-outline-success btn-sm" onclick="downloadInvoice('HD2023001')">
                        <i class="fas fa-download me-1"></i>Tải PDF
                    </button>
                </div>
            </div>

            <!-- Invoice Item 2 - Pending -->
            <div class="invoice-card" data-status="pending" data-month="2023-12">
                <div class="invoice-status pending">
                    <i class="fas fa-clock"></i>
                    <span>Chờ thanh toán</span>
                </div>
                <div class="invoice-content">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="invoice-info">
                                <div class="invoice-id">HD2023002</div>
                                <div class="invoice-date">Ngày tạo: 25/12/2023</div>
                                <div class="due-date">Hạn thanh toán: 30/12/2023</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="property-info">
                                <h4 class="property-name">Homestay Hạnh Đào</h4>
                                <p class="property-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    789 Đường Hạnh Đào, Hà Nội
                                </p>
                                <div class="invoice-period">
                                    <i class="fas fa-calendar"></i>
                                    Tháng 12/2023
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="amount-info">
                                <div class="amount-breakdown">
                                    <div class="breakdown-item">
                                        <span>Tiền phòng:</span>
                                        <span>8.000.000 VNĐ</span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Tiền điện:</span>
                                        <span>150.000 VNĐ</span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Tiền nước:</span>
                                        <span>80.000 VNĐ</span>
                                    </div>
                                    <div class="breakdown-total">
                                        <span>Tổng cộng:</span>
                                        <span>8.230.000 VNĐ</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="payment-method">
                                <div class="method-label">Phương thức</div>
                                <div class="method-value">Chưa chọn</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="invoice-actions">
                    <button class="btn btn-primary btn-sm" onclick="payInvoice('HD2023002')">
                        <i class="fas fa-credit-card me-1"></i>Thanh toán
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="viewInvoice('HD2023002')">
                        <i class="fas fa-eye me-1"></i>Xem chi tiết
                    </button>
                    <button class="btn btn-outline-success btn-sm" onclick="downloadInvoice('HD2023002')">
                        <i class="fas fa-download me-1"></i>Tải PDF
                    </button>
                </div>
            </div>

            <!-- Invoice Item 3 - Paid -->
            <div class="invoice-card" data-status="paid" data-month="2023-11">
                <div class="invoice-status paid">
                    <i class="fas fa-check-circle"></i>
                    <span>Đã thanh toán</span>
                </div>
                <div class="invoice-content">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="invoice-info">
                                <div class="invoice-id">HD2023003</div>
                                <div class="invoice-date">Ngày tạo: 01/11/2023</div>
                                <div class="due-date">Đã thanh toán: 03/11/2023</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="property-info">
                                <h4 class="property-name">Phòng trọ cao cấp Cầu Giấy</h4>
                                <p class="property-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    123 Đường Cầu Giấy, Hà Nội
                                </p>
                                <div class="invoice-period">
                                    <i class="fas fa-calendar"></i>
                                    Tháng 11/2023
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="amount-info">
                                <div class="amount-breakdown">
                                    <div class="breakdown-item">
                                        <span>Tiền phòng:</span>
                                        <span>2.500.000 VNĐ</span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Tiền điện:</span>
                                        <span>120.000 VNĐ</span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Tiền nước:</span>
                                        <span>60.000 VNĐ</span>
                                    </div>
                                    <div class="breakdown-total">
                                        <span>Tổng cộng:</span>
                                        <span>2.680.000 VNĐ</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="payment-method">
                                <div class="method-label">Phương thức</div>
                                <div class="method-value">
                                    <div class="method-icon momo">
                                        <img src="https://developers.momo.vn/v3/assets/images/logo.png" alt="MoMo" style="width: 20px;">
                                        <span>MoMo</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="invoice-actions">
                    <button class="btn btn-outline-primary btn-sm" onclick="viewInvoice('HD2023003')">
                        <i class="fas fa-eye me-1"></i>Xem chi tiết
                    </button>
                    <button class="btn btn-outline-success btn-sm" onclick="downloadInvoice('HD2023003')">
                        <i class="fas fa-download me-1"></i>Tải PDF
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="viewReceipt('HD2023003')">
                        <i class="fas fa-receipt me-1"></i>Biên lai
                    </button>
                </div>
            </div>

            <!-- Invoice Item 4 - Paid -->
            <div class="invoice-card" data-status="paid" data-month="2023-10">
                <div class="invoice-status paid">
                    <i class="fas fa-check-circle"></i>
                    <span>Đã thanh toán</span>
                </div>
                <div class="invoice-content">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="invoice-info">
                                <div class="invoice-id">HD2023004</div>
                                <div class="invoice-date">Ngày tạo: 01/10/2023</div>
                                <div class="due-date">Đã thanh toán: 02/10/2023</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="property-info">
                                <h4 class="property-name">Homestay Hạnh Đào</h4>
                                <p class="property-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    789 Đường Hạnh Đào, Hà Nội
                                </p>
                                <div class="invoice-period">
                                    <i class="fas fa-calendar"></i>
                                    Tháng 10/2023
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="amount-info">
                                <div class="amount-breakdown">
                                    <div class="breakdown-item">
                                        <span>Tiền phòng:</span>
                                        <span>8.000.000 VNĐ</span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Tiền điện:</span>
                                        <span>180.000 VNĐ</span>
                                    </div>
                                    <div class="breakdown-item">
                                        <span>Tiền nước:</span>
                                        <span>90.000 VNĐ</span>
                                    </div>
                                    <div class="breakdown-total">
                                        <span>Tổng cộng:</span>
                                        <span>8.270.000 VNĐ</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="payment-method">
                                <div class="method-label">Phương thức</div>
                                <div class="method-value">
                                    <div class="method-icon bank">
                                        <i class="fas fa-university"></i>
                                        <span>Chuyển khoản</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="invoice-actions">
                    <button class="btn btn-outline-primary btn-sm" onclick="viewInvoice('HD2023004')">
                        <i class="fas fa-eye me-1"></i>Xem chi tiết
                    </button>
                    <button class="btn btn-outline-success btn-sm" onclick="downloadInvoice('HD2023004')">
                        <i class="fas fa-download me-1"></i>Tải PDF
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="viewReceipt('HD2023004')">
                        <i class="fas fa-receipt me-1"></i>Biên lai
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div class="empty-state" style="display: none;">
                <div class="empty-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h3>Không có hóa đơn nào</h3>
                <p>Không tìm thấy hóa đơn nào phù hợp với bộ lọc hiện tại.</p>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-section">
            <nav aria-label="Invoices pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <span class="page-link">Trước</span>
                    </li>
                    <li class="page-item active">
                        <span class="page-link">1</span>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="#">2</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="#">3</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="#">Sau</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Payment Method Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chọn phương thức thanh toán</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="invoice-summary mb-4">
                    <h6>Thông tin hóa đơn</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="summary-item">
                                <span class="label">Mã hóa đơn:</span>
                                <span class="value" id="paymentInvoiceId">-</span>
                            </div>
                            <div class="summary-item">
                                <span class="label">Phòng:</span>
                                <span class="value" id="paymentProperty">-</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="summary-item">
                                <span class="label">Kỳ thanh toán:</span>
                                <span class="value" id="paymentPeriod">-</span>
                            </div>
                            <div class="summary-item total">
                                <span class="label">Tổng tiền:</span>
                                <span class="value" id="paymentAmount">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="payment-methods">
                    <h6>Chọn phương thức thanh toán</h6>
                    <div class="method-options">
                        <div class="method-option" data-method="momo">
                            <div class="method-icon">
                                <img src="https://developers.momo.vn/v3/assets/images/logo.png" alt="MoMo" style="width: 40px;">
                            </div>
                            <div class="method-info">
                                <h6>Ví MoMo</h6>
                                <p>Thanh toán nhanh chóng với ví điện tử</p>
                            </div>
                            <div class="method-select">
                                <input type="radio" name="payment_method" value="momo" id="method_momo">
                                <label for="method_momo"></label>
                            </div>
                        </div>

                        <div class="method-option" data-method="bank">
                            <div class="method-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="method-info">
                                <h6>Chuyển khoản ngân hàng</h6>
                                <p>Chuyển tiền qua tài khoản ngân hàng</p>
                            </div>
                            <div class="method-select">
                                <input type="radio" name="payment_method" value="bank" id="method_bank">
                                <label for="method_bank"></label>
                            </div>
                        </div>

                        <div class="method-option" data-method="vnpay">
                            <div class="method-icon">
                                <img src="https://vnpay.vn/s1/statics.vnpay.vn/2023/9/06ncktiwd6dc1694418196384.png" alt="VNPay" style="width: 40px;">
                            </div>
                            <div class="method-info">
                                <h6>VNPay</h6>
                                <p>Cổng thanh toán trực tuyến VNPay</p>
                            </div>
                            <div class="method-select">
                                <input type="radio" name="payment_method" value="vnpay" id="method_vnpay">
                                <label for="method_vnpay"></label>
                            </div>
                        </div>

                        <div class="method-option" data-method="zalopay">
                            <div class="method-icon">
                                <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-ZaloPay-Square.png" alt="ZaloPay" style="width: 40px;">
                            </div>
                            <div class="method-info">
                                <h6>ZaloPay</h6>
                                <p>Thanh toán an toàn với ZaloPay</p>
                            </div>
                            <div class="method-select">
                                <input type="radio" name="payment_method" value="zalopay" id="method_zalopay">
                                <label for="method_zalopay"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="processPayment()" disabled id="confirmPaymentBtn">
                    <i class="fas fa-credit-card me-1"></i>Xác nhận thanh toán
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Detail Modal -->
<div class="modal fade" id="invoiceDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết hóa đơn</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="invoice-detail-content" id="invoiceDetailContent">
                    <!-- Invoice details will be loaded here -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Đang tải thông tin hóa đơn...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-success" onclick="downloadCurrentInvoice()">
                    <i class="fas fa-download me-1"></i>Tải PDF
                </button>
                <button type="button" class="btn btn-primary" onclick="printInvoice()">
                    <i class="fas fa-print me-1"></i>In hóa đơn
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Processing Modal -->
<div class="modal fade" id="processingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="processing-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h4 class="mt-3">Đang xử lý thanh toán...</h4>
                <p>Vui lòng không đóng trang này</p>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%" id="paymentProgress"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
