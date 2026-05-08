# QUẢN LÝ GÓI ĐĂNG KÝ - SUPERADMIN

## Tổng quan

Chức năng này cho phép SuperAdmin quản lý các gói đăng ký (subscription plans) trong hệ thống SAAS, bao gồm tạo, xem, cập nhật, xóa, duplicate, và toggle trạng thái của plan.

## Quyền truy cập

- **SuperAdmin**: Có quyền quản lý tất cả subscription plans

**Route**: `/superadmin/subscription-plans`

## Các bước thực hiện

### 1. Xem danh sách gói đăng ký

1. Truy cập **Subscription Plans** từ menu SuperAdmin
2. Hệ thống hiển thị danh sách tất cả plans
3. Có thể lọc theo:
   - Trạng thái (hoạt động/inactive)
   - Loại (standard/custom)
   - Tìm kiếm theo name, code, description
   - Sắp xếp theo name, price_monthly, price_yearly, sort_order, created_at

### 2. Xem chi tiết gói đăng ký

1. Click vào tên plan hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - Thông tin cơ bản (code, name, description)
   - Pricing (price_monthly, price_yearly, currency)
   - Trial days
   - Trạng thái (hoạt động/inactive)
   - Features (max_properties, max_units, max_users, etc.)
   - Số lượng organizations đang sử dụng plan

### 3. Tạo gói đăng ký mới

1. Click **Tạo Plan** hoặc **+ New**
2. Điền thông tin:
   - **Code** (bắt buộc, unique): Mã định danh plan
   - **Name** (bắt buộc): Tên gói đăng ký
   - **Description** (tùy chọn): Mô tả
   - **Price Hàng tháng** (bắt buộc, >= 0): Giá hàng tháng
   - **Price Hàng năm** (bắt buộc, >= 0): Giá hàng năm
   - **Currency** (bắt buộc): Đơn vị tiền tệ (VND, USD, etc.)
   - **Trial Days** (bắt buộc, >= 0): Số ngày dùng thử
   - **Trạng thái** (bắt buộc): hoạt động hoặc không hoạt động
   - **Is Tùy chỉnh** (bắt buộc): tùy chỉnh plan hay standard plan
   - **Sắp xếp Order** (bắt buộc, >= 0): Thứ tự hiển thị
   - **Features** (bắt buộc, ít nhất 1 feature):
     - Feature Key (ví dụ: max_properties)
     - Feature Name (ví dụ: Tối đa số bất động sản)
     - Feature Loại (limit, boolean, json)
     - Feature Value (giá trị của feature)
3. Click **Lưu**
4. Hệ thống tạo plan và hiển thị thông báo thành công

### 4. Cập nhật gói đăng ký

1. Truy cập chi tiết plan cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin cần thay đổi
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Code phải unique
- Features có thể thêm/sửa/xóa

### 5. Xóa gói đăng ký (Soft Xóa)

1. Truy cập chi tiết plan cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa

**Lưu ý**: 
- Chỉ có thể xóa plan không có organizations đang sử dụng
- Soft xóa không xóa vĩnh viễn

### 6. Toggle Trạng thái (Kích hoạt/Vô hiệu hóa)

1. Truy cập danh sách plans hoặc chi tiết plan
2. Click **Toggle Trạng thái** hoặc switch **Trạng thái**
3. Hệ thống cập nhật trạng thái:
   - `active` → `inactive`
   - `inactive` → `active`

**Lưu ý**: 
- Plan không hoạt động không thể assign cho tổ chức mới
- Organizations đang sử dụng plan vẫn hoạt động bình thường

### 7. Duplicate Plan (Nhân bản Plan)

1. Truy cập chi tiết plan cần nhân bản
2. Click **Duplicate**
3. Hệ thống tạo plan mới với:
   - Code: `[original_code]_COPY_[timestamp]`
   - Name: `[original_name] (Copy)`
   - Is Tùy chỉnh: `true`
   - Features được copy từ plan gốc
4. Redirect đến trang chỉnh sửa plan mới
5. Có thể chỉnh sửa và lưu

## Ràng buộc và điều kiện

### Validation Rules

- **Code**: 
  - Bắt buộc
  - Phải unique trong hệ thống
  - Max 50 ký tự
- **Name**: 
  - Bắt buộc
  - Max 255 ký tự
- **Price Hàng tháng**: 
  - Bắt buộc
  - Phải >= 0
  - Numeric
- **Price Hàng năm**: 
  - Bắt buộc
  - Phải >= 0
  - Numeric
- **Currency**: 
  - Bắt buộc
  - Max 3 ký tự (VND, USD, etc.)
- **Trial Days**: 
  - Bắt buộc
  - Phải >= 0
  - Integer
- **Trạng thái**: 
  - Bắt buộc
  - Phải là `active` hoặc `inactive`
- **Is Tùy chỉnh**: 
  - Bắt buộc
  - Phải là boolean
