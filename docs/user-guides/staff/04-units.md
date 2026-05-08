# QUẢN LÝ PHÒNG/CĂN - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý phòng/căn (phòng) trong tổ chức, bao gồm tạo (single/bulk), xem, cập nhật, xóa, và quản lý amenities, trạng thái.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả phòng trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `asset.access`
  - Tạo phòng: Cần capability `asset.unit.create`
  - Cập nhật phòng: Cần capability `asset.unit.update`
  - Xem tất cả phòng: Cần capability `asset.unit.view` hoặc `asset.unit.view_all`
  - Chỉ xem phòng từ bất động sản được gán: Có capability `asset.unit.view_own` (mặc định)
  - Xóa phòng: Cần capability `asset.unit.delete`

**Route**: `/staff/units`

## Các bước thực hiện

### 1. Xem danh sách Phòng

1. Truy cập **Phòng** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả phòng trong tổ chức
3. Có thể lọc theo:
   - Bất động sản (nếu có nhiều bất động sản)
   - Trạng thái (available, reserved, occupied, maintenance)
   - Phòng Loại (room, apartment, dorm, shared)
   - Tìm kiếm by code, name
   - Sắp xếp theo code, bất động sản, trạng thái, created_at

### 2. Xem chi tiết Phòng

1. Click vào phòng trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Phòng ID, Code, Name
     - Bất động sản
     - Phòng Loại (room, apartment, dorm, shared)
     - Max Occupancy
     - Base Rent, Deposit Số tiền
     - Area (m²)
     - Trạng thái (available, reserved, occupied, maintenance)
     - Description
   - **Thông tin liên quan:**
     - Amenities: Danh sách tiện ích
     - Hợp đồng thuê: Danh sách hợp đồng thuê của phòng
     - Booking Deposits: Danh sách đặt cọc
     - Hóa đơn: Danh sách hóa đơn
     - Tickets: Danh sách tickets
   - **Thống kê:**
     - Tổng Hợp đồng thuê
     - Hoạt động Hợp đồng thuê
     - Tổng Revenue
     - Outstanding Số tiền
     - Booking Deposits

### 3. Tạo Phòng (Single)

1. Click **Tạo Phòng** hoặc **+ New**
2. Chọn **Creation Mode**: `Single`
3. Điền thông tin:
   - **Bất động sản** (bắt buộc): Chọn bất động sản
   - **Code** (bắt buộc, unique): Mã phòng
   - **Phòng Loại** (bắt buộc): Loại phòng (room, apartment, dorm, shared)
   - **Max Occupancy** (bắt buộc): Số người tối đa
   - **Base Rent** (bắt buộc): Tiền thuê cơ bản
   - **Deposit Số tiền** (tùy chọn): Tiền cọc
   - **Area (m²)** (tùy chọn): Diện tích
   - **Trạng thái** (bắt buộc): `available`, `reserved`, `occupied`, `maintenance`
   - **Description** (tùy chọn): Mô tả
   - **Amenities** (tùy chọn): Chọn tiện ích (điều hòa, wifi, tủ lạnh, etc.)
   - **Images** (tùy chọn): Upload hình ảnh phòng
4. Click **Lưu**
5. Phòng được tạo và hiển thị trong danh sách

### 4. Tạo Phòng (Bulk)

1. Click **Tạo Phòng** hoặc **+ New**
2. Chọn **Creation Mode**: `Bulk`
3. Điền thông tin chung:
   - **Bất động sản** (bắt buộc): Chọn bất động sản
   - **Phòng Loại** (bắt buộc): Loại phòng
   - **Max Occupancy** (bắt buộc): Số người tối đa
   - **Base Rent** (bắt buộc): Tiền thuê cơ bản
   - **Deposit Số tiền** (tùy chọn): Tiền cọc
   - **Area (m²)** (tùy chọn): Diện tích
   - **Trạng thái** (bắt buộc): `available`
   - **Amenities** (tùy chọn): Chọn tiện ích
4. Điền thông tin bulk:
   - **Starting Number**: Số bắt đầu (ví dụ: 101)
   - **Ending Number**: Số kết thúc (ví dụ: 120)
   - **Code Prefix**: Tiền tố code (ví dụ: "P")
   - **Code Format**: Format code (ví dụ: "{prefix}{number}")
