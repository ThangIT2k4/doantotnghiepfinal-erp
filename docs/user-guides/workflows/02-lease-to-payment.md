# QUY TRÌNH TỪ LEASE ĐẾN THANH TOÁN

## Tổng quan

Quy trình này mô tả các bước từ khi tạo Hợp đồng thuê (hợp đồng thuê) đến khi Khách thuê thanh toán hóa đơn thành công.

## Workflow

### Bước 1: Tạo Hợp đồng Thuê (Hợp đồng thuê)

**Người thực hiện:** Môi giới hoặc Quản lý

**Các bước:**
1. Truy cập **Hợp đồng thuê** → **Tạo**
2. Chọn **Phòng** (phòng cần cho thuê)
3. Chọn **Khách thuê** (người thuê)
4. Chọn **Môi giới** (người quản lý hợp đồng)
5. Điền thông tin:
   - Start Ngày, End Ngày: Ngày bắt đầu và kết thúc
   - Rent Số tiền: Tiền thuê
   - Deposit Số tiền: Tiền cọc
   - Billing Day: Ngày tạo hóa đơn (1-28)
   - Thanh toán Cycle: Chu kỳ thanh toán (hàng tháng, hàng quý, hàng năm, tùy chỉnh)
   - Thanh toán Day: Ngày thanh toán (1-31)
   - Tùy chỉnh Months: Số tháng (nếu Thanh toán Cycle = tùy chỉnh)
   - Services: Dịch vụ (điện, nước, internet, etc.)
   - Residents: Người ở cùng (nếu có)
6. Click **Lưu**
7. Hợp đồng thuê được tạo với trạng thái `active`
8. Phòng trạng thái chuyển sang `occupied`

**Xem chi tiết:**
- [Quản lý Hợp đồng thuê](../manager/05-leases.md)
- [Môi giới Hợp đồng thuê](../agent/05-leases.md)

### Bước 2: Tự động tạo Hóa đơn (Auto-tạo Hóa đơn)

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Hệ thống tự động tạo hóa đơn theo Thanh toán Cycle của Hợp đồng thuê
2. Hóa đơn được tạo vào Billing Day mỗi chu kỳ:
   - Hàng tháng: Mỗi tháng 1 hóa đơn
   - Hàng quý: Mỗi quý 1 hóa đơn
   - Hàng năm: Mỗi năm 1 hóa đơn
   - Tùy chỉnh: Theo số tháng đã cấu hình
3. Hóa đơn có trạng thái `draft`
4. Hóa đơn chứa các items:
   - Rent: Tiền thuê theo chu kỳ
   - Services: Dịch vụ (nếu có trong hợp đồng thuê)
   - Meter Readings: Chỉ số công tơ (nếu có)
   - Other Costs: Chi phí khác (nếu có)

**Lưu ý**: 
- Hóa đơn được tạo tự động, không cần thao tác thủ công
- Hóa đơn có trạng thái `draft` cho đến khi Quản lý/Agent issue

### Bước 3: Phát hành Hóa đơn (Issue Hóa đơn)

**Người thực hiện:** Quản lý hoặc Môi giới

**Các bước:**
1. Truy cập **Hóa đơn** → Tìm hóa đơn có trạng thái `draft`
2. Review thông tin hóa đơn:
   - Items: Kiểm tra các khoản phí
   - Số tiền: Kiểm tra tổng tiền
   - Đến hạn Ngày: Kiểm tra hạn thanh toán
3. Click **Issue** hoặc **Phát hành**
4. Hóa đơn trạng thái chuyển sang `issued`
5. Hệ thống gửi thông báo cho Khách thuê (email/in-app)
6. Hệ thống gửi thông báo cho Môi giới và Quản lý

**Xem chi tiết:**
- [Quản lý Hóa đơn](../manager/12-invoices.md)
- [Môi giới Hóa đơn](../agent/11-invoices.md)

### Bước 4: Khách thuê Thanh toán (Khách thuê Thanh toán)

**Người thực hiện:** Khách thuê

