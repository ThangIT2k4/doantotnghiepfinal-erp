# QUY TRÌNH TÍNH TOÁN HOA HỒNG

## Tổng quan

Quy trình này mô tả cách hệ thống tính toán hoa hồng (commission) cho Môi giới khi có các trigger events (deposit đã thanh toán, hợp đồng thuê signed, hóa đơn đã thanh toán, viewing done, listing published).

## Workflow

### Bước 1: Trigger Event (Sự kiện Kích hoạt)

**Người thực hiện:** Hệ thống (tự động khi có sự kiện)

**Các trigger events:**

#### 1.1. Deposit Đã thanh toán (Đặt cọc đã thanh toán)

1. Booking Deposit được approve
2. Deposit được mark đã thanh toán
3. Hệ thống trigger event `deposit_paid`
4. Hệ thống tìm các Commission Policies phù hợp

#### 1.2. Hợp đồng thuê Signed (Ký hợp đồng)

1. Hợp đồng thuê được tạo với trạng thái `active`
2. Hệ thống trigger event `lease_signed`
3. Hệ thống tìm các Commission Policies phù hợp

#### 1.3. Hóa đơn Đã thanh toán (Hóa đơn đã thanh toán)

1. Hóa đơn được thanh toán đủ (trạng thái = `paid`)
2. Hệ thống trigger event `invoice_paid`
3. Hệ thống tìm các Commission Policies phù hợp
4. Hệ thống kiểm tra Apply Limit Months (nếu có)

**Lưu ý**: 
- Apply Limit Months chỉ áp dụng cho trigger `invoice_paid`
- Chỉ tính hoa hồng cho N tháng đầu tiên (nếu Apply Limit Months = N)
- Từ tháng thứ (N+1) trở đi, không tính hoa hồng

#### 1.4. Viewing Done (Hoàn tất lịch xem phòng)

1. Viewing được mark done
2. Hệ thống trigger event `viewing_done`
3. Hệ thống tìm các Commission Policies phù hợp

#### 1.5. Listing Published (Đăng tin)

1. Listing được publish
2. Hệ thống trigger event `listing_published`
3. Hệ thống tìm các Commission Policies phù hợp

### Bước 2: Tìm Commission Policies Phù hợp

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Hệ thống tìm tất cả Commission Policies có:
   - Trạng thái = `active`
   - Trigger Event = event vừa xảy ra
   - Filters (nếu có) phù hợp với dữ liệu hiện tại
2. Hệ thống lọc policies theo:
   - Hoạt động trạng thái
   - Filters JSON (nếu có)
   - Apply Limit Months (đối với `invoice_paid`)
3. Hệ thống trả về danh sách policies phù hợp

**Lưu ý**: 
- Nếu không có policy phù hợp, không tạo commission event
- Có thể có nhiều policies cùng trigger một event

### Bước 3: Tạo Commission Event

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Với mỗi policy phù hợp, hệ thống tạo Commission Event
2. Commission Event chứa:
   - Policy ID: ID của commission policy
   - Người dùng ID: ID của Môi giới được gán hoa hồng
   - Trigger Event: Event kích hoạt (deposit_paid, lease_signed, invoice_paid, etc.)
   - Trigger Source Loại: Loại source (booking_deposit, hợp đồng thuê, hóa đơn, viewing, listing)
   - Trigger Source ID: ID của source (deposit ID, hợp đồng thuê ID, hóa đơn ID, etc.)
   - Số tiền: Số tiền tính hoa hồng (từ source)
   - Trạng thái: `pending`
3. Hệ thống gán hoa hồng cho Môi giới thông qua `user_id` trong commission_event
4. Hệ thống lưu Commission Event

### Bước 4: Tính toán Hoa hồng

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Với mỗi Commission Event, hệ thống tính toán commission theo policy:
   
#### 4.1. Calculation Loại = Percent

1. Hệ thống tính commission = Số tiền × Percent Value / 100
2. Ví dụ: Số tiền = 10,000,000 VND, Percent Value = 5% → Commission = 500,000 VND

#### 4.2. Calculation Loại = Flat

