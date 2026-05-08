# QUẢN LÝ BẤT ĐỘNG SẢN - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý bất động sản (bất động sản) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, và quản lý thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả bất động sản trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `asset.access`
  - Tạo bất động sản: Cần capability `asset.property.create`
  - Cập nhật bất động sản: Cần capability `asset.property.update`
  - Xem tất cả bất động sản: Cần capability `asset.property.view` hoặc `asset.property.view_all`
  - Chỉ xem bất động sản được gán: Có capability `asset.property.view_own` (mặc định, chỉ xem bất động sản được assign)
  - Xóa bất động sản: Cần capability `asset.property.delete`

**Route**: `/staff/properties`

## Các bước thực hiện

### 1. Xem danh sách Bất động sản

1. Truy cập **Bất động sản** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả bất động sản trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (hoạt động, không hoạt động)
   - Loại (apartment, house, condo, etc.)
   - City, District
   - Tìm kiếm by name, address
   - Sắp xếp theo name, created_at, updated_at

### 2. Xem chi tiết Bất động sản

1. Click vào bất động sản trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Bất động sản ID, Name, Code
     - Loại, Trạng thái
     - Address (Street, Ward, District, City)
     - Coordinates (Latitude, Longitude)
     - Description
   - **Thông tin liên quan:**
     - Phòng: Danh sách phòng trong bất động sản
     - Hợp đồng thuê: Danh sách hợp đồng thuê của bất động sản
     - Images: Hình ảnh bất động sản
   - **Thống kê:**
     - Tổng Phòng
     - Occupied Phòng
     - Vacant Phòng
     - Occupancy Rate

### 3. Tạo Bất động sản mới

1. Click **Tạo Bất động sản** hoặc **+ New**
2. Điền thông tin:
   - **Name** (bắt buộc): Tên bất động sản
   - **Code** (bắt buộc, unique): Mã bất động sản
   - **Loại** (bắt buộc): Loại bất động sản (apartment, house, condo, etc.)
   - **Trạng thái** (bắt buộc): `active` hoặc `inactive`
   - **Address**:
     - Street: Đường/phố
     - Ward: Phường/Xã
     - District: Quận/Huyện
     - City: Thành phố
   - **Coordinates** (nếu có):
     - Latitude: Vĩ độ
     - Longitude: Kinh độ
   - **Description**: Mô tả bất động sản
   - **Images** (tùy chọn): Upload hình ảnh bất động sản
3. Click **Lưu**
4. Bất động sản được tạo và hiển thị trong danh sách

### 4. Cập nhật Bất động sản

1. Truy cập chi tiết bất động sản cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin cần thay đổi
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

### 5. Xóa Bất động sản

1. Truy cập chi tiết bất động sản cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa bất động sản

**Lưu ý**: 
- Chỉ có thể xóa bất động sản không có phòng hoặc hợp đồng thuê hoạt động
- Xóa bất động sản sẽ xóa tất cả phòng và related data (nếu cho phép)

### 6. Upload Images

1. Truy cập chi tiết bất động sản
2. Scroll đến phần **Images**
3. Click **Upload Images**
4. Chọn files ảnh (hỗ trợ: jpg, png, gif)
5. Click **Upload**
6. Hệ thống upload và hiển thị hình ảnh

**Lưu ý**: 
- Max size: 5MB mỗi file (tùy cấu hình)
- Có thể upload nhiều ảnh cùng lúc
- Có thể xóa ảnh đã upload

### 7. Xem Thống kê Bất động sản

1. Truy cập chi tiết bất động sản
2. Scroll đến phần **Thống kê**
3. Hệ thống hiển thị:
   - **Tổng Phòng**: Tổng số phòng
   - **Occupied Phòng**: Số phòng đã cho thuê
   - **Vacant Phòng**: Số phòng trống
   - **Occupancy Rate**: Tỷ lệ lấp đầy (%)
   - **Tổng Revenue**: Tổng doanh thu từ bất động sản
   - **Hoạt động Hợp đồng thuê**: Số hợp đồng thuê hoạt động

## Ràng buộc và điều kiện

### Validation Rules

- **Name**: 
  - Bắt buộc
  - Không được để trống
  - Max 255 ký tự
- **Code**: 
  - Bắt buộc
  - Phải unique trong tổ chức
  - Format: Alphanumeric, có thể có dấu gạch dưới
- **Loại**: 
  - Bắt buộc
  - Phải là một trong: apartment, house, condo, etc.
- **Trạng thái**: 
  - Bắt buộc
  - Phải là `active` hoặc `inactive`
