# ĐĂNG KÝ, ĐĂNG NHẬP VÀ QUÊN MẬT KHẨU - TENANT

## Tổng quan

Tài liệu này hướng dẫn Khách thuê đăng ký, đăng nhập, quên mật khẩu, và xác thực email trong hệ thống Quản lý Bất động sản Cho thuê.

## Quyền truy cập

- **Public**: Ai cũng có thể đăng ký và đăng nhập
- **Khách thuê**: Có quyền đăng nhập và sử dụng các chức năng của Khách thuê

## Đăng ký

### Các bước đăng ký

1. Truy cập trang đăng ký: `/register`
2. Điền thông tin:
   - **Email** (bắt buộc, unique, đúng format): Email của bạn
   - **Phone** (bắt buộc, unique, đúng format): Số điện thoại
   - **Password** (bắt buộc, tối thiểu 8 ký tự): Mật khẩu
   - **Full Name** (bắt buộc): Họ và tên
3. Click **Đăng ký**
4. Hệ thống validate thông tin
5. Nếu hợp lệ, hệ thống tạo tài khoản và gửi OTP qua email
6. Chuyển đến trang xác thực email

### Validation

- **Email**: 
  - Phải unique trong hệ thống
  - Phải đúng format email
  - Không được để trống
- **Phone**: 
  - Phải unique trong hệ thống
  - Phải đúng format số điện thoại
  - Không được để trống
- **Password**: 
  - Tối thiểu 8 ký tự
  - Nên có chữ hoa, chữ thường, số, và ký tự đặc biệt
  - Không được để trống

## Xác thực Email (Email Verification)

### Các bước xác thực email

1. Sau khi đăng ký, hệ thống gửi OTP (6 chữ số) qua email
2. Truy cập trang xác thực email: `/email-verification`
3. Nhập **OTP** đã nhận từ email
4. Click **Xác thực**
5. Nếu OTP đúng và chưa hết hạn:
   - Hệ thống kích hoạt tài khoản
   - Đánh dấu email đã xác thực
   - Tự động đăng nhập và redirect đến bảng điều khiển

### OTP Rules

- OTP là 6 chữ số
- OTP có thời gian hết hạn (thường 15 phút)
- Mỗi OTP chỉ có thể sử dụng 1 lần
- Có thể resend OTP nếu hết hạn

### Resend OTP

1. Trên trang xác thực email, click **Gửi lại OTP**
2. Hệ thống gửi OTP mới qua email
3. Nhập OTP mới và xác thực

### Lưu ý

- Kiểm tra thư mục spam nếu không nhận được email
- OTP chỉ hiệu lực trong 15 phút
- Phải xác thực email mới có thể sử dụng đầy đủ chức năng

## Đăng nhập

### Phương thức đăng nhập

Hệ thống hỗ trợ 2 phương thức:

1. **Email + Password**: Đăng nhập bằng email và mật khẩu
2. **Google OAuth**: Đăng nhập bằng tài khoản Google

### Các bước đăng nhập bằng Email + Password

1. Truy cập trang đăng nhập: `/login`
2. Nhập **Email** hoặc **Số điện thoại**
3. Nhập **Mật khẩu**
4. (Tùy chọn) Chọn **Remember me** để lưu phiên đăng nhập
5. Click **Đăng nhập**
6. Nếu thành công, hệ thống redirect đến `/tenant/dashboard`

### Các bước đăng nhập bằng Google OAuth

1. Truy cập trang đăng nhập: `/login`
2. Click **Đăng nhập bằng Google**
3. Chọn tài khoản Google
4. Cho phép ứng dụng truy cập thông tin (nếu lần đầu)
5. Hệ thống tự động đăng nhập và redirect đến bảng điều khiển

### Ràng buộc

- Email/Phone phải tồn tại trong hệ thống
- Password phải đúng
- Người dùng phải có trạng thái `active`
- Email phải được xác thực (nếu được yêu cầu)

## Quên mật khẩu

### Các bước đặt lại mật khẩu

1. Truy cập trang quên mật khẩu: `/forgot-password`
2. Nhập **Email** hoặc **Số điện thoại**
3. Click **Gửi OTP**
4. Hệ thống gửi OTP qua email
5. Truy cập trang nhập OTP: `/forgot-password/otp`
6. Nhập **OTP** đã nhận
7. Click **Xác thực OTP**
8. Nếu OTP đúng, chuyển đến trang đặt mật khẩu mới
9. Nhập **Mật khẩu mới**
10. Xác nhận mật khẩu mới
11. Click **Đặt lại mật khẩu**
12. Hệ thống cập nhật mật khẩu và hiển thị thông báo thành công

### Resend OTP

1. Trên trang nhập OTP, click **Gửi lại OTP**
2. Hệ thống gửi OTP mới qua email
3. Nhập OTP mới và xác thực

### Lưu ý

- OTP có thời gian hết hạn (thường 15 phút)
- Mỗi OTP chỉ có thể sử dụng 1 lần
- Mật khẩu mới phải khác mật khẩu cũ

## Đăng xuất

### Các bước đăng xuất

1. Click vào **Avatar** hoặc **Menu** ở header
2. Click **Đăng xuất** hoặc truy cập `/logout`
3. Hệ thống đăng xuất và redirect về trang chủ hoặc trang đăng nhập

## Lưu ý

1. **Bảo mật tài khoản**
   - Không chia sẻ mật khẩu với người khác
   - Sử dụng mật khẩu mạnh
   - Đổi mật khẩu định kỳ

2. **Email Verification**
   - Phải xác thực email để sử dụng đầy đủ chức năng
   - Kiểm tra thư mục spam nếu không nhận được email

3. **Remember Me**
   - Chỉ sử dụng trên thiết bị cá nhân và an toàn
   - Không sử dụng trên thiết bị công cộng

## Troubleshooting

### Không nhận được email OTP

1. Kiểm tra thư mục spam
2. Đợi vài phút (email có thể bị delay)
3. Sử dụng chức năng "Gửi lại OTP"
4. Kiểm tra email đã nhập đúng chưa
5. Liên hệ hỗ trợ nếu vẫn không nhận được

### OTP hết hạn

1. Sử dụng chức năng "Gửi lại OTP"
2. Nhập OTP mới trong thời gian hiệu lực

### Không thể đăng nhập

1. Kiểm tra email/phone và password
2. Kiểm tra email đã được xác thực chưa
3. Kiểm tra người dùng có trạng thái `active` không
4. Sử dụng "Quên mật khẩu" nếu quên password
5. Liên hệ hỗ trợ nếu vẫn không thể đăng nhập

### Google OAuth không hoạt động

1. Kiểm tra kết nối mạng
2. Kiểm tra email Google đã được liên kết với tài khoản chưa
3. Cho phép ứng dụng truy cập (nếu bị chặn)
4. Thử phương thức đăng nhập khác (Email + Password)

---

**Lưu ý**: Đăng ký và đăng nhập là bước đầu tiên để sử dụng hệ thống. Đảm bảo thông tin chính xác và bảo mật tài khoản.

**Cập nhật**: 2025-11-02

