# QUẢN LÝ LỊCH XEM PHÒNG - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý lịch xem phòng (viewings/appointments) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, confirm, hủy, mark done, calendar xem, today xem, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả viewings trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `crm.access`
  - Tạo viewing: Cần capability `crm.appointment.create`
  - Cập nhật viewing: Cần capability `crm.appointment.update`
  - Xem tất cả viewings: Cần capability `crm.appointment.view` hoặc `crm.appointment.view_all`
  - Chỉ xem viewings của mình: Có capability `crm.appointment.view_own` (mặc định)
  - Confirm/Cancel/Mark Done: Cần capability `crm.appointment.update`
  - Xóa viewing: Cần capability `crm.appointment.delete`

**Route**: `/staff/viewings`

## Các bước thực hiện

### 1. Xem danh sách Viewings

1. Truy cập **Viewings** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả viewings trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (requested, confirmed, done, no_show, cancelled)
   - Môi giới (nếu có nhiều agents)
   - Bất động sản, Phòng (nếu có nhiều bất động sản)
   - Lead, Khách thuê (nếu có nhiều leads/tenants)
   - Ngày (today, this week, this month, upcoming, tùy chỉnh range)
   - Sắp xếp theo schedule_at, created_at, trạng thái

### 2. Calendar Xem (Xem theo Lịch)

1. Truy cập **Viewings** → **Calendar**
2. Hệ thống hiển thị lịch xem phòng theo tháng
3. Có thể chuyển tháng:
   - Click **Trước đó Month** hoặc **Tiếp theo Month**
   - Hoặc chọn tháng từ dropdown
4. Viewings được hiển thị trên calendar:
   - Màu sắc khác nhau theo trạng thái
   - Click vào viewing để xem chi tiết
5. Có thể lọc theo Môi giới hoặc Bất động sản

### 3. Today Xem (Xem Hôm nay)

1. Truy cập **Viewings** → **Today**
2. Hệ thống hiển thị tất cả viewings hôm nay
3. Có thể lọc theo:
   - Trạng thái
   - Môi giới
   - Bất động sản
   - Thời gian range

### 4. Xem chi tiết Viewing

1. Click vào viewing trong danh sách, calendar, hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Viewing ID: Mã lịch xem
     - Bất động sản, Phòng: Bất động sản và phòng (nếu có)
     - Lead hoặc Khách thuê: Khách hàng
     - Môi giới: Người xử lý
     - Schedule At: Thời gian hẹn xem phòng
     - Trạng thái: Trạng thái hiện tại
     - Note: Ghi chú
   - **Thông tin khác:**
     - Result Note: Kết quả sau khi xem (nếu đã done)
     - Feedback Rating: Đánh giá (1-5, nếu đã done)
     - Feedback Notes: Ghi chú feedback (nếu có)
     - Photos: Hình ảnh (nếu có)
     - Checklist: Checklist xem phòng (JSON, nếu có)
     - Virtual Viewing Link: Link xem ảo (nếu có)
   - **Thống kê:**
     - Tổng Viewings: Tổng số lịch xem
     - Completed Viewings: Số lịch xem đã hoàn tất
     - Conversion Rate: Tỷ lệ chuyển đổi Viewing → Hợp đồng thuê

### 5. Tạo Viewing mới

1. Click **Tạo Viewing** hoặc **+ New**
2. Điền thông tin:
   - **Lead** hoặc **Khách thuê** (bắt buộc): Chọn lead hoặc khách thuê
   - **Bất động sản** (tùy chọn): Chọn bất động sản (nếu có)
   - **Phòng** (tùy chọn): Chọn phòng (nếu có)
   - **Môi giới** (bắt buộc): Chọn môi giới xử lý
   - **Schedule At** (bắt buộc): Thời gian hẹn xem phòng (datetime, phải trong tương lai)
   - **Trạng thái** (tự động): `requested`
   - **Note** (tùy chọn): Ghi chú
   - **Checklist** (tùy chọn): Checklist xem phòng (JSON)
   - **Virtual Viewing Link** (tùy chọn): Link xem ảo
3. Click **Lưu**
4. Viewing được tạo với trạng thái `requested`
5. Hệ thống gửi thông báo cho Lead/Tenant, Môi giới, và Quản lý

