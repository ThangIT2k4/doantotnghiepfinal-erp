# QUẢN LÝ SỰ KIỆN HOA HỒNG - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý sự kiện hoa hồng (commission events) trong tổ chức, bao gồm xem, approve, reject, mark đã thanh toán, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ quản lý tất cả commission events trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `finance.access`
  - Xem commission events của mình: Không cần capability (tự động lọc theo user_id)
  - Xem tất cả commission events: Cần capability `finance.commission.view` hoặc `finance.commission.view_all`
  - Approve/Mark Đã thanh toán: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/commission-events`

## Các bước thực hiện

### 1. Xem danh sách Commission Events

1. Truy cập **Commission Events** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả commission events trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (đang chờ, đã phê duyệt, đã thanh toán, từ chối)
   - Môi giới (nếu có nhiều agents)
   - Trigger Event (deposit_paid, lease_signed, invoice_paid, viewing_done, listing_published)
   - Trigger Source (booking_deposit, hợp đồng thuê, hóa đơn, viewing, listing)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo created_at, commission_amount, trạng thái

### 2. Xem chi tiết Commission Event

1. Click vào event trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Event ID: Mã sự kiện
     - Policy: Commission policy được áp dụng
     - Môi giới: Môi giới được gán hoa hồng
     - Trigger Event: Sự kiện kích hoạt
     - Trigger Source Loại: Loại source (booking_deposit, hợp đồng thuê, hóa đơn, viewing, listing)
     - Trigger Source ID: ID của source (deposit ID, hợp đồng thuê ID, hóa đơn ID, etc.)
   - **Thông tin tài chính:**
     - Số tiền: Số tiền tính hoa hồng (từ source)
     - Commission Số tiền: Số tiền hoa hồng đã tính
     - Min Số tiền: Số tiền tối thiểu (từ policy)
     - Cap Số tiền: Số tiền tối đa (từ policy)
   - **Thông tin khác:**
     - Trạng thái: Trạng thái hiện tại (đang chờ, đã phê duyệt, đã thanh toán, từ chối)
     - Basis: Cơ sở tính toán (cash, accrual)
     - Created At: Ngày tạo event
     - Đã phê duyệt At: Ngày approve (nếu có)
     - Đã thanh toán At: Ngày thanh toán (nếu có)
     - Đã phê duyệt By: Người approve (nếu có)
     - Đã thanh toán By: Người mark đã thanh toán (nếu có)

### 3. Approve Commission Event (Phê duyệt Hoa hồng)

1. Truy cập chi tiết event có trạng thái `pending`
2. Review thông tin event:
   - Policy: Commission policy được áp dụng
   - Môi giới: Môi giới được gán hoa hồng
   - Số tiền: Số tiền tính hoa hồng (từ source)
   - Commission Số tiền: Số tiền hoa hồng đã tính
   - Trigger Source: Source liên quan (deposit, hợp đồng thuê, hóa đơn, etc.)
3. Click **Approve** hoặc **Phê duyệt**
4. Commission Event trạng thái chuyển sang `approved`
5. Hệ thống gửi thông báo cho Môi giới
6. Commission Event sẵn sàng thanh toán (nếu Basis = accrual) hoặc chờ thanh toán thực tế (nếu Basis = cash)

**Lưu ý**: 
- Chỉ Quản lý mới có thể approve commission event
- Review kỹ thông tin trước khi approve

### 4. Reject Commission Event (Từ chối Hoa hồng)

1. Truy cập chi tiết event có trạng thái `pending`
2. Click **Reject** hoặc **Từ chối**
3. (Tùy chọn) Nhập lý do từ chối
4. Xác nhận reject
5. Commission Event trạng thái chuyển sang `rejected`
6. Hệ thống gửi thông báo cho Môi giới

**Lưu ý**: 
- Chỉ Quản lý mới có thể reject commission event
- Reject event khi thông tin không đúng hoặc cần điều chỉnh

### 5. Mark Đã thanh toán (Đánh dấu Đã thanh toán)

1. Sau khi approve event và sẵn sàng thanh toán (nếu Basis = accrual hoặc đã có thanh toán nếu Basis = cash), Quản lý mark đã thanh toán
2. Truy cập chi tiết event có trạng thái `approved`
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
- Chỉ có thể mark đã thanh toán event có trạng thái `approved` và sẵn sàng thanh toán
- Commission có thể được thanh toán qua payroll hoặc company hóa đơn

### 6. Xem Thống kê

1. Truy cập **Commission Events** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Events by Trạng thái: Phân bố theo trạng thái
   - Events by Môi giới: Phân bố theo môi giới
   - Events by Trigger Event: Phân bố theo trigger event
   - Events by Period: Phân bố theo thời gian
   - Tổng Commission Số tiền: Tổng số tiền hoa hồng
   - Đã phê duyệt Commission Số tiền: Tổng số tiền hoa hồng đã approve
   - Đã thanh toán Commission Số tiền: Tổng số tiền hoa hồng đã thanh toán

## Ràng buộc và điều kiện

### Validation Rules

- **Commission Event**: 
  - Phải tồn tại và thuộc về tổ chức
- **Trạng thái**: 
  - Phải là một trong: đang chờ, đã phê duyệt, đã thanh toán, từ chối

### Business Rules

1. **Commission Event phải được approve trước khi mark đã thanh toán**
   - Chỉ Quản lý mới có thể approve event
   - Event không thể mark đã thanh toán nếu chưa approve

2. **Basis = Cash cần thanh toán thực tế**
   - Nếu Basis = Cash, phải có thanh toán thành công từ source
   - Nếu chưa có thanh toán, commission chưa sẵn sàng thanh toán

3. **Basis = Accrual sẵn sàng thanh toán ngay**
   - Nếu Basis = Accrual, commission sẵn sàng thanh toán ngay sau khi approve
   - Không cần chờ thanh toán thực tế

4. **Thanh toán Methods**
   - Commission có thể được thanh toán qua payroll hoặc company hóa đơn
   - Quản lý chọn phương thức thanh toán phù hợp

5. **Trạng thái Flow**
   - `pending` → `approved` → `paid`
   - `pending` → `rejected`

## Trạng thái và Workflow

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

### Workflow Approve và Mark Đã thanh toán

1. Hệ thống tự động tạo commission event khi có trigger event → Trạng thái `pending`
2. Quản lý review và approve event → Trạng thái `approved`
3. Kiểm tra Basis:
   - `cash`: Chờ thanh toán thực tế từ source
   - `accrual`: Sẵn sàng thanh toán ngay
4. Quản lý mark đã thanh toán → Trạng thái `paid`
5. Hệ thống tạo Payroll Item hoặc Company Hóa đơn
6. Commission được thanh toán cho Môi giới

## Ví dụ

### Ví dụ 1: Approve và Mark Đã thanh toán Commission Event

**Commission Event:**
- Policy: "Hoa hồng từ Deposit - 3%"
- Môi giới: Môi giới B
- Số tiền: 5,000,000 VND (từ deposit)
- Commission Số tiền: 150,000 VND (5M × 3%)
- Trigger Event: `deposit_paid`
- Basis: `cash`
- Trạng thái: `pending`

**Các bước:**
1. Truy cập chi tiết Commission Event
2. Quản lý review thông tin event
3. Click **Approve** → Trạng thái `approved`
4. Kiểm tra deposit đã thanh toán (Basis = cash)
5. Click **Mark Đã thanh toán** → Trạng thái `paid`
6. Hệ thống tạo Company Hóa đơn cho Môi giới B với số tiền 150,000 VND

### Ví dụ 2: Approve với Basis = Accrual

**Commission Event:**
- Policy: "Hoa hồng từ Hợp đồng thuê - 5%"
- Môi giới: Môi giới C
- Số tiền: 10,000,000 VND (từ hợp đồng thuê deposit)
- Commission Số tiền: 500,000 VND (10M × 5%)
- Trigger Event: `lease_signed`
- Basis: `accrual`
- Trạng thái: `pending`

**Các bước:**
1. Truy cập chi tiết Commission Event
2. Quản lý review thông tin event
3. Click **Approve** → Trạng thái `approved`
4. Vì Basis = `accrual`, commission sẵn sàng thanh toán ngay
5. Click **Mark Đã thanh toán** → Trạng thái `paid`
6. Hệ thống tạo Payroll Item hoặc Company Hóa đơn cho Môi giới C

## Lưu ý

1. **Approve Commission**
   - Review kỹ thông tin trước khi approve
   - Đảm bảo commission được tính đúng

2. **Basis = Cash**
   - Chờ thanh toán thực tế từ source trước khi mark đã thanh toán
   - Kiểm tra deposit/invoice đã thanh toán chưa

3. **Basis = Accrual**
   - Commission sẵn sàng thanh toán ngay sau khi approve
   - Không cần chờ thanh toán thực tế

4. **Thanh toán Methods**
   - Chọn phương thức thanh toán phù hợp (payroll hoặc company hóa đơn)
   - Payroll: Thanh toán cùng lương
   - Company Hóa đơn: Thanh toán riêng

5. **Thống kê**
   - Xem thống kê để theo dõi tổng hoa hồng
   - Theo dõi đang chờ, đã phê duyệt, đã thanh toán amounts

## Troubleshooting

### Không thể approve commission event

1. Kiểm tra event có trạng thái `pending` không
2. Kiểm tra Quản lý có quyền approve không
3. Liên hệ hỗ trợ nếu vẫn không thể approve

### Không thể mark đã thanh toán commission event

1. Kiểm tra event có trạng thái `approved` không
2. Kiểm tra Basis = cash có thanh toán thực tế không
3. Kiểm tra Basis = accrual có sẵn sàng thanh toán không
4. Liên hệ hỗ trợ nếu vẫn không thể mark đã thanh toán

---

**Xem thêm:**
- [Quản lý Commission Policies](./18-commission-policies.md)
- [Môi giới Commission Events](../agent/18-commission-events.md)
- [Workflow Commission Calculation](../workflows/04-commission-calculation.md)

**Cập nhật: 2025-01-XX

