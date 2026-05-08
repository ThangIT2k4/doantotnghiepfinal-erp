# RÀNG BUỘC VÀ ĐIỀU KIỆN

## Tổng quan

Tài liệu này mô tả tất cả các ràng buộc và điều kiện trong hệ thống Quản lý Bất động sản Cho thuê. Các ràng buộc này phải được tuân thủ nghiêm ngặt để đảm bảo tính nhất quán và chính xác của dữ liệu.

## Ràng buộc kỹ thuật

### Platform Requirements

- **PHP**: 8.2 hoặc cao hơn
- **MySQL**: 8.0 hoặc cao hơn
- **Web Server**: Apache hoặc Nginx
- **OS**: Linux hoặc Windows Server

### Browser Support

- Chrome (latest versions)
- Firefox (latest versions)
- Safari (latest versions)
- Edge (latest versions)
- Mobile browsers

### Third-party Services

- **Email Service**: SMTP phải hoạt động bình thường
- **SePay API**: Phải hoạt động ổn định
- **Google OAuth API**: Phải cấu hình đúng

## Ràng buộc dữ liệu

### Xác thực & Authorization

#### Đăng ký
- **Email**: 
  - Phải unique trong hệ thống
  - Phải đúng format email (RFC 5322)
  - Không được để trống
- **Phone**: 
  - Phải unique trong hệ thống
  - Phải đúng format số điện thoại
  - Không được để trống
- **Password**: 
  - Tối thiểu 8 ký tự
  - Nên có chữ hoa, chữ thường, số, và ký tự đặc biệt
  - Không được để trống

#### Đăng nhập
- Người dùng phải tồn tại trong hệ thống
- Email/Phone phải được xác thực
- Password phải đúng
- Người dùng phải có trạng thái `active`

#### Email Verification
- OTP phải là 6 chữ số
- OTP có thời gian hết hạn (thường 15 phút)
- Chỉ cho phép resend sau một khoảng thời gian nhất định

### Tổ chức

- **Code**: Phải unique trong hệ thống
- **Email**: Phải đúng format email
- **Tax Code**: Nếu có, phải đúng format
- Không thể xóa tổ chức có người dùng đang hoạt động

### Bất động sản

- **Name**: Không được để trống
- **Bất động sản Loại**: Phải tồn tại trong hệ thống
- **Tổ chức**: Phải thuộc về một tổ chức
- Không thể xóa bất động sản có phòng hoặc hợp đồng thuê đang hoạt động

### Phòng

- **Code**: Phải unique trong cùng một bất động sản
- **Bất động sản**: Phải tồn tại và thuộc tổ chức hiện tại
- **Base Rent**: Phải >= 0
- **Deposit Số tiền**: Phải >= 0
- **Max Occupancy**: Phải > 0
- Không thể xóa phòng có hợp đồng thuê hoạt động
- Không thể xóa phòng có booking deposit đang đang chờ/approved

### Hợp đồng thuê

- **Phòng**: 
  - Phải tồn tại
  - Phải có trạng thái `available` hoặc `reserved`
  - Không được có hợp đồng thuê hoạt động khác
- **Khách thuê/Lead**: 
  - Phải có ít nhất một trong hai (khách thuê hoặc lead)
  - Khách thuê phải tồn tại và có trạng thái `active`
- **Start Ngày**: 
  - Không được để trống
  - Phải là ngày hợp lệ
- **End Ngày**: 
  - Không được để trống
  - Phải sau Start Ngày
  - Phải là ngày hợp lệ
- **Hợp đồng Number**: 
  - Phải unique trong hệ thống
  - Nếu không nhập, hệ thống tự động tạo (format: HD{org_id}{YYYY}{MM}{XXXX}, ví dụ: HD32025110001)
- **Rent Số tiền**: Phải >= 0
- **Deposit Số tiền**: Phải >= 0
- **Billing Day**: Phải từ 1 đến 28
- **Thanh toán Cycle**: 
  - Phải tồn tại trong bảng `payment_cycles` (không còn enum hard-coded)
  - Có thể là: hàng tháng, hàng quý, hàng năm, tùy chỉnh hoặc các cycle tùy chỉnh
