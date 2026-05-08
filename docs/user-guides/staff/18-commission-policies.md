# QUẢN LÝ CHÍNH SÁCH HOA HỒNG - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý chính sách hoa hồng (commission policies) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, và cấu hình trigger events, calculation types, apply limit months.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả commission policies trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `finance.access`
  - Xem commission policies: Không cần capability (mặc định có thể xem)
  - Tạo/Cập nhật: Chỉ Quản lý (Môi giới không có quyền)

**Route**: `/staff/commission-policies`

## Các bước thực hiện

### 1. Xem danh sách Commission Policies

1. Truy cập **Commission Policies** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả commission policies trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (hoạt động, không hoạt động)
   - Trigger Event (deposit_paid, lease_signed, invoice_paid, viewing_done, listing_published)
   - Calculation Loại (percent, flat, tiered)
   - Sắp xếp theo created_at, updated_at, trạng thái

### 2. Xem chi tiết Commission Policy

1. Click vào policy trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Policy ID: Mã policy
     - Name: Tên policy
     - Description: Mô tả
     - Trạng thái: Trạng thái hiện tại (hoạt động, không hoạt động)
   - **Trigger Event:**
     - Trigger Event: Sự kiện kích hoạt (deposit_paid, lease_signed, invoice_paid, viewing_done, listing_published)
     - Filters: JSON filters (nếu có)
   - **Calculation Loại:**
     - Calculation Loại: Loại tính toán (percent, flat, tiered)
     - Percent Value: Giá trị % (nếu Calculation Loại = percent)
     - Flat Số tiền: Số tiền cố định (nếu Calculation Loại = flat)
     - Tiered: Cấu hình tiered (nếu Calculation Loại = tiered)
   - **Limits:**
     - Min Số tiền: Số tiền tối thiểu (nếu có)
     - Cap Số tiền: Số tiền tối đa (nếu có)
     - Apply Limit Months: Số tháng giới hạn (chỉ cho invoice_paid)
   - **Basis:**
     - Basis: Cơ sở tính toán (cash, accrual)
   - **Thống kê:**
     - Tổng Commission Events: Tổng số sự kiện hoa hồng
     - Tổng Commission Số tiền: Tổng số tiền hoa hồng
     - Đã phê duyệt Events: Số sự kiện đã approve
     - Đã thanh toán Events: Số sự kiện đã thanh toán

### 3. Tạo Commission Policy mới

1. Click **Tạo Commission Policy** hoặc **+ New**
2. Điền thông tin:
   - **Name** (bắt buộc): Tên policy
   - **Description** (tùy chọn): Mô tả
   - **Trigger Event** (bắt buộc): Sự kiện kích hoạt:
     - `deposit_paid`: Đặt cọc đã thanh toán
     - `lease_signed`: Ký hợp đồng
     - `invoice_paid`: Hóa đơn đã thanh toán
     - `viewing_done`: Hoàn tất lịch xem phòng
     - `listing_published`: Đăng tin
   - **Filters** (tùy chọn): JSON filters để lọc dữ liệu (ví dụ: {"property_id": 1, "unit_type": "apartment"})
   - **Calculation Loại** (bắt buộc): Loại tính toán:
     - `percent`: Tính % từ số tiền
     - `flat`: Sử dụng số tiền cố định
     - `tiered`: Tính theo bậc thang
   - **Percent Value** (nếu Calculation Loại = percent): Giá trị % (ví dụ: 5 = 5%)
   - **Flat Số tiền** (nếu Calculation Loại = flat): Số tiền cố định (ví dụ: 1,000,000 VND)
   - **Tiered** (nếu Calculation Loại = tiered): Cấu hình tiered (JSON, ví dụ: [{"min": 0, "max": 10000000, "percent": 5}, {"min": 10000001, "max": 50000000, "percent": 7}])
   - **Min Số tiền** (tùy chọn): Số tiền tối thiểu (nếu có)
   - **Cap Số tiền** (tùy chọn): Số tiền tối đa (nếu có)
   - **Apply Limit Months** (chỉ cho invoice_paid): Số tháng giới hạn (ví dụ: 3 = chỉ tính hoa hồng cho 3 tháng đầu tiên)
   - **Basis** (bắt buộc): Cơ sở tính toán:
     - `cash`: Chờ thanh toán thực tế
     - `accrual`: Sẵn sàng thanh toán ngay
   - **Trạng thái** (bắt buộc): `active` hoặc `inactive`
