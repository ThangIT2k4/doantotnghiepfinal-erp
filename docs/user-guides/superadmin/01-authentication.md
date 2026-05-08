# ĐĂNG NHẬP VÀ ĐĂNG XUẤT - SUPERADMIN

## Tổng quan

Tài liệu này hướng dẫn SuperAdmin đăng nhập và đăng xuất khỏi hệ thống Quản lý Bất động sản Cho thuê.

## Quyền truy cập

- **SuperAdmin**: Có quyền đăng nhập và đăng xuất

## Đăng nhập

### Phương thức đăng nhập

Hệ thống hỗ trợ 2 phương thức đăng nhập:

1. **Email + Password**: Đăng nhập bằng email và mật khẩu
2. **Google OAuth**: Đăng nhập bằng tài khoản Google

### Các bước đăng nhập bằng Email + Password

1. Truy cập trang đăng nhập: `/login`
2. Nhập **Email** hoặc **Số điện thoại** của tài khoản SuperAdmin
3. Nhập **Mật khẩu**
4. (Tùy chọn) Chọn **Remember me** để lưu phiên đăng nhập
5. Click **Đăng nhập**

### Các bước đăng nhập bằng Google OAuth

1. Truy cập trang đăng nhập: `/login`
2. Click **Đăng nhập bằng Google**
3. Chọn tài khoản Google
4. Cho phép ứng dụng truy cập thông tin (nếu lần đầu)
5. Hệ thống tự động đăng nhập

### Xử lý sau khi đăng nhập

Sau khi đăng nhập thành công:

1. Hệ thống kiểm tra role của người dùng
2. Redirect đến bảng điều khiển tương ứng:
   - SuperAdmin → `/superadmin/dashboard`
3. Track last đăng nhập thời gian
4. Tạo session cho người dùng

### Ràng buộc và điều kiện

#### Validation Rules

- **Email/Phone**: 
  - Phải tồn tại trong hệ thống
  - Phải có trạng thái `active`
  - Email phải đúng format (nếu dùng email)
- **Password**: 
  - Phải đúng với mật khẩu đã lưu
  - Password được hash bằng bcrypt
- **Người dùng Trạng thái**: 
  - Người dùng phải có trạng thái `active`
  - Người dùng phải có role `SuperAdmin` trong hệ thống

#### Trường hợp lỗi

1. **Email/Phone không tồn tại**
   - Thông báo: "Email/Số điện thoại không tồn tại"
   - Cách xử lý: Kiểm tra lại email/phone hoặc liên hệ quản trị viên

2. **Password không đúng**
   - Thông báo: "Mật khẩu không đúng"
   - Cách xử lý: Kiểm tra lại mật khẩu hoặc sử dụng "Quên mật khẩu"

3. **Người dùng không hoạt động**
   - Thông báo: "Tài khoản chưa được kích hoạt"
   - Cách xử lý: Liên hệ quản trị viên để kích hoạt tài khoản

4. **Email chưa được xác thực**
   - Thông báo: "Email chưa được xác thực"
   - Cách xử lý: Xác thực email hoặc liên hệ quản trị viên

5. **CSRF Token Mismatch**
   - Thông báo: "Token không hợp lệ"
   - Cách xử lý: Refresh trang và thử lại

### Remember Me

- Khi chọn "Remember me", hệ thống lưu cookie để tự động đăng nhập ở lần truy cập tiếp theo
- Cookie có thời gian hết hạn (thường 30 ngày)
- Có thể bỏ chọn "Remember me" nếu không muốn lưu phiên đăng nhập

### Last Đăng nhập Tracking

- Hệ thống tự động cập nhật `last_login_at` mỗi khi đăng nhập thành công
- Thông tin này được lưu trong bảng `users`
- Dùng để theo dõi hoạt động của người dùng

## Đăng xuất

### Các bước đăng xuất

1. Click vào **Avatar** hoặc **Menu** ở header
2. Click **Đăng xuất** hoặc truy cập `/logout`
3. Xác nhận đăng xuất (nếu có)
4. Hệ thống đăng xuất và redirect về trang chủ hoặc trang đăng nhập

### Xử lý sau khi đăng xuất

Sau khi đăng xuất thành công:

1. Session được destroy
2. Remember me cookie được xóa (nếu có)
3. Redirect về trang chủ (`/`) hoặc trang đăng nhập (`/login`)

### Bảo mật

- Khi đăng xuất, tất cả session data được xóa
- Không thể truy cập các trang yêu cầu xác thực sau khi đăng xuất
- Phải đăng nhập lại để tiếp tục sử dụng

## Session Management

### Session Timeout

- Session mặc định có thời gian hết hạn (thường 2 giờ không hoạt động)
- Sau khi hết hạn, người dùng phải đăng nhập lại
- Sử dụng "Remember me" để kéo dài session

### Multiple Sessions

- Người dùng có thể đăng nhập từ nhiều thiết bị/browser khác nhau
- Mỗi session độc lập với nhau
- Đăng xuất ở một thiết bị không ảnh hưởng đến session ở thiết bị khác

## Lưu ý

1. **Bảo mật tài khoản**
   - Không chia sẻ mật khẩu với người khác
   - Sử dụng mật khẩu mạnh (có chữ hoa, chữ thường, số, ký tự đặc biệt)
   - Đổi mật khẩu định kỳ

2. **Remember Me**
   - Chỉ sử dụng "Remember me" trên thiết bị cá nhân và an toàn
   - Không sử dụng trên thiết bị công cộng

3. **Đăng nhập bằng Google**
   - Đảm bảo email Google đã được liên kết với tài khoản SuperAdmin
   - Phải có quyền truy cập tài khoản Google

4. **Session Security**
   - Luôn đăng xuất khi không sử dụng, đặc biệt trên thiết bị công cộng
   - Không để session mở trên thiết bị không an toàn

## Troubleshooting

### Không thể đăng nhập

1. Kiểm tra email/phone và password
2. Kiểm tra kết nối mạng
3. Clear browser cache và cookies
4. Thử trình duyệt khác
5. Liên hệ quản trị viên nếu vẫn không thể đăng nhập

### Session bị mất

1. Refresh trang
2. Đăng nhập lại
3. Kiểm tra "Remember me" có được chọn không
4. Kiểm tra browser có xóa cookies tự động không

### Google OAuth không hoạt động

1. Kiểm tra kết nối mạng
2. Kiểm tra email Google đã được liên kết với tài khoản chưa
3. Cho phép ứng dụng truy cập (nếu bị chặn)
4. Thử phương thức đăng nhập khác (Email + Password)

### Quên mật khẩu

Xem [Common FAQ](../common/04-faq.md) - Q1: Tôi quên mật khẩu, làm sao để lấy lại?

---

**Lưu ý**: SuperAdmin có quyền cao nhất trong hệ thống, cần bảo mật tài khoản cẩn thận.

**Cập nhật**: 2025-11-02

