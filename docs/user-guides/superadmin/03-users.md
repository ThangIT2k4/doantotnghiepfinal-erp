# QUẢN LÝ NGƯỜI DÙNG - SUPERADMIN

## Tổng quan

Chức năng này cho phép SuperAdmin quản lý tất cả người dùng trong hệ thống, bao gồm tạo, xem, cập nhật, xóa, và toggle trạng thái của người dùng.

## Quyền truy cập

- **SuperAdmin**: Có quyền quản lý tất cả người dùng trong hệ thống

**Route**: `/superadmin/users`

## Các bước thực hiện

### 1. Xem danh sách người dùng

1. Truy cập **Người dùng** từ menu SuperAdmin
2. Hệ thống hiển thị danh sách tất cả người dùng
3. Có thể lọc theo:
   - Role (SuperAdmin, Quản lý, Môi giới, Landlord, Khách thuê)
   - Trạng thái (hoạt động/inactive)
   - Tổ chức
   - Tìm kiếm theo name, email, phone
   - Sắp xếp theo name, email, created_at, trạng thái

### 2. Xem chi tiết người dùng

1. Click vào tên người dùng hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - Thông tin cơ bản (name, email, phone)
   - Trạng thái
   - Email verified
   - Last đăng nhập
   - Organizations và roles của người dùng
   - Hồ sơ information

### 3. Tạo người dùng mới

1. Click **Tạo Người dùng** hoặc **+ New**
2. Điền thông tin:
   - **Email** (bắt buộc, unique, đúng format): Email của người dùng
   - **Phone** (bắt buộc, unique, đúng format): Số điện thoại
   - **Password** (bắt buộc, tối thiểu 8 ký tự): Mật khẩu
   - **Full Name** (bắt buộc): Họ và tên
   - **Trạng thái** (bắt buộc): hoạt động hoặc không hoạt động
3. Click **Lưu**
4. Hệ thống tạo người dùng và hiển thị thông báo thành công

**Lưu ý**: Người dùng mới tạo sẽ cần xác thực email nếu email verification được yêu cầu.

### 4. Cập nhật thông tin người dùng

1. Truy cập chi tiết người dùng cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin cần thay đổi:
   - Full Name
   - Email (nếu chưa được xác thực)
   - Phone
   - Trạng thái
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Không thể sửa email nếu đã được xác thực
- Phone phải unique
- Email phải unique và đúng format

### 5. Xóa người dùng (Soft Xóa)

1. Truy cập chi tiết người dùng cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa (không xóa vĩnh viễn)

**Lưu ý**: 
- Soft xóa chỉ đánh dấu người dùng là deleted, không xóa dữ liệu
- Dữ liệu vẫn được lưu trong database
- Có thể restore sau nếu cần

### 6. Toggle Trạng thái (Kích hoạt/Vô hiệu hóa)

1. Truy cập danh sách người dùng hoặc chi tiết người dùng
2. Click **Toggle Trạng thái** hoặc switch **Trạng thái**
3. Hệ thống cập nhật trạng thái:
   - `active` → `inactive`
   - `inactive` → `active`

**Lưu ý**: 
- Người dùng không hoạt động không thể đăng nhập
- Dữ liệu vẫn được giữ nguyên

### 7. Xem Organizations và Roles của Người dùng

1. Truy cập chi tiết người dùng
2. Click tab **Organizations** hoặc scroll đến phần **Organizations**
3. Hệ thống hiển thị:
   - Danh sách organizations người dùng thuộc
   - Role trong mỗi tổ chức
   - Trạng thái trong mỗi tổ chức
   - Ngày tham gia

### 8. Đặt lại Password

1. Truy cập chi tiết người dùng
2. Click **Đặt lại Password**
3. Nhập mật khẩu mới
4. Xác nhận mật khẩu mới
5. Click **Lưu**
6. Hệ thống cập nhật mật khẩu và gửi email thông báo cho người dùng

**Lưu ý**: 
- Người dùng sẽ nhận email thông báo mật khẩu mới
- Người dùng cần đăng nhập lại với mật khẩu mới

## Ràng buộc và điều kiện

### Validation Rules

- **Email**: 
  - Bắt buộc
  - Phải unique trong hệ thống
  - Phải đúng format email (RFC 5322)
- **Phone**: 
  - Bắt buộc
  - Phải unique trong hệ thống
  - Phải đúng format số điện thoại
- **Password**: 
  - Bắt buộc (khi tạo mới)
  - Tối thiểu 8 ký tự
  - Nên có chữ hoa, chữ thường, số, và ký tự đặc biệt
