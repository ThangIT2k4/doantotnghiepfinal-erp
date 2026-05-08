# QUẢN LÝ HÓA ĐƠN CÔNG TY - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý hóa đơn công ty (company hóa đơn) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, approve, mark đã thanh toán, hủy, bulk hành động, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả company hóa đơn trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `finance.access`
  - Xem company hóa đơn: Cần capability `finance.company_invoice.view` (mặc định chỉ xem)
  - Tạo/Cập nhật/Approve: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/company-invoices`

## Các bước thực hiện

### 1. Xem danh sách Company Hóa đơn

1. Truy cập **Company Hóa đơn** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả company hóa đơn trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (draft, đang chờ, đã phê duyệt, đã thanh toán, overdue, cancelled)
   - Hóa đơn Loại (vendor_payment, user_payout, master_lease, ticket_cost, deposit_refund, payroll_payslip)
   - Nhà cung cấp (nếu có nhiều nhà cung cấp)
   - Người dùng (nếu có nhiều người dùng)
   - Ngày (today, this week, this month, overdue, tùy chỉnh range)
   - Sắp xếp theo issue_date, due_date, total_amount, trạng thái

### 2. Xem chi tiết Company Hóa đơn

1. Click vào hóa đơn trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Hóa đơn Number: Số hóa đơn
     - Hóa đơn Loại: Loại hóa đơn
     - Nhà cung cấp hoặc Người dùng: Nhà cung cấp hoặc người dùng
     - Issue Ngày, Đến hạn Ngày: Ngày phát hành và hạn thanh toán
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin tài chính:**
     - Items: Danh sách các khoản
     - Subtotal, Tax Số tiền, Discount Số tiền, Tổng Số tiền
     - Đã thanh toán Số tiền, Remaining Số tiền
   - **Thông tin liên quan:**
     - Source Loại: Loại source (master_lease, ticket, deposit_refund, payroll_payslip, etc.)
     - Source ID: ID của source
   - **Thông tin khác:**
     - Description, Note
     - Attachment URL
     - Created At, Updated At

### 3. Tạo Company Hóa đơn mới

1. Click **Tạo Company Hóa đơn** hoặc **+ New**
2. Điền thông tin:
   - **Hóa đơn Loại** (bắt buộc): Loại hóa đơn
   - **Nhà cung cấp** hoặc **Người dùng** (tùy theo Hóa đơn Loại)
   - **Issue Ngày**, **Đến hạn Ngày**
   - **Items** (bắt buộc, ít nhất 1 item)
   - **Subtotal**, **Tax Số tiền**, **Discount Số tiền**, **Tổng Số tiền**
   - **Description**, **Note**
   - **Attachment URL** (tùy chọn)
   - **Trạng thái** (tự động): `draft` hoặc `pending`
3. Click **Lưu**
4. Company Hóa đơn được tạo với trạng thái `draft` hoặc `pending`

### 4. Approve Company Hóa đơn (Phê duyệt)

1. Truy cập chi tiết hóa đơn có trạng thái `pending`
2. Click **Approve** hoặc **Phê duyệt**
3. Hóa đơn trạng thái chuyển sang `approved`
4. Hệ thống gửi thông báo cho Nhà cung cấp/User và Quản lý

### 5. Mark Đã thanh toán (Đánh dấu Đã thanh toán)

1. Truy cập chi tiết hóa đơn có trạng thái `approved`
2. Click **Mark Đã thanh toán** hoặc **Đánh dấu đã thanh toán**
3. Hóa đơn trạng thái chuyển sang `paid`
4. Hệ thống gửi thông báo cho Nhà cung cấp/User và Quản lý

### 6. Hủy Company Hóa đơn (Hủy)

1. Truy cập chi tiết hóa đơn cần hủy
2. Click **Hủy** hoặc **Hủy**
3. Hóa đơn trạng thái chuyển sang `cancelled`
4. Hệ thống gửi thông báo cho Nhà cung cấp/User và Quản lý

### 7. Bulk Hành động (Xử lý Hàng loạt)

1. Chọn nhiều hóa đơn trong danh sách
2. Chọn action:
   - **Approve**: Duyệt hàng loạt
   - **Hủy**: Hủy hàng loạt
   - **Mark Overdue**: Đánh dấu quá hạn hàng loạt
   - **Xóa**: Xóa hàng loạt
3. Click **Apply**
4. Hệ thống xử lý hàng loạt và hiển thị kết quả

### 8. Xem Thống kê

1. Truy cập **Company Hóa đơn** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Hóa đơn by Trạng thái: Phân bố theo trạng thái
   - Hóa đơn by Loại: Phân bố theo loại
   - Hóa đơn by Period: Phân bố theo thời gian
   - Tổng Số tiền: Tổng số tiền
   - Đã thanh toán Số tiền: Tổng số tiền đã thanh toán
   - Outstanding Số tiền: Tổng số tiền còn nợ

## Ràng buộc và điều kiện

### Validation Rules

- **Hóa đơn Loại**: Bắt buộc, phải là một trong các loại
- **Nhà cung cấp/User**: Tùy theo Hóa đơn Loại
- **Items**: Bắt buộc, ít nhất 1 item
- **Tổng Số tiền**: Phải > 0

### Business Rules

1. **Hóa đơn Loại**
   - `vendor_payment`: Thanh toán cho nhà cung cấp
   - `user_payout`: Thanh toán cho người dùng
   - `master_lease`: Hóa đơn master hợp đồng thuê
   - `ticket_cost`: Chi phí ticket
   - `deposit_refund`: Hoàn tiền cọc
   - `payroll_payslip`: Thanh toán lương

2. **Trạng thái Flow**
   - `draft` → `pending` → `approved` → `paid`
   - `pending` hoặc `approved` → `cancelled`
   - `approved` → `overdue`

3. **Bulk Hành động**
   - Chỉ áp dụng cho hóa đơn phù hợp với action
   - Hiển thị số lượng thành công và lỗi

## Ví dụ

### Ví dụ 1: Tạo Company Hóa đơn cho Nhà cung cấp

**Thông tin hóa đơn:**
- Hóa đơn Loại: `vendor_payment`
- Nhà cung cấp: Nhà cung cấp ABC
- Issue Ngày: 2025-01-15
- Đến hạn Ngày: 2025-02-15
- Items: Sửa chữa phòng - 2,000,000 VND
- Tổng Số tiền: 2,000,000 VND
- Trạng thái: `pending`

**Các bước:**
1. Truy cập Company Hóa đơn
2. Click **Tạo Company Hóa đơn**
3. Chọn Hóa đơn Loại: `vendor_payment`
4. Chọn Nhà cung cấp: Nhà cung cấp ABC
5. Điền Issue Ngày, Đến hạn Ngày
6. Thêm Item
7. Click **Lưu**
8. Hóa đơn được tạo với trạng thái `pending`

---

**Xem thêm:**
- [Quản lý Hóa đơn](./12-invoices.md)
- [Quản lý Cash Outflows](./25-cash-outflows.md)

**Cập nhật: 2025-01-XX