**Các bước:**
1. Khách thuê nhận thông báo về hóa đơn mới
2. Khách thuê truy cập **Hóa đơn** → Chọn hóa đơn cần thanh toán
3. Khách thuê click **Pay** hoặc **Thanh toán**
4. Khách thuê chọn phương thức thanh toán:
   - **Cash**: Tiền mặt
   - **Bank Transfer**: Chuyển khoản ngân hàng
   - **SePay**: Thanh toán qua SePay (QR code, chuyển khoản)
5. Khách thuê nhập số tiền (<= remaining số tiền)
6. Khách thuê click **Pay**
7. Hệ thống tạo thanh toán:
   - Cash/Bank Transfer: trạng thái `success` (cần verify)
   - SePay: trạng thái `pending`
8. Hệ thống cập nhật hóa đơn trạng thái

**Xem chi tiết:**
- [Khách thuê Hóa đơn](../tenant/07-invoices.md)

### Bước 5: Thanh toán Thành công (Thanh toán Success)

**Người thực hiện:** Hệ thống (tự động) hoặc Môi giới/Manager (verify)

**Các bước:**

#### 5.1. Thanh toán với Cash/Bank Transfer

1. Môi giới hoặc Quản lý verify thanh toán
2. Môi giới hoặc Quản lý mark thanh toán as đã thanh toán
3. Thanh toán trạng thái = `success`
4. Hóa đơn trạng thái được cập nhật:
   - `paid`: Nếu thanh toán đủ
   - `partially_paid`: Nếu thanh toán một phần
5. Hệ thống gửi thông báo cho Khách thuê

#### 5.2. Thanh toán với SePay

1. Khách thuê quét QR code hoặc chuyển khoản theo thông tin SePay
2. SePay xử lý thanh toán
3. SePay gửi webhook callback cho hệ thống
4. Hệ thống tự động cập nhật thanh toán trạng thái:
   - `success`: Nếu thanh toán thành công
   - `failed`: Nếu thanh toán thất bại
5. Hệ thống cập nhật hóa đơn trạng thái khi thanh toán thành công
6. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Thanh toán với SePay có thể mất vài phút để xử lý
- Hệ thống tự động cập nhật khi nhận được webhook

### Bước 6: Cập nhật Trạng thái Hóa đơn (Cập nhật Hóa đơn Trạng thái)

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Sau khi thanh toán thành công, hệ thống tự động cập nhật hóa đơn:
   - **Đã thanh toán Số tiền**: Tăng lên theo số tiền đã thanh toán
   - **Remaining Số tiền**: Giảm đi theo số tiền đã thanh toán
   - **Trạng thái**: 
     - `paid`: Nếu Đã thanh toán Số tiền = Tổng Số tiền
     - `partially_paid`: Nếu Đã thanh toán Số tiền < Tổng Số tiền
     - `overdue`: Nếu quá Đến hạn Ngày và chưa thanh toán đủ
2. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Hóa đơn trạng thái được cập nhật tự động
- Có thể theo dõi trạng thái trong Hóa đơn

### Bước 7: Tính toán Hoa hồng (Commission Calculation) - Nếu có

**Người thực hiện:** Hệ thống (tự động)

**Các bước:**
1. Sau khi hóa đơn được thanh toán đủ, hệ thống kiểm tra Commission Policies
2. Nếu có policy với trigger `invoice_paid`, hệ thống tạo Commission Event
3. Hệ thống tính toán commission theo policy:
   - Percent: % từ hóa đơn số tiền
   - Flat: Số tiền cố định
   - Tiered: Theo bậc thang
4. Hệ thống áp dụng Apply Limit Months (nếu có):
   - Chỉ tính hoa hồng cho N tháng đầu tiên
   - Từ tháng thứ (N+1) trở đi, không tính hoa hồng
5. Commission Event được tạo với trạng thái `pending`
6. Quản lý approve commission → trạng thái `approved`
7. Quản lý mark đã thanh toán → trạng thái `paid`

**Xem chi tiết:**
- [Workflow Commission Calculation](./04-commission-calculation.md)

## Trạng thái và Chuyển đổi

### Hợp đồng thuê Trạng thái Flow

```
active → terminated/expired
```/expired
```

### Hóa đơn Trạng thái Flow

```
draft → issued → paid/overdue
          ↓          ↓
    partially_paid  cancelled
