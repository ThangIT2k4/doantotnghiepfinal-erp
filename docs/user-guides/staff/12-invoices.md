# QUẢN LÝ HÓA ĐƠN - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý hóa đơn (hóa đơn) trong tổ chức, bao gồm tạo (manual/auto), xem, cập nhật, xóa, issue, hủy, và quản lý items.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả hóa đơn trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `billing.access`
  - Tạo hóa đơn: Cần capability `billing.invoice.create`
  - Cập nhật hóa đơn: Cần capability `billing.invoice.update`
  - Xem tất cả hóa đơn: Cần capability `billing.invoice.view` hoặc `billing.invoice.view_all`
  - Chỉ xem hóa đơn của hợp đồng thuê được gán: Có capability `billing.invoice.view_own` (mặc định)
  - Xóa hóa đơn: Cần capability `billing.invoice.delete`
  - Issue hóa đơn: Cần capability `billing.invoice.update`

**Route**: `/staff/invoices`

## Các bước thực hiện

### 1. Xem danh sách Hóa đơn

1. Truy cập **Hóa đơn** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả hóa đơn trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (draft, issued, đã thanh toán, overdue, cancelled)
   - Hợp đồng thuê (nếu có nhiều hợp đồng thuê)
   - Khách thuê (nếu có nhiều khách thuê)
   - Đến hạn Ngày (today, this week, this month, overdue, tùy chỉnh range)
   - Hóa đơn Loại (khách thuê, landlord)
   - Sắp xếp theo issue_date, due_date, số tiền, trạng thái

### 2. Xem chi tiết Hóa đơn

1. Click vào hóa đơn trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Hóa đơn Number: Số hóa đơn (format: HD{org_id}{YYYY}{MM}{XXXX}, ví dụ: HD32025110001)
     - Hợp đồng thuê hoặc Booking Deposit: Hợp đồng hoặc đặt cọc liên quan
     - Bất động sản, Phòng: Bất động sản và phòng
     - Khách thuê: Người thuê
     - Issue Ngày, Đến hạn Ngày: Ngày phát hành và hạn thanh toán
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin tài chính:**
     - Items: Danh sách các khoản phí (rent, service, meter, deposit, ticket_cost, other)
     - Subtotal: Tổng tiền
     - Tax Số tiền: Thuế
     - Discount Số tiền: Giảm giá
     - Tổng Số tiền: Tổng cộng
     - Đã thanh toán Số tiền: Đã thanh toán
     - Remaining Số tiền: Còn nợ
   - **Thông tin khác:**
     - Note: Ghi chú
     - Thanh toán: Danh sách thanh toán (nếu có)
     - Created At, Updated At: Thời gian tạo và cập nhật

### 3. Tạo Hóa đơn thủ công (Manual)

1. Click **Tạo Hóa đơn** hoặc **+ New**
2. Chọn **Creation Mode**: `Manual`
3. Điền thông tin:
   - **Hợp đồng thuê hoặc Booking Deposit** (bắt buộc): Chọn hợp đồng hoặc đặt cọc
   - **Hóa đơn Loại** (bắt buộc): `monthly_rent`, `first_invoice`, `booking_deposit`, hoặc `other`
   - **Issue Ngày** (bắt buộc): Ngày phát hành
   - **Đến hạn Ngày** (bắt buộc): Ngày hạn thanh toán (phải >= Issue Ngày)
   - **Hóa đơn Number** (tự động): Số hóa đơn (tự động generate nếu không nhập, format: HD{org_id}{YYYY}{MM}{XXXX})
   - **Items** (bắt buộc, ít nhất 1 item):
     - Item Loại: rent, service, meter, deposit, ticket_cost, other
     - Description: Mô tả
     - Quantity: Số lượng
     - Phòng Price: Giá đơn vị
     - Số tiền: Thành tiền (Quantity × Phòng Price)
   - **Subtotal** (tự động): Tổng tiền các items
   - **Tax Số tiền** (tùy chọn): Thuế
   - **Discount Số tiền** (tùy chọn): Giảm giá
   - **Tổng Số tiền** (tự động): Subtotal + Tax - Discount
   - **Note** (tùy chọn): Ghi chú
   - **Trạng thái** (tự động): `draft`
