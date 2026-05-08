# QUẢN LÝ HỒ SƠ - TENANT

## Tổng quan

Chức năng này cho phép Khách thuê quản lý thông tin hồ sơ cá nhân, bao gồm xem, cập nhật thông tin, và xác thực email.

## Quyền truy cập

- **Khách thuê**: Có quyền quản lý hồ sơ của chính mình

**Route**: `/tenant/profile`

## Các bước thực hiện

### 1. Xem Hồ sơ

1. Truy cập **Hồ sơ** từ menu Khách thuê
2. Hệ thống hiển thị thông tin hồ sơ:
   - Thông tin cơ bản: Full Name, Email, Phone
   - Email Verification Trạng thái
   - Thông tin bổ sung (nếu có)
   - Ngày tạo tài khoản
   - Last Đăng nhập

### 2. Cập nhật Thông tin

1. Click **Chỉnh sửa** hoặc **Cập nhật**
2. Cập nhật thông tin cần thay đổi:
   - **Full Name**: Họ và tên
   - **Phone**: Số điện thoại
   - **Address**: Địa chỉ (nếu có)
   - **Avatar**: Ảnh đại diện (nếu có)
3. Click **Lưu**
4. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Không thể sửa Email sau khi đã xác thực
- Phone phải unique trong hệ thống
- Phone phải đúng format số điện thoại

### 3. Xác thực Email (Email Verification)

#### 3.1. Kiểm tra trạng thái xác thực

1. Truy cập Hồ sơ
2. Xem **Email Verification Trạng thái**:
   - **Verified**: Email đã được xác thực
   - **Not Verified**: Email chưa được xác thực

#### 3.2. Gửi OTP xác thực

1. Nếu email chưa được xác thực, click **Verify Email** hoặc **Gửi OTP**
2. Hệ thống gửi OTP (6 chữ số) qua email
3. Chờ nhận email OTP

#### 3.3. Nhập OTP xác thực

1. Truy cập trang xác thực email: `/tenant/profile/otp-verification`
2. Nhập **OTP** đã nhận từ email
3. Click **Verify** hoặc **Xác thực**
4. Nếu OTP đúng và chưa hết hạn:
   - Hệ thống đánh dấu email đã xác thực
   - Hiển thị thông báo thành công
   - Email Verification Trạng thái chuyển sang `Verified`

#### 3.4. Resend OTP

1. Nếu không nhận được OTP hoặc OTP hết hạn, click **Gửi lại OTP**
2. Hệ thống gửi OTP mới qua email
3. Nhập OTP mới và xác thực

### 4. Thay đổi Email

1. Truy cập Hồ sơ
2. Click **Change Email** (nếu email chưa được xác thực)
3. Nhập **Email mới**
4. Xác nhận email mới
5. Click **Lưu**
6. Hệ thống gửi OTP qua email mới
7. Nhập OTP để xác thực email mới

**Lưu ý**: 
- Chỉ có thể thay đổi email nếu email cũ chưa được xác thực
- Email mới phải unique trong hệ thống
- Email mới phải đúng format

### 5. Thay đổi Mật khẩu

1. Truy cập Hồ sơ
2. Click **Change Password**
3. Nhập thông tin:
   - **Current Password**: Mật khẩu hiện tại
   - **New Password**: Mật khẩu mới (tối thiểu 8 ký tự)
   - **Confirm New Password**: Xác nhận mật khẩu mới
4. Click **Lưu**
5. Hệ thống cập nhật mật khẩu và hiển thị thông báo thành công

**Lưu ý**: 
- Mật khẩu mới phải khác mật khẩu cũ
- Mật khẩu mới phải tối thiểu 8 ký tự
- Nên sử dụng mật khẩu mạnh (có chữ hoa, chữ thường, số, ký tự đặc biệt)

## Ràng buộc và điều kiện

### Validation Rules

- **Full Name**: 
  - Bắt buộc
  - Không được để trống
- **Email**: 
  - Bắt buộc
  - Phải unique trong hệ thống (nếu thay đổi)
  - Phải đúng format email (RFC 5322)
  - Không thể sửa sau khi đã xác thực