- **Full Name**: 
  - Bắt buộc
  - Không được để trống
- **Trạng thái**: 
  - Bắt buộc
  - Phải là `active` hoặc `inactive`

### Business Rules

1. **Không thể xóa người dùng có dữ liệu quan trọng**
   - Hợp đồng thuê, Hóa đơn, Thanh toán, Tickets
   - Hệ thống chỉ thực hiện soft xóa

2. **Người dùng có thể thuộc nhiều organizations**
   - Mỗi tổ chức có role riêng
   - Trạng thái trong mỗi tổ chức độc lập

3. **Email Verification**
   - Người dùng mới tạo cần xác thực email
   - Email đã xác thực không thể sửa

## Trạng thái và Workflow

### Trạng thái Flow

```
active ←→ inactive
```

- **hoạt động**: Người dùng có thể đăng nhập và sử dụng hệ thống
- **không hoạt động**: Người dùng không thể đăng nhập

### Workflow Tạo Người dùng

1. SuperAdmin tạo người dùng mới
2. Điền thông tin bắt buộc (Email, Phone, Password, Full Name)
3. Set trạng thái = `active`
4. Hệ thống tạo người dùng
5. Hệ thống gửi email xác thực (nếu được yêu cầu)
6. Người dùng cần xác thực email
7. Có thể assign người dùng vào tổ chức và gán role

## Ví dụ

### Ví dụ 1: Tạo người dùng mới

**Thông tin người dùng:**
- Email: `manager@example.com`
- Phone: `0123456789`
- Password: `SecurePass123!`
- Full Name: `Nguyễn Văn A`
- Trạng thái: `active`

**Các bước:**
1. Click **Tạo Người dùng**
2. Điền thông tin trên
3. Click **Lưu**
4. Hệ thống tạo người dùng với ID tự động
5. Gửi email xác thực cho người dùng

### Ví dụ 2: Toggle Trạng thái

**Kịch bản:** Vô hiệu hóa người dùng tạm thời

**Các bước:**
1. Truy cập danh sách người dùng
2. Tìm người dùng cần vô hiệu hóa
3. Click switch **Trạng thái** để chuyển từ `active` sang `inactive`
4. Xác nhận
5. Người dùng không thể đăng nhập

## Lưu ý

1. **Email và Phone là unique**
   - Mỗi người dùng phải có email và phone riêng
   - Không thể tạo 2 người dùng cùng email hoặc phone

2. **Soft Xóa**
   - Xóa người dùng chỉ đánh dấu deleted, không xóa dữ liệu
   - Dữ liệu vẫn được giữ để audit trail
   - Có thể restore sau nếu cần

3. **Multi-Tổ chức**
   - Người dùng có thể thuộc nhiều organizations
   - Mỗi tổ chức có role và trạng thái riêng
   - SuperAdmin có thể quản lý tất cả

4. **Password Security**
   - Không chia sẻ mật khẩu với người khác
   - Nên yêu cầu người dùng đổi mật khẩu sau lần đầu đăng nhập
   - Sử dụng mật khẩu mạnh

5. **Email Verification**
   - Người dùng mới tạo cần xác thực email
   - Email đã xác thực không thể sửa
   - Có thể resend email verification

## Troubleshooting

### Không thể tạo người dùng mới

1. Kiểm tra Email có bị trùng không
2. Kiểm tra Phone có bị trùng không
3. Kiểm tra Email và Phone có đúng format không
4. Kiểm tra tất cả các trường bắt buộc đã điền chưa
5. Kiểm tra Password có đủ 8 ký tự không
6. Liên hệ quản trị viên hệ thống

### Người dùng không thể đăng nhập

1. Kiểm tra trạng thái của người dùng (phải là `active`)
2. Kiểm tra người dùng có thuộc tổ chức không
3. Kiểm tra người dùng có role phù hợp không
4. Kiểm tra email đã được xác thực chưa
5. Kiểm tra password có đúng không

### Không thể xóa người dùng

1. Kiểm tra người dùng có dữ liệu quan trọng không
2. Hệ thống chỉ hỗ trợ soft xóa
3. Nếu cần xóa vĩnh viễn, liên hệ quản trị viên hệ thống

### Email verification không hoạt động

1. Kiểm tra email đã được gửi chưa
2. Kiểm tra thư mục spam
3. Resend email verification
4. Kiểm tra email service có hoạt động không

---

**Lưu ý**: SuperAdmin có quyền quản lý tất cả người dùng trong hệ thống. Cần cẩn thận khi thực hiện các thao tác quan trọng như xóa hoặc vô hiệu hóa người dùng.

**Cập nhật**: 2025-11-02

