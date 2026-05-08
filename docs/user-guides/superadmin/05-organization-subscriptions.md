# QUẢN LÝ ĐĂNG KÝ CỦA TỔ CHỨC - SUPERADMIN

## Tổng quan

Chức năng này cho phép SuperAdmin quản lý đăng ký (subscriptions) của các tổ chức, bao gồm assign plan, hủy, extend, renew, và activate subscription.

## Quyền truy cập

- **SuperAdmin**: Có quyền quản lý tất cả subscriptions của organizations

**Route**: `/superadmin/subscriptions`

## Các bước thực hiện

### 1. Xem danh sách subscriptions

1. Truy cập **Subscriptions** từ menu SuperAdmin
2. Hệ thống hiển thị danh sách tất cả subscriptions
3. Có thể lọc theo:
   - Tổ chức
   - Plan
   - Trạng thái (hoạt động/inactive)
   - Tìm kiếm theo tổ chức name, plan name
   - Sắp xếp theo start_date, end_date, created_at

### 2. Xem chi tiết subscription của tổ chức

1. Truy cập **Organizations** → Chọn tổ chức
2. Click tab **Subscription** hoặc truy cập `/superadmin/organizations/{organization}/subscription`/superadmin/organizations/{tổ chức}/subscription`
3. Hệ thống hiển thị:
   - Subscription plan hiện tại
   - Start Ngày, End Ngày
   - Trial period
   - Trạng thái (hoạt động/inactive)
   - Features được cấp
   - Lịch sử hóa đơn

### 3. Assign Plan cho Tổ chức

1. Truy cập chi tiết tổ chức
2. Click **Assign Plan** hoặc **Subscribe**
3. Điền thông tin:
   - **Plan** (bắt buộc): Chọn subscription plan
   - **Start Ngày** (bắt buộc): Ngày bắt đầu subscription
   - **End Ngày** (tùy chọn): Ngày kết thúc (nếu không có, sẽ tính theo billing cycle)
   - **Trial Days** (tùy chọn): Số ngày dùng thử (nếu không có, sẽ lấy từ plan)
4. Click **Lưu**
5. Hệ thống tạo subscription và hiển thị thông báo thành công

**Lưu ý**: 
- Plan phải có trạng thái `active`
- Start Ngày phải >= ngày hiện tại
- End Ngày phải >= Start Ngày (nếu có)

### 4. Hủy Subscription

1. Truy cập chi tiết subscription của tổ chức
2. Click **Hủy Subscription**
3. Xác nhận hủy
4. Hệ thống cập nhật subscription trạng thái = `inactive`
5. Tổ chức vẫn có thể sử dụng đến End Ngày

**Lưu ý**: 
- Hủy không xóa subscription ngay lập tức
- Tổ chức vẫn có thể sử dụng đến End Ngày
- Sau End Ngày, tổ chức không thể sử dụng hệ thống (nếu không renew)

### 5. Extend Subscription (Gia hạn)

1. Truy cập chi tiết subscription của tổ chức
2. Click **Extend Subscription**
3. Điền thông tin:
   - **Extension Days** (bắt buộc): Số ngày muốn gia hạn
   - **New End Ngày**: End Ngày mới (tự động tính)
4. Click **Lưu**
5. Hệ thống cập nhật End Ngày và hiển thị thông báo thành công

**Lưu ý**: 
- Extension Days phải > 0
- New End Ngày = Old End Ngày + Extension Days

### 6. Renew Subscription (Gia hạn với billing cycle mới)

1. Truy cập chi tiết subscription của tổ chức
2. Click **Renew Subscription**
3. Hệ thống tự động:
   - Tạo subscription mới với:
     - Start Ngày = End Ngày của subscription cũ
     - End Ngày = Start Ngày + Billing Cycle (hàng tháng/yearly)
     - Plan = Plan hiện tại
   - Tạo subscription hóa đơn (nếu cần)
4. Click **Confirm**
5. Hệ thống tạo subscription mới và hiển thị thông báo thành công

**Lưu ý**: 
- Renew tạo subscription mới, không cập nhật subscription cũ
- Subscription cũ vẫn được giữ để audit trail

### 7. Activate Subscription (Kích hoạt)

1. Truy cập chi tiết subscription của tổ chức
2. Click **Activate Subscription**
3. Hệ thống cập nhật subscription trạng thái = `active`
4. Tổ chức có thể sử dụng hệ thống

**Lưu ý**: 
- Subscription phải có trạng thái `inactive`
- Start Ngày phải <= ngày hiện tại
- End Ngày phải >= ngày hiện tại (nếu có)

### 8. Xem Subscription Hóa đơn

1. Truy cập chi tiết subscription của tổ chức
2. Click tab **Hóa đơn** hoặc truy cập `/superadmin/organizations/{organization}/subscription/invoices`/superadmin/organizations/{tổ chức}/subscription/invoices`
3. Hệ thống hiển thị danh sách subscription hóa đơn với:
   - Hóa đơn Number
   - Issue Ngày, Đến hạn Ngày
   - Số tiền
   - Trạng thái (đã thanh toán/unpaid/overdue)
   - Thanh toán Ngày (nếu đã thanh toán)

## Ràng buộc và điều kiện

### Validation Rules

- **Plan**: 
  - Bắt buộc
  - Phải tồn tại và có trạng thái `active`
