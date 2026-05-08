# QUẢN LÝ NHÀ CUNG CẤP - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý nhà cung cấp (nhà cung cấp) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, và quản lý thông tin ngân hàng.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả nhà cung cấp trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `finance.access`
  - Xem nhà cung cấp: Cần capability `finance.vendor.view` (mặc định có thể xem)
  - Tạo/Cập nhật: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/vendors`

## Các bước thực hiện

### 1. Xem danh sách Nhà cung cấp

1. Truy cập **Nhà cung cấp** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả nhà cung cấp trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (hoạt động, không hoạt động)
   - Nhà cung cấp Loại
   - Tìm kiếm by name, tax_code, phone, email
   - Sắp xếp theo name, created_at, trạng thái

### 2. Xem chi tiết Nhà cung cấp

1. Click vào nhà cung cấp trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Name, Tax Code
     - Phone, Email, Address
     - Nhà cung cấp Loại
     - Trạng thái
   - **Thông tin ngân hàng:**
     - SePay Bank
     - Account Number, Account Holder Name
     - Branch Name, Branch Code
     - Swift Code (nếu có)
     - Banking Notes
   - **Thông tin liên hệ:**
     - Contact Person
     - Contact Phone, Contact Email
   - **Thông tin khác:**
     - Business License
     - Company Hóa đơn: Danh sách hóa đơn công ty
     - Cash Outflows: Danh sách dòng tiền chi

### 3. Tạo Nhà cung cấp mới

1. Click **Tạo Nhà cung cấp** hoặc **+ New**
2. Điền thông tin:
   - **Name** (bắt buộc): Tên nhà cung cấp
   - **Tax Code**: Mã số thuế
   - **Phone**, **Email**, **Address**
   - **Nhà cung cấp Loại**: Loại nhà cung cấp
   - **SePay Bank** (tùy chọn): Ngân hàng SePay
   - **Account Number**, **Account Holder Name**
   - **Branch Name**, **Branch Code**
   - **Swift Code** (tùy chọn)
   - **Banking Notes** (tùy chọn)
   - **Contact Person**, **Contact Phone**, **Contact Email**
   - **Business License** (tùy chọn)
   - **Trạng thái** (bắt buộc): `active` hoặc `inactive`
3. Click **Lưu**
4. Nhà cung cấp được tạo với trạng thái `active` hoặc `inactive`

### 4. Cập nhật Nhà cung cấp

1. Truy cập chi tiết nhà cung cấp cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

### 5. Xóa Nhà cung cấp

1. Truy cập chi tiết nhà cung cấp cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa nhà cung cấp

### 6. Xem Thống kê

1. Truy cập **Nhà cung cấp** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Nhà cung cấp by Trạng thái: Phân bố theo trạng thái
   - Nhà cung cấp by Loại: Phân bố theo loại
   - Tổng Thanh toán: Tổng số tiền thanh toán

## Ràng buộc và điều kiện

### Validation Rules

- **Name**: Bắt buộc, không được để trống
- **Trạng thái**: Bắt buộc, phải là `active` hoặc `inactive`

### Business Rules

1. **Nhà cung cấp Loại**
   - Phân loại nhà cung cấp theo loại (maintenance, cleaning, etc.)

2. **SePay Bank**
   - Chọn SePay Bank để thanh toán qua SePay
   - Cần thông tin tài khoản ngân hàng đầy đủ

3. **Company Hóa đơn**
   - Nhà cung cấp có thể có nhiều company hóa đơn
   - Hóa đơn Loại = `vendor_payment`

## Ví dụ

### Ví dụ 1: Tạo Nhà cung cấp

**Thông tin nhà cung cấp:**
- Name: `Vendor ABC`
- Phone: `0123456789`
- Email: `vendor@example.com`
- Nhà cung cấp Loại: `maintenance`
- Trạng thái: `active`

**Các bước:**
1. Truy cập Nhà cung cấp
2. Click **Tạo Nhà cung cấp**
3. Điền thông tin trên
4. Click **Lưu**
5. Nhà cung cấp được tạo với trạng thái `active`

---

**Xem thêm:**
- [Quản lý Company Hóa đơn](./24-company-invoices.md)
- [Quản lý Cash Outflows](./25-cash-outflows.md)

**Cập nhật: 2025-01-XX

