# QUẢN LÝ SEPAY - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý SePay (hệ thống thanh toán), bao gồm xem transactions, webhook logs, và cài đặt.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ quản lý SePay trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `system.sepay.access`
  - Xem transactions: Cần capability `system.sepay.view` (mặc định có thể xem)
  - Xem webhook logs: Cần capability `system.sepay.view` (mặc định có thể xem)
  - Cài đặt SePay: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/sepay`

## Các bước thực hiện

### 1. Xem danh sách SePay Transactions

1. Truy cập **SePay** → **Transactions** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả SePay transactions trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (đang chờ, success, failed)
   - Thanh toán Method
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo created_at, số tiền, trạng thái

### 2. Xem chi tiết Transaction

1. Click vào transaction trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Transaction ID: Mã giao dịch
     - Thanh toán: Thanh toán liên quan
     - Số tiền: Số tiền
     - Trạng thái: Trạng thái hiện tại
     - Thanh toán Method: Phương thức thanh toán
   - **Thông tin SePay:**
     - SePay Transaction ID: Mã giao dịch SePay
     - QR Code: Mã QR thanh toán
     - Bank Account: Thông tin tài khoản ngân hàng
     - Transaction Reference: Số tham chiếu giao dịch
   - **Thông tin khác:**
     - Created At, Updated At
     - Đã thanh toán At: Ngày giờ thanh toán (nếu có)

### 3. Xem Webhook Logs

1. Truy cập **SePay** → **Webhook Logs** từ menu Nhân viên
2. Hệ thống hiển thị danh sách webhook logs
3. Có thể lọc theo:
   - Trạng thái (success, failed)
   - Loại (thanh toán, refund, etc.)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo created_at, trạng thái

### 4. Xem chi tiết Webhook Log

1. Click vào webhook log trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Webhook ID: Mã webhook
     - Loại: Loại webhook
     - Trạng thái: Trạng thái (success, failed)
   - **Request Data:**
     - Headers: Headers của request
     - Body: Body của request
   - **Response Data:**
     - Trạng thái Code: Trạng thái code của response
     - Response: Response từ hệ thống
   - **Thông tin khác:**
     - Created At: Ngày giờ nhận webhook
     - Processed At: Ngày giờ xử lý webhook

### 5. Cấu hình SePay Cài đặt

1. Truy cập **SePay** → **Cài đặt** từ menu Nhân viên
2. Hệ thống hiển thị cài đặt SePay:
   - **API Cài đặt:**
     - API Key: API key của SePay
     - API Secret: API secret của SePay
     - Webhook URL: URL nhận webhook
   - **Thanh toán Cài đặt:**
     - Đã bật: Bật/tắt SePay
     - Supported Banks: Danh sách ngân hàng hỗ trợ
   - **Other Cài đặt:**
     - Timeout: Thời gian timeout (giây)
     - Retry Count: Số lần retry
3. Cập nhật thông tin nếu cần
4. Click **Lưu**
5. Cài đặt được lưu

### 6. Retry Failed Transaction (Thử lại Giao dịch Thất bại)

1. Truy cập chi tiết transaction có trạng thái `failed`
2. Click **Retry** hoặc **Thử lại**
3. Hệ thống gửi lại request đến SePay
4. Transaction trạng thái được cập nhật

### 7. Xem Thống kê

1. Truy cập **SePay** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Transactions by Trạng thái: Phân bố theo trạng thái
   - Transactions by Period: Phân bố theo thời gian
   - Tổng Success Số tiền: Tổng số tiền thanh toán thành công
   - Tổng Failed Số tiền: Tổng số tiền thanh toán thất bại
   - Success Rate: Tỷ lệ thành công
   - Webhook Logs by Trạng thái: Phân bố webhook logs theo trạng thái

## Ràng buộc và điều kiện

### Validation Rules

- **API Key**: Bắt buộc nếu SePay đã bật
- **API Secret**: Bắt buộc nếu SePay đã bật
- **Webhook URL**: Bắt buộc nếu SePay đã bật

### Business Rules

1. **SePay Integration**
   - SePay là hệ thống thanh toán bên thứ ba
   - Cần API Key và API Secret để tích hợp

2. **Webhook Processing**
   - Webhook được nhận từ SePay khi có transaction
   - Hệ thống xử lý webhook và cập nhật thanh toán trạng thái

3. **Transaction Trạng thái**
   - `pending`: Giao dịch đang chờ xử lý
   - `success`: Giao dịch thành công
   - `failed`: Giao dịch thất bại

## Ví dụ

### Ví dụ 1: Xem SePay Transaction

**Kịch bản:** Quản lý muốn xem transaction SePay

**Các bước:**
1. Truy cập SePay → Transactions
2. Hệ thống hiển thị danh sách transactions
3. Click vào transaction cần xem
4. Hệ thống hiển thị thông tin chi tiết:
   - Transaction ID: TXN-123456
   - Thanh toán: Thanh toán #001
   - Số tiền: 10,000,000 VND
   - Trạng thái: `success`
   - Đã thanh toán At: 2025-01-15 10:00

---

**Xem thêm:**
- [Quản lý Thanh toán](./13-payments.md)
- [SePay Webhook Integration](../../SEPAY_WEBHOOK_INTEGRATION.md)

**Cập nhật: 2025-01-XX