1. Hệ thống sử dụng Flat Số tiền từ policy
2. Ví dụ: Flat Số tiền = 1,000,000 VND → Commission = 1,000,000 VND

#### 4.3. Calculation Loại = Tiered

1. Hệ thống tính commission theo bậc thang (tiered)
2. Ví dụ: 
   - Số tiền <= 10,000,000: 5%
   - Số tiền > 10,000,000 và <= 50,000,000: 7%
   - Số tiền > 50,000,000: 10%

3. Hệ thống áp dụng Min Số tiền và Cap Số tiền (nếu có):
   - Commission >= Min Số tiền (nếu có)
   - Commission <= Cap Số tiền (nếu có)

4. Hệ thống cập nhật Commission Event với:
   - Commission Số tiền: Số tiền hoa hồng đã tính
   - Trạng thái: `pending`

**Lưu ý**: 
- Commission Số tiền được tính và lưu trong Commission Event
- Người dùng ID được gán từ Môi giới tham gia sự kiện

### Bước 5: Xử lý Apply Limit Months (Chỉ cho Hóa đơn Đã thanh toán)

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Chỉ áp dụng cho trigger `invoice_paid`
2. Kiểm tra policy có Apply Limit Months không:
   - Nếu có Apply Limit Months = N:
     - Kiểm tra số tháng từ khi hợp đồng thuê bắt đầu đến hiện tại
     - Đếm số hóa đơn đã thanh toán của hợp đồng thuê
     - Chỉ tính hoa hồng nếu số hóa đơn đã thanh toán <= N
     - Từ hóa đơn thứ (N+1) trở đi, không tính hoa hồng
   - Nếu Apply Limit Months = NULL:
     - Tính hoa hồng cho tất cả các tháng (không giới hạn)

**Ví dụ:**
- Policy: `apply_limit_months = 3`, `trigger_event = invoice_paid`, `percent_value = 5%`
- Hợp đồng thuê: 12 tháng, rent = 10,000,000 VND/tháng
- Hóa đơn tháng 1 (tháng 1): Tính hoa hồng ✓ (10M × 5% = 500K)
- Hóa đơn tháng 2 (tháng 2): Tính hoa hồng ✓ (10M × 5% = 500K)
- Hóa đơn tháng 3 (tháng 3): Tính hoa hồng ✓ (10M × 5% = 500K)
- Hóa đơn tháng 4 (tháng 4): Không tính hoa hồng ✗
- Hóa đơn tháng 5-12: Không tính hoa hồng ✗
- Tổng hoa hồng: 3 × 500K = 1,500,000 VND

### Bước 6: Quản lý Phê duyệt Hoa hồng

**Người thực hiện:** Quản lý

**Các bước:**
1. Truy cập **Commission Events** → Tìm event có trạng thái `pending`
2. Review thông tin commission event:
   - Policy: Commission policy được áp dụng
   - Môi giới: Môi giới được gán hoa hồng
   - Số tiền: Số tiền tính hoa hồng (từ source)
   - Commission Số tiền: Số tiền hoa hồng đã tính
   - Trigger Event: Event kích hoạt
   - Trigger Source: Source liên quan (deposit, hợp đồng thuê, hóa đơn, etc.)
3. Click **Approve** hoặc **Reject**
4. Nếu Approve:
   - Commission Event trạng thái = `approved`
   - Hệ thống gửi thông báo cho Môi giới
5. Nếu Reject:
   - Commission Event trạng thái = `rejected`
   - Hệ thống gửi thông báo cho Môi giới

**Xem chi tiết:**
- [Quản lý Commission Events](../manager/19-commission-events.md)

### Bước 7: Kiểm tra Basis (Cơ sở Tính toán)

**Người thực hiện:** Hệ thống (tự động) hoặc Quản lý

**Các bước:**
1. Kiểm tra Commission Policy có Basis:
   - **Cash**: Chờ thanh toán thực tế
   - **Accrual**: Sẵn sàng thanh toán ngay

#### 7.1. Basis = Cash

1. Chờ thanh toán thực tế từ source (deposit đã thanh toán, hóa đơn đã thanh toán, etc.)
2. Khi thanh toán thành công, commission mới sẵn sàng thanh toán
3. Nếu chưa có thanh toán, commission chưa sẵn sàng thanh toán