### 6. Confirm Viewing (Xác nhận Lịch)

1. Truy cập chi tiết viewing có trạng thái `requested`
2. Click **Confirm** hoặc **Xác nhận**
3. Viewing trạng thái chuyển sang `confirmed`
4. Hệ thống gửi thông báo cho Lead/Tenant (email/in-app)
5. Hệ thống gửi thông báo cho Môi giới

**Lưu ý**: 
- Chỉ có thể confirm viewing có trạng thái `requested`
- Confirm viewing để thông báo cho Lead/Tenant

### 7. Hủy Viewing (Hủy Lịch)

1. Truy cập chi tiết viewing cần hủy
2. Click **Hủy** hoặc **Hủy**
3. (Tùy chọn) Nhập lý do hủy
4. Xác nhận hủy
5. Viewing trạng thái chuyển sang `cancelled`
6. Hệ thống gửi thông báo cho Lead/Tenant, Môi giới, và Quản lý

**Lưu ý**: 
- Chỉ có thể hủy viewing có trạng thái `requested` hoặc `confirmed`
- Không thể hủy viewing đã `done` hoặc `cancelled`

### 8. Mark Done (Đánh dấu Đã Xem Xong)

1. Truy cập chi tiết viewing cần mark done
2. Click **Mark Done** hoặc **Đánh dấu đã xem xong**
3. Điền thông tin:
   - **Result Note** (bắt buộc): Kết quả sau khi xem phòng
   - **Feedback Rating** (tùy chọn): Đánh giá (1-5)
   - **Feedback Notes** (tùy chọn): Ghi chú feedback
   - **Photos** (tùy chọn): Upload hình ảnh
4. Click **Lưu**
5. Viewing trạng thái chuyển sang `done`
6. Hệ thống gửi thông báo cho Lead/Tenant, Môi giới, và Quản lý
7. Lead trạng thái có thể được cập nhật:
   - `qualified` nếu Lead quan tâm
   - `lost` nếu Lead không quan tâm

### 9. Cập nhật Viewing

1. Truy cập chi tiết viewing cần cập nhật
2. Click **Chỉnh sửa** (chỉ khi trạng thái = `requested`)
3. Cập nhật thông tin:
   - Schedule At (nếu có slot available)
   - Bất động sản, Phòng
   - Môi giới
   - Note
   - Checklist
   - Virtual Viewing Link
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Chỉ có thể cập nhật viewing có trạng thái `requested`
- Không thể cập nhật viewing đã `confirmed`, `done`, hoặc `cancelled`

### 10. Xóa Viewing

1. Truy cập chi tiết viewing cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa viewing

**Lưu ý**: 
- Có thể xóa viewing bất cứ lúc nào
- Xóa viewing không ảnh hưởng đến lead/tenant hoặc hợp đồng thuê

### 11. Xem Thống kê

1. Truy cập **Viewings** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Viewings by Trạng thái: Phân bố theo trạng thái
   - Viewings by Môi giới: Phân bố theo môi giới
   - Viewings by Period: Phân bố theo thời gian
   - Conversion Rate: Tỷ lệ chuyển đổi Viewing → Hợp đồng thuê
   - Average Feedback Rating: Đánh giá trung bình

## Ràng buộc và điều kiện

### Validation Rules

- **Lead hoặc Khách thuê**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
- **Môi giới**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
- **Schedule At**: 
  - Bắt buộc
  - Phải là datetime hợp lệ
  - Phải trong tương lai (khi tạo mới)
  - Phải trong available slots
- **Trạng thái**: 
  - Khi tạo: `requested`
  - Phải là một trong: requested, confirmed, done, no_show, cancelled
- **Photos**: 
  - Tùy chọn
  - Phải là file ảnh hợp lệ
  - Max size: 5MB mỗi file

### Business Rules

1. **Schedule At phải trong tương lai**
   - Không thể tạo viewing trong quá khứ
   - Phải trong available slots

2. **Available Slots**
   - Schedule At phải trong available slots
   - Không thể đặt lịch trùng với viewing khác

3. **Trạng thái Flow**
   - `requested` → `confirmed` → `done`
   - `requested` hoặc `confirmed` → `cancelled` hoặc `no_show`

4. **Chỉ có thể cập nhật viewing có trạng thái `requested`**
   - Không thể cập nhật viewing đã `confirmed`, `done`, hoặc `cancelled`

