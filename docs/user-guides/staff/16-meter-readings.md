# QUẢN LÝ CHỈ SỐ CÔNG TƠ - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý chỉ số công tơ (meter readings) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, auto-hóa đơn, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả meter readings trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `asset.access`
  - Tạo meter reading: Cần capability `asset.meter_reading.create`
  - Cập nhật meter reading: Cần capability `asset.meter_reading.update`
  - Xem tất cả meter readings: Cần capability `asset.meter_reading.view` hoặc `asset.meter_reading.view_all`
  - Chỉ xem meter readings từ meters được gán: Có capability `asset.meter_reading.view_own` (mặc định)
  - Xóa meter reading: Cần capability `asset.meter_reading.delete`

**Route**: `/staff/meter-readings`

## Các bước thực hiện

### 1. Xem danh sách Meter Readings

1. Truy cập **Meter Readings** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả meter readings trong tổ chức
3. Có thể lọc theo:
   - Meter (nếu có nhiều meters)
   - Bất động sản, Phòng (nếu có nhiều bất động sản/units)
   - Service (electricity, water, gas, etc.)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo reading_date, value, created_at

### 2. Xem chi tiết Meter Reading

1. Click vào reading trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Reading ID: Mã reading
     - Meter: Công tơ
     - Serial Number: Số seri công tơ
     - Phòng: Phòng/Căn
     - Service: Loại dịch vụ
     - Reading Ngày: Ngày đọc chỉ số
     - Value: Chỉ số mới
     - Last Value: Chỉ số trước (nếu có)
     - Usage: Sử dụng (Current - Last)
   - **Thông tin khác:**
     - Image URL: Hình ảnh công tơ (nếu có)
     - Taken By: Người đọc chỉ số (Môi giới/Manager)
     - Note: Ghi chú
     - Created At: Ngày tạo reading
   - **Hóa đơn Item:**
     - Hóa đơn Item được tạo tự động (nếu có)
     - Hóa đơn Number: Số hóa đơn
     - Số tiền: Số tiền (Usage × Phòng Price)

### 3. Tạo Meter Reading mới

1. Click **Tạo Meter Reading** hoặc **+ New**
2. Điền thông tin:
   - **Meter** (bắt buộc): Chọn công tơ (phải có trạng thái `active`)
   - **Reading Ngày** (bắt buộc): Ngày đọc chỉ số (phải >= Last Reading Ngày)
   - **Value** (bắt buộc): Chỉ số mới (phải >= Last Value)
   - **Image URL** (tùy chọn): Upload hình ảnh công tơ
   - **Taken By** (tự động): Quản lý hiện tại
   - **Note** (tùy chọn): Ghi chú
3. Click **Lưu**
4. Hệ thống tự động:
   - Tính Usage = Current Value - Last Value
   - Kiểm tra Phòng có Hợp đồng thuê hoạt động không
   - Nếu có hợp đồng thuê hoạt động, kiểm tra Service có trong hợp đồng thuê không
   - Nếu có service trong hợp đồng thuê, tạo Hóa đơn Item
5. Meter Reading được lưu và hiển thị trong danh sách

**Lưu ý**: 
- Reading Ngày phải >= Last Reading Ngày
- Value phải >= Last Value
- Auto-tạo Hóa đơn Item nếu có hợp đồng thuê hoạt động và service trong hợp đồng thuê

### 4. Auto-tạo Hóa đơn Item

Khi tạo meter reading, hệ thống tự động tạo Hóa đơn Item nếu:

1. Phòng có Hợp đồng thuê hoạt động
2. Service có trong Hợp đồng thuê
3. Service có Phòng Price trong Hợp đồng thuê

**Các bước tự động:**

1. Hệ thống kiểm tra Phòng có Hợp đồng thuê hoạt động không:
   - Nếu không có hợp đồng thuê hoạt động: Lưu reading, không tạo hóa đơn item
   - Nếu có hợp đồng thuê hoạt động: Tiếp tục bước 2

2. Hệ thống kiểm tra Service có trong Hợp đồng thuê không:
   - Nếu không có service trong hợp đồng thuê: Lưu reading, không tạo hóa đơn item
   - Nếu có service trong hợp đồng thuê: Tiếp tục bước 3

3. Hệ thống tính Số tiền:
   - Usage = Current Value - Last Value
   - Số tiền = Usage × Phòng Price (từ hợp đồng thuê service)

4. Hệ thống tìm Hóa đơn chưa issued của Hợp đồng thuê:
   - Tìm hóa đơn có trạng thái `draft` và Issue Ngày trong tương lai
   - Nếu không có: Tạo hóa đơn mới với trạng thái `draft`

5. Hệ thống tạo Hóa đơn Item:
   - Item Loại: `meter`
   - Description: "[Service] usage - [Usage] [Phòng]"
   - Quantity: Usage
   - Phòng Price: Phòng Price từ hợp đồng thuê service
   - Số tiền: Usage × Phòng Price
   - Source Loại: `meter_reading`
   - Source ID: Meter Reading ID