- **Sắp xếp Order**: 
  - Bắt buộc
  - Phải >= 0
  - Integer
- **Features**: 
  - Bắt buộc
  - Phải có ít nhất 1 feature
  - Mỗi feature phải có: feature_key, feature_name, feature_type, feature_value

### Feature Types

1. **limit**: Giới hạn số lượng (ví dụ: max_properties = 10)
2. **boolean**: Tính năng có/không (ví dụ: allow_excel_export = true)
3. **json**: Dữ liệu phức tạp (ví dụ: allowed_payment_methods = ["cash", "bank_transfer"])

### Business Rules

1. **Không thể xóa plan có organizations đang sử dụng**
   - Phải hủy subscriptions trước
   - Hoặc assign plan khác cho organizations

2. **Auto-enforcement**
   - Middleware kiểm tra subscription limits
   - Không cho phép vượt quá limits
   - Ví dụ: max_properties = 10, không thể tạo bất động sản thứ 11

3. **Tùy chỉnh Plans**
   - Tùy chỉnh plans thường được tạo từ duplicate
   - Dùng cho trường hợp đặc biệt

## Trạng thái và Workflow

### Trạng thái Flow

```
active ←→ inactive
```

- **hoạt động**: Plan có thể được assign cho tổ chức mới
- **không hoạt động**: Plan không thể được assign cho tổ chức mới, nhưng organizations đang sử dụng vẫn hoạt động

### Workflow Tạo Plan

1. SuperAdmin tạo plan mới
2. Điền thông tin bắt buộc (Code, Name, Price, Currency, etc.)
3. Thêm Features (max_properties, max_units, max_users, etc.)
4. Set trạng thái = `active`
5. Hệ thống tạo plan
6. Có thể assign plan cho tổ chức

## Ví dụ

### Ví dụ 1: Tạo plan mới

**Thông tin plan:**
- Code: `BASIC`
- Name: `Gói Cơ Bản`
- Description: `Gói dành cho doanh nghiệp nhỏ`
- Price Hàng tháng: `500000` VND
- Price Hàng năm: `5000000` VND
- Currency: `VND`
- Trial Days: `7`
- Trạng thái: `active`
- Is Tùy chỉnh: `false`
- Sắp xếp Order: `1`

**Features:**
- max_properties: `10` (limit)
- max_units: `50` (limit)
- max_users: `5` (limit)
- allow_excel_export: `true` (boolean)
- allow_sepay: `true` (boolean)

**Các bước:**
1. Click **Tạo Plan**
2. Điền thông tin trên
3. Thêm các features
4. Click **Lưu**
5. Hệ thống tạo plan với ID tự động

### Ví dụ 2: Duplicate Plan

**Kịch bản:** Tạo tùy chỉnh plan từ plan cơ bản

**Các bước:**
1. Truy cập plan `BASIC`
2. Click **Duplicate**
3. Hệ thống tạo plan mới:
   - Code: `BASIC_COPY_1234567890`
   - Name: `Gói Cơ Bản (Copy)`
   - Is Tùy chỉnh: `true`
4. Chỉnh sửa thông tin (ví dụ: tăng max_properties lên 20)
5. Click **Lưu**

## Lưu ý

1. **Features là bắt buộc**
   - Mỗi plan phải có ít nhất 1 feature
   - Features định nghĩa giới hạn và tính năng của plan

2. **Auto-enforcement**
   - Hệ thống tự động kiểm tra limits khi tổ chức thực hiện thao tác
   - Không cho phép vượt quá limits đã định

3. **Tùy chỉnh Plans**
   - Tùy chỉnh plans thường được tạo từ duplicate
   - Dùng cho trường hợp đặc biệt, không theo chuẩn

4. **Pricing**
   - Price Hàng tháng và Price Hàng năm độc lập
   - Có thể set giá khác nhau cho hàng tháng và hàng năm

5. **Trial Days**
   - Trial days cho phép tổ chức dùng thử miễn phí
   - Sau khi hết trial, tổ chức phải thanh toán hoặc hủy subscription

## Troubleshooting

### Không thể tạo plan mới

1. Kiểm tra Code có bị trùng không
2. Kiểm tra tất cả các trường bắt buộc đã điền chưa
3. Kiểm tra Features có ít nhất 1 feature không
4. Kiểm tra Price >= 0
5. Liên hệ quản trị viên hệ thống

### Không thể xóa plan

1. Kiểm tra plan có organizations đang sử dụng không
2. Phải hủy subscriptions hoặc assign plan khác trước
3. Hệ thống chỉ hỗ trợ soft xóa

### Features không hoạt động

1. Kiểm tra feature_type có đúng không (limit, boolean, json)
2. Kiểm tra feature_value có đúng format không
3. Kiểm tra middleware có kiểm tra feature không

---

**Lưu ý**: Subscription plans là cơ sở của mô hình SAAS, cần cẩn thận khi tạo và cập nhật.

**Cập nhật**: 2025-11-02

