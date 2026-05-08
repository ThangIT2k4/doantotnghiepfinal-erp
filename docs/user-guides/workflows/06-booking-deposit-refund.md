# QUY TRÌNH ĐẶT CỌC VÀ HOÀN TIỀN CỌC

## Tổng quan

Quy trình này mô tả 2 chức năng riêng biệt:
1. **Booking Deposit (Đặt cọc giữ chỗ)**: Đặt cọc trước khi có hợp đồng thuê
2. **Deposit Refund (Hoàn tiền cọc trong hợp đồng)**: Hoàn tiền cọc khi hợp đồng thuê kết thúc hoặc bị terminate

**Lưu ý**: Hai chức năng này hoàn toàn độc lập:
- Booking Deposit là tiền đặt cọc giữ chỗ (trước khi có hợp đồng)
- Deposit Refund là hoàn tiền cọc trong hợp đồng thuê (từ hợp đồng thuê.deposit_amount)

## Workflow

### Bước 1: Tạo Booking Deposit

**Người thực hiện:** Môi giới hoặc Quản lý

**Các bước:**
1. Truy cập **Booking Deposits** → **Tạo**
2. Chọn **Phòng** (phòng cần đặt cọc)
3. Chọn **Khách thuê Người dùng** hoặc **Lead**
4. Chọn **Môi giới**
5. Điền thông tin:
   - **Số tiền**: Số tiền cọc
   - **Deposit Loại**: Loại cọc (booking, security, advance)
   - **Hold Until**: Ngày giữ đến khi nào
   - **Notes**: Ghi chú
6. Click **Lưu**
7. Booking Deposit được tạo với trạng thái `pending`
8. Phòng trạng thái chuyển sang `reserved`

**Xem chi tiết:**
- [Quản lý Booking Deposits](../manager/10-booking-deposits.md)
- [Môi giới Booking Deposits](../agent/09-booking-deposits.md)

### Bước 2: Quản lý Phê duyệt Đặt Cọc

**Người thực hiện:** Quản lý

**Các bước:**
1. Truy cập chi tiết Booking Deposit có trạng thái `pending`
2. Review thông tin deposit
3. Click **Approve** hoặc **Phê duyệt**
4. Booking Deposit trạng thái chuyển sang `approved`
5. Hệ thống tự động tạo Hóa đơn cho deposit:
   - Hóa đơn Loại: `deposit`
   - Số tiền = Deposit Số tiền
   - Trạng thái: `draft`

**Lưu ý**: 
- Chỉ Quản lý mới có thể approve deposit
- Môi giới chỉ có thể tạo deposit, không thể approve

### Bước 3: Phát hành Hóa đơn cho Deposit

**Người thực hiện:** Quản lý hoặc Môi giới

**Các bước:**
1. Truy cập Hóa đơn được tạo tự động cho deposit
2. Review thông tin hóa đơn
3. Click **Issue** hoặc **Phát hành**
4. Hóa đơn trạng thái chuyển sang `issued`
5. Hệ thống gửi thông báo cho Khách thuê (nếu là Khách thuê Người dùng)

### Bước 4: Khách thuê Thanh toán Cọc

**Người thực hiện:** Khách thuê

**Các bước:**
1. Khách thuê nhận thông báo về hóa đơn cọc
2. Khách thuê truy cập **Hóa đơn** → Chọn hóa đơn cọc
3. Khách thuê thanh toán hóa đơn (xem [Khách thuê Hóa đơn](../tenant/07-invoices.md))
4. Hóa đơn trạng thái chuyển sang `paid`
5. Môi giới hoặc Quản lý mark deposit đã thanh toán:
   - Truy cập chi tiết Booking Deposit
   - Click **Mark Đã thanh toán** hoặc **Đánh dấu đã thanh toán**
   - Link với Thanh toán
6. Booking Deposit trạng thái chuyển sang `paid`
7. Tiền cọc được giữ cho đến khi kết thúc hợp đồng thuê

**Lưu ý**: 
- Deposit được giữ trong hệ thống
- Không được sử dụng cho đến khi kết thúc hợp đồng thuê