6. Hệ thống cập nhật Hóa đơn:
   - Subtotal: Tăng lên theo Số tiền của item
   - Tổng Số tiền: Tăng lên theo Số tiền của item

**Lưu ý**: 
- Auto-tạo Hóa đơn Item chỉ xảy ra khi có hợp đồng thuê hoạt động và service trong hợp đồng thuê
- Hóa đơn Item được thêm vào hóa đơn chưa issued
- Nếu không có hóa đơn chưa issued, hệ thống tạo hóa đơn mới

### 5. Cập nhật Meter Reading

1. Truy cập chi tiết reading cần cập nhật
2. Click **Chỉnh sửa** (chỉ khi reading chưa có Hóa đơn Item)
3. Cập nhật thông tin:
   - Reading Ngày (nếu chưa có Hóa đơn Item)
   - Value (nếu chưa có Hóa đơn Item)
   - Image URL
   - Note
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Chỉ có thể cập nhật reading chưa có Hóa đơn Item
- Không thể cập nhật reading đã có Hóa đơn Item (để đảm bảo tính chính xác)

### 6. Xóa Meter Reading

1. Truy cập chi tiết reading cần xóa
2. Click **Xóa** (chỉ khi reading chưa có Hóa đơn Item)
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa reading

**Lưu ý**: 
- Chỉ có thể xóa reading chưa có Hóa đơn Item
- Không thể xóa reading đã có Hóa đơn Item (để đảm bảo tính chính xác)
- Nếu reading đã có Hóa đơn Item, phải xóa Hóa đơn Item trước (nếu hóa đơn chưa issued)

### 7. Xem Hóa đơn Item

1. Truy cập chi tiết reading có Hóa đơn Item
2. Scroll đến phần **Hóa đơn Item**
3. Hệ thống hiển thị:
   - Hóa đơn Number: Số hóa đơn
   - Hóa đơn Trạng thái: Trạng thái hóa đơn
   - Item Description: Mô tả item
   - Quantity: Số lượng (Usage)
   - Phòng Price: Giá đơn vị
   - Số tiền: Thành tiền
4. Click vào Hóa đơn để xem chi tiết hóa đơn

### 8. Xem Thống kê

1. Truy cập **Meter Readings** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Readings by Service: Phân bố theo dịch vụ
   - Readings by Period: Phân bố theo thời gian
   - Tổng Usage: Tổng sử dụng
   - Average Usage: Sử dụng trung bình
   - Usage by Phòng: Sử dụng theo phòng
   - Usage by Bất động sản: Sử dụng theo bất động sản

## Ràng buộc và điều kiện

### Validation Rules

- **Meter**: 
  - Bắt buộc
  - Phải tồn tại và có trạng thái `active`
- **Reading Ngày**: 
  - Bắt buộc
  - Phải là datetime hợp lệ
  - Phải >= Last Reading Ngày (nếu có last reading)
- **Value**: 
  - Bắt buộc
  - Phải là số >= 0
  - Phải >= Last Value (nếu có last reading)
- **Image URL**: 
  - Tùy chọn
  - Phải là URL hợp lệ (nếu có)

### Business Rules

1. **Meter Reading Value phải >= Last Value**
   - Current Value phải >= Last Value
   - Không thể nhập chỉ số giảm (trừ khi công tơ bị hỏng và thay mới)

2. **Reading Ngày phải >= Last Reading Ngày**
   - Reading Ngày mới phải >= Last Reading Ngày
   - Không thể đọc chỉ số trong quá khứ (trừ khi điều chỉnh)

3. **Phòng phải có Hợp đồng thuê hoạt động**
   - Chỉ tạo hóa đơn item nếu phòng có hợp đồng thuê hoạt động
   - Nếu không có hợp đồng thuê, chỉ lưu reading để theo dõi

4. **Service phải có trong Hợp đồng thuê**
   - Chỉ tạo hóa đơn item nếu service có trong hợp đồng thuê
   - Service phải có Phòng Price trong hợp đồng thuê

5. **Usage phải > 0**
   - Usage = Current Value - Last Value
   - Nếu Usage <= 0, không tạo hóa đơn item (có thể do lỗi nhập)

6. **Không thể cập nhật/xóa reading đã có Hóa đơn Item**
   - Reading đã có Hóa đơn Item không thể cập nhật/xóa
   - Đảm bảo tính chính xác của hóa đơn

## Trạng thái và Workflow

### Meter Reading Flow

```
Create Meter Reading → Calculate Usage → Check Lease → Check Service → Create Invoice Item → Issue Invoice → Tenant Payment
```

### Workflow Tạo Meter Reading

1. Quản lý hoặc Môi giới tạo meter reading mới
2. Điền thông tin: Meter, Reading Ngày, Value
3. Upload hình ảnh (nếu có)
4. Click Lưu
5. Hệ thống tự động:
   - Tính Usage = Current Value - Last Value
   - Kiểm tra Phòng có Hợp đồng thuê hoạt động không
   - Kiểm tra Service có trong Hợp đồng thuê không
   - Tính Số tiền = Usage × Phòng Price
   - Tạo Hóa đơn Item trong hóa đơn chưa issued
