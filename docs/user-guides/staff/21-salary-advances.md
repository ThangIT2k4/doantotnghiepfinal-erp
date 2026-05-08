# QUẢN LÝ TẠM ỨNG LƯƠNG - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý tạm ứng lương (salary advances) trong tổ chức, bao gồm xem, approve, reject, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ quản lý tất cả salary advances trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `finance.access`
  - Tạo request tạm ứng lương: Không cần capability (có thể tạo cho chính mình)
  - Xem salary advances của mình: Không cần capability (tự động lọc theo user_id)
  - Xem tất cả salary advances: Cần capability `finance.salary_advance.view` hoặc `finance.salary_advance.view_all`
  - Approve/Reject: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/salary-advances`

## Các bước thực hiện

### 1. Xem danh sách Salary Advances

1. Truy cập **Salary Advances** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả salary advances trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (đang chờ, đã phê duyệt, từ chối, repaid)
   - Người dùng (nếu có nhiều người dùng)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo created_at, số tiền, trạng thái

### 2. Xem chi tiết Salary Advance

1. Click vào advance trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Advance ID: Mã tạm ứng
     - Người dùng: Nhân viên
     - Số tiền: Số tiền tạm ứng
     - Advance Ngày: Ngày tạm ứng
     - Expected Repayment Ngày: Ngày dự kiến hoàn trả
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin khác:**
     - Reason: Lý do tạm ứng
     - Repayment Method: Phương thức hoàn trả
     - Created At, Updated At

### 3. Approve Salary Advance (Phê duyệt)

1. Truy cập chi tiết advance có trạng thái `pending`
2. Review thông tin advance
3. Click **Approve** hoặc **Phê duyệt**
4. Advance trạng thái chuyển sang `approved`
5. Hệ thống gửi thông báo cho Người dùng và Quản lý

### 4. Reject Salary Advance (Từ chối)

1. Truy cập chi tiết advance có trạng thái `pending`
2. Click **Reject** hoặc **Từ chối**
3. (Tùy chọn) Nhập lý do từ chối
4. Xác nhận reject
5. Advance trạng thái chuyển sang `rejected`
6. Hệ thống gửi thông báo cho Người dùng và Quản lý

### 5. Xem Thống kê

1. Truy cập **Salary Advances** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Advances by Trạng thái: Phân bố theo trạng thái
   - Advances by Period: Phân bố theo thời gian
   - Tổng Advance Số tiền: Tổng số tiền tạm ứng
   - Đang chờ Số tiền: Tổng số tiền đang chờ approve
   - Repaid Số tiền: Tổng số tiền đã hoàn trả

## Ràng buộc và điều kiện

### Validation Rules

- **Salary Advance**: Phải tồn tại và thuộc về tổ chức
- **Trạng thái**: Phải là `pending` để approve/reject

### Business Rules

1. **Trạng thái Flow**
   - `pending` → `approved` hoặc `rejected`
   - `approved` → `repaid`

2. **Repayment**
   - Salary Advance được trả qua Payroll Payslip
   - Số tiền được trừ vào Deduction Số tiền

## Ví dụ

### Ví dụ 1: Approve Salary Advance

**Thông tin advance:**
- Người dùng: Môi giới B
- Số tiền: 5,000,000 VND
- Reason: "Cần tiền gấp cho gia đình"
- Trạng thái: `pending`

**Các bước:**
1. Truy cập Salary Advances
2. Click vào advance cần approve
3. Review thông tin advance
4. Click **Approve**
5. Advance trạng thái chuyển sang `approved`
6. Hệ thống gửi thông báo cho Môi giới B

---

**Xem thêm:**
- [Môi giới Salary Advances](../agent/22-salary-advances.md)
- [Quản lý Payroll Payslips](./23-payroll-payslips.md)

**Cập nhật: 2025-01-XX

