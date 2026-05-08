# QUY TRÌNH TỪ LEAD ĐẾN LEASE

## Tổng quan

Quy trình này mô tả các bước từ khi có Lead (khách hàng tiềm năng) đến khi tạo Hợp đồng thuê (hợp đồng thuê) thành công.

## Workflow

### Bước 1: Tạo Lead

**Người thực hiện:** Môi giới hoặc Lead tự đăng ký

**Các bước:**
1. Truy cập **Leads** → **Tạo**
2. Điền thông tin:
   - Name, Phone, Email
   - Source (web, zalo, fb, referral, etc.)
   - Desired City, Budget (min, max)
   - Note
   - Trạng thái: `new`
3. Click **Lưu**
4. Lead được tạo với trạng thái `new`

**Xem chi tiết:**
- [Quản lý Leads](../manager/08-leads.md) - Route: `/staff/leads`
- [Môi giới Leads](../agent/07-leads.md) - Route: `/staff/leads` (với capability check)

### Bước 2: Tạo Lịch Xem Phòng (Viewing)

**Người thực hiện:** Môi giới

**Các bước:**
1. Truy cập **Viewings** → **Tạo**
2. Chọn **Lead** hoặc tạo Lead mới
3. Chọn **Bất động sản** và **Phòng** (nếu có)
4. Chọn **Môi giới**
5. Điền thông tin:
   - Schedule At (datetime)
   - Trạng thái: `requested`
   - Note
6. Click **Lưu**
7. Viewing được tạo với trạng thái `requested`

**Xem chi tiết:**
- [Quản lý Viewings](../manager/09-viewings.md) - Route: `/staff/viewings`
- [Môi giới Viewings](../agent/08-viewings.md) - Route: `/staff/viewings` (với capability check)

### Bước 3: Xác nhận Lịch Xem

**Người thực hiện:** Môi giới hoặc Quản lý

**Các bước:**
1. Truy cập chi tiết Viewing
2. Click **Confirm**
3. Viewing trạng thái chuyển sang `confirmed`
4. Hệ thống gửi thông báo cho Lead (email/in-app)

**Lưu ý:** 
- Có thể hủy lịch xem nếu không phù hợp
- Lead cũng có thể hủy lịch xem từ phía họ

### Bước 4: Đánh dấu Đã Xem Xong

**Người thực hiện:** Môi giới

**Các bước:**
1. Sau khi xem phòng xong, truy cập chi tiết Viewing
2. Click **Mark Done**
3. Điền thông tin:
   - Result Note: Kết quả xem phòng
   - Feedback Rating: Đánh giá (1-5)
   - Feedback Notes: Ghi chú
   - Upload Photos (nếu có)
4. Click **Lưu**
5. Viewing trạng thái chuyển sang `done`
6. Lead trạng thái có thể được cập nhật:
   - `qualified` nếu Lead quan tâm
   - `lost` nếu Lead không quan tâm

### Bước 5: Tạo Đặt Cọc (Booking Deposit)

**Người thực hiện:** Môi giới (nếu Lead quan tâm)

**Các bước:**
1. Truy cập **Booking Deposits** → **Tạo**
2. Chọn **Phòng** (phòng Lead muốn đặt)
3. Chọn **Khách thuê** (Lead) hoặc **Lead**
4. Chọn **Môi giới**
5. Điền thông tin:
   - Số tiền: Số tiền cọc
   - Deposit Loại: `booking`
   - Hold Until: Ngày giữ đến khi nào
   - Notes
6. Click **Lưu**
7. Deposit được tạo với trạng thái `pending`
8. Phòng trạng thái chuyển sang `reserved`

**Xem chi tiết:**
- [Quản lý Booking Deposits](../manager/10-booking-deposits.md) - Route: `/staff/booking-deposits`
- [Môi giới Booking Deposits](../agent/09-booking-deposits.md) - Route: `/staff/booking-deposits` (với capability check)

### Bước 6: Phê duyệt Đặt Cọc

**Người thực hiện:** Quản lý

**Các bước:**
1. Truy cập chi tiết Booking Deposit
2. Review thông tin deposit
3. Click **Approve**
4. Deposit trạng thái chuyển sang `approved`
5. Hệ thống tự động tạo Hóa đơn cho deposit

**Lưu ý:** 
- Quản lý có thể reject deposit nếu không phù hợp
- Phòng vẫn giữ trạng thái `reserved` khi deposit được approve

### Bước 7: Đánh dấu Đã Thanh toán Cọc

**Người thực hiện:** Môi giới hoặc Quản lý

**Các bước:**
1. Sau khi Lead thanh toán tiền cọc, truy cập chi tiết Booking Deposit
2. Click **Mark Đã thanh toán**
3. Link với Thanh toán (nếu đã có) hoặc tạo Thanh toán mới
4. Deposit trạng thái chuyển sang `paid`
5. Hóa đơn trạng thái cũng chuyển sang `paid`

### Bước 8: Chuyển đổi Lead thành Khách thuê

**Người thực hiện:** Môi giới hoặc Quản lý

**Các bước:**
1. Sau khi deposit đã đã thanh toán, truy cập chi tiết Lead
2. Click **Convert to Khách thuê**
3. Hệ thống tạo Người dùng account cho Lead (nếu chưa có)
4. Lead trạng thái chuyển sang `converted`
5. Lead được link với Người dùng account

**Lưu ý:** 
- Nếu Lead đã có Người dùng account (đã đăng ký), hệ thống chỉ link Lead với Người dùng
- Nếu Lead chưa có Người dùng account, hệ thống tạo mới và gửi email xác thực

