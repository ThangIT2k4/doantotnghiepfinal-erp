# CÀI ĐẶT CHU KỲ THANH TOÁN - STAFF

## Tổng quan

Chức năng này cho phép Quản lý cấu hình cài đặt chu kỳ thanh toán (thanh toán cycle cài đặt) cho tổ chức.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ cấu hình thanh toán cycle cài đặt (wildcard `*` = true)
- **Môi giới**: Không có quyền truy cập chức năng này (chỉ Quản lý)

**Route**: `/staff/payment-cycle-settings`

## Các bước thực hiện

### 1. Truy cập Thanh toán Cycle Cài đặt

1. Truy cập **Cài đặt** → **Thanh toán Cycle** từ menu Nhân viên
2. Hệ thống hiển thị trang cài đặt chu kỳ thanh toán

### 2. Cấu hình Thanh toán Cycle

1. Điền thông tin:
   - **Mặc định Thanh toán Cycle** (bắt buộc): Chu kỳ thanh toán mặc định (hàng tháng, hàng quý, hàng năm, tùy chỉnh)
   - **Mặc định Thanh toán Day** (bắt buộc): Ngày thanh toán mặc định (1-31)
   - **Mặc định Billing Day** (bắt buộc): Ngày tạo hóa đơn mặc định (1-28)
   - **Auto-tạo Hóa đơn** (bắt buộc): Tự động tạo hóa đơn (có/no)
   - **Hóa đơn Generation Thời gian** (nếu Auto-tạo = có): Thời gian tạo hóa đơn (ví dụ: 00:00)
2. Click **Lưu**
3. Cài đặt được lưu cho tổ chức

**Lưu ý**: 
- Cài đặt áp dụng cho tất cả hợp đồng thuê mới
- Hợp đồng thuê hiện tại không bị ảnh hưởng (trừ khi cập nhật thủ công)

### 3. Cấu hình Auto-tạo Hóa đơn

1. Bật **Auto-tạo Hóa đơn**
2. Cấu hình **Hóa đơn Generation Thời gian**: Thời gian tự động tạo hóa đơn
3. Hệ thống tự động tạo hóa đơn cho hợp đồng thuê theo thanh toán cycle
4. Click **Lưu**
5. Cài đặt được lưu

**Lưu ý**: 
- Auto-tạo hóa đơn chạy tự động (cron job)
- Hóa đơn được tạo với trạng thái `draft` cho đến khi Quản lý issue

### 4. Cập nhật Thanh toán Cycle Cài đặt

1. Truy cập **Cài đặt** → **Thanh toán Cycle**
2. Click **Chỉnh sửa** hoặc **Cập nhật**
3. Cập nhật thông tin
4. Click **Lưu**
5. Cài đặt được cập nhật

### 5. Xem Thống kê

1. Truy cập **Cài đặt** → **Thanh toán Cycle** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Hợp đồng thuê by Thanh toán Cycle: Phân bố theo chu kỳ thanh toán
   - Auto-created Hóa đơn: Số hóa đơn được tạo tự động
   - Manual Hóa đơn: Số hóa đơn được tạo thủ công

## Ràng buộc và điều kiện

### Validation Rules

- **Mặc định Thanh toán Cycle**: Bắt buộc, phải là một trong: hàng tháng, hàng quý, hàng năm, tùy chỉnh
- **Mặc định Thanh toán Day**: Bắt buộc, phải là số từ 1-31
- **Mặc định Billing Day**: Bắt buộc, phải là số từ 1-28
- **Hóa đơn Generation Thời gian**: Bắt buộc nếu Auto-tạo = có, phải là thời gian hợp lệ

### Business Rules

1. **Thanh toán Cycle**
   - `monthly`: Thanh toán hàng tháng
   - `quarterly`: Thanh toán hàng quý
   - `yearly`: Thanh toán hàng năm
   - `custom`: Thanh toán tùy chỉnh (số tháng)

2. **Auto-tạo Hóa đơn**
   - Nếu bật: Hệ thống tự động tạo hóa đơn theo thanh toán cycle
   - Nếu tắt: Quản lý tạo hóa đơn thủ công

3. **Billing Day vs Thanh toán Day**
   - Billing Day: Ngày tạo hóa đơn (thường < Thanh toán Day)
   - Thanh toán Day: Ngày thanh toán (đến hạn ngày của hóa đơn)

## Ví dụ

### Ví dụ 1: Cấu hình Thanh toán Cycle Cài đặt

**Cài đặt:**
- Mặc định Thanh toán Cycle: `monthly`
- Mặc định Thanh toán Day: `5` (ngày 5 hàng tháng)
- Mặc định Billing Day: `1` (ngày 1 hàng tháng)
- Auto-tạo Hóa đơn: `yes`
- Hóa đơn Generation Thời gian: `00:00`

**Các bước:**
1. Truy cập Cài đặt → Thanh toán Cycle
2. Chọn Mặc định Thanh toán Cycle: `monthly`
3. Nhập Mặc định Thanh toán Day: `5`
4. Nhập Mặc định Billing Day: `1`
5. Bật Auto-tạo Hóa đơn
6. Nhập Hóa đơn Generation Thời gian: `00:00`
7. Click **Lưu**
8. Cài đặt được lưu
9. Hệ thống tự động tạo hóa đơn vào ngày 1 hàng tháng lúc 00:00

---

**Xem thêm:**
- [Quản lý Hợp đồng thuê](./05-leases.md)
- [Quản lý Hóa đơn](./12-invoices.md)

**Cập nhật: 2025-01-XX

