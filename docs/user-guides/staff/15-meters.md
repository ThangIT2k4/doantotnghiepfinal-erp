# QUẢN LÝ CÔNG TƠ - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý công tơ (meters) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, activate, deactivate, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả meters trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `asset.access`
  - Tạo meter: Cần capability `asset.meter.create`
  - Cập nhật meter: Cần capability `asset.meter.update`
  - Xem tất cả meters: Cần capability `asset.meter.view` hoặc `asset.meter.view_all`
  - Chỉ xem meters từ phòng được gán: Có capability `asset.meter.view_own` (mặc định)
  - Xóa meter: Cần capability `asset.meter.delete`

**Route**: `/staff/meters`

## Các bước thực hiện

### 1. Xem danh sách Meters

1. Truy cập **Meters** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả meters trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (hoạt động, không hoạt động)
   - Service (electricity, water, gas, etc.)
   - Bất động sản (nếu có nhiều bất động sản)
   - Phòng (nếu có nhiều phòng)
   - Sắp xếp theo serial_number, installed_at, trạng thái

### 2. Xem chi tiết Meter

1. Click vào meter trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Meter ID: Mã công tơ
     - Serial Number: Số seri công tơ
     - Bất động sản: Bất động sản (nếu có)
     - Phòng: Phòng/Căn
     - Service: Loại dịch vụ (điện, nước, gas, etc.)
     - Trạng thái: Trạng thái hiện tại (hoạt động, không hoạt động)
   - **Thông tin khác:**
     - Installed At: Ngày lắp đặt
     - Last Reading: Chỉ số cuối cùng (nếu có)
     - Last Reading Ngày: Ngày đọc cuối (nếu có)
     - Created At: Ngày tạo meter
     - Updated At: Ngày cập nhật meter
   - **Thống kê:**
     - Tổng Readings: Tổng số lần đọc chỉ số
     - Last Reading Value: Chỉ số cuối
     - Usage This Month: Sử dụng tháng này
     - Tổng Usage: Tổng sử dụng

### 3. Tạo Meter mới

1. Click **Tạo Meter** hoặc **+ New**
2. Điền thông tin:
   - **Bất động sản** (tùy chọn): Chọn bất động sản (nếu có)
   - **Phòng** (bắt buộc): Chọn phòng/căn
   - **Service** (bắt buộc): Chọn loại dịch vụ (điện, nước, gas, etc.)
   - **Serial Number** (bắt buộc, unique): Số seri công tơ
   - **Installed At** (bắt buộc): Ngày lắp đặt
   - **Trạng thái** (bắt buộc): `active` hoặc `inactive`
   - **Note** (tùy chọn): Ghi chú
3. Click **Lưu**
4. Meter được tạo với trạng thái `active` hoặc `inactive`

**Lưu ý**: 
- Mỗi phòng + service chỉ có 1 meter hoạt động tại một thời điểm
- Phải có meter trước khi đọc chỉ số

### 4. Cập nhật Meter

1. Truy cập chi tiết meter cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin:
   - Serial Number (nếu cần thay đổi)
   - Installed At
   - Trạng thái
   - Note
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Không thể thay đổi Bất động sản hoặc Phòng sau khi đã tạo
- Không thể thay đổi Service sau khi đã tạo

### 5. Activate/Deactivate Meter

1. Truy cập chi tiết meter cần activate/deactivate
2. Click **Activate** hoặc **Deactivate**
3. Xác nhận
4. Meter trạng thái chuyển sang `active` hoặc `inactive`
5. Hệ thống gửi thông báo cho Quản lý

**Lưu ý**: 
- Activate meter để có thể đọc chỉ số
- Deactivate meter khi không sử dụng nữa

### 6. Xóa Meter

1. Truy cập chi tiết meter cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa meter

**Lưu ý**: 
- Chỉ có thể xóa meter không có readings hoặc không còn sử dụng
- Xóa meter sẽ không xóa readings đã có

### 7. Xem Readings

1. Truy cập chi tiết meter
2. Scroll đến phần **Readings**
3. Hệ thống hiển thị danh sách readings của meter với:
   - Reading Ngày: Ngày đọc chỉ số
   - Value: Chỉ số
   - Usage: Sử dụng (Current - Last)
   - Taken By: Người đọc chỉ số
   - Hóa đơn Item: Hóa đơn item liên quan (nếu có)
4. Click vào reading để xem chi tiết

### 8. Xem Thống kê

