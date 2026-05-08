# QUẢN LÝ NGƯỜI DÙNG - STAFF

## Tổng quan

Chức năng này cho phép Quản lý quản lý người dùng (người dùng) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, và toggle trạng thái.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả người dùng trong tổ chức (wildcard `*` = true)
- **Môi giới**: Không có quyền truy cập chức năng này (chỉ Quản lý)

**Route**: `/staff/users`

## Các bước thực hiện

### 1. Xem danh sách Người dùng

1. Truy cập **Người dùng** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả người dùng trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (hoạt động, không hoạt động)
   - Role (quản lý, môi giới, khách thuê, landlord)
   - Tìm kiếm by name, phone, email
   - Sắp xếp theo name, created_at, trạng thái

### 2. Xem chi tiết Người dùng

1. Click vào người dùng trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Người dùng ID, Name, Phone, Email
     - Role, Trạng thái
     - Email Verified
   - **Thông tin liên quan:**
     - Hợp đồng thuê: Hợp đồng (nếu có)
     - Hóa đơn: Hóa đơn (nếu có)
     - Thanh toán: Thanh toán (nếu có)
     - Tickets: Tickets (nếu có)
     - Capabilities: Quyền hạn (nếu có)

### 3. Tạo Người dùng mới

1. Click **Tạo Người dùng** hoặc **+ New**
2. Điền thông tin:
   - **Name** (bắt buộc): Họ và tên
   - **Phone** (bắt buộc, unique): Số điện thoại
   - **Email** (tùy chọn, unique nếu có): Email
   - **Password** (bắt buộc): Mật khẩu
   - **Role** (bắt buộc): quản lý, môi giới, khách thuê, landlord
   - **Trạng thái** (bắt buộc): `active` hoặc `inactive`
   - **Tổ chức** (tự động): Tổ chức hiện tại
3. Click **Lưu**
4. Người dùng được tạo với role và trạng thái tương ứng
5. Hệ thống gửi email verification (nếu có email)

### 4. Cập nhật Người dùng

1. Truy cập chi tiết người dùng cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin: Name, Phone, Email, Trạng thái
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

### 5. Toggle Trạng thái (Bật/Tắt)

1. Truy cập chi tiết người dùng cần toggle trạng thái
2. Click **Toggle Trạng thái** hoặc **Bật/Tắt**
3. Người dùng trạng thái chuyển sang `active` hoặc `inactive`
4. Hệ thống gửi thông báo cho người dùng (nếu có)

### 6. Xóa Người dùng

1. Truy cập chi tiết người dùng cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa người dùng

### 7. Xem Thống kê

1. Truy cập **Người dùng** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Người dùng by Role: Phân bố theo vai trò
   - Người dùng by Trạng thái: Phân bố theo trạng thái
   - Tổng Người dùng: Tổng số người dùng

## Ràng buộc và điều kiện

### Validation Rules

- **Name**: Bắt buộc, không được để trống
- **Phone**: Bắt buộc, phải unique trong hệ thống
- **Email**: Tùy chọn, phải unique trong hệ thống (nếu có)
- **Password**: Bắt buộc, tối thiểu 8 ký tự
- **Role**: Bắt buộc, phải là một trong: quản lý, môi giới, khách thuê, landlord

### Business Rules

1. **Phone Unique**
   - Phone phải unique trong hệ thống
   - Không thể có 2 người dùng cùng phone

2. **Email Unique**
   - Email phải unique trong hệ thống (nếu có)
   - Không thể có 2 người dùng cùng email

3. **Trạng thái**
   - `active`: Người dùng đang hoạt động
   - `inactive`: Người dùng không hoạt động (tạm dừng)

## Ví dụ

### Ví dụ 1: Tạo Người dùng

**Thông tin người dùng:**
- Name: `Nguyễn Văn A`
- Phone: `0123456789`
- Email: `nguyenvana@example.com`
- Password: `password123`
- Role: `agent`
- Trạng thái: `active`

**Các bước:**
1. Truy cập Người dùng
2. Click **Tạo Người dùng**
3. Điền thông tin trên
4. Click **Lưu**
5. Người dùng được tạo với role `agent` và trạng thái `active`

---

**Xem thêm:**
- [Quản lý Capabilities](./29-capabilities.md)
- [Quản lý Nhân viên](./27-staff.md)

**Cập nhật: 2025-01-XX