3. Click **Lưu**
4. Commission Policy được tạo với trạng thái `active` hoặc `inactive`

**Lưu ý**: 
- Apply Limit Months chỉ áp dụng cho trigger `invoice_paid`
- Nếu Apply Limit Months = 3, chỉ tính hoa hồng cho 3 tháng đầu tiên (từ khi hợp đồng thuê bắt đầu)

### 4. Cập nhật Commission Policy

1. Truy cập chi tiết policy cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin:
   - Name, Description
   - Trigger Event, Filters
   - Calculation Loại, Percent Value, Flat Số tiền, Tiered
   - Min Số tiền, Cap Số tiền
   - Apply Limit Months
   - Basis
   - Trạng thái
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

**Lưu ý**: 
- Cập nhật policy sẽ không ảnh hưởng đến commission events đã tạo
- Policy mới sẽ áp dụng cho các sự kiện mới

### 5. Activate/Deactivate Policy

1. Truy cập chi tiết policy cần activate/deactivate
2. Click **Activate** hoặc **Deactivate**
3. Xác nhận
4. Policy trạng thái chuyển sang `active` hoặc `inactive`
5. Hệ thống gửi thông báo cho Quản lý

**Lưu ý**: 
- Chỉ policies có trạng thái `active` mới được áp dụng
- Policies không hoạt động sẽ không tạo commission events

### 6. Xóa Commission Policy

1. Truy cập chi tiết policy cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa policy

**Lưu ý**: 
- Có thể xóa policy bất cứ lúc nào
- Xóa policy sẽ không ảnh hưởng đến commission events đã tạo

### 7. Xem Thống kê

1. Truy cập **Commission Policies** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Policies by Trạng thái: Phân bố theo trạng thái
   - Policies by Trigger Event: Phân bố theo trigger event
   - Policies by Calculation Loại: Phân bố theo loại tính toán
   - Tổng Commission Số tiền: Tổng số tiền hoa hồng
   - Đã phê duyệt Commission Số tiền: Tổng số tiền hoa hồng đã approve
   - Đã thanh toán Commission Số tiền: Tổng số tiền hoa hồng đã thanh toán

## Ràng buộc và điều kiện

### Validation Rules

- **Name**: 
  - Bắt buộc
  - Không được để trống
  - Max 255 ký tự
- **Trigger Event**: 
  - Bắt buộc
  - Phải là một trong: deposit_paid, lease_signed, invoice_paid, viewing_done, listing_published
- **Calculation Loại**: 
  - Bắt buộc
  - Phải là một trong: percent, flat, tiered
- **Percent Value**: 
  - Bắt buộc nếu Calculation Loại = percent
  - Phải > 0 và <= 100
- **Flat Số tiền**: 
  - Bắt buộc nếu Calculation Loại = flat
  - Phải > 0
- **Tiered**: 
  - Bắt buộc nếu Calculation Loại = tiered
  - Phải là JSON hợp lệ
- **Min Số tiền**: 
  - Tùy chọn
  - Phải >= 0 (nếu có)
- **Cap Số tiền**: 
  - Tùy chọn
  - Phải >= Min Số tiền (nếu có)
- **Apply Limit Months**: 
  - Tùy chọn (chỉ cho invoice_paid)
  - Phải > 0 và <= 60 (nếu có)
- **Basis**: 
  - Bắt buộc
  - Phải là một trong: cash, accrual
- **Trạng thái**: 
  - Bắt buộc
  - Phải là `active` hoặc `inactive`

### Business Rules

1. **Apply Limit Months chỉ áp dụng cho invoice_paid**
   - Chỉ kiểm tra Apply Limit Months khi trigger = `invoice_paid`
   - Các trigger khác không áp dụng Apply Limit Months

2. **Commission Số tiền phải >= Min Số tiền và <= Cap Số tiền**
   - Nếu Commission Số tiền < Min Số tiền, sử dụng Min Số tiền
   - Nếu Commission Số tiền > Cap Số tiền, sử dụng Cap Số tiền

3. **Policy Trạng thái**
   - `active`: Policy đang hoạt động, sẽ tạo commission events
   - `inactive`: Policy không hoạt động, sẽ không tạo commission events

4. **Basis**
   - `cash`: Chờ thanh toán thực tế từ source
   - `accrual`: Sẵn sàng thanh toán ngay sau khi approve