- **Start Ngày**: 
  - Bắt buộc
  - Phải là ngày hợp lệ
  - Phải >= ngày hiện tại (khi assign)
- **End Ngày**: 
  - Tùy chọn
  - Phải >= Start Ngày (nếu có)
  - Phải là ngày hợp lệ
- **Trial Days**: 
  - Tùy chọn
  - Phải >= 0
  - Nếu không có, sẽ lấy từ plan

### Business Rules

1. **Một tổ chức chỉ có 1 subscription hoạt động tại một thời điểm**
   - Khi assign plan mới, subscription cũ sẽ bị không hoạt động (nếu còn hoạt động)
   - Hoặc renew để tạo subscription mới

2. **Auto-enforcement**
   - Middleware kiểm tra subscription limits
   - Không cho phép vượt quá limits
   - Ví dụ: max_properties = 10, không thể tạo bất động sản thứ 11

3. **Trial Period**
   - Trial period cho phép tổ chức dùng thử miễn phí
   - Sau khi hết trial, tổ chức phải thanh toán hoặc hủy

4. **Billing Cycle**
   - Hàng tháng: Gia hạn mỗi tháng
   - Hàng năm: Gia hạn mỗi năm
   - Subscription hóa đơn được tạo tự động khi renew

## Trạng thái và Workflow

### Trạng thái Flow

```
inactive → active → inactive
```

- **hoạt động**: Subscription đang hoạt động, tổ chức có thể sử dụng hệ thống
- **không hoạt động**: Subscription bị hủy hoặc hết hạn, tổ chức không thể sử dụng hệ thống

### Workflow Assign Plan

1. SuperAdmin assign plan cho tổ chức
2. Điền Start Ngày, End Ngày (tùy chọn), Trial Days (tùy chọn)
3. Hệ thống tạo subscription với trạng thái = `active`
4. Tổ chức có thể sử dụng hệ thống
5. Sau khi hết trial, tổ chức phải thanh toán hoặc hủy

### Workflow Renew Subscription

1. Subscription sắp hết hạn (gần End Ngày)
2. SuperAdmin renew subscription
3. Hệ thống tạo subscription mới với:
   - Start Ngày = End Ngày của subscription cũ
   - End Ngày = Start Ngày + Billing Cycle
4. Tạo subscription hóa đơn (nếu cần)
5. Tổ chức tiếp tục sử dụng hệ thống

## Ví dụ

### Ví dụ 1: Assign Plan

**Thông tin subscription:**
- Tổ chức: `ORG001`
- Plan: `BASIC`
- Start Ngày: `2025-01-01`
- End Ngày: `2025-12-31` (hàng năm)
- Trial Days: `7`

**Các bước:**
1. Truy cập tổ chức `ORG001`
2. Click **Assign Plan**
3. Chọn plan `BASIC`
4. Điền Start Ngày, End Ngày, Trial Days
5. Click **Lưu**
6. Hệ thống tạo subscription với trạng thái `active`

### Ví dụ 2: Renew Subscription

**Kịch bản:** Subscription sắp hết hạn vào 2025-12-31

**Các bước:**
1. Truy cập subscription của tổ chức
2. Click **Renew Subscription**
3. Hệ thống tự động:
   - Tạo subscription mới:
     - Start Ngày: `2026-01-01`
     - End Ngày: `2026-12-31` (nếu billing cycle = hàng năm)
   - Tạo subscription hóa đơn
4. Click **Confirm**
5. Hệ thống tạo subscription mới

## Lưu ý

1. **Subscription Limits**
   - Tổ chức không thể vượt quá limits của plan
   - Hệ thống tự động kiểm tra khi tổ chức thực hiện thao tác

2. **Trial Period**
   - Trial period cho phép tổ chức dùng thử
   - Sau khi hết trial, tổ chức phải thanh toán hoặc hủy

3. **Renew vs Extend**
   - Renew: Tạo subscription mới với billing cycle mới
   - Extend: Gia hạn subscription hiện tại với số ngày cụ thể

4. **Auto-enforcement**
   - Middleware tự động kiểm tra limits
   - Không cho phép vượt quá limits

5. **Subscription Hóa đơn**
   - Hóa đơn được tạo tự động khi renew
   - Tổ chức phải thanh toán hóa đơn

## Troubleshooting

### Không thể assign plan

1. Kiểm tra plan có trạng thái `active` không
2. Kiểm tra Start Ngày có >= ngày hiện tại không
3. Kiểm tra End Ngày có >= Start Ngày không
4. Kiểm tra tổ chức đã có subscription hoạt động chưa (nếu có, phải hủy hoặc renew trước)

### Tổ chức không thể sử dụng hệ thống

1. Kiểm tra subscription có trạng thái `active` không
2. Kiểm tra Start Ngày có <= ngày hiện tại không
3. Kiểm tra End Ngày có >= ngày hiện tại không
4. Kiểm tra subscription limits có bị vượt quá không

### Subscription bị expire

1. Kiểm tra End Ngày có <= ngày hiện tại không
2. Renew subscription hoặc extend subscription
3. Hoặc assign plan mới

---

**Lưu ý**: Subscription management là cốt lõi của mô hình SAAS, cần cẩn thận khi assign và renew subscription.

**Cập nhật**: 2025-11-02