```/overdue
          ↓          ↓
    partially_paid  cancelled
```

### Thanh toán Trạng thái Flow

```
pending → success/failed
```/failed
```

### Commission Event Trạng thái Flow (Nếu có)

```
pending → approved → paid
```

## Ràng buộc

1. **Phòng chỉ có 1 hợp đồng thuê hoạt động tại một thời điểm**
   - Phải terminate hoặc chờ hết hạn hợp đồng thuê hiện tại
   - Hoặc chọn phòng khác

2. **Hóa đơn phải có ít nhất 1 item**
   - Hóa đơn không thể tạo nếu không có items
   - Phải có ít nhất 1 item (rent, service, meter, etc.)

3. **Thanh toán số tiền không được vượt quá hóa đơn remaining số tiền**
   - Số tiền phải <= Hóa đơn Remaining Số tiền
   - Có thể thanh toán nhiều lần (partial thanh toán)

4. **Hóa đơn phải được issue trước khi thanh toán**
   - Chỉ có thể thanh toán hóa đơn có trạng thái `issued`, `overdue`, hoặc `partially_paid`
   - Không thể thanh toán hóa đơn có trạng thái `draft` hoặc `cancelled`

5. **Commission Policy Apply Limit Months**
   - Nếu policy có Apply Limit Months = N, chỉ tính hoa hồng cho N tháng đầu tiên
   - Từ tháng thứ (N+1) trở đi, không tính hoa hồng

## Ví dụ

### Ví dụ hoàn chỉnh

**Hợp đồng thuê:** 
- Phòng: P101
- Khách thuê: Nguyễn Văn A
- Start: 2025-01-01, End: 2025-12-31
- Rent: 10,000,000 VND/tháng
- Thanh toán Cycle: hàng tháng
- Thanh toán Day: 5
- Billing Day: 1

**Bước 1:** Môi giới tạo Hợp đồng thuê với thông tin trên

**Bước 2:** Hệ thống tự động tạo Hóa đơn vào Billing Day (ngày 1):
- Hóa đơn HD-202502-0001: Số tiền = 10,000,000 VND, Đến hạn Ngày = 2025-02-05, Trạng thái = `draft`

**Bước 3:** Quản lý issue Hóa đơn:
- Hóa đơn trạng thái = `issued`
- Hệ thống gửi thông báo cho Khách thuê

**Bước 4:** Khách thuê thanh toán:
- Chọn SePay
- Số tiền: 10,000,000 VND
- Quét QR code và thanh toán

**Bước 5:** SePay xử lý và gửi webhook:
- Thanh toán trạng thái = `success`
- Hóa đơn trạng thái = `paid`

**Bước 6:** Hệ thống cập nhật hóa đơn trạng thái = `paid`

**Bước 7:** Hệ thống tính hoa hồng (nếu có policy):
- Commission Event được tạo với số tiền = 500,000 VND (5% của 10,000,000 VND)
- Quản lý approve và mark đã thanh toán

## Lưu ý

1. **Auto-tạo Hóa đơn**
   - Hóa đơn được tạo tự động theo Thanh toán Cycle
   - Không cần thao tác thủ công

2. **Partial Thanh toán**
   - Có thể thanh toán nhiều lần cho một hóa đơn
   - Hóa đơn trạng thái sẽ là `partially_paid` nếu chưa thanh toán đủ

3. **Thanh toán Methods**
   - Cash: Thanh toán tiền mặt (cần verify)
   - Bank Transfer: Chuyển khoản (cần verify)
   - SePay: Thanh toán qua SePay (tự động verify qua webhook)

4. **Commission Calculation**
   - Hoa hồng chỉ được tính khi hóa đơn được thanh toán đủ
   - Apply Limit Months giới hạn số tháng tính hoa hồng

---

**Xem thêm:**
- [Quản lý Hợp đồng thuê](../manager/05-leases.md)
- [Quản lý Hóa đơn](../manager/12-invoices.md)
- [Khách thuê Hóa đơn](../tenant/07-invoices.md)
- [Workflow Commission Calculation](./04-commission-calculation.md)

**Cập nhật**: 2025-11-02