#### 7.2. Basis = Accrual

1. Commission sẵn sàng thanh toán ngay sau khi approve
2. Không cần chờ thanh toán thực tế

**Lưu ý**: 
- Basis chỉ ảnh hưởng đến thời điểm có thể mark đã thanh toán
- Không ảnh hưởng đến cách tính toán commission

### Bước 8: Đánh dấu Đã thanh toán (Mark Đã thanh toán)

**Người thực hiện:** Quản lý

**Các bước:**
1. Sau khi commission được approve và sẵn sàng thanh toán, Quản lý mark đã thanh toán
2. Truy cập **Commission Events** → Chọn event có trạng thái `approved`
3. Click **Mark Đã thanh toán** hoặc **Đánh dấu đã thanh toán**
4. Hệ thống cập nhật Commission Event trạng thái = `paid`
5. Hệ thống tạo Payroll Item hoặc Company Hóa đơn:
   - **Payroll Item**: Nếu commission được thanh toán qua payroll
     - Tạo Payroll Item cho Môi giới
     - Link với Payroll Payslip
   - **Company Hóa đơn**: Nếu commission được thanh toán riêng
     - Tạo Company Hóa đơn cho Môi giới
     - Hóa đơn Loại = `user_payout`
     - Số tiền = Commission Số tiền
6. Hệ thống gửi thông báo cho Môi giới

**Lưu ý**: 
- Commission có thể được thanh toán qua payroll hoặc company hóa đơn
- Quản lý chọn phương thức thanh toán phù hợp

## Trạng thái và Chuyển đổi

### Commission Event Trạng thái Flow

```
pending → approved → paid
         ↓
      rejected
```

- **đang chờ**: Commission event đã được tạo, chờ Quản lý phê duyệt
- **đã phê duyệt**: Quản lý đã phê duyệt, chờ mark đã thanh toán
- **đã thanh toán**: Commission đã được thanh toán
- **từ chối**: Quản lý đã từ chối commission

### Trigger Events và Policies

```
Trigger Event → Find Policies → Create Commission Event → Calculate Commission → Approve → Paid
```

## Ràng buộc

1. **Apply Limit Months chỉ áp dụng cho invoice_paid**
   - Chỉ kiểm tra Apply Limit Months khi trigger = `invoice_paid`
   - Các trigger khác không áp dụng Apply Limit Months

2. **Commission Số tiền phải >= Min Số tiền và <= Cap Số tiền**
   - Nếu Commission Số tiền < Min Số tiền, sử dụng Min Số tiền
   - Nếu Commission Số tiền > Cap Số tiền, sử dụng Cap Số tiền

3. **Môi giới phải được gán trong sự kiện**
   - Commission Event phải có Người dùng ID (Môi giới)
   - Môi giới được gán từ source (deposit môi giới, hợp đồng thuê môi giới, hóa đơn môi giới, etc.)

4. **Policy phải hoạt động**
   - Chỉ policies có trạng thái `active` mới được áp dụng
   - Policies không hoạt động không tạo commission event

5. **Basis = Cash cần thanh toán thực tế**
   - Nếu Basis = Cash, phải có thanh toán thành công từ source
   - Nếu chưa có thanh toán, commission chưa sẵn sàng thanh toán

## Ví dụ

### Ví dụ 1: Commission từ Deposit Đã thanh toán

**Kịch bản:** Booking Deposit được thanh toán, có commission policy

**Booking Deposit:**
- Số tiền: 5,000,000 VND
- Môi giới: Môi giới A
- Trạng thái: `paid`

**Commission Policy:**
- Trigger Event: `deposit_paid`
- Calc Loại: `percent`
- Percent Value: `3%`
- Min Số tiền: `100,000 VND`
- Cap Số tiền: `500,000 VND`
- Trạng thái: `active`

**Các bước:**
1. Booking Deposit được mark đã thanh toán
2. Hệ thống trigger event `deposit_paid`
3. Hệ thống tìm policy phù hợp → Tìm thấy policy trên
4. Hệ thống tạo Commission Event:
   - Người dùng ID: Môi giới A
   - Trigger Event: `deposit_paid`
   - Số tiền: 5,000,000 VND