4. Click **Lưu**
5. Hóa đơn được tạo với trạng thái `draft`

### 4. Tạo Hóa đơn tự động (Auto-tạo)

Hệ thống tự động tạo hóa đơn theo Thanh toán Cycle của Hợp đồng thuê:

1. Hệ thống kiểm tra Thanh toán Cycle của Hợp đồng thuê:
   - Hàng tháng: Mỗi tháng 1 hóa đơn
   - Hàng quý: Mỗi quý 1 hóa đơn
   - Hàng năm: Mỗi năm 1 hóa đơn
   - Tùy chỉnh: Theo số tháng đã cấu hình
2. Vào Billing Day mỗi chu kỳ, hệ thống tự động tạo hóa đơn:
   - Hợp đồng thuê được lấy từ hoạt động hợp đồng thuê
   - Issue Ngày = Billing Day
   - Đến hạn Ngày = Issue Ngày + Thanh toán Day
   - Items được tạo từ:
     - Rent: Theo chu kỳ
     - Services: Từ hợp đồng thuê services
     - Meter Readings: Từ meter readings chưa hóa đơn
   - Trạng thái = `draft`
3. Quản lý hoặc Môi giới có thể review và issue hóa đơn

**Lưu ý**: 
- Auto-tạo hóa đơn được tạo tự động, không cần thao tác thủ công
- Hóa đơn có trạng thái `draft` cho đến khi Quản lý/Agent issue

### 5. Issue Hóa đơn (Phát hành Hóa đơn)

1. Truy cập chi tiết hóa đơn có trạng thái `draft`
2. Review thông tin hóa đơn:
   - Items: Kiểm tra các khoản phí
   - Số tiền: Kiểm tra tổng tiền
   - Đến hạn Ngày: Kiểm tra hạn thanh toán
3. Click **Issue** hoặc **Phát hành**
4. Hóa đơn trạng thái chuyển sang `issued`
5. Hệ thống gửi thông báo cho Khách thuê (email/in-app)
6. Hệ thống gửi thông báo cho Môi giới và Quản lý

**Lưu ý**: 
- Chỉ có thể issue hóa đơn có trạng thái `draft`
- Hóa đơn được issue không thể chỉnh sửa (trừ khi hủy)

### 6. Cập nhật Hóa đơn

1. Truy cập chi tiết hóa đơn cần cập nhật
2. Click **Chỉnh sửa** (chỉ khi trạng thái = `draft`)
3. Cập nhật thông tin:
   - Items: Thêm, sửa, xóa items
   - Tax Số tiền, Discount Số tiền
   - Note
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Chỉ có thể cập nhật hóa đơn có trạng thái `draft`
- Không thể cập nhật hóa đơn đã `issued`, `paid`, hoặc `cancelled`
- Có thể thêm items từ ticket costs, meter readings

### 7. Thêm Hóa đơn Items

1. Truy cập chi tiết hóa đơn có trạng thái `draft`
2. Click **Add Item** hoặc **Thêm khoản phí**
3. Điền thông tin:
   - **Item Loại**: rent, service, meter, deposit, ticket_cost, other
   - **Description**: Mô tả
   - **Quantity**: Số lượng
   - **Phòng Price**: Giá đơn vị
   - **Số tiền**: Thành tiền (tự động = Quantity × Phòng Price)
4. Click **Add**
5. Item được thêm vào hóa đơn
6. Tổng Số tiền tự động cập nhật

### 8. Hủy Hóa đơn (Hủy Hóa đơn)

1. Truy cập chi tiết hóa đơn cần hủy
2. Click **Hủy** hoặc **Hủy**
3. (Tùy chọn) Nhập lý do hủy
4. Xác nhận hủy
5. Hóa đơn trạng thái chuyển sang `cancelled`
6. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Chỉ có thể hủy hóa đơn có trạng thái `draft` hoặc `issued`
- Không thể hủy hóa đơn đã `paid`
- Hóa đơn không có trạng thái `partially_paid`, hệ thống tự tính remaining số tiền
- Hủy hóa đơn sẽ không được tính vào revenue

