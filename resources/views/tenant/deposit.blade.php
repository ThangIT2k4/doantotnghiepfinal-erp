@extends('layouts.app')

@section('title', 'Đặt cọc thuê phòng')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/user/deposit.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/js/user/deposit.js') }}?v={{ time() }}"></script>
@endpush

@section('content')
<div class="deposit-container">
    <div class="container">
        <!-- Progress Steps -->
        <div class="progress-section">
            <div class="progress-steps">
                <div class="step active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-title">Chọn phương thức</div>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-title">Xác nhận</div>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-title">Biên lai</div>
                </div>
            </div>
        </div>

        <!-- Room Info Header -->
        <div class="room-info-header">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="room-image">
                        <img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=300&h=200&fit=crop" alt="Phòng trọ">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="room-details">
                        <h3 class="room-title">Phòng trọ cao cấp Cầu Giấy</h3>
                        <p class="room-location">
                            <i class="fas fa-map-marker-alt"></i>
                            123 Đường Cầu Giấy, Phường Dịch Vọng, Quận Cầu Giấy, Hà Nội
                        </p>
                        <div class="room-specs">
                            <span class="spec">
                                <i class="fas fa-expand-arrows-alt"></i>
                                25m²
                            </span>
                            <span class="spec">
                                <i class="fas fa-users"></i>
                                2 người
                            </span>
                            <span class="spec price">
                                <i class="fas fa-money-bill-wave"></i>
                                2.5 triệu/tháng
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="deposit-summary">
                        <div class="summary-item">
                            <span class="label">Tiền cọc:</span>
                            <span class="amount">2.500.000 VNĐ</span>
                        </div>
                        <div class="summary-item total">
                            <span class="label">Tổng thanh toán:</span>
                            <span class="amount">2.500.000 VNĐ</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step Content -->
        <div class="step-content">
            <!-- Step 1: Payment Method -->
            <div class="step-panel active" id="step1">
                <div class="panel-header">
                    <h2>Chọn phương thức thanh toán</h2>
                    <p>Vui lòng chọn phương thức thanh toán phù hợp để đặt cọc phòng trọ</p>
                </div>
                
                <div class="payment-methods">
                    <div class="payment-method" data-method="bank">
                        <div class="method-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="method-info">
                            <h4>Chuyển khoản ngân hàng</h4>
                            <p>Chuyển tiền trực tiếp qua tài khoản ngân hàng</p>
                            <div class="method-features">
                                <span class="feature">✓ An toàn</span>
                                <span class="feature">✓ Nhanh chóng</span>
                                <span class="feature">✓ Không phí</span>
                            </div>
                        </div>
                        <div class="method-select">
                            <input type="radio" name="payment_method" value="bank" id="bank">
                            <label for="bank"></label>
                        </div>
                    </div>

                    <div class="payment-method" data-method="momo">
                        <div class="method-icon momo">
                            <img src="https://developers.momo.vn/v3/assets/images/logo.png" alt="MoMo" style="width: 40px;">
                        </div>
                        <div class="method-info">
                            <h4>Ví MoMo</h4>
                            <p>Thanh toán nhanh chóng qua ví điện tử MoMo</p>
                            <div class="method-features">
                                <span class="feature">✓ Tiện lợi</span>
                                <span class="feature">✓ Tức thì</span>
                                <span class="feature">✓ Ưu đãi</span>
                            </div>
                        </div>
                        <div class="method-select">
                            <input type="radio" name="payment_method" value="momo" id="momo">
                            <label for="momo"></label>
                        </div>
                    </div>

                    <div class="payment-method" data-method="zalopay">
                        <div class="method-icon zalopay">
                            <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-ZaloPay-Square.png" alt="ZaloPay" style="width: 40px;">
                        </div>
                        <div class="method-info">
                            <h4>ZaloPay</h4>
                            <p>Thanh toán an toàn với ví điện tử ZaloPay</p>
                            <div class="method-features">
                                <span class="feature">✓ Bảo mật</span>
                                <span class="feature">✓ Dễ dùng</span>
                                <span class="feature">✓ Cashback</span>
                            </div>
                        </div>
                        <div class="method-select">
                            <input type="radio" name="payment_method" value="zalopay" id="zalopay">
                            <label for="zalopay"></label>
                        </div>
                    </div>

                    <div class="payment-method" data-method="vnpay">
                        <div class="method-icon vnpay">
                            <img src="https://vnpay.vn/s1/statics.vnpay.vn/2023/9/06ncktiwd6dc1694418196384.png" alt="VNPay" style="width: 40px;">
                        </div>
                        <div class="method-info">
                            <h4>VNPay</h4>
                            <p>Cổng thanh toán trực tuyến VNPay</p>
                            <div class="method-features">
                                <span class="feature">✓ Đa dạng</span>
                                <span class="feature">✓ Tin cậy</span>
                                <span class="feature">✓ Hỗ trợ 24/7</span>
                            </div>
                        </div>
                        <div class="method-select">
                            <input type="radio" name="payment_method" value="vnpay" id="vnpay">
                            <label for="vnpay"></label>
                        </div>
                    </div>
                </div>

                <div class="step-actions">
                    <button class="btn btn-primary btn-lg" id="nextStep1" disabled>
                        Tiếp tục
                        <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2: Confirmation -->
            <div class="step-panel" id="step2">
                <div class="panel-header">
                    <h2>Xác nhận thông tin thanh toán</h2>
                    <p>Vui lòng kiểm tra lại thông tin trước khi thực hiện thanh toán</p>
                </div>

                <div class="confirmation-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-section">
                                <h4>Thông tin người thuê</h4>
                                <div class="info-group">
                                    <label>Họ và tên:</label>
                                    <input type="text" class="form-control" id="renterName" placeholder="Nguyễn Văn A" required>
                                </div>
                                <div class="info-group">
                                    <label>Số điện thoại:</label>
                                    <input type="tel" class="form-control" id="renterPhone" placeholder="0987 654 321" required>
                                </div>
                                <div class="info-group">
                                    <label>Email:</label>
                                    <input type="email" class="form-control" id="renterEmail" placeholder="email@example.com" required>
                                </div>
                                <div class="info-group">
                                    <label>CCCD/CMND:</label>
                                    <input type="text" class="form-control" id="renterID" placeholder="123456789012" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="payment-summary">
                                <h4>Chi tiết thanh toán</h4>
                                <div class="summary-details">
                                    <div class="detail-row">
                                        <span>Phòng trọ:</span>
                                        <span>Phòng trọ cao cấp Cầu Giấy</span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Tiền thuê/tháng:</span>
                                        <span>2.500.000 VNĐ</span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Tiền cọc (1 tháng):</span>
                                        <span>2.500.000 VNĐ</span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Phí dịch vụ:</span>
                                        <span>0 VNĐ</span>
                                    </div>
                                    <div class="detail-row total">
                                        <span>Tổng cộng:</span>
                                        <span>2.500.000 VNĐ</span>
                                    </div>
                                </div>

                                <div class="selected-method">
                                    <h5>Phương thức thanh toán:</h5>
                                    <div class="method-display" id="selectedMethod">
                                        <!-- Will be populated by JS -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="terms-agreement">
                        <label class="checkbox-container">
                            <input type="checkbox" id="agreeTerms" required>
                            <span class="checkmark"></span>
                            Tôi đã đọc và đồng ý với 
                            <a href="#" class="text-link">Điều khoản và điều kiện</a> 
                            và 
                            <a href="#" class="text-link">Chính sách bảo mật</a>
                        </label>
                    </div>
                </div>

                <div class="step-actions">
                    <button class="btn btn-outline-secondary btn-lg" id="backStep2">
                        <i class="fas fa-arrow-left me-2"></i>
                        Quay lại
                    </button>
                    <button class="btn btn-success btn-lg" id="confirmPayment" disabled>
                        Xác nhận thanh toán
                        <i class="fas fa-credit-card ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: Receipt -->
            <div class="step-panel" id="step3">
                <div class="receipt-container">
                    <div class="receipt-header">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2>Thanh toán thành công!</h2>
                        <p>Cảm ơn bạn đã đặt cọc. Dưới đây là biên lai thanh toán của bạn.</p>
                    </div>

                    <div class="receipt-content">
                        <div class="receipt-info">
                            <div class="receipt-title">
                                <h3>BIÊN LAI THANH TOÁN</h3>
                                <div class="receipt-number">
                                    <span>Mã giao dịch: <strong id="transactionId">DP2023122501</strong></span>
                                    <span>Ngày: <strong id="transactionDate">25/12/2023 14:30</strong></span>
                                </div>
                            </div>

                            <div class="receipt-details">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="detail-section">
                                            <h4>Thông tin người thuê</h4>
                                            <div class="detail-item">
                                                <span class="label">Họ và tên:</span>
                                                <span class="value" id="receiptRenterName">-</span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="label">Số điện thoại:</span>
                                                <span class="value" id="receiptRenterPhone">-</span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="label">Email:</span>
                                                <span class="value" id="receiptRenterEmail">-</span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="label">CCCD/CMND:</span>
                                                <span class="value" id="receiptRenterID">-</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="detail-section">
                                            <h4>Thông tin phòng trọ</h4>
                                            <div class="detail-item">
                                                <span class="label">Tên phòng:</span>
                                                <span class="value">Phòng trọ cao cấp Cầu Giấy</span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="label">Địa chỉ:</span>
                                                <span class="value">123 Đường Cầu Giấy, Hà Nội</span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="label">Diện tích:</span>
                                                <span class="value">25m²</span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="label">Giá thuê:</span>
                                                <span class="value">2.500.000 VNĐ/tháng</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="payment-breakdown">
                                    <h4>Chi tiết thanh toán</h4>
                                    <table class="breakdown-table">
                                        <tr>
                                            <td>Tiền cọc (1 tháng)</td>
                                            <td class="amount">2.500.000 VNĐ</td>
                                        </tr>
                                        <tr>
                                            <td>Phí dịch vụ</td>
                                            <td class="amount">0 VNĐ</td>
                                        </tr>
                                        <tr class="total-row">
                                            <td><strong>Tổng thanh toán</strong></td>
                                            <td class="amount"><strong>2.500.000 VNĐ</strong></td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="payment-method-info">
                                    <h4>Phương thức thanh toán</h4>
                                    <div class="method-used" id="receiptPaymentMethod">
                                        <!-- Will be populated by JS -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="receipt-footer">
                            <div class="qr-code">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=DP2023122501" alt="QR Code">
                                <p>Mã QR biên lai</p>
                            </div>
                            <div class="footer-text">
                                <p>Biên lai này là bằng chứng xác nhận bạn đã đặt cọc thành công.</p>
                                <p>Vui lòng lưu giữ biên lai để làm thủ tục nhận phòng.</p>
                            </div>
                        </div>
                    </div>

                    <div class="receipt-actions">
                        <button class="btn btn-outline-primary btn-lg" id="downloadReceipt">
                            <i class="fas fa-download me-2"></i>
                            Tải biên lai
                        </button>
                        <button class="btn btn-outline-info btn-lg" id="emailReceipt">
                            <i class="fas fa-envelope me-2"></i>
                            Gửi email
                        </button>
                        <button class="btn btn-primary btn-lg" id="printReceipt">
                            <i class="fas fa-print me-2"></i>
                            In biên lai
                        </button>
                        <a href="{{ route('tenant.appointments') }}" class="btn btn-success btn-lg">
                            <i class="fas fa-calendar me-2"></i>
                            Quản lý lịch hẹn
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <h4 class="mt-3">Đang xử lý thanh toán...</h4>
                <p>Vui lòng không đóng trang này</p>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="success-animation">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
                <h4 class="text-success mt-3">Thanh toán thành công!</h4>
                <p>Giao dịch của bạn đã được xử lý thành công.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">Tiếp tục</button>
            </div>
        </div>
    </div>
</div>
@endsection