5. Click **Lưu**
6. Hệ thống tạo nhiều phòng:
   - Từ Starting Number đến Ending Number
   - Code được tạo theo format
   - Tất cả phòng có thông tin chung giống nhau

**Lưu ý**: 
- Bulk creation tạo nhiều phòng cùng lúc
- Code format ví dụ: "P101", "P102", ..., "P120"
- Tất cả phòng được tạo với trạng thái `available`

### 5. Cập nhật Phòng

1. Truy cập chi tiết phòng cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin cần thay đổi
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Không thể thay đổi Bất động sản sau khi đã tạo
- Có thể thay đổi trạng thái, amenities, rent, etc.

### 6. Xóa Phòng

1. Truy cập chi tiết phòng cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa phòng

**Lưu ý**: 
- Chỉ có thể xóa phòng không có hợp đồng thuê hoạt động hoặc booking deposits hoạt động
- Xóa phòng sẽ không ảnh hưởng đến hợp đồng thuê đã kết thúc

### 7. Quản lý Amenities

1. Truy cập chi tiết phòng
2. Scroll đến phần **Amenities**
3. Click **Chỉnh sửa Amenities**
4. Chọn/bỏ chọn amenities:
   - Điều hòa
   - WiFi
   - Tủ lạnh
   - Máy nước nóng
   - Nội thất
   - Và nhiều amenities khác
5. Click **Lưu**
6. Hệ thống cập nhật amenities

### 8. Thay đổi Trạng thái

1. Truy cập chi tiết phòng
2. Click **Change Trạng thái** hoặc **Chỉnh sửa**
3. Chọn trạng thái mới:
   - **available**: Phòng trống, có thể cho thuê
   - **reserved**: Phòng đã được đặt cọc
   - **occupied**: Phòng đang có người thuê
   - **maintenance**: Phòng đang bảo trì
4. Click **Lưu**
5. Hệ thống cập nhật trạng thái

**Lưu ý**: 
- Trạng thái `occupied` được set tự động khi có hợp đồng thuê hoạt động
- Trạng thái `reserved` được set tự động khi có booking deposit đã phê duyệt
- Có thể set `maintenance` khi cần bảo trì

### 9. Upload/Delete Images

1. Truy cập chi tiết phòng
2. Scroll đến phần **Images**
3. Click **Upload Images** (nếu muốn thêm)
4. Chọn files ảnh
5. Click **Upload**
6. Hoặc click **Xóa** trên ảnh cần xóa
7. Hệ thống cập nhật images

## Ràng buộc và điều kiện

### Validation Rules

- **Bất động sản**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
- **Code**: 
  - Bắt buộc
  - Phải unique trong bất động sản (hoặc tổ chức)
  - Format: Alphanumeric, có thể có dấu gạch dưới
- **Phòng Loại**: 
  - Bắt buộc
  - Phải là một trong: room, apartment, dorm, shared
- **Max Occupancy**: 
  - Bắt buộc
  - Phải >= 1
- **Base Rent**: 
  - Bắt buộc
  - Phải >= 0
- **Deposit Số tiền**: 
  - Tùy chọn
  - Phải >= 0
- **Area (m²)**: 
  - Tùy chọn
  - Phải >= 0
- **Trạng thái**: 
  - Bắt buộc
  - Phải là một trong: available, reserved, occupied, maintenance
- **Images**: 
  - Tùy chọn
  - Phải là file ảnh hợp lệ
  - Max size: 2MB mỗi file

### Business Rules

1. **Code phải unique trong bất động sản**
   - Không thể có 2 phòng cùng code trong 1 bất động sản
   - Code có thể trùng giữa các bất động sản

2. **Không thể xóa phòng có hợp đồng thuê hoạt động**
   - Phải terminate tất cả hợp đồng thuê hoạt động trước
   - Hoặc chờ hợp đồng thuê hết hạn

3. **Không thể xóa phòng có booking deposits hoạt động**
   - Phải hủy hoặc refund tất cả deposits trước
   - Hoặc chờ deposits expire

4. **Trạng thái tự động**
   - `occupied`: Khi có hợp đồng thuê hoạt động
   - `reserved`: Khi có booking deposit đã phê duyệt
   - `available`: Khi không có hợp đồng thuê hoặc deposit hoạt động

