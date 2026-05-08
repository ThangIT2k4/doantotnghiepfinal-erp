# XỬ LÝ LỖI

## Tổng quan

Tài liệu này mô tả các lỗi thường gặp trong hệ thống Quản lý Bất động sản Cho thuê và cách xử lý chúng.

## Các loại lỗi

### 1. Lỗi Xác thực (Xác thực Errors)

#### 1.1. Đăng nhập thất bại

**Nguyên nhân:**
- Email/Phone không tồn tại trong hệ thống
- Password không đúng
- Người dùng chưa được kích hoạt (trạng thái = không hoạt động)
- Email chưa được xác thực

**Cách xử lý:**
1. Kiểm tra lại email/phone và password
2. Liên hệ quản trị viên để kích hoạt tài khoản
3. Thực hiện xác thực email nếu chưa làm
4. Sử dụng chức năng "Quên mật khẩu" nếu cần

#### 1.2. OTP hết hạn

**Nguyên nhân:**
- OTP đã hết hạn (thường là 15 phút)
- OTP đã được sử dụng

**Cách xử lý:**
1. Sử dụng chức năng "Gửi lại OTP"
2. Đảm bảo nhập OTP trong thời gian hiệu lực
3. Không sử dụng lại OTP đã dùng

#### 1.3. Email chưa được xác thực

**Nguyên nhân:**
- Chưa hoàn tất quy trình xác thực email sau khi đăng ký

**Cách xử lý:**
1. Kiểm tra email để lấy OTP
2. Nhập OTP để xác thực
3. Nếu không nhận được email, sử dụng "Gửi lại OTP"
4. Kiểm tra thư mục spam

### 2. Lỗi Phân quyền (Authorization Errors)

#### 2.1. Không có quyền truy cập

**Thông báo lỗi:** "Bạn không có quyền thực hiện thao tác này"

**Nguyên nhân:**
- Người dùng không có capability cần thiết
- Người dùng không thuộc tổ chức đúng
- Người dùng không có role phù hợp

**Cách xử lý:**
1. Liên hệ Quản lý để được cấp quyền
2. Kiểm tra role của mình
3. Kiểm tra tổ chức membership

#### 2.2. Không truy cập được dữ liệu

**Nguyên nhân:**
- Dữ liệu thuộc tổ chức khác
- Người dùng không được gán quyền truy cập

**Cách xử lý:**
1. Kiểm tra tổ chức của mình
2. Liên hệ Quản lý để được gán quyền truy cập

### 3. Lỗi Dữ liệu (Data Validation Errors)

#### 3.1. Email đã tồn tại

**Thông báo lỗi:** "Email này đã được sử dụng"

**Cách xử lý:**
1. Sử dụng email khác
2. Sử dụng chức năng "Quên mật khẩu" nếu đây là email của bạn
3. Liên hệ quản trị viên nếu cần

#### 3.2. Phone đã tồn tại

**Thông báo lỗi:** "Số điện thoại này đã được sử dụng"

**Cách xử lý:**
1. Sử dụng số điện thoại khác
2. Liên hệ quản trị viên nếu cần

#### 3.3. Phòng đã có hợp đồng thuê hoạt động

**Thông báo lỗi:** "Phòng này đã có hợp đồng đang hoạt động"

**Cách xử lý:**
1. Chọn phòng khác
2. Terminate hợp đồng thuê hiện tại nếu cần
3. Chờ đến khi hợp đồng thuê kết thúc

#### 3.4. Hóa đơn đã thanh toán đủ

**Thông báo lỗi:** "Hóa đơn này đã được thanh toán đủ"

**Cách xử lý:**
1. Kiểm tra trạng thái hóa đơn
2. Không cần thanh toán thêm

#### 3.5. Số tiền vượt quá giới hạn

**Thông báo lỗi:** "Số tiền vượt quá số tiền còn lại"

**Cách xử lý:**
1. Kiểm tra số tiền còn lại (remaining số tiền)
2. Nhập số tiền <= remaining số tiền
3. Tạo nhiều thanh toán nếu cần thanh toán nhiều lần

#### 3.6. Ngày không hợp lệ

**Thông báo lỗi:** "Ngày không hợp lệ" hoặc "End Ngày phải sau Start Ngày"

**Cách xử lý:**
1. Kiểm tra format ngày (YYYY-MM-DD)
2. Đảm bảo End Ngày >= Start Ngày
3. Không chọn ngày trong quá khứ (nếu bắt buộc)

#### 3.7. Meter reading value không hợp lệ

**Thông báo lỗi:** "Chỉ số phải >= chỉ số lần trước"

**Cách xử lý:**
1. Kiểm tra chỉ số lần trước
2. Nhập chỉ số >= chỉ số trước
3. Kiểm tra lại công tơ nếu chỉ số giảm (có thể bị hỏng hoặc thay mới)

### 4. Lỗi Thanh toán (Thanh toán Errors)

#### 4.1. SePay thanh toán failed

**Thông báo lỗi:** "Thanh toán thất bại" hoặc thanh toán trạng thái = `failed`

**Nguyên nhân:**
- SePay API lỗi
- Tài khoản không đủ tiền
- Thông tin thanh toán không đúng
- Network timeout
- Webhook không được xử lý kịp thời

**Cách xử lý:**
1. Kiểm tra tài khoản ngân hàng
2. Kiểm tra thanh toán trạng thái trong hệ thống (có thể webhook chưa được xử lý)
3. Sử dụng chức năng "Retry" nếu có (trong SePay Management)
4. Thử lại thanh toán
5. Sử dụng phương thức thanh toán khác (cash, bank transfer)
6. Liên hệ hỗ trợ nếu lỗi tiếp tục

