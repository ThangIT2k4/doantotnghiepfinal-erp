# QUẢN LÝ CHU KỲ LƯƠNG - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý chu kỳ lương (payroll cycles) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, lock, generate payslips, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả payroll cycles trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `finance.access`
  - Xem payroll cycles: Không cần capability (mặc định có thể xem, read-only)
  - Tạo/Lock/Generate Payslips: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/payroll-cycles`

## Các bước thực hiện

### 1. Xem danh sách Payroll Cycles

1. Truy cập **Payroll Cycles** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả payroll cycles trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (open, locked)
   - Period Month (YYYY-MM)
   - Year
   - Sắp xếp theo period_month, created_at, trạng thái

### 2. Xem chi tiết Payroll Cycle

1. Click vào cycle trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Cycle ID: Mã chu kỳ
     - Period Month: Tháng tính lương (YYYY-MM)
     - Trạng thái: Trạng thái hiện tại (open, locked)
   - **Thông tin payslips:**
     - Danh sách payslips đã generate
     - Tổng số payslips
     - Tổng Gross Số tiền, Deduction Số tiền, Net Số tiền
   - **Thông tin khác:**
     - Note: Ghi chú
     - Created At, Updated At

### 3. Tạo Payroll Cycle mới

1. Click **Tạo Payroll Cycle** hoặc **+ New**
2. Điền thông tin:
   - **Period Month** (bắt buộc, format YYYY-MM): Tháng tính lương (ví dụ: 2025-01)
   - **Note** (tùy chọn): Ghi chú
   - **Trạng thái** (tự động): `open`
3. Click **Lưu**
4. Payroll Cycle được tạo với trạng thái `open`

**Lưu ý**: 
- Period Month phải unique trong tổ chức
- Không thể tạo cycle cho tháng đã tồn tại

### 4. Generate Payslips (Tạo Phiếu Lương)

1. Truy cập chi tiết cycle có trạng thái `open`
2. Click **Generate Payslips** hoặc **Tạo Phiếu Lương**
3. Hệ thống tự động:
   - Lấy danh sách Người dùng có Salary Hợp đồng hoạt động trong period
   - Với mỗi người dùng:
     - Lấy Salary Hợp đồng hoạt động
     - Tính Gross Số tiền = Base Salary + Allowances
     - Lấy Salary Advances chưa trả hết
     - Tính Deduction Số tiền = Salary Advances + Other Deductions
     - Lấy Commission Events đã approve nhưng chưa thanh toán
     - Tính Net Số tiền = Gross Số tiền - Deduction Số tiền
     - Tạo Payroll Payslip với Payroll Items
4. Hệ thống hiển thị danh sách payslips đã tạo
5. Quản lý có thể review và chỉnh sửa payslips

**Lưu ý**: 
- Payslips được tạo tự động cho tất cả người dùng có hoạt động hợp đồng
- Quản lý có thể chỉnh sửa payslips trước khi lock cycle

### 5. Lock Payroll Cycle (Khóa Chu kỳ)

1. Sau khi review và chỉnh sửa payslips xong, truy cập chi tiết cycle có trạng thái `open`
2. Click **Lock Cycle** hoặc **Khóa chu kỳ**
3. Xác nhận lock
4. Payroll Cycle trạng thái chuyển sang `locked`
5. Không thể chỉnh sửa payslips nữa

**Lưu ý**: 
- Lock cycle để đảm bảo payslips không bị thay đổi
- Sau khi lock, không thể chỉnh sửa payslips

### 6. Cập nhật Payroll Cycle

1. Truy cập chi tiết cycle cần cập nhật
2. Click **Chỉnh sửa** (chỉ khi trạng thái = `open`)
3. Cập nhật thông tin: Note
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

### 7. Xóa Payroll Cycle

1. Truy cập chi tiết cycle cần xóa
2. Click **Xóa** (chỉ khi không có payslips)
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa cycle

### 8. Xem Thống kê

1. Truy cập **Payroll Cycles** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Cycles by Trạng thái: Phân bố theo trạng thái
   - Cycles by Period: Phân bố theo thời gian
   - Tổng Gross Số tiền: Tổng lương gộp
   - Tổng Deduction Số tiền: Tổng khấu trừ
   - Tổng Net Số tiền: Tổng lương thực lãnh

## Ràng buộc và điều kiện

### Validation Rules

- **Period Month**: Bắt buộc, format YYYY-MM, phải unique trong tổ chức

### Business Rules

1. **Period Month Unique**
   - Không thể tạo cycle cho tháng đã tồn tại
   - Mỗi tháng chỉ có 1 cycle

2. **Generate Payslips**
   - Chỉ generate cho người dùng có Salary Hợp đồng hoạt động
   - Tính toán tự động từ hợp đồng, advances, commissions

3. **Lock Cycle**
   - Sau khi lock, không thể chỉnh sửa payslips
   - Đảm bảo tính nhất quán của dữ liệu

## Ví dụ

### Ví dụ 1: Tạo Payroll Cycle và Generate Payslips

**Thông tin cycle:**
- Period Month: 2025-01
- Trạng thái: `open`

**Các bước:**
1. Truy cập Payroll Cycles
2. Click **Tạo Payroll Cycle**
3. Nhập Period Month: 2025-01
4. Click **Lưu**
5. Cycle được tạo với trạng thái `open`
6. Click **Generate Payslips**
7. Hệ thống tự động tạo payslips cho tất cả người dùng có hoạt động hợp đồng
8. Quản lý review và chỉnh sửa payslips nếu cần
9. Click **Lock Cycle**
10. Cycle trạng thái chuyển sang `locked`

---

**Xem thêm:**
- [Quản lý Payroll Payslips](./23-payroll-payslips.md)
- [Quản lý Salary Hợp đồng](./20-salary-contracts.md)
- [Workflow Payroll Process](../workflows/05-payroll-process.md)

**Cập nhật: 2025-01-XX