### Bước 5: Tạo Hợp đồng thuê từ Booking Deposit

**Người thực hiện:** Môi giới hoặc Quản lý

**Các bước:**
1. Sau khi booking deposit đã đã thanh toán, tạo Hợp đồng thuê từ deposit:
   - Truy cập **Hợp đồng thuê** → **Tạo**
   - Chọn **Phòng** (phòng đã đặt cọc)
   - Chọn **Khách thuê** (người dùng đã convert từ Lead hoặc Khách thuê Người dùng)
   - Deposit Số tiền trong hợp đồng thuê có thể được set từ Booking Deposit đã đã thanh toán (hoặc set khác)
2. Hợp đồng thuê được tạo với deposit_amount (trong hợp đồng)
3. Booking Deposit được link với hợp đồng thuê (không phải chuyển thành deposit_refund)

**Lưu ý**: 
- Khi tạo hợp đồng thuê, deposit_amount trong hợp đồng thuê là tiền cọc trong hợp đồng thuê
- Booking Deposit và Deposit Số tiền trong Hợp đồng thuê là 2 khái niệm khác nhau
- Booking Deposit là tiền đặt cọc giữ chỗ (trước hợp đồng)
- Deposit Số tiền trong Hợp đồng thuê là tiền cọc trong hợp đồng (có thể = hoặc khác booking deposit)

### Bước 6: Hợp đồng thuê Kết thúc hoặc Bị Terminate

**Người thực hiện:** Hệ thống hoặc Quản lý

**Các bước:**
1. Hợp đồng thuê kết thúc tự nhiên (End Ngày) hoặc bị terminate
2. Hệ thống kiểm tra hợp đồng thuê có deposit_amount > 0 không
3. Quản lý/Agent tính toán hoàn tiền từ deposit trong hợp đồng (hợp đồng thuê.deposit_amount):
   - **Original Deposit Số tiền** = hợp đồng thuê.deposit_amount
   - **Deducted Số tiền** = 
     - Unpaid hóa đơn của hợp đồng thuê (nếu có)
     - Ticket costs đã charge to tenant_deposit (nếu có)
     - Damages (nếu có)
   - **Refund Số tiền** = Original Deposit Số tiền - Deducted Số tiền

**Lưu ý**: 
- Deposit Refund được tạo từ Hợp đồng thuê, không phải từ Booking Deposit
- Deposit Refund tính từ deposit trong hợp đồng thuê (hợp đồng thuê.deposit_amount)
- Nếu Refund Số tiền <= 0, không hoàn tiền hoặc cần thu thêm
- Nếu Refund Số tiền > 0, tạo Deposit Refund

### Bước 7: Tạo Deposit Refund (Từ Hợp đồng thuê)

**Người thực hiện:** Môi giới hoặc Quản lý

**Các bước:**
1. Sau khi hợp đồng thuê kết thúc hoặc bị terminate, tạo Deposit Refund:
   - Truy cập **Deposit Refunds** → **Tạo**
   - Chọn **Hợp đồng thuê** (bắt buộc) - Hợp đồng đã kết thúc hoặc bị terminate
   - Hệ thống tự động lấy thông tin từ hợp đồng thuê:
     - Phòng, Khách thuê (tự động từ hợp đồng thuê)
     - Original Deposit Số tiền (tự động từ hợp đồng thuê.deposit_amount)
   - Hệ thống tự động tính toán:
     - Original Deposit Số tiền: Số tiền cọc trong hợp đồng (từ hợp đồng thuê.deposit_amount)
     - Deducted Số tiền: Số tiền đã trừ (từ unpaid hóa đơn của hợp đồng thuê, ticket costs, damages)
     - Refund Số tiền: Số tiền hoàn lại = Original Deposit Số tiền - Deducted Số tiền
2. Điền thông tin:
   - **Refund Reason**: Lý do hoàn tiền
   - **Refund Method**: Phương thức thanh toán (cash, bank_transfer, wallet)
   - **Notes**: Ghi chú