- **Thanh toán Day**: Phải từ 1 đến 31
- **Tùy chỉnh Months**: 
  - Chỉ bắt buộc khi Thanh toán Cycle = tùy chỉnh
  - Phải từ 1 đến 60
- Không thể xóa hợp đồng thuê có hóa đơn chưa thanh toán
- Không thể xóa hợp đồng thuê có thanh toán đang chờ

### Lead

- **Name**: Không được để trống
- **Phone**: 
  - Phải unique trong hệ thống (nếu có)
  - Phải đúng format
- **Email**: 
  - Phải unique trong hệ thống (nếu có)
  - Phải đúng format
- **Trạng thái**: Phải là một trong: new, contacted, qualified, lost, converted
- **Source**: Phải là một trong các nguồn được định nghĩa (web, zalo, fb, referral, etc.)

### Viewing

- **Bất động sản/Unit**: Phải tồn tại
- **Môi giới**: Phải tồn tại và có trạng thái `active`
- **Schedule At**: 
  - Không được để trống
  - Phải là datetime hợp lệ
  - Phải trong tương lai (khi tạo mới)
- **Trạng thái**: Phải là một trong: requested, confirmed, done, no_show, cancelled
- Không thể hủy viewing đã done

### Booking Deposit

- **Phòng**: 
  - Phải tồn tại
  - Phải có trạng thái `available` hoặc `reserved`
- **Khách thuê/Lead**: Phải có ít nhất một trong hai
- **Môi giới**: Phải tồn tại và có trạng thái `active`
- **Số tiền**: Phải > 0
- **Deposit Loại**: Phải là một trong: booking, security, advance
- **Hold Until**: 
  - Không được để trống
  - Phải là datetime hợp lệ
  - Phải trong tương lai (khi tạo mới)
- **Thanh toán Trạng thái**: Phải là một trong: đang chờ, pending_approval, đã thanh toán, refunded, expired, cancelled
- Không thể approve deposit đã expired
- Không thể mark đã thanh toán nếu chưa approve (trạng thái phải là `pending` sau khi approve)
- Deposit tự động expire nếu quá Hold Until và chưa đã thanh toán
- Trạng thái transitions: pending_approval → đang chờ → đã thanh toán/expired/cancelled

### Hóa đơn

- **Hợp đồng thuê/Booking Deposit**: Phải có ít nhất một trong hai (hợp đồng thuê hoặc booking_deposit)
- **Hóa đơn Number**: 
  - Phải unique trong hệ thống
  - Format: HD{org_id}{YYYY}{MM}{XXXX} (ví dụ: HD32025110001)
  - Tự động tạo nếu không nhập
- **Issue Ngày**: 
  - Không được để trống
  - Phải là ngày hợp lệ
- **Đến hạn Ngày**: 
  - Không được để trống
  - Phải >= Issue Ngày
  - Phải là ngày hợp lệ
- **Items**: 
  - Phải có ít nhất 1 item
  - Mỗi item phải có: Loại, Description, Quantity, Phòng Price, Số tiền
- **Tổng Số tiền**: 
  - Phải = Subtotal + Tax Số tiền - Discount Số tiền
  - Phải > 0
- **Trạng thái**: 
  - Khi tạo: `draft`
  - Sau khi issue: `issued`
  - Sau khi thanh toán đủ: `paid` (tự động khi total_paid >= total_amount)
  - Sau khi quá hạn: `overdue` (tự động khi due_date < now() và chưa đã thanh toán)
  - Có thể: `cancelled`
- **Hóa đơn Loại**: Phải là một trong: monthly_rent, first_invoice, booking_deposit, other
- Không thể sửa hóa đơn đã issued (trừ một số trường hợp đặc biệt)
- Không thể xóa hóa đơn đã có thanh toán
- Không thể thanh toán hóa đơn đã cancelled
- Trạng thái transitions: draft → issued → đã thanh toán/overdue/cancelled

### Thanh toán

- **Hóa đơn**: 
  - Phải tồn tại
  - Phải có trạng thái `issued` hoặc `overdue` (không có trạng thái `partially_paid`, hệ thống tự tính remaining số tiền)
  - Không được cancelled
- **Số tiền**: 
  - Phải > 0
  - Phải <= Hóa đơn Remaining Số tiền (total_amount - paid_amount)
