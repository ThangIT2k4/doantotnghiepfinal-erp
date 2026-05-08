# QUẢN LÝ TỔ CHỨC - SUPERADMIN

## Tổng quan

Chức năng này cho phép SuperAdmin quản lý các tổ chức (organizations) trong hệ thống, bao gồm tạo, xem, cập nhật, xóa, và toggle trạng thái của tổ chức.

## Quyền truy cập

- **SuperAdmin**: Có quyền quản lý tất cả tổ chức

**Route**: `/superadmin/organizations`

## Các bước thực hiện

### 1. Xem danh sách tổ chức

1. Truy cập **Organizations** từ menu SuperAdmin
2. Hệ thống hiển thị danh sách tất cả tổ chức
3. Có thể lọc theo:
   - Trạng thái (hoạt động/inactive)
   - Tìm kiếm theo name, code, email
   - Sắp xếp theo name, created_at, trạng thái

### 2. Xem chi tiết tổ chức

1. Click vào tên tổ chức hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - Thông tin cơ bản (code, name, email, phone, address)
   - Thông tin liên hệ
   - Tax code
   - Trạng thái
   - Danh sách người dùng trong tổ chức
   - Subscription hiện tại
   - Thống kê (bất động sản, phòng, hợp đồng thuê, người dùng)

### 3. Tạo tổ chức mới

1. Click **Tạo Tổ chức** hoặc **+ New**
2. Điền thông tin:
   - **Code** (bắt buộc, unique): Mã định danh tổ chức
   - **Name** (bắt buộc): Tên tổ chức
   - **Email** (bắt buộc, đúng format): Email liên hệ
   - **Phone** (tùy chọn): Số điện thoại
   - **Address** (tùy chọn): Địa chỉ
   - **Tax Code** (tùy chọn): Mã số thuế
   - **Trạng thái** (bắt buộc): hoạt động hoặc không hoạt động
3. Click **Lưu**
4. Hệ thống tạo tổ chức và hiển thị thông báo thành công

### 4. Cập nhật thông tin tổ chức

1. Truy cập chi tiết tổ chức cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin cần thay đổi
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Không thể sửa Code sau khi tạo
- Email phải đúng format
- Tax Code phải đúng format (nếu có)

### 5. Xóa tổ chức (Soft Xóa)

1. Truy cập chi tiết tổ chức cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa (không xóa vĩnh viễn)

**Lưu ý**: 
- Soft xóa chỉ đánh dấu tổ chức là deleted, không xóa dữ liệu
- Dữ liệu vẫn được lưu trong database
- Có thể restore sau nếu cần

### 6. Toggle Trạng thái (Kích hoạt/Vô hiệu hóa)

1. Truy cập danh sách tổ chức hoặc chi tiết tổ chức
2. Click **Toggle Trạng thái** hoặc switch **Trạng thái**
3. Hệ thống cập nhật trạng thái:
   - `active` → `inactive`
   - `inactive` → `active`

**Lưu ý**: 
- Tổ chức không hoạt động không thể đăng nhập
- Người dùng trong tổ chức không hoạt động cũng không thể đăng nhập
- Dữ liệu vẫn được giữ nguyên

### 7. Xem danh sách Người dùng của tổ chức

1. Truy cập chi tiết tổ chức
2. Click tab **Người dùng** hoặc scroll đến phần **Người dùng**
3. Hệ thống hiển thị danh sách tất cả người dùng trong tổ chức với:
   - Thông tin người dùng (name, email, phone)
   - Role trong tổ chức
   - Trạng thái
   - Ngày tham gia

### 8. Xem Subscription của tổ chức

1. Truy cập chi tiết tổ chức
2. Click tab **Subscription** hoặc scroll đến phần **Subscription**
3. Hệ thống hiển thị:
   - Subscription plan hiện tại
   - Start Ngày, End Ngày
   - Trial period
   - Trạng thái
   - Features được cấp

## Ràng buộc và điều kiện

### Validation Rules

- **Code**: 
  - Bắt buộc
  - Phải unique trong hệ thống
  - Không được để trống
  - Không thể sửa sau khi tạo
- **Name**: 
  - Bắt buộc
  - Không được để trống
- **Email**: 
  - Bắt buộc
  - Phải đúng format email (RFC 5322)
  - Phải unique (nếu có)
- **Phone**: 
  - Tùy chọn
  - Phải đúng format số điện thoại (nếu có)
