# QUẢN LÝ PHIẾU LƯƠNG - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý phiếu lương (payroll payslips) trong tổ chức, bao gồm xem, recalculate, mark đã thanh toán, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ quản lý tất cả payroll payslips trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `finance.access`
  - Xem payslip của mình: Không cần capability (tự động lọc theo user_id, read-only)
  - Xem tất cả payslips: Cần capability `finance.payroll_payslip.view` hoặc `finance.payroll_payslip.view_all`
  - Recalculate/Mark Đã thanh toán: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/payroll-payslips`

## Các bước thực hiện

### 1. Xem danh sách Payroll Payslips

1. Truy cập **Payroll Payslips** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả payroll payslips trong tổ chức
3. Có thể lọc theo:
   - Payroll Cycle (nếu có nhiều cycles)
   - Người dùng (nếu có nhiều người dùng)
   - Trạng thái (đang chờ, đã thanh toán)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo created_at, net_amount, trạng thái

### 2. Xem chi tiết Payroll Payslip

1. Click vào payslip trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Payslip ID: Mã phiếu lương
     - Payroll Cycle: Chu kỳ lương
     - Người dùng: Nhân viên
     - Period Month: Tháng tính lương
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin tài chính:**
     - Gross Số tiền: Lương gộp (Base Salary + Allowances)
     - Deduction Số tiền: Khấu trừ (Advances + Other Deductions)
     - Net Số tiền: Lương thực lãnh (Gross - Deductions)
   - **Payroll Items:**
     - Base Salary: Lương cơ bản
     - Allowances: Phụ cấp
     - Advances: Tạm ứng
     - Commissions: Hoa hồng
     - Other Deductions: Khấu trừ khác
   - **Thông tin khác:**
     - Created At, Updated At

### 3. Recalculate Payslip (Tính lại)

1. Truy cập chi tiết payslip cần recalculate
2. Click **Recalculate** hoặc **Tính lại** (chỉ khi cycle trạng thái = `open`)
3. Hệ thống tự động tính lại:
   - Gross Số tiền = Base Salary + Allowances (từ Salary Hợp đồng)
   - Deduction Số tiền = Salary Advances + Other Deductions
   - Net Số tiền = Gross Số tiền - Deduction Số tiền
4. Payslip được cập nhật với số liệu mới

**Lưu ý**: 
- Chỉ có thể recalculate payslip khi cycle trạng thái = `open`
- Không thể recalculate payslip khi cycle đã `locked`

### 4. Mark Đã thanh toán (Đánh dấu Đã thanh toán)

1. Truy cập chi tiết payslip cần mark đã thanh toán
2. Click **Mark Đã thanh toán** hoặc **Đánh dấu đã thanh toán** (chỉ khi cycle trạng thái = `locked`)
3. Payslip trạng thái chuyển sang `paid`
4. Hệ thống cập nhật:
   - Commission Events trạng thái = `paid`
   - Salary Advances trạng thái = `repaid`
   - Company Hóa đơn trạng thái = `paid` (nếu có)
5. Hệ thống tạo Cash Outflow record
6. Hệ thống gửi thông báo cho Người dùng và Quản lý

**Lưu ý**: 
- Chỉ có thể mark đã thanh toán payslip khi cycle trạng thái = `locked`
- Mark đã thanh toán sẽ tự động cập nhật related records

### 5. Xem Thống kê

1. Truy cập **Payroll Payslips** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Payslips by Trạng thái: Phân bố theo trạng thái
   - Payslips by Period: Phân bố theo thời gian
   - Tổng Gross Số tiền: Tổng lương gộp
   - Tổng Deduction Số tiền: Tổng khấu trừ
   - Tổng Net Số tiền: Tổng lương thực lãnh
   - Đã thanh toán Số tiền: Tổng lương đã thanh toán

## Ràng buộc và điều kiện

### Validation Rules

- **Payroll Payslip**: Phải tồn tại và thuộc về tổ chức
- **Cycle Trạng thái**: Phải là `open` để recalculate, `locked` để mark đã thanh toán

### Business Rules

1. **Recalculate Payslip**
   - Chỉ có thể recalculate khi cycle trạng thái = `open`
   - Tính toán lại từ Salary Hợp đồng, Advances, Commissions

2. **Mark Đã thanh toán**
   - Chỉ có thể mark đã thanh toán khi cycle trạng thái = `locked`
   - Tự động cập nhật related records

3. **Trạng thái Flow**
   - `pending` → `paid`

## Ví dụ

### Ví dụ 1: Recalculate và Mark Đã thanh toán Payslip

**Payslip:**
- Người dùng: Môi giới B
- Gross Số tiền: 11,000,000 VND (Base Salary + Allowances)
- Deduction Số tiền: 1,000,000 VND (Advances)
- Net Số tiền: 10,000,000 VND
- Trạng thái: `pending`

**Các bước:**
1. Truy cập chi tiết Payslip
2. Click **Recalculate** (nếu cycle trạng thái = `open`)
3. Hệ thống tính lại số liệu
4. Sau khi lock cycle, click **Mark Đã thanh toán**
5. Payslip trạng thái chuyển sang `paid`
6. Hệ thống cập nhật related records

---

**Xem thêm:**
- [Quản lý Payroll Cycles](./22-payroll-cycles.md)
- [Môi giới Payslips](../agent/21-payslips.md)
- [Workflow Payroll Process](../workflows/05-payroll-process.md)

**Cập nhật: 2025-01-XX

