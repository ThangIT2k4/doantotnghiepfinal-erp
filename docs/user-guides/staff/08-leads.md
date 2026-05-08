# QUẢN LÝ LEAD - STAFF

## Tổng quan

Chức năng này cho phép Nhân viên (Quản lý và Môi giới) quản lý lead (khách hàng tiềm năng) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, chuyển đổi trạng thái, convert to khách thuê/lease, và thống kê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả leads trong tổ chức (wildcard `*` = true)
- **Môi giới**: 
  - Truy cập module: Cần capability `crm.access`
  - Tạo lead: Cần capability `crm.lead.create`
  - Cập nhật lead: Cần capability `crm.lead.update`
  - Xem tất cả leads: Cần capability `crm.lead.view` hoặc `crm.lead.view_all`
  - Chỉ xem leads của mình: Có capability `crm.lead.view_own` (mặc định, chỉ xem leads được gán cho bất động sản của mình)
  - Xóa lead: Cần capability `crm.lead.delete`
  - Convert to khách thuê/lease: Cần capability `crm.lead.convert` hoặc `contract.lease.create`

**Route**: `/staff/leads`

## Các bước thực hiện

### 1. Xem danh sách Leads

1. Truy cập **Leads** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả leads trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (new, contacted, qualified, converted, lost)
   - Source (website, referral, walk_in, other)
   - Môi giới (nếu có nhiều agents)
   - Bất động sản, Phòng (nếu có nhiều bất động sản)
   - Ngày (today, this week, this month, tùy chỉnh range)
   - Sắp xếp theo created_at, updated_at, trạng thái

### 2. Xem chi tiết Lead

1. Click vào lead trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Lead ID: Mã lead
     - Name: Họ và tên
     - Phone: Số điện thoại
     - Email: Email
     - Source: Nguồn lead
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin liên quan:**
     - Môi giới: Người xử lý
     - Bất động sản, Phòng: Bất động sản quan tâm (nếu có)
     - Viewings: Lịch xem phòng (nếu có)
     - Booking Deposit: Đặt cọc (nếu có)
     - Hợp đồng thuê: Hợp đồng (nếu đã convert)
     - Khách thuê: Người thuê (nếu đã convert)
   - **Thông tin khác:**
     - Notes: Ghi chú
     - Created At, Updated At

### 3. Tạo Lead mới

1. Click **Tạo Lead** hoặc **+ New**
2. Điền thông tin:
   - **Name** (bắt buộc): Họ và tên
   - **Phone** (bắt buộc, unique): Số điện thoại
   - **Email** (tùy chọn, unique nếu có): Email
   - **Source** (bắt buộc): Nguồn lead (website, referral, walk_in, other)
   - **Môi giới** (tùy chọn): Chọn môi giới xử lý
   - **Bất động sản, Phòng** (tùy chọn): Chọn bất động sản/unit quan tâm
   - **Notes** (tùy chọn): Ghi chú
   - **Trạng thái** (tự động): `new`
3. Click **Lưu**
4. Lead được tạo với trạng thái `new`
5. Hệ thống gửi thông báo cho Môi giới (nếu có assign) và Quản lý

### 4. Cập nhật Lead Trạng thái

1. Truy cập chi tiết lead cần cập nhật trạng thái
2. Click **Change Trạng thái** hoặc **Cập nhật Trạng thái**
3. Chọn trạng thái mới:
   - **new**: Lead mới, chưa liên hệ
   - **contacted**: Đã liên hệ
   - **qualified**: Đã đủ điều kiện (quan tâm)
   - **converted**: Đã chuyển đổi (thành khách thuê/lease)
   - **lost**: Đã mất (không quan tâm)
4. Click **Lưu**
5. Hệ thống cập nhật trạng thái
6. Hệ thống gửi thông báo cho Môi giới và Quản lý

**Lưu ý**: 
- Trạng thái `qualified` thường được set sau khi viewing done và lead quan tâm
- Trạng thái `converted` được set tự động khi convert to khách thuê/lease

### 5. Convert to Khách thuê (Chuyển đổi thành Khách thuê)

1. Truy cập chi tiết lead cần convert
2. Click **Convert to Khách thuê** hoặc **Chuyển đổi thành Khách thuê**
3. Hệ thống tạo Khách thuê Người dùng từ thông tin Lead:
   - Name, Phone, Email từ Lead
   - Trạng thái: `active`
   - Role: `tenant`
   - Tổ chức: Tổ chức hiện tại
