# QUẢN LÝ THÔNG BÁO - TENANT

## Tổng quan

Chức năng này cho phép Khách thuê xem và quản lý thông báo (thông báo) trong hệ thống.

## Quyền truy cập

- **Khách thuê**: Có quyền xem và quản lý thông báo của chính mình

**Route**: `/tenant/notifications`

## Các bước thực hiện

### 1. Xem danh sách Thông báo

1. Truy cập **Thông báo** từ menu Khách thuê
2. Hệ thống hiển thị danh sách tất cả thông báo của Khách thuê
3. Có thể lọc theo:
   - Read Trạng thái (read/unread/all)
   - Loại (hóa đơn, thanh toán, ticket, viewing, hợp đồng thuê, etc.)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo created_at, read_at

### 2. Xem chi tiết Notification

1. Click vào notification trong danh sách
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Notification ID: Mã thông báo
     - Loại: Loại thông báo
     - Title: Tiêu đề
     - Content: Nội dung
     - Read Trạng thái: Đã đọc/Chưa đọc
     - Created At: Thời gian tạo
     - Read At: Thời gian đọc (nếu đã đọc)
   - **Thông tin liên quan:**
     - Link: Link đến trang liên quan (nếu có)
     - Entity ID: ID của entity liên quan (hóa đơn, thanh toán, ticket, etc.)
     - Entity Loại: Loại entity (Hóa đơn, Thanh toán, Ticket, etc.)

### 3. Đánh dấu Đã đọc

#### 3.1. Đánh dấu một Notification đã đọc

1. Click vào notification trong danh sách
2. Hệ thống tự động đánh dấu notification là đã đọc
3. Hoặc click **Mark as Read** trên notification
4. Hệ thống cập nhật notification read_status = `read`
5. Unread count được giảm đi 1

#### 3.2. Đánh dấu tất cả Thông báo đã đọc

1. Truy cập danh sách thông báo
2. Click **Mark All as Read** hoặc **Đánh dấu tất cả đã đọc**
3. Xác nhận
4. Hệ thống đánh dấu tất cả thông báo là đã đọc
5. Unread count về 0

### 4. Xóa Notification

1. Truy cập chi tiết notification cần xóa
2. Click **Xóa** hoặc **Xóa**
3. Xác nhận xóa
4. Hệ thống xóa notification (soft xóa)

**Lưu ý**: 
- Có thể xóa notification đã đọc hoặc chưa đọc
- Notification được soft xóa, có thể restore sau nếu cần

### 5. Xem Unread Count

1. Xem **Unread Count** trên header hoặc icon notification
2. Unread count hiển thị số thông báo chưa đọc
3. Click vào icon để xem danh sách thông báo

**Lưu ý**: 
- Unread count được cập nhật real-thời gian
- Click vào notification sẽ tự động đánh dấu đã đọc

### 6. Xem Recent Thông báo

1. Click vào **icon notification** trên header
2. Hệ thống hiển thị danh sách thông báo gần đây (top 10)
3. Có thể click vào notification để xem chi tiết
4. Có thể click **Xem All** để xem tất cả thông báo

### 7. Truy cập Entity liên quan

1. Click vào notification có link
2. Hệ thống redirect đến trang liên quan:
   - Hóa đơn → `/tenant/invoices/{id}`
   - Thanh toán → `/tenant/payments/{id}`
   - Ticket → `/tenant/tickets/{id}`
   - Hợp đồng thuê → `/tenant/contracts/{id}`
   - Viewing → `/tenant/appointments/{id}`

**Lưu ý**: 
- Chỉ thông báo có entity liên quan mới có link
- Click vào link sẽ tự động đánh dấu notification đã đọc

### 8. Notification Cài đặt (Nếu có)

1. Truy cập **Thông báo** → **Cài đặt**
2. Có thể cấu hình:
   - Notification Preferences: Loại thông báo muốn nhận
   - Email Thông báo: Có nhận email không
   - Push Thông báo: Có nhận push không (nếu có)
3. Click **Lưu**
4. Hệ thống lưu cấu hình

## Các loại Thông báo

### Hóa đơn Thông báo

- **Hóa đơn Issued**: Hóa đơn đã được phát hành
- **Hóa đơn Overdue**: Hóa đơn quá hạn thanh toán
- **Hóa đơn Đã thanh toán**: Hóa đơn đã được thanh toán

### Thanh toán Thông báo

- **Thanh toán Success**: Thanh toán thành công
- **Thanh toán Failed**: Thanh toán thất bại
- **Thanh toán Đang chờ**: Thanh toán đang chờ xử lý (SePay)

### Ticket Thông báo

- **Ticket Created**: Ticket đã được tạo
- **Ticket Updated**: Ticket đã được cập nhật
- **Ticket Resolved**: Ticket đã được giải quyết
- **Ticket Closed**: Ticket đã đóng