- **Phone**: 
  - Bắt buộc
  - Phải unique trong hệ thống
  - Phải đúng format số điện thoại
- **Password**: 
  - Tối thiểu 8 ký tự (khi thay đổi)
  - Phải khác mật khẩu cũ
  - Nên có chữ hoa, chữ thường, số, ký tự đặc biệt

### Email Verification Rules

- **OTP**: 
  - 6 chữ số
  - Có thời gian hết hạn (thường 15 phút)
  - Mỗi OTP chỉ có thể sử dụng 1 lần
- **Resend OTP**: 
  - Có thể resend sau một khoảng thời gian nhất định
  - Mỗi lần resend sẽ tạo OTP mới

## Trạng thái và Workflow

### Email Verification Trạng thái Flow

```
Not Verified → Verified
```

- **Not Verified**: Email chưa được xác thực
- **Verified**: Email đã được xác thực

### Workflow Xác thực Email

1. Khách thuê gửi OTP qua email
2. Hệ thống tạo OTP và gửi email
3. Khách thuê nhập OTP
4. Hệ thống kiểm tra OTP
5. Nếu đúng và chưa hết hạn, đánh dấu email đã xác thực

## Ví dụ

### Ví dụ 1: Cập nhật Thông tin

**Thông tin hiện tại:**
- Full Name: `Nguyễn Văn A`
- Phone: `0123456789`
- Email: `nguyenvana@example.com` (Verified)

**Cập nhật:**
- Full Name: `Nguyễn Văn An`
- Phone: `0987654321` (thay đổi)

**Các bước:**
1. Truy cập Hồ sơ
2. Click **Chỉnh sửa**
3. Cập nhật Full Name và Phone
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

### Ví dụ 2: Xác thực Email

**Kịch bản:** Khách thuê chưa xác thực email

**Các bước:**
1. Truy cập Hồ sơ
2. Xem Email Verification Trạng thái: `Not Verified`
3. Click **Verify Email**
4. Hệ thống gửi OTP qua email: `123456`
5. Truy cập trang xác thực email
6. Nhập OTP: `123456`
7. Click **Verify**
8. Hệ thống đánh dấu email đã xác thực
9. Email Verification Trạng thái chuyển sang `Verified`

## Lưu ý

1. **Email Verification**
   - Phải xác thực email để sử dụng đầy đủ chức năng
   - Kiểm tra thư mục spam nếu không nhận được email
   - OTP có thời gian hết hạn (15 phút)

2. **Bảo mật Thông tin**
   - Không chia sẻ thông tin với người khác
   - Sử dụng mật khẩu mạnh
   - Đổi mật khẩu định kỳ

3. **Phone Unique**
   - Phone phải unique trong hệ thống
   - Không thể sử dụng phone đã được sử dụng bởi người dùng khác

4. **Email Unique**
   - Email phải unique trong hệ thống
   - Không thể sử dụng email đã được sử dụng bởi người dùng khác

## Troubleshooting

### Không nhận được OTP

1. Kiểm tra thư mục spam
2. Đợi vài phút (email có thể bị delay)
3. Sử dụng chức năng "Gửi lại OTP"
4. Kiểm tra email đã nhập đúng chưa
5. Liên hệ hỗ trợ nếu vẫn không nhận được

### OTP hết hạn

1. Sử dụng chức năng "Gửi lại OTP"
2. Nhập OTP mới trong thời gian hiệu lực

### Không thể cập nhật thông tin

1. Kiểm tra tất cả các trường bắt buộc đã điền chưa
2. Kiểm tra Phone có bị trùng không
3. Kiểm tra Email có bị trùng không (nếu thay đổi)
4. Kiểm tra format Phone và Email có đúng không
5. Liên hệ hỗ trợ nếu vẫn không thể cập nhật

### Không thể thay đổi email

1. Kiểm tra email cũ đã được xác thực chưa
2. Chỉ có thể thay đổi email nếu email cũ chưa được xác thực
3. Liên hệ hỗ trợ nếu cần thay đổi email đã xác thực

---

**Lưu ý**: Quản lý hồ sơ giúp Khách thuê cập nhật thông tin cá nhân và xác thực email để sử dụng đầy đủ chức năng.

**Cập nhật**: 2025-11-02