#### 4.2. Webhook timeout hoặc không nhận được webhook

**Nguyên nhân:**
- SePay không gửi webhook callback
- Network issue
- Server overload
- Webhook URL không đúng hoặc không accessible
- API key không đúng trong Authorization header

**Cách xử lý:**
1. Chờ vài phút, hệ thống sẽ tự động cập nhật
2. Kiểm tra Webhook Logs trong `/staff/webhook-logs` để xem webhook có được nhận không
3. Sử dụng chức năng "Check Đang chờ Thanh toán" trong SePay Management
4. Kiểm tra thủ công thanh toán trạng thái
5. Liên hệ quản trị viên để xử lý thủ công hoặc retry webhook

#### 4.3. Thanh toán đã được xử lý

**Thông báo lỗi:** "Thanh toán này đã được xử lý" hoặc "Thanh toán này đã thành công"

**Cách xử lý:**
1. Không cần xử lý lại
2. Kiểm tra trạng thái của thanh toán (phải là `success`)
3. Kiểm tra hóa đơn trạng thái (phải là `paid` nếu đã thanh toán đủ)

### 5. Lỗi File Upload

#### 5.1. File quá lớn

**Thông báo lỗi:** "File vượt quá kích thước cho phép"

**Cách xử lý:**
1. Giảm kích thước file
2. Nén file trước khi upload
3. Sử dụng file format khác (nếu được)

#### 5.2. File format không hợp lệ

**Thông báo lỗi:** "Định dạng file không được hỗ trợ"

**Cách xử lý:**
1. Chuyển đổi sang format được hỗ trợ
2. Kiểm tra danh sách format được hỗ trợ

#### 5.3. Upload failed

**Nguyên nhân:**
- Network issue
- Server storage full
- Permission denied

**Cách xử lý:**
1. Thử lại upload
2. Kiểm tra kết nối mạng
3. Liên hệ quản trị viên nếu lỗi tiếp tục

### 6. Lỗi Workflow

#### 6.1. Không thể approve booking deposit

**Nguyên nhân:**
- Deposit đã expired (payment_status = `expired`)
- Deposit đã đã thanh toán (payment_status = `paid`)
- Deposit đã cancelled (payment_status = `cancelled`)
- Deposit không có trạng thái `pending_approval`

**Cách xử lý:**
1. Kiểm tra payment_status của deposit
2. Nếu deposit đã expired hoặc cancelled, có thể khôi phục về `pending_approval` hoặc `pending`
3. Tạo deposit mới nếu cần

#### 6.2. Không thể terminate hợp đồng thuê

**Nguyên nhân:**
- Hợp đồng thuê có hóa đơn chưa thanh toán
- Hợp đồng thuê đã terminated

**Cách xử lý:**
1. Thanh toán tất cả hóa đơn còn nợ
2. Kiểm tra trạng thái của hợp đồng thuê

#### 6.3. Không thể xóa entity

**Nguyên nhân:**
- Entity có quan hệ với entity khác (foreign key constraint)
- Entity đang được sử dụng

**Cách xử lý:**
1. Xóa các entity liên quan trước
2. Kiểm tra quan hệ của entity
3. Sử dụng soft xóa thay vì xóa (nếu có)

### 7. Lỗi Hệ thống (System Errors)

#### 7.1. Server Error (500)

**Nguyên nhân:**
- Server issue
- Database connection failed
- Code error

**Cách xử lý:**
1. Thử lại sau vài phút
2. Liên hệ quản trị viên
3. Kiểm tra server trạng thái (nếu có)

#### 7.2. Database Error

**Nguyên nhân:**
- Database connection failed
- Query timeout
- Constraint violation

**Cách xử lý:**
1. Thử lại thao tác
2. Liên hệ quản trị viên
3. Kiểm tra dữ liệu đầu vào

#### 7.3. Session Expired

**Thông báo lỗi:** "Phiên làm việc đã hết hạn"

**Cách xử lý:**
1. Đăng nhập lại
2. Sử dụng "Remember me" để kéo dài session

#### 7.4. CSRF Token Mismatch

**Thông báo lỗi:** "Token không hợp lệ"

**Cách xử lý:**
1. Refresh trang
2. Đăng nhập lại
3. Clear browser cache và cookies

## Cách báo cáo lỗi

Khi gặp lỗi, vui lòng cung cấp thông tin sau:

1. **Mô tả lỗi**: Lỗi gì xảy ra?
2. **Bước thực hiện**: Đang làm gì khi lỗi xảy ra?
3. **Thông báo lỗi**: Thông báo lỗi cụ thể là gì?
4. **Thời gian**: Khi nào lỗi xảy ra?
5. **Người dùng**: Tài khoản nào gặp lỗi?
6. **Role**: Vai trò của người dùng (SuperAdmin, Quản lý, Môi giới, Khách thuê)
7. **Browser**: Trình duyệt và phiên bản
8. **Screenshot**: Ảnh chụp màn hình lỗi (nếu có)

## Liên hệ hỗ trợ

Nếu lỗi không được giải quyết bằng các cách trên, vui lòng:

1. Xem [FAQ](./04-faq.md) để xem câu hỏi thường gặp
2. Liên hệ quản trị viên hệ thống
3. Gửi email đến support với thông tin lỗi chi tiết

---

**Lưu ý**: Hầu hết các lỗi có thể được giải quyết bằng cách kiểm tra lại dữ liệu đầu vào, đảm bảo tuân thủ các ràng buộc, và thử lại thao tác.

**Cập nhật**: 2025-01-XX

