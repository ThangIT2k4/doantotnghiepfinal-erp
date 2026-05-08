# ĐĂNG NHẬP VÀ ĐĂNG XUẤT - STAFF (MANAGER & AGENT)

## Tổng quan

Tài liệu này hướng dẫn Nhân viên (Quản lý và Môi giới) đăng nhập và đăng xuất khỏi hệ thống Quản lý Bất động sản Cho thuê. Hệ thống sử dụng unified routes `/staff/*` cho cả Quản lý và Môi giới.

## Quyền truy cập

- **Quản lý**: Có quyền đăng nhập và đăng xuất, truy cập đầy đủ tất cả chức năng
- **Môi giới**: Có quyền đăng nhập và đăng xuất, truy cập bị giới hạn bởi capabilities được Quản lý cấp

## Đăng nhập

### Phương thức đăng nhập

Hệ thống hỗ trợ 2 phương thức đăng nhập:

1. **Email + Password**: Đăng nhập bằng email và mật khẩu
2. **Google OAuth**: Đăng nhập bằng tài khoản Google

### Các bước đăng nhập bằng Email + Password

1. Truy cập trang đăng nhập: `/login`
2. Nhập **Email** hoặc **Số điện thoại** của tài khoản Nhân viên (Quản lý hoặc Môi giới)
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

1. Hệ thống kiểm tra role của người dùng (Quản lý hoặc Môi giới)
2. Hệ thống kiểm tra tổ chức membership
3. Hệ thống lưu role key vào session (`auth_role_key`)
4. Redirect đến bảng điều khiển tương ứng:
   - Quản lý → `/staff/dashboard` (unified staff dashboard với đầy đủ quyền)
   - Môi giới → `/staff/dashboard` (unified staff dashboard với quyền giới hạn bởi capabilities)
5. Track last đăng nhập thời gian
6. Tạo session cho người dùng

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
  - Người dùng phải có role `Manager` hoặc `Agent` trong tổ chức
  - Tổ chức phải có trạng thái `active`

#### Tổ chức Membership

- Người dùng phải thuộc về một tổ chức
- Người dùng phải có role `Manager` hoặc `Agent` trong tổ chức đó
- Tổ chức phải có trạng thái `active`
- Người dùng chỉ thấy dữ liệu của tổ chức mình
- Môi giới chỉ thấy dữ liệu được Quản lý cấp quyền (qua capabilities)

#### Trường hợp lỗi

1. **Email/Phone không tồn tại**
   - Thông báo: "Email/Số điện thoại không tồn tại"
   - Cách xử lý: Kiểm tra lại email/phone hoặc liên hệ quản trị viên

2. **Password không đúng**
   - Thông báo: "Mật khẩu không đúng"
   - Cách xử lý: Kiểm tra lại mật khẩu hoặc sử dụng "Quên mật khẩu"

3. **Người dùng không hoạt động**
   - Thông báo: "Tài khoản chưa được kích hoạt"
   - Cách xử lý: Liên hệ Quản lý hoặc SuperAdmin để kích hoạt tài khoản

4. **Tổ chức không hoạt động**
   - Thông báo: "Tổ chức chưa được kích hoạt"
   - Cách xử lý: Liên hệ SuperAdmin để kích hoạt tổ chức

5. **Không có tổ chức membership**
   - Thông báo: "Bạn chưa thuộc về tổ chức nào"
   - Cách xử lý: Liên hệ SuperAdmin hoặc Quản lý để được assign vào tổ chức

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

3. **Tổ chức Membership**
   - Nhân viên (Quản lý/Agent) chỉ thấy dữ liệu của tổ chức mình
   - Không thể thấy dữ liệu của tổ chức khác
   - Quản lý có quyền truy cập đầy đủ dữ liệu của tổ chức
   - Môi giới chỉ thấy dữ liệu được Quản lý cấp quyền (qua capabilities)
   - SuperAdmin là exception, có thể thấy tất cả

## Troubleshooting

### Không thể đăng nhập

1. Kiểm tra email/phone và password
2. Kiểm tra người dùng có trạng thái `active` không
3. Kiểm tra người dùng có role `Manager` hoặc `Agent` trong tổ chức không
4. Kiểm tra tổ chức có trạng thái `active` không
5. Kiểm tra kết nối mạng
6. Clear browser cache và cookies
7. Thử trình duyệt khác
8. Liên hệ Quản lý hoặc SuperAdmin nếu vẫn không thể đăng nhập

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

**Lưu ý**: 
- Quản lý có quyền cao trong tổ chức, cần bảo mật tài khoản cẩn thận.
- Môi giới cần được Quản lý cấp capabilities để truy cập các chức năng cụ thể.
- Hệ thống sử dụng unified routes `/staff/*` cho cả Quản lý và Môi giới.

**Cập nhật**: 2025-11-11  
**Phiên bản**: 2.1  
**Lưu ý**: Routes đã được unified - Quản lý và Môi giới đều sử dụng `/staff/*` routes với phân quyền dựa trên capabilities

