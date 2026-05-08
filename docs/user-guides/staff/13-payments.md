# QUẢN LÝ THANH TOÁN - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý thanh toán (thanh toán) trong tổ chức, bao gồm xem, tạo, cập nhật, xóa, mark đã thanh toán, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả thanh toán trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Tạo thanh toán: Cần capability `payment.create`
  - Cập nhật thanh toán: Cần capability `payment.update`
  - Xem tất cả thanh toán: Cần capability `payment.view` hoặc `payment.view_all`
  - Chỉ xem thanh toán của hóa đơn từ hợp đồng thuê được gán: Có capability `payment.view_own` (mặc định)
  - Xóa thanh toán: Cần capability `payment.delete`
  - Mark Đã thanh toán: Cần capability `payment.update` (chỉ cho thanh toán đang chờ)

**Route**: `/staff/payments`

## Các bước thực hiện

### 1. Xem danh sách Thanh toán

1. Truy cập **Thanh toán** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả thanh toán trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (đang chờ, success, failed, refunded)
   - Thanh toán Method (từ bảng payment_methods: cash, bank_transfer, sepay, other)
   - Hóa đơn (nếu có nhiều hóa đơn)
   - Khách thuê (nếu có nhiều khách thuê)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo paid_at, số tiền, trạng thái

### 2. Xem chi tiết Thanh toán

1. Click vào thanh toán trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Thanh toán ID: Mã thanh toán
     - Hóa đơn: Hóa đơn liên quan
     - Hóa đơn Number: Số hóa đơn
     - Khách thuê hoặc Lead: Người thanh toán (có thể là khách thuê người dùng hoặc lead)
     - Số tiền: Số tiền thanh toán
     - Đã thanh toán At: Ngày giờ thanh toán
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin thanh toán:**
     - Thanh toán Method: Phương thức thanh toán
     - Transaction Reference: Số tham chiếu giao dịch (nếu có)
     - Bank Account: Thông tin tài khoản ngân hàng (nếu SePay)
     - QR Code: Mã QR thanh toán (nếu SePay và đang chờ)
     - Proof of Thanh toán: Ảnh chụp biên lai (nếu có)
   - **Thông tin khác:**
     - Note: Ghi chú
     - Created At: Ngày tạo thanh toán
     - Updated At: Ngày cập nhật thanh toán
     - Verified By: Người verify thanh toán (nếu có)
     - Verified At: Ngày verify thanh toán (nếu có)

### 3. Tạo Thanh toán thủ công

1. Click **Tạo Thanh toán** hoặc **+ New**
2. Điền thông tin:
   - **Hóa đơn** (bắt buộc): Chọn hóa đơn cần thanh toán
   - **Thanh toán Method** (bắt buộc): Chọn từ dropdown (cash, bank_transfer, sepay, other)
   - **Số tiền** (bắt buộc): Số tiền thanh toán (<= hóa đơn remaining số tiền)
   - **Đã thanh toán At** (bắt buộc): Ngày giờ thanh toán
   - **Payer** (bắt buộc): Chọn Khách thuê Người dùng hoặc Lead (phải có ít nhất một trong hai)
   - **Transaction Reference** (tùy chọn): Số tham chiếu giao dịch
   - **Proof of Thanh toán** (tùy chọn): Upload ảnh chụp biên lai
   - **Note** (tùy chọn): Ghi chú
   - **Trạng thái** (tự động): 
     - `success` (nếu cash/bank_transfer, có thể mark đã thanh toán ngay)
     - `pending` (nếu SePay, chờ webhook callback)
3. Click **Lưu**
4. Hệ thống tạo thanh toán và cập nhật hóa đơn trạng thái
5. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

### 4. Mark Thanh toán as Đã thanh toán (Đánh dấu Đã Thanh toán)

1. Truy cập chi tiết thanh toán có trạng thái `pending` (thường là SePay thanh toán chưa nhận được webhook)
2. Review thông tin thanh toán:
   - Số tiền: Kiểm tra số tiền
   - Proof of Thanh toán: Kiểm tra ảnh chụp biên lai (nếu có)
   - Transaction Reference: Kiểm tra số tham chiếu (nếu có)
3. Click **Mark as Đã thanh toán** hoặc **Đánh dấu đã thanh toán**
4. Hệ thống cập nhật thanh toán trạng thái = `success`
5. Hệ thống tự động cập nhật hóa đơn trạng thái (đã thanh toán nếu thanh toán đủ, hoặc giữ issued/overdue nếu chưa đủ)
6. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Chỉ cần mark đã thanh toán cho thanh toán với trạng thái `pending` (thường là SePay chưa nhận webhook)
- Thanh toán với Cash/Bank Transfer thường được tạo với trạng thái `success` ngay
- Thanh toán với SePay được tự động cập nhật qua webhook, nhưng có thể mark đã thanh toán thủ công nếu cần


