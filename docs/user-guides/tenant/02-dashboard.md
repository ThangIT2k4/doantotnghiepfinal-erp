# DASHBOARD - TENANT

## Tổng quan

Bảng điều khiển của Khách thuê hiển thị tổng quan về hợp đồng, hóa đơn, thanh toán, và thông báo của người thuê.

## Quyền truy cập

- **Khách thuê**: Có quyền truy cập bảng điều khiển

**Route**: `/tenant/dashboard`

## Các bước thực hiện

### 1. Truy cập Bảng điều khiển

1. Đăng nhập với tài khoản Khách thuê
2. Hệ thống tự động redirect đến `/tenant/dashboard`
3. Hoặc click **Bảng điều khiển** từ menu Khách thuê

### 2. Xem Tổng quan

Bảng điều khiển hiển thị các module sau:

#### 2.1. Hợp đồng Module (Hợp đồng)

- **Hoạt động Hợp đồng**: Số hợp đồng đang hoạt động
- **Tổng Hợp đồng**: Tổng số hợp đồng
- **Expiring Soon**: Số hợp đồng sắp hết hạn (trong 30 ngày)
- **Danh sách hợp đồng gần đây**

#### 2.2. Hóa đơn Module (Hóa đơn)

- **Unpaid Hóa đơn**: Số hóa đơn chưa thanh toán
- **Tổng Số tiền Đến hạn**: Tổng số tiền còn nợ
- **Overdue Hóa đơn**: Số hóa đơn quá hạn
- **Danh sách hóa đơn gần đây**

#### 2.3. Thanh toán Module (Thanh toán)

- **Tổng Đã thanh toán**: Tổng số tiền đã thanh toán
- **Recent Thanh toán**: Các thanh toán gần đây
- **Thanh toán Methods**: Phương thức thanh toán đã sử dụng

#### 2.4. Tickets Module (Yêu cầu bảo trì)

- **Open Tickets**: Số ticket đang mở
- **Tổng Tickets**: Tổng số ticket
- **Resolved Tickets**: Số ticket đã giải quyết
- **Danh sách ticket gần đây**

#### 2.5. Appointments Module (Lịch xem phòng)

- **Upcoming Appointments**: Lịch xem phòng sắp tới
- **Tổng Appointments**: Tổng số lịch xem phòng
- **Danh sách appointments gần đây**

#### 2.6. Thông báo Module (Thông báo)

- **Unread Thông báo**: Số thông báo chưa đọc
- **Recent Thông báo**: Thông báo gần đây
- **Notification Types**: Loại thông báo (hóa đơn, thanh toán, ticket, etc.)

### 3. Xem Chi tiết

1. Click vào số liệu hoặc card trong module
2. Hệ thống redirect đến trang chi tiết tương ứng:
   - Hợp đồng → `/tenant/contracts`
   - Hóa đơn → `/tenant/invoices`
   - Thanh toán → `/tenant/payments`
   - Tickets → `/tenant/tickets`
   - Appointments → `/tenant/appointments`
   - Thông báo → `/tenant/notifications`

### 4. Quick Hành động

Bảng điều khiển cung cấp các quick hành động:

- **Pay Hóa đơn**: Thanh toán hóa đơn nhanh
- **Tạo Ticket**: Tạo ticket bảo trì nhanh
- **Xem Hợp đồng**: Xem hợp đồng nhanh
- **Book Appointment**: Đặt lịch xem phòng nhanh

## Ràng buộc và điều kiện

### Data Refresh

- Bảng điều khiển tự động refresh mỗi 5 phút
- Có thể refresh thủ công bằng cách refresh trang
- Dữ liệu được tính từ database real-thời gian

### Thống kê Calculation

- Thống kê được tính từ dữ liệu của Khách thuê hiện tại
- Chỉ hiển thị dữ liệu của Khách thuê đang đăng nhập
- Dữ liệu được lọc theo tổ chức của Khách thuê

## Ví dụ

### Ví dụ 1: Xem Tổng quan

**Kịch bản:** Khách thuê muốn xem tổng quan về hợp đồng và hóa đơn

**Các bước:**
1. Đăng nhập với tài khoản Khách thuê
2. Hệ thống tự động redirect đến bảng điều khiển
3. Xem các module:
   - Hợp đồng: 1 hoạt động, 1 tổng
   - Hóa đơn: 2 unpaid, 5,000,000 VND đến hạn
   - Thanh toán: 10,000,000 VND tổng đã thanh toán
   - Tickets: 1 open, 3 tổng
   - Appointments: 0 upcoming
   - Thông báo: 5 unread

## Lưu ý

1. **Data Privacy**
   - Bảng điều khiển chỉ hiển thị dữ liệu của Khách thuê đang đăng nhập
   - Không thể thấy dữ liệu của Khách thuê khác

2. **Real-thời gian Updates**
   - Dữ liệu được cập nhật real-thời gian từ database
   - Refresh trang để cập nhật dữ liệu mới nhất

3. **Quick Hành động**
   - Sử dụng quick hành động để thực hiện thao tác nhanh
   - Tiết kiệm thời gian điều hướng

## Troubleshooting

### Bảng điều khiển không hiển thị dữ liệu

1. Refresh trang
2. Kiểm tra kết nối mạng
3. Kiểm tra người dùng có hợp đồng hoạt động không
4. Liên hệ hỗ trợ nếu vẫn không hiển thị

### Thống kê không chính xác

1. Refresh trang
2. Chờ vài phút để hệ thống tính lại
3. Kiểm tra dữ liệu trong Hợp đồng, Hóa đơn, Thanh toán
4. Liên hệ hỗ trợ nếu vẫn không chính xác

---

**Lưu ý**: Bảng điều khiển cung cấp cái nhìn tổng quan về tình trạng hợp đồng, hóa đơn, và các hoạt động của Khách thuê.

**Cập nhật**: 2025-11-02

