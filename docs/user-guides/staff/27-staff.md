# QUẢN LÝ NHÂN VIÊN - STAFF

## Tổng quan

Chức năng này cho phép Quản lý quản lý nhân viên (nhân viên) trong tổ chức, bao gồm tạo, xem, cập nhật, xóa, assign bất động sản, và quản lý salary hợp đồng, commission events.

## Quyền truy cập

- **Quản lý**: Có quyền đầy đủ (CRUD) cho tất cả nhân viên trong tổ chức (wildcard `*` = true)
- **Môi giới**: Không có quyền truy cập chức năng này (chỉ Quản lý)

**Route**: `/staff/staff`

## Các bước thực hiện

### 1. Xem danh sách Nhân viên

1. Truy cập **Nhân viên** từ menu Nhân viên
2. Hệ thống hiển thị danh sách tất cả nhân viên trong tổ chức
3. Có thể lọc theo:
   - Trạng thái (hoạt động, không hoạt động)
   - Role (môi giới, quản lý, landlord)
   - Tìm kiếm by name, phone, email
   - Sắp xếp theo name, created_at, trạng thái

### 2. Xem chi tiết Nhân viên

1. Click vào nhân viên trong danh sách hoặc action **Xem**
2. Hệ thống hiển thị thông tin chi tiết:
   - **Thông tin cơ bản:**
     - Người dùng ID, Name, Phone, Email
     - Role, Trạng thái
   - **Thông tin liên quan:**
     - Salary Hợp đồng: Hợp đồng lương
     - Commission Events: Sự kiện hoa hồng
     - Assigned Bất động sản: Bất động sản được giao
     - Hợp đồng thuê: Hợp đồng được giao
     - Payroll Payslips: Phiếu lương
   - **Thống kê:**
     - Tổng Hợp đồng thuê: Tổng số hợp đồng
     - Tổng Commission: Tổng hoa hồng
     - Tổng Revenue: Tổng doanh thu

### 3. Tạo Nhân viên mới

1. Click **Tạo Nhân viên** hoặc **+ New**
2. Điền thông tin (tương tự tạo Người dùng):
   - **Name**, **Phone**, **Email**
   - **Password**
   - **Role** (môi giới, quản lý, landlord)
   - **Trạng thái** (hoạt động, không hoạt động)
3. Click **Lưu**
4. Nhân viên Người dùng được tạo với role tương ứng

### 4. Assign Bất động sản (Gán Bất động sản)

1. Truy cập chi tiết nhân viên
2. Scroll đến phần **Assigned Bất động sản**
3. Click **Assign Bất động sản** hoặc **Gán Bất động sản**
4. Chọn bất động sản từ danh sách
5. Click **Lưu**
6. Bất động sản được gán cho nhân viên

### 5. Cập nhật Nhân viên

1. Truy cập chi tiết nhân viên cần cập nhật
2. Click **Chỉnh sửa**
3. Cập nhật thông tin: Name, Phone, Email, Trạng thái
4. Click **Lưu**
5. Hệ thống cập nhật và hiển thị thông báo thành công

### 6. Xem Thống kê

1. Truy cập **Nhân viên** → **Thống kê**
2. Hệ thống hiển thị thống kê:
   - Nhân viên by Role: Phân bố theo vai trò
   - Nhân viên by Trạng thái: Phân bố theo trạng thái
   - Tổng Nhân viên: Tổng số nhân viên

## Ràng buộc và điều kiện

### Validation Rules

- Tương tự tạo Người dùng

### Business Rules

1. **Nhân viên Role**
   - Môi giới: Nhân viên môi giới
   - Quản lý: Quản lý
   - Landlord: Chủ nhà

2. **Assign Bất động sản**
   - Nhân viên có thể được gán nhiều bất động sản
   - Giúp quản lý và theo dõi

3. **Salary Hợp đồng và Commissions**
   - Nhân viên có thể có salary hợp đồng và commission events
   - Xem chi tiết trong các module tương ứng

## Ví dụ

### Ví dụ 1: Tạo Nhân viên và Assign Bất động sản

**Thông tin nhân viên:**
- Name: `Nguyễn Văn A`
- Phone: `0123456789`
- Email: `nguyenvana@example.com`
- Role: `agent`
- Trạng thái: `active`

**Các bước:**
1. Truy cập Nhân viên
2. Click **Tạo Nhân viên**
3. Điền thông tin trên
4. Click **Lưu**
5. Nhân viên được tạo
6. Assign Bất động sản cho nhân viên
7. Chọn bất động sản từ danh sách
8. Click **Lưu**

---

**Xem thêm:**
- [Quản lý Người dùng](./28-users.md)
- [Quản lý Salary Hợp đồng](./20-salary-contracts.md)

**Cập nhật: 2025-01-XX

