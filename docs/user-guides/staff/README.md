# HƯỚNG DẪN SỬ DỤNG HỆ THỐNG - STAFF

## 📋 Tổng quan

Tài liệu này cung cấp hướng dẫn chi tiết về cách sử dụng hệ thống Quản lý Bất động sản Cho thuê dành cho **Nhân viên** (Quản lý và Môi giới). Hệ thống sử dụng unified routes `/staff/*` cho cả Quản lý và Môi giới, với phân quyền dựa trên capabilities.

### Đối tượng sử dụng

- **Quản lý**: Có quyền truy cập đầy đủ tất cả chức năng trong tổ chức
- **Môi giới**: Có quyền truy cập bị giới hạn bởi capabilities được Quản lý cấp

### Cấu trúc tài liệu

Tài liệu được tổ chức theo các nhóm chức năng chính:

1. **🔐 Xác thực & Bảng điều khiển** - Đăng nhập, đăng xuất, và tổng quan hệ thống
2. **🏢 Quản lý Tài sản (Asset Management)** - Bất động sản, Phòng, Meters
3. **👥 Quản lý Khách hàng (CRM)** - Leads, Viewings, Khách thuê, Reviews
4. **📄 Quản lý Hợp đồng (Hợp đồng Management)** - Hợp đồng thuê, Booking Deposits, Deposit Refunds
5. **💰 Quản lý Tài chính (Finance Management)** - Hóa đơn, Thanh toán, Commission, Payroll
6. **🔧 Bảo trì (Maintenance)** - Tickets, Meter Readings
7. **⚙️ Cài đặt & Tiện ích (Cài đặt & Utilities)** - Cài đặt, Capabilities, SePay, Thông báo

---

## 🚀 Bắt đầu nhanh

### 1. Đăng nhập hệ thống

Xem hướng dẫn chi tiết: [01-xác thực.md](./01-authentication.md)

- Truy cập `/login`
- Đăng nhập bằng Email/Password hoặc Google OAuth
- Hệ thống tự động redirect đến Bảng điều khiển

### 2. Khám phá Bảng điều khiển

Xem hướng dẫn chi tiết: [02-bảng điều khiển.md](./02-dashboard.md)

- Bảng điều khiển cung cấp tổng quan về hoạt động của tổ chức
- Tùy chỉnh modules hiển thị theo nhu cầu
- Xem thống kê, biểu đồ, và báo cáo

### 3. Tìm hiểu Capabilities

Xem hướng dẫn chi tiết: [29-capabilities.md](./29-capabilities.md)

- Quản lý có wildcard `*` = true (tất cả quyền)
- Môi giới cần capabilities cụ thể để truy cập chức năng
- Ownership filtering: `view_all` vs `view_own`

---

## 📚 Danh sách tài liệu theo nhóm

### 🔐 Xác thực & Bảng điều khiển

| # | Tài liệu | Mô tả | Route |
|---|----------|-------|-------|
| 01 | [Đăng nhập và Đăng xuất](./01-authentication.md) | Hướng dẫn đăng nhập, đăng xuất, và quản lý session | `/login`, `/logout` |
| 02 | [Bảng điều khiển](./02-dashboard.md) | Tổng quan hệ thống, thống kê, và ERP modules | `/staff/dashboard` |

---

### 🏢 Quản lý Tài sản (Asset Management)

| # | Tài liệu | Mô tả | Route |
|---|----------|-------|-------|
| 03 | [Quản lý Bất động sản](./03-properties.md) | Tạo, cập nhật, và quản lý bất động sản | `/staff/properties` |
| 04 | [Quản lý Phòng/Căn](./04-units.md) | Tạo, cập nhật, và quản lý phòng (phòng/căn) | `/staff/units` |
| 15 | [Quản lý Đồng hồ](./15-meters.md) | Tạo, cập nhật, và quản lý meters (đồng hồ điện/nước) | `/staff/meters` |
| 16 | [Quản lý Chỉ số Đồng hồ](./16-meter-readings.md) | Ghi nhận và quản lý meter readings | `/staff/meter-readings` |

---

### 👥 Quản lý Khách hàng (CRM)

| # | Tài liệu | Mô tả | Route |
|---|----------|-------|-------|
| 07 | [Quản lý Người thuê](./07-tenants.md) | Tạo, cập nhật, và quản lý khách thuê | `/staff/tenants` |
| 08 | [Quản lý Leads](./08-leads.md) | Tạo, cập nhật, và quản lý leads (khách hàng tiềm năng) | `/staff/leads` |
| 09 | [Quản lý Lịch xem phòng](./09-viewings.md) | Tạo, cập nhật, và quản lý viewings (appointments) | `/staff/viewings` |
| 17 | [Quản lý Đánh giá](./17-reviews.md) | Xem và quản lý reviews từ khách thuê | `/staff/reviews` |

---

### 📄 Quản lý Hợp đồng (Hợp đồng Management)