- **Address**: 
  - Street, Ward, District, City (tùy chọn)
  - Phải đúng format (nếu có)
- **Coordinates**: 
  - Latitude: -90 đến 90
  - Longitude: -180 đến 180
- **Images**: 
  - Tùy chọn
  - Phải là file ảnh hợp lệ
  - Max size: 5MB mỗi file

### Business Rules

1. **Code phải unique trong tổ chức**
   - Không thể có 2 bất động sản cùng code trong 1 tổ chức
   - Code có thể trùng giữa các organizations

2. **Không thể xóa bất động sản có phòng hoặc hợp đồng thuê hoạt động**
   - Phải xóa/terminate tất cả phòng và hợp đồng thuê trước
   - Hoặc soft xóa để giữ lại dữ liệu

3. **Bất động sản Trạng thái**
   - `active`: Bất động sản đang hoạt động
   - `inactive`: Bất động sản không hoạt động (tạm dừng)

## Trạng thái và Workflow

### Bất động sản Trạng thái Flow

```
inactive ↔ active
```

- **hoạt động**: Bất động sản đang hoạt động, có thể tạo phòng và hợp đồng thuê
- **không hoạt động**: Bất động sản tạm dừng, không thể tạo phòng mới

### Workflow Tạo Bất động sản

1. Quản lý tạo bất động sản mới
2. Điền thông tin: Name, Code, Loại, Trạng thái, Address
3. Upload images (nếu có)
4. Click Lưu
5. Bất động sản được tạo với trạng thái `active` hoặc `inactive`
6. Có thể tạo phòng cho bất động sản sau

## Ví dụ

### Ví dụ 1: Tạo Bất động sản mới

**Thông tin bất động sản:**
- Name: `Chung cư ABC`
- Code: `PROP001`
- Loại: `apartment`
- Trạng thái: `active`
- Address:
  - Street: `123 Đường ABC`
  - Ward: `Phường 1`
  - District: `Quận 1`
  - City: `Hồ Chí Minh`
- Description: `Chung cư cao cấp tại trung tâm thành phố`
- Images: Upload 3 ảnh

**Các bước:**
1. Truy cập Bất động sản
2. Click **Tạo Bất động sản**
3. Điền thông tin trên
4. Upload 3 ảnh
5. Click **Lưu**
6. Bất động sản được tạo thành công

### Ví dụ 2: Cập nhật Bất động sản

**Kịch bản:** Cập nhật địa chỉ bất động sản

**Các bước:**
1. Truy cập chi tiết bất động sản "Chung cư ABC"
2. Click **Chỉnh sửa**
3. Cập nhật Address:
   - Street: `456 Đường XYZ` (thay đổi)
   - Ward, District, City: Giữ nguyên
4. Click **Lưu**
5. Hệ thống cập nhật thành công

## Lưu ý

1. **Code Unique**
   - Code phải unique trong tổ chức
   - Chọn code dễ nhớ và không trùng

2. **Trạng thái Management**
   - Chỉ tạo phòng khi bất động sản trạng thái = `active`
   - Có thể tạm dừng bất động sản bằng cách chuyển trạng thái = `inactive`

3. **Images**
   - Upload ảnh chất lượng tốt để hiển thị
   - Có thể upload nhiều ảnh
   - Có thể xóa ảnh không cần thiết

4. **Coordinates**
   - Thêm coordinates để hiển thị trên bản đồ
   - Dùng cho tính năng map integration

## Troubleshooting

### Không thể tạo bất động sản

1. Kiểm tra tất cả các trường bắt buộc đã điền chưa
2. Kiểm tra Code có bị trùng không
3. Kiểm tra Loại có đúng không
4. Kiểm tra quyền truy cập
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Không thể xóa bất động sản

1. Kiểm tra bất động sản có phòng không
2. Kiểm tra bất động sản có hợp đồng thuê hoạt động không
3. Xóa/terminate tất cả phòng và hợp đồng thuê trước
4. Hoặc liên hệ hỗ trợ để soft xóa

### Images không upload được

1. Kiểm tra file có đúng format không (jpg, png, gif)
2. Kiểm tra file size có vượt quá 5MB không
3. Kiểm tra kết nối mạng
4. Thử upload lại
5. Liên hệ hỗ trợ nếu vẫn lỗi

---

**Xem thêm:**
- [Quản lý Phòng](./04-units.md)
- [Quản lý Hợp đồng thuê](./05-leases.md)

**Cập nhật: 2025-01-XX

