# QUY TRÌNH ĐỌC CHỈ SỐ CÔNG TƠ ĐẾN HÓA ĐƠN

## Tổng quan

Quy trình này mô tả các bước từ khi Môi giới/Manager đọc chỉ số công tơ (meter reading) đến khi tạo hóa đơn (hóa đơn) tự động cho khách thuê.

## Workflow

### Bước 1: Tạo Công tơ (Meter)

**Người thực hiện:** Quản lý hoặc Môi giới

**Các bước:**
1. Truy cập **Meters** → **Tạo**
2. Chọn **Bất động sản** (nếu có)
3. Chọn **Phòng** (phòng có công tơ)
4. Chọn **Service** (điện, nước, gas, etc.)
5. Điền thông tin:
   - **Serial Number**: Số seri công tơ
   - **Installed At**: Ngày lắp đặt
   - **Trạng thái**: `active`
6. Click **Lưu**
7. Meter được tạo với trạng thái `active`

**Lưu ý**: 
- Mỗi phòng + service chỉ có 1 meter hoạt động tại một thời điểm
- Phải có meter trước khi đọc chỉ số

**Xem chi tiết:**
- [Quản lý Meters](../manager/15-meters.md)
- [Môi giới Meters](../agent/14-meters.md)

### Bước 2: Đọc Chỉ số Công tơ (Meter Reading)

**Người thực hiện:** Môi giới hoặc Quản lý

**Các bước:**
1. Truy cập **Meter Readings** → **Tạo**
2. Chọn **Meter** (công tơ cần đọc)
3. Điền thông tin:
   - **Reading Ngày**: Ngày đọc chỉ số
   - **Value**: Chỉ số mới (phải >= chỉ số trước)
   - **Image URL**: Upload hình ảnh công tơ (nếu có)
   - **Taken By**: Người đọc chỉ số (Môi giới/Manager)
   - **Note**: Ghi chú (nếu có)
4. Click **Lưu**
5. Meter Reading được lưu
6. Hệ thống tự động:
   - Tính Usage = Current Value - Last Value
   - Kiểm tra Phòng có Hợp đồng thuê hoạt động không
   - Nếu có hợp đồng thuê hoạt động, kiểm tra Service có trong hợp đồng thuê không
   - Nếu có service trong hợp đồng thuê, tạo Hóa đơn Item

### Bước 3: Kiểm tra Hợp đồng thuê và Service

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Hệ thống kiểm tra Phòng có Hợp đồng thuê hoạt động không:
   - Nếu không có hợp đồng thuê hoạt động: 
     - Lưu reading thành công
     - Không tạo hóa đơn item
     - Kết thúc
   - Nếu có hợp đồng thuê hoạt động: Tiếp tục bước 4

**Lưu ý**: 
- Chỉ tạo hóa đơn item nếu phòng có hợp đồng thuê hoạt động
- Nếu không có hợp đồng thuê, chỉ lưu reading để theo dõi

### Bước 4: Kiểm tra Service trong Hợp đồng thuê

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Hệ thống lấy danh sách Services trong Hợp đồng thuê
2. Hệ thống kiểm tra Service (điện, nước, etc.) có trong hợp đồng thuê không:
   - Nếu không có service trong hợp đồng thuê:
     - Lưu reading thành công
     - Không tạo hóa đơn item
     - Kết thúc
   - Nếu có service trong hợp đồng thuê: Tiếp tục bước 5

**Lưu ý**: 
- Chỉ tạo hóa đơn item nếu service có trong hợp đồng thuê
- Service phải có Phòng Price trong hợp đồng thuê

### Bước 5: Tính Usage và Số tiền

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Hệ thống tính Usage:
   - **Usage** = Current Value - Last Value
   - Ví dụ: Current = 1000, Last = 950 → Usage = 50
2. Hệ thống lấy Phòng Price của Service từ Hợp đồng thuê:
   - Phòng Price = Giá mỗi đơn vị (ví dụ: 3,000 VND/kWh)
3. Hệ thống tính Số tiền:
   - **Số tiền** = Usage × Phòng Price
   - Ví dụ: Usage = 50, Phòng Price = 3,000 → Số tiền = 150,000 VND

**Lưu ý**: 
- Usage phải >= 0 (Current Value >= Last Value)
- Phòng Price phải có trong hợp đồng thuê service

### Bước 6: Tạo hoặc Cập nhật Hóa đơn Item

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Hệ thống tìm Hóa đơn chưa issued của Hợp đồng thuê:
   - Tìm hóa đơn có trạng thái `draft` và Issue Ngày trong tương lai
   - Nếu không có: Tạo hóa đơn mới với trạng thái `draft`
   - Nếu có: Sử dụng hóa đơn hiện tại

2. Hệ thống tạo Hóa đơn Item:
   - **Item Loại**: `meter`
   - **Description**: "[Service] usage - [Usage] [Phòng]"
     - Ví dụ: "Điện usage - 50 kWh"
   - **Quantity**: Usage (ví dụ: 50)
   - **Phòng Price**: Phòng Price từ hợp đồng thuê service (ví dụ: 3,000 VND/kWh)
   - **Số tiền**: Usage × Phòng Price (ví dụ: 150,000 VND)
   - **Source Loại**: `meter_reading`
   - **Source ID**: Meter Reading ID

3. Hệ thống cập nhật Hóa đơn:
   - Subtotal: Tăng lên theo Số tiền của item
   - Tổng Số tiền: Tăng lên theo Số tiền của item

**Lưu ý**: 
- Hóa đơn Item được thêm vào hóa đơn chưa issued
- Nếu không có hóa đơn chưa issued, hệ thống tạo hóa đơn mới

### Bước 7: Phát hành Hóa đơn (Tùy chọn)

