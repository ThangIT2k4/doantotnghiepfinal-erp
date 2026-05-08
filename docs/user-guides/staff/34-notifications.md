# QUẢN LÝ THÔNG BÁO - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý thông báo (thông báo) của chính mình, bao gồm xem, mark as read, xóa, và lọc.

## Quyền truy cập

- **Quản lý**: Có quyền quản lý thông báo của chính mình (tự động lọc theo user_id)
- **Môi giới**: Có quyền quản lý thông báo của chính mình (tự động lọc theo user_id, tương tự Quản lý)

**Route**: `/staff/notifications`

## Các bước thực hiện

### 1. Xem danh sách Thông báo

1. Truy cập **Thông báo** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả thông báo của Quản lý
3. Có thể lọc theo:
   - Trạng thái (read, unread)
   - Loại (hợp đồng thuê, hóa đơn, thanh toán, ticket, viewing, etc.)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo created_at, read_at

**Lưu ý**: 
- Quản lý chỉ thấy thông báo của chính mình
- Không thể thấy thông báo của Quản lý khác

### 2. Xem chi tiết Notification

1. Click vào notification trong danh sách
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Notification ID: Mã thông báo
     - Loại: Loại thông báo
     - Title: Tiêu đề
     - Content: Nội dung
     - Trạng thái: Trạng thái (read, unread)
   - **Thông tin liên quan:**
     - Link: Link đến resource liên quan (nếu có)
     - Created At: Ngày giờ tạo
     - Read At: Ngày giờ đọc (nếu có)

### 3. Mark as Read (Đánh dấu Đã đọc)

1. Click vào notification chưa đọc
2. Hệ thống tự động mark as read
3. Hoặc click **Mark as Read** trên notification
4. Notification trạng thái chuyển sang `read`
5. Unread count giảm đi 1

**Lưu ý**: 
- Click vào notification sẽ tự động mark as read
- Có thể mark as read nhiều thông báo cùng lúc

### 4. Mark All as Read (Đánh dấu Tất cả Đã đọc)

1. Truy cập danh sách thông báo
2. Click **Mark All as Read** hoặc **Đánh dấu tất cả đã đọc**
3. Tất cả unread thông báo được mark as read
4. Unread count = 0

### 5. Xóa Notification (Xóa)

1. Click **Xóa** trên notification cần xóa
2. Xác nhận xóa
3. Hệ thống xóa notification (soft xóa)

**Lưu ý**: 
- Có thể xóa notification bất cứ lúc nào
- Xóa notification không ảnh hưởng đến resource liên quan

### 6. Xem Unread Count

1. Xem unread count trên badge notification (icon thông báo)
2. Unread count hiển thị số thông báo chưa đọc
3. Click vào badge để xem danh sách thông báo

### 7. Real-thời gian Updates (Cập nhật Thời gian Thực)

Hệ thống tự động cập nhật thông báo mới:

1. Hệ thống polling thông báo mới mỗi 30 giây (hoặc real-thời gian nếu có WebSocket)
2. Unread count tự động cập nhật
3. Danh sách thông báo tự động refresh

## Ràng buộc và điều kiện

### Validation Rules

- Không có validation (chỉ xem và quản lý)

### Business Rules

1. **Quản lý chỉ thấy thông báo của chính mình**
   - Không thể thấy thông báo của Quản lý khác
   - Dữ liệu được lọc theo user_id (Quản lý)

2. **Notification Types**
   - Hợp đồng thuê created/updated
   - Hóa đơn issued
   - Thanh toán received
   - Ticket created/updated
   - Viewing confirmed/cancelled
   - Booking deposit đã phê duyệt
   - Commission event created
   - Payroll payslip generated

3. **Real-thời gian Updates**
   - Hệ thống polling hoặc WebSocket để cập nhật thông báo mới
   - Unread count tự động cập nhật

## Ví dụ

### Ví dụ 1: Xem và Mark as Read Notification

**Kịch bản:** Quản lý nhận được thông báo về hóa đơn mới

**Các bước:**
1. Click vào badge notification (unread count: 10)
2. Hệ thống hiển thị danh sách thông báo
3. Click vào notification "Hóa đơn HD-202501-0001 đã được phát hành"
4. Hệ thống tự động mark as read
5. Notification trạng thái chuyển sang `read`
6. Click vào link trong notification để xem hóa đơn

---

**Xem thêm:**
- [Môi giới Thông báo](../agent/27-notifications.md)
- [Khách thuê Thông báo](../tenant/11-notifications.md)

**Cập nhật: 2025-01-XX