- **Thanh toán Method**: Phải tồn tại trong bảng `payment_methods` (key_code: cash, bank_transfer, sepay, other)
- **Đã thanh toán At**: 
  - Không được để trống
  - Phải là datetime hợp lệ
- **Payer**: Phải có ít nhất một trong hai: `payer_user_id` (khách thuê) hoặc `lead_id`
- **Trạng thái**: 
  - Khi tạo (cash): `success` (hoặc `pending` để môi giới xác nhận)
  - Khi tạo (sepay): `pending`
  - Sau khi SePay webhook: `success` hoặc `failed`
  - Có thể: `refunded`
- Không thể xóa thanh toán đã success
- Không thể sửa thanh toán đã success
- Trạng thái transitions: đang chờ → success/failed, success → refunded

### Ticket

- **Phòng/Lease**: Phải có ít nhất một trong hai
- **Title**: Không được để trống
- **Description**: Không được để trống
- **Priority**: Phải là một trong: low, medium, high, urgent
- **Trạng thái**: Phải là một trong: open, in_progress, resolved, closed, cancelled
- Không thể xóa ticket đã closed

### Ticket Log

- **Ticket**: Phải tồn tại
- **Action**: Không được để trống
- **Detail**: Không được để trống
- **Cost Số tiền**: Nếu có, phải >= 0
- **Charge To**: Phải là một trong: none, tenant_deposit, tenant_invoice, landlord, nhà cung cấp
- **Linked Hóa đơn ID**: 
  - Bắt buộc nếu Charge To = tenant_invoice
  - Hóa đơn phải tồn tại và thuộc hợp đồng thuê của ticket
- **Nhà cung cấp ID**: 
  - Bắt buộc nếu Charge To = nhà cung cấp
  - Nhà cung cấp phải tồn tại
- **Warranty Period Days**: Nếu có, phải >= 0

### Meter

- **Phòng**: Phải tồn tại
- **Service**: Phải tồn tại và là một trong: electricity, water, gas, other
- **Serial Number**: Nếu có, phải unique trong cùng phòng và service
- **Trạng thái**: Phải là một trong: hoạt động, không hoạt động
- Mỗi phòng + service chỉ có 1 meter hoạt động tại một thời điểm

### Meter Reading

- **Meter**: Phải tồn tại và có trạng thái `active`
- **Reading Ngày**: 
  - Không được để trống
  - Phải là ngày hợp lệ
  - Phải >= Last Reading Ngày (nếu có)
- **Value**: 
  - Phải là số dương
  - Phải >= Last Reading Value (nếu có)
- **Taken By**: Phải tồn tại và có trạng thái `active`

### Review

- **Phòng**: Phải tồn tại
- **Hợp đồng thuê**: Phải tồn tại và thuộc phòng
- **Khách thuê**: Phải là khách thuê của hợp đồng thuê
- **Overall Rating**: Phải từ 1 đến 5
- **Location Rating**: Phải từ 1 đến 5
- **Quality Rating**: Phải từ 1 đến 5
- **Service Rating**: Phải từ 1 đến 5
- **Price Rating**: Phải từ 1 đến 5
- **Title**: Không được để trống
- **Content**: Không được để trống
- Mỗi hợp đồng thuê chỉ có thể có 1 review từ khách thuê đó

### Commission Policy

- **Title**: Không được để trống
- **Code**: Phải unique trong hệ thống
- **Trigger Event**: Phải là một trong: deposit_paid, lease_signed, invoice_paid, viewing_done, listing_published
- **Basis**: Phải là một trong: cash, accrual
- **Calc Loại**: Phải là một trong: percent, flat, tiered
- **Percent Value**: 
  - Bắt buộc nếu Calc Loại = percent
  - Phải từ 0 đến 100
- **Flat Số tiền**: 
  - Bắt buộc nếu Calc Loại = flat
  - Phải > 0
- **Apply Limit Months**: 
  - Nếu có, phải từ 1 đến 12
  - Chủ yếu dùng cho trigger invoice_paid
- **Min Số tiền**: Nếu có, phải >= 0
- **Cap Số tiền**: Nếu có, phải >= Min Số tiền
- **Hoạt động**: Phải là boolean