5. **Mark Done tự động cập nhật Lead trạng thái**
   - Lead trạng thái = `qualified` nếu Lead quan tâm
   - Lead trạng thái = `lost` nếu Lead không quan tâm

## Trạng thái và Workflow

### Viewing Trạng thái Flow

```
requested → confirmed → done
    ↓          ↓
cancelled   cancelled/no_show
```/no_show
```

- **requested**: Đã đặt lịch, chờ Môi giới xác nhận
- **confirmed**: Môi giới đã xác nhận lịch
- **done**: Đã hoàn tất xem phòng
- **no_show**: Không đến xem phòng
- **cancelled**: Đã hủy lịch xem phòng

### Workflow Tạo Viewing

1. Quản lý hoặc Môi giới tạo viewing mới
2. Điền thông tin: Lead/Tenant, Bất động sản, Phòng, Môi giới, Schedule At
3. Click Lưu → Viewing có trạng thái `requested`
4. Môi giới confirm viewing → Trạng thái `confirmed`
5. Sau khi xem phòng, Môi giới mark done → Trạng thái `done`
6. Lead trạng thái được cập nhật (nếu có)

## Ví dụ

### Ví dụ 1: Tạo Viewing mới

**Thông tin viewing:**
- Lead: Nguyễn Văn A
- Bất động sản: Bất động sản ABC
- Phòng: Phòng 101
- Môi giới: Môi giới B
- Schedule At: 2025-01-20 14:00
- Trạng thái: `requested`
- Note: "Khách hàng muốn xem phòng vào buổi chiều"

**Các bước:**
1. Truy cập Viewings
2. Click **Tạo Viewing**
3. Chọn Lead: Nguyễn Văn A
4. Chọn Bất động sản: Bất động sản ABC, Phòng: Phòng 101
5. Chọn Môi giới: Môi giới B
6. Chọn Schedule At: 2025-01-20 14:00
7. Nhập Note
8. Click **Lưu**
9. Viewing được tạo với trạng thái `requested`

### Ví dụ 2: Mark Done

**Kịch bản:** Môi giới đã xem phòng xong với Lead

**Các bước:**
1. Truy cập chi tiết viewing có trạng thái `confirmed`
2. Click **Mark Done**
3. Điền thông tin:
   - Result Note: "Khách hàng rất quan tâm, muốn đặt cọc"
   - Feedback Rating: 5
   - Feedback Notes: "Phòng đẹp, sạch sẽ"
   - Photos: Upload 3 ảnh phòng
4. Click **Lưu**
5. Viewing trạng thái chuyển sang `done`
6. Lead trạng thái tự động chuyển sang `qualified`

## Lưu ý

1. **Calendar Xem**
   - Sử dụng calendar xem để theo dõi lịch xem phòng
   - Dễ dàng sắp xếp và quản lý thời gian

2. **Today Xem**
   - Sử dụng today xem để xem lịch xem phòng hôm nay
   - Giúp không bỏ lỡ lịch hẹn

3. **Available Slots**
   - Kiểm tra available slots trước khi tạo viewing
   - Tránh đặt lịch trùng

4. **Mark Done**
   - Mark done ngay sau khi xem phòng xong
   - Điền đầy đủ thông tin để theo dõi hiệu quả

## Troubleshooting

### Không thể tạo viewing

1. Kiểm tra Schedule At có trong tương lai không
2. Kiểm tra Schedule At có trong available slots không
3. Kiểm tra Môi giới có tồn tại không
4. Kiểm tra Lead/Tenant có tồn tại không
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Available slots không có

1. Thử chọn thời gian khác
2. Liên hệ Môi giới để sắp xếp thời gian phù hợp
3. Chọn bất động sản/unit khác

### Không thể mark done

1. Kiểm tra viewing có trạng thái `confirmed` không
2. Kiểm tra Result Note đã điền chưa
3. Liên hệ hỗ trợ nếu vẫn không thể mark done

---

**Xem thêm:**
- [Quản lý Leads](./08-leads.md)
- [Quản lý Booking Deposits](./10-booking-deposits.md)
- [Workflow Lead to Hợp đồng thuê](../workflows/01-lead-to-lease.md)

**Cập nhật: 2025-01-XX

