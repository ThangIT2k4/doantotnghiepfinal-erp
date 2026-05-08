# QUẢN LÝ LỊCH XEM PHÒNG - TENANT

## Tổng quan

Chức năng này cho phép Khách thuê xem, quản lý lịch xem phòng (appointments) của mình, bao gồm xem chi tiết, hủy, và cập nhật lịch xem phòng.

## Quyền truy cập

- **Khách thuê**: Có quyền xem và quản lý appointments của chính mình

**Route**: `/tenant/appointments`

## Các bước thực hiện

### 1. Xem danh sách Appointments

1. Truy cập **Appointments** từ menu Khách thuê
2. Hệ thống hiển thị danh sách tất cả appointments của Khách thuê
3. Có thể lọc theo:
   - Trạng thái (requested, confirmed, done, no_show, cancelled)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Bất động sản (nếu có nhiều appointments)
   - Sắp xếp theo schedule_at, created_at, trạng thái

### 2. Xem chi tiết Appointment

1. Click vào appointment trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - Bất động sản và Phòng (nếu có)
   - Môi giới (người xử lý)
   - Schedule At: Thời gian hẹn xem phòng
   - Trạng thái: Trạng thái hiện tại
   - Note: Ghi chú
   - Result Note: Kết quả sau khi xem (nếu đã done)
   - Feedback Rating: Đánh giá (nếu đã done)
   - Photos: Hình ảnh (nếu có)

### 3. Hủy Appointment

1. Truy cập chi tiết appointment cần hủy
2. Click **Hủy** hoặc **Hủy lịch**
3. (Tùy chọn) Nhập lý do hủy
4. Xác nhận hủy
5. Hệ thống cập nhật appointment trạng thái = `cancelled`
6. Hệ thống gửi thông báo cho Môi giới và Quản lý

**Lưu ý**: 
- Chỉ có thể hủy appointment có trạng thái `requested` hoặc `confirmed`
- Không thể hủy appointment đã `done` hoặc `cancelled`
- Hủy appointment sẽ giải phóng slot thời gian

### 4. Cập nhật Appointment

1. Truy cập chi tiết appointment cần cập nhật
2. Click **Chỉnh sửa** hoặc **Cập nhật** (chỉ khi trạng thái = `requested`)
3. Cập nhật thông tin:
   - **Schedule At**: Thay đổi thời gian hẹn (nếu có slot available)
   - **Note**: Cập nhật ghi chú
4. Click **Lưu**
5. Hệ thống cập nhật và gửi thông báo cho Môi giới

**Lưu ý**: 
- Chỉ có thể cập nhật appointment có trạng thái `requested`
- Không thể cập nhật appointment đã `confirmed`, `done`, hoặc `cancelled`
- Schedule At mới phải trong tương lai và có slot available

### 5. Đặt lịch xem phòng mới (Public Booking)

1. Truy cập trang Bất động sản detail hoặc Phòng detail
2. Click **Book Appointment** hoặc **Đặt lịch xem phòng**
3. Điền thông tin:
   - **Name**: Họ và tên
   - **Phone**: Số điện thoại
   - **Email**: Email
   - **Bất động sản/Unit**: Chọn bất động sản/unit muốn xem
   - **Schedule At**: Chọn thời gian hẹn (từ available slots)
   - **Note**: Ghi chú (nếu có)
4. Click **Book** hoặc **Đặt lịch**
5. Hệ thống tạo appointment với trạng thái `requested`
6. Hệ thống gửi email confirmation cho Khách thuê
7. Hệ thống gửi thông báo cho Môi giới

**Lưu ý**: 
- Có thể đặt lịch mà không cần đăng nhập (public booking)
- Hệ thống sẽ tự động tạo Lead hoặc link với Khách thuê nếu đã có account

## Ràng buộc và điều kiện

### Validation Rules

- **Name**: 
  - Bắt buộc (khi đặt lịch public)
  - Không được để trống
- **Phone**: 
  - Bắt buộc (khi đặt lịch public)
  - Phải đúng format số điện thoại
- **Email**: 
  - Bắt buộc (khi đặt lịch public)
  - Phải đúng format email
- **Bất động sản/Unit**: 
  - Bắt buộc
  - Phải tồn tại và có trạng thái hoạt động
- **Schedule At**: 
  - Bắt buộc
  - Phải là datetime hợp lệ
  - Phải trong tương lai (khi tạo mới)
  - Phải trong available slots
- **Trạng thái**: 
  - Phải là một trong: requested, confirmed, done, no_show, cancelled

### Business Rules

1. **Chỉ có thể hủy appointment chưa done**
   - Trạng thái phải là `requested` hoặc `confirmed`
   - Không thể hủy appointment đã `done`

2. **Chỉ có thể cập nhật appointment requested**
   - Trạng thái phải là `requested`
   - Không thể cập nhật appointment đã `confirmed` hoặc `done`