- **Tax Code**: 
  - Tùy chọn
  - Phải đúng format mã số thuế (nếu có)
- **Trạng thái**: 
  - Bắt buộc
  - Phải là `active` hoặc `inactive`

### Business Rules

1. **Không thể xóa tổ chức có người dùng đang hoạt động**
   - Phải không hoạt động tổ chức trước
   - Hoặc chuyển người dùng sang tổ chức khác

2. **Không thể xóa tổ chức có dữ liệu quan trọng**
   - Bất động sản, Phòng, Hợp đồng thuê, Hóa đơn, Thanh toán
   - Hệ thống chỉ thực hiện soft xóa

3. **Tổ chức Isolation**
   - Mỗi tổ chức chỉ thấy dữ liệu của mình
   - SuperAdmin có thể thấy tất cả

## Trạng thái và Workflow

### Trạng thái Flow

```
active ←→ inactive
```

- **hoạt động**: Tổ chức đang hoạt động, người dùng có thể đăng nhập
- **không hoạt động**: Tổ chức bị vô hiệu hóa, người dùng không thể đăng nhập

### Workflow Tạo Tổ chức

1. SuperAdmin tạo tổ chức mới
2. Điền thông tin bắt buộc (Code, Name, Email)
3. Set trạng thái = `active`
4. Hệ thống tạo tổ chức
5. Có thể assign subscription plan cho tổ chức
6. Có thể tạo người dùng và gán role cho tổ chức

## Ví dụ

### Ví dụ 1: Tạo tổ chức mới

**Thông tin tổ chức:**
- Code: `ORG001`
- Name: `Công ty Bất động sản ABC`
- Email: `contact@abc-realestate.com`
- Phone: `0123456789`
- Address: `123 Đường ABC, Quận 1, TP.HCM`
- Tax Code: `1234567890`
- Trạng thái: `active`

**Các bước:**
1. Click **Tạo Tổ chức**
2. Điền thông tin trên
3. Click **Lưu**
4. Hệ thống tạo tổ chức với ID tự động

### Ví dụ 2: Toggle Trạng thái

**Kịch bản:** Vô hiệu hóa tổ chức tạm thời

**Các bước:**
1. Truy cập danh sách tổ chức
2. Tìm tổ chức cần vô hiệu hóa
3. Click switch **Trạng thái** để chuyển từ `active` sang `inactive`
4. Xác nhận
5. Tổ chức và người dùng trong tổ chức không thể đăng nhập

## Lưu ý

1. **Code là unique và không thể sửa**
   - Chọn code cẩn thận khi tạo tổ chức
   - Code dùng để định danh tổ chức trong hệ thống

2. **Soft Xóa**
   - Xóa tổ chức chỉ đánh dấu deleted, không xóa dữ liệu
   - Dữ liệu vẫn được giữ để audit trail
   - Có thể restore sau nếu cần

3. **Tổ chức Isolation**
   - Mỗi tổ chức độc lập với nhau
   - Không thể thấy dữ liệu của tổ chức khác
   - SuperAdmin là exception, có thể thấy tất cả

4. **Trạng thái Management**
   - Không hoạt động tổ chức sẽ ngăn tất cả người dùng trong tổ chức đăng nhập
   - Dùng để tạm dừng hoạt động hoặc suspend tổ chức

## Troubleshooting

### Không thể tạo tổ chức mới

1. Kiểm tra Code có bị trùng không
2. Kiểm tra Email có đúng format không
3. Kiểm tra tất cả các trường bắt buộc đã điền chưa
4. Liên hệ quản trị viên hệ thống

### Không thể xóa tổ chức

1. Kiểm tra tổ chức có người dùng đang hoạt động không
2. Kiểm tra tổ chức có dữ liệu quan trọng không
3. Hệ thống chỉ hỗ trợ soft xóa
4. Nếu cần xóa vĩnh viễn, liên hệ quản trị viên hệ thống

### Người dùng không thể đăng nhập

1. Kiểm tra trạng thái của tổ chức (phải là `active`)
2. Kiểm tra trạng thái của người dùng (phải là `active`)
3. Kiểm tra người dùng có thuộc tổ chức không
4. Kiểm tra người dùng có role phù hợp không

---

**Lưu ý**: SuperAdmin có quyền quản lý tất cả tổ chức trong hệ thống. Cần cẩn thận khi thực hiện các thao tác quan trọng như xóa hoặc vô hiệu hóa tổ chức.

**Cập nhật**: 2025-11-02

