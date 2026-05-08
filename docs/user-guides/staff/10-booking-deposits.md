# QUẢN LÝ ĐẶT CỌC - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý đặt cọc (booking deposits) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, approve, mark đã thanh toán, refund, hủy, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả booking deposits trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Tạo booking deposit: Cần capability `contract.booking_deposit.create`
  - Cập nhật booking deposit: Cần capability `contract.booking_deposit.update`
  - Xem tất cả booking deposits: Cần capability `contract.booking_deposit.view` hoặc `contract.booking_deposit.view_all`
  - Chỉ xem booking deposits của mình: Có capability `contract.booking_deposit.view_own` (mặc định)
  - Approve booking deposit: Chỉ Quản lý (Môi giới không có quyền)
  - Mark Đã thanh toán: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/booking-deposits`

## Các bước thực hiện

### 1. Xem danh sách Booking Deposits

1. Truy cập **Booking Deposits** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả booking deposits trong tổ chức
3. Có thể lọc theo:
   - Thanh toán Trạng thái (pending_approval, đang chờ, đã thanh toán, expired, cancelled, refunded)
   - Phòng (nếu có nhiều phòng)
   - Khách thuê, Lead (nếu có nhiều khách thuê/leads)
   - Môi giới (nếu có nhiều agents)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo created_at, hold_until, số tiền, trạng thái

### 2. Xem chi tiết Booking Deposit

1. Click vào deposit trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Deposit ID: Mã đặt cọc
     - Phòng: Phòng được đặt cọc
     - Khách thuê Người dùng hoặc Lead: Khách hàng
     - Môi giới: Người xử lý
     - Số tiền: Số tiền cọc
     - Deposit Loại: Loại cọc (booking, security, advance)
     - Hold Until: Ngày giữ đến khi nào
     - Thanh toán Trạng thái: Trạng thái thanh toán (pending_approval, đang chờ, đã thanh toán, expired, cancelled, refunded)
   - **Thông tin khác:**
     - Notes: Ghi chú
     - Created At: Ngày tạo deposit
     - Đã phê duyệt At: Ngày approve (nếu có)
     - Đã thanh toán At: Ngày thanh toán (nếu có)
     - Hóa đơn: Hóa đơn liên quan (nếu có)
     - Thanh toán: Thanh toán liên quan (nếu có)

### 3. Tạo Booking Deposit mới

1. Click **Tạo Booking Deposit** hoặc **+ New**
2. Điền thông tin:
   - **Phòng** (bắt buộc): Chọn phòng cần đặt cọc (phải available hoặc reserved)
   - **Khách thuê Người dùng** hoặc **Lead** (bắt buộc): Chọn khách thuê người dùng hoặc lead
   - **Môi giới** (bắt buộc): Chọn môi giới xử lý
   - **Số tiền** (bắt buộc): Số tiền cọc (phải > 0)
   - **Deposit Loại** (bắt buộc): Loại cọc (booking, security, advance)
   - **Hold Until** (bắt buộc): Ngày giữ đến khi nào (datetime, phải trong tương lai)
   - **Notes** (tùy chọn): Ghi chú
   - **Thanh toán Trạng thái** (tự động): `pending_approval` (chờ Quản lý approve)
3. Click **Lưu**
4. Booking Deposit được tạo với payment_status `pending_approval`
5. Phòng trạng thái tự động chuyển sang `reserved`
6. Hệ thống gửi thông báo cho Quản lý

### 4. Approve Booking Deposit (Phê duyệt Đặt Cọc)

1. Truy cập chi tiết deposit có payment_status `pending_approval`
2. Click **Approve** hoặc **Phê duyệt**
3. Xác nhận approve
4. Booking Deposit payment_status chuyển sang `pending` (chờ thanh toán)
5. Hệ thống tự động tạo Hóa đơn cho deposit:
   - Hóa đơn Loại: `booking_deposit`
   - Số tiền = Deposit Số tiền
   - Trạng thái: `draft`
   - Link với booking_deposit_id
6. Hệ thống gửi thông báo cho Khách thuê (nếu là Khách thuê Người dùng), Môi giới, và Quản lý

**Lưu ý**: 
- Chỉ Quản lý mới có thể approve deposit
- Approve deposit sẽ tự động tạo hóa đơn với trạng thái `draft`
- Sau khi approve, payment_status = `pending` (chờ thanh toán)

### 5. Phát hành Hóa đơn cho Deposit

1. Truy cập Hóa đơn được tạo tự động cho deposit
2. Review thông tin hóa đơn
3. Click **Issue** hoặc **Phát hành**
4. Hóa đơn trạng thái chuyển sang `issued`
5. Hệ thống gửi thông báo cho Khách thuê (nếu là Khách thuê Người dùng)

### 6. Mark Deposit Đã thanh toán (Đánh dấu Đã Thanh toán)

1. Sau khi khách thuê thanh toán hóa đơn deposit (hóa đơn trạng thái = `paid`), truy cập chi tiết deposit có payment_status `pending`
2. Click **Mark Đã thanh toán** hoặc **Đánh dấu đã thanh toán**
3. Hệ thống tự động link với Thanh toán từ hóa đơn
4. Booking Deposit payment_status chuyển sang `paid`
5. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Chỉ có thể mark đã thanh toán deposit có payment_status `pending` (sau khi đã approve)
- Deposit phải có hóa đơn đã `paid` trước khi mark đã thanh toán deposit
- Chỉ Quản lý mới có thể mark đã thanh toán deposit

### 7. Refund Booking Deposit (Hoàn Tiền Đặt Cọc - Nếu Hủy)

1. Nếu booking deposit đã đã thanh toán (payment_status = `paid`) nhưng chưa chuyển thành hợp đồng thuê và cần hủy, truy cập chi tiết deposit
2. Click **Refund Booking Deposit** hoặc **Hoàn tiền đặt cọc**
3. Hệ thống tự động tính toán:
   - Deposit Số tiền: Số tiền cọc ban đầu
   - Deducted Số tiền: Số tiền đã trừ (nếu có)
   - Refund Số tiền: Số tiền hoàn lại = Deposit Số tiền - Deducted Số tiền
4. Điền thông tin:
   - Refund Reason: Lý do hoàn tiền (ví dụ: "Hủy đặt cọc trước khi tạo hợp đồng")
   - Notes: Ghi chú
5. Click **Lưu**
6. Hệ thống tạo Company Hóa đơn (loại = deposit_refund) để hoàn tiền
7. Deposit payment_status chuyển sang `refunded` sau khi hóa đơn được đã thanh toán

**Lưu ý**: 
- Chỉ áp dụng khi booking deposit đã đã thanh toán nhưng chưa chuyển thành hợp đồng thuê
- Khác với Deposit Refund trong hợp đồng thuê (xem [Deposit Refunds](./11-deposit-refunds.md))
- Deposit Refund trong hợp đồng thuê được tạo từ Hợp đồng thuê, không phải Booking Deposit

### 8. Hủy Booking Deposit (Hủy Đặt Cọc)

1. Truy cập chi tiết deposit cần hủy
2. Click **Hủy** hoặc **Hủy**
3. (Tùy chọn) Nhập lý do hủy
4. Xác nhận hủy
5. Booking Deposit payment_status chuyển sang `cancelled`
6. Phòng trạng thái tự động chuyển sang `available`
7. Hệ thống gửi thông báo cho Khách thuê/Lead, Môi giới, và Quản lý

**Lưu ý**: 
- Chỉ có thể hủy deposit có payment_status `pending_approval`, `pending`, hoặc `expired`
- Không thể hủy deposit đã `paid` hoặc `refunded`

### 9. Cập nhật Booking Deposit

1. Truy cập chi tiết deposit cần cập nhật
2. Click **Chỉnh sửa** (chỉ khi payment_status = `pending_approval`)
3. Cập nhật thông tin:
   - Số tiền (nếu chưa approve)
   - Hold Until
   - Notes
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Chỉ có thể cập nhật deposit có payment_status `pending_approval`
- Không thể cập nhật deposit đã `pending` (sau approve), `paid`, hoặc `cancelled`

### 10. Xóa Booking Deposit

1. Truy cập chi tiết deposit cần xóa
2. Click **Xóa** (chỉ khi payment_status = `pending_approval` hoặc `cancelled`)
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa deposit
5. Phòng trạng thái tự động chuyển sang `available` nếu deposit là hoạt động duy nhất

**Lưu ý**: 
- Chỉ có thể xóa deposit có payment_status `pending_approval` hoặc `cancelled`
- Không thể xóa deposit đã `pending` (sau approve), `paid`, hoặc `refunded`

### 11. Auto-expire Booking Deposit

Hệ thống tự động expire deposit nếu quá Hold Until và chưa đã thanh toán:

1. Hệ thống kiểm tra deposits có Hold Until < now() và payment_status = `pending` (sau khi approve)
2. Hệ thống tự động cập nhật deposit payment_status = `expired`
3. Phòng trạng thái tự động chuyển sang `available`
4. Hệ thống gửi thông báo cho Khách thuê/Lead, Môi giới, và Quản lý

**Lưu ý**: 
- Auto-expire được chạy tự động (cron job)
- Deposit expired sẽ không thể thanh toán nữa (có thể khôi phục về đang chờ hoặc cancelled)

### 12. Xem Thống kê

1. Truy cập **Booking Deposits** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Deposits by Trạng thái: Phân bố theo trạng thái
   - Deposits by Period: Phân bố theo thời gian
   - Tổng Deposit Số tiền: Tổng số tiền đặt cọc
   - Đang chờ Deposits: Số deposit đang chờ approve
   - Đã thanh toán Deposits: Số deposit đã thanh toán
   - Conversion Rate: Tỷ lệ chuyển đổi Deposit → Hợp đồng thuê

## Ràng buộc và điều kiện

### Validation Rules

- **Phòng**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
  - Phải có trạng thái `available` hoặc `reserved`
- **Khách thuê Người dùng hoặc Lead**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
- **Môi giới**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
- **Số tiền**: 
  - Bắt buộc
  - Phải > 0
- **Deposit Loại**: 
  - Bắt buộc
  - Phải là một trong: booking, security, advance
- **Hold Until**: 
  - Bắt buộc
  - Phải là datetime hợp lệ
  - Phải trong tương lai (khi tạo mới)
- **Thanh toán Trạng thái**: 
  - Khi tạo: `pending_approval`
  - Phải là một trong: pending_approval, đang chờ, đã thanh toán, expired, cancelled, refunded

### Business Rules

1. **Phòng phải available hoặc reserved**
   - Không thể đặt cọc cho phòng đã `occupied`
   - Phòng trạng thái chuyển sang `reserved` khi tạo deposit

2. **Deposit phải được approve trước khi mark đã thanh toán**
   - Chỉ Quản lý mới có thể approve deposit
   - Approve deposit: payment_status từ `pending_approval` → `pending`
   - Approve deposit sẽ tự động tạo hóa đơn với trạng thái `draft`

3. **Auto-tạo Hóa đơn**
   - Hóa đơn được tạo tự động khi approve deposit
   - Hóa đơn có trạng thái `draft` cho đến khi issue

4. **Mark Đã thanh toán**
   - Chỉ có thể mark đã thanh toán deposit có trạng thái `approved`
   - Deposit phải có hóa đơn đã `paid` trước khi mark đã thanh toán deposit

5. **Auto-expire**
   - Deposit tự động expire nếu quá Hold Until và chưa đã thanh toán
   - Phòng trạng thái chuyển sang `available` khi deposit expire

6. **Refund Booking Deposit**
   - Chỉ áp dụng khi deposit đã đã thanh toán nhưng chưa chuyển thành hợp đồng thuê và cần hủy
   - Tạo Company Hóa đơn để hoàn tiền
   - Khác với Deposit Refund trong hợp đồng thuê (từ Hợp đồng thuê)

## Trạng thái và Workflow

### Booking Deposit Thanh toán Trạng thái Flow

```
pending_approval → pending → paid → refunded
         ↓           ↓
      cancelled   expired
