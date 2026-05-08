# QUẢN LÝ NGƯỜI THUÊ - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý người thuê (khách thuê) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, convert from lead, add residents, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả khách thuê trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `party.access`
  - Tạo khách thuê: Cần capability `party.user.create` (với role khách thuê)
  - Cập nhật khách thuê: Cần capability `party.user.update`
  - Xem tất cả khách thuê: Cần capability `party.user.view` hoặc `party.user.view_all`
  - Chỉ xem khách thuê từ hợp đồng thuê được gán: Có capability `party.user.view_own` (mặc định)
  - Xóa khách thuê: Cần capability `party.user.delete`

**Route**: `/staff/tenants`

## Các bước thực hiện

### 1. Xem danh sách Khách thuê

1. Truy cập **Khách thuê** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả khách thuê trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (hoạt động, không hoạt động)
   - Bất động sản (nếu có nhiều bất động sản)
   - Phòng (nếu có nhiều phòng)
   - Hợp đồng thuê (nếu có nhiều hợp đồng thuê)
   - Tìm kiếm by name, phone, email
   - Sắp xếp theo name, created_at, updated_at

### 2. Xem chi tiết Khách thuê

1. Click vào khách thuê trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Người dùng ID: Mã người dùng
     - Name: Họ và tên
     - Phone: Số điện thoại
     - Email: Email
     - Trạng thái: Trạng thái (hoạt động, không hoạt động)
     - Email Verified: Email đã xác thực
   - **Thông tin liên quan:**
     - Hợp đồng thuê: Danh sách hợp đồng thuê của khách thuê
     - Hóa đơn: Danh sách hóa đơn của khách thuê
     - Thanh toán: Danh sách thanh toán của khách thuê
     - Tickets: Danh sách tickets của khách thuê
     - Reviews: Danh sách reviews của khách thuê
     - Residents: Danh sách người ở cùng (nếu có)
     - Người dùng Banking: Thông tin ngân hàng (nếu có)
   - **Thống kê:**
     - Tổng Hợp đồng thuê: Tổng số hợp đồng
     - Hoạt động Hợp đồng thuê: Số hợp đồng đang hoạt động
     - Tổng Hóa đơn: Tổng số hóa đơn
     - Outstanding Số tiền: Tổng số tiền còn nợ
     - Tổng Đã thanh toán: Tổng số tiền đã thanh toán

### 3. Tạo Khách thuê mới

1. Click **Tạo Khách thuê** hoặc **+ New**
2. Điền thông tin:
   - **Name** (bắt buộc): Họ và tên
   - **Phone** (bắt buộc, unique): Số điện thoại
   - **Email** (tùy chọn, unique nếu có): Email
   - **Password** (tự động hoặc bắt buộc): Mật khẩu (tự động generate hoặc nhập)
   - **Trạng thái** (bắt buộc): `active` hoặc `inactive`
   - **Email Verified** (tự động): `false` (cần xác thực sau)
   - **Role** (tự động): `tenant`
   - **Tổ chức** (tự động): Tổ chức hiện tại
3. Click **Lưu**
4. Khách thuê Người dùng được tạo với role `tenant`
5. Hệ thống gửi email verification (nếu có email)
6. Hệ thống gửi thông báo cho Quản lý

### 4. Convert Lead to Khách thuê (Chuyển đổi Lead thành Khách thuê)

1. Truy cập chi tiết Lead cần convert
2. Click **Convert to Khách thuê** hoặc **Chuyển đổi thành Khách thuê**
3. Hệ thống tạo Khách thuê Người dùng từ thông tin Lead:
   - Name, Phone, Email từ Lead
   - Trạng thái: `active`
   - Role: `tenant`
   - Tổ chức: Tổ chức hiện tại
   - Email Verified: `false` (cần xác thực sau)
4. Lead trạng thái tự động chuyển sang `converted`
5. Khách thuê được tạo và link với Lead
6. Hệ thống gửi thông báo cho Môi giới và Quản lý

**Lưu ý**: 
- Convert to Khách thuê tạo người dùng account cho Lead
- Lead có thể được convert thành Hợp đồng thuê sau khi có Khách thuê

### 5. Cập nhật Khách thuê

