# QUẢN LÝ MASTER LEASE - STAFF

## Tổng quan

Chức năng này cho phép Quản lý quản lý master hợp đồng thuê (hợp đồng thuê tổng) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, và quản lý sub-hợp đồng thuê.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả master hợp đồng thuê trong tổ chức (wildcard `*` = true)
- **Môi giới**: Không có quyền truy cập chức năng này (chỉ Quản lý)

**Route**: `/staff/master-leases`

## Các bước thực hiện

### 1. Xem danh sách Master Hợp đồng thuê

1. Truy cập **Master Hợp đồng thuê** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả master hợp đồng thuê trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (hoạt động, terminated, expired)
   - Bất động sản
   - Landlord (nếu có nhiều landlords)
   - Sắp xếp theo start_date, end_date, created_at, trạng thái

### 2. Xem chi tiết Master Hợp đồng thuê

1. Click vào master hợp đồng thuê trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Master Hợp đồng thuê ID: Mã master hợp đồng thuê
     - Bất động sản: Bất động sản
     - Landlord: Chủ nhà
     - Start Ngày, End Ngày: Ngày bắt đầu và kết thúc
     - Trạng thái: Trạng thái hiện tại
   - **Thông tin tài chính:**
     - Master Rent: Tiền thuê master
     - Deposit Số tiền: Tiền cọc
   - **Sub-hợp đồng thuê:**
     - Danh sách sub-hợp đồng thuê (regular hợp đồng thuê) liên quan
   - **Company Hóa đơn:**
     - Danh sách company hóa đơn liên quan

### 3. Tạo Master Hợp đồng thuê mới

1. Click **Tạo Master Hợp đồng thuê** hoặc **+ New**
2. Điền thông tin:
   - **Bất động sản** (bắt buộc): Chọn bất động sản
   - **Landlord** (bắt buộc): Chọn landlord
   - **Start Ngày**, **End Ngày**: Ngày bắt đầu và kết thúc
   - **Master Rent** (bắt buộc): Tiền thuê master (> 0)
   - **Deposit Số tiền**: Tiền cọc
   - **Trạng thái** (tự động): `active`
   - **Note**: Ghi chú
3. Click **Lưu**
4. Master Hợp đồng thuê được tạo với trạng thái `active`

### 4. Cập nhật Master Hợp đồng thuê

1. Truy cập chi tiết master hợp đồng thuê cần cập nhật
2. Click **Chỉnh sửa** (chỉ khi trạng thái = `active`)
3. Cập nhật thông tin: Dates, Master Rent, Deposit Số tiền, Note
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

### 5. Terminate Master Hợp đồng thuê (Chấm dứt)

1. Truy cập chi tiết master hợp đồng thuê cần terminate
2. Click **Terminate** hoặc **Chấm dứt**
3. Nhập Termination Ngày
4. Xác nhận terminate
5. Master Hợp đồng thuê trạng thái chuyển sang `terminated`
6. Hệ thống gửi thông báo cho Quản lý

### 6. Xem Sub-hợp đồng thuê

1. Truy cập chi tiết master hợp đồng thuê
2. Scroll đến phần **Sub-hợp đồng thuê**
3. Hệ thống hiển thị danh sách regular hợp đồng thuê (sub-hợp đồng thuê) liên quan
4. Click vào sub-hợp đồng thuê để xem chi tiết

### 7. Xóa Master Hợp đồng thuê

1. Truy cập chi tiết master hợp đồng thuê cần xóa
2. Click **Xóa** (chỉ khi không có sub-hợp đồng thuê)
3. Xác nhận xóa
4. Hệ thống thực hiện soft xóa master hợp đồng thuê

### 8. Xem Thống kê

1. Truy cập **Master Hợp đồng thuê** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Master Hợp đồng thuê by Trạng thái: Phân bố theo trạng thái
   - Tổng Master Rent: Tổng tiền thuê master
   - Tổng Sub-hợp đồng thuê: Tổng số sub-hợp đồng thuê

## Ràng buộc và điều kiện

### Validation Rules

- **Bất động sản**: Bắt buộc, phải tồn tại và thuộc về tổ chức
- **Landlord**: Bắt buộc, phải tồn tại
- **Master Rent**: Bắt buộc, phải > 0
- **Start Ngày**: Bắt buộc
- **End Ngày**: Tùy chọn, phải >= Start Ngày (nếu có)

### Business Rules

1. **Master Hợp đồng thuê và Sub-hợp đồng thuê**
   - Master Hợp đồng thuê là hợp đồng thuê tổng từ landlord
   - Sub-hợp đồng thuê là các regular hợp đồng thuê cho khách thuê

2. **Company Hóa đơn**
   - Master Hợp đồng thuê có thể có company hóa đơn để thanh toán cho landlord

3. **Trạng thái Flow**
   - `active` → `terminated` hoặc `expired`

## Ví dụ

### Ví dụ 1: Tạo Master Hợp đồng thuê

**Thông tin master hợp đồng thuê:**
- Bất động sản: Bất động sản ABC
- Landlord: Landlord XYZ
- Start Ngày: 2025-01-01
- End Ngày: 2025-12-31
- Master Rent: 50,000,000 VND/tháng
- Trạng thái: `active`

**Các bước:**
1. Truy cập Master Hợp đồng thuê
2. Click **Tạo Master Hợp đồng thuê**
3. Điền thông tin trên
4. Click **Lưu**
5. Master Hợp đồng thuê được tạo với trạng thái `active`

---

**Xem thêm:**
- [Quản lý Hợp đồng thuê](./05-leases.md)
- [Quản lý Company Hóa đơn](./24-company-invoices.md)

**Cập nhật: 2025-01-XX