5. **Filters JSON**
   - Filters JSON cho phép lọc dữ liệu theo điều kiện
   - Ví dụ: {"property_id": 1, "unit_type": "apartment"}

## Trạng thái và Workflow

### Commission Policy Trạng thái Flow

```
inactive ↔ active
```

- **hoạt động**: Policy đang hoạt động, sẽ tạo commission events
- **không hoạt động**: Policy không hoạt động, sẽ không tạo commission events

### Workflow Tạo Commission Policy

1. Quản lý tạo commission policy mới
2. Điền thông tin: Name, Trigger Event, Calculation Loại, Limits, Basis
3. Click Lưu → Policy có trạng thái `active` hoặc `inactive`
4. Policy sẽ được áp dụng cho các sự kiện mới (nếu hoạt động)

## Ví dụ

### Ví dụ 1: Tạo Policy với Percent

**Thông tin policy:**
- Name: `Hoa hồng từ Deposit - 3%`
- Trigger Event: `deposit_paid`
- Calculation Loại: `percent`
- Percent Value: `3` (3%)
- Min Số tiền: `100,000 VND`
- Cap Số tiền: `500,000 VND`
- Basis: `cash`
- Trạng thái: `active`

**Các bước:**
1. Truy cập Commission Policies
2. Click **Tạo Commission Policy**
3. Điền thông tin trên
4. Click **Lưu**
5. Policy được tạo với trạng thái `active`

**Khi deposit đã thanh toán:**
- Deposit Số tiền: 5,000,000 VND
- Commission = 5,000,000 × 3% = 150,000 VND
- Min Số tiền: 150,000 >= 100,000 ✓
- Cap Số tiền: 150,000 <= 500,000 ✓
- Commission Số tiền = 150,000 VND

### Ví dụ 2: Tạo Policy với Apply Limit Months

**Thông tin policy:**
- Name: `Hoa hồng từ Invoice - 5% - 3 tháng`
- Trigger Event: `invoice_paid`
- Calculation Loại: `percent`
- Percent Value: `5` (5%)
- Apply Limit Months: `3` (chỉ tính hoa hồng cho 3 tháng đầu tiên)
- Basis: `cash`
- Trạng thái: `active`

**Khi hóa đơn đã thanh toán:**
- Hợp đồng thuê: 12 tháng, rent = 10,000,000 VND/tháng
- Hóa đơn tháng 1: Tính hoa hồng ✓ (10M × 5% = 500K)
- Hóa đơn tháng 2: Tính hoa hồng ✓ (10M × 5% = 500K)
- Hóa đơn tháng 3: Tính hoa hồng ✓ (10M × 5% = 500K)
- Hóa đơn tháng 4: Không tính hoa hồng ✗ (đã quá 3 tháng)

## Lưu ý

1. **Apply Limit Months**
   - Chỉ áp dụng cho trigger `invoice_paid`
   - Giới hạn số tháng tính hoa hồng
   - Tính theo số hóa đơn đã thanh toán, không phải số tháng từ start ngày

2. **Min/Cap Số tiền**
   - Min Số tiền: Đảm bảo hoa hồng tối thiểu
   - Cap Số tiền: Giới hạn hoa hồng tối đa

3. **Basis**
   - Cash: Chờ thanh toán thực tế
   - Accrual: Sẵn sàng thanh toán ngay

4. **Filters JSON**
   - Sử dụng filters để áp dụng policy cho một số điều kiện cụ thể
   - Ví dụ: chỉ áp dụng cho bất động sản cụ thể hoặc phòng loại cụ thể

## Troubleshooting

### Không thể tạo policy

1. Kiểm tra tất cả các trường bắt buộc đã điền chưa
2. Kiểm tra Calculation Loại có đúng không
3. Kiểm tra Percent Value/Flat Số tiền có > 0 không
4. Kiểm tra Apply Limit Months có <= 60 không (nếu có)
5. Liên hệ hỗ trợ nếu vẫn không thể tạo

### Policy không tạo commission events

1. Kiểm tra policy có trạng thái `active` không
2. Kiểm tra trigger event có xảy ra không
3. Kiểm tra filters có phù hợp không
4. Kiểm tra Apply Limit Months (nếu có)
5. Liên hệ hỗ trợ nếu vẫn không tạo commission events

---

**Xem thêm:**
- [Quản lý Commission Events](./19-commission-events.md)
- [Workflow Commission Calculation](../workflows/04-commission-calculation.md)

**Cập nhật: 2025-01-XX