6. Meter Reading được lưu và Hóa đơn Item được tạo (nếu có)

## Ví dụ

### Ví dụ 1: Tạo Meter Reading và Auto-tạo Hóa đơn Item

**Meter:**
- Serial Number: `ELC001`
- Phòng: Phòng 101
- Service: `electricity`
- Last Reading: Value = 950, Ngày = 2025-01-01

**Current Reading:**
- Reading Ngày: 2025-02-01
- Value: 1000

**Hợp đồng thuê:**
- Phòng: Phòng 101
- Services:
  - Electricity: Phòng Price = 3,000 VND/kWh

**Các bước:**
1. Truy cập Meter Readings
2. Click **Tạo Meter Reading**
3. Chọn Meter: ELC001
4. Nhập Reading Ngày: 2025-02-01
5. Nhập Value: 1000
6. Upload hình ảnh công tơ
7. Click **Lưu**
8. Hệ thống tính Usage: 1000 - 950 = 50 kWh
9. Hệ thống kiểm tra Hợp đồng thuê: Phòng 101 có hợp đồng thuê hoạt động ✓
10. Hệ thống kiểm tra Service: Electricity có trong hợp đồng thuê ✓
11. Hệ thống tính Số tiền: 50 × 3,000 = 150,000 VND
12. Hệ thống tìm Hóa đơn chưa issued: Tìm thấy Hóa đơn HD-202502-0001
13. Hệ thống tạo Hóa đơn Item:
    - Item Loại: `meter`
    - Description: "Điện usage - 50 kWh"
    - Quantity: 50
    - Phòng Price: 3,000 VND/kWh
    - Số tiền: 150,000 VND
14. Hóa đơn Tổng Số tiền tăng từ 10,000,000 VND lên 10,150,000 VND

### Ví dụ 2: Tạo Meter Reading không có Hợp đồng thuê

**Kịch bản:** Phòng không có hợp đồng thuê hoạt động

**Meter:**
- Phòng: Phòng 101 (không có hợp đồng thuê hoạt động)
- Service: `electricity`

**Các bước:**
1. Truy cập Meter Readings
2. Click **Tạo Meter Reading**
3. Chọn Meter và điền thông tin
4. Click **Lưu**
5. Hệ thống tính Usage: 50 kWh
6. Hệ thống kiểm tra Hợp đồng thuê: Phòng 101 không có hợp đồng thuê hoạt động ✗
7. Hệ thống lưu reading nhưng không tạo hóa đơn item
8. Reading được lưu để theo dõi

## Lưu ý

1. **Value >= Last Value**
   - Chỉ số mới phải >= chỉ số cũ
   - Kiểm tra kỹ trước khi lưu

2. **Reading Ngày >= Last Reading Ngày**
   - Ngày đọc mới phải >= ngày đọc cũ
   - Kiểm tra kỹ trước khi lưu

3. **Auto-tạo Hóa đơn Item**
   - Hóa đơn item được tạo tự động khi có hợp đồng thuê hoạt động và service trong hợp đồng thuê
   - Hóa đơn item được thêm vào hóa đơn chưa issued

4. **Không thể cập nhật/xóa reading đã có Hóa đơn Item**
   - Đảm bảo tính chính xác của hóa đơn
   - Phải xóa Hóa đơn Item trước nếu cần thay đổi (nếu hóa đơn chưa issued)

5. **Multiple Readings**
   - Có thể đọc chỉ số nhiều lần trong một tháng
   - Mỗi reading tạo một hóa đơn item riêng (nếu trong cùng hóa đơn)

## Troubleshooting

### Không thể tạo meter reading

1. Kiểm tra Meter có trạng thái `active` không
2. Kiểm tra Value có >= Last Value không
3. Kiểm tra Reading Ngày có >= Last Reading Ngày không
4. Kiểm tra Meter có Phòng và Service không
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Hóa đơn Item không được tạo tự động

1. Kiểm tra Phòng có Hợp đồng thuê hoạt động không
2. Kiểm tra Service có trong Hợp đồng thuê không
3. Kiểm tra Service có Phòng Price trong Hợp đồng thuê không
4. Kiểm tra Usage có > 0 không
5. Kiểm tra có Hóa đơn chưa issued không
6. Liên hệ hỗ trợ nếu vẫn không tạo hóa đơn item

### Không thể cập nhật reading

1. Kiểm tra reading có Hóa đơn Item chưa
2. Chỉ có thể cập nhật reading chưa có Hóa đơn Item
3. Nếu reading đã có Hóa đơn Item, phải xóa Hóa đơn Item trước (nếu hóa đơn chưa issued)
4. Liên hệ hỗ trợ nếu vẫn không thể cập nhật

---

**Xem thêm:**
- [Quản lý Meters](./15-meters.md)
- [Quản lý Hóa đơn](./12-invoices.md)
- [Workflow Meter Reading to Hóa đơn](../workflows/07-meter-reading-to-invoice.md)

**Cập nhật: 2025-01-XX