```

- **pending_approval**: Đặt cọc đã được tạo, chờ Quản lý phê duyệt
- **đang chờ**: Quản lý đã phê duyệt, chờ thanh toán (sau khi approve)
- **đã thanh toán**: Đã thanh toán cọc, đang giữ tiền cọc
- **expired**: Đã hết hạn (quá Hold Until và chưa đã thanh toán)
- **refunded**: Đã hoàn tiền cọc
- **cancelled**: Đã hủy đặt cọc

### Workflow Tạo Booking Deposit

1. Môi giới hoặc Quản lý tạo booking deposit mới
2. Điền thông tin: Phòng, Khách thuê/Lead, Môi giới, Số tiền, Deposit Loại, Hold Until
3. Click Lưu → Deposit có payment_status `pending_approval`
4. Phòng trạng thái chuyển sang `reserved`
5. Quản lý approve deposit → payment_status `pending`, tự động tạo hóa đơn với trạng thái `draft`
6. Quản lý issue hóa đơn → Hóa đơn trạng thái `issued`
7. Khách thuê thanh toán hóa đơn → Hóa đơn trạng thái `paid`
8. Quản lý mark deposit đã thanh toán → payment_status `paid`
9. Tiền cọc được giữ cho đến khi kết thúc hợp đồng thuê
10. Tiền đặt cọc chuyển thành deposit trong hợp đồng thuê khi tạo hợp đồng (từ booking deposit đã đã thanh toán)
11. Nếu hủy booking deposit trước khi tạo hợp đồng thuê → Refund Booking Deposit → payment_status `refunded`

## Ví dụ

### Ví dụ 1: Tạo và Approve Booking Deposit

**Thông tin deposit:**
- Phòng: P101 (Bất động sản ABC)
- Lead: Nguyễn Văn A
- Môi giới: Môi giới B
- Số tiền: 20,000,000 VND
- Deposit Loại: `booking`
- Hold Until: 2025-01-22 23:59:59
- Thanh toán Trạng thái: `pending_approval`

**Các bước:**
1. Môi giới tạo booking deposit với thông tin trên
2. Phòng trạng thái chuyển sang `reserved`
3. Quản lý approve deposit → payment_status `pending`
4. Hệ thống tự động tạo Hóa đơn:
   - Hóa đơn Loại: `booking_deposit`
   - Số tiền: 20,000,000 VND
   - Trạng thái: `draft`
5. Quản lý issue hóa đơn → Hóa đơn trạng thái `issued`
6. Khách thuê thanh toán hóa đơn → Hóa đơn trạng thái `paid`
7. Quản lý mark deposit đã thanh toán → payment_status `paid`

### Ví dụ 2: Refund Deposit

**Kịch bản:** Hợp đồng thuê kết thúc, cần hoàn tiền cọc

**Deposit:**
- Số tiền: 20,000,000 VND
- Thanh toán Trạng thái: `paid`

**Các bước:**
1. Truy cập chi tiết deposit có payment_status `paid`
2. Click **Refund Booking Deposit**
3. Hệ thống tính toán:
   - Deposit Số tiền: 20,000,000 VND
   - Deducted Số tiền:
     - Unpaid hóa đơn: 5,000,000 VND
     - Ticket costs: 1,000,000 VND
     - Tổng: 6,000,000 VND
   - Refund Số tiền: 20,000,000 - 6,000,000 = 14,000,000 VND
4. Nhập Refund Reason: "Hủy đặt cọc trước khi tạo hợp đồng"
5. Click **Lưu**
6. Hệ thống tạo Company Hóa đơn (loại = deposit_refund)
7. Quản lý approve và đã thanh toán company hóa đơn → Deposit payment_status = `refunded`

## Lưu ý

1. **Phòng Trạng thái**
   - Phòng trạng thái chuyển sang `reserved` khi tạo deposit
   - Phòng trạng thái chuyển sang `available` khi deposit expire, hủy, hoặc refund

2. **Approve Deposit**
   - Chỉ Quản lý mới có thể approve deposit
   - Approve deposit sẽ tự động tạo hóa đơn

3. **Auto-expire**
   - Deposit tự động expire nếu quá Hold Until và chưa đã thanh toán
   - Phòng được release về `available`

4. **Refund Calculation**
   - Refund Số tiền = Deposit Số tiền - Deducted Số tiền
   - Deducted Số tiền bao gồm: unpaid hóa đơn, ticket costs, damages

## Troubleshooting

### Không thể tạo deposit

1. Kiểm tra Phòng có trạng thái `available` hoặc `reserved` không
2. Kiểm tra Phòng có thuộc về tổ chức không
3. Kiểm tra Số tiền > 0
4. Kiểm tra Hold Until có trong tương lai không
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Deposit không tự động expire

1. Kiểm tra cron job có chạy không
2. Kiểm tra Hold Until có < now không
3. Kiểm tra deposit có trạng thái `approved` hoặc `pending` không
4. Chạy manual expire nếu cần
5. Liên hệ hỗ trợ nếu vẫn không expire

---

**Xem thêm:**
- [Nhân viên Phòng](./04-units.md)
- [Nhân viên Hợp đồng thuê](./05-leases.md)
- [Nhân viên Deposit Refunds](./11-deposit-refunds.md)
- [Workflow Booking Deposit Refund](../workflows/06-booking-deposit-refund.md)

**Cập nhật: 2025-01-XX