### Bước 9: Tạo Hợp đồng Thuê (Hợp đồng thuê)

**Người thực hiện:** Môi giới hoặc Quản lý

**Các bước:**
1. Truy cập **Hợp đồng thuê** → **Tạo**
2. Chọn **Phòng** (phòng đã đặt cọc)
3. Chọn **Khách thuê** (người dùng đã convert từ Lead)
4. Chọn **Môi giới** (môi giới xử lý)
5. Điền thông tin:
   - Start Ngày, End Ngày
   - Rent Số tiền, Deposit Số tiền
   - Billing Day, Thanh toán Cycle, Thanh toán Day
   - Services (điện, nước, internet, etc.)
   - Residents (người ở cùng, nếu có)
6. Click **Lưu**
7. Hợp đồng thuê được tạo với trạng thái `active`
8. Phòng trạng thái chuyển sang `occupied`
9. Hệ thống tự động tạo Hóa đơn đầu tiên theo Thanh toán Cycle

**Xem chi tiết:**
- [Quản lý Hợp đồng thuê](../manager/05-leases.md) - Route: `/staff/leases`
- [Môi giới Hợp đồng thuê](../agent/05-leases.md) - Route: `/staff/leases` (với capability check)

### Bước 10: Phát hành Hóa đơn

**Người thực hiện:** Quản lý hoặc Môi giới

**Các bước:**
1. Truy cập Hóa đơn được tạo tự động
2. Review thông tin hóa đơn
3. Click **Issue**
4. Hóa đơn trạng thái chuyển sang `issued`
5. Hệ thống gửi thông báo cho Khách thuê

**Xem chi tiết:**
- [Quản lý Hóa đơn](../manager/12-invoices.md) - Route: `/staff/invoices`
- [Môi giới Hóa đơn](../agent/11-invoices.md) - Route: `/staff/invoices` (với capability check)

## Trạng thái và Chuyển đổi

### Lead Trạng thái Flow

```
new → contacted → qualified → converted
                    ↓
                   lost
```

### Viewing Trạng thái Flow

```
requested → confirmed → done
             ↓          ↓
         cancelled   no_show
```

### Booking Deposit Trạng thái Flow

```
pending → approved → paid
         ↓
      rejected
```

### Phòng Trạng thái Flow

```
available → reserved → occupied
```

## Ràng buộc

1. **Phòng chỉ có 1 hợp đồng thuê hoạt động tại một thời điểm**
   - Phải terminate hợp đồng thuê hiện tại trước khi tạo hợp đồng thuê mới cho phòng khác
   - Hoặc chọn phòng khác

2. **Booking Deposit phải được approve trước khi mark đã thanh toán**
   - Quản lý phải approve deposit
   - Deposit không thể mark đã thanh toán nếu chưa approve

3. **Lead phải được convert thành Khách thuê trước khi tạo Hợp đồng thuê**
   - Hoặc có thể tạo Hợp đồng thuê trực tiếp với Khách thuê (nếu Khách thuê đã có account)

4. **Deposit Số tiền phải được tính vào Hợp đồng thuê**
   - Deposit đã thanh toán sẽ được trừ vào Deposit Số tiền của Hợp đồng thuê
   - Không cần thanh toán lại deposit khi tạo Hợp đồng thuê

## Ví dụ

### Ví dụ hoàn chỉnh

**Lead:** Nguyễn Văn A, Phone: 0123456789, Source: Web

**Bước 1:** Môi giới tạo Lead với trạng thái `new`

**Bước 2:** Môi giới tạo Viewing cho Phòng P101, Schedule: 2025-01-15 14:00

**Bước 3:** Môi giới confirm Viewing

**Bước 4:** Sau khi xem, Môi giới mark done, Lead trạng thái → `qualified`

**Bước 5:** Môi giới tạo Booking Deposit: Phòng P101, Số tiền: 5,000,000 VND, Hold Until: 2025-01-22

**Bước 6:** Quản lý approve deposit

**Bước 7:** Lead thanh toán, Môi giới mark đã thanh toán

**Bước 8:** Môi giới convert Lead thành Khách thuê

**Bước 9:** Môi giới tạo Hợp đồng thuê: Phòng P101, Khách thuê Nguyễn Văn A, Start: 2025-02-01, End: 2026-01-31

**Bước 10:** Quản lý issue Hóa đơn đầu tiên

## Lưu ý

1. **Workflow có thể linh hoạt**
   - Không nhất thiết phải qua tất cả các bước
   - Có thể tạo Hợp đồng thuê trực tiếp nếu Khách thuê đã có account

2. **Lead có thể không qua Viewing**
   - Có thể tạo Booking Deposit trực tiếp nếu Lead đã biết phòng

3. **Deposit và Hợp đồng thuê**
   - Deposit Số tiền sẽ được trừ vào Deposit của Hợp đồng thuê
   - Không cần thanh toán lại deposit

---

**Xem thêm:**
- [Quản lý Leads](../manager/08-leads.md) - Route: `/staff/leads`
- [Quản lý Viewings](../manager/09-viewings.md) - Route: `/staff/viewings`
- [Quản lý Booking Deposits](../manager/10-booking-deposits.md) - Route: `/staff/booking-deposits`
- [Quản lý Hợp đồng thuê](../manager/05-leases.md) - Route: `/staff/leases`
- [Routes Mapping](../common/00-routes-mapping.md) - Mapping routes cũ và mới

**Cập nhật**: 2025-11-03  
**Lưu ý**: Tất cả routes Quản lý và Môi giới đã được unified thành `/staff/*`