### Commission Event

- **Policy**: Phải tồn tại và có trạng thái `active`
- **Người dùng (Môi giới)**: Phải tồn tại và có trạng thái `active`
- **Trigger Event**: Phải khớp với Policy Trigger Event
- **Số tiền**: Phải >= 0 (tự động tính từ policy)
- **Trạng thái**: Phải là một trong: đang chờ, đã phê duyệt, từ chối, đã thanh toán
- Không thể approve event đã từ chối hoặc đã thanh toán
- Không thể mark đã thanh toán nếu chưa approve

### Salary Hợp đồng

- **Người dùng**: Phải tồn tại và có trạng thái `active`
- **Base Salary**: Phải > 0
- **Currency**: Hiện tại chỉ hỗ trợ VND
- **Pay Cycle**: Phải là một trong: hàng tháng, bi-hàng tuần, hàng tuần
- **Pay Day**: Phải từ 1 đến 31
- **Effective From**: 
  - Không được để trống
  - Phải là ngày hợp lệ
- **Effective To**: 
  - Nếu có, phải >= Effective From
  - Phải là ngày hợp lệ

### Salary Advance

- **Người dùng**: Phải tồn tại và có trạng thái `active`
- **Số tiền**: Phải > 0
- **Reason**: Không được để trống
- **Expected Repayment Ngày**: 
  - Không được để trống
  - Phải là ngày hợp lệ
  - Phải trong tương lai
- **Repayment Method**: Phải là một trong: payroll_deduction, direct_payment, installment
- **Installment Months**: 
  - Bắt buộc nếu Repayment Method = installment
  - Phải từ 1 đến 12
- **Trạng thái**: Phải là một trong: đang chờ, đã phê duyệt, từ chối, repaid, partially_repaid

### Payroll Cycle

- **Period Month**: 
  - Phải là format YYYY-MM
  - Phải unique trong tổ chức
  - Phải là tháng hợp lệ
- **Trạng thái**: Phải là một trong: open, locked, đã thanh toán
- Không thể sửa cycle đã locked hoặc đã thanh toán

### Payroll Payslip

- **Payroll Cycle**: Phải tồn tại
- **Người dùng**: Phải tồn tại và có Salary Hợp đồng hoạt động trong period
- **Gross Số tiền**: Phải >= 0 (tự động tính)
- **Deduction Số tiền**: Phải >= 0 (tự động tính)
- **Net Số tiền**: Phải = Gross Số tiền - Deduction Số tiền
- **Trạng thái**: Phải là một trong: draft, đã phê duyệt, đã thanh toán
- Không thể sửa payslip đã đã thanh toán

### Company Hóa đơn

- **Nhà cung cấp/User**: Phải có ít nhất một trong hai
- **Hóa đơn Number**: Phải unique trong hệ thống
- **Hóa đơn Loại**: Phải là một trong: master_lease, ticket_cost, deposit_refund, payroll_payslip, landlord_payout, user_payout, utility, maintenance, service, supply, other
- **Issue Ngày**: 
  - Không được để trống
  - Phải là ngày hợp lệ
- **Đến hạn Ngày**: 
  - Không được để trống
  - Phải >= Issue Ngày
  - Phải là ngày hợp lệ
- **Items**: Phải có ít nhất 1 item
- **Tổng Số tiền**: Phải > 0
- **Trạng thái**: Phải là một trong: draft, đang chờ, đã phê duyệt, đã thanh toán, overdue, cancelled
- Không thể sửa hóa đơn đã đã phê duyệt hoặc đã thanh toán
- Không thể xóa hóa đơn đã có thanh toán

### Cash Outflow

- **Nhà cung cấp/User**: Có thể để trống hoặc có một
- **Company Hóa đơn**: Có thể để trống (nếu không link)
- **Số tiền**: Phải > 0
- **Thanh toán Method**: Phải là một trong: cash, bank_transfer, sepay, other
- **Đã thanh toán At**: 
  - Không được để trống
  - Phải là datetime hợp lệ
- **Trạng thái**: Phải là một trong: đang chờ, success, failed, reversed
- Không thể reverse outflow đã reversed

### Master Hợp đồng thuê

