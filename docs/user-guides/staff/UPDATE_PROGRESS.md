# Tiến độ cập nhật tài liệu Nhân viên

## Tổng quan
Document này track tiến độ cập nhật tất cả các file tài liệu trong `docs/user-guides/staff/`/user-guides/staff/` theo codebase thực tế.

## Checklist cập nhật

### ✅ Core Modules (Nhóm 1)

#### ✅ 05-hợp đồng thuê.md
- [x] Capabilities: hợp đồng.truy cập, hợp đồng.hợp đồng thuê.xem, hợp đồng.hợp đồng thuê.tạo, hợp đồng.hợp đồng thuê.cập nhật, hợp đồng.hợp đồng thuê.xóa
- [x] Ownership filtering: view_all vs view_own
- [x] Trạng thái values: draft, hoạt động, terminated, expired
- [x] Routes: /staff/leases
- [x] Hợp đồng number format: HD{org_id}{YYYY}{MM}{XXXX}
- [x] Thanh toán Cycle: Từ bảng payment_cycles
- [x] Hợp đồng thuê Service Set: Bắt buộc
- [x] Booking Deposit integration: booking_deposit_id field
- [x] Lead to Khách thuê auto-creation

#### ✅ 12-hóa đơn.md
- [x] Capabilities: billing.truy cập, billing.hóa đơn.tạo, billing.hóa đơn.cập nhật, billing.hóa đơn.xem
- [x] Ownership filtering: view_all vs view_own
- [x] Trạng thái values: draft, issued, đã thanh toán, overdue, cancelled (KHÔNG có partially_paid)
- [x] Routes: /staff/invoices
- [x] Hóa đơn number format: HD{org_id}{YYYY}{MM}{XXXX}
- [x] Hóa đơn types: monthly_rent, first_invoice, booking_deposit, other
- [x] Hóa đơn có thể từ Hợp đồng thuê hoặc Booking Deposit
- [x] Remaining số tiền được tính tự động

#### ✅ 13-thanh toán.md
- [x] Capabilities: billing.thanh toán.tạo, billing.thanh toán.cập nhật, billing.thanh toán.xem
- [x] Ownership filtering: view_all vs view_own
- [x] Trạng thái values: đang chờ, success, failed, refunded (KHÔNG có đã thanh toán)
- [x] Routes: /staff/payments
- [x] Thanh toán method: Từ bảng payment_methods
- [x] SePay thanh toán flow với webhook
- [x] Thanh toán có thể từ khách thuê người dùng hoặc lead

#### ✅ 10-booking-deposits.md
- [x] Capabilities: hợp đồng.booking_deposit.tạo, hợp đồng.booking_deposit.cập nhật
- [x] Ownership filtering: view_all vs view_own
- [x] Thanh toán Trạng thái: pending_approval, đang chờ, đã thanh toán, expired, cancelled, refunded (KHÔNG dùng trạng thái)
- [x] Routes: /staff/booking-deposits
- [x] Approve workflow: pending_approval → đang chờ
- [x] Auto-tạo hóa đơn khi approve
- [x] Mark đã thanh toán workflow: Chỉ khi payment_status = đang chờ và hóa đơn đã đã thanh toán
- [x] Tạo hợp đồng thuê từ booking deposit

### ⏳ CRM Modules (Nhóm 2)

#### ⏳ 08-leads.md
- [ ] Capabilities: crm.lead.tạo, crm.lead.cập nhật, crm.lead.xem
- [ ] Ownership filtering: view_all vs view_own
- [ ] Trạng thái values: new, contacted, qualified, converted, lost
- [ ] Routes: /staff/leads
- [ ] Lead to Khách thuê conversion

#### ⏳ 09-viewings.md
- [ ] Capabilities: crm.viewing.tạo, crm.viewing.cập nhật, crm.viewing.xem
- [ ] Ownership filtering: view_all vs view_own
- [ ] Trạng thái values: scheduled, completed, cancelled
- [ ] Routes: /staff/viewings
- [ ] Viewing to Booking Deposit workflow

#### ⏳ 07-khách thuê.md
- [ ] Capabilities: crm.khách thuê.tạo, crm.khách thuê.cập nhật, crm.khách thuê.xem
- [ ] Ownership filtering: view_all vs view_own
- [ ] Routes: /staff/tenants
- [ ] Khách thuê từ Lead auto-creation

### ⏳ Asset Modules (Nhóm 3)

#### ⏳ 03-bất động sản.md
- [ ] Capabilities: asset.bất động sản.tạo, asset.bất động sản.cập nhật, asset.bất động sản.xem
- [ ] Ownership filtering: view_all vs view_own
- [ ] Routes: /staff/properties

#### ⏳ 04-phòng.md
- [ ] Capabilities: asset.phòng.tạo, asset.phòng.cập nhật, asset.phòng.xem
- [ ] Ownership filtering: view_all vs view_own
- [ ] Trạng thái values: available, occupied, reserved, maintenance
- [ ] Routes: /staff/units

#### ⏳ 15-meters.md
- [ ] Capabilities: asset.meter.tạo, asset.meter.cập nhật, asset.meter.xem
- [ ] Ownership filtering: view_all vs view_own
- [ ] Routes: /staff/meters

#### ⏳ 16-meter-readings.md
- [ ] Capabilities: asset.meter_reading.tạo, asset.meter_reading.cập nhật, asset.meter_reading.xem
- [ ] Ownership filtering: view_all vs view_own
- [ ] Routes: /staff/meter-readings

### ⏳ Finance Modules (Nhóm 4)

#### ⏳ 18-commission-policies.md
- [ ] Capabilities: finance.commission_policy.tạo, finance.commission_policy.cập nhật
- [ ] Routes: /staff/commission-policies

#### ⏳ 19-commission-events.md
- [ ] Capabilities: finance.commission_event.xem
- [ ] Routes: /staff/commission-events

#### ⏳ 20-salary-hợp đồng.md
- [ ] Capabilities: finance.salary_contract.tạo, finance.salary_contract.cập nhật
- [ ] Routes: /staff/salary-contracts

#### ⏳ 21-salary-advances.md
- [ ] Capabilities: finance.salary_advance.tạo, finance.salary_advance.cập nhật
- [ ] Routes: /staff/salary-advances

#### ⏳ 22-payroll-cycles.md
- [ ] Capabilities: finance.payroll_cycle.tạo, finance.payroll_cycle.cập nhật
- [ ] Routes: /staff/payroll-cycles
- [ ] Format: YYYY-MM

#### ⏳ 23-payroll-payslips.md
- [ ] Capabilities: finance.payroll_payslip.xem
- [ ] Routes: /staff/payroll-payslips

#### ⏳ 24-company-hóa đơn.md
- [ ] Capabilities: finance.company_invoice.tạo, finance.company_invoice.cập nhật
- [ ] Routes: /staff/company-invoices
- [ ] Trạng thái values: draft, đang chờ, đã phê duyệt, đã thanh toán, overdue, cancelled

#### ⏳ 25-cash-outflows.md
- [ ] Capabilities: finance.cash_outflow.tạo, finance.cash_outflow.cập nhật
- [ ] Routes: /staff/cash-outflows
- [ ] Trạng thái values: đang chờ, success, failed, reversed

#### ⏳ 11-deposit-refunds.md
- [ ] Capabilities: hợp đồng.deposit_refund.tạo, hợp đồng.deposit_refund.cập nhật
- [ ] Routes: /staff/deposit-refunds
- [ ] Trạng thái values: đang chờ, đã phê duyệt, đã thanh toán, cancelled

### ⏳ Cài đặt và Utilities (Nhóm 5)

#### ⏳ 29-capabilities.md
- [ ] Capability system tổng quan
- [ ] Quản lý wildcard `*` = true
- [ ] Môi giới capabilities và overrides
- [ ] view_own vs view_all

#### ⏳ 35-cài đặt.md
- [ ] Routes: /staff/system-settings
- [ ] Tổ chức cài đặt
- [ ] Email cài đặt

#### ⏳ 33-sepay.md
- [ ] Routes: /staff/sepay
- [ ] SePay cấu hình
- [ ] Webhook setup

#### ⏳ 34-thông báo.md
- [ ] Routes: /staff/notifications
- [ ] Notification preferences
- [ ] Email cài đặt

#### ⏳ 32-thanh toán-cycle-cài đặt.md
- [ ] Routes: /staff/payment-cycle-settings
- [ ] Thanh toán cycle management

#### ⏳ 02-bảng điều khiển.md
- [ ] Capabilities cho từng module
- [ ] Thống kê calculation
- [ ] Module truy cập control

### ⏳ Other Modules

#### ⏳ 14-tickets.md
- [ ] Capabilities: maintenance.ticket.tạo, maintenance.ticket.cập nhật
- [ ] Routes: /staff/tickets
- [ ] Trạng thái values: open, in_progress, resolved, closed, cancelled

#### ⏳ 17-reviews.md
- [ ] Capabilities: crm.review.xem
- [ ] Routes: /staff/reviews

#### ⏳ 06-master-hợp đồng thuê.md
- [ ] Capabilities: hợp đồng.master_lease.tạo, hợp đồng.master_lease.cập nhật
- [ ] Routes: /staff/master-leases

#### ⏳ 26-nhà cung cấp.md
- [ ] Capabilities: finance.nhà cung cấp.tạo, finance.nhà cung cấp.cập nhật
- [ ] Routes: /staff/vendors

#### ⏳ 27-nhân viên.md
- [ ] Capabilities: nhân viên.tạo, nhân viên.cập nhật
- [ ] Routes: /staff/staff

#### ⏳ 28-người dùng.md
- [ ] Capabilities: người dùng.tạo, người dùng.cập nhật
- [ ] Routes: /staff/users

#### ⏳ 30-người dùng-banking.md
- [ ] Routes: /staff/user-banking

#### ⏳ 31-excel-xuất.md
- [ ] Routes: /staff/excel-export (nếu có)

## Các điểm chung cần cập nhật

### ✅ Đã cập nhật
- [x] Routes: Tất cả routes đã unified vào `/staff/*`
- [x] Capabilities: Đã cập nhật cho core modules
- [x] Trạng thái values: Đã cập nhật cho core modules
- [x] Format codes: Hóa đơn/Contract number format đã đúng

### ⏳ Cần cập nhật
- [ ] Capabilities cho tất cả modules còn lại
- [ ] Trạng thái values cho tất cả modules còn lại
- [ ] Ownership filtering cho tất cả modules
- [ ] Workflows và business rules
- [ ] Validation rules

## Notes

- Tất cả routes đã được unified vào `/staff/*`
- Quản lý có wildcard `*` = true (tất cả quyền)
- Môi giới cần capabilities cụ thể và có ownership filtering
- Trạng thái values đã được cập nhật theo Models
- Format codes đã được cập nhật theo implementation

## Tiếp theo Steps

1. Tiếp tục cập nhật CRM modules (Leads, Viewings, Khách thuê)
2. Cập nhật Asset modules (Bất động sản, Phòng, Meters)
3. Cập nhật Finance modules (Commission, Payroll, Company Hóa đơn)
4. Cập nhật Cài đặt và Utilities
5. Final review và consistency check