### 9. Xóa Hóa đơn

1. Truy cập chi tiết hóa đơn cần xóa
2. Click **Xóa** (chỉ khi trạng thái = `draft` và chưa có thanh toán)
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa hóa đơn

**Lưu ý**: 
- Chỉ có thể xóa hóa đơn có trạng thái `draft` và chưa có thanh toán
- Không thể xóa hóa đơn đã `issued`, `paid`, hoặc có thanh toán

### 10. Download Hóa đơn

1. Truy cập chi tiết hóa đơn
2. Click **Download** hoặc **Tải xuống**
3. Hệ thống tạo file PDF hoặc Word
4. Hệ thống tải file về máy

**Lưu ý**: 
- File có thể là PDF hoặc Word tùy cấu hình
- File chứa thông tin đầy đủ của hóa đơn

## Ràng buộc và điều kiện

### Validation Rules

- **Hợp đồng thuê**: 
  - Bắt buộc
  - Phải tồn tại và thuộc về tổ chức
- **Hóa đơn Number**: 
  - Tự động generate nếu không nhập
  - Phải unique trong tổ chức (format: HD{org_id}{YYYY}{MM}{XXXX}, ví dụ: HD32025110001)
- **Issue Ngày**: 
  - Bắt buộc
  - Phải là ngày hợp lệ
- **Đến hạn Ngày**: 
  - Bắt buộc
  - Phải >= Issue Ngày
- **Items**: 
  - Bắt buộc
  - Phải có ít nhất 1 item
  - Mỗi item phải có: Item Loại, Description, Quantity, Phòng Price, Số tiền
- **Tổng Số tiền**: 
  - Tự động = Subtotal + Tax Số tiền - Discount Số tiền
  - Phải >= 0

### Business Rules

1. **Hóa đơn Number phải unique**
   - Không thể có 2 hóa đơn cùng số trong tổ chức
   - Hóa đơn Number được auto-generate theo format HD-YYYYMM-XXXX

2. **Hóa đơn phải có ít nhất 1 item**
   - Không thể tạo hóa đơn không có items
   - Phải có ít nhất 1 item (rent, service, meter, etc.)

3. **Chỉ có thể cập nhật hóa đơn có trạng thái `draft`**
   - Không thể cập nhật hóa đơn đã `issued`, `paid`, hoặc `cancelled`
   - Phải hủy hóa đơn trước nếu muốn thay đổi

4. **Auto-tạo Hóa đơn**
   - Hóa đơn được tạo tự động theo Thanh toán Cycle
   - Hóa đơn có trạng thái `draft` cho đến khi issue

5. **Hóa đơn Trạng thái Flow**
   - `draft` → `issued` → `paid`/`overdue`/`cancelled`
   - Hệ thống tự động tính remaining số tiền (total_amount - paid_amount), không có trạng thái `partially_paid`

## Trạng thái và Workflow

### Hóa đơn Trạng thái Flow