5. **Một phòng chỉ có 1 hợp đồng thuê hoạt động tại một thời điểm**
   - Không thể tạo nhiều hợp đồng thuê hoạt động cho cùng 1 phòng
   - Phải terminate hoặc chờ hết hạn hợp đồng thuê hiện tại

## Trạng thái và Workflow

### Phòng Trạng thái Flow

```
available ↔ reserved ↔ occupied ↔ maintenance
```

- **available**: Phòng trống, có thể cho thuê
- **reserved**: Phòng đã được đặt cọc
- **occupied**: Phòng đang có người thuê
- **maintenance**: Phòng đang bảo trì

### Workflow Tạo Phòng

1. Quản lý tạo phòng mới (single hoặc bulk)
2. Điền thông tin: Bất động sản, Code, Loại, Rent, Trạng thái, Amenities
3. Upload images (nếu có)
4. Click Lưu
5. Phòng được tạo với trạng thái `available`
6. Có thể tạo booking deposit hoặc hợp đồng thuê cho phòng

## Ví dụ

### Ví dụ 1: Tạo Phòng Single

**Thông tin phòng:**
- Bất động sản: `Chung cư ABC`
- Code: `P101`
- Phòng Loại: `apartment`
- Max Occupancy: `2`
- Base Rent: `10,000,000 VND`
- Deposit Số tiền: `20,000,000 VND`
- Area: `50 m²`
- Trạng thái: `available`
- Amenities: Điều hòa, WiFi, Tủ lạnh

**Các bước:**
1. Truy cập Phòng
2. Click **Tạo Phòng**
3. Chọn Mode: `Single`
4. Điền thông tin trên
5. Chọn Amenities
6. Click **Lưu**
7. Phòng được tạo thành công

### Ví dụ 2: Tạo Phòng Bulk

**Thông tin chung:**
- Bất động sản: `Chung cư ABC`
- Phòng Loại: `apartment`
- Max Occupancy: `2`
- Base Rent: `10,000,000 VND`
- Deposit Số tiền: `20,000,000 VND`
- Trạng thái: `available`

**Thông tin bulk:**
- Starting Number: `101`
- Ending Number: `120`
- Code Prefix: `P`
- Code Format: `{prefix}{number}`

**Các bước:**
1. Truy cập Phòng
2. Click **Tạo Phòng**
3. Chọn Mode: `Bulk`
4. Điền thông tin chung và bulk
5. Click **Lưu**
6. Hệ thống tạo 20 phòng: P101, P102, ..., P120

## Lưu ý

1. **Code Unique**
   - Code phải unique trong bất động sản
   - Chọn code dễ nhớ và không trùng

2. **Bulk Creation**
   - Tiết kiệm thời gian khi tạo nhiều phòng giống nhau
   - Code format linh hoạt

3. **Trạng thái Management**
   - Trạng thái được cập nhật tự động khi có hợp đồng thuê hoặc deposit
   - Có thể set `maintenance` khi cần bảo trì

4. **Amenities**
   - Chọn amenities phù hợp với phòng
   - Giúp tìm kiếm và marketing

## Troubleshooting

### Không thể tạo phòng

1. Kiểm tra tất cả các trường bắt buộc đã điền chưa
2. Kiểm tra Code có bị trùng không
3. Kiểm tra Bất động sản có tồn tại không
4. Kiểm tra quyền truy cập
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Không thể xóa phòng

1. Kiểm tra phòng có hợp đồng thuê hoạt động không
2. Kiểm tra phòng có booking deposits hoạt động không
3. Terminate/cancel tất cả hợp đồng thuê và deposits trước
4. Hoặc liên hệ hỗ trợ để soft xóa

### Bulk creation lỗi

1. Kiểm tra Starting Number <= Ending Number
2. Kiểm tra Code Format có đúng không
3. Kiểm tra có phòng code trùng không
4. Thử tạo từng phần nhỏ hơn
5. Liên hệ hỗ trợ nếu vẫn lỗi

---

**Xem thêm:**
- [Quản lý Bất động sản](./03-properties.md)
- [Quản lý Hợp đồng thuê](./05-leases.md)
- [Quản lý Booking Deposits](./10-booking-deposits.md)

**Cập nhật: 2025-01-XX