### Hợp đồng thuê Thông báo

- **Hợp đồng thuê Created**: Hợp đồng đã được tạo
- **Hợp đồng thuê Updated**: Hợp đồng đã được cập nhật
- **Hợp đồng thuê Expiring Soon**: Hợp đồng sắp hết hạn
- **Hợp đồng thuê Terminated**: Hợp đồng đã chấm dứt

### Viewing Thông báo

- **Viewing Confirmed**: Lịch xem phòng đã được xác nhận
- **Viewing Cancelled**: Lịch xem phòng đã bị hủy
- **Viewing Reminder**: Nhắc nhở lịch xem phòng

### Review Thông báo

- **Review Replied**: Review đã được reply

## Ràng buộc và điều kiện

### Business Rules

1. **Khách thuê chỉ thấy thông báo của chính mình**
   - Không thể thấy thông báo của Khách thuê khác
   - Dữ liệu được lọc theo Khách thuê ID

2. **Read Trạng thái**
   - `unread`: Notification chưa đọc
   - `read`: Notification đã đọc

3. **Notification Types**
   - Mỗi notification có loại riêng
   - Loại xác định loại notification và cách hiển thị

## Trạng thái và Workflow

### Read Trạng thái Flow

```
unread → read
```

- **unread**: Notification chưa đọc
- **read**: Notification đã đọc

### Workflow Nhận Notification

1. Event xảy ra trong hệ thống (hóa đơn issued, thanh toán success, etc.)
2. Hệ thống tạo notification cho Khách thuê
3. Notification có trạng thái `unread`
4. Unread count tăng lên
5. Khách thuê nhận thông báo (in-app, email)
6. Khách thuê click vào notification để xem
7. Hệ thống tự động đánh dấu notification là đã đọc
8. Unread count giảm đi

## Ví dụ

### Ví dụ 1: Xem danh sách Thông báo

**Kịch bản:** Khách thuê muốn xem tất cả thông báo

**Các bước:**
1. Truy cập Thông báo
2. Hệ thống hiển thị danh sách:
   - Notification 1: "Hóa đơn HD-202501-0001 đã được phát hành" (unread, Hóa đơn)
   - Notification 2: "Thanh toán đã thành công" (read, Thanh toán)
   - Notification 3: "Ticket đã được giải quyết" (read, Ticket)

### Ví dụ 2: Đánh dấu Đã đọc

**Kịch bản:** Khách thuê muốn đánh dấu tất cả thông báo đã đọc

**Các bước:**
1. Truy cập Thông báo
2. Click **Mark All as Read**
3. Xác nhận
4. Hệ thống đánh dấu tất cả thông báo là đã đọc
5. Unread count về 0

### Ví dụ 3: Truy cập Entity liên quan

**Kịch bản:** Khách thuê nhận notification về hóa đơn mới

**Các bước:**
1. Click vào notification "Hóa đơn HD-202501-0001 đã được phát hành"
2. Hệ thống tự động đánh dấu notification là đã đọc
3. Hệ thống redirect đến `/tenant/invoices/1`
4. Khách thuê xem chi tiết hóa đơn

## Lưu ý

1. **Unread Count**
   - Unread count hiển thị trên header
   - Click vào icon để xem thông báo
   - Click vào notification sẽ tự động đánh dấu đã đọc

2. **Email Thông báo**
   - Một số thông báo được gửi qua email
   - Kiểm tra email để không bỏ lỡ thông báo quan trọng

3. **Notification Cài đặt**
   - Cấu hình notification preferences để nhận thông báo phù hợp
   - Tắt thông báo không cần thiết để tránh spam

4. **Link to Entity**
   - Click vào notification có link để xem entity liên quan
   - Tiết kiệm thời gian điều hướng

## Troubleshooting

### Không nhận được notification

1. Kiểm tra notification cài đặt
2. Kiểm tra email có được gửi không
3. Kiểm tra notification có trong danh sách không
4. Liên hệ hỗ trợ nếu vẫn không nhận được

### Unread count không chính xác

1. Refresh trang
2. Đánh dấu tất cả thông báo đã đọc
3. Kiểm tra thông báo có trạng thái unread không
4. Liên hệ hỗ trợ nếu vẫn không chính xác

### Không thể xóa notification

1. Kiểm tra notification có tồn tại không
2. Refresh trang
3. Thử lại sau vài phút
4. Liên hệ hỗ trợ nếu vẫn lỗi

---

**Lưu ý**: Quản lý thông báo giúp Khách thuê theo dõi các sự kiện quan trọng trong hệ thống và không bỏ lỡ thông tin cần thiết.

**Cập nhật**: 2025-11-02

