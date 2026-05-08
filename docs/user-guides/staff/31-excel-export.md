# XUẤT DỮ LIỆU EXCEL - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) xuất dữ liệu Excel từ các bảng dữ liệu trong hệ thống.

## Quyền truy cập

- **Quản lý**: Có quyền xuất dữ liệu Excel cho tất cả modules (wildcard `*` = true)
- **Môi giới**: 
  - Xuất dữ liệu Excel: Cần capability `system.excel.export`
  - Chỉ xuất được dữ liệu của chính mình hoặc modules được cấp quyền (không phải toàn bộ tổ chức)

**Route**: `/staff/excel-export`

## Các bước thực hiện

### 1. Xuất Excel từ danh sách

1. Truy cập bất kỳ module nào (Bất động sản, Phòng, Hợp đồng thuê, Hóa đơn, Thanh toán, etc.)
2. Lọc dữ liệu nếu cần (theo trạng thái, ngày range, etc.)
3. Click **Xuất Excel** hoặc **Xuất Excel**
4. Hệ thống tạo file Excel với dữ liệu hiện tại
5. File Excel được tải về máy

### 2. Xuất Excel từ chi tiết

1. Truy cập chi tiết bất kỳ record nào
2. Scroll đến phần có thể xuất (Items, Logs, etc.)
3. Click **Xuất Excel** hoặc **Xuất Excel**
4. Hệ thống tạo file Excel với dữ liệu tương ứng
5. File Excel được tải về máy

### 3. Các module hỗ trợ Xuất Excel

- **Bất động sản**: Danh sách bất động sản
- **Phòng**: Danh sách phòng/căn
- **Hợp đồng thuê**: Danh sách hợp đồng
- **Khách thuê**: Danh sách người thuê
- **Leads**: Danh sách leads
- **Hóa đơn**: Danh sách hóa đơn
- **Thanh toán**: Danh sách thanh toán
- **Tickets**: Danh sách tickets
- **Meters**: Danh sách công tơ
- **Meter Readings**: Danh sách chỉ số công tơ
- **Commission Events**: Danh sách sự kiện hoa hồng
- **Payroll Payslips**: Danh sách phiếu lương
- **Cash Outflows**: Danh sách dòng tiền chi
- **Company Hóa đơn**: Danh sách hóa đơn công ty

### 4. Xuất với Filters

1. Áp dụng filters trên danh sách (trạng thái, ngày range, etc.)
2. Click **Xuất Excel**
3. File Excel chỉ chứa dữ liệu đã lọc
4. File Excel được tải về máy

**Lưu ý**: 
- Xuất Excel sẽ xuất dữ liệu hiện tại (đã lọc)
- Nếu không lọc, xuất tất cả dữ liệu

### 5. Xuất Format

File Excel được xuất với format:
- **Sheet name**: Tên module (ví dụ: "Bất động sản", "Hợp đồng thuê")
- **Headers**: Tiêu đề cột (Name, Trạng thái, Ngày, etc.)
- **Data rows**: Dữ liệu tương ứng
- **File extension**: `.xlsx`
- **File name**: `{module_name}_{date}.xlsx` (ví dụ: `Properties_2025-01-15.xlsx`)

## Ràng buộc và điều kiện

### Validation Rules

- Không có validation (chỉ xuất dữ liệu)

### Business Rules

1. **Xuất Data Scope**
   - Xuất chỉ chứa dữ liệu người dùng có quyền truy cập
   - Dữ liệu được lọc theo tổ chức

2. **Xuất Size**
   - Xuất có thể chứa nhiều dữ liệu
   - File Excel có thể lớn nếu có nhiều dữ liệu

3. **Xuất Performance**
   - Xuất có thể mất vài giây nếu có nhiều dữ liệu
   - Hệ thống hiển thị thông báo khi xuất thành công

## Ví dụ

### Ví dụ 1: Xuất Bất động sản Excel

**Kịch bản:** Quản lý muốn xuất danh sách bất động sản ra Excel

**Các bước:**
1. Truy cập Bất động sản
2. Lọc dữ liệu nếu cần (theo trạng thái, city, etc.)
3. Click **Xuất Excel**
4. Hệ thống tạo file Excel `Properties_2025-01-15.xlsx`
5. File Excel được tải về máy
6. File chứa:
   - Headers: Name, Code, Loại, Trạng thái, Address, etc.
   - Data rows: Tất cả bất động sản (đã lọc)

### Ví dụ 2: Xuất Hóa đơn Excel với Filters

**Kịch bản:** Quản lý muốn xuất hóa đơn tháng 1/2025

**Các bước:**
1. Truy cập Hóa đơn
2. Lọc:
   - Issue Ngày: 2025-01-01 đến 2025-01-31
   - Trạng thái: `issued`, `paid`, `overdue`
3. Click **Xuất Excel**
4. Hệ thống tạo file Excel `Invoices_2025-01-15.xlsx`
5. File Excel chỉ chứa hóa đơn tháng 1/2025 với trạng thái đã chọn

---

**Xem thêm:**
- [Quản lý Bất động sản](./03-properties.md)
- [Quản lý Hóa đơn](./12-invoices.md)

**Cập nhật: 2025-01-XX