### 5. Cập nhật Thanh toán

1. Truy cập chi tiết thanh toán cần cập nhật
2. Click **Chỉnh sửa** (chỉ khi trạng thái = `pending` hoặc `failed`)
3. Cập nhật thông tin:
   - Số tiền (nếu chưa success)
   - Transaction Reference
   - Proof of Thanh toán
   - Note
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Chỉ có thể cập nhật thanh toán có trạng thái `pending` hoặc `failed`
- Không thể cập nhật thanh toán đã `success` hoặc `refunded`

### 6. Xóa Thanh toán

1. Truy cập chi tiết thanh toán cần xóa
2. Click **Xóa** (chỉ khi trạng thái = `pending` hoặc `failed`)
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa thanh toán
5. Hóa đơn trạng thái được cập nhật lại (nếu thanh toán đã được áp dụng)

**Lưu ý**: 
- Chỉ có thể xóa thanh toán có trạng thái `pending` hoặc `failed`
- Không thể xóa thanh toán đã `success` hoặc `refunded`

### 7. Xem Thống kê

1. Truy cập **Thanh toán** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Thanh toán by Trạng thái: Phân bố theo trạng thái
   - Thanh toán by Method: Phân bố theo phương thức
   - Thanh toán by Period: Phân bố theo thời gian
   - Tổng Đã thanh toán: Tổng số tiền đã thanh toán
   - Outstanding Số tiền: Tổng số tiền còn nợ
   - AR Aging: Phân tích công nợ theo thời gian

## Ràng buộc và điều kiện

### Validation Rules

- **Hóa đơn**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
  - Phải có trạng thái `issued` hoặc `overdue` (không có trạng thái `partially_paid`, hệ thống tự tính remaining số tiền)
- **Thanh toán Method**: 
  - Bắt buộc
  - Phải tồn tại trong bảng payment_methods (key_code: cash, bank_transfer, sepay, other)
- **Payer**: 
  - Bắt buộc
  - Phải có ít nhất một trong hai: `payer_user_id` (khách thuê) hoặc `lead_id`
- **Số tiền**: 
  - Bắt buộc
  - Phải > 0
  - Phải <= Hóa đơn Remaining Số tiền
- **Đã thanh toán At**: 
  - Bắt buộc
  - Phải là datetime hợp lệ
- **Transaction Reference**: 
  - Tùy chọn
  - Phải là số hoặc chuỗi (nếu có)

### Business Rules

1. **Thanh toán Số tiền không được vượt quá Hóa đơn Remaining Số tiền**
   - Số tiền phải <= Hóa đơn Remaining Số tiền
   - Có thể thanh toán nhiều lần (partial thanh toán)

2. **Thanh toán Trạng thái**
   - `pending`: Thanh toán đang chờ xử lý (SePay, chờ webhook callback)
   - `success`: Thanh toán thành công
   - `failed`: Thanh toán thất bại
   - `refunded`: Thanh toán đã được hoàn tiền

3. **SePay Thanh toán Flow**
   - Thanh toán với SePay được tạo với trạng thái `pending`
   - SePay gửi webhook callback về hệ thống
   - Hệ thống tự động cập nhật thanh toán trạng thái = `success` hoặc `failed`
   - Có thể mark đã thanh toán thủ công nếu webhook bị delay

4. **Partial Thanh toán**
   - Có thể thanh toán nhiều lần cho một hóa đơn
   - Hệ thống tự động tính remaining số tiền = total_amount - paid_amount
   - Hóa đơn trạng thái sẽ là `issued` hoặc `overdue` nếu chưa thanh toán đủ
   - Hóa đơn trạng thái sẽ là `paid` tự động khi total_paid >= total_amount

## Trạng thái và Workflow

### Thanh toán Trạng thái Flow

