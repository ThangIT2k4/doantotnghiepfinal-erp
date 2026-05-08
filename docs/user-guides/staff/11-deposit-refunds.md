# QUẢN LÝ HOÀN TIỀN CỌC - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý hoàn tiền cọc (deposit refunds) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, approve, mark đã thanh toán, hủy, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả deposit refunds trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `contract.access`
  - Tạo deposit refund: Cần capability `contract.deposit_refund.create`
  - Cập nhật deposit refund: Cần capability `contract.deposit_refund.update`
  - Xem tất cả deposit refunds: Cần capability `contract.deposit_refund.view` hoặc `contract.deposit_refund.view_all`
  - Chỉ xem deposit refunds từ hợp đồng thuê được gán: Có capability `contract.deposit_refund.view_own` (mặc định)
  - Approve/Mark Đã thanh toán: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/deposit-refunds`

## Các bước thực hiện

### 1. Xem danh sách Deposit Refunds

1. Truy cập **Deposit Refunds** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả deposit refunds trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (đang chờ, đã phê duyệt, đã thanh toán, từ chối, cancelled)
   - Booking Deposit (nếu có nhiều deposits)
   - Khách thuê (nếu có nhiều khách thuê)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo created_at, refund_amount, trạng thái

### 2. Xem chi tiết Deposit Refund

1. Click vào refund trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Refund ID: Mã hoàn tiền
     - Hợp đồng thuê: Hợp đồng thuê liên quan (bắt buộc)
     - Khách thuê: Người thuê (tự động từ hợp đồng thuê)
     - Phòng: Phòng/Căn (tự động từ hợp đồng thuê)
     - Môi giới: Môi giới xử lý (nếu có)
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin tài chính:**
     - Original Deposit Số tiền: Số tiền cọc trong hợp đồng (tự động từ hợp đồng thuê.deposit_amount)
     - Deducted Số tiền: Số tiền đã trừ:
       - Unpaid Hóa đơn: Hóa đơn chưa thanh toán của hợp đồng thuê
       - Ticket Costs: Chi phí tickets đã charge to tenant_deposit
       - Damages: Chi phí sửa chữa (nếu có)
     - Refund Số tiền: Số tiền hoàn lại = Original Deposit Số tiền - Deducted Số tiền
   - **Thông tin khác:**
     - Refund Reason: Lý do hoàn tiền
     - Notes: Ghi chú
     - Created At: Ngày tạo refund
     - Đã phê duyệt At: Ngày approve (nếu có)
     - Đã thanh toán At: Ngày thanh toán (nếu có)
     - Thanh toán Method: Phương thức thanh toán (nếu có)

### 3. Tạo Deposit Refund mới

1. Sau khi hợp đồng thuê kết thúc hoặc bị terminate, truy cập **Deposit Refunds** → **Tạo**
2. Chọn **Hợp đồng thuê** (bắt buộc) - Hợp đồng đã kết thúc hoặc bị terminate
3. Hệ thống tự động lấy thông tin từ hợp đồng thuê:
   - Phòng, Khách thuê (tự động từ hợp đồng thuê)
   - Original Deposit Số tiền (tự động từ hợp đồng thuê.deposit_amount)
4. Hệ thống tự động tính toán:
   - **Original Deposit Số tiền**: Số tiền cọc trong hợp đồng (từ hợp đồng thuê.deposit_amount)
   - **Deducted Số tiền**: Số tiền đã trừ (tự động tính):
     - Unpaid Hóa đơn: Tổng số tiền các hóa đơn chưa thanh toán của hợp đồng thuê (trạng thái issued/overdue)
     - Ticket Costs: Tổng số tiền các ticket logs đã charge to tenant_deposit
     - Damages: Chi phí sửa chữa (nếu có, nhập thủ công)
     - Tổng: Tổng Deducted Số tiền
   - **Refund Số tiền**: Số tiền hoàn lại = Original Deposit Số tiền - Deducted Số tiền
4. Điền thông tin:
   - **Refund Reason** (bắt buộc): Lý do hoàn tiền (ví dụ: "Hợp đồng thuê kết thúc")
   - **Notes** (tùy chọn): Ghi chú
   - **Trạng thái** (tự động): `pending`
5. Click **Lưu**
6. Deposit Refund được tạo với trạng thái `pending`
7. Hệ thống gửi thông báo cho Quản lý

**Lưu ý**: 
- Refund Số tiền không thể âm
- Nếu Refund Số tiền <= 0, không hoàn tiền hoặc cần thu thêm

### 4. Approve Deposit Refund (Phê duyệt Hoàn Tiền)

1. Truy cập chi tiết refund có trạng thái `pending`
2. Review thông tin refund:
   - Deposit Số tiền
   - Deducted Số tiền (kiểm tra tính đúng)
   - Refund Số tiền
   - Refund Reason
3. Click **Approve** hoặc **Phê duyệt**
4. Deposit Refund trạng thái chuyển sang `approved`
5. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Chỉ Quản lý mới có thể approve refund
- Phải review kỹ Deducted Số tiền trước khi approve

### 5. Reject Deposit Refund (Từ chối Hoàn Tiền)

1. Truy cập chi tiết refund có trạng thái `pending`
2. Click **Reject** hoặc **Từ chối**
3. (Tùy chọn) Nhập lý do từ chối
4. Xác nhận reject
5. Deposit Refund trạng thái chuyển sang `rejected`
6. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Chỉ Quản lý mới có thể reject refund
- Reject refund khi thông tin không đúng hoặc cần điều chỉnh

### 6. Mark Đã thanh toán (Đánh dấu Đã Thanh toán)

1. Sau khi approve refund, Quản lý xử lý thanh toán hoàn tiền
2. Truy cập chi tiết refund có trạng thái `approved`
3. Click **Mark Đã thanh toán** hoặc **Đánh dấu đã thanh toán**
4. Chọn phương thức thanh toán:
   - **Bank Transfer**: Chuyển khoản ngân hàng
   - **Cash**: Tiền mặt
   - **SePay**: Thanh toán qua SePay
5. Điền thông tin thanh toán:
   - **Đã thanh toán At** (bắt buộc): Ngày giờ thanh toán
   - **Transaction Reference** (tùy chọn): Số tham chiếu giao dịch
   - **Thanh toán Method** (bắt buộc): bank_transfer, cash, sepay
6. Click **Lưu**
7. Deposit Refund trạng thái chuyển sang `paid`
8. Booking Deposit trạng thái chuyển sang `refunded`
9. Hệ thống tạo Cash Outflow record
10. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Chỉ có thể mark đã thanh toán refund có trạng thái `approved`
- Mark đã thanh toán refund sẽ tạo Cash Outflow record

### 7. Hủy Deposit Refund (Hủy Hoàn Tiền)

1. Truy cập chi tiết refund cần hủy
2. Click **Hủy** hoặc **Hủy**
3. (Tùy chọn) Nhập lý do hủy
4. Xác nhận hủy
5. Deposit Refund trạng thái chuyển sang `cancelled`
6. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Chỉ có thể hủy refund có trạng thái `pending` hoặc `approved`
- Không thể hủy refund đã `paid` hoặc `rejected`

### 8. Cập nhật Deposit Refund

1. Truy cập chi tiết refund cần cập nhật
2. Click **Chỉnh sửa** (chỉ khi trạng thái = `pending`)
3. Cập nhật thông tin:
   - Deducted Số tiền (nếu cần điều chỉnh)
   - Damages (nếu có)
   - Refund Số tiền (tự động tính lại)
   - Refund Reason
   - Notes
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Chỉ có thể cập nhật refund có trạng thái `pending`
- Refund Số tiền được tính lại tự động

### 9. Xóa Deposit Refund

1. Truy cập chi tiết refund cần xóa
2. Click **Xóa** (chỉ khi trạng thái = `pending` hoặc `cancelled`)
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa refund

**Lưu ý**: 
- Chỉ có thể xóa refund có trạng thái `pending` hoặc `cancelled`
- Không thể xóa refund đã `approved`, `paid`, hoặc `rejected`

### 10. Xem Thống kê

1. Truy cập **Deposit Refunds** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Refunds by Trạng thái: Phân bố theo trạng thái
   - Refunds by Period: Phân bố theo thời gian
   - Tổng Refund Số tiền: Tổng số tiền hoàn lại
   - Average Refund Số tiền: Số tiền hoàn lại trung bình
   - Tổng Deducted Số tiền: Tổng số tiền đã trừ

## Ràng buộc và điều kiện

### Validation Rules

- **Hợp đồng thuê**: 
  - Bắt buộc
  - Phải tồn tại và có trạng thái = `terminated` hoặc end_date đã qua
  - Phải có deposit_amount > 0
  - Không thể tạo refund nếu đã có refund đang chờ/approved cho hợp đồng thuê đó
- **Original Deposit Số tiền**: 
  - Tự động từ hợp đồng thuê.deposit_amount
  - Phải > 0
- **Deducted Số tiền**: 
  - Tự động tính từ:
    - Unpaid Hóa đơn: Tổng số tiền các hóa đơn chưa thanh toán của hợp đồng thuê
    - Ticket Costs: Tổng số tiền các tickets đã charge to khách thuê
    - Damages: Chi phí sửa chữa (nếu có, nhập thủ công)
  - Phải >= 0
- **Refund Số tiền**: 
  - Tự động tính = Deposit Số tiền - Deducted Số tiền
  - Phải >= 0 (không thể âm)
- **Refund Reason**: 
  - Bắt buộc
  - Không được để trống
- **Trạng thái**: 
  - Khi tạo: `pending`
  - Phải là một trong: đang chờ, đã phê duyệt, đã thanh toán, từ chối, cancelled

### Business Rules

1. **Refund Số tiền phải = Deposit Số tiền - Deducted Số tiền**
   - Refund Số tiền không thể âm
   - Nếu Refund Số tiền <= 0, không hoàn tiền hoặc cần thu thêm

2. **Deposit Refund phải được approve trước khi mark đã thanh toán**
   - Chỉ Quản lý mới có thể approve refund
   - Refund không thể mark đã thanh toán nếu chưa approve

3. **Deducted Số tiền Calculation**
   - Unpaid Hóa đơn: Tổng số tiền các hóa đơn chưa thanh toán của hợp đồng thuê
   - Ticket Costs: Tổng số tiền các tickets đã charge to khách thuê (deposit hoặc hóa đơn)
   - Damages: Chi phí sửa chữa (nếu có, nhập thủ công)

4. **Trạng thái Flow**
   - `pending` → `approved` → `paid`
   - `pending` → `rejected` hoặc `cancelled`

5. **Mark Đã thanh toán**
   - Mark đã thanh toán refund sẽ tạo Cash Outflow record
   - Booking Deposit trạng thái chuyển sang `refunded`

## Trạng thái và Workflow

### Deposit Refund Trạng thái Flow

```
pending → approved → paid
         ↓          ↓
      rejected   cancelled