3. Click **Lưu**
4. Deposit Refund được tạo với trạng thái `pending`
5. Hệ thống gửi thông báo cho Quản lý

**Xem chi tiết:**
- [Quản lý Deposit Refunds](../manager/11-deposit-refunds.md)
- [Môi giới Deposit Refunds](../agent/10-deposit-refunds.md)

### Bước 8: Quản lý Phê duyệt Hoàn Tiền

**Người thực hiện:** Quản lý

**Các bước:**
1. Truy cập chi tiết Deposit Refund có trạng thái `pending`
2. Review thông tin refund:
   - Deposit Số tiền
   - Deducted Số tiền
   - Refund Số tiền
   - Refund Reason
3. Click **Approve** hoặc **Phê duyệt**
4. Deposit Refund trạng thái chuyển sang `approved`
5. Hệ thống gửi thông báo cho Khách thuê

**Lưu ý**: 
- Quản lý phải review kỹ trước khi approve
- Đảm bảo Deducted Số tiền được tính đúng

### Bước 9: Thanh toán Hoàn Tiền

**Người thực hiện:** Quản lý

**Các bước:**
1. Sau khi approve refund, Quản lý xử lý thanh toán hoàn tiền:
   
#### 9.1. Thanh toán bằng Bank Transfer

1. Chọn **Bank Transfer**
2. Chuyển khoản vào tài khoản ngân hàng của Khách thuê
3. Nhập Transaction Reference
4. Mark thanh toán success
5. Hệ thống tạo Cash Outflow record

#### 9.2. Thanh toán bằng Cash

1. Chọn **Cash**
2. Hoàn tiền mặt cho Khách thuê
3. Mark thanh toán success
4. Hệ thống tạo Cash Outflow record

#### 9.3. Thanh toán qua SePay

1. Chọn **SePay**
2. Chuyển khoản qua SePay
3. Nhập Transaction Reference
4. Chờ SePay webhook callback
5. Hệ thống tự động cập nhật thanh toán trạng thái
6. Hệ thống tạo Cash Outflow record

2. Sau khi thanh toán thành công:
   - Click **Mark Đã thanh toán** hoặc **Đánh dấu đã thanh toán**
   - Deposit Refund trạng thái chuyển sang `paid`
   - Hệ thống tạo Company Hóa đơn (loại = deposit_refund)
   - Hệ thống tạo Cash Outflow record
   - Hệ thống gửi thông báo cho Khách thuê

## Trạng thái và Chuyển đổi

### Booking Deposit Trạng thái Flow

```
pending → approved → paid → refunded
         ↓           ↓
      rejected   expired
```

- **đang chờ**: Đặt cọc đã được tạo, chờ Quản lý phê duyệt
- **đã phê duyệt**: Quản lý đã phê duyệt, chờ thanh toán
- **đã thanh toán**: Đã thanh toán cọc, đang giữ tiền cọc
- **expired**: Đã hết hạn (quá Hold Until và chưa đã thanh toán)
- **refunded**: Đã hoàn tiền cọc
- **từ chối**: Quản lý đã từ chối

### Deposit Refund Trạng thái Flow