```
pending → success/failed
success → refunded
```/failed
success → refunded
```

- **đang chờ**: Thanh toán đang chờ xử lý (SePay, chờ webhook callback)
- **success**: Thanh toán thành công
- **failed**: Thanh toán thất bại
- **refunded**: Thanh toán đã được hoàn tiền

### Workflow Tạo Thanh toán

1. Quản lý hoặc Môi giới (có quyền) tạo thanh toán thủ công
2. Điền thông tin: Hóa đơn, Thanh toán Method, Số tiền, Đã thanh toán At, Payer (Khách thuê hoặc Lead)
3. Upload Proof of Thanh toán (nếu có)
4. Click Lưu
5. Hệ thống tạo thanh toán:
   - Cash/Bank Transfer: trạng thái `success` (có thể mark đã thanh toán ngay)
   - SePay: trạng thái `pending` (chờ webhook callback)
6. Hệ thống tự động cập nhật hóa đơn paid_amount và trạng thái (đã thanh toán nếu đủ, hoặc giữ issued/overdue nếu chưa đủ)
7. Nếu SePay: Hệ thống nhận webhook và tự động cập nhật thanh toán trạng thái
8. Có thể mark đã thanh toán thủ công nếu SePay webhook bị delay

## Ví dụ

### Ví dụ 1: Tạo Thanh toán thủ công (Cash)

**Thông tin thanh toán:**
- Hóa đơn: HD32025110001
- Thanh toán Method: `cash`
- Số tiền: 10,000,000 VND
- Đã thanh toán At: 2025-01-15 10:00
- Payer: Khách thuê Người dùng (Nguyễn Văn A)
- Trạng thái: `success`

**Các bước:**
1. Truy cập Thanh toán
2. Click **Tạo Thanh toán**
3. Chọn Hóa đơn: HD32025110001
4. Chọn Thanh toán Method: `cash`
5. Nhập Số tiền: 10,000,000 VND
6. Nhập Đã thanh toán At: 2025-01-15 10:00
7. Chọn Payer: Khách thuê Người dùng (Nguyễn Văn A)
8. Click **Lưu**
9. Thanh toán được tạo với trạng thái `success`
10. Hóa đơn tự động cập nhật paid_amount và trạng thái (đã thanh toán nếu đủ)

### Ví dụ 2: SePay Thanh toán với Webhook

**Kịch bản:** Khách thuê thanh toán qua SePay

**Các bước:**
1. Khách thuê chọn thanh toán SePay từ hóa đơn
2. Hệ thống tạo thanh toán với trạng thái `pending`
3. Khách thuê scan QR code hoặc chuyển khoản
4. SePay gửi webhook callback về hệ thống
5. Hệ thống tự động cập nhật thanh toán trạng thái = `success`
6. Hóa đơn tự động cập nhật paid_amount và trạng thái

## Lưu ý

1. **SePay Thanh toán**
   - Thanh toán với SePay có thể mất vài phút để xử lý (chờ webhook)
   - Có thể mark đã thanh toán thủ công nếu webhook bị delay
   - Kiểm tra Webhook Logs trong `/staff/webhook-logs` nếu cần

2. **Partial Thanh toán**
   - Có thể thanh toán nhiều lần cho một hóa đơn
   - Theo dõi remaining số tiền để đảm bảo thanh toán đủ

3. **Proof of Thanh toán**
   - Upload proof of thanh toán để làm bằng chứng
   - Giúp verify nhanh hơn

4. **Thanh toán Payer**
   - Thanh toán phải có ít nhất một trong hai: Khách thuê Người dùng hoặc Lead
   - Khách thuê Người dùng: Nếu khách thuê đã có tài khoản
   - Lead: Nếu chưa có tài khoản khách thuê

## Troubleshooting

### Không thể tạo thanh toán

1. Kiểm tra Hóa đơn có trạng thái phù hợp không (`issued`, `overdue`)
2. Kiểm tra Số tiền có <= Hóa đơn Remaining Số tiền không
3. Kiểm tra đã chọn Payer (Khách thuê Người dùng hoặc Lead) chưa
4. Kiểm tra tất cả các trường bắt buộc đã điền chưa
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Thanh toán đang chờ quá lâu (SePay)

1. Kiểm tra SePay connection có ổn định không
2. Kiểm tra webhook có được gửi không (xem Webhook Logs)
3. Kiểm tra thanh toán trong SePay system
4. Có thể mark đã thanh toán thủ công nếu đã xác nhận thanh toán
5. Sử dụng "Check Đang chờ Thanh toán" trong SePay Management
6. Liên hệ hỗ trợ nếu thanh toán quá lâu (quá 30 phút)

---

**Xem thêm:**
- [Nhân viên Hóa đơn](./12-invoices.md)
- [Nhân viên Sepay Management](./33-sepay.md)
- [Khách thuê Thanh toán](../tenant/08-payments.md)
- [Workflow Hợp đồng thuê to Thanh toán](../workflows/02-lease-to-payment.md)

**Cập nhật: 2025-01-XX

