# CÀI ĐẶT - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) xem và cập nhật cài đặt (cài đặt) cho tổ chức, bao gồm general cài đặt, email cài đặt, thanh toán cài đặt, và các cài đặt khác.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ xem và cập nhật cài đặt cho tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `system.settings.access`
  - Xem cài đặt: Cần capability `system.settings.view` (mặc định có thể xem)
  - Cập nhật cài đặt: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/system-settings`

## Các bước thực hiện

### 1. Truy cập Cài đặt

1. Click **Cài đặt** từ menu Nhân viên
2. Hệ thống hiển thị trang cài đặt

### 2. Xem General Cài đặt (Cài đặt Chung)

1. Truy cập **Cài đặt** → **General**
2. Hệ thống hiển thị:
   - **Thông tin tổ chức:**
     - Tổ chức Name
     - Tổ chức Code
     - Phone, Email, Address
     - Logo
   - **Cài đặt khác:**
     - Timezone
     - Language
     - Currency
     - Ngày Format

### 3. Cập nhật General Cài đặt

1. Truy cập **Cài đặt** → **General**
2. Click **Chỉnh sửa** hoặc **Cập nhật**
3. Cập nhật thông tin:
   - Tổ chức Name, Phone, Email, Address
   - Logo (upload)
   - Timezone, Language, Currency, Ngày Format
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

### 4. Xem Email Cài đặt (Cài đặt Email)

1. Truy cập **Cài đặt** → **Email**
2. Hệ thống hiển thị:
   - **SMTP Cài đặt:**
     - SMTP Host
     - SMTP Port
     - SMTP Username
     - SMTP Password
     - Encryption (TLS, SSL)
     - From Email
     - From Name
   - **Email Templates:**
     - Hóa đơn Email Template
     - Thanh toán Email Template
     - Ticket Email Template

### 5. Cập nhật Email Cài đặt

1. Truy cập **Cài đặt** → **Email**
2. Click **Chỉnh sửa** hoặc **Cập nhật**
3. Cập nhật SMTP cài đặt và Email Templates
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

### 6. Xem Thanh toán Cài đặt (Cài đặt Thanh toán)

1. Truy cập **Cài đặt** → **Thanh toán**
2. Hệ thống hiển thị:
   - **SePay Cài đặt:**
     - Đã bật: Bật/tắt SePay
     - API Key
     - API Secret
     - Webhook URL
   - **Thanh toán Methods:**
     - Cash: Bật/tắt thanh toán tiền mặt
     - Bank Transfer: Bật/tắt chuyển khoản
     - SePay: Bật/tắt SePay

### 7. Cập nhật Thanh toán Cài đặt

1. Truy cập **Cài đặt** → **Thanh toán**
2. Click **Chỉnh sửa** hoặc **Cập nhật**
3. Cập nhật SePay cài đặt và Thanh toán Methods
4. Click **Lưu**
5. Cài đặt được cập nhật

### 8. Xem Other Cài đặt (Cài đặt Khác)

1. Truy cập **Cài đặt** → **Other**
2. Hệ thống hiển thị:
   - **Notification Cài đặt:**
     - Email Thông báo: Bật/tắt thông báo email
     - In-app Thông báo: Bật/tắt thông báo trong ứng dụng
   - **System Cài đặt:**
     - Maintenance Mode: Chế độ bảo trì
     - Debug Mode: Chế độ debug
     - Cache Cài đặt: Cài đặt cache

### 9. Cập nhật Other Cài đặt

1. Truy cập **Cài đặt** → **Other**
2. Click **Chỉnh sửa** hoặc **Cập nhật**
3. Cập nhật Notification Cài đặt và System Cài đặt
4. Click **Lưu**
5. Cài đặt được cập nhật

### 10. Test Email Cài đặt (Kiểm tra Email)

1. Truy cập **Cài đặt** → **Email**
2. Click **Test Email** hoặc **Kiểm tra Email**
3. Nhập email test
4. Click **Send**
5. Hệ thống gửi email test
6. Kiểm tra email đã nhận được chưa

## Ràng buộc và điều kiện

### Validation Rules

- **Tổ chức Name**: Bắt buộc, không được để trống
- **SMTP Host**: Bắt buộc nếu Email đã bật
- **SMTP Port**: Bắt buộc nếu Email đã bật
- **API Key**: Bắt buộc nếu SePay đã bật
- **API Secret**: Bắt buộc nếu SePay đã bật

### Business Rules

1. **Cài đặt Scope**
   - Cài đặt áp dụng cho toàn bộ tổ chức
   - Tất cả người dùng trong tổ chức bị ảnh hưởng

2. **Email Cài đặt**
   - Email cài đặt dùng để gửi email thông báo
   - Cần test email cài đặt trước khi sử dụng

3. **Thanh toán Cài đặt**
   - Thanh toán cài đặt điều khiển các phương thức thanh toán
   - SePay cần API Key và API Secret để hoạt động

## Ví dụ

### Ví dụ 1: Cập nhật General Cài đặt

**Kịch bản:** Quản lý muốn cập nhật thông tin tổ chức

**Các bước:**
1. Truy cập Cài đặt → General
2. Click **Chỉnh sửa**
3. Cập nhật:
   - Tổ chức Name: `Công ty ABC`
   - Phone: `0123456789`
   - Email: `contact@example.com`
   - Timezone: `Asia/Ho_Chi_Minh`/Ho_Chi_Minh`
   - Language: `vi`
   - Currency: `VND`
4. Click **Lưu**
5. Cài đặt được cập nhật

### Ví dụ 2: Test Email Cài đặt

**Kịch bản:** Quản lý muốn kiểm tra email cài đặt

**Các bước:**
1. Truy cập Cài đặt → Email
2. Click **Test Email**
3. Nhập email test: `test@example.com`
4. Click **Send**
5. Hệ thống gửi email test
6. Kiểm tra email đã nhận được chưa

---

**Xem thêm:**
- [Quản lý SePay](./33-sepay.md)
- [Quản lý Thanh toán Cycle Cài đặt](./32-payment-cycle-settings.md)

**Cập nhật: 2025-01-XX

