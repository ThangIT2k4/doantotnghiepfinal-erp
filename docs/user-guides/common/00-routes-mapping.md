# MAPPING ROUTES - HỆ THỐNG HIỆN TẠI

## Tổng quan

Tài liệu này mô tả mapping giữa routes cũ và routes mới sau khi unified Quản lý và Môi giới vào hệ thống `/staff/*`.

## Routes Mapping

### Quản lý Routes (Cũ → Mới)

| Chức năng | Route Cũ | Route Mới | Ghi chú |
|-----------|----------|-----------|---------|
| Bảng điều khiển | `/manager/dashboard` | `/staff/dashboard` | Unified |
| Bất động sản | `/manager/properties` | `/staff/properties` | Unified |
| Phòng | `/manager/units` | `/staff/units` | Unified |
| Hợp đồng thuê | `/manager/leases` | `/staff/leases` | Unified |
| Khách thuê | `/manager/tenants` | `/staff/tenants` | Unified |
| Leads | `/manager/leads` | `/staff/leads` | Unified |
| Viewings | `/manager/viewings` | `/staff/viewings` | Unified |
| Booking Deposits | `/manager/booking-deposits` | `/staff/booking-deposits` | Unified |
| Deposit Refunds | `/manager/deposit-refunds` | `/staff/deposit-refunds` | Unified |
| Hóa đơn | `/manager/invoices` | `/staff/invoices` | Unified |
| Thanh toán | `/manager/payments` | `/staff/payments` | Unified |
| Tickets | `/manager/tickets` | `/staff/tickets` | Unified |
| Meters | `/manager/meters` | `/staff/meters` | Unified |
| Meter Readings | `/manager/meter-readings` | `/staff/meter-readings` | Unified |
| Reviews | `/manager/reviews` | `/staff/reviews` | Unified |
| Commission Policies | `/manager/commission-policies` | `/staff/commission-policies` | Unified |
| Commission Events | `/manager/commission-events` | `/staff/commission-events` | Unified |
| Salary Hợp đồng | `/manager/salary-contracts` | `/staff/salary-contracts` | Unified |
| Salary Advances | `/manager/salary-advances` | `/staff/salary-advances` | Unified |
| Payroll Cycles | `/manager/payroll-cycles` | `/staff/payroll-cycles` | Unified |
| Payroll Payslips | `/manager/payroll-payslips` | `/staff/payroll-payslips` | Unified |
| Company Hóa đơn | `/manager/company-invoices` | `/staff/company-invoices` | Unified |
| Cash Outflows | `/manager/cash-outflows` | `/staff/cash-outflows` | Unified |
| Nhà cung cấp | `/manager/vendors` | `/staff/vendors` | Unified |
| Nhân viên | `/manager/staff` | `/staff/staff` | Unified |
| Người dùng | `/manager/users` | `/staff/users` | Unified |
| Capabilities | `/manager/capabilities` | `/staff/capabilities` | Unified |
| Người dùng Banking | `/manager/user-banking` | `/staff/user-banking` | Unified |
| Excel Xuất | `/manager/excel-export` | `/staff/excel-export` | Unified (không còn route riêng) |
| Cài đặt | `/manager/settings` | `/staff/system-settings` | Unified |
| Thông báo | `/manager/notifications` | `/staff/notifications` | Unified |
| Booking Deposit Cài đặt | - | `/staff/booking-deposit-settings` | Mới |
| Tổ chức Banking | `/manager/organization-banking` | `/staff/organization-banking` | Unified |
| Thanh toán Cycle Cài đặt | - | `/staff/payment-cycle-settings` | Mới |
| Hợp đồng thuê Service Cài đặt | - | `/staff/lease-service-settings` | Mới |
| Master Hợp đồng thuê | `/manager/master-leases` | `/staff/master-leases` | Unified |
| Financial Management | - | `/staff/financial-management` | Mới |
| Sepay Management | - | `/staff/sepay` | Mới |
| Webhook Logs | - | `/staff/webhook-logs` | Mới |

### Môi giới Routes (Cũ → Mới)