4. Lead trạng thái tự động chuyển sang `converted`
5. Khách thuê được tạo và link với Lead
6. Hệ thống gửi thông báo cho Môi giới và Quản lý

**Lưu ý**: 
- Convert to Khách thuê tạo người dùng account cho Lead
- Lead có thể được convert thành Hợp đồng thuê sau khi có Khách thuê

### 6. Convert to Hợp đồng thuê (Chuyển đổi thành Hợp đồng thuê)

1. Truy cập chi tiết lead có Khách thuê hoặc convert to Khách thuê trước
2. Click **Convert to Hợp đồng thuê** hoặc **Chuyển đổi thành Hợp đồng thuê**
3. Điền thông tin Hợp đồng thuê (tương tự tạo Hợp đồng thuê mới)
4. Click **Lưu**
5. Hợp đồng thuê được tạo và link với Lead
6. Lead trạng thái tự động chuyển sang `converted`
7. Hệ thống gửi thông báo cho Khách thuê, Môi giới, và Quản lý

**Lưu ý**: 
- Lead phải có Khách thuê hoặc convert to Khách thuê trước khi convert to Hợp đồng thuê
- Convert to Hợp đồng thuê tạo hợp đồng thuê từ lead

### 7. Cập nhật Lead

1. Truy cập chi tiết lead cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin: Name, Phone, Email, Source, Môi giới, Notes
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

### 8. Xóa Lead

1. Truy cập chi tiết lead cần xóa
2. Click **Xóa**
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa lead

### 9. Xem Thống kê

1. Truy cập **Leads** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Leads by Trạng thái: Phân bố theo trạng thái
   - Leads by Source: Phân bố theo nguồn
   - Leads by Môi giới: Phân bố theo môi giới
   - Leads by Period: Phân bố theo thời gian
   - Conversion Rate: Tỷ lệ chuyển đổi Lead → Hợp đồng thuê
   - Average Conversion Thời gian: Thời gian chuyển đổi trung bình

## Ràng buộc và điều kiện

### Validation Rules

- **Name**: Bắt buộc, không được để trống
- **Phone**: Bắt buộc, phải unique trong hệ thống
- **Email**: Tùy chọn, phải unique trong hệ thống (nếu có)
- **Source**: Bắt buộc, phải là một trong: website, referral, walk_in, other
- **Trạng thái**: Bắt buộc, phải là một trong: new, contacted, qualified, converted, lost

### Business Rules

1. **Phone Unique**
   - Phone phải unique trong hệ thống
   - Nếu Phone đã được sử dụng, có thể convert lead thành khách thuê

2. **Trạng thái Flow**
   - `new` → `contacted` → `qualified` → `converted`
   - `new` hoặc `contacted` → `lost`

3. **Convert to Khách thuê/Lease**
   - Convert to Khách thuê tạo người dùng account cho Lead
   - Convert to Hợp đồng thuê tạo hợp đồng thuê từ lead (cần có Khách thuê)

4. **Viewing Done tự động cập nhật Lead trạng thái**
   - Lead trạng thái = `qualified` nếu Lead quan tâm
   - Lead trạng thái = `lost` nếu Lead không quan tâm

## Ví dụ

### Ví dụ 1: Tạo Lead và Convert to Hợp đồng thuê

**Thông tin lead:**
- Name: `Nguyễn Văn A`
- Phone: `0123456789`
- Email: `nguyenvana@example.com`
- Source: `website`
- Trạng thái: `new`

**Các bước:**
1. Truy cập Leads
2. Click **Tạo Lead**
3. Điền thông tin trên
4. Click **Lưu**
5. Lead được tạo với trạng thái `new`
6. Môi giới liên hệ và tạo Viewing
7. Sau khi viewing done, Lead trạng thái chuyển sang `qualified`
8. Click **Convert to Khách thuê**
9. Khách thuê được tạo và link với Lead
10. Click **Convert to Hợp đồng thuê**
11. Điền thông tin Hợp đồng thuê
12. Hợp đồng thuê được tạo và link với Lead
13. Lead trạng thái chuyển sang `converted`

---

**Xem thêm:**
- [Quản lý Khách thuê](./07-tenants.md)
- [Quản lý Hợp đồng thuê](./05-leases.md)
- [Quản lý Viewings](./09-viewings.md)
- [Workflow Lead to Hợp đồng thuê](../workflows/01-lead-to-lease.md)

**Cập nhật: 2025-01-XX
