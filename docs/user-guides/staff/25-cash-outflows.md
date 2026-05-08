# QUẢN LÝ DÒNG TIỀN CHI - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý dòng tiền chi (cash outflows) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, mark success, mark failed, reverse, bulk hành động, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả cash outflows trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `finance.access`
  - Xem cash outflows: Cần capability `finance.cash_outflow.view` (mặc định chỉ xem)
  - Tạo/Cập nhật/Mark Đã thanh toán: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/cash-outflows`

## Các bước thực hiện

### 1. Xem danh sách Cash Outflows

1. Truy cập **Cash Outflows** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả cash outflows trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (đang chờ, success, failed, reversed)
   - Category (vendor_payment, salary, commission, refund, other)
   - Thanh toán Method (cash, bank_transfer, sepay)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo paid_at, số tiền, trạng thái

### 2. Xem chi tiết Cash Outflow

1. Click vào outflow trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Outflow ID: Mã dòng tiền chi
     - Category: Loại chi
     - Số tiền: Số tiền
     - Thanh toán Method: Phương thức thanh toán
     - Đã thanh toán At: Ngày giờ thanh toán
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin khác:**
     - Note: Ghi chú
     - Company Hóa đơn: Hóa đơn công ty liên quan (nếu có)
     - Created At, Updated At

### 3. Tạo Cash Outflow mới

1. Click **Tạo Cash Outflow** hoặc **+ New**
2. Điền thông tin:
   - **Category** (bắt buộc): Loại chi
   - **Số tiền** (bắt buộc): Số tiền (> 0)
   - **Thanh toán Method** (bắt buộc): cash, bank_transfer, sepay
   - **Đã thanh toán At** (bắt buộc): Ngày giờ thanh toán
   - **Note** (tùy chọn): Ghi chú
   - **Company Hóa đơn** (tùy chọn): Hóa đơn công ty liên quan
   - **Trạng thái** (tự động): `pending`
3. Click **Lưu**
4. Cash Outflow được tạo với trạng thái `pending`

### 4. Mark Success (Đánh dấu Thành công)

1. Truy cập chi tiết outflow có trạng thái `pending`
2. Click **Mark Success** hoặc **Đánh dấu thành công**
3. Outflow trạng thái chuyển sang `success`
4. Hệ thống cập nhật Company Hóa đơn trạng thái (nếu có)

### 5. Mark Failed (Đánh dấu Thất bại)

1. Truy cập chi tiết outflow có trạng thái `pending`
2. Click **Mark Failed** hoặc **Đánh dấu thất bại**
3. Outflow trạng thái chuyển sang `failed`

### 6. Reverse (Hoàn trả)

1. Truy cập chi tiết outflow có trạng thái `success`
2. Click **Reverse** hoặc **Hoàn trả**
3. Outflow trạng thái chuyển sang `reversed`
4. Đã thanh toán At được đặt lại về null

### 7. Bulk Hành động (Xử lý Hàng loạt)

1. Chọn nhiều outflows trong danh sách
2. Chọn action:
   - **Mark Success**: Đánh dấu thành công hàng loạt
   - **Mark Failed**: Đánh dấu thất bại hàng loạt
   - **Reverse**: Hoàn trả hàng loạt
3. Click **Apply**
4. Hệ thống xử lý hàng loạt và hiển thị kết quả

### 8. Xem Thống kê

1. Truy cập **Cash Outflows** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Outflows by Trạng thái: Phân bố theo trạng thái
   - Outflows by Category: Phân bố theo loại
   - Outflows by Period: Phân bố theo thời gian
   - Tổng Số tiền: Tổng số tiền chi
   - Success Số tiền: Tổng số tiền chi thành công
   - Failed Số tiền: Tổng số tiền chi thất bại

## Ràng buộc và điều kiện

### Validation Rules

- **Category**: Bắt buộc
- **Số tiền**: Bắt buộc, phải > 0
- **Thanh toán Method**: Bắt buộc
- **Đã thanh toán At**: Bắt buộc

### Business Rules

1. **Trạng thái Flow**
   - `pending` → `success` hoặc `failed`
   - `success` → `reversed`

2. **Company Hóa đơn**
   - Cash Outflow có thể link với Company Hóa đơn
   - Khi mark success, Company Hóa đơn trạng thái được cập nhật

3. **Reverse**
   - Chỉ có thể reverse outflow có trạng thái `success`
   - Reverse sẽ đặt lại Đã thanh toán At về null

## Ví dụ

### Ví dụ 1: Tạo và Mark Success Cash Outflow

**Thông tin outflow:**
- Category: `vendor_payment`
- Số tiền: 2,000,000 VND
- Thanh toán Method: `bank_transfer`
- Đã thanh toán At: 2025-01-15 10:00
- Note: "Thanh toán cho Nhà cung cấp ABC"
- Trạng thái: `pending`

**Các bước:**
1. Truy cập Cash Outflows
2. Click **Tạo Cash Outflow**
3. Điền thông tin trên
4. Click **Lưu**
5. Outflow được tạo với trạng thái `pending`
6. Click **Mark Success**
7. Outflow trạng thái chuyển sang `success`

---

**Xem thêm:**
- [Quản lý Company Hóa đơn](./24-company-invoices.md)

**Cập nhật: 2025-01-XX

