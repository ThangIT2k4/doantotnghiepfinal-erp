# QUẢN LÝ QUYỀN HẠN - STAFF

## Tổng quan

Chức năng này cho phép Quản lý quản lý quyền hạn (capabilities) cho người dùng trong tổ chức, bao gồm xem, grant, revoke, bulk cập nhật.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ quản lý capabilities cho tất cả người dùng trong tổ chức (wildcard `*` = true)
- **Môi giới**: Không có quyền truy cập chức năng này (chỉ Quản lý)

**Route**: `/staff/capabilities`

## Các bước thực hiện

### 1. Xem danh sách Capabilities

1. Truy cập **Capabilities** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả capabilities trong hệ thống
3. Có thể lọc theo:
   - Category (bất động sản, phòng, hợp đồng thuê, hóa đơn, thanh toán, etc.)
   - Tìm kiếm by name
   - Sắp xếp theo name, category

### 2. Xem Người dùng Capabilities

1. Truy cập chi tiết Người dùng hoặc **Người dùng** → **Capabilities**
2. Chọn Người dùng từ danh sách
3. Hệ thống hiển thị:
   - **Current Capabilities**: Danh sách quyền hạn hiện tại của người dùng
   - **Available Capabilities**: Danh sách quyền hạn có sẵn
   - **Mặc định Capabilities**: Quyền hạn mặc định theo role

### 3. Grant Capability (Cấp quyền)

1. Truy cập **Người dùng** → **Capabilities** → Chọn Người dùng
2. Scroll đến phần **Available Capabilities**
3. Click **Grant** trên capability cần cấp
4. Capability được thêm vào người dùng
5. Hệ thống gửi thông báo cho người dùng (nếu có)

**Lưu ý**: 
- Grant capability cho phép người dùng sử dụng chức năng tương ứng
- Capability được lưu vào database

### 4. Revoke Capability (Thu hồi quyền)

1. Truy cập **Người dùng** → **Capabilities** → Chọn Người dùng
2. Scroll đến phần **Current Capabilities**
3. Click **Revoke** trên capability cần thu hồi
4. Capability được xóa khỏi người dùng
5. Hệ thống gửi thông báo cho người dùng (nếu có)

**Lưu ý**: 
- Revoke capability sẽ xóa quyền hạn của người dùng
- Người dùng không thể sử dụng chức năng tương ứng sau khi revoke

### 5. Bulk Cập nhật Capabilities (Cập nhật Hàng loạt)

1. Truy cập **Người dùng** → **Capabilities** → Chọn Người dùng
2. Click **Bulk Cập nhật** hoặc **Cập nhật hàng loạt**
3. Chọn nhiều capabilities:
   - **Grant**: Cấp quyền cho tất cả capabilities được chọn
   - **Revoke**: Thu hồi quyền cho tất cả capabilities được chọn
4. Click **Apply**
5. Hệ thống xử lý hàng loạt và hiển thị kết quả

### 6. Xem Người dùng với Capability

1. Truy cập **Capabilities**
2. Click vào capability cần xem
3. Hệ thống hiển thị danh sách người dùng có capability đó
4. Click vào người dùng để xem chi tiết

### 7. Xem Thống kê

1. Truy cập **Capabilities** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Tổng Capabilities: Tổng số quyền hạn
   - Capabilities by Category: Phân bố theo danh mục
   - Người dùng by Capability: Số người dùng có từng capability

## Ràng buộc và điều kiện

### Validation Rules

- **Người dùng**: Phải tồn tại và thuộc về tổ chức
- **Capability**: Phải tồn tại trong hệ thống

### Business Rules

1. **Mặc định Capabilities**
   - Mỗi role có mặc định capabilities
   - Quản lý và Môi giới có mặc định capabilities theo role
   - Capabilities có thể được grant/revoke bởi Quản lý

2. **Capability Check**
   - Controllers kiểm tra capability trước khi cho phép sử dụng chức năng
   - Gate::authorize hoặc CapabilityService::checkCapability

3. **Grant/Revoke**
   - Grant capability cho phép người dùng sử dụng chức năng
   - Revoke capability thu hồi quyền hạn của người dùng

## Ví dụ

### Ví dụ 1: Grant Capability cho Môi giới

**Kịch bản:** Quản lý muốn cấp quyền `invoice.create` cho Môi giới

**Các bước:**
1. Truy cập Người dùng → Capabilities
2. Chọn Môi giới cần cấp quyền
3. Scroll đến phần Available Capabilities
4. Tìm capability `invoice.create`
5. Click **Grant**
6. Capability được thêm vào Môi giới
7. Môi giới có thể tạo hóa đơn sau đó

### Ví dụ 2: Bulk Cập nhật Capabilities

**Kịch bản:** Quản lý muốn cấp nhiều quyền cho Môi giới cùng lúc

**Các bước:**
1. Truy cập Người dùng → Capabilities
2. Chọn Môi giới cần cập nhật quyền
3. Click **Bulk Cập nhật**
4. Chọn capabilities:
   - `invoice.create`
   - `invoice.update`
   - `payment.create`
5. Chọn action: **Grant**
6. Click **Apply**
7. Tất cả capabilities được grant cho Môi giới

---

**Xem thêm:**
- [Quản lý Người dùng](./28-users.md)
- [Capability Management Guide](../../CAPABILITY_MANAGEMENT_GUIDE.md)

**Cập nhật: 2025-01-XX