1. Truy cập **Meters** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Meters by Trạng thái: Phân bố theo trạng thái
   - Meters by Service: Phân bố theo dịch vụ
   - Tổng Meters: Tổng số công tơ
   - Hoạt động Meters: Số công tơ hoạt động
   - Meters by Bất động sản: Phân bố theo bất động sản

## Ràng buộc và điều kiện

### Validation Rules

- **Phòng**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
- **Service**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
- **Serial Number**: 
  - Bắt buộc
  - Phải unique trong tổ chức (hoặc phòng + service)
- **Installed At**: 
  - Bắt buộc
  - Phải là ngày hợp lệ
- **Trạng thái**: 
  - Bắt buộc
  - Phải là `active` hoặc `inactive`

### Business Rules

1. **Mỗi phòng + service chỉ có 1 meter hoạt động tại một thời điểm**
   - Không thể tạo nhiều meters hoạt động cho cùng 1 phòng + service
   - Phải deactivate meter cũ trước khi activate meter mới

2. **Phải có meter trước khi đọc chỉ số**
   - Meter phải có trạng thái `active`
   - Meter phải có Phòng và Service

3. **Meter Trạng thái**
   - `active`: Meter đang hoạt động, có thể đọc chỉ số
   - `inactive`: Meter không hoạt động, không thể đọc chỉ số

4. **Last Reading**
   - Last Reading được cập nhật tự động khi có reading mới
   - Last Reading Ngày được cập nhật tự động

## Trạng thái và Workflow

### Meter Trạng thái Flow

```
inactive ↔ active
```

- **hoạt động**: Meter đang hoạt động, có thể đọc chỉ số
- **không hoạt động**: Meter không hoạt động, không thể đọc chỉ số

### Workflow Tạo Meter

1. Quản lý tạo meter mới
2. Điền thông tin: Phòng, Service, Serial Number, Installed At, Trạng thái
3. Click Lưu
4. Meter được tạo với trạng thái `active` hoặc `inactive`
5. Có thể đọc chỉ số sau khi meter hoạt động

## Ví dụ

### Ví dụ 1: Tạo Meter mới

**Thông tin meter:**
- Bất động sản: Bất động sản ABC
- Phòng: Phòng 101
- Service: `electricity`
- Serial Number: `ELC001`
- Installed At: 2025-01-01
- Trạng thái: `active`

**Các bước:**
1. Truy cập Meters
2. Click **Tạo Meter**
3. Chọn Bất động sản: Bất động sản ABC
4. Chọn Phòng: Phòng 101
5. Chọn Service: `electricity`
6. Nhập Serial Number: `ELC001`
7. Chọn Installed At: 2025-01-01
8. Chọn Trạng thái: `active`
9. Click **Lưu**
10. Meter được tạo với trạng thái `active`

### Ví dụ 2: Deactivate Meter

**Kịch bản:** Meter cũ bị hỏng, cần deactivate và tạo meter mới

**Các bước:**
1. Truy cập chi tiết meter cũ
2. Click **Deactivate**
3. Meter trạng thái chuyển sang `inactive`
4. Tạo meter mới với Serial Number mới
5. Activate meter mới → Trạng thái `active`

## Lưu ý

1. **Meter Unique**
   - Mỗi phòng + service chỉ có 1 meter hoạt động
   - Deactivate meter cũ trước khi activate meter mới

2. **Meter Trạng thái**
   - Chỉ có thể đọc chỉ số khi meter trạng thái = `active`
   - Deactivate meter khi không sử dụng nữa

3. **Readings**
   - Meter phải có readings để tính usage
   - Readings được liên kết với meter

## Troubleshooting

### Không thể tạo meter

1. Kiểm tra Phòng có tồn tại không
2. Kiểm tra Service có tồn tại không
3. Kiểm tra Serial Number có unique không
4. Kiểm tra Phòng + Service đã có meter hoạt động chưa
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Không thể đọc chỉ số

1. Kiểm tra meter có trạng thái `active` không
2. Kiểm tra meter có Phòng và Service không
3. Kiểm tra meter có Serial Number không
4. Liên hệ hỗ trợ nếu vẫn không thể đọc chỉ số

---

**Xem thêm:**
- [Quản lý Phòng](./04-units.md)
- [Quản lý Meter Readings](./16-meter-readings.md)
- [Workflow Meter Reading to Hóa đơn](../workflows/07-meter-reading-to-invoice.md)

**Cập nhật: 2025-01-XX