- **Landlord Người dùng**: Phải tồn tại và có role `landlord`
- **Bất động sản**: Phải tồn tại
- **Start Ngày**: 
  - Không được để trống
  - Phải là ngày hợp lệ
- **End Ngày**: 
  - Không được để trống
  - Phải >= Start Ngày
  - Phải là ngày hợp lệ
- **Base Rent**: Phải >= 0
- **Revenue Share Pct**: Phải từ 0 đến 100

## Ràng buộc nghiệp vụ

### Tổng quan

- Một phòng chỉ có thể có 1 hợp đồng thuê hoạt động tại một thời điểm
- Hóa đơn phải có ít nhất 1 item
- Thanh toán số tiền không được vượt quá hóa đơn remaining số tiền
- Booking deposit phải được approve trước khi mark đã thanh toán
- Hợp đồng thuê phải có khách thuê hoặc lead
- Meter reading value phải >= giá trị trước
- Hợp đồng number phải unique
- Hóa đơn number phải unique

### Workflow Constraints

- Lead → Viewing → Booking Deposit → Hợp đồng thuê → Hóa đơn → Thanh toán
- Ticket có cost → Ticket Log → Hóa đơn Item hoặc Deposit deduction
- Meter Reading → Hóa đơn Item (nếu có service trong hợp đồng thuê)
- Commission Event: Trigger → Tạo → Approve → Đã thanh toán
- Payroll: Cycle → Generate Payslips → Lock → Pay → Đã thanh toán

### Tổ chức Isolation

- Mỗi tổ chức chỉ thấy dữ liệu của mình
- SuperAdmin có thể thấy tất cả dữ liệu
- Quản lý chỉ thấy dữ liệu trong tổ chức của mình
- Môi giới chỉ thấy dữ liệu được gán hoặc trong tổ chức của mình
- Khách thuê chỉ thấy dữ liệu của chính mình

## Validation Rules

### Ngày/Time

- Start Ngày < End Ngày (cho hợp đồng thuê, hợp đồng, etc.)
- Đến hạn Ngày >= Issue Ngày (cho hóa đơn)
- Reading Ngày >= Last Reading Ngày (cho meter reading)
- Hold Until > Current Ngày (cho booking deposit khi tạo mới)
- Effective From <= Effective To (cho hợp đồng)
- Schedule At > Current Ngày (cho viewing khi tạo mới)

### Số tiền

- Tất cả số tiền phải >= 0 (trừ khi có ràng buộc khác)
- Thanh toán số tiền <= Hóa đơn remaining số tiền
- Refund số tiền <= Deposit số tiền - Deducted số tiền
- Commission số tiền >= Min Số tiền và <= Cap Số tiền (nếu có)

### Trạng thái Transitions

#### Hóa đơn
- `draft` → `issued` → `paid`/`overdue`/`cancelled`
- `overdue` → `paid`/`cancelled`
- `paid` → (không thể chuyển)
- `cancelled` → (không thể chuyển)

#### Thanh toán
- `pending` → `success`/`failed`
- `success` → `refunded`
- `failed` → (có thể retry)
- `refunded` → (không thể chuyển)

#### Booking Deposit
- `pending_approval` → `pending`/`cancelled`
- `pending` → `paid`/`expired`/`cancelled`
- `paid` → `refunded`/`expired`
- `expired` → `cancelled`/`paid` (thanh toán lại)
- `cancelled` → `pending_approval`/`pending`/`paid` (khôi phục)

#### Ticket
- `open` → `in_progress` → `resolved` → `closed`
- Có thể: `cancelled` từ bất kỳ trạng thái nào

#### Commission Event
- `pending` → `approved` → `paid`
- Có thể: `rejected` từ `pending`

#### Payroll Cycle
- `open` → `locked` → `paid`

#### Company Hóa đơn
- `draft` → `pending` → `approved` → `paid`/`overdue`/`cancelled`
- `overdue` → `paid`/`cancelled`

#### Cash Outflow
- `pending` → `success`/`failed`
- `success` → `reversed`

---

**Lưu ý**: Tất cả các ràng buộc trên phải được kiểm tra trước khi thực hiện thao tác. Nếu vi phạm, hệ thống sẽ hiển thị thông báo lỗi cụ thể.

**Cập nhật**: 2025-01-XX

