# Tóm tắt cập nhật tài liệu Nhân viên

## ✅ Đã hoàn thành (Core Modules)

### 1. 05-hợp đồng thuê.md ✅
- Capabilities: `contract.access`, `contract.lease.view`, `contract.lease.create`, `contract.lease.update`, `contract.lease.delete`
- Ownership filtering: `view_all` vs `view_own`
- Trạng thái: draft, hoạt động, terminated, expired
- Routes: `/staff/leases`
- Hợp đồng number format: `HD{org_id}{YYYY}{MM}{XXXX}`
- Thanh toán Cycle: Từ bảng `payment_cycles`
- Hợp đồng thuê Service Set: Bắt buộc
- Booking Deposit integration

### 2. 12-hóa đơn.md ✅
- Capabilities: `billing.access`, `billing.invoice.create`, `billing.invoice.update`, `billing.invoice.view`
- Ownership filtering: `view_all` vs `view_own`
- Trạng thái: draft, issued, đã thanh toán, overdue, cancelled (KHÔNG có `partially_paid`)
- Routes: `/staff/invoices`
- Hóa đơn number format: `HD{org_id}{YYYY}{MM}{XXXX}`
- Hóa đơn types: monthly_rent, first_invoice, booking_deposit, other
- Hóa đơn có thể từ Hợp đồng thuê hoặc Booking Deposit

### 3. 13-thanh toán.md ✅
- Capabilities: `billing.payment.create`, `billing.payment.update`, `billing.payment.view`
- Ownership filtering: `view_all` vs `view_own`
- Trạng thái: đang chờ, success, failed, refunded
- Routes: `/staff/payments`
- Thanh toán method: Từ bảng `payment_methods`
- SePay thanh toán flow với webhook

### 4. 10-booking-deposits.md ✅
- Capabilities: `contract.booking_deposit.create`, `contract.booking_deposit.update`
- Ownership filtering: `view_all` vs `view_own`
- Thanh toán Trạng thái: pending_approval, đang chờ, đã thanh toán, expired, cancelled, refunded
- Routes: `/staff/booking-deposits`
- Approve workflow: pending_approval → đang chờ
- Auto-tạo hóa đơn khi approve

## ✅ Đã kiểm tra và đúng

### 5. 08-leads.md ✅
- Capabilities: `crm.access`, `crm.lead.create`, `crm.lead.update`, `crm.lead.view`, `crm.lead.view_own`
- Trạng thái: new, contacted, qualified, converted, lost
- Routes: `/staff/leads`

### 6. 09-viewings.md ✅
- Capabilities: `crm.access`, `crm.appointment.create`, `crm.appointment.update`, `crm.appointment.view`, `crm.appointment.view_own`
- Trạng thái: requested, confirmed, done, no_show, cancelled
- Routes: `/staff/viewings`

### 7. 14-tickets.md ✅
- Capabilities: `work.access`, `work.ticket.create`, `work.ticket.update`, `work.ticket.view`, `work.ticket.view_own`
- Trạng thái: open, in_progress, resolved, closed, cancelled
- Routes: `/staff/tickets`

### 8. 03-bất động sản.md ✅
- Capabilities: `asset.access`, `asset.property.create`, `asset.property.update`, `asset.property.view`, `asset.property.view_own`
- Routes: `/staff/properties`

### 9. 24-company-hóa đơn.md ✅
- Capabilities: `finance.access`, `finance.company_invoice.view` (chỉ Quản lý có quyền tạo/update)
- Trạng thái: draft, đang chờ, đã phê duyệt, đã thanh toán, overdue, cancelled
- Routes: `/staff/company-invoices`

## ⏳ Cần kiểm tra và cập nhật (nếu cần)

### CRM Modules
- **07-khách thuê.md**: Kiểm tra capabilities `party.access`, `party.user.*`
- **11-deposit-refunds.md**: Kiểm tra capabilities và trạng thái values

### Asset Modules
- **04-phòng.md**: Kiểm tra capabilities và trạng thái values (available, occupied, reserved, maintenance)
- **15-meters.md**: Kiểm tra capabilities
- **16-meter-readings.md**: Kiểm tra capabilities

### Finance Modules
- **18-commission-policies.md**: Kiểm tra capabilities
- **19-commission-events.md**: Kiểm tra capabilities
- **20-salary-hợp đồng.md**: Kiểm tra capabilities
- **21-salary-advances.md**: Kiểm tra capabilities và trạng thái values
- **22-payroll-cycles.md**: Kiểm tra capabilities và format (YYYY-MM)
- **23-payroll-payslips.md**: Kiểm tra capabilities
- **25-cash-outflows.md**: Kiểm tra capabilities và trạng thái values (đang chờ, success, failed, reversed)

### Cài đặt và Utilities
- **29-capabilities.md**: Cập nhật capability system tổng quan
- **35-cài đặt.md**: Routes `/staff/system-settings`
- **33-sepay.md**: Routes `/staff/sepay`
- **34-thông báo.md**: Routes `/staff/notifications`
- **32-thanh toán-cycle-cài đặt.md**: Routes `/staff/payment-cycle-settings`
- **02-bảng điều khiển.md**: Capabilities cho từng module

### Other Modules
- **06-master-hợp đồng thuê.md**: Kiểm tra capabilities
- **17-reviews.md**: Kiểm tra capabilities
- **26-nhà cung cấp.md**: Kiểm tra capabilities
- **27-nhân viên.md**: Kiểm tra capabilities
- **28-người dùng.md**: Kiểm tra capabilities
- **30-người dùng-banking.md**: Kiểm tra routes
- **31-excel-xuất.md**: Kiểm tra routes

## Các điểm chung đã cập nhật

### ✅ Routes
- Tất cả routes đã unified vào `/staff/*`
- Không còn references đến `/manager/*` và `/agent/*`

### ✅ Capabilities Pattern
- Quản lý: Wildcard `*` = true (tất cả quyền)
- Môi giới: Cần capabilities cụ thể
- Ownership filtering: `view_all` vs `view_own`

### ✅ Trạng thái Values
- Booking Deposit: `payment_status` (không phải `status`)
- Hóa đơn: Không có `partially_paid`
- Thanh toán: `pending`, `success`, `failed`, `refunded` (không phải `paid`)

### ✅ Format Codes
- Hóa đơn/Contract Number: `HD{org_id}{YYYY}{MM}{XXXX}`

## Notes

- Tất cả core modules đã được cập nhật đầy đủ
- Các file còn lại cần kiểm tra capabilities và trạng thái values
- Đảm bảo consistency giữa các file
- Tất cả routes đã unified vào `/staff/*`

## Tiếp theo Steps

1. Kiểm tra và cập nhật các file còn lại nếu cần
2. Đảm bảo tất cả capabilities khớp với `config/role_capabilities.php`/role_capabilities.php`
3. Đảm bảo tất cả trạng thái values khớp với Models
4. Final review và consistency check