1. Truy cập chi tiết khách thuê cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin:
   - Name
   - Phone (nếu unique)
   - Email (nếu unique và chưa verify)
   - Trạng thái
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Không thể sửa Email sau khi đã xác thực
- Phone phải unique trong hệ thống

### 6. Add Residents (Thêm Người ở cùng)

1. Truy cập chi tiết khách thuê
2. Scroll đến phần **Residents**
3. Click **Add Resident** hoặc **Thêm người ở cùng**
4. Điền thông tin:
   - **Name** (bắt buộc): Họ và tên
   - **Phone** (tùy chọn): Số điện thoại
   - **Email** (tùy chọn): Email
   - **ID Card Number** (tùy chọn): Số CMND/CCCD
   - **Relationship** (tùy chọn): Mối quan hệ với khách thuê
   - **Note** (tùy chọn): Ghi chú
5. Click **Lưu**
6. Resident được thêm vào khách thuê

**Lưu ý**: 
- Residents được liên kết với khách thuê
- Residents có thể được thêm vào hợp đồng thuê

### 7. Cập nhật Resident

1. Truy cập chi tiết khách thuê
2. Scroll đến phần **Residents**
3. Click **Chỉnh sửa** trên resident cần cập nhật
4. Cập nhật thông tin
5. Click **Lưu**
6. Hệ thống cập nhật và hiển thị thông báo thành công

### 8. Xóa Resident

1. Truy cập chi tiết khách thuê
2. Scroll đến phần **Residents**
3. Click **Xóa** trên resident cần xóa
4. Xác nhận xóa
5. Hệ thống xóa resident (soft xóa)

### 9. Xóa Khách thuê

1. Truy cập chi tiết khách thuê cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa khách thuê

**Lưu ý**: 
- Có thể xóa khách thuê bất cứ lúc nào
- Xóa khách thuê không ảnh hưởng đến hợp đồng thuê, hóa đơn, thanh toán đã có

### 10. Xem Thống kê

1. Truy cập **Khách thuê** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Tổng Khách thuê: Tổng số người thuê
   - Hoạt động Khách thuê: Số người thuê hoạt động
   - Khách thuê by Bất động sản: Phân bố theo bất động sản
   - Khách thuê by Period: Phân bố theo thời gian
   - Average Hợp đồng thuê Duration: Thời gian thuê trung bình
   - Outstanding Số tiền: Tổng số tiền còn nợ
   - Tổng Đã thanh toán: Tổng số tiền đã thanh toán

## Ràng buộc và điều kiện

### Validation Rules

- **Name**: 
  - Bắt buộc
  - Không được để trống
  - Max 255 ký tự
- **Phone**: 
  - Bắt buộc
  - Phải unique trong hệ thống
  - Phải đúng format số điện thoại
- **Email**: 
  - Tùy chọn
  - Phải unique trong hệ thống (nếu có)
  - Phải đúng format email (nếu có)
- **Password**: 
  - Tự động generate hoặc bắt buộc nhập
  - Tối thiểu 8 ký tự (nếu nhập)
- **Trạng thái**: 
  - Bắt buộc
  - Phải là `active` hoặc `inactive`

### Business Rules

1. **Phone Unique**
   - Phone phải unique trong hệ thống
   - Không thể có 2 người dùng cùng phone

2. **Email Unique**
   - Email phải unique trong hệ thống (nếu có)
   - Không thể có 2 người dùng cùng email

3. **Convert from Lead**
   - Convert to Khách thuê tạo người dùng account cho Lead
   - Lead trạng thái chuyển sang `converted`
   - Khách thuê được link với Lead

4. **Residents**
   - Residents được liên kết với khách thuê
   - Residents có thể được thêm vào hợp đồng thuê

5. **Trạng thái**
   - `active`: Khách thuê đang hoạt động
   - `inactive`: Khách thuê không hoạt động (tạm dừng)

## Trạng thái và Workflow

### Khách thuê Trạng thái Flow

```
inactive ↔ active
```

- **hoạt động**: Khách thuê đang hoạt động, có thể tạo hợp đồng thuê
- **không hoạt động**: Khách thuê không hoạt động, không thể tạo hợp đồng thuê mới