**Người thực hiện:** Quản lý hoặc Môi giới

**Các bước:**
1. Sau khi tạo hóa đơn item, Quản lý hoặc Môi giới có thể issue hóa đơn:
   - Truy cập **Hóa đơn** → Tìm hóa đơn có meter reading items
   - Click **Issue** hoặc **Phát hành**
   - Hóa đơn trạng thái chuyển sang `issued`
   - Hệ thống gửi thông báo cho Khách thuê

**Lưu ý**: 
- Hóa đơn có thể được issue ngay hoặc để draft
- Hóa đơn sẽ được issue tự động khi đến Thanh toán Cycle (nếu có cấu hình)

### Bước 8: Khách thuê Thanh toán Hóa đơn

**Người thực hiện:** Khách thuê

**Các bước:**
1. Khách thuê nhận thông báo về hóa đơn có meter reading
2. Khách thuê truy cập **Hóa đơn** → Xem hóa đơn có meter reading items
3. Khách thuê thanh toán hóa đơn (xem [Khách thuê Hóa đơn](../tenant/07-invoices.md))
4. Hóa đơn trạng thái chuyển sang `paid`

## Trạng thái và Chuyển đổi

### Meter Reading Flow

```
Create Meter Reading → Calculate Usage → Check Lease → Check Service → Create Invoice Item → Issue Invoice → Tenant Payment
```

### Hóa đơn Trạng thái Flow (cho Meter Reading)

```
draft → issued → paid
```

## Ràng buộc

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

## Ví dụ

### Ví dụ hoàn chỉnh

**Meter:**
- Phòng: P101
- Service: `electricity`
- Serial Number: `ELC001`
- Trạng thái: `active`

**Last Reading:**
- Reading Ngày: 2025-01-01
- Value: 950 kWh

**Current Reading:**
- Reading Ngày: 2025-02-01
- Value: 1000 kWh

**Hợp đồng thuê:**
- Phòng: P101
- Services:
  - Electricity: Phòng Price = 3,000 VND/kWh
  - Water: Phòng Price = 15,000 VND/m³

**Các bước:**
1. Môi giới tạo Meter Reading:
   - Reading Ngày: 2025-02-01
   - Value: 1000 kWh
   - Taken By: Môi giới A

2. Hệ thống tính Usage:
   - Usage = 1000 - 950 = 50 kWh

3. Hệ thống kiểm tra Hợp đồng thuê:
   - Phòng P101 có hợp đồng thuê hoạt động ✓

4. Hệ thống kiểm tra Service:
   - Service `electricity` có trong hợp đồng thuê ✓
   - Phòng Price = 3,000 VND/kWh ✓

5. Hệ thống tính Số tiền:
   - Số tiền = 50 × 3,000 = 150,000 VND

6. Hệ thống tìm Hóa đơn chưa issued:
   - Tìm thấy Hóa đơn HD-202502-0001 (trạng thái = `draft`)

7. Hệ thống tạo Hóa đơn Item:
   - Item Loại: `meter`
   - Description: "Điện usage - 50 kWh"
   - Quantity: 50
   - Phòng Price: 3,000 VND/kWh
   - Số tiền: 150,000 VND

8. Hóa đơn Tổng Số tiền tăng từ 10,000,000 VND lên 10,150,000 VND

9. Quản lý issue hóa đơn → trạng thái = `issued`

10. Khách thuê thanh toán hóa đơn → trạng thái = `paid`

### Ví dụ 2: Tạo Hóa đơn mới

**Kịch bản:** Không có hóa đơn chưa issued

**Các bước:**
1. Môi giới tạo Meter Reading:
   - Usage = 50 kWh
   - Số tiền = 150,000 VND

2. Hệ thống tìm Hóa đơn chưa issued:
   - Không tìm thấy hóa đơn chưa issued

3. Hệ thống tạo Hóa đơn mới:
   - Hóa đơn Number: HD-202502-0002 (tự động)
   - Trạng thái: `draft`
   - Issue Ngày: 2025-02-01
   - Đến hạn Ngày: 2025-02-05 (từ Thanh toán Day của hợp đồng thuê)

4. Hệ thống tạo Hóa đơn Item:
   - Item Loại: `meter`
   - Description: "Điện usage - 50 kWh"
   - Số tiền: 150,000 VND

5. Hóa đơn Tổng Số tiền = 150,000 VND

6. Quản lý có thể issue hóa đơn hoặc để draft

## Lưu ý

1. **Meter Reading Validation**
   - Value phải >= Last Value
   - Reading Ngày phải >= Last Reading Ngày
   - Kiểm tra kỹ trước khi lưu

2. **Auto-tạo Hóa đơn Item**
   - Hóa đơn item được tạo tự động khi có meter reading
   - Chỉ tạo nếu phòng có hợp đồng thuê hoạt động và service có trong hợp đồng thuê

3. **Hóa đơn Creation**
   - Hóa đơn được tạo tự động nếu không có hóa đơn chưa issued
   - Hóa đơn có thể được issue ngay hoặc để draft

4. **Multiple Readings**
   - Có thể đọc chỉ số nhiều lần trong một tháng
   - Mỗi reading tạo một hóa đơn item riêng (nếu trong cùng hóa đơn)

5. **Service Price**
   - Service phải có Phòng Price trong hợp đồng thuê
   - Nếu không có Phòng Price, không tạo hóa đơn item

---

**Xem thêm:**
- [Quản lý Meters](../manager/15-meters.md)
- [Quản lý Meter Readings](../manager/16-meter-readings.md)
- [Quản lý Hóa đơn](../manager/12-invoices.md)
- [Khách thuê Hóa đơn](../tenant/07-invoices.md)

**Cập nhật**: 2025-11-02

