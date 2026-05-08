# QUẢN LÝ HỢP ĐỒNG LƯƠNG - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý hợp đồng lương (salary hợp đồng) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, activate, terminate, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả salary hợp đồng trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `finance.access`
  - Xem salary hợp đồng của mình: Không cần capability (tự động lọc theo user_id, read-only)
  - Xem tất cả salary hợp đồng: Cần capability `finance.salary_contract.view` hoặc `finance.salary_contract.view_all`
  - Tạo/Cập nhật: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/salary-contracts`

## Các bước thực hiện

### 1. Xem danh sách Salary Hợp đồng

1. Truy cập **Salary Hợp đồng** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả salary hợp đồng trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (draft, hoạt động, terminated)
   - Người dùng (nếu có nhiều người dùng)
   - Sắp xếp theo start_date, end_date, created_at, trạng thái

### 2. Xem chi tiết Salary Hợp đồng

1. Click vào hợp đồng trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Hợp đồng ID: Mã hợp đồng
     - Người dùng: Nhân viên
     - Start Ngày, End Ngày: Ngày bắt đầu và kết thúc
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin lương:**
     - Base Salary: Lương cơ bản
     - Allowances: Các khoản phụ cấp
     - KPI Cài đặt: Cài đặt KPI (nếu có)
   - **Thông tin khác:**
     - Note: Ghi chú
     - Created At, Updated At

### 3. Tạo Salary Hợp đồng mới

1. Click **Tạo Salary Hợp đồng** hoặc **+ New**
2. Điền thông tin:
   - **Người dùng** (bắt buộc): Chọn nhân viên
   - **Start Ngày** (bắt buộc): Ngày bắt đầu
   - **End Ngày** (tùy chọn): Ngày kết thúc
   - **Base Salary** (bắt buộc): Lương cơ bản (> 0)
   - **Allowances** (tùy chọn): Các khoản phụ cấp
   - **KPI Cài đặt** (tùy chọn): Cài đặt KPI
   - **Note** (tùy chọn): Ghi chú
   - **Trạng thái** (tự động): `draft`
3. Click **Lưu**
4. Salary Hợp đồng được tạo với trạng thái `draft`

### 4. Activate Salary Hợp đồng (Kích hoạt)

1. Truy cập chi tiết hợp đồng có trạng thái `draft`
2. Click **Activate** hoặc **Kích hoạt**
3. Hợp đồng trạng thái chuyển sang `active`
4. Hệ thống gửi thông báo cho Người dùng và Quản lý

### 5. Terminate Salary Hợp đồng (Chấm dứt)

1. Truy cập chi tiết hợp đồng có trạng thái `active`
2. Click **Terminate** hoặc **Chấm dứt**
3. Nhập Termination Ngày
4. Xác nhận terminate
5. Hợp đồng trạng thái chuyển sang `terminated`
6. Hệ thống gửi thông báo cho Người dùng và Quản lý

### 6. Cập nhật Salary Hợp đồng

1. Truy cập chi tiết hợp đồng cần cập nhật
2. Click **Chỉnh sửa** (chỉ khi trạng thái = `draft`)
3. Cập nhật thông tin: Base Salary, Allowances, KPI Cài đặt, Note
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Chỉ có thể cập nhật hợp đồng có trạng thái `draft`
- Không thể cập nhật hợp đồng đã `active` hoặc `terminated`

### 7. Xóa Salary Hợp đồng

1. Truy cập chi tiết hợp đồng cần xóa
2. Click **Xóa** (chỉ khi trạng thái = `draft`)
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa hợp đồng

### 8. Xem Thống kê

1. Truy cập **Salary Hợp đồng** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Hợp đồng by Trạng thái: Phân bố theo trạng thái
   - Tổng Base Salary: Tổng lương cơ bản
   - Average Salary: Lương trung bình
   - Hợp đồng by Period: Phân bố theo thời gian

## Ràng buộc và điều kiện

### Validation Rules

- **Người dùng**: Bắt buộc, phải tồn tại và thuộc về tổ chức
- **Start Ngày**: Bắt buộc, phải là ngày hợp lệ
- **End Ngày**: Tùy chọn, phải >= Start Ngày (nếu có)
- **Base Salary**: Bắt buộc, phải > 0

### Business Rules

1. **Chỉ có 1 hợp đồng hoạt động cho mỗi người dùng tại một thời điểm**
   - Không thể activate nhiều hợp đồng cho cùng 1 người dùng
   - Phải terminate hợp đồng cũ trước khi activate hợp đồng mới

2. **Trạng thái Flow**
   - `draft` → `active` → `terminated`

3. **Activate Hợp đồng**
   - Chỉ có thể activate hợp đồng có trạng thái `draft`
   - Activate sẽ deactivate các hợp đồng khác của người dùng (nếu có)

## Ví dụ

### Ví dụ 1: Tạo và Activate Salary Hợp đồng

**Thông tin hợp đồng:**
- Người dùng: Môi giới B
- Start Ngày: 2025-01-01
- Base Salary: 10,000,000 VND
- Allowances: 1,000,000 VND
- Trạng thái: `draft`

**Các bước:**
1. Truy cập Salary Hợp đồng
2. Click **Tạo Salary Hợp đồng**
3. Chọn Người dùng: Môi giới B
4. Nhập Start Ngày: 2025-01-01
5. Nhập Base Salary: 10,000,000 VND
6. Nhập Allowances: 1,000,000 VND
7. Click **Lưu**
8. Hợp đồng được tạo với trạng thái `draft`
9. Click **Activate**
10. Hợp đồng trạng thái chuyển sang `active`

---

**Xem thêm:**
- [Quản lý Payroll Cycles](./22-payroll-cycles.md)
- [Workflow Payroll Process](../workflows/05-payroll-process.md)

**Cập nhật: 2025-01-XX