```

- **đang chờ**: Hoàn tiền đã được tạo, chờ Quản lý phê duyệt
- **đã phê duyệt**: Quản lý đã phê duyệt, chờ thanh toán
- **đã thanh toán**: Đã thanh toán hoàn tiền
- **từ chối**: Quản lý đã từ chối
- **cancelled**: Đã hủy hoàn tiền

### Workflow Tạo Deposit Refund

1. Sau khi hợp đồng thuê kết thúc hoặc bị terminate, Quản lý hoặc Môi giới tạo deposit refund
2. Chọn Hợp đồng thuê đã kết thúc/terminate
3. Hệ thống tự động tính toán:
   - Original Deposit Số tiền từ hợp đồng thuê.deposit_amount
   - Deducted Số tiền từ Unpaid Hóa đơn (của hợp đồng thuê), Ticket Costs (charge to tenant_deposit), Damages
   - Refund Số tiền = Original Deposit Số tiền - Deducted Số tiền
3. Điền Refund Reason và Notes
4. Click Lưu → Refund có trạng thái `pending`
5. Quản lý approve refund → Trạng thái `approved`
6. Quản lý mark đã thanh toán → Trạng thái `paid`
7. Hệ thống tạo Company Hóa đơn (loại = deposit_refund)

## Ví dụ

### Ví dụ 1: Tạo Deposit Refund

**Hợp đồng thuê:**
- Deposit Số tiền: 20,000,000 VND (trong hợp đồng thuê)
- Trạng thái: `terminated`
- End Ngày: 2025-06-30

**Hợp đồng thuê kết thúc:**
- Unpaid Hóa đơn: 5,000,000 VND (các hóa đơn chưa thanh toán của hợp đồng thuê)
- Ticket Costs: 1,000,000 VND (ticket logs đã charge to tenant_deposit)
- Damages: 0 VND

**Các bước:**
1. Truy cập **Deposit Refunds** → **Tạo**
2. Chọn **Hợp đồng thuê** đã kết thúc
3. Hệ thống tự động tính toán:
   - Original Deposit Số tiền: 20,000,000 VND (từ hợp đồng thuê.deposit_amount)
   - Deducted Số tiền:
     - Unpaid Hóa đơn: 5,000,000 VND
     - Ticket Costs: 1,000,000 VND
     - Damages: 0 VND
     - Tổng: 6,000,000 VND
   - Refund Số tiền: 20,000,000 - 6,000,000 = 14,000,000 VND
4. Nhập Refund Reason: "Hợp đồng thuê kết thúc, hoàn tiền cọc"
5. Chọn Refund Method: `bank_transfer`
6. Click **Lưu**
7. Refund được tạo với trạng thái `pending`

### Ví dụ 2: Approve và Mark Đã thanh toán

**Các bước:**
1. Quản lý approve refund → Trạng thái `approved`
2. Quản lý mark đã thanh toán refund:
   - Thanh toán Method: `bank_transfer`
   - Đã thanh toán At: 2025-06-30 10:00
   - Transaction Reference: `REF123456`
3. Click **Lưu**
4. Refund trạng thái chuyển sang `paid`
5. Hệ thống tạo Company Hóa đơn (loại = deposit_refund)
6. Hệ thống tạo Cash Outflow record
7. Hệ thống gửi thông báo cho Khách thuê

## Lưu ý

1. **Deducted Số tiền Calculation**
   - Tính toán cẩn thận để đảm bảo chính xác
   - Bao gồm tất cả các khoản nợ từ khách thuê

2. **Refund Số tiền**
   - Refund Số tiền không thể âm
   - Nếu Refund Số tiền <= 0, không hoàn tiền hoặc cần thu thêm

3. **Approve Refund**
   - Review kỹ Deducted Số tiền trước khi approve
   - Đảm bảo tính đúng của các khoản nợ

4. **Thanh toán Processing**
   - Hoàn tiền có thể thực hiện bằng nhiều phương thức
   - Tạo Cash Outflow để track chi phí

5. **Company Hóa đơn và Cash Outflow**
   - Khi mark đã thanh toán, hệ thống tạo Company Hóa đơn (loại = deposit_refund)
   - Hệ thống tạo Cash Outflow record để track chi phí

## Troubleshooting

### Không thể tạo deposit refund

1. Kiểm tra Hợp đồng thuê có trạng thái `terminated` hoặc end_date đã qua không
2. Kiểm tra Hợp đồng thuê có deposit_amount > 0 không
3. Kiểm tra đã có deposit refund đang chờ/approved cho hợp đồng thuê đó chưa
4. Kiểm tra Refund Số tiền có > 0 không
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Deducted Số tiền không chính xác

1. Kiểm tra Unpaid Hóa đơn có được tính đúng không
2. Kiểm tra Ticket Costs có được tính đúng không
3. Kiểm tra Damages có được nhập đúng không
4. Tính lại Deducted Số tiền nếu cần
5. Liên hệ hỗ trợ nếu vẫn không chính xác

### Không thể approve refund

1. Kiểm tra refund có trạng thái `pending` không
2. Kiểm tra Refund Số tiền có >= 0 không
3. Review Deducted Số tiền có chính xác không
4. Liên hệ hỗ trợ nếu vẫn không thể approve

---

**Xem thêm:**
- [Quản lý Booking Deposits](./10-booking-deposits.md)
- [Quản lý Hợp đồng thuê](./05-leases.md)
- [Workflow Booking Deposit Refund](../workflows/06-booking-deposit-refund.md)

**Cập nhật: 2025-01-XX