```
pending → approved → paid
         ↓
      rejected/cancelled
```/cancelled
```

- **đang chờ**: Hoàn tiền đã được tạo, chờ Quản lý phê duyệt
- **đã phê duyệt**: Quản lý đã phê duyệt, chờ thanh toán
- **đã thanh toán**: Đã thanh toán hoàn tiền
- **từ chối**: Quản lý đã từ chối
- **cancelled**: Đã hủy hoàn tiền

## Ràng buộc

1. **Phòng phải available hoặc reserved khi tạo deposit**
   - Phòng trạng thái phải là `available` hoặc `reserved`
   - Không thể đặt cọc cho phòng đã `occupied`

2. **Deposit phải được approve trước khi mark đã thanh toán**
   - Chỉ Quản lý mới có thể approve deposit
   - Deposit không thể mark đã thanh toán nếu chưa approve

3. **Deposit tự động expire nếu quá Hold Until**
   - Nếu quá Hold Until và chưa đã thanh toán, deposit tự động expire
   - Phòng được release về trạng thái `available`

4. **Deposit Refund được tạo từ Hợp đồng thuê, không phải Booking Deposit**
   - Deposit Refund liên quan đến deposit trong hợp đồng thuê (hợp đồng thuê.deposit_amount)
   - Original Deposit Số tiền = hợp đồng thuê.deposit_amount
   - Deducted Số tiền bao gồm:
     - Unpaid hóa đơn của hợp đồng thuê
     - Ticket costs đã charge to tenant_deposit
     - Damages (nếu có)
   - Refund Số tiền = Original Deposit Số tiền - Deducted Số tiền
   - Refund Số tiền không thể âm

5. **Deposit Refund phải được approve trước khi mark đã thanh toán**
   - Chỉ Quản lý mới có thể approve refund
   - Refund không thể mark đã thanh toán nếu chưa approve

## Ví dụ

### Ví dụ hoàn chỉnh

**Booking Deposit:**
- Phòng: P101
- Khách thuê: Nguyễn Văn A
- Số tiền: 20,000,000 VND
- Deposit Loại: `booking`
- Hold Until: 2025-01-22

**Bước 1:** Môi giới tạo Booking Deposit với thông tin trên

**Bước 2:** Quản lý approve deposit → trạng thái = `approved`

**Bước 3:** Quản lý issue Hóa đơn cho deposit → trạng thái = `issued`

**Bước 4:** Khách thuê thanh toán hóa đơn → trạng thái = `paid`
- Môi giới mark deposit đã thanh toán → trạng thái = `paid`

**Bước 5:** Môi giới tạo Hợp đồng thuê từ deposit:
- Deposit Số tiền của Hợp đồng thuê = 20,000,000 VND (từ deposit)

**Bước 6:** Hợp đồng thuê kết thúc sau 12 tháng

**Bước 7:** Quản lý tính toán hoàn tiền từ hợp đồng thuê:
- Original Deposit Số tiền (từ hợp đồng thuê.deposit_amount): 20,000,000 VND
- Deducted Số tiền:
  - Unpaid hóa đơn (của hợp đồng thuê): 5,000,000 VND
  - Ticket costs (charge to tenant_deposit): 1,000,000 VND
  - Tổng Deducted: 6,000,000 VND
- Refund Số tiền: 20,000,000 - 6,000,000 = 14,000,000 VND

**Bước 8:** Môi giới tạo Deposit Refund từ Hợp đồng thuê:
- Chọn Hợp đồng thuê đã kết thúc
- Original Deposit Số tiền: 20,000,000 VND (tự động từ hợp đồng thuê)
- Refund Số tiền: 14,000,000 VND

**Bước 9:** Quản lý approve refund → trạng thái = `approved`

**Bước 10:** Quản lý thanh toán hoàn tiền:
- Bank Transfer: 14,000,000 VND vào tài khoản Khách thuê
- Mark đã thanh toán → trạng thái = `paid`
- Hệ thống tạo Company Hóa đơn (loại = deposit_refund)

## Lưu ý

1. **Deposit Hold Period**
   - Deposit được giữ cho đến khi kết thúc hợp đồng thuê
   - Không được sử dụng cho đến khi hoàn tiền

2. **Deducted Số tiền Calculation**
   - Tính toán cẩn thận để đảm bảo chính xác
   - Bao gồm tất cả các khoản nợ từ khách thuê

3. **Refund Số tiền**
   - Refund Số tiền không thể âm
   - Nếu Refund Số tiền <= 0, không hoàn tiền hoặc cần thu thêm

4. **Thanh toán Processing**
   - Hoàn tiền có thể thực hiện bằng nhiều phương thức
   - Tạo Cash Outflow để track chi phí

---

**Xem thêm:**
- [Quản lý Booking Deposits](../manager/10-booking-deposits.md)
- [Quản lý Deposit Refunds](../manager/11-deposit-refunds.md)
- [Workflow Lead to Hợp đồng thuê](./01-lead-to-lease.md)

**Cập nhật**: 2025-11-02