5. Hệ thống tính commission: 5,000,000 × 3% = 150,000 VND
6. Hệ thống áp dụng Min Số tiền: 150,000 >= 100,000 ✓
7. Hệ thống áp dụng Cap Số tiền: 150,000 <= 500,000 ✓
8. Commission Số tiền = 150,000 VND
9. Trạng thái = `pending`
10. Quản lý approve → trạng thái = `approved`
11. Quản lý mark đã thanh toán → trạng thái = `paid`

### Ví dụ 2: Commission từ Hóa đơn Đã thanh toán với Apply Limit Months

**Kịch bản:** Hóa đơn được thanh toán, có commission policy với Apply Limit Months

**Hợp đồng thuê:**
- Start Ngày: 2025-01-01
- Rent: 10,000,000 VND/tháng
- Thanh toán Cycle: hàng tháng
- Môi giới: Môi giới B

**Commission Policy:**
- Trigger Event: `invoice_paid`
- Calc Loại: `percent`
- Percent Value: `5%`
- Apply Limit Months: `3`
- Trạng thái: `active`

**Hóa đơn tháng 1:**
- Hóa đơn Number: HD-202502-0001
- Số tiền: 10,000,000 VND
- Trạng thái: `paid`

**Các bước:**
1. Hóa đơn được thanh toán đủ
2. Hệ thống trigger event `invoice_paid`
3. Hệ thống kiểm tra Apply Limit Months = 3
4. Hệ thống đếm số hóa đơn đã thanh toán của hợp đồng thuê = 1 (tháng 1)
5. Hệ thống kiểm tra: 1 <= 3 → Tính hoa hồng ✓
6. Hệ thống tạo Commission Event:
   - Người dùng ID: Môi giới B
   - Trigger Event: `invoice_paid`
   - Số tiền: 10,000,000 VND
   - Commission Số tiền: 10,000,000 × 5% = 500,000 VND
7. Trạng thái = `pending`
8. Quản lý approve → trạng thái = `approved`
9. Quản lý mark đã thanh toán → trạng thái = `paid`

**Hóa đơn tháng 4:**
- Hóa đơn Number: HD-202505-0001
- Số tiền: 10,000,000 VND
- Trạng thái: `paid`

**Các bước:**
1. Hóa đơn được thanh toán đủ
2. Hệ thống trigger event `invoice_paid`
3. Hệ thống kiểm tra Apply Limit Months = 3
4. Hệ thống đếm số hóa đơn đã thanh toán của hợp đồng thuê = 4 (tháng 1, 2, 3, 4)
5. Hệ thống kiểm tra: 4 > 3 → Không tính hoa hồng ✗
6. Không tạo Commission Event

## Lưu ý

1. **Apply Limit Months**
   - Chỉ áp dụng cho trigger `invoice_paid`
   - Giới hạn số tháng tính hoa hồng
   - Tính theo số hóa đơn đã thanh toán, không phải số tháng từ start ngày

2. **Multiple Policies**
   - Có thể có nhiều policies cùng trigger một event
   - Mỗi policy tạo một commission event riêng
   - Môi giới có thể nhận nhiều commissions từ một event

3. **Commission Calculation**
   - Percent: Tính % từ số tiền
   - Flat: Sử dụng số tiền cố định
   - Tiered: Tính theo bậc thang
   - Min/Cap Số tiền: Áp dụng giới hạn

4. **Basis**
   - Cash: Chờ thanh toán thực tế
   - Accrual: Sẵn sàng thanh toán ngay

5. **Thanh toán Methods**
   - Commission có thể được thanh toán qua payroll hoặc company hóa đơn
   - Quản lý chọn phương thức phù hợp

---

**Xem thêm:**
- [Quản lý Commission Policies](../manager/18-commission-policies.md)
- [Quản lý Commission Events](../manager/19-commission-events.md)
- [Môi giới Commission Events](../agent/18-commission-events.md)

**Cập nhật**: 2025-11-02