```
draft → issued → paid/overdue/cancelled
```/overdue/cancelled
```

- **draft**: Hóa đơn đang soạn thảo
- **issued**: Hóa đơn đã phát hành, chờ thanh toán
- **đã thanh toán**: Hóa đơn đã thanh toán đủ (tự động khi total_paid >= total_amount)
- **overdue**: Hóa đơn quá hạn thanh toán (tự động khi due_date < now() và chưa đã thanh toán)
- **cancelled**: Hóa đơn đã hủy

**Lưu ý**: Hệ thống không có trạng thái `partially_paid`. Remaining số tiền được tính tự động = total_amount - paid_amount.

### Workflow Tạo Hóa đơn

1. Quản lý hoặc Môi giới (có quyền) tạo hóa đơn thủ công hoặc hệ thống auto-tạo
2. Điền thông tin: Hợp đồng thuê/Booking Deposit, Items, Amounts, Dates
3. Click Lưu → Hóa đơn có trạng thái `draft`
4. Quản lý hoặc Môi giới (có quyền) issue hóa đơn → Trạng thái `issued`
5. Khách thuê thanh toán → Hệ thống tự động cập nhật paid_amount, trạng thái chuyển sang `paid` khi thanh toán đủ
6. Hoặc Quản lý/Agent hủy hóa đơn → Trạng thái `cancelled`

## Ví dụ

### Ví dụ 1: Tạo Hóa đơn thủ công

**Thông tin hóa đơn:**
- Hợp đồng thuê: HD32025110001
- Hóa đơn Loại: `monthly_rent`
- Issue Ngày: 2025-01-01
- Đến hạn Ngày: 2025-01-05
- Items:
  - Rent: 10,000,000 VND
  - Electricity: 150,000 VND (50 kWh × 3,000 VND/kWh)
  - Water: 300,000 VND (20 m³ × 15,000 VND/m³)
- Subtotal: 10,450,000 VND
- Tax Số tiền: 0 VND
- Discount Số tiền: 0 VND
- Tổng Số tiền: 10,450,000 VND
- Trạng thái: `draft`

**Các bước:**
1. Truy cập Hóa đơn
2. Click **Tạo Hóa đơn**
3. Chọn Hợp đồng thuê: HD-202501-0001
4. Điền thông tin trên
5. Thêm Items
6. Click **Lưu**
7. Hóa đơn được tạo với trạng thái `draft`

### Ví dụ 2: Issue Hóa đơn

**Kịch bản:** Quản lý muốn phát hành hóa đơn

**Các bước:**
1. Truy cập chi tiết hóa đơn có trạng thái `draft`
2. Review thông tin hóa đơn
3. Click **Issue**
4. Hóa đơn trạng thái chuyển sang `issued`
5. Hệ thống gửi thông báo cho Khách thuê

## Lưu ý

1. **Auto-tạo Hóa đơn**
   - Hóa đơn được tạo tự động theo Thanh toán Cycle
   - Review và issue hóa đơn sau khi auto-tạo

2. **Hóa đơn Items**
   - Thêm items từ ticket costs, meter readings
   - Items có thể được thêm sau khi tạo hóa đơn

3. **Trạng thái Management**
   - Chỉ có thể cập nhật hóa đơn có trạng thái `draft`
   - Hủy hóa đơn nếu cần thay đổi sau khi issue

4. **Hóa đơn Number**
   - Hóa đơn Number được auto-generate
   - Format: HD{org_id}{YYYY}{MM}{XXXX} (ví dụ: HD32025110001)

## Troubleshooting

### Không thể tạo hóa đơn

1. Kiểm tra Hợp đồng thuê có tồn tại không
2. Kiểm tra Hóa đơn có ít nhất 1 item không
3. Kiểm tra Đến hạn Ngày >= Issue Ngày
4. Kiểm tra tất cả các trường bắt buộc đã điền chưa
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Không thể issue hóa đơn

1. Kiểm tra hóa đơn có trạng thái `draft` không
2. Kiểm tra hóa đơn có items không
3. Kiểm tra Tổng Số tiền > 0
4. Liên hệ hỗ trợ nếu vẫn không thể issue

### Auto-tạo hóa đơn không tạo

1. Kiểm tra Hợp đồng thuê có Thanh toán Cycle không
2. Kiểm tra Billing Day có đúng không
3. Kiểm tra Hợp đồng thuê có trạng thái `active` không
4. Kiểm tra có cron job chạy không
5. Liên hệ hỗ trợ nếu vẫn không tạo

---

**Xem thêm:**
- [Nhân viên Hợp đồng thuê](./05-leases.md)
- [Nhân viên Thanh toán](./13-payments.md)
- [Nhân viên Booking Deposits](./10-booking-deposits.md)
- [Khách thuê Hóa đơn](../tenant/07-invoices.md)
- [Workflow Hợp đồng thuê to Thanh toán](../workflows/02-lease-to-payment.md)

**Cập nhật: 2025-01-XX