### Workflow Tạo Khách thuê

1. Quản lý tạo khách thuê mới hoặc convert from Lead
2. Điền thông tin: Name, Phone, Email, Password
3. Click Lưu
4. Khách thuê Người dùng được tạo với role `tenant`
5. Hệ thống gửi email verification (nếu có email)
6. Khách thuê có thể đăng nhập và sử dụng hệ thống

## Ví dụ

### Ví dụ 1: Tạo Khách thuê mới

**Thông tin khách thuê:**
- Name: `Nguyễn Văn A`
- Phone: `0123456789`
- Email: `nguyenvana@example.com`
- Password: `password123` (tự động generate hoặc nhập)
- Trạng thái: `active`
- Role: `tenant` (tự động)

**Các bước:**
1. Truy cập Khách thuê
2. Click **Tạo Khách thuê**
3. Điền thông tin trên
4. Click **Lưu**
5. Khách thuê Người dùng được tạo với role `tenant`
6. Hệ thống gửi email verification

### Ví dụ 2: Convert Lead to Khách thuê

**Kịch bản:** Lead đã quan tâm, muốn chuyển đổi thành Khách thuê

**Lead:**
- Name: `Trần Thị B`
- Phone: `0987654321`
- Email: `tranthib@example.com`
- Trạng thái: `qualified`

**Các bước:**
1. Truy cập chi tiết Lead "Trần Thị B"
2. Click **Convert to Khách thuê**
3. Hệ thống tạo Khách thuê Người dùng:
   - Name: `Trần Thị B`
   - Phone: `0987654321`
   - Email: `tranthib@example.com`
   - Trạng thái: `active`
   - Role: `tenant`
4. Lead trạng thái chuyển sang `converted`
5. Khách thuê được tạo và link với Lead

### Ví dụ 3: Add Residents

**Kịch bản:** Khách thuê muốn thêm người ở cùng

**Resident:**
- Name: `Nguyễn Thị C`
- Phone: `0912345678`
- Relationship: `Vợ/Chồng`/Chồng`
- ID Card Number: `123456789`

**Các bước:**
1. Truy cập chi tiết Khách thuê "Nguyễn Văn A"
2. Scroll đến phần Residents
3. Click **Add Resident**
4. Điền thông tin trên
5. Click **Lưu**
6. Resident được thêm vào khách thuê

## Lưu ý

1. **Phone Unique**
   - Phone phải unique trong hệ thống
   - Kiểm tra trước khi tạo khách thuê

2. **Email Unique**
   - Email phải unique trong hệ thống (nếu có)
   - Kiểm tra trước khi tạo khách thuê

3. **Convert from Lead**
   - Convert to Khách thuê tạo người dùng account cho Lead
   - Lead có thể được convert thành Hợp đồng thuê sau khi có Khách thuê

4. **Residents**
   - Thêm residents để theo dõi người ở cùng
   - Residents có thể được thêm vào hợp đồng thuê

5. **Password**
   - Password có thể tự động generate hoặc nhập thủ công
   - Nên gửi password cho khách thuê qua email hoặc SMS

## Troubleshooting

### Không thể tạo khách thuê

1. Kiểm tra Phone có unique không
2. Kiểm tra Email có unique không (nếu có)
3. Kiểm tra tất cả các trường bắt buộc đã điền chưa
4. Kiểm tra Phone và Email có đúng format không
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Không thể convert from Lead

1. Kiểm tra Phone có unique không
2. Kiểm tra Email có unique không (nếu có)
3. Nếu Phone/Email đã được sử dụng, phải cập nhật hoặc sử dụng Người dùng hiện có
4. Liên hệ hỗ trợ nếu vẫn không thể convert

### Không thể add residents

1. Kiểm tra Name đã điền chưa
2. Kiểm tra tất cả các trường bắt buộc đã điền chưa
3. Liên hệ hỗ trợ nếu vẫn không thể add residents

---

**Xem thêm:**
- [Quản lý Hợp đồng thuê](./05-leases.md)
- [Quản lý Leads](./08-leads.md)
- [Workflow Lead to Hợp đồng thuê](../workflows/01-lead-to-lease.md)

**Cập nhật: 2025-01-XX