| Chức năng | Route Cũ | Route Mới | Ghi chú |
|-----------|----------|-----------|---------|
| Bảng điều khiển | `/agent/dashboard` | `/staff/dashboard` | Unified |
| Bất động sản | `/agent/properties` | `/staff/properties` | Read-only cho Môi giới |
| Phòng | `/agent/units` | `/staff/units` | Unified |
| Hợp đồng thuê | `/agent/leases` | `/staff/leases` | Unified |
| Khách thuê | `/agent/tenants` | `/staff/tenants` | Unified |
| Leads | `/agent/leads` | `/staff/leads` | Unified |
| Viewings | `/agent/viewings` | `/staff/viewings` | Unified |
| Booking Deposits | `/agent/booking-deposits` | `/staff/booking-deposits` | Unified |
| Deposit Refunds | `/agent/deposit-refunds` | `/staff/deposit-refunds` | Unified |
| Hóa đơn | `/agent/invoices` | `/staff/invoices` | Unified |
| Thanh toán | `/agent/payments` | `/staff/payments` | Unified |
| Tickets | `/agent/tickets` | `/staff/tickets` | Unified |
| Meters | `/agent/meters` | `/staff/meters` | Unified |
| Meter Readings | `/agent/meter-readings` | `/staff/meter-readings` | Unified |
| Reviews | `/agent/reviews` | `/staff/reviews` | Unified |
| Commission Policies | `/agent/commission-policies` | `/staff/commission-policies` | Unified |
| Commission Events | `/agent/commission-events` | `/staff/commission-events` | Unified |
| Salary Hợp đồng | `/agent/salary-contracts` | `/staff/salary-contracts` | Read-only cho Môi giới |
| Payroll Cycles | `/agent/payroll-cycles` | `/staff/payroll-cycles` | Read-only cho Môi giới |
| Payslips | `/agent/payslips` | `/staff/payroll-payslips` | Unified |
| Salary Advances | `/agent/salary-advances` | `/staff/salary-advances` | Unified |
| Revenue Báo cáo | `/agent/revenue-reports` | `/staff/revenue-reports` | Unified (không còn route riêng) |
| Thanh toán Báo cáo | `/agent/payment-reports` | `/staff/payment-reports` | Unified (không còn route riêng) |
| Người dùng Banking | `/agent/user-banking` | `/staff/user-banking` | Unified |
| Cài đặt | `/agent/settings` | `/staff/system-settings` | Unified |
| Thông báo | `/agent/notifications` | `/staff/notifications` | Unified |

### Routes Không Thay Đổi

| Vai trò | Routes | Ghi chú |
|---------|--------|---------|
| SuperAdmin | `/superadmin/*` | Không thay đổi |
| Khách thuê | `/tenant/*` | Không thay đổi |
| Landlord | `/landlord/*` | Không thay đổi |
| Public | `/`, `/login`, `/register` | Không thay đổi |

## Phân Quyền

### Quản lý
- Có quyền truy cập tất cả routes `/staff/*`
- Có wildcard capability (`*`) = tất cả quyền
- Có thể quản lý capabilities cho Môi giới

### Môi giới
- Truy cập routes `/staff/*` nhưng bị giới hạn bởi capabilities
- Chỉ thấy và thao tác với dữ liệu được Quản lý cấp quyền
- Không thể truy cập một số chức năng quản lý (như Nhân viên Management, Subscription)

## Backward Compatibility

Hệ thống vẫn hỗ trợ redirect từ routes cũ:

- `/manager/*` → redirect đến `/staff/*`
- `/agent/*` → redirect đến `/staff/*`

Tuy nhiên, khuyến nghị sử dụng routes mới `/staff/*` để đảm bảo tính nhất quán.

## Cách Sử Dụng Trong Tài Liệu

Khi viết tài liệu mới hoặc cập nhật tài liệu cũ:

1. **Luôn sử dụng routes mới**: `/staff/*` cho Quản lý và Môi giới
2. **Ghi chú phân quyền**: Nếu Môi giới có hạn chế, ghi rõ
3. **Tham chiếu capability**: Nếu cần capability cụ thể, ghi rõ

## Ví Dụ

### Trước (Cũ)
```
Manager truy cập: /manager/properties
Agent truy cập: /agent/properties
```/manager/properties
Môi giới truy cập: /agent/properties
```

### Sau (Mới)
```
Manager truy cập: /staff/properties
Agent truy cập: /staff/properties (với capability check)
```/staff/properties
Môi giới truy cập: /staff/properties (với capability check)
```

---

**Cập nhật**: 2025-01-XX  
**Phiên bản**: 2.1

