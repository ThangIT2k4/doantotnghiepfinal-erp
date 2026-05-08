# DASHBOARD - SUPERADMIN

## Tổng quan

Bảng điều khiển của SuperAdmin hiển thị tổng quan toàn hệ thống, bao gồm thống kê về organizations, người dùng, subscriptions, và revenue.

## Quyền truy cập

- **SuperAdmin**: Có quyền truy cập bảng điều khiển

**Route**: `/superadmin/dashboard`

## Các bước thực hiện

### 1. Truy cập Bảng điều khiển

1. Đăng nhập với tài khoản SuperAdmin
2. Hệ thống tự động redirect đến `/superadmin/dashboard`
3. Hoặc click **Bảng điều khiển** từ menu SuperAdmin

### 2. Xem Tổng quan Hệ thống

Bảng điều khiển hiển thị các module sau:

#### 2.1. Organizations Module

- **Tổng Organizations**: Tổng số tổ chức trong hệ thống
- **Hoạt động Organizations**: Số tổ chức đang hoạt động
- **Không hoạt động Organizations**: Số tổ chức không hoạt động
- **New Organizations (This Month)**: Số tổ chức mới trong tháng này

#### 2.2. Người dùng Module

- **Tổng Người dùng**: Tổng số người dùng trong hệ thống
- **Hoạt động Người dùng**: Số người dùng đang hoạt động
- **Không hoạt động Người dùng**: Số người dùng không hoạt động
- **New Người dùng (This Month)**: Số người dùng mới trong tháng này

#### 2.3. Subscriptions Module

- **Tổng Subscriptions**: Tổng số subscriptions
- **Hoạt động Subscriptions**: Số subscriptions đang hoạt động
- **Expired Subscriptions**: Số subscriptions đã hết hạn
- **Trial Subscriptions**: Số subscriptions đang trong trial period

#### 2.4. Revenue Module (Nếu có)

- **Tổng Revenue**: Tổng doanh thu từ subscriptions
- **Hàng tháng Revenue**: Doanh thu tháng này
- **Revenue Chart**: Biểu đồ doanh thu theo tháng/năm

### 3. Xem Chi tiết

1. Click vào số liệu trong module
2. Hệ thống redirect đến trang chi tiết tương ứng:
   - Organizations → `/superadmin/organizations`
   - Người dùng → `/superadmin/users`
   - Subscriptions → `/superadmin/subscriptions`

### 4. Clear Cache

1. Click **Clear Cache** trên bảng điều khiển
2. Hệ thống xóa cache và refresh bảng điều khiển
3. Sử dụng khi dữ liệu không được cập nhật

## Ràng buộc và điều kiện

### Data Refresh

- Bảng điều khiển tự động refresh mỗi 5 phút
- Có thể refresh thủ công bằng cách refresh trang
- Clear cache để force refresh

### Thống kê Calculation

- Thống kê được tính từ database
- Có thể bị delay nếu có nhiều dữ liệu
- Cache được sử dụng để tối ưu hiệu suất

## Ví dụ

### Ví dụ 1: Xem Tổng quan

**Kịch bản:** SuperAdmin muốn xem tổng quan hệ thống

**Các bước:**
1. Đăng nhập với tài khoản SuperAdmin
2. Hệ thống tự động redirect đến bảng điều khiển
3. Xem các module:
   - Organizations: 10 tổng, 8 hoạt động, 2 không hoạt động
   - Người dùng: 100 tổng, 80 hoạt động, 20 không hoạt động
   - Subscriptions: 8 hoạt động, 2 expired
   - Revenue: 10,000,000 VND (tháng này)

## Lưu ý

1. **Data Accuracy**
   - Thống kê có thể bị delay
   - Refresh trang hoặc clear cache để cập nhật

2. **Performance**
   - Cache được sử dụng để tối ưu hiệu suất
   - Clear cache nếu dữ liệu không chính xác

3. **Permissions**
   - Chỉ SuperAdmin có thể truy cập bảng điều khiển
   - Các role khác có bảng điều khiển riêng

## Troubleshooting

### Bảng điều khiển không hiển thị dữ liệu

1. Refresh trang
2. Clear cache
3. Kiểm tra kết nối database
4. Liên hệ quản trị viên hệ thống

### Thống kê không chính xác

1. Clear cache
2. Refresh trang
3. Kiểm tra dữ liệu trong database
4. Chờ vài phút để hệ thống tính lại

---

**Lưu ý**: Bảng điều khiển cung cấp cái nhìn tổng quan về hệ thống, giúp SuperAdmin theo dõi và quản lý hiệu quả.

**Cập nhật**: 2025-11-02