| # | Tài liệu | Mô tả | Route |
|---|----------|-------|-------|
| 05 | [Quản lý Hợp đồng thuê](./05-leases.md) | Tạo, cập nhật, terminate, renew hợp đồng thuê | `/staff/leases` |
| 06 | [Quản lý Hợp đồng Master](./06-master-leases.md) | Quản lý master hợp đồng thuê (hợp đồng với chủ nhà) | `/staff/master-leases` |
| 10 | [Quản lý Đặt cọc](./10-booking-deposits.md) | Tạo, approve, mark đã thanh toán, refund booking deposits | `/staff/booking-deposits` |
| 11 | [Quản lý Hoàn tiền cọc](./11-deposit-refunds.md) | Tạo và quản lý deposit refunds từ hợp đồng thuê | `/staff/deposit-refunds` |

---

### 💰 Quản lý Tài chính (Finance Management)

#### Billing (Hóa đơn & Thanh toán)

| # | Tài liệu | Mô tả | Route |
|---|----------|-------|-------|
| 12 | [Quản lý Hóa đơn](./12-invoices.md) | Tạo, issue, và quản lý hóa đơn | `/staff/invoices` |
| 13 | [Quản lý Thanh toán](./13-payments.md) | Tạo, cập nhật, và quản lý thanh toán | `/staff/payments` |
| 33 | [Cấu hình SePay](./33-sepay.md) | Cấu hình và quản lý SePay thanh toán gateway | `/staff/sepay` |

#### Commission & Payroll (Hoa hồng & Lương)

| # | Tài liệu | Mô tả | Route |
|---|----------|-------|-------|
| 18 | [Chính sách Hoa hồng](./18-commission-policies.md) | Tạo và quản lý commission policies | `/staff/commission-policies` |
| 19 | [Sự kiện Hoa hồng](./19-commission-events.md) | Xem và quản lý commission events | `/staff/commission-events` |
| 20 | [Hợp đồng Lương](./20-salary-contracts.md) | Tạo và quản lý salary hợp đồng | `/staff/salary-contracts` |
| 21 | [Tạm ứng Lương](./21-salary-advances.md) | Tạo và quản lý salary advances | `/staff/salary-advances` |
| 22 | [Chu kỳ Lương](./22-payroll-cycles.md) | Tạo và quản lý payroll cycles | `/staff/payroll-cycles` |
| 23 | [Phiếu Lương](./23-payroll-payslips.md) | Xem và quản lý payroll payslips | `/staff/payroll-payslips` |

#### Company Finance (Tài chính Công ty)

| # | Tài liệu | Mô tả | Route |
|---|----------|-------|-------|
| 24 | [Hóa đơn Công ty](./24-company-invoices.md) | Tạo và quản lý company hóa đơn | `/staff/company-invoices` |
| 25 | [Dòng tiền Chi ra](./25-cash-outflows.md) | Tạo và quản lý cash outflows | `/staff/cash-outflows` |
| 26 | [Quản lý Nhà cung cấp](./26-vendors.md) | Tạo và quản lý nhà cung cấp | `/staff/vendors` |

---

### 🔧 Bảo trì (Maintenance)

| # | Tài liệu | Mô tả | Route |
|---|----------|-------|-------|
| 14 | [Quản lý Ticket](./14-tickets.md) | Tạo, cập nhật, và quản lý maintenance tickets | `/staff/tickets` |

---

### ⚙️ Cài đặt & Tiện ích (Cài đặt & Utilities)

| # | Tài liệu | Mô tả | Route |
|---|----------|-------|-------|
| 27 | [Quản lý Nhân viên](./27-staff.md) | Tạo và quản lý nhân viên members | `/staff/staff` |
| 28 | [Quản lý Người dùng](./28-users.md) | Tạo và quản lý người dùng | `/staff/users` |
| 29 | [Quản lý Capabilities](./29-capabilities.md) | Cấp và quản lý capabilities cho Môi giới | `/staff/capabilities` |
| 30 | [Quản lý Tài khoản Ngân hàng](./30-user-banking.md) | Quản lý tài khoản ngân hàng của người dùng | `/staff/user-banking` |
| 31 | [Xuất Excel](./31-excel-export.md) | Hướng dẫn xuất dữ liệu ra Excel | `/staff/excel-export` |
| 32 | [Cài đặt Chu kỳ Thanh toán](./32-payment-cycle-settings.md) | Cấu hình thanh toán cycles | `/staff/payment-cycle-settings` |
| 34 | [Thông báo](./34-notifications.md) | Quản lý thông báo và preferences | `/staff/notifications` |
| 35 | [Cài đặt Hệ thống](./35-settings.md) | Cấu hình system cài đặt | `/staff/system-settings` |

---

## 🔄 Workflow chính

### Workflow Quản lý Thuê

```
Lead → Viewing → Booking Deposit → Lease → Invoice → Payment
```

1. **Lead**: Tạo lead từ khách hàng tiềm năng
2. **Viewing**: Đặt lịch xem phòng
3. **Booking Deposit**: Đặt cọc để giữ phòng
4. **Hợp đồng thuê**: Tạo hợp đồng thuê từ booking deposit
5. **Hóa đơn**: Tạo hóa đơn theo chu kỳ
6. **Thanh toán**: Ghi nhận thanh toán