3. **Available Slots**
   - Schedule At phải trong available slots
   - Không thể đặt lịch trùng với appointment khác

4. **Public Booking**
   - Có thể đặt lịch mà không cần đăng nhập
   - Hệ thống tự động tạo Lead hoặc link với Khách thuê

## Trạng thái và Workflow

### Trạng thái Flow

```
requested → confirmed → done
    ↓          ↓
cancelled   cancelled/no_show
```/no_show
```

- **requested**: Đã đặt lịch, chờ Môi giới xác nhận
- **confirmed**: Môi giới đã xác nhận lịch
- **done**: Đã hoàn tất xem phòng
- **no_show**: Không đến xem phòng
- **cancelled**: Đã hủy lịch xem phòng

### Workflow Đặt Lịch Xem Phòng

1. Khách thuê đặt lịch xem phòng (public hoặc đã đăng nhập)
2. Hệ thống tạo appointment với trạng thái `requested`
3. Hệ thống gửi email confirmation cho Khách thuê
4. Hệ thống gửi thông báo cho Môi giới
5. Môi giới xác nhận appointment → trạng thái `confirmed`
6. Sau khi xem phòng, Môi giới mark done → trạng thái `done`
7. Hoặc Khách thuê có thể hủy trước khi done → trạng thái `cancelled`

## Ví dụ

### Ví dụ 1: Xem danh sách Appointments

**Kịch bản:** Khách thuê muốn xem tất cả lịch xem phòng của mình

**Các bước:**
1. Truy cập Appointments
2. Hệ thống hiển thị danh sách:
   - Appointment 1: Bất động sản ABC, Phòng 101, Schedule: 2025-01-15 14:00, Trạng thái: `confirmed`
   - Appointment 2: Bất động sản XYZ, Phòng 202, Schedule: 2025-01-20 10:00, Trạng thái: `requested`
   - Appointment 3: Bất động sản ABC, Phòng 102, Schedule: 2025-01-10 09:00, Trạng thái: `done`

### Ví dụ 2: Hủy Appointment

**Kịch bản:** Khách thuê muốn hủy lịch xem phòng

**Các bước:**
1. Truy cập chi tiết appointment có trạng thái `confirmed`
2. Click **Hủy**
3. Nhập lý do: "Có việc đột xuất"
4. Xác nhận hủy
5. Hệ thống cập nhật trạng thái = `cancelled`
6. Hệ thống gửi thông báo cho Môi giới

### Ví dụ 3: Đặt lịch xem phòng mới (Public)

**Kịch bản:** Khách thuê (chưa đăng nhập) muốn đặt lịch xem phòng

**Các bước:**
1. Truy cập trang Bất động sản detail
2. Click **Book Appointment**
3. Điền thông tin:
   - Name: `Nguyễn Văn A`
   - Phone: `0123456789`
   - Email: `nguyenvana@example.com`
   - Bất động sản: `Property ABC`
   - Phòng: `Unit 101`
   - Schedule At: `2025-01-25 14:00` (chọn từ available slots)
4. Click **Book**
5. Hệ thống tạo appointment với trạng thái `requested`
6. Hệ thống gửi email confirmation

## Lưu ý

1. **Hủy Appointment**
   - Hủy sớm để Môi giới có thời gian sắp xếp lại
   - Hủy appointment sẽ giải phóng slot thời gian

2. **Cập nhật Appointment**
   - Cập nhật sớm nếu cần thay đổi thời gian
   - Kiểm tra available slots trước khi cập nhật

3. **Public Booking**
   - Có thể đặt lịch mà không cần đăng nhập
   - Hệ thống sẽ tạo Lead hoặc link với Khách thuê nếu đã có account

4. **Email Confirmation**
   - Nhận email confirmation sau khi đặt lịch
   - Kiểm tra thư mục spam nếu không nhận được

## Troubleshooting

### Không thể hủy appointment

1. Kiểm tra trạng thái của appointment
2. Chỉ có thể hủy appointment có trạng thái `requested` hoặc `confirmed`
3. Liên hệ Môi giới nếu cần hủy appointment đã `confirmed`

### Không thể cập nhật appointment

1. Kiểm tra trạng thái của appointment
2. Chỉ có thể cập nhật appointment có trạng thái `requested`
3. Kiểm tra available slots trước khi cập nhật

### Không nhận được email confirmation

1. Kiểm tra thư mục spam
2. Đợi vài phút (email có thể bị delay)
3. Kiểm tra email đã nhập đúng chưa
4. Liên hệ hỗ trợ nếu vẫn không nhận được

### Không có available slots

1. Thử chọn thời gian khác
2. Liên hệ Môi giới để sắp xếp thời gian phù hợp
3. Chọn bất động sản/unit khác

---

**Lưu ý**: Quản lý appointments giúp Khách thuê theo dõi và sắp xếp lịch xem phòng một cách hiệu quả.

**Cập nhật**: 2025-11-02

