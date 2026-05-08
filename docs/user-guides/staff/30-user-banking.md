# QUẢN LÝ THÔNG TIN NGÂN HÀNG - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý thông tin ngân hàng (người dùng banking) cho người dùng trong tổ chức, bao gồm tạo, xem, cập nhật, xóa.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả người dùng banking trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `party.access`
  - Xem/Quản lý thông tin ngân hàng của mình: Không cần capability (tự động lọc theo user_id)
  - Quản lý thông tin ngân hàng của người dùng khác: Cần capability `party.user_banking.update` (chỉ Quản lý)

**Route**: `/staff/user-banking`

## Các bước thực hiện

### 1. Xem danh sách Người dùng Banking

1. Truy cập **Người dùng Banking** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả người dùng banking trong tổ chức
3. Có thể lọc theo:
   - Người dùng (nếu có nhiều người dùng)
   - SePay Bank
   - Trạng thái (hoạt động, không hoạt động)
   - Sắp xếp theo created_at, updated_at

### 2. Xem chi tiết Người dùng Banking

1. Click vào banking trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Banking ID: Mã thông tin ngân hàng
     - Người dùng: Người dùng
     - SePay Bank: Ngân hàng SePay
     - Account Number: Số tài khoản
     - Account Holder Name: Tên chủ tài khoản
     - Branch Name, Branch Code: Chi nhánh
     - Swift Code (nếu có)
     - Notes (nếu có)
     - Trạng thái: Trạng thái hiện tại

### 3. Tạo Người dùng Banking mới

1. Click **Tạo Người dùng Banking** hoặc **+ New**
2. Điền thông tin:
   - **Người dùng** (bắt buộc): Chọn người dùng
   - **SePay Bank** (bắt buộc): Chọn ngân hàng SePay
   - **Account Number** (bắt buộc): Số tài khoản
   - **Account Holder Name** (bắt buộc): Tên chủ tài khoản
   - **Branch Name**: Tên chi nhánh
   - **Branch Code**: Mã chi nhánh
   - **Swift Code** (tùy chọn): Swift Code
   - **Notes** (tùy chọn): Ghi chú
   - **Trạng thái** (tự động): `active`
3. Click **Lưu**
4. Người dùng Banking được tạo với trạng thái `active`

### 4. Cập nhật Người dùng Banking

1. Truy cập chi tiết banking cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin: SePay Bank, Account Number, Account Holder Name, Branch Name, Branch Code, Swift Code, Notes
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

### 5. Xóa Người dùng Banking

1. Truy cập chi tiết banking cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa banking

### 6. Xem Thống kê

1. Truy cập **Người dùng Banking** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Tổng Banking: Tổng số thông tin ngân hàng
   - Banking by SePay Bank: Phân bố theo ngân hàng
   - Hoạt động Banking: Số thông tin ngân hàng hoạt động

## Ràng buộc và điều kiện

### Validation Rules

- **Người dùng**: Bắt buộc, phải tồn tại và thuộc về tổ chức
- **SePay Bank**: Bắt buộc, phải tồn tại
- **Account Number**: Bắt buộc, không được để trống
- **Account Holder Name**: Bắt buộc, không được để trống

### Business Rules

1. **Usage**
   - Người dùng Banking dùng cho thanh toán qua SePay
   - Dùng cho payroll (nhận lương)
   - Dùng cho commission thanh toán

2. **Trạng thái**
   - `active`: Thông tin ngân hàng đang hoạt động
   - `inactive`: Thông tin ngân hàng không hoạt động

3. **Multiple Banking**
   - Người dùng có thể có nhiều thông tin ngân hàng
   - Có thể chọn banking account khi thanh toán

## Ví dụ

### Ví dụ 1: Tạo Người dùng Banking cho Môi giới

**Thông tin banking:**
- Người dùng: Môi giới B
- SePay Bank: Vietcombank
- Account Number: `1234567890`
- Account Holder Name: `NGUYEN VAN B`
- Branch Name: `Chi nhánh ABC`
- Branch Code: `001`
- Trạng thái: `active`

**Các bước:**
1. Truy cập Người dùng Banking
2. Click **Tạo Người dùng Banking**
3. Chọn Người dùng: Môi giới B
4. Chọn SePay Bank: Vietcombank
5. Nhập Account Number: `1234567890`
6. Nhập Account Holder Name: `NGUYEN VAN B`
7. Nhập Branch Name: `Chi nhánh ABC`
8. Nhập Branch Code: `001`
9. Click **Lưu**
10. Người dùng Banking được tạo với trạng thái `active`

---

**Xem thêm:**
- [Môi giới Người dùng Banking](../agent/25-user-banking.md)
- [Khách thuê Người dùng Banking](../tenant/04-user-banking.md)

**Cập nhật: 2025-01-XX