### Workflow Quản lý Tài chính

```
Invoice → Payment → Commission Event → Payroll
```

1. **Hóa đơn**: Tạo hóa đơn cho khách thuê
2. **Thanh toán**: Ghi nhận thanh toán
3. **Commission Event**: Tính hoa hồng cho môi giới
4. **Payroll**: Tính lương và tạm ứng

---

## 🔑 Quyền truy cập và Capabilities

### Quản lý

- Có wildcard `*` = true (tất cả quyền)
- Truy cập đầy đủ tất cả chức năng trong tổ chức
- Có thể cấp capabilities cho Môi giới

### Môi giới

- Cần capabilities cụ thể để truy cập chức năng
- Ownership filtering:
  - `view_all`: Xem tất cả dữ liệu trong tổ chức
  - `view_own`: Chỉ xem dữ liệu được gán cho mình

Xem chi tiết: [29-capabilities.md](./29-capabilities.md)

---

## 📊 Thống kê và Báo cáo

### Bảng điều khiển Modules

Bảng điều khiển cung cấp các modules:

- **Tổng quan (Tổng quan)**: Thống kê tổng quan
- **Doanh thu (Revenue)**: Thống kê doanh thu
- **Khách hàng (Customers)**: Thống kê khách hàng
- **Bất động sản (Bất động sản)**: Thống kê bất động sản
- **Môi giới (Agents)**: Thống kê môi giới
- **Hợp đồng (Hợp đồng)**: Thống kê hợp đồng

Xem chi tiết: [02-bảng điều khiển.md](./02-dashboard.md)

---

## 🛠️ Tính năng nâng cao

### ERP Modules System

Hệ thống hỗ trợ ERP Modules System - cho phép Nhân viên chọn/bỏ chọn các modules để hiển thị trên bảng điều khiển.

### Excel Xuất

Xuất dữ liệu ra Excel để phân tích offline.

Xem chi tiết: [31-excel-xuất.md](./31-excel-export.md)

### SePay Integration

Tích hợp SePay thanh toán gateway để thanh toán online.

Xem chi tiết: [33-sepay.md](./33-sepay.md)

---

## 📝 Ghi chú quan trọng

### Routes

- Tất cả routes đã unified vào `/staff/*`
- Quản lý và Môi giới đều sử dụng cùng routes
- Phân quyền dựa trên role và capabilities

### Trạng thái Values

- **Booking Deposit**: `payment_status` (pending_approval, đang chờ, đã thanh toán, expired, cancelled, refunded)
- **Hóa đơn**: draft, issued, đã thanh toán, overdue, cancelled (KHÔNG có partially_paid)
- **Thanh toán**: đang chờ, success, failed, refunded (KHÔNG có đã thanh toán)
- **Hợp đồng thuê**: draft, hoạt động, terminated, expired

### Format Codes

- **Hóa đơn/Contract Number**: `HD{org_id}{YYYY}{MM}{XXXX}`
- **Payroll Cycle**: `YYYY-MM`

---

## 🔍 Tìm kiếm nhanh

### Theo chức năng

- **Tạo hợp đồng**: [05-hợp đồng thuê.md](./05-leases.md)
- **Tạo hóa đơn**: [12-hóa đơn.md](./12-invoices.md)
- **Ghi nhận thanh toán**: [13-thanh toán.md](./13-payments.md)
- **Quản lý đặt cọc**: [10-booking-deposits.md](./10-booking-deposits.md)
- **Quản lý leads**: [08-leads.md](./08-leads.md)
- **Quản lý tickets**: [14-tickets.md](./14-tickets.md)

### Theo vấn đề

- **Không thể đăng nhập**: [01-xác thực.md](./01-authentication.md#troubleshooting)
- **Không thấy dữ liệu**: [29-capabilities.md](./29-capabilities.md)
- **Bảng điều khiển không hiển thị**: [02-bảng điều khiển.md](./02-dashboard.md#troubleshooting)
- **Lỗi thanh toán**: [13-thanh toán.md](./13-payments.md#troubleshooting)

---

## 📞 Hỗ trợ

Nếu gặp vấn đề hoặc cần hỗ trợ:

1. Kiểm tra phần **Troubleshooting** trong từng tài liệu
2. Xem [FAQ](../common/04-faq.md) để tìm câu trả lời
3. Liên hệ Quản lý hoặc SuperAdmin

---

## 📅 Cập nhật

- **Phiên bản**: 2.1
- **Cập nhật lần cuối**: 2025-01-XX
- **Trạng thái**: Đang cập nhật

Xem tiến độ cập nhật: [UPDATE_PROGRESS.md](./UPDATE_PROGRESS.md)

---

## 🔗 Liên kết liên quan

- [Routes Mapping](../common/00-routes-mapping.md)
- [Glossary](../common/01-glossary.md)
- [Constraints](../common/02-constraints.md)
- [FAQ](../common/04-faq.md)

---

**Lưu ý**: Tài liệu này được cập nhật thường xuyên. Vui lòng kiểm tra phiên bản mới nhất trước khi sử dụng.

